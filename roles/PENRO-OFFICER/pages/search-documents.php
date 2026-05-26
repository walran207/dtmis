<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'PENRO_OFFICER';
$forwardedSearchInitialsFallback = 'OR';
$forwardedSearchActiveMenu = 'global_search';
$forwardedSearchBrandSubtitle = 'PENRO Officer Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as PENRO Officer.';
$forwardedSearchTableTitle = 'PENRO Officer Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';
