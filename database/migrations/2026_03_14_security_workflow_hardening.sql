START TRANSACTION;

-- Security: login, MFA, and first-login reset controls.
ALTER TABLE users
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash,
    ADD COLUMN is_seeded_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER must_change_password,
    ADD COLUMN password_changed_at DATETIME NULL AFTER is_seeded_demo,
    ADD COLUMN failed_login_attempts INT NOT NULL DEFAULT 0 AFTER password_changed_at,
    ADD COLUMN locked_until DATETIME NULL AFTER failed_login_attempts,
    ADD COLUMN last_login_at DATETIME NULL AFTER locked_until,
    ADD COLUMN last_login_ip VARCHAR(45) NULL AFTER last_login_at,
    ADD COLUMN mfa_otp_code VARCHAR(6) NULL AFTER otp_expires_at,
    ADD COLUMN mfa_otp_expires_at DATETIME NULL AFTER mfa_otp_code,
    ADD COLUMN mfa_failed_attempts INT NOT NULL DEFAULT 0 AFTER mfa_otp_expires_at,
    ADD COLUMN mfa_locked_until DATETIME NULL AFTER mfa_failed_attempts,
    ADD COLUMN mfa_resend_count INT NOT NULL DEFAULT 0 AFTER mfa_locked_until,
    ADD COLUMN mfa_resend_window_started_at DATETIME NULL AFTER mfa_resend_count,
    ADD COLUMN mfa_last_sent_at DATETIME NULL AFTER mfa_resend_window_started_at;

CREATE INDEX idx_users_locked_until ON users(locked_until);
CREATE INDEX idx_users_mfa_locked_until ON users(mfa_locked_until);
CREATE INDEX idx_users_must_change_password ON users(must_change_password);

CREATE TABLE IF NOT EXISTS security_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    email VARCHAR(100) NULL,
    event_type VARCHAR(80) NOT NULL,
    event_status VARCHAR(30) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    remarks TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_security_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_security_audit_event_created ON security_audit_logs(event_type, created_at);
CREATE INDEX idx_security_audit_user_created ON security_audit_logs(user_id, created_at);
CREATE INDEX idx_security_audit_email_created ON security_audit_logs(email, created_at);

-- Mark shared-hash seeded/demo accounts for forced reset on first successful MFA verification.
UPDATE users
SET must_change_password = 1,
    is_seeded_demo = 1
WHERE password_hash IN (
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK'
);

-- Workflow transitions: table-driven permission map.
CREATE TABLE IF NOT EXISTS workflow_transitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(30) NOT NULL,
    allowed_from_role_id INT NULL,
    allowed_to_role_id INT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_workflow_transition (action_type, allowed_from_role_id, allowed_to_role_id),
    CONSTRAINT fk_workflow_trans_from_role FOREIGN KEY (allowed_from_role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_workflow_trans_to_role FOREIGN KEY (allowed_to_role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE INDEX idx_workflow_transition_action ON workflow_transitions(action_type, is_active);

-- Seed baseline transitions for DENR flow.
INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'Section Staff to Division Chief', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'SECTION_STAFF'
  AND rt.name = 'DIVISION_CHIEF'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'Division Chief to PACDO', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'DIVISION_CHIEF'
  AND rt.name = 'PACDO'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'CENRO to PENRO', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'CENRO'
  AND rt.name = 'PENRO'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PENRO to PACDO', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'PENRO'
  AND rt.name = 'PACDO'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PACDO to ORED', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'PACDO'
  AND rt.name = 'ORED'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'FORWARD', rf.id, rt.id, 'PENRO route down to CENRO', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'PENRO'
  AND rt.name = 'CENRO'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'FORWARD'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RETURN', rf.id, rt.id, 'PACDO return to PENRO', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'PACDO'
  AND rt.name = 'PENRO'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'RETURN'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RETURN', rf.id, rt.id, 'ORED return to PACDO', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'ORED'
  AND rt.name = 'PACDO'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'RETURN'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RETURN', rf.id, rt.id, 'Division Chief return to Section Staff', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'DIVISION_CHIEF'
  AND rt.name = 'SECTION_STAFF'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'RETURN'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'REROUTE', rf.id, rt.id, 'Division Chief ad-hoc reroute to staff', 1
FROM roles rf
JOIN roles rt
WHERE rf.name = 'DIVISION_CHIEF'
  AND rt.name = 'SECTION_STAFF'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'REROUTE'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id = rt.id
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'OVERRIDE', rf.id, NULL, 'ORED executive override', 1
FROM roles rf
WHERE rf.name = 'ORED'
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'OVERRIDE'
        AND wt.allowed_from_role_id = rf.id
        AND wt.allowed_to_role_id IS NULL
  );

INSERT INTO workflow_transitions (action_type, allowed_from_role_id, allowed_to_role_id, description, is_active)
SELECT 'RECEIVE', NULL, rt.id, 'Receive into custody', 1
FROM roles rt
WHERE rt.name IN ('ORED', 'PACDO', 'PENRO', 'CENRO', 'DIVISION_CHIEF', 'SECTION_STAFF')
  AND NOT EXISTS (
      SELECT 1 FROM workflow_transitions wt
      WHERE wt.action_type = 'RECEIVE'
        AND wt.allowed_from_role_id IS NULL
        AND wt.allowed_to_role_id = rt.id
  );

-- Query performance indexes for workflow and dashboard.
CREATE INDEX idx_documents_scope_status_created ON documents(current_office_id, pending_office_id, status, created_at);
CREATE INDEX idx_activity_logs_doc_action_created ON activity_logs(document_id, action_type, created_at);
CREATE INDEX idx_tracking_slips_doc_receive_office ON tracking_slips(document_id, receiving_office_id, date_time_received);

COMMIT;
