<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

const AUTH_LOGIN_MAX_ATTEMPTS = 5;
const AUTH_LOGIN_LOCK_MINUTES = 15;

const AUTH_MFA_CODE_TTL_MINUTES = 5;
const AUTH_PASSWORD_RESET_CODE_TTL_MINUTES = 10;
const AUTH_MFA_MAX_ATTEMPTS = 5;
const AUTH_MFA_LOCK_MINUTES = 15;
const AUTH_MFA_RESEND_LIMIT = 3;
const AUTH_MFA_RESEND_WINDOW_MINUTES = 10;
const AUTH_MFA_RESEND_COOLDOWN_SECONDS = 60;

function auth_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function auth_normalize_username(string $username): string
{
    return strtolower(trim($username));
}

function auth_username_is_valid(string $username): bool
{
    $length = function_exists('mb_strlen') ? mb_strlen($username) : strlen($username);
    if ($length < 3 || $length > 50) {
        return false;
    }

    return (bool)preg_match('/^[a-z0-9._-]+$/', $username);
}

function auth_normalize_username_part(string $value): string
{
    $normalized = trim($value);
    if ($normalized === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        if (is_string($transliterated) && $transliterated !== '') {
            $normalized = $transliterated;
        }
    }

    $normalized = strtolower($normalized);
    $normalized = preg_replace('/[^a-z0-9]+/', '.', $normalized) ?? '';
    $normalized = preg_replace('/\.{2,}/', '.', $normalized) ?? $normalized;

    return trim($normalized, '._-');
}

function auth_build_username_seed(string $firstName, string $lastName): string
{
    $parts = array_values(array_filter([
        auth_normalize_username_part($firstName),
        auth_normalize_username_part($lastName),
    ], static fn(string $part): bool => $part !== ''));

    $seed = implode('.', $parts);
    if ($seed === '') {
        $seed = 'user';
    }

    $seed = substr($seed, 0, 50);
    if (strlen($seed) < 3) {
        $seed = substr($seed . 'user', 0, 3);
    }

    return trim($seed, '._-');
}

function auth_build_username_candidate(string $baseUsername, int $suffixIndex = 0): string
{
    $base = trim($baseUsername);
    if ($base === '') {
        $base = 'user';
    }

    if ($suffixIndex <= 0) {
        return substr($base, 0, 50);
    }

    $suffix = '.' . (string)($suffixIndex + 1);
    $maxBaseLength = max(1, 50 - strlen($suffix));
    $trimmedBase = rtrim(substr($base, 0, $maxBaseLength), '._-');
    if ($trimmedBase === '') {
        $trimmedBase = 'u';
    }

    return $trimmedBase . $suffix;
}

function auth_resolve_unique_username(PDO $pdo, string $firstName, string $lastName): string
{
    $baseUsername = auth_build_username_seed($firstName, $lastName);
    $lookupPrefix = strtolower($baseUsername) . '.%';

    $stmt = $pdo->prepare(
        'SELECT LOWER(username) AS username_key
           FROM users
          WHERE LOWER(username) = :base_username
             OR LOWER(username) LIKE :lookup_prefix'
    );
    $stmt->execute([
        ':base_username' => strtolower($baseUsername),
        ':lookup_prefix' => $lookupPrefix,
    ]);

    $existingUsernames = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $usernameKey) {
        $normalizedKey = strtolower(trim((string)$usernameKey));
        if ($normalizedKey !== '') {
            $existingUsernames[$normalizedKey] = true;
        }
    }

    for ($suffixIndex = 0; $suffixIndex < 100000; $suffixIndex += 1) {
        $candidate = auth_build_username_candidate($baseUsername, $suffixIndex);
        if (!isset($existingUsernames[strtolower($candidate)])) {
            return $candidate;
        }
    }

    throw new RuntimeException('Unable to generate a unique username right now.');
}

