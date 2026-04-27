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
$roleName = 'CENRO';
$queueRows = [];
$metrics = [
    'pending_total'   => 0,
    'due_soon_total'  => 0,
    'overdue_total'   => 0,
    'due_today_total' => 0,
    'created_today'   => 0,
    'completed_week'  => 0,
];

try {
    $pdo = getDatabaseConnection();
    // Only docs returned to this CENRO office by RECORDS-UNIT/ORED
    $queueRows = dashboard_fetch_returned_from_regional_rows($pdo, $officeId, 80);
    $metrics   = dashboard_fetch_role_metrics($pdo, $officeId);
} catch (Throwable $exception) {
    $queueRows = [];
}

$tableRows = [];
$awaitingReceiveCount = 0;
$forCorrectionCount = 0;
$inProcessCount = 0;
$onHoldCount = 0;
foreach ($queueRows as $row) {
    $statusRaw = strtolower(trim((string)($row['status'] ?? '')));
    $hasReceived = str_contains($statusRaw, 'received')
        || str_contains($statusRaw, 'recieved')
        || str_contains($statusRaw, 'in process');
    $hasCorrection = str_contains($statusRaw, 'return')
        || str_contains($statusRaw, 'correction')
        || str_contains($statusRaw, 'comply')
        || str_contains($statusRaw, 'compliance')
        || str_contains($statusRaw, 'revise')
        || str_contains($statusRaw, 'resubmit');
    $hasAwaiting = str_contains($statusRaw, 'awaiting receive')
        || str_contains($statusRaw, 'for receive')
        || str_contains($statusRaw, 'in transit')
        || str_contains($statusRaw, 'routed')
        || str_contains($statusRaw, 'assigned');
    $hasOnHold = str_contains($statusRaw, 'pending')
        || str_contains($statusRaw, 'hold');

    if ($hasCorrection) {
        $forCorrectionCount++;
    } elseif ($hasReceived) {
        $inProcessCount++;
    } elseif ($hasAwaiting || $statusRaw === '') {
        $awaitingReceiveCount++;
    } elseif ($hasOnHold) {
        $onHoldCount++;
    } else {
        $onHoldCount++;
    }

    $tableRows[] = [
        'value' => [
            (string)($row['tracking_id'] ?? '-'),
            (string)($row['subject'] ?? '-'),
            (string)($row['document_type'] ?? '-'),
            (string)($row['current_holder'] ?? '-'),
            (string)($row['date_received'] ?? '-'),
            (string)($row['time_remaining'] ?? '-'),
            (string)($row['status'] ?? '-'),
            'View | Comply | Resubmit',
        ],
        'meta' => [
            'document_id'        => (string)($row['document_id'] ?? 0),
            'tracking_id'        => (string)($row['tracking_id'] ?? ''),
            'status_raw'         => (string)($row['status'] ?? ''),
            'date_received_raw'  => (string)($row['date_received_raw'] ?? ''),
            'date_created_raw'   => (string)($row['date_created_raw'] ?? ''),
            'has_signed_action'  => (int)($row['has_signed_action'] ?? 0) > 0 ? '1' : '0',
            'origin_office_id'   => (string)((int)($row['origin_office_id'] ?? 0)),
            'current_office_id'  => (string)((int)($row['current_office_id'] ?? 0)),
            'pending_office_id'  => (string)((int)($row['pending_office_id'] ?? 0)),
        ],
    ];
}

$returnedCount = count($tableRows);
$queueMixBase = max(
    $forCorrectionCount,
    $awaitingReceiveCount,
    $inProcessCount,
    $onHoldCount,
    1
);
$forCorrectionWidth = (string)(int)round(($forCorrectionCount / $queueMixBase) * 100) . '%';
$awaitingReceiveWidth = (string)(int)round(($awaitingReceiveCount / $queueMixBase) * 100) . '%';
$inProcessWidth = (string)(int)round(($inProcessCount / $queueMixBase) * 100) . '%';
$onHoldWidth = (string)(int)round(($onHoldCount / $queueMixBase) * 100) . '%';

$initialsFallback  = 'CE';
$pageTitle         = 'Returned from Regional | DENR Region XII eDATS';
$activeMenu        = 'inbox_returned_regional';
$brandSubtitle     = 'CENRO Portal';
$pageHeading       = 'Returned from Regional';
$pageSubtitle      = 'Documents sent back by RECORDS-UNIT/ORED to CENRO for compliance corrections.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=returned_regional');
$queueControlsPlacement = 'table_card';
$statusFilterOptions = [
    ['value' => '', 'label' => 'All Returned Docs'],
    ['value' => 'awaiting_receive', 'label' => 'Awaiting Receive'],
    ['value' => 'for_correction', 'label' => 'For Correction / Compliance'],
    ['value' => 'on_hold', 'label' => 'Pending / On Hold'],
    ['value' => 'received', 'label' => 'Received / In Process'],
];

$kpiCards = [
    ['label' => 'Returned to CENRO', 'icon' => 'orange', 'value' => (string)$returnedCount],
    ['label' => 'For Correction / Compliance', 'icon' => 'violet', 'value' => (string)$forCorrectionCount],
    ['label' => 'Awaiting Receive', 'icon' => 'blue', 'value' => (string)$awaitingReceiveCount],
    ['label' => 'Pending / On Hold', 'icon' => 'green', 'value' => (string)$onHoldCount],
];

$panels = [
    [
        'title' => 'Returned Compliance Queue Mix',
        'rows'  => [
            ['label' => 'For Correction / Compliance', 'value' => (string)$forCorrectionCount, 'width' => $forCorrectionWidth],
            ['label' => 'Awaiting Receive', 'value' => (string)$awaitingReceiveCount, 'width' => $awaitingReceiveWidth],
            ['label' => 'Pending / On Hold', 'value' => (string)$onHoldCount, 'width' => $onHoldWidth],
            ['label' => 'Received / In Process', 'value' => (string)$inProcessCount, 'width' => $inProcessWidth],
        ],
    ],
];

$tableTitle    = 'Returned from Regional Queue';
$tableColumns  = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Current Holder', 'Date Returned', 'Time Remaining', 'Status', 'Quick Actions'];
$pageActions   = ['View', 'Comply', 'Resubmit / Forward'];
$stickyActions = ['View Tracking Slip', 'Forward / Resubmit', 'Return / Request Info'];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';

