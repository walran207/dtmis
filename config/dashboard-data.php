<?php
declare(strict_types=1);

require_once __DIR__ . '/workflow.php';

if (!function_exists('dashboard_column_exists')) {
    function dashboard_column_exists(PDO $pdo, string $table, string $column): bool
    {
        static $cache = [];
        $cacheKey = strtolower($table . '.' . $column);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = db_column_exists($pdo, $table, $column);

        return $cache[$cacheKey];
    }
}

if (!function_exists('dashboard_documents_arta_category_expr')) {
    function dashboard_documents_arta_category_expr(PDO $pdo): string
    {
        $hasCategoryOverride = dashboard_column_exists($pdo, 'documents', 'arta_category_override');
        if ($hasCategoryOverride) {
            return "COALESCE(NULLIF(TRIM(d.arta_category_override), ''), dt.category)";
        }

        return 'dt.category';
    }
}

if (!function_exists('dashboard_documents_arta_days_expr')) {
    function dashboard_documents_arta_days_expr(PDO $pdo): string
    {
        $hasDaysOverride = dashboard_column_exists($pdo, 'documents', 'arta_days_limit_override');
        if ($hasDaysOverride) {
            return 'COALESCE(NULLIF(d.arta_days_limit_override, 0), dt.arta_days_limit, 3)';
        }

        return 'COALESCE(dt.arta_days_limit, 3)';
    }
}

if (!function_exists('dashboard_documents_row_version_expr')) {
    function dashboard_documents_row_version_expr(PDO $pdo): string
    {
        if (dashboard_column_exists($pdo, 'documents', 'row_version')) {
            return 'COALESCE(d.row_version, 1)';
        }

        return '1';
    }
}

if (!function_exists('dashboard_documents_sender_expr')) {
    function dashboard_documents_sender_expr(PDO $pdo, string $originOfficeAlias = 'o_origin'): string
    {
        if (dashboard_column_exists($pdo, 'documents', 'sender')) {
            return "COALESCE(NULLIF(TRIM(d.sender), ''), NULLIF(TRIM(d.external_client_name), ''), COALESCE({$originOfficeAlias}.name, ''))";
        }

        return "COALESCE(NULLIF(TRIM(d.external_client_name), ''), COALESCE({$originOfficeAlias}.name, ''))";
    }
}

if (!function_exists('dashboard_documents_origin_expr')) {
    function dashboard_documents_origin_expr(PDO $pdo, string $originOfficeAlias = 'o_origin'): string
    {
        if (dashboard_column_exists($pdo, 'documents', 'originating_entity_name')) {
            return "COALESCE(NULLIF(TRIM(d.originating_entity_name), ''), COALESCE({$originOfficeAlias}.name, '-'))";
        }

        return "COALESCE({$originOfficeAlias}.name, '-')";
    }
}

if (!function_exists('dashboard_inline_sqlsrv_named_params')) {
    function dashboard_inline_sqlsrv_named_params(PDO $pdo, string $sql, array $params): string
    {
        if (!db_is_sql_server($pdo) || $params === []) {
            return $sql;
        }

        $replacements = [];
        foreach ($params as $key => $value) {
            $placeholder = (string)$key;
            if ($placeholder === '') {
                continue;
            }
            if ($placeholder[0] !== ':') {
                $placeholder = ':' . $placeholder;
            }
            $replacements[$placeholder] = (string)(int)$value;
        }

        if ($replacements === []) {
            return $sql;
        }

        uksort($replacements, static function (string $left, string $right): int {
            return strlen($right) <=> strlen($left);
        });

        return strtr($sql, $replacements);
    }
}

if (!function_exists('dashboard_sql_top_one_subquery')) {
    function dashboard_sql_top_one_subquery(
        PDO $pdo,
        string $selectExpr,
        string $fromClause,
        string $whereClause,
        string $orderBy,
        string $alias
    ): string {
        return "(
                        SELECT TOP (1) {$selectExpr}
                        {$fromClause}
                        {$whereClause}
                        ORDER BY {$orderBy}
                    ) AS {$alias}";
    }
}

if (!function_exists('dashboard_sql_custom_others_remarks_subquery')) {
    function dashboard_sql_custom_others_remarks_subquery(PDO $pdo, string $alias = 'custom_others_remarks'): string
    {
        return dashboard_sql_top_one_subquery(
            $pdo,
            'al_custom.remarks',
            'FROM activity_logs al_custom',
            "WHERE al_custom.document_id = d.id
              AND LOWER(COALESCE(al_custom.remarks, '')) LIKE '%specified document type (others):%'",
            'al_custom.id DESC',
            $alias
        );
    }
}

if (!function_exists('dashboard_sql_exists_flag')) {
    function dashboard_sql_exists_flag(PDO $pdo, string $subquery, string $alias): string
    {
        return 'CASE WHEN EXISTS(' . $subquery . ') THEN 1 ELSE 0 END AS ' . $alias;
    }
}

if (!function_exists('dashboard_sql_limit_suffix')) {
    function dashboard_sql_limit_suffix(int $limit): string
    {
        $safeLimit = max(1, $limit);
        return ' OFFSET 0 ROWS FETCH NEXT ' . $safeLimit . ' ROWS ONLY';
    }
}

if (!function_exists('dashboard_sql_add_days_expr')) {
    function dashboard_sql_add_days_expr(string $dateExpr, string $daysExpr): string
    {
        return 'DATEADD(DAY, ' . $daysExpr . ', ' . $dateExpr . ')';
    }
}

if (!function_exists('dashboard_sql_current_date_expr')) {
    function dashboard_sql_current_date_expr(): string
    {
        return 'CAST(SYSDATETIME() AS DATE)';
    }
}

if (!function_exists('dashboard_sql_date_only_expr')) {
    function dashboard_sql_date_only_expr(string $dateExpr): string
    {
        return 'CAST(' . $dateExpr . ' AS DATE)';
    }
}

if (!function_exists('dashboard_sql_days_until_expr')) {
    function dashboard_sql_days_until_expr(string $dateExpr): string
    {
        return 'DATEDIFF(DAY, ' . dashboard_sql_current_date_expr() . ', ' . dashboard_sql_date_only_expr($dateExpr) . ')';
    }
}

if (!function_exists('dashboard_sql_recent_cutoff_expr')) {
    function dashboard_sql_recent_cutoff_expr(int $days): string
    {
        return 'DATEADD(DAY, -' . max(0, $days) . ', SYSDATETIME())';
    }
}

if (!function_exists('dashboard_normalize_filter_date_input')) {
    function dashboard_normalize_filter_date_input(?string $value, bool $endOfDay = false): ?array
    {
        $raw = trim((string)($value ?? ''));
        if ($raw === '') {
            return null;
        }

        try {
            $date = new DateTimeImmutable($raw);
            $date = $endOfDay ? $date->setTime(23, 59, 59) : $date->setTime(0, 0, 0);

            return [
                'input' => $date->format('Y-m-d'),
                'sql' => $date->format('Y-m-d H:i:s'),
                'timestamp' => $date->getTimestamp(),
            ];
        } catch (Throwable $exception) {
            return null;
        }
    }
}

if (!function_exists('dashboard_effective_date_range')) {
    function dashboard_effective_date_range(?array $dateRange = null): array
    {
        $source = is_array($dateRange) ? $dateRange : $_GET;

        $fromDate = dashboard_normalize_filter_date_input((string)($source['from'] ?? $source['fromDate'] ?? $source['from_date'] ?? ''), false);
        $toDate = dashboard_normalize_filter_date_input((string)($source['to'] ?? $source['toDate'] ?? $source['to_date'] ?? ''), true);

        if ($fromDate !== null && $toDate !== null && (int)$fromDate['timestamp'] > (int)$toDate['timestamp']) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
            $fromDate = dashboard_normalize_filter_date_input((string)($fromDate['input'] ?? ''), false);
            $toDate = dashboard_normalize_filter_date_input((string)($toDate['input'] ?? ''), true);
        }

        $normalized = [];
        if ($fromDate !== null) {
            $normalized['from_input'] = (string)$fromDate['input'];
            $normalized['from_sql'] = (string)$fromDate['sql'];
        }
        if ($toDate !== null) {
            $normalized['to_input'] = (string)$toDate['input'];
            $normalized['to_sql'] = (string)$toDate['sql'];
        }

        return $normalized;
    }
}

if (!function_exists('dashboard_build_date_range_clause')) {
    function dashboard_build_date_range_clause(string $expression, ?array $dateRange = null, string $prefix = 'date_range'): array
    {
        $range = dashboard_effective_date_range($dateRange);
        $clauses = [];
        $params = [];

        if (isset($range['from_sql']) && trim((string)$range['from_sql']) !== '') {
            $clauses[] = $expression . ' >= :' . $prefix . '_from';
            $params[$prefix . '_from'] = (string)$range['from_sql'];
        }
        if (isset($range['to_sql']) && trim((string)$range['to_sql']) !== '') {
            $clauses[] = $expression . ' <= :' . $prefix . '_to';
            $params[$prefix . '_to'] = (string)$range['to_sql'];
        }

        return [
            'sql' => empty($clauses) ? '' : (' AND ' . implode(' AND ', $clauses)),
            'params' => $params,
            'range' => $range,
        ];
    }
}

if (!function_exists('dashboard_section_like_level_sql_list')) {
function dashboard_section_like_level_sql_list(): string
{
    return "'SECTION', 'CENRO_SECTION', 'PENRO_DIVISION', 'PASU_OFFICER'";
}
}

