#!/usr/bin/env bash
set -euo pipefail

# Smoke test for successful contact flow with SMTP available.
#
# Usage:
#   scripts/smoke-contact-success.sh --url "http://127.0.0.1:8000/"

BASE_URL=""
TIMEOUT=20

usage() {
  cat <<'USAGE'
Usage:
  scripts/smoke-contact-success.sh --url URL [--timeout SECONDS]

Options:
  --url URL           Base URL da landing (ex.: http://127.0.0.1:8000/)
  --timeout SECONDS   Timeout por request curl (default: 20)
  --help              Mostra esta ajuda

Notas:
  - Exige backend com SMTP funcional (sandbox/local recomendado).
  - Este teste faz POST valido em /contato.
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

echo "[info] Base URL: $BASE_URL"
echo "[info] POST URL: $POST_URL"

curl -sS --max-time "$TIMEOUT" \
  -D "$headers_file" \
  -c "$cookie_file" \
  -o /dev/null \
  -X POST "$POST_URL" \
  --data-urlencode "nome=Contato Smoke" \
  --data-urlencode "telefone=(84) 99999-0000" \
  --data-urlencode "email=smoke@example.test" \
  --data-urlencode "empresa=NatalCloud QA" \
  --data-urlencode "mensagem=Mensagem automatizada de smoke test com SMTP sandbox."

status="$(awk 'toupper($1) ~ /^HTTP\// {code=$2} END{print code}' "$headers_file")"
location="$(awk 'BEGIN{IGNORECASE=1} /^Location:/ {sub(/^Location:[[:space:]]*/, "", $0); gsub(/\r/, "", $0); loc=$0} END{print loc}' "$headers_file")"

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

html_after_post="$(curl -sS -L --max-time "$TIMEOUT" -b "$cookie_file" "$BASE_URL")"

if grep -Fq 'data-form-result-event="lead_form_submit_success"' <<< "$html_after_post"; then
  echo "[ok  ] evento de sucesso presente no HTML"
else
  echo "[fail] nao encontrou evento lead_form_submit_success no HTML" >&2
  failures=$((failures + 1))
fi

if grep -Fq 'data-form-result-type="success"' <<< "$html_after_post"; then
  echo "[ok  ] tipo de resultado success presente"
else
  echo "[fail] nao encontrou data-form-result-type=success" >&2
  failures=$((failures + 1))
fi

if grep -Fq 'Recebemos sua solicitação. Protocolo:' <<< "$html_after_post"; then
  echo "[ok  ] mensagem de sucesso renderizada"
else
  echo "[fail] mensagem de sucesso nao encontrada no HTML" >&2
  failures=$((failures + 1))
fi

echo
echo "Summary"
echo "  failures: $failures"

if [[ $failures -gt 0 ]]; then
  exit 2
fi

echo "[ok  ] smoke de contato com sucesso SMTP passou."