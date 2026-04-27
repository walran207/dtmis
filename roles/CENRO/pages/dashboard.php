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
$roleName = 'CENRO';
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
    $pendingReceivedTotal,
    $pendingApprovalTotal,
    $pendingForwardTotal,
    $releasedTotal,
    1
);

$pendingReceivedWidth = (string)(int)round(($pendingReceivedTotal / $snapshotBase) * 100) . '%';
$pendingApprovalWidth = (string)(int)round(($pendingApprovalTotal / $snapshotBase) * 100) . '%';
$pendingForwardWidth = (string)(int)round(($pendingForwardTotal / $snapshotBase) * 100) . '%';
$releasedWidth = (string)(int)round(($releasedTotal / $snapshotBase) * 100) . '%';

$pageTitle = 'CENRO Dashboard | DENR Region XII eDATS';
$brandSubtitle = 'CENRO Portal';
$pageHeading = 'CENRO Dashboard';
$pageSubtitle = 'Frontline intake, processing, and forwarding to PENRO.';
$activeMenu = 'dashboard';
$showQueueTable = false;
$tableTitle = 'Documents in Custody / Action Queue';
$tableColumns = ['Tracking ID', 'Subject', 'Status', 'Last Actor', 'Date Created', 'Action'];
$pageActions = [];
$stickyActions = [];
$showStickySearch = false;
$showStickyStatusFilter = false;
$showQueueFilterControls = false;
$enableTableCardFilterControls = false;
$hideHeaderSearch = true;
$showStatusCategoryFilter = false;
$routeOffices = is_array($routeOffices ?? null) ? $routeOffices : [];
$statCardNavigationTargets = [
    'default' => app_url('CENRO/pending-receive.php'),
    'awaiting_receive' => app_url('CENRO/pending-receive.php'),
    'received' => app_url('CENRO/pending-receive.php'),
    'approved' => app_url('CENRO/pending-receive.php'),
    'intake_drafts' => app_url('CENRO/create-intake.php'),
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
        'title' => 'CENRO Action Queue Mix',
        'rows' => [
            ['label' => 'Pending Received', 'value' => (string)$pendingReceivedTotal, 'width' => $pendingReceivedWidth],
            ['label' => 'Pending Approval', 'value' => (string)$pendingApprovalTotal, 'width' => $pendingApprovalWidth],
            ['label' => 'Pending Forward', 'value' => (string)$pendingForwardTotal, 'width' => $pendingForwardWidth],
            ['label' => 'Released', 'value' => (string)$releasedTotal, 'width' => $releasedWidth],
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
