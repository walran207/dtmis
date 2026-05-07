# eDATS Offline Setup Checklist

## 1. Install `mkcert`

Use one of these:

```powershell
choco install mkcert
```

Or download from:

- [mkcert GitHub](https://github.com/FiloSottile/mkcert)

Verify install:

```powershell
mkcert -help
```

## 2. Install the local CA

Run:

```powershell
mkcert -install
```

Check where the CA is stored:

```powershell
mkcert -CAROOT
```

This CA must also be trusted on every client laptop/device that will open eDATS over HTTPS.

## 3. Find the hotspot/LAN IP

Run:

```powershell
ipconfig
```

Example:

- `192.168.5.15`

## 4. Generate the HTTPS certificate

Go to the Apache SSL certificate folder:

```powershell
cd C:\xampp\apache\conf\ssl.crt
```

Generate the certificate:

```powershell
mkcert localhost 127.0.0.1 192.168.5.15
```

Example output files:

- `localhost+2.pem`
- `localhost+2-key.pem`

If the IP changes later, generate a new certificate again using the new IP.

## 5. Configure Apache SSL in XAMPP

Edit:

- [httpd-ssl.conf](C:/xampp/apache/conf/extra/httpd-ssl.conf)

Use these values:

```apache
ServerName 192.168.5.15:443
SSLCertificateFile "conf/ssl.crt/localhost+2.pem"
SSLCertificateKeyFile "conf/ssl.crt/localhost+2-key.pem"
```

## 6. Restart Apache

Restart Apache from the XAMPP Control Panel.

## 7. Test HTTPS on the server laptop

Open:

- `https://localhost/edats`
- `https://192.168.5.15/edats`

Check in browser console:

```js
window.isSecureContext
navigator.serviceWorker.getRegistrations().then(r => console.log(r))
navigator.serviceWorker.controller
```

Expected:

- `window.isSecureContext` should be `true`
- a service worker registration should exist
- if `navigator.serviceWorker.controller` is `null`, refresh once

## 8. Trust the CA on the other laptop/device

On the server laptop:

```powershell
mkcert -CAROOT
```

Copy the root CA file from that folder, usually:

- `rootCA.pem`

To the other laptop/device.

On Windows client laptop:

1. Open `certmgr.msc`
2. Go to `Trusted Root Certification Authorities`
3. Import `rootCA.pem`
4. Close and reopen the browser

Important:

- Import the `mkcert` root CA, not the site certificate like `localhost+2.pem`

## 9. Test HTTPS on the other laptop

Connect the other laptop to the hotspot/LAN, then open:

- `https://192.168.5.15/edats`

Check in browser console:

```js
window.isSecureContext
navigator.serviceWorker.getRegistrations().then(r => console.log(r))
navigator.serviceWorker.controller
```

Expected:

- secure connection
- service worker registration exists

## 10. Test offline flow

1. Open eDATS while online
2. Log in
3. Visit the pages you want available offline
4. Refresh once if needed
5. Confirm the service worker is active
6. Disconnect hotspot/network
7. Reload a previously visited page
8. Test offline intake creation

## Important reminders

- `http://192.168.x.x` is not enough for service worker offline support
- `https://192.168.x.x` is required for other devices
- `localhost` is a special trusted case on the server laptop
- every client device must trust the same `mkcert` root CA
- if the hotspot IP changes, regenerate the certificate
- service worker may need one refresh after first install

## Quick troubleshooting

If the browser says `Not secure`:

- check that Apache is serving `https://`
- check that the client device trusts `rootCA.pem`
- fully close and reopen the browser

If `window.isSecureContext` is `false`:

- the browser still does not trust the certificate

If service worker registration is missing:

- verify HTTPS is working first
- refresh once after the first page load
- check for warnings in the browser console

## App-side notes already implemented

These eDATS fixes are already in place:

- service worker moved to the app root
- visible warning when service worker registration fails
- improved cached navigation fallback
- queued offline work is preserved better on session expiry
- auth pages only clear offline data on explicit logout
