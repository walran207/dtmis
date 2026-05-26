<?php
$roleBasePath = dirname(__DIR__);
$roleName = 'CENRO_UNIT';
$initialsFallback = 'SS';
$pageTitle = 'For Staff Action | DENR Region XII DTMIS';
$activeMenu = 'cenro_unit_action';
$brandSubtitle = 'CENRO Unit Portal';
$pageHeading = 'For Staff Action';
$pageSubtitle = 'Assigned records requiring section staff action.';
$searchPlaceholder = 'Search Tracking ID or subject';
$kpiCards = json_decode('[{"label":"Pending Received","icon":"blue","value":"18"},{"label":"Pending Approval","icon":"orange","value":"7"},{"label":"Completed","icon":"violet","value":"12"},{"label":"Pending Forward","icon":"green","value":"3"}]', true);
$panels = json_decode('[{"rows":[{"label":"Pending Received","width":"45%","value":"18"},{"label":"Pending Approval","width":"30%","value":"12"},{"label":"Pending Sign","width":"20%","value":"8"},{"label":"Pending Forward","width":"15%","value":"6"}],"title":"For Staff Action Queue Mix"},{"rows":[{"label":"Received events","width":"55%","value":"24"},{"label":"Approved events","width":"35%","value":"14"},{"label":"Forwarded events","width":"25%","value":"8"},{"label":"Reroute events","width":"12%","value":"4"}],"title":"Staff Workflow Activity Signals"},{"chips":["Receive and validate incoming docs","Prepare approval-ready notes","Coordinate pending signatures","Forward and close ready items"],"title":"Staff Action Focus"}]', true);
$tableTitle = 'For Staff Action Queue';
$tableColumns = json_decode('["Tracking ID","Subject","Document Type (+ ARTA)","Date Created","Date Received","Time Remaining","Status","Quick Actions"]', true);
$tableRows = json_decode('[{"value":["R12-SS-2026-001","Pending Receive - Priority","Action Docket (Complex)","2026-03-14 07:55","2026-03-14 08:12","08h 20m","For Action","View | Receive | Forward"],"Count":8},{"value":["R12-SS-2026-002","Pending Receive - Standard","Memo (Simple)","2026-03-13 10:30","2026-03-13 14:45","1d 04h","In Queue","View | Forward"],"Count":8},{"value":["R12-SS-2026-003","Pending Receive - Returned","Request Info (Overdue)","2026-03-11 08:05","2026-03-11 09:10","Overdue by 1d","Urgent","View | Receive | Forward"],"Count":8}]', true);
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
$pageActions = json_decode('["View","Receive","Forward"]', true);
$stickyActions = json_decode('["Receive","Forward"]', true);
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"Pending Receive has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

$kpiCards = [];
$panels = [];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
