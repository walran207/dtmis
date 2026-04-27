START TRANSACTION;

-- Normalize existing names to requested naming style.
UPDATE offices
SET name = 'PENRO SOUTH COTABATO'
WHERE name = 'PENRO - South Cotabato (Koronadal City)';

UPDATE offices
SET name = 'PENRO SARANGANI'
WHERE name = 'PENRO - Sarangani';

UPDATE offices
SET name = 'CENRO Banga'
WHERE name = 'CENRO - Banga';

UPDATE offices
SET name = 'CENRO Glan'
WHERE name = 'CENRO - Glan';

-- Seed PENRO offices under ORED.
INSERT INTO offices (name, level, parent_office_id)
SELECT 'PENRO COTABATO', 'Provincial', o.id
FROM offices o
WHERE o.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1 FROM offices p
      WHERE p.name = 'PENRO COTABATO'
        AND p.level = 'Provincial'
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'PENRO SULTAN KUDARAT', 'Provincial', o.id
FROM offices o
WHERE o.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1 FROM offices p
      WHERE p.name = 'PENRO SULTAN KUDARAT'
        AND p.level = 'Provincial'
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'PENRO SOUTH COTABATO', 'Provincial', o.id
FROM offices o
WHERE o.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1 FROM offices p
      WHERE p.name = 'PENRO SOUTH COTABATO'
        AND p.level = 'Provincial'
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'PENRO SARANGANI', 'Provincial', o.id
FROM offices o
WHERE o.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1 FROM offices p
      WHERE p.name = 'PENRO SARANGANI'
        AND p.level = 'Provincial'
  );

-- Seed CENRO offices under their PENRO parent.
INSERT INTO offices (name, level, parent_office_id)
SELECT 'CENRO Midyap', 'Community', p.id
FROM offices p
WHERE p.name = 'PENRO COTABATO'
  AND NOT EXISTS (
      SELECT 1 FROM offices c
      WHERE c.name = 'CENRO Midyap'
        AND c.parent_office_id = p.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'CENRO Matalam', 'Community', p.id
FROM offices p
WHERE p.name = 'PENRO COTABATO'
  AND NOT EXISTS (
      SELECT 1 FROM offices c
      WHERE c.name = 'CENRO Matalam'
        AND c.parent_office_id = p.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'CENRO Tacurong City', 'Community', p.id
FROM offices p
WHERE p.name = 'PENRO SULTAN KUDARAT'
  AND NOT EXISTS (
      SELECT 1 FROM offices c
      WHERE c.name = 'CENRO Tacurong City'
        AND c.parent_office_id = p.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'CENRO Kalamansig', 'Community', p.id
FROM offices p
WHERE p.name = 'PENRO SULTAN KUDARAT'
  AND NOT EXISTS (
      SELECT 1 FROM offices c
      WHERE c.name = 'CENRO Kalamansig'
        AND c.parent_office_id = p.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'CENRO Banga', 'Community', p.id
FROM offices p
WHERE p.name = 'PENRO SOUTH COTABATO'
  AND NOT EXISTS (
      SELECT 1 FROM offices c
      WHERE c.name = 'CENRO Banga'
        AND c.parent_office_id = p.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'CENRO General Santos City', 'Community', p.id
FROM offices p
WHERE p.name = 'PENRO SOUTH COTABATO'
  AND NOT EXISTS (
      SELECT 1 FROM offices c
      WHERE c.name = 'CENRO General Santos City'
        AND c.parent_office_id = p.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'CENRO Kiamba', 'Community', p.id
FROM offices p
WHERE p.name = 'PENRO SARANGANI'
  AND NOT EXISTS (
      SELECT 1 FROM offices c
      WHERE c.name = 'CENRO Kiamba'
        AND c.parent_office_id = p.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'CENRO Glan', 'Community', p.id
FROM offices p
WHERE p.name = 'PENRO SARANGANI'
  AND NOT EXISTS (
      SELECT 1 FROM offices c
      WHERE c.name = 'CENRO Glan'
        AND c.parent_office_id = p.id
  );

-- Ensure normalized existing CENROs are attached to the correct PENRO parent.
UPDATE offices c
JOIN offices p ON p.name = 'PENRO SOUTH COTABATO'
SET c.parent_office_id = p.id
WHERE c.name = 'CENRO Banga';

UPDATE offices c
JOIN offices p ON p.name = 'PENRO SARANGANI'
SET c.parent_office_id = p.id
WHERE c.name = 'CENRO Glan';

-- Keep PENRO -> CENRO mappings clean: remove wrong scope rows, then insert CENRO mappings.
DELETE rum
FROM role_unit_mappings rum
JOIN roles pr ON pr.id = rum.parent_role_id
JOIN roles cr ON cr.id = rum.child_role_id
JOIN offices o ON o.id = rum.office_id
WHERE pr.name = 'PENRO'
  AND cr.name = 'CENRO'
  AND o.level <> 'Community';

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
  AND p.name IN (
      'PENRO COTABATO',
      'PENRO SULTAN KUDARAT',
      'PENRO SOUTH COTABATO',
      'PENRO SARANGANI'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = pr.id
        AND rum.child_role_id = cr.id
        AND rum.office_id = c.id
  );

COMMIT;
