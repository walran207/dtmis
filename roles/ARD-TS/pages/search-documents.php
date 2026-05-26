<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'ARD_TS';
$forwardedSearchInitialsFallback = 'AT';
$forwardedSearchActiveMenu = 'search';
$forwardedSearchBrandSubtitle = 'ARD TS Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as ARD TS.';
$forwardedSearchTableTitle = 'ARD TS Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';
