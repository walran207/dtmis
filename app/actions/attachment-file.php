<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/attachment-backups.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Authentication required.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Method not allowed.';
    exit;
}

$attachmentId = (int)($_GET['attachment_id'] ?? 0);
if ($attachmentId <= 0) {
    http_response_code(422);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Attachment ID is required.';
    exit;
}

function resolve_attachment_absolute_path(string $rawPath): ?string
{
    return app_resolve_attachment_absolute_path($rawPath);
}

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare(
        'SELECT 
            da.file_name, 
            da.file_path,
            d.id AS document_id,
            d.originating_office_id,
            d.current_office_id,
            d.pending_office_id,
            d.created_by_user_id
         FROM document_attachments da
         INNER JOIN documents d ON d.id = da.document_id
         WHERE da.id = :id'
    );
    $stmt->execute(['id' => $attachmentId]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$attachment) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Attachment not found.';
        exit;
    }

    // Authorization Check
    $userOfficeId = (int)($_SESSION['office_id'] ?? 0);
    $userRole = strtoupper(trim((string)($_SESSION['role_name'] ?? '')));
    $isSuperAdmin = $userRole === 'SUPER-ADMIN' || $userRole === 'SUPER_ADMIN';

    $docId = (int)($attachment['document_id'] ?? 0);
    $docOriginOfficeId = (int)($attachment['originating_office_id'] ?? 0);
    $docCurrentOfficeId = (int)($attachment['current_office_id'] ?? 0);
    $docPendingOfficeId = (int)($attachment['pending_office_id'] ?? 0);
    $docCreatedById = (int)($attachment['created_by_user_id'] ?? 0);

    $isAuthorized = $isSuperAdmin
        || $docCreatedById === (int)$_SESSION['user_id']
        || ($userOfficeId > 0 && (
            $userOfficeId === $docOriginOfficeId ||
            $userOfficeId === $docCurrentOfficeId ||
            $userOfficeId === $docPendingOfficeId
        ));

    if (!$isAuthorized && $docId > 0 && $userOfficeId > 0) {
        $historyStmt = $pdo->prepare(
            'SELECT 1 FROM activity_logs 
             WHERE document_id = :doc_id 
               AND (source_office_id = :office_id OR destination_office_id = :office_id)'
        );
        $historyStmt->execute(['doc_id' => $docId, 'office_id' => $userOfficeId]);
        if ($historyStmt->fetch()) {
            $isAuthorized = true;
        }
    }

    if (!$isAuthorized) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'You are not authorized to access this attachment.';
        exit;
    }

    $fileName = trim((string)($attachment['file_name'] ?? 'attachment'));
    $filePath = (string)($attachment['file_path'] ?? '');
    $absolutePath = resolve_attachment_absolute_path($filePath);
    $isImageAttachment = attachment_binary_backup_is_image_filename($fileName !== '' ? $fileName : $filePath);
    if ($absolutePath !== null && $isImageAttachment && !attachment_binary_backup_is_valid_image_file($absolutePath)) {
        $absolutePath = null;
    }

    if ($absolutePath !== null) {
        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $absolutePath);
                if (is_string($detected) && trim($detected) !== '') {
                    $mime = $detected;
                }
                finfo_close($finfo);
            }
        }

        if ($fileName === '') {
            $fileName = basename($absolutePath);
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($absolutePath));
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $fileName) . '"');
        header('Cache-Control: private, max-age=300');

        readfile($absolutePath);
        exit;
    }

    $backup = attachment_binary_backup_fetch($pdo, $attachmentId);
    if (!is_array($backup)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Attachment file not found.';
        exit;
    }

    $backupContent = (string)($backup['binary_content'] ?? '');
    if ($backupContent === '') {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Attachment backup not found.';
        exit;
    }

    $backupMime = trim((string)($backup['mime_type'] ?? ''));
    if ($backupMime === '') {
        $backupMime = 'application/octet-stream';
    }

    $backupName = trim((string)($backup['file_name'] ?? ''));
    if ($backupName !== '') {
        $fileName = $backupName;
    } elseif ($fileName === '') {
        $fileName = 'attachment';
    }

    header('Content-Type: ' . $backupMime);
    header('Content-Length: ' . (string)strlen($backupContent));
    header('Content-Disposition: inline; filename="' . str_replace('"', '', $fileName) . '"');
    header('Cache-Control: private, max-age=300');

    echo $backupContent;
    exit;
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to load attachment.';
}
