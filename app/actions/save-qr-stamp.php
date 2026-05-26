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
    echo json_encode(['ok' => false, 'message' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$csrfToken = (string)($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$documentId = (int)($_POST['document_id'] ?? 0);
$modeRaw = trim((string)($_POST['qr_print_mode'] ?? 'Grid-Snap'));
$xRaw = (float)($_POST['qr_x_coordinate'] ?? 50);
$yRaw = (float)($_POST['qr_y_coordinate'] ?? 50);

if ($documentId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Document ID is required.']);
    exit;
}

$modeUpper = strtoupper(str_replace(['_', ' '], '-', $modeRaw));
$mode = $modeUpper === 'FREE-DRAG' ? 'Free-Drag' : 'Grid-Snap';
$xCoordinate = max(0, min(100, round($xRaw, 2)));
$yCoordinate = max(0, min(100, round($yRaw, 2)));

try {
    $pdo = getDatabaseConnection();

    $docStmt = $pdo->prepare('SELECT TOP (1) id FROM documents WHERE id = :id');
    $docStmt->execute(['id' => $documentId]);
    if (!$docStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Document not found.']);
        exit;
    }

    $updates = [];
    $params = ['id' => $documentId];

    if (dashboard_column_exists($pdo, 'documents', 'qr_print_mode')) {
        $updates[] = 'qr_print_mode = :qr_print_mode';
        $params['qr_print_mode'] = $mode;
    }
    if (dashboard_column_exists($pdo, 'documents', 'qr_x_coordinate')) {
        $updates[] = 'qr_x_coordinate = :qr_x_coordinate';
        $params['qr_x_coordinate'] = $xCoordinate;
    }
    if (dashboard_column_exists($pdo, 'documents', 'qr_y_coordinate')) {
        $updates[] = 'qr_y_coordinate = :qr_y_coordinate';
        $params['qr_y_coordinate'] = $yCoordinate;
    }

    if (empty($updates)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'QR coordinate columns are missing in documents table.']);
        exit;
    }

    $sql = 'UPDATE documents SET ' . implode(', ', $updates) . ' WHERE id = :id';
    $updateStmt = $pdo->prepare($sql);
    $updateStmt->execute($params);

    echo json_encode([
        'ok' => true,
        'message' => 'QR placement saved.',
        'document_id' => $documentId,
        'qr_print_mode' => $mode,
        'qr_x_coordinate' => $xCoordinate,
        'qr_y_coordinate' => $yCoordinate,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to save QR placement right now.']);
}
