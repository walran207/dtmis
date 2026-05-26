<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/attachment-backups.php';
require_once dirname(__DIR__, 2) . '/config/document-recycle-bin.php';
require_once dirname(__DIR__, 2) . '/config/workflow.php';
require_once dirname(__DIR__, 2) . '/config/offline-sync.php';
require_once dirname(__DIR__, 2) . '/config/notification-mail.php';

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

$csrf = (string)($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$action = strtoupper(trim((string)($_POST['action'] ?? '')));
$documentId = (int)($_POST['document_id'] ?? 0);
$trackingId = strtoupper(trim((string)($_POST['tracking_id'] ?? '')));
$destinationOfficeId = (int)($_POST['destination_office_id'] ?? 0);
$destinationUserId = (int)($_POST['destination_user_id'] ?? 0);
$routeMode = strtolower(trim((string)($_POST['route_mode'] ?? '')));
$releaseMode = strtolower(trim((string)($_POST['release_mode'] ?? '')));
$bypassReason = trim((string)($_POST['bypass_reason'] ?? ''));
$remarks = trim((string)($_POST['remarks'] ?? ''));
$receiveMethod = strtoupper(trim((string)($_POST['receive_method'] ?? 'MANUAL')));
$subject = trim((string)($_POST['subject'] ?? ''));
$sourceType = strtoupper(trim((string)($_POST['source_type'] ?? '')));
$documentTypeId = (int)($_POST['document_type_id'] ?? 0);
$intakeComplexityTypeRaw = trim((string)($_POST['intake_complexity_type'] ?? ''));
$intakeComplexityDaysRaw = trim((string)($_POST['intake_complexity_days'] ?? ''));
$sender = trim((string)($_POST['sender'] ?? ''));
$externalClientName = trim((string)($_POST['external_client_name'] ?? ''));
$clientAddress = trim((string)($_POST['client_address'] ?? ''));
$originatingEntityName = trim((string)($_POST['originating_entity_name'] ?? ''));
$attachmentId = (int)($_POST['attachment_id'] ?? 0);
$operationIdRaw = (string)($_POST['operation_id'] ?? '');
$preconditionVersionRaw = trim((string)($_POST['precondition_version'] ?? ''));
$syncModeRaw = trim((string)($_POST['sync_mode'] ?? ''));
$syncHeaderRaw = trim((string)($_SERVER['HTTP_X_DTMIS_OUTBOX_SYNC'] ?? ''));
$isOutboxSyncRequest = $syncModeRaw === '1' || $syncHeaderRaw === '1';
$preconditionVersion = 0;
if ($preconditionVersionRaw !== '') {
    if (!preg_match('/^\d+$/', $preconditionVersionRaw)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Invalid precondition version.']);
        exit;
    }
    $preconditionVersion = (int)$preconditionVersionRaw;
}

const SUBJECT_CHANGE_REMARK_PREFIX = 'SUBJECT_CHANGE_JSON:';
const SENSITIVE_SYNC_REAUTH_WINDOW_SECONDS = 28800; // 8 hours
const DOCUMENT_ACTION_IDEMPOTENCY_TABLE = 'api_idempotency_operations';
const DOCUMENT_ACTION_IDEMPOTENCY_ACTION_KEY = 'document_action_workflow';
const DOCUMENT_ACTION_IDEMPOTENCY_PENDING_WINDOW_SECONDS = 45;

final class DocumentVersionConflictException extends RuntimeException
{
    private int $currentVersion;

    public function __construct(string $message, int $currentVersion)
    {
        parent::__construct($message);
        $this->currentVersion = max(1, $currentVersion);
    }

    public function currentVersion(): int
    {
        return $this->currentVersion;
    }
}

final class DocumentSyncReauthRequiredException extends RuntimeException
{
}

final class DocumentActionIdempotencyConflictException extends RuntimeException
{
}

function document_action_normalize_operation_id(string $raw): string
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

function document_action_rollout_block_message(string $action): string
{
    $actionLabel = strtoupper(trim($action));
    if ($actionLabel === '') {
        $actionLabel = 'WORKFLOW';
    }

    $policy = app_offline_policy_for_role((string)($_SESSION['role_name'] ?? ''));
    $pilotRoleKey = app_normalize_role_key((string)($policy['pilot_role_key'] ?? ''));

    if ($pilotRoleKey !== '') {
        return 'Offline sync for ' . $actionLabel
            . ' action is in pilot for role ' . $pilotRoleKey
            . ' only. Reconnect and run this action online.';
    }

    return 'Offline sync for ' . $actionLabel
        . ' action is disabled during rollout for this role. Reconnect and run this action online.';
}

function document_action_current_row_version(PDO $pdo, int $documentId): int
{
    $stmt = $pdo->prepare(
        'SELECT TOP (1) COALESCE(row_version, 1)
         FROM documents
         WHERE id = :document_id'
    );
    $stmt->execute(['document_id' => $documentId]);

    return max(1, (int)($stmt->fetchColumn() ?: 1));
}

function document_action_assert_precondition(
    PDO $pdo,
    int $documentId,
    int $preconditionVersion,
    string $action
): void {
    if ($preconditionVersion <= 0) {
        return;
    }

    $currentVersion = document_action_current_row_version($pdo, $documentId);
    if ($currentVersion === $preconditionVersion) {
        return;
    }

    $actionLabel = strtoupper(trim($action));
    throw new DocumentVersionConflictException(
        'Document changed while you were offline or in another tab. Refresh and retry ' . $actionLabel . '.',
        $currentVersion
    );
}

function document_action_touch_row_version(PDO $pdo, int $documentId): void
{
    if ($documentId <= 0) {
        return;
    }

    $stmt = $pdo->prepare(
        'UPDATE documents
         SET row_version = COALESCE(row_version, 1) + 1
         WHERE id = :document_id'
    );
    $stmt->execute(['document_id' => $documentId]);
}

function document_action_is_sensitive_sync_action(string $action): bool
{
    return in_array(strtoupper(trim($action)), [
        'FORWARD',
        'REROUTE',
        'RETURN',
        'OVERRIDE',
        'RELEASE',
        'COMPLETE',
        'SIGN',
        'UNSIGN',
    ], true);
}

function document_action_assert_sensitive_sync_reauth(string $action, bool $isOutboxSyncRequest): void
{
    if (!$isOutboxSyncRequest) {
        return;
    }
    if (!document_action_is_sensitive_sync_action($action)) {
        return;
    }

    $authenticatedAt = (int)($_SESSION['authenticated_at'] ?? 0);
    $lastSensitiveSyncReauthAt = (int)($_SESSION['last_sensitive_sync_reauth_at'] ?? 0);
    $lastVerifiedAt = max($authenticatedAt, $lastSensitiveSyncReauthAt);

    if ($lastVerifiedAt <= 0 || (time() - $lastVerifiedAt) > SENSITIVE_SYNC_REAUTH_WINDOW_SECONDS) {
        throw new DocumentSyncReauthRequiredException(
            'Re-authentication is required before syncing this sensitive action. Please sign in again and retry sync.'
        );
    }
}

function document_action_idempotency_ensure_table(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $tableName = DOCUMENT_ACTION_IDEMPOTENCY_TABLE;
    if (db_is_sql_server($pdo)) {
        $pdo->exec(
            "IF OBJECT_ID(N'dbo.{$tableName}', N'U') IS NULL
             BEGIN
                 CREATE TABLE dbo.{$tableName} (
                     id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                     user_id INT NOT NULL,
                     action_key NVARCHAR(64) NOT NULL,
                     operation_id NVARCHAR(80) NOT NULL,
                     request_hash CHAR(64) NOT NULL,
                     status NVARCHAR(16) NOT NULL CONSTRAINT DF_{$tableName}_status DEFAULT 'PENDING',
                     document_id INT NULL,
                     tracking_id NVARCHAR(64) NULL,
                     attachment_count INT NULL,
                     response_code SMALLINT NULL,
                     response_json NVARCHAR(MAX) NULL,
                     created_at DATETIME2 NOT NULL CONSTRAINT DF_{$tableName}_created_at DEFAULT SYSDATETIME(),
                     updated_at DATETIME2 NOT NULL CONSTRAINT DF_{$tableName}_updated_at DEFAULT SYSDATETIME(),
                     CONSTRAINT UQ_{$tableName}_operation UNIQUE (user_id, action_key, operation_id)
                 );
                 CREATE INDEX IDX_{$tableName}_status_updated ON dbo.{$tableName}(status, updated_at);
             END"
        );
    } else {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS {$tableName} (
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
    }

    $ensured = true;
}

function document_action_idempotency_request_hash(array $payload, array $uploadedFiles): string
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
        'action' => strtoupper(trim((string)($payload['action'] ?? ''))),
        'document_id' => (int)($payload['document_id'] ?? 0),
        'tracking_id' => strtoupper(trim((string)($payload['tracking_id'] ?? ''))),
        'destination_office_id' => (int)($payload['destination_office_id'] ?? 0),
        'destination_user_id' => (int)($payload['destination_user_id'] ?? 0),
        'route_mode' => strtolower(trim((string)($payload['route_mode'] ?? ''))),
        'bypass_reason' => trim((string)($payload['bypass_reason'] ?? '')),
        'remarks' => trim((string)($payload['remarks'] ?? '')),
        'receive_method' => strtoupper(trim((string)($payload['receive_method'] ?? 'MANUAL'))),
        'subject' => trim((string)($payload['subject'] ?? '')),
        'source_type' => strtoupper(trim((string)($payload['source_type'] ?? ''))),
        'sender' => trim((string)($payload['sender'] ?? '')),
        'document_type_id' => (int)($payload['document_type_id'] ?? 0),
        'intake_complexity_type' => trim((string)($payload['intake_complexity_type'] ?? '')),
        'intake_complexity_days' => trim((string)($payload['intake_complexity_days'] ?? '')),
        'external_client_name' => trim((string)($payload['external_client_name'] ?? '')),
        'client_address' => trim((string)($payload['client_address'] ?? '')),
        'originating_entity_name' => trim((string)($payload['originating_entity_name'] ?? '')),
        'attachment_id' => (int)($payload['attachment_id'] ?? 0),
        'precondition_version' => trim((string)($payload['precondition_version'] ?? '')),
        'files' => $fileMeta,
    ];

    return hash('sha256', (string)json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function document_action_idempotency_begin(
    PDO $pdo,
    int $userId,
    string $operationId,
    string $requestHash
): array {
    if ($operationId === '') {
        return ['mode' => 'process'];
    }

    document_action_idempotency_ensure_table($pdo);

    $tableName = DOCUMENT_ACTION_IDEMPOTENCY_TABLE;
    $actionKey = DOCUMENT_ACTION_IDEMPOTENCY_ACTION_KEY;
    $pdo->beginTransaction();
    try {
        $select = $pdo->prepare(
            "SELECT TOP (1) id, request_hash, status, response_code, response_json, updated_at
             FROM {$tableName} WITH (UPDLOCK, ROWLOCK)
             WHERE user_id = :user_id
               AND action_key = :action_key
               AND operation_id = :operation_id
             "
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
                    "INSERT INTO {$tableName} (
                        user_id, action_key, operation_id, request_hash, status, created_at, updated_at
                    ) VALUES (
                        :user_id, :action_key, :operation_id, :request_hash, 'PENDING', SYSDATETIME(), SYSDATETIME()
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
            throw new DocumentActionIdempotencyConflictException(
                'This operation_id is already used for a different document action request.'
            );
        }

        $status = strtoupper((string)($existing['status'] ?? 'PENDING'));
        $responseCode = (int)($existing['response_code'] ?? 200);
        $responseJson = trim((string)($existing['response_json'] ?? ''));

        if ($status === 'COMPLETED') {
            $decoded = $responseJson !== '' ? json_decode($responseJson, true) : null;
            if (is_array($decoded) && array_key_exists('ok', $decoded)) {
                $decoded['operation_id'] = $operationId;
                $pdo->commit();
                return [
                    'mode' => 'replay',
                    'code' => $responseCode > 0 ? $responseCode : 200,
                    'payload' => $decoded,
                ];
            }
        }

        if ($status === 'PENDING') {
            $updatedAtRaw = (string)($existing['updated_at'] ?? '');
            $updatedAtTs = strtotime($updatedAtRaw);
            if (
                $updatedAtTs !== false
                && (time() - $updatedAtTs) < DOCUMENT_ACTION_IDEMPOTENCY_PENDING_WINDOW_SECONDS
            ) {
                throw new DocumentActionIdempotencyConflictException(
                    'This document action request is currently processing. Please retry shortly.'
                );
            }
        }

        $reset = $pdo->prepare(
            "UPDATE {$tableName}
             SET status = 'PENDING',
                 response_code = NULL,
                 response_json = NULL,
                 updated_at = SYSDATETIME()
             WHERE id = :id"
        );
        $reset->execute(['id' => (int)($existing['id'] ?? 0)]);

        $pdo->commit();
        return ['mode' => 'process'];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function document_action_idempotency_mark_completed(
    PDO $pdo,
    int $userId,
    string $operationId,
    array $payload,
    int $responseCode = 200
): void {
    if ($operationId === '') {
        return;
    }

    try {
        document_action_idempotency_ensure_table($pdo);
        $tableName = DOCUMENT_ACTION_IDEMPOTENCY_TABLE;
        $encoded = (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmt = $pdo->prepare(
            "UPDATE {$tableName}
             SET status = 'COMPLETED',
                 response_code = :response_code,
                 response_json = :response_json,
                 updated_at = SYSDATETIME()
             WHERE user_id = :user_id
               AND action_key = :action_key
               AND operation_id = :operation_id"
        );
        $stmt->execute([
            'response_code' => max(100, min(599, $responseCode)),
            'response_json' => $encoded,
            'user_id' => $userId,
            'action_key' => DOCUMENT_ACTION_IDEMPOTENCY_ACTION_KEY,
            'operation_id' => $operationId,
        ]);
    } catch (Throwable $exception) {
        // Idempotency persistence should not break successful action response.
    }
}

function document_action_idempotency_mark_failed(
    ?PDO $pdo,
    int $userId,
    string $operationId,
    string $message,
    int $responseCode
): void {
    if (!$pdo instanceof PDO || $operationId === '') {
        return;
    }

    try {
        document_action_idempotency_ensure_table($pdo);
        $tableName = DOCUMENT_ACTION_IDEMPOTENCY_TABLE;
        $safeCode = max(100, min(599, $responseCode));
        $errorPayload = [
            'ok' => false,
            'operation_id' => $operationId,
            'message' => trim($message) === '' ? 'Unable to process action right now.' : trim($message),
        ];
        $stmt = $pdo->prepare(
            "UPDATE {$tableName}
             SET status = 'FAILED',
                 response_code = :response_code,
                 response_json = :response_json,
                 updated_at = SYSDATETIME()
             WHERE user_id = :user_id
               AND action_key = :action_key
               AND operation_id = :operation_id"
        );
        $stmt->execute([
            'response_code' => $safeCode,
            'response_json' => (string)json_encode($errorPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'user_id' => $userId,
            'action_key' => DOCUMENT_ACTION_IDEMPOTENCY_ACTION_KEY,
            'operation_id' => $operationId,
        ]);
    } catch (Throwable $exception) {
        // Best-effort only.
    }
}

$operationId = '';
try {
    $operationId = document_action_normalize_operation_id($operationIdRaw);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
    exit;
}

function workflow_build_subject_change_log_remarks(string $fromSubject, string $toSubject): string
{
    $payload = [
        'from' => $fromSubject,
        'to' => $toSubject,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json) || $json === '') {
        return 'Subject changed from "' . $fromSubject . '" to "' . $toSubject . '".';
    }

    return SUBJECT_CHANGE_REMARK_PREFIX . $json;
}

function workflow_apply_subject_change_if_requested(
    PDO $pdo,
    int $documentId,
    int $actorUserId,
    string $action,
    string $requestedSubject,
    string $actorRoleKey
): ?array {
    $normalizedAction = strtoupper(trim($action));
    if (!in_array($normalizedAction, ['FORWARD', 'REROUTE', 'RETURN'], true)) {
        return null;
    }

    $nextSubject = trim($requestedSubject);
    if ($nextSubject === '') {
        return null;
    }

    if (!in_array($actorRoleKey, ['DIVISION_CHIEF', 'SECTION_STAFF', 'CENRO_SECTION', 'CENRO_UNIT', 'PASU_OFFICER', 'PAMO_UNIT', 'PENRO_DIVISION', 'PENRO_SECTION', 'PENRO_SECTION_UNIT'], true)) {
        throw new RuntimeException('Only Division/Section roles can add response while routing.');
    }
    if (strlen($nextSubject) > 255) {
        throw new InvalidArgumentException('Response is too long.');
    }

    $document = workflow_get_document_context($pdo, $documentId, true);
    $currentSubject = trim((string)($document['subject'] ?? ''));
    if ($currentSubject === $nextSubject) {
        return null;
    }

    workflow_log_action($pdo, [
        'document_id' => $documentId,
        'user_id' => $actorUserId,
        'action_type' => 'Edited',
        'action_scope' => 'ACTION',
        'remarks' => workflow_build_subject_change_log_remarks($currentSubject, $nextSubject),
        'is_visible_on_slip' => 0,
    ]);

    return [
        'from' => $currentSubject,
        'to' => $nextSubject,
    ];
}

function workflow_build_prepared_response_route_context(
    string $actorRoleKey,
    string $action,
    array $destinationOffice
): array {
    $normalizedAction = strtoupper(trim($action));
    $destinationOfficeName = strtoupper(trim((string)($destinationOffice['name'] ?? '')));
    $destinationOfficeLevel = strtoupper(trim((string)($destinationOffice['level'] ?? '')));

    $isArdDestination = str_contains($destinationOfficeName, 'ARD')
        || str_contains($destinationOfficeName, 'ASSISTANT REGIONAL DIRECTOR')
        || str_contains($destinationOfficeName, 'TECHNICAL SERVICES')
        || str_contains($destinationOfficeName, 'MANAGEMENT SERVICES');

    $isDivisionChiefPreparedResponse = in_array($actorRoleKey, ['DIVISION_CHIEF'], true)
        && $normalizedAction === 'FORWARD'
        && ($isArdDestination || str_contains($destinationOfficeName, 'ORED') || str_contains($destinationOfficeName, 'REGIONAL EXECUTIVE'));
    $isSectionStaffPreparedResponse = in_array($actorRoleKey, ['SECTION_STAFF'], true)
        && $normalizedAction === 'FORWARD'
        && (
            $destinationOfficeLevel === 'DIVISION'
            || str_contains($destinationOfficeName, 'DIVISION')
            || $destinationOfficeLevel === 'CENRO_SECTION'
            || $destinationOfficeLevel === 'PENRO_DIVISION'
            || str_contains($destinationOfficeName, 'CENRO SECTION')
            || str_contains($destinationOfficeName, 'PENRO DIVISION')
        );
    $isCenroSectionPreparedResponse = in_array($actorRoleKey, ['CENRO_SECTION', 'PENRO_DIVISION'], true)
        && $normalizedAction === 'FORWARD'
        && (
            $destinationOfficeLevel === 'CENRO_OFFICER'
            || $destinationOfficeLevel === 'PENRO_OFFICER'
            || str_contains($destinationOfficeName, 'CENRO OFFICER')
            || str_contains($destinationOfficeName, 'PENRO OFFICER')
            || str_contains($destinationOfficeName, ' - OFFICER')
        );
    $isCenroUnitPreparedResponse = in_array($actorRoleKey, ['CENRO_UNIT', 'PENRO_SECTION', 'PENRO_SECTION_UNIT'], true)
        && $normalizedAction === 'FORWARD'
        && (
            $destinationOfficeLevel === 'CENRO_SECTION'
            || $destinationOfficeLevel === 'PENRO_DIVISION'
            || str_contains($destinationOfficeName, 'CENRO SECTION')
            || str_contains($destinationOfficeName, 'PENRO DIVISION')
        );
    $isPamoUnitPreparedResponse = $actorRoleKey === 'PAMO_UNIT'
        && $normalizedAction === 'FORWARD'
        && (
            $destinationOfficeLevel === 'PASU_OFFICER'
            || str_contains($destinationOfficeName, 'PAMO OFFICER')
            || str_contains($destinationOfficeName, ' - OFFICER')
        );

    $isPreparedResponseRoute = $isDivisionChiefPreparedResponse
        || $isSectionStaffPreparedResponse
        || $isCenroSectionPreparedResponse
        || $isCenroUnitPreparedResponse
        || $isPamoUnitPreparedResponse;

    return [
        'allowed' => $isPreparedResponseRoute,
        'is_ard_destination' => $isArdDestination,
        'is_division_chief_route' => $isDivisionChiefPreparedResponse,
        'is_section_staff_route' => $isSectionStaffPreparedResponse,
        'is_cenro_section_route' => $isCenroSectionPreparedResponse,
        'is_cenro_unit_route' => $isCenroUnitPreparedResponse,
        'target_label' => $isDivisionChiefPreparedResponse
            ? ($isArdDestination ? 'ARD' : 'ORED')
            : (
                $isSectionStaffPreparedResponse
                    ? 'Division Chief'
                    : (
                        $isCenroSectionPreparedResponse
                            ? (in_array($actorRoleKey, ['PENRO_DIVISION'], true) ? 'PENRO Officer' : 'CENRO Officer')
                            : (
                                $isCenroUnitPreparedResponse
                                    ? (in_array($actorRoleKey, ['PENRO_SECTION', 'PENRO_SECTION_UNIT'], true) ? 'PENRO Division' : 'CENRO Section')
                                    : ($isPamoUnitPreparedResponse ? 'PASU' : '')
                            )
                    )
            ),
    ];
}

if ($releaseMode !== '' && !in_array($releaseMode, ['complete_local', 'send_to_office'], true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid release mode.']);
    exit;
}

function workflow_build_endorsement_route_context(
    string $actorRoleKey,
    string $action,
    array $destinationOffice,
    string $releaseMode = ''
): array {
    $normalizedAction = strtoupper(trim($action));
    $normalizedReleaseMode = strtolower(trim($releaseMode));
    if (!in_array($normalizedAction, ['FORWARD', 'RELEASE'], true)) {
        return [
            'allowed' => false,
            'required' => false,
            'target_label' => '',
        ];
    }

    $destinationOfficeName = strtoupper(trim((string)($destinationOffice['name'] ?? '')));
    $destinationOfficeLevel = strtoupper(trim((string)($destinationOffice['level'] ?? '')));

    $isRecordsUnitDestination = workflow_name_matches_records_unit($destinationOfficeName);
    $isRegionalDestination = $isRecordsUnitDestination || $destinationOfficeLevel === 'REGIONAL' || str_contains($destinationOfficeName, 'REGIONAL');
    $isPenroDestination = $destinationOfficeLevel === 'PENRO_ADMIN_RECORD' || str_contains($destinationOfficeName, 'PENRO ADMIN');
    $targetLabel = trim((string)($destinationOffice['name'] ?? ''));
    if ($targetLabel === '') {
        $targetLabel = $isRecordsUnitDestination ? 'PACDO/RECORDS-UNIT' : 'destination office';
    }

    $isAdminForwardToRecordsUnit = in_array($actorRoleKey, ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD'], true) && $normalizedAction === 'FORWARD' && $isRecordsUnitDestination;
    $isCenroForwardToPenro = $actorRoleKey === 'CENRO_ADMIN_RECORD'
        && $normalizedAction === 'FORWARD'
        && $isPenroDestination;
    $isCenroReleaseToPenroOrRegional = $actorRoleKey === 'CENRO_ADMIN_RECORD'
        && $normalizedAction === 'RELEASE'
        && $normalizedReleaseMode === 'send_to_office'
        && ($isPenroDestination || $isRegionalDestination);

    if (!$isAdminForwardToRecordsUnit && !$isCenroForwardToPenro && !$isCenroReleaseToPenroOrRegional) {
        return [
            'allowed' => false,
            'required' => false,
            'target_label' => '',
        ];
    }

    return [
        'allowed' => true,
        'required' => false,
        'target_label' => $isRecordsUnitDestination ? 'PACDO/RECORDS-UNIT' : $targetLabel,
    ];
}

function workflow_document_has_prepared_response_attachment(PDO $pdo, int $documentId, int $officeId): bool
{
    if ($documentId <= 0 || $officeId <= 0) {
        return false;
    }
    if (!workflow_table_exists($pdo, 'document_attachments')) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT
            da.file_name,
            da.uploaded_by,
            d.created_by_user_id,
            u_u.office_id AS uploaded_by_office_id,
            o_u.parent_office_id AS uploaded_by_parent_office_id,
            r_u.name AS uploaded_by_role
         FROM document_attachments da
         INNER JOIN documents d ON d.id = da.document_id
         LEFT JOIN users u_u ON u_u.id = da.uploaded_by
         LEFT JOIN offices o_u ON o_u.id = u_u.office_id
         LEFT JOIN roles r_u ON r_u.id = u_u.role_id
         WHERE da.document_id = :document_id
          ORDER BY da.id DESC'
    );
    $stmt->execute([
        'document_id' => $documentId,
    ]);

    while ($row = $stmt->fetch()) {
        $uploadedByOfficeId = (int)($row['uploaded_by_office_id'] ?? 0);
        $uploadedByParentOfficeId = (int)($row['uploaded_by_parent_office_id'] ?? 0);
        $matchesOfficeChain = ($uploadedByOfficeId > 0 && $uploadedByOfficeId === $officeId)
            || ($uploadedByParentOfficeId > 0 && $uploadedByParentOfficeId === $officeId);
        if (!$matchesOfficeChain) {
            continue;
        }
        $category = document_action_classify_route_attachment(
            (string)($row['file_name'] ?? ''),
            (string)($row['uploaded_by_role'] ?? ''),
            (int)($row['uploaded_by'] ?? 0),
            (int)($row['created_by_user_id'] ?? 0)
        );
        if ($category === 'prepared_response') {
            return true;
        }
    }

    return false;
}

function intake_status_is_terminal(string $status): bool
{
    $normalized = strtolower(trim($status));
    if ($normalized === '') {
        return false;
    }

    $terminalKeywords = ['completed', 'released', 'closed', 'resolved', 'done', 'signed', 'cancelled', 'canceled'];
    foreach ($terminalKeywords as $keyword) {
        if (str_contains($normalized, $keyword)) {
            return true;
        }
    }

    return false;
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

function intake_status_is_forwarded(string $status): bool
{
    $normalized = strtolower(trim($status));
    if ($normalized === '') {
        return false;
    }

    return str_contains($normalized, 'forward')
        || str_contains($normalized, 'route')
        || str_starts_with($normalized, 'assigned to ');
}

function intake_status_is_correction_stage(string $status): bool
{
    $normalized = strtolower(trim($status));
    if ($normalized === '') {
        return false;
    }

    return str_contains($normalized, 'received')
        || str_contains($normalized, 'recieved')
        || str_contains($normalized, 'return')
        || str_contains($normalized, 'approved');
}

function intake_document_has_return_action(PDO $pdo, int $documentId): bool
{
    if ($documentId <= 0 || !workflow_table_exists($pdo, 'activity_logs')) {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM activity_logs
         WHERE document_id = :document_id
           AND (
                LOWER(COALESCE(action_type, '')) LIKE 'return%'
                OR LOWER(COALESCE(action_type, '')) = 'returned'
           )"
    );
    $stmt->execute(['document_id' => $documentId]);
    return (int)($stmt->fetchColumn() ?: 0) > 0;
}

function intake_assert_manage_permission(PDO $pdo, int $documentId, int $actorUserId): array
{
    $document = workflow_get_document_context($pdo, $documentId, true);
    $actor = workflow_get_user_context($pdo, $actorUserId);

    $status = (string)($document['status'] ?? '');
    if (intake_status_is_terminal($status)) {
        throw new RuntimeException('This document can no longer be edited or deleted.');
    }

    $createdByUserId = (int)($document['created_by_user_id'] ?? 0);
    $actorOfficeId = (int)($actor['office_id'] ?? 0);
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    $pendingOfficeId = (int)($document['pending_office_id'] ?? 0);
    $currentOfficeId = (int)($document['current_office_id'] ?? 0);

    $isCreatorDraft = $createdByUserId > 0
        && $createdByUserId === $actorUserId
        && strtolower(trim($status)) === 'created';

    $rowIsInActorCustody = true;
    if ($pendingOfficeId > 0) {
        $rowIsInActorCustody = $actorOfficeId > 0 && $pendingOfficeId === $actorOfficeId;
    } elseif ($currentOfficeId > 0) {
        $rowIsInActorCustody = $actorOfficeId > 0 && $currentOfficeId === $actorOfficeId;
    }

    $hasReturnAction = intake_document_has_return_action($pdo, $documentId);
    $isCorrectionCycle = in_array($actorRoleKey, ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD', 'RECORDS_UNIT'], true)
        && $hasReturnAction
        && $rowIsInActorCustody
        && intake_status_is_correction_stage($status)
        && !intake_status_is_forwarded($status);

    if (!$isCreatorDraft && !$isCorrectionCycle) {
        throw new RuntimeException(
            'Only the intake creator (draft) or the receiving CENRO Admin Record/PENRO Admin Record/RECORDS-UNIT correction office can edit or delete this document.'
        );
    }

    if ($isCreatorDraft) {
        if ($pendingOfficeId > 0 || intake_status_is_forwarded($status)) {
            throw new RuntimeException('This document was already forwarded and can no longer be edited or deleted.');
        }

        if (workflow_table_exists($pdo, 'tracking_slips')) {
            $trackingSlipCountStmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM tracking_slips
                 WHERE document_id = :document_id'
            );
            $trackingSlipCountStmt->execute(['document_id' => $documentId]);
            $trackingSlipCount = (int)($trackingSlipCountStmt->fetchColumn() ?: 0);
            if ($trackingSlipCount > 0) {
                throw new RuntimeException('This document was already received and can no longer be edited or deleted.');
            }
        }

        if (workflow_table_exists($pdo, 'activity_logs')) {
            $progressedActionCountStmt = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM activity_logs
                 WHERE document_id = :document_id
                   AND LOWER(COALESCE(action_type, '')) NOT IN ('created', 'edited')"
            );
            $progressedActionCountStmt->execute(['document_id' => $documentId]);
            $progressedActionCount = (int)($progressedActionCountStmt->fetchColumn() ?: 0);
            if ($progressedActionCount > 0) {
                throw new RuntimeException('This document already progressed and can no longer be edited or deleted.');
            }
        }
    }

    return $document;
}

function document_action_classify_route_attachment(
    ?string $fileName,
    ?string $uploadedByRole,
    int $uploadedByUserId = 0,
    int $documentCreatorUserId = 0
): string
{
    $roleKey = app_normalize_role_key((string)$uploadedByRole);
    if (in_array($roleKey, [
        'DIVISION_CHIEF',
        'SECTION_STAFF',
        'CENRO_SECTION',
        'CENRO_UNIT',
        'PASU_OFFICER',
        'PAMO_UNIT',
        'PENRO_DIVISION',
        'PENRO_SECTION',
        'PENRO_SECTION_UNIT',
    ], true)) {
        return 'prepared_response';
    }

    $fileNameKey = strtolower(trim((string)$fileName));
    if ($fileNameKey !== '') {
        $preparedResponseMarkers = [
            'prepared response',
            'prepared_response',
            'prepared-response',
            'prepared_of_response',
        ];
        foreach ($preparedResponseMarkers as $marker) {
            if (str_contains($fileNameKey, $marker)) {
                return 'prepared_response';
            }
        }

        if (str_contains($fileNameKey, 'endorsement') || str_contains($fileNameKey, 'endorse')) {
            return 'endorsement';
        }
    }

    $isCreatorUpload = $uploadedByUserId > 0
        && $documentCreatorUserId > 0
        && $uploadedByUserId === $documentCreatorUserId;
    if (!$isCreatorUpload && in_array($roleKey, ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD'], true)) {
        return 'endorsement';
    }

    return 'original';
}

function document_action_assert_route_attachment_manage_permission(
    PDO $pdo,
    int $documentId,
    int $attachmentId,
    int $actorUserId
): array {
    $document = workflow_get_document_context($pdo, $documentId, true);
    $actor = workflow_get_user_context($pdo, $actorUserId);

    if (intake_status_is_terminal((string)($document['status'] ?? ''))) {
        throw new RuntimeException('This document can no longer be edited.');
    }

    $actorOfficeId = (int)($actor['office_id'] ?? 0);
    $currentOfficeId = (int)($document['current_office_id'] ?? 0);
    if ($actorOfficeId <= 0 || $currentOfficeId <= 0 || $actorOfficeId !== $currentOfficeId) {
        throw new RuntimeException('Only the current office can manage route attachments.');
    }

    if (!workflow_table_exists($pdo, 'document_attachments')) {
        throw new RuntimeException('Attachment storage table is not available.');
    }

    $attachmentSelectStmt = $pdo->prepare(
        'SELECT TOP (1)
            da.id,
            da.document_id,
            da.file_name,
            da.file_path,
            da.version_number,
            da.uploaded_by,
            d.created_by_user_id,
            u.office_id AS uploaded_by_office_id,
            r.name AS uploaded_by_role
         FROM document_attachments da WITH (UPDLOCK, ROWLOCK)
         INNER JOIN documents d ON d.id = da.document_id
         LEFT JOIN users u ON u.id = da.uploaded_by
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE da.id = :attachment_id
           AND da.document_id = :document_id'
    );
    $attachmentSelectStmt->execute([
        'attachment_id' => $attachmentId,
        'document_id' => $documentId,
    ]);
    $attachment = $attachmentSelectStmt->fetch();
    if (!$attachment) {
        throw new RuntimeException('Attachment not found.');
    }

    $uploadedByOfficeId = (int)($attachment['uploaded_by_office_id'] ?? 0);
    if ($uploadedByOfficeId <= 0 || $uploadedByOfficeId !== $actorOfficeId) {
        throw new RuntimeException('You can only manage route attachments uploaded by your current office.');
    }

    $attachmentCategory = document_action_classify_route_attachment(
        (string)($attachment['file_name'] ?? ''),
        (string)($attachment['uploaded_by_role'] ?? ''),
        (int)($attachment['uploaded_by'] ?? 0),
        (int)($attachment['created_by_user_id'] ?? 0)
    );
    if (!in_array($attachmentCategory, ['prepared_response', 'endorsement'], true)) {
        throw new RuntimeException('Only prepared response or endorsement attachments can be managed here.');
    }

    $attachment['route_attachment_category'] = $attachmentCategory;
    return [
        'document' => $document,
        'actor' => $actor,
        'attachment' => $attachment,
    ];
}

function workflow_document_has_endorsement_attachment(PDO $pdo, int $documentId, int $officeId): bool
{
    if ($documentId <= 0 || $officeId <= 0) {
        return false;
    }
    if (!workflow_table_exists($pdo, 'document_attachments')) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT
            da.file_name,
            da.uploaded_by,
            d.created_by_user_id,
            r.name AS uploaded_by_role
         FROM document_attachments da
         INNER JOIN documents d ON d.id = da.document_id
         LEFT JOIN users u ON u.id = da.uploaded_by
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE da.document_id = :document_id
           AND u.office_id = :office_id
         ORDER BY da.id DESC'
    );
    $stmt->execute([
        'document_id' => $documentId,
        'office_id' => $officeId,
    ]);

    while ($row = $stmt->fetch()) {
        $category = document_action_classify_route_attachment(
            (string)($row['file_name'] ?? ''),
            (string)($row['uploaded_by_role'] ?? ''),
            (int)($row['uploaded_by'] ?? 0),
            (int)($row['created_by_user_id'] ?? 0)
        );
        if ($category === 'endorsement') {
            return true;
        }
    }

    return false;
}

function document_action_replace_route_attachment_file(
    PDO $pdo,
    int $documentId,
    int $attachmentId,
    int $actorUserId,
    array $validatedFiles
): array {
    if (count($validatedFiles) !== 1) {
        throw new InvalidArgumentException('Upload exactly one replacement attachment.');
    }

    $permission = document_action_assert_route_attachment_manage_permission($pdo, $documentId, $attachmentId, $actorUserId);
    $attachment = $permission['attachment'];
    $existingVersion = max(1, (int)($attachment['version_number'] ?? 1));
    $existingPath = trim((string)($attachment['file_path'] ?? ''));

    [$relativeDir, $absoluteDir] = intake_build_attachment_storage_paths();
    $file = $validatedFiles[0];
    $safeBaseName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$file['original_name']) ?? ('attachment.' . $file['extension']);
    if ($safeBaseName === '') {
        $safeBaseName = 'attachment.' . $file['extension'];
    }

    $storedFileName = 'doc_' . $documentId . '_v' . $existingVersion . '_' . $safeBaseName;
    $relativePath = $relativeDir . '/' . $storedFileName;
    $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $storedFileName;

    if (!move_uploaded_file((string)$file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Failed to store replacement attachment file: ' . (string)$file['original_name']);
    }

    $updateStmt = $pdo->prepare(
        'UPDATE document_attachments
         SET file_name = :file_name,
             file_path = :file_path,
             uploaded_by = :uploaded_by,
             uploaded_at = SYSDATETIME()
         WHERE id = :attachment_id
           AND document_id = :document_id'
    );
    $updateStmt->execute([
        'file_name' => (string)$file['original_name'],
        'file_path' => $relativePath,
        'uploaded_by' => $actorUserId,
        'attachment_id' => $attachmentId,
        'document_id' => $documentId,
    ]);
    if ($updateStmt->rowCount() < 1) {
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
        throw new RuntimeException('Unable to replace attachment.');
    }

    attachment_binary_backup_upsert_from_file(
        $pdo,
        $attachmentId,
        $documentId,
        (string)$file['original_name'],
        $absolutePath
    );

    return [
        'old_file_path' => $existingPath,
        'new_file_name' => (string)$file['original_name'],
        'attachment_category' => (string)($attachment['route_attachment_category'] ?? 'attachment'),
    ];
}

function intake_normalize_uploaded_files(?array $fileBag): array
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

function intake_validate_uploaded_files(array $files): array
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
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($files as $file) {
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                throw new InvalidArgumentException('One or more attachments exceeded server upload limits.');
            }
            throw new InvalidArgumentException('One of the uploaded files failed to upload. Please try again.');
        }

        $originalName = trim((string)($file['name'] ?? ''));
        if ($originalName === '') {
            throw new InvalidArgumentException('Uploaded file has no filename.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            throw new InvalidArgumentException('Uploaded file is empty.');
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

function intake_store_document_attachments(PDO $pdo, int $documentId, int $uploadedByUserId, array $validatedFiles): int
{
    if ($documentId <= 0 || empty($validatedFiles)) {
        return 0;
    }

    if (!db_table_exists($pdo, 'document_attachments')) {
        throw new RuntimeException('Attachment storage table is not available.');
    }

    $versionStmt = $pdo->prepare('SELECT COALESCE(MAX(version_number), 0) FROM document_attachments WHERE document_id = :document_id');
    $versionStmt->execute(['document_id' => $documentId]);
    $currentVersion = (int)($versionStmt->fetchColumn() ?: 0);

    [$relativeDir, $absoluteDir] = app_attachment_storage_directory();

    $insert = $pdo->prepare(
        'INSERT INTO document_attachments (
            document_id, uploaded_by, file_name, file_path, version_number, is_internal_only, uploaded_at
         ) VALUES (
            :document_id, :uploaded_by, :file_name, :file_path, :version_number, :is_internal_only, SYSDATETIME()
         )'
    );

    $saved = 0;
    foreach ($validatedFiles as $index => $file) {
        $safeBaseName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$file['original_name']) ?? ('attachment.' . $file['extension']);
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
        $attachmentId = attachment_binary_backup_resolve_attachment_id($pdo, $documentId, $versionNumber, $relativePath);
        if ($attachmentId > 0) {
            attachment_binary_backup_upsert_from_file(
                $pdo,
                $attachmentId,
                $documentId,
                (string)$file['original_name'],
                $absolutePath
            );
        }
        $saved++;
    }

    return $saved;
}

function intake_resolve_attachment_absolute_path(string $filePath): ?string
{
    return app_resolve_attachment_absolute_path($filePath);
}

function intake_build_attachment_storage_paths(): array
{
    return app_attachment_storage_directory();
}

function intake_store_generated_attachment(
    PDO $pdo,
    int $documentId,
    int $uploadedByUserId,
    string $displayName,
    string $relativePath,
    ?string $absolutePath = null
): int {
    $versionStmt = $pdo->prepare('SELECT COALESCE(MAX(version_number), 0) FROM document_attachments WHERE document_id = :document_id');
    $versionStmt->execute(['document_id' => $documentId]);
    $nextVersion = ((int)($versionStmt->fetchColumn() ?: 0)) + 1;

    $insert = $pdo->prepare(
        'INSERT INTO document_attachments (
            document_id, uploaded_by, file_name, file_path, version_number, is_internal_only, uploaded_at
         ) VALUES (
            :document_id, :uploaded_by, :file_name, :file_path, :version_number, :is_internal_only, SYSDATETIME()
         )'
    );
    $insert->execute([
        'document_id' => $documentId,
        'uploaded_by' => $uploadedByUserId,
        'file_name' => $displayName,
        'file_path' => $relativePath,
        'version_number' => $nextVersion,
        'is_internal_only' => 0,
    ]);

    $attachmentId = attachment_binary_backup_resolve_attachment_id($pdo, $documentId, $nextVersion, $relativePath);
    if ($attachmentId > 0 && $absolutePath !== null && trim($absolutePath) !== '') {
        attachment_binary_backup_upsert_from_file(
            $pdo,
            $attachmentId,
            $documentId,
            $displayName,
            $absolutePath
        );
    }

    return $attachmentId;
}

function intake_delete_attachment_file_if_present(string $filePath): void
{
    $absolutePath = intake_resolve_attachment_absolute_path($filePath);
    if ($absolutePath === null || !is_file($absolutePath)) {
        return;
    }

    @unlink($absolutePath);
}

function digital_signature_sign_image_file(
    string $sourceAbsolutePath,
    string $outputAbsolutePath,
    string $signerName,
    string $dateLine,
    string $timeLine,
    ?string $signatureImageAbsolutePath = null,
    float $signatureXPercent = 58.0,
    float $signatureYPercent = 74.0,
    float $blockWidthPercent = 42.0,
    float $blockHeightPercent = 14.25
): void {
    if (!function_exists('exec')) {
        throw new RuntimeException('Digital signature renderer is unavailable on this server.');
    }

    $script = <<<'POWERSHELL'
param(
    [Parameter(Mandatory = $true)][string]$InputPath,
    [Parameter(Mandatory = $true)][string]$OutputPath,
    [Parameter(Mandatory = $true)][string]$SignerName,
    [Parameter(Mandatory = $true)][string]$DateLine,
    [Parameter(Mandatory = $true)][string]$TimeLine,
    [string]$SignatureImagePath = '',
    [double]$SignatureXPercent = 58.0,
    [double]$SignatureYPercent = 74.0,
    [double]$BlockWidthPercent = 42.0,
    [double]$BlockHeightPercent = 14.25
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$sourceImage = [System.Drawing.Image]::FromFile($InputPath)
$canvas = New-Object System.Drawing.Bitmap($sourceImage.Width, $sourceImage.Height, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
$graphics = [System.Drawing.Graphics]::FromImage($canvas)

try {
    $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBilinear
    $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
    $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $graphics.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::ClearTypeGridFit
    $graphics.DrawImage($sourceImage, 0, 0, $sourceImage.Width, $sourceImage.Height)

    $padding = [Math]::Max([int]($sourceImage.Width * 0.02), 20)
    $SignatureXPercent = [Math]::Min(100.0, [Math]::Max(0.0, $SignatureXPercent))
    $SignatureYPercent = [Math]::Min(100.0, [Math]::Max(0.0, $SignatureYPercent))
    $BlockWidthPercent = [Math]::Min(90.0, [Math]::Max(20.0, $BlockWidthPercent))
    $BlockHeightPercent = [Math]::Min(80.0, [Math]::Max(12.0, $BlockHeightPercent))

    $blockWidth = [Math]::Max([int]($sourceImage.Width * ($BlockWidthPercent / 100.0)), 240)
    $blockHeight = [Math]::Max([int]($sourceImage.Height * ($BlockHeightPercent / 100.0)), 120)

    if ($blockWidth -gt ($sourceImage.Width - ($padding * 2))) {
        $blockWidth = $sourceImage.Width - ($padding * 2)
    }
    if ($blockHeight -gt ($sourceImage.Height - ($padding * 2))) {
        $blockHeight = $sourceImage.Height - ($padding * 2)
    }

    $availableX = [Math]::Max($sourceImage.Width - $blockWidth - ($padding * 2), 0)
    $availableY = [Math]::Max($sourceImage.Height - $blockHeight - ($padding * 2), 0)
    $blockX = $padding + [int]([Math]::Round($availableX * ($SignatureXPercent / 100.0)))
    $blockY = $padding + [int]([Math]::Round($availableY * ($SignatureYPercent / 100.0)))

    # Keep final stamp borderless: no outer background rectangle.

    $innerPadding = [Math]::Max([int]($blockHeight * 0.07), 8)
    $columnGap = [Math]::Max([int]($blockWidth * 0.025), 8)
    $availableTextMinWidth = 120
    $signatureAreaWidth = [Math]::Max([int]($blockWidth * 0.38), 92)
    $signatureAreaWidth = [Math]::Min(
        $signatureAreaWidth,
        [Math]::Max(80, $blockWidth - ($innerPadding * 2) - $columnGap - $availableTextMinWidth)
    )
    $signatureAreaHeight = [Math]::Max(64, $blockHeight - ($innerPadding * 2))
    $signatureX = $blockX + $innerPadding
    $signatureY = $blockY + $innerPadding

    $signatureWidth = [Math]::Max([int]($signatureAreaWidth * 0.90), 72)
    $signatureHeight = [Math]::Max([int]($signatureAreaHeight * 0.88), 56)

    $signatureDrawn = $false
    if ($SignatureImagePath -ne '' -and (Test-Path -LiteralPath $SignatureImagePath)) {
        $signatureImage = [System.Drawing.Image]::FromFile($SignatureImagePath)
        try {
            $ratioWidth = $signatureWidth / $signatureImage.Width
            $ratioHeight = $signatureHeight / $signatureImage.Height
            $ratio = [Math]::Min($ratioWidth, $ratioHeight)
            $drawWidth = [Math]::Max([int]($signatureImage.Width * $ratio), 1)
            $drawHeight = [Math]::Max([int]($signatureImage.Height * $ratio), 1)
            $drawX = $signatureX + [int](($signatureAreaWidth - $drawWidth) / 2)
            $drawY = $signatureY + [int](($signatureAreaHeight - $drawHeight) / 2)
            $graphics.DrawImage($signatureImage, $drawX, $drawY, $drawWidth, $drawHeight)
            $signatureDrawn = $true
        } finally {
            $signatureImage.Dispose()
        }
    }

    if (-not $signatureDrawn) {
        $fallbackSignatureSize = [Math]::Min([Math]::Max([int]($signatureAreaHeight * 0.28), 12), 28)
        $fallbackSignatureFont = New-Object System.Drawing.Font('Segoe Script', [float]$fallbackSignatureSize, [System.Drawing.FontStyle]::Regular)
        $fallbackSignatureBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(230, 39, 70, 156))
        $graphics.DrawString($SignerName, $fallbackSignatureFont, $fallbackSignatureBrush, [float]($signatureX + 4), [float]($signatureY + [Math]::Max([int]($signatureAreaHeight * 0.22), 8)))
        $fallbackSignatureBrush.Dispose()
        $fallbackSignatureFont.Dispose()
    }

    $textX = $signatureX + $signatureAreaWidth + $columnGap
    $textTop = $signatureY
    $textMaxWidth = [Math]::Max(90, ($blockX + $blockWidth - $innerPadding) - $textX)
    $titleFontSize = [Math]::Min([Math]::Max([int]($blockHeight * 0.085), 8), 14)
    $nameFontSize = [Math]::Min([Math]::Max([int]($blockHeight * 0.115), 10), 18)
    $metaFontSize = [Math]::Min([Math]::Max([int]($blockHeight * 0.09), 8), 14)
    $lineGap = [Math]::Max([int]($blockHeight * 0.02), 2)

    $titleFont = New-Object System.Drawing.Font('Segoe UI', [float]$titleFontSize, [System.Drawing.FontStyle]::Regular)
    $nameFont = New-Object System.Drawing.Font('Segoe UI', [float]$nameFontSize, [System.Drawing.FontStyle]::Bold)
    $metaFont = New-Object System.Drawing.Font('Segoe UI', [float]$metaFontSize, [System.Drawing.FontStyle]::Regular)
    $textBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 18, 25, 35))

    while (
        ($graphics.MeasureString('Digitally signed by', $titleFont).Width -gt $textMaxWidth -or
         $graphics.MeasureString($SignerName, $nameFont).Width -gt $textMaxWidth -or
         $graphics.MeasureString($DateLine, $metaFont).Width -gt $textMaxWidth -or
         $graphics.MeasureString($TimeLine, $metaFont).Width -gt $textMaxWidth -or
         (
            (
                $titleFont.GetHeight($graphics)
                + $lineGap
                + $nameFont.GetHeight($graphics)
                + $lineGap
                + $metaFont.GetHeight($graphics)
                + [Math]::Max($lineGap - 1, 1)
                + $metaFont.GetHeight($graphics)
            ) -gt $signatureAreaHeight
         )) -and
        ($titleFontSize -gt 7 -or $nameFontSize -gt 9 -or $metaFontSize -gt 7)
    ) {
        if ($titleFontSize -gt 7) { $titleFontSize -= 1 }
        if ($nameFontSize -gt 9) { $nameFontSize -= 1 }
        if ($metaFontSize -gt 7) { $metaFontSize -= 1 }
        $titleFont.Dispose()
        $nameFont.Dispose()
        $metaFont.Dispose()
        $titleFont = New-Object System.Drawing.Font('Segoe UI', [float]$titleFontSize, [System.Drawing.FontStyle]::Regular)
        $nameFont = New-Object System.Drawing.Font('Segoe UI', [float]$nameFontSize, [System.Drawing.FontStyle]::Bold)
        $metaFont = New-Object System.Drawing.Font('Segoe UI', [float]$metaFontSize, [System.Drawing.FontStyle]::Regular)
    }

    $totalTextHeight = $titleFont.GetHeight($graphics) + $lineGap + $nameFont.GetHeight($graphics) + $lineGap + $metaFont.GetHeight($graphics) + [Math]::Max($lineGap - 1, 1) + $metaFont.GetHeight($graphics)
    $textY = [float]($textTop + [Math]::Max([int](($signatureAreaHeight - $totalTextHeight) / 2), 0))
    $graphics.DrawString('Digitally signed by', $titleFont, $textBrush, [float]$textX, $textY)
    $textY += [float]($titleFont.GetHeight($graphics) + $lineGap)

    $graphics.DrawString($SignerName, $nameFont, $textBrush, [float]$textX, $textY)
    $textY += [float]($nameFont.GetHeight($graphics) + $lineGap)

    $graphics.DrawString($DateLine, $metaFont, $textBrush, [float]$textX, $textY)
    $textY += [float]($metaFont.GetHeight($graphics) + [Math]::Max($lineGap - 1, 1))

    $graphics.DrawString($TimeLine, $metaFont, $textBrush, [float]$textX, $textY)

    $textBrush.Dispose()
    $metaFont.Dispose()
    $nameFont.Dispose()
    $titleFont.Dispose()

    $outputDir = [System.IO.Path]::GetDirectoryName($OutputPath)
    if (-not [string]::IsNullOrWhiteSpace($outputDir) -and -not (Test-Path -LiteralPath $outputDir)) {
        New-Item -Path $outputDir -ItemType Directory -Force | Out-Null
    }

    $canvas.Save($OutputPath, [System.Drawing.Imaging.ImageFormat]::Png)
} finally {
    $graphics.Dispose()
    $canvas.Dispose()
    $sourceImage.Dispose()
}
POWERSHELL;

    $scriptPath = tempnam(sys_get_temp_dir(), 'DTMIS_signature_');
    if ($scriptPath === false) {
        throw new RuntimeException('Unable to allocate temporary signature script path.');
    }
    $scriptPath .= '.ps1';
    if (file_put_contents($scriptPath, $script) === false) {
        throw new RuntimeException('Unable to write temporary signature script.');
    }

    $command = 'powershell -NoProfile -ExecutionPolicy Bypass -File '
        . escapeshellarg($scriptPath)
        . ' -InputPath ' . escapeshellarg($sourceAbsolutePath)
        . ' -OutputPath ' . escapeshellarg($outputAbsolutePath)
        . ' -SignerName ' . escapeshellarg($signerName)
        . ' -DateLine ' . escapeshellarg($dateLine)
        . ' -TimeLine ' . escapeshellarg($timeLine)
        . ' -SignatureImagePath ' . escapeshellarg((string)($signatureImageAbsolutePath ?? ''))
        . ' -SignatureXPercent ' . escapeshellarg((string)$signatureXPercent)
        . ' -SignatureYPercent ' . escapeshellarg((string)$signatureYPercent)
        . ' -BlockWidthPercent ' . escapeshellarg((string)$blockWidthPercent)
        . ' -BlockHeightPercent ' . escapeshellarg((string)$blockHeightPercent);

    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    @unlink($scriptPath);

    if ($exitCode !== 0 || !is_file($outputAbsolutePath)) {
        $message = trim(implode(' ', $output));
        if ($message === '') {
            $message = 'Unknown rendering failure.';
        }
        throw new RuntimeException('Unable to apply digital signature overlay. ' . $message);
    }
}

function digital_signature_resolve_signer_name(PDO $pdo, int $actorUserId): string
{
    $stmt = $pdo->prepare(
        'SELECT TOP (1) first_name, last_name
         FROM users
         WHERE id = :id'
    );
    $stmt->execute(['id' => $actorUserId]);
    $user = $stmt->fetch();
    if (!$user) {
        return 'Regional Director';
    }

    $firstName = trim((string)($user['first_name'] ?? ''));
    $lastName = trim((string)($user['last_name'] ?? ''));
    $fullName = trim($firstName . ' ' . $lastName);

    return $fullName !== '' ? $fullName : 'Regional Director';
}

function digital_signature_profile_defaults(): array
{
    return [
        'mode' => 'Grid-Snap',
        'paper_size' => 'A4',
        'x_pct' => 58.0,
        'y_pct' => 74.0,
        'block_width_pct' => 42.0,
        'block_height_pct' => 14.25,
    ];
}

function digital_signature_normalize_profile(array $raw): array
{
    $defaults = digital_signature_profile_defaults();
    $modeRaw = strtoupper(str_replace(['_', ' '], '-', (string)($raw['mode'] ?? $defaults['mode'])));
    $paperRaw = strtoupper(trim((string)($raw['paper_size'] ?? $defaults['paper_size'])));
    $paperSize = in_array($paperRaw, ['A4', 'LETTER', 'CUSTOM_85X13'], true) ? $paperRaw : 'A4';

    return [
        'mode' => $modeRaw === 'FREE-DRAG' ? 'Free-Drag' : 'Grid-Snap',
        'paper_size' => $paperSize,
        'x_pct' => max(0.0, min(100.0, round((float)($raw['x_pct'] ?? $defaults['x_pct']), 2))),
        'y_pct' => max(0.0, min(100.0, round((float)($raw['y_pct'] ?? $defaults['y_pct']), 2))),
        'block_width_pct' => max(20.0, min(90.0, round((float)($raw['block_width_pct'] ?? $defaults['block_width_pct']), 2))),
        'block_height_pct' => max(12.0, min(80.0, round((float)($raw['block_height_pct'] ?? $defaults['block_height_pct']), 2))),
    ];
}

function digital_signature_load_profile(int $actorUserId): array
{
    if ($actorUserId <= 0) {
        return digital_signature_profile_defaults();
    }

    $profilePath = dirname(__DIR__)
        . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'signatures'
        . DIRECTORY_SEPARATOR . 'profiles'
        . DIRECTORY_SEPARATOR . 'user_' . $actorUserId . '.json';

    if (!is_file($profilePath) || !is_readable($profilePath)) {
        return digital_signature_profile_defaults();
    }

    $json = file_get_contents($profilePath);
    if ($json === false || trim($json) === '') {
        return digital_signature_profile_defaults();
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return digital_signature_profile_defaults();
    }

    return digital_signature_normalize_profile($decoded);
}

function digital_signature_resolve_signature_image_path(int $actorUserId): ?string
{
    $userPattern = dirname(__DIR__)
        . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'signatures'
        . DIRECTORY_SEPARATOR . 'user_' . $actorUserId . '.*';
    $userCandidates = glob($userPattern) ?: [];
    $userCandidates = array_values(array_filter($userCandidates, static function (string $path): bool {
        return is_file($path) && is_readable($path);
    }));
    if (!empty($userCandidates)) {
        usort($userCandidates, static function (string $a, string $b): int {
            return filemtime($b) <=> filemtime($a);
        });
        return $userCandidates[0];
    }

    $candidates = [
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'signatures' . DIRECTORY_SEPARATOR . 'ored_default.png',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'signatures' . DIRECTORY_SEPARATOR . 'rd_signature.png',
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function workflow_apply_ored_digital_signature(PDO $pdo, int $documentId, int $actorUserId, ?array $profileOverride = null): array
{
    $result = [
        'stamped' => 0,
        'skipped' => 0,
        'unsupported' => 0,
    ];

    if ($documentId <= 0 || $actorUserId <= 0) {
        return $result;
    }
    if (!workflow_table_exists($pdo, 'document_attachments')) {
        return $result;
    }

    $actorContext = workflow_get_user_context($pdo, $actorUserId);
    $actorRoleKey = app_normalize_role_key((string)($actorContext['role_name'] ?? ''));
    $canDigitallySign = in_array($actorRoleKey, ['CENRO_OFFICER', 'PENRO_OFFICER', 'PASU_OFFICER'], true)
        || workflow_actor_is_ored_signer($pdo, $actorContext);
    if (!$canDigitallySign) {
        return $result;
    }

    $attachmentsStmt = $pdo->prepare(
        'SELECT
            da.id,
            da.file_name,
            da.file_path,
            da.uploaded_by,
            r_u.name AS uploaded_by_role
         FROM document_attachments da
         LEFT JOIN users u_u ON u_u.id = da.uploaded_by
         LEFT JOIN roles r_u ON r_u.id = u_u.role_id
         WHERE da.document_id = :document_id
         ORDER BY da.version_number ASC, da.uploaded_at ASC, da.id ASC'
    );
    $attachmentsStmt->execute(['document_id' => $documentId]);
    $attachments = $attachmentsStmt->fetchAll() ?: [];
    if (empty($attachments)) {
        return $result;
    }

    $signedBySourceName = [];
    foreach ($attachments as $existingAttachment) {
        $existingName = trim((string)($existingAttachment['file_name'] ?? ''));
        if (!str_starts_with($existingName, '[Digitally Signed] ')) {
            continue;
        }
        $sourceName = trim(substr($existingName, strlen('[Digitally Signed] ')));
        if ($sourceName === '') {
            continue;
        }
        $signedBySourceName[strtolower($sourceName)] = true;
    }

    $signableExtensions = ['png', 'jpg', 'jpeg', 'bmp'];
    $preparedResponseRoleKeys = ['DIVISION_CHIEF', 'SECTION_STAFF', 'CENRO_SECTION', 'CENRO_UNIT', 'PASU_OFFICER', 'PAMO_UNIT', 'PENRO_DIVISION', 'PENRO_SECTION', 'PENRO_SECTION_UNIT'];

    $signerName = digital_signature_resolve_signer_name($pdo, $actorUserId);
    $signatureImagePath = digital_signature_resolve_signature_image_path($actorUserId);
    $signatureProfile = is_array($profileOverride)
        ? digital_signature_normalize_profile($profileOverride)
        : digital_signature_load_profile($actorUserId);
    $manilaNow = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    $dateLine = 'Date: ' . $manilaNow->format('Y.m.d');
    $timeLine = $manilaNow->format("H:i:s +08'00'");

    [$relativeDir, $absoluteDir] = intake_build_attachment_storage_paths();
    foreach ($attachments as $attachment) {
        $attachmentId = (int)($attachment['id'] ?? 0);
        $originalName = trim((string)($attachment['file_name'] ?? ''));
        $relativePath = trim((string)($attachment['file_path'] ?? ''));
        $uploadedBy = (int)($attachment['uploaded_by'] ?? 0);
        $uploadedByRoleKey = app_normalize_role_key((string)($attachment['uploaded_by_role'] ?? ''));
        if ($attachmentId <= 0 || $relativePath === '') {
            $result['skipped']++;
            continue;
        }
        if ($uploadedBy === $actorUserId) {
            $result['skipped']++;
            continue;
        }
        if (!in_array($uploadedByRoleKey, $preparedResponseRoleKeys, true)) {
            $result['skipped']++;
            continue;
        }
        if (str_contains(strtolower($originalName), 'digitally signed')) {
            $result['skipped']++;
            continue;
        }
        if ($originalName !== '' && isset($signedBySourceName[strtolower($originalName)])) {
            $result['skipped']++;
            continue;
        }

        $extension = strtolower((string)pathinfo($originalName !== '' ? $originalName : $relativePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $signableExtensions, true)) {
            $result['unsupported']++;
            continue;
        }

        $sourceAbsolutePath = intake_resolve_attachment_absolute_path($relativePath);
        if ($sourceAbsolutePath === null) {
            $result['skipped']++;
            continue;
        }

        $signedFileName = 'doc_' . $documentId
            . '_signed_' . $attachmentId
            . '_' . date('Ymd_His')
            . '_' . bin2hex(random_bytes(3))
            . '.png';
        $signedAbsolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $signedFileName;
        $signedRelativePath = $relativeDir . '/' . $signedFileName;

        digital_signature_sign_image_file(
            $sourceAbsolutePath,
            $signedAbsolutePath,
            $signerName,
            $dateLine,
            $timeLine,
            $signatureImagePath,
            (float)($signatureProfile['x_pct'] ?? 58.0),
            (float)($signatureProfile['y_pct'] ?? 74.0),
            (float)($signatureProfile['block_width_pct'] ?? 42.0),
            (float)($signatureProfile['block_height_pct'] ?? 14.25)
        );

        $displayName = '[Digitally Signed] ' . ($originalName !== '' ? $originalName : basename($relativePath));
        intake_store_generated_attachment($pdo, $documentId, $actorUserId, $displayName, $signedRelativePath, $signedAbsolutePath);
        if ($originalName !== '') {
            $signedBySourceName[strtolower($originalName)] = true;
        }
        $result['stamped']++;
    }

    return $result;
}

function workflow_current_office_leg_started_at(PDO $pdo, int $documentId, int $officeId, string $documentCreatedAt = ''): string
{
    if ($documentId <= 0 || $officeId <= 0 || !workflow_table_exists($pdo, 'tracking_slips')) {
        return $documentCreatedAt;
    }

    $latestReceiveStmt = $pdo->prepare(
        'SELECT MAX(ts.date_time_received)
         FROM tracking_slips ts
         WHERE ts.document_id = :document_id
           AND ts.receiving_office_id = :office_id'
    );
    $latestReceiveStmt->execute([
        'document_id' => $documentId,
        'office_id' => $officeId,
    ]);

    $latestReceiveAt = trim((string)($latestReceiveStmt->fetchColumn() ?: ''));
    return $latestReceiveAt !== '' ? $latestReceiveAt : $documentCreatedAt;
}

function workflow_find_local_sign_window(PDO $pdo, int $documentId, int $officeId, string $roleKey, string $legStartedAt = ''): ?array
{
    if ($documentId <= 0 || $officeId <= 0 || $roleKey === '' || !workflow_table_exists($pdo, 'activity_logs')) {
        return null;
    }

    $params = [
        'document_id' => $documentId,
        'office_id' => $officeId,
        'role_name' => $roleKey,
    ];
    $createdAtFilter = '';
    if ($legStartedAt !== '') {
        $createdAtFilter = ' AND al.created_at >= :leg_started_at';
        $params['leg_started_at'] = $legStartedAt;
    }

    $signedLogStmt = $pdo->prepare(
        'SELECT TOP (1) al.id, al.created_at
         FROM activity_logs al
         INNER JOIN users u ON u.id = al.user_id
         INNER JOIN roles r ON r.id = u.role_id
         WHERE al.document_id = :document_id
           AND LOWER(COALESCE(al.action_type, \'\')) = \'signed\'
           AND u.office_id = :office_id
           AND UPPER(COALESCE(r.name, \'\')) = :role_name'
         . $createdAtFilter
         . ' ORDER BY al.created_at DESC, al.id DESC'
    );
    $signedLogStmt->execute($params);
    $signedLog = $signedLogStmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($signedLog) || empty($signedLog['id'])) {
        return null;
    }

    return [
        'id' => (int)($signedLog['id'] ?? 0),
        'created_at' => trim((string)($signedLog['created_at'] ?? '')),
    ];
}

function workflow_undo_ored_sign(PDO $pdo, int $documentId, int $actorUserId, string $remarks = ''): array
{
    $result = [
        'removed_attachment_count' => 0,
        'removed_signed_log_count' => 0,
    ];

    if ($documentId <= 0 || $actorUserId <= 0) {
        throw new InvalidArgumentException('Document reference is required.');
    }

    $actorContext = workflow_get_user_context($pdo, $actorUserId);
    $actorRoleKey = app_normalize_role_key((string)($actorContext['role_name'] ?? ''));
    $canUndoSign = in_array($actorRoleKey, ['CENRO_OFFICER', 'PENRO_OFFICER', 'PASU_OFFICER'], true)
        || workflow_actor_is_ored_signer($pdo, $actorContext);
    if (!$canUndoSign) {
        throw new RuntimeException('Only the ORED signing account, CENRO Officer, PENRO Officer, or PASU can undo a sign action.');
    }

    $document = workflow_get_document_context($pdo, $documentId, true);
    $status = strtolower(trim((string)($document['status'] ?? '')));
    $isSignedStage = str_contains($status, 'signed') || str_contains($status, 'completed');
    if (!$isSignedStage) {
        throw new RuntimeException('Document is not in signed stage.');
    }
    if (str_contains($status, 'forward') || str_starts_with($status, 'assigned to ')) {
        throw new RuntimeException('Undo Sign is allowed only before forwarding.');
    }

    $actorOfficeId = (int)($actorContext['office_id'] ?? 0);
    $currentOfficeId = (int)($document['current_office_id'] ?? 0);
    if ($actorOfficeId > 0 && $currentOfficeId > 0 && $actorOfficeId !== $currentOfficeId) {
        throw new RuntimeException('You can undo sign only while the document is still in your office.');
    }
    $documentCreatedAt = trim((string)($document['created_at'] ?? ''));
    $legStartedAt = workflow_current_office_leg_started_at($pdo, $documentId, $actorOfficeId, $documentCreatedAt);
    $localSignWindow = workflow_find_local_sign_window(
        $pdo,
        $documentId,
        $actorOfficeId,
        $actorRoleKey,
        $legStartedAt
    );
    if (!is_array($localSignWindow) || (int)($localSignWindow['id'] ?? 0) <= 0) {
        throw new RuntimeException('No active sign record was found for your current office leg.');
    }

    $attachmentFilePathsToDelete = [];

    $pdo->beginTransaction();
    try {
        if (workflow_table_exists($pdo, 'document_attachments')) {
            $signedAttachmentRowsStmt = $pdo->prepare(
                'SELECT da.id, da.file_path
                 FROM document_attachments da
                 INNER JOIN users u ON u.id = da.uploaded_by
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE da.document_id = :document_id
                   AND u.office_id = :office_id
                   AND UPPER(COALESCE(r.name, \'\')) = :role_name
                   AND da.uploaded_at >= :leg_started_at
                   AND (
                        LOWER(COALESCE(da.file_name, \'\')) LIKE :signed_name_pattern
                        OR LOWER(COALESCE(da.file_path, \'\')) LIKE :signed_path_pattern
                   )'
            );
            $signedAttachmentRowsStmt->execute([
                'document_id' => $documentId,
                'office_id' => $actorOfficeId,
                'role_name' => $actorRoleKey,
                'leg_started_at' => $legStartedAt !== '' ? $legStartedAt : $localSignWindow['created_at'],
                'signed_name_pattern' => '[digitally signed] %',
                'signed_path_pattern' => '%_signed_%',
            ]);
            $signedAttachmentRows = $signedAttachmentRowsStmt->fetchAll() ?: [];

            if (!empty($signedAttachmentRows)) {
                $deleteAttachmentStmt = $pdo->prepare(
                    'DELETE FROM document_attachments
                     WHERE id = :attachment_id
                       AND document_id = :document_id'
                );
                foreach ($signedAttachmentRows as $signedAttachmentRow) {
                    $attachmentId = (int)($signedAttachmentRow['id'] ?? 0);
                    if ($attachmentId <= 0) {
                        continue;
                    }
                    $filePath = trim((string)($signedAttachmentRow['file_path'] ?? ''));
                    if ($filePath !== '') {
                        $attachmentFilePathsToDelete[] = $filePath;
                    }
                    $deleteAttachmentStmt->execute([
                        'attachment_id' => $attachmentId,
                        'document_id' => $documentId,
                    ]);
                    attachment_binary_backup_delete_for_attachment($pdo, $attachmentId);
                    $result['removed_attachment_count'] += max(0, (int)$deleteAttachmentStmt->rowCount());
                }
            }
        }

        if (workflow_table_exists($pdo, 'activity_logs')) {
            $deleteSignedLogsStmt = $pdo->prepare(
                "DELETE al
                 FROM activity_logs al
                 INNER JOIN users u ON u.id = al.user_id
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE al.document_id = :document_id
                   AND LOWER(COALESCE(al.action_type, '')) = 'signed'
                   AND u.office_id = :office_id
                   AND UPPER(COALESCE(r.name, '')) = :role_name
                   AND al.created_at >= :leg_started_at"
            );
            $deleteSignedLogsStmt->execute([
                'document_id' => $documentId,
                'office_id' => $actorOfficeId,
                'role_name' => $actorRoleKey,
                'leg_started_at' => $legStartedAt !== '' ? $legStartedAt : $localSignWindow['created_at'],
            ]);
            $result['removed_signed_log_count'] = max(0, (int)$deleteSignedLogsStmt->rowCount());
        }

        $updateStatusStmt = $pdo->prepare(
            'UPDATE documents
             SET status = :status
             WHERE id = :document_id'
        );
        $updateStatusStmt->execute([
            'status' => 'Approved',
            'document_id' => $documentId,
        ]);

        workflow_log_action($pdo, [
            'document_id' => $documentId,
            'user_id' => $actorUserId,
            'action_type' => 'Unsigned',
            'action_scope' => 'ACTION',
            'remarks' => $remarks !== '' ? $remarks : 'Undo sign executed. Status reverted to Approved.',
            'is_visible_on_slip' => 0,
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    foreach ($attachmentFilePathsToDelete as $relativePath) {
        intake_delete_attachment_file_if_present((string)$relativePath);
    }

    return $result;
}

$pdo = null;
$actorUserId = (int)($_SESSION['user_id'] ?? 0);

try {
    $pdo = getDatabaseConnection();
    $actorContext = workflow_get_user_context($pdo, $actorUserId);
    $actorRoleKey = app_normalize_role_key((string)($actorContext['role_name'] ?? ''));
    $actorOfficeId = (int)($actorContext['office_id'] ?? 0);
    if ($isOutboxSyncRequest && !app_offline_is_workflow_outbox_enabled((string)($actorContext['role_name'] ?? ''), $action)) {
        $rolloutMessage = document_action_rollout_block_message($action);
        offline_sync_log_event($pdo, [
            'event_type' => 'ROLLOUT_BLOCKED',
            'route_kind' => 'document_action',
            'action_name' => $action,
            'operation_id' => $operationId,
            'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'source' => 'server-document-action',
            'message' => $rolloutMessage,
            'http_status' => 403,
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
    $successMessage = 'Action completed.';
    $remarksAttachmentFiles = [];
    $remarksAttachmentCount = 0;
    $currentDocumentVersion = 0;
    $preparedResponseRouteContext = [
        'allowed' => false,
        'is_ard_destination' => false,
        'is_division_chief_route' => false,
        'is_section_staff_route' => false,
        'is_cenro_section_route' => false,
        'is_cenro_unit_route' => false,
        'target_label' => '',
    ];
    $endorsementRouteContext = [
        'allowed' => false,
        'required' => false,
        'target_label' => '',
    ];
    $rawUploadedFiles = intake_normalize_uploaded_files($_FILES['attachment_files'] ?? null);

    if ($action !== 'EDIT') {
        $remarksAttachmentFiles = intake_validate_uploaded_files($rawUploadedFiles);
    }

    if ($documentId <= 0 && $action === 'RECEIVE' && $trackingId !== '') {
        $resolveStmt = $pdo->prepare(
            'SELECT TOP (1) id
             FROM documents
             WHERE UPPER(tracking_id) = :tracking_id'
        );
        $resolveStmt->execute(['tracking_id' => $trackingId]);
        $documentId = (int)($resolveStmt->fetchColumn() ?: 0);
    }

    if ($documentId <= 0) {
        throw new InvalidArgumentException(
            $trackingId !== '' ? 'Tracking ID not found.' : 'Document ID is required.'
        );
    }

    if ($operationId !== '') {
        $idempotencyDecision = document_action_idempotency_begin(
            $pdo,
            $actorUserId,
            $operationId,
            document_action_idempotency_request_hash($_POST, $rawUploadedFiles)
        );
        if (($idempotencyDecision['mode'] ?? '') === 'replay') {
            http_response_code((int)($idempotencyDecision['code'] ?? 200));
            echo json_encode($idempotencyDecision['payload'] ?? [
                'ok' => false,
                'message' => 'Unable to replay document action safely.',
            ]);
            exit;
        }
    }

    workflow_ensure_document_row_version_column($pdo);
    document_action_assert_precondition($pdo, $documentId, $preconditionVersion, $action);
    document_action_assert_sensitive_sync_reauth($action, $isOutboxSyncRequest);

    $routingSubjectChangeRequested = in_array($action, ['FORWARD', 'REROUTE', 'RETURN'], true) && $subject !== '';
    if ($routingSubjectChangeRequested) {
        if (!in_array($actorRoleKey, ['DIVISION_CHIEF', 'SECTION_STAFF', 'CENRO_SECTION', 'CENRO_UNIT', 'PASU_OFFICER', 'PAMO_UNIT', 'PENRO_DIVISION', 'PENRO_SECTION', 'PENRO_SECTION_UNIT'], true)) {
            throw new RuntimeException('Only Division/Section roles can add response while routing.');
        }
        if (strlen($subject) > 255) {
            throw new InvalidArgumentException('Response is too long.');
        }
    }

    switch ($action) {
        case 'FORWARD':
            if ($destinationOfficeId <= 0) {
                throw new InvalidArgumentException('Destination office is required.');
            }
            $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
            $preparedResponseRouteContext = workflow_build_prepared_response_route_context(
                $actorRoleKey,
                $action,
                $destinationOffice
            );
            $endorsementRouteContext = workflow_build_endorsement_route_context(
                $actorRoleKey,
                $action,
                $destinationOffice
            );
            if (($preparedResponseRouteContext['allowed'] ?? false) && empty($remarksAttachmentFiles)) {
                $existingPreparedResponseOfficeId = (int)($actorOfficeId ?? 0);
                $hasExistingPreparedResponse = workflow_document_has_prepared_response_attachment(
                    $pdo,
                    $documentId,
                    $existingPreparedResponseOfficeId
                );
                if (!$hasExistingPreparedResponse) {
                    $targetLabel = trim((string)($preparedResponseRouteContext['target_label'] ?? ''));
                    if ($targetLabel === '') {
                        $targetLabel = 'the destination office';
                    }
                    throw new InvalidArgumentException(
                        'Prepared of response attachment is required before forwarding to '
                        . $targetLabel
                        . '. Upload at least one file, unless your current office or its child office already uploaded the prepared response.'
                        . ' A prepared response from another office outside your office chain does not satisfy this requirement.'
                    );
                }
            }
            if (($endorsementRouteContext['required'] ?? false) && empty($remarksAttachmentFiles)) {
                $endorsementTargetLabel = trim((string)($endorsementRouteContext['target_label'] ?? 'destination office'));
                if ($endorsementTargetLabel === '') {
                    $endorsementTargetLabel = 'destination office';
                }
                $existingEndorsementOfficeId = (int)($actorOfficeId ?? 0);
                $hasExistingEndorsement = workflow_document_has_endorsement_attachment(
                    $pdo,
                    $documentId,
                    $existingEndorsementOfficeId
                );
                if (!$hasExistingEndorsement) {
                    throw new InvalidArgumentException(
                        'Endorsement letter attachment is required before forwarding to '
                        . $endorsementTargetLabel
                        . '.'
                    );
                }
            }
            workflow_forward_document(
                $pdo,
                $documentId,
                $actorUserId,
                $destinationOfficeId,
                $destinationUserId > 0 ? $destinationUserId : null,
                $remarks,
                $bypassReason,
                !empty($remarksAttachmentFiles),
                $routeMode
            );
            break;

        case 'REROUTE':
            if ($destinationOfficeId <= 0) {
                throw new InvalidArgumentException('Destination office is required.');
            }
            workflow_reroute_document($pdo, $documentId, $actorUserId, $destinationOfficeId, $remarks);
            break;

        case 'RETURN':
            if ($destinationOfficeId <= 0) {
                throw new InvalidArgumentException('Destination office is required.');
            }
            workflow_return_document(
                $pdo,
                $documentId,
                $actorUserId,
                $destinationOfficeId,
                $destinationUserId > 0 ? $destinationUserId : null,
                $remarks
            );
            break;

        case 'OVERRIDE':
            if ($destinationOfficeId <= 0) {
                throw new InvalidArgumentException('Destination office is required.');
            }
            workflow_override_document(
                $pdo,
                $documentId,
                $actorUserId,
                $destinationOfficeId,
                $destinationUserId > 0 ? $destinationUserId : null,
                $remarks
            );
            break;

        case 'RELEASE':
            if ($destinationOfficeId <= 0 && $releaseMode === 'send_to_office') {
                throw new InvalidArgumentException('Destination office is required.');
            }
            if ($destinationOfficeId <= 0) {
                $originStmt = $pdo->prepare(
                    'SELECT originating_office_id
                     FROM documents
                     WHERE id = :id'
                );
                $originStmt->execute(['id' => $documentId]);
                $destinationOfficeId = (int)($originStmt->fetchColumn() ?: 0);
                if ($destinationOfficeId <= 0) {
                    throw new InvalidArgumentException('Destination office is required.');
                }
            }
            $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
            $endorsementRouteContext = workflow_build_endorsement_route_context(
                $actorRoleKey,
                $action,
                $destinationOffice,
                $releaseMode
            );
            if (($endorsementRouteContext['required'] ?? false) && empty($remarksAttachmentFiles)) {
                $endorsementTargetLabel = trim((string)($endorsementRouteContext['target_label'] ?? 'destination office'));
                if ($endorsementTargetLabel === '') {
                    $endorsementTargetLabel = 'destination office';
                }
                $existingEndorsementOfficeId = (int)($actorOfficeId ?? 0);
                $hasExistingEndorsement = workflow_document_has_endorsement_attachment(
                    $pdo,
                    $documentId,
                    $existingEndorsementOfficeId
                );
                if (!$hasExistingEndorsement) {
                    throw new InvalidArgumentException(
                        'Endorsement letter attachment is required before releasing to '
                        . $endorsementTargetLabel
                        . '.'
                    );
                }
            }
            workflow_release_document(
                $pdo,
                $documentId,
                $actorUserId,
                $destinationOfficeId,
                $destinationUserId > 0 ? $destinationUserId : null,
                $remarks,
                $releaseMode
            );
            break;

        case 'APPROVE':
            throw new RuntimeException('Approve action is no longer available.');

        case 'SIGN':
            $actorContext = workflow_get_user_context($pdo, $actorUserId);
            $actorRoleKey = app_normalize_role_key((string)($actorContext['role_name'] ?? ''));
            if (in_array($actorRoleKey, ['CENRO_OFFICER', 'PENRO_OFFICER', 'PASU_OFFICER'], true)) {
                throw new RuntimeException('Digital signing is no longer available for this role.');
            }
            if (!workflow_actor_is_ored_signer($pdo, $actorContext)) {
                throw new RuntimeException('Digital signing is available only for the ORED signing account.');
            }
            workflow_sign_document(
                $pdo,
                $documentId,
                $actorUserId,
                $remarks
            );
            $successMessage = 'Document signed successfully.';
            $signatureProfileOverride = null;
            if (
                isset($_POST['x_pct'])
                || isset($_POST['y_pct'])
                || isset($_POST['block_width_pct'])
                || isset($_POST['block_height_pct'])
                || isset($_POST['mode'])
                || isset($_POST['paper_size'])
            ) {
                $signatureProfileOverride = [
                    'mode' => (string)($_POST['mode'] ?? 'Grid-Snap'),
                    'paper_size' => (string)($_POST['paper_size'] ?? 'A4'),
                    'x_pct' => (float)($_POST['x_pct'] ?? 58.0),
                    'y_pct' => (float)($_POST['y_pct'] ?? 74.0),
                    'block_width_pct' => (float)($_POST['block_width_pct'] ?? 42.0),
                    'block_height_pct' => (float)($_POST['block_height_pct'] ?? 14.25),
                ];
            }
            try {
                $digitalSignatureResult = workflow_apply_ored_digital_signature(
                    $pdo,
                    $documentId,
                    $actorUserId,
                    $signatureProfileOverride
                );
                $stampedCount = (int)($digitalSignatureResult['stamped'] ?? 0);
                $unsupportedCount = (int)($digitalSignatureResult['unsupported'] ?? 0);
                if ($stampedCount > 0) {
                    $successMessage .= ' Digital signature overlay applied to ' . $stampedCount . ' attachment(s).';
                    workflow_log_action($pdo, [
                        'document_id' => $documentId,
                        'user_id' => $actorUserId,
                        'action_type' => 'Edited',
                        'action_scope' => 'ACTION',
                        'remarks' => 'Digital signature overlay applied to ' . $stampedCount . ' attachment(s) after sign action.',
                        'is_visible_on_slip' => 0,
                    ]);
                } elseif ($unsupportedCount > 0) {
                    $successMessage .= ' No eligible image attachment found for digital overlay (supported: PNG/JPG/JPEG/BMP).';
                }
            } catch (Throwable $signatureException) {
                $successMessage .= ' Digital signature overlay was not applied: ' . $signatureException->getMessage();
            }
            break;

        case 'UNSIGN':
            $undoResult = workflow_undo_ored_sign(
                $pdo,
                $documentId,
                $actorUserId,
                $remarks
            );
            $successMessage = 'Sign has been undone. Document is back to Approved stage.';
            $removedAttachmentCount = (int)($undoResult['removed_attachment_count'] ?? 0);
            if ($removedAttachmentCount > 0) {
                $successMessage .= ' Removed ' . $removedAttachmentCount . ' digitally signed attachment(s).';
            }
            break;

        case 'PENDING':
            workflow_mark_pending(
                $pdo,
                $documentId,
                $actorUserId,
                $remarks
            );
            break;

        case 'COMPLETE':
            workflow_complete_document(
                $pdo,
                $documentId,
                $actorUserId,
                $remarks
            );
            break;

        case 'RECEIVE':
            workflow_mark_received(
                $pdo,
                $documentId,
                $actorUserId,
                $destinationOfficeId > 0 ? $destinationOfficeId : null,
                $remarks,
                $receiveMethod
            );
            break;

        case 'EDIT':
            if ($subject === '') {
                throw new InvalidArgumentException('Subject is required.');
            }
            if (strlen($subject) > 255) {
                throw new InvalidArgumentException('Subject is too long.');
            }

            $uploadedFiles = $rawUploadedFiles;
            $validatedFiles = intake_validate_uploaded_files($uploadedFiles);

            $pdo->beginTransaction();
            try {
                $document = intake_assert_manage_permission($pdo, $documentId, $actorUserId);

                $nextSourceType = $sourceType !== ''
                    ? workflow_normalize_source_type($sourceType)
                    : workflow_normalize_source_type((string)($document['source_type'] ?? 'INTERNAL'));
                $nextDocumentTypeId = $documentTypeId > 0
                    ? $documentTypeId
                    : (int)($document['document_type_id'] ?? 0);
                if ($nextDocumentTypeId <= 0) {
                    throw new InvalidArgumentException('Document type is required.');
                }

                $docTypeExistsStmt = $pdo->prepare(
                    'SELECT COUNT(*)
                     FROM document_types
                     WHERE id = :id'
                );
                $docTypeExistsStmt->execute(['id' => $nextDocumentTypeId]);
                if ((int)($docTypeExistsStmt->fetchColumn() ?: 0) <= 0) {
                    throw new InvalidArgumentException('Selected document type is not valid.');
                }

                $existingArtaCategory = trim((string)($document['arta_category_override'] ?? ''));
                if ($existingArtaCategory === '') {
                    $existingArtaCategory = trim((string)($document['arta_category'] ?? ''));
                }
                $complexityDefaults = intake_complexity_defaults(
                    $intakeComplexityTypeRaw !== '' ? $intakeComplexityTypeRaw : $existingArtaCategory
                );
                $nextComplexityType = (string)($complexityDefaults['category'] ?? 'Simple');
                if ($nextComplexityType === '') {
                    $nextComplexityType = 'Simple';
                }

                $existingArtaDaysLimit = (int)($document['arta_days_limit_override'] ?? 0);
                if ($existingArtaDaysLimit <= 0) {
                    $existingArtaDaysLimit = (int)($document['arta_days_limit'] ?? 0);
                }
                $nextComplexityDays = $intakeComplexityDaysRaw === ''
                    ? ($existingArtaDaysLimit > 0 ? $existingArtaDaysLimit : (int)($complexityDefaults['days'] ?? 3))
                    : (int)$intakeComplexityDaysRaw;
                if ($nextComplexityDays < 1 || $nextComplexityDays > 365) {
                    throw new InvalidArgumentException('Days must be a whole number from 1 to 365.');
                }

                $existingExternalClientName = trim((string)($document['external_client_name'] ?? ''));
                $existingClientAddress = trim((string)($document['client_address'] ?? ''));
                $existingSender = trim((string)($document['sender'] ?? ''));
                $existingOriginatingEntityName = trim((string)($document['originating_entity_name'] ?? ''));
                $nextExternalClientName = $nextSourceType === 'EXTERNAL'
                    ? ($externalClientName !== '' ? $externalClientName : $existingExternalClientName)
                    : '';
                if ($nextSourceType === 'EXTERNAL' && $nextExternalClientName === '') {
                    throw new InvalidArgumentException('External client name is required when source is External.');
                }
                $nextClientAddress = $nextSourceType === 'EXTERNAL'
                    ? ($clientAddress !== '' ? $clientAddress : $existingClientAddress)
                    : $clientAddress;
                if ($nextSourceType === 'EXTERNAL' && $nextClientAddress === '') {
                    throw new InvalidArgumentException('Client address is required when source is External.');
                }

                workflow_ensure_document_arta_override_columns($pdo);
                workflow_ensure_document_sender_column($pdo);
                workflow_ensure_document_client_address_column($pdo);
                workflow_ensure_document_originating_entity_column($pdo);
                $originOfficeContext = workflow_get_office_context($pdo, (int)($document['originating_office_id'] ?? 0));
                $defaultInternalSender = trim((string)($originOfficeContext['name'] ?? ''));
                $defaultOriginatingEntityName = $defaultInternalSender;
                $nextSender = $sender !== ''
                    ? $sender
                    : ($nextSourceType === 'EXTERNAL'
                        ? ($nextExternalClientName !== '' ? $nextExternalClientName : $existingSender)
                        : ($existingSender !== '' ? $existingSender : $defaultInternalSender));
                $nextOriginatingEntityName = $originatingEntityName !== ''
                    ? $originatingEntityName
                    : ($existingOriginatingEntityName !== '' ? $existingOriginatingEntityName : $defaultOriginatingEntityName);
                if ($nextOriginatingEntityName === '') {
                    throw new InvalidArgumentException('Originating office / entity is required.');
                }
                $updateStmt = $pdo->prepare(
                    'UPDATE documents
                     SET subject = :subject,
                         source_type = :source_type,
                         sender = :sender,
                         external_client_name = :external_client_name,
                         client_address = :client_address,
                         originating_entity_name = :originating_entity_name,
                         document_type_id = :document_type_id,
                         arta_category_override = :arta_category_override,
                         arta_days_limit_override = :arta_days_limit_override
                     WHERE id = :document_id'
                );
                $updateStmt->execute([
                    'subject' => $subject,
                    'source_type' => $nextSourceType,
                    'sender' => $nextSender !== '' ? $nextSender : null,
                    'external_client_name' => $nextSourceType === 'EXTERNAL' ? $nextExternalClientName : null,
                    'client_address' => $nextClientAddress !== '' ? $nextClientAddress : null,
                    'originating_entity_name' => $nextOriginatingEntityName !== '' ? $nextOriginatingEntityName : null,
                    'document_type_id' => $nextDocumentTypeId,
                    'arta_category_override' => $nextComplexityType,
                    'arta_days_limit_override' => $nextComplexityDays,
                    'document_id' => $documentId,
                ]);

                $savedAttachmentCount = intake_store_document_attachments($pdo, $documentId, $actorUserId, $validatedFiles);

                workflow_log_action($pdo, [
                    'document_id' => $documentId,
                    'user_id' => $actorUserId,
                    'action_type' => 'Edited',
                    'action_scope' => 'ACTION',
                    'remarks' => $remarks !== ''
                        ? $remarks
                        : ('Intake details updated.' . ($savedAttachmentCount > 0 ? ' Added ' . $savedAttachmentCount . ' attachment(s).' : '')),
                    'is_visible_on_slip' => 0,
                ]);

                $pdo->commit();
                $successMessage = 'Document updated successfully.'
                    . ($savedAttachmentCount > 0 ? ' ' . $savedAttachmentCount . ' attachment(s) added.' : '');
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
            break;

        case 'DELETE':
            $pdo->beginTransaction();
            try {
                intake_assert_manage_permission($pdo, $documentId, $actorUserId);

                document_recycle_bin_archive_document($pdo, $documentId, $actorUserId);

                $attachmentsTableExists = db_table_exists($pdo, 'document_attachments');

                if ($attachmentsTableExists) {
                    $attachmentDeleteStmt = $pdo->prepare(
                        'DELETE FROM document_attachments
                         WHERE document_id = :document_id'
                    );
                    $attachmentDeleteStmt->execute(['document_id' => $documentId]);
                }

                if (workflow_table_exists($pdo, 'tracking_slips')) {
                    $trackingSlipDeleteStmt = $pdo->prepare(
                        'DELETE FROM tracking_slips
                         WHERE document_id = :document_id'
                    );
                    $trackingSlipDeleteStmt->execute(['document_id' => $documentId]);
                }

                if (workflow_table_exists($pdo, 'activity_logs')) {
                    $activityDeleteStmt = $pdo->prepare(
                        'DELETE FROM activity_logs
                         WHERE document_id = :document_id'
                    );
                    $activityDeleteStmt->execute(['document_id' => $documentId]);
                }

                $documentDeleteStmt = $pdo->prepare(
                    'DELETE FROM documents
                     WHERE id = :document_id'
                );
                $documentDeleteStmt->execute(['document_id' => $documentId]);
                if ($documentDeleteStmt->rowCount() < 1) {
                    throw new RuntimeException('Unable to delete document.');
                }

                $pdo->commit();
                $successMessage = 'Document moved to recycle bin.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
            break;

        case 'DELETE_ATTACHMENT':
            if ($attachmentId <= 0) {
                throw new InvalidArgumentException('Attachment ID is required.');
            }

            $filePathToDelete = '';
            $attachmentName = 'Attachment';

            $pdo->beginTransaction();
            try {
                intake_assert_manage_permission($pdo, $documentId, $actorUserId);

                if (!db_table_exists($pdo, 'document_attachments')) {
                    throw new RuntimeException('Attachment storage table is not available.');
                }

                $attachmentSelectStmt = $pdo->prepare(
                    'SELECT id, file_name, file_path, uploaded_by
                     FROM document_attachments
                     WHERE id = :attachment_id
                       AND document_id = :document_id'
                );
                $attachmentSelectStmt->execute([
                    'attachment_id' => $attachmentId,
                    'document_id' => $documentId,
                ]);
                $attachment = $attachmentSelectStmt->fetch();
                if (!$attachment) {
                    throw new RuntimeException('Attachment not found.');
                }

                $uploadedBy = (int)($attachment['uploaded_by'] ?? 0);
                if ($uploadedBy <= 0 || $uploadedBy !== $actorUserId) {
                    throw new RuntimeException('You can only delete your own attachments.');
                }

                $attachmentName = trim((string)($attachment['file_name'] ?? ''));
                if ($attachmentName === '') {
                    $attachmentName = 'Attachment';
                }
                $filePathToDelete = trim((string)($attachment['file_path'] ?? ''));

                $attachmentDeleteStmt = $pdo->prepare(
                    'DELETE FROM document_attachments
                     WHERE id = :attachment_id
                       AND document_id = :document_id'
                );
                $attachmentDeleteStmt->execute([
                    'attachment_id' => $attachmentId,
                    'document_id' => $documentId,
                ]);
                if ($attachmentDeleteStmt->rowCount() < 1) {
                    throw new RuntimeException('Unable to delete attachment.');
                }
                attachment_binary_backup_delete_for_attachment($pdo, $attachmentId);

                workflow_log_action($pdo, [
                    'document_id' => $documentId,
                    'user_id' => $actorUserId,
                    'action_type' => 'Edited',
                    'action_scope' => 'ACTION',
                    'remarks' => 'Deleted attachment: ' . $attachmentName,
                    'is_visible_on_slip' => 0,
                ]);

                $pdo->commit();

                if ($filePathToDelete !== '') {
                    intake_delete_attachment_file_if_present($filePathToDelete);
                }

                $successMessage = 'Attachment deleted successfully.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
            break;

        case 'ROUTE_DELETE_ATTACHMENT':
            if ($attachmentId <= 0) {
                throw new InvalidArgumentException('Attachment ID is required.');
            }

            $filePathToDelete = '';
            $attachmentName = 'Attachment';

            $pdo->beginTransaction();
            try {
                $permission = document_action_assert_route_attachment_manage_permission(
                    $pdo,
                    $documentId,
                    $attachmentId,
                    $actorUserId
                );
                $attachment = $permission['attachment'];
                $attachmentName = trim((string)($attachment['file_name'] ?? ''));
                if ($attachmentName === '') {
                    $attachmentName = 'Attachment';
                }
                $filePathToDelete = trim((string)($attachment['file_path'] ?? ''));

                $attachmentDeleteStmt = $pdo->prepare(
                    'DELETE FROM document_attachments
                     WHERE id = :attachment_id
                       AND document_id = :document_id'
                );
                $attachmentDeleteStmt->execute([
                    'attachment_id' => $attachmentId,
                    'document_id' => $documentId,
                ]);
                if ($attachmentDeleteStmt->rowCount() < 1) {
                    throw new RuntimeException('Unable to delete attachment.');
                }
                attachment_binary_backup_delete_for_attachment($pdo, $attachmentId);

                $attachmentLabel = ((string)($attachment['route_attachment_category'] ?? '') === 'endorsement')
                    ? 'endorsement attachment'
                    : 'prepared response attachment';
                workflow_log_action($pdo, [
                    'document_id' => $documentId,
                    'user_id' => $actorUserId,
                    'action_type' => 'Edited',
                    'action_scope' => 'ACTION',
                    'remarks' => 'Deleted ' . $attachmentLabel . ': ' . $attachmentName,
                    'is_visible_on_slip' => 0,
                ]);

                $pdo->commit();

                if ($filePathToDelete !== '') {
                    intake_delete_attachment_file_if_present($filePathToDelete);
                }

                $successMessage = 'Attachment deleted successfully.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
            break;

        case 'REPLACE_ROUTE_ATTACHMENT':
            if ($attachmentId <= 0) {
                throw new InvalidArgumentException('Attachment ID is required.');
            }
            if (empty($remarksAttachmentFiles)) {
                throw new InvalidArgumentException('Upload one replacement file first.');
            }

            $oldFilePathToDelete = '';

            $pdo->beginTransaction();
            try {
                $replacement = document_action_replace_route_attachment_file(
                    $pdo,
                    $documentId,
                    $attachmentId,
                    $actorUserId,
                    $remarksAttachmentFiles
                );
                $oldFilePathToDelete = trim((string)($replacement['old_file_path'] ?? ''));
                $replacementName = trim((string)($replacement['new_file_name'] ?? ''));
                if ($replacementName === '') {
                    $replacementName = 'Attachment';
                }
                $attachmentLabel = ((string)($replacement['attachment_category'] ?? '') === 'endorsement')
                    ? 'endorsement attachment'
                    : 'prepared response attachment';

                workflow_log_action($pdo, [
                    'document_id' => $documentId,
                    'user_id' => $actorUserId,
                    'action_type' => 'Edited',
                    'action_scope' => 'ACTION',
                    'remarks' => 'Replaced ' . $attachmentLabel . ' with: ' . $replacementName,
                    'is_visible_on_slip' => 0,
                ]);

                $pdo->commit();

                if ($oldFilePathToDelete !== '') {
                    intake_delete_attachment_file_if_present($oldFilePathToDelete);
                }

                $successMessage = 'Attachment replaced successfully.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
            break;

        default:
            throw new InvalidArgumentException('Unsupported action.');
    }

    $subjectChange = workflow_apply_subject_change_if_requested(
        $pdo,
        $documentId,
        $actorUserId,
        $action,
        $subject,
        $actorRoleKey
    );
    if (is_array($subjectChange)) {
        $successMessage .= ' Response recorded: "' . (string)$subjectChange['to'] . '".';
    }

    if (!empty($remarksAttachmentFiles) && !in_array($action, ['REPLACE_ROUTE_ATTACHMENT'], true)) {
        $preparedResponseAllowed = (bool)($preparedResponseRouteContext['allowed'] ?? false);
        $endorsementAllowed = (bool)($endorsementRouteContext['allowed'] ?? false);
        if (!$preparedResponseAllowed && !$endorsementAllowed) {
            throw new InvalidArgumentException('Route attachments are allowed only for prepared response or endorsement routing routes.');
        }

        $remarksAttachmentCount = intake_store_document_attachments($pdo, $documentId, $actorUserId, $remarksAttachmentFiles);
        if ($remarksAttachmentCount > 0) {
            if ($preparedResponseAllowed) {
                $preparedResponseTarget = trim((string)($preparedResponseRouteContext['target_label'] ?? ''));
                if ($preparedResponseTarget === '') {
                    $preparedResponseTarget = 'destination office';
                }
                workflow_log_action($pdo, [
                    'document_id' => $documentId,
                    'user_id' => $actorUserId,
                    'action_type' => 'Edited',
                    'action_scope' => 'ACTION',
                    'remarks' => 'Prepared of response attached (' . $remarksAttachmentCount . ' file(s)) for ' . $preparedResponseTarget . '.',
                    'is_visible_on_slip' => 0,
                ]);
                $successMessage .= ' Prepared of response uploaded (' . $remarksAttachmentCount . ' file(s)).';
            } elseif ($endorsementAllowed) {
                $endorsementTarget = trim((string)($endorsementRouteContext['target_label'] ?? 'destination office'));
                if ($endorsementTarget === '') {
                    $endorsementTarget = 'destination office';
                }
                $endorsementActionPhrase = ($action === 'RELEASE')
                    ? 'releasing to '
                    : 'forwarding to ';
                workflow_log_action($pdo, [
                    'document_id' => $documentId,
                    'user_id' => $actorUserId,
                    'action_type' => 'Edited',
                    'action_scope' => 'ACTION',
                    'remarks' => 'Endorsement letter attached (' . $remarksAttachmentCount . ' file(s)) for ' . $endorsementActionPhrase . $endorsementTarget . '.',
                    'is_visible_on_slip' => 0,
                ]);
                $successMessage .= ' Endorsement letter uploaded (' . $remarksAttachmentCount . ' file(s)).';
            }
        }
    }

    if ($action !== 'DELETE') {
        document_action_touch_row_version($pdo, $documentId);
        $currentDocumentVersion = document_action_current_row_version($pdo, $documentId);
    }
    if (in_array($action, ['FORWARD', 'RELEASE', 'SIGN'], true)) {
        $mailEventMap = [
            'FORWARD' => 'forwarded',
            'RELEASE' => 'released',
            'SIGN' => 'signed',
        ];
        $mailContext = [
            'destination_office_id' => $destinationOfficeId,
            'remarks' => $remarks,
        ];
        if ($operationId !== '') {
            $mailContext['dedupe_key'] = 'workflow:' . $operationId;
        }
        notification_mail_send_document_event(
            $pdo,
            (string)$mailEventMap[$action],
            $documentId,
            $mailContext
        );
    }
    $responsePayload = ['ok' => true, 'message' => $successMessage];
    if ($currentDocumentVersion > 0) {
        $responsePayload['current_version'] = $currentDocumentVersion;
    }
    if ($operationId !== '') {
        $responsePayload['operation_id'] = $operationId;
    }
    document_action_idempotency_mark_completed($pdo, $actorUserId, $operationId, $responsePayload, 200);
    offline_sync_log_event($pdo, [
        'event_type' => $isOutboxSyncRequest ? 'SYNC_SUCCESS' : 'ACTION_SUCCESS',
        'route_kind' => 'document_action',
        'action_name' => $action,
        'operation_id' => $operationId,
        'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'source' => 'server-document-action',
        'http_status' => 200,
        'message' => (string)($responsePayload['message'] ?? 'Action completed.'),
        'payload' => [
            'is_outbox_sync' => $isOutboxSyncRequest,
            'role_key' => $actorRoleKey,
            'current_version' => (int)($responsePayload['current_version'] ?? 0),
        ],
    ]);
    echo json_encode($responsePayload);
} catch (DocumentVersionConflictException $exception) {
    document_action_idempotency_mark_failed($pdo, $actorUserId, $operationId, $exception->getMessage(), 409);
    offline_sync_log_event($pdo, [
        'event_type' => 'ACTION_CONFLICT',
        'route_kind' => 'document_action',
        'action_name' => $action,
        'operation_id' => $operationId,
        'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'source' => 'server-document-action',
        'http_status' => 409,
        'message' => $exception->getMessage(),
        'payload' => [
            'is_outbox_sync' => $isOutboxSyncRequest,
            'current_version' => $exception->currentVersion(),
        ],
    ]);
    http_response_code(409);
    echo json_encode([
        'ok' => false,
        'message' => $exception->getMessage(),
        'conflict' => true,
        'current_version' => $exception->currentVersion(),
    ]);
} catch (DocumentActionIdempotencyConflictException $exception) {
    document_action_idempotency_mark_failed($pdo, $actorUserId, $operationId, $exception->getMessage(), 409);
    offline_sync_log_event($pdo, [
        'event_type' => 'IDEMPOTENCY_CONFLICT',
        'route_kind' => 'document_action',
        'action_name' => $action,
        'operation_id' => $operationId,
        'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'source' => 'server-document-action',
        'http_status' => 409,
        'message' => $exception->getMessage(),
        'payload' => [
            'is_outbox_sync' => $isOutboxSyncRequest,
        ],
    ]);
    http_response_code(409);
    echo json_encode([
        'ok' => false,
        'message' => $exception->getMessage(),
        'conflict' => true,
    ]);
} catch (DocumentSyncReauthRequiredException $exception) {
    document_action_idempotency_mark_failed($pdo, $actorUserId, $operationId, $exception->getMessage(), 428);
    offline_sync_log_event($pdo, [
        'event_type' => 'SYNC_REAUTH_REQUIRED',
        'route_kind' => 'document_action',
        'action_name' => $action,
        'operation_id' => $operationId,
        'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'source' => 'server-document-action',
        'http_status' => 428,
        'message' => $exception->getMessage(),
        'payload' => [
            'is_outbox_sync' => $isOutboxSyncRequest,
        ],
    ]);
    http_response_code(428);
    echo json_encode([
        'ok' => false,
        'message' => $exception->getMessage(),
        'reauth_required' => true,
    ]);
} catch (InvalidArgumentException $exception) {
    document_action_idempotency_mark_failed($pdo, $actorUserId, $operationId, $exception->getMessage(), 422);
    offline_sync_log_event($pdo, [
        'event_type' => 'ACTION_VALIDATION_FAILED',
        'route_kind' => 'document_action',
        'action_name' => $action,
        'operation_id' => $operationId,
        'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'source' => 'server-document-action',
        'http_status' => 422,
        'message' => $exception->getMessage(),
        'payload' => [
            'is_outbox_sync' => $isOutboxSyncRequest,
        ],
    ]);
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
} catch (RuntimeException $exception) {
    document_action_idempotency_mark_failed($pdo, $actorUserId, $operationId, $exception->getMessage(), 403);
    offline_sync_log_event($pdo, [
        'event_type' => 'ACTION_FORBIDDEN',
        'route_kind' => 'document_action',
        'action_name' => $action,
        'operation_id' => $operationId,
        'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'source' => 'server-document-action',
        'http_status' => 403,
        'message' => $exception->getMessage(),
        'payload' => [
            'is_outbox_sync' => $isOutboxSyncRequest,
        ],
    ]);
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
} catch (Throwable $exception) {
    document_action_idempotency_mark_failed($pdo, $actorUserId, $operationId, 'Unable to process action right now.', 500);
    offline_sync_log_event($pdo, [
        'event_type' => 'ACTION_SERVER_ERROR',
        'route_kind' => 'document_action',
        'action_name' => $action,
        'operation_id' => $operationId,
        'request_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'source' => 'server-document-action',
        'http_status' => 500,
        'message' => 'Unable to process action right now.',
        'payload' => [
            'is_outbox_sync' => $isOutboxSyncRequest,
        ],
    ]);
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to process action right now.']);
}
