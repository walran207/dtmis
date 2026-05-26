<?php
declare(strict_types=1);

header('Content-Type: application/json');

echo json_encode([
    'php_version' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'loaded_ini' => php_ini_loaded_file(),
    'extension_dir' => ini_get('extension_dir'),
    'session_save_path' => ini_get('session.save_path'),
    'sys_temp_dir' => sys_get_temp_dir(),
    'session_status' => session_status(),
    'db_driver_env' => getenv('DB_DRIVER') ?: getenv('DB_CONNECTION') ?: null,
    'pdo_drivers' => PDO::getAvailableDrivers(),
    'extensions' => [
        'sqlsrv' => extension_loaded('sqlsrv'),
        'pdo_sqlsrv' => extension_loaded('pdo_sqlsrv'),
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
