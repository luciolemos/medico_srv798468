#!/usr/bin/env bash
set -euo pipefail

# Creates temporary landings for known presets and validates the generated output.

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

usage() {
  cat <<'USAGE'
Usage:
  bash scripts/audit-generated-landings.sh [SLUG...]

When no slug is provided, all slugs from config/presets/niches.php are audited.
Each landing is created in a temporary directory, validated, and removed.
USAGE
}

preset_slugs() {
  php -r '
    $file = $argv[1];
    if (!is_file($file)) {
        exit(1);
    }
    $presets = require $file;
    if (!is_array($presets)) {
        exit(1);
    }
    foreach (array_keys($presets) as $slug) {
        if (is_string($slug)) {
            echo $slug, PHP_EOL;
        }
    }
  ' "$PROJECT_ROOT/config/presets/niches.php"
}

env_value() {
  local file="$1"
  local key="$2"

  awk -F '=' -v key="$key" '
    $1 == key {
      value = substr($0, index($0, "=") + 1)
      gsub(/^"/, "", value)
      gsub(/"$/, "", value)
      print value
      exit
    }
  ' "$file"
}

fail() {
  echo "[fail] $*" >&2
  exit 1
}

if [[ "${1:-}" == "--help" || "${1:-}" == "-h" ]]; then
  usage
  exit 0
fi

if [[ "$#" -gt 0 ]]; then
  SLUGS=("$@")
else
  mapfile -t SLUGS < <(preset_slugs)
fi

if [[ "${#SLUGS[@]}" -eq 0 ]]; then
  fail "nenhum slug para auditar"
fi

mapfile -t ALL_PRESET_SLUGS < <(preset_slugs)
TMP_ROOT="$(mktemp -d /tmp/landing-audit-XXXXXX)"
trap 'rm -rf "$TMP_ROOT"' EXIT

for slug in "${SLUGS[@]}"; do
  if [[ ! "$slug" =~ ^[a-z0-9][a-z0-9-]*$ ]]; then
    fail "slug inválido: $slug"
  fi

  target="$TMP_ROOT/$slug"
  echo "[step] auditando geração: $slug"
  bash scripts/create-landing.sh "$slug" --target "$target" >/dev/null

  env_file="$target/.env"
  content_file="$(env_value "$env_file" APP_CONTENT_FILE)"
  app_slug="$(env_value "$env_file" APP_SLUG)"

  [[ "$app_slug" == "$slug" ]] || fail "APP_SLUG divergente para $slug: $app_slug"
  [[ -n "$content_file" ]] || fail "APP_CONTENT_FILE vazio para $slug"
  [[ -f "$target/config/content/${content_file}.php" ]] || fail "conteúdo ativo não encontrado para $slug: $content_file"

  php "$target/scripts/validate-landing-content.php" \
    --project-root "$target" \
    --content "$content_file" \
    --slug "$slug" \
    --strict >/dev/null

  expected_content_count=1
  if [[ "$content_file" != "landing" ]]; then
    expected_content_count=2
  fi
  actual_content_count="$(find "$target/config/content" -maxdepth 1 -type f -name '*.php' | wc -l | tr -d '[:space:]')"
  [[ "$actual_content_count" == "$expected_content_count" ]] \
    || fail "$slug gerou $actual_content_count arquivos de conteúdo; esperado $expected_content_count"

  for required in \
    "$target/public/assets/img/hero/${slug}-640.webp" \
    "$target/public/assets/img/hero/${slug}-960.webp" \
    "$target/public/assets/img/hero/${slug}-1896.webp" \
    "$target/public/assets/img/hero/${slug}-mobile-640.webp" \
    "$target/public/assets/img/social/${slug}-og.jpg" \
    "$target/public/assets/img/${slug}-mark.svg"; do
    [[ -f "$required" ]] || fail "asset obrigatório ausente para $slug: ${required#$target/}"
  done

  if [[ "$slug" != "medico" ]]; then
    ! rg -n "assets/img/hero/medico-|assets/img/social/medico-og|assets/img/medico-mark\\.svg|assets/img/clinic-mark\\.svg|assets/img/img_default_640\\.webp" \
      "$target/config" "$target/views" "$target/src" >/tmp/landing-audit-stale.txt \
      || fail "$slug contém fallback/asset stale: $(head -1 /tmp/landing-audit-stale.txt)"
  fi

  for other_slug in "${ALL_PRESET_SLUGS[@]}"; do
    if [[ "$other_slug" == "$slug" ]]; then
      continue
    fi

    leftover="$(find "$target/public/assets/img/hero" "$target/public/assets/img/social" "$target/public/assets/img" -maxdepth 1 -type f -name "${other_slug}-*" -print -quit)"
    [[ -z "$leftover" ]] || fail "$slug carregou asset de outro nicho: ${leftover#$target/}"

    leftover_content="$target/config/content/${other_slug}.php"
    [[ ! -f "$leftover_content" ]] || fail "$slug carregou conteúdo de outro nicho: ${leftover_content#$target/}"
  done

  echo "[ok  ] $slug: conteúdo=$content_file"
done

echo "[ok  ] auditoria de landings geradas passou."
