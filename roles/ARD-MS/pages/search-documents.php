<?php
$roleBasePath = dirname(__DIR__);
$roleName = 'ARD_MS';
$initialsFallback = 'AM';
$pageTitle = 'Archive / Search | DENR Region XII eDATS';
$activeMenu = 'search';
$brandSubtitle = 'ARD MS Portal';
$pageHeading = 'Archive / Search';
$pageSubtitle = 'Search completed documents archived after release is received back by the originating office.';
$searchPlaceholder = 'Search completed tracking ID, subject, office, or holder';
$kpiCards = json_decode('[{"label":"Received","icon":"blue","value":"0"},{"label":"Approved","icon":"orange","value":"0"},{"label":"Forwarded","icon":"violet","value":"0"},{"label":"Completed","icon":"green","value":"0"}]', true);
$panels = json_decode('[{"rows":[{"label":"Completed","width":"50%","value":"0"},{"label":"Received events","width":"25%","value":"0"},{"label":"Forwarded events","width":"25%","value":"0"}],"title":"Archive Snapshot"},{"chips":["Completed only","Searchable archive","Date-range filter"],"title":"Archive Notes"}]', true);
$tableTitle = 'Completed Document Archive';
$tableColumns = json_decode('["Tracking ID","Source","Subject","Document Type (+ ARTA)","Status","Date Created"]', true);
$tableRows = [];
$pageActions = [];
$stickyActions = [];
$queueControlsPlacement = 'table_card';
$enableTableCardFilterControls = true;
$showStatusCategoryFilter = false;
$dateFilterPlacement = 'table_card';
$showQrReceiveScanner = false;
$showCompletedQueueRowsOnly = true;
$showStrictCompletedOnly = true;
$notifications = json_decode('[{"title":"Archive synced","timeLabel":"Now","message":"Completed documents archive refreshed.","unread":true,"datetime":"2026-03-14T10:30:00"},{"title":"Tracking slip updated","timeLabel":"48m ago","message":"Custody timeline received a new completion event.","unread":true,"datetime":"2026-03-14T09:42:00"},{"title":"Archive reminder","timeLabel":"2h ago","message":"Use date range and search to locate historical records.","unread":false,"datetime":"2026-03-14T08:05:00"}]', true);

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
