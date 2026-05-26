SET NOCOUNT ON;
SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    /*
        SQL Server-safe fresh data reset for DTMIS / eDATS.
        Preserves:
        - users
        - roles
        - offices
        - document_types
        - workflow mappings / transitions / other master setup

        Clears:
        - live workflow documents and their attachments
        - tracking / activity history
        - recycle bin archives
        - binary attachment backups
        - document type requests
        - security / offline / idempotency / notification logs

        Note:
        - This resets database rows only.
        - Attachment files stored on disk should be cleaned separately if needed.
    */

    IF OBJECT_ID(N'dbo.document_attachment_binary_backups', N'U') IS NOT NULL
        DELETE FROM dbo.document_attachment_binary_backups;

    IF OBJECT_ID(N'dbo.deleted_documents_archive', N'U') IS NOT NULL
        DELETE FROM dbo.deleted_documents_archive;

    IF OBJECT_ID(N'dbo.deleted_users_archive', N'U') IS NOT NULL
        DELETE FROM dbo.deleted_users_archive;

    IF OBJECT_ID(N'dbo.activity_logs', N'U') IS NOT NULL
        DELETE FROM dbo.activity_logs;

    IF OBJECT_ID(N'dbo.tracking_slips', N'U') IS NOT NULL
        DELETE FROM dbo.tracking_slips;

    IF OBJECT_ID(N'dbo.document_attachments', N'U') IS NOT NULL
        DELETE FROM dbo.document_attachments;

    IF OBJECT_ID(N'dbo.documents', N'U') IS NOT NULL
        DELETE FROM dbo.documents;

    IF OBJECT_ID(N'dbo.document_type_requests', N'U') IS NOT NULL
        DELETE FROM dbo.document_type_requests;

    IF OBJECT_ID(N'dbo.security_audit_logs', N'U') IS NOT NULL
        DELETE FROM dbo.security_audit_logs;

    IF OBJECT_ID(N'dbo.offline_sync_logs', N'U') IS NOT NULL
        DELETE FROM dbo.offline_sync_logs;

    IF OBJECT_ID(N'dbo.api_idempotency_operations', N'U') IS NOT NULL
        DELETE FROM dbo.api_idempotency_operations;

    IF OBJECT_ID(N'dbo.email_notification_logs', N'U') IS NOT NULL
        DELETE FROM dbo.email_notification_logs;

    DECLARE @identityTables TABLE (table_name SYSNAME NOT NULL);

    INSERT INTO @identityTables (table_name)
    VALUES
        (N'activity_logs'),
        (N'tracking_slips'),
        (N'document_attachments'),
        (N'documents'),
        (N'document_type_requests'),
        (N'security_audit_logs'),
        (N'offline_sync_logs'),
        (N'api_idempotency_operations'),
        (N'email_notification_logs'),
        (N'deleted_documents_archive'),
        (N'deleted_users_archive');

    DECLARE @tableName SYSNAME;
    DECLARE @resetSql NVARCHAR(MAX);

    DECLARE identity_cursor CURSOR LOCAL FAST_FORWARD FOR
        SELECT it.table_name
        FROM @identityTables it
        WHERE OBJECT_ID(N'dbo.' + it.table_name, N'U') IS NOT NULL
          AND EXISTS (
                SELECT 1
                FROM sys.identity_columns ic
                INNER JOIN sys.objects so ON so.object_id = ic.object_id
                WHERE so.name = it.table_name
            );

    OPEN identity_cursor;
    FETCH NEXT FROM identity_cursor INTO @tableName;

    WHILE @@FETCH_STATUS = 0
    BEGIN
        SET @resetSql = N'DBCC CHECKIDENT (''dbo.' + REPLACE(@tableName, '''', '''''') + ''', RESEED, 0);';
        EXEC sp_executesql @resetSql;
        FETCH NEXT FROM identity_cursor INTO @tableName;
    END;

    CLOSE identity_cursor;
    DEALLOCATE identity_cursor;

    IF OBJECT_ID(N'dbo.users', N'U') IS NOT NULL
    BEGIN
        UPDATE dbo.users
        SET
            failed_login_attempts = 0,
            locked_until = NULL,
            otp_code = NULL,
            otp_expires_at = NULL,
            mfa_otp_code = NULL,
            mfa_otp_expires_at = NULL,
            mfa_failed_attempts = 0,
            mfa_locked_until = NULL,
            mfa_resend_count = 0,
            mfa_resend_window_started_at = NULL,
            mfa_last_sent_at = NULL;
    END;

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;

    THROW;
END CATCH;
