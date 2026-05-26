<?php
$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$footerYear = (string)($footerYear ?? date('Y'));
$footerVersion = (string)($footerVersion ?? 'DTMIS v2.0.0');
?>
            <footer class="dashboard-footer" aria-label="Footer">
                <p class="footer-copy">&copy; <?php echo $esc($footerYear); ?> DENR Region XII. All rights reserved.</p>
                <div class="footer-meta">
                    <span><?php echo $esc($footerVersion); ?></span>
                    <a href="#">Privacy Notice</a>
                    <a href="#">Terms of Use</a>
                    <a href="#">Help Desk</a>
                </div>
            </footer>
