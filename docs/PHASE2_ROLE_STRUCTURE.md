## Phase 2 Role Folder Standardization

This project now uses a standardized role-internal layout while preserving backward compatibility.

### Standard per-role structure

```text
ROLE/
  pages/         # Canonical PHP page implementations
  partials/      # Shared role view fragments
  assets/css/    # Canonical role CSS files
  css/           # Compatibility wrappers (@import -> assets/css)
  *.php          # Compatibility wrappers (require -> pages/*.php)
```

### Compatibility behavior

- Existing URLs like `ROLE/dashboard.php` continue to work.
- Root role PHP files now act as wrappers and load `ROLE/pages/*.php`.
- Legacy CSS paths like `ROLE/css/dashboard.css` continue to work through wrapper files.

### Migration guardrails

- Keep links/routes targeting existing role root files until all consumers are updated.
- New role page development should be added under `ROLE/pages/`.
- New role styles should be added under `ROLE/assets/css/`.
