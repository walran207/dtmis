<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/user-recycle-bin.php';

$deletedUsers = [];

try {
    $pdo = getDatabaseConnection();
    $deletedUsers = user_recycle_bin_fetch_deleted_users($pdo, 400);
} catch (Throwable $exception) {
    $deletedUsers = [];
}

$pageTitle = 'Deleted Users | DENR Region XII DTMIS';
$brandSubtitle = 'Super Admin Portal';
$pageHeading = 'Deleted Users';
$pageSubtitle = 'Review archived user accounts and restore them back into the active user directory when needed.';
$activeMenu = 'deleted_users';
$showQueueTable = false;
$renderStandardContent = false;
$customSectionInclude = __DIR__ . '/sections/deleted-users-panel.php';
$disableLiveDashboardRefresh = true;
$hideHeaderSearch = true;
$showQrReceiveScanner = false;
$dtrCsrfToken = (string)($_SESSION['csrf_token'] ?? '');
$superAdminCsrfToken = (string)($_SESSION['csrf_token'] ?? '');
$superAdminDeletedUsers = $deletedUsers;
$superAdminDeletedUsersEndpoint = app_url('actions/super-admin-deleted-users.php');

$activeDeletedUsers = 0;
foreach ($deletedUsers as $deletedUser) {
    if (!empty($deletedUser['is_active'])) {
        $activeDeletedUsers += 1;
    }
}

$kpiCards = [
    ['label' => 'Archived Users', 'value' => (string)count($deletedUsers), 'icon' => 'orange'],
    ['label' => 'Previously Active', 'value' => (string)$activeDeletedUsers, 'icon' => 'green'],
];

$panels = [
    [
        'title' => 'User Recycle Bin',
        'chips' => [
            'Super Admin visibility',
            'Restore original user ID',
            'Restore role and office mapping',
            'Admin delete safety net',
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
