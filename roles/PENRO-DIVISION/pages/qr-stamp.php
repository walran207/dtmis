<?php
$roleBasePath = dirname(__DIR__);
$roleName = 'PENRO_DIVISION';
$initialsFallback = 'DC';
$pageTitle = 'QR Stamp Workspace | DENR Region XII eDATS';
$activeMenu = 'qr_stamp';
$brandSubtitle = 'PENRO Division Portal';
$pageHeading = 'QR Stamp Workspace';
$pageSubtitle = 'Place and print QR stamps on blank paper sizes.';
$searchPlaceholder = 'Search';
$renderStandardContent = false;
$customSectionInclude = dirname(__DIR__, 3) . '/app/modules/softcopy-qr-stamp-panel.php';
$enableCharts = false;
$hideHeaderSearch = true;
$stickyActions = [];
$pageActions = [];
$kpiCards = [];
$panels = [];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
