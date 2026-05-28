<?php
require_once dirname(__DIR__, 3) . '/config/app.php';

$roleBasePath = $roleBasePath ?? dirname(__DIR__);
$roleName = 'ORED';
$initialsFallback = 'OR';
$pageTitle = 'Path Diversion / Override | DENR Region XII DTMIS';
$activeMenu = 'path_diversion_override';
$brandSubtitle = 'ORED Portal';
$pageHeading = 'Path Diversion / Override';
$pageSubtitle = 'Executive reroute and override desk for exception-based routing decisions.';
$searchPlaceholder = 'Search Tracking ID or subject';
$dashboardLivePath = app_url('actions/dashboard-live.php?scope=outbox');
$kpiCards = [];
$panels = [];
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
