<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'PAMO_UNIT';
$forwardedSearchInitialsFallback = 'SS';
$forwardedSearchActiveMenu = 'search';
$forwardedSearchBrandSubtitle = 'PAMO Unit Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as PAMO Unit.';
$forwardedSearchTableTitle = 'PAMO Unit Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';
