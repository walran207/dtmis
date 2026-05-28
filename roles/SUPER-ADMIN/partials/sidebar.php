<?php
$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$activeMenuKey = strtolower((string)($activeMenu ?? 'dashboard'));
$brandTitle = (string)($brandTitle ?? 'DENR XII');
$brandSubtitle = (string)($brandSubtitle ?? 'Super Admin Portal');
$profileInitial = (string)($initials ?? 'SA');
$profileName = (string)($fullName ?? 'Super Admin');
$profileRole = (string)($roleName ?? 'SUPER_ADMIN');
$profileOfficeTag = (string)($profileOfficeTag ?? '');
$profileParentOfficeTag = (string)($profileParentOfficeTag ?? '');

$menuItems = [
    ['key' => 'dashboard', 'label' => 'Control Center', 'href' => 'dashboard.php', 'icon' => 'dashboard'],
    ['key' => 'documents_management', 'label' => 'Documents Management', 'href' => 'documents-management.php', 'icon' => 'audit'],
    ['key' => 'users_management', 'label' => 'Users Management', 'href' => 'users-management.php', 'icon' => 'users'],
    ['key' => 'deleted_users', 'label' => 'Deleted Users', 'href' => 'deleted-users.php', 'icon' => 'audit'],
    ['key' => 'data_management', 'label' => 'Data Management', 'href' => 'data-management.php', 'icon' => 'database'],
    ['key' => 'deleted_documents', 'label' => 'Deleted Documents', 'href' => 'deleted-documents.php', 'icon' => 'audit'],
    ['key' => 'document_type_review', 'label' => 'Document Type Review', 'href' => 'document-type-review.php', 'icon' => 'audit'],
    ['key' => 'analytics_dashboard', 'label' => 'Analytics Dashboard', 'href' => 'analytics-dashboard.php', 'icon' => 'report'],
    ['key' => 'network_tracking', 'label' => 'Network Tracking', 'href' => 'network-tracking.php', 'icon' => 'network'],
    ['key' => 'export_center', 'label' => 'Export Center', 'href' => 'export-center.php', 'icon' => 'download'],
    ['key' => 'design_system', 'label' => 'Design System', 'href' => 'design-system.php', 'icon' => 'palette'],
    ['key' => 'profile_settings', 'label' => 'Profile Settings', 'href' => 'profile-settings.php', 'icon' => 'users'],
];

if (!function_exists('DTMIS_sidebar_icon')) {
    function DTMIS_sidebar_icon(string $icon): string
    {
        $icons = [
            'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect></svg>',
            'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
            'database' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"></path><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"></path></svg>',
            'audit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>',
            'report' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5"></path><path d="M10 19V9"></path><path d="M16 19V13"></path><path d="M22 19V7"></path></svg>',
            'network' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1 1 0 0 1 .2 1.1l-.1.2a8.5 8.5 0 0 1-15 0l-.1-.2a1 1 0 0 1 .2-1.1"></path><path d="M7 9a5 5 0 0 1 10 0"></path></svg>',
            'download' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
            'palette' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"></circle><circle cx="17.5" cy="10.5" r=".5"></circle><circle cx="8.5" cy="7.5" r=".5"></circle><circle cx="6.5" cy="12.5" r=".5"></circle><path d="M12 22a10 10 0 1 1 10-10 3 3 0 0 1-3 3h-2a2 2 0 0 0-2 2 2 2 0 0 0 2 2 3 3 0 0 1 3 3"></path></svg>',
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
                    <?php
                    $itemKey = strtolower((string)($item['key'] ?? ''));
                    $itemClass = $activeMenuKey === $itemKey ? 'menu-link is-active' : 'menu-link';
                    ?>
                    <a href="<?php echo $esc((string)($item['href'] ?? '#')); ?>" class="<?php echo $itemClass; ?>" aria-label="<?php echo $esc((string)($item['label'] ?? 'Menu')); ?>" title="<?php echo $esc((string)($item['label'] ?? 'Menu')); ?>">
                        <span class="menu-icon" aria-hidden="true"><?php echo DTMIS_sidebar_icon((string)($item['icon'] ?? 'dashboard')); ?></span>
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
