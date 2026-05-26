<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/dashboard-data.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'message' => 'Authentication required.',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Method not allowed.',
    ]);
    exit;
}

$officeId = (int)($_SESSION['office_id'] ?? 0);
$roleName = (string)($_SESSION['role_name'] ?? '');
$userId = (int)($_SESSION['user_id'] ?? 0);
$limit = (int)($_GET['limit'] ?? 12);
$limit = max(3, min($limit, 30));

if ($officeId <= 0) {
    echo json_encode([
        'ok' => true,
        'notifications' => [],
        'newest_id' => 0,
        'server_time' => date('c'),
    ]);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $notifications = dashboard_fetch_notifications($pdo, $officeId, $limit, $roleName, $userId);

    $newestId = 0;
    foreach ($notifications as $notification) {
        $newestId = max($newestId, (int)($notification['id'] ?? 0));
    }

    echo json_encode([
        'ok' => true,
        'notifications' => $notifications,
        'newest_id' => $newestId,
        'server_time' => date('c'),
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to load notifications right now.',
    ]);
}
