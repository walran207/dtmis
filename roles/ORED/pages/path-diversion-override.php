<?php
require_once dirname(__DIR__, 3) . '/config/app.php';

$roleBasePath = dirname(__DIR__);
$roleName = 'ORED';
$initialsFallback = 'OR';
$pageTitle = 'Path Diversion / Override | DENR Region XII eDATS';
$activeMenu = 'path_diversion_override';
$brandSubtitle = 'ORED Portal';
$pageHeading = 'Path Diversion / Override';
$pageSubtitle = 'Executive reroute and override desk for exception-based routing decisions.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=outbox');
$kpiCards = [
    ['label' => 'Validate Reason', 'icon' => 'orange', 'value' => '0'],
    ['label' => 'Override Holder', 'icon' => 'violet', 'value' => '0'],
    ['label' => 'Return for Justification', 'icon' => 'green', 'value' => '0'],
    ['label' => 'Add Remarks', 'icon' => 'blue', 'value' => '0'],
];
$panels = [
    [
        'title' => 'Path Diversion / Override Queue Mix',
        'rows' => [
            ['label' => 'Pending', 'width' => '45%', 'value' => '0'],
            ['label' => 'Due Soon / Overdue', 'width' => '30%', 'value' => '0'],
            ['label' => 'Returned', 'width' => '25%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'Diversion Control Signals',
        'rows' => [
            ['label' => 'Received events', 'width' => '50%', 'value' => '0'],
            ['label' => 'Forwarded events', 'width' => '30%', 'value' => '0'],
            ['label' => 'Reroute events', 'width' => '20%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'Override Review Focus',
        'chips' => ['Reason validated', 'ARTA impact checked', 'Custody holder verified', 'Override remarks encoded'],
    ],
];
$tableTitle = 'Reroute / Override';
$tableColumns = json_decode('["Tracking ID","From","Document Type","Subject","Current Holder","REOUTE TO","Status","Remarks","Date Created","Quick Actions"]', true);
$tableRows = [];
$pageActions = json_decode('["Reroute","Override"]', true);
$stickyActions = json_decode('[]', true);
$enableQueueRerouteAction = true;
$rerouteOfficeLevelFilter = '';
$hideQueueDocumentOnlyActions = true;
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$dateFilterPlacement = 'table_card';
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"Path Diversion / Override has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
