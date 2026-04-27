<?php
declare(strict_types=1);

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

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        $basePath = app_base_path();
        $normalizedPath = trim($path, '/');

        if ($normalizedPath === '') {
            return $basePath === '' ? '/' : $basePath . '/';
        }

        return ($basePath === '' ? '' : $basePath) . '/' . $normalizedPath;
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

        return $normalized;
    }
}

if (!function_exists('app_role_folder_map')) {
    function app_role_folder_map(): array
    {
        return [
            'SUPER_ADMIN' => 'SUPER-ADMIN',
            'SUPERADMIN' => 'SUPER-ADMIN',
            'ORED' => 'ORED',
            'RECORDS_UNIT' => 'RECORS-UNIT',
            'PACDO' => 'RECORS-UNIT',
            'PENRO' => 'PENRO',
            'CENRO' => 'CENRO',
            'PASU' => 'PASU',
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
            'ORED',
            'SUPER_ADMIN',
            'RECORDS_UNIT',
            'PENRO',
            'CENRO',
            'PASU',
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
            'APPROVE',
            'OVERRIDE',
            'RELEASE',
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
        $pilotRoleRaw = (string)(getenv('EDATS_OFFLINE_PILOT_ROLE') ?: 'RECORDS_UNIT');
        $pilotRoleKey = app_normalize_role_key($pilotRoleRaw);
        if ($pilotRoleKey === '') {
            $pilotRoleKey = 'RECORDS_UNIT';
        }

        // Comma-separated role keys (or '*' for all roles).
        $workflowRolesEnv = getenv('EDATS_OFFLINE_WORKFLOW_ROLES');
        $workflowRoleCsv = $workflowRolesEnv === false ? $pilotRoleKey : (string)$workflowRolesEnv;
        $workflowRoles = app_offline_parse_role_allowlist($workflowRoleCsv, [$pilotRoleKey], false);

        $intakeRolesEnv = getenv('EDATS_OFFLINE_INTAKE_ROLES');
        $intakeRoleCsv = $intakeRolesEnv === false ? '*' : (string)$intakeRolesEnv;
        $intakeRoles = app_offline_parse_role_allowlist($intakeRoleCsv, $defaultRoles, true);

        $readCacheRolesEnv = getenv('EDATS_OFFLINE_READ_CACHE_ROLES');
        $readCacheRoleCsv = $readCacheRolesEnv === false ? '*' : (string)$readCacheRolesEnv;
        $readCacheRoles = app_offline_parse_role_allowlist($readCacheRoleCsv, $defaultRoles, true);

        $workflowActionsEnv = getenv('EDATS_OFFLINE_WORKFLOW_ACTIONS');
        $workflowActionCsv = $workflowActionsEnv === false ? '' : (string)$workflowActionsEnv;
        $workflowActions = app_offline_parse_action_allowlist(
            $workflowActionCsv,
            app_offline_default_workflow_action_keys()
        );

        $syncLogEnabledRaw = getenv('EDATS_OFFLINE_SYNC_LOG_ENABLED');
        $syncLogEnabled = true;
        if ($syncLogEnabledRaw !== false) {
            $normalized = strtolower(trim((string)$syncLogEnabledRaw));
            $syncLogEnabled = !in_array($normalized, ['0', 'false', 'no', 'off'], true);
        }

        $rolloutStageRaw = (string)(getenv('EDATS_OFFLINE_ROLLOUT_STAGE') ?: 'pilot');
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
