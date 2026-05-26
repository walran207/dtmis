<?php
declare(strict_types=1);

require_once __DIR__ . '/_role_helpers.php';
require_once dirname(__DIR__, 2) . '/config/notification-mail.php';

if (function_exists('app_touch_session_heartbeat')) {
    app_touch_session_heartbeat();
}

$fullName = trim(((string)($_SESSION['first_name'] ?? '')) . ' ' . ((string)($_SESSION['last_name'] ?? '')));
if ($fullName === '') {
    $fullName = (string)($_SESSION['email'] ?? 'Authorized User');
}

$roleName = (string)($_SESSION['role_name'] ?? ($roleName ?? 'Authorized User'));
$initials = strtoupper(substr((string)($_SESSION['first_name'] ?? ''), 0, 1) . substr((string)($_SESSION['last_name'] ?? ''), 0, 1));
if ($initials === '') {
    $initials = (string)($initialsFallback ?? 'AU');
}

$pageTitle = (string)($pageTitle ?? 'Role Page | DENR Region XII DTMIS');
$activeMenu = (string)($activeMenu ?? 'dashboard');
$welcomeLoaderMessage = trim((string)($_SESSION['welcome_loader_message'] ?? ''));
if ($welcomeLoaderMessage === '') {
    $firstNameForWelcome = trim((string)($_SESSION['first_name'] ?? ''));
    $welcomeLoaderMessage = $firstNameForWelcome !== '' ? 'Welcome back, ' . $firstNameForWelcome . '!' : 'Welcome back!';
}
$showWelcomeLoader = ((int)($_SESSION['show_welcome_loader'] ?? 0) === 1) && $activeMenu === 'dashboard';
if ($showWelcomeLoader) {
    unset($_SESSION['show_welcome_loader'], $_SESSION['welcome_loader_message']);
}
$footerYear = '2026';
$footerVersion = 'DTMIS v2.0.0';
$brandTitle = 'DENR XII';
$brandSubtitle = (string)($brandSubtitle ?? 'Role Portal');
$pageHeading = (string)($pageHeading ?? 'Workspace');
$pageSubtitle = (string)($pageSubtitle ?? 'Track, review, and route documents.');
$searchPlaceholder = (string)($searchPlaceholder ?? 'Search Tracking ID or subject');

$roleFolder = basename(str_replace('\\', '/', $roleBasePath));
$designSystemCss = [
    app_url('assets/design-system/tokens.css'),
    app_url('assets/design-system/themes.css'),
    app_url('assets/design-system/role-accents.css'),
    app_url('assets/design-system/responsive.css'),
    app_url('assets/design-system/components.css'),
    app_url('assets/design-system/utilities.css'),
];
$defaultCss = array_merge(
    $designSystemCss,
    [
        app_url('assets/css/dashboard.css'),
        app_url('assets/css/dark-overrides.css'),
        app_url('assets/css/support-modals.css'),
    ]
);
$roleCssRelativePath = $roleFolder . '/assets/css/dashboard.css';
$roleCssDiskPath = dirname(__DIR__, 2) . '/roles/' . $roleCssRelativePath;
if (is_file($roleCssDiskPath)) {
    $defaultCss[] = app_url($roleCssRelativePath);
}
$customCss = is_array($extraCss ?? null) ? $extraCss : [];
$extraCss = array_values(array_unique(array_merge($defaultCss, $customCss)));

$kpiCards = is_array($kpiCards ?? null) ? $kpiCards : [
    ['label' => 'Pending', 'value' => '0', 'icon' => 'orange'],
    ['label' => 'Due Soon', 'value' => '0', 'icon' => 'violet'],
    ['label' => 'Completed', 'value' => '0', 'icon' => 'green'],
    ['label' => 'Returned', 'value' => '0', 'icon' => 'blue'],
];

$panels = is_array($panels ?? null) ? $panels : [];
$showLiveFlowTracker = (bool)($showLiveFlowTracker ?? false);
$liveFlowTrackerTitle = (string)($liveFlowTrackerTitle ?? 'Horizontal Document Flow (Live)');
$roleKeyForFlowDefault = app_normalize_role_key($roleName);
if (is_array($liveFlowTrackerSteps ?? null)) {
    $liveFlowTrackerSteps = $liveFlowTrackerSteps;
} elseif (in_array($roleKeyForFlowDefault, ['PENRO_ADMIN_RECORD', 'PENRO_OFFICER', 'PENRO_DIVISION', 'PENRO_SECTION', 'PENRO_SECTION_UNIT'], true)) {
    $liveFlowTrackerSteps = [
        'PENRO Admin Record (Origin Office)',
        'PENRO Officer',
        'PENRO Division',
        'PENRO Section',
        'PENRO Officer Sign',
        'PENRO Admin Record (Returned)',
        'Complete / Release to Next Office',
    ];
} elseif (in_array($roleKeyForFlowDefault, ['CENRO_ADMIN_RECORD', 'CENRO_OFFICER', 'CENRO_SECTION', 'CENRO_UNIT'], true)) {
    $liveFlowTrackerSteps = [
        'CENRO Admin Record (Origin Office)',
        'CENRO Officer',
        'CENRO Section',
        'CENRO Unit',
        'CENRO Officer Sign',
        'CENRO Admin Record (Returned)',
        'Release / Complete or Send',
        'Origin Office or Next Office',
    ];
} elseif (in_array($roleKeyForFlowDefault, ['PAMO_ADMIN', 'PASU_OFFICER', 'PAMO_UNIT'], true)) {
    $liveFlowTrackerSteps = [
        'PAMO Admin',
        'PASU',
        'PAMO Unit',
        'PASU Sign',
        'PAMO Admin (Returned)',
        'Complete / Release to Next Office',
    ];
} else {
    $liveFlowTrackerSteps = [
        'CENRO Admin Record',
        'PENRO Admin Record',
        'RECORDS-UNIT',
        'ORED',
        'ARD TS/ARD MS',
        'Division/Section',
        'ORED Sign',
        'RECORDS-UNIT Release',
        'Origin Office (Returned)',
    ];
}

$tableTitle = (string)($tableTitle ?? 'Queue');
$tableColumns = is_array($tableColumns ?? null) ? $tableColumns : ['Tracking ID', 'Subject', 'Status', 'Action'];
$tableRows = is_array($tableRows ?? null) ? $tableRows : [];
$currentScriptName = strtolower((string)basename((string)($_SERVER['SCRIPT_NAME'] ?? '')));
$showQueueIndicator = role_page_uses_queue_indicator($activeMenu, $currentScriptName);
if ($showQueueIndicator) {
    $tableColumns = role_table_with_indicator_column($tableColumns);
}
$pageActions = is_array($pageActions ?? null) ? $pageActions : ['View', 'Receive', 'Forward'];
$stickyActions = is_array($stickyActions ?? null) ? $stickyActions : [];
$routeOffices = is_array($routeOffices ?? null) ? $routeOffices : [];
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');

$notifications = is_array($notifications ?? null) ? $notifications : [
    ['title' => 'Queue updated', 'message' => 'A new document has been routed to this page.', 'datetime' => '2026-03-14T10:30:00', 'timeLabel' => 'Now', 'unread' => true],
    ['title' => 'Tracking slip updated', 'message' => 'A receive event was added to the custody timeline.', 'datetime' => '2026-03-14T09:45:00', 'timeLabel' => '45m ago', 'unread' => true],
    ['title' => 'Reminder', 'message' => 'Review ARTA at-risk items before end of day.', 'datetime' => '2026-03-14T08:20:00', 'timeLabel' => '2h ago', 'unread' => false],
];
$notificationsPageUrl = app_url('notifications.php');
$notificationFeedUrl = app_url('actions/notifications-feed.php');
$notificationSoundUrl = app_url('assets/audio/notif-sound.wav');

$officeId = (int)($_SESSION['office_id'] ?? 0);
$roleKeyRaw = app_normalize_role_key($roleName);
$roleKey = app_role_behavior_key($roleName);
$regionalOredCanSign = $roleKeyRaw === 'ORED_SIGN';
$isCenroAdminRecordContext = $roleKeyRaw === 'CENRO_ADMIN_RECORD';
$isPenroAdminRecordContext = $roleKeyRaw === 'PENRO_ADMIN_RECORD';
$offlinePolicy = app_offline_policy_for_role($roleName);
$offlineSyncLogUrl = app_url('actions/offline-sync-log.php');
$isRecordsUnitContext = $roleKey === 'RECORDS_UNIT'
    || $roleKey === 'ADMIN_RECORDS_UNIT'
    || $isCenroAdminRecordContext
    || strtoupper($roleFolder) === 'RECORS-UNIT';

if ($activeMenu === 'dashboard' && $roleKey !== 'SUPER_ADMIN') {
    $dashboardFinalStageLabel = 'Released';
    $dashboardFinalStageIcon = 'green';

    if ($roleKey === 'ORED') {
        $dashboardFinalStageLabel = 'Pending Sign';
        $dashboardFinalStageIcon = 'violet';
    } elseif ($isRecordsUnitContext) {
        $dashboardFinalStageLabel = 'Pending Released';
        $dashboardFinalStageIcon = 'green';
    } elseif (in_array($roleKey, ['ARD_TS', 'ARD_MS', 'DIVISION_CHIEF', 'SECTION_STAFF'], true)) {
        $dashboardFinalStageLabel = 'Completed';
        $dashboardFinalStageIcon = 'green';
    }

    $kpiCards = [
        ['label' => 'Pending Received', 'value' => '0', 'icon' => 'blue'],
    ];
    if (in_array($roleKeyRaw, ['CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD', 'PAMO_ADMIN'], true)) {
        $kpiCards[] = ['label' => 'Intake Drafts', 'value' => '0', 'icon' => 'violet'];
        $kpiCards[] = ['label' => 'Pending Forward', 'value' => '0', 'icon' => 'blue'];
        $kpiCards[] = ['label' => $dashboardFinalStageLabel, 'value' => '0', 'icon' => $dashboardFinalStageIcon];
        $kpiCards[] = ['label' => 'Returned', 'value' => '0', 'icon' => 'red'];
    } elseif ($isRecordsUnitContext) {
        $kpiCards[] = ['label' => 'Pending Forward', 'value' => '0', 'icon' => 'violet'];
        $kpiCards[] = ['label' => $dashboardFinalStageLabel, 'value' => '0', 'icon' => $dashboardFinalStageIcon];
        $kpiCards[] = ['label' => 'Returned', 'value' => '0', 'icon' => 'red'];
    } else {
        $kpiCards[] = ['label' => 'Pending Forward', 'value' => '0', 'icon' => 'violet'];
        $kpiCards[] = ['label' => $dashboardFinalStageLabel, 'value' => '0', 'icon' => $dashboardFinalStageIcon];
    }
}

