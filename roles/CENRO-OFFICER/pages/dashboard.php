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
$roleName = 'CENRO_OFFICER';
$queueRows = [];
$routeOffices = [];
$inQueueTotal = 0;
$pendingForwardTotal = 0;
$metrics = [
    'total_scope' => 0,
    'pending_total' => 0,
    'due_today_total' => 0,
    'due_soon_total' => 0,
    'overdue_total' => 0,
    'completed_total' => 0,
    'completed_week' => 0,
    'created_today' => 0,
    'external_today' => 0,
    'pending_approval_total' => 0,
    'pending_sign_total' => 0,
    'pending_forward_total' => 0,
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

$inQueueTotal = max((int)($metrics['pending_total'] ?? 0), count($queueRows));
$pendingForwardTotal = (int)($metrics['pending_forward_total'] ?? ($metrics['for_endorsement_total'] ?? 0));

$progressBase = max(
    $inQueueTotal,
    $pendingForwardTotal,
    (int)($metrics['returned_total'] ?? 0),
    1
);

$inQueueWidth = (string)(int)round(($inQueueTotal / $progressBase) * 100) . '%';
$pendingForwardWidth = (string)(int)round(($pendingForwardTotal / $progressBase) * 100) . '%';
$returnedWidth = (string)(int)round((((int)($metrics['returned_total'] ?? 0)) / $progressBase) * 100) . '%';

$tableRows = [];
foreach ($queueRows as $queueRow) {
    $tableRows[] = [
        'value' => [
            (string)($queueRow['tracking_id'] ?? '-'),
            (string)($queueRow['subject'] ?? '-') . ' [' . (string)($queueRow['status'] ?? 'Pending') . ']',
            (string)($queueRow['document_type'] ?? '-'),
            (string)($queueRow['date_received'] ?? '-'),
            'View Tracking Slip | Receive | Pending | Forward | Send Back to Admin Record | Print Package',
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

$pageTitle = 'CENRO Officer Dashboard | DENR Region XII DTMIS';
$brandSubtitle = 'CENRO Officer Portal';
$pageHeading = 'CENRO Officer Dashboard';
$pageSubtitle = 'CENRO officer action and strict internal routing.';
$activeMenu = 'dashboard';
$tableTitle = 'CENRO Officer Action Desk (ARTA risk first)';
$tableColumns = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Date Received', 'Quick Actions'];
$pageActions = ['View Tracking Slip', 'Print Package', 'Receive', 'Pending', 'Forward', 'Send Back to Admin Record'];
$stickyActions = ['Receive', 'Pending', 'Forward', 'Print Slip', 'Print Package'];
$queueControlsPlacement = 'table_card';
$showStatusCategoryFilter = false;
$dateFilterPlacement = 'table_card';
$showQueueTable = false;
$routeOffices = is_array($routeOffices ?? null) ? $routeOffices : [];

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
            ['label' => 'In Queue', 'value' => (string)$inQueueTotal, 'width' => $inQueueWidth],
            ['label' => 'Pending forward', 'value' => (string)$pendingForwardTotal, 'width' => $pendingForwardWidth],
            ['label' => 'Returned', 'value' => (string)($metrics['returned_total'] ?? 0), 'width' => $returnedWidth],
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
