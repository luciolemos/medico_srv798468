#!/usr/bin/env bash
set -euo pipefail

# Start local dev server with temporary .env overrides.
# - APP_BASE=""
# - APP_ENV="dev"

HOST="127.0.0.1"
PORT="8000"

usage() {
  cat <<USAGE
Usage:
  bash scripts/dev-local.sh [--host HOST] [--port PORT]

Examples:
  bash scripts/dev-local.sh
  bash scripts/dev-local.sh --port 8080
USAGE
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --host)
      HOST="${2:-}"
      shift 2
      ;;
    --port)
      PORT="${2:-}"
      shift 2
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

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

if [[ ! -f .env ]]; then
  echo "[error] arquivo .env nao encontrado" >&2
  exit 1
fi

cp .env .env.bak.dev-local

cleanup() {
  if [[ -f .env.bak.dev-local ]]; then
    mv .env.bak.dev-local .env
  fi
}
trap cleanup EXIT INT TERM

sed -i 's|^APP_BASE=.*$|APP_BASE=""|' .env
sed -i 's|^APP_ENV=.*$|APP_ENV="dev"|' .env

echo "[info] .env temporario: APP_BASE=\"\", APP_ENV=\"dev\""
echo "[info] Servidor local em http://${HOST}:${PORT}/"
echo "[info] Ao encerrar, o .env original sera restaurado automaticamente."

php -S "${HOST}:${PORT}" -t public