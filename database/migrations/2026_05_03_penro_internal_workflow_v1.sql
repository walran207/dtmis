-- PENRO Internal Workflow v1
-- Adds PENRO internal role layer, office seed tree, role mappings, and transitions.

START TRANSACTION;

-- 1) Roles
INSERT INTO roles (name, description)
SELECT 'PENRO_ADMIN_RECORD', 'PENRO Admin Record (PENRO internal records steward)'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'PENRO_ADMIN_RECORD'
);

INSERT INTO roles (name, description)
SELECT 'PENRO_OFFICER', 'PENRO Officer (PENRO head office reviewer/signatory)'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'PENRO_OFFICER'
);

INSERT INTO roles (name, description)
SELECT 'PENRO_DIVISION', 'PENRO Division (PENRO internal division-level processing)'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'PENRO_DIVISION'
);

INSERT INTO roles (name, description)
SELECT 'PENRO_SECTION', 'PENRO Section (PENRO internal section/unit-level processing)'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'PENRO_SECTION'
);

-- 2) Per-PENRO office internal child offices (Admin + Officer)
INSERT INTO offices (name, level, parent_office_id)
SELECT
    CONCAT(p.name, ' - Admin Records'),
    'PENRO_ADMIN_RECORD',
    p.id
FROM offices p
WHERE UPPER(COALESCE(p.level, '')) = 'PROVINCIAL'
  AND NOT EXISTS (
      SELECT 1
      FROM offices x
      WHERE x.parent_office_id = p.id
        AND UPPER(COALESCE(x.level, '')) = 'PENRO_ADMIN_RECORD'
        AND UPPER(COALESCE(x.name, '')) = UPPER(CONCAT(p.name, ' - Admin Records'))
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT
    CONCAT(p.name, ' - Officer'),
    'PENRO_OFFICER',
    p.id
FROM offices p
WHERE UPPER(COALESCE(p.level, '')) = 'PROVINCIAL'
  AND NOT EXISTS (
      SELECT 1
      FROM offices x
      WHERE x.parent_office_id = p.id
        AND UPPER(COALESCE(x.level, '')) = 'PENRO_OFFICER'
        AND UPPER(COALESCE(x.name, '')) = UPPER(CONCAT(p.name, ' - Officer'))
  );

-- 3) Standardized PENRO divisions under each PENRO Officer office
INSERT INTO offices (name, level, parent_office_id)
SELECT
    d.division_name,
    'PENRO_DIVISION',
    o.id
FROM offices o
JOIN (
    SELECT 'Technical Services Division' AS division_name
    UNION ALL SELECT 'Management Services Division'
) d
WHERE UPPER(COALESCE(o.level, '')) = 'PENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM offices x
      WHERE x.parent_office_id = o.id
        AND UPPER(COALESCE(x.level, '')) = 'PENRO_DIVISION'
        AND UPPER(COALESCE(x.name, '')) = UPPER(d.division_name)
  );

-- 4) Standardized PENRO sections/units under each seeded PENRO division
INSERT INTO offices (name, level, parent_office_id)
SELECT
    s.section_name,
    'PENRO_SECTION',
    d.id
FROM offices d
JOIN (
    SELECT 'TECHNICAL SERVICES DIVISION' AS division_name, 'Mt. Matutum Protected Landscape (MMPL)' AS section_name
    UNION ALL SELECT 'TECHNICAL SERVICES DIVISION', 'Allah Valley Protected Landscape (AVPL)'
    UNION ALL SELECT 'TECHNICAL SERVICES DIVISION', 'Conservation & Development Section'
    UNION ALL SELECT 'TECHNICAL SERVICES DIVISION', 'Regulation & Permitting Section'
    UNION ALL SELECT 'TECHNICAL SERVICES DIVISION', 'Monitoring & Enforcement Section'
    UNION ALL SELECT 'MANAGEMENT SERVICES DIVISION', 'Admin & Finance Section'
    UNION ALL SELECT 'MANAGEMENT SERVICES DIVISION', 'Accounting Unit'
    UNION ALL SELECT 'MANAGEMENT SERVICES DIVISION', 'Budgeting Unit'
    UNION ALL SELECT 'MANAGEMENT SERVICES DIVISION', 'Human Resources Unit'
    UNION ALL SELECT 'MANAGEMENT SERVICES DIVISION', 'General Services Unit'
    UNION ALL SELECT 'MANAGEMENT SERVICES DIVISION', 'Records Unit'
    UNION ALL SELECT 'MANAGEMENT SERVICES DIVISION', 'Planning Section'
    UNION ALL SELECT 'MANAGEMENT SERVICES DIVISION', 'ICT Unit'
    UNION ALL SELECT 'MANAGEMENT SERVICES DIVISION', 'Cashiering Unit'
) s
    ON UPPER(COALESCE(d.name, '')) = s.division_name
