<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/admin.php';
require_once dirname(__DIR__, 2) . '/config/user-recycle-bin.php';
require_once dirname(__DIR__, 2) . '/auth/security.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Authentication required.']);
    exit;
}

if (!admin_has_access()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Admin access required.']);
    exit;
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

try {
    $pdo = getDatabaseConnection();
    $scope = admin_resolve_scope($pdo);

    if ($method === 'GET') {
        echo json_encode([
            'ok' => true,
            'overview' => admin_fetch_scope_overview($pdo, $scope),
            'users' => admin_fetch_scope_users($pdo, $scope, 700),
            'roles' => admin_fetch_manageable_roles($pdo),
            'offices' => admin_fetch_scope_offices($scope),
            'scope' => [
                'scope_type' => (string)($scope['scope_type'] ?? 'DIRECT'),
                'summary_label' => (string)($scope['summary_label'] ?? ''),
                'include_pamo' => !empty($scope['include_pamo']),
                'root_office_name' => (string)(($scope['root_office']['name'] ?? '') ?: ''),
                'penro_office_name' => (string)(($scope['penro_office']['name'] ?? '') ?: ''),
            ],
            'server_time' => date('c'),
        ]);
        exit;
    }

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if ($csrfToken === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token. Refresh and try again.']);
        exit;
    }

    $action = strtoupper(trim((string)($_POST['action'] ?? '')));

    if ($action === 'CREATE_USER') {
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $officeId = (int)($_POST['office_id'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
        $stringLength = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $roleId <= 0 || $officeId <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'First name, last name, email, password, role, and office are required.']);
            exit;
        }

        if ($stringLength($firstName) > 50 || $stringLength($lastName) > 50) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'First name and last name must be 50 characters or fewer.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $stringLength($email) > 100) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Enter a valid email address with 100 characters or fewer.']);
            exit;
        }

        if ($stringLength($password) < 8 || $stringLength($password) > 128) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Password must be between 8 and 128 characters.']);
            exit;
        }

        if (!admin_scope_allows_office($scope, $officeId)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Selected office is outside your allowed management scope.']);
            exit;
        }

        $roleStmt = $pdo->prepare('SELECT TOP (1) id, name FROM roles WHERE id = :id');
        $roleStmt->execute([':id' => $roleId]);
        $role = $roleStmt->fetch();
        if (!$role || !admin_is_manageable_role_key((string)($role['name'] ?? ''))) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Selected role is not assignable from this page.']);
            exit;
        }
        $username = auth_resolve_unique_username($pdo, $firstName, $lastName);

        $stmt = $pdo->prepare(
            'INSERT INTO users (
                office_id,
                role_id,
                first_name,
                last_name,
                username,
                email,
                password_hash,
                must_change_password,
                is_seeded_demo,
                password_changed_at,
                failed_login_attempts,
                locked_until,
                last_login_at,
                last_login_ip,
                otp_code,
                otp_expires_at,
                mfa_otp_code,
                mfa_otp_expires_at,
                mfa_failed_attempts,
                mfa_locked_until,
                mfa_resend_count,
                mfa_resend_window_started_at,
                mfa_last_sent_at,
                is_active
             ) VALUES (
                :office_id,
                :role_id,
                :first_name,
                :last_name,
                :username,
                :email,
                :password_hash,
                1,
                0,
                NULL,
                0,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                0,
                NULL,
                0,
                NULL,
                NULL,
                :is_active
             )'
        );
        $stmt->execute([
            ':office_id' => $officeId,
            ':role_id' => $roleId,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':is_active' => $isActive,
        ]);

        echo json_encode([
            'ok' => true,
            'message' => sprintf('Local user account created successfully. Assigned username: @%s.', $username),
            'username' => $username,
            'overview' => admin_fetch_scope_overview($pdo, $scope),
            'users' => admin_fetch_scope_users($pdo, $scope, 700),
        ]);
        exit;
    }

    $targetUserId = (int)($_POST['user_id'] ?? 0);
    if ($targetUserId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'A valid user is required.']);
        exit;
    }

    $targetUser = admin_fetch_manageable_user_by_id($pdo, $scope, $targetUserId);
    if ($targetUser === null) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Selected user is outside your allowed management scope.']);
        exit;
    }

    if ($action === 'RESET_SECURITY') {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET failed_login_attempts = 0,
                 locked_until = NULL,
                 mfa_failed_attempts = 0,
                 mfa_locked_until = NULL,
                 mfa_resend_count = 0,
                 mfa_resend_window_started_at = NULL,
                 mfa_last_sent_at = NULL,
                 mfa_otp_code = NULL,
                 mfa_otp_expires_at = NULL
             WHERE id = :id'
        );
        $stmt->execute([':id' => $targetUserId]);

        echo json_encode([
            'ok' => true,
            'message' => 'User security lock counters were reset.',
            'overview' => admin_fetch_scope_overview($pdo, $scope),
            'users' => admin_fetch_scope_users($pdo, $scope, 700),
        ]);
        exit;
    }

    if ($action === 'DELETE_USER') {
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        if ($targetUserId === $currentUserId) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'You cannot delete your own account while signed in.']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            user_recycle_bin_archive_user($pdo, $targetUserId, $currentUserId);
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $targetUserId]);
            if ($stmt->rowCount() < 1) {
                throw new RuntimeException('Unable to move user account to recycle bin.');
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $rawMessage = trim((string)$exception->getMessage());
            $message = $rawMessage !== ''
                ? $rawMessage
                : 'This account cannot be deleted right now because it is linked to existing records.';
            if ($message === 'The active result for the query contains no fields.' || str_contains(strtolower($message), 'foreign key')) {
                $message = 'This account cannot be deleted right now because it is linked to existing records.';
            }
            http_response_code(422);
            echo json_encode([
                'ok' => false,
                'message' => $message,
            ]);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'message' => 'User account moved to recycle bin.',
            'overview' => admin_fetch_scope_overview($pdo, $scope),
            'users' => admin_fetch_scope_users($pdo, $scope, 700),
        ]);
        exit;
    }

    if ($action !== 'UPDATE_USER') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Unsupported action.']);
        exit;
    }

    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName = trim((string)($_POST['last_name'] ?? ''));
    $username = auth_normalize_username((string)($_POST['username'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $roleId = (int)($_POST['role_id'] ?? 0);
    $officeId = (int)($_POST['office_id'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
    $resetSecurity = (int)($_POST['reset_security'] ?? 0) === 1;
    $stringLength = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);

    if ($firstName === '' || $lastName === '' || $username === '' || $email === '' || $roleId <= 0 || $officeId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'First name, last name, username, email, role, and office are required.']);
        exit;
    }

    if ($stringLength($firstName) > 50 || $stringLength($lastName) > 50) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'First name and last name must be 50 characters or fewer.']);
        exit;
    }

    if (!auth_username_is_valid($username)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Username must use 3 to 50 lowercase letters, numbers, dots, underscores, or hyphens.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $stringLength($email) > 100) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Enter a valid email address with 100 characters or fewer.']);
        exit;
    }

    if (!admin_scope_allows_office($scope, $officeId)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Selected office is outside your allowed management scope.']);
        exit;
    }

    $roleStmt = $pdo->prepare('SELECT TOP (1) id, name FROM roles WHERE id = :id');
    $roleStmt->execute([':id' => $roleId]);
    $role = $roleStmt->fetch();
    if (!$role || !admin_is_manageable_role_key((string)($role['name'] ?? ''))) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Selected role is not assignable from this page.']);
        exit;
    }

    $usernameStmt = $pdo->prepare('SELECT TOP (1) id FROM users WHERE LOWER(username) = LOWER(:username) AND id <> :id');
    $usernameStmt->execute([
        ':username' => $username,
        ':id' => $targetUserId,
    ]);
    if ((int)$usernameStmt->fetchColumn() > 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'That username is already used by another DTMIS account.']);
        exit;
    }

    $sql = 'UPDATE users
            SET first_name = :first_name,
                last_name = :last_name,
                username = :username,
                email = :email,
                role_id = :role_id,
                office_id = :office_id,
                is_active = :is_active';

    if ($resetSecurity) {
        $sql .= ',
                failed_login_attempts = 0,
                locked_until = NULL,
                mfa_failed_attempts = 0,
                mfa_locked_until = NULL,
                mfa_resend_count = 0,
                mfa_resend_window_started_at = NULL,
                mfa_last_sent_at = NULL,
                mfa_otp_code = NULL,
                mfa_otp_expires_at = NULL';
    }

    $sql .= ' WHERE id = :id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':username' => $username,
        ':email' => $email,
        ':role_id' => $roleId,
        ':office_id' => $officeId,
        ':is_active' => $isActive,
        ':id' => $targetUserId,
    ]);

    echo json_encode([
        'ok' => true,
        'message' => 'User profile updated successfully.',
        'overview' => admin_fetch_scope_overview($pdo, $scope),
        'users' => admin_fetch_scope_users($pdo, $scope, 700),
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to process admin user request right now.',
    ]);
}
