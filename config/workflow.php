<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

function workflow_normalize_source_type(string $sourceType): string
{
    $normalized = strtoupper(trim($sourceType));
    if (!in_array($normalized, ['INTERNAL', 'EXTERNAL'], true)) {
        throw new InvalidArgumentException('Invalid source type.');
    }

    return $normalized;
}

function workflow_resolve_display_status(string $status, int $pendingOfficeId = 0, int $currentOfficeId = 0): string
{
    $resolved = trim($status);
    if ($resolved === '') {
        $resolved = 'Pending';
    }

    return $resolved;
}

function workflow_table_exists(PDO $pdo, string $tableName): bool
{
    static $cache = [];
    $key = strtolower(trim($tableName));
    if ($key === '') {
        return false;
    }
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $cache[$key] = db_table_exists($pdo, $tableName);

    return $cache[$key];
}

function workflow_column_exists(PDO $pdo, string $tableName, string $columnName): bool
{
    $safeTable = trim($tableName);
    $safeColumn = trim($columnName);
    if ($safeTable === '' || $safeColumn === '') {
        return false;
    }

    return db_column_exists($pdo, $safeTable, $safeColumn);
}

function workflow_ensure_document_arta_override_columns(PDO $pdo): void
{
    if (!workflow_table_exists($pdo, 'documents')) {
        throw new RuntimeException('Documents table is missing.');
    }

    if (!workflow_column_exists($pdo, 'documents', 'arta_category_override')) {
        try {
            if (db_is_sql_server($pdo)) {
                $pdo->exec('ALTER TABLE documents ADD arta_category_override NVARCHAR(50) NULL');
            } else {
                $pdo->exec(
                    "ALTER TABLE documents
                     ADD COLUMN arta_category_override VARCHAR(50) NULL DEFAULT NULL
                     AFTER document_type_id"
                );
            }
        } catch (Throwable $exception) {
            if (!workflow_column_exists($pdo, 'documents', 'arta_category_override')) {
                throw $exception;
            }
        }
    }

    if (!workflow_column_exists($pdo, 'documents', 'arta_days_limit_override')) {
        try {
            if (db_is_sql_server($pdo)) {
                $pdo->exec('ALTER TABLE documents ADD arta_days_limit_override INT NULL');
            } else {
                $pdo->exec(
                    "ALTER TABLE documents
                     ADD COLUMN arta_days_limit_override INT(11) NULL DEFAULT NULL
                     AFTER arta_category_override"
                );
            }
        } catch (Throwable $exception) {
            if (!workflow_column_exists($pdo, 'documents', 'arta_days_limit_override')) {
                throw $exception;
            }
        }
    }
}

function workflow_ensure_document_row_version_column(PDO $pdo): void
{
    if (!workflow_table_exists($pdo, 'documents')) {
        throw new RuntimeException('Documents table is missing.');
    }

    if (!workflow_column_exists($pdo, 'documents', 'row_version')) {
        try {
            if (db_is_sql_server($pdo)) {
                $pdo->exec('ALTER TABLE documents ADD row_version INT NOT NULL CONSTRAINT DF_documents_row_version DEFAULT 1');
            } else {
                $pdo->exec(
                    "ALTER TABLE documents
                     ADD COLUMN row_version INT UNSIGNED NOT NULL DEFAULT 1
                     AFTER pending_user_id"
                );
            }
        } catch (Throwable $exception) {
            if (!workflow_column_exists($pdo, 'documents', 'row_version')) {
                throw $exception;
            }
        }
    }
}

function workflow_get_user_context(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT TOP (1) u.id, u.office_id, u.role_id, r.name AS role_name
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id AND u.is_active = 1'
    );
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new RuntimeException('User not found or inactive.');
    }

    return $user;
}

function workflow_get_document_context(PDO $pdo, int $documentId, bool $forUpdate = false): array
{
    $sql = 'SELECT TOP (1)
                d.*,
                cu.role_id AS current_holder_role_id,
                rc.name AS current_holder_role_name,
                pu.role_id AS pending_user_role_id,
                rp.name AS pending_user_role_name
            FROM documents AS d
            LEFT JOIN users cu ON cu.id = d.current_holder_user_id
            LEFT JOIN roles rc ON rc.id = cu.role_id
            LEFT JOIN users pu ON pu.id = d.pending_user_id
            LEFT JOIN roles rp ON rp.id = pu.role_id
            WHERE d.id = :id';
    if ($forUpdate) {
        $sql = str_replace('FROM documents AS d', 'FROM documents AS d WITH (UPDLOCK, ROWLOCK)', $sql);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $documentId]);
    $document = $stmt->fetch();

    if (!$document) {
        throw new RuntimeException('Document not found.');
    }

    return $document;
}

function workflow_document_current_leg_started_at(PDO $pdo, array $documentContext, int $officeId): string
{
    $documentId = (int)($documentContext['id'] ?? 0);
    $documentCreatedAt = trim((string)($documentContext['created_at'] ?? ''));
    if ($documentId <= 0 || $officeId <= 0 || !workflow_table_exists($pdo, 'tracking_slips')) {
        return $documentCreatedAt;
    }

    $latestReceiveStmt = $pdo->prepare(
        'SELECT MAX(ts.date_time_received)
         FROM tracking_slips ts
         WHERE ts.document_id = :document_id
           AND ts.receiving_office_id = :office_id'
    );
    $latestReceiveStmt->execute([
        'document_id' => $documentId,
        'office_id' => $officeId,
    ]);
    $latestReceiveAt = trim((string)($latestReceiveStmt->fetchColumn() ?: ''));

    return $latestReceiveAt !== '' ? $latestReceiveAt : $documentCreatedAt;
}

