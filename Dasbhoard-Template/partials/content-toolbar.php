<?php
$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$viewHeading = (string)($viewHeading ?? 'Overview');
$viewSubtitle = (string)($viewSubtitle ?? "Here's what's happening in your area today.");
?>
            <header class="content-header">
                <div>
                    <h1><?php echo $esc($viewHeading); ?></h1>
                    <p><?php echo $esc($viewSubtitle); ?></p>
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
                            <span class="profile-avatar" aria-hidden="true"><?php echo $esc((string)$initials); ?></span>
                            <span class="profile-name"><?php echo $esc((string)$fullName); ?></span>
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
