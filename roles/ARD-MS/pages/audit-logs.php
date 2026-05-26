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
$actionTypes = ['Approved', 'Forwarded', 'Returned', 'Rerouted', 'Released'];

try {
    $pdo = getDatabaseConnection();
    $actedRows = dashboard_fetch_actor_tracker_rows($pdo, $userId, $actionTypes, 120);
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
    $rowStatus = strtolower(trim((string)($row['status'] ?? '')));
    if (str_contains($rowStatus, 'completed')) {
        continue;
    }

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
            (string)($row['sender'] ?? '-'),
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
            'current_office_id' => (string)((int)($row['current_office_id'] ?? 0)),
            'pending_office_id' => (string)((int)($row['pending_office_id'] ?? 0)),
            'current_office_level' => (string)($row['current_office_level'] ?? ''),
            'pending_office_level' => (string)($row['pending_office_level'] ?? ''),
            'current_holder' => (string)($row['current_holder'] ?? ''),
            'has_section_receive' => (int)($row['has_section_receive'] ?? 0) > 0 ? '1' : '0',
        ],
    ];
}

$roleName = 'ARD_MS';
$initialsFallback = 'AM';
$pageTitle = 'Audit Logs | DENR Region XII DTMIS';
$activeMenu = 'audit_logs';
$brandSubtitle = 'ARD MS Portal';
$pageHeading = 'ARD MS Action Tracker (Live)';
$pageSubtitle = 'Documents you approved/forwarded and their current routing position.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=approved&actions=' . rawurlencode(implode(',', $actionTypes)));

$kpiCards = [
    ['label' => 'Approved by Me', 'icon' => 'blue', 'value' => (string)$approvedCount],
    ['label' => 'Forwarded / Rerouted', 'icon' => 'violet', 'value' => (string)$forwardedCount],
    ['label' => 'Still In Progress', 'icon' => 'orange', 'value' => (string)$activeCount],
    ['label' => 'Total Actioned', 'icon' => 'green', 'value' => (string)count($actedRows)],
];

$panels = [
    [
        'title' => 'Chief Tracker Snapshot',
        'rows' => [
            ['label' => 'Approved', 'value' => (string)$approvedCount, 'width' => '34%'],
            ['label' => 'Forwarded / Rerouted', 'value' => (string)$forwardedCount, 'width' => '33%'],
            ['label' => 'In Progress', 'value' => (string)$activeCount, 'width' => '33%'],
        ],
    ],
];

$kpiCards = [];
$panels = [];

$tableTitle = 'ARD MS Action Live Tracker';
$tableColumns = ['Tracking ID', 'Sender', 'Subject', 'Document Type (+ ARTA)', 'Current Holder', 'Last Action Time', 'Last Action by Me', 'Status', 'Quick Actions'];
$pageActions = ['View Tracking Slip', 'Print Package', 'Live Routing Tracker'];
$stickyActions = [];
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$dateFilterPlacement = 'table_card';
$showLiveFlowTracker = true;

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
