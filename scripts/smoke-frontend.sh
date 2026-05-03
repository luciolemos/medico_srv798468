#!/usr/bin/env bash
set -euo pipefail

# Frontend smoke test for core clinic content and analytics hooks.
#
# Usage:
#   scripts/smoke-frontend.sh --url "https://example.com/medico/"

BASE_URL=""
TIMEOUT=20

usage() {
  cat <<'USAGE'
Usage:
  scripts/smoke-frontend.sh --url URL [--timeout SECONDS]

Options:
  --url URL           Base URL da clínica (ex.: https://host/medico/)
  --timeout SECONDS   Timeout por request curl (default: 20)
  --help              Mostra esta ajuda

Notas:
  - Valida sinais de regressao no conteúdo principal e hooks de analytics no JS.
USAGE
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --url)
      BASE_URL="${2:-}"
      shift 2
      ;;
    --timeout)
      TIMEOUT="${2:-}"
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

if [[ -z "$BASE_URL" ]]; then
  echo "[error] informe --url" >&2
  usage
  exit 1
fi

if ! command -v curl >/dev/null 2>&1; then
  echo "[error] curl nao encontrado" >&2
  exit 1
fi

normalize_url() {
  local url="$1"
  if [[ "$url" == */ ]]; then
    printf '%s' "$url"
  else
    printf '%s/' "$url"
  fi
}

assert_contains() {
  local haystack="$1"
  local needle="$2"
  local name="$3"
  if grep -Fq "$needle" <<< "$haystack"; then
    echo "[ok  ] $name"
    return 0
  fi

  echo "[fail] $name (nao encontrou: $needle)" >&2
  return 1
}

BASE_URL="$(normalize_url "$BASE_URL")"
JS_URL="${BASE_URL}assets/js/landing.js"

echo "[info] Base URL: $BASE_URL"
echo "[info] JS URL: $JS_URL"

failures=0

home_html="$(curl -sS -L --max-time "$TIMEOUT" "$BASE_URL")"
assert_contains "$home_html" 'Cuidado médico' 'hero clínica presente' || failures=$((failures + 1))
assert_contains "$home_html" 'Serviços da clínica' 'serviços da clínica presentes' || failures=$((failures + 1))
assert_contains "$home_html" 'Solicite seu agendamento' 'formulário de agendamento presente' || failures=$((failures + 1))

landing_js="$(curl -sS -L --max-time "$TIMEOUT" "$JS_URL")"
assert_contains "$landing_js" 'window.dataLayer.push' 'hook dataLayer presente' || failures=$((failures + 1))
assert_contains "$landing_js" 'window.gtag("event"' 'hook gtag presente' || failures=$((failures + 1))
assert_contains "$landing_js" 'emitAnalyticsEvent("cta_click"' 'evento cta_click presente' || failures=$((failures + 1))
assert_contains "$landing_js" 'emitAnalyticsEvent("lead_form_submit_attempt"' 'evento lead_form_submit_attempt presente' || failures=$((failures + 1))

echo
echo "Summary"
echo "  failures: $failures"

if [[ $failures -gt 0 ]]; then
  exit 2
fi

echo "[ok  ] smoke frontend passou."
