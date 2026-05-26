<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (!function_exists('attachment_binary_backup_table_name')) {
    function attachment_binary_backup_table_name(): string
    {
        return 'document_attachment_binary_backups';
    }
}

if (!function_exists('attachment_binary_backup_is_image_filename')) {
    function attachment_binary_backup_is_image_filename(string $fileName): bool
    {
        $extension = strtolower((string)pathinfo(trim($fileName), PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
    }
}

if (!function_exists('attachment_binary_backup_detect_mime_type')) {
    function attachment_binary_backup_detect_mime_type(string $absolutePath): string
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return '';
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return strtolower(trim((string)$finfo->file($absolutePath)));
    }
}

if (!function_exists('attachment_binary_backup_should_store')) {
    function attachment_binary_backup_should_store(string $fileName, string $mimeType = ''): bool
    {
        if ($mimeType !== '' && str_starts_with(strtolower($mimeType), 'image/')) {
            return true;
        }

        return attachment_binary_backup_is_image_filename($fileName);
    }
}

if (!function_exists('attachment_binary_backup_is_valid_image_file')) {
    function attachment_binary_backup_is_valid_image_file(string $absolutePath): bool
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return false;
        }

        $size = @filesize($absolutePath);
        if (!is_int($size) && !is_float($size)) {
            return false;
        }
        if ((int)$size <= 0) {
            return false;
        }

        return @getimagesize($absolutePath) !== false;
    }
}

if (!function_exists('attachment_binary_backup_ensure_table')) {
    function attachment_binary_backup_ensure_table(PDO $pdo): void
    {
        static $ensured = [];

        $cacheKey = spl_object_hash($pdo);
        if (isset($ensured[$cacheKey])) {
            return;
        }

        $tableName = attachment_binary_backup_table_name();
        if (!db_table_exists($pdo, $tableName)) {
            if (db_is_sql_server($pdo)) {
                $pdo->exec(
                    "IF OBJECT_ID(N'dbo.{$tableName}', N'U') IS NULL
                     BEGIN
                         CREATE TABLE dbo.{$tableName} (
                             attachment_id INT NOT NULL PRIMARY KEY,
                             document_id INT NOT NULL,
                             file_name NVARCHAR(255) NOT NULL,
                             mime_type NVARCHAR(255) NULL,
                             byte_size BIGINT NOT NULL,
                             binary_content VARBINARY(MAX) NOT NULL,
                             created_at DATETIME2 NOT NULL CONSTRAINT DF_{$tableName}_created_at DEFAULT SYSDATETIME(),
                             updated_at DATETIME2 NOT NULL CONSTRAINT DF_{$tableName}_updated_at DEFAULT SYSDATETIME()
                         );
                         CREATE INDEX IDX_{$tableName}_document_id ON dbo.{$tableName}(document_id);
                     END"
                );
            } else {
                $pdo->exec(
                    "CREATE TABLE IF NOT EXISTS {$tableName} (
                        attachment_id INT NOT NULL PRIMARY KEY,
                        document_id INT NOT NULL,
                        file_name VARCHAR(255) NOT NULL,
                        mime_type VARCHAR(255) NULL,
                        byte_size BIGINT NOT NULL,
                        binary_content LONGBLOB NOT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    )"
                );
            }
        }

        $ensured[$cacheKey] = true;
    }
}

if (!function_exists('attachment_binary_backup_resolve_attachment_id')) {
    function attachment_binary_backup_resolve_attachment_id(PDO $pdo, int $documentId, int $versionNumber, string $relativePath = ''): int
    {
        $lastInsertId = (int)$pdo->lastInsertId();
        if ($lastInsertId > 0) {
            return $lastInsertId;
        }

        $sql = 'SELECT TOP (1) id
                FROM document_attachments
                WHERE document_id = :document_id
                  AND version_number = :version_number';
        $params = [
            'document_id' => $documentId,
            'version_number' => $versionNumber,
        ];

        if ($relativePath !== '') {
            $sql .= ' AND file_path = :file_path';
            $params['file_path'] = $relativePath;
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int)($stmt->fetchColumn() ?: 0);
    }
}

