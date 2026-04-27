<?php
declare(strict_types=1);

if (!function_exists('super_admin_current_role_key')) {
    function super_admin_current_role_key(): string
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

if (!function_exists('super_admin_has_access')) {
    function super_admin_has_access(): bool
    {
        return super_admin_current_role_key() === 'SUPER_ADMIN';
    }
}

if (!function_exists('super_admin_column_exists')) {
    function super_admin_column_exists(PDO $pdo, string $table, string $column): bool
    {
        static $cache = [];
        $cacheKey = strtolower($table . '.' . $column);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $safeTable = str_replace('`', '', $table);
        $safeColumn = str_replace(['\\', "'"], ['\\\\', "\\'"], $column);
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
        $cache[$cacheKey] = $stmt ? (bool)$stmt->fetch() : false;

        return $cache[$cacheKey];
    }
}

if (!function_exists('super_admin_format_datetime')) {
    function super_admin_format_datetime(?string $value): string
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

if (!function_exists('super_admin_fetch_global_overview')) {
    function super_admin_fetch_global_overview(PDO $pdo): array
    {
        $overview = [
            'users_total' => 0,
            'users_active' => 0,
            'roles_total' => 0,
            'offices_total' => 0,
            'documents_total' => 0,
            'documents_pending' => 0,
            'documents_overdue' => 0,
            'documents_completed_30d' => 0,
            'activity_events_24h' => 0,
            'activity_events_7d' => 0,
            'compliance_rate' => 100,
        ];

        $usersRow = $pdo->query('SELECT COUNT(*) AS users_total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS users_active FROM users')?->fetch() ?: [];
        $overview['users_total'] = (int)($usersRow['users_total'] ?? 0);
        $overview['users_active'] = (int)($usersRow['users_active'] ?? 0);

        $overview['roles_total'] = (int)($pdo->query('SELECT COUNT(*) FROM roles')?->fetchColumn() ?: 0);
        $overview['offices_total'] = (int)($pdo->query('SELECT COUNT(*) FROM offices')?->fetchColumn() ?: 0);

        $docRow = $pdo->query(
            "SELECT
                COUNT(*) AS documents_total,
                SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('overdue', 'at-risk', 'at risk') THEN 1 ELSE 0 END) AS documents_overdue,
                SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('completed', 'released', 'closed', 'resolved', 'done', 'signed', 'signed/completed') THEN 1 ELSE 0 END) AS completed_total,
                SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('completed', 'released', 'closed', 'resolved', 'done', 'signed', 'signed/completed')
                         AND COALESCE(updated_at, created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    THEN 1 ELSE 0 END) AS documents_completed_30d
             FROM documents"
        )?->fetch() ?: [];

        $documentsTotal = (int)($docRow['documents_total'] ?? 0);
        $documentsOverdue = (int)($docRow['documents_overdue'] ?? 0);
        $documentsCompleted = (int)($docRow['completed_total'] ?? 0);

        $overview['documents_total'] = $documentsTotal;
        $overview['documents_overdue'] = $documentsOverdue;
        $overview['documents_completed_30d'] = (int)($docRow['documents_completed_30d'] ?? 0);
        $overview['documents_pending'] = max($documentsTotal - $documentsCompleted, 0);

        $activityRow = $pdo->query(
            "SELECT
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) AS events_24h,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS events_7d
             FROM activity_logs"
        )?->fetch() ?: [];

        $overview['activity_events_24h'] = (int)($activityRow['events_24h'] ?? 0);
        $overview['activity_events_7d'] = (int)($activityRow['events_7d'] ?? 0);

        if ($documentsTotal > 0) {
            $onTime = max($documentsTotal - $documentsOverdue, 0);
            $overview['compliance_rate'] = (int)round(($onTime / $documentsTotal) * 100);
        }

        return $overview;
    }
}

