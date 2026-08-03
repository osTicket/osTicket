#!/bin/sh
# Periodic task runner. api/cron.php is osTicket's local cron entry point: it
# fetches mail, fires overdue/SLA alerts and advances the search index.
set -eu

INTERVAL="${OSTICKET_CRON_INTERVAL:-60}"
CONFIG=/var/www/html/include/ost-config.php

echo "[cron] running api/cron.php every ${INTERVAL}s"

while true; do
    if grep -qi "define('OSTINSTALLED',TRUE)" "$CONFIG" 2>/dev/null; then
        su -s /bin/sh -c "php /var/www/html/api/cron.php" www-data \
            || echo "[cron] run failed (exit $?)"
    else
        echo "[cron] osTicket is not installed yet -- waiting"
    fi
    sleep "$INTERVAL"
done
