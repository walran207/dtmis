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

if (!function_exists('pacdo_branch_level_prefix')) {
    function pacdo_branch_level_prefix(string $level): string
    {
        $normalized = strtoupper(trim($level));
        if ($normalized === 'PROVINCIAL') {
            return 'PENRO';
        }
        if ($normalized === 'COMMUNITY') {
            return 'CENRO';
        }
        if ($normalized === 'PROTECTED AREA') {
            return 'PASU';
        }
        return 'Office';
    }
}

$officeId = (int)($_SESSION['office_id'] ?? 0);
$roleName = 'PENRO_ADMIN_RECORD';
$initialsFallback = 'PC';

$activityCounters = [
    'received_events' => 0,
    'approved_events' => 0,
    'forwarded_events' => 0,
    'reroute_events' => 0,
];
$notifications = [
    [
        'title' => 'Branch analytics ready',
        'timeLabel' => 'Now',
        'message' => 'Branch-level workload snapshot is active.',
        'unread' => true,
        'datetime' => date('c'),
    ],
];
$tableRows = [];
$summary = [
    'branch_count' => 0,
    'pending_total' => 0,
    'due_total' => 0,
    'overdue_total' => 0,
    'completed_week' => 0,
    'risk_branch_count' => 0,
    'compliance_total' => 0,
];
$routeOffices = [];

try {
    $pdo = getDatabaseConnection();
    $activityCounters = dashboard_fetch_activity_counters($pdo, $officeId, 30);
    $dbNotifications = dashboard_fetch_notifications($pdo, $officeId, 12, $roleName);
    if (!empty($dbNotifications)) {
        $notifications = $dbNotifications;
    }

    $officesStmt = $pdo->query(
        "SELECT id, name, level
         FROM offices
         WHERE UPPER(COALESCE(level, '')) IN ('PROVINCIAL', 'COMMUNITY', 'PROTECTED AREA')
         ORDER BY
            CASE UPPER(level)
                WHEN 'PROVINCIAL' THEN 1
                WHEN 'COMMUNITY' THEN 2
                WHEN 'PROTECTED AREA' THEN 3
                ELSE 4
            END,
            name ASC
         LIMIT 200"
    );
    $branchOffices = $officesStmt ? ($officesStmt->fetchAll() ?: []) : [];

    $branchRows = [];
    foreach ($branchOffices as $office) {
        $branchOfficeId = (int)($office['id'] ?? 0);
        if ($branchOfficeId <= 0) {
            continue;
        }

        $metrics = dashboard_fetch_role_metrics($pdo, $branchOfficeId);
        $hasActivity = (int)($metrics['total_scope'] ?? 0) > 0
            || (int)($metrics['pending_total'] ?? 0) > 0
            || (int)($metrics['completed_week'] ?? 0) > 0;
        if (!$hasActivity) {
            continue;
        }

        $dueSoonToday = (int)($metrics['due_today_total'] ?? 0) + (int)($metrics['due_soon_total'] ?? 0);
        $branchRows[] = [
            'office_name' => trim((string)($office['name'] ?? '')),
            'office_level' => trim((string)($office['level'] ?? '')),
            'pending_total' => (int)($metrics['pending_total'] ?? 0),
            'due_total' => $dueSoonToday,
            'overdue_total' => (int)($metrics['overdue_total'] ?? 0),
            'completed_week' => (int)($metrics['completed_week'] ?? 0),
            'compliance_rate' => (int)($metrics['compliance_rate'] ?? 100),
        ];
    }

    usort($branchRows, static function (array $left, array $right): int {
        $riskLeft = [$left['overdue_total'] ?? 0, $left['due_total'] ?? 0, $left['pending_total'] ?? 0];
        $riskRight = [$right['overdue_total'] ?? 0, $right['due_total'] ?? 0, $right['pending_total'] ?? 0];
        if ($riskLeft === $riskRight) {
            return strcmp((string)($left['office_name'] ?? ''), (string)($right['office_name'] ?? ''));
        }
        return $riskRight <=> $riskLeft;
    });

    foreach ($branchRows as $branchRow) {
        $summary['branch_count'] += 1;
        $summary['pending_total'] += (int)$branchRow['pending_total'];
        $summary['due_total'] += (int)$branchRow['due_total'];
        $summary['overdue_total'] += (int)$branchRow['overdue_total'];
        $summary['completed_week'] += (int)$branchRow['completed_week'];
        $summary['compliance_total'] += (int)$branchRow['compliance_rate'];

        $branchRiskLoad = (int)$branchRow['due_total'] + (int)$branchRow['overdue_total'];
        if ($branchRiskLoad > 0) {
            $summary['risk_branch_count'] += 1;
        }

        $prefix = pacdo_branch_level_prefix((string)$branchRow['office_level']);
        $tableRows[] = [
            'value' => [
                $prefix . ': ' . (string)$branchRow['office_name'],
                (string)$branchRow['pending_total'],
                (string)$branchRow['due_total'],
                (string)$branchRow['overdue_total'],
                (string)$branchRow['completed_week'],
                (string)$branchRow['compliance_rate'] . '%',
            ],
            'meta' => [],
        ];
    }

    if (empty($tableRows)) {
        $tableRows[] = [
            'value' => [
                'No active branch records found.',
                '0',
                '0',
                '0',
                '0',
                '100%',
            ],
            'meta' => [],
        ];
    }

    $routeOffices = dashboard_fetch_route_offices($pdo, $officeId);
} catch (Throwable $exception) {
    $tableRows = [
        [
            'value' => [
                'Unable to load branch analytics right now.',
                '0',
                '0',
                '0',
                '0',
                '0%',
            ],
            'meta' => [],
        ],
    ];
}

