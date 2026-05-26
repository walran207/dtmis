<?php
declare(strict_types=1);

if (!function_exists('admin_current_role_key')) {
    function admin_current_role_key(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        $roleName = (string)($_SESSION['role_name'] ?? '');
        if (function_exists('app_normalize_role_key')) {
            return app_normalize_role_key($roleName);
        }

        $normalized = strtoupper(trim($roleName));
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized) ?? '';
        return trim($normalized, '_');
    }
}

if (!function_exists('admin_has_access')) {
    function admin_has_access(): bool
    {
        return admin_current_role_key() === 'ADMIN';
    }
}

if (!function_exists('admin_normalize_office_row')) {
    function admin_normalize_office_row(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => trim((string)($row['name'] ?? '')),
            'level' => strtoupper(trim((string)($row['level'] ?? ''))),
            'parent_office_id' => (int)($row['parent_office_id'] ?? 0),
        ];
    }
}

if (!function_exists('admin_fetch_offices_index')) {
    function admin_fetch_offices_index(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT id, name, level, parent_office_id FROM offices ORDER BY id ASC')?->fetchAll() ?: [];
        $result = [];
        foreach ($rows as $row) {
            $office = admin_normalize_office_row((array)$row);
            if ($office['id'] <= 0) {
                continue;
            }
            $result[$office['id']] = $office;
        }

        return $result;
    }
}

if (!function_exists('admin_build_children_index')) {
    function admin_build_children_index(array $officesById): array
    {
        $children = [];
        foreach ($officesById as $office) {
            $parentId = (int)($office['parent_office_id'] ?? 0);
            if ($parentId <= 0) {
                continue;
            }
            if (!isset($children[$parentId])) {
                $children[$parentId] = [];
            }
            $children[$parentId][] = (int)$office['id'];
        }

        return $children;
    }
}

if (!function_exists('admin_collect_descendant_office_ids')) {
    function admin_collect_descendant_office_ids(array $childrenIndex, int $rootOfficeId): array
    {
        if ($rootOfficeId <= 0) {
            return [];
        }

        $queue = [$rootOfficeId];
        $seen = [];

        while (!empty($queue)) {
            $officeId = (int)array_shift($queue);
            if ($officeId <= 0 || isset($seen[$officeId])) {
                continue;
            }
            $seen[$officeId] = true;

            foreach ($childrenIndex[$officeId] ?? [] as $childId) {
                if (!isset($seen[$childId])) {
                    $queue[] = (int)$childId;
                }
            }
        }

        $officeIds = array_map('intval', array_keys($seen));
        sort($officeIds, SORT_NUMERIC);
        return $officeIds;
    }
}

if (!function_exists('admin_build_office_lineage')) {
    function admin_build_office_lineage(array $officesById, int $officeId): array
    {
        $lineage = [];
        $visited = [];
        $cursor = $officeId;

        while ($cursor > 0 && isset($officesById[$cursor]) && !isset($visited[$cursor])) {
            $visited[$cursor] = true;
            $lineage[] = $officesById[$cursor];
            $cursor = (int)($officesById[$cursor]['parent_office_id'] ?? 0);
        }

        return $lineage;
    }
}

if (!function_exists('admin_is_manageable_role_key')) {
    function admin_is_manageable_role_key(string $roleName): bool
    {
        $roleKey = function_exists('app_normalize_role_key')
            ? app_normalize_role_key($roleName)
            : strtoupper(trim($roleName));

        return !in_array($roleKey, ['SUPER_ADMIN', 'ADMIN'], true);
    }
}

if (!function_exists('admin_sql_placeholders')) {
    function admin_sql_placeholders(array $values, string $prefix): array
    {
        $params = [];
        $placeholders = [];
        $index = 0;

        foreach ($values as $value) {
            $key = ':' . $prefix . $index;
            $params[$key] = $value;
            $placeholders[] = $key;
            $index += 1;
        }

        return ['placeholders' => $placeholders, 'params' => $params];
    }
}

if (!function_exists('admin_format_datetime')) {
    function admin_format_datetime(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '-';
        }

        try {
            return (new DateTimeImmutable($value))->format('M d, Y h:i A');
        } catch (Throwable $exception) {
            return '-';
        }
    }
}

