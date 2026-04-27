START TRANSACTION;

-- Update one canonical PENRO/CENRO account per office to readable office-based emails.
UPDATE users u
JOIN (
    SELECT MIN(u2.id) AS user_id
    FROM users u2
    JOIN roles r2 ON r2.id = u2.role_id
    JOIN offices o2 ON o2.id = u2.office_id
    WHERE
        (r2.name = 'PENRO' AND o2.name IN (
            'PENRO COTABATO',
            'PENRO SULTAN KUDARAT',
            'PENRO SOUTH COTABATO',
            'PENRO SARANGANI'
        ))
        OR
        (r2.name = 'CENRO' AND o2.name IN (
            'CENRO Midyap',
            'CENRO Matalam',
            'CENRO Tacurong City',
            'CENRO Kalamansig',
            'CENRO Banga',
            'CENRO General Santos City',
            'CENRO Kiamba',
            'CENRO Glan'
        ))
    GROUP BY r2.name, o2.name
) picked ON picked.user_id = u.id
JOIN roles r ON r.id = u.role_id
JOIN offices o ON o.id = u.office_id
SET u.email = CASE
    WHEN r.name = 'PENRO' AND o.name = 'PENRO COTABATO' THEN 'penro.cotabato@denr.gov.ph'
    WHEN r.name = 'PENRO' AND o.name = 'PENRO SULTAN KUDARAT' THEN 'penro.sultan.kudarat@denr.gov.ph'
    WHEN r.name = 'PENRO' AND o.name = 'PENRO SOUTH COTABATO' THEN 'penro.south.cotabato@denr.gov.ph'
    WHEN r.name = 'PENRO' AND o.name = 'PENRO SARANGANI' THEN 'penro.sarangani@denr.gov.ph'
    WHEN r.name = 'CENRO' AND o.name = 'CENRO Midyap' THEN 'cenro.midyap@denr.gov.ph'
    WHEN r.name = 'CENRO' AND o.name = 'CENRO Matalam' THEN 'cenro.matalam@denr.gov.ph'
    WHEN r.name = 'CENRO' AND o.name = 'CENRO Tacurong City' THEN 'cenro.tacurong.city@denr.gov.ph'
    WHEN r.name = 'CENRO' AND o.name = 'CENRO Kalamansig' THEN 'cenro.kalamansig@denr.gov.ph'
    WHEN r.name = 'CENRO' AND o.name = 'CENRO Banga' THEN 'cenro.banga@denr.gov.ph'
    WHEN r.name = 'CENRO' AND o.name = 'CENRO General Santos City' THEN 'cenro.general.santos.city@denr.gov.ph'
    WHEN r.name = 'CENRO' AND o.name = 'CENRO Kiamba' THEN 'cenro.kiamba@denr.gov.ph'
    WHEN r.name = 'CENRO' AND o.name = 'CENRO Glan' THEN 'cenro.glan@denr.gov.ph'
    ELSE u.email
END;

COMMIT;
