<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/super-admin.php';

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
            'overview' => super_admin_fetch_global_overview($pdo),
            'document_types' => super_admin_fetch_document_types($pdo, 600),
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
    if ($action !== 'UPDATE_DOCUMENT_TYPE') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Unsupported action.']);
        exit;
    }

    $documentTypeId = (int)($_POST['document_type_id'] ?? 0);
    $category = trim((string)($_POST['category'] ?? 'Simple'));
    $daysLimit = max(1, min(365, (int)($_POST['arta_days_limit'] ?? 3)));
    $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;

    if ($documentTypeId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'A valid document type is required.']);
        exit;
    }

    $allowedCategories = ['Simple', 'Complex', 'Highly Technical'];
    if (!in_array($category, $allowedCategories, true)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Invalid complexity category.']);
        exit;
    }

    $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM document_types WHERE id = :id');
    $existsStmt->execute(['id' => $documentTypeId]);
    if ((int)$existsStmt->fetchColumn() <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Document type not found.']);
        exit;
    }

    $hasIsActive = super_admin_column_exists($pdo, 'document_types', 'is_active');
    if ($hasIsActive) {
        $stmt = $pdo->prepare(
            'UPDATE document_types
             SET category = :category,
                 arta_days_limit = :arta_days_limit,
                 is_active = :is_active
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([
            'category' => $category,
            'arta_days_limit' => $daysLimit,
            'is_active' => $isActive,
            'id' => $documentTypeId,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'UPDATE document_types
             SET category = :category,
                 arta_days_limit = :arta_days_limit
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([
            'category' => $category,
            'arta_days_limit' => $daysLimit,
            'id' => $documentTypeId,
        ]);
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Document type updated successfully.',
        'overview' => super_admin_fetch_global_overview($pdo),
        'document_types' => super_admin_fetch_document_types($pdo, 600),
        'server_time' => date('c'),
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to update data right now.',
    ]);
}
