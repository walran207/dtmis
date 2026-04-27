## Phase 5: Roles Container

Role folders are now organized under a single parent directory:

```text
roles/
  ARD-MS/
  ARD-TS/
  CENRO/
  DIVISION-CHIEF/
  ORED/
  RECORS-UNIT/
  PASU/
  PENRO/
  SECTION-STAFF/
```

### Internal path updates applied

- Role page includes now use `dirname(__DIR__, 3)` for root-level includes (`config/*`, `app/templates/*`, `app/modules/*`).

### URL compatibility

Existing URLs remain valid through rewrite rules in [`.htaccess`](/C:/xampp/htdocs/Edats/.htaccess), including:

- `ROLE/dashboard.php` -> `roles/ROLE/pages/dashboard.php`
- `ROLE/assets/...` -> `roles/ROLE/assets/...`
- `ROLE/css/...` -> `roles/ROLE/assets/css/...` (legacy CSS path support)

This means you get cleaner filesystem organization without changing user-facing role URLs.
