<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'PASU_OFFICER';
$forwardedSearchInitialsFallback = 'OR';
$forwardedSearchActiveMenu = 'global_search';
$forwardedSearchBrandSubtitle = 'PASU Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as PASU.';
$forwardedSearchTableTitle = 'PASU Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';