if (!function_exists('admin_hidden_account_emails')) {
    function admin_hidden_account_emails(): array
    {
        static $emails = null;
        if (is_array($emails)) {
            return $emails;
        }

        $emails = [
            'seed.ard.ms.50@denr.gov.ph',
            'seed.ard.ts.49@denr.gov.ph',
            'seed.cenro.admin.record.51@denr.gov.ph',
            'seed.cenro.officer.66@denr.gov.ph',
            'seed.cenro.section.81@denr.gov.ph',
            'seed.cenro.section.82@denr.gov.ph',
            'seed.cenro.section.83@denr.gov.ph',
            'seed.cenro.section.84@denr.gov.ph',
            'seed.cenro.unit.144@denr.gov.ph',
            'seed.cenro.unit.145@denr.gov.ph',
            'seed.cenro.unit.146@denr.gov.ph',
            'seed.cenro.unit.147@denr.gov.ph',
            'seed.cenro.unit.148@denr.gov.ph',
            'seed.cenro.unit.149@denr.gov.ph',
            'seed.cenro.unit.150@denr.gov.ph',
            'seed.cenro.unit.151@denr.gov.ph',
            'seed.division.chief.10@denr.gov.ph',
            'seed.division.chief.11@denr.gov.ph',
            'seed.division.chief.12@denr.gov.ph',
            'seed.division.chief.13@denr.gov.ph',
            'seed.division.chief.14@denr.gov.ph',
            'seed.division.chief.15@denr.gov.ph',
            'seed.division.chief.3@denr.gov.ph',
            'seed.division.chief.9@denr.gov.ph',
            'seed.ored.1@denr.gov.ph',
            'seed.ored.sign.1@denr.gov.ph',
            'seed.pacdo.2@denr.gov.ph',
            'seed.pamo.admin.357@denr.gov.ph',
            'seed.pamo.unit.358@denr.gov.ph',
            'seed.pamo.unit.359@denr.gov.ph',
            'seed.pamo.unit.360@denr.gov.ph',
            'seed.pasu.officer.356@denr.gov.ph',
            'seed.penro.admin.record.208@denr.gov.ph',
            'seed.penro.division.222@denr.gov.ph',
            'seed.penro.division.223@denr.gov.ph',
            'walran207@gmail.com',
            'seed.penro.section.239@denr.gov.ph',
            'seed.penro.section.240@denr.gov.ph',
            'seed.penro.section.241@denr.gov.ph',
            'seed.penro.section.242@denr.gov.ph',
            'seed.penro.section.243@denr.gov.ph',
            'seed.penro.section.320@denr.gov.ph',
            'seed.penro.section.321@denr.gov.ph',
            'seed.penro.section.322@denr.gov.ph',
            'seed.penro.section.323@denr.gov.ph',
            'seed.penro.section.324@denr.gov.ph',
            'seed.penro.section.325@denr.gov.ph',
            'seed.penro.section.326@denr.gov.ph',
            'seed.penro.section.327@denr.gov.ph',
            'seed.penro.section.328@denr.gov.ph',
            'seed.penro.section.unit.237@denr.gov.ph',
            'seed.penro.section.unit.238@denr.gov.ph',
            'seed.penro.section.unit.244@denr.gov.ph',
            'seed.penro.section.unit.245@denr.gov.ph',
            'seed.penro.section.unit.300@denr.gov.ph',
            'seed.penro.section.unit.301@denr.gov.ph',
            'seed.penro.section.unit.302@denr.gov.ph',
            'seed.penro.section.unit.303@denr.gov.ph',
            'seed.penro.section.unit.304@denr.gov.ph',
            'seed.section.staff.16@denr.gov.ph',
            'seed.section.staff.17@denr.gov.ph',
            'seed.section.staff.18@denr.gov.ph',
            'seed.section.staff.19@denr.gov.ph',
            'seed.section.staff.20@denr.gov.ph',
            'seed.section.staff.21@denr.gov.ph',
            'seed.section.staff.22@denr.gov.ph',
            'seed.section.staff.23@denr.gov.ph',
            'seed.section.staff.24@denr.gov.ph',
            'seed.section.staff.25@denr.gov.ph',
            'seed.section.staff.26@denr.gov.ph',
            'seed.section.staff.27@denr.gov.ph',
            'seed.section.staff.28@denr.gov.ph',
            'seed.section.staff.29@denr.gov.ph',
            'seed.section.staff.30@denr.gov.ph',
            'seed.section.staff.31@denr.gov.ph',
            'seed.section.staff.32@denr.gov.ph',
            'seed.section.staff.33@denr.gov.ph',
            'seed.section.staff.34@denr.gov.ph',
            'seed.section.staff.35@denr.gov.ph',
            'seed.section.staff.36@denr.gov.ph',
            'seed.section.staff.37@denr.gov.ph',
            'seed.section.staff.38@denr.gov.ph',
            'seed.section.staff.39@denr.gov.ph',
            'seed.section.staff.40@denr.gov.ph',
        ];

        $emails = array_values(array_unique(array_map(
            static fn(string $email): string => strtolower(trim($email)),
            $emails
        )));

        return $emails;
    }
}

