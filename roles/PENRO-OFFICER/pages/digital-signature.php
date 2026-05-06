<?php
$roleBasePath = dirname(__DIR__);
$roleName = 'PENRO_OFFICER';
$initialsFallback = 'OR';
$pageTitle = 'Digital Signature Workspace | DENR Region XII eDATS';
$activeMenu = 'digital_signature';
$brandSubtitle = 'PENRO Officer Portal';
$pageHeading = 'Digital Signature Workspace';
$pageSubtitle = 'Upload and position the digital signature block for PENRO Officer signing.';
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
