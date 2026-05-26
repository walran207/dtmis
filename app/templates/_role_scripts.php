    <script>
        (function () {
            const SIDEBAR_COLLAPSE_KEY = 'DTMIS_sidebar_collapsed';
            const SIDEBAR_WIDTH_KEY = 'DTMIS_sidebar_width';
            const SIDEBAR_DEFAULT_WIDTH = 242;
            const SIDEBAR_MIN_WIDTH = 200;
            const SIDEBAR_MAX_WIDTH = 520;
            const logoutPath = <?= json_encode(app_url('auth/logout.php')) ?>;
            const profileSettingsPath = 'profile-settings.php';
            const trackingSlipPath = <?= json_encode(app_url('tracking-slip.php')) ?>;
            const printPackagePath = <?= json_encode(app_url('print-package.php')) ?>;
            const qrStampWorkspacePath = <?= json_encode($qrStampWorkspacePath) ?>;
            const actionStampWorkspacePath = <?= json_encode($actionStampWorkspacePath) ?>;
            const digitalSignatureWorkspacePath = <?= json_encode($digitalSignatureWorkspacePath) ?>;
            const documentActionPath = <?= json_encode(app_url('actions/document-action.php')) ?>;
            const documentDetailsPath = <?= json_encode(app_url('actions/document-details.php')) ?>;
            const createDocumentPath = <?= json_encode(app_url('actions/create-document.php')) ?>;
            const notificationFeedPath = <?= json_encode($notificationFeedUrl) ?>;
            const dashboardLivePath = <?= json_encode((isset($dashboardLivePath) && is_string($dashboardLivePath) && trim($dashboardLivePath) !== '') ? $dashboardLivePath : app_url('actions/dashboard-live.php')) ?>;
            const sessionHeartbeatPath = <?= json_encode(app_url('actions/session-heartbeat.php')) ?>;
            const serverSideExportPath = <?= json_encode((isset($serverSideExportPath) && is_string($serverSideExportPath) && trim($serverSideExportPath) !== '') ? $serverSideExportPath : '') ?>;
            const offlineProbePath = <?= json_encode(app_url('manifest.webmanifest')) ?>;
            const notificationsPagePath = <?= json_encode($notificationsPageUrl) ?>;
            const notificationSoundPath = <?= json_encode($notificationSoundUrl) ?>;
            const csrfToken = <?= json_encode($csrfToken) ?>;
            const offlinePolicy = <?= json_encode($offlinePolicy ?? app_offline_policy_for_role((string)($_SESSION['role_name'] ?? '')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            if (offlinePolicy && typeof offlinePolicy === 'object') {
                window.__DTMIS_OFFLINE_POLICY = offlinePolicy;
            }
            window.__DTMIS_OFFLINE_SYNC_LOG_URL = <?= json_encode($offlineSyncLogUrl ?? app_url('actions/offline-sync-log.php')) ?>;
            const currentUserId = <?= json_encode((int)($_SESSION['user_id'] ?? 0)) ?>;
            if (Number.isFinite(currentUserId) && currentUserId > 0) {
                window.__DTMIS_CACHE_SCOPE = 'user:' + String(currentUserId);
            }
            const currentRoleKey = <?= json_encode($roleKey) ?>;
            const currentRoleKeyRaw = <?= json_encode($roleKeyRaw ?? $roleKey) ?>;
            const activeMenuKey = <?= json_encode($activeMenu ?? 'dashboard') ?>;
            const preferScopedQueueCountersForLiveStats = String(activeMenuKey || '').trim() !== 'dashboard';
            const isCenroAdminRecordRole = String(currentRoleKeyRaw || '').toUpperCase() === 'CENRO_ADMIN_RECORD';
            const isPamoAdminRole = String(currentRoleKeyRaw || '').toUpperCase() === 'PAMO_ADMIN';
            const offlineArchivePrefetchLimit = 40;
            const offlinePrefetchedDocumentUrls = new Set();
            let offlineArchivePrefetchTimerId = 0;
            let offlineArchiveAvailabilityRequestId = 0;
            const isPenroAdminRecordRole = String(currentRoleKeyRaw || '').toUpperCase() === 'PENRO_ADMIN_RECORD';
            const isPamoOfficerRole = String(currentRoleKeyRaw || '').toUpperCase() === 'PASU_OFFICER';
            const isPamoUnitRole = String(currentRoleKeyRaw || '').toUpperCase() === 'PAMO_UNIT';
            const isPenroOfficerRole = String(currentRoleKeyRaw || '').toUpperCase() === 'PENRO_OFFICER';
            const isPenroDivisionRole = String(currentRoleKeyRaw || '').toUpperCase() === 'PENRO_DIVISION';
            const isPenroSectionRole = String(currentRoleKeyRaw || '').toUpperCase() === 'PENRO_SECTION';
            const isPenroSectionUnitRole = String(currentRoleKeyRaw || '').toUpperCase() === 'PENRO_SECTION_UNIT';
            const isCenroOfficerRole = String(currentRoleKeyRaw || '').toUpperCase() === 'CENRO_OFFICER';
            const isCenroSectionRole = String(currentRoleKeyRaw || '').toUpperCase() === 'CENRO_SECTION';
            const isCenroUnitRole = String(currentRoleKeyRaw || '').toUpperCase() === 'CENRO_UNIT';
            const isRegionalOredReviewer = currentRoleKey === 'ORED' && String(currentRoleKeyRaw || '').toUpperCase() === 'ORED';
            const isRegionalOredSigner = currentRoleKey === 'ORED' && String(currentRoleKeyRaw || '').toUpperCase() === 'ORED_SIGN';
            const approveActionDisabled = true;
            const officerDigitalSignDisabled = isCenroOfficerRole || isPenroOfficerRole || isPamoOfficerRole;
            const regionalOredDigitalSignDisabled = currentRoleKey === 'ORED' && !isRegionalOredSigner;
            const queueFlowPopoverDisabled = true;
            const isCenroInternalFlowRole = isCenroAdminRecordRole || isCenroOfficerRole || isCenroSectionRole || isCenroUnitRole;
            const isPamoInternalFlowRole = isPamoAdminRole || isPamoOfficerRole || isPamoUnitRole;
            const isPenroInternalFlowRole = isPenroAdminRecordRole || isPenroOfficerRole || isPenroDivisionRole || isPenroSectionRole || isPenroSectionUnitRole;
            const isArdRole = currentRoleKey === 'ARD_TS' || currentRoleKey === 'ARD_MS';
            const isIntakePage = <?= json_encode($isIntakePage) ?>;
            const filterRouteExistingAttachmentsToPreparedResponse = <?= json_encode((bool)($filterRouteExistingAttachmentsToPreparedResponse ?? false)) ?>;
            const forceHideRerouteQuickAction = <?= json_encode((bool)($forceHideRerouteQuickAction ?? false)) ?>;
            const currentOfficeId = <?= json_encode((int)($_SESSION['office_id'] ?? 0)) ?>;
            const currentOfficeName = <?= json_encode((string)($officeContext['name'] ?? '')) ?>;
            const currentOfficeLevel = <?= json_encode((string)($officeContext['level'] ?? '')) ?>;
            const currentOfficeParentId = <?= json_encode((int)($officeContext['parent_office_id'] ?? 0)) ?>;
            const isInternalPasuOffice = currentRoleKey === 'PASU_OFFICER' && String(currentOfficeLevel || '').trim().toUpperCase() === 'PASU_OFFICER';
            const hideRerouteQuickAction = ['RECORDS_UNIT'].indexOf(String(currentRoleKey || '').toUpperCase()) !== -1
                || isCenroUnitRole
                || isPamoUnitRole
                || forceHideRerouteQuickAction;
            const executiveRoleLabel = isCenroOfficerRole
                ? 'CENRO Officer'
                : (isPenroOfficerRole
                    ? 'PENRO Officer'
                    : (isPamoOfficerRole ? 'PASU' : 'ORED'));
            const hideQueueDocumentOnlyActions = <?= json_encode($hideQueueDocumentOnlyActions) ?>;
            const enableQueueRerouteAction = <?= json_encode($enableQueueRerouteAction) ?>;
            const rerouteOfficeLevelFilter = <?= json_encode($rerouteOfficeLevelFilter) ?>;
            const showDivisionChiefStaffRerouteTargets = <?= json_encode((bool)($showDivisionChiefStaffRerouteTargets ?? false)) ?>;
            const routeOffices = <?= json_encode($routeOffices, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const routeFallbackOffice = <?= json_encode($routeFallbackOffice, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const routeFallbackOffices = <?= json_encode($routeFallbackOffices, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const statCardNavigationTargets = <?= json_encode($statCardNavigationTargets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            let chartTrendData = <?= json_encode($chartTrendPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            let chartPieSeries = <?= json_encode($chartPieSeries ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            let chartPieTotal  = <?= (int)($chartPieTotal ?? 0) ?>;
            function getDashboardLiveLimit() {
                try {
                    const parsedLivePath = new URL(String(dashboardLivePath || ''), window.location.origin);
                    const requestedLimit = Number.parseInt(parsedLivePath.searchParams.get('limit') || '', 10);
                    if (Number.isFinite(requestedLimit) && requestedLimit >= 5) {
                        return Math.min(requestedLimit, 100);
                    }
                } catch (error) {
                }
                return 80;
            }
            const notificationReadKey = 'DTMIS_notif_read_until_' + String(currentUserId > 0 ? currentUserId : 'global');
            const screenshotAutoCaptureKey = 'DTMIS_screenshot_auto_capture_' + String(currentUserId > 0 ? currentUserId : 'global');
            const screenshotPreferenceDbName = 'DTMIS_user_preferences';
            const screenshotPreferenceStoreName = 'kv';
            const screenshotDirectoryHandleKey = 'screenshot_directory_handle_v1_' + String(currentUserId > 0 ? currentUserId : 'global');
            const hideCompletedQueueRows = <?= json_encode($hideCompletedQueueRows) ?>;
            const showCompletedQueueRowsOnly = <?= json_encode($showCompletedQueueRowsOnly) ?>;
            const showStrictCompletedOnly = <?= json_encode($showStrictCompletedOnly) ?>;
            const forceDocumentToolsOnTrackingTable = <?= json_encode($forceDocumentToolsOnTrackingTable ?? false) ?>;
            const liveDashboardRefreshEnabled = <?= json_encode(!$disableLiveDashboardRefresh) ?>;
            const bulkRowExportEnabled = <?= json_encode($enableBulkRowExport) ?>;
            const forceWorkflowActionScope = <?= json_encode((bool)($forceWorkflowActionScope ?? false)) ?>;
            let liveScopeMode = 'queue';
            try {
                const parsedLivePath = new URL(String(dashboardLivePath || ''), window.location.origin);
                liveScopeMode = normalizeLabelKey(parsedLivePath.searchParams.get('scope') || 'queue');
            } catch (error) {
                liveScopeMode = 'queue';
            }
            const workflowActionScopes = ['queue', 'pending_receive', 'pending_receive_action', 'pending_receive_penro', 'for_cenro_action', 'office_action', 'returned_regional', 'outbox'];
            const workflowActionScopeActive = workflowActionScopes.indexOf(liveScopeMode) !== -1 || forceWorkflowActionScope;
            const offlineQueuedStatusFilterValue = 'offline_queued';
            const offlineQueuedStatusFilterLabel = 'Offline Queued';
            const offlineQueuedStatusPrefix = '[Offline]';

            const logoutButtons = document.querySelectorAll('[data-logout="true"]');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarHost = document.querySelector('.sidebar-host');
            const dashboardShell = document.querySelector('.dashboard-shell');
            const queueSearchInput = document.getElementById('queueSearch');
            const tablePanel = document.querySelector('.table-panel');
            const tableElement = tablePanel ? tablePanel.querySelector('table') : null;
            const tableBody = tableElement ? tableElement.querySelector('tbody') : null;
            const tableWrap = tablePanel ? tablePanel.querySelector('.table-wrap') : null;
            const queueLiveStatus = document.getElementById('queueLiveStatus');
            const queueLiveChannelIndicator = document.getElementById('queueLiveChannelIndicator');
            const headerOfflineIndicator = document.getElementById('headerOfflineIndicator');
            const headerOfflineIndicatorText = document.getElementById('headerOfflineIndicatorText');
            const documentTools = document.getElementById('documentTools');
            const documentToolsMeta = document.getElementById('documentToolsMeta');
            const openCreateIntakeModalBtn = document.getElementById('openCreateIntakeModalBtn');
            const selectedDetailsBtn = document.getElementById('selectedDetailsBtn');
            const selectedViewSlipBtn = document.getElementById('selectedViewSlipBtn');
            const selectedPrintPackageBtn = document.getElementById('selectedPrintPackageBtn');
            const selectedQrStampBtn = document.getElementById('selectedQrStampBtn');
            const defaultDocumentToolsMetaText = (function () {
                if (openCreateIntakeModalBtn && selectedQrStampBtn) {
                    return 'Use Create Intake or select a document row to view details, tracking slip, print package, or QR stamp.';
                }
                if (openCreateIntakeModalBtn) {
                    return 'Use Create Intake or select a document row to view details, tracking slip, or print package.';
                }
                if (selectedQrStampBtn) {
                    return 'Select a document row to view details, tracking slip, print package, or QR stamp.';
                }
                return 'Select a document row to view details, tracking slip, or print package.';
            })();
            const exportLink = tablePanel ? tablePanel.querySelector('.table-link') : null;
            const bulkSelectAllRows = document.getElementById('bulkSelectAllRows');
            const bulkSelectedCount = document.getElementById('bulkSelectedCount');
            const notifToggle = document.getElementById('notifToggle');
            const notifDropdown = document.getElementById('notifDropdown');
            const profileToggle = document.getElementById('profileToggle');
            const profileDropdown = document.getElementById('profileDropdown');
            const profileSettingsButton = document.querySelector('[data-profile-settings="true"]');
            const setScreenshotDirectoryBtn = document.getElementById('setScreenshotDirectoryBtn');
            const capturePageScreenshotBtn = document.getElementById('capturePageScreenshotBtn');
            const capturePageScreenshotHeaderBtn = document.getElementById('capturePageScreenshotHeaderBtn');
            const toggleAutoScreenshotBtn = document.getElementById('toggleAutoScreenshotBtn');
            const statusFilterSelect = document.getElementById('statusFilterSelect');
            const dateFilterToggle = document.getElementById('dateFilterToggle');
            const dateFilterDropdown = document.getElementById('dateFilterDropdown');
            const dateFilterApplyButton = dateFilterDropdown ? dateFilterDropdown.querySelector('.date-filter-btn') : null;
            const dateFilterFromInput = dateFilterDropdown ? dateFilterDropdown.querySelector('input[name="fromDate"]') : null;
            const dateFilterToInput = dateFilterDropdown ? dateFilterDropdown.querySelector('input[name="toDate"]') : null;
            const queueTablePagination = document.getElementById('queueTablePagination');
            const queueTablePaginationMeta = document.getElementById('queueTablePaginationMeta');
            const queueTablePrevBtn = document.getElementById('queueTablePrevBtn');
            const queueTableNextBtn = document.getElementById('queueTableNextBtn');
            const routeActionModal = document.getElementById('routeActionModal');
            const routeActionForm = document.getElementById('routeActionForm');
            const routeActionTitle = document.getElementById('routeActionTitle');
            const routeActionMeta = document.getElementById('routeActionMeta');
            const routeReleaseModeWrap = document.getElementById('routeReleaseModeWrap');
            const routeReleaseModeMeta = document.getElementById('routeReleaseModeMeta');
            const routeReleaseMode = document.getElementById('routeReleaseMode');
            const routeDestinationWrap = document.getElementById('routeDestinationWrap');
            const routeDestinationMeta = document.getElementById('routeDestinationMeta');
            const routeDestinationTypeFilterWrap = document.getElementById('routeDestinationTypeFilterWrap');
            const routeDestinationFilterButtons = routeDestinationTypeFilterWrap
                ? Array.from(routeDestinationTypeFilterWrap.querySelectorAll('[data-route-destination-filter]'))
                : [];
            const routeDestinationOffice = document.getElementById('routeDestinationOffice');
            const routeBypassReasonWrap = document.getElementById('routeBypassReasonWrap');
            const routeBypassReason = document.getElementById('routeBypassReason');
            const routeRemarks = document.getElementById('routeRemarks');
            const routeNewSubjectWrap = document.getElementById('routeNewSubjectWrap');
            const routeNewSubject = document.getElementById('routeNewSubject');
            const routeAttachmentWrap = document.getElementById('routeAttachmentWrap');
            const routeAttachmentLabel = document.getElementById('routeAttachmentLabel');
            const routeAttachmentMeta = document.getElementById('routeAttachmentMeta');
            const routeAttachmentInput = document.getElementById('routeAttachmentInput');
            const routeExistingAttachmentsMeta = document.getElementById('routeExistingAttachmentsMeta');
            const routeExistingAttachmentsList = document.getElementById('routeExistingAttachmentsList');
            const routeActionSubmit = document.getElementById('routeActionSubmit');
            const routeActionCloseButtons = document.querySelectorAll('[data-action-modal-close="true"]');
            const editIntakeModal = document.getElementById('editIntakeModal');
            const editIntakeForm = document.getElementById('editIntakeForm');
            const editIntakeMeta = document.getElementById('editIntakeMeta');
            const editIntakeDocumentId = document.getElementById('editIntakeDocumentId');
            const editIntakeTrackingId = document.getElementById('editIntakeTrackingId');
            const editIntakeSourceType = document.getElementById('editIntakeSourceType');
            const editIntakeRoutingOffice = document.getElementById('editIntakeRoutingOffice');
            const editIntakeOriginatingEntity = document.getElementById('editIntakeOriginatingEntity');
            const editIntakeExternalWrap = document.getElementById('editIntakeExternalWrap');
            const editIntakeExternalClient = document.getElementById('editIntakeExternalClient');
            const editIntakeClientAddressWrap = document.getElementById('editIntakeClientAddressWrap');
            const editIntakeClientAddress = document.getElementById('editIntakeClientAddress');
            const editIntakeSubject = document.getElementById('editIntakeSubject');
            const editIntakeDocumentType = document.getElementById('editIntakeDocumentType');
            const editIntakeComplexityType = document.getElementById('editIntakeComplexityType');
            const editIntakeComplexityDays = document.getElementById('editIntakeComplexityDays');
            const editIntakeCurrentAttachments = document.getElementById('editIntakeCurrentAttachments');
            const editIntakeCurrentAttachmentsEmpty = document.getElementById('editIntakeCurrentAttachmentsEmpty');
            const editIntakeCurrentAttachmentsList = document.getElementById('editIntakeCurrentAttachmentsList');
            const editIntakeAttachments = document.getElementById('editIntakeAttachments');
            const editIntakeRemarks = document.getElementById('editIntakeRemarks');
            const editIntakeSubmit = document.getElementById('editIntakeSubmit');
            const editIntakeCloseButtons = document.querySelectorAll('[data-edit-intake-close="true"]');
            const appDialogModal = document.getElementById('appDialogModal');
            const appDialogTitle = document.getElementById('appDialogTitle');
            const appDialogMessage = document.getElementById('appDialogMessage');
            const appDialogConfirm = document.getElementById('appDialogConfirm');
            const appDialogCancel = document.getElementById('appDialogCancel');
            const appDialogCloseButtons = document.querySelectorAll('[data-app-dialog-close="true"]');
            const documentDetailsModal = document.getElementById('documentDetailsModal');
            const documentDetailsMeta = document.getElementById('documentDetailsMeta');
            const documentDetailsLoading = document.getElementById('documentDetailsLoading');
            const documentDetailsContent = document.getElementById('documentDetailsContent');
            const documentDetailsFields = document.getElementById('documentDetailsFields');
            const documentDetailsAttachmentFilterWrap = document.getElementById('documentDetailsAttachmentFilterWrap');
            const documentDetailsAttachmentFilterButtons = documentDetailsAttachmentFilterWrap
                ? Array.from(documentDetailsAttachmentFilterWrap.querySelectorAll('[data-details-attachment-filter]'))
                : [];
            const documentDetailsAttachmentSourceFilterWrap = document.getElementById('documentDetailsAttachmentSourceFilterWrap');
            const documentDetailsAttachmentSourceFilter = document.getElementById('documentDetailsAttachmentSourceFilter');
            const documentDetailsAttachmentSelect = document.getElementById('documentDetailsAttachmentSelect');
            const documentDetailsOpenAttachment = document.getElementById('documentDetailsOpenAttachment');
            const documentDetailsPreviewEmpty = document.getElementById('documentDetailsPreviewEmpty');
            const documentDetailsPreviewFrame = document.getElementById('documentDetailsPreviewFrame');
            const documentDetailsPreviewImage = document.getElementById('documentDetailsPreviewImage');
            const documentDetailsCloseButtons = document.querySelectorAll('[data-document-details-close="true"]');
            const welcomeLoader = document.getElementById('welcomeLoader');
            const stickyActionButtons = document.querySelectorAll('.page-sticky-actions button');
            const workflowSplineChart = document.getElementById('workflowSplineChart');
            const chartPanels = Array.from(document.querySelectorAll('.chart-panel[data-chart-kind]'));
            const intakeForm = document.getElementById('intakeForm');
            const createIntakeModal = document.getElementById('createIntakeModal');
            const createIntakeCloseButtons = document.querySelectorAll('[data-create-intake-close="true"]');
            const intakeSourceTypeInput = document.getElementById('intakeSourceType');
            const intakeRoutingOfficeInput = document.getElementById('intakeRoutingOffice');
            const intakeOriginatingEntityInput = document.getElementById('intakeOriginatingEntity');
            const intakeOriginatingEntityLabel = document.getElementById('intakeOriginatingEntityLabel');
            const intakeOriginatingEntityHint = document.getElementById('intakeOriginatingEntityHint');
            const intakeSenderLabel = document.getElementById('intakeSenderLabel');
            const intakeSenderHint = document.getElementById('intakeSenderHint');
            const intakeExternalClient = document.getElementById('intakeExternalClient');
            const intakeClientAddressField = document.getElementById('intakeClientAddressField');
            const intakeClientAddressLabel = document.getElementById('intakeClientAddressLabel');
            const intakeClientAddressHint = document.getElementById('intakeClientAddressHint');
            const intakeClientAddressInput = document.getElementById('intakeClientAddress');
            const intakeDocumentType = document.getElementById('intakeDocumentType');
            const intakeSubmitButton = document.getElementById('intakeSubmit');
            const intakeSubmitForwardButton = document.getElementById('intakeSubmitForward');
            const intakeSaveDraftButton = document.getElementById('intakeSaveDraft');
            const intakeRemarksInput = document.getElementById('intakeRemarks');
            const intakeAttachmentsInput = document.getElementById('intakeAttachments');
            const intakeDestinationOffice = document.getElementById('intakeDestinationOffice');
            const intakeOtherDocumentTypeField = document.getElementById('intakeOtherDocumentTypeField');
            const customDocumentTypeNameInput = document.getElementById('customDocumentTypeName');
            const intakeComplexityTypeInput = document.getElementById('intakeComplexityType');
            const intakeComplexityDaysInput = document.getElementById('intakeComplexityDays');
            const intakeColorClassificationInput = document.getElementById('intakeColorClassification');
            const qrReceiveModal = document.getElementById('qrReceiveModal');
            const qrReceiveCloseButtons = document.querySelectorAll('[data-qr-receive-close="true"]');
            const qrReceivePanel = document.getElementById('qrReceivePanel');
            const openQrReceiveScannerBtn = document.getElementById('openQrReceiveScannerBtn');
            const qrReceiveTrackingInput = document.getElementById('qrReceiveTrackingInput');
            const qrReceiveManualBtn = document.getElementById('qrReceiveManualBtn');
            const qrReceiveStartBtn = document.getElementById('qrReceiveStartBtn');
            const qrReceiveStopBtn = document.getElementById('qrReceiveStopBtn');
            const qrReceiveViewport = document.getElementById('qrReceiveViewport');
            const qrReceiveVideo = document.getElementById('qrReceiveVideo');
            const qrReceiveStatus = document.getElementById('qrReceiveStatus');
            const liveFlowTrackerCard = document.getElementById('liveFlowTrackerCard');
            const liveFlowTrackingId = document.getElementById('liveFlowTrackingId');
            const liveFlowCurrentHolder = document.getElementById('liveFlowCurrentHolder');
            const liveFlowStatus = document.getElementById('liveFlowStatus');
            const liveFlowSteps = Array.from(document.querySelectorAll('.live-flow-step'));
            const liveFlowStepLabels = liveFlowSteps.map(function (stepNode) {
                const titleNode = stepNode ? stepNode.querySelector('.live-flow-step-title') : null;
                return titleNode ? String(titleNode.textContent || '').trim() : '';
            });
            const liveFlowViewport = document.getElementById('liveFlowViewport');
            const liveFlowCanvas = document.getElementById('liveFlowCanvas');
            const liveFlowZoomInBtn = document.getElementById('liveFlowZoomIn');
            const liveFlowZoomOutBtn = document.getElementById('liveFlowZoomOut');
            const liveFlowResetBtn = document.getElementById('liveFlowResetView');

            let activeRouteContext = null;
            let routeExistingAttachmentsRequestId = 0;
            let routeExistingPreparedResponseCount = null;
            let routeExistingPreparedResponseLoading = false;
            let activeRouteDestinationFilter = 'all';
            let qrScanStream = null;
            let qrDetector = null;
            let qrScanRafId = 0;
            let qrIsActive = false;
            let qrDetectInProgress = false;
            let sidebarResizeHandle = null;
            let isSidebarResizing = false;
            let sidebarResizeStartX = 0;
            let sidebarResizeStartWidth = 0;
            let liveRefreshTimerId = 0;
            let liveRefreshInFlight = false;
            let liveRefreshPending = false;
            let sessionHeartbeatTimerId = 0;
            let sessionHeartbeatInFlight = false;
            let latestLiveDashboardPayload = null;
            let chartOverrideStateByKind = {};
            let chartRequestSerialByKind = {};
            let selectedQueueTrackingId = '';
            const queueRowsPerPage = 10;
            const shouldPaginateQueueTable = !/tracking-slip\.php$/i.test(String(window.location.pathname || ''));
            const queueTableStateStorageKey = 'DTMIS_queue_table_state_v1:' + String(window.location.pathname || '').toLowerCase();
            let queueCurrentPage = 1;
            let lastQueueFilterSignature = '';
            let selectedBulkRowKeys = new Set();
            let appDialogResolver = null;
            let documentDetailsAbortController = null;
            let documentDetailsAttachments = [];
            let documentDetailsAllAttachments = [];
            let documentDetailsAttachmentFilter = 'original';
            let documentDetailsAttachmentSourceFilterValue = 'all';
            let documentDetailsCreatorUserId = 0;
            let html2CanvasLoaderPromise = null;
            let htmlToImageLoaderPromise = null;
            let screenshotDirectoryHandleCache = null;
            let screenshotDisplayStream = null;
            let screenshotDisplayVideo = null;
            let screenshotAutoCaptureEnabled = false;
            let screenshotAutoCaptureTriggered = false;
            let screenshotCaptureInProgress = false;
            let editIntakeTriggerButton = null;
            let editIntakeDocumentVersion = 0;
            let liveFlowScale = 1;
            let liveFlowOffsetX = 0;
            let liveFlowOffsetY = 0;
            let liveFlowIsPanning = false;
            let liveFlowLastPanX = 0;
            let liveFlowLastPanY = 0;
            let liveFlowHasManualTransform = false;
            let queueFlowHoverPopover = null;
            let queueFlowHoverTrackingLabel = null;
            let queueFlowHoverCurrentHolderLabel = null;
            let queueFlowHoverStatusLabel = null;
            let queueFlowHoverStageLabel = null;
            let queueFlowHoverStepNodes = [];
            let queueFlowHoverActiveRow = null;
            let queueFlowSidebarLayout = null;
            let latestLiveQueueRows = [];
            let offlineQueuedQueueRows = [];
            let offlineQueueRefreshRequestId = 0;
            let offlineQueuedRowsSignature = '';
            let searchableSelectCounter = 0;
            const searchableSelectRegistry = new Map();
            let activeSearchableSelect = null;

            function searchableSelectQueryKey(value) {
                return String(value || '').trim().toLowerCase();
            }

            function searchableSelectPlaceholder(select) {
                if (!select) {
                    return 'Search options...';
                }
                const explicitPlaceholder = String(select.dataset && select.dataset.searchPlaceholder ? select.dataset.searchPlaceholder : '').trim();
                if (explicitPlaceholder !== '') {
                    return explicitPlaceholder;
                }
                if (!select.id) {
                    select.id = 'searchableSelect' + String(++searchableSelectCounter);
                }
                const directLabel = document.querySelector('label[for="' + CSS.escape(select.id) + '"]');
                const wrappingLabel = select.closest('label');
                const sourceLabel = directLabel || wrappingLabel;
                const labelText = sourceLabel
                    ? String(sourceLabel.textContent || '').replace(/\s+/g, ' ').trim()
                    : '';
                return labelText !== '' ? ('Search ' + labelText + '...') : 'Search options...';
            }

            function searchableSelectCollectOptions(select) {
                const items = [];
                Array.from(select.children).forEach(function (childNode) {
                    if (childNode instanceof HTMLOptionElement) {
                        items.push({
                            value: String(childNode.value || ''),
                            text: String(childNode.textContent || '').replace(/\s+/g, ' ').trim(),
                            disabled: !!childNode.disabled,
                            selected: !!childNode.selected,
                            groupLabel: '',
                        });
                        return;
                    }
                    if (childNode instanceof HTMLOptGroupElement) {
                        const groupLabel = String(childNode.label || '').trim();
                        Array.from(childNode.children).forEach(function (groupOptionNode) {
                            if (!(groupOptionNode instanceof HTMLOptionElement)) {
                                return;
                            }
                            items.push({
                                value: String(groupOptionNode.value || ''),
                                text: String(groupOptionNode.textContent || '').replace(/\s+/g, ' ').trim(),
                                disabled: !!groupOptionNode.disabled || !!childNode.disabled,
                                selected: !!groupOptionNode.selected,
                                groupLabel: groupLabel,
                            });
                        });
                    }
                });
                return items;
            }

            function searchableSelectSelectedText(select) {
                const selectedOption = select.selectedOptions && select.selectedOptions.length > 0
                    ? select.selectedOptions[0]
                    : (select.options && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null);
                const fallbackOption = selectedOption || (select.options && select.options.length > 0 ? select.options[0] : null);
                return fallbackOption
                    ? String(fallbackOption.textContent || '').replace(/\s+/g, ' ').trim()
                    : 'Select option';
            }

            function searchableSelectMatches(item, queryKey) {
                if (queryKey === '') {
                    return true;
                }
                if (item.value === '') {
                    return true;
                }
                const textKey = searchableSelectQueryKey(item.text);
                const valueKey = searchableSelectQueryKey(item.value);
                const groupKey = searchableSelectQueryKey(item.groupLabel);
                return textKey.indexOf(queryKey) !== -1
                    || valueKey.indexOf(queryKey) !== -1
                    || groupKey.indexOf(queryKey) !== -1;
            }

            function searchableSelectClose(meta, restoreFocus) {
                if (!meta || !meta.isOpen) {
                    return;
                }
                meta.isOpen = false;
                meta.wrapper.classList.remove('is-open');
                meta.panel.hidden = true;
                meta.input.setAttribute('aria-expanded', 'false');
                searchableSelectSyncTrigger(meta);
                if (activeSearchableSelect === meta) {
                    activeSearchableSelect = null;
                }
                if (restoreFocus) {
                    meta.input.focus();
                    meta.input.select();
                }
            }

            function searchableSelectSyncTrigger(meta) {
                if (!meta || !meta.select) {
                    return;
                }
                meta.input.disabled = !!meta.select.disabled;
                meta.wrapper.hidden = !!meta.select.hidden;
                if (!meta.isOpen) {
                    meta.input.value = searchableSelectSelectedText(meta.select);
                }
            }

            function searchableSelectRenderOptions(meta) {
                if (!meta) {
                    return;
                }
                const queryKey = searchableSelectQueryKey(meta.input.value);
                const selectedValue = String(meta.select.value || '');
                const visibleItems = meta.options.filter(function (item) {
                    return searchableSelectMatches(item, queryKey);
                });

                meta.list.innerHTML = '';

                if (visibleItems.length === 0) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'searchable-select-empty';
                    emptyState.textContent = 'No matching options';
                    meta.list.appendChild(emptyState);
                    return;
                }

                visibleItems.forEach(function (item) {
                    const optionButton = document.createElement('button');
                    optionButton.type = 'button';
                    optionButton.className = 'searchable-select-option';
                    if (item.value === selectedValue) {
                        optionButton.classList.add('is-selected');
                    }
                    optionButton.disabled = !!item.disabled;
                    optionButton.dataset.value = item.value;

                    const optionPrimary = document.createElement('span');
                    optionPrimary.className = 'searchable-select-option-text';
                    optionPrimary.textContent = item.text;
                    optionButton.appendChild(optionPrimary);

                    if (item.groupLabel !== '') {
                        const optionMeta = document.createElement('span');
                        optionMeta.className = 'searchable-select-option-meta';
                        optionMeta.textContent = item.groupLabel;
                        optionButton.appendChild(optionMeta);
                    }

                    optionButton.addEventListener('click', function () {
                        if (item.disabled) {
                            return;
                        }
                        meta.select.value = item.value;
                        meta.select.dispatchEvent(new Event('change', { bubbles: true }));
                        searchableSelectRefresh(meta.select, false);
                        searchableSelectClose(meta, true);
                    });

                    meta.list.appendChild(optionButton);
                });
            }

            function searchableSelectOpen(meta) {
                if (!meta || meta.select.disabled) {
                    return;
                }
                if (activeSearchableSelect && activeSearchableSelect !== meta) {
                    searchableSelectClose(activeSearchableSelect, false);
                }
                activeSearchableSelect = meta;
                meta.isOpen = true;
                meta.wrapper.classList.add('is-open');
                meta.panel.hidden = false;
                meta.input.setAttribute('aria-expanded', 'true');
                meta.input.value = '';
                searchableSelectRenderOptions(meta);
                requestAnimationFrame(function () {
                    meta.input.focus();
                });
            }

            function searchableSelectToggle(meta) {
                if (!meta) {
                    return;
                }
                if (meta.isOpen) {
                    searchableSelectClose(meta, true);
                    return;
                }
                searchableSelectOpen(meta);
            }

            function searchableSelectRefresh(select, preserveQuery) {
                const meta = searchableSelectRegistry.get(select);
                if (!meta) {
                    return;
                }
                meta.options = searchableSelectCollectOptions(select);
                if (!preserveQuery) {
                    meta.input.value = '';
                }
                searchableSelectSyncTrigger(meta);
                if (meta.isOpen) {
                    searchableSelectRenderOptions(meta);
                }
            }

            function focusSearchableSelect(select, openPanel) {
                const meta = searchableSelectRegistry.get(select);
                if (!meta) {
                    if (select && typeof select.focus === 'function') {
                        select.focus();
                    }
                    return;
                }
                if (openPanel) {
                    searchableSelectOpen(meta);
                    return;
                }
                meta.trigger.focus();
            }

            function initSearchableSelect(select) {
                if (!(select instanceof HTMLSelectElement) || select.multiple || select.size > 1) {
                    return;
                }
                if (String(select.dataset && select.dataset.noSearchableSelect || '') === '1') {
                    return;
                }

                if (searchableSelectRegistry.has(select)) {
                    searchableSelectRefresh(select, true);
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className = 'searchable-select';

                const control = document.createElement('div');
                control.className = 'searchable-select-control';

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'searchable-select-input';
                input.placeholder = searchableSelectPlaceholder(select);
                input.autocomplete = 'off';
                input.spellcheck = false;
                input.setAttribute('aria-label', input.placeholder);
                input.setAttribute('aria-expanded', 'false');
                input.setAttribute('aria-autocomplete', 'list');
                input.setAttribute('aria-haspopup', 'listbox');
                input.setAttribute('role', 'combobox');

                const triggerCaret = document.createElement('span');
                triggerCaret.className = 'searchable-select-trigger-caret';
                triggerCaret.innerHTML = '&#9662;';
                control.appendChild(input);
                control.appendChild(triggerCaret);

                const panel = document.createElement('div');
                panel.className = 'searchable-select-panel';
                panel.hidden = true;

                const list = document.createElement('div');
                list.className = 'searchable-select-list';

                panel.appendChild(list);

                if (select.parentNode) {
                    select.parentNode.insertBefore(wrapper, select);
                }
                wrapper.appendChild(select);
                wrapper.appendChild(control);
                wrapper.appendChild(panel);
                select.classList.add('searchable-select-native');
                select.setAttribute('tabindex', '-1');
                select.setAttribute('aria-hidden', 'true');

                const meta = {
                    select: select,
                    wrapper: wrapper,
                    control: control,
                    trigger: input,
                    input: input,
                    panel: panel,
                    list: list,
                    options: searchableSelectCollectOptions(select),
                    isOpen: false,
                    observer: null,
                };
                searchableSelectRegistry.set(select, meta);

                input.addEventListener('click', function () {
                    if (!meta.isOpen) {
                        searchableSelectOpen(meta);
                    }
                });

                input.addEventListener('focus', function () {
                    if (!meta.isOpen) {
                        requestAnimationFrame(function () {
                            input.select();
                        });
                    }
                });

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'ArrowDown' || event.key === 'Enter') {
                        event.preventDefault();
                        searchableSelectOpen(meta);
                        return;
                    }
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        searchableSelectClose(meta, true);
                    }
                });

                input.addEventListener('input', function () {
                    if (!meta.isOpen) {
                        if (activeSearchableSelect && activeSearchableSelect !== meta) {
                            searchableSelectClose(activeSearchableSelect, false);
                        }
                        meta.isOpen = true;
                        meta.wrapper.classList.add('is-open');
                        meta.panel.hidden = false;
                        meta.input.setAttribute('aria-expanded', 'true');
                        activeSearchableSelect = meta;
                    }
                    searchableSelectRenderOptions(meta);
                });

                select.addEventListener('change', function () {
                    searchableSelectRefresh(select, false);
                });

                meta.observer = new MutationObserver(function () {
                    searchableSelectRefresh(select, true);
                });
                meta.observer.observe(select, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['disabled', 'hidden'],
                });

                searchableSelectSyncTrigger(meta);
            }

            function initAllSearchableSelects(root) {
                const scope = root && root.querySelectorAll ? root : document;
                scope.querySelectorAll('select').forEach(function (selectNode) {
                    initSearchableSelect(selectNode);
                });
            }

            document.addEventListener('click', function (event) {
                if (!activeSearchableSelect) {
                    return;
                }
                if (activeSearchableSelect.wrapper.contains(event.target)) {
                    return;
                }
                searchableSelectClose(activeSearchableSelect, false);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && activeSearchableSelect) {
                    searchableSelectClose(activeSearchableSelect, true);
                }
            });

            function closeAppDialog(result) {
                if (!appDialogModal) {
                    return;
                }
                appDialogModal.classList.remove('is-open');
                appDialogModal.hidden = true;
                document.body.classList.remove('modal-open');
                if (typeof appDialogResolver === 'function') {
                    const resolver = appDialogResolver;
                    appDialogResolver = null;
                    resolver(result);
                }
            }

            function openAppDialog(options) {
                if (!appDialogModal || !appDialogTitle || !appDialogMessage || !appDialogConfirm || !appDialogCancel) {
                    window.alert(String(options && options.message ? options.message : ''));
                    return Promise.resolve(true);
                }

                const settings = options && typeof options === 'object' ? options : {};
                const title = String(settings.title || 'Notice').trim();
                const message = String(settings.message || '').trim();
                const confirmLabel = String(settings.confirmLabel || 'OK').trim();
                const cancelLabel = String(settings.cancelLabel || 'Cancel').trim();
                const showCancel = !!settings.showCancel;

                appDialogTitle.textContent = title === '' ? 'Notice' : title;
                appDialogMessage.textContent = message;
                appDialogConfirm.textContent = confirmLabel === '' ? 'OK' : confirmLabel;
                appDialogCancel.textContent = cancelLabel === '' ? 'Cancel' : cancelLabel;
                appDialogCancel.hidden = !showCancel;

                appDialogModal.hidden = false;
                appDialogModal.classList.add('is-open');
                document.body.classList.add('modal-open');
                appDialogConfirm.focus();

                return new Promise(function (resolve) {
                    appDialogResolver = resolve;
                });
            }

            function showAlertDialog(message, title) {
                return openAppDialog({
                    title: String(title || 'Notice'),
                    message: String(message || ''),
                    showCancel: false,
                    confirmLabel: 'OK',
                });
            }

            function showConfirmDialog(message, title, confirmLabel) {
                return openAppDialog({
                    title: String(title || 'Please confirm'),
                    message: String(message || ''),
                    showCancel: true,
                    confirmLabel: String(confirmLabel || 'Confirm'),
                    cancelLabel: 'Cancel',
                });
            }

            window.showAlertDialog = showAlertDialog;
            window.showConfirmDialog = showConfirmDialog;

            function parseContentDispositionFilename(headerValue) {
                const raw = String(headerValue || '').trim();
                if (raw === '') {
                    return '';
                }

                let match = raw.match(/filename\*=UTF-8''([^;]+)/i);
                if (match && match[1]) {
                    try {
                        return decodeURIComponent(String(match[1]).trim());
                    } catch (error) {
                        return String(match[1]).trim();
                    }
                }

                match = raw.match(/filename=\"([^\"]+)\"/i);
                if (match && match[1]) {
                    return String(match[1]).trim();
                }

                match = raw.match(/filename=([^;]+)/i);
                if (match && match[1]) {
                    return String(match[1]).trim().replace(/^["']|["']$/g, '');
                }

                return '';
            }

            function downloadBlob(blob, filename) {
                const safeBlob = blob instanceof Blob
                    ? blob
                    : new Blob([String(blob == null ? '' : blob)], { type: 'application/octet-stream' });
                const safeFilename = String(filename || '').trim() || ('DTMIS-export-' + new Date().toISOString().slice(0, 10) + '.csv');
                const exportLinkNode = document.createElement('a');
                exportLinkNode.href = URL.createObjectURL(safeBlob);
                exportLinkNode.download = safeFilename;
                document.body.appendChild(exportLinkNode);
                exportLinkNode.click();
                document.body.removeChild(exportLinkNode);
                window.setTimeout(function () {
                    URL.revokeObjectURL(exportLinkNode.href);
                }, 1000);
            }

            async function sendSessionHeartbeat() {
                if (
                    sessionHeartbeatInFlight
                    || !sessionHeartbeatPath
                    || !currentUserId
                    || document.hidden
                    || !navigator.onLine
                ) {
                    return;
                }

                sessionHeartbeatInFlight = true;
                try {
                    const formData = new FormData();
                    formData.set('csrf_token', String(csrfToken || ''));

                    await fetch(sessionHeartbeatPath, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        cache: 'no-store',
                        keepalive: true,
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                } catch (error) {
                    // Ignore transient heartbeat failures.
                } finally {
                    sessionHeartbeatInFlight = false;
                }
            }

            function bindSessionHeartbeat() {
                if (!sessionHeartbeatPath || !currentUserId) {
                    return;
                }

                sendSessionHeartbeat();
                if (sessionHeartbeatTimerId > 0) {
                    window.clearInterval(sessionHeartbeatTimerId);
                }
                sessionHeartbeatTimerId = window.setInterval(function () {
                    sendSessionHeartbeat();
                }, 60000);

                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) {
                        sendSessionHeartbeat();
                    }
                });

                window.addEventListener('online', function () {
                    sendSessionHeartbeat();
                });
            }

            function bindAppDialog() {
                if (!appDialogModal || !appDialogConfirm) {
                    return;
                }

                if (appDialogConfirm) {
                    appDialogConfirm.addEventListener('click', function () {
                        closeAppDialog(true);
                    });
                }
                if (appDialogCancel) {
                    appDialogCancel.addEventListener('click', function () {
                        closeAppDialog(false);
                    });
                }
                appDialogCloseButtons.forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        closeAppDialog(false);
                    });
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && appDialogModal && !appDialogModal.hidden) {
                        closeAppDialog(false);
                    }
                });
            }

            function screenshotDirectoryPickerSupported() {
                return typeof window.showDirectoryPicker === 'function' && typeof window.indexedDB !== 'undefined';
            }

            function openScreenshotPreferenceDb() {
                return new Promise(function (resolve, reject) {
                    if (typeof window.indexedDB === 'undefined') {
                        reject(new Error('IndexedDB is not available in this browser.'));
                        return;
                    }
                    const request = window.indexedDB.open(screenshotPreferenceDbName, 1);
                    request.onupgradeneeded = function () {
                        const db = request.result;
                        if (!db.objectStoreNames.contains(screenshotPreferenceStoreName)) {
                            db.createObjectStore(screenshotPreferenceStoreName);
                        }
                    };
                    request.onsuccess = function () {
                        resolve(request.result);
                    };
                    request.onerror = function () {
                        reject(request.error || new Error('Unable to open screenshot preference storage.'));
                    };
                });
            }

            function screenshotPreferenceGet(key) {
                return openScreenshotPreferenceDb().then(function (db) {
                    return new Promise(function (resolve, reject) {
                        const tx = db.transaction(screenshotPreferenceStoreName, 'readonly');
                        const store = tx.objectStore(screenshotPreferenceStoreName);
                        const request = store.get(String(key || ''));
                        request.onsuccess = function () {
                            resolve(request.result || null);
                        };
                        request.onerror = function () {
                            reject(request.error || new Error('Unable to read screenshot preference.'));
                        };
                        tx.oncomplete = function () {
                            db.close();
                        };
                    });
                });
            }

            function screenshotPreferenceSet(key, value) {
                return openScreenshotPreferenceDb().then(function (db) {
                    return new Promise(function (resolve, reject) {
                        const tx = db.transaction(screenshotPreferenceStoreName, 'readwrite');
                        const store = tx.objectStore(screenshotPreferenceStoreName);
                        const request = store.put(value, String(key || ''));
                        request.onsuccess = function () {
                            resolve(true);
                        };
                        request.onerror = function () {
                            reject(request.error || new Error('Unable to save screenshot preference.'));
                        };
                        tx.oncomplete = function () {
                            db.close();
                        };
                    });
                });
            }

            function screenshotPreferenceDelete(key) {
                return openScreenshotPreferenceDb().then(function (db) {
                    return new Promise(function (resolve, reject) {
                        const tx = db.transaction(screenshotPreferenceStoreName, 'readwrite');
                        const store = tx.objectStore(screenshotPreferenceStoreName);
                        const request = store.delete(String(key || ''));
                        request.onsuccess = function () {
                            resolve(true);
                        };
                        request.onerror = function () {
                            reject(request.error || new Error('Unable to clear screenshot preference.'));
                        };
                        tx.oncomplete = function () {
                            db.close();
                        };
                    });
                });
            }

            async function ensureScreenshotDirectoryPermission(handle) {
                if (!handle || typeof handle.queryPermission !== 'function' || typeof handle.requestPermission !== 'function') {
                    return false;
                }
                const options = { mode: 'readwrite' };
                try {
                    const existing = await handle.queryPermission(options);
                    if (existing === 'granted') {
                        return true;
                    }
                } catch (error) {
                    return false;
                }

                try {
                    const requested = await handle.requestPermission(options);
                    return requested === 'granted';
                } catch (error) {
                    return false;
                }
            }

            async function getStoredScreenshotDirectoryHandle() {
                if (!screenshotDirectoryPickerSupported()) {
                    return null;
                }
                if (screenshotDirectoryHandleCache) {
                    return screenshotDirectoryHandleCache;
                }
                try {
                    const storedHandle = await screenshotPreferenceGet(screenshotDirectoryHandleKey);
                    if (!storedHandle) {
                        return null;
                    }
                    const granted = await ensureScreenshotDirectoryPermission(storedHandle);
                    if (!granted) {
                        return null;
                    }
                    screenshotDirectoryHandleCache = storedHandle;
                    return storedHandle;
                } catch (error) {
                    return null;
                }
            }

            async function pickScreenshotDirectory(options) {
                const settings = options && typeof options === 'object' ? options : {};
                const forcePrompt = !!settings.forcePrompt;
                const promptIfMissing = settings.promptIfMissing !== false;

                if (!screenshotDirectoryPickerSupported()) {
                    return null;
                }

                if (!forcePrompt) {
                    const existingHandle = await getStoredScreenshotDirectoryHandle();
                    if (existingHandle) {
                        return existingHandle;
                    }
                    if (!promptIfMissing) {
                        return null;
                    }
                }

                let selectedHandle = null;
                try {
                    selectedHandle = await window.showDirectoryPicker({ mode: 'readwrite' });
                } catch (error) {
                    return null;
                }
                if (!selectedHandle) {
                    return null;
                }
                const granted = await ensureScreenshotDirectoryPermission(selectedHandle);
                if (!granted) {
                    return null;
                }
                screenshotDirectoryHandleCache = selectedHandle;
                try {
                    await screenshotPreferenceSet(screenshotDirectoryHandleKey, selectedHandle);
                } catch (error) {
                    // Keep runtime-selected handle even if preference storage write fails.
                }
                return selectedHandle;
            }

            function buildPageScreenshotFileName() {
                const now = new Date();
                const stamp = now.getFullYear().toString()
                    + String(now.getMonth() + 1).padStart(2, '0')
                    + String(now.getDate()).padStart(2, '0')
                    + '-'
                    + String(now.getHours()).padStart(2, '0')
                    + String(now.getMinutes()).padStart(2, '0')
                    + String(now.getSeconds()).padStart(2, '0');
                const pathKey = String(window.location.pathname || '')
                    .replace(/\\/g, '/')
                    .split('/')
                    .filter(function (part) { return String(part || '').trim() !== ''; })
                    .join('-')
                    .replace(/[^A-Za-z0-9._-]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '')
                    .toLowerCase();
                const pageKey = pathKey !== '' ? pathKey : 'dashboard';
                return 'DTMIS-' + pageKey + '-' + stamp + '.png';
            }

            function screenshotDisplayCaptureSupported() {
                return !!(navigator.mediaDevices && typeof navigator.mediaDevices.getDisplayMedia === 'function');
            }

            function hasActiveScreenshotDisplayStream() {
                if (!screenshotDisplayStream || typeof screenshotDisplayStream.getVideoTracks !== 'function') {
                    return false;
                }
                const tracks = screenshotDisplayStream.getVideoTracks();
                if (!tracks || tracks.length === 0) {
                    return false;
                }
                return tracks.some(function (track) {
                    return track && track.readyState === 'live';
                });
            }

            function stopScreenshotDisplayStream() {
                if (screenshotDisplayStream && typeof screenshotDisplayStream.getTracks === 'function') {
                    screenshotDisplayStream.getTracks().forEach(function (track) {
                        if (track && typeof track.stop === 'function') {
                            track.stop();
                        }
                    });
                }
                screenshotDisplayStream = null;
                if (screenshotDisplayVideo) {
                    try {
                        screenshotDisplayVideo.pause();
                    } catch (pauseError) {
                        // Ignore pause failures.
                    }
                    screenshotDisplayVideo.srcObject = null;
                }
                screenshotDisplayVideo = null;
            }

            function waitForVideoPlayable(videoElement) {
                return new Promise(function (resolve, reject) {
                    if (!videoElement) {
                        reject(new Error('Display stream video is unavailable.'));
                        return;
                    }
                    const handleReady = function () {
                        cleanup();
                        resolve(true);
                    };
                    const handleError = function () {
                        cleanup();
                        reject(new Error('Unable to prepare display stream for screenshot.'));
                    };
                    const cleanup = function () {
                        videoElement.removeEventListener('loadedmetadata', handleReady);
                        videoElement.removeEventListener('canplay', handleReady);
                        videoElement.removeEventListener('error', handleError);
                    };
                    videoElement.addEventListener('loadedmetadata', handleReady);
                    videoElement.addEventListener('canplay', handleReady);
                    videoElement.addEventListener('error', handleError);
                    setTimeout(function () {
                        if (videoElement.videoWidth > 0 && videoElement.videoHeight > 0) {
                            cleanup();
                            resolve(true);
                        }
                    }, 140);
                });
            }

            async function ensureScreenshotDisplayStream(options) {
                const settings = options && typeof options === 'object' ? options : {};
                const allowPrompt = settings.allowPrompt !== false;

                if (!screenshotDisplayCaptureSupported()) {
                    return null;
                }
                if (hasActiveScreenshotDisplayStream()) {
                    return screenshotDisplayStream;
                }
                if (!allowPrompt) {
                    return null;
                }

                const requestedStream = await navigator.mediaDevices.getDisplayMedia({
                    video: {
                        displaySurface: 'browser',
                        cursor: 'always',
                        frameRate: { ideal: 30, max: 30 },
                    },
                    audio: false,
                    preferCurrentTab: true,
                    selfBrowserSurface: 'include',
                    surfaceSwitching: 'include',
                });
                if (!requestedStream) {
                    return null;
                }

                screenshotDisplayStream = requestedStream;
                const videoTracks = requestedStream.getVideoTracks ? requestedStream.getVideoTracks() : [];
                if (videoTracks && videoTracks[0]) {
                    videoTracks[0].addEventListener('ended', function () {
                        stopScreenshotDisplayStream();
                    });
                }
                return requestedStream;
            }

            async function capturePageScreenshotBlobFromDisplay(options) {
                const settings = options && typeof options === 'object' ? options : {};
                const allowPrompt = settings.allowPrompt !== false;
                const displayStream = await ensureScreenshotDisplayStream({
                    allowPrompt: allowPrompt,
                });
                if (!displayStream) {
                    return null;
                }

                if (!screenshotDisplayVideo) {
                    screenshotDisplayVideo = document.createElement('video');
                    screenshotDisplayVideo.muted = true;
                    screenshotDisplayVideo.autoplay = true;
                    screenshotDisplayVideo.playsInline = true;
                    screenshotDisplayVideo.style.position = 'fixed';
                    screenshotDisplayVideo.style.left = '-99999px';
                    screenshotDisplayVideo.style.top = '-99999px';
                    screenshotDisplayVideo.style.width = '1px';
                    screenshotDisplayVideo.style.height = '1px';
                    screenshotDisplayVideo.setAttribute('aria-hidden', 'true');
                    document.body.appendChild(screenshotDisplayVideo);
                }

                screenshotDisplayVideo.srcObject = displayStream;
                try {
                    await screenshotDisplayVideo.play();
                } catch (playError) {
                    // Continue and let readiness check decide if frame is available.
                }
                await waitForVideoPlayable(screenshotDisplayVideo);

                const fallbackWidth = Math.max(window.innerWidth || 0, 1);
                const fallbackHeight = Math.max(window.innerHeight || 0, 1);
                const captureWidth = Math.max(Number(screenshotDisplayVideo.videoWidth || 0), fallbackWidth);
                const captureHeight = Math.max(Number(screenshotDisplayVideo.videoHeight || 0), fallbackHeight);
                const frameCanvas = document.createElement('canvas');
                frameCanvas.width = captureWidth;
                frameCanvas.height = captureHeight;
                const context = frameCanvas.getContext('2d');
                if (!context) {
                    throw new Error('Unable to capture screenshot frame context.');
                }
                context.drawImage(screenshotDisplayVideo, 0, 0, captureWidth, captureHeight);
                const frameBlob = await new Promise(function (resolve, reject) {
                    frameCanvas.toBlob(function (blob) {
                        if (!blob) {
                            reject(new Error('Unable to build display screenshot image.'));
                            return;
                        }
                        resolve(blob);
                    }, 'image/png');
                });
                return frameBlob || null;
            }

            function ensureHtml2CanvasLoaded() {
                if (typeof window.html2canvas === 'function') {
                    return Promise.resolve(window.html2canvas);
                }
                if (html2CanvasLoaderPromise) {
                    return html2CanvasLoaderPromise;
                }

                html2CanvasLoaderPromise = new Promise(function (resolve, reject) {
                    const existingScript = document.querySelector('script[data-DTMIS-html2canvas="true"]');
                    if (existingScript) {
                        existingScript.addEventListener('load', function () {
                            if (typeof window.html2canvas === 'function') {
                                resolve(window.html2canvas);
                            } else {
                                reject(new Error('Screenshot library loaded but unavailable.'));
                            }
                        }, { once: true });
                        existingScript.addEventListener('error', function () {
                            reject(new Error('Unable to load screenshot library.'));
                        }, { once: true });
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/html2canvas-pro@1.5.13/dist/html2canvas-pro.min.js';
                    script.async = true;
                    script.defer = true;
                    script.dataset.DTMISHtml2canvas = 'true';
                    script.onload = function () {
                        if (typeof window.html2canvas === 'function') {
                            resolve(window.html2canvas);
                        } else {
                            reject(new Error('Screenshot library loaded but unavailable.'));
                        }
                    };
                    script.onerror = function () {
                        reject(new Error('Unable to load screenshot library. Check internet access.'));
                    };
                    document.head.appendChild(script);
                }).catch(function (error) {
                    html2CanvasLoaderPromise = null;
                    throw error;
                });

                return html2CanvasLoaderPromise;
            }

            function ensureHtmlToImageLoaded() {
                if (window.htmlToImage && typeof window.htmlToImage.toBlob === 'function') {
                    return Promise.resolve(window.htmlToImage);
                }
                if (htmlToImageLoaderPromise) {
                    return htmlToImageLoaderPromise;
                }

                htmlToImageLoaderPromise = new Promise(function (resolve, reject) {
                    const existingScript = document.querySelector('script[data-DTMIS-htmltoimage="true"]');
                    if (existingScript) {
                        existingScript.addEventListener('load', function () {
                            if (window.htmlToImage && typeof window.htmlToImage.toBlob === 'function') {
                                resolve(window.htmlToImage);
                            } else {
                                reject(new Error('Fallback screenshot library loaded but unavailable.'));
                            }
                        }, { once: true });
                        existingScript.addEventListener('error', function () {
                            reject(new Error('Unable to load fallback screenshot library.'));
                        }, { once: true });
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/html-to-image@1.11.13/dist/html-to-image.min.js';
                    script.async = true;
                    script.defer = true;
                    script.dataset.DTMISHtmltoimage = 'true';
                    script.onload = function () {
                        if (window.htmlToImage && typeof window.htmlToImage.toBlob === 'function') {
                            resolve(window.htmlToImage);
                        } else {
                            reject(new Error('Fallback screenshot library loaded but unavailable.'));
                        }
                    };
                    script.onerror = function () {
                        reject(new Error('Unable to load fallback screenshot library. Check internet access.'));
                    };
                    document.head.appendChild(script);
                }).catch(function (error) {
                    htmlToImageLoaderPromise = null;
                    throw error;
                });

                return htmlToImageLoaderPromise;
            }

            async function capturePageScreenshotBlobFallback() {
                await ensureHtmlToImageLoaded();
                if (!window.htmlToImage || typeof window.htmlToImage.toBlob !== 'function') {
                    throw new Error('Fallback screenshot library is unavailable.');
                }
                if (document.fonts && typeof document.fonts.ready === 'object') {
                    try {
                        await document.fonts.ready;
                    } catch (fontError) {
                        // Ignore font readiness errors and proceed with capture.
                    }
                }

                const captureRoot = document.body || document.documentElement;
                const documentElement = document.documentElement || captureRoot;
                const bodyElement = document.body || captureRoot;
                const captureWidth = Math.max(
                    Number(documentElement.scrollWidth || 0),
                    Number(bodyElement.scrollWidth || 0),
                    Number(window.innerWidth || 0)
                );
                const captureHeight = Math.max(
                    Number(documentElement.scrollHeight || 0),
                    Number(bodyElement.scrollHeight || 0),
                    Number(window.innerHeight || 0)
                );
                const captureScale = Math.max(1, Math.min(2.5, Number(window.devicePixelRatio || 1)));
                let fontEmbedCss = '';
                if (typeof window.htmlToImage.getFontEmbedCSS === 'function') {
                    try {
                        fontEmbedCss = String(await window.htmlToImage.getFontEmbedCSS(captureRoot) || '');
                    } catch (fontEmbedError) {
                        fontEmbedCss = '';
                    }
                }

                const screenshotBlob = await window.htmlToImage.toBlob(captureRoot, {
                    cacheBust: true,
                    backgroundColor: '#0b1324',
                    pixelRatio: captureScale,
                    width: captureWidth,
                    height: captureHeight,
                    fontEmbedCSS: fontEmbedCss !== '' ? fontEmbedCss : undefined,
                });
                if (!screenshotBlob) {
                    throw new Error('Fallback screenshot capture returned an empty image.');
                }
                return screenshotBlob;
            }

            async function capturePageScreenshotBlob(options) {
                const settings = options && typeof options === 'object' ? options : {};
                const allowDisplayPrompt = settings.allowDisplayPrompt !== false;
                try {
                    const displayBlob = await capturePageScreenshotBlobFromDisplay({
                        allowPrompt: allowDisplayPrompt,
                    });
                    if (displayBlob) {
                        return displayBlob;
                    }
                } catch (displayCaptureError) {
                    if (window && window.console && typeof window.console.warn === 'function') {
                        window.console.warn('Display capture fallback to DOM renderer:', displayCaptureError);
                    }
                }

                const captureRoot = document.body || document.documentElement;
                const scrollX = Number(window.scrollX || window.pageXOffset || 0);
                const scrollY = Number(window.scrollY || window.pageYOffset || 0);
                const captureScale = Math.max(1, Math.min(2.5, Number(window.devicePixelRatio || 1)));
                const captureWidth = Math.max(captureRoot.scrollWidth || 0, window.innerWidth || 0);
                const captureHeight = Math.max(captureRoot.scrollHeight || 0, window.innerHeight || 0);
                try {
                    if (document.fonts && typeof document.fonts.ready === 'object') {
                        try {
                            await document.fonts.ready;
                        } catch (fontError) {
                            // Ignore font readiness errors and proceed with capture.
                        }
                    }
                    await ensureHtml2CanvasLoaded();
                    let captureCanvas = null;
                    try {
                        captureCanvas = await window.html2canvas(captureRoot, {
                            useCORS: true,
                            allowTaint: false,
                            backgroundColor: '#0b1324',
                            scale: captureScale,
                            windowWidth: captureWidth,
                            windowHeight: captureHeight,
                            scrollX: -scrollX,
                            scrollY: -scrollY,
                            foreignObjectRendering: true,
                            removeContainer: true,
                            logging: false,
                        });
                    } catch (foreignObjectError) {
                        captureCanvas = await window.html2canvas(captureRoot, {
                            useCORS: true,
                            allowTaint: false,
                            backgroundColor: '#0b1324',
                            scale: captureScale,
                            windowWidth: captureWidth,
                            windowHeight: captureHeight,
                            scrollX: -scrollX,
                            scrollY: -scrollY,
                            removeContainer: true,
                            logging: false,
                        });
                    }

                    return await new Promise(function (resolve, reject) {
                        captureCanvas.toBlob(function (blob) {
                            if (!blob) {
                                reject(new Error('Unable to build screenshot image.'));
                                return;
                            }
                            resolve(blob);
                        }, 'image/png');
                    });
                } catch (primaryError) {
                    try {
                        return await capturePageScreenshotBlobFallback();
                    } catch (fallbackError) {
                        const primaryMessage = primaryError && primaryError.message ? String(primaryError.message) : '';
                        const fallbackMessage = fallbackError && fallbackError.message ? String(fallbackError.message) : '';
                        if (primaryMessage !== '' && fallbackMessage !== '' && fallbackMessage !== primaryMessage) {
                            throw new Error(primaryMessage + ' Fallback capture failed: ' + fallbackMessage);
                        }
                        throw fallbackError || primaryError || new Error('Unable to capture screenshot right now.');
                    }
                }
            }

            function downloadScreenshotBlob(blob, fileName) {
                const objectUrl = URL.createObjectURL(blob);
                const anchor = document.createElement('a');
                anchor.href = objectUrl;
                anchor.download = fileName;
                document.body.appendChild(anchor);
                anchor.click();
                anchor.remove();
                setTimeout(function () {
                    URL.revokeObjectURL(objectUrl);
                }, 1500);
            }

            async function saveScreenshotBlobToDirectory(blob, fileName, directoryHandle) {
                if (!directoryHandle || typeof directoryHandle.getFileHandle !== 'function') {
                    throw new Error('Screenshot folder is not available.');
                }
                const fileHandle = await directoryHandle.getFileHandle(fileName, { create: true });
                const writable = await fileHandle.createWritable();
                try {
                    await writable.write(blob);
                    await writable.close();
                } catch (error) {
                    try {
                        await writable.abort();
                    } catch (abortError) {
                        // Ignore abort errors when write fails.
                    }
                    throw error;
                }
            }

            async function clearStoredScreenshotDirectoryHandle() {
                screenshotDirectoryHandleCache = null;
                if (!screenshotDirectoryPickerSupported()) {
                    return;
                }
                try {
                    await screenshotPreferenceDelete(screenshotDirectoryHandleKey);
                } catch (error) {
                    // Ignore preference clear errors.
                }
            }

            function getReadableScreenshotErrorMessage(error) {
                const rawMessage = error && error.message
                    ? String(error.message)
                    : (typeof error === 'string' ? error : (error ? String(error) : ''));
                if (rawMessage === '') {
                    return 'Unable to capture screenshot right now.';
                }
                if (/unsupported color function|Attempting to parse/i.test(rawMessage)) {
                    return 'Screenshot capture failed due to unsupported page styling in this browser. Please refresh and try again.';
                }
                return rawMessage;
            }

            function readAutoScreenshotPreference() {
                screenshotAutoCaptureEnabled = String(localStorage.getItem(screenshotAutoCaptureKey) || '') === '1';
            }

            function writeAutoScreenshotPreference(enabled) {
                screenshotAutoCaptureEnabled = !!enabled;
                if (screenshotAutoCaptureEnabled) {
                    localStorage.setItem(screenshotAutoCaptureKey, '1');
                } else {
                    localStorage.removeItem(screenshotAutoCaptureKey);
                }
            }

            function syncAutoScreenshotToggleUi() {
                if (!toggleAutoScreenshotBtn) {
                    return;
                }
                toggleAutoScreenshotBtn.textContent = screenshotAutoCaptureEnabled ? 'Auto Screenshot: On' : 'Auto Screenshot: Off';
                toggleAutoScreenshotBtn.setAttribute('aria-pressed', screenshotAutoCaptureEnabled ? 'true' : 'false');
            }

            async function captureAndSaveCurrentPageScreenshot(options) {
                const settings = options && typeof options === 'object' ? options : {};
                const forceDirectoryPrompt = !!settings.forceDirectoryPrompt;
                const silentSuccess = !!settings.silentSuccess;
                const silentErrors = !!settings.silentErrors;
                const skipPromptOnMissingDirectory = !!settings.skipPromptOnMissingDirectory;

                if (screenshotCaptureInProgress) {
                    return false;
                }
                screenshotCaptureInProgress = true;

                try {
                    let directoryHandle = null;
                    if (screenshotDirectoryPickerSupported()) {
                        directoryHandle = await pickScreenshotDirectory({
                            forcePrompt: forceDirectoryPrompt,
                            promptIfMissing: !skipPromptOnMissingDirectory,
                        });
                        if (!directoryHandle && skipPromptOnMissingDirectory) {
                            return false;
                        }
                        if (!directoryHandle && !skipPromptOnMissingDirectory) {
                            if (!silentErrors) {
                                showAlertDialog('Please set a screenshot folder first.');
                            }
                            return false;
                        }
                    }

                    const screenshotBlob = await capturePageScreenshotBlob({
                        allowDisplayPrompt: !silentSuccess && !silentErrors,
                    });
                    const screenshotFileName = buildPageScreenshotFileName();
                    if (directoryHandle) {
                        const granted = await ensureScreenshotDirectoryPermission(directoryHandle);
                        if (!granted) {
                            await clearStoredScreenshotDirectoryHandle();
                            if (!silentErrors) {
                                showAlertDialog('Screenshot folder access was denied. Please choose the folder again.');
                            }
                            return false;
                        }
                        await saveScreenshotBlobToDirectory(screenshotBlob, screenshotFileName, directoryHandle);
                        if (!silentSuccess) {
                            showAlertDialog('Screenshot saved to your configured folder as "' + screenshotFileName + '".');
                        }
                    } else {
                        downloadScreenshotBlob(screenshotBlob, screenshotFileName);
                        if (!silentSuccess) {
                            showAlertDialog('Screenshot downloaded as "' + screenshotFileName + '".');
                        }
                    }
                    return true;
                } catch (error) {
                    const errorName = error && error.name ? String(error.name) : '';
                    if (errorName === 'NotAllowedError' || errorName === 'SecurityError') {
                        await clearStoredScreenshotDirectoryHandle();
                    }
                    if (window && window.console && typeof window.console.error === 'function') {
                        window.console.error('Screenshot capture failure:', error);
                    }
                    if (!silentErrors) {
                        showAlertDialog(getReadableScreenshotErrorMessage(error));
                    }
                    return false;
                } finally {
                    screenshotCaptureInProgress = false;
                }
            }

            async function bindScreenshotTools() {
                readAutoScreenshotPreference();
                syncAutoScreenshotToggleUi();

                if (setScreenshotDirectoryBtn) {
                    setScreenshotDirectoryBtn.addEventListener('click', function () {
                        if (!screenshotDirectoryPickerSupported()) {
                            showAlertDialog('This browser does not support persistent folder access. Screenshots will download to your default Downloads folder.');
                            return;
                        }
                        pickScreenshotDirectory({
                            forcePrompt: true,
                            promptIfMissing: true,
                        }).then(function (directoryHandle) {
                            if (directoryHandle) {
                                showAlertDialog('Screenshot folder saved. Future captures will reuse this location.');
                                return;
                            }
                            showAlertDialog('No screenshot folder selected. You can set it again anytime.');
                        }).catch(function (error) {
                            showAlertDialog(error && error.message ? error.message : 'Unable to save screenshot folder.');
                        });
                    });
                }

                if (capturePageScreenshotBtn) {
                    capturePageScreenshotBtn.addEventListener('click', function () {
                        captureAndSaveCurrentPageScreenshot({
                            forceDirectoryPrompt: false,
                            silentSuccess: false,
                            silentErrors: false,
                            skipPromptOnMissingDirectory: false,
                        });
                    });
                }
                if (capturePageScreenshotHeaderBtn) {
                    capturePageScreenshotHeaderBtn.addEventListener('click', function () {
                        captureAndSaveCurrentPageScreenshot({
                            forceDirectoryPrompt: false,
                            silentSuccess: false,
                            silentErrors: false,
                            skipPromptOnMissingDirectory: false,
                        });
                    });
                }

                if (toggleAutoScreenshotBtn) {
                    toggleAutoScreenshotBtn.addEventListener('click', function () {
                        const nextState = !screenshotAutoCaptureEnabled;
                        writeAutoScreenshotPreference(nextState);
                        syncAutoScreenshotToggleUi();
                        showAlertDialog(nextState
                            ? 'Auto screenshot is enabled. This page will capture once after loading and save to your configured folder.'
                            : 'Auto screenshot is disabled.'
                        );
                    });
                }
            }

            function runAutoScreenshotOnceAfterLoad() {
                if (!screenshotAutoCaptureEnabled || screenshotAutoCaptureTriggered) {
                    return;
                }
                screenshotAutoCaptureTriggered = true;
                setTimeout(function () {
                    captureAndSaveCurrentPageScreenshot({
                        forceDirectoryPrompt: false,
                        silentSuccess: true,
                        silentErrors: true,
                        skipPromptOnMissingDirectory: true,
                    });
                }, 850);
            }

            window.addEventListener('beforeunload', function () {
                stopScreenshotDisplayStream();
            });

            function getSelectedQueueRowContext() {
                if (selectedQueueTrackingId === '') {
                    return null;
                }
                const row = findQueueRowByTrackingId(selectedQueueTrackingId);
                if (!row) {
                    return null;
                }
                const trackingId = String(row.getAttribute('data-tracking-id') || '').trim();
                const documentId = parseInt(String(row.getAttribute('data-document-id') || '0'), 10);
                if (trackingId === '' && (!Number.isFinite(documentId) || documentId <= 0)) {
                    return null;
                }
                return {
                    row: row,
                    trackingId: trackingId,
                    documentId: Number.isFinite(documentId) ? documentId : 0,
                };
            }

            function setDocumentDetailsLoadingState(message, isError) {
                if (!documentDetailsLoading || !documentDetailsContent) {
                    return;
                }
                documentDetailsLoading.hidden = false;
                documentDetailsLoading.textContent = String(message || 'Loading document details...');
                documentDetailsLoading.classList.toggle('error', !!isError);
                documentDetailsContent.hidden = true;
            }

            function appendDocumentDetailItem(container, label, value, isFullWidth) {
                if (!container) {
                    return;
                }
                const item = document.createElement('div');
                item.className = 'details-item' + (isFullWidth ? ' details-item-full' : '');

                const labelNode = document.createElement('span');
                labelNode.className = 'details-item-label';
                labelNode.textContent = String(label || '').trim();
                item.appendChild(labelNode);

                const valueNode = document.createElement('p');
                valueNode.className = 'details-item-value';
                const safeValue = String(value || '').trim();
                valueNode.textContent = safeValue !== '' ? safeValue : '-';
                item.appendChild(valueNode);

                container.appendChild(item);
            }

            function resetDocumentDetailsPreview(message) {
                if (documentDetailsPreviewFrame) {
                    documentDetailsPreviewFrame.hidden = true;
                    documentDetailsPreviewFrame.removeAttribute('src');
                }
                if (documentDetailsPreviewImage) {
                    documentDetailsPreviewImage.hidden = true;
                    documentDetailsPreviewImage.removeAttribute('src');
                }
                if (documentDetailsPreviewEmpty) {
                    documentDetailsPreviewEmpty.hidden = false;
                    documentDetailsPreviewEmpty.textContent = String(message || 'Select a file to preview.');
                }
                if (documentDetailsOpenAttachment) {
                    documentDetailsOpenAttachment.hidden = true;
                    documentDetailsOpenAttachment.removeAttribute('href');
                }
            }

            function renderDocumentAttachmentPreview(attachment) {
                if (!attachment || typeof attachment !== 'object') {
                    resetDocumentDetailsPreview('No preview available.');
                    return;
                }

                const fileUrl = String(attachment.file_url || '').trim();
                const previewType = normalizeLabelKey(String(attachment.preview_type || 'none'));
                const fileName = String(attachment.file_name || 'Attachment').trim() || 'Attachment';

                if (fileUrl === '') {
                    resetDocumentDetailsPreview('File link is unavailable for this attachment.');
                    return;
                }

                if (documentDetailsOpenAttachment) {
                    documentDetailsOpenAttachment.href = fileUrl;
                    documentDetailsOpenAttachment.hidden = false;
                    documentDetailsOpenAttachment.textContent = 'Open File';
                }

                if (previewType === 'pdf' && documentDetailsPreviewFrame) {
                    if (documentDetailsPreviewEmpty) {
                        documentDetailsPreviewEmpty.hidden = true;
                    }
                    if (documentDetailsPreviewImage) {
                        documentDetailsPreviewImage.hidden = true;
                        documentDetailsPreviewImage.removeAttribute('src');
                    }
                    documentDetailsPreviewFrame.hidden = false;
                    documentDetailsPreviewFrame.src = fileUrl;
                    return;
                }

                if (previewType === 'image' && documentDetailsPreviewImage) {
                    if (documentDetailsPreviewEmpty) {
                        documentDetailsPreviewEmpty.hidden = true;
                    }
                    if (documentDetailsPreviewFrame) {
                        documentDetailsPreviewFrame.hidden = true;
                        documentDetailsPreviewFrame.removeAttribute('src');
                    }
                    documentDetailsPreviewImage.hidden = false;
                    documentDetailsPreviewImage.src = fileUrl;
                    documentDetailsPreviewImage.alt = fileName;
                    return;
                }

                resetDocumentDetailsPreview('Preview is not available for this file type. Use Open File.');
            }

            function normalizeDocumentDetailsAttachmentFilter(value) {
                const normalized = normalizeLabelKey(String(value || ''));
                if (normalized === 'endorsement' || normalized === 'endorsement_letter') {
                    return 'endorsement';
                }
                if (normalized === 'response') {
                    return 'response';
                }
                return 'original';
            }

            function humanizeAttachmentRoleLabel(value) {
                const raw = String(value || '').trim();
                if (raw !== '') {
                    if (raw.indexOf('_') !== -1) {
                        return raw
                            .toLowerCase()
                            .split('_')
                            .filter(function (part) { return String(part || '').trim() !== ''; })
                            .map(function (part) {
                                return part.charAt(0).toUpperCase() + part.slice(1);
                            })
                            .join(' ');
                    }
                    return raw;
                }
                return 'Unknown Role';
            }

            function classifyDocumentAttachmentSource(attachment) {
                if (!attachment || typeof attachment !== 'object') {
                    return {
                        scope_key: 'other',
                        scope_label: 'Other',
                        role_label: 'Unknown Role',
                        option_key: 'other|unknown-role',
                        option_label: 'Other - Unknown Role',
                    };
                }

                const roleKey = normalizeLabelKey(String(
                    attachment.uploaded_by_role_key || attachment.uploaded_by_role || ''
                ));
                const officeLevelKey = normalizeLabelKey(String(attachment.uploaded_by_office_level || ''));
                let scopeKey = 'other';
                let scopeLabel = 'Other';

                if (
                    roleKey === 'ored'
                    || roleKey === 'ard_ts'
                    || roleKey === 'ard_ms'
                    || roleKey === 'division_chief'
                    || roleKey === 'section_staff'
                    || roleKey === 'section'
                    || roleKey === 'records_unit'
                    || officeLevelKey === 'division'
                    || officeLevelKey === 'section'
                ) {
                    scopeKey = 'regional';
                    scopeLabel = 'Regional';
                } else if (roleKey.indexOf('cenro_') === 0 || officeLevelKey.indexOf('cenro_') === 0) {
                    scopeKey = 'cenro';
                    scopeLabel = 'CENRO';
                } else if (roleKey.indexOf('penro_') === 0 || officeLevelKey.indexOf('penro_') === 0) {
                    scopeKey = 'penro';
                    scopeLabel = 'PENRO';
                } else if (
                    roleKey === 'pamo_admin'
                    || roleKey === 'pasu_officer'
                    || roleKey === 'pamo_unit'
                    || officeLevelKey === 'pamo_admin'
                    || officeLevelKey === 'pasu_officer'
                    || officeLevelKey === 'pamo_unit'
                ) {
                    scopeKey = 'pamo';
                    scopeLabel = 'PAMO/PASU';
                }

                const roleLabel = humanizeAttachmentRoleLabel(
                    attachment.uploaded_by_role || attachment.uploaded_by_office_level || ''
                );

                return {
                    scope_key: scopeKey,
                    scope_label: scopeLabel,
                    role_label: roleLabel,
                    option_key: scopeKey + '|' + normalizeLabelKey(roleLabel),
                    option_label: scopeLabel + ' - ' + roleLabel,
                };
            }

            function classifyDocumentAttachmentCategory(attachment) {
                if (!attachment || typeof attachment !== 'object') {
                    return 'original';
                }

                const routeAttachmentCategory = normalizeLabelKey(String(attachment.route_attachment_category || ''));
                if (routeAttachmentCategory === 'prepared_response') {
                    return 'response';
                }
                if (routeAttachmentCategory === 'endorsement' || routeAttachmentCategory === 'endorsement_letter') {
                    return 'endorsement';
                }

                const fileNameKey = normalizeLabelKey(String(attachment.file_name || ''));
                const uploadedByRoleKey = normalizeLabelKey(String(
                    attachment.uploaded_by_role_key || attachment.uploaded_by_role || ''
                ));
                const uploadedByUserId = Number(attachment.uploaded_by_user_id || 0);
                const creatorUserId = Number(documentDetailsCreatorUserId || 0);
                const isCreatorUpload = Number.isFinite(uploadedByUserId)
                    && uploadedByUserId > 0
                    && Number.isFinite(creatorUserId)
                    && creatorUserId > 0
                    && uploadedByUserId === creatorUserId;

                const isPreparedResponse = attachment.is_prepared_response === true
                    || Number(attachment.is_prepared_response || 0) === 1
                    || uploadedByRoleKey === 'division_chief'
                    || uploadedByRoleKey === 'section_staff'
                    || fileNameKey.indexOf('prepared response') !== -1
                    || fileNameKey.indexOf('prepared_response') !== -1
                    || fileNameKey.indexOf('prepared-response') !== -1
                    || fileNameKey.indexOf('prepared_of_response') !== -1;
                if (isPreparedResponse) {
                    return 'response';
                }

                const looksEndorsementByName = fileNameKey.indexOf('endorsement') !== -1
                    || fileNameKey.indexOf('endorse') !== -1;
                const isPenroCenroPasuUpload = uploadedByRoleKey === 'penro'
                    || uploadedByRoleKey === 'cenro'
                    || uploadedByRoleKey === 'pasu'
                    || uploadedByRoleKey === 'penro_admin_record'
                    || uploadedByRoleKey === 'cenro_admin_record'
                    || uploadedByRoleKey === 'pamo_admin';
                if (looksEndorsementByName || (isPenroCenroPasuUpload && !isCreatorUpload)) {
                    return 'endorsement';
                }

                return 'original';
            }

            function updateDocumentAttachmentFilterButtons(categoryCounts) {
                if (!documentDetailsAttachmentFilterWrap) {
                    return;
                }

                const counts = categoryCounts && typeof categoryCounts === 'object' ? categoryCounts : {};
                const hasAnyAttachment = Number(counts.original || 0) > 0
                    || Number(counts.endorsement || 0) > 0
                    || Number(counts.response || 0) > 0;
                documentDetailsAttachmentFilterWrap.hidden = !hasAnyAttachment;
                if (!hasAnyAttachment) {
                    return;
                }

                documentDetailsAttachmentFilterButtons.forEach(function (button) {
                    const mode = normalizeDocumentDetailsAttachmentFilter(button.dataset.detailsAttachmentFilter || '');
                    const count = Number(counts[mode] || 0);
                    button.disabled = count < 1;
                    button.classList.toggle('is-active', mode === documentDetailsAttachmentFilter);
                });
            }

            function renderDocumentAttachments(attachments, documentData) {
                const doc = documentData && typeof documentData === 'object' ? documentData : {};
                documentDetailsCreatorUserId = Number(doc.created_by_user_id || 0);
                documentDetailsAllAttachments = Array.isArray(attachments)
                    ? attachments.map(function (attachment) {
                        const safeAttachment = attachment && typeof attachment === 'object'
                            ? Object.assign({}, attachment)
                            : {};
                        safeAttachment.attachment_category = classifyDocumentAttachmentCategory(safeAttachment);
                        safeAttachment.attachment_source = classifyDocumentAttachmentSource(safeAttachment);
                        return safeAttachment;
                    })
                    : [];

                const categoryCounts = { original: 0, endorsement: 0, response: 0 };
                documentDetailsAllAttachments.forEach(function (attachment) {
                    const category = normalizeDocumentDetailsAttachmentFilter(attachment.attachment_category || 'original');
                    categoryCounts[category] = Number(categoryCounts[category] || 0) + 1;
                });

                if (Number(categoryCounts[documentDetailsAttachmentFilter] || 0) < 1) {
                    if (categoryCounts.original > 0) {
                        documentDetailsAttachmentFilter = 'original';
                    } else if (categoryCounts.endorsement > 0) {
                        documentDetailsAttachmentFilter = 'endorsement';
                    } else if (categoryCounts.response > 0) {
                        documentDetailsAttachmentFilter = 'response';
                    } else {
                        documentDetailsAttachmentFilter = 'original';
                    }
                }
                updateDocumentAttachmentFilterButtons(categoryCounts);

                const categoryAttachments = documentDetailsAllAttachments.filter(function (attachment) {
                    const category = normalizeDocumentDetailsAttachmentFilter(attachment.attachment_category || 'original');
                    return category === documentDetailsAttachmentFilter;
                });

                const sourceOptions = [];
                const sourceOptionMap = {};
                categoryAttachments.forEach(function (attachment) {
                    const source = attachment && typeof attachment === 'object' && attachment.attachment_source
                        ? attachment.attachment_source
                        : classifyDocumentAttachmentSource(attachment);
                    const optionKey = String(source.option_key || 'other|unknown-role');
                    if (sourceOptionMap[optionKey]) {
                        return;
                    }
                    sourceOptionMap[optionKey] = true;
                    sourceOptions.push({
                        value: optionKey,
                        label: String(source.option_label || 'Other - Unknown Role'),
                    });
                });

                const shouldShowSourceFilter = documentDetailsAttachmentFilter !== 'original' && sourceOptions.length > 0;
                if (documentDetailsAttachmentSourceFilterWrap) {
                    documentDetailsAttachmentSourceFilterWrap.hidden = !shouldShowSourceFilter;
                }
                if (documentDetailsAttachmentSourceFilter) {
                    documentDetailsAttachmentSourceFilter.innerHTML = '';
                    const allOption = document.createElement('option');
                    allOption.value = 'all';
                    allOption.textContent = 'All sources';
                    documentDetailsAttachmentSourceFilter.appendChild(allOption);
                    sourceOptions.forEach(function (optionItem) {
                        const optionNode = document.createElement('option');
                        optionNode.value = optionItem.value;
                        optionNode.textContent = optionItem.label;
                        documentDetailsAttachmentSourceFilter.appendChild(optionNode);
                    });
                    const hasRequestedSource = documentDetailsAttachmentSourceFilterValue === 'all'
                        || sourceOptions.some(function (optionItem) {
                            return optionItem.value === documentDetailsAttachmentSourceFilterValue;
                        });
                    if (!hasRequestedSource) {
                        documentDetailsAttachmentSourceFilterValue = 'all';
                    }
                    documentDetailsAttachmentSourceFilter.value = documentDetailsAttachmentSourceFilterValue;
                    documentDetailsAttachmentSourceFilter.disabled = !shouldShowSourceFilter || sourceOptions.length < 2;
                }

                documentDetailsAttachments = categoryAttachments.filter(function (attachment) {
                    const category = normalizeDocumentDetailsAttachmentFilter(attachment.attachment_category || 'original');
                    if (category !== documentDetailsAttachmentFilter) {
                        return false;
                    }
                    if (!shouldShowSourceFilter || documentDetailsAttachmentSourceFilterValue === 'all') {
                        return true;
                    }
                    const source = attachment && typeof attachment === 'object' && attachment.attachment_source
                        ? attachment.attachment_source
                        : classifyDocumentAttachmentSource(attachment);
                    return String(source.option_key || '') === documentDetailsAttachmentSourceFilterValue;
                });

                if (!documentDetailsAttachmentSelect) {
                    return;
                }

                documentDetailsAttachmentSelect.innerHTML = '';

                if (documentDetailsAllAttachments.length === 0) {
                    const emptyOption = document.createElement('option');
                    emptyOption.value = '';
                    emptyOption.textContent = 'No attachments uploaded yet';
                    documentDetailsAttachmentSelect.appendChild(emptyOption);
                    documentDetailsAttachmentSelect.disabled = true;
                    resetDocumentDetailsPreview('No uploaded file found for this document.');
                    return;
                }

                if (documentDetailsAttachments.length === 0) {
                    const emptyCategoryOption = document.createElement('option');
                    emptyCategoryOption.value = '';
                    const categoryLabel = documentDetailsAttachmentFilter === 'response'
                        ? 'Response'
                        : (documentDetailsAttachmentFilter === 'endorsement' ? 'Endorsement Letter' : 'Original Attachment');
                    emptyCategoryOption.textContent = 'No ' + categoryLabel + ' files found';
                    documentDetailsAttachmentSelect.appendChild(emptyCategoryOption);
                    documentDetailsAttachmentSelect.disabled = true;
                    resetDocumentDetailsPreview('No file found in this category.');
                    return;
                }

                let preferredIndex = 0;
                documentDetailsAttachments.forEach(function (attachment, index) {
                    const option = document.createElement('option');
                    option.value = String(index);
                    const version = String(attachment.version_number || '').trim();
                    const fileName = String(attachment.file_name || 'Attachment').trim() || 'Attachment';
                    const uploadedAt = String(attachment.uploaded_at || '').trim();
                    option.textContent = (version !== '' ? ('V' + version + ' - ') : '') + fileName + (uploadedAt !== '' ? (' (' + uploadedAt + ')') : '');
                    documentDetailsAttachmentSelect.appendChild(option);
                    const previewType = normalizeLabelKey(String(attachment.preview_type || 'none'));
                    const hasPreview = (previewType === 'pdf' || previewType === 'image') && String(attachment.file_url || '').trim() !== '';
                    if (hasPreview && preferredIndex === 0) {
                        preferredIndex = index;
                    }
                });

                documentDetailsAttachmentSelect.disabled = false;
                documentDetailsAttachmentSelect.value = String(preferredIndex);
                renderDocumentAttachmentPreview(documentDetailsAttachments[preferredIndex] || null);
            }

            function renderDocumentDetailsPayload(documentData, attachments) {
                if (!documentDetailsFields || !documentDetailsContent || !documentDetailsLoading) {
                    return;
                }

                documentDetailsFields.innerHTML = '';
                const doc = documentData && typeof documentData === 'object' ? documentData : {};

                appendDocumentDetailItem(documentDetailsFields, 'Tracking ID', doc.tracking_id || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Document Type', doc.document_type || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'ARTA Category', doc.arta_category || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Status', doc.status || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Source', doc.source_type || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Sender', doc.sender || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Originating Office / Entity', doc.originating_entity_name || doc.originating_office || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Routing Office', doc.routing_office || doc.originating_office || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Current Office', doc.current_office || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Pending Office', doc.pending_office || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Created By', doc.created_by || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Created At', doc.created_at || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Last Action', doc.last_action || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Last Action At', doc.last_action_at || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Last Actor', doc.last_actor || '-', false);
                appendDocumentDetailItem(documentDetailsFields, 'Attachment Count', doc.attachment_count || '0', false);
                appendDocumentDetailItem(documentDetailsFields, 'Subject / Text Details', doc.subject || '-', true);
                appendDocumentDetailItem(documentDetailsFields, 'Latest Remarks', doc.last_remarks || '-', true);

                renderDocumentAttachments(attachments, doc);
                documentDetailsLoading.hidden = true;
                documentDetailsLoading.classList.remove('error');
                documentDetailsContent.hidden = false;
            }

            function closeDocumentDetailsModal() {
                if (!documentDetailsModal) {
                    return;
                }
                documentDetailsModal.classList.remove('is-open');
                documentDetailsModal.hidden = true;
                document.body.classList.remove('modal-open');
                if (documentDetailsAbortController) {
                    documentDetailsAbortController.abort();
                    documentDetailsAbortController = null;
                }
                documentDetailsAttachments = [];
                documentDetailsAllAttachments = [];
                documentDetailsCreatorUserId = 0;
                documentDetailsAttachmentFilter = 'original';
                documentDetailsAttachmentSourceFilterValue = 'all';
                if (documentDetailsFields) {
                    documentDetailsFields.innerHTML = '';
                }
                if (documentDetailsAttachmentFilterWrap) {
                    documentDetailsAttachmentFilterWrap.hidden = true;
                }
                if (documentDetailsAttachmentSourceFilterWrap) {
                    documentDetailsAttachmentSourceFilterWrap.hidden = true;
                }
                if (documentDetailsAttachmentFilterButtons.length > 0) {
                    documentDetailsAttachmentFilterButtons.forEach(function (button) {
                        const mode = normalizeDocumentDetailsAttachmentFilter(button.dataset.detailsAttachmentFilter || '');
                        button.disabled = false;
                        button.classList.toggle('is-active', mode === 'original');
                    });
                }
                if (documentDetailsAttachmentSourceFilter) {
                    documentDetailsAttachmentSourceFilter.innerHTML = '<option value="all">All sources</option>';
                    documentDetailsAttachmentSourceFilter.value = 'all';
                    documentDetailsAttachmentSourceFilter.disabled = true;
                }
                if (documentDetailsAttachmentSelect) {
                    documentDetailsAttachmentSelect.innerHTML = '<option value="">No attachment selected</option>';
                    documentDetailsAttachmentSelect.disabled = true;
                }
                resetDocumentDetailsPreview('Select a file to preview.');
            }

            function openDocumentDetailsModal() {
                const context = getSelectedQueueRowContext();
                if (!context) {
                    showAlertDialog('Select a document row first.');
                    return;
                }
                if (!documentDetailsModal) {
                    showAlertDialog('Document details modal is not available on this page.');
                    return;
                }

                documentDetailsModal.hidden = false;
                documentDetailsModal.classList.add('is-open');
                document.body.classList.add('modal-open');
                if (documentDetailsMeta) {
                    documentDetailsMeta.textContent = context.trackingId !== ''
                        ? ('Tracking ID: ' + context.trackingId)
                        : 'Document details';
                }
                setDocumentDetailsLoadingState('Loading document details...', false);
                resetDocumentDetailsPreview('Loading file preview...');

                if (documentDetailsAbortController) {
                    documentDetailsAbortController.abort();
                }
                documentDetailsAbortController = new AbortController();

                const requestUrl = new URL(String(documentDetailsPath || ''), window.location.origin);
                if (context.documentId > 0) {
                    requestUrl.searchParams.set('document_id', String(context.documentId));
                }
                if (context.trackingId !== '') {
                    requestUrl.searchParams.set('tracking_id', context.trackingId);
                }
                requestUrl.searchParams.set('t', String(Date.now()));

                fetch(requestUrl.toString(), {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json' },
                    signal: documentDetailsAbortController.signal,
                }).then(function (response) {
                    return response.json().catch(function () {
                        return { ok: false, message: 'Unexpected server response.' };
                    }).then(function (json) {
                        if (!response.ok || !json || json.ok !== true) {
                            throw new Error(String(json && json.message ? json.message : 'Unable to load document details.'));
                        }
                        return json;
                    });
                }).then(function (payload) {
                    renderDocumentDetailsPayload(payload.document || {}, payload.attachments || []);
                }).catch(function (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                    setDocumentDetailsLoadingState(String(error && error.message ? error.message : 'Unable to load document details.'), true);
                    resetDocumentDetailsPreview('Preview unavailable.');
                }).finally(function () {
                    documentDetailsAbortController = null;
                });
            }

            function bindDocumentDetailsModal() {
                if (!documentDetailsModal) {
                    return;
                }
                documentDetailsCloseButtons.forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        closeDocumentDetailsModal();
                    });
                });
                if (documentDetailsAttachmentSelect) {
                    documentDetailsAttachmentSelect.addEventListener('change', function () {
                        const selectedIndex = parseInt(String(documentDetailsAttachmentSelect.value || '0'), 10);
                        if (!Number.isFinite(selectedIndex) || selectedIndex < 0 || selectedIndex >= documentDetailsAttachments.length) {
                            resetDocumentDetailsPreview('Select a file to preview.');
                            return;
                        }
                        renderDocumentAttachmentPreview(documentDetailsAttachments[selectedIndex]);
                    });
                }
                if (documentDetailsAttachmentFilterButtons.length > 0) {
                    documentDetailsAttachmentFilterButtons.forEach(function (button) {
                        button.addEventListener('click', function () {
                            const requestedFilter = normalizeDocumentDetailsAttachmentFilter(
                                button.dataset.detailsAttachmentFilter || ''
                            );
                            if (requestedFilter === documentDetailsAttachmentFilter) {
                                return;
                            }
                            documentDetailsAttachmentFilter = requestedFilter;
                            documentDetailsAttachmentSourceFilterValue = 'all';
                            renderDocumentAttachments(documentDetailsAllAttachments, {
                                created_by_user_id: documentDetailsCreatorUserId,
                            });
                        });
                    });
                }
                if (documentDetailsAttachmentSourceFilter) {
                    documentDetailsAttachmentSourceFilter.addEventListener('change', function () {
                        documentDetailsAttachmentSourceFilterValue = String(documentDetailsAttachmentSourceFilter.value || 'all');
                        renderDocumentAttachments(documentDetailsAllAttachments, {
                            created_by_user_id: documentDetailsCreatorUserId,
                        });
                    });
                }
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && documentDetailsModal && !documentDetailsModal.hidden) {
                        closeDocumentDetailsModal();
                    }
                });
            }

            function queueStatusAlreadyReceived(status) {
                const normalized = normalizeLabelKey(status || '');
                if (normalized === '') {
                    return false;
                }
                const isPendingReceiveState = normalized.indexOf('pending receive') !== -1
                    || normalized.indexOf('pending received') !== -1
                    || normalized.indexOf('pending recieved') !== -1
                    || normalized.indexOf('awaiting receive') !== -1
                    || normalized.indexOf('for receive') !== -1
                    || normalized.indexOf('in transit') !== -1
                    || normalized.indexOf('routed') !== -1
                    || normalized.indexOf('assigned') !== -1;
                if (isPendingReceiveState) {
                    return false;
                }
                return normalized.indexOf('received') !== -1 || normalized.indexOf('recieved') !== -1;
            }

            function queueRowHasReceivedState(status, pendingOfficeId, rowCurrentOfficeId) {
                if (queueStatusAlreadyReceived(status)) {
                    return true;
                }
                const pendingId = Number(pendingOfficeId || 0);
                if (Number.isFinite(pendingId) && pendingId > 0) {
                    return false;
                }
                const currentRowOfficeId = Number(rowCurrentOfficeId || 0);
                return Number.isFinite(currentRowOfficeId)
                    && currentRowOfficeId > 0
                    && Number.isFinite(currentOfficeId)
                    && currentOfficeId > 0
                    && currentRowOfficeId === currentOfficeId;
            }

            function queueStatusAllowsReceive(status) {
                const normalized = normalizeLabelKey(status || '');
                if (normalized === '') {
                    return true;
                }
                if (queueStatusAlreadyReceived(normalized)) {
                    return false;
                }
                const isReleasedIncoming = normalized.indexOf('released') !== -1 || normalized.indexOf('release') !== -1;
                if (isReleasedIncoming) {
                    return true;
                }
                return !queueStatusIsTerminal(normalized);
            }

            function queueRowAllowsReceive(status, pendingOfficeId, rowCurrentOfficeId) {
                const pendingId = Number(pendingOfficeId || 0);
                const sameOfficePending = Number.isFinite(pendingId)
                    && pendingId > 0
                    && Number.isFinite(currentOfficeId)
                    && currentOfficeId > 0
                    && pendingId === currentOfficeId;
                let canReceiveInScope = sameOfficePending;
                if (!canReceiveInScope) {
                    const currentRowOfficeId = Number(rowCurrentOfficeId || 0);
                    const isDivisionOrSection = currentRoleKey === 'DIVISION_CHIEF' || currentRoleKey === 'SECTION_STAFF' || isArdRole;
                    const pendingUnknown = !(Number.isFinite(pendingId) && pendingId > 0);
                    const rowClearlyIncoming = Number.isFinite(currentRowOfficeId)
                        && currentRowOfficeId > 0
                        && Number.isFinite(currentOfficeId)
                        && currentOfficeId > 0
                        && currentRowOfficeId !== currentOfficeId;
                    if (!(isDivisionOrSection && pendingUnknown && rowClearlyIncoming)) {
                        return false;
                    }
                    canReceiveInScope = true;
                }

                if (!canReceiveInScope) {
                    return false;
                }

                const normalizedStatus = normalizeLabelKey(status || '');
                const isForwardLikeIncoming = normalizedStatus.indexOf('forward') !== -1
                    || normalizedStatus.indexOf('route') !== -1
                    || normalizedStatus.indexOf('assigned to ') === 0;
                if (isForwardLikeIncoming && !queueStatusAlreadyReceived(normalizedStatus)) {
                    return true;
                }
                const isReleasedIncoming = normalizedStatus.indexOf('released') !== -1
                    || normalizedStatus.indexOf('release') !== -1;
                if (isReleasedIncoming && !queueStatusAlreadyReceived(normalizedStatus)) {
                    return true;
                }

                return queueStatusAllowsReceive(normalizedStatus);
            }

            function queueStatusIsTerminal(status) {
                const normalized = normalizeLabelKey(status || '');
                if (normalized === '') {
                    return false;
                }
                // Terminal means "done with this office" or "end of life".
                // "signed" is NOT terminal here because it still needs to be forwarded.
                const terminalKeywords = ['forwarded', 'released', 'completed', 'cancelled', 'canceled', 'closed'];
                return terminalKeywords.some(function (keyword) {
                    return new RegExp('\\b' + keyword + '\\b').test(normalized);
                });
            }

            function queueStatusIsApproved(status) {
                const normalized = normalizeLabelKey(status || '');
                return /\bapproved\b/.test(normalized)
                    || (/\bsigned\b/.test(normalized) && normalized.indexOf('assigned') === -1)
                    || /\bverified\b/.test(normalized)
                    || /\bendorsed\b/.test(normalized)
                    || /\bchecked\b/.test(normalized)
                    || /\bvalidated\b/.test(normalized);
            }

            function queueStatusNeedsApproval(status) {
                const normalized = normalizeLabelKey(status || '');
                return normalized.indexOf('approval') !== -1
                    || normalized.indexOf('for approval') !== -1
                    || normalized.indexOf('validation') !== -1
                    || normalized.indexOf('validate') !== -1;
            }

            function queueStatusIsSignedCompleted(status) {
                const normalized = normalizeLabelKey(status || '');
                return /\b(signed|completed)\b/.test(normalized);
            }

            function queueStatusIsForwarded(status) {
                const normalized = normalizeLabelKey(status || '');
                return normalized.indexOf('forward') !== -1
                    || normalized.indexOf('route') !== -1
                    || normalized.indexOf('assigned to ') === 0;
            }

            function queueRowIsCompletedForView(queueRow) {
                const status = normalizeLabelKey(queueRow && queueRow.status ? queueRow.status : '');
                if (showStrictCompletedOnly) {
                    return status.indexOf('completed') !== -1;
                }
                const terminalKeywords = ['forwarded', 'completed', 'released', 'closed', 'resolved', 'done', 'signed', 'cancelled', 'canceled'];
                const hasTerminalStatus = terminalKeywords.some(function (keyword) {
                    return status.indexOf(keyword) !== -1;
                });
                if (hasTerminalStatus) {
                    return true;
                }

                const hasSignedAction = Number(queueRow && queueRow.has_signed_action ? queueRow.has_signed_action : 0) > 0;
                if (hasSignedAction && (status.indexOf('received') !== -1 || status.indexOf('recieved') !== -1)) {
                    return true;
                }

                return false;
            }

            function isQueueTableLayout(requireActionColumn) {
                if (!tableElement) {
                    return false;
                }
                const headers = Array.from(tableElement.querySelectorAll('thead th')).map(function (th) {
                    return normalizeLabelKey(th.textContent || '');
                });
                const hasTrackingColumn = headers.some(function (label) {
                    return label.indexOf('tracking') !== -1;
                });
                const hasActionColumn = headers.some(function (label) {
                    return label.indexOf('quick action') !== -1 || label === 'action' || label === 'actions';
                });
                const needsActionColumn = requireActionColumn !== false;
                return hasTrackingColumn && (!needsActionColumn || hasActionColumn);
            }

            function getQueueTableRows() {
                if (!tableBody) {
                    return [];
                }
                return Array.from(tableBody.querySelectorAll('tr'));
            }

            function getBulkRowSelectionKey(row, fallbackIndex) {
                if (!row) {
                    return '';
                }
                const explicitKey = String(row.getAttribute('data-row-key') || '').trim();
                if (explicitKey !== '') {
                    return explicitKey;
                }
                const documentId = String(row.getAttribute('data-document-id') || '').trim();
                if (documentId !== '') {
                    return 'doc:' + documentId;
                }
                const trackingId = String(row.getAttribute('data-tracking-id') || '').trim().toUpperCase();
                if (trackingId !== '') {
                    return 'trk:' + trackingId;
                }
                const rowIndex = Number.isFinite(Number(fallbackIndex)) ? Number(fallbackIndex) : 0;
                return 'idx:' + String(rowIndex);
            }

            function getBulkSelectableRows(includeHidden) {
                const rows = getQueueTableRows().filter(function (row) {
                    return !row.querySelector('.table-empty-state');
                });
                if (includeHidden) {
                    return rows;
                }
                return rows.filter(function (row) { return row.style.display !== 'none'; });
            }

            function syncBulkSelectionUi() {
                if (!bulkRowExportEnabled || !tableBody) {
                    return;
                }

                const allRows = getBulkSelectableRows(true);
                const visibleRows = getBulkSelectableRows(false);
                const validKeys = new Set();
                let selectedVisibleCount = 0;

                allRows.forEach(function (row, index) {
                    const rowKey = getBulkRowSelectionKey(row, index);
                    if (rowKey === '') {
                        return;
                    }
                    validKeys.add(rowKey);
                    const checkbox = row.querySelector('.bulk-row-select');
                    const isSelected = selectedBulkRowKeys.has(rowKey);
                    if (checkbox) {
                        checkbox.checked = isSelected;
                    }
                    if (isSelected && row.style.display !== 'none') {
                        selectedVisibleCount += 1;
                    }
                });

                const nextSelectedKeys = new Set();
                selectedBulkRowKeys.forEach(function (key) {
                    if (validKeys.has(key)) {
                        nextSelectedKeys.add(key);
                    }
                });
                selectedBulkRowKeys = nextSelectedKeys;

                if (bulkSelectedCount) {
                    bulkSelectedCount.textContent = 'Selected: ' + String(selectedBulkRowKeys.size);
                }

                if (bulkSelectAllRows) {
                    if (visibleRows.length === 0) {
                        bulkSelectAllRows.checked = false;
                        bulkSelectAllRows.indeterminate = false;
                        bulkSelectAllRows.disabled = true;
                    } else {
                        bulkSelectAllRows.disabled = false;
                        bulkSelectAllRows.checked = selectedVisibleCount > 0 && selectedVisibleCount === visibleRows.length;
                        bulkSelectAllRows.indeterminate = selectedVisibleCount > 0 && selectedVisibleCount < visibleRows.length;
                    }
                }
            }

            function findQueueRowByTrackingId(trackingId) {
                const target = String(trackingId || '').trim().toUpperCase();
                if (target === '') {
                    return null;
                }
                const rows = getQueueTableRows();
                for (let i = 0; i < rows.length; i += 1) {
                    const rowTrackingId = String(rows[i].getAttribute('data-tracking-id') || '').trim().toUpperCase();
                    if (rowTrackingId === target) {
                        return rows[i];
                    }
                }
                return null;
            }

            function findFirstVisibleQueueRow() {
                const rows = getQueueTableRows();
                for (let i = 0; i < rows.length; i += 1) {
                    const trackingId = String(rows[i].getAttribute('data-tracking-id') || '').trim();
                    if (trackingId === '' || rows[i].style.display === 'none') {
                        continue;
                    }
                    return rows[i];
                }
                return null;
            }

            function clampLiveFlowScale(scale) {
                const numeric = Number(scale);
                if (!Number.isFinite(numeric)) {
                    return 1;
                }
                return Math.min(2.1, Math.max(0.2, numeric));
            }

            function applyLiveFlowTransform() {
                if (!liveFlowCanvas) {
                    return;
                }
                liveFlowCanvas.style.transform = 'translate(' + String(Math.round(liveFlowOffsetX)) + 'px, ' + String(Math.round(liveFlowOffsetY)) + 'px) scale(' + String(liveFlowScale.toFixed(3)) + ')';
            }

            function fitLiveFlowCanvas() {
                if (!liveFlowViewport || !liveFlowCanvas) {
                    return;
                }
                const viewportWidth = Math.max(120, liveFlowViewport.clientWidth);
                const viewportHeight = Math.max(120, liveFlowViewport.clientHeight);
                const canvasWidth = Math.max(1, liveFlowCanvas.offsetWidth);
                const canvasHeight = Math.max(1, liveFlowCanvas.offsetHeight);
                const padding = 28;
                const scaleX = (viewportWidth - padding) / canvasWidth;
                const scaleY = (viewportHeight - padding) / canvasHeight;
                liveFlowScale = clampLiveFlowScale(Math.min(scaleX, scaleY));
                liveFlowOffsetX = (viewportWidth - (canvasWidth * liveFlowScale)) / 2;
                liveFlowOffsetY = (viewportHeight - (canvasHeight * liveFlowScale)) / 2;
                applyLiveFlowTransform();
            }

            function zoomLiveFlow(nextScale, anchorClientX, anchorClientY) {
                if (!liveFlowViewport || !liveFlowCanvas) {
                    return;
                }
                const clampedScale = clampLiveFlowScale(nextScale);
                if (Math.abs(clampedScale - liveFlowScale) < 0.0001) {
                    return;
                }
                const rect = liveFlowViewport.getBoundingClientRect();
                const anchorX = Number.isFinite(anchorClientX) ? anchorClientX : (rect.left + (rect.width / 2));
                const anchorY = Number.isFinite(anchorClientY) ? anchorClientY : (rect.top + (rect.height / 2));
                const localX = anchorX - rect.left;
                const localY = anchorY - rect.top;
                const worldX = (localX - liveFlowOffsetX) / liveFlowScale;
                const worldY = (localY - liveFlowOffsetY) / liveFlowScale;
                liveFlowScale = clampedScale;
                liveFlowOffsetX = localX - (worldX * liveFlowScale);
                liveFlowOffsetY = localY - (worldY * liveFlowScale);
                applyLiveFlowTransform();
            }

            function bindLiveFlowViewport() {
                if (!liveFlowViewport || !liveFlowCanvas) {
                    return;
                }

                fitLiveFlowCanvas();

                if (liveFlowZoomInBtn) {
                    liveFlowZoomInBtn.addEventListener('click', function () {
                        liveFlowHasManualTransform = true;
                        zoomLiveFlow(liveFlowScale * 1.12);
                    });
                }
                if (liveFlowZoomOutBtn) {
                    liveFlowZoomOutBtn.addEventListener('click', function () {
                        liveFlowHasManualTransform = true;
                        zoomLiveFlow(liveFlowScale / 1.12);
                    });
                }
                if (liveFlowResetBtn) {
                    liveFlowResetBtn.addEventListener('click', function () {
                        liveFlowHasManualTransform = false;
                        fitLiveFlowCanvas();
                    });
                }

                liveFlowViewport.addEventListener('wheel', function (event) {
                    event.preventDefault();
                    liveFlowHasManualTransform = true;
                    const zoomFactor = event.deltaY < 0 ? 1.08 : 0.92;
                    zoomLiveFlow(liveFlowScale * zoomFactor, event.clientX, event.clientY);
                }, { passive: false });

                liveFlowViewport.addEventListener('pointerdown', function (event) {
                    if (event.button !== 0) {
                        return;
                    }
                    if (event.target && event.target.closest('.live-flow-tool-btn')) {
                        return;
                    }
                    liveFlowHasManualTransform = true;
                    liveFlowIsPanning = true;
                    liveFlowLastPanX = event.clientX;
                    liveFlowLastPanY = event.clientY;
                    liveFlowViewport.classList.add('is-panning');
                    if (typeof liveFlowViewport.setPointerCapture === 'function') {
                        liveFlowViewport.setPointerCapture(event.pointerId);
                    }
                });

                liveFlowViewport.addEventListener('pointermove', function (event) {
                    if (!liveFlowIsPanning) {
                        return;
                    }
                    const deltaX = event.clientX - liveFlowLastPanX;
                    const deltaY = event.clientY - liveFlowLastPanY;
                    liveFlowLastPanX = event.clientX;
                    liveFlowLastPanY = event.clientY;
                    liveFlowOffsetX += deltaX;
                    liveFlowOffsetY += deltaY;
                    applyLiveFlowTransform();
                });

                const endPan = function (event) {
                    if (!liveFlowIsPanning) {
                        return;
                    }
                    liveFlowIsPanning = false;
                    liveFlowViewport.classList.remove('is-panning');
                    if (event && typeof liveFlowViewport.releasePointerCapture === 'function') {
                        try {
                            liveFlowViewport.releasePointerCapture(event.pointerId);
                        } catch (error) {
                            // Ignore pointer release issues.
                        }
                    }
                };

                liveFlowViewport.addEventListener('pointerup', endPan);
                liveFlowViewport.addEventListener('pointercancel', endPan);
                liveFlowViewport.addEventListener('pointerleave', function () {
                    if (!liveFlowIsPanning) {
                        return;
                    }
                    liveFlowIsPanning = false;
                    liveFlowViewport.classList.remove('is-panning');
                });

                window.addEventListener('resize', function () {
                    if (!liveFlowHasManualTransform) {
                        fitLiveFlowCanvas();
                    }
                });
            }

            function flowStatusLabelFromState(state) {
                if (state === 'completed') {
                    return 'Completed';
                }
                if (state === 'active') {
                    return 'In Progress';
                }
                return 'Pending';
            }

            function resetLiveFlowTracker(message) {
                if (!liveFlowTrackerCard || liveFlowSteps.length === 0) {
                    return;
                }
                if (liveFlowTrackingId) {
                    liveFlowTrackingId.textContent = String(message || 'Tracking: -');
                }
                if (liveFlowCurrentHolder) {
                    liveFlowCurrentHolder.textContent = 'Current Holder: -';
                }
                if (liveFlowStatus) {
                    liveFlowStatus.textContent = 'Status: Pending';
                }
                liveFlowSteps.forEach(function (stepNode) {
                    stepNode.classList.remove('is-completed', 'is-active');
                    stepNode.classList.add('is-pending');
                    const stateNode = stepNode.querySelector('.live-flow-step-state');
                    if (stateNode) {
                        stateNode.textContent = 'Pending';
                    }
                });
            }

            function deriveLiveFlowSnapshot(row) {
                if (!row || liveFlowSteps.length === 0) {
                    return null;
                }

                const trackingId = String(row.getAttribute('data-tracking-id') || '').trim();
                const statusRaw = String(row.getAttribute('data-status') || getRowCellTextByHeader(row, 'status') || 'Pending').trim();
                const currentHolderRaw = String(row.getAttribute('data-current-holder') || getRowCellTextByHeader(row, 'current holder') || getRowCellTextByHeader(row, 'to') || '-').trim();
                const hasSignedAction = String(row.getAttribute('data-has-signed-action') || '0') === '1';
                const pendingOfficeId = parseInt(String(row.getAttribute('data-pending-office-id') || '0'), 10);
                const currentOfficeLevel = normalizeLabelKey(String(row.getAttribute('data-current-office-level') || ''));
                const pendingOfficeLevel = normalizeLabelKey(String(row.getAttribute('data-pending-office-level') || ''));
                const effectiveOfficeLevel = pendingOfficeId > 0 ? pendingOfficeLevel : currentOfficeLevel;
                const normalizedStatus = normalizeLabelKey(statusRaw);
                const normalizedHolder = normalizeLabelKey(currentHolderRaw);
                if (isPenroInternalFlowRole) {
                    const holderIsPenroAdminRecord = normalizedHolder.indexOf('adminrecord') !== -1
                        || normalizedHolder.indexOf('adminrecords') !== -1
                        || effectiveOfficeLevel === 'penro_admin_record';
                    const holderIsPenroOfficer = normalizedHolder.indexOf('penroofficer') !== -1
                        || (normalizedHolder.indexOf('officer') !== -1 && normalizedHolder.indexOf('penro') !== -1)
                        || effectiveOfficeLevel === 'penro_officer';
                    const holderIsPenroDivision = normalizedHolder.indexOf('division') !== -1
                        || effectiveOfficeLevel === 'penro_division';
                    const holderIsPenroSection = normalizedHolder.indexOf('section') !== -1
                        || normalizedHolder.indexOf('unit') !== -1
                        || effectiveOfficeLevel === 'penro_section'
                        || effectiveOfficeLevel === 'penro_unit';
                    const holderIsRecordsUnit = normalizedHolder.indexOf('recordsunit') !== -1
                        || normalizedHolder.indexOf('records-unit') !== -1
                        || normalizedHolder.indexOf('pacdo') !== -1
                        || normalizedHolder.indexOf('records unit') !== -1;
                    const holderIsExternalOriginOffice = normalizedHolder.indexOf('cenro') !== -1
                        || normalizedHolder.indexOf('pasu') !== -1
                        || normalizedHolder.indexOf('origin') !== -1
                        || effectiveOfficeLevel === 'community'
                        || effectiveOfficeLevel === 'protected area';
                    const isSignedStage = queueStatusIsSignedCompleted(statusRaw) || hasSignedAction;
                    const isForwardedStage = queueStatusIsForwarded(statusRaw);
                    const isReturnedStage = normalizedStatus.indexOf('return') !== -1;
                    const isCompletedStage = normalizedStatus.indexOf('completed') !== -1 || normalizedStatus.indexOf('done') !== -1;
                    const isOutwardStage = holderIsRecordsUnit || holderIsExternalOriginOffice || (isCompletedStage && !holderIsPenroAdminRecord);

                    let activeStep = -1;
                    if (holderIsPenroOfficer) {
                        activeStep = (isSignedStage && !isForwardedStage) ? 4 : 1;
                    } else if (holderIsPenroDivision) {
                        activeStep = 2;
                    } else if (holderIsPenroSection) {
                        activeStep = 3;
                    } else if (holderIsPenroAdminRecord) {
                        activeStep = (isReturnedStage || isSignedStage || hasSignedAction || isForwardedStage) ? 5 : 0;
                    } else if (isOutwardStage) {
                        activeStep = 6;
                    } else if (normalizedStatus.indexOf('created') !== -1) {
                        activeStep = 0;
                    }

                    let completedThrough = activeStep > 0 ? (activeStep - 1) : -1;
                    if (holderIsPenroSection) {
                        completedThrough = Math.max(completedThrough, 2);
                    }
                    if (holderIsPenroOfficer && activeStep === 4) {
                        completedThrough = Math.max(completedThrough, 3);
                    }
                    if (holderIsPenroAdminRecord && activeStep === 5) {
                        completedThrough = Math.max(completedThrough, 4);
                    }
                    if (isOutwardStage) {
                        completedThrough = Math.max(completedThrough, 5);
                    }
                    if (isCompletedStage) {
                        completedThrough = 6;
                        activeStep = -1;
                    }

                    const maxStepIndex = liveFlowSteps.length - 1;
                    if (maxStepIndex < 0) {
                        return null;
                    }
                    if (activeStep > maxStepIndex) {
                        activeStep = maxStepIndex;
                    }
                    if (completedThrough > maxStepIndex) {
                        completedThrough = maxStepIndex;
                    }

                    const stepStates = [];
                    for (let stepIndex = 0; stepIndex < liveFlowSteps.length; stepIndex += 1) {
                        if (stepIndex <= completedThrough) {
                            stepStates.push('completed');
                        } else if (activeStep >= 0 && stepIndex === activeStep) {
                            stepStates.push('active');
                        } else {
                            stepStates.push('pending');
                        }
                    }

                    return {
                        trackingId: trackingId,
                        statusRaw: statusRaw,
                        currentHolderRaw: currentHolderRaw,
                        activeStep: activeStep,
                        isCompleted: isCompletedStage,
                        stepStates: stepStates,
                    };
                }
                if (isCenroInternalFlowRole) {
                    const holderIsOriginOffice = normalizedHolder.indexOf('origin') !== -1
                        || normalizedHolder.indexOf('cenro') !== -1
                        || normalizedHolder.indexOf('pasu') !== -1
                        || effectiveOfficeLevel === 'community'
                        || effectiveOfficeLevel === 'protected area';
                    const holderIsCenroAdminRecord = normalizedHolder.indexOf('admin record') !== -1
                        || normalizedHolder.indexOf('admin records') !== -1
                        || effectiveOfficeLevel === 'cenro_admin_record';
                    const holderIsCenroOfficer = normalizedHolder.indexOf('cenro officer') !== -1
                        || normalizedHolder.indexOf(' - officer') !== -1
                        || normalizedHolder.indexOf(' officer') !== -1
                        || effectiveOfficeLevel === 'cenro_officer';
                    const holderIsCenroSection = normalizedHolder.indexOf('section') !== -1
                        || effectiveOfficeLevel === 'cenro_section';
                    const holderIsCenroUnit = normalizedHolder.indexOf('unit') !== -1
                        || effectiveOfficeLevel === 'cenro_unit';
                    const isSignedStage = queueStatusIsSignedCompleted(statusRaw) || hasSignedAction;
                    const isForwardedStage = queueStatusIsForwarded(statusRaw);
                    const isReleasedStage = normalizedStatus.indexOf('released') !== -1 || normalizedStatus.indexOf('release') !== -1;
                    const isCompletedStage = normalizedStatus.indexOf('completed') !== -1 || normalizedStatus.indexOf('done') !== -1;

                    let activeStep = -1;
                    if (holderIsCenroOfficer) {
                        activeStep = 1;
                    } else if (holderIsCenroSection) {
                        activeStep = 2;
                    } else if (holderIsCenroUnit) {
                        activeStep = 3;
                    } else if (holderIsCenroAdminRecord) {
                        activeStep = isSignedStage ? 5 : 0;
                    } else if (holderIsOriginOffice) {
                        activeStep = isReleasedStage ? 7 : 0;
                    } else if (normalizedStatus.indexOf('created') !== -1) {
                        activeStep = 0;
                    }

                    let completedThrough = activeStep > 0 ? (activeStep - 1) : -1;
                    if (isSignedStage) {
                        completedThrough = Math.max(completedThrough, 4);
                        if (holderIsCenroOfficer && !isForwardedStage) {
                            activeStep = Math.max(activeStep, 4);
                        } else if (holderIsCenroAdminRecord && !isReleasedStage) {
                            activeStep = Math.max(activeStep, 5);
                        }
                    }
                    if (isReleasedStage) {
                        completedThrough = Math.max(completedThrough, 6);
                        activeStep = 7;
                    }
                    if (isCompletedStage) {
                        completedThrough = 7;
                        activeStep = -1;
                    }

                    const maxStepIndex = liveFlowSteps.length - 1;
                    if (maxStepIndex < 0) {
                        return null;
                    }
                    if (activeStep > maxStepIndex) {
                        activeStep = maxStepIndex;
                    }
                    if (completedThrough > maxStepIndex) {
                        completedThrough = maxStepIndex;
                    }

                    const stepStates = [];
                    for (let stepIndex = 0; stepIndex < liveFlowSteps.length; stepIndex += 1) {
                        if (stepIndex <= completedThrough) {
                            stepStates.push('completed');
                        } else if (activeStep >= 0 && stepIndex === activeStep) {
                            stepStates.push('active');
                        } else {
                            stepStates.push('pending');
                        }
                    }

                    return {
                        trackingId: trackingId,
                        statusRaw: statusRaw,
                        currentHolderRaw: currentHolderRaw,
                        activeStep: activeStep,
                        isCompleted: isCompletedStage,
                        stepStates: stepStates,
                    };
                }
                const holderIsOrigin = normalizedHolder.indexOf('cenro') !== -1
                    || normalizedHolder.indexOf('pasu') !== -1
                    || normalizedHolder.indexOf('origin') !== -1
                    || effectiveOfficeLevel === 'community'
                    || effectiveOfficeLevel === 'protected area';
                const holderIsPenro = normalizedHolder.indexOf('penro') !== -1
                    || effectiveOfficeLevel === 'provincial';
                const holderIsPacdo = normalizedHolder.indexOf('records_unit') !== -1
                    || normalizedHolder.indexOf('pacdo') !== -1
                    || normalizedHolder.indexOf('records unit') !== -1
                    || normalizedHolder.indexOf('records_unit') !== -1;
                const holderIsOred = normalizedHolder.indexOf('ored') !== -1;
                const holderIsArdTs = normalizedHolder.indexOf('ard_ts') !== -1
                    || normalizedHolder.indexOf('ard ts') !== -1
                    || normalizedHolder.indexOf('assistant_regional_director_technical') !== -1
                    || (normalizedHolder.indexOf('assistant_regional_director') !== -1 && normalizedHolder.indexOf('technical') !== -1)
                    || normalizedHolder.indexOf('technical_services') !== -1;
                const holderIsArdMs = normalizedHolder.indexOf('ard_ms') !== -1
                    || normalizedHolder.indexOf('ard ms') !== -1
                    || normalizedHolder.indexOf('assistant_regional_director_management') !== -1
                    || (normalizedHolder.indexOf('assistant_regional_director') !== -1 && normalizedHolder.indexOf('management') !== -1)
                    || normalizedHolder.indexOf('management_services') !== -1;
                const holderIsArd = holderIsArdTs || holderIsArdMs;
                const holderIsDivisionSection = normalizedHolder.indexOf('division') !== -1
                    || normalizedHolder.indexOf('section') !== -1
                    || effectiveOfficeLevel === 'division'
                    || effectiveOfficeLevel === 'section';
                const isApproved = queueStatusIsApproved(statusRaw);
                const isSigned = queueStatusIsSignedCompleted(statusRaw) || hasSignedAction;
                const isReleased = normalizedStatus.indexOf('released') !== -1;
                const isCompleted = normalizedStatus.indexOf('completed') !== -1 && (isReleased || holderIsOrigin);

                let activeStep = -1;
                if (holderIsPenro) {
                    activeStep = 1;
                } else if (holderIsPacdo) {
                    activeStep = 2;
                } else if (holderIsOred) {
                    activeStep = 3;
                } else if (holderIsArd) {
                    activeStep = 4;
                } else if (holderIsDivisionSection) {
                    activeStep = 5;
                } else if (holderIsOrigin) {
                    activeStep = isReleased ? 8 : 0;
                } else if (normalizedStatus.indexOf('created') !== -1) {
                    // Unknown holder label should not auto-snap to Origin unless status strongly indicates an early stage.
                    activeStep = 0;
                }

                let completedThrough = activeStep > 0 ? (activeStep - 1) : -1;
                if (isApproved && holderIsDivisionSection) {
                    // Mark ORED and ARD as completed when the document is already in Division/Section custody.
                    completedThrough = Math.max(completedThrough, 4);
                }
                if (isSigned) {
                    completedThrough = Math.max(completedThrough, 6);
                    if (!isReleased) {
                        if (holderIsPacdo) {
                            activeStep = Math.max(activeStep, 7);
                        } else if (holderIsOred) {
                            activeStep = Math.max(activeStep, 6);
                        }
                    }
                }
                if (isReleased) {
                    completedThrough = Math.max(completedThrough, 7);
                    activeStep = 8;
                }
                if (isCompleted) {
                    completedThrough = 8;
                    activeStep = -1;
                }

                const maxStepIndex = liveFlowSteps.length - 1;
                if (maxStepIndex < 0) {
                    return null;
                }
                if (activeStep > maxStepIndex) {
                    activeStep = maxStepIndex;
                }
                if (completedThrough > maxStepIndex) {
                    completedThrough = maxStepIndex;
                }

                const stepStates = [];
                for (let stepIndex = 0; stepIndex < liveFlowSteps.length; stepIndex += 1) {
                    if (stepIndex <= completedThrough) {
                        stepStates.push('completed');
                    } else if (activeStep >= 0 && stepIndex === activeStep) {
                        stepStates.push('active');
                    } else {
                        stepStates.push('pending');
                    }
                }

                return {
                    trackingId: trackingId,
                    statusRaw: statusRaw,
                    currentHolderRaw: currentHolderRaw,
                    activeStep: activeStep,
                    isCompleted: isCompleted,
                    stepStates: stepStates,
                };
            }

            function renderLiveFlowSnapshot(snapshot) {
                if (!liveFlowTrackerCard || liveFlowSteps.length === 0 || !snapshot) {
                    return;
                }

                if (liveFlowTrackingId) {
                    liveFlowTrackingId.textContent = 'Tracking: ' + (snapshot.trackingId !== '' ? snapshot.trackingId : '-');
                }
                if (liveFlowCurrentHolder) {
                    liveFlowCurrentHolder.textContent = 'Current Holder: ' + (snapshot.currentHolderRaw !== '' ? snapshot.currentHolderRaw : '-');
                }
                if (liveFlowStatus) {
                    liveFlowStatus.textContent = 'Status: ' + (snapshot.statusRaw !== '' ? snapshot.statusRaw : 'Pending');
                }

                liveFlowSteps.forEach(function (stepNode, stepIndex) {
                    const state = String(snapshot.stepStates[stepIndex] || 'pending');
                    stepNode.classList.toggle('is-completed', state === 'completed');
                    stepNode.classList.toggle('is-active', state === 'active');
                    stepNode.classList.toggle('is-pending', state === 'pending');
                    const stateNode = stepNode.querySelector('.live-flow-step-state');
                    if (stateNode) {
                        stateNode.textContent = flowStatusLabelFromState(state);
                    }
                });
            }

            function renderLiveFlowFromRow(row) {
                if (!liveFlowTrackerCard || liveFlowSteps.length === 0) {
                    return;
                }
                const snapshot = deriveLiveFlowSnapshot(row);
                if (!snapshot) {
                    resetLiveFlowTracker('Tracking: -');
                    return;
                }
                renderLiveFlowSnapshot(snapshot);
            }

            function liveFlowHoverStageLabel(snapshot) {
                if (!snapshot) {
                    return 'Stage: Pending';
                }
                if (snapshot.activeStep >= 0) {
                    const activeLabel = String(liveFlowStepLabels[snapshot.activeStep] || '').trim();
                    if (activeLabel !== '') {
                        return 'Stage: In Progress (' + activeLabel + ')';
                    }
                    return 'Stage: In Progress';
                }
                if (snapshot.isCompleted) {
                    return 'Stage: Completed';
                }
                return 'Stage: Pending';
            }

            function ensureQueueFlowHoverPopover() {
                if (queueFlowPopoverDisabled || queueFlowHoverPopover || liveFlowSteps.length === 0) {
                    return;
                }

                const popover = document.createElement('div');
                popover.className = 'queue-flow-hover-popover';
                popover.hidden = true;
                popover.setAttribute('role', 'status');
                popover.setAttribute('aria-live', 'polite');

                const heading = document.createElement('p');
                heading.className = 'queue-flow-hover-title';
                heading.textContent = 'Horizontal Document Flow (Live)';

                const stage = document.createElement('p');
                stage.className = 'queue-flow-hover-stage';
                stage.textContent = 'Stage: Pending';
                queueFlowHoverStageLabel = stage;

                const meta = document.createElement('div');
                meta.className = 'queue-flow-hover-meta';

                const trackingMeta = document.createElement('span');
                trackingMeta.className = 'queue-flow-hover-meta-item';
                trackingMeta.textContent = 'Tracking: -';
                queueFlowHoverTrackingLabel = trackingMeta;

                const holderMeta = document.createElement('span');
                holderMeta.className = 'queue-flow-hover-meta-item';
                holderMeta.textContent = 'Current Holder: -';
                queueFlowHoverCurrentHolderLabel = holderMeta;

                const statusMeta = document.createElement('span');
                statusMeta.className = 'queue-flow-hover-meta-item';
                statusMeta.textContent = 'Status: Pending';
                queueFlowHoverStatusLabel = statusMeta;

                meta.appendChild(trackingMeta);
                meta.appendChild(holderMeta);
                meta.appendChild(statusMeta);

                const track = document.createElement('div');
                track.className = 'queue-flow-hover-track';
                track.style.gridTemplateColumns = 'repeat(' + String(Math.max(liveFlowSteps.length, 1)) + ', minmax(0, 1fr))';
                queueFlowHoverStepNodes = [];
                liveFlowStepLabels.forEach(function (stepLabel, stepIndex) {
                    const node = document.createElement('span');
                    node.className = 'queue-flow-hover-step is-pending';
                    node.textContent = String(stepIndex + 1);
                    node.setAttribute('aria-label', String(stepLabel || ('Step ' + String(stepIndex + 1))));
                    node.title = String(stepLabel || ('Step ' + String(stepIndex + 1)));
                    queueFlowHoverStepNodes.push(node);
                    track.appendChild(node);
                });

                popover.appendChild(heading);
                popover.appendChild(stage);
                popover.appendChild(meta);
                popover.appendChild(track);
                if (tablePanel && tableWrap && tableWrap.parentElement) {
                    queueFlowSidebarLayout = tablePanel.querySelector('.queue-table-flow-layout');
                    if (!queueFlowSidebarLayout) {
                        queueFlowSidebarLayout = document.createElement('div');
                        queueFlowSidebarLayout.className = 'queue-table-flow-layout';
                        tableWrap.parentElement.insertBefore(queueFlowSidebarLayout, tableWrap);
                        queueFlowSidebarLayout.appendChild(tableWrap);
                    }
                    queueFlowSidebarLayout.appendChild(popover);
                } else if (document.body) {
                    document.body.appendChild(popover);
                }
                queueFlowHoverPopover = popover;
            }

            function positionQueueFlowHoverPopover(row, clientX, clientY) {
                if (!queueFlowHoverPopover || !row || queueFlowSidebarLayout) {
                    return;
                }

                const rowRect = row.getBoundingClientRect();
                const anchorX = Number.isFinite(clientX) ? clientX : rowRect.right;
                const anchorY = Number.isFinite(clientY) ? clientY : (rowRect.top + (rowRect.height / 2));
                const viewportPadding = 12;
                const gapX = 22;
                const popoverRect = queueFlowHoverPopover.getBoundingClientRect();

                let left = anchorX + gapX;
                let top = anchorY - (popoverRect.height / 2);

                if (left + popoverRect.width > window.innerWidth - viewportPadding) {
                    left = anchorX - popoverRect.width - gapX;
                }
                if (left < viewportPadding) {
                    left = viewportPadding;
                }
                if (top < viewportPadding) {
                    top = viewportPadding;
                }
                if (top + popoverRect.height > window.innerHeight - viewportPadding) {
                    top = window.innerHeight - popoverRect.height - viewportPadding;
                }

                queueFlowHoverPopover.style.left = String(Math.round(left)) + 'px';
                queueFlowHoverPopover.style.top = String(Math.round(top)) + 'px';
            }

            function renderQueueFlowHoverSnapshot(snapshot, row, clientX, clientY) {
                if (!queueFlowHoverPopover || !snapshot) {
                    return;
                }
                if (queueFlowHoverTrackingLabel) {
                    queueFlowHoverTrackingLabel.textContent = 'Tracking: ' + (snapshot.trackingId !== '' ? snapshot.trackingId : '-');
                }
                if (queueFlowHoverCurrentHolderLabel) {
                    queueFlowHoverCurrentHolderLabel.textContent = 'Current Holder: ' + (snapshot.currentHolderRaw !== '' ? snapshot.currentHolderRaw : '-');
                }
                if (queueFlowHoverStatusLabel) {
                    queueFlowHoverStatusLabel.textContent = 'Status: ' + (snapshot.statusRaw !== '' ? snapshot.statusRaw : 'Pending');
                }
                if (queueFlowHoverStageLabel) {
                    queueFlowHoverStageLabel.textContent = liveFlowHoverStageLabel(snapshot);
                }

                queueFlowHoverStepNodes.forEach(function (node, index) {
                    const state = String(snapshot.stepStates[index] || 'pending');
                    node.classList.toggle('is-completed', state === 'completed');
                    node.classList.toggle('is-active', state === 'active');
                    node.classList.toggle('is-pending', state === 'pending');
                });

                renderLiveFlowSnapshot(snapshot);
                queueFlowHoverPopover.hidden = false;
                queueFlowHoverPopover.classList.add('is-visible');
                if (queueFlowSidebarLayout) {
                    queueFlowSidebarLayout.classList.add('has-flow-sidebar');
                } else {
                    positionQueueFlowHoverPopover(row, clientX, clientY);
                }
            }

            function syncQueueFlowSidebarFromSelection() {
                if (!queueFlowSidebarLayout || !queueFlowHoverPopover) {
                    return;
                }
                const selectedRow = findQueueRowByTrackingId(selectedQueueTrackingId) || findFirstVisibleQueueRow();
                const snapshot = deriveLiveFlowSnapshot(selectedRow);
                if (!snapshot) {
                    queueFlowHoverPopover.classList.remove('is-visible');
                    queueFlowHoverPopover.hidden = true;
                    queueFlowSidebarLayout.classList.remove('has-flow-sidebar');
                    return;
                }
                renderQueueFlowHoverSnapshot(snapshot, selectedRow);
            }

            function hideQueueFlowHoverPopover(restoreLiveFlowTracker) {
                if (queueFlowHoverActiveRow) {
                    queueFlowHoverActiveRow.classList.remove('queue-row-hover-live-flow');
                }
                queueFlowHoverActiveRow = null;
                if (queueFlowSidebarLayout) {
                    syncQueueFlowSidebarFromSelection();
                    if (restoreLiveFlowTracker !== false) {
                        syncLiveFlowTracker();
                    }
                    return;
                }
                if (queueFlowHoverPopover) {
                    queueFlowHoverPopover.classList.remove('is-visible');
                    queueFlowHoverPopover.hidden = true;
                }
                if (restoreLiveFlowTracker !== false) {
                    syncLiveFlowTracker();
                }
            }

            function updateQueueFlowHoverPopover(row, clientX, clientY) {
                if (!row || !tableBody || row.style.display === 'none') {
                    hideQueueFlowHoverPopover(true);
                    return;
                }
                const trackingId = String(row.getAttribute('data-tracking-id') || '').trim();
                if (trackingId === '') {
                    hideQueueFlowHoverPopover(true);
                    return;
                }
                const snapshot = deriveLiveFlowSnapshot(row);
                if (!snapshot) {
                    hideQueueFlowHoverPopover(true);
                    return;
                }

                ensureQueueFlowHoverPopover();
                if (!queueFlowHoverPopover) {
                    return;
                }

                if (queueFlowHoverActiveRow && queueFlowHoverActiveRow !== row) {
                    queueFlowHoverActiveRow.classList.remove('queue-row-hover-live-flow');
                }
                queueFlowHoverActiveRow = row;
                queueFlowHoverActiveRow.classList.add('queue-row-hover-live-flow');
                renderQueueFlowHoverSnapshot(snapshot, row, clientX, clientY);
            }

            function syncLiveFlowTracker() {
                if (!liveFlowTrackerCard) {
                    return;
                }
                const selectedRow = findQueueRowByTrackingId(selectedQueueTrackingId) || findFirstVisibleQueueRow();
                renderLiveFlowFromRow(selectedRow);
                syncQueueFlowSidebarFromSelection();
            }

            function syncSelectedDocumentToolsState(preferVisibleRow) {
                if (!isQueueTableLayout(!forceDocumentToolsOnTrackingTable)) {
                    if (documentTools) {
                        documentTools.hidden = true;
                    }
                    resetLiveFlowTracker('Tracking: -');
                    return;
                }

                if (documentTools) {
                    documentTools.hidden = false;
                }

                let selectedRow = findQueueRowByTrackingId(selectedQueueTrackingId);
                if (!selectedRow || (preferVisibleRow && selectedRow.style.display === 'none')) {
                    selectedRow = findFirstVisibleQueueRow();
                    selectedQueueTrackingId = selectedRow
                        ? String(selectedRow.getAttribute('data-tracking-id') || '').trim()
                        : '';
                }

                const rows = getQueueTableRows();
                rows.forEach(function (row) {
                    const rowTrackingId = String(row.getAttribute('data-tracking-id') || '').trim();
                    if (rowTrackingId === '') {
                        return;
                    }
                    row.classList.add('queue-row-selectable');
                    row.tabIndex = 0;
                    const isSelected = selectedQueueTrackingId !== '' && rowTrackingId.toUpperCase() === selectedQueueTrackingId.toUpperCase();
                    row.classList.toggle('queue-row-selected', isSelected);
                    row.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                });

                const hasSelection = selectedQueueTrackingId !== '';
                if (selectedDetailsBtn) {
                    selectedDetailsBtn.disabled = !hasSelection;
                }
                if (selectedViewSlipBtn) {
                    selectedViewSlipBtn.disabled = !hasSelection;
                }
                if (selectedPrintPackageBtn) {
                    selectedPrintPackageBtn.disabled = !hasSelection;
                }
                if (selectedQrStampBtn) {
                    selectedQrStampBtn.disabled = !hasSelection;
                }
                if (documentToolsMeta) {
                    let metaText = defaultDocumentToolsMetaText;
                    if (hasSelection) {
                        metaText = 'Selected: ' + selectedQueueTrackingId;
                        if (showCompletedQueueRowsOnly && selectedRow) {
                            const offlineReady = String(selectedRow.getAttribute('data-offline-ready') || '') === '1';
                            metaText += offlineReady ? ' | Offline Ready' : ' | Online Only';
                        }
                    }
                    documentToolsMeta.textContent = metaText;
                }
                syncLiveFlowTracker();
                persistQueueTableState();
            }

            function bindSelectedDocumentTools() {
                if (selectedDetailsBtn) {
                    selectedDetailsBtn.addEventListener('click', function () {
                        if (selectedQueueTrackingId === '') {
                            showAlertDialog('Select a document row first.');
                            return;
                        }
                        openDocumentDetailsModal();
                    });
                }
                if (selectedViewSlipBtn) {
                    selectedViewSlipBtn.addEventListener('click', function () {
                        if (selectedQueueTrackingId === '') {
                            showAlertDialog('Select a document row first.');
                            return;
                        }
                        window.location.href = trackingSlipPath + '?tracking_id=' + encodeURIComponent(selectedQueueTrackingId);
                    });
                }
                if (selectedPrintPackageBtn) {
                    selectedPrintPackageBtn.addEventListener('click', function () {
                        if (selectedQueueTrackingId === '') {
                            showAlertDialog('Select a document row first.');
                            return;
                        }
                        window.open(printPackagePath + '?tracking_id=' + encodeURIComponent(selectedQueueTrackingId) + '&autoprint=1', '_blank');
                    });
                }
                if (selectedQrStampBtn) {
                    selectedQrStampBtn.addEventListener('click', function () {
                        if (selectedQueueTrackingId === '') {
                            showAlertDialog('Select a document row first.');
                            return;
                        }
                        if (String(qrStampWorkspacePath || '').trim() === '') {
                            showAlertDialog('QR stamp workspace is not available for this role.');
                            return;
                        }
                        window.location.href = qrStampWorkspacePath + '?tracking_id=' + encodeURIComponent(selectedQueueTrackingId);
                    });
                }
            }

            function bindQueueRowSelection() {
                if (!tableBody) {
                    return;
                }

                tableBody.addEventListener('click', function (event) {
                    if (event.target.closest('.quick-action-btn, .quick-action-link, a, button, input, select, textarea')) {
                        return;
                    }
                    const row = event.target.closest('tr');
                    if (!row || !tableBody.contains(row)) {
                        return;
                    }
                    const trackingId = String(row.getAttribute('data-tracking-id') || '').trim();
                    if (trackingId === '') {
                        return;
                    }
                    selectedQueueTrackingId = trackingId;
                    syncSelectedDocumentToolsState(false);
                });

                tableBody.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }
                    const row = event.target.closest('tr');
                    if (!row || !tableBody.contains(row)) {
                        return;
                    }
                    const trackingId = String(row.getAttribute('data-tracking-id') || '').trim();
                    if (trackingId === '') {
                        return;
                    }
                    event.preventDefault();
                    selectedQueueTrackingId = trackingId;
                    syncSelectedDocumentToolsState(false);
                });

                tableBody.addEventListener('dblclick', function (event) {
                    if (event.target.closest('.quick-action-btn, .quick-action-link, a, button, input, select, textarea')) {
                        return;
                    }
                    const row = event.target.closest('tr');
                    if (!row || !tableBody.contains(row)) {
                        return;
                    }
                    const trackingId = String(row.getAttribute('data-tracking-id') || '').trim();
                    if (trackingId === '') {
                        return;
                    }
                    selectedQueueTrackingId = trackingId;
                    syncSelectedDocumentToolsState(false);
                    openDocumentDetailsModal();
                });

                prefetchArchiveTrackingDocumentsFromTable();
            }

            function bindQueueRowFlowHoverPopover() {
                if (queueFlowPopoverDisabled || !tableBody || !liveFlowTrackerCard || liveFlowSteps.length === 0) {
                    return;
                }
                tableBody.addEventListener('mouseover', function (event) {
                    const row = event.target.closest('tr');
                    if (!row || !tableBody.contains(row)) {
                        return;
                    }
                    updateQueueFlowHoverPopover(row, event.clientX, event.clientY);
                });

                tableBody.addEventListener('mousemove', function (event) {
                    const row = event.target.closest('tr');
                    if (!row || !tableBody.contains(row)) {
                        return;
                    }
                    updateQueueFlowHoverPopover(row, event.clientX, event.clientY);
                });

                tableBody.addEventListener('mouseout', function (event) {
                    const row = event.target.closest('tr');
                    if (!row || !tableBody.contains(row)) {
                        return;
                    }

                    const relatedTarget = event.relatedTarget;
                    const nextRow = relatedTarget && typeof relatedTarget.closest === 'function'
                        ? relatedTarget.closest('tr')
                        : null;
                    if (nextRow && tableBody.contains(nextRow)) {
                        return;
                    }
                    hideQueueFlowHoverPopover(true);
                });

                tableBody.addEventListener('mouseleave', function () {
                    hideQueueFlowHoverPopover(true);
                });

                window.addEventListener('scroll', function () {
                    if (queueFlowSidebarLayout) {
                        return;
                    }
                    if (queueFlowHoverPopover && !queueFlowHoverPopover.hidden) {
                        hideQueueFlowHoverPopover(true);
                    }
                }, true);
            }

            function rowIsManagedByCurrentOffice(pendingOfficeId, rowCurrentOfficeId) {
                const pendingId = Number(pendingOfficeId || 0);
                if (Number.isFinite(pendingId) && pendingId > 0) {
                    return Number.isFinite(currentOfficeId) && currentOfficeId > 0 && pendingId === currentOfficeId;
                }
                const currentRowOfficeId = Number(rowCurrentOfficeId || 0);
                if (Number.isFinite(currentRowOfficeId) && currentRowOfficeId > 0) {
                    return Number.isFinite(currentOfficeId) && currentOfficeId > 0 && currentRowOfficeId === currentOfficeId;
                }
                return true;
            }

            function isRoutedOutRowForCurrentOffice(pendingOfficeId, rowCurrentOfficeId) {
                const pendingId = Number(pendingOfficeId || 0);
                if (Number.isFinite(pendingId) && pendingId > 0) {
                    return Number.isFinite(currentOfficeId) && currentOfficeId > 0 && pendingId !== currentOfficeId;
                }
                const currentRowOfficeId = Number(rowCurrentOfficeId || 0);
                return Number.isFinite(currentRowOfficeId)
                    && currentRowOfficeId > 0
                    && Number.isFinite(currentOfficeId)
                    && currentOfficeId > 0
                    && currentRowOfficeId !== currentOfficeId;
            }

            function buildQuickActionElement(actionKey, rowContext) {
                const trackingId = rowContext.trackingId;
                const documentId = rowContext.documentId;
                const rowStatus = String(rowContext.status || '');
                const hasSectionReceive = !!rowContext.hasSectionReceive;
                const hasSignedAction = !!rowContext.hasSignedAction;
                const hasReturnAction = !!rowContext.hasReturnAction;
                const createdByUserId = Number(rowContext.createdByUserId || 0);
                const originOfficeId = Number(rowContext.originOfficeId || 0);
                const rowCurrentOfficeId = Number(rowContext.rowCurrentOfficeId || 0);
                const pendingOfficeId = Number(rowContext.pendingOfficeId || 0);
                const rowDocumentVersion = Number(rowContext.rowDocumentVersion || 0);
                const routedOutFromCurrentOffice = isRoutedOutRowForCurrentOffice(pendingOfficeId, rowCurrentOfficeId);
                const normalizedStatus = normalizeLabelKey(rowStatus);
                const isReceived = queueRowHasReceivedState(normalizedStatus, pendingOfficeId, rowCurrentOfficeId);
                const isApproved = queueStatusIsApproved(normalizedStatus) || (approveActionDisabled && isReceived);
                const isTerminal = queueStatusIsTerminal(normalizedStatus);
                const isReleasedIncoming = normalizedStatus.indexOf('released') !== -1 || normalizedStatus.indexOf('release') !== -1;
                const canManageIntakeRow = queueRowCanManageIntake({
                    created_by_user_id: createdByUserId,
                    status: rowStatus,
                    pending_office_id: pendingOfficeId,
                    current_office_id: rowCurrentOfficeId,
                    origin_office_id: originOfficeId,
                    has_return_action: hasReturnAction ? 1 : 0,
                });
                const penroIntakeDirectForward = isPenroAdminRecordRole
                    && Number.isFinite(createdByUserId)
                    && createdByUserId > 0
                    && createdByUserId === currentUserId
                    && normalizedStatus === 'created'
                    && !(Number.isFinite(pendingOfficeId) && pendingOfficeId > 0);
                const encodedTrackingId = encodeURIComponent(trackingId);

                const linkMap = {
                    'view tracking slip': {
                        label: 'View Tracking Slip',
                        href: trackingSlipPath + '?tracking_id=' + encodedTrackingId,
                        tooltip: 'Open full tracking slip timeline and routing history.',
                    },
                    'view': {
                        label: 'View Tracking Slip',
                        href: trackingSlipPath + '?tracking_id=' + encodedTrackingId,
                        tooltip: 'Open full tracking slip timeline and routing history.',
                    },
                    'track': {
                        label: 'View Tracking Slip',
                        href: trackingSlipPath + '?tracking_id=' + encodedTrackingId,
                        tooltip: 'Open full tracking slip timeline and routing history.',
                    },
                    'print slip': {
                        label: 'Print Slip',
                        href: trackingSlipPath + '?tracking_id=' + encodedTrackingId + '&autoprint=1',
                        tooltip: 'Open print-ready tracking slip for this document.',
                    },
                    'print package': {
                        label: 'Print Package',
                        href: printPackagePath + '?tracking_id=' + encodedTrackingId + '&autoprint=1',
                        tooltip: 'Open print-ready document package.',
                    },
                };

                if (Object.prototype.hasOwnProperty.call(linkMap, actionKey)) {
                    const definition = linkMap[actionKey];
                    const link = document.createElement('a');
                    link.className = 'quick-action-link';
                    link.href = definition.href;
                    link.textContent = definition.label;
                    link.title = String(definition.tooltip || definition.label);
                    link.setAttribute('aria-label', String(definition.tooltip || definition.label));
                    return link;
                }

                const buttonMap = {
                    'receive': 'RECEIVE',
                    'forward': 'FORWARD',
                    'send back to admin record': 'FORWARD',
                    'send back to records-unit': 'FORWARD',
                    'send back to ard': 'FORWARD',
                    'send back to cenro officer': 'FORWARD',
                    'send back to penro officer': 'FORWARD',
                    'send back to ored': 'FORWARD',
                    'reroute': 'REROUTE',
                    'return': 'RETURN',
                    'override': 'OVERRIDE',
                    'sign': 'SIGN',
                    'complete': 'COMPLETE',
                    'undo sign': 'UNSIGN',
                    'release': 'RELEASE',
                    'edit': 'EDIT',
                    'delete': 'DELETE',
                };
                const tooltipMap = {
                    'receive': 'Accept custody and acknowledge receipt of this document.',
                    'forward': 'Route this document to the next destination office.',
                    'send back to admin record': 'Send this document back to the originating Admin Record office.',
                    'send back to records-unit': 'Send this signed document back to RECORDS-UNIT for release handling.',
                    'send back to ard': 'Forward this document back to your supervising ARD for review.',
                    'send back to cenro officer': 'Send this document back to your parent CENRO Officer for review.',
                    'send back to penro officer': 'Send this document back to your parent PENRO Officer for review.',
                    'send back to ored': 'Forward this document back to ORED for final review.',
                    'reroute': 'Send this document to a different destination office.',
                    'return': 'Return this document for correction or additional input.',
                    'override': 'Apply executive override routing (ORED only).',
                    'sign': 'Open digital signature workspace for signing and printing.',
                    'complete': 'Mark transaction as released at CENRO Admin Record.',
                    'undo sign': 'Undo your ORED sign and revert the document back to Approved stage.',
                    'release': (isCenroAdminRecordRole || isPenroAdminRecordRole || isPamoAdminRole)
                        ? 'Complete the document in this office or release it to another office.'
                        : 'Release document back to the originating office.',
                    'edit': 'Edit source, subject, document type, complexity, days, attachments, and remarks.',
                    'delete': 'Delete this intake document.',
                };
                const displayLabelMap = {
                    'send back to admin record': 'Send Back to Admin Record',
                    'send back to records-unit': 'Send Back to RECORDS-UNIT',
                    'send back to ard': 'Send Back to ARD',
                    'send back to cenro officer': 'Send Back to CENRO Officer',
                    'send back to penro officer': 'Send Back to PENRO Officer',
                    'send back to ored': 'Send Back to ORED',
                    'undo sign': 'Undo Sign',
                };
                if (!Object.prototype.hasOwnProperty.call(buttonMap, actionKey)) {
                    return null;
                }
                const routeMode = actionKey === 'send back to admin record'
                    ? 'admin_record_back'
                    : (actionKey === 'send back to records-unit'
                        ? 'records_unit_back'
                    : ((actionKey === 'send back to ard' || actionKey === 'send back to penro officer')
                        ? 'ard_back'
                        : (actionKey === 'send back to ored'
                            ? 'ored_back'
                            : (actionKey === 'send back to cenro officer' ? 'cenro_officer_back' : ''))));
                if (isCenroOfficerRole && actionKey === 'override') {
                    return null;
                }
                if ((isCenroOfficerRole || isPenroOfficerRole || isPamoOfficerRole) === false && actionKey === 'send back to admin record') {
                    return null;
                }
                if ((isCenroSectionRole || isPenroSectionRole || isPenroDivisionRole) && actionKey === 'send back to ard') {
                    return null;
                }
                if (!isCenroSectionRole && actionKey === 'send back to cenro officer') {
                    return null;
                }
                if (!isPenroSectionRole && !isPenroDivisionRole && actionKey === 'send back to penro officer') {
                    return null;
                }
                if (isCenroUnitRole && actionKey === 'reroute') {
                    return null;
                }
                if ((actionKey === 'edit' || actionKey === 'delete')) {
                    if (!canManageIntakeRow || isTerminal) {
                        return null;
                    }
                }
                if (actionKey === 'reroute' && hideRerouteQuickAction) {
                    return null;
                }
                if (actionKey === 'approve' && approveActionDisabled) {
                    return null;
                }
                if ((actionKey === 'sign' || actionKey === 'undo sign') && (officerDigitalSignDisabled || regionalOredDigitalSignDisabled)) {
                    return null;
                }
                if (actionKey === 'edit' || actionKey === 'delete') {
                    const intakeButton = document.createElement('button');
                    intakeButton.type = 'button';
                    intakeButton.className = 'quick-action-btn';
                    intakeButton.dataset.action = buttonMap[actionKey];
                    intakeButton.dataset.documentId = documentId > 0 ? String(documentId) : '';
                    intakeButton.dataset.trackingId = trackingId;
                    intakeButton.dataset.hasSectionReceive = hasSectionReceive ? '1' : '0';
                    intakeButton.dataset.status = rowStatus;
                    intakeButton.dataset.originOfficeId = Number.isFinite(originOfficeId) && originOfficeId > 0 ? String(originOfficeId) : '';
                    intakeButton.dataset.hasSignedAction = hasSignedAction ? '1' : '0';
                    intakeButton.dataset.hasReturnAction = hasReturnAction ? '1' : '0';
                    intakeButton.dataset.createdByUserId = Number.isFinite(createdByUserId) && createdByUserId > 0 ? String(createdByUserId) : '';
                    intakeButton.dataset.currentOfficeId = Number.isFinite(rowCurrentOfficeId) && rowCurrentOfficeId > 0 ? String(rowCurrentOfficeId) : '';
                    intakeButton.dataset.pendingOfficeId = Number.isFinite(pendingOfficeId) && pendingOfficeId > 0 ? String(pendingOfficeId) : '';
                    intakeButton.dataset.documentVersion = Number.isFinite(rowDocumentVersion) && rowDocumentVersion > 0 ? String(rowDocumentVersion) : '';
                    intakeButton.textContent = actionKey.charAt(0).toUpperCase() + actionKey.slice(1);
                    intakeButton.title = String(tooltipMap[actionKey] || 'Run this document action.');
                    intakeButton.setAttribute('aria-label', String(tooltipMap[actionKey] || 'Run this document action.'));
                    return intakeButton;
                }
                const isSignedCompleted = queueStatusIsSignedCompleted(normalizedStatus);
                const isSignedForPhase = isSignedCompleted || hasSignedAction;
                const isForwarded = queueStatusIsForwarded(normalizedStatus) || routedOutFromCurrentOffice;
                const isReleasedAwaitingOriginReceive = currentRoleKey === 'RECORDS_UNIT'
                    && normalizedStatus.indexOf('released') !== -1
                    && isTerminal
                    && Number.isFinite(pendingOfficeId)
                    && pendingOfficeId > 0
                    && Number.isFinite(currentOfficeId)
                    && currentOfficeId > 0
                    && pendingOfficeId !== currentOfficeId;
                const bypassRoleStageForReroute = actionKey === 'reroute'
                    && enableQueueRerouteAction
                    && (currentRoleKey === 'ORED' || currentRoleKey === 'DIVISION_CHIEF' || isArdRole || isCenroOfficerRole || isCenroSectionRole)
                    && (isForwarded || routedOutFromCurrentOffice);
                if (
                    currentRoleKey === 'RECORDS_UNIT'
                    && !isCenroAdminRecordRole
                    && !isPenroAdminRecordRole
                    && !isPamoAdminRole
                    && !canManageIntakeRow
                ) {
                    if (!isReceived && !isReleasedAwaitingOriginReceive) {
                        if (actionKey !== 'receive') {
                            return null;
                        }
                    } else if (hasSignedAction) {
                        if (actionKey !== 'release') {
                            return null;
                        }
                        if (isTerminal && !isReleasedAwaitingOriginReceive) {
                            return null;
                        }
                    } else if (actionKey !== 'forward' && actionKey !== 'return') {
                        return null;
                    }
                }
                if ((isCenroAdminRecordRole || isPenroAdminRecordRole || isPamoAdminRole) && !canManageIntakeRow) {
                    if (!isReceived) {
                        if (actionKey !== 'receive') {
                            return null;
                        }
                    } else if (isSignedForPhase) {
                        if (actionKey !== 'release') {
                            return null;
                        }
                    } else if (actionKey !== 'forward' && actionKey !== 'release') {
                        return null;
                    }
                }
                if (currentRoleKey === 'ORED' && !bypassRoleStageForReroute) {
                    if (!isReceived) {
                        if (actionKey !== 'receive') {
                            return null;
                        }
                    } else if (isSignedForPhase && !isForwarded) {
                        if (isCenroOfficerRole) {
                            if (actionKey !== 'forward' && actionKey !== 'undo sign' && actionKey !== 'return') {
                                return null;
                            }
                        } else if (isRegionalOredReviewer) {
                            if (actionKey !== 'forward' && actionKey !== 'send back to records-unit' && actionKey !== 'return') {
                                return null;
                            }
                        } else if (actionKey !== 'forward' && actionKey !== 'undo sign' && actionKey !== 'return') {
                            return null;
                        }
                    } else if (isSignedForPhase && isForwarded) {
                        if (isCenroOfficerRole || isRegionalOredReviewer || actionKey !== 'override') {
                            return null;
                        }
                    } else if (!isForwarded) {
                        if (isCenroOfficerRole || officerDigitalSignDisabled) {
                            if (actionKey !== 'sign' && actionKey !== 'forward' && actionKey !== 'send back to admin record' && actionKey !== 'reroute' && actionKey !== 'return') {
                                return null;
                            }
                        } else if (isRegionalOredReviewer) {
                            if (actionKey !== 'forward' && actionKey !== 'return') {
                                return null;
                            }
                        } else if (actionKey !== 'sign' && actionKey !== 'forward' && actionKey !== 'return') {
                            return null;
                        }
                    } else if (isCenroOfficerRole || isRegionalOredReviewer || actionKey !== 'override') {
                        return null;
                    }
                }
                if ((currentRoleKey === 'DIVISION_CHIEF' || isArdRole) && !bypassRoleStageForReroute) {
                    if (!isReceived) {
                        if (actionKey !== 'receive') {
                            return null;
                        }
                    } else if (currentRoleKey === 'DIVISION_CHIEF' && !isCenroSectionRole && actionKey !== 'forward' && actionKey !== 'send back to ard' && actionKey !== 'send back to penro officer') {
                        return null;
                    } else if (currentRoleKey === 'DIVISION_CHIEF' && isCenroSectionRole && actionKey !== 'forward' && actionKey !== 'reroute' && actionKey !== 'send back to cenro officer') {
                        return null;
                    } else if (isArdRole && actionKey !== 'forward' && actionKey !== 'send back to ored') {
                        return null;
                    }
                }
                if (currentRoleKey === 'SECTION_STAFF') {
                    if (!isReceived) {
                        if (actionKey !== 'receive') {
                            return null;
                        }
                    } else if (actionKey !== 'forward') {
                        return null;
                    }
                }
                if (actionKey === 'receive' && !queueRowAllowsReceive(rowStatus, pendingOfficeId, rowCurrentOfficeId)) {
                    return null;
                }
                if (
                    actionKey === 'receive'
                    && isPenroAdminRecordRole
                    && !isReleasedIncoming
                    && !hasReturnAction
                    && normalizedStatus.indexOf('return') === -1
                    && Number.isFinite(originOfficeId)
                    && originOfficeId > 0
                    && Number.isFinite(currentOfficeId)
                    && currentOfficeId > 0
                    && originOfficeId === currentOfficeId
                ) {
                    return null;
                }
                if (actionKey === 'forward' || actionKey === 'send back to admin record' || actionKey === 'send back to records-unit' || actionKey === 'send back to ard' || actionKey === 'send back to cenro officer' || actionKey === 'send back to penro officer' || actionKey === 'send back to ored') {
                    if (
                        (routedOutFromCurrentOffice || queueStatusIsForwarded(normalizedStatus))
                        && !(currentRoleKey === 'ORED' && !isCenroOfficerRole)
                    ) {
                        return null;
                    }
                    if (isTerminal && !(currentRoleKey === 'ORED' && isSignedCompleted)) {
                        return null;
                    }
                    if (isPenroAdminRecordRole && !penroIntakeDirectForward && !canManageIntakeRow && !isReceived) {
                        return null;
                    }
                    if (
                        currentRoleKey === 'RECORDS_UNIT'
                        && !isCenroAdminRecordRole
                        && !isPenroAdminRecordRole
                        && !isPamoAdminRole
                        && !canManageIntakeRow
                        && !isApproved
                    ) {
                        return null;
                    }
                }
                if (actionKey === 'undo sign') {
                    if (currentRoleKey !== 'ORED') {
                        return null;
                    }
                    if (!isReceived || !isSignedForPhase || isForwarded) {
                        return null;
                    }
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'quick-action-btn';
                button.dataset.action = buttonMap[actionKey];
                button.dataset.documentId = documentId > 0 ? String(documentId) : '';
                button.dataset.trackingId = trackingId;
                button.dataset.hasSectionReceive = hasSectionReceive ? '1' : '0';
                button.dataset.status = rowStatus;
                button.dataset.originOfficeId = Number.isFinite(originOfficeId) && originOfficeId > 0 ? String(originOfficeId) : '';
                button.dataset.hasSignedAction = hasSignedAction ? '1' : '0';
                button.dataset.hasReturnAction = hasReturnAction ? '1' : '0';
                button.dataset.createdByUserId = Number.isFinite(createdByUserId) && createdByUserId > 0 ? String(createdByUserId) : '';
                button.dataset.currentOfficeId = Number.isFinite(rowCurrentOfficeId) && rowCurrentOfficeId > 0 ? String(rowCurrentOfficeId) : '';
                button.dataset.pendingOfficeId = Number.isFinite(pendingOfficeId) && pendingOfficeId > 0 ? String(pendingOfficeId) : '';
                button.dataset.documentVersion = Number.isFinite(rowDocumentVersion) && rowDocumentVersion > 0 ? String(rowDocumentVersion) : '';
                if (routeMode !== '') {
                    button.dataset.routeMode = routeMode;
                }
                let buttonLabel = Object.prototype.hasOwnProperty.call(displayLabelMap, actionKey)
                    ? displayLabelMap[actionKey]
                    : (actionKey.charAt(0).toUpperCase() + actionKey.slice(1));
                let buttonTooltip = String(tooltipMap[actionKey] || 'Run this document action.');
                if (actionKey === 'release' && (isCenroAdminRecordRole || isPenroAdminRecordRole || isPamoAdminRole)) {
                    buttonTooltip = Number.isFinite(originOfficeId)
                        && originOfficeId > 0
                        && Number.isFinite(rowCurrentOfficeId)
                        && rowCurrentOfficeId > 0
                        && originOfficeId === rowCurrentOfficeId
                        ? 'Complete this document locally or release it to another office.'
                        : 'Release this finished document to another office for the next workflow leg.';
                }
                if (actionKey === 'release' && isReleasedAwaitingOriginReceive) {
                    buttonLabel = 'Send to Originating Office';
                    buttonTooltip = 'Send this released document to the originating office so they can Receive and complete.';
                }
                button.textContent = buttonLabel;
                button.title = buttonTooltip;
                button.setAttribute('aria-label', buttonTooltip);
                return button;
            }

            function renderQueueActionControls() {
                const queueRows = document.querySelectorAll('.table-panel tbody tr');
                const isQueueTable = isQueueTableLayout(true);
                const headerNodes = tableElement
                    ? Array.from(tableElement.querySelectorAll('thead th'))
                    : [];
                const trackingColumnIndex = headerNodes.findIndex(function (th) {
                    return normalizeLabelKey(th.textContent || '').indexOf('tracking') !== -1;
                });
                const actionColumnIndex = headerNodes.findIndex(function (th) {
                    const headerLabel = normalizeLabelKey(th.textContent || '');
                    return headerLabel.indexOf('quick action') !== -1 || headerLabel === 'action' || headerLabel === 'actions';
                });
                const canRenderQuickActions = actionColumnIndex >= 0;
                const indicatorColumnIndex = headerNodes.findIndex(function (th) {
                    const headerLabel = normalizeLabelKey(th.textContent || '');
                    return headerLabel === 'indicator' || headerLabel.indexOf('indicator') !== -1;
                });
                const dateCreatedColumnIndex = headerNodes.findIndex(function (th) {
                    const headerLabel = normalizeLabelKey(th.textContent || '');
                    return headerLabel.indexOf('date created') !== -1
                        || headerLabel.indexOf('created date') !== -1
                        || headerLabel.indexOf('created at') !== -1;
                });
                const statusColumnIndex = headerNodes.findIndex(function (th) {
                    return normalizeLabelKey(th.textContent || '').indexOf('status') !== -1;
                });

                function dateCreatedIndicatorState(rawValue) {
                    const parsedTs = parseDateInputValue(rawValue, false);
                    if (parsedTs === null) {
                        return '';
                    }
                    const nowTs = Date.now();
                    if (!Number.isFinite(nowTs) || !Number.isFinite(parsedTs)) {
                        return '';
                    }
                    if (parsedTs >= nowTs) {
                        return 'recent';
                    }
                    return (nowTs - parsedTs) <= 86400000 ? 'recent' : 'past';
                }

                function renderDateCreatedIndicator(cell, rawDateValue) {
                    if (!cell) {
                        return;
                    }

                    const existingLabelNode = cell.querySelector('.date-created-label');
                    const labelFromNode = existingLabelNode ? String(existingLabelNode.textContent || '').trim() : '';
                    const labelFromText = String(cell.textContent || '').trim();
                    const dateLabel = labelFromNode !== '' ? labelFromNode : (labelFromText !== '' ? labelFromText : '-');

                    const rawValue = String(rawDateValue || '').trim();
                    let indicatorState = dateCreatedIndicatorState(rawValue);
                    if (indicatorState === '') {
                        indicatorState = dateCreatedIndicatorState(dateLabel);
                    }

                    cell.classList.add('date-created-cell');
                    cell.setAttribute('data-date-created-label', dateLabel);
                    cell.innerHTML = '';

                    const content = document.createElement('span');
                    content.className = 'date-created-content';

                    const labelNode = document.createElement('span');
                    labelNode.className = 'date-created-label';
                    labelNode.textContent = dateLabel;
                    content.appendChild(labelNode);

                    if (indicatorState !== '') {
                        const badge = document.createElement('span');
                        badge.className = 'date-recency-indicator ' + (indicatorState === 'recent' ? 'is-recent' : 'is-past');
                        badge.textContent = indicatorState === 'recent' ? 'Recent' : 'Past';
                        content.appendChild(badge);
                    }

                    cell.appendChild(content);
                }

                function queueIndicatorMeta(rawLabel) {
                    const normalized = normalizeLabelKey(rawLabel || '');
                    if (normalized.indexOf('yellow') !== -1 || normalized.indexOf('urgent') !== -1) {
                        return {
                            className: 'is-yellow',
                            shortLabel: 'Urgent',
                            fullLabel: 'Yellow - Urgent',
                        };
                    }
                    if (normalized.indexOf('pink') !== -1 || normalized.indexOf('simple') !== -1) {
                        return {
                            className: 'is-pink',
                            shortLabel: 'Simple',
                            fullLabel: 'Pink - Simple',
                        };
                    }
                    if (normalized.indexOf('blue') !== -1 || normalized.indexOf('complex') !== -1 || normalized.indexOf('highly technical') !== -1) {
                        return {
                            className: 'is-blue',
                            shortLabel: 'Complex/HT',
                            fullLabel: 'Blue - Complex/Highly Technical',
                        };
                    }
                    if (normalized.indexOf('green') !== -1 || normalized.indexOf('released') !== -1) {
                        return {
                            className: 'is-green',
                            shortLabel: 'Released',
                            fullLabel: 'Green - Released',
                        };
                    }

                    return {
                        className: 'is-neutral',
                        shortLabel: 'N/A',
                        fullLabel: String(rawLabel || 'Indicator'),
                    };
                }

                function renderQueueIndicatorCell(cell, labelRaw) {
                    if (!cell) {
                        return;
                    }

                    const resolvedLabel = String(labelRaw || '').trim();
                    const meta = queueIndicatorMeta(resolvedLabel);

                    cell.classList.add('queue-indicator-cell');
                    cell.innerHTML = '';

                    const badge = document.createElement('span');
                    badge.className = 'queue-indicator-badge ' + meta.className;
                    badge.textContent = meta.shortLabel;
                    badge.title = meta.fullLabel;
                    badge.setAttribute('aria-label', meta.fullLabel);
                    cell.appendChild(badge);
                }

                queueRows.forEach(function (row) {
                    const cells = row.querySelectorAll('td');
                    if (cells.length < 2) {
                        return;
                    }

                    if (indicatorColumnIndex >= 0 && cells[indicatorColumnIndex]) {
                        const indicatorLabel = String(cells[indicatorColumnIndex].textContent || '').trim();
                        renderQueueIndicatorCell(cells[indicatorColumnIndex], indicatorLabel);
                    }

                    if (isIntakePage && dateCreatedColumnIndex >= 0 && cells[dateCreatedColumnIndex]) {
                        const dateCreatedRaw = String(row.getAttribute('data-date-created') || '').trim();
                        renderDateCreatedIndicator(cells[dateCreatedColumnIndex], dateCreatedRaw);
                    }

                    if (!canRenderQuickActions) {
                        return;
                    }

                    const trackingCell = trackingColumnIndex >= 0 && cells[trackingColumnIndex]
                        ? cells[trackingColumnIndex]
                        : cells[0];
                    const actionCell = actionColumnIndex >= 0 && cells[actionColumnIndex]
                        ? cells[actionColumnIndex]
                        : cells[cells.length - 1];
                    const trackingId = (row.getAttribute('data-tracking-id') || trackingCell.textContent || '').trim();
                    const documentId = parseInt(row.getAttribute('data-document-id') || '0', 10);
                    let rowStatus = String(row.getAttribute('data-status') || '').trim();
                    if (rowStatus === '' && statusColumnIndex >= 0 && cells[statusColumnIndex]) {
                        rowStatus = String(cells[statusColumnIndex].textContent || '').trim();
                    }
                    const rowHasSectionReceive = String(row.getAttribute('data-has-section-receive') || '0') === '1';
                    const rowHasSignedAction = String(row.getAttribute('data-has-signed-action') || '0') === '1';
                    const rowHasReturnAction = String(row.getAttribute('data-has-return-action') || '0') === '1';
                    const rowCreatedByUserId = parseInt(String(row.getAttribute('data-created-by-user-id') || '0'), 10);
                    const rowOriginOfficeId = parseInt(String(row.getAttribute('data-origin-office-id') || '0'), 10);
                    const rowCurrentOfficeId = parseInt(String(row.getAttribute('data-current-office-id') || '0'), 10);
                    const rowPendingOfficeId = parseInt(String(row.getAttribute('data-pending-office-id') || '0'), 10);
                    const rowDocumentVersion = parseInt(String(row.getAttribute('data-document-version') || '0'), 10);
                    const allowReceiveAction = queueRowAllowsReceive(rowStatus, rowPendingOfficeId, rowCurrentOfficeId);
                    const routedOutFromCurrentOffice = isRoutedOutRowForCurrentOffice(rowPendingOfficeId, rowCurrentOfficeId);
                    const normalizedRowStatus = normalizeLabelKey(rowStatus);
                    if (trackingId === '') {
                        return;
                    }

                    const rawActionText = actionCell.textContent.trim().toLowerCase();
                    if (rawActionText === '') {
                        return;
                    }

                    const documentOnlyActions = ['view tracking slip', 'view', 'track', 'print slip', 'print package'];
                    const intakeManageActions = ['edit', 'delete'];
                    const queueRowContext = {
                        created_by_user_id: Number.isFinite(rowCreatedByUserId) && rowCreatedByUserId > 0 ? rowCreatedByUserId : 0,
                        status: rowStatus,
                        pending_office_id: Number.isFinite(rowPendingOfficeId) && rowPendingOfficeId > 0 ? rowPendingOfficeId : 0,
                        current_office_id: Number.isFinite(rowCurrentOfficeId) && rowCurrentOfficeId > 0 ? rowCurrentOfficeId : 0,
                        origin_office_id: Number.isFinite(rowOriginOfficeId) && rowOriginOfficeId > 0 ? rowOriginOfficeId : 0,
                        has_return_action: rowHasReturnAction ? 1 : 0,
                    };
                    const canManageIntakeRow = queueRowCanManageIntake(queueRowContext);
                    const actionKeys = rawActionText
                        .split('|')
                        .map(function (item) { return item.trim(); })
                        .filter(function (item) {
                            if (item === '') {
                                return false;
                            }
                            const normalizedItem = normalizeLabelKey(item);
                            const allowRoutedOutReroute = enableQueueRerouteAction
                                && normalizedItem === 'reroute'
                                && (
                                    (currentRoleKey === 'ORED' && !isCenroOfficerRole)
                                    || (currentRoleKey === 'DIVISION_CHIEF' && !isCenroSectionRole)
                                    || isArdRole
                                    || isCenroOfficerRole
                                    || isCenroSectionRole
                                );
                            const allowRoutedOutReleaseFollowup = currentRoleKey === 'RECORDS_UNIT'
                                && normalizedItem === 'release'
                                && normalizedRowStatus.indexOf('released') !== -1
                                && Number.isFinite(rowPendingOfficeId)
                                && rowPendingOfficeId > 0
                                && Number.isFinite(currentOfficeId)
                                && currentOfficeId > 0
                                && rowPendingOfficeId !== currentOfficeId;
                            if (
                                routedOutFromCurrentOffice
                                && documentOnlyActions.indexOf(normalizedItem) === -1
                                && intakeManageActions.indexOf(normalizedItem) === -1
                                && !(currentRoleKey === 'ORED' && !isCenroOfficerRole && normalizedItem === 'override')
                                && !allowRoutedOutReroute
                                && !allowRoutedOutReleaseFollowup
                            ) {
                                return false;
                            }
                            if (normalizedItem === 'receive' && !allowReceiveAction) {
                                return false;
                            }
                            if (normalizedItem === 'reroute' && hideRerouteQuickAction) {
                                return false;
                            }
                            if (isCenroOfficerRole && normalizedItem === 'override') {
                                return false;
                            }
                            if (isCenroSectionRole && normalizedItem === 'send_back_to_ard') {
                                return false;
                            }
                            if (isCenroUnitRole && normalizedItem === 'reroute') {
                                return false;
                            }
                            if (hideQueueDocumentOnlyActions && documentOnlyActions.indexOf(normalizedItem) !== -1) {
                                return false;
                            }
                            if (
                                isQueueTable
                                && workflowActionScopeActive
                                && !routedOutFromCurrentOffice
                                && documentOnlyActions.indexOf(normalizedItem) !== -1
                            ) {
                                return false;
                            }
                            return true;
                        });
                    const rowIsReceivedForOfficerReturn = queueRowHasReceivedState(rowStatus, rowPendingOfficeId, rowCurrentOfficeId);
                    const rowIsTerminalForOfficerReturn = queueStatusIsTerminal(normalizedRowStatus);
                    const rowIsForwardedForOfficerReturn = queueStatusIsForwarded(normalizedRowStatus) || routedOutFromCurrentOffice;
                    if (
                        (isCenroOfficerRole || isPenroOfficerRole || isPamoOfficerRole)
                        && rowIsReceivedForOfficerReturn
                        && !rowIsTerminalForOfficerReturn
                        && !rowIsForwardedForOfficerReturn
                        && actionKeys.indexOf('send back to admin record') === -1
                    ) {
                        const forwardIndex = actionKeys.indexOf('forward');
                        const returnIndex = actionKeys.indexOf('return');
                        if (forwardIndex !== -1 && returnIndex !== -1 && returnIndex > forwardIndex) {
                            actionKeys.splice(returnIndex, 0, 'send back to admin record');
                        } else if (forwardIndex !== -1) {
                            actionKeys.splice(forwardIndex + 1, 0, 'send back to admin record');
                        } else {
                            actionKeys.push('send back to admin record');
                        }
                    }
                    if (
                        currentRoleKey === 'ORED'
                        && isRegionalOredReviewer
                        && rowIsReceivedForOfficerReturn
                        && (queueStatusIsSignedCompleted(normalizedRowStatus) || rowHasSignedAction)
                        && !rowIsTerminalForOfficerReturn
                        && !rowIsForwardedForOfficerReturn
                        && actionKeys.indexOf('send back to records-unit') === -1
                    ) {
                        const forwardIndex = actionKeys.indexOf('forward');
                        const returnIndex = actionKeys.indexOf('return');
                        if (forwardIndex !== -1 && returnIndex !== -1 && returnIndex > forwardIndex) {
                            actionKeys.splice(returnIndex, 0, 'send back to records-unit');
                        } else if (forwardIndex !== -1) {
                            actionKeys.splice(forwardIndex + 1, 0, 'send back to records-unit');
                        } else {
                            actionKeys.push('send back to records-unit');
                        }
                    }
                    if (canManageIntakeRow) {
                        if (actionKeys.indexOf('edit') === -1) {
                            actionKeys.push('edit');
                        }
                        if (actionKeys.indexOf('delete') === -1) {
                            actionKeys.push('delete');
                        }
                    }
                    const rowIsSignedForUndo = queueStatusIsSignedCompleted(normalizedRowStatus) || rowHasSignedAction;
                    if (
                        (currentRoleKey === 'ORED' || isPenroOfficerRole)
                        && rowIsSignedForUndo
                        && !queueStatusIsForwarded(normalizedRowStatus)
                        && actionKeys.indexOf('forward') !== -1
                        && actionKeys.indexOf('undo sign') === -1
                    ) {
                        actionKeys.push('undo sign');
                    }

                    if (actionKeys.length === 0) {
                        actionCell.textContent = '-';
                        return;
                    }

                    const actionGroup = document.createElement('div');
                    actionGroup.className = 'quick-action-group';
                    let renderCount = 0;
                    actionKeys.forEach(function (actionKey) {
                        const element = buildQuickActionElement(actionKey, {
                            trackingId: trackingId,
                            documentId: documentId,
                            status: rowStatus,
                            hasSectionReceive: rowHasSectionReceive,
                            hasSignedAction: rowHasSignedAction,
                            hasReturnAction: rowHasReturnAction,
                            createdByUserId: Number.isFinite(rowCreatedByUserId) && rowCreatedByUserId > 0 ? rowCreatedByUserId : 0,
                            originOfficeId: Number.isFinite(rowOriginOfficeId) && rowOriginOfficeId > 0 ? rowOriginOfficeId : 0,
                            rowCurrentOfficeId: Number.isFinite(rowCurrentOfficeId) && rowCurrentOfficeId > 0 ? rowCurrentOfficeId : 0,
                            pendingOfficeId: Number.isFinite(rowPendingOfficeId) && rowPendingOfficeId > 0 ? rowPendingOfficeId : 0,
                            rowDocumentVersion: Number.isFinite(rowDocumentVersion) && rowDocumentVersion > 0 ? rowDocumentVersion : 0,
                        });
                        if (!element) {
                            return;
                        }
                        actionGroup.appendChild(element);
                        renderCount += 1;
                    });

                    if (renderCount === 0) {
                        actionCell.textContent = '-';
                        return;
                    }

                    actionCell.innerHTML = '';
                    actionCell.appendChild(actionGroup);
                });
                syncSelectedDocumentToolsState(true);
            }

            function isRoutingAction(action) {
                return action === 'FORWARD' || action === 'REROUTE' || action === 'RETURN' || action === 'OVERRIDE' || action === 'RELEASE';
            }

            function selectedRouteDestinationOption() {
                if (!routeDestinationOffice) {
                    return null;
                }
                const selectedIndex = routeDestinationOffice.selectedIndex;
                if (!Number.isFinite(selectedIndex) || selectedIndex < 0 || selectedIndex >= routeDestinationOffice.options.length) {
                    return null;
                }
                return routeDestinationOffice.options[selectedIndex] || null;
            }

            function routeDestinationOptionIsCenroAdminRecord(option) {
                const optionLevel = routeDestinationOptionLevel(option);
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                return optionLevel === 'CENRO_ADMIN_RECORD'
                    || optionLabel.indexOf('ADMIN RECORD') !== -1;
            }

            function routeDestinationOptionIsCenroUnit(option) {
                const optionLevel = routeDestinationOptionLevel(option);
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                return optionLevel === 'CENRO_UNIT'
                    || optionLabel.indexOf('CENRO UNIT') !== -1;
            }

            function routeDestinationOptionIsCenroOfficer(option) {
                const optionLevel = routeDestinationOptionLevel(option);
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                return optionLevel === 'CENRO_OFFICER'
                    || optionLabel.indexOf('CENRO OFFICER') !== -1
                    || optionLabel.indexOf(' - OFFICER') !== -1;
            }

            function routeDestinationOptionIsPamoAdmin(option) {
                const optionLevel = routeDestinationOptionLevel(option);
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                return optionLevel === 'PAMO_ADMIN'
                    || optionLabel.indexOf('PAMO ADMIN') !== -1;
            }

            function routeDestinationOptionIsPamoUnit(option) {
                const optionLevel = routeDestinationOptionLevel(option);
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                return optionLevel === 'PAMO_UNIT'
                    || optionLabel.indexOf('PAMO UNIT') !== -1;
            }

            function routeDestinationOptionIsPamoOfficer(option) {
                const optionLevel = routeDestinationOptionLevel(option);
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                return optionLevel === 'PASU_OFFICER'
                    || optionLabel.indexOf('PAMO OFFICER') !== -1
                    || (optionLabel.indexOf('PAMO') !== -1 && optionLabel.indexOf('OFFICER') !== -1);
            }

            function routeDestinationOptionIsPenroOfficer(option) {
                const optionLevel = routeDestinationOptionLevel(option);
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                return optionLevel === 'PENRO_OFFICER'
                    || optionLabel.indexOf('PENRO OFFICER') !== -1
                    || (optionLabel.indexOf('PENRO') !== -1 && optionLabel.indexOf('OFFICER') !== -1);
            }

            function routeDestinationOptionIsPenroDivision(option) {
                const optionLevel = routeDestinationOptionLevel(option);
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                return optionLevel === 'PENRO_DIVISION'
                    || optionLabel.indexOf('PENRO DIVISION') !== -1;
            }

            function preparedResponseSourceLabel() {
                if (isPenroDivisionRole) {
                    return 'your PENRO Division or its Section';
                }
                if (isPenroSectionRole || isPenroSectionUnitRole) {
                    return 'your PENRO Section or its Unit';
                }
                if (isPamoOfficerRole) {
                    return 'your PASU Office or its Unit';
                }
                if (isPamoUnitRole) {
                    return 'your PAMO Unit';
                }
                if (isCenroSectionRole) {
                    return 'your CENRO Section or its Unit';
                }
                if (isCenroUnitRole) {
                    return 'your CENRO Unit';
                }
                if (currentRoleKey === 'DIVISION_CHIEF') {
                    return 'your Division or its Section';
                }
                if (currentRoleKey === 'SECTION_STAFF') {
                    return 'your Section';
                }
                return 'your Division/Section';
            }

            function currentRouteAttachmentOfficeId() {
                const contextOfficeId = routeContextOfficeId(activeRouteContext);
                if (Number.isFinite(contextOfficeId) && contextOfficeId > 0) {
                    return contextOfficeId;
                }
                return Number.isFinite(currentOfficeId) && currentOfficeId > 0 ? currentOfficeId : 0;
            }

            function attachmentBelongsToCurrentRouteOffice(attachment) {
                const uploadedByOfficeId = Number(attachment && attachment.uploaded_by_office_id ? attachment.uploaded_by_office_id : 0);
                const officeId = currentRouteAttachmentOfficeId();
                return Number.isFinite(uploadedByOfficeId)
                    && uploadedByOfficeId > 0
                    && Number.isFinite(officeId)
                    && officeId > 0
                    && uploadedByOfficeId === officeId;
            }

            function attachmentBelongsToCurrentRouteOfficeChain(attachment) {
                const uploadedByOfficeId = Number(attachment && attachment.uploaded_by_office_id ? attachment.uploaded_by_office_id : 0);
                const uploadedByParentOfficeId = Number(attachment && attachment.uploaded_by_office_parent_id ? attachment.uploaded_by_office_parent_id : 0);
                const officeId = currentRouteAttachmentOfficeId();
                const isSameOffice = Number.isFinite(uploadedByOfficeId)
                    && uploadedByOfficeId > 0
                    && Number.isFinite(officeId)
                    && officeId > 0
                    && uploadedByOfficeId === officeId;
                const isDirectChildOffice = Number.isFinite(uploadedByParentOfficeId)
                    && uploadedByParentOfficeId > 0
                    && Number.isFinite(officeId)
                    && officeId > 0
                    && uploadedByParentOfficeId === officeId;
                return isSameOffice || isDirectChildOffice;
            }

            function allowsPreparedResponseAttachment(context, option) {
                const action = String(context && context.action ? context.action : '').toUpperCase();
                const routeMode = normalizeLabelKey(context && context.routeMode ? context.routeMode : '');
                const destinationOption = option || selectedRouteDestinationOption();
                if (action !== 'FORWARD') {
                    return false;
                }
                if (currentRoleKey === 'DIVISION_CHIEF' && !isCenroSectionRole && !isPenroDivisionRole) {
                    return routeMode === 'ard_back';
                }
                if (currentRoleKey === 'SECTION_STAFF' && !isCenroUnitRole && !isPenroSectionRole && !isPenroSectionUnitRole) {
                    return true;
                }
                if (isCenroSectionRole) {
                    return routeMode === 'cenro_officer_back'
                        || routeDestinationOptionIsCenroOfficer(destinationOption);
                }
                if (isCenroUnitRole) {
                    return true;
                }
                if (isPamoUnitRole) {
                    return routeDestinationOptionIsPamoOfficer(destinationOption);
                }
                if (isPenroDivisionRole) {
                    return routeMode === 'ard_back'
                        || routeDestinationOptionIsPenroOfficer(destinationOption)
                        || routeDestinationOptionIsCenroOfficer(destinationOption);
                }
                if (isPenroSectionUnitRole) {
                    return routeDestinationOptionIsPenroDivision(destinationOption);
                }
                if (isPenroSectionRole) {
                    return routeDestinationOptionIsPenroDivision(destinationOption);
                }
                return false;
            }

            function routeDestinationOptionIsPenro(option) {
                const optionLevel = routeDestinationOptionLevel(option);
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                return optionLevel === 'PENRO_ADMIN_RECORD' || optionLabel.indexOf('PENRO ADMIN') !== -1 || optionLabel.indexOf('ADMIN RECORD') !== -1;
            }

            function routeDestinationOptionIsRecordsUnit(option) {
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                return officeNameMatchesRecordsUnit(optionLabel);
            }

            function routeDestinationOptionIsRegional(option) {
                const optionLevel = routeDestinationOptionLevel(option);
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                return optionLevel === 'REGIONAL'
                    || routeDestinationOptionIsRecordsUnit(option)
                    || optionLabel.indexOf('[REGIONAL]') !== -1
                    || optionLabel.indexOf('REGIONAL') !== -1;
            }

            function routeEndorsementTargetLabel(option) {
                const rawLabel = String(option && option.textContent ? option.textContent : '').trim();
                if (rawLabel === '') {
                    return 'destination office';
                }
                return rawLabel.replace(/\s*\[[^\]]+\]\s*$/, '').trim() || 'destination office';
            }

            function routeEndorsementRequirementRule(context, option) {
                const action = String(context && context.action ? context.action : '').toUpperCase();
                if (!option) {
                    return { allowed: false, required: false, targetLabel: '' };
                }

                if (action === 'FORWARD') {
                    if (isCenroAdminRecordRole && routeDestinationOptionIsPenro(option)) {
                        return {
                            allowed: true,
                            required: false,
                            targetLabel: routeEndorsementTargetLabel(option),
                        };
                    }
                    if ((isCenroAdminRecordRole || isPenroAdminRecordRole) && routeDestinationOptionIsRecordsUnit(option)) {
                        return {
                            allowed: true,
                            required: false,
                            targetLabel: 'PACDO/RECORDS-UNIT',
                        };
                    }
                    return { allowed: false, required: false, targetLabel: '' };
                }

                if (action === 'RELEASE' && isCenroAdminRecordRole && resolveRouteReleaseMode(context) === 'send_to_office') {
                    if (routeDestinationOptionIsPenro(option) || routeDestinationOptionIsRegional(option)) {
                        return {
                            allowed: true,
                            required: false,
                            targetLabel: routeEndorsementTargetLabel(option),
                        };
                    }
                }

                return { allowed: false, required: false, targetLabel: '' };
            }

            function allowsEndorsementLetterAttachment(context, option) {
                return routeEndorsementRequirementRule(context, option).allowed === true;
            }

            function endorsementLetterRequired(context, option) {
                return routeEndorsementRequirementRule(context, option).required === true;
            }

            function routeAttachmentRequirement(context, option) {
                if (allowsPreparedResponseAttachment(context, option)) {
                    return 'prepared_response';
                }
                if (allowsEndorsementLetterAttachment(context, option)) {
                    return 'endorsement_letter';
                }
                return '';
            }

            function updatePreparedResponseAttachmentMeta(context) {
                if (!routeAttachmentMeta || !allowsPreparedResponseAttachment(context)) {
                    return;
                }

                if (routeExistingPreparedResponseLoading) {
                    routeAttachmentMeta.textContent = 'Checking existing prepared response attachments from ' + preparedResponseSourceLabel() + '. Upload is required if none is found.';
                    return;
                }

                if (Number.isFinite(routeExistingPreparedResponseCount) && routeExistingPreparedResponseCount > 0) {
                    routeAttachmentMeta.textContent = 'Optional: a prepared response is already uploaded by ' + preparedResponseSourceLabel() + '. Upload another file only when needed.';
                    return;
                }

                routeAttachmentMeta.textContent = 'Required: upload prepared response file(s). It becomes optional once ' + preparedResponseSourceLabel() + ' already uploaded one.';
            }

            function resetRouteExistingAttachmentsDisplay() {
                routeExistingAttachmentsRequestId += 1;
                routeExistingPreparedResponseLoading = false;
                routeExistingPreparedResponseCount = null;
                if (routeExistingAttachmentsMeta) {
                    routeExistingAttachmentsMeta.hidden = true;
                    routeExistingAttachmentsMeta.textContent = '';
                }
                if (routeExistingAttachmentsList) {
                    routeExistingAttachmentsList.hidden = true;
                    routeExistingAttachmentsList.innerHTML = '';
                }
            }

            function isPreparedResponseRouteAttachment(attachment) {
                if (!attachment || typeof attachment !== 'object') {
                    return false;
                }
                const category = normalizeLabelKey(String(attachment.route_attachment_category || ''));
                if (category === 'prepared_response') {
                    return true;
                }
                if (attachment.is_prepared_response === true || Number(attachment.is_prepared_response || 0) === 1) {
                    return true;
                }
                const uploadedByRoleKey = normalizeLabelKey(String(
                    attachment.uploaded_by_role_key || attachment.uploaded_by_role || ''
                ));
                return uploadedByRoleKey === 'division_chief'
                    || uploadedByRoleKey === 'section_staff'
                    || uploadedByRoleKey === 'cenro_section'
                    || uploadedByRoleKey === 'cenro_unit'
                    || uploadedByRoleKey === 'pamo_officer'
                    || uploadedByRoleKey === 'pamo_unit'
                    || uploadedByRoleKey === 'penro_division'
                    || uploadedByRoleKey === 'penro_section'
                    || uploadedByRoleKey === 'penro_section_unit';
            }

            function isEndorsementRouteAttachment(attachment) {
                if (!attachment || typeof attachment !== 'object') {
                    return false;
                }
                const category = normalizeLabelKey(String(attachment.route_attachment_category || ''));
                if (category === 'endorsement' || category === 'endorsement_letter') {
                    return true;
                }
                const fileNameKey = normalizeLabelKey(String(attachment.file_name || ''));
                if (fileNameKey.indexOf('endorsement') !== -1 || fileNameKey.indexOf('endorse') !== -1) {
                    return true;
                }
                const uploadedByRoleKey = normalizeLabelKey(String(
                    attachment.uploaded_by_role_key || attachment.uploaded_by_role || ''
                ));
                const uploadedByUserId = Number(attachment.uploaded_by_user_id || 0);
                const creatorUserId = Number(documentDetailsCreatorUserId || 0);
                const isCreatorUpload = Number.isFinite(uploadedByUserId)
                    && uploadedByUserId > 0
                    && Number.isFinite(creatorUserId)
                    && creatorUserId > 0
                    && uploadedByUserId === creatorUserId;
                if (isCreatorUpload) {
                    return false;
                }
                return uploadedByRoleKey === 'cenro_admin_record'
                    || uploadedByRoleKey === 'penro_admin_record';
            }

            function currentRouteAttachmentRequirement() {
                const selectedIndex = routeDestinationOffice ? routeDestinationOffice.selectedIndex : -1;
                const selectedOption = routeDestinationOffice && selectedIndex >= 0
                    ? routeDestinationOffice.options[selectedIndex]
                    : null;
                return routeAttachmentRequirement(activeRouteContext, selectedOption);
            }

            function currentRouteAttachmentLabel() {
                return currentRouteAttachmentRequirement() === 'endorsement_letter'
                    ? 'endorsement attachment'
                    : 'prepared response attachment';
            }

            function filterRouteAttachmentsByPurpose(attachments) {
                const safeAttachments = Array.isArray(attachments) ? attachments : [];
                const attachmentRequirement = currentRouteAttachmentRequirement();
                if (attachmentRequirement === 'prepared_response') {
                    return safeAttachments.filter(function (attachment) {
                        return isPreparedResponseRouteAttachment(attachment)
                            && attachmentBelongsToCurrentRouteOfficeChain(attachment);
                    });
                }
                if (attachmentRequirement === 'endorsement_letter') {
                    return safeAttachments.filter(function (attachment) {
                        return isEndorsementRouteAttachment(attachment)
                            && attachmentBelongsToCurrentRouteOffice(attachment);
                    });
                }
                if (!filterRouteExistingAttachmentsToPreparedResponse) {
                    return safeAttachments;
                }
                return safeAttachments.filter(isPreparedResponseRouteAttachment);
            }

            function renderRouteExistingAttachments(attachments) {
                if (!routeExistingAttachmentsMeta || !routeExistingAttachmentsList) {
                    return;
                }
                const allAttachments = Array.isArray(attachments) ? attachments : [];
                const attachmentRequirement = currentRouteAttachmentRequirement();
                const safeAttachments = filterRouteAttachmentsByPurpose(allAttachments);
                routeExistingPreparedResponseCount = safeAttachments.length;
                const visibleAttachments = attachmentRequirement === 'prepared_response'
                    ? allAttachments.filter(isPreparedResponseRouteAttachment)
                    : safeAttachments;
                const attachmentLabel = attachmentRequirement === 'endorsement_letter'
                    ? 'endorsement attachment'
                    : (filterRouteExistingAttachmentsToPreparedResponse || attachmentRequirement === 'prepared_response'
                        ? 'prepared response attachment'
                        : 'attachment');
                routeExistingAttachmentsList.innerHTML = '';
                if (visibleAttachments.length === 0) {
                    routeExistingAttachmentsMeta.hidden = false;
                    routeExistingAttachmentsMeta.textContent = attachmentRequirement === 'endorsement_letter'
                        ? 'No endorsement attachment from your current office found yet. Upload is optional before forwarding.'
                        : (filterRouteExistingAttachmentsToPreparedResponse
                            ? 'No prepared response attachment from ' + preparedResponseSourceLabel() + ' found yet. Upload is required before forwarding.'
                            : 'No existing attachment found yet. You can forward without uploading another file.');
                    routeExistingAttachmentsList.hidden = true;
                    updatePreparedResponseAttachmentMeta(activeRouteContext);
                    return;
                }

                routeExistingAttachmentsMeta.hidden = false;
                if (attachmentRequirement === 'prepared_response') {
                    if (routeExistingPreparedResponseCount > 0) {
                        routeExistingAttachmentsMeta.textContent = routeExistingPreparedResponseCount === 1
                            ? '1 existing prepared response attachment from ' + preparedResponseSourceLabel() + ' found. Uploading another file is optional.'
                            : String(routeExistingPreparedResponseCount) + ' existing prepared response attachments from ' + preparedResponseSourceLabel() + ' found. Uploading another file is optional.';
                    } else {
                        routeExistingAttachmentsMeta.textContent = visibleAttachments.length === 1
                            ? 'No prepared response attachment from ' + preparedResponseSourceLabel() + ' counts for this route yet. 1 prepared response from another office is listed below for viewing.'
                            : 'No prepared response attachment from ' + preparedResponseSourceLabel() + ' counts for this route yet. ' + String(visibleAttachments.length) + ' prepared responses from other offices are listed below for viewing.';
                    }
                } else {
                    routeExistingAttachmentsMeta.textContent = safeAttachments.length === 1
                        ? '1 existing ' + attachmentLabel + ' from your current office found. Uploading another file is optional.'
                        : String(safeAttachments.length) + ' existing ' + attachmentLabel + 's from your current office found. Uploading another file is optional.';
                }

                const maxVisible = 10;
                visibleAttachments.slice(0, maxVisible).forEach(function (attachment) {
                    const item = document.createElement('li');
                    const fileName = String(attachment && attachment.file_name ? attachment.file_name : 'Attachment').trim() || 'Attachment';
                    const attachmentId = Number(attachment && attachment.id ? attachment.id : 0);
                    const canManage = !!(attachment && attachment.can_manage_route_attachment);
                    const uploadedByOfficeName = String(attachment && attachment.uploaded_by_office_name ? attachment.uploaded_by_office_name : '').trim();
                    const uploadedByRole = String(attachment && attachment.uploaded_by_role ? attachment.uploaded_by_role : '').trim();
                    const versionNumber = Number(attachment && attachment.version_number ? attachment.version_number : 0);
                    const descriptorParts = [];
                    if (Number.isFinite(versionNumber) && versionNumber > 0) {
                        descriptorParts.push('v' + String(versionNumber));
                    }
                    if (uploadedByRole !== '') {
                        descriptorParts.push(uploadedByRole);
                    }
                    if (uploadedByOfficeName !== '') {
                        descriptorParts.push(uploadedByOfficeName);
                    }
                    const descriptor = descriptorParts.length > 0 ? (' (' + descriptorParts.join(', ') + ')') : '';

                    const contentRow = document.createElement('div');
                    contentRow.className = 'route-existing-attachment-row';
                    const contentText = document.createElement('span');
                    contentText.className = 'route-existing-attachment-text';
                    const fileUrl = String(attachment && attachment.file_url ? attachment.file_url : '').trim();
                    if (fileUrl !== '') {
                        const link = document.createElement('a');
                        link.href = fileUrl;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.textContent = fileName;
                        contentText.appendChild(link);
                        if (descriptor !== '') {
                            const textNode = document.createTextNode(descriptor);
                            contentText.appendChild(textNode);
                        }
                    } else {
                        contentText.textContent = fileName + descriptor;
                    }
                    contentRow.appendChild(contentText);

                    if (canManage && Number.isFinite(attachmentId) && attachmentId > 0) {
                        const actionGroup = document.createElement('div');
                        actionGroup.className = 'route-existing-attachment-actions';
                        const replacementInput = document.createElement('input');
                        replacementInput.type = 'file';
                        replacementInput.hidden = true;
                        replacementInput.addEventListener('change', function () {
                            const replacementFile = replacementInput.files && replacementInput.files.length > 0
                                ? replacementInput.files[0]
                                : null;
                            if (!replacementFile) {
                                return;
                            }
                            const activeDocumentId = Number(activeRouteContext && activeRouteContext.documentId ? activeRouteContext.documentId : 0);
                            if (!Number.isFinite(activeDocumentId) || activeDocumentId <= 0) {
                                showAlertDialog('Missing document reference for attachment replace.');
                                replacementInput.value = '';
                                return;
                            }

                            replaceButton.disabled = true;
                            deleteButton.disabled = true;
                            sendDocumentAction({
                                action: 'REPLACE_ROUTE_ATTACHMENT',
                                documentId: activeDocumentId,
                                attachmentId: attachmentId,
                                attachmentFiles: [replacementFile],
                                preconditionVersion: Number(activeRouteContext && activeRouteContext.documentVersion ? activeRouteContext.documentVersion : 0),
                            }).then(function (result) {
                                if (result && result.queued_offline) {
                                    showAlertDialog(result.message || 'Offline mode: action was queued and will sync automatically.');
                                    return;
                                }

                                const nextVersion = Number(result && result.current_version ? result.current_version : 0);
                                if (activeRouteContext && Number.isFinite(nextVersion) && nextVersion > 0) {
                                    activeRouteContext.documentVersion = nextVersion;
                                }
                                loadRouteExistingAttachments(activeRouteContext);
                                syncRouteAttachmentUi(activeRouteContext);
                                showAlertDialog(result.message || 'Attachment replaced.');
                            }).catch(function (error) {
                                showAlertDialog(error.message || 'Unable to replace attachment.');
                            }).finally(function () {
                                replacementInput.value = '';
                                replaceButton.disabled = false;
                                deleteButton.disabled = false;
                            });
                        });

                        const replaceButton = document.createElement('button');
                        replaceButton.type = 'button';
                        replaceButton.className = 'route-existing-attachment-replace';
                        replaceButton.textContent = 'Replace';
                        replaceButton.title = 'Replace this ' + attachmentLabel;
                        replaceButton.addEventListener('click', function () {
                            replacementInput.click();
                        });

                        const deleteButton = document.createElement('button');
                        deleteButton.type = 'button';
                        deleteButton.className = 'route-existing-attachment-delete';
                        deleteButton.textContent = 'Remove';
                        deleteButton.title = 'Remove this ' + attachmentLabel;
                        deleteButton.addEventListener('click', function () {
                            const activeDocumentId = Number(activeRouteContext && activeRouteContext.documentId ? activeRouteContext.documentId : 0);
                            if (!Number.isFinite(activeDocumentId) || activeDocumentId <= 0) {
                                showAlertDialog('Missing document reference for attachment delete.');
                                return;
                            }

                            showConfirmDialog(
                                'Remove ' + attachmentLabel + ' "' + fileName + '"?',
                                'Confirm Attachment Remove',
                                'Remove'
                            ).then(function (confirmed) {
                                if (!confirmed) {
                                    return;
                                }

                                replaceButton.disabled = true;
                                deleteButton.disabled = true;
                                sendDocumentAction({
                                    action: 'ROUTE_DELETE_ATTACHMENT',
                                    documentId: activeDocumentId,
                                    attachmentId: attachmentId,
                                    preconditionVersion: Number(activeRouteContext && activeRouteContext.documentVersion ? activeRouteContext.documentVersion : 0),
                                }).then(function (result) {
                                    if (result && result.queued_offline) {
                                        showAlertDialog(result.message || 'Offline mode: action was queued and will sync automatically.');
                                        return;
                                    }

                                    const nextVersion = Number(result && result.current_version ? result.current_version : 0);
                                    if (activeRouteContext && Number.isFinite(nextVersion) && nextVersion > 0) {
                                        activeRouteContext.documentVersion = nextVersion;
                                    }
                                    loadRouteExistingAttachments(activeRouteContext);
                                    syncRouteAttachmentUi(activeRouteContext);
                                    showAlertDialog(result.message || 'Attachment removed.');
                                }).catch(function (error) {
                                    showAlertDialog(error.message || 'Unable to remove attachment.');
                                }).finally(function () {
                                    replaceButton.disabled = false;
                                    deleteButton.disabled = false;
                                });
                            });
                        });
                        actionGroup.appendChild(replacementInput);
                        actionGroup.appendChild(replaceButton);
                        actionGroup.appendChild(deleteButton);
                        contentRow.appendChild(actionGroup);
                    }

                    item.appendChild(contentRow);
                    routeExistingAttachmentsList.appendChild(item);
                });

                if (visibleAttachments.length > maxVisible) {
                    const moreItem = document.createElement('li');
                    moreItem.textContent = '+' + String(visibleAttachments.length - maxVisible) + ' more attachment(s). Open View Details to see all.';
                    routeExistingAttachmentsList.appendChild(moreItem);
                }

                routeExistingAttachmentsList.hidden = false;
                updatePreparedResponseAttachmentMeta(activeRouteContext);
            }

            function syncRouteAttachmentUi(context) {
                if (!routeAttachmentWrap || !routeAttachmentInput || !routeAttachmentLabel || !routeAttachmentMeta) {
                    return '';
                }

                const selectedIndex = routeDestinationOffice ? routeDestinationOffice.selectedIndex : -1;
                const selectedOption = routeDestinationOffice && selectedIndex >= 0
                    ? routeDestinationOffice.options[selectedIndex]
                    : null;
                const attachmentKind = routeAttachmentRequirement(context, selectedOption);
                const showAttachment = attachmentKind !== '';
                routeAttachmentWrap.hidden = !showAttachment;

                if (!showAttachment) {
                    resetRouteExistingAttachmentsDisplay();
                    routeAttachmentInput.value = '';
                    return '';
                }

                if (attachmentKind === 'prepared_response') {
                    if (isPenroDivisionRole) {
                        routeAttachmentLabel.textContent = 'Prepared Response (Send Back to PENRO Officer)';
                    } else if (isPenroSectionRole || isPenroSectionUnitRole) {
                        routeAttachmentLabel.textContent = 'Prepared Response (Forward to PENRO Division)';
                    } else if (isCenroSectionRole) {
                        routeAttachmentLabel.textContent = 'Prepared Response (Send Back to CENRO Officer)';
                    } else if (isCenroUnitRole) {
                        routeAttachmentLabel.textContent = 'Prepared Response (Forward to CENRO Section)';
                    } else if (currentRoleKey === 'DIVISION_CHIEF') {
                        routeAttachmentLabel.textContent = 'Prepared Response (Send Back to ARD)';
                    } else {
                        routeAttachmentLabel.textContent = 'Prepared Response (Send Back to Division Chief)';
                    }
                    routeExistingPreparedResponseCount = null;
                    routeExistingPreparedResponseLoading = true;
                    updatePreparedResponseAttachmentMeta(context);
                    loadRouteExistingAttachments(context);
                    routeAttachmentInput.value = '';
                    return attachmentKind;
                }

                if (attachmentKind === 'endorsement_letter') {
                    routeAttachmentLabel.textContent = 'Endorsement Letter (Attachment)';
                    const endorsementRule = routeEndorsementRequirementRule(context, selectedOption);
                    const targetLabel = endorsementRule.targetLabel || 'destination office';
                    const isRequired = endorsementLetterRequired(context, selectedOption);
                    routeExistingPreparedResponseCount = null;
                    routeExistingPreparedResponseLoading = true;
                    routeAttachmentMeta.textContent = isRequired
                        ? 'Required: upload endorsement letter before routing to ' + targetLabel + ', unless your current office already uploaded one.'
                        : 'Optional: upload endorsement letter when needed before routing to ' + targetLabel + '.';
                    loadRouteExistingAttachments(context);
                    routeAttachmentInput.value = '';
                    return attachmentKind;
                }

                resetRouteExistingAttachmentsDisplay();
                routeAttachmentInput.value = '';
                return '';
            }

            function loadRouteExistingAttachments(context) {
                if (!routeExistingAttachmentsMeta || !routeExistingAttachmentsList) {
                    return;
                }
                routeExistingPreparedResponseLoading = true;
                routeExistingPreparedResponseCount = null;
                updatePreparedResponseAttachmentMeta(context);

                const docId = Number(context && context.documentId ? context.documentId : 0);
                const trackingId = String(context && context.trackingId ? context.trackingId : '').trim();
                if ((!Number.isFinite(docId) || docId <= 0) && trackingId === '') {
                    routeExistingPreparedResponseLoading = false;
                    renderRouteExistingAttachments([]);
                    return;
                }
                if (!documentDetailsPath) {
                    routeExistingPreparedResponseLoading = false;
                    renderRouteExistingAttachments([]);
                    return;
                }

                const requestId = ++routeExistingAttachmentsRequestId;
                routeExistingAttachmentsMeta.hidden = false;
                routeExistingAttachmentsMeta.textContent = currentRouteAttachmentRequirement() === 'endorsement_letter'
                    ? 'Checking existing endorsement attachments...'
                    : (filterRouteExistingAttachmentsToPreparedResponse
                        ? 'Checking existing prepared response attachments...'
                        : 'Checking existing attachments...');
                routeExistingAttachmentsList.hidden = true;
                routeExistingAttachmentsList.innerHTML = '';

                const requestUrl = new URL(String(documentDetailsPath || ''), window.location.origin);
                if (Number.isFinite(docId) && docId > 0) {
                    requestUrl.searchParams.set('document_id', String(docId));
                }
                if (trackingId !== '') {
                    requestUrl.searchParams.set('tracking_id', trackingId);
                }
                requestUrl.searchParams.set('t', String(Date.now()));

                fetch(requestUrl.toString(), {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json' },
                }).then(function (response) {
                    return response.json().catch(function () {
                        return { ok: false, message: 'Unexpected server response.' };
                    }).then(function (json) {
                        if (!response.ok || !json || json.ok !== true) {
                            throw new Error(String(json && json.message ? json.message : 'Unable to load attachments.'));
                        }
                        return json;
                    });
                }).then(function (payload) {
                    if (requestId !== routeExistingAttachmentsRequestId) {
                        return;
                    }
                    routeExistingPreparedResponseLoading = false;
                    const attachments = Array.isArray(payload && payload.attachments) ? payload.attachments : [];
                    renderRouteExistingAttachments(attachments);
                }).catch(function () {
                    if (requestId !== routeExistingAttachmentsRequestId) {
                        return;
                    }
                    routeExistingPreparedResponseLoading = false;
                    routeExistingPreparedResponseCount = null;
                    updatePreparedResponseAttachmentMeta(context);
                    routeExistingAttachmentsMeta.hidden = false;
                    routeExistingAttachmentsMeta.textContent = 'Unable to load existing attachments right now.';
                    routeExistingAttachmentsList.hidden = true;
                    routeExistingAttachmentsList.innerHTML = '';
                });
            }

            function actionLabel(action) {
                const map = {
                    'FORWARD': 'Forward',
                    'REROUTE': 'Reroute',
                    'RETURN': 'Return',
                    'OVERRIDE': 'Override',
                    'RECEIVE': 'Receive',
                    'SIGN': 'Sign',
                    'UNSIGN': 'Undo Sign',
                    'PENDING': 'Pending',
                    'RELEASE': 'Release',
                    'COMPLETE': 'Released',
                    'EDIT': 'Edit',
                    'DELETE': 'Delete',
                };
                if (Object.prototype.hasOwnProperty.call(map, action)) {
                    return map[action];
                }
                const raw = String(action || '').toLowerCase();
                return raw.charAt(0).toUpperCase() + raw.slice(1);
            }

            function redirectToRecordsUnitActionStamp(action, trackingId) {
                const rawRoleKey = String(currentRoleKeyRaw || currentRoleKey || '').toUpperCase();
                const canUseActionStamp = rawRoleKey === 'RECORDS_UNIT'
                    || rawRoleKey === 'CENRO_ADMIN_RECORD'
                    || rawRoleKey === 'PENRO_ADMIN_RECORD'
                    || rawRoleKey === 'PAMO_ADMIN';
                if (!canUseActionStamp) {
                    return false;
                }
                if (String(actionStampWorkspacePath || '').trim() === '') {
                    return false;
                }

                const actionKey = String(action || '').toUpperCase();
                let stampType = '';
                if (actionKey === 'RECEIVE') {
                    stampType = 'received';
                } else if (actionKey === 'RELEASE' || actionKey === 'COMPLETE') {
                    stampType = 'released';
                } else {
                    return false;
                }

                try {
                    const targetUrl = new URL(String(actionStampWorkspacePath), window.location.origin);
                    targetUrl.searchParams.set('stamp_type', stampType);
                    const normalizedTracking = String(trackingId || '').trim();
                    if (normalizedTracking !== '') {
                        targetUrl.searchParams.set('tracking_id', normalizedTracking);
                    }
                    window.location.href = targetUrl.toString();
                    return true;
                } catch (error) {
                    return false;
                }
            }

            function routeActionLabel(context) {
                const action = String(context && context.action ? context.action : '').toUpperCase();
                const routeMode = normalizeLabelKey(context && context.routeMode ? context.routeMode : '');
                if (routeContextSupportsDualRelease(context)) {
                    return resolveRouteReleaseMode(context) === 'complete_local' ? 'Complete Here' : 'Release';
                }
                if (action === 'FORWARD' && isCenroSectionRole && routeMode === 'cenro_officer_back') {
                    return 'Send Back to CENRO Officer';
                }
                if (action === 'FORWARD' && (isCenroOfficerRole || isPenroOfficerRole || isPamoOfficerRole) && routeMode === 'admin_record_back') {
                    return 'Send Back to Admin Record';
                }
                if (action === 'FORWARD' && currentRoleKey === 'ORED' && routeMode === 'records_unit_back') {
                    return 'Send Back to RECORDS-UNIT';
                }
                if (action === 'FORWARD' && currentRoleKey === 'DIVISION_CHIEF' && !isCenroSectionRole && routeMode === 'ard_back') {
                    if (isPenroDivisionRole || isPenroSectionRole) {
                        return 'Send Back to PENRO Officer';
                    }
                    return 'Send Back to ARD';
                }
                if (action === 'FORWARD' && isArdRole && routeMode === 'ored_back') {
                    return 'Send Back to ORED';
                }
                return actionLabel(action);
            }

            function routeContextSupportsDualRelease(context) {
                const action = String(context && context.action ? context.action : '').toUpperCase();
                if (action !== 'RELEASE') {
                    return false;
                }
                return isCenroAdminRecordRole || isPenroAdminRecordRole || isPamoAdminRole;
            }

            function normalizeRouteReleaseMode(value) {
                return String(value || '').trim().toLowerCase();
            }

            function routeCanCompleteLocally(context) {
                if (!routeContextSupportsDualRelease(context)) {
                    return false;
                }
                const originOfficeId = Number(context && context.originOfficeId ? context.originOfficeId : 0);
                const contextOfficeId = routeContextOfficeId(context);
                return Number.isFinite(originOfficeId)
                    && originOfficeId > 0
                    && Number.isFinite(contextOfficeId)
                    && contextOfficeId > 0
                    && originOfficeId === contextOfficeId;
            }

            function resolveRouteReleaseMode(context) {
                if (!routeContextSupportsDualRelease(context)) {
                    return '';
                }
                const selectedMode = normalizeRouteReleaseMode(routeReleaseMode ? routeReleaseMode.value : '');
                if (selectedMode === 'complete_local' && routeCanCompleteLocally(context)) {
                    return 'complete_local';
                }
                if (selectedMode === 'send_to_office') {
                    return 'send_to_office';
                }
                return routeCanCompleteLocally(context) ? 'complete_local' : 'send_to_office';
            }

            function routeReleaseRequiresDestination(context) {
                if (!routeContextSupportsDualRelease(context)) {
                    return true;
                }
                return resolveRouteReleaseMode(context) === 'send_to_office';
            }

            function routeActionAllowsSubjectChange(context) {
                const action = String(context && context.action ? context.action : '').toUpperCase();
                if (!['FORWARD', 'REROUTE', 'RETURN'].includes(action)) {
                    return false;
                }
                return currentRoleKey === 'DIVISION_CHIEF' || currentRoleKey === 'SECTION_STAFF';
            }

            function normalizeOfficePolicyKey(value) {
                return normalizeLabelKey(value).replace(/[^a-z0-9]/g, '');
            }

            function divisionTrackFromOfficeName(officeName) {
                const key = normalizeOfficePolicyKey(String(officeName || ''));
                if (key === '') {
                    return '';
                }

                const tsKeys = [
                    'licensespatentsdeedsdivision',
                    'surveysmappingdivision',
                    'conservationdevtdivision',
                    'enforcementdivision',
                ];
                const msKeys = [
                    'legaldivision',
                    'planningandmgtdivision',
                    'planningandmanagementdivisionpmd',
                    'administrativedivision',
                    'financedivision',
                ];
                if (tsKeys.indexOf(key) !== -1) {
                    return 'TS';
                }
                if (msKeys.indexOf(key) !== -1) {
                    return 'MS';
                }
                return '';
            }

            function officeNameMatchesRbco(value) {
                const key = normalizeOfficePolicyKey(String(value || ''));
                return key === 'rbcoriverbasincontroloffice'
                    || key === 'riverbasincontroloffice';
            }

            function findRouteOfficeById(officeId) {
                const normalizedOfficeId = Number(officeId || 0);
                if (!Number.isFinite(normalizedOfficeId) || normalizedOfficeId <= 0) {
                    return null;
                }

                const candidateCollections = [];
                if (Array.isArray(routeOffices)) {
                    candidateCollections.push(routeOffices);
                }
                if (Array.isArray(routeFallbackOffices) && routeFallbackOffices.length > 0) {
                    candidateCollections.push(routeFallbackOffices);
                } else if (routeFallbackOffice && typeof routeFallbackOffice === 'object') {
                    candidateCollections.push([routeFallbackOffice]);
                }

                for (let collectionIndex = 0; collectionIndex < candidateCollections.length; collectionIndex += 1) {
                    const collection = candidateCollections[collectionIndex];
                    const match = collection.find(function (candidate) {
                        return Number(candidate && candidate.id ? candidate.id : 0) === normalizedOfficeId;
                    });
                    if (match) {
                        return match;
                    }
                }

                return null;
            }

            function officeTrackFromRouteOffice(office) {
                const officeLevel = String(office && office.level ? office.level : '').toUpperCase();
                const officeName = String(office && office.name ? office.name : '');

                if (officeLevel === 'DIVISION') {
                    return divisionTrackFromOfficeName(officeName);
                }

                if (officeLevel === 'SECTION' || officeLevel === 'UNIT') {
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    if (Number.isFinite(parentOfficeId) && parentOfficeId > 0) {
                        const parentOffice = findRouteOfficeById(parentOfficeId);
                        if (parentOffice) {
                            const parentLevel = String(parentOffice && parentOffice.level ? parentOffice.level : '').toUpperCase();
                            if (parentLevel === 'DIVISION') {
                                return divisionTrackFromOfficeName(String(parentOffice && parentOffice.name ? parentOffice.name : ''));
                            }

                            const parentTrack = ardTrackFromOfficeName(String(parentOffice && parentOffice.name ? parentOffice.name : ''));
                            if (parentTrack !== '') {
                                return parentTrack;
                            }
                        }
                    }
                }

                return ardTrackFromOfficeName(officeName);
            }

            function ardTrackFromOfficeName(officeName) {
                const name = String(officeName || '').toUpperCase();
                const key = normalizeOfficePolicyKey(name);
                if (
                    key.indexOf('ardts') !== -1
                    || (name.indexOf('ASSISTANT REGIONAL DIRECTOR') !== -1 && name.indexOf('TECHNICAL') !== -1)
                    || name.indexOf('TECHNICAL SERVICES') !== -1
                ) {
                    return 'TS';
                }
                if (
                    key.indexOf('ardms') !== -1
                    || (name.indexOf('ASSISTANT REGIONAL DIRECTOR') !== -1 && name.indexOf('MANAGEMENT') !== -1)
                    || name.indexOf('MANAGEMENT SERVICES') !== -1
                ) {
                    return 'MS';
                }
                return '';
            }

            function ardTrackFromRoleKey(roleKey) {
                const normalized = String(roleKey || '').toUpperCase();
                if (normalized === 'ARD_TS') {
                    return 'TS';
                }
                if (normalized === 'ARD_MS') {
                    return 'MS';
                }
                return '';
            }

            function isOredPostSignStatus(statusValue) {
                const normalizedStatus = normalizeLabelKey(String(statusValue || ''));
                return queueStatusIsSignedCompleted(normalizedStatus);
            }

            function routeContextHasSignedPhase(context) {
                return isOredPostSignStatus(context && context.currentStatus ? context.currentStatus : '')
                    || Boolean(context && context.hasSignedAction);
            }

            function officeNameMatchesRecordsUnit(value) {
                const normalized = String(value || '').toUpperCase();
                return normalized.indexOf('PACDO') !== -1
                    || normalized.indexOf('RECORDS-UNIT') !== -1
                    || normalized.indexOf('RECORDS_UNIT') !== -1
                    || normalized.indexOf('RECORDS UNIT') !== -1
                    || normalized.indexOf('RECORDSUNIT') !== -1;
            }

            function isOfficerAdminRecordBackRoute(context) {
                const action = String(context && context.action ? context.action : '').toUpperCase();
                const routeMode = normalizeLabelKey(context && context.routeMode ? context.routeMode : '');
                return action === 'FORWARD'
                    && routeMode === 'admin_record_back'
                    && (isCenroOfficerRole || isPenroOfficerRole || isPamoOfficerRole);
            }

            function routeDestinationFilterUiPreset(context) {
                if (isPamoAdminRole) {
                    const action = String(context && context.action ? context.action : '').toUpperCase();
                    if (action === 'FORWARD') {
                        return 'pamo_admin';
                    }
                }
                if (isPenroAdminRecordRole) {
                    return 'penro_admin_record';
                }
                if (isCenroAdminRecordRole) {
                    return 'cenro_admin_record';
                }
                if (isPamoOfficerRole) {
                    const action = String(context && context.action ? context.action : '').toUpperCase();
                    if (action === 'REROUTE' && !isOredPostSignStatus(context && context.currentStatus ? context.currentStatus : '')) {
                        return 'pamo_officer_reroute';
                    }
                    if (action === 'FORWARD' && !isOredPostSignStatus(context && context.currentStatus ? context.currentStatus : '')) {
                        return 'pamo_officer_forward';
                    }
                }
                if (isPenroOfficerRole) {
                    const action = String(context && context.action ? context.action : '').toUpperCase();
                    if (action === 'REROUTE') {
                        return 'penro_officer_reroute';
                    }
                    if (action === 'FORWARD' && !isOredPostSignStatus(context && context.currentStatus ? context.currentStatus : '')) {
                        return 'penro_officer_forward';
                    }
                }
                if (isCenroOfficerRole) {
                    const action = String(context && context.action ? context.action : '').toUpperCase();
                    if (action === 'REROUTE') {
                        return 'cenro_officer_reroute';
                    }
                    if (action === 'FORWARD' && !isOredPostSignStatus(context && context.currentStatus ? context.currentStatus : '')) {
                        return 'cenro_officer_forward';
                    }
                }
                if (currentRoleKey === 'ORED' && !isCenroOfficerRole && !isPamoOfficerRole) {
                    if (isRegionalOredReviewer) {
                        return 'default';
                    }
                    const action = String(context && context.action ? context.action : '').toUpperCase();
                    if (action === 'REROUTE') {
                        return 'ored';
                    }
                    if (action === 'FORWARD' && !isOredPostSignStatus(context && context.currentStatus ? context.currentStatus : '')) {
                        return 'ored';
                    }
                }
                return 'default';
            }

            function syncRouteDestinationFilterLabels(context) {
                if (!routeDestinationTypeFilterWrap) {
                    return;
                }
                const preset = routeDestinationFilterUiPreset(context);
                const helperMeta = routeDestinationTypeFilterWrap.querySelector('.action-modal-meta');
                const filterButtons = routeDestinationFilterButtons.slice(0, 3);
                if (filterButtons.length < 3) {
                    return;
                }
                let showSecondFilterButton = true;
                let showThirdFilterButton = true;
                if (preset === 'pamo_officer_forward' || preset === 'pamo_officer_reroute') {
                    showSecondFilterButton = false;
                    showThirdFilterButton = false;
                } else if (preset === 'cenro_officer_forward' || preset === 'penro_officer_forward') {
                    showThirdFilterButton = false;
                } else if (preset === 'cenro_officer_reroute' || preset === 'penro_officer_reroute') {
                    showSecondFilterButton = false;
                    showThirdFilterButton = false;
                }
                filterButtons[1].hidden = !showSecondFilterButton;
                filterButtons[1].disabled = !showSecondFilterButton;
                filterButtons[2].hidden = !showThirdFilterButton;
                filterButtons[2].disabled = !showThirdFilterButton;
                if (!showSecondFilterButton) {
                    filterButtons[1].classList.remove('is-active');
                    filterButtons[1].setAttribute('aria-pressed', 'false');
                }
                if (!showThirdFilterButton) {
                    filterButtons[2].classList.remove('is-active');
                    filterButtons[2].setAttribute('aria-pressed', 'false');
                }

                if (preset === 'penro_admin_record') {
                    if (helperMeta) {
                        helperMeta.textContent = 'Choose destination path: Internal (PENRO Officer) is recommended. CENRO or Regional (PACDO) are optional.';
                    }
                    filterButtons[0].textContent = 'Internal (Recommended)';
                    filterButtons[1].textContent = 'CENRO (Optional)';
                    filterButtons[2].textContent = 'Regional (Optional)';
                    return;
                }

                if (preset === 'cenro_admin_record') {
                    if (helperMeta) {
                        helperMeta.textContent = 'Choose destination path: Internal is recommended. PENRO or Regional (PACDO) are optional.';
                    }
                    filterButtons[0].textContent = 'Internal (Recommended)';
                    filterButtons[1].textContent = 'PENRO (Optional)';
                    filterButtons[2].textContent = 'Regional (Optional)';
                    return;
                }

                if (preset === 'pamo_admin') {
                    if (helperMeta) {
                        helperMeta.innerHTML = 'Choose destination path: <strong>PASU first</strong> for internal PAMO flow. CENRO and PENRO options are limited to the offices allowed for your PAMO jurisdiction.';
                    }
                    filterButtons[0].textContent = 'PASU (Recommended)';
                    filterButtons[1].textContent = 'CENRO';
                    filterButtons[2].textContent = 'PENRO';
                    return;
                }

                if (preset === 'ored') {
                    if (helperMeta) {
                        helperMeta.innerHTML = 'Choose path: <strong>ARD first</strong> is recommended. Direct Division or Section is a bypass and requires reason.';
                    }
                    filterButtons[0].textContent = 'ARD (Recommended)';
                    filterButtons[1].textContent = 'Division (Bypass)';
                    filterButtons[2].textContent = 'Section (Bypass)';
                    return;
                }

                if (preset === 'pamo_officer_forward' || preset === 'pamo_officer_reroute') {
                    if (helperMeta) {
                        helperMeta.innerHTML = preset === 'pamo_officer_reroute'
                            ? 'Choose path: <strong>PAMO Unit only</strong> for PASU reroute.'
                            : 'Choose path: <strong>Section only</strong> for the PASU internal flow.';
                    }
                    filterButtons[0].textContent = preset === 'pamo_officer_reroute' ? 'PAMO Unit' : 'Section';
                    filterButtons[1].textContent = 'Division';
                    filterButtons[2].textContent = 'ARD';
                    return;
                }

                if (preset === 'cenro_officer_reroute') {
                    if (helperMeta) {
                        helperMeta.innerHTML = 'Choose path: <strong>CENRO Section only</strong> for CENRO Officer reroute.';
                    }
                    filterButtons[0].textContent = 'Section (Recommended)';
                    filterButtons[1].textContent = 'Unit (Bypass)';
                    filterButtons[2].textContent = 'Admin Record (Bypass)';
                    return;
                }

                if (preset === 'penro_officer_forward') {
                    if (helperMeta) {
                        helperMeta.textContent = 'Choose path: Division is recommended. Direct Section/Unit is a bypass and requires reason.';
                    }
                    filterButtons[0].textContent = 'Division (Recommended)';
                    filterButtons[1].textContent = 'Section/Unit (Bypass)';
                    return;
                }
                if (preset === 'penro_officer_reroute') {
                    if (helperMeta) {
                        helperMeta.textContent = 'Reroute Document: Must select a Division under your PENRO jurisdiction.';
                    }
                    filterButtons[0].textContent = 'Division';
                    return;
                }

                if (preset === 'cenro_officer_forward') {
                    if (helperMeta) {
                        helperMeta.innerHTML = 'Choose path: <strong>CENRO Section first</strong> is recommended. Direct CENRO Unit is a bypass and requires reason.';
                    }
                    filterButtons[0].textContent = 'Section (Recommended)';
                    filterButtons[1].textContent = 'Unit (Bypass)';
                    filterButtons[2].textContent = 'Section (Bypass)';
                    return;
                }

                if (helperMeta) {
                    helperMeta.innerHTML = 'Choose path: <strong>ARD first</strong> is recommended. Direct Division or Section is a bypass and requires reason.';
                }
                filterButtons[0].textContent = 'ARD (Recommended)';
                filterButtons[1].textContent = 'Division (Bypass)';
                filterButtons[2].textContent = 'Section (Bypass)';
            }

            function routeSupportsDestinationTypeFilter(context) {
                const action = String(context && context.action ? context.action : '').toUpperCase();
                if (isOfficerAdminRecordBackRoute(context)) {
                    return false;
                }
                if (isPamoAdminRole) {
                    return action === 'FORWARD';
                }
                if (isPenroAdminRecordRole) {
                    return action === 'FORWARD';
                }
                if (isCenroAdminRecordRole) {
                    return action === 'FORWARD';
                }
                if (isPamoOfficerRole) {
                    return (action === 'FORWARD' || action === 'REROUTE')
                        && !isOredPostSignStatus(context && context.currentStatus ? context.currentStatus : '');
                }
                if (isCenroOfficerRole || isPenroOfficerRole) {
                    if (action === 'REROUTE') {
                        return true;
                    }
                    if (action === 'FORWARD') {
                        return !isOredPostSignStatus(context && context.currentStatus ? context.currentStatus : '');
                    }
                    return false;
                }
                if (currentRoleKey === 'ORED' && !isCenroOfficerRole && !isPamoOfficerRole) {
                    if (isRegionalOredReviewer) {
                        return false;
                    }
                    if (action === 'REROUTE') {
                        return true;
                    }
                    if (action !== 'FORWARD') {
                        return false;
                    }
                    return !isOredPostSignStatus(context && context.currentStatus ? context.currentStatus : '');
                }
                return false;
            }

            function officeMatchesDestinationTypeFilter(office, filterMode) {
                const normalizedFilter = normalizeLabelKey(String(filterMode || ''));
                if (normalizedFilter === '' || normalizedFilter === 'all') {
                    return true;
                }

                const officeName = String(office && office.name ? office.name : '');
                const officeNameUpper = officeName.toUpperCase();
                const officeLevel = String(office && office.level ? office.level : '').toUpperCase();

                if (isPamoAdminRole) {
                    const isPasuOffice = officeLevel === 'PASU_OFFICER'
                        || officeNameUpper.indexOf(' - PASU') !== -1
                        || (officeNameUpper.indexOf('PASU') !== -1 && officeNameUpper.indexOf('PROTECTED') !== -1);
                    const isCenroOffice = officeLevel === 'CENRO_ADMIN_RECORD'
                        || officeNameUpper.indexOf('CENRO ADMIN') !== -1;
                    const isPenroOffice = officeLevel === 'PENRO_ADMIN_RECORD'
                        || officeNameUpper.indexOf('PENRO ADMIN') !== -1;
                    if (normalizedFilter === 'ard') {
                        return isPasuOffice;
                    }
                    if (normalizedFilter === 'division') {
                        return isCenroOffice;
                    }
                    if (normalizedFilter === 'section') {
                        return isPenroOffice;
                    }
                    return true;
                }

                if (isPenroAdminRecordRole) {
                    const rawLevel = String(office && office.level ? office.level : '').trim().toUpperCase();
                    const isInternalOffice = rawLevel === 'PENRO_OFFICER'
                        || (officeNameUpper.indexOf(' - OFFICER') !== -1 && officeNameUpper.indexOf('PENRO') !== -1);
                    const isCenroOffice = rawLevel === 'CENRO_ADMIN_RECORD'
                        || officeNameUpper.indexOf('CENRO ADMIN') !== -1;
                    const isRegionalOffice = officeNameMatchesRecordsUnit(officeNameUpper);
                    if (normalizedFilter === 'ard') {
                        return isInternalOffice;
                    }
                    if (normalizedFilter === 'division') {
                        return isCenroOffice;
                    }
                    if (normalizedFilter === 'section') {
                        return isRegionalOffice;
                    }
                    return true;
                }

                if (isCenroAdminRecordRole) {
                    const isInternalOffice = officeLevel === 'CENRO_OFFICER'
                        || (officeNameUpper.indexOf(' - OFFICER') !== -1 && officeNameUpper.indexOf('CENRO') !== -1);
                    const isPenroOffice = officeLevel === 'PENRO_ADMIN_RECORD' || officeNameUpper.indexOf('PENRO ADMIN') !== -1;
                    const isRegionalOffice = officeNameMatchesRecordsUnit(officeNameUpper);
                    if (normalizedFilter === 'ard') {
                        return isInternalOffice;
                    }
                    if (normalizedFilter === 'division') {
                        return isPenroOffice;
                    }
                    if (normalizedFilter === 'section') {
                        return isRegionalOffice;
                    }
                    return true;
                }

                if (isPenroOfficerRole) {
                    const routeAction = String(activeRouteContext && activeRouteContext.action ? activeRouteContext.action : '').toUpperCase();
                    const isDivisionOffice = officeLevel === 'PENRO_DIVISION'
                        || (officeNameUpper.indexOf('PENRO') !== -1 && officeNameUpper.indexOf('DIVISION') !== -1);
                    const isSectionOffice = officeLevel === 'PENRO_SECTION'
                        || (officeNameUpper.indexOf('PENRO') !== -1 && officeNameUpper.indexOf('SECTION') !== -1);

                    if (routeAction === 'REROUTE') {
                        return isDivisionOffice;
                    }
                    if (normalizedFilter === 'ard') {
                        return isDivisionOffice;
                    }
                    if (normalizedFilter === 'division') {
                        return isSectionOffice;
                    }
                    return true;
                }
                if (isCenroOfficerRole) {
                    const routeAction = String(activeRouteContext && activeRouteContext.action ? activeRouteContext.action : '').toUpperCase();
                    const isSectionOffice = officeLevel === 'CENRO_SECTION'
                        || officeNameUpper.indexOf('CENRO SECTION') !== -1
                        || officeNameUpper.indexOf('SECTION') !== -1;
                    const isUnitOffice = officeLevel === 'CENRO_UNIT'
                        || officeNameUpper.indexOf('CENRO UNIT') !== -1
                        || officeNameUpper.indexOf('UNIT') !== -1;
                    const isAdminRecordOffice = officeLevel === 'CENRO_ADMIN_RECORD'
                        || officeNameUpper.indexOf('ADMIN RECORD') !== -1;
                    if (routeAction === 'REROUTE') {
                        return isSectionOffice;
                    }
                    if (normalizedFilter === 'ard') {
                        return isSectionOffice;
                    }
                    if (normalizedFilter === 'division') {
                        return isUnitOffice;
                    }
                    if (normalizedFilter === 'section') {
                        if (routeAction === 'FORWARD') {
                            return isSectionOffice || isUnitOffice || isAdminRecordOffice;
                        }
                        return isAdminRecordOffice;
                    }
                    return true;
                }
                if (isPamoOfficerRole) {
                    const isSectionOffice = officeLevel === 'PAMO_UNIT'
                        || officeNameUpper.indexOf('PAMO UNIT') !== -1
                        || officeNameUpper.indexOf('UNIT') !== -1;
                    if (normalizedFilter === 'ard') {
                        return isSectionOffice;
                    }
                    if (normalizedFilter === 'division' || normalizedFilter === 'section') {
                        return false;
                    }
                    return isSectionOffice;
                }
                if (currentRoleKey === 'ORED' && !isCenroOfficerRole && !isPenroOfficerRole && !isPamoOfficerRole) {
                    const isArdOffice = officeLevel === 'REGIONAL' && ardTrackFromOfficeName(officeNameUpper) !== '';
                    const isDivisionOffice = officeLevel === 'DIVISION';
                    const isSectionOffice = officeLevel === 'SECTION';
                    if (normalizedFilter === 'ard') {
                        return isArdOffice;
                    }
                    if (normalizedFilter === 'division') {
                        return isDivisionOffice;
                    }
                    if (normalizedFilter === 'section') {
                        return isSectionOffice;
                    }
                    return true;
                }

                const isArdOffice = ardTrackFromOfficeName(officeNameUpper) !== '';
                const isDivisionOffice = officeLevel === 'DIVISION' || officeNameUpper.indexOf('DIVISION') !== -1;
                const isSectionOffice = officeLevel === 'SECTION' || officeNameUpper.indexOf('SECTION') !== -1;

                if (normalizedFilter === 'ard') {
                    return isArdOffice;
                }
                if (normalizedFilter === 'division') {
                    return isDivisionOffice;
                }
                if (normalizedFilter === 'section') {
                    return isSectionOffice;
                }

                return true;
            }

            function setRouteDestinationFilterMode(mode) {
                const normalizedMode = normalizeLabelKey(String(mode || ''));
                if (normalizedMode === 'ard' || normalizedMode === 'division' || normalizedMode === 'section' || normalizedMode === 'all') {
                    activeRouteDestinationFilter = normalizedMode;
                } else {
                    activeRouteDestinationFilter = 'all';
                }

                routeDestinationFilterButtons.forEach(function (button) {
                    const buttonMode = normalizeLabelKey(String(button && button.dataset ? button.dataset.routeDestinationFilter : ''));
                    const isActive = buttonMode === activeRouteDestinationFilter;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
            }

            function routeDestinationFilterFallbackModes(mode) {
                const normalizedMode = normalizeLabelKey(String(mode || ''));
                const context = activeRouteContext || null;
                const preset = routeDestinationFilterUiPreset(context);
                if (preset === 'pamo_officer_forward' || preset === 'pamo_officer_reroute') {
                    return [];
                }
                if (preset === 'cenro_officer_reroute' || preset === 'penro_officer_reroute') {
                    return [];
                }
                if (preset === 'cenro_officer_forward' || preset === 'penro_officer_forward') {
                    if (normalizedMode === 'ard') {
                        return ['division'];
                    }
                    if (normalizedMode === 'division') {
                        return ['ard'];
                    }
                    return ['ard', 'division'];
                }
                if (normalizedMode === 'ard') {
                    return ['division', 'section'];
                }
                if (normalizedMode === 'division') {
                    return ['section', 'ard'];
                }
                if (normalizedMode === 'section') {
                    return ['division', 'ard'];
                }
                return ['ard', 'division', 'section'];
            }

            function routeDestinationFilterAllowsManualFallback(context) {
                const preset = routeDestinationFilterUiPreset(context);
                return preset !== 'penro_admin_record' && preset !== 'cenro_admin_record';
            }

            function syncRouteDestinationFilterUi(context) {
                const showFilter = routeSupportsDestinationTypeFilter(context);
                syncRouteDestinationFilterLabels(context);
                if (routeDestinationTypeFilterWrap) {
                    routeDestinationTypeFilterWrap.hidden = !showFilter;
                }
                if (showFilter) {
                    setRouteDestinationFilterMode('ard');
                } else {
                    setRouteDestinationFilterMode('all');
                }
                return showFilter;
            }

            function autoSelectFirstRouteDestinationOption(context) {
                if (!routeDestinationOffice) {
                    return false;
                }
                const action = String(context && context.action ? context.action : '').toUpperCase();
                if ((!isCenroAdminRecordRole && !isPenroAdminRecordRole) || action !== 'FORWARD') {
                    return false;
                }
                const firstSelectable = Array.from(routeDestinationOffice.options).find(function (option) {
                    return String(option && option.value ? option.value : '').trim() !== '';
                }) || null;
                if (!firstSelectable) {
                    return false;
                }
                routeDestinationOffice.value = String(firstSelectable.value || '');
                return true;
            }

            function selectPreferredOredForwardDestination(context) {
                if (currentRoleKey !== 'ORED' || isCenroOfficerRole || isPenroOfficerRole || isPamoOfficerRole || !routeDestinationOffice) {
                    return;
                }
                const action = String(context && context.action ? context.action : '').toUpperCase();
                if (action !== 'FORWARD' && action !== 'REROUTE') {
                    return;
                }
                const isPostSignStage = routeContextHasSignedPhase(context);
                const routeMode = normalizeLabelKey(context && context.routeMode ? context.routeMode : '');
                if (isRegionalOredReviewer && routeMode === 'records_unit_back') {
                    const recordsUnitOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        return officeNameMatchesRecordsUnit(String(option.textContent || '').toUpperCase());
                    });
                    if (recordsUnitOption) {
                        routeDestinationOffice.value = String(recordsUnitOption.value || '');
                    }
                    return;
                }
                if (isRegionalOredReviewer && !isPostSignStage) {
                    const sameOfficeOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        return String(option.value || '') === String(currentOfficeId || '');
                    });
                    if (sameOfficeOption) {
                        routeDestinationOffice.value = String(sameOfficeOption.value || '');
                    }
                    return;
                }
                if (!isRegionalOredReviewer && isPostSignStage) {
                    const sameOfficeOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        return String(option.value || '') === String(currentOfficeId || '');
                    });
                    if (sameOfficeOption) {
                        routeDestinationOffice.value = String(sameOfficeOption.value || '');
                    }
                    return;
                }
                const preferredOption = Array.from(routeDestinationOffice.options).find(function (option) {
                    const label = String(option.textContent || '').toUpperCase();
                    if (activeRouteDestinationFilter === 'division') {
                        return label.indexOf('[DIVISION]') !== -1 || label.indexOf('DIVISION') !== -1;
                    }
                    if (activeRouteDestinationFilter === 'section') {
                        return label.indexOf('[SECTION]') !== -1 || label.indexOf('SECTION') !== -1;
                    }
                    return label.indexOf('ARD') !== -1 || label.indexOf('ASSISTANT REGIONAL DIRECTOR') !== -1;
                });
                if (preferredOption) {
                    routeDestinationOffice.value = String(preferredOption.value || '');
                }
            }

            function selectPreferredCenroOfficerRerouteDestination(context) {
                if (!isCenroOfficerRole || !routeDestinationOffice) {
                    return;
                }
                const action = String(context && context.action ? context.action : '').toUpperCase();
                if (action !== 'REROUTE' && action !== 'FORWARD') {
                    return;
                }
                const currentStatus = normalizeLabelKey(context && context.currentStatus ? context.currentStatus : '');
                const isPostSignStage = isOredPostSignStatus(currentStatus);
                const preferredOption = Array.from(routeDestinationOffice.options).find(function (option) {
                    const label = String(option.textContent || '').toUpperCase();
                    const level = routeDestinationOptionLevel(option);
                    if (action === 'FORWARD') {
                        if (isPostSignStage) {
                            return level === 'CENRO_ADMIN_RECORD' || label.indexOf('ADMIN RECORD') !== -1;
                        }
                        if (activeRouteDestinationFilter === 'division') {
                            return (level === 'CENRO_UNIT' || label.indexOf('CENRO UNIT') !== -1 || label.indexOf('UNIT') !== -1)
                                && cenroUnitNameAllowed(label);
                        }
                        return level === 'CENRO_SECTION' || label.indexOf('CENRO SECTION') !== -1 || label.indexOf('SECTION') !== -1;
                    }
                    return level === 'CENRO_SECTION' || label.indexOf('CENRO SECTION') !== -1 || label.indexOf('SECTION') !== -1;
                });
                if (preferredOption) {
                    routeDestinationOffice.value = String(preferredOption.value || '');
                }
            }

            function selectPreferredPenroOfficerRerouteDestination(context) {
                if (!isPenroOfficerRole || !routeDestinationOffice) {
                    return;
                }
                const action = String(context && context.action ? context.action : '').toUpperCase();
                if (action !== 'REROUTE' && action !== 'FORWARD') {
                    return;
                }
                const currentStatus = normalizeLabelKey(context && context.currentStatus ? context.currentStatus : '');
                const isPostSignStage = isOredPostSignStatus(currentStatus);
                const preferredOption = Array.from(routeDestinationOffice.options).find(function (option) {
                    const label = String(option.textContent || '').toUpperCase();
                    const level = routeDestinationOptionLevel(option);
                    if (action === 'FORWARD') {
                        if (isPostSignStage) {
                            return level === 'PENRO_ADMIN_RECORD' || label.indexOf('ADMIN RECORD') !== -1;
                        }
                        if (activeRouteDestinationFilter === 'division') {
                            return level === 'PENRO_SECTION' || label.indexOf('PENRO SECTION') !== -1 || label.indexOf('SECTION') !== -1;
                        }
                        return level === 'PENRO_DIVISION' || label.indexOf('DIVISION') !== -1;
                    }
                    return level === 'PENRO_DIVISION' || label.indexOf('DIVISION') !== -1;
                });
                if (preferredOption) {
                    routeDestinationOffice.value = String(preferredOption.value || '');
                }
            }

            function routeDestinationOptionLevel(option) {
                if (!option) {
                    return '';
                }
                const dataLevel = String(option.dataset && option.dataset.officeLevel ? option.dataset.officeLevel : '').trim().toUpperCase();
                if (dataLevel !== '') {
                    return dataLevel;
                }
                const label = String(option.textContent || '').trim();
                const levelMatch = label.match(/\[([^\]]+)\]\s*$/);
                if (levelMatch && levelMatch[1]) {
                    return String(levelMatch[1]).trim().toUpperCase();
                }
                const labelUpper = label.toUpperCase();
                if (labelUpper.indexOf('SECTION') !== -1) {
                    return 'SECTION';
                }
                if (labelUpper.indexOf('DIVISION') !== -1) {
                    return 'DIVISION';
                }
                if (labelUpper.indexOf('REGIONAL') !== -1) {
                    return 'REGIONAL';
                }
                return '';
            }

            function officeNameKey(value) {
                return String(value || '').toUpperCase().replace(/[^A-Z0-9]+/g, '');
            }

            function isPenroManagementServicesDivisionName(value) {
                return officeNameKey(value) === 'MANAGEMENTSERVICESDIVISION';
            }

            function isPenroTechnicalServicesDivisionName(value) {
                return officeNameKey(value) === 'TECHNICALSERVICESDIVISION';
            }

            function isPenroAdminFinanceSectionName(value) {
                return officeNameKey(value) === 'ADMINFINANCESECTION';
            }

            function isPenroAdminFinanceUnitName(value) {
                return [
                    'ACCOUNTINGUNIT',
                    'BUDGETINGUNIT',
                    'HUMANRESOURCESUNIT',
                    'GENERALSERVICESUNIT',
                    'RECORDSUNIT'
                ].indexOf(officeNameKey(value)) !== -1;
            }

            function cenroSectionUnitNameKeys(sectionName) {
                const sectionKey = officeNameKey(sectionName);
                if (sectionKey === 'MONITORINGANDENFORCEMENTSECTIONMES') {
                    return [
                        'PATROLLINGFORESTSURVEILLANCE',
                        'ENFORCEMENTANDMONITORINGTENUREASSESSMENT'
                    ];
                }
                if (sectionKey === 'CONSERVATIONANDDEVELOPMENTSECTIONCDS') {
                    return [
                        'NATIONALGREENINGPROGRAM',
                        'COASTALANDMARINEECOSYSTEMMANAGEMENTPROGRAM'
                    ];
                }
                if (sectionKey === 'REGULATIONANDPERMITTINGSECTIONRPS') {
                    return [
                        'SURVEYANDMAPPINGUNIT',
                        'PATENTSANDDEEDSUNIT',
                        'PERMITTINGANDLICENSINGUNIT'
                    ];
                }
                if (sectionKey === 'PLANNINGANDSUPPORTUNITPSU') {
                    return [];
                }
                return [];
            }

            function cenroUnitNameAllowed(value) {
                return [
                    'PATROLLINGFORESTSURVEILLANCE',
                    'ENFORCEMENTANDMONITORINGTENUREASSESSMENT',
                    'NATIONALGREENINGPROGRAM',
                    'COASTALANDMARINEECOSYSTEMMANAGEMENTPROGRAM',
                    'SURVEYANDMAPPINGUNIT',
                    'PATENTSANDDEEDSUNIT',
                    'PERMITTINGANDLICENSINGUNIT'
                ].indexOf(officeNameKey(value)) !== -1;
            }

            function cenroSectionAllowsChildUnit(office) {
                const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                if (
                    String(office && office.level ? office.level : '').toUpperCase() !== 'CENRO_UNIT'
                    || !Number.isFinite(parentOfficeId)
                    || parentOfficeId <= 0
                    || parentOfficeId !== currentOfficeId
                ) {
                    return false;
                }
                return cenroSectionUnitNameKeys(currentOfficeName).indexOf(officeNameKey(office && office.name ? office.name : '')) !== -1;
            }

            function penroDivisionAllowsDestination(office) {
                const officeLevel = String(office && office.level ? office.level : '').toUpperCase();
                const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                if (
                    (officeLevel !== 'PENRO_SECTION' && officeLevel !== 'PENRO_UNIT')
                    || !Number.isFinite(parentOfficeId)
                    || parentOfficeId <= 0
                    || parentOfficeId !== currentOfficeId
                ) {
                    return false;
                }

                const destinationKey = officeNameKey(office && office.name ? office.name : '');
                if (isPenroManagementServicesDivisionName(currentOfficeName)) {
                    return [
                        'ADMINFINANCESECTION',
                        'PLANNINGSECTION',
                        'ICTUNIT',
                        'CASHIERINGUNIT'
                    ].indexOf(destinationKey) !== -1;
                }
                if (isPenroTechnicalServicesDivisionName(currentOfficeName)) {
                    return [
                        'MTMATUTUMPROTECTEDLANDSCAPEMMPL',
                        'ALLAHVALLEYPROTECTEDLANDSCAPEAVPL',
                        'CONSERVATIONDEVELOPMENTSECTION',
                        'REGULATIONPERMITTINGSECTION',
                        'MONITORINGENFORCEMENTSECTION'
                    ].indexOf(destinationKey) !== -1;
                }
                return officeLevel === 'PENRO_SECTION';
            }

            function penroSectionAllowsChildUnit(office) {
                if (!isPenroAdminFinanceSectionName(currentOfficeName)) {
                    return false;
                }
                const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                if (
                    !Number.isFinite(parentOfficeId)
                    || parentOfficeId <= 0
                    || parentOfficeId !== currentOfficeParentId
                ) {
                    return false;
                }
                return isPenroAdminFinanceUnitName(office && office.name ? office.name : '');
            }

            function routeRequiresOredBypassReason(context, option) {
                const action = String(context && context.action ? context.action : '').toUpperCase();
                const optionLabel = String(option && option.textContent ? option.textContent : '').toUpperCase();
                const optionLevel = routeDestinationOptionLevel(option);
                if (isPenroOfficerRole) {
                    if (action === 'REROUTE') {
                        return false;
                    }
                    if (action === 'FORWARD') {
                        const currentStatus = normalizeLabelKey(context && context.currentStatus ? context.currentStatus : '');
                        const isPostSignStage = isOredPostSignStatus(currentStatus);
                        if (isPostSignStage) {
                            return false;
                        }
                        return optionLevel === 'PENRO_SECTION' || optionLabel.indexOf('PENRO SECTION') !== -1;
                    }
                }
                if (isCenroOfficerRole) {
                    if (action === 'REROUTE') {
                        return false;
                    }
                    if (action === 'FORWARD') {
                        const currentStatus = normalizeLabelKey(context && context.currentStatus ? context.currentStatus : '');
                        const isPostSignStage = isOredPostSignStatus(currentStatus);
                        if (isPostSignStage) {
                            return false;
                        }
                        return optionLevel === 'CENRO_UNIT'
                            || optionLabel.indexOf('CENRO UNIT') !== -1
                            || optionLabel.indexOf('UNIT') !== -1;
                    }
                }
                if (currentRoleKey !== 'ORED' || isCenroOfficerRole) {
                    return false;
                }
                if (action !== 'FORWARD') {
                    return false;
                }
                const isPostSignStage = isOredPostSignStatus(context && context.currentStatus ? context.currentStatus : '');
                if (!isRegionalOredReviewer) {
                    return false;
                }
                if (!isPostSignStage) {
                    return false;
                }
                const isArdDestination = optionLevel === 'REGIONAL'
                    && (
                        optionLabel.indexOf('ARD') !== -1
                        || optionLabel.indexOf('ASSISTANT REGIONAL DIRECTOR') !== -1
                        || optionLabel.indexOf('TECHNICAL SERVICES') !== -1
                        || optionLabel.indexOf('MANAGEMENT SERVICES') !== -1
                    );
                if (isArdDestination) {
                    return false;
                }
                return optionLevel === 'DIVISION' || optionLevel === 'SECTION';
            }

            function syncRouteBypassReasonUi(context) {
                if (!routeBypassReasonWrap || !routeBypassReason) {
                    return false;
                }

                const selectedIndex = routeDestinationOffice ? routeDestinationOffice.selectedIndex : -1;
                const selectedOption = routeDestinationOffice && selectedIndex >= 0
                    ? routeDestinationOffice.options[selectedIndex]
                    : null;
                const requiresBypassReason = routeRequiresOredBypassReason(context, selectedOption);
                routeBypassReasonWrap.hidden = !requiresBypassReason;
                routeBypassReason.required = requiresBypassReason;
                if (!requiresBypassReason) {
                    routeBypassReason.value = '';
                }

                return requiresBypassReason;
            }

            function officePassesForwardPolicy(office, context) {
                const action = String(context && context.action ? context.action : '').toUpperCase();
                const officeName = String(office && office.name ? office.name : '').toUpperCase();
                const officeLevel = String(office && office.level ? office.level : '').toUpperCase();
                const currentStatus = normalizeLabelKey(context && context.currentStatus ? context.currentStatus : '');
                const isOredPostSignStage = routeContextHasSignedPhase(context);
                const originOfficeId = Number(context && context.originOfficeId ? context.originOfficeId : 0);
                const routeMode = normalizeLabelKey(context && context.routeMode ? context.routeMode : '');
                const officeId = Number(office && office.id ? office.id : 0);
                const policyOfficeId = routeContextOfficeId(context);
                const policyParentOfficeId = routeContextParentOfficeId(context);

                if (
                    action === 'RELEASE'
                    && currentRoleKey === 'RECORDS_UNIT'
                    && !isCenroAdminRecordRole
                    && !isPenroAdminRecordRole
                    && !isPamoAdminRole
                ) {
                    if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                        return Number.isFinite(officeId) && officeId === originOfficeId;
                    }
                    return true;
                }

                const releaseUsesForwardPolicy = action === 'RELEASE'
                    && (isCenroAdminRecordRole || isPenroAdminRecordRole || isPamoAdminRole);

                if (action === 'REROUTE') {
                    if (isPenroSectionUnitRole) {
                        return false;
                    }
                    if (isPamoOfficerRole) {
                        const isPostSignStage = isOredPostSignStatus(currentStatus);
                        if (isPostSignStage) {
                            return false;
                        }
                        const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                        const isUnitDestination = (officeLevel === 'PAMO_UNIT' || officeName.indexOf('PAMO UNIT') !== -1 || officeName.indexOf('UNIT') !== -1)
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && Number.isFinite(policyOfficeId)
                            && policyOfficeId > 0
                            && parentOfficeId === policyOfficeId;
                        return isUnitDestination;
                    }
                if (isPenroOfficerRole) {
                    const isPostSignStage = isOredPostSignStatus(currentStatus);
                    if (isPostSignStage) {
                        return false;
                    }
                        const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                        const isDivisionDestination = (officeLevel === 'PENRO_DIVISION' || officeName.indexOf('DIVISION') !== -1)
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && parentOfficeId === currentOfficeId;
                        return isDivisionDestination;
                    }
                    if (isCenroOfficerRole) {
                        const isPostSignStage = isOredPostSignStatus(currentStatus);
                        if (isPostSignStage) {
                            return false;
                        }
                        const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                        const isSectionDestination = (officeLevel === 'CENRO_SECTION' || officeName.indexOf('CENRO SECTION') !== -1)
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && parentOfficeId === currentOfficeId;
                        const isUnitDestination = (officeLevel === 'CENRO_UNIT' || officeName.indexOf('CENRO UNIT') !== -1)
                            && cenroUnitNameAllowed(officeName);
                        const isAdminRecordDestination = officeLevel === 'CENRO_ADMIN_RECORD' || officeName.indexOf('ADMIN RECORD') !== -1;
                        return isSectionDestination || isUnitDestination || isAdminRecordDestination;
                    }
                    if (isCenroSectionRole) {
                        return cenroSectionAllowsChildUnit(office);
                    }
                    if (currentRoleKey === 'ORED') {
                        const isArdDestination = officeLevel === 'REGIONAL' && ardTrackFromOfficeName(officeName) !== '';
                        const isDivisionDestination = officeLevel === 'DIVISION';
                        const isSectionDestination = officeLevel === 'SECTION';
                        return isArdDestination || isDivisionDestination || isSectionDestination;
                    }
                    if (currentRoleKey === 'DIVISION_CHIEF') {
                        const hasSectionStaff = String(office && office.has_section_staff ? office.has_section_staff : '') === '1'
                            || Boolean(office && office.has_section_staff);
                        const isSectionDestination = officeLevel === 'SECTION'
                            || officeName.indexOf('SECTION') !== -1
                            || (showDivisionChiefStaffRerouteTargets && hasSectionStaff);
                        const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                        if (
                            showDivisionChiefStaffRerouteTargets
                            && hasSectionStaff
                            && Number.isFinite(officeId)
                            && officeId > 0
                            && officeId === currentOfficeId
                        ) {
                            return true;
                        }
                        if (Number.isFinite(parentOfficeId) && parentOfficeId > 0) {
                            return isSectionDestination && parentOfficeId === currentOfficeId;
                        }
                        return isSectionDestination;
                    }
                    if (isArdRole && String(rerouteOfficeLevelFilter || '').toLowerCase() === 'division') {
                        const actorTrack = ardTrackFromRoleKey(currentRoleKey);
                        const officeTrack = divisionTrackFromOfficeName(officeName);
                        if (actorTrack === '' || officeTrack === '') {
                            return false;
                        }
                        return actorTrack === officeTrack;
                    }
                    if (String(rerouteOfficeLevelFilter || '').toLowerCase() === 'ard') {
                        return ardTrackFromOfficeName(officeName) !== '';
                    }
                    if (String(rerouteOfficeLevelFilter || '').toLowerCase() === 'division') {
                        return officeLevel === 'DIVISION' || officeName.indexOf('DIVISION') !== -1;
                    }
                    if (String(rerouteOfficeLevelFilter || '').toLowerCase() === 'section') {
                        return officeLevel === 'SECTION';
                    }
                    return true;
                }

                if (action === 'RETURN') {
                    if (isPenroOfficerRole) {
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                            return Number.isFinite(officeId) && officeId === originOfficeId;
                        }
                        const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                        return officeLevel === 'PENRO_ADMIN_RECORD'
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && parentOfficeId === currentOfficeParentId;
                    }

                    if (isCenroOfficerRole) {
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                            return Number.isFinite(officeId) && officeId === originOfficeId;
                        }
                        const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                        return officeLevel === 'CENRO_ADMIN_RECORD'
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && parentOfficeId === currentOfficeParentId;
                    }

                    if (isPenroAdminRecordRole) {
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                            return Number.isFinite(officeId) && officeId === originOfficeId;
                        }

                        const isCenroDestination = officeName.indexOf('CENRO ADMIN') !== -1
                            || officeLevel === 'CENRO_ADMIN_RECORD';
                        const isPasuDestination = officeName.indexOf('PASU') !== -1;
                        return isCenroDestination || isPasuDestination;
                    }

                    if (currentRoleKey === 'RECORDS_UNIT') {
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                            return Number.isFinite(officeId) && officeId === originOfficeId;
                        }
                        return officeName.indexOf('PENRO ADMIN') !== -1 || officeLevel === 'PENRO_ADMIN_RECORD';
                    }
                    if (currentRoleKey === 'ORED') {
                        return officeNameMatchesRecordsUnit(officeName);
                    }

                    return true;
                }

                if (releaseUsesForwardPolicy && (isPenroAdminRecordRole || isCenroAdminRecordRole)) {
                    const isCurrentOffice = Number.isFinite(officeId)
                        && officeId > 0
                        && officeId === currentOfficeId;
                    const isOriginDestination = Number.isFinite(originOfficeId)
                        && originOfficeId > 0
                        && Number.isFinite(officeId)
                        && officeId === originOfficeId
                        && !isCurrentOffice;
                    const isRecordsUnitDestination = officeNameMatchesRecordsUnit(officeName);
                    if (isCenroAdminRecordRole) {
                        const isPenroDestination = officeLevel === 'PENRO_ADMIN_RECORD'
                            || officeName.indexOf('PENRO ADMIN') !== -1;
                        return isOriginDestination || isPenroDestination || isRecordsUnitDestination;
                    }

                    if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                        return isOriginDestination || isRecordsUnitDestination;
                    }

                    const isCenroDestination = officeLevel === 'CENRO_ADMIN_RECORD'
                        || officeName.indexOf('CENRO ADMIN') !== -1;
                    return isOriginDestination || isCenroDestination || isRecordsUnitDestination;
                }

                if (action !== 'FORWARD' && !releaseUsesForwardPolicy) {
                    return true;
                }

                if (isPenroSectionUnitRole) {
                    const policyOfficeId = Number(routeFallbackOffice && routeFallbackOffice.id ? routeFallbackOffice.id : 0);
                    if (Number.isFinite(policyOfficeId) && policyOfficeId > 0) {
                        return Number.isFinite(officeId) && officeId === policyOfficeId;
                    }
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    return officeLevel === 'PENRO_SECTION'
                        && Number.isFinite(parentOfficeId)
                        && parentOfficeId > 0
                        && parentOfficeId === currentOfficeId;
                }
                if (isCenroOfficerRole) {
                    const isPostSignStage = isOredPostSignStatus(currentStatus);
                    const isAdminRecordBack = routeMode === 'admin_record_back';
                    if (isPostSignStage) {
                        const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                        const isAdminRecordChild = (officeLevel === 'CENRO_ADMIN_RECORD' || officeName.indexOf('ADMIN RECORD') !== -1)
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && (
                                parentOfficeId === currentOfficeId
                                || (
                                    Number.isFinite(currentOfficeParentId)
                                    && currentOfficeParentId > 0
                                    && parentOfficeId === currentOfficeParentId
                                )
                            );
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0 && Number.isFinite(officeId) && officeId === originOfficeId) {
                            return true;
                        }
                        return isAdminRecordChild;
                    }
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    const isAdminRecordChild = (officeLevel === 'CENRO_ADMIN_RECORD' || officeName.indexOf('ADMIN RECORD') !== -1)
                        && Number.isFinite(parentOfficeId)
                        && parentOfficeId > 0
                        && (
                            parentOfficeId === currentOfficeId
                            || (
                                Number.isFinite(currentOfficeParentId)
                                && currentOfficeParentId > 0
                                && parentOfficeId === currentOfficeParentId
                            )
                        );
                    if (isAdminRecordBack) {
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0 && Number.isFinite(officeId) && officeId === originOfficeId) {
                            return true;
                        }
                        return isAdminRecordChild;
                    }
                    const isSectionChild = (officeLevel === 'CENRO_SECTION' || officeName.indexOf('CENRO SECTION') !== -1)
                        && Number.isFinite(parentOfficeId)
                        && parentOfficeId > 0
                        && parentOfficeId === currentOfficeId;
                    const isUnitSameRoot = (officeLevel === 'CENRO_UNIT' || officeName.indexOf('CENRO UNIT') !== -1 || officeName.indexOf('UNIT') !== -1)
                        && cenroUnitNameAllowed(officeName);
                    return isSectionChild || isUnitSameRoot;
                }
                if (isPamoAdminRole) {
                    if (
                        releaseUsesForwardPolicy
                        && Number.isFinite(officeId)
                        && officeId > 0
                        && officeId === currentOfficeId
                    ) {
                        return false;
                    }
                    const isCenroDestination = officeLevel === 'CENRO_ADMIN_RECORD' || officeName.indexOf('CENRO ADMIN') !== -1;
                    const isPenroDestination = officeLevel === 'PENRO_ADMIN_RECORD' || officeName.indexOf('PENRO ADMIN') !== -1;
                    return isCenroDestination || isPenroDestination;
                }
                if (isPenroAdminRecordRole) {
                    if (
                        releaseUsesForwardPolicy
                        && Number.isFinite(officeId)
                        && officeId > 0
                        && officeId === currentOfficeId
                    ) {
                        return false;
                    }
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    const isOfficerDestination = officeLevel === 'PENRO_OFFICER'
                        && Number.isFinite(parentOfficeId)
                        && parentOfficeId > 0
                        && parentOfficeId === currentOfficeParentId;
                    const isCenroDestination = officeLevel === 'CENRO_ADMIN_RECORD' || officeName.indexOf('CENRO ADMIN') !== -1;
                    const isRecordsUnitDestination = officeNameMatchesRecordsUnit(officeName);
                    return isOfficerDestination || isCenroDestination || isRecordsUnitDestination;
                }
                if (isPenroOfficerRole) {
                    const isPostSignStage = isOredPostSignStatus(currentStatus);
                    const isAdminRecordBack = routeMode === 'admin_record_back';
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    if (isPostSignStage) {
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0 && Number.isFinite(officeId) && officeId === originOfficeId) {
                            return true;
                        }
                        return officeLevel === 'PENRO_ADMIN_RECORD'
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && parentOfficeId === currentOfficeParentId;
                    }
                    if (isAdminRecordBack) {
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0 && Number.isFinite(officeId) && officeId === originOfficeId) {
                            return true;
                        }
                        return officeLevel === 'PENRO_ADMIN_RECORD'
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && parentOfficeId === currentOfficeParentId;
                    }
                    const isDivisionChild = officeLevel === 'PENRO_DIVISION'
                        && Number.isFinite(parentOfficeId)
                        && parentOfficeId > 0
                        && parentOfficeId === currentOfficeId;
                    const isSectionSameChain = officeLevel === 'PENRO_SECTION' || officeLevel === 'PENRO_UNIT';
                    return isDivisionChild || isSectionSameChain;
                }
                if (isPamoOfficerRole) {
                    const isPostSignStage = isOredPostSignStatus(currentStatus);
                    const isAdminRecordBack = routeMode === 'admin_record_back';
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    if (isPostSignStage) {
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0 && Number.isFinite(officeId) && officeId === originOfficeId) {
                            return true;
                        }
                        return officeLevel === 'PAMO_ADMIN'
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && Number.isFinite(policyParentOfficeId)
                            && policyParentOfficeId > 0
                            && parentOfficeId === policyParentOfficeId;
                    }
                    if (isAdminRecordBack) {
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0 && Number.isFinite(officeId) && officeId === originOfficeId) {
                            return true;
                        }
                        return officeLevel === 'PAMO_ADMIN'
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && Number.isFinite(policyParentOfficeId)
                            && policyParentOfficeId > 0
                            && parentOfficeId === policyParentOfficeId;
                    }
                    const isUnitChild = officeLevel === 'PAMO_UNIT'
                        && Number.isFinite(parentOfficeId)
                        && parentOfficeId > 0
                        && Number.isFinite(policyOfficeId)
                        && policyOfficeId > 0
                        && parentOfficeId === policyOfficeId;
                    return isUnitChild;
                }
                if (isCenroSectionRole) {
                    if (routeMode === 'cenro_officer_back') {
                        return officeLevel === 'CENRO_OFFICER' || officeName.indexOf('CENRO OFFICER') !== -1 || officeName.indexOf(' - OFFICER') !== -1;
                    }
                    const isChildUnit = cenroSectionAllowsChildUnit(office);
                    const isAdminSibling = officeLevel === 'CENRO_ADMIN_RECORD' || officeName.indexOf('ADMIN RECORD') !== -1;
                    return isChildUnit || isAdminSibling;
                }
                if (isCenroUnitRole) {
                    const policyOfficeId = Number(routeFallbackOffice && routeFallbackOffice.id ? routeFallbackOffice.id : 0);
                    if (Number.isFinite(policyOfficeId) && policyOfficeId > 0) {
                        return Number.isFinite(officeId) && officeId === policyOfficeId;
                    }
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    return (officeLevel === 'CENRO_SECTION' || officeName.indexOf('CENRO SECTION') !== -1)
                        && Number.isFinite(parentOfficeId)
                        && parentOfficeId > 0
                        && parentOfficeId === currentOfficeId;
                }
                if (isPamoUnitRole) {
                    const policyOfficeId = Number(routeFallbackOffice && routeFallbackOffice.id ? routeFallbackOffice.id : 0);
                    if (Number.isFinite(policyOfficeId) && policyOfficeId > 0) {
                        return Number.isFinite(officeId) && officeId === policyOfficeId;
                    }
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    return officeLevel === 'PASU_OFFICER'
                        && Number.isFinite(parentOfficeId)
                        && parentOfficeId > 0
                        && parentOfficeId === currentOfficeId;
                }
                if (isPenroAdminRecordRole) {
                    return officeNameMatchesRecordsUnit(officeName);
                }
                if (isCenroAdminRecordRole) {
                    if (
                        releaseUsesForwardPolicy
                        && Number.isFinite(officeId)
                        && officeId > 0
                        && officeId === currentOfficeId
                    ) {
                        return false;
                    }
                    const isOfficerDestination = officeLevel === 'CENRO_OFFICER' || officeName.indexOf(' - OFFICER') !== -1;
                    const isPenroDestination = officeLevel === 'PENRO_ADMIN_RECORD' || officeName.indexOf('PENRO ADMIN') !== -1;
                    const isRecordsUnitDestination = officeNameMatchesRecordsUnit(officeName);
                    return isOfficerDestination || isPenroDestination || isRecordsUnitDestination;
                }
                if (currentRoleKey === 'RECORDS_UNIT') {
                    return officeName.indexOf('ORED') !== -1 || officeName.indexOf('REGIONAL EXECUTIVE') !== -1;
                }
                if (currentRoleKey === 'ORED') {
                    if (isRegionalOredReviewer) {
                        if (isOredPostSignStage) {
                            const isArdDestination = officeLevel === 'REGIONAL' && ardTrackFromOfficeName(officeName) !== '';
                            const isRecordsUnitDestination = officeNameMatchesRecordsUnit(officeName);
                            if (routeMode === 'records_unit_back') {
                                return isRecordsUnitDestination;
                            }
                            return isArdDestination
                                || officeLevel === 'DIVISION'
                                || officeLevel === 'SECTION'
                                || isRecordsUnitDestination;
                        }
                        return Number.isFinite(officeId)
                            && officeId > 0
                            && officeId === currentOfficeId;
                    }
                    if (!isRegionalOredReviewer && isOredPostSignStage) {
                        return Number.isFinite(officeId)
                            && officeId > 0
                            && officeId === currentOfficeId;
                    }
                    const isArdDestination = officeLevel === 'REGIONAL' && ardTrackFromOfficeName(officeName) !== '';
                    return isArdDestination
                        || officeLevel === 'DIVISION'
                        || officeLevel === 'SECTION';
                }
                if (currentRoleKey === 'DIVISION_CHIEF') {
                    if (isPenroDivisionRole || isPenroSectionRole) {
                        if (routeMode === 'ard_back') {
                            if (isPenroDivisionRole && Number.isFinite(currentOfficeParentId) && currentOfficeParentId > 0) {
                                return Number.isFinite(officeId) && officeId === currentOfficeParentId;
                            }
                            return officeLevel === 'PENRO_OFFICER'
                                || officeName.indexOf('PENRO OFFICER') !== -1
                                || (officeName.indexOf(' - OFFICER') !== -1 && officeName.indexOf('PENRO') !== -1);
                        }
                        if (isPenroDivisionRole) {
                            return penroDivisionAllowsDestination(office);
                        }
                        if (isPenroAdminFinanceSectionName(currentOfficeName)) {
                            const isParentDivision = Number.isFinite(currentOfficeParentId)
                                && currentOfficeParentId > 0
                                && Number.isFinite(officeId)
                                && officeId === currentOfficeParentId;
                            return penroSectionAllowsChildUnit(office) || isParentDivision;
                        }
                        if (isPenroAdminFinanceUnitName(currentOfficeName)) {
                            const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                            return isPenroAdminFinanceSectionName(officeName)
                                && Number.isFinite(parentOfficeId)
                                && parentOfficeId > 0
                                && parentOfficeId === currentOfficeParentId;
                        }
                        return Number.isFinite(currentOfficeParentId)
                            && currentOfficeParentId > 0
                            && Number.isFinite(officeId)
                            && officeId === currentOfficeParentId;
                    }

                    const isOredDestination = officeName.indexOf('ORED') !== -1 || officeName.indexOf('REGIONAL EXECUTIVE') !== -1;
                    const isArdDestination = ardTrackFromOfficeName(officeName) !== '';
                    if (routeMode === 'ard_back') {
                        const regionalFallback = Array.isArray(routeFallbackOffices)
                            ? routeFallbackOffices.find(function (candidate) {
                                const candidateLevel = String(candidate && candidate.level ? candidate.level : '').toUpperCase();
                                return candidateLevel === 'REGIONAL';
                            })
                            : null;
                        const policyOfficeId = Number(regionalFallback && regionalFallback.id ? regionalFallback.id : 0);
                        if (Number.isFinite(policyOfficeId) && policyOfficeId > 0) {
                            return Number.isFinite(officeId) && officeId === policyOfficeId;
                        }
                        return isArdDestination || isOredDestination;
                    }
                    const isSectionDestination = officeLevel === 'SECTION';
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    if (Number.isFinite(parentOfficeId) && parentOfficeId > 0) {
                        return isSectionDestination && parentOfficeId === currentOfficeId;
                    }
                    return isSectionDestination;
                }
                if (currentRoleKey === 'SECTION_STAFF') {
                    if (isPenroSectionRole) {
                        if (isPenroAdminFinanceSectionName(currentOfficeName)) {
                            const isParentDivision = Number.isFinite(currentOfficeParentId)
                                && currentOfficeParentId > 0
                                && Number.isFinite(officeId)
                                && officeId === currentOfficeParentId;
                            return penroSectionAllowsChildUnit(office) || isParentDivision;
                        }
                        if (isPenroAdminFinanceUnitName(currentOfficeName)) {
                            const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                            return isPenroAdminFinanceSectionName(officeName)
                                && Number.isFinite(parentOfficeId)
                                && parentOfficeId > 0
                                && parentOfficeId === currentOfficeParentId;
                        }
                        return Number.isFinite(currentOfficeParentId)
                            && currentOfficeParentId > 0
                            && Number.isFinite(officeId)
                            && officeId === currentOfficeParentId;
                    }
                    const policyOfficeId = Number(routeFallbackOffice && routeFallbackOffice.id ? routeFallbackOffice.id : 0);
                    if (Number.isFinite(policyOfficeId) && policyOfficeId > 0) {
                        return Number.isFinite(officeId) && officeId === policyOfficeId;
                    }
                    return officeLevel === 'DIVISION' || officeName.indexOf('DIVISION') !== -1;
                }
                if (isArdRole) {
                    const actorTrack = ardTrackFromRoleKey(currentRoleKey);
                    const isOredDestination = officeName.indexOf('ORED') !== -1 || officeName.indexOf('REGIONAL EXECUTIVE') !== -1;
                    const isRbcoDestination = officeNameMatchesRbco(officeName);
                    if (routeMode === 'ored_back') {
                        return isOredDestination;
                    }
                    if (isOredDestination) {
                        return false;
                    }
                    if (officeLevel !== 'DIVISION' && !isRbcoDestination) {
                        return false;
                    }
                    const officeTrack = officeTrackFromRouteOffice(office);
                    if (actorTrack === '' || officeTrack === '') {
                        return false;
                    }
                    return actorTrack === officeTrack;
                }

                return true;
            }

            function routeOfficeHidesLevelSuffix(normalizedLevel, office) {
                return normalizedLevel === 'CENRO_SECTION'
                    || normalizedLevel === 'CENRO_UNIT'
                    || normalizedLevel === 'CENRO_ADMIN_RECORD'
                    || normalizedLevel === 'CENRO_OFFICER'
                    || normalizedLevel === 'PAMO_ADMIN'
                    || normalizedLevel === 'PASU_OFFICER'
                    || normalizedLevel === 'PAMO_UNIT'
                    || normalizedLevel === 'PENRO_OFFICER'
                    || normalizedLevel === 'PENRO_DIVISION'
                    || normalizedLevel === 'PENRO_SECTION'
                    || officeNameMatchesRbco(office && office.name ? office.name : '');
            }

            function appendRouteOfficeOption(office) {
                if (!routeDestinationOffice) {
                    return false;
                }
                const officeId = Number(office && office.id ? office.id : 0);
                if (!Number.isFinite(officeId) || officeId <= 0) {
                    return false;
                }
                const alreadyExists = Array.from(routeDestinationOffice.options).some(function (existingOption) {
                    return String(existingOption.value || '') === String(officeId);
                });
                if (alreadyExists) {
                    return false;
                }

                const level = String(office && office.level ? office.level : '').trim();
                const normalizedLevel = String(level).toUpperCase();
                const hideLevelSuffix = routeOfficeHidesLevelSuffix(normalizedLevel, office);
                const option = document.createElement('option');
                option.value = String(officeId);
                option.dataset.officeLevel = normalizedLevel;
                option.textContent = String(office && office.route_label ? office.route_label : (office && office.name ? office.name : ('Office #' + String(officeId))))
                    + (level === '' || hideLevelSuffix ? '' : ' [' + level + ']');
                routeDestinationOffice.appendChild(option);
                return true;
            }

            function routeContextOfficeId(context) {
                const action = String(context && context.action ? context.action : '').toUpperCase();
                if (isPamoOfficerRole && (action === 'FORWARD' || action === 'REROUTE')) {
                    return Number.isFinite(currentOfficeId) && currentOfficeId > 0 ? currentOfficeId : 0;
                }
                const contextOfficeId = Number(context && context.currentOfficeId ? context.currentOfficeId : 0);
                if (Number.isFinite(contextOfficeId) && contextOfficeId > 0) {
                    return contextOfficeId;
                }
                return Number.isFinite(currentOfficeId) && currentOfficeId > 0 ? currentOfficeId : 0;
            }

            function routeContextParentOfficeId(context) {
                const contextParentOfficeId = Number(context && context.currentOfficeParentId ? context.currentOfficeParentId : 0);
                if (Number.isFinite(contextParentOfficeId) && contextParentOfficeId > 0) {
                    return contextParentOfficeId;
                }
                return Number.isFinite(currentOfficeParentId) && currentOfficeParentId > 0 ? currentOfficeParentId : 0;
            }

            function forcePopulatePamoRouteDestinations(context) {
                if (!routeDestinationOffice || !(isPamoOfficerRole || isPamoUnitRole || isPamoAdminRole)) {
                    return 0;
                }

                const action = String(context && context.action ? context.action : '').toUpperCase();
                const currentStatus = normalizeLabelKey(context && context.currentStatus ? context.currentStatus : '');
                const isPostSignStage = isOredPostSignStatus(currentStatus);
                const originOfficeId = Number(context && context.originOfficeId ? context.originOfficeId : 0);
                const policyOfficeId = routeContextOfficeId(context);
                let forcedCount = 0;

                const candidates = [];
                if (Array.isArray(routeOffices)) {
                    routeOffices.forEach(function (office) {
                        candidates.push(office);
                    });
                }
                if (Array.isArray(routeFallbackOffices) && routeFallbackOffices.length > 0) {
                    routeFallbackOffices.forEach(function (office) {
                        candidates.push(office);
                    });
                } else if (routeFallbackOffice && typeof routeFallbackOffice === 'object') {
                    candidates.push(routeFallbackOffice);
                }

                candidates.forEach(function (office) {
                    const officeLevel = String(office && office.level ? office.level : '').toUpperCase();
                    const officeId = Number(office && office.id ? office.id : 0);
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    let allowed = false;

                    if (isPamoOfficerRole) {
                        if (action === 'REROUTE') {
                            allowed = !isPostSignStage
                                && officeLevel === 'PAMO_UNIT'
                                && Number.isFinite(policyOfficeId)
                                && policyOfficeId > 0
                                && parentOfficeId === policyOfficeId;
                        } else if (action === 'FORWARD') {
                            if (isPostSignStage) {
                                allowed = (Number.isFinite(originOfficeId) && originOfficeId > 0 && officeId === originOfficeId)
                                    || (officeLevel === 'PAMO_ADMIN');
                            } else {
                                allowed = officeLevel === 'PAMO_UNIT'
                                    && Number.isFinite(policyOfficeId)
                                    && policyOfficeId > 0
                                    && parentOfficeId === policyOfficeId;
                            }
                        } else if (action === 'RETURN') {
                            allowed = (Number.isFinite(originOfficeId) && originOfficeId > 0 && officeId === originOfficeId)
                                || officeLevel === 'PAMO_ADMIN';
                        }
                    } else if (isPamoUnitRole && action === 'FORWARD') {
                        allowed = officeLevel === 'PASU_OFFICER';
                    } else if (isPamoAdminRole && action === 'FORWARD') {
                        allowed = officeLevel === 'PASU_OFFICER'
                            || officeLevel === 'CENRO_ADMIN_RECORD'
                            || officeLevel === 'PENRO_ADMIN_RECORD';
                    }

                    if (!allowed) {
                        return;
                    }
                    if (appendRouteOfficeOption(office)) {
                        forcedCount += 1;
                    }
                });

                return forcedCount;
            }

            function forcePopulateOfficerAdminRecordBackDestinations(context) {
                if (!routeDestinationOffice || !(isCenroOfficerRole || isPenroOfficerRole || isPamoOfficerRole)) {
                    return 0;
                }
                const action = String(context && context.action ? context.action : '').toUpperCase();
                const routeMode = normalizeLabelKey(context && context.routeMode ? context.routeMode : '');
                if (action !== 'FORWARD' || routeMode !== 'admin_record_back') {
                    return 0;
                }

                const originOfficeId = Number(context && context.originOfficeId ? context.originOfficeId : 0);
                const policyOfficeId = routeContextOfficeId(context);
                const policyParentOfficeId = routeContextParentOfficeId(context);
                const candidates = [];
                if (Array.isArray(routeOffices)) {
                    routeOffices.forEach(function (office) {
                        candidates.push(office);
                    });
                }
                if (Array.isArray(routeFallbackOffices) && routeFallbackOffices.length > 0) {
                    routeFallbackOffices.forEach(function (office) {
                        candidates.push(office);
                    });
                } else if (routeFallbackOffice && typeof routeFallbackOffice === 'object') {
                    candidates.push(routeFallbackOffice);
                }

                let forcedCount = 0;
                candidates.forEach(function (office) {
                    const officeId = Number(office && office.id ? office.id : 0);
                    const officeLevel = String(office && office.level ? office.level : '').toUpperCase();
                    const officeName = String(office && office.name ? office.name : '').toUpperCase();
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    let allowed = false;

                    if (Number.isFinite(originOfficeId) && originOfficeId > 0 && Number.isFinite(officeId) && officeId > 0 && officeId === originOfficeId) {
                        allowed = true;
                    } else if (isCenroOfficerRole) {
                        const isCenroAdmin = officeLevel === 'CENRO_ADMIN_RECORD'
                            || (officeLevel === '' && officeName.indexOf('CENRO') !== -1 && officeName.indexOf('ADMIN') !== -1);
                        const isSameChain = Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && (
                                (Number.isFinite(policyOfficeId) && policyOfficeId > 0 && parentOfficeId === policyOfficeId)
                                || (Number.isFinite(policyParentOfficeId) && policyParentOfficeId > 0 && parentOfficeId === policyParentOfficeId)
                            );
                        allowed = isCenroAdmin && isSameChain;
                    } else if (isPenroOfficerRole) {
                        const isPenroAdmin = officeLevel === 'PENRO_ADMIN_RECORD'
                            || (officeLevel === '' && officeName.indexOf('PENRO') !== -1 && officeName.indexOf('ADMIN') !== -1);
                        const isSameChain = Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && (
                                (Number.isFinite(policyOfficeId) && policyOfficeId > 0 && parentOfficeId === policyOfficeId)
                                || (Number.isFinite(policyParentOfficeId) && policyParentOfficeId > 0 && parentOfficeId === policyParentOfficeId)
                            );
                        allowed = isPenroAdmin && isSameChain;
                    } else if (isPamoOfficerRole) {
                        const isPamoAdmin = officeLevel === 'PAMO_ADMIN'
                            || (officeLevel === '' && officeName.indexOf('PAMO') !== -1 && officeName.indexOf('ADMIN') !== -1);
                        const isSameChain = Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && (
                                (Number.isFinite(policyOfficeId) && policyOfficeId > 0 && parentOfficeId === policyOfficeId)
                                || (Number.isFinite(policyParentOfficeId) && policyParentOfficeId > 0 && parentOfficeId === policyParentOfficeId)
                            );
                        allowed = isPamoAdmin && isSameChain;
                    }

                    if (!allowed) {
                        return;
                    }
                    if (appendRouteOfficeOption(office)) {
                        forcedCount += 1;
                    }
                });

                return forcedCount;
            }

            function populateDestinationOfficeSelect(context) {
                if (!routeDestinationOffice) {
                    return 0;
                }
                routeDestinationOffice.innerHTML = '';
                const applyOfficeTypeFilter = routeSupportsDestinationTypeFilter(context);

                function officeMatchesPamoRoutingFallback(office) {
                    const action = String(context && context.action ? context.action : '').toUpperCase();
                    const officeId = Number(office && office.id ? office.id : 0);
                    const officeLevel = String(office && office.level ? office.level : '').toUpperCase();
                    const parentOfficeId = Number(office && office.parent_office_id ? office.parent_office_id : 0);
                    const originOfficeId = Number(context && context.originOfficeId ? context.originOfficeId : 0);
                    const currentStatus = normalizeLabelKey(context && context.currentStatus ? context.currentStatus : '');
                    const isPostSignStage = isOredPostSignStatus(currentStatus);
                    const policyOfficeId = routeContextOfficeId(context);
                    const policyParentOfficeId = routeContextParentOfficeId(context);

                    if (isPamoOfficerRole) {
                        if (action === 'REROUTE') {
                            if (isPostSignStage) {
                                return false;
                            }
                            const isUnitChild = officeLevel === 'PAMO_UNIT'
                                && Number.isFinite(parentOfficeId)
                                && parentOfficeId > 0
                                && Number.isFinite(policyOfficeId)
                                && policyOfficeId > 0
                                && parentOfficeId === policyOfficeId;
                            return isUnitChild;
                        }
                        if (action === 'FORWARD') {
                            if (isPostSignStage) {
                                if (Number.isFinite(originOfficeId) && originOfficeId > 0 && officeId === originOfficeId) {
                                    return true;
                                }
                                return officeLevel === 'PAMO_ADMIN'
                                    && Number.isFinite(parentOfficeId)
                                    && parentOfficeId > 0
                                    && Number.isFinite(policyParentOfficeId)
                                    && policyParentOfficeId > 0
                                    && parentOfficeId === policyParentOfficeId;
                            }
                            const isUnitChild = officeLevel === 'PAMO_UNIT'
                                && Number.isFinite(parentOfficeId)
                                && parentOfficeId > 0
                                && Number.isFinite(policyOfficeId)
                                && policyOfficeId > 0
                                && parentOfficeId === policyOfficeId;
                            return isUnitChild;
                        }
                        if (action === 'RETURN') {
                            if (Number.isFinite(originOfficeId) && originOfficeId > 0 && officeId === originOfficeId) {
                                return true;
                            }
                            return officeLevel === 'PAMO_ADMIN';
                        }
                    }

                    if (isPamoUnitRole && action === 'FORWARD') {
                        return officeLevel === 'PASU_OFFICER'
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && parentOfficeId === currentOfficeId;
                    }

                    if (isPamoAdminRole && action === 'FORWARD') {
                        const isOfficerDestination = officeLevel === 'PASU_OFFICER'
                            && Number.isFinite(parentOfficeId)
                            && parentOfficeId > 0
                            && parentOfficeId === currentOfficeParentId;
                        const isCenroDestination = officeLevel === 'CENRO_ADMIN_RECORD' || String(office && office.name ? office.name : '').toUpperCase().indexOf('CENRO ADMIN') !== -1;
                        const isPenroDestination = officeLevel === 'PENRO_ADMIN_RECORD' || String(office && office.name ? office.name : '').toUpperCase().indexOf('PENRO ADMIN') !== -1;
                        return isOfficerDestination || isCenroDestination || isPenroDestination;
                    }

                    return false;
                }

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Select destination office';
                routeDestinationOffice.appendChild(placeholder);

                let count = 0;
                if (Array.isArray(routeOffices)) {
                    routeOffices.forEach(function (office) {
                        const officeId = Number(office.id || 0);
                        if (!Number.isFinite(officeId) || officeId <= 0) {
                            return;
                        }
                        if (!officePassesForwardPolicy(office, context)) {
                            return;
                        }
                        if (applyOfficeTypeFilter && !officeMatchesDestinationTypeFilter(office, activeRouteDestinationFilter)) {
                            return;
                        }
                        if (appendRouteOfficeOption(office)) {
                            count += 1;
                        }
                    });
                }

                if (count === 0) {
                    const fallbackCandidates = Array.isArray(routeFallbackOffices) && routeFallbackOffices.length > 0
                        ? routeFallbackOffices
                        : (routeFallbackOffice && typeof routeFallbackOffice === 'object' ? [routeFallbackOffice] : []);

                    fallbackCandidates.forEach(function (fallbackOffice) {
                        const fallbackOfficeId = Number(fallbackOffice.id || 0);
                        if (!Number.isFinite(fallbackOfficeId) || fallbackOfficeId <= 0) {
                            return;
                        }
                        if (!officePassesForwardPolicy(fallbackOffice, context)) {
                            return;
                        }
                        if (applyOfficeTypeFilter && !officeMatchesDestinationTypeFilter(fallbackOffice, activeRouteDestinationFilter)) {
                            return;
                        }
                        const alreadyExists = Array.from(routeDestinationOffice.options).some(function (existingOption) {
                            return String(existingOption.value || '') === String(fallbackOfficeId);
                        });
                        if (alreadyExists) {
                            return;
                        }

                        if (appendRouteOfficeOption(fallbackOffice)) {
                            count += 1;
                        }
                    });
                }

                if (count === 0 && (isPamoOfficerRole || isPamoUnitRole || isPamoAdminRole)) {
                    const rescueCandidates = [];
                    if (Array.isArray(routeOffices)) {
                        routeOffices.forEach(function (office) {
                            rescueCandidates.push(office);
                        });
                    }
                    if (Array.isArray(routeFallbackOffices) && routeFallbackOffices.length > 0) {
                        routeFallbackOffices.forEach(function (office) {
                            rescueCandidates.push(office);
                        });
                    } else if (routeFallbackOffice && typeof routeFallbackOffice === 'object') {
                        rescueCandidates.push(routeFallbackOffice);
                    }

                    rescueCandidates.forEach(function (office) {
                        if (!officeMatchesPamoRoutingFallback(office)) {
                            return;
                        }
                        if (applyOfficeTypeFilter && !officeMatchesDestinationTypeFilter(office, activeRouteDestinationFilter)) {
                            return;
                        }
                        if (appendRouteOfficeOption(office)) {
                            count += 1;
                        }
                    });
                }
                return count;
            }

            function syncDualReleaseModalUi(context) {
                if (!routeContextSupportsDualRelease(context)) {
                    if (routeReleaseModeWrap) {
                        routeReleaseModeWrap.hidden = true;
                    }
                    if (routeDestinationWrap) {
                        routeDestinationWrap.hidden = false;
                    }
                    if (routeDestinationOffice) {
                        routeDestinationOffice.required = true;
                    }
                    if (routeDestinationMeta) {
                        routeDestinationMeta.textContent = 'Required for routing actions. Remarks are optional.';
                    }
                    if (routeActionSubmit) {
                        routeActionSubmit.textContent = 'Submit';
                    }
                    return '';
                }

                const canCompleteLocal = routeCanCompleteLocally(context);
                const currentMode = resolveRouteReleaseMode(context);

                if (routeReleaseModeWrap) {
                    routeReleaseModeWrap.hidden = false;
                }
                if (routeReleaseModeMeta) {
                    routeReleaseModeMeta.textContent = canCompleteLocal
                        ? 'Choose whether to complete the document in this office or release it to another office.'
                        : 'This document cannot be completed locally here, so release it to another office for the next workflow leg.';
                }
                if (routeReleaseMode) {
                    routeReleaseMode.innerHTML = '';
                    if (canCompleteLocal) {
                        const localOption = document.createElement('option');
                        localOption.value = 'complete_local';
                        localOption.textContent = 'Complete in this office';
                        routeReleaseMode.appendChild(localOption);
                    }
                    const sendOption = document.createElement('option');
                    sendOption.value = 'send_to_office';
                    sendOption.textContent = 'Release and send to office';
                    routeReleaseMode.appendChild(sendOption);
                    routeReleaseMode.value = currentMode;
                    routeReleaseMode.disabled = routeReleaseMode.options.length <= 1;
                }

                const requiresDestination = currentMode === 'send_to_office';
                if (routeDestinationWrap) {
                    routeDestinationWrap.hidden = !requiresDestination;
                }
                if (routeDestinationOffice) {
                    routeDestinationOffice.required = requiresDestination;
                    if (!requiresDestination) {
                        routeDestinationOffice.value = '';
                    }
                }
                if (routeDestinationMeta) {
                    routeDestinationMeta.textContent = requiresDestination
                        ? 'Select the next office that should receive this released document.'
                        : 'Destination office is not required when you complete the document locally.';
                }
                if (routeActionTitle) {
                    routeActionTitle.textContent = requiresDestination ? 'Release Document' : 'Complete Document';
                }
                if (routeActionMeta) {
                    const trackingPrefix = String(context && context.trackingId ? context.trackingId : '').trim() !== ''
                        ? ('Tracking ID: ' + String(context.trackingId).trim() + ' | ')
                        : '';
                    routeActionMeta.textContent = requiresDestination
                        ? (trackingPrefix + 'Select destination office and add optional remarks. The document will stay Released until the next office clicks Receive.')
                        : (trackingPrefix + 'This will mark the document completed in this office and close the workflow for this release leg.');
                }
                if (routeActionSubmit) {
                    routeActionSubmit.textContent = requiresDestination ? 'Release' : 'Complete Here';
                }

                return currentMode;
            }

            function openRouteActionModal(context) {
                if (!routeActionModal || !routeActionForm || !routeDestinationOffice || !routeActionTitle || !routeActionMeta || !routeRemarks) {
                    showAlertDialog('Routing form is not available on this page.');
                    return false;
                }

                activeRouteContext = context;
                const routeFilterEnabled = syncRouteDestinationFilterUi(context);
                const dualReleaseContext = routeContextSupportsDualRelease(context);
                const releaseNeedsDestination = !dualReleaseContext || routeReleaseRequiresDestination(context);
                let optionCount = 0;
                if (releaseNeedsDestination) {
                    optionCount = populateDestinationOfficeSelect(context);
                    if (optionCount === 0 && routeFilterEnabled) {
                        const fallbackModes = routeDestinationFilterFallbackModes(activeRouteDestinationFilter);
                        for (let index = 0; index < fallbackModes.length; index += 1) {
                            const fallbackMode = fallbackModes[index];
                            setRouteDestinationFilterMode(fallbackMode);
                            optionCount = populateDestinationOfficeSelect(context);
                            if (optionCount > 0) {
                                break;
                            }
                        }
                    }
                    if (optionCount === 0) {
                        optionCount = forcePopulateOfficerAdminRecordBackDestinations(context);
                    }
                    if (optionCount === 0) {
                        optionCount = forcePopulatePamoRouteDestinations(context);
                    }
                    if (optionCount === 0) {
                        showAlertDialog('No destination offices are configured for routing.');
                        activeRouteContext = null;
                        return false;
                    }
                } else {
                    routeDestinationOffice.innerHTML = '';
                    const localPlaceholder = document.createElement('option');
                    localPlaceholder.value = '';
                    localPlaceholder.textContent = 'Destination not required';
                    routeDestinationOffice.appendChild(localPlaceholder);
                }

                const routeContextAction = String(context && context.action ? context.action : '').toUpperCase();
                const normalizedRouteMode = normalizeLabelKey(context && context.routeMode ? context.routeMode : '');
                const isOfficerAdminRecordBack = routeContextAction === 'FORWARD'
                    && normalizedRouteMode === 'admin_record_back'
                    && (isCenroOfficerRole || isPenroOfficerRole || isPamoOfficerRole);
                const isOredRecordsUnitBack = routeContextAction === 'FORWARD'
                    && normalizedRouteMode === 'records_unit_back'
                    && currentRoleKey === 'ORED'
                    && isRegionalOredReviewer;
                if (isCenroSectionRole && context.action === 'FORWARD' && normalizedRouteMode === 'cenro_officer_back') {
                    routeActionTitle.textContent = 'Send Back to CENRO Officer';
                } else if (isOfficerAdminRecordBack) {
                    routeActionTitle.textContent = 'Send Back to Admin Record';
                } else if (isOredRecordsUnitBack) {
                    routeActionTitle.textContent = 'Send Back to RECORDS-UNIT';
                } else if (currentRoleKey === 'DIVISION_CHIEF' && !isCenroSectionRole && context.action === 'FORWARD' && normalizedRouteMode === 'ard_back') {
                    if (isPenroDivisionRole || isPenroSectionRole) {
                        routeActionTitle.textContent = 'Send Back to PENRO Officer';
                    } else {
                        routeActionTitle.textContent = 'Send Back to ARD';
                    }
                } else if (isArdRole && context.action === 'FORWARD' && normalizedRouteMode === 'ored_back') {
                    routeActionTitle.textContent = 'Send Back to ORED';
                } else {
                    routeActionTitle.textContent = actionLabel(context.action) + ' Document';
                }
                const rerouteRemarksRequired = routeContextAction === 'REROUTE'
                    && (((currentRoleKey === 'ORED' && !isCenroOfficerRole) || isArdRole || (currentRoleKey === 'DIVISION_CHIEF' && !isCenroSectionRole) || isCenroOfficerRole || isCenroSectionRole || isPamoOfficerRole));
                const returnRemarksRequired = routeContextAction === 'RETURN';
                routeActionMeta.textContent = isOfficerAdminRecordBack
                    ? (context.trackingId !== ''
                        ? ('Tracking ID: ' + context.trackingId + ' | Send this document back to the originating Admin Record office. Remarks are optional.')
                        : 'Send this document back to the originating Admin Record office. Remarks are optional.')
                    : (isOredRecordsUnitBack
                        ? (context.trackingId !== ''
                            ? ('Tracking ID: ' + context.trackingId + ' | Send this signed document back to RECORDS-UNIT. Remarks are optional.')
                            : 'Send this signed document back to RECORDS-UNIT. Remarks are optional.')
                    : (context.trackingId !== ''
                        ? ('Tracking ID: ' + context.trackingId + (returnRemarksRequired ? ' | Remarks are required for return.' : (rerouteRemarksRequired ? ' | Remarks are required for reroute.' : '')))
                        : (returnRemarksRequired
                            ? 'Select destination office and provide required correction remarks.'
                            : (rerouteRemarksRequired
                                ? 'Select destination office and provide the reroute reason.'
                                : 'Select destination office and add optional remarks.'))));
                routeRemarks.value = '';
                routeRemarks.required = returnRemarksRequired || rerouteRemarksRequired;
                routeRemarks.placeholder = isOfficerAdminRecordBack
                    ? 'Optional remarks for the Admin Record office'
                    : (isOredRecordsUnitBack
                        ? 'Optional remarks for RECORDS-UNIT'
                    : (returnRemarksRequired
                        ? 'Required: describe specific corrections needed before re-processing.'
                        : (rerouteRemarksRequired
                            ? 'Required: describe the reroute reason.'
                            : 'Optional remarks for activity logs')));
                routeDestinationOffice.value = '';
                autoSelectFirstRouteDestinationOption(context);
                if (routeBypassReason) {
                    routeBypassReason.value = '';
                    routeBypassReason.required = false;
                }
                if (routeBypassReasonWrap) {
                    routeBypassReasonWrap.hidden = true;
                }
                if (routeNewSubjectWrap && routeNewSubject) {
                    const allowSubjectChange = routeActionAllowsSubjectChange(context);
                    routeNewSubjectWrap.hidden = !allowSubjectChange;
                    routeNewSubject.value = '';
                    routeNewSubject.required = false;
                }
                if (routeReleaseMode) {
                    routeReleaseMode.disabled = false;
                }

                if (isPenroAdminRecordRole && context.action === 'FORWARD') {
                    const pacdoOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        return officeNameMatchesRecordsUnit(option.textContent || '');
                    });
                    if (pacdoOption) {
                        routeDestinationOffice.value = String(pacdoOption.value || '');
                    }
                }
                if (isPenroAdminRecordRole && context.action === 'RETURN') {
                    const originOfficeId = Number(context.originOfficeId || 0);
                    let returnTargetOption = null;

                    if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                        returnTargetOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            return String(option.value || '') === String(originOfficeId);
                        }) || null;
                    }

                    if (!returnTargetOption) {
                        returnTargetOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            const label = String(option.textContent || '').toUpperCase();
                            return label.indexOf('CENRO ADMIN') !== -1 || label.indexOf('PASU') !== -1;
                        }) || null;
                    }

                    if (returnTargetOption) {
                        routeDestinationOffice.value = String(returnTargetOption.value || '');
                    }
                }
                if ((isPenroAdminRecordRole || isCenroAdminRecordRole) && context.action === 'RELEASE') {
                    const originOfficeId = Number(context.originOfficeId || 0);
                    let releaseTargetOption = null;

                    if (Number.isFinite(originOfficeId) && originOfficeId > 0 && originOfficeId !== currentOfficeId) {
                        releaseTargetOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            return String(option.value || '') === String(originOfficeId);
                        }) || null;
                    }

                    if (!releaseTargetOption) {
                        releaseTargetOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            const label = String(option.textContent || '').toUpperCase();
                            const level = routeDestinationOptionLevel(option);
                            if (isCenroAdminRecordRole) {
                                return level === 'PENRO_ADMIN_RECORD' || label.indexOf('PENRO ADMIN') !== -1;
                            }
                            return level === 'CENRO_ADMIN_RECORD' || label.indexOf('CENRO ADMIN') !== -1;
                        }) || null;
                    }

                    if (!releaseTargetOption) {
                        releaseTargetOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            return officeNameMatchesRecordsUnit(String(option.textContent || '').toUpperCase());
                        }) || null;
                    }

                    if (releaseTargetOption) {
                        routeDestinationOffice.value = String(releaseTargetOption.value || '');
                    }
                }
                if ((isPenroOfficerRole || isCenroOfficerRole || isPamoOfficerRole) && context.action === 'RETURN') {
                    const originOfficeId = Number(context.originOfficeId || 0);
                    let returnTargetOption = null;

                    if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                        returnTargetOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            return String(option.value || '') === String(originOfficeId);
                        }) || null;
                    }

                    if (!returnTargetOption) {
                        returnTargetOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            const level = routeDestinationOptionLevel(option);
                            const label = String(option.textContent || '').toUpperCase();
                            if (isPenroOfficerRole) {
                                return level === 'PENRO_ADMIN_RECORD' || label.indexOf('ADMIN RECORD') !== -1;
                            }
                            if (isPamoOfficerRole) {
                                return level === 'PAMO_ADMIN' || label.indexOf('PAMO ADMIN') !== -1;
                            }
                            return level === 'CENRO_ADMIN_RECORD' || label.indexOf('ADMIN RECORD') !== -1;
                        }) || null;
                    }

                    if (returnTargetOption) {
                        routeDestinationOffice.value = String(returnTargetOption.value || '');
                    }
                }
                if (isCenroAdminRecordRole && context.action === 'FORWARD') {
                    const policyOfficeId = Number(routeFallbackOffice && routeFallbackOffice.id ? routeFallbackOffice.id : 0);
                    if (Number.isFinite(policyOfficeId) && policyOfficeId > 0) {
                        const policyOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            return String(option.value || '') === String(policyOfficeId);
                        });
                        if (policyOption) {
                            routeDestinationOffice.value = String(policyOption.value || '');
                        }
                    }
                }
                if (isCenroAdminRecordRole && context.action === 'FORWARD') {
                    const officerOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        const label = String(option.textContent || '').toUpperCase();
                        return routeDestinationOptionLevel(option) === 'CENRO_OFFICER'
                            || label.indexOf('CENRO OFFICER') !== -1
                            || label.indexOf(' - OFFICER') !== -1;
                    });
                    if (officerOption) {
                        routeDestinationOffice.value = String(officerOption.value || '');
                    }
                }
                if (isCenroOfficerRole && context.action === 'FORWARD') {
                    const currentStatus = normalizeLabelKey(context.currentStatus || '');
                    const isPostSignStage = isOredPostSignStatus(currentStatus);
                    if (normalizedRouteMode === 'admin_record_back' || isPostSignStage) {
                        const originOfficeId = Number(context.originOfficeId || 0);
                        let originOption = null;
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                            originOption = Array.from(routeDestinationOffice.options).find(function (option) {
                                return String(option.value || '') === String(originOfficeId);
                            }) || null;
                        }
                        if (!originOption) {
                            originOption = Array.from(routeDestinationOffice.options).find(function (option) {
                                return routeDestinationOptionIsCenroAdminRecord(option);
                            }) || null;
                        }
                        if (originOption) {
                            routeDestinationOffice.value = String(originOption.value || '');
                        }
                    } else {
                        const sectionOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            const label = String(option.textContent || '').toUpperCase();
                            return routeDestinationOptionLevel(option) === 'CENRO_SECTION'
                                || label.indexOf('CENRO SECTION') !== -1;
                        });
                        if (sectionOption) {
                            routeDestinationOffice.value = String(sectionOption.value || '');
                        }
                    }
                }
                if (isCenroSectionRole && context.action === 'FORWARD') {
                    if (normalizedRouteMode === 'cenro_officer_back') {
                        const officerBackOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            const label = String(option.textContent || '').toUpperCase();
                            return routeDestinationOptionLevel(option) === 'CENRO_OFFICER'
                                || label.indexOf('CENRO OFFICER') !== -1
                                || label.indexOf(' - OFFICER') !== -1;
                        });
                        if (officerBackOption) {
                            routeDestinationOffice.value = String(officerBackOption.value || '');
                        }
                    }
                    const unitOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        return routeDestinationOptionIsCenroUnit(option);
                    });
                    const adminOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        return routeDestinationOptionIsCenroAdminRecord(option);
                    });
                    if (normalizedRouteMode !== 'cenro_officer_back' && unitOption) {
                        routeDestinationOffice.value = String(unitOption.value || '');
                    } else if (normalizedRouteMode !== 'cenro_officer_back' && adminOption) {
                        routeDestinationOffice.value = String(adminOption.value || '');
                    }
                }
                if (isCenroUnitRole && context.action === 'FORWARD') {
                    const sectionBackOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        const label = String(option.textContent || '').toUpperCase();
                        return routeDestinationOptionLevel(option) === 'CENRO_SECTION'
                            || label.indexOf('CENRO SECTION') !== -1;
                    });
                    if (sectionBackOption) {
                        routeDestinationOffice.value = String(sectionBackOption.value || '');
                    }
                }
                if (isPamoAdminRole && context.action === 'FORWARD') {
                    const officerOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        return routeDestinationOptionIsPamoOfficer(option);
                    });
                    if (officerOption) {
                        routeDestinationOffice.value = String(officerOption.value || '');
                    }
                }
                if (isPamoOfficerRole && context.action === 'FORWARD') {
                    const currentStatus = normalizeLabelKey(context.currentStatus || '');
                    const isPostSignStage = isOredPostSignStatus(currentStatus);
                    if (normalizedRouteMode === 'admin_record_back' || isPostSignStage) {
                        const originOfficeId = Number(context.originOfficeId || 0);
                        let adminOption = null;
                        if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                            adminOption = Array.from(routeDestinationOffice.options).find(function (option) {
                                return String(option.value || '') === String(originOfficeId);
                            }) || null;
                        }
                        if (!adminOption) {
                            adminOption = Array.from(routeDestinationOffice.options).find(function (option) {
                                return routeDestinationOptionIsPamoAdmin(option);
                            }) || null;
                        }
                        if (adminOption) {
                            routeDestinationOffice.value = String(adminOption.value || '');
                        }
                    } else {
                        const unitOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            return routeDestinationOptionIsPamoUnit(option);
                        });
                        if (unitOption) {
                            routeDestinationOffice.value = String(unitOption.value || '');
                        }
                    }
                }
                if (isPamoUnitRole && context.action === 'FORWARD') {
                    const officerBackOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        return routeDestinationOptionIsPamoOfficer(option);
                    });
                    if (officerBackOption) {
                        routeDestinationOffice.value = String(officerBackOption.value || '');
                    }
                }
                if (
                    currentRoleKey === 'RECORDS_UNIT'
                    && !isCenroAdminRecordRole
                    && !isPenroAdminRecordRole
                    && !isPamoAdminRole
                    && context.action === 'FORWARD'
                ) {
                    const oredOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        const label = String(option.textContent || '').toUpperCase();
                        return label.indexOf('ORED') !== -1 || label.indexOf('REGIONAL EXECUTIVE') !== -1;
                    });
                    if (oredOption) {
                        routeDestinationOffice.value = String(oredOption.value || '');
                    }
                }
                if (
                    currentRoleKey === 'RECORDS_UNIT'
                    && !isCenroAdminRecordRole
                    && !isPenroAdminRecordRole
                    && !isPamoAdminRole
                    && context.action === 'RETURN'
                ) {
                    const originOfficeId = Number(context.originOfficeId || 0);
                    if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                        const originOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            return String(option.value || '') === String(originOfficeId);
                        });
                        if (originOption) {
                            routeDestinationOffice.value = String(originOption.value || '');
                        }
                    }
                    if (String(routeDestinationOffice.value || '') === '') {
                    const penroOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        const label = String(option.textContent || '').toUpperCase();
                        return label.indexOf('PENRO ADMIN') !== -1 || routeDestinationOptionLevel(option) === 'PENRO_ADMIN_RECORD';
                    });
                        if (penroOption) {
                            routeDestinationOffice.value = String(penroOption.value || '');
                        }
                    }
                }
                if (
                    currentRoleKey === 'RECORDS_UNIT'
                    && !isCenroAdminRecordRole
                    && !isPenroAdminRecordRole
                    && !isPamoAdminRole
                    && context.action === 'RELEASE'
                ) {
                    const originOfficeId = Number(context.originOfficeId || 0);
                    if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                        const originOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            return String(option.value || '') === String(originOfficeId);
                        });
                        if (originOption) {
                            routeDestinationOffice.value = String(originOption.value || '');
                        }
                    }
                }
                if (currentRoleKey === 'ORED' && context.action === 'FORWARD') {
                    selectPreferredOredForwardDestination(context);
                }
                if (isPenroOfficerRole && context.action === 'FORWARD' && normalizedRouteMode === 'admin_record_back') {
                    const originOfficeId = Number(context.originOfficeId || 0);
                    let adminRecordOption = null;
                    if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                        adminRecordOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            return String(option.value || '') === String(originOfficeId);
                        }) || null;
                    }
                    if (!adminRecordOption) {
                        adminRecordOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            return routeDestinationOptionIsPenro(option);
                        }) || null;
                    }
                    if (adminRecordOption) {
                        routeDestinationOffice.value = String(adminRecordOption.value || '');
                    }
                }
                if (isPenroOfficerRole && context.action === 'REROUTE') {
                    selectPreferredPenroOfficerRerouteDestination(context);
                }
                if (isCenroOfficerRole && context.action === 'REROUTE') {
                    selectPreferredCenroOfficerRerouteDestination(context);
                }
                if (currentRoleKey === 'ORED' && context.action === 'RETURN') {
                    const pacdoOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        return officeNameMatchesRecordsUnit(String(option.textContent || '').toUpperCase());
                    });
                    if (pacdoOption) {
                        routeDestinationOffice.value = String(pacdoOption.value || '');
                    }
                }
                if (isCenroSectionRole && context.action === 'FORWARD' && normalizedRouteMode === 'cenro_officer_back') {
                    const officerOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        const label = String(option.textContent || '').toUpperCase();
                        return routeDestinationOptionLevel(option) === 'CENRO_OFFICER'
                            || label.indexOf('CENRO OFFICER') !== -1
                            || label.indexOf(' - OFFICER') !== -1;
                    });
                    if (officerOption) {
                        routeDestinationOffice.value = String(officerOption.value || '');
                    }
                } else if (currentRoleKey === 'DIVISION_CHIEF' && !isCenroSectionRole && context.action === 'FORWARD') {
                    const preferredOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        const label = String(option.textContent || '').toUpperCase();
                        if (normalizedRouteMode === 'ard_back') {
                            if (isPenroDivisionRole || isPenroSectionRole) {
                                return routeDestinationOptionLevel(option) === 'PENRO_OFFICER'
                                    || label.indexOf('PENRO OFFICER') !== -1
                                    || (label.indexOf(' - OFFICER') !== -1 && label.indexOf('PENRO') !== -1);
                            }
                            return label.indexOf('ARD') !== -1
                                || label.indexOf('ASSISTANT REGIONAL DIRECTOR') !== -1
                                || label.indexOf('ORED') !== -1
                                || label.indexOf('REGIONAL EXECUTIVE') !== -1;
                        }
                        return label.indexOf('[SECTION]') !== -1 || label.indexOf('SECTION') !== -1;
                    });
                    if (preferredOption) {
                        routeDestinationOffice.value = String(preferredOption.value || '');
                    }
                }
                if (isArdRole && context.action === 'FORWARD') {
                    if (normalizedRouteMode === 'ored_back') {
                        const oredOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            const label = String(option.textContent || '').toUpperCase();
                            return label.indexOf('ORED') !== -1 || label.indexOf('REGIONAL EXECUTIVE') !== -1;
                        });
                        if (oredOption) {
                            routeDestinationOffice.value = String(oredOption.value || '');
                        }
                    } else {
                        const actorTrack = ardTrackFromRoleKey(currentRoleKey);
                        const preferredOption = Array.from(routeDestinationOffice.options).find(function (option) {
                            const label = String(option.textContent || '').toUpperCase();
                            const levelHint = label.indexOf('[DIVISION]') !== -1 || label.indexOf('DIVISION') !== -1;
                            if (!levelHint) {
                                return false;
                            }
                            return divisionTrackFromOfficeName(label) === actorTrack;
                        });
                        if (preferredOption) {
                            routeDestinationOffice.value = String(preferredOption.value || '');
                        }
                    }
                }
                if (currentRoleKey === 'SECTION_STAFF' && context.action === 'FORWARD') {
                    const divisionOption = Array.from(routeDestinationOffice.options).find(function (option) {
                        const label = String(option.textContent || '').toUpperCase();
                        return label.indexOf('[DIVISION]') !== -1 || label.indexOf('DIVISION') !== -1;
                    });
                    if (divisionOption) {
                        routeDestinationOffice.value = String(divisionOption.value || '');
                    }
                }
                if (String(routeDestinationOffice.value || '') === '' && releaseNeedsDestination) {
                    autoSelectFirstRouteDestinationOption(context);
                }

                syncDualReleaseModalUi(context);
                if (!dualReleaseContext && routeActionSubmit) {
                    routeActionSubmit.textContent = isOfficerAdminRecordBack ? 'Send Back to Admin Record' : 'Submit';
                }

                if (isOfficerAdminRecordBack && routeDestinationMeta) {
                    routeDestinationMeta.textContent = 'Admin Record destination is preselected for this send-back route. Remarks are optional.';
                }

                syncRouteBypassReasonUi(context);
                syncRouteAttachmentUi(context);
                initAllSearchableSelects(routeActionModal);

                routeActionModal.hidden = false;
                routeActionModal.classList.add('is-open');
                document.body.classList.add('modal-open');
                if (dualReleaseContext && !routeReleaseRequiresDestination(context) && routeReleaseMode) {
                    focusSearchableSelect(routeReleaseMode, false);
                } else {
                    focusSearchableSelect(routeDestinationOffice, false);
                }
                return true;
            }

            function closeRouteActionModal() {
                if (!routeActionModal) {
                    return;
                }
                routeActionModal.classList.remove('is-open');
                routeActionModal.hidden = true;
                document.body.classList.remove('modal-open');
                activeRouteContext = null;
                if (routeReleaseModeWrap) {
                    routeReleaseModeWrap.hidden = true;
                }
                if (routeReleaseMode) {
                    routeReleaseMode.disabled = false;
                }
                if (routeDestinationWrap) {
                    routeDestinationWrap.hidden = false;
                }
                if (routeDestinationMeta) {
                    routeDestinationMeta.textContent = 'Required for routing actions. Remarks are optional.';
                }
                if (routeDestinationTypeFilterWrap) {
                    routeDestinationTypeFilterWrap.hidden = true;
                }
                setRouteDestinationFilterMode('all');
                if (routeActionForm) {
                    routeActionForm.reset();
                }
                if (routeRemarks) {
                    routeRemarks.required = false;
                    routeRemarks.placeholder = 'Optional remarks for activity logs';
                }
                if (routeActionSubmit) {
                    routeActionSubmit.textContent = 'Submit';
                }
                if (routeBypassReasonWrap) {
                    routeBypassReasonWrap.hidden = true;
                }
                if (routeBypassReason) {
                    routeBypassReason.value = '';
                    routeBypassReason.required = false;
                }
                if (routeAttachmentWrap) {
                    routeAttachmentWrap.hidden = true;
                }
                if (routeAttachmentLabel) {
                    routeAttachmentLabel.textContent = 'Prepared Response (Attachment)';
                }
                if (routeAttachmentMeta) {
                    routeAttachmentMeta.textContent = 'Attach prepared response file(s) when this routing path requires them.';
                }
                if (routeAttachmentInput) {
                    routeAttachmentInput.value = '';
                }
                if (routeNewSubjectWrap) {
                    routeNewSubjectWrap.hidden = true;
                }
                if (routeNewSubject) {
                    routeNewSubject.value = '';
                    routeNewSubject.required = false;
                }
                resetRouteExistingAttachmentsDisplay();
            }

            function bindRouteActionModal() {
                if (!routeActionModal || !routeActionForm || !routeDestinationOffice || !routeRemarks) {
                    return;
                }

                routeActionCloseButtons.forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        if (routeActionSubmit && routeActionSubmit.disabled) {
                            return;
                        }
                        closeRouteActionModal();
                    });
                });

                routeDestinationFilterButtons.forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        if (!activeRouteContext) {
                            return;
                        }
                        if (!routeSupportsDestinationTypeFilter(activeRouteContext)) {
                            return;
                        }
                        if (routeActionSubmit && routeActionSubmit.disabled) {
                            return;
                        }

                        const requestedMode = normalizeLabelKey(String(button && button.dataset ? button.dataset.routeDestinationFilter : ''));
                        setRouteDestinationFilterMode(requestedMode);
                        let optionCount = populateDestinationOfficeSelect(activeRouteContext);
                        if (optionCount === 0 && routeDestinationFilterAllowsManualFallback(activeRouteContext)) {
                            const fallbackModes = routeDestinationFilterFallbackModes(activeRouteDestinationFilter);
                            for (let index = 0; index < fallbackModes.length; index += 1) {
                                const fallbackMode = fallbackModes[index];
                                setRouteDestinationFilterMode(fallbackMode);
                                optionCount = populateDestinationOfficeSelect(activeRouteContext);
                                if (optionCount > 0) {
                                    break;
                                }
                            }
                        }
                        if (optionCount > 0) {
                            autoSelectFirstRouteDestinationOption(activeRouteContext);
                            selectPreferredOredForwardDestination(activeRouteContext);
                            selectPreferredCenroOfficerRerouteDestination(activeRouteContext);
                        }
                        syncRouteBypassReasonUi(activeRouteContext);
                        syncRouteAttachmentUi(activeRouteContext);
                    });
                });

                routeDestinationOffice.addEventListener('change', function () {
                    if (!activeRouteContext) {
                        return;
                    }
                    syncRouteBypassReasonUi(activeRouteContext);
                    syncRouteAttachmentUi(activeRouteContext);
                });

                if (routeReleaseMode) {
                    routeReleaseMode.addEventListener('change', function () {
                        if (!activeRouteContext || !routeContextSupportsDualRelease(activeRouteContext)) {
                            return;
                        }

                        const needsDestination = routeReleaseRequiresDestination(activeRouteContext);
                        if (needsDestination) {
                            let optionCount = populateDestinationOfficeSelect(activeRouteContext);
                            if (optionCount === 0) {
                                optionCount = forcePopulatePamoRouteDestinations(activeRouteContext);
                            }
                            if (String(routeDestinationOffice.value || '') === '') {
                                autoSelectFirstRouteDestinationOption(activeRouteContext);
                            }
                        } else {
                            routeDestinationOffice.innerHTML = '';
                            const placeholder = document.createElement('option');
                            placeholder.value = '';
                            placeholder.textContent = 'Destination not required';
                            routeDestinationOffice.appendChild(placeholder);
                        }

                        syncDualReleaseModalUi(activeRouteContext);
                        syncRouteBypassReasonUi(activeRouteContext);
                        syncRouteAttachmentUi(activeRouteContext);
                    });
                }

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !routeActionModal.hidden && (!routeActionSubmit || !routeActionSubmit.disabled)) {
                        closeRouteActionModal();
                    }
                });

                routeActionForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    if (!activeRouteContext) {
                        return;
                    }

                    const routeContextAction = String(activeRouteContext.action || '').toUpperCase();
                    const releaseMode = routeContextSupportsDualRelease(activeRouteContext)
                        ? resolveRouteReleaseMode(activeRouteContext)
                        : '';
                    const requiresDestination = routeContextAction !== 'RELEASE'
                        || !routeContextSupportsDualRelease(activeRouteContext)
                        || releaseMode === 'send_to_office';
                    const remarksValue = String(routeRemarks.value || '').trim();
                    let destinationOfficeId = parseInt(String(routeDestinationOffice.value || '0'), 10);
                    if (requiresDestination && (!Number.isFinite(destinationOfficeId) || destinationOfficeId <= 0)) {
                        showAlertDialog('Please select a destination office.');
                        focusSearchableSelect(routeDestinationOffice, false);
                        return;
                    }
                    if (!requiresDestination) {
                        destinationOfficeId = 0;
                    }
                    const selectedOption = requiresDestination
                        ? routeDestinationOffice.options[routeDestinationOffice.selectedIndex]
                        : null;
                    const bypassReasonValue = routeBypassReason ? String(routeBypassReason.value || '').trim() : '';
                    const bypassReasonRequired = routeRequiresOredBypassReason(activeRouteContext, selectedOption);
                    const effectiveRerouteRemarks = remarksValue !== ''
                        ? remarksValue
                        : (bypassReasonRequired && bypassReasonValue !== '' ? ('Bypass reason: ' + bypassReasonValue) : '');
                    if (bypassReasonRequired && bypassReasonValue === '') {
                        const bypassActorLabel = isCenroOfficerRole
                            ? 'CENRO Officer'
                            : 'ORED';
                        showAlertDialog('Bypass reason is required for ' + bypassActorLabel + ' bypass routing.');
                        if (routeBypassReason) {
                            routeBypassReason.focus();
                        }
                        return;
                    }
                    if (((currentRoleKey === 'ORED' && !isCenroOfficerRole) || isArdRole || (currentRoleKey === 'DIVISION_CHIEF' && !isCenroSectionRole) || isCenroOfficerRole || isCenroSectionRole || isPamoOfficerRole) && routeContextAction === 'REROUTE' && effectiveRerouteRemarks === '') {
                        const rerouteActorLabel = isCenroOfficerRole
                            ? 'CENRO Officer'
                            : (isPamoOfficerRole
                                ? 'PASU'
                                : (currentRoleKey === 'ORED'
                                    ? 'ORED'
                            : (currentRoleKey === 'DIVISION_CHIEF'
                                ? (isCenroSectionRole ? 'CENRO Section' : 'Division')
                                : 'ARD')));
                        showAlertDialog(rerouteActorLabel + ' reroute requires remarks describing the reroute reason.');
                        routeRemarks.focus();
                        return;
                    }
                    if (routeContextAction === 'RETURN' && remarksValue === '') {
                        showAlertDialog('Return requires remarks. Please specify the corrections needed by the receiving office.');
                        routeRemarks.focus();
                        return;
                    }

                    const destinationOfficeLabel = requiresDestination
                        ? (selectedOption ? String(selectedOption.textContent || '').trim() : 'selected office')
                        : 'this office';
                    let remarksForSubmit = remarksValue;
                    if (bypassReasonRequired && bypassReasonValue !== '') {
                        const bypassPrefix = 'Bypass reason: ' + bypassReasonValue;
                        if (remarksForSubmit === '') {
                            remarksForSubmit = bypassPrefix;
                        } else if (normalizeLabelKey(remarksForSubmit).indexOf('bypass reason:') === -1) {
                            remarksForSubmit = remarksForSubmit + ' | ' + bypassPrefix;
                        }
                    }
                    const submitButton = routeActionSubmit || null;
                    const triggerButton = activeRouteContext.triggerButton || null;
                    const actionText = routeActionLabel(activeRouteContext);
                    const confirmMessage = requiresDestination
                        ? ('Proceed with ' + actionText + ' to ' + destinationOfficeLabel + '?')
                        : 'Proceed with ' + actionText + ' in this office?';

                    showConfirmDialog(
                        confirmMessage,
                        'Confirm Routing',
                        actionText
                    ).then(function (confirmed) {
                        if (!confirmed) {
                            return;
                        }
                        if (submitButton) {
                            submitButton.disabled = true;
                        }
                        if (triggerButton) {
                            triggerButton.disabled = true;
                        }

                        const attachmentFiles = (routeAttachmentInput && routeAttachmentWrap && !routeAttachmentWrap.hidden && routeAttachmentInput.files)
                            ? Array.from(routeAttachmentInput.files)
                            : [];
                        const nextSubjectValue = (routeNewSubjectWrap && routeNewSubject && !routeNewSubjectWrap.hidden)
                            ? String(routeNewSubject.value || '').trim()
                            : '';
                        if (nextSubjectValue.length > 255) {
                            showAlertDialog('Response is too long. Keep it within 255 characters.');
                            if (submitButton) {
                                submitButton.disabled = false;
                            }
                            if (triggerButton) {
                                triggerButton.disabled = false;
                            }
                            if (routeNewSubject && routeNewSubjectWrap && !routeNewSubjectWrap.hidden) {
                                routeNewSubject.focus();
                            }
                            return;
                        }
                        const selectedAttachmentOption = requiresDestination
                            ? routeDestinationOffice.options[routeDestinationOffice.selectedIndex]
                            : null;
                        const requiresPreparedResponse = allowsPreparedResponseAttachment(activeRouteContext, selectedAttachmentOption);
                        const requiresEndorsementLetter = endorsementLetterRequired(activeRouteContext, selectedAttachmentOption);
                        if (requiresPreparedResponse && attachmentFiles.length < 1) {
                            if (Number.isFinite(routeExistingPreparedResponseCount) && routeExistingPreparedResponseCount < 1) {
                                showAlertDialog('Prepared of response attachment is required. Upload at least one file, or proceed once ' + preparedResponseSourceLabel() + ' already uploaded one.');
                                if (submitButton) {
                                    submitButton.disabled = false;
                                }
                                if (triggerButton) {
                                    triggerButton.disabled = false;
                                }
                                if (routeAttachmentInput && routeAttachmentWrap && !routeAttachmentWrap.hidden) {
                                    routeAttachmentInput.focus();
                                }
                                return;
                            }
                        }
                        if (requiresEndorsementLetter && attachmentFiles.length < 1) {
                            if (Number.isFinite(routeExistingPreparedResponseCount) && routeExistingPreparedResponseCount < 1) {
                                const endorsementRule = routeEndorsementRequirementRule(activeRouteContext, selectedAttachmentOption);
                                const endorsementTarget = endorsementRule.targetLabel || 'destination office';
                                const endorsementVerb = routeContextAction === 'RELEASE' ? 'releasing to' : 'forwarding to';
                                showAlertDialog('Endorsement letter attachment is required before ' + endorsementVerb + ' ' + endorsementTarget + '.');
                                if (routeAttachmentInput && routeAttachmentWrap && !routeAttachmentWrap.hidden) {
                                    routeAttachmentInput.focus();
                                }
                                if (submitButton) {
                                    submitButton.disabled = false;
                                }
                                if (triggerButton) {
                                    triggerButton.disabled = false;
                                }
                                return;
                            }
                        }
                        const routeContextTrackingId = String(activeRouteContext.trackingId || '').trim();
                        sendDocumentAction({
                            action: routeContextAction,
                            documentId: activeRouteContext.documentId,
                            preconditionVersion: Number(activeRouteContext.documentVersion || 0),
                            destinationOfficeId: destinationOfficeId,
                            routeMode: String(activeRouteContext.routeMode || ''),
                            releaseMode: releaseMode,
                            bypassReason: bypassReasonValue,
                            remarks: remarksForSubmit,
                            subject: nextSubjectValue,
                            attachmentFiles: attachmentFiles,
                        }).then(function (result) {
                            if (result && result.queued_offline) {
                                closeRouteActionModal();
                                showAlertDialog(result.message || 'Offline mode: action was queued and will sync automatically.');
                                return;
                            }
                            closeRouteActionModal();
                            showAlertDialog(result.message || 'Action completed.').then(function () {
                                finalizeQueueActionSuccess(routeContextAction, routeContextTrackingId);
                            });
                        }).catch(function (error) {
                            closeRouteActionModal();
                            showAlertDialog(error.message || 'Unable to complete action.');
                        }).finally(function () {
                            if (submitButton) {
                                submitButton.disabled = false;
                            }
                            if (triggerButton) {
                                triggerButton.disabled = false;
                            }
                        });
                    });
                });
            }

            function setEditIntakeSourceVisibility() {
                if (!editIntakeSourceType || !editIntakeExternalWrap || !editIntakeExternalClient) {
                    return;
                }
                const isExternal = String(editIntakeSourceType.value || '').toUpperCase() === 'EXTERNAL';
                const routingOfficeValue = editIntakeRoutingOffice
                    ? String(editIntakeRoutingOffice.value || currentOfficeName || '').trim()
                    : String(currentOfficeName || '').trim();
                editIntakeExternalWrap.hidden = false;
                editIntakeExternalClient.hidden = false;
                editIntakeExternalClient.required = true;
                editIntakeExternalWrap.textContent = isExternal ? 'Client Name' : 'Sender Name';
                editIntakeExternalClient.placeholder = isExternal ? 'Enter client name' : 'Enter sender name';
                if (editIntakeOriginatingEntity) {
                    editIntakeOriginatingEntity.required = true;
                    editIntakeOriginatingEntity.placeholder = isExternal
                        ? 'Enter client or external originating entity'
                        : 'Enter originating office or unit';
                    if (!isExternal && String(editIntakeOriginatingEntity.value || '').trim() === '') {
                        editIntakeOriginatingEntity.value = routingOfficeValue;
                    }
                }
                if (editIntakeClientAddressWrap && editIntakeClientAddress) {
                    editIntakeClientAddressWrap.hidden = false;
                    editIntakeClientAddress.hidden = false;
                    editIntakeClientAddress.required = isExternal;
                    editIntakeClientAddressWrap.textContent = isExternal ? 'Client Address' : 'Office Address';
                    editIntakeClientAddress.placeholder = isExternal ? 'Enter client address' : 'Enter office address';
                }
            }

            function syncEditIntakeComplexityDefaults(forceDays) {
                if (!editIntakeComplexityType || !editIntakeComplexityDays) {
                    return;
                }
                const defaults = customTypeDefaultsForCategory(editIntakeComplexityType.value);
                editIntakeComplexityType.value = defaults.category;
                const currentDaysRaw = String(editIntakeComplexityDays.value || '').trim();
                if (forceDays || currentDaysRaw === '') {
                    editIntakeComplexityDays.value = String(defaults.days);
                }
            }

            function syncEditIntakeComplexityFromDocumentType(forceDays) {
                if (!editIntakeDocumentType || !editIntakeComplexityType) {
                    syncEditIntakeComplexityDefaults(forceDays);
                    return;
                }

                const selectedOption = editIntakeDocumentType.selectedOptions && editIntakeDocumentType.selectedOptions.length > 0
                    ? editIntakeDocumentType.selectedOptions[0]
                    : null;
                const docCategory = String(selectedOption ? (selectedOption.getAttribute('data-category') || '') : '').trim();
                const currentCategory = String(editIntakeComplexityType.value || '').trim();
                if (docCategory !== '') {
                    const resolvedDefaults = customTypeDefaultsForCategory(docCategory);
                    const currentDaysRaw = String(editIntakeComplexityDays ? (editIntakeComplexityDays.value || '') : '').trim();
                    const nextCategory = resolvedDefaults.category;
                    const shouldResetDays = forceDays
                        || currentDaysRaw === ''
                        || currentCategory !== nextCategory;

                    editIntakeComplexityType.value = nextCategory;
                    if (editIntakeComplexityDays && shouldResetDays) {
                        editIntakeComplexityDays.value = String(resolvedDefaults.days);
                    }
                }
                syncEditIntakeComplexityDefaults(forceDays);
            }

            function populateEditIntakeDocumentTypeOptions(documentTypeId, documentTypeLabel) {
                if (!editIntakeDocumentType) {
                    return;
                }
                editIntakeDocumentType.innerHTML = '';

                const baseOptions = [];
                if (intakeDocumentType) {
                    Array.from(intakeDocumentType.options).forEach(function (option) {
                        const value = String(option.value || '').trim();
                        const label = String(option.textContent || '').trim();
                        if (value === '' || label === '') {
                            return;
                        }
                        baseOptions.push({
                            value: value,
                            label: label,
                            category: String(option.getAttribute('data-category') || '').trim(),
                        });
                    });
                }

                if (baseOptions.length === 0) {
                    const fallback = document.createElement('option');
                    fallback.value = '';
                    fallback.textContent = 'No document types available';
                    editIntakeDocumentType.appendChild(fallback);
                    editIntakeDocumentType.disabled = true;
                    return;
                }
                editIntakeDocumentType.disabled = false;

                baseOptions.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = item.value;
                    option.textContent = item.label;
                    if (item.category !== '') {
                        option.setAttribute('data-category', item.category);
                    }
                    editIntakeDocumentType.appendChild(option);
                });

                const preferredId = Number(documentTypeId || 0);
                if (Number.isFinite(preferredId) && preferredId > 0) {
                    editIntakeDocumentType.value = String(preferredId);
                }
                if (String(editIntakeDocumentType.value || '') !== String(preferredId > 0 ? preferredId : '')) {
                    const normalizedTarget = normalizeLabelKey(documentTypeLabel || '');
                    if (normalizedTarget !== '') {
                        const fallbackOption = Array.from(editIntakeDocumentType.options).find(function (option) {
                            return normalizeLabelKey(String(option.textContent || '')).indexOf(normalizedTarget) !== -1;
                        });
                        if (fallbackOption) {
                            editIntakeDocumentType.value = String(fallbackOption.value || '');
                        }
                    }
                }

                syncEditIntakeComplexityFromDocumentType(false);
            }

            function applyEditIntakeComplexityPrefill(categoryRaw, daysRaw) {
                if (!editIntakeComplexityType || !editIntakeComplexityDays) {
                    return;
                }

                const resolvedDefaults = customTypeDefaultsForCategory(categoryRaw);
                editIntakeComplexityType.value = resolvedDefaults.category;

                const parsedDays = Number.parseInt(String(daysRaw || '').trim(), 10);
                if (Number.isFinite(parsedDays) && parsedDays >= 1 && parsedDays <= 365) {
                    editIntakeComplexityDays.value = String(parsedDays);
                } else {
                    editIntakeComplexityDays.value = String(resolvedDefaults.days);
                }
            }

            function renderEditIntakeCurrentAttachments(attachments, documentId) {
                if (!editIntakeCurrentAttachmentsEmpty || !editIntakeCurrentAttachmentsList) {
                    return;
                }
                const list = Array.isArray(attachments) ? attachments : [];
                editIntakeCurrentAttachmentsList.innerHTML = '';
                if (list.length === 0) {
                    editIntakeCurrentAttachmentsEmpty.hidden = false;
                    editIntakeCurrentAttachmentsList.hidden = true;
                    return;
                }

                list.forEach(function (attachment) {
                    const fileName = String(attachment && attachment.file_name ? attachment.file_name : 'Attachment').trim();
                    const attachmentId = Number(attachment && attachment.id ? attachment.id : 0);
                    const canDelete = !!(attachment && attachment.can_delete);
                    const listItem = document.createElement('li');
                    listItem.className = 'edit-intake-attachment-item';

                    const nameNode = document.createElement('span');
                    nameNode.className = 'edit-intake-attachment-name';
                    nameNode.textContent = fileName === '' ? 'Attachment' : fileName;
                    listItem.appendChild(nameNode);

                    if (canDelete && Number.isFinite(attachmentId) && attachmentId > 0) {
                        const deleteButton = document.createElement('button');
                        deleteButton.type = 'button';
                        deleteButton.className = 'edit-intake-attachment-delete';
                        deleteButton.textContent = 'Delete';
                        deleteButton.title = 'Delete this attachment';
                        deleteButton.addEventListener('click', function () {
                            if (!Number.isFinite(documentId) || documentId <= 0) {
                                showAlertDialog('Missing document reference for attachment delete.');
                                return;
                            }

                            showConfirmDialog(
                                'Delete attachment "' + (fileName === '' ? 'Attachment' : fileName) + '"?',
                                'Confirm Attachment Delete',
                                'Delete'
                            ).then(function (confirmed) {
                                if (!confirmed) {
                                    return;
                                }

                                deleteButton.disabled = true;
                                sendDocumentAction({
                                    action: 'DELETE_ATTACHMENT',
                                    documentId: documentId,
                                    attachmentId: attachmentId,
                                    preconditionVersion: Number.isFinite(editIntakeDocumentVersion) && editIntakeDocumentVersion > 0
                                        ? editIntakeDocumentVersion
                                        : 0,
                                }).then(function (result) {
                                    if (result && result.queued_offline) {
                                        showAlertDialog(result.message || 'Offline mode: action was queued and will sync automatically.');
                                        return;
                                    }
                                    const nextVersion = Number(result && result.current_version ? result.current_version : 0);
                                    if (Number.isFinite(nextVersion) && nextVersion > 0) {
                                        editIntakeDocumentVersion = nextVersion;
                                    }
                                    listItem.remove();
                                    if (!editIntakeCurrentAttachmentsList.children.length) {
                                        editIntakeCurrentAttachmentsEmpty.hidden = false;
                                        editIntakeCurrentAttachmentsList.hidden = true;
                                    }
                                    showAlertDialog(result.message || 'Attachment deleted.');
                                }).catch(function (error) {
                                    showAlertDialog(error.message || 'Unable to delete attachment.');
                                }).finally(function () {
                                    deleteButton.disabled = false;
                                });
                            });
                        });
                        listItem.appendChild(deleteButton);
                    }

                    editIntakeCurrentAttachmentsList.appendChild(listItem);
                });

                editIntakeCurrentAttachmentsEmpty.hidden = true;
                editIntakeCurrentAttachmentsList.hidden = false;
            }

            function closeEditIntakeModal() {
                if (!editIntakeModal) {
                    return;
                }
                editIntakeModal.classList.remove('is-open');
                editIntakeModal.hidden = true;
                document.body.classList.remove('modal-open');
                editIntakeTriggerButton = null;
                editIntakeDocumentVersion = 0;
                if (editIntakeForm) {
                    editIntakeForm.reset();
                }
                if (editIntakeDocumentType) {
                    editIntakeDocumentType.innerHTML = '';
                }
                if (editIntakeComplexityType) {
                    editIntakeComplexityType.value = 'Simple';
                }
                if (editIntakeComplexityDays) {
                    editIntakeComplexityDays.value = '3';
                }
                if (editIntakeCurrentAttachmentsList) {
                    editIntakeCurrentAttachmentsList.innerHTML = '';
                    editIntakeCurrentAttachmentsList.hidden = true;
                }
                if (editIntakeCurrentAttachmentsEmpty) {
                    editIntakeCurrentAttachmentsEmpty.hidden = false;
                }
            }

            function openEditIntakeModal(context) {
                if (
                    !editIntakeModal
                    || !editIntakeForm
                    || !editIntakeDocumentId
                    || !editIntakeTrackingId
                    || !editIntakeSourceType
                    || !editIntakeSubject
                    || !editIntakeDocumentType
                    || !editIntakeComplexityType
                    || !editIntakeComplexityDays
                    || !editIntakeRemarks
                ) {
                    showAlertDialog('Edit intake modal is not available on this page.');
                    return;
                }

                const documentId = Number(context && context.documentId ? context.documentId : 0);
                const initialDocumentVersion = Number(context && context.documentVersion ? context.documentVersion : 0);
                const trackingId = String(context && context.trackingId ? context.trackingId : '').trim();
                if (!Number.isFinite(documentId) || documentId <= 0) {
                    showAlertDialog('Missing document reference for edit.');
                    return;
                }
                editIntakeDocumentVersion = Number.isFinite(initialDocumentVersion) && initialDocumentVersion > 0
                    ? initialDocumentVersion
                    : 0;

                editIntakeTriggerButton = context && context.triggerButton ? context.triggerButton : null;
                if (editIntakeTriggerButton) {
                    editIntakeTriggerButton.disabled = true;
                }

                const url = documentDetailsPath + '?document_id=' + encodeURIComponent(String(documentId)) + '&t=' + String(Date.now());
                fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                }).then(function (response) {
                    return response.json().catch(function () {
                        return { ok: false, message: 'Unexpected server response.' };
                    }).then(function (json) {
                        if (!response.ok || !json.ok) {
                            throw new Error(json.message || 'Unable to load editable document details.');
                        }
                        return json;
                    });
                }).then(function (payload) {
                    const doc = payload && payload.document && typeof payload.document === 'object'
                        ? payload.document
                        : {};
                    const attachments = Array.isArray(payload && payload.attachments) ? payload.attachments : [];

                    editIntakeDocumentId.value = String(documentId);
                    editIntakeTrackingId.value = trackingId !== '' ? trackingId : String(doc.tracking_id || '');
                    const fetchedVersion = Number(doc && doc.row_version ? doc.row_version : 0);
                    if (Number.isFinite(fetchedVersion) && fetchedVersion > 0) {
                        editIntakeDocumentVersion = fetchedVersion;
                    }
                    if (editIntakeMeta) {
                        const metaTracking = editIntakeTrackingId.value !== '' ? editIntakeTrackingId.value : ('#' + String(documentId));
                        editIntakeMeta.textContent = 'Editing: ' + metaTracking;
                    }

                    const sourceRaw = String(doc.source_type_raw || doc.source_type || 'INTERNAL').toUpperCase();
                    editIntakeSourceType.value = sourceRaw.indexOf('EXTERNAL') !== -1 ? 'EXTERNAL' : 'INTERNAL';
                    if (editIntakeRoutingOffice) {
                        editIntakeRoutingOffice.value = String(doc.routing_office || doc.originating_office || currentOfficeName || '').trim();
                    }
                    if (editIntakeOriginatingEntity) {
                        editIntakeOriginatingEntity.value = String(doc.originating_entity_name || doc.originating_office || '').trim();
                    }
                    if (editIntakeExternalClient) {
                        editIntakeExternalClient.value = String(doc.sender || doc.external_client_name || '').trim();
                    }
                    if (editIntakeClientAddress) {
                        editIntakeClientAddress.value = String(doc.client_address || '').trim();
                    }
                    setEditIntakeSourceVisibility();

                    editIntakeSubject.value = String(doc.subject || '').trim();
                    populateEditIntakeDocumentTypeOptions(Number(doc.document_type_id || 0), String(doc.document_type || ''));
                    applyEditIntakeComplexityPrefill(
                        String(doc.arta_category_raw || doc.arta_category || ''),
                        Number(doc.arta_days_limit || 0)
                    );
                    renderEditIntakeCurrentAttachments(attachments, documentId);

                    const lastRemarks = String(doc.last_remarks || '').trim();
                    editIntakeRemarks.value = lastRemarks === '-' ? '' : lastRemarks;

                    if (editIntakeAttachments) {
                        editIntakeAttachments.value = '';
                    }

                    editIntakeModal.hidden = false;
                    editIntakeModal.classList.add('is-open');
                    document.body.classList.add('modal-open');
                    editIntakeSubject.focus();
                }).catch(function (error) {
                    showAlertDialog(error.message || 'Unable to load edit form right now.');
                }).finally(function () {
                    if (editIntakeTriggerButton) {
                        editIntakeTriggerButton.disabled = false;
                    }
                });
            }

            function bindEditIntakeModal() {
                if (
                    !editIntakeModal
                    || !editIntakeForm
                    || !editIntakeDocumentId
                    || !editIntakeTrackingId
                    || !editIntakeSourceType
                    || !editIntakeOriginatingEntity
                    || !editIntakeSubject
                    || !editIntakeDocumentType
                    || !editIntakeComplexityType
                    || !editIntakeComplexityDays
                    || !editIntakeRemarks
                    || !editIntakeSubmit
                ) {
                    return;
                }

                editIntakeCloseButtons.forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        if (editIntakeSubmit.disabled) {
                            return;
                        }
                        closeEditIntakeModal();
                    });
                });

                editIntakeSourceType.addEventListener('change', function () {
                    setEditIntakeSourceVisibility();
                });
                editIntakeDocumentType.addEventListener('change', function () {
                    syncEditIntakeComplexityFromDocumentType(true);
                });
                editIntakeComplexityType.addEventListener('change', function () {
                    syncEditIntakeComplexityDefaults(true);
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !editIntakeModal.hidden && !editIntakeSubmit.disabled) {
                        closeEditIntakeModal();
                    }
                });

                editIntakeForm.addEventListener('submit', function (event) {
                    event.preventDefault();

                    const documentId = parseInt(String(editIntakeDocumentId.value || '0'), 10);
                    if (!Number.isFinite(documentId) || documentId <= 0) {
                        showAlertDialog('Missing document reference for edit.');
                        return;
                    }

                    const subjectValue = String(editIntakeSubject.value || '').trim();
                    if (subjectValue === '') {
                        showAlertDialog('Subject is required.');
                        editIntakeSubject.focus();
                        return;
                    }

                    const documentTypeValue = parseInt(String(editIntakeDocumentType.value || '0'), 10);
                    if (!Number.isFinite(documentTypeValue) || documentTypeValue <= 0) {
                        showAlertDialog('Please select a document type.');
                        focusSearchableSelect(editIntakeDocumentType, false);
                        return;
                    }
                    const resolvedComplexity = customTypeDefaultsForCategory(editIntakeComplexityType.value).category;
                    editIntakeComplexityType.value = resolvedComplexity;
                    const complexityDaysRaw = String(editIntakeComplexityDays.value || '').trim();
                    const complexityDays = Number.parseInt(complexityDaysRaw, 10);
                    if (!Number.isFinite(complexityDays) || complexityDays < 1 || complexityDays > 365) {
                        showAlertDialog('Days must be a whole number from 1 to 365.');
                        editIntakeComplexityDays.focus();
                        return;
                    }

                    const sourceValue = String(editIntakeSourceType.value || 'INTERNAL').toUpperCase();
                    const senderValue = editIntakeExternalClient
                        ? String(editIntakeExternalClient.value || '').trim()
                        : '';
                    const originatingEntityValue = editIntakeOriginatingEntity
                        ? String(editIntakeOriginatingEntity.value || '').trim()
                        : '';
                    const clientAddressValue = editIntakeClientAddress
                        ? String(editIntakeClientAddress.value || '').trim()
                        : '';
                    if (senderValue === '') {
                        showAlertDialog(sourceValue === 'EXTERNAL'
                            ? 'Client name is required when source is External.'
                            : 'Sender name is required.'
                        );
                        if (editIntakeExternalClient) {
                            editIntakeExternalClient.focus();
                        }
                        return;
                    }
                    if (originatingEntityValue === '') {
                        showAlertDialog('Originating office / entity is required.');
                        if (editIntakeOriginatingEntity) {
                            editIntakeOriginatingEntity.focus();
                        }
                        return;
                    }
                    if (sourceValue === 'EXTERNAL' && clientAddressValue === '') {
                        showAlertDialog('Client address is required when source is External.');
                        if (editIntakeClientAddress) {
                            editIntakeClientAddress.focus();
                        }
                        return;
                    }

                    const trackingIdValue = String(editIntakeTrackingId.value || '').trim();
                    const confirmLabel = trackingIdValue !== '' ? trackingIdValue : 'this document';
                    showConfirmDialog(
                        'Save updated intake details for ' + confirmLabel + '?',
                        'Confirm Edit',
                        'Save'
                    ).then(function (confirmed) {
                        if (!confirmed) {
                            return;
                        }

                        const formData = new FormData();
                        formData.set('csrf_token', csrfToken);
                        formData.set('action', 'EDIT');
                        formData.set('document_id', String(documentId));
                        formData.set('source_type', sourceValue);
                        formData.set('sender', senderValue);
                        formData.set('originating_entity_name', originatingEntityValue);
                        formData.set('subject', subjectValue);
                        formData.set('document_type_id', String(documentTypeValue));
                        formData.set('intake_complexity_type', resolvedComplexity);
                        formData.set('intake_complexity_days', String(complexityDays));
                        formData.set('remarks', String(editIntakeRemarks.value || '').trim());
                        if (Number.isFinite(editIntakeDocumentVersion) && editIntakeDocumentVersion > 0) {
                            formData.set('precondition_version', String(Math.max(1, parseInt(String(editIntakeDocumentVersion), 10) || 1)));
                        }
                        formData.set('external_client_name', sourceValue === 'EXTERNAL' ? senderValue : '');
                        formData.set('client_address', clientAddressValue);
                        if (editIntakeAttachments && editIntakeAttachments.files) {
                            Array.from(editIntakeAttachments.files).forEach(function (file) {
                                formData.append('attachment_files[]', file);
                            });
                        }

                        editIntakeSubmit.disabled = true;
                        fetch(documentActionPath, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin',
                        }).then(function (response) {
                            return response.json().catch(function () {
                                return { ok: false, message: 'Unexpected server response.' };
                            }).then(function (json) {
                                if (!response.ok || !json.ok) {
                                    throw new Error(json.message || 'Unable to update document.');
                                }
                                return json;
                            });
                        }).then(function (result) {
                            if (result && result.queued_offline) {
                                closeEditIntakeModal();
                                showAlertDialog(result.message || 'Offline mode: action was queued and will sync automatically.');
                                return;
                            }
                            closeEditIntakeModal();
                            showAlertDialog(result.message || 'Document updated.').then(function () {
                                window.location.reload();
                            });
                        }).catch(function (error) {
                            showAlertDialog(error.message || 'Unable to update document.');
                        }).finally(function () {
                            editIntakeSubmit.disabled = false;
                        });
                    });
                });
            }

            function sendDocumentAction(payload) {
                function applyDocumentVersionUpdate(documentId, nextVersion) {
                    const parsedDocumentId = parseInt(String(documentId || '0'), 10);
                    const parsedVersion = parseInt(String(nextVersion || '0'), 10);
                    if (!Number.isFinite(parsedDocumentId) || parsedDocumentId <= 0) {
                        return;
                    }
                    if (!Number.isFinite(parsedVersion) || parsedVersion <= 0) {
                        return;
                    }
                    if (!tableBody) {
                        return;
                    }
                    const row = tableBody.querySelector('tr[data-document-id="' + String(parsedDocumentId) + '"]');
                    if (!row) {
                        return;
                    }
                    row.setAttribute('data-document-version', String(parsedVersion));
                    const buttons = row.querySelectorAll('.quick-action-btn');
                    buttons.forEach(function (btn) {
                        btn.dataset.documentVersion = String(parsedVersion);
                    });
                }

                const hasAttachmentFiles = Array.isArray(payload.attachmentFiles) && payload.attachmentFiles.length > 0;
                let requestBody = null;
                const requestOptions = {
                    method: 'POST',
                    credentials: 'same-origin',
                };

                if (hasAttachmentFiles) {
                    const formData = new FormData();
                    formData.set('csrf_token', csrfToken);
                    formData.set('action', payload.action);
                    if (payload.documentId && payload.documentId > 0) {
                        formData.set('document_id', String(payload.documentId));
                    }
                    if (payload.trackingId) {
                        formData.set('tracking_id', String(payload.trackingId));
                    }
                    if (payload.destinationOfficeId && payload.destinationOfficeId > 0) {
                        formData.set('destination_office_id', String(payload.destinationOfficeId));
                    }
                    if (payload.routeMode) {
                        formData.set('route_mode', String(payload.routeMode));
                    }
                    if (payload.releaseMode) {
                        formData.set('release_mode', String(payload.releaseMode));
                    }
                    if (payload.bypassReason) {
                        formData.set('bypass_reason', String(payload.bypassReason));
                    }
                    if (payload.remarks) {
                        formData.set('remarks', payload.remarks);
                    }
                    if (payload.subject) {
                        formData.set('subject', String(payload.subject));
                    }
                    if (payload.attachmentId && payload.attachmentId > 0) {
                        formData.set('attachment_id', String(payload.attachmentId));
                    }
                    if (payload.receiveMethod) {
                        formData.set('receive_method', String(payload.receiveMethod));
                    }
                    if (payload.preconditionVersion && Number(payload.preconditionVersion) > 0) {
                        formData.set('precondition_version', String(Math.max(1, parseInt(String(payload.preconditionVersion), 10) || 1)));
                    }
                    payload.attachmentFiles.forEach(function (file) {
                        formData.append('attachment_files[]', file);
                    });
                    requestBody = formData;
                } else {
                    const body = new URLSearchParams();
                    body.set('csrf_token', csrfToken);
                    body.set('action', payload.action);
                    if (payload.documentId && payload.documentId > 0) {
                        body.set('document_id', String(payload.documentId));
                    }
                    if (payload.trackingId) {
                        body.set('tracking_id', String(payload.trackingId));
                    }
                    if (payload.destinationOfficeId && payload.destinationOfficeId > 0) {
                        body.set('destination_office_id', String(payload.destinationOfficeId));
                    }
                    if (payload.routeMode) {
                        body.set('route_mode', String(payload.routeMode));
                    }
                    if (payload.releaseMode) {
                        body.set('release_mode', String(payload.releaseMode));
                    }
                    if (payload.bypassReason) {
                        body.set('bypass_reason', String(payload.bypassReason));
                    }
                    if (payload.remarks) {
                        body.set('remarks', payload.remarks);
                    }
                    if (payload.subject) {
                        body.set('subject', String(payload.subject));
                    }
                    if (payload.attachmentId && payload.attachmentId > 0) {
                        body.set('attachment_id', String(payload.attachmentId));
                    }
                    if (payload.receiveMethod) {
                        body.set('receive_method', String(payload.receiveMethod));
                    }
                    if (payload.preconditionVersion && Number(payload.preconditionVersion) > 0) {
                        body.set('precondition_version', String(Math.max(1, parseInt(String(payload.preconditionVersion), 10) || 1)));
                    }
                    requestOptions.headers = {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    };
                    requestBody = body.toString();
                }

                requestOptions.body = requestBody;

                return fetch(documentActionPath, requestOptions).then(function (response) {
                    return response.json().catch(function () {
                        return { ok: false, message: 'Unexpected server response.' };
                    }).then(function (json) {
                        if (!response.ok || !json.ok) {
                            throw new Error(json.message || 'Action failed.');
                        }
                        const updatedVersion = Number(json && json.current_version ? json.current_version : 0);
                        if (payload.documentId && payload.documentId > 0 && Number.isFinite(updatedVersion) && updatedVersion > 0) {
                            applyDocumentVersionUpdate(payload.documentId, updatedVersion);
                        }
                        return json;
                    });
                });
            }

            function requestLiveDashboardRefresh() {
                if (!liveDashboardRefreshEnabled || !dashboardLivePath) {
                    return false;
                }
                if (liveRefreshInFlight) {
                    liveRefreshPending = true;
                    return true;
                }
                pollLiveDashboardData();
                return true;
            }

            function finalizeQueueActionSuccess(action, trackingId) {
                if (redirectToRecordsUnitActionStamp(action, trackingId)) {
                    return;
                }
                if (requestLiveDashboardRefresh()) {
                    return;
                }
                window.location.reload();
            }

            function qrSetStatus(message, isError) {
                if (!qrReceiveStatus) {
                    return;
                }
                qrReceiveStatus.textContent = String(message || '');
                qrReceiveStatus.classList.toggle('error', !!isError);
            }

            function extractTrackingId(rawValue) {
                const raw = String(rawValue || '').trim();
                if (raw === '') {
                    return '';
                }

                try {
                    const parsed = new URL(raw, window.location.origin);
                    const queryTracking = String(parsed.searchParams.get('tracking_id') || '').trim();
                    if (queryTracking !== '') {
                        return queryTracking.toUpperCase();
                    }
                } catch (error) {
                    // Continue with pattern matching.
                }

                const denrMatch = raw.match(/DENR-XII-\d{4}-\d{4}/i);
                if (denrMatch && denrMatch[0]) {
                    return String(denrMatch[0]).toUpperCase();
                }

                const legacyMatch = raw.match(/R12-[A-Z]{2,8}-\d{4}-\d{3,4}/i);
                if (legacyMatch && legacyMatch[0]) {
                    return String(legacyMatch[0]).toUpperCase();
                }

                return raw.toUpperCase();
            }

            function findDocumentIdByTrackingId(trackingId) {
                if (!tableBody || trackingId === '') {
                    return 0;
                }
                const rows = Array.from(tableBody.querySelectorAll('tr'));
                for (let i = 0; i < rows.length; i += 1) {
                    const rowTrackingId = String(rows[i].getAttribute('data-tracking-id') || '').trim().toUpperCase();
                    if (rowTrackingId === trackingId) {
                        const rowDocumentId = parseInt(String(rows[i].getAttribute('data-document-id') || '0'), 10);
                        if (Number.isFinite(rowDocumentId) && rowDocumentId > 0) {
                            return rowDocumentId;
                        }
                    }
                }
                return 0;
            }

            function findDocumentVersionByTrackingId(trackingId) {
                if (!tableBody || trackingId === '') {
                    return 0;
                }
                const rows = Array.from(tableBody.querySelectorAll('tr'));
                for (let i = 0; i < rows.length; i += 1) {
                    const rowTrackingId = String(rows[i].getAttribute('data-tracking-id') || '').trim().toUpperCase();
                    if (rowTrackingId !== trackingId) {
                        continue;
                    }
                    const rowVersion = parseInt(String(rows[i].getAttribute('data-document-version') || '0'), 10);
                    if (Number.isFinite(rowVersion) && rowVersion > 0) {
                        return rowVersion;
                    }
                }
                return 0;
            }

            function stopQrScanner() {
                qrIsActive = false;
                if (qrScanRafId) {
                    window.cancelAnimationFrame(qrScanRafId);
                    qrScanRafId = 0;
                }
                if (qrScanStream) {
                    qrScanStream.getTracks().forEach(function (track) {
                        track.stop();
                    });
                    qrScanStream = null;
                }
                if (qrReceiveVideo) {
                    qrReceiveVideo.pause();
                    qrReceiveVideo.srcObject = null;
                }
                if (qrReceiveViewport) {
                    qrReceiveViewport.hidden = true;
                }
                if (qrReceiveStartBtn) {
                    qrReceiveStartBtn.disabled = false;
                }
                if (qrReceiveStopBtn) {
                    qrReceiveStopBtn.disabled = true;
                }
            }

            function receiveByTrackingId(rawValue, receiveSourceLabel) {
                const trackingId = extractTrackingId(rawValue);
                if (trackingId === '') {
                    qrSetStatus('Enter a valid tracking ID or tracking-slip URL.', true);
                    return;
                }

                if (qrReceiveTrackingInput) {
                    qrReceiveTrackingInput.value = trackingId;
                }

                if (queueSearchInput) {
                    queueSearchInput.value = trackingId;
                    applyTableFilters();
                }

                const documentId = findDocumentIdByTrackingId(trackingId);
                const documentVersion = findDocumentVersionByTrackingId(trackingId);
                if (qrReceiveManualBtn) {
                    qrReceiveManualBtn.disabled = true;
                }
                if (documentId <= 0) {
                    qrSetStatus('Tracking ID is not in the visible queue table. Trying secure server lookup...', false);
                } else {
                    qrSetStatus('Processing receive action for ' + trackingId + '...', false);
                }

                const receivePayload = {
                    action: 'RECEIVE',
                    remarks: receiveSourceLabel === 'QR' ? 'Received via QR scanner.' : 'Received via quick tracking input.',
                    receiveMethod: 'MANUAL',
                };
                if (documentId > 0) {
                    receivePayload.documentId = documentId;
                    if (Number.isFinite(documentVersion) && documentVersion > 0) {
                        receivePayload.preconditionVersion = documentVersion;
                    }
                } else {
                    receivePayload.trackingId = trackingId;
                }

                sendDocumentAction(receivePayload).then(function (result) {
                    if (result && result.queued_offline) {
                        qrSetStatus(result.message || 'Offline mode: receive action queued and will sync automatically.', false);
                        showAlertDialog(result.message || 'Offline mode: receive action queued and will sync automatically.');
                        return;
                    }
                    showAlertDialog(result.message || 'Document received.').then(function () {
                        if (redirectToRecordsUnitActionStamp('RECEIVE', trackingId)) {
                            return;
                        }
                        window.location.reload();
                    });
                }).catch(function (error) {
                    qrSetStatus(error.message || 'Unable to receive document.', true);
                }).finally(function () {
                    if (qrReceiveManualBtn) {
                        qrReceiveManualBtn.disabled = false;
                    }
                });
            }

            // jsQR fallback: used when BarcodeDetector is unavailable (Firefox, older Chrome, non-secure IP).
            let jsQRLib = null;

            function loadJsQR() {
                return new Promise(function (resolve, reject) {
                    if (jsQRLib) {
                        resolve(jsQRLib);
                        return;
                    }
                    if (window.jsQR) {
                        jsQRLib = window.jsQR;
                        resolve(jsQRLib);
                        return;
                    }
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js';
                    script.onload = function () {
                        jsQRLib = window.jsQR || null;
                        if (jsQRLib) { resolve(jsQRLib); } else { reject(new Error('jsQR failed to load.')); }
                    };
                    script.onerror = function () { reject(new Error('Unable to load QR library. Check internet connection.')); };
                    document.head.appendChild(script);
                });
            }

            async function scanQrFrame() {
                if (!qrIsActive || !qrReceiveVideo) {
                    return;
                }

                if (qrReceiveVideo.readyState >= 2 && !qrDetectInProgress) {
                    qrDetectInProgress = true;
                    try {
                        let rawValue = '';

                        if (qrDetector) {
                            // BarcodeDetector path (Chrome/Edge with secure context)
                            const detections = await qrDetector.detect(qrReceiveVideo);
                            if (Array.isArray(detections) && detections.length > 0) {
                                rawValue = String(detections[0].rawValue || '').trim();
                            }
                        } else if (jsQRLib) {
                            // jsQR canvas fallback path
                            const canvas = document.createElement('canvas');
                            canvas.width = qrReceiveVideo.videoWidth || 640;
                            canvas.height = qrReceiveVideo.videoHeight || 480;
                            const ctx = canvas.getContext('2d');
                            if (ctx) {
                                ctx.drawImage(qrReceiveVideo, 0, 0, canvas.width, canvas.height);
                                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                                const code = jsQRLib(imageData.data, imageData.width, imageData.height);
                                if (code) {
                                    rawValue = String(code.data || '').trim();
                                }
                            }
                        }

                        if (rawValue !== '') {
                            const trackingId = extractTrackingId(rawValue);
                            if (trackingId !== '') {
                                stopQrScanner();
                                receiveByTrackingId(trackingId, 'QR');
                                return;
                            }
                        }
                    } catch (error) {
                        // Ignore intermittent detector failures and keep scanning.
                    } finally {
                        qrDetectInProgress = false;
                    }
                }

                qrScanRafId = window.requestAnimationFrame(scanQrFrame);
            }

            async function startQrScanner() {
                if (!qrReceivePanel || !qrReceiveVideo || !qrReceiveStartBtn || !qrReceiveStopBtn || !qrReceiveViewport) {
                    return;
                }

                if (!window.isSecureContext) {
                    qrSetStatus('Camera QR scan needs HTTPS or localhost. This page is not in a secure context, so use manual Tracking ID input.', true);
                    return;
                }

                const hasBarcodeDetector = ('BarcodeDetector' in window);

                if (!hasBarcodeDetector) {
                    // Try jsQR fallback before giving up
                    qrSetStatus('Loading QR fallback library...', false);
                    try {
                        await loadJsQR();
                        qrSetStatus('QR library loaded (fallback mode). Starting camera...', false);
                    } catch (loadError) {
                        qrSetStatus(
                            'QR camera fallback library could not be loaded. ' +
                            'Use manual Tracking ID input, or open the page in Chrome on localhost/HTTPS.',
                            true
                        );
                        return;
                    }
                } else {
                    try {
                        const supportedFormats = await BarcodeDetector.getSupportedFormats();
                        if (Array.isArray(supportedFormats) && supportedFormats.length > 0 && supportedFormats.indexOf('qr_code') === -1) {
                            qrSetStatus('Camera opened, but QR format is unavailable in this browser profile. Try updated Chrome over HTTPS or use manual Tracking ID input.', true);
                            return;
                        }
                    } catch (error) {
                        // Continue; some browsers do not expose supported format list reliably.
                    }
                    try {
                        qrDetector = new BarcodeDetector({ formats: ['qr_code'] });
                    } catch (error) {
                        qrDetector = null;
                    }
                }

                try {
                    qrScanStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: { ideal: 'environment' },
                            width: { ideal: 1280 },
                            height: { ideal: 720 },
                        },
                        audio: false,
                    });

                    qrReceiveVideo.srcObject = qrScanStream;
                    qrReceiveViewport.hidden = false;
                    await qrReceiveVideo.play();

                    qrIsActive = true;
                    qrReceiveStartBtn.disabled = true;
                    qrReceiveStopBtn.disabled = false;
                    qrSetStatus(
                        hasBarcodeDetector
                            ? 'Scanner active. Align QR code inside the focus frame.'
                            : 'Scanner active (fallback mode). Align QR code inside the focus frame.',
                        false
                    );

                    if (qrScanRafId) {
                        window.cancelAnimationFrame(qrScanRafId);
                        qrScanRafId = 0;
                    }
                    scanQrFrame();
                } catch (error) {
                    stopQrScanner();
                    qrSetStatus('Unable to access camera for QR scanning. Allow camera permission or use manual input.', true);
                }
            }

            function closeQrReceiveModal() {
                if (!qrReceiveModal) {
                    return;
                }
                stopQrScanner();
                qrReceiveModal.classList.remove('is-open');
                qrReceiveModal.hidden = true;
                document.body.classList.remove('modal-open');
            }

            function openQrReceiveModal() {
                if (!qrReceiveModal || !qrReceivePanel) {
                    return;
                }
                qrReceiveModal.hidden = false;
                qrReceiveModal.classList.add('is-open');
                document.body.classList.add('modal-open');
                if (qrReceiveTrackingInput) {
                    qrReceiveTrackingInput.focus();
                }
                if (qrReceiveStartBtn && !qrReceiveStartBtn.disabled) {
                    qrReceiveStartBtn.click();
                }
            }

            function bindQrReceivePanel() {
                if (!qrReceivePanel) {
                    return;
                }

                if (openQrReceiveScannerBtn) {
                    openQrReceiveScannerBtn.addEventListener('click', function () {
                        openQrReceiveModal();
                    });
                }

                qrReceiveCloseButtons.forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        closeQrReceiveModal();
                    });
                });

                if (qrReceiveManualBtn && qrReceiveTrackingInput) {
                    qrReceiveManualBtn.addEventListener('click', function () {
                        receiveByTrackingId(qrReceiveTrackingInput.value, 'MANUAL');
                    });
                }

                if (qrReceiveTrackingInput) {
                    qrReceiveTrackingInput.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            receiveByTrackingId(qrReceiveTrackingInput.value, 'MANUAL');
                        }
                    });
                }

                if (qrReceiveStartBtn) {
                    qrReceiveStartBtn.addEventListener('click', function () {
                        startQrScanner();
                    });
                }

                if (qrReceiveStopBtn) {
                    qrReceiveStopBtn.addEventListener('click', function () {
                        stopQrScanner();
                        qrSetStatus('Scanner stopped.', false);
                    });
                }

                window.addEventListener('beforeunload', function () {
                    stopQrScanner();
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && qrReceiveModal && !qrReceiveModal.hidden) {
                        closeQrReceiveModal();
                    }
                });
            }

            function getRowCellTextByHeader(row, headerToken) {
                if (!tableElement || !row) {
                    return '';
                }
                const token = normalizeLabelKey(headerToken || '');
                if (token === '') {
                    return '';
                }
                const headers = Array.from(tableElement.querySelectorAll('thead th'));
                const columnIndex = headers.findIndex(function (headerNode) {
                    return normalizeLabelKey(headerNode.textContent || '').indexOf(token) !== -1;
                });
                if (columnIndex < 0) {
                    return '';
                }
                const cells = row.querySelectorAll('td');
                if (!cells[columnIndex]) {
                    return '';
                }
                return String(cells[columnIndex].textContent || '').trim();
            }

            function bindQueueActionHandlers() {
                document.addEventListener('click', function (event) {
                    const button = event.target.closest('.quick-action-btn');
                    if (!button) {
                        return;
                    }

                    event.preventDefault();
                    const action = String(button.dataset.action || '').toUpperCase();
                    const documentId = parseInt(String(button.dataset.documentId || '0'), 10);
                    const trackingId = String(button.dataset.trackingId || '').trim();
                    const hasSectionReceive = String(button.dataset.hasSectionReceive || '0') === '1';
                    const hasSignedAction = String(button.dataset.hasSignedAction || '0') === '1';
                    const hasReturnAction = String(button.dataset.hasReturnAction || '0') === '1';
                    const createdByUserId = parseInt(String(button.dataset.createdByUserId || '0'), 10);
                    const currentStatus = String(button.dataset.status || '').trim();
                    const originOfficeId = parseInt(String(button.dataset.originOfficeId || '0'), 10);
                    const rowCurrentOfficeId = parseInt(String(button.dataset.currentOfficeId || '0'), 10);
                    const pendingOfficeId = parseInt(String(button.dataset.pendingOfficeId || '0'), 10);
                    const documentVersion = parseInt(String(button.dataset.documentVersion || '0'), 10);
                    const normalizedCurrentStatus = normalizeLabelKey(currentStatus);
                    const penroCreatorDraftDirectForward = isPenroAdminRecordRole
                        && Number.isFinite(createdByUserId)
                        && createdByUserId > 0
                        && createdByUserId === currentUserId
                        && normalizedCurrentStatus === 'created'
                        && !(Number.isFinite(pendingOfficeId) && pendingOfficeId > 0);
                    const canManageIntakeRow = queueRowCanManageIntake({
                        created_by_user_id: Number.isFinite(createdByUserId) && createdByUserId > 0 ? createdByUserId : 0,
                        status: currentStatus,
                        pending_office_id: Number.isFinite(pendingOfficeId) && pendingOfficeId > 0 ? pendingOfficeId : 0,
                        current_office_id: Number.isFinite(rowCurrentOfficeId) && rowCurrentOfficeId > 0 ? rowCurrentOfficeId : 0,
                        origin_office_id: Number.isFinite(originOfficeId) && originOfficeId > 0 ? originOfficeId : 0,
                        has_return_action: hasReturnAction ? 1 : 0,
                    });
                    const routeMode = normalizeLabelKey(button.dataset.routeMode || '');
                    if (!Number.isFinite(documentId) || documentId <= 0) {
                        showAlertDialog('Missing document reference. Refresh and try again.');
                        return;
                    }

                    if (
                        action === 'RECEIVE'
                        && !queueRowAllowsReceive(currentStatus, pendingOfficeId, rowCurrentOfficeId)
                    ) {
                        showAlertDialog('This document is not currently awaiting receive in your office.');
                        return;
                    }

                    if (
                        action === 'RECEIVE'
                        && isPenroAdminRecordRole
                        && !hasReturnAction
                        && normalizeLabelKey(currentStatus).indexOf('return') === -1
                        && normalizeLabelKey(currentStatus).indexOf('released') === -1
                        && normalizeLabelKey(currentStatus).indexOf('release') === -1
                        && Number.isFinite(originOfficeId)
                        && originOfficeId > 0
                        && Number.isFinite(currentOfficeId)
                        && currentOfficeId > 0
                        && originOfficeId === currentOfficeId
                    ) {
                        showAlertDialog('Receive is hidden for PENRO Admin Record-origin documents already in PENRO Admin Record. Use Forward to route to RECORDS-UNIT.');
                        return;
                    }
                    if (
                        currentRoleKey === 'ORED'
                        && action !== 'RECEIVE'
                        && !(enableQueueRerouteAction && action === 'REROUTE')
                        && !queueRowHasReceivedState(currentStatus, pendingOfficeId, rowCurrentOfficeId)
                    ) {
                        showAlertDialog(executiveRoleLabel + ' must click Receive first before other actions.');
                        return;
                    }

                    if (
                        action === 'SIGN'
                        && currentRoleKey === 'ORED'
                        && regionalOredDigitalSignDisabled
                    ) {
                        showAlertDialog('Digital signing is available only for the ORED signing account.');
                        return;
                    }
                    if (
                        action === 'SIGN'
                        && currentRoleKey === 'ORED'
                        && !queueRowHasReceivedState(currentStatus, pendingOfficeId, rowCurrentOfficeId)
                    ) {
                        showAlertDialog(executiveRoleLabel + ' must click Receive first before Sign.');
                        return;
                    }
                    if (
                        action === 'UNSIGN'
                        && (currentRoleKey === 'ORED' || isPenroOfficerRole)
                        && (currentRoleKey !== 'ORED' || !regionalOredDigitalSignDisabled)
                        && !queueStatusIsSignedCompleted(currentStatus)
                        && !hasSignedAction
                    ) {
                        showAlertDialog('Undo Sign is available only after the document is signed.');
                        return;
                    }
                    if (
                        action === 'UNSIGN'
                        && (currentRoleKey === 'ORED' || isPenroOfficerRole)
                        && (currentRoleKey !== 'ORED' || !regionalOredDigitalSignDisabled)
                        && queueStatusIsForwarded(currentStatus)
                    ) {
                        showAlertDialog('Undo Sign is available only before forwarding.');
                        return;
                    }
                    if (
                        action === 'FORWARD'
                        && currentRoleKey === 'ORED'
                        && !queueRowHasReceivedState(currentStatus, pendingOfficeId, rowCurrentOfficeId)
                        && !(queueStatusIsSignedCompleted(currentStatus) || hasSignedAction)
                    ) {
                        showAlertDialog(executiveRoleLabel + ' must click Receive first before Forward.');
                        return;
                    }
                    if (
                        action === 'OVERRIDE'
                        && currentRoleKey === 'ORED'
                        && regionalOredDigitalSignDisabled
                    ) {
                        showAlertDialog('Override is available only for the ORED signing account.');
                        return;
                    }
                    if (
                        action === 'OVERRIDE'
                        && currentRoleKey === 'ORED'
                        && !isCenroOfficerRole
                        && !(
                            queueStatusIsForwarded(currentStatus)
                            || (
                                Number.isFinite(pendingOfficeId)
                                && pendingOfficeId > 0
                                && Number.isFinite(currentOfficeId)
                                && currentOfficeId > 0
                                && pendingOfficeId !== currentOfficeId
                            )
                        )
                    ) {
                        showAlertDialog('Override becomes available after the document is forwarded.');
                        return;
                    }
                    if (
                        action === 'FORWARD'
                        && isCenroAdminRecordRole
                        && !queueRowHasReceivedState(currentStatus, pendingOfficeId, rowCurrentOfficeId)
                    ) {
                        showAlertDialog('CENRO Admin Record must click Receive first before Forward.');
                        return;
                    }
                    if (
                        action === 'FORWARD'
                        && isPenroAdminRecordRole
                        && !penroCreatorDraftDirectForward
                        && !canManageIntakeRow
                        && !queueRowHasReceivedState(currentStatus, pendingOfficeId, rowCurrentOfficeId)
                    ) {
                        showAlertDialog('PENRO Admin Record must click Receive first before Forward.');
                        return;
                    }
                    if (
                        action === 'FORWARD'
                        && (currentRoleKey === 'DIVISION_CHIEF' || isArdRole)
                        && !queueRowHasReceivedState(currentStatus, pendingOfficeId, rowCurrentOfficeId)
                    ) {
                        showAlertDialog((isArdRole ? 'ARD' : 'Division Chief') + ' must click Receive first before Forward.');
                        return;
                    }
                    if (
                        action === 'FORWARD'
                        && currentRoleKey === 'SECTION_STAFF'
                        && !queueRowHasReceivedState(currentStatus, pendingOfficeId, rowCurrentOfficeId)
                    ) {
                        showAlertDialog('Section Staff must click Receive first before Forward.');
                        return;
                    }
                    if (
                        action === 'FORWARD'
                        && currentRoleKey === 'RECORDS_UNIT'
                        && !isCenroAdminRecordRole
                        && !isPenroAdminRecordRole
                        && !isPamoAdminRole
                        && !queueRowCanManageIntake({
                            created_by_user_id: createdByUserId,
                            status: currentStatus,
                            pending_office_id: pendingOfficeId,
                            current_office_id: rowCurrentOfficeId,
                            origin_office_id: originOfficeId,
                            has_return_action: hasReturnAction ? 1 : 0,
                        })
                        && !queueRowHasReceivedState(currentStatus, pendingOfficeId, rowCurrentOfficeId)
                    ) {
                        showAlertDialog('RECORDS-UNIT must click Receive first before Forward.');
                        return;
                    }
                    if (
                        action === 'RELEASE'
                        && currentRoleKey === 'RECORDS_UNIT'
                        && !isCenroAdminRecordRole
                        && !isPenroAdminRecordRole
                        && !isPamoAdminRole
                        && !hasSignedAction
                    ) {
                        showAlertDialog('Release is available only after ORED signs/completes the document and routes it back to RECORDS-UNIT.');
                        return;
                    }
                    if (
                        action === 'RELEASE'
                        && currentRoleKey === 'RECORDS_UNIT'
                        && !isCenroAdminRecordRole
                        && !isPenroAdminRecordRole
                        && !isPamoAdminRole
                        && normalizeLabelKey(currentStatus).indexOf('released') !== -1
                        && Number.isFinite(pendingOfficeId)
                        && pendingOfficeId > 0
                        && Number.isFinite(currentOfficeId)
                        && currentOfficeId > 0
                        && pendingOfficeId !== currentOfficeId
                    ) {
                        showAlertDialog('This document is already sent to the originating office and is now waiting for their Receive confirmation.');
                        return;
                    }

                    if (
                        action === 'FORWARD'
                        && Number.isFinite(pendingOfficeId)
                        && pendingOfficeId > 0
                        && Number.isFinite(currentOfficeId)
                        && currentOfficeId > 0
                        && pendingOfficeId !== currentOfficeId
                    ) {
                        showAlertDialog('This document is already forwarded and waiting for the next office to receive it.');
                        return;
                    }

                    if (action === 'EDIT') {
                        if (!queueRowCanManageIntake({
                            created_by_user_id: createdByUserId,
                            status: currentStatus,
                            pending_office_id: pendingOfficeId,
                            current_office_id: rowCurrentOfficeId,
                            origin_office_id: originOfficeId,
                            has_return_action: hasReturnAction ? 1 : 0,
                        })) {
                            showAlertDialog('Edit is allowed only for your intake draft or a returned correction file now in your office custody.');
                            return;
                        }
                        openEditIntakeModal({
                            documentId: documentId,
                            documentVersion: Number.isFinite(documentVersion) && documentVersion > 0 ? documentVersion : 0,
                            trackingId: trackingId,
                            triggerButton: button,
                        });
                        return;
                    }

                    if (action === 'DELETE') {
                        if (!queueRowCanManageIntake({
                            created_by_user_id: createdByUserId,
                            status: currentStatus,
                            pending_office_id: pendingOfficeId,
                            current_office_id: rowCurrentOfficeId,
                            origin_office_id: originOfficeId,
                            has_return_action: hasReturnAction ? 1 : 0,
                        })) {
                            showAlertDialog('Delete is allowed only for your intake draft or a returned correction file now in your office custody.');
                            return;
                        }

                        showConfirmDialog(
                            'Delete ' + (trackingId !== '' ? trackingId : 'this document') + '? This action cannot be undone.',
                            'Confirm Delete',
                            'Delete'
                        ).then(function (confirmed) {
                            if (!confirmed) {
                                return;
                            }

                            button.disabled = true;
                            sendDocumentAction({
                                action: 'DELETE',
                                documentId: documentId,
                                preconditionVersion: Number.isFinite(documentVersion) && documentVersion > 0 ? documentVersion : 0,
                            }).then(function (result) {
                                if (result && result.queued_offline) {
                                    showAlertDialog(result.message || 'Offline mode: action was queued and will sync automatically.');
                                    return;
                                }
                                showAlertDialog(result.message || 'Document deleted.').then(function () {
                                    window.location.reload();
                                });
                            }).catch(function (error) {
                                showAlertDialog(error.message || 'Unable to delete document.');
                            }).finally(function () {
                                button.disabled = false;
                            });
                        });
                        return;
                    }

                    if (
                        action === 'SIGN'
                        && String(currentRoleKey || '').toUpperCase() === 'ORED'
                        && String(digitalSignatureWorkspacePath || '').trim() !== ''
                    ) {
                        let targetUrl = '';
                        try {
                            const parsed = new URL(String(digitalSignatureWorkspacePath), window.location.origin);
                            if (Number.isFinite(documentId) && documentId > 0) {
                                parsed.searchParams.set('document_id', String(documentId));
                            }
                            if (trackingId !== '') {
                                parsed.searchParams.set('tracking_id', trackingId);
                            }
                            parsed.searchParams.set('return_to', 'rd-action-desk.php');
                            targetUrl = parsed.toString();
                        } catch (error) {
                            targetUrl = String(digitalSignatureWorkspacePath);
                        }
                        window.location.href = targetUrl;
                        return;
                    }

                    if (isRoutingAction(action)) {
                        if (
                            action === 'RELEASE'
                            && currentRoleKey === 'RECORDS_UNIT'
                            && !isCenroAdminRecordRole
                            && !isPenroAdminRecordRole
                            && !isPamoAdminRole
                            && Number.isFinite(originOfficeId)
                            && originOfficeId > 0
                        ) {
                            const releaseLabel = 'originating office';
                            showConfirmDialog(
                                'Proceed to Release this document to the ' + releaseLabel + '?',
                                'Confirm Release',
                                'Release'
                            ).then(function (confirmed) {
                                if (!confirmed) {
                                    return;
                                }

                                button.disabled = true;
                                sendDocumentAction({
                                    action: action,
                                    documentId: documentId,
                                    destinationOfficeId: originOfficeId,
                                    preconditionVersion: Number.isFinite(documentVersion) && documentVersion > 0 ? documentVersion : 0,
                                }).then(function (result) {
                                    if (result && result.queued_offline) {
                                        showAlertDialog(result.message || 'Offline mode: action was queued and will sync automatically.');
                                        return;
                                    }
                                    showAlertDialog(result.message || 'Action completed.').then(function () {
                                        finalizeQueueActionSuccess(action, trackingId);
                                    });
                                }).catch(function (error) {
                                    showAlertDialog(error.message || 'Unable to complete action.');
                                }).finally(function () {
                                    button.disabled = false;
                                });
                            });
                            return;
                        }

                        openRouteActionModal({
                            action: action,
                            documentId: documentId,
                            documentVersion: Number.isFinite(documentVersion) && documentVersion > 0 ? documentVersion : 0,
                            trackingId: trackingId,
                            hasSectionReceive: hasSectionReceive,
                            hasSignedAction: hasSignedAction,
                            currentStatus: currentStatus,
                            currentOfficeId: Number.isFinite(rowCurrentOfficeId) && rowCurrentOfficeId > 0 ? rowCurrentOfficeId : 0,
                            currentOfficeParentId: Number.isFinite(currentOfficeParentId) && currentOfficeParentId > 0 ? currentOfficeParentId : 0,
                            pendingOfficeId: Number.isFinite(pendingOfficeId) && pendingOfficeId > 0 ? pendingOfficeId : 0,
                            originOfficeId: Number.isFinite(originOfficeId) && originOfficeId > 0 ? originOfficeId : 0,
                            routeMode: routeMode,
                            triggerButton: button,
                        });
                        return;
                    }

                    const actionText = actionLabel(action);
                    showConfirmDialog(
                        'Proceed to ' + actionText + (trackingId !== '' ? (' document ' + trackingId) : ' this document') + '?',
                        'Confirm Action',
                        actionText
                    ).then(function (confirmed) {
                        if (!confirmed) {
                            return;
                        }

                        button.disabled = true;
                        sendDocumentAction({
                            action: action,
                            documentId: documentId,
                            preconditionVersion: Number.isFinite(documentVersion) && documentVersion > 0 ? documentVersion : 0,
                        }).then(function (result) {
                            if (result && result.queued_offline) {
                                showAlertDialog(result.message || 'Offline mode: action was queued and will sync automatically.');
                                return;
                            }
                            showAlertDialog(result.message || 'Action completed.').then(function () {
                                if (redirectToRecordsUnitActionStamp(action, trackingId)) {
                                    return;
                                }
                                window.location.reload();
                            });
                        }).catch(function (error) {
                            showAlertDialog(error.message || 'Unable to complete action.');
                        }).finally(function () {
                            button.disabled = false;
                        });
                    });
                });
            }

            function handleWelcomeLoader() {
                if (!welcomeLoader) {
                    return;
                }
                const rawDuration = parseInt(String(welcomeLoader.getAttribute('data-duration-ms') || '1000'), 10);
                const duration = Number.isFinite(rawDuration) && rawDuration > 0 ? rawDuration : 1000;
                window.setTimeout(function () {
                    welcomeLoader.classList.add('is-leaving');
                    window.setTimeout(function () {
                        if (welcomeLoader.parentNode) {
                            welcomeLoader.parentNode.removeChild(welcomeLoader);
                        }
                    }, 240);
                }, duration);
            }

            function parseDateInputValue(value, endOfDay) {
                const raw = String(value || '').trim();
                if (raw === '') {
                    return null;
                }

                const dateOnlyMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                if (dateOnlyMatch) {
                    const year = parseInt(dateOnlyMatch[1], 10);
                    const monthIndex = parseInt(dateOnlyMatch[2], 10) - 1;
                    const day = parseInt(dateOnlyMatch[3], 10);
                    const parsedDate = endOfDay
                        ? new Date(year, monthIndex, day, 23, 59, 59, 999)
                        : new Date(year, monthIndex, day, 0, 0, 0, 0);
                    const ts = parsedDate.getTime();
                    return Number.isFinite(ts) ? ts : null;
                }

                const isoLikeMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/);
                if (isoLikeMatch) {
                    const year = parseInt(isoLikeMatch[1], 10);
                    const monthIndex = parseInt(isoLikeMatch[2], 10) - 1;
                    const day = parseInt(isoLikeMatch[3], 10);
                    const hour = parseInt(String(isoLikeMatch[4] || '0'), 10);
                    const minute = parseInt(String(isoLikeMatch[5] || '0'), 10);
                    const second = parseInt(String(isoLikeMatch[6] || '0'), 10);
                    const ts = new Date(year, monthIndex, day, hour, minute, second, 0).getTime();
                    return Number.isFinite(ts) ? ts : null;
                }

                const parsed = Date.parse(raw);
                if (!Number.isFinite(parsed)) {
                    return null;
                }
                return parsed;
            }

            function normalizeDateInputString(value) {
                const raw = String(value || '').trim();
                const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
                return match ? match[1] : '';
            }

            function normalizeDateFilterInputsInPlace() {
                if (!dateFilterFromInput || !dateFilterToInput) {
                    return;
                }
                const fromDate = normalizeDateInputString(dateFilterFromInput.value);
                const toDate = normalizeDateInputString(dateFilterToInput.value);
                if (fromDate === '' || toDate === '') {
                    return;
                }
                const fromTimestamp = parseDateInputValue(fromDate, false);
                const toTimestamp = parseDateInputValue(toDate, true);
                if (fromTimestamp === null || toTimestamp === null) {
                    return;
                }
                if (fromTimestamp <= toTimestamp) {
                    return;
                }
                dateFilterFromInput.value = toDate;
                dateFilterToInput.value = fromDate;
            }

            function getActiveDateFilterRange() {
                normalizeDateFilterInputsInPlace();
                return {
                    fromDate: normalizeDateInputString(dateFilterFromInput ? dateFilterFromInput.value : ''),
                    toDate: normalizeDateInputString(dateFilterToInput ? dateFilterToInput.value : ''),
                };
            }

            function appendDateRangeToUrlObject(urlObject, range) {
                if (!(urlObject instanceof URL)) {
                    return;
                }
                const dateRange = range && typeof range === 'object' ? range : getActiveDateFilterRange();
                const fromDate = normalizeDateInputString(dateRange.fromDate || '');
                const toDate = normalizeDateInputString(dateRange.toDate || '');

                if (fromDate !== '') {
                    urlObject.searchParams.set('fromDate', fromDate);
                } else {
                    urlObject.searchParams.delete('fromDate');
                }

                if (toDate !== '') {
                    urlObject.searchParams.set('toDate', toDate);
                } else {
                    urlObject.searchParams.delete('toDate');
                }
            }

            function syncDateFilterQueryState() {
                try {
                    const currentUrl = new URL(window.location.href);
                    appendDateRangeToUrlObject(currentUrl, getActiveDateFilterRange());
                    window.history.replaceState({}, '', currentUrl.toString());
                } catch (error) {
                    // Ignore URL update failures on unsupported contexts.
                }
            }

            function applyDateFilterFromQuery() {
                let parsedUrl = null;
                try {
                    parsedUrl = new URL(window.location.href);
                } catch (error) {
                    parsedUrl = null;
                }
                if (!parsedUrl) {
                    return;
                }

                const fromDate = normalizeDateInputString(parsedUrl.searchParams.get('fromDate') || '');
                const toDate = normalizeDateInputString(parsedUrl.searchParams.get('toDate') || '');
                if (dateFilterFromInput && fromDate !== '') {
                    dateFilterFromInput.value = fromDate;
                }
                if (dateFilterToInput && toDate !== '') {
                    dateFilterToInput.value = toDate;
                }
            }

            function normalizeRangeObject(range) {
                const source = range && typeof range === 'object' ? range : {};
                return {
                    fromDate: normalizeDateInputString(source.fromDate || source.from_input || ''),
                    toDate: normalizeDateInputString(source.toDate || source.to_input || ''),
                };
            }

            function dateRangeEquals(leftRange, rightRange) {
                const left = normalizeRangeObject(leftRange);
                const right = normalizeRangeObject(rightRange);
                return left.fromDate === right.fromDate && left.toDate === right.toDate;
            }

            function dateRangeIsEmpty(range) {
                const normalized = normalizeRangeObject(range);
                return normalized.fromDate === '' && normalized.toDate === '';
            }

            function formatDateRangeCaption(range, fallbackLabel) {
                const normalized = normalizeRangeObject(range);
                if (dateRangeIsEmpty(normalized)) {
                    return String(fallbackLabel || 'All dates');
                }
                if (normalized.fromDate !== '' && normalized.toDate !== '') {
                    return normalized.fromDate + ' to ' + normalized.toDate;
                }
                if (normalized.fromDate !== '') {
                    return 'From ' + normalized.fromDate;
                }
                return 'Until ' + normalized.toDate;
            }

            function normalizeLabelKey(value) {
                return String(value || '').toLowerCase().trim();
            }

            function toLocalDateKey(dateValue) {
                if (!(dateValue instanceof Date)) {
                    return '';
                }
                const year = dateValue.getFullYear();
                const month = String(dateValue.getMonth() + 1).padStart(2, '0');
                const day = String(dateValue.getDate()).padStart(2, '0');
                return year + '-' + month + '-' + day;
            }

            function extractDateKey(rawDateValue) {
                const raw = String(rawDateValue || '').trim();
                if (raw === '') {
                    return '';
                }
                const match = raw.match(/^(\d{4}-\d{2}-\d{2})/);
                if (match) {
                    return match[1];
                }
                const parsedTs = parseDateInputValue(raw, false);
                if (parsedTs === null) {
                    return '';
                }
                return toLocalDateKey(new Date(parsedTs));
            }

            function dateKeyDayDiffFromToday(dateKey) {
                const key = String(dateKey || '').trim();
                const match = key.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                if (!match) {
                    return null;
                }
                const year = parseInt(match[1], 10);
                const monthIndex = parseInt(match[2], 10) - 1;
                const day = parseInt(match[3], 10);
                const target = new Date(year, monthIndex, day, 0, 0, 0, 0);
                const today = new Date();
                const startToday = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0, 0);
                const diffMs = target.getTime() - startToday.getTime();
                if (!Number.isFinite(diffMs)) {
                    return null;
                }
                return Math.round(diffMs / 86400000);
            }

            function ensureOfflineQueuedStatusFilterOption() {
                if (!isIntakePage) {
                    return;
                }
                if (!statusFilterSelect) {
                    return;
                }
                const hasOption = Array.from(statusFilterSelect.options).some(function (option) {
                    return normalizeLabelKey(option.value || '') === offlineQueuedStatusFilterValue;
                });
                if (hasOption) {
                    return;
                }
                const option = document.createElement('option');
                option.value = offlineQueuedStatusFilterValue;
                option.textContent = offlineQueuedStatusFilterLabel;
                statusFilterSelect.appendChild(option);
            }

            function outboxTextEntryValue(entries, key) {
                const targetKey = String(key || '').trim();
                if (targetKey === '') {
                    return '';
                }
                const list = Array.isArray(entries) ? entries : [];
                for (let i = 0; i < list.length; i += 1) {
                    const entry = list[i];
                    if (!entry || String(entry.kind || '') !== 'text') {
                        continue;
                    }
                    if (String(entry.key || '') !== targetKey) {
                        continue;
                    }
                    return String(entry.value == null ? '' : entry.value).trim();
                }
                return '';
            }

            function resolveDocumentTypeLabelForOfflineRow(documentTypeIdRaw, fallbackLabel) {
                const fallback = String(fallbackLabel || '').trim();
                const documentTypeId = String(documentTypeIdRaw || '').trim();
                if (intakeDocumentType && documentTypeId !== '') {
                    const option = Array.from(intakeDocumentType.options).find(function (node) {
                        return String(node.value || '').trim() === documentTypeId;
                    });
                    if (option) {
                        const optionLabel = String(option.textContent || '').trim();
                        if (optionLabel !== '') {
                            return optionLabel;
                        }
                    }
                }
                return fallback !== '' ? fallback : 'Offline Intake';
            }

            function offlineSyncStatusLabel(statusRaw) {
                const status = normalizeLabelKey(statusRaw);
                if (status === 'syncing') {
                    return offlineQueuedStatusPrefix + ' Syncing';
                }
                if (status === 'failed') {
                    return offlineQueuedStatusPrefix + ' Sync Failed';
                }
                if (status === 'blocked_reauth') {
                    return offlineQueuedStatusPrefix + ' Re-login Required';
                }
                return offlineQueuedStatusPrefix + ' Queued';
            }

            function offlineOperationShortLabel(operationId) {
                const raw = String(operationId || '').trim();
                if (raw === '') {
                    return 'LOCAL';
                }
                const compact = raw.replace(/[^a-z0-9]/gi, '').toUpperCase();
                if (compact === '') {
                    return 'LOCAL';
                }
                return compact.slice(0, 8);
            }

            function mapOfflineOperationToQueueRow(operation) {
                const row = operation && typeof operation === 'object' ? operation : null;
                if (!row) {
                    return null;
                }
                if (normalizeLabelKey(row.kind || '') !== 'intake_create') {
                    return null;
                }

                const status = normalizeLabelKey(row.status || '');
                if (status === '' || status === 'synced') {
                    return null;
                }

                const bodyEntries = Array.isArray(row.bodyEntries) ? row.bodyEntries : [];
                const operationId = String(row.operationId || '').trim();
                const createdTs = Number(row.createdAt || 0);
                const createdDate = Number.isFinite(createdTs) && createdTs > 0 ? new Date(createdTs) : new Date();
                const createdIso = createdDate.toISOString();
                const createdLabel = createdDate.toLocaleString();

                const subject = outboxTextEntryValue(bodyEntries, 'subject');
                const sourceType = outboxTextEntryValue(bodyEntries, 'source_type');
                const documentTypeId = outboxTextEntryValue(bodyEntries, 'document_type_id');
                const providedDocumentTypeLabel = outboxTextEntryValue(bodyEntries, 'document_type');
                const complexityType = outboxTextEntryValue(bodyEntries, 'intake_complexity_type');
                const localRef = offlineOperationShortLabel(operationId);
                const normalizedSourceType = normalizeLabelKey(sourceType) === 'external' ? 'External' : 'Internal';
                const currentHolder = status === 'blocked_reauth'
                    ? 'Queued locally: re-authentication required'
                    : 'Queued on this device';

                return {
                    document_id: 0,
                    row_version: 0,
                    tracking_id: '',
                    offline_tracking_label: 'LOCAL-' + localRef,
                    document_type: resolveDocumentTypeLabelForOfflineRow(documentTypeId, providedDocumentTypeLabel),
                    subject: subject !== '' ? subject : '(Offline intake without subject)',
                    status: offlineSyncStatusLabel(status),
                    source_type: normalizedSourceType.toUpperCase(),
                    arta_category: complexityType !== '' ? complexityType : '-',
                    current_holder: currentHolder,
                    date_created: createdLabel,
                    date_received: createdLabel,
                    date_created_raw: createdIso,
                    date_received_raw: createdIso,
                    created_by_user_id: Number.isFinite(currentUserId) ? currentUserId : 0,
                    offline_queued: 1,
                    offline_operation_id: operationId,
                    offline_row_key: operationId !== '' ? ('offline:' + operationId) : ('offline:' + localRef)
                };
            }

            function queueRowIdentityKey(queueRow) {
                const row = queueRow && typeof queueRow === 'object' ? queueRow : {};
                const offlineKey = String(row.offline_row_key || '').trim();
                if (offlineKey !== '') {
                    return offlineKey;
                }
                const documentId = Number(row.document_id || 0);
                if (Number.isFinite(documentId) && documentId > 0) {
                    return 'doc:' + String(documentId);
                }
                const trackingId = String(row.tracking_id || '').trim().toUpperCase();
                if (trackingId !== '') {
                    return 'trk:' + trackingId;
                }
                return '';
            }

            function mergeQueueRowsForRender(baseRows) {
                const liveRows = Array.isArray(baseRows) ? baseRows : [];
                if (!Array.isArray(offlineQueuedQueueRows) || offlineQueuedQueueRows.length === 0) {
                    return liveRows.slice();
                }
                const merged = [];
                const seen = new Set();
                offlineQueuedQueueRows.forEach(function (row) {
                    const key = queueRowIdentityKey(row);
                    if (key !== '' && seen.has(key)) {
                        return;
                    }
                    if (key !== '') {
                        seen.add(key);
                    }
                    merged.push(row);
                });
                liveRows.forEach(function (row) {
                    const key = queueRowIdentityKey(row);
                    if (key !== '' && seen.has(key)) {
                        return;
                    }
                    if (key !== '') {
                        seen.add(key);
                    }
                    merged.push(row);
                });
                return merged;
            }

            function findHeaderIndexByKeywords(headers, keywords) {
                const list = Array.isArray(headers) ? headers : [];
                const needles = Array.isArray(keywords) ? keywords : [];
                for (let i = 0; i < list.length; i += 1) {
                    const headerText = normalizeLabelKey(list[i]);
                    if (headerText === '') {
                        continue;
                    }
                    for (let j = 0; j < needles.length; j += 1) {
                        const needle = normalizeLabelKey(needles[j]);
                        if (needle !== '' && headerText.indexOf(needle) !== -1) {
                            return i;
                        }
                    }
                }
                return -1;
            }

            function tableCellText(cells, index) {
                if (!Array.isArray(cells) || index < 0 || index >= cells.length) {
                    return '';
                }
                return String(cells[index] ? cells[index].textContent : '').trim();
            }

            function captureLiveQueueRowsFromTable() {
                if (!tableElement || !tableBody) {
                    return [];
                }
                const headerTexts = Array.from(tableElement.querySelectorAll('thead th')).map(function (node) {
                    return String(node.textContent || '').trim();
                });
                const rows = getQueueTableRows().filter(function (row) {
                    return !row.querySelector('.table-empty-state');
                });
                if (rows.length === 0) {
                    return [];
                }

                const trackingIndex = findHeaderIndexByKeywords(headerTexts, ['tracking']);
                const subjectIndex = findHeaderIndexByKeywords(headerTexts, ['subject', 'reason']);
                const documentTypeIndex = findHeaderIndexByKeywords(headerTexts, ['document type']);
                const statusIndex = findHeaderIndexByKeywords(headerTexts, ['status', 'action required']);
                const sourceIndex = findHeaderIndexByKeywords(headerTexts, ['source']);
                const currentHolderIndex = findHeaderIndexByKeywords(headerTexts, ['current holder', 'requested by', 'to']);
                const originIndex = findHeaderIndexByKeywords(headerTexts, ['origin', 'from']);
                const createdDateIndex = findHeaderIndexByKeywords(headerTexts, ['date created', 'created at', 'created date']);
                const receivedDateIndex = findHeaderIndexByKeywords(headerTexts, ['date received', 'last update', 'last action']);
                const artaIndex = findHeaderIndexByKeywords(headerTexts, ['arta']);

                return rows.map(function (row) {
                    const cells = Array.from(row.querySelectorAll('td'));
                    const trackingId = String(row.getAttribute('data-tracking-id') || '').trim() || tableCellText(cells, trackingIndex);
                    const createdRaw = String(row.getAttribute('data-date-created') || '').trim();
                    const receivedRaw = String(row.getAttribute('data-date-received') || '').trim();
                    const deadlineRaw = String(row.getAttribute('data-deadline-at') || '').trim();
                    const deadlineBucket = String(row.getAttribute('data-deadline-bucket') || '').trim();
                    const createdText = tableCellText(cells, createdDateIndex);
                    const receivedText = tableCellText(cells, receivedDateIndex);
                    const statusRaw = String(row.getAttribute('data-status') || '').trim() || tableCellText(cells, statusIndex);
                    return {
                        document_id: Number(row.getAttribute('data-document-id') || 0),
                        row_version: Number(row.getAttribute('data-document-version') || 0),
                        tracking_id: trackingId,
                        source_type: tableCellText(cells, sourceIndex),
                        subject: tableCellText(cells, subjectIndex),
                        document_type: tableCellText(cells, documentTypeIndex),
                        arta_category: tableCellText(cells, artaIndex),
                        origin_office: tableCellText(cells, originIndex),
                        current_holder: String(row.getAttribute('data-current-holder') || '').trim() || tableCellText(cells, currentHolderIndex),
                        status: statusRaw,
                        date_created_raw: createdRaw,
                        date_received_raw: receivedRaw,
                        deadline_at_raw: deadlineRaw,
                        deadline_bucket: deadlineBucket,
                        date_created: createdText !== '' ? createdText : createdRaw,
                        date_received: receivedText !== '' ? receivedText : receivedRaw,
                        has_section_receive: Number(row.getAttribute('data-has-section-receive') || 0),
                        has_signed_action: Number(row.getAttribute('data-has-signed-action') || 0),
                        created_by_user_id: Number(row.getAttribute('data-created-by-user-id') || 0),
                        origin_office_id: Number(row.getAttribute('data-origin-office-id') || 0),
                        current_office_id: Number(row.getAttribute('data-current-office-id') || 0),
                        pending_office_id: Number(row.getAttribute('data-pending-office-id') || 0),
                        current_office_level: String(row.getAttribute('data-current-office-level') || '').trim(),
                        pending_office_level: String(row.getAttribute('data-pending-office-level') || '').trim(),
                    };
                });
            }

            async function refreshOfflineQueuedRowsInView() {
                if (!isIntakePage) {
                    return;
                }
                ensureOfflineQueuedStatusFilterOption();
                if (latestLiveQueueRows.length === 0) {
                    latestLiveQueueRows = captureLiveQueueRowsFromTable();
                }
                if (!window.DTMISOfflineOutbox || typeof window.DTMISOfflineOutbox.listOperations !== 'function') {
                    const shouldRerender = offlineQueuedQueueRows.length > 0 || offlineQueuedRowsSignature !== '';
                    offlineQueuedQueueRows = [];
                    offlineQueuedRowsSignature = '';
                    if (shouldRerender) {
                        renderLiveQueueTable(latestLiveQueueRows);
                    }
                    return;
                }

                const requestId = ++offlineQueueRefreshRequestId;
                try {
                    const operations = await window.DTMISOfflineOutbox.listOperations({ includeSynced: false });
                    if (requestId !== offlineQueueRefreshRequestId) {
                        return;
                    }
                    const mappedRows = (Array.isArray(operations) ? operations : [])
                        .map(mapOfflineOperationToQueueRow)
                        .filter(function (row) { return !!row; });
                    const nextSignature = mappedRows.map(function (row) {
                        return [
                            String(row.offline_operation_id || ''),
                            normalizeLabelKey(row.status || ''),
                            String(row.subject || ''),
                            String(row.date_created_raw || '')
                        ].join('::');
                    }).join('|');
                    if (nextSignature === offlineQueuedRowsSignature) {
                        return;
                    }
                    offlineQueuedQueueRows = mappedRows;
                    offlineQueuedRowsSignature = nextSignature;
                } catch (error) {
                    if (requestId !== offlineQueueRefreshRequestId) {
                        return;
                    }
                    if (offlineQueuedQueueRows.length === 0 && offlineQueuedRowsSignature === '') {
                        return;
                    }
                    offlineQueuedQueueRows = [];
                    offlineQueuedRowsSignature = '';
                }
                renderLiveQueueTable(latestLiveQueueRows);
            }

            function matchesStatusCategoryFilter(filterValue, statusRawValue, createdAtRawValue, deadlineAtRawValue, deadlineBucketRawValue, pendingOfficeId, rowCurrentOfficeId, hasSignedActionRawValue, isOfflineQueuedRow, createdByUserIdRaw, actorActionTypeRawValue) {
                const normalizedFilter = normalizeLabelKey(filterValue);
                if (normalizedFilter === '') {
                    return true;
                }

                const status = normalizeLabelKey(statusRawValue);
                const deadlineBucket = normalizeLabelKey(deadlineBucketRawValue);
                const deadlineDateKey = extractDateKey(deadlineAtRawValue);
                const deadlineDayDiff = dateKeyDayDiffFromToday(deadlineDateKey);
                const actorActionType = normalizeLabelKey(actorActionTypeRawValue);
                const offlineQueued = !!isOfflineQueuedRow;
                if (normalizedFilter === offlineQueuedStatusFilterValue) {
                    return offlineQueued;
                }
                const pendingId = Number(pendingOfficeId || 0);
                const currentRowOfficeId = Number(rowCurrentOfficeId || 0);
                const hasSignedAction = Number(hasSignedActionRawValue || 0) > 0;
                const rowCreatedByUserId = Number(createdByUserIdRaw || 0);
                const statusHasReceived = status.indexOf('received') !== -1 || status.indexOf('recieved') !== -1;
                const statusHasApproved = queueStatusIsApproved(status);
                const statusHasPending = status.indexOf('pending') !== -1 || status.indexOf('hold') !== -1;
                const statusHasForward = status.indexOf('forward') !== -1 || status.indexOf('routed') !== -1 || status.indexOf('route') !== -1;
                const statusHasDueSoonKeyword = status.indexOf('due soon') !== -1
                    || status.indexOf('at risk') !== -1
                    || status.indexOf('at-risk') !== -1;
                const statusHasDueSoon = deadlineDayDiff === null
                    ? statusHasDueSoonKeyword
                    : deadlineDayDiff === 1;
                const statusHasOverdue = deadlineDayDiff === null
                    ? (status.indexOf('overdue') !== -1 || status.indexOf('violation') !== -1)
                    : deadlineDayDiff < 0;
                const statusHasAwaitingReceive = status.indexOf('assigned') !== -1
                    || status.indexOf('awaiting receive') !== -1
                    || status.indexOf('for receive') !== -1
                    || status.indexOf('in transit') !== -1
                    || status.indexOf('routed') !== -1;
                const statusHasCorrection = status.indexOf('return') !== -1
                    || status.indexOf('correction') !== -1
                    || status.indexOf('comply') !== -1
                    || status.indexOf('compliance') !== -1
                    || status.indexOf('revise') !== -1
                    || status.indexOf('resubmit') !== -1;
                const statusHasReleased = status.indexOf('released') !== -1 || status.indexOf('release') !== -1;
                const statusHasSignedCompleted = (status.indexOf('signed') !== -1 && status.indexOf('assigned') === -1)
                    || status.indexOf('completed') !== -1
                    || status.indexOf('done') !== -1;
                const statusIsPendingForward = statusHasApproved
                    && !statusHasForward
                    && !statusHasReleased
                    && !statusHasSignedCompleted;
                const actionHasCreated = actorActionType.indexOf('create') !== -1
                    || actorActionType.indexOf('new') !== -1
                    || actorActionType.indexOf('encoded') !== -1
                    || actorActionType.indexOf('submit') !== -1;
                const actionHasReceived = actorActionType.indexOf('receive') !== -1 || actorActionType.indexOf('recieve') !== -1;
                const actionHasForwarded = actorActionType.indexOf('forward') !== -1
                    || actorActionType.indexOf('route') !== -1
                    || actorActionType.indexOf('routed') !== -1
                    || actorActionType.indexOf('dispatch') !== -1;
                const actionHasApproved = actorActionType.indexOf('approve') !== -1 || actorActionType.indexOf('approval') !== -1;
                const actionHasSigned = (actorActionType.indexOf('sign') !== -1 && actorActionType.indexOf('assign') === -1)
                    || actorActionType.indexOf('signature') !== -1;
                const actionHasReleased = actorActionType.indexOf('release') !== -1;
                const statusIsTerminalForActor = statusHasReleased || statusHasSignedCompleted;
                const rowAllowsReceive = queueRowAllowsReceive(status, pendingId, currentRowOfficeId);
                const rowHasReceivedState = queueRowHasReceivedState(status, pendingId, currentRowOfficeId);
                const statusIsAwaitingReceive = !rowHasReceivedState
                    && (status === '' || statusHasAwaitingReceive || statusHasPending || statusHasForward || rowAllowsReceive);
                const statusIsDraftLike = status === ''
                    || status === 'created'
                    || status.indexOf('draft') !== -1
                    || status.indexOf('encoded') !== -1
                    || status.indexOf('submitted') !== -1;
                const statusRoutedOut = queueStatusIsForwarded(status) || isRoutedOutRowForCurrentOffice(pendingId, currentRowOfficeId);
                const isMyIntakeDraft = Number.isFinite(rowCreatedByUserId)
                    && rowCreatedByUserId > 0
                    && Number.isFinite(currentUserId)
                    && currentUserId > 0
                    && rowCreatedByUserId === currentUserId
                    && !queueStatusIsTerminal(status)
                    && !statusRoutedOut
                    && !(Number.isFinite(pendingId) && pendingId > 0)
                    && statusIsDraftLike;

                if (normalizedFilter === 'actor_created') {
                    return actionHasCreated;
                }
                if (normalizedFilter === 'actor_received') {
                    return actionHasReceived;
                }
                if (normalizedFilter === 'actor_forwarded') {
                    return actionHasForwarded;
                }
                if (normalizedFilter === 'actor_approved') {
                    return actionHasApproved;
                }
                if (normalizedFilter === 'actor_signed') {
                    return actionHasSigned;
                }
                if (normalizedFilter === 'actor_released') {
                    return actionHasReleased;
                }
                if (normalizedFilter === 'actor_in_progress') {
                    if (offlineQueued) {
                        return true;
                    }
                    return !statusIsTerminalForActor;
                }
                if (normalizedFilter === 'actor_actioned') {
                    return true;
                }
                if (normalizedFilter === 'received') {
                    return statusHasReceived;
                }
                if (normalizedFilter === 'approved') {
                    return statusHasApproved;
                }
                if (normalizedFilter === 'sign') {
                    return statusHasSignedCompleted;
                }
                if (normalizedFilter === 'forward') {
                    return statusHasForward;
                }
                if (normalizedFilter === 'pending_forward') {
                    return statusIsPendingForward;
                }
                if (normalizedFilter === 'pending') {
                    if (offlineQueued) {
                        return true;
                    }
                    return statusHasPending
                        || rowAllowsReceive
                        || statusIsAwaitingReceive
                        || (statusHasForward && !statusHasReleased && !statusHasSignedCompleted);
                }
                if (normalizedFilter === 'due_soon') {
                    if (deadlineBucket !== '') {
                        return deadlineBucket === 'due_soon';
                    }
                    return statusHasDueSoon;
                }
                if (normalizedFilter === 'overdue') {
                    if (deadlineBucket !== '') {
                        return deadlineBucket === 'overdue';
                    }
                    return statusHasOverdue;
                }
                if (normalizedFilter === 'completed') {
                    return statusHasSignedCompleted || statusHasReleased;
                }
                if (normalizedFilter === 'ongoing_docs') {
                    if (offlineQueued) {
                        return true;
                    }
                    return !(statusHasSignedCompleted || statusHasReleased || statusHasApproved);
                }
                if (normalizedFilter === 'awaiting_receive') {
                    return rowAllowsReceive || statusIsAwaitingReceive;
                }
                if (normalizedFilter === 'in_transit') {
                    return !rowHasReceivedState && (statusHasAwaitingReceive || statusHasForward);
                }
                if (normalizedFilter === 'on_hold') {
                    return statusHasPending;
                }
                if (normalizedFilter === 'for_correction') {
                    return statusHasCorrection || (!statusHasReleased && !statusHasSignedCompleted && (statusHasPending || statusHasAwaitingReceive));
                }
                if (normalizedFilter === 'released') {
                    return statusHasReleased;
                }
                if (normalizedFilter === 'pending_released') {
                    return rowHasReceivedState && hasSignedAction && !statusHasReleased && !statusHasForward;
                }
                if (normalizedFilter === 'signed_completed') {
                    return statusHasSignedCompleted;
                }
                if (normalizedFilter === 'new_created') {
                    if (offlineQueued) {
                        const createdKey = extractDateKey(createdAtRawValue);
                        const todayKey = toLocalDateKey(new Date());
                        return createdKey === todayKey;
                    }
                    const createdKey = extractDateKey(createdAtRawValue);
                    const todayKey = toLocalDateKey(new Date());
                    if (createdKey !== '') {
                        return createdKey === todayKey;
                    }
                    return status.indexOf('created') !== -1
                        || status.indexOf('new') !== -1
                        || status.indexOf('encoded') !== -1
                        || status.indexOf('submitted') !== -1;
                }
                if (normalizedFilter === 'intake_drafts') {
                    return isMyIntakeDraft;
                }

                return true;
            }

            function buildQueueCounters(queueRows) {
                const counters = {
                    pending_total: 0,
                    intake_draft_total: 0,
                    pending_receive_total: 0,
                    pending_approval_total: 0,
                    pending_sign_total: 0,
                    pending_release_total: 0,
                    pending_forward_total: 0,
                    completed_total: 0,
                };
                const safeRows = Array.isArray(queueRows) ? queueRows : [];

                safeRows.forEach(function (queueRow) {
                    const status = String(queueRow && queueRow.status ? queueRow.status : '').trim();
                    const normalizedStatus = normalizeLabelKey(status);
                    const rowCurrentOfficeId = Number(queueRow && queueRow.current_office_id ? queueRow.current_office_id : 0);
                    const pendingOfficeId = Number(queueRow && queueRow.pending_office_id ? queueRow.pending_office_id : 0);
                    const isManagedByCurrentOffice = rowIsManagedByCurrentOffice(pendingOfficeId, rowCurrentOfficeId);
                    const canReceiveRow = queueRowAllowsReceive(normalizedStatus, pendingOfficeId, rowCurrentOfficeId);
                    const isTerminal = queueStatusIsTerminal(normalizedStatus) && !canReceiveRow;
                    const isReceived = queueRowHasReceivedState(normalizedStatus, pendingOfficeId, rowCurrentOfficeId);
                    const isApproved = queueStatusIsApproved(normalizedStatus);
                    const isSignedCompleted = queueStatusIsSignedCompleted(normalizedStatus);
                    const hasSignedAction = Number(queueRow && queueRow.has_signed_action ? queueRow.has_signed_action : 0) > 0;
                    const rowCreatedByUserId = Number(queueRow && queueRow.created_by_user_id ? queueRow.created_by_user_id : 0);
                    const isReleased = normalizedStatus.indexOf('released') !== -1 || normalizedStatus.indexOf('release') !== -1;
                    const isAwaitingSignature = !isSignedCompleted && (
                        normalizedStatus.indexOf('signature') !== -1
                        || normalizedStatus.indexOf('pending sign') !== -1
                        || normalizedStatus.indexOf('for sign') !== -1
                        || normalizedStatus.indexOf('approved') !== -1
                    );
                    const isForwarded = queueStatusIsForwarded(normalizedStatus) || isRoutedOutRowForCurrentOffice(pendingOfficeId, rowCurrentOfficeId);

                    if (isTerminal) {
                        counters.completed_total += 1;
                        return;
                    }

                    const hasPendingOffice = Number.isFinite(pendingOfficeId) && pendingOfficeId > 0;
                    const isDraftLikeStatus = normalizedStatus === ''
                        || normalizedStatus === 'created'
                        || normalizedStatus.indexOf('draft') !== -1
                        || normalizedStatus.indexOf('encoded') !== -1
                        || normalizedStatus.indexOf('submitted') !== -1;
                    const belongsToCurrentUser = Number.isFinite(rowCreatedByUserId)
                        && rowCreatedByUserId > 0
                        && Number.isFinite(currentUserId)
                        && currentUserId > 0
                        && rowCreatedByUserId === currentUserId;
                    const isIntakeDraft = belongsToCurrentUser && !isForwarded && !hasPendingOffice && isDraftLikeStatus;
                    if (isIntakeDraft) {
                        counters.intake_draft_total += 1;
                    }

                    if (canReceiveRow) {
                        counters.pending_receive_total += 1;
                    }

                    if (isManagedByCurrentOffice && !isIntakeDraft && isReceived && !isApproved && !hasSignedAction && !isForwarded) {
                        counters.pending_approval_total += 1;
                    }

                    if (isManagedByCurrentOffice && isAwaitingSignature && !isForwarded) {
                        counters.pending_sign_total += 1;
                    }

                    if (isManagedByCurrentOffice && isReceived && hasSignedAction && !isReleased && !isForwarded) {
                        counters.pending_release_total += 1;
                    }

                    if (isManagedByCurrentOffice && isApproved && !isForwarded) {
                        counters.pending_forward_total += 1;
                    }

                    counters.pending_total += 1;
                });

                return counters;
            }

            function buildActorActionCounters(queueRows) {
                const counters = {
                    actor_created_total: 0,
                    actor_received_total: 0,
                    actor_forwarded_total: 0,
                    actor_approved_total: 0,
                    actor_signed_total: 0,
                    actor_released_total: 0,
                    actor_in_progress_total: 0,
                    actor_actioned_total: 0,
                };
                const safeRows = Array.isArray(queueRows) ? queueRows : [];
                counters.actor_actioned_total = safeRows.length;

                safeRows.forEach(function (queueRow) {
                    const actionType = normalizeLabelKey(queueRow && queueRow.actor_action_type ? queueRow.actor_action_type : '');
                    const status = normalizeLabelKey(queueRow && queueRow.status ? queueRow.status : '');
                    const actionHasCreated = actionType.indexOf('create') !== -1
                        || actionType.indexOf('new') !== -1
                        || actionType.indexOf('encoded') !== -1
                        || actionType.indexOf('submit') !== -1;
                    const actionHasReceived = actionType.indexOf('receive') !== -1 || actionType.indexOf('recieve') !== -1;
                    const actionHasForwarded = actionType.indexOf('forward') !== -1
                        || actionType.indexOf('route') !== -1
                        || actionType.indexOf('routed') !== -1
                        || actionType.indexOf('dispatch') !== -1;
                    const actionHasApproved = actionType.indexOf('approve') !== -1 || actionType.indexOf('approval') !== -1;
                    const actionHasSigned = (actionType.indexOf('sign') !== -1 && actionType.indexOf('assign') === -1)
                        || actionType.indexOf('signature') !== -1;
                    const actionHasReleased = actionType.indexOf('release') !== -1;
                    const statusHasReleased = status.indexOf('released') !== -1 || status.indexOf('release') !== -1;
                    const statusHasSignedCompleted = (status.indexOf('signed') !== -1 && status.indexOf('assigned') === -1)
                        || status.indexOf('completed') !== -1
                        || status.indexOf('done') !== -1;

                    if (actionHasCreated) {
                        counters.actor_created_total += 1;
                    }
                    if (actionHasReceived) {
                        counters.actor_received_total += 1;
                    }
                    if (actionHasForwarded) {
                        counters.actor_forwarded_total += 1;
                    }
                    if (actionHasApproved) {
                        counters.actor_approved_total += 1;
                    }
                    if (actionHasSigned) {
                        counters.actor_signed_total += 1;
                    }
                    if (actionHasReleased) {
                        counters.actor_released_total += 1;
                    }
                    if (!(statusHasReleased || statusHasSignedCompleted)) {
                        counters.actor_in_progress_total += 1;
                    }
                });

                return counters;
            }

            function liveMetricValueForLabel(label, metrics, activityCounters, returnedTotal, queueCounters, actorActionCounters) {
                const labelKey = normalizeLabelKey(label);
                if (labelKey === '') {
                    return null;
                }
                const counters = queueCounters && typeof queueCounters === 'object' ? queueCounters : {};
                const actorCounters = actorActionCounters && typeof actorActionCounters === 'object' ? actorActionCounters : {};
                if (labelKey.indexOf('created by me') !== -1) {
                    return Number(actorCounters.actor_created_total || 0);
                }
                if (labelKey.indexOf('received by me') !== -1) {
                    return Number(actorCounters.actor_received_total || 0);
                }
                if (labelKey.indexOf('forwarded by me') !== -1) {
                    return Number(actorCounters.actor_forwarded_total || 0);
                }
                if (labelKey.indexOf('approved by me') !== -1) {
                    return Number(actorCounters.actor_approved_total || 0);
                }
                if (labelKey.indexOf('signed by me') !== -1) {
                    return Number(actorCounters.actor_signed_total || 0);
                }
                if (labelKey.indexOf('released by me') !== -1) {
                    return Number(actorCounters.actor_released_total || 0);
                }
                if (labelKey.indexOf('still in progress') !== -1) {
                    return Number(actorCounters.actor_in_progress_total || 0);
                }
                if (labelKey.indexOf('total actioned') !== -1) {
                    return Number(actorCounters.actor_actioned_total || 0);
                }
                if (labelKey.indexOf('encoded today') !== -1 || labelKey.indexOf('created today') !== -1) {
                    return Number(metrics.created_today || 0);
                }
                if (labelKey.indexOf('external intake') !== -1) {
                    return Number(metrics.external_today || 0);
                }
                if (labelKey.indexOf('for clearance') !== -1) {
                    return Number(metrics.for_clearance_total || 0);
                }
                if (labelKey.indexOf('for endorsement') !== -1) {
                    return Number(metrics.for_endorsement_total || 0);
                }
                if (labelKey.indexOf('for signature') !== -1 || /\brd\b/.test(labelKey) || labelKey.indexOf('my signature') !== -1) {
                    return Number(metrics.for_signature_total || 0);
                }
                if (labelKey.indexOf('site validation') !== -1 || labelKey.indexOf('validation') !== -1) {
                    return Number(metrics.for_validation_total || 0);
                }
                if (labelKey.indexOf('completed this week') !== -1) {
                    return Number(metrics.completed_week || 0);
                }
                if (labelKey.indexOf('completed today') !== -1) {
                    return Number(metrics.completed_today || 0);
                }
                if (labelKey.indexOf('ongoing docs') !== -1 || labelKey.indexOf('ongoing') !== -1) {
                    if (Object.prototype.hasOwnProperty.call(counters, 'pending_total')) {
                        return Number(counters.pending_total || 0);
                    }
                    return Number(metrics.pending_total || 0);
                }
                if (labelKey.indexOf('intake draft') !== -1) {
                    if (Object.prototype.hasOwnProperty.call(counters, 'intake_draft_total')) {
                        return Number(counters.intake_draft_total || 0);
                    }
                    return Number(metrics.created_today || 0);
                }
                if (labelKey === 'completed' || labelKey.indexOf('completed total') !== -1) {
                    return Number(metrics.completed_total || 0);
                }
                if (labelKey.indexOf('pending released') !== -1) {
                    const metricPendingRelease = Number(metrics.pending_release_total || 0);
                    const metricPendingForward = Number(metrics.pending_forward_total || 0);
                    if (Object.prototype.hasOwnProperty.call(counters, 'pending_release_total')) {
                        return Math.max(Number(counters.pending_release_total || 0), metricPendingRelease, metricPendingForward);
                    }
                    if (Object.prototype.hasOwnProperty.call(counters, 'pending_forward_total')) {
                        return Math.max(Number(counters.pending_forward_total || 0), metricPendingForward);
                    }
                    return Math.max(metricPendingRelease, metricPendingForward);
                }
                if (labelKey.indexOf('released') !== -1) {
                    return Number(metrics.released_total || 0);
                }
                if (labelKey.indexOf('due soon / overdue') !== -1) {
                    return Number(metrics.due_soon_total || 0) + Number(metrics.overdue_total || 0);
                }
                if (labelKey.indexOf('due today / due soon') !== -1 || labelKey.indexOf('due soon / due today') !== -1) {
                    return Number(metrics.due_today_total || 0) + Number(metrics.due_soon_total || 0);
                }
                if (labelKey.indexOf('due soon') !== -1) {
                    return Number(metrics.due_soon_total || 0);
                }
                if (labelKey.indexOf('overdue') !== -1 || labelKey.indexOf('at-risk') !== -1 || labelKey.indexOf('at risk') !== -1 || labelKey.indexOf('violations') !== -1) {
                    return Number(metrics.overdue_total || 0);
                }
                if (labelKey.indexOf('pending approval') !== -1) {
                    if (Object.prototype.hasOwnProperty.call(counters, 'pending_approval_total')) {
                        return Number(counters.pending_approval_total || 0);
                    }
                    return Number(metrics.pending_approval_total || 0);
                }
                if (labelKey.indexOf('pending sign') !== -1) {
                    const metricPendingSign = Number(metrics.pending_sign_total || 0);
                    if (Object.prototype.hasOwnProperty.call(counters, 'pending_sign_total')) {
                        return Math.max(Number(counters.pending_sign_total || 0), metricPendingSign);
                    }
                    return metricPendingSign;
                }
                if (labelKey.indexOf('pending forward') !== -1) {
                    const metricPendingForward = Number(metrics.pending_forward_total || 0);
                    if (Object.prototype.hasOwnProperty.call(counters, 'pending_forward_total')) {
                        return Math.max(Number(counters.pending_forward_total || 0), metricPendingForward);
                    }
                    return metricPendingForward;
                }
                if (
                    labelKey.indexOf('pending receive') !== -1
                    || labelKey.indexOf('pending received') !== -1
                    || labelKey.indexOf('pending received') !== -1
                ) {
                    if (Object.prototype.hasOwnProperty.call(counters, 'pending_receive_total')) {
                        return Number(counters.pending_receive_total || 0);
                    }
                    return Number(metrics.pending_total || 0);
                }
                if (labelKey.indexOf('pending') !== -1) {
                    if (Object.prototype.hasOwnProperty.call(counters, 'pending_total')) {
                        return Number(counters.pending_total || 0);
                    }
                    return Number(metrics.pending_total || 0);
                }
                if (labelKey.indexOf('returned') !== -1) {
                    return Number(returnedTotal || 0);
                }
                if (labelKey === 'received' || labelKey.indexOf('received events') !== -1) {
                    return Number(activityCounters.received_events || 0);
                }
                if (labelKey === 'approved' || labelKey.indexOf('approved events') !== -1) {
                    return Number(activityCounters.approved_events || 0);
                }
                if (labelKey === 'forwarded' || labelKey.indexOf('forwarded events') !== -1) {
                    return Number(activityCounters.forwarded_events || 0);
                }
                if (labelKey.indexOf('reroute events') !== -1) {
                    return Number(activityCounters.reroute_events || 0);
                }
                return null;
            }

            function updateLiveCardsAndPanels(payload) {
                if (!payload || typeof payload !== 'object') {
                    return;
                }
                const metrics = payload.metrics && typeof payload.metrics === 'object' ? payload.metrics : {};
                const activityCounters = payload.activity_counters && typeof payload.activity_counters === 'object' ? payload.activity_counters : {};
                const returnedTotal = preferScopedQueueCountersForLiveStats
                    ? Number(payload.returned_total || 0)
                    : Number(metrics.returned_total || payload.returned_total || 0);
                const payloadQueueRows = Array.isArray(payload.queue_rows) ? payload.queue_rows : [];
                const queueCounters = buildQueueCounters(payloadQueueRows);
                const queueCountersForDisplay = preferScopedQueueCountersForLiveStats ? queueCounters : {};
                const actorActionCounters = buildActorActionCounters(payloadQueueRows);

                const cards = Array.from(document.querySelectorAll('.stat-card'));
                cards.forEach(function (card) {
                    const disableStatCardLive = String(card.getAttribute('data-disable-stat-card-live') || '').trim() === '1';
                    if (disableStatCardLive) {
                        return;
                    }

                    const labelNode = card.querySelector('.stat-label');
                    const valueNode = card.querySelector('.stat-value');
                    if (!labelNode || !valueNode) {
                        return;
                    }
                    const mappedValue = liveMetricValueForLabel(labelNode.textContent || '', metrics, activityCounters, returnedTotal, queueCountersForDisplay, actorActionCounters);
                    if (mappedValue === null) {
                        return;
                    }
                    valueNode.textContent = String(mappedValue);
                });

                const progressRows = Array.from(document.querySelectorAll('.progress-row'));
                let maxValue = 1;
                const staged = [];
                progressRows.forEach(function (row) {
                    const labelNode = row.querySelector('.row-meta span:first-child');
                    const valueNode = row.querySelector('.row-meta span:last-child');
                    const barNode = row.querySelector('.bar > span');
                    if (!labelNode || !valueNode || !barNode) {
                        return;
                    }
                    const mappedValue = liveMetricValueForLabel(labelNode.textContent || '', metrics, activityCounters, returnedTotal, queueCountersForDisplay, actorActionCounters);
                    if (mappedValue === null) {
                        return;
                    }
                    const numeric = Math.max(0, Number(mappedValue || 0));
                    staged.push({ valueNode: valueNode, barNode: barNode, numeric: numeric });
                    maxValue = Math.max(maxValue, numeric);
                });

                staged.forEach(function (item) {
                    item.valueNode.textContent = String(item.numeric);
                    item.barNode.style.width = String(Math.round((item.numeric / maxValue) * 100)) + '%';
                });
            }

            function findChartPanel(chartKind) {
                return chartPanels.find(function (panel) {
                    return String(panel.getAttribute('data-chart-kind') || '').trim() === String(chartKind || '').trim();
                }) || null;
            }

            function getChartPanelFilterElements(chartPanel) {
                if (!chartPanel) {
                    return null;
                }
                const filterWrap = chartPanel.querySelector('.chart-date-filter');
                if (!filterWrap) {
                    return null;
                }
                return {
                    panel: chartPanel,
                    kind: String(chartPanel.getAttribute('data-chart-kind') || '').trim(),
                    wrap: filterWrap,
                    toggle: filterWrap.querySelector('.chart-date-filter-toggle'),
                    dropdown: filterWrap.querySelector('.chart-date-filter-dropdown'),
                    fromInput: filterWrap.querySelector('input[name="fromDate"]'),
                    toInput: filterWrap.querySelector('input[name="toDate"]'),
                    applyButton: filterWrap.querySelector('[data-chart-date-apply]'),
                    resetButton: filterWrap.querySelector('[data-chart-date-reset]'),
                    caption: chartPanel.querySelector('[data-chart-filter-caption]'),
                };
            }

            function setChartDateDropdownOpen(elements, isOpen) {
                if (!elements || !elements.dropdown || !elements.toggle) {
                    return;
                }
                elements.dropdown.classList.toggle('is-open', !!isOpen);
                elements.toggle.setAttribute('aria-expanded', String(!!isOpen));
            }

            function setChartPanelBusy(chartKind, isBusy) {
                const chartPanel = findChartPanel(chartKind);
                if (!chartPanel) {
                    return;
                }
                chartPanel.classList.toggle('is-chart-loading', !!isBusy);
            }

            function syncChartFilterCaption(chartKind) {
                const chartPanel = findChartPanel(chartKind);
                const elements = getChartPanelFilterElements(chartPanel);
                if (!elements || !elements.caption) {
                    return;
                }
                const pageRange = getActiveDateFilterRange();
                const overrideState = chartOverrideStateByKind[chartKind] && chartOverrideStateByKind[chartKind].active
                    ? normalizeRangeObject(chartOverrideStateByKind[chartKind].range)
                    : null;
                if (!overrideState || dateRangeEquals(overrideState, pageRange)) {
                    elements.caption.textContent = formatDateRangeCaption(pageRange, 'Page range');
                    return;
                }
                elements.caption.textContent = formatDateRangeCaption(overrideState, 'Custom range');
            }

            function syncChartFilterInputsFromRange(chartKind, range) {
                const chartPanel = findChartPanel(chartKind);
                const elements = getChartPanelFilterElements(chartPanel);
                if (!elements) {
                    return;
                }
                const normalized = normalizeRangeObject(range);
                if (elements.fromInput) {
                    elements.fromInput.value = normalized.fromDate;
                }
                if (elements.toInput) {
                    elements.toInput.value = normalized.toDate;
                }
            }

            function getChartPanelSelectedRange(chartKind) {
                const chartPanel = findChartPanel(chartKind);
                const elements = getChartPanelFilterElements(chartPanel);
                if (!elements) {
                    return getActiveDateFilterRange();
                }
                const range = {
                    fromDate: normalizeDateInputString(elements.fromInput ? elements.fromInput.value : ''),
                    toDate: normalizeDateInputString(elements.toInput ? elements.toInput.value : ''),
                };
                if (range.fromDate !== '' && range.toDate !== '') {
                    const fromTimestamp = parseDateInputValue(range.fromDate, false);
                    const toTimestamp = parseDateInputValue(range.toDate, true);
                    if (fromTimestamp !== null && toTimestamp !== null && fromTimestamp > toTimestamp) {
                        const swapFrom = range.toDate;
                        const swapTo = range.fromDate;
                        range.fromDate = swapFrom;
                        range.toDate = swapTo;
                        if (elements.fromInput) {
                            elements.fromInput.value = swapFrom;
                        }
                        if (elements.toInput) {
                            elements.toInput.value = swapTo;
                        }
                    }
                }
                return range;
            }

            function buildChartDashboardRequestUrl(range) {
                const fallbackJoiner = String(dashboardLivePath || '').indexOf('?') !== -1 ? '&' : '?';
                const normalizedRange = normalizeRangeObject(range);
                try {
                    const requestUrl = new URL(String(dashboardLivePath || ''), window.location.origin);
                    appendDateRangeToUrlObject(requestUrl, normalizedRange);
                    requestUrl.searchParams.set('limit', String(getDashboardLiveLimit()));
                    requestUrl.searchParams.set('t', String(Date.now()));
                    return requestUrl.toString();
                } catch (error) {
                    let requestUrl = String(dashboardLivePath || '') + fallbackJoiner + 'limit=' + encodeURIComponent(String(getDashboardLiveLimit())) + '&t=' + String(Date.now());
                    if (normalizedRange.fromDate) {
                        requestUrl += '&fromDate=' + encodeURIComponent(normalizedRange.fromDate);
                    }
                    if (normalizedRange.toDate) {
                        requestUrl += '&toDate=' + encodeURIComponent(normalizedRange.toDate);
                    }
                    return requestUrl;
                }
            }

            function updateSingleChartFromPayload(chartKind, payload) {
                if (!payload || typeof payload !== 'object') {
                    return;
                }
                if (chartKind === 'arta_mix') {
                    updateArtaPieChart(payload.arta_distribution);
                    return;
                }
                if (chartKind === 'queue_status') {
                    updateQueueStatusBarChart(payload.metrics);
                    return;
                }
                if (chartKind === 'workflow_trend') {
                    updateWorkflowTrendChart(payload.workflow_trend);
                }
            }

            function fetchChartPayloadForRange(chartKind, range) {
                const requestSerial = (Number(chartRequestSerialByKind[chartKind] || 0) + 1);
                chartRequestSerialByKind[chartKind] = requestSerial;
                const normalizedRange = normalizeRangeObject(range);
                setChartPanelBusy(chartKind, true);
                return fetchWithLocalhostFallback(buildChartDashboardRequestUrl(normalizedRange), {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json' },
                }).then(function (networkResult) {
                    const response = networkResult && networkResult.response ? networkResult.response : null;
                    if (!response || !response.ok) {
                        throw new Error('Unable to load chart data right now.');
                    }
                    return response.json();
                }).then(function (payload) {
                    if (!payload || payload.ok !== true) {
                        throw new Error(payload && payload.message ? String(payload.message) : 'Unable to load chart data right now.');
                    }
                    if (requestSerial !== Number(chartRequestSerialByKind[chartKind] || 0)) {
                        return payload;
                    }
                    updateSingleChartFromPayload(chartKind, payload);
                    return payload;
                }).finally(function () {
                    setChartPanelBusy(chartKind, false);
                });
            }

            function applyChartDateRange(chartKind, range, options) {
                const normalizedRange = normalizeRangeObject(range);
                const pageRange = getActiveDateFilterRange();
                const usePageRange = dateRangeEquals(normalizedRange, pageRange);
                const chartPanel = findChartPanel(chartKind);
                const elements = getChartPanelFilterElements(chartPanel);
                const config = options && typeof options === 'object' ? options : {};

                if (!usePageRange) {
                    chartOverrideStateByKind[chartKind] = {
                        active: true,
                        range: normalizedRange,
                    };
                } else {
                    delete chartOverrideStateByKind[chartKind];
                }

                syncChartFilterCaption(chartKind);
                if (elements) {
                    setChartDateDropdownOpen(elements, false);
                }

                if (usePageRange && latestLiveDashboardPayload && !config.forceFetch) {
                    updateSingleChartFromPayload(chartKind, latestLiveDashboardPayload);
                    return Promise.resolve(latestLiveDashboardPayload);
                }

                return fetchChartPayloadForRange(chartKind, usePageRange ? pageRange : normalizedRange).catch(function (error) {
                    if (elements) {
                        setChartDateDropdownOpen(elements, false);
                    }
                    const fallbackMessage = error && error.message ? String(error.message) : 'Unable to load chart data right now.';
                    showAlertDialog(fallbackMessage);
                    return null;
                });
            }

            function refreshChartOverrides() {
                const overrideKinds = Object.keys(chartOverrideStateByKind).filter(function (chartKind) {
                    return chartOverrideStateByKind[chartKind] && chartOverrideStateByKind[chartKind].active;
                });
                overrideKinds.forEach(function (chartKind) {
                    fetchChartPayloadForRange(chartKind, chartOverrideStateByKind[chartKind].range).catch(function () {
                        // Keep the current chart view if the refresh fails.
                    });
                });
            }

            function syncChartControlsToPageRange() {
                const pageRange = getActiveDateFilterRange();
                chartPanels.forEach(function (chartPanel) {
                    const chartKind = String(chartPanel.getAttribute('data-chart-kind') || '').trim();
                    if (chartKind === '') {
                        return;
                    }
                    if (chartOverrideStateByKind[chartKind] && chartOverrideStateByKind[chartKind].active) {
                        if (dateRangeEquals(chartOverrideStateByKind[chartKind].range, pageRange)) {
                            delete chartOverrideStateByKind[chartKind];
                        } else {
                            syncChartFilterCaption(chartKind);
                            return;
                        }
                    }
                    if (chartOverrideStateByKind[chartKind] && chartOverrideStateByKind[chartKind].active) {
                        syncChartFilterCaption(chartKind);
                        return;
                    }
                    syncChartFilterInputsFromRange(chartKind, pageRange);
                    syncChartFilterCaption(chartKind);
                });
            }

            function updateArtaPieChart(distributionPayload) {
                const chartPanel = document.querySelector('.chart-panel[data-chart-kind="arta_mix"]');
                if (!chartPanel) {
                    return;
                }

                const distribution = distributionPayload && typeof distributionPayload === 'object' ? distributionPayload : {};
                const nextSeries = [
                    { label: 'Simple', value: Math.max(0, Number(distribution.simple || 0)), color: '#f5bf3a' },
                    { label: 'Complex', value: Math.max(0, Number(distribution.complex || 0)), color: '#e4649c' },
                    { label: 'Highly Technical', value: Math.max(0, Number(distribution.highly_technical || 0)), color: '#6f63e8' },
                    { label: 'Uncategorized', value: Math.max(0, Number(distribution.uncategorized || 0)), color: '#8fa5bc' },
                ];
                const nextTotal = nextSeries.reduce(function (sum, item) {
                    return sum + Math.max(0, Number(item.value || 0));
                }, 0);

                chartPieSeries = nextSeries;
                chartPieTotal = nextTotal;

                const pieWrap = chartPanel.querySelector('.chart-pie-wrap');
                if (!pieWrap) {
                    return;
                }

                let donut = pieWrap.querySelector('.chart-donut');
                let emptyState = pieWrap.querySelector('.chart-empty');
                const legendList = pieWrap.querySelector('.chart-legend-list');

                if (nextTotal > 0) {
                    let angleStart = 0;
                    const gradientStops = [];
                    nextSeries.forEach(function (item) {
                        const value = Math.max(0, Number(item.value || 0));
                        if (value <= 0) {
                            return;
                        }
                        const slice = (value / nextTotal) * 360;
                        const angleEnd = angleStart + slice;
                        gradientStops.push(String(item.color || '#8fa5bc') + ' ' + String(angleStart.toFixed(2)) + 'deg ' + String(angleEnd.toFixed(2)) + 'deg');
                        angleStart = angleEnd;
                    });

                    if (!donut) {
                        donut = document.createElement('div');
                        donut.className = 'chart-donut';
                        donut.innerHTML = '<div class="chart-donut-hole"><strong>0</strong><span>Docs</span></div>';
                        pieWrap.insertBefore(donut, legendList || pieWrap.firstChild);
                    }
                    donut.style.background = 'conic-gradient(' + gradientStops.join(', ') + ')';
                    const totalNode = donut.querySelector('.chart-donut-hole strong');
                    if (totalNode) {
                        totalNode.textContent = String(nextTotal);
                    }
                    if (emptyState && emptyState.parentNode) {
                        emptyState.parentNode.removeChild(emptyState);
                    }
                } else {
                    if (donut && donut.parentNode) {
                        donut.parentNode.removeChild(donut);
                    }
                    donut = null;
                    if (!emptyState) {
                        emptyState = document.createElement('div');
                        emptyState.className = 'chart-empty';
                        emptyState.textContent = 'No ARTA data yet.';
                        pieWrap.insertBefore(emptyState, legendList || pieWrap.firstChild);
                    }
                }

                if (legendList) {
                    legendList.innerHTML = nextSeries.map(function (item) {
                        return '<div class="chart-legend-item">'
                            + '<span class="legend-dot" style="background: ' + String(item.color || '#8fa5bc') + ';"></span>'
                            + '<span>' + String(item.label || '') + '</span>'
                            + '<strong>' + String(Math.max(0, Number(item.value || 0))) + '</strong>'
                            + '</div>';
                    }).join('');
                }
            }

            function updateQueueStatusBarChart(metricsPayload) {
                const chartPanel = document.querySelector('.chart-panel[data-chart-kind="queue_status"]');
                if (!chartPanel) {
                    return;
                }

                const metrics = metricsPayload && typeof metricsPayload === 'object' ? metricsPayload : {};
                const dueSoonCombined = Math.max(0, Number(metrics.due_today_total || 0)) + Math.max(0, Number(metrics.due_soon_total || 0));
                const series = [
                    { label: 'Pending', value: Math.max(0, Number(metrics.pending_total || 0)), color: '#2f7de1' },
                    { label: 'Due Soon', value: dueSoonCombined, color: '#23a68f' },
                    { label: 'Overdue', value: Math.max(0, Number(metrics.overdue_total || 0)), color: '#d34747' },
                    { label: 'Completed', value: Math.max(0, Number(metrics.completed_week || 0)), color: '#1a9b5f' },
                ];
                const maxValue = Math.max(1, ...series.map(function (item) {
                    return Math.max(0, Number(item.value || 0));
                }));
                const barWrap = chartPanel.querySelector('.chart-bar-wrap');
                if (!barWrap) {
                    return;
                }

                barWrap.innerHTML = series.map(function (item) {
                    const height = Math.round((Math.max(0, Number(item.value || 0)) / maxValue) * 100);
                    return '<div class="chart-bar-col">'
                        + '<div class="chart-bar-rail"><span class="chart-bar-fill" style="--bar-val: ' + String(height) + '%; background: ' + String(item.color || '#2f7de1') + ';"></span></div>'
                        + '<strong>' + String(Math.max(0, Number(item.value || 0))) + '</strong>'
                        + '<span>' + String(item.label || '') + '</span>'
                        + '</div>';
                }).join('');
            }

            function updateWorkflowTrendChart(trendPayload) {
                const chartPanel = document.querySelector('.chart-panel[data-chart-kind="workflow_trend"]');
                if (!chartPanel) {
                    return;
                }

                const payload = trendPayload && typeof trendPayload === 'object' ? trendPayload : {};
                const labels = Array.isArray(payload.labels) ? payload.labels.slice() : [];
                const nextTrendData = {
                    labels: labels,
                    series: [
                        {
                            key: 'received',
                            label: 'Received',
                            color: '#2f7de1',
                            values: Array.isArray(payload.received) ? payload.received.slice() : [],
                        },
                        {
                            key: 'forwarded',
                            label: 'Forwarded / Routed',
                            color: '#1ea48a',
                            values: Array.isArray(payload.forwarded) ? payload.forwarded.slice() : [],
                        },
                        {
                            key: 'completed',
                            label: 'Approved / Signed',
                            color: '#7b5ad9',
                            values: Array.isArray(payload.completed) ? payload.completed.slice() : [],
                        },
                    ],
                };

                chartTrendData = nextTrendData;

                const axisRow = chartPanel.querySelector('.chart-axis-row');
                if (axisRow) {
                    axisRow.style.gridTemplateColumns = 'repeat(' + String(Math.max(labels.length, 1)) + ', minmax(0, 1fr))';
                    axisRow.innerHTML = labels.map(function (label) {
                        return '<span>' + String(label || '') + '</span>';
                    }).join('');
                }

                const splineWrap = chartPanel.querySelector('.chart-spline-wrap');
                if (splineWrap) {
                    let hasPoints = false;
                    nextTrendData.series.forEach(function (series) {
                        if (!Array.isArray(series.values)) {
                            return;
                        }
                        series.values.forEach(function (value) {
                            if (Math.max(0, Number(value || 0)) > 0) {
                                hasPoints = true;
                            }
                        });
                    });

                    let emptyState = splineWrap.querySelector('.chart-empty-overlay');
                    if (hasPoints) {
                        if (emptyState && emptyState.parentNode) {
                            emptyState.parentNode.removeChild(emptyState);
                        }
                    } else if (!emptyState) {
                        emptyState = document.createElement('div');
                        emptyState.className = 'chart-empty chart-empty-overlay';
                        emptyState.textContent = 'No trend data yet.';
                        splineWrap.appendChild(emptyState);
                    }
                }

                renderWorkflowSplineChart();
            }

            function updateLiveCharts(payload) {
                if (!payload || typeof payload !== 'object') {
                    return;
                }

                latestLiveDashboardPayload = payload;

                if (!(chartOverrideStateByKind.arta_mix && chartOverrideStateByKind.arta_mix.active)) {
                    updateArtaPieChart(payload.arta_distribution);
                }
                if (!(chartOverrideStateByKind.queue_status && chartOverrideStateByKind.queue_status.active)) {
                    updateQueueStatusBarChart(payload.metrics);
                }
                if (!(chartOverrideStateByKind.workflow_trend && chartOverrideStateByKind.workflow_trend.active)) {
                    updateWorkflowTrendChart(payload.workflow_trend);
                }
            }

            function queueRowCanManageIntake(queueRow) {
                if (!isIntakePage) {
                    return false;
                }

                const status = normalizeLabelKey(queueRow && queueRow.status ? queueRow.status : '');
                if (queueStatusIsTerminal(status)) {
                    return false;
                }

                const pendingOfficeId = Number(queueRow && queueRow.pending_office_id ? queueRow.pending_office_id : 0);
                const rowCurrentOfficeId = Number(queueRow && queueRow.current_office_id ? queueRow.current_office_id : 0);
                const rowIsInMyCustody = rowIsManagedByCurrentOffice(pendingOfficeId, rowCurrentOfficeId);
                if (
                    Number.isFinite(pendingOfficeId)
                    && pendingOfficeId > 0
                    && !rowIsInMyCustody
                ) {
                    return false;
                }
                const hasReceivedCustody = queueRowHasReceivedState(status, pendingOfficeId, rowCurrentOfficeId);
                if (queueStatusIsForwarded(status)) {
                    return false;
                }

                const createdByUserId = Number(queueRow && queueRow.created_by_user_id ? queueRow.created_by_user_id : 0);
                const isCreatorDraft = Number.isFinite(createdByUserId)
                    && createdByUserId > 0
                    && createdByUserId === currentUserId
                    && status === 'created';
                if (isCreatorDraft) {
                    return true;
                }

                const hasReturnAction = Number(queueRow && queueRow.has_return_action ? queueRow.has_return_action : 0) > 0;
                const isCorrectionCycle = hasReturnAction
                    && rowIsInMyCustody
                    && hasReceivedCustody
                    && (
                        status.indexOf('received') !== -1
                        || status.indexOf('return') !== -1
                        || status.indexOf('approved') !== -1
                    );

                return (isCenroAdminRecordRole || isPenroAdminRecordRole || currentRoleKey === 'RECORDS_UNIT') && isCorrectionCycle;
            }

            function finalizeQueueActionText(actions, queueRow) {
                const list = Array.isArray(actions) ? actions.slice() : [];
                if (queueRowCanManageIntake(queueRow)) {
                    list.push('Edit');
                    list.push('Delete');
                }
                const unique = list.filter(function (value, index, array) {
                    return array.indexOf(value) === index;
                });
                if (unique.length === 0) {
                    return '-';
                }
                return unique.join(' | ');
            }

            function queueDefaultActionText(queueRow) {
                if (Number(queueRow && queueRow.offline_queued ? queueRow.offline_queued : 0) > 0) {
                    return 'Awaiting Sync';
                }
                if (!workflowActionScopeActive) {
                    return finalizeQueueActionText(
                        hideQueueDocumentOnlyActions ? [] : ['View Tracking Slip', 'Print Package'],
                        queueRow
                    );
                }
                if (queueRowCanManageIntake(queueRow)) {
                    return finalizeQueueActionText(['Forward'], queueRow);
                }
                const status = normalizeLabelKey(queueRow.status || '');
                const rowCurrentOfficeId = Number(queueRow.current_office_id || 0);
                const pendingOfficeId = Number(queueRow.pending_office_id || 0);
                const rowIsInMyCustody = rowIsManagedByCurrentOffice(pendingOfficeId, rowCurrentOfficeId);
                if (!rowIsInMyCustody) {
                    const routedOutFromCurrentOffice = isRoutedOutRowForCurrentOffice(pendingOfficeId, rowCurrentOfficeId);
                    const isPacdoReleasedAwaitingOriginReceive = currentRoleKey === 'RECORDS_UNIT'
                        && !isCenroAdminRecordRole
                        && status.indexOf('released') !== -1
                        && queueStatusIsTerminal(status)
                        && routedOutFromCurrentOffice;
                    if (isPacdoReleasedAwaitingOriginReceive) {
                        const pacdoReleasedActions = hideQueueDocumentOnlyActions
                            ? ['Release']
                            : ['View Tracking Slip', 'Release', 'Print Package'];
                        return finalizeQueueActionText(pacdoReleasedActions, queueRow);
                    }
                    if (currentRoleKey === 'ORED' && !isCenroOfficerRole && (queueStatusIsForwarded(status) || routedOutFromCurrentOffice)) {
                        const routedOutActions = hideQueueDocumentOnlyActions ? [] : ['View Tracking Slip'];
                        if (enableQueueRerouteAction) {
                            routedOutActions.push('Reroute');
                        }
                        routedOutActions.push('Override');
                        if (!hideQueueDocumentOnlyActions) {
                            routedOutActions.push('Print Package');
                        }
                        return finalizeQueueActionText(
                            routedOutActions,
                            queueRow
                        );
                    }
                    if ((currentRoleKey === 'DIVISION_CHIEF' || isArdRole) && enableQueueRerouteAction && (queueStatusIsForwarded(status) || routedOutFromCurrentOffice)) {
                        return finalizeQueueActionText(
                            hideQueueDocumentOnlyActions ? ['Reroute'] : ['View Tracking Slip', 'Reroute', 'Print Package'],
                            queueRow
                        );
                    }
                    if ((isCenroOfficerRole || isCenroSectionRole) && enableQueueRerouteAction && (queueStatusIsForwarded(status) || routedOutFromCurrentOffice)) {
                        return finalizeQueueActionText(
                            hideQueueDocumentOnlyActions ? ['Reroute'] : ['View Tracking Slip', 'Reroute', 'Print Package'],
                            queueRow
                        );
                    }
                    return finalizeQueueActionText(
                        hideQueueDocumentOnlyActions ? [] : ['View Tracking Slip', 'Print Package'],
                        queueRow
                    );
                }
                const hasSignedAction = Number(queueRow.has_signed_action || 0) > 0;
                const isReceived = queueRowHasReceivedState(status, pendingOfficeId, rowCurrentOfficeId);
                const isApproved = queueStatusIsApproved(status) || (approveActionDisabled && isReceived);
                const isSignedCompleted = queueStatusIsSignedCompleted(status);
                const isSignedForPhase = isSignedCompleted || hasSignedAction;
                const isForwarded = queueStatusIsForwarded(status);
                const isTerminal = queueStatusIsTerminal(status);
                const actions = hideQueueDocumentOnlyActions ? [] : ['View Tracking Slip'];
                if (currentRoleKey === 'RECORDS_UNIT') {
                    if (isCenroAdminRecordRole || isPenroAdminRecordRole || isPamoAdminRole) {
                        if (!isReceived && queueRowAllowsReceive(status, pendingOfficeId, rowCurrentOfficeId)) {
                            actions.push('Receive');
                        } else if (isSignedForPhase) {
                            actions.push('Release');
                        } else {
                            actions.push('Forward');
                            actions.push('Release');
                        }
                        if (!hideQueueDocumentOnlyActions) {
                            actions.push('Print Package');
                        }
                        return finalizeQueueActionText(actions, queueRow);
                    }
                    if (!isReceived && queueRowAllowsReceive(status, pendingOfficeId, rowCurrentOfficeId)) {
                        actions.push('Receive');
                    } else if (hasSignedAction) {
                        actions.push('Release');
                    } else {
                        actions.push('Forward');
                        actions.push('Return');
                    }
                    if (!hideQueueDocumentOnlyActions) {
                        actions.push('Print Package');
                    }
                    return finalizeQueueActionText(actions, queueRow);
                }
                if (currentRoleKey === 'ORED' || isPenroOfficerRole) {
                    if (!isReceived) {
                        if (queueRowAllowsReceive(status, pendingOfficeId, rowCurrentOfficeId)) {
                            actions.push('Receive');
                        }
                    } else if (isSignedForPhase && !isForwarded) {
                        // After signing, ORED can still undo sign before forwarding.
                        if (!regionalOredDigitalSignDisabled) {
                            actions.push('Undo Sign');
                        }
                        actions.push('Forward');
                        if (isRegionalOredReviewer) {
                            actions.push('Send Back to RECORDS-UNIT');
                        }
                        actions.push('Return');
                    } else if (isSignedForPhase && isForwarded) {
                        if (!isCenroOfficerRole && !regionalOredDigitalSignDisabled) {
                            actions.push('Override');
                        }
                    } else if (!isForwarded) {
                        if (!officerDigitalSignDisabled && !regionalOredDigitalSignDisabled) {
                            actions.push('Sign');
                        }
                        actions.push('Forward');
                        if (isCenroOfficerRole || isPenroOfficerRole || isPamoOfficerRole) {
                            actions.push('Send Back to Admin Record');
                        }
                        if (isCenroOfficerRole && enableQueueRerouteAction) {
                            actions.push('Reroute');
                        }
                        actions.push('Return');
                    } else {
                        if (!isCenroOfficerRole && !regionalOredDigitalSignDisabled) {
                            actions.push('Override');
                        }
                    }
                    if (!hideQueueDocumentOnlyActions) {
                        actions.push('Print Package');
                    }
                    return finalizeQueueActionText(actions, queueRow);
                }
                if (currentRoleKey === 'DIVISION_CHIEF') {
                    if (!isReceived) {
                        if (queueRowAllowsReceive(status, pendingOfficeId, rowCurrentOfficeId)) {
                            actions.push('Receive');
                        }
                    } else {
                        actions.push('Forward');
                        if (isCenroSectionRole && enableQueueRerouteAction) {
                            actions.push('Reroute');
                        }
                        if (isCenroSectionRole) {
                            actions.push('Send Back to CENRO Officer');
                        } else if (isPenroDivisionRole || isPenroSectionRole) {
                            actions.push('Send Back to PENRO Officer');
                        } else {
                            actions.push('Send Back to ARD');
                        }
                    }
                    if (!hideQueueDocumentOnlyActions) {
                        actions.push('Print Package');
                    }
                    return finalizeQueueActionText(actions, queueRow);
                }
                if (isArdRole) {
                    if (!isReceived) {
                        if (queueRowAllowsReceive(status, pendingOfficeId, rowCurrentOfficeId)) {
                            actions.push('Receive');
                        }
                    } else {
                        actions.push('Forward');
                        actions.push('Send Back to ORED');
                    }
                    if (!hideQueueDocumentOnlyActions) {
                        actions.push('Print Package');
                    }
                    return finalizeQueueActionText(actions, queueRow);
                }
                if (currentRoleKey === 'SECTION_STAFF') {
                    if (!isReceived) {
                        if (queueRowAllowsReceive(status, pendingOfficeId, rowCurrentOfficeId)) {
                            actions.push('Receive');
                        }
                    } else {
                        actions.push('Forward');
                    }
                    if (!hideQueueDocumentOnlyActions) {
                        actions.push('Print Package');
                    }
                    return finalizeQueueActionText(actions, queueRow);
                }
                if (queueRowAllowsReceive(status, pendingOfficeId, rowCurrentOfficeId)) {
                    actions.push('Receive');
                }
                if (currentRoleKey === 'ORED' && !regionalOredDigitalSignDisabled) {
                    actions.push('Sign');
                }
                if (!isCenroAdminRecordRole || isReceived) {
                    actions.push('Forward');
                }
                if ((isCenroOfficerRole || isPamoOfficerRole) && isReceived && !isTerminal && !isForwarded) {
                    actions.push('Send Back to Admin Record');
                }
                if (isPenroAdminRecordRole && isReceived && !isTerminal && !isForwarded) {
                    actions.push('Return');
                } else if (status.indexOf('return') !== -1) {
                    actions.push('Return');
                }
                if (currentRoleKey === 'RECORDS_UNIT') {
                    actions.push('Release');
                }
                if (currentRoleKey === 'ORED' && !isCenroOfficerRole && !regionalOredDigitalSignDisabled) {
                    actions.push('Override');
                }
                if (!hideRerouteQuickAction) {
                    actions.push('Reroute');
                }
                if (!hideQueueDocumentOnlyActions) {
                    actions.push('Print Package');
                }
                return finalizeQueueActionText(actions, queueRow);
            }

            function queueActorActionLabel(queueRow) {
                const actionType = String(queueRow.actor_action_type || '').trim();
                const actionRemarks = String(queueRow.actor_action_remarks || '').trim();
                if (actionType === '') {
                    return '-';
                }
                if (actionRemarks === '' || normalizeLabelKey(actionRemarks) === normalizeLabelKey(actionType)) {
                    return actionType;
                }
                const normalizedRemarks = actionRemarks.replace(/\s+/g, ' ').trim();
                if (normalizedRemarks === '') {
                    return actionType;
                }
                const shortRemarks = normalizedRemarks.length > 64
                    ? (normalizedRemarks.slice(0, 61) + '...')
                    : normalizedRemarks;
                return actionType + ' | ' + shortRemarks;
            }

            function queueIntakeIndicatorLabel(queueRow) {
                const status = normalizeLabelKey(queueRow.status || '');
                const complexity = normalizeLabelKey(queueRow.arta_category || '');
                const subject = normalizeLabelKey(queueRow.subject || '');
                const remarks = normalizeLabelKey(queueRow.last_remarks || queueRow.actor_action_remarks || '');
                const deadlineBucket = normalizeLabelKey(queueRow.deadline_bucket || '');

                if (remarks.indexOf('color classification:') !== -1) {
                    if (remarks.indexOf('yellow') !== -1 || remarks.indexOf('urgent') !== -1) {
                        return 'Yellow - Urgent';
                    }
                    if (remarks.indexOf('pink') !== -1 || remarks.indexOf('simple') !== -1) {
                        return 'Pink - Simple';
                    }
                    if (remarks.indexOf('blue') !== -1 || remarks.indexOf('complex') !== -1 || remarks.indexOf('highly technical') !== -1) {
                        return 'Blue - Complex/Highly Technical';
                    }
                    if (remarks.indexOf('green') !== -1 || remarks.indexOf('released') !== -1) {
                        return 'Green - Released';
                    }
                }

                if (status.indexOf('released') !== -1) {
                    return 'Green - Released';
                }

                if (
                    status.indexOf('urgent') !== -1
                    || subject.indexOf('urgent') !== -1
                    || remarks.indexOf('urgent') !== -1
                    || deadlineBucket.indexOf('overdue') !== -1
                    || deadlineBucket.indexOf('at-risk') !== -1
                    || deadlineBucket.indexOf('at risk') !== -1
                ) {
                    return 'Yellow - Urgent';
                }

                if (
                    complexity.indexOf('complex') !== -1
                    || complexity.indexOf('highly technical') !== -1
                    || complexity.indexOf('highly_technical') !== -1
                    || complexity.indexOf('highly-technical') !== -1
                ) {
                    return 'Blue - Complex/Highly Technical';
                }

                if (complexity.indexOf('simple') !== -1) {
                    return 'Pink - Simple';
                }

                return 'Pink - Simple';
            }

            function queueCellValueForColumn(column, queueRow) {
                const columnKey = normalizeLabelKey(column);
                if (columnKey === 'indicator' || columnKey.indexOf('indicator') !== -1) {
                    return queueIntakeIndicatorLabel(queueRow);
                }
                if (columnKey.indexOf('tracking') !== -1) {
                    const offlineTrackingLabel = String(queueRow && queueRow.offline_tracking_label ? queueRow.offline_tracking_label : '').trim();
                    if (offlineTrackingLabel !== '') {
                        return offlineTrackingLabel;
                    }
                    return String(queueRow.tracking_id || '-');
                }
                if (columnKey.indexOf('source') !== -1) {
                    const sourceType = String(queueRow.source_type || 'INTERNAL').toLowerCase();
                    return sourceType.charAt(0).toUpperCase() + sourceType.slice(1);
                }
                if (columnKey.indexOf('subject') !== -1 && columnKey.indexOf('complexity') !== -1) {
                    const subject = String(queueRow.subject || '').trim();
                    const complexity = String(queueRow.arta_category || '').trim();
                    if (subject === '' && (complexity === '' || complexity === '-')) {
                        return '-';
                    }
                    if (subject === '') {
                        return complexity;
                    }
                    if (complexity === '' || complexity === '-') {
                        return subject;
                    }
                    return subject + ' (' + complexity + ')';
                }
                if (columnKey.indexOf('subject') !== -1) {
                    return String(queueRow.subject || '-');
                }
                if (columnKey.indexOf('document type') !== -1) {
                    return String(queueRow.document_type || '-');
                }
                if (columnKey === 'arta' || columnKey.indexOf('arta') !== -1) {
                    return String(queueRow.arta_category || '-');
                }
                if (columnKey.indexOf('from') !== -1 || columnKey.indexOf('origin') !== -1) {
                    return String(queueRow.origin_office || '-');
                }
                if (columnKey.indexOf('reroute to') !== -1 || columnKey.indexOf('reoute to') !== -1) {
                    const rerouteTo = String(queueRow.reroute_to || '').trim();
                    if (rerouteTo !== '') {
                        return rerouteTo;
                    }
                    return String(queueRow.current_holder || '-');
                }
                if (columnKey === 'to' || columnKey.indexOf('to ') === 0 || columnKey.indexOf('current holder') !== -1) {
                    return String(queueRow.current_holder || '-');
                }
                if (columnKey.indexOf('remarks') !== -1) {
                    const lastRemarks = String(queueRow.last_remarks || queueRow.actor_action_remarks || '').trim();
                    return lastRemarks !== '' ? lastRemarks : '-';
                }
                if (columnKey.indexOf('reason') !== -1) {
                    return String(queueRow.subject || '-');
                }
                if (columnKey.indexOf('requested by') !== -1 || columnKey.indexOf('requested') !== -1) {
                    return String(queueRow.current_holder || '-');
                }
                if (
                    columnKey.indexOf('last action by me') !== -1
                    || columnKey.indexOf('my action') !== -1
                ) {
                    return queueActorActionLabel(queueRow);
                }
                if (
                    columnKey.indexOf('last action time') !== -1
                    || columnKey === 'last action'
                    || columnKey.indexOf('last update') !== -1
                ) {
                    return String(queueRow.date_received || '-');
                }
                if (columnKey.indexOf('sender') !== -1) {
                    return String(queueRow.sender || '-');
                }
                if (columnKey.indexOf('date created') !== -1 || columnKey.indexOf('created date') !== -1 || columnKey.indexOf('created at') !== -1) {
                    return String(queueRow.date_created || '-');
                }
                if (columnKey.indexOf('date') !== -1) {
                    return String(queueRow.date_received || '-');
                }
                if (columnKey.indexOf('time remaining') !== -1) {
                    return String(queueRow.time_remaining || '-');
                }
                if (columnKey.indexOf('status') !== -1) {
                    return String(queueRow.status || '-');
                }
                if (columnKey.indexOf('quick action') !== -1 || columnKey === 'action' || columnKey === 'actions') {
                    return queueDefaultActionText(queueRow);
                }
                return '-';
            }

            function queueOfflineDocumentPrefetch(url) {
                const requestUrl = String(url || '').trim();
                if (
                    requestUrl === ''
                    || !showCompletedQueueRowsOnly
                    || !navigator.onLine
                    || typeof window.fetch !== 'function'
                    || offlinePrefetchedDocumentUrls.has(requestUrl)
                ) {
                    return;
                }

                offlinePrefetchedDocumentUrls.add(requestUrl);
                window.fetch(requestUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'text/html',
                        'X-DTMIS-Prefetch': '1',
                    },
                }).catch(function () {
                    offlinePrefetchedDocumentUrls.delete(requestUrl);
                });
            }

            function archiveTrackingDocumentUrls(trackingId) {
                const normalizedTrackingId = String(trackingId || '').trim();
                if (normalizedTrackingId === '') {
                    return [];
                }
                const encodedTrackingId = encodeURIComponent(normalizedTrackingId);
                return [
                    trackingSlipPath + '?tracking_id=' + encodedTrackingId,
                    trackingSlipPath + '?tracking_id=' + encodedTrackingId + '&autoprint=1',
                    printPackagePath + '?tracking_id=' + encodedTrackingId + '&autoprint=1',
                ];
            }

            function prefetchArchiveTrackingDocuments(queueRows) {
                if (!showCompletedQueueRowsOnly || !Array.isArray(queueRows) || queueRows.length === 0) {
                    return;
                }

                queueRows.slice(0, offlineArchivePrefetchLimit).forEach(function (queueRow) {
                    const trackingId = String(queueRow && queueRow.tracking_id ? queueRow.tracking_id : '').trim();
                    if (trackingId === '') {
                        return;
                    }

                    archiveTrackingDocumentUrls(trackingId).forEach(queueOfflineDocumentPrefetch);
                });
            }

            function prefetchArchiveTrackingDocumentsFromTable() {
                if (!showCompletedQueueRowsOnly || !tableBody) {
                    return;
                }

                const queueRows = Array.from(tableBody.querySelectorAll('tr[data-tracking-id]'))
                    .slice(0, offlineArchivePrefetchLimit)
                    .map(function (row) {
                        return {
                            tracking_id: String(row.getAttribute('data-tracking-id') || '').trim(),
                        };
                    })
                    .filter(function (row) {
                        return row.tracking_id !== '';
                    });

                prefetchArchiveTrackingDocuments(queueRows);
            }

            function scheduleArchivePrefetchFromTable() {
                if (!showCompletedQueueRowsOnly) {
                    return;
                }
                if (offlineArchivePrefetchTimerId) {
                    window.clearTimeout(offlineArchivePrefetchTimerId);
                }
                offlineArchivePrefetchTimerId = window.setTimeout(function () {
                    offlineArchivePrefetchTimerId = 0;
                    prefetchArchiveTrackingDocumentsFromTable();
                }, 180);
            }

            async function offlineArchiveTrackingReady(trackingId) {
                if (
                    !showCompletedQueueRowsOnly
                    || String(trackingId || '').trim() === ''
                    || typeof window.caches === 'undefined'
                    || typeof window.caches.match !== 'function'
                ) {
                    return false;
                }

                const urls = archiveTrackingDocumentUrls(trackingId);
                if (urls.length === 0) {
                    return false;
                }

                for (let index = 0; index < urls.length; index += 1) {
                    const match = await window.caches.match(urls[index]);
                    if (!match) {
                        return false;
                    }
                }

                return true;
            }

            function applyArchiveOfflineReadyUi(row, isReady) {
                if (!row) {
                    return;
                }

                row.setAttribute('data-offline-ready', isReady ? '1' : '0');
                row.classList.toggle('queue-row-offline-ready', !!isReady);

                const firstCell = row.querySelector('td');
                if (!firstCell) {
                    return;
                }

                let chip = firstCell.querySelector('.offline-ready-chip');
                if (isReady) {
                    if (!chip) {
                        chip = document.createElement('span');
                        chip.className = 'offline-ready-chip';
                        chip.textContent = 'Offline Ready';
                        firstCell.appendChild(chip);
                    }
                } else if (chip) {
                    chip.remove();
                }
            }

            async function refreshArchiveOfflineAvailabilityIndicators() {
                if (!showCompletedQueueRowsOnly || !tableBody) {
                    return;
                }

                const requestId = ++offlineArchiveAvailabilityRequestId;
                const rows = Array.from(tableBody.querySelectorAll('tr[data-tracking-id]'))
                    .filter(function (row) {
                        return row.style.display !== 'none';
                    })
                    .slice(0, offlineArchivePrefetchLimit);

                for (let index = 0; index < rows.length; index += 1) {
                    const row = rows[index];
                    const trackingId = String(row.getAttribute('data-tracking-id') || '').trim();
                    const isReady = await offlineArchiveTrackingReady(trackingId);
                    if (requestId !== offlineArchiveAvailabilityRequestId) {
                        return;
                    }
                    applyArchiveOfflineReadyUi(row, isReady);
                }

                if (requestId === offlineArchiveAvailabilityRequestId) {
                    syncSelectedDocumentToolsState(false);
                }
            }

            function renderLiveQueueTable(queueRows) {
                if (!tableElement || !tableBody) {
                    return;
                }
                const headerNodes = Array.from(tableElement.querySelectorAll('thead th'));
                const headers = headerNodes.map(function (th) {
                    return String(th.textContent || '').trim();
                });
                const isQueueTable = headers.some(function (header) {
                    return normalizeLabelKey(header).indexOf('tracking') !== -1;
                });
                if (!isQueueTable) {
                    return;
                }
                const safeRows = Array.isArray(queueRows) ? queueRows.slice() : [];
                latestLiveQueueRows = safeRows.slice();
                const rowsForRender = mergeQueueRowsForRender(safeRows);
                tableBody.innerHTML = '';

                if (rowsForRender.length === 0) {
                    const tr = document.createElement('tr');
                    const td = document.createElement('td');
                    td.className = 'table-empty-state';
                    td.colSpan = Math.max(headers.length, 1);
                    td.textContent = 'No documents found for this queue.';
                    tr.appendChild(td);
                    tableBody.appendChild(tr);
                    selectedQueueTrackingId = '';
                    syncSelectedDocumentToolsState(true);
                    return;
                }

                const fragment = document.createDocumentFragment();
                rowsForRender.forEach(function (queueRow) {
                    const tr = document.createElement('tr');
                    const documentId = Number(queueRow.document_id || 0);
                    const documentVersion = Number(queueRow.row_version || 0);
                    const trackingId = String(queueRow.tracking_id || '').trim();
                    const offlineRowKey = String(queueRow.offline_row_key || '').trim();
                    const offlineOperationId = String(queueRow.offline_operation_id || '').trim();
                    const isOfflineQueued = Number(queueRow.offline_queued || 0) > 0;
                    const dateReceivedRaw = String(queueRow.date_received_raw || '').trim();
                    const dateCreatedRaw = String(queueRow.date_created_raw || '').trim();
                    const deadlineAtRaw = String(queueRow.deadline_at_raw || '').trim();
                    const deadlineBucketRaw = String(queueRow.deadline_bucket || '').trim();
                    const statusRaw = String(queueRow.status || '').trim();
                    const hasSectionReceive = Number(queueRow.has_section_receive || 0) > 0;
                    const hasSignedAction = Number(queueRow.has_signed_action || 0) > 0
                        || queueStatusIsSignedCompleted(String(queueRow.status || ''));
                    const hasReturnAction = Number(queueRow.has_return_action || 0) > 0;
                    const createdByUserId = Number(queueRow.created_by_user_id || 0);
                    const originOfficeId = Number(queueRow.origin_office_id || queueRow.originating_office_id || 0);
                    const currentOfficeIdRaw = Number(queueRow.current_office_id || 0);
                    const pendingOfficeId = Number(queueRow.pending_office_id || 0);
                    const currentOfficeLevel = String(queueRow.current_office_level || '').trim().toUpperCase();
                    const pendingOfficeLevel = String(queueRow.pending_office_level || '').trim().toUpperCase();
                    const currentHolderLabel = String(queueRow.current_holder || '').trim();
                    const actorActionTypeRaw = String(queueRow.actor_action_type || '').trim();

                    if (documentId > 0) {
                        tr.setAttribute('data-document-id', String(documentId));
                    }
                    if (Number.isFinite(documentVersion) && documentVersion > 0) {
                        tr.setAttribute('data-document-version', String(documentVersion));
                    }
                    if (trackingId !== '') {
                        tr.setAttribute('data-tracking-id', trackingId);
                    }
                    if (offlineRowKey !== '') {
                        tr.setAttribute('data-row-key', offlineRowKey);
                    } else if (documentId > 0) {
                        tr.setAttribute('data-row-key', 'doc:' + String(documentId));
                    } else if (trackingId !== '') {
                        tr.setAttribute('data-row-key', 'trk:' + trackingId.toUpperCase());
                    }
                    if (isOfflineQueued) {
                        tr.setAttribute('data-offline-queued', '1');
                    }
                    if (offlineOperationId !== '') {
                        tr.setAttribute('data-offline-operation-id', offlineOperationId);
                    }
                    if (dateReceivedRaw !== '') {
                        tr.setAttribute('data-date-received', dateReceivedRaw);
                    }
                    if (dateCreatedRaw !== '') {
                        tr.setAttribute('data-date-created', dateCreatedRaw);
                    }
                    if (deadlineAtRaw !== '') {
                        tr.setAttribute('data-deadline-at', deadlineAtRaw);
                    }
                    if (deadlineBucketRaw !== '') {
                        tr.setAttribute('data-deadline-bucket', deadlineBucketRaw);
                    }
                    if (statusRaw !== '') {
                        tr.setAttribute('data-status', statusRaw);
                    }
                    if (actorActionTypeRaw !== '') {
                        tr.setAttribute('data-actor-action-type', actorActionTypeRaw);
                    }
                    if (currentHolderLabel !== '') {
                        tr.setAttribute('data-current-holder', currentHolderLabel);
                    }
                    if (hasSectionReceive) {
                        tr.setAttribute('data-has-section-receive', '1');
                    }
                    if (hasSignedAction) {
                        tr.setAttribute('data-has-signed-action', '1');
                    }
                    if (hasReturnAction) {
                        tr.setAttribute('data-has-return-action', '1');
                    }
                    if (Number.isFinite(createdByUserId) && createdByUserId > 0) {
                        tr.setAttribute('data-created-by-user-id', String(createdByUserId));
                    }
                    if (Number.isFinite(originOfficeId) && originOfficeId > 0) {
                        tr.setAttribute('data-origin-office-id', String(originOfficeId));
                    }
                    if (Number.isFinite(currentOfficeIdRaw) && currentOfficeIdRaw > 0) {
                        tr.setAttribute('data-current-office-id', String(currentOfficeIdRaw));
                    }
                    if (Number.isFinite(pendingOfficeId) && pendingOfficeId > 0) {
                        tr.setAttribute('data-pending-office-id', String(pendingOfficeId));
                    }
                    if (currentOfficeLevel !== '') {
                        tr.setAttribute('data-current-office-level', currentOfficeLevel);
                    }
                    if (pendingOfficeLevel !== '') {
                        tr.setAttribute('data-pending-office-level', pendingOfficeLevel);
                    }

                    headerNodes.forEach(function (headerNode, columnIndex) {
                        const column = headers[columnIndex];
                        const isExportIgnored = String(headerNode.getAttribute('data-export-ignore') || '') === '1';
                        if (bulkRowExportEnabled && isExportIgnored) {
                            const selectCell = document.createElement('td');
                            selectCell.className = 'bulk-select-col';
                            selectCell.setAttribute('data-export-ignore', '1');
                            const selectInput = document.createElement('input');
                            selectInput.type = 'checkbox';
                            selectInput.className = 'bulk-row-select';
                            selectInput.setAttribute('aria-label', 'Select row for export');
                            selectCell.appendChild(selectInput);
                            tr.appendChild(selectCell);
                            return;
                        }
                        const td = document.createElement('td');
                        td.textContent = queueCellValueForColumn(column, queueRow);
                        tr.appendChild(td);
                    });
                    fragment.appendChild(tr);
                });
                tableBody.appendChild(fragment);

                renderQueueActionControls();
                applyTableFilters();
            }

            function setLiveStatus(message, isError) {
                if (!queueLiveStatus) {
                    return;
                }
                queueLiveStatus.textContent = String(message || '');
                queueLiveStatus.classList.toggle('error', !!isError);
            }

            function setLiveChannelIndicator(mode) {
                if (!queueLiveChannelIndicator) {
                    return;
                }
                const normalizedMode = normalizeLabelKey(mode || 'pending');
                const classMap = {
                    pending: 'is-pending',
                    internet: 'is-internet',
                    localhost: 'is-localhost',
                    cache: 'is-cache',
                    offline: 'is-offline',
                    checking: 'is-pending',
                };
                const labelMap = {
                    pending: 'Sync Endpoint: Waiting',
                    internet: 'Sync Endpoint: Internet',
                    localhost: 'Sync Endpoint: Localhost Fallback',
                    cache: 'Sync Endpoint: Offline Cache',
                    offline: 'Sync Endpoint: Offline Mode',
                    checking: 'Sync Endpoint: Checking Localhost...',
                };
                const resolvedClass = Object.prototype.hasOwnProperty.call(classMap, normalizedMode)
                    ? classMap[normalizedMode]
                    : classMap.pending;
                const resolvedLabel = Object.prototype.hasOwnProperty.call(labelMap, normalizedMode)
                    ? labelMap[normalizedMode]
                    : labelMap.pending;
                queueLiveChannelIndicator.classList.remove('is-pending', 'is-internet', 'is-localhost', 'is-cache', 'is-offline');
                queueLiveChannelIndicator.classList.add(resolvedClass);
                queueLiveChannelIndicator.textContent = resolvedLabel;
                queueLiveChannelIndicator.setAttribute('aria-label', resolvedLabel);
            }

            function normalizeHostname(hostname) {
                return String(hostname || '').trim().toLowerCase();
            }

            function isLoopbackHostname(hostname) {
                const normalized = normalizeHostname(hostname);
                return normalized === 'localhost'
                    || normalized === '127.0.0.1'
                    || normalized === '::1'
                    || normalized === '[::1]';
            }

            function resolveLocalhostFallbackUrls(rawUrl) {
                let parsedUrl;
                try {
                    parsedUrl = new URL(String(rawUrl || ''), window.location.origin);
                } catch (error) {
                    return [];
                }

                if (parsedUrl.origin !== window.location.origin) {
                    return [];
                }

                const currentHost = normalizeHostname(window.location.hostname || '');
                const primaryHost = normalizeHostname(parsedUrl.hostname || '');
                const hostCandidates = [];

                if (primaryHost === 'localhost') {
                    hostCandidates.push('127.0.0.1');
                } else if (primaryHost === '127.0.0.1' || primaryHost === '::1' || primaryHost === '[::1]') {
                    hostCandidates.push('localhost');
                } else {
                    hostCandidates.push('localhost', '127.0.0.1');
                }

                const unique = [];
                hostCandidates.forEach(function (candidateHost) {
                    const normalizedCandidate = normalizeHostname(candidateHost);
                    if (normalizedCandidate === '' || normalizedCandidate === primaryHost) {
                        return;
                    }
                    if (unique.indexOf(normalizedCandidate) !== -1) {
                        return;
                    }
                    try {
                        const fallbackUrl = new URL(parsedUrl.toString());
                        fallbackUrl.hostname = candidateHost;
                        unique.push(fallbackUrl.toString());
                    } catch (error) {
                        // Ignore malformed fallback candidate.
                    }
                });

                if (!isLoopbackHostname(currentHost) && unique.length > 1) {
                    return [unique[0]];
                }

                return unique;
            }

            async function fetchWithLocalhostFallback(rawUrl, requestOptions) {
                const options = requestOptions && typeof requestOptions === 'object' ? requestOptions : {};
                const markNetworkUnavailable = function (error) {
                    if (error && typeof error === 'object') {
                        error.__DTMISNetworkUnavailable = true;
                    }
                    return error;
                };
                try {
                    const primaryResponse = await fetch(String(rawUrl || ''), options);
                    return {
                        response: primaryResponse,
                        viaLocalhost: false,
                    };
                } catch (primaryError) {
                    const fallbackUrls = resolveLocalhostFallbackUrls(rawUrl);
                    if (!Array.isArray(fallbackUrls) || fallbackUrls.length === 0) {
                        throw markNetworkUnavailable(primaryError);
                    }

                    let lastError = primaryError;
                    for (let index = 0; index < fallbackUrls.length; index += 1) {
                        const fallbackUrl = String(fallbackUrls[index] || '').trim();
                        if (fallbackUrl === '') {
                            continue;
                        }
                        try {
                            const fallbackOptions = Object.assign({}, options, {
                                credentials: 'include',
                            });
                            const fallbackResponse = await fetch(fallbackUrl, fallbackOptions);
                            return {
                                response: fallbackResponse,
                                viaLocalhost: true,
                            };
                        } catch (fallbackError) {
                            lastError = fallbackError;
                        }
                    }

                    throw markNetworkUnavailable(lastError);
                }
            }

            function queuedOutboxCountFromState(state) {
                if (!state || typeof state !== 'object') {
                    return 0;
                }
                const pending = Number(state.pending || 0);
                const syncing = Number(state.syncing || 0);
                const failed = Number(state.failed || 0);
                const blocked = Number(state.blocked || 0);
                return Math.max(0, pending + syncing + failed + blocked);
            }

            let headerConnectivityMode = navigator.onLine ? 'online' : 'offline';
            let headerConnectivityProbeToken = 0;

            function setHeaderConnectivityMode(mode, stateOverride) {
                const normalizedMode = normalizeLabelKey(mode || 'online');
                if (normalizedMode === 'local' || normalizedMode === 'localhost') {
                    headerConnectivityMode = 'local';
                } else if (normalizedMode === 'offline' || normalizedMode === 'cache') {
                    headerConnectivityMode = 'offline';
                } else {
                    headerConnectivityMode = 'online';
                }
                renderHeaderOfflineIndicator(stateOverride);
            }

            async function refreshHeaderConnectivityMode(stateOverride) {
                const probeToken = headerConnectivityProbeToken + 1;
                headerConnectivityProbeToken = probeToken;
                try {
                    const probeUrl = new URL(String(offlineProbePath || window.location.pathname || '/'), window.location.origin);
                    probeUrl.searchParams.set('offline_probe', String(Date.now()));
                    const result = await fetchWithLocalhostFallback(probeUrl.toString(), {
                        method: 'GET',
                        cache: 'no-store',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json,text/plain,*/*',
                            'X-DTMIS-Probe': '1',
                        },
                    });
                    if (probeToken !== headerConnectivityProbeToken) {
                        return;
                    }

                    const response = result && result.response ? result.response : null;
                    const viaLocalhost = !!(result && result.viaLocalhost);
                    const localReachable = !!(response && response.ok);
                    if (localReachable && viaLocalhost) {
                        setHeaderConnectivityMode('local', stateOverride);
                        return;
                    }

                    if (localReachable) {
                        if (!navigator.onLine && isLoopbackHostname(window.location.hostname)) {
                            setHeaderConnectivityMode('local', stateOverride);
                            return;
                        }
                        setHeaderConnectivityMode('online', stateOverride);
                        return;
                    }

                    setHeaderConnectivityMode('offline', stateOverride);
                } catch (error) {
                    if (probeToken !== headerConnectivityProbeToken) {
                        return;
                    }
                    setHeaderConnectivityMode('offline', stateOverride);
                }
            }

            function renderHeaderOfflineIndicator(stateOverride) {
                if (!headerOfflineIndicator || !headerOfflineIndicatorText) {
                    return;
                }

                let queuedCount = queuedOutboxCountFromState(stateOverride);
                if (queuedCount <= 0 && window.DTMISOfflineOutbox && typeof window.DTMISOfflineOutbox.getState === 'function') {
                    try {
                        queuedCount = queuedOutboxCountFromState(window.DTMISOfflineOutbox.getState());
                    } catch (error) {
                        queuedCount = 0;
                    }
                }

                const resolvedMode = headerConnectivityMode === 'local'
                    ? 'local'
                    : (headerConnectivityMode === 'offline' ? 'offline' : 'online');
                const isOnline = resolvedMode === 'online';
                const isLocalOnly = resolvedMode === 'local';
                const isOffline = resolvedMode === 'offline';

                let label = 'Online';
                if (isLocalOnly) {
                    label = 'Local only';
                } else if (isOffline) {
                    label = 'Offline';
                }
                if (queuedCount > 0) {
                    label += ' | ' + String(queuedCount) + ' queued';
                }

                headerOfflineIndicator.classList.toggle('is-online', isOnline);
                headerOfflineIndicator.classList.toggle('is-local', isLocalOnly);
                headerOfflineIndicator.classList.toggle('is-offline', isOffline);
                headerOfflineIndicatorText.textContent = label;
                headerOfflineIndicator.setAttribute('aria-label', 'Network status: ' + label);
                if (isOnline) {
                    headerOfflineIndicator.title = queuedCount > 0
                        ? 'Connected. ' + String(queuedCount) + ' request(s) queued for sync.'
                        : 'Connected.';
                } else if (isLocalOnly) {
                    headerOfflineIndicator.title = queuedCount > 0
                        ? 'Internet is unavailable, but the local app is reachable. ' + String(queuedCount) + ' request(s) are queued.'
                        : 'Internet is unavailable, but the local app is reachable.';
                } else {
                    headerOfflineIndicator.title = 'Offline. Requests will be queued and synced when connection returns.';
                }
            }

            function bindHeaderOfflineIndicator() {
                if (!headerOfflineIndicator || !headerOfflineIndicatorText) {
                    return;
                }
                renderHeaderOfflineIndicator();
                window.addEventListener('online', function () {
                    refreshHeaderConnectivityMode();
                });
                window.addEventListener('offline', function () {
                    refreshHeaderConnectivityMode();
                });
                window.addEventListener('DTMIS:offline-read-hit', function () {
                    setHeaderConnectivityMode('offline');
                });
                window.addEventListener('DTMIS:outbox-state', function (event) {
                    const detail = event && event.detail ? event.detail : null;
                    const nextState = detail && detail.state ? detail.state : null;
                    if (nextState && nextState.online === false) {
                        setHeaderConnectivityMode('offline', nextState);
                        return;
                    }
                    if (nextState && nextState.online === true && headerConnectivityMode === 'offline' && navigator.onLine) {
                        refreshHeaderConnectivityMode(nextState);
                        return;
                    }
                    renderHeaderOfflineIndicator(nextState);
                });
                window.addEventListener('DTMIS:offline-data-cleared', function () {
                    renderHeaderOfflineIndicator();
                });
                refreshHeaderConnectivityMode();
            }

            function bindOfflineQueuedQueueView() {
                if (!isIntakePage) {
                    return;
                }
                ensureOfflineQueuedStatusFilterOption();
                refreshOfflineQueuedRowsInView();
                window.addEventListener('DTMIS:offline-badge-click', function () {
                    ensureOfflineQueuedStatusFilterOption();
                    refreshOfflineQueuedRowsInView().finally(function () {
                        if (statusFilterSelect) {
                            statusFilterSelect.value = offlineQueuedStatusFilterValue;
                        }
                        applyTableFilters();
                        if (tablePanel) {
                            tablePanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                });
                window.addEventListener('DTMIS:outbox-state', function () {
                    refreshOfflineQueuedRowsInView();
                });
                window.addEventListener('DTMIS:outbox-synced', function () {
                    refreshOfflineQueuedRowsInView();
                });
                window.addEventListener('DTMIS:offline-data-cleared', function () {
                    refreshOfflineQueuedRowsInView();
                });
            }

            function pollLiveDashboardData() {
                if (!liveDashboardRefreshEnabled || !dashboardLivePath || liveRefreshInFlight) {
                    return;
                }

                liveRefreshInFlight = true;
                const requestUrl = buildLiveDashboardRequestUrl();
                fetchWithLocalhostFallback(requestUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json' },
                }).then(function (networkResult) {
                    const response = networkResult && networkResult.response ? networkResult.response : null;
                    const viaLocalhost = !!(networkResult && networkResult.viaLocalhost);
                    if (!response) {
                        const noResponseError = new Error('Unable to sync live dashboard data.');
                        noResponseError.__DTMISSyncFailure = true;
                        throw noResponseError;
                    }
                    if (!response.ok) {
                        return response.text().then(function (rawText) {
                            let endpointMessage = '';
                            const responseText = String(rawText || '').trim();
                            if (responseText !== '') {
                                try {
                                    const parsed = JSON.parse(responseText);
                                    endpointMessage = String(parsed && parsed.message ? parsed.message : '').trim();
                                } catch (error) {
                                    endpointMessage = responseText.replace(/\s+/g, ' ').slice(0, 180).trim();
                                }
                            }
                            const httpError = new Error(
                                'Live sync endpoint returned HTTP '
                                + String(response.status)
                                + (endpointMessage !== '' ? (': ' + endpointMessage) : '.')
                            );
                            httpError.__DTMISSyncFailure = true;
                            httpError.__DTMISHttpStatus = Number(response.status || 0);
                            httpError.__DTMISViaLocalhost = viaLocalhost;
                            throw httpError;
                        });
                    }
                    const offlineCacheHit = String(response.headers.get('X-DTMIS-Offline-Cache') || '').toUpperCase() === 'HIT';
                    return response.json().then(function (payload) {
                        return {
                            payload: payload,
                            viaLocalhost: viaLocalhost,
                            offlineCacheHit: offlineCacheHit,
                        };
                    });
                }).then(function (payload) {
                    const data = payload && typeof payload === 'object' ? payload.payload : null;
                    if (!data || data.ok !== true) {
                        const payloadError = new Error(data && data.message ? String(data.message) : 'Live data sync failed.');
                        payloadError.__DTMISSyncFailure = true;
                        payloadError.__DTMISViaLocalhost = !!(payload && payload.viaLocalhost);
                        throw payloadError;
                    }

                    updateLiveCardsAndPanels(data);
                    updateLiveCharts(data);
                    refreshChartOverrides();
                    const payloadQueueRows = Array.isArray(data.queue_rows) ? data.queue_rows : [];
                    let queueRowsForView = payloadQueueRows;
                    if (showCompletedQueueRowsOnly) {
                        queueRowsForView = payloadQueueRows.filter(function (row) { return queueRowIsCompletedForView(row); });
                    } else if (hideCompletedQueueRows) {
                        queueRowsForView = payloadQueueRows.filter(function (row) { return !queueRowIsCompletedForView(row); });
                    }
                    renderLiveQueueTable(queueRowsForView);

                    const serverTimeRaw = String(data.server_time || '').trim();
                    const syncDate = serverTimeRaw === '' ? new Date() : new Date(serverTimeRaw);
                    const syncLabel = Number.isNaN(syncDate.getTime())
                        ? 'just now'
                        : syncDate.toLocaleString();
                    if (payload.offlineCacheHit) {
                        setLiveChannelIndicator('cache');
                        setHeaderConnectivityMode('offline');
                        setLiveStatus('Live sync: offline mode (cached data) as of ' + syncLabel + '.', true);
                        return;
                    }
                    if (payload.viaLocalhost) {
                        setLiveChannelIndicator('localhost');
                        setHeaderConnectivityMode('local');
                        setLiveStatus('Live sync: updated via localhost at ' + syncLabel + '.', false);
                        return;
                    }
                    if (!navigator.onLine && isLoopbackHostname(window.location.hostname)) {
                        setLiveChannelIndicator('localhost');
                        setHeaderConnectivityMode('local');
                        setLiveStatus('Live sync: local app reachable while internet is unavailable. Updated ' + syncLabel + '.', false);
                        return;
                    }
                    setLiveChannelIndicator('internet');
                    setHeaderConnectivityMode('online');
                    setLiveStatus('Live sync: updated ' + syncLabel + '.', false);
                }).catch(function (error) {
                    const networkUnavailable = !!(error && error.__DTMISNetworkUnavailable);
                    if (networkUnavailable) {
                        setLiveChannelIndicator('offline');
                        setHeaderConnectivityMode('offline');
                        setLiveStatus('Live sync: offline mode active (internet and localhost unavailable). Showing latest loaded data.', true);
                        return;
                    }

                    const viaLocalhost = !!(error && error.__DTMISViaLocalhost);
                    const endpointErrorMessage = String(error && error.message ? error.message : '').trim();
                    setLiveChannelIndicator(viaLocalhost ? 'localhost' : 'internet');
                    setLiveStatus(
                        endpointErrorMessage !== ''
                            ? endpointErrorMessage
                            : 'Live sync endpoint error. Connection is online, but the server response failed. Retrying automatically...',
                        true
                    );
                }).finally(function () {
                    liveRefreshInFlight = false;
                    if (liveRefreshPending) {
                        liveRefreshPending = false;
                        window.setTimeout(function () {
                            pollLiveDashboardData();
                        }, 0);
                    }
                });
            }

            function bindLiveDashboardRefresh() {
                if (!liveDashboardRefreshEnabled || !dashboardLivePath) {
                    return;
                }
                setLiveChannelIndicator('pending');
                pollLiveDashboardData();
                liveRefreshTimerId = window.setInterval(function () {
                    if (!document.hidden) {
                        pollLiveDashboardData();
                    }
                }, 15000);
                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) {
                        pollLiveDashboardData();
                    }
                });
                window.addEventListener('offline', function () {
                    setLiveChannelIndicator('checking');
                    setLiveStatus('Internet is offline. Trying localhost fallback before offline mode...', false);
                    pollLiveDashboardData();
                });
                window.addEventListener('online', function () {
                    setLiveChannelIndicator('internet');
                    setLiveStatus('Connection restored. Syncing latest queue...', false);
                    pollLiveDashboardData();
                });
                window.addEventListener('beforeunload', function () {
                    if (liveRefreshTimerId > 0) {
                        window.clearInterval(liveRefreshTimerId);
                    }
                });
            }

            function buildLiveDashboardRequestUrl() {
                const fallbackJoiner = String(dashboardLivePath || '').indexOf('?') !== -1 ? '&' : '?';
                try {
                    const requestUrl = new URL(String(dashboardLivePath || ''), window.location.origin);
                    appendDateRangeToUrlObject(requestUrl, getActiveDateFilterRange());
                    requestUrl.searchParams.set('limit', String(getDashboardLiveLimit()));
                    requestUrl.searchParams.set('t', String(Date.now()));
                    return requestUrl.toString();
                } catch (error) {
                    const range = getActiveDateFilterRange();
                    let requestUrl = String(dashboardLivePath || '') + fallbackJoiner + 'limit=' + encodeURIComponent(String(getDashboardLiveLimit())) + '&t=' + String(Date.now());
                    if (range.fromDate) {
                        requestUrl += '&fromDate=' + encodeURIComponent(range.fromDate);
                    }
                    if (range.toDate) {
                        requestUrl += '&toDate=' + encodeURIComponent(range.toDate);
                    }
                    return requestUrl;
                }
            }

            function updateQueuePaginationUi(totalRows, totalPages, pageStartIndex, pageEndIndex) {
                if (!queueTablePagination || !queueTablePaginationMeta || !queueTablePrevBtn || !queueTableNextBtn) {
                    return;
                }

                const hasPagination = totalRows > queueRowsPerPage;
                queueTablePagination.hidden = !hasPagination;
                queueTablePrevBtn.disabled = queueCurrentPage <= 1;
                queueTableNextBtn.disabled = queueCurrentPage >= totalPages;

                const safeStart = totalRows > 0 ? pageStartIndex + 1 : 0;
                const safeEnd = totalRows > 0 ? Math.min(pageEndIndex, totalRows) : 0;
                queueTablePaginationMeta.textContent = 'Showing ' + String(safeStart) + '-' + String(safeEnd) + ' of ' + String(totalRows);
            }

            function buildQueueFilterSignature() {
                const searchTerm = String(queueSearchInput ? queueSearchInput.value : '').toLowerCase().trim();
                const statusFilterValue = normalizeLabelKey(statusFilterSelect ? statusFilterSelect.value : '');
                const fromTimestamp = parseDateInputValue(dateFilterFromInput ? dateFilterFromInput.value : '', false);
                const toTimestamp = parseDateInputValue(dateFilterToInput ? dateFilterToInput.value : '', true);
                return [searchTerm, statusFilterValue, String(fromTimestamp), String(toTimestamp)].join('|');
            }

            function persistQueueTableState() {
                if (!shouldPaginateQueueTable || typeof window.sessionStorage === 'undefined') {
                    return;
                }
                try {
                    const payload = {
                        page: queueCurrentPage,
                        trackingId: String(selectedQueueTrackingId || '').trim(),
                        search: String(queueSearchInput ? queueSearchInput.value : '').trim(),
                        status: String(statusFilterSelect ? statusFilterSelect.value : '').trim(),
                        fromDate: normalizeDateInputString(dateFilterFromInput ? dateFilterFromInput.value : ''),
                        toDate: normalizeDateInputString(dateFilterToInput ? dateFilterToInput.value : ''),
                    };
                    window.sessionStorage.setItem(queueTableStateStorageKey, JSON.stringify(payload));
                } catch (error) {
                    // Ignore storage errors.
                }
            }

            function restoreQueueTableState() {
                if (!shouldPaginateQueueTable || typeof window.sessionStorage === 'undefined') {
                    return;
                }

                let parsedUrl = null;
                try {
                    parsedUrl = new URL(window.location.href);
                } catch (error) {
                    parsedUrl = null;
                }

                let persisted = null;
                try {
                    const raw = String(window.sessionStorage.getItem(queueTableStateStorageKey) || '').trim();
                    persisted = raw !== '' ? JSON.parse(raw) : null;
                } catch (error) {
                    persisted = null;
                }

                if (!persisted || typeof persisted !== 'object') {
                    return;
                }

                const hasStatusQuery = !!(parsedUrl && normalizeLabelKey(parsedUrl.searchParams.get('status') || '') !== '');
                const hasFromDateQuery = !!(parsedUrl && normalizeDateInputString(parsedUrl.searchParams.get('fromDate') || '') !== '');
                const hasToDateQuery = !!(parsedUrl && normalizeDateInputString(parsedUrl.searchParams.get('toDate') || '') !== '');

                if (queueSearchInput && String(queueSearchInput.value || '').trim() === '' && typeof persisted.search === 'string') {
                    queueSearchInput.value = String(persisted.search || '').trim();
                }

                if (statusFilterSelect && !hasStatusQuery && typeof persisted.status === 'string') {
                    const persistedStatus = String(persisted.status || '').trim();
                    const matchingOption = Array.from(statusFilterSelect.options).find(function (option) {
                        return String(option.value || '').trim() === persistedStatus;
                    });
                    if (matchingOption) {
                        statusFilterSelect.value = persistedStatus;
                    }
                }

                if (dateFilterFromInput && !hasFromDateQuery && typeof persisted.fromDate === 'string') {
                    const restoredFromDate = normalizeDateInputString(persisted.fromDate);
                    if (restoredFromDate !== '') {
                        dateFilterFromInput.value = restoredFromDate;
                    }
                }

                if (dateFilterToInput && !hasToDateQuery && typeof persisted.toDate === 'string') {
                    const restoredToDate = normalizeDateInputString(persisted.toDate);
                    if (restoredToDate !== '') {
                        dateFilterToInput.value = restoredToDate;
                    }
                }

                const restoredPage = parseInt(String(persisted.page || ''), 10);
                if (Number.isFinite(restoredPage) && restoredPage > 0) {
                    queueCurrentPage = restoredPage;
                }

                if (typeof persisted.trackingId === 'string') {
                    selectedQueueTrackingId = String(persisted.trackingId || '').trim();
                }

                lastQueueFilterSignature = buildQueueFilterSignature();
            }

            function renderQueuePagination(filteredRows, resetPage) {
                const safeFilteredRows = Array.isArray(filteredRows) ? filteredRows : [];
                if (!shouldPaginateQueueTable) {
                    safeFilteredRows.forEach(function (row) {
                        row.style.display = '';
                    });
                    if (queueTablePagination) {
                        queueTablePagination.hidden = true;
                    }
                    return;
                }
                const totalRows = safeFilteredRows.length;
                const totalPages = Math.max(1, Math.ceil(totalRows / queueRowsPerPage));
                const shouldResetPage = resetPage === true;

                if (shouldResetPage) {
                    queueCurrentPage = 1;
                }
                if (queueCurrentPage > totalPages) {
                    queueCurrentPage = totalPages;
                }
                if (queueCurrentPage < 1) {
                    queueCurrentPage = 1;
                }

                const startIndex = (queueCurrentPage - 1) * queueRowsPerPage;
                const endIndex = startIndex + queueRowsPerPage;
                safeFilteredRows.forEach(function (row, index) {
                    row.style.display = (index >= startIndex && index < endIndex) ? '' : 'none';
                });

                updateQueuePaginationUi(totalRows, totalPages, startIndex, endIndex);
            }

            function bindQueuePagination() {
                if (!queueTablePrevBtn || !queueTableNextBtn) {
                    return;
                }

                queueTablePrevBtn.addEventListener('click', function () {
                    if (queueCurrentPage <= 1) {
                        return;
                    }
                    queueCurrentPage -= 1;
                    applyTableFilters(false);
                });

                queueTableNextBtn.addEventListener('click', function () {
                    queueCurrentPage += 1;
                    applyTableFilters(false);
                });
            }

            function applyTableFilters(resetPage) {
                if (!tableElement || !tableBody) {
                    return;
                }

                const searchTerm = String(queueSearchInput ? queueSearchInput.value : '').toLowerCase().trim();
                const statusFilterValue = normalizeLabelKey(statusFilterSelect ? statusFilterSelect.value : '');
                const fromTimestamp = parseDateInputValue(dateFilterFromInput ? dateFilterFromInput.value : '', false);
                const toTimestamp = parseDateInputValue(dateFilterToInput ? dateFilterToInput.value : '', true);
                const filterSignature = buildQueueFilterSignature();
                const shouldResetPage = resetPage === true || filterSignature !== lastQueueFilterSignature;
                lastQueueFilterSignature = filterSignature;

                const headers = Array.from(tableElement.querySelectorAll('thead th')).map(function (th) {
                    return String(th.textContent || '').toLowerCase();
                });
                let dateColumnIndex = headers.findIndex(function (text) { return text.indexOf('date') !== -1; });
                if (dateColumnIndex < 0) {
                    dateColumnIndex = headers.findIndex(function (text) { return text.indexOf('last update') !== -1; });
                }
                if (dateColumnIndex < 0) {
                    dateColumnIndex = headers.findIndex(function (text) { return text.indexOf('received') !== -1; });
                }
                if (dateColumnIndex < 0) {
                    dateColumnIndex = headers.findIndex(function (text) {
                        return text.indexOf('created at') !== -1
                            || text.indexOf('updated at') !== -1
                            || text.indexOf('created') !== -1
                            || text.indexOf('updated') !== -1;
                    });
                }
                let statusColumnIndex = headers.findIndex(function (text) { return text.indexOf('status') !== -1; });
                if (statusColumnIndex < 0) {
                    statusColumnIndex = headers.findIndex(function (text) {
                        return text.indexOf('action required') !== -1 || text.indexOf('action / taken') !== -1;
                    });
                }
                const actorActionColumnIndex = headers.findIndex(function (text) {
                    return text.indexOf('last action by me') !== -1
                        || text.indexOf('my action') !== -1
                        || text.indexOf('action by me') !== -1
                        || text.indexOf('action / taken') !== -1;
                });
                const dateColumnKey = dateColumnIndex >= 0 ? headers[dateColumnIndex] : '';
                const useCreatedDate = dateColumnKey.indexOf('created') !== -1;

                const rows = Array.from(tableBody.querySelectorAll('tr'));
                const filteredRows = [];
                rows.forEach(function (row) {
                    const cells = Array.from(row.querySelectorAll('td'));
                    if (cells.length === 0) {
                        return;
                    }
                    if (row.querySelector('.table-empty-state')) {
                        return;
                    }

                    const rowText = String(row.textContent || '').toLowerCase();
                    const matchesSearch = searchTerm === '' || rowText.indexOf(searchTerm) !== -1;

                    let matchesDate = true;
                    if (dateColumnIndex >= 0 && (fromTimestamp !== null || toTimestamp !== null)) {
                        const dateCell = cells[dateColumnIndex];
                        const dateText = String(dateCell ? dateCell.textContent : '').trim();
                        const rowDateRaw = String(
                            row.getAttribute(useCreatedDate ? 'data-date-created' : 'data-date-received')
                            || row.getAttribute('data-date-received')
                            || row.getAttribute('data-date-created')
                            || ''
                        ).trim();
                        const rowDateTs = parseDateInputValue(rowDateRaw !== '' ? rowDateRaw : dateText, false);
                        if (rowDateTs === null) {
                            matchesDate = false;
                        } else if (fromTimestamp !== null && rowDateTs < fromTimestamp) {
                            matchesDate = false;
                        } else if (toTimestamp !== null && rowDateTs > toTimestamp) {
                            matchesDate = false;
                        }
                    }

                    let rowStatusRaw = String(row.getAttribute('data-status') || '').trim();
                    if (rowStatusRaw === '' && statusColumnIndex >= 0) {
                        const statusCell = cells[statusColumnIndex];
                        rowStatusRaw = String(statusCell ? statusCell.textContent : '').trim();
                    }

                    const rowDateCreatedRaw = String(row.getAttribute('data-date-created') || '').trim();
                    const rowDeadlineRaw = String(row.getAttribute('data-deadline-at') || '').trim();
                    const rowDeadlineBucket = String(row.getAttribute('data-deadline-bucket') || '').trim();
                    let rowActorActionRaw = String(row.getAttribute('data-actor-action-type') || '').trim();
                    if (rowActorActionRaw === '' && actorActionColumnIndex >= 0) {
                        const actionCell = cells[actorActionColumnIndex];
                        rowActorActionRaw = String(actionCell ? actionCell.textContent : '').trim();
                    }
                    const rowPendingOfficeId = parseInt(String(row.getAttribute('data-pending-office-id') || '0'), 10);
                    const rowCurrentOfficeId = parseInt(String(row.getAttribute('data-current-office-id') || '0'), 10);
                    const rowHasSignedAction = parseInt(String(row.getAttribute('data-has-signed-action') || '0'), 10);
                    const rowCreatedByUserId = parseInt(String(row.getAttribute('data-created-by-user-id') || '0'), 10);
                    const rowIsOfflineQueued = String(row.getAttribute('data-offline-queued') || '') === '1';
                    const matchesStatus = matchesStatusCategoryFilter(
                        statusFilterValue,
                        rowStatusRaw,
                        rowDateCreatedRaw,
                        rowDeadlineRaw,
                        rowDeadlineBucket,
                        Number.isFinite(rowPendingOfficeId) ? rowPendingOfficeId : 0,
                        Number.isFinite(rowCurrentOfficeId) ? rowCurrentOfficeId : 0,
                        Number.isFinite(rowHasSignedAction) ? rowHasSignedAction : 0,
                        rowIsOfflineQueued,
                        Number.isFinite(rowCreatedByUserId) ? rowCreatedByUserId : 0,
                        rowActorActionRaw
                    );

                    row.style.display = 'none';
                    if (matchesSearch && matchesDate && matchesStatus) {
                        filteredRows.push(row);
                    }
                });
                renderQueuePagination(filteredRows, shouldResetPage);
                syncSelectedDocumentToolsState(true);
                syncBulkSelectionUi();
                persistQueueTableState();
                scheduleArchivePrefetchFromTable();
                refreshArchiveOfflineAvailabilityIndicators();
            }

            function bindSearchAndFilters() {
                if (queueSearchInput) {
                    const searchForm = queueSearchInput.closest('form');
                    queueSearchInput.addEventListener('input', applyTableFilters);
                    if (searchForm) {
                        searchForm.addEventListener('submit', function (event) {
                            event.preventDefault();
                            applyTableFilters();
                        });
                    }
                }

                if (dateFilterApplyButton) {
                    dateFilterApplyButton.addEventListener('click', function () {
                        syncDateFilterQueryState();
                        syncChartControlsToPageRange();
                        applyTableFilters();
                        if (liveDashboardRefreshEnabled && dashboardLivePath) {
                            pollLiveDashboardData();
                        } else {
                            window.location.reload();
                            return;
                        }
                        setOpen(dateFilterDropdown, dateFilterToggle, false);
                    });
                }

                if (dateFilterFromInput) {
                    dateFilterFromInput.addEventListener('change', applyTableFilters);
                }
                if (dateFilterToInput) {
                    dateFilterToInput.addEventListener('change', applyTableFilters);
                }
                if (statusFilterSelect) {
                    statusFilterSelect.addEventListener('change', applyTableFilters);
                }
            }

            function bindChartDateFilters() {
                if (chartPanels.length === 0) {
                    return;
                }

                chartPanels.forEach(function (chartPanel) {
                    const elements = getChartPanelFilterElements(chartPanel);
                    if (!elements || !elements.kind) {
                        return;
                    }

                    syncChartFilterCaption(elements.kind);

                    if (elements.toggle) {
                        elements.toggle.addEventListener('click', function (event) {
                            event.stopPropagation();
                            const nextState = !(elements.dropdown && elements.dropdown.classList.contains('is-open'));
                            chartPanels.forEach(function (panelNode) {
                                const siblingElements = getChartPanelFilterElements(panelNode);
                                if (siblingElements && siblingElements.kind !== elements.kind) {
                                    setChartDateDropdownOpen(siblingElements, false);
                                }
                            });
                            setChartDateDropdownOpen(elements, nextState);
                        });
                    }

                    if (elements.applyButton) {
                        elements.applyButton.addEventListener('click', function () {
                            applyChartDateRange(elements.kind, getChartPanelSelectedRange(elements.kind));
                        });
                    }

                    if (elements.resetButton) {
                        elements.resetButton.addEventListener('click', function () {
                            const pageRange = getActiveDateFilterRange();
                            syncChartFilterInputsFromRange(elements.kind, pageRange);
                            applyChartDateRange(elements.kind, pageRange);
                        });
                    }
                });

                document.addEventListener('click', function (event) {
                    chartPanels.forEach(function (chartPanel) {
                        const elements = getChartPanelFilterElements(chartPanel);
                        if (!elements || !elements.wrap) {
                            return;
                        }
                        if (!elements.wrap.contains(event.target)) {
                            setChartDateDropdownOpen(elements, false);
                        }
                    });
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key !== 'Escape') {
                        return;
                    }
                    chartPanels.forEach(function (chartPanel) {
                        const elements = getChartPanelFilterElements(chartPanel);
                        if (elements) {
                            setChartDateDropdownOpen(elements, false);
                        }
                    });
                });
            }

            function statusFilterOptionLabelForValue(filterValue, fallbackLabel) {
                const normalizedFilter = normalizeLabelKey(filterValue);
                if (normalizedFilter === '') {
                    return String(fallbackLabel || '').trim();
                }
                const labels = {
                    awaiting_receive: 'Awaiting Receive',
                    in_transit: 'In Transit',
                    received: 'Received',
                    approved: 'Approved',
                    sign: 'For Signature',
                    forward: 'Forwarded / Routed',
                    pending: 'Pending',
                    due_soon: 'Due Soon',
                    overdue: 'Overdue',
                    for_correction: 'Returned / For Correction',
                    released: 'Released',
                    pending_released: 'Pending Released',
                    ongoing_docs: 'Ongoing Docs',
                    actor_created: 'Created by Me',
                    actor_received: 'Received by Me',
                    actor_forwarded: 'Forwarded by Me',
                    actor_approved: 'Approved by Me',
                    actor_signed: 'Signed by Me',
                    actor_released: 'Released by Me',
                    actor_in_progress: 'Still In Progress',
                    actor_actioned: 'Total Actioned',
                    completed: 'Completed',
                    signed_completed: 'Signed / Completed',
                    pending_forward: 'Pending Forward',
                    intake_drafts: 'Intake Drafts',
                    new_created: 'Encoded Today',
                };
                if (normalizedFilter === offlineQueuedStatusFilterValue) {
                    return offlineQueuedStatusFilterLabel;
                }
                if (Object.prototype.hasOwnProperty.call(labels, normalizedFilter)) {
                    return labels[normalizedFilter];
                }
                const fallback = String(fallbackLabel || '').trim();
                return fallback !== '' ? fallback : normalizedFilter.replace(/_/g, ' ');
            }

            function ensureStatusFilterOption(filterValue, optionLabel) {
                if (!statusFilterSelect) {
                    return '';
                }
                const normalizedFilter = normalizeLabelKey(filterValue);
                if (normalizedFilter === '') {
                    return '';
                }
                const existingOption = Array.from(statusFilterSelect.options).find(function (option) {
                    return normalizeLabelKey(option.value || '') === normalizedFilter;
                });
                if (existingOption) {
                    return String(existingOption.value || '');
                }
                const option = document.createElement('option');
                option.value = normalizedFilter;
                option.textContent = statusFilterOptionLabelForValue(normalizedFilter, optionLabel);
                statusFilterSelect.appendChild(option);
                return normalizedFilter;
            }

            function cardStatusFilterCandidatesFromLabel(label) {
                const labelKey = normalizeLabelKey(label);
                if (labelKey === '') {
                    return [];
                }
                if (labelKey.indexOf('created by me') !== -1) {
                    return ['actor_created', 'new_created', 'pending'];
                }
                if (labelKey.indexOf('received by me') !== -1) {
                    return ['actor_received', 'received', 'pending'];
                }
                if (labelKey.indexOf('forwarded by me') !== -1) {
                    return ['actor_forwarded', 'forward', 'pending'];
                }
                if (labelKey.indexOf('approved by me') !== -1) {
                    return ['actor_approved', 'approved', 'pending'];
                }
                if (labelKey.indexOf('signed by me') !== -1) {
                    return ['actor_signed', 'sign', 'approved', 'pending'];
                }
                if (labelKey.indexOf('released by me') !== -1) {
                    return ['actor_released', 'released', 'completed'];
                }
                if (labelKey.indexOf('still in progress') !== -1) {
                    return ['actor_in_progress', 'ongoing_docs', 'pending'];
                }
                if (labelKey.indexOf('total actioned') !== -1) {
                    return ['actor_actioned', 'completed', 'signed_completed', 'released'];
                }
                if (labelKey.indexOf('pending forward') !== -1) {
                    return ['pending_forward', 'approved', 'forward', 'pending'];
                }
                if (labelKey.indexOf('intake draft') !== -1) {
                    return ['intake_drafts', 'new_created', 'pending'];
                }
                if (labelKey.indexOf('pending released') !== -1) {
                    return ['pending_released', 'released', 'forward', 'pending'];
                }
                if (
                    labelKey.indexOf('pending receive') !== -1
                    || labelKey.indexOf('pending received') !== -1
                    || labelKey.indexOf('awaiting receive') !== -1
                    || labelKey.indexOf('in transit') !== -1
                ) {
                    return ['awaiting_receive', 'forward', 'pending'];
                }
                if (
                    labelKey.indexOf('pending approval') !== -1
                    || labelKey.indexOf('for validation') !== -1
                    || labelKey.indexOf('to validate') !== -1
                ) {
                    return ['received', 'approved', 'pending'];
                }
                if (
                    labelKey.indexOf('pending sign') !== -1
                    || labelKey.indexOf('for signature') !== -1
                    || labelKey.indexOf('to sign') !== -1
                ) {
                    return ['sign', 'approved', 'pending'];
                }
                if (
                    labelKey.indexOf('completed') !== -1
                    || labelKey.indexOf('actioned') !== -1
                    || labelKey.indexOf('done') !== -1
                ) {
                    return ['completed', 'signed_completed', 'released', 'approved'];
                }
                if (labelKey.indexOf('released') !== -1) {
                    return ['released', 'signed_completed', 'completed'];
                }
                if (
                    labelKey.indexOf('forwarded') !== -1 || labelKey.indexOf('routed') !== -1 || labelKey.indexOf('dispatched') !== -1
                ) {
                    return ['forward', 'pending'];
                }
                if (
                    labelKey.indexOf('returned') !== -1
                    || labelKey.indexOf('correction') !== -1
                    || labelKey.indexOf('compliance') !== -1
                    || labelKey.indexOf('comply') !== -1
                ) {
                    return ['for_correction', 'pending'];
                }
                if (labelKey.indexOf('overdue') !== -1) {
                    return ['overdue', 'pending'];
                }
                if (labelKey.indexOf('due soon') !== -1 || labelKey.indexOf('due today') !== -1 || labelKey.indexOf('at risk') !== -1) {
                    return ['due_soon', 'pending'];
                }
                if (
                    labelKey.indexOf('new') !== -1
                    || labelKey.indexOf('created today') !== -1
                    || labelKey.indexOf('encoded today') !== -1
                    || labelKey.indexOf('created by me') !== -1
                    || labelKey.indexOf('submitted') !== -1
                ) {
                    return ['new_created', 'pending'];
                }
                if (labelKey.indexOf('offline') !== -1 || labelKey.indexOf('queued locally') !== -1) {
                    return [offlineQueuedStatusFilterValue, 'pending'];
                }
                if (
                    labelKey.indexOf('ongoing docs') !== -1
                    || labelKey.indexOf('all ongoing') !== -1
                ) {
                    return ['ongoing_docs', 'pending', 'awaiting_receive', 'forward'];
                }
                if (
                    labelKey.indexOf('pending') !== -1
                    || labelKey.indexOf('in progress') !== -1
                    || labelKey.indexOf('ongoing') !== -1
                    || labelKey.indexOf('active') !== -1
                ) {
                    return ['pending', 'awaiting_receive', 'forward'];
                }
                if (labelKey.indexOf('received') !== -1 || labelKey.indexOf('total received') !== -1) {
                    return ['received', 'pending'];
                }

                return [];
            }

            function ensureStatCardStatusFilterOptions() {
                if (!statusFilterSelect) {
                    return;
                }
                const cards = Array.from(document.querySelectorAll('.stat-card'));
                cards.forEach(function (card) {
                    const labelNode = card.querySelector('.stat-label');
                    const label = String(labelNode ? labelNode.textContent : '').trim();
                    if (label === '') {
                        return;
                    }
                    const candidates = cardStatusFilterCandidatesFromLabel(label);
                    if (candidates.length === 0) {
                        return;
                    }
                    ensureStatusFilterOption(candidates[0], label);
                });
            }

            function findExistingStatusFilterValue(candidates) {
                if (!statusFilterSelect) {
                    return '';
                }
                const options = Array.from(statusFilterSelect.options);
                const list = Array.isArray(candidates) ? candidates : [];
                for (let i = 0; i < list.length; i += 1) {
                    const candidate = String(list[i] || '').trim();
                    const normalizedCandidate = normalizeLabelKey(candidate);
                    const matchedOption = options.find(function (option) {
                        return normalizeLabelKey(option.value || '') === normalizedCandidate;
                    });
                    if (matchedOption) {
                        return String(matchedOption.value || '');
                    }
                }
                return '';
            }

            function resolveCardStatusFilterValue(label) {
                const candidates = cardStatusFilterCandidatesFromLabel(label);
                if (candidates.length === 0) {
                    return '';
                }
                if (!statusFilterSelect) {
                    return normalizeLabelKey(candidates[0]);
                }
                return findExistingStatusFilterValue(candidates);
            }

            function resolveStatCardNavigationTarget(filterValue) {
                const normalizedFilter = normalizeLabelKey(filterValue);
                if (
                    normalizedFilter !== ''
                    && typeof statCardNavigationTargets[normalizedFilter] === 'string'
                    && String(statCardNavigationTargets[normalizedFilter]).trim() !== ''
                ) {
                    return String(statCardNavigationTargets[normalizedFilter]).trim();
                }
                if (typeof statCardNavigationTargets.default === 'string' && String(statCardNavigationTargets.default).trim() !== '') {
                    return String(statCardNavigationTargets.default).trim();
                }
                return '';
            }

            function applyStatusFilterFromQuery() {
                if (!statusFilterSelect) {
                    return;
                }
                let parsedUrl = null;
                try {
                    parsedUrl = new URL(window.location.href);
                } catch (error) {
                    parsedUrl = null;
                }
                if (!parsedUrl) {
                    return;
                }
                const requestedStatus = normalizeLabelKey(parsedUrl.searchParams.get('status') || '');
                if (requestedStatus === '') {
                    return;
                }

                let resolvedValue = findExistingStatusFilterValue([requestedStatus]);
                if (resolvedValue === '') {
                    const fallbackMap = {
                        awaiting_receive: ['awaiting_receive', 'forward', 'pending', ''],
                        in_transit: ['in_transit', 'awaiting_receive', 'forward', 'pending', ''],
                        actor_created: ['actor_created', 'new_created', 'pending', ''],
                        actor_received: ['actor_received', 'received', 'pending', ''],
                        actor_forwarded: ['actor_forwarded', 'forward', 'pending', ''],
                        actor_approved: ['actor_approved', 'approved', 'pending', ''],
                        actor_signed: ['actor_signed', 'sign', 'approved', 'pending', ''],
                        actor_released: ['actor_released', 'released', 'completed', ''],
                        actor_in_progress: ['actor_in_progress', 'ongoing_docs', 'pending', ''],
                        actor_actioned: ['actor_actioned', 'completed', 'signed_completed', 'released', ''],
                        received: ['received', 'pending', ''],
                        approved: ['approved', 'pending', ''],
                        sign: ['sign', 'approved', 'pending', ''],
                        forward: ['forward', 'pending', ''],
                        pending: ['pending', ''],
                        due_soon: ['due_soon', 'pending', ''],
                        overdue: ['overdue', 'pending', ''],
                        for_correction: ['for_correction', 'pending', ''],
                        released: ['released', 'signed_completed', 'completed', ''],
                        pending_released: ['pending_released', 'released', 'forward', 'pending', ''],
                        ongoing_docs: ['ongoing_docs', 'pending', 'awaiting_receive', 'forward', ''],
                        completed: ['completed', 'signed_completed', 'released', ''],
                        signed_completed: ['signed_completed', 'completed', 'released', ''],
                        pending_forward: ['pending_forward', 'approved', 'forward', 'pending', ''],
                        intake_drafts: ['intake_drafts', 'new_created', 'pending', ''],
                        new_created: ['new_created', 'pending', ''],
                        offline_queued: [offlineQueuedStatusFilterValue, 'pending', ''],
                    };
                    const fallbackCandidates = Object.prototype.hasOwnProperty.call(fallbackMap, requestedStatus)
                        ? fallbackMap[requestedStatus]
                        : [requestedStatus, 'pending', ''];
                    resolvedValue = findExistingStatusFilterValue(fallbackCandidates);
                }

                if (resolvedValue !== '') {
                    statusFilterSelect.value = resolvedValue;
                }
            }

            function bindStatCardQuickFilters() {
                const cards = Array.from(document.querySelectorAll('.stat-card'));
                if (cards.length === 0) {
                    return;
                }

                cards.forEach(function (card) {
                    const disableStatCardNav = String(card.getAttribute('data-disable-stat-card-nav') || '').trim() === '1';
                    if (disableStatCardNav) {
                        card.classList.remove('stat-card-clickable');
                        card.removeAttribute('role');
                        card.removeAttribute('tabindex');
                        card.removeAttribute('aria-label');
                        return;
                    }

                    const labelNode = card.querySelector('.stat-label');
                    const label = String(labelNode ? labelNode.textContent : '').trim();
                    const filterValue = resolveCardStatusFilterValue(label);
                    const navigationTarget = resolveStatCardNavigationTarget(filterValue);
                    if (navigationTarget === '') {
                        card.classList.remove('stat-card-clickable');
                        card.removeAttribute('role');
                        card.removeAttribute('tabindex');
                        card.removeAttribute('aria-label');
                        return;
                    }

                    card.classList.add('stat-card-clickable');
                    card.setAttribute('role', 'button');
                    card.setAttribute('tabindex', '0');
                    card.setAttribute('aria-label', label === '' ? 'Open queue details' : ('Open page for ' + label));

                    const applyCardAction = function () {
                        try {
                            const destinationUrl = new URL(navigationTarget, window.location.origin);
                            appendDateRangeToUrlObject(destinationUrl, getActiveDateFilterRange());
                            window.location.href = destinationUrl.toString();
                        } catch (error) {
                            window.location.href = navigationTarget;
                        }
                    };

                    card.addEventListener('click', function () {
                        applyCardAction();
                    });
                    card.addEventListener('keydown', function (event) {
                        if (event.key !== 'Enter' && event.key !== ' ') {
                            return;
                        }
                        event.preventDefault();
                        applyCardAction();
                    });
                });
            }

            async function exportVisibleTableToCsv() {
                if (!tableElement || !tableBody) {
                    showAlertDialog('No table is available for export.');
                    return;
                }

                const headerNodes = Array.from(tableElement.querySelectorAll('thead th'));
                const exportColumnIndexes = [];
                const headers = headerNodes.reduce(function (acc, th, index) {
                    const isIgnored = String(th.getAttribute('data-export-ignore') || '') === '1';
                    if (isIgnored) {
                        return acc;
                    }
                    exportColumnIndexes.push(index);
                    acc.push(String(th.textContent || '').trim());
                    return acc;
                }, []);

                const visibleRows = Array.from(tableBody.querySelectorAll('tr'))
                    .filter(function (row) { return row.style.display !== 'none' && !row.querySelector('.table-empty-state'); });

                let sourceRows = visibleRows;
                if (bulkRowExportEnabled) {
                    sourceRows = visibleRows.filter(function (row, index) {
                        const rowKey = getBulkRowSelectionKey(row, index);
                        return rowKey !== '' && selectedBulkRowKeys.has(rowKey);
                    });
                    if (sourceRows.length === 0) {
                        showAlertDialog('Select at least one visible row to export.');
                        return;
                    }
                }

                const rows = sourceRows.map(function (row) {
                    const cells = Array.from(row.querySelectorAll('td'));
                    return exportColumnIndexes.map(function (columnIndex) {
                        const cell = cells[columnIndex];
                        return String(cell ? cell.textContent : '').replace(/\s+/g, ' ').trim();
                    });
                });

                if (rows.length === 0) {
                    showAlertDialog('No visible rows to export.');
                    return;
                }

                if (serverSideExportPath) {
                    const requestUrl = new URL(String(serverSideExportPath), window.location.origin);
                    const searchTerm = String(queueSearchInput ? queueSearchInput.value : '').trim();
                    const fromDate = String(dateFilterFromInput ? dateFilterFromInput.value : '').trim();
                    const toDate = String(dateFilterToInput ? dateFilterToInput.value : '').trim();

                    if (searchTerm !== '') {
                        requestUrl.searchParams.set('search', searchTerm);
                    }
                    if (fromDate !== '') {
                        requestUrl.searchParams.set('fromDate', fromDate);
                    }
                    if (toDate !== '') {
                        requestUrl.searchParams.set('toDate', toDate);
                    }

                    if (bulkRowExportEnabled) {
                        sourceRows.forEach(function (row, index) {
                            const rowKey = getBulkRowSelectionKey(row, index);
                            if (rowKey !== '') {
                                requestUrl.searchParams.append('row_keys[]', rowKey);
                            }
                        });
                    }

                    try {
                        const response = await fetch(requestUrl.toString(), {
                            method: 'GET',
                            credentials: 'same-origin',
                            cache: 'no-store',
                            headers: {
                                'Accept': 'text/csv,application/json;q=0.9,*/*;q=0.8'
                            }
                        });

                        if (!response.ok) {
                            const contentType = String(response.headers.get('Content-Type') || '').toLowerCase();
                            let message = 'Unable to export rows right now.';
                            if (contentType.indexOf('application/json') !== -1) {
                                const payload = await response.json().catch(function () { return null; });
                                if (payload && payload.message) {
                                    message = String(payload.message);
                                }
                            } else {
                                const text = await response.text().catch(function () { return ''; });
                                if (String(text).trim() !== '') {
                                    message = String(text).trim();
                                }
                            }
                            showAlertDialog(message);
                            return;
                        }

                        const filename = parseContentDispositionFilename(response.headers.get('Content-Disposition'))
                            || ('super-admin-export-' + new Date().toISOString().slice(0, 10) + '.csv');
                        const blob = await response.blob();
                        downloadBlob(blob, filename);
                        return;
                    } catch (error) {
                        showAlertDialog(error && error.message ? error.message : 'Unable to export rows right now.');
                        return;
                    }
                }

                const csvEscape = function (value) {
                    const text = String(value || '');
                    if (text.indexOf(',') !== -1 || text.indexOf('"') !== -1 || text.indexOf('\n') !== -1) {
                        return '"' + text.replace(/"/g, '""') + '"';
                    }
                    return text;
                };

                const lines = [];
                if (headers.length > 0) {
                    lines.push(headers.map(csvEscape).join(','));
                }
                rows.forEach(function (row) {
                    lines.push(row.map(csvEscape).join(','));
                });

                const csvBlob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
                downloadBlob(csvBlob, 'DTMIS-export-' + new Date().toISOString().slice(0, 10) + '.csv');
            }

            function bindBulkRowSelection() {
                if (!bulkRowExportEnabled || !tableBody) {
                    return;
                }

                if (bulkSelectAllRows) {
                    bulkSelectAllRows.addEventListener('change', function () {
                        const shouldSelect = !!bulkSelectAllRows.checked;
                        const visibleRows = getBulkSelectableRows(false);
                        visibleRows.forEach(function (row, index) {
                            const rowKey = getBulkRowSelectionKey(row, index);
                            if (rowKey === '') {
                                return;
                            }
                            if (shouldSelect) {
                                selectedBulkRowKeys.add(rowKey);
                            } else {
                                selectedBulkRowKeys.delete(rowKey);
                            }
                            const checkbox = row.querySelector('.bulk-row-select');
                            if (checkbox) {
                                checkbox.checked = shouldSelect;
                            }
                        });
                        syncBulkSelectionUi();
                    });
                }

                tableBody.addEventListener('change', function (event) {
                    const target = event.target;
                    if (!(target instanceof HTMLInputElement) || !target.classList.contains('bulk-row-select')) {
                        return;
                    }
                    const row = target.closest('tr');
                    if (!row) {
                        return;
                    }
                    const rowIndex = Array.from(tableBody.querySelectorAll('tr')).indexOf(row);
                    const rowKey = getBulkRowSelectionKey(row, rowIndex);
                    if (rowKey === '') {
                        return;
                    }
                    if (target.checked) {
                        selectedBulkRowKeys.add(rowKey);
                    } else {
                        selectedBulkRowKeys.delete(rowKey);
                    }
                    syncBulkSelectionUi();
                });

                syncBulkSelectionUi();
            }

            function bindExportAction() {
                if (!exportLink) {
                    return;
                }
                exportLink.addEventListener('click', function (event) {
                    event.preventDefault();
                    void exportVisibleTableToCsv();
                });
            }

            function getFirstVisibleTableRow() {
                if (!tableBody) {
                    return null;
                }
                const rows = Array.from(tableBody.querySelectorAll('tr'));
                for (let i = 0; i < rows.length; i += 1) {
                    if (rows[i].style.display === 'none') {
                        continue;
                    }
                    return rows[i];
                }
                return null;
            }

            function getFirstVisibleActionButton(actionName) {
                const selectedRow = findQueueRowByTrackingId(selectedQueueTrackingId);
                if (selectedRow && selectedRow.style.display !== 'none') {
                    const selectedButton = selectedRow.querySelector('.quick-action-btn[data-action="' + actionName + '"]');
                    if (selectedButton) {
                        return selectedButton;
                    }
                }
                const rows = tableBody ? Array.from(tableBody.querySelectorAll('tr')) : [];
                for (let i = 0; i < rows.length; i += 1) {
                    if (rows[i].style.display === 'none') {
                        continue;
                    }
                    const button = rows[i].querySelector('.quick-action-btn[data-action="' + actionName + '"]');
                    if (button) {
                        return button;
                    }
                }
                return null;
            }

            function getFirstVisibleTrackingId() {
                const selectedRow = findQueueRowByTrackingId(selectedQueueTrackingId);
                if (selectedRow && selectedRow.style.display !== 'none') {
                    const selectedTracking = String(selectedRow.getAttribute('data-tracking-id') || '').trim();
                    if (selectedTracking !== '') {
                        return selectedTracking;
                    }
                }
                const row = getFirstVisibleTableRow();
                if (!row) {
                    return '';
                }
                return String(row.getAttribute('data-tracking-id') || '').trim();
            }

            function bindStickyActions() {
                if (!stickyActionButtons || stickyActionButtons.length === 0) {
                    return;
                }

                stickyActionButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const label = String(button.textContent || '').toLowerCase();
                        if (label.indexOf('search') !== -1 && queueSearchInput) {
                            queueSearchInput.focus();
                            return;
                        }
                        if ((label.indexOf('export') !== -1 || label.indexOf('generate') !== -1) && exportLink) {
                            void exportVisibleTableToCsv();
                            return;
                        }
                        if (label.indexOf('filter') !== -1 && dateFilterToggle) {
                            dateFilterToggle.click();
                            return;
                        }
                        if (label.indexOf('submit intake') !== -1 && intakeForm) {
                            intakeForm.requestSubmit();
                            return;
                        }
                        if (label.indexOf('save draft') !== -1 && intakeSaveDraftButton) {
                            intakeSaveDraftButton.click();
                            return;
                        }
                        if (label.indexOf('print package') !== -1) {
                            const trackingId = getFirstVisibleTrackingId();
                            if (trackingId === '') {
                                showAlertDialog('No document available to print.');
                                return;
                            }
                            window.open(printPackagePath + '?tracking_id=' + encodeURIComponent(trackingId) + '&autoprint=1', '_blank');
                            return;
                        }
                        if (label.indexOf('print') !== -1 || label.indexOf('slip') !== -1 || label.indexOf('qr') !== -1) {
                            const trackingId = getFirstVisibleTrackingId();
                            if (trackingId === '') {
                                showAlertDialog('No document available to print.');
                                return;
                            }
                            window.open(trackingSlipPath + '?tracking_id=' + encodeURIComponent(trackingId) + '&autoprint=1', '_blank');
                            return;
                        }
                        if (label.indexOf('receive') !== -1) {
                            const receiveButton = getFirstVisibleActionButton('RECEIVE');
                            if (receiveButton) {
                                receiveButton.click();
                                return;
                            }
                        }
                        if (label.indexOf('forward') !== -1) {
                            const forwardButton = getFirstVisibleActionButton('FORWARD');
                            if (forwardButton) {
                                forwardButton.click();
                                return;
                            }
                        }
                        if (label.indexOf('undo sign') !== -1 || label.indexOf('unsign') !== -1) {
                            const unsignButton = getFirstVisibleActionButton('UNSIGN');
                            if (unsignButton) {
                                unsignButton.click();
                                return;
                            }
                        }
                        if (label.indexOf('sign') !== -1) {
                            const signButton = getFirstVisibleActionButton('SIGN');
                            if (signButton) {
                                signButton.click();
                                return;
                            }
                        }
                        if (label.indexOf('pending') !== -1) {
                            const pendingButton = getFirstVisibleActionButton('PENDING');
                            if (pendingButton) {
                                pendingButton.click();
                                return;
                            }
                        }
                        if (label.indexOf('release') !== -1) {
                            const releaseButton = getFirstVisibleActionButton('RELEASE');
                            if (releaseButton) {
                                releaseButton.click();
                                return;
                            }
                        }
                        if (label.indexOf('reroute') !== -1) {
                            if (hideRerouteQuickAction) {
                                return;
                            }
                            const rerouteButton = getFirstVisibleActionButton('REROUTE');
                            if (rerouteButton) {
                                rerouteButton.click();
                                return;
                            }
                        }
                        if (label.indexOf('return') !== -1) {
                            const returnButton = getFirstVisibleActionButton('RETURN');
                            if (returnButton) {
                                returnButton.click();
                            }
                        }
                    });
                });
            }

            function buildSmoothPath(points) {
                if (!points || points.length === 0) {
                    return '';
                }
                if (points.length === 1) {
                    return 'M ' + points[0].x + ' ' + points[0].y;
                }

                let path = 'M ' + points[0].x + ' ' + points[0].y;
                for (let i = 0; i < points.length - 1; i += 1) {
                    const p0 = i > 0 ? points[i - 1] : points[i];
                    const p1 = points[i];
                    const p2 = points[i + 1];
                    const p3 = i !== points.length - 2 ? points[i + 2] : p2;

                    const cp1x = p1.x + ((p2.x - p0.x) / 6);
                    const cp1y = p1.y + ((p2.y - p0.y) / 6);
                    const cp2x = p2.x - ((p3.x - p1.x) / 6);
                    const cp2y = p2.y - ((p3.y - p1.y) / 6);

                    path += ' C ' + cp1x + ' ' + cp1y + ', ' + cp2x + ' ' + cp2y + ', ' + p2.x + ' ' + p2.y;
                }
                return path;
            }

            function renderWorkflowSplineChart() {
                if (!workflowSplineChart || !chartTrendData || !Array.isArray(chartTrendData.labels) || !Array.isArray(chartTrendData.series)) {
                    return;
                }

                const labels = chartTrendData.labels.slice();
                if (labels.length === 0) {
                    return;
                }

                const width = 620;
                const height = 220;
                const padding = { top: 14, right: 12, bottom: 24, left: 12 };
                const innerWidth = width - padding.left - padding.right;
                const innerHeight = height - padding.top - padding.bottom;
                const rootTheme = String(document.documentElement.getAttribute('data-theme') || '').toLowerCase();
                const dashboardStyles = window.getComputedStyle(document.documentElement);
                const guideColor = String(dashboardStyles.getPropertyValue('--dash-chart-grid') || '').trim()
                    || (rootTheme === 'dark' ? '#2f4760' : '#e4edf7');

                let maxValue = 0;
                chartTrendData.series.forEach(function (series) {
                    if (!Array.isArray(series.values)) {
                        return;
                    }
                    series.values.forEach(function (value) {
                        maxValue = Math.max(maxValue, Number(value || 0));
                    });
                });
                maxValue = Math.max(maxValue, 1);

                const horizontalGuideValues = [0, 0.25, 0.5, 0.75, 1];
                const guideMarkup = horizontalGuideValues.map(function (ratio) {
                    const y = padding.top + ((1 - ratio) * innerHeight);
                    return '<line x1="' + padding.left + '" y1="' + y + '" x2="' + (width - padding.right) + '" y2="' + y + '" stroke="' + guideColor + '" stroke-width="1" />';
                }).join('');

                function encodeSvgData(value) {
                    return String(value || '')
                        .replace(/&/g, '&amp;')
                        .replace(/"/g, '&quot;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                }

                const seriesMarkup = chartTrendData.series.map(function (series, seriesIndex) {
                    if (!Array.isArray(series.values) || series.values.length === 0) {
                        return '';
                    }

                    const points = series.values.map(function (rawValue, index) {
                        const value = Number(rawValue || 0);
                        const x = labels.length > 1
                            ? padding.left + ((index / (labels.length - 1)) * innerWidth)
                            : padding.left + (innerWidth / 2);
                        const y = padding.top + (innerHeight - ((value / maxValue) * innerHeight));
                        return { x: Number(x.toFixed(2)), y: Number(y.toFixed(2)) };
                    });

                    const pointMarkup = points.map(function (point, pointIndex) {
                        const pointValue = Number(series.values[pointIndex] || 0);
                        const previousValue = pointIndex > 0 ? Number(series.values[pointIndex - 1] || 0) : 0;
                        const pointLabel = labels[pointIndex] || '';
                        return '<circle cx="' + point.x + '" cy="' + point.y + '" r="2.6" fill="' + String(series.color || '#2f7de1') + '" data-series-index="' + seriesIndex + '" data-series-key="' + encodeSvgData(series.key || '') + '" data-series-label="' + encodeSvgData(series.label || '') + '" data-point-index="' + pointIndex + '" data-label="' + encodeSvgData(pointLabel) + '" data-value="' + pointValue + '" data-prev-value="' + previousValue + '" data-color="' + encodeSvgData(series.color || '#2f7de1') + '" />';
                    }).join('');

                    return '<path d="' + buildSmoothPath(points) + '" fill="none" stroke="' + String(series.color || '#2f7de1') + '" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />' + pointMarkup;
                }).join('');

                workflowSplineChart.innerHTML = '<g>' + guideMarkup + '</g><g>' + seriesMarkup + '</g>';
            }

            function bindNotificationActions() {
                if (!notifDropdown || !notifToggle) {
                    return;
                }

                const notifCount = notifToggle.querySelector('.notif-count');
                const notifList = notifDropdown.querySelector('.notif-list');
                const notifClear = notifDropdown.querySelector('.notif-clear');
                if (!notifList) {
                    return;
                }

                const pollIntervalMs = 3000;
                let highestSeenNotificationId = 0;
                let hasInitializedPoll = false;
                let pollTimerId = 0;
                let pollInFlight = false;
                let notificationSoundUnlocked = false;
                let pendingSoundCount = 0;
                const notificationAudio = notificationSoundPath ? new Audio(notificationSoundPath) : null;

                if (notificationAudio) {
                    notificationAudio.preload = 'auto';
                }

                function setNotifCount(unreadCount) {
                    if (!notifCount) {
                        return;
                    }
                    const safeCount = Math.max(0, Number(unreadCount || 0));
                    notifCount.textContent = String(safeCount);
                    notifCount.style.display = safeCount > 0 ? 'inline-flex' : 'none';
                }

                function getReadUntilTs() {
                    try {
                        const raw = String(localStorage.getItem(notificationReadKey) || '').trim();
                        const parsed = parseInt(raw, 10);
                        return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
                    } catch (error) {
                        return 0;
                    }
                }

                function setReadUntilTs(ts) {
                    try {
                        localStorage.setItem(notificationReadKey, String(ts));
                    } catch (error) {
                        // Ignore storage errors.
                    }
                }

                function getNotificationItems() {
                    return Array.from(notifList.querySelectorAll('.notif-item'));
                }

                function itemNotificationId(item) {
                    const raw = String(item ? (item.getAttribute('data-notification-id') || '') : '').trim();
                    const parsed = parseInt(raw, 10);
                    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
                }

                function notificationItemTimestamp(item) {
                    const timeEl = item.querySelector('time');
                    const datetimeAttr = String(item ? (item.getAttribute('data-datetime') || '') : '').trim();
                    const dateTimeRaw = datetimeAttr !== '' ? datetimeAttr : String(timeEl ? (timeEl.getAttribute('datetime') || '') : '').trim();
                    const ts = parseDateInputValue(dateTimeRaw, false);
                    return ts === null ? 0 : ts;
                }

                function applyNotificationReadState() {
                    const notifItems = getNotificationItems();
                    const readUntil = getReadUntilTs();
                    let unread = 0;
                    notifItems.forEach(function (item) {
                        const itemTs = notificationItemTimestamp(item);
                        const isUnread = itemTs > readUntil;
                        item.classList.toggle('is-unread', isUnread);
                        if (isUnread) {
                            unread += 1;
                        }
                    });
                    setNotifCount(unread);
                }

                function notificationIndicatorMeta(notification) {
                    const rawKey = normalizeLabelKey(notification && notification.indicator_key ? notification.indicator_key : '');
                    if (rawKey === 'yellow') {
                        return {
                            key: 'yellow',
                            className: 'is-yellow',
                            fullLabel: 'Yellow - Urgent',
                        };
                    }
                    if (rawKey === 'pink') {
                        return {
                            key: 'pink',
                            className: 'is-pink',
                            fullLabel: 'Pink - Simple',
                        };
                    }
                    if (rawKey === 'blue') {
                        return {
                            key: 'blue',
                            className: 'is-blue',
                            fullLabel: 'Blue - Complex/Highly Technical',
                        };
                    }
                    if (rawKey === 'green') {
                        return {
                            key: 'green',
                            className: 'is-green',
                            fullLabel: 'Green - Released',
                        };
                    }

                    const fallbackRaw = normalizeLabelKey(
                        String(notification && notification.indicator_label ? notification.indicator_label : '') + ' '
                        + String(notification && notification.title ? notification.title : '') + ' '
                        + String(notification && notification.message ? notification.message : '')
                    );
                    if (fallbackRaw.indexOf('yellow') !== -1 || fallbackRaw.indexOf('urgent') !== -1 || fallbackRaw.indexOf('overdue') !== -1) {
                        return {
                            key: 'yellow',
                            className: 'is-yellow',
                            fullLabel: 'Yellow - Urgent',
                        };
                    }
                    if (fallbackRaw.indexOf('blue') !== -1 || fallbackRaw.indexOf('complex') !== -1 || fallbackRaw.indexOf('highly technical') !== -1) {
                        return {
                            key: 'blue',
                            className: 'is-blue',
                            fullLabel: 'Blue - Complex/Highly Technical',
                        };
                    }
                    if (fallbackRaw.indexOf('green') !== -1 || fallbackRaw.indexOf('released') !== -1) {
                        return {
                            key: 'green',
                            className: 'is-green',
                            fullLabel: 'Green - Released',
                        };
                    }
                    if (fallbackRaw.indexOf('pink') !== -1 || fallbackRaw.indexOf('simple') !== -1) {
                        return {
                            key: 'pink',
                            className: 'is-pink',
                            fullLabel: 'Pink - Simple',
                        };
                    }

                    return {
                        key: 'neutral',
                        className: 'is-neutral',
                        fullLabel: 'Indicator',
                    };
                }

                function renderNotificationItems(items) {
                    notifList.innerHTML = '';
                    if (!Array.isArray(items) || items.length === 0) {
                        const emptyState = document.createElement('article');
                        emptyState.className = 'notif-empty';
                        emptyState.textContent = 'No notifications yet.';
                        notifList.appendChild(emptyState);
                        setNotifCount(0);
                        return 0;
                    }

                    let latestId = 0;
                    items.forEach(function (notification) {
                        const id = Math.max(0, Number(notification && notification.id ? notification.id : 0));
                        latestId = Math.max(latestId, id);

                        const title = String(notification && notification.title ? notification.title : 'Update');
                        const message = String(notification && notification.message ? notification.message : '');
                        const datetime = String(notification && notification.datetime ? notification.datetime : '').trim();
                        const timeLabel = String(notification && notification.timeLabel ? notification.timeLabel : 'Now');
                        const trackingId = String(notification && notification.tracking_id ? notification.tracking_id : '').trim();
                        const indicatorMeta = notificationIndicatorMeta(notification);
                        const href = trackingId !== ''
                            ? trackingSlipPath + '?tracking_id=' + encodeURIComponent(trackingId)
                            : notificationsPagePath;

                        const article = document.createElement('article');
                        article.className = 'notif-item is-unread';
                        article.setAttribute('data-notification-id', String(id));
                        article.setAttribute('data-datetime', datetime);
                        article.setAttribute('data-tracking-id', trackingId);
                        article.setAttribute('data-indicator-key', indicatorMeta.key);

                        const link = document.createElement('a');
                        link.className = 'notif-item-link';
                        link.href = href;

                        const indicator = document.createElement('span');
                        indicator.className = 'notif-item-indicator ' + indicatorMeta.className;
                        indicator.title = indicatorMeta.fullLabel;
                        indicator.setAttribute('aria-label', indicatorMeta.fullLabel);
                        link.appendChild(indicator);

                        const copy = document.createElement('div');
                        copy.className = 'notif-item-copy';

                        const heading = document.createElement('h3');
                        heading.textContent = title;
                        copy.appendChild(heading);

                        const paragraph = document.createElement('p');
                        paragraph.textContent = message;
                        copy.appendChild(paragraph);

                        const time = document.createElement('time');
                        time.setAttribute('datetime', datetime);
                        time.textContent = timeLabel;
                        copy.appendChild(time);

                        link.appendChild(copy);

                        article.appendChild(link);
                        notifList.appendChild(article);
                    });

                    return latestId;
                }

                function updateHighestSeenFromDom() {
                    const notifItems = getNotificationItems();
                    let latestId = 0;
                    notifItems.forEach(function (item) {
                        latestId = Math.max(latestId, itemNotificationId(item));
                    });
                    highestSeenNotificationId = Math.max(highestSeenNotificationId, latestId);
                }

                function playNotificationSound() {
                    if (!notificationAudio || !notificationSoundUnlocked) {
                        return;
                    }
                    try {
                        notificationAudio.currentTime = 0;
                        const playPromise = notificationAudio.play();
                        if (playPromise && typeof playPromise.catch === 'function') {
                            playPromise.catch(function () {
                                // Ignore browser autoplay restrictions.
                            });
                        }
                    } catch (error) {
                        // Ignore browser autoplay restrictions.
                    }
                }

                function playNotificationSoundBurst(count) {
                    const safeCount = Math.max(0, Math.min(6, Number(count || 0)));
                    if (safeCount <= 0) {
                        return;
                    }
                    if (!notificationSoundUnlocked) {
                        pendingSoundCount = Math.min(12, pendingSoundCount + safeCount);
                        return;
                    }
                    for (let index = 0; index < safeCount; index += 1) {
                        window.setTimeout(function () {
                            playNotificationSound();
                        }, index * 260);
                    }
                }

                function unlockNotificationSound() {
                    notificationSoundUnlocked = true;
                    if (pendingSoundCount > 0) {
                        const queuedCount = pendingSoundCount;
                        pendingSoundCount = 0;
                        playNotificationSoundBurst(queuedCount);
                    }
                }

                function pollNotifications() {
                    if (!notificationFeedPath || pollInFlight) {
                        return;
                    }
                    pollInFlight = true;

                    const requestUrl = notificationFeedPath + '?limit=12&t=' + String(Date.now());
                    fetch(requestUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: { 'Accept': 'application/json' }
                    }).then(function (response) {
                        if (!response.ok) {
                            throw new Error('Unable to fetch notifications.');
                        }
                        return response.json();
                    }).then(function (payload) {
                        if (!payload || payload.ok !== true || !Array.isArray(payload.notifications)) {
                            return;
                        }

                        const previousLatest = highestSeenNotificationId;
                        let newNotificationCount = 0;
                        if (hasInitializedPoll) {
                            payload.notifications.forEach(function (notification) {
                                const id = Math.max(0, Number(notification && notification.id ? notification.id : 0));
                                if (id > previousLatest) {
                                    newNotificationCount += 1;
                                }
                            });
                        }
                        const latestFromServer = Math.max(0, Number(payload.newest_id || 0));
                        const latestRendered = renderNotificationItems(payload.notifications);
                        const latestIncoming = Math.max(latestFromServer, latestRendered);
                        highestSeenNotificationId = Math.max(highestSeenNotificationId, latestIncoming);
                        applyNotificationReadState();

                        if (hasInitializedPoll && newNotificationCount > 0) {
                            playNotificationSoundBurst(newNotificationCount);
                        }
                    }).catch(function () {
                        // Keep notification UI functional even if polling fails.
                    }).finally(function () {
                        pollInFlight = false;
                        hasInitializedPoll = true;
                    });
                }

                updateHighestSeenFromDom();
                applyNotificationReadState();

                if (notifClear) {
                    notifClear.addEventListener('click', function () {
                        setReadUntilTs(Date.now());
                        applyNotificationReadState();
                    });
                }

                notifList.addEventListener('click', function (event) {
                    const item = event.target.closest('.notif-item');
                    if (!item || !notifList.contains(item)) {
                        return;
                    }

                    const itemTs = notificationItemTimestamp(item);
                    if (itemTs > 0) {
                        const readUntil = getReadUntilTs();
                        if (itemTs > readUntil) {
                            setReadUntilTs(itemTs);
                        }
                    } else {
                        setReadUntilTs(Date.now());
                    }
                    applyNotificationReadState();
                });

                document.addEventListener('pointerdown', unlockNotificationSound, { once: true });
                document.addEventListener('keydown', unlockNotificationSound, { once: true });

                pollNotifications();
                pollTimerId = window.setInterval(function () {
                    if (!document.hidden) {
                        pollNotifications();
                    }
                }, pollIntervalMs);

                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) {
                        pollNotifications();
                    }
                });

                window.addEventListener('beforeunload', function () {
                    if (pollTimerId > 0) {
                        window.clearInterval(pollTimerId);
                    }
                });
            }

            function customTypeDefaultsForCategory(category) {
                const normalized = String(category || '').trim().toLowerCase();
                if (normalized === 'complex') {
                    return { days: 7, category: 'Complex' };
                }
                if (normalized === 'highly technical' || normalized === 'highly_technical' || normalized === 'highly-technical') {
                    return { days: 20, category: 'Highly Technical' };
                }
                if (normalized === 'others' || normalized === 'other') {
                    return { days: 3, category: 'Others' };
                }
                return { days: 3, category: 'Simple' };
            }

            function isOthersDocumentTypeSelected() {
                if (!intakeDocumentType) {
                    return false;
                }
                const selectedOption = intakeDocumentType.selectedOptions && intakeDocumentType.selectedOptions.length > 0
                    ? intakeDocumentType.selectedOptions[0]
                    : null;
                if (!selectedOption) {
                    return false;
                }
                return String(selectedOption.getAttribute('data-is-others') || '0') === '1';
            }

            function autoSelectOthersDocumentType(customTypeName) {
                if (!intakeDocumentType) {
                    return false;
                }
                const normalizedCustomType = String(customTypeName || '').trim();
                if (normalizedCustomType === '') {
                    return false;
                }
                if (isOthersDocumentTypeSelected()) {
                    return true;
                }
                const selectedDocumentTypeId = String(intakeDocumentType.value || '').trim();
                if (selectedDocumentTypeId !== '') {
                    return false;
                }
                const othersOption = Array.from(intakeDocumentType.options || []).find(function (option) {
                    if (!option) {
                        return false;
                    }
                    const optionValue = String(option.value || '').trim();
                    if (optionValue === '') {
                        return false;
                    }
                    return String(option.getAttribute('data-is-others') || '0') === '1';
                });
                if (!othersOption) {
                    return false;
                }
                intakeDocumentType.value = String(othersOption.value || '').trim();
                intakeDocumentType.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            }

            function toggleOtherDocumentTypeField() {
                if (!intakeOtherDocumentTypeField || !customDocumentTypeNameInput) {
                    return;
                }
                const shouldShow = isOthersDocumentTypeSelected();
                intakeOtherDocumentTypeField.hidden = !shouldShow;
                customDocumentTypeNameInput.required = shouldShow;
            }

            function syncCustomTypeDefaults(forceDays) {
                if (!intakeComplexityTypeInput || !intakeComplexityDaysInput) {
                    return;
                }
                const defaults = customTypeDefaultsForCategory(intakeComplexityTypeInput.value);
                intakeComplexityTypeInput.value = defaults.category;
                if (forceDays || String(intakeComplexityDaysInput.value || '').trim() === '') {
                    intakeComplexityDaysInput.value = String(defaults.days);
                }
            }

            function syncColorClassificationFromComplexity() {
                if (!intakeComplexityTypeInput || !intakeColorClassificationInput) {
                    return;
                }

                const normalizedComplexity = String(intakeComplexityTypeInput.value || '').trim().toLowerCase();
                if (normalizedComplexity === 'complex' || normalizedComplexity === 'highly technical') {
                    intakeColorClassificationInput.value = 'BLUE_COMPLEX_HIGHLY_TECHNICAL';
                    return;
                }
                if (normalizedComplexity === 'others' || normalizedComplexity === 'other') {
                    intakeColorClassificationInput.value = 'YELLOW_URGENT';
                    return;
                }
                intakeColorClassificationInput.value = 'PINK_SIMPLE';
            }

            function syncComplexityFromDocumentType(forceDays) {
                if (!intakeDocumentType || !intakeComplexityTypeInput) {
                    syncCustomTypeDefaults(forceDays);
                    syncColorClassificationFromComplexity();
                    return;
                }

                const selectedOption = intakeDocumentType.selectedOptions && intakeDocumentType.selectedOptions.length > 0
                    ? intakeDocumentType.selectedOptions[0]
                    : null;
                const isOthersSelection = selectedOption
                    ? String(selectedOption.getAttribute('data-is-others') || '0') === '1'
                    : false;
                if (isOthersSelection) {
                    syncCustomTypeDefaults(forceDays);
                    syncColorClassificationFromComplexity();
                    return;
                }
                const docCategory = String(selectedOption ? (selectedOption.getAttribute('data-category') || '') : '').trim();
                const currentCategory = String(intakeComplexityTypeInput.value || '').trim();
                if (docCategory !== '') {
                    const resolvedDefaults = customTypeDefaultsForCategory(docCategory);
                    const currentDaysRaw = String(intakeComplexityDaysInput ? (intakeComplexityDaysInput.value || '') : '').trim();
                    const nextCategory = resolvedDefaults.category;
                    const shouldResetDays = forceDays
                        || currentDaysRaw === ''
                        || currentCategory !== nextCategory;

                    intakeComplexityTypeInput.value = nextCategory;
                    if (intakeComplexityDaysInput && shouldResetDays) {
                        intakeComplexityDaysInput.value = String(resolvedDefaults.days);
                    }
                }
                syncCustomTypeDefaults(forceDays);
                syncColorClassificationFromComplexity();
            }

            function closeCreateIntakeModal() {
                if (!createIntakeModal) {
                    return;
                }
                createIntakeModal.classList.remove('is-open');
                createIntakeModal.hidden = true;
                document.body.classList.remove('modal-open');
            }

            function openCreateIntakeModal() {
                if (!createIntakeModal || !intakeForm) {
                    showAlertDialog('Create intake modal is not available on this page.');
                    return;
                }

                createIntakeModal.hidden = false;
                createIntakeModal.classList.add('is-open');
                document.body.classList.add('modal-open');
                syncIntakeSenderSource();
                toggleOtherDocumentTypeField();
                syncComplexityFromDocumentType(false);

                const preferredFocusNode = document.getElementById('intakeSubject');
                if (preferredFocusNode) {
                    preferredFocusNode.focus();
                }
            }

            function bindCreateIntakeModal() {
                if (!createIntakeModal || !intakeForm || !openCreateIntakeModalBtn) {
                    return;
                }

                openCreateIntakeModalBtn.addEventListener('click', function () {
                    openCreateIntakeModal();
                });

                createIntakeCloseButtons.forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        if ((intakeSubmitButton && intakeSubmitButton.disabled) || (intakeSubmitForwardButton && intakeSubmitForwardButton.disabled)) {
                            return;
                        }
                        closeCreateIntakeModal();
                    });
                });

                document.addEventListener('keydown', function (event) {
                    if (
                        event.key === 'Escape'
                        && createIntakeModal
                        && !createIntakeModal.hidden
                        && !(intakeSubmitButton && intakeSubmitButton.disabled)
                        && !(intakeSubmitForwardButton && intakeSubmitForwardButton.disabled)
                    ) {
                        closeCreateIntakeModal();
                    }
                });
            }

            function bindIntakeForm() {
                if (!intakeForm) {
                    return;
                }

                const draftKey = 'DTMIS_intake_draft_' + String(<?= json_encode($roleFolder) ?>).toLowerCase();
                let intakeSubmitMode = 'submit';
                function syncIntakeSenderSource() {
                    if (!intakeExternalClient) {
                        return;
                    }

                    const sourceTypeValue = String(intakeSourceTypeInput ? intakeSourceTypeInput.value : 'INTERNAL').trim().toUpperCase();
                    const isClientSource = sourceTypeValue === 'EXTERNAL';
                    const routingOfficeValue = String(
                        (intakeRoutingOfficeInput && intakeRoutingOfficeInput.value) || currentOfficeName || ''
                    ).trim();

                    if (intakeSenderLabel) {
                        intakeSenderLabel.textContent = isClientSource ? 'Client Name' : 'Sender Name';
                    }
                    if (intakeSenderHint) {
                        intakeSenderHint.textContent = isClientSource
                            ? 'Required when sender source is Client. Shown on the tracking slip above Encoder.'
                            : 'Enter the sender name from the current office. Shown on the tracking slip above Encoder.';
                    }

                    intakeExternalClient.readOnly = false;
                    intakeExternalClient.required = true;
                    intakeExternalClient.placeholder = isClientSource ? 'Enter client name' : 'Enter sender name';
                    if (intakeClientAddressField && intakeClientAddressInput) {
                        intakeClientAddressField.hidden = false;
                        intakeClientAddressInput.hidden = false;
                        intakeClientAddressInput.required = isClientSource;
                        intakeClientAddressInput.placeholder = isClientSource ? 'Enter client address' : 'Enter office address';
                        if (intakeClientAddressLabel) {
                            intakeClientAddressLabel.textContent = isClientSource ? 'Client Address' : 'Office Address';
                        }
                        if (intakeClientAddressHint) {
                            intakeClientAddressHint.textContent = isClientSource
                                ? 'Required for client source. Shown in the tracking slip Address field.'
                                : 'Optional. Leave blank to use the originating office on the tracking slip.';
                        }
                    }
                    if (intakeOriginatingEntityInput) {
                        intakeOriginatingEntityInput.required = true;
                        intakeOriginatingEntityInput.placeholder = isClientSource
                            ? 'Enter client or external originating entity'
                            : 'Enter originating office or unit';
                        if (intakeOriginatingEntityLabel) {
                            intakeOriginatingEntityLabel.textContent = 'Originating Office / Entity';
                        }
                        if (intakeOriginatingEntityHint) {
                            intakeOriginatingEntityHint.textContent = isClientSource
                                ? 'Required for client source. Shown on the tracking slip and print package.'
                                : 'Prefilled from your DENR office, but you can edit the display value.';
                        }
                        if (!isClientSource && String(intakeOriginatingEntityInput.value || '').trim() === '') {
                            intakeOriginatingEntityInput.value = routingOfficeValue;
                        }
                        if (isClientSource && String(intakeOriginatingEntityInput.value || '').trim() === routingOfficeValue) {
                            intakeOriginatingEntityInput.value = '';
                        }
                    }
                }

                function resolveForwardQueuePath(trackingId) {
                    let basePath = '';
                    if (statCardNavigationTargets && typeof statCardNavigationTargets === 'object') {
                        const mappedPath = String(statCardNavigationTargets.forward || statCardNavigationTargets.default || '').trim();
                        if (mappedPath !== '') {
                            basePath = mappedPath;
                        }
                    }
                    if (basePath === '') {
                        basePath = window.location.pathname;
                    }
                    try {
                        const target = new URL(basePath, window.location.origin);
                        const normalizedTracking = String(trackingId || '').trim();
                        if (normalizedTracking !== '') {
                            target.searchParams.set('tracking_id', normalizedTracking);
                        }
                        target.searchParams.set('auto_action', 'forward');
                        return target.toString();
                    } catch (error) {
                        return String(basePath || window.location.href);
                    }
                }

                if (intakeSubmitButton) {
                    intakeSubmitButton.addEventListener('click', function () {
                        intakeSubmitMode = 'submit';
                    });
                }
                if (intakeSubmitForwardButton) {
                    intakeSubmitForwardButton.addEventListener('click', function () {
                        intakeSubmitMode = 'forward';
                        intakeForm.requestSubmit();
                    });
                }

                toggleOtherDocumentTypeField();
                syncIntakeSenderSource();

                if (intakeSourceTypeInput) {
                    intakeSourceTypeInput.addEventListener('change', syncIntakeSenderSource);
                }

                if (intakeDocumentType) {
                    intakeDocumentType.addEventListener('change', function () {
                        toggleOtherDocumentTypeField();
                        syncComplexityFromDocumentType(true);
                    });
                }
                if (intakeComplexityTypeInput) {
                    intakeComplexityTypeInput.addEventListener('change', function () {
                        syncCustomTypeDefaults(true);
                        syncColorClassificationFromComplexity();
                    });
                }
                if (intakeComplexityTypeInput) {
                    intakeComplexityTypeInput.addEventListener('change', function () {
                        syncCustomTypeDefaults(true);
                    });
                }
                syncComplexityFromDocumentType(false);

                if (intakeDestinationOffice) {
                    intakeDestinationOffice.disabled = true;
                }

                if (intakeSaveDraftButton) {
                    intakeSaveDraftButton.addEventListener('click', function () {
                        const formData = new FormData(intakeForm);
                        const draftPayload = {};
                        formData.forEach(function (value, key) {
                            if (value instanceof File) {
                                return;
                            }
                            draftPayload[key] = String(value);
                        });
                        localStorage.setItem(draftKey, JSON.stringify(draftPayload));
                        showAlertDialog('Draft saved locally on this browser.');
                    });
                }

                try {
                    const savedDraftRaw = localStorage.getItem(draftKey);
                    if (savedDraftRaw) {
                        const savedDraft = JSON.parse(savedDraftRaw);
                        Object.keys(savedDraft).forEach(function (key) {
                            const field = intakeForm.querySelector('[name="' + key + '"]');
                            if (field && typeof savedDraft[key] === 'string') {
                                field.value = savedDraft[key];
                            }
                        });
                        syncIntakeSenderSource();
                        toggleOtherDocumentTypeField();
                        syncComplexityFromDocumentType(false);
                    }
                } catch (error) {
                    // Ignore invalid local draft payload.
                }

                intakeForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    const submitMode = intakeSubmitMode === 'forward' ? 'forward' : 'submit';
                    const isSubmitAndForward = submitMode === 'forward';
                    intakeSubmitMode = 'submit';
                    const formData = new FormData(intakeForm);
                    formData.set('csrf_token', csrfToken);

                    const sourceTypeValue = String(intakeSourceTypeInput ? intakeSourceTypeInput.value : 'INTERNAL').trim().toUpperCase();
                    const senderValue = String(intakeExternalClient ? intakeExternalClient.value : '').trim();
                    const clientAddressValue = String(intakeClientAddressInput ? intakeClientAddressInput.value : '').trim();
                    const originatingEntityValue = String(intakeOriginatingEntityInput ? intakeOriginatingEntityInput.value : '').trim();
                    if (senderValue === '') {
                        showAlertDialog(sourceTypeValue === 'EXTERNAL'
                            ? 'Please enter the client name.'
                            : 'Please enter the sender name.'
                        );
                        if (intakeExternalClient) {
                            intakeExternalClient.focus();
                        }
                        return;
                    }
                    if (originatingEntityValue === '') {
                        showAlertDialog('Please enter the originating office / entity.');
                        if (intakeOriginatingEntityInput) {
                            intakeOriginatingEntityInput.focus();
                        }
                        return;
                    }
                    if (sourceTypeValue === 'EXTERNAL' && clientAddressValue === '') {
                        showAlertDialog('Please enter the client address.');
                        if (intakeClientAddressInput) {
                            intakeClientAddressInput.focus();
                        }
                        return;
                    }
                    formData.set('source_type', sourceTypeValue === 'EXTERNAL' ? 'EXTERNAL' : 'INTERNAL');
                    formData.set('sender', senderValue);
                    formData.set('external_client_name', sourceTypeValue === 'EXTERNAL' ? senderValue : '');
                    formData.set('client_address', clientAddressValue);
                    formData.set('originating_entity_name', originatingEntityValue);

                    const remarksText = String(intakeRemarksInput ? intakeRemarksInput.value : '').trim();
                    const selectedFiles = intakeAttachmentsInput && intakeAttachmentsInput.files
                        ? Array.from(intakeAttachmentsInput.files)
                        : [];
                    const selectedAttachmentCount = selectedFiles.length;
                    const hasAttachment = selectedAttachmentCount > 0;
                    formData.set('attachment_count_expected', String(selectedAttachmentCount));
                    if (!hasAttachment && remarksText === '') {
                        showAlertDialog('Soft copy not uploaded. Please provide remarks explaining why no digital file was attached.');
                        if (intakeRemarksInput) {
                            intakeRemarksInput.focus();
                        }
                        return;
                    }

                    const selectedDocumentTypeId = String(intakeDocumentType ? intakeDocumentType.value : '').trim();
                    const customTypeName = String(customDocumentTypeNameInput ? customDocumentTypeNameInput.value : '').trim();
                    if (selectedDocumentTypeId === '' && customTypeName !== '') {
                        autoSelectOthersDocumentType(customTypeName);
                    }
                    const resolvedDocumentTypeId = String(intakeDocumentType ? intakeDocumentType.value : '').trim();
                    const isImplicitOthersSubmission = resolvedDocumentTypeId === '' && customTypeName !== '';
                    if (resolvedDocumentTypeId === '' && !isImplicitOthersSubmission) {
                        showAlertDialog('Please select a document type.');
                        if (intakeDocumentType) {
                            focusSearchableSelect(intakeDocumentType, false);
                        }
                        return;
                    }
                    if (customTypeName !== '' && resolvedDocumentTypeId !== '' && !isOthersDocumentTypeSelected()) {
                        showAlertDialog('Use the Others document type before entering a custom document type.');
                        if (intakeDocumentType) {
                            focusSearchableSelect(intakeDocumentType, false);
                        }
                        return;
                    }
                    if (resolvedDocumentTypeId !== '' && isOthersDocumentTypeSelected() && customTypeName === '') {
                        showAlertDialog('Please specify the document type under Others.');
                        if (customDocumentTypeNameInput) {
                            customDocumentTypeNameInput.focus();
                        }
                        return;
                    }
                    formData.set('custom_document_type_name', customTypeName);

                    if (intakeComplexityTypeInput) {
                        const normalizedComplexity = customTypeDefaultsForCategory(intakeComplexityTypeInput.value).category;
                        intakeComplexityTypeInput.value = normalizedComplexity;
                        formData.set('intake_complexity_type', normalizedComplexity);
                    }

                    if (intakeComplexityDaysInput) {
                        let resolvedDaysRaw = String(intakeComplexityDaysInput.value || '').trim();
                        if (resolvedDaysRaw === '') {
                            const categoryForDefault = intakeComplexityTypeInput ? intakeComplexityTypeInput.value : 'Simple';
                            resolvedDaysRaw = String(customTypeDefaultsForCategory(categoryForDefault).days);
                            intakeComplexityDaysInput.value = resolvedDaysRaw;
                        }
                        const resolvedDays = Number.parseInt(resolvedDaysRaw, 10);
                        if (!Number.isFinite(resolvedDays) || resolvedDays < 1 || resolvedDays > 365) {
                            showAlertDialog('Days must be a whole number from 1 to 365.');
                            intakeComplexityDaysInput.focus();
                            return;
                        }
                        formData.set('intake_complexity_days', String(resolvedDays));
                    }

                    const submitBtn = intakeSubmitButton || null;
                    const submitForwardBtn = intakeSubmitForwardButton || null;
                    showConfirmDialog(
                        isSubmitAndForward
                            ? 'Submit this intake and open the forward queue now?'
                            : 'Submit this intake and generate a tracking record now?',
                        'Confirm Intake Submission',
                        isSubmitAndForward ? 'Submit & Forward' : 'Submit'
                    ).then(function (confirmed) {
                        if (!confirmed) {
                            return;
                        }

                        if (submitBtn) {
                            submitBtn.disabled = true;
                        }
                        if (submitForwardBtn) {
                            submitForwardBtn.disabled = true;
                        }

                        fetch(createDocumentPath, {
                            method: 'POST',
                            body: formData,
                        }).then(function (response) {
                            return response.json().catch(function () {
                                return { ok: false, message: 'Unexpected server response.' };
                            }).then(function (json) {
                                if (!response.ok || !json.ok) {
                                    throw new Error(json.message || 'Unable to submit intake.');
                                }
                                return json;
                            });
                        }).then(function (json) {
                            const queuedOffline = !!(json && json.queued_offline);
                            if (queuedOffline) {
                                localStorage.removeItem(draftKey);
                                intakeForm.reset();
                                syncIntakeSenderSource();
                                toggleOtherDocumentTypeField();
                                syncComplexityFromDocumentType(false);
                                closeCreateIntakeModal();
                                showAlertDialog(json.message || 'Offline mode: intake was queued and will sync when connection returns.').then(function () {
                                    if (window.DTMISOfflineOutbox && typeof window.DTMISOfflineOutbox.requestSync === 'function') {
                                        window.DTMISOfflineOutbox.requestSync();
                                    }
                                });
                                return;
                            }

                            localStorage.removeItem(draftKey);
                            const trackingId = String(json.tracking_id || '').trim();
                            if (isSubmitAndForward) {
                                const forwardQueuePath = resolveForwardQueuePath(trackingId);
                                showAlertDialog('Intake submitted successfully. Opening forward queue for ' + (trackingId !== '' ? trackingId : 'new record') + '.').then(function () {
                                    window.location.href = forwardQueuePath;
                                });
                                return;
                            }
                            showAlertDialog('Intake submitted successfully. Tracking ID: ' + (trackingId !== '' ? trackingId : 'Generated')).then(function () {
                                if (json.tracking_slip_url) {
                                    window.location.href = String(json.tracking_slip_url);
                                    return;
                                }
                                window.location.reload();
                            });
                        }).catch(function (error) {
                            showAlertDialog(error.message || 'Failed to submit intake.');
                        }).finally(function () {
                            if (submitBtn) {
                                submitBtn.disabled = false;
                            }
                            if (submitForwardBtn) {
                                submitForwardBtn.disabled = false;
                            }
                        });
                    });
                });
            }

            function triggerAutoForwardFromQuery() {
                if (!tableBody) {
                    return;
                }
                let parsed;
                try {
                    parsed = new URL(window.location.href);
                } catch (error) {
                    return;
                }
                const autoAction = normalizeLabelKey(parsed.searchParams.get('auto_action') || '');
                if (autoAction !== 'forward') {
                    return;
                }
                const trackingId = String(parsed.searchParams.get('tracking_id') || '').trim().toUpperCase();
                if (trackingId === '') {
                    return;
                }

                const rows = Array.from(tableBody.querySelectorAll('tr'));
                const matchedRow = rows.find(function (row) {
                    const rowTrackingId = String(row.getAttribute('data-tracking-id') || '').trim().toUpperCase();
                    return rowTrackingId === trackingId;
                });
                if (!matchedRow) {
                    return;
                }

                const forwardButton = matchedRow.querySelector('button.quick-action-btn[data-action="forward"]');
                if (!forwardButton || typeof forwardButton.click !== 'function') {
                    return;
                }

                parsed.searchParams.delete('auto_action');
                try {
                    const nextQuery = parsed.searchParams.toString();
                    const nextUrl = parsed.pathname + (nextQuery !== '' ? '?' + nextQuery : '') + parsed.hash;
                    window.history.replaceState({}, document.title, nextUrl);
                } catch (error) {
                    // Ignore URL cleanup errors.
                }

                window.setTimeout(function () {
                    forwardButton.click();
                }, 120);
            }

            function isDesktopSidebarResizeAllowed() {
                return !window.matchMedia('(max-width: 900px)').matches;
            }

            function clampSidebarWidth(width) {
                const numeric = Number(width);
                if (!Number.isFinite(numeric)) {
                    return SIDEBAR_DEFAULT_WIDTH;
                }
                return Math.max(SIDEBAR_MIN_WIDTH, Math.min(SIDEBAR_MAX_WIDTH, Math.round(numeric)));
            }

            function setSidebarWidth(widthPx) {
                if (!dashboardShell) {
                    return SIDEBAR_DEFAULT_WIDTH;
                }
                const clamped = clampSidebarWidth(widthPx);
                dashboardShell.style.setProperty('--sidebar-width', String(clamped) + 'px');
                return clamped;
            }

            function getSidebarWidthFromStorage() {
                const stored = parseInt(String(localStorage.getItem(SIDEBAR_WIDTH_KEY) || ''), 10);
                if (!Number.isFinite(stored) || stored <= 0) {
                    return SIDEBAR_DEFAULT_WIDTH;
                }
                return clampSidebarWidth(stored);
            }

            function persistSidebarWidth(widthPx) {
                localStorage.setItem(SIDEBAR_WIDTH_KEY, String(clampSidebarWidth(widthPx)));
            }

            function ensureSidebarResizeHandle() {
                if (!sidebarHost) {
                    return null;
                }
                let handle = sidebarHost.querySelector('.sidebar-resize-handle');
                if (handle) {
                    return handle;
                }

                handle = document.createElement('button');
                handle.type = 'button';
                handle.className = 'sidebar-resize-handle';
                handle.setAttribute('aria-label', 'Resize sidebar');
                handle.title = 'Drag to resize sidebar';
                sidebarHost.appendChild(handle);
                return handle;
            }

            function stopSidebarResize() {
                if (!isSidebarResizing) {
                    return;
                }
                isSidebarResizing = false;
                if (dashboardShell) {
                    dashboardShell.classList.remove('is-resizing');
                }
                document.removeEventListener('mousemove', onSidebarResizeMove);
                document.removeEventListener('mouseup', onSidebarResizeEnd);
            }

            function onSidebarResizeMove(event) {
                if (!isSidebarResizing || !isDesktopSidebarResizeAllowed()) {
                    return;
                }
                const deltaX = Number(event.clientX || 0) - sidebarResizeStartX;
                const nextWidth = sidebarResizeStartWidth + deltaX;
                setSidebarWidth(nextWidth);
            }

            function onSidebarResizeEnd() {
                if (!isSidebarResizing) {
                    return;
                }
                const appliedWidth = parseInt(String((dashboardShell && getComputedStyle(dashboardShell).getPropertyValue('--sidebar-width')) || SIDEBAR_DEFAULT_WIDTH), 10);
                persistSidebarWidth(appliedWidth);
                stopSidebarResize();
            }

            function bindSidebarResize() {
                if (!dashboardShell || !sidebarHost) {
                    return;
                }

                const initialWidth = getSidebarWidthFromStorage();
                setSidebarWidth(initialWidth);

                sidebarResizeHandle = ensureSidebarResizeHandle();
                if (!sidebarResizeHandle) {
                    return;
                }

                sidebarResizeHandle.addEventListener('mousedown', function (event) {
                    if (!isDesktopSidebarResizeAllowed() || dashboardShell.classList.contains('is-collapsed')) {
                        return;
                    }
                    event.preventDefault();
                    isSidebarResizing = true;
                    sidebarResizeStartX = Number(event.clientX || 0);
                    sidebarResizeStartWidth = sidebarHost.getBoundingClientRect().width;
                    dashboardShell.classList.add('is-resizing');
                    document.addEventListener('mousemove', onSidebarResizeMove);
                    document.addEventListener('mouseup', onSidebarResizeEnd);
                });

                sidebarResizeHandle.addEventListener('dblclick', function () {
                    if (!isDesktopSidebarResizeAllowed() || dashboardShell.classList.contains('is-collapsed')) {
                        return;
                    }
                    const resetWidth = setSidebarWidth(SIDEBAR_DEFAULT_WIDTH);
                    persistSidebarWidth(resetWidth);
                });

                sidebarResizeHandle.addEventListener('keydown', function (event) {
                    if (!isDesktopSidebarResizeAllowed() || dashboardShell.classList.contains('is-collapsed')) {
                        return;
                    }
                    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                        return;
                    }
                    event.preventDefault();
                    const currentWidth = parseInt(String(getComputedStyle(dashboardShell).getPropertyValue('--sidebar-width') || SIDEBAR_DEFAULT_WIDTH), 10);
                    const step = event.key === 'ArrowRight' ? 16 : -16;
                    const nextWidth = setSidebarWidth(currentWidth + step);
                    persistSidebarWidth(nextWidth);
                });

                window.addEventListener('resize', function () {
                    if (!isDesktopSidebarResizeAllowed()) {
                        stopSidebarResize();
                    }
                });

                window.addEventListener('blur', function () {
                    stopSidebarResize();
                });
            }

            function applySidebarState(isCollapsed) {
                if (!dashboardShell || !sidebarToggle) {
                    return;
                }
                dashboardShell.classList.toggle('is-collapsed', isCollapsed);
                sidebarToggle.setAttribute('aria-expanded', String(!isCollapsed));
                sidebarToggle.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
                sidebarToggle.title = isCollapsed ? 'Expand sidebar' : 'Collapse sidebar';
            }

            const savedCollapsed = localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === 'true';
            applySidebarState(savedCollapsed);
            bindSidebarResize();
            requestAnimationFrame(function () {
                if (dashboardShell) {
                    dashboardShell.classList.add('is-ready');
                }
            });
            bindAppDialog();
            handleWelcomeLoader();
            bindHeaderOfflineIndicator();
            bindSelectedDocumentTools();
            bindDocumentDetailsModal();
            bindQueueRowSelection();
            bindQueueRowFlowHoverPopover();
            bindLiveFlowViewport();
            renderQueueActionControls();
            syncSelectedDocumentToolsState(true);
            bindBulkRowSelection();
            renderWorkflowSplineChart();
            document.addEventListener('DTMIS:theme-changed', renderWorkflowSplineChart);
            bindRouteActionModal();
            bindEditIntakeModal();
            bindQueueActionHandlers();
            bindCreateIntakeModal();
            bindIntakeForm();
            bindQueuePagination();
            bindSearchAndFilters();
            bindOfflineQueuedQueueView();
            ensureStatCardStatusFilterOptions();
            initAllSearchableSelects(document);
            applyDateFilterFromQuery();
            applyStatusFilterFromQuery();
            restoreQueueTableState();
            bindStatCardQuickFilters();
            bindChartDateFilters();
            syncChartControlsToPageRange();
            bindExportAction();
            bindSessionHeartbeat();
            bindStickyActions();
            bindNotificationActions();
            bindQrReceivePanel();
            applyTableFilters();
            triggerAutoForwardFromQuery();
            bindLiveDashboardRefresh();
            bindScreenshotTools().finally(function () {
                runAutoScreenshotOnceAfterLoad();
            });


            if (sidebarToggle && dashboardShell) {
                sidebarToggle.addEventListener('click', function () {
                    const nextState = !dashboardShell.classList.contains('is-collapsed');
                    stopSidebarResize();
                    localStorage.setItem(SIDEBAR_COLLAPSE_KEY, String(nextState));
                    applySidebarState(nextState);
                });
            }

            function setOpen(dropdown, toggle, isOpen) {
                if (!dropdown || !toggle) {
                    return;
                }
                dropdown.classList.toggle('is-open', isOpen);
                toggle.setAttribute('aria-expanded', String(isOpen));
            }

            if (notifToggle && notifDropdown) {
                notifToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const nextState = !notifDropdown.classList.contains('is-open');
                    setOpen(notifDropdown, notifToggle, nextState);
                    if (nextState) {
                        setOpen(profileDropdown, profileToggle, false);
                        setOpen(dateFilterDropdown, dateFilterToggle, false);
                    }
                });
            }

            if (profileToggle && profileDropdown) {
                profileToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const nextState = !profileDropdown.classList.contains('is-open');
                    setOpen(profileDropdown, profileToggle, nextState);
                    if (nextState) {
                        setOpen(notifDropdown, notifToggle, false);
                        setOpen(dateFilterDropdown, dateFilterToggle, false);
                    }
                });
            }

            if (dateFilterToggle && dateFilterDropdown) {
                dateFilterToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const nextState = !dateFilterDropdown.classList.contains('is-open');
                    setOpen(dateFilterDropdown, dateFilterToggle, nextState);
                    if (nextState) {
                        setOpen(notifDropdown, notifToggle, false);
                        setOpen(profileDropdown, profileToggle, false);
                    }
                });
            }

            document.addEventListener('click', function (event) {
                if (notifDropdown && notifToggle && !notifDropdown.contains(event.target) && !notifToggle.contains(event.target)) {
                    setOpen(notifDropdown, notifToggle, false);
                }
                if (profileDropdown && profileToggle && !profileDropdown.contains(event.target) && !profileToggle.contains(event.target)) {
                    setOpen(profileDropdown, profileToggle, false);
                }
                if (dateFilterDropdown && dateFilterToggle && !dateFilterDropdown.contains(event.target) && !dateFilterToggle.contains(event.target)) {
                    setOpen(dateFilterDropdown, dateFilterToggle, false);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    setOpen(notifDropdown, notifToggle, false);
                    setOpen(profileDropdown, profileToggle, false);
                    setOpen(dateFilterDropdown, dateFilterToggle, false);
                }
            });

            function flushOfflineSensitiveDataOnLogout() {
                if (window.DTMISOfflineSecurity && typeof window.DTMISOfflineSecurity.clearSensitiveData === 'function') {
                    return Promise.resolve(window.DTMISOfflineSecurity.clearSensitiveData('logout-click'));
                }

                const fallbackTasks = [];
                if (window.DTMISOfflineOutbox && typeof window.DTMISOfflineOutbox.clearAll === 'function') {
                    fallbackTasks.push(Promise.resolve(window.DTMISOfflineOutbox.clearAll('logout-click')));
                } else if (window.DTMISOfflineOutbox && typeof window.DTMISOfflineOutbox.clear === 'function') {
                    fallbackTasks.push(Promise.resolve(window.DTMISOfflineOutbox.clear()));
                }
                if (window.DTMISOfflineReadCache && typeof window.DTMISOfflineReadCache.clear === 'function') {
                    fallbackTasks.push(Promise.resolve(window.DTMISOfflineReadCache.clear()));
                }
                return Promise.allSettled(fallbackTasks);
            }

            logoutButtons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    flushOfflineSensitiveDataOnLogout().finally(function () {
                        window.location.replace(logoutPath);
                    });
                });
            });

            if (profileSettingsButton) {
                profileSettingsButton.addEventListener('click', function () {
                    window.location.href = profileSettingsPath;
                });
            }

            /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
               MOUSE-TRACKING POPOVER + CURSOR RING SYSTEM
               â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
            (function initDashPopover() {
                /* â”€â”€ DOM setup â”€â”€ */
                const cursorDot  = document.createElement('div');
                cursorDot.className = 'dash-cursor-dot';
                const cursorRing = document.createElement('div');
                cursorRing.className = 'dash-cursor-ring';
                const popover    = document.createElement('div');
                popover.className = 'dash-hover-popover';
                popover.innerHTML = '<div class="dash-popover-inner"></div>';
                document.body.appendChild(popover);

                const popoverInner = popover.querySelector('.dash-popover-inner');

                /* â”€â”€ State â”€â”€ */
                let mouseX = 0, mouseY = 0;
                let ringX = 0, ringY = 0;
                let ringTargetX = 0, ringTargetY = 0;
                let rafId = 0;
                let activeTarget = null;
                let activePopoverKey = '';
                let isVisible = false;
                const useFreeHoverMovement = true;
                const cursorDotOffsetX = 12;
                const cursorDotOffsetY = 12;
                const cursorRingOffsetX = 12;
                const cursorRingOffsetY = 12;
                const cursorRingMatchesTip = false;
                const popoverGapX = 68;
                const popoverGapY = 52;
                let sparkleColors = ['#2f7de1','#198f7f','#8b5cf6','#f59e0b','#10b981'];

                /* â”€â”€ Icon masks per card color â”€â”€ */
                const iconMasks = {
                    blue:   "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='9' cy='7' r='4'/%3E%3Cpath d='M22 21v-2a4 4 0 0 0-3-3.87'/%3E%3Cpath d='M16 3.13a4 4 0 0 1 0 7.75'/%3E%3C/svg%3E\")",
                    orange: "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cpolyline points='12 6 12 12 16 14'/%3E%3C/svg%3E\")",
                    violet: "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 20h9'/%3E%3Cpath d='M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z'/%3E%3C/svg%3E\")",
                    green:  "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E\")",
                    red:    "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='9 14 4 9 9 4'/%3E%3Cpath d='M20 20v-3a4 4 0 0 0-4-4H4'/%3E%3C/svg%3E\")",
                };
                const iconColors = {
                    blue:   { fg: '#2f7de1', bg: 'rgba(47,125,225,0.14)' },
                    orange: { fg: '#f59e0b', bg: 'rgba(245,158,11,0.14)'  },
                    violet: { fg: '#8b5cf6', bg: 'rgba(139,92,246,0.14)'  },
                    green:  { fg: '#10b981', bg: 'rgba(16,185,129,0.14)'  },
                    red:    { fg: '#ef4444', bg: 'rgba(239,68,68,0.14)'  },
                };
                const chartIcons = {
                    donut:  "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Ccircle cx='12' cy='12' r='4'/%3E%3C/svg%3E\")",
                    bar:    "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='18' y1='20' x2='18' y2='10'/%3E%3Cline x1='12' y1='20' x2='12' y2='4'/%3E%3Cline x1='6' y1='20' x2='6' y2='14'/%3E%3Cline x1='2' y1='20' x2='22' y2='20'/%3E%3C/svg%3E\")",
                    spline: "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='22 12 18 12 15 21 9 3 6 12 2 12'/%3E%3C/svg%3E\")",
                    panel:  "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='3' width='18' height='18' rx='2'/%3E%3Cpath d='M3 9h18M9 21V9'/%3E%3C/svg%3E\")",
                };

                /* â”€â”€ rAF loop: smooth cursor ring lag â”€â”€ */
                function lerp(a, b, t) { return a + (b - a) * t; }

                function animateRing() {
                    if (cursorRingMatchesTip) {
                        ringX = ringTargetX;
                        ringY = ringTargetY;
                    } else {
                        ringX = lerp(ringX, ringTargetX, 0.18);
                        ringY = lerp(ringY, ringTargetY, 0.18);
                    }
                    cursorRing.style.left = ringX + 'px';
                    cursorRing.style.top  = ringY + 'px';
                    rafId = requestAnimationFrame(animateRing);
                }
                rafId = requestAnimationFrame(animateRing);

                function setCursorIndicatorPosition(x, y, useCursorOffsets) {
                    const applyOffsets = useCursorOffsets !== false;
                    const dotOffsetX = applyOffsets ? cursorDotOffsetX : 0;
                    const dotOffsetY = applyOffsets ? cursorDotOffsetY : 0;
                    const ringOffsetX = applyOffsets ? cursorRingOffsetX : 0;
                    const ringOffsetY = applyOffsets ? cursorRingOffsetY : 0;
                    cursorDot.style.left = (x + dotOffsetX) + 'px';
                    cursorDot.style.top  = (y + dotOffsetY) + 'px';
                    ringTargetX = x + ringOffsetX;
                    ringTargetY = y + ringOffsetY;
                }

                /* â”€â”€ Snapping calculation â”€â”€ */
                function getSnappingPoint(target, mx, my) {
                    if (!target) return null;
                    const targetRect = target.getBoundingClientRect();

                    /* Helper: only snap if the point is actually within the target component's visual bounds */
                    function isReliable(p, threshold) {
                        if (!p) return false;
                        const d = Math.hypot(p.x - mx, p.y - my);
                        if (d > threshold) return false;
                        // Containment check with tiny margin
                        return (p.x >= targetRect.left - 20 && p.x <= targetRect.right + 20 &&
                                p.y >= targetRect.top - 20  && p.y <= targetRect.bottom + 20);
                    }

                    /* 1. Stat Card -> Snap to icon only when near card top/icon area */
                    if (target.classList.contains('stat-card')) {
                        const icon = target.querySelector('.stat-icon');
                        if (icon) {
                            const r = icon.getBoundingClientRect();
                            const p = { x: r.left + r.width / 2, y: r.top + r.height / 2 };
                            // Always snap if hovering anywhere in a small stat card, or if near icon
                            const dist = Math.hypot(p.x - mx, p.y - my);
                            if (dist < 150) return p; 
                        }
                    }

                    /* 2. Vertical Bars -> Snap to top center of filled portion */
                    const barWrap = target.querySelector('.chart-bar-wrap');
                    if (barWrap) {
                        const bars = Array.from(target.querySelectorAll('.chart-bar-col'));
                        let best = null;
                        let minDist = 60; // Tighter threshold
                        bars.forEach(function (c) {
                            const fill = c.querySelector('.chart-bar-fill');
                            const rail = c.querySelector('.chart-bar-rail');
                            if (!fill || !rail) return;
                            const rr = rail.getBoundingClientRect();
                            const fr = fill.getBoundingClientRect();
                            const p = { x: rr.left + rr.width/2, y: fr.top };
                            const d = Math.hypot(p.x - mx, p.y - my);
                            if (d < minDist) { minDist = d; best = p; }
                        });
                        if (best && isReliable(best, 80)) return best;
                    }

                    /* 3. Horizontal Progress Bars -> Snap to right end tip of fill */
                    const progList = target.querySelector('.progress-list');
                    if (progList) {
                        const rows = Array.from(target.querySelectorAll('.progress-row'));
                        let best = null;
                        let minDist = 50; 
                        rows.forEach(function (r) {
                            const fill = r.querySelector('.bar span');
                            const bar  = r.querySelector('.bar');
                            if (!fill || !bar) return;
                            const br = bar.getBoundingClientRect();
                            const fr = fill.getBoundingClientRect();
                            const p = { x: fr.left + fr.width, y: br.top + br.height / 2 };
                            const d = Math.hypot(p.x - mx, p.y - my);
                            if (d < minDist) { minDist = d; best = p; }
                        });
                        if (best && isReliable(best, 60)) return best;
                    }

                    /* 4. Spline Chart -> Snap to nearest data point */
                    const spline = target.querySelector('.chart-spline');
                    if (spline) {
                        const circles = Array.from(spline.querySelectorAll('circle'));
                        let best = null;
                        let minDist = 70;
                        circles.forEach(function (c) {
                            const r = c.getBoundingClientRect();
                            const p = { x: r.left + r.width / 2, y: r.top + r.height / 2 };
                            const d = Math.hypot(p.x - mx, p.y - my);
                            if (d < minDist) { minDist = d; best = p; }
                        });
                        if (best && isReliable(best, 100)) return best;
                    }

                    /* 5. Donut Chart -> Snap to mid-angle of current slice */
                    const donut = target.querySelector('.chart-donut');
                    if (donut) {
                        const r = donut.getBoundingClientRect();
                        const cx = r.left + r.width / 2;
                        const cy = r.top + r.height / 2;
                        const dist = Math.hypot(mx - cx, my - cy);
                        if (dist > r.width * 1.5) return null;
                        
                        const radius = r.width / 2 * 0.72;
                        const dx = mx - cx;
                        const dy = my - cy;
                        let angle = Math.atan2(dy, dx) * 180 / Math.PI + 90;
                        if (angle < 0) angle += 360;

                        if (typeof chartPieSeries !== 'undefined' && Array.isArray(chartPieSeries) && typeof chartPieTotal !== 'undefined' && chartPieTotal > 0) {
                            let start = 0;
                            for (const item of chartPieSeries) {
                                const val = Math.max(0, parseInt(item.value || 0));
                                if (val <= 0) continue;
                                const slice = (val / chartPieTotal) * 360;
                                if (angle >= start && angle < start + slice) {
                                    const mid = start + slice / 2;
                                    const rad = (mid - 90) * Math.PI / 180;
                                    const snap = { x: cx + Math.cos(rad) * radius, y: cy + Math.sin(rad) * radius };
                                    if (isReliable(snap, 200)) return snap;
                                }
                                start += slice;
                            }
                        }
                    }

                    return null;
                }

                /* â”€â”€ Position the straight-snap dot & popover â”€â”€ */
                function updatePositions(x, y, useCursorOffsets) {
                    setCursorIndicatorPosition(x, y, useCursorOffsets);

                    if (!isVisible) return;

                    /* Flip if near viewport edge */
                    const pr    = popover.getBoundingClientRect();
                    const vw    = window.innerWidth;
                    const vh    = window.innerHeight;
                    const nearR = (mouseX + pr.width  + popoverGapX + 20) > vw;
                    const nearB = (mouseY + pr.height + popoverGapY + 20) > vh;

                    popover.classList.toggle('is-flipped-x', nearR);
                    popover.classList.toggle('is-flipped-y', nearB);

                    const finalX = nearR ? (mouseX - pr.width - popoverGapX) : (mouseX + popoverGapX);
                    const finalY = nearB ? (mouseY - pr.height - popoverGapY) : (mouseY + popoverGapY);

                    popover.style.left = finalX + 'px';
                    popover.style.top  = finalY + 'px';
                }

                /* â”€â”€ Build popover content â”€â”€ */
                function buildStatCardContent(target) {
                    const labelEl = target.querySelector('.stat-label');
                    const valueEl = target.querySelector('.stat-value');
                    const iconEl  = target.querySelector('.stat-icon');

                    const label = labelEl ? labelEl.textContent.trim() : '';
                    const value = valueEl ? valueEl.textContent.trim() : '0';
                    const colorClass = iconEl
                        ? (['blue','orange','violet','green','red'].find(function(c){ return iconEl.classList.contains(c); }) || 'blue')
                        : 'blue';

                    const iconColor = iconColors[colorClass] || iconColors.blue;
                    const mask      = iconMasks[colorClass] || iconMasks.blue;
                    const accentMap = { blue:'linear-gradient(90deg,#2f7de1,#60a5fa)', orange:'linear-gradient(90deg,#f59e0b,#fb923c)', violet:'linear-gradient(90deg,#8b5cf6,#a78bfa)', green:'linear-gradient(90deg,#10b981,#34d399)', red:'linear-gradient(90deg,#ef4444,#f87171)' };
                    const accent    = accentMap[colorClass] || accentMap.blue;

                    popover.style.setProperty('--dash-pop-accent', accent);
                    const num = parseInt(value.replace(/\D/g,''), 10) || 0;

                    return '<div class="dash-popover-icon-row">'
                        + '<span class="dash-popover-icon" style="background:' + iconColor.bg + ';color:' + iconColor.fg + ';"><span style="width:16px;height:16px;display:block;background:currentColor;-webkit-mask-image:' + mask + ';mask-image:' + mask + ';-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;"></span></span>'
                        + '<div><p class="dash-popover-label">KPI Snapshot</p><p class="dash-popover-title">' + escapeHtml(label) + '</p></div>'
                        + '</div>'
                        + '<div class="dash-popover-divider"></div>'
                        + '<div class="dash-popover-value-row"><span class="dash-popover-value" data-target="' + num + '">0</span><span class="dash-popover-unit">docs</span></div>'
                        + '<p class="dash-popover-meta">Live count â€” updates every 30s</p>';
                }

                function buildChartPanelContent(target) {
                    const headEl  = target.querySelector('.chart-head h2, h2, .panel-head h2');
                    const noteEl  = target.querySelector('.chart-note');
                    const title   = headEl ? headEl.textContent.trim() : 'Metric Detail';
                    const note    = noteEl ? noteEl.textContent.trim() : '';
                    
                    /* Detect chart type */
                    let chartType = 'panel';
                    let accent = 'linear-gradient(90deg,#0d9488,#2dd4bf)';
                    if (target.querySelector('.chart-donut'))         { chartType = 'donut'; accent = 'linear-gradient(90deg,#7c3aed,#a78bfa)'; }
                    else if (target.querySelector('.chart-bar-wrap')) { chartType = 'bar';   accent = 'linear-gradient(90deg,#2f7de1,#60a5fa)'; }
                    else if (target.querySelector('.chart-spline'))   { chartType = 'spline'; accent = 'linear-gradient(90deg,#0d9488,#2dd4bf)'; }
                    else if (target.querySelector('.progress-list'))  { chartType = 'panel'; accent = 'linear-gradient(90deg,#f59e0b,#fb923c)'; }

                    popover.style.setProperty('--dash-pop-accent', accent);
                    const mask = chartIcons[chartType] || chartIcons.panel;
                    const iconC = { donut:{ fg:'#7c3aed',bg:'rgba(139,92,246,0.14)' }, bar:{ fg:'#2f7de1',bg:'rgba(47,125,225,0.14)' }, spline:{ fg:'#0d9488',bg:'rgba(13,148,136,0.14)' }, panel:{ fg:'#f59e0b',bg:'rgba(245,158,11,0.14)' } };
                    const ic = iconC[chartType] || iconC.panel;

                    /* Collect a quick summary stat */
                    let summaryNum = 0;
                    let summaryLabel = 'items';
                    if (chartType === 'donut') {
                        const hole = target.querySelector('.chart-donut-hole strong');
                        if (hole) { summaryNum = parseInt(hole.textContent.trim(), 10) || 0; summaryLabel = 'total docs'; }
                    } else if (chartType === 'bar') {
                        const cols = target.querySelectorAll('.chart-bar-col strong');
                        let total = 0;
                        cols.forEach(function(c){ total += parseInt(c.textContent.trim(),10)||0; });
                        summaryNum = total; summaryLabel = 'in queue';
                    } else if (chartType === 'spline') {
                        summaryLabel = '7-day trend';
                    } else if (chartType === 'panel') {
                        const rowVals = target.querySelectorAll('.progress-row .row-meta span:nth-child(2)');
                        let total = 0;
                        rowVals.forEach(function(v){ total += parseInt(v.textContent.trim(), 10) || 0; });
                        summaryNum = total; 
                        summaryLabel = 'active count';
                    }

                    return '<div class="dash-popover-icon-row">'
                        + '<span class="dash-popover-icon" style="background:' + ic.bg + ';color:' + ic.fg + ';"><span style="width:16px;height:16px;display:block;background:currentColor;-webkit-mask-image:' + mask + ';mask-image:' + mask + ';-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;"></span></span>'
                        + '<div><p class="dash-popover-label">Dashboard â€” ' + escapeHtml(note || 'Insight') + '</p><p class="dash-popover-title">' + escapeHtml(title) + '</p></div>'
                        + '</div>'
                        + '<div class="dash-popover-divider"></div>'
                        + (summaryLabel !== '7-day trend'
                            ? '<div class="dash-popover-value-row"><span class="dash-popover-value" data-target="' + summaryNum + '">0</span><span class="dash-popover-unit">' + escapeHtml(summaryLabel) + '</span></div>'
                            : '<div class="dash-popover-value-row"><span class="dash-popover-value" style="font-size:14px;letter-spacing:0">Trend Analysis</span></div>'
                          )
                        + '<p class="dash-popover-meta">Hover cards to inspect details</p>';
                }

                function escapeHtml(str) {
                    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g, '&#39;');
                }

                function clampNumber(value, min, max) {
                    return Math.max(min, Math.min(max, value));
                }

                function parseNumericValue(value) {
                    const normalized = String(value || '').replace(/,/g, '');
                    const parsed = parseFloat(normalized.replace(/[^0-9.\-]/g, ''));
                    return Number.isFinite(parsed) ? parsed : 0;
                }

                function formatNumber(value) {
                    const numeric = Number(value || 0);
                    if (!Number.isFinite(numeric)) {
                        return '0';
                    }
                    return Math.round(numeric).toLocaleString('en-US');
                }

                function formatPercent(part, total) {
                    if (!Number.isFinite(part) || !Number.isFinite(total) || total <= 0) {
                        return '0%';
                    }
                    return String(Math.round((part / total) * 100)) + '%';
                }

                function signedValue(value) {
                    const numeric = Math.round(Number(value || 0));
                    if (!Number.isFinite(numeric)) {
                        return '0';
                    }
                    return (numeric > 0 ? '+' : '') + formatNumber(numeric);
                }

                function centerPoint(element) {
                    if (!element) {
                        return null;
                    }
                    const rect = element.getBoundingClientRect();
                    return { x: rect.left + (rect.width / 2), y: rect.top + (rect.height / 2) };
                }

                function getSvgCircleClientPoint(circle) {
                    if (!circle || typeof circle.getAttribute !== 'function') {
                        return null;
                    }
                    const svg = circle.ownerSVGElement;
                    if (!svg || !svg.viewBox || !svg.viewBox.baseVal) {
                        return centerPoint(circle);
                    }
                    const svgRect = svg.getBoundingClientRect();
                    const viewBox = svg.viewBox.baseVal;
                    const cx = parseFloat(String(circle.getAttribute('cx') || '0'));
                    const cy = parseFloat(String(circle.getAttribute('cy') || '0'));
                    if (!Number.isFinite(cx) || !Number.isFinite(cy) || viewBox.width <= 0 || viewBox.height <= 0) {
                        return centerPoint(circle);
                    }
                    return {
                        x: svgRect.left + (((cx - viewBox.x) / viewBox.width) * svgRect.width),
                        y: svgRect.top + (((cy - viewBox.y) / viewBox.height) * svgRect.height)
                    };
                }

                function buildDetailGrid(items) {
                    const safeItems = Array.isArray(items)
                        ? items.filter(function (item) {
                            return item && String(item.value || '').trim() !== '';
                        })
                        : [];
                    if (safeItems.length === 0) {
                        return '';
                    }

                    return '<div class="dash-popover-detail-grid">'
                        + safeItems.map(function (item) {
                            return '<div class="dash-popover-detail-item">'
                                + '<span class="dash-popover-detail-label">' + escapeHtml(item.label || '') + '</span>'
                                + '<strong class="dash-popover-detail-value">' + escapeHtml(item.value || '') + '</strong>'
                                + '</div>';
                        }).join('')
                        + '</div>';
                }

                function buildMeter(percent, fill) {
                    const safePercent = clampNumber(Math.round(Number(percent || 0)), 0, 100);
                    return '<div class="dash-popover-bar-row"><div class="dash-popover-bar"><span class="dash-popover-bar-fill" style="width:' + safePercent + '%;background:' + String(fill || 'linear-gradient(90deg,#198f7f,#2f7de1)') + ';"></span></div></div>';
                }

                function renderPopoverCard(options) {
                    const accent = String(options.accent || 'linear-gradient(90deg,#198f7f,#2f7de1)');
                    const iconFg = String(options.iconFg || '#198f7f');
                    const iconBg = String(options.iconBg || 'rgba(25,143,127,0.14)');
                    const iconMask = String(options.iconMask || chartIcons.panel);
                    const unit = String(options.unit || '').trim();
                    const numericValue = Number(options.value || 0);

                    popover.style.setProperty('--dash-pop-accent', accent);

                    return '<div class="dash-popover-icon-row">'
                        + '<span class="dash-popover-icon" style="background:' + iconBg + ';color:' + iconFg + ';"><span style="width:16px;height:16px;display:block;background:currentColor;-webkit-mask-image:' + iconMask + ';mask-image:' + iconMask + ';-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;"></span></span>'
                        + '<div><p class="dash-popover-label">' + escapeHtml(options.kicker || 'Dashboard Insight') + '</p><p class="dash-popover-title">' + escapeHtml(options.title || 'Metric Detail') + '</p></div>'
                        + '</div>'
                        + '<div class="dash-popover-divider"></div>'
                        + '<div class="dash-popover-value-row"><span class="dash-popover-value" data-target="' + Math.max(0, Math.round(numericValue)) + '">0</span>' + (unit !== '' ? '<span class="dash-popover-unit">' + escapeHtml(unit) + '</span>' : '') + '</div>'
                        + (typeof options.barPercent === 'number' ? buildMeter(options.barPercent, options.barFill || accent) : '')
                        + buildDetailGrid(options.details)
                        + '<p class="dash-popover-meta">' + escapeHtml(options.meta || 'Hover the dashboard to inspect details.') + '</p>';
                }

                function getPanelTitle(target) {
                    const titleNode = target ? target.querySelector('.chart-head h2, h2, .panel-head h2') : null;
                    return titleNode ? titleNode.textContent.trim() : 'Metric Detail';
                }

                function getPanelNote(target) {
                    const noteNode = target ? target.querySelector('.chart-note') : null;
                    return noteNode ? noteNode.textContent.trim() : '';
                }

                function getProgressRowData(row) {
                    if (!row) {
                        return null;
                    }
                    const labelNode = row.querySelector('.row-meta span:first-child');
                    const valueNode = row.querySelector('.row-meta span:last-child');
                    const fillNode = row.querySelector('.bar span');
                    const barNode = row.querySelector('.bar');
                    const label = labelNode ? labelNode.textContent.trim() : '';
                    const value = valueNode ? parseNumericValue(valueNode.textContent) : 0;
                    const width = fillNode ? parseNumericValue(fillNode.style.width) : 0;
                    let snap = null;
                    if (fillNode && barNode) {
                        const barRect = barNode.getBoundingClientRect();
                        const fillRect = fillNode.getBoundingClientRect();
                        snap = { x: fillRect.left + fillRect.width, y: barRect.top + (barRect.height / 2) };
                    }
                    return { row: row, label: label, value: value, width: width, snap: snap };
                }

                function getBarColumnData(col) {
                    if (!col) {
                        return null;
                    }
                    const labelNode = col.querySelector('span:last-child');
                    const valueNode = col.querySelector('strong');
                    const fillNode = col.querySelector('.chart-bar-fill');
                    const railNode = col.querySelector('.chart-bar-rail');
                    const label = labelNode ? labelNode.textContent.trim() : '';
                    const value = valueNode ? parseNumericValue(valueNode.textContent) : 0;
                    const height = fillNode ? parseNumericValue(fillNode.style.height) : 0;
                    const color = fillNode ? String(fillNode.style.background || fillNode.style.backgroundColor || '#2f7de1') : '#2f7de1';
                    let snap = null;
                    if (fillNode && railNode) {
                        const railRect = railNode.getBoundingClientRect();
                        const fillRect = fillNode.getBoundingClientRect();
                        snap = { x: railRect.left + (railRect.width / 2), y: fillRect.top };
                    }
                    return { col: col, label: label, value: value, height: height, color: color, snap: snap };
                }

                function getStatCardContext(target) {
                    const labelNode = target.querySelector('.stat-label');
                    const valueNode = target.querySelector('.stat-value');
                    const iconNode = target.querySelector('.stat-icon');
                    const pillNode = target.querySelector('.live-pill');
                    const label = labelNode ? labelNode.textContent.trim() : 'KPI Snapshot';
                    const rawValue = valueNode ? valueNode.textContent.trim() : '0';
                    const numericValue = parseNumericValue(rawValue);
                    const colorClass = iconNode
                        ? (['blue', 'orange', 'violet', 'green', 'red'].find(function (color) {
                            return iconNode.classList.contains(color);
                        }) || 'blue')
                        : 'blue';
                    const allCards = Array.from(document.querySelectorAll('.stat-card'));
                    const values = allCards.map(function (card) {
                        const text = card.querySelector('.stat-value');
                        return parseNumericValue(text ? text.textContent : 0);
                    });
                    const deckTotal = values.reduce(function (sum, value) {
                        return sum + value;
                    }, 0);
                    const ranked = values.slice().sort(function (a, b) {
                        return b - a;
                    });
                    return {
                        kind: 'stat-card',
                        key: 'stat:' + label + ':' + rawValue,
                        snap: centerPoint(iconNode) || centerPoint(target),
                        label: label,
                        rawValue: rawValue,
                        value: numericValue,
                        unit: rawValue.indexOf('%') !== -1 ? '%' : 'items',
                        colorClass: colorClass,
                        pill: pillNode ? pillNode.textContent.trim() : '',
                        deckTotal: deckTotal,
                        rank: Math.max(ranked.indexOf(numericValue) + 1, 1),
                        totalCards: values.length
                    };
                }

                function getProgressRowContext(target, row, hoverX, hoverY) {
                    const panelTitle = getPanelTitle(target);
                    const rowData = getProgressRowData(row);
                    if (!rowData) {
                        return null;
                    }
                    const rows = Array.from(target.querySelectorAll('.progress-row')).map(getProgressRowData).filter(Boolean);
                    const total = rows.reduce(function (sum, item) {
                        return sum + item.value;
                    }, 0);
                    const topRow = rows.slice().sort(function (a, b) {
                        return b.value - a.value;
                    })[0] || null;
                    return {
                        kind: 'progress-row',
                        key: 'progress:' + panelTitle + ':' + rowData.label + ':' + rowData.value,
                        snap: (rowData.width <= 6 || rowData.value <= 0)
                            ? { x: Number.isFinite(hoverX) ? hoverX : (centerPoint(row) ? centerPoint(row).x : 0), y: Number.isFinite(hoverY) ? hoverY : (centerPoint(row) ? centerPoint(row).y : 0) }
                            : (rowData.snap || centerPoint(row)),
                        panelTitle: panelTitle,
                        panelNote: getPanelNote(target) || 'Grid detail',
                        label: rowData.label,
                        value: rowData.value,
                        width: rowData.width,
                        total: total,
                        topRow: topRow
                    };
                }

                function getProgressSummaryContext(target) {
                    const rows = Array.from(target.querySelectorAll('.progress-row')).map(getProgressRowData).filter(Boolean);
                    const total = rows.reduce(function (sum, item) {
                        return sum + item.value;
                    }, 0);
                    const topRow = rows.slice().sort(function (a, b) {
                        return b.value - a.value;
                    })[0] || null;
                    return {
                        kind: 'progress-summary',
                        key: 'progress-summary:' + getPanelTitle(target) + ':' + total,
                        snap: centerPoint(target),
                        panelTitle: getPanelTitle(target),
                        panelNote: getPanelNote(target) || 'Grid detail',
                        value: total,
                        topRow: topRow,
                        rowCount: rows.length
                    };
                }

                function getBarColumnContext(target, col, hoverX, hoverY) {
                    const panelTitle = getPanelTitle(target);
                    const columnData = getBarColumnData(col);
                    if (!columnData) {
                        return null;
                    }
                    const columns = Array.from(target.querySelectorAll('.chart-bar-col')).map(getBarColumnData).filter(Boolean);
                    const total = columns.reduce(function (sum, item) {
                        return sum + item.value;
                    }, 0);
                    const peak = columns.slice().sort(function (a, b) {
                        return b.value - a.value;
                    })[0] || null;
                    return {
                        kind: 'bar-column',
                        key: 'bar:' + panelTitle + ':' + columnData.label + ':' + columnData.value,
                        snap: (columnData.height <= 8 || columnData.value <= 0)
                            ? { x: Number.isFinite(hoverX) ? hoverX : (centerPoint(col) ? centerPoint(col).x : 0), y: Number.isFinite(hoverY) ? hoverY : (centerPoint(col) ? centerPoint(col).y : 0) }
                            : (columnData.snap || centerPoint(col)),
                        panelTitle: panelTitle,
                        panelNote: getPanelNote(target) || 'Current scope',
                        label: columnData.label,
                        value: columnData.value,
                        height: columnData.height,
                        color: columnData.color,
                        total: total,
                        peak: peak
                    };
                }

                function getBarSummaryContext(target) {
                    const columns = Array.from(target.querySelectorAll('.chart-bar-col')).map(getBarColumnData).filter(Boolean);
                    const total = columns.reduce(function (sum, item) {
                        return sum + item.value;
                    }, 0);
                    const peak = columns.slice().sort(function (a, b) {
                        return b.value - a.value;
                    })[0] || null;
                    return {
                        kind: 'bar-summary',
                        key: 'bar-summary:' + getPanelTitle(target) + ':' + total,
                        snap: centerPoint(target.querySelector('.chart-bar-wrap')) || centerPoint(target),
                        panelTitle: getPanelTitle(target),
                        panelNote: getPanelNote(target) || 'Current scope',
                        value: total,
                        peak: peak,
                        columnCount: columns.length
                    };
                }

                function getDonutSliceSnap(target, index) {
                    const donut = target.querySelector('.chart-donut');
                    const total = Math.max(0, Number(chartPieTotal || 0));
                    if (!donut || !Array.isArray(chartPieSeries) || total <= 0) {
                        return null;
                    }
                    const rect = donut.getBoundingClientRect();
                    const cx = rect.left + (rect.width / 2);
                    const cy = rect.top + (rect.height / 2);
                    const radius = (rect.width / 2) * 0.72;
                    let start = 0;
                    for (let itemIndex = 0; itemIndex < chartPieSeries.length; itemIndex += 1) {
                        const item = chartPieSeries[itemIndex];
                        const value = Math.max(0, Number(item && item.value ? item.value : 0));
                        if (value <= 0) {
                            continue;
                        }
                        const slice = (value / total) * 360;
                        if (itemIndex === index) {
                            const mid = start + (slice / 2);
                            const radians = (mid - 90) * Math.PI / 180;
                            return {
                                x: cx + (Math.cos(radians) * radius),
                                y: cy + (Math.sin(radians) * radius)
                            };
                        }
                        start += slice;
                    }
                    return null;
                }

                function getDonutSliceContext(target, index, fallbackElement) {
                    const item = Array.isArray(chartPieSeries) ? chartPieSeries[index] : null;
                    const label = item ? String(item.label || 'Category').trim() : 'Category';
                    const value = item ? Math.max(0, Number(item.value || 0)) : 0;
                    const total = Math.max(0, Number(chartPieTotal || 0));
                    return {
                        kind: 'donut-slice',
                        key: 'donut:' + label + ':' + value,
                        snap: getDonutSliceSnap(target, index) || centerPoint(fallbackElement) || centerPoint(target.querySelector('.chart-donut')) || centerPoint(target),
                        panelTitle: getPanelTitle(target),
                        panelNote: getPanelNote(target) || 'Active workload',
                        label: label,
                        value: value,
                        total: total,
                        color: item ? String(item.color || '#7c3aed') : '#7c3aed'
                    };
                }

                function getDonutSummaryContext(target) {
                    const total = Math.max(0, Number(chartPieTotal || 0));
                    const dominant = Array.isArray(chartPieSeries)
                        ? chartPieSeries.slice().sort(function (a, b) {
                            return Number(b && b.value ? b.value : 0) - Number(a && a.value ? a.value : 0);
                        })[0] || null
                        : null;
                    return {
                        kind: 'donut-summary',
                        key: 'donut-summary:' + total,
                        snap: centerPoint(target.querySelector('.chart-donut')) || centerPoint(target),
                        panelTitle: getPanelTitle(target),
                        panelNote: getPanelNote(target) || 'Active workload',
                        value: total,
                        dominant: dominant
                    };
                }

                function getSplinePointContext(target, circle) {
                    if (!circle) {
                        return null;
                    }
                    const seriesIndex = parseInt(String(circle.getAttribute('data-series-index') || '0'), 10);
                    const pointIndex = parseInt(String(circle.getAttribute('data-point-index') || '0'), 10);
                    const label = String(circle.getAttribute('data-label') || '').trim();
                    const seriesLabel = String(circle.getAttribute('data-series-label') || 'Series').trim();
                    const value = parseNumericValue(circle.getAttribute('data-value'));
                    const previousValue = parseNumericValue(circle.getAttribute('data-prev-value'));
                    const color = String(circle.getAttribute('data-color') || '#0d9488');
                    const seriesValues = chartTrendData && Array.isArray(chartTrendData.series) && chartTrendData.series[seriesIndex] && Array.isArray(chartTrendData.series[seriesIndex].values)
                        ? chartTrendData.series[seriesIndex].values
                        : [];
                    const seriesMax = seriesValues.reduce(function (max, item) {
                        return Math.max(max, Number(item || 0));
                    }, 0);
                    const dayTotal = chartTrendData && Array.isArray(chartTrendData.series)
                        ? chartTrendData.series.reduce(function (sum, series) {
                            const seriesValue = Array.isArray(series.values) ? Number(series.values[pointIndex] || 0) : 0;
                            return sum + seriesValue;
                        }, 0)
                        : value;
                    return {
                        kind: 'spline-point',
                        key: 'spline:' + seriesLabel + ':' + label + ':' + value,
                        snap: getSvgCircleClientPoint(circle) || centerPoint(circle),
                        panelTitle: getPanelTitle(target),
                        panelNote: getPanelNote(target) || '7-day trend',
                        label: label,
                        seriesLabel: seriesLabel,
                        value: value,
                        previousValue: previousValue,
                        delta: value - previousValue,
                        dayTotal: dayTotal,
                        seriesMax: seriesMax,
                        color: color
                    };
                }

                function getSplineSummaryContext(target) {
                    const totals = Array.isArray(chartTrendData && chartTrendData.series)
                        ? chartTrendData.series.map(function (series) {
                            return {
                                label: String(series.label || 'Series'),
                                total: Array.isArray(series.values)
                                    ? series.values.reduce(function (sum, value) { return sum + Number(value || 0); }, 0)
                                    : 0
                            };
                        })
                        : [];
                    const total = totals.reduce(function (sum, item) {
                        return sum + item.total;
                    }, 0);
                    const strongest = totals.slice().sort(function (a, b) {
                        return b.total - a.total;
                    })[0] || null;
                    return {
                        kind: 'spline-summary',
                        key: 'spline-summary:' + total,
                        snap: centerPoint(target.querySelector('.chart-spline-wrap')) || centerPoint(target),
                        panelTitle: getPanelTitle(target),
                        panelNote: getPanelNote(target) || '7-day trend',
                        value: total,
                        strongest: strongest
                    };
                }

                function resolvePopoverContext(target, sourceEl, mx, my) {
                    if (!target) {
                        return null;
                    }

                    if (target.classList.contains('stat-card')) {
                        return getStatCardContext(target);
                    }

                    const source = sourceEl && typeof sourceEl.closest === 'function' ? sourceEl : null;

                    if (target.classList.contains('chart-panel')) {
                        const legendItem = source ? source.closest('.chart-legend-item') : null;
                        if (legendItem && target.contains(legendItem)) {
                            const legendItems = Array.from(target.querySelectorAll('.chart-legend-item'));
                            const legendIndex = legendItems.indexOf(legendItem);
                            if (legendIndex >= 0) {
                                return getDonutSliceContext(target, legendIndex, legendItem);
                            }
                        }

                        const hoveredColumn = source ? source.closest('.chart-bar-col') : null;
                        if (hoveredColumn && target.contains(hoveredColumn)) {
                            return getBarColumnContext(target, hoveredColumn, mx, my);
                        }

                        const barWrap = target.querySelector('.chart-bar-wrap');
                        if (barWrap) {
                            const wrapRect = barWrap.getBoundingClientRect();
                            if (mx >= wrapRect.left && mx <= wrapRect.right && my >= wrapRect.top && my <= wrapRect.bottom) {
                                const columns = Array.from(target.querySelectorAll('.chart-bar-col')).map(function (col) {
                                    const data = getBarColumnData(col);
                                    if (!data || !data.snap) {
                                        return null;
                                    }
                                    return { col: col, distance: Math.abs(data.snap.x - mx) };
                                }).filter(Boolean).sort(function (a, b) {
                                    return a.distance - b.distance;
                                });
                                if (columns[0]) {
                                    return getBarColumnContext(target, columns[0].col, mx, my);
                                }
                            }
                        }

                        const donut = target.querySelector('.chart-donut');
                        if (donut) {
                            const donutRect = donut.getBoundingClientRect();
                            const total = Math.max(0, Number(chartPieTotal || 0));
                            if (mx >= donutRect.left && mx <= donutRect.right && my >= donutRect.top && my <= donutRect.bottom && total > 0 && Array.isArray(chartPieSeries)) {
                                const cx = donutRect.left + (donutRect.width / 2);
                                const cy = donutRect.top + (donutRect.height / 2);
                                let angle = Math.atan2(my - cy, mx - cx) * 180 / Math.PI + 90;
                                if (angle < 0) {
                                    angle += 360;
                                }
                                let start = 0;
                                for (let index = 0; index < chartPieSeries.length; index += 1) {
                                    const sliceValue = Math.max(0, Number(chartPieSeries[index] && chartPieSeries[index].value ? chartPieSeries[index].value : 0));
                                    if (sliceValue <= 0) {
                                        continue;
                                    }
                                    const sweep = (sliceValue / total) * 360;
                                    if (angle >= start && angle < start + sweep) {
                                        return getDonutSliceContext(target, index, donut);
                                    }
                                    start += sweep;
                                }
                            }
                        }

                        const hoveredPoint = source ? source.closest('.chart-spline circle') : null;
                        if (hoveredPoint && target.contains(hoveredPoint)) {
                            return getSplinePointContext(target, hoveredPoint);
                        }

                        const splineWrap = target.querySelector('.chart-spline-wrap');
                        if (splineWrap) {
                            const wrapRect = splineWrap.getBoundingClientRect();
                            if (mx >= wrapRect.left && mx <= wrapRect.right && my >= wrapRect.top && my <= wrapRect.bottom) {
                                const points = Array.from(target.querySelectorAll('.chart-spline circle')).map(function (circle) {
                                    const snap = getSvgCircleClientPoint(circle) || centerPoint(circle);
                                    if (!snap) {
                                        return null;
                                    }
                                    return { circle: circle, distance: Math.hypot(snap.x - mx, snap.y - my) };
                                }).filter(Boolean).sort(function (a, b) {
                                    return a.distance - b.distance;
                                });
                                if (points[0]) {
                                    return getSplinePointContext(target, points[0].circle);
                                }
                            }
                        }

                        if (target.querySelector('.chart-donut')) {
                            return getDonutSummaryContext(target);
                        }
                        if (target.querySelector('.chart-bar-wrap')) {
                            return getBarSummaryContext(target);
                        }
                        if (target.querySelector('.chart-spline')) {
                            return getSplineSummaryContext(target);
                        }
                    }

                    if (target.classList.contains('panel') && target.querySelector('.progress-list')) {
                        const hoveredRow = source ? source.closest('.progress-row') : null;
                        if (hoveredRow && target.contains(hoveredRow)) {
                            return getProgressRowContext(target, hoveredRow, mx, my);
                        }

                        const list = target.querySelector('.progress-list');
                        if (list) {
                            const listRect = list.getBoundingClientRect();
                            if (mx >= listRect.left && mx <= listRect.right && my >= listRect.top && my <= listRect.bottom) {
                                const rows = Array.from(target.querySelectorAll('.progress-row')).map(function (row) {
                                    const rect = row.getBoundingClientRect();
                                    return { row: row, distance: Math.abs((rect.top + (rect.height / 2)) - my) };
                                }).sort(function (a, b) {
                                    return a.distance - b.distance;
                                });
                                if (rows[0]) {
                                    return getProgressRowContext(target, rows[0].row, mx, my);
                                }
                            }
                        }

                        return getProgressSummaryContext(target);
                    }

                    return null;
                }

                function buildStatCardContent(target, context) {
                    const detail = context || getStatCardContext(target);
                    const palette = iconColors[detail.colorClass] || iconColors.blue;
                    const accentMap = {
                        blue: 'linear-gradient(90deg,#2f7de1,#60a5fa)',
                        orange: 'linear-gradient(90deg,#f59e0b,#fb923c)',
                        violet: 'linear-gradient(90deg,#8b5cf6,#a78bfa)',
                        green: 'linear-gradient(90deg,#10b981,#34d399)',
                        red: 'linear-gradient(90deg,#ef4444,#f87171)'
                    };
                    return renderPopoverCard({
                        kicker: 'KPI Snapshot',
                        title: detail.label,
                        value: detail.value,
                        unit: detail.unit,
                        accent: accentMap[detail.colorClass] || accentMap.blue,
                        iconFg: palette.fg,
                        iconBg: palette.bg,
                        iconMask: iconMasks[detail.colorClass] || iconMasks.blue,
                        barPercent: detail.deckTotal > 0 ? (detail.value / detail.deckTotal) * 100 : null,
                        details: [
                            { label: 'Status', value: detail.pill || 'Live metric' },
                            { label: 'Deck share', value: formatPercent(detail.value, detail.deckTotal) },
                            { label: 'Rank', value: '#' + detail.rank + ' of ' + detail.totalCards }
                        ],
                        meta: detail.rawValue.indexOf('%') !== -1 ? 'Percentage-based summary metric.' : 'Live count synced with dashboard updates.'
                    });
                }

                function buildChartPanelContent(target, context) {
                    const detail = context || null;
                    if (!detail) {
                        return '';
                    }

                    if (detail.kind === 'progress-row') {
                        return renderPopoverCard({
                            kicker: 'Grid Detail',
                            title: detail.label,
                            value: detail.value,
                            unit: 'items',
                            accent: 'linear-gradient(90deg,#f59e0b,#fb923c)',
                            iconFg: '#f59e0b',
                            iconBg: 'rgba(245,158,11,0.14)',
                            iconMask: chartIcons.panel,
                            barPercent: detail.total > 0 ? (detail.value / detail.total) * 100 : detail.width,
                            details: [
                                { label: 'Share', value: formatPercent(detail.value, detail.total) },
                                { label: 'Panel total', value: formatNumber(detail.total) },
                                { label: 'Top row', value: detail.topRow ? detail.topRow.label + ' / ' + formatNumber(detail.topRow.value) : '-' }
                            ],
                            meta: detail.panelTitle + ' / ' + detail.panelNote
                        });
                    }

                    if (detail.kind === 'progress-summary') {
                        return renderPopoverCard({
                            kicker: 'Grid Overview',
                            title: detail.panelTitle,
                            value: detail.value,
                            unit: 'items',
                            accent: 'linear-gradient(90deg,#f59e0b,#fb923c)',
                            iconFg: '#f59e0b',
                            iconBg: 'rgba(245,158,11,0.14)',
                            iconMask: chartIcons.panel,
                            details: [
                                { label: 'Rows', value: formatNumber(detail.rowCount) },
                                { label: 'Top row', value: detail.topRow ? detail.topRow.label + ' / ' + formatNumber(detail.topRow.value) : '-' },
                                { label: 'Mode', value: detail.panelNote }
                            ],
                            meta: 'Move across a row to lock the popover to that exact queue item.'
                        });
                    }

                    if (detail.kind === 'donut-slice') {
                        return renderPopoverCard({
                            kicker: 'Pie Slice',
                            title: detail.label,
                            value: detail.value,
                            unit: 'docs',
                            accent: 'linear-gradient(90deg,#7c3aed,#a78bfa)',
                            iconFg: '#7c3aed',
                            iconBg: 'rgba(139,92,246,0.14)',
                            iconMask: chartIcons.donut,
                            barPercent: detail.total > 0 ? (detail.value / detail.total) * 100 : 0,
                            barFill: detail.color,
                            details: [
                                { label: 'Share', value: formatPercent(detail.value, detail.total) },
                                { label: 'Mix total', value: formatNumber(detail.total) },
                                { label: 'Scope', value: detail.panelNote }
                            ],
                            meta: detail.panelTitle + ' / workload category spotlight'
                        });
                    }

                    if (detail.kind === 'donut-summary') {
                        return renderPopoverCard({
                            kicker: 'Pie Overview',
                            title: detail.panelTitle,
                            value: detail.value,
                            unit: 'docs',
                            accent: 'linear-gradient(90deg,#7c3aed,#a78bfa)',
                            iconFg: '#7c3aed',
                            iconBg: 'rgba(139,92,246,0.14)',
                            iconMask: chartIcons.donut,
                            details: [
                                { label: 'Largest slice', value: detail.dominant ? String(detail.dominant.label || '') + ' / ' + formatNumber(detail.dominant.value || 0) : '-' },
                                { label: 'Scope', value: detail.panelNote || 'Active workload' }
                            ],
                            meta: 'Hover the donut or legend to inspect a specific ARTA category.'
                        });
                    }

                    if (detail.kind === 'bar-column') {
                        return renderPopoverCard({
                            kicker: 'Bar Detail',
                            title: detail.label,
                            value: detail.value,
                            unit: 'docs',
                            accent: 'linear-gradient(90deg,#2f7de1,#60a5fa)',
                            iconFg: '#2f7de1',
                            iconBg: 'rgba(47,125,225,0.14)',
                            iconMask: chartIcons.bar,
                            barPercent: detail.peak && detail.peak.value > 0 ? (detail.value / detail.peak.value) * 100 : detail.height,
                            barFill: detail.color,
                            details: [
                                { label: 'Scope share', value: formatPercent(detail.value, detail.total) },
                                { label: 'Peak bar', value: detail.peak ? detail.peak.label + ' / ' + formatNumber(detail.peak.value) : '-' },
                                { label: 'Current scope', value: detail.panelNote }
                            ],
                            meta: detail.panelTitle + ' / snapped to the active column'
                        });
                    }

                    if (detail.kind === 'bar-summary') {
                        return renderPopoverCard({
                            kicker: 'Bar Overview',
                            title: detail.panelTitle,
                            value: detail.value,
                            unit: 'docs',
                            accent: 'linear-gradient(90deg,#2f7de1,#60a5fa)',
                            iconFg: '#2f7de1',
                            iconBg: 'rgba(47,125,225,0.14)',
                            iconMask: chartIcons.bar,
                            details: [
                                { label: 'Columns', value: formatNumber(detail.columnCount) },
                                { label: 'Peak bar', value: detail.peak ? detail.peak.label + ' / ' + formatNumber(detail.peak.value) : '-' },
                                { label: 'Scope', value: detail.panelNote }
                            ],
                            meta: 'Hover a bar to lock onto that queue status.'
                        });
                    }

                    if (detail.kind === 'spline-point') {
                        return renderPopoverCard({
                            kicker: 'Spline Point',
                            title: detail.seriesLabel + ' / ' + detail.label,
                            value: detail.value,
                            unit: 'events',
                            accent: 'linear-gradient(90deg,#0d9488,#2dd4bf)',
                            iconFg: '#0d9488',
                            iconBg: 'rgba(13,148,136,0.14)',
                            iconMask: chartIcons.spline,
                            barPercent: detail.seriesMax > 0 ? (detail.value / detail.seriesMax) * 100 : 0,
                            barFill: detail.color,
                            details: [
                                { label: 'Previous', value: formatNumber(detail.previousValue) },
                                { label: 'Change', value: signedValue(detail.delta) },
                                { label: 'Day total', value: formatNumber(detail.dayTotal) }
                            ],
                            meta: detail.panelTitle + ' / ' + detail.panelNote
                        });
                    }

                    if (detail.kind === 'spline-summary') {
                        return renderPopoverCard({
                            kicker: 'Spline Overview',
                            title: detail.panelTitle,
                            value: detail.value,
                            unit: 'events',
                            accent: 'linear-gradient(90deg,#0d9488,#2dd4bf)',
                            iconFg: '#0d9488',
                            iconBg: 'rgba(13,148,136,0.14)',
                            iconMask: chartIcons.spline,
                            details: [
                                { label: 'Strongest series', value: detail.strongest ? detail.strongest.label + ' / ' + formatNumber(detail.strongest.total) : '-' },
                                { label: 'Window', value: '7 days' }
                            ],
                            meta: 'Move inside the plot area to snap to the nearest workflow point.'
                        });
                    }

                    return '';
                }

                /* â”€â”€ Count-up animation â”€â”€ */
                function animateCountUp(el, targetNum) {
                    if (!el || isNaN(targetNum)) return;
                    const duration = Math.min(400, 80 + targetNum * 18);
                    const startTs = performance.now();
                    function step(now) {
                        const prog = Math.min((now - startTs) / duration, 1);
                        const eased = 1 - Math.pow(1 - prog, 3);
                        el.textContent = String(Math.round(eased * targetNum));
                        if (prog < 1) {
                            requestAnimationFrame(step);
                        } else {
                            el.textContent = String(targetNum);
                            el.classList.add('is-counting');
                            setTimeout(function(){ el.classList.remove('is-counting'); }, 200);
                        }
                    }
                    requestAnimationFrame(step);
                }

                /* â”€â”€ Spawn entry sparkles â”€â”€ */
                function spawnSparkles(x, y) {
                    const count = 6;
                    for (let i = 0; i < count; i++) {
                        const s = document.createElement('div');
                        s.className = 'dash-popover-sparkle';
                        const angle = (Math.PI * 2 * i / count) + (Math.random() * 0.5);
                        const dist  = 24 + Math.random() * 16;
                        const sx = Math.round(Math.cos(angle) * dist);
                        const sy = Math.round(Math.sin(angle) * dist);
                        s.style.cssText = 'left:' + x + 'px;top:' + y + 'px;background:' + sparkleColors[i % sparkleColors.length] + ';--sx:' + sx + 'px;--sy:' + sy + 'px;';
                        document.body.appendChild(s);
                        s.addEventListener('animationend', function() { s.remove(); }, { once: true });
                    }
                }

                /* â”€â”€ Show / hide â”€â”€ */
                function showPopover(target, x, y, context) {
                    const nextKey = context && context.key ? String(context.key) : '';
                    if (activeTarget === target && activePopoverKey === nextKey && isVisible) return;
                    activeTarget = target;
                    activePopoverKey = nextKey;
                    isVisible = true;

                    /* Build content */
                    let html = '';
                    if (target.classList.contains('stat-card')) {
                        html = buildStatCardContent(target, context);
                    } else if (target.closest('.chart-panel') || target.classList.contains('chart-panel')) {
                        const panel = target.classList.contains('chart-panel') ? target : target.closest('.chart-panel');
                        html = buildChartPanelContent(panel, context);
                    } else if (target.classList.contains('panel') && target.querySelector('.progress-list')) {
                        html = buildChartPanelContent(target, context);
                    }

                    if (!html) { hidePopover(); return; }
                    popoverInner.innerHTML = html;

                    const pr = popover.getBoundingClientRect();
                    const finalX = mouseX + popoverGapX;
                    const finalY = mouseY + popoverGapY;

                    popover.style.left = finalX + 'px';
                    popover.style.top  = finalY + 'px';
                    popover.classList.add('is-visible');

                    /* Animate count-up value */
                    const valueEl = popoverInner.querySelector('.dash-popover-value[data-target]');
                    if (valueEl) {
                        animateCountUp(valueEl, parseInt(valueEl.getAttribute('data-target'), 10) || 0);
                    }

                    /* Cursor rings */
                    cursorDot.classList.add('is-active','is-hovering');
                    cursorRing.classList.add('is-active','is-hovering');

                    spawnSparkles(x, y);
                }

                function setNativeCursorSuppressed() {}

                function hidePopover() {
                    if (!isVisible) return;
                    isVisible = false;
                    activeTarget = null;
                    activePopoverKey = '';
                    setCursorIndicatorPosition(mouseX, mouseY, true);
                    setNativeCursorSuppressed(false);
                    popover.classList.remove('is-visible','is-flipped-x','is-flipped-y');
                    cursorDot.classList.remove('is-hovering');
                    cursorRing.classList.remove('is-hovering');
                }

                /* â”€â”€ Target detection â”€â”€ */
                function getHoverTarget(el) {
                    if (!el) return null;
                    if (typeof el.closest !== 'function') {
                        el = el.parentElement || null;
                    }
                    if (!el || typeof el.closest !== 'function') return null;
                    /* Stat cards â€” excluding clickable link navigation clicks */
                    const statCard = el.closest('.stat-card');
                    if (statCard) return statCard;
                    /* Chart panel articles */
                    const chartPanel = el.closest('.chart-panel');
                    if (chartPanel) return chartPanel;
                    /* Progress/insight panels */
                    const panel = el.closest('.card.panel');
                    if (panel && !panel.classList.contains('chart-panel') && panel.querySelector('.progress-list')) return panel;
                    return null;
                }

                /* â”€â”€ Global mouse events â”€â”€ */
                document.addEventListener('mousemove', function(e) {
                    mouseX = e.clientX;
                    mouseY = e.clientY;

                    const target = getHoverTarget(e.target);
                    const context = resolvePopoverContext(target, e.target, mouseX, mouseY);

                    if (target) {
                        const shouldSnapToSplinePoint = Boolean(context && context.kind === 'spline-point' && context.snap);
                        const shouldUseContextSnap = shouldSnapToSplinePoint || (!useFreeHoverMovement && context && context.snap);
                        const px = shouldUseContextSnap ? context.snap.x : mouseX;
                        const py = shouldUseContextSnap ? context.snap.y : mouseY;
                        const useCursorOffsets = !shouldUseContextSnap;
                        setNativeCursorSuppressed(true);
                        setCursorIndicatorPosition(px, py, useCursorOffsets);
                        showPopover(target, px, py, context);
                        updatePositions(px, py, useCursorOffsets);
                    } else {
                        hidePopover();
                        cursorDot.classList.remove('is-active');
                        cursorRing.classList.remove('is-active');
                        /* Reset positions so it follows mouse when not snapping */
                        setCursorIndicatorPosition(mouseX, mouseY, true);
                    }
                }, { passive: true });

                document.addEventListener('mouseenter', function(e) {
                    const target = getHoverTarget(e.target);
                    if (target) {
                        setNativeCursorSuppressed(true);
                        cursorDot.classList.add('is-active');
                        cursorRing.classList.add('is-active');
                    }
                }, true);

                document.addEventListener('mouseleave', function(e) {
                    if (!e.relatedTarget) {
                        hidePopover();
                        cursorDot.classList.remove('is-active','is-hovering');
                        cursorRing.classList.remove('is-active','is-hovering');
                    }
                }, true);

                /* Touch devices: suppress entirely */
                window.addEventListener('touchstart', function() {
                    cancelAnimationFrame(rafId);
                    cursorDot.style.display = 'none';
                    cursorRing.style.display = 'none';
                    popover.style.display = 'none';
                }, { once: true });

            })();
            /* â•â• end dash popover â•â• */

            /* â•â• Mobile Nav Drawer Drawer â•â• */
            (function() {
                const navToggle = document.querySelector('.mobile-nav-toggle');
                const backdrop = document.querySelector('.sidebar-backdrop');
                const sidebarHost = document.querySelector('.sidebar-host');
                const mobileDrawerQuery = window.matchMedia('(max-width: 900px)');
                
                if (!navToggle || !backdrop || !sidebarHost) return;

                function syncNavToggleState() {
                    navToggle.setAttribute('aria-expanded', String(sidebarHost.classList.contains('is-open')));
                }
                
                function openDrawer() {
                    if (!mobileDrawerQuery.matches) {
                        closeDrawer();
                        return;
                    }
                    sidebarHost.classList.add('is-open');
                    backdrop.classList.add('is-open');
                    document.body.classList.add('mobile-nav-open');
                    syncNavToggleState();
                }
                
                function closeDrawer() {
                    sidebarHost.classList.remove('is-open');
                    backdrop.classList.remove('is-open');
                    document.body.classList.remove('mobile-nav-open');
                    syncNavToggleState();
                }

                function syncDrawerMode() {
                    if (!mobileDrawerQuery.matches) {
                        closeDrawer();
                    } else {
                        syncNavToggleState();
                    }
                }
                
                navToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (!mobileDrawerQuery.matches) {
                        closeDrawer();
                        return;
                    }
                    if (sidebarHost.classList.contains('is-open')) {
                        closeDrawer();
                    } else {
                        openDrawer();
                    }
                });
                
                backdrop.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeDrawer();
                });
                
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sidebarHost.classList.contains('is-open')) {
                        closeDrawer();
                    }
                });

                if (typeof mobileDrawerQuery.addEventListener === 'function') {
                    mobileDrawerQuery.addEventListener('change', syncDrawerMode);
                } else if (typeof mobileDrawerQuery.addListener === 'function') {
                    mobileDrawerQuery.addListener(syncDrawerMode);
                }

                window.addEventListener('resize', syncDrawerMode, { passive: true });
                syncDrawerMode();
            })();
            /* â•â• end mobile nav â•â• */


        })();
    </script>
