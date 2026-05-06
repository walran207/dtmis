-- Add missing PENRO Officer -> PENRO Admin Record forward transition.

START TRANSACTION;

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PENRO Officer back to originating PENRO Admin Record', 1
FROM roles rf
JOIN roles rt ON UPPER(rt.name) = 'PENRO_ADMIN_RECORD'
WHERE UPPER(rf.name) = 'PENRO_OFFICER'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE UPPER(wt.action_type) = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

COMMIT;
