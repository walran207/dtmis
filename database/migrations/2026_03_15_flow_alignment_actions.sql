START TRANSACTION;

-- Additional forward transitions to match the approved flow map.
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'ORED assign to Division Chief', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'ORED'
  AND rt.name = 'DIVISION_CHIEF'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'Division Chief escalate to ORED', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'DIVISION_CHIEF'
  AND rt.name = 'ORED'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'ORED route signed item to PACDO', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'ORED'
  AND rt.name = 'PACDO'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'Division Chief assign to Section Staff', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'DIVISION_CHIEF'
  AND rt.name = 'SECTION_STAFF'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

-- Additional return transitions for "back to sender" scenarios.
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RETURN', rf.id, rt.id, 'PENRO return to CENRO', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'PENRO'
  AND rt.name = 'CENRO'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'RETURN'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RETURN', rf.id, rt.id, 'PENRO return to PASU', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'PENRO'
  AND rt.name = 'PASU'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'RETURN'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RETURN', rf.id, rt.id, 'PACDO return directly to CENRO', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'PACDO'
  AND rt.name = 'CENRO'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'RETURN'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RETURN', rf.id, rt.id, 'PACDO return directly to PASU', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'PACDO'
  AND rt.name = 'PASU'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'RETURN'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

-- New decision/status action policies.
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'APPROVE', r.id, NULL, CONCAT(r.name, ' can approve'), 1
FROM roles r
WHERE r.name IN ('PENRO', 'PACDO', 'ORED', 'DIVISION_CHIEF')
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'APPROVE'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'SIGN', r.id, NULL, 'ORED can sign', 1
FROM roles r
WHERE r.name = 'ORED'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'SIGN'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'PENDING', r.id, NULL, CONCAT(r.name, ' can mark pending'), 1
FROM roles r
WHERE r.name IN ('ORED', 'PACDO', 'PENRO', 'CENRO', 'PASU', 'DIVISION_CHIEF', 'SECTION_STAFF')
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'PENDING'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

-- Release handoff from PACDO back to field/provincial offices.
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RELEASE', rf.id, rt.id, 'PACDO release to PENRO', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'PACDO'
  AND rt.name = 'PENRO'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'RELEASE'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RELEASE', rf.id, rt.id, 'PACDO release to CENRO', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'PACDO'
  AND rt.name = 'CENRO'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'RELEASE'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RELEASE', rf.id, rt.id, 'PACDO release to PASU', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'PACDO'
  AND rt.name = 'PASU'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'RELEASE'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

COMMIT;
