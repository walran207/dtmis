<?php
$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$activeMenuKey = strtolower((string)($activeMenu ?? 'dashboard'));
$brandTitle = (string)($brandTitle ?? 'DENR XII');
$brandSubtitle = (string)($brandSubtitle ?? 'Admin Portal');
$profileInitial = (string)($initials ?? 'AU');
$profileName = (string)($fullName ?? 'Authorized User');
$profileRole = (string)($roleName ?? 'Authorized User');
$profileOfficeTag = (string)($profileOfficeTag ?? '');
$profileParentOfficeTag = (string)($profileParentOfficeTag ?? '');

$menuItems = [
    ['key' => 'dashboard', 'label' => 'My Dashboard', 'href' => 'dashboard.php', 'icon' => 'dashboard'],
    ['type' => 'label', 'label' => 'My Inbox'],
    ['key' => 'inbox_pending_receive', 'label' => 'For Staff Action', 'href' => 'for-staff-action.php', 'icon' => 'inbox'],
    ['key' => 'outbox', 'label' => 'My Outbox', 'href' => 'my-outbox.php', 'icon' => 'outbox'],
    ['key' => 'search', 'label' => 'Search Documents', 'href' => 'search-documents.php', 'icon' => 'search'],
];

if (!function_exists('edats_sidebar_icon')) {
    function edats_sidebar_icon(string $icon): string
    {
        $icons = [
            'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect></svg>',
            'inbox' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13h5l2 3h4l2-3h5"></path><path d="M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"></path></svg>',
            'outbox' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"></path><path d="M22 2l-7 20-4-9-9-4 20-7z"></path></svg>',
            'create' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
            'checklist' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l2 2 4-4"></path><rect x="3" y="4" width="18" height="16" rx="2"></rect></svg>',
            'monitor' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h18v12H3z"></path><path d="M8 20h8"></path><path d="M12 16v4"></path></svg>',
            'endorse' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>',
            'report' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5"></path><path d="M10 19V9"></path><path d="M16 19V13"></path><path d="M22 19V7"></path></svg>',
            'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 4v6c0 5-3.5 8-8 8s-8-3-8-8V7l8-4z"></path></svg>',
            'alert' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path></svg>',
            'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>',
            'signature' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l5 4 13-13-5-5L3 17z"></path><path d="M14 4l5 5"></path></svg>',
            'override' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2l4 4-4 4"></path><path d="M3 6h18"></path><path d="M7 22l-4-4 4-4"></path><path d="M21 18H3"></path></svg>',
            'audit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>',
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
                        <p class="menu-label" title="Menu section: <?php echo $esc((string)$item['label']); ?>"><?php echo $esc((string)$item['label']); ?></p>
                        <?php continue; ?>
                    <?php endif; ?>
                    <?php
                    $itemKey = strtolower((string)($item['key'] ?? ''));
                    $itemClass = $activeMenuKey === $itemKey ? 'menu-link is-active' : 'menu-link';
                    ?>
                    <a href="<?php echo $esc((string)($item['href'] ?? '#')); ?>" class="<?php echo $itemClass; ?>" aria-label="<?php echo $esc((string)($item['label'] ?? 'Menu')); ?>" title="<?php echo $esc((string)($item['label'] ?? 'Menu')); ?>">
                        <span class="menu-icon" aria-hidden="true"><?php echo edats_sidebar_icon((string)($item['icon'] ?? 'dashboard')); ?></span>
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
                        <?php if ($profileOfficeTag !== ''): ?>
                        <p class="user-office"><?php echo $esc($profileOfficeTag); ?></p>
                        <?php endif; ?>
                        <?php if ($profileParentOfficeTag !== ''): ?>
                        <p class="user-office parent"><?php echo $esc($profileParentOfficeTag); ?></p>
                        <?php endif; ?>
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

