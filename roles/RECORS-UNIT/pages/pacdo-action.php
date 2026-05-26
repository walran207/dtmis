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

$officeId = (int) ($_SESSION['office_id'] ?? 0);
$roleName = 'RECORDS-UNIT';
$queueRows = [];
$routeOffices = [];
$metrics = [
    'pending_total' => 0,
    'pending_approval_total' => 0,
    'pending_forward_total' => 0,
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
    $queueRows = dashboard_fetch_pending_receive_rows($pdo, $officeId, 50, true, true);
    $routeOffices = dashboard_fetch_route_offices($pdo, $officeId);
    $metrics = array_merge($metrics, dashboard_fetch_role_metrics($pdo, $officeId));
} catch (Throwable $exception) {
    $queueRows = [];
    $routeOffices = [];
}

$actionBase = max(
    (int) $metrics['pending_total'],
    (int) $metrics['pending_approval_total'],
    (int) $metrics['pending_forward_total'],
    (int) $metrics['released_total'],
    (int) $metrics['completed_total'],
    1
);

$riskBase = max(
    (int) $metrics['at_risk_total'],
    (int) $metrics['due_today_total'],
    (int) $metrics['due_soon_total'],
    (int) $metrics['overdue_total'],
    1
);

$pendingReceivedWidth = (string) (int) round((((int) $metrics['pending_total']) / $actionBase) * 100) . '%';
$pendingApprovalWidth = (string) (int) round((((int) $metrics['pending_approval_total']) / $actionBase) * 100) . '%';
$pendingForwardWidth = (string) (int) round((((int) $metrics['pending_forward_total']) / $actionBase) * 100) . '%';
$releasedWidth = (string) (int) round((((int) $metrics['released_total']) / $actionBase) * 100) . '%';
$completedWidth = (string) (int) round((((int) $metrics['completed_total']) / $actionBase) * 100) . '%';
$atRiskWidth = (string) (int) round((((int) $metrics['at_risk_total']) / $riskBase) * 100) . '%';
$dueSoonWidth = (string) (int) round(((((int) $metrics['due_today_total']) + ((int) $metrics['due_soon_total'])) / $riskBase) * 100) . '%';
$overdueWidth = (string) (int) round((((int) $metrics['overdue_total']) / $riskBase) * 100) . '%';

$tableRows = [];
foreach ($queueRows as $queueRow) {
    $tableRows[] = [
        'value' => [
            (string) ($queueRow['tracking_id'] ?? '-'),
            (string) ($queueRow['subject'] ?? '-'),
            (string) ($queueRow['document_type'] ?? '-'),
            (string) ($queueRow['date_received'] ?? '-'),
            (string) ($queueRow['time_remaining'] ?? '-'),
            (string) ($queueRow['date_created'] ?? '-'),
            (string) ($queueRow['status'] ?? 'Pending'),
            
            'View Tracking Slip | Receive | Approve | Pending | Forward | Return | Release | Print Package',
        ],
        'meta' => [
            'document_id' => (string) ($queueRow['document_id'] ?? 0),
            'tracking_id' => (string) ($queueRow['tracking_id'] ?? ''),
            'status_raw' => (string) ($queueRow['status'] ?? ''),
            'date_received_raw' => (string) ($queueRow['date_received_raw'] ?? ''),
            'date_created_raw' => (string) ($queueRow['date_created_raw'] ?? ''),
            'has_section_receive' => (int) ($queueRow['has_section_receive'] ?? 0) > 0 ? '1' : '0',
            'has_signed_action' => (int) ($queueRow['has_signed_action'] ?? 0) > 0 ? '1' : '0',
            'origin_office_id' => (string) ((int) ($queueRow['origin_office_id'] ?? 0)),
            'current_office_id' => (string) ((int) ($queueRow['current_office_id'] ?? 0)),
            'pending_office_id' => (string) ((int) ($queueRow['pending_office_id'] ?? 0)),
        ],
    ];
}

$pageTitle = 'RECORDS-UNIT Action | DENR Region XII DTMIS';
$brandSubtitle = 'RECORDS-UNIT Portal';
$pageHeading = 'RECORDS-UNIT Action';
$pageSubtitle = 'Queue handling and workflow actions for RECORDS-UNIT documents.';
$activeMenu = 'pacdo_action';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=pending_receive_action&exclude_returned=1');
$tableTitle = 'For Clearance Queue (ARTA risk first)';
$tableColumns = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Date Received', 'Time Remaining', 'Date Created', 'Status', 'Quick Actions'];
$pageActions = ['View Tracking Slip', 'Print Package', 'Receive', 'Approve', 'Pending', 'Forward', 'Return', 'Release'];
$stickyActions = ['Receive', 'Approve', 'Pending', 'Forward', 'Return / Request Info', 'Release', 'Print Slip', 'Print Package'];
$queueControlsPlacement = 'table_card';
$showStatusCategoryFilter = true;
$statusFilterOptions = [
    ['value' => '', 'label' => 'All Status Categories'],
    ['value' => 'awaiting_receive', 'label' => 'Awaiting Receive'],
    ['value' => 'received', 'label' => 'Received'],
    ['value' => 'approved', 'label' => 'Approved'],
    ['value' => 'pending_forward', 'label' => 'Pending Forward'],
    ['value' => 'forward', 'label' => 'Forwarded'],
    ['value' => 'pending_released', 'label' => 'Pending Released'],
];
$routeOffices = is_array($routeOffices ?? null) ? $routeOffices : [];

$kpiCards = [];
$panels = [];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
