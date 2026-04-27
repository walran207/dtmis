START TRANSACTION;

-- Seed default ARD TS / ARD MS user accounts (idempotent).
-- Temporary passwords (share only with authorized admin):
-- ARD TS: ArdTs!2026Temp
-- ARD MS: ArdMs!2026Temp

SET @ard_ts_role_id = (
    SELECT id
    FROM roles
    WHERE UPPER(name) = 'ARD_TS'
    LIMIT 1
);

SET @ard_ms_role_id = (
    SELECT id
    FROM roles
    WHERE UPPER(name) = 'ARD_MS'
    LIMIT 1
);

SET @ard_ts_office_id = (
    SELECT id
    FROM offices
    WHERE UPPER(name) LIKE '%ARD TS%'
       OR (UPPER(name) LIKE '%ASSISTANT REGIONAL DIRECTOR%' AND UPPER(name) LIKE '%TECHNICAL%')
    ORDER BY id ASC
    LIMIT 1
);

SET @ard_ms_office_id = (
    SELECT id
    FROM offices
    WHERE UPPER(name) LIKE '%ARD MS%'
       OR (UPPER(name) LIKE '%ASSISTANT REGIONAL DIRECTOR%' AND UPPER(name) LIKE '%MANAGEMENT%')
    ORDER BY id ASC
    LIMIT 1
);

INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    must_change_password,
    is_seeded_demo,
    is_active
)
SELECT
    @ard_ts_office_id,
    @ard_ts_role_id,
    'Assistant',
    'Regional Director TS',
    'ard.ts.regional@denr.gov.ph',
    '$2y$10$mBDdfPJsqJK2u0X2cubm9u8FRkdz2S9WVgFUlJAnAWXlscb3iYp0O',
    1,
    1,
    1
WHERE @ard_ts_office_id IS NOT NULL
  AND @ard_ts_role_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE LOWER(u.email) = 'ard.ts.regional@denr.gov.ph'
  );

INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    must_change_password,
    is_seeded_demo,
    is_active
)
SELECT
    @ard_ms_office_id,
    @ard_ms_role_id,
    'Assistant',
    'Regional Director MS',
    'ard.ms.regional@denr.gov.ph',
    '$2y$10$Ybn28HKPj98DT1xF1KboSe7Oo4ZSSOaSWtpxs.EMB0E9gboJ0iv.G',
    1,
    1,
    1
WHERE @ard_ms_office_id IS NOT NULL
  AND @ard_ms_role_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE LOWER(u.email) = 'ard.ms.regional@denr.gov.ph'
  );

COMMIT;
