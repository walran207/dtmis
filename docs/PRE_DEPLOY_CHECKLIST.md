# Pre-Deployment Checklist

Project path: `C:\xampp\htdocs\edats`  
Last updated: `2026-04-05`

## 1) Run Automated Checks (Required)

From project root (`C:\xampp\htdocs\edats`), run:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\deploy\run-tests.ps1
powershell -ExecutionPolicy Bypass -File .\scripts\deploy\smoke-test.ps1 -BaseUrl "http://localhost/edats"
powershell -ExecutionPolicy Bypass -File .\scripts\deploy\predeploy.ps1 -BaseUrl "http://localhost/edats"
```

Expected: all scripts finish with `PASSED` and exit code `0`.

## 2) Manual Functional Checks (Required)

Use role accounts and confirm:

- Login, logout, and invalid-login lockout behavior
- Role access guard (cannot open pages from other roles)
- Create intake (internal and external)
- Forward, receive, return, pending, approve/sign/release flows
- Tracking slip and print package open correctly
- Document type request workflow (request, approve/reject, permission checks)
- Dashboard totals and search results match expected records

Reference detailed UAT cases: `docs/UAT_TEST_CHECKLIST.md`
Reference QA checklist: `docs/QA_TESTING_CHECKLIST.md`

## 3) Security and Data Safety (Required)

- Confirm CSRF protection on forms and action endpoints
- Confirm unauthenticated access to action endpoints is blocked
- Confirm file upload type/size validation still works
- Confirm no debug/test endpoints are publicly reachable
- Run DB backup and verify restore to a test database

## 4) API Security Hardening (Required)

- Enforce object-level authorization on API records (document/attachment ownership and role access)
- Restrict attachment file serving to approved storage paths only (for example: `storage/uploads`)
- Centralize API guard checks (auth, request method, CSRF, standard JSON error responses)
- Enforce HTTPS in production and secure session cookies (`Secure`, `HttpOnly`, `SameSite`)
- Add rate limiting per IP and per user on sensitive endpoints (login, OTP, workflow actions)
- Tighten CORS policy to trusted origins only (or same-origin only if no cross-origin use case)
- Monitor and alert on repeated `401`, `403`, and `429` responses

## 5) Deployment Readiness (Required)

- Production `.htaccess`/Apache config is in place
- DB migrations are applied and verified
- Required writable directories exist:
  - `storage`
  - `storage/uploads`
  - `storage/signatures`
- SMTP/external integrations are configured for production values
- Rollback plan is ready (previous code + last known-good DB backup)

## 6) Go/No-Go Sign-Off

- Tester sign-off: `PASS/FAIL`
- Product owner sign-off: `PASS/FAIL`
- Deployment owner sign-off: `PASS/FAIL`
- Planned deployment date/time:
- Rollback owner:

## 7) QA Checklist Completion (Required)

- QA checklist executed: `docs/QA_TESTING_CHECKLIST.md`
- All critical defects resolved or accepted with documented risk
- Final QA regression smoke completed after fixes
