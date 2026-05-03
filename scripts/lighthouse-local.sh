#!/usr/bin/env bash
set -euo pipefail

# One-shot local Lighthouse runner.
#
# Usage:
#   bash scripts/lighthouse-local.sh
#   bash scripts/lighthouse-local.sh strict
#   bash scripts/lighthouse-local.sh balanced

PROFILE="${1:-strict}"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

if [[ ! -f .env ]]; then
  echo "[error] arquivo .env nao encontrado." >&2
  exit 1
fi

cp .env .env.bak.lighthouse-local

cleanup() {
  if [[ -n "${PHP_PID:-}" ]]; then
    kill "$PHP_PID" 2>/dev/null || true
  fi
  if [[ -f .env.bak.lighthouse-local ]]; then
    mv .env.bak.lighthouse-local .env
  fi
}
trap cleanup EXIT

echo "[step] Preparando ambiente local temporario"
sed -i 's|^APP_BASE=.*$|APP_BASE=""|' .env
sed -i 's|^APP_ENV=.*$|APP_ENV="dev"|' .env

echo "[step] Subindo servidor local PHP"
php -S 127.0.0.1:8000 -t public >/tmp/landing-lighthouse-local-php.log 2>&1 &
PHP_PID=$!

echo "[step] Aguardando servidor responder"
curl -sS --retry 30 --retry-connrefused --retry-delay 1 http://127.0.0.1:8000/ >/dev/null

echo "[step] Executando Lighthouse local ($PROFILE)"
bash scripts/lighthouse-ci.sh "$PROFILE"

echo "[ok  ] lighthouse local concluido com sucesso."
