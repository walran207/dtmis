<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$overview = [
    'users_total' => 0,
    'users_active' => 0,
    'users_inactive' => 0,
    'users_locked' => 0,
    'offices_total' => 0,
];
$users = [];
$roles = [];
$offices = [];
$scope = [
    'summary_label' => 'Managed office scope: unavailable',
    'include_pamo' => false,
    'root_office' => null,
    'penro_office' => null,
];

try {
    $pdo = getDatabaseConnection();
    $scope = admin_resolve_scope($pdo);
    $overview = admin_fetch_scope_overview($pdo, $scope);
    $users = admin_fetch_scope_users($pdo, $scope, 700);
    $roles = admin_fetch_manageable_roles($pdo);
    $offices = admin_fetch_scope_offices($scope);
} catch (Throwable $exception) {
    $users = [];
    $roles = [];
    $offices = [];
}

$pageTitle = 'ICT Admin Dashboard | DENR Region XII DTMIS';
$brandSubtitle = 'ICT Admin Portal';
$pageHeading = 'Dashboard';
$pageSubtitle = 'View scoped account insights, usage patterns, and office coverage at a glance.';
$activeMenu = 'dashboard';
$showQueueTable = false;
$renderStandardContent = false;
$customSectionInclude = __DIR__ . '/sections/dashboard-panel.php';
$disableLiveDashboardRefresh = true;
$hideHeaderSearch = true;
$showQrReceiveScanner = false;
$dtrCsrfToken = (string)($_SESSION['csrf_token'] ?? '');
$adminOverview = $overview;
$adminUsers = $users;
$adminRoles = $roles;
$adminOffices = $offices;
$adminScope = $scope;
$adminUsersEndpoint = app_url('actions/admin-users.php');

$kpiCards = [
    ['label' => 'Managed Users', 'value' => (string)$overview['users_total'], 'icon' => 'blue'],
    ['label' => 'Active Users', 'value' => (string)$overview['users_active'], 'icon' => 'green'],
    ['label' => 'Inactive Users', 'value' => (string)$overview['users_inactive'], 'icon' => 'violet'],
    ['label' => 'Locked Users', 'value' => (string)$overview['users_locked'], 'icon' => 'orange'],
    ['label' => 'Covered Offices', 'value' => (string)$overview['offices_total'], 'icon' => 'blue'],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
