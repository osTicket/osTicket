osTicket with Docker Compose
============================

Runs the source in this repository as a container image (Apache + PHP 8.3),
with a MariaDB service alongside it. The application code lives **inside** the
image; only state — database files, `ost-config.php`, plugins, language packs
and file-backed attachments — lives in mapped volumes under `./data/`.

Layout
------

```
docker-compose.yml           three services: db, app, cron
.env.example                 copy to .env, then edit
docker/app/Dockerfile        application image
docker/app/entrypoint.sh     wires /data into the application tree on boot
docker/app/cron.sh           runs api/cron.php on an interval
docker/app/apache-osticket.conf
docker/app/php-osticket.ini

data/db/                     MariaDB data directory
data/osticket/config/        ost-config.php  (symlinked to include/ost-config.php)
data/osticket/plugins/       plugin .phar files, copied into include/plugins/ on boot
data/osticket/i18n/          language pack .phar files, copied into include/i18n/ on boot
data/osticket/attachments/   target for the Filesystem storage plugin
```

`data/` and `.env` are gitignored.

First run
---------

```sh
cp .env.example .env
# edit .env -- at minimum MYSQL_ROOT_PASSWORD and MYSQL_PASSWORD
docker compose up -d --build
```

Open <http://localhost:8100>. The container ships an `ost-config.php` with
`OSTINSTALLED=FALSE`, so osTicket redirects to its installer. Fill it in using:

| Field           | Value                            |
| --------------- | -------------------------------- |
| MySQL Hostname  | `db`                             |
| MySQL Database  | `MYSQL_DATABASE` from `.env`     |
| MySQL Username  | `MYSQL_USER` from `.env`         |
| MySQL Password  | `MYSQL_PASSWORD` from `.env`     |

The installer writes through the symlink to
`data/osticket/config/ost-config.php`, so the install survives
`docker compose down` and image rebuilds.

After the installer finishes, drop the `setup/` directory (osTicket warns
about it until you do):

```sh
# in .env
OSTICKET_REMOVE_SETUP=true
docker compose up -d --force-recreate app
```

Day-to-day
----------

```sh
docker compose logs -f app        # Apache access/error logs go to stdout
docker compose exec app php manage.php --help
docker compose restart cron
```

**Rebuilding after a code change.** The source is baked into the image, so
`docker compose up -d --build` is the update path. Nothing under `./data/` is
touched by a rebuild.

**Plugins.** Drop the `.phar` into `data/osticket/plugins/` and recreate the
app container; it is copied into `include/plugins/` at boot and will then show
up under Admin Panel → Manage → Plugins.

**Language packs.** Same, but into `data/osticket/i18n/`.

**Attachments.** osTicket stores attachments in the database by default. To put
them on disk instead, install the Filesystem storage plugin and point it at
`/data/attachments`, which is mapped to `data/osticket/attachments/`.

**Backups.** `data/db/` plus `data/osticket/` is the complete state.

```sh
docker compose exec db mariadb-dump -u root -p"$MYSQL_ROOT_PASSWORD" \
    --single-transaction osticket > backup.sql
```

Notes and trade-offs
--------------------

**PHP 8.3, not 8.4.** osTicket supports 8.2–8.4, but `ext-imap` is unbundled in
8.4 and Debian no longer packages uw-imap. It is omitted here in either case:
osTicket calls `imap_utf8()` / `imap_mime_header_decode()` only behind
`function_exists()` guards, and mail fetching goes through the bundled
laminas-mail. The installer's prerequisite page lists PHP IMAP under
*recommended*, not *required*, so it will show a red mark that is safe to
ignore. Everything actually required (PHP version, `mysqli`) plus the full
recommended set — `gd`, `intl`, `zip`, `gettext`, `apcu`, OPcache, `ldap` — is
installed.

**MariaDB `sql-mode`.** Set to `NO_ENGINE_SUBSTITUTION` only. osTicket's schema
relies on columns without defaults, which `STRICT_TRANS_TABLES` rejects.

**Behind a reverse proxy.** The vhost maps `X-Forwarded-Proto: https` onto
`HTTPS=on`. Also set `TRUSTED_PROXIES` in
`data/osticket/config/ost-config.php` to your proxy's IP or CIDR, otherwise
osTicket ignores `X-Forwarded-For` and logs the proxy as the client IP.

**Ownership on Linux hosts.** The entrypoint runs `chown -R www-data /data`,
which is a no-op on Docker Desktop bind mounts but matters on a Linux host —
`www-data` inside the container is uid 33.

**Not included.** TLS termination, an SMTP relay, and inbound mail piping
(`api/pipe.php`). Put a reverse proxy in front for TLS; configure outbound SMTP
and IMAP/POP fetching from within osTicket's admin panel — the `cron` service
polls for new mail every `OSTICKET_CRON_INTERVAL` seconds.
