<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

const AUTH_LOGIN_MAX_ATTEMPTS = 5;
const AUTH_LOGIN_LOCK_MINUTES = 15;

const AUTH_MFA_CODE_TTL_MINUTES = 5;
const AUTH_MFA_MAX_ATTEMPTS = 5;
const AUTH_MFA_LOCK_MINUTES = 15;
const AUTH_MFA_RESEND_LIMIT = 3;
const AUTH_MFA_RESEND_WINDOW_MINUTES = 10;
const AUTH_MFA_RESEND_COOLDOWN_SECONDS = 60;

function auth_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function auth_client_ip(): string
{
    $raw = trim((string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    if ($raw !== '') {
        $first = trim(explode(',', $raw)[0]);
        if ($first !== '') {
            return substr($first, 0, 45);
        }
    }

    $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
    return $remote !== '' ? substr($remote, 0, 45) : '0.0.0.0';
}

function auth_user_agent(): string
{
    return substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown')), 0, 255);
}

function auth_is_local_environment(): bool
{
    $serverName = strtolower(trim((string)($_SERVER['SERVER_NAME'] ?? '')));
    $remoteAddr = strtolower(trim((string)($_SERVER['REMOTE_ADDR'] ?? '')));

    return in_array($serverName, ['localhost', '127.0.0.1'], true)
        || in_array($remoteAddr, ['127.0.0.1', '::1'], true);
}

function auth_log_security_event(PDO $pdo, array $payload): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO security_audit_logs (
                user_id, email, event_type, event_status, ip_address, user_agent, remarks, created_at
             ) VALUES (
                :user_id, :email, :event_type, :event_status, :ip_address, :user_agent, :remarks, NOW()
             )'
        );
        $stmt->execute([
            'user_id' => $payload['user_id'] ?? null,
            'email' => $payload['email'] ?? null,
            'event_type' => (string)($payload['event_type'] ?? 'UNKNOWN_EVENT'),
            'event_status' => (string)($payload['event_status'] ?? 'INFO'),
            'ip_address' => auth_client_ip(),
            'user_agent' => auth_user_agent(),
            'remarks' => $payload['remarks'] ?? null,
        ]);
    } catch (Throwable $exception) {
        // Do not break auth flow if audit logging fails.
    }
}

function auth_generate_otp(): string
{
    return (string)random_int(100000, 999999);
}

function auth_send_email_otp(string $email, string $code, string $purpose = 'Login Verification'): bool
{
    $subject = 'DENR eDATS OTP - ' . $purpose;
    $message = "Your OTP code is: {$code}\n\nThis code expires in " . AUTH_MFA_CODE_TTL_MINUTES . " minutes.\nIf you did not request this, contact DENR ICT support immediately.";
    $headers = "From: no-reply@denr.gov.ph\r\n";

    $sent = @mail($email, $subject, $message, $headers);

    if (!$sent && auth_is_local_environment()) {
        $logPath = __DIR__ . '/../database/dev-otp.log';
        $line = sprintf(
            "[%s] %s | %s | OTP:%s\n",
            date('Y-m-d H:i:s'),
            $purpose,
            $email,
            $code
        );
        @file_put_contents($logPath, $line, FILE_APPEND);
    }

    return $sent || auth_is_local_environment();
}

function auth_clear_pending_mfa_session(): void
{
    unset(
        $_SESSION['pending_mfa_user_id'],
        $_SESSION['pending_mfa_email'],
        $_SESSION['pending_mfa_first_name'],
        $_SESSION['pending_mfa_last_name'],
        $_SESSION['pending_mfa_role_id'],
        $_SESSION['pending_mfa_role_name'],
        $_SESSION['pending_mfa_office_id'],
        $_SESSION['pending_mfa_verified_at']
    );
}

function auth_set_authenticated_session(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['email'] = (string)$user['email'];
    $_SESSION['first_name'] = (string)$user['first_name'];
    $_SESSION['last_name'] = (string)$user['last_name'];
    $_SESSION['role_id'] = (int)$user['role_id'];
    $_SESSION['role_name'] = (string)$user['role_name'];
    $_SESSION['office_id'] = (int)$user['office_id'];
    $_SESSION['authenticated_at'] = time();
    $_SESSION['last_sensitive_sync_reauth_at'] = time();
    unset($_SESSION['mfa_verified_at']);
}

function auth_apply_remember_me_cookie(bool $remember): void
{
    if (!$remember) {
        return;
    }

    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie(session_name(), session_id(), [
        'expires' => time() + (60 * 60 * 24 * 30),
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function auth_password_meets_policy(string $password): bool
{
    if (strlen($password) < 10) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    if (!preg_match('/\d/', $password)) {
        return false;
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }

    return true;
}

function auth_format_lock_remaining(?string $lockedUntil): string
{
    if ($lockedUntil === null || trim($lockedUntil) === '') {
        return '';
    }
    $until = strtotime($lockedUntil);
    if ($until === false) {
        return '';
    }
    $seconds = $until - time();
    if ($seconds <= 0) {
        return '';
    }
    $minutes = (int)ceil($seconds / 60);
    return $minutes . ' minute' . ($minutes === 1 ? '' : 's');
}

function auth_fetch_user_for_login(PDO $pdo, string $email): array|false
{
    $stmt = $pdo->prepare(
        'SELECT
            u.id,
            u.email,
            u.password_hash,
            u.first_name,
            u.last_name,
            u.role_id,
            u.office_id,
            u.is_active,
            u.must_change_password,
            u.failed_login_attempts,
            u.locked_until,
            u.mfa_otp_code,
            u.mfa_otp_expires_at,
            u.mfa_failed_attempts,
            u.mfa_locked_until,
            u.mfa_resend_count,
            u.mfa_resend_window_started_at,
            u.mfa_last_sent_at,
            r.name AS role_name
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    return $user ?: false;
}
