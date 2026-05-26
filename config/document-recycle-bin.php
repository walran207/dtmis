<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (!function_exists('document_recycle_bin_table_name')) {
    function document_recycle_bin_table_name(): string
    {
        return 'deleted_documents_archive';
    }
}

if (!function_exists('document_recycle_bin_ensure_table')) {
    function document_recycle_bin_ensure_table(PDO $pdo): void
    {
        static $ensured = [];

        $cacheKey = spl_object_hash($pdo);
        if (isset($ensured[$cacheKey])) {
            return;
        }

        $tableName = document_recycle_bin_table_name();
        if (!db_table_exists($pdo, $tableName)) {
            if (db_is_sql_server($pdo)) {
                $pdo->exec(
                    "IF OBJECT_ID(N'dbo.{$tableName}', N'U') IS NULL
                     BEGIN
                         CREATE TABLE dbo.{$tableName} (
                             id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                             original_document_id INT NOT NULL,
                             tracking_id NVARCHAR(255) NOT NULL,
                             subject NVARCHAR(MAX) NOT NULL,
                             deleted_at DATETIME2 NOT NULL CONSTRAINT DF_{$tableName}_deleted_at DEFAULT SYSDATETIME(),
                             deleted_by_user_id INT NULL,
                             document_payload_json NVARCHAR(MAX) NOT NULL,
                             attachments_payload_json NVARCHAR(MAX) NULL,
                             activity_logs_payload_json NVARCHAR(MAX) NULL,
                             tracking_slips_payload_json NVARCHAR(MAX) NULL
                         );
                         CREATE UNIQUE INDEX UQ_{$tableName}_original_document_id ON dbo.{$tableName}(original_document_id);
                         CREATE INDEX IDX_{$tableName}_deleted_at ON dbo.{$tableName}(deleted_at DESC);
                     END"
                );
            } else {
                $pdo->exec(
                    "CREATE TABLE IF NOT EXISTS {$tableName} (
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        original_document_id INT NOT NULL,
                        tracking_id VARCHAR(255) NOT NULL,
                        subject LONGTEXT NOT NULL,
                        deleted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        deleted_by_user_id INT NULL,
                        document_payload_json LONGTEXT NOT NULL,
                        attachments_payload_json LONGTEXT NULL,
                        activity_logs_payload_json LONGTEXT NULL,
                        tracking_slips_payload_json LONGTEXT NULL,
                        UNIQUE KEY uq_original_document_id (original_document_id)
                    )"
                );
            }
        }

        $ensured[$cacheKey] = true;
    }
}

if (!function_exists('document_recycle_bin_json_encode')) {
    function document_recycle_bin_json_encode(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded) || $encoded === false) {
            throw new RuntimeException('Unable to encode recycle bin payload.');
        }

        return $encoded;
    }
}