WHERE UPPER(COALESCE(d.level, '')) = 'PENRO_DIVISION'
  AND NOT EXISTS (
      SELECT 1
      FROM offices x
      WHERE x.parent_office_id = d.id
        AND UPPER(COALESCE(x.level, '')) = 'PENRO_SECTION'
        AND UPPER(COALESCE(x.name, '')) = UPPER(s.section_name)
  );

-- 5) role_unit_mappings for PENRO internal chain
INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT
    r_admin.id,
    r_officer.id,
    o_officer.id,
    CONCAT(o_officer.name, ' (Admin Record -> Officer)')
FROM roles r_admin
JOIN roles r_officer ON UPPER(r_officer.name) = 'PENRO_OFFICER'
JOIN offices o_officer ON UPPER(COALESCE(o_officer.level, '')) = 'PENRO_OFFICER'
WHERE UPPER(r_admin.name) = 'PENRO_ADMIN_RECORD'
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
    r_division.id,
    o_division.id,
    CONCAT(o_division.name, ' (Officer -> Division)')
FROM roles r_officer
JOIN roles r_division ON UPPER(r_division.name) = 'PENRO_DIVISION'
JOIN offices o_division ON UPPER(COALESCE(o_division.level, '')) = 'PENRO_DIVISION'
WHERE UPPER(r_officer.name) = 'PENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = r_officer.id
        AND rum.child_role_id = r_division.id
        AND rum.office_id = o_division.id
  );

INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT
    r_division.id,
    r_section.id,
    o_section.id,
    CONCAT(o_section.name, ' (Division -> Section)')
FROM roles r_division
JOIN roles r_section ON UPPER(r_section.name) = 'PENRO_SECTION'
JOIN offices o_section ON UPPER(COALESCE(o_section.level, '')) = 'PENRO_SECTION'
WHERE UPPER(r_division.name) = 'PENRO_DIVISION'
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = r_division.id
        AND rum.child_role_id = r_section.id
        AND rum.office_id = o_section.id
  );

-- 6) workflow_transitions for PENRO internal workflow + escalation + complete
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PENRO Admin Record to PENRO Officer', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PENRO_OFFICER'
WHERE UPPER(rf.name) = 'PENRO_ADMIN_RECORD'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PENRO Officer to PENRO Division', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PENRO_DIVISION'
WHERE UPPER(rf.name) = 'PENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PENRO Division to PENRO Section', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PENRO_SECTION'
WHERE UPPER(rf.name) = 'PENRO_DIVISION'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PENRO Section back to PENRO Division', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PENRO_DIVISION'
WHERE UPPER(rf.name) = 'PENRO_SECTION'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PENRO Division back to PENRO Admin Record', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PENRO_ADMIN_RECORD'
WHERE UPPER(rf.name) = 'PENRO_DIVISION'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PENRO Admin Record to CENRO Admin Record', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'CENRO_ADMIN_RECORD'
WHERE UPPER(rf.name) = 'PENRO_ADMIN_RECORD'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PENRO Admin Record to PACDO/Records Unit', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) IN ('PACDO', 'RECORDS_UNIT')
WHERE UPPER(rf.name) = 'PENRO_ADMIN_RECORD'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'COMPLETE', rf.id, NULL, 'PENRO Admin Record can complete locally', 1
FROM roles rf
WHERE UPPER(rf.name) = 'PENRO_ADMIN_RECORD'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'COMPLETE'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id IS NULL
  );

-- Receive permissions
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RECEIVE', NULL, r.id, CONCAT('Receive into custody (', r.name, ')'), 1
FROM roles r
WHERE UPPER(r.name) IN ('PENRO_ADMIN_RECORD', 'PENRO_OFFICER', 'PENRO_DIVISION', 'PENRO_SECTION')
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'RECEIVE'
        AND wt.allowed_from_role_id IS NULL
        AND wt.allowed_to_role_id = r.id
  );

-- Approve permissions
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'APPROVE', r.id, NULL, CONCAT(r.name, ' can approve'), 1
FROM roles r
WHERE UPPER(r.name) IN ('PENRO_OFFICER', 'PENRO_DIVISION', 'PENRO_SECTION')
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'APPROVE'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

-- Pending permissions
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'PENDING', r.id, NULL, CONCAT(r.name, ' can mark pending'), 1
FROM roles r
WHERE UPPER(r.name) IN ('PENRO_ADMIN_RECORD', 'PENRO_OFFICER', 'PENRO_DIVISION', 'PENRO_SECTION')
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'PENDING'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

-- Sign permission
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'SIGN', r.id, NULL, 'PENRO Officer can sign', 1
FROM roles r
WHERE UPPER(r.name) = 'PENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'SIGN'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

COMMIT;
