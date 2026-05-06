-- CENRO Internal Workflow v1
-- Adds CENRO internal role layer, office seed tree, role mappings, and transitions.

START TRANSACTION;

-- 1) Roles
INSERT INTO roles (name, description)
SELECT 'CENRO_ADMIN_RECORD', 'CENRO Admin Record (CENRO internal records steward)'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'CENRO_ADMIN_RECORD'
);

INSERT INTO roles (name, description)
SELECT 'CENRO_OFFICER', 'CENRO Officer (CENRO head office reviewer/signatory)'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'CENRO_OFFICER'
);

INSERT INTO roles (name, description)
SELECT 'CENRO_SECTION', 'CENRO Section (CENRO internal section-level processing)'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'CENRO_SECTION'
);

INSERT INTO roles (name, description)
SELECT 'CENRO_UNIT', 'CENRO Unit (CENRO internal unit-level processing)'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'CENRO_UNIT'
);

-- 2) Per-CENRO office internal child offices (Admin + Officer)
INSERT INTO offices (name, level, parent_office_id)
SELECT
    CONCAT(c.name, ' - Admin Records'),
    'CENRO_ADMIN_RECORD',
    c.id
FROM offices c
WHERE UPPER(COALESCE(c.level, '')) = 'COMMUNITY'
  AND UPPER(COALESCE(c.name, '')) LIKE 'CENRO %'
  AND NOT EXISTS (
      SELECT 1
      FROM offices x
      WHERE x.parent_office_id = c.id
        AND UPPER(COALESCE(x.level, '')) = 'CENRO_ADMIN_RECORD'
        AND UPPER(COALESCE(x.name, '')) = UPPER(CONCAT(c.name, ' - Admin Records'))
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT
    CONCAT(c.name, ' - Officer'),
    'CENRO_OFFICER',
    c.id
FROM offices c
WHERE UPPER(COALESCE(c.level, '')) = 'COMMUNITY'
  AND UPPER(COALESCE(c.name, '')) LIKE 'CENRO %'
  AND NOT EXISTS (
      SELECT 1
      FROM offices x
      WHERE x.parent_office_id = c.id
        AND UPPER(COALESCE(x.level, '')) = 'CENRO_OFFICER'
        AND UPPER(COALESCE(x.name, '')) = UPPER(CONCAT(c.name, ' - Officer'))
  );

-- 3) Standardized CENRO sections under each CENRO Officer office
INSERT INTO offices (name, level, parent_office_id)
SELECT
    s.section_name,
    'CENRO_SECTION',
    o.id
FROM offices o
JOIN (
    SELECT 'Monitoring and Enforcement Section (MES)' AS section_name
    UNION ALL SELECT 'Conservation and Development Section (CDS)'
    UNION ALL SELECT 'Regulation and Permitting Section (RPS)'
    UNION ALL SELECT 'Planning and Support Unit (PSU)'
) s
WHERE UPPER(COALESCE(o.level, '')) = 'CENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM offices x
      WHERE x.parent_office_id = o.id
        AND UPPER(COALESCE(x.level, '')) = 'CENRO_SECTION'
        AND UPPER(COALESCE(x.name, '')) = UPPER(s.section_name)
  );

-- 4) Standardized CENRO units under each seeded CENRO section
INSERT INTO offices (name, level, parent_office_id)
SELECT
    u.unit_name,
    'CENRO_UNIT',
    s.id
FROM offices s
JOIN (
    SELECT 'Monitoring and Enforcement Section (MES)' AS section_name, 'Patrolling/Forest Surveillance' AS unit_name
    UNION ALL SELECT 'Monitoring and Enforcement Section (MES)', 'Enforcement and Monitoring Tenure Assessment'
    UNION ALL SELECT 'Conservation and Development Section (CDS)', 'National Greening Program'
    UNION ALL SELECT 'Conservation and Development Section (CDS)', 'Coastal and Marine Ecosystem Management Program'
    UNION ALL SELECT 'Regulation and Permitting Section (RPS)', 'Survey and Mapping Unit'
    UNION ALL SELECT 'Regulation and Permitting Section (RPS)', 'Patents and Deeds Unit'
    UNION ALL SELECT 'Regulation and Permitting Section (RPS)', 'Permitting and Licensing Unit'
) u
    ON UPPER(COALESCE(s.name, '')) = UPPER(u.section_name)
