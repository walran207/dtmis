<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'CENRO_SECTION';
$forwardedSearchInitialsFallback = 'DC';
$forwardedSearchActiveMenu = 'search';
$forwardedSearchBrandSubtitle = 'CENRO Section Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as CENRO Section.';
$forwardedSearchTableTitle = 'CENRO Section Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';
