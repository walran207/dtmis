<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/workflow.php';
require_once dirname(__DIR__, 2) . '/config/offline-sync.php';

const CREATE_DOC_MAX_FILE_SIZE = 15728640; // 15 MB
const CREATE_DOC_MAX_ATTACHMENTS = 40;
const CREATE_DOC_MAX_TOTAL_UPLOAD_SIZE = 251658240; // 240 MB
const INTAKE_IDEMPOTENCY_TABLE = 'api_idempotency_operations';
const INTAKE_IDEMPOTENCY_ACTION_KEY = 'create_document_intake';

final class IntakeConflictException extends RuntimeException
{
}

function normalize_operation_id(string $raw): string
{
    $operationId = trim($raw);
    if ($operationId === '') {
        return '';
    }

    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{7,79}$/', $operationId)) {
        throw new InvalidArgumentException('Invalid operation_id format.');
    }

    return $operationId;
}

function intake_rollout_block_message(): string
{
    $policy = app_offline_policy_for_role((string)($_SESSION['role_name'] ?? ''));
    $pilotRoleKey = app_normalize_role_key((string)($policy['pilot_role_key'] ?? ''));
    if ($pilotRoleKey !== '') {
        return 'Offline intake sync is currently in pilot for role ' . $pilotRoleKey . ' only. Reconnect and submit online.';
    }

    return 'Offline intake sync is disabled for this role during rollout. Reconnect and submit online.';
}

function intake_color_classification_options(): array
{
    return [
        'YELLOW_URGENT' => 'Yellow - Urgent',
        'PINK_SIMPLE' => 'Pink - Simple',
        'BLUE_COMPLEX_HIGHLY_TECHNICAL' => 'Blue - Complex/Highly Technical',
        'GREEN_RELEASED' => 'Green - Released',
    ];
}

function intake_build_success_payload(
    int $documentId,
    string $trackingId,
    int $attachmentCount,
    string $message,
    string $operationId = ''
): array {
    $payload = [
        'ok' => true,
        'message' => $message,
        'document_id' => $documentId,
        'tracking_id' => $trackingId,
        'attachment_count' => max(0, $attachmentCount),
        'tracking_slip_url' => app_url('tracking-slip.php?tracking_id=' . urlencode($trackingId)),
    ];
    if ($operationId !== '') {
        $payload['operation_id'] = $operationId;
    }

    return $payload;
}

function intake_idempotency_ensure_table(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $tableName = INTAKE_IDEMPOTENCY_TABLE;
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `action_key` VARCHAR(64) NOT NULL,
            `operation_id` VARCHAR(80) NOT NULL,
            `request_hash` CHAR(64) NOT NULL,
            `status` ENUM('PENDING','COMPLETED','FAILED') NOT NULL DEFAULT 'PENDING',
            `document_id` INT NULL,
            `tracking_id` VARCHAR(64) NULL,
            `attachment_count` INT NULL,
            `response_code` SMALLINT UNSIGNED NULL,
            `response_json` MEDIUMTEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_idempotency_operation` (`user_id`, `action_key`, `operation_id`),
            KEY `idx_idempotency_status_updated` (`status`, `updated_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ensured = true;
}

