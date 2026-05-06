-- PAMO Internal Workflow v1
-- Adds PAMO internal role layer, office seed tree, role mappings, and transitions.

START TRANSACTION;

-- 1) Roles
INSERT INTO roles (name, description)
SELECT 'PAMO_ADMIN', 'PAMO Admin (PAMO internal admin / records steward)'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'PAMO_ADMIN'
);

INSERT INTO roles (name, description)
SELECT 'PASU_OFFICER', 'Protected Area Superintendent (PAMO head office reviewer/signatory)'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'PASU_OFFICER'
);

INSERT INTO roles (name, description)
SELECT 'PAMO_UNIT', 'PAMO Unit (PAMO internal unit-level processing)'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'PAMO_UNIT'
);

-- 2) Per-protected-area PAMO child offices (Admin + Officer)
INSERT INTO offices (name, level, parent_office_id)
SELECT
    CONCAT(p.name, ' - PAMO Admin'),
    'PAMO_ADMIN',
    p.id
FROM offices p
WHERE UPPER(COALESCE(p.level, '')) = 'PROTECTED AREA'
  AND NOT EXISTS (
      SELECT 1
      FROM offices x
      WHERE x.parent_office_id = p.id
        AND UPPER(COALESCE(x.level, '')) = 'PAMO_ADMIN'
        AND UPPER(COALESCE(x.name, '')) = UPPER(CONCAT(p.name, ' - PAMO Admin'))
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT
    CONCAT(p.name, ' - PASU'),
    'PASU_OFFICER',
    p.id
FROM offices p
WHERE UPPER(COALESCE(p.level, '')) = 'PROTECTED AREA'
  AND NOT EXISTS (
      SELECT 1
      FROM offices x
      WHERE x.parent_office_id = p.id
        AND UPPER(COALESCE(x.level, '')) = 'PASU_OFFICER'
        AND UPPER(COALESCE(x.name, '')) = UPPER(CONCAT(p.name, ' - PASU'))
  );

-- 3) Standardized PAMO units under each PASU office
INSERT INTO offices (name, level, parent_office_id)
SELECT
    u.unit_name,
    'PAMO_UNIT',
    o.id
FROM offices o
JOIN (
    SELECT 'PA RESOURCES MANAGEMENT AND PROTECTED UNIT' AS unit_name
    UNION ALL SELECT 'PA SOCIO - ECONOMIC MANAGEMENT UNIT'
    UNION ALL SELECT 'PA POLICY, PLANNING AND KNOWLEDGE MANAGEMENT UNIT'
) u
WHERE UPPER(COALESCE(o.level, '')) = 'PASU_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM offices x
      WHERE x.parent_office_id = o.id
        AND UPPER(COALESCE(x.level, '')) = 'PAMO_UNIT'
        AND UPPER(COALESCE(x.name, '')) = UPPER(u.unit_name)
  );

-- 4) role_unit_mappings for PAMO internal chain
INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT
    r_admin.id,
    r_officer.id,
    o_officer.id,
    CONCAT(o_officer.name, ' (Admin -> Officer)')
FROM roles r_admin
JOIN roles r_officer ON UPPER(r_officer.name) = 'PASU_OFFICER'
JOIN offices o_officer ON UPPER(COALESCE(o_officer.level, '')) = 'PASU_OFFICER'
WHERE UPPER(r_admin.name) = 'PAMO_ADMIN'
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
    CONCAT(o_unit.name, ' (Officer -> Unit)')
FROM roles r_officer
JOIN roles r_unit ON UPPER(r_unit.name) = 'PAMO_UNIT'
JOIN offices o_unit ON UPPER(COALESCE(o_unit.level, '')) = 'PAMO_UNIT'
WHERE UPPER(r_officer.name) = 'PASU_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = r_officer.id
        AND rum.child_role_id = r_unit.id
        AND rum.office_id = o_unit.id
  );

-- 5) workflow_transitions for PAMO internal workflow
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PAMO Admin to PASU', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PASU_OFFICER'
WHERE UPPER(rf.name) = 'PAMO_ADMIN'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PASU to PAMO Unit', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PAMO_UNIT'
WHERE UPPER(rf.name) = 'PASU_OFFICER'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'REROUTE', rf.id, rt.id, 'PASU reroute to child PAMO Unit', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PAMO_UNIT'
WHERE UPPER(rf.name) = 'PASU_OFFICER'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'REROUTE'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PAMO Unit back to PASU', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PASU_OFFICER'
WHERE UPPER(rf.name) = 'PAMO_UNIT'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PASU back to PAMO Admin', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PAMO_ADMIN'
WHERE UPPER(rf.name) = 'PASU_OFFICER'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PAMO Admin to CENRO Admin Record', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'CENRO_ADMIN_RECORD'
WHERE UPPER(rf.name) = 'PAMO_ADMIN'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PAMO Admin to PENRO Admin Record', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PENRO_ADMIN_RECORD'
WHERE UPPER(rf.name) = 'PAMO_ADMIN'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'COMPLETE', rf.id, NULL, 'PAMO Admin can complete locally', 1
FROM roles rf
WHERE UPPER(rf.name) = 'PAMO_ADMIN'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'COMPLETE'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id IS NULL
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RECEIVE', NULL, r.id, CONCAT('Receive into custody (', r.name, ')'), 1
FROM roles r
WHERE UPPER(r.name) IN ('PAMO_ADMIN', 'PASU_OFFICER', 'PAMO_UNIT')
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'RECEIVE'
        AND wt.allowed_from_role_id IS NULL
        AND wt.allowed_to_role_id = r.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'APPROVE', r.id, NULL, CONCAT(r.name, ' can approve'), 1
FROM roles r
WHERE UPPER(r.name) IN ('PAMO_ADMIN', 'PASU_OFFICER', 'PAMO_UNIT')
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'APPROVE'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'PENDING', r.id, NULL, CONCAT(r.name, ' can mark pending'), 1
FROM roles r
WHERE UPPER(r.name) IN ('PAMO_ADMIN', 'PASU_OFFICER', 'PAMO_UNIT')
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'PENDING'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'SIGN', r.id, NULL, 'PASU can sign', 1
FROM roles r
WHERE UPPER(r.name) = 'PASU_OFFICER'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'SIGN'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

COMMIT;
