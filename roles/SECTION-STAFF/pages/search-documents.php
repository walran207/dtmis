<?php
$roleBasePath = dirname(__DIR__);
$forwardedSearchRoleName = 'Section Staff';
$forwardedSearchInitialsFallback = 'SS';
$forwardedSearchActiveMenu = 'search';
$forwardedSearchBrandSubtitle = 'Section Staff Portal';
$forwardedSearchPageSubtitle = 'Search documents you forwarded, rerouted, or returned as Section Staff.';
$forwardedSearchTableTitle = 'Section Staff Forwarded Transaction Tracker';

require dirname(__DIR__, 3) . '/app/templates/role-forwarded-search-page.php';
