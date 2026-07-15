#!/usr/bin/env bash
# Starts Docker daemon and bootstraps osTicket Compose (web + db + schema/seeds).
# Idempotent — safe on every cloud agent wake.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "Starting Docker daemon..."
if ! sudo service docker status >/dev/null 2>&1; then
  sudo service docker start
fi

echo "Waiting for Docker socket..."
for i in $(seq 1 30); do
  if docker info >/dev/null 2>&1; then
    echo "Docker is ready."
    break
  fi
  if [ "$i" -eq 30 ]; then
    echo "Docker daemon did not become ready in time" >&2
    exit 1
  fi
  sleep 1
done

bash scripts/ci-docker-bootstrap.sh