if (!function_exists('super_admin_fetch_recent_documents')) {
    function super_admin_fetch_recent_documents(PDO $pdo, int $limit = 30): array
    {
        $safeLimit = max(5, min($limit, 200));
        $hasPendingOffice = super_admin_column_exists($pdo, 'documents', 'pending_office_id');
        $pendingSelect = $hasPendingOffice
            ? 'LEFT JOIN offices o_pending ON o_pending.id = d.pending_office_id'
            : '';
        $pendingField = $hasPendingOffice
            ? "COALESCE(o_pending.name, '-') AS pending_office,"
            : "'-' AS pending_office,";

        $sql = "SELECT
                    d.id,
                    d.tracking_id,
                    d.subject,
                    d.status,
                    d.source_type,
                    dt.name AS document_type,
                    COALESCE(o_origin.name, '-') AS origin_office,
                    COALESCE(o_current.name, '-') AS current_office,
                    {$pendingField}
                    d.created_at,
                    d.updated_at
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN offices o_origin ON o_origin.id = d.originating_office_id
                LEFT JOIN offices o_current ON o_current.id = d.current_office_id
                {$pendingSelect}
                ORDER BY COALESCE(d.updated_at, d.created_at) DESC, d.id DESC
                LIMIT {$safeLimit}";

        $rows = $pdo->query($sql)?->fetchAll() ?: [];
        if (empty($rows)) {
            return [];
        }

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[] = [
                'document_id' => (int)($row['id'] ?? 0),
                'tracking_id' => trim((string)($row['tracking_id'] ?? '-')),
                'subject' => trim((string)($row['subject'] ?? 'No subject')),
                'status' => trim((string)($row['status'] ?? 'Pending')),
                'source_type' => strtoupper(trim((string)($row['source_type'] ?? 'INTERNAL'))),
                'document_type' => trim((string)($row['document_type'] ?? 'Uncategorized')),
                'origin_office' => trim((string)($row['origin_office'] ?? '-')),
                'current_office' => trim((string)($row['current_office'] ?? '-')),
                'pending_office' => trim((string)($row['pending_office'] ?? '-')),
                'date_created' => super_admin_format_datetime((string)($row['created_at'] ?? null)),
                'date_created_raw' => (string)($row['created_at'] ?? ''),
                'date_updated' => super_admin_format_datetime((string)($row['updated_at'] ?? null)),
                'date_updated_raw' => (string)($row['updated_at'] ?? ''),
            ];
        }

        return $mapped;
    }
}

if (!function_exists('super_admin_fetch_office_analytics')) {
    function super_admin_fetch_office_analytics(PDO $pdo, int $limit = 120): array
    {
        $safeLimit = max(10, min($limit, 200));

        $sql = "SELECT
                    o.id,
                    o.name,
                    o.level,
                    COUNT(d.id) AS total_docs,
                    SUM(CASE WHEN LOWER(COALESCE(d.status, '')) IN ('completed', 'released', 'closed', 'resolved', 'done', 'signed', 'signed/completed') THEN 1 ELSE 0 END) AS completed_docs,
                    SUM(CASE WHEN LOWER(COALESCE(d.status, '')) IN ('overdue', 'at-risk', 'at risk') THEN 1 ELSE 0 END) AS overdue_docs,
                    SUM(CASE WHEN COALESCE(d.updated_at, d.created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS touched_7d
                FROM offices o
                LEFT JOIN documents d
                    ON d.current_office_id = o.id
                    OR d.originating_office_id = o.id
                GROUP BY o.id, o.name, o.level
                ORDER BY overdue_docs DESC, total_docs DESC, o.name ASC
                LIMIT {$safeLimit}";

        $rows = $pdo->query($sql)?->fetchAll() ?: [];
        if (empty($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $totalDocs = (int)($row['total_docs'] ?? 0);
            $completedDocs = (int)($row['completed_docs'] ?? 0);
            $overdueDocs = (int)($row['overdue_docs'] ?? 0);
            $pendingDocs = max($totalDocs - $completedDocs, 0);
            $complianceRate = $totalDocs > 0
                ? (int)round((max($totalDocs - $overdueDocs, 0) / $totalDocs) * 100)
                : 100;

            $result[] = [
                'office_id' => (int)($row['id'] ?? 0),
                'office_name' => trim((string)($row['name'] ?? '-')),
                'office_level' => trim((string)($row['level'] ?? '-')),
                'total_docs' => $totalDocs,
                'pending_docs' => $pendingDocs,
                'overdue_docs' => $overdueDocs,
                'touched_7d' => (int)($row['touched_7d'] ?? 0),
                'compliance_rate' => $complianceRate,
            ];
        }

        return $result;
    }
}

if (!function_exists('super_admin_fetch_users')) {
    function super_admin_fetch_users(PDO $pdo, int $limit = 300): array
    {
        $safeLimit = max(20, min($limit, 1000));

        $sql = "SELECT
                    u.id,
                    u.first_name,
                    u.last_name,
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
                ORDER BY u.id ASC
                LIMIT {$safeLimit}";

        $rows = $pdo->query($sql)?->fetchAll() ?: [];
        if (empty($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int)($row['id'] ?? 0),
                'first_name' => trim((string)($row['first_name'] ?? '')),
                'last_name' => trim((string)($row['last_name'] ?? '')),
                'email' => trim((string)($row['email'] ?? '')),
                'office_id' => (int)($row['office_id'] ?? 0),
                'role_id' => (int)($row['role_id'] ?? 0),
                'is_active' => (int)($row['is_active'] ?? 0) === 1,
                'failed_login_attempts' => (int)($row['failed_login_attempts'] ?? 0),
                'locked_until' => (string)($row['locked_until'] ?? ''),
                'locked_until_label' => super_admin_format_datetime((string)($row['locked_until'] ?? null)),
                'last_login_at' => (string)($row['last_login_at'] ?? ''),
                'last_login_label' => super_admin_format_datetime((string)($row['last_login_at'] ?? null)),
                'role_name' => trim((string)($row['role_name'] ?? '')),
                'office_name' => trim((string)($row['office_name'] ?? '')),
            ];
        }, $rows);
    }
}

if (!function_exists('super_admin_fetch_roles')) {
    function super_admin_fetch_roles(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT id, name, description FROM roles ORDER BY name ASC')?->fetchAll() ?: [];
        if (empty($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int)($row['id'] ?? 0),
                'name' => trim((string)($row['name'] ?? '')),
                'description' => trim((string)($row['description'] ?? '')),
            ];
        }, $rows);
    }
}

