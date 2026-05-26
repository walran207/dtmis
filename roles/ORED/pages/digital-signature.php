<?php
$roleBasePath = dirname(__DIR__);
$roleName = 'ORED';
$initialsFallback = 'OR';
$pageTitle = 'Digital Signature Workspace | DENR Region XII DTMIS';
$activeMenu = 'digital_signature';
$brandSubtitle = 'ORED Portal';
$pageHeading = 'Digital Signature Workspace';
$pageSubtitle = 'Upload and position the digital signature block for ORED signing.';
$searchPlaceholder = 'Search';
$renderStandardContent = false;
$customSectionInclude = dirname(__DIR__, 3) . '/app/modules/softcopy-digital-signature-panel.php';
$enableCharts = false;
$hideHeaderSearch = true;
$stickyActions = [];
$pageActions = [];
$kpiCards = [];
$panels = [];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