if (!function_exists('admin_email_is_hidden')) {
    function admin_email_is_hidden(string $email): bool
    {
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail === '') {
            return false;
        }

        return in_array($normalizedEmail, admin_hidden_account_emails(), true);
    }
}

if (!function_exists('admin_resolve_scope')) {
    function admin_resolve_scope(PDO $pdo, ?int $officeId = null): array
    {
        $currentOfficeId = $officeId ?? (int)($_SESSION['office_id'] ?? 0);
        $officesById = admin_fetch_offices_index($pdo);
        if ($currentOfficeId <= 0 || !isset($officesById[$currentOfficeId])) {
            return [
                'scope_type' => 'DIRECT',
                'assigned_office' => null,
                'root_office' => null,
                'penro_office' => null,
                'include_pamo' => false,
                'allowed_office_ids' => [],
                'allowed_offices' => [],
                'summary_label' => 'No office scope assigned',
                'office_count' => 0,
            ];
        }

        $childrenIndex = admin_build_children_index($officesById);
        $lineage = admin_build_office_lineage($officesById, $currentOfficeId);

        $assignedOffice = $officesById[$currentOfficeId];
        $rootOffice = $assignedOffice;
        $scopeType = 'DIRECT';
        foreach ($lineage as $office) {
            if ((string)($office['level'] ?? '') === 'COMMUNITY') {
                $rootOffice = $office;
                $scopeType = 'CENRO';
                break;
            }
        }
        if ($scopeType !== 'CENRO') {
            foreach ($lineage as $office) {
                if ((string)($office['level'] ?? '') === 'PROVINCIAL') {
                    $rootOffice = $office;
                    $scopeType = 'PENRO';
                    break;
                }
            }
        }

        $penroOffice = null;
        foreach ($lineage as $office) {
            if ((string)($office['level'] ?? '') === 'PROVINCIAL') {
                $penroOffice = $office;
                break;
            }
        }

        $allowedOfficeIds = admin_collect_descendant_office_ids($childrenIndex, (int)$rootOffice['id']);
        $includePamo = false;
        if ($scopeType === 'CENRO' && $penroOffice !== null) {
            foreach ($officesById as $office) {
                if ((int)($office['parent_office_id'] ?? 0) !== (int)$penroOffice['id']) {
                    continue;
                }
                if ((string)($office['level'] ?? '') !== 'PROTECTED AREA') {
                    continue;
                }
                $includePamo = true;
                $allowedOfficeIds = array_merge(
                    $allowedOfficeIds,
                    admin_collect_descendant_office_ids($childrenIndex, (int)$office['id'])
                );
            }
        }

        $allowedOfficeIds = array_values(array_unique(array_map('intval', $allowedOfficeIds)));
        sort($allowedOfficeIds, SORT_NUMERIC);

        $allowedOffices = [];
        foreach ($allowedOfficeIds as $allowedOfficeId) {
            if (!isset($officesById[$allowedOfficeId])) {
                continue;
            }
            $allowedOffices[] = $officesById[$allowedOfficeId];
        }

        usort($allowedOffices, static function (array $left, array $right): int {
            return strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
        });

        $summaryLabel = 'Managed office scope: ' . (string)($rootOffice['name'] ?? 'Assigned Office');
        if ($includePamo && $penroOffice !== null) {
            $summaryLabel .= ' + PAMO/PASU under ' . (string)($penroOffice['name'] ?? '');
        }

        return [
            'scope_type' => $scopeType,
            'assigned_office' => $assignedOffice,
            'root_office' => $rootOffice,
            'penro_office' => $penroOffice,
            'include_pamo' => $includePamo,
            'allowed_office_ids' => $allowedOfficeIds,
            'allowed_offices' => $allowedOffices,
            'summary_label' => $summaryLabel,
            'office_count' => count($allowedOffices),
        ];
    }
}

if (!function_exists('admin_fetch_manageable_roles')) {
    function admin_fetch_manageable_roles(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT id, name, description FROM roles ORDER BY name ASC')?->fetchAll() ?: [];
        $roles = [];
        foreach ($rows as $row) {
            $roleName = trim((string)($row['name'] ?? ''));
            if ($roleName === '' || !admin_is_manageable_role_key($roleName)) {
                continue;
            }
            $roles[] = [
                'id' => (int)($row['id'] ?? 0),
                'name' => $roleName,
                'description' => trim((string)($row['description'] ?? '')),
            ];
        }

        return $roles;
    }
}

if (!function_exists('admin_fetch_scope_offices')) {
    function admin_fetch_scope_offices(array $scope): array
    {
        $offices = [];
        foreach ($scope['allowed_offices'] ?? [] as $office) {
            $offices[] = [
                'id' => (int)($office['id'] ?? 0),
                'name' => trim((string)($office['name'] ?? '')),
                'level' => trim((string)($office['level'] ?? '')),
            ];
        }

        return $offices;
    }
}

