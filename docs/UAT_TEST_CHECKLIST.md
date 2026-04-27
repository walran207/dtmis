# eDATS UAT Test Checklist

Project: `C:\xampp\htdocs\Edats`  
Prepared on: `2026-03-15`  
Purpose: User Acceptance Testing checklist for core and role-based features.

## How To Use This Checklist
1. Execute each test case in order.
2. Mark `PASS` or `FAIL` in the result column.
3. If failed, record the exact error and screenshot in the notes column.

## Test Data Setup
1. Ensure database migrations are applied, especially:
   - `database/migrations/2026_03_14_v1_feature_foundation.sql`
   - `database/migrations/2026_03_14_security_workflow_hardening.sql`
   - `database/migrations/2026_03_15_document_type_requests_module.sql`
2. Ensure seeded users exist for these roles:
   - ORED, PACDO, PENRO, CENRO, PASU, DIVISION-CHIEF, SECTION-STAFF
3. Ensure Apache + PHP + MySQL are running in XAMPP.
4. Base URL must be reachable at `http://localhost/edats/`.

## Result Log Template
| TC ID | Feature | Tester | Date | Result (PASS/FAIL) | Notes |
|---|---|---|---|---|---|
| TC-001 | Example |  |  |  |  |

## A. Entry, Routing, and Access
| TC ID | Feature | Preconditions | Steps | Expected Result | Result | Notes |
|---|---|---|---|---|---|---|
| TC-001 | Root URL redirect | App running | 1. Open `http://localhost/edats/` | Redirects to `http://localhost/edats/auth/login.php` |  |  |
| TC-002 | Dashboard direct access while logged out | Logged out | 1. Open `http://localhost/edats/dashboard.php` | Redirects to login |  |  |
| TC-003 | Role page guard | Logged in as CENRO | 1. Open PENRO dashboard URL directly | Redirects back to CENRO dashboard |  |  |
| TC-004 | Logout | Logged in | 1. Click logout 2. Re-open previous dashboard URL | Session ends and user is redirected to login |  |  |

## B. Authentication and Account Management
| TC ID | Feature | Preconditions | Steps | Expected Result | Result | Notes |
|---|---|---|---|---|---|---|
| TC-005 | Login success | Valid user account | 1. Open login 2. Enter valid email/password 3. Submit | Login succeeds and redirects to role dashboard |  |  |
| TC-006 | Login invalid password | Valid email, wrong password | 1. Submit invalid password | Error shown: invalid credentials |  |  |
| TC-007 | Login inactive or unknown account | Unknown or inactive user | 1. Submit credentials | Login denied with generic error |  |  |
| TC-008 | Login lockout | Valid user | 1. Enter wrong password repeatedly (5x) | Account becomes temporarily locked |  |  |
| TC-009 | Lockout countdown | Account currently locked | 1. Retry login during lock period | Message shows remaining lock time |  |  |
| TC-010 | Remember me session | Valid user | 1. Check Remember Me 2. Login 3. Close/reopen browser | Session persists according to remember cookie policy |  |  |
| TC-011 | Registration success | Email not yet used | 1. Open register 2. Fill all fields 3. Submit | User account created and redirected to login |  |  |
| TC-012 | Registration domain validation | Non-DENR email | 1. Register with non-`@denr.gov.ph` email | Validation error shown |  |  |
| TC-013 | Forgot password OTP request | Active user email | 1. Open forgot password 2. Request code | OTP generated, success info shown |  |  |
| TC-014 | Forgot password invalid OTP | Active user email | 1. Enter wrong OTP in reset form | Reset blocked with OTP error |  |  |
| TC-015 | Forgot password success | Valid OTP | 1. Enter valid OTP 2. Set new password | Password updates; redirected to login |  |  |

## C. Security Controls
| TC ID | Feature | Preconditions | Steps | Expected Result | Result | Notes |
|---|---|---|---|---|---|---|
| TC-016 | CSRF on login | Logged out | 1. Submit login form with invalid CSRF token | Request rejected with session/CSRF error |  |  |
| TC-017 | CSRF on document actions | Logged in | 1. Submit document action with invalid CSRF token | API returns token error and action not applied |  |  |
| TC-018 | Auth required for action endpoints | Logged out | 1. POST to `actions/document-action.php` | Returns authentication required |  |  |
| TC-019 | Security audit logging | DB access to `security_audit_logs` | 1. Perform success and failed login attempts | Corresponding log rows are recorded |  |  |

