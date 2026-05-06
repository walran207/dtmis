START TRANSACTION;

-- Seed additional PAMO protected-area chains for Cotabato and Sarangani.
-- Temporary password (share only with authorized admin): PamoInternal!2026
-- All seeded accounts require password change at first login.

-- 1) Protected-area root offices
INSERT INTO offices (name, level, parent_office_id)
SELECT 'PASU - MANPI', 'Protected Area', p.id
FROM offices p
WHERE UPPER(COALESCE(p.name, '')) = 'PENRO COTABATO'
  AND UPPER(COALESCE(p.level, '')) = 'PROVINCIAL'
  AND NOT EXISTS (
      SELECT 1
      FROM offices o
      WHERE UPPER(COALESCE(o.name, '')) = 'PASU - MANPI'
        AND UPPER(COALESCE(o.level, '')) = 'PROTECTED AREA'
        AND o.parent_office_id = p.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'PASU - SBPS', 'Protected Area', p.id
FROM offices p
WHERE UPPER(COALESCE(p.name, '')) = 'PENRO SARANGANI'
  AND UPPER(COALESCE(p.level, '')) = 'PROVINCIAL'
  AND NOT EXISTS (
      SELECT 1
      FROM offices o
      WHERE UPPER(COALESCE(o.name, '')) = 'PASU - SBPS'
        AND UPPER(COALESCE(o.level, '')) = 'PROTECTED AREA'
        AND o.parent_office_id = p.id
  );

-- 2) Per-protected-area PAMO Admin and PASU offices
INSERT INTO offices (name, level, parent_office_id)
SELECT CONCAT(p.name, ' - PAMO Admin'), 'PAMO_ADMIN', p.id
FROM offices p
WHERE UPPER(COALESCE(p.level, '')) = 'PROTECTED AREA'
  AND UPPER(COALESCE(p.name, '')) IN ('PASU - MANPI', 'PASU - SBPS')
  AND NOT EXISTS (
      SELECT 1
      FROM offices o
      WHERE o.parent_office_id = p.id
        AND UPPER(COALESCE(o.level, '')) = 'PAMO_ADMIN'
        AND UPPER(COALESCE(o.name, '')) = UPPER(CONCAT(p.name, ' - PAMO Admin'))
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT CONCAT(p.name, ' - PASU'), 'PASU_OFFICER', p.id
FROM offices p
WHERE UPPER(COALESCE(p.level, '')) = 'PROTECTED AREA'
  AND UPPER(COALESCE(p.name, '')) IN ('PASU - MANPI', 'PASU - SBPS')
  AND NOT EXISTS (
      SELECT 1
      FROM offices o
      WHERE o.parent_office_id = p.id
        AND UPPER(COALESCE(o.level, '')) = 'PASU_OFFICER'
        AND UPPER(COALESCE(o.name, '')) = UPPER(CONCAT(p.name, ' - PASU'))
  );

-- 3) Standard PAMO units under each new PASU office
INSERT INTO offices (name, level, parent_office_id)
SELECT u.unit_name, 'PAMO_UNIT', p.id
FROM offices p
JOIN (
    SELECT 'PA RESOURCES MANAGEMENT AND PROTECTED UNIT' AS unit_name
    UNION ALL SELECT 'PA SOCIO - ECONOMIC MANAGEMENT UNIT'
    UNION ALL SELECT 'PA POLICY, PLANNING AND KNOWLEDGE MANAGEMENT UNIT'
) u
WHERE UPPER(COALESCE(p.level, '')) = 'PASU_OFFICER'
  AND p.parent_office_id IN (
      SELECT id
      FROM offices
      WHERE UPPER(COALESCE(level, '')) = 'PROTECTED AREA'
        AND UPPER(COALESCE(name, '')) IN ('PASU - MANPI', 'PASU - SBPS')
  )
  AND NOT EXISTS (
      SELECT 1
      FROM offices o
      WHERE o.parent_office_id = p.id
        AND UPPER(COALESCE(o.level, '')) = 'PAMO_UNIT'
        AND UPPER(COALESCE(o.name, '')) = UPPER(u.unit_name)
  );

