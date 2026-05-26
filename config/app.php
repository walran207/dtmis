<?php
declare(strict_types=1);

if (!function_exists('app_storage_path')) {
    function app_storage_path(string $path = ''): string
    {
        $storageRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'storage';
        $normalizedPath = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), '\\/');

        if ($normalizedPath === '') {
            return $storageRoot;
        }

        return $storageRoot . DIRECTORY_SEPARATOR . $normalizedPath;
    }
}

if (!function_exists('app_session_heartbeat_storage_path')) {
    function app_session_heartbeat_storage_path(string $path = ''): string
    {
        $heartbeatRoot = app_storage_path('session-heartbeats');
        $normalizedPath = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), '\\/');

        if ($normalizedPath === '') {
            return $heartbeatRoot;
        }

        return $heartbeatRoot . DIRECTORY_SEPARATOR . $normalizedPath;
    }
}

if (!function_exists('app_session_heartbeat_session_key')) {
    function app_session_heartbeat_session_key(?string $sessionId = null): string
    {
        $rawSessionId = trim((string)($sessionId ?? session_id()));
        $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '_', $rawSessionId) ?? '';
        $sanitized = trim($sanitized, '_');

        return $sanitized !== '' ? $sanitized : 'anonymous';
    }
}

if (!function_exists('app_session_heartbeat_file_path')) {
    function app_session_heartbeat_file_path(?string $sessionId = null): string
    {
        return app_session_heartbeat_storage_path(app_session_heartbeat_session_key($sessionId) . '.json');
    }
}

if (!function_exists('app_touch_session_heartbeat')) {
    function app_touch_session_heartbeat(array $overrides = []): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $userId = isset($overrides['user_id'])
            ? (int)$overrides['user_id']
            : (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $heartbeatDirectory = app_session_heartbeat_storage_path();
        if (!app_prepare_writable_directory($heartbeatDirectory)) {
            return false;
        }

        $now = time();
        $roleName = isset($overrides['role_name'])
            ? (string)$overrides['role_name']
            : (string)($_SESSION['role_name'] ?? '');
        $payload = [
            'session_id' => (string)session_id(),
            'session_key' => app_session_heartbeat_session_key(),
            'user_id' => $userId,
            'username' => isset($overrides['username'])
                ? (string)$overrides['username']
                : (string)($_SESSION['username'] ?? ''),
            'email' => isset($overrides['email'])
                ? (string)$overrides['email']
                : (string)($_SESSION['email'] ?? ''),
            'first_name' => isset($overrides['first_name'])
                ? (string)$overrides['first_name']
                : (string)($_SESSION['first_name'] ?? ''),
            'last_name' => isset($overrides['last_name'])
                ? (string)$overrides['last_name']
                : (string)($_SESSION['last_name'] ?? ''),
            'role_id' => isset($overrides['role_id'])
                ? (int)$overrides['role_id']
                : (int)($_SESSION['role_id'] ?? 0),
            'role_name' => $roleName,
            'role_key' => app_normalize_role_key($roleName),
            'office_id' => isset($overrides['office_id'])
                ? (int)$overrides['office_id']
                : (int)($_SESSION['office_id'] ?? 0),
            'authenticated_at' => isset($overrides['authenticated_at'])
                ? (int)$overrides['authenticated_at']
                : (int)($_SESSION['authenticated_at'] ?? $now),
            'last_seen_at' => $now,
            'last_seen_iso' => gmdate('c', $now),
        ];

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($encoded) || $encoded === '') {
            return false;
        }

        return file_put_contents(app_session_heartbeat_file_path(), $encoded, LOCK_EX) !== false;
    }
}

if (!function_exists('app_clear_session_heartbeat')) {
    function app_clear_session_heartbeat(?string $sessionId = null): bool
    {
        $filePath = app_session_heartbeat_file_path($sessionId);
        if (!is_file($filePath)) {
            return true;
        }

        return @unlink($filePath);
    }
}

