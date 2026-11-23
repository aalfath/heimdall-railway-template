# Changelog

## 2026-11-23: Heimdall 2.8.4 (first release)

- Heimdall `linuxserver/heimdall:v2.8.4-ls375@sha256:51693bcb3a46e619592a4f7aad65d6610f8bda80c3b167d03254a96a944e8aed`.
- Admin password set from `HEIMDALL_ADMIN_PASSWORD` (generated) before the web server starts; no open first-boot window.
- nginx listens on `PORT`; nginx and PHP-FPM workers sized for a 0.5 GB service.
- Database queue (`QUEUE_CONNECTION=database`), so the app catalogue downloads in the background and pages don't fail when the catalogue site is slow.
- Volume at `/config` for the database, uploads, sessions and the app catalogue.
- Laravel log level `warning` (upstream writes DEBUG lines to the volume on every request).
- Health check `/up`; a failed init step exits non-zero so Railway restarts the service.