## D. Document Intake and Attachments
| TC ID | Feature | Preconditions | Steps | Expected Result | Result | Notes |
|---|---|---|---|---|---|---|
| TC-020 | Internal intake creation | Logged in (CENRO/PENRO/PASU/SECTION etc.) | 1. Open create intake 2. Fill subject/type 3. Submit | Document created with tracking ID |  |  |
| TC-021 | External intake validation | Logged in | 1. Select `EXTERNAL` source 2. Leave client name blank 3. Submit | Validation error for external client name |  |  |
| TC-022 | Initial destination routing | Logged in | 1. Create intake with destination office | New document is created and marked forwarded to destination |  |  |
| TC-023 | Attachment required rule | Logged in | 1. Submit with no attachment and no remarks | Request rejected by validation |  |  |
| TC-024 | Attachment type allowlist | Logged in | 1. Upload unsupported extension | Upload blocked with unsupported type message |  |  |
| TC-025 | Attachment size limit | Logged in | 1. Upload file over 15MB | Upload blocked with file size message |  |  |
| TC-026 | Attachment versioning | Existing document with attachments | 1. Upload additional files for same document | New versions are incremented and stored |  |  |

## E. Workflow Actions and Routing
| TC ID | Feature | Preconditions | Steps | Expected Result | Result | Notes |
|---|---|---|---|---|---|---|
| TC-027 | Forward action | Document in user custody | 1. Forward to another office | Status updates to Forwarded; activity log recorded |  |  |
| TC-028 | Receive action | Document pending for user office | 1. Click receive | Status updates to Received; tracking slip row inserted |  |  |
| TC-029 | Auto receive on slip open | Logged in user opens tracking slip of pending doc | 1. Open tracking slip | Auto receive executes once without duplicate spam |  |  |
| TC-030 | Return action | Document in user custody | 1. Return to office/user with remarks | Status updates to Returned; route recorded |  |  |
| TC-031 | Mark pending | Document in user custody | 1. Set action to Pending | Status updates to Pending |  |  |
| TC-032 | Approve action | User with approve authority | 1. Execute approve | Status updates to Approved |  |  |
| TC-033 | Sign action | User with sign authority | 1. Execute sign | Status updates to Signed |  |  |
| TC-034 | Release action | Authorized user | 1. Execute release to destination | Status updates to Released with destination log |  |  |
| TC-035 | Reroute action | Supervisor role | 1. Reroute document | Status remains route-active, destination changes |  |  |
| TC-036 | Override action (ORED only) | Logged in as ORED | 1. Perform override route | Override succeeds and logs `Overridden` |  |  |
| TC-037 | Override denied for non-ORED | Logged in as non-ORED | 1. Attempt override | Request denied by authorization policy |  |  |
| TC-038 | Transition policy enforcement | Workflow transition rules loaded | 1. Attempt invalid transition path | Operation blocked with transition policy error |  |  |
| TC-038A | ORED cannot assign to Section | Logged in as ORED; routable document exists | 1. Try routing to Section office/user | Action is blocked with policy message |  |  |
| TC-038B | Division Chief can assign to Section | Logged in as Division Chief; routable document exists | 1. Forward to Section office | Action succeeds |  |  |

## F. Tracking Slip and Print Package
| TC ID | Feature | Preconditions | Steps | Expected Result | Result | Notes |
|---|---|---|---|---|---|---|
| TC-039 | Tracking slip by tracking ID | Existing tracking ID | 1. Open `tracking-slip.php?tracking_id=...` | Correct document details and timeline appear |  |  |
| TC-040 | Tracking slip missing ID handling | No documents in DB or invalid ID | 1. Open slip with invalid ID | Clear user-facing error shown |  |  |
| TC-041 | Print package rendering | Existing tracking ID | 1. Open `print-package.php?tracking_id=...` | Printable package displays summary, timeline, attachments |  |  |
| TC-042 | QR visibility | Existing tracking ID | 1. Open tracking slip/print package | QR image is rendered for tracking ID |  |  |

## G. Document Type Requests (DTR)
| TC ID | Feature | Preconditions | Steps | Expected Result | Result | Notes |
|---|---|---|---|---|---|---|
| TC-043 | Request create by requester role | Logged in as CENRO/PENRO/PASU | 1. Submit new DTR | Status is `PENDING` and appears in list |  |  |
| TC-044 | Request edit by requester | Own pending request exists | 1. Select request 2. Edit fields 3. Save | Changes saved if status is pending |  |  |
| TC-045 | Request delete by requester | Own pending request exists | 1. Delete request | Request removed successfully |  |  |
| TC-046 | Approve request by PACDO | Logged in as PACDO; pending request exists | 1. Approve with optional remarks | Request becomes `APPROVED` and document type is created/updated |  |  |
| TC-047 | Reject request by PACDO | Logged in as PACDO; pending request exists | 1. Reject with remarks | Request becomes `REJECTED` |  |  |
| TC-048 | Non-reviewer approve block | Logged in as non-PACDO | 1. Attempt approve action | Action denied by permission check |  |  |
| TC-049 | Category defaults | Create request with category variants | 1. Submit Simple/Complex/Highly Technical | Default day/color rules are applied correctly |  |  |