if (!function_exists('app_collect_session_heartbeats')) {
    function app_collect_session_heartbeats(int $activeWithinSeconds = 900, int $retentionSeconds = 86400): array
    {
        $heartbeatDirectory = app_session_heartbeat_storage_path();
        if (!is_dir($heartbeatDirectory)) {
            return [];
        }

        $activeCutoff = time() - max(60, $activeWithinSeconds);
        $retentionCutoff = time() - max($activeWithinSeconds, $retentionSeconds);
        $paths = glob($heartbeatDirectory . DIRECTORY_SEPARATOR . '*.json') ?: [];
        $entries = [];

        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }

            $modifiedAt = @filemtime($path);
            if ($modifiedAt !== false && $modifiedAt < $retentionCutoff) {
                @unlink($path);
                continue;
            }

            $raw = @file_get_contents($path);
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }

            $lastSeenAt = (int)($decoded['last_seen_at'] ?? 0);
            if ($lastSeenAt <= 0) {
                $lastSeenAt = $modifiedAt !== false ? (int)$modifiedAt : 0;
            }
            if ($lastSeenAt < $retentionCutoff) {
                @unlink($path);
                continue;
            }
            if ($lastSeenAt < $activeCutoff) {
                continue;
            }

            $sessionId = trim((string)($decoded['session_id'] ?? ''));
            if ($sessionId === '') {
                $sessionId = pathinfo($path, PATHINFO_FILENAME);
            }

            $decoded['session_id'] = $sessionId;
            $decoded['last_seen_at'] = $lastSeenAt;
            $entries[$sessionId] = $decoded;
        }

        return array_values($entries);
    }
}

if (!function_exists('app_project_root_path')) {
    function app_project_root_path(): string
    {
        return dirname(__DIR__);
    }
}

if (!function_exists('app_path_is_absolute')) {
    function app_path_is_absolute(string $path): bool
    {
        $normalized = str_replace('\\', '/', trim($path));
        if ($normalized === '') {
            return false;
        }

        return preg_match('/^[A-Za-z]:\//', $normalized) === 1 || str_starts_with($normalized, '/');
    }
}

if (!function_exists('app_attachment_storage_root')) {
    function app_attachment_storage_root(): string
    {
        return 'C:\\inetpub\\wwwroot\\edats\\edats-attachments';
    }
}

if (!function_exists('app_attachment_storage_path')) {
    function app_attachment_storage_path(string $path = ''): string
    {
        $storageRoot = app_attachment_storage_root();
        $normalizedPath = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), '\\/');

        if ($normalizedPath === '') {
            return $storageRoot;
        }

        return $storageRoot . DIRECTORY_SEPARATOR . $normalizedPath;
    }
}

if (!function_exists('app_attachment_storage_directory')) {
    function app_attachment_storage_directory(?DateTimeInterface $date = null): array
    {
        $effectiveDate = $date ?? new DateTimeImmutable('now');
        $relativeDir = $effectiveDate->format('Y') . '/' . $effectiveDate->format('m');
        $absoluteDir = app_attachment_storage_path($relativeDir);

        if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException('Unable to create attachment storage directory.');
        }

        return [$relativeDir, $absoluteDir];
    }
}

if (!function_exists('app_resolve_attachment_absolute_path')) {
    function app_resolve_attachment_absolute_path(string $storedPath): ?string
    {
        $trimmed = trim($storedPath);
        if ($trimmed === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $trimmed);
        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
            return null;
        }

        $candidates = [];
        if (app_path_is_absolute($normalized)) {
            $candidates[] = $normalized;
        } else {
            $relativePath = ltrim($normalized, '/');
            $candidates[] = app_attachment_storage_path($relativePath);
            $candidates[] = app_project_root_path() . DIRECTORY_SEPARATOR . 'edats-attachments' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $candidates[] = app_project_root_path() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'attachments' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $candidates[] = app_project_root_path() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'attachments' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $candidates[] = app_project_root_path() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $candidates[] = app_project_root_path() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        }

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);
            if ($realPath === false || !is_file($realPath) || !is_readable($realPath)) {
                continue;
            }

            return $realPath;
        }

        return null;
    }
}

