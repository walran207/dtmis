<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$overview = [
    'documents_total' => 0,
    'documents_pending' => 0,
    'documents_overdue' => 0,
    'documents_completed_30d' => 0,
];

try {
    $pdo = getDatabaseConnection();
    $overview = super_admin_fetch_global_overview($pdo);
} catch (Throwable $exception) {
    $overview = [
        'documents_total' => 0,
        'documents_pending' => 0,
        'documents_overdue' => 0,
        'documents_completed_30d' => 0,
    ];
}

$pageTitle = 'Documents Management | DENR Region XII DTMIS';
$brandSubtitle = 'Super Admin Portal';
$pageHeading = 'Documents Management';
$pageSubtitle = 'Review, inspect, and archive any document created across the system from one global super admin workspace.';
$activeMenu = 'documents_management';
$showQueueTable = false;
$renderStandardContent = false;
$customSectionInclude = __DIR__ . '/sections/documents-management-panel.php';
$disableLiveDashboardRefresh = true;
$hideHeaderSearch = true;
$showQrReceiveScanner = false;
$superAdminCsrfToken = (string)($_SESSION['csrf_token'] ?? '');
$superAdminDocumentsEndpoint = app_url('actions/super-admin-documents.php');
$superAdminDocumentDetailsEndpoint = app_url('actions/document-details.php');
$superAdminTrackingSlipPath = app_url('tracking-slip.php');
$superAdminPrintPackagePath = app_url('print-package.php');

$kpiCards = [
    ['label' => 'Total Documents', 'value' => (string)($overview['documents_total'] ?? 0), 'icon' => 'blue'],
    ['label' => 'Active Queue', 'value' => (string)($overview['documents_pending'] ?? 0), 'icon' => 'orange'],
    ['label' => 'Overdue', 'value' => (string)($overview['documents_overdue'] ?? 0), 'icon' => 'orange'],
    ['label' => 'Completed (30d)', 'value' => (string)($overview['documents_completed_30d'] ?? 0), 'icon' => 'green'],
];

$panels = [
    [
        'title' => 'Global Document Control',
        'chips' => [
            'Search all offices',
            'Inspect attachments and routing context',
            'Open tracking slip and print package',
            'Move records to recycle bin',
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
