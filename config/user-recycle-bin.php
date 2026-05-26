<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (!function_exists('user_recycle_bin_table_name')) {
    function user_recycle_bin_table_name(): string
    {
        return 'deleted_users_archive';
    }
}

if (!function_exists('user_recycle_bin_ensure_table')) {
    function user_recycle_bin_ensure_table(PDO $pdo): void
    {
        static $ensured = [];

        $cacheKey = spl_object_hash($pdo);
        if (isset($ensured[$cacheKey])) {
            return;
        }

        $tableName = user_recycle_bin_table_name();
        if (!db_table_exists($pdo, $tableName)) {
            if (db_is_sql_server($pdo)) {
                $pdo->exec(
                    "IF OBJECT_ID(N'dbo.{$tableName}', N'U') IS NULL
                     BEGIN
                         CREATE TABLE dbo.{$tableName} (
                             id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                             original_user_id INT NOT NULL,
                             email NVARCHAR(255) NOT NULL,
                             full_name NVARCHAR(255) NOT NULL,
                             role_name NVARCHAR(255) NULL,
                             office_name NVARCHAR(255) NULL,
                             deleted_at DATETIME2 NOT NULL CONSTRAINT DF_{$tableName}_deleted_at DEFAULT SYSDATETIME(),
                             deleted_by_user_id INT NULL,
                             user_payload_json NVARCHAR(MAX) NOT NULL
                         );
                         CREATE UNIQUE INDEX UQ_{$tableName}_original_user_id ON dbo.{$tableName}(original_user_id);
                         CREATE INDEX IDX_{$tableName}_deleted_at ON dbo.{$tableName}(deleted_at DESC);
                     END"
                );
            } else {
                $pdo->exec(
                    "CREATE TABLE IF NOT EXISTS {$tableName} (
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        original_user_id INT NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        full_name VARCHAR(255) NOT NULL,
                        role_name VARCHAR(255) NULL,
                        office_name VARCHAR(255) NULL,
                        deleted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        deleted_by_user_id INT NULL,
                        user_payload_json LONGTEXT NOT NULL,
                        UNIQUE KEY uq_original_user_id (original_user_id)
                    )"
                );
            }
        }

        $ensured[$cacheKey] = true;
    }
}

if (!function_exists('user_recycle_bin_json_encode')) {
    function user_recycle_bin_json_encode(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded) || $encoded === false) {
            throw new RuntimeException('Unable to encode deleted user payload.');
        }

        return $encoded;
    }
}

if (!function_exists('user_recycle_bin_json_decode_row')) {
    function user_recycle_bin_json_decode_row(?string $value): array
    {
        $trimmed = trim((string)$value);
        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        if (!is_array($decoded)) {
            return [];
        }

        $isSequential = array_keys($decoded) === range(0, count($decoded) - 1);
        if ($isSequential) {
            return [];
        }

        return $decoded;
    }
}

if (!function_exists('user_recycle_bin_identity_column')) {
    function user_recycle_bin_identity_column(PDO $pdo, string $table): ?string
    {
        static $cache = [];

        $cacheKey = strtolower($table);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $stmt = $pdo->prepare(
            "SELECT TOP (1) c.name AS column_name
             FROM sys.identity_columns c
             INNER JOIN sys.objects o ON o.object_id = c.object_id
             WHERE o.name = :table_name"
        );
        $stmt->execute(['table_name' => $table]);
        $value = $stmt->fetchColumn();
        $cache[$cacheKey] = is_string($value) && trim($value) !== '' ? trim($value) : null;

        return $cache[$cacheKey];
    }
}

if (!function_exists('user_recycle_bin_restore_row')) {
    function user_recycle_bin_restore_row(PDO $pdo, string $table, array $row): void
    {
        if ($row === []) {
            return;
        }

        $identityColumn = user_recycle_bin_identity_column($pdo, $table);
        $hasIdentityValue = $identityColumn !== null
            && array_key_exists($identityColumn, $row)
            && $row[$identityColumn] !== null;

        if ($hasIdentityValue && db_is_sql_server($pdo)) {
            $pdo->exec("SET IDENTITY_INSERT dbo.{$table} ON");
        }

        try {
            $columns = array_keys($row);
            $placeholders = [];
            $params = [];
            foreach ($columns as $index => $column) {
                $placeholder = 'p_' . $index;
                $placeholders[] = ':' . $placeholder;
                $params[$placeholder] = $row[$column];
            }

            $sql = 'INSERT INTO ' . $table
                . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } finally {
            if ($hasIdentityValue && db_is_sql_server($pdo)) {
                $pdo->exec("SET IDENTITY_INSERT dbo.{$table} OFF");
            }
        }
    }
}