function workflow_document_has_local_sign_stage(PDO $pdo, array $documentContext, int $officeId, string $roleKey): bool
{
    $documentId = (int)($documentContext['id'] ?? 0);
    $normalizedRoleKey = app_normalize_role_key($roleKey);
    if ($documentId <= 0 || $officeId <= 0 || $normalizedRoleKey === '' || !workflow_table_exists($pdo, 'activity_logs')) {
        return false;
    }

    $legStartedAt = workflow_document_current_leg_started_at($pdo, $documentContext, $officeId);
    $params = [
        'document_id' => $documentId,
        'office_id' => $officeId,
        'role_name' => $normalizedRoleKey,
    ];
    $legStartedFilter = '';
    if ($legStartedAt !== '') {
        $legStartedFilter = ' AND al.created_at >= :leg_started_at';
        $params['leg_started_at'] = $legStartedAt;
    }

    $signedStmt = $pdo->prepare(
        'SELECT TOP (1) 1
         FROM activity_logs al
         INNER JOIN users u ON u.id = al.user_id
         INNER JOIN roles r ON r.id = u.role_id
         WHERE al.document_id = :document_id
           AND LOWER(COALESCE(al.action_type, \'\')) = \'signed\'
           AND u.office_id = :office_id
           AND UPPER(COALESCE(r.name, \'\')) = :role_name'
         . $legStartedFilter
    );
    $signedStmt->execute($params);

    return (bool)$signedStmt->fetchColumn();
}

function workflow_get_role_id_by_key(PDO $pdo, string $roleKey): ?int
{
    static $roleMap = null;
    if (!is_array($roleMap)) {
        $roleMap = [];
        $stmt = $pdo->query('SELECT id, name FROM roles');
        foreach ($stmt->fetchAll() as $role) {
            $roleMap[app_normalize_role_key((string)$role['name'])] = (int)$role['id'];
        }
    }

    $normalized = app_normalize_role_key($roleKey);
    return $roleMap[$normalized] ?? null;
}

function workflow_is_regional_ored_role_key(string $roleKey): bool
{
    return in_array(app_normalize_role_key($roleKey), ['ORED', 'ORED_SIGN'], true);
}

function workflow_is_ored_sign_role_key(string $roleKey): bool
{
    return app_normalize_role_key($roleKey) === 'ORED_SIGN';
}

function workflow_ored_resolve_user_id_by_priority(
    PDO $pdo,
    int $officeId,
    array $preferredRoleKeys,
    ?int $excludeUserId = null
): ?int {
    if ($officeId <= 0 || $preferredRoleKeys === []) {
        return null;
    }

    $priorityMap = [];
    foreach (array_values($preferredRoleKeys) as $index => $roleKey) {
        $normalizedRoleKey = app_normalize_role_key((string)$roleKey);
        if ($normalizedRoleKey === '' || array_key_exists($normalizedRoleKey, $priorityMap)) {
            continue;
        }
        $priorityMap[$normalizedRoleKey] = $index;
    }
    if ($priorityMap === []) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT u.id, r.name AS role_name
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.office_id = :office_id
           AND u.is_active = 1
         ORDER BY u.id DESC'
    );
    $stmt->execute(['office_id' => $officeId]);
    $users = $stmt->fetchAll() ?: [];

    $bestUserId = null;
    $bestPriority = PHP_INT_MAX;
    foreach ($users as $user) {
        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0 || ($excludeUserId !== null && $excludeUserId > 0 && $userId === $excludeUserId)) {
            continue;
        }

        $normalizedRoleKey = app_normalize_role_key((string)($user['role_name'] ?? ''));
        if (!array_key_exists($normalizedRoleKey, $priorityMap)) {
            continue;
        }

        $priority = (int)$priorityMap[$normalizedRoleKey];
        if ($priority < $bestPriority) {
            $bestPriority = $priority;
            $bestUserId = $userId;
        }
    }

    return $bestUserId;
}

function workflow_ored_office_has_signer(PDO $pdo, int $officeId, ?int $excludeUserId = null): bool
{
    return workflow_ored_resolve_user_id_by_priority($pdo, $officeId, ['ORED_SIGN'], $excludeUserId) !== null;
}

function workflow_ored_resolve_signer_user_id(PDO $pdo, int $officeId, ?int $excludeUserId = null): ?int
{
    return workflow_ored_resolve_user_id_by_priority($pdo, $officeId, ['ORED_SIGN', 'ORED'], $excludeUserId);
}

function workflow_ored_resolve_reviewer_user_id(PDO $pdo, int $officeId, ?int $excludeUserId = null): ?int
{
    return workflow_ored_resolve_user_id_by_priority($pdo, $officeId, ['ORED', 'ORED_SIGN'], $excludeUserId);
}

function workflow_actor_is_ored_signer(PDO $pdo, array $actor): bool
{
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    if ($actorRoleKey === 'ORED_SIGN') {
        return true;
    }
    if ($actorRoleKey !== 'ORED') {
        return false;
    }

    $officeId = (int)($actor['office_id'] ?? 0);
    $actorUserId = (int)($actor['id'] ?? 0);
    if ($officeId <= 0) {
        return true;
    }

    return !workflow_ored_office_has_signer($pdo, $officeId, $actorUserId > 0 ? $actorUserId : null);
}

function workflow_can_override_role(string $roleName): bool
{
    return workflow_is_ored_sign_role_key($roleName);
}

function workflow_is_supervisor_role(string $roleName): bool
{
    $key = app_normalize_role_key($roleName);
    return in_array($key, [
        'DIVISION_CHIEF',
        'ARD_TS',
        'ARD_MS',
        'RECORDS_UNIT',
        'ORED',
        'ORED_SIGN',
        'CENRO_ADMIN_RECORD',
        'CENRO_OFFICER',
        'CENRO_SECTION',
        'PAMO_ADMIN',
        'PASU_OFFICER',
        'PENRO_ADMIN_RECORD',
        'PENRO_OFFICER',
        'PENRO_DIVISION',
    ], true);
}

function workflow_office_level_key(?string $level): string
{
    return strtoupper(trim((string)$level));
}

function workflow_is_cenro_section_level(?string $level): bool
{
    return in_array(workflow_office_level_key($level), ['CENRO_SECTION', 'PENRO_DIVISION'], true);
}

function workflow_is_cenro_unit_level(?string $level): bool
{
    return in_array(workflow_office_level_key($level), ['CENRO_UNIT', 'PENRO_SECTION', 'PENRO_UNIT'], true);
}

function workflow_is_cenro_officer_level(?string $level): bool
{
    return in_array(workflow_office_level_key($level), ['CENRO_OFFICER', 'PENRO_OFFICER'], true);
}

function workflow_is_cenro_admin_record_level(?string $level): bool
{
    return in_array(workflow_office_level_key($level), ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD'], true);
}

function workflow_find_cenro_root_office(PDO $pdo, int $startOfficeId): ?array
{
    if ($startOfficeId <= 0) {
        return null;
    }

    $visited = [];
    $cursor = $startOfficeId;
    for ($depth = 0; $depth < 16; $depth += 1) {
        if ($cursor <= 0 || isset($visited[$cursor])) {
            break;
        }
        $visited[$cursor] = true;
        $office = workflow_get_office_context($pdo, $cursor);
        if (!$office) {
            break;
        }

        $level = workflow_office_level_key((string)($office['level'] ?? ''));
        $name = strtoupper(trim((string)($office['name'] ?? '')));
        if ($level === 'COMMUNITY' && str_contains($name, 'CENRO')) {
            return $office;
        }
        $cursor = (int)($office['parent_office_id'] ?? 0);
    }

    return null;
}

function workflow_find_penro_root_office(PDO $pdo, int $startOfficeId): ?array
{
    if ($startOfficeId <= 0) {
        return null;
    }

    $visited = [];
    $cursor = $startOfficeId;
    for ($depth = 0; $depth < 16; $depth += 1) {
        if ($cursor <= 0 || isset($visited[$cursor])) {
            break;
        }
        $visited[$cursor] = true;
        $office = workflow_get_office_context($pdo, $cursor);
        if (!$office) {
            break;
        }

        $level = workflow_office_level_key((string)($office['level'] ?? ''));
        if ($level === 'PROVINCIAL') {
            return $office;
        }

        $cursor = (int)($office['parent_office_id'] ?? 0);
    }

    return null;
}

function workflow_find_pamo_root_office(PDO $pdo, int $startOfficeId): ?array
{
    if ($startOfficeId <= 0) {
        return null;
    }

    $visited = [];
    $cursor = $startOfficeId;
    for ($depth = 0; $depth < 16; $depth += 1) {
        if ($cursor <= 0 || isset($visited[$cursor])) {
            break;
        }
        $visited[$cursor] = true;
        $office = workflow_get_office_context($pdo, $cursor);
        if (!$office) {
            break;
        }

        $level = workflow_office_level_key((string)($office['level'] ?? ''));
        if ($level === 'PROTECTED AREA') {
            return $office;
        }

        $cursor = (int)($office['parent_office_id'] ?? 0);
    }

    return null;
}

function workflow_office_name_key(string $name): string
{
    $normalized = strtoupper(trim($name));
    $normalized = preg_replace('/[^A-Z0-9]+/', '', $normalized) ?? '';
    return $normalized;
}

function workflow_penro_scope_key(string $officeName): string
{
    $officeKey = workflow_office_name_key($officeName);
    if ($officeKey === '') {
        return '';
    }
    if (str_contains($officeKey, 'SOUTHCOTABATO')) {
        return 'SOUTH_COTABATO';
    }
    if (str_contains($officeKey, 'SULTANKUDARAT')) {
        return 'SULTAN_KUDARAT';
    }
    if (str_contains($officeKey, 'SARANGANI')) {
        return 'SARANGANI';
    }
    if (str_contains($officeKey, 'COTABATO')) {
        return 'COTABATO';
    }

    return '';
}

function workflow_penro_scope_allows_cenro_name(string $penroOfficeName, string $cenroOfficeName): bool
{
    $scopeKey = workflow_penro_scope_key($penroOfficeName);
    return workflow_scope_key_allows_cenro_name($scopeKey, $cenroOfficeName);
}

function workflow_scope_key_allows_cenro_name(string $scopeKey, string $cenroOfficeName): bool
{
    $cenroKey = workflow_office_name_key($cenroOfficeName);
    if ($scopeKey === '' || $cenroKey === '') {
        return false;
    }

    $allowedCenroPatterns = [
        'COTABATO' => ['CENROMIDSAYAP', 'CENROMIDAYAP', 'CENROMATALAM'],
        'SULTAN_KUDARAT' => ['CENROTACURONG', 'CENROTACURONGCITY', 'CENROKALAMANSIG', 'CENROKALAMASIG'],
        'SOUTH_COTABATO' => ['CENROBANGA', 'CENROGENERALSANTOS', 'CENROGENERALSANTOSCITY'],
        'SARANGANI' => ['CENROKIAMBA', 'CENROGLAN'],
    ];

    foreach (($allowedCenroPatterns[$scopeKey] ?? []) as $allowedPattern) {
        if (str_contains($cenroKey, $allowedPattern)) {
            return true;
        }
    }

    return false;
}

function workflow_pamo_scope_key(string $officeName): string
{
    $officeKey = workflow_office_name_key($officeName);
    if ($officeKey === '') {
        return '';
    }

    if (
        str_contains($officeKey, 'MMPL')
        || str_contains($officeKey, 'MTMATUTUMPROTECTEDLANDSCAPE')
    ) {
        return 'SOUTH_COTABATO';
    }

    if (
        str_contains($officeKey, 'MANPI')
        || str_contains($officeKey, 'NORTHCOTABATO')
        || str_contains($officeKey, 'COTABATO')
        || str_contains($officeKey, 'ALLAHVALLEYPROTECTEDLANDSCAPE')
        || str_contains($officeKey, 'AVPL')
    ) {
        return 'COTABATO';
    }

    if (
        str_contains($officeKey, 'SBPS')
        || str_contains($officeKey, 'SARANGANI')
        || str_contains($officeKey, 'PROTECTEDSEASCAPE')
    ) {
        return 'SARANGANI';
    }

    return '';
}

function workflow_pamo_scope_allows_penro_name(string $pamoOfficeName, string $penroOfficeName): bool
{
    $pamoScopeKey = workflow_pamo_scope_key($pamoOfficeName);
    $penroScopeKey = workflow_penro_scope_key($penroOfficeName);

    return $pamoScopeKey !== '' && $pamoScopeKey === $penroScopeKey;
}

function workflow_pamo_scope_allows_cenro_name(string $pamoOfficeName, string $cenroOfficeName): bool
{
    $pamoScopeKey = workflow_pamo_scope_key($pamoOfficeName);
    return workflow_scope_key_allows_cenro_name($pamoScopeKey, $cenroOfficeName);
}

function workflow_penro_internal_is_management_services_division_name(string $officeName): bool
{
    return workflow_office_name_key($officeName) === 'MANAGEMENTSERVICESDIVISION';
}

function workflow_penro_internal_is_technical_services_division_name(string $officeName): bool
{
    return workflow_office_name_key($officeName) === 'TECHNICALSERVICESDIVISION';
}

function workflow_penro_internal_is_admin_finance_section_name(string $officeName): bool
{
    return workflow_office_name_key($officeName) === 'ADMINFINANCESECTION';
}

function workflow_penro_internal_is_admin_finance_unit_name(string $officeName): bool
{
    static $unitKeys = null;
    if (!is_array($unitKeys)) {
        $unitKeys = [
            'ACCOUNTINGUNIT',
            'BUDGETINGUNIT',
            'HUMANRESOURCESUNIT',
            'GENERALSERVICESUNIT',
            'RECORDSUNIT',
        ];
    }

    return in_array(workflow_office_name_key($officeName), $unitKeys, true);
}

function workflow_penro_internal_division_child_name_keys(string $divisionOfficeName): array
{
    if (workflow_penro_internal_is_management_services_division_name($divisionOfficeName)) {
        return [
            'ADMINFINANCESECTION',
            'PLANNINGSECTION',
            'ICTUNIT',
            'CASHIERINGUNIT',
        ];
    }

    if (workflow_penro_internal_is_technical_services_division_name($divisionOfficeName)) {
        return [
            'MTMATUTUMPROTECTEDLANDSCAPEMMPL',
            'ALLAHVALLEYPROTECTEDLANDSCAPEAVPL',
            'CONSERVATIONDEVELOPMENTSECTION',
            'REGULATIONPERMITTINGSECTION',
            'MONITORINGENFORCEMENTSECTION',
        ];
    }

    return [];
}

function workflow_penro_internal_is_admin_finance_section_office(?array $office): bool
{
    return is_array($office)
        && workflow_penro_internal_is_admin_finance_section_name((string)($office['name'] ?? ''));
}

function workflow_penro_internal_is_admin_finance_unit_office(?array $office): bool
{
    return is_array($office)
        && workflow_penro_internal_is_admin_finance_unit_name((string)($office['name'] ?? ''));
}

function workflow_penro_internal_section_actor_mode(?array $office): string
{
    if (!is_array($office)) {
        return 'unit';
    }

    $officeLevel = workflow_office_level_key((string)($office['level'] ?? ''));
    if ($officeLevel === 'PENRO_UNIT') {
        return 'unit';
    }

    if ($officeLevel === 'PENRO_SECTION') {
        if (workflow_penro_internal_is_admin_finance_unit_office($office)) {
            return 'unit';
        }
        return 'section';
    }

    return 'unit';
}

function workflow_penro_internal_division_allows_destination(array $divisionOffice, array $destinationOffice): bool
{
    $divisionOfficeId = (int)($divisionOffice['id'] ?? 0);
    $destinationParentOfficeId = (int)($destinationOffice['parent_office_id'] ?? 0);
    if ($divisionOfficeId <= 0 || $destinationParentOfficeId <= 0 || $destinationParentOfficeId !== $divisionOfficeId) {
        return false;
    }

    $destinationLevel = workflow_office_level_key((string)($destinationOffice['level'] ?? ''));
    if (!in_array($destinationLevel, ['PENRO_SECTION', 'PENRO_UNIT'], true)) {
        return false;
    }

    $allowedChildKeys = workflow_penro_internal_division_child_name_keys((string)($divisionOffice['name'] ?? ''));
    if ($allowedChildKeys === []) {
        return $destinationLevel === 'PENRO_SECTION';
    }

    return in_array(
        workflow_office_name_key((string)($destinationOffice['name'] ?? '')),
        $allowedChildKeys,
        true
    );
}

function workflow_find_penro_admin_finance_section(PDO $pdo, int $divisionOfficeId): ?array
{
    if ($divisionOfficeId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, name, level, parent_office_id
         FROM offices
         WHERE parent_office_id = :parent_office_id
           AND UPPER(COALESCE(level, \'\')) = \'PENRO_SECTION\'
         ORDER BY id ASC'
    );
    $stmt->execute(['parent_office_id' => $divisionOfficeId]);
    foreach (($stmt->fetchAll() ?: []) as $office) {
        if (workflow_penro_internal_is_admin_finance_section_office($office)) {
            return $office;
        }
    }

    return null;
}

function workflow_penro_internal_section_allows_destination(PDO $pdo, array $sectionOffice, array $destinationOffice): bool
{
    $sectionParentOfficeId = (int)($sectionOffice['parent_office_id'] ?? 0);
    if ($sectionParentOfficeId <= 0) {
        return false;
    }

    if (workflow_penro_internal_is_admin_finance_section_office($sectionOffice)) {
        $destinationParentOfficeId = (int)($destinationOffice['parent_office_id'] ?? 0);
        if ($destinationParentOfficeId <= 0 || $destinationParentOfficeId !== $sectionParentOfficeId) {
            return false;
        }

        return workflow_penro_internal_is_admin_finance_unit_office($destinationOffice);
    }

    if (workflow_penro_internal_is_admin_finance_unit_office($sectionOffice)) {
        $adminFinanceSection = workflow_find_penro_admin_finance_section($pdo, $sectionParentOfficeId);
        $adminFinanceSectionId = (int)($adminFinanceSection['id'] ?? 0);
        return $adminFinanceSectionId > 0 && (int)($destinationOffice['id'] ?? 0) === $adminFinanceSectionId;
    }

    return false;
}

function workflow_penro_internal_resolve_section_parent_destination(PDO $pdo, array $sectionOffice): ?array
{
    $sectionParentOfficeId = (int)($sectionOffice['parent_office_id'] ?? 0);
    if ($sectionParentOfficeId <= 0) {
        return null;
    }

    if (workflow_penro_internal_is_admin_finance_unit_office($sectionOffice)) {
        return workflow_find_penro_admin_finance_section($pdo, $sectionParentOfficeId);
    }

    return workflow_get_office_context($pdo, $sectionParentOfficeId);
}

function workflow_cenro_internal_section_unit_name_keys(string $sectionOfficeName): array
{
    $sectionKey = workflow_office_name_key($sectionOfficeName);
    if ($sectionKey === 'MONITORINGANDENFORCEMENTSECTIONMES') {
        return [
            'PATROLLINGFORESTSURVEILLANCE',
            'ENFORCEMENTANDMONITORINGTENUREASSESSMENT',
        ];
    }
    if ($sectionKey === 'CONSERVATIONANDDEVELOPMENTSECTIONCDS') {
        return [
            'NATIONALGREENINGPROGRAM',
            'COASTALANDMARINEECOSYSTEMMANAGEMENTPROGRAM',
        ];
    }
    if ($sectionKey === 'REGULATIONANDPERMITTINGSECTIONRPS') {
        return [
            'SURVEYANDMAPPINGUNIT',
            'PATENTSANDDEEDSUNIT',
            'PERMITTINGANDLICENSINGUNIT',
        ];
    }
    if ($sectionKey === 'PLANNINGANDSUPPORTUNITPSU') {
        return [];
    }

    return [];
}

function workflow_cenro_internal_is_valid_unit_name(string $unitOfficeName): bool
{
    static $validUnitKeys = null;
    if (!is_array($validUnitKeys)) {
        $validUnitKeys = array_merge(
            workflow_cenro_internal_section_unit_name_keys('Monitoring and Enforcement Section (MES)'),
            workflow_cenro_internal_section_unit_name_keys('Conservation and Development Section (CDS)'),
            workflow_cenro_internal_section_unit_name_keys('Regulation and Permitting Section (RPS)')
        );
    }

    return in_array(workflow_office_name_key($unitOfficeName), $validUnitKeys, true);
}

function workflow_cenro_internal_section_allows_unit_destination(array $sectionOffice, array $destinationOffice): bool
{
    $sectionOfficeId = (int)($sectionOffice['id'] ?? 0);
    $destinationParentOfficeId = (int)($destinationOffice['parent_office_id'] ?? 0);
    if ($sectionOfficeId <= 0 || $destinationParentOfficeId <= 0 || $destinationParentOfficeId !== $sectionOfficeId) {
        return false;
    }

    $destinationLevel = workflow_office_level_key((string)($destinationOffice['level'] ?? ''));
    if ($destinationLevel !== 'CENRO_UNIT') {
        return false;
    }

    $allowedUnitKeys = workflow_cenro_internal_section_unit_name_keys((string)($sectionOffice['name'] ?? ''));
    if ($allowedUnitKeys === []) {
        return false;
    }

    return in_array(
        workflow_office_name_key((string)($destinationOffice['name'] ?? '')),
        $allowedUnitKeys,
        true
    );
}

function workflow_cenro_belongs_to_penro(PDO $pdo, int $penroOfficeId, int $cenroOfficeId): bool
{
    if ($penroOfficeId <= 0 || $cenroOfficeId <= 0) {
        return false;
    }

    $penroOffice = workflow_get_office_context($pdo, $penroOfficeId);
    $cenroOffice = workflow_get_office_context($pdo, $cenroOfficeId);
    if (!$penroOffice || !$cenroOffice) {
        return false;
    }

    $cenroParentPenro = workflow_find_parent_office_by_level($pdo, $cenroOfficeId, 'PROVINCIAL');
    $cenroParentPenroId = (int)($cenroParentPenro['id'] ?? 0);
    if ($cenroParentPenroId > 0 && $cenroParentPenroId === $penroOfficeId) {
        return true;
    }

    return workflow_penro_scope_allows_cenro_name(
        (string)($penroOffice['name'] ?? ''),
        (string)($cenroOffice['name'] ?? '')
    );
}

function workflow_name_matches_records_unit(string $name): bool
{
    $normalized = strtoupper(trim($name));
    if ($normalized === '') {
        return false;
    }

    return str_contains($normalized, 'PACDO')
        || str_contains($normalized, 'RECORDS-UNIT')
        || str_contains($normalized, 'RECORDS UNIT')
        || str_contains($normalized, 'RECORDS_UNIT')
        || str_contains($normalized, 'RECORDSUNIT');
}

function workflow_ard_track_from_role_key(string $roleKey): ?string
{
    $normalized = app_normalize_role_key($roleKey);
    if ($normalized === 'ARD_TS') {
        return 'TS';
    }
    if ($normalized === 'ARD_MS') {
        return 'MS';
    }

    return null;
}

function workflow_division_track_from_name(string $officeName): ?string
{
    static $tsDivisionKeys = null;
    static $msDivisionKeys = null;

    if (!is_array($tsDivisionKeys) || !is_array($msDivisionKeys)) {
        $tsDivisionKeys = array_map('workflow_office_name_key', [
            'Licenses, Patents & Deeds Division',
            'Surveys & Mapping Division',
            'Conservation & Devt. Division',
            'Enforcement Division',
        ]);
        $msDivisionKeys = array_map('workflow_office_name_key', [
            'Legal Division',
            'Planning and Mgt. Division',
            'Planning and Management Division (PMD)',
            'Administrative Division',
            'Finance Division',
        ]);
    }

    $officeKey = workflow_office_name_key($officeName);
    if ($officeKey === '') {
        return null;
    }
    if (in_array($officeKey, $tsDivisionKeys, true)) {
        return 'TS';
    }
    if (in_array($officeKey, $msDivisionKeys, true)) {
        return 'MS';
    }

    return null;
}

function workflow_ard_track_from_office_name(string $officeName): ?string
{
    $name = strtoupper(trim($officeName));
    $key = workflow_office_name_key($officeName);

    if (
        str_contains($key, 'ARDTS')
        || (str_contains($name, 'ASSISTANT REGIONAL DIRECTOR') && str_contains($name, 'TECHNICAL'))
        || str_contains($name, 'TECHNICAL SERVICES')
    ) {
        return 'TS';
    }
    if (
        str_contains($key, 'ARDMS')
        || (str_contains($name, 'ASSISTANT REGIONAL DIRECTOR') && str_contains($name, 'MANAGEMENT'))
        || str_contains($name, 'MANAGEMENT SERVICES')
    ) {
        return 'MS';
    }

    return null;
}

function workflow_find_ard_office_by_track(PDO $pdo, string $track): ?array
{
    $normalizedTrack = strtoupper(trim($track));
    if (!in_array($normalizedTrack, ['TS', 'MS'], true)) {
        return null;
    }

    $roleKey = $normalizedTrack === 'TS' ? 'ARD_TS' : 'ARD_MS';
    $stmt = $pdo->prepare(
        'SELECT TOP (1) o.id, o.name, o.level, o.parent_office_id
         FROM offices o
         INNER JOIN users u ON u.office_id = o.id
         INNER JOIN roles r ON r.id = u.role_id
         WHERE UPPER(r.name) = :role_name
         ORDER BY o.id ASC'
    );
    $stmt->execute(['role_name' => $roleKey]);
    $office = $stmt->fetch();
    if ($office) {
        return $office;
    }

    $likeNeedle = $normalizedTrack === 'TS' ? '%TECHNICAL%' : '%MANAGEMENT%';
    $fallbackStmt = $pdo->prepare(
        'SELECT TOP (1) id, name, level, parent_office_id
         FROM offices
         WHERE UPPER(name) LIKE :needle
            OR UPPER(name) LIKE :ard_short
         ORDER BY id ASC'
    );
    $fallbackStmt->execute([
        'needle' => $likeNeedle,
        'ard_short' => '%ARD ' . $normalizedTrack . '%',
    ]);
    $office = $fallbackStmt->fetch();
    if ($office) {
        return $office;
    }

    return null;
}

function workflow_find_ard_office_for_division(PDO $pdo, int $divisionOfficeId): ?array
{
    if ($divisionOfficeId <= 0) {
        return null;
    }

    $divisionOffice = workflow_get_office_context($pdo, $divisionOfficeId);
    if (!$divisionOffice) {
        return null;
    }

    $divisionLevel = strtoupper(trim((string)($divisionOffice['level'] ?? '')));
    if ($divisionLevel !== 'DIVISION') {
        return null;
    }

    $parentOfficeId = (int)($divisionOffice['parent_office_id'] ?? 0);
    if ($parentOfficeId > 0) {
        $parentOffice = workflow_get_office_context($pdo, $parentOfficeId);
        if ($parentOffice && workflow_ard_track_from_office_name((string)($parentOffice['name'] ?? '')) !== null) {
            return $parentOffice;
        }
    }

    $divisionTrack = workflow_division_track_from_name((string)($divisionOffice['name'] ?? ''));
    if ($divisionTrack === null) {
        return null;
    }

    return workflow_find_ard_office_by_track($pdo, $divisionTrack);
}

function workflow_office_is_ored(array $office): bool
{
    $name = strtoupper(trim((string)($office['name'] ?? '')));
    return str_contains($name, 'ORED') || str_contains($name, 'REGIONAL EXECUTIVE');
}

function workflow_office_is_rbco(array $office): bool
{
    $officeKey = workflow_office_name_key((string)($office['name'] ?? ''));
    return $officeKey === 'RBCORIVERBASINCONTROLOFFICE'
        || $officeKey === 'RIVERBASINCONTROLOFFICE';
}

function workflow_office_track_from_context(PDO $pdo, array $office): ?string
{
    $officeLevel = workflow_office_level_key((string)($office['level'] ?? ''));
    $officeName = (string)($office['name'] ?? '');

    if ($officeLevel === 'DIVISION') {
        return workflow_division_track_from_name($officeName);
    }

    if ($officeLevel === 'SECTION' || $officeLevel === 'UNIT') {
        $parentOfficeId = (int)($office['parent_office_id'] ?? 0);
        if ($parentOfficeId > 0) {
            $parentOffice = workflow_get_office_context($pdo, $parentOfficeId);
            if ($parentOffice) {
                $parentLevel = workflow_office_level_key((string)($parentOffice['level'] ?? ''));
                if ($parentLevel === 'DIVISION') {
                    return workflow_division_track_from_name((string)($parentOffice['name'] ?? ''));
                }

                $parentTrack = workflow_ard_track_from_office_name((string)($parentOffice['name'] ?? ''));
                if ($parentTrack !== null) {
                    return $parentTrack;
                }
            }
        }
    }

    return workflow_ard_track_from_office_name($officeName);
}

function workflow_infer_office_role_id(PDO $pdo, int $officeId): ?int
{
    if ($officeId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT TOP (1) id, name, level
         FROM offices
         WHERE id = :id'
    );
    $stmt->execute(['id' => $officeId]);
    $office = $stmt->fetch();
    if (!$office) {
        return null;
    }

    $level = strtoupper(trim((string)($office['level'] ?? '')));
    $name = strtoupper((string)($office['name'] ?? ''));

    if ($level === 'PROTECTED AREA') {
        return null;
    }
    if ($level === 'COMMUNITY') {
        return workflow_get_role_id_by_key($pdo, 'CENRO_ADMIN_RECORD');
    }
    if ($level === 'CENRO_ADMIN_RECORD') {
        return workflow_get_role_id_by_key($pdo, 'CENRO_ADMIN_RECORD');
    }
    if ($level === 'PAMO_ADMIN') {
        return workflow_get_role_id_by_key($pdo, 'PAMO_ADMIN');
    }
    if ($level === 'PENRO_ADMIN_RECORD') {
        return workflow_get_role_id_by_key($pdo, 'PENRO_ADMIN_RECORD');
    }
    if ($level === 'CENRO_OFFICER') {
        return workflow_get_role_id_by_key($pdo, 'CENRO_OFFICER');
    }
    if ($level === 'PASU_OFFICER') {
        return workflow_get_role_id_by_key($pdo, 'PASU_OFFICER');
    }
    if ($level === 'PENRO_OFFICER') {
        return workflow_get_role_id_by_key($pdo, 'PENRO_OFFICER');
    }
    if ($level === 'CENRO_SECTION') {
        return workflow_get_role_id_by_key($pdo, 'CENRO_SECTION');
    }
    if ($level === 'PENRO_DIVISION') {
        return workflow_get_role_id_by_key($pdo, 'PENRO_DIVISION');
    }
    if ($level === 'CENRO_UNIT') {
        return workflow_get_role_id_by_key($pdo, 'CENRO_UNIT');
    }
    if ($level === 'PAMO_UNIT') {
        return workflow_get_role_id_by_key($pdo, 'PAMO_UNIT');
    }
    if ($level === 'PENRO_SECTION') {
        return workflow_get_role_id_by_key($pdo, 'PENRO_SECTION');
    }
    if ($level === 'PENRO_UNIT') {
        return workflow_get_role_id_by_key($pdo, 'PENRO_SECTION_UNIT');
    }
    if ($level === 'PROVINCIAL') {
        return workflow_get_role_id_by_key($pdo, 'PENRO_ADMIN_RECORD');
    }
    if ($level === 'DIVISION') {
        return workflow_get_role_id_by_key($pdo, 'DIVISION_CHIEF');
    }
    if ($level === 'SECTION') {
        return workflow_get_role_id_by_key($pdo, 'SECTION_STAFF');
    }
    $ardTrack = workflow_ard_track_from_office_name($name);
    if ($ardTrack === 'TS') {
        return workflow_get_role_id_by_key($pdo, 'ARD_TS');
    }
    if ($ardTrack === 'MS') {
        return workflow_get_role_id_by_key($pdo, 'ARD_MS');
    }
    if (workflow_name_matches_records_unit($name)) {
        return workflow_get_role_id_by_key($pdo, 'RECORDS_UNIT');
    }
    if (str_contains($name, 'ORED') || str_contains($name, 'REGIONAL EXECUTIVE')) {
        return workflow_get_role_id_by_key($pdo, 'ORED');
    }

    return null;
}

function workflow_get_office_context(PDO $pdo, int $officeId): ?array
{
    if ($officeId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT TOP (1) id, name, level, parent_office_id
         FROM offices
         WHERE id = :id'
    );
    $stmt->execute(['id' => $officeId]);
    $office = $stmt->fetch();

    return $office ?: null;
}

function workflow_find_parent_office_by_level(PDO $pdo, int $startOfficeId, string $targetLevel): ?array
{
    $target = strtoupper(trim($targetLevel));
    if ($startOfficeId <= 0 || $target === '') {
        return null;
    }

    $visited = [];
    $cursor = $startOfficeId;
    for ($depth = 0; $depth < 12; $depth += 1) {
        if ($cursor <= 0 || isset($visited[$cursor])) {
            break;
        }
        $visited[$cursor] = true;

        $office = workflow_get_office_context($pdo, $cursor);
        if (!$office) {
            break;
        }

        $level = strtoupper(trim((string)($office['level'] ?? '')));
        if ($level === $target) {
            return $office;
        }

        $cursor = (int)($office['parent_office_id'] ?? 0);
    }

    return null;
}

function workflow_find_child_office_by_level(PDO $pdo, int $parentOfficeId, string $targetLevel): ?array
{
    $level = workflow_office_level_key($targetLevel);
    if ($parentOfficeId <= 0 || $level === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT TOP (1) id, name, level, parent_office_id
         FROM offices
         WHERE parent_office_id = :parent_office_id
           AND UPPER(COALESCE(level, \'\')) = :level
         ORDER BY id ASC'
    );
    $stmt->execute([
        'parent_office_id' => $parentOfficeId,
        'level' => $level,
    ]);
    $office = $stmt->fetch();

    return $office ?: null;
}

function workflow_resolve_officer_admin_record_back_destination(PDO $pdo, string $actorRoleKey, array $actor, array $documentSnapshot): int
{
    $normalizedActorRoleKey = app_normalize_role_key($actorRoleKey);
    $expectedLevel = match ($normalizedActorRoleKey) {
        'CENRO_OFFICER' => 'CENRO_ADMIN_RECORD',
        'PENRO_OFFICER' => 'PENRO_ADMIN_RECORD',
        'PASU_OFFICER' => 'PAMO_ADMIN',
        default => '',
    };
    if ($expectedLevel === '') {
        return 0;
    }

    $originatingOfficeId = (int)($documentSnapshot['originating_office_id'] ?? 0);
    if ($originatingOfficeId > 0) {
        $originOffice = workflow_get_office_context($pdo, $originatingOfficeId);
        if ($originOffice && workflow_office_level_key((string)($originOffice['level'] ?? '')) === $expectedLevel) {
            return $originatingOfficeId;
        }
    }

    $actorOfficeId = (int)($actor['office_id'] ?? 0);
    if ($actorOfficeId <= 0) {
        return 0;
    }

    $actorOffice = workflow_get_office_context($pdo, $actorOfficeId);
    $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
    if ($actorParentOfficeId > 0) {
        $siblingAdminOffice = workflow_find_child_office_by_level($pdo, $actorParentOfficeId, $expectedLevel);
        if ($siblingAdminOffice) {
            return (int)($siblingAdminOffice['id'] ?? 0);
        }
    }

    $rootFinder = match ($normalizedActorRoleKey) {
        'CENRO_OFFICER' => 'workflow_find_cenro_root_office',
        'PENRO_OFFICER' => 'workflow_find_penro_root_office',
        'PASU_OFFICER' => 'workflow_find_pamo_root_office',
        default => null,
    };
    if (!is_string($rootFinder) || !function_exists($rootFinder)) {
        return 0;
    }

    $rootOffice = $rootFinder($pdo, $actorOfficeId);
    $rootOfficeId = (int)($rootOffice['id'] ?? 0);
    if ($rootOfficeId <= 0) {
        return 0;
    }

    if (workflow_office_level_key((string)($rootOffice['level'] ?? '')) === $expectedLevel) {
        return $rootOfficeId;
    }

    $rootAdminOffice = workflow_find_child_office_by_level($pdo, $rootOfficeId, $expectedLevel);
    if ($rootAdminOffice) {
        return (int)($rootAdminOffice['id'] ?? 0);
    }

    return 0;
}

function workflow_find_records_unit_office(PDO $pdo): ?array
{
    $stmt = $pdo->query(
        "SELECT TOP (1) id, name, level, parent_office_id
         FROM offices
         WHERE UPPER(name) LIKE '%PACDO%'
            OR UPPER(name) LIKE '%RECORDS-UNIT%'
            OR UPPER(name) LIKE '%RECORDS UNIT%'
            OR UPPER(name) LIKE '%RECORDS_UNIT%'
         ORDER BY id ASC"
    );
    $office = $stmt ? $stmt->fetch() : false;
    if ($office) {
        return $office;
    }

    $fallback = $pdo->query(
        "SELECT TOP (1) o.id, o.name, o.level, o.parent_office_id
         FROM offices o
         INNER JOIN users u ON u.office_id = o.id
         INNER JOIN roles r ON r.id = u.role_id
         WHERE UPPER(REPLACE(REPLACE(r.name, '_', ' '), '-', ' ')) IN ('PACDO', 'RECORDS UNIT')
         ORDER BY o.id ASC"
    );
    $office = $fallback ? $fallback->fetch() : false;
    return $office ?: null;
}

function workflow_find_pacdo_office(PDO $pdo): ?array
{
    return workflow_find_records_unit_office($pdo);
}

function workflow_required_initial_destination_office(PDO $pdo, array $actor, int $originatingOfficeId): ?array
{
    $roleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));

    if ($roleKey === 'CENRO_ADMIN_RECORD') {
        $cenroRoot = workflow_find_cenro_root_office($pdo, $originatingOfficeId);
        if (!$cenroRoot) {
            throw new RuntimeException('Unable to resolve CENRO office context from office hierarchy.');
        }

        $penroRoot = workflow_find_parent_office_by_level($pdo, (int)($cenroRoot['id'] ?? 0), 'PROVINCIAL');
        if (!$penroRoot) {
            throw new RuntimeException('Unable to resolve parent PENRO destination from office hierarchy.');
        }

        $penroAdminOffice = workflow_find_child_office_by_level($pdo, (int)($penroRoot['id'] ?? 0), 'PENRO_ADMIN_RECORD');
        if (!$penroAdminOffice) {
            throw new RuntimeException('Unable to resolve PENRO Admin Record destination from office hierarchy.');
        }

        return [
            'office_id' => (int)$penroAdminOffice['id'],
            'office_name' => (string)$penroAdminOffice['name'],
            'policy_label' => 'CENRO Admin Record documents should route to PENRO Admin Record first. Direct to RECORDS-UNIT is allowed only as bypass.',
        ];
    }

    if ($roleKey === 'PENRO_ADMIN_RECORD') {
        $recordsUnitOffice = workflow_find_records_unit_office($pdo);
        if (!$recordsUnitOffice) {
            throw new RuntimeException('Unable to resolve RECORDS-UNIT destination office.');
        }

        return [
            'office_id' => (int)$recordsUnitOffice['id'],
            'office_name' => (string)$recordsUnitOffice['name'],
            'policy_label' => 'PENRO Admin Record documents must route to RECORDS-UNIT.',
        ];
    }

    return null;
}

function workflow_build_assigned_status(string $officeName): string
{
    $prefix = 'Assigned to ';
    $clean = trim((string)preg_replace('/\s+/', ' ', $officeName));
    if ($clean === '') {
        return 'Assigned to Division';
    }

    $maxNameLen = 50 - strlen($prefix);
    if ($maxNameLen <= 0) {
        return 'Assigned';
    }
    if (strlen($clean) > $maxNameLen) {
        $clean = substr($clean, 0, $maxNameLen);
    }

    return $prefix . $clean;
}

function workflow_resolve_destination_role_id(PDO $pdo, ?int $destinationUserId, ?int $destinationOfficeId): ?int
{
    if ($destinationUserId !== null && $destinationUserId > 0) {
        $stmt = $pdo->prepare('SELECT TOP (1) role_id FROM users WHERE id = :id');
        $stmt->execute(['id' => $destinationUserId]);
        $roleId = $stmt->fetchColumn();
        return $roleId === false ? null : (int)$roleId;
    }

    if ($destinationOfficeId !== null && $destinationOfficeId > 0) {
        return workflow_infer_office_role_id($pdo, $destinationOfficeId);
    }

    return null;
}

function workflow_role_key_from_user_id(PDO $pdo, int $userId): ?string
{
    if ($userId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT TOP (1) r.name
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id'
    );
    $stmt->execute(['id' => $userId]);
    $roleName = $stmt->fetchColumn();
    if ($roleName === false) {
        return null;
    }

    return app_normalize_role_key((string)$roleName);
}

function workflow_destination_is_section_scope(PDO $pdo, int $destinationOfficeId, ?int $destinationUserId): bool
{
    if ($destinationOfficeId > 0) {
        $office = workflow_get_office_context($pdo, $destinationOfficeId);
        if ($office && strtoupper(trim((string)($office['level'] ?? ''))) === 'SECTION') {
            return true;
        }
    }

    if ($destinationUserId !== null && $destinationUserId > 0) {
        $roleKey = workflow_role_key_from_user_id($pdo, $destinationUserId);
        if (in_array($roleKey, ['SECTION_STAFF', 'SECTION'], true)) {
            return true;
        }
    }

    return false;
}

function workflow_assert_section_assignment_scope(
    PDO $pdo,
    array $actor,
    int $destinationOfficeId,
    ?int $destinationUserId,
    string $transitionAction
): void {
    $actionType = strtoupper(trim($transitionAction));
    if (!in_array($actionType, ['FORWARD', 'REROUTE', 'RETURN', 'RELEASE', 'OVERRIDE'], true)) {
        return;
    }

    if (!workflow_destination_is_section_scope($pdo, $destinationOfficeId, $destinationUserId)) {
        return;
    }

    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
    $destinationParentOfficeId = (int)($destinationOffice['parent_office_id'] ?? 0);

    if (workflow_is_regional_ored_role_key($actorRoleKey)) {
        if (in_array($actionType, ['FORWARD', 'REROUTE'], true)) {
            return;
        }
        throw new RuntimeException('ORED can assign to Section only through Forward or Reroute action.');
    }

    if ($actorRoleKey !== 'DIVISION_CHIEF') {
        throw new RuntimeException('Only Division Chief can assign documents to Section/Section Staff.');
    }

    $actorOfficeId = (int)($actor['office_id'] ?? 0);
    if ($actorOfficeId <= 0 || $destinationParentOfficeId <= 0 || $destinationParentOfficeId !== $actorOfficeId) {
        throw new RuntimeException('Division Chief can forward/reroute only to sections under their own division.');
    }
}

function workflow_assert_cenro_internal_assignment_scope(
    PDO $pdo,
    array $actor,
    int $destinationOfficeId,
    string $transitionAction,
    array $documentContext = []
): void {
    $actionType = strtoupper(trim($transitionAction));
    if (!in_array($actionType, ['FORWARD', 'REROUTE', 'RETURN'], true)) {
        return;
    }

    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    $actorOfficeId = (int)($actor['office_id'] ?? 0);
    $actorOffice = $actorOfficeId > 0 ? workflow_get_office_context($pdo, $actorOfficeId) : null;
    $actorOfficeLevel = workflow_office_level_key((string)($actorOffice['level'] ?? ''));
    $isPamoInternalRole = in_array($actorRoleKey, ['PAMO_ADMIN', 'PAMO_UNIT'], true)
        || ($actorRoleKey === 'PASU_OFFICER' && $actorOfficeLevel === 'PASU_OFFICER');
    if ($isPamoInternalRole) {
        if ($actorOfficeId <= 0) {
            throw new RuntimeException('Actor office is required for PAMO internal routing.');
        }

        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new RuntimeException('Destination office is not valid.');
        }

        $destinationLevel = workflow_office_level_key((string)($destinationOffice['level'] ?? ''));
        $destinationRoleId = workflow_infer_office_role_id($pdo, $destinationOfficeId);
        $destinationRoleName = '';
        if ($destinationRoleId !== null) {
            $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
            $roleStmt->execute(['id' => $destinationRoleId]);
            $destinationRoleName = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
        }

        $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
        $actorRoot = workflow_find_pamo_root_office($pdo, $actorOfficeId);
        $destinationRoot = workflow_find_pamo_root_office($pdo, $destinationOfficeId);
        $actorRootId = (int)($actorRoot['id'] ?? 0);
        $destinationRootId = (int)($destinationRoot['id'] ?? 0);
        $isSamePamoRoot = $actorRootId > 0 && $destinationRootId > 0 && $actorRootId === $destinationRootId;

        if ($actorRoleKey === 'PAMO_ADMIN') {
            $isOfficerTarget = $destinationRoleName === 'PASU_OFFICER'
                && $destinationLevel === 'PASU_OFFICER'
                && $isSamePamoRoot;
            $isPenroTarget = $destinationRoleName === 'PENRO_ADMIN_RECORD' && $destinationLevel === 'PENRO_ADMIN_RECORD';
            $isCenroTarget = $destinationRoleName === 'CENRO_ADMIN_RECORD' && $destinationLevel === 'CENRO_ADMIN_RECORD';
            if (!$isOfficerTarget && !$isPenroTarget && !$isCenroTarget) {
                throw new RuntimeException('PAMO Admin can route only to same-chain PASU, CENRO Admin Record, or PENRO Admin Record.');
            }
            return;
        }

        if ($actorRoleKey === 'PASU_OFFICER') {
            $isPostSignStage = workflow_document_has_local_sign_stage(
                $pdo,
                $documentContext,
                $actorOfficeId,
                $actorRoleKey
            );
            $originatingOfficeId = (int)($documentContext['originating_office_id'] ?? 0);
            $isUnitTarget = $destinationRoleName === 'PAMO_UNIT'
                && $destinationLevel === 'PAMO_UNIT'
                && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorOfficeId;
            $isAdminTarget = $destinationRoleName === 'PAMO_ADMIN'
                && $destinationLevel === 'PAMO_ADMIN'
                && $isSamePamoRoot;

            if ($actionType === 'REROUTE') {
                if (!$isUnitTarget) {
                    throw new RuntimeException('PASU reroute can target only child PAMO Unit destinations.');
                }
                return;
            }

            if ($actionType === 'FORWARD' && $isPostSignStage) {
                $isOriginAdmin = $isAdminTarget
                    && (
                        ($originatingOfficeId > 0 && $destinationOfficeId === $originatingOfficeId)
                        || $isSamePamoRoot
                    );
                if (!$isOriginAdmin) {
                    throw new RuntimeException('After signing, PASU can forward only back to the originating PAMO Admin office.');
                }
                return;
            }

            if (!$isUnitTarget && !$isAdminTarget) {
                throw new RuntimeException('PASU can route only to child PAMO Unit or back to PAMO Admin.');
            }
            return;
        }

        if ($actorRoleKey === 'PAMO_UNIT') {
            if ($destinationRoleName !== 'PASU_OFFICER' || $destinationLevel !== 'PASU_OFFICER') {
                throw new RuntimeException('PAMO Unit can route only back to its parent PASU.');
            }
            if ($actorParentOfficeId > 0 && $destinationOfficeId !== $actorParentOfficeId) {
                throw new RuntimeException('PAMO Unit can route only back to its parent PASU.');
            }
            return;
        }
    }

    $isCenroInternal = in_array($actorRoleKey, ['CENRO_ADMIN_RECORD', 'CENRO_OFFICER', 'CENRO_SECTION', 'CENRO_UNIT'], true);
    $isPenroInternal = in_array($actorRoleKey, ['PENRO_ADMIN_RECORD', 'PENRO_OFFICER', 'PENRO_DIVISION', 'PENRO_SECTION', 'PENRO_SECTION_UNIT'], true);
    if (!$isCenroInternal && !$isPenroInternal) {
        return;
    }

    $adminRole = $isPenroInternal ? 'PENRO_ADMIN_RECORD' : 'CENRO_ADMIN_RECORD';
    $officerRole = $isPenroInternal ? 'PENRO_OFFICER' : 'CENRO_OFFICER';
    $sectionRole = $isPenroInternal ? 'PENRO_DIVISION' : 'CENRO_SECTION';
    $unitRole = $isPenroInternal ? 'PENRO_SECTION' : 'CENRO_UNIT';
    $unitActorRoles = $isPenroInternal ? ['PENRO_SECTION', 'PENRO_SECTION_UNIT'] : ['CENRO_UNIT'];
    $sectionLevelLabel = $isPenroInternal ? 'PENRO Division' : 'CENRO Section';
    $unitLevelLabel = $isPenroInternal ? 'PENRO Section' : 'CENRO Unit';
    $officerLabel = $isPenroInternal ? 'PENRO Officer' : 'CENRO Officer';
    $adminLabel = $isPenroInternal ? 'PENRO Admin Record' : 'CENRO Admin Record';
    $rootFinder = $isPenroInternal ? 'workflow_find_penro_root_office' : 'workflow_find_cenro_root_office';

    $actorOfficeId = (int)($actor['office_id'] ?? 0);
    if ($actorOfficeId <= 0) {
        throw new RuntimeException('Actor office is required for CENRO internal routing.');
    }

    $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
    if (!$destinationOffice) {
        throw new RuntimeException('Destination office is not valid.');
    }
    $destinationLevel = workflow_office_level_key((string)($destinationOffice['level'] ?? ''));
    $destinationRoleId = workflow_infer_office_role_id($pdo, $destinationOfficeId);
    $destinationRoleName = '';
    if ($destinationRoleId !== null) {
        $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
        $roleStmt->execute(['id' => $destinationRoleId]);
        $destinationRoleName = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
    }

    if ($isPenroInternal && $actorRoleKey === 'PENRO_DIVISION') {
        $actorOffice = workflow_get_office_context($pdo, $actorOfficeId);
        $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
        $isSectionChild = is_array($actorOffice)
            && $destinationRoleName === 'PENRO_SECTION'
            && workflow_penro_internal_division_allows_destination($actorOffice, $destinationOffice);
        $isAdminRecordTarget = $destinationRoleName === 'PENRO_ADMIN_RECORD'
            && workflow_is_cenro_admin_record_level($destinationLevel)
            && $actorParentOfficeId > 0
            && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorParentOfficeId;
        $isOfficerParent = $actionType === 'FORWARD'
            && $destinationRoleName === 'PENRO_OFFICER'
            && workflow_is_cenro_officer_level($destinationLevel)
            && $actorParentOfficeId > 0
            && $destinationOfficeId === $actorParentOfficeId;

        if ($actionType === 'REROUTE') {
            if (!$isSectionChild) {
                throw new RuntimeException('PENRO Division reroute can target only valid child PENRO Section destinations under its hierarchy.');
            }
            return;
        }

        if (!$isSectionChild && !$isAdminRecordTarget && !$isOfficerParent) {
            throw new RuntimeException('PENRO Division can route only to valid child PENRO Section destinations, back to PENRO Admin Record, or send back to its parent PENRO Officer.');
        }
        return;
    }

    if ($isPenroInternal && in_array($actorRoleKey, ['PENRO_SECTION', 'PENRO_SECTION_UNIT'], true)) {
        $actorOffice = workflow_get_office_context($pdo, $actorOfficeId);
        if (!$actorOffice) {
            throw new RuntimeException('Actor office is required for PENRO section routing.');
        }

        $actorMode = $actorRoleKey === 'PENRO_SECTION'
            ? 'section'
            : workflow_penro_internal_section_actor_mode($actorOffice);
        if ($actorMode === 'unit') {
            $requiredSection = workflow_find_parent_office_by_level($pdo, $actorOfficeId, 'PENRO_SECTION');
            $requiredSectionOfficeId = (int)($requiredSection['id'] ?? 0);
            if ($requiredSectionOfficeId <= 0 || $destinationOfficeId !== $requiredSectionOfficeId) {
                throw new RuntimeException('PENRO unit can route only back to its parent PENRO Section.');
            }
            return;
        }

        $actorParentDestination = workflow_penro_internal_resolve_section_parent_destination($pdo, $actorOffice);
        $parentDestinationId = (int)($actorParentDestination['id'] ?? 0);
        $isChildUnit = $destinationRoleName === 'PENRO_SECTION'
            && workflow_penro_internal_section_allows_destination($pdo, $actorOffice, $destinationOffice);
        $isParentDestination = $parentDestinationId > 0 && $destinationOfficeId === $parentDestinationId;

        if (workflow_penro_internal_is_admin_finance_section_office($actorOffice)) {
            if ($actionType === 'REROUTE') {
                if (!$isChildUnit) {
                    throw new RuntimeException('Admin & Finance Section reroute can target only its child unit offices.');
                }
                return;
            }

            if (!$isChildUnit && !$isParentDestination) {
                throw new RuntimeException('Admin & Finance Section can route only to its child unit offices or back to its parent PENRO Division.');
            }
            return;
        }

        if (workflow_penro_internal_is_admin_finance_unit_office($actorOffice)) {
            if (!$isParentDestination) {
                throw new RuntimeException('Admin & Finance unit can route only back to Admin & Finance Section.');
            }
            return;
        }

        if (!$isParentDestination) {
            throw new RuntimeException('PENRO Section can route only back to its parent PENRO Division.');
        }
        return;
    }

    if ($actorRoleKey === $officerRole) {
        $isPostSignStage = workflow_document_has_local_sign_stage(
            $pdo,
            $documentContext,
            $actorOfficeId,
            $actorRoleKey
        );
        $originatingOfficeId = (int)($documentContext['originating_office_id'] ?? 0);
        $actorOffice = workflow_get_office_context($pdo, $actorOfficeId);
        $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
        $actorRoot = $rootFinder($pdo, $actorOfficeId);
        $destinationRoot = $rootFinder($pdo, $destinationOfficeId);
        $actorRootId = (int)($actorRoot['id'] ?? 0);
        $destinationRootId = (int)($destinationRoot['id'] ?? 0);
        $isSameCenroRoot = $actorRootId > 0 && $destinationRootId > 0 && $actorRootId === $destinationRootId;
        $isSectionChild = $destinationRoleName === $sectionRole
            && workflow_is_cenro_section_level($destinationLevel)
            && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorOfficeId;
        $isUnitSameRoot = $destinationRoleName === $unitRole
            && workflow_is_cenro_unit_level($destinationLevel)
            && (
                !$isCenroInternal
                || workflow_cenro_internal_is_valid_unit_name((string)($destinationOffice['name'] ?? ''))
            )
            && $isSameCenroRoot;
        $isAdminRecordSameRoot = $destinationRoleName === $adminRole
            && workflow_is_cenro_admin_record_level($destinationLevel)
            && (
                ($actorParentOfficeId > 0 && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorParentOfficeId)
                || $isSameCenroRoot
            );
        $isAdminRecordTarget = $destinationRoleName === $adminRole
            && workflow_is_cenro_admin_record_level($destinationLevel)
            && (
                ($originatingOfficeId > 0 && $destinationOfficeId === $originatingOfficeId)
                || $isAdminRecordSameRoot
            );

        if ($actionType === 'FORWARD' && $isPostSignStage) {
            $isOriginAdminRecord = $destinationRoleName === $adminRole
                && workflow_is_cenro_admin_record_level($destinationLevel)
                && (
                    ($originatingOfficeId > 0 && $destinationOfficeId === $originatingOfficeId)
                    || ($actorParentOfficeId > 0 && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorParentOfficeId)
                    || $isSameCenroRoot
                );
            if (!$isOriginAdminRecord) {
                throw new RuntimeException('After signing, ' . $officerLabel . ' can forward only back to the originating ' . $adminLabel . ' office.');
            }
            return;
        }

        if ($actionType === 'REROUTE') {
            if ($isPostSignStage) {
                throw new RuntimeException('After signing, ' . $officerLabel . ' reroute is disabled. Forward directly to originating ' . $adminLabel . '.');
            }
            if (!$isSectionChild) {
                throw new RuntimeException($officerLabel . ' reroute can target only child ' . $sectionLevelLabel . ' offices.');
            }
            return;
        }

        if ($actionType === 'FORWARD') {
            if (!$isSectionChild && !$isUnitSameRoot && !$isAdminRecordTarget) {
                throw new RuntimeException($officerLabel . ' can forward only to child ' . $sectionLevelLabel . ', bypass to same-chain ' . $unitLevelLabel . ', or send back to ' . $adminLabel . '.');
            }
            return;
        }

        if ($actionType === 'RETURN') {
            $isOriginAdminRecord = $destinationRoleName === $adminRole
                && workflow_is_cenro_admin_record_level($destinationLevel)
                && (
                    ($originatingOfficeId > 0 && $destinationOfficeId === $originatingOfficeId)
                    || (
                        $originatingOfficeId <= 0
                        && (
                            ($actorParentOfficeId > 0 && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorParentOfficeId)
                            || $isSameCenroRoot
                        )
                    )
                );
            if (!$isOriginAdminRecord) {
                throw new RuntimeException($officerLabel . ' return can target only the originating ' . $adminLabel . ' office.');
            }
            return;
        }

        if (!$isSectionChild) {
            throw new RuntimeException($officerLabel . ' can route only to child ' . $sectionLevelLabel . ' offices.');
        }
        return;
    }

    if ($actorRoleKey === $sectionRole) {
        $actorOffice = workflow_get_office_context($pdo, $actorOfficeId);
        $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
        $isUnitChild = $destinationRoleName === $unitRole
            && (
                !$isCenroInternal
                ? (
                    workflow_is_cenro_unit_level($destinationLevel)
                    && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorOfficeId
                )
                : (
                    is_array($actorOffice)
                    && workflow_cenro_internal_section_allows_unit_destination($actorOffice, $destinationOffice)
                )
            );
        $isAdminSibling = $destinationRoleName === $adminRole
            && workflow_is_cenro_admin_record_level($destinationLevel)
            && $actorParentOfficeId > 0
            && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorParentOfficeId;
        $isOfficerParent = $actionType === 'FORWARD'
            && $destinationRoleName === $officerRole
            && workflow_is_cenro_officer_level($destinationLevel)
            && $actorParentOfficeId > 0
            && $destinationOfficeId === $actorParentOfficeId;

        if ($actionType === 'REROUTE') {
            if (!$isUnitChild) {
                throw new RuntimeException($sectionLevelLabel . ' reroute can target only child ' . $unitLevelLabel . ' offices.');
            }
            return;
        }

        if (!$isUnitChild && !$isAdminSibling && !$isOfficerParent) {
            throw new RuntimeException($sectionLevelLabel . ' can route only to its child ' . $unitLevelLabel . ' offices, back to ' . $adminLabel . ', or send back to its parent ' . $officerLabel . '.');
        }
        return;
    }

    if (in_array($actorRoleKey, $unitActorRoles, true)) {
        $requiredParentLevel = $isPenroInternal ? 'PENRO_DIVISION' : 'CENRO_SECTION';
        $requiredParentId = workflow_find_parent_office_by_level($pdo, $actorOfficeId, $requiredParentLevel);
        $requiredParentOfficeId = (int)($requiredParentId['id'] ?? 0);
        if (
            $destinationRoleName !== $sectionRole
            || !workflow_is_cenro_section_level($destinationLevel)
            || $requiredParentOfficeId <= 0
            || $destinationOfficeId !== $requiredParentOfficeId
        ) {
            throw new RuntimeException($unitLevelLabel . ' can route only back to its parent ' . $sectionLevelLabel . '.');
        }
        return;
    }

    if ($actorRoleKey === $adminRole) {
        if ($destinationRoleName === $officerRole && workflow_is_cenro_officer_level($destinationLevel)) {
            $actorRoot = $rootFinder($pdo, $actorOfficeId);
            $destinationRoot = $rootFinder($pdo, $destinationOfficeId);
            $actorRootId = (int)($actorRoot['id'] ?? 0);
            $destinationRootId = (int)($destinationRoot['id'] ?? 0);
            if ($actorRootId <= 0 || $destinationRootId <= 0 || $actorRootId !== $destinationRootId) {
                throw new RuntimeException($adminLabel . ' can route only to the officer under the same office chain.');
            }
            return;
        }

        $allowedExternalDestinations = $isPenroInternal
            ? ['CENRO_ADMIN_RECORD', 'RECORDS_UNIT']
            : ['PENRO_ADMIN_RECORD', 'RECORDS_UNIT'];
        if (in_array($destinationRoleName, $allowedExternalDestinations, true)) {
            return;
        }

        $secondaryExternal = $isPenroInternal ? 'CENRO Admin Record' : 'PENRO Admin Record';
        throw new RuntimeException($adminLabel . ' can route only to ' . $officerLabel . ', ' . $secondaryExternal . ', or PACDO/Records Unit.');
    }
}

function workflow_ensure_document_sender_column(PDO $pdo): void
{
    if (!workflow_table_exists($pdo, 'documents')) {
        throw new RuntimeException('Documents table is missing.');
    }

    if (!workflow_column_exists($pdo, 'documents', 'sender')) {
        try {
            if (db_is_sql_server($pdo)) {
                $pdo->exec('ALTER TABLE documents ADD sender NVARCHAR(255) NULL');
            } else {
                $pdo->exec(
                    "ALTER TABLE documents
                     ADD COLUMN sender VARCHAR(255) NULL DEFAULT NULL
                     AFTER source_type"
                );
            }
        } catch (Throwable $exception) {
            if (!workflow_column_exists($pdo, 'documents', 'sender')) {
                throw $exception;
            }
        }
    }
}

function workflow_ensure_document_client_address_column(PDO $pdo): void
{
    if (!workflow_table_exists($pdo, 'documents')) {
        throw new RuntimeException('Documents table is missing.');
    }

    if (!workflow_column_exists($pdo, 'documents', 'client_address')) {
        try {
            if (db_is_sql_server($pdo)) {
                $pdo->exec('ALTER TABLE documents ADD client_address NVARCHAR(255) NULL');
            } else {
                $pdo->exec(
                    "ALTER TABLE documents
                     ADD COLUMN client_address VARCHAR(255) NULL DEFAULT NULL
                     AFTER external_client_name"
                );
            }
        } catch (Throwable $exception) {
            if (!workflow_column_exists($pdo, 'documents', 'client_address')) {
                throw $exception;
            }
        }
    }
}

function workflow_ensure_document_originating_entity_column(PDO $pdo): void
{
    if (!workflow_table_exists($pdo, 'documents')) {
        throw new RuntimeException('Documents table is missing.');
    }

    if (!workflow_column_exists($pdo, 'documents', 'originating_entity_name')) {
        try {
            if (db_is_sql_server($pdo)) {
                $pdo->exec('ALTER TABLE documents ADD originating_entity_name NVARCHAR(255) NULL');
            } else {
                $pdo->exec(
                    "ALTER TABLE documents
                     ADD COLUMN originating_entity_name VARCHAR(255) NULL DEFAULT NULL
                     AFTER client_address"
                );
            }
        } catch (Throwable $exception) {
            if (!workflow_column_exists($pdo, 'documents', 'originating_entity_name')) {
                throw $exception;
            }
        }
    }
}

function workflow_transition_is_allowed(PDO $pdo, string $actionType, ?int $fromRoleId, ?int $toRoleId): bool
{
    if (!workflow_table_exists($pdo, 'workflow_transitions')) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT TOP (1) 1
         FROM workflow_transitions wt
         WHERE wt.action_type = :action_type
           AND wt.is_active = 1
           AND (
                (:from_is_null_a = 1 AND wt.allowed_from_role_id IS NULL)
                OR
                (:from_is_null_b = 0 AND (wt.allowed_from_role_id IS NULL OR wt.allowed_from_role_id = :from_role_id))
           )
           AND (
                (:to_is_null_a = 1 AND wt.allowed_to_role_id IS NULL)
               OR
               (:to_is_null_b = 0 AND (wt.allowed_to_role_id IS NULL OR wt.allowed_to_role_id = :to_role_id))
           )'
    );
    $stmt->execute([
        'action_type' => strtoupper(trim($actionType)),
        'from_is_null_a' => $fromRoleId === null ? 1 : 0,
        'from_is_null_b' => $fromRoleId === null ? 1 : 0,
        'from_role_id' => $fromRoleId ?? 0,
        'to_is_null_a' => $toRoleId === null ? 1 : 0,
        'to_is_null_b' => $toRoleId === null ? 1 : 0,
        'to_role_id' => $toRoleId ?? 0,
    ]);

    return (bool)$stmt->fetchColumn();
}

function workflow_transition_policy_fallback_allowed(PDO $pdo, string $actionType, ?int $fromRoleId, ?int $toRoleId): bool
{
    $normalizedAction = strtoupper(trim($actionType));
    $oredRoleId = workflow_get_role_id_by_key($pdo, 'ORED');
    $oredSignRoleId = workflow_get_role_id_by_key($pdo, 'ORED_SIGN');
    $divisionChiefRoleId = workflow_get_role_id_by_key($pdo, 'DIVISION_CHIEF');
    $sectionRoleId = workflow_get_role_id_by_key($pdo, 'SECTION_STAFF');
    $ardTsRoleId = workflow_get_role_id_by_key($pdo, 'ARD_TS');
    $ardMsRoleId = workflow_get_role_id_by_key($pdo, 'ARD_MS');
    $penroRoleId = workflow_get_role_id_by_key($pdo, 'PENRO_ADMIN_RECORD');
    $cenroRoleId = workflow_get_role_id_by_key($pdo, 'CENRO_ADMIN_RECORD');
    $pamoAdminRoleId = workflow_get_role_id_by_key($pdo, 'PAMO_ADMIN');
    $pamoOfficerRoleId = workflow_get_role_id_by_key($pdo, 'PASU_OFFICER');
    $pamoUnitRoleId = workflow_get_role_id_by_key($pdo, 'PAMO_UNIT');
    $recordsUnitRoleId = workflow_get_role_id_by_key($pdo, 'RECORDS_UNIT');
    $cenroAdminRecordRoleId = workflow_get_role_id_by_key($pdo, 'CENRO_ADMIN_RECORD');
    $cenroOfficerRoleId = workflow_get_role_id_by_key($pdo, 'CENRO_OFFICER');
    $cenroSectionRoleId = workflow_get_role_id_by_key($pdo, 'CENRO_SECTION');
    $cenroUnitRoleId = workflow_get_role_id_by_key($pdo, 'CENRO_UNIT');
    $penroAdminRecordRoleId = workflow_get_role_id_by_key($pdo, 'PENRO_ADMIN_RECORD');
    $penroOfficerRoleId = workflow_get_role_id_by_key($pdo, 'PENRO_OFFICER');
    $penroDivisionRoleId = workflow_get_role_id_by_key($pdo, 'PENRO_DIVISION');
    $penroSectionRoleId = workflow_get_role_id_by_key($pdo, 'PENRO_SECTION');

    if ($normalizedAction === 'PENDING' && $toRoleId === null && $fromRoleId !== null) {
        $allowedPendingRoleIds = array_values(array_filter([
            $cenroRoleId,
            $cenroAdminRecordRoleId,
            $cenroOfficerRoleId,
            $cenroSectionRoleId,
            $cenroUnitRoleId,
            $pamoAdminRoleId,
            $pamoOfficerRoleId,
            $pamoUnitRoleId,
            $penroAdminRecordRoleId,
            $penroOfficerRoleId,
            $penroDivisionRoleId,
            $penroSectionRoleId,
            $penroRoleId,
            $recordsUnitRoleId,
            $oredRoleId,
            $oredSignRoleId,
            $divisionChiefRoleId,
            $sectionRoleId,
            $ardTsRoleId,
            $ardMsRoleId,
        ], static fn($id): bool => $id !== null));

        foreach ($allowedPendingRoleIds as $allowedRoleId) {
            if ((int)$fromRoleId === (int)$allowedRoleId) {
                return true;
            }
        }
    }

    if ($normalizedAction === 'RECEIVE' && $toRoleId !== null) {
        if (
            ($oredRoleId !== null && (int)$toRoleId === (int)$oredRoleId)
            || ($oredSignRoleId !== null && (int)$toRoleId === (int)$oredSignRoleId)
            || ($ardTsRoleId !== null && (int)$toRoleId === (int)$ardTsRoleId)
            || ($ardMsRoleId !== null && (int)$toRoleId === (int)$ardMsRoleId)
            || ($cenroAdminRecordRoleId !== null && (int)$toRoleId === (int)$cenroAdminRecordRoleId)
            || ($cenroOfficerRoleId !== null && (int)$toRoleId === (int)$cenroOfficerRoleId)
            || ($cenroSectionRoleId !== null && (int)$toRoleId === (int)$cenroSectionRoleId)
            || ($cenroUnitRoleId !== null && (int)$toRoleId === (int)$cenroUnitRoleId)
            || ($pamoAdminRoleId !== null && (int)$toRoleId === (int)$pamoAdminRoleId)
            || ($pamoOfficerRoleId !== null && (int)$toRoleId === (int)$pamoOfficerRoleId)
            || ($pamoUnitRoleId !== null && (int)$toRoleId === (int)$pamoUnitRoleId)
            || ($penroAdminRecordRoleId !== null && (int)$toRoleId === (int)$penroAdminRecordRoleId)
            || ($penroOfficerRoleId !== null && (int)$toRoleId === (int)$penroOfficerRoleId)
            || ($penroDivisionRoleId !== null && (int)$toRoleId === (int)$penroDivisionRoleId)
            || ($penroSectionRoleId !== null && (int)$toRoleId === (int)$penroSectionRoleId)
        ) {
            return true;
        }
    }

    if ($normalizedAction === 'SIGN' && $toRoleId === null && $fromRoleId !== null) {
        if ($cenroOfficerRoleId !== null && (int)$fromRoleId === (int)$cenroOfficerRoleId) {
            return true;
        }
        if ($pamoOfficerRoleId !== null && (int)$fromRoleId === (int)$pamoOfficerRoleId) {
            return true;
        }
        if ($penroOfficerRoleId !== null && (int)$fromRoleId === (int)$penroOfficerRoleId) {
            return true;
        }
    }

    if ($normalizedAction === 'COMPLETE' && $toRoleId === null && $fromRoleId !== null) {
        if ($cenroAdminRecordRoleId !== null && (int)$fromRoleId === (int)$cenroAdminRecordRoleId) {
            return true;
        }
        if ($penroAdminRecordRoleId !== null && (int)$fromRoleId === (int)$penroAdminRecordRoleId) {
            return true;
        }
    }

    if ($normalizedAction === 'RELEASE' && $fromRoleId !== null && $toRoleId !== null) {
        $recordsUnitReleaseTargets = array_values(array_filter([
            $recordsUnitRoleId,
            $penroAdminRecordRoleId,
            $penroRoleId,
            $cenroAdminRecordRoleId,
            $cenroRoleId,
            $pamoAdminRoleId,
        ], static fn($id): bool => $id !== null));

        if (
            $recordsUnitRoleId !== null
            && (int)$fromRoleId === (int)$recordsUnitRoleId
            && in_array((int)$toRoleId, array_map('intval', $recordsUnitReleaseTargets), true)
        ) {
            return true;
        }

        if (
            $cenroAdminRecordRoleId !== null
            && (int)$fromRoleId === (int)$cenroAdminRecordRoleId
            && (
                (int)$toRoleId === (int)$cenroAdminRecordRoleId
                || ($cenroOfficerRoleId !== null && (int)$toRoleId === (int)$cenroOfficerRoleId)
                || ($penroAdminRecordRoleId !== null && (int)$toRoleId === (int)$penroAdminRecordRoleId)
                || ($recordsUnitRoleId !== null && (int)$toRoleId === (int)$recordsUnitRoleId)
            )
        ) {
            return true;
        }

        if (
            $penroAdminRecordRoleId !== null
            && (int)$fromRoleId === (int)$penroAdminRecordRoleId
            && (
                (int)$toRoleId === (int)$penroAdminRecordRoleId
                || ($penroOfficerRoleId !== null && (int)$toRoleId === (int)$penroOfficerRoleId)
                || ($cenroAdminRecordRoleId !== null && (int)$toRoleId === (int)$cenroAdminRecordRoleId)
                || ($recordsUnitRoleId !== null && (int)$toRoleId === (int)$recordsUnitRoleId)
            )
        ) {
            return true;
        }

        if (
            $pamoAdminRoleId !== null
            && (int)$fromRoleId === (int)$pamoAdminRoleId
            && (
                (int)$toRoleId === (int)$pamoAdminRoleId
                || ($pamoOfficerRoleId !== null && (int)$toRoleId === (int)$pamoOfficerRoleId)
                || ($cenroAdminRecordRoleId !== null && (int)$toRoleId === (int)$cenroAdminRecordRoleId)
                || ($penroAdminRecordRoleId !== null && (int)$toRoleId === (int)$penroAdminRecordRoleId)
            )
        ) {
            return true;
        }
    }

    if ($normalizedAction === 'FORWARD' && $fromRoleId !== null && $toRoleId !== null) {
        if (
            $oredRoleId !== null
            && $oredSignRoleId !== null
            && (int)$fromRoleId === (int)$oredRoleId
            && (int)$toRoleId === (int)$oredSignRoleId
        ) {
            return true;
        }

        // ARD flow fallback when transition seed rows are missing.
        $oredToArd = ($oredRoleId !== null || $oredSignRoleId !== null)
            && (
                ($ardTsRoleId !== null && (
                    ($oredRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$ardTsRoleId)
                    || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$oredSignRoleId && (int)$toRoleId === (int)$ardTsRoleId)
                ))
                || ($ardMsRoleId !== null && (
                    ($oredRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$ardMsRoleId)
                    || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$oredSignRoleId && (int)$toRoleId === (int)$ardMsRoleId)
                ))
            );
        if ($oredToArd) {
            return true;
        }

        if (
            $sectionRoleId !== null
            && (
                ($oredRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$sectionRoleId)
                || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$oredSignRoleId && (int)$toRoleId === (int)$sectionRoleId)
            )
        ) {
            return true;
        }

        $ardToDivision = $divisionChiefRoleId !== null
            && (
                ($ardTsRoleId !== null && (int)$fromRoleId === (int)$ardTsRoleId && (int)$toRoleId === (int)$divisionChiefRoleId)
                || ($ardMsRoleId !== null && (int)$fromRoleId === (int)$ardMsRoleId && (int)$toRoleId === (int)$divisionChiefRoleId)
            );
        if ($ardToDivision) {
            return true;
        }

        $divisionToArd = $divisionChiefRoleId !== null
            && (
                ($ardTsRoleId !== null && (int)$fromRoleId === (int)$divisionChiefRoleId && (int)$toRoleId === (int)$ardTsRoleId)
                || ($ardMsRoleId !== null && (int)$fromRoleId === (int)$divisionChiefRoleId && (int)$toRoleId === (int)$ardMsRoleId)
            );
        if ($divisionToArd) {
            return true;
        }

        if (
            $divisionChiefRoleId !== null
            && $oredSignRoleId !== null
            && (int)$fromRoleId === (int)$divisionChiefRoleId
            && (int)$toRoleId === (int)$oredSignRoleId
        ) {
            return true;
        }

        $ardToOred = ($oredRoleId !== null || $oredSignRoleId !== null)
            && (
                ($ardTsRoleId !== null && (
                    ($oredRoleId !== null && (int)$fromRoleId === (int)$ardTsRoleId && (int)$toRoleId === (int)$oredRoleId)
                    || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$ardTsRoleId && (int)$toRoleId === (int)$oredSignRoleId)
                ))
                || ($ardMsRoleId !== null && (
                    ($oredRoleId !== null && (int)$fromRoleId === (int)$ardMsRoleId && (int)$toRoleId === (int)$oredRoleId)
                    || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$ardMsRoleId && (int)$toRoleId === (int)$oredSignRoleId)
                ))
            );
        if ($ardToOred) {
            return true;
        }

        $cenroInternalForward = $cenroAdminRecordRoleId !== null
            && $cenroOfficerRoleId !== null
            && (int)$fromRoleId === (int)$cenroAdminRecordRoleId
            && (int)$toRoleId === (int)$cenroOfficerRoleId;
        if ($cenroInternalForward) {
            return true;
        }
        if (
            $cenroOfficerRoleId !== null
            && $cenroSectionRoleId !== null
            && (int)$fromRoleId === (int)$cenroOfficerRoleId
            && (int)$toRoleId === (int)$cenroSectionRoleId
        ) {
            return true;
        }
        if (
            $cenroOfficerRoleId !== null
            && $cenroUnitRoleId !== null
            && (int)$fromRoleId === (int)$cenroOfficerRoleId
            && (int)$toRoleId === (int)$cenroUnitRoleId
        ) {
            return true;
        }
        if (
            $cenroOfficerRoleId !== null
            && $cenroAdminRecordRoleId !== null
            && (int)$fromRoleId === (int)$cenroOfficerRoleId
            && (int)$toRoleId === (int)$cenroAdminRecordRoleId
        ) {
            return true;
        }
        if (
            $cenroSectionRoleId !== null
            && $cenroUnitRoleId !== null
            && (int)$fromRoleId === (int)$cenroSectionRoleId
            && (int)$toRoleId === (int)$cenroUnitRoleId
        ) {
            return true;
        }
        if (
            $cenroSectionRoleId !== null
            && $cenroOfficerRoleId !== null
            && (int)$fromRoleId === (int)$cenroSectionRoleId
            && (int)$toRoleId === (int)$cenroOfficerRoleId
        ) {
            return true;
        }
        if (
            $cenroUnitRoleId !== null
            && $cenroSectionRoleId !== null
            && (int)$fromRoleId === (int)$cenroUnitRoleId
            && (int)$toRoleId === (int)$cenroSectionRoleId
        ) {
            return true;
        }
        if (
            $cenroSectionRoleId !== null
            && $cenroAdminRecordRoleId !== null
            && (int)$fromRoleId === (int)$cenroSectionRoleId
            && (int)$toRoleId === (int)$cenroAdminRecordRoleId
        ) {
            return true;
        }
        if (
            $cenroAdminRecordRoleId !== null
            && $penroRoleId !== null
            && (int)$fromRoleId === (int)$cenroAdminRecordRoleId
            && (int)$toRoleId === (int)$penroRoleId
        ) {
            return true;
        }
        if (
            $cenroAdminRecordRoleId !== null
            && $recordsUnitRoleId !== null
            && (int)$fromRoleId === (int)$cenroAdminRecordRoleId
            && (int)$toRoleId === (int)$recordsUnitRoleId
        ) {
            return true;
        }
        if (
            $penroAdminRecordRoleId !== null
            && $penroOfficerRoleId !== null
            && (int)$fromRoleId === (int)$penroAdminRecordRoleId
            && (int)$toRoleId === (int)$penroOfficerRoleId
        ) {
            return true;
        }
        if (
            $penroOfficerRoleId !== null
            && $penroDivisionRoleId !== null
            && (int)$fromRoleId === (int)$penroOfficerRoleId
            && (int)$toRoleId === (int)$penroDivisionRoleId
        ) {
            return true;
        }
        if (
            $penroOfficerRoleId !== null
            && $penroAdminRecordRoleId !== null
            && (int)$fromRoleId === (int)$penroOfficerRoleId
            && (int)$toRoleId === (int)$penroAdminRecordRoleId
        ) {
            return true;
        }
        if (
            $penroDivisionRoleId !== null
            && $penroSectionRoleId !== null
            && (int)$fromRoleId === (int)$penroDivisionRoleId
            && (int)$toRoleId === (int)$penroSectionRoleId
        ) {
            return true;
        }
        if (
            $penroSectionRoleId !== null
            && $penroDivisionRoleId !== null
            && (int)$fromRoleId === (int)$penroSectionRoleId
            && (int)$toRoleId === (int)$penroDivisionRoleId
        ) {
            return true;
        }
        if (
            $penroDivisionRoleId !== null
            && $penroAdminRecordRoleId !== null
            && (int)$fromRoleId === (int)$penroDivisionRoleId
            && (int)$toRoleId === (int)$penroAdminRecordRoleId
        ) {
            return true;
        }
        if (
            $penroDivisionRoleId !== null
            && $penroOfficerRoleId !== null
            && (int)$fromRoleId === (int)$penroDivisionRoleId
            && (int)$toRoleId === (int)$penroOfficerRoleId
        ) {
            return true;
        }
        if (
            $penroAdminRecordRoleId !== null
            && $cenroRoleId !== null
            && (int)$fromRoleId === (int)$penroAdminRecordRoleId
            && (int)$toRoleId === (int)$cenroRoleId
        ) {
            return true;
        }
        if (
            $penroAdminRecordRoleId !== null
            && $recordsUnitRoleId !== null
            && (int)$fromRoleId === (int)$penroAdminRecordRoleId
            && (int)$toRoleId === (int)$recordsUnitRoleId
        ) {
            return true;
        }
    }

    if ($normalizedAction === 'RETURN' && $fromRoleId !== null && $toRoleId !== null) {
        $divisionToArdReturn = $divisionChiefRoleId !== null
            && (
                ($ardTsRoleId !== null && (int)$fromRoleId === (int)$divisionChiefRoleId && (int)$toRoleId === (int)$ardTsRoleId)
                || ($ardMsRoleId !== null && (int)$fromRoleId === (int)$divisionChiefRoleId && (int)$toRoleId === (int)$ardMsRoleId)
            );
        if ($divisionToArdReturn) {
            return true;
        }

        if (
            $penroOfficerRoleId !== null
            && $penroAdminRecordRoleId !== null
            && (int)$fromRoleId === (int)$penroOfficerRoleId
            && (int)$toRoleId === (int)$penroAdminRecordRoleId
        ) {
            return true;
        }

        if (
            $cenroOfficerRoleId !== null
            && $cenroAdminRecordRoleId !== null
            && (int)$fromRoleId === (int)$cenroOfficerRoleId
            && (int)$toRoleId === (int)$cenroAdminRecordRoleId
        ) {
            return true;
        }

        if (
            $pamoOfficerRoleId !== null
            && $pamoAdminRoleId !== null
            && (int)$fromRoleId === (int)$pamoOfficerRoleId
            && (int)$toRoleId === (int)$pamoAdminRoleId
        ) {
            return true;
        }

        if (
            $penroRoleId !== null
            && $cenroRoleId !== null
            && (int)$fromRoleId === (int)$penroRoleId
            && (int)$toRoleId === (int)$cenroRoleId
        ) {
            return true;
        }

        if (
            $recordsUnitRoleId !== null
            && $penroRoleId !== null
            && (int)$fromRoleId === (int)$recordsUnitRoleId
            && (int)$toRoleId === (int)$penroRoleId
        ) {
            return true;
        }

        if (
            ($oredRoleId !== null || $oredSignRoleId !== null)
            && $recordsUnitRoleId !== null
            && (
                ($oredRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$recordsUnitRoleId)
                || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$oredSignRoleId && (int)$toRoleId === (int)$recordsUnitRoleId)
            )
        ) {
            return true;
        }
    }

    if ($normalizedAction === 'REROUTE' && $fromRoleId !== null && $toRoleId !== null) {
        // Policy fallback for reroute when workflow transition seed rows are missing.
        $oredToArd = ($oredRoleId !== null || $oredSignRoleId !== null)
            && (
                ($ardTsRoleId !== null && (
                    ($oredRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$ardTsRoleId)
                    || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$oredSignRoleId && (int)$toRoleId === (int)$ardTsRoleId)
                ))
                || ($ardMsRoleId !== null && (
                    ($oredRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$ardMsRoleId)
                    || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$oredSignRoleId && (int)$toRoleId === (int)$ardMsRoleId)
                ))
            );
        if ($oredToArd) {
            return true;
        }

        $ardToDivision = $divisionChiefRoleId !== null
            && (
                ($ardTsRoleId !== null && (int)$fromRoleId === (int)$ardTsRoleId && (int)$toRoleId === (int)$divisionChiefRoleId)
                || ($ardMsRoleId !== null && (int)$fromRoleId === (int)$ardMsRoleId && (int)$toRoleId === (int)$divisionChiefRoleId)
            );
        if ($ardToDivision) {
            return true;
        }

        $ardToOred = ($oredRoleId !== null || $oredSignRoleId !== null)
            && (
                ($ardTsRoleId !== null && (
                    ($oredRoleId !== null && (int)$fromRoleId === (int)$ardTsRoleId && (int)$toRoleId === (int)$oredRoleId)
                    || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$ardTsRoleId && (int)$toRoleId === (int)$oredSignRoleId)
                ))
                || ($ardMsRoleId !== null && (
                    ($oredRoleId !== null && (int)$fromRoleId === (int)$ardMsRoleId && (int)$toRoleId === (int)$oredRoleId)
                    || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$ardMsRoleId && (int)$toRoleId === (int)$oredSignRoleId)
                ))
            );
        if ($ardToOred) {
            return true;
        }

        if (
            $divisionChiefRoleId !== null
            && $divisionChiefRoleId !== null
            && (
                ($oredRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$divisionChiefRoleId)
                || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$oredSignRoleId && (int)$toRoleId === (int)$divisionChiefRoleId)
            )
        ) {
            return true;
        }

        if (
            $sectionRoleId !== null
            && $sectionRoleId !== null
            && (
                ($oredRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$sectionRoleId)
                || ($oredSignRoleId !== null && (int)$fromRoleId === (int)$oredSignRoleId && (int)$toRoleId === (int)$sectionRoleId)
            )
        ) {
            return true;
        }

        if (
            $divisionChiefRoleId !== null
            && $sectionRoleId !== null
            && (int)$fromRoleId === (int)$divisionChiefRoleId
            && (int)$toRoleId === (int)$sectionRoleId
        ) {
            return true;
        }

        if (
            $cenroOfficerRoleId !== null
            && $cenroSectionRoleId !== null
            && (int)$fromRoleId === (int)$cenroOfficerRoleId
            && (int)$toRoleId === (int)$cenroSectionRoleId
        ) {
            return true;
        }

        if (
            $cenroOfficerRoleId !== null
            && $cenroUnitRoleId !== null
            && (int)$fromRoleId === (int)$cenroOfficerRoleId
            && (int)$toRoleId === (int)$cenroUnitRoleId
        ) {
            return true;
        }

        if (
            $cenroOfficerRoleId !== null
            && $cenroAdminRecordRoleId !== null
            && (int)$fromRoleId === (int)$cenroOfficerRoleId
            && (int)$toRoleId === (int)$cenroAdminRecordRoleId
        ) {
            return true;
        }

        if (
            $cenroSectionRoleId !== null
            && $cenroUnitRoleId !== null
            && (int)$fromRoleId === (int)$cenroSectionRoleId
            && (int)$toRoleId === (int)$cenroUnitRoleId
        ) {
            return true;
        }

        if (
            $pamoOfficerRoleId !== null
            && $pamoUnitRoleId !== null
            && (int)$fromRoleId === (int)$pamoOfficerRoleId
            && (int)$toRoleId === (int)$pamoUnitRoleId
        ) {
            return true;
        }

        if (
            $penroOfficerRoleId !== null
            && $penroDivisionRoleId !== null
            && (int)$fromRoleId === (int)$penroOfficerRoleId
            && (int)$toRoleId === (int)$penroDivisionRoleId
        ) {
            return true;
        }
        if (
            $penroDivisionRoleId !== null
            && $penroSectionRoleId !== null
            && (int)$fromRoleId === (int)$penroDivisionRoleId
            && (int)$toRoleId === (int)$penroSectionRoleId
        ) {
            return true;
        }
        if (
            $penroSectionRoleId !== null
            && (int)$fromRoleId === (int)$penroSectionRoleId
            && (int)$toRoleId === (int)$penroSectionRoleId
        ) {
            return true;
        }
    }

    return false;
}