if (!function_exists('super_admin_fetch_offices')) {
    function super_admin_fetch_offices(PDO $pdo, int $limit = 400): array
    {
        $safeLimit = max(20, min($limit, 1000));
        $sql = "SELECT id, name, level FROM offices ORDER BY name ASC LIMIT {$safeLimit}";
        $rows = $pdo->query($sql)?->fetchAll() ?: [];
        if (empty($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int)($row['id'] ?? 0),
                'name' => trim((string)($row['name'] ?? '')),
                'level' => trim((string)($row['level'] ?? '')),
            ];
        }, $rows);
    }
}

if (!function_exists('super_admin_fetch_document_types')) {
    function super_admin_fetch_document_types(PDO $pdo, int $limit = 400): array
    {
        $safeLimit = max(20, min($limit, 1000));
        $hasIsActive = super_admin_column_exists($pdo, 'document_types', 'is_active');
        $activeSelect = $hasIsActive ? 'dt.is_active' : '1 AS is_active';

        $sql = "SELECT
                    dt.id,
                    dt.name,
                    dt.category,
                    dt.arta_days_limit,
                    {$activeSelect},
                    COALESCE(r.name, '') AS created_by_role
                FROM document_types dt
                LEFT JOIN roles r ON r.id = dt.created_by_role_id
                ORDER BY dt.name ASC
                LIMIT {$safeLimit}";

        $rows = $pdo->query($sql)?->fetchAll() ?: [];
        if (empty($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int)($row['id'] ?? 0),
                'name' => trim((string)($row['name'] ?? '')),
                'category' => trim((string)($row['category'] ?? 'Simple')),
                'arta_days_limit' => max(1, (int)($row['arta_days_limit'] ?? 3)),
                'is_active' => (int)($row['is_active'] ?? 0) === 1,
                'created_by_role' => trim((string)($row['created_by_role'] ?? '')),
            ];
        }, $rows);
    }
}

