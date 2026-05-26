<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'Division Chief';
$forwardedSearchInitialsFallback = 'DC';
$forwardedSearchActiveMenu = 'search';
$forwardedSearchBrandSubtitle = 'Division Chief Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as Division Chief.';
$forwardedSearchTableTitle = 'Division Chief Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';
