<?php
declare(strict_types=1);

if (!isset($roleBasePath) || !is_string($roleBasePath) || $roleBasePath === '') {
    $roleBasePath = dirname(__DIR__);
}

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/dashboard-data.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

$roleName = trim((string)($forwardedSearchRoleName ?? ''));
$initialsFallback = trim((string)($forwardedSearchInitialsFallback ?? 'AU'));
$activeMenu = trim((string)($forwardedSearchActiveMenu ?? 'search'));
$brandSubtitle = trim((string)($forwardedSearchBrandSubtitle ?? 'Role Portal'));
$pageSubtitle = trim((string)($forwardedSearchPageSubtitle ?? 'Search your forwarded routing history.'));
$tableTitle = trim((string)($forwardedSearchTableTitle ?? 'Forwarded Transaction Tracker'));
$actionTypesInput = is_array($forwardedSearchActionTypes ?? null)
    ? $forwardedSearchActionTypes
    : ['Forwarded', 'Rerouted', 'Returned'];

if ($roleName === '') {
    throw new RuntimeException('Forwarded search role name is required.');
}

$actionTypes = [];
foreach ($actionTypesInput as $type) {
    $value = trim((string)$type);
    if ($value === '') {
        continue;
    }
    $actionTypes[] = $value;
}
if (empty($actionTypes)) {
    $actionTypes = ['Forwarded', 'Rerouted', 'Returned'];
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$actedRows = [];

try {
    $pdo = getDatabaseConnection();
    $actedRows = dashboard_fetch_actor_tracker_rows($pdo, $userId, $actionTypes, 120);
} catch (Throwable $exception) {
    $actedRows = [];
}

$forwardedCount = 0;
$reroutedCount = 0;
$returnedCount = 0;
foreach ($actedRows as $row) {
    $actionType = strtolower(trim((string)($row['actor_action_type'] ?? '')));
    if (str_contains($actionType, 'reroute')) {
        $reroutedCount++;
        continue;
    }
    if (str_contains($actionType, 'return')) {
        $returnedCount++;
        continue;
    }
    if (str_contains($actionType, 'forward') || str_contains($actionType, 'release')) {
        $forwardedCount++;
    }
}

$tableRows = [];
foreach ($actedRows as $row) {
    $actionTypeLabel = trim((string)($row['actor_action_type'] ?? ''));
    $actionRemarks = trim((string)($row['actor_action_remarks'] ?? ''));
    if ($actionTypeLabel === '') {
        $actionTypeLabel = '-';
    } elseif ($actionRemarks !== '' && strcasecmp($actionRemarks, $actionTypeLabel) !== 0) {
        $normalizedRemarks = preg_replace('/\s+/', ' ', $actionRemarks);
        if ($normalizedRemarks !== null && $normalizedRemarks !== '') {
            if (strlen($normalizedRemarks) > 64) {
                $normalizedRemarks = substr($normalizedRemarks, 0, 61) . '...';
            }
            $actionTypeLabel .= ' | ' . $normalizedRemarks;
        }
    }

    $tableRows[] = [
        'value' => [
            (string)($row['tracking_id'] ?? '-'),
            (string)($row['sender'] ?? '-'),
            (string)($row['subject'] ?? '-'),
            (string)($row['document_type'] ?? '-'),
            (string)($row['current_holder'] ?? '-'),
            (string)($row['date_received'] ?? '-'),
            $actionTypeLabel,
            (string)($row['status'] ?? '-'),
            'View Tracking Slip | Print Package',
        ],
        'meta' => [
            'document_id' => (string)($row['document_id'] ?? 0),
            'tracking_id' => (string)($row['tracking_id'] ?? ''),
            'date_received_raw' => (string)($row['date_received_raw'] ?? ''),
            'date_created_raw' => (string)($row['date_created_raw'] ?? ''),
            'status_raw' => (string)($row['status'] ?? ''),
            'origin_office_id' => (string)($row['origin_office_id'] ?? 0),
            'current_office_id' => (string)((int)($row['current_office_id'] ?? 0)),
            'pending_office_id' => (string)((int)($row['pending_office_id'] ?? 0)),
            'current_office_level' => (string)($row['current_office_level'] ?? ''),
            'pending_office_level' => (string)($row['pending_office_level'] ?? ''),
            'current_holder' => (string)($row['current_holder'] ?? ''),
            'has_section_receive' => (int)($row['has_section_receive'] ?? 0) > 0 ? '1' : '0',
            'has_signed_action' => (string)(strtolower(trim((string)($row['actor_action_type'] ?? ''))) === 'signed' ? '1' : '0'),
        ],
    ];
}

$pageTitle = 'Search Documents | DENR Region XII DTMIS';
$pageHeading = 'Search Documents';
$searchPlaceholder = 'Search Tracking ID, subject, status, or current holder';
$dashboardLivePath = app_url(
    'actions/dashboard-live.php?scope=approved&limit=120&actions=' . rawurlencode(implode(',', $actionTypes))
);

$kpiCards = [];

$panels = [];

$tableColumns = ['Tracking ID', 'Sender', 'Subject', 'Document Type (+ ARTA)', 'Current Holder', 'Last Action Time', 'Last Action by Me', 'Status', 'Quick Actions'];
$pageActions = ['View Tracking Slip', 'Print Package', 'Search Transactions'];
$stickyActions = ['View Tracking Slip', 'Print Package', 'Search'];
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$dateFilterPlacement = 'table_card';
$showQrReceiveScanner = false;
$notifications = json_decode('[{"title":"Forwarded tracker synced","timeLabel":"Now","message":"Your outbound routing history was refreshed.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"A document you routed changed holder or status.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"Search reminder","timeLabel":"2h ago","message":"Use search and date range to review forwarded, rerouted, and returned documents.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 2) . '/app/templates/role-page-template.php';
