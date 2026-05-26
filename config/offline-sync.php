<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

if (!function_exists('offline_sync_log_ensure_table')) {
    function offline_sync_log_ensure_table(PDO $pdo): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        if (db_is_sql_server($pdo)) {
            $pdo->exec(
                "IF OBJECT_ID(N'dbo.offline_sync_logs', N'U') IS NULL
                 BEGIN
                     CREATE TABLE dbo.offline_sync_logs (
                         id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                         user_id INT NULL,
                         office_id INT NULL,
                         role_key NVARCHAR(48) NOT NULL CONSTRAINT DF_offline_sync_logs_role_key DEFAULT '',
                         event_type NVARCHAR(64) NOT NULL,
                         route_kind NVARCHAR(64) NOT NULL CONSTRAINT DF_offline_sync_logs_route_kind DEFAULT '',
                         action_name NVARCHAR(64) NOT NULL CONSTRAINT DF_offline_sync_logs_action_name DEFAULT '',
                         operation_id NVARCHAR(80) NOT NULL CONSTRAINT DF_offline_sync_logs_operation_id DEFAULT '',
                         request_url NVARCHAR(255) NOT NULL CONSTRAINT DF_offline_sync_logs_request_url DEFAULT '',
                         http_status SMALLINT NULL,
                         attempt_count INT NOT NULL CONSTRAINT DF_offline_sync_logs_attempt_count DEFAULT 0,
                         queue_pending INT NOT NULL CONSTRAINT DF_offline_sync_logs_queue_pending DEFAULT 0,
                         queue_failed INT NOT NULL CONSTRAINT DF_offline_sync_logs_queue_failed DEFAULT 0,
                         queue_blocked INT NOT NULL CONSTRAINT DF_offline_sync_logs_queue_blocked DEFAULT 0,
                         source NVARCHAR(32) NOT NULL CONSTRAINT DF_offline_sync_logs_source DEFAULT 'client',
                         message NVARCHAR(255) NOT NULL CONSTRAINT DF_offline_sync_logs_message DEFAULT '',
                         payload_json NVARCHAR(MAX) NULL,
                         created_at DATETIME2 NOT NULL CONSTRAINT DF_offline_sync_logs_created_at DEFAULT SYSDATETIME()
                     );
                     CREATE INDEX idx_offline_sync_logs_created ON dbo.offline_sync_logs (created_at);
                     CREATE INDEX idx_offline_sync_logs_user_created ON dbo.offline_sync_logs (user_id, created_at);
                     CREATE INDEX idx_offline_sync_logs_event_created ON dbo.offline_sync_logs (event_type, created_at);
                 END"
            );
        } else {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS `offline_sync_logs` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `user_id` INT NULL,
                    `office_id` INT NULL,
                    `role_key` VARCHAR(48) NOT NULL DEFAULT '',
                    `event_type` VARCHAR(64) NOT NULL,
                    `route_kind` VARCHAR(64) NOT NULL DEFAULT '',
                    `action_name` VARCHAR(64) NOT NULL DEFAULT '',
                    `operation_id` VARCHAR(80) NOT NULL DEFAULT '',
                    `request_url` VARCHAR(255) NOT NULL DEFAULT '',
                    `http_status` SMALLINT UNSIGNED NULL,
                    `attempt_count` INT UNSIGNED NOT NULL DEFAULT 0,
                    `queue_pending` INT UNSIGNED NOT NULL DEFAULT 0,
                    `queue_failed` INT UNSIGNED NOT NULL DEFAULT 0,
                    `queue_blocked` INT UNSIGNED NOT NULL DEFAULT 0,
                    `source` VARCHAR(32) NOT NULL DEFAULT 'client',
                    `message` VARCHAR(255) NOT NULL DEFAULT '',
                    `payload_json` MEDIUMTEXT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_offline_sync_logs_created` (`created_at`),
                    KEY `idx_offline_sync_logs_user_created` (`user_id`, `created_at`),
                    KEY `idx_offline_sync_logs_event_created` (`event_type`, `created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }

        $ensured = true;
    }
}

