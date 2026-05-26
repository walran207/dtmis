<?php
declare(strict_types=1);

$profileSettingsNotice = is_array($profileSettingsNotice ?? null) ? $profileSettingsNotice : ['type' => '', 'message' => ''];
$profileSettingsEmailErrors = is_array($profileSettingsEmailErrors ?? null) ? $profileSettingsEmailErrors : ['email' => '', 'current_password' => ''];
$profileSettingsPasswordErrors = is_array($profileSettingsPasswordErrors ?? null) ? $profileSettingsPasswordErrors : ['current_password' => '', 'new_password' => '', 'confirm_password' => ''];
$profileSettingsView = is_array($profileSettingsView ?? null) ? $profileSettingsView : ['display_name' => '', 'username' => '', 'email' => '', 'role_name' => '', 'role_label' => ''];
$profileSettingsFormState = is_array($profileSettingsFormState ?? null) ? $profileSettingsFormState : ['email' => ''];
$profileSettingsFormAction = (string)($profileSettingsFormAction ?? '');
$csrfTokenValue = (string)($_SESSION['csrf_token'] ?? '');
$profileSettingsNoticeType = (string)($profileSettingsNotice['type'] ?? '');
$profileSettingsNoticeMessage = (string)($profileSettingsNotice['message'] ?? '');
$profileSettingsNoticeTarget = (string)($profileSettingsNotice['target'] ?? '');
$emailAlertVisible = $profileSettingsNoticeMessage !== '' && ($profileSettingsNoticeTarget === 'email' || $profileSettingsNoticeTarget === 'general');
$passwordAlertVisible = $profileSettingsNoticeMessage !== '' && ($profileSettingsNoticeTarget === 'password' || $profileSettingsNoticeTarget === 'general');
?>
<section class="profile-settings-shell" aria-label="Profile settings workspace">
    <header class="card profile-settings-overview">
        <div class="profile-settings-overview-head">
            <p class="profile-settings-kicker">Account Settings</p>
            <h1><?= e((string)($profileSettingsView['display_name'] ?? 'Your Account')) ?></h1>
            <p>Use this page to update your email address or password.</p>
        </div>
        <div class="profile-settings-meta-list">
            <div class="profile-settings-meta-item">
                <span class="label">Username</span>
                <strong><?= e((string)($profileSettingsView['username'] ?? '')) ?></strong>
            </div>
            <div class="profile-settings-meta-item">
                <span class="label">Role</span>
                <strong><?= e((string)($profileSettingsView['role_label'] ?? $profileSettingsView['role_name'] ?? '')) ?></strong>
            </div>
            <div class="profile-settings-meta-item">
                <span class="label">Current Email</span>
                <strong><?= e((string)($profileSettingsView['email'] ?? '')) ?></strong>
            </div>
        </div>
    </header>

    <div class="profile-settings-stack">
    <article class="card profile-settings-panel">
        <div class="profile-settings-panel-head profile-settings-panel-head-compact">
            <h2>Update Email</h2>
            <p>Enter a new email address, then confirm using your current password.</p>
        </div>

        <?php if ($emailAlertVisible && $profileSettingsNoticeType !== ''): ?>
        <p class="profile-settings-alert is-<?= e($profileSettingsNoticeType) ?>" role="status"><?= e($profileSettingsNoticeMessage) ?></p>
        <?php endif; ?>

        <form class="profile-settings-form" method="post" action="<?= e($profileSettingsFormAction) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfTokenValue) ?>">
            <input type="hidden" name="profile_action" value="update_email">

            <div class="profile-settings-current-value">
                <span>Current email</span>
                <strong><?= e((string)($profileSettingsView['email'] ?? '')) ?></strong>
            </div>

            <label class="profile-settings-field">
                <span>New Email</span>
                <input type="email" name="email" maxlength="100" value="<?= e((string)($profileSettingsFormState['email'] ?? '')) ?>" placeholder="name@denr.gov.ph" required>
                <?php if ($profileSettingsEmailErrors['email'] !== ''): ?>
                <small class="profile-settings-error"><?= e($profileSettingsEmailErrors['email']) ?></small>
                <?php endif; ?>
            </label>

            <label class="profile-settings-field">
                <span>Current Password</span>
                <input type="password" name="current_password" autocomplete="current-password" placeholder="Enter current password" required>
                <?php if ($profileSettingsEmailErrors['current_password'] !== ''): ?>
                <small class="profile-settings-error"><?= e($profileSettingsEmailErrors['current_password']) ?></small>
                <?php endif; ?>
            </label>

            <div class="profile-settings-actions">
                <button type="submit" class="profile-settings-btn">Save Email</button>
            </div>
        </form>
    </article>

    <article class="card profile-settings-panel">
        <div class="profile-settings-panel-head profile-settings-panel-head-compact">
            <h2>Change Password</h2>
            <p>Use your current password, then set a new one that meets the security rules.</p>
        </div>

        <?php if ($passwordAlertVisible && $profileSettingsNoticeType !== ''): ?>
        <p class="profile-settings-alert is-<?= e($profileSettingsNoticeType) ?>" role="status"><?= e($profileSettingsNoticeMessage) ?></p>
        <?php endif; ?>

        <form class="profile-settings-form" method="post" action="<?= e($profileSettingsFormAction) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfTokenValue) ?>">
            <input type="hidden" name="profile_action" value="update_password">

            <label class="profile-settings-field">
                <span>Current Password</span>
                <div class="profile-settings-input-wrap">
                    <input id="profilePasswordCurrent" type="password" name="current_password" autocomplete="current-password" placeholder="Enter current password" required>
                    <button type="button" class="profile-password-toggle" data-target="profilePasswordCurrent" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
                <?php if ($profileSettingsPasswordErrors['current_password'] !== ''): ?>
                <small class="profile-settings-error"><?= e($profileSettingsPasswordErrors['current_password']) ?></small>
                <?php endif; ?>
            </label>

            <label class="profile-settings-field">
                <span>New Password</span>
                <div class="profile-settings-input-wrap">
                    <input id="profilePasswordNew" type="password" name="new_password" autocomplete="new-password" placeholder="Enter new password" required>
                    <button type="button" class="profile-password-toggle" data-target="profilePasswordNew" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
                <?php if ($profileSettingsPasswordErrors['new_password'] !== ''): ?>
                <small class="profile-settings-error"><?= e($profileSettingsPasswordErrors['new_password']) ?></small>
                <?php endif; ?>
            </label>

            <label class="profile-settings-field">
                <span>Confirm New Password</span>
                <div class="profile-settings-input-wrap">
                    <input id="profilePasswordConfirm" type="password" name="confirm_password" autocomplete="new-password" placeholder="Repeat new password" required>
                    <button type="button" class="profile-password-toggle" data-target="profilePasswordConfirm" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
                <?php if ($profileSettingsPasswordErrors['confirm_password'] !== ''): ?>
                <small class="profile-settings-error"><?= e($profileSettingsPasswordErrors['confirm_password']) ?></small>
                <?php endif; ?>
            </label>

            <p class="profile-settings-hint">Password rules: at least 10 characters with uppercase, lowercase, number, and symbol.</p>

            <div class="profile-settings-actions">
                <button type="submit" class="profile-settings-btn">Update Password</button>
            </div>
        </form>
    </article>
    </div>
</section>
<script>
    (function () {
        const toggles = document.querySelectorAll('.profile-password-toggle[data-target]');

        toggles.forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                const targetId = toggle.getAttribute('data-target');
                const input = targetId ? document.getElementById(targetId) : null;
                if (!input) {
                    return;
                }

                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                toggle.textContent = isHidden ? 'Hide' : 'Show';
                toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                toggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            });
        });
    })();
</script>
