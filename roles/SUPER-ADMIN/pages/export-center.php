<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recentDocuments = [];
$overview = [
    'documents_total' => 0,
    'documents_pending' => 0,
    'documents_overdue' => 0,
    'documents_completed_30d' => 0,
    'activity_events_24h' => 0,
    'activity_events_7d' => 0,
    'compliance_rate' => 100,
];

try {
    $pdo = getDatabaseConnection();
    $overview = super_admin_fetch_global_overview($pdo);
    $recentDocuments = super_admin_fetch_recent_documents($pdo, 200);
} catch (Throwable $exception) {
    $recentDocuments = [];
}

$tableRows = [];
foreach ($recentDocuments as $document) {
    $tableRows[] = [
        'value' => [
            (string)($document['tracking_id'] ?? '-'),
            (string)($document['source_type'] ?? '-'),
            (string)($document['subject'] ?? '-'),
            (string)($document['document_type'] ?? '-'),
            (string)($document['status'] ?? '-'),
            (string)($document['origin_office'] ?? '-'),
            (string)($document['current_office'] ?? '-'),
            (string)($document['pending_office'] ?? '-'),
            (string)($document['date_created'] ?? '-'),
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

$pageTitle = 'Export Center | DENR Region XII eDATS';
$brandSubtitle = 'Super Admin Portal';
$pageHeading = 'Export Center';
$pageSubtitle = 'System-wide export workspace for filtered and selected operational records.';
$activeMenu = 'export_center';
$tableTitle = 'Exportable Document Dataset';
$tableColumns = ['Tracking ID', 'Source', 'Subject', 'Document Type', 'Status', 'Origin Office', 'Current Office', 'Pending Office', 'Created At', 'Updated At'];
$searchPlaceholder = 'Search dataset before export';
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$showStatusCategoryFilter = false;
$dateFilterPlacement = 'table_card';
$disableLiveDashboardRefresh = true;
$showQrReceiveScanner = false;
$enableBulkRowExport = true;
$stickyActions = ['Export CSV'];
$pageActions = ['Select rows', 'Filter data', 'Export CSV'];

$kpiCards = [
    ['label' => 'Rows Ready', 'value' => (string)count($tableRows), 'icon' => 'blue'],
    ['label' => 'Pending Queue', 'value' => (string)$overview['documents_pending'], 'icon' => 'orange'],
    ['label' => 'Overdue Queue', 'value' => (string)$overview['documents_overdue'], 'icon' => 'violet'],
    ['label' => 'Compliance', 'value' => (string)$overview['compliance_rate'] . '%', 'icon' => 'green'],
];

$panels = [
    [
        'title' => 'Export Guidance',
        'chips' => [
            'Use search and date filters first',
            'Select specific rows for targeted exports',
            'CSV includes only visible columns',
            'Bulk checkbox enables selective export',
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