if (!function_exists('offline_sync_truncate')) {
    function offline_sync_truncate(string $value, int $maxLength): string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || $maxLength < 1) {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($trimmed, 'UTF-8') > $maxLength) {
                return mb_substr($trimmed, 0, $maxLength, 'UTF-8');
            }

            return $trimmed;
        }

        return strlen($trimmed) > $maxLength ? substr($trimmed, 0, $maxLength) : $trimmed;
    }
}

if (!function_exists('offline_sync_log_event')) {
    function offline_sync_log_event(?PDO $pdo, array $payload): bool
    {
        if (!$pdo instanceof PDO || !app_offline_sync_logging_enabled()) {
            return false;
        }

        try {
            offline_sync_log_ensure_table($pdo);

            $roleKey = app_normalize_role_key((string)($payload['role_key'] ?? ($_SESSION['role_name'] ?? '')));
            $eventType = strtoupper(offline_sync_truncate((string)($payload['event_type'] ?? 'UNKNOWN'), 64));
            if ($eventType === '') {
                $eventType = 'UNKNOWN';
            }

            $httpStatusRaw = (int)($payload['http_status'] ?? 0);
            $httpStatus = $httpStatusRaw > 0 ? max(100, min(599, $httpStatusRaw)) : null;

            $attemptCount = max(0, min(1000000, (int)($payload['attempt_count'] ?? 0)));
            $queuePending = max(0, min(1000000, (int)($payload['queue_pending'] ?? 0)));
            $queueFailed = max(0, min(1000000, (int)($payload['queue_failed'] ?? 0)));
            $queueBlocked = max(0, min(1000000, (int)($payload['queue_blocked'] ?? 0)));

            $extraPayload = $payload['payload'] ?? null;
            if ($extraPayload !== null && !is_array($extraPayload)) {
                $extraPayload = ['value' => $extraPayload];
            }
            $payloadJson = null;
            if (is_array($extraPayload)) {
                $encoded = json_encode($extraPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (is_string($encoded) && $encoded !== '') {
                    $payloadJson = $encoded;
                }
            }

            $stmt = $pdo->prepare(
                'INSERT INTO offline_sync_logs (
                    user_id, office_id, role_key, event_type, route_kind, action_name, operation_id,
                    request_url, http_status, attempt_count, queue_pending, queue_failed, queue_blocked,
                    source, message, payload_json, created_at
                ) VALUES (
                    :user_id, :office_id, :role_key, :event_type, :route_kind, :action_name, :operation_id,
                    :request_url, :http_status, :attempt_count, :queue_pending, :queue_failed, :queue_blocked,
                    :source, :message, :payload_json, SYSDATETIME()
                )'
            );
            $stmt->execute([
                'user_id' => isset($payload['user_id']) ? (int)$payload['user_id'] : (int)($_SESSION['user_id'] ?? 0),
                'office_id' => isset($payload['office_id']) ? (int)$payload['office_id'] : (int)($_SESSION['office_id'] ?? 0),
                'role_key' => $roleKey,
                'event_type' => $eventType,
                'route_kind' => offline_sync_truncate((string)($payload['route_kind'] ?? ''), 64),
                'action_name' => strtoupper(offline_sync_truncate((string)($payload['action_name'] ?? ''), 64)),
                'operation_id' => offline_sync_truncate((string)($payload['operation_id'] ?? ''), 80),
                'request_url' => offline_sync_truncate((string)($payload['request_url'] ?? ''), 255),
                'http_status' => $httpStatus,
                'attempt_count' => $attemptCount,
                'queue_pending' => $queuePending,
                'queue_failed' => $queueFailed,
                'queue_blocked' => $queueBlocked,
                'source' => offline_sync_truncate((string)($payload['source'] ?? 'client'), 32),
                'message' => offline_sync_truncate((string)($payload['message'] ?? ''), 255),
                'payload_json' => $payloadJson,
            ]);

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }
}