if (!function_exists('super_admin_fetch_network_traffic')) {
    function super_admin_fetch_network_traffic(PDO $pdo, int $bucketMinutes = 5, int $bucketCount = 12): array
    {
        $minutes = max(1, min($bucketMinutes, 30));
        $count = max(6, min($bucketCount, 72));
        $lookbackMinutes = $minutes * $count;

        $summary = [
            'active_users' => 0,
            'online_users' => 0,
            'offline_users' => 0,
            'traffic_last_5m' => 0,
            'traffic_last_1h' => 0,
            'inbound_last_1h' => 0,
            'outbound_last_1h' => 0,
            'approval_last_1h' => 0,
        ];

        $usersRow = $pdo->query(
            "SELECT
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_users,
                SUM(CASE WHEN is_active = 1 AND last_login_at IS NOT NULL AND last_login_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 ELSE 0 END) AS online_users
             FROM users"
        )?->fetch() ?: [];

        $summary['active_users'] = (int)($usersRow['active_users'] ?? 0);
        $summary['online_users'] = (int)($usersRow['online_users'] ?? 0);
        $summary['offline_users'] = max($summary['active_users'] - $summary['online_users'], 0);

        $trafficRow = $pdo->query(
            "SELECT
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 ELSE 0 END) AS traffic_last_5m,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 ELSE 0 END) AS traffic_last_1h,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    AND LOWER(COALESCE(action_type, '')) IN ('received', 'recieved', 'opened') THEN 1 ELSE 0 END) AS inbound_last_1h,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    AND LOWER(COALESCE(action_type, '')) IN ('forwarded', 'forward', 'rerouted', 'reroute', 'released', 'returned', 'return', 'overridden', 'override') THEN 1 ELSE 0 END) AS outbound_last_1h,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    AND LOWER(COALESCE(action_type, '')) IN ('approved', 'approve', 'signed') THEN 1 ELSE 0 END) AS approval_last_1h
             FROM activity_logs"
        )?->fetch() ?: [];

        $summary['traffic_last_5m'] = (int)($trafficRow['traffic_last_5m'] ?? 0);
        $summary['traffic_last_1h'] = (int)($trafficRow['traffic_last_1h'] ?? 0);
        $summary['inbound_last_1h'] = (int)($trafficRow['inbound_last_1h'] ?? 0);
        $summary['outbound_last_1h'] = (int)($trafficRow['outbound_last_1h'] ?? 0);
        $summary['approval_last_1h'] = (int)($trafficRow['approval_last_1h'] ?? 0);

        $mixRows = $pdo->query(
            "SELECT UPPER(COALESCE(action_type, 'UNKNOWN')) AS action_key, COUNT(*) AS total
             FROM activity_logs
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             GROUP BY UPPER(COALESCE(action_type, 'UNKNOWN'))
             ORDER BY total DESC, action_key ASC
             LIMIT 8"
        )?->fetchAll() ?: [];

        $actionMix = [];
        foreach ($mixRows as $row) {
            $actionMix[] = [
                'action' => trim((string)($row['action_key'] ?? 'UNKNOWN')),
                'total' => (int)($row['total'] ?? 0),
            ];
        }

        $points = [];
        for ($index = $count - 1; $index >= 0; $index--) {
            $start = new DateTimeImmutable('-' . (($index + 1) * $minutes) . ' minutes');
            $end = new DateTimeImmutable('-' . ($index * $minutes) . ' minutes');

            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS total
                 FROM activity_logs
                 WHERE created_at >= :start_at
                   AND created_at < :end_at'
            );
            $stmt->execute([
                'start_at' => $start->format('Y-m-d H:i:s'),
                'end_at' => $end->format('Y-m-d H:i:s'),
            ]);

            $points[] = [
                'label' => $end->format('H:i'),
                'total' => (int)($stmt->fetchColumn() ?: 0),
            ];
        }

        $eventSql = "SELECT
                        al.id,
                        al.action_type,
                        al.created_at,
                        COALESCE(d.tracking_id, '') AS tracking_id,
                        COALESCE(o_src.name, '') AS source_office,
                        COALESCE(o_dst.name, '') AS destination_office
                     FROM activity_logs al
                     LEFT JOIN documents d ON d.id = al.document_id
                     LEFT JOIN offices o_src ON o_src.id = al.source_office_id
                     LEFT JOIN offices o_dst ON o_dst.id = al.destination_office_id
                     WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL {$lookbackMinutes} MINUTE)
                     ORDER BY al.created_at DESC, al.id DESC
                     LIMIT 15";

        $eventRows = $pdo->query($eventSql)?->fetchAll() ?: [];
        $recentEvents = [];
        foreach ($eventRows as $row) {
            $recentEvents[] = [
                'id' => (int)($row['id'] ?? 0),
                'action_type' => trim((string)($row['action_type'] ?? 'UPDATE')),
                'tracking_id' => trim((string)($row['tracking_id'] ?? '')),
                'source_office' => trim((string)($row['source_office'] ?? '')),
                'destination_office' => trim((string)($row['destination_office'] ?? '')),
                'created_at' => (string)($row['created_at'] ?? ''),
                'created_label' => super_admin_format_datetime((string)($row['created_at'] ?? null)),
            ];
        }

        return [
            'summary' => $summary,
            'action_mix' => $actionMix,
            'traffic_points' => $points,
            'recent_events' => $recentEvents,
            'server_time' => date('c'),
        ];
    }
}
