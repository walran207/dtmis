<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/dashboard-data.php';
require_once dirname(__DIR__) . '/auth/security.php';

if (!function_exists('notification_mail_feature_flags')) {
    function notification_mail_feature_flags(): array
    {
        return [
            // Change 0 to 1 later for the notification you want to enable.
            'forwarded' => 1,
            'released' => 1,
            'signed' => 1,
            'due_soon' => 1,
            'overdue' => 1,

            // Reserved for future OTP toggle wiring.
            'otp' => 1,
        ];
    }
}

if (!function_exists('notification_mail_is_enabled')) {
    function notification_mail_is_enabled(string $eventKey): bool
    {
        $flags = notification_mail_feature_flags();
        $normalizedKey = strtolower(trim($eventKey));
        return ((int)($flags[$normalizedKey] ?? 0)) === 1;
    }
}

if (!function_exists('notification_mail_log_table_name')) {
    function notification_mail_log_table_name(): string
    {
        return 'email_notification_logs';
    }
}

if (!function_exists('notification_mail_ensure_log_table')) {
    function notification_mail_ensure_log_table(PDO $pdo): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        $tableName = notification_mail_log_table_name();
        if (db_is_sql_server($pdo)) {
            $pdo->exec(
                "IF OBJECT_ID(N'dbo.{$tableName}', N'U') IS NULL
                 BEGIN
                     CREATE TABLE dbo.{$tableName} (
                         id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                         event_key NVARCHAR(64) NOT NULL,
                         document_id INT NOT NULL,
                         office_id INT NOT NULL,
                         recipient_email NVARCHAR(255) NOT NULL,
                         dedupe_key NVARCHAR(128) NOT NULL,
                         sent_at DATETIME2 NOT NULL CONSTRAINT DF_{$tableName}_sent_at DEFAULT SYSDATETIME(),
                         CONSTRAINT UQ_{$tableName}_dedupe UNIQUE (event_key, document_id, office_id, recipient_email, dedupe_key)
                     );
                 END"
            );
        } else {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS {$tableName} (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `event_key` VARCHAR(64) NOT NULL,
                    `document_id` INT NOT NULL,
                    `office_id` INT NOT NULL,
                    `recipient_email` VARCHAR(255) NOT NULL,
                    `dedupe_key` VARCHAR(128) NOT NULL,
                    `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_email_notification_dedupe` (`event_key`, `document_id`, `office_id`, `recipient_email`, `dedupe_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }

        $ensured = true;
    }
}

if (!function_exists('notification_mail_recipient_modes')) {
    function notification_mail_recipient_modes(): array
    {
        return [
            'forwarded' => 'destination_office',
            'released' => 'destination_office',
            'signed' => 'originating_office',
            'due_soon' => 'current_office',
            'overdue' => 'current_office',
        ];
    }
}

if (!function_exists('notification_mail_office_recipients')) {
    function notification_mail_office_recipients(PDO $pdo, int $officeId): array
    {
        if ($officeId <= 0) {
            return [];
        }

        $stmt = $pdo->prepare(
            "SELECT email
             FROM users
             WHERE office_id = :office_id
               AND is_active = 1
               AND email IS NOT NULL
               AND LTRIM(RTRIM(email)) <> ''
             ORDER BY id ASC"
        );
        $stmt->execute(['office_id' => $officeId]);

        $emails = [];
        foreach (($stmt->fetchAll(PDO::FETCH_COLUMN) ?: []) as $email) {
            $normalizedEmail = strtolower(trim((string)$email));
            if ($normalizedEmail === '') {
                continue;
            }
            $emails[$normalizedEmail] = $normalizedEmail;
        }

        return array_values($emails);
    }
}

if (!function_exists('notification_mail_target_office_id')) {
    function notification_mail_target_office_id(array $document, string $eventKey, array $context = []): int
    {
        $modes = notification_mail_recipient_modes();
        $mode = strtolower(trim((string)($modes[strtolower(trim($eventKey))] ?? 'current_office')));

        return match ($mode) {
            'destination_office' => (int)($context['destination_office_id'] ?? $document['pending_office_id'] ?? $document['current_office_id'] ?? 0),
            'originating_office' => (int)($document['originating_office_id'] ?? 0),
            default => (int)($document['current_office_id'] ?? 0),
        };
    }
}

if (!function_exists('notification_mail_send_plain_email')) {
    function notification_mail_send_plain_email(string $email, string $subject, string $message): bool
    {
        $smtpConfig = auth_mail_smtp_config();
        if (auth_mail_smtp_is_configured($smtpConfig)) {
            $sent = auth_send_via_smtp($email, $subject, $message, $smtpConfig);
            if ($sent) {
                return true;
            }
        }

        $fallbackFromEmail = (string)($smtpConfig['from_email'] ?? '');
        if ($fallbackFromEmail === '') {
            $fallbackFromEmail = auth_mail_env('MAIL_FROM_ADDRESS', 'no-reply@denr12.online');
        }
        $headers = "From: {$fallbackFromEmail}\r\n";

        return (bool)@mail($email, $subject, $message, $headers);
    }
}

if (!function_exists('notification_mail_dedupe_record_exists')) {
    function notification_mail_dedupe_record_exists(
        PDO $pdo,
        string $eventKey,
        int $documentId,
        int $officeId,
        string $recipientEmail,
        string $dedupeKey
    ): bool {
        if ($dedupeKey === '') {
            return false;
        }

        notification_mail_ensure_log_table($pdo);
        $tableName = notification_mail_log_table_name();
        $stmt = $pdo->prepare(
            db_is_sql_server($pdo)
                ? "SELECT TOP (1) 1
                   FROM {$tableName}
                   WHERE event_key = :event_key
                     AND document_id = :document_id
                     AND office_id = :office_id
                     AND recipient_email = :recipient_email
                     AND dedupe_key = :dedupe_key"
                : "SELECT 1
                   FROM {$tableName}
                   WHERE event_key = :event_key
                     AND document_id = :document_id
                     AND office_id = :office_id
                     AND recipient_email = :recipient_email
                     AND dedupe_key = :dedupe_key
                   LIMIT 1"
        );
        $stmt->execute([
            'event_key' => $eventKey,
            'document_id' => $documentId,
            'office_id' => $officeId,
            'recipient_email' => strtolower(trim($recipientEmail)),
            'dedupe_key' => $dedupeKey,
        ]);

        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('notification_mail_record_sent')) {
    function notification_mail_record_sent(
        PDO $pdo,
        string $eventKey,
        int $documentId,
        int $officeId,
        string $recipientEmail,
        string $dedupeKey
    ): void {
        if ($dedupeKey === '') {
            return;
        }

        notification_mail_ensure_log_table($pdo);
        $tableName = notification_mail_log_table_name();

        try {
            $stmt = $pdo->prepare(
                db_is_sql_server($pdo)
                    ? "INSERT INTO {$tableName} (
                            event_key, document_id, office_id, recipient_email, dedupe_key, sent_at
                       ) VALUES (
                            :event_key, :document_id, :office_id, :recipient_email, :dedupe_key, SYSDATETIME()
                       )"
                    : "INSERT INTO {$tableName} (
                            event_key, document_id, office_id, recipient_email, dedupe_key, sent_at
                       ) VALUES (
                            :event_key, :document_id, :office_id, :recipient_email, :dedupe_key, NOW()
                       )"
            );
            $stmt->execute([
                'event_key' => $eventKey,
                'document_id' => $documentId,
                'office_id' => $officeId,
                'recipient_email' => strtolower(trim($recipientEmail)),
                'dedupe_key' => $dedupeKey,
            ]);
        } catch (Throwable $exception) {
            // Best-effort log only.
        }
    }
}

if (!function_exists('notification_mail_send_document_event')) {
    function notification_mail_send_document_event(PDO $pdo, string $eventKey, int $documentId, array $context = []): int
    {
        $normalizedEventKey = strtolower(trim($eventKey));
        if (!notification_mail_is_enabled($normalizedEventKey) || $documentId <= 0) {
            return 0;
        }

        $document = workflow_get_document_context($pdo, $documentId, false);
        $targetOfficeId = notification_mail_target_office_id($document, $normalizedEventKey, $context);
        $recipientEmails = notification_mail_office_recipients($pdo, $targetOfficeId);
        if ($targetOfficeId <= 0 || $recipientEmails === []) {
            return 0;
        }

        $trackingId = strtoupper(trim((string)($document['tracking_id'] ?? '')));
        $documentSubject = trim((string)($document['subject'] ?? 'No subject'));
        $documentStatus = trim((string)($document['status'] ?? 'Pending'));
        $remarks = trim((string)($context['remarks'] ?? ''));
        $deadlineAt = trim((string)($context['deadline_at'] ?? ''));
        $officeContext = workflow_get_office_context($pdo, $targetOfficeId);
        $officeName = trim((string)($officeContext['name'] ?? ''));

        $subjectMap = [
            'forwarded' => 'Document Forwarded',
            'released' => 'Document Released',
            'signed' => 'Document Signed',
            'due_soon' => 'Document Due Soon',
            'overdue' => 'Document Overdue',
        ];
        $mailSubject = '[DTMIS] ' . ($subjectMap[$normalizedEventKey] ?? 'Document Update');
        if ($trackingId !== '') {
            $mailSubject .= ' - ' . $trackingId;
        }

        $trackingUrl = app_public_url('tracking-slip.php');
        if ($trackingId !== '') {
            $trackingUrl .= '?tracking_id=' . rawurlencode($trackingId) . '&public=1';
        }

        $bodyLines = [
            'Notification Type: ' . strtoupper(str_replace('_', ' ', $normalizedEventKey)),
            'Tracking ID: ' . ($trackingId !== '' ? $trackingId : 'N/A'),
            'Subject: ' . $documentSubject,
            'Status: ' . $documentStatus,
        ];
        if ($officeName !== '') {
            $bodyLines[] = 'Office: ' . $officeName;
        }
        if ($deadlineAt !== '') {
            $bodyLines[] = 'Deadline: ' . $deadlineAt;
        }
        if ($remarks !== '') {
            $bodyLines[] = 'Remarks: ' . $remarks;
        }
        $bodyLines[] = 'Tracking Slip: ' . $trackingUrl;

        $mailBody = implode("\n", $bodyLines);
        $dedupeKey = trim((string)($context['dedupe_key'] ?? ''));
        $sentCount = 0;

        foreach ($recipientEmails as $recipientEmail) {
            if (
                $dedupeKey !== ''
                && notification_mail_dedupe_record_exists(
                    $pdo,
                    $normalizedEventKey,
                    $documentId,
                    $targetOfficeId,
                    $recipientEmail,
                    $dedupeKey
                )
            ) {
                continue;
            }

            if (!notification_mail_send_plain_email($recipientEmail, $mailSubject, $mailBody)) {
                continue;
            }

            $sentCount++;
            notification_mail_record_sent(
                $pdo,
                $normalizedEventKey,
                $documentId,
                $targetOfficeId,
                $recipientEmail,
                $dedupeKey
            );
        }

        return $sentCount;
    }
}

if (!function_exists('notification_mail_process_deadline_alerts')) {
    function notification_mail_process_deadline_alerts(PDO $pdo): int
    {
        $deadlineEvents = ['due_soon', 'overdue'];
        $enabledDeadlineEvents = array_values(array_filter(
            $deadlineEvents,
            static fn(string $eventKey): bool => notification_mail_is_enabled($eventKey)
        ));
        if ($enabledDeadlineEvents === []) {
            return 0;
        }

        $terminalStatuses = "'completed', 'released', 'closed', 'approved', 'resolved', 'done', 'signed', 'signed/completed', 'cancelled', 'canceled'";
        $deadlineExpr = dashboard_sql_add_days_expr('d.created_at', dashboard_documents_arta_days_expr($pdo));
        $sql = 'SELECT
                    d.id,
                    d.status,
                    ' . $deadlineExpr . ' AS deadline_at
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                WHERE d.current_office_id IS NOT NULL
                  AND d.current_office_id <> 0
                  AND LOWER(COALESCE(d.status, \'\')) NOT IN (' . $terminalStatuses . ')
                ORDER BY d.id DESC';

        $stmt = $pdo->query($sql);
        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        $sentCount = 0;
        $todayDedupeKey = date('Y-m-d');

        foreach ($rows as $row) {
            $deadlineAt = trim((string)($row['deadline_at'] ?? ''));
            $status = trim((string)($row['status'] ?? ''));
            $bucket = dashboard_deadline_bucket($deadlineAt, $status);
            if (!in_array($bucket, $enabledDeadlineEvents, true)) {
                continue;
            }

            $sentCount += notification_mail_send_document_event($pdo, $bucket, (int)($row['id'] ?? 0), [
                'deadline_at' => $deadlineAt,
                'dedupe_key' => $bucket . ':' . $todayDedupeKey,
            ]);
        }

        return $sentCount;
    }
}

if (!function_exists('notification_mail_deadline_sweep_lock_path')) {
    function notification_mail_deadline_sweep_lock_path(): string
    {
        return app_storage_path('locks/notification-deadline-sweep.lock');
    }
}

if (!function_exists('notification_mail_run_deadline_sweep')) {
    function notification_mail_run_deadline_sweep(PDO $pdo, int $minimumIntervalSeconds = 1800, bool $force = false): array
    {
        if (!notification_mail_is_enabled('due_soon') && !notification_mail_is_enabled('overdue')) {
            return [
                'ran' => false,
                'sent_count' => 0,
                'reason' => 'disabled',
            ];
        }

        $lockPath = notification_mail_deadline_sweep_lock_path();
        $lockDirectory = dirname($lockPath);
        if (!app_prepare_writable_directory($lockDirectory)) {
            return [
                'ran' => false,
                'sent_count' => 0,
                'reason' => 'lock_directory_unavailable',
            ];
        }

        $handle = @fopen($lockPath, 'c+');
        if (!$handle) {
            return [
                'ran' => false,
                'sent_count' => 0,
                'reason' => 'lock_unavailable',
            ];
        }

        try {
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                return [
                    'ran' => false,
                    'sent_count' => 0,
                    'reason' => 'already_running',
                ];
            }

            rewind($handle);
            $lastRunRaw = trim((string)stream_get_contents($handle));
            $lastRunAt = ctype_digit($lastRunRaw) ? (int)$lastRunRaw : 0;
            $effectiveMinimumInterval = max(300, $minimumIntervalSeconds);
            if (!$force && $lastRunAt > 0 && (time() - $lastRunAt) < $effectiveMinimumInterval) {
                return [
                    'ran' => false,
                    'sent_count' => 0,
                    'reason' => 'throttled',
                ];
            }

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string)time());
            fflush($handle);

            return [
                'ran' => true,
                'sent_count' => notification_mail_process_deadline_alerts($pdo),
                'reason' => 'completed',
            ];
        } catch (Throwable $exception) {
            error_log('DTMIS notification deadline sweep failed: ' . $exception->getMessage());

            return [
                'ran' => false,
                'sent_count' => 0,
                'reason' => 'failed',
            ];
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }
}

if (!function_exists('notification_mail_maybe_run_deadline_sweep')) {
    function notification_mail_maybe_run_deadline_sweep(PDO $pdo, int $minimumIntervalSeconds = 1800): void
    {
        notification_mail_run_deadline_sweep($pdo, $minimumIntervalSeconds, false);
    }
}
