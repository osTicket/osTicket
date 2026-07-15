#!/usr/bin/env bash
# Runs once per environment install from repo root.
set -euo pipefail

echo "Installing Node dependencies (npm ci)..."
npm ci
