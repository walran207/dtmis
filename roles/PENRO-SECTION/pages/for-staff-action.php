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
$roleName = 'PENRO_SECTION';
$queueRows = [];
$routeOffices = [];
$metrics = [
    'pending_total' => 0,
    'pending_approval_total' => 0,
    'pending_sign_total' => 0,
    'pending_forward_total' => 0,
    'completed_total' => 0,
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

$pendingReceivedTotal = (int)($metrics['pending_total'] ?? 0);
$pendingApprovalTotal = (int)($metrics['pending_approval_total'] ?? 0);
$pendingSignTotal = (int)($metrics['pending_sign_total'] ?? 0);
$pendingForwardTotal = (int)($metrics['pending_forward_total'] ?? 0);
$completedTotal = (int)($metrics['completed_total'] ?? 0);

$actionBase = max(
    $pendingReceivedTotal,
    $pendingApprovalTotal,
    $pendingSignTotal,
    $pendingForwardTotal,
    1
);

$pendingReceivedWidth = (string)(int)round(($pendingReceivedTotal / $actionBase) * 100) . '%';
$pendingApprovalWidth = (string)(int)round(($pendingApprovalTotal / $actionBase) * 100) . '%';
$pendingSignWidth = (string)(int)round(($pendingSignTotal / $actionBase) * 100) . '%';
$pendingForwardWidth = (string)(int)round(($pendingForwardTotal / $actionBase) * 100) . '%';

$tableRows = [];
foreach ($queueRows as $queueRow) {
    $tableRows[] = [
        'value' => [
            (string)($queueRow['tracking_id'] ?? '-'),
            (string)($queueRow['subject'] ?? '-'),
            (string)($queueRow['document_type'] ?? '-'),
            (string)($queueRow['date_created'] ?? '-'),
            (string)($queueRow['date_received'] ?? '-'),
            (string)($queueRow['time_remaining'] ?? '-'),
            (string)($queueRow['status'] ?? 'Pending'),
            'View Tracking Slip | Receive | Pending | Forward | Print Package',
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

$pageTitle = 'For PENRO Section Action | DENR Region XII DTMIS';
$activeMenu = 'cenro_unit_action';
$brandSubtitle = 'PENRO Section Portal';
$pageHeading = 'For PENRO Section Action';
$pageSubtitle = 'Assigned records requiring PENRO section action.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=pending_receive_action');
$tableTitle = 'For PENRO Section Action Queue';
$tableColumns = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Date Created', 'Date Received', 'Time Remaining', 'Status', 'Quick Actions'];
$queueControlsPlacement = 'table_card';
$filterRouteExistingAttachmentsToPreparedResponse = true;
$showStatusCategoryFilter = true;
$statusFilterOptions = [
    ['value' => '', 'label' => 'All Status Categories'],
    ['value' => 'awaiting_receive', 'label' => 'Awaiting Receive'],
    ['value' => 'received', 'label' => 'Received'],
    ['value' => 'approved', 'label' => 'Approved'],
    ['value' => 'pending_forward', 'label' => 'Pending Forward'],
    ['value' => 'forward', 'label' => 'Forwarded'],
];
$pageActions = ['View Tracking Slip', 'Print Package', 'Receive', 'Pending', 'Forward'];
$stickyActions = ['Receive', 'Pending', 'Forward'];
$routeOffices = is_array($routeOffices ?? null) ? $routeOffices : [];

$kpiCards = [
    ['label' => 'Pending Received', 'icon' => 'blue', 'value' => (string)$pendingReceivedTotal],
    ['label' => 'Pending Approval', 'icon' => 'orange', 'value' => (string)$pendingApprovalTotal],
    ['label' => 'Completed', 'icon' => 'violet', 'value' => (string)$completedTotal],
    ['label' => 'Pending Forward', 'icon' => 'green', 'value' => (string)$pendingForwardTotal],
];

$panels = [
    [
        'title' => 'For Staff Action Queue Mix',
        'rows' => [
            ['label' => 'Pending Received', 'width' => $pendingReceivedWidth, 'value' => (string)$pendingReceivedTotal],
            ['label' => 'Pending Approval', 'width' => $pendingApprovalWidth, 'value' => (string)$pendingApprovalTotal],
            ['label' => 'Pending Sign', 'width' => $pendingSignWidth, 'value' => (string)$pendingSignTotal],
            ['label' => 'Pending Forward', 'width' => $pendingForwardWidth, 'value' => (string)$pendingForwardTotal],
        ],
    ],
    [
        'title' => 'Staff Workflow Activity Signals',
        'rows' => [
            ['label' => 'Received', 'width' => $pendingReceivedWidth, 'value' => (string)$pendingReceivedTotal],
            ['label' => 'Approved', 'width' => $pendingApprovalWidth, 'value' => (string)$pendingApprovalTotal],
            ['label' => 'Completed', 'width' => $pendingSignWidth, 'value' => (string)$completedTotal],
            ['label' => 'Pending Forward', 'width' => $pendingForwardWidth, 'value' => (string)$pendingForwardTotal],
        ],
    ],
    [
        'title' => 'Staff Action Focus',
        'chips' => ['Receive and validate incoming docs', 'Prepare approval-ready notes', 'Coordinate pending signatures', 'Forward and close ready items'],
    ],
];

$kpiCards = [];
$panels = [];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
