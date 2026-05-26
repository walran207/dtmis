<?php
declare(strict_types=1);

require_once __DIR__ . '/test-account-seed-common.php';

$pdo = test_account_connect();
$specs = test_account_build_specs($pdo);

if ($specs === []) {
    echo 'No test-account specs were generated, so there is nothing to delete.' . PHP_EOL;
    exit(0);
}

$emails = array_values(array_map(
    static fn(array $spec): string => (string)($spec['email'] ?? ''),
    $specs
));

$placeholders = [];
$params = [];
foreach ($emails as $index => $email) {
    $key = 'email_' . $index;
    $placeholders[] = ':' . $key;
    $params[$key] = $email;
}

$selectSql = 'SELECT id, email
              FROM users
              WHERE is_seeded_demo = 1
                AND email IN (' . implode(', ', $placeholders) . ')
              ORDER BY id ASC';
$deleteSql = 'DELETE FROM users
              WHERE is_seeded_demo = 1
                AND email IN (' . implode(', ', $placeholders) . ')';

$selectStmt = $pdo->prepare($selectSql);
$deleteStmt = $pdo->prepare($deleteSql);

$selectStmt->execute($params);
$rows = $selectStmt->fetchAll() ?: [];

if ($rows === []) {
    echo 'No matching seeded test accounts were found.' . PHP_EOL;
} else {
    $pdo->beginTransaction();
    try {
        $deleteStmt->execute($params);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    echo 'Deleted ' . count($rows) . ' seeded test accounts.' . PHP_EOL;
}

$exportPath = test_account_seed_export_path();
if (is_file($exportPath)) {
    @unlink($exportPath);
}