function intake_idempotency_request_hash(array $payload, array $uploadedFiles): string
{
    $fileMeta = [];
    foreach ($uploadedFiles as $index => $file) {
        $fileMeta[] = [
            'index' => (int)$index,
            'name' => trim((string)($file['name'] ?? '')),
            'size' => (int)($file['size'] ?? 0),
            'type' => strtolower(trim((string)($file['type'] ?? ''))),
            'error' => (int)($file['error'] ?? UPLOAD_ERR_NO_FILE),
        ];
    }

    $normalized = [
        'subject' => trim((string)($payload['subject'] ?? '')),
        'source_type' => strtoupper(trim((string)($payload['source_type'] ?? 'INTERNAL'))),
        'external_client_name' => trim((string)($payload['external_client_name'] ?? '')),
        'document_type_id' => (int)($payload['document_type_id'] ?? 0),
        'action_required' => trim((string)($payload['action_required'] ?? '')),
        'remarks' => trim((string)($payload['remarks'] ?? '')),
        'custom_document_type_name' => trim((string)($payload['custom_document_type_name'] ?? '')),
        'intake_complexity_type' => trim((string)($payload['intake_complexity_type'] ?? 'Simple')),
        'intake_complexity_days' => trim((string)($payload['intake_complexity_days'] ?? '')),
        'intake_color_classification' => strtoupper(trim((string)($payload['intake_color_classification'] ?? 'PINK_SIMPLE'))),
        'attachment_count_expected' => (int)($payload['attachment_count_expected'] ?? 0),
        'files' => $fileMeta,
    ];

    return hash('sha256', (string)json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function intake_idempotency_begin(
    PDO $pdo,
    int $userId,
    string $operationId,
    string $requestHash
): array {
    intake_idempotency_ensure_table($pdo);

    $tableName = INTAKE_IDEMPOTENCY_TABLE;
    $actionKey = INTAKE_IDEMPOTENCY_ACTION_KEY;
    $pdo->beginTransaction();
    try {
        $select = $pdo->prepare(
            "SELECT id, request_hash, status, document_id, tracking_id, attachment_count, response_code, response_json, updated_at
             FROM `{$tableName}`
             WHERE user_id = :user_id
               AND action_key = :action_key
               AND operation_id = :operation_id
             LIMIT 1
             FOR UPDATE"
        );
        $select->execute([
            'user_id' => $userId,
            'action_key' => $actionKey,
            'operation_id' => $operationId,
        ]);
        $existing = $select->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$existing) {
            try {
                $insert = $pdo->prepare(
                    "INSERT INTO `{$tableName}` (
                        user_id, action_key, operation_id, request_hash, status, created_at, updated_at
                    ) VALUES (
                        :user_id, :action_key, :operation_id, :request_hash, 'PENDING', NOW(), NOW()
                    )"
                );
                $insert->execute([
                    'user_id' => $userId,
                    'action_key' => $actionKey,
                    'operation_id' => $operationId,
                    'request_hash' => $requestHash,
                ]);
                $pdo->commit();
                return ['mode' => 'process'];
            } catch (PDOException $insertException) {
                if ((string)$insertException->getCode() !== '23000') {
                    throw $insertException;
                }
                $select->execute([
                    'user_id' => $userId,
                    'action_key' => $actionKey,
                    'operation_id' => $operationId,
                ]);
                $existing = $select->fetch(PDO::FETCH_ASSOC) ?: null;
                if (!$existing) {
                    throw $insertException;
                }
            }
        }

        $storedHash = (string)($existing['request_hash'] ?? '');
        if ($storedHash !== '' && !hash_equals($storedHash, $requestHash)) {
            throw new IntakeConflictException('This operation_id is already used for a different intake request.');
        }

        $status = strtoupper((string)($existing['status'] ?? 'PENDING'));
        $documentId = (int)($existing['document_id'] ?? 0);
        $trackingId = trim((string)($existing['tracking_id'] ?? ''));
        $attachmentCount = (int)($existing['attachment_count'] ?? 0);
        $responseCode = (int)($existing['response_code'] ?? 200);
        $responseJson = trim((string)($existing['response_json'] ?? ''));

        if ($status === 'COMPLETED') {
            $decoded = $responseJson !== '' ? json_decode($responseJson, true) : null;
            if (is_array($decoded) && !empty($decoded['ok'])) {
                $decoded['operation_id'] = $operationId;
                $pdo->commit();
                return [
                    'mode' => 'replay',
                    'code' => $responseCode > 0 ? $responseCode : 200,
                    'payload' => $decoded,
                ];
            }

            if ($documentId > 0 && $trackingId !== '') {
                $pdo->commit();
                return [
                    'mode' => 'replay',
                    'code' => 200,
                    'payload' => intake_build_success_payload(
                        $documentId,
                        $trackingId,
                        $attachmentCount,
                        'Document intake already created (idempotent replay).',
                        $operationId
                    ),
                ];
            }
        }

        if ($documentId > 0 && $trackingId !== '') {
            $pdo->commit();
            return [
                'mode' => 'replay',
                'code' => 200,
                'payload' => intake_build_success_payload(
                    $documentId,
                    $trackingId,
                    $attachmentCount,
                    'Document intake already created (idempotent replay).',
                    $operationId
                ),
            ];
        }

        if ($status === 'PENDING') {
            $updatedAtRaw = (string)($existing['updated_at'] ?? '');
            $updatedAtTs = strtotime($updatedAtRaw);
            if ($updatedAtTs !== false && (time() - $updatedAtTs) < 45) {
                throw new IntakeConflictException('This intake request is currently processing. Please retry shortly.');
            }
        }

        $reset = $pdo->prepare(
            "UPDATE `{$tableName}`
             SET status = 'PENDING',
                 response_code = NULL,
                 response_json = NULL,
                 updated_at = NOW()
             WHERE id = :id"
        );
        $reset->execute(['id' => (int)$existing['id']]);

        $pdo->commit();
        return ['mode' => 'process'];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function intake_idempotency_mark_created_document(PDO $pdo, int $userId, string $operationId, int $documentId, string $trackingId): void
{
    if ($operationId === '') {
        return;
    }

    $tableName = INTAKE_IDEMPOTENCY_TABLE;
    $stmt = $pdo->prepare(
        "UPDATE `{$tableName}`
         SET document_id = :document_id,
             tracking_id = :tracking_id,
             updated_at = NOW()
         WHERE user_id = :user_id
           AND action_key = :action_key
           AND operation_id = :operation_id"
    );
    $stmt->execute([
        'document_id' => $documentId,
        'tracking_id' => $trackingId,
        'user_id' => $userId,
        'action_key' => INTAKE_IDEMPOTENCY_ACTION_KEY,
        'operation_id' => $operationId,
    ]);
}

function intake_idempotency_mark_completed(PDO $pdo, int $userId, string $operationId, array $payload, int $responseCode = 200): void
{
    if ($operationId === '') {
        return;
    }

    $tableName = INTAKE_IDEMPOTENCY_TABLE;
    $encoded = (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt = $pdo->prepare(
        "UPDATE `{$tableName}`
         SET status = 'COMPLETED',
             document_id = :document_id,
             tracking_id = :tracking_id,
             attachment_count = :attachment_count,
             response_code = :response_code,
             response_json = :response_json,
             updated_at = NOW()
         WHERE user_id = :user_id
           AND action_key = :action_key
           AND operation_id = :operation_id"
    );
    $stmt->execute([
        'document_id' => (int)($payload['document_id'] ?? 0),
        'tracking_id' => (string)($payload['tracking_id'] ?? ''),
        'attachment_count' => (int)($payload['attachment_count'] ?? 0),
        'response_code' => max(100, min(599, $responseCode)),
        'response_json' => $encoded,
        'user_id' => $userId,
        'action_key' => INTAKE_IDEMPOTENCY_ACTION_KEY,
        'operation_id' => $operationId,
    ]);
}

function intake_idempotency_mark_failed(PDO $pdo, int $userId, string $operationId, string $message, int $responseCode): void
{
    if ($operationId === '') {
        return;
    }

    $tableName = INTAKE_IDEMPOTENCY_TABLE;
    $safeCode = max(100, min(599, $responseCode));
    $errorPayload = [
        'ok' => false,
        'operation_id' => $operationId,
        'message' => trim($message) === '' ? 'Unable to create document right now.' : trim($message),
    ];

    $stmt = $pdo->prepare(
        "UPDATE `{$tableName}`
         SET status = 'FAILED',
             response_code = :response_code,
             response_json = :response_json,
             updated_at = NOW()
         WHERE user_id = :user_id
           AND action_key = :action_key
           AND operation_id = :operation_id"
    );
    $stmt->execute([
        'response_code' => $safeCode,
        'response_json' => (string)json_encode($errorPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'user_id' => $userId,
        'action_key' => INTAKE_IDEMPOTENCY_ACTION_KEY,
        'operation_id' => $operationId,
    ]);
}

function parse_ini_size_to_bytes(string $raw): int
{
    $value = trim($raw);
    if ($value === '') {
        return 0;
    }

    $lastChar = strtolower(substr($value, -1));
    $number = (float)$value;
    if (!is_finite($number) || $number <= 0) {
        return 0;
    }

    switch ($lastChar) {
        case 'g':
            $number *= 1024;
            // no break
        case 'm':
            $number *= 1024;
            // no break
        case 'k':
            $number *= 1024;
            break;
    }

    return (int)round($number);
}

function intake_complexity_defaults(string $categoryRaw): array
{
    $normalized = strtolower(trim(str_replace(['_', '-'], ' ', $categoryRaw)));
    if (str_contains($normalized, 'highly') || str_contains($normalized, 'technical')) {
        return ['category' => 'Highly Technical', 'days' => 20];
    }
    if ($normalized === 'others' || $normalized === 'other') {
        return ['category' => 'Others', 'days' => 3];
    }
    if (str_contains($normalized, 'complex')) {
        return ['category' => 'Complex', 'days' => 7];
    }

    return ['category' => 'Simple', 'days' => 3];
}

function normalize_uploaded_files(?array $fileBag): array
{
    if (!is_array($fileBag) || !isset($fileBag['name'])) {
        return [];
    }

    if (!is_array($fileBag['name'])) {
        return [[
            'name' => (string)($fileBag['name'] ?? ''),
            'type' => (string)($fileBag['type'] ?? ''),
            'tmp_name' => (string)($fileBag['tmp_name'] ?? ''),
            'error' => (int)($fileBag['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int)($fileBag['size'] ?? 0),
        ]];
    }

    $files = [];
    $count = count($fileBag['name']);
    for ($i = 0; $i < $count; $i += 1) {
        $files[] = [
            'name' => (string)($fileBag['name'][$i] ?? ''),
            'type' => (string)($fileBag['type'][$i] ?? ''),
            'tmp_name' => (string)($fileBag['tmp_name'][$i] ?? ''),
            'error' => (int)($fileBag['error'][$i] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int)($fileBag['size'][$i] ?? 0),
        ];
    }

    return $files;
}

function validate_uploaded_intake_files(array $files): array
{
    $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt'];
    $allowedMime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'text/plain',
    ];

    $validated = [];
    $nonEmptyFileCount = 0;
    $totalUploadSize = 0;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($files as $file) {
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $nonEmptyFileCount++;
        if ($nonEmptyFileCount > CREATE_DOC_MAX_ATTACHMENTS) {
            throw new InvalidArgumentException('You can upload up to 40 attachments per intake.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                throw new InvalidArgumentException('One or more attachments exceeded server upload limits. Please upload smaller files or fewer files at once.');
            }
            throw new InvalidArgumentException('One of the uploaded files failed to upload. Please try again.');
        }

        $originalName = trim((string)($file['name'] ?? ''));
        if ($originalName === '') {
            throw new InvalidArgumentException('Uploaded file has no filename.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            // Skip empty file slots instead of throwing, allowing remarks-only submission.
            continue;
        }
        if ($size > CREATE_DOC_MAX_FILE_SIZE) {
            throw new InvalidArgumentException('Each attachment must be 15 MB or smaller.');
        }
        $totalUploadSize += $size;
        if ($totalUploadSize > CREATE_DOC_MAX_TOTAL_UPLOAD_SIZE) {
            throw new InvalidArgumentException('Total upload size is too large. Keep the combined attachments around 240 MB or less per intake.');
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new InvalidArgumentException('Invalid uploaded file payload.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new InvalidArgumentException('Unsupported attachment type: ' . $originalName);
        }

        $detectedMime = strtolower((string)$finfo->file($tmpName));
        if ($detectedMime !== '' && !in_array($detectedMime, $allowedMime, true)) {
            throw new InvalidArgumentException('Unsupported attachment MIME type for: ' . $originalName);
        }

        $validated[] = [
            'original_name' => $originalName,
            'tmp_name' => $tmpName,
            'extension' => $extension,
        ];
    }

    return $validated;
}

function store_document_attachments(PDO $pdo, int $documentId, int $uploadedByUserId, array $validatedFiles): int
{
    if ($documentId <= 0 || empty($validatedFiles)) {
        return 0;
    }

    $tableCheck = $pdo->query("SHOW TABLES LIKE 'document_attachments'");
    if (!$tableCheck || !$tableCheck->fetchColumn()) {
        throw new RuntimeException('Attachment storage table is not available.');
    }

    $versionStmt = $pdo->prepare('SELECT COALESCE(MAX(version_number), 0) FROM document_attachments WHERE document_id = :document_id');
    $versionStmt->execute(['document_id' => $documentId]);
    $currentVersion = (int)($versionStmt->fetchColumn() ?: 0);

    $year = date('Y');
    $month = date('m');
    $relativeDir = 'storage/uploads/attachments/' . $year . '/' . $month;
    $absoluteDir = dirname(__DIR__) . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Unable to create attachment storage directory.');
    }

    $insert = $pdo->prepare(
        'INSERT INTO document_attachments (
            document_id, uploaded_by, file_name, file_path, version_number, is_internal_only, uploaded_at
         ) VALUES (
            :document_id, :uploaded_by, :file_name, :file_path, :version_number, :is_internal_only, NOW()
         )'
    );

    $saved = 0;
    foreach ($validatedFiles as $index => $file) {
        $safeBaseName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$file['original_name']) ?? 'attachment.' . $file['extension'];
        if ($safeBaseName === '') {
            $safeBaseName = 'attachment.' . $file['extension'];
        }
        $versionNumber = $currentVersion + $index + 1;
        $fileName = 'doc_' . $documentId . '_v' . $versionNumber . '_' . $safeBaseName;
        $relativePath = $relativeDir . '/' . $fileName;
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file((string)$file['tmp_name'], $absolutePath)) {
            throw new RuntimeException('Failed to store attachment file: ' . (string)$file['original_name']);
        }

        $insert->execute([
            'document_id' => $documentId,
            'uploaded_by' => $uploadedByUserId,
            'file_name' => (string)$file['original_name'],
            'file_path' => $relativePath,
            'version_number' => $versionNumber,
            'is_internal_only' => 0,
        ]);
        $saved++;
    }

    return $saved;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
$serverPostMax = parse_ini_size_to_bytes((string)ini_get('post_max_size'));
if ($contentLength > 0 && $serverPostMax > 0 && $contentLength > $serverPostMax) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'message' => 'Total upload payload is too large for the server. Please upload fewer/smaller files.']);
    exit;
}

$csrfToken = (string)($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$subject = trim((string)($_POST['subject'] ?? ''));
$sourceType = strtoupper(trim((string)($_POST['source_type'] ?? 'INTERNAL')));
$externalClientName = trim((string)($_POST['external_client_name'] ?? ''));
$documentTypeId = (int)($_POST['document_type_id'] ?? 0);
$actionRequired = trim((string)($_POST['action_required'] ?? ''));
$remarks = trim((string)($_POST['remarks'] ?? ''));
$customTypeName = trim((string)($_POST['custom_document_type_name'] ?? ''));
$intakeComplexityTypeRaw = trim((string)($_POST['intake_complexity_type'] ?? 'Simple'));
$intakeComplexityDaysRaw = trim((string)($_POST['intake_complexity_days'] ?? ''));
$intakeColorClassificationRaw = strtoupper(trim((string)($_POST['intake_color_classification'] ?? 'PINK_SIMPLE')));
$expectedAttachmentCount = (int)($_POST['attachment_count_expected'] ?? 0);
$operationIdRaw = (string)($_POST['operation_id'] ?? '');
$syncModeRaw = trim((string)($_POST['sync_mode'] ?? ''));
$syncHeaderRaw = trim((string)($_SERVER['HTTP_X_EDATS_OUTBOX_SYNC'] ?? ''));
$isOutboxSyncRequest = $syncModeRaw === '1' || $syncHeaderRaw === '1';
$operationId = '';
try {
    $operationId = normalize_operation_id($operationIdRaw);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
    exit;
}
$uploadedFiles = normalize_uploaded_files($_FILES['attachment_files'] ?? null);

$complexityDefaults = intake_complexity_defaults($intakeComplexityTypeRaw);
$intakeComplexityType = (string)$complexityDefaults['category'];
$intakeComplexityDays = $intakeComplexityDaysRaw === ''
    ? (int)$complexityDefaults['days']
    : (int)$intakeComplexityDaysRaw;
if ($intakeColorClassificationRaw === '') {
    $intakeColorClassificationRaw = 'PINK_SIMPLE';
}
$intakeColorOptions = intake_color_classification_options();
if (!array_key_exists($intakeColorClassificationRaw, $intakeColorOptions)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid color classification selected.']);
    exit;
}
 $intakeColorClassificationLabel = (string)$intakeColorOptions[$intakeColorClassificationRaw];
if ($intakeComplexityDays < 1 || $intakeComplexityDays > 365) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Days must be a whole number from 1 to 365.']);
    exit;
}

if ($expectedAttachmentCount > CREATE_DOC_MAX_ATTACHMENTS) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'You can upload up to 40 attachments per intake.']);
    exit;
}

