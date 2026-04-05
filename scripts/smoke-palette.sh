#!/usr/bin/env bash
set -euo pipefail

# Smoke test for SSR palette behavior (query/cookie/default).
#
# Usage:
#   scripts/smoke-palette.sh --url "https://srv798468.hstgr.cloud/natalcloud/"
#   scripts/smoke-palette.sh --url "http://localhost/natalcloud/" --default blue

BASE_URL=""
DEFAULT_PALETTE="blue"
TIMEOUT=20

usage() {
  cat <<'USAGE'
Usage:
  scripts/smoke-palette.sh --url URL [--default PALETTE] [--timeout SECONDS]

Options:
  --url URL           Base URL da landing (ex.: https://host/natalcloud/)
  --default PALETTE   Paleta esperada sem query/cookie (default: blue)
  --timeout SECONDS   Timeout por request curl (default: 20)
  --help              Mostra esta ajuda

Notas:
  - Este script valida comportamento SSR (backend + HTML inicial).
  - Nao valida efeitos client-side que dependem de JavaScript do navegador.
USAGE
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --url)
      BASE_URL="${2:-}"
      shift 2
      ;;
    --default)
      DEFAULT_PALETTE="${2:-}"
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

extract_palette_from_html() {
  local html="$1"
  printf '%s' "$html" \
    | grep -oE '/assets/css/palettes/[a-z]+\.css' \
    | head -n1 \
    | sed -E 's@.*/palettes/([a-z]+)\.css@\1@'
}

request_html() {
  local url="$1"
  local cookie_file="${2:-}"
  if [[ -n "$cookie_file" ]]; then
    curl -sS -L --max-time "$TIMEOUT" -b "$cookie_file" "$url"
  else
    curl -sS -L --max-time "$TIMEOUT" "$url"
  fi
}

assert_palette() {
  local name="$1"
  local got="$2"
  local expected="$3"
  if [[ "$got" == "$expected" ]]; then
    echo "[ok  ] $name -> $got"
    return 0
  fi
  echo "[fail] $name -> esperado '$expected', obtido '$got'" >&2
  return 1
}

BASE_URL="$(normalize_url "$BASE_URL")"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

failures=0
cookie_emerald="$TMP_DIR/cookie_emerald.txt"
cookie_red="$TMP_DIR/cookie_red.txt"

echo "[info] Base URL: $BASE_URL"
echo "[info] Default esperado: $DEFAULT_PALETTE"

# 1) Query valida deve prevalecer
html_q_red="$(request_html "${BASE_URL}?palette=red")"
p_q_red="$(extract_palette_from_html "$html_q_red")"
assert_palette "query valida (red)" "$p_q_red" "red" || failures=$((failures + 1))

# 2) Query deve prevalecer sobre cookie
curl -sS -L --max-time "$TIMEOUT" -c "$cookie_emerald" "${BASE_URL}?palette=emerald" >/dev/null
html_q_blue_cookie_emerald="$(request_html "${BASE_URL}?palette=blue" "$cookie_emerald")"
p_q_blue_cookie_emerald="$(extract_palette_from_html "$html_q_blue_cookie_emerald")"
assert_palette "query sobre cookie" "$p_q_blue_cookie_emerald" "blue" || failures=$((failures + 1))

# 3) Cookie deve prevalecer sem query
curl -sS -L --max-time "$TIMEOUT" -c "$cookie_red" "${BASE_URL}?palette=red" >/dev/null
html_cookie_red="$(request_html "$BASE_URL" "$cookie_red")"
p_cookie_red="$(extract_palette_from_html "$html_cookie_red")"
assert_palette "cookie sem query" "$p_cookie_red" "red" || failures=$((failures + 1))

# 4) Query invalida deve cair no default SSR esperado
html_q_invalid="$(request_html "${BASE_URL}?palette=invalida")"
p_q_invalid="$(extract_palette_from_html "$html_q_invalid")"
assert_palette "query invalida" "$p_q_invalid" "$DEFAULT_PALETTE" || failures=$((failures + 1))

# 5) Sem query/cookie deve usar default SSR esperado
html_default="$(request_html "$BASE_URL")"
p_default="$(extract_palette_from_html "$html_default")"
assert_palette "sem query/cookie" "$p_default" "$DEFAULT_PALETTE" || failures=$((failures + 1))

echo
echo "Summary"
echo "  failures: $failures"

if [[ $failures -gt 0 ]]; then
  exit 2
fi

echo "[ok  ] smoke SSR de paleta passou."