if (!function_exists('app_session_save_path_from_ini')) {
    function app_session_save_path_from_ini(string $configuredPath): string
    {
        $normalized = trim($configuredPath);
        if ($normalized === '') {
            return '';
        }

        if (str_contains($normalized, ';')) {
            $segments = explode(';', $normalized);
            $normalized = trim((string)end($segments));
        }

        return trim($normalized, " \t\n\r\0\x0B\"'");
    }
}

if (!function_exists('app_prepare_writable_directory')) {
    function app_prepare_writable_directory(string $directory): bool
    {
        $normalized = trim($directory);
        if ($normalized === '') {
            return false;
        }

        if (!is_dir($normalized) && !@mkdir($normalized, 0775, true) && !is_dir($normalized)) {
            return false;
        }

        return is_writable($normalized);
    }
}

if (!function_exists('app_session_lifetime_seconds')) {
    function app_session_lifetime_seconds(): int
    {
        $raw = trim((string)(getenv('DTMIS_SESSION_LIFETIME_SECONDS') ?: ''));
        if ($raw === '' || !preg_match('/^\d+$/', $raw)) {
            return 28800; // 8 hours
        }

        return max(900, min(2592000, (int)$raw));
    }
}

if (!function_exists('app_configure_session_runtime')) {
    function app_configure_session_runtime(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetimeSeconds = app_session_lifetime_seconds();
        ini_set('session.gc_maxlifetime', (string)$lifetimeSeconds);
        ini_set('session.cookie_lifetime', (string)$lifetimeSeconds);
    }
}

if (!function_exists('app_configure_session_storage')) {
    function app_configure_session_storage(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $configuredPath = app_session_save_path_from_ini((string)ini_get('session.save_path'));
        if ($configuredPath !== '' && is_dir($configuredPath) && is_writable($configuredPath)) {
            return;
        }

        $candidates = [];

        $environmentPath = trim((string)(getenv('DTMIS_SESSION_PATH') ?: ''));
        if ($environmentPath !== '') {
            $candidates[] = $environmentPath;
        }

        $candidates[] = app_storage_path('sessions');

        $systemTemp = rtrim((string)sys_get_temp_dir(), '\\/');
        if ($systemTemp !== '') {
            $candidates[] = $systemTemp . DIRECTORY_SEPARATOR . 'DTMIS-sessions';
            $candidates[] = $systemTemp;
        }

        foreach ($candidates as $candidate) {
            if (!app_prepare_writable_directory($candidate)) {
                continue;
            }

            ini_set('session.save_path', $candidate);
            return;
        }
    }
}

// Apply runtime-safe session timeout defaults before starting any session.
app_configure_session_runtime();
// Protect deployments where PHP still points session.save_path to a missing local-dev directory.
app_configure_session_storage();

if (!function_exists('app_normalize_path')) {
    function app_normalize_path(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}

if (!function_exists('app_base_path')) {
    function app_base_path(): string
    {
        static $basePath = null;

        if ($basePath !== null) {
            return $basePath;
        }

        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptFilename = (string)($_SERVER['SCRIPT_FILENAME'] ?? '');
        $scriptDirUrl = str_replace('\\', '/', dirname($scriptName));
        $scriptDirUrl = $scriptDirUrl === '/' ? '' : rtrim($scriptDirUrl, '/');

        $appRoot = realpath(__DIR__ . '/..');
        $scriptDirFs = realpath(dirname($scriptFilename));

        if ($appRoot === false || $scriptDirFs === false) {
            $basePath = $scriptDirUrl;
            return $basePath;
        }

        $appRootNormalized = app_normalize_path($appRoot);
        $scriptDirNormalized = app_normalize_path($scriptDirFs);
        $relativeDir = '';

        if (str_starts_with($scriptDirNormalized, $appRootNormalized)) {
            $relativeDir = trim(substr($scriptDirNormalized, strlen($appRootNormalized)), '/');
        }

        if ($relativeDir !== '') {
            $suffix = '/' . str_replace('\\', '/', $relativeDir);
            if (str_ends_with($scriptDirUrl, $suffix)) {
                $scriptDirUrl = substr($scriptDirUrl, 0, -strlen($suffix));
            }
        }

        $basePath = $scriptDirUrl;

        return $basePath;
    }
}

if (!function_exists('app_phpless_role_folders')) {
    function app_phpless_role_folders(): array
    {
        return [
            'ADMIN',
            'ARD-MS',
            'ARD-TS',
            'CENRO-ADMIN-RECORD',
            'CENRO-OFFICER',
            'CENRO-SECTION',
            'CENRO-UNIT',
            'DIVISION-CHIEF',
            'ORED',
            'PACDO',
            'PAMO-ADMIN',
            'PAMO-UNIT',
            'PASU-OFFICER',
            'PENRO-ADMIN-RECORD',
            'PENRO-DIVISION',
            'PENRO-OFFICER',
            'PENRO-SECTION',
            'PENRO-SECTION-UNIT',
            'RECORS-UNIT',
            'SECTION-STAFF',
            'SUPER-ADMIN',
        ];
    }
}

if (!function_exists('app_should_hide_php_extension')) {
    function app_should_hide_php_extension(string $path): bool
    {
        $normalizedPath = trim(str_replace('\\', '/', $path), '/');
        if ($normalizedPath === '' || !preg_match('/\.php$/i', $normalizedPath)) {
            return false;
        }

        if (preg_match('/^auth\/[A-Za-z0-9_-]+\.php$/i', $normalizedPath) === 1) {
            return true;
        }

        if (preg_match('/^(dashboard|notifications|print-package|softcopy-digital-signature|softcopy-qr-stamp|softcopy-records-unit-stamp|support-center|tracking-slip)\.php$/i', $normalizedPath) === 1) {
            return true;
        }

        $roleFolders = array_map(
            static fn(string $folder): string => preg_quote($folder, '/'),
            app_phpless_role_folders()
        );
        $rolePattern = '/^(?:' . implode('|', $roleFolders) . ')\/[A-Za-z0-9_-]+\.php$/i';

        return preg_match($rolePattern, $normalizedPath) === 1;
    }
}

if (!function_exists('app_public_route_path')) {
    function app_public_route_path(string $path): string
    {
        $trimmedPath = trim($path);
        if ($trimmedPath === '') {
            return '';
        }

        $fragment = '';
        $fragmentOffset = strpos($trimmedPath, '#');
        if ($fragmentOffset !== false) {
            $fragment = substr($trimmedPath, $fragmentOffset);
            $trimmedPath = substr($trimmedPath, 0, $fragmentOffset);
        }

        $query = '';
        $queryOffset = strpos($trimmedPath, '?');
        if ($queryOffset !== false) {
            $query = substr($trimmedPath, $queryOffset);
            $trimmedPath = substr($trimmedPath, 0, $queryOffset);
        }

        $normalizedPath = trim(str_replace('\\', '/', $trimmedPath), '/');
        if ($normalizedPath !== '' && app_should_hide_php_extension($normalizedPath)) {
            $normalizedPath = substr($normalizedPath, 0, -4);
        }

        return $normalizedPath . $query . $fragment;
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        $basePath = app_base_path();
        $normalizedPath = app_public_route_path($path);
        $normalizedPath = trim($normalizedPath, '/');

        if ($normalizedPath === '') {
            return $basePath === '' ? '/' : $basePath . '/';
        }

        return ($basePath === '' ? '' : $basePath) . '/' . $normalizedPath;
    }
}

if (!function_exists('app_request_origin')) {
    function app_request_origin(): string
    {
        $scheme = 'http';
        $https = strtolower(trim((string)($_SERVER['HTTPS'] ?? '')));
        if (($https !== '' && $https !== 'off' && $https !== '0')
            || strtolower(trim((string)($_SERVER['REQUEST_SCHEME'] ?? ''))) === 'https'
            || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            $scheme = 'https';
        }

        $host = trim((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost'));
        if ($host === '') {
            $host = 'localhost';
        }

        return $scheme . '://' . $host;
    }
}

if (!function_exists('app_public_base_url')) {
    function app_public_base_url(): string
    {
        static $publicBaseUrl = null;

        if ($publicBaseUrl !== null) {
            return $publicBaseUrl;
        }

        $configured = trim((string)(getenv('DTMIS_PUBLIC_BASE_URL') ?: ''));
        if ($configured === '') {
            $configured = 'https://www.denrtwelve.com/dtmis';
        }
        $configured = rtrim($configured, '/');
        if ($configured !== '') {
            $publicBaseUrl = $configured;
            return $publicBaseUrl;
        }

        $basePath = app_base_path();
        $publicBaseUrl = rtrim(app_request_origin() . ($basePath === '' ? '' : $basePath), '/');
        return $publicBaseUrl;
    }
}

if (!function_exists('app_public_url')) {
    function app_public_url(string $path = ''): string
    {
        $baseUrl = app_public_base_url();
        $normalizedPath = trim(app_public_route_path($path), '/');

        if ($normalizedPath === '') {
            return $baseUrl . '/';
        }

        return rtrim($baseUrl, '/') . '/' . $normalizedPath;
    }
}

if (!function_exists('app_redirect')) {
    function app_redirect(string $path): never
    {
        header('Location: ' . app_url($path));
        exit;
    }
}

if (!function_exists('app_normalize_role_key')) {
    function app_normalize_role_key(string $roleName): string
    {
        $normalized = strtoupper(trim($roleName));
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        // Backward-compatible alias: PACDO now maps to RECORDS-UNIT.
        if ($normalized === 'PACDO' || $normalized === 'RECORDSUNIT') {
            return 'RECORDS_UNIT';
        }
        // Transitional alias: PENRO section-unit accounts share the PENRO section workspace.
        if ($normalized === 'PENRO_SECTIONUNIT') {
            return 'PENRO_SECTION_UNIT';
        }
        if ($normalized === 'OREDSIGN') {
            return 'ORED_SIGN';
        }

        return $normalized;
    }
}

if (!function_exists('app_role_behavior_key')) {
    function app_role_behavior_key(string $roleName): string
    {
        $roleKey = app_normalize_role_key($roleName);
        $map = [
            'CENRO_ADMIN_RECORD' => 'RECORDS_UNIT',
            'CENRO_OFFICER' => 'ORED',
            'CENRO_SECTION' => 'DIVISION_CHIEF',
            'CENRO_UNIT' => 'SECTION_STAFF',
            'ADMIN' => 'ADMIN',
            'PAMO_ADMIN' => 'RECORDS_UNIT',
            'PASU_OFFICER' => 'ORED',
            'PAMO_UNIT' => 'SECTION_STAFF',
            'PENRO_ADMIN_RECORD' => 'RECORDS_UNIT',
            'PENRO_OFFICER' => 'ORED',
            'ORED_SIGN' => 'ORED',
            'PENRO_DIVISION' => 'DIVISION_CHIEF',
            'PENRO_SECTION' => 'SECTION_STAFF',
            'PENRO_SECTION_UNIT' => 'SECTION_STAFF',
        ];

        return $map[$roleKey] ?? $roleKey;
    }
}

if (!function_exists('app_role_folder_map')) {
    function app_role_folder_map(): array
    {
        return [
            'ADMIN' => 'ADMIN',
            'SUPER_ADMIN' => 'SUPER-ADMIN',
            'SUPERADMIN' => 'SUPER-ADMIN',
            'ORED' => 'ORED',
            'ORED_SIGN' => 'ORED',
            'RECORDS_UNIT' => 'RECORS-UNIT',
            'PACDO' => 'RECORS-UNIT',
            'CENRO_ADMIN_RECORD' => 'CENRO-ADMIN-RECORD',
            'PENRO_ADMIN_RECORD' => 'PENRO-ADMIN-RECORD',
            'PAMO_ADMIN' => 'PAMO-ADMIN',
            'PASU_OFFICER' => 'PASU-OFFICER',
            'PAMO_UNIT' => 'PAMO-UNIT',
            'CENRO_OFFICER' => 'CENRO-OFFICER',
            'PENRO_OFFICER' => 'PENRO-OFFICER',
            'CENRO_SECTION' => 'CENRO-SECTION',
            'PENRO_DIVISION' => 'PENRO-DIVISION',
            'CENRO_UNIT' => 'CENRO-UNIT',
            'PENRO_SECTION' => 'PENRO-SECTION',
            'PENRO_SECTION_UNIT' => 'PENRO-SECTION',
            'SECTION' => 'SECTION-STAFF',
            'SECTION_STAFF' => 'SECTION-STAFF',
            'DIVISION' => 'DIVISION-CHIEF',
            'DIVISION_CHIEF' => 'DIVISION-CHIEF',
            'ARD_TS' => 'ARD-TS',
            'ARD_MS' => 'ARD-MS',
            'CHIEF' => 'DIVISION-CHIEF',
        ];
    }
}

if (!function_exists('app_role_folder_from_role')) {
    function app_role_folder_from_role(?string $roleName): ?string
    {
        if ($roleName === null || trim($roleName) === '') {
            return null;
        }

        $roleKey = app_normalize_role_key($roleName);
        $map = app_role_folder_map();

        return $map[$roleKey] ?? null;
    }
}

if (!function_exists('app_role_dashboard_path')) {
    function app_role_dashboard_path(?string $roleName): string
    {
        $folder = app_role_folder_from_role($roleName);
        if ($folder === null) {
            return 'auth/login.php';
        }

        return $folder . '/dashboard.php';
    }
}

if (!function_exists('app_redirect_to_role_dashboard')) {
    function app_redirect_to_role_dashboard(?string $roleName): never
    {
        app_redirect(app_role_dashboard_path($roleName));
    }
}

if (!function_exists('app_require_role_folder_access')) {
    function app_require_role_folder_access(string $requiredFolder): void
    {
        if (empty($_SESSION['user_id'])) {
            app_redirect('auth/login.php');
        }

        $expected = strtoupper(trim($requiredFolder));
        $actual = app_role_folder_from_role((string)($_SESSION['role_name'] ?? ''));

        if ($actual === null) {
            app_redirect('auth/logout.php');
        }

        if (strtoupper($actual) !== $expected) {
            app_redirect(app_role_dashboard_path((string)($_SESSION['role_name'] ?? '')));
        }
    }
}

if (!function_exists('app_offline_default_role_keys')) {
    function app_offline_default_role_keys(): array
    {
        return [
            'ADMIN',
            'ORED',
            'ORED_SIGN',
            'SUPER_ADMIN',
            'RECORDS_UNIT',
            'CENRO_ADMIN_RECORD',
            'PENRO_ADMIN_RECORD',
            'PAMO_ADMIN',
            'PASU_OFFICER',
            'CENRO_OFFICER',
            'PENRO_OFFICER',
            'CENRO_SECTION',
            'PENRO_DIVISION',
            'CENRO_UNIT',
            'PENRO_SECTION',
            'PENRO_SECTION_UNIT',
            'PAMO_UNIT',
            'DIVISION_CHIEF',
            'SECTION_STAFF',
            'ARD_TS',
            'ARD_MS',
        ];
    }
}

if (!function_exists('app_offline_default_workflow_action_keys')) {
    function app_offline_default_workflow_action_keys(): array
    {
        return [
            'RECEIVE',
            'FORWARD',
            'REROUTE',
            'RETURN',
            'OVERRIDE',
            'RELEASE',
            'COMPLETE',
            'UNSIGN',
            'PENDING',
            'EDIT',
            'DELETE',
            'DELETE_ATTACHMENT',
        ];
    }
}

if (!function_exists('app_offline_parse_csv_set')) {
    function app_offline_parse_csv_set(string $raw): array
    {
        $items = [];
        foreach (explode(',', $raw) as $segment) {
            $value = strtoupper(trim($segment));
            if ($value === '') {
                continue;
            }
            $items[$value] = true;
        }

        return array_keys($items);
    }
}

if (!function_exists('app_offline_parse_role_allowlist')) {
    function app_offline_parse_role_allowlist(string $raw, array $defaultRoles, bool $defaultAllowAll = false): array
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return [
                'allow_all' => $defaultAllowAll,
                'roles' => array_values(array_unique(array_map('app_normalize_role_key', $defaultRoles))),
            ];
        }

        $normalized = app_offline_parse_csv_set($trimmed);
        if (in_array('*', $normalized, true) || in_array('ALL', $normalized, true)) {
            return ['allow_all' => true, 'roles' => []];
        }

        $roles = [];
        foreach ($normalized as $value) {
            $roleKey = app_normalize_role_key($value);
            if ($roleKey === '') {
                continue;
            }
            $roles[$roleKey] = true;
        }

        return [
            'allow_all' => false,
            'roles' => array_keys($roles),
        ];
    }
}

if (!function_exists('app_offline_parse_action_allowlist')) {
    function app_offline_parse_action_allowlist(string $raw, array $defaultActions): array
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return array_values(array_unique(array_map(static function (string $value): string {
                return strtoupper(trim($value));
            }, $defaultActions)));
        }

        $normalized = app_offline_parse_csv_set($trimmed);
        if (in_array('*', $normalized, true) || in_array('ALL', $normalized, true)) {
            return [];
        }

        return array_values(array_unique(array_map(static function (string $value): string {
            return strtoupper(trim($value));
        }, $normalized)));
    }
}

if (!function_exists('app_offline_role_is_allowed')) {
    function app_offline_role_is_allowed(string $roleKey, array $allowlist): bool
    {
        if (!empty($allowlist['allow_all'])) {
            return true;
        }

        if ($roleKey === '') {
            return false;
        }

        $roles = is_array($allowlist['roles'] ?? null) ? $allowlist['roles'] : [];
        return in_array($roleKey, $roles, true);
    }
}

if (!function_exists('app_offline_rollout_settings')) {
    function app_offline_rollout_settings(): array
    {
        static $settings = null;
        if (is_array($settings)) {
            return $settings;
        }

        $defaultRoles = app_offline_default_role_keys();
        $pilotRoleRaw = (string)(getenv('DTMIS_OFFLINE_PILOT_ROLE') ?: 'RECORDS_UNIT');
        $pilotRoleKey = app_normalize_role_key($pilotRoleRaw);
        if ($pilotRoleKey === '') {
            $pilotRoleKey = 'RECORDS_UNIT';
        }

        // Comma-separated role keys (or '*' for all roles).
        $workflowRolesEnv = getenv('DTMIS_OFFLINE_WORKFLOW_ROLES');
        $workflowRoleCsv = $workflowRolesEnv === false ? $pilotRoleKey : (string)$workflowRolesEnv;
        $workflowRoles = app_offline_parse_role_allowlist($workflowRoleCsv, [$pilotRoleKey], false);

        $intakeRolesEnv = getenv('DTMIS_OFFLINE_INTAKE_ROLES');
        $intakeRoleCsv = $intakeRolesEnv === false ? '*' : (string)$intakeRolesEnv;
        $intakeRoles = app_offline_parse_role_allowlist($intakeRoleCsv, $defaultRoles, true);

        $readCacheRolesEnv = getenv('DTMIS_OFFLINE_READ_CACHE_ROLES');
        $readCacheRoleCsv = $readCacheRolesEnv === false ? '*' : (string)$readCacheRolesEnv;
        $readCacheRoles = app_offline_parse_role_allowlist($readCacheRoleCsv, $defaultRoles, true);

        $workflowActionsEnv = getenv('DTMIS_OFFLINE_WORKFLOW_ACTIONS');
        $workflowActionCsv = $workflowActionsEnv === false ? '' : (string)$workflowActionsEnv;
        $workflowActions = app_offline_parse_action_allowlist(
            $workflowActionCsv,
            app_offline_default_workflow_action_keys()
        );

        $syncLogEnabledRaw = getenv('DTMIS_OFFLINE_SYNC_LOG_ENABLED');
        $syncLogEnabled = true;
        if ($syncLogEnabledRaw !== false) {
            $normalized = strtolower(trim((string)$syncLogEnabledRaw));
            $syncLogEnabled = !in_array($normalized, ['0', 'false', 'no', 'off'], true);
        }

        $rolloutStageRaw = (string)(getenv('DTMIS_OFFLINE_ROLLOUT_STAGE') ?: 'pilot');
        $rolloutStage = strtolower(trim($rolloutStageRaw));
        if ($rolloutStage === '') {
            $rolloutStage = 'pilot';
        }

        $settings = [
            'phase' => 'phase7',
            'rollout_stage' => $rolloutStage,
            'pilot_role_key' => $pilotRoleKey,
            'read_cache_roles' => $readCacheRoles,
            'intake_outbox_roles' => $intakeRoles,
            'workflow_outbox_roles' => $workflowRoles,
            'workflow_action_allowlist' => $workflowActions,
            'sync_log_enabled' => $syncLogEnabled,
        ];

        return $settings;
    }
}

