<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'PENRO_DIVISION';
$forwardedSearchInitialsFallback = 'DC';
$forwardedSearchActiveMenu = 'search';
$forwardedSearchBrandSubtitle = 'PENRO Division Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as PENRO Division.';
$forwardedSearchTableTitle = 'PENRO Division Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';
