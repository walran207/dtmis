<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

if (!function_exists('dtr_table_exists')) {
    function dtr_table_exists(PDO $pdo): bool
    {
        return db_table_exists($pdo, 'document_type_requests');
    }
}

if (!function_exists('dtr_user_context')) {
    function dtr_user_context(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare(
            'SELECT TOP (1) u.id, u.office_id, u.role_id, u.is_active, r.name AS role_name, o.name AS office_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN offices o ON o.id = u.office_id
             WHERE u.id = :id'
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user || (int)($user['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('User not found or inactive.');
        }
        return $user;
    }
}

if (!function_exists('dtr_is_requester_role')) {
    function dtr_is_requester_role(string $roleName): bool
    {
        $key = app_normalize_role_key($roleName);
        return in_array($key, ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD', 'PAMO_ADMIN'], true);
    }
}

if (!function_exists('dtr_is_reviewer_role')) {
    function dtr_is_reviewer_role(string $roleName): bool
    {
        $key = app_normalize_role_key($roleName);
        return $key === 'RECORDS_UNIT';
    }
}

if (!function_exists('dtr_category_defaults')) {
    function dtr_category_defaults(string $categoryRaw): array
    {
        $normalized = strtolower(trim(str_replace(['_', '-'], ' ', $categoryRaw)));
        if (str_contains($normalized, 'highly') || str_contains($normalized, 'technical')) {
            return [
                'category' => 'Highly Technical',
                'days' => 20,
                'color' => 'Red',
            ];
        }
        if (str_contains($normalized, 'complex')) {
            return [
                'category' => 'Complex',
                'days' => 7,
                'color' => 'Pink',
            ];
        }
        return [
            'category' => 'Simple',
            'days' => 3,
            'color' => 'Yellow',
        ];
    }
}

if (!function_exists('dtr_fetch_summary')) {
    function dtr_fetch_summary(PDO $pdo, ?int $requestedByUserId = null): array
    {
        $summary = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        if (!dtr_table_exists($pdo)) {
            return $summary;
        }

        $sql = 'SELECT
                    COUNT(*) AS total_count,
                    SUM(CASE WHEN UPPER(COALESCE(status, \'PENDING\')) = \'PENDING\' THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN UPPER(COALESCE(status, \'\')) = \'APPROVED\' THEN 1 ELSE 0 END) AS approved_count,
                    SUM(CASE WHEN UPPER(COALESCE(status, \'\')) = \'REJECTED\' THEN 1 ELSE 0 END) AS rejected_count
                FROM document_type_requests';
        $params = [];
        if ($requestedByUserId !== null && $requestedByUserId > 0) {
            $sql .= ' WHERE requested_by_user_id = :requested_by_user_id';
            $params['requested_by_user_id'] = $requestedByUserId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        $summary['total'] = (int)($row['total_count'] ?? 0);
        $summary['pending'] = (int)($row['pending_count'] ?? 0);
        $summary['approved'] = (int)($row['approved_count'] ?? 0);
        $summary['rejected'] = (int)($row['rejected_count'] ?? 0);

        return $summary;
    }
}

if (!function_exists('dtr_fetch_requests')) {
    function dtr_fetch_requests(PDO $pdo, ?int $requestedByUserId = null, int $limit = 600): array
    {
        if (!dtr_table_exists($pdo)) {
            return [];
        }

        $safeLimit = max(1, min(1000, $limit));
        $sql = 'SELECT dtr.*,
                       TRIM(CONCAT(COALESCE(req.first_name, \'\'), \' \', COALESCE(req.last_name, \'\'))) AS requester_name,
                       rl.name AS requester_role,
                       o.name AS requester_office_name
                FROM document_type_requests dtr
                LEFT JOIN users req ON req.id = dtr.requested_by_user_id
                LEFT JOIN roles rl ON rl.id = req.role_id
                LEFT JOIN offices o ON o.id = dtr.requested_by_office_id';
        $params = [];
        if ($requestedByUserId !== null && $requestedByUserId > 0) {
            $sql .= ' WHERE dtr.requested_by_user_id = :requested_by_user_id';
            $params['requested_by_user_id'] = $requestedByUserId;
        }

        $sql .= '
            ORDER BY
                CASE dtr.status
                    WHEN \'PENDING\' THEN 0
                    WHEN \'REJECTED\' THEN 1
                    ELSE 2
                END ASC,
                dtr.created_at DESC,
                dtr.id DESC
            OFFSET 0 ROWS FETCH NEXT ' . $safeLimit . ' ROWS ONLY';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }
}

if (!function_exists('dtr_build_live_payload')) {
    function dtr_build_live_payload(PDO $pdo, array $actor, int $limit = 600): array
    {
        $actorRole = (string)($actor['role_name'] ?? '');
        $actorUserId = (int)($actor['id'] ?? 0);
        $isReviewer = dtr_is_reviewer_role($actorRole);
        $isRequester = dtr_is_requester_role($actorRole);

        if (!$isReviewer && !$isRequester) {
            throw new RuntimeException('You are not allowed to access document type requests.');
        }

        $requestedByUserId = $isReviewer ? null : $actorUserId;
        $requests = dtr_fetch_requests($pdo, $requestedByUserId, $limit);
        $summary = dtr_fetch_summary($pdo, $requestedByUserId);

        $snapshotRows = array_map(
            static function (array $request): array {
                return [
                    (int)($request['id'] ?? 0),
                    (string)($request['status'] ?? ''),
                    (string)($request['updated_at'] ?? ''),
                    (string)($request['reviewed_at'] ?? ''),
                ];
            },
            $requests
        );

        return [
            'mode' => $isReviewer ? 'reviewer' : 'requester',
            'summary' => $summary,
            'requests' => $requests,
            'snapshot_token' => sha1((string)json_encode([
                'summary' => $summary,
                'rows' => $snapshotRows,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        ];
    }
}
