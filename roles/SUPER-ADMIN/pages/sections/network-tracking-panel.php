<?php
declare(strict_types=1);

$superAdminNetworkEndpoint = (string)($superAdminNetworkEndpoint ?? app_url('actions/super-admin-network.php'));
$superAdminNetworkSnapshot = is_array($superAdminNetworkSnapshot ?? null) ? $superAdminNetworkSnapshot : [];
?>
<section class="super-admin-grid" aria-label="Live network tracking">
    <article class="card super-admin-panel super-admin-panel-full">
        <div class="super-admin-panel-head">
            <h2>Live Network and Traffic Monitor</h2>
            <p>Online status, throughput, and live event stream for routing traffic.</p>
        </div>

        <div class="super-admin-live-head">
            <div id="superAdminNetworkLiveBadge" class="super-admin-live-badge is-online">Live: Connected</div>
            <div class="super-admin-live-actions">
                <button id="superAdminNetworkRefresh" type="button" class="super-admin-btn super-admin-btn-secondary">Refresh Now</button>
            </div>
        </div>

        <div class="super-admin-network-cards" id="superAdminNetworkCards">
            <article class="network-card">
                <p>Online Users</p>
                <h3 data-network-metric="online_users">0</h3>
            </article>
            <article class="network-card">
                <p>Offline Users</p>
                <h3 data-network-metric="offline_users">0</h3>
            </article>
            <article class="network-card">
                <p>Traffic Last 5 Minutes</p>
                <h3 data-network-metric="traffic_last_5m">0</h3>
            </article>
            <article class="network-card">
                <p>Traffic Last Hour</p>
                <h3 data-network-metric="traffic_last_1h">0</h3>
            </article>
            <article class="network-card">
                <p>Inbound Events (1h)</p>
                <h3 data-network-metric="inbound_last_1h">0</h3>
            </article>
            <article class="network-card">
                <p>Outbound Events (1h)</p>
                <h3 data-network-metric="outbound_last_1h">0</h3>
            </article>
        </div>

        <div class="super-admin-network-grid">
            <section class="network-block">
                <div class="network-block-head">
                    <h3>Traffic Buckets (5-minute intervals)</h3>
                </div>
                <div id="superAdminTrafficBars" class="super-admin-traffic-bars" aria-label="Traffic bars"></div>
            </section>

            <section class="network-block">
                <div class="network-block-head">
                    <h3>Action Mix (Last 1 Hour)</h3>
                </div>
                <div class="super-admin-table-wrap">
                    <table class="super-admin-table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="superAdminActionMixBody">
                            <tr><td colspan="2" class="table-empty-state">No action data yet.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="network-block">
            <div class="network-block-head">
                <h3>Recent Routing and Workflow Events</h3>
                <p id="superAdminNetworkTimestamp">Updated just now</p>
            </div>
            <div class="super-admin-table-wrap">
                <table class="super-admin-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Tracking ID</th>
                            <th>Source Office</th>
                            <th>Destination Office</th>
                        </tr>
                    </thead>
                    <tbody id="superAdminNetworkEventsBody">
                        <tr><td colspan="5" class="table-empty-state">No recent network events.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <p id="superAdminNetworkStatus" class="super-admin-status" role="status" aria-live="polite">Live monitor initialized.</p>
    </article>
</section>

