<?php
declare(strict_types=1);

$roleBasePath = $roleBasePath ?? dirname(__DIR__);

require_once dirname(__DIR__, 3) . '/config/app.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/config/dashboard-data.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

$roleName = 'ORED';
$initialsFallback = 'OR';
$pageTitle = 'Regional Summary Reports | DENR Region XII DTMIS';
$activeMenu = 'regional_reports';
$brandSubtitle = 'ORED Portal';
$pageHeading = 'Regional Summary Reports';
$pageSubtitle = 'Regional workload and completion performance across offices.';
$searchPlaceholder = 'Search office or unit';

$summaryRows = [];
try {
    $pdo = getDatabaseConnection();
    $summaryRows = dashboard_fetch_regional_office_summary_rows($pdo, 200);
} catch (Throwable $exception) {
    $summaryRows = [];
}

$totalUnits = 0;
$regionalPending = 0;
$regionalDueSoon = 0;
$regionalOverdue = 0;
$regionalCompleted = 0;
$unitsWithRisk = 0;
$highPressureUnits = 0;

foreach ($summaryRows as $summaryRow) {
    $totalUnits++;
    $pendingCount = (int)($summaryRow['pending_count'] ?? 0);
    $dueSoonCount = (int)($summaryRow['due_soon_count'] ?? 0);
    $overdueCount = (int)($summaryRow['overdue_count'] ?? 0);
    $completedCount = (int)($summaryRow['completed_count'] ?? 0);

    $regionalPending += max($pendingCount, 0);
    $regionalDueSoon += max($dueSoonCount, 0);
    $regionalOverdue += max($overdueCount, 0);
    $regionalCompleted += max($completedCount, 0);

    if (($dueSoonCount + $overdueCount) > 0) {
        $unitsWithRisk++;
    }
    if ($pendingCount >= 10) {
        $highPressureUnits++;
    }
}

$regionalRisk = $regionalDueSoon + $regionalOverdue;
$stableUnits = max($totalUnits - $unitsWithRisk, 0);

$workloadBase = max($regionalPending, $regionalRisk, $regionalCompleted, 1);
$healthBase = max($totalUnits, $unitsWithRisk, $stableUnits, $highPressureUnits, 1);

$workloadPendingWidth = (string)(int)round(($regionalPending / $workloadBase) * 100) . '%';
$workloadRiskWidth = (string)(int)round(($regionalRisk / $workloadBase) * 100) . '%';
$workloadClosedWidth = (string)(int)round(($regionalCompleted / $workloadBase) * 100) . '%';
$healthUnitsWidth = (string)(int)round(($totalUnits / $healthBase) * 100) . '%';
$healthRiskWidth = (string)(int)round(($unitsWithRisk / $healthBase) * 100) . '%';
$healthStableWidth = (string)(int)round(($stableUnits / $healthBase) * 100) . '%';
$healthPressureWidth = (string)(int)round(($highPressureUnits / $healthBase) * 100) . '%';

$kpiCards = [];
$panels = [];

$tableTitle = 'Regional Office Summary';
$tableColumns = ['Office / Unit', 'Pending', 'Due Soon', 'Overdue', 'Completed', 'Export'];
$tableRows = [];
foreach ($summaryRows as $summaryRow) {
    $officeName = trim((string)($summaryRow['office_name'] ?? 'Unknown Office'));
    $officeLevel = trim((string)($summaryRow['office_level'] ?? ''));
    $officeLabel = $officeLevel !== '' ? $officeName . ' (' . $officeLevel . ')' : $officeName;
    $tableRows[] = [
        'value' => [
            $officeLabel,
            (string)((int)($summaryRow['pending_count'] ?? 0)),
            (string)((int)($summaryRow['due_soon_count'] ?? 0)),
            (string)((int)($summaryRow['overdue_count'] ?? 0)),
            (string)((int)($summaryRow['completed_count'] ?? 0)),
            'CSV',
        ],
        'meta' => [
            'tracking_id' => $officeName,
            'status_raw' => '',
            'date_created_raw' => '',
            'date_received_raw' => '',
        ],
    ];
}

$pageActions = ['Filter Date', 'Export CSV'];
$stickyActions = [];
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$showStatusCategoryFilter = false;
$dateFilterPlacement = 'table_card';
$showQrReceiveScanner = false;
$disableLiveDashboardRefresh = true;

$notifications = [
    [
        'title' => 'Regional summary synced',
        'timeLabel' => 'Now',
        'message' => 'Regional office summary has been refreshed from live records.',
        'unread' => true,
        'datetime' => date('c'),
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
