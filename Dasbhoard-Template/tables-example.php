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

$pageTitle = 'Tables Example | DENR Region XII eDATS';
$activeMenu = 'tables_example';
$footerYear = '2026';
$footerVersion = 'eDATS v2.0.0';
$viewHeading = 'Tables Example';
$viewSubtitle = 'Reusable table layouts for queue views, archives, and monitoring pages.';

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

        <main class="content">
            <?php include __DIR__ . '/partials/content-toolbar.php'; ?>

            <section class="stats-grid" aria-label="Table summary stats">
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon blue"></span></div>
                    <p class="stat-label">Rows in Queue</p>
                    <p class="stat-value">128</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon orange"></span></div>
                    <p class="stat-label">Filtered Rows</p>
                    <p class="stat-value">43</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon violet"></span></div>
                    <p class="stat-label">Overdue Rows</p>
                    <p class="stat-value">7</p>
                </article>
                <article class="card stat-card">
                    <div class="stat-head"><span class="stat-icon green"></span></div>
                    <p class="stat-label">Export Ready</p>
                    <p class="stat-value">Yes</p>
                </article>
            </section>

            <section class="card table-panel" aria-label="Primary queue table example">
                <div class="table-header">
                    <h2>Queue Table (Primary)</h2>
                    <a href="#" class="table-link">Export CSV</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Tracking ID</th>
                                <th>Subject</th>
                                <th>Office</th>
                                <th>Status</th>
                                <th>Age</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>R12-Q-2026-001</td>
                                <td>Site compliance memo</td>
                                <td>PENRO South Cotabato</td>
                                <td><span class="status-badge">Received</span></td>
                                <td>5h</td>
                                <td>View | Receive | Forward</td>
                            </tr>
                            <tr>
                                <td>R12-Q-2026-002</td>
                                <td>ARTA validation request</td>
                                <td>PENRO Sarangani</td>
                                <td><span class="status-badge">Pending</span></td>
                                <td>14h</td>
                                <td>View | Pending | Return</td>
                            </tr>
                            <tr>
                                <td>R12-Q-2026-003</td>
                                <td>Forwarding endorsement</td>
                                <td>PENRO Cotabato</td>
                                <td><span class="status-badge">Approved</span></td>
                                <td>1d</td>
                                <td>View | Forward</td>
                            </tr>
                            <tr>
                                <td>R12-Q-2026-004</td>
                                <td>Regional feedback package</td>
                                <td>PENRO Sultan Kudarat</td>
                                <td><span class="status-badge">Returned</span></td>
                                <td>2d</td>
                                <td>View | Comply | Resubmit</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="table-demo-grid" aria-label="Additional table patterns">
                <article class="card table-panel">
                    <div class="table-header">
                        <h2>Compact Metrics Table</h2>
                        <a href="#" class="table-link">Print</a>
                    </div>
                    <div class="table-wrap compact-table-wrap">
                        <table class="compact-table">
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th>Pending</th>
                                    <th>Due Soon</th>
                                    <th>Overdue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Unit 1</td><td>8</td><td>2</td><td>0</td></tr>
                                <tr><td>Unit 2</td><td>11</td><td>4</td><td>1</td></tr>
                                <tr><td>Unit 3</td><td>5</td><td>1</td><td>0</td></tr>
                                <tr><td>Unit 4</td><td>9</td><td>3</td><td>2</td></tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="card table-panel">
                    <div class="table-header">
                        <h2>Action Log Table</h2>
                        <a href="#" class="table-link">View All</a>
                    </div>
                    <div class="table-wrap">
                        <table class="striped-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Action</th>
                                    <th>User</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>09:15</td><td>Received</td><td>M. Santos</td><td>Validated inbound package.</td></tr>
                                <tr><td>09:38</td><td>Approved</td><td>F. Alicerto</td><td>Approved for forward stage.</td></tr>
                                <tr><td>10:05</td><td>Forwarded</td><td>C. Mendoza</td><td>Forwarded to PACDO.</td></tr>
                                <tr><td>10:48</td><td>Returned</td><td>A. Reyes</td><td>Needs attachment correction.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>

<?php include __DIR__ . '/partials/footer.php'; ?>
        </main>
    </div>

<?php include __DIR__ . '/partials/template-interactions.php'; ?>
