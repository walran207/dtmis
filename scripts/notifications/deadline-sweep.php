<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/notification-mail.php';

$options = getopt('', ['force', 'min-interval::']);
$force = array_key_exists('force', $options);
$minimumIntervalSeconds = 900;

if (array_key_exists('min-interval', $options)) {
    $rawInterval = trim((string)$options['min-interval']);
    if ($rawInterval !== '' && ctype_digit($rawInterval)) {
        $minimumIntervalSeconds = (int)$rawInterval;
    }
}

try {
    $pdo = getDatabaseConnection();
    $result = notification_mail_run_deadline_sweep($pdo, $minimumIntervalSeconds, $force);

    $status = strtoupper((string)($result['reason'] ?? 'unknown'));
    $ran = !empty($result['ran']) ? 'yes' : 'no';
    $sentCount = (int)($result['sent_count'] ?? 0);

    echo '[deadline-sweep] status=' . $status
        . ' ran=' . $ran
        . ' sent_count=' . $sentCount
        . ' min_interval=' . $minimumIntervalSeconds
        . ' force=' . ($force ? 'yes' : 'no')
        . PHP_EOL;

    exit($status === 'FAILED' ? 1 : 0);
} catch (Throwable $exception) {
    fwrite(STDERR, '[deadline-sweep] error=' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
