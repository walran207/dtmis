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

$pageTitle = 'Insight Grid Example | DENR Region XII eDATS';
$activeMenu = 'insight_grid_example';
$footerYear = '2026';
$footerVersion = 'eDATS v2.0.0';
$viewHeading = 'Insight Grid Example';
$viewSubtitle = 'Sample insight panels and dense analytics layout blocks.';

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

        <main class="content">
            <?php include __DIR__ . '/partials/content-toolbar.php'; ?>

            <section class="insight-grid" aria-label="Insight grid examples">
                <article class="card panel">
                    <h2>Turnaround Time Bands</h2>
                    <div class="age-grid">
                        <div class="age-tile"><p>0-1 day</p><strong>42</strong></div>
                        <div class="age-tile"><p>2-3 days</p><strong>27</strong></div>
                        <div class="age-tile"><p>4-7 days</p><strong>11</strong></div>
                        <div class="age-tile"><p>8+ days</p><strong>5</strong></div>
                    </div>
                </article>

                <article class="card panel">
                    <h2>Priority Distribution</h2>
                    <div class="progress-list">
                        <div class="progress-row"><div class="row-meta"><span>High</span><span>22%</span></div><div class="bar"><span style="width: 22%"></span></div></div>
                        <div class="progress-row"><div class="row-meta"><span>Medium</span><span>48%</span></div><div class="bar"><span style="width: 48%"></span></div></div>
                        <div class="progress-row"><div class="row-meta"><span>Low</span><span>30%</span></div><div class="bar"><span style="width: 30%"></span></div></div>
                    </div>
                </article>

                <article class="card panel">
                    <h2>Holder Mix</h2>
                    <div class="gender-wrap">
                        <div class="donut"><span>85</span></div>
                        <div class="legend">
                            <p><span class="dot male"></span> Office Actions <strong>53</strong></p>
                            <p><span class="dot female"></span> Pending Receive <strong>32</strong></p>
                        </div>
                    </div>
                </article>
            </section>

            <section class="card table-panel" aria-label="Insight matrix table">
                <div class="table-header">
                    <h2>Insight Matrix</h2>
                    <a href="#" class="table-link">Export</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Office</th>
                                <th>Pending</th>
                                <th>Due Soon</th>
                                <th>Overdue</th>
                                <th>Risk Flag</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>PENRO South Cotabato</td><td>14</td><td>6</td><td>2</td><td><span class="status-badge">Moderate</span></td></tr>
                            <tr><td>PENRO Sarangani</td><td>9</td><td>3</td><td>0</td><td><span class="status-badge">Stable</span></td></tr>
                            <tr><td>PENRO Cotabato</td><td>11</td><td>4</td><td>1</td><td><span class="status-badge">Moderate</span></td></tr>
                            <tr><td>PENRO Sultan Kudarat</td><td>17</td><td>8</td><td>3</td><td><span class="status-badge">Watch</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

<?php include __DIR__ . '/partials/footer.php'; ?>
        </main>
    </div>

<?php include __DIR__ . '/partials/template-interactions.php'; ?>