function workflow_assert_transition_allowed(PDO $pdo, string $actionType, ?int $fromRoleId, ?int $toRoleId): void
{
    if (!workflow_table_exists($pdo, 'workflow_transitions')) {
        throw new RuntimeException('Workflow transition rules are missing. Apply latest database migration.');
    }

    if (!workflow_transition_is_allowed($pdo, $actionType, $fromRoleId, $toRoleId)) {
        if (workflow_transition_policy_fallback_allowed($pdo, $actionType, $fromRoleId, $toRoleId)) {
            return;
        }
        throw new RuntimeException('Transition denied by workflow policy.');
    }
}

function workflow_assert_actor_can_route_document(array $document, array $actor, string $actionType): void
{
    $actionType = strtoupper(trim($actionType));
    $isCurrentHolder = (int)($document['current_holder_user_id'] ?? 0) === (int)$actor['id'];
    $isCurrentOfficeMember = (int)($document['current_office_id'] ?? 0) === (int)$actor['office_id'];
    $actorRole = (string)($actor['role_name'] ?? '');

    if ($actionType === 'OVERRIDE') {
        if (!workflow_can_override_role($actorRole)) {
            throw new RuntimeException('Only ORED can perform override.');
        }
        return;
    }

    if ($actionType === 'REROUTE') {
        if (!$isCurrentHolder && !workflow_is_supervisor_role($actorRole) && !workflow_can_override_role($actorRole)) {
            throw new RuntimeException('User is not authorized to reroute this document.');
        }
        return;
    }

    if (!$isCurrentHolder && !$isCurrentOfficeMember && !workflow_can_override_role($actorRole)) {
        throw new RuntimeException('User is not authorized to route this document.');
    }
}

