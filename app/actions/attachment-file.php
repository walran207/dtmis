<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

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
    $trimmed = trim($rawPath);
    if ($trimmed === '') {
        return null;
    }

    $normalized = str_replace('\\', '/', $trimmed);
    if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
        return null;
    }

    $isWindowsAbsolute = (bool)preg_match('/^[A-Za-z]:\//', $normalized);
    $isUnixAbsolute = str_starts_with($normalized, '/');

    if ($isWindowsAbsolute || $isUnixAbsolute) {
        $candidate = $normalized;
    } else {
        $candidate = str_replace('\\', '/', __DIR__ . '/../' . ltrim($normalized, '/'));
    }

    $real = realpath($candidate);
    if ($real === false || !is_file($real) || !is_readable($real)) {
        return null;
    }

    return $real;
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
         WHERE da.id = :id
         LIMIT 1'
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
               AND (source_office_id = :office_id OR destination_office_id = :office_id) 
             LIMIT 1'
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

    $filePath = (string)($attachment['file_path'] ?? '');
    $absolutePath = resolve_attachment_absolute_path($filePath);
    if ($absolutePath === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Attachment file not found.';
        exit;
    }

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

    $fileName = trim((string)($attachment['file_name'] ?? 'attachment'));
    if ($fileName === '') {
        $fileName = basename($absolutePath);
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($absolutePath));
    header('Content-Disposition: inline; filename="' . str_replace('"', '', $fileName) . '"');
    header('Cache-Control: private, max-age=300');

    readfile($absolutePath);
    exit;
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to load attachment.';
}

