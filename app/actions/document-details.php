<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

function format_dt_label(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '-';
    }
    try {
        return (new DateTimeImmutable($value))->format('M d, Y h:i A');
    } catch (Throwable $exception) {
        return (string)$value;
    }
}

function attachment_public_url(?string $filePath): ?string
{
    if ($filePath === null || trim($filePath) === '') {
        return null;
    }

    $path = str_replace('\\', '/', trim($filePath));
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    if (str_starts_with($path, '/')) {
        return $path;
    }

    return app_url(ltrim($path, '/'));
}

function attachment_preview_type(?string $fileName, ?string $filePath): string
{
    $candidate = trim((string)$fileName);
    if ($candidate === '') {
        $candidate = trim((string)$filePath);
    }
    if ($candidate === '') {
        return 'none';
    }

    $extension = strtolower((string)pathinfo($candidate, PATHINFO_EXTENSION));
    if ($extension === 'pdf') {
        return 'pdf';
    }
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
        return 'image';
    }
    return 'none';
}

function document_details_column_exists(PDO $pdo, string $table, string $column): bool
{
    $safeTable = str_replace('`', '', trim($table));
    $safeColumn = str_replace(['\\', "'"], ['\\\\', "\\'"], trim($column));
    if ($safeTable === '' || $safeColumn === '') {
        return false;
    }

    $sql = "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'";
    $stmt = $pdo->query($sql);
    return $stmt ? (bool)$stmt->fetch() : false;
}

function document_details_arta_category_expr(PDO $pdo): string
{
    if (document_details_column_exists($pdo, 'documents', 'arta_category_override')) {
        return "COALESCE(NULLIF(TRIM(d.arta_category_override), ''), dt.category)";
    }

    return 'dt.category';
}

function document_details_arta_days_expr(PDO $pdo): string
{
    if (document_details_column_exists($pdo, 'documents', 'arta_days_limit_override')) {
        return 'COALESCE(d.arta_days_limit_override, dt.arta_days_limit)';
    }

    return 'dt.arta_days_limit';
}

function document_details_row_version_expr(PDO $pdo): string
{
    if (document_details_column_exists($pdo, 'documents', 'row_version')) {
        return 'COALESCE(d.row_version, 1)';
    }

    return '1';
}

$documentId = (int)($_GET['document_id'] ?? 0);
$trackingId = strtoupper(trim((string)($_GET['tracking_id'] ?? '')));