function workflow_assert_ored_reroute_policy(
    PDO $pdo,
    array $actor,
    ?int $destinationRoleId,
    int $destinationOfficeId,
    string $remarks
): void {
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    if (!workflow_actor_is_ored_signer($pdo, $actor)) {
        return;
    }

    if (trim($remarks) === '') {
        throw new RuntimeException('ORED reroute requires remarks describing the executive diversion reason.');
    }

    $ardTsRoleId = workflow_get_role_id_by_key($pdo, 'ARD_TS');
    $ardMsRoleId = workflow_get_role_id_by_key($pdo, 'ARD_MS');
    $isArdDestination = $destinationRoleId !== null
        && (
            ($ardTsRoleId !== null && (int)$destinationRoleId === (int)$ardTsRoleId)
            || ($ardMsRoleId !== null && (int)$destinationRoleId === (int)$ardMsRoleId)
        );
    $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
    $destinationLevel = strtoupper(trim((string)($destinationOffice['level'] ?? '')));
    $destinationName = strtoupper(trim((string)($destinationOffice['name'] ?? '')));
    $isDivisionDestination = $destinationLevel === 'DIVISION' || str_contains($destinationName, 'DIVISION');
    $isSectionDestination = $destinationLevel === 'SECTION' || str_contains($destinationName, 'SECTION');
    if ($isArdDestination || $isDivisionDestination || $isSectionDestination) {
        return;
    }

    $destinationName = trim((string)($destinationOffice['name'] ?? 'selected office'));
    if ($destinationName === '') {
        $destinationName = 'selected office';
    }
    throw new RuntimeException(
        'ORED reroute can target only ARD TS/MS, Division, or Section. Selected destination "' . $destinationName . '" is not allowed.'
    );
}

