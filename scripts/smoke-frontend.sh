#!/usr/bin/env bash
set -euo pipefail

# Frontend smoke test for SSR copy mode and analytics hooks.
#
# Usage:
#   scripts/smoke-frontend.sh --url "https://srv798468.hstgr.cloud/natalcloud/"

BASE_URL=""
TIMEOUT=20

usage() {
  cat <<'USAGE'
Usage:
  scripts/smoke-frontend.sh --url URL [--timeout SECONDS]

Options:
  --url URL           Base URL da landing (ex.: https://host/natalcloud/)
  --timeout SECONDS   Timeout por request curl (default: 20)
  --help              Mostra esta ajuda

Notas:
  - Valida sinais de regressao em SSR de copy mode e hooks de analytics no JS.
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

html_growth="$(curl -sS -L --max-time "$TIMEOUT" "${BASE_URL}?copy=growth")"
assert_contains "$html_growth" 'data-copy-mode="growth"' 'SSR copy mode growth' || failures=$((failures + 1))
assert_contains "$html_growth" 'Copy: Growth' 'label de copy mode growth' || failures=$((failures + 1))

html_invalid_copy="$(curl -sS -L --max-time "$TIMEOUT" "${BASE_URL}?copy=invalido")"
assert_contains "$html_invalid_copy" 'data-copy-mode="soft"' 'fallback SSR copy mode soft' || failures=$((failures + 1))

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