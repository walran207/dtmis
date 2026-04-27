<?php
$roleBasePath = dirname(__DIR__);
$roleName = 'PENRO';
$initialsFallback = 'PE';
$pageTitle = 'Returned from RECORDS-UNIT | DENR Region XII eDATS';
$activeMenu = 'inbox_returned_pacdo';
$brandSubtitle = 'PENRO Portal';
$pageHeading = 'Returned from RECORDS-UNIT';
$pageSubtitle = 'RECORDS-UNIT-returned items requiring PENRO routing down.';
$searchPlaceholder = 'Search Tracking ID or subject';
$kpiCards = json_decode('[{"label":"Pending Items","icon":"orange","value":"18"},{"label":"Due Soon","icon":"violet","value":"7","pill":"ARTA"},{"label":"Completed Today","icon":"green","value":"12"},{"label":"Returned","icon":"blue","value":"3"}]', true);
$panels = [
    [
        'title' => 'RECORDS-UNIT Return Processing Mix',
        'rows' => [
            ['label' => 'Pending Receive', 'width' => '0%', 'value' => '0'],
            ['label' => 'Pending Forward', 'width' => '0%', 'value' => '0'],
            ['label' => 'Pending Approval', 'width' => '0%', 'value' => '0'],
            ['label' => 'Returned', 'width' => '0%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'ARTA Risk Follow-Up',
        'rows' => [
            ['label' => 'Due Soon / Due Today', 'width' => '0%', 'value' => '0'],
            ['label' => 'Overdue', 'width' => '0%', 'value' => '0'],
            ['label' => 'Pending', 'width' => '0%', 'value' => '0'],
            ['label' => 'Forwarded events', 'width' => '0%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'Returned Queue Focus',
        'chips' => [
            'Validate RECORDS-UNIT return remarks',
            'Prioritize overdue returned items',
            'Route down to correct office quickly',
            'Log disposition notes in tracking slip',
        ],
    ],
];
$tableTitle = 'Returned from RECORDS-UNIT Queue';
$tableColumns = json_decode('["Tracking ID","Subject","Document Type (+ ARTA)","Current Holder","Date Received","Time Remaining","Status","Quick Actions"]', true);
$tableRows = json_decode('[{"value":["R12-PE-2026-001","Returned from RECORDS-UNIT - Priority","Action Docket (Complex)","PENRO","2026-03-14 08:12","08h 20m","For Action","View | Receive | Forward"],"Count":8},{"value":["R12-PE-2026-002","Returned from RECORDS-UNIT - Standard","Memo (Simple)","PENRO","2026-03-13 14:45","1d 04h","In Queue","View | Forward"],"Count":8},{"value":["R12-PE-2026-003","Returned from RECORDS-UNIT - Returned","Request Info (Overdue)","PENRO","2026-03-11 09:10","Overdue by 1d","Urgent","View | Receive | Forward"],"Count":8}]', true);
$pageActions = json_decode('["View","Receive","Forward"]', true);
$queueControlsPlacement = 'table_card';
$dateFilterPlacement = 'table_card';
$stickyActions = json_decode('["Receive","Forward","Return / Request Info"]', true);
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"Returned from RECORDS-UNIT has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';


