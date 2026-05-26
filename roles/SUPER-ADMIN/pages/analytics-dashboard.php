<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$overview = [
    'documents_total' => 0,
    'documents_pending' => 0,
    'documents_overdue' => 0,
    'documents_completed_30d' => 0,
    'activity_events_24h' => 0,
    'activity_events_7d' => 0,
    'compliance_rate' => 100,
];
$officeAnalytics = [];

try {
    $pdo = getDatabaseConnection();
    $overview = super_admin_fetch_global_overview($pdo);
    $officeAnalytics = super_admin_fetch_office_analytics($pdo, 150);
} catch (Throwable $exception) {
    $officeAnalytics = [];
}

$tableRows = [];
$highRiskOffices = 0;
foreach ($officeAnalytics as $office) {
    $pendingDocs = (int)($office['pending_docs'] ?? 0);
    $overdueDocs = (int)($office['overdue_docs'] ?? 0);
    if ($pendingDocs + $overdueDocs > 0) {
        $highRiskOffices += 1;
    }

    $tableRows[] = [
        'value' => [
            (string)($office['office_name'] ?? '-'),
            (string)($office['office_level'] ?? '-'),
            (string)($office['total_docs'] ?? 0),
            (string)$pendingDocs,
            (string)$overdueDocs,
            (string)($office['touched_7d'] ?? 0),
            (string)($office['compliance_rate'] ?? 100) . '%',
        ],
        'meta' => [
            'tracking_id' => '',
            'status_raw' => $overdueDocs > 0 ? 'overdue' : 'stable',
            'current_holder' => (string)($office['office_level'] ?? ''),
        ],
    ];
}

$pageTitle = 'Analytics Dashboard | DENR Region XII DTMIS';
$brandSubtitle = 'Super Admin Portal';
$pageHeading = 'Analytics Dashboard';
$pageSubtitle = 'Cross-office workload, risk, and compliance analytics.';
$activeMenu = 'analytics_dashboard';
$tableTitle = 'Office Workload and Compliance Matrix';
$tableColumns = ['Office', 'Level', 'Total Docs', 'Pending', 'Overdue', 'Touched (7d)', 'Compliance'];
$searchPlaceholder = 'Search office name, level, or risk metrics';
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$showStatusCategoryFilter = false;
$dateFilterPlacement = 'table_card';
$disableLiveDashboardRefresh = true;
$showQrReceiveScanner = false;
$hideQueueDocumentOnlyActions = true;
$enableBulkRowExport = true;
$stickyActions = ['Export CSV'];
$pageActions = ['Export analytics', 'Risk triage', 'Performance review'];

$kpiCards = [
    ['label' => 'Total Documents', 'value' => (string)$overview['documents_total'], 'icon' => 'blue'],
    ['label' => 'Pending Queue', 'value' => (string)$overview['documents_pending'], 'icon' => 'orange'],
    ['label' => 'Overdue Items', 'value' => (string)$overview['documents_overdue'], 'icon' => 'orange'],
    ['label' => 'High-Risk Offices', 'value' => (string)$highRiskOffices, 'icon' => 'violet'],
    ['label' => 'Events (24h)', 'value' => (string)$overview['activity_events_24h'], 'icon' => 'blue'],
    ['label' => 'Compliance', 'value' => (string)$overview['compliance_rate'] . '%', 'icon' => 'green'],
];

$analyticsBase = max(
    (int)$overview['documents_total'],
    (int)$overview['documents_pending'],
    (int)$overview['documents_overdue'],
    (int)$overview['documents_completed_30d'],
    1
);

$panels = [
    [
        'title' => 'Global Workflow Pressure',
        'rows' => [
            ['label' => 'Pending Queue', 'value' => (string)$overview['documents_pending'], 'width' => (string)(int)round(((int)$overview['documents_pending'] / $analyticsBase) * 100) . '%'],
            ['label' => 'Overdue Queue', 'value' => (string)$overview['documents_overdue'], 'width' => (string)(int)round(((int)$overview['documents_overdue'] / $analyticsBase) * 100) . '%'],
            ['label' => 'Closed (30d)', 'value' => (string)$overview['documents_completed_30d'], 'width' => (string)(int)round(((int)$overview['documents_completed_30d'] / $analyticsBase) * 100) . '%'],
            ['label' => 'Events (7d)', 'value' => (string)$overview['activity_events_7d'], 'width' => (string)(int)round(((int)$overview['activity_events_7d'] / max((int)$overview['activity_events_7d'], 1)) * 100) . '%'],
        ],
    ],
    [
        'title' => 'Regional Intelligence',
        'chips' => [
            'High-risk offices: ' . (string)$highRiskOffices,
            'Office rows: ' . (string)count($tableRows),
            'Trend window: 7 days',
            'Export-ready matrix',
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
