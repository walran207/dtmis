<?php
declare(strict_types=1);

$roleBasePath = dirname(__DIR__);

require_once dirname(__DIR__, 3) . '/config/app.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/config/document-type-requests.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$dtrTableReady = false;
$dtrRequests = [];

try {
    $pdo = getDatabaseConnection();
    $dtrTableReady = dtr_table_exists($pdo);

    if ($dtrTableReady) {
        $actor = dtr_user_context($pdo, (int)$_SESSION['user_id']);
        if (!dtr_is_requester_role((string)($actor['role_name'] ?? ''))) {
            app_redirect_to_role_dashboard((string)($_SESSION['role_name'] ?? ''));
        }

        $stmt = $pdo->prepare(
            'SELECT dtr.*,
                    TRIM(CONCAT(COALESCE(req.first_name, \'\'), \' \', COALESCE(req.last_name, \'\'))) AS requester_name,
                    rl.name AS requester_role,
                    o.name AS requester_office_name
             FROM document_type_requests dtr
             LEFT JOIN users req ON req.id = dtr.requested_by_user_id
             LEFT JOIN roles rl ON rl.id = req.role_id
             LEFT JOIN offices o ON o.id = dtr.requested_by_office_id
             WHERE dtr.requested_by_user_id = :user_id
             ORDER BY dtr.created_at DESC, dtr.id DESC
             LIMIT 300'
        );
        $stmt->execute(['user_id' => (int)$_SESSION['user_id']]);
        $dtrRequests = $stmt->fetchAll() ?: [];
        $dtrSummary = dtr_fetch_summary($pdo, (int)$_SESSION['user_id']);
    }
} catch (Throwable $exception) {
    $dtrTableReady = false;
    $dtrRequests = [];
    $dtrSummary = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
    ];
}

if (!isset($dtrSummary) || !is_array($dtrSummary)) {
    $dtrSummary = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
    ];
}

$dtrStatsCards = [
    ['label' => 'My Requests', 'value' => (string)$dtrSummary['total'], 'icon' => 'blue'],
    ['label' => 'Pending Review', 'value' => (string)$dtrSummary['pending'], 'icon' => 'orange'],
    ['label' => 'Approved', 'value' => (string)$dtrSummary['approved'], 'icon' => 'green'],
    ['label' => 'Rejected', 'value' => (string)$dtrSummary['rejected'], 'icon' => 'violet'],
];

$roleName = 'CENRO';
$initialsFallback = 'CE';
$pageTitle = 'Document Type Requests | DENR Region XII eDATS';
$activeMenu = 'document_type_requests';
$brandSubtitle = 'CENRO Portal';
$pageHeading = 'Document Type Requests';
$pageSubtitle = 'Request new document types for RECORDS-UNIT review and approval.';
$searchPlaceholder = 'Search request id or document type';
$renderStandardContent = false;
$customSectionInclude = dirname(__DIR__, 3) . '/app/modules/document-type-requests-panel.php';
$extraCss = [app_url('assets/css/document-type-requests.css')];
$enableCharts = false;
$hideHeaderSearch = true;
$stickyActions = [];
$pageActions = [];
$dtrMode = 'requester';
$dtrCsrfToken = (string)($_SESSION['csrf_token'] ?? '');

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';


