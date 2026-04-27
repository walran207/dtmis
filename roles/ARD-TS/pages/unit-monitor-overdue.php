<?php
$roleBasePath = dirname(__DIR__);
$roleName = 'ARD_TS';
$initialsFallback = 'AT';
$pageTitle = 'Unit Monitor (Overdue / At-Risk) | DENR Region XII eDATS';
$activeMenu = 'unit_monitor_overdue';
$brandSubtitle = 'ARD TS Portal';
$pageHeading = 'Unit Monitor (Overdue / At-Risk)';
$pageSubtitle = 'Overdue and at-risk queue visibility by staff.';
$searchPlaceholder = 'Search Tracking ID or subject';
$kpiCards = [
    ['label' => 'Pending Receive', 'icon' => 'orange', 'value' => '0'],
    ['label' => 'Due Today / Due Soon', 'icon' => 'blue', 'value' => '0', 'pill' => 'ARTA'],
    ['label' => 'Overdue', 'icon' => 'violet', 'value' => '0'],
    ['label' => 'At-Risk Violations', 'icon' => 'green', 'value' => '0'],
];
$panels = [
    [
        'title' => 'ARTA Risk Snapshot',
        'rows' => [
            ['label' => 'Pending Receive', 'width' => '0%', 'value' => '0'],
            ['label' => 'Due Today / Due Soon', 'width' => '0%', 'value' => '0'],
            ['label' => 'Overdue', 'width' => '0%', 'value' => '0'],
            ['label' => 'At-Risk Violations', 'width' => '0%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'Overdue Monitoring Focus',
        'chips' => ['Prioritize overdue first', 'Validate current holder', 'Escalate long-running delays', 'Reroute blocked items'],
    ],
];
$tableTitle = 'Unit Monitor';
$tableColumns = json_decode('["Tracking ID","Subject","Document Type (+ ARTA)","Current Holder","Date Received","Time Remaining","Status","Quick Actions"]', true);
$tableRows = json_decode('[{"value":["R12-DC-2026-001","Unit Monitor (Overdue / At-Risk) - Priority","Action Docket (Complex)","ARD TS","2026-03-14 08:12","08h 20m","For Action","View | Receive | Forward"],"Count":8},{"value":["R12-DC-2026-002","Unit Monitor (Overdue / At-Risk) - Standard","Memo (Simple)","ARD TS","2026-03-13 14:45","1d 04h","In Queue","View | Forward"],"Count":8},{"value":["R12-DC-2026-003","Unit Monitor (Overdue / At-Risk) - Returned","Request Info (Overdue)","ARD TS","2026-03-11 09:10","Overdue by 1d","Urgent","View | Receive | Forward"],"Count":8}]', true);
$pageActions = json_decode('["Monitor","Assign","Reroute"]', true);
$stickyActions = json_decode('["Reroute"]', true);
$queueControlsPlacement = 'table_card';
$showStatusCategoryFilter = false;
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"Unit Monitor (Overdue / At-Risk) has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
