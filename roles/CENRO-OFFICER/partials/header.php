<?php
if (!isset($pageTitle) || trim((string)$pageTitle) === '') {
    $pageTitle = 'Dashboard | DENR Region XII DTMIS';
}

if (!isset($extraCss) || !is_array($extraCss)) {
    $extraCss = [];
}

$allCss = array_merge([
    app_url('partials-css/partial.css'),
], $extraCss);
$roleKeyAttr = htmlspecialchars((string)($roleKey ?? app_normalize_role_key((string)($roleName ?? ''))), ENT_QUOTES, 'UTF-8');
$themeToggleScript = app_url('assets/design-system/theme-toggle.js');
$designRuntimePath = dirname(__DIR__, 3) . '/assets/design-system/runtime-design-system.js';
$designRuntimeVersion = is_file($designRuntimePath) ? (string)filemtime($designRuntimePath) : '1';
$designRuntimeScript = app_url('assets/design-system/runtime-design-system.js') . '?v=' . rawurlencode($designRuntimeVersion);
$offlineReadCacheScript = app_url('assets/js/offline-read-cache.js');
$offlineOutboxScript = app_url('assets/js/offline-outbox.js');
$pwaManifestPath = app_url('manifest.webmanifest');
$pwaBootstrapScript = app_url('assets/js/pwa-init.js');
$pwaIconPath = app_url('assets/Logo.png');
$pwaBasePath = rtrim((string)app_url(''), '/');
if ($pwaBasePath === '') {
    $pwaBasePath = '/';
}
if (!isset($preloadStyleIncludes) || !is_array($preloadStyleIncludes)) {
    $preloadStyleIncludes = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1f4d7a">
    <title><?php echo htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="manifest" href="<?php echo htmlspecialchars((string)$pwaManifestPath, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars((string)$pwaIconPath, ENT_QUOTES, 'UTF-8'); ?>">
    <script>
        (function () {
            try {
                var storedTheme = localStorage.getItem('DTMIS_theme') || 'system';
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var resolvedTheme = storedTheme === 'dark' || (storedTheme === 'system' && prefersDark) ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', resolvedTheme);
            } catch (error) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
<?php foreach ($preloadStyleIncludes as $styleInclude): ?>
    <?php if (is_string($styleInclude) && $styleInclude !== '' && is_file($styleInclude)) { include $styleInclude; } ?>
<?php endforeach; ?>
<?php foreach ($allCss as $cssPath): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars((string)$cssPath, ENT_QUOTES, 'UTF-8'); ?>">
<?php endforeach; ?>
    <script src="<?php echo htmlspecialchars((string)$offlineReadCacheScript, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars((string)$offlineOutboxScript, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars((string)$themeToggleScript, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars((string)$designRuntimeScript, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars((string)$pwaBootstrapScript, ENT_QUOTES, 'UTF-8'); ?>" data-base-path="<?php echo htmlspecialchars((string)$pwaBasePath, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
</head>
<body data-role-key="<?php echo $roleKeyAttr; ?>">
    <script>
        (function () {
            try {
                var rootTheme = document.documentElement.getAttribute('data-theme') || 'light';
                document.body.setAttribute('data-theme', rootTheme);
            } catch (error) {
                document.body.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <div class="dashboard-shell">
