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

$pageTitle = 'Stats & Cards Example | DENR Region XII eDATS';
$activeMenu = 'stats_cards_example';
$footerYear = '2026';
$footerVersion = 'eDATS v2.0.0';
$viewHeading = 'Stats and Cards Example';
$viewSubtitle = 'Sample KPI blocks and card variants for role dashboards.';

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

        <main class="content">
            <?php include __DIR__ . '/partials/content-toolbar.php'; ?>

            <section class="stats-grid" aria-label="Primary KPI cards">
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon blue"></span><span class="live-pill">Live</span></div>
                    <p class="stat-label">Pending Receive</p>
                    <p class="stat-value">18</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon orange"></span></div>
                    <p class="stat-label">Pending Approval</p>
                    <p class="stat-value">11</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon violet"></span></div>
                    <p class="stat-label">Pending Forward</p>
                    <p class="stat-value">8</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon green"></span></div>
                    <p class="stat-label">Released</p>
                    <p class="stat-value">23</p>
                </article>
            </section>

            <section class="cards-showcase" aria-label="Card variants">
                <article class="card card-compact">
                    <h2>Compliance Note</h2>
                    <p>Use this compact card for short reminders and callouts.</p>
                    <span class="chip">Policy</span>
                </article>
                <article class="card card-compact">
                    <h2>Workflow Tip</h2>
                    <p>Keep `Receive -> Approve -> Forward` visible in high-action pages.</p>
                    <span class="chip">Best Practice</span>
                </article>
                <article class="card card-compact">
                    <h2>Alert Summary</h2>
                    <p>Overdue items should always be elevated to a colored indicator.</p>
                    <span class="chip warning">At-Risk</span>
                </article>
            </section>

            <section class="example-grid" aria-label="Extended KPI cards">
                <article class="card panel">
                    <h2>Weekly Snapshot</h2>
                    <div class="kpi-row">
                        <div class="kpi-tile"><span>Received</span><strong>128</strong></div>
                        <div class="kpi-tile"><span>Approved</span><strong>94</strong></div>
                        <div class="kpi-tile"><span>Forwarded</span><strong>88</strong></div>
                    </div>
                </article>
                <article class="card panel">
                    <h2>Operational Health</h2>
                    <div class="progress-list">
                        <div class="progress-row">
                            <div class="row-meta"><span>On-time processing</span><span>96%</span></div>
                            <div class="bar"><span style="width: 96%"></span></div>
                        </div>
                        <div class="progress-row">
                            <div class="row-meta"><span>Data completeness</span><span>91%</span></div>
                            <div class="bar"><span style="width: 91%"></span></div>
                        </div>
                    </div>
                </article>
            </section>

<?php include __DIR__ . '/partials/footer.php'; ?>
        </main>
    </div>

<?php include __DIR__ . '/partials/template-interactions.php'; ?>
