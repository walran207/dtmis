<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/app.php';

$roleBasePath = dirname(__DIR__);
$roleName = 'PASU';
$initialsFallback = 'PA';
$pageTitle = 'For PASU Action | DENR Region XII eDATS';
$activeMenu = 'inbox_pending_receive';
$brandSubtitle = 'PASU Portal';
$pageHeading = 'For PASU Action';
$pageSubtitle = 'PASU documents already received and awaiting action.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=for_cenro_action');
$kpiCards = [
    ['label' => 'Pending Received', 'icon' => 'blue', 'value' => '0'],
    ['label' => 'Pending Approval', 'icon' => 'orange', 'value' => '0'],
    ['label' => 'Pending Forward', 'icon' => 'violet', 'value' => '0'],
    ['label' => 'Completed', 'icon' => 'green', 'value' => '0'],
];
$panels = [
    [
        'title' => 'PASU Action Queue Mix',
        'rows' => [
            ['label' => 'Pending Received', 'width' => '0%', 'value' => '0'],
            ['label' => 'Pending Approval', 'width' => '0%', 'value' => '0'],
            ['label' => 'Pending Forward', 'width' => '0%', 'value' => '0'],
            ['label' => 'Completed', 'width' => '0%', 'value' => '0'],
        ],
    ],
];
$tableTitle = 'For PASU Action Queue';
$tableColumns = ['Tracking ID', 'Subject', 'Document Type (+ ARTA)', 'Current Holder', 'Date Received', 'Time Remaining', 'Status', 'Quick Actions'];
$tableRows = [];
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
$pageActions = ['View', 'Receive', 'Approve', 'Forward'];
$stickyActions = ['Receive', 'Approve', 'Forward', 'Return / Request Info'];
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"For PASU Action has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
