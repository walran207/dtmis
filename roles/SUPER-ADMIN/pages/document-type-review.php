<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$overview = [
    'documents_total' => 0,
    'documents_pending' => 0,
    'documents_overdue' => 0,
    'documents_completed_30d' => 0,
    'compliance_rate' => 100,
];
$documentTypes = [];

try {
    $pdo = getDatabaseConnection();
    $overview = super_admin_fetch_global_overview($pdo);
    $documentTypes = super_admin_fetch_document_types($pdo, 700);
} catch (Throwable $exception) {
    $documentTypes = [];
}

$inactiveTypes = 0;
foreach ($documentTypes as $type) {
    if (empty($type['is_active'])) {
        $inactiveTypes += 1;
    }
}

$pageTitle = 'Document Type Review | DENR Region XII DTMIS';
$brandSubtitle = 'Super Admin Portal';
$pageHeading = 'Document Type Review';
$pageSubtitle = 'Review and manage document type complexity, ARTA days, and status controls.';
$activeMenu = 'document_type_review';
$showQueueTable = false;
$renderStandardContent = false;
$customSectionInclude = __DIR__ . '/sections/data-management-panel.php';
$disableLiveDashboardRefresh = true;
$hideHeaderSearch = true;
$showQrReceiveScanner = false;
$superAdminDataEndpoint = app_url('actions/super-admin-data.php');
$superAdminDocumentTypes = $documentTypes;
$superAdminDataOverview = $overview;
$superAdminCsrfToken = (string)($_SESSION['csrf_token'] ?? '');

$kpiCards = [
    ['label' => 'Document Types', 'value' => (string)count($documentTypes), 'icon' => 'blue'],
    ['label' => 'Inactive Types', 'value' => (string)$inactiveTypes, 'icon' => 'orange'],
    ['label' => 'Pending Documents', 'value' => (string)$overview['documents_pending'], 'icon' => 'violet'],
    ['label' => 'Compliance Rate', 'value' => (string)$overview['compliance_rate'] . '%', 'icon' => 'green'],
];

$panels = [
    [
        'title' => 'Document Type Governance',
        'chips' => [
            'ARTA category alignment',
            'Days limit calibration',
            'Activate / deactivate types',
            'Regional quality controls',
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
