[![Deploy on Railway](https://railway.com/button.svg)](https://railway.com/new/template/heimdall-1?utm_medium=integration&utm_source=button&utm_campaign=heimdall)

# Heimdall on Railway

One-click Railway template for [Heimdall](https://github.com/linuxserver/Heimdall) (MIT): an application dashboard and launcher that keeps a tile for every web app you use on one page.

## What it deploys

One service, **Heimdall**, built from this repo's `Dockerfile`:

- the official LinuxServer.io image `linuxserver/heimdall:v2.8.4-ls375` (Heimdall 2.8.4), pinned by digest (`sha256:51693bcb3a46e619592a4f7aad65d6610f8bda80c3b167d03254a96a944e8aed`). It runs nginx, PHP-FPM and Heimdall's background queue worker in one container;
- a volume at `/config` holding everything Heimdall stores: the SQLite database, `.env` (with the app key), uploaded icons and backgrounds, login sessions and the downloaded app catalogue;
- an extra init step (`root/etc/s6-overlay/s6-rc.d/init-railway`) that, before the web server starts, renders nginx for Railway's `PORT`, sizes nginx and PHP-FPM for a small service, and sets the admin password from `HEIMDALL_ADMIN_PASSWORD`. Stock Heimdall starts with a passwordless admin, which on a public URL would be an open, editable dashboard; this template never serves it that way;
- health check `GET /up` (Laravel's health route, open without a login), restart policy `ON_FAILURE`. If any init step fails, the container exits non-zero so Railway marks the deploy failed and restarts it.

It uses about 75 MB of RAM and fits Railway's Free plan (0.5 GB).

## Variables

| Variable | Default | Purpose |
|---|---|---|
| `HEIMDALL_ADMIN_PASSWORD` | generated (24 characters) | Password of the admin user. Find it in the service's **Variables** tab. Changing it and redeploying sets the new password. |
| `HEIMDALL_ADMIN_USERNAME` | `admin` | User name of the admin user (Heimdall's user 1). |
| `APP_URL` | `https://${{RAILWAY_PUBLIC_DOMAIN}}` | Public URL Heimdall uses for its links. Change it if you add a custom domain. |
| `PORT` | `8080` | Port nginx listens on (the public domain points to it). |

Optional, not in the template: `ALLOW_INTERNAL_REQUESTS=true` lets Heimdall's server-side lookups (website icons, enhanced-app status) reach private addresses, for example your other Railway services on `*.railway.internal`. `TZ` sets the time zone (default `Etc/UTC`).

## After deploy

1. Open the service's public URL. You land on Heimdall's login page: sign in with `admin` and the `HEIMDALL_ADMIN_PASSWORD` value from the **Variables** tab.
2. Open the menu on the right, choose **Items** and **Add**. Pick an app from the *Application Type* list (Heimdall fills in its name, colour and icon) or enter your own, add the URL, tick **Pinned** and save.
3. Optional: under **Users** you can add more users, each with their own tiles and an optional public front page; under **Settings** you can change the background, search provider and language. If you change the admin password in Heimdall's own user page, the template leaves it alone until you change `HEIMDALL_ADMIN_PASSWORD`.

## Updates

The `Dockerfile` pins Heimdall by version and digest. When a new release comes out we bump the pin here and add an entry to [CHANGELOG.md](CHANGELOG.md). Railway then shows an "update available" notice on projects deployed from this template, and you choose when to apply it. Heimdall migrates its database at boot and your `/config` volume is kept across updates.

## Files

- `Dockerfile`: the pinned upstream image plus the init step and its defaults.
- `root/etc/s6-overlay/s6-rc.d/init-railway/run`: nginx on `PORT`, worker counts from the CPU quota, PHP limits, sessions on the volume, admin password.
- `root/railway/admin.php`: finishes Heimdall's first-run setup and writes the hashed admin password.
- `root/defaults/railway/`: the nginx site, `php.ini` and PHP-FPM pool settings written on every boot.
