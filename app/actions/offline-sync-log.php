<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/offline-sync.php';

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

if (!app_offline_sync_logging_enabled()) {
    echo json_encode(['ok' => true, 'logged' => false, 'disabled' => true]);
    exit;
}

$rawBody = file_get_contents('php://input');
$decoded = is_string($rawBody) && trim($rawBody) !== '' ? json_decode($rawBody, true) : null;
$payload = is_array($decoded) ? $decoded : $_POST;

if (!is_array($payload)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid payload.']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $logged = offline_sync_log_event($pdo, [
        'event_type' => (string)($payload['event_type'] ?? ''),
        'route_kind' => (string)($payload['route_kind'] ?? ''),
        'action_name' => (string)($payload['action_name'] ?? ''),
        'operation_id' => (string)($payload['operation_id'] ?? ''),
        'request_url' => (string)($payload['request_url'] ?? ''),
        'http_status' => (int)($payload['http_status'] ?? 0),
        'attempt_count' => (int)($payload['attempt_count'] ?? 0),
        'queue_pending' => (int)($payload['queue_pending'] ?? 0),
        'queue_failed' => (int)($payload['queue_failed'] ?? 0),
        'queue_blocked' => (int)($payload['queue_blocked'] ?? 0),
        'source' => (string)($payload['source'] ?? 'client'),
        'message' => (string)($payload['message'] ?? ''),
        'payload' => is_array($payload['payload'] ?? null) ? $payload['payload'] : null,
    ]);

    echo json_encode(['ok' => true, 'logged' => $logged]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to write sync log right now.']);
}
