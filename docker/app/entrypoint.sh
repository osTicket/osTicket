#!/bin/sh
# Prepares the persistent /data volume and links it into the application tree,
# then hands off to the container command (apache2-foreground or the cron loop).
set -eu

OST_ROOT=/var/www/html
DATA_DIR=/data
DEFAULTS=/opt/osticket-defaults

log() { echo "[entrypoint] $*"; }

# ---------------------------------------------------------------------------
# Persistent layout
# ---------------------------------------------------------------------------
mkdir -p \
    "$DATA_DIR/config" \
    "$DATA_DIR/plugins" \
    "$DATA_DIR/i18n" \
    "$DATA_DIR/attachments"

# ---------------------------------------------------------------------------
# Configuration file
#
# osTicket insists on writing include/ost-config.php itself during setup, so we
# keep the real file on the volume and symlink it into place. A config with
# OSTINSTALLED=FALSE is exactly what bootstrap.php needs to redirect a fresh
# container to setup/install.php.
# ---------------------------------------------------------------------------
CONFIG_FILE="$DATA_DIR/config/ost-config.php"
if [ ! -f "$CONFIG_FILE" ]; then
    log "no ost-config.php on the volume yet -- seeding the installer template"
    cp "$DEFAULTS/ost-config.php" "$CONFIG_FILE"
fi
chmod 0664 "$CONFIG_FILE" 2>/dev/null || true
ln -sfn "$CONFIG_FILE" "$OST_ROOT/include/ost-config.php"

# ---------------------------------------------------------------------------
# Plugins and language packs
#
# Both are discovered by globbing include/plugins/* and include/i18n/* (see
# Plugin::getPlugins() and Internationalization::availableLanguages()). Copying
# them in on boot -- rather than mounting over those directories -- keeps the
# files shipped with the image intact across upgrades.
# ---------------------------------------------------------------------------
if [ -z "$(ls -A "$DATA_DIR/plugins" 2>/dev/null)" ]; then
    log "seeding $DATA_DIR/plugins from image defaults"
    cp -a "$DEFAULTS/plugins/." "$DATA_DIR/plugins/"
fi

log "installing plugins from $DATA_DIR/plugins"
cp -a "$DATA_DIR/plugins/." "$OST_ROOT/include/plugins/"

if [ -n "$(ls -A "$DATA_DIR/i18n" 2>/dev/null)" ]; then
    log "installing language packs from $DATA_DIR/i18n"
    cp -a "$DATA_DIR/i18n/." "$OST_ROOT/include/i18n/"
fi

# ---------------------------------------------------------------------------
# Runtime PHP settings driven by the environment
# ---------------------------------------------------------------------------
cat > /usr/local/etc/php/conf.d/zzz-runtime.ini <<INI
date.timezone = ${PHP_TIMEZONE:-UTC}
memory_limit = ${PHP_MEMORY_LIMIT:-256M}
max_execution_time = ${PHP_MAX_EXECUTION_TIME:-60}
upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE:-20M}
post_max_size = ${PHP_POST_MAX_SIZE:-24M}
INI

# ---------------------------------------------------------------------------
# The setup/ directory is a standing security warning once installed. Removing
# it is per-container and reversible -- flip the flag back and recreate.
# ---------------------------------------------------------------------------
if [ "${OSTICKET_REMOVE_SETUP:-false}" = "true" ] && [ -d "$OST_ROOT/setup" ]; then
    log "OSTICKET_REMOVE_SETUP=true -- removing $OST_ROOT/setup"
    rm -rf "$OST_ROOT/setup"
fi

# /data is the only tree www-data needs to write. Best effort: this is a no-op
# on bind mounts backed by Windows/macOS, where the mount already grants access.
chown -R www-data:www-data "$DATA_DIR" 2>/dev/null || true

log "ready -- exec: $*"
exec "$@"
