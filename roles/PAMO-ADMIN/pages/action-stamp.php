<?php
declare(strict_types=1);

$roleBasePath = dirname(__DIR__);
$roleName = 'PAMO_ADMIN';
$initialsFallback = 'PC';
$pageTitle = 'Action Stamp Workspace | DENR Region XII eDATS';
$activeMenu = 'action_stamp';
$brandSubtitle = 'PAMO Admin Portal';
$pageHeading = 'Action Stamp Workspace';
$pageSubtitle = 'Print RECEIVED and RELEASED stamp layouts for ADMIN-PAMO Admin.';
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

