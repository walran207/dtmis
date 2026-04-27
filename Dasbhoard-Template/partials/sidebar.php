<?php
$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$activeMenuKey = strtolower((string)($activeMenu ?? 'dashboard'));
$brandTitle = (string)($brandTitle ?? 'DENR XII');
$brandSubtitle = (string)($brandSubtitle ?? 'Admin Portal');
$profileInitial = (string)($initials ?? 'AU');
$profileName = (string)($fullName ?? 'Authorized User');
$profileRole = (string)($roleName ?? 'Authorized User');

$menuItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'dashboard'],
    ['type' => 'label', 'label' => 'Examples'],
    ['key' => 'charts_example', 'label' => 'Charts', 'href' => 'charts-example.php', 'icon' => 'chart'],
    ['key' => 'stats_cards_example', 'label' => 'Stats & Cards', 'href' => 'stats-cards-example.php', 'icon' => 'cards'],
    ['key' => 'insight_grid_example', 'label' => 'Insight Grid', 'href' => 'insight-grid-example.php', 'icon' => 'grid'],
    ['key' => 'tables_example', 'label' => 'Tables', 'href' => 'tables-example.php', 'icon' => 'table'],
    ['key' => 'input_forms_example', 'label' => 'Input Forms', 'href' => 'input-forms-example.php', 'icon' => 'form'],
];

if (!function_exists('dashboard_template_sidebar_icon')) {
    function dashboard_template_sidebar_icon(string $icon): string
    {
        $icons = [
            'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect></svg>',
            'chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V9"></path><path d="M10 19V5"></path><path d="M16 19v-7"></path><path d="M22 19V3"></path></svg>',
            'cards' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="6" rx="1.5"></rect><rect x="3" y="14" width="8" height="6" rx="1.5"></rect><rect x="13" y="14" width="8" height="6" rx="1.5"></rect></svg>',
            'grid' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h8v8H3z"></path><path d="M13 3h8v5h-8z"></path><path d="M13 10h8v11h-8z"></path><path d="M3 13h8v8H3z"></path></svg>',
            'table' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h18v14H3z"></path><path d="M3 10h18"></path><path d="M8 10v9"></path><path d="M15 10v9"></path></svg>',
            'form' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7h12"></path><path d="M8 12h12"></path><path d="M8 17h8"></path><path d="M3 7h.01"></path><path d="M3 12h.01"></path><path d="M3 17h.01"></path></svg>',
            'dot' => '<svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><circle cx="12" cy="12" r="3"></circle></svg>',
        ];

        return $icons[$icon] ?? $icons['dashboard'];
    }
}
?>
    <div class="sidebar-host">
        <aside class="sidebar">
            <div class="brand">
                <button id="sidebarToggle" type="button" class="sidebar-toggle" aria-label="Collapse sidebar" aria-expanded="true" title="Collapse or expand the sidebar menu.">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 18l-6-6 6-6"></path>
                    </svg>
                </button>
                <img src="../assets/Logo.png" alt="DENR Logo" class="brand-logo">
                <p class="brand-title"><?php echo $esc($brandTitle); ?></p>
                <p class="brand-subtitle"><?php echo $esc($brandSubtitle); ?></p>
            </div>

            <nav class="menu" aria-label="Sidebar">
                <?php foreach ($menuItems as $item): ?>
                    <?php if (($item['type'] ?? 'link') === 'label'): ?>
                        <p class="menu-label" title="Menu section: <?php echo $esc((string)($item['label'] ?? '')); ?>"><?php echo $esc((string)($item['label'] ?? '')); ?></p>
                        <?php continue; ?>
                    <?php endif; ?>
                    <?php
                    $itemKey = strtolower((string)($item['key'] ?? ''));
                    $itemClass = $activeMenuKey === $itemKey ? 'menu-link is-active' : 'menu-link';
                    ?>
                    <a href="<?php echo $esc((string)($item['href'] ?? '#')); ?>" class="<?php echo $itemClass; ?>" aria-label="<?php echo $esc((string)($item['label'] ?? 'Menu')); ?>" title="<?php echo $esc((string)($item['label'] ?? 'Menu')); ?>">
                        <span class="menu-icon" aria-hidden="true"><?php echo dashboard_template_sidebar_icon((string)($item['icon'] ?? 'dashboard')); ?></span>
                        <span class="menu-text"><?php echo $esc((string)($item['label'] ?? 'Untitled')); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="user-badge">
                    <span class="user-dot"><?php echo $esc($profileInitial); ?></span>
                    <div class="user-meta">
                        <p class="user-name"><?php echo $esc($profileName); ?></p>
                        <p class="user-role"><?php echo $esc($profileRole); ?></p>
                    </div>
                </div>
                <button id="logoutBtn" type="button" class="logout-icon-btn" aria-label="Logout" data-logout="true" title="Logout from your account.">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M10 17l5-5-5-5"></path>
                        <path d="M15 12H3"></path>
                        <path d="M21 5v14"></path>
                    </svg>
                </button>
            </div>
        </aside>
    </div>
