<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/role-office-rules.php';

$overview = [
    'users_total' => 0,
    'users_active' => 0,
];
$users = [];
$roles = [];
$offices = [];

try {
    $pdo = getDatabaseConnection();
    $overview = super_admin_fetch_global_overview($pdo);
    $users = super_admin_fetch_users($pdo, 700);
    $roles = super_admin_fetch_roles($pdo);
    $offices = super_admin_fetch_offices($pdo, 700);
} catch (Throwable $exception) {
    $users = [];
    $roles = [];
    $offices = [];
}

$lockedUsers = 0;
$inactiveUsers = 0;
foreach ($users as $user) {
    if ((string)($user['locked_until'] ?? '') !== '') {
        $lockedUsers += 1;
    }
    if (empty($user['is_active'])) {
        $inactiveUsers += 1;
    }
}

$pageTitle = 'Users Management | DENR Region XII DTMIS';
$brandSubtitle = 'Super Admin Portal';
$pageHeading = 'Users Management';
$pageSubtitle = 'Modify user role assignments, office mapping, status, and security lock states.';
$activeMenu = 'users_management';
$showQueueTable = false;
$renderStandardContent = false;
$customSectionInclude = __DIR__ . '/sections/users-management-panel.php';
$disableLiveDashboardRefresh = true;
$hideHeaderSearch = true;
$showQrReceiveScanner = false;
$dtrCsrfToken = (string)($_SESSION['csrf_token'] ?? '');
$superAdminUsers = $users;
$superAdminRoles = $roles;
$superAdminOffices = $offices;
$superAdminOfficeDirectory = DTMIS_build_office_directory($offices);
$superAdminOfficeOptionsPayload = is_array($superAdminOfficeDirectory['office_options_payload'] ?? null)
    ? $superAdminOfficeDirectory['office_options_payload']
    : [];
$superAdminFixedOfficeIdByRoleName = is_array($superAdminOfficeDirectory['fixed_office_id_by_role_name'] ?? null)
    ? $superAdminOfficeDirectory['fixed_office_id_by_role_name']
    : [];
$superAdminUsersEndpoint = app_url('actions/super-admin-users.php');

$kpiCards = [
    ['label' => 'Total Users', 'value' => (string)$overview['users_total'], 'icon' => 'blue'],
    ['label' => 'Active Users', 'value' => (string)$overview['users_active'], 'icon' => 'green'],
    ['label' => 'Locked Users', 'value' => (string)$lockedUsers, 'icon' => 'orange'],
    ['label' => 'Inactive Users', 'value' => (string)$inactiveUsers, 'icon' => 'violet'],
];

$panels = [
    [
        'title' => 'Access Governance',
        'chips' => [
            'Role remapping',
            'Office reassignment',
            'Account activation / deactivation',
            'Security lock reset',
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
