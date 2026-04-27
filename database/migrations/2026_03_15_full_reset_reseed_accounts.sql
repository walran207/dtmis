START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE activity_logs;
TRUNCATE TABLE tracking_slips;
TRUNCATE TABLE document_attachments;
TRUNCATE TABLE documents;
TRUNCATE TABLE document_type_requests;
TRUNCATE TABLE security_audit_logs;
TRUNCATE TABLE role_unit_mappings;
TRUNCATE TABLE users;

SET FOREIGN_KEY_CHECKS = 1;

-- Regional core accounts.
INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    is_active
)
SELECT
    o.id,
    r.id,
    'Regional',
    'Director',
    'ored.regional@denr.gov.ph',
    '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK',
    1
FROM offices o
JOIN roles r ON r.name = 'ORED'
WHERE o.name = 'Office of the Regional Executive Director (ORED)'
LIMIT 1;

INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    is_active
)
SELECT
    o.id,
    r.id,
    'Regional',
    'PACDO',
    'pacdo.regional@denr.gov.ph',
    '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK',
    1
FROM offices o
JOIN roles r ON r.name = 'PACDO'
WHERE o.name = 'Public Affairs and Communication Development Office (PACDO)'
LIMIT 1;

-- PENRO accounts (4 offices).
INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    is_active
)
SELECT
    o.id,
    r.id,
    'PENRO',
    REPLACE(o.name, 'PENRO ', ''),
    CASE o.name
        WHEN 'PENRO COTABATO' THEN 'penro.cotabato@denr.gov.ph'
        WHEN 'PENRO SULTAN KUDARAT' THEN 'penro.sultan.kudarat@denr.gov.ph'
        WHEN 'PENRO SOUTH COTABATO' THEN 'penro.south.cotabato@denr.gov.ph'
        WHEN 'PENRO SARANGANI' THEN 'penro.sarangani@denr.gov.ph'
        ELSE CONCAT('penro.', LOWER(REPLACE(REPLACE(REPLACE(REPLACE(o.name, 'PENRO ', ''), ' ', '.'), '-', '.'), '  ', '.')), '@denr.gov.ph')
    END,
    '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK',
    1
FROM offices o
JOIN roles r ON r.name = 'PENRO'
WHERE o.level = 'Provincial'
  AND o.name LIKE 'PENRO %';

-- CENRO accounts under PENRO offices.
INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    is_active
)
SELECT
    o.id,
    r.id,
    'CENRO',
    REPLACE(o.name, 'CENRO ', ''),
    CASE o.name
        WHEN 'CENRO Midyap' THEN 'cenro.midyap@denr.gov.ph'
        WHEN 'CENRO Matalam' THEN 'cenro.matalam@denr.gov.ph'
        WHEN 'CENRO Tacurong City' THEN 'cenro.tacurong.city@denr.gov.ph'
        WHEN 'CENRO Kalamansig' THEN 'cenro.kalamansig@denr.gov.ph'
        WHEN 'CENRO Banga' THEN 'cenro.banga@denr.gov.ph'
        WHEN 'CENRO General Santos City' THEN 'cenro.general.santos.city@denr.gov.ph'
        WHEN 'CENRO Kiamba' THEN 'cenro.kiamba@denr.gov.ph'
        WHEN 'CENRO Glan' THEN 'cenro.glan@denr.gov.ph'
        ELSE CONCAT('cenro.', LOWER(REPLACE(REPLACE(REPLACE(REPLACE(o.name, 'CENRO ', ''), ' ', '.'), '-', '.'), '  ', '.')), '@denr.gov.ph')
    END,
    '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK',
    1
FROM offices o
JOIN roles r ON r.name = 'CENRO'
WHERE o.level = 'Community'
  AND o.name LIKE 'CENRO %';

-- PASU account(s).
INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    is_active
)
SELECT
    o.id,
    r.id,
    'PASU',
    'Superintendent',
    CONCAT(
        'pasu.',
        LOWER(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(REPLACE(o.name, '.', ''), 'PASU - ', ''),
                            ' ',
                            '.'
                        ),
                        '-',
                        '.'
                    ),
                    '(',
                    ''
                ),
                ')',
                ''
            )
        ),
        '@denr.gov.ph'
    ),
    '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK',
    1
FROM offices o
JOIN roles r ON r.name = 'PASU'
WHERE o.level = 'Protected Area';

-- Division Chief accounts per Division office.
INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    is_active
)
SELECT
    o.id,
    r.id,
    'Division Chief',
    LEFT(o.name, 50),
    CONCAT(
        'chief.',
        LOWER(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(REPLACE(o.name, '.', ''), ' & ', ' '),
                                        '&',
                                        ' '
                                    ),
                                    '(',
                                    ''
                                ),
                                ')',
                                ''
                            ),
                            '-',
                            ' '
                        ),
                        '/',
                        ' '
                    ),
                    ',',
                    ''
                ),
                ' ',
                '.'
            )
        ),
        '@denr.gov.ph'
    ),
    '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK',
    1
FROM offices o
JOIN roles r ON r.name = 'DIVISION_CHIEF'
WHERE o.level = 'Division';

-- Section Staff accounts per Division/Section office, using office-based names (no unit numbers).
INSERT INTO users (
    office_id,
    role_id,
    first_name,
    last_name,
    email,
    password_hash,
    is_active
)
SELECT
    o.id,
    r.id,
    'Section Staff',
    LEFT(o.name, 50),
    CONCAT(
        'staff.',
        LOWER(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(REPLACE(o.name, '.', ''), ' & ', ' '),
                                        '&',
                                        ' '
                                    ),
                                    '(',
                                    ''
                                ),
                                ')',
                                ''
                            ),
                            '-',
                            ' '
                        ),
                        '/',
                        ' '
                    ),
                    ',',
                    ''
                ),
                ' ',
                '.'
            )
        ),
        '@denr.gov.ph'
    ),
    '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK',
    1
FROM offices o
JOIN roles r ON r.name = 'SECTION_STAFF'
WHERE o.level IN ('Division', 'Section');

-- Rebuild role-unit mappings.
INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT
    pr.id,
    cr.id,
    c.id,
    CONCAT(p.name, ' -> ', c.name)
FROM roles pr
JOIN roles cr
JOIN offices c ON c.level = 'Community'
JOIN offices p ON p.id = c.parent_office_id
WHERE pr.name = 'PENRO'
  AND cr.name = 'CENRO'
  AND p.level = 'Provincial';

INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT
    dr.id,
    sr.id,
    s.id,
    CONCAT(d.name, ' -> ', s.name)
FROM roles dr
JOIN roles sr
JOIN offices s ON s.level = 'Section'
JOIN offices d ON d.id = s.parent_office_id
WHERE dr.name = 'DIVISION_CHIEF'
  AND sr.name = 'SECTION_STAFF'
  AND d.level = 'Division';

COMMIT;
