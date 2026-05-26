<?php
$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$footerYear = (string)($footerYear ?? date('Y'));
$footerVersion = (string)($footerVersion ?? 'DTMIS v2.0.0');
?>
            <footer class="dashboard-footer" aria-label="Footer">
                <p class="footer-copy">&copy; <?php echo $esc($footerYear); ?> DENR Region XII. All rights reserved.</p>
                <div class="footer-meta">
                    <span><?php echo $esc($footerVersion); ?></span>
                    <a href="<?php echo $esc(app_url('support-center.php#privacy-notice')); ?>" data-support-modal="privacy-notice">Privacy Notice</a>
                    <a href="<?php echo $esc(app_url('support-center.php#terms-of-use')); ?>" data-support-modal="terms-of-use">Terms of Use</a>
                    <a href="<?php echo $esc(app_url('support-center.php#help-desk')); ?>" data-support-modal="help-desk">Help Desk</a>
                    <a href="<?php echo $esc(app_url('support-center.php#report-phishing')); ?>" data-support-modal="report-phishing">Report phishing</a>
                </div>
            </footer>
