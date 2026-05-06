<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Authentication required.';
    exit;
}

$roleKey = app_normalize_role_key((string)($_SESSION['role_name'] ?? ''));
if (!in_array($roleKey, ['ORED', 'CENRO_OFFICER', 'PENRO_OFFICER', 'PASU_OFFICER'], true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Digital signature workspace is available for ORED, CENRO Officer, PENRO Officer, and PASU only.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Method not allowed.';
    exit;
}

function digital_signature_files_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'signatures';
}

function digital_signature_user_image_glob(int $userId): array
{
    $pattern = digital_signature_files_dir() . DIRECTORY_SEPARATOR . 'user_' . $userId . '.*';
    $matches = glob($pattern) ?: [];
    return array_values(array_filter($matches, static function (string $path): bool {
        return is_file($path) && is_readable($path);
    }));
}

function digital_signature_resolve_image_absolute_path(int $userId): ?string
{
    $candidates = digital_signature_user_image_glob($userId);
    if (!empty($candidates)) {
        usort($candidates, static function (string $a, string $b): int {
            return filemtime($b) <=> filemtime($a);
        });
        return $candidates[0];
    }

    $fallbackNames = ['ored_default.png', 'rd_signature.png'];
    foreach ($fallbackNames as $fileName) {
        $candidate = digital_signature_files_dir() . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }
    }

    return null;
}

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        http_response_code(401);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Authentication required.';
        exit;
    }

    $absolutePath = digital_signature_resolve_image_absolute_path($userId);
    if ($absolutePath === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'No signature image found.';
        exit;
    }

    $mime = 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $detected = finfo_file($finfo, $absolutePath);
            if (is_string($detected) && trim($detected) !== '') {
                $mime = $detected;
            }
            finfo_close($finfo);
        }
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($absolutePath));
    header('Content-Disposition: inline; filename="' . str_replace('"', '', basename($absolutePath)) . '"');
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');

    readfile($absolutePath);
    exit;
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to load signature image.';
}
