START TRANSACTION;

-- Module 1: parent-child role mapping for routing logic
CREATE TABLE IF NOT EXISTS role_unit_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_role_id INT NOT NULL,
    child_role_id INT NOT NULL,
    office_id INT NULL,
    unit_name VARCHAR(150) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_unit_mapping (parent_role_id, child_role_id, office_id),
    CONSTRAINT fk_role_unit_parent FOREIGN KEY (parent_role_id) REFERENCES roles(id),
    CONSTRAINT fk_role_unit_child FOREIGN KEY (child_role_id) REFERENCES roles(id),
    CONSTRAINT fk_role_unit_office FOREIGN KEY (office_id) REFERENCES offices(id)
);

-- Module 2 + 3: intake metadata + custody vs action routing state
ALTER TABLE documents
    ADD COLUMN source_type ENUM('INTERNAL', 'EXTERNAL') NOT NULL DEFAULT 'INTERNAL' AFTER status,
    ADD COLUMN external_client_name VARCHAR(255) NULL AFTER source_type,
    ADD COLUMN created_by_user_id INT NULL AFTER external_client_name,
    ADD COLUMN current_holder_user_id INT NULL AFTER created_by_user_id,
    ADD COLUMN pending_office_id INT NULL AFTER current_holder_user_id,
    ADD COLUMN pending_user_id INT NULL AFTER pending_office_id;

ALTER TABLE documents
    ADD CONSTRAINT fk_documents_created_by_user FOREIGN KEY (created_by_user_id) REFERENCES users(id),
    ADD CONSTRAINT fk_documents_current_holder_user FOREIGN KEY (current_holder_user_id) REFERENCES users(id),
    ADD CONSTRAINT fk_documents_pending_office FOREIGN KEY (pending_office_id) REFERENCES offices(id),
    ADD CONSTRAINT fk_documents_pending_user FOREIGN KEY (pending_user_id) REFERENCES users(id);

CREATE INDEX idx_documents_source_type ON documents(source_type);
CREATE INDEX idx_documents_pending_office ON documents(pending_office_id);
CREATE INDEX idx_documents_status_created ON documents(status, created_at);

-- Module 2 (PACDO custom document types)
ALTER TABLE document_types
    ADD COLUMN is_custom TINYINT(1) NOT NULL DEFAULT 0 AFTER indicator_color,
    ADD COLUMN created_by_role_id INT NULL AFTER is_custom,
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER created_by_role_id;

ALTER TABLE document_types
    ADD CONSTRAINT fk_document_types_created_by_role FOREIGN KEY (created_by_role_id) REFERENCES roles(id);

-- Module 4 + 5: strict custody/action split
ALTER TABLE activity_logs
    ADD COLUMN action_scope ENUM('CUSTODY', 'ACTION') NOT NULL DEFAULT 'ACTION' AFTER action_type,
    ADD COLUMN destination_user_id INT NULL AFTER destination_office_id,
    ADD COLUMN is_visible_on_slip TINYINT(1) NOT NULL DEFAULT 0 AFTER remarks;

ALTER TABLE activity_logs
    ADD CONSTRAINT fk_activity_logs_destination_user FOREIGN KEY (destination_user_id) REFERENCES users(id);

CREATE INDEX idx_activity_scope_created ON activity_logs(action_scope, created_at);
CREATE INDEX idx_activity_doc_visible ON activity_logs(document_id, is_visible_on_slip);

ALTER TABLE tracking_slips
    ADD COLUMN receive_method ENUM('AUTO_OPEN', 'MANUAL') NOT NULL DEFAULT 'MANUAL' AFTER action_required;

-- Module 1 + 2 seed alignment
UPDATE roles
SET name = 'DIVISION_CHIEF',
    description = 'Division Chief / Section Chief supervisor'
WHERE name = 'CHIEF';

INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT p.id, c.id, 3, 'PMD Section Supervision'
FROM roles p
JOIN roles c
WHERE p.name = 'DIVISION_CHIEF'
  AND c.name = 'SECTION_STAFF'
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = p.id
        AND rum.child_role_id = c.id
        AND rum.office_id = 3
  );

INSERT INTO role_unit_mappings (parent_role_id, child_role_id, office_id, unit_name)
SELECT p.id, c.id, 4, 'Provincial to Community Chain'
FROM roles p
JOIN roles c
WHERE p.name = 'PENRO'
  AND c.name = 'CENRO'
  AND NOT EXISTS (
      SELECT 1
      FROM role_unit_mappings rum
      WHERE rum.parent_role_id = p.id
        AND rum.child_role_id = c.id
        AND rum.office_id = 4
  );

INSERT INTO offices (name, level, parent_office_id)
SELECT 'PASU - Mt. Matutum Protected Landscape', 'Protected Area', 4
WHERE NOT EXISTS (
    SELECT 1 FROM offices WHERE name = 'PASU - Mt. Matutum Protected Landscape'
);

INSERT INTO document_types (name, category, arta_days_limit, indicator_color, is_custom, created_by_role_id, is_active)
SELECT
    'PACDO Custom Client Request',
    'Simple',
    3,
    'Yellow',
    1,
    r.id,
    1
FROM roles r
WHERE r.name = 'PACDO'
  AND NOT EXISTS (
      SELECT 1
      FROM document_types dt
      WHERE dt.name = 'PACDO Custom Client Request'
  );

COMMIT;
