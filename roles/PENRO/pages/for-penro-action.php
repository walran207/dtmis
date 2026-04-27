<?php
$roleBasePath = dirname(__DIR__);
$roleName = 'PENRO';
$initialsFallback = 'PE';
$pageTitle = 'For PENRO Action | DENR Region XII eDATS';
$activeMenu = 'inbox_for_penro_action';
$brandSubtitle = 'PENRO Portal';
$pageHeading = 'For PENRO Action';
$pageSubtitle = 'PASU/CENRO documents requiring PENRO decision or endorsement.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=office_action');
$kpiCards = json_decode('[{"label":"Pending Items","icon":"orange","value":"18"},{"label":"Due Soon","icon":"violet","value":"7","pill":"ARTA"},{"label":"Completed Today","icon":"green","value":"12"},{"label":"Returned","icon":"blue","value":"3"}]', true);
$panels = json_decode('[{"rows":[{"label":"Pending Receive","width":"39%","value":"7"},{"label":"For Action","width":"50%","value":"9"},{"label":"Returned / Request Info","width":"11%","value":"2"}],"title":"For PENRO Action Queue Mix"},{"rows":[{"label":"Received events","width":"66%","value":"24"},{"label":"Forwarded events","width":"22%","value":"8"},{"label":"Reroute events","width":"12%","value":"4"}],"title":"Tracking Slip Signals"},{"chips":["Remarks encoded","ARTA checked","Holder verified","Attachment reviewed"],"title":"Activity Log Focus"}]', true);
$tableTitle = 'For PENRO Action Queue';
$tableColumns = json_decode('["Tracking ID","Subject","Document Type (+ ARTA)","Current Holder","Date Received","Time Remaining","Status","Quick Actions"]', true);
$tableRows = json_decode('[{"value":["R12-PE-2026-001","For PENRO Action - Priority","Action Docket (Complex)","PENRO","2026-03-14 08:12","08h 20m","For Action","View | Receive | Forward"],"Count":8},{"value":["R12-PE-2026-002","For PENRO Action - Standard","Memo (Simple)","PENRO","2026-03-13 14:45","1d 04h","In Queue","View | Forward"],"Count":8},{"value":["R12-PE-2026-003","For PENRO Action - Returned","Request Info (Overdue)","PENRO","2026-03-11 09:10","Overdue by 1d","Urgent","View | Receive | Forward"],"Count":8}]', true);
$pageActions = json_decode('["View","Receive","Forward"]', true);
$stickyActions = json_decode('["Receive","Forward","Return / Request Info"]', true);
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"For PENRO Action has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
