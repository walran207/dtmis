## Phase 6: Root File Relocation

To keep project root clean, standalone root files were moved while keeping `.htaccess` and `index.php` at root.

### Moved pages

- `dashboard.php` -> `app/pages/dashboard.php`
- `notifications.php` -> `app/pages/notifications.php`
- `print-package.php` -> `app/pages/print-package.php`
- `tracking-slip.php` -> `app/pages/tracking-slip.php`
- `softcopy-digital-signature.php` -> `app/pages/softcopy-digital-signature.php`
- `softcopy-qr-stamp.php` -> `app/pages/softcopy-qr-stamp.php`
- `softcopy-records-unit-stamp.php` -> `app/pages/softcopy-records-unit-stamp.php`

### Moved assets

- `dashboard.css` -> `assets/css/dashboard.css`
- `notif-sound.wav` -> `assets/audio/notif-sound.wav`

### Compatibility

Legacy root URLs are preserved through rewrite rules in [`.htaccess`](/C:/xampp/htdocs/Edats/.htaccess):

- `/*.php` pages listed above route to `app/pages/*.php`
- `/dashboard.css` routes to `assets/css/dashboard.css`
- `/notif-sound.wav` routes to `assets/audio/notif-sound.wav`