function workflow_assert_ard_reroute_policy(
    PDO $pdo,
    array $actor,
    int $destinationOfficeId,
    string $remarks
): void {
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    if (!in_array($actorRoleKey, ['ARD_TS', 'ARD_MS'], true)) {
        return;
    }

    if (trim($remarks) === '') {
        throw new RuntimeException('ARD reroute requires remarks describing the reroute reason.');
    }

    $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
    if (!$destinationOffice) {
        throw new InvalidArgumentException('Destination office is not valid.');
    }

    $destinationLevel = strtoupper(trim((string)($destinationOffice['level'] ?? '')));
    if ($destinationLevel !== 'DIVISION') {
        throw new RuntimeException('ARD reroute can target only Division offices under the same ARD track.');
    }

    $actorTrack = workflow_ard_track_from_role_key($actorRoleKey);
    $destinationTrack = workflow_division_track_from_name((string)($destinationOffice['name'] ?? ''));
    if ($actorTrack === null || $destinationTrack === null) {
        throw new RuntimeException('Unable to determine ARD/Division track mapping for reroute destination.');
    }
    if ($actorTrack !== $destinationTrack) {
        throw new RuntimeException('ARD reroute can target only divisions under the same ARD track.');
    }
}

function workflow_assert_division_reroute_policy(
    PDO $pdo,
    array $actor,
    int $destinationOfficeId,
    string $remarks
): void {
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    if ($actorRoleKey !== 'DIVISION_CHIEF') {
        return;
    }

    if (trim($remarks) === '') {
        throw new RuntimeException('Division reroute requires remarks describing the reroute reason.');
    }

    $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
    if (!$destinationOffice) {
        throw new InvalidArgumentException('Destination office is not valid.');
    }

    $destinationLevel = strtoupper(trim((string)($destinationOffice['level'] ?? '')));
    if ($destinationLevel !== 'SECTION') {
        throw new RuntimeException('Division reroute can target only Section offices under your division.');
    }

    $destinationParentOfficeId = (int)($destinationOffice['parent_office_id'] ?? 0);
    $actorOfficeId = (int)($actor['office_id'] ?? 0);
    if ($actorOfficeId <= 0 || $destinationParentOfficeId <= 0 || $destinationParentOfficeId !== $actorOfficeId) {
        throw new RuntimeException('Division reroute can target only sections under your own division.');
    }
}

function workflow_assert_cenro_reroute_policy(
    PDO $pdo,
    array $actor,
    int $destinationOfficeId,
    string $remarks
): void {
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    $isCenroRole = in_array($actorRoleKey, ['CENRO_OFFICER', 'CENRO_SECTION'], true);
    $isPenroRole = in_array($actorRoleKey, ['PENRO_OFFICER', 'PENRO_DIVISION'], true);
    $actorOfficeId = (int)($actor['office_id'] ?? 0);
    $actorOffice = $actorOfficeId > 0 ? workflow_get_office_context($pdo, $actorOfficeId) : null;
    $isPamoRole = $actorRoleKey === 'PASU_OFFICER'
        && workflow_office_level_key((string)($actorOffice['level'] ?? '')) === 'PASU_OFFICER';
    if (!$isCenroRole && !$isPenroRole && !$isPamoRole) {
        return;
    }

    if ($isPamoRole) {
        if (trim($remarks) === '') {
            throw new RuntimeException('PASU reroute requires remarks describing the reroute reason.');
        }

        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }

        if ($actorOfficeId <= 0) {
            throw new RuntimeException('Actor office is required for PAMO reroute.');
        }

        $destinationLevel = workflow_office_level_key((string)($destinationOffice['level'] ?? ''));
        if ($destinationLevel !== 'PAMO_UNIT' || (int)($destinationOffice['parent_office_id'] ?? 0) !== $actorOfficeId) {
            throw new RuntimeException('PASU reroute can target only child PAMO Unit offices.');
        }
        return;
    }

    $officerRole = $isPenroRole ? 'PENRO_OFFICER' : 'CENRO_OFFICER';
    $divisionRole = $isPenroRole ? 'PENRO_DIVISION' : 'CENRO_SECTION';
    $sectionRole = $isPenroRole ? 'PENRO_SECTION' : 'CENRO_UNIT';
    $officerLabel = $isPenroRole ? 'PENRO Officer' : 'CENRO Officer';
    $divisionLabel = $isPenroRole ? 'PENRO Division' : 'CENRO Section';
    $sectionLabel = $isPenroRole ? 'PENRO Section' : 'CENRO Unit';
    $rootFinder = $isPenroRole ? 'workflow_find_penro_root_office' : 'workflow_find_cenro_root_office';

    if (trim($remarks) === '') {
        $actorLabel = $actorRoleKey === $officerRole ? $officerLabel : $divisionLabel;
        throw new RuntimeException($actorLabel . ' reroute requires remarks describing the reroute reason.');
    }

    $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
    if (!$destinationOffice) {
        throw new InvalidArgumentException('Destination office is not valid.');
    }

    $destinationLevel = workflow_office_level_key((string)($destinationOffice['level'] ?? ''));
    $destinationRoleId = workflow_infer_office_role_id($pdo, $destinationOfficeId);
    $destinationRoleName = '';
    if ($destinationRoleId !== null) {
        $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
        $roleStmt->execute(['id' => $destinationRoleId]);
        $destinationRoleName = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
    }

    $actorOfficeId = (int)($actor['office_id'] ?? 0);
    if ($actorOfficeId <= 0) {
        throw new RuntimeException('Actor office is required for CENRO reroute.');
    }

    if ($actorRoleKey === $divisionRole) {
        $isUnitChild = $destinationRoleName === $sectionRole
            && workflow_is_cenro_unit_level($destinationLevel)
            && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorOfficeId;
        if (!$isUnitChild) {
            throw new RuntimeException($divisionLabel . ' reroute can target only child ' . $sectionLabel . ' offices.');
        }
        return;
    }

    $actorOffice = workflow_get_office_context($pdo, $actorOfficeId);
    $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
    $actorRoot = $rootFinder($pdo, $actorOfficeId);
    $destinationRoot = $rootFinder($pdo, $destinationOfficeId);
    $actorRootId = (int)($actorRoot['id'] ?? 0);
    $destinationRootId = (int)($destinationRoot['id'] ?? 0);
    $isSameCenroRoot = $actorRootId > 0 && $destinationRootId > 0 && $actorRootId === $destinationRootId;

    $isSectionChild = $destinationRoleName === $divisionRole
        && workflow_is_cenro_section_level($destinationLevel)
        && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorOfficeId;
    if (!$isSectionChild) {
        throw new RuntimeException($officerLabel . ' reroute can target only child ' . $divisionLabel . ' offices.');
    }
}

function workflow_can_receive_regional_ored_reviewer_assignment(PDO $pdo, array $document, array $receiver): bool
{
    $receiverRoleKey = app_normalize_role_key((string)($receiver['role_name'] ?? ''));
    if ($receiverRoleKey !== 'ORED') {
        return false;
    }

    $receiverOfficeId = (int)($receiver['office_id'] ?? 0);
    $pendingOfficeId = (int)($document['pending_office_id'] ?? 0);
    $pendingUserId = (int)($document['pending_user_id'] ?? 0);
    if ($receiverOfficeId <= 0 || $pendingOfficeId <= 0 || $pendingUserId <= 0 || $receiverOfficeId !== $pendingOfficeId) {
        return false;
    }

    try {
        $pendingUser = workflow_get_user_context($pdo, $pendingUserId);
    } catch (Throwable $exception) {
        return false;
    }

    return app_normalize_role_key((string)($pendingUser['role_name'] ?? '')) === 'ORED'
        && (int)($pendingUser['office_id'] ?? 0) === $receiverOfficeId;
}

function workflow_assert_receive_permission(PDO $pdo, array $document, array $receiver): void
{
    $receiverRole = (string)($receiver['role_name'] ?? '');
    $canOverride = workflow_can_override_role($receiverRole);

    $pendingUserId = (int)($document['pending_user_id'] ?? 0);
    $pendingOfficeId = (int)($document['pending_office_id'] ?? 0);

    if ($pendingOfficeId <= 0 && $pendingUserId <= 0) {
        throw new RuntimeException('Document is not awaiting receive.');
    }

    if (
        $pendingUserId > 0
        && $pendingUserId !== (int)$receiver['id']
        && !$canOverride
        && !workflow_can_receive_regional_ored_reviewer_assignment($pdo, $document, $receiver)
    ) {
        throw new RuntimeException('Document is pending for a different user.');
    }

    if ($pendingOfficeId > 0 && $pendingOfficeId !== (int)$receiver['office_id'] && !$canOverride) {
        throw new RuntimeException('Document is pending for a different office.');
    }
}

function workflow_generate_tracking_id(PDO $pdo): string
{
    $year = date('Y');
    $prefix = 'DENR-XII-' . $year . '-';

    $stmt = $pdo->prepare(
        'SELECT TOP (1) tracking_id
         FROM documents
         WHERE tracking_id LIKE :prefix
         ORDER BY id DESC'
    );
    $stmt->execute(['prefix' => $prefix . '%']);
    $latest = (string)($stmt->fetchColumn() ?: '');

    $nextNumber = 1;
    if ($latest !== '' && preg_match('/(\d+)$/', $latest, $matches) === 1) {
        $nextNumber = ((int)$matches[1]) + 1;
    }

    return $prefix . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
}

function workflow_can_create_custom_document_type(string $roleName): bool
{
    return in_array(app_normalize_role_key($roleName), ['RECORDS_UNIT', 'PENRO_ADMIN_RECORD', 'PAMO_ADMIN'], true);
}

function workflow_extract_custom_others_document_type(?string $remarks): string
{
    $value = trim((string)$remarks);
    if ($value === '') {
        return '';
    }

    if (preg_match('/Specified document type \(Others\):\s*([^|]+)/i', $value, $matches) !== 1) {
        return '';
    }

    return trim((string)($matches[1] ?? ''));
}

function workflow_strip_custom_others_document_type_from_remarks(?string $remarks): string
{
    $value = trim((string)$remarks);
    if ($value === '') {
        return '';
    }

    $value = preg_replace('/\s*\|?\s*Specified document type \(Others\):\s*[^|]+/i', '', $value);
    $value = preg_replace('/\s*\|\s*\|+\s*/', ' | ', (string)$value);
    $value = trim((string)$value, " \t\n\r\0\x0B|");

    return $value;
}

function workflow_extract_complexity_category_from_remarks(?string $remarks): string
{
    $value = trim((string)$remarks);
    if ($value === '') {
        return '';
    }

    if (stripos($value, 'Highly Technical') !== false) {
        return 'Highly Technical';
    }
    if (preg_match('/Color classification:\s*[^|]*\bSimple\b/i', $value) === 1) {
        return 'Simple';
    }
    if (preg_match('/Color classification:\s*[^|]*\bComplex\b/i', $value) === 1) {
        return 'Complex';
    }

    return '';
}

function workflow_format_document_type_label(string $documentType, string $artaCategory = '', ?string $remarks = null): string
{
    $resolvedType = trim($documentType);
    if ($resolvedType === '') {
        $resolvedType = 'Uncategorized';
    }

    $normalizedType = strtolower(preg_replace('/\s+/', ' ', $resolvedType) ?? '');
    $customOthersType = workflow_extract_custom_others_document_type($remarks);
    if ($customOthersType !== '' && in_array($normalizedType, ['others', 'other'], true)) {
        $resolvedType = 'Others: ' . $customOthersType;
    }

    $resolvedCategory = trim($artaCategory);
    $normalizedCategory = strtolower(preg_replace('/\s+/', ' ', $resolvedCategory) ?? '');
    if ($customOthersType !== '' && in_array($normalizedCategory, ['', 'others', 'other'], true)) {
        $resolvedCategory = workflow_extract_complexity_category_from_remarks($remarks);
        if ($resolvedCategory === '') {
            $resolvedCategory = 'Simple';
        }
    }

    return $resolvedCategory !== ''
        ? $resolvedType . ' (' . $resolvedCategory . ')'
        : $resolvedType;
}

