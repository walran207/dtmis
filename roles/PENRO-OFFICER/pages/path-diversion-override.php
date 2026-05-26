<?php
require_once dirname(__DIR__, 3) . '/config/app.php';

$roleBasePath = dirname(__DIR__);
$roleName = 'PENRO_OFFICER';
$initialsFallback = 'CO';
$pageTitle = 'Path Diversion / Override | DENR Region XII DTMIS';
$activeMenu = 'path_diversion_override';
$brandSubtitle = 'PENRO Officer Portal';
$pageHeading = 'PENRO Officer Path Diversion';
$pageSubtitle = 'Dedicated reroute desk for PENRO Officer exception routing and bypass decisions.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=outbox');
$kpiCards = [];
$panels = [];
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
