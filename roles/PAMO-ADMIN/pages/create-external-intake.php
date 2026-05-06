<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

app_redirect('PAMO-ADMIN/create-intake.php');
