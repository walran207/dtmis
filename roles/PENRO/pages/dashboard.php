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
$roleName = 'PENRO';
$queueRows = [];
$routeOffices = [];
$metrics = [
    'total_scope' => 0,
    'pending_total' => 0,
    'pending_approval_total' => 0,
    'pending_forward_total' => 0,
    'due_today_total' => 0,
    'due_soon_total' => 0,
    'overdue_total' => 0,
    'released_total' => 0,
    'completed_total' => 0,
    'completed_week' => 0,
    'created_today' => 0,
    'external_today' => 0,
    'for_signature_total' => 0,
    'for_clearance_total' => 0,
    'for_endorsement_total' => 0,
    'for_validation_total' => 0,
    'at_risk_total' => 0,
    'overrides_30d' => 0,
    'compliance_rate' => 100,
];

try {
    $pdo = getDatabaseConnection();
    $queueRows = dashboard_fetch_queue_rows($pdo, $officeId, 8);
    $routeOffices = dashboard_fetch_route_offices($pdo, $officeId);
    $metrics = dashboard_fetch_role_metrics($pdo, $officeId);
} catch (Throwable $exception) {
    $queueRows = [];
    $routeOffices = [];
}

$pendingReceivedTotal = (int)($metrics['pending_total'] ?? 0);
$pendingApprovalTotal = (int)($metrics['pending_approval_total'] ?? 0);
$pendingForwardTotal = (int)($metrics['pending_forward_total'] ?? ($metrics['for_endorsement_total'] ?? 0));
$releasedTotal = (int)($metrics['released_total'] ?? 0);

$snapshotBase = max(
    $metrics['pending_total'],
    $metrics['due_today_total'],
    $metrics['due_soon_total'],
    $metrics['overdue_total'],
    $metrics['completed_week'],
    1
);

$pendingWidth = (string)(int)round(($metrics['pending_total'] / $snapshotBase) * 100) . '%';
$dueSoonWidth = (string)(int)round((($metrics['due_today_total'] + $metrics['due_soon_total']) / $snapshotBase) * 100) . '%';
$overdueWidth = (string)(int)round(($metrics['overdue_total'] / $snapshotBase) * 100) . '%';
$completedWidth = (string)(int)round(($metrics['completed_week'] / $snapshotBase) * 100) . '%';

$tableRows = [];
foreach ($queueRows as $queueRow) {
    $tableRows[] = [
        'value' => [
            (string)($queueRow['tracking_id'] ?? '-'),
            (string)($queueRow['subject'] ?? '-'),
            (string)($queueRow['status'] ?? 'Pending'),
            (string)($queueRow['document_type'] ?? '-'),
            (string)($queueRow['date_received'] ?? '-'),
            'View Tracking Slip | Receive | Approve | Pending | Forward | Return | Print Package',
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

$pageTitle = 'PENRO Dashboard | DENR Region XII eDATS';
$brandSubtitle = 'PENRO Portal';
$pageHeading = 'PENRO Dashboard';
$pageSubtitle = 'Provincial supervision, endorsement to RECORDS-UNIT, and routing down.';
$activeMenu = 'dashboard';
$tableTitle = 'For PENRO Action (ARTA risk first)';
$tableColumns = ['Tracking ID', 'Subject', 'Status', 'Document Type (+ ARTA)', 'Date Received', 'Quick Actions'];
$queueControlsPlacement = 'table_card';
$pageActions = [];
$stickyActions = [];
$showQueueTable = false;
$hideHeaderSearch = true;
$routeOffices = is_array($routeOffices ?? null) ? $routeOffices : [];
$statCardNavigationTargets = [
    'default' => app_url('PENRO/pending-receive.php'),
    'awaiting_receive' => app_url('PENRO/pending-receive.php'),
    'received' => app_url('PENRO/pending-receive.php'),
    'approved' => app_url('PENRO/pending-receive.php'),
    'intake_drafts' => app_url('PENRO/create-intake.php'),
];

$kpiCards = [
    ['label' => 'Encoded Today', 'value' => (string)($metrics['created_today'] ?? 0), 'icon' => 'blue'],
    ['label' => 'Due Soon', 'value' => (string)($metrics['due_soon_total'] ?? 0), 'icon' => 'orange'],
    ['label' => 'Pending Forward', 'value' => (string)($metrics['pending_forward_total'] ?? 0), 'icon' => 'violet'],
    ['label' => 'Completed Today', 'value' => (string)($metrics['completed_today'] ?? 0), 'icon' => 'green'],
    ['label' => 'Returned', 'value' => (string)($metrics['returned_total'] ?? 0), 'icon' => 'red'],
];

$panels = [
    [
        'title' => 'Queue Snapshot',
        'rows' => [
            ['label' => 'Pending', 'value' => (string)$metrics['pending_total'], 'width' => $pendingWidth],
            ['label' => 'Due Soon / Due Today', 'value' => (string)($metrics['due_today_total'] + $metrics['due_soon_total']), 'width' => $dueSoonWidth],
            ['label' => 'Overdue', 'value' => (string)$metrics['overdue_total'], 'width' => $overdueWidth],
            ['label' => 'Completed (7d)', 'value' => (string)$metrics['completed_week'], 'width' => $completedWidth],
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';


