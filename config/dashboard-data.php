<?php
declare(strict_types=1);

if (!function_exists('dashboard_column_exists')) {
    function dashboard_column_exists(PDO $pdo, string $table, string $column): bool
    {
        static $cache = [];
        $cacheKey = strtolower($table . '.' . $column);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $safeTable = str_replace('`', '', $table);
        $safeColumn = str_replace(['\\', "'"], ['\\\\', "\\'"], $column);
        $sql = "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'";
        $stmt = $pdo->query($sql);
        $cache[$cacheKey] = $stmt ? (bool)$stmt->fetch() : false;

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
    function dashboard_fetch_queue_rows(PDO $pdo, int $officeId, int $limit = 8): array
    {
        if ($officeId <= 0) {
            return [];
        }

        $safeLimit = max(1, min($limit, 20));
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $scope = dashboard_build_office_scope($officeId, $hasPendingOfficeColumn);
        $pendingOfficeSelect = $hasPendingOfficeColumn
            ? 'd.pending_office_id AS pending_office_id,'
            : 'NULL AS pending_office_id,';

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
                    d.created_at,
                    ' . $pendingOfficeSelect . '
                    dt.name AS document_type,
                    ' . dashboard_documents_arta_category_expr($pdo) . ' AS arta_category,
                    (
                        SELECT al_ret.remarks
                        FROM activity_logs al_ret
                        WHERE al_ret.document_id = d.id
                          AND (
                              LOWER(COALESCE(al_ret.action_type, \'\')) LIKE \'return%\'
                              OR LOWER(COALESCE(al_ret.action_type, \'\')) = \'returned\'
                          )
                          AND TRIM(COALESCE(al_ret.remarks, \'\')) <> \'\'
                        ORDER BY al_ret.id DESC
                        LIMIT 1
                    ) AS return_remarks,
                    (
                        SELECT al_last.remarks
                        FROM activity_logs al_last
                        WHERE al_last.document_id = d.id
                          AND TRIM(COALESCE(al_last.remarks, \'\')) <> \'\'
                        ORDER BY al_last.id DESC
                        LIMIT 1
                    ) AS last_remarks,
                    COALESCE(office_ts.office_received_at, d.created_at) AS queue_received_at,
                    DATE_ADD(d.created_at, INTERVAL ' . dashboard_documents_arta_days_expr($pdo) . ' DAY) AS deadline_at,
                    COALESCE(o_current.name, \'-\') AS current_holder,
                    COALESCE(o_origin.name, \'-\') AS origin_office,
                    EXISTS(
                        SELECT 1
                        FROM activity_logs al_signed
                        WHERE al_signed.document_id = d.id
                          AND LOWER(COALESCE(al_signed.action_type, \'\')) = \'signed\'
                    ) AS has_signed_action,
                    EXISTS(
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, \'\')) = \'SECTION\'
                    ) AS has_section_receive,
                    EXISTS(
                        SELECT 1
                        FROM activity_logs al_ret
                        WHERE al_ret.document_id = d.id
                          AND LOWER(COALESCE(al_ret.action_type, \'\')) IN (\'return\', \'returned\')
                    ) AS has_return_action
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
                ORDER BY
                    CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) = \'overdue\' THEN 3
                        WHEN LOWER(COALESCE(d.status, \'\')) IN (\'due soon\', \'at-risk\', \'at risk\') THEN 2
                        ELSE 1
                    END DESC,
                    queue_received_at DESC,
                    d.id DESC
                LIMIT ' . $safeLimit;

        $stmt = $pdo->prepare($sql);
        $params = $scope['params'];
        $params['office_id_custody'] = $officeId;
        $stmt->execute($params);
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

            $status = trim((string)($row['status'] ?? 'Pending'));
            if ($status === '') {
                $status = 'Pending';
            }

            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized'));
            if ($documentType === '') {
                $documentType = 'Uncategorized';
            }

            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $documentTypeLabel = $artaCategory !== ''
                ? $documentType . ' (' . $artaCategory . ')'
                : $documentType;
            $returnRemarks = trim((string)($row['return_remarks'] ?? ''));
            $lastRemarks = trim((string)($row['last_remarks'] ?? ''));
            $resolvedRemarks = $returnRemarks !== ''
                ? $returnRemarks
                : $lastRemarks;

            $mapped[] = [
                'document_id' => (int)($row['id'] ?? 0),
                'row_version' => max(1, (int)($row['row_version'] ?? 1)),
                'tracking_id' => $trackingId,
                'subject' => $subject,
                'status' => $status,
                'source_type' => strtoupper(trim((string)($row['source_type'] ?? 'INTERNAL'))),
                'created_by_user_id' => (int)($row['created_by_user_id'] ?? 0),
                'origin_office_id' => (int)($row['originating_office_id'] ?? 0),
                'current_office_id' => (int)($row['current_office_id'] ?? 0),
                'pending_office_id' => (int)($row['pending_office_id'] ?? 0),
                'has_signed_action' => (int)($row['has_signed_action'] ?? 0) > 0 ? 1 : 0,
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
    function dashboard_fetch_origin_tracker_rows(PDO $pdo, int $originOfficeId, int $limit = 50): array
    {
        if ($originOfficeId <= 0) {
            return [];
        }

        $safeLimit = max(5, min($limit, 200));
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
                    COALESCE(o_current.name, \'-\') AS current_holder_office,
                    COALESCE(o_pending.name, \'\') AS pending_holder_office,
                    COALESCE(last_actions.last_action_at, d.created_at) AS last_move_at,
                    DATE_ADD(COALESCE(last_receives.last_received_at, d.created_at), INTERVAL ' . dashboard_documents_arta_days_expr($pdo) . ' DAY) AS deadline_at,
                    EXISTS(
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, \'\')) = \'SECTION\'
                    ) AS has_section_receive,
                    EXISTS(
                        SELECT 1
                        FROM activity_logs al_ret
                        WHERE al_ret.document_id = d.id
                          AND LOWER(COALESCE(al_ret.action_type, \'\')) IN (\'return\', \'returned\')
                    ) AS has_return_action
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN offices o_current ON o_current.id = d.current_office_id
                LEFT JOIN offices o_pending ON o_pending.id = d.pending_office_id
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
                ORDER BY last_move_at DESC, d.id DESC
                LIMIT ' . $safeLimit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['originating_office_id' => $originOfficeId]);
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

            $status = trim((string)($row['status'] ?? 'Pending'));
            if ($status === '') {
                $status = 'Pending';
            }

            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized'));
            if ($documentType === '') {
                $documentType = 'Uncategorized';
            }

            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $documentTypeLabel = $artaCategory !== ''
                ? $documentType . ' (' . $artaCategory . ')'
                : $documentType;

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
                'source_type' => strtoupper(trim((string)($row['source_type'] ?? 'INTERNAL'))),
                'origin_office_id' => (int)($row['originating_office_id'] ?? 0),
                'document_type' => $documentTypeLabel,
                'arta_category' => $artaCategory !== '' ? $artaCategory : 'Simple',
                'current_holder' => $currentHolder,
                'origin_office' => '',
                'has_section_receive' => (int)($row['has_section_receive'] ?? 0) > 0 ? 1 : 0,
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
    function dashboard_fetch_actor_tracker_rows(PDO $pdo, int $actorUserId, array $actionTypes = ['Approved', 'Signed'], int $limit = 50): array
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
                    COALESCE(o_current.name, \'-\') AS current_holder_office,
                    COALESCE(UPPER(o_current.level), \'\') AS current_holder_level,
                    COALESCE(o_pending.name, \'\') AS pending_holder_office,
                    COALESCE(UPPER(o_pending.level), \'\') AS pending_holder_level,
                    actor_event.created_at AS actor_action_at,
                    actor_event.action_type AS actor_action_type,
                    actor_event.remarks AS actor_action_remarks,
                    (
                        SELECT al_ret.remarks
                        FROM activity_logs al_ret
                        WHERE al_ret.document_id = d.id
                          AND (
                              LOWER(COALESCE(al_ret.action_type, \'\')) LIKE \'return%\'
                              OR LOWER(COALESCE(al_ret.action_type, \'\')) = \'returned\'
                          )
                          AND TRIM(COALESCE(al_ret.remarks, \'\')) <> \'\'
                        ORDER BY al_ret.id DESC
                        LIMIT 1
                    ) AS return_remarks,
                    (
                        SELECT al_last.remarks
                        FROM activity_logs al_last
                        WHERE al_last.document_id = d.id
                          AND TRIM(COALESCE(al_last.remarks, \'\')) <> \'\'
                        ORDER BY al_last.id DESC
                        LIMIT 1
                    ) AS last_remarks,
                    DATE_ADD(COALESCE(last_receives.last_received_at, d.created_at), INTERVAL ' . dashboard_documents_arta_days_expr($pdo) . ' DAY) AS deadline_at,
                    EXISTS(
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, \'\')) = \'SECTION\'
                    ) AS has_section_receive,
                    EXISTS(
                        SELECT 1
                        FROM activity_logs al_ret
                        WHERE al_ret.document_id = d.id
                          AND (
                              LOWER(COALESCE(al_ret.action_type, \'\')) LIKE \'return%\'
                              OR LOWER(COALESCE(al_ret.action_type, \'\')) = \'returned\'
                          )
                    ) AS has_return_action
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
                LEFT JOIN (
                    SELECT document_id, MAX(date_time_received) AS last_received_at
                    FROM tracking_slips
                    GROUP BY document_id
                ) last_receives ON last_receives.document_id = d.id
                ORDER BY actor_event.created_at DESC, d.id DESC
                LIMIT ' . $safeLimit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['actor_user_id' => $actorUserId]);
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

            $status = trim((string)($row['status'] ?? 'Pending'));
            if ($status === '') {
                $status = 'Pending';
            }

            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized'));
            if ($documentType === '') {
                $documentType = 'Uncategorized';
            }

            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $documentTypeLabel = $artaCategory !== ''
                ? $documentType . ' (' . $artaCategory . ')'
                : $documentType;

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
            $resolvedRemarks = $returnRemarks !== ''
                ? $returnRemarks
                : ($lastRemarks !== '' ? $lastRemarks : $actionRemarks);

            $mapped[] = [
                'document_id' => (int)($row['id'] ?? 0),
                'row_version' => max(1, (int)($row['row_version'] ?? 1)),
                'tracking_id' => $trackingId,
                'subject' => $subject,
                'status' => $status,
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
                'current_office_level' => trim((string)($row['current_holder_level'] ?? '')),
                'pending_office_level' => trim((string)($row['pending_holder_level'] ?? '')),
                'origin_office' => '',
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
    function dashboard_fetch_division_staff_tracker_rows(PDO $pdo, int $divisionOfficeId, array $actionTypes = ['Approved', 'Forwarded', 'Returned', 'Rerouted'], int $limit = 100): array
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

        $sectionStmt = $pdo->prepare(
            "SELECT id
             FROM offices
             WHERE UPPER(COALESCE(level, '')) = 'SECTION'
               AND parent_office_id = :division_office_id
             ORDER BY id ASC"
        );
        $sectionStmt->execute(['division_office_id' => $divisionOfficeId]);
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
                    DATE_ADD(COALESCE(last_receives.last_received_at, d.created_at), INTERVAL ' . dashboard_documents_arta_days_expr($pdo) . ' DAY) AS deadline_at,
                    EXISTS(
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, \'\')) = \'SECTION\'
                    ) AS has_section_receive
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
                ORDER BY actor_event.created_at DESC, d.id DESC
                LIMIT ' . $safeLimit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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

            $status = trim((string)($row['status'] ?? 'Pending'));
            if ($status === '') {
                $status = 'Pending';
            }

            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized'));
            if ($documentType === '') {
                $documentType = 'Uncategorized';
            }

            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $documentTypeLabel = $artaCategory !== ''
                ? $documentType . ' (' . $artaCategory . ')'
                : $documentType;

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
    function dashboard_fetch_ard_division_tracker_rows(PDO $pdo, int $ardOfficeId, array $actionTypes = ['Approved', 'Forwarded', 'Returned', 'Rerouted'], int $limit = 100): array
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
                    DATE_ADD(COALESCE(last_receives.last_received_at, d.created_at), INTERVAL ' . dashboard_documents_arta_days_expr($pdo) . ' DAY) AS deadline_at,
                    EXISTS(
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, \'\')) = \'SECTION\'
                    ) AS has_section_receive
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
                ORDER BY actor_event.created_at DESC, d.id DESC
                LIMIT ' . $safeLimit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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

            $status = trim((string)($row['status'] ?? 'Pending'));
            if ($status === '') {
                $status = 'Pending';
            }

            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized'));
            if ($documentType === '') {
                $documentType = 'Uncategorized';
            }

            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $documentTypeLabel = $artaCategory !== ''
                ? $documentType . ' (' . $artaCategory . ')'
                : $documentType;

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
    function dashboard_fetch_role_metrics(PDO $pdo, int $officeId): array
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

        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $hasSourceTypeColumn = dashboard_column_exists($pdo, 'documents', 'source_type');
        $scope = dashboard_build_office_scope($officeId, $hasPendingOfficeColumn);

        $externalTodayExpr = $hasSourceTypeColumn
            ? 'SUM(CASE WHEN d.source_type = \'EXTERNAL\' AND DATE(d.created_at) = CURDATE() THEN 1 ELSE 0 END) AS external_today'
            : 'SUM(CASE WHEN LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%external%\' AND DATE(d.created_at) = CURDATE() THEN 1 ELSE 0 END) AS external_today';

        $custodyStartExpr = 'COALESCE(office_ts.office_received_at, d.created_at)';
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
                             AND DATEDIFF(DATE_ADD(' . $custodyStartExpr . ', INTERVAL ' . dashboard_documents_arta_days_expr($pdo) . ' DAY), CURDATE()) = 0
                        THEN 1 ELSE 0
                    END) AS due_today_total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND DATEDIFF(DATE_ADD(' . $custodyStartExpr . ', INTERVAL ' . dashboard_documents_arta_days_expr($pdo) . ' DAY), CURDATE()) = 1
                        THEN 1 ELSE 0
                    END) AS due_soon_total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND DATEDIFF(DATE_ADD(' . $custodyStartExpr . ', INTERVAL ' . dashboard_documents_arta_days_expr($pdo) . ' DAY), CURDATE()) < 0
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
                        WHEN DATE(d.created_at) = CURDATE()
                        THEN 1 ELSE 0
                    END) AS created_today,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND LOWER(COALESCE(d.status, \'\')) LIKE \'%returned%\'
                        THEN 1 ELSE 0
                    END) AS returned_total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\')
                             AND EXISTS (
                                SELECT 1
                                FROM activity_logs al_c
                                WHERE al_c.document_id = d.id
                                  AND LOWER(al_c.action_type) IN (\'completed\', \'released\', \'approved\', \'signed\', \'signed/completed\', \'closed\', \'resolved\', \'done\')
                                  AND DATE(al_c.created_at) = CURDATE()
                             )
                        THEN 1 ELSE 0
                    END) AS completed_today,
                    ' . $externalTodayExpr . ',
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                             AND (
                                 LOWER(COALESCE(d.status, \'\')) LIKE \'%approval%\'
                                 OR LOWER(COALESCE(ts_latest.action_required, \'\')) LIKE \'%approval%\'
                             )
                        THEN 1 ELSE 0
                    END) AS pending_approval_total,
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
                WHERE ' . $scope['where'];

        $stmt = $pdo->prepare($sql);
        $params = $scope['params'];
        $params['office_id_custody'] = $officeId;
        $params['office_id_current_metric'] = $officeId;
        if ($hasPendingOfficeColumn) {
            $params['office_id_pending_metric'] = $officeId;
        }
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
                            AND LOWER(al.action_type) IN (\'completed\', \'released\', \'approved\', \'signed\', \'closed\', \'resolved\')
                            AND al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        $completionStmt = $pdo->prepare($completionSql);
        $completionStmt->execute($scope['params']);
        $metrics['completed_week'] = (int)($completionStmt->fetchColumn() ?: 0);

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
                     AND al.action_type IN (\'Rerouted\', \'Diverted\', \'Override\', \'Overridden\')
                     AND al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute($scope['params']);
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
                        WHEN \'PROVINCIAL\' THEN 4
                        WHEN \'COMMUNITY\' THEN 5
                        WHEN \'PROTECTED AREA\' THEN 6
                        ELSE 7
                    END,
                    name ASC
                LIMIT ' . $safeLimit;

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
    function dashboard_fetch_regional_office_summary_rows(PDO $pdo, int $limit = 120): array
    {
        $safeLimit = max(5, min($limit, 500));
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $ownerOfficeExpr = $hasPendingOfficeColumn
            ? 'CASE WHEN d.pending_office_id IS NOT NULL AND d.pending_office_id > 0 THEN d.pending_office_id ELSE d.current_office_id END'
            : 'd.current_office_id';
        $deadlineExpr = 'DATE_ADD(d.created_at, INTERVAL ' . dashboard_documents_arta_days_expr($pdo) . ' DAY)';
        $terminalStatuses = "'completed','released','closed','approved','resolved','done','signed','signed/completed','cancelled','canceled'";

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
                             AND DATEDIFF(' . $deadlineExpr . ', CURDATE()) = 1
                        THEN 1 ELSE 0
                    END) AS due_soon_count,
                    SUM(CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) NOT IN (' . $terminalStatuses . ')
                             AND ' . $deadlineExpr . ' < CURDATE()
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
                GROUP BY o.id, o.name, o.level
                ORDER BY pending_count DESC, overdue_count DESC, due_soon_count DESC, o.name ASC
                LIMIT ' . $safeLimit;

        $stmt = $pdo->query($sql);
        $rows = $stmt ? ($stmt->fetchAll() ?: []) : [];
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

if (!function_exists('dashboard_fetch_activity_counters')) {
    function dashboard_fetch_activity_counters(PDO $pdo, int $officeId, int $windowDays = 30): array
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
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $scope = dashboard_build_office_scope($officeId, $hasPendingOfficeColumn);

        $sql = 'SELECT
                    SUM(CASE WHEN LOWER(al.action_type) = \'received\' THEN 1 ELSE 0 END) AS received_events,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'approved\', \'approve\') THEN 1 ELSE 0 END) AS approved_events,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'forwarded\', \'forward\') THEN 1 ELSE 0 END) AS forwarded_events,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'rerouted\', \'reroute\', \'overridden\', \'override\') THEN 1 ELSE 0 END) AS reroute_events,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'returned\', \'return\') THEN 1 ELSE 0 END) AS returned_events
                FROM activity_logs al
                INNER JOIN documents d ON d.id = al.document_id
                WHERE ' . $scope['where'] . '
                  AND al.created_at >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($scope['params']);
        $row = $stmt->fetch() ?: [];

        $counters = $defaults;
        foreach (array_keys($defaults) as $key) {
            $counters[$key] = (int)($row[$key] ?? 0);
        }

        return $counters;
    }
}

if (!function_exists('dashboard_fetch_notifications')) {
    function dashboard_fetch_notifications(PDO $pdo, int $officeId, int $limit = 12, ?string $roleName = null): array
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
        if (in_array($roleKey, ['CENRO', 'PENRO'], true)) {
            // CENRO/PENRO: full lifecycle notifications only for documents originated by their office.
            $whereClause = 'd.originating_office_id = :office_id_origin_notifications';
            $params = [
                'office_id_origin_notifications' => $officeId,
            ];
        } elseif (in_array($roleKey, ['RECORDS_UNIT', 'ORED', 'DIVISION_CHIEF', 'SECTION_STAFF', 'ARD_TS', 'ARD_MS'], true)) {
            // RECORDS-UNIT/ORED/Division/Section: alert only for newly incoming routed documents.
            $whereClause = "al.destination_office_id = :office_id_destination_notifications
                            AND UPPER(COALESCE(al.action_type, '')) IN (
                                'FORWARDED', 'FORWARD', 'REROUTED', 'REROUTE',
                                'OVERRIDDEN', 'OVERRIDE', 'RETURNED', 'RETURN',
                                'RELEASED', 'RELEASE'
                            )";
            $params = [
                'office_id_destination_notifications' => $officeId,
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
                    (
                        SELECT al_color.remarks
                        FROM activity_logs al_color
                        WHERE al_color.document_id = d.id
                          AND LOWER(COALESCE(al_color.remarks, \'\')) LIKE \'%color classification:%\'
                        ORDER BY al_color.id DESC
                        LIMIT 1
                    ) AS color_classification_remarks
                FROM activity_logs al
                INNER JOIN documents d ON d.id = al.document_id
                LEFT JOIN offices dest ON dest.id = al.destination_office_id
                WHERE ' . $whereClause . '
                ORDER BY al.created_at DESC, al.id DESC
                LIMIT ' . $safeLimit;

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
    function dashboard_fetch_arta_distribution(PDO $pdo, int $officeId): array
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
                  AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'released\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')
                GROUP BY LOWER(COALESCE(' . dashboard_documents_arta_category_expr($pdo) . ', \'\'))';

        $stmt = $pdo->prepare($sql);
        $params = $scope['params'];
        $params['office_id_custody'] = $officeId;
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
    function dashboard_fetch_pending_receive_rows(PDO $pdo, int $officeId, int $limit = 60, bool $includeInOfficeActionStage = false): array
    {
        if ($officeId <= 0) {
            return [];
        }
        $safeLimit = max(1, min($limit, 200));
        $hasPending = dashboard_column_exists($pdo, 'documents', 'pending_office_id');

        if ($hasPending) {
            if ($includeInOfficeActionStage) {
                // Office-action variant: keep row visible after Receive/Approve,
                // and also after Sign (for ORED post-sign forward to RECORDS-UNIT),
                // until it is forwarded out.
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
                            )';
                $params = [
                    'office_id_pending_incoming' => $officeId,
                    'office_id_current_incoming' => $officeId,
                    'office_id_current_action' => $officeId,
                    'office_id_pending_action' => $officeId,
                    'office_id_custody' => $officeId,
                ];
            } else {
                // Docs awaiting receipt: routed to this office but not yet in custody
                $whereClause = 'd.pending_office_id = :office_id_pending
                               AND d.current_office_id <> :office_id_current
                               AND LOWER(COALESCE(d.status, \'\')) NOT IN (\'completed\', \'closed\', \'approved\', \'resolved\', \'done\', \'signed\', \'signed/completed\', \'cancelled\', \'canceled\')';
                $params = [
                    'office_id_pending' => $officeId,
                    'office_id_current' => $officeId,
                    'office_id_custody' => $officeId,
                ];
            }
        } else {
            // Fallback: docs currently owned but status suggests incoming
            $whereClause = 'd.current_office_id = :office_id_current
                           AND LOWER(COALESCE(d.status, \'\')) IN (\'pending receive\', \'for receive\', \'in transit\', \'routed\', \'received\', \'recieved\', \'approved\', \'signed\', \'signed/completed\')';
            $params = [
                'office_id_current' => $officeId,
                'office_id_custody' => $officeId,
            ];
        }

        return dashboard_cenro_run_filtered_query($pdo, $whereClause, $params, $safeLimit);
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
    function dashboard_cenro_run_filtered_query(PDO $pdo, string $whereClause, array $params, int $safeLimit): array
    {
        $hasPendingOfficeColumn = dashboard_column_exists($pdo, 'documents', 'pending_office_id');
        $pendingOfficeSelect = $hasPendingOfficeColumn
            ? 'd.pending_office_id AS pending_office_id,'
            : 'NULL AS pending_office_id,';

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
                    (
                        SELECT al_last.remarks
                        FROM activity_logs al_last
                        WHERE al_last.document_id = d.id
                          AND TRIM(COALESCE(al_last.remarks, \'\')) <> \'\'
                        ORDER BY al_last.id DESC
                        LIMIT 1
                    ) AS last_remarks,
                    COALESCE(office_ts.office_received_at, d.created_at) AS queue_received_at,
                    DATE_ADD(d.created_at, INTERVAL ' . dashboard_documents_arta_days_expr($pdo) . ' DAY) AS deadline_at,
                    COALESCE(o_current.name, \'-\') AS current_holder,
                    COALESCE(o_pending.name, \'\') AS pending_holder,
                    COALESCE(o_origin.name, \'-\') AS origin_office,
                    EXISTS(
                        SELECT 1
                        FROM activity_logs al_signed
                        WHERE al_signed.document_id = d.id
                          AND LOWER(COALESCE(al_signed.action_type, \'\')) = \'signed\'
                    ) AS has_signed_action,
                    EXISTS(
                        SELECT 1
                        FROM tracking_slips ts_sec
                        INNER JOIN offices o_sec ON o_sec.id = ts_sec.receiving_office_id
                        WHERE ts_sec.document_id = d.id
                          AND UPPER(COALESCE(o_sec.level, \'\')) = \'SECTION\'
                    ) AS has_section_receive
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
                ORDER BY
                    CASE
                        WHEN LOWER(COALESCE(d.status, \'\')) = \'overdue\' THEN 3
                        WHEN LOWER(COALESCE(d.status, \'\')) IN (\'due soon\', \'at-risk\', \'at risk\') THEN 2
                        ELSE 1
                    END DESC,
                    queue_received_at DESC,
                    d.id DESC
                LIMIT ' . $safeLimit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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
            $subject = trim((string)($row['subject'] ?? '')) ?: 'No subject';
            $status  = trim((string)($row['status'] ?? 'Pending')) ?: 'Pending';
            $documentType = trim((string)($row['document_type'] ?? 'Uncategorized')) ?: 'Uncategorized';
            $artaCategory = trim((string)($row['arta_category'] ?? ''));
            $documentTypeLabel = $artaCategory !== ''
                ? $documentType . ' (' . $artaCategory . ')'
                : $documentType;

            $mapped[] = [
                'document_id'       => (int)($row['id'] ?? 0),
                'row_version'       => max(1, (int)($row['row_version'] ?? 1)),
                'tracking_id'       => $trackingId,
                'subject'           => $subject,
                'status'            => $status,
                'source_type'       => strtoupper(trim((string)($row['source_type'] ?? 'INTERNAL'))),
                'origin_office_id'  => (int)($row['originating_office_id'] ?? 0),
                'current_office_id' => (int)($row['current_office_id'] ?? 0),
                'pending_office_id' => (int)($row['pending_office_id'] ?? 0),
                'last_remarks'      => trim((string)($row['last_remarks'] ?? '')),
                'has_signed_action' => (int)($row['has_signed_action'] ?? 0) > 0 ? 1 : 0,
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

/* -----------------------------------------------------------------------
 * End CENRO-specific filters
 * ----------------------------------------------------------------------- */

if (!function_exists('dashboard_fetch_workflow_trend')) {
    function dashboard_fetch_workflow_trend(PDO $pdo, int $officeId, int $days = 7): array
    {
        $safeDays = max(5, min($days, 31));
        $today = new DateTimeImmutable('today');
        $start = $today->sub(new DateInterval('P' . ($safeDays - 1) . 'D'));

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
        $startAt = $start->format('Y-m-d 00:00:00');

        $sql = 'SELECT
                    DATE(al.created_at) AS activity_day,
                    SUM(CASE WHEN LOWER(al.action_type) = \'received\' THEN 1 ELSE 0 END) AS received_count,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'forwarded\', \'forward\', \'rerouted\', \'reroute\', \'released\', \'overridden\', \'override\') THEN 1 ELSE 0 END) AS forwarded_count,
                    SUM(CASE WHEN LOWER(al.action_type) IN (\'approved\', \'signed\', \'completed\', \'released\', \'closed\', \'resolved\', \'done\') THEN 1 ELSE 0 END) AS completed_count
                FROM activity_logs al
                INNER JOIN documents d ON d.id = al.document_id
                WHERE ' . $scope['where'] . '
                  AND al.created_at >= :start_at
                GROUP BY DATE(al.created_at)
                ORDER BY activity_day ASC';

        $stmt = $pdo->prepare($sql);
        $params = $scope['params'];
        $params['start_at'] = $startAt;
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
