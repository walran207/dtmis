<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';

function test_account_seed_password(): string
{
    return 'DTMISTest123!';
}

function test_account_seed_export_path(): string
{
    return dirname(__DIR__, 2) . '/storage/test-accounts/southcot-regional-test-accounts.csv';
}

function test_account_role_slug(string $roleKey): string
{
    $slug = strtolower(trim($roleKey));
    $slug = preg_replace('/[^a-z0-9]+/', '.', $slug) ?? '';
    $slug = trim($slug, '.');
    return $slug !== '' ? $slug : 'role';
}

function test_account_connect(): PDO
{
    return getDatabaseConnection();
}

function test_account_fetch_role_ids(PDO $pdo): array
{
    $rows = $pdo->query('SELECT id, name FROM roles ORDER BY id ASC')?->fetchAll() ?: [];
    $map = [];
    foreach ($rows as $row) {
        $key = strtoupper(trim((string)($row['name'] ?? '')));
        if ($key === '') {
            continue;
        }
        $map[$key] = (int)($row['id'] ?? 0);
    }
    return $map;
}

function test_account_fetch_office_map(PDO $pdo): array
{
    $rows = $pdo->query('SELECT id, name, level, parent_office_id FROM offices ORDER BY id ASC')?->fetchAll() ?: [];
    $map = [];
    foreach ($rows as $row) {
        $map[(int)($row['id'] ?? 0)] = [
            'id' => (int)($row['id'] ?? 0),
            'name' => trim((string)($row['name'] ?? '')),
            'level' => trim((string)($row['level'] ?? '')),
            'parent_office_id' => isset($row['parent_office_id']) ? (int)$row['parent_office_id'] : null,
        ];
    }
    return $map;
}

function test_account_fetch_descendants_by_level(PDO $pdo, int $rootOfficeId, string $level): array
{
    $stmt = $pdo->prepare(
        "WITH office_tree AS (
            SELECT id, name, level, parent_office_id
            FROM offices
            WHERE id = :root_office_id

            UNION ALL

            SELECT child.id, child.name, child.level, child.parent_office_id
            FROM offices child
            INNER JOIN office_tree parent_tree ON child.parent_office_id = parent_tree.id
        )
        SELECT id, name, level, parent_office_id
        FROM office_tree
        WHERE id <> :root_office_id_dup
          AND UPPER(COALESCE(level, '')) = :level_name
        ORDER BY id ASC"
    );
    $stmt->execute([
        'root_office_id' => $rootOfficeId,
        'root_office_id_dup' => $rootOfficeId,
        'level_name' => strtoupper(trim($level)),
    ]);

    return $stmt->fetchAll() ?: [];
}

function test_account_add_spec(array &$specs, array $office, string $roleKey, string $group): void
{
    $officeId = (int)($office['id'] ?? 0);
    if ($officeId <= 0) {
        return;
    }

    $roleKey = strtoupper(trim($roleKey));
    if ($roleKey === '') {
        return;
    }

    $email = sprintf('seed.%s.%d@denr.gov.ph', test_account_role_slug($roleKey), $officeId);
    $specs[$email] = [
        'email' => $email,
        'role_key' => $roleKey,
        'office_id' => $officeId,
        'office_name' => trim((string)($office['name'] ?? '')),
        'office_level' => trim((string)($office['level'] ?? '')),
        'first_name' => 'Seed',
        'last_name' => $roleKey . ' ' . $officeId,
        'group_name' => $group,
    ];
}

function test_account_build_specs(PDO $pdo): array
{
    $offices = test_account_fetch_office_map($pdo);
    $specs = [];

    foreach ([
        [1, 'ORED', 'Regional'],
        [1, 'ORED_SIGN', 'Regional'],
        [2, 'PACDO', 'Regional'],
        [49, 'ARD_TS', 'Regional'],
        [50, 'ARD_MS', 'Regional'],
        [208, 'PENRO_ADMIN_RECORD', 'South Cotabato'],
        [215, 'PENRO_OFFICER', 'South Cotabato'],
        [51, 'CENRO_ADMIN_RECORD', 'South Cotabato'],
        [66, 'CENRO_OFFICER', 'South Cotabato'],
        [356, 'PASU_OFFICER', 'South Cotabato'],
        [357, 'PAMO_ADMIN', 'South Cotabato'],
    ] as [$officeId, $roleKey, $group]) {
        if (!isset($offices[$officeId])) {
            throw new RuntimeException('Required office ID ' . $officeId . ' was not found.');
        }
        test_account_add_spec($specs, $offices[$officeId], $roleKey, $group);
    }

    foreach ([49, 50] as $regionalRootId) {
        foreach (test_account_fetch_descendants_by_level($pdo, $regionalRootId, 'DIVISION') as $office) {
            test_account_add_spec($specs, $office, 'DIVISION_CHIEF', 'Regional');
        }
        foreach (test_account_fetch_descendants_by_level($pdo, $regionalRootId, 'SECTION') as $office) {
            test_account_add_spec($specs, $office, 'SECTION_STAFF', 'Regional');
        }
    }

    foreach (test_account_fetch_descendants_by_level($pdo, 215, 'PENRO_DIVISION') as $office) {
        test_account_add_spec($specs, $office, 'PENRO_DIVISION', 'South Cotabato');
    }
    foreach (test_account_fetch_descendants_by_level($pdo, 215, 'PENRO_SECTION') as $office) {
        test_account_add_spec($specs, $office, 'PENRO_SECTION', 'South Cotabato');
    }
    foreach (test_account_fetch_descendants_by_level($pdo, 215, 'PENRO_UNIT') as $office) {
        test_account_add_spec($specs, $office, 'PENRO_SECTION_UNIT', 'South Cotabato');
    }
    foreach (test_account_fetch_descendants_by_level($pdo, 66, 'CENRO_SECTION') as $office) {
        test_account_add_spec($specs, $office, 'CENRO_SECTION', 'South Cotabato');
    }
    foreach (test_account_fetch_descendants_by_level($pdo, 66, 'CENRO_UNIT') as $office) {
        test_account_add_spec($specs, $office, 'CENRO_UNIT', 'South Cotabato');
    }
    foreach (test_account_fetch_descendants_by_level($pdo, 356, 'PAMO_UNIT') as $office) {
        test_account_add_spec($specs, $office, 'PAMO_UNIT', 'South Cotabato');
    }

    ksort($specs);
    return array_values($specs);
}

function test_account_write_export(array $rows): string
{
    $path = test_account_seed_export_path();
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create export directory: ' . $directory);
    }

    $handle = fopen($path, 'wb');
    if ($handle === false) {
        throw new RuntimeException('Unable to open export file: ' . $path);
    }

    fputcsv($handle, [
        'email',
        'password',
        'role_key',
        'office_id',
        'office_name',
        'office_level',
        'group_name',
        'status',
    ]);

    foreach ($rows as $row) {
        fputcsv($handle, [
            (string)($row['email'] ?? ''),
            test_account_seed_password(),
            (string)($row['role_key'] ?? ''),
            (string)($row['office_id'] ?? ''),
            (string)($row['office_name'] ?? ''),
            (string)($row['office_level'] ?? ''),
            (string)($row['group_name'] ?? ''),
            (string)($row['status'] ?? ''),
        ]);
    }

    fclose($handle);
    return $path;
}