if (!function_exists('attachment_binary_backup_exists')) {
    function attachment_binary_backup_exists(PDO $pdo, int $attachmentId): bool
    {
        if ($attachmentId <= 0 || !db_table_exists($pdo, attachment_binary_backup_table_name())) {
            return false;
        }

        $stmt = $pdo->prepare(
            'SELECT 1
             FROM ' . attachment_binary_backup_table_name() . '
             WHERE attachment_id = :attachment_id'
        );
        $stmt->execute(['attachment_id' => $attachmentId]);

        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('attachment_binary_backup_bind_binary_param')) {
    function attachment_binary_backup_bind_binary_param(PDO $pdo, PDOStatement $statement, string $parameter, string $binaryContent): mixed
    {
        if (db_is_sql_server($pdo)) {
            $stream = fopen('php://temp', 'r+b');
            if (!is_resource($stream)) {
                throw new RuntimeException('Unable to prepare binary attachment stream for SQL Server.');
            }

            fwrite($stream, $binaryContent);
            rewind($stream);

            if (defined('PDO::SQLSRV_ENCODING_BINARY')) {
                $statement->bindParam(
                    $parameter,
                    $stream,
                    PDO::PARAM_LOB,
                    0,
                    constant('PDO::SQLSRV_ENCODING_BINARY')
                );
            } else {
                $statement->bindParam($parameter, $stream, PDO::PARAM_LOB);
            }

            return $stream;
        }

        $statement->bindValue($parameter, $binaryContent, PDO::PARAM_LOB);
        return null;
    }
}

if (!function_exists('attachment_binary_backup_upsert_from_file')) {
    function attachment_binary_backup_upsert_from_file(
        PDO $pdo,
        int $attachmentId,
        int $documentId,
        string $fileName,
        string $absolutePath
    ): bool {
        if ($attachmentId <= 0 || $documentId <= 0 || trim($fileName) === '' || trim($absolutePath) === '') {
            return false;
        }

        $mimeType = attachment_binary_backup_detect_mime_type($absolutePath);
        if (!attachment_binary_backup_should_store($fileName, $mimeType)) {
            return false;
        }

        $binaryContent = @file_get_contents($absolutePath);
        if (!is_string($binaryContent) || $binaryContent === '') {
            throw new RuntimeException('Unable to read attachment image for database backup.');
        }

        attachment_binary_backup_ensure_table($pdo);

        $tableName = attachment_binary_backup_table_name();
        $byteSize = strlen($binaryContent);

        if (attachment_binary_backup_exists($pdo, $attachmentId)) {
            $updateStmt = $pdo->prepare(
                "UPDATE {$tableName}
                 SET document_id = :document_id,
                     file_name = :file_name,
                     mime_type = :mime_type,
                     byte_size = :byte_size,
                     binary_content = :binary_content,
                     updated_at = SYSDATETIME()
                 WHERE attachment_id = :attachment_id"
            );
            $updateStmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
            $updateStmt->bindValue(':file_name', $fileName, PDO::PARAM_STR);
            $updateStmt->bindValue(':mime_type', $mimeType !== '' ? $mimeType : null, $mimeType !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $updateStmt->bindValue(':byte_size', $byteSize, PDO::PARAM_INT);
            $binaryStream = attachment_binary_backup_bind_binary_param($pdo, $updateStmt, ':binary_content', $binaryContent);
            $updateStmt->bindValue(':attachment_id', $attachmentId, PDO::PARAM_INT);
            $updateStmt->execute();
            if (is_resource($binaryStream)) {
                fclose($binaryStream);
            }

            return true;
        }

        $insertStmt = $pdo->prepare(
            "INSERT INTO {$tableName} (
                attachment_id, document_id, file_name, mime_type, byte_size, binary_content, created_at, updated_at
             ) VALUES (
                :attachment_id, :document_id, :file_name, :mime_type, :byte_size, :binary_content, SYSDATETIME(), SYSDATETIME()
             )"
        );
        $insertStmt->bindValue(':attachment_id', $attachmentId, PDO::PARAM_INT);
        $insertStmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
        $insertStmt->bindValue(':file_name', $fileName, PDO::PARAM_STR);
        $insertStmt->bindValue(':mime_type', $mimeType !== '' ? $mimeType : null, $mimeType !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $insertStmt->bindValue(':byte_size', $byteSize, PDO::PARAM_INT);
        $binaryStream = attachment_binary_backup_bind_binary_param($pdo, $insertStmt, ':binary_content', $binaryContent);
        $insertStmt->execute();
        if (is_resource($binaryStream)) {
            fclose($binaryStream);
        }

        return true;
    }
}

if (!function_exists('attachment_binary_backup_normalize_content')) {
    function attachment_binary_backup_normalize_content(mixed $payload): string
    {
        if (is_resource($payload)) {
            $content = stream_get_contents($payload);
            return is_string($content) ? $content : '';
        }

        return is_string($payload) ? $payload : '';
    }
}

if (!function_exists('attachment_binary_backup_fetch')) {
    function attachment_binary_backup_fetch(PDO $pdo, int $attachmentId): ?array
    {
        if ($attachmentId <= 0 || !db_table_exists($pdo, attachment_binary_backup_table_name())) {
            return null;
        }

        $stmt = $pdo->prepare(
            'SELECT file_name, mime_type, byte_size, binary_content
             FROM ' . attachment_binary_backup_table_name() . '
             WHERE attachment_id = :attachment_id'
        );
        $stmt->execute(['attachment_id' => $attachmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        $row['binary_content'] = attachment_binary_backup_normalize_content($row['binary_content'] ?? '');
        if (!is_string($row['binary_content']) || $row['binary_content'] === '') {
            return null;
        }

        return $row;
    }
}

if (!function_exists('attachment_binary_backup_delete_for_attachment')) {
    function attachment_binary_backup_delete_for_attachment(PDO $pdo, int $attachmentId): void
    {
        if ($attachmentId <= 0 || !db_table_exists($pdo, attachment_binary_backup_table_name())) {
            return;
        }

        $stmt = $pdo->prepare(
            'DELETE FROM ' . attachment_binary_backup_table_name() . '
             WHERE attachment_id = :attachment_id'
        );
        $stmt->execute(['attachment_id' => $attachmentId]);
    }
}

if (!function_exists('attachment_binary_backup_delete_for_document')) {
    function attachment_binary_backup_delete_for_document(PDO $pdo, int $documentId): void
    {
        if ($documentId <= 0 || !db_table_exists($pdo, attachment_binary_backup_table_name())) {
            return;
        }

        $stmt = $pdo->prepare(
            'DELETE FROM ' . attachment_binary_backup_table_name() . '
             WHERE document_id = :document_id'
        );
        $stmt->execute(['document_id' => $documentId]);
    }
}
