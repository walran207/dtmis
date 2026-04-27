START TRANSACTION;

-- Align role naming for hierarchy mapping.
UPDATE roles
SET name = 'DIVISION_CHIEF',
    description = 'Division Chief / Section Chief supervisor'
WHERE name = 'CHIEF';

-- Seed top-level divisions under ORED (idempotent).
INSERT INTO offices (name, level, parent_office_id)
SELECT 'Licenses, Patents & Deeds Division', 'Division', ored.id
FROM offices ored
WHERE ored.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1
      FROM offices d
      WHERE d.name = 'Licenses, Patents & Deeds Division'
        AND d.parent_office_id = ored.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Surveys & Mapping Division', 'Division', ored.id
FROM offices ored
WHERE ored.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1
      FROM offices d
      WHERE d.name = 'Surveys & Mapping Division'
        AND d.parent_office_id = ored.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Conservation & Devt. Division', 'Division', ored.id
FROM offices ored
WHERE ored.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1
      FROM offices d
      WHERE d.name = 'Conservation & Devt. Division'
        AND d.parent_office_id = ored.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Enforcement Division', 'Division', ored.id
FROM offices ored
WHERE ored.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1
      FROM offices d
      WHERE d.name = 'Enforcement Division'
        AND d.parent_office_id = ored.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Legal Division', 'Division', ored.id
FROM offices ored
WHERE ored.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1
      FROM offices d
      WHERE d.name = 'Legal Division'
        AND d.parent_office_id = ored.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Planning and Mgt. Division', 'Division', ored.id
FROM offices ored
WHERE ored.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1
      FROM offices d
      WHERE d.name IN ('Planning and Mgt. Division', 'Planning and Management Division (PMD)')
        AND d.parent_office_id = ored.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Administrative Division', 'Division', ored.id
FROM offices ored
WHERE ored.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1
      FROM offices d
      WHERE d.name = 'Administrative Division'
        AND d.parent_office_id = ored.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Finance Division', 'Division', ored.id
FROM offices ored
WHERE ored.name = 'Office of the Regional Executive Director (ORED)'
  AND NOT EXISTS (
      SELECT 1
      FROM offices d
      WHERE d.name = 'Finance Division'
        AND d.parent_office_id = ored.id
  );

-- Sections: Licenses, Patents & Deeds Division
INSERT INTO offices (name, level, parent_office_id)
SELECT 'Forest Utilization', 'Section', d.id
FROM offices d
WHERE d.name = 'Licenses, Patents & Deeds Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Forest Utilization'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Wildlife Resource Permitting', 'Section', d.id
FROM offices d
WHERE d.name = 'Licenses, Patents & Deeds Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Wildlife Resource Permitting'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Water Resources Utilization', 'Section', d.id
FROM offices d
WHERE d.name = 'Licenses, Patents & Deeds Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Water Resources Utilization'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Patents and Deeds', 'Section', d.id
FROM offices d
WHERE d.name = 'Licenses, Patents & Deeds Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Patents and Deeds'
        AND s.parent_office_id = d.id
  );

-- Sections: Surveys & Mapping Division
INSERT INTO offices (name, level, parent_office_id)
SELECT 'Surveys & Control', 'Section', d.id
FROM offices d
WHERE d.name = 'Surveys & Mapping Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Surveys & Control'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Land Evaluation Surveys', 'Section', d.id
FROM offices d
WHERE d.name = 'Surveys & Mapping Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Land Evaluation Surveys'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Agretacion Surveys & Corrections', 'Section', d.id
FROM offices d
WHERE d.name = 'Surveys & Mapping Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Agretacion Surveys & Corrections'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Original & Other Surveys', 'Section', d.id
FROM offices d
WHERE d.name = 'Surveys & Mapping Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Original & Other Surveys'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Land Records', 'Section', d.id
FROM offices d
WHERE d.name = 'Surveys & Mapping Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Land Records'
        AND s.parent_office_id = d.id
  );

-- Sections: Conservation & Devt. Division
INSERT INTO offices (name, level, parent_office_id)
SELECT 'PA Management & Biodiversity Conservation', 'Section', d.id
FROM offices d
WHERE d.name = 'Conservation & Devt. Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'PA Management & Biodiversity Conservation'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Production Forest Management', 'Section', d.id
FROM offices d
WHERE d.name = 'Conservation & Devt. Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Production Forest Management'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Coastal Resources & Foreshore Management', 'Section', d.id
FROM offices d
WHERE d.name = 'Conservation & Devt. Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Coastal Resources & Foreshore Management'
        AND s.parent_office_id = d.id
  );

