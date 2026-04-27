<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$pageTitle = 'Design System | DENR Region XII eDATS';
$brandSubtitle = 'Super Admin Portal';
$pageHeading = 'Design System Customizer';
$pageSubtitle = 'Adjust theme tokens, role sidebar accents, layout density, and responsive behavior directly in the UI.';
$activeMenu = 'design_system';
$showQueueTable = false;
$renderStandardContent = false;
$customSectionInclude = __DIR__ . '/sections/design-system-panel.php';
$disableLiveDashboardRefresh = true;
$hideHeaderSearch = true;
$showQrReceiveScanner = false;

$kpiCards = [
    ['label' => 'Theme Presets', 'value' => '4', 'icon' => 'blue'],
    ['label' => 'Density Modes', 'value' => '2', 'icon' => 'violet'],
    ['label' => 'Responsive Preview', 'value' => '3', 'icon' => 'orange'],
    ['label' => 'Live Apply', 'value' => 'Enabled', 'icon' => 'green'],
];

$panels = [
    [
        'title' => 'Customization Controls',
        'chips' => [
            'Theme mode and palette',
            'Card radius and spacing',
            'Sidebar width tuning',
            'Responsive preview window',
        ],
    ],
];

require dirname(__DIR__, 3) . '/app/templates/role-page-template.php';
