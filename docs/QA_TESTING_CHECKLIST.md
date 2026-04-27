# QA Testing Checklist

Project path: `C:\xampp\htdocs\edats`  
Last updated: `2026-04-05`

## Test Execution Rules

- Use a staging environment that matches production configuration
- Log each test case result as `PASS` or `FAIL`
- Attach failure evidence (error text + screenshot + timestamp)
- Re-test every fixed defect before sign-off

## A) Build and Environment Validation

- Application loads at `http://localhost/edats/`
- No PHP parse/runtime errors in Apache/PHP logs
- Database connection works for all core modules
- Required writable directories are available:
  - `storage`
  - `storage/uploads`
  - `storage/signatures`

## B) Core Functional QA

- Authentication:
  - login success
  - invalid login error
  - lockout after repeated failed attempts
  - logout and session invalidation
- Role and access control:
  - role dashboard access is restricted correctly
  - cross-role page access is blocked
- Document lifecycle:
  - create intake (internal/external)
  - forward/receive/return/pending
  - approve/sign/release (authorized roles only)
- Tracking and output:
  - tracking slip loads by tracking ID
  - print package renders correctly

## C) API and Security QA

- Unauthenticated API requests return `401`
- Wrong HTTP method returns `405`
- Invalid CSRF token returns rejection (`419` in current implementation)
- API record access enforces ownership/role constraints (no IDOR)
- Attachment access is limited to authorized users only
- Upload validation blocks disallowed file types and oversized files

## D) UI and Compatibility QA

- Desktop browsers: Chrome, Edge, Firefox
- Mobile/tablet layout check for critical screens
- Form validation and error messages are clear
- No broken navigation links or missing assets

## E) Performance and Reliability QA

- Dashboard and queue pages load within acceptable time
- Notification/feed endpoints respond consistently
- Repeated API calls do not produce duplicate actions
- Long-running pages remain usable without crashes

## F) Data Integrity QA

- Activity logs are created for workflow actions
- Security audit logs are written for login events
- Document status and routing history remain consistent
- Attachment versioning increments correctly

## G) Regression QA

- Re-test all previously fixed defects
- Re-test impacted modules after each bug fix deployment
- Run smoke test after every hotfix

## H) Pre-Release Sign-Off

- QA Lead sign-off: `PASS/FAIL`
- UAT representative sign-off: `PASS/FAIL`
- Product owner sign-off: `PASS/FAIL`
- Release date and build tag:
- Known issues accepted for release:

## QA Result Log Template

| TC ID | Module | Scenario | Tester | Date | Result (PASS/FAIL) | Evidence/Notes |
|---|---|---|---|---|---|---|
| QA-001 | Auth | Login success |  |  |  |  |
