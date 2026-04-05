#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8000/}"

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

echo "[step] Node dependencies"
if [[ ! -d node_modules/@playwright/test ]]; then
  npm install --no-audit --no-fund
else
  echo "[info] @playwright/test ja instalado em node_modules."
fi

echo "[step] Playwright browsers install"
npx playwright install --with-deps chromium

echo "[step] Playwright smoke e2e"
E2E_BASE_URL="$BASE_URL" npx playwright test --config=playwright.config.js

echo "[ok  ] smoke e2e passou."
