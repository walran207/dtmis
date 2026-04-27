START TRANSACTION;

CREATE TABLE IF NOT EXISTS api_idempotency_operations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_key VARCHAR(64) NOT NULL,
    operation_id VARCHAR(80) NOT NULL,
    request_hash CHAR(64) NOT NULL,
    status ENUM('PENDING','COMPLETED','FAILED') NOT NULL DEFAULT 'PENDING',
    document_id INT NULL,
    tracking_id VARCHAR(64) NULL,
    attachment_count INT NULL,
    response_code SMALLINT UNSIGNED NULL,
    response_json MEDIUMTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_idempotency_operation (user_id, action_key, operation_id),
    KEY idx_idempotency_status_updated (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
