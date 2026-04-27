## Phase 7: Role Template Split

`app/templates/role-page-template.php` was split into smaller chunks without changing behavior.

### New files

- [`_role_helpers.php`](/C:/xampp/htdocs/Edats/app/templates/_role_helpers.php)
  - Auth/session checks
  - Shared helper functions

- [`_role_bootstrap.php`](/C:/xampp/htdocs/Edats/app/templates/_role_bootstrap.php)
  - Runtime variable setup
  - Queue/metrics/bootstrap preparation

- [`_role_styles.php`](/C:/xampp/htdocs/Edats/app/templates/_role_styles.php)
  - Extracted inline CSS block

- [`_role_scripts.php`](/C:/xampp/htdocs/Edats/app/templates/_role_scripts.php)
  - Extracted inline JavaScript block

- [`role-page-template.php`](/C:/xampp/htdocs/Edats/app/templates/role-page-template.php)
  - Now acts as a thin orchestrator that includes the chunk files.

### Why this is safe

- Entry path stayed the same: `app/templates/role-page-template.php`
- Role pages still include the same template path
- No route changes were introduced for this split
- Full PHP lint and smoke checks passed after extraction
