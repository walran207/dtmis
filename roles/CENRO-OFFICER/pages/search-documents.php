<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'CENRO_OFFICER';
$forwardedSearchInitialsFallback = 'OR';
$forwardedSearchActiveMenu = 'global_search';
$forwardedSearchBrandSubtitle = 'CENRO Officer Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as CENRO Officer.';
$forwardedSearchTableTitle = 'CENRO Officer Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';
