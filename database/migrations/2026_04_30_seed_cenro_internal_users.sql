START TRANSACTION;

-- Seed default CENRO internal users (idempotent).
-- Temporary password (share only with authorized admin): CenroInternal!2026
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
    LEFT(COALESCE(NULLIF(TRIM(c.name), ''), 'CENRO'), 50) AS first_name,
    'Admin Record' AS last_name,
    CONCAT('cenro.admin.record.office', o.id, '@denr.gov.ph') AS email,
    '$2y$10$x7N2cmQdK/6cgepI9nbk8uHHCkXAYzBXg6yDfWkVUrb0hBKOQFYe6' AS password_hash,
    1 AS must_change_password,
    1 AS is_seeded_demo,
    NULL AS otp_code,
    NULL AS otp_expires_at,
    1 AS is_active
FROM offices o
JOIN roles r ON UPPER(r.name) = 'CENRO_ADMIN_RECORD'
LEFT JOIN offices c ON c.id = o.parent_office_id
WHERE UPPER(COALESCE(o.level, '')) = 'CENRO_ADMIN_RECORD'
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.office_id = o.id
        AND u.role_id = r.id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE LOWER(u.email) = LOWER(CONCAT('cenro.admin.record.office', o.id, '@denr.gov.ph'))
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
    LEFT(COALESCE(NULLIF(TRIM(c.name), ''), 'CENRO'), 50) AS first_name,
    'Officer' AS last_name,
    CONCAT('cenro.officer.office', o.id, '@denr.gov.ph') AS email,
    '$2y$10$x7N2cmQdK/6cgepI9nbk8uHHCkXAYzBXg6yDfWkVUrb0hBKOQFYe6' AS password_hash,
    1 AS must_change_password,
    1 AS is_seeded_demo,
    NULL AS otp_code,
    NULL AS otp_expires_at,
    1 AS is_active
FROM offices o
JOIN roles r ON UPPER(r.name) = 'CENRO_OFFICER'
LEFT JOIN offices c ON c.id = o.parent_office_id
WHERE UPPER(COALESCE(o.level, '')) = 'CENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.office_id = o.id
        AND u.role_id = r.id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE LOWER(u.email) = LOWER(CONCAT('cenro.officer.office', o.id, '@denr.gov.ph'))
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
    LEFT(COALESCE(NULLIF(TRIM(c.name), ''), 'CENRO'), 50) AS first_name,
    LEFT(CONCAT('Section - ', COALESCE(o.name, 'Section')), 50) AS last_name,
    CONCAT('cenro.section.office', o.id, '@denr.gov.ph') AS email,
    '$2y$10$x7N2cmQdK/6cgepI9nbk8uHHCkXAYzBXg6yDfWkVUrb0hBKOQFYe6' AS password_hash,
    1 AS must_change_password,
    1 AS is_seeded_demo,
    NULL AS otp_code,
    NULL AS otp_expires_at,
    1 AS is_active
FROM offices o
JOIN roles r ON UPPER(r.name) = 'CENRO_SECTION'
LEFT JOIN offices p1 ON p1.id = o.parent_office_id
LEFT JOIN offices c ON c.id = p1.parent_office_id
WHERE UPPER(COALESCE(o.level, '')) = 'CENRO_SECTION'
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.office_id = o.id
        AND u.role_id = r.id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE LOWER(u.email) = LOWER(CONCAT('cenro.section.office', o.id, '@denr.gov.ph'))
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
    LEFT(COALESCE(NULLIF(TRIM(c.name), ''), 'CENRO'), 50) AS first_name,
    LEFT(CONCAT('Unit - ', COALESCE(o.name, 'Unit')), 50) AS last_name,
    CONCAT('cenro.unit.office', o.id, '@denr.gov.ph') AS email,
    '$2y$10$x7N2cmQdK/6cgepI9nbk8uHHCkXAYzBXg6yDfWkVUrb0hBKOQFYe6' AS password_hash,
    1 AS must_change_password,
    1 AS is_seeded_demo,
    NULL AS otp_code,
    NULL AS otp_expires_at,
    1 AS is_active
FROM offices o
JOIN roles r ON UPPER(r.name) = 'CENRO_UNIT'
LEFT JOIN offices p1 ON p1.id = o.parent_office_id
LEFT JOIN offices p2 ON p2.id = p1.parent_office_id
LEFT JOIN offices c ON c.id = p2.parent_office_id
WHERE UPPER(COALESCE(o.level, '')) = 'CENRO_UNIT'
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.office_id = o.id
        AND u.role_id = r.id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE LOWER(u.email) = LOWER(CONCAT('cenro.unit.office', o.id, '@denr.gov.ph'))
  );

COMMIT;