$statCardNavigationTargets = is_array($statCardNavigationTargets ?? null) ? $statCardNavigationTargets : [];
$defaultStatCardNavigationPath = '';
$roleSpecificStatCardNavigationTargets = [];
switch ($roleKey) {
    case 'RECORDS_UNIT':
        if (!$isCenroAdminRecordContext && !$isPenroAdminRecordContext && $roleKeyRaw !== 'PAMO_ADMIN') {
            $defaultStatCardNavigationPath = app_url($roleFolder . '/pacdo-action.php');
            break;
        }
        if ($isCenroAdminRecordContext) {
            $defaultStatCardNavigationPath = app_url($roleFolder . '/pacdo-action.php');
            $cenroPendingReceivePath = app_url($roleFolder . '/pacdo-action.php');
            $cenroIntakePath = app_url($roleFolder . '/create-intake.php');
            $roleSpecificStatCardNavigationTargets = [
                'received' => $cenroPendingReceivePath,
                'approved' => $cenroPendingReceivePath,
                'intake_drafts' => $cenroIntakePath,
            ];
            break;
        }
        if ($isPenroAdminRecordContext) {
            $defaultStatCardNavigationPath = app_url($roleFolder . '/pacdo-action.php');
            $penroPendingReceivePath = app_url($roleFolder . '/pacdo-action.php');
            $penroIntakePath = app_url($roleFolder . '/create-intake.php');
            $roleSpecificStatCardNavigationTargets = [
                'received' => $penroPendingReceivePath,
                'approved' => $penroPendingReceivePath,
                'intake_drafts' => $penroIntakePath,
            ];
            break;
        }
        if ($roleKeyRaw === 'PAMO_ADMIN') {
            $defaultStatCardNavigationPath = app_url($roleFolder . '/pacdo-action.php');
            $pamoPendingReceivePath = app_url($roleFolder . '/pacdo-action.php');
            $pamoIntakePath = app_url($roleFolder . '/create-intake.php');
            $roleSpecificStatCardNavigationTargets = [
                'received' => $pamoPendingReceivePath,
                'approved' => $pamoPendingReceivePath,
                'intake_drafts' => $pamoIntakePath,
            ];
            break;
        }
        $defaultStatCardNavigationPath = app_url($roleFolder . '/pacdo-action.php');
        break;
    case 'ADMIN_RECORDS_UNIT':
        $defaultStatCardNavigationPath = app_url($roleFolder . '/pacdo-action.php');
        break;
    case 'ORED':
        $defaultStatCardNavigationPath = app_url($roleFolder . '/rd-action-desk.php');
        break;
    case 'DIVISION_CHIEF':
        $defaultStatCardNavigationPath = app_url($roleFolder . '/for-chief-action.php');
        break;
    case 'ARD_TS':
        $defaultStatCardNavigationPath = app_url($roleFolder . '/ard-ts-action.php');
        break;
    case 'ARD_MS':
        $defaultStatCardNavigationPath = app_url($roleFolder . '/ard-ms-action.php');
        break;
    case 'SECTION_STAFF':
        $defaultStatCardNavigationPath = app_url($roleFolder . '/for-staff-action.php');
        break;
}
if ($defaultStatCardNavigationPath !== '') {
    $defaultStatCardNavigationTargets = [
        'default' => $defaultStatCardNavigationPath,
        'awaiting_receive' => $defaultStatCardNavigationPath,
        'received' => $defaultStatCardNavigationPath,
        'approved' => $defaultStatCardNavigationPath,
        'sign' => $defaultStatCardNavigationPath,
        'forward' => $defaultStatCardNavigationPath,
        'pending' => $defaultStatCardNavigationPath,
        'released' => $defaultStatCardNavigationPath,
        'pending_released' => $defaultStatCardNavigationPath,
        'completed' => $defaultStatCardNavigationPath,
        'signed_completed' => $defaultStatCardNavigationPath,
        'for_correction' => $defaultStatCardNavigationPath,
        'due_soon' => $defaultStatCardNavigationPath,
        'overdue' => $defaultStatCardNavigationPath,
        'new_created' => $defaultStatCardNavigationPath,
    ];
    if (!empty($roleSpecificStatCardNavigationTargets)) {
        $defaultStatCardNavigationTargets = array_merge(
            $defaultStatCardNavigationTargets,
            $roleSpecificStatCardNavigationTargets
        );
    }
    $statCardNavigationTargets = array_merge($defaultStatCardNavigationTargets, $statCardNavigationTargets);
}
$normalizedStatCardNavigationTargets = [];
foreach ($statCardNavigationTargets as $targetKey => $targetPath) {
    if (!is_string($targetPath)) {
        continue;
    }
    $normalizedPath = trim($targetPath);
    if ($normalizedPath === '') {
        continue;
    }
    $rawKey = strtolower(trim((string)$targetKey));
    if ($rawKey === '') {
        continue;
    }
    if ($rawKey !== 'default') {
        $rawKey = preg_replace('/[^a-z0-9_]+/', '_', $rawKey) ?? '';
        $rawKey = trim($rawKey, '_');
    }
    if ($rawKey === '') {
        continue;
    }
    $normalizedStatCardNavigationTargets[$rawKey] = $normalizedPath;
}
$statCardNavigationTargets = $normalizedStatCardNavigationTargets;
$qrStampWorkspaceDiskPath = dirname(__DIR__, 2) . '/roles/' . $roleFolder . '/pages/qr-stamp.php';
$showQrStampDocumentTool = is_file($qrStampWorkspaceDiskPath);
$qrStampWorkspacePath = $showQrStampDocumentTool ? app_url($roleFolder . '/qr-stamp.php') : '';
$hasActionStampWorkspace = in_array($roleKeyRaw, ['RECORDS_UNIT'], true)
    && is_file(dirname(__DIR__, 2) . '/roles/' . $roleFolder . '/pages/action-stamp.php');
$actionStampWorkspacePath = $hasActionStampWorkspace
    ? app_url($roleFolder . '/action-stamp.php')
    : '';
$digitalSignatureWorkspacePath = $regionalOredCanSign ? app_url($roleFolder . '/digital-signature.php') : '';
$isIntakePage = in_array($activeMenu, ['create_intake', 'create_external_intake'], true);
$useDefaultIntakeKpiCards = (bool)($useDefaultIntakeKpiCards ?? true);
$forceDocumentToolsOnTrackingTable = (bool)($forceDocumentToolsOnTrackingTable ?? in_array($activeMenu, ['search', 'global_search'], true));
$enableStickyQueueFiltersOnIntake = (bool)($enableStickyQueueFiltersOnIntake ?? false);
$queueControlsPlacement = (string)($queueControlsPlacement ?? 'sticky');
$queueControlsPlacement = in_array($queueControlsPlacement, ['sticky', 'table_card'], true)
    ? $queueControlsPlacement
    : 'sticky';
$showQrReceiveScanner = isset($showQrReceiveScanner)
    ? (bool)$showQrReceiveScanner
    : $roleKey === 'RECORDS_UNIT';
$canAddCustomType = false;
$renderStandardContent = (bool)($renderStandardContent ?? true);
$showQueueTable = (bool)($showQueueTable ?? true);
$hideCompletedQueueRows = (bool)($hideCompletedQueueRows ?? false);
$showCompletedQueueRowsOnly = (bool)($showCompletedQueueRowsOnly ?? false);
$showStrictCompletedOnly = (bool)($showStrictCompletedOnly ?? false);
$disableLiveDashboardRefresh = (bool)($disableLiveDashboardRefresh ?? false);
$enableBulkRowExport = (bool)($enableBulkRowExport ?? false);
$enableTableCardFilterControls = (bool)($enableTableCardFilterControls ?? false);
$dateFilterPlacement = (string)($dateFilterPlacement ?? 'header');
$dateFilterPlacement = in_array($dateFilterPlacement, ['header', 'table_card'], true)
    ? $dateFilterPlacement
    : 'header';
$hasQuickActionColumn = role_has_quick_action_column($tableColumns);
$showQueueFilterControls = (bool)($showQueueFilterControls ?? ($hasQuickActionColumn && (!$isIntakePage || $enableStickyQueueFiltersOnIntake) && $renderStandardContent));
$showTableCardFilterControls = (bool)($showTableCardFilterControls ?? (($showQueueFilterControls || $enableTableCardFilterControls)
    && $queueControlsPlacement === 'table_card'
    && $renderStandardContent));
$showStatusCategoryFilter = (bool)($showStatusCategoryFilter ?? true);
$showHeaderDateFilter = (bool)($showHeaderDateFilter ?? ($dateFilterPlacement !== 'table_card'));
$showTableCardDateFilter = (bool)($showTableCardDateFilter ?? ($dateFilterPlacement === 'table_card' && $renderStandardContent));
$defaultStatusFilterOptions = [
    ['value' => '', 'label' => 'All Status Categories'],
    ['value' => 'pending', 'label' => 'Pending'],
    ['value' => 'received', 'label' => 'Received'],
    ['value' => 'approved', 'label' => 'Approved'],
    ['value' => 'pending_forward', 'label' => 'Pending Forward'],
];
$statusFilterOptionsInput = is_array($statusFilterOptions ?? null) ? $statusFilterOptions : $defaultStatusFilterOptions;
$statusFilterOptions = [];
foreach ($statusFilterOptionsInput as $option) {
    if (!is_array($option)) {
        continue;
    }
    $optionValue = trim((string)($option['value'] ?? ''));
    $optionLabel = trim((string)($option['label'] ?? ''));
    if ($optionLabel === '') {
        continue;
    }
    if ($optionValue === 'completed' && strcasecmp($optionLabel, 'Released') === 0) {
        $optionValue = 'released';
    }
    if ($optionValue === 'approved' && strcasecmp($optionLabel, 'Approved') === 0) {
        $optionLabel = 'Approved / Signed';
    }
    $statusFilterOptions[] = [
        'value' => $optionValue,
        'label' => $optionLabel,
    ];
}
if (empty($statusFilterOptions)) {
    $statusFilterOptions = $defaultStatusFilterOptions;
}
$showStickyStatusFilter = (bool)($showStickyStatusFilter ?? ($showQueueFilterControls && !$showTableCardFilterControls));
$showStickySearch = (bool)($showStickySearch ?? $showStickyStatusFilter);
$showHeaderSearch = (bool)($showHeaderSearch ?? (!$showStickySearch && !$showTableCardFilterControls));
$hideHeaderSearch = (bool)($hideHeaderSearch ?? false);
if ($hideHeaderSearch) {
    $showHeaderSearch = false;
}
$pageActions = role_sanitize_page_actions($pageActions, $showQueueFilterControls);
$stickyActions = role_sanitize_sticky_actions($stickyActions, $showQueueFilterControls);
$showStickyActionsBar = $showStickyStatusFilter || !empty($stickyActions);
$customSectionInclude = (isset($customSectionInclude) && is_string($customSectionInclude)) ? $customSectionInclude : '';
$intakeAllowsExternal = in_array($roleKey, ['RECORDS_UNIT', 'SECTION_STAFF'], true) || $activeMenu === 'create_external_intake';
$showIntakeForwardToField = (bool)($showIntakeForwardToField ?? false);
$showIntakeActionRequiredField = (bool)($showIntakeActionRequiredField ?? false);
$activeDateFilterRange = function_exists('dashboard_effective_date_range')
    ? dashboard_effective_date_range()
    : [];

if ($isIntakePage) {
    // Standard cards will be populated after metrics fetch.
}
$hideQueueDocumentOnlyActions = (bool)($hideQueueDocumentOnlyActions ?? false);
$enableQueueRerouteAction = (bool)($enableQueueRerouteAction ?? false);
$rerouteOfficeLevelFilter = isset($rerouteOfficeLevelFilter) ? strtolower(trim((string)$rerouteOfficeLevelFilter)) : '';
$documentTypeOptions = [];
$liveQueueRows = [];
$liveMetrics = [];
$activityCounters = [];
$routeFallbackOffice = null;
$routeFallbackOffices = [];
$artaDistribution = [
    'simple' => 0,
    'complex' => 0,
    'highly_technical' => 0,
    'uncategorized' => 0,
    'total' => 0,
];
$workflowTrend = [
    'labels' => [],
    'received' => [],
    'forwarded' => [],
    'completed' => [],
];
$profileOfficeTag = '';
$profileParentOfficeTag = '';
$dashboardAssignmentCaption = '';
$intakeLockedDestinationOffice = null;
$intakeLockedDestinationId = 0;
$initialLiveScope = 'queue';
$initialLiveActions = [];
$initialLiveLimit = 50;

