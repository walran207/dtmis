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

$officeId = (int)($_SESSION['office_id'] ?? 0);
$roleName = 'PASU';
$queueRows = [];
$metrics = [
    'pending_total' => 0,
    'pending_approval_total' => 0,
    'pending_forward_total' => 0,
    'released_total' => 0,
    'due_soon_total' => 0,
    'overdue_total' => 0,
    'created_today' => 0,
    'completed_week' => 0,
    'due_today_total' => 0,
];

try {
    $pdo = getDatabaseConnection();
    // Only docs routed TO this PASU office but not yet formally received
    $queueRows = dashboard_fetch_pending_receive_rows($pdo, $officeId, 80);
    $metrics = dashboard_fetch_role_metrics($pdo, $officeId);
} catch (Throwable $exception) {
    $queueRows = [];
}

$tableRows = [];
foreach ($queueRows as $row) {
    $tableRows[] = [
        'value' => [
            (string)($row['tracking_id'] ?? '-'),
            (string)($row['subject'] ?? '-'),
            (string)($row['document_type'] ?? '-'),
            (string)($row['current_holder'] ?? '-'),
            (string)($row['date_received'] ?? '-'),
            (string)($row['time_remaining'] ?? '-'),
            (string)($row['status'] ?? '-'),
            'View | Receive | Forward',
        ],
        'meta' => [
            'document_id' => (string)($row['document_id'] ?? 0),
            'tracking_id' => (string)($row['tracking_id'] ?? ''),
            'status_raw' => (string)($row['status'] ?? ''),
            'date_received_raw' => (string)($row['date_received_raw'] ?? ''),
            'date_created_raw' => (string)($row['date_created_raw'] ?? ''),
            'origin_office_id' => (string)((int)($row['origin_office_id'] ?? 0)),
            'current_office_id' => (string)((int)($row['current_office_id'] ?? 0)),
            'pending_office_id' => (string)((int)($row['pending_office_id'] ?? 0)),
        ],
    ];
}

$pendingCount = count($tableRows);
$pendingApprovalTotal = (int)($metrics['pending_approval_total'] ?? 0);
$pendingForwardTotal = (int)($metrics['pending_forward_total'] ?? 0);
$releasedTotal = (int)($metrics['released_total'] ?? 0);
$workflowBase = max(
    $pendingCount,
    $pendingApprovalTotal,
    $pendingForwardTotal,
    $releasedTotal,
    1
);

$pendingReceivedWidth = (string)(int)round(($pendingCount / $workflowBase) * 100) . '%';
$pendingApprovalWidth = (string)(int)round(($pendingApprovalTotal / $workflowBase) * 100) . '%';
$pendingForwardWidth = (string)(int)round(($pendingForwardTotal / $workflowBase) * 100) . '%';
$releasedWidth = (string)(int)round(($releasedTotal / $workflowBase) * 100) . '%';

$initialsFallback = 'PA';
$pageTitle = 'Pending Receive | DENR Region XII eDATS';
$activeMenu = 'inbox_pending_receive';
$brandSubtitle = 'PASU Portal';
$pageHeading = 'Pending Receive';
$pageSubtitle = 'Incoming documents routed to PASU awaiting formal receipt.';
$searchPlaceholder = 'Search Tracking ID or subject';
$queueControlsPlacement = 'table_card';
$statusFilterOptions = [
    ['value' => '', 'label' => 'All Incoming Docs'],
    ['value' => 'awaiting_receive', 'label' => 'Awaiting Receive'],
    ['value' => 'in_transit', 'label' => 'Routed / In Transit'],
    ['value' => 'on_hold', 'label' => 'Pending / On Hold'],
    ['value' => 'new_created', 'label' => 'Newly Encoded (Today)'],
];
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=pending_receive');

$kpiCards = [
    ['label' => 'Pending Received', 'icon' => 'orange', 'value' => (string)$pendingCount],
    ['label' => 'Pending Approval', 'icon' => 'violet', 'value' => (string)$pendingApprovalTotal],
    ['label' => 'Pending Forward', 'icon' => 'blue', 'value' => (string)$pendingForwardTotal],
    ['label' => 'Released', 'icon' => 'green', 'value' => (string)$releasedTotal],
];

$panels = [
    [
        'title' => 'Pending Receive Workflow Snapshot',
        'rows' => [
            ['label' => 'Pending Received', 'value' => (string)$pendingCount, 'width' => $pendingReceivedWidth],
            ['label' => 'Pending Approval', 'value' => (string)$pendingApprovalTotal, 'width' => $pendingApprovalWidth],
            ['label' => 'Pending Forward', 'value' => (string)$pendingForwardTotal, 'width' => $pendingForwardWidth],
            ['label' => 'Released', 'value' => (string)$releasedTotal, 'width' => $releasedWidth],
        ],
    ],
];

$tableTitle = 'Pending Receive Queue';
$tableColumns = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Current Holder', 'Date Routed', 'Time Remaining', 'Status', 'Quick Actions'];
$pageActions = ['View', 'Receive', 'Forward'];
$stickyActions = ['Receive', 'Forward', 'Return / Request Info'];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';

