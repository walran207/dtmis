<?php
declare(strict_types=1);

$roleBasePath = dirname(__DIR__);
$roleName = 'ADMIN-RECORDS-UNIT';
$initialsFallback = 'PC';
$pageTitle = 'Action Stamp Workspace | DENR Region XII eDATS';
$activeMenu = 'action_stamp';
$brandSubtitle = 'ADMIN-RECORDS-UNIT Portal';
$pageHeading = 'Action Stamp Workspace';
$pageSubtitle = 'Print RECEIVED and RELEASED stamp layouts for ADMIN-RECORDS-UNIT.';
$searchPlaceholder = 'Search';
$renderStandardContent = false;
$customSectionInclude = dirname(__DIR__, 3) . '/app/modules/records-unit-action-stamp-panel.php';
$enableCharts = false;
$hideHeaderSearch = true;
$stickyActions = [];
$pageActions = [];
$kpiCards = [];
$panels = [];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';