if (isset($dashboardLivePath) && is_string($dashboardLivePath) && trim($dashboardLivePath) !== '') {
    $queryString = (string)(parse_url($dashboardLivePath, PHP_URL_QUERY) ?? '');
    if ($queryString !== '') {
        $queryParts = [];
        parse_str($queryString, $queryParts);
        $parsedScope = strtolower(trim((string)($queryParts['scope'] ?? 'queue')));
        if ($parsedScope !== '') {
            $initialLiveScope = $parsedScope;
        }
        $parsedLimit = (int)($queryParts['limit'] ?? 50);
        $initialLiveLimit = max(5, min($parsedLimit, 100));
        $parsedActionsRaw = trim((string)($queryParts['actions'] ?? ''));
        if ($parsedActionsRaw !== '') {
            foreach (explode(',', $parsedActionsRaw) as $segment) {
                $actionName = trim((string)$segment);
                if ($actionName !== '') {
                    $initialLiveActions[] = $actionName;
                }
            }
        }
    }
}

$pdo = null;
try {
    $pdo = getDatabaseConnection();
} catch (Throwable $exception) {
    $pdo = null;
}

if ($pdo instanceof PDO && $officeId > 0) {
    try {
        $officeContextStmt = $pdo->prepare(
            'SELECT TOP (1)
                o.id,
                o.name,
                o.level,
                o.parent_office_id,
                parent.name AS parent_name,
                parent.level AS parent_level
             FROM offices o
             LEFT JOIN offices parent ON parent.id = o.parent_office_id
             WHERE o.id = :office_id'
        );
        $officeContextStmt->execute(['office_id' => $officeId]);
        $officeContext = $officeContextStmt->fetch() ?: [];
        if (!empty($officeContext)) {
            $assignmentTags = role_assignment_tags_from_office($officeContext);
            $profileOfficeTag = (string)($assignmentTags['primary'] ?? '');
            $profileParentOfficeTag = (string)($assignmentTags['secondary'] ?? '');
            $dashboardAssignmentCaption = (string)($assignmentTags['caption'] ?? '');
        }
    } catch (Throwable $exception) {
        $profileOfficeTag = '';
        $profileParentOfficeTag = '';
        $dashboardAssignmentCaption = '';
    }
}

if ($pdo instanceof PDO && $officeId > 0) {
    try {
        if (empty($routeOffices)) {
            $routeOffices = dashboard_fetch_route_offices($pdo, $officeId);
        }
            $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
        switch ($initialLiveScope) {
            case 'origin':
                $liveQueueRows = dashboard_fetch_origin_tracker_rows($pdo, $officeId, $initialLiveLimit);
                break;
            case 'archive':
                $liveQueueRows = dashboard_fetch_archive_rows($pdo, $officeId, $initialLiveLimit);
                break;
            case 'pending_receive':
                $liveQueueRows = dashboard_fetch_pending_receive_rows($pdo, $officeId, $initialLiveLimit, false, false, $sessionUserId, $roleKeyRaw);
                break;
            case 'pending_receive_action':
                $liveQueueRows = dashboard_fetch_pending_receive_rows($pdo, $officeId, $initialLiveLimit, true, false, $sessionUserId, $roleKeyRaw);
                break;
            case 'pending_receive_penro':
                $liveQueueRows = dashboard_fetch_pending_receive_rows($pdo, $officeId, $initialLiveLimit, true, false, $sessionUserId, $roleKeyRaw);
                break;
            case 'for_cenro_action':
                $liveQueueRows = dashboard_fetch_for_cenro_action_rows($pdo, $officeId, $initialLiveLimit);
                break;
            case 'office_action':
                $liveQueueRows = dashboard_fetch_office_action_rows($pdo, $officeId, $initialLiveLimit);
                break;
            case 'returned_regional':
                $liveQueueRows = dashboard_fetch_returned_from_regional_rows($pdo, $officeId, $initialLiveLimit);
                break;
            case 'outbox':
                $liveQueueRows = dashboard_fetch_outbox_rows($pdo, $officeId, $initialLiveLimit);
                break;
            case 'approved':
            case 'acted':
                $actionTypes = !empty($initialLiveActions) ? $initialLiveActions : ['Approved', 'Signed'];
                $liveQueueRows = $sessionUserId > 0
                    ? dashboard_fetch_actor_tracker_rows($pdo, $sessionUserId, $actionTypes, $initialLiveLimit)
                    : [];
                break;
            case 'division_staff':
                $actionTypes = !empty($initialLiveActions) ? $initialLiveActions : ['Approved', 'Forwarded', 'Returned', 'Rerouted'];
                $liveQueueRows = dashboard_fetch_division_staff_tracker_rows($pdo, $officeId, $actionTypes, $initialLiveLimit);
                break;
            case 'ard_division':
                $actionTypes = !empty($initialLiveActions) ? $initialLiveActions : ['Approved', 'Forwarded', 'Returned', 'Rerouted'];
                $liveQueueRows = dashboard_fetch_ard_division_tracker_rows($pdo, $officeId, $actionTypes, $initialLiveLimit);
                break;
            default:
                $liveQueueRows = dashboard_fetch_queue_rows($pdo, $officeId, $initialLiveLimit);
                break;
        }
        $liveMetrics = dashboard_fetch_role_metrics($pdo, $officeId);
        $activityCounters = dashboard_fetch_activity_counters($pdo, $officeId, 30);
        $artaDistribution = dashboard_fetch_arta_distribution($pdo, $officeId);
        $workflowTrend = dashboard_fetch_workflow_trend($pdo, $officeId, 7);
        $dbNotifications = dashboard_fetch_notifications($pdo, $officeId, 12, $roleName, $sessionUserId);
        $notifications = is_array($dbNotifications) ? $dbNotifications : [];

        if ($isIntakePage && $useDefaultIntakeKpiCards) {
            $kpiCards = [
                ['label' => 'Encoded Today', 'icon' => 'blue', 'value' => (string)($liveMetrics['created_today'] ?? '0'), 'pill' => 'Live'],
                ['label' => 'Due Soon', 'icon' => 'violet', 'value' => (string)($liveMetrics['due_soon_total'] ?? '0'), 'pill' => 'ARTA'],
                ['label' => 'Ongoing Docs', 'icon' => 'green', 'value' => (string)($liveMetrics['pending_total'] ?? '0')],
                ['label' => 'Returned', 'icon' => 'blue', 'value' => (string)($liveMetrics['returned_total'] ?? '0')],
            ];
        }
    } catch (Throwable $exception) {
        // Keep UI rendering with fallback/static values.
    }
}

if (!empty($liveQueueRows)) {
    if ($showCompletedQueueRowsOnly) {
        $liveQueueRows = array_values(array_filter(
            $liveQueueRows,
            static fn(array $row): bool => $showStrictCompletedOnly
                ? role_queue_row_is_strictly_completed($row)
                : role_queue_row_is_completed($row)
        ));
    } elseif ($hideCompletedQueueRows) {
        $liveQueueRows = array_values(array_filter(
            $liveQueueRows,
            static fn(array $row): bool => !role_queue_row_is_completed($row)
        ));
    }
}

if (!empty($routeOffices) && !in_array($roleKey, ['DIVISION_CHIEF', 'ORED'], true)) {
    $routeOffices = array_values(array_filter(
        $routeOffices,
        static function (array $office) use ($roleKey): bool {
            $level = strtoupper(trim((string)($office['level'] ?? '')));
            if (!in_array($level, ['SECTION', 'UNIT'], true)) {
                return true;
            }

            if (in_array($roleKey, ['ARD_TS', 'ARD_MS'], true)) {
                $officeKey = strtoupper(trim((string)($office['name'] ?? '')));
                $officeKey = preg_replace('/[^A-Z0-9]+/', '', $officeKey) ?? '';
                return $officeKey === 'RBCORIVERBASINCONTROLOFFICE'
                    || $officeKey === 'RIVERBASINCONTROLOFFICE';
            }

            return false;
        }
    ));
}

