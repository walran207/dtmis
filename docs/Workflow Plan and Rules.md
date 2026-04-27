## eDATS Workflow Plan v2 (Improved and Decision-Complete)

### Summary
This workflow enforces a strict upward routing path for intake, controlled review at ORED/Division, and a release-back cycle to the originating office for completion.

1. `CENRO/PASU -> PENRO -> PACDO -> ORED`
2. `ORED -> Division Chief -> (optional) Section Staff -> Division Chief -> ORED`
3. `ORED (final sign) -> PACDO (release) -> Originating Office (receive) -> Completed`

### Final Workflow Rules
1. **Intake and Upward Routing**
   1. CENRO and PASU can only forward intake to PENRO.
   2. PENRO can only forward to PACDO.
   3. PACDO can only forward to ORED.
2. **ORED and Division Processing**
   1. ORED receives and reviews.
   2. If action is needed at division level, ORED forwards to Division Chief.
   3. Division Chief may either handle directly or assign to Section Staff.
   4. If assigned, Section Staff receives and returns/forwards back to Division Chief.
   5. Division Chief forwards back to ORED for final review.
3. **Release and Completion**
   1. ORED signs only after final review is clear.
   2. Signed document is routed to PACDO.
   3. PACDO prints tracking slip/package and performs `Release` to the originating office only.
   4. Originating office receives released document.
   5. Status becomes `Completed`.

### Action Visibility and Sequencing Rules (UI + Policy)
1. `Receive` is visible only when the document is pending for the current office/user.
2. `Approve` is visible only after `Receive` is completed.
3. `Forward` is visible only after `Approve` for approval-required roles.
4. Approval-required roles are: `PENRO`, `PACDO`, `Division Chief`, `ORED`.
5. For non-approval roles (`CENRO`, `PASU`, `Section Staff`), `Forward` is allowed after `Receive` (or initial creation where applicable).
6. `Sign` is only for `ORED` during final stage before release.
7. `Release` is only for `PACDO` and destination must be the originating office.

### Edit/Delete Policy (Strict Lock)
1. `Edit` and `Delete` are allowed only for the document creator.
2. Once the document is forwarded to the next office, `Edit/Delete` are no longer allowed.
3. `Edit/Delete` are always blocked for terminal states (`Released`, `Signed`, `Completed`, `Closed`, `Cancelled`).
4. `Edit/Delete` are blocked after any custody receive/progress event.

### Validation and Acceptance Scenarios
1. CENRO creates intake and can edit/delete only before forwarding.
2. After CENRO forwards to PENRO, edit/delete are disabled immediately.
3. PENRO cannot forward unless received then approved.
4. PACDO cannot forward to ORED unless received then approved.
5. ORED cannot sign until division/section loop is completed (if invoked).
6. PACDO release fails if destination is not originating office.
7. Origin office receive on released document sets status to `Completed`.

### Assumptions and Defaults
1. “Concern office” means the next routed office in the approved workflow path.
2. Division-to-section assignment is optional and controlled by Division Chief.
3. Return/reroute paths can exist, but they do not bypass the final sign-release-complete chain.
4. This plan is the target policy baseline; all workflow transitions, backend guards, and button visibility should conform to it.
