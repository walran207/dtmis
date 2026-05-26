<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/workflow.php';

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

$roleKey = app_normalize_role_key((string)($_SESSION['role_name'] ?? ''));
$canUseDigitalSignatureWorkspace = in_array($roleKey, ['CENRO_OFFICER', 'PENRO_OFFICER', 'PASU_OFFICER'], true);
if (!$canUseDigitalSignatureWorkspace) {
    try {
        $pdo = getDatabaseConnection();
        $actorContext = workflow_get_user_context($pdo, (int)($_SESSION['user_id'] ?? 0));
        $canUseDigitalSignatureWorkspace = workflow_actor_is_ored_signer($pdo, $actorContext);
    } catch (Throwable $exception) {
        $canUseDigitalSignatureWorkspace = false;
    }
}
if (!$canUseDigitalSignatureWorkspace) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Digital signature workspace is available only for the ORED signing account, CENRO Officer, PENRO Officer, and PASU.']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

const DIGITAL_SIGNATURE_ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'bmp', 'gif'];
const DIGITAL_SIGNATURE_MAX_FILE_SIZE = 5242880; // 5 MB

function digital_signature_profiles_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'signatures' . DIRECTORY_SEPARATOR . 'profiles';
}

function digital_signature_files_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'signatures';
}

function digital_signature_ensure_dirs(): void
{
    $paths = [digital_signature_profiles_dir(), digital_signature_files_dir()];
    foreach ($paths as $path) {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException('Unable to create digital signature storage directory.');
        }
    }
}

function digital_signature_profile_path(int $userId): string
{
    return digital_signature_profiles_dir() . DIRECTORY_SEPARATOR . 'user_' . $userId . '.json';
}

function digital_signature_user_image_glob(int $userId): array
{
    $pattern = digital_signature_files_dir() . DIRECTORY_SEPARATOR . 'user_' . $userId . '.*';
    $matches = glob($pattern) ?: [];
    return array_values(array_filter($matches, static function (string $path): bool {
        return is_file($path);
    }));
}

function digital_signature_user_image_relative_path(int $userId): ?string
{
    $userCandidates = digital_signature_user_image_glob($userId);
    if (!empty($userCandidates)) {
        usort($userCandidates, static function (string $a, string $b): int {
            return filemtime($b) <=> filemtime($a);
        });
        $latest = str_replace('\\', '/', $userCandidates[0]);
        $base = str_replace('\\', '/', dirname(__DIR__));
        if (str_starts_with($latest, $base . '/')) {
            return ltrim(substr($latest, strlen($base)), '/');
        }
    }

    $fallbackNames = ['ored_default.png', 'rd_signature.png'];
    foreach ($fallbackNames as $fileName) {
        $candidate = digital_signature_files_dir() . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($candidate)) {
            return 'storage/signatures/' . $fileName;
        }
    }

    return null;
}

function digital_signature_user_image_url(int $userId): string
{
    $imagePath = digital_signature_user_image_relative_path($userId);
    if ($imagePath === null) {
        return '';
    }

    return app_url('actions/digital-signature-image.php') . '?t=' . rawurlencode((string)time());
}

function digital_signature_profile_defaults(): array
{
    return [
        'mode' => 'Grid-Snap',
        'paper_size' => 'A4',
        'x_pct' => 58.0,
        'y_pct' => 74.0,
        'block_width_pct' => 42.0,
        'block_height_pct' => 14.25,
    ];
}

function digital_signature_normalize_mode(string $mode): string
{
    $normalized = strtoupper(str_replace(['_', ' '], '-', trim($mode)));
    return $normalized === 'FREE-DRAG' ? 'Free-Drag' : 'Grid-Snap';
}

function digital_signature_normalize_paper_size(string $paperSize): string
{
    $candidate = strtoupper(trim($paperSize));
    if (in_array($candidate, ['A4', 'LETTER', 'CUSTOM_85X13'], true)) {
        return $candidate;
    }
    return 'A4';
}

function digital_signature_normalize_profile(array $raw): array
{
    $defaults = digital_signature_profile_defaults();

    $mode = digital_signature_normalize_mode((string)($raw['mode'] ?? $defaults['mode']));
    $paperSize = digital_signature_normalize_paper_size((string)($raw['paper_size'] ?? $defaults['paper_size']));
    $xPct = (float)($raw['x_pct'] ?? $defaults['x_pct']);
    $yPct = (float)($raw['y_pct'] ?? $defaults['y_pct']);
    $blockWidthPct = (float)($raw['block_width_pct'] ?? $defaults['block_width_pct']);
    $blockHeightPct = (float)($raw['block_height_pct'] ?? $defaults['block_height_pct']);

    return [
        'mode' => $mode,
        'paper_size' => $paperSize,
        'x_pct' => max(0.0, min(100.0, round($xPct, 2))),
        'y_pct' => max(0.0, min(100.0, round($yPct, 2))),
        'block_width_pct' => max(20.0, min(90.0, round($blockWidthPct, 2))),
        'block_height_pct' => max(12.0, min(80.0, round($blockHeightPct, 2))),
    ];
}

