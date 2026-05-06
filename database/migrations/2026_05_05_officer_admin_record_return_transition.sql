START TRANSACTION;

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RETURN', rf.id, rt.id, 'PENRO Officer return to originating PENRO Admin Record', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PENRO_ADMIN_RECORD'
WHERE UPPER(rf.name) = 'PENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'RETURN'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RETURN', rf.id, rt.id, 'CENRO Officer return to originating CENRO Admin Record', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'CENRO_ADMIN_RECORD'
WHERE UPPER(rf.name) = 'CENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'RETURN'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RETURN', rf.id, rt.id, 'PASU return to originating PAMO Admin', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PAMO_ADMIN'
WHERE UPPER(rf.name) = 'PASU_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'RETURN'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

COMMIT;
