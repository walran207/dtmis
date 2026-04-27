<?php
$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$activeMenuKey = strtolower((string)($activeMenu ?? 'dashboard'));
$brandTitle = (string)($brandTitle ?? 'DENR XII');
$brandSubtitle = (string)($brandSubtitle ?? 'Admin Portal');
$profileInitial = (string)($initials ?? 'AU');
$profileName = (string)($fullName ?? 'Authorized User');
$profileRole = (string)($roleName ?? 'Authorized User');

$dashboardClass = $activeMenuKey === 'dashboard' ? 'menu-link is-active' : 'menu-link';
$residentsClass = $activeMenuKey === 'residents' ? 'menu-link is-active' : 'menu-link';
$demographicsClass = $activeMenuKey === 'demographics' ? 'menu-link is-active' : 'menu-link';
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
                <a href="dashboard-home.php" class="<?php echo $dashboardClass; ?>" aria-label="Dashboard" title="Dashboard">
                    <span class="menu-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                            <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                            <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                        </svg>
                    </span>
                    <span class="menu-text">Dashboard</span>
                </a>
                <a href="#" class="<?php echo $residentsClass; ?>" aria-label="Residents Profiling" title="Residents Profiling">
                    <span class="menu-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 19v-1a4 4 0 0 1 4-4h3"></path>
                            <circle cx="10" cy="8" r="3"></circle>
                            <path d="M14 11a3 3 0 1 0 0-6"></path>
                            <path d="M14 19h7"></path>
                            <path d="M18 15v8"></path>
                        </svg>
                    </span>
                    <span class="menu-text">Residents Profiling</span>
                </a>
                <p class="menu-label" title="Menu section: Analysis">Analysis</p>
                <a href="#" class="<?php echo $demographicsClass; ?>" aria-label="Demographics" title="Demographics">
                    <span class="menu-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 20V10"></path>
                            <path d="M10 20V4"></path>
                            <path d="M16 20v-6"></path>
                            <path d="M22 20V8"></path>
                        </svg>
                    </span>
                    <span class="menu-text">Demographics</span>
                </a>
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
