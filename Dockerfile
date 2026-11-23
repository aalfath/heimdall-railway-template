# Heimdall application dashboard for Railway.
# Upstream: https://github.com/linuxserver/Heimdall (MIT), image by LinuxServer.io.
# Pinned by version and digest; bump both together and add a CHANGELOG.md entry.
FROM linuxserver/heimdall:v2.8.4-ls375@sha256:51693bcb3a46e619592a4f7aad65d6610f8bda80c3b167d03254a96a944e8aed

# Fail the container (non-zero exit) if any init step fails, so Railway's
# ON_FAILURE restart policy and the deploy status see it.
ENV S6_BEHAVIOUR_IF_STAGE2_FAILS=2 \
    TZ=Etc/UTC \
    TRUSTED_PROXIES=* \
    QUEUE_CONNECTION=database \
    LOG_LEVEL=warning \
    PORT=8080

# QUEUE_CONNECTION=database: Heimdall's .env still says "sync" (the image only
# rewrites the old QUEUE_DRIVER key), so every page load would download the app
# catalogue inline and answer 500 whenever appslist.heimdall.site is slow or
# down. With "database" the bundled queue worker does it in the background.
# LOG_LEVEL=warning: upstream logs DEBUG lines to the volume on every request.
# TRUSTED_PROXIES=*: trust Railway's edge for X-Forwarded-Proto/For, so links
# are https. (X-Forwarded-Host is never trusted upstream, CVE-2025-50578.)

# s6 oneshot "init-railway": runs after the image's own setup and before
# nginx, php-fpm and the queue worker start (see root/).
COPY root/ /

RUN set -eux; \
    chmod 0755 /etc/s6-overlay/s6-rc.d/init-railway/run /etc/s6-overlay/s6-rc.d/svc-queue/run; \
    bash -n /etc/s6-overlay/s6-rc.d/init-railway/run; \
    php -l /railway/admin.php; \
    grep -q '__PORT__' /defaults/railway/site.conf

EXPOSE 8080