$riskBase = max(
    $summary['pending_total'],
    $summary['due_total'],
    $summary['overdue_total'],
    $summary['completed_week'],
    1
);
$pendingWidth = (string)(int)round(($summary['pending_total'] / $riskBase) * 100) . '%';
$dueWidth = (string)(int)round(($summary['due_total'] / $riskBase) * 100) . '%';
$overdueWidth = (string)(int)round(($summary['overdue_total'] / $riskBase) * 100) . '%';
$completedWidth = (string)(int)round(($summary['completed_week'] / $riskBase) * 100) . '%';
$averageComplianceRate = $summary['branch_count'] > 0
    ? (int)max(0, min(100, round($summary['compliance_total'] / $summary['branch_count'])))
    : 100;
$totalRiskItems = $summary['due_total'] + $summary['overdue_total'];
$branchesOnTrack = max($summary['branch_count'] - $summary['risk_branch_count'], 0);
$topRiskRow = !empty($tableRows) ? ($tableRows[0]['value'] ?? []) : [];
$topRiskBranchName = $summary['branch_count'] > 0 ? (string)($topRiskRow[0] ?? 'None') : 'None';
$topRiskLoad = $summary['branch_count'] > 0
    ? ((int)($topRiskRow[2] ?? 0) + (int)($topRiskRow[3] ?? 0))
    : 0;

$pageTitle = 'Branch Analytics | DENR Region XII eDATS';
$activeMenu = 'penro_admin_record_analytics';
$brandSubtitle = 'PENRO Admin Record Portal';
$pageHeading = 'Branch Analytics';
$pageSubtitle = 'Branch workload and ARTA risk totals across CENRO, PENRO, and PASU offices.';
$searchPlaceholder = 'Search branch office, level, or totals';

$kpiCards = [
    ['label' => 'Active Branches', 'value' => (string)$summary['branch_count'], 'icon' => 'blue'],
    ['label' => 'Workload Backlog', 'value' => (string)$summary['pending_total'], 'icon' => 'orange'],
    ['label' => 'Risk Watch Items', 'value' => (string)$totalRiskItems, 'icon' => 'violet'],
    ['label' => 'Average Compliance', 'value' => (string)$averageComplianceRate . '%', 'icon' => 'green'],
];

$panels = [
    [
        'title' => 'Branch Risk Snapshot',
        'rows' => [
            ['label' => 'Backlog Total', 'value' => (string)$summary['pending_total'], 'width' => $pendingWidth],
            ['label' => 'Attention Soon/Today', 'value' => (string)$summary['due_total'], 'width' => $dueWidth],
            ['label' => 'Past-Due Total', 'value' => (string)$summary['overdue_total'], 'width' => $overdueWidth],
            ['label' => 'Closed Last 7 Days', 'value' => (string)$summary['completed_week'], 'width' => $completedWidth],
        ],
    ],
    [
        'title' => 'Branch Coverage Indicators',
        'rows' => [
            ['label' => 'Branches With Risk', 'value' => (string)$summary['risk_branch_count'], 'width' => '100%'],
            ['label' => 'Branches On Track', 'value' => (string)$branchesOnTrack, 'width' => '70%'],
            ['label' => 'Top Branch Risk Load', 'value' => (string)$topRiskLoad, 'width' => '55%'],
            ['label' => 'Mean Compliance (%)', 'value' => (string)$averageComplianceRate . '%', 'width' => '85%'],
        ],
    ],
    [
        'title' => 'Analytics Focus',
        'chips' => [
            'Sorted by branch risk',
            'Top risk: ' . $topRiskBranchName,
            'Coverage: PENRO, CENRO, PASU',
            'Window: last 7 days',
        ],
    ],
];

$tableTitle = 'Branch Workload Summary';
$tableColumns = ['Office / Unit', 'Pending', 'Due Soon / Today', 'Overdue', 'Completed (7d)', 'Compliance'];
$pageActions = [];
$stickyActions = [];
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$showStatusCategoryFilter = false;
$showQrReceiveScanner = false;
$dateFilterPlacement = 'table_card';
$routeOffices = is_array($routeOffices ?? null) ? $routeOffices : [];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';

