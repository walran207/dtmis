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

$pageTitle = 'Charts Example | DENR Region XII eDATS';
$activeMenu = 'charts_example';
$footerYear = '2026';
$footerVersion = 'eDATS v2.0.0';
$viewHeading = 'Charts Example';
$viewSubtitle = 'Sample chart components you can reuse in your dashboards.';

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

        <main class="content">
            <?php include __DIR__ . '/partials/content-toolbar.php'; ?>

            <section class="stats-grid" aria-label="Chart quick stats">
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon blue"></span></div>
                    <p class="stat-label">Avg Daily Routing</p>
                    <p class="stat-value">48</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon orange"></span></div>
                    <p class="stat-label">Peak Queue</p>
                    <p class="stat-value">92</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon violet"></span></div>
                    <p class="stat-label">Resolved Today</p>
                    <p class="stat-value">37</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon green"></span></div>
                    <p class="stat-label">Compliance Rate</p>
                    <p class="stat-value">96%</p>
                </article>
            </section>

            <section class="example-grid" aria-label="Chart library">
                <article class="card panel chart-card">
                    <h2>Weekly Volume (Bar)</h2>
                    <div class="mini-bar-chart" aria-hidden="true">
                        <div class="mini-bar"><span style="height: 42%"></span><label>Mon</label></div>
                        <div class="mini-bar"><span style="height: 61%"></span><label>Tue</label></div>
                        <div class="mini-bar"><span style="height: 55%"></span><label>Wed</label></div>
                        <div class="mini-bar"><span style="height: 74%"></span><label>Thu</label></div>
                        <div class="mini-bar"><span style="height: 88%"></span><label>Fri</label></div>
                        <div class="mini-bar"><span style="height: 63%"></span><label>Sat</label></div>
                        <div class="mini-bar"><span style="height: 49%"></span><label>Sun</label></div>
                    </div>
                </article>

                <article class="card panel chart-card">
                    <h2>Queue Trend (Line)</h2>
                    <div class="line-chart-wrap" role="img" aria-label="Queue trend line chart">
                        <svg class="line-chart-svg" viewBox="0 0 320 150" preserveAspectRatio="none">
                            <polyline points="0,115 45,95 90,100 135,76 180,72 225,54 270,68 320,48" />
                        </svg>
                        <div class="line-chart-legend">
                            <span><i></i> Pending Queue</span>
                            <strong>+14% vs last week</strong>
                        </div>
                    </div>
                </article>

                <article class="card panel chart-card">
                    <h2>Status Split (Progress)</h2>
                    <div class="progress-list">
                        <div class="progress-row">
                            <div class="row-meta"><span>Received</span><span>44%</span></div>
                            <div class="bar"><span style="width: 44%"></span></div>
                        </div>
                        <div class="progress-row">
                            <div class="row-meta"><span>Approved</span><span>31%</span></div>
                            <div class="bar"><span style="width: 31%"></span></div>
                        </div>
                        <div class="progress-row">
                            <div class="row-meta"><span>Forwarded</span><span>18%</span></div>
                            <div class="bar"><span style="width: 18%"></span></div>
                        </div>
                        <div class="progress-row">
                            <div class="row-meta"><span>Returned</span><span>7%</span></div>
                            <div class="bar"><span style="width: 7%"></span></div>
                        </div>
                    </div>
                </article>
            </section>

<?php include __DIR__ . '/partials/footer.php'; ?>
        </main>
    </div>

<?php include __DIR__ . '/partials/template-interactions.php'; ?>
