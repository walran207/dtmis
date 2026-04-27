<?php
if (!isset($pageTitle) || trim((string)$pageTitle) === '') {
    $pageTitle = 'Dashboard | DENR Region XII eDATS';
}

$pwaManifestPath = function_exists('app_url') ? app_url('manifest.webmanifest') : '../manifest.webmanifest';
$offlineReadCacheScript = function_exists('app_url') ? app_url('assets/js/offline-read-cache.js') : '../assets/js/offline-read-cache.js';
$offlineOutboxScript = function_exists('app_url') ? app_url('assets/js/offline-outbox.js') : '../assets/js/offline-outbox.js';
$pwaBootstrapScript = function_exists('app_url') ? app_url('assets/js/pwa-init.js') : '../assets/js/pwa-init.js';
$pwaIconPath = function_exists('app_url') ? app_url('assets/Logo.png') : '../assets/Logo.png';
$pwaBasePath = function_exists('app_url') ? rtrim((string)app_url(''), '/') : '';
if ($pwaBasePath === '') {
    $pwaBasePath = '/';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1f4d7a">
    <title><?php echo htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="manifest" href="<?php echo htmlspecialchars((string)$pwaManifestPath, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars((string)$pwaIconPath, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="./dashboard.css">
    <script src="<?php echo htmlspecialchars((string)$offlineReadCacheScript, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars((string)$offlineOutboxScript, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars((string)$pwaBootstrapScript, ENT_QUOTES, 'UTF-8'); ?>" data-base-path="<?php echo htmlspecialchars((string)$pwaBasePath, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
</head>
<body>
    <div class="dashboard-shell">
