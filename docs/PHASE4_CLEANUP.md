## Phase 4 Cleanup (Wrapper Removal)

Phase 4 removed compatibility wrapper files and switched to canonical runtime paths.

### Canonical locations now

- Role pages: `ROLE/pages/*.php`
- Role styles: `ROLE/assets/css/*.css`
- Actions: `app/actions/*.php`
- Modules: `app/modules/*.php`
- Role template: `app/templates/role-page-template.php`

### What was removed

- Wrapper files under `actions/` and `modules/`
- Root `role-page-template.php` wrapper
- Root role page wrappers like `ROLE/dashboard.php`
- Role `ROLE/css/*.css` wrapper files
- Empty legacy directories (`ROLE/crud`, `ROLE/js`, `assets/stamps`)

### Backward URL compatibility

Legacy URLs are now preserved via rewrite rules in [`.htaccess`](/C:/xampp/htdocs/Edats/.htaccess):

- `actions/*.php` -> `app/actions/*.php`
- `modules/*.php` -> `app/modules/*.php`
- `role-page-template.php` -> `app/templates/role-page-template.php`
- `ROLE/css/*.css` -> `ROLE/assets/css/*.css`
- `ROLE/*.php` -> `ROLE/pages/*.php`

These compatibility rewrites require Apache with `mod_rewrite` enabled.
