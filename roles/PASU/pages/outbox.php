<?php
declare(strict_types=1);

$roleBasePath = dirname(__DIR__);

require_once dirname(__DIR__, 3) . '/config/app.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/config/dashboard-data.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

$officeId = (int)($_SESSION['office_id'] ?? 0);
$originRows = [];
$metrics = ['created_today' => 0];

try {
    $pdo = getDatabaseConnection();
    $originRows = dashboard_fetch_origin_tracker_rows($pdo, $officeId, 80);
    $metrics = dashboard_fetch_role_metrics($pdo, $officeId);
} catch (Throwable $exception) {
    $originRows = [];
}

$terminalStatuses = ['completed', 'released', 'signed', 'signed/completed', 'approved', 'closed', 'cancelled', 'canceled'];
$activeCount = 0;
$releasedCount = 0;
$signedCount = 0;
foreach ($originRows as $row) {
    $status = strtolower(trim((string)($row['status'] ?? '')));
    if (str_contains($status, 'released')) {
        $releasedCount++;
    }
    if (str_contains($status, 'signed') || str_contains($status, 'completed')) {
        $signedCount++;
    }
    if (!in_array($status, $terminalStatuses, true)) {
        $activeCount++;
    }
}

$tableRows = [];
foreach ($originRows as $row) {
    $tableRows[] = [
        'value' => [
            (string)($row['tracking_id'] ?? '-'),
            (string)($row['subject'] ?? '-'),
            (string)($row['document_type'] ?? '-'),
            (string)($row['current_holder'] ?? '-'),
            (string)($row['date_received'] ?? '-'),
            (string)($row['status'] ?? '-'),
            'View Tracking Slip | Print Package',
        ],
        'meta' => [
            'document_id' => (string)($row['document_id'] ?? 0),
            'tracking_id' => (string)($row['tracking_id'] ?? ''),
            'date_received_raw' => (string)($row['date_received_raw'] ?? ''),
            'date_created_raw' => (string)($row['date_created_raw'] ?? ''),
            'status_raw' => (string)($row['status'] ?? ''),
            'origin_office_id' => (string)($row['origin_office_id'] ?? 0),
        ],
    ];
}

$roleName = 'PASU';
$initialsFallback = 'PA';
$pageTitle = 'Outbox | DENR Region XII eDATS';
$activeMenu = 'outbox';
$brandSubtitle = 'PASU Portal';
$pageHeading = 'Outbox (Live Origin Tracker)';
$pageSubtitle = 'Track where PASU-originated documents are currently routed.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=origin');

$kpiCards = [
    ['label' => 'Origin Docs (Active)', 'icon' => 'orange', 'value' => (string)$activeCount],
    ['label' => 'Released', 'icon' => 'violet', 'value' => (string)$releasedCount],
    ['label' => 'Signed/Completed', 'icon' => 'green', 'value' => (string)$signedCount],
    ['label' => 'Encoded Today', 'icon' => 'blue', 'value' => (string)($metrics['created_today'] ?? 0)],
];

$panels = [
    [
        'title' => 'Live Origin Route Snapshot',
        'rows' => [
            ['label' => 'In Progress', 'value' => (string)$activeCount, 'width' => '42%'],
            ['label' => 'Released', 'value' => (string)$releasedCount, 'width' => '31%'],
            ['label' => 'Signed/Completed', 'value' => (string)$signedCount, 'width' => '27%'],
        ],
    ],
];

$tableTitle = 'Outbox Live Tracker';
$tableColumns = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Current Holder', 'Last Update', 'Status', 'Quick Actions'];
$pageActions = ['View Tracking Slip', 'Print Package', 'Live Route Monitor'];
$stickyActions = ['View Tracking Slip', 'Print Package', 'Search'];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';