if ($subject === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Subject is required.']);
    exit;
}

if ($sourceType !== 'INTERNAL' && $sourceType !== 'EXTERNAL') {
    $sourceType = 'INTERNAL';
}

if ($sourceType === 'EXTERNAL' && $externalClientName === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'External client name is required for external intake.']);
    exit;
}

$actorUserId = (int)($_SESSION['user_id'] ?? 0);
$pdo = null;

try {
    $validatedFiles = validate_uploaded_intake_files($uploadedFiles);
    if ($expectedAttachmentCount > 0 && count($validatedFiles) < $expectedAttachmentCount) {
        $serverMaxUploads = (int)ini_get('max_file_uploads');
        if ($serverMaxUploads > 0 && $expectedAttachmentCount > $serverMaxUploads) {
            throw new InvalidArgumentException(
                'Only ' . count($validatedFiles) . ' of ' . $expectedAttachmentCount
                . ' attachments were accepted by the server. Please upload in smaller batches or increase server max_file_uploads.'
            );
        }
        throw new InvalidArgumentException('Some selected attachments were not uploaded successfully. Please try again.');
    }
    if (count($validatedFiles) === 0 && $remarks === '') {
        throw new InvalidArgumentException('Soft copy not uploaded. Please provide remarks explaining why no digital file was attached.');
    }

    if ($customTypeName !== '') {
        throw new InvalidArgumentException('Custom type creation was moved to Document Type Requests.');
    }

    if ($documentTypeId <= 0) {
        throw new InvalidArgumentException('Please select a document type.');
    }

    $pdo = getDatabaseConnection();
    workflow_ensure_document_arta_override_columns($pdo);
    workflow_ensure_document_row_version_column($pdo);
    $actor = workflow_get_user_context($pdo, $actorUserId);
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));

    if ($isOutboxSyncRequest && !app_offline_is_intake_outbox_enabled((string)($actor['role_name'] ?? ''))) {
        $rolloutMessage = intake_rollout_block_message();
        offline_sync_log_event($pdo, [
            'event_type' => 'ROLLOUT_BLOCKED',
            'route_kind' => 'intake_create',
            'operation_id' => $operationId,
            'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'source' => 'server-create-document',
            'http_status' => 403,
            'message' => $rolloutMessage,
            'payload' => [
                'is_outbox_sync' => true,
                'role_key' => $actorRoleKey,
            ],
        ]);
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'message' => $rolloutMessage,
            'offline_rollout_blocked' => true,
        ]);
        exit;
    }

    $remarksParts = [];
    if ($intakeColorClassificationLabel !== '') {
        $remarksParts[] = 'Color classification: ' . $intakeColorClassificationLabel;
    }
    if ($actionRequired !== '') {
        $remarksParts[] = 'Action required: ' . $actionRequired;
    }
    if ($remarks !== '') {
        $remarksParts[] = $remarks;
    }

    if ($operationId !== '') {
        $idempotencyDecision = intake_idempotency_begin(
            $pdo,
            $actorUserId,
            $operationId,
            intake_idempotency_request_hash($_POST, $uploadedFiles)
        );
        if (($idempotencyDecision['mode'] ?? '') === 'replay') {
            http_response_code((int)($idempotencyDecision['code'] ?? 200));
            echo json_encode($idempotencyDecision['payload'] ?? ['ok' => false, 'message' => 'Unable to replay request safely.']);
            exit;
        }
    }

    $created = workflow_create_document($pdo, [
        'subject' => $subject,
        'document_type_id' => $documentTypeId,
        'source_type' => $sourceType,
        'external_client_name' => $sourceType === 'EXTERNAL' ? $externalClientName : null,
        'originating_office_id' => (int)($actor['office_id'] ?? (int)($_SESSION['office_id'] ?? 0)),
        'arta_category_override' => $intakeComplexityType,
        'arta_days_limit_override' => $intakeComplexityDays,
        'remarks' => implode(' | ', $remarksParts),
    ], $actorUserId);

    $trackingId = (string)($created['tracking_id'] ?? '');
    $documentId = (int)($created['document_id'] ?? 0);
    intake_idempotency_mark_created_document($pdo, $actorUserId, $operationId, $documentId, $trackingId);

    $attachmentCount = store_document_attachments($pdo, $documentId, $actorUserId, $validatedFiles);
    $successPayload = intake_build_success_payload(
        $documentId,
        $trackingId,
        $attachmentCount,
        'Document intake created successfully.',
        $operationId
    );
    intake_idempotency_mark_completed($pdo, $actorUserId, $operationId, $successPayload, 200);
    offline_sync_log_event($pdo, [
        'event_type' => $isOutboxSyncRequest ? 'SYNC_SUCCESS' : 'ACTION_SUCCESS',
        'route_kind' => 'intake_create',
        'operation_id' => $operationId,
        'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'source' => 'server-create-document',
        'http_status' => 200,
        'message' => (string)($successPayload['message'] ?? 'Document intake created successfully.'),
        'payload' => [
            'is_outbox_sync' => $isOutboxSyncRequest,
            'role_key' => $actorRoleKey ?? app_normalize_role_key((string)($_SESSION['role_name'] ?? '')),
            'document_id' => (int)($successPayload['document_id'] ?? 0),
            'tracking_id' => (string)($successPayload['tracking_id'] ?? ''),
            'attachment_count' => (int)($successPayload['attachment_count'] ?? 0),
        ],
    ]);

    echo json_encode($successPayload);
} catch (IntakeConflictException $exception) {
    if ($pdo instanceof PDO) {
        offline_sync_log_event($pdo, [
            'event_type' => 'IDEMPOTENCY_CONFLICT',
            'route_kind' => 'intake_create',
            'operation_id' => $operationId,
            'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'source' => 'server-create-document',
            'http_status' => 409,
            'message' => $exception->getMessage(),
            'payload' => [
                'is_outbox_sync' => $isOutboxSyncRequest,
            ],
        ]);
    }
    http_response_code(409);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
} catch (InvalidArgumentException $exception) {
    if ($pdo instanceof PDO && $operationId !== '') {
        intake_idempotency_mark_failed($pdo, $actorUserId, $operationId, $exception->getMessage(), 422);
    }
    if ($pdo instanceof PDO) {
        offline_sync_log_event($pdo, [
            'event_type' => 'ACTION_VALIDATION_FAILED',
            'route_kind' => 'intake_create',
            'operation_id' => $operationId,
            'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'source' => 'server-create-document',
            'http_status' => 422,
            'message' => $exception->getMessage(),
            'payload' => [
                'is_outbox_sync' => $isOutboxSyncRequest,
            ],
        ]);
    }
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
} catch (RuntimeException $exception) {
    if ($pdo instanceof PDO && $operationId !== '') {
        intake_idempotency_mark_failed($pdo, $actorUserId, $operationId, $exception->getMessage(), 403);
    }
    if ($pdo instanceof PDO) {
        offline_sync_log_event($pdo, [
            'event_type' => 'ACTION_FORBIDDEN',
            'route_kind' => 'intake_create',
            'operation_id' => $operationId,
            'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'source' => 'server-create-document',
            'http_status' => 403,
            'message' => $exception->getMessage(),
            'payload' => [
                'is_outbox_sync' => $isOutboxSyncRequest,
            ],
        ]);
    }
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
} catch (Throwable $exception) {
    if ($pdo instanceof PDO && $operationId !== '') {
        intake_idempotency_mark_failed($pdo, $actorUserId, $operationId, 'Unable to create document right now.', 500);
    }
    if ($pdo instanceof PDO) {
        offline_sync_log_event($pdo, [
            'event_type' => 'ACTION_SERVER_ERROR',
            'route_kind' => 'intake_create',
            'operation_id' => $operationId,
            'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'source' => 'server-create-document',
            'http_status' => 500,
            'message' => 'Unable to create document right now.',
            'payload' => [
                'is_outbox_sync' => $isOutboxSyncRequest,
            ],
        ]);
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to create document right now.']);
}
