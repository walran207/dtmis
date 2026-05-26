<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$loginUrl = app_url('auth/login.php');
$homeUrl = app_url('');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Center | DENR Region XII DTMIS</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #0a0f18;
            --panel: #111927;
            --panel-soft: #152133;
            --line: #223551;
            --text: #e8eef7;
            --muted: #9fb1c8;
            --accent: #5ea2ff;
            --accent-soft: rgba(94, 162, 255, 0.16);
            --success: #70d7a5;
            --warn: #f0b35d;
            --danger: #ff7f8d;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(94, 162, 255, 0.12), transparent 28%),
                linear-gradient(180deg, #0b1018 0%, #0a0f18 100%);
            color: var(--text);
        }

        a {
            color: var(--accent);
        }

        .support-shell {
            width: min(1080px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 56px;
        }

        .support-hero {
            display: grid;
            gap: 16px;
            padding: 28px;
            border: 1px solid var(--line);
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(21, 33, 51, 0.94), rgba(17, 25, 39, 0.96));
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
        }

        .support-eyebrow {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.14em;
            color: var(--warn);
            text-transform: uppercase;
        }

        .support-hero h1 {
            margin: 0;
            font-size: clamp(30px, 4vw, 44px);
            line-height: 1.08;
        }

        .support-hero p {
            margin: 0;
            max-width: 760px;
            color: var(--muted);
            line-height: 1.7;
        }

        .support-actions,
        .support-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .support-chip,
        .support-link-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 16px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--panel-soft);
            color: var(--text);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .support-link-btn.primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #08101b;
        }

        .support-grid {
            display: grid;
            gap: 18px;
            margin-top: 22px;
        }

        .support-card {
            padding: 24px;
            border: 1px solid var(--line);
            border-radius: 22px;
            background: rgba(17, 25, 39, 0.94);
        }

        .support-card h2 {
            margin: 0 0 10px;
            font-size: 24px;
        }

        .support-card p,
        .support-card li {
            color: var(--muted);
            line-height: 1.7;
        }

        .support-card ul,
        .support-card ol {
            margin: 14px 0 0 20px;
            padding: 0;
        }

        .support-callout {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid var(--line);
            background: rgba(94, 162, 255, 0.08);
            color: var(--text);
        }

        .support-callout.warn {
            background: rgba(240, 179, 93, 0.1);
        }

        .support-callout.danger {
            background: rgba(255, 127, 141, 0.1);
        }

        .support-section-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .support-mini-link {
            color: var(--success);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .support-footer {
            margin-top: 24px;
            color: var(--muted);
            font-size: 13px;
            text-align: center;
        }

        @media (max-width: 640px) {
            .support-shell {
                width: min(100% - 20px, 1080px);
                padding-top: 20px;
            }

            .support-hero,
            .support-card {
                padding: 20px;
                border-radius: 18px;
            }
        }
    </style>
</head>
<body>
    <main class="support-shell">
        <section class="support-hero" aria-label="Support center intro">
            <p class="support-eyebrow">DENR Region XII DTMIS</p>
            <h1>Support Center</h1>
            <p>Use this page for the legal notices, basic support guidance, and phishing-reporting steps connected to the footer links across the system.</p>
            <div class="support-actions">
                <a class="support-link-btn primary" href="<?php echo $esc($loginUrl); ?>">Back to Sign In</a>
                <a class="support-link-btn" href="<?php echo $esc($homeUrl); ?>">System Home</a>
            </div>
            <nav class="support-nav" aria-label="Support sections">
                <a class="support-chip" href="#privacy-notice">Privacy Notice</a>
                <a class="support-chip" href="#terms-of-use">Terms of Use</a>
                <a class="support-chip" href="#help-desk">Help Desk</a>
                <a class="support-chip" href="#report-phishing">Report phishing</a>
            </nav>
        </section>

        <section class="support-grid" aria-label="Support details">
            <article id="privacy-notice" class="support-card">
                <h2>Privacy Notice</h2>
                <p>DTMIS processes document-routing information needed for official DENR work. This can include user account details, office routing history, document metadata, timestamps, and uploaded attachments that are part of the records workflow.</p>
                <ul>
                    <li>Information inside the system should be used only for authorized government work.</li>
                    <li>Access should follow your assigned role and office responsibilities.</li>
                    <li>Uploaded files and tracking history may be retained for audit, records, and compliance needs.</li>
                    <li>Sensitive data should not be copied or shared outside approved DENR channels.</li>
                </ul>
                <div class="support-callout">If you believe a record is exposed to the wrong office or user, report it immediately through your system administrator or responsible records unit.</div>
            </article>

            <article id="terms-of-use" class="support-card">
                <h2>Terms of Use</h2>
                <p>This system is intended for official DENR Region XII document tracking and records-management activities only. By using the system, users are expected to handle records responsibly and keep credentials secure.</p>
                <ol>
                    <li>Use only your assigned account and role permissions.</li>
                    <li>Do not upload malware, misleading files, or non-work-related material.</li>
                    <li>Do not attempt to bypass routing, audit, or security controls without authorization.</li>
                    <li>Do not share confidential records outside approved channels.</li>
                    <li>Log out or lock your workstation when leaving your device unattended.</li>
                </ol>
                <div class="support-callout warn">Unauthorized access, tampering, or misuse may result in administrative, civil, or criminal action.</div>
            </article>

            <article id="help-desk" class="support-card">
                <h2>Help Desk</h2>
                <p>If you need assistance with login, routing, attachments, QR tools, or dashboard behavior, prepare a short issue summary before contacting your support focal person or system administrator.</p>
                <ul>
                    <li>What page you were on</li>
                    <li>The tracking ID or document involved, if any</li>
                    <li>What you expected to happen</li>
                    <li>What actually happened</li>
                    <li>A screenshot of the error or unexpected screen</li>
                </ul>
                <div class="support-callout">Recommended first contact: your office DTMIS focal person, records unit, or the system administrator assigned to your deployment.</div>
                <div class="support-section-links">
                    <a class="support-mini-link" href="#report-phishing">Need a security report instead?</a>
                </div>
            </article>

            <article id="report-phishing" class="support-card">
                <h2>Report phishing</h2>
                <p>If you received a suspicious email, message, attachment, or login prompt that appears related to DTMIS or DENR, do not click more links and do not submit credentials.</p>
                <ul>
                    <li>Take a screenshot of the suspicious message or page.</li>
                    <li>Keep the sender address, subject line, and time received.</li>
                    <li>Do not forward the message to large groups unless instructed by your security lead.</li>
                    <li>Report it immediately to your IT/security focal person or system administrator.</li>
                    <li>If you already entered your password, change it as soon as possible and report the incident urgently.</li>
                </ul>
                <div class="support-callout danger">Urgent: if credentials may have been exposed, notify your administrator right away so access can be reviewed and protected.</div>
            </article>
        </section>

        <p class="support-footer">DENR Region XII DTMIS support information page.</p>
    </main>
</body>
</html>
