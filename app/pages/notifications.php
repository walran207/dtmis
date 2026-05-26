<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/dashboard-data.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

function notifications_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$fullName = trim(((string)($_SESSION['first_name'] ?? '')) . ' ' . ((string)($_SESSION['last_name'] ?? '')));
if ($fullName === '') {
    $fullName = (string)($_SESSION['email'] ?? 'Authorized User');
}

$roleName = (string)($_SESSION['role_name'] ?? '');
$roleKey = app_normalize_role_key($roleName);
$officeId = (int)($_SESSION['office_id'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? 0);
$offlinePolicy = app_offline_policy_for_role($roleName);
$offlineSyncLogUrl = app_url('actions/offline-sync-log.php');
$dashboardUrl = app_url(app_role_dashboard_path($roleName));
$trackingSlipUrl = app_public_url('tracking-slip.php');
$notificationsPageUrl = app_url('notifications.php');
$notificationFeedUrl = app_url('actions/notifications-feed.php');
$notificationSoundUrl = app_url('assets/audio/notif-sound.wav');

$notifications = [];
$errorMessage = '';

try {
    if ($officeId > 0) {
        $pdo = getDatabaseConnection();
        $notifications = dashboard_fetch_notifications($pdo, $officeId, 50, $roleName, $userId);
    }
} catch (Throwable $exception) {
    $errorMessage = 'Unable to load notifications right now.';
}

$latestId = 0;
foreach ($notifications as $notification) {
    $latestId = max($latestId, (int)($notification['id'] ?? 0));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications | DENR Region XII DTMIS</title>
    <script>
        window.__DTMIS_OFFLINE_POLICY = <?php echo json_encode($offlinePolicy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.__DTMIS_OFFLINE_SYNC_LOG_URL = <?php echo json_encode($offlineSyncLogUrl); ?>;
    </script>
    <script src="<?php echo notifications_e(app_url('assets/js/offline-read-cache.js')); ?>"></script>
    <script src="<?php echo notifications_e(app_url('assets/js/offline-outbox.js')); ?>"></script>
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --line: #dbe5f0;
            --text: #1f3348;
            --muted: #617487;
            --accent: #2f7de1;
            --unread: #f3f9ff;
            --danger: #d92d20;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #0b1421;
                --card: #111827;
                --line: #1e293b;
                --text: #f1f5f9;
                --muted: #94a3b8;
                --accent: #60a5fa;
                --unread: #162335;
                --gradient-1: #0b1421;
                --gradient-2: #0e1726;
                --gradient-3: #080f1a;
            }
        }

        [data-theme="dark"] {
            --bg: #0b1421;
            --card: #111827;
            --line: #1e293b;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --accent: #60a5fa;
            --unread: #162335;
            --gradient-1: #0b1421;
            --gradient-2: #0e1726;
            --gradient-3: #080f1a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: var(--text);
            background: radial-gradient(circle at top right, var(--gradient-1, #e9f2ff) 0, var(--bg) 50%, var(--gradient-3, #eef3fa) 100%);
            min-height: 100vh;
        }

        .page {
            max-width: 980px;
            margin: 28px auto;
            padding: 0 16px 24px;
        }

        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .head-text h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: 0.01em;
        }

        .head-text p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .head-actions {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .pill {
            border: 1px solid var(--line);
            background: var(--card);
            color: var(--text);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .pill:hover {
            border-color: var(--accent);
            background: var(--unread);
        }

        .counter {
            display: inline-flex;
            min-width: 20px;
            height: 20px;
            border-radius: 999px;
            background: var(--danger);
            color: #fff;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            margin-left: 6px;
            padding: 0 6px;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--card);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(24, 50, 76, 0.08);
        }

        .notice {
            margin: 0;
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            font-size: 14px;
            color: #ef4444;
            background: rgba(239, 68, 68, 0.08);
        }

        .notif-list {
            display: block;
        }

        .notif-item {
            border-bottom: 1px solid var(--line);
            background: var(--card);
        }

        .notif-item:last-child {
            border-bottom: none;
        }

        .notif-item.is-unread {
            background: var(--unread);
        }

        .notif-item-link {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            text-decoration: none;
            color: inherit;
            padding: 14px 16px;
        }

        .notif-item-copy {
            min-width: 0;
            flex: 1 1 auto;
        }

        .notif-item-indicator {
            display: inline-flex;
            width: 10px;
            height: 10px;
            border-radius: 999px;
            border: 1px solid transparent;
            margin-top: 6px;
            flex: 0 0 auto;
        }

        .notif-item-indicator.is-yellow {
            background: #f59e0b;
            border-color: #fbbf24;
        }

        .notif-item-indicator.is-pink {
            background: #ec4899;
            border-color: #f472b6;
        }

        .notif-item-indicator.is-blue {
            background: #3b82f6;
            border-color: #60a5fa;
        }

        .notif-item-indicator.is-green {
            background: #10b981;
            border-color: #34d399;
        }

        .notif-item-indicator.is-neutral {
            background: #94a3b8;
            border-color: #cbd5e1;
        }

        .notif-item-link:focus-visible {
            outline: 2px solid #9bb9dc;
            outline-offset: -2px;
        }

        .notif-item h2 {
            margin: 0 0 6px;
            font-size: 17px;
            font-weight: 700;
        }

        .notif-item p {
            margin: 0 0 7px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }

        .notif-item time {
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }

        .empty {
            margin: 0;
            padding: 24px 16px;
            color: var(--muted);
            text-align: center;
            font-size: 14px;
        }

        @media (max-width: 680px) {
            .page {
                margin-top: 16px;
            }

            .page-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .head-actions {
                width: 100%;
            }

            .pill {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body data-role-key="<?php echo notifications_e($roleKey); ?>">
    <main class="page">
        <header class="page-head">
            <div class="head-text">
                <h1>Notifications</h1>
                <p><?php echo notifications_e($fullName); ?><?php echo $roleName !== '' ? ' | ' . notifications_e($roleName) : ''; ?></p>
            </div>
            <div class="head-actions">
                <a class="pill" href="<?php echo notifications_e($dashboardUrl); ?>">Back to Dashboard</a>
                <button id="markAllRead" type="button" class="pill">Mark all read <span id="unreadCount" class="counter">0</span></button>
            </div>
        </header>

        <section class="panel">
            <?php if ($errorMessage !== ''): ?>
            <p class="notice"><?php echo notifications_e($errorMessage); ?></p>
            <?php endif; ?>
            <div id="notificationList" class="notif-list" data-latest-id="<?php echo notifications_e((string)$latestId); ?>">
                <?php if (empty($notifications)): ?>
                <p class="empty">No notifications yet.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                            $notificationId = (int)($notification['id'] ?? 0);
                            $notificationDateTime = (string)($notification['datetime'] ?? '');
                            $notificationTrackingId = trim((string)($notification['tracking_id'] ?? ''));
                            $notificationIndicatorKeyRaw = strtolower(trim((string)($notification['indicator_key'] ?? '')));
                            $notificationIndicatorKey = in_array($notificationIndicatorKeyRaw, ['yellow', 'pink', 'blue', 'green', 'neutral'], true)
                                ? $notificationIndicatorKeyRaw
                                : 'neutral';
                            $notificationIndicatorLabel = trim((string)($notification['indicator_label'] ?? ''));
                            if ($notificationIndicatorLabel === '') {
                                if ($notificationIndicatorKey === 'yellow') {
                                    $notificationIndicatorLabel = 'Yellow - Urgent';
                                } elseif ($notificationIndicatorKey === 'blue') {
                                    $notificationIndicatorLabel = 'Blue - Complex/Highly Technical';
                                } elseif ($notificationIndicatorKey === 'green') {
                                    $notificationIndicatorLabel = 'Green - Released';
                                } elseif ($notificationIndicatorKey === 'pink') {
                                    $notificationIndicatorLabel = 'Pink - Simple';
                                } else {
                                    $notificationIndicatorLabel = 'Indicator';
                                }
                            }
                            $notificationLink = $notificationTrackingId !== ''
                                ? $trackingSlipUrl . '?tracking_id=' . rawurlencode($notificationTrackingId) . '&public=1'
                                : $notificationsPageUrl;
                        ?>
                <article
                    class="notif-item <?php echo !empty($notification['unread']) ? 'is-unread' : ''; ?>"
                    data-notification-id="<?php echo notifications_e((string)$notificationId); ?>"
                    data-datetime="<?php echo notifications_e($notificationDateTime); ?>"
                    data-indicator-key="<?php echo notifications_e($notificationIndicatorKey); ?>"
                >
                    <a class="notif-item-link" href="<?php echo notifications_e($notificationLink); ?>">
                        <span class="notif-item-indicator is-<?php echo notifications_e($notificationIndicatorKey); ?>" title="<?php echo notifications_e($notificationIndicatorLabel); ?>" aria-label="<?php echo notifications_e($notificationIndicatorLabel); ?>"></span>
                        <div class="notif-item-copy">
                            <h2><?php echo notifications_e((string)($notification['title'] ?? 'Update')); ?></h2>
                            <p><?php echo notifications_e((string)($notification['message'] ?? '')); ?></p>
                            <time datetime="<?php echo notifications_e($notificationDateTime); ?>"><?php echo notifications_e((string)($notification['timeLabel'] ?? 'Now')); ?></time>
                        </div>
                    </a>
                </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        (function () {
            const list = document.getElementById('notificationList');
            const markAllReadButton = document.getElementById('markAllRead');
            const unreadCountBadge = document.getElementById('unreadCount');
            if (!list) {
                return;
            }

            const currentUserId = <?= json_encode($userId) ?>;
            if (Number.isFinite(currentUserId) && currentUserId > 0) {
                window.__DTMIS_CACHE_SCOPE = 'user:' + String(currentUserId);
            }
            const trackingSlipPath = <?= json_encode($trackingSlipUrl) ?>;
            const notificationsPagePath = <?= json_encode($notificationsPageUrl) ?>;
            const feedPath = <?= json_encode($notificationFeedUrl) ?>;
            const soundPath = <?= json_encode($notificationSoundUrl) ?>;
            const readKey = 'DTMIS_notif_read_until_' + String(currentUserId > 0 ? currentUserId : 'global');
            const pollIntervalMs = 3000;

            let highestSeenId = Math.max(0, Number(list.getAttribute('data-latest-id') || 0));
            let pollInitialized = false;
            let pollInFlight = false;
            let soundUnlocked = false;
            let pendingSoundCount = 0;
            let pollTimerId = 0;
            const notificationAudio = soundPath ? new Audio(soundPath) : null;

            if (notificationAudio) {
                notificationAudio.preload = 'auto';
            }

            function parseDateValue(value) {
                const raw = String(value || '').trim();
                if (raw === '') {
                    return 0;
                }

                const isoLike = raw.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/);
                if (isoLike) {
                    const year = parseInt(isoLike[1], 10);
                    const monthIndex = parseInt(isoLike[2], 10) - 1;
                    const day = parseInt(isoLike[3], 10);
                    const hour = parseInt(String(isoLike[4] || '0'), 10);
                    const minute = parseInt(String(isoLike[5] || '0'), 10);
                    const second = parseInt(String(isoLike[6] || '0'), 10);
                    const ts = new Date(year, monthIndex, day, hour, minute, second, 0).getTime();
                    return Number.isFinite(ts) ? ts : 0;
                }

                const parsed = Date.parse(raw);
                return Number.isFinite(parsed) ? parsed : 0;
            }

            function normalizeLabelKey(value) {
                return String(value || '')
                    .toLowerCase()
                    .replace(/[_-]+/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function notificationIndicatorMeta(notification) {
                const rawKey = normalizeLabelKey(notification && notification.indicator_key ? notification.indicator_key : '');
                if (rawKey === 'yellow') {
                    return { key: 'yellow', className: 'is-yellow', fullLabel: 'Yellow - Urgent' };
                }
                if (rawKey === 'pink') {
                    return { key: 'pink', className: 'is-pink', fullLabel: 'Pink - Simple' };
                }
                if (rawKey === 'blue') {
                    return { key: 'blue', className: 'is-blue', fullLabel: 'Blue - Complex/Highly Technical' };
                }
                if (rawKey === 'green') {
                    return { key: 'green', className: 'is-green', fullLabel: 'Green - Released' };
                }

                const fallback = normalizeLabelKey(
                    String(notification && notification.indicator_label ? notification.indicator_label : '') + ' '
                    + String(notification && notification.title ? notification.title : '') + ' '
                    + String(notification && notification.message ? notification.message : '')
                );
                if (fallback.indexOf('yellow') !== -1 || fallback.indexOf('urgent') !== -1 || fallback.indexOf('overdue') !== -1) {
                    return { key: 'yellow', className: 'is-yellow', fullLabel: 'Yellow - Urgent' };
                }
                if (fallback.indexOf('blue') !== -1 || fallback.indexOf('complex') !== -1 || fallback.indexOf('highly technical') !== -1) {
                    return { key: 'blue', className: 'is-blue', fullLabel: 'Blue - Complex/Highly Technical' };
                }
                if (fallback.indexOf('green') !== -1 || fallback.indexOf('released') !== -1) {
                    return { key: 'green', className: 'is-green', fullLabel: 'Green - Released' };
                }
                if (fallback.indexOf('pink') !== -1 || fallback.indexOf('simple') !== -1) {
                    return { key: 'pink', className: 'is-pink', fullLabel: 'Pink - Simple' };
                }

                return { key: 'neutral', className: 'is-neutral', fullLabel: 'Indicator' };
            }

            function getReadUntilTs() {
                try {
                    const raw = String(localStorage.getItem(readKey) || '').trim();
                    const parsed = parseInt(raw, 10);
                    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
                } catch (error) {
                    return 0;
                }
            }

            function setReadUntilTs(ts) {
                try {
                    localStorage.setItem(readKey, String(ts));
                } catch (error) {
                    // Ignore local storage errors.
                }
            }

            function getNotificationItems() {
                return Array.from(list.querySelectorAll('.notif-item'));
            }

            function itemTimestamp(item) {
                if (!item) {
                    return 0;
                }
                const datetimeRaw = String(item.getAttribute('data-datetime') || '').trim();
                return parseDateValue(datetimeRaw);
            }

            function updateUnreadCount(count) {
                if (!unreadCountBadge) {
                    return;
                }
                const safeCount = Math.max(0, Number(count || 0));
                unreadCountBadge.textContent = String(safeCount);
                unreadCountBadge.style.display = safeCount > 0 ? 'inline-flex' : 'none';
            }

            function applyReadState() {
                const readUntil = getReadUntilTs();
                const items = getNotificationItems();
                let unread = 0;

                items.forEach(function (item) {
                    const isUnread = itemTimestamp(item) > readUntil;
                    item.classList.toggle('is-unread', isUnread);
                    if (isUnread) {
                        unread += 1;
                    }
                });

                updateUnreadCount(unread);
            }

            function playSoundIfAllowed() {
                if (!notificationAudio || !soundUnlocked) {
                    return;
                }
                try {
                    notificationAudio.currentTime = 0;
                    const playPromise = notificationAudio.play();
                    if (playPromise && typeof playPromise.catch === 'function') {
                        playPromise.catch(function () {
                            // Ignore browser autoplay restrictions.
                        });
                    }
                } catch (error) {
                    // Ignore browser autoplay restrictions.
                }
            }

            function playSoundBurst(count) {
                const safeCount = Math.max(0, Math.min(6, Number(count || 0)));
                if (safeCount <= 0) {
                    return;
                }
                if (!soundUnlocked) {
                    pendingSoundCount = Math.min(12, pendingSoundCount + safeCount);
                    return;
                }
                for (let index = 0; index < safeCount; index += 1) {
                    window.setTimeout(function () {
                        playSoundIfAllowed();
                    }, index * 260);
                }
            }

            function createNotificationItem(notification) {
                const id = Math.max(0, Number(notification && notification.id ? notification.id : 0));
                const title = String(notification && notification.title ? notification.title : 'Update');
                const message = String(notification && notification.message ? notification.message : '');
                const datetime = String(notification && notification.datetime ? notification.datetime : '').trim();
                const timeLabel = String(notification && notification.timeLabel ? notification.timeLabel : 'Now');
                        const trackingId = String(notification && notification.tracking_id ? notification.tracking_id : '').trim();
                        const indicatorMeta = notificationIndicatorMeta(notification);
                        const href = trackingId !== ''
                            ? trackingSlipPath + '?tracking_id=' + encodeURIComponent(trackingId) + '&public=1'
                            : notificationsPagePath;

                const article = document.createElement('article');
                article.className = 'notif-item is-unread';
                article.setAttribute('data-notification-id', String(id));
                article.setAttribute('data-datetime', datetime);
                article.setAttribute('data-indicator-key', indicatorMeta.key);

                const link = document.createElement('a');
                link.className = 'notif-item-link';
                link.href = href;

                const indicator = document.createElement('span');
                indicator.className = 'notif-item-indicator ' + indicatorMeta.className;
                indicator.title = indicatorMeta.fullLabel;
                indicator.setAttribute('aria-label', indicatorMeta.fullLabel);
                link.appendChild(indicator);

                const copy = document.createElement('div');
                copy.className = 'notif-item-copy';

                const heading = document.createElement('h2');
                heading.textContent = title;
                copy.appendChild(heading);

                const paragraph = document.createElement('p');
                paragraph.textContent = message;
                copy.appendChild(paragraph);

                const time = document.createElement('time');
                time.setAttribute('datetime', datetime);
                time.textContent = timeLabel;
                copy.appendChild(time);

                link.appendChild(copy);

                article.appendChild(link);
                return article;
            }

            function renderNotifications(notifications) {
                list.innerHTML = '';

                if (!Array.isArray(notifications) || notifications.length === 0) {
                    const empty = document.createElement('p');
                    empty.className = 'empty';
                    empty.textContent = 'No notifications yet.';
                    list.appendChild(empty);
                    updateUnreadCount(0);
                    return 0;
                }

                let latestId = 0;
                notifications.forEach(function (notification) {
                    latestId = Math.max(latestId, Math.max(0, Number(notification && notification.id ? notification.id : 0)));
                    list.appendChild(createNotificationItem(notification));
                });

                return latestId;
            }

            function pollNotifications() {
                if (!feedPath || pollInFlight) {
                    return;
                }
                pollInFlight = true;

                const requestUrl = feedPath + '?limit=50&t=' + String(Date.now());
                fetch(requestUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json' }
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('Unable to fetch notifications.');
                    }
                    return response.json();
                }).then(function (payload) {
                    if (!payload || payload.ok !== true || !Array.isArray(payload.notifications)) {
                        return;
                    }

                    const previousLatest = highestSeenId;
                    let newNotificationCount = 0;
                    if (pollInitialized) {
                        payload.notifications.forEach(function (notification) {
                            const id = Math.max(0, Number(notification && notification.id ? notification.id : 0));
                            if (id > previousLatest) {
                                newNotificationCount += 1;
                            }
                        });
                    }
                    const latestFromServer = Math.max(0, Number(payload.newest_id || 0));
                    const latestRendered = renderNotifications(payload.notifications);
                    const latestIncoming = Math.max(latestFromServer, latestRendered);
                    highestSeenId = Math.max(highestSeenId, latestIncoming);
                    applyReadState();

                    if (pollInitialized && newNotificationCount > 0) {
                        playSoundBurst(newNotificationCount);
                    }
                }).catch(function () {
                    // Keep page usable even if polling fails.
                }).finally(function () {
                    pollInFlight = false;
                    pollInitialized = true;
                });
            }

            list.addEventListener('click', function (event) {
                const item = event.target.closest('.notif-item');
                if (!item || !list.contains(item)) {
                    return;
                }

                const ts = itemTimestamp(item);
                if (ts > 0) {
                    setReadUntilTs(Math.max(getReadUntilTs(), ts));
                } else {
                    setReadUntilTs(Date.now());
                }
                applyReadState();
            });

            if (markAllReadButton) {
                markAllReadButton.addEventListener('click', function () {
                    setReadUntilTs(Date.now());
                    applyReadState();
                });
            }

            document.addEventListener('pointerdown', function () {
                soundUnlocked = true;
                if (pendingSoundCount > 0) {
                    const queuedCount = pendingSoundCount;
                    pendingSoundCount = 0;
                    playSoundBurst(queuedCount);
                }
            }, { once: true });
            document.addEventListener('keydown', function () {
                soundUnlocked = true;
                if (pendingSoundCount > 0) {
                    const queuedCount = pendingSoundCount;
                    pendingSoundCount = 0;
                    playSoundBurst(queuedCount);
                }
            }, { once: true });

            applyReadState();
            pollNotifications();
            pollTimerId = window.setInterval(function () {
                if (!document.hidden) {
                    pollNotifications();
                }
            }, pollIntervalMs);

            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    pollNotifications();
                }
            });

            window.addEventListener('beforeunload', function () {
                if (pollTimerId > 0) {
                    window.clearInterval(pollTimerId);
                }
            });

            // Sync theme with dashboard if available
            (function() {
                function syncTheme() {
                    try {
                        const theme = window.parent.document.documentElement.getAttribute('data-theme') 
                             || window.parent.document.body.getAttribute('data-theme');
                        if (theme) {
                            document.documentElement.setAttribute('data-theme', theme);
                        }
                    } catch(e) {}
                }
                syncTheme();
                const observer = new MutationObserver(syncTheme);
                try {
                    observer.observe(window.parent.document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
                    observer.observe(window.parent.document.body, { attributes: true, attributeFilter: ['data-theme'] });
                } catch(e) {}
            })();
        })();
    </script>
</body>
</html>
