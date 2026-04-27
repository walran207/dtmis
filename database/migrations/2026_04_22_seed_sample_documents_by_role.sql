-- Reusable sample-data script:
-- Inserts N documents for a chosen role (default: 5 rows).
--
-- How to use:
-- 1) Set @target_role to any role name in `roles.name`
--    e.g. ORED, ARD_TS, ARD_MS, DIVISION_CHIEF, SECTION_STAFF, PACDO, PENRO, CENRO
-- 2) Set @rows (1..5 recommended)
-- 3) Run this script in phpMyAdmin or MySQL client.

SET @target_role := 'ARD_MS';
SET @rows := 5;

-- Pick one active user for the selected role.
SELECT u.id, u.office_id
INTO @actor_user_id, @actor_office_id
FROM users u
JOIN roles r ON r.id = u.role_id
WHERE r.name = @target_role
  AND u.is_active = 1
ORDER BY u.id
LIMIT 1;

-- Pick an active document type.
SELECT dt.id
INTO @doc_type_id
FROM document_types dt
WHERE dt.is_active = 1
ORDER BY dt.id
LIMIT 1;

-- Compute tracking-id sequence base for current year.
SET @yr := YEAR(CURDATE());

SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(d.tracking_id, '-', -1) AS UNSIGNED)), 0)
INTO @base_seq
FROM documents d
WHERE d.tracking_id LIKE CONCAT('DENR-XII-', @yr, '-%');

-- Insert up to 5 sample documents.
INSERT INTO documents (
  tracking_id,
  subject,
  document_type_id,
  originating_office_id,
  current_office_id,
  status,
  source_type,
  created_by_user_id,
  current_holder_user_id,
  row_version,
  qr_print_mode,
  created_at
)
SELECT
  CONCAT('DENR-XII-', @yr, '-', LPAD(@base_seq + n.n, 4, '0')) AS tracking_id,
  CONCAT('AUTO TEST - ', @target_role, ' - Doc #', n.n) AS subject,
  @doc_type_id AS document_type_id,
  @actor_office_id AS originating_office_id,
  @actor_office_id AS current_office_id,
  'Created' AS status,
  'INTERNAL' AS source_type,
  @actor_user_id AS created_by_user_id,
  @actor_user_id AS current_holder_user_id,
  1 AS row_version,
  'Grid-Snap' AS qr_print_mode,
  NOW() AS created_at
FROM (
  SELECT 1 AS n
  UNION ALL SELECT 2
  UNION ALL SELECT 3
  UNION ALL SELECT 4
  UNION ALL SELECT 5
) n
WHERE n.n <= @rows
  AND @actor_user_id IS NOT NULL
  AND @actor_office_id IS NOT NULL
  AND @doc_type_id IS NOT NULL;

-- Verify inserted rows from this run.
SELECT
  id,
  tracking_id,
  subject,
  status,
  current_office_id,
  created_by_user_id,
  created_at
FROM documents
WHERE tracking_id LIKE CONCAT('DENR-XII-', @yr, '-%')
  AND CAST(SUBSTRING_INDEX(tracking_id, '-', -1) AS UNSIGNED) > @base_seq
ORDER BY id;
