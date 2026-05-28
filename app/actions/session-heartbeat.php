<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';

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

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$csrfToken = (string)($_POST['csrf_token'] ?? '');
if ($csrfToken === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

if (!app_touch_session_heartbeat()) {
    $message = function_exists('app_last_session_heartbeat_error')
        ? app_last_session_heartbeat_error()
        : 'Unable to update session heartbeat.';
    error_log('DTMIS session heartbeat failed: ' . $message);
    echo json_encode([
        'ok' => false,
        'message' => 'Session heartbeat skipped.',
        'reason' => $message,
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'server_time' => date('c'),
]);
