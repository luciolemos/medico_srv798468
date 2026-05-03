#!/usr/bin/env bash
set -euo pipefail

# Batch update APP_PALETTE across multiple site .env files.
#
# Usage:
#   scripts/set-palettes.sh --dry-run medico=blue medico-red=red
#   scripts/set-palettes.sh --base-dir /var/www medico=blue medico-amber=amber
#   scripts/set-palettes.sh --from-file mappings.txt
#
# Mapping file format (one per line):
#   slug=palette
#   # comments are ignored

BASE_DIR="/var/www"
DRY_RUN=0
FROM_FILE=""

ALLOWED="blue red emerald amber violet"

usage() {
  cat <<USAGE
Usage:
  scripts/set-palettes.sh [--base-dir DIR] [--dry-run] [--from-file FILE] [slug=palette ...]

Options:
  --base-dir DIR   Base directory containing site folders (default: /var/www)
  --dry-run        Show planned changes without writing files
  --from-file FILE Read mappings from file (slug=palette per line)
  --help           Show this help

Allowed palettes:
  blue, red, emerald, amber, violet
USAGE
}

is_allowed_palette() {
  local value="$1"
  for p in $ALLOWED; do
    [[ "$p" == "$value" ]] && return 0
  done
  return 1
}

ensure_palette_in_env() {
  local env_file="$1"
  local palette="$2"

  if grep -q '^APP_PALETTE=' "$env_file"; then
    if [[ "$DRY_RUN" -eq 1 ]]; then
      echo "[dry ] update $env_file -> APP_PALETTE=\"$palette\""
    else
      sed -i "s|^APP_PALETTE=.*$|APP_PALETTE=\"$palette\"|" "$env_file"
      echo "[ok  ] update $env_file -> APP_PALETTE=\"$palette\""
    fi
  else
    if [[ "$DRY_RUN" -eq 1 ]]; then
      echo "[dry ] append $env_file -> APP_PALETTE=\"$palette\""
    else
      printf '\nAPP_PALETTE="%s"\n' "$palette" >> "$env_file"
      echo "[ok  ] append $env_file -> APP_PALETTE=\"$palette\""
    fi
  fi
}

MAPPINGS=()

while [[ $# -gt 0 ]]; do
  case "$1" in
    --base-dir)
      BASE_DIR="${2:-}"
      shift 2
      ;;
    --dry-run)
      DRY_RUN=1
      shift
      ;;
    --from-file)
      FROM_FILE="${2:-}"
      shift 2
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *=*)
      MAPPINGS+=("$1")
      shift
      ;;
    *)
      echo "[error] Unknown argument: $1" >&2
      usage
      exit 1
      ;;
  esac
done

if [[ -n "$FROM_FILE" ]]; then
  if [[ ! -f "$FROM_FILE" ]]; then
    echo "[error] Mapping file not found: $FROM_FILE" >&2
    exit 1
  fi
  while IFS= read -r line || [[ -n "$line" ]]; do
    line="${line#${line%%[![:space:]]*}}" # ltrim
    line="${line%${line##*[![:space:]]}}" # rtrim
    [[ -z "$line" || "${line:0:1}" == "#" ]] && continue
    if [[ "$line" == *=* ]]; then
      MAPPINGS+=("$line")
    else
      echo "[warn] ignored invalid line: $line"
    fi
  done < "$FROM_FILE"
fi

if [[ ${#MAPPINGS[@]} -eq 0 ]]; then
  echo "[error] No mappings provided." >&2
  usage
  exit 1
fi

if [[ ! -d "$BASE_DIR" ]]; then
  echo "[error] Base directory not found: $BASE_DIR" >&2
  exit 1
fi

updated=0
skipped=0
errors=0

for map in "${MAPPINGS[@]}"; do
  slug="${map%%=*}"
  palette="${map#*=}"

  if [[ -z "$slug" || -z "$palette" ]]; then
    echo "[error] invalid mapping: $map" >&2
    errors=$((errors + 1))
    continue
  fi

  if ! is_allowed_palette "$palette"; then
    echo "[error] invalid palette '$palette' for slug '$slug' (allowed: $ALLOWED)" >&2
    errors=$((errors + 1))
    continue
  fi

  env_file="$BASE_DIR/$slug/.env"
  if [[ ! -f "$env_file" ]]; then
    echo "[warn] .env not found for slug '$slug' at $env_file"
    skipped=$((skipped + 1))
    continue
  fi

  ensure_palette_in_env "$env_file" "$palette"
  updated=$((updated + 1))
done

echo
echo "Summary"
echo "  updated: $updated"
echo "  skipped: $skipped"
echo "  errors:  $errors"

if [[ $errors -gt 0 ]]; then
  exit 2
fi
