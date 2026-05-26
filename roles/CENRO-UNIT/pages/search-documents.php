<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'CENRO_UNIT';
$forwardedSearchInitialsFallback = 'SS';
$forwardedSearchActiveMenu = 'search';
$forwardedSearchBrandSubtitle = 'CENRO Unit Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as CENRO Unit.';
$forwardedSearchTableTitle = 'CENRO Unit Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';
