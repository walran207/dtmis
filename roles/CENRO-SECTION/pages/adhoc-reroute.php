<?php
require_once dirname(__DIR__, 3) . '/config/app.php';

$roleBasePath = dirname(__DIR__);
$roleName = 'CENRO_SECTION';
$initialsFallback = 'CS';
$pageTitle = 'Ad-Hoc Reroute | DENR Region XII eDATS';
$activeMenu = 'adhoc_reroute';
$brandSubtitle = 'CENRO Section Portal';
$pageHeading = 'CENRO Section Ad-Hoc Reroute';
$pageSubtitle = 'Pull routed documents and reroute to appropriate CENRO Unit with documented reason.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=outbox');
$kpiCards = [
    ['label' => 'Pending Receive', 'icon' => 'orange', 'value' => '0'],
    ['label' => 'Pending Forward', 'icon' => 'blue', 'value' => '0'],
    ['label' => 'Returned', 'icon' => 'violet', 'value' => '0'],
    ['label' => 'Overdue', 'icon' => 'green', 'value' => '0'],
];
$panels = [
    [
        'title' => 'CENRO Section Reroute Queue Mix',
        'rows' => [
            ['label' => 'Pending Receive', 'width' => '0%', 'value' => '0'],
            ['label' => 'Pending Forward', 'width' => '0%', 'value' => '0'],
            ['label' => 'Returned / Request Info', 'width' => '0%', 'value' => '0'],
            ['label' => 'Overdue', 'width' => '0%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'Reroute Tracking Signals',
        'rows' => [
            ['label' => 'Received events', 'width' => '0%', 'value' => '0'],
            ['label' => 'Forwarded events', 'width' => '0%', 'value' => '0'],
            ['label' => 'Reroute events', 'width' => '0%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'Reroute Checklist',
        'chips' => ['Pull from queue before reroute', 'Set clear reroute reason', 'Confirm target CENRO Unit', 'Capture remarks for audit trail'],
    ],
];
$tableTitle = 'Reroute / Override';
$tableColumns = json_decode('["Tracking ID","From","Document Type","Subject","Current Holder","REROUTE TO","Status","Remarks","Date Created","Quick Actions"]', true);
$tableRows = [];
$pageActions = json_decode('["Pull from Queue","Reroute","Add Remarks"]', true);
$stickyActions = [];
$enableQueueRerouteAction = true;
$forceWorkflowActionScope = true;
$hideQueueDocumentOnlyActions = true;
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$dateFilterPlacement = 'table_card';
$statusFilterOptions = [
    ['value' => '', 'label' => 'All Status Categories'],
    ['value' => 'received', 'label' => 'Received / Pulled'],
    ['value' => 'forward', 'label' => 'Forwarded / Rerouted'],
    ['value' => 'for_correction', 'label' => 'Returned / For Revision'],
];
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"CENRO Section reroute desk has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"Reroute reminder","timeLabel":"2h ago","message":"Validate reroute reason before submitting.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
