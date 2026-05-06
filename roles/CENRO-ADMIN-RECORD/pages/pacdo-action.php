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
$roleName = 'CENRO_ADMIN_RECORD';
$queueRows = [];
$routeOffices = [];
$metrics = [
    'pending_total' => 0,
    'pending_approval_total' => 0,
    'pending_forward_total' => 0,
    'returned_total' => 0,
    'released_total' => 0,
    'completed_total' => 0,
    'at_risk_total' => 0,
    'due_today_total' => 0,
    'due_soon_total' => 0,
    'overdue_total' => 0,
    'for_clearance_total' => 0,
];

try {
    $pdo = getDatabaseConnection();
    $queueRows = dashboard_fetch_pending_receive_rows($pdo, $officeId, 50, true);
    $routeOffices = dashboard_fetch_route_offices($pdo, $officeId);
    $metrics = array_merge($metrics, dashboard_fetch_role_metrics($pdo, $officeId));
} catch (Throwable $exception) {
    $queueRows = [];
    $routeOffices = [];
}

$actionBase = max(
    (int)$metrics['pending_total'],
    (int)$metrics['pending_approval_total'],
    (int)$metrics['pending_forward_total'],
    (int)$metrics['released_total'],
    (int)$metrics['completed_total'],
    1
);

$riskBase = max(
    (int)$metrics['at_risk_total'],
    (int)$metrics['due_today_total'],
    (int)$metrics['due_soon_total'],
    (int)$metrics['overdue_total'],
    1
);

$pendingReceivedWidth = (string)(int)round((((int)$metrics['pending_total']) / $actionBase) * 100) . '%';
$pendingApprovalWidth = (string)(int)round((((int)$metrics['pending_approval_total']) / $actionBase) * 100) . '%';
$pendingForwardWidth = (string)(int)round((((int)$metrics['pending_forward_total']) / $actionBase) * 100) . '%';
$releasedWidth = (string)(int)round((((int)$metrics['released_total']) / $actionBase) * 100) . '%';
$completedWidth = (string)(int)round((((int)$metrics['completed_total']) / $actionBase) * 100) . '%';
$atRiskWidth = (string)(int)round((((int)$metrics['at_risk_total']) / $riskBase) * 100) . '%';
$dueSoonWidth = (string)(int)round(((((int)$metrics['due_today_total']) + ((int)$metrics['due_soon_total'])) / $riskBase) * 100) . '%';
$overdueWidth = (string)(int)round((((int)$metrics['overdue_total']) / $riskBase) * 100) . '%';

$tableRows = [];
foreach ($queueRows as $queueRow) {
    $tableRows[] = [
        'value' => [
            (string)($queueRow['tracking_id'] ?? '-'),
            (string)($queueRow['subject'] ?? '-'),
            (string)($queueRow['document_type'] ?? '-'),
            (string)($queueRow['date_received'] ?? '-'),
            (string)($queueRow['time_remaining'] ?? '-'),
            (string)($queueRow['date_created'] ?? '-'),
            (string)($queueRow['status'] ?? 'Pending'),
            'View Tracking Slip | Receive | Approve | Pending | Forward | Released | Print Package',
        ],
        'meta' => [
            'document_id' => (string)($queueRow['document_id'] ?? 0),
            'tracking_id' => (string)($queueRow['tracking_id'] ?? ''),
            'status_raw' => (string)($queueRow['status'] ?? ''),
            'date_received_raw' => (string)($queueRow['date_received_raw'] ?? ''),
            'date_created_raw' => (string)($queueRow['date_created_raw'] ?? ''),
            'has_section_receive' => (int)($queueRow['has_section_receive'] ?? 0) > 0 ? '1' : '0',
            'has_signed_action' => (int)($queueRow['has_signed_action'] ?? 0) > 0 ? '1' : '0',
            'origin_office_id' => (string)((int)($queueRow['origin_office_id'] ?? 0)),
            'current_office_id' => (string)((int)($queueRow['current_office_id'] ?? 0)),
            'pending_office_id' => (string)((int)($queueRow['pending_office_id'] ?? 0)),
        ],
    ];
}

