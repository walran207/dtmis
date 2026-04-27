START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

-- Clear transactional/test activity while preserving master/setup data.
DELETE FROM activity_logs;
DELETE FROM tracking_slips;
DELETE FROM document_attachments;
DELETE FROM documents;
DELETE FROM document_type_requests;
DELETE FROM security_audit_logs;

ALTER TABLE activity_logs AUTO_INCREMENT = 1;
ALTER TABLE tracking_slips AUTO_INCREMENT = 1;
ALTER TABLE document_attachments AUTO_INCREMENT = 1;
ALTER TABLE documents AUTO_INCREMENT = 1;
ALTER TABLE document_type_requests AUTO_INCREMENT = 1;
ALTER TABLE security_audit_logs AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- Keep all users, roles, offices, document types, mappings, and transitions.
-- Reset transient auth state so existing accounts can log in cleanly.
UPDATE users
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

COMMIT;
