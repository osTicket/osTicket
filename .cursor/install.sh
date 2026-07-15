#!/usr/bin/env bash
# Runs once per environment install from repo root.
set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "Installing GitHub CLI (gh) for PR create/resume..."
  # Fallback when the cloud image predates the Dockerfile gh install.
  curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg \
    | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg
  sudo chmod go+r /usr/share/keyrings/githubcli-archive-keyring.gpg
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" \
    | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null
  sudo apt-get update
  sudo apt-get install -y --no-install-recommends gh
fi

echo "Installing Node dependencies (npm ci)..."
npm ci
