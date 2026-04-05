#!/usr/bin/env bash
set -euo pipefail

# Smoke test for contact form HTTP behavior.
#
# Usage:
#   scripts/smoke-contact.sh --url "https://srv798468.hstgr.cloud/natalcloud/"
#   scripts/smoke-contact.sh --url "http://127.0.0.1:8000/"

BASE_URL=""
TIMEOUT=20

usage() {
  cat <<'USAGE'
Usage:
  scripts/smoke-contact.sh --url URL [--timeout SECONDS]

Options:
  --url URL           Base URL da landing (ex.: https://host/natalcloud/)
  --timeout SECONDS   Timeout por request curl (default: 20)
  --help              Mostra esta ajuda

Notas:
  - Este script valida o fluxo HTTP do POST /contato com payload invalido.
  - O CSRF e obtido via GET da home antes do POST.
  - Resultado esperado: redirect 302 para #form-orcamento com erro de validacao.
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

BASE_URL="$(normalize_url "$BASE_URL")"
POST_URL="${BASE_URL}contato"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT
headers_file="$TMP_DIR/headers.txt"
cookie_file="$TMP_DIR/cookies.txt"
home_html_file="$TMP_DIR/home.html"

extract_csrf_token() {
  local html_file="$1"
  tr '\n' ' ' < "$html_file" | sed -n 's/.*name="csrf_token"[^>]*value="\([^"]*\)".*/\1/p' | head -n 1
}

echo "[info] Base URL: $BASE_URL"
echo "[info] POST URL: $POST_URL"
echo "[step] Captura CSRF da home"

curl -sS --max-time "$TIMEOUT" \
  -c "$cookie_file" \
  -b "$cookie_file" \
  -o "$home_html_file" \
  "$BASE_URL"

csrf_token="$(extract_csrf_token "$home_html_file")"
if [[ -z "$csrf_token" ]]; then
  echo "[fail] nao foi possivel extrair csrf_token da home" >&2
  exit 2
fi

curl -sS --max-time "$TIMEOUT" \
  -D "$headers_file" \
  -c "$cookie_file" \
  -b "$cookie_file" \
  -o /dev/null \
  -X POST "$POST_URL" \
  --data-urlencode "csrf_token=$csrf_token" \
  --data-urlencode "nome=" \
  --data-urlencode "telefone=" \
  --data-urlencode "email=email-invalido" \
  --data-urlencode "empresa=" \
  --data-urlencode "mensagem="

status="$(awk 'toupper($1) ~ /^HTTP\// {code=$2} END{print code}' "$headers_file")"
location="$(awk 'BEGIN{IGNORECASE=1} /^Location:/ {sub(/^Location:[[:space:]]*/, "", $0); gsub(/\r/, "", $0); loc=$0} END{print loc}' "$headers_file")"
has_session_cookie=0
if grep -q 'PHPSESSID' "$cookie_file"; then
  has_session_cookie=1
fi

html_after_post="$(curl -sS -L --max-time "$TIMEOUT" -b "$cookie_file" "$BASE_URL")"

failures=0

if [[ "$status" == "302" ]]; then
  echo "[ok  ] status 302"
else
  echo "[fail] status esperado 302, obtido '$status'" >&2
  failures=$((failures + 1))
fi

if [[ "$location" == *"#form-orcamento"* ]]; then
  echo "[ok  ] redirect para #form-orcamento"
else
  echo "[fail] redirect esperado para #form-orcamento, obtido '$location'" >&2
  failures=$((failures + 1))
fi

if [[ "$has_session_cookie" -eq 1 ]]; then
  echo "[ok  ] cookie de sessao emitido"
else
  echo "[fail] cookie de sessao PHPSESSID nao encontrado" >&2
  failures=$((failures + 1))
fi

if grep -Fq 'data-form-result-type="error"' <<< "$html_after_post"; then
  echo "[ok  ] tipo de resultado error presente"
else
  echo "[fail] nao encontrou data-form-result-type=error no HTML" >&2
  failures=$((failures + 1))
fi

if grep -Fq 'Revise os campos e tente novamente.' <<< "$html_after_post"; then
  echo "[ok  ] mensagem de erro de validacao renderizada"
else
  echo "[fail] nao encontrou mensagem de erro de validacao esperada no HTML" >&2
  failures=$((failures + 1))
fi

echo
echo "Summary"
echo "  failures: $failures"

if [[ $failures -gt 0 ]]; then
  exit 2
fi

echo "[ok  ] smoke de contato passou."
