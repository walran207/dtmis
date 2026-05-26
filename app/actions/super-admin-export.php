<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/super-admin.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Authentication required.']);
    exit;
}

if (!super_admin_has_access()) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Super Admin access required.']);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $documentIds = [];
    $trackingIds = [];
    $rowKeys = $_GET['row_keys'] ?? [];
    if (!is_array($rowKeys)) {
        $rowKeys = [$rowKeys];
    }

    foreach ($rowKeys as $rowKey) {
        $normalizedRowKey = trim((string)$rowKey);
        if ($normalizedRowKey === '') {
            continue;
        }

        if (str_starts_with($normalizedRowKey, 'doc:')) {
            $documentId = (int)substr($normalizedRowKey, 4);
            if ($documentId > 0) {
                $documentIds[] = $documentId;
            }
            continue;
        }

        if (str_starts_with($normalizedRowKey, 'trk:')) {
            $trackingId = trim(substr($normalizedRowKey, 4));
            if ($trackingId !== '') {
                $trackingIds[] = $trackingId;
            }
        }
    }

    $pdo = getDatabaseConnection();
    $rows = super_admin_fetch_export_documents($pdo, [
        'search' => trim((string)($_GET['search'] ?? '')),
        'from_date' => trim((string)($_GET['fromDate'] ?? '')),
        'to_date' => trim((string)($_GET['toDate'] ?? '')),
        'document_ids' => $documentIds,
        'tracking_ids' => $trackingIds,
    ], 5000);

    $csvEscape = static function ($value): string {
        $text = preg_replace('/\s+/', ' ', trim((string)$value)) ?? '';
        if (str_contains($text, ',') || str_contains($text, '"') || str_contains($text, "\n")) {
            return '"' . str_replace('"', '""', $text) . '"';
        }

        return $text;
    };

    $headers = [
        'Tracking ID',
        'Source',
        'Subject',
        'Document Type',
        'Status',
        'Originating Office / Entity',
        'Current Office',
        'Pending Office',
        'Created At',
        'Updated At',
    ];

    $lines = [
        implode(',', array_map($csvEscape, $headers)),
    ];

    foreach ($rows as $row) {
        $lines[] = implode(',', array_map($csvEscape, [
            (string)($row['tracking_id'] ?? ''),
            (string)($row['source_type'] ?? ''),
            (string)($row['subject'] ?? ''),
            (string)($row['document_type'] ?? ''),
            (string)($row['status'] ?? ''),
            (string)($row['origin_office'] ?? ''),
            (string)($row['current_office'] ?? ''),
            (string)($row['pending_office'] ?? ''),
            (string)($row['date_created'] ?? ''),
            (string)($row['date_updated'] ?? ''),
        ]));
    }

    $filename = 'super-admin-export-' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF" . implode("\n", $lines);
    exit;
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to export Super Admin records right now.',
    ]);
    exit;
}
