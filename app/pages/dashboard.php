<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';

session_start();

if (!empty($_SESSION['user_id'])) {
    app_redirect_to_role_dashboard((string)($_SESSION['role_name'] ?? ''));
}

app_redirect('auth/login.php');
