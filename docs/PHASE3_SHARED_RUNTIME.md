## Phase 3 Shared Runtime Migration

Phase 3 moved shared runtime internals into `app/` while preserving old public paths using compatibility wrappers.

### Canonical runtime locations

- `app/actions/*.php` (from `actions/*.php`)
- `app/modules/*.php` (from `modules/*.php`)
- `app/templates/role-page-template.php` (from `role-page-template.php`)

### Backward compatibility kept

- Existing action URLs such as `actions/document-action.php` still work through wrapper files.
- Existing role page references to `role-page-template.php` still work through a root wrapper.
- Existing module includes continue to work through `modules/*.php` wrappers.

### Additional fix included

Role pages under `ROLE/pages/` that set `$customSectionInclude` were updated to point to `app/modules/*` so include resolution remains correct after Phase 2 page relocation.