if ($isCenroAdminRecordContext) {
    $routeFallbackOffices = [];
    if ($pdo instanceof PDO) {
        $actorUserId = (int)($_SESSION['user_id'] ?? 0);
        $actorContext = $actorUserId > 0 ? workflow_get_user_context($pdo, $actorUserId) : null;
        $actorOfficeId = (int)($actorContext['office_id'] ?? $officeId);
        $actorParentPenro = $actorOfficeId > 0
            ? workflow_find_parent_office_by_level($pdo, $actorOfficeId, 'PROVINCIAL')
            : null;
        $actorParentPenroId = (int)($actorParentPenro['id'] ?? 0);
        $cenroRoot = $actorOfficeId > 0 ? workflow_find_cenro_root_office($pdo, $actorOfficeId) : null;
        $cenroRootId = (int)($cenroRoot['id'] ?? 0);

        $filtered = [];
        foreach ($routeOffices as $office) {
            $candidateId = (int)($office['id'] ?? 0);
            if ($candidateId <= 0) {
                continue;
            }
            $candidateContext = workflow_get_office_context($pdo, $candidateId);
            if (!$candidateContext) {
                continue;
            }
            $candidateLevel = strtoupper(trim((string)($candidateContext['level'] ?? '')));
            $candidateRoleId = workflow_infer_office_role_id($pdo, $candidateId);
            $candidateRoleName = '';
            if ($candidateRoleId !== null) {
                $candidateRoleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
                $candidateRoleStmt->execute(['id' => $candidateRoleId]);
                $candidateRoleName = app_normalize_role_key((string)($candidateRoleStmt->fetchColumn() ?: ''));
            }

            $candidateRoot = workflow_find_cenro_root_office($pdo, $candidateId);
            $candidateRootId = (int)($candidateRoot['id'] ?? 0);
            $isSameCenroOfficer = $candidateRoleName === 'CENRO_OFFICER'
                && $cenroRootId > 0
                && $candidateRootId > 0
                && $candidateRootId === $cenroRootId;
            $isPenro = $candidateRoleName === 'PENRO_ADMIN_RECORD'
                && $candidateLevel === 'PENRO_ADMIN_RECORD'
                && $actorParentPenroId > 0
                && (int)($candidateContext['parent_office_id'] ?? 0) === $actorParentPenroId;
            $isRecordsUnit = $candidateRoleName === 'RECORDS_UNIT'
                || (
                    $candidateLevel === 'REGIONAL'
                    && role_name_matches_records_unit((string)($candidateContext['name'] ?? ''))
                );

            if (!$isSameCenroOfficer && !$isPenro && !$isRecordsUnit) {
                continue;
            }

            $filtered[] = [
                'id' => (int)($office['id'] ?? 0),
                'name' => (string)($office['name'] ?? ''),
                'level' => (string)($office['level'] ?? ''),
                'parent_office_id' => (int)($candidateContext['parent_office_id'] ?? 0),
            ];
        }
        if (!empty($filtered)) {
            $appendUniqueOffice = static function (array &$list, ?array $officeToAdd): void {
                if (!is_array($officeToAdd) || (int)($officeToAdd['id'] ?? 0) <= 0) {
                    return;
                }
                $officeIdToAdd = (int)($officeToAdd['id'] ?? 0);
                foreach ($list as $existingOffice) {
                    if ((int)($existingOffice['id'] ?? 0) === $officeIdToAdd) {
                        return;
                    }
                }
                $list[] = [
                    'id' => $officeIdToAdd,
                    'name' => (string)($officeToAdd['name'] ?? ''),
                    'level' => (string)($officeToAdd['level'] ?? ''),
                    'parent_office_id' => (int)($officeToAdd['parent_office_id'] ?? 0),
                ];
            };

            $cenroOfficerOffice = $cenroRootId > 0
                ? workflow_find_child_office_by_level($pdo, $cenroRootId, 'CENRO_OFFICER')
                : null;
            $penroAdminOffice = $actorParentPenroId > 0
                ? workflow_find_child_office_by_level($pdo, $actorParentPenroId, 'PENRO_ADMIN_RECORD')
                : null;
            $recordsUnitOffice = workflow_find_records_unit_office($pdo);

            $appendUniqueOffice($filtered, $cenroOfficerOffice);
            $appendUniqueOffice($filtered, $penroAdminOffice);
            $appendUniqueOffice($filtered, $recordsUnitOffice);

            usort($filtered, static function (array $left, array $right): int {
                $priority = static function (array $office): int {
                    $level = strtoupper(trim((string)($office['level'] ?? '')));
                    if ($level === 'CENRO_OFFICER') {
                        return 1;
                    }
                    if ($level === 'PENRO_ADMIN_RECORD') {
                        return 2;
                    }
                    if ($level === 'REGIONAL') {
                        return 3;
                    }
                    return 4;
                };

                $priorityCompare = $priority($left) <=> $priority($right);
                if ($priorityCompare !== 0) {
                    return $priorityCompare;
                }

                return strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
            });

            $routeOffices = $filtered;
            $routeFallbackOffices = $filtered;
            $routeFallbackOffice = $filtered[0];
        }
    }
} elseif (in_array($roleKeyRaw, ['CENRO_OFFICER', 'CENRO_SECTION', 'CENRO_UNIT'], true)) {
    $routeFallbackOffices = [];
    if ($pdo instanceof PDO) {
        $actorUserId = (int)($_SESSION['user_id'] ?? 0);
        $actorContext = $actorUserId > 0 ? workflow_get_user_context($pdo, $actorUserId) : null;
        $actorOfficeId = (int)($actorContext['office_id'] ?? $officeId);
        $actorOffice = $actorOfficeId > 0 ? workflow_get_office_context($pdo, $actorOfficeId) : null;
        $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
        $actorParentPenro = $actorOfficeId > 0
            ? workflow_find_parent_office_by_level($pdo, $actorOfficeId, 'PROVINCIAL')
            : null;
        $actorParentPenroId = (int)($actorParentPenro['id'] ?? 0);
        $actorRoot = $actorOfficeId > 0 ? workflow_find_cenro_root_office($pdo, $actorOfficeId) : null;
        $actorRootId = (int)($actorRoot['id'] ?? 0);

        $filtered = [];
        foreach ($routeOffices as $office) {
            $candidateId = (int)($office['id'] ?? 0);
            if ($candidateId <= 0) {
                continue;
            }
            $candidateContext = workflow_get_office_context($pdo, $candidateId);
            if (!$candidateContext) {
                continue;
            }
            $candidateLevel = strtoupper(trim((string)($candidateContext['level'] ?? '')));

            if ($roleKeyRaw === 'CENRO_OFFICER') {
                $isChildSection = $candidateLevel === 'CENRO_SECTION'
                    && (int)($candidateContext['parent_office_id'] ?? 0) === $actorOfficeId;
                $isChildAdminRecord = $candidateLevel === 'CENRO_ADMIN_RECORD'
                    && (int)($candidateContext['parent_office_id'] ?? 0) === $actorOfficeId;
                $isSiblingAdminRecord = $candidateLevel === 'CENRO_ADMIN_RECORD'
                    && $actorParentOfficeId > 0
                    && (int)($candidateContext['parent_office_id'] ?? 0) === $actorParentOfficeId;
                $candidateRoot = $candidateId > 0 ? workflow_find_cenro_root_office($pdo, $candidateId) : null;
                $candidateRootId = (int)($candidateRoot['id'] ?? 0);
                $isBypassUnitSameRoot = $candidateLevel === 'CENRO_UNIT'
                    && workflow_cenro_internal_is_valid_unit_name((string)($candidateContext['name'] ?? ''))
                    && $actorRootId > 0
                    && $candidateRootId > 0
                    && $candidateRootId === $actorRootId;
                if (!$isChildSection && !$isChildAdminRecord && !$isSiblingAdminRecord && !$isBypassUnitSameRoot) {
                    continue;
                }
            } elseif ($roleKeyRaw === 'CENRO_SECTION') {
                $isChildUnit = is_array($actorOffice)
                    && workflow_cenro_internal_section_allows_unit_destination($actorOffice, $candidateContext);
                $isSiblingAdmin = $candidateLevel === 'CENRO_ADMIN_RECORD'
                    && $actorParentOfficeId > 0
                    && (int)($candidateContext['parent_office_id'] ?? 0) === $actorParentOfficeId;
                $isParentOfficer = $candidateLevel === 'CENRO_OFFICER'
                    && $actorParentOfficeId > 0
                    && $candidateId === $actorParentOfficeId;
                if (!$isChildUnit && !$isSiblingAdmin && !$isParentOfficer) {
                    continue;
                }
            } elseif ($roleKeyRaw === 'CENRO_UNIT') {
                if ($candidateLevel !== 'CENRO_SECTION') {
                    continue;
                }
                if ((int)($candidateId ?? 0) !== $actorParentOfficeId) {
                    continue;
                }
            }

            $filtered[] = [
                'id' => (int)($office['id'] ?? 0),
                'name' => (string)($office['name'] ?? ''),
                'level' => (string)($office['level'] ?? ''),
                'parent_office_id' => (int)($candidateContext['parent_office_id'] ?? 0),
            ];
        }

        if (!empty($filtered)) {
            usort($filtered, static function (array $left, array $right): int {
                $priority = static function (array $office): int {
                    $level = strtoupper(trim((string)($office['level'] ?? '')));
                    if ($level === 'CENRO_OFFICER') {
                        return 1;
                    }
                    return 2;
                };

                return $priority($left) <=> $priority($right);
            });
            $routeOffices = $filtered;
            $routeFallbackOffices = $filtered;
            $routeFallbackOffice = $filtered[0];
        }
    }
} elseif ($roleKeyRaw === 'PAMO_ADMIN') {
    $routeFallbackOffices = [];
    if ($pdo instanceof PDO) {
        $actorUserId = (int)($_SESSION['user_id'] ?? 0);
        $actorContext = $actorUserId > 0 ? workflow_get_user_context($pdo, $actorUserId) : null;
        $actorOfficeId = (int)($actorContext['office_id'] ?? $officeId);
        $pamoRoot = $actorOfficeId > 0 ? workflow_find_pamo_root_office($pdo, $actorOfficeId) : null;
        $pamoRootId = (int)($pamoRoot['id'] ?? 0);
        $pamoRootName = (string)($pamoRoot['name'] ?? '');
        $pamoScopeKey = workflow_pamo_scope_key($pamoRootName);

        $candidatePool = is_array($routeOffices) ? $routeOffices : [];
        $targetLevels = $roleKeyRaw === 'PASU_OFFICER'
            ? ['PAMO_UNIT', 'PAMO_ADMIN']
            : ['PASU_OFFICER'];
        if (!empty($targetLevels)) {
            $targetLevelSql = "'" . implode("','", array_map(static function (string $level): string {
                return str_replace("'", "''", strtoupper($level));
            }, $targetLevels)) . "'";
            $targetOfficeStmt = $pdo->query(
                'SELECT id, name, level, parent_office_id
                 FROM offices
                 WHERE UPPER(COALESCE(level, \'\')) IN (' . $targetLevelSql . ')
                 ORDER BY name ASC'
            );
            $targetOfficeRows = $targetOfficeStmt ? ($targetOfficeStmt->fetchAll() ?: []) : [];
            foreach ($targetOfficeRows as $officeRow) {
                $candidatePool[] = [
                    'id' => (int)($officeRow['id'] ?? 0),
                    'name' => (string)($officeRow['name'] ?? ''),
                    'level' => (string)($officeRow['level'] ?? ''),
                    'parent_office_id' => (int)($officeRow['parent_office_id'] ?? 0),
                ];
            }
        }

        $filtered = [];
        $seenCandidateIds = [];
        foreach ($candidatePool as $office) {
            $candidateId = (int)($office['id'] ?? 0);
            if ($candidateId <= 0) {
                continue;
            }
            if (isset($seenCandidateIds[$candidateId])) {
                continue;
            }
            $seenCandidateIds[$candidateId] = true;
            $candidateContext = workflow_get_office_context($pdo, $candidateId);
            if (!$candidateContext) {
                continue;
            }
            $candidateLevel = strtoupper(trim((string)($candidateContext['level'] ?? '')));
            $candidateRoleId = workflow_infer_office_role_id($pdo, $candidateId);
            $candidateRoleName = '';
            if ($candidateRoleId !== null) {
                $candidateRoleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
                $candidateRoleStmt->execute(['id' => $candidateRoleId]);
                $candidateRoleName = app_normalize_role_key((string)($candidateRoleStmt->fetchColumn() ?: ''));
            }

            $candidateRoot = $candidateId > 0 ? workflow_find_pamo_root_office($pdo, $candidateId) : null;
            $candidateRootId = (int)($candidateRoot['id'] ?? 0);
            $isSamePamoOfficer = $candidateLevel === 'PASU_OFFICER'
                && $pamoRootId > 0
                && $candidateRootId > 0
                && $candidateRootId === $pamoRootId;
            $isCenro = $candidateRoleName === 'CENRO_ADMIN_RECORD'
                && $candidateLevel === 'CENRO_ADMIN_RECORD'
                && $pamoScopeKey !== ''
                && workflow_pamo_scope_allows_cenro_name($pamoRootName, (string)($candidateContext['name'] ?? ''));
            $isPenro = $candidateRoleName === 'PENRO_ADMIN_RECORD'
                && $candidateLevel === 'PENRO_ADMIN_RECORD'
                && $pamoScopeKey !== ''
                && workflow_pamo_scope_allows_penro_name($pamoRootName, (string)($candidateContext['name'] ?? ''));

            if (!$isSamePamoOfficer && !$isCenro && !$isPenro) {
                continue;
            }

            $filtered[] = [
                'id' => (int)($office['id'] ?? 0),
                'name' => (string)($office['name'] ?? ''),
                'level' => (string)($office['level'] ?? ''),
                'parent_office_id' => (int)($candidateContext['parent_office_id'] ?? 0),
            ];
        }

        if ($pamoRootId > 0) {
            $penroRootsStmt = $pdo->query(
                "SELECT id
                 FROM offices
                 WHERE UPPER(COALESCE(level, '')) = 'PROVINCIAL'
                 ORDER BY id ASC"
            );
            foreach (($penroRootsStmt ? ($penroRootsStmt->fetchAll() ?: []) : []) as $penroRootOffice) {
                $penroRootOfficeId = (int)($penroRootOffice['id'] ?? 0);
                if ($penroRootOfficeId <= 0) {
                    continue;
                }
                $penroAdminOffice = workflow_find_child_office_by_level($pdo, $penroRootOfficeId, 'PENRO_ADMIN_RECORD');
                if (!is_array($penroAdminOffice)) {
                    continue;
                }
                if (
                    $pamoScopeKey === ''
                    || !workflow_pamo_scope_allows_penro_name($pamoRootName, (string)($penroAdminOffice['name'] ?? ''))
                ) {
                    continue;
                }
                $alreadyIncluded = false;
                foreach ($filtered as $existingOffice) {
                    if ((int)($existingOffice['id'] ?? 0) === (int)($penroAdminOffice['id'] ?? 0)) {
                        $alreadyIncluded = true;
                        break;
                    }
                }
                if (!$alreadyIncluded) {
                    $filtered[] = [
                        'id' => (int)($penroAdminOffice['id'] ?? 0),
                        'name' => (string)($penroAdminOffice['name'] ?? ''),
                        'level' => (string)($penroAdminOffice['level'] ?? ''),
                        'parent_office_id' => (int)($penroAdminOffice['parent_office_id'] ?? 0),
                    ];
                }
            }

            $cenroAdminsStmt = $pdo->query(
                "SELECT id, name, level, parent_office_id
                 FROM offices
                 WHERE UPPER(COALESCE(level, '')) = 'CENRO_ADMIN_RECORD'
                 ORDER BY name ASC, id ASC"
            );
            foreach (($cenroAdminsStmt ? ($cenroAdminsStmt->fetchAll() ?: []) : []) as $cenroAdminOffice) {
                if (
                    $pamoScopeKey === ''
                    || !workflow_pamo_scope_allows_cenro_name($pamoRootName, (string)($cenroAdminOffice['name'] ?? ''))
                ) {
                    continue;
                }
                $alreadyIncluded = false;
                foreach ($filtered as $existingOffice) {
                    if ((int)($existingOffice['id'] ?? 0) === (int)($cenroAdminOffice['id'] ?? 0)) {
                        $alreadyIncluded = true;
                        break;
                    }
                }
                if (!$alreadyIncluded) {
                    $filtered[] = [
                        'id' => (int)($cenroAdminOffice['id'] ?? 0),
                        'name' => (string)($cenroAdminOffice['name'] ?? ''),
                        'level' => (string)($cenroAdminOffice['level'] ?? ''),
                        'parent_office_id' => (int)($cenroAdminOffice['parent_office_id'] ?? 0),
                    ];
                }
            }
        }

        if (!empty($filtered)) {
            $routeOffices = $filtered;
            $routeFallbackOffices = $filtered;
            $routeFallbackOffice = $filtered[0];
        }
    }
} elseif ($roleKeyRaw === 'PENRO_ADMIN_RECORD') {
    $routeFallbackOffices = [];
    if ($pdo instanceof PDO) {
        $actorUserId = (int)($_SESSION['user_id'] ?? 0);
        $actorContext = $actorUserId > 0 ? workflow_get_user_context($pdo, $actorUserId) : null;
        $actorOfficeId = (int)($actorContext['office_id'] ?? $officeId);
        $penroRoot = $actorOfficeId > 0 ? workflow_find_penro_root_office($pdo, $actorOfficeId) : null;
        $penroRootId = (int)($penroRoot['id'] ?? 0);
        $appendUniqueOffice = static function (array &$list, ?array $officeToAdd): void {
            if (!is_array($officeToAdd) || (int)($officeToAdd['id'] ?? 0) <= 0) {
                return;
            }
            $officeIdToAdd = (int)($officeToAdd['id'] ?? 0);
            foreach ($list as $existingOffice) {
                if ((int)($existingOffice['id'] ?? 0) === $officeIdToAdd) {
                    return;
                }
            }
            $list[] = [
                'id' => $officeIdToAdd,
                'name' => (string)($officeToAdd['name'] ?? ''),
                'level' => (string)($officeToAdd['level'] ?? ''),
                'parent_office_id' => (int)($officeToAdd['parent_office_id'] ?? 0),
            ];
        };

        $candidatePool = is_array($routeOffices) ? $routeOffices : [];
        $targetLevels = $roleKeyRaw === 'PASU_OFFICER'
            ? ['PAMO_UNIT', 'PAMO_ADMIN']
            : ['PASU_OFFICER'];
        $targetLevelSql = "'" . implode("','", array_map(static function (string $level): string {
            return str_replace("'", "''", strtoupper($level));
        }, $targetLevels)) . "'";
        $targetOfficeStmt = $pdo->query(
            'SELECT id, name, level, parent_office_id
             FROM offices
             WHERE UPPER(COALESCE(level, \'\')) IN (' . $targetLevelSql . ')
             ORDER BY name ASC, id ASC'
        );
        foreach (($targetOfficeStmt ? ($targetOfficeStmt->fetchAll() ?: []) : []) as $officeRow) {
            $candidatePool[] = [
                'id' => (int)($officeRow['id'] ?? 0),
                'name' => (string)($officeRow['name'] ?? ''),
                'level' => (string)($officeRow['level'] ?? ''),
                'parent_office_id' => (int)($officeRow['parent_office_id'] ?? 0),
            ];
        }

        $filtered = [];
        $seenCandidateIds = [];
        foreach ($candidatePool as $office) {
            $candidateId = (int)($office['id'] ?? 0);
            if ($candidateId <= 0) {
                continue;
            }
            if (isset($seenCandidateIds[$candidateId])) {
                continue;
            }
            $seenCandidateIds[$candidateId] = true;
            $candidateContext = workflow_get_office_context($pdo, $candidateId);
            if (!$candidateContext) {
                continue;
            }
            $candidateLevel = strtoupper(trim((string)($candidateContext['level'] ?? '')));
            $candidateRoleId = workflow_infer_office_role_id($pdo, $candidateId);
            $candidateRoleName = '';
            if ($candidateRoleId !== null) {
                $candidateRoleStmt = $pdo->prepare('SELECT TOP (1) name FROM roles WHERE id = :id');
                $candidateRoleStmt->execute(['id' => $candidateRoleId]);
                $candidateRoleName = app_normalize_role_key((string)($candidateRoleStmt->fetchColumn() ?: ''));
            }

            $candidateRoot = $candidateId > 0 ? workflow_find_penro_root_office($pdo, $candidateId) : null;
            $candidateRootId = (int)($candidateRoot['id'] ?? 0);
            $isSamePenroOfficer = $candidateLevel === 'PENRO_OFFICER'
                && $penroRootId > 0
                && $candidateRootId > 0
                && $candidateRootId === $penroRootId;
            $isCenro = ($candidateRoleName === 'CENRO_ADMIN_RECORD' || $candidateLevel === 'CENRO_ADMIN_RECORD')
                && $penroRootId > 0
                && workflow_cenro_belongs_to_penro($pdo, $penroRootId, $candidateId);
            $isRecordsUnit = $candidateRoleName === 'RECORDS_UNIT'
                || role_name_matches_records_unit((string)($candidateContext['name'] ?? ''));

            if (!$isSamePenroOfficer && !$isCenro && !$isRecordsUnit) {
                continue;
            }

            $filtered[] = [
                'id' => (int)($office['id'] ?? 0),
                'name' => (string)($office['name'] ?? ''),
                'level' => (string)($office['level'] ?? ''),
                'parent_office_id' => (int)($candidateContext['parent_office_id'] ?? 0),
            ];
        }

        if ($penroRootId > 0) {
            $officerStmt = $pdo->prepare(
                "SELECT id, name, level, parent_office_id
                 FROM offices
                 WHERE parent_office_id = :parent_office_id
                   AND UPPER(COALESCE(level, '')) = 'PENRO_OFFICER'
                 ORDER BY name ASC, id ASC"
            );
            $officerStmt->execute(['parent_office_id' => $penroRootId]);
            foreach (($officerStmt->fetchAll() ?: []) as $officerOffice) {
                $appendUniqueOffice($filtered, $officerOffice);
            }
        }

        $cenroStmt = $pdo->query(
            "SELECT id, name, level, parent_office_id
             FROM offices
             WHERE UPPER(COALESCE(level, '')) = 'CENRO_ADMIN_RECORD'
             ORDER BY name ASC, id ASC"
        );
        foreach (($cenroStmt ? ($cenroStmt->fetchAll() ?: []) : []) as $cenroOffice) {
            $cenroOfficeId = (int)($cenroOffice['id'] ?? 0);
            if ($penroRootId <= 0 || $cenroOfficeId <= 0 || !workflow_cenro_belongs_to_penro($pdo, $penroRootId, $cenroOfficeId)) {
                continue;
            }
            $appendUniqueOffice($filtered, $cenroOffice);
        }

        $appendUniqueOffice($filtered, role_find_records_unit_route_office($pdo));

        if (!empty($filtered)) {
            $routeOffices = $filtered;
            $routeFallbackOffices = $filtered;
            $routeFallbackOffice = $filtered[0];
        }
    }
} elseif (in_array($roleKeyRaw, ['PASU_OFFICER', 'PAMO_UNIT'], true)) {
    $routeFallbackOffices = [];
    if ($pdo instanceof PDO) {
        $actorUserId = (int)($_SESSION['user_id'] ?? 0);
        $actorContext = $actorUserId > 0 ? workflow_get_user_context($pdo, $actorUserId) : null;
        $actorOfficeId = (int)($actorContext['office_id'] ?? $officeId);
        $actorOffice = $actorOfficeId > 0 ? workflow_get_office_context($pdo, $actorOfficeId) : null;
        $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
        $actorRoot = $actorOfficeId > 0 ? workflow_find_pamo_root_office($pdo, $actorOfficeId) : null;
        $actorRootId = (int)($actorRoot['id'] ?? 0);

        $candidatePool = is_array($routeOffices) ? $routeOffices : [];
        $targetLevels = $roleKeyRaw === 'PASU_OFFICER'
            ? ['PAMO_UNIT', 'PAMO_ADMIN']
            : ['PASU_OFFICER'];
        $targetLevelSql = "'" . implode("','", array_map(static function (string $level): string {
            return str_replace("'", "''", strtoupper($level));
        }, $targetLevels)) . "'";
        $targetOfficeStmt = $pdo->query(
            'SELECT id, name, level, parent_office_id
             FROM offices
             WHERE UPPER(COALESCE(level, \'\')) IN (' . $targetLevelSql . ')
             ORDER BY name ASC, id ASC'
        );
        foreach (($targetOfficeStmt ? ($targetOfficeStmt->fetchAll() ?: []) : []) as $officeRow) {
            $candidatePool[] = [
                'id' => (int)($officeRow['id'] ?? 0),
                'name' => (string)($officeRow['name'] ?? ''),
                'level' => (string)($officeRow['level'] ?? ''),
                'parent_office_id' => (int)($officeRow['parent_office_id'] ?? 0),
            ];
        }

        $filtered = [];
        $seenCandidateIds = [];
        foreach ($candidatePool as $office) {
            $candidateId = (int)($office['id'] ?? 0);
            if ($candidateId <= 0) {
                continue;
            }
            if (isset($seenCandidateIds[$candidateId])) {
                continue;
            }
            $seenCandidateIds[$candidateId] = true;
            $candidateContext = workflow_get_office_context($pdo, $candidateId);
            if (!$candidateContext) {
                continue;
            }
            $candidateLevel = strtoupper(trim((string)($candidateContext['level'] ?? '')));
            $candidateRoot = $candidateId > 0 ? workflow_find_pamo_root_office($pdo, $candidateId) : null;
            $candidateRootId = (int)($candidateRoot['id'] ?? 0);

            if ($roleKeyRaw === 'PASU_OFFICER') {
                $isChildUnit = $candidateLevel === 'PAMO_UNIT'
                    && (int)($candidateContext['parent_office_id'] ?? 0) === $actorOfficeId;
                $isSiblingAdmin = $candidateLevel === 'PAMO_ADMIN'
                    && $actorRootId > 0
                    && $candidateRootId > 0
                    && $candidateRootId === $actorRootId;
                if (!$isChildUnit && !$isSiblingAdmin) {
                    continue;
                }
            } else {
                if ($candidateLevel !== 'PASU_OFFICER') {
                    continue;
                }
                if ($actorParentOfficeId <= 0 || $candidateId !== $actorParentOfficeId) {
                    continue;
                }
            }

            $filtered[] = [
                'id' => (int)($office['id'] ?? 0),
                'name' => (string)($office['name'] ?? ''),
                'level' => (string)($office['level'] ?? ''),
                'parent_office_id' => (int)($candidateContext['parent_office_id'] ?? 0),
            ];
        }

        if (!empty($filtered)) {
            $routeOffices = $filtered;
            $routeFallbackOffices = $filtered;
            $routeFallbackOffice = $filtered[0];
        }
    }
} elseif (in_array($roleKeyRaw, ['PENRO_OFFICER', 'PENRO_DIVISION', 'PENRO_SECTION', 'PENRO_SECTION_UNIT'], true)) {
    $routeFallbackOffices = [];
    if ($pdo instanceof PDO) {
        $actorUserId = (int)($_SESSION['user_id'] ?? 0);
        $actorContext = $actorUserId > 0 ? workflow_get_user_context($pdo, $actorUserId) : null;
        $actorOfficeId = (int)($actorContext['office_id'] ?? $officeId);
        $actorOffice = $actorOfficeId > 0 ? workflow_get_office_context($pdo, $actorOfficeId) : null;
        $actorParentOfficeId = (int)($actorOffice['parent_office_id'] ?? 0);
        $actorRoot = $actorOfficeId > 0 ? workflow_find_penro_root_office($pdo, $actorOfficeId) : null;
        $actorRootId = (int)($actorRoot['id'] ?? 0);

        $filtered = [];
        foreach ($routeOffices as $office) {
            $candidateId = (int)($office['id'] ?? 0);
            if ($candidateId <= 0) {
                continue;
            }
            $candidateContext = workflow_get_office_context($pdo, $candidateId);
            if (!$candidateContext) {
                continue;
            }
            $candidateLevel = strtoupper(trim((string)($candidateContext['level'] ?? '')));

            if ($roleKeyRaw === 'PENRO_OFFICER') {
                $candidateRoot = $candidateId > 0 ? workflow_find_penro_root_office($pdo, $candidateId) : null;
                $candidateRootId = (int)($candidateRoot['id'] ?? 0);
                $isChildDivision = $candidateLevel === 'PENRO_DIVISION'
                    && (int)($candidateContext['parent_office_id'] ?? 0) === $actorOfficeId;
                $isSiblingAdminRecord = $candidateLevel === 'PENRO_ADMIN_RECORD'
                    && $actorRootId > 0
                    && $candidateRootId > 0
                    && $candidateRootId === $actorRootId;
                $isBypassSectionSameRoot = $candidateLevel === 'PENRO_SECTION'
                    && $actorRootId > 0
                    && $candidateRootId > 0
                    && $candidateRootId === $actorRootId;
                if (!$isChildDivision && !$isSiblingAdminRecord && !$isBypassSectionSameRoot) {
                    continue;
                }
            } elseif ($roleKeyRaw === 'PENRO_DIVISION') {
                $candidateRoot = $candidateId > 0 ? workflow_find_penro_root_office($pdo, $candidateId) : null;
                $candidateRootId = (int)($candidateRoot['id'] ?? 0);
                $isChildSection = is_array($actorOffice)
                    && workflow_penro_internal_division_allows_destination($actorOffice, $candidateContext);
                $isSameRootAdminRecord = $candidateLevel === 'PENRO_ADMIN_RECORD'
                    && $actorRootId > 0
                    && $candidateRootId > 0
                    && $candidateRootId === $actorRootId;
                $isParentOfficer = $candidateLevel === 'PENRO_OFFICER'
                    && $actorParentOfficeId > 0
                    && $candidateId === $actorParentOfficeId;
                if (!$isChildSection && !$isSameRootAdminRecord && !$isParentOfficer) {
                    continue;
                }
            } elseif ($roleKeyRaw === 'PENRO_SECTION') {
                $isAdminFinanceSection = workflow_penro_internal_is_admin_finance_section_office($actorOffice);
                $isAdminFinanceUnit = workflow_penro_internal_is_admin_finance_unit_office($actorOffice);
                $isChildUnit = workflow_penro_internal_section_allows_destination($pdo, $actorOffice ?? [], $candidateContext);
                $parentDestination = is_array($actorOffice)
                    ? workflow_penro_internal_resolve_section_parent_destination($pdo, $actorOffice)
                    : null;
                $parentDestinationId = (int)($parentDestination['id'] ?? 0);
                $isParentDestination = $parentDestinationId > 0 && $candidateId === $parentDestinationId;

                if ($isAdminFinanceSection) {
                    if (!$isChildUnit && !$isParentDestination) {
                        continue;
                    }
                } elseif ($isAdminFinanceUnit) {
                    if (!$isParentDestination) {
                        continue;
                    }
                } else {
                    if ($candidateLevel !== 'PENRO_DIVISION') {
                        continue;
                    }
                    if ($actorParentOfficeId <= 0 || $candidateId !== $actorParentOfficeId) {
                        continue;
                    }
                }
            } elseif ($roleKeyRaw === 'PENRO_SECTION_UNIT') {
                $actorMode = workflow_penro_internal_section_actor_mode($actorOffice);
                if ($actorMode === 'section') {
                    $isAdminFinanceSection = workflow_penro_internal_is_admin_finance_section_office($actorOffice);
                    $isAdminFinanceUnit = workflow_penro_internal_is_admin_finance_unit_office($actorOffice);
                    $isChildUnit = workflow_penro_internal_section_allows_destination($pdo, $actorOffice ?? [], $candidateContext);
                    $parentDestination = is_array($actorOffice)
                        ? workflow_penro_internal_resolve_section_parent_destination($pdo, $actorOffice)
                        : null;
                    $parentDestinationId = (int)($parentDestination['id'] ?? 0);
                    $isParentDestination = $parentDestinationId > 0 && $candidateId === $parentDestinationId;

                    if ($isAdminFinanceSection) {
                        if (!$isChildUnit && !$isParentDestination) {
                            continue;
                        }
                    } elseif ($isAdminFinanceUnit) {
                        if (!$isParentDestination) {
                            continue;
                        }
                    } else {
                        if ($candidateLevel !== 'PENRO_DIVISION') {
                            continue;
                        }
                        if ($actorParentOfficeId <= 0 || $candidateId !== $actorParentOfficeId) {
                            continue;
                        }
                    }
                } else {
                    if ($candidateLevel !== 'PENRO_SECTION') {
                        continue;
                    }
                    if ($actorParentOfficeId <= 0 || $candidateId !== $actorParentOfficeId) {
                        continue;
                    }
                }
            }

            $filtered[] = [
                'id' => (int)($office['id'] ?? 0),
                'name' => (string)($office['name'] ?? ''),
                'level' => (string)($office['level'] ?? ''),
                'parent_office_id' => (int)($candidateContext['parent_office_id'] ?? 0),
            ];
        }

        $appendUniqueOffice = static function (array &$list, ?array $officeToAdd): void {
            if (!is_array($officeToAdd) || (int)($officeToAdd['id'] ?? 0) <= 0) {
                return;
            }
            $officeIdToAdd = (int)($officeToAdd['id'] ?? 0);
            foreach ($list as $existingOffice) {
                if ((int)($existingOffice['id'] ?? 0) === $officeIdToAdd) {
                    return;
                }
            }
            $list[] = [
                'id' => $officeIdToAdd,
                'name' => (string)($officeToAdd['name'] ?? ''),
                'level' => (string)($officeToAdd['level'] ?? ''),
                'parent_office_id' => (int)($officeToAdd['parent_office_id'] ?? 0),
            ];
        };

        if ($roleKeyRaw === 'PENRO_OFFICER') {
            $divisionStmt = $pdo->prepare(
                "SELECT id, name, level, parent_office_id
                 FROM offices
                 WHERE parent_office_id = :parent_office_id
                   AND UPPER(COALESCE(level, '')) = 'PENRO_DIVISION'
                 ORDER BY name ASC, id ASC"
            );
            $divisionStmt->execute(['parent_office_id' => $actorOfficeId]);
            foreach (($divisionStmt->fetchAll() ?: []) as $divisionOffice) {
                $appendUniqueOffice($filtered, $divisionOffice);
            }

            if ($actorParentOfficeId > 0) {
                $adminRecordStmt = $pdo->prepare(
                    "SELECT id, name, level, parent_office_id
                     FROM offices
                     WHERE parent_office_id = :parent_office_id
                       AND UPPER(COALESCE(level, '')) = 'PENRO_ADMIN_RECORD'
                     ORDER BY name ASC, id ASC"
                );
                $adminRecordStmt->execute(['parent_office_id' => $actorParentOfficeId]);
                foreach (($adminRecordStmt->fetchAll() ?: []) as $adminRecordOffice) {
                    $appendUniqueOffice($filtered, $adminRecordOffice);
                }
            }
        } elseif ($roleKeyRaw === 'PENRO_DIVISION') {
            $sectionStmt = $pdo->prepare(
                "SELECT id, name, level, parent_office_id
                 FROM offices
                 WHERE parent_office_id = :parent_office_id
                   AND UPPER(COALESCE(level, '')) = 'PENRO_SECTION'
                 ORDER BY name ASC, id ASC"
            );
            $sectionStmt->execute(['parent_office_id' => $actorOfficeId]);
            foreach (($sectionStmt->fetchAll() ?: []) as $sectionOffice) {
                if (is_array($actorOffice) && workflow_penro_internal_division_allows_destination($actorOffice, $sectionOffice)) {
                    $appendUniqueOffice($filtered, $sectionOffice);
                }
            }

            if ($actorParentOfficeId > 0) {
                $appendUniqueOffice($filtered, workflow_get_office_context($pdo, $actorParentOfficeId));
            }
            if ($actorRootId > 0) {
                $adminRecordStmt = $pdo->prepare(
                    "SELECT id, name, level, parent_office_id
                     FROM offices
                     WHERE parent_office_id = :parent_office_id
                       AND UPPER(COALESCE(level, '')) = 'PENRO_ADMIN_RECORD'
                     ORDER BY name ASC, id ASC"
                );
                $adminRecordStmt->execute(['parent_office_id' => $actorRootId]);
                foreach (($adminRecordStmt->fetchAll() ?: []) as $adminRecordOffice) {
                    $appendUniqueOffice($filtered, $adminRecordOffice);
                }
            }
        } elseif ($roleKeyRaw === 'PENRO_SECTION') {
            if (is_array($actorOffice) && workflow_penro_internal_is_admin_finance_section_office($actorOffice)) {
                $sectionStmt = $pdo->prepare(
                    "SELECT id, name, level, parent_office_id
                     FROM offices
                     WHERE parent_office_id = :parent_office_id
                       AND UPPER(COALESCE(level, '')) = 'PENRO_SECTION'
                     ORDER BY name ASC, id ASC"
                );
                $sectionStmt->execute(['parent_office_id' => $actorParentOfficeId]);
                foreach (($sectionStmt->fetchAll() ?: []) as $sectionOffice) {
                    if (workflow_penro_internal_section_allows_destination($pdo, $actorOffice, $sectionOffice)) {
                        $appendUniqueOffice($filtered, $sectionOffice);
                    }
                }
            } elseif (is_array($actorOffice) && workflow_penro_internal_is_admin_finance_unit_office($actorOffice)) {
                $appendUniqueOffice($filtered, workflow_penro_internal_resolve_section_parent_destination($pdo, $actorOffice));
            } elseif ($actorParentOfficeId > 0) {
                $appendUniqueOffice($filtered, workflow_get_office_context($pdo, $actorParentOfficeId));
            }

            if (is_array($actorOffice) && workflow_penro_internal_is_admin_finance_section_office($actorOffice)) {
                $appendUniqueOffice($filtered, workflow_penro_internal_resolve_section_parent_destination($pdo, $actorOffice));
            }
        } elseif ($roleKeyRaw === 'PENRO_SECTION_UNIT') {
            $actorMode = workflow_penro_internal_section_actor_mode($actorOffice);
            if ($actorMode === 'section') {
                if (is_array($actorOffice) && workflow_penro_internal_is_admin_finance_section_office($actorOffice)) {
                    $sectionStmt = $pdo->prepare(
                        "SELECT id, name, level, parent_office_id
                         FROM offices
                         WHERE parent_office_id = :parent_office_id
                           AND UPPER(COALESCE(level, '')) = 'PENRO_SECTION'
                         ORDER BY name ASC, id ASC"
                    );
                    $sectionStmt->execute(['parent_office_id' => $actorParentOfficeId]);
                    foreach (($sectionStmt->fetchAll() ?: []) as $sectionOffice) {
                        if (workflow_penro_internal_section_allows_destination($pdo, $actorOffice, $sectionOffice)) {
                            $appendUniqueOffice($filtered, $sectionOffice);
                        }
                    }
                } elseif (is_array($actorOffice) && workflow_penro_internal_is_admin_finance_unit_office($actorOffice)) {
                    $appendUniqueOffice($filtered, workflow_penro_internal_resolve_section_parent_destination($pdo, $actorOffice));
                } elseif ($actorParentOfficeId > 0) {
                    $appendUniqueOffice($filtered, workflow_get_office_context($pdo, $actorParentOfficeId));
                }

                if (is_array($actorOffice) && workflow_penro_internal_is_admin_finance_section_office($actorOffice)) {
                    $appendUniqueOffice($filtered, workflow_penro_internal_resolve_section_parent_destination($pdo, $actorOffice));
                }
            } elseif ($actorParentOfficeId > 0) {
                $appendUniqueOffice($filtered, workflow_get_office_context($pdo, $actorParentOfficeId));
            }
        }

        if (!empty($filtered)) {
            $routeOffices = $filtered;
            $routeFallbackOffices = $filtered;
            $routeFallbackOffice = $filtered[0];
        }
    }
} elseif (in_array($roleKey, ['RECORDS_UNIT', 'ORED', 'DIVISION_CHIEF', 'SECTION_STAFF', 'ARD_TS', 'ARD_MS'], true)) {
    if ($roleKey === 'ORED') {
        if (in_array($roleKeyRaw, ['ORED', 'ORED_SIGN'], true)) {
            $sameOfficeExists = false;
            foreach ($routeOffices as $office) {
                if ((int)($office['id'] ?? 0) === $officeId) {
                    $sameOfficeExists = true;
                    break;
                }
            }
            if (!$sameOfficeExists && $officeId > 0 && !empty($officeContext)) {
                $routeOffices[] = [
                    'id' => $officeId,
                    'name' => (string)($officeContext['name'] ?? 'ORED'),
                    'level' => (string)($officeContext['level'] ?? ''),
                    'parent_office_id' => (int)($officeContext['parent_office_id'] ?? 0),
                    'route_label' => $roleKeyRaw === 'ORED_SIGN' ? 'ORED Review' : 'ORED Sign',
                ];
            }
        }
        foreach ($routeOffices as $office) {
            $level = strtoupper(trim((string)($office['level'] ?? '')));
            $name = strtoupper((string)($office['name'] ?? ''));
            if ($level === 'DIVISION' || str_contains($name, 'DIVISION')) {
                $routeFallbackOffices[] = [
                    'id' => (int)($office['id'] ?? 0),
                    'name' => (string)($office['name'] ?? ''),
                    'level' => (string)($office['level'] ?? ''),
                ];
            }
        }

        if (empty($routeFallbackOffices) && $pdo instanceof PDO) {
            $routeFallbackOffices = role_find_division_route_offices($pdo);
        }

        if ($pdo instanceof PDO) {
            $resolvedRecordsUnit = role_find_records_unit_route_office($pdo);
            if ($resolvedRecordsUnit !== null) {
                $exists = false;
                foreach ($routeFallbackOffices as $fallbackOffice) {
                    if ((int)($fallbackOffice['id'] ?? 0) === (int)($resolvedRecordsUnit['id'] ?? 0)) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $routeFallbackOffices[] = $resolvedRecordsUnit;
                }
            }
        }

        if (!empty($routeFallbackOffices)) {
            $routeFallbackOffice = $routeFallbackOffices[0];
            foreach ($routeFallbackOffices as $fallbackOffice) {
                $exists = false;
                foreach ($routeOffices as $office) {
                    if ((int)($office['id'] ?? 0) === (int)($fallbackOffice['id'] ?? 0)) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $routeOffices[] = $fallbackOffice;
                }
            }
        }
    } elseif ($roleKey === 'ARD_TS' || $roleKey === 'ARD_MS') {
        if ($pdo instanceof PDO) {
            $track = role_ard_track_from_role_key($roleKey);
            if ($track !== null) {
                $divisionCandidates = role_find_division_route_offices($pdo);
                foreach ($divisionCandidates as $divisionOffice) {
                    $divisionTrack = role_division_track_from_name((string)($divisionOffice['name'] ?? ''));
                    if ($divisionTrack !== $track) {
                        continue;
                    }
                    $routeFallbackOffices[] = [
                        'id' => (int)($divisionOffice['id'] ?? 0),
                        'name' => (string)($divisionOffice['name'] ?? ''),
                        'level' => (string)($divisionOffice['level'] ?? ''),
                    ];
                }

                $rbcoStmt = $pdo->prepare(
                    "SELECT TOP (1) id, name, level, parent_office_id
                     FROM offices
                     WHERE UPPER(name) IN ('RBCO (RIVER BASIN CONTROL OFFICE)', 'RIVER BASIN CONTROL OFFICE')
                     ORDER BY id ASC"
                );
                $rbcoStmt->execute();
                $rbcoOffice = $rbcoStmt->fetch();
                if ($rbcoOffice) {
                    $routeFallbackOffices[] = [
                        'id' => (int)($rbcoOffice['id'] ?? 0),
                        'name' => (string)($rbcoOffice['name'] ?? ''),
                        'level' => (string)($rbcoOffice['level'] ?? ''),
                        'parent_office_id' => (int)($rbcoOffice['parent_office_id'] ?? 0),
                    ];
                }
            }
            $resolvedOred = role_find_ored_route_office($pdo);
            if ($resolvedOred !== null) {
                $exists = false;
                foreach ($routeFallbackOffices as $fallbackOffice) {
                    if ((int)($fallbackOffice['id'] ?? 0) === (int)($resolvedOred['id'] ?? 0)) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $routeFallbackOffices[] = $resolvedOred;
                }
            }
        }

        if (!empty($routeFallbackOffices)) {
            $routeFallbackOffice = $routeFallbackOffices[0];
            foreach ($routeFallbackOffices as $fallbackOffice) {
                $exists = false;
                foreach ($routeOffices as $office) {
                    if ((int)($office['id'] ?? 0) === (int)($fallbackOffice['id'] ?? 0)) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $routeOffices[] = $fallbackOffice;
                }
            }
        }
    } elseif ($roleKey === 'DIVISION_CHIEF') {
        if ($pdo instanceof PDO) {
            // Default: strictly limit Division Chief section routing to sections under the current division office.
            $routeFallbackOffices = role_find_section_route_offices($pdo, $officeId, false);

            // Optional page-level fallback: when no section offices exist under the division,
            // allow section-staff targets mapped to this division (including division-level staff seat).
            if (empty($routeFallbackOffices) && (bool)($showDivisionChiefStaffRerouteTargets ?? false)) {
                $routeFallbackOffices = role_find_section_staff_route_offices($pdo, $officeId);
            }
        }

        if ($pdo instanceof PDO) {
            $resolvedArd = role_find_parent_ard_route_office($pdo, $officeId);
            if ($resolvedArd !== null) {
                $exists = false;
                foreach ($routeFallbackOffices as $fallbackOffice) {
                    if ((int)($fallbackOffice['id'] ?? 0) === (int)($resolvedArd['id'] ?? 0)) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $routeFallbackOffices[] = $resolvedArd;
                }
            } else {
                $resolvedOred = role_find_ored_route_office($pdo);
                if ($resolvedOred !== null) {
                    $exists = false;
                    foreach ($routeFallbackOffices as $fallbackOffice) {
                        if ((int)($fallbackOffice['id'] ?? 0) === (int)($resolvedOred['id'] ?? 0)) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $routeFallbackOffices[] = $resolvedOred;
                    }
                }
            }
        }

        if (!empty($routeFallbackOffices)) {
            $routeFallbackOffice = $routeFallbackOffices[0];
            foreach ($routeFallbackOffices as $fallbackOffice) {
                $exists = false;
                foreach ($routeOffices as $office) {
                    if ((int)($office['id'] ?? 0) === (int)($fallbackOffice['id'] ?? 0)) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $routeOffices[] = $fallbackOffice;
                }
            }
        }
    } elseif ($roleKey === 'SECTION_STAFF') {
        if ($pdo instanceof PDO) {
            $routeFallbackOffice = role_find_parent_division_route_office($pdo, $officeId);
            if ($routeFallbackOffice === null) {
                $routeFallbackOffice = role_find_division_route_office($pdo);
            }
        }

        if ($routeFallbackOffice === null) {
            foreach ($routeOffices as $office) {
                $level = strtoupper(trim((string)($office['level'] ?? '')));
                $name = strtoupper((string)($office['name'] ?? ''));
                if ($level === 'DIVISION' || str_contains($name, 'DIVISION')) {
                    $routeFallbackOffice = [
                        'id' => (int)($office['id'] ?? 0),
                        'name' => (string)($office['name'] ?? ''),
                        'level' => (string)($office['level'] ?? ''),
                    ];
                    break;
                }
            }
        }

        if ($routeFallbackOffice !== null) {
            $routeFallbackOffices = [$routeFallbackOffice];
            $exists = false;
            foreach ($routeOffices as $office) {
                if ((int)($office['id'] ?? 0) === (int)($routeFallbackOffice['id'] ?? 0)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $routeOffices[] = $routeFallbackOffice;
            }
        }
    } else {
        $routesToRecordsUnit = $isPenroAdminRecordContext;
        $targetLabel = $routesToRecordsUnit ? 'RECORDS-UNIT' : 'ORED';
        foreach ($routeOffices as $office) {
            $officeName = (string)($office['name'] ?? '');
            $targetMatched = $routesToRecordsUnit
                ? role_name_matches_records_unit($officeName)
                : stripos($officeName, $targetLabel) !== false;
            if ($targetMatched) {
                $routeFallbackOffice = [
                    'id' => (int)($office['id'] ?? 0),
                    'name' => $officeName,
                    'level' => (string)($office['level'] ?? ''),
                ];
                break;
            }
        }

        if ($routeFallbackOffice === null && $pdo instanceof PDO) {
            $resolvedOffice = $routesToRecordsUnit
                ? role_find_records_unit_route_office($pdo)
                : role_find_ored_route_office($pdo);
            if (is_array($resolvedOffice) && (int)($resolvedOffice['id'] ?? 0) > 0) {
                $routeFallbackOffice = $resolvedOffice;
            }
        }

        if ($routeFallbackOffice !== null) {
            $routeFallbackOffices = [$routeFallbackOffice];
            $exists = false;
            foreach ($routeOffices as $office) {
                if ((int)($office['id'] ?? 0) === (int)($routeFallbackOffice['id'] ?? 0)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $routeOffices[] = $routeFallbackOffice;
            }
        }
    }
}

// Automatic routing from intake is disabled globally.
$intakeLockedDestinationOffice = null;
$intakeLockedDestinationId = 0;

if ($pdo instanceof PDO && $isIntakePage) {
    try {
        $hasIsCustom = dashboard_column_exists($pdo, 'document_types', 'is_custom');
        $hasIsActive = dashboard_column_exists($pdo, 'document_types', 'is_active');

        $sql = 'SELECT id, name, category, arta_days_limit';
        if ($hasIsCustom) {
            $sql .= ', is_custom';
        } else {
            $sql .= ', 0 AS is_custom';
        }
        $sql .= ' FROM document_types';
        if ($hasIsActive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY name ASC';

        $docTypeStmt = $pdo->query($sql);
        $documentTypeOptions = $docTypeStmt ? ($docTypeStmt->fetchAll() ?: []) : [];
    } catch (Throwable $exception) {
        $documentTypeOptions = [];
    }
}

$hasMetaRows = false;
foreach ($tableRows as $checkRow) {
    if (is_array($checkRow) && isset($checkRow['meta']) && is_array($checkRow['meta'])) {
        $hasMetaRows = true;
        break;
    }
}
if (!empty($liveQueueRows) && !$hasMetaRows && !role_has_date_column($tableColumns)) {
    $tableColumns[] = 'Date Created';
}
if (!$hasMetaRows && !empty($liveQueueRows)) {
    $tableRows = role_table_rows_from_queue($liveQueueRows, $tableColumns);
}

$liveQueueMetaByTracking = [];
foreach ($liveQueueRows as $queueRow) {
    $trackingKey = strtoupper(trim((string)($queueRow['tracking_id'] ?? '')));
    if ($trackingKey === '') {
        continue;
    }
    $liveQueueMetaByTracking[$trackingKey] = $queueRow;
}

if ($hasMetaRows && !empty($liveQueueMetaByTracking)) {
    foreach ($tableRows as $rowIndex => $tableRow) {
        if (!is_array($tableRow) || !isset($tableRow['meta']) || !is_array($tableRow['meta'])) {
            continue;
        }
        $trackingIdMeta = strtoupper(trim((string)($tableRow['meta']['tracking_id'] ?? '')));
        if ($trackingIdMeta === '') {
            $valueCells = is_array($tableRow['value'] ?? null) ? $tableRow['value'] : [];
            $trackingIdMeta = strtoupper(trim((string)($valueCells[0] ?? '')));
        }
        if ($trackingIdMeta === '' || !isset($liveQueueMetaByTracking[$trackingIdMeta])) {
            continue;
        }

        $sourceRow = $liveQueueMetaByTracking[$trackingIdMeta];
        if (!isset($tableRows[$rowIndex]['meta']['status_raw'])) {
            $tableRows[$rowIndex]['meta']['status_raw'] = (string)($sourceRow['status'] ?? '');
        }
        if (!isset($tableRows[$rowIndex]['meta']['row_version'])) {
            $tableRows[$rowIndex]['meta']['row_version'] = (string)max(1, (int)($sourceRow['row_version'] ?? 1));
        }
        if (!isset($tableRows[$rowIndex]['meta']['date_received_raw'])) {
            $tableRows[$rowIndex]['meta']['date_received_raw'] = (string)($sourceRow['date_received_raw'] ?? '');
        }
        if (!isset($tableRows[$rowIndex]['meta']['date_created_raw'])) {
            $tableRows[$rowIndex]['meta']['date_created_raw'] = (string)($sourceRow['date_created_raw'] ?? '');
        }
        if (!isset($tableRows[$rowIndex]['meta']['deadline_at_raw'])) {
            $tableRows[$rowIndex]['meta']['deadline_at_raw'] = (string)($sourceRow['deadline_at_raw'] ?? '');
        }
        if (!isset($tableRows[$rowIndex]['meta']['deadline_bucket'])) {
            $tableRows[$rowIndex]['meta']['deadline_bucket'] = (string)($sourceRow['deadline_bucket'] ?? '');
        }
        if (!isset($tableRows[$rowIndex]['meta']['has_section_receive'])) {
            $tableRows[$rowIndex]['meta']['has_section_receive'] = (int)($sourceRow['has_section_receive'] ?? 0) > 0 ? '1' : '0';
        }
        if (!isset($tableRows[$rowIndex]['meta']['origin_office_id'])) {
            $tableRows[$rowIndex]['meta']['origin_office_id'] = (string)((int)($sourceRow['origin_office_id'] ?? 0));
        }
        if (!isset($tableRows[$rowIndex]['meta']['current_office_id'])) {
            $tableRows[$rowIndex]['meta']['current_office_id'] = (string)((int)($sourceRow['current_office_id'] ?? 0));
        }
        if (!isset($tableRows[$rowIndex]['meta']['pending_office_id'])) {
            $tableRows[$rowIndex]['meta']['pending_office_id'] = (string)((int)($sourceRow['pending_office_id'] ?? 0));
        }
        if (!isset($tableRows[$rowIndex]['meta']['current_office_level'])) {
            $tableRows[$rowIndex]['meta']['current_office_level'] = (string)($sourceRow['current_office_level'] ?? '');
        }
        if (!isset($tableRows[$rowIndex]['meta']['pending_office_level'])) {
            $tableRows[$rowIndex]['meta']['pending_office_level'] = (string)($sourceRow['pending_office_level'] ?? '');
        }
        if (!isset($tableRows[$rowIndex]['meta']['current_holder'])) {
            $tableRows[$rowIndex]['meta']['current_holder'] = (string)($sourceRow['current_holder'] ?? '');
        }
        if (!isset($tableRows[$rowIndex]['meta']['has_signed_action'])) {
            $tableRows[$rowIndex]['meta']['has_signed_action'] = (int)($sourceRow['has_signed_action'] ?? 0) > 0 ? '1' : '0';
        }
        if (!isset($tableRows[$rowIndex]['meta']['has_return_action'])) {
            $tableRows[$rowIndex]['meta']['has_return_action'] = (int)($sourceRow['has_return_action'] ?? 0) > 0 ? '1' : '0';
        }
        if (!isset($tableRows[$rowIndex]['meta']['created_by_user_id'])) {
            $tableRows[$rowIndex]['meta']['created_by_user_id'] = (string)((int)($sourceRow['created_by_user_id'] ?? 0));
        }
    }
}

if ($showQueueIndicator) {
    $indicatorColumnIndex = role_table_indicator_column_index($tableColumns);
    if ($indicatorColumnIndex >= 0) {
        $expectedColumnCount = count($tableColumns);
        foreach ($tableRows as $rowIndex => $tableRow) {
            $rowMeta = [];
            $cells = [];
            $hasWrappedValue = false;

            if (is_array($tableRow) && array_key_exists('value', $tableRow) && is_array($tableRow['value'])) {
                $hasWrappedValue = true;
                $cells = array_values($tableRow['value']);
                $rowMeta = is_array($tableRow['meta'] ?? null) ? $tableRow['meta'] : [];
            } elseif (is_array($tableRow)) {
                $cells = array_values($tableRow);
            } else {
                $cells = [(string)$tableRow];
            }

            if (count($cells) >= $expectedColumnCount) {
                continue;
            }

            $trackingId = strtoupper(trim((string)($rowMeta['tracking_id'] ?? ($cells[0] ?? ''))));
            $sourceRow = ($trackingId !== '' && isset($liveQueueMetaByTracking[$trackingId]) && is_array($liveQueueMetaByTracking[$trackingId]))
                ? $liveQueueMetaByTracking[$trackingId]
                : null;
            $indicatorLabel = is_array($sourceRow)
                ? role_queue_intake_indicator_label($sourceRow)
                : role_fallback_indicator_label($rowMeta);

            array_splice($cells, $indicatorColumnIndex, 0, [$indicatorLabel]);

            if ($hasWrappedValue) {
                $tableRows[$rowIndex]['value'] = $cells;
                if (!isset($tableRows[$rowIndex]['meta']['indicator_label'])) {
                    $tableRows[$rowIndex]['meta']['indicator_label'] = $indicatorLabel;
                }
            } else {
                $tableRows[$rowIndex] = $cells;
            }
        }
    }
}

$returnedTotal = 0;
foreach ($liveQueueRows as $queueRow) {
    $status = strtolower(trim((string)($queueRow['status'] ?? '')));
    if (str_contains($status, 'return')) {
        $returnedTotal++;
    }
}
$queueCounters = role_queue_status_counters($liveQueueRows, $officeId);
$preferScopedQueueCountersForDisplay = ($activeMenu ?? '') !== 'dashboard';
$queueCountersForDisplay = $preferScopedQueueCountersForDisplay ? $queueCounters : [];
$returnedTotalForDisplay = $preferScopedQueueCountersForDisplay
    ? $returnedTotal
    : (int)($liveMetrics['returned_total'] ?? $returnedTotal);

if (!empty($liveMetrics)) {
    foreach ($kpiCards as $index => $card) {
        $mappedValue = role_metric_value_for_label((string)($card['label'] ?? ''), $liveMetrics, $activityCounters, $returnedTotalForDisplay, $queueCountersForDisplay);
        if ($mappedValue !== null) {
            $kpiCards[$index]['value'] = (string)$mappedValue;
        }
    }

    foreach ($panels as $panelIndex => $panel) {
        if (empty($panel['rows']) || !is_array($panel['rows'])) {
            continue;
        }
        $rowValues = [];
        foreach ($panel['rows'] as $rowIndex => $row) {
            $mappedValue = role_metric_value_for_label((string)($row['label'] ?? ''), $liveMetrics, $activityCounters, $returnedTotalForDisplay, $queueCountersForDisplay);
            if ($mappedValue !== null) {
                $panels[$panelIndex]['rows'][$rowIndex]['value'] = (string)$mappedValue;
                $rowValues[] = $mappedValue;
            } else {
                $existingValue = (int)preg_replace('/[^0-9]/', '', (string)($row['value'] ?? '0'));
                $rowValues[] = max($existingValue, 0);
            }
        }
        $maxRowValue = max(array_merge([1], $rowValues));
        foreach ($panels[$panelIndex]['rows'] as $rowIndex => $row) {
            $valueInt = (int)preg_replace('/[^0-9]/', '', (string)($row['value'] ?? '0'));
            $panels[$panelIndex]['rows'][$rowIndex]['width'] = (string)(int)round(($valueInt / $maxRowValue) * 100) . '%';
        }
    }
}

$enableCharts = (bool)($enableCharts ?? ($activeMenu === 'dashboard'));
$chartPieSeries = [
    ['label' => 'Simple', 'value' => (int)($artaDistribution['simple'] ?? 0), 'color' => '#f5bf3a'],
    ['label' => 'Complex', 'value' => (int)($artaDistribution['complex'] ?? 0), 'color' => '#e4649c'],
    ['label' => 'Highly Technical', 'value' => (int)($artaDistribution['highly_technical'] ?? 0), 'color' => '#6f63e8'],
    ['label' => 'Uncategorized', 'value' => (int)($artaDistribution['uncategorized'] ?? 0), 'color' => '#8fa5bc'],
];
$chartPieTotal = 0;
foreach ($chartPieSeries as $seriesItem) {
    $chartPieTotal += max((int)($seriesItem['value'] ?? 0), 0);
}

$dueSoonCombined = (int)($liveMetrics['due_today_total'] ?? 0) + (int)($liveMetrics['due_soon_total'] ?? 0);
$chartBarSeries = [
    ['label' => 'Pending', 'value' => max((int)($liveMetrics['pending_total'] ?? 0), 0), 'color' => '#2f7de1'],
    ['label' => 'Due Soon', 'value' => max($dueSoonCombined, 0), 'color' => '#23a68f'],
    ['label' => 'Overdue', 'value' => max((int)($liveMetrics['overdue_total'] ?? 0), 0), 'color' => '#d34747'],
    ['label' => 'Completed', 'value' => max((int)($liveMetrics['completed_week'] ?? 0), 0), 'color' => '#1a9b5f'],
];
$chartBarMax = 1;
foreach ($chartBarSeries as $seriesItem) {
    $chartBarMax = max($chartBarMax, (int)($seriesItem['value'] ?? 0));
}
foreach ($chartBarSeries as $seriesIndex => $seriesItem) {
    $chartBarSeries[$seriesIndex]['height'] = (string)(int)round((((int)$seriesItem['value']) / $chartBarMax) * 100) . '%';
}

$chartTrendLabels = is_array($workflowTrend['labels'] ?? null) ? $workflowTrend['labels'] : [];
$chartTrendSeries = [
    [
        'key' => 'received',
        'label' => 'Received',
        'color' => '#2f7de1',
        'values' => is_array($workflowTrend['received'] ?? null) ? $workflowTrend['received'] : [],
    ],
    [
        'key' => 'forwarded',
        'label' => 'Forwarded / Routed',
        'color' => '#1ea48a',
        'values' => is_array($workflowTrend['forwarded'] ?? null) ? $workflowTrend['forwarded'] : [],
    ],
    [
        'key' => 'completed',
        'label' => 'Approved / Signed',
        'color' => '#7b5ad9',
        'values' => is_array($workflowTrend['completed'] ?? null) ? $workflowTrend['completed'] : [],
    ],
];
$chartHasTrendPoints = false;
foreach ($chartTrendSeries as $seriesItem) {
    foreach ($seriesItem['values'] as $value) {
        if ((int)$value > 0) {
            $chartHasTrendPoints = true;
            break 2;
        }
    }
}
$chartTrendPayload = [
    'labels' => $chartTrendLabels,
    'series' => $chartTrendSeries,
];
