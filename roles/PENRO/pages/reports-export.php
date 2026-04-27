<?php
$roleBasePath = dirname(__DIR__);
$roleName = 'PENRO';
$initialsFallback = 'PE';
$pageTitle = 'Reports / Export | DENR Region XII eDATS';
$activeMenu = 'reports';
$brandSubtitle = 'PENRO Portal';
$pageHeading = 'Reports / Export';
$pageSubtitle = 'Generate provincial reports and export summaries.';
$searchPlaceholder = 'Search Tracking ID or subject';
$kpiCards = json_decode('[{"label":"Received","icon":"blue","value":"0"},{"label":"Approved","icon":"orange","value":"0"},{"label":"Forwarded","icon":"violet","value":"0"},{"label":"Completed","icon":"green","value":"0"}]', true);
$panels = json_decode('[{"rows":[{"label":"Received events","width":"34%","value":"0"},{"label":"Approved events","width":"33%","value":"0"},{"label":"Forwarded events","width":"33%","value":"0"}],"title":"Reports / Export Activity Mix"},{"chips":["Date range filtered","Live counters","Export-ready"],"title":"Report Notes"}]', true);
$tableTitle = 'Reports / Export';
$tableColumns = json_decode('["Tracking ID","Subject","Document Type (+ ARTA)","Status","Date Received"]', true);
$tableRows = [];
$pageActions = [];
$stickyActions = [];
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$enableBulkRowExport = true;
$showStatusCategoryFilter = false;
$dateFilterPlacement = 'table_card';
$showQrReceiveScanner = false;
$notifications = json_decode('[{"title":"Queue updated","timeLabel":"Now","message":"Reports / Export has a new routed document.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"ARTA reminder","timeLabel":"2h ago","message":"Review due-soon and overdue items.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
