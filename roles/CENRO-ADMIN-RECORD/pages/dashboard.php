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
$routeOffices = [];
$metrics = [
    'total_scope' => 0,
    'pending_total' => 0,
    'due_today_total' => 0,
    'due_soon_total' => 0,
    'overdue_total' => 0,
    'completed_total' => 0,
    'released_total' => 0,
    'completed_week' => 0,
    'created_today' => 0,
    'external_today' => 0,
    'pending_approval_total' => 0,
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
    $routeOffices = dashboard_fetch_route_offices($pdo, $officeId);
    $metrics = dashboard_fetch_role_metrics($pdo, $officeId);
} catch (Throwable $exception) {
    $routeOffices = [];
}

$workflowBase = max(
    $metrics['pending_total'],
    $metrics['pending_approval_total'],
    $metrics['pending_forward_total'],
    $metrics['completed_total'],
    $metrics['released_total'],
    1
);

$riskBase = max(
    $metrics['at_risk_total'],
    $metrics['due_today_total'],
    $metrics['due_soon_total'],
    $metrics['overdue_total'],
    1
);

$pendingReceivedWidth = (string)(int)round(($metrics['pending_total'] / $workflowBase) * 100) . '%';
$pendingApprovalWidth = (string)(int)round(($metrics['pending_approval_total'] / $workflowBase) * 100) . '%';
$pendingForwardWidth = (string)(int)round(($metrics['pending_forward_total'] / $workflowBase) * 100) . '%';
$completedWidth = (string)(int)round(($metrics['completed_total'] / $workflowBase) * 100) . '%';
$releasedWidth = (string)(int)round(($metrics['released_total'] / $workflowBase) * 100) . '%';
$atRiskWidth = (string)(int)round(($metrics['at_risk_total'] / $riskBase) * 100) . '%';
$dueSoonWidth = (string)(int)round((($metrics['due_today_total'] + $metrics['due_soon_total']) / $riskBase) * 100) . '%';
$overdueWidth = (string)(int)round(($metrics['overdue_total'] / $riskBase) * 100) . '%';

$pageTitle = 'CENRO Admin Record Dashboard | DENR Region XII eDATS';
$brandSubtitle = 'CENRO Admin Record Portal';
$pageHeading = 'CENRO Admin Record Management Dashboard';
$pageSubtitle = 'CENRO internal records gatekeeping, clearance checks, and ARTA monitoring.';
$activeMenu = 'dashboard';
$showQueueTable = false;
$tableColumns = ['Tracking ID', 'Subject', 'Status'];
$pageActions = [];
$stickyActions = [];
$hideHeaderSearch = true;
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
        'title' => 'Workflow Gatekeeping',
        'rows' => [
            ['label' => 'Pending Received', 'value' => (string)$metrics['pending_total'], 'width' => $pendingReceivedWidth],
            ['label' => 'Pending Approval', 'value' => (string)$metrics['pending_approval_total'], 'width' => $pendingApprovalWidth],
            ['label' => 'Pending Forward', 'value' => (string)$metrics['pending_forward_total'], 'width' => $pendingForwardWidth],
        ],
    ],
    [
        'title' => 'Clearance Outcomes',
        'rows' => [
            ['label' => 'Completed', 'value' => (string)$metrics['completed_total'], 'width' => $completedWidth],
            ['label' => 'Released', 'value' => (string)$metrics['released_total'], 'width' => $releasedWidth],
            ['label' => 'Completed (7d)', 'value' => (string)$metrics['completed_week'], 'width' => $completedWidth],
        ],
    ],
    [
        'title' => 'ARTA Risk Monitor',
        'rows' => [
            ['label' => 'At-Risk Total', 'value' => (string)$metrics['at_risk_total'], 'width' => $atRiskWidth],
            ['label' => 'Due Soon / Due Today', 'value' => (string)($metrics['due_today_total'] + $metrics['due_soon_total']), 'width' => $dueSoonWidth],
            ['label' => 'Overdue', 'value' => (string)$metrics['overdue_total'], 'width' => $overdueWidth],
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';



