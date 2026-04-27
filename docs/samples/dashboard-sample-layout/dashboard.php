<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

session_start();

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$fullName = trim(((string)($_SESSION['first_name'] ?? '')) . ' ' . ((string)($_SESSION['last_name'] ?? '')));
if ($fullName === '') {
    $fullName = (string)($_SESSION['email'] ?? 'Authorized User');
}
$roleName = (string)($_SESSION['role_name'] ?? 'Authorized User');
$initials = strtoupper(substr((string)($_SESSION['first_name'] ?? ''), 0, 1) . substr((string)($_SESSION['last_name'] ?? ''), 0, 1));
if ($initials === '') {
    $initials = 'AU';
}

$pageTitle = 'Dashboard | DENR Region XII eDATS';
$activeMenu = 'dashboard';
$footerYear = '2026';
$footerVersion = 'eDATS v2.0.0';

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

        <main class="content">
            <header class="content-header">
                <div>
                    <h1>Overview</h1>
                    <p>Here's what's happening in your area today.</p>
                </div>
                <div class="header-actions">
                    <div class="notif-wrap">
                        <button id="notifToggle" type="button" class="notif-btn" aria-label="Open notifications" aria-expanded="false" aria-controls="notifDropdown">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V10a6 6 0 1 0-12 0v4.2a2 2 0 0 1-.6 1.4L4 17h5"></path>
                                <path d="M9 17a3 3 0 0 0 6 0"></path>
                            </svg>
                            <span class="notif-count">6</span>
                        </button>

                        <div id="notifDropdown" class="notif-dropdown" role="dialog" aria-label="Notifications">
                            <div class="notif-head">
                                <h2>Notifications</h2>
                                <button type="button" class="notif-clear">Mark all read</button>
                            </div>
                            <div class="notif-list" tabindex="0">
                                <article class="notif-item is-unread">
                                    <h3>New resident submitted</h3>
                                    <p>Maria Lopez profile is pending verification.</p>
                                    <time datetime="2026-04-02T09:05:00">5m ago</time>
                                </article>
                                <article class="notif-item is-unread">
                                    <h3>Data sync complete</h3>
                                    <p>Barangay records synced with municipal server.</p>
                                    <time datetime="2026-04-02T08:47:00">23m ago</time>
                                </article>
                                <article class="notif-item">
                                    <h3>System reminder</h3>
                                    <p>Weekly demographic report due at 3:00 PM.</p>
                                    <time datetime="2026-04-02T07:10:00">2h ago</time>
                                </article>
                                <article class="notif-item">
                                    <h3>Password policy update</h3>
                                    <p>All users must update password every 90 days.</p>
                                    <time datetime="2026-04-01T16:40:00">Yesterday</time>
                                </article>
                            </div>
                        </div>
                    </div>

                    <div class="profile-wrap">
                        <button id="profileToggle" type="button" class="profile-btn" aria-label="Open profile menu" aria-expanded="false" aria-controls="profileDropdown">
                            <span class="profile-avatar" aria-hidden="true"><?php echo e($initials); ?></span>
                            <span class="profile-name"><?php echo e($fullName); ?></span>
                            <svg class="profile-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 9l6 6 6-6"></path>
                            </svg>
                        </button>
                        <div id="profileDropdown" class="profile-dropdown" role="menu" aria-label="Profile menu">
                            <button type="button" class="profile-menu-btn" role="menuitem">Profile settings</button>
                            <button type="button" class="profile-menu-btn danger" role="menuitem" data-logout="true">Logout</button>
                        </div>
                    </div>

                    <div class="date-filter-wrap">
                        <button id="dateFilterToggle" type="button" class="date-filter-icon-btn" aria-label="Open date range filter" aria-expanded="false" aria-controls="dateFilterDropdown">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <path d="M16 2v4"></path>
                                <path d="M8 2v4"></path>
                                <path d="M3 10h18"></path>
                            </svg>
                        </button>
                        <form id="dateFilterDropdown" class="date-range-dropdown" aria-label="Date range filter">
                            <label class="date-field">
                                <span>From</span>
                                <input type="date" name="fromDate">
                            </label>
                            <label class="date-field">
                                <span>To</span>
                                <input type="date" name="toDate">
                            </label>
                            <button type="button" class="date-filter-btn">Apply</button>
                        </form>
                    </div>
                </div>
            </header>

            <section class="stats-grid" aria-label="Summary cards">
                <article class="card stat-card">
                    <div class="stat-head">
                        <span class="stat-icon blue"></span>
                        <span class="live-pill">Live</span>
                    </div>
                    <p class="stat-label">Total Residents</p>
                    <p class="stat-value">11</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head">
                        <span class="stat-icon orange"></span>
                    </div>
                    <p class="stat-label">Senior Citizens</p>
                    <p class="stat-value">0</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head">
                        <span class="stat-icon violet"></span>
                    </div>
                    <p class="stat-label">PWDs</p>
                    <p class="stat-value">0</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head">
                        <span class="stat-icon green"></span>
                    </div>
                    <p class="stat-label">4Ps Beneficiaries</p>
                    <p class="stat-value">1</p>
                </article>
            </section>

            <section class="insight-grid" aria-label="Analytics">
                <article class="card panel">
                    <h2>Civil Status Distribution</h2>
                    <div class="progress-list">
                        <div class="progress-row">
                            <div class="row-meta"><span>Single</span><span>8 (73%)</span></div>
                            <div class="bar"><span style="width: 73%"></span></div>
                        </div>
                        <div class="progress-row">
                            <div class="row-meta"><span>Married</span><span>3 (27%)</span></div>
                            <div class="bar"><span style="width: 27%"></span></div>
                        </div>
                        <div class="progress-row">
                            <div class="row-meta"><span>Widowed</span><span>0 (0%)</span></div>
                            <div class="bar"><span style="width: 0%"></span></div>
                        </div>
                        <div class="progress-row">
                            <div class="row-meta"><span>Separated</span><span>0 (0%)</span></div>
                            <div class="bar"><span style="width: 0%"></span></div>
                        </div>
                    </div>
                </article>

                <article class="card panel">
                    <h2>Population by Age</h2>
                    <div class="age-grid">
                        <div class="age-tile">
                            <p>Children (0-14)</p>
                            <strong>0</strong>
                        </div>
                        <div class="age-tile">
                            <p>Youth (15-30)</p>
                            <strong>8</strong>
                        </div>
                        <div class="age-tile">
                            <p>Adults (31-59)</p>
                            <strong>3</strong>
                        </div>
                        <div class="age-tile">
                            <p>Seniors (60+)</p>
                            <strong>0</strong>
                        </div>
                    </div>
                </article>

                <article class="card panel">
                    <h2>Gender Ratio</h2>
                    <div class="gender-wrap">
                        <div class="donut" aria-label="Gender ratio donut chart">
                            <span>11</span>
                        </div>
                        <div class="legend">
                            <p><span class="dot male"></span> Male <strong>2</strong></p>
                            <p><span class="dot female"></span> Female <strong>9</strong></p>
                        </div>
                    </div>
                </article>
            </section>

            <section class="card table-panel" aria-label="Top population by purok">
                <div class="table-header">
                    <h2>Top Population by Purok</h2>
                    <a href="#" class="table-link">View All</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Purok Name</th>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Percent</th>
                                <th>Bar Chart</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Purok Capitol West</td>
                                <td><span class="status-badge">Active</span></td>
                                <td>3</td>
                                <td>27.3%</td>
                                <td><div class="bar"><span style="width: 27.3%"></span></div></td>
                            </tr>
                            <tr>
                                <td>Purok Capitol Centro</td>
                                <td><span class="status-badge">Active</span></td>
                                <td>3</td>
                                <td>27.3%</td>
                                <td><div class="bar"><span style="width: 27.3%"></span></div></td>
                            </tr>
                            <tr>
                                <td>Purok Tagumpay 1</td>
                                <td><span class="status-badge">Active</span></td>
                                <td>2</td>
                                <td>18.2%</td>
                                <td><div class="bar"><span style="width: 18.2%"></span></div></td>
                            </tr>
                            <tr>
                                <td>Purok Maunlad</td>
                                <td><span class="status-badge">Active</span></td>
                                <td>2</td>
                                <td>18.2%</td>
                                <td><div class="bar"><span style="width: 18.2%"></span></div></td>
                            </tr>
                            <tr>
                                <td>Purok Engineering</td>
                                <td><span class="status-badge">Active</span></td>
                                <td>1</td>
                                <td>9.1%</td>
                                <td><div class="bar"><span style="width: 9.1%"></span></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

