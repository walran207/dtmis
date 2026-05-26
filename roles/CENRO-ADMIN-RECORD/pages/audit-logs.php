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
$officeId = (int)($_SESSION['office_id'] ?? 0);
$actedRows = [];
$actionTypes = ['Received', 'Approved', 'Forwarded', 'Released', 'Returned'];
$metrics = [
    'pending_total' => 0,
    'pending_approval_total' => 0,
    'pending_forward_total' => 0,
    'completed_total' => 0,
];

try {
    $pdo = getDatabaseConnection();
    $actedRows = dashboard_fetch_actor_tracker_rows($pdo, $userId, $actionTypes, 120);
    if ($officeId > 0) {
        $metrics = array_merge($metrics, dashboard_fetch_role_metrics($pdo, $officeId));
    }
} catch (Throwable $exception) {
    $actedRows = [];
}

$releasedCount = 0;
$approvedCount = 0;
$activeCount = 0;
foreach ($actedRows as $row) {
    $actionType = strtolower(trim((string)($row['actor_action_type'] ?? '')));
    $status = strtolower(trim((string)($row['status'] ?? '')));
    if (str_contains($actionType, 'release')) {
        $releasedCount++;
    }
    if (str_contains($actionType, 'approve')) {
        $approvedCount++;
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
            (string)($row['sender'] ?? '-'),
            (string)($row['subject'] ?? '-'),
            (string)($row['document_type'] ?? '-'),
            (string)($row['current_holder'] ?? '-'),
            (string)($row['date_received'] ?? '-'),
            (string)($row['time_remaining'] ?? '-'),
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
$roleName = 'CENRO_ADMIN_RECORD';
$initialsFallback = 'PA';
$pageTitle = 'Audit Logs | DENR Region XII DTMIS';
$activeMenu = 'audit_logs';
$brandSubtitle = 'CENRO Admin Record Portal';
$pageHeading = 'CENRO Admin Record Audit Logs';
$pageSubtitle = 'Documents you received, approved, forwarded, and released with current routing status.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=acted&actions=' . rawurlencode(implode(',', $actionTypes)));

$kpiCards = [
    ['label' => 'Released by Me', 'icon' => 'green', 'value' => (string)$releasedCount],
    ['label' => 'Approved by Me', 'icon' => 'blue', 'value' => (string)$approvedCount],
    ['label' => 'Due Soon', 'icon' => 'orange', 'value' => (string)((int)($metrics['due_soon_total'] ?? 0))],
    ['label' => 'Still In Progress', 'icon' => 'orange', 'value' => (string)$activeCount],
    ['label' => 'Total Actioned', 'icon' => 'violet', 'value' => (string)count($actedRows)],
];

$panels = [
    [
        'title' => 'CENRO Admin Record Action Snapshot',
        'rows' => [
            ['label' => 'Released', 'value' => (string)$releasedCount, 'width' => '34%'],
            ['label' => 'Approved', 'value' => (string)$approvedCount, 'width' => '33%'],
            ['label' => 'In Progress', 'value' => (string)$activeCount, 'width' => '33%'],
        ],
    ],
    [
        'title' => 'Live Tracking Diagram (Concern Docs)',
        'rows' => [
            ['label' => 'Pending Receive', 'value' => (string)((int)($metrics['pending_total'] ?? 0)), 'width' => '25%'],
            ['label' => 'Pending Approval', 'value' => (string)((int)($metrics['pending_approval_total'] ?? 0)), 'width' => '25%'],
            ['label' => 'Pending Forward', 'value' => (string)((int)($metrics['pending_forward_total'] ?? 0)), 'width' => '25%'],
            ['label' => 'Completed', 'value' => (string)((int)($metrics['completed_total'] ?? 0)), 'width' => '25%'],
        ],
        'chips' => [
            'CENRO Admin Record -> CENRO Officer -> CENRO Section',
            'CENRO Section -> CENRO Unit -> CENRO Section -> CENRO Admin Record',
            'CENRO Admin Record -> Complete or Forward to PENRO/PACDO',
        ],
    ],
];

$kpiCards = [];
$panels = [];

$tableTitle = 'CENRO Admin Record Action Tracker';
$tableColumns = ['Tracking ID', 'Sender', 'Subject', 'Document Type (+ ARTA)', 'Current Holder', 'Last Action Time', 'Time Remaining', 'Last Action by Me', 'Status', 'Quick Actions'];
$pageActions = ['View Tracking Slip', 'Print Package', 'Live Audit Tracker'];
$stickyActions = ['View Tracking Slip', 'Print Package', 'Search'];
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$dateFilterPlacement = 'table_card';
$showLiveFlowTracker = true;

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';


