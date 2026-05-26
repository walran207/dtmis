<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

session_start();

app_clear_session_heartbeat();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
}

session_destroy();

app_redirect('auth/login.php?offline_clear=1');