if (!function_exists('user_recycle_bin_role_exists')) {
    function user_recycle_bin_role_exists(PDO $pdo, int $roleId): bool
    {
        if ($roleId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT TOP (1) 1 FROM roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);

        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('user_recycle_bin_office_exists')) {
    function user_recycle_bin_office_exists(PDO $pdo, int $officeId): bool
    {
        if ($officeId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT TOP (1) 1 FROM offices WHERE id = :id');
        $stmt->execute(['id' => $officeId]);

        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('user_recycle_bin_username_exists')) {
    function user_recycle_bin_username_exists(PDO $pdo, string $username): bool
    {
        $normalized = strtolower(trim($username));
        if ($normalized === '') {
            return false;
        }

        $stmt = $pdo->prepare('SELECT TOP (1) 1 FROM users WHERE LOWER(username) = LOWER(:username)');
        $stmt->execute(['username' => $normalized]);

        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('user_recycle_bin_build_username_candidate')) {
    function user_recycle_bin_build_username_candidate(string $seed, int $userId): string
    {
        $candidate = strtolower(trim($seed));
        if ($candidate !== '') {
            $candidate = preg_replace('/[^a-z0-9._-]+/', '.', $candidate) ?? '';
            $candidate = trim($candidate, '._-');
        }
        if ($candidate === '') {
            $candidate = 'user' . max($userId, 1);
        }
        if (strlen($candidate) > 50) {
            $candidate = substr($candidate, 0, 50);
            $candidate = rtrim($candidate, '._-');
        }
        if (strlen($candidate) < 3) {
            $candidate .= str_repeat('0', 3 - strlen($candidate));
        }

        return $candidate;
    }
}

if (!function_exists('user_recycle_bin_resolve_restored_username')) {
    function user_recycle_bin_resolve_restored_username(PDO $pdo, array $userRow): string
    {
        $userId = (int)($userRow['id'] ?? 0);
        $baseSeed = trim((string)($userRow['username'] ?? ''));
        if ($baseSeed === '') {
            $email = trim((string)($userRow['email'] ?? ''));
            $baseSeed = $email !== '' ? (string)preg_replace('/@.*$/', '', $email) : '';
        }

        $base = user_recycle_bin_build_username_candidate($baseSeed, $userId);
        $candidate = $base;
        $suffix = 0;
        while (user_recycle_bin_username_exists($pdo, $candidate)) {
            $suffix += 1;
            $suffixText = '.' . ($userId > 0 ? (string)$userId : (string)$suffix);
            $trimmedBase = substr($base, 0, max(1, 50 - strlen($suffixText)));
            $trimmedBase = rtrim($trimmedBase, '._-');
            if ($trimmedBase === '') {
                $trimmedBase = 'user';
            }
            $candidate = $trimmedBase . $suffixText;
        }

        return $candidate;
    }
}

if (!function_exists('user_recycle_bin_user_exists')) {
    function user_recycle_bin_user_exists(PDO $pdo, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT TOP (1) 1 FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);

        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('user_recycle_bin_archive_user')) {
    function user_recycle_bin_archive_user(PDO $pdo, int $userId, int $deletedByUserId): int
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID is required for recycle bin archive.');
        }

        user_recycle_bin_ensure_table($pdo);

        $existsStmt = $pdo->prepare(
            'SELECT TOP (1) id
             FROM ' . user_recycle_bin_table_name() . '
             WHERE original_user_id = :user_id'
        );
        $existsStmt->execute(['user_id' => $userId]);
        if ((int)($existsStmt->fetchColumn() ?: 0) > 0) {
            throw new RuntimeException('User is already in the recycle bin.');
        }

        $userStmt = $pdo->prepare(
            'SELECT TOP (1)
                u.*,
                COALESCE(r.name, \'\') AS recycle_role_name,
                COALESCE(o.name, \'\') AS recycle_office_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN offices o ON o.id = u.office_id
             WHERE u.id = :id'
        );
        $userStmt->execute(['id' => $userId]);
        $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($userRow)) {
            throw new RuntimeException('User not found.');
        }

        $roleName = trim((string)($userRow['recycle_role_name'] ?? ''));
        $officeName = trim((string)($userRow['recycle_office_name'] ?? ''));
        unset($userRow['recycle_role_name'], $userRow['recycle_office_name']);

        $fullName = trim((string)($userRow['first_name'] ?? '') . ' ' . (string)($userRow['last_name'] ?? ''));
        if ($fullName === '') {
            $fullName = trim((string)($userRow['email'] ?? ''));
        }

        $insertStmt = $pdo->prepare(
            'INSERT INTO ' . user_recycle_bin_table_name() . ' (
                original_user_id,
                email,
                full_name,
                role_name,
                office_name,
                deleted_at,
                deleted_by_user_id,
                user_payload_json
             ) VALUES (
                :original_user_id,
                :email,
                :full_name,
                :role_name,
                :office_name,
                SYSDATETIME(),
                :deleted_by_user_id,
                :user_payload_json
             )'
        );
        $insertStmt->execute([
            'original_user_id' => $userId,
            'email' => trim((string)($userRow['email'] ?? '')),
            'full_name' => $fullName,
            'role_name' => $roleName !== '' ? $roleName : null,
            'office_name' => $officeName !== '' ? $officeName : null,
            'deleted_by_user_id' => $deletedByUserId > 0 ? $deletedByUserId : null,
            'user_payload_json' => user_recycle_bin_json_encode($userRow),
        ]);

        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('user_recycle_bin_fetch_deleted_users')) {
    function user_recycle_bin_fetch_deleted_users(PDO $pdo, int $limit = 250): array
    {
        user_recycle_bin_ensure_table($pdo);

        $safeLimit = max(20, min($limit, 1000));
        $stmt = $pdo->query(
            "SELECT TOP ({$safeLimit})
                a.id,
                a.original_user_id,
                a.email,
                a.full_name,
                a.role_name,
                a.office_name,
                a.deleted_at,
                a.deleted_by_user_id,
                a.user_payload_json,
                COALESCE(u.email, '') AS deleted_by_email,
                COALESCE(u.first_name, '') AS deleted_by_first_name,
                COALESCE(u.last_name, '') AS deleted_by_last_name
             FROM " . user_recycle_bin_table_name() . " a
             LEFT JOIN users u ON u.id = a.deleted_by_user_id
             ORDER BY a.deleted_at DESC, a.id DESC"
        );
        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        return array_map(static function (array $row): array {
            $userPayload = user_recycle_bin_json_decode_row((string)($row['user_payload_json'] ?? ''));
            return [
                'archive_id' => (int)($row['id'] ?? 0),
                'original_user_id' => (int)($row['original_user_id'] ?? 0),
                'email' => trim((string)($row['email'] ?? '')),
                'full_name' => trim((string)($row['full_name'] ?? '')),
                'role_name' => trim((string)($row['role_name'] ?? '')),
                'office_name' => trim((string)($row['office_name'] ?? '')),
                'is_active' => (int)($userPayload['is_active'] ?? 0) === 1,
                'deleted_at' => (string)($row['deleted_at'] ?? ''),
                'deleted_at_label' => function_exists('super_admin_format_datetime')
                    ? super_admin_format_datetime((string)($row['deleted_at'] ?? null))
                    : (string)($row['deleted_at'] ?? ''),
                'deleted_by_user_id' => (int)($row['deleted_by_user_id'] ?? 0),
                'deleted_by_name' => trim((string)($row['deleted_by_first_name'] ?? '') . ' ' . (string)($row['deleted_by_last_name'] ?? '')),
                'deleted_by_email' => trim((string)($row['deleted_by_email'] ?? '')),
            ];
        }, $rows);
    }
}

if (!function_exists('user_recycle_bin_restore_user')) {
    function user_recycle_bin_restore_user(PDO $pdo, int $archiveId): array
    {
        if ($archiveId <= 0) {
            throw new InvalidArgumentException('Recycle bin entry is required.');
        }

        user_recycle_bin_ensure_table($pdo);

        $archiveStmt = $pdo->prepare(
            'SELECT TOP (1) *
             FROM ' . user_recycle_bin_table_name() . ' WITH (UPDLOCK, ROWLOCK)
             WHERE id = :id'
        );
        $archiveStmt->execute(['id' => $archiveId]);
        $archiveRow = $archiveStmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($archiveRow)) {
            throw new RuntimeException('Recycle bin entry not found.');
        }

        $userRow = user_recycle_bin_json_decode_row((string)($archiveRow['user_payload_json'] ?? ''));
        if ($userRow === []) {
            throw new RuntimeException('Deleted user payload is incomplete.');
        }

        $userId = (int)($userRow['id'] ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('Deleted user payload is invalid.');
        }

        if (user_recycle_bin_user_exists($pdo, $userId)) {
            throw new RuntimeException('Cannot restore because that user ID already exists.');
        }

        $userRow['username'] = user_recycle_bin_resolve_restored_username($pdo, $userRow);

        $roleId = (int)($userRow['role_id'] ?? 0);
        if (!user_recycle_bin_role_exists($pdo, $roleId)) {
            throw new RuntimeException('Cannot restore user because the assigned role no longer exists.');
        }

        $officeId = (int)($userRow['office_id'] ?? 0);
        if (!user_recycle_bin_office_exists($pdo, $officeId)) {
            throw new RuntimeException('Cannot restore user because the assigned office no longer exists.');
        }

        user_recycle_bin_restore_row($pdo, 'users', $userRow);

        $deleteStmt = $pdo->prepare(
            'DELETE FROM ' . user_recycle_bin_table_name() . '
             WHERE id = :id'
        );
        $deleteStmt->execute(['id' => $archiveId]);

        return [
            'user_id' => $userId,
            'username' => trim((string)($userRow['username'] ?? '')),
            'email' => trim((string)($userRow['email'] ?? '')),
            'full_name' => trim((string)($userRow['first_name'] ?? '') . ' ' . (string)($userRow['last_name'] ?? '')),
        ];
    }
}
