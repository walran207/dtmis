<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/auth/security.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

if (!isset($roleBasePath) || !is_string($roleBasePath) || !is_dir($roleBasePath)) {
    throw new RuntimeException('Role base path is required for profile settings.');
}

$rawCurrentRequestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
$currentRequestPath = (string)(parse_url($rawCurrentRequestUri, PHP_URL_PATH) ?? '');
$currentRequestQuery = (string)(parse_url($rawCurrentRequestUri, PHP_URL_QUERY) ?? '');
$normalizedCurrentRequestPath = trim(str_replace('\\', '/', $currentRequestPath), '/');
$normalizedBasePath = trim(str_replace('\\', '/', app_base_path()), '/');

if ($normalizedBasePath !== '') {
    if (strcasecmp($normalizedCurrentRequestPath, $normalizedBasePath) === 0) {
        $normalizedCurrentRequestPath = '';
    } else {
        $basePathPrefix = $normalizedBasePath . '/';
        if (strncasecmp($normalizedCurrentRequestPath, $basePathPrefix, strlen($basePathPrefix)) === 0) {
            $normalizedCurrentRequestPath = substr($normalizedCurrentRequestPath, strlen($basePathPrefix));
        }
    }
}

$currentRequestRoute = $normalizedCurrentRequestPath !== '' ? $normalizedCurrentRequestPath : 'profile-settings.php';
if ($currentRequestQuery !== '') {
    $currentRequestRoute .= '?' . $currentRequestQuery;
}
$currentRequestUrl = app_url($currentRequestRoute);
$profileSettingsFlash = $_SESSION['profile_settings_flash'] ?? null;
unset($_SESSION['profile_settings_flash']);

$profileSettingsNotice = [
    'type' => '',
    'message' => '',
    'target' => '',
];
if (is_array($profileSettingsFlash)) {
    $profileSettingsNotice['type'] = (string)($profileSettingsFlash['type'] ?? '');
    $profileSettingsNotice['message'] = (string)($profileSettingsFlash['message'] ?? '');
    $profileSettingsNotice['target'] = (string)($profileSettingsFlash['target'] ?? '');
}

$profileSettingsEmailErrors = [
    'email' => '',
    'current_password' => '',
];
$profileSettingsPasswordErrors = [
    'current_password' => '',
    'new_password' => '',
    'confirm_password' => '',
];
$profileSettingsFormState = [
    'email' => '',
];