$pageTitle = 'CENRO Admin Record Action | DENR Region XII eDATS';
$brandSubtitle = 'CENRO Admin Record Portal';
$pageHeading = 'CENRO Admin Record Action';
$pageSubtitle = 'Queue handling and workflow actions for CENRO Admin Record documents.';
$activeMenu = 'cenro_admin_record_action';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=pending_receive_action');
$tableTitle = 'For Clearance Queue (ARTA risk first)';
$tableColumns = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Date Received', 'Time Remaining', 'Date Created', 'Status', 'Quick Actions'];
$pageActions = ['View Tracking Slip', 'Print Package', 'Receive', 'Approve', 'Pending', 'Forward', 'Released'];
$stickyActions = ['Receive', 'Approve', 'Pending', 'Forward', 'Released', 'Print Slip', 'Print Package'];
$queueControlsPlacement = 'table_card';
$showStatusCategoryFilter = true;
$statusFilterOptions = [
    ['value' => '', 'label' => 'All Status Categories'],
    ['value' => 'awaiting_receive', 'label' => 'Awaiting Receive'],
    ['value' => 'received', 'label' => 'Received'],
    ['value' => 'approved', 'label' => 'Approved'],
    ['value' => 'pending_forward', 'label' => 'Pending Forward'],
    ['value' => 'forward', 'label' => 'Forwarded'],
    ['value' => 'completed', 'label' => 'Released'],
];
$routeOffices = is_array($routeOffices ?? null) ? $routeOffices : [];

$kpiCards = [
    ['label' => 'Pending Received', 'value' => (string)$metrics['pending_total'], 'icon' => 'blue'],
    ['label' => 'Pending Approval', 'value' => (string)$metrics['pending_approval_total'], 'icon' => 'orange'],
    ['label' => 'Pending Forward', 'value' => (string)$metrics['pending_forward_total'], 'icon' => 'violet'],
    ['label' => 'Returned', 'value' => (string)$metrics['returned_total'], 'icon' => 'red'],
    ['label' => 'Released', 'value' => (string)$metrics['completed_total'], 'icon' => 'green'],
];

$panels = [
    [
        'title' => 'Action Pipeline',
        'rows' => [
            ['label' => 'Pending Received', 'value' => (string)$metrics['pending_total'], 'width' => $pendingReceivedWidth],
            ['label' => 'Pending Approval', 'value' => (string)$metrics['pending_approval_total'], 'width' => $pendingApprovalWidth],
            ['label' => 'Pending Forward', 'value' => (string)$metrics['pending_forward_total'], 'width' => $pendingForwardWidth],
            ['label' => 'Released', 'value' => (string)$metrics['completed_total'], 'width' => $completedWidth],
        ],
    ],
    [
        'title' => 'Clearance Throughput',
        'rows' => [
            ['label' => 'For Clearance', 'value' => (string)$metrics['for_clearance_total'], 'width' => $pendingApprovalWidth],
            ['label' => 'Released', 'value' => (string)$metrics['completed_total'], 'width' => $completedWidth],
            ['label' => 'Pending Released', 'value' => (string)$metrics['released_total'], 'width' => $releasedWidth],
        ],
    ],
    [
        'title' => 'ARTA Risk Watch',
        'rows' => [
            ['label' => 'At-Risk Total', 'value' => (string)$metrics['at_risk_total'], 'width' => $atRiskWidth],
            ['label' => 'Due Soon / Due Today', 'value' => (string)(((int)$metrics['due_today_total']) + ((int)$metrics['due_soon_total'])), 'width' => $dueSoonWidth],
            ['label' => 'Overdue', 'value' => (string)$metrics['overdue_total'], 'width' => $overdueWidth],
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