if (!function_exists('admin_fetch_scope_users')) {
    function admin_fetch_scope_users(PDO $pdo, array $scope, int $limit = 600): array
    {
        $officeIds = array_values(array_filter(array_map('intval', (array)($scope['allowed_office_ids'] ?? []))));
        if (empty($officeIds)) {
            return [];
        }

        $safeLimit = max(20, min($limit, 1000));
        $officeClause = admin_sql_placeholders($officeIds, 'office_id_');

        $hiddenEmailClause = admin_sql_placeholders(admin_hidden_account_emails(), 'hidden_email_');

                $sql = "SELECT TOP ({$safeLimit})
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.username,
                    u.email,
                    u.office_id,
                    u.role_id,
                    u.is_active,
                    u.failed_login_attempts,
                    u.locked_until,
                    u.last_login_at,
                    COALESCE(r.name, '') AS role_name,
                    COALESCE(o.name, '') AS office_name
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN offices o ON o.id = u.office_id
                WHERE u.office_id IN (" . implode(', ', $officeClause['placeholders']) . ")
                  AND UPPER(COALESCE(r.name, '')) NOT IN ('SUPER_ADMIN', 'ADMIN')
                  AND LOWER(COALESCE(u.email, '')) NOT IN (" . implode(', ', $hiddenEmailClause['placeholders']) . ")
                ORDER BY u.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($officeClause['params'], $hiddenEmailClause['params']));
        $rows = $stmt->fetchAll() ?: [];

        return array_map(static function (array $row): array {
            return [
                'id' => (int)($row['id'] ?? 0),
                'first_name' => trim((string)($row['first_name'] ?? '')),
                'last_name' => trim((string)($row['last_name'] ?? '')),
                'username' => trim((string)($row['username'] ?? '')),
                'email' => trim((string)($row['email'] ?? '')),
                'office_id' => (int)($row['office_id'] ?? 0),
                'role_id' => (int)($row['role_id'] ?? 0),
                'is_active' => (int)($row['is_active'] ?? 0) === 1,
                'failed_login_attempts' => (int)($row['failed_login_attempts'] ?? 0),
                'locked_until' => (string)($row['locked_until'] ?? ''),
                'locked_until_label' => admin_format_datetime((string)($row['locked_until'] ?? null)),
                'last_login_at' => (string)($row['last_login_at'] ?? ''),
                'last_login_label' => admin_format_datetime((string)($row['last_login_at'] ?? null)),
                'role_name' => trim((string)($row['role_name'] ?? '')),
                'office_name' => trim((string)($row['office_name'] ?? '')),
            ];
        }, $rows);
    }
}

if (!function_exists('admin_fetch_scope_overview')) {
    function admin_fetch_scope_overview(PDO $pdo, array $scope): array
    {
        $users = admin_fetch_scope_users($pdo, $scope, 1000);
        $overview = [
            'users_total' => count($users),
            'users_active' => 0,
            'users_inactive' => 0,
            'users_locked' => 0,
            'offices_total' => (int)($scope['office_count'] ?? 0),
        ];

        foreach ($users as $user) {
            if (!empty($user['is_active'])) {
                $overview['users_active'] += 1;
            } else {
                $overview['users_inactive'] += 1;
            }
            if (trim((string)($user['locked_until'] ?? '')) !== '') {
                $overview['users_locked'] += 1;
            }
        }

        return $overview;
    }
}

if (!function_exists('admin_scope_allows_office')) {
    function admin_scope_allows_office(array $scope, int $officeId): bool
    {
        if ($officeId <= 0) {
            return false;
        }

        return in_array($officeId, (array)($scope['allowed_office_ids'] ?? []), true);
    }
}

if (!function_exists('admin_fetch_manageable_user_by_id')) {
    function admin_fetch_manageable_user_by_id(PDO $pdo, array $scope, int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare(
            'SELECT TOP (1)
                u.id,
                u.office_id,
                u.role_id,
                u.username,
                u.email,
                COALESCE(r.name, \'\') AS role_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id'
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $roleName = trim((string)($row['role_name'] ?? ''));
        $officeId = (int)($row['office_id'] ?? 0);
        if (
            !admin_scope_allows_office($scope, $officeId)
            || !admin_is_manageable_role_key($roleName)
            || admin_email_is_hidden((string)($row['email'] ?? ''))
        ) {
            return null;
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'office_id' => $officeId,
            'role_id' => (int)($row['role_id'] ?? 0),
            'username' => trim((string)($row['username'] ?? '')),
            'email' => trim((string)($row['email'] ?? '')),
            'role_name' => $roleName,
        ];
    }
}