if (!function_exists('app_offline_is_workflow_action_allowed')) {
    function app_offline_is_workflow_action_allowed(?string $action): bool
    {
        $actionKey = strtoupper(trim((string)$action));
        if ($actionKey === '') {
            return false;
        }

        $allowlist = app_offline_rollout_settings()['workflow_action_allowlist'] ?? [];
        if (!is_array($allowlist) || empty($allowlist)) {
            return true;
        }

        return in_array($actionKey, $allowlist, true);
    }
}

if (!function_exists('app_offline_policy_for_role')) {
    function app_offline_policy_for_role(?string $roleName): array
    {
        $roleKey = app_normalize_role_key((string)$roleName);
        $settings = app_offline_rollout_settings();

        $readCacheEnabled = app_offline_role_is_allowed($roleKey, $settings['read_cache_roles'] ?? []);
        $intakeOutboxEnabled = app_offline_role_is_allowed($roleKey, $settings['intake_outbox_roles'] ?? []);
        $workflowOutboxEnabled = app_offline_role_is_allowed($roleKey, $settings['workflow_outbox_roles'] ?? []);

        return [
            'phase' => (string)($settings['phase'] ?? 'phase7'),
            'rollout_stage' => (string)($settings['rollout_stage'] ?? 'pilot'),
            'pilot_role_key' => (string)($settings['pilot_role_key'] ?? 'RECORDS_UNIT'),
            'role_key' => $roleKey,
            'read_cache_enabled' => $readCacheEnabled,
            'intake_outbox_enabled' => $intakeOutboxEnabled,
            'workflow_outbox_enabled' => $workflowOutboxEnabled,
            'workflow_allowed_actions' => array_values((array)($settings['workflow_action_allowlist'] ?? [])),
            'sync_log_enabled' => (bool)($settings['sync_log_enabled'] ?? true),
        ];
    }
}

if (!function_exists('app_offline_is_intake_outbox_enabled')) {
    function app_offline_is_intake_outbox_enabled(?string $roleName): bool
    {
        $policy = app_offline_policy_for_role($roleName);
        return (bool)($policy['intake_outbox_enabled'] ?? false);
    }
}

if (!function_exists('app_offline_is_workflow_outbox_enabled')) {
    function app_offline_is_workflow_outbox_enabled(?string $roleName, ?string $action = null): bool
    {
        $policy = app_offline_policy_for_role($roleName);
        if (!(bool)($policy['workflow_outbox_enabled'] ?? false)) {
            return false;
        }

        if ($action === null || trim((string)$action) === '') {
            return true;
        }

        return app_offline_is_workflow_action_allowed($action);
    }
}

if (!function_exists('app_offline_sync_logging_enabled')) {
    function app_offline_sync_logging_enabled(): bool
    {
        $settings = app_offline_rollout_settings();
        return (bool)($settings['sync_log_enabled'] ?? true);
    }
}
