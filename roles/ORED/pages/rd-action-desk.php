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

$officeId = (int) ($_SESSION['office_id'] ?? 0);
$userId = (int) ($_SESSION['user_id'] ?? 0);
$roleName = 'ORED';
$sessionRoleKey = app_normalize_role_key((string)($_SESSION['role_name'] ?? ''));
$isOredSigningAccount = $sessionRoleKey === 'ORED_SIGN';
$queueRows = [];
$routeOffices = [];
$metrics = [
    'total_scope' => 0,
    'pending_total' => 0,
    'due_today_total' => 0,
    'due_soon_total' => 0,
    'overdue_total' => 0,
    'completed_total' => 0,
    'completed_week' => 0,
    'created_today' => 0,
    'external_today' => 0,
    'pending_approval_total' => 0,
    'pending_sign_total' => 0,
    'pending_forward_total' => 0,
    'for_signature_total' => 0,
    'for_clearance_total' => 0,
    'for_endorsement_total' => 0,
    'for_validation_total' => 0,
    'at_risk_total' => 0,
    'overrides_30d' => 0,
    'compliance_rate' => 100,
];

try {
    $pdo = getDatabaseConnection();
    $queueRows = dashboard_fetch_pending_receive_rows($pdo, $officeId, 50, true, false, $userId, $sessionRoleKey);
    $routeOffices = dashboard_fetch_route_offices($pdo, $officeId);
    $metrics = dashboard_fetch_role_metrics($pdo, $officeId, null, $userId, $sessionRoleKey);
} catch (Throwable $exception) {
    $queueRows = [];
    $routeOffices = [];
}

$pendingReceivedTotal = (int) ($metrics['pending_total'] ?? 0);
$pendingApprovalTotal = (int) ($metrics['pending_approval_total'] ?? 0);
$pendingSignFromQueue = 0;
$pendingForwardFromQueue = 0;
foreach ($queueRows as $queueRow) {
    $status = strtolower(trim((string) ($queueRow['status'] ?? '')));
    if ($status === '') {
        continue;
    }
    $isTerminal = str_contains($status, 'released')
        || str_contains($status, 'signed')
        || str_contains($status, 'completed')
        || str_contains($status, 'closed')
        || str_contains($status, 'resolved')
        || str_contains($status, 'done')
        || str_contains($status, 'cancelled')
        || str_contains($status, 'canceled');
    if ($isTerminal) {
        continue;
    }
    $isAwaitingSignature = str_contains($status, 'signature')
        || str_contains($status, 'pending sign')
        || str_contains($status, 'for sign')
        || str_contains($status, 'approved');
    $isApproved = str_contains($status, 'approved') || str_contains($status, 'approval');
    $isForwarded = str_contains($status, 'forward') || str_starts_with($status, 'assigned to ');
    $isSignedCompleted = str_contains($status, 'signed') || str_contains($status, 'completed');
    if ($isAwaitingSignature && !$isSignedCompleted && !$isForwarded) {
        $pendingSignFromQueue++;
    }
    if ($isApproved && !$isForwarded) {
        $pendingForwardFromQueue++;
    }
}
$pendingSignTotal = max((int) ($metrics['pending_sign_total'] ?? 0), $pendingSignFromQueue);
$pendingForwardTotal = max((int) ($metrics['pending_forward_total'] ?? 0), $pendingForwardFromQueue);
$snapshotBase = max($pendingApprovalTotal, $pendingSignTotal, $pendingForwardTotal, 1);
$pendingApprovalWidth = (string) (int) round(($pendingApprovalTotal / $snapshotBase) * 100) . '%';
$pendingSignWidth = (string) (int) round(($pendingSignTotal / $snapshotBase) * 100) . '%';
$pendingForwardWidth = (string) (int) round(($pendingForwardTotal / $snapshotBase) * 100) . '%';

$tableRows = [];
foreach ($queueRows as $queueRow) {
    $tableRows[] = [
        'value' => [
            (string) ($queueRow['tracking_id'] ?? '-'),
            (string) ($queueRow['subject'] ?? '-'),
            (string) ($queueRow['document_type'] ?? '-'),
            (string) ($queueRow['date_received'] ?? '-'),
            (string) ($queueRow['time_remaining'] ?? '-'),
            (string) ($queueRow['status'] ?? '-'),
            (string) ($queueRow['date_created'] ?? '-'),
            $isOredSigningAccount
                ? 'View Tracking Slip | Receive | Approve | Sign | Undo Sign | Pending | Forward | Return | Override | Print Package'
                : 'View Tracking Slip | Receive | Approve | Pending | Forward | Return | Print Package',
        ],
        'meta' => [
            'document_id' => (string) ($queueRow['document_id'] ?? 0),
            'tracking_id' => (string) ($queueRow['tracking_id'] ?? ''),
            'status_raw' => (string) ($queueRow['status'] ?? ''),
            'date_received_raw' => (string) ($queueRow['date_received_raw'] ?? ''),
            'date_created_raw' => (string) ($queueRow['date_created_raw'] ?? ''),
            'has_section_receive' => (int) ($queueRow['has_section_receive'] ?? 0) > 0 ? '1' : '0',
            'has_signed_action' => (int) ($queueRow['has_signed_action'] ?? 0) > 0 ? '1' : '0',
            'origin_office_id' => (string) ((int) ($queueRow['origin_office_id'] ?? 0)),
            'current_office_id' => (string) ((int) ($queueRow['current_office_id'] ?? 0)),
            'pending_office_id' => (string) ((int) ($queueRow['pending_office_id'] ?? 0)),
        ],
    ];
}

$pageTitle = 'RD Action Desk | DENR Region XII DTMIS';
$brandSubtitle = 'ORED Portal';
$pageHeading = 'RD Action Desk';
$pageSubtitle = 'Dedicated queue for approval, signature, and forwarding actions.';
$activeMenu = 'rd_action_desk';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=pending_receive_action');
$tableTitle = 'RD Action Desk';
$tableColumns = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Date Received', 'Time Remaining', 'Status', 'Date Created', 'Quick Actions'];
$pageActions = $isOredSigningAccount
    ? ['View Tracking Slip', 'Print Package', 'Receive', 'Approve', 'Sign', 'Undo Sign', 'Forward', 'Return', 'Override']
    : ['View Tracking Slip', 'Print Package', 'Receive', 'Approve', 'Forward', 'Return'];
$stickyActions = [];
$queueControlsPlacement = 'table_card';
$showStatusCategoryFilter = true;
$statusFilterOptions = [
    ['value' => '', 'label' => 'All Status Categories'],
    ['value' => 'awaiting_receive', 'label' => 'Awaiting Receive'],
    ['value' => 'received', 'label' => 'Received'],
    ['value' => 'approved', 'label' => 'Approved'],
    ['value' => 'signed_completed', 'label' => 'Signed / Completed'],
    ['value' => 'pending_forward', 'label' => 'Pending Forward'],
];
$dateFilterPlacement = 'table_card';
$routeOffices = is_array($routeOffices ?? null) ? $routeOffices : [];

$kpiCards = [];
$panels = [];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
