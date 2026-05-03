#!/usr/bin/env bash
set -euo pipefail

# Unified local test runner.
#
# Usage:
#   scripts/run-tests.sh --url "https://example.com/medico/"
#   scripts/run-tests.sh --url "http://127.0.0.1:8000/" --with-contact-success

BASE_URL=""
DEFAULT_PALETTE="blue"
WITH_CONTACT_SUCCESS=0

usage() {
  cat <<'USAGE'
Usage:
  scripts/run-tests.sh --url URL [--default-palette PALETTE] [--with-contact-success]

Options:
  --url URL                Base URL da landing
  --default-palette VALUE  Paleta default esperada no SSR (default: blue)
  --with-contact-success   Executa smoke de contato com sucesso SMTP
  --help                   Mostra esta ajuda
USAGE
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --url)
      BASE_URL="${2:-}"
      shift 2
      ;;
    --default-palette)
      DEFAULT_PALETTE="${2:-}"
      shift 2
      ;;
    --with-contact-success)
      WITH_CONTACT_SUCCESS=1
      shift
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      echo "[error] argumento desconhecido: $1" >&2
      usage
      exit 1
      ;;
  esac
done

if [[ -z "$BASE_URL" ]]; then
  echo "[error] informe --url" >&2
  usage
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_ROOT"

chmod +x scripts/smoke-palette.sh scripts/smoke-contact.sh scripts/smoke-frontend.sh scripts/smoke-contact-success.sh

echo "[step] Unit tests"
php scripts/test-unit.php

echo "[step] Smoke palette"
bash scripts/smoke-palette.sh --url "$BASE_URL" --default "$DEFAULT_PALETTE"

echo "[step] Smoke contact (invalid payload)"
bash scripts/smoke-contact.sh --url "$BASE_URL"

echo "[step] Smoke frontend"
bash scripts/smoke-frontend.sh --url "$BASE_URL"

if [[ "$WITH_CONTACT_SUCCESS" -eq 1 ]]; then
  echo "[step] Smoke contact success (SMTP)"
  bash scripts/smoke-contact-success.sh --url "$BASE_URL"
else
  echo "[skip] Smoke contact success (use --with-contact-success when SMTP sandbox is available)"
fi

echo "[ok  ] all selected tests finished successfully."
