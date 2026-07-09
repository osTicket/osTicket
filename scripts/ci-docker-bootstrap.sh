#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "Starting Docker services from docker-compose.yml..."
docker compose up -d

echo "Waiting for MySQL..."
for i in $(seq 1 60); do
  if docker compose exec -T db mysqladmin ping -h localhost -uroot -posticket --silent 2>/dev/null; then
    break
  fi
  if [ "$i" -eq 60 ]; then
    echo "MySQL did not become ready in time" >&2
    exit 1
  fi
  sleep 2
done

if ! docker compose exec -T db mysql -uroot -posticket -e "USE osticket" 2>/dev/null; then
  docker compose exec -T db mysql -uroot -posticket -e "CREATE DATABASE IF NOT EXISTS osticket"
fi

TABLE_COUNT="$(
  docker compose exec -T db mysql -uroot -posticket -N -e \
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='osticket' AND table_name='ost_config'" \
    2>/dev/null | tr -d '\r'
)"

if [ "${TABLE_COUNT:-0}" = "0" ]; then
  echo "Importing osTicket schema..."
  sed 's/%TABLE_PREFIX%/ost_/g' setup/inc/streams/core/install-mysql.sql \
    | docker compose exec -T db mysql -uroot -posticket osticket
fi

if [ ! -f include/ost-config.php ]; then
  echo "Creating include/ost-config.php for CI..."
  sed \
    -e "s/define('OSTINSTALLED',FALSE)/define('OSTINSTALLED',TRUE)/" \
    -e "s/'%CONFIG-SIRI'/'ci-parity-check-salt'/" \
    -e "s/'%ADMIN-EMAIL'/'ci@localhost'/" \
    -e "s/'%CONFIG-DBHOST'/'db'/" \
    -e "s/'%CONFIG-DBNAME'/'osticket'/" \
    -e "s/'%CONFIG-DBUSER'/'root'/" \
    -e "s/'%CONFIG-DBPASS'/'osticket'/" \
    -e "s/'%CONFIG-PREFIX'/'ost_'/" \
    include/ost-sampleconfig.php > include/ost-config.php
fi

SCHEDULE_COUNT="$(
  docker compose exec -T db mysql -uroot -posticket -N -e \
    "SELECT COUNT(*) FROM ost_schedule WHERE id=1" \
    2>/dev/null | tr -d '\r'
)"

if [ "${SCHEDULE_COUNT:-0}" = "0" ]; then
  echo "Loading default schedules from i18n..."
  docker compose exec -T web php legacy/seed/ci-load-schedules.php
fi

echo "Ensuring verifier-only schedule seed (id=5)..."
docker compose exec -T db mysql -uroot -posticket osticket < legacy/seed/verifier-empty-schedule.sql

docker compose exec -T db mysql -uroot -posticket osticket -e \
  "INSERT INTO ost_config (namespace, \`key\`, value, updated)
   VALUES ('core', 'default_timezone', 'America/New_York', NOW())
   ON DUPLICATE KEY UPDATE value=VALUES(value), updated=NOW()"

echo "Docker bootstrap complete."
