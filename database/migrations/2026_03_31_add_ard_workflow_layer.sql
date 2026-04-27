START TRANSACTION;

-- 1) Add ARD roles (idempotent).
INSERT INTO roles (name, description)
SELECT 'ARD_TS', 'Assistant Regional Director for Technical Services'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'ARD_TS'
);

INSERT INTO roles (name, description)
SELECT 'ARD_MS', 'Assistant Regional Director for Management Services'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE UPPER(name) = 'ARD_MS'
);

-- 2) Add ARD offices under ORED (idempotent).
INSERT INTO offices (name, level, parent_office_id)
SELECT 'Assistant Regional Director for Technical Services (ARD TS)', 'Regional', ored.id
FROM offices ored
WHERE (UPPER(ored.name) LIKE '%ORED%' OR UPPER(ored.name) LIKE '%REGIONAL EXECUTIVE%')
  AND NOT EXISTS (
      SELECT 1
      FROM offices o
      WHERE UPPER(o.name) LIKE '%ARD TS%'
         OR (UPPER(o.name) LIKE '%ASSISTANT REGIONAL DIRECTOR%' AND UPPER(o.name) LIKE '%TECHNICAL%')
  )
LIMIT 1;

INSERT INTO offices (name, level, parent_office_id)
SELECT 'Assistant Regional Director for Management Services (ARD MS)', 'Regional', ored.id
FROM offices ored
WHERE (UPPER(ored.name) LIKE '%ORED%' OR UPPER(ored.name) LIKE '%REGIONAL EXECUTIVE%')
  AND NOT EXISTS (
      SELECT 1
      FROM offices o
      WHERE UPPER(o.name) LIKE '%ARD MS%'
         OR (UPPER(o.name) LIKE '%ASSISTANT REGIONAL DIRECTOR%' AND UPPER(o.name) LIKE '%MANAGEMENT%')
  )
LIMIT 1;

-- 3) Re-parent divisions to ARD tracks.
SET @ard_ts_office_id = (
    SELECT id
    FROM offices
    WHERE UPPER(name) LIKE '%ARD TS%'
       OR (UPPER(name) LIKE '%ASSISTANT REGIONAL DIRECTOR%' AND UPPER(name) LIKE '%TECHNICAL%')
    ORDER BY id ASC
    LIMIT 1
);

SET @ard_ms_office_id = (
    SELECT id
    FROM offices
    WHERE UPPER(name) LIKE '%ARD MS%'
       OR (UPPER(name) LIKE '%ASSISTANT REGIONAL DIRECTOR%' AND UPPER(name) LIKE '%MANAGEMENT%')
    ORDER BY id ASC
    LIMIT 1
);

UPDATE offices
SET parent_office_id = @ard_ts_office_id
WHERE @ard_ts_office_id IS NOT NULL
  AND UPPER(COALESCE(level, '')) = 'DIVISION'
  AND name IN (
      'Licenses, Patents & Deeds Division',
      'Surveys & Mapping Division',
      'Conservation & Devt. Division',
      'Enforcement Division'
  );

UPDATE offices
SET parent_office_id = @ard_ms_office_id
WHERE @ard_ms_office_id IS NOT NULL
  AND UPPER(COALESCE(level, '')) = 'DIVISION'
  AND name IN (
      'Legal Division',
      'Planning and Mgt. Division',
      'Planning and Management Division (PMD)',
      'Administrative Division',
      'Finance Division'
  );

-- 4) Optional ARD->Division supervision mapping for admin visibility.
INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT parent_role.id,
       child_role.id,
       division_office.id,
       CONCAT(parent_role.name, ' -> ', division_office.name)
FROM roles parent_role
JOIN roles child_role
JOIN offices division_office
WHERE UPPER(parent_role.name) = 'ARD_TS'
  AND UPPER(child_role.name) = 'DIVISION_CHIEF'
  AND UPPER(COALESCE(division_office.level, '')) = 'DIVISION'
  AND division_office.name IN (
      'Licenses, Patents & Deeds Division',
      'Surveys & Mapping Division',
      'Conservation & Devt. Division',
      'Enforcement Division'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = parent_role.id
        AND rum.child_role_id = child_role.id
        AND rum.office_id = division_office.id
  );

INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT parent_role.id,
       child_role.id,
       division_office.id,
       CONCAT(parent_role.name, ' -> ', division_office.name)
FROM roles parent_role
JOIN roles child_role
JOIN offices division_office
WHERE UPPER(parent_role.name) = 'ARD_MS'
  AND UPPER(child_role.name) = 'DIVISION_CHIEF'
  AND UPPER(COALESCE(division_office.level, '')) = 'DIVISION'
  AND division_office.name IN (
      'Legal Division',
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
        AND rum.office_id = division_office.id
  );

-- 5) Receive transitions for ARD roles.
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RECEIVE', NULL, r.id, CONCAT('Receive into custody (', r.name, ')'), 1
FROM roles r
WHERE UPPER(r.name) IN ('ARD_TS', 'ARD_MS')
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE wt.action_type = 'RECEIVE'
        AND wt.allowed_from_role_id IS NULL
        AND wt.allowed_to_role_id = r.id
  );

-- 6) Decision/status transitions for ARD roles.
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'APPROVE', r.id, NULL, CONCAT(r.name, ' can approve'), 1
FROM roles r
WHERE UPPER(r.name) IN ('ARD_TS', 'ARD_MS')
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE wt.action_type = 'APPROVE'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'PENDING', r.id, NULL, CONCAT(r.name, ' can mark pending'), 1
FROM roles r
WHERE UPPER(r.name) IN ('ARD_TS', 'ARD_MS')
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE wt.action_type = 'PENDING'
        AND wt.allowed_from_role_id = r.id
        AND wt.allowed_to_role_id IS NULL
  );

-- 7) Forward transitions for ORED <-> ARD <-> Division.
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'ORED assign to ARD TS', 1
FROM roles rf
JOIN roles rt
WHERE UPPER(rf.name) = 'ORED'
  AND UPPER(rt.name) = 'ARD_TS'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'ORED assign to ARD MS', 1
FROM roles rf
JOIN roles rt
WHERE UPPER(rf.name) = 'ORED'
  AND UPPER(rt.name) = 'ARD_MS'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'ARD TS assign to Division Chief', 1
FROM roles rf
JOIN roles rt
WHERE UPPER(rf.name) = 'ARD_TS'
  AND UPPER(rt.name) = 'DIVISION_CHIEF'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'ARD MS assign to Division Chief', 1
FROM roles rf
JOIN roles rt
WHERE UPPER(rf.name) = 'ARD_MS'
  AND UPPER(rt.name) = 'DIVISION_CHIEF'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'Division Chief return to ARD TS', 1
FROM roles rf
JOIN roles rt
WHERE UPPER(rf.name) = 'DIVISION_CHIEF'
  AND UPPER(rt.name) = 'ARD_TS'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'Division Chief return to ARD MS', 1
FROM roles rf
JOIN roles rt
WHERE UPPER(rf.name) = 'DIVISION_CHIEF'
  AND UPPER(rt.name) = 'ARD_MS'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'ARD TS elevate to ORED', 1
FROM roles rf
JOIN roles rt
WHERE UPPER(rf.name) = 'ARD_TS'
  AND UPPER(rt.name) = 'ORED'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'ARD MS elevate to ORED', 1
FROM roles rf
JOIN roles rt
WHERE UPPER(rf.name) = 'ARD_MS'
  AND UPPER(rt.name) = 'ORED'
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

COMMIT;
