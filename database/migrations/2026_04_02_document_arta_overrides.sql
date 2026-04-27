-- Per-document ARTA overrides for intake complexity + custom days.
ALTER TABLE documents
    ADD COLUMN IF NOT EXISTS arta_category_override VARCHAR(50) NULL DEFAULT NULL AFTER document_type_id;

ALTER TABLE documents
    ADD COLUMN IF NOT EXISTS arta_days_limit_override INT(11) NULL DEFAULT NULL AFTER arta_category_override;

