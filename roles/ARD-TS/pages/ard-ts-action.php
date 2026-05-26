<?php
require_once dirname(__DIR__, 3) . '/config/app.php';

$roleBasePath = dirname(__DIR__);
$roleName = 'ARD_TS';
$initialsFallback = 'AT';
$pageTitle = 'For ARD TS Action | DENR Region XII DTMIS';
$activeMenu = 'for_chief_action';
$brandSubtitle = 'ARD TS Portal';
$pageHeading = 'For ARD TS Action';
$pageSubtitle = 'ARD TS review queue for approval, division assignment, and escalation.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=pending_receive_action');
$kpiCards = [];
$panels = [];
$tableTitle = 'For ARD TS Action Queue';
$tableColumns = json_decode('["Tracking ID","Subject","Document Type (+ ARTA)","Date Created","Date Received","Time Remaining","Status","Quick Actions"]', true);
$tableRows = json_decode('[{"value":["R12-DC-2026-001","For ARD TS Action - Priority","Action Docket (Complex)","2026-03-14 07:55","2026-03-14 08:12","08h 20m","For Action","View | Receive | Approve | Forward | Send Back to ARD | Reroute"],"Count":8},{"value":["R12-DC-2026-002","For ARD TS Action - Standard","Memo (Simple)","2026-03-13 10:30","2026-03-13 14:45","1d 04h","In Queue","View | Receive | Approve | Forward | Send Back to ARD | Reroute"],"Count":8},{"value":["R12-DC-2026-003","For ARD TS Action - Returned","Request Info (Overdue)","2026-03-11 08:05","2026-03-11 09:10","Overdue by 1d","Urgent","View | Receive | Approve | Forward | Send Back to ARD | Reroute"],"Count":8}]', true);
$pageActions = json_decode('["Receive","Approve / Forward","Reroute","Return / Request Info"]', true);
$stickyActions = json_decode('["Receive","Approve","Forward","Reroute","Return"]', true);
$enableQueueRerouteAction = true;
$queueControlsPlacement = 'table_card';
$filterRouteExistingAttachmentsToPreparedResponse = true;
$statusFilterOptions = [
    ['value' => '', 'label' => 'All Status Categories'],
    ['value' => 'awaiting_receive', 'label' => 'Awaiting Receive'],
    ['value' => 'received', 'label' => 'Received'],
    ['value' => 'approved', 'label' => 'Approved'],
    ['value' => 'pending_forward', 'label' => 'Pending Forward'],
    ['value' => 'forward', 'label' => 'Forwarded'],
];
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"For ARD TS Action has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