-- 4) Role-unit mappings
INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT
    r_admin.id,
    r_officer.id,
    o_officer.id,
    CONCAT(o_officer.name, ' (Admin -> PASU)')
FROM roles r_admin
JOIN roles r_officer ON UPPER(r_officer.name) = 'PASU_OFFICER'
JOIN offices o_officer ON UPPER(COALESCE(o_officer.level, '')) = 'PASU_OFFICER'
JOIN offices p ON p.id = o_officer.parent_office_id
WHERE UPPER(r_admin.name) = 'PAMO_ADMIN'
  AND UPPER(COALESCE(p.name, '')) IN ('PASU - MANPI', 'PASU - SBPS')
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = r_admin.id
        AND rum.child_role_id = r_officer.id
        AND rum.office_id = o_officer.id
  );

INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT
    r_officer.id,
    r_unit.id,
    o_unit.id,
    CONCAT(o_unit.name, ' (PASU -> Unit)')
FROM roles r_officer
JOIN roles r_unit ON UPPER(r_unit.name) = 'PAMO_UNIT'
JOIN offices o_unit ON UPPER(COALESCE(o_unit.level, '')) = 'PAMO_UNIT'
JOIN offices o_pasu ON o_pasu.id = o_unit.parent_office_id
JOIN offices p ON p.id = o_pasu.parent_office_id
WHERE UPPER(r_officer.name) = 'PASU_OFFICER'
  AND UPPER(COALESCE(p.name, '')) IN ('PASU - MANPI', 'PASU - SBPS')
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = r_officer.id
        AND rum.child_role_id = r_unit.id
        AND rum.office_id = o_unit.id
  );

-- 5) Default PAMO users
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
    o.id,
    r.id,
    LEFT(COALESCE(NULLIF(TRIM(p.name), ''), 'PAMO'), 50),
    'Admin',
    CONCAT('pamo.admin.office', o.id, '@denr.gov.ph'),
    '$2y$10$Xd.w/CivotEto3.KReUKHus0KjML9mWQOX2CKBxq53gJL6rqxclxm',
    1,
    1,
    NULL,
    NULL,
    1
FROM offices o
JOIN roles r ON UPPER(r.name) = 'PAMO_ADMIN'
LEFT JOIN offices p ON p.id = o.parent_office_id
WHERE UPPER(COALESCE(o.level, '')) = 'PAMO_ADMIN'
  AND UPPER(COALESCE(p.name, '')) IN ('PASU - MANPI', 'PASU - SBPS')
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
    o.id,
    r.id,
    LEFT(COALESCE(NULLIF(TRIM(p.name), ''), 'PAMO'), 50),
    'Officer',
    CONCAT('pasu.officer.office', o.id, '@denr.gov.ph'),
    '$2y$10$Xd.w/CivotEto3.KReUKHus0KjML9mWQOX2CKBxq53gJL6rqxclxm',
    1,
    1,
    NULL,
    NULL,
    1
FROM offices o
JOIN roles r ON UPPER(r.name) = 'PASU_OFFICER'
LEFT JOIN offices p ON p.id = o.parent_office_id
WHERE UPPER(COALESCE(o.level, '')) = 'PASU_OFFICER'
  AND UPPER(COALESCE(p.name, '')) IN ('PASU - MANPI', 'PASU - SBPS')
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
    o.id,
    r.id,
    LEFT(COALESCE(NULLIF(TRIM(p2.name), ''), 'PAMO'), 50),
    LEFT(CONCAT('Unit - ', COALESCE(o.name, 'Unit')), 50),
    CONCAT('pamo.unit.office', o.id, '@denr.gov.ph'),
    '$2y$10$Xd.w/CivotEto3.KReUKHus0KjML9mWQOX2CKBxq53gJL6rqxclxm',
    1,
    1,
    NULL,
    NULL,
    1
FROM offices o
JOIN roles r ON UPPER(r.name) = 'PAMO_UNIT'
LEFT JOIN offices p1 ON p1.id = o.parent_office_id
LEFT JOIN offices p2 ON p2.id = p1.parent_office_id
WHERE UPPER(COALESCE(o.level, '')) = 'PAMO_UNIT'
  AND UPPER(COALESCE(p2.name, '')) IN ('PASU - MANPI', 'PASU - SBPS')
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
