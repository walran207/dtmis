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

$pageTitle = 'Input Forms Example | DENR Region XII eDATS';
$activeMenu = 'input_forms_example';
$footerYear = '2026';
$footerVersion = 'eDATS v2.0.0';
$viewHeading = 'Input Forms Example';
$viewSubtitle = 'Reusable form patterns for intake, routing, and filtering pages.';

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

        <main class="content">
            <?php include __DIR__ . '/partials/content-toolbar.php'; ?>

            <section class="form-demo-layout" aria-label="Form examples">
                <form class="card form-card" action="#" method="post">
                    <h2>Document Intake Form</h2>
                    <p class="helper-text">Example of a full-width intake form card.</p>
                    <div class="field-grid">
                        <label class="field">
                            <span>Subject</span>
                            <input type="text" placeholder="Enter document subject">
                        </label>
                        <label class="field">
                            <span>Source Type</span>
                            <select>
                                <option>Internal</option>
                                <option>External</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Document Type</span>
                            <select>
                                <option>Memo</option>
                                <option>Request Letter</option>
                                <option>Action Docket</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>ARTA Category</span>
                            <select>
                                <option>Simple</option>
                                <option>Complex</option>
                                <option>Highly Technical</option>
                            </select>
                        </label>
                        <label class="field field-full">
                            <span>Remarks</span>
                            <textarea rows="4" placeholder="Type workflow notes and action required"></textarea>
                        </label>
                    </div>
                    <div class="btn-row">
                        <button type="button" class="btn-secondary">Save Draft</button>
                        <button type="button" class="btn-primary">Submit Intake</button>
                    </div>
                </form>

                <form class="card form-card" action="#" method="post">
                    <h2>Routing Setup Form</h2>
                    <p class="helper-text">Example of route controls with check/radio inputs.</p>
                    <div class="field-grid">
                        <label class="field">
                            <span>Destination Office</span>
                            <select>
                                <option>PENRO South Cotabato</option>
                                <option>PENRO Sarangani</option>
                                <option>PENRO Cotabato</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Priority</span>
                            <select>
                                <option>Normal</option>
                                <option>Urgent</option>
                                <option>Critical</option>
                            </select>
                        </label>
                    </div>

                    <div class="check-grid">
                        <label><input type="checkbox" checked> Require approval before forward</label>
                        <label><input type="checkbox"> Mark as confidential</label>
                        <label><input type="checkbox" checked> Notify destination office</label>
                    </div>

                    <div class="check-grid radio-grid">
                        <label><input type="radio" name="mode" checked> Route now</label>
                        <label><input type="radio" name="mode"> Save for later</label>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn-secondary">Reset</button>
                        <button type="button" class="btn-primary">Apply Routing</button>
                    </div>
                </form>
            </section>

<?php include __DIR__ . '/partials/footer.php'; ?>
        </main>
    </div>

<?php include __DIR__ . '/partials/template-interactions.php'; ?>
