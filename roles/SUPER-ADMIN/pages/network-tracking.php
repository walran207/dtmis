<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$networkSnapshot = [
    'summary' => [
        'active_users' => 0,
        'online_users' => 0,
        'offline_users' => 0,
        'traffic_last_5m' => 0,
        'traffic_last_1h' => 0,
        'inbound_last_1h' => 0,
        'outbound_last_1h' => 0,
        'approval_last_1h' => 0,
    ],
    'action_mix' => [],
    'traffic_points' => [],
    'recent_events' => [],
    'server_time' => date('c'),
];

try {
    $pdo = getDatabaseConnection();
    $networkSnapshot = super_admin_fetch_network_traffic($pdo, 5, 12);
} catch (Throwable $exception) {
    $networkSnapshot = $networkSnapshot;
}

$summary = is_array($networkSnapshot['summary'] ?? null) ? $networkSnapshot['summary'] : [];

$pageTitle = 'Network Tracking | DENR Region XII eDATS';
$brandSubtitle = 'Super Admin Portal';
$pageHeading = 'Live Network Tracking';
$pageSubtitle = 'Monitor online/offline state and workflow traffic in near real-time.';
$activeMenu = 'network_tracking';
$showQueueTable = false;
$renderStandardContent = false;
$customSectionInclude = __DIR__ . '/sections/network-tracking-panel.php';
$disableLiveDashboardRefresh = true;
$hideHeaderSearch = true;
$showQrReceiveScanner = false;
$superAdminNetworkEndpoint = app_url('actions/super-admin-network.php');
$superAdminNetworkSnapshot = $networkSnapshot;

$kpiCards = [
    ['label' => 'Online Users', 'value' => (string)($summary['online_users'] ?? 0), 'icon' => 'green'],
    ['label' => 'Offline Users', 'value' => (string)($summary['offline_users'] ?? 0), 'icon' => 'violet'],
    ['label' => 'Traffic (5m)', 'value' => (string)($summary['traffic_last_5m'] ?? 0), 'icon' => 'blue'],
    ['label' => 'Traffic (1h)', 'value' => (string)($summary['traffic_last_1h'] ?? 0), 'icon' => 'orange'],
    ['label' => 'Inbound (1h)', 'value' => (string)($summary['inbound_last_1h'] ?? 0), 'icon' => 'blue'],
    ['label' => 'Outbound (1h)', 'value' => (string)($summary['outbound_last_1h'] ?? 0), 'icon' => 'orange'],
];

$panels = [
    [
        'title' => 'Live Tracking Signals',
        'chips' => [
            'Auto-refresh every 20 seconds',
            'Traffic buckets in 5-minute windows',
            'Action mix for last hour',
            'Recent events feed',
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