function auth_resolve_unique_username_from_reserved(array &$reservedUsernames, string $firstName, string $lastName): string
{
    $baseUsername = auth_build_username_seed($firstName, $lastName);

    for ($suffixIndex = 0; $suffixIndex < 100000; $suffixIndex += 1) {
        $candidate = auth_build_username_candidate($baseUsername, $suffixIndex);
        $candidateKey = strtolower($candidate);
        if (!isset($reservedUsernames[$candidateKey])) {
            $reservedUsernames[$candidateKey] = true;
            return $candidate;
        }
    }

    throw new RuntimeException('Unable to generate a unique username right now.');
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
                :user_id, :email, :event_type, :event_status, :ip_address, :user_agent, :remarks, SYSDATETIME()
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

function auth_mail_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value !== false && trim((string)$value) !== '') {
        return trim((string)$value);
    }

    $serverValue = $_SERVER[$key] ?? null;
    if (is_string($serverValue) && trim($serverValue) !== '') {
        return trim($serverValue);
    }

    $envValue = $_ENV[$key] ?? null;
    if (is_string($envValue) && trim($envValue) !== '') {
        return trim($envValue);
    }

    $webConfigValue = auth_web_config_app_setting($key);
    if ($webConfigValue !== null && trim($webConfigValue) !== '') {
        return trim($webConfigValue);
    }

    return $default;
}

function auth_web_config_app_setting(string $key): ?string
{
    static $cache = null;

    if (!is_array($cache)) {
        $cache = [];
        $configPath = dirname(__DIR__) . '/web.config';
        if (is_file($configPath) && is_readable($configPath)) {
            $xml = @simplexml_load_file($configPath);
            if ($xml !== false && isset($xml->appSettings->add)) {
                foreach ($xml->appSettings->add as $node) {
                    $attributes = $node->attributes();
                    $entryKey = isset($attributes['key']) ? trim((string)$attributes['key']) : '';
                    if ($entryKey === '') {
                        continue;
                    }
                    $cache[$entryKey] = isset($attributes['value']) ? (string)$attributes['value'] : '';
                }
            }
        }
    }

    if (!array_key_exists($key, $cache)) {
        return null;
    }

    return (string)$cache[$key];
}

function auth_mail_bool_env(string $key, bool $default = false): bool
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    $normalized = strtolower(trim((string)$value));
    if ($normalized === '') {
        return $default;
    }

    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

function auth_mail_timeout_env(string $key, int $default = 15): int
{
    $value = getenv($key);
    if ($value === false || trim((string)$value) === '') {
        return $default;
    }

    $numeric = (int)$value;
    return max(5, min(120, $numeric));
}

function auth_mail_smtp_config(): array
{
    $host = auth_mail_env('DTMIS_SMTP_HOST', auth_mail_env('SMTP_HOST', ''));
    $username = auth_mail_env('DTMIS_SMTP_USERNAME', auth_mail_env('SMTP_USERNAME', auth_mail_env('SMTP_USER', '')));
    $password = auth_mail_env('DTMIS_SMTP_PASSWORD', auth_mail_env('SMTP_PASSWORD', auth_mail_env('SMTP_PASS', '')));
    $fromEmail = auth_mail_env(
        'DTMIS_SMTP_FROM_EMAIL',
        auth_mail_env('MAIL_FROM_ADDRESS', auth_mail_env('MAIL_FROM_EMAIL', ''))
    );
    $fromName = auth_mail_env('DTMIS_SMTP_FROM_NAME', auth_mail_env('MAIL_FROM_NAME', 'DENR Region XII DTMIS'));
    $encryption = strtolower(auth_mail_env('DTMIS_SMTP_ENCRYPTION', auth_mail_env('SMTP_ENCRYPTION', 'tls')));
    if ($encryption === 'none') {
        $encryption = '';
    }

    if (!in_array($encryption, ['', 'tls', 'ssl', 'starttls'], true)) {
        $encryption = 'tls';
    }

    return [
        'host' => $host,
        'port' => max(1, min(65535, (int)auth_mail_env('DTMIS_SMTP_PORT', auth_mail_env('SMTP_PORT', '587')))),
        'username' => $username,
        'password' => $password,
        'from_email' => $fromEmail,
        'from_name' => $fromName !== '' ? $fromName : 'DENR Region XII DTMIS',
        'encryption' => $encryption,
        'auth' => auth_mail_bool_env('DTMIS_SMTP_AUTH', true),
        'timeout' => auth_mail_timeout_env('DTMIS_SMTP_TIMEOUT', 15),
        'debug' => auth_mail_bool_env('DTMIS_SMTP_DEBUG', false),
    ];
}

