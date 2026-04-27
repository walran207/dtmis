<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION['flash_success'] = 'First-login password reset is temporarily disabled.';
unset($_SESSION['force_reset_user_id'], $_SESSION['force_reset_email']);

app_redirect('auth/login.php');
