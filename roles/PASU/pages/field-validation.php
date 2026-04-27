<?php
$roleBasePath = dirname(__DIR__);
$roleName = 'PASU';
$initialsFallback = 'PA';
$pageTitle = 'Field Validation | DENR Region XII eDATS';
$activeMenu = 'field_validation';
$brandSubtitle = 'PASU Portal';
$pageHeading = 'Field Validation';
$pageSubtitle = 'Site validation checklist, evidence uploads, and recommendation drafting.';
$searchPlaceholder = 'Search Tracking ID or subject';
$kpiCards = json_decode('[{"label":"Pending Items","icon":"orange","value":"18"},{"label":"Due Soon","icon":"violet","value":"7","pill":"ARTA"},{"label":"Completed Today","icon":"green","value":"12"},{"label":"Returned","icon":"blue","value":"3"}]', true);
$panels = json_decode('[{"rows":[{"label":"Pending Receive","width":"39%","value":"7"},{"label":"For Action","width":"50%","value":"9"},{"label":"Returned / Request Info","width":"11%","value":"2"}],"title":"Field Validation Queue Mix"},{"rows":[{"label":"Received events","width":"66%","value":"24"},{"label":"Forwarded events","width":"22%","value":"8"},{"label":"Reroute events","width":"12%","value":"4"}],"title":"Tracking Slip Signals"},{"chips":["Remarks encoded","ARTA checked","Holder verified","Attachment reviewed"],"title":"Activity Log Focus"}]', true);
$tableTitle = 'Field Validation Queue';
$tableColumns = json_decode('["Tracking ID","Subject","Document Type (+ ARTA)","Current Holder","Date Received","Time Remaining","Status","Quick Actions"]', true);
$tableRows = json_decode('[{"value":["R12-PA-2026-001","Field Validation - Priority","Action Docket (Complex)","PASU","2026-03-14 08:12","08h 20m","For Action","View | Receive | Forward"],"Count":8},{"value":["R12-PA-2026-002","Field Validation - Standard","Memo (Simple)","PASU","2026-03-13 14:45","1d 04h","In Queue","View | Forward"],"Count":8},{"value":["R12-PA-2026-003","Field Validation - Returned","Request Info (Overdue)","PASU","2026-03-11 09:10","Overdue by 1d","Urgent","View | Receive | Forward"],"Count":8}]', true);
$pageActions = json_decode('["View","Receive","Forward"]', true);
$stickyActions = json_decode('["Receive","Forward","Return / Request Info"]', true);
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"Field Validation has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
