<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/security.php';

session_start();

// Already-authenticated users should not revisit the login form.
if (!empty($_SESSION['user_id'])) {
    app_redirect_to_role_dashboard((string)($_SESSION['role_name'] ?? ''));
}

// One CSRF token per session; reused by auth forms.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Absolute asset URLs keep auth pages working under rewritten base paths.
$pwaManifestPath = app_url('manifest.webmanifest');
$offlineReadCacheScript = app_url('assets/js/offline-read-cache.js');
$offlineOutboxScript = app_url('assets/js/offline-outbox.js');
$pwaBootstrapScript = app_url('assets/js/pwa-init.js');
$pwaBasePath = rtrim((string)app_url(''), '/');
if ($pwaBasePath === '') {
    $pwaBasePath = '/';
}
// MFA is currently disabled, so clear any stale pending MFA session state.
auth_clear_pending_mfa_session();

$flashSuccess = (string)($_SESSION['flash_success'] ?? '');
unset($_SESSION['flash_success']);

$email = '';
$fieldErrors = [
    'email' => '',
    'password' => '',
];
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Normalize inputs early so every validation path uses consistent values.
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    // Timing-safe CSRF comparison.
    if (!hash_equals((string)$_SESSION['csrf_token'], $csrfToken)) {
        $formError = 'Your session expired. Please try again.';
    }

    if ($email === '') {
        $fieldErrors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fieldErrors['email'] = 'Enter a valid email address.';
    } elseif (!(str_ends_with($email, '@gmail.com') || str_ends_with($email, '@denr.gov.ph'))) {
        $fieldErrors['email'] = 'Use a Gmail or DENR email address.';
    }

    if ($password === '') {
        $fieldErrors['password'] = 'Password is required.';
    }

    $hasFieldErrors = $fieldErrors['email'] !== '' || $fieldErrors['password'] !== '';

    if ($formError === '' && !$hasFieldErrors) {
        try {
            $pdo = getDatabaseConnection();
            $user = auth_fetch_user_for_login($pdo, $email);
        } catch (Throwable $exception) {
            // Keep error generic to avoid exposing DB internals.
            $user = false;
            $formError = 'Unable to sign in right now. Please contact your administrator.';
        }

        // Block sign-in immediately when the lockout window is still active.
        if ($formError === '' && $user && (int)$user['is_active'] === 1) {
            $lockedRemaining = auth_format_lock_remaining((string)($user['locked_until'] ?? ''));
            if ($lockedRemaining !== '') {
                $formError = 'Account temporarily locked due to repeated failed logins. Try again in ' . $lockedRemaining . '.';
                auth_log_security_event($pdo, [
                    'user_id' => (int)$user['id'],
                    'email' => $email,
                    'event_type' => 'LOGIN_BLOCKED_LOCKOUT',
                    'event_status' => 'BLOCKED',
                    'remarks' => 'Login blocked due to active lockout.',
                ]);
            }
        }

        // Use a single generic failure message, while still auditing precise reasons internally.
        if ($formError === '' && (!$user || (int)$user['is_active'] !== 1 || !password_verify($password, (string)$user['password_hash']))) {
            if ($user && (int)$user['is_active'] === 1) {
                $attempts = ((int)$user['failed_login_attempts']) + 1;
                $lockUntil = null;
                // Reset attempts once lockout is applied, so next cycle starts cleanly.
                if ($attempts >= AUTH_LOGIN_MAX_ATTEMPTS) {
                    $lockUntil = (new DateTimeImmutable('now'))
                        ->add(new DateInterval('PT' . AUTH_LOGIN_LOCK_MINUTES . 'M'))
                        ->format('Y-m-d H:i:s');
                    $attempts = 0;
                }

                $update = $pdo->prepare(
                    'UPDATE users
                     SET failed_login_attempts = :attempts,
                         locked_until = :locked_until
                     WHERE id = :id'
                );
                $update->execute([
                    'attempts' => $attempts,
                    'locked_until' => $lockUntil,
                    'id' => (int)$user['id'],
                ]);

                auth_log_security_event($pdo, [
                    'user_id' => (int)$user['id'],
                    'email' => $email,
                    'event_type' => 'LOGIN_PASSWORD_FAILED',
                    'event_status' => 'FAILED',
                    'remarks' => $lockUntil ? 'Lockout triggered due to repeated failed password attempts.' : 'Invalid password.',
                ]);
            } else {
                auth_log_security_event($pdo, [
                    'user_id' => null,
                    'email' => $email,
                    'event_type' => 'LOGIN_USER_NOT_FOUND',
                    'event_status' => 'FAILED',
                    'remarks' => 'Login email not found or inactive account.',
                ]);
            }

            $formError = 'Invalid email or password.';
        }

        if ($formError === '' && $user) {
            // Successful login: clear lockout counters and stamp access metadata.
            $update = $pdo->prepare(
                'UPDATE users
                 SET failed_login_attempts = 0,
                     locked_until = NULL,
                     last_login_at = NOW(),
                     last_login_ip = :last_login_ip
                 WHERE id = :id'
            );
            $update->execute([
                'last_login_ip' => auth_client_ip(),
                'id' => (int)$user['id'],
            ]);

            $_SESSION['pending_remember_me'] = !empty($_POST['rememberMe']);

            auth_log_security_event($pdo, [
                'user_id' => (int)$user['id'],
                'email' => (string)$user['email'],
                'event_type' => 'LOGIN_SUCCESS',
                'event_status' => 'SUCCESS',
                'remarks' => 'Password verified. MFA login step is currently disabled.',
            ]);

            // First-login forced reset is temporarily disabled.
            unset($_SESSION['force_reset_user_id'], $_SESSION['force_reset_email']);

            auth_set_authenticated_session([
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role_id' => $user['role_id'],
                'role_name' => $user['role_name'],
                'office_id' => $user['office_id'],
            ]);
            auth_apply_remember_me_cookie(!empty($_SESSION['pending_remember_me']));
            unset($_SESSION['pending_remember_me']);
            unset($_SESSION['force_reset_user_id'], $_SESSION['force_reset_email']);
            // Defensive clear in case a previous session left MFA flags behind.
            auth_clear_pending_mfa_session();
            $welcomeFirstName = trim((string)($user['first_name'] ?? ''));
            $_SESSION['show_welcome_loader'] = 1;
            $_SESSION['welcome_loader_message'] = $welcomeFirstName !== ''
                ? 'Welcome back, ' . $welcomeFirstName . '!'
                : 'Welcome back!';

            app_redirect_to_role_dashboard((string)$user['role_name']);
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
    <title>Login | DENR Region XII eDATS</title>
    <link rel="manifest" href="<?php echo htmlspecialchars((string)$pwaManifestPath, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars((string)app_url('assets/Logo.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="./css/login.css">
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
            <h1 id="portalTitle">DENR Region XII eDATS Portal</h1>
            <p class="portal-label">Official government system for authorized personnel.</p>
        </div>

        <?php if ($formError !== ''): ?>
            <p class="field-error form-error" role="alert"><?= auth_e($formError) ?></p>
        <?php endif; ?>
        <?php if ($flashSuccess !== ''): ?>
            <p class="form-success" role="status"><?= auth_e($flashSuccess) ?></p>
        <?php endif; ?>

        <form id="loginForm" method="post" action="./login.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= auth_e((string)$_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <span class="input-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                            <polyline points="3 7 12 13 21 7"></polyline>
                        </svg>
                    </span>
                    <input id="email" type="email" name="email" placeholder="example@gmail.com or name@denr.gov.ph" autocomplete="username" value="<?= auth_e($email) ?>" required>
                </div>
                <?php if ($fieldErrors['email'] !== ''): ?>
                    <p class="field-error" role="alert"><?= auth_e($fieldErrors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                    <input id="password" type="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Show password">
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

            <div class="form-options">
                <label class="remember-wrap">
                    <input id="rememberMe" type="checkbox" name="rememberMe" <?= !empty($_POST['rememberMe']) ? 'checked' : '' ?>>
                    <span>Remember me</span>
                </label>
                <a href="./forgot-password.php">Forgot password?</a>
            </div>

            <button id="signInBtn" type="submit" class="btn-primary">
                <span class="btn-text">Sign In</span>
            </button>
        </form>

        <p class="auth-switch">
            Need an account?
            <a href="./register.php">Register here</a>
        </p>

        <p class="security-note">
            This is an official DENR information system. Unauthorized access, use, or modification is prohibited and may result in administrative or legal action.
        </p>

        <div class="support-links">
            <a href="#privacy">Privacy Notice</a>
            <a href="#terms">Terms</a>
            <a href="#helpdesk">Help Desk</a>
            <a href="#report">Report phishing</a>
        </div>
    </main>

    <script src="./js/login.js"></script>
</body>
</html>
