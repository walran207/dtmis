START TRANSACTION;

-- Ensure role naming is aligned.
UPDATE roles
SET name = 'DIVISION_CHIEF',
    description = 'Division Chief / Section Chief supervisor'
WHERE name = 'CHIEF';

-- Seed one Division Chief account per Division/Section office unit.
INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    otp_code,
    otp_expires_at,
    is_active
)
SELECT
    o.id AS office_id,
    rc.id AS role_id,
    'Division Chief' AS first_name,
    CONCAT('Unit ', o.id) AS last_name,
    CONCAT('chief.unit', o.id, '@denr.gov.ph') AS email,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' AS password_hash,
    NULL AS otp_code,
    NULL AS otp_expires_at,
    1 AS is_active
FROM offices o
JOIN roles rc ON rc.name = 'DIVISION_CHIEF'
WHERE o.level IN ('Division', 'Section')
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.email = CONCAT('chief.unit', o.id, '@denr.gov.ph')
  );

-- Seed one Section Staff account per Division/Section office unit.
INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    otp_code,
    otp_expires_at,
    is_active
)
SELECT
    o.id AS office_id,
    rs.id AS role_id,
    'Section Staff' AS first_name,
    CONCAT('Unit ', o.id) AS last_name,
    CONCAT('staff.unit', o.id, '@denr.gov.ph') AS email,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' AS password_hash,
    NULL AS otp_code,
    NULL AS otp_expires_at,
    1 AS is_active
FROM offices o
JOIN roles rs ON rs.name = 'SECTION_STAFF'
WHERE o.level IN ('Division', 'Section')
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.email = CONCAT('staff.unit', o.id, '@denr.gov.ph')
  );

COMMIT;