if (!function_exists('document_recycle_bin_json_decode_rows')) {
    function document_recycle_bin_json_decode_rows(?string $value): array
    {
        $trimmed = trim((string)$value);
        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('document_recycle_bin_identity_column')) {
    function document_recycle_bin_identity_column(PDO $pdo, string $table): ?string
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

if (!function_exists('document_recycle_bin_table_rows')) {
    function document_recycle_bin_table_rows(PDO $pdo, string $table, string $whereSql, array $params): array
    {
        if (!db_table_exists($pdo, $table)) {
            return [];
        }

        $stmt = $pdo->prepare("SELECT * FROM {$table} {$whereSql}");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('document_recycle_bin_user_exists')) {
    function document_recycle_bin_user_exists(PDO $pdo, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT TOP (1) 1 FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);

        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('document_recycle_bin_office_exists')) {
    function document_recycle_bin_office_exists(PDO $pdo, int $officeId): bool
    {
        if ($officeId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT TOP (1) 1 FROM offices WHERE id = :id');
        $stmt->execute(['id' => $officeId]);

        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('document_recycle_bin_document_type_exists')) {
    function document_recycle_bin_document_type_exists(PDO $pdo, int $documentTypeId): bool
    {
        if ($documentTypeId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT TOP (1) 1 FROM document_types WHERE id = :id');
        $stmt->execute(['id' => $documentTypeId]);

        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('document_recycle_bin_archive_document')) {
    function document_recycle_bin_archive_document(PDO $pdo, int $documentId, int $deletedByUserId): int
    {
        if ($documentId <= 0) {
            throw new InvalidArgumentException('Document ID is required for recycle bin archive.');
        }

        document_recycle_bin_ensure_table($pdo);

        $existsStmt = $pdo->prepare(
            'SELECT TOP (1) id
             FROM ' . document_recycle_bin_table_name() . '
             WHERE original_document_id = :document_id'
        );
        $existsStmt->execute(['document_id' => $documentId]);
        if ((int)($existsStmt->fetchColumn() ?: 0) > 0) {
            throw new RuntimeException('Document is already in the recycle bin.');
        }

        $documentRows = document_recycle_bin_table_rows(
            $pdo,
            'documents',
            'WHERE id = :document_id',
            ['document_id' => $documentId]
        );
        $document = $documentRows[0] ?? null;
        if (!is_array($document)) {
            throw new RuntimeException('Document not found.');
        }

        $attachments = document_recycle_bin_table_rows(
            $pdo,
            'document_attachments',
            'WHERE document_id = :document_id ORDER BY id ASC',
            ['document_id' => $documentId]
        );
        $activityLogs = document_recycle_bin_table_rows(
            $pdo,
            'activity_logs',
            'WHERE document_id = :document_id ORDER BY id ASC',
            ['document_id' => $documentId]
        );
        $trackingSlips = document_recycle_bin_table_rows(
            $pdo,
            'tracking_slips',
            'WHERE document_id = :document_id ORDER BY id ASC',
            ['document_id' => $documentId]
        );

        $insertStmt = $pdo->prepare(
            'INSERT INTO ' . document_recycle_bin_table_name() . ' (
                original_document_id,
                tracking_id,
                subject,
                deleted_at,
                deleted_by_user_id,
                document_payload_json,
                attachments_payload_json,
                activity_logs_payload_json,
                tracking_slips_payload_json
             ) VALUES (
                :original_document_id,
                :tracking_id,
                :subject,
                SYSDATETIME(),
                :deleted_by_user_id,
                :document_payload_json,
                :attachments_payload_json,
                :activity_logs_payload_json,
                :tracking_slips_payload_json
             )'
        );
        $insertStmt->execute([
            'original_document_id' => $documentId,
            'tracking_id' => (string)($document['tracking_id'] ?? ''),
            'subject' => (string)($document['subject'] ?? ''),
            'deleted_by_user_id' => $deletedByUserId > 0 ? $deletedByUserId : null,
            'document_payload_json' => document_recycle_bin_json_encode($document),
            'attachments_payload_json' => $attachments === [] ? null : document_recycle_bin_json_encode($attachments),
            'activity_logs_payload_json' => $activityLogs === [] ? null : document_recycle_bin_json_encode($activityLogs),
            'tracking_slips_payload_json' => $trackingSlips === [] ? null : document_recycle_bin_json_encode($trackingSlips),
        ]);

        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('document_recycle_bin_fetch_deleted_documents')) {
    function document_recycle_bin_fetch_deleted_documents(PDO $pdo, int $limit = 250): array
    {
        document_recycle_bin_ensure_table($pdo);

        $safeLimit = max(20, min($limit, 1000));
        $stmt = $pdo->query(
            "SELECT TOP ({$safeLimit})
                a.id,
                a.original_document_id,
                a.tracking_id,
                a.subject,
                a.deleted_at,
                a.deleted_by_user_id,
                a.attachments_payload_json,
                COALESCE(u.email, '') AS deleted_by_email,
                COALESCE(u.first_name, '') AS deleted_by_first_name,
                COALESCE(u.last_name, '') AS deleted_by_last_name
             FROM " . document_recycle_bin_table_name() . " a
             LEFT JOIN users u ON u.id = a.deleted_by_user_id
             ORDER BY a.deleted_at DESC, a.id DESC"
        );
        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        return array_map(static function (array $row): array {
            $attachmentRows = document_recycle_bin_json_decode_rows((string)($row['attachments_payload_json'] ?? ''));
            return [
                'archive_id' => (int)($row['id'] ?? 0),
                'original_document_id' => (int)($row['original_document_id'] ?? 0),
                'tracking_id' => trim((string)($row['tracking_id'] ?? '')),
                'subject' => trim((string)($row['subject'] ?? '')),
                'deleted_at' => (string)($row['deleted_at'] ?? ''),
                'deleted_at_label' => function_exists('super_admin_format_datetime')
                    ? super_admin_format_datetime((string)($row['deleted_at'] ?? null))
                    : (string)($row['deleted_at'] ?? ''),
                'deleted_by_user_id' => (int)($row['deleted_by_user_id'] ?? 0),
                'deleted_by_name' => trim((string)($row['deleted_by_first_name'] ?? '') . ' ' . (string)($row['deleted_by_last_name'] ?? '')),
                'deleted_by_email' => trim((string)($row['deleted_by_email'] ?? '')),
                'attachment_count' => count($attachmentRows),
            ];
        }, $rows);
    }
}

if (!function_exists('document_recycle_bin_sanitize_document_row')) {
    function document_recycle_bin_sanitize_document_row(PDO $pdo, array $row): array
    {
        $documentTypeId = (int)($row['document_type_id'] ?? 0);
        if (!document_recycle_bin_document_type_exists($pdo, $documentTypeId)) {
            throw new RuntimeException('Cannot restore document because its document type no longer exists.');
        }

        foreach (['originating_office_id', 'current_office_id'] as $requiredOfficeColumn) {
            $officeId = (int)($row[$requiredOfficeColumn] ?? 0);
            if (!document_recycle_bin_office_exists($pdo, $officeId)) {
                throw new RuntimeException('Cannot restore document because an office reference no longer exists.');
            }
        }

        foreach (['pending_office_id'] as $optionalOfficeColumn) {
            $officeId = (int)($row[$optionalOfficeColumn] ?? 0);
            if ($officeId > 0 && !document_recycle_bin_office_exists($pdo, $officeId)) {
                $row[$optionalOfficeColumn] = null;
            }
        }

        foreach (['created_by_user_id', 'current_holder_user_id', 'pending_user_id'] as $optionalUserColumn) {
            $userId = (int)($row[$optionalUserColumn] ?? 0);
            if ($userId > 0 && !document_recycle_bin_user_exists($pdo, $userId)) {
                $row[$optionalUserColumn] = null;
            }
        }

        return $row;
    }
}

if (!function_exists('document_recycle_bin_sanitize_attachment_rows')) {
    function document_recycle_bin_sanitize_attachment_rows(PDO $pdo, array $rows, int $fallbackUserId): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $uploadedBy = (int)($row['uploaded_by'] ?? 0);
            if ($uploadedBy <= 0 || !document_recycle_bin_user_exists($pdo, $uploadedBy)) {
                $row['uploaded_by'] = $fallbackUserId;
            }

            $result[] = $row;
        }

        return $result;
    }
}

if (!function_exists('document_recycle_bin_sanitize_activity_log_rows')) {
    function document_recycle_bin_sanitize_activity_log_rows(PDO $pdo, array $rows, int $fallbackUserId): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $userId = (int)($row['user_id'] ?? 0);
            if ($userId <= 0 || !document_recycle_bin_user_exists($pdo, $userId)) {
                $row['user_id'] = $fallbackUserId;
            }

            $destinationUserId = (int)($row['destination_user_id'] ?? 0);
            if ($destinationUserId > 0 && !document_recycle_bin_user_exists($pdo, $destinationUserId)) {
                $row['destination_user_id'] = null;
            }

            $destinationOfficeId = (int)($row['destination_office_id'] ?? 0);
            if ($destinationOfficeId > 0 && !document_recycle_bin_office_exists($pdo, $destinationOfficeId)) {
                $row['destination_office_id'] = null;
            }

            $result[] = $row;
        }

        return $result;
    }
}

if (!function_exists('document_recycle_bin_sanitize_tracking_slip_rows')) {
    function document_recycle_bin_sanitize_tracking_slip_rows(PDO $pdo, array $rows, int $fallbackUserId): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach (['from_office_id', 'receiving_office_id'] as $officeColumn) {
                $officeId = (int)($row[$officeColumn] ?? 0);
                if (!document_recycle_bin_office_exists($pdo, $officeId)) {
                    throw new RuntimeException('Cannot restore document because a tracking slip office no longer exists.');
                }
            }

            $receivedBy = (int)($row['received_by'] ?? 0);
            if ($receivedBy <= 0 || !document_recycle_bin_user_exists($pdo, $receivedBy)) {
                $row['received_by'] = $fallbackUserId;
            }

            $result[] = $row;
        }

        return $result;
    }
}

if (!function_exists('document_recycle_bin_restore_rows')) {
    function document_recycle_bin_restore_rows(PDO $pdo, string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $identityColumn = document_recycle_bin_identity_column($pdo, $table);
        $hasIdentityValues = false;
        if ($identityColumn !== null) {
            foreach ($rows as $row) {
                if (is_array($row) && array_key_exists($identityColumn, $row) && $row[$identityColumn] !== null) {
                    $hasIdentityValues = true;
                    break;
                }
            }
        }

        if ($hasIdentityValues && db_is_sql_server($pdo)) {
            $pdo->exec("SET IDENTITY_INSERT dbo.{$table} ON");
        }

        try {
            foreach ($rows as $row) {
                if (!is_array($row) || $row === []) {
                    continue;
                }

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
            }
        } finally {
            if ($hasIdentityValues && db_is_sql_server($pdo)) {
                $pdo->exec("SET IDENTITY_INSERT dbo.{$table} OFF");
            }
        }
    }
}

if (!function_exists('document_recycle_bin_restore_document')) {
    function document_recycle_bin_restore_document(PDO $pdo, int $archiveId, int $restoredByUserId): array
    {
        if ($archiveId <= 0) {
            throw new InvalidArgumentException('Recycle bin entry is required.');
        }

        if ($restoredByUserId <= 0 || !document_recycle_bin_user_exists($pdo, $restoredByUserId)) {
            throw new RuntimeException('A valid restoring user is required.');
        }

        document_recycle_bin_ensure_table($pdo);

        $archiveStmt = $pdo->prepare(
            'SELECT TOP (1) *
             FROM ' . document_recycle_bin_table_name() . ' WITH (UPDLOCK, ROWLOCK)
             WHERE id = :id'
        );
        $archiveStmt->execute(['id' => $archiveId]);
        $archiveRow = $archiveStmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($archiveRow)) {
            throw new RuntimeException('Recycle bin entry not found.');
        }

        $documentRows = document_recycle_bin_json_decode_rows((string)($archiveRow['document_payload_json'] ?? ''));
        $documentRow = is_array($documentRows) && array_keys($documentRows) !== range(0, count($documentRows) - 1)
            ? $documentRows
            : [];
        if ($documentRow === []) {
            throw new RuntimeException('Recycle bin payload is incomplete.');
        }

        $documentRow = document_recycle_bin_sanitize_document_row($pdo, $documentRow);
        $attachments = document_recycle_bin_sanitize_attachment_rows(
            $pdo,
            document_recycle_bin_json_decode_rows((string)($archiveRow['attachments_payload_json'] ?? '')),
            $restoredByUserId
        );
        $activityLogs = document_recycle_bin_sanitize_activity_log_rows(
            $pdo,
            document_recycle_bin_json_decode_rows((string)($archiveRow['activity_logs_payload_json'] ?? '')),
            $restoredByUserId
        );
        $trackingSlips = document_recycle_bin_sanitize_tracking_slip_rows(
            $pdo,
            document_recycle_bin_json_decode_rows((string)($archiveRow['tracking_slips_payload_json'] ?? '')),
            $restoredByUserId
        );

        $originalDocumentId = (int)($documentRow['id'] ?? 0);
        if ($originalDocumentId <= 0) {
            throw new RuntimeException('Recycle bin document ID is invalid.');
        }

        $documentExistsStmt = $pdo->prepare('SELECT TOP (1) 1 FROM documents WHERE id = :id');
        $documentExistsStmt->execute(['id' => $originalDocumentId]);
        if ((bool)$documentExistsStmt->fetchColumn()) {
            throw new RuntimeException('Cannot restore because that document ID already exists.');
        }

        $trackingId = trim((string)($documentRow['tracking_id'] ?? ''));
        if ($trackingId !== '') {
            $trackingStmt = $pdo->prepare('SELECT TOP (1) 1 FROM documents WHERE tracking_id = :tracking_id');
            $trackingStmt->execute(['tracking_id' => $trackingId]);
            if ((bool)$trackingStmt->fetchColumn()) {
                throw new RuntimeException('Cannot restore because the tracking ID is already in use.');
            }
        }

        document_recycle_bin_restore_rows($pdo, 'documents', [$documentRow]);
        document_recycle_bin_restore_rows($pdo, 'document_attachments', $attachments);
        document_recycle_bin_restore_rows($pdo, 'activity_logs', $activityLogs);
        document_recycle_bin_restore_rows($pdo, 'tracking_slips', $trackingSlips);

        $deleteArchiveStmt = $pdo->prepare(
            'DELETE FROM ' . document_recycle_bin_table_name() . '
             WHERE id = :id'
        );
        $deleteArchiveStmt->execute(['id' => $archiveId]);

        return [
            'document_id' => $originalDocumentId,
            'tracking_id' => $trackingId,
            'subject' => trim((string)($documentRow['subject'] ?? '')),
        ];
    }
}
