IF COL_LENGTH('documents', 'client_address') IS NULL
BEGIN
    ALTER TABLE documents
    ADD client_address NVARCHAR(255) NULL;
END;
