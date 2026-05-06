<?php
require_once dirname(__DIR__, 3) . '/config/app.php';

$roleBasePath = dirname(__DIR__);
$roleName = 'PENRO_OFFICER';
$initialsFallback = 'CO';
$pageTitle = 'Path Diversion / Override | DENR Region XII eDATS';
$activeMenu = 'path_diversion_override';
$brandSubtitle = 'PENRO Officer Portal';
$pageHeading = 'PENRO Officer Path Diversion';
$pageSubtitle = 'Dedicated reroute desk for PENRO Officer exception routing and bypass decisions.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=outbox');
$kpiCards = [
    ['label' => 'Pull from Queue', 'icon' => 'orange', 'value' => '0'],
    ['label' => 'Reroute Ready', 'icon' => 'violet', 'value' => '0'],
    ['label' => 'Bypass Reviews', 'icon' => 'green', 'value' => '0'],
    ['label' => 'Remarks Logged', 'icon' => 'blue', 'value' => '0'],
];
$panels = [
    [
        'title' => 'PENRO Officer Reroute Mix',
        'rows' => [
            ['label' => 'Pending', 'width' => '40%', 'value' => '0'],
            ['label' => 'Forwarded / Routed Out', 'width' => '35%', 'value' => '0'],
            ['label' => 'Bypass Candidates', 'width' => '25%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'Reroute Control Signals',
        'rows' => [
            ['label' => 'Received events', 'width' => '34%', 'value' => '0'],
            ['label' => 'Forwarded events', 'width' => '33%', 'value' => '0'],
            ['label' => 'Reroute events', 'width' => '33%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'Bypass Checklist',
        'chips' => ['Section first by default', 'Bypass reason required', 'Confirm destination office', 'Capture remarks for audit trail'],
    ],
];
$tableTitle = 'PENRO Officer Reroute / Override';
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
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"PENRO Officer reroute desk has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"Reroute reminder","timeLabel":"2h ago","message":"Review bypass justification before submitting reroute.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
