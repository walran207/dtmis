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
    $actedRows = dashboard_fetch_actor_tracker_rows($pdo, $userId, $actionTypes, 120);
} catch (Throwable $exception) {
    $actedRows = [];
}

$activeCount = 0;
$completedCount = 0;
$forwardedCount = 0;
foreach ($actedRows as $row) {
    $status = strtolower(trim((string)($row['status'] ?? '')));
    $actionType = strtolower(trim((string)($row['actor_action_type'] ?? '')));
    if (str_contains($actionType, 'forward') || str_contains($actionType, 'reroute')) {
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
            'current_office_id' => (string)((int)($row['current_office_id'] ?? 0)),
            'pending_office_id' => (string)((int)($row['pending_office_id'] ?? 0)),
            'current_office_level' => (string)($row['current_office_level'] ?? ''),
            'pending_office_level' => (string)($row['pending_office_level'] ?? ''),
            'current_holder' => (string)($row['current_holder'] ?? ''),
            'has_section_receive' => (int)($row['has_section_receive'] ?? 0) > 0 ? '1' : '0',
        ],
    ];
}

$roleName = 'PENRO_SECTION';
$initialsFallback = 'SS';
$pageTitle = 'Search Documents | DENR Region XII eDATS';
$activeMenu = 'search';
$brandSubtitle = 'PENRO Section Portal';
$pageHeading = 'Search Documents';
$pageSubtitle = 'Search your finished PENRO Section transactions and review how many documents you forwarded.';
$searchPlaceholder = 'Search Tracking ID, subject, status, or current holder';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=approved&actions=' . rawurlencode(implode(',', $actionTypes)));

$kpiCards = [
    ['label' => 'Actioned Docs (Active)', 'icon' => 'orange', 'value' => (string)$activeCount],
    ['label' => 'Forwarded by Me', 'icon' => 'violet', 'value' => (string)$forwardedCount],
    ['label' => 'Reached Final Status', 'icon' => 'green', 'value' => (string)$completedCount],
    ['label' => 'Total Actioned', 'icon' => 'blue', 'value' => (string)count($actedRows)],
];

$panels = [
    [
        'title' => 'Finished Transaction Snapshot',
        'rows' => [
            ['label' => 'Still In Progress', 'value' => (string)$activeCount, 'width' => '46%'],
            ['label' => 'Forwarded by Me', 'value' => (string)$forwardedCount, 'width' => '27%'],
            ['label' => 'Finalized', 'value' => (string)$completedCount, 'width' => '27%'],
        ],
    ],
];

$tableTitle = 'PENRO Section Finished Transaction Tracker';
$tableColumns = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Current Holder', 'Last Action Time', 'Last Action by Me', 'Status', 'Quick Actions'];
$pageActions = ['View Tracking Slip', 'Print Package', 'Search Transactions'];
$stickyActions = ['View Tracking Slip', 'Print Package', 'Search'];
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$dateFilterPlacement = 'table_card';
$showQrReceiveScanner = false;
$notifications = json_decode('[{"title":"Transaction tracker synced","timeLabel":"Now","message":"Finished PENRO Section transactions refreshed.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"A tracked document changed holder/state.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"Search reminder","timeLabel":"2h ago","message":"Use search and date range to review completed/forwarded work.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
