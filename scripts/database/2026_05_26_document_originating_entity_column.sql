IF COL_LENGTH('documents', 'originating_entity_name') IS NULL
BEGIN
    ALTER TABLE documents
    ADD originating_entity_name NVARCHAR(255) NULL;
END;
