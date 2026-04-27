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