$profileSettingsUser = [
    'id' => (int)($_SESSION['user_id'] ?? 0),
    'first_name' => (string)($_SESSION['first_name'] ?? ''),
    'last_name' => (string)($_SESSION['last_name'] ?? ''),
    'username' => (string)($_SESSION['username'] ?? ''),
    'email' => (string)($_SESSION['email'] ?? ''),
    'role_name' => (string)($_SESSION['role_name'] ?? ''),
];

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare(
        'SELECT TOP (1)
            u.id,
            u.first_name,
            u.last_name,
            u.username,
            u.email,
            u.password_hash,
            u.is_active,
            r.name AS role_name
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id'
    );
    $stmt->execute(['id' => (int)$profileSettingsUser['id']]);
    $dbUser = $stmt->fetch();
    if (!$dbUser || (int)($dbUser['is_active'] ?? 0) !== 1) {
        $_SESSION = [];
        session_destroy();
        app_redirect('auth/login.php');
    }

    $profileSettingsUser = [
        'id' => (int)($dbUser['id'] ?? 0),
        'first_name' => (string)($dbUser['first_name'] ?? ''),
        'last_name' => (string)($dbUser['last_name'] ?? ''),
        'username' => (string)($dbUser['username'] ?? ''),
        'email' => (string)($dbUser['email'] ?? ''),
        'role_name' => (string)($dbUser['role_name'] ?? ($_SESSION['role_name'] ?? '')),
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        $action = trim((string)($_POST['profile_action'] ?? ''));

        if ($csrfToken === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
            $_SESSION['profile_settings_flash'] = [
                'type' => 'error',
                'message' => 'Your session expired. Please try again.',
                'target' => $action === 'update_password' ? 'password' : 'email',
            ];
            app_redirect($currentRequestRoute);
        }

        if ($action === 'update_email') {
            $newEmail = strtolower(trim((string)($_POST['email'] ?? '')));
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $profileSettingsFormState['email'] = $newEmail;

            if ($newEmail === '') {
                $profileSettingsEmailErrors['email'] = 'Email is required.';
            } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $profileSettingsEmailErrors['email'] = 'Enter a valid email address.';
            } elseif (!(str_ends_with($newEmail, '@gmail.com') || str_ends_with($newEmail, '@denr.gov.ph'))) {
                $profileSettingsEmailErrors['email'] = 'Use a Gmail or DENR email address.';
            } elseif ($newEmail === strtolower((string)$profileSettingsUser['email'])) {
                $profileSettingsEmailErrors['email'] = 'Use a different email address from your current one.';
            }

            if ($currentPassword === '') {
                $profileSettingsEmailErrors['current_password'] = 'Current password is required to update your email.';
            } elseif (!password_verify($currentPassword, (string)($dbUser['password_hash'] ?? ''))) {
                $profileSettingsEmailErrors['current_password'] = 'Current password is incorrect.';
            }

            if ($profileSettingsEmailErrors['email'] === '' && $profileSettingsEmailErrors['current_password'] === '') {
                $update = $pdo->prepare('UPDATE users SET email = :email WHERE id = :id');
                $update->execute([
                    'email' => $newEmail,
                    'id' => (int)$profileSettingsUser['id'],
                ]);

                $_SESSION['email'] = $newEmail;
                if (function_exists('app_touch_session_heartbeat')) {
                    app_touch_session_heartbeat(['email' => $newEmail]);
                }

                auth_log_security_event($pdo, [
                    'user_id' => (int)$profileSettingsUser['id'],
                    'email' => $newEmail,
                    'event_type' => 'PROFILE_EMAIL_UPDATED',
                    'event_status' => 'SUCCESS',
                    'remarks' => 'Profile email updated from the dedicated profile settings page.',
                ]);

                $_SESSION['profile_settings_flash'] = [
                    'type' => 'success',
                    'message' => 'Your email address was updated successfully.',
                    'target' => 'email',
                ];
                app_redirect($currentRequestRoute);
            }

            $profileSettingsNotice = [
                'type' => 'error',
                'message' => 'Fix the email form errors and try again.',
                'target' => 'email',
            ];
        } elseif ($action === 'update_password') {
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            if ($currentPassword === '') {
                $profileSettingsPasswordErrors['current_password'] = 'Current password is required.';
            } elseif (!password_verify($currentPassword, (string)($dbUser['password_hash'] ?? ''))) {
                $profileSettingsPasswordErrors['current_password'] = 'Current password is incorrect.';
            }

            if ($newPassword === '') {
                $profileSettingsPasswordErrors['new_password'] = 'New password is required.';
            } elseif (!auth_password_meets_policy($newPassword)) {
                $profileSettingsPasswordErrors['new_password'] = 'Use at least 10 characters with uppercase, lowercase, number, and symbol.';
            } elseif (password_verify($newPassword, (string)($dbUser['password_hash'] ?? ''))) {
                $profileSettingsPasswordErrors['new_password'] = 'New password must be different from your current password.';
            }

            if ($confirmPassword === '') {
                $profileSettingsPasswordErrors['confirm_password'] = 'Confirm your new password.';
            } elseif ($newPassword !== '' && $confirmPassword !== $newPassword) {
                $profileSettingsPasswordErrors['confirm_password'] = 'Passwords do not match.';
            }

            if (
                $profileSettingsPasswordErrors['current_password'] === ''
                && $profileSettingsPasswordErrors['new_password'] === ''
                && $profileSettingsPasswordErrors['confirm_password'] === ''
            ) {
                $update = $pdo->prepare(
                    'UPDATE users
                     SET password_hash = :password_hash,
                         password_changed_at = SYSDATETIME(),
                         must_change_password = 0,
                         failed_login_attempts = 0,
                         locked_until = NULL
                     WHERE id = :id'
                );
                $update->execute([
                    'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'id' => (int)$profileSettingsUser['id'],
                ]);

                auth_log_security_event($pdo, [
                    'user_id' => (int)$profileSettingsUser['id'],
                    'email' => (string)$profileSettingsUser['email'],
                    'event_type' => 'PROFILE_PASSWORD_UPDATED',
                    'event_status' => 'SUCCESS',
                    'remarks' => 'Profile password updated from the dedicated profile settings page.',
                ]);

                $_SESSION['profile_settings_flash'] = [
                    'type' => 'success',
                    'message' => 'Your password was updated successfully.',
                    'target' => 'password',
                ];
                app_redirect($currentRequestRoute);
            }

            $profileSettingsNotice = [
                'type' => 'error',
                'message' => 'Fix the password form errors and try again.',
                'target' => 'password',
            ];
        } else {
            $profileSettingsNotice = [
                'type' => 'error',
                'message' => 'Unsupported profile action.',
                'target' => 'general',
            ];
        }
    }
} catch (Throwable $exception) {
    $profileSettingsNotice = [
        'type' => 'error',
        'message' => 'Unable to load profile settings right now. Please contact your administrator.',
        'target' => 'general',
    ];
}

$fullNameValue = trim($profileSettingsUser['first_name'] . ' ' . $profileSettingsUser['last_name']);
$displayNameValue = $fullNameValue !== '' ? $fullNameValue : 'Your Account';
$roleLabelValue = ucwords(strtolower(str_replace(['_', '-'], ' ', (string)$profileSettingsUser['role_name'])));

$pageTitle = 'Profile Settings | DENR Region XII DTMIS';
$pageHeading = 'Profile Settings';
$pageSubtitle = 'Update your email address and password for this account.';
$activeMenu = 'profile_settings';
$showQueueTable = false;
$renderStandardContent = false;
$customSectionInclude = dirname(__DIR__) . '/modules/profile-settings-panel.php';
$disableLiveDashboardRefresh = true;
$hideHeaderSearch = true;
$showQrReceiveScanner = false;
$hideHeaderDateFilter = true;
$profileSettingsFormAction = $currentRequestUrl;
$profileSettingsView = [
    'display_name' => $displayNameValue,
    'username' => (string)$profileSettingsUser['username'],
    'email' => (string)$profileSettingsUser['email'],
    'role_name' => (string)$profileSettingsUser['role_name'],
    'role_label' => $roleLabelValue !== '' ? $roleLabelValue : (string)$profileSettingsUser['role_name'],
];

require dirname(__DIR__) . '/templates/role-page-template.php';
