<?php
declare(strict_types=1);

require_once __DIR__ . '/test-account-seed-common.php';

$pdo = test_account_connect();
$roleIds = test_account_fetch_role_ids($pdo);
$specs = test_account_build_specs($pdo);

if ($specs === []) {
    throw new RuntimeException('No test-account specs were generated.');
}

$requiredRoles = array_values(array_unique(array_map(
    static fn(array $spec): string => (string)($spec['role_key'] ?? ''),
    $specs
)));

$missingRoles = [];
foreach ($requiredRoles as $roleKey) {
    if (!isset($roleIds[$roleKey]) || (int)$roleIds[$roleKey] <= 0) {
        $missingRoles[] = $roleKey;
    }
}

if ($missingRoles !== []) {
    throw new RuntimeException(
        'Cannot seed test accounts because these roles are missing: ' . implode(', ', $missingRoles)
    );
}

$passwordHash = password_hash(test_account_seed_password(), PASSWORD_DEFAULT);
if (!is_string($passwordHash) || $passwordHash === '') {
    throw new RuntimeException('Unable to hash the shared test password.');
}

$selectStmt = $pdo->prepare(
    'SELECT TOP (1) id, is_seeded_demo
     FROM users
     WHERE email = :email'
);

$insertStmt = $pdo->prepare(
    'INSERT INTO users (
        office_id,
        role_id,
        first_name,
        last_name,
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
        :email,
        :password_hash,
        0,
        1,
        SYSDATETIME(),
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
        1
    )'
);

$updateStmt = $pdo->prepare(
    'UPDATE users
     SET office_id = :office_id,
         role_id = :role_id,
         first_name = :first_name,
         last_name = :last_name,
         password_hash = :password_hash,
         must_change_password = 0,
         is_seeded_demo = 1,
         password_changed_at = SYSDATETIME(),
         failed_login_attempts = 0,
         locked_until = NULL,
         last_login_ip = NULL,
         otp_code = NULL,
         otp_expires_at = NULL,
         mfa_otp_code = NULL,
         mfa_otp_expires_at = NULL,
         mfa_failed_attempts = 0,
         mfa_locked_until = NULL,
         mfa_resend_count = 0,
         mfa_resend_window_started_at = NULL,
         mfa_last_sent_at = NULL,
         is_active = 1
     WHERE id = :user_id'
);

$resultRows = [];
$created = 0;
$updated = 0;

$pdo->beginTransaction();

try {
    foreach ($specs as $spec) {
        $email = (string)$spec['email'];
        $selectStmt->execute(['email' => $email]);
        $existing = $selectStmt->fetch();

        $payload = [
            'office_id' => (int)$spec['office_id'],
            'role_id' => (int)$roleIds[(string)$spec['role_key']],
            'first_name' => (string)$spec['first_name'],
            'last_name' => (string)$spec['last_name'],
            'email' => $email,
            'password_hash' => $passwordHash,
        ];

        if ($existing) {
            $userId = (int)($existing['id'] ?? 0);
            $isSeededDemo = (int)($existing['is_seeded_demo'] ?? 0) === 1;
            if ($userId <= 0) {
                throw new RuntimeException('Encountered an invalid existing user row for ' . $email . '.');
            }
            if (!$isSeededDemo) {
                throw new RuntimeException(
                    'Refusing to overwrite a non-seeded account with email ' . $email . '.'
                );
            }

            $payload['user_id'] = $userId;
            $updateStmt->execute($payload);
            $updated++;
            $spec['status'] = 'updated';
        } else {
            $insertStmt->execute($payload);
            $created++;
            $spec['status'] = 'created';
        }

        $resultRows[] = $spec;
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

$exportPath = test_account_write_export($resultRows);

echo 'Seeded test accounts successfully.' . PHP_EOL;
echo 'Created: ' . $created . PHP_EOL;
echo 'Updated: ' . $updated . PHP_EOL;
echo 'Total: ' . count($resultRows) . PHP_EOL;
echo 'Shared password: ' . test_account_seed_password() . PHP_EOL;
echo 'Export: ' . $exportPath . PHP_EOL;
