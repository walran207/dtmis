<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$overview = [
    'users_total' => 0,
    'users_active' => 0,
    'roles_total' => 0,
    'offices_total' => 0,
    'documents_total' => 0,
    'documents_pending' => 0,
    'documents_overdue' => 0,
    'documents_completed_30d' => 0,
    'activity_events_24h' => 0,
    'activity_events_7d' => 0,
    'compliance_rate' => 100,
];
$recentDocuments = [];
$officeAnalytics = [];
$routeOffices = [];

try {
    $pdo = getDatabaseConnection();
    $overview = super_admin_fetch_global_overview($pdo);
    $recentDocuments = super_admin_fetch_recent_documents($pdo, 60);
    $officeAnalytics = super_admin_fetch_office_analytics($pdo, 20);
    $routeOffices = dashboard_fetch_route_offices($pdo, (int)($_SESSION['office_id'] ?? 0));
} catch (Throwable $exception) {
    $recentDocuments = [];
    $officeAnalytics = [];
}

$workloadRisk = (int)$overview['documents_pending'] + (int)$overview['documents_overdue'];
$topRiskOffice = !empty($officeAnalytics) ? (string)($officeAnalytics[0]['office_name'] ?? '-') : '-';
$topRiskOfficeLoad = !empty($officeAnalytics)
    ? ((int)($officeAnalytics[0]['overdue_docs'] ?? 0) + (int)($officeAnalytics[0]['pending_docs'] ?? 0))
    : 0;

$tableRows = [];
foreach ($recentDocuments as $document) {
    $tableRows[] = [
        'value' => [
            (string)($document['tracking_id'] ?? '-'),
            (string)($document['subject'] ?? '-'),
            (string)($document['status'] ?? '-'),
            (string)($document['document_type'] ?? '-'),
            (string)($document['current_office'] ?? '-'),
            (string)($document['pending_office'] ?? '-'),
            (string)($document['date_updated'] ?? '-'),
        ],
        'meta' => [
            'tracking_id' => (string)($document['tracking_id'] ?? ''),
            'status_raw' => (string)($document['status'] ?? ''),
            'date_created_raw' => (string)($document['date_created_raw'] ?? ''),
            'date_received_raw' => (string)($document['date_updated_raw'] ?? ''),
            'current_holder' => (string)($document['current_office'] ?? ''),
        ],
    ];
}

$pageTitle = 'Super Admin Control Center | DENR Region XII DTMIS';
$brandSubtitle = 'Super Admin Portal';
$pageHeading = 'Super Admin Control Center';
$pageSubtitle = 'Global operations view for users, data, analytics, export, and live traffic telemetry.';
$activeMenu = 'dashboard';
$tableTitle = 'Recent System-Wide Document Activity';
$tableColumns = ['Tracking ID', 'Subject', 'Status', 'Document Type', 'Current Office', 'Pending Office', 'Last Update'];
$searchPlaceholder = 'Search tracking id, subject, office, or status';
$stickyActions = ['Export CSV'];
$pageActions = ['User control', 'Data governance', 'Live network telemetry'];
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$showStatusCategoryFilter = false;
$dateFilterPlacement = 'table_card';
$showQrReceiveScanner = false;
$disableLiveDashboardRefresh = true;
$enableBulkRowExport = true;

$kpiCards = [
    ['label' => 'Active Users', 'value' => (string)$overview['users_active'], 'icon' => 'blue'],
    ['label' => 'Roles / Offices', 'value' => (string)($overview['roles_total'] . ' / ' . $overview['offices_total']), 'icon' => 'violet'],
    ['label' => 'Pending Documents', 'value' => (string)$overview['documents_pending'], 'icon' => 'orange'],
    ['label' => 'Overdue Documents', 'value' => (string)$overview['documents_overdue'], 'icon' => 'orange'],
    ['label' => 'Traffic (24h)', 'value' => (string)$overview['activity_events_24h'], 'icon' => 'blue'],
    ['label' => 'Compliance Rate', 'value' => (string)$overview['compliance_rate'] . '%', 'icon' => 'green'],
];

$overviewBase = max(
    (int)$overview['documents_total'],
    (int)$overview['documents_pending'],
    (int)$overview['documents_completed_30d'],
    (int)$overview['documents_overdue'],
    1
);

$panels = [
    [
        'title' => 'System Load Overview',
        'rows' => [
            ['label' => 'Total Documents', 'value' => (string)$overview['documents_total'], 'width' => (string)(int)round(((int)$overview['documents_total'] / $overviewBase) * 100) . '%'],
            ['label' => 'Pending Queue', 'value' => (string)$overview['documents_pending'], 'width' => (string)(int)round(((int)$overview['documents_pending'] / $overviewBase) * 100) . '%'],
            ['label' => 'Closed (30d)', 'value' => (string)$overview['documents_completed_30d'], 'width' => (string)(int)round(((int)$overview['documents_completed_30d'] / $overviewBase) * 100) . '%'],
            ['label' => 'Overdue', 'value' => (string)$overview['documents_overdue'], 'width' => (string)(int)round(((int)$overview['documents_overdue'] / $overviewBase) * 100) . '%'],
        ],
    ],
    [
        'title' => 'User and Role Coverage',
        'rows' => [
            ['label' => 'Total Users', 'value' => (string)$overview['users_total'], 'width' => '100%'],
            ['label' => 'Active Users', 'value' => (string)$overview['users_active'], 'width' => '82%'],
            ['label' => 'Defined Roles', 'value' => (string)$overview['roles_total'], 'width' => '55%'],
            ['label' => 'Tracked Offices', 'value' => (string)$overview['offices_total'], 'width' => '68%'],
        ],
    ],
    [
        'title' => 'Operational Watchlist',
        'chips' => [
            'Risk load: ' . $workloadRisk,
            'Top risk office: ' . $topRiskOffice,
            'Top risk load: ' . $topRiskOfficeLoad,
            'Events (7d): ' . (string)$overview['activity_events_7d'],
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
