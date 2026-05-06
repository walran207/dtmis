START TRANSACTION;

-- Seed default PAMO internal users (idempotent).
-- Temporary password (share only with authorized admin): PamoInternal!2026
-- All seeded accounts require password change at first login.

INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    must_change_password,
    is_seeded_demo,
    otp_code,
    otp_expires_at,
    is_active
)
SELECT
    o.id AS office_id,
    r.id AS role_id,
    LEFT(COALESCE(NULLIF(TRIM(p.name), ''), 'PAMO'), 50) AS first_name,
    'Admin' AS last_name,
    CONCAT('pamo.admin.office', o.id, '@denr.gov.ph') AS email,
    '$2y$10$Xd.w/CivotEto3.KReUKHus0KjML9mWQOX2CKBxq53gJL6rqxclxm' AS password_hash,
    1 AS must_change_password,
    1 AS is_seeded_demo,
    NULL AS otp_code,
    NULL AS otp_expires_at,
    1 AS is_active
FROM offices o
JOIN roles r ON UPPER(r.name) = 'PAMO_ADMIN'
LEFT JOIN offices p ON p.id = o.parent_office_id
WHERE UPPER(COALESCE(o.level, '')) = 'PAMO_ADMIN'
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.office_id = o.id
        AND u.role_id = r.id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE LOWER(u.email) = LOWER(CONCAT('pamo.admin.office', o.id, '@denr.gov.ph'))
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
    otp_code,
    otp_expires_at,
    is_active
)
SELECT
    o.id AS office_id,
    r.id AS role_id,
    LEFT(COALESCE(NULLIF(TRIM(p.name), ''), 'PAMO'), 50) AS first_name,
    'Officer' AS last_name,
    CONCAT('pasu.officer.office', o.id, '@denr.gov.ph') AS email,
    '$2y$10$Xd.w/CivotEto3.KReUKHus0KjML9mWQOX2CKBxq53gJL6rqxclxm' AS password_hash,
    1 AS must_change_password,
    1 AS is_seeded_demo,
    NULL AS otp_code,
    NULL AS otp_expires_at,
    1 AS is_active
FROM offices o
JOIN roles r ON UPPER(r.name) = 'PASU_OFFICER'
LEFT JOIN offices p ON p.id = o.parent_office_id
WHERE UPPER(COALESCE(o.level, '')) = 'PASU_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.office_id = o.id
        AND u.role_id = r.id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE LOWER(u.email) = LOWER(CONCAT('pasu.officer.office', o.id, '@denr.gov.ph'))
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
    otp_code,
    otp_expires_at,
    is_active
)
SELECT
    o.id AS office_id,
    r.id AS role_id,
    LEFT(COALESCE(NULLIF(TRIM(p2.name), ''), 'PAMO'), 50) AS first_name,
    LEFT(CONCAT('Unit - ', COALESCE(o.name, 'Unit')), 50) AS last_name,
    CONCAT('pamo.unit.office', o.id, '@denr.gov.ph') AS email,
    '$2y$10$Xd.w/CivotEto3.KReUKHus0KjML9mWQOX2CKBxq53gJL6rqxclxm' AS password_hash,
    1 AS must_change_password,
    1 AS is_seeded_demo,
    NULL AS otp_code,
    NULL AS otp_expires_at,
    1 AS is_active
FROM offices o
JOIN roles r ON UPPER(r.name) = 'PAMO_UNIT'
LEFT JOIN offices p1 ON p1.id = o.parent_office_id
LEFT JOIN offices p2 ON p2.id = p1.parent_office_id
WHERE UPPER(COALESCE(o.level, '')) = 'PAMO_UNIT'
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.office_id = o.id
        AND u.role_id = r.id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE LOWER(u.email) = LOWER(CONCAT('pamo.unit.office', o.id, '@denr.gov.ph'))
  );

COMMIT;