function workflow_create_document_type(PDO $pdo, array $payload, int $createdByUserId): int
{
    $user = workflow_get_user_context($pdo, $createdByUserId);
    $isCustom = !empty($payload['is_custom']);

    if ($isCustom && !workflow_can_create_custom_document_type((string)($user['role_name'] ?? ''))) {
        throw new RuntimeException('Only RECORDS-UNIT can add custom document types.');
    }

    $name = trim((string)($payload['name'] ?? ''));
    $category = trim((string)($payload['category'] ?? 'Simple'));
    $days = (int)($payload['arta_days_limit'] ?? 3);
    $color = trim((string)($payload['indicator_color'] ?? 'Yellow'));

    if ($name === '') {
        throw new InvalidArgumentException('Document type name is required.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO document_types (name, category, arta_days_limit, indicator_color, is_custom, created_by_role_id, is_active)
         VALUES (:name, :category, :days, :color, :is_custom, :created_by_role_id, 1)'
    );
    $stmt->execute([
        'name' => $name,
        'category' => $category,
        'days' => max(1, $days),
        'color' => $color,
        'is_custom' => $isCustom ? 1 : 0,
        'created_by_role_id' => $isCustom ? (int)$user['role_id'] : null,
    ]);

    return (int)$pdo->lastInsertId();
}

function workflow_log_action(PDO $pdo, array $payload): void
{
    $actionScope = strtoupper((string)($payload['action_scope'] ?? 'ACTION'));
    if (!in_array($actionScope, ['ACTION', 'CUSTODY'], true)) {
        throw new InvalidArgumentException('Invalid action scope.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO activity_logs (
            document_id, user_id, action_type, action_scope, destination_office_id, destination_user_id, remarks, is_visible_on_slip, created_at
         ) VALUES (
            :document_id, :user_id, :action_type, :action_scope, :destination_office_id, :destination_user_id, :remarks, :is_visible_on_slip, SYSDATETIME()
         )'
    );
    $stmt->execute([
        'document_id' => (int)$payload['document_id'],
        'user_id' => (int)$payload['user_id'],
        'action_type' => (string)$payload['action_type'],
        'action_scope' => $actionScope,
        'destination_office_id' => $payload['destination_office_id'] ?? null,
        'destination_user_id' => $payload['destination_user_id'] ?? null,
        'remarks' => $payload['remarks'] ?? null,
        'is_visible_on_slip' => !empty($payload['is_visible_on_slip']) ? 1 : 0,
    ]);
}

function workflow_create_document(PDO $pdo, array $payload, int $actorUserId): array
{
    $user = workflow_get_user_context($pdo, $actorUserId);

    $subject = trim((string)($payload['subject'] ?? ''));
    $documentTypeId = (int)($payload['document_type_id'] ?? 0);
    $sourceType = workflow_normalize_source_type((string)($payload['source_type'] ?? 'INTERNAL'));
    $sender = trim((string)($payload['sender'] ?? ''));
    $externalClientName = trim((string)($payload['external_client_name'] ?? ''));
    $clientAddress = trim((string)($payload['client_address'] ?? ''));
    $originatingOfficeId = (int)($payload['originating_office_id'] ?? $user['office_id']);
    $originatingEntityName = trim((string)($payload['originating_entity_name'] ?? ''));
    $remarks = trim((string)($payload['remarks'] ?? ''));
    $artaCategoryOverride = trim((string)($payload['arta_category_override'] ?? ''));
    $artaDaysLimitOverride = (int)($payload['arta_days_limit_override'] ?? 0);

    if ($subject === '') {
        throw new InvalidArgumentException('Subject is required.');
    }
    if ($documentTypeId <= 0) {
        throw new InvalidArgumentException('Document type is required.');
    }
    if ($sourceType === 'EXTERNAL' && $externalClientName === '') {
        throw new InvalidArgumentException('External client name is required for external intake.');
    }
    if ($sourceType === 'EXTERNAL' && $clientAddress === '') {
        throw new InvalidArgumentException('Client address is required for external intake.');
    }
    if ($originatingEntityName === '') {
        $originOffice = workflow_get_office_context($pdo, $originatingOfficeId);
        $originatingEntityName = trim((string)($originOffice['name'] ?? ''));
    }
    if ($originatingEntityName === '') {
        throw new InvalidArgumentException('Originating office / entity is required.');
    }

    if ($sender === '') {
        if ($sourceType === 'EXTERNAL' && $externalClientName !== '') {
            $sender = $externalClientName;
        } else {
            $originOffice = $originOffice ?? workflow_get_office_context($pdo, $originatingOfficeId);
            $sender = trim((string)($originOffice['name'] ?? ''));
        }
    }
    if ($sender === '') {
        throw new InvalidArgumentException('Sender is required.');
    }

    $trackingId = workflow_generate_tracking_id($pdo);
    $hasArtaCategoryOverrideColumn = workflow_column_exists($pdo, 'documents', 'arta_category_override');
    $hasArtaDaysOverrideColumn = workflow_column_exists($pdo, 'documents', 'arta_days_limit_override');
    $hasSenderColumn = workflow_column_exists($pdo, 'documents', 'sender');
    $hasClientAddressColumn = workflow_column_exists($pdo, 'documents', 'client_address');
    $hasOriginatingEntityColumn = workflow_column_exists($pdo, 'documents', 'originating_entity_name');

    $pdo->beginTransaction();
    try {
        $insertColumns = [
            'tracking_id',
            'subject',
            'document_type_id',
            'originating_office_id',
            'current_office_id',
            'status',
            'source_type',
            'external_client_name',
            'client_address',
            'originating_entity_name',
            'created_by_user_id',
            'current_holder_user_id',
            'pending_office_id',
            'pending_user_id',
        ];
        $insertPlaceholders = [
            ':tracking_id',
            ':subject',
            ':document_type_id',
            ':originating_office_id',
            ':current_office_id',
            ':status',
            ':source_type',
            ':external_client_name',
            ':client_address',
            ':originating_entity_name',
            ':created_by_user_id',
            ':current_holder_user_id',
            ':pending_office_id',
            ':pending_user_id',
        ];
        $insertParams = [
            'tracking_id' => $trackingId,
            'subject' => $subject,
            'document_type_id' => $documentTypeId,
            'originating_office_id' => $originatingOfficeId,
            'current_office_id' => $originatingOfficeId,
            'status' => 'Created',
            'source_type' => $sourceType,
            'external_client_name' => $externalClientName !== '' ? $externalClientName : null,
            'client_address' => $clientAddress !== '' ? $clientAddress : null,
            'originating_entity_name' => $originatingEntityName !== '' ? $originatingEntityName : null,
            'created_by_user_id' => $actorUserId,
            'current_holder_user_id' => $actorUserId,
            'pending_office_id' => null,
            'pending_user_id' => null,
        ];

        if (!$hasClientAddressColumn) {
            $clientAddressColumnIndex = array_search('client_address', $insertColumns, true);
            if ($clientAddressColumnIndex !== false) {
                unset($insertColumns[$clientAddressColumnIndex], $insertPlaceholders[$clientAddressColumnIndex], $insertParams['client_address']);
                $insertColumns = array_values($insertColumns);
                $insertPlaceholders = array_values($insertPlaceholders);
            }
        }
        if (!$hasOriginatingEntityColumn) {
            $originatingEntityColumnIndex = array_search('originating_entity_name', $insertColumns, true);
            if ($originatingEntityColumnIndex !== false) {
                unset($insertColumns[$originatingEntityColumnIndex], $insertPlaceholders[$originatingEntityColumnIndex], $insertParams['originating_entity_name']);
                $insertColumns = array_values($insertColumns);
                $insertPlaceholders = array_values($insertPlaceholders);
            }
        }

        if ($hasSenderColumn) {
            $insertColumns[] = 'sender';
            $insertPlaceholders[] = ':sender';
            $insertParams['sender'] = $sender;
        }

        if ($hasArtaCategoryOverrideColumn) {
            $insertColumns[] = 'arta_category_override';
            $insertPlaceholders[] = ':arta_category_override';
            $insertParams['arta_category_override'] = $artaCategoryOverride !== '' ? $artaCategoryOverride : null;
        }

        if ($hasArtaDaysOverrideColumn) {
            $insertColumns[] = 'arta_days_limit_override';
            $insertPlaceholders[] = ':arta_days_limit_override';
            $insertParams['arta_days_limit_override'] = $artaDaysLimitOverride > 0 ? $artaDaysLimitOverride : null;
        }

        $insertSql = 'INSERT INTO documents (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertPlaceholders) . ')';
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute($insertParams);

        $documentId = (int)$pdo->lastInsertId();

        workflow_log_action($pdo, [
            'document_id' => $documentId,
            'user_id' => $actorUserId,
            'action_type' => 'Created',
            'action_scope' => 'ACTION',
            'remarks' => $remarks === '' ? 'Document intake created.' : $remarks,
            'is_visible_on_slip' => 0,
        ]);

        $pdo->commit();
        return [
            'document_id' => $documentId,
            'tracking_id' => $trackingId,
        ];
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function workflow_route_document_internal(
    PDO $pdo,
    int $documentId,
    int $actorUserId,
    int $destinationOfficeId,
    ?int $destinationUserId,
    string $remarks,
    string $transitionAction,
    string $documentStatus,
    string $logActionType
): void {
    if ($destinationOfficeId <= 0) {
        throw new InvalidArgumentException('Destination office is required.');
    }

    $transitionAction = strtoupper(trim($transitionAction));
    $finalRemarks = $remarks;
    $pdo->beginTransaction();
    try {
        $actor = workflow_get_user_context($pdo, $actorUserId);
        $document = workflow_get_document_context($pdo, $documentId, true);
        $currentOfficeId = (int)($document['current_office_id'] ?? 0);
        $currentPendingOfficeId = (int)($document['pending_office_id'] ?? 0);
        $currentHolderUserId = (int)($document['current_holder_user_id'] ?? 0);

        $sameOfficeUserHandoff = $currentOfficeId > 0
            && $currentOfficeId === $destinationOfficeId
            && $destinationUserId !== null
            && $destinationUserId > 0
            && $destinationUserId !== $currentHolderUserId;

        if (
            $currentPendingOfficeId > 0
            && $currentPendingOfficeId === $destinationOfficeId
            && (
                !$sameOfficeUserHandoff
                || (int)($document['pending_user_id'] ?? 0) === (int)$destinationUserId
            )
        ) {
            // Idempotent routing guard: destination already pending for receive.
            $pdo->commit();
            return;
        }
        if ($currentPendingOfficeId <= 0 && $currentOfficeId > 0 && $currentOfficeId === $destinationOfficeId && !$sameOfficeUserHandoff) {
            throw new RuntimeException('Destination office must be different from current office.');
        }

        workflow_assert_cenro_internal_assignment_scope(
            $pdo,
            $actor,
            $destinationOfficeId,
            $transitionAction,
            $document
        );
        workflow_assert_section_assignment_scope(
            $pdo,
            $actor,
            $destinationOfficeId,
            $destinationUserId,
            $transitionAction
        );
        workflow_assert_actor_can_route_document($document, $actor, $transitionAction);

        $toRoleId = workflow_resolve_destination_role_id($pdo, $destinationUserId, $destinationOfficeId);
        if ($transitionAction === 'REROUTE') {
            $finalRemarks = trim($remarks);
            workflow_assert_ored_reroute_policy($pdo, $actor, $toRoleId, $destinationOfficeId, $finalRemarks);
            workflow_assert_ard_reroute_policy($pdo, $actor, $destinationOfficeId, $finalRemarks);
            workflow_assert_division_reroute_policy($pdo, $actor, $destinationOfficeId, $finalRemarks);
            workflow_assert_cenro_reroute_policy($pdo, $actor, $destinationOfficeId, $finalRemarks);
            if ($finalRemarks === '') {
                $finalRemarks = 'Ad-hoc reroute by supervisor.';
            }
        }
        workflow_assert_transition_allowed(
            $pdo,
            $transitionAction,
            (int)($actor['role_id'] ?? 0),
            $toRoleId
        );

        $update = $pdo->prepare(
            'UPDATE documents
             SET status = :status,
                 pending_office_id = :pending_office_id,
                 pending_user_id = :pending_user_id
             WHERE id = :id'
        );
        $update->execute([
            'status' => $documentStatus,
            'pending_office_id' => $destinationOfficeId,
            'pending_user_id' => $destinationUserId !== null && $destinationUserId > 0 ? $destinationUserId : null,
            'id' => $documentId,
        ]);

        workflow_log_action($pdo, [
            'document_id' => $documentId,
            'user_id' => $actorUserId,
            'action_type' => $logActionType,
            'action_scope' => 'ACTION',
            'destination_office_id' => $destinationOfficeId,
            'destination_user_id' => $destinationUserId !== null && $destinationUserId > 0 ? $destinationUserId : null,
            'remarks' => $finalRemarks,
            'is_visible_on_slip' => 0,
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function workflow_forward_document(
    PDO $pdo,
    int $documentId,
    int $actorUserId,
    int $destinationOfficeId,
    ?int $destinationUserId = null,
    string $remarks = '',
    string $bypassReason = '',
    bool $hasRouteAttachment = false,
    string $routeMode = ''
): void
{
    $documentStatus = 'Forwarded';
    $finalRemarks = $remarks === '' ? 'Forwarded to next office.' : $remarks;

    $actor = workflow_get_user_context($pdo, $actorUserId);
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    $actorIsRegionalOred = workflow_is_regional_ored_role_key($actorRoleKey);
    $actorIsOredSigner = $actorIsRegionalOred && workflow_actor_is_ored_signer($pdo, $actor);
    $actorIsOredReviewer = $actorRoleKey === 'ORED' && !$actorIsOredSigner;
    $documentSnapshot = workflow_get_document_context($pdo, $documentId, false);
    $regionalOredSignedStage = false;
    if ($actorIsRegionalOred) {
        $regionalOredSignedStage = workflow_document_has_local_sign_stage(
            $pdo,
            $documentSnapshot,
            (int)($actor['office_id'] ?? 0),
            'ORED_SIGN'
        );
    }
    $normalizedRouteMode = strtolower(trim($routeMode));
    if ($normalizedRouteMode === 'admin_record_back') {
        $resolvedAdminDestinationOfficeId = workflow_resolve_officer_admin_record_back_destination(
            $pdo,
            $actorRoleKey,
            $actor,
            $documentSnapshot
        );
        if ($resolvedAdminDestinationOfficeId > 0) {
            $destinationOfficeId = $resolvedAdminDestinationOfficeId;
            $destinationUserId = null;
        }
    } elseif ($normalizedRouteMode === 'records_unit_back') {
        $recordsUnitOffice = workflow_find_records_unit_office($pdo);
        $resolvedRecordsUnitOfficeId = (int)($recordsUnitOffice['id'] ?? 0);
        if ($resolvedRecordsUnitOfficeId > 0) {
            $destinationOfficeId = $resolvedRecordsUnitOfficeId;
            $destinationUserId = null;
        }
    }

    $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
    $destinationIsRegionalOred = is_array($destinationOffice) && workflow_office_is_ored($destinationOffice);
    if ($actorRoleKey === 'RECORDS_UNIT' && $destinationIsRegionalOred && ($destinationUserId === null || $destinationUserId <= 0)) {
        $destinationUserId = workflow_ored_resolve_reviewer_user_id($pdo, $destinationOfficeId);
    }
    if (
        in_array($actorRoleKey, ['ARD_TS', 'ARD_MS', 'DIVISION_CHIEF'], true)
        && $destinationIsRegionalOred
        && ($destinationUserId === null || $destinationUserId <= 0)
    ) {
        $destinationUserId = workflow_ored_resolve_signer_user_id($pdo, $destinationOfficeId);
    }
    if ($actorIsOredReviewer && !$regionalOredSignedStage) {
        $destinationOfficeId = (int)($actor['office_id'] ?? 0);
        $destinationOffice = $destinationOfficeId > 0 ? workflow_get_office_context($pdo, $destinationOfficeId) : $destinationOffice;
        $destinationUserId = workflow_ored_resolve_signer_user_id(
            $pdo,
            $destinationOfficeId,
            (int)($actor['id'] ?? 0) > 0 ? (int)$actor['id'] : null
        );
        if ($destinationOfficeId <= 0 || $destinationUserId === null || $destinationUserId <= 0) {
            throw new RuntimeException('No ORED signatory account is configured for this office yet.');
        }
    } elseif ($actorIsOredSigner && $regionalOredSignedStage) {
        $destinationOfficeId = (int)($actor['office_id'] ?? 0);
        $destinationOffice = $destinationOfficeId > 0 ? workflow_get_office_context($pdo, $destinationOfficeId) : $destinationOffice;
        $destinationUserId = workflow_ored_resolve_reviewer_user_id(
            $pdo,
            $destinationOfficeId,
            (int)($actor['id'] ?? 0) > 0 ? (int)$actor['id'] : null
        );
        if ($destinationOfficeId <= 0 || $destinationUserId === null || $destinationUserId <= 0) {
            throw new RuntimeException('No ORED review account is configured for this office yet.');
        }
    }

    $currentStatus = strtolower(trim((string)($documentSnapshot['status'] ?? '')));
    $canForwardOredToPacdoCorrectionWithoutApproval = false;
    if ($actorIsRegionalOred) {
        $oredActorOfficeId = (int)($actor['office_id'] ?? 0);
        $documentCurrentOfficeId = (int)($documentSnapshot['current_office_id'] ?? 0);
        $documentPendingOfficeId = (int)($documentSnapshot['pending_office_id'] ?? 0);
        $isOredCustodyAfterReceive = $oredActorOfficeId > 0
            && $documentCurrentOfficeId === $oredActorOfficeId
            && $documentPendingOfficeId <= 0
            && (
                str_contains($currentStatus, 'received')
                || str_contains($currentStatus, 'recieved')
                || str_contains($currentStatus, 'return')
            );
        if ($isOredCustodyAfterReceive) {
            $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
            $destinationName = strtoupper(trim((string)($destinationOffice['name'] ?? '')));
            $isPacdoDestination = workflow_name_matches_records_unit($destinationName);
            if ($isPacdoDestination) {
                $cycleStmt = $pdo->prepare(
                    'SELECT
                        MAX(CASE WHEN LOWER(COALESCE(action_type, \'\')) = \'signed\' THEN 1 ELSE 0 END) AS has_signed,
                        MAX(CASE WHEN LOWER(COALESCE(action_type, \'\')) IN (\'return\', \'returned\') THEN 1 ELSE 0 END) AS has_return
                     FROM activity_logs
                     WHERE document_id = :document_id'
                );
                $cycleStmt->execute(['document_id' => $documentId]);
                $cycleFlags = $cycleStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $hasSigned = (int)($cycleFlags['has_signed'] ?? 0) > 0;
                $hasReturn = (int)($cycleFlags['has_return'] ?? 0) > 0;
                $canForwardOredToPacdoCorrectionWithoutApproval = $hasSigned && $hasReturn;
            }
        }
    }
    $canForwardCreatedIntakeWithoutApproval = in_array($actorRoleKey, ['RECORDS_UNIT', 'CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD', 'PAMO_ADMIN'], true)
        && (int)($documentSnapshot['created_by_user_id'] ?? 0) === $actorUserId
        && str_starts_with($currentStatus, 'created')
        && (int)($documentSnapshot['pending_office_id'] ?? 0) <= 0;
    $canForwardReturnedCorrectionWithoutApproval = false;
    if (in_array($actorRoleKey, ['RECORDS_UNIT', 'CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD', 'PAMO_ADMIN'], true)) {
        $actorOfficeId = (int)($actor['office_id'] ?? 0);
        $documentCurrentOfficeId = (int)($documentSnapshot['current_office_id'] ?? 0);
        $documentPendingOfficeId = (int)($documentSnapshot['pending_office_id'] ?? 0);
        $isActorCustodyReceived = $actorOfficeId > 0
            && $documentCurrentOfficeId === $actorOfficeId
            && $documentPendingOfficeId <= 0
            && (
                str_contains($currentStatus, 'received')
                || str_contains($currentStatus, 'recieved')
                || str_contains($currentStatus, 'return')
            );
        if ($isActorCustodyReceived) {
            $returnActionStmt = $pdo->prepare(
                'SELECT TOP (1) 1
                 FROM activity_logs
                 WHERE document_id = :document_id
                   AND LOWER(COALESCE(action_type, \'\')) IN (\'return\', \'returned\')'
            );
            $returnActionStmt->execute(['document_id' => $documentId]);
            $canForwardReturnedCorrectionWithoutApproval = (bool)$returnActionStmt->fetchColumn();
        }
    }

    $isPostSignStage = workflow_document_has_local_sign_stage(
        $pdo,
        $documentSnapshot,
        (int)($actor['office_id'] ?? 0),
        $actorRoleKey
    ) || ($actorIsRegionalOred && $regionalOredSignedStage)
      || ($actorIsOredSigner && $canForwardOredToPacdoCorrectionWithoutApproval);
    if ($actorRoleKey === 'CENRO_ADMIN_RECORD') {
        $originatingOfficeId = (int)($documentSnapshot['originating_office_id'] ?? 0);
        if ($originatingOfficeId <= 0) {
            $originatingOfficeId = (int)($actor['office_id'] ?? 0);
        }
        $requiredDestination = workflow_required_initial_destination_office($pdo, $actor, $originatingOfficeId);
        if ($requiredDestination !== null) {
            $destinationRoleId = workflow_resolve_destination_role_id($pdo, $destinationUserId, $destinationOfficeId);
            $destinationRoleKey = '';
            if ($destinationRoleId !== null) {
                $destinationRoleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
                $destinationRoleStmt->execute(['id' => $destinationRoleId]);
                $destinationRoleKey = app_normalize_role_key((string)($destinationRoleStmt->fetchColumn() ?: ''));
            }
            $requiredOfficeId = (int)($requiredDestination['office_id'] ?? 0);
            $isInternalCenroOfficerRoute = false;
            if ($destinationRoleKey === 'CENRO_OFFICER') {
                $actorRoot = workflow_find_cenro_root_office($pdo, (int)($actor['office_id'] ?? 0));
                $destinationRoot = workflow_find_cenro_root_office($pdo, $destinationOfficeId);
                $actorRootId = (int)($actorRoot['id'] ?? 0);
                $destinationRootId = (int)($destinationRoot['id'] ?? 0);
                $isInternalCenroOfficerRoute = $actorRootId > 0
                    && $destinationRootId > 0
                    && $actorRootId === $destinationRootId;
            }
            if ($requiredOfficeId > 0 && $destinationOfficeId !== $requiredOfficeId && !$isInternalCenroOfficerRoute) {
                $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
                $destinationOfficeName = strtoupper(trim((string)($destinationOffice['name'] ?? '')));
                $isRecordsUnitBypass = workflow_name_matches_records_unit($destinationOfficeName);
                if (!$isRecordsUnitBypass) {
                    throw new RuntimeException('Routing policy violation: ' . (string)($requiredDestination['policy_label'] ?? 'CENRO Admin Record must route to PENRO Admin Record first. Direct to RECORDS-UNIT is allowed only as bypass.'));
                }
            }
        }
    }

    if ($actorRoleKey === 'PENRO_ADMIN_RECORD') {
        $penroPendingOfficeId = (int)($documentSnapshot['pending_office_id'] ?? 0);
        $penroActorOfficeId = (int)($actor['office_id'] ?? 0);

        if ($penroPendingOfficeId > 0 && $penroActorOfficeId > 0 && $penroPendingOfficeId !== $penroActorOfficeId) {
            throw new RuntimeException('Document is already forwarded and waiting for the destination office to receive it.');
        }

        $penroDestinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        $penroDestinationName = strtoupper(trim((string)($penroDestinationOffice['name'] ?? '')));
        // Endorsement attachment is optional for this route.
    }

    if ($actorRoleKey === 'SECTION_STAFF') {
        $actorOfficeId = (int)($actor['office_id'] ?? 0);
        $requiredDivision = workflow_find_parent_office_by_level($pdo, $actorOfficeId, 'DIVISION');
        if (!$requiredDivision) {
            throw new RuntimeException('Unable to resolve parent Division destination for this section.');
        }
        $requiredDivisionOfficeId = (int)($requiredDivision['id'] ?? 0);
        if ($requiredDivisionOfficeId > 0 && $destinationOfficeId !== $requiredDivisionOfficeId) {
            throw new RuntimeException('Section Staff can forward only to their originating Division.');
        }
    }

    if ($actorRoleKey === 'CENRO_UNIT') {
        $actorOfficeId = (int)($actor['office_id'] ?? 0);
        $requiredSection = workflow_find_parent_office_by_level($pdo, $actorOfficeId, 'CENRO_SECTION');
        if (!$requiredSection) {
            throw new RuntimeException('Unable to resolve parent CENRO Section destination for this unit.');
        }
        $requiredSectionOfficeId = (int)($requiredSection['id'] ?? 0);
        if ($requiredSectionOfficeId > 0 && $destinationOfficeId !== $requiredSectionOfficeId) {
            throw new RuntimeException('CENRO Unit can forward only to its parent CENRO Section.');
        }
    }

    if ($actorRoleKey === 'PAMO_UNIT') {
        $actorOfficeId = (int)($actor['office_id'] ?? 0);
        $requiredOfficer = workflow_find_parent_office_by_level($pdo, $actorOfficeId, 'PASU_OFFICER');
        if (!$requiredOfficer) {
            throw new RuntimeException('Unable to resolve parent PASU destination for this unit.');
        }
        $requiredOfficerOfficeId = (int)($requiredOfficer['id'] ?? 0);
        if ($requiredOfficerOfficeId > 0 && $destinationOfficeId !== $requiredOfficerOfficeId) {
            throw new RuntimeException('PAMO Unit can forward only to its parent PASU.');
        }
    }

    if (in_array($actorRoleKey, ['PENRO_SECTION', 'PENRO_SECTION_UNIT'], true)) {
        $actorOfficeId = (int)($actor['office_id'] ?? 0);
        $actorOffice = workflow_get_office_context($pdo, $actorOfficeId);
        $actorMode = $actorRoleKey === 'PENRO_SECTION'
            ? 'section'
            : workflow_penro_internal_section_actor_mode($actorOffice);
        if ($actorMode === 'unit') {
            $requiredSection = workflow_find_parent_office_by_level($pdo, $actorOfficeId, 'PENRO_SECTION');
            if (!$requiredSection) {
                throw new RuntimeException('Unable to resolve parent PENRO Section destination for this unit.');
            }
            $requiredSectionOfficeId = (int)($requiredSection['id'] ?? 0);
            if ($requiredSectionOfficeId > 0 && $destinationOfficeId !== $requiredSectionOfficeId) {
                throw new RuntimeException('PENRO unit can forward only to its parent PENRO Section.');
            }
        } else {
            if (!$actorOffice) {
                throw new RuntimeException('Unable to resolve current PENRO Section office.');
            }

            $parentDestination = workflow_penro_internal_resolve_section_parent_destination($pdo, $actorOffice);
            $parentDestinationId = (int)($parentDestination['id'] ?? 0);
            $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
            if (!$destinationOffice) {
                throw new InvalidArgumentException('Destination office is not valid.');
            }
            $isChildUnit = workflow_penro_internal_section_allows_destination($pdo, $actorOffice, $destinationOffice);
            $isParentDestination = $parentDestinationId > 0 && $destinationOfficeId === $parentDestinationId;

            if (workflow_penro_internal_is_admin_finance_section_office($actorOffice)) {
                if (!$isChildUnit && !$isParentDestination) {
                    throw new RuntimeException('Admin & Finance Section can forward only to its child unit offices or back to its parent PENRO Division.');
                }
            } elseif (workflow_penro_internal_is_admin_finance_unit_office($actorOffice)) {
                if (!$isParentDestination) {
                    throw new RuntimeException('Admin & Finance unit can forward only back to Admin & Finance Section.');
                }
            } elseif (!$isParentDestination) {
                throw new RuntimeException('PENRO Section can forward only to its parent PENRO Division.');
            }
        }
    }

    if ($actorRoleKey === 'CENRO_SECTION') {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        $destinationRoleId = workflow_resolve_destination_role_id($pdo, $destinationUserId, $destinationOfficeId);
        $destinationRoleKey = '';
        if ($destinationRoleId !== null) {
            $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
            $roleStmt->execute(['id' => $destinationRoleId]);
            $destinationRoleKey = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
        }

        $actorOffice = workflow_get_office_context($pdo, (int)($actor['office_id'] ?? 0));
        $isUnitChild = $destinationRoleKey === 'CENRO_UNIT'
            && is_array($actorOffice)
            && workflow_cenro_internal_section_allows_unit_destination($actorOffice, $destinationOffice);
        $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
        $isAdminRecordTarget = $destinationRoleKey === 'CENRO_ADMIN_RECORD'
            && workflow_is_cenro_admin_record_level((string)($destinationOffice['level'] ?? ''))
            && $actorParentOfficeId > 0
            && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorParentOfficeId;
        $isOfficerParent = $destinationRoleKey === 'CENRO_OFFICER'
            && workflow_is_cenro_officer_level((string)($destinationOffice['level'] ?? ''))
            && $actorParentOfficeId > 0
            && $destinationOfficeId === $actorParentOfficeId;

        if (!$isUnitChild && !$isAdminRecordTarget && !$isOfficerParent) {
            throw new RuntimeException('CENRO Section can forward only to child CENRO Unit, back to CENRO Admin Record, or send back to parent CENRO Officer.');
        }
        if ($isOfficerParent) {
            if ($finalRemarks === '') {
                $finalRemarks = 'CENRO Section instruction: sent back to CENRO Officer.';
            }
            $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'CENRO Officer'));
        }
    }

    if ($actorRoleKey === 'PENRO_DIVISION') {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        $destinationRoleId = workflow_resolve_destination_role_id($pdo, $destinationUserId, $destinationOfficeId);
        $destinationRoleKey = '';
        if ($destinationRoleId !== null) {
            $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
            $roleStmt->execute(['id' => $destinationRoleId]);
            $destinationRoleKey = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
        }

        $actorOffice = workflow_get_office_context($pdo, (int)($actor['office_id'] ?? 0));
        $isSectionChild = $destinationRoleKey === 'PENRO_SECTION'
            && is_array($actorOffice)
            && workflow_penro_internal_division_allows_destination($actorOffice, $destinationOffice);

        $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
        $isAdminRecordTarget = $destinationRoleKey === 'PENRO_ADMIN_RECORD'
            && workflow_is_cenro_admin_record_level((string)($destinationOffice['level'] ?? ''))
            && $actorParentOfficeId > 0
            && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorParentOfficeId;
        $isOfficerParent = $destinationRoleKey === 'PENRO_OFFICER'
            && workflow_is_cenro_officer_level((string)($destinationOffice['level'] ?? ''))
            && $actorParentOfficeId > 0
            && $destinationOfficeId === $actorParentOfficeId;

        if (!$isSectionChild && !$isAdminRecordTarget && !$isOfficerParent) {
            throw new RuntimeException('PENRO Division can forward only to child PENRO Section, back to PENRO Admin Record, or send back to parent PENRO Officer.');
        }
        if ($isOfficerParent) {
            if ($finalRemarks === '') {
                $finalRemarks = 'PENRO Division instruction: sent back to PENRO Officer.';
            }
            $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'PENRO Officer'));
        }
    }

    if ($actorRoleKey === 'CENRO_OFFICER') {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        $destinationRoleId = workflow_resolve_destination_role_id($pdo, $destinationUserId, $destinationOfficeId);
        $destinationRoleKey = '';
        if ($destinationRoleId !== null) {
            $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
            $roleStmt->execute(['id' => $destinationRoleId]);
            $destinationRoleKey = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
        }
        $originatingOfficeId = (int)($documentSnapshot['originating_office_id'] ?? 0);
        $actorOfficeId = (int)($actor['office_id'] ?? 0);
        $actorOffice = workflow_get_office_context($pdo, $actorOfficeId);
        $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
        $actorRoot = workflow_find_cenro_root_office($pdo, $actorOfficeId);
        $destinationRoot = workflow_find_cenro_root_office($pdo, $destinationOfficeId);
        $actorRootId = (int)($actorRoot['id'] ?? 0);
        $destinationRootId = (int)($destinationRoot['id'] ?? 0);
        $isSameCenroRoot = $actorRootId > 0 && $destinationRootId > 0 && $actorRootId === $destinationRootId;
        $destinationLevelKey = workflow_office_level_key((string)($destinationOffice['level'] ?? ''));
        if ($isPostSignStage) {
            $isOriginAdminRecord = ($destinationRoleKey === 'CENRO_ADMIN_RECORD' || $destinationLevelKey === 'CENRO_ADMIN_RECORD')
                && workflow_is_cenro_admin_record_level($destinationLevelKey)
                && (
                    ($originatingOfficeId > 0 && $destinationOfficeId === $originatingOfficeId)
                    || ($actorParentOfficeId > 0 && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorParentOfficeId)
                    || $isSameCenroRoot
                );
            if (!$isOriginAdminRecord) {
                throw new RuntimeException('After signing, CENRO Officer can forward only back to the originating CENRO Admin Record office.');
            }
            if ($finalRemarks === '') {
                $finalRemarks = 'CENRO Officer instruction: returned to originating CENRO Admin Record for final processing.';
            }
            $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'CENRO Admin Record'));
        } else {
            $isSectionTarget = ($destinationRoleKey === 'CENRO_SECTION' || $destinationLevelKey === 'CENRO_SECTION')
                && workflow_is_cenro_section_level($destinationLevelKey)
                && (int)($destinationOffice['parent_office_id'] ?? 0) === (int)($actor['office_id'] ?? 0);
            $isUnitBypassTarget = ($destinationRoleKey === 'CENRO_UNIT' || $destinationLevelKey === 'CENRO_UNIT')
                && workflow_is_cenro_unit_level($destinationLevelKey)
                && workflow_cenro_internal_is_valid_unit_name((string)($destinationOffice['name'] ?? ''))
                && $isSameCenroRoot;
            $isAdminRecordTarget = ($destinationRoleKey === 'CENRO_ADMIN_RECORD' || $destinationLevelKey === 'CENRO_ADMIN_RECORD')
                && workflow_is_cenro_admin_record_level($destinationLevelKey)
                && (
                    ($originatingOfficeId > 0 && $destinationOfficeId === $originatingOfficeId)
                    || ($actorParentOfficeId > 0 && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorParentOfficeId)
                    || $isSameCenroRoot
                );
            if (!$isSectionTarget && !$isUnitBypassTarget && !$isAdminRecordTarget) {
                throw new RuntimeException('CENRO Officer can forward only to child CENRO Section, bypass to same-chain CENRO Unit, or send back to CENRO Admin Record.');
            }

            if ($isAdminRecordTarget) {
                if ($finalRemarks === '') {
                    $finalRemarks = 'CENRO Officer instruction: sent back to CENRO Admin Record.';
                }
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'CENRO Admin Record'));
            } elseif ($isUnitBypassTarget) {
                $normalizedBypassReason = trim($bypassReason);
                if ($normalizedBypassReason === '') {
                    throw new RuntimeException('Bypass reason is required when CENRO Officer forwards directly to CENRO Unit.');
                }
                $bypassPrefix = 'Bypass reason: ' . $normalizedBypassReason;
                if ($remarks === '') {
                    $finalRemarks = $bypassPrefix;
                } elseif (stripos($remarks, 'bypass reason:') === false) {
                    $finalRemarks = trim($remarks) . ' | ' . $bypassPrefix;
                } else {
                    $finalRemarks = $remarks;
                }
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'CENRO Unit'));
            } else {
                if ($finalRemarks === '') {
                    $finalRemarks = 'CENRO Officer instruction: assigned to concerned CENRO section.';
                }
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'CENRO Section'));
            }
        }
    }

    if ($actorRoleKey === 'PENRO_OFFICER') {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        $destinationRoleId = workflow_resolve_destination_role_id($pdo, $destinationUserId, $destinationOfficeId);
        $destinationRoleKey = '';
        if ($destinationRoleId !== null) {
            $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
            $roleStmt->execute(['id' => $destinationRoleId]);
            $destinationRoleKey = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
        }
        $originatingOfficeId = (int)($documentSnapshot['originating_office_id'] ?? 0);
        $actorOfficeId = (int)($actor['office_id'] ?? 0);
        $actorOffice = workflow_get_office_context($pdo, $actorOfficeId);
        $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
        $actorRoot = workflow_find_penro_root_office($pdo, $actorOfficeId);
        $destinationRoot = workflow_find_penro_root_office($pdo, $destinationOfficeId);
        $actorRootId = (int)($actorRoot['id'] ?? 0);
        $destinationRootId = (int)($destinationRoot['id'] ?? 0);
        $isSamePenroRoot = $actorRootId > 0 && $destinationRootId > 0 && $actorRootId === $destinationRootId;
        $destinationLevelKey = workflow_office_level_key((string)($destinationOffice['level'] ?? ''));
        if ($isPostSignStage) {
            $isOriginAdminRecord = ($destinationRoleKey === 'PENRO_ADMIN_RECORD' || $destinationLevelKey === 'PENRO_ADMIN_RECORD')
                && workflow_is_cenro_admin_record_level($destinationLevelKey)
                && (
                    ($originatingOfficeId > 0 && $destinationOfficeId === $originatingOfficeId)
                    || ($actorParentOfficeId > 0 && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorParentOfficeId)
                    || $isSamePenroRoot
                );
            if (!$isOriginAdminRecord) {
                throw new RuntimeException('After signing, PENRO Officer can forward only back to the originating PENRO Admin Record office.');
            }
            if ($finalRemarks === '') {
                $finalRemarks = 'PENRO Officer instruction: returned to originating PENRO Admin Record for final processing.';
            }
            $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'PENRO Admin Record'));
        } else {
            $isDivisionTarget = ($destinationRoleKey === 'PENRO_DIVISION' || $destinationLevelKey === 'PENRO_DIVISION')
                && workflow_is_cenro_section_level($destinationLevelKey)
                && (int)($destinationOffice['parent_office_id'] ?? 0) === (int)($actor['office_id'] ?? 0);
            $isSectionBypassTarget = ($destinationRoleKey === 'PENRO_SECTION' || $destinationLevelKey === 'PENRO_SECTION' || $destinationLevelKey === 'PENRO_UNIT')
                && workflow_is_cenro_unit_level($destinationLevelKey)
                && $isSamePenroRoot;
            $isAdminRecordTarget = ($destinationRoleKey === 'PENRO_ADMIN_RECORD' || $destinationLevelKey === 'PENRO_ADMIN_RECORD')
                && workflow_is_cenro_admin_record_level($destinationLevelKey)
                && (
                    ($originatingOfficeId > 0 && $destinationOfficeId === $originatingOfficeId)
                    || ($actorParentOfficeId > 0 && (int)($destinationOffice['parent_office_id'] ?? 0) === $actorParentOfficeId)
                    || $isSamePenroRoot
                );
            if (!$isDivisionTarget && !$isSectionBypassTarget && !$isAdminRecordTarget) {
                throw new RuntimeException('PENRO Officer can forward only to child PENRO Division, bypass to same-chain PENRO Section, or send back to PENRO Admin Record.');
            }

            if ($isAdminRecordTarget) {
                if ($finalRemarks === '') {
                    $finalRemarks = 'PENRO Officer instruction: sent back to PENRO Admin Record.';
                }
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'PENRO Admin Record'));
            } elseif ($isSectionBypassTarget) {
                $normalizedBypassReason = trim($bypassReason);
                if ($normalizedBypassReason === '') {
                    throw new RuntimeException('Bypass reason is required when PENRO Officer forwards directly to PENRO Section.');
                }
                $bypassPrefix = 'Bypass reason: ' . $normalizedBypassReason;
                if ($remarks === '') {
                    $finalRemarks = $bypassPrefix;
                } elseif (stripos($remarks, 'bypass reason:') === false) {
                    $finalRemarks = trim($remarks) . ' | ' . $bypassPrefix;
                } else {
                    $finalRemarks = $remarks;
                }
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'PENRO Section'));
            } else {
                if ($finalRemarks === '') {
                    $finalRemarks = 'PENRO Officer instruction: assigned to concerned PENRO division.';
                }
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'PENRO Division'));
            }
        }
    }

    $pasuActorOffice = workflow_get_office_context($pdo, (int)($actor['office_id'] ?? 0));
    if ($actorRoleKey === 'PASU_OFFICER' && workflow_office_level_key((string)($pasuActorOffice['level'] ?? '')) === 'PASU_OFFICER') {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        $destinationRoleId = workflow_resolve_destination_role_id($pdo, $destinationUserId, $destinationOfficeId);
        $destinationRoleKey = '';
        if ($destinationRoleId !== null) {
            $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
            $roleStmt->execute(['id' => $destinationRoleId]);
            $destinationRoleKey = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
        }
        $originatingOfficeId = (int)($documentSnapshot['originating_office_id'] ?? 0);
        $actorOfficeId = (int)($actor['office_id'] ?? 0);
        $actorOffice = $pasuActorOffice ?: workflow_get_office_context($pdo, $actorOfficeId);
        $actorRoot = workflow_find_pamo_root_office($pdo, $actorOfficeId);
        $destinationRoot = workflow_find_pamo_root_office($pdo, $destinationOfficeId);
        $actorRootId = (int)($actorRoot['id'] ?? 0);
        $destinationRootId = (int)($destinationRoot['id'] ?? 0);
        $isSamePamoRoot = $actorRootId > 0 && $destinationRootId > 0 && $actorRootId === $destinationRootId;
        $destinationLevelKey = workflow_office_level_key((string)($destinationOffice['level'] ?? ''));
        if ($isPostSignStage) {
            $isOriginAdmin = ($destinationRoleKey === 'PAMO_ADMIN' || $destinationLevelKey === 'PAMO_ADMIN')
                && $destinationLevelKey === 'PAMO_ADMIN'
                && (
                    ($originatingOfficeId > 0 && $destinationOfficeId === $originatingOfficeId)
                    || $isSamePamoRoot
                );
            if (!$isOriginAdmin) {
                throw new RuntimeException('After signing, PASU can forward only back to the originating PAMO Admin office.');
            }
            if ($finalRemarks === '') {
                $finalRemarks = 'PASU instruction: returned to originating PAMO Admin for final processing.';
            }
            $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'PAMO Admin'));
        } else {
            $isUnitTarget = ($destinationRoleKey === 'PAMO_UNIT' || $destinationLevelKey === 'PAMO_UNIT')
                && $destinationLevelKey === 'PAMO_UNIT'
                && (int)($destinationOffice['parent_office_id'] ?? 0) === (int)($actorOffice['id'] ?? 0);
            $isAdminTarget = ($destinationRoleKey === 'PAMO_ADMIN' || $destinationLevelKey === 'PAMO_ADMIN')
                && $destinationLevelKey === 'PAMO_ADMIN'
                && $isSamePamoRoot;
            if (!$isUnitTarget && !$isAdminTarget) {
                throw new RuntimeException('PASU can forward only to child PAMO Unit or back to PAMO Admin.');
            }
            if ($isAdminTarget) {
                if ($finalRemarks === '') {
                    $finalRemarks = 'PASU instruction: returned to PAMO Admin.';
                }
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'PAMO Admin'));
            } else {
                if ($finalRemarks === '') {
                    $finalRemarks = 'PASU instruction: assigned to concerned PAMO unit.';
                }
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'PAMO Unit'));
            }
        }
    }

    if ($actorRoleKey === 'PAMO_ADMIN') {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        $destinationRoleId = workflow_resolve_destination_role_id($pdo, $destinationUserId, $destinationOfficeId);
        $destinationRoleKey = '';
        if ($destinationRoleId !== null) {
            $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
            $roleStmt->execute(['id' => $destinationRoleId]);
            $destinationRoleKey = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
        }

        if ($destinationRoleKey === 'PASU_OFFICER') {
            $actorRoot = workflow_find_pamo_root_office($pdo, (int)($actor['office_id'] ?? 0));
            $destinationRoot = workflow_find_pamo_root_office($pdo, $destinationOfficeId);
            $actorRootId = (int)($actorRoot['id'] ?? 0);
            $destinationRootId = (int)($destinationRoot['id'] ?? 0);
            if ($actorRootId <= 0 || $destinationRootId <= 0 || $actorRootId !== $destinationRootId) {
                throw new RuntimeException('PAMO Admin can forward only to PASU under the same PAMO office.');
            }
            if ($finalRemarks === '') {
                $finalRemarks = 'PAMO Admin instruction: forwarded to PASU.';
            }
            $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'PASU'));
        } elseif ($destinationRoleKey === 'CENRO_ADMIN_RECORD') {
            if ($finalRemarks === '') {
                $finalRemarks = 'PAMO Admin endorsement: forwarded to CENRO Admin Record.';
            }
        } elseif ($destinationRoleKey === 'PENRO_ADMIN_RECORD') {
            if ($finalRemarks === '') {
                $finalRemarks = 'PAMO Admin endorsement: forwarded to PENRO Admin Record.';
            }
        } else {
            throw new RuntimeException('PAMO Admin can forward only to PASU, CENRO Admin Record, or PENRO Admin Record.');
        }
    }

    if ($actorRoleKey === 'CENRO_ADMIN_RECORD') {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        $destinationRoleId = workflow_resolve_destination_role_id($pdo, $destinationUserId, $destinationOfficeId);
        $destinationRoleKey = '';
        if ($destinationRoleId !== null) {
            $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
            $roleStmt->execute(['id' => $destinationRoleId]);
            $destinationRoleKey = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
        }

        if ($destinationRoleKey === 'CENRO_OFFICER') {
            $actorRoot = workflow_find_cenro_root_office($pdo, (int)($actor['office_id'] ?? 0));
            $destinationRoot = workflow_find_cenro_root_office($pdo, $destinationOfficeId);
            $actorRootId = (int)($actorRoot['id'] ?? 0);
            $destinationRootId = (int)($destinationRoot['id'] ?? 0);
            if ($actorRootId <= 0 || $destinationRootId <= 0 || $actorRootId !== $destinationRootId) {
                throw new RuntimeException('CENRO Admin Record can forward only to CENRO Officer under the same CENRO office.');
            }
            if ($finalRemarks === '') {
                $finalRemarks = 'CENRO Admin Record instruction: forwarded to CENRO Officer.';
            }
            $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'CENRO Officer'));
        } elseif ($destinationRoleKey === 'PENRO_ADMIN_RECORD') {
            if ($finalRemarks === '') {
                $finalRemarks = 'CENRO Admin Record endorsement: forwarded to PENRO Admin Record.';
            }
        } elseif ($destinationRoleKey === 'RECORDS_UNIT') {
            if ($finalRemarks === '') {
                $finalRemarks = 'CENRO Admin Record endorsement: forwarded directly to PACDO/RECORDS-UNIT.';
            }
        } else {
            throw new RuntimeException('CENRO Admin Record can forward only to CENRO Officer, PENRO Admin Record, or PACDO/RECORDS-UNIT.');
        }
    }

    if ($actorRoleKey === 'PENRO_ADMIN_RECORD') {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        $destinationRoleId = workflow_resolve_destination_role_id($pdo, $destinationUserId, $destinationOfficeId);
        $destinationRoleKey = '';
        if ($destinationRoleId !== null) {
            $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
            $roleStmt->execute(['id' => $destinationRoleId]);
            $destinationRoleKey = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
        }

        if ($destinationRoleKey === 'PENRO_OFFICER') {
            $actorRoot = workflow_find_penro_root_office($pdo, (int)($actor['office_id'] ?? 0));
            $destinationRoot = workflow_find_penro_root_office($pdo, $destinationOfficeId);
            $actorRootId = (int)($actorRoot['id'] ?? 0);
            $destinationRootId = (int)($destinationRoot['id'] ?? 0);
            if ($actorRootId <= 0 || $destinationRootId <= 0 || $actorRootId !== $destinationRootId) {
                throw new RuntimeException('PENRO Admin Record can forward only to PENRO Officer under the same PENRO office.');
            }
            if ($finalRemarks === '') {
                $finalRemarks = 'PENRO Admin Record instruction: forwarded to PENRO Officer.';
            }
            $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'PENRO Officer'));
        } elseif ($destinationRoleKey === 'CENRO_ADMIN_RECORD') {
            $actorRoot = workflow_find_penro_root_office($pdo, (int)($actor['office_id'] ?? 0));
            $actorRootId = (int)($actorRoot['id'] ?? 0);
            if ($actorRootId <= 0 || !workflow_cenro_belongs_to_penro($pdo, $actorRootId, $destinationOfficeId)) {
                throw new RuntimeException('PENRO Admin Record can forward only to CENRO Admin Record offices under the same PENRO scope.');
            }
            if ($finalRemarks === '') {
                $finalRemarks = 'PENRO Admin Record endorsement: forwarded to CENRO Admin Record.';
            }
        } elseif ($destinationRoleKey === 'RECORDS_UNIT') {
            if ($finalRemarks === '') {
                $finalRemarks = 'PENRO Admin Record endorsement: forwarded directly to PACDO/RECORDS-UNIT.';
            }
        } else {
            throw new RuntimeException('PENRO Admin Record can forward only to PENRO Officer, CENRO Admin Record, or PACDO/RECORDS-UNIT.');
        }
    }

    if ($actorRoleKey === 'DIVISION_CHIEF') {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }

        $destinationLevel = strtoupper(trim((string)($destinationOffice['level'] ?? '')));
        if ($destinationLevel !== 'SECTION') {
            $divisionOfficeId = (int)($actor['office_id'] ?? 0);
            $expectedArdOffice = workflow_find_ard_office_for_division($pdo, $divisionOfficeId);
            if ($expectedArdOffice !== null) {
                $expectedArdOfficeId = (int)($expectedArdOffice['id'] ?? 0);
                if ($expectedArdOfficeId > 0 && $destinationOfficeId !== $expectedArdOfficeId) {
                    throw new RuntimeException('Division Chief can escalate only to the assigned ARD office.');
                }
            } elseif (!workflow_office_is_ored($destinationOffice)) {
                throw new RuntimeException('Division Chief can escalate only to ARD or ORED.');
            }
        }
    }

    if (in_array($actorRoleKey, ['ARD_TS', 'ARD_MS'], true)) {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }

        $destinationLevel = strtoupper(trim((string)($destinationOffice['level'] ?? '')));
        $destinationName = (string)($destinationOffice['name'] ?? '');
        if (workflow_office_is_ored($destinationOffice)) {
            if ($remarks === '') {
                $finalRemarks = 'ARD endorsement: elevated to ORED.';
            }
        } else {
            $isDivisionDestination = $destinationLevel === 'DIVISION';
            $isRbcoDestination = workflow_office_is_rbco($destinationOffice);
            if (!$isDivisionDestination && !$isRbcoDestination) {
                throw new RuntimeException('ARD can forward only to concerned Division, RBCO, or back to ORED.');
            }

            $actorTrack = workflow_ard_track_from_role_key($actorRoleKey);
            $destinationTrack = workflow_office_track_from_context($pdo, $destinationOffice);
            if ($actorTrack !== null && $destinationTrack === null) {
                throw new RuntimeException('Unable to determine ARD cluster mapping for the selected office.');
            }
            if ($actorTrack !== null && $destinationTrack !== null && $actorTrack !== $destinationTrack) {
                throw new RuntimeException('ARD can route only to offices under their assigned cluster.');
            }

            if ($isRbcoDestination) {
                $documentStatus = workflow_build_assigned_status($destinationName !== '' ? $destinationName : 'RBCO');
                if ($remarks === '') {
                    $finalRemarks = 'ARD instruction: assigned directly to RBCO.';
                }
            } else {
                $documentStatus = workflow_build_assigned_status($destinationName !== '' ? $destinationName : 'Division');
                if ($remarks === '') {
                    $finalRemarks = 'ARD instruction: assigned to concerned division.';
                }
            }
        }
    }

    if (workflow_is_regional_ored_role_key($actorRoleKey)) {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        $destinationLevel = strtoupper(trim((string)($destinationOffice['level'] ?? '')));
        $destinationName = strtoupper(trim((string)($destinationOffice['name'] ?? '')));
        $isPacdoDestination = workflow_name_matches_records_unit($destinationName);
        $isArdDestination = workflow_ard_track_from_office_name((string)($destinationOffice['name'] ?? '')) !== null;
        $isDivisionDestination = $destinationLevel === 'DIVISION' || str_contains($destinationName, 'DIVISION');
        $isSectionDestination = $destinationLevel === 'SECTION' || str_contains($destinationName, 'SECTION');
        $normalizedBypassReason = trim($bypassReason);

        if ($actorIsOredReviewer && !$regionalOredSignedStage) {
            $actorOfficeId = (int)($actor['office_id'] ?? 0);
            if ((int)($destinationOffice['id'] ?? 0) !== $actorOfficeId) {
                throw new RuntimeException('ORED review can forward only to the ORED signing account in the same office.');
            }
            if ($destinationUserId === null || $destinationUserId <= 0 || $destinationUserId === (int)($actor['id'] ?? 0)) {
                throw new RuntimeException('A different ORED signing account is required for this handoff.');
            }

            $destinationUser = workflow_get_user_context($pdo, $destinationUserId);
            if (!workflow_actor_is_ored_signer($pdo, $destinationUser)) {
                throw new RuntimeException('Selected destination user is not the ORED signing account.');
            }

            $documentStatus = workflow_build_assigned_status('ORED Sign');
            if ($finalRemarks === '') {
                $finalRemarks = 'ORED review endorsement: forwarded to ORED Sign.';
            }
        } elseif ($actorIsOredSigner && $isPostSignStage) {
            $actorOfficeId = (int)($actor['office_id'] ?? 0);
            if ((int)($destinationOffice['id'] ?? 0) !== $actorOfficeId) {
                throw new RuntimeException('ORED signing account can forward only back to the ORED review account in the same office.');
            }
            if ($destinationUserId === null || $destinationUserId <= 0 || $destinationUserId === (int)($actor['id'] ?? 0)) {
                throw new RuntimeException('A different ORED review account is required for this handoff.');
            }

            $destinationUser = workflow_get_user_context($pdo, $destinationUserId);
            if (workflow_actor_is_ored_signer($pdo, $destinationUser)) {
                throw new RuntimeException('Selected destination user must be the non-signing ORED review account.');
            }

            $documentStatus = workflow_build_assigned_status('ORED Review');
            if ($finalRemarks === '') {
                $finalRemarks = 'ORED signing endorsement: returned to ORED review for onward routing.';
            }
        } else {
            $reviewerPostSignRoute = $actorIsOredReviewer && $regionalOredSignedStage;
            if ($isPacdoDestination) {
                if (!$reviewerPostSignRoute) {
                    throw new RuntimeException('ORED forward must go to ARD, Division, or Section. Use Return instead of forwarding to PACDO/RECORDS-UNIT.');
                }
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'RECORDS-UNIT'));
                if ($finalRemarks === '') {
                    $finalRemarks = 'ORED review release: sent back to RECORDS-UNIT.';
                }
            } elseif (!$isArdDestination && !$isDivisionDestination && !$isSectionDestination) {
                throw new RuntimeException(
                    $isPostSignStage
                        ? 'After signing, ORED can forward only to ARD, Division, Section, or RECORDS-UNIT.'
                        : 'Before signing, ORED can forward only to ARD, Division, or Section.'
                );
            } elseif ($isDivisionDestination || $isSectionDestination) {
                if ($normalizedBypassReason === '') {
                    throw new RuntimeException('Bypass reason is required when ORED forwards directly to Division/Section.');
                }
                $bypassPrefix = 'Bypass reason: ' . $normalizedBypassReason;
                if ($remarks === '') {
                    $finalRemarks = $bypassPrefix;
                } elseif (stripos($remarks, 'bypass reason:') === false) {
                    $finalRemarks = trim($remarks) . ' | ' . $bypassPrefix;
                } else {
                    $finalRemarks = $remarks;
                }
            }

            if ($isDivisionDestination) {
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'Division'));
                if ($finalRemarks === '') {
                    $finalRemarks = 'ORED instruction: assigned to concerned division.';
                }
            } elseif ($isSectionDestination) {
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'Section'));
                if ($finalRemarks === '') {
                    $finalRemarks = 'ORED instruction: assigned directly to concerned section.';
                }
            } elseif ($isArdDestination) {
                $documentStatus = workflow_build_assigned_status((string)($destinationOffice['name'] ?? 'ARD'));
                if ($finalRemarks === '') {
                    $finalRemarks = 'ORED instruction: assigned to concerned ARD.';
                }
            }
        }
    }

    workflow_route_document_internal(
        $pdo,
        $documentId,
        $actorUserId,
        $destinationOfficeId,
        $destinationUserId,
        $finalRemarks,
        'FORWARD',
        $documentStatus,
        'Forwarded'
    );
}

