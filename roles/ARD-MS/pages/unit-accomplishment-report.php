<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/config/dashboard-data.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$roleBasePath = dirname(__DIR__);
$roleName = 'ARD_MS';
$initialsFallback = 'AM';
$pageTitle = 'Unit Accomplishment Report / Export | DENR Region XII DTMIS';
$activeMenu = 'unit_report';
$brandSubtitle = 'ARD MS Portal';
$pageHeading = 'Unit Accomplishment Report / Export';
$pageSubtitle = 'Generate unit accomplishment reports and exports.';
$searchPlaceholder = 'Search Tracking ID or subject';
$kpiCards = [
    ['label' => 'Pending', 'icon' => 'orange', 'value' => '0'],
    ['label' => 'Due Today / Due Soon', 'icon' => 'blue', 'value' => '0', 'pill' => 'ARTA'],
    ['label' => 'Overdue', 'icon' => 'violet', 'value' => '0'],
    ['label' => 'Completed This Week', 'icon' => 'green', 'value' => '0'],
];
$panels = [
    [
        'title' => 'Accomplishment Snapshot',
        'rows' => [
            ['label' => 'Pending', 'width' => '0%', 'value' => '0'],
            ['label' => 'Due Today / Due Soon', 'width' => '0%', 'value' => '0'],
            ['label' => 'Overdue', 'width' => '0%', 'value' => '0'],
            ['label' => 'Completed This Week', 'width' => '0%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'Export Readiness',
        'chips' => ['Set date filter', 'Validate office totals', 'Review overdue before export', 'Generate CSV per unit'],
    ],
];
$tableTitle = 'Reports / Export';
$tableColumns = json_decode('["Office / Unit","Pending","Due Soon","Overdue","Completed","Quick Actions"]', true);
$tableRows = [];

try {
    $pdo = getDatabaseConnection();
    $officeId = (int)($_SESSION['office_id'] ?? 0);
    $unitOffices = [];

    if ($officeId > 0) {
        $officeStmt = $pdo->prepare('SELECT TOP (1) id, name, level FROM offices WHERE id = :office_id');
        $officeStmt->execute(['office_id' => $officeId]);
        $currentOffice = $officeStmt->fetch() ?: null;

        if (is_array($currentOffice)) {
            $currentLevel = strtoupper(trim((string)($currentOffice['level'] ?? '')));
            if ($currentLevel === 'DIVISION') {
                $unitsStmt = $pdo->prepare(
                    'SELECT id, name, level
                     FROM offices
                     WHERE id = :office_id_a OR parent_office_id = :office_id_b
                     ORDER BY CASE WHEN id = :office_id_c THEN 0 ELSE 1 END, name ASC'
                );
                $unitsStmt->execute([
                    'office_id_a' => $officeId,
                    'office_id_b' => $officeId,
                    'office_id_c' => $officeId,
                ]);
                $unitOffices = $unitsStmt->fetchAll() ?: [];
            }

            if (empty($unitOffices)) {
                $unitOffices[] = $currentOffice;
            }
        }
    }

    foreach ($unitOffices as $unitOffice) {
        $unitId = (int)($unitOffice['id'] ?? 0);
        if ($unitId <= 0) {
            continue;
        }

        $unitMetrics = dashboard_fetch_role_metrics($pdo, $unitId);
        $pendingTotal = (int)($unitMetrics['pending_total'] ?? 0);
        $dueSoonTotal = (int)($unitMetrics['due_today_total'] ?? 0) + (int)($unitMetrics['due_soon_total'] ?? 0);
        $overdueTotal = (int)($unitMetrics['overdue_total'] ?? 0);
        $completedTotal = (int)($unitMetrics['completed_total'] ?? 0);
        $statusTags = [];
        if ($pendingTotal > 0) {
            $statusTags[] = 'pending';
        }
        if ($dueSoonTotal > 0) {
            $statusTags[] = 'due soon';
        }
        if ($overdueTotal > 0) {
            $statusTags[] = 'overdue';
        }
        if ($completedTotal > 0) {
            $statusTags[] = 'completed';
        }
        $statusRaw = !empty($statusTags) ? implode(' | ', $statusTags) : 'pending';

        $tableRows[] = [
            'value' => [
                (string)($unitOffice['name'] ?? ('Office #' . (string)$unitId)),
                (string)$pendingTotal,
                (string)$dueSoonTotal,
                (string)$overdueTotal,
                (string)$completedTotal,
                'Export CSV',
            ],
            'meta' => [
                'status_raw' => $statusRaw,
            ],
        ];
    }
} catch (Throwable $exception) {
    $tableRows = [];
}

$pageActions = json_decode('["Filter Date","Generate Report","Export CSV"]', true);
$stickyActions = [];
$queueControlsPlacement = 'table_card';
$statusFilterOptions = [
    ['value' => 'due_soon', 'label' => 'Due Soon / Due Today'],
    ['value' => 'overdue', 'label' => 'Overdue Units'],
    ['value' => 'completed', 'label' => 'Completed Units'],
];
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"Unit Accomplishment Report / Export has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
