<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/security.php';

session_start();

if (!empty($_SESSION['user_id'])) {
    app_redirect_to_role_dashboard((string)($_SESSION['role_name'] ?? ''));
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pwaManifestPath = app_url('manifest.webmanifest');
$offlineReadCacheScript = app_url('assets/js/offline-read-cache.js');
$offlineOutboxScript = app_url('assets/js/offline-outbox.js');
$pwaBootstrapScript = app_url('assets/js/pwa-init.js');
$forgotPasswordUrl = app_url('auth/forgot-password.php');
$loginUrl = app_url('auth/login.php');
$pwaBasePath = rtrim((string)app_url(''), '/');
if ($pwaBasePath === '') {
    $pwaBasePath = '/';
}

$username = '';
$otpCode = '';
$fieldErrors = [
    'username' => '',
    'otp_code' => '',
    'password' => '',
    'confirm_password' => '',
];
$formError = '';
$infoMessage = '';
$devOtp = '';
$showResetForm = false;
$inlineOtpTestingMode = false;

function forgot_password_identifier_is_valid(string $value): bool
{
    if ($value === '') {
        return false;
    }

    if (str_contains($value, '@')) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    return auth_username_is_valid($value);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'request_code');
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    $username = auth_normalize_username((string)($_POST['username'] ?? ''));

    if (!hash_equals((string)$_SESSION['csrf_token'], $csrfToken)) {
        $formError = 'Your session expired. Please try again.';
    }

    if ($username === '') {
        $fieldErrors['username'] = 'Username or email is required.';
    } elseif (!forgot_password_identifier_is_valid($username)) {
        $fieldErrors['username'] = 'Enter a valid username or email address.';
    }

    if ($formError === '' && $action === 'request_code' && $fieldErrors['username'] === '') {
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = getDatabaseConnection();

            $stmt = $pdo->prepare(
                'SELECT TOP (1) id, email
                 FROM users
                 WHERE (LOWER(username) = LOWER(:lookup) OR LOWER(email) = LOWER(:lookup))
                   AND is_active = 1'
            );
            $stmt->execute(['lookup' => $username]);
            $user = $stmt->fetch();

            if ($user) {
                $generatedOtp = auth_generate_otp();
                $otpExpiresAt = (new DateTimeImmutable('+'. AUTH_PASSWORD_RESET_CODE_TTL_MINUTES . ' minutes'))->format('Y-m-d H:i:s');

                $updateStmt = $pdo->prepare(
                    'UPDATE users
                     SET otp_code = :otp_code, otp_expires_at = :otp_expires_at
                     WHERE id = :id'
                );
                $updateStmt->execute([
                    'otp_code' => $generatedOtp,
                    'otp_expires_at' => $otpExpiresAt,
                    'id' => (int)$user['id'],
                ]);

                $sent = $inlineOtpTestingMode
                    ? true
                    : auth_send_email_otp((string)$user['email'], $generatedOtp, 'Password Reset', AUTH_PASSWORD_RESET_CODE_TTL_MINUTES);
                auth_log_security_event($pdo, [
                    'user_id' => (int)$user['id'],
                    'email' => (string)$user['email'],
                    'event_type' => 'PASSWORD_RESET_OTP_REQUEST',
                    'event_status' => $sent ? 'SUCCESS' : 'FAILED',
                    'remarks' => $inlineOtpTestingMode
                        ? 'Password reset OTP generated in inline testing mode.'
                        : ($sent ? 'Password reset OTP sent.' : 'Password reset OTP generated but email failed.'),
                ]);

                if ($inlineOtpTestingMode || auth_is_local_environment()) {
                    $devOtp = $generatedOtp;
                }
            }

            $showResetForm = true;
            $infoMessage = 'If the account exists, a 6-digit reset code has been generated for verification.';
        } catch (Throwable $exception) {
            $formError = 'Unable to process password reset right now. Please contact your administrator.';
        }
    } elseif ($formError === '' && $action === 'reset_password') {
        $showResetForm = true;
        $otpCode = trim((string)($_POST['otp_code'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($otpCode === '') {
            $fieldErrors['otp_code'] = 'Verification code is required.';
        } elseif (!preg_match('/^\d{6}$/', $otpCode)) {
            $fieldErrors['otp_code'] = 'Enter a valid 6-digit code.';
        }

        if ($password === '') {
            $fieldErrors['password'] = 'New password is required.';
        } elseif (strlen($password) < 8) {
            $fieldErrors['password'] = 'Password must be at least 8 characters.';
        }

        if ($confirmPassword === '') {
            $fieldErrors['confirm_password'] = 'Confirm your new password.';
        } elseif ($password !== '' && $confirmPassword !== $password) {
            $fieldErrors['confirm_password'] = 'Passwords do not match.';
        }

        $hasFieldErrors = false;
        foreach ($fieldErrors as $error) {
            if ($error !== '') {
                $hasFieldErrors = true;
                break;
            }
        }

        if (!$hasFieldErrors) {
            try {
                require_once __DIR__ . '/../config/database.php';
                $pdo = getDatabaseConnection();

                $stmt = $pdo->prepare(
                    'SELECT TOP (1) id, email, otp_expires_at
                     FROM users
                     WHERE (LOWER(username) = LOWER(:lookup) OR LOWER(email) = LOWER(:lookup))
                       AND otp_code = :otp_code
                       AND is_active = 1'
                );
                $stmt->execute([
                    'lookup' => $username,
                    'otp_code' => $otpCode,
                ]);
                $user = $stmt->fetch();

                $isExpired = true;
                if ($user && !empty($user['otp_expires_at'])) {
                    $expiresTimestamp = strtotime((string)$user['otp_expires_at']);
                    $isExpired = $expiresTimestamp === false || $expiresTimestamp < time();
                }

                if (!$user || $isExpired) {
                    $fieldErrors['otp_code'] = 'Invalid or expired verification code.';
                    auth_log_security_event($pdo, [
                        'user_id' => $user ? (int)$user['id'] : null,
                        'email' => $user ? (string)$user['email'] : null,
                        'event_type' => 'PASSWORD_RESET_OTP_VERIFY',
                        'event_status' => 'FAILED',
                        'remarks' => 'Invalid or expired password reset OTP.',
                    ]);
                } else {
                    $updateStmt = $pdo->prepare(
                        'UPDATE users
                         SET password_hash = :password_hash,
                             must_change_password = 0,
                             password_changed_at = SYSDATETIME(),
                             otp_code = NULL,
                             otp_expires_at = NULL
                         WHERE id = :id'
                    );
                    $updateStmt->execute([
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => (int)$user['id'],
                    ]);

                    auth_log_security_event($pdo, [
                        'user_id' => (int)$user['id'],
                        'email' => (string)$user['email'],
                        'event_type' => 'PASSWORD_RESET_SUCCESS',
                        'event_status' => 'SUCCESS',
                        'remarks' => 'Password reset completed.',
                    ]);

                    $_SESSION['flash_success'] = 'Password reset successful. Please sign in with your new password.';
                    app_redirect('auth/login.php');
                }
            } catch (Throwable $exception) {
                $formError = 'Unable to reset password right now. Please contact your administrator.';
            }
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
    <title>Forgot Password | DENR Region XII DTMIS</title>
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
            <h1 id="portalTitle">Forgot Password</h1>
            <p class="portal-label">Verify your account and set a new password.</p>
        </div>

        <?php if ($formError !== ''): ?>
            <p class="field-error form-error" role="alert"><?= e($formError) ?></p>
        <?php endif; ?>
        <?php if ($infoMessage !== ''): ?>
            <p class="form-success" role="status"><?= e($infoMessage) ?></p>
        <?php endif; ?>
        <?php if ($devOtp !== ''): ?>
            <p class="otp-hint" role="status">Local reset code: <strong><?= e($devOtp) ?></strong></p>
        <?php endif; ?>

        <?php if (!$showResetForm): ?>
            <form id="requestResetForm" method="post" action="<?= e($forgotPasswordUrl) ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e((string)$_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="request_code">

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21a8 8 0 1 0-16 0"></path>
                                <circle cx="12" cy="8" r="4"></circle>
                            </svg>
                        </span>
                        <input id="username" type="text" name="username" placeholder="Enter your username or email" value="<?= e($username) ?>" autocomplete="username" required>
                    </div>
                    <?php if ($fieldErrors['username'] !== ''): ?>
                        <p class="field-error" role="alert"><?= e($fieldErrors['username']) ?></p>
                    <?php endif; ?>
                </div>

                <button id="sendCodeBtn" type="submit" class="btn-primary" data-default-text="Send Reset Code" data-loading-text="Sending Code...">
                    <span class="btn-text">Send Reset Code</span>
                </button>
            </form>
        <?php else: ?>
            <form id="resetForm" method="post" action="<?= e($forgotPasswordUrl) ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e((string)$_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="reset_password">

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21a8 8 0 1 0-16 0"></path>
                                <circle cx="12" cy="8" r="4"></circle>
                            </svg>
                        </span>
                        <input id="username" type="text" name="username" placeholder="Enter your username or email" value="<?= e($username) ?>" autocomplete="username" required>
                    </div>
                    <?php if ($fieldErrors['username'] !== ''): ?>
                        <p class="field-error" role="alert"><?= e($fieldErrors['username']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="otp_code">Verification Code</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2v4"></path>
                                <path d="M12 18v4"></path>
                                <path d="M4.93 4.93l2.83 2.83"></path>
                                <path d="M16.24 16.24l2.83 2.83"></path>
                                <path d="M2 12h4"></path>
                                <path d="M18 12h4"></path>
                                <path d="M4.93 19.07l2.83-2.83"></path>
                                <path d="M16.24 7.76l2.83-2.83"></path>
                            </svg>
                        </span>
                        <input id="otp_code" type="text" name="otp_code" maxlength="6" inputmode="numeric" placeholder="6-digit code" value="<?= e($otpCode) ?>" required>
                    </div>
                    <?php if ($fieldErrors['otp_code'] !== ''): ?>
                        <p class="field-error" role="alert"><?= e($fieldErrors['otp_code']) ?></p>
                    <?php endif; ?>
                </div>

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
                        <p class="field-error" role="alert"><?= e($fieldErrors['password']) ?></p>
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
                        <p class="field-error" role="alert"><?= e($fieldErrors['confirm_password']) ?></p>
                    <?php endif; ?>
                </div>

                <button id="resetPasswordBtn" type="submit" class="btn-primary" data-default-text="Reset Password" data-loading-text="Resetting Password...">
                    <span class="btn-text">Reset Password</span>
                </button>
            </form>

            <p class="helper-note">
                Need a new verification code?
                <a href="<?= e($forgotPasswordUrl) ?>">Start again</a>
            </p>
        <?php endif; ?>

        <p class="auth-switch">
            Remember your password?
            <a href="<?= e($loginUrl) ?>">Sign in</a>
        </p>
    </main>

    <script src="./js/forgot-password.js"></script>
</body>
</html>
