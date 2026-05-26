<?php
$supportModalEsc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$supportCenterBaseUrl = app_url('support-center.php');
?>
    <div id="supportModal" class="action-modal support-modal" hidden>
        <button type="button" class="action-modal-backdrop" data-support-modal-close="true" aria-label="Close support dialog"></button>
        <div class="action-modal-dialog support-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="supportModalTitle">
            <div class="action-modal-form support-modal-shell">
                <div class="support-modal-head">
                    <p class="support-modal-eyebrow">DENR Region XII DTMIS</p>
                    <h3 id="supportModalTitle" class="action-modal-title">Support</h3>
                    <p id="supportModalIntro" class="action-modal-meta"></p>
                </div>
                <div id="supportModalContent" class="support-modal-content"></div>
                <div class="action-modal-actions support-modal-actions">
                    <a id="supportModalFallbackLink" class="action-modal-btn action-modal-btn-secondary" href="<?php echo $supportModalEsc($supportCenterBaseUrl); ?>">Open full page</a>
                    <button type="button" class="action-modal-btn action-modal-btn-primary" data-support-modal-close="true">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="supportModalTemplates" hidden>
        <section
            data-support-modal-section="privacy-notice"
            data-support-modal-title="Privacy Notice"
            data-support-modal-intro="How DTMIS information should be handled inside official DENR workflows."
        >
            <p>DTMIS processes document-routing information needed for official DENR work. This can include user account details, office routing history, document metadata, timestamps, and uploaded attachments that are part of the records workflow.</p>
            <ul>
                <li>Information inside the system should be used only for authorized government work.</li>
                <li>Access should follow your assigned role and office responsibilities.</li>
                <li>Uploaded files and tracking history may be retained for audit, records, and compliance needs.</li>
                <li>Sensitive data should not be copied or shared outside approved DENR channels.</li>
            </ul>
            <div class="support-modal-callout">If you believe a record is exposed to the wrong office or user, report it immediately through your system administrator or responsible records unit.</div>
        </section>

        <section
            data-support-modal-section="terms-of-use"
            data-support-modal-title="Terms of Use"
            data-support-modal-intro="Baseline rules for proper and authorized use of the system."
        >
            <p>This system is intended for official DENR Region XII document tracking and records-management activities only. By using the system, users are expected to handle records responsibly and keep credentials secure.</p>
            <ol>
                <li>Use only your assigned account and role permissions.</li>
                <li>Do not upload malware, misleading files, or non-work-related material.</li>
                <li>Do not attempt to bypass routing, audit, or security controls without authorization.</li>
                <li>Do not share confidential records outside approved channels.</li>
                <li>Log out or lock your workstation when leaving your device unattended.</li>
            </ol>
            <div class="support-modal-callout warn">Unauthorized access, tampering, or misuse may result in administrative, civil, or criminal action.</div>
        </section>

        <section
            data-support-modal-section="help-desk"
            data-support-modal-title="Help Desk"
            data-support-modal-intro="What to prepare before asking for help with login, routing, or document actions."
        >
            <p>If you need assistance with login, routing, attachments, QR tools, or dashboard behavior, prepare a short issue summary before contacting your support focal person or system administrator.</p>
            <ul>
                <li>What page you were on</li>
                <li>The tracking ID or document involved, if any</li>
                <li>What you expected to happen</li>
                <li>What actually happened</li>
                <li>A screenshot of the error or unexpected screen</li>
            </ul>
            <div class="support-modal-callout">Recommended first contact: your office DTMIS focal person, records unit, or the system administrator assigned to your deployment.</div>
        </section>

        <section
            data-support-modal-section="report-phishing"
            data-support-modal-title="Report phishing"
            data-support-modal-intro="What to do if a message, attachment, or login prompt looks suspicious."
        >
            <p>If you received a suspicious email, message, attachment, or login prompt that appears related to DTMIS or DENR, do not click more links and do not submit credentials.</p>
            <ul>
                <li>Take a screenshot of the suspicious message or page.</li>
                <li>Keep the sender address, subject line, and time received.</li>
                <li>Do not forward the message to large groups unless instructed by your security lead.</li>
                <li>Report it immediately to your IT or security focal person or system administrator.</li>
                <li>If you already entered your password, change it as soon as possible and report the incident urgently.</li>
            </ul>
            <div class="support-modal-callout danger">Urgent: if credentials may have been exposed, notify your administrator right away so access can be reviewed and protected.</div>
        </section>
    </div>
