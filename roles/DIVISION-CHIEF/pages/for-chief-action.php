<?php
require_once dirname(__DIR__, 3) . '/config/app.php';

$roleBasePath = dirname(__DIR__);
$roleName = 'Division Chief';
$initialsFallback = 'DC';
$pageTitle = 'For Chief Action | DENR Region XII eDATS';
$activeMenu = 'for_chief_action';
$brandSubtitle = 'Division Chief Portal';
$pageHeading = 'For Chief Action';
$pageSubtitle = 'Chief review queue for approval, return, and endorsement.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=pending_receive_action');
$kpiCards = [
    ['label' => 'Pending Received', 'icon' => 'orange', 'value' => '0'],
    ['label' => 'Pending Approval', 'icon' => 'blue', 'value' => '0'],
    ['label' => 'Completed', 'icon' => 'violet', 'value' => '0'],
    ['label' => 'Pending Forward', 'icon' => 'green', 'value' => '0'],
];
$panels = [
    [
        'title' => 'Chief Action Pending Mix',
        'rows' => [
            ['label' => 'Pending Received', 'width' => '0%', 'value' => '0'],
            ['label' => 'Pending Approval', 'width' => '0%', 'value' => '0'],
            ['label' => 'Pending Sign', 'width' => '0%', 'value' => '0'],
            ['label' => 'Pending Forward', 'width' => '0%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'ARTA Priority Snapshot',
        'rows' => [
            ['label' => 'Due Today / Due Soon', 'width' => '0%', 'value' => '0'],
            ['label' => 'Overdue', 'width' => '0%', 'value' => '0'],
            ['label' => 'Pending Approval', 'width' => '0%', 'value' => '0'],
            ['label' => 'Pending Sign', 'width' => '0%', 'value' => '0'],
        ],
    ],
    [
        'title' => 'Chief Action Checklist',
        'chips' => ['Review ARTA urgency', 'Validate remarks and attachments', 'Approve or return with reason', 'Forward to correct office'],
    ],
];
$tableTitle = 'For Chief Action Queue';
$tableColumns = json_decode('["Tracking ID","Subject","Document Type (+ ARTA)","Date Created","Date Received","Time Remaining","Status","Quick Actions"]', true);
$tableRows = json_decode('[{"value":["R12-DC-2026-001","For Chief Action - Priority","Action Docket (Complex)","2026-03-14 07:55","2026-03-14 08:12","08h 20m","For Action","View | Receive | Approve | Forward | Send Back to ARD | Reroute"],"Count":8},{"value":["R12-DC-2026-002","For Chief Action - Standard","Memo (Simple)","2026-03-13 10:30","2026-03-13 14:45","1d 04h","In Queue","View | Receive | Approve | Forward | Send Back to ARD | Reroute"],"Count":8},{"value":["R12-DC-2026-003","For Chief Action - Returned","Request Info (Overdue)","2026-03-11 08:05","2026-03-11 09:10","Overdue by 1d","Urgent","View | Receive | Approve | Forward | Send Back to ARD | Reroute"],"Count":8}]', true);
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
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"For Chief Action has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';

