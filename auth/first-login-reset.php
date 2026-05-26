<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/security.php';

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$loginUrl = app_url('auth/login.php');
$resetUrl = app_url('auth/first-login-reset.php');
$pwaManifestPath = app_url('manifest.webmanifest');
$offlineReadCacheScript = app_url('assets/js/offline-read-cache.js');
$offlineOutboxScript = app_url('assets/js/offline-outbox.js');
$pwaBootstrapScript = app_url('assets/js/pwa-init.js');
$pwaBasePath = rtrim((string)app_url(''), '/');
if ($pwaBasePath === '') {
    $pwaBasePath = '/';
}

$forceResetUserId = (int)($_SESSION['force_reset_user_id'] ?? 0);
$forceResetUsername = auth_normalize_username((string)($_SESSION['force_reset_username'] ?? ''));

if ($forceResetUserId <= 0 || $forceResetUsername === '') {
    $_SESSION['flash_success'] = 'Please sign in first before setting a new password.';
    app_redirect('auth/login.php');
}

$fieldErrors = [
    'password' => '',
    'confirm_password' => '',
];
$formError = '';
$user = false;

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare(
        'SELECT
            u.id,
            u.username,
            u.email,
            u.password_hash,
            u.first_name,
            u.last_name,
            u.role_id,
            u.office_id,
            u.is_active,
            u.must_change_password,
            r.name AS role_name
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id
           AND LOWER(u.username) = LOWER(:username)'
    );
    $stmt->execute([
        'id' => $forceResetUserId,
        'username' => $forceResetUsername,
    ]);
    $user = $stmt->fetch();
} catch (Throwable $exception) {
    $formError = 'Unable to load your account right now. Please try again.';
}

if (!$user || (int)($user['is_active'] ?? 0) !== 1) {
    unset($_SESSION['force_reset_user_id'], $_SESSION['force_reset_username'], $_SESSION['force_reset_email'], $_SESSION['pending_remember_me']);
    $_SESSION['flash_success'] = 'Your first-login reset session expired. Please sign in again.';
    app_redirect('auth/login.php');
}

