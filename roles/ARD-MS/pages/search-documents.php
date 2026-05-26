<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'ARD_MS';
$forwardedSearchInitialsFallback = 'AM';
$forwardedSearchActiveMenu = 'search';
$forwardedSearchBrandSubtitle = 'ARD MS Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as ARD MS.';
$forwardedSearchTableTitle = 'ARD MS Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';
