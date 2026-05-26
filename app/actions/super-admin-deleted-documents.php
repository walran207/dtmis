<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/super-admin.php';
require_once dirname(__DIR__, 2) . '/config/document-recycle-bin.php';

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

if (!super_admin_has_access()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Super Admin access required.']);
    exit;
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

try {
    $pdo = getDatabaseConnection();

    if ($method === 'GET') {
        echo json_encode([
            'ok' => true,
            'deleted_documents' => document_recycle_bin_fetch_deleted_documents($pdo, 400),
            'server_time' => date('c'),
        ]);
        exit;
    }

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if ($csrfToken === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token. Refresh and try again.']);
        exit;
    }

    $action = strtoupper(trim((string)($_POST['action'] ?? '')));
    if ($action !== 'RESTORE_DOCUMENT') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Unsupported action.']);
        exit;
    }

    $archiveId = (int)($_POST['archive_id'] ?? 0);
    if ($archiveId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'A valid recycle bin entry is required.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $restored = document_recycle_bin_restore_document($pdo, $archiveId, (int)$_SESSION['user_id']);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Document restored successfully: ' . trim((string)($restored['tracking_id'] ?? '')),
        'deleted_documents' => document_recycle_bin_fetch_deleted_documents($pdo, 400),
        'restored_document' => $restored,
        'server_time' => date('c'),
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => trim($exception->getMessage()) !== ''
            ? (string)$exception->getMessage()
            : 'Unable to process recycle bin request right now.',
    ]);
}