<?php include __DIR__ . '/partials/footer.php'; ?>
        </main>
    </div>

    <script>
        (function () {
            const SIDEBAR_COLLAPSE_KEY = 'edats_sidebar_collapsed';
            const logoutPath = <?= json_encode(app_url('auth/logout.php')) ?>;

            const logoutButtons = document.querySelectorAll('[data-logout="true"]');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const dashboardShell = document.querySelector('.dashboard-shell');
            const notifToggle = document.getElementById('notifToggle');
            const notifDropdown = document.getElementById('notifDropdown');
            const profileToggle = document.getElementById('profileToggle');
            const profileDropdown = document.getElementById('profileDropdown');
            const dateFilterToggle = document.getElementById('dateFilterToggle');
            const dateFilterDropdown = document.getElementById('dateFilterDropdown');

            function applySidebarState(isCollapsed) {
                if (!dashboardShell || !sidebarToggle) {
                    return;
                }
                dashboardShell.classList.toggle('is-collapsed', isCollapsed);
                sidebarToggle.setAttribute('aria-expanded', String(!isCollapsed));
                sidebarToggle.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
                sidebarToggle.title = isCollapsed ? 'Expand sidebar' : 'Collapse sidebar';
            }

            const savedCollapsed = localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === 'true';
            applySidebarState(savedCollapsed);
            requestAnimationFrame(function () {
                if (dashboardShell) {
                    dashboardShell.classList.add('is-ready');
                }
            });

            if (sidebarToggle && dashboardShell) {
                sidebarToggle.addEventListener('click', function () {
                    const nextState = !dashboardShell.classList.contains('is-collapsed');
                    localStorage.setItem(SIDEBAR_COLLAPSE_KEY, String(nextState));
                    applySidebarState(nextState);
                });
            }

            function setNotifOpen(isOpen) {
                if (!notifDropdown || !notifToggle) {
                    return;
                }
                notifDropdown.classList.toggle('is-open', isOpen);
                notifToggle.setAttribute('aria-expanded', String(isOpen));
            }

            function setProfileOpen(isOpen) {
                if (!profileDropdown || !profileToggle) {
                    return;
                }
                profileDropdown.classList.toggle('is-open', isOpen);
                profileToggle.setAttribute('aria-expanded', String(isOpen));
            }

            function setDateFilterOpen(isOpen) {
                if (!dateFilterDropdown || !dateFilterToggle) {
                    return;
                }
                dateFilterDropdown.classList.toggle('is-open', isOpen);
                dateFilterToggle.setAttribute('aria-expanded', String(isOpen));
            }

            if (notifToggle && notifDropdown) {
                notifToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const nextState = !notifDropdown.classList.contains('is-open');
                    setNotifOpen(nextState);
                    if (nextState) {
                        setProfileOpen(false);
                        setDateFilterOpen(false);
                    }
                });
            }

            if (profileToggle && profileDropdown) {
                profileToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const nextState = !profileDropdown.classList.contains('is-open');
                    setProfileOpen(nextState);
                    if (nextState) {
                        setNotifOpen(false);
                        setDateFilterOpen(false);
                    }
                });
            }

            if (dateFilterToggle && dateFilterDropdown) {
                dateFilterToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const nextState = !dateFilterDropdown.classList.contains('is-open');
                    setDateFilterOpen(nextState);
                    if (nextState) {
                        setNotifOpen(false);
                        setProfileOpen(false);
                    }
                });
            }

            document.addEventListener('click', function (event) {
                if (notifDropdown && notifToggle && !notifDropdown.contains(event.target) && !notifToggle.contains(event.target)) {
                    setNotifOpen(false);
                }

                if (profileDropdown && profileToggle && !profileDropdown.contains(event.target) && !profileToggle.contains(event.target)) {
                    setProfileOpen(false);
                }

                if (dateFilterDropdown && dateFilterToggle && !dateFilterDropdown.contains(event.target) && !dateFilterToggle.contains(event.target)) {
                    setDateFilterOpen(false);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    setNotifOpen(false);
                    setProfileOpen(false);
                    setDateFilterOpen(false);
                }
            });

            logoutButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    window.location.replace(logoutPath);
                });
            });
        })();
    </script>
</body>
</html>
