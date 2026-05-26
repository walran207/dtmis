IF COL_LENGTH('dbo.users', 'username') IS NULL
BEGIN
    ALTER TABLE dbo.users
    ADD username NVARCHAR(50) NULL;
END;
GO

;WITH source_rows AS (
    SELECT
        u.id,
        LOWER(
            CASE
                WHEN CHARINDEX('@', COALESCE(u.email, '') + '@') > 1
                    THEN LEFT(u.email, CHARINDEX('@', u.email + '@') - 1)
                WHEN COALESCE(u.email, '') <> ''
                    THEN u.email
                ELSE 'user' + CAST(u.id AS VARCHAR(12))
            END
        ) AS raw_username
    FROM dbo.users u
),
normalized_rows AS (
    SELECT
        s.id,
        CASE
            WHEN LTRIM(RTRIM(
                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                    s.raw_username,
                    ' ', '.'
                ), '+', '.'),
                    '/', '.'
                ), '\', '.'),
                    '@', '.'
                ), ':', '.'),
                    ';', '.'),
                    ',', '.'),
                    '(', '.'),
                    ')', '.')
            )) = ''
                THEN 'user' + CAST(s.id AS VARCHAR(12))
            ELSE LTRIM(RTRIM(
                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                    s.raw_username,
                    ' ', '.'
                ), '+', '.'),
                    '/', '.'
                ), '\', '.'),
                    '@', '.'
                ), ':', '.'),
                    ';', '.'),
                    ',', '.'),
                    '(', '.'),
                    ')', '.')
            ))
        END AS base_username
    FROM source_rows s
),
deduped_rows AS (
    SELECT
        n.id,
        n.base_username,
        ROW_NUMBER() OVER (PARTITION BY n.base_username ORDER BY n.id ASC) AS duplicate_rank
    FROM normalized_rows n
),
final_rows AS (
    SELECT
        d.id,
        LOWER(
            CASE
                WHEN d.duplicate_rank = 1
                    THEN LEFT(d.base_username, 50)
                ELSE LEFT(d.base_username, 50 - LEN('.' + CAST(d.id AS VARCHAR(12)))) + '.' + CAST(d.id AS VARCHAR(12))
            END
        ) AS resolved_username
    FROM deduped_rows d
)
UPDATE u
SET u.username = f.resolved_username
FROM dbo.users u
INNER JOIN final_rows f
    ON f.id = u.id
WHERE u.username IS NULL
   OR LTRIM(RTRIM(u.username)) = '';
GO

IF EXISTS (
    SELECT 1
    FROM sys.columns
    WHERE object_id = OBJECT_ID('dbo.users')
      AND name = 'username'
      AND is_nullable = 1
)
BEGIN
    ALTER TABLE dbo.users
    ALTER COLUMN username NVARCHAR(50) NOT NULL;
END;
GO

IF EXISTS (
    SELECT 1
    FROM sys.key_constraints
    WHERE parent_object_id = OBJECT_ID('dbo.users')
      AND name = 'UQ_users_email'
)
BEGIN
    ALTER TABLE dbo.users
    DROP CONSTRAINT UQ_users_email;
END;
GO

IF EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID('dbo.users')
      AND name = 'UQ_users_email'
)
BEGIN
    DROP INDEX UQ_users_email ON dbo.users;
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID('dbo.users')
      AND name = 'UQ_users_username'
)
BEGIN
    CREATE UNIQUE INDEX UQ_users_username
        ON dbo.users(username);
END;
GO
