<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'PENRO_SECTION';
$forwardedSearchInitialsFallback = 'SS';
$forwardedSearchActiveMenu = 'search';
$forwardedSearchBrandSubtitle = 'PENRO Section Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as PENRO Section.';
$forwardedSearchTableTitle = 'PENRO Section Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';
