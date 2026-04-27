START TRANSACTION;

-- Add SUPER_ADMIN role (idempotent).
INSERT INTO roles (name, description)
SELECT 'SUPER_ADMIN', 'System Super Administrator (global user/data/analytics/network/theme control)'
WHERE NOT EXISTS (
    SELECT 1
    FROM roles
    WHERE UPPER(name) = 'SUPER_ADMIN'
);

COMMIT;