function workflow_update_document_status_internal(
    PDO $pdo,
    int $documentId,
    int $actorUserId,
    string $transitionAction,
    string $documentStatus,
    string $logActionType,
    string $defaultRemarks,
    string $remarks = ''
): void {
    $transitionAction = strtoupper(trim($transitionAction));

    $pdo->beginTransaction();
    try {
        $actor = workflow_get_user_context($pdo, $actorUserId);
        $document = workflow_get_document_context($pdo, $documentId, true);

        workflow_assert_actor_can_route_document($document, $actor, $transitionAction);
        workflow_assert_transition_allowed(
            $pdo,
            $transitionAction,
            (int)($actor['role_id'] ?? 0),
            null
        );

        if ($transitionAction === 'COMPLETE') {
            $update = $pdo->prepare(
                'UPDATE documents
                 SET status = :status,
                     current_office_id = :current_office_id,
                     current_holder_user_id = :current_holder_user_id,
                     pending_office_id = NULL,
                     pending_user_id = NULL
                 WHERE id = :id'
            );
            $update->execute([
                'status' => $documentStatus,
                'current_office_id' => (int)($actor['office_id'] ?? 0),
                'current_holder_user_id' => (int)($actor['id'] ?? 0),
                'id' => $documentId,
            ]);
        } else {
            $update = $pdo->prepare(
                'UPDATE documents
                 SET status = :status
                 WHERE id = :id'
            );
            $update->execute([
                'status' => $documentStatus,
                'id' => $documentId,
            ]);
        }

        workflow_log_action($pdo, [
            'document_id' => $documentId,
            'user_id' => $actorUserId,
            'action_type' => $logActionType,
            'action_scope' => 'ACTION',
            'remarks' => $remarks === '' ? $defaultRemarks : $remarks,
            'is_visible_on_slip' => 0,
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function workflow_return_document(PDO $pdo, int $documentId, int $actorUserId, int $destinationOfficeId, ?int $destinationUserId = null, string $remarks = ''): void
{
    $returnRemarks = trim($remarks);
    if ($returnRemarks === '') {
        throw new InvalidArgumentException('Return remarks are required. Please specify correction instructions for the receiving office.');
    }

    $actor = workflow_get_user_context($pdo, $actorUserId);
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    if ($actorRoleKey === 'RECORDS_UNIT') {
        $documentSnapshot = workflow_get_document_context($pdo, $documentId, false);
        $originatingOfficeId = (int)($documentSnapshot['originating_office_id'] ?? 0);
        if ($originatingOfficeId > 0 && $destinationOfficeId !== $originatingOfficeId) {
            throw new RuntimeException('RECORDS-UNIT return can target only the originating office.');
        }
    }
    if ($actorIsOredReviewer) {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        if ((int)($destinationOffice['id'] ?? 0) !== (int)($actor['office_id'] ?? 0)) {
            throw new RuntimeException('ORED review can forward only to the ORED signing account.');
        }
        if ($destinationUserId === null || $destinationUserId <= 0 || $destinationUserId === (int)($actor['id'] ?? 0)) {
            throw new RuntimeException('A different ORED signing account is required for this handoff.');
        }

        $documentStatus = workflow_build_assigned_status('ORED Sign');
        if ($finalRemarks === '') {
            $finalRemarks = 'ORED review endorsement: forwarded to ORED Sign.';
        }
    } elseif ($actorIsRegionalOred) {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        $destinationName = strtoupper(trim((string)($destinationOffice['name'] ?? '')));
        if (!workflow_name_matches_records_unit($destinationName)) {
            throw new RuntimeException('ORED return can target only RECORDS-UNIT (PACDO).');
        }
    }
    if (in_array($actorRoleKey, ['PENRO_OFFICER', 'CENRO_OFFICER', 'PASU_OFFICER'], true)) {
        $documentSnapshot = workflow_get_document_context($pdo, $documentId, false);
        $originatingOfficeId = (int)($documentSnapshot['originating_office_id'] ?? 0);
        if ($originatingOfficeId > 0 && $destinationOfficeId !== $originatingOfficeId) {
            $actorLabel = $actorRoleKey === 'PENRO_OFFICER'
                ? 'PENRO Officer'
                : ($actorRoleKey === 'PASU_OFFICER' ? 'PASU' : 'CENRO Officer');
            throw new RuntimeException($actorLabel . ' return can target only the originating Admin Record office.');
        }
    }

    workflow_route_document_internal(
        $pdo,
        $documentId,
        $actorUserId,
        $destinationOfficeId,
        $destinationUserId,
        $returnRemarks,
        'RETURN',
        'Returned for Correction',
        'Returned'
    );
}

function workflow_mark_pending(PDO $pdo, int $documentId, int $actorUserId, string $remarks = ''): void
{
    workflow_update_document_status_internal(
        $pdo,
        $documentId,
        $actorUserId,
        'PENDING',
        'Pending',
        'Pending',
        'Marked as pending for additional review/action.',
        $remarks
    );
}

function workflow_complete_document(PDO $pdo, int $documentId, int $actorUserId, string $remarks = ''): void
{
    $actor = workflow_get_user_context($pdo, $actorUserId);
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    if (!in_array($actorRoleKey, ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD', 'PAMO_ADMIN'], true)) {
        throw new RuntimeException('Only CENRO Admin Record, PENRO Admin Record, or PAMO Admin can complete internal transactions.');
    }

    workflow_update_document_status_internal(
        $pdo,
        $documentId,
        $actorUserId,
        'COMPLETE',
        'Completed',
        'Completed',
        'Internal transaction completed locally.',
        $remarks
    );
}

function workflow_approve_document(PDO $pdo, int $documentId, int $actorUserId, string $remarks = ''): void
{
    throw new RuntimeException('Approve action is no longer available.');
}

function workflow_sign_document(PDO $pdo, int $documentId, int $actorUserId, string $remarks = ''): void
{
    $document = workflow_get_document_context($pdo, $documentId, false);
    $status = strtolower(trim((string)($document['status'] ?? '')));
    if (in_array($status, ['signed', 'signed/completed'], true)) {
        return;
    }

    workflow_update_document_status_internal(
        $pdo,
        $documentId,
        $actorUserId,
        'SIGN',
        'Signed/Completed',
        'Signed',
        'Document signed.',
        $remarks
    );
}

function workflow_override_document(PDO $pdo, int $documentId, int $actorUserId, int $destinationOfficeId, ?int $destinationUserId = null, string $remarks = ''): void
{
    workflow_route_document_internal(
        $pdo,
        $documentId,
        $actorUserId,
        $destinationOfficeId,
        $destinationUserId,
        $remarks === '' ? 'Executive path diversion override.' : $remarks,
        'OVERRIDE',
        'Forwarded',
        'Overridden'
    );
}

function workflow_release_mode_key(string $releaseMode): string
{
    $normalized = strtolower(trim($releaseMode));
    return in_array($normalized, ['complete_local', 'send_to_office'], true) ? $normalized : '';
}

function workflow_role_supports_dual_release(string $actorRoleKey): bool
{
    return in_array($actorRoleKey, ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD', 'PAMO_ADMIN'], true);
}

function workflow_assert_internal_release_destination_matches_forward_policy(
    PDO $pdo,
    array $actor,
    int $destinationOfficeId,
    ?int $destinationUserId = null
): void {
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
    if (!$destinationOffice) {
        throw new InvalidArgumentException('Destination office is not valid.');
    }

    $destinationRoleId = workflow_resolve_destination_role_id($pdo, $destinationUserId, $destinationOfficeId);
    $destinationRoleKey = '';
    if ($destinationRoleId !== null) {
        $roleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
        $roleStmt->execute(['id' => $destinationRoleId]);
        $destinationRoleKey = app_normalize_role_key((string)($roleStmt->fetchColumn() ?: ''));
    }

    if ($actorRoleKey === 'PAMO_ADMIN') {
        if (in_array($destinationRoleKey, ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD'], true)) {
            return;
        }
        throw new RuntimeException('PAMO Admin can release only to CENRO Admin Record or PENRO Admin Record.');
    }

    if ($actorRoleKey === 'CENRO_ADMIN_RECORD') {
        if ($destinationRoleKey === 'CENRO_OFFICER') {
            $actorRoot = workflow_find_cenro_root_office($pdo, (int)($actor['office_id'] ?? 0));
            $destinationRoot = workflow_find_cenro_root_office($pdo, $destinationOfficeId);
            $actorRootId = (int)($actorRoot['id'] ?? 0);
            $destinationRootId = (int)($destinationRoot['id'] ?? 0);
            if ($actorRootId <= 0 || $destinationRootId <= 0 || $actorRootId !== $destinationRootId) {
                throw new RuntimeException('CENRO Admin Record can release only to CENRO Officer under the same CENRO office.');
            }
            return;
        }
        if (in_array($destinationRoleKey, ['PENRO_ADMIN_RECORD', 'RECORDS_UNIT'], true)) {
            return;
        }
        throw new RuntimeException('CENRO Admin Record can release only to CENRO Officer, PENRO Admin Record, or PACDO/RECORDS-UNIT.');
    }

    if ($actorRoleKey === 'PENRO_ADMIN_RECORD') {
        if ($destinationRoleKey === 'PENRO_OFFICER') {
            $actorRoot = workflow_find_penro_root_office($pdo, (int)($actor['office_id'] ?? 0));
            $destinationRoot = workflow_find_penro_root_office($pdo, $destinationOfficeId);
            $actorRootId = (int)($actorRoot['id'] ?? 0);
            $destinationRootId = (int)($destinationRoot['id'] ?? 0);
            if ($actorRootId <= 0 || $destinationRootId <= 0 || $actorRootId !== $destinationRootId) {
                throw new RuntimeException('PENRO Admin Record can release only to PENRO Officer under the same PENRO office.');
            }
            return;
        }
        if ($destinationRoleKey === 'CENRO_ADMIN_RECORD') {
            $actorRoot = workflow_find_penro_root_office($pdo, (int)($actor['office_id'] ?? 0));
            $actorRootId = (int)($actorRoot['id'] ?? 0);
            if ($actorRootId <= 0 || !workflow_cenro_belongs_to_penro($pdo, $actorRootId, $destinationOfficeId)) {
                throw new RuntimeException('PENRO Admin Record can release only to CENRO Admin Record offices under the same PENRO scope.');
            }
            return;
        }
        if ($destinationRoleKey === 'RECORDS_UNIT') {
            return;
        }
        throw new RuntimeException('PENRO Admin Record can release only to PENRO Officer, CENRO Admin Record, or PACDO/RECORDS-UNIT.');
    }
}

function workflow_release_document(
    PDO $pdo,
    int $documentId,
    int $actorUserId,
    int $destinationOfficeId,
    ?int $destinationUserId = null,
    string $remarks = '',
    string $releaseMode = ''
): void
{
    $actor = workflow_get_user_context($pdo, $actorUserId);
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    $document = workflow_get_document_context($pdo, $documentId, false);
    $releaseModeKey = workflow_release_mode_key($releaseMode);
    $supportsDualRelease = workflow_role_supports_dual_release($actorRoleKey);

    $currentStatus = strtolower(trim((string)($document['status'] ?? '')));
    $currentOfficeId = (int)($document['current_office_id'] ?? 0);
    $currentPendingOfficeId = (int)($document['pending_office_id'] ?? 0);
    $actorOfficeId = (int)($actor['office_id'] ?? 0);
    $originatingOfficeId = (int)($document['originating_office_id'] ?? 0);

    if (
        $currentStatus === 'released'
        && $currentPendingOfficeId > 0
        && $currentPendingOfficeId === $destinationOfficeId
        && $currentOfficeId === $actorOfficeId
    ) {
        // Idempotent release guard: do not create duplicate release logs.
        return;
    }

    if ($supportsDualRelease) {
        if ($releaseModeKey === '') {
            $releaseModeKey = ($originatingOfficeId > 0 && $destinationOfficeId > 0 && $destinationOfficeId !== $originatingOfficeId)
                ? 'send_to_office'
                : 'complete_local';
        }

        if ($releaseModeKey === 'complete_local') {
            if ($originatingOfficeId <= 0 || $actorOfficeId !== $originatingOfficeId) {
                throw new RuntimeException('Local completion is allowed only when the current office is the originating office.');
            }
            $destinationOfficeId = $originatingOfficeId;

            if ($currentStatus === 'completed') {
                return;
            }

            $pdo->beginTransaction();
            try {
                $lockedDocument = workflow_get_document_context($pdo, $documentId, true);
                workflow_assert_actor_can_route_document($lockedDocument, $actor, 'RELEASE');

                $update = $pdo->prepare(
                    'UPDATE documents
                     SET status = :status,
                         current_office_id = :current_office_id,
                         current_holder_user_id = :current_holder_user_id,
                         pending_office_id = NULL,
                         pending_user_id = NULL
                     WHERE id = :id'
                );
                $update->execute([
                    'status' => 'Completed',
                    'current_office_id' => $destinationOfficeId,
                    'current_holder_user_id' => $actorUserId,
                    'id' => $documentId,
                ]);

                workflow_log_action($pdo, [
                    'document_id' => $documentId,
                    'user_id' => $actorUserId,
                    'action_type' => 'Released',
                    'action_scope' => 'ACTION',
                    'destination_office_id' => $destinationOfficeId,
                    'destination_user_id' => null,
                    'remarks' => $remarks === '' ? 'Released and completed at originating office.' : $remarks,
                    'is_visible_on_slip' => 0,
                ]);

                workflow_log_action($pdo, [
                    'document_id' => $documentId,
                    'user_id' => $actorUserId,
                    'action_type' => 'Completed',
                    'action_scope' => 'CUSTODY',
                    'destination_office_id' => $destinationOfficeId,
                    'destination_user_id' => null,
                    'remarks' => 'Document transaction completed at originating office.',
                    'is_visible_on_slip' => 1,
                ]);

                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }

            return;
        }

        if ($releaseModeKey !== 'send_to_office') {
            throw new InvalidArgumentException('Invalid release mode.');
        }

        if ($destinationOfficeId <= 0) {
            throw new InvalidArgumentException('Destination office is required.');
        }
        if ($destinationOfficeId === $currentOfficeId) {
            throw new RuntimeException('Destination office must be different from current office.');
        }

        $isOriginDestination = $originatingOfficeId > 0 && $destinationOfficeId === $originatingOfficeId;

        if (in_array($actorRoleKey, ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD'], true)) {
            $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
            $destinationName = (string)($destinationOffice['name'] ?? '');
            $destinationLevel = strtoupper(trim((string)($destinationOffice['level'] ?? '')));
            $destinationParentOfficeId = (int)($destinationOffice['parent_office_id'] ?? 0);
            $isRegionalDestination = workflow_name_matches_records_unit($destinationName);
            $isReleaseChainDestination = false;

            if ($actorRoleKey === 'CENRO_ADMIN_RECORD') {
                $actorParentPenro = workflow_find_parent_office_by_level($pdo, $actorOfficeId, 'PROVINCIAL');
                $actorParentPenroId = (int)($actorParentPenro['id'] ?? 0);
                $isReleaseChainDestination = $destinationLevel === 'PENRO_ADMIN_RECORD'
                    && $actorParentPenroId > 0
                    && $destinationParentOfficeId === $actorParentPenroId;
            } else {
                if (
                    $originatingOfficeId > 0
                    && $destinationLevel === 'CENRO_ADMIN_RECORD'
                    && !$isOriginDestination
                ) {
                    throw new RuntimeException('PENRO Admin Record release can target only the originating CENRO office.');
                }
                $actorPenroRoot = workflow_find_penro_root_office($pdo, $actorOfficeId);
                $actorPenroRootId = (int)($actorPenroRoot['id'] ?? 0);
                $isReleaseChainDestination = $destinationLevel === 'CENRO_ADMIN_RECORD'
                    && $actorPenroRootId > 0
                    && workflow_cenro_belongs_to_penro($pdo, $actorPenroRootId, $destinationOfficeId);
            }

            if (!$isOriginDestination && !$isReleaseChainDestination && !$isRegionalDestination) {
                if ($actorRoleKey === 'CENRO_ADMIN_RECORD') {
                    throw new RuntimeException('Released documents can be sent only to the originating office, PENRO Admin Record, or PACDO/RECORDS-UNIT.');
                }
                throw new RuntimeException('Released documents can be sent only to the originating office, CENRO Admin Record, or PACDO/RECORDS-UNIT.');
            }
        }

        if (!$isOriginDestination) {
            workflow_assert_internal_release_destination_matches_forward_policy(
                $pdo,
                $actor,
                $destinationOfficeId,
                $destinationUserId
            );
        }
    } elseif (in_array($actorRoleKey, ['RECORDS_UNIT'], true)) {
        if ($releaseModeKey !== '') {
            throw new RuntimeException('This release mode is not available for RECORDS-UNIT.');
        }
        if ($originatingOfficeId > 0 && $destinationOfficeId !== $originatingOfficeId) {
            $releaseActorLabel = match ($actorRoleKey) {
                'CENRO_ADMIN_RECORD' => 'CENRO Admin Record',
                'PENRO_ADMIN_RECORD' => 'PENRO Admin Record',
                'PAMO_ADMIN' => 'PAMO Admin',
                default => 'RECORDS-UNIT',
            };
            throw new RuntimeException($releaseActorLabel . ' release must route back to the originating office.');
        }

        $selfReleaseToOrigin = $originatingOfficeId > 0
            && $destinationOfficeId === $originatingOfficeId
            && $currentOfficeId > 0
            && $currentOfficeId === $destinationOfficeId;
        if ($selfReleaseToOrigin) {
            if ($currentStatus === 'completed') {
                return;
            }

            $pdo->beginTransaction();
            try {
                $lockedDocument = workflow_get_document_context($pdo, $documentId, true);
                workflow_assert_actor_can_route_document($lockedDocument, $actor, 'RELEASE');

                $update = $pdo->prepare(
                    'UPDATE documents
                     SET status = :status,
                         current_office_id = :current_office_id,
                         current_holder_user_id = :current_holder_user_id,
                         pending_office_id = NULL,
                         pending_user_id = NULL
                     WHERE id = :id'
                );
                $update->execute([
                    'status' => 'Completed',
                    'current_office_id' => $destinationOfficeId,
                    'current_holder_user_id' => $actorUserId,
                    'id' => $documentId,
                ]);

                workflow_log_action($pdo, [
                    'document_id' => $documentId,
                    'user_id' => $actorUserId,
                    'action_type' => 'Released',
                    'action_scope' => 'ACTION',
                    'destination_office_id' => $destinationOfficeId,
                    'destination_user_id' => null,
                    'remarks' => $remarks === '' ? 'Released and completed at originating office.' : $remarks,
                    'is_visible_on_slip' => 0,
                ]);

                workflow_log_action($pdo, [
                    'document_id' => $documentId,
                    'user_id' => $actorUserId,
                    'action_type' => 'Completed',
                    'action_scope' => 'CUSTODY',
                    'destination_office_id' => $destinationOfficeId,
                    'destination_user_id' => null,
                    'remarks' => 'Document transaction completed at originating office.',
                    'is_visible_on_slip' => 1,
                ]);

                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }

            return;
        }
    } elseif ($releaseModeKey !== '') {
        throw new RuntimeException('This release mode is not available for the current role.');
    }

    workflow_route_document_internal(
        $pdo,
        $documentId,
        $actorUserId,
        $destinationOfficeId,
        $destinationUserId,
        $remarks === '' ? 'Released and routed to receiving office.' : $remarks,
        'RELEASE',
        'Released',
        'Released'
    );
}

function workflow_resolve_tracking_action_required(
    PDO $pdo,
    int $documentId,
    int $destinationOfficeId,
    string $receiveRemarks = ''
): string {
    $actionRequired = '';

    if ($documentId > 0 && $destinationOfficeId > 0) {
        $routeRemarksStmt = $pdo->prepare(
            'SELECT TOP (1) remarks
             FROM activity_logs
             WHERE document_id = :document_id
               AND action_scope = :action_scope
               AND destination_office_id = :destination_office_id
               AND action_type IN (\'Forwarded\', \'Rerouted\', \'Sent\', \'Returned\', \'Released\', \'Overridden\')
             ORDER BY created_at DESC, id DESC'
        );
        $routeRemarksStmt->execute([
            'document_id' => $documentId,
            'action_scope' => 'ACTION',
            'destination_office_id' => $destinationOfficeId,
        ]);
        $actionRequired = trim((string)($routeRemarksStmt->fetchColumn() ?: ''));
    }

    $receiveRemarks = trim($receiveRemarks);
    if ($actionRequired === '' && $receiveRemarks !== '') {
        $actionRequired = $receiveRemarks;
    }

    if ($actionRequired === '') {
        $actionRequired = 'For appropriate action';
    }

    if (strlen($actionRequired) > 255) {
        $actionRequired = substr($actionRequired, 0, 255);
    }

    return $actionRequired;
}

function workflow_mark_received(PDO $pdo, int $documentId, int $receivedByUserId, ?int $receivingOfficeId = null, string $remarks = '', string $receiveMethod = 'MANUAL'): void
{
    $receiveMethod = strtoupper(trim($receiveMethod));
    if ($receiveMethod !== 'MANUAL') {
        throw new InvalidArgumentException('Automatic receive is disabled. Use manual receive action only.');
    }

    $receiver = workflow_get_user_context($pdo, $receivedByUserId);

    $pdo->beginTransaction();
    try {
        $document = workflow_get_document_context($pdo, $documentId, true);
        workflow_assert_receive_permission($pdo, $document, $receiver);

        $fromOfficeId = (int)$document['current_office_id'];
        $toOfficeId = $receivingOfficeId ?? (int)($document['pending_office_id'] ?: $receiver['office_id']);
        $status = strtolower(trim((string)($document['status'] ?? '')));
        $originatingOfficeId = (int)($document['originating_office_id'] ?? 0);
        $isReleasedReturnToOrigin = $status === 'released'
            && $originatingOfficeId > 0
            && $toOfficeId === $originatingOfficeId;

        if ($fromOfficeId > 0 && $toOfficeId > 0 && $fromOfficeId === $toOfficeId) {
            $sameOfficePending = (int)($document['pending_office_id'] ?? 0) > 0
                && (int)($document['pending_office_id'] ?? 0) === $fromOfficeId;
            if (!$sameOfficePending) {
                // Self-receive is not a valid custody transition; treat as no-op.
                $pdo->commit();
                return;
            }

            // Recovery path for stale routed records where current_office_id and
            // pending_office_id accidentally point to the same office.
            $repairStatus = $isReleasedReturnToOrigin ? 'Completed' : 'Received';
            $repair = $pdo->prepare(
                'UPDATE documents
                 SET status = :status,
                     current_holder_user_id = :current_holder_user_id,
                     pending_office_id = NULL,
                     pending_user_id = NULL
                 WHERE id = :id'
            );
            $repair->execute([
                'status' => $repairStatus,
                'current_holder_user_id' => $receivedByUserId,
                'id' => $documentId,
            ]);

            $repairActionType = $isReleasedReturnToOrigin ? 'Completed' : 'Received';
            $repairRemarks = $isReleasedReturnToOrigin
                ? 'Document transaction completed at originating office.'
                : 'Document officially received.';
            workflow_log_action($pdo, [
                'document_id' => $documentId,
                'user_id' => $receivedByUserId,
                'action_type' => $repairActionType,
                'action_scope' => 'CUSTODY',
                'destination_office_id' => $toOfficeId,
                'remarks' => $remarks === '' ? $repairRemarks : $remarks,
                'is_visible_on_slip' => 1,
            ]);

            $pdo->commit();
            return;
        }

        $alreadyReceivedByOffice =
            (int)($document['pending_office_id'] ?? 0) === 0
            && (int)($document['current_office_id'] ?? 0) === $toOfficeId
            && $status === 'received';
        $alreadyCompletedByOrigin =
            (int)($document['pending_office_id'] ?? 0) === 0
            && (int)($document['current_office_id'] ?? 0) === $toOfficeId
            && $status === 'completed'
            && $originatingOfficeId > 0
            && $toOfficeId === $originatingOfficeId;
        if ($alreadyReceivedByOffice || $alreadyCompletedByOrigin) {
            $pdo->commit();
            return;
        }

        $fromRoleId = null;
        if ((int)($document['pending_user_role_id'] ?? 0) > 0) {
            $fromRoleId = (int)$document['pending_user_role_id'];
        } elseif ((int)($document['current_holder_role_id'] ?? 0) > 0) {
            $fromRoleId = (int)$document['current_holder_role_id'];
        } else {
            $fromRoleId = workflow_infer_office_role_id($pdo, $fromOfficeId);
        }
        workflow_assert_transition_allowed(
            $pdo,
            'RECEIVE',
            $fromRoleId,
            (int)($receiver['role_id'] ?? 0)
        );

        $nextStatus = $isReleasedReturnToOrigin ? 'Completed' : 'Received';
        $update = $pdo->prepare(
            'UPDATE documents
             SET status = :status,
                 current_office_id = :current_office_id,
                 current_holder_user_id = :current_holder_user_id,
                 pending_office_id = NULL,
                 pending_user_id = NULL
             WHERE id = :id'
        );
        $update->execute([
            'status' => $nextStatus,
            'current_office_id' => $toOfficeId,
            'current_holder_user_id' => $receivedByUserId,
            'id' => $documentId,
        ]);

        $actionRequired = workflow_resolve_tracking_action_required(
            $pdo,
            $documentId,
            $toOfficeId,
            $remarks
        );

        $slip = $pdo->prepare(
            'INSERT INTO tracking_slips (
                document_id, from_office_id, receiving_office_id, received_by, date_time_received, action_required, receive_method
             ) VALUES (
                :document_id, :from_office_id, :receiving_office_id, :received_by, SYSDATETIME(), :action_required, :receive_method
             )'
        );
        $slip->execute([
            'document_id' => $documentId,
            'from_office_id' => $fromOfficeId,
            'receiving_office_id' => $toOfficeId,
            'received_by' => $receivedByUserId,
            'action_required' => $actionRequired,
            'receive_method' => $receiveMethod,
        ]);

        $receiveActionType = $isReleasedReturnToOrigin ? 'Completed' : 'Received';
        $receiveRemarks = $isReleasedReturnToOrigin
            ? 'Document transaction completed at originating office.'
            : 'Document officially received.';
        workflow_log_action($pdo, [
            'document_id' => $documentId,
            'user_id' => $receivedByUserId,
            'action_type' => $receiveActionType,
            'action_scope' => 'CUSTODY',
            'destination_office_id' => $toOfficeId,
            'remarks' => $remarks === '' ? $receiveRemarks : $remarks,
            'is_visible_on_slip' => 1,
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function workflow_reroute_document(PDO $pdo, int $documentId, int $actorUserId, int $destinationOfficeId, string $remarks = ''): void
{
    workflow_route_document_internal(
        $pdo,
        $documentId,
        $actorUserId,
        $destinationOfficeId,
        null,
        $remarks,
        'REROUTE',
        'Forwarded',
        'Rerouted'
    );
}
