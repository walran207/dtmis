START TRANSACTION;

CREATE TABLE IF NOT EXISTS document_type_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requested_name VARCHAR(100) NOT NULL,
    requested_category VARCHAR(50) NOT NULL,
    requested_days INT NOT NULL,
    requested_color VARCHAR(20) NOT NULL,
    justification TEXT NULL,
    requested_by_user_id INT NOT NULL,
    requested_by_office_id INT NOT NULL,
    status ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
    reviewed_by_user_id INT NULL,
    reviewed_at DATETIME NULL,
    review_remarks TEXT NULL,
    linked_document_type_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dtr_requested_by_user FOREIGN KEY (requested_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_dtr_requested_by_office FOREIGN KEY (requested_by_office_id) REFERENCES offices(id) ON DELETE CASCADE,
    CONSTRAINT fk_dtr_reviewed_by_user FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_dtr_linked_document_type FOREIGN KEY (linked_document_type_id) REFERENCES document_types(id) ON DELETE SET NULL
);

CREATE INDEX idx_dtr_status_created ON document_type_requests(status, created_at);
CREATE INDEX idx_dtr_requester_status ON document_type_requests(requested_by_user_id, status, created_at);
CREATE INDEX idx_dtr_office_status ON document_type_requests(requested_by_office_id, status, created_at);

COMMIT;