if ($documentId <= 0 && $trackingId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Document reference is required.']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $artaCategoryExpr = document_details_arta_category_expr($pdo);
    $artaDaysExpr = document_details_arta_days_expr($pdo);
    $rowVersionExpr = document_details_row_version_expr($pdo);

    if ($documentId > 0) {
        $documentStmt = $pdo->prepare(
            'SELECT
                d.id,
                d.tracking_id,
                d.subject,
                d.status,
                d.source_type,
                d.external_client_name,
                d.document_type_id,
                d.created_at,
                d.originating_office_id,
                d.current_office_id,
                d.pending_office_id,
                d.created_by_user_id,
                ' . $rowVersionExpr . ' AS row_version,
                dt.name AS document_type,
                ' . $artaCategoryExpr . ' AS arta_category,
                ' . $artaDaysExpr . ' AS arta_days_limit,
                origin.name AS originating_office,
                current_office.name AS current_office,
                pending_office.name AS pending_office,
                CONCAT(COALESCE(created_by.first_name, \'\'), \' \', COALESCE(created_by.last_name, \'\')) AS created_by_name
             FROM documents d
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             LEFT JOIN offices origin ON origin.id = d.originating_office_id
             LEFT JOIN offices current_office ON current_office.id = d.current_office_id
             LEFT JOIN offices pending_office ON pending_office.id = d.pending_office_id
             LEFT JOIN users created_by ON created_by.id = d.created_by_user_id
             WHERE d.id = :document_id
             LIMIT 1'
        );
        $documentStmt->execute(['document_id' => $documentId]);
    } else {
        $documentStmt = $pdo->prepare(
            'SELECT
                d.id,
                d.tracking_id,
                d.subject,
                d.status,
                d.source_type,
                d.external_client_name,
                d.document_type_id,
                d.created_at,
                d.originating_office_id,
                d.current_office_id,
                d.pending_office_id,
                d.created_by_user_id,
                ' . $rowVersionExpr . ' AS row_version,
                dt.name AS document_type,
                ' . $artaCategoryExpr . ' AS arta_category,
                ' . $artaDaysExpr . ' AS arta_days_limit,
                origin.name AS originating_office,
                current_office.name AS current_office,
                pending_office.name AS pending_office,
                CONCAT(COALESCE(created_by.first_name, \'\'), \' \', COALESCE(created_by.last_name, \'\')) AS created_by_name
             FROM documents d
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             LEFT JOIN offices origin ON origin.id = d.originating_office_id
             LEFT JOIN offices current_office ON current_office.id = d.current_office_id
             LEFT JOIN offices pending_office ON pending_office.id = d.pending_office_id
             LEFT JOIN users created_by ON created_by.id = d.created_by_user_id
             WHERE UPPER(d.tracking_id) = :tracking_id
             LIMIT 1'
        );
        $documentStmt->execute(['tracking_id' => $trackingId]);
    }

    $document = $documentStmt->fetch();
    if (!$document) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Document not found.']);
        exit;
    }

    $resolvedDocumentId = (int)($document['id'] ?? 0);
    $userOfficeId = (int)($_SESSION['office_id'] ?? 0);
    $userRole = strtoupper(trim((string)($_SESSION['role_name'] ?? '')));
    $isSuperAdmin = $userRole === 'SUPER-ADMIN' || $userRole === 'SUPER_ADMIN';

    $docOriginOfficeId = (int)($document['originating_office_id'] ?? 0);
    $docCurrentOfficeId = (int)($document['current_office_id'] ?? 0);
    $docPendingOfficeId = (int)($document['pending_office_id'] ?? 0);
    $docCreatedById = (int)($document['created_by_user_id'] ?? 0);

    $isAuthorized = $isSuperAdmin
        || $docCreatedById === (int)$_SESSION['user_id']
        || ($userOfficeId > 0 && (
            $userOfficeId === $docOriginOfficeId ||
            $userOfficeId === $docCurrentOfficeId ||
            $userOfficeId === $docPendingOfficeId
        ));

    // Fallback: Check activity logs if they ever handled it (history)
    if (!$isAuthorized && $resolvedDocumentId > 0 && $userOfficeId > 0) {
        $historyStmt = $pdo->prepare(
            'SELECT 1 FROM activity_logs 
             WHERE document_id = :doc_id 
               AND (source_office_id = :office_id OR destination_office_id = :office_id) 
             LIMIT 1'
        );
        $historyStmt->execute(['doc_id' => $resolvedDocumentId, 'office_id' => $userOfficeId]);
        if ($historyStmt->fetch()) {
            $isAuthorized = true;
        }
    }

    if (!$isAuthorized) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'You are not authorized to view this document.']);
        exit;
    }

    $lastActivityStmt = $pdo->prepare(
        'SELECT
            al.action_type,
            al.remarks,
            al.created_at,
            CONCAT(COALESCE(actor.first_name, \'\'), \' \', COALESCE(actor.last_name, \'\')) AS actor_name
         FROM activity_logs al
         LEFT JOIN users actor ON actor.id = al.user_id
         WHERE al.document_id = :document_id
         ORDER BY al.created_at DESC, al.id DESC
         LIMIT 1'
    );
    $lastActivityStmt->execute(['document_id' => $resolvedDocumentId]);
    $lastActivity = $lastActivityStmt->fetch() ?: [];

    $attachments = [];
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'document_attachments'");
    $attachmentsTableExists = $tableCheck !== false && $tableCheck->fetch() !== false;

    if ($attachmentsTableExists) {
        $attachmentStmt = $pdo->prepare(
            'SELECT
                da.id,
                da.file_name,
                da.file_path,
                da.version_number,
                da.is_internal_only,
                da.uploaded_by AS uploaded_by_user_id,
                da.uploaded_at,
                CONCAT(COALESCE(u.first_name, \'\'), \' \', COALESCE(u.last_name, \'\')) AS uploaded_by_name,
                r_u.name AS uploaded_by_role
             FROM document_attachments da
             LEFT JOIN users u ON u.id = da.uploaded_by
             LEFT JOIN roles r_u ON r_u.id = u.role_id
             WHERE da.document_id = :document_id
             ORDER BY da.version_number DESC, da.uploaded_at DESC, da.id DESC'
        );
        $attachmentStmt->execute(['document_id' => $resolvedDocumentId]);
        $attachmentRows = $attachmentStmt->fetchAll() ?: [];

        foreach ($attachmentRows as $row) {
            $attachmentId = (int)($row['id'] ?? 0);
            $filePath = (string)($row['file_path'] ?? '');
            $fileUrl = $attachmentId > 0
                ? app_url('actions/attachment-file.php?attachment_id=' . $attachmentId)
                : (attachment_public_url($filePath) ?? '');
            $attachments[] = [
                'id' => $attachmentId,
                'file_name' => trim((string)($row['file_name'] ?? '')) !== '' ? (string)$row['file_name'] : 'Attachment',
                'file_path' => $filePath,
                'file_url' => $fileUrl,
                'version_number' => (int)($row['version_number'] ?? 1),
                'is_internal_only' => (int)($row['is_internal_only'] ?? 0) === 1,
                'uploaded_by_user_id' => (int)($row['uploaded_by_user_id'] ?? 0),
                'can_delete' => (int)($row['uploaded_by_user_id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0),
                'uploaded_at' => format_dt_label((string)($row['uploaded_at'] ?? null)),
                'uploaded_by' => trim((string)($row['uploaded_by_name'] ?? '')) !== '' ? (string)$row['uploaded_by_name'] : 'Unknown',
                'uploaded_by_role' => trim((string)($row['uploaded_by_role'] ?? '')),
                'uploaded_by_role_key' => app_normalize_role_key((string)($row['uploaded_by_role'] ?? '')),
                'is_prepared_response' => in_array(
                    app_normalize_role_key((string)($row['uploaded_by_role'] ?? '')),
                    ['DIVISION_CHIEF', 'SECTION_STAFF'],
                    true
                ),
                'preview_type' => attachment_preview_type((string)($row['file_name'] ?? ''), $filePath),
            ];
        }
    }

    $createdBy = trim((string)($document['created_by_name'] ?? ''));
    if ($createdBy === '') {
        $createdBy = '-';
    }

    $subject = trim((string)($document['subject'] ?? ''));
    if ($subject === '') {
        $subject = 'No subject';
    }

    $sourceType = strtoupper(trim((string)($document['source_type'] ?? 'INTERNAL')));
    if ($sourceType === 'EXTERNAL') {
        $externalClient = trim((string)($document['external_client_name'] ?? ''));
        if ($externalClient !== '') {
            $sourceType .= ' (' . $externalClient . ')';
        }
    }

    echo json_encode([
        'ok' => true,
        'document' => [
            'id' => $resolvedDocumentId,
            'tracking_id' => (string)($document['tracking_id'] ?? '-'),
            'subject' => $subject,
            'status' => trim((string)($document['status'] ?? '')) !== '' ? (string)$document['status'] : '-',
            'source_type' => $sourceType,
            'source_type_raw' => strtoupper(trim((string)($document['source_type'] ?? 'INTERNAL'))),
            'external_client_name' => trim((string)($document['external_client_name'] ?? '')),
            'document_type_id' => (int)($document['document_type_id'] ?? 0),
            'document_type' => trim((string)($document['document_type'] ?? '')) !== '' ? (string)$document['document_type'] : '-',
            'arta_category_raw' => trim((string)($document['arta_category'] ?? '')),
            'arta_category' => trim((string)($document['arta_category'] ?? '')) !== '' ? (string)$document['arta_category'] : '-',
            'arta_days_limit' => (int)($document['arta_days_limit'] ?? 0),
            'originating_office' => trim((string)($document['originating_office'] ?? '')) !== '' ? (string)$document['originating_office'] : '-',
            'current_office' => trim((string)($document['current_office'] ?? '')) !== '' ? (string)$document['current_office'] : '-',
            'pending_office' => trim((string)($document['pending_office'] ?? '')) !== '' ? (string)$document['pending_office'] : '-',
            'created_by' => $createdBy,
            'created_at' => format_dt_label((string)($document['created_at'] ?? null)),
            'last_action' => trim((string)($lastActivity['action_type'] ?? '')) !== '' ? (string)$lastActivity['action_type'] : '-',
            'last_action_at' => format_dt_label((string)($lastActivity['created_at'] ?? null)),
            'last_remarks' => trim((string)($lastActivity['remarks'] ?? '')) !== '' ? (string)$lastActivity['remarks'] : '-',
            'last_actor' => trim((string)($lastActivity['actor_name'] ?? '')) !== '' ? (string)$lastActivity['actor_name'] : '-',
            'attachment_count' => count($attachments),
            'row_version' => (int)($document['row_version'] ?? 1),
        ],
        'attachments' => $attachments,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to load document details right now.']);
}
