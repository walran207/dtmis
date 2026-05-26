<?php
declare(strict_types=1);

$roleBasePath = dirname(__DIR__);

require_once dirname(__DIR__, 3) . '/config/app.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/config/dashboard-data.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$actedRows = [];
$actionTypes = ['Forwarded', 'Rerouted', 'Returned', 'Approved'];

try {
    $pdo = getDatabaseConnection();
    $actedRows = dashboard_fetch_actor_tracker_rows($pdo, $userId, $actionTypes, 80);
} catch (Throwable $exception) {
    $actedRows = [];
}

$activeCount = 0;
$completedCount = 0;
$forwardedCount = 0;
foreach ($actedRows as $row) {
    $status = strtolower(trim((string)($row['status'] ?? '')));
    $actionType = strtolower(trim((string)($row['actor_action_type'] ?? '')));
    if (str_contains($actionType, 'forward')) {
        $forwardedCount++;
    }
    if (str_contains($status, 'released') || str_contains($status, 'completed') || str_contains($status, 'signed')) {
        $completedCount++;
    } else {
        $activeCount++;
    }
}

$tableRows = [];
foreach ($actedRows as $row) {
    $actionTypeLabel = trim((string)($row['actor_action_type'] ?? ''));
    $actionRemarks = trim((string)($row['actor_action_remarks'] ?? ''));
    if ($actionTypeLabel === '') {
        $actionTypeLabel = '-';
    } elseif ($actionRemarks !== '' && strcasecmp($actionRemarks, $actionTypeLabel) !== 0) {
        $normalizedRemarks = preg_replace('/\s+/', ' ', $actionRemarks);
        if ($normalizedRemarks !== null && $normalizedRemarks !== '') {
            if (strlen($normalizedRemarks) > 64) {
                $normalizedRemarks = substr($normalizedRemarks, 0, 61) . '...';
            }
            $actionTypeLabel .= ' | ' . $normalizedRemarks;
        }
    }
    $tableRows[] = [
        'value' => [
            (string)($row['tracking_id'] ?? '-'),
            (string)($row['subject'] ?? '-'),
            (string)($row['document_type'] ?? '-'),
            (string)($row['current_holder'] ?? '-'),
            (string)($row['date_received'] ?? '-'),
            $actionTypeLabel,
            (string)($row['status'] ?? '-'),
            'View Tracking Slip | Print Package',
        ],
        'meta' => [
            'document_id' => (string)($row['document_id'] ?? 0),
            'tracking_id' => (string)($row['tracking_id'] ?? ''),
            'date_received_raw' => (string)($row['date_received_raw'] ?? ''),
            'date_created_raw' => (string)($row['date_created_raw'] ?? ''),
            'status_raw' => (string)($row['status'] ?? ''),
            'origin_office_id' => (string)($row['origin_office_id'] ?? 0),
        ],
    ];
}
$roleName = 'PENRO_SECTION';
$initialsFallback = 'SS';
$pageTitle = 'My Outbox | DENR Region XII DTMIS';
$activeMenu = 'cenro_unit_outbox';
$brandSubtitle = 'PENRO Section Portal';
$pageHeading = 'My Outbox (Live Action Tracker)';
$pageSubtitle = 'Live view of documents you acted on and where they are now.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=approved&actions=' . rawurlencode(implode(',', $actionTypes)));

$kpiCards = [
    ['label' => 'Actioned Docs (Active)', 'icon' => 'orange', 'value' => (string)$activeCount],
    ['label' => 'Forwarded by Me', 'icon' => 'violet', 'value' => (string)$forwardedCount],
    ['label' => 'Reached Final Status', 'icon' => 'green', 'value' => (string)$completedCount],
    ['label' => 'Total Actioned', 'icon' => 'blue', 'value' => (string)count($actedRows)],
];

$panels = [
    [
        'title' => 'My Live Action Snapshot',
        'rows' => [
            ['label' => 'Still In Progress', 'value' => (string)$activeCount, 'width' => '46%'],
            ['label' => 'Forwarded by Me', 'value' => (string)$forwardedCount, 'width' => '27%'],
            ['label' => 'Finalized', 'value' => (string)$completedCount, 'width' => '27%'],
        ],
    ],
];

$tableTitle = 'My Outbox Live Tracker';
$tableColumns = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Current Holder', 'Last Action Time', 'Last Action by Me', 'Status', 'Quick Actions'];
$pageActions = ['View Tracking Slip', 'Print Package', 'Live Action Monitor'];
$stickyActions = ['View Tracking Slip', 'Print Package', 'Search'];
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$dateFilterPlacement = 'table_card';

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