function auth_mail_smtp_is_configured(array $config): bool
{
    if (($config['host'] ?? '') === '' || ($config['from_email'] ?? '') === '') {
        return false;
    }

    if (!empty($config['auth'])) {
        return ($config['username'] ?? '') !== '' && ($config['password'] ?? '') !== '';
    }

    return true;
}

function auth_send_via_smtp(string $email, string $subject, string $message, array $config): bool
{
    if (!class_exists(PHPMailer::class)) {
        return false;
    }

    $mailer = new PHPMailer(true);
    $mailer->CharSet = PHPMailer::CHARSET_UTF8;
    $mailer->isSMTP();
    $mailer->Host = (string)$config['host'];
    $mailer->Port = (int)$config['port'];
    $mailer->Timeout = (int)$config['timeout'];
    $mailer->SMTPAuth = !empty($config['auth']);
    $mailer->SMTPAutoTLS = false;

    if ($mailer->SMTPAuth) {
        $mailer->Username = (string)$config['username'];
        $mailer->Password = (string)$config['password'];
    }

    $encryption = (string)($config['encryption'] ?? '');
    $mailer->SMTPSecure = '';
    if ($encryption === 'ssl') {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption === 'tls' || $encryption === 'starttls') {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->SMTPAutoTLS = true;
    }

    if (!empty($config['debug'])) {
        $mailer->SMTPDebug = 2;
    }

    $mailer->setFrom((string)$config['from_email'], (string)$config['from_name']);
    $mailer->addAddress($email);
    $mailer->Subject = $subject;
    $mailer->Body = $message;
    $mailer->isHTML(false);

    try {
        return $mailer->send();
    } catch (PHPMailerException $exception) {
        error_log('DTMIS SMTP send failed: ' . $exception->getMessage());
        return false;
    } catch (Throwable $exception) {
        error_log('DTMIS SMTP send failed: ' . $exception->getMessage());
        return false;
    }
}

function auth_send_email_otp(
    string $email,
    string $code,
    string $purpose = 'Login Verification',
    ?int $ttlMinutes = null
): bool
{
    $effectiveTtlMinutes = max(1, $ttlMinutes ?? AUTH_MFA_CODE_TTL_MINUTES);
    $subject = 'DENR DTMIS OTP - ' . $purpose;
    $message = "Your OTP code is: {$code}\n\nThis code expires in {$effectiveTtlMinutes} minutes.\nIf you did not request this, contact DENR ICT support immediately.";
    $smtpConfig = auth_mail_smtp_config();
    if (auth_mail_smtp_is_configured($smtpConfig)) {
        $sent = auth_send_via_smtp($email, $subject, $message, $smtpConfig);
        if ($sent) {
            return true;
        }
    }

    $fallbackFromEmail = (string)($smtpConfig['from_email'] ?? '');
    if ($fallbackFromEmail === '') {
        $fallbackFromEmail = auth_mail_env('MAIL_FROM_ADDRESS', 'no-reply@denr12.online');
    }
    $headers = "From: {$fallbackFromEmail}\r\n";

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
        $_SESSION['pending_mfa_username'],
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
    $_SESSION['username'] = (string)$user['username'];
    $_SESSION['email'] = (string)$user['email'];
    $_SESSION['first_name'] = (string)$user['first_name'];
    $_SESSION['last_name'] = (string)$user['last_name'];
    $_SESSION['role_id'] = (int)$user['role_id'];
    $_SESSION['role_name'] = (string)$user['role_name'];
    $_SESSION['office_id'] = (int)$user['office_id'];
    $_SESSION['authenticated_at'] = time();
    $_SESSION['last_sensitive_sync_reauth_at'] = time();
    unset($_SESSION['mfa_verified_at']);

    if (function_exists('app_touch_session_heartbeat')) {
        app_touch_session_heartbeat();
    }
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

function auth_fetch_user_for_login(PDO $pdo, string $username): array|false
{
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
         WHERE LOWER(u.username) = LOWER(:username)'
    );
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    return $user ?: false;
}
