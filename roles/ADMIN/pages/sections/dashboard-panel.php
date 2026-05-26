<?php
declare(strict_types=1);

$adminOverview = is_array($adminOverview ?? null) ? $adminOverview : [];
$adminUsers = is_array($adminUsers ?? null) ? $adminUsers : [];
$adminScope = is_array($adminScope ?? null) ? $adminScope : [];
$scopeRootOfficeName = trim((string)(($adminScope['root_office']['name'] ?? '') ?: 'Assigned Scope'));
$scopePenroName = trim((string)(($adminScope['penro_office']['name'] ?? '') ?: ''));
$scopeType = trim((string)($adminScope['scope_type'] ?? 'DIRECT'));
$scopeSummaryLabel = trim((string)($adminScope['summary_label'] ?? 'Managed office scope'));
$scopeIncludesPamo = !empty($adminScope['include_pamo']);
?>
<section class="admin-grid admin-dashboard-grid" aria-label="Scoped admin analytics dashboard">
    <article class="card admin-panel admin-panel-full">
        <div class="admin-scope-banner">
            <div class="admin-scope-copy">
                <p class="admin-scope-kicker">Dashboard Scope</p>
                <h2><?= htmlspecialchars($scopeRootOfficeName, ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars($scopeSummaryLabel, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="admin-scope-chips" aria-label="Scope details">
                <span class="admin-scope-chip"><?= htmlspecialchars($scopeType, ENT_QUOTES, 'UTF-8') ?> scope</span>
                <span class="admin-scope-chip"><?= (int)($adminOverview['offices_total'] ?? 0) ?> offices covered</span>
                <span class="admin-scope-chip"><?= (int)($adminOverview['users_total'] ?? 0) ?> managed users</span>
                <?php if ($scopeIncludesPamo): ?>
                <span class="admin-scope-chip">Includes PAMO/PASU in <?= htmlspecialchars($scopePenroName, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
        </div>
    </article>

    <article class="card admin-panel admin-panel-full">
        <div class="admin-panel-head">
            <h2>Quick Actions</h2>
            <p>The dashboard is now focused on insight cards and charts. Use account management when you need to work on specific user accounts.</p>
        </div>
        <div class="admin-split-actions">
            <div>
                <p class="admin-inline-kicker">Workspace Focus</p>
                <h3>Admin Analytics</h3>
                <p>Track user distribution, office load, login activity, inactive accounts, and scope coverage from one place.</p>
            </div>
            <div class="admin-inline-actions">
                <a href="account-creation.php" class="admin-btn admin-btn-primary">Open Account Management</a>
            </div>
        </div>
    </article>

    <article class="card admin-panel admin-panel-full">
        <div class="admin-stat-grid" id="adminStatGrid">
            <div class="admin-stat-card">
                <p class="admin-stat-label">Managed Users</p>
                <p class="admin-stat-value"><?= (int)($adminOverview['users_total'] ?? 0) ?></p>
                <p class="admin-stat-note">Accounts visible in your current scope.</p>
            </div>
            <div class="admin-stat-card">
                <p class="admin-stat-label">Active Accounts</p>
                <p class="admin-stat-value"><?= (int)($adminOverview['users_active'] ?? 0) ?></p>
                <p class="admin-stat-note">Ready to log in and work today.</p>
            </div>
            <div class="admin-stat-card">
                <p class="admin-stat-label">Inactive Accounts</p>
                <p class="admin-stat-value"><?= (int)($adminOverview['users_inactive'] ?? 0) ?></p>
                <p class="admin-stat-note">Candidates for review or reactivation.</p>
            </div>
            <div class="admin-stat-card">
                <p class="admin-stat-label">Locked Accounts</p>
                <p class="admin-stat-value"><?= (int)($adminOverview['users_locked'] ?? 0) ?></p>
                <p class="admin-stat-note">Need reset or follow-up support.</p>
            </div>
            <div class="admin-stat-card">
                <p class="admin-stat-label">Covered Offices</p>
                <p class="admin-stat-value"><?= (int)($adminOverview['offices_total'] ?? 0) ?></p>
                <p class="admin-stat-note">Branches included in this dashboard.</p>
            </div>
        </div>
    </article>

    <article class="card admin-panel">
        <div class="admin-panel-head">
            <h2>Role Distribution</h2>
            <p>Pie view of the current account mix inside your scope.</p>
        </div>
        <div class="admin-donut-layout">
            <div id="adminRoleDonut" class="admin-donut">
                <div class="admin-donut-hole">
                    <strong id="adminRoleDonutTotal">0</strong>
                    <span>Users</span>
                </div>
            </div>
            <div id="adminRoleLegend" class="admin-legend-list"></div>
        </div>
    </article>

    <article class="card admin-panel">
        <div class="admin-panel-head">
            <h2>Insight Grid</h2>
            <p>Highlights to help ICT admins spot account health issues quickly.</p>
        </div>
        <div id="adminInsightGrid" class="admin-insight-grid"></div>
    </article>

    <article class="card admin-panel admin-panel-full">
        <div class="admin-panel-head">
            <h2>7-Day Login Trend</h2>
            <p>Spline chart showing recent last-login activity across visible accounts.</p>
        </div>
        <div class="admin-spline-card">
            <svg id="adminLoginTrendChart" class="admin-spline-chart" viewBox="0 0 760 260" role="img" aria-label="Seven day login trend chart"></svg>
            <div id="adminTrendLabels" class="admin-trend-labels"></div>
        </div>
    </article>

    <article class="card admin-panel admin-panel-full">
        <div class="admin-panel-head">
            <h2>Top Office Load</h2>
            <p>Office view of where the highest number of managed accounts are currently assigned.</p>
        </div>
        <div id="adminOfficeBars" class="admin-office-bars"></div>
    </article>
</section>

<script>
(function () {
    const users = <?= json_encode($adminUsers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const scope = <?= json_encode($adminScope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const donutNode = document.getElementById('adminRoleDonut');
    const donutTotalNode = document.getElementById('adminRoleDonutTotal');
    const legendNode = document.getElementById('adminRoleLegend');
    const insightGridNode = document.getElementById('adminInsightGrid');
    const officeBarsNode = document.getElementById('adminOfficeBars');
    const trendChartNode = document.getElementById('adminLoginTrendChart');
    const trendLabelsNode = document.getElementById('adminTrendLabels');

    const palette = ['#14b8a6', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#22c55e', '#f97316'];

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>\"']/g, function (character) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            };
            return map[character] || character;
        });
    }

    function formatDateLabel(date) {
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }

    function summarizeRoles() {
        const counts = {};
        users.forEach(function (user) {
            const key = String(user && user.role_name || 'Unassigned').trim() || 'Unassigned';
            counts[key] = Number(counts[key] || 0) + 1;
        });

        return Object.keys(counts).map(function (roleName, index) {
            return {
                role: roleName,
                value: counts[roleName],
                color: palette[index % palette.length]
            };
        }).sort(function (left, right) {
            return right.value - left.value;
        });
    }

    function renderRoleDonut() {
        if (!donutNode || !legendNode) {
            return;
        }

        const series = summarizeRoles();
        const total = series.reduce(function (sum, item) {
            return sum + Number(item && item.value || 0);
        }, 0);

        if (donutTotalNode) {
            donutTotalNode.textContent = String(total);
        }

        if (total <= 0 || series.length === 0) {
            donutNode.style.background = 'conic-gradient(#243245 0deg 360deg)';
            legendNode.innerHTML = '<p class="admin-empty">No role data available.</p>';
            return;
        }

        let offset = 0;
        const gradientStops = series.map(function (item) {
            const slice = (Number(item.value || 0) / total) * 360;
            const start = offset;
            offset += slice;
            return item.color + ' ' + start.toFixed(2) + 'deg ' + offset.toFixed(2) + 'deg';
        });

        donutNode.style.background = 'conic-gradient(' + gradientStops.join(', ') + ')';
        legendNode.innerHTML = series.map(function (item) {
            const pct = total > 0 ? Math.round((Number(item.value || 0) / total) * 100) : 0;
            return '<div class="admin-legend-item">'
                + '<span class="admin-legend-dot" style="background:' + escapeHtml(item.color) + ';"></span>'
                + '<div><strong>' + escapeHtml(item.role) + '</strong><p>' + escapeHtml(String(item.value)) + ' users · ' + escapeHtml(String(pct)) + '%</p></div>'
                + '</div>';
        }).join('');
    }

    function renderInsights() {
        if (!insightGridNode) {
            return;
        }

        const officeCounts = {};
        let latestLogin = null;
        let latestLoginUser = '';
        users.forEach(function (user) {
            const officeName = String(user && user.office_name || 'Unassigned').trim() || 'Unassigned';
            officeCounts[officeName] = Number(officeCounts[officeName] || 0) + 1;
            const loginValue = String(user && user.last_login_at || '').trim();
            if (loginValue !== '') {
                const loginDate = new Date(loginValue);
                if (!Number.isNaN(loginDate.getTime()) && (!latestLogin || loginDate > latestLogin)) {
                    latestLogin = loginDate;
                    latestLoginUser = String((user.first_name || '') + ' ' + (user.last_name || '')).trim() || String(user.email || 'A user');
                }
            }
        });

        const topOfficeEntry = Object.keys(officeCounts).sort(function (left, right) {
            return officeCounts[right] - officeCounts[left];
        })[0] || 'No offices yet';
        const inactiveCount = users.filter(function (user) { return !user.is_active; }).length;
        const lockedCount = users.filter(function (user) { return String(user && user.locked_until || '').trim() !== ''; }).length;
        const scopeMode = String(scope && scope.scope_type || 'DIRECT');

        const cards = [
            { label: 'Top Office Load', value: topOfficeEntry, note: String(officeCounts[topOfficeEntry] || 0) + ' accounts assigned' },
            { label: 'Inactive Watchlist', value: String(inactiveCount), note: inactiveCount > 0 ? 'Needs review from ICT admin' : 'All visible accounts are active' },
            { label: 'Security Watch', value: String(lockedCount), note: lockedCount > 0 ? 'Locked accounts may need reset' : 'No current security lock alerts' },
            { label: 'Scope Mode', value: scopeMode, note: scope && scope.include_pamo ? 'With PAMO/PASU coverage' : 'Direct office-tree coverage only' },
            { label: 'Most Recent Login', value: latestLogin ? formatDateLabel(latestLogin) : 'No data', note: latestLogin ? latestLoginUser : 'No recent login timestamp in scope' },
            { label: 'Visible Accounts', value: String(users.length), note: 'Live count for the current admin coverage' }
        ];

        insightGridNode.innerHTML = cards.map(function (card) {
            return '<article class="admin-insight-card">'
                + '<p class="admin-insight-label">' + escapeHtml(card.label) + '</p>'
                + '<h3>' + escapeHtml(card.value) + '</h3>'
                + '<p class="admin-insight-note">' + escapeHtml(card.note) + '</p>'
                + '</article>';
        }).join('');
    }

    function renderOfficeBars() {
        if (!officeBarsNode) {
            return;
        }

        const counts = {};
        users.forEach(function (user) {
            const officeName = String(user && user.office_name || 'Unassigned').trim() || 'Unassigned';
            counts[officeName] = Number(counts[officeName] || 0) + 1;
        });

        const topOffices = Object.keys(counts).map(function (officeName) {
            return { office: officeName, value: counts[officeName] };
        }).sort(function (left, right) {
            return right.value - left.value;
        }).slice(0, 6);

        const maxValue = topOffices.reduce(function (max, item) {
            return Math.max(max, Number(item && item.value || 0));
        }, 0);

        if (topOffices.length === 0 || maxValue <= 0) {
            officeBarsNode.innerHTML = '<p class="admin-empty">No office distribution data available.</p>';
            return;
        }

        officeBarsNode.innerHTML = topOffices.map(function (item, index) {
            const width = Math.max(8, Math.round((Number(item.value || 0) / maxValue) * 100));
            return '<div class="admin-office-bar-row">'
                + '<div class="admin-office-bar-copy"><strong>' + escapeHtml(item.office) + '</strong><span>' + escapeHtml(String(item.value)) + ' users</span></div>'
                + '<div class="admin-office-bar-rail"><span class="admin-office-bar-fill" style="width:' + escapeHtml(String(width)) + '%; background:' + escapeHtml(palette[index % palette.length]) + ';"></span></div>'
                + '</div>';
        }).join('');
    }

    function renderTrendChart() {
        if (!trendChartNode || !trendLabelsNode) {
            return;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const buckets = [];
        for (let index = 6; index >= 0; index -= 1) {
            const date = new Date(today);
            date.setDate(today.getDate() - index);
            buckets.push({ key: date.toISOString().slice(0, 10), label: formatDateLabel(date), value: 0 });
        }

        const bucketIndex = {};
        buckets.forEach(function (bucket, index) {
            bucketIndex[bucket.key] = index;
        });

        users.forEach(function (user) {
            const loginValue = String(user && user.last_login_at || '').trim();
            if (loginValue === '') {
                return;
            }

            const loginDate = new Date(loginValue);
            if (Number.isNaN(loginDate.getTime())) {
                return;
            }

            const key = loginDate.toISOString().slice(0, 10);
            if (Object.prototype.hasOwnProperty.call(bucketIndex, key)) {
                buckets[bucketIndex[key]].value += 1;
            }
        });

        const maxValue = buckets.reduce(function (max, bucket) {
            return Math.max(max, Number(bucket && bucket.value || 0));
        }, 0);

        if (maxValue <= 0) {
            trendChartNode.innerHTML = '<text x="50%" y="50%" text-anchor="middle" fill="#94a3b8" font-size="16">No login activity recorded in the last 7 days.</text>';
            trendLabelsNode.innerHTML = buckets.map(function (bucket) {
                return '<span>' + escapeHtml(bucket.label) + '</span>';
            }).join('');
            return;
        }

        const width = 760;
        const height = 260;
        const paddingX = 36;
        const paddingTop = 20;
        const paddingBottom = 34;
        const chartWidth = width - (paddingX * 2);
        const chartHeight = height - paddingTop - paddingBottom;

        const points = buckets.map(function (bucket, index) {
            const x = paddingX + ((chartWidth / Math.max(1, buckets.length - 1)) * index);
            const y = paddingTop + chartHeight - ((Number(bucket.value || 0) / maxValue) * chartHeight);
            return { x: x, y: y, value: bucket.value, label: bucket.label };
        });

        const pointString = points.map(function (point) {
            return point.x.toFixed(1) + ',' + point.y.toFixed(1);
        }).join(' ');

        const areaPath = ['M ' + paddingX + ' ' + (paddingTop + chartHeight)];
        points.forEach(function (point, index) {
            areaPath.push((index === 0 ? 'L ' : 'L ') + point.x.toFixed(1) + ' ' + point.y.toFixed(1));
        });
        areaPath.push('L ' + points[points.length - 1].x.toFixed(1) + ' ' + (paddingTop + chartHeight));
        areaPath.push('Z');

        const gridLines = [];
        for (let index = 0; index <= 4; index += 1) {
            const y = paddingTop + ((chartHeight / 4) * index);
            gridLines.push('<line x1="' + paddingX + '" y1="' + y.toFixed(1) + '" x2="' + (width - paddingX) + '" y2="' + y.toFixed(1) + '" />');
        }

        const circles = points.map(function (point) {
            return '<circle cx="' + point.x.toFixed(1) + '" cy="' + point.y.toFixed(1) + '" r="4.5"></circle>'
                + '<text x="' + point.x.toFixed(1) + '" y="' + (point.y - 12).toFixed(1) + '" text-anchor="middle">' + escapeHtml(String(point.value)) + '</text>';
        }).join('');

        trendChartNode.innerHTML = ''
            + '<g class="admin-spline-grid">' + gridLines.join('') + '</g>'
            + '<path class="admin-spline-area" d="' + areaPath.join(' ') + '"></path>'
            + '<polyline class="admin-spline-line" points="' + pointString + '"></polyline>'
            + '<g class="admin-spline-points">' + circles + '</g>';

        trendLabelsNode.innerHTML = buckets.map(function (bucket) {
            return '<span>' + escapeHtml(bucket.label) + '</span>';
        }).join('');
    }

    renderRoleDonut();
    renderInsights();
    renderOfficeBars();
    renderTrendChart();
})();
</script>
