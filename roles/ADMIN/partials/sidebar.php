<?php
$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$activeMenuKey = strtolower((string)($activeMenu ?? 'dashboard'));
$brandTitle = (string)($brandTitle ?? 'DENR XII');
$brandSubtitle = (string)($brandSubtitle ?? 'ICT Admin Portal');
$profileInitial = (string)($initials ?? 'AD');
$profileName = (string)($fullName ?? 'ICT Admin');
$profileRole = (string)($roleName ?? 'ADMIN');
$profileOfficeTag = (string)($profileOfficeTag ?? '');
$profileParentOfficeTag = (string)($profileParentOfficeTag ?? '');

$menuItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'users'],
    ['key' => 'account_management', 'label' => 'Account Management', 'href' => 'account-creation.php', 'icon' => 'plus-user'],
    ['key' => 'profile_settings', 'label' => 'Profile Settings', 'href' => 'profile-settings.php', 'icon' => 'users'],
];

if (!function_exists('DTMIS_sidebar_icon')) {
    function DTMIS_sidebar_icon(string $icon): string
    {
        $icons = [
            'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
            'plus-user' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M17 11h6"></path></svg>',
        ];

        return $icons[$icon] ?? $icons['users'];
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
                    <?php $itemClass = $activeMenuKey === strtolower((string)($item['key'] ?? '')) ? 'menu-link is-active' : 'menu-link'; ?>
                    <a href="<?php echo $esc((string)($item['href'] ?? '#')); ?>" class="<?php echo $itemClass; ?>" aria-label="<?php echo $esc((string)($item['label'] ?? 'Menu')); ?>" title="<?php echo $esc((string)($item['label'] ?? 'Menu')); ?>">
                        <span class="menu-icon" aria-hidden="true"><?php echo DTMIS_sidebar_icon((string)($item['icon'] ?? 'users')); ?></span>
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