if (!function_exists('dashboard_internal_admin_local_signed_action')) {
    function dashboard_internal_admin_local_signed_action(PDO $pdo, int $documentId, int $officeId): ?bool
    {
        static $resultCache = [];
        static $officeCache = [];
        static $rootCache = [];

        if ($documentId <= 0 || $officeId <= 0) {
            return null;
        }

        $cacheKey = $documentId . ':' . $officeId;
        if (array_key_exists($cacheKey, $resultCache)) {
            return $resultCache[$cacheKey];
        }

        $officeCacheKey = (string)$officeId;
        if (!array_key_exists($officeCacheKey, $officeCache)) {
            $officeCache[$officeCacheKey] = workflow_get_office_context($pdo, $officeId);
        }
        $office = $officeCache[$officeCacheKey];
        if (!is_array($office)) {
            $resultCache[$cacheKey] = null;
            return null;
        }

        $officeLevel = strtoupper(trim((string)($office['level'] ?? '')));
        $expectedRoleKey = '';
        $rootResolver = null;

        if ($officeLevel === 'CENRO_ADMIN_RECORD') {
            $expectedRoleKey = 'CENRO_OFFICER';
            $rootResolver = static function (int $signedOfficeId) use ($pdo, &$rootCache): int {
                $rootCacheKey = 'cenro:' . $signedOfficeId;
                if (!array_key_exists($rootCacheKey, $rootCache)) {
                    $root = workflow_find_cenro_root_office($pdo, $signedOfficeId);
                    $rootCache[$rootCacheKey] = (int)($root['id'] ?? 0);
                }
                return (int)$rootCache[$rootCacheKey];
            };
        } elseif ($officeLevel === 'PENRO_ADMIN_RECORD') {
            $expectedRoleKey = 'PENRO_OFFICER';
            $rootResolver = static function (int $signedOfficeId) use ($pdo, &$rootCache): int {
                $rootCacheKey = 'penro:' . $signedOfficeId;
                if (!array_key_exists($rootCacheKey, $rootCache)) {
                    $root = workflow_find_penro_root_office($pdo, $signedOfficeId);
                    $rootCache[$rootCacheKey] = (int)($root['id'] ?? 0);
                }
                return (int)$rootCache[$rootCacheKey];
            };
        } elseif ($officeLevel === 'PAMO_ADMIN') {
            $expectedRoleKey = 'PASU_OFFICER';
            $rootResolver = static function (int $signedOfficeId) use ($pdo, &$rootCache): int {
                $rootCacheKey = 'pamo:' . $signedOfficeId;
                if (!array_key_exists($rootCacheKey, $rootCache)) {
                    $root = workflow_find_pamo_root_office($pdo, $signedOfficeId);
                    $rootCache[$rootCacheKey] = (int)($root['id'] ?? 0);
                }
                return (int)$rootCache[$rootCacheKey];
            };
        } else {
            $resultCache[$cacheKey] = null;
            return null;
        }

        $viewerRootId = $rootResolver($officeId);
        if ($viewerRootId <= 0 || $expectedRoleKey === '') {
            $resultCache[$cacheKey] = false;
            return false;
        }

        $stmt = $pdo->prepare(
            'SELECT DISTINCT u.office_id
             FROM activity_logs al
             INNER JOIN users u ON u.id = al.user_id
             INNER JOIN roles r ON r.id = u.role_id
             INNER JOIN offices o ON o.id = u.office_id
             WHERE al.document_id = :document_id
               AND LOWER(COALESCE(al.action_type, \'\')) = \'signed\'
               AND UPPER(COALESCE(r.name, \'\')) = :role_name'
        );
        $stmt->execute([
            'document_id' => $documentId,
            'role_name' => $expectedRoleKey,
        ]);
        $signedOfficeIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        foreach ($signedOfficeIds as $signedOfficeIdRaw) {
            $signedOfficeId = (int)$signedOfficeIdRaw;
            if ($signedOfficeId <= 0) {
                continue;
            }
            if ($rootResolver($signedOfficeId) === $viewerRootId) {
                $resultCache[$cacheKey] = true;
                return true;
            }
        }

        $resultCache[$cacheKey] = false;
        return false;
    }
}

if (!function_exists('dashboard_queue_local_signed_action')) {
    function dashboard_queue_local_signed_action(PDO $pdo, int $documentId, int $officeId): ?bool
    {
        static $resultCache = [];
        static $officeCache = [];

        if ($documentId <= 0 || $officeId <= 0) {
            return null;
        }

        $cacheKey = $documentId . ':' . $officeId;
        if (array_key_exists($cacheKey, $resultCache)) {
            return $resultCache[$cacheKey];
        }

        $officeCacheKey = (string)$officeId;
        if (!array_key_exists($officeCacheKey, $officeCache)) {
            $officeCache[$officeCacheKey] = workflow_get_office_context($pdo, $officeId);
        }
        $office = $officeCache[$officeCacheKey];
        if (!is_array($office)) {
            $resultCache[$cacheKey] = null;
            return null;
        }

        $officeLevel = strtoupper(trim((string)($office['level'] ?? '')));
        if (in_array($officeLevel, ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD', 'PAMO_ADMIN'], true)) {
            $resultCache[$cacheKey] = dashboard_internal_admin_local_signed_action($pdo, $documentId, $officeId);
            return $resultCache[$cacheKey];
        }

        if (workflow_name_matches_records_unit((string)($office['name'] ?? ''))) {
            $latestReceiveStmt = $pdo->prepare(
                'SELECT TOP (1) ts.from_office_id, ts.date_time_received
                 FROM tracking_slips ts
                 WHERE ts.document_id = :document_id
                   AND ts.receiving_office_id = :office_id
                 ORDER BY ts.date_time_received DESC, ts.id DESC'
            );
            $latestReceiveStmt->execute([
                'document_id' => $documentId,
                'office_id' => $officeId,
            ]);
            $latestReceive = $latestReceiveStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            $latestReceiveAt = trim((string)($latestReceive['date_time_received'] ?? ''));
            $fromOfficeId = (int)($latestReceive['from_office_id'] ?? 0);
            if ($latestReceiveAt === '' || $fromOfficeId <= 0) {
                $resultCache[$cacheKey] = false;
                return false;
            }

            $fromOfficeCacheKey = 'from:' . $fromOfficeId;
            if (!array_key_exists($fromOfficeCacheKey, $officeCache)) {
                $officeCache[$fromOfficeCacheKey] = workflow_get_office_context($pdo, $fromOfficeId);
            }
            $fromOffice = $officeCache[$fromOfficeCacheKey];
            if (!is_array($fromOffice) || !workflow_office_is_ored($fromOffice)) {
                $resultCache[$cacheKey] = false;
                return false;
            }

            $latestPacdoDispatchStmt = $pdo->prepare(
                'SELECT MAX(al.created_at)
                 FROM activity_logs al
                 INNER JOIN users u ON u.id = al.user_id
                 WHERE al.document_id = :document_id
                   AND u.office_id = :office_id
                   AND al.destination_office_id = :destination_office_id
                   AND LOWER(COALESCE(al.action_type, \'\')) IN (\'forwarded\', \'forward\', \'released\', \'rerouted\', \'overridden\')
                   AND al.created_at <= :received_at'
            );
            $latestPacdoDispatchStmt->execute([
                'document_id' => $documentId,
                'office_id' => $officeId,
                'destination_office_id' => $fromOfficeId,
                'received_at' => $latestReceiveAt,
            ]);
            $latestPacdoDispatchAt = trim((string)($latestPacdoDispatchStmt->fetchColumn() ?: ''));
            if ($latestPacdoDispatchAt === '') {
                $resultCache[$cacheKey] = false;
                return false;
            }

            $oredSignedStmt = $pdo->prepare(
                'SELECT MAX(al.created_at)
                 FROM activity_logs al
                 INNER JOIN users u ON u.id = al.user_id
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE al.document_id = :document_id
                   AND u.office_id = :office_id
                   AND UPPER(COALESCE(r.name, \'\')) IN (\'ORED\', \'ORED_SIGN\')
                   AND LOWER(COALESCE(al.action_type, \'\')) IN (\'signed\', \'signed/completed\', \'completed\')
                   AND al.created_at >= :dispatched_at
                   AND al.created_at <= :received_at'
            );
            $oredSignedStmt->execute([
                'document_id' => $documentId,
                'office_id' => $fromOfficeId,
                'dispatched_at' => $latestPacdoDispatchAt,
                'received_at' => $latestReceiveAt,
            ]);

            $resultCache[$cacheKey] = trim((string)($oredSignedStmt->fetchColumn() ?: '')) !== '';
            return $resultCache[$cacheKey];
        }

        $expectedRoleKey = match ($officeLevel) {
            'CENRO_OFFICER' => 'CENRO_OFFICER',
            'PENRO_OFFICER' => 'PENRO_OFFICER',
            'PASU_OFFICER' => 'PASU_OFFICER',
            default => (workflow_office_is_ored($office) ? 'ORED' : ''),
        };

        if ($expectedRoleKey === '') {
            // Non-signing queues such as PACDO/RECORDS-UNIT, Division, Section, and
            // other internal offices should not inherit a previous office's signed
            // state. Treat them as unsigned unless their own local logic says otherwise.
            $resultCache[$cacheKey] = false;
            return false;
        }

        if ($expectedRoleKey === 'ORED') {
            $signDTMIStmt = $pdo->prepare(
                'SELECT TOP (1) al.created_at
                 FROM activity_logs al
                 INNER JOIN users u ON u.id = al.user_id
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE al.document_id = :document_id
                   AND LOWER(COALESCE(al.action_type, \'\')) = \'signed\'
                   AND UPPER(COALESCE(r.name, \'\')) IN (\'ORED\', \'ORED_SIGN\')
                   AND u.office_id = :office_id
                 ORDER BY al.created_at DESC, al.id DESC'
            );
            $signDTMIStmt->execute([
                'document_id' => $documentId,
                'office_id' => $officeId,
            ]);
        } else {
            $signDTMIStmt = $pdo->prepare(
                'SELECT TOP (1) al.created_at
                 FROM activity_logs al
                 INNER JOIN users u ON u.id = al.user_id
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE al.document_id = :document_id
                   AND LOWER(COALESCE(al.action_type, \'\')) = \'signed\'
                   AND UPPER(COALESCE(r.name, \'\')) = :role_name
                   AND u.office_id = :office_id
                 ORDER BY al.created_at DESC, al.id DESC'
            );
            $signDTMIStmt->execute([
                'document_id' => $documentId,
                'role_name' => $expectedRoleKey,
                'office_id' => $officeId,
            ]);
        }
        $signedAtRaw = (string)($signDTMIStmt->fetchColumn() ?: '');
        if ($signedAtRaw === '') {
            $resultCache[$cacheKey] = false;
            return false;
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
        $latestReceiveRaw = (string)($latestReceiveStmt->fetchColumn() ?: '');
        if ($latestReceiveRaw === '') {
            $resultCache[$cacheKey] = true;
            return true;
        }

        $signedAtTs = strtotime($signedAtRaw);
        $latestReceiveTs = strtotime($latestReceiveRaw);
        if ($signedAtTs === false || $latestReceiveTs === false) {
            $resultCache[$cacheKey] = true;
            return true;
        }

        $resultCache[$cacheKey] = $signedAtTs >= $latestReceiveTs;
        return $resultCache[$cacheKey];
    }
}

if (!function_exists('dashboard_format_datetime_label')) {
    function dashboard_format_datetime_label(?string $value): string
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

if (!function_exists('dashboard_relative_time_label')) {
    function dashboard_relative_time_label(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return 'Now';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return 'Now';
        }

        $diff = time() - $ts;
        if ($diff < 0) {
            $diff = 0;
        }
        if ($diff < 60) {
            return 'Now';
        }
        if ($diff < 3600) {
            $mins = (int)floor($diff / 60);
            return $mins . 'm ago';
        }
        if ($diff < 86400) {
            $hours = (int)floor($diff / 3600);
            return $hours . 'h ago';
        }
        $days = (int)floor($diff / 86400);
        return $days . 'd ago';
    }
}

if (!function_exists('dashboard_notification_indicator_label')) {
    function dashboard_notification_indicator_label(string $indicatorKey): string
    {
        $normalized = strtolower(trim($indicatorKey));
        if ($normalized === 'yellow') {
            return 'Yellow - Urgent';
        }
        if ($normalized === 'blue') {
            return 'Blue - Complex/Highly Technical';
        }
        if ($normalized === 'green') {
            return 'Green - Released';
        }
        if ($normalized === 'neutral') {
            return 'Indicator';
        }
        return 'Pink - Simple';
    }
}

if (!function_exists('dashboard_notification_indicator_key')) {
    function dashboard_notification_indicator_key(string $actionType, string $remarks = '', string $classificationRemarks = ''): string
    {
        $actionRaw = strtoupper(trim($actionType));
        if (in_array($actionRaw, ['RELEASED', 'RELEASE'], true)) {
            return 'green';
        }

        $combinedRemarks = trim($classificationRemarks . ' | ' . $remarks);
        $classificationText = '';
        if ($combinedRemarks !== '' && preg_match('/color\s*classification\s*:\s*([^|\r\n]+)/i', $combinedRemarks, $matches) === 1) {
            $classificationText = strtolower(trim((string)($matches[1] ?? '')));
        }

        $normalizedCombined = strtolower($combinedRemarks);
        if (
            str_contains($classificationText, 'yellow')
            || str_contains($classificationText, 'urgent')
            || str_contains($normalizedCombined, 'urgent')
            || str_contains($normalizedCombined, 'asap')
            || str_contains($normalizedCombined, 'immediate')
            || str_contains($normalizedCombined, 'overdue')
        ) {
            return 'yellow';
        }

        if (
            str_contains($classificationText, 'blue')
            || str_contains($classificationText, 'complex')
            || str_contains($classificationText, 'highly technical')
            || str_contains($normalizedCombined, 'complex')
            || str_contains($normalizedCombined, 'highly technical')
        ) {
            return 'blue';
        }

        if (
            str_contains($classificationText, 'green')
            || str_contains($classificationText, 'released')
            || str_contains($normalizedCombined, 'released')
        ) {
            return 'green';
        }

        if (
            str_contains($classificationText, 'pink')
            || str_contains($classificationText, 'simple')
            || str_contains($normalizedCombined, 'simple')
        ) {
            return 'pink';
        }

        return 'pink';
    }
}

if (!function_exists('dashboard_format_time_remaining')) {
    function dashboard_format_time_remaining(?string $deadlineAt, ?string $status = null): string
    {
        if ($deadlineAt === null || trim($deadlineAt) === '') {
            return '-';
        }

        $statusLabel = strtolower(trim((string)($status ?? '')));
        if (in_array($statusLabel, ['completed', 'released', 'closed', 'approved', 'resolved', 'done', 'signed', 'signed/completed', 'cancelled', 'canceled'], true)) {
            return 'Completed';
        }

        $deadlineTs = strtotime($deadlineAt);
        if ($deadlineTs === false) {
            return '-';
        }

        $seconds = $deadlineTs - time();
        if ($seconds >= 0) {
            $days = (int)floor($seconds / 86400);
            $hours = (int)floor(($seconds % 86400) / 3600);
            if ($days > 0) {
                return $days . 'd ' . $hours . 'h';
            }
            $minutes = (int)ceil(($seconds % 3600) / 60);
            return max($hours, 0) . 'h ' . max($minutes, 0) . 'm';
        }

        $overdueSeconds = abs($seconds);
        $days = (int)floor($overdueSeconds / 86400);
        $hours = (int)floor(($overdueSeconds % 86400) / 3600);
        if ($days > 0) {
            return 'Overdue by ' . $days . 'd';
        }
        return 'Overdue by ' . max($hours, 1) . 'h';
    }
}

if (!function_exists('dashboard_deadline_day_diff')) {
    function dashboard_deadline_day_diff(?string $deadlineAt): ?int
    {
        if ($deadlineAt === null || trim($deadlineAt) === '') {
            return null;
        }

        try {
            $deadlineDate = (new DateTimeImmutable($deadlineAt))->setTime(0, 0, 0);
            $todayDate = (new DateTimeImmutable('today'))->setTime(0, 0, 0);
            $interval = $todayDate->diff($deadlineDate);
            $days = (int)$interval->days;
            return $interval->invert === 1 ? -$days : $days;
        } catch (Throwable $exception) {
            return null;
        }
    }
}

if (!function_exists('dashboard_deadline_bucket')) {
    function dashboard_deadline_bucket(?string $deadlineAt, ?string $status = null): string
    {
        $statusLabel = strtolower(trim((string)($status ?? '')));
        if (in_array($statusLabel, ['completed', 'released', 'closed', 'approved', 'resolved', 'done', 'signed', 'signed/completed', 'cancelled', 'canceled'], true)) {
            return 'completed';
        }

        $dayDiff = dashboard_deadline_day_diff($deadlineAt);
        if ($dayDiff === null) {
            return '';
        }
        if ($dayDiff < 0) {
            return 'overdue';
        }
        if ($dayDiff === 0) {
            return 'due_today';
        }
        if ($dayDiff === 1) {
            return 'due_soon';
        }
        return 'on_track';
    }
}

if (!function_exists('dashboard_build_office_scope')) {
    function dashboard_build_office_scope(int $officeId, bool $hasPendingOfficeColumn): array
    {
        if ($hasPendingOfficeColumn) {
            return [
                // Show office queue plus documents originated by this office for live tracking:
                // - incoming queue (pending_office_id = office)
                // - currently held by this office (including routed-out pending handoff)
                // - originated by this office (read-only unless in custody)
                'where' => '(
                    d.pending_office_id = :office_id_pending
                    OR d.current_office_id = :office_id_current_a
                    OR d.originating_office_id = :office_id_origin
                )',
                'params' => [
                    'office_id_current_a' => $officeId,
                    'office_id_pending' => $officeId,
                    'office_id_origin' => $officeId,
                ],
            ];
        }

        return [
            'where' => '(d.current_office_id = :office_id_current OR d.originating_office_id = :office_id_origin)',
            'params' => [
                'office_id_current' => $officeId,
                'office_id_origin' => $officeId,
            ],
        ];
    }
}

if (!function_exists('dashboard_fetch_queue_rows')) {
    function dashboard_fetch_queue_rows(
        PDO $pdo,
        int $officeId,
        int $limit = 8,
        ?array $dateRange = null,
        ?int $viewerUserId = null,
        ?string $viewerRoleKey = null
    ): array
    {
        if ($officeId <= 0) {
            return [];
        }

        $safeLimit = max(1, min($limit, 20));
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $scope = dashboard_build_office_scope($officeId, $hasPendingOfficeColumn);
        $assignmentFilter = dashboard_build_regional_ored_assignment_filter(
            $pdo,
            $viewerUserId,
            $viewerRoleKey,
            $officeId
        );
        if ($assignmentFilter['sql'] !== '') {
            $scope['where'] .= $assignmentFilter['sql'];
            $scope['params'] = array_merge($scope['params'], $assignmentFilter['params']);
        }
        $pendingOfficeSelect = $hasPendingOfficeColumn
            ? 'd.pending_office_id AS pending_office_id,'
            : 'NULL AS pending_office_id,';
        $dateClause = dashboard_build_date_range_clause('COALESCE(office_ts.office_received_at, d.created_at)', $dateRange, 'queue_rows_date');

                $sql = 'SELECT
                    d.id,
                    ' . dashboard_documents_row_version_expr($pdo) . ' AS row_version,
                    d.tracking_id,
                    d.subject,
                    d.status,
                    d.source_type,
                    ' . dashboard_documents_sender_expr($pdo) . ' AS sender,
                    d.created_by_user_id,
                    d.originating_office_id,
                    d.current_office_id,
                    d.created_at,
                    ' . $pendingOfficeSelect . '
                    dt.name AS document_type,
                    ' . dashboard_documents_arta_category_expr($pdo) . ' AS arta_category,
                    ' . dashboard_sql_top_one_subquery(
                        $pdo,
                        'al_ret.remarks',
                        'FROM activity_logs al_ret',
                        "WHERE al_ret.document_id = d.id
                          AND (
                              LOWER(COALESCE(al_ret.action_type, '')) LIKE 'return%'
                              OR LOWER(COALESCE(al_ret.action_type, '')) = 'returned'
                          )
                          AND TRIM(COALESCE(al_ret.remarks, '')) <> ''",
                        'al_ret.id DESC',
                        'return_remarks'
                    ) . ',
                    ' . dashboard_sql_top_one_subquery(
                        $pdo,
                        'al_last.remarks',
                        'FROM activity_logs al_last',
                        "WHERE al_last.document_id = d.id
                          AND TRIM(COALESCE(al_last.remarks, '')) <> ''",
                        'al_last.id DESC',
                        'last_remarks'
                    ) . ',
                    ' . dashboard_sql_custom_others_remarks_subquery($pdo) . ',
                    COALESCE(office_ts.office_received_at, d.created_at) AS queue_received_at,
                    ' . dashboard_sql_add_days_expr('d.created_at', dashboard_documents_arta_days_expr($pdo)) . ' AS deadline_at,
                    COALESCE(o_current.name, \'-\') AS current_holder,
                    ' . dashboard_documents_origin_expr($pdo) . ' AS origin_office,
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM activity_logs al_signed
                        WHERE al_signed.document_id = d.id
                          AND LOWER(COALESCE(al_signed.action_type, '')) = 'signed'
                    ",
                        'has_signed_action'
                    ) . ',
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, '')) IN (" . dashboard_section_like_level_sql_list() . ")
                    ",
                        'has_section_receive'
                    ) . ',
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM activity_logs al_ret
                        WHERE al_ret.document_id = d.id
                          AND LOWER(COALESCE(al_ret.action_type, '')) IN ('return', 'returned')
                    ",
                        'has_return_action'
                    ) . '
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN offices o_current ON o_current.id = d.current_office_id
                LEFT JOIN offices o_origin ON o_origin.id = d.originating_office_id
                LEFT JOIN (
                    SELECT ts.document_id, MAX(ts.date_time_received) AS office_received_at
                    FROM tracking_slips ts
                    WHERE ts.receiving_office_id = :office_id_custody
                    GROUP BY ts.document_id
                ) office_ts ON office_ts.document_id = d.id
                WHERE ' . $scope['where'] . '
                ' . $dateClause['sql'] . '
                ORDER BY
                    CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) = \'overdue\' THEN 3
                        WHEN LOWER(COALESCE(d.status, \'\')) IN (\'due soon\', \'at-risk\', \'at risk\') THEN 2
                        ELSE 1
                    END DESC,
                    queue_received_at DESC,
                    d.id DESC' . dashboard_sql_limit_suffix($safeLimit);

        $stmt = $pdo->prepare($sql);
        $params = $scope['params'];
        $params['office_id_custody'] = $officeId;
        $params = array_merge($params, $dateClause['params']);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return [];
        }

        $contextOfficeId = (int)($params['office_id_custody']
            ?? $params['office_id_current']
            ?? $params['office_id_current_action']
            ?? $params['office_id_origin']
            ?? 0);

        $mapped = [];
        foreach ($rows as $row) {
            $trackingId = trim((string)($row['tracking_id'] ?? ''));
            if ($trackingId === '') {
                continue;
            }

            $subject = trim((string)($row['subject'] ?? ''));
            if ($subject === '') {
                $subject = 'No subject';
            }

            $status = workflow_resolve_display_status(
                trim((string)($row['status'] ?? 'Pending')),
                (int)($row['pending_office_id'] ?? 0),
                (int)($row['current_office_id'] ?? 0)
            );

            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized'));
            if ($documentType === '') {
                $documentType = 'Uncategorized';
            }

            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $returnRemarks = trim((string)($row['return_remarks'] ?? ''));
            $lastRemarks = trim((string)($row['last_remarks'] ?? ''));
            $resolvedRemarksRaw = $returnRemarks !== ''
                ? $returnRemarks
                : $lastRemarks;
            $documentTypeRemarks = trim((string)($row['custom_others_remarks'] ?? ''));
            if ($documentTypeRemarks === '') {
                $documentTypeRemarks = $resolvedRemarksRaw;
            }
            $documentTypeLabel = workflow_format_document_type_label($documentType, $artaCategory, $documentTypeRemarks);
            $resolvedRemarks = workflow_strip_custom_others_document_type_from_remarks($resolvedRemarksRaw);
            $documentId = (int)($row['id'] ?? 0);
            $effectiveHasSignedAction = (int)($row['has_signed_action'] ?? 0) > 0 ? 1 : 0;
            $localSignedAction = dashboard_queue_local_signed_action($pdo, $documentId, $contextOfficeId);
            if ($localSignedAction !== null) {
                $effectiveHasSignedAction = $localSignedAction ? 1 : 0;
            }

            $mapped[] = [
                'document_id' => $documentId,
                'row_version' => max(1, (int)($row['row_version'] ?? 1)),
                'tracking_id' => $trackingId,
                'subject' => $subject,
                'status' => $status,
                'status_raw' => trim((string)($row['status'] ?? '')) !== '' ? (string)$row['status'] : 'Pending',
                'sender' => trim((string)($row['sender'] ?? '')) !== '' ? (string)$row['sender'] : '-',
                'source_type' => strtoupper(trim((string)($row['source_type'] ?? 'INTERNAL'))),
                'created_by_user_id' => (int)($row['created_by_user_id'] ?? 0),
                'origin_office_id' => (int)($row['originating_office_id'] ?? 0),
                'current_office_id' => (int)($row['current_office_id'] ?? 0),
                'pending_office_id' => (int)($row['pending_office_id'] ?? 0),
                'has_signed_action' => $effectiveHasSignedAction,
                'document_type' => $documentTypeLabel,
                'arta_category' => $artaCategory !== '' ? $artaCategory : 'Simple',
                'last_remarks' => $resolvedRemarks,
                'return_remarks' => $returnRemarks,
                'current_holder' => trim((string)($row['current_holder'] ?? '-')),
                'origin_office' => trim((string)($row['origin_office'] ?? '-')),
                'has_section_receive' => (int)($row['has_section_receive'] ?? 0) > 0 ? 1 : 0,
                'has_return_action' => (int)($row['has_return_action'] ?? 0) > 0 ? 1 : 0,
                'date_received' => dashboard_format_datetime_label((string)($row['queue_received_at'] ?? null)),
                'date_received_raw' => (string)($row['queue_received_at'] ?? ''),
                'date_created' => dashboard_format_datetime_label((string)($row['created_at'] ?? null)),
                'date_created_raw' => (string)($row['created_at'] ?? ''),
                'deadline_at_raw' => (string)($row['deadline_at'] ?? ''),
                'deadline_bucket' => dashboard_deadline_bucket((string)($row['deadline_at'] ?? null), $status),
                'time_remaining' => dashboard_format_time_remaining((string)($row['deadline_at'] ?? null), $status),
            ];
        }

        return $mapped;
    }
}

if (!function_exists('dashboard_fetch_archive_rows')) {
    function dashboard_fetch_archive_rows(PDO $pdo, int $officeId, int $limit = 50, ?array $dateRange = null): array
    {
        if ($officeId <= 0) {
            return [];
        }

        $safeLimit = max(5, min($limit, 100));
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $scope = dashboard_build_office_scope($officeId, $hasPendingOfficeColumn);
        $pendingOfficeSelect = $hasPendingOfficeColumn
            ? 'd.pending_office_id AS pending_office_id,'
            : 'NULL AS pending_office_id,';
        $dateClause = dashboard_build_date_range_clause('COALESCE(office_ts.office_received_at, d.created_at)', $dateRange, 'archive_rows_date');
        $terminalStatuses = "'completed', 'released', 'closed', 'approved', 'resolved', 'done', 'signed', 'signed/completed', 'cancelled', 'canceled'";

        $sql = 'SELECT
                    d.id,
                    ' . dashboard_documents_row_version_expr($pdo) . ' AS row_version,
                    d.tracking_id,
                    d.subject,
                    d.status,
                    d.source_type,
                    ' . dashboard_documents_sender_expr($pdo) . ' AS sender,
                    d.created_by_user_id,
                    d.originating_office_id,
                    d.current_office_id,
                    d.created_at,
                    ' . $pendingOfficeSelect . '
                    dt.name AS document_type,
                    ' . dashboard_documents_arta_category_expr($pdo) . ' AS arta_category,
                    ' . dashboard_sql_top_one_subquery(
                        $pdo,
                        'al_ret.remarks',
                        'FROM activity_logs al_ret',
                        "WHERE al_ret.document_id = d.id
                          AND (
                              LOWER(COALESCE(al_ret.action_type, '')) LIKE 'return%'
                              OR LOWER(COALESCE(al_ret.action_type, '')) = 'returned'
                          )
                          AND TRIM(COALESCE(al_ret.remarks, '')) <> ''",
                        'al_ret.id DESC',
                        'return_remarks'
                    ) . ',
                    ' . dashboard_sql_top_one_subquery(
                        $pdo,
                        'al_last.remarks',
                        'FROM activity_logs al_last',
                        "WHERE al_last.document_id = d.id
                          AND TRIM(COALESCE(al_last.remarks, '')) <> ''",
                        'al_last.id DESC',
                        'last_remarks'
                    ) . ',
                    ' . dashboard_sql_custom_others_remarks_subquery($pdo) . ',
                    COALESCE(office_ts.office_received_at, d.created_at) AS queue_received_at,
                    ' . dashboard_sql_add_days_expr('d.created_at', dashboard_documents_arta_days_expr($pdo)) . ' AS deadline_at,
                    COALESCE(o_current.name, \'-\') AS current_holder,
                    ' . dashboard_documents_origin_expr($pdo) . ' AS origin_office,
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM activity_logs al_signed
                        WHERE al_signed.document_id = d.id
                          AND LOWER(COALESCE(al_signed.action_type, '')) = 'signed'
                    ",
                        'has_signed_action'
                    ) . ',
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, '')) IN (" . dashboard_section_like_level_sql_list() . ")
                    ",
                        'has_section_receive'
                    ) . ',
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM activity_logs al_ret
                        WHERE al_ret.document_id = d.id
                          AND LOWER(COALESCE(al_ret.action_type, '')) IN ('return', 'returned')
                    ",
                        'has_return_action'
                    ) . '
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN offices o_current ON o_current.id = d.current_office_id
                LEFT JOIN offices o_origin ON o_origin.id = d.originating_office_id
                LEFT JOIN (
                    SELECT ts.document_id, MAX(ts.date_time_received) AS office_received_at
                    FROM tracking_slips ts
                    WHERE ts.receiving_office_id = :office_id_custody
                    GROUP BY ts.document_id
                ) office_ts ON office_ts.document_id = d.id
                WHERE ' . $scope['where'] . '
                  AND LOWER(COALESCE(d.status, \'\')) IN (' . $terminalStatuses . ')
                ' . $dateClause['sql'] . '
                ORDER BY
                    queue_received_at DESC,
                    d.id DESC' . dashboard_sql_limit_suffix($safeLimit);

        $stmt = $pdo->prepare($sql);
        $params = $scope['params'];
        $params['office_id_custody'] = $officeId;
        $params = array_merge($params, $dateClause['params']);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return [];
        }

        $contextOfficeId = (int)($params['office_id_custody']
            ?? $params['office_id_current']
            ?? $params['office_id_current_action']
            ?? $params['office_id_origin']
            ?? 0);

        $mapped = [];
        foreach ($rows as $row) {
            $trackingId = trim((string)($row['tracking_id'] ?? ''));
            if ($trackingId === '') {
                continue;
            }

            $subject = trim((string)($row['subject'] ?? ''));
            if ($subject === '') {
                $subject = 'No subject';
            }

            $status = workflow_resolve_display_status(
                trim((string)($row['status'] ?? 'Pending')),
                (int)($row['pending_office_id'] ?? 0),
                (int)($row['current_office_id'] ?? 0)
            );

            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized'));
            if ($documentType === '') {
                $documentType = 'Uncategorized';
            }

            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $returnRemarks = trim((string)($row['return_remarks'] ?? ''));
            $lastRemarks = trim((string)($row['last_remarks'] ?? ''));
            $resolvedRemarksRaw = $returnRemarks !== ''
                ? $returnRemarks
                : $lastRemarks;
            $documentTypeRemarks = trim((string)($row['custom_others_remarks'] ?? ''));
            if ($documentTypeRemarks === '') {
                $documentTypeRemarks = $resolvedRemarksRaw;
            }
            $documentTypeLabel = workflow_format_document_type_label($documentType, $artaCategory, $documentTypeRemarks);
            $resolvedRemarks = workflow_strip_custom_others_document_type_from_remarks($resolvedRemarksRaw);
            $documentId = (int)($row['id'] ?? 0);
            $effectiveHasSignedAction = (int)($row['has_signed_action'] ?? 0) > 0 ? 1 : 0;
            $localSignedAction = dashboard_queue_local_signed_action($pdo, $documentId, $contextOfficeId);
            if ($localSignedAction !== null) {
                $effectiveHasSignedAction = $localSignedAction ? 1 : 0;
            }

            $mapped[] = [
                'document_id' => $documentId,
                'row_version' => max(1, (int)($row['row_version'] ?? 1)),
                'tracking_id' => $trackingId,
                'subject' => $subject,
                'status' => $status,
                'status_raw' => trim((string)($row['status'] ?? '')) !== '' ? (string)$row['status'] : 'Pending',
                'sender' => trim((string)($row['sender'] ?? '')) !== '' ? (string)$row['sender'] : '-',
                'source_type' => strtoupper(trim((string)($row['source_type'] ?? 'INTERNAL'))),
                'created_by_user_id' => (int)($row['created_by_user_id'] ?? 0),
                'origin_office_id' => (int)($row['originating_office_id'] ?? 0),
                'current_office_id' => (int)($row['current_office_id'] ?? 0),
                'pending_office_id' => (int)($row['pending_office_id'] ?? 0),
                'has_signed_action' => $effectiveHasSignedAction,
                'document_type' => $documentTypeLabel,
                'arta_category' => $artaCategory !== '' ? $artaCategory : 'Simple',
                'last_remarks' => $resolvedRemarks,
                'return_remarks' => $returnRemarks,
                'current_holder' => trim((string)($row['current_holder'] ?? '-')),
                'origin_office' => trim((string)($row['origin_office'] ?? '-')),
                'has_section_receive' => (int)($row['has_section_receive'] ?? 0) > 0 ? 1 : 0,
                'has_return_action' => (int)($row['has_return_action'] ?? 0) > 0 ? 1 : 0,
                'date_received' => dashboard_format_datetime_label((string)($row['queue_received_at'] ?? null)),
                'date_received_raw' => (string)($row['queue_received_at'] ?? ''),
                'date_created' => dashboard_format_datetime_label((string)($row['created_at'] ?? null)),
                'date_created_raw' => (string)($row['created_at'] ?? ''),
                'deadline_at_raw' => (string)($row['deadline_at'] ?? ''),
                'deadline_bucket' => dashboard_deadline_bucket((string)($row['deadline_at'] ?? null), $status),
                'time_remaining' => dashboard_format_time_remaining((string)($row['deadline_at'] ?? null), $status),
            ];
        }

        return $mapped;
    }
}

if (!function_exists('dashboard_fetch_origin_tracker_rows')) {
    function dashboard_fetch_origin_tracker_rows(PDO $pdo, int $originOfficeId, int $limit = 50, ?array $dateRange = null): array
    {
        if ($originOfficeId <= 0) {
            return [];
        }

        $safeLimit = max(5, min($limit, 200));
        $dateClause = dashboard_build_date_range_clause('COALESCE(last_actions.last_action_at, d.created_at)', $dateRange, 'origin_tracker_date');
        $returnRemarksSql = dashboard_sql_top_one_subquery(
            $pdo,
            'al_ret.remarks',
            'FROM activity_logs al_ret',
            "WHERE al_ret.document_id = d.id
              AND (
                  LOWER(COALESCE(al_ret.action_type, '')) LIKE 'return%'
                  OR LOWER(COALESCE(al_ret.action_type, '')) = 'returned'
              )
              AND TRIM(COALESCE(al_ret.remarks, '')) <> ''",
            'al_ret.id DESC',
            'return_remarks'
        );
        $lastRemarksSql = dashboard_sql_top_one_subquery(
            $pdo,
            'al_last.remarks',
            'FROM activity_logs al_last',
            "WHERE al_last.document_id = d.id
              AND TRIM(COALESCE(al_last.remarks, '')) <> ''",
            'al_last.id DESC',
            'last_remarks'
        );
        $customOthersRemarksSql = dashboard_sql_custom_others_remarks_subquery($pdo);
        $sql = 'SELECT
                    d.id,
                    ' . dashboard_documents_row_version_expr($pdo) . ' AS row_version,
                    d.tracking_id,
                    d.subject,
                    d.status,
                    d.source_type,
                    ' . dashboard_documents_sender_expr($pdo) . ' AS sender,
                    d.created_by_user_id,
                    d.originating_office_id,
                    d.current_office_id,
                    d.pending_office_id,
                    d.created_at,
                    dt.name AS document_type,
                    ' . dashboard_documents_arta_category_expr($pdo) . ' AS arta_category,
                    COALESCE(o_current.name, \'-\') AS current_holder_office,
                    COALESCE(o_pending.name, \'\') AS pending_holder_office,
                    COALESCE(last_actions.last_action_at, d.created_at) AS last_move_at,
                    ' . $returnRemarksSql . ',
                    ' . $lastRemarksSql . ',
                    ' . $customOthersRemarksSql . ',
                    ' . dashboard_sql_add_days_expr('COALESCE(last_receives.last_received_at, d.created_at)', dashboard_documents_arta_days_expr($pdo)) . ' AS deadline_at,
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, '')) IN (" . dashboard_section_like_level_sql_list() . ")
                    ",
                        'has_section_receive'
                    ) . ',
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM activity_logs al_ret
                        WHERE al_ret.document_id = d.id
                          AND LOWER(COALESCE(al_ret.action_type, '')) IN ('return', 'returned')
                    ",
                        'has_return_action'
                    ) . '
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN offices o_current ON o_current.id = d.current_office_id
                LEFT JOIN offices o_pending ON o_pending.id = d.pending_office_id
                LEFT JOIN offices o_origin ON o_origin.id = d.originating_office_id
                LEFT JOIN (
                    SELECT document_id, MAX(created_at) AS last_action_at
                    FROM activity_logs
                    GROUP BY document_id
                ) last_actions ON last_actions.document_id = d.id
                LEFT JOIN (
                    SELECT document_id, MAX(date_time_received) AS last_received_at
                    FROM tracking_slips
                    GROUP BY document_id
                ) last_receives ON last_receives.document_id = d.id
                WHERE d.originating_office_id = :originating_office_id
                ' . $dateClause['sql'] . '
                ORDER BY last_move_at DESC, d.id DESC' . dashboard_sql_limit_suffix($safeLimit);

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([
            'originating_office_id' => $originOfficeId,
        ], $dateClause['params']));
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return [];
        }

        $mapped = [];
        foreach ($rows as $row) {
            $trackingId = trim((string)($row['tracking_id'] ?? ''));
            if ($trackingId === '') {
                continue;
            }

            $subject = trim((string)($row['subject'] ?? ''));
            if ($subject === '') {
                $subject = 'No subject';
            }

            $status = workflow_resolve_display_status(
                trim((string)($row['status'] ?? 'Pending')),
                (int)($row['pending_office_id'] ?? 0),
                (int)($row['current_office_id'] ?? 0)
            );

            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized'));
            if ($documentType === '') {
                $documentType = 'Uncategorized';
            }

            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $currentHolder = trim((string)($row['pending_holder_office'] ?? ''));
            if ($currentHolder === '') {
                $currentHolder = trim((string)($row['current_holder_office'] ?? '-'));
            }
            if ($currentHolder === '') {
                $currentHolder = '-';
            }

            $returnRemarks = trim((string)($row['return_remarks'] ?? ''));
            $lastRemarks = trim((string)($row['last_remarks'] ?? ''));
            $resolvedRemarksRaw = $returnRemarks !== ''
                ? $returnRemarks
                : $lastRemarks;
            $documentTypeRemarks = trim((string)($row['custom_others_remarks'] ?? ''));
            if ($documentTypeRemarks === '') {
                $documentTypeRemarks = $resolvedRemarksRaw;
            }
            $documentTypeLabel = workflow_format_document_type_label($documentType, $artaCategory, $documentTypeRemarks);
            $resolvedRemarks = workflow_strip_custom_others_document_type_from_remarks($resolvedRemarksRaw);

            $mapped[] = [
                'document_id' => (int)($row['id'] ?? 0),
                'row_version' => max(1, (int)($row['row_version'] ?? 1)),
                'tracking_id' => $trackingId,
                'subject' => $subject,
                'status' => $status,
                'status_raw' => trim((string)($row['status'] ?? '')) !== '' ? (string)$row['status'] : 'Pending',
                'sender' => trim((string)($row['sender'] ?? '')) !== '' ? (string)$row['sender'] : '-',
                'source_type' => strtoupper(trim((string)($row['source_type'] ?? 'INTERNAL'))),
                'created_by_user_id' => (int)($row['created_by_user_id'] ?? 0),
                'origin_office_id' => (int)($row['originating_office_id'] ?? 0),
                'current_office_id' => (int)($row['current_office_id'] ?? 0),
                'pending_office_id' => (int)($row['pending_office_id'] ?? 0),
                'document_type' => $documentTypeLabel,
                'arta_category' => $artaCategory !== '' ? $artaCategory : 'Simple',
                'last_remarks' => $resolvedRemarks,
                'return_remarks' => $returnRemarks,
                'current_holder' => $currentHolder,
                'origin_office' => '',
                'has_section_receive' => (int)($row['has_section_receive'] ?? 0) > 0 ? 1 : 0,
                'has_return_action' => (int)($row['has_return_action'] ?? 0) > 0 ? 1 : 0,
                'date_received' => dashboard_format_datetime_label((string)($row['last_move_at'] ?? null)),
                'date_received_raw' => (string)($row['last_move_at'] ?? ''),
                'date_created' => dashboard_format_datetime_label((string)($row['created_at'] ?? null)),
                'date_created_raw' => (string)($row['created_at'] ?? ''),
                'deadline_at_raw' => (string)($row['deadline_at'] ?? ''),
                'deadline_bucket' => dashboard_deadline_bucket((string)($row['deadline_at'] ?? null), $status),
                'time_remaining' => dashboard_format_time_remaining((string)($row['deadline_at'] ?? null), $status),
            ];
        }

        return $mapped;
    }
}

if (!function_exists('dashboard_fetch_actor_tracker_rows')) {
    function dashboard_fetch_actor_tracker_rows(PDO $pdo, int $actorUserId, array $actionTypes = ['Approved', 'Signed'], int $limit = 50, ?array $dateRange = null): array
    {
        if ($actorUserId <= 0) {
            return [];
        }

        $normalizedTypes = [];
        foreach ($actionTypes as $type) {
            $value = trim((string)$type);
            if ($value === '') {
                continue;
            }
            $value = preg_replace('/\s+/', ' ', $value);
            if ($value === null || $value === '') {
                continue;
            }
            $normalizedTypes[] = ucfirst(strtolower($value));
        }
        $normalizedTypes = array_values(array_unique($normalizedTypes));
        if (empty($normalizedTypes)) {
            $normalizedTypes = ['Approved', 'Signed'];
        }

        $quotedTypes = array_map(static fn(string $value): string => "'" . str_replace("'", "''", $value) . "'", $normalizedTypes);
        $inListSql = implode(', ', $quotedTypes);

        $safeLimit = max(5, min($limit, 200));
        $dateClause = dashboard_build_date_range_clause('actor_event.created_at', $dateRange, 'actor_tracker_date');
        $returnRemarksSql = dashboard_sql_top_one_subquery(
            $pdo,
            'al_ret.remarks',
            'FROM activity_logs al_ret',
            "WHERE al_ret.document_id = d.id
              AND (
                  LOWER(COALESCE(al_ret.action_type, '')) LIKE 'return%'
                  OR LOWER(COALESCE(al_ret.action_type, '')) = 'returned'
              )
              AND TRIM(COALESCE(al_ret.remarks, '')) <> ''",
            'al_ret.id DESC',
            'return_remarks'
        );
        $lastRemarksSql = dashboard_sql_top_one_subquery(
            $pdo,
            'al_last.remarks',
            'FROM activity_logs al_last',
            "WHERE al_last.document_id = d.id
              AND TRIM(COALESCE(al_last.remarks, '')) <> ''",
            'al_last.id DESC',
            'last_remarks'
        );
        $customOthersRemarksSql = dashboard_sql_custom_others_remarks_subquery($pdo);
        $sql = 'SELECT
                    d.id,
                    ' . dashboard_documents_row_version_expr($pdo) . ' AS row_version,
                    d.tracking_id,
                    d.subject,
                    d.status,
                    d.source_type,
                    d.created_by_user_id,
                    d.originating_office_id,
                    d.current_office_id,
                    d.pending_office_id,
                    d.created_at,
                    dt.name AS document_type,
                    ' . dashboard_documents_arta_category_expr($pdo) . ' AS arta_category,
                    ' . dashboard_documents_sender_expr($pdo) . ' AS sender,
                    ' . dashboard_documents_origin_expr($pdo) . ' AS origin_office,
                    COALESCE(o_current.name, \'-\') AS current_holder_office,
                    COALESCE(UPPER(o_current.level), \'\') AS current_holder_level,
                    COALESCE(o_pending.name, \'\') AS pending_holder_office,
                    COALESCE(UPPER(o_pending.level), \'\') AS pending_holder_level,
                    actor_event.created_at AS actor_action_at,
                    actor_event.action_type AS actor_action_type,
                    actor_event.remarks AS actor_action_remarks,
                    ' . $returnRemarksSql . ',
                    ' . $lastRemarksSql . ',
                    ' . $customOthersRemarksSql . ',
                    ' . dashboard_sql_add_days_expr('COALESCE(last_receives.last_received_at, d.created_at)', dashboard_documents_arta_days_expr($pdo)) . ' AS deadline_at,
                    CASE WHEN EXISTS(
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, \'\')) IN (' . dashboard_section_like_level_sql_list() . ')
                    ) THEN 1 ELSE 0 END AS has_section_receive,
                    CASE WHEN EXISTS(
                        SELECT 1
                        FROM activity_logs al_ret
                        WHERE al_ret.document_id = d.id
                          AND (
                              LOWER(COALESCE(al_ret.action_type, \'\')) LIKE \'return%\'
                              OR LOWER(COALESCE(al_ret.action_type, \'\')) = \'returned\'
                          )
                    ) THEN 1 ELSE 0 END AS has_return_action
                FROM documents d
                INNER JOIN (
                    SELECT al.document_id, MAX(al.id) AS latest_actor_event_id
                    FROM activity_logs al
                    WHERE al.user_id = :actor_user_id
                      AND al.action_type IN (' . $inListSql . ')
                    GROUP BY al.document_id
                ) actor_docs ON actor_docs.document_id = d.id
                INNER JOIN activity_logs actor_event ON actor_event.id = actor_docs.latest_actor_event_id
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN offices o_current ON o_current.id = d.current_office_id
                LEFT JOIN offices o_pending ON o_pending.id = d.pending_office_id
                LEFT JOIN offices o_origin ON o_origin.id = d.originating_office_id
                LEFT JOIN (
                    SELECT document_id, MAX(date_time_received) AS last_received_at
                    FROM tracking_slips
                    GROUP BY document_id
                ) last_receives ON last_receives.document_id = d.id
                WHERE 1 = 1
                ' . $dateClause['sql'] . '
                ORDER BY actor_event.created_at DESC, d.id DESC' . dashboard_sql_limit_suffix($safeLimit);

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([
            'actor_user_id' => $actorUserId,
        ], $dateClause['params']));
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return [];
        }

        $mapped = [];
        foreach ($rows as $row) {
            $trackingId = trim((string)($row['tracking_id'] ?? ''));
            if ($trackingId === '') {
                continue;
            }

            $subject = trim((string)($row['subject'] ?? ''));
            if ($subject === '') {
                $subject = 'No subject';
            }

            $status = workflow_resolve_display_status(
                trim((string)($row['status'] ?? 'Pending')),
                (int)($row['pending_office_id'] ?? 0),
                (int)($row['current_office_id'] ?? 0)
            );

            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized'));
            if ($documentType === '') {
                $documentType = 'Uncategorized';
            }

            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $currentHolder = trim((string)($row['pending_holder_office'] ?? ''));
            if ($currentHolder === '') {
                $currentHolder = trim((string)($row['current_holder_office'] ?? '-'));
            }
            if ($currentHolder === '') {
                $currentHolder = '-';
            }

            $actionType = trim((string)($row['actor_action_type'] ?? ''));
            $actionRemarks = trim((string)($row['actor_action_remarks'] ?? ''));
            $returnRemarks = trim((string)($row['return_remarks'] ?? ''));
            $lastRemarks = trim((string)($row['last_remarks'] ?? ''));
            $resolvedRemarksRaw = $returnRemarks !== ''
                ? $returnRemarks
                : ($lastRemarks !== '' ? $lastRemarks : $actionRemarks);
            $documentTypeRemarks = trim((string)($row['custom_others_remarks'] ?? ''));
            if ($documentTypeRemarks === '') {
                $documentTypeRemarks = $resolvedRemarksRaw;
            }
            $documentTypeLabel = workflow_format_document_type_label($documentType, $artaCategory, $documentTypeRemarks);
            $resolvedRemarks = workflow_strip_custom_others_document_type_from_remarks($resolvedRemarksRaw);

            $mapped[] = [
                'document_id' => (int)($row['id'] ?? 0),
                'row_version' => max(1, (int)($row['row_version'] ?? 1)),
                'tracking_id' => $trackingId,
                'subject' => $subject,
                'status' => $status,
                'status_raw' => trim((string)($row['status'] ?? '')) !== '' ? (string)$row['status'] : 'Pending',
                'source_type' => strtoupper(trim((string)($row['source_type'] ?? 'INTERNAL'))),
                'created_by_user_id' => (int)($row['created_by_user_id'] ?? 0),
                'origin_office_id' => (int)($row['originating_office_id'] ?? 0),
                'current_office_id' => (int)($row['current_office_id'] ?? 0),
                'pending_office_id' => (int)($row['pending_office_id'] ?? 0),
                'sender' => trim((string)($row['sender'] ?? '')) !== '' ? (string)$row['sender'] : '-',
                'document_type' => $documentTypeLabel,
                'arta_category' => $artaCategory !== '' ? $artaCategory : 'Simple',
                'last_remarks' => $resolvedRemarks,
                'return_remarks' => $returnRemarks,
                'current_holder' => $currentHolder,
                'current_office_level' => trim((string)($row['current_holder_level'] ?? '')),
                'pending_office_level' => trim((string)($row['pending_holder_level'] ?? '')),
                'origin_office' => trim((string)($row['origin_office'] ?? '-')),
                'has_section_receive' => (int)($row['has_section_receive'] ?? 0) > 0 ? 1 : 0,
                'has_return_action' => (int)($row['has_return_action'] ?? 0) > 0 ? 1 : 0,
                'date_received' => dashboard_format_datetime_label((string)($row['actor_action_at'] ?? null)),
                'date_received_raw' => (string)($row['actor_action_at'] ?? ''),
                'date_created' => dashboard_format_datetime_label((string)($row['created_at'] ?? null)),
                'date_created_raw' => (string)($row['created_at'] ?? ''),
                'deadline_at_raw' => (string)($row['deadline_at'] ?? ''),
                'deadline_bucket' => dashboard_deadline_bucket((string)($row['deadline_at'] ?? null), $status),
                'time_remaining' => dashboard_format_time_remaining((string)($row['deadline_at'] ?? null), $status),
                'actor_action_type' => $actionType,
                'actor_action_remarks' => $actionRemarks,
            ];
        }

        return $mapped;
    }
}

if (!function_exists('dashboard_fetch_division_staff_tracker_rows')) {
    function dashboard_fetch_division_staff_tracker_rows(PDO $pdo, int $divisionOfficeId, array $actionTypes = ['Approved', 'Forwarded', 'Returned', 'Rerouted'], int $limit = 100, ?array $dateRange = null): array
    {
        if ($divisionOfficeId <= 0) {
            return [];
        }

        $normalizedTypes = [];
        foreach ($actionTypes as $type) {
            $value = trim((string)$type);
            if ($value === '') {
                continue;
            }
            $value = preg_replace('/\s+/', ' ', $value);
            if ($value === null || $value === '') {
                continue;
            }
            $normalizedTypes[] = ucfirst(strtolower($value));
        }
        $normalizedTypes = array_values(array_unique($normalizedTypes));
        if (empty($normalizedTypes)) {
            $normalizedTypes = ['Approved', 'Forwarded', 'Returned', 'Rerouted'];
        }

        $safeLimit = max(5, min($limit, 200));
        $trackedOfficeIds = [$divisionOfficeId];

        $divisionLevelStmt = $pdo->prepare(
            "SELECT TOP (1) UPPER(COALESCE(level, '')) AS office_level
             FROM offices
             WHERE id = :division_office_id"
        );
        $divisionLevelStmt->execute(['division_office_id' => $divisionOfficeId]);
        $divisionOfficeLevel = strtoupper(trim((string)($divisionLevelStmt->fetchColumn() ?: '')));
        if ($divisionOfficeLevel === 'CENRO_SECTION') {
            $childOfficeLevel = 'CENRO_UNIT';
        } elseif ($divisionOfficeLevel === 'PENRO_DIVISION') {
            $childOfficeLevel = 'PENRO_SECTION';
        } elseif ($divisionOfficeLevel === 'PASU_OFFICER') {
            $childOfficeLevel = 'PAMO_UNIT';
        } else {
            $childOfficeLevel = 'SECTION';
        }

        $sectionStmt = $pdo->prepare(
            "SELECT id
             FROM offices
             WHERE UPPER(COALESCE(level, '')) = :child_office_level
               AND parent_office_id = :division_office_id
             ORDER BY id ASC"
        );
        $sectionStmt->execute([
            'child_office_level' => $childOfficeLevel,
            'division_office_id' => $divisionOfficeId,
        ]);
        $sectionRows = $sectionStmt->fetchAll() ?: [];
        foreach ($sectionRows as $sectionRow) {
            $sectionOfficeId = (int)($sectionRow['id'] ?? 0);
            if ($sectionOfficeId > 0) {
                $trackedOfficeIds[] = $sectionOfficeId;
            }
        }
        $trackedOfficeIds = array_values(array_unique(array_filter($trackedOfficeIds, static fn(int $id): bool => $id > 0)));
        if (empty($trackedOfficeIds)) {
            return [];
        }

        $officePlaceholders = [];
        $params = [];
        foreach ($trackedOfficeIds as $index => $trackedOfficeId) {
            $paramKey = 'office_' . $index;
            $officePlaceholders[] = ':' . $paramKey;
            $params[$paramKey] = $trackedOfficeId;
        }

        $quotedTypes = array_map(static fn(string $value): string => "'" . str_replace("'", "''", $value) . "'", $normalizedTypes);
        $inListSql = implode(', ', $quotedTypes);
        $officeInSql = implode(', ', $officePlaceholders);
        $dateClause = dashboard_build_date_range_clause('actor_event.created_at', $dateRange, 'division_tracker_date');

        $sql = 'SELECT
                    d.id,
                    ' . dashboard_documents_row_version_expr($pdo) . ' AS row_version,
                    d.tracking_id,
                    d.subject,
                    d.status,
                    d.source_type,
                    d.originating_office_id,
                    d.current_office_id,
                    d.pending_office_id,
                    d.created_at,
                    dt.name AS document_type,
                    ' . dashboard_documents_arta_category_expr($pdo) . ' AS arta_category,
                    COALESCE(o_current.name, \'-\') AS current_holder_office,
                    COALESCE(UPPER(o_current.level), \'\') AS current_holder_level,
                    COALESCE(o_pending.name, \'\') AS pending_holder_office,
                    COALESCE(UPPER(o_pending.level), \'\') AS pending_holder_level,
                    actor_event.created_at AS actor_action_at,
                    actor_event.action_type AS actor_action_type,
                    actor_event.remarks AS actor_action_remarks,
                    ' . dashboard_sql_custom_others_remarks_subquery($pdo) . ',
                    ' . dashboard_sql_add_days_expr('COALESCE(last_receives.last_received_at, d.created_at)', dashboard_documents_arta_days_expr($pdo)) . ' AS deadline_at,
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, '')) IN (" . dashboard_section_like_level_sql_list() . ")
                    ",
                        'has_section_receive'
                    ) . '
                FROM documents d
                INNER JOIN (
                    SELECT al.document_id, MAX(al.id) AS latest_team_event_id
                    FROM activity_logs al
                    INNER JOIN users u_actor ON u_actor.id = al.user_id
                    WHERE u_actor.office_id IN (' . $officeInSql . ')
                      AND al.action_type IN (' . $inListSql . ')
                    GROUP BY al.document_id
                ) actor_docs ON actor_docs.document_id = d.id
                INNER JOIN activity_logs actor_event ON actor_event.id = actor_docs.latest_team_event_id
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN offices o_current ON o_current.id = d.current_office_id
                LEFT JOIN offices o_pending ON o_pending.id = d.pending_office_id
                LEFT JOIN (
                    SELECT document_id, MAX(date_time_received) AS last_received_at
                    FROM tracking_slips
                    GROUP BY document_id
                ) last_receives ON last_receives.document_id = d.id
                WHERE 1 = 1
                ' . $dateClause['sql'] . '
                ORDER BY actor_event.created_at DESC, d.id DESC' . dashboard_sql_limit_suffix($safeLimit);

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, $dateClause['params']));
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return [];
        }

        $contextOfficeId = (int)($params['office_id_custody']
            ?? $params['office_id_current']
            ?? $params['office_id_current_action']
            ?? $params['office_id_origin']
            ?? 0);

        $mapped = [];
        foreach ($rows as $row) {
            $trackingId = trim((string)($row['tracking_id'] ?? ''));
            if ($trackingId === '') {
                continue;
            }

            $subject = trim((string)($row['subject'] ?? ''));
            if ($subject === '') {
                $subject = 'No subject';
            }

            $status = workflow_resolve_display_status(
                trim((string)($row['status'] ?? 'Pending')),
                (int)($row['pending_office_id'] ?? 0),
                (int)($row['current_office_id'] ?? 0)
            );

            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized'));
            if ($documentType === '') {
                $documentType = 'Uncategorized';
            }

            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $documentTypeRemarks = trim((string)($row['custom_others_remarks'] ?? ''));
            if ($documentTypeRemarks === '') {
                $documentTypeRemarks = trim((string)($row['last_remarks'] ?? ''));
            }
            if ($documentTypeRemarks === '') {
                $documentTypeRemarks = trim((string)($row['actor_action_remarks'] ?? ''));
            }
            $documentTypeLabel = workflow_format_document_type_label($documentType, $artaCategory, $documentTypeRemarks);

            $currentHolder = trim((string)($row['pending_holder_office'] ?? ''));
            if ($currentHolder === '') {
                $currentHolder = trim((string)($row['current_holder_office'] ?? '-'));
            }
            if ($currentHolder === '') {
                $currentHolder = '-';
            }

            $mapped[] = [
                'document_id' => (int)($row['id'] ?? 0),
                'row_version' => max(1, (int)($row['row_version'] ?? 1)),
                'tracking_id' => $trackingId,
                'subject' => $subject,
                'status' => $status,
                'status_raw' => trim((string)($row['status'] ?? '')) !== '' ? (string)$row['status'] : 'Pending',
                'source_type' => strtoupper(trim((string)($row['source_type'] ?? 'INTERNAL'))),
                'origin_office_id' => (int)($row['originating_office_id'] ?? 0),
                'current_office_id' => (int)($row['current_office_id'] ?? 0),
                'pending_office_id' => (int)($row['pending_office_id'] ?? 0),
                'document_type' => $documentTypeLabel,
                'arta_category' => $artaCategory !== '' ? $artaCategory : 'Simple',
                'current_holder' => $currentHolder,
                'current_office_level' => trim((string)($row['current_holder_level'] ?? '')),
                'pending_office_level' => trim((string)($row['pending_holder_level'] ?? '')),
                'origin_office' => '',
                'has_section_receive' => (int)($row['has_section_receive'] ?? 0) > 0 ? 1 : 0,
                'date_received' => dashboard_format_datetime_label((string)($row['actor_action_at'] ?? null)),
                'date_received_raw' => (string)($row['actor_action_at'] ?? ''),
                'date_created' => dashboard_format_datetime_label((string)($row['created_at'] ?? null)),
                'date_created_raw' => (string)($row['created_at'] ?? ''),
                'deadline_at_raw' => (string)($row['deadline_at'] ?? ''),
                'deadline_bucket' => dashboard_deadline_bucket((string)($row['deadline_at'] ?? null), $status),
                'time_remaining' => dashboard_format_time_remaining((string)($row['deadline_at'] ?? null), $status),
                'actor_action_type' => trim((string)($row['actor_action_type'] ?? '')),
                'actor_action_remarks' => trim((string)($row['actor_action_remarks'] ?? '')),
            ];
        }

        return $mapped;
    }
}

if (!function_exists('dashboard_fetch_ard_division_tracker_rows')) {
    function dashboard_fetch_ard_division_tracker_rows(PDO $pdo, int $ardOfficeId, array $actionTypes = ['Approved', 'Forwarded', 'Returned', 'Rerouted'], int $limit = 100, ?array $dateRange = null): array
    {
        if ($ardOfficeId <= 0) {
            return [];
        }

        $normalizedTypes = [];
        foreach ($actionTypes as $type) {
            $value = trim((string)$type);
            if ($value === '') {
                continue;
            }
            $value = preg_replace('/\s+/', ' ', $value);
            if ($value === null || $value === '') {
                continue;
            }
            $normalizedTypes[] = ucfirst(strtolower($value));
        }
        $normalizedTypes = array_values(array_unique($normalizedTypes));
        if (empty($normalizedTypes)) {
            $normalizedTypes = ['Approved', 'Forwarded', 'Returned', 'Rerouted'];
        }

        $safeLimit = max(5, min($limit, 200));

        $divisionStmt = $pdo->prepare(
            "SELECT id
             FROM offices
             WHERE UPPER(COALESCE(level, '')) = 'DIVISION'
               AND parent_office_id = :ard_office_id
             ORDER BY id ASC"
        );
        $divisionStmt->execute(['ard_office_id' => $ardOfficeId]);
        $divisionRows = $divisionStmt->fetchAll() ?: [];

        $trackedOfficeIds = [];
        foreach ($divisionRows as $divisionRow) {
            $divisionOfficeId = (int)($divisionRow['id'] ?? 0);
            if ($divisionOfficeId > 0) {
                $trackedOfficeIds[] = $divisionOfficeId;
            }
        }
        $trackedOfficeIds = array_values(array_unique(array_filter($trackedOfficeIds, static fn(int $id): bool => $id > 0)));
        if (empty($trackedOfficeIds)) {
            return [];
        }

        $officePlaceholders = [];
        $params = [];
        foreach ($trackedOfficeIds as $index => $trackedOfficeId) {
            $paramKey = 'office_' . $index;
            $officePlaceholders[] = ':' . $paramKey;
            $params[$paramKey] = $trackedOfficeId;
        }

        $quotedTypes = array_map(static fn(string $value): string => "'" . str_replace("'", "''", $value) . "'", $normalizedTypes);
        $inListSql = implode(', ', $quotedTypes);
        $officeInSql = implode(', ', $officePlaceholders);
        $dateClause = dashboard_build_date_range_clause('actor_event.created_at', $dateRange, 'ard_tracker_date');

        $sql = 'SELECT
                    d.id,
                    ' . dashboard_documents_row_version_expr($pdo) . ' AS row_version,
                    d.tracking_id,
                    d.subject,
                    d.status,
                    d.source_type,
                    d.originating_office_id,
                    d.current_office_id,
                    d.pending_office_id,
                    d.created_at,
                    dt.name AS document_type,
                    ' . dashboard_documents_arta_category_expr($pdo) . ' AS arta_category,
                    COALESCE(o_current.name, \'-\') AS current_holder_office,
                    COALESCE(UPPER(o_current.level), \'\') AS current_holder_level,
                    COALESCE(o_pending.name, \'\') AS pending_holder_office,
                    COALESCE(UPPER(o_pending.level), \'\') AS pending_holder_level,
                    actor_event.created_at AS actor_action_at,
                    actor_event.action_type AS actor_action_type,
                    actor_event.remarks AS actor_action_remarks,
                    ' . dashboard_sql_custom_others_remarks_subquery($pdo) . ',
                    ' . dashboard_sql_add_days_expr('COALESCE(last_receives.last_received_at, d.created_at)', dashboard_documents_arta_days_expr($pdo)) . ' AS deadline_at,
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, '')) IN (" . dashboard_section_like_level_sql_list() . ")
                    ",
                        'has_section_receive'
                    ) . '
                FROM documents d
                INNER JOIN (
                    SELECT al.document_id, MAX(al.id) AS latest_division_event_id
                    FROM activity_logs al
                    INNER JOIN users u_actor ON u_actor.id = al.user_id
                    WHERE u_actor.office_id IN (' . $officeInSql . ')
                      AND al.action_type IN (' . $inListSql . ')
                    GROUP BY al.document_id
                ) actor_docs ON actor_docs.document_id = d.id
                INNER JOIN activity_logs actor_event ON actor_event.id = actor_docs.latest_division_event_id
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN offices o_current ON o_current.id = d.current_office_id
                LEFT JOIN offices o_pending ON o_pending.id = d.pending_office_id
                LEFT JOIN (
                    SELECT document_id, MAX(date_time_received) AS last_received_at
                    FROM tracking_slips
                    GROUP BY document_id
                ) last_receives ON last_receives.document_id = d.id
                WHERE 1 = 1
                ' . $dateClause['sql'] . '
                ORDER BY actor_event.created_at DESC, d.id DESC' . dashboard_sql_limit_suffix($safeLimit);

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, $dateClause['params']));
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return [];
        }

        $contextOfficeId = (int)($params['office_id_custody']
            ?? $params['office_id_current']
            ?? $params['office_id_current_action']
            ?? $params['office_id_origin']
            ?? 0);

        $mapped = [];
        foreach ($rows as $row) {
            $trackingId = trim((string)($row['tracking_id'] ?? ''));
            if ($trackingId === '') {
                continue;
            }

            $subject = trim((string)($row['subject'] ?? ''));
            if ($subject === '') {
                $subject = 'No subject';
            }

            $status = workflow_resolve_display_status(
                trim((string)($row['status'] ?? 'Pending')),
                (int)($row['pending_office_id'] ?? 0),
                (int)($row['current_office_id'] ?? 0)
            );

            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized'));
            if ($documentType === '') {
                $documentType = 'Uncategorized';
            }

            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $documentTypeRemarks = trim((string)($row['custom_others_remarks'] ?? ''));
            if ($documentTypeRemarks === '') {
                $documentTypeRemarks = trim((string)($row['last_remarks'] ?? ''));
            }
            if ($documentTypeRemarks === '') {
                $documentTypeRemarks = trim((string)($row['actor_action_remarks'] ?? ''));
            }
            $documentTypeLabel = workflow_format_document_type_label($documentType, $artaCategory, $documentTypeRemarks);

            $currentHolder = trim((string)($row['pending_holder_office'] ?? ''));
            if ($currentHolder === '') {
                $currentHolder = trim((string)($row['current_holder_office'] ?? '-'));
            }
            if ($currentHolder === '') {
                $currentHolder = '-';
            }

            $mapped[] = [
                'document_id' => (int)($row['id'] ?? 0),
                'row_version' => max(1, (int)($row['row_version'] ?? 1)),
                'tracking_id' => $trackingId,
                'subject' => $subject,
                'status' => $status,
                'status_raw' => trim((string)($row['status'] ?? '')) !== '' ? (string)$row['status'] : 'Pending',
                'source_type' => strtoupper(trim((string)($row['source_type'] ?? 'INTERNAL'))),
                'origin_office_id' => (int)($row['originating_office_id'] ?? 0),
                'current_office_id' => (int)($row['current_office_id'] ?? 0),
                'pending_office_id' => (int)($row['pending_office_id'] ?? 0),
                'document_type' => $documentTypeLabel,
                'arta_category' => $artaCategory !== '' ? $artaCategory : 'Simple',
                'current_holder' => $currentHolder,
                'current_office_level' => trim((string)($row['current_holder_level'] ?? '')),
                'pending_office_level' => trim((string)($row['pending_holder_level'] ?? '')),
                'origin_office' => '',
                'has_section_receive' => (int)($row['has_section_receive'] ?? 0) > 0 ? 1 : 0,
                'date_received' => dashboard_format_datetime_label((string)($row['actor_action_at'] ?? null)),
                'date_received_raw' => (string)($row['actor_action_at'] ?? ''),
                'date_created' => dashboard_format_datetime_label((string)($row['created_at'] ?? null)),
                'date_created_raw' => (string)($row['created_at'] ?? ''),
                'deadline_at_raw' => (string)($row['deadline_at'] ?? ''),
                'deadline_bucket' => dashboard_deadline_bucket((string)($row['deadline_at'] ?? null), $status),
                'time_remaining' => dashboard_format_time_remaining((string)($row['deadline_at'] ?? null), $status),
                'actor_action_type' => trim((string)($row['actor_action_type'] ?? '')),
                'actor_action_remarks' => trim((string)($row['actor_action_remarks'] ?? '')),
            ];
        }

        return $mapped;
    }
}

if (!function_exists('dashboard_fetch_role_metrics')) {
    function dashboard_fetch_role_metrics(
        PDO $pdo,
        int $officeId,
        ?array $dateRange = null,
        ?int $viewerUserId = null,
        ?string $viewerRoleKey = null
    ): array
    {
        $defaults = [
            'total_scope' => 0,
            'pending_total' => 0,
            'due_today_total' => 0,
            'due_soon_total' => 0,
            'overdue_total' => 0,
            'completed_total' => 0,
            'released_total' => 0,
            'completed_week' => 0,
            'created_today' => 0,
            'completed_today' => 0,
            'returned_total' => 0,
            'external_today' => 0,
            'pending_approval_total' => 0,
            'pending_sign_total' => 0,
            'pending_forward_total' => 0,
            'for_signature_total' => 0,
            'for_clearance_total' => 0,
            'for_endorsement_total' => 0,
            'for_validation_total' => 0,
            'at_risk_total' => 0,
            'overrides_30d' => 0,
            'compliance_rate' => 100,
        ];

        if ($officeId <= 0) {
            return $defaults;
        }

        $isSqlServer = db_is_sql_server($pdo);
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $hasSourceTypeColumn = dashboard_column_exists($pdo, 'documents', 'source_type');
        $scope = dashboard_build_office_scope($officeId, $hasPendingOfficeColumn);
        $assignmentFilter = dashboard_build_regional_ored_assignment_filter(
            $pdo,
            $viewerUserId,
            $viewerRoleKey,
            $officeId
        );
        if ($assignmentFilter['sql'] !== '') {
            $scope['where'] .= $assignmentFilter['sql'];
            $scope['params'] = array_merge($scope['params'], $assignmentFilter['params']);
        }
        $dateClause = dashboard_build_date_range_clause('COALESCE(office_ts.office_received_at, d.created_at)', $dateRange, 'role_metrics_date');
        $activityDateClause = dashboard_build_date_range_clause('al.created_at', $dateRange, 'role_metrics_activity_date');
        $hasExplicitDateRange = !empty($dateClause['range']);

        $externalTodayExpr = $hasSourceTypeColumn
            ? 'SUM(CASE WHEN d.source_type = \'EXTERNAL\' AND ' . dashboard_sql_date_only_expr('d.created_at') . ' = ' . dashboard_sql_current_date_expr() . ' THEN 1 ELSE 0 END) AS external_today'
            : 'SUM(CASE WHEN LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%external%\' AND ' . dashboard_sql_date_only_expr('d.created_at') . ' = ' . dashboard_sql_current_date_expr() . ' THEN 1 ELSE 0 END) AS external_today';

        $custodyStartExpr = 'COALESCE(office_ts.office_received_at, d.created_at)';
        $custodyDeadlineExpr = dashboard_sql_add_days_expr($custodyStartExpr, dashboard_documents_arta_days_expr($pdo));
        $pendingApprovalExpr = $hasPendingOfficeColumn
            ? '                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'resolved\', \'done\', \'cancelled\', \'canceled\')
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%approved%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%signed%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%verified%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%endorsed%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%checked%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%validated%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%forward%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%route%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'assigned to %\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%draft%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%created%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%encoded%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%submitted%\'
                             AND (
                                 LOWER(COALESCE(d.status, \'\')) LIKE \'%received%\'
                                 OR LOWER(COALESCE(d.status, \'\')) LIKE \'%recieved%\'
                                 OR (
                                     d.current_office_id = :office_id_current_metric
                                     AND (d.pending_office_id IS NULL OR d.pending_office_id <= 0)
                                 )
                             )
                             AND (
                                 d.current_office_id = :office_id_current_metric
                                 OR d.pending_office_id = :office_id_pending_metric
                             )
                        THEN 1 ELSE 0
                    END) AS pending_approval_total
'
            : '                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'resolved\', \'done\', \'cancelled\', \'canceled\')
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%approved%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%signed%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%verified%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%endorsed%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%checked%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%validated%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%forward%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%route%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'assigned to %\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%draft%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%created%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%encoded%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%submitted%\'
                             AND (
                                 LOWER(COALESCE(d.status, \'\')) LIKE \'%received%\'
                                 OR LOWER(COALESCE(d.status, \'\')) LIKE \'%recieved%\'
                                 OR d.current_office_id = :office_id_current_metric
                             )
                             AND d.current_office_id = :office_id_current_metric
                        THEN 1 ELSE 0
                    END) AS pending_approval_total
';
        $pendingForwardExpr = $hasPendingOfficeColumn
            ? '                    SUM(CASE
                        WHEN (
                                LOWER(COALESCE(d.status, \'\')) LIKE \'%approved%\'
                                OR LOWER(COALESCE(d.status, \'\')) LIKE \'%signed%\'
                                OR LOWER(COALESCE(d.status, \'\')) LIKE \'%verified%\'
                                OR LOWER(COALESCE(d.status, \'\')) LIKE \'%endorsed%\'
                                OR LOWER(COALESCE(d.status, \'\')) LIKE \'%checked%\'
                                OR LOWER(COALESCE(d.status, \'\')) LIKE \'%validated%\'
                             )
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%forward%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%route%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'resolved\', \'done\', \'cancelled\', \'canceled\')
                             AND (
                                 d.current_office_id = :office_id_current_metric
                                 OR d.pending_office_id = :office_id_pending_metric
                             )
                        THEN 1 ELSE 0
                    END) AS pending_forward_total
'
            : '                    SUM(CASE
                        WHEN (
                                LOWER(COALESCE(d.status, \'\')) LIKE \'%approved%\'
                                OR LOWER(COALESCE(d.status, \'\')) LIKE \'%signed%\'
                                OR LOWER(COALESCE(d.status, \'\')) LIKE \'%verified%\'
                                OR LOWER(COALESCE(d.status, \'\')) LIKE \'%endorsed%\'
                                OR LOWER(COALESCE(d.status, \'\')) LIKE \'%checked%\'
                                OR LOWER(COALESCE(d.status, \'\')) LIKE \'%validated%\'
                             )
                             AND d.current_office_id = :office_id_current_metric
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%forward%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT LIKE \'%route%\'
                             AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'resolved\', \'done\', \'cancelled\', \'canceled\')
                        THEN 1 ELSE 0
                    END) AS pending_forward_total
';

        $sql = 'SELECT
                    COUNT(*) AS total_scope,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                        THEN 0 ELSE 1
                    END) AS pending_total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND ' . dashboard_sql_days_until_expr($custodyDeadlineExpr) . ' = 0
                        THEN 1 ELSE 0
                    END) AS due_today_total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND ' . dashboard_sql_days_until_expr($custodyDeadlineExpr) . ' = 1
                        THEN 1 ELSE 0
                    END) AS due_soon_total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND ' . dashboard_sql_days_until_expr($custodyDeadlineExpr) . ' < 0
                        THEN 1 ELSE 0
                    END) AS overdue_total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\')
                        THEN 1 ELSE 0
                    END) AS completed_total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) IN (\'released\', \'release\')
                        THEN 1 ELSE 0
                    END) AS released_total,
                    SUM(CASE
                        WHEN ' . dashboard_sql_date_only_expr('d.created_at') . ' = ' . dashboard_sql_current_date_expr() . '
                        THEN 1 ELSE 0
                    END) AS created_today,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND LOWER(COALESCE(d.status, \'\')) LIKE \'%returned%\'
                        THEN 1 ELSE 0
                    END) AS returned_total,
                    0 AS completed_today,
                    ' . $externalTodayExpr . ',
                    ' . $pendingApprovalExpr . ',
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND (
                                 LOWER(COALESCE(d.status, \'\')) LIKE \'%approved%\'
                                 OR
                                 LOWER(COALESCE(d.status, \'\')) LIKE \'%signature%\'
                                 OR LOWER(COALESCE(d.status, \'\')) LIKE \'%pending sign%\'
                                 OR LOWER(COALESCE(d.status, \'\')) LIKE \'%for sign%\'
                                 OR LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%signature%\'
                             )
                        THEN 1 ELSE 0
                    END) AS pending_sign_total,
                    ' . $pendingForwardExpr . ',
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND (
                                 LOWER(COALESCE(d.status, \'\')) LIKE \'%signature%\'
                                 OR LOWER(COALESCE(d.status, \'\')) LIKE \'%approval%\'
                                 OR LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%signature%\'
                                 OR LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%approval%\'
                             )
                        THEN 1 ELSE 0
                    END) AS for_signature_total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND (
                                 LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%clearance%\'
                                 OR LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%appropriate action%\'
                                 OR LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%for clearance%\'
                             )
                        THEN 1 ELSE 0
                    END) AS for_clearance_total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND (
                                 LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%endorse%\'
                                 OR LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%appropriate action%\'
                                 OR LOWER(COALESCE(d.status, \'\')) LIKE \'%forward%\'
                             )
                        THEN 1 ELSE 0
                    END) AS for_endorsement_total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND (
                                 LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%validation%\'
                                 OR LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%site%\'
                                 OR LOWER(COALESCE(d.subject, \'\')) LIKE \'%validation%\'
                                 OR LOWER(COALESCE(d.subject, \'\')) LIKE \'%site%\'
                             )
                        THEN 1 ELSE 0
                    END) AS for_validation_total
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN (
                    SELECT ts.document_id, ts.action_required
                    FROM tracking_slips ts
                    INNER JOIN (
                        SELECT document_id, MAX(id) AS latest_id
                        FROM tracking_slips
                        GROUP BY document_id
                    ) ts_latest_ids ON ts_latest_ids.latest_id = ts.id
                ) ts_latest ON ts_latest.document_id = d.id
                LEFT JOIN (
                    SELECT ts.document_id, MAX(ts.date_time_received) AS office_received_at
                    FROM tracking_slips ts
                    WHERE ts.receiving_office_id = :office_id_custody
                    GROUP BY ts.document_id
                ) office_ts ON office_ts.document_id = d.id
                WHERE ' . $scope['where'] . '
                ' . $dateClause['sql'];

        $officeScopeParams = array_merge($scope['params'], [
            'office_id_custody' => $officeId,
            'office_id_current_metric' => $officeId,
        ]);
        if ($hasPendingOfficeColumn) {
            $officeScopeParams['office_id_pending_metric'] = $officeId;
        }
        if ($isSqlServer) {
            $sql = dashboard_inline_sqlsrv_named_params($pdo, $sql, $officeScopeParams);
        }

        $stmt = $pdo->prepare($sql);
        $params = $isSqlServer
            ? $dateClause['params']
            : array_merge($officeScopeParams, $dateClause['params']);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        $metrics = $defaults;
        foreach (array_keys($defaults) as $key) {
            if ($key === 'compliance_rate' || $key === 'at_risk_total' || $key === 'overrides_30d' || $key === 'completed_week') {
                continue;
            }
            $metrics[$key] = (int)($row[$key] ?? 0);
        }

        $completionSql = 'SELECT COUNT(DISTINCT al.document_id) AS completed_week
                          FROM activity_logs al
                          INNER JOIN documents d ON d.id = al.document_id
                          WHERE ' . $scope['where'] . '
                            ' . $activityDateClause['sql'] . '
                            AND LOWER(al.action_type) IN (\'completed\', \'released\', \'approved\', \'signed\', \'closed\', \'resolved\')'
                            . ($hasExplicitDateRange ? '' : '
                            AND al.created_at >= ' . dashboard_sql_recent_cutoff_expr(7));
        if (db_is_sql_server($pdo)) {
            $completionSql = dashboard_inline_sqlsrv_named_params($pdo, $completionSql, $scope['params']);
        }
        $completionStmt = $pdo->prepare($completionSql);
        $completionStmt->execute(db_is_sql_server($pdo) ? $activityDateClause['params'] : array_merge($scope['params'], $activityDateClause['params']));
        $metrics['completed_week'] = (int)($completionStmt->fetchColumn() ?: 0);

        $completedTodaySql = 'SELECT COUNT(DISTINCT d.id) AS completed_today
                              FROM documents d
                              INNER JOIN activity_logs al_c ON al_c.document_id = d.id
                              WHERE ' . $scope['where'] . '
                                AND LOWER(COALESCE(d.status, \'\')) IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\')
                                AND LOWER(al_c.action_type) IN (\'completed\', \'released\', \'approved\', \'signed\', \'signed/completed\', \'closed\', \'resolved\', \'done\')
                                AND ' . dashboard_sql_date_only_expr('al_c.created_at') . ' = ' . dashboard_sql_current_date_expr();
        if (db_is_sql_server($pdo)) {
            $completedTodaySql = dashboard_inline_sqlsrv_named_params($pdo, $completedTodaySql, $scope['params']);
        }
        $completedTodayStmt = $pdo->prepare($completedTodaySql);
        $completedTodayStmt->execute(db_is_sql_server($pdo) ? [] : $scope['params']);
        $metrics['completed_today'] = (int)($completedTodayStmt->fetchColumn() ?: 0);

        // Keep the dashboard "Pending Received" metric aligned with the
        // actionable office queue instead of the broader office tracking scope.
        $metrics['pending_total'] = dashboard_count_pending_receive_rows(
            $pdo,
            $officeId,
            true,
            true,
            $viewerUserId,
            $viewerRoleKey,
            $dateRange
        );

        if ($metrics['completed_week'] === 0 && $metrics['completed_total'] > 0) {
            $metrics['completed_week'] = $metrics['completed_total'];
        }

        if ($metrics['for_validation_total'] === 0 && $metrics['pending_total'] > 0) {
            $metrics['for_validation_total'] = $metrics['pending_total'];
        }
        if ($metrics['for_endorsement_total'] === 0 && $metrics['pending_total'] > 0) {
            $metrics['for_endorsement_total'] = $metrics['pending_total'];
        }
        if ($metrics['for_clearance_total'] === 0 && $metrics['pending_total'] > 0) {
            $metrics['for_clearance_total'] = $metrics['pending_total'];
        }
        $metrics['at_risk_total'] = $metrics['due_today_total'] + $metrics['due_soon_total'] + $metrics['overdue_total'];

        $logSql = 'SELECT COUNT(*) AS override_count
                   FROM activity_logs al
                   INNER JOIN documents d ON d.id = al.document_id
                   WHERE ' . $scope['where'] . '
                     ' . $activityDateClause['sql'] . '
                     AND al.action_type IN (\'Rerouted\', \'Diverted\', \'Override\', \'Overridden\')'
                     . ($hasExplicitDateRange ? '' : '
                     AND al.created_at >= ' . dashboard_sql_recent_cutoff_expr(30));
        if (db_is_sql_server($pdo)) {
            $logSql = dashboard_inline_sqlsrv_named_params($pdo, $logSql, $scope['params']);
        }
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute(db_is_sql_server($pdo) ? $activityDateClause['params'] : array_merge($scope['params'], $activityDateClause['params']));
        $metrics['overrides_30d'] = (int)($logStmt->fetchColumn() ?: 0);

        $activeScope = max($metrics['pending_total'], 1);
        if ($metrics['total_scope'] > 0) {
            $metrics['compliance_rate'] = (int)max(
                0,
                min(100, round((($activeScope - $metrics['overdue_total']) / $activeScope) * 100))
            );
        }

        return $metrics;
    }
}

if (!function_exists('dashboard_fetch_route_offices')) {
    function dashboard_fetch_route_offices(PDO $pdo, int $excludeOfficeId = 0, int $limit = 200): array
    {
        $safeLimit = max(5, min($limit, 500));
        $sql = 'SELECT id, name, level, parent_office_id
                FROM offices
                WHERE (:exclude_id_a <= 0 OR id <> :exclude_id_b)
                ORDER BY
                    CASE UPPER(COALESCE(level, \'\'))
                        WHEN \'REGIONAL\' THEN 1
                        WHEN \'DIVISION\' THEN 2
                        WHEN \'SECTION\' THEN 3
                        WHEN \'CENRO_SECTION\' THEN 4
                        WHEN \'CENRO_UNIT\' THEN 5
                        WHEN \'CENRO_OFFICER\' THEN 6
                        WHEN \'CENRO_ADMIN_RECORD\' THEN 7
                        WHEN \'PAMO_UNIT\' THEN 8
                        WHEN \'PASU_OFFICER\' THEN 9
                        WHEN \'PAMO_ADMIN\' THEN 10
                        WHEN \'PENRO_DIVISION\' THEN 11
                        WHEN \'PENRO_SECTION\' THEN 12
                        WHEN \'PENRO_OFFICER\' THEN 13
                        WHEN \'PENRO_ADMIN_RECORD\' THEN 14
                        WHEN \'PROVINCIAL\' THEN 15
                        WHEN \'COMMUNITY\' THEN 16
                        WHEN \'PROTECTED AREA\' THEN 17
                        ELSE 18
                    END,
                    name ASC' . dashboard_sql_limit_suffix($safeLimit);

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'exclude_id_a' => $excludeOfficeId,
            'exclude_id_b' => $excludeOfficeId,
        ]);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return [];
        }

        $offices = [];
        foreach ($rows as $row) {
            $offices[] = [
                'id' => (int)($row['id'] ?? 0),
                'name' => (string)($row['name'] ?? ''),
                'level' => (string)($row['level'] ?? ''),
                'parent_office_id' => (int)($row['parent_office_id'] ?? 0),
            ];
        }

        return $offices;
    }
}

if (!function_exists('dashboard_fetch_regional_office_summary_rows')) {
    function dashboard_fetch_regional_office_summary_rows(PDO $pdo, int $limit = 120, ?array $dateRange = null): array
    {
        $safeLimit = max(5, min($limit, 500));
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $ownerOfficeExpr = $hasPendingOfficeColumn
            ? 'CASE WHEN d.pending_office_id IS NOT NULL AND d.pending_office_id > 0 THEN d.pending_office_id ELSE d.current_office_id END'
            : 'd.current_office_id';
        $deadlineExpr = dashboard_sql_add_days_expr('d.created_at', dashboard_documents_arta_days_expr($pdo));
        $terminalStatuses = "'completed','released','closed','approved','resolved','done','signed','signed/completed','cancelled','canceled'";
        $dateClause = dashboard_build_date_range_clause('d.created_at', $dateRange, 'regional_summary_date');

        $sql = 'SELECT
                    o.id AS office_id,
                    o.name AS office_name,
                    UPPER(COALESCE(o.level, \'\')) AS office_level,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (' . $terminalStatuses . ')
                        THEN 1 ELSE 0
                    END) AS pending_count,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (' . $terminalStatuses . ')
                             AND ' . dashboard_sql_days_until_expr($deadlineExpr) . ' = 1
                        THEN 1 ELSE 0
                    END) AS due_soon_count,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (' . $terminalStatuses . ')
                             AND ' . dashboard_sql_date_only_expr($deadlineExpr) . ' < ' . dashboard_sql_current_date_expr() . '
                        THEN 1 ELSE 0
                    END) AS overdue_count,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) IN (' . $terminalStatuses . ')
                        THEN 1 ELSE 0
                    END) AS completed_count
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                INNER JOIN offices o ON o.id = ' . $ownerOfficeExpr . '
                WHERE o.id > 0
                  AND UPPER(COALESCE(o.level, \'\')) <> \'REGIONAL\'
                  ' . $dateClause['sql'] . '
                GROUP BY o.id, o.name, o.level
                ORDER BY pending_count DESC, overdue_count DESC, due_soon_count DESC, o.name ASC' . dashboard_sql_limit_suffix($safeLimit);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($dateClause['params']);
        $rows = $stmt->fetchAll() ?: [];
        if (empty($rows)) {
            return [];
        }

        $summaryRows = [];
        foreach ($rows as $row) {
            $summaryRows[] = [
                'office_id' => (int)($row['office_id'] ?? 0),
                'office_name' => trim((string)($row['office_name'] ?? 'Unknown Office')),
                'office_level' => trim((string)($row['office_level'] ?? '')),
                'pending_count' => max((int)($row['pending_count'] ?? 0), 0),
                'due_soon_count' => max((int)($row['due_soon_count'] ?? 0), 0),
                'overdue_count' => max((int)($row['overdue_count'] ?? 0), 0),
                'completed_count' => max((int)($row['completed_count'] ?? 0), 0),
            ];
        }

        return $summaryRows;
    }
}

if (!function_exists('dashboard_collect_office_subtree_ids')) {
    function dashboard_collect_office_subtree_ids(PDO $pdo, int $rootOfficeId): array
    {
        if ($rootOfficeId <= 0) {
            return [];
        }

        $queue = [$rootOfficeId];
        $seen = [];
        $subtreeIds = [];
        $childrenStmt = $pdo->prepare(
            'SELECT id
             FROM offices
             WHERE parent_office_id = :parent_office_id'
        );

        while (!empty($queue)) {
            $officeId = (int)array_shift($queue);
            if ($officeId <= 0 || isset($seen[$officeId])) {
                continue;
            }

            $seen[$officeId] = true;
            $subtreeIds[] = $officeId;

            $childrenStmt->execute(['parent_office_id' => $officeId]);
            $children = $childrenStmt->fetchAll() ?: [];
            foreach ($children as $child) {
                $childId = (int)($child['id'] ?? 0);
                if ($childId > 0 && !isset($seen[$childId])) {
                    $queue[] = $childId;
                }
            }
        }

        return $subtreeIds;
    }
}

if (!function_exists('dashboard_fetch_office_summary_rows_by_office_ids')) {
    function dashboard_fetch_office_summary_rows_by_office_ids(PDO $pdo, array $officeIds, int $limit = 120, ?array $dateRange = null): array
    {
        $scopedOfficeIds = [];
        foreach ($officeIds as $officeId) {
            $normalizedId = (int)$officeId;
            if ($normalizedId > 0) {
                $scopedOfficeIds[$normalizedId] = true;
            }
        }
        $scopedOfficeIds = array_keys($scopedOfficeIds);
        if (empty($scopedOfficeIds)) {
            return [];
        }

        $safeLimit = max(5, min($limit, 500));
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $ownerOfficeExpr = $hasPendingOfficeColumn
            ? 'CASE WHEN d.pending_office_id IS NOT NULL AND d.pending_office_id > 0 THEN d.pending_office_id ELSE d.current_office_id END'
            : 'd.current_office_id';
        $deadlineExpr = dashboard_sql_add_days_expr('d.created_at', dashboard_documents_arta_days_expr($pdo));
        $terminalStatuses = "'completed','released','closed','approved','resolved','done','signed','signed/completed','cancelled','canceled'";
        $dateClause = dashboard_build_date_range_clause('d.created_at', $dateRange, 'office_summary_date');

        $officePlaceholders = [];
        $params = [];
        foreach ($scopedOfficeIds as $index => $officeId) {
            $key = 'office_id_' . $index;
            $officePlaceholders[] = ':' . $key;
            $params[$key] = (int)$officeId;
        }

        $sql = 'SELECT
                    o.id AS office_id,
                    o.name AS office_name,
                    UPPER(COALESCE(o.level, \'\')) AS office_level,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (' . $terminalStatuses . ')
                        THEN 1 ELSE 0
                    END) AS pending_count,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (' . $terminalStatuses . ')
                             AND ' . dashboard_sql_days_until_expr($deadlineExpr) . ' = 1
                        THEN 1 ELSE 0
                    END) AS due_soon_count,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (' . $terminalStatuses . ')
                             AND ' . dashboard_sql_date_only_expr($deadlineExpr) . ' < ' . dashboard_sql_current_date_expr() . '
                        THEN 1 ELSE 0
                    END) AS overdue_count,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) IN (' . $terminalStatuses . ')
                        THEN 1 ELSE 0
                    END) AS completed_count
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                INNER JOIN offices o ON o.id = ' . $ownerOfficeExpr . '
                WHERE o.id IN (' . implode(', ', $officePlaceholders) . ')
                ' . $dateClause['sql'] . '
                GROUP BY o.id, o.name, o.level
                ORDER BY pending_count DESC, overdue_count DESC, due_soon_count DESC, o.name ASC' . dashboard_sql_limit_suffix($safeLimit);

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, $dateClause['params']));
        $rows = $stmt->fetchAll() ?: [];
        if (empty($rows)) {
            return [];
        }

        $summaryRows = [];
        foreach ($rows as $row) {
            $summaryRows[] = [
                'office_id' => (int)($row['office_id'] ?? 0),
                'office_name' => trim((string)($row['office_name'] ?? 'Unknown Office')),
                'office_level' => trim((string)($row['office_level'] ?? '')),
                'pending_count' => max((int)($row['pending_count'] ?? 0), 0),
                'due_soon_count' => max((int)($row['due_soon_count'] ?? 0), 0),
                'overdue_count' => max((int)($row['overdue_count'] ?? 0), 0),
                'completed_count' => max((int)($row['completed_count'] ?? 0), 0),
            ];
        }

        return $summaryRows;
    }
}

if (!function_exists('dashboard_fetch_cenro_internal_office_summary_rows')) {
    function dashboard_fetch_cenro_internal_office_summary_rows(PDO $pdo, int $cenroRootOfficeId, int $limit = 120): array
    {
        if ($cenroRootOfficeId <= 0) {
            return [];
        }

        $subtreeIds = dashboard_collect_office_subtree_ids($pdo, $cenroRootOfficeId);
        if (empty($subtreeIds)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($subtreeIds as $index => $officeId) {
            $key = 'subtree_office_id_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = (int)$officeId;
        }

        $internalOfficeSql = 'SELECT id
                              FROM offices
                              WHERE id IN (' . implode(', ', $placeholders) . ')
                                AND UPPER(COALESCE(level, \'\')) IN (
                                    \'CENRO_ADMIN_RECORD\',
                                    \'CENRO_OFFICER\',
                                    \'CENRO_SECTION\',
                                    \'CENRO_UNIT\',
                                    \'PAMO_ADMIN\',
                                    \'PASU_OFFICER\',
                                    \'PAMO_UNIT\',
                                    \'PENRO_ADMIN_RECORD\',
                                    \'PENRO_OFFICER\',
                                    \'PENRO_DIVISION\',
                                    \'PENRO_SECTION\'
                                )';
        $internalStmt = $pdo->prepare($internalOfficeSql);
        $internalStmt->execute($params);
        $internalIds = array_map(
            static fn(array $row): int => (int)($row['id'] ?? 0),
            $internalStmt->fetchAll() ?: []
        );
        $internalIds = array_values(array_filter($internalIds, static fn(int $id): bool => $id > 0));
        if (empty($internalIds)) {
            return [];
        }

        return dashboard_fetch_office_summary_rows_by_office_ids($pdo, $internalIds, $limit);
    }
}

if (!function_exists('dashboard_fetch_activity_counters')) {
    function dashboard_fetch_activity_counters(PDO $pdo, int $officeId, int $windowDays = 30, ?array $dateRange = null): array
    {
        $defaults = [
            'received_events' => 0,
            'approved_events' => 0,
            'forwarded_events' => 0,
            'reroute_events' => 0,
            'returned_events' => 0,
        ];

        if ($officeId <= 0) {
            return $defaults;
        }

        $days = max(1, min($windowDays, 120));
        $isSqlServer = db_is_sql_server($pdo);
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $scope = dashboard_build_office_scope($officeId, $hasPendingOfficeColumn);
        $dateClause = dashboard_build_date_range_clause('al.created_at', $dateRange, 'activity_counter_date');
        $hasExplicitDateRange = !empty($dateClause['range']);

        $sql = 'SELECT
                    SUM(CASE WHEN LOWER(al.action_type) = \'received\' THEN 1 ELSE 0 END) AS received_events,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'approved\', \'approve\') THEN 1 ELSE 0 END) AS approved_events,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'forwarded\', \'forward\') THEN 1 ELSE 0 END) AS forwarded_events,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'rerouted\', \'reroute\', \'overridden\', \'override\') THEN 1 ELSE 0 END) AS reroute_events,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'returned\', \'return\') THEN 1 ELSE 0 END) AS returned_events
                FROM activity_logs al
                INNER JOIN documents d ON d.id = al.document_id
                WHERE ' . $scope['where'] . '
                ' . $dateClause['sql'] . '
                  ' . ($hasExplicitDateRange ? '' : ('AND al.created_at >= ' . dashboard_sql_recent_cutoff_expr($days)));

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($scope['params'], $dateClause['params']));
        $row = $stmt->fetch() ?: [];

        $counters = $defaults;
        foreach (array_keys($defaults) as $key) {
            $counters[$key] = (int)($row[$key] ?? 0);
        }

        return $counters;
    }
}

if (!function_exists('dashboard_fetch_notifications')) {
    function dashboard_fetch_notifications(PDO $pdo, int $officeId, int $limit = 12, ?string $roleName = null, ?int $userId = null): array
    {
        if ($officeId <= 0) {
            return [];
        }

        $safeLimit = max(3, min($limit, 30));
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $scope = dashboard_build_office_scope($officeId, $hasPendingOfficeColumn);
        $roleKey = '';
        if ($roleName !== null && trim($roleName) !== '') {
            if (function_exists('app_normalize_role_key')) {
                $roleKey = app_normalize_role_key($roleName);
            } else {
                $roleKey = strtoupper(trim((string)$roleName));
                $roleKey = preg_replace('/[^A-Z0-9]+/', '_', $roleKey) ?? '';
                $roleKey = trim($roleKey, '_');
            }
        }

        $whereClause = $scope['where'];
        $params = $scope['params'];
        if (in_array($roleKey, ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD', 'PAMO_ADMIN'], true)) {
            // Admin-record origin offices: full lifecycle notifications only for documents originated by their office.
            $whereClause = 'd.originating_office_id = :office_id_origin_notifications';
            $params = [
                'office_id_origin_notifications' => $officeId,
            ];
        } elseif (in_array($roleKey, ['ORED', 'ORED_SIGN'], true)) {
            // Keep reviewer and signer notifications separated by assignment.
            $whereClause = "al.destination_office_id = :office_id_destination_notifications
                            AND UPPER(COALESCE(al.action_type, '')) IN (
                                'FORWARDED', 'FORWARD', 'REROUTED', 'REROUTE',
                                'OVERRIDDEN', 'OVERRIDE', 'RETURNED', 'RETURN',
                                'RELEASED', 'RELEASE'
                            )
                            AND (
                                COALESCE(al.destination_user_id, 0) = 0
                                OR COALESCE(al.destination_user_id, 0) = :notification_user_id
                            )";
            $params = [
                'office_id_destination_notifications' => $officeId,
                'notification_user_id' => max(0, (int)$userId),
            ];
        } elseif (in_array($roleKey, ['CENRO_OFFICER', 'CENRO_SECTION', 'CENRO_UNIT', 'PASU_OFFICER', 'PAMO_UNIT', 'PENRO_OFFICER', 'PENRO_DIVISION', 'PENRO_SECTION', 'RECORDS_UNIT', 'DIVISION_CHIEF', 'SECTION_STAFF', 'ARD_TS', 'ARD_MS'], true)) {
            // Internal action roles: prioritize incoming routed events; keep origin fallback for legacy office mappings.
            $whereClause = "(
                                al.destination_office_id = :office_id_destination_notifications
                                AND UPPER(COALESCE(al.action_type, '')) IN (
                                    'FORWARDED', 'FORWARD', 'REROUTED', 'REROUTE',
                                    'OVERRIDDEN', 'OVERRIDE', 'RETURNED', 'RETURN',
                                    'RELEASED', 'RELEASE'
                                )
                            )
                            OR d.originating_office_id = :office_id_origin_notifications";
            $params = [
                'office_id_destination_notifications' => $officeId,
                'office_id_origin_notifications' => $officeId,
            ];
        }

        $sql = 'SELECT
                    al.id,
                    al.action_type,
                    al.remarks,
                    al.created_at,
                    d.tracking_id,
                    d.subject,
                    COALESCE(dest.name, \'\') AS destination_office_name,
                    ' . dashboard_sql_top_one_subquery(
                        $pdo,
                        'al_color.remarks',
                        'FROM activity_logs al_color',
                        "WHERE al_color.document_id = d.id
                          AND LOWER(COALESCE(al_color.remarks, '')) LIKE '%color classification:%'",
                        'al_color.id DESC',
                        'color_classification_remarks'
                    ) . '
                FROM activity_logs al
                INNER JOIN documents d ON d.id = al.document_id
                LEFT JOIN offices dest ON dest.id = al.destination_office_id
                WHERE ' . $whereClause . '
                ORDER BY al.created_at DESC, al.id DESC' . dashboard_sql_limit_suffix($safeLimit);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
        if (empty($rows)) {
            return [];
        }

        $notifications = [];
        foreach ($rows as $row) {
            $actionRaw = strtoupper(trim((string)($row['action_type'] ?? 'UPDATE')));
            $title = 'Queue Updated';
            if ($actionRaw === 'RECEIVED') {
                $title = 'Document Received';
            } elseif (in_array($actionRaw, ['FORWARDED', 'REROUTED', 'OVERRIDDEN', 'RELEASED'], true)) {
                $title = 'Document Routed';
            } elseif (in_array($actionRaw, ['RETURNED', 'RETURN'], true)) {
                $title = 'Returned for Correction';
            } elseif ($actionRaw === 'PENDING') {
                $title = 'Document Pending';
            } elseif (in_array($actionRaw, ['APPROVED', 'SIGNED'], true)) {
                $title = 'Review Completed';
            }

            $trackingId = trim((string)($row['tracking_id'] ?? ''));
            $subject = trim((string)($row['subject'] ?? ''));
            $remarks = trim((string)($row['remarks'] ?? ''));
            $destinationOfficeName = trim((string)($row['destination_office_name'] ?? ''));
            $colorClassificationRemarks = trim((string)($row['color_classification_remarks'] ?? ''));
            $indicatorKey = dashboard_notification_indicator_key($actionRaw, $remarks, $colorClassificationRemarks);
            $indicatorLabel = dashboard_notification_indicator_label($indicatorKey);

            $messageParts = [];
            if ($trackingId !== '') {
                $messageParts[] = $trackingId;
            }
            if ($subject !== '') {
                $messageParts[] = $subject;
            }
            if ($destinationOfficeName !== '' && in_array($actionRaw, ['FORWARDED', 'REROUTED', 'OVERRIDDEN', 'RELEASED'], true)) {
                $messageParts[] = 'to ' . $destinationOfficeName;
            }
            if ($remarks !== '') {
                $messageParts[] = $remarks;
            }

            $message = implode(' - ', $messageParts);
            if ($message === '') {
                $message = 'A document update was recorded.';
            }

            $createdAt = (string)($row['created_at'] ?? '');
            $notifications[] = [
                'id' => (int)($row['id'] ?? 0),
                'title' => $title,
                'message' => $message,
                'datetime' => $createdAt,
                'timeLabel' => dashboard_relative_time_label($createdAt),
                'unread' => true,
                'tracking_id' => $trackingId,
                'indicator_key' => $indicatorKey,
                'indicator_label' => $indicatorLabel,
            ];
        }

        return $notifications;
    }
}

if (!function_exists('dashboard_fetch_arta_distribution')) {
    function dashboard_fetch_arta_distribution(PDO $pdo, int $officeId, ?array $dateRange = null): array
    {
        $defaults = [
            'simple' => 0,
            'complex' => 0,
            'highly_technical' => 0,
            'uncategorized' => 0,
            'total' => 0,
        ];

        if ($officeId <= 0) {
            return $defaults;
        }

        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $scope = dashboard_build_office_scope($officeId, $hasPendingOfficeColumn);
        $dateClause = dashboard_build_date_range_clause('COALESCE(office_ts.office_received_at, d.created_at)', $dateRange, 'arta_distribution_date');

        $sql = 'SELECT LOWER(COALESCE(' . dashboard_documents_arta_category_expr($pdo) . ', \'\')) AS category_name, COUNT(*) AS total_count
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN (
                    SELECT ts.document_id, MAX(ts.date_time_received) AS office_received_at
                    FROM tracking_slips ts
                    WHERE ts.receiving_office_id = :office_id_custody
                    GROUP BY ts.document_id
                ) office_ts ON office_ts.document_id = d.id
                WHERE ' . $scope['where'] . '
                  ' . $dateClause['sql'] . '
                  AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                GROUP BY LOWER(COALESCE(' . dashboard_documents_arta_category_expr($pdo) . ', \'\'))';

        $stmt = $pdo->prepare($sql);
        $params = $scope['params'];
        $params['office_id_custody'] = $officeId;
        $params = array_merge($params, $dateClause['params']);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $distribution = $defaults;
        foreach ($rows as $row) {
            $count = (int)($row['total_count'] ?? 0);
            $category = (string)($row['category_name'] ?? '');
            if ($count <= 0) {
                continue;
            }

            if (str_contains($category, 'highly') || str_contains($category, 'technical')) {
                $distribution['highly_technical'] += $count;
                continue;
            }
            if (str_contains($category, 'complex')) {
                $distribution['complex'] += $count;
                continue;
            }
            if (str_contains($category, 'simple')) {
                $distribution['simple'] += $count;
                continue;
            }
            $distribution['uncategorized'] += $count;
        }

        $distribution['total'] =
            $distribution['simple']
            + $distribution['complex']
            + $distribution['highly_technical']
            + $distribution['uncategorized'];

        return $distribution;
    }
}

/* -----------------------------------------------------------------------
 * CENRO-specific filtered queue fetchers
 * ----------------------------------------------------------------------- */

/**
 * Pending Receive â€” documents routed TO this office that have NOT yet been
 * formally received (pending_office_id = office AND current_office_id â‰  office).
 */
if (!function_exists('dashboard_fetch_pending_receive_rows')) {
    function dashboard_build_pending_receive_filter(
        PDO $pdo,
        int $officeId,
        bool $includeInOfficeActionStage = false,
        bool $excludeReturnedDocuments = false,
        ?int $viewerUserId = null,
        ?string $viewerRoleKey = null
    ): array
    {
        $hasPending = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $excludeReturnedSql = $excludeReturnedDocuments
            ? "
                           AND NOT EXISTS (
                               SELECT 1
                               FROM activity_logs al_ret
                               WHERE al_ret.document_id = d.id
                                 AND LOWER(COALESCE(al_ret.action_type, '')) IN ('return', 'returned')
                                 AND al_ret.id = (
                                     SELECT MAX(al_last.id)
                                     FROM activity_logs al_last
                                     WHERE al_last.document_id = d.id
                                 )
                           )"
            : '';

        if ($hasPending) {
            if ($includeInOfficeActionStage) {
                $whereClause = '(
                                (
                                    d.pending_office_id = :office_id_pending_incoming
                                    AND d.current_office_id <> :office_id_current_incoming
                                    AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'closed\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                                )
                                OR
                                (
                                    d.current_office_id = :office_id_current_action
                                    AND (d.pending_office_id IS NULL OR d.pending_office_id = 0 OR d.pending_office_id = :office_id_pending_action)
                                    AND (
                                        LOWER(COALESCE(d.status, \'\')) IN (\'received\', \'recieved\', \'approved\', \'signed\', \'signed/completed\', \'forwarded\')
                                        OR LOWER(COALESCE(d.status, \'\')) LIKE \'assigned to %\'
                                    )
                                )
                            )' . $excludeReturnedSql;
                $params = [
                    'office_id_pending_incoming' => $officeId,
                    'office_id_current_incoming' => $officeId,
                    'office_id_current_action' => $officeId,
                    'office_id_pending_action' => $officeId,
                    'office_id_custody' => $officeId,
                ];
            } else {
                $whereClause = 'd.pending_office_id = :office_id_pending
                               AND d.current_office_id <> :office_id_current
                               AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')'
                               . $excludeReturnedSql;
                $params = [
                    'office_id_pending' => $officeId,
                    'office_id_current' => $officeId,
                    'office_id_custody' => $officeId,
                ];
            }
        } else {
            $whereClause = 'd.current_office_id = :office_id_current
                           AND LOWER(COALESCE(d.status, \'\')) IN (\'pending receive\', \'for receive\', \'in transit\', \'routed\', \'received\', \'recieved\', \'approved\', \'signed\', \'signed/completed\')'
                           . $excludeReturnedSql;
            $params = [
                'office_id_current' => $officeId,
                'office_id_custody' => $officeId,
            ];
        }

        $assignmentFilter = dashboard_build_regional_ored_assignment_filter(
            $pdo,
            $viewerUserId,
            $viewerRoleKey,
            $officeId
        );
        if ($assignmentFilter['sql'] !== '') {
            $whereClause .= $assignmentFilter['sql'];
            $params = array_merge($params, $assignmentFilter['params']);
        }

        return [
            'where' => $whereClause,
            'params' => $params,
        ];
    }

    function dashboard_fetch_pending_receive_rows(
        PDO $pdo,
        int $officeId,
        int $limit = 60,
        bool $includeInOfficeActionStage = false,
        bool $excludeReturnedDocuments = false,
        ?int $viewerUserId = null,
        ?string $viewerRoleKey = null
    ): array
    {
        if ($officeId <= 0) {
            return [];
        }
        $safeLimit = max(1, min($limit, 200));
        $filter = dashboard_build_pending_receive_filter(
            $pdo,
            $officeId,
            $includeInOfficeActionStage,
            $excludeReturnedDocuments,
            $viewerUserId,
            $viewerRoleKey
        );

        return dashboard_cenro_run_filtered_query($pdo, $filter['where'], $filter['params'], $safeLimit);
    }
}

if (!function_exists('dashboard_build_regional_ored_assignment_filter')) {
    function dashboard_build_regional_ored_assignment_filter(
        PDO $pdo,
        ?int $viewerUserId = null,
        ?string $viewerRoleKey = null,
        ?int $viewerOfficeId = null,
        string $documentAlias = 'd'
    ): array {
        $userId = (int)($viewerUserId ?? 0);
        $officeId = (int)($viewerOfficeId ?? 0);
        if ($userId <= 0) {
            return ['sql' => '', 'params' => []];
        }

        $roleKey = app_normalize_role_key((string)($viewerRoleKey ?? ''));
        if (!function_exists('workflow_is_regional_ored_role_key') || !workflow_is_regional_ored_role_key($roleKey)) {
            return ['sql' => '', 'params' => []];
        }

        $hasPendingOfficeId = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $hasPendingUserId = dashboard_column_exists($pdo, 'documents', 'pending_user_id');
        $hasCurrentHolderUserId = dashboard_column_exists($pdo, 'documents', 'current_holder_user_id');
        if (!$hasPendingUserId && !$hasCurrentHolderUserId) {
            return ['sql' => '', 'params' => []];
        }

        $viewerParam = 'ored_queue_viewer_user_id';
        $params = [];
        $reviewerRoleId = function_exists('workflow_get_role_id_by_key')
            ? (int)(workflow_get_role_id_by_key($pdo, 'ORED') ?? 0)
            : 0;
        $signerRoleId = function_exists('workflow_get_role_id_by_key')
            ? (int)(workflow_get_role_id_by_key($pdo, 'ORED_SIGN') ?? 0)
            : 0;
        $pendingAssignedToViewer = $hasPendingUserId
            ? '(' . $documentAlias . '.pending_user_id = :' . $viewerParam . ')'
            : '0 = 1';
        $currentlyHeldByViewer = $hasCurrentHolderUserId
            ? '(
                    (' . ($hasPendingUserId ? $documentAlias . '.pending_user_id IS NULL OR ' . $documentAlias . '.pending_user_id = 0' : '1 = 1') . ')
                    AND ' . $documentAlias . '.current_holder_user_id = :' . $viewerParam . '
               )'
            : '0 = 1';

        if ($roleKey === 'ORED_SIGN') {
            $params[$viewerParam] = $userId;
            $sql = ' AND (
                            ' . $pendingAssignedToViewer . '
                            OR ' . $currentlyHeldByViewer . '
                        )';

            return [
                'sql' => $sql,
                'params' => $params,
            ];
        }

        $pendingAssignedToReviewerPool = '0 = 1';
        if ($hasPendingUserId && $officeId > 0 && $reviewerRoleId > 0) {
            $params['ored_queue_viewer_office_id_pool'] = $officeId;
            $params['ored_queue_reviewer_role_id'] = $reviewerRoleId;
            $pendingAssignedToReviewerPool = 'EXISTS (
                    SELECT 1
                    FROM users u_pool
                    WHERE u_pool.id = ' . $documentAlias . '.pending_user_id
                      AND u_pool.is_active = 1
                      AND u_pool.office_id = :ored_queue_viewer_office_id_pool
                      AND u_pool.role_id = :ored_queue_reviewer_role_id
                )';
        }
        $currentlyHeldByReviewerPool = '0 = 1';
        if ($hasCurrentHolderUserId && $officeId > 0 && $reviewerRoleId > 0) {
            $params['ored_queue_viewer_office_id_holder_pool'] = $officeId;
            $params['ored_queue_reviewer_role_id_holder'] = $reviewerRoleId;
            $currentlyHeldByReviewerPool = 'EXISTS (
                    SELECT 1
                    FROM users u_hold
                    WHERE u_hold.id = ' . $documentAlias . '.current_holder_user_id
                      AND u_hold.is_active = 1
                      AND u_hold.office_id = :ored_queue_viewer_office_id_holder_pool
                      AND u_hold.role_id = :ored_queue_reviewer_role_id_holder
                )';
        }

        $incomingToRegionalOredOffice = '0 = 1';
        if ($hasPendingOfficeId && $officeId > 0) {
            $params['ored_queue_viewer_office_id'] = $officeId;
            $params['ored_queue_viewer_office_id_current'] = $officeId;
            $incomingUserStageGuard = '1 = 1';
            if ($hasPendingUserId) {
                $incomingUserStageGuard = '(
                        ' . $documentAlias . '.pending_user_id IS NULL
                        OR ' . $documentAlias . '.pending_user_id = 0
                        OR ' . $pendingAssignedToReviewerPool . '
                    )';
                if ($signerRoleId > 0) {
                    $params['ored_queue_signer_role_id'] = $signerRoleId;
                    $incomingUserStageGuard = '(
                            ' . $documentAlias . '.pending_user_id IS NULL
                            OR ' . $documentAlias . '.pending_user_id = 0
                            OR ' . $pendingAssignedToReviewerPool . '
                            OR NOT EXISTS (
                                SELECT 1
                                FROM users u_sign
                                WHERE u_sign.id = ' . $documentAlias . '.pending_user_id
                                  AND u_sign.is_active = 1
                                  AND u_sign.role_id = :ored_queue_signer_role_id
                            )
                        )';
                }
            }
            $incomingToRegionalOredOffice = '(
                    ' . $documentAlias . '.pending_office_id = :ored_queue_viewer_office_id
                    AND ' . $documentAlias . '.current_office_id <> :ored_queue_viewer_office_id_current
                    AND ' . $incomingUserStageGuard . '
                )';
        }

        $unassignedToAnySpecificUser = '0 = 1';
        if ($hasPendingUserId || $hasCurrentHolderUserId) {
            $pendingUnassigned = $hasPendingUserId
                ? '(' . $documentAlias . '.pending_user_id IS NULL OR ' . $documentAlias . '.pending_user_id = 0)'
                : '1 = 1';
            $currentUnassigned = $hasCurrentHolderUserId
                ? '(' . $documentAlias . '.current_holder_user_id IS NULL OR ' . $documentAlias . '.current_holder_user_id = 0)'
                : '1 = 1';
            $unassignedToAnySpecificUser = '(' . $pendingUnassigned . ' AND ' . $currentUnassigned . ')';
        }

        $sql = ' AND (
                        ' . $incomingToRegionalOredOffice . '
                        OR
                        ' . $pendingAssignedToReviewerPool . '
                        OR (
                            (' . ($hasPendingUserId ? $documentAlias . '.pending_user_id IS NULL OR ' . $documentAlias . '.pending_user_id = 0' : '1 = 1') . ')
                            AND ' . $currentlyHeldByReviewerPool . '
                        )
                        OR ' . $unassignedToAnySpecificUser . '
                    )';

        return [
            'sql' => $sql,
            'params' => $params,
        ];
    }
}

/**
 * For CENRO Action â€” documents currently held by this office that still need
 * processing (not merely pending receipt, not forwarded out yet).
 */
if (!function_exists('dashboard_fetch_for_cenro_action_rows')) {
    function dashboard_fetch_for_cenro_action_rows(PDO $pdo, int $officeId, int $limit = 60): array
    {
        if ($officeId <= 0) {
            return [];
        }
        $safeLimit = max(1, min($limit, 200));
        $hasPending = dashboard_column_exists($pdo, 'documents', 'pending_office_id');

        if ($hasPending) {
            // Docs in this office's custody AND not already routed out to another office
            $whereClause = 'd.current_office_id = :office_id_current
                           AND (d.pending_office_id IS NULL OR d.pending_office_id = :office_id_pending OR d.pending_office_id = 0)
                           AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\', \'forwarded\')';
            $params = [
                'office_id_current' => $officeId,
                'office_id_pending' => $officeId,
                'office_id_custody' => $officeId,
            ];
        } else {
            $whereClause = 'd.current_office_id = :office_id_current
                           AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\', \'forwarded\')';
            $params = [
                'office_id_current' => $officeId,
                'office_id_custody' => $officeId,
            ];
        }

        return dashboard_cenro_run_filtered_query($pdo, $whereClause, $params, $safeLimit);
    }
}

/**
 * Office Action - documents currently held by this office that still need
 * action. Excludes forwarded-out and terminal states, but keeps approved rows
 * so approval-required offices can proceed to Forward.
 */
if (!function_exists('dashboard_fetch_office_action_rows')) {
    function dashboard_fetch_office_action_rows(PDO $pdo, int $officeId, int $limit = 60): array
    {
        if ($officeId <= 0) {
            return [];
        }
        $safeLimit = max(1, min($limit, 200));
        $hasPending = dashboard_column_exists($pdo, 'documents', 'pending_office_id');

        if ($hasPending) {
            $whereClause = 'd.current_office_id = :office_id_current
                           AND (d.pending_office_id IS NULL OR d.pending_office_id = :office_id_pending OR d.pending_office_id = 0)
                           AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\', \'forwarded\')';
            $params = [
                'office_id_current' => $officeId,
                'office_id_pending' => $officeId,
                'office_id_custody' => $officeId,
            ];
        } else {
            $whereClause = 'd.current_office_id = :office_id_current
                           AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\', \'forwarded\')';
            $params = [
                'office_id_current' => $officeId,
                'office_id_custody' => $officeId,
            ];
        }

        return dashboard_cenro_run_filtered_query($pdo, $whereClause, $params, $safeLimit);
    }
}

/**
 * Returned from Regional â€” documents sent BACK to this office by a regional
 * office (RECORDS-UNIT/ORED) after a Return action in the activity log.
 */
if (!function_exists('dashboard_fetch_returned_from_regional_rows')) {
    function dashboard_fetch_returned_from_regional_rows(PDO $pdo, int $officeId, int $limit = 60): array
    {
        if ($officeId <= 0) {
            return [];
        }
        $safeLimit = max(1, min($limit, 200));
        $hasPending = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $hasSourceOfficeColumn = dashboard_column_exists($pdo, 'activity_logs', 'source_office_id');

        // Docs where the latest workflow action was a return from RECORDS-UNIT/ORED
        // and the current/pending holder is now this office.
        $holderScopeClause = $hasPending
            ? '(d.current_office_id = :office_id_current OR d.pending_office_id = :office_id_pending)'
            : 'd.current_office_id = :office_id_current';

        $regionalJoinSql = $hasSourceOfficeColumn
            ? 'INNER JOIN offices o_ret ON o_ret.id = al_ret.source_office_id'
            : 'INNER JOIN users u_ret ON u_ret.id = al_ret.user_id
                               INNER JOIN offices o_ret ON o_ret.id = u_ret.office_id';

        $whereClause = $holderScopeClause . '
                        AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                        AND EXISTS (
                            SELECT 1
                            FROM activity_logs al_ret
                            ' . $regionalJoinSql . '
                            WHERE al_ret.document_id = d.id
                              AND LOWER(al_ret.action_type) IN (\'returned\', \'return\')
                              AND (
                                    UPPER(COALESCE(o_ret.name, \'\')) LIKE \'%PACDO%\'
                                    OR UPPER(COALESCE(o_ret.name, \'\')) LIKE \'%RECORDS-UNIT%\'
                                    OR UPPER(COALESCE(o_ret.name, \'\')) LIKE \'%RECORDS UNIT%\'
                                    OR UPPER(COALESCE(o_ret.name, \'\')) LIKE \'%RECORDS_UNIT%\'
                                    OR UPPER(COALESCE(o_ret.name, \'\')) LIKE \'%ORED%\'
                                    OR UPPER(COALESCE(o_ret.name, \'\')) LIKE \'%REGIONAL EXECUTIVE%\'
                              )
                              AND al_ret.created_at = (
                                  SELECT MAX(al_last.created_at)
                                  FROM activity_logs al_last
                                  WHERE al_last.document_id = d.id
                              )
                        )';
        $params = [
            'office_id_current' => $officeId,
            'office_id_custody' => $officeId,
        ];
        if ($hasPending) {
            $params['office_id_pending'] = $officeId;
        }

        return dashboard_cenro_run_filtered_query($pdo, $whereClause, $params, $safeLimit);
    }
}

/**
 * Outbox â€” documents forwarded OUT by this office; the document is no longer
 * in this office's custody (current_office_id â‰  this office but originated
 * here OR was last forwarded by this office).
 */
if (!function_exists('dashboard_fetch_outbox_rows')) {
    function dashboard_fetch_outbox_rows(PDO $pdo, int $officeId, int $limit = 60): array
    {
        if ($officeId <= 0) {
            return [];
        }
        $safeLimit = max(1, min($limit, 200));
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $hasSourceOfficeColumn = dashboard_column_exists($pdo, 'activity_logs', 'source_office_id');
        $actorOfficeClause = $hasSourceOfficeColumn
            ? 'al_fwd.source_office_id = :office_id_fwd'
            : 'EXISTS (
                    SELECT 1
                    FROM users u_fwd
                    WHERE u_fwd.id = al_fwd.user_id
                      AND u_fwd.office_id = :office_id_fwd
                )';

        // Docs originated from this office OR last forwarded by this office.
        // Include both:
        // 1) already received by another office (current_office_id changed), and
        // 2) routed-out handoff rows still in transit (current_office_id is this office
        //    but pending_office_id points to another office).
        $holderScopeClause = $hasPendingOfficeColumn
            ? '(
                    d.current_office_id <> :office_id_current
                    OR (
                        d.current_office_id = :office_id_current_same
                        AND d.pending_office_id IS NOT NULL
                        AND d.pending_office_id <> 0
                        AND d.pending_office_id <> :office_id_pending_self
                    )
                )'
            : 'd.current_office_id <> :office_id_current';

        $whereClause = $holderScopeClause . '
                        AND (
                            d.originating_office_id = :office_id_origin
                            OR EXISTS (
                                SELECT 1
                                FROM activity_logs al_fwd
                                WHERE al_fwd.document_id = d.id
                                  AND ' . $actorOfficeClause . '
                                  AND LOWER(al_fwd.action_type) IN (\'forwarded\', \'forward\', \'rerouted\', \'released\')
                            )
                        )
                        AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'resolved\', \'done\', \'cancelled\', \'canceled\')';
        $params = [
            'office_id_current' => $officeId,
            'office_id_origin'  => $officeId,
            'office_id_fwd'     => $officeId,
            'office_id_custody' => $officeId,
        ];
        if ($hasPendingOfficeColumn) {
            $params['office_id_current_same'] = $officeId;
            $params['office_id_pending_self'] = $officeId;
        }

        return dashboard_cenro_run_filtered_query($pdo, $whereClause, $params, $safeLimit);
    }
}

/**
 * Shared SQL runner used by all CENRO-specific filtered queue fetchers.
 * Returns the same mapped-row format as dashboard_fetch_queue_rows.
 */
if (!function_exists('dashboard_cenro_run_filtered_query')) {
    function dashboard_cenro_run_filtered_query(PDO $pdo, string $whereClause, array $params, int $safeLimit, ?array $dateRange = null): array
    {
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $pendingOfficeSelect = $hasPendingOfficeColumn
            ? 'd.pending_office_id AS pending_office_id,'
            : 'NULL AS pending_office_id,';
        $dateClause = dashboard_build_date_range_clause('COALESCE(office_ts.office_received_at, d.created_at)', $dateRange, 'cenro_queue_date');

        $sql = 'SELECT
                    d.id,
                    ' . dashboard_documents_row_version_expr($pdo) . ' AS row_version,
                    d.tracking_id,
                    d.subject,
                    d.status,
                    d.source_type,
                    d.originating_office_id,
                    d.current_office_id,
                    d.created_at,
                    ' . $pendingOfficeSelect . '
                    dt.name AS document_type,
                    ' . dashboard_documents_arta_category_expr($pdo) . ' AS arta_category,
                    ' . dashboard_sql_top_one_subquery(
                        $pdo,
                        'al_last.remarks',
                        'FROM activity_logs al_last',
                        "WHERE al_last.document_id = d.id
                          AND TRIM(COALESCE(al_last.remarks, '')) <> ''",
                        'al_last.id DESC',
                        'last_remarks'
                    ) . ',
                    ' . dashboard_sql_custom_others_remarks_subquery($pdo) . ',
                    COALESCE(office_ts.office_received_at, d.created_at) AS queue_received_at,
                    ' . dashboard_sql_add_days_expr('d.created_at', dashboard_documents_arta_days_expr($pdo)) . ' AS deadline_at,
                    COALESCE(o_current.name, \'-\') AS current_holder,
                    COALESCE(o_pending.name, \'\') AS pending_holder,
                    ' . dashboard_documents_origin_expr($pdo) . ' AS origin_office,
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM activity_logs al_signed
                        WHERE al_signed.document_id = d.id
                          AND LOWER(COALESCE(al_signed.action_type, '')) = 'signed'
                    ",
                        'has_signed_action'
                    ) . ',
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, '')) IN (" . dashboard_section_like_level_sql_list() . ")
                    ",
                    'has_section_receive'
                    ) . ',
                    ' . dashboard_sql_exists_flag(
                        $pdo,
                        "
                        SELECT 1
                        FROM activity_logs al_ret
                        WHERE al_ret.document_id = d.id
                          AND LOWER(COALESCE(al_ret.action_type, '')) IN ('return', 'returned')
                    ",
                        'has_return_action'
                    ) . '
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN offices o_current ON o_current.id = d.current_office_id
                LEFT JOIN offices o_pending ON o_pending.id = d.pending_office_id
                LEFT JOIN offices o_origin ON o_origin.id = d.originating_office_id
                LEFT JOIN (
                    SELECT ts.document_id, MAX(ts.date_time_received) AS office_received_at
                    FROM tracking_slips ts
                    WHERE ts.receiving_office_id = :office_id_custody
                    GROUP BY ts.document_id
                ) office_ts ON office_ts.document_id = d.id
                WHERE ' . $whereClause . '
                ' . $dateClause['sql'] . '
                ORDER BY
                    CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) = \'overdue\' THEN 3
                        WHEN LOWER(COALESCE(d.status, \'\')) IN (\'due soon\', \'at-risk\', \'at risk\') THEN 2
                        ELSE 1
                    END DESC,
                    queue_received_at DESC,
                    d.id DESC' . dashboard_sql_limit_suffix($safeLimit);

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, $dateClause['params']));
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return [];
        }

        $contextOfficeId = (int)($params['office_id_custody']
            ?? $params['office_id_current']
            ?? $params['office_id_current_action']
            ?? $params['office_id_origin']
            ?? 0);

        $mapped = [];
        foreach ($rows as $row) {
            $trackingId = trim((string)($row['tracking_id'] ?? ''));
            if ($trackingId === '') {
                continue;
            }
            $subject = trim((string)($row['subject'] ?? '')) ?: 'No subject';
            $status  = trim((string)($row['status'] ?? 'Pending')) ?: 'Pending';
            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized')) ?: 'Uncategorized';
            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $documentTypeRemarks = trim((string)($row['custom_others_remarks'] ?? ''));
            if ($documentTypeRemarks === '') {
                $documentTypeRemarks = trim((string)($row['last_remarks'] ?? ''));
            }
            $documentTypeLabel = workflow_format_document_type_label($documentType, $artaCategory, $documentTypeRemarks);
            $documentId = (int)($row['id'] ?? 0);
            $effectiveHasSignedAction = (int)($row['has_signed_action'] ?? 0) > 0 ? 1 : 0;
            $localSignedAction = dashboard_queue_local_signed_action($pdo, $documentId, $contextOfficeId);
            if ($localSignedAction !== null) {
                $effectiveHasSignedAction = $localSignedAction ? 1 : 0;
            }

            $mapped[] = [
                'document_id'       => $documentId,
                'row_version'       => max(1, (int)($row['row_version'] ?? 1)),
                'tracking_id'       => $trackingId,
                'subject'           => $subject,
                'status'            => $status,
                'source_type'       => strtoupper(trim((string)($row['source_type'] ?? 'INTERNAL'))),
                'origin_office_id'  => (int)($row['originating_office_id'] ?? 0),
                'current_office_id' => (int)($row['current_office_id'] ?? 0),
                'pending_office_id' => (int)($row['pending_office_id'] ?? 0),
                'last_remarks'      => workflow_strip_custom_others_document_type_from_remarks(
                    trim((string)($row['last_remarks'] ?? ''))
                ),
                'has_signed_action' => $effectiveHasSignedAction,
                'document_type'     => $documentTypeLabel,
                'arta_category'     => $artaCategory !== '' ? $artaCategory : 'Simple',
                'current_holder'    => trim((string)($row['current_holder'] ?? '-')),
                'reroute_to'        => (
                    (int)($row['pending_office_id'] ?? 0) > 0
                    && trim((string)($row['pending_holder'] ?? '')) !== ''
                )
                    ? trim((string)($row['pending_holder'] ?? '-'))
                    : trim((string)($row['current_holder'] ?? '-')),
                'origin_office'     => trim((string)($row['origin_office'] ?? '-')),
                'has_section_receive' => (int)($row['has_section_receive'] ?? 0) > 0 ? 1 : 0,
                'has_return_action' => (int)($row['has_return_action'] ?? 0) > 0 ? 1 : 0,
                'date_received'     => dashboard_format_datetime_label((string)($row['queue_received_at'] ?? null)),
                'date_received_raw' => (string)($row['queue_received_at'] ?? ''),
                'date_created'      => dashboard_format_datetime_label((string)($row['created_at'] ?? null)),
                'date_created_raw'  => (string)($row['created_at'] ?? ''),
                'deadline_at_raw'   => (string)($row['deadline_at'] ?? ''),
                'deadline_bucket'   => dashboard_deadline_bucket((string)($row['deadline_at'] ?? null), $status),
                'time_remaining'    => dashboard_format_time_remaining((string)($row['deadline_at'] ?? null), $status),
            ];
        }

        return $mapped;
    }
}

if (!function_exists('dashboard_cenro_count_filtered_query')) {
    function dashboard_cenro_count_filtered_query(PDO $pdo, string $whereClause, array $params, ?array $dateRange = null): int
    {
        $dateClause = dashboard_build_date_range_clause('COALESCE(office_ts.office_received_at, d.created_at)', $dateRange, 'cenro_queue_date');
        $sql = 'SELECT COUNT(*) AS total_count
                FROM documents d
                LEFT JOIN (
                    SELECT ts.document_id, MAX(ts.date_time_received) AS office_received_at
                    FROM tracking_slips ts
                    WHERE ts.receiving_office_id = :office_id_custody
                    GROUP BY ts.document_id
                ) office_ts ON office_ts.document_id = d.id
                WHERE ' . $whereClause . '
                ' . $dateClause['sql'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, $dateClause['params']));

        return (int)($stmt->fetchColumn() ?: 0);
    }
}

if (!function_exists('dashboard_count_pending_receive_rows')) {
    function dashboard_count_pending_receive_rows(
        PDO $pdo,
        int $officeId,
        bool $includeInOfficeActionStage = false,
        bool $excludeReturnedDocuments = false,
        ?int $viewerUserId = null,
        ?string $viewerRoleKey = null,
        ?array $dateRange = null
    ): int
    {
        if ($officeId <= 0) {
            return 0;
        }

        $filter = dashboard_build_pending_receive_filter(
            $pdo,
            $officeId,
            $includeInOfficeActionStage,
            $excludeReturnedDocuments,
            $viewerUserId,
            $viewerRoleKey
        );

        return dashboard_cenro_count_filtered_query($pdo, $filter['where'], $filter['params'], $dateRange);
    }
}

/* -----------------------------------------------------------------------
 * End CENRO-specific filters
 * ----------------------------------------------------------------------- */

if (!function_exists('dashboard_fetch_workflow_trend')) {
    function dashboard_fetch_workflow_trend(PDO $pdo, int $officeId, int $days = 7, ?array $dateRange = null): array
    {
        $range = dashboard_effective_date_range($dateRange);
        $startDate = null;
        $endDate = null;
        if (isset($range['from_input']) && trim((string)$range['from_input']) !== '') {
            try {
                $startDate = (new DateTimeImmutable((string)$range['from_input']))->setTime(0, 0, 0);
            } catch (Throwable $exception) {
                $startDate = null;
            }
        }
        if (isset($range['to_input']) && trim((string)$range['to_input']) !== '') {
            try {
                $endDate = (new DateTimeImmutable((string)$range['to_input']))->setTime(0, 0, 0);
            } catch (Throwable $exception) {
                $endDate = null;
            }
        }

        if ($startDate !== null && $endDate !== null) {
            $spanDays = (int)$startDate->diff($endDate)->days + 1;
            $safeDays = max(1, min($spanDays, 31));
            $start = $startDate;
        } elseif ($startDate !== null) {
            $safeDays = max(5, min($days, 31));
            $start = $startDate;
        } elseif ($endDate !== null) {
            $safeDays = max(5, min($days, 31));
            $start = $endDate->sub(new DateInterval('P' . ($safeDays - 1) . 'D'));
        } else {
            $safeDays = max(5, min($days, 31));
            $today = new DateTimeImmutable('today');
            $start = $today->sub(new DateInterval('P' . ($safeDays - 1) . 'D'));
        }

        $result = [
            'labels' => [],
            'received' => [],
            'forwarded' => [],
            'completed' => [],
        ];

        for ($i = 0; $i < $safeDays; $i += 1) {
            $day = $start->add(new DateInterval('P' . $i . 'D'));
            $result['labels'][] = $day->format('M d');
            $result['received'][] = 0;
            $result['forwarded'][] = 0;
            $result['completed'][] = 0;
        }

        if ($officeId <= 0) {
            return $result;
        }

        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $scope = dashboard_build_office_scope($officeId, $hasPendingOfficeColumn);
        $dateClause = dashboard_build_date_range_clause('al.created_at', $dateRange, 'workflow_trend_date');
        $startAt = $start->format('Y-m-d 00:00:00');

        $sql = 'SELECT
                    ' . dashboard_sql_date_only_expr('al.created_at') . ' AS activity_day,
                    SUM(CASE WHEN LOWER(al.action_type) = \'received\' THEN 1 ELSE 0 END) AS received_count,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'forwarded\', \'forward\', \'rerouted\', \'reroute\', \'released\', \'overridden\', \'override\') THEN 1 ELSE 0 END) AS forwarded_count,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'approved\', \'signed\', \'completed\', \'released\', \'closed\', \'resolved\', \'done\') THEN 1 ELSE 0 END) AS completed_count
                FROM activity_logs al
                INNER JOIN documents d ON d.id = al.document_id
                WHERE ' . $scope['where'] . '
                  ' . $dateClause['sql'] . '
                  AND al.created_at >= :start_at
                GROUP BY ' . dashboard_sql_date_only_expr('al.created_at') . '
                ORDER BY activity_day ASC';

        $stmt = $pdo->prepare($sql);
        $params = $scope['params'];
        $params['start_at'] = $startAt;
        $params = array_merge($params, $dateClause['params']);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $indexByDay = [];
        for ($i = 0; $i < $safeDays; $i += 1) {
            $day = $start->add(new DateInterval('P' . $i . 'D'))->format('Y-m-d');
            $indexByDay[$day] = $i;
        }

        foreach ($rows as $row) {
            $day = (string)($row['activity_day'] ?? '');
            if ($day === '' || !array_key_exists($day, $indexByDay)) {
                continue;
            }
            $index = $indexByDay[$day];
            $result['received'][$index] = (int)($row['received_count'] ?? 0);
            $result['forwarded'][$index] = (int)($row['forwarded_count'] ?? 0);
            $result['completed'][$index] = (int)($row['completed_count'] ?? 0);
        }

        return $result;
    }
}