-- Sections: Enforcement Division
INSERT INTO offices (name, level, parent_office_id)
SELECT 'Surveillance & Intelligence', 'Section', d.id
FROM offices d
WHERE d.name = 'Enforcement Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Surveillance & Intelligence'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Compliance, Monitoring & Investigation', 'Section', d.id
FROM offices d
WHERE d.name = 'Enforcement Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Compliance, Monitoring & Investigation'
        AND s.parent_office_id = d.id
  );

-- Sections: Planning and Mgt. Division (or existing PMD)
INSERT INTO offices (name, level, parent_office_id)
SELECT 'Planning & Programming', 'Section', d.id
FROM offices d
WHERE d.id = (
    SELECT picked.id
    FROM (
        SELECT id
        FROM offices
        WHERE name IN ('Planning and Mgt. Division', 'Planning and Management Division (PMD)')
        ORDER BY CASE WHEN name = 'Planning and Mgt. Division' THEN 0 ELSE 1 END
        LIMIT 1
    ) AS picked
)
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Planning & Programming'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Monitoring & Evaluation', 'Section', d.id
FROM offices d
WHERE d.id = (
    SELECT picked.id
    FROM (
        SELECT id
        FROM offices
        WHERE name IN ('Planning and Mgt. Division', 'Planning and Management Division (PMD)')
        ORDER BY CASE WHEN name = 'Planning and Mgt. Division' THEN 0 ELSE 1 END
        LIMIT 1
    ) AS picked
)
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Monitoring & Evaluation'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Regional ICT Unit', 'Section', d.id
FROM offices d
WHERE d.id = (
    SELECT picked.id
    FROM (
        SELECT id
        FROM offices
        WHERE name IN ('Planning and Mgt. Division', 'Planning and Management Division (PMD)')
        ORDER BY CASE WHEN name = 'Planning and Mgt. Division' THEN 0 ELSE 1 END
        LIMIT 1
    ) AS picked
)
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Regional ICT Unit'
        AND s.parent_office_id = d.id
  );

-- Sections: Administrative Division
INSERT INTO offices (name, level, parent_office_id)
SELECT 'Personnel', 'Section', d.id
FROM offices d
WHERE d.name = 'Administrative Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Personnel'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'HRDM (Human Resource Development Management)', 'Section', d.id
FROM offices d
WHERE d.name = 'Administrative Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'HRDM (Human Resource Development Management)'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Procurement', 'Section', d.id
FROM offices d
WHERE d.name = 'Administrative Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Procurement'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'General Services', 'Section', d.id
FROM offices d
WHERE d.name = 'Administrative Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'General Services'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Records', 'Section', d.id
FROM offices d
WHERE d.name = 'Administrative Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Records'
        AND s.parent_office_id = d.id
  );

-- Sections: Finance Division
INSERT INTO offices (name, level, parent_office_id)
SELECT 'Cash', 'Section', d.id
FROM offices d
WHERE d.name = 'Finance Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Cash'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Accounting', 'Section', d.id
FROM offices d
WHERE d.name = 'Finance Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Accounting'
        AND s.parent_office_id = d.id
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Budget', 'Section', d.id
FROM offices d
WHERE d.name = 'Finance Division'
  AND NOT EXISTS (
      SELECT 1
      FROM offices s
      WHERE s.name = 'Budget'
        AND s.parent_office_id = d.id
  );

-- Map each section to Division Chief -> Section Staff supervision chain.
INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT parent_role.id,
       child_role.id,
       section_office.id,
       CONCAT(parent_office.name, ' - ', section_office.name)
FROM roles parent_role
JOIN roles child_role
JOIN offices section_office ON section_office.level = 'Section'
JOIN offices parent_office ON parent_office.id = section_office.parent_office_id
WHERE parent_role.name = 'DIVISION_CHIEF'
  AND child_role.name = 'SECTION_STAFF'
  AND parent_office.name IN (
      'Licenses, Patents & Deeds Division',
      'Surveys & Mapping Division',
      'Conservation & Devt. Division',
      'Enforcement Division',
      'Planning and Mgt. Division',
      'Planning and Management Division (PMD)',
      'Administrative Division',
      'Finance Division'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = parent_role.id
        AND rum.child_role_id = child_role.id
        AND rum.office_id = section_office.id
  );

COMMIT;
