<?php
declare(strict_types=1);

$roleBasePath = dirname(__DIR__);

require_once dirname(__DIR__, 3) . '/config/app.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/config/dashboard-data.php';
require_once dirname(__DIR__, 3) . '/config/super-admin.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

if (!super_admin_has_access()) {
    app_redirect_to_role_dashboard((string)($_SESSION['role_name'] ?? ''));
}

$roleName = 'SUPER_ADMIN';
$initialsFallback = 'SA';
