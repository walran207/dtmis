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
$roleName = 'PENRO';
$queueRows = [];
$metrics = [
    'pending_approval_total' => 0,
    'pending_forward_total' => 0,
    'released_total' => 0,
    'due_soon_total' => 0,
    'overdue_total' => 0,
];

try {
    $pdo = getDatabaseConnection();
    $queueRows = dashboard_fetch_pending_receive_rows($pdo, $officeId, 80, true);
    $metrics   = dashboard_fetch_role_metrics($pdo, $officeId);
} catch (Throwable $exception) {
    $queueRows = [];
}

$tableRows = [];
foreach ($queueRows as $row) {
    $tableRows[] = [
        'value' => [
            (string)($row['tracking_id'] ?? '-'),
            (string)($row['subject'] ?? '-'),
            (string)($row['document_type'] ?? '-'),
            (string)($row['current_holder'] ?? '-'),
            (string)($row['date_received'] ?? '-'),
            (string)($row['time_remaining'] ?? '-'),
            (string)($row['status'] ?? '-'),
            'View | Receive | Approve | Forward',
        ],
        'meta' => [
            'document_id'       => (string)($row['document_id'] ?? 0),
            'tracking_id'       => (string)($row['tracking_id'] ?? ''),
            'status_raw'        => (string)($row['status'] ?? ''),
            'date_received_raw' => (string)($row['date_received_raw'] ?? ''),
            'date_created_raw'  => (string)($row['date_created_raw'] ?? ''),
            'origin_office_id'  => (string)((int)($row['origin_office_id'] ?? 0)),
            'current_office_id' => (string)((int)($row['current_office_id'] ?? 0)),
            'pending_office_id' => (string)((int)($row['pending_office_id'] ?? 0)),
        ],
    ];
}

$pendingCount = count($tableRows);
$pendingApprovalTotal = (int)($metrics['pending_approval_total'] ?? 0);
$pendingForwardTotal = (int)($metrics['pending_forward_total'] ?? 0);
$releasedTotal = (int)($metrics['released_total'] ?? 0);
$workflowBase = max($pendingCount, $pendingApprovalTotal, $pendingForwardTotal, $releasedTotal, 1);

$pendingReceivedWidth = (string)(int)round(($pendingCount / $workflowBase) * 100) . '%';
$pendingApprovalWidth = (string)(int)round(($pendingApprovalTotal / $workflowBase) * 100) . '%';
$pendingForwardWidth = (string)(int)round(($pendingForwardTotal / $workflowBase) * 100) . '%';
$releasedWidth = (string)(int)round(($releasedTotal / $workflowBase) * 100) . '%';

$initialsFallback = 'PE';
$pageTitle = 'Pending Receive | DENR Region XII eDATS';
$activeMenu = 'inbox_pending_receive';
$brandSubtitle = 'PENRO Portal';
$pageHeading = 'Pending Receive';
$pageSubtitle = 'Items awaiting PENRO receive action.';
$searchPlaceholder = 'Search Tracking ID or subject';
$queueControlsPlacement = 'table_card';
$statusFilterOptions = [
    ['value' => '', 'label' => 'All Status Categories'],
    ['value' => 'awaiting_receive', 'label' => 'Awaiting Receive'],
    ['value' => 'received', 'label' => 'Received'],
    ['value' => 'approved', 'label' => 'Approved'],
    ['value' => 'released', 'label' => 'Released'],
    ['value' => 'new_created', 'label' => 'New Docs Created'],
    ['value' => 'signed_completed', 'label' => 'Signed / Completed'],
];
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=pending_receive_penro');

$kpiCards = [
    ['label' => 'Pending Received', 'icon' => 'blue', 'value' => (string)$pendingCount],
    ['label' => 'Pending Approval', 'icon' => 'orange', 'value' => (string)$pendingApprovalTotal],
    ['label' => 'Pending Forward', 'icon' => 'violet', 'value' => (string)$pendingForwardTotal],
    ['label' => 'Released', 'icon' => 'green', 'value' => (string)$releasedTotal],
];
$panels = [
    [
        'title' => 'Pending Receive Workflow Mix',
        'rows' => [
            ['label' => 'Pending Received', 'width' => $pendingReceivedWidth, 'value' => (string)$pendingCount],
            ['label' => 'Pending Approval', 'width' => $pendingApprovalWidth, 'value' => (string)$pendingApprovalTotal],
            ['label' => 'Pending Forward', 'width' => $pendingForwardWidth, 'value' => (string)$pendingForwardTotal],
            ['label' => 'Released', 'width' => $releasedWidth, 'value' => (string)$releasedTotal],
        ],
    ],
    [
        'title' => 'Pending Receive SLA Watch',
        'rows' => [
            ['label' => 'Due Soon / Due Today', 'width' => '0%', 'value' => (string)((int)($metrics['due_soon_total'] ?? 0))],
            ['label' => 'Overdue', 'width' => '0%', 'value' => (string)((int)($metrics['overdue_total'] ?? 0))],
            ['label' => 'Returned', 'width' => '0%', 'value' => '0'],
            ['label' => 'Received events', 'width' => '0%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'Receive Desk Focus',
        'chips' => [
            'Prioritize oldest incoming items',
            'Receive before routing to next step',
            'Validate ARTA category and due date',
            'Capture clear remarks in the slip',
        ],
    ],
];
$tableTitle = 'Pending Receive Queue';
$tableColumns = json_decode('["Tracking ID","Subject","Document Type (+ ARTA)","Current Holder","Date Received","Time Remaining","Status","Quick Actions"]', true);
$pageActions = json_decode('["View","Receive","Approve","Forward"]', true);
$stickyActions = json_decode('["Receive","Approve","Forward","Return / Request Info"]', true);
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"Pending Receive has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';