if ((int)($user['must_change_password'] ?? 0) !== 1) {
    unset($_SESSION['force_reset_user_id'], $_SESSION['force_reset_username'], $_SESSION['force_reset_email'], $_SESSION['pending_remember_me']);
    $_SESSION['flash_success'] = 'Your password is already updated. Please sign in.';
    app_redirect('auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $formError === '') {
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (!hash_equals((string)$_SESSION['csrf_token'], $csrfToken)) {
        $formError = 'Your session expired. Please refresh and try again.';
    }

    if ($password === '') {
        $fieldErrors['password'] = 'New password is required.';
    } elseif (!auth_password_meets_policy($password)) {
        $fieldErrors['password'] = 'Use at least 10 characters with uppercase, lowercase, number, and symbol.';
    } elseif (password_verify($password, (string)($user['password_hash'] ?? ''))) {
        $fieldErrors['password'] = 'New password must be different from your current password.';
    }

    if ($confirmPassword === '') {
        $fieldErrors['confirm_password'] = 'Confirm your new password.';
    } elseif ($password !== '' && $confirmPassword !== $password) {
        $fieldErrors['confirm_password'] = 'Passwords do not match.';
    }

    if (
        $formError === ''
        && $fieldErrors['password'] === ''
        && $fieldErrors['confirm_password'] === ''
    ) {
        try {
            $update = $pdo->prepare(
                'UPDATE users
                 SET password_hash = :password_hash,
                     must_change_password = 0,
                     password_changed_at = SYSDATETIME(),
                     failed_login_attempts = 0,
                     locked_until = NULL
                 WHERE id = :id'
            );
            $update->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => (int)$user['id'],
            ]);

            auth_log_security_event($pdo, [
                'user_id' => (int)$user['id'],
                'email' => (string)$user['email'],
                'event_type' => 'FIRST_LOGIN_PASSWORD_CHANGED',
                'event_status' => 'SUCCESS',
                'remarks' => 'First-login password update completed.',
            ]);

            auth_set_authenticated_session([
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role_id' => $user['role_id'],
                'role_name' => $user['role_name'],
                'office_id' => $user['office_id'],
            ]);
            auth_apply_remember_me_cookie(!empty($_SESSION['pending_remember_me']));
            unset($_SESSION['pending_remember_me'], $_SESSION['force_reset_user_id'], $_SESSION['force_reset_username'], $_SESSION['force_reset_email']);
            auth_clear_pending_mfa_session();

            $welcomeFirstName = trim((string)($user['first_name'] ?? ''));
            $_SESSION['show_welcome_loader'] = 1;
            $_SESSION['welcome_loader_message'] = $welcomeFirstName !== ''
                ? 'Welcome, ' . $welcomeFirstName . '!'
                : 'Welcome!';

            app_redirect_to_role_dashboard((string)$user['role_name']);
        } catch (Throwable $exception) {
            $formError = 'Unable to update password right now. Please contact your administrator.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1f4d7a">
    <title>First Login Reset | DENR Region XII DTMIS</title>
    <link rel="manifest" href="<?php echo htmlspecialchars((string)$pwaManifestPath, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars((string)app_url('assets/Logo.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="./css/forgot-password.css">
    <script src="<?php echo htmlspecialchars((string)$offlineReadCacheScript, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars((string)$offlineOutboxScript, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="./js/theme-sync.js"></script>
    <script src="<?php echo htmlspecialchars((string)$pwaBootstrapScript, ENT_QUOTES, 'UTF-8'); ?>" data-base-path="<?php echo htmlspecialchars((string)$pwaBasePath, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
</head>
<body>
    <button id="themeToggle" class="theme-toggle-btn" aria-label="Toggle dark mode">
        <span class="theme-icon light-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
        </span>
        <span class="theme-icon dark-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </span>
    </button>
    <main class="container" aria-labelledby="portalTitle">
        <div class="card-accent" aria-hidden="true"></div>

        <div class="logo-container">
            <div class="logo"><img src="../assets/Logo.png" alt="DENR Logo"></div>
        </div>

        <div class="heading">
            <p class="gov-label">Republic of the Philippines</p>
            <h1 id="portalTitle">Set a New Password</h1>
            <p class="portal-label">First login detected for username <?= auth_e((string)$user['username']) ?>. Update your password to continue.</p>
        </div>

        <?php if ($formError !== ''): ?>
            <p class="field-error form-error" role="alert"><?= auth_e($formError) ?></p>
        <?php endif; ?>

        <form id="firstLoginResetForm" method="post" action="<?= auth_e($resetUrl) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= auth_e((string)$_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-wrapper">
                    <span class="input-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                    <input id="password" type="password" name="password" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" data-target="password" aria-label="Show password" aria-pressed="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <?php if ($fieldErrors['password'] !== ''): ?>
                    <p class="field-error" role="alert"><?= auth_e($fieldErrors['password']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="input-wrapper">
                    <span class="input-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                    <input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password" aria-pressed="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <?php if ($fieldErrors['confirm_password'] !== ''): ?>
                    <p class="field-error" role="alert"><?= auth_e($fieldErrors['confirm_password']) ?></p>
                <?php endif; ?>
            </div>

            <p class="otp-hint" role="note">Password rules: at least 10 characters with uppercase, lowercase, number, and symbol.</p>

            <button id="resetPasswordBtn" type="submit" class="btn-primary" data-default-text="Update Password" data-loading-text="Updating Password...">
                <span class="btn-text">Update Password</span>
            </button>
        </form>

        <p class="auth-switch">
            <a href="<?= auth_e($loginUrl) ?>">Back to Sign In</a>
        </p>
    </main>

    <script src="./js/first-login-reset.js"></script>
</body>
</html>
