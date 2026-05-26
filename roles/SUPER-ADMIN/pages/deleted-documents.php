<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/document-recycle-bin.php';

$deletedDocuments = [];

try {
    $pdo = getDatabaseConnection();
    $deletedDocuments = document_recycle_bin_fetch_deleted_documents($pdo, 400);
} catch (Throwable $exception) {
    $deletedDocuments = [];
}

$pageTitle = 'Deleted Documents | DENR Region XII DTMIS';
$brandSubtitle = 'Super Admin Portal';
$pageHeading = 'Deleted Documents';
$pageSubtitle = 'Review archived documents and restore them back into the active workflow when needed.';
$activeMenu = 'deleted_documents';
$showQueueTable = false;
$renderStandardContent = false;
$customSectionInclude = __DIR__ . '/sections/deleted-documents-panel.php';
$disableLiveDashboardRefresh = true;
$hideHeaderSearch = true;
$showQrReceiveScanner = false;
$dtrCsrfToken = (string)($_SESSION['csrf_token'] ?? '');
$superAdminCsrfToken = (string)($_SESSION['csrf_token'] ?? '');
$superAdminDeletedDocuments = $deletedDocuments;
$superAdminDeletedDocumentsEndpoint = app_url('actions/super-admin-deleted-documents.php');

$kpiCards = [
    ['label' => 'Archived Documents', 'value' => (string)count($deletedDocuments), 'icon' => 'orange'],
];

$panels = [
    [
        'title' => 'Recycle Bin',
        'chips' => [
            'Super Admin visibility',
            'Restore document metadata',
            'Restore attachments and route history',
            'Keep files and image backups intact',
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
