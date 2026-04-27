START TRANSACTION;

-- Seed missing PENRO users for target provincial offices.
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
    r.id AS role_id,
    'PENRO' AS first_name,
    REPLACE(o.name, 'PENRO ', '') AS last_name,
    CASE o.name
        WHEN 'PENRO COTABATO' THEN 'penro.cotabato@denr.gov.ph'
        WHEN 'PENRO SULTAN KUDARAT' THEN 'penro.sultan.kudarat@denr.gov.ph'
        WHEN 'PENRO SOUTH COTABATO' THEN 'penro.south.cotabato@denr.gov.ph'
        WHEN 'PENRO SARANGANI' THEN 'penro.sarangani@denr.gov.ph'
        ELSE CONCAT('penro.office', o.id, '@denr.gov.ph')
    END AS email,
    '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK' AS password_hash,
    NULL AS otp_code,
    NULL AS otp_expires_at,
    1 AS is_active
FROM offices o
JOIN roles r ON r.name = 'PENRO'
WHERE o.name IN (
    'PENRO COTABATO',
    'PENRO SULTAN KUDARAT',
    'PENRO SOUTH COTABATO',
    'PENRO SARANGANI'
)
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.office_id = o.id
        AND u.role_id = r.id
  );

-- Seed missing CENRO users for target community offices.
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
    r.id AS role_id,
    'CENRO' AS first_name,
    REPLACE(o.name, 'CENRO ', '') AS last_name,
    CASE o.name
        WHEN 'CENRO Midyap' THEN 'cenro.midyap@denr.gov.ph'
        WHEN 'CENRO Matalam' THEN 'cenro.matalam@denr.gov.ph'
        WHEN 'CENRO Tacurong City' THEN 'cenro.tacurong.city@denr.gov.ph'
        WHEN 'CENRO Kalamansig' THEN 'cenro.kalamansig@denr.gov.ph'
        WHEN 'CENRO Banga' THEN 'cenro.banga@denr.gov.ph'
        WHEN 'CENRO General Santos City' THEN 'cenro.general.santos.city@denr.gov.ph'
        WHEN 'CENRO Kiamba' THEN 'cenro.kiamba@denr.gov.ph'
        WHEN 'CENRO Glan' THEN 'cenro.glan@denr.gov.ph'
        ELSE CONCAT('cenro.office', o.id, '@denr.gov.ph')
    END AS email,
    '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK' AS password_hash,
    NULL AS otp_code,
    NULL AS otp_expires_at,
    1 AS is_active
FROM offices o
JOIN roles r ON r.name = 'CENRO'
WHERE o.name IN (
    'CENRO Midyap',
    'CENRO Matalam',
    'CENRO Tacurong City',
    'CENRO Kalamansig',
    'CENRO Banga',
    'CENRO General Santos City',
    'CENRO Kiamba',
    'CENRO Glan'
)
  AND NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.office_id = o.id
        AND u.role_id = r.id
  );

COMMIT;
