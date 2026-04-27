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

$ardOfficeId = (int)($_SESSION['office_id'] ?? 0);
$actedRows = [];
$actionTypes = ['Approved', 'Forwarded', 'Returned', 'Rerouted'];

try {
    $pdo = getDatabaseConnection();
    $actedRows = dashboard_fetch_ard_division_tracker_rows($pdo, $ardOfficeId, $actionTypes, 100);
} catch (Throwable $exception) {
    $actedRows = [];
}

$approvedCount = 0;
$forwardedCount = 0;
$activeCount = 0;
foreach ($actedRows as $row) {
    $actionType = strtolower(trim((string)($row['actor_action_type'] ?? '')));
    $status = strtolower(trim((string)($row['status'] ?? '')));
    if (str_contains($actionType, 'approve')) {
        $approvedCount++;
    }
    if (str_contains($actionType, 'forward') || str_contains($actionType, 'reroute')) {
        $forwardedCount++;
    }
    if (str_contains($status, 'released') || str_contains($status, 'completed') || str_contains($status, 'signed')) {
        continue;
    }
    $activeCount++;
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

$roleName = 'ARD_TS';
$initialsFallback = 'AT';
$pageTitle = 'Unit Monitor (With Division) | DENR Region XII eDATS';
$activeMenu = 'unit_monitor_with_staff';
$brandSubtitle = 'ARD TS Portal';
$pageHeading = 'Unit Monitor (With Division) - Live Action Tracker';
$pageSubtitle = 'Tracks documents handled by divisions under ARD TS in real time.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=ard_division&actions=' . rawurlencode(implode(',', $actionTypes)));

$kpiCards = [
    ['label' => 'Approved (Division)', 'icon' => 'green', 'value' => (string)$approvedCount],
    ['label' => 'Forwarded/Rerouted', 'icon' => 'blue', 'value' => (string)$forwardedCount],
    ['label' => 'Still In Progress', 'icon' => 'orange', 'value' => (string)$activeCount],
    ['label' => 'Total Actioned', 'icon' => 'violet', 'value' => (string)count($actedRows)],
];

$panels = [
    [
        'title' => 'Division Action Snapshot',
        'rows' => [
            ['label' => 'Approved', 'value' => (string)$approvedCount, 'width' => '34%'],
            ['label' => 'Forwarded / Rerouted', 'value' => (string)$forwardedCount, 'width' => '33%'],
            ['label' => 'In Progress', 'value' => (string)$activeCount, 'width' => '33%'],
        ],
    ],
];

$tableTitle = 'Division Routing Live Tracker';
$tableColumns = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Current Holder', 'Last Action Time', 'Last Action by Division', 'Status', 'Quick Actions'];
$pageActions = ['View Tracking Slip', 'Print Package', 'Live Approval Tracker'];
$stickyActions = ['View Tracking Slip', 'Print Package', 'Search'];
$queueControlsPlacement = 'table_card';
$statusFilterOptions = [
    ['value' => '', 'label' => 'All Status Categories'],
    ['value' => 'received', 'label' => 'Received'],
    ['value' => 'approved', 'label' => 'Approved'],
    ['value' => 'forward', 'label' => 'Forwarded'],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
