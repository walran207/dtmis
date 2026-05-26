IF COL_LENGTH('documents', 'sender') IS NULL
BEGIN
    ALTER TABLE documents
    ADD sender NVARCHAR(255) NULL;
END;