WHERE UPPER(COALESCE(s.level, '')) = 'CENRO_SECTION'
  AND NOT EXISTS (
      SELECT 1
      FROM offices x
      WHERE x.parent_office_id = s.id
        AND UPPER(COALESCE(x.level, '')) = 'CENRO_UNIT'
        AND UPPER(COALESCE(x.name, '')) = UPPER(u.unit_name)
  );

-- 5) role_unit_mappings for CENRO internal chain
INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT
    r_admin.id,
    r_officer.id,
    o_officer.id,
    CONCAT(o_officer.name, ' (Admin Record -> Officer)')
FROM roles r_admin
JOIN roles r_officer ON UPPER(r_officer.name) = 'CENRO_OFFICER'
JOIN offices o_officer ON UPPER(COALESCE(o_officer.level, '')) = 'CENRO_OFFICER'
WHERE UPPER(r_admin.name) = 'CENRO_ADMIN_RECORD'
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
    r_section.id,
    o_section.id,
    CONCAT(o_section.name, ' (Officer -> Section)')
FROM roles r_officer
JOIN roles r_section ON UPPER(r_section.name) = 'CENRO_SECTION'
JOIN offices o_section ON UPPER(COALESCE(o_section.level, '')) = 'CENRO_SECTION'
WHERE UPPER(r_officer.name) = 'CENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = r_officer.id
        AND rum.child_role_id = r_section.id
        AND rum.office_id = o_section.id
  );

INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT
    r_section.id,
    r_unit.id,
    o_unit.id,
    CONCAT(o_unit.name, ' (Section -> Unit)')
FROM roles r_section
JOIN roles r_unit ON UPPER(r_unit.name) = 'CENRO_UNIT'
JOIN offices o_unit ON UPPER(COALESCE(o_unit.level, '')) = 'CENRO_UNIT'
WHERE UPPER(r_section.name) = 'CENRO_SECTION'
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = r_section.id
        AND rum.child_role_id = r_unit.id
        AND rum.office_id = o_unit.id
  );

-- 6) workflow_transitions for CENRO internal workflow + escalation + complete
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'CENRO Admin Record to CENRO Officer', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'CENRO_OFFICER'
WHERE UPPER(rf.name) = 'CENRO_ADMIN_RECORD'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'CENRO Officer to CENRO Section', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'CENRO_SECTION'
WHERE UPPER(rf.name) = 'CENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'CENRO Section to CENRO Unit', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'CENRO_UNIT'
WHERE UPPER(rf.name) = 'CENRO_SECTION'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'CENRO Unit back to CENRO Section', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'CENRO_SECTION'
WHERE UPPER(rf.name) = 'CENRO_UNIT'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'CENRO Section back to CENRO Admin Record', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'CENRO_ADMIN_RECORD'
WHERE UPPER(rf.name) = 'CENRO_SECTION'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'CENRO Admin Record to PENRO Admin Record', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PENRO_ADMIN_RECORD'
WHERE UPPER(rf.name) = 'CENRO_ADMIN_RECORD'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'CENRO Admin Record to PACDO/Records Unit', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) IN ('PACDO', 'RECORDS_UNIT')
WHERE UPPER(rf.name) = 'CENRO_ADMIN_RECORD'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'COMPLETE', rf.id, NULL, 'CENRO Admin Record can complete locally', 1
FROM roles rf
WHERE UPPER(rf.name) = 'CENRO_ADMIN_RECORD'
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
WHERE UPPER(r.name) IN ('CENRO_ADMIN_RECORD', 'CENRO_OFFICER', 'CENRO_SECTION', 'CENRO_UNIT')
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
WHERE UPPER(r.name) IN ('CENRO_OFFICER', 'CENRO_SECTION', 'CENRO_UNIT')
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
WHERE UPPER(r.name) IN ('CENRO_ADMIN_RECORD', 'CENRO_OFFICER', 'CENRO_SECTION', 'CENRO_UNIT')
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'PENDING'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

-- Sign permission (CENRO Officer full ORED-like authority)
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'SIGN', r.id, NULL, 'CENRO Officer can sign', 1
FROM roles r
WHERE UPPER(r.name) = 'CENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'SIGN'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

COMMIT;
