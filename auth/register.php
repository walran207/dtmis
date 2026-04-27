<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

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
$pwaBasePath = rtrim((string)app_url(''), '/');
if ($pwaBasePath === '') {
    $pwaBasePath = '/';
}

$pdo = null;
$offices = [];
$roles = [];

$firstName = '';
$lastName = '';
$email = '';
$officeId = '';
$roleId = '';

$fieldErrors = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'office_id' => '',
    'role_id' => '',
    'password' => '',
    'confirm_password' => '',
];
$formError = '';

try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();

    $officeStmt = $pdo->query('SELECT id, name FROM offices ORDER BY name ASC');
    $offices = $officeStmt->fetchAll();

    $roleStmt = $pdo->query('SELECT id, name FROM roles ORDER BY name ASC');
    $roles = $roleStmt->fetchAll();
} catch (Throwable $exception) {
    $formError = 'Unable to load registration details right now. Please contact your administrator.';
}

$validOfficeIds = [];
$officeNameById = [];
foreach ($offices as $office) {
    $officeIdKey = (string)$office['id'];
    $validOfficeIds[$officeIdKey] = true;
    $officeNameById[$officeIdKey] = (string)($office['name'] ?? '');
}

$validRoleIds = [];
$roleNameById = [];
foreach ($roles as $role) {
    $roleIdKey = (string)$role['id'];
    $validRoleIds[$roleIdKey] = true;
    $roleNameById[$roleIdKey] = (string)($role['name'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName = trim((string)($_POST['last_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $officeId = (string)($_POST['office_id'] ?? '');
    $roleId = (string)($_POST['role_id'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals((string)$_SESSION['csrf_token'], $csrfToken)) {
        $formError = 'Your session expired. Please try again.';
    }

    if ($firstName === '') {
        $fieldErrors['first_name'] = 'First name is required.';
    } elseif (mb_strlen($firstName) > 50) {
        $fieldErrors['first_name'] = 'First name must not exceed 50 characters.';
    }

    if ($lastName === '') {
        $fieldErrors['last_name'] = 'Last name is required.';
    } elseif (mb_strlen($lastName) > 50) {
        $fieldErrors['last_name'] = 'Last name must not exceed 50 characters.';
    }

    if ($email === '') {
        $fieldErrors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fieldErrors['email'] = 'Enter a valid email address.';
    } elseif (!(str_ends_with($email, '@gmail.com') || str_ends_with($email, '@denr.gov.ph'))) {
        $fieldErrors['email'] = 'Use a Gmail or DENR email address.';
    }

    if ($officeId === '' || !isset($validOfficeIds[$officeId])) {
        $fieldErrors['office_id'] = 'Select your office.';
    }

    if ($roleId === '' || !isset($validRoleIds[$roleId])) {
        $fieldErrors['role_id'] = 'Select your role.';
    }

    if ($fieldErrors['office_id'] === '' && $fieldErrors['role_id'] === '') {
        $selectedRoleName = strtoupper(trim((string)($roleNameById[$roleId] ?? '')));
        $selectedOfficeName = strtoupper(trim((string)($officeNameById[$officeId] ?? '')));

        if ($selectedRoleName === 'ARD_TS') {
            $isTsOffice = str_contains($selectedOfficeName, 'ARD TS')
                || (str_contains($selectedOfficeName, 'ASSISTANT REGIONAL DIRECTOR') && str_contains($selectedOfficeName, 'TECHNICAL'));
            if (!$isTsOffice) {
                $fieldErrors['office_id'] = 'ARD TS accounts must use the ARD TS office.';
            }
        } elseif ($selectedRoleName === 'ARD_MS') {
            $isMsOffice = str_contains($selectedOfficeName, 'ARD MS')
                || (str_contains($selectedOfficeName, 'ASSISTANT REGIONAL DIRECTOR') && str_contains($selectedOfficeName, 'MANAGEMENT'));
            if (!$isMsOffice) {
                $fieldErrors['office_id'] = 'ARD MS accounts must use the ARD MS office.';
            }
        }
    }

    if ($password === '') {
        $fieldErrors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $fieldErrors['password'] = 'Password must be at least 8 characters.';
    }

    if ($confirmPassword === '') {
        $fieldErrors['confirm_password'] = 'Confirm your password.';
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

    if ($formError === '' && !$hasFieldErrors) {
        try {
            if (!$pdo instanceof PDO) {
                require_once __DIR__ . '/../config/database.php';
                $pdo = getDatabaseConnection();
            }

            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $checkStmt->execute(['email' => $email]);
            $existingUser = $checkStmt->fetch();

            if ($existingUser) {
                $fieldErrors['email'] = 'That email is already registered.';
            } else {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO users (office_id, role_id, first_name, last_name, email, password_hash, is_active)
                     VALUES (:office_id, :role_id, :first_name, :last_name, :email, :password_hash, 1)'
                );
                $insertStmt->execute([
                    'office_id' => (int)$officeId,
                    'role_id' => (int)$roleId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $_SESSION['flash_success'] = 'Registration successful. Please sign in.';
                app_redirect('auth/login.php');
            }
        } catch (Throwable $exception) {
            $formError = 'Unable to register right now. Please contact your administrator.';
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
    <title>Register | DENR Region XII eDATS</title>
    <link rel="manifest" href="<?php echo htmlspecialchars((string)$pwaManifestPath, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars((string)app_url('assets/Logo.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="./css/register.css">
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
            <h1 id="portalTitle">Create eDATS Account</h1>
            <p class="portal-label">Register your approved email credentials to access the system.</p>
        </div>

        <?php if ($formError !== ''): ?>
            <p class="field-error form-error" role="alert"><?= e($formError) ?></p>
        <?php endif; ?>

        <form id="registerForm" method="post" action="./register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e((string)$_SESSION['csrf_token']) ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <input id="first_name" type="text" name="first_name" value="<?= e($firstName) ?>" maxlength="50" autocomplete="given-name" required>
                    </div>
                    <?php if ($fieldErrors['first_name'] !== ''): ?>
                        <p class="field-error" role="alert"><?= e($fieldErrors['first_name']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <input id="last_name" type="text" name="last_name" value="<?= e($lastName) ?>" maxlength="50" autocomplete="family-name" required>
                    </div>
                    <?php if ($fieldErrors['last_name'] !== ''): ?>
                        <p class="field-error" role="alert"><?= e($fieldErrors['last_name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <span class="input-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                            <polyline points="3 7 12 13 21 7"></polyline>
                        </svg>
                    </span>
                    <input id="email" type="email" name="email" placeholder="example@gmail.com or name@denr.gov.ph" value="<?= e($email) ?>" autocomplete="email" required>
                </div>
                <?php if ($fieldErrors['email'] !== ''): ?>
                    <p class="field-error" role="alert"><?= e($fieldErrors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="office_id">Office</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 21h18"></path>
                                <path d="M5 21V7l8-4 6 4v14"></path>
                                <path d="M9 9h1"></path>
                                <path d="M9 13h1"></path>
                                <path d="M14 9h1"></path>
                                <path d="M14 13h1"></path>
                            </svg>
                        </span>
                        <select id="office_id" name="office_id" required>
                            <option value="">Select office</option>
                            <?php foreach ($offices as $office): ?>
                                <?php $selected = $officeId === (string)$office['id'] ? 'selected' : ''; ?>
                                <option value="<?= e((string)$office['id']) ?>" <?= $selected ?>><?= e((string)$office['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($fieldErrors['office_id'] !== ''): ?>
                        <p class="field-error" role="alert"><?= e($fieldErrors['office_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="role_id">Role</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <path d="M20 8v6"></path>
                                <path d="M23 11h-6"></path>
                            </svg>
                        </span>
                        <select id="role_id" name="role_id" required>
                            <option value="">Select role</option>
                            <?php foreach ($roles as $role): ?>
                                <?php $selected = $roleId === (string)$role['id'] ? 'selected' : ''; ?>
                                <option value="<?= e((string)$role['id']) ?>" <?= $selected ?>><?= e((string)$role['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($fieldErrors['role_id'] !== ''): ?>
                        <p class="field-error" role="alert"><?= e($fieldErrors['role_id']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="password">Password</label>
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
                    <label for="confirm_password">Confirm Password</label>
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
            </div>

            <button id="registerBtn" type="submit" class="btn-primary">
                <span class="btn-text">Create Account</span>
            </button>
        </form>

        <p class="auth-switch">
            Already have an account?
            <a href="./login.php">Sign in</a>
        </p>

        <p class="security-note">
            This is an official DENR information system. Unauthorized access, use, or modification is prohibited and may result in administrative or legal action.
        </p>
    </main>

    <script src="./js/register.js"></script>
</body>
</html>
