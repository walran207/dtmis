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

    // MariaDB does not support parameter placeholders in SHOW TABLES LIKE.
    $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($tableName));
    $cache[$key] = $stmt ? (bool)$stmt->fetchColumn() : false;

    return $cache[$key];
}

function workflow_column_exists(PDO $pdo, string $tableName, string $columnName): bool
{
    $safeTable = str_replace('`', '', trim($tableName));
    $safeColumn = str_replace(['\\', "'"], ['\\\\', "\\'"], trim($columnName));
    if ($safeTable === '' || $safeColumn === '') {
        return false;
    }

    $sql = "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'";
    $stmt = $pdo->query($sql);
    return $stmt ? (bool)$stmt->fetch() : false;
}

function workflow_ensure_document_arta_override_columns(PDO $pdo): void
{
    if (!workflow_table_exists($pdo, 'documents')) {
        throw new RuntimeException('Documents table is missing.');
    }

    if (!workflow_column_exists($pdo, 'documents', 'arta_category_override')) {
        try {
            $pdo->exec(
                "ALTER TABLE documents
                 ADD COLUMN arta_category_override VARCHAR(50) NULL DEFAULT NULL
                 AFTER document_type_id"
            );
        } catch (Throwable $exception) {
            if (!workflow_column_exists($pdo, 'documents', 'arta_category_override')) {
                throw $exception;
            }
        }
    }

    if (!workflow_column_exists($pdo, 'documents', 'arta_days_limit_override')) {
        try {
            $pdo->exec(
                "ALTER TABLE documents
                 ADD COLUMN arta_days_limit_override INT(11) NULL DEFAULT NULL
                 AFTER arta_category_override"
            );
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
            $pdo->exec(
                "ALTER TABLE documents
                 ADD COLUMN row_version INT UNSIGNED NOT NULL DEFAULT 1
                 AFTER pending_user_id"
            );
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
        'SELECT u.id, u.office_id, u.role_id, r.name AS role_name
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id AND u.is_active = 1
         LIMIT 1'
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
    $sql = 'SELECT
                d.*,
                cu.role_id AS current_holder_role_id,
                rc.name AS current_holder_role_name,
                pu.role_id AS pending_user_role_id,
                rp.name AS pending_user_role_name
            FROM documents d
            LEFT JOIN users cu ON cu.id = d.current_holder_user_id
            LEFT JOIN roles rc ON rc.id = cu.role_id
            LEFT JOIN users pu ON pu.id = d.pending_user_id
            LEFT JOIN roles rp ON rp.id = pu.role_id
            WHERE d.id = :id
            LIMIT 1';
    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $documentId]);
    $document = $stmt->fetch();

    if (!$document) {
        throw new RuntimeException('Document not found.');
    }

    return $document;
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

function workflow_can_override_role(string $roleName): bool
{
    return app_normalize_role_key($roleName) === 'ORED';
}

function workflow_is_supervisor_role(string $roleName): bool
{
    $key = app_normalize_role_key($roleName);
    return in_array($key, ['DIVISION_CHIEF', 'ARD_TS', 'ARD_MS', 'PENRO', 'RECORDS_UNIT', 'ORED'], true);
}

function workflow_office_name_key(string $name): string
{
    $normalized = strtoupper(trim($name));
    $normalized = preg_replace('/[^A-Z0-9]+/', '', $normalized) ?? '';
    return $normalized;
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
        'SELECT o.id, o.name, o.level, o.parent_office_id
         FROM offices o
         INNER JOIN users u ON u.office_id = o.id
         INNER JOIN roles r ON r.id = u.role_id
         WHERE UPPER(r.name) = :role_name
         ORDER BY o.id ASC
         LIMIT 1'
    );
    $stmt->execute(['role_name' => $roleKey]);
    $office = $stmt->fetch();
    if ($office) {
        return $office;
    }

    $likeNeedle = $normalizedTrack === 'TS' ? '%TECHNICAL%' : '%MANAGEMENT%';
    $fallbackStmt = $pdo->prepare(
        'SELECT id, name, level, parent_office_id
         FROM offices
         WHERE UPPER(name) LIKE :needle
            OR UPPER(name) LIKE :ard_short
         ORDER BY id ASC
         LIMIT 1'
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

function workflow_infer_office_role_id(PDO $pdo, int $officeId): ?int
{
    if ($officeId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, name, level
         FROM offices
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $officeId]);
    $office = $stmt->fetch();
    if (!$office) {
        return null;
    }

    $level = strtoupper(trim((string)($office['level'] ?? '')));
    $name = strtoupper((string)($office['name'] ?? ''));

    if ($level === 'PROTECTED AREA') {
        return workflow_get_role_id_by_key($pdo, 'PASU');
    }
    if ($level === 'COMMUNITY') {
        return workflow_get_role_id_by_key($pdo, 'CENRO');
    }
    if ($level === 'PROVINCIAL') {
        return workflow_get_role_id_by_key($pdo, 'PENRO');
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
        'SELECT id, name, level, parent_office_id
         FROM offices
         WHERE id = :id
         LIMIT 1'
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

function workflow_find_records_unit_office(PDO $pdo): ?array
{
    $stmt = $pdo->query(
        "SELECT id, name, level, parent_office_id
         FROM offices
         WHERE UPPER(name) LIKE '%PACDO%'
            OR UPPER(name) LIKE '%RECORDS-UNIT%'
            OR UPPER(name) LIKE '%RECORDS UNIT%'
            OR UPPER(name) LIKE '%RECORDS_UNIT%'
         ORDER BY id ASC
         LIMIT 1"
    );
    $office = $stmt ? $stmt->fetch() : false;
    if ($office) {
        return $office;
    }

    $fallback = $pdo->query(
        "SELECT o.id, o.name, o.level, o.parent_office_id
         FROM offices o
         INNER JOIN users u ON u.office_id = o.id
         INNER JOIN roles r ON r.id = u.role_id
         WHERE UPPER(REPLACE(REPLACE(r.name, '_', ' '), '-', ' ')) IN ('PACDO', 'RECORDS UNIT')
         ORDER BY o.id ASC
         LIMIT 1"
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

    if (in_array($roleKey, ['CENRO', 'PASU'], true)) {
        $penroOffice = workflow_find_parent_office_by_level($pdo, $originatingOfficeId, 'PROVINCIAL');
        if (!$penroOffice) {
            throw new RuntimeException('Unable to resolve PENRO destination from office hierarchy.');
        }

        return [
            'office_id' => (int)$penroOffice['id'],
            'office_name' => (string)$penroOffice['name'],
            'policy_label' => 'CENRO/PASU documents should route to PENRO first. Direct to RECORDS-UNIT is allowed only as bypass.',
        ];
    }

    if ($roleKey === 'PENRO') {
        $recordsUnitOffice = workflow_find_records_unit_office($pdo);
        if (!$recordsUnitOffice) {
            throw new RuntimeException('Unable to resolve RECORDS-UNIT destination office.');
        }

        return [
            'office_id' => (int)$recordsUnitOffice['id'],
            'office_name' => (string)$recordsUnitOffice['name'],
            'policy_label' => 'PENRO documents must route to RECORDS-UNIT.',
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
        $stmt = $pdo->prepare('SELECT role_id FROM users WHERE id = :id LIMIT 1');
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
        'SELECT r.name
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id
         LIMIT 1'
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

    if ($actorRoleKey === 'ORED') {
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

function workflow_transition_is_allowed(PDO $pdo, string $actionType, ?int $fromRoleId, ?int $toRoleId): bool
{
    if (!workflow_table_exists($pdo, 'workflow_transitions')) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT 1
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
           )
         LIMIT 1'
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
    $divisionChiefRoleId = workflow_get_role_id_by_key($pdo, 'DIVISION_CHIEF');
    $sectionRoleId = workflow_get_role_id_by_key($pdo, 'SECTION_STAFF');
    $ardTsRoleId = workflow_get_role_id_by_key($pdo, 'ARD_TS');
    $ardMsRoleId = workflow_get_role_id_by_key($pdo, 'ARD_MS');
    $penroRoleId = workflow_get_role_id_by_key($pdo, 'PENRO');
    $cenroRoleId = workflow_get_role_id_by_key($pdo, 'CENRO');
    $recordsUnitRoleId = workflow_get_role_id_by_key($pdo, 'RECORDS_UNIT');

    if ($normalizedAction === 'APPROVE' && $toRoleId === null && $fromRoleId !== null) {
        $allowedApproveRoleIds = array_values(array_filter([
            $sectionRoleId,
            $divisionChiefRoleId,
            $ardTsRoleId,
            $ardMsRoleId,
            $penroRoleId,
            $recordsUnitRoleId,
            $oredRoleId,
        ], static fn($id): bool => $id !== null));

        foreach ($allowedApproveRoleIds as $allowedRoleId) {
            if ((int)$fromRoleId === (int)$allowedRoleId) {
                return true;
            }
        }
    }

    if ($normalizedAction === 'PENDING' && $toRoleId === null && $fromRoleId !== null) {
        $allowedPendingRoleIds = array_values(array_filter([
            $cenroRoleId,
            workflow_get_role_id_by_key($pdo, 'PASU'),
            $penroRoleId,
            $recordsUnitRoleId,
            $oredRoleId,
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
            ($ardTsRoleId !== null && (int)$toRoleId === (int)$ardTsRoleId)
            || ($ardMsRoleId !== null && (int)$toRoleId === (int)$ardMsRoleId)
        ) {
            return true;
        }
    }

    if ($normalizedAction === 'FORWARD' && $fromRoleId !== null && $toRoleId !== null) {
        // ARD flow fallback when transition seed rows are missing.
        $oredToArd = $oredRoleId !== null
            && (
                ($ardTsRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$ardTsRoleId)
                || ($ardMsRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$ardMsRoleId)
            );
        if ($oredToArd) {
            return true;
        }

        if (
            $oredRoleId !== null
            && $sectionRoleId !== null
            && (int)$fromRoleId === (int)$oredRoleId
            && (int)$toRoleId === (int)$sectionRoleId
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

        $ardToOred = $oredRoleId !== null
            && (
                ($ardTsRoleId !== null && (int)$fromRoleId === (int)$ardTsRoleId && (int)$toRoleId === (int)$oredRoleId)
                || ($ardMsRoleId !== null && (int)$fromRoleId === (int)$ardMsRoleId && (int)$toRoleId === (int)$oredRoleId)
            );
        if ($ardToOred) {
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
            $oredRoleId !== null
            && $recordsUnitRoleId !== null
            && (int)$fromRoleId === (int)$oredRoleId
            && (int)$toRoleId === (int)$recordsUnitRoleId
        ) {
            return true;
        }
    }

    if ($normalizedAction === 'REROUTE' && $fromRoleId !== null && $toRoleId !== null) {
        // Policy fallback for reroute when workflow transition seed rows are missing.
        $oredToArd = $oredRoleId !== null
            && (
                ($ardTsRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$ardTsRoleId)
                || ($ardMsRoleId !== null && (int)$fromRoleId === (int)$oredRoleId && (int)$toRoleId === (int)$ardMsRoleId)
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

        $ardToOred = $oredRoleId !== null
            && (
                ($ardTsRoleId !== null && (int)$fromRoleId === (int)$ardTsRoleId && (int)$toRoleId === (int)$oredRoleId)
                || ($ardMsRoleId !== null && (int)$fromRoleId === (int)$ardMsRoleId && (int)$toRoleId === (int)$oredRoleId)
            );
        if ($ardToOred) {
            return true;
        }

        if (
            $oredRoleId !== null
            && $divisionChiefRoleId !== null
            && (int)$fromRoleId === (int)$oredRoleId
            && (int)$toRoleId === (int)$divisionChiefRoleId
        ) {
            return true;
        }

        if (
            $oredRoleId !== null
            && $sectionRoleId !== null
            && (int)$fromRoleId === (int)$oredRoleId
            && (int)$toRoleId === (int)$sectionRoleId
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
    if ($actorRoleKey !== 'ORED') {
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

function workflow_assert_receive_permission(array $document, array $receiver): void
{
    $receiverRole = (string)($receiver['role_name'] ?? '');
    $canOverride = workflow_can_override_role($receiverRole);

    $pendingUserId = (int)($document['pending_user_id'] ?? 0);
    $pendingOfficeId = (int)($document['pending_office_id'] ?? 0);

    if ($pendingOfficeId <= 0 && $pendingUserId <= 0) {
        throw new RuntimeException('Document is not awaiting receive.');
    }

    if ($pendingUserId > 0 && $pendingUserId !== (int)$receiver['id'] && !$canOverride) {
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
        'SELECT tracking_id
         FROM documents
         WHERE tracking_id LIKE :prefix
         ORDER BY id DESC
         LIMIT 1'
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
    return app_normalize_role_key($roleName) === 'RECORDS_UNIT';
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
            :document_id, :user_id, :action_type, :action_scope, :destination_office_id, :destination_user_id, :remarks, :is_visible_on_slip, NOW()
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
    $externalClientName = trim((string)($payload['external_client_name'] ?? ''));
    $originatingOfficeId = (int)($payload['originating_office_id'] ?? $user['office_id']);
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

    $trackingId = workflow_generate_tracking_id($pdo);
    $hasArtaCategoryOverrideColumn = workflow_column_exists($pdo, 'documents', 'arta_category_override');
    $hasArtaDaysOverrideColumn = workflow_column_exists($pdo, 'documents', 'arta_days_limit_override');

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
            'external_client_name' => $sourceType === 'EXTERNAL' ? $externalClientName : null,
            'created_by_user_id' => $actorUserId,
            'current_holder_user_id' => $actorUserId,
            'pending_office_id' => null,
            'pending_user_id' => null,
        ];

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

        if ($currentPendingOfficeId > 0 && $currentPendingOfficeId === $destinationOfficeId) {
            // Idempotent routing guard: destination already pending for receive.
            $pdo->commit();
            return;
        }
        if ($currentPendingOfficeId <= 0 && $currentOfficeId > 0 && $currentOfficeId === $destinationOfficeId) {
            throw new RuntimeException('Destination office must be different from current office.');
        }

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
    bool $hasRouteAttachment = false
): void
{
    $documentStatus = 'Forwarded';
    $finalRemarks = $remarks === '' ? 'Forwarded to next office.' : $remarks;

    $actor = workflow_get_user_context($pdo, $actorUserId);
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    $documentSnapshot = workflow_get_document_context($pdo, $documentId, false);
    $currentStatus = strtolower(trim((string)($documentSnapshot['status'] ?? '')));
    $canForwardOredToPacdoCorrectionWithoutApproval = false;
    if ($actorRoleKey === 'ORED') {
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
    $canForwardCreatedIntakeWithoutApproval = in_array($actorRoleKey, ['PENRO', 'RECORDS_UNIT'], true)
        && (int)($documentSnapshot['created_by_user_id'] ?? 0) === $actorUserId
        && str_starts_with($currentStatus, 'created')
        && (int)($documentSnapshot['pending_office_id'] ?? 0) <= 0;
    $canForwardReturnedCorrectionWithoutApproval = false;
    if (in_array($actorRoleKey, ['PENRO', 'RECORDS_UNIT'], true)) {
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
                'SELECT 1
                 FROM activity_logs
                 WHERE document_id = :document_id
                   AND LOWER(COALESCE(action_type, \'\')) IN (\'return\', \'returned\')
                 LIMIT 1'
            );
            $returnActionStmt->execute(['document_id' => $documentId]);
            $canForwardReturnedCorrectionWithoutApproval = (bool)$returnActionStmt->fetchColumn();
        }
    }

    $isPostSignStage = str_contains($currentStatus, 'signed')
        || str_contains($currentStatus, 'completed')
        || ($actorRoleKey === 'ORED' && $canForwardOredToPacdoCorrectionWithoutApproval);
    $approvalRequiredRoles = ['PENRO', 'RECORDS_UNIT', 'DIVISION_CHIEF', 'SECTION_STAFF', 'ORED', 'ARD_TS', 'ARD_MS'];
    if (in_array($actorRoleKey, $approvalRequiredRoles, true)) {
        $requiresApprovedStatus = !($actorRoleKey === 'ORED' && $isPostSignStage);
        if ($canForwardCreatedIntakeWithoutApproval || $canForwardReturnedCorrectionWithoutApproval) {
            $requiresApprovedStatus = false;
        }
        if ($requiresApprovedStatus && !str_contains($currentStatus, 'approved')) {
            throw new RuntimeException($actorRoleKey . ' must approve the document before forwarding.');
        }
    }

    if (in_array($actorRoleKey, ['CENRO', 'PASU'], true)) {
        $originatingOfficeId = (int)($documentSnapshot['originating_office_id'] ?? 0);
        if ($originatingOfficeId <= 0) {
            $originatingOfficeId = (int)($actor['office_id'] ?? 0);
        }
        $requiredDestination = workflow_required_initial_destination_office($pdo, $actor, $originatingOfficeId);
        if ($requiredDestination !== null) {
            $requiredOfficeId = (int)($requiredDestination['office_id'] ?? 0);
            if ($requiredOfficeId > 0 && $destinationOfficeId !== $requiredOfficeId) {
                $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
                $destinationOfficeName = strtoupper(trim((string)($destinationOffice['name'] ?? '')));
                $isRecordsUnitBypass = workflow_name_matches_records_unit($destinationOfficeName);
                if (!$isRecordsUnitBypass) {
                    throw new RuntimeException('Routing policy violation: ' . (string)($requiredDestination['policy_label'] ?? 'CENRO/PASU must route to PENRO first. Direct to RECORDS-UNIT is allowed only as bypass.'));
                }
            }
        }
    }

    if ($actorRoleKey === 'PENRO') {
        $penroPendingOfficeId = (int)($documentSnapshot['pending_office_id'] ?? 0);
        $penroActorOfficeId = (int)($actor['office_id'] ?? 0);

        if ($penroPendingOfficeId > 0 && $penroActorOfficeId > 0 && $penroPendingOfficeId !== $penroActorOfficeId) {
            throw new RuntimeException('Document is already forwarded and waiting for the destination office to receive it.');
        }

        $penroDestinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        $penroDestinationName = strtoupper(trim((string)($penroDestinationOffice['name'] ?? '')));
        if (workflow_name_matches_records_unit($penroDestinationName) && !$hasRouteAttachment) {
            throw new RuntimeException('Endorsement letter attachment is required before forwarding to PACDO/RECORDS-UNIT.');
        }
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
            if ($destinationLevel !== 'DIVISION') {
                throw new RuntimeException('ARD can forward only to concerned Division or back to ORED.');
            }

            $actorTrack = workflow_ard_track_from_role_key($actorRoleKey);
            $divisionTrack = workflow_division_track_from_name($destinationName);
            if ($actorTrack !== null && $divisionTrack === null) {
                throw new RuntimeException('Unable to determine ARD cluster mapping for the selected division.');
            }
            if ($actorTrack !== null && $divisionTrack !== null && $actorTrack !== $divisionTrack) {
                throw new RuntimeException('ARD can route only to divisions under their assigned cluster.');
            }

            $documentStatus = workflow_build_assigned_status($destinationName !== '' ? $destinationName : 'Division');
            if ($remarks === '') {
                $finalRemarks = 'ARD instruction: assigned to concerned division.';
            }
        }
    }

    if ($actorRoleKey === 'ORED') {
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

        if ($isPostSignStage) {
            if (!$isPacdoDestination) {
                throw new RuntimeException('After signing, ORED can forward only to RECORDS-UNIT.');
            }
        } else {
            if ($isPacdoDestination) {
                throw new RuntimeException('ORED can forward to RECORDS-UNIT only after signing.');
            }
            if (!$isArdDestination && !$isDivisionDestination && !$isSectionDestination) {
                throw new RuntimeException('Before signing, ORED can forward only to ARD, Division, or Section.');
            }

            if ($isDivisionDestination || $isSectionDestination) {
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

        $update = $pdo->prepare(
            'UPDATE documents
             SET status = :status
             WHERE id = :id'
        );
        $update->execute([
            'status' => $documentStatus,
            'id' => $documentId,
        ]);

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
    if ($actorRoleKey === 'ORED') {
        $destinationOffice = workflow_get_office_context($pdo, $destinationOfficeId);
        if (!$destinationOffice) {
            throw new InvalidArgumentException('Destination office is not valid.');
        }
        $destinationName = strtoupper(trim((string)($destinationOffice['name'] ?? '')));
        if (!workflow_name_matches_records_unit($destinationName)) {
            throw new RuntimeException('ORED return can target only RECORDS-UNIT (PACDO).');
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

function workflow_approve_document(PDO $pdo, int $documentId, int $actorUserId, string $remarks = ''): void
{
    $document = workflow_get_document_context($pdo, $documentId, false);
    $status = strtolower(trim((string)($document['status'] ?? '')));
    if (str_contains($status, 'approved')) {
        return;
    }
    if (!str_contains($status, 'received')) {
        throw new RuntimeException('Document must be received before approval.');
    }

    workflow_update_document_status_internal(
        $pdo,
        $documentId,
        $actorUserId,
        'APPROVE',
        'Approved',
        'Approved',
        'Document approved.',
        $remarks
    );
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

function workflow_release_document(PDO $pdo, int $documentId, int $actorUserId, int $destinationOfficeId, ?int $destinationUserId = null, string $remarks = ''): void
{
    $actor = workflow_get_user_context($pdo, $actorUserId);
    $actorRoleKey = app_normalize_role_key((string)($actor['role_name'] ?? ''));
    $document = workflow_get_document_context($pdo, $documentId, false);

    $currentStatus = strtolower(trim((string)($document['status'] ?? '')));
    $currentOfficeId = (int)($document['current_office_id'] ?? 0);
    $currentPendingOfficeId = (int)($document['pending_office_id'] ?? 0);
    $actorOfficeId = (int)($actor['office_id'] ?? 0);

    if (
        $currentStatus === 'released'
        && $currentPendingOfficeId > 0
        && $currentPendingOfficeId === $destinationOfficeId
        && $currentOfficeId === $actorOfficeId
    ) {
        // Idempotent release guard: do not create duplicate release logs.
        return;
    }

    if ($actorRoleKey === 'RECORDS_UNIT') {
        $originatingOfficeId = (int)($document['originating_office_id'] ?? 0);
        if ($originatingOfficeId > 0 && $destinationOfficeId !== $originatingOfficeId) {
            throw new RuntimeException('RECORDS-UNIT release must route back to the originating office.');
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
            'SELECT remarks
             FROM activity_logs
             WHERE document_id = :document_id
               AND action_scope = :action_scope
               AND destination_office_id = :destination_office_id
               AND action_type IN (\'Forwarded\', \'Rerouted\', \'Sent\', \'Returned\', \'Released\', \'Overridden\')
             ORDER BY created_at DESC, id DESC
             LIMIT 1'
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
        workflow_assert_receive_permission($document, $receiver);

        $fromOfficeId = (int)$document['current_office_id'];
        $toOfficeId = $receivingOfficeId ?? (int)($document['pending_office_id'] ?: $receiver['office_id']);
        $status = strtolower(trim((string)($document['status'] ?? '')));
        $originatingOfficeId = (int)($document['originating_office_id'] ?? 0);
        $isReleasedReturnToOrigin = $status === 'released'
            && $originatingOfficeId > 0
            && $toOfficeId === $originatingOfficeId;

        if ($fromOfficeId > 0 && $toOfficeId > 0 && $fromOfficeId === $toOfficeId) {
            // Self-receive is not a valid custody transition; treat as no-op.
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
                :document_id, :from_office_id, :receiving_office_id, :received_by, NOW(), :action_required, :receive_method
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
