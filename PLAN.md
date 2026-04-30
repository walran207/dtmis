## CENRO Internal Workflow v1 (Full New Roles, CENRO-only Scope)

### Summary
Implement a dedicated CENRO internal routing layer using 4 new system roles with full workflow enforcement, then hand off to existing regional flow as needed.

- New roles: `CENRO_ADMIN_RECORD`, `CENRO_OFFICER`, `CENRO_SECTION`, `CENRO_UNIT`
- Applies only to CENRO offices
- CENRO chain: `ADMIN_RECORD -> OFFICER -> SECTION -> UNIT -> SECTION -> ADMIN_RECORD`
- From `CENRO_ADMIN_RECORD`, allow 3 outcomes:
1. Complete locally
2. Forward to PENRO
3. Forward direct to PACDO/Regional Records (endorsement attachment required)

### Key Changes
#### 1) Database and Seed Design
- Add the 4 new roles in `roles`.
- Extend office hierarchy under each CENRO office with standardized children:
1. `Monitoring and Enforcement Section (MES)` + units:
   - `Patrolling/Forest Surveillance`
   - `Enforcement and Monitoring Tenure Assessment`
2. `Conservation and Development Section (CDS)` + units:
   - `National Greening Program`
   - `Coastal and Marine Ecosystem Management Program`
3. `Regulation and Permitting Section (RPS)` + units:
   - `Survey and Mapping Unit`
   - `Patents and Deeds Unit`
   - `Permitting and Licensing Unit`
4. `Planning and Support Unit (PSU)` (+ one default operational unit seed for routing completeness)
- Add role relationships in `role_unit_mappings`:
1. `CENRO_ADMIN_RECORD -> CENRO_OFFICER` (per CENRO office)
2. `CENRO_OFFICER -> CENRO_SECTION` (per section office)
3. `CENRO_SECTION -> CENRO_UNIT` (per unit office)
- Add workflow rules in `workflow_transitions` for the CENRO chain and escalation outcomes:
1. `FORWARD`: admin->officer, officer->section, section->unit, unit->section, section->admin
2. `FORWARD`: admin->PENRO
3. `FORWARD`: admin->PACDO
4. `COMPLETE`: admin->NULL (local completion)
5. `RECEIVE/APPROVE/PENDING/SIGN` permissions for new roles (CENRO Officer gets full ORED-like authority)

#### 2) Workflow Engine and Policy
- Extend role/office inference so CENRO section/unit offices resolve to the new role IDs.
- Add CENRO-specific destination scope guards:
1. Officer can only target sections under its own CENRO office
2. Section can only target its child units or back to admin record
3. Unit can only return to parent section
- Add `COMPLETE` action handler to close transaction locally from `CENRO_ADMIN_RECORD`.
- Enforce endorsement attachment as required when `CENRO_ADMIN_RECORD` routes directly to PACDO.
- Keep source type policy flexible: both `INTERNAL` and `EXTERNAL` can use all 3 outcomes.

#### 3) Interface and Compatibility Changes
- New public role keys: `CENRO_ADMIN_RECORD`, `CENRO_OFFICER`, `CENRO_SECTION`, `CENRO_UNIT`.
- New workflow action key: `COMPLETE`.
- New office levels for disambiguation: `CENRO_SECTION`, `CENRO_UNIT`.
- Keep UI/function parity by mapping new roles to existing behavior screens:
1. `CENRO_ADMIN_RECORD` behaves like Records Unit
2. `CENRO_OFFICER` behaves like ORED
3. `CENRO_SECTION` behaves like Division Chief
4. `CENRO_UNIT` behaves like Section Staff

### Test Plan
1. Migration idempotency:
   - Re-run migrations without duplicate roles/offices/transitions/mappings.
2. Happy path chain:
   - `ADMIN_RECORD -> OFFICER -> SECTION -> UNIT -> SECTION -> ADMIN_RECORD -> COMPLETE`.
3. Escalation paths:
   - Admin to PENRO works.
   - Admin to PACDO works only with endorsement attachment.
4. Permission negatives:
   - Officer cannot route outside its own CENRO subtree.
   - Section cannot target unrelated unit/section.
   - Unit cannot forward except back to its parent section.
5. Role authority:
   - CENRO Officer can Receive/Approve/Sign as configured.
6. Backward compatibility:
   - Existing CENRO/PENRO/PACDO/ORED/ARD routes continue unchanged for non-CENRO-internal records.

### Assumptions and Defaults
- “Regional” target means `PACDO / Records Unit`.
- All CENRO offices get the same seeded 4-section template.
- `PSU` gets one default unit seed so section->unit->section workflow is always executable.
- Direct PACDO route from CENRO admin always requires endorsement letter attachment.
- No strict `INTERNAL` vs `EXTERNAL` route lock; policy is attachment/permission based.
