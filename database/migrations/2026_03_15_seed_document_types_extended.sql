START TRANSACTION;

INSERT INTO document_types (
    name,
    category,
    arta_days_limit,
    indicator_color,
    is_custom,
    created_by_role_id,
    is_active
)
SELECT
    seed.name,
    seed.category,
    seed.arta_days_limit,
    seed.indicator_color,
    0,
    NULL,
    1
FROM (
    SELECT 'Memorandum' AS name, 'Simple' AS category, 3 AS arta_days_limit, 'Yellow' AS indicator_color
    UNION ALL SELECT 'Endorsement Letter', 'Simple', 3, 'Yellow'
    UNION ALL SELECT 'Certification Request', 'Simple', 3, 'Yellow'
    UNION ALL SELECT 'Records Request', 'Simple', 3, 'Yellow'
    UNION ALL SELECT 'Permit Application', 'Complex', 7, 'Pink'
    UNION ALL SELECT 'Permit Renewal', 'Complex', 7, 'Pink'
    UNION ALL SELECT 'Compliance Report', 'Complex', 7, 'Pink'
    UNION ALL SELECT 'Inspection Report', 'Complex', 7, 'Pink'
    UNION ALL SELECT 'Legal Opinion Request', 'Complex', 7, 'Pink'
    UNION ALL SELECT 'Notice of Violation', 'Complex', 7, 'Pink'
    UNION ALL SELECT 'Site Validation Report', 'Complex', 7, 'Pink'
    UNION ALL SELECT 'GIS Accomplishment Report', 'Complex', 7, 'Pink'
    UNION ALL SELECT 'ECC Application', 'Highly Technical', 20, 'Red'
    UNION ALL SELECT 'Foreshore Lease Evaluation', 'Highly Technical', 20, 'Red'
    UNION ALL SELECT 'Wildlife Special Permit', 'Highly Technical', 20, 'Red'
) AS seed
LEFT JOIN document_types dt
       ON LOWER(dt.name) = LOWER(seed.name)
WHERE dt.id IS NULL;

COMMIT;