function digital_signature_load_profile(int $userId): array
{
    $profilePath = digital_signature_profile_path($userId);
    if (!is_file($profilePath) || !is_readable($profilePath)) {
        return digital_signature_profile_defaults();
    }

    $json = file_get_contents($profilePath);
    if ($json === false || trim($json) === '') {
        return digital_signature_profile_defaults();
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return digital_signature_profile_defaults();
    }

    return digital_signature_normalize_profile($decoded);
}

function digital_signature_save_profile(int $userId, array $profile): void
{
    digital_signature_ensure_dirs();
    $profilePath = digital_signature_profile_path($userId);
    $encoded = json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false || file_put_contents($profilePath, $encoded) === false) {
        throw new RuntimeException('Unable to save digital signature profile.');
    }
}

function digital_signature_resolve_signer_name(PDO $pdo, int $userId): string
{
    $stmt = $pdo->prepare(
        'SELECT TOP (1) first_name, last_name
         FROM users
         WHERE id = :id'
    );
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return 'Regional Director';
    }

    $fullName = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
    return $fullName !== '' ? $fullName : 'Regional Director';
}

function digital_signature_handle_upload(int $userId, ?array $upload): ?string
{
    if (!is_array($upload) || !isset($upload['error'])) {
        return null;
    }

    $error = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Unable to upload signature image.');
    }

    $tmpName = (string)($upload['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new InvalidArgumentException('Invalid signature upload payload.');
    }

    $size = (int)($upload['size'] ?? 0);
    if ($size <= 0 || $size > DIGITAL_SIGNATURE_MAX_FILE_SIZE) {
        throw new InvalidArgumentException('Signature image must be 5 MB or smaller.');
    }

    $originalName = trim((string)($upload['name'] ?? ''));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, DIGITAL_SIGNATURE_ALLOWED_EXTENSIONS, true)) {
        throw new InvalidArgumentException('Signature image must be PNG/JPG/JPEG/BMP/GIF.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = strtolower((string)$finfo->file($tmpName));
    $allowedMime = ['image/png', 'image/jpeg', 'image/bmp', 'image/gif'];
    if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
        throw new InvalidArgumentException('Unsupported signature image format.');
    }

    digital_signature_ensure_dirs();
    foreach (digital_signature_user_image_glob($userId) as $oldFile) {
        @unlink($oldFile);
    }

    $targetName = 'user_' . $userId . '.' . $extension;
    $targetAbsolutePath = digital_signature_files_dir() . DIRECTORY_SEPARATOR . $targetName;
    if (!move_uploaded_file($tmpName, $targetAbsolutePath)) {
        throw new RuntimeException('Unable to store signature image.');
    }

    return 'storage/signatures/' . $targetName;
}

function digital_signature_remove_user_image(int $userId): void
{
    foreach (digital_signature_user_image_glob($userId) as $filePath) {
        @unlink($filePath);
    }
}

try {
    $pdo = getDatabaseConnection();
    $signerName = digital_signature_resolve_signer_name($pdo, $userId);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $profile = digital_signature_load_profile($userId);
        $imageUrl = digital_signature_user_image_url($userId);

        echo json_encode([
            'ok' => true,
            'profile' => $profile,
            'signer_name' => $signerName,
            'signature_image_url' => $imageUrl,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token.']);
        exit;
    }

    $profile = digital_signature_normalize_profile([
        'mode' => (string)($_POST['mode'] ?? ''),
        'paper_size' => (string)($_POST['paper_size'] ?? ''),
        'x_pct' => (float)($_POST['x_pct'] ?? 58),
        'y_pct' => (float)($_POST['y_pct'] ?? 74),
        'block_width_pct' => (float)($_POST['block_width_pct'] ?? 42),
        'block_height_pct' => (float)($_POST['block_height_pct'] ?? 14.25),
    ]);
    digital_signature_save_profile($userId, $profile);

    $uploadPath = digital_signature_handle_upload($userId, $_FILES['signature_image'] ?? null);
    $removeImage = ((string)($_POST['remove_signature_image'] ?? '') === '1');
    if ($removeImage) {
        digital_signature_remove_user_image($userId);
        $uploadPath = null;
    }

    $imageUrl = digital_signature_user_image_url($userId);

    $message = 'Digital signature profile saved.';
    if ($uploadPath !== null) {
        $message .= ' Signature image updated.';
    } elseif ($removeImage) {
        $message .= ' Signature image removed.';
    }

    echo json_encode([
        'ok' => true,
        'message' => $message,
        'profile' => $profile,
        'signer_name' => $signerName,
        'signature_image_url' => $imageUrl,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to process digital signature profile right now.']);
}
