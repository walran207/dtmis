<?php
declare(strict_types=1);

$roleBasePath = dirname(__DIR__);
$roleName = 'PAMO_ADMIN';
$initialsFallback = 'PC';
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
$userId = (int)($_SESSION['user_id'] ?? 0);
$intakeActionTypes = ['Created'];

try {
    $pdo = getDatabaseConnection();
    $metrics = dashboard_fetch_role_metrics($pdo, $officeId);
    $queueRows = dashboard_fetch_actor_tracker_rows($pdo, $userId, $intakeActionTypes, 10);
} catch (Throwable $exception) {
    $metrics = [];
    $queueRows = [];
}

$pageTitle = 'Create / Intake | DENR Region XII eDATS';
$activeMenu = 'create_intake';
$brandSubtitle = 'PAMO Admin Portal';
$pageHeading = 'Create / Intake';
$pageSubtitle = 'Fast intake page for internal/external submissions.';
$searchPlaceholder = 'Search Tracking ID or subject';
$kpiCards = [
    ['label' => 'Encoded Today', 'value' => (string)($metrics['created_today'] ?? 0), 'icon' => 'blue'],
    ['label' => 'Pending Forward', 'value' => (string)($metrics['pending_forward_total'] ?? 0), 'icon' => 'violet'],
    ['label' => 'Intake Drafts', 'value' => (string)($metrics['pending_approval_total'] ?? 0), 'icon' => 'orange'],
    ['label' => 'Returned', 'value' => (string)($metrics['returned_total'] ?? 0), 'icon' => 'red'],
];
$useDefaultIntakeKpiCards = false;

$tableTitle = 'Recent Intakes';
$tableColumns = ['Indicator', 'Tracking ID', 'Document Type', 'Subject', 'Status', 'Date Created', 'Remarks', 'Quick Actions'];
$showQueueTable = true;
$enableStickyQueueFiltersOnIntake = true;
$queueControlsPlacement = 'table_card';
$hideHeaderSearch = true;
$showStatusCategoryFilter = true;
$hideCompletedQueueRows = true;
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=acted&actions=' . rawurlencode(implode(',', $intakeActionTypes)));
$forceWorkflowActionScope = true;

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