## H. Dashboard, Search, and Monitoring
| TC ID | Feature | Preconditions | Steps | Expected Result | Result | Notes |
|---|---|---|---|---|---|---|
| TC-050 | Dashboard load per role | Login per role account | 1. Open role dashboard | Dashboard loads without permission or query errors |  |  |
| TC-051 | Queue metrics accuracy | Known sample dataset | 1. Compare dashboard counts vs DB query | Total/pending/due/overdue values are accurate |  |  |
| TC-052 | Search by tracking ID | Known tracking ID | 1. Search in role/global search page | Matching record appears |  |  |
| TC-053 | Search by keyword/subject | Known subject keyword | 1. Search by keyword | Related documents appear |  |  |
| TC-054 | Pending receive queue | Forwarded docs exist for office | 1. Open pending receive page | Correct pending documents are listed |  |  |

## I. Role-Specific Smoke Tests
| TC ID | Role | Feature Page | Steps | Expected Result | Result | Notes |
|---|---|---|---|---|---|---|
| TC-056 | ORED | `path-diversion-override.php` | Override a valid document path | Override works only for ORED |  |  |
| TC-057 | PACDO | `for-clearance.php` | Open queue and process one item | Queue/action works |  |  |
| TC-058 | PACDO | `arta-compliance-monitor.php` | Open monitor page | Data renders correctly |  |  |
| TC-059 | PENRO | `for-endorsement.php` | Process one document | Endorsement flow works |  |  |
| TC-060 | CENRO | `for-cenro-action.php` | Process one document | Action flow works |  |  |
| TC-061 | PASU | `field-validation.php` | Open and process one item | Validation flow works |  |  |
| TC-062 | DIVISION-CHIEF | `adhoc-reroute.php` | Reroute a document | Reroute works with policy checks |  |  |
| TC-063 | SECTION-STAFF | `in-progress.php` | Open and update one active item | Page and action flow work |  |  |

## J. Disabled Feature Confirmation (Regression Guard)
| TC ID | Feature | Preconditions | Steps | Expected Result | Result | Notes |
|---|---|---|---|---|---|---|
| TC-064 | MFA step disabled | Valid login credentials | 1. Login normally | User is not stopped at OTP page |  |  |
| TC-065 | First-login force reset disabled | User with `must_change_password=1` | 1. Login with valid credentials | User goes to role dashboard, not force-reset page |  |  |

## K. New Feature Requests (QR + URL)
| TC ID | Feature | Preconditions | Steps | Expected Result | Result | Notes |
|---|---|---|---|---|---|---|
| TC-066 | PACDO QR quick receive (camera) | Login as PACDO; pending document exists | 1. Open PACDO queue page 2. Start QR scan 3. Scan tracking QR | Document receives successfully and queue refreshes |  |  |
| TC-067 | PENRO QR quick receive (manual input) | Login as PENRO; pending document exists | 1. Paste tracking ID in fast receive field 2. Click Receive | Document receives successfully |  |  |
| TC-068 | QR quick receive invalid tracking | Login as PACDO/PENRO | 1. Enter invalid tracking ID 2. Click Receive | UI shows not found/validation error, no action applied |  |  |
| TC-069 | Intake with URL only | Login on intake page | 1. Add subject/type 2. Add valid URL in Document URL(s) 3. Submit with no file | Intake succeeds and URL is saved as attachment reference |  |  |
| TC-070 | Intake with invalid URL | Login on intake page | 1. Enter malformed URL 2. Submit | Validation blocks submission with clear error |  |  |
| TC-071 | Print package URL attachment link | Existing doc with URL attachment | 1. Open print package for tracking ID | Attachment path renders as clickable external URL |  |  |

## Exit Criteria
1. All P1/P2 critical flows are `PASS`.
2. No blocker defects remain on:
   - login/auth
   - intake creation
   - receive/forward/return workflows
   - role access restrictions
3. Any failed non-blocker case has a ticket number and workaround.
