<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/super-admin.php';

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

if (!super_admin_has_access()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Super Admin access required.']);
    exit;
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

try {
    $pdo = getDatabaseConnection();

    if ($method === 'GET') {
        $users = super_admin_fetch_users($pdo, 600);
        $roles = super_admin_fetch_roles($pdo);
        $offices = super_admin_fetch_offices($pdo, 600);

        echo json_encode([
            'ok' => true,
            'users' => $users,
            'roles' => $roles,
            'offices' => $offices,
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
    $targetUserId = (int)($_POST['user_id'] ?? 0);

    if ($targetUserId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'A valid user is required.']);
        exit;
    }

    if ($action === 'RESET_SECURITY') {
        $targetUserStmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
        $targetUserStmt->execute(['id' => $targetUserId]);
        if (!$targetUserStmt->fetch()) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'User not found.']);
            exit;
        }

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
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $targetUserId]);

        echo json_encode([
            'ok' => true,
            'message' => 'User security lock counters were reset.',
            'users' => super_admin_fetch_users($pdo, 600),
        ]);
        exit;
    }

    if ($action !== 'UPDATE_USER') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Unsupported action.']);
        exit;
    }

    $roleId = (int)($_POST['role_id'] ?? 0);
    $officeId = (int)($_POST['office_id'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
    $resetSecurity = (int)($_POST['reset_security'] ?? 0) === 1;

    if ($roleId <= 0 || $officeId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Role and office are required.']);
        exit;
    }

    $roleCheckStmt = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE id = :id');
    $roleCheckStmt->execute(['id' => $roleId]);
    if ((int)$roleCheckStmt->fetchColumn() <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Selected role does not exist.']);
        exit;
    }

    $officeCheckStmt = $pdo->prepare('SELECT COUNT(*) FROM offices WHERE id = :id');
    $officeCheckStmt->execute(['id' => $officeId]);
    if ((int)$officeCheckStmt->fetchColumn() <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Selected office does not exist.']);
        exit;
    }

    $targetUserStmt = $pdo->prepare('SELECT id, role_id FROM users WHERE id = :id LIMIT 1');
    $targetUserStmt->execute(['id' => $targetUserId]);
    $targetUser = $targetUserStmt->fetch();
    if (!$targetUser) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'User not found.']);
        exit;
    }

    $currentUserId = (int)($_SESSION['user_id'] ?? 0);
    $currentRoleId = (int)($_SESSION['role_id'] ?? 0);
    if ($targetUserId === $currentUserId) {
        if ($isActive !== 1) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'You cannot deactivate your own Super Admin account.']);
            exit;
        }
        if ($roleId !== $currentRoleId) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'You cannot change your own role from this page.']);
            exit;
        }
    }

    $sql = 'UPDATE users
            SET role_id = :role_id,
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

    $sql .= ' WHERE id = :id LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'role_id' => $roleId,
        'office_id' => $officeId,
        'is_active' => $isActive,
        'id' => $targetUserId,
    ]);

    echo json_encode([
        'ok' => true,
        'message' => 'User profile updated successfully.',
        'users' => super_admin_fetch_users($pdo, 600),
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to process Super Admin user request right now.',
    ]);
}
