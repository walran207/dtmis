START TRANSACTION;

-- Backfill seeded CENRO internal user labels to include assigned CENRO place.

UPDATE users u
INNER JOIN roles r ON r.id = u.role_id
INNER JOIN offices o ON o.id = u.office_id
LEFT JOIN offices c ON c.id = o.parent_office_id
SET
    u.first_name = LEFT(COALESCE(NULLIF(TRIM(c.name), ''), 'CENRO'), 50),
    u.last_name = 'Admin Record'
WHERE u.is_seeded_demo = 1
  AND UPPER(r.name) = 'CENRO_ADMIN_RECORD'
  AND UPPER(COALESCE(o.level, '')) = 'CENRO_ADMIN_RECORD';

UPDATE users u
INNER JOIN roles r ON r.id = u.role_id
INNER JOIN offices o ON o.id = u.office_id
LEFT JOIN offices c ON c.id = o.parent_office_id
SET
    u.first_name = LEFT(COALESCE(NULLIF(TRIM(c.name), ''), 'CENRO'), 50),
    u.last_name = 'Officer'
WHERE u.is_seeded_demo = 1
  AND UPPER(r.name) = 'CENRO_OFFICER'
  AND UPPER(COALESCE(o.level, '')) = 'CENRO_OFFICER';

UPDATE users u
INNER JOIN roles r ON r.id = u.role_id
INNER JOIN offices o ON o.id = u.office_id
LEFT JOIN offices p1 ON p1.id = o.parent_office_id
LEFT JOIN offices c ON c.id = p1.parent_office_id
SET
    u.first_name = LEFT(COALESCE(NULLIF(TRIM(c.name), ''), 'CENRO'), 50),
    u.last_name = LEFT(CONCAT('Section - ', COALESCE(o.name, 'Section')), 50)
WHERE u.is_seeded_demo = 1
  AND UPPER(r.name) = 'CENRO_SECTION'
  AND UPPER(COALESCE(o.level, '')) = 'CENRO_SECTION';

UPDATE users u
INNER JOIN roles r ON r.id = u.role_id
INNER JOIN offices o ON o.id = u.office_id
LEFT JOIN offices p1 ON p1.id = o.parent_office_id
LEFT JOIN offices p2 ON p2.id = p1.parent_office_id
LEFT JOIN offices c ON c.id = p2.parent_office_id
SET
    u.first_name = LEFT(COALESCE(NULLIF(TRIM(c.name), ''), 'CENRO'), 50),
    u.last_name = LEFT(CONCAT('Unit - ', COALESCE(o.name, 'Unit')), 50)
WHERE u.is_seeded_demo = 1
  AND UPPER(r.name) = 'CENRO_UNIT'
  AND UPPER(COALESCE(o.level, '')) = 'CENRO_UNIT';

COMMIT;