<script>
(function () {
    const endpoint = <?= json_encode($superAdminNetworkEndpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let snapshot = <?= json_encode($superAdminNetworkSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const liveBadge = document.getElementById('superAdminNetworkLiveBadge');
    const refreshButton = document.getElementById('superAdminNetworkRefresh');
    const trafficBars = document.getElementById('superAdminTrafficBars');
    const actionMixBody = document.getElementById('superAdminActionMixBody');
    const eventsBody = document.getElementById('superAdminNetworkEventsBody');
    const timestampNode = document.getElementById('superAdminNetworkTimestamp');
    const statusNode = document.getElementById('superAdminNetworkStatus');

    let pollHandle = null;

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

    function setStatus(message, isError) {
        if (!statusNode) {
            return;
        }
        statusNode.textContent = String(message || '');
        statusNode.classList.toggle('is-error', !!isError);
    }

    function setLiveBadge(isOnline) {
        if (!liveBadge) {
            return;
        }
        liveBadge.classList.toggle('is-online', !!isOnline);
        liveBadge.classList.toggle('is-offline', !isOnline);
        liveBadge.textContent = isOnline ? 'Live: Connected' : 'Live: Offline';
    }

    function renderMetrics(summary) {
        const metrics = [
            'online_users',
            'offline_users',
            'traffic_last_5m',
            'traffic_last_1h',
            'inbound_last_1h',
            'outbound_last_1h',
        ];

        metrics.forEach(function (key) {
            const node = document.querySelector('[data-network-metric="' + key + '"]');
            if (!node) {
                return;
            }
            node.textContent = String(Number(summary && summary[key] || 0));
        });
    }

    function renderTrafficBars(points) {
        if (!trafficBars) {
            return;
        }
        if (!Array.isArray(points) || points.length === 0) {
            trafficBars.innerHTML = '<p class="super-admin-empty">No traffic buckets yet.</p>';
            return;
        }

        let maxValue = 1;
        points.forEach(function (point) {
            maxValue = Math.max(maxValue, Number(point && point.total || 0));
        });

        trafficBars.innerHTML = points.map(function (point) {
            const value = Number(point && point.total || 0);
            const height = Math.max(8, Math.round((value / maxValue) * 100));
            const label = String(point && point.label || '--:--');
            return `
                <div class="traffic-bar-wrap" title="${escapeHtml(label + ' - ' + value + ' events')}">
                    <div class="traffic-bar" style="height:${height}%"></div>
                    <p>${escapeHtml(label)}</p>
                    <span>${escapeHtml(value)}</span>
                </div>
            `;
        }).join('');
    }

    function renderActionMix(items) {
        if (!actionMixBody) {
            return;
        }
        if (!Array.isArray(items) || items.length === 0) {
            actionMixBody.innerHTML = '<tr><td colspan="2" class="table-empty-state">No action data yet.</td></tr>';
            return;
        }

        actionMixBody.innerHTML = items.map(function (item) {
            return '<tr><td>' + escapeHtml(item.action || '-') + '</td><td>' + escapeHtml(item.total || 0) + '</td></tr>';
        }).join('');
    }

    function renderEvents(items) {
        if (!eventsBody) {
            return;
        }
        if (!Array.isArray(items) || items.length === 0) {
            eventsBody.innerHTML = '<tr><td colspan="5" class="table-empty-state">No recent network events.</td></tr>';
            return;
        }

        eventsBody.innerHTML = items.map(function (item) {
            return `
                <tr>
                    <td>${escapeHtml(item.created_label || '-')}</td>
                    <td>${escapeHtml(item.action_type || '-')}</td>
                    <td>${escapeHtml(item.tracking_id || '-')}</td>
                    <td>${escapeHtml(item.source_office || '-')}</td>
                    <td>${escapeHtml(item.destination_office || '-')}</td>
                </tr>
            `;
        }).join('');
    }

    function renderSnapshot() {
        const data = snapshot && typeof snapshot === 'object' ? snapshot : {};
        renderMetrics(data.summary || {});
        renderTrafficBars(Array.isArray(data.traffic_points) ? data.traffic_points : []);
        renderActionMix(Array.isArray(data.action_mix) ? data.action_mix : []);
        renderEvents(Array.isArray(data.recent_events) ? data.recent_events : []);

        if (timestampNode) {
            const raw = String(data.server_time || '');
            const parsedDate = raw ? new Date(raw) : null;
            timestampNode.textContent = parsedDate && !Number.isNaN(parsedDate.getTime())
                ? 'Updated ' + parsedDate.toLocaleString()
                : 'Updated recently';
        }
    }

    async function fetchSnapshot() {
        const response = await fetch(endpoint + '?bucket_minutes=5&bucket_count=12', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await response.json().catch(function () {
            return { ok: false, message: 'Unexpected server response.' };
        });

        if (!response.ok || !json.ok || !json.snapshot) {
            throw new Error(String(json.message || 'Unable to fetch network data.'));
        }

        snapshot = json.snapshot;
        renderSnapshot();
    }

    async function refreshNetwork() {
        if (!navigator.onLine) {
            setLiveBadge(false);
            setStatus('Live refresh paused while browser is offline.', true);
            return;
        }

        try {
            setLiveBadge(true);
            setStatus('Refreshing live network data...', false);
            await fetchSnapshot();
            setStatus('Live network data updated.', false);
        } catch (error) {
            setStatus(error.message || 'Unable to refresh network snapshot.', true);
        }
    }

    function startPolling() {
        if (pollHandle) {
            window.clearInterval(pollHandle);
        }
        pollHandle = window.setInterval(function () {
            refreshNetwork();
        }, 20000);
    }

    if (refreshButton) {
        refreshButton.addEventListener('click', function () {
            refreshNetwork();
        });
    }

    window.addEventListener('online', function () {
        setLiveBadge(true);
        refreshNetwork();
    });

    window.addEventListener('offline', function () {
        setLiveBadge(false);
        setStatus('Browser is offline. Showing last loaded telemetry.', true);
    });

    renderSnapshot();
    setLiveBadge(!!navigator.onLine);
    startPolling();
})();
</script>
