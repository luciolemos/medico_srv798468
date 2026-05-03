#!/usr/bin/env bash
set -euo pipefail

# Create a clean landing copy from this prototype.
#
# Usage:
#   bash scripts/create-landing.sh pediatria --name "Clínica Pediátrica" --mark P --palette emerald --request-prefix PED
#   bash scripts/create-landing.sh veterinaria --target /var/www/veterinaria --name "Clínica Veterinária"

SOURCE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_DIR="/var/www"
SLUG=""
TARGET_DIR=""
APP_NAME=""
APP_MARK=""
APP_PALETTE=""
REQUEST_PREFIX=""
DRY_RUN=0

usage() {
  cat <<'USAGE'
Usage:
  bash scripts/create-landing.sh SLUG [options]

Options:
  --target DIR             Diretório de destino. Default: /var/www/SLUG
  --base-dir DIR           Diretório base quando --target não for informado. Default: /var/www
  --name NAME              Nome público da landing. Default: slug em formato título
  --mark TEXT              Marca curta no nav/favicon textual. Default: primeira letra do nome
  --palette VALUE          Paleta inicial: blue, red, emerald, amber ou violet. Default: preset do nicho ou blue
  --request-prefix VALUE   Prefixo de protocolo, por exemplo PED, VET, ODO. Default: preset do nicho ou slug em maiúsculas
  --dry-run                Mostra o que seria feito sem copiar arquivos
  --help                   Mostra esta ajuda

O script nunca sobrescreve diretório existente com arquivos.
USAGE
}

is_allowed_palette() {
  case "$1" in
    blue|red|emerald|amber|violet) return 0 ;;
    *) return 1 ;;
  esac
}

titleize_slug() {
  local value="$1"
  value="${value//-/ }"
  value="${value//_/ }"
  awk '{
    for (i = 1; i <= NF; i++) {
      $i = toupper(substr($i, 1, 1)) substr($i, 2)
    }
    print
  }' <<< "$value"
}

sed_escape() {
  printf '%s' "$1" | sed 's/[\\&|]/\\&/g'
}

preset_value() {
  local key="$1"
  php -r '
    $file = $argv[1];
    $slug = $argv[2];
    $key = $argv[3];
    if (!is_file($file)) {
        exit;
    }
    $presets = require $file;
    $value = is_array($presets) ? ($presets[$slug][$key] ?? "") : "";
    if (is_scalar($value)) {
        echo $value;
    }
  ' "$SOURCE_ROOT/config/presets/niches.php" "$SLUG" "$key"
}

set_env_value() {
  local file="$1"
  local key="$2"
  local value="$3"
  local escaped
  escaped="$(sed_escape "$value")"

  if grep -q "^${key}=" "$file"; then
    sed -i "s|^${key}=.*$|${key}=\"${escaped}\"|" "$file"
  else
    printf '%s="%s"\n' "$key" "$value" >> "$file"
  fi
}

apply_content_preset() {
  local content_file="$TARGET_DIR/config/content/landing.php"
  local typography
  local schema_type

  if [[ ! -f "$content_file" ]]; then
    return
  fi

  typography="$(preset_value typography)"
  schema_type="$(preset_value schema_type)"

  if [[ -n "$typography" ]]; then
    sed -i "s|'profile' => '[^']*'|'profile' => '$(sed_escape "$typography")'|g" "$content_file"
  fi

  if [[ -n "$schema_type" ]]; then
    sed -i "/'schema' => \\[/,/^        \\],/ s|'type' => '[^']*'|'type' => '$(sed_escape "$schema_type")'|1" "$content_file"
  fi
}

rename_standard_assets() {
  local content_file="$TARGET_DIR/config/content/landing.php"

  if [[ "$SLUG" == "medico" ]]; then
    return
  fi

  if [[ -f "$TARGET_DIR/public/assets/img/hero/medico-640.webp" ]]; then
    mv "$TARGET_DIR/public/assets/img/hero/medico-640.webp" "$TARGET_DIR/public/assets/img/hero/${SLUG}-640.webp"
  fi
  if [[ -f "$TARGET_DIR/public/assets/img/hero/medico-960.webp" ]]; then
    mv "$TARGET_DIR/public/assets/img/hero/medico-960.webp" "$TARGET_DIR/public/assets/img/hero/${SLUG}-960.webp"
  fi
  if [[ -f "$TARGET_DIR/public/assets/img/hero/medico-1896.webp" ]]; then
    mv "$TARGET_DIR/public/assets/img/hero/medico-1896.webp" "$TARGET_DIR/public/assets/img/hero/${SLUG}-1896.webp"
  fi
  if [[ -f "$TARGET_DIR/public/assets/img/hero/medico-mobile-640.webp" ]]; then
    mv "$TARGET_DIR/public/assets/img/hero/medico-mobile-640.webp" "$TARGET_DIR/public/assets/img/hero/${SLUG}-mobile-640.webp"
  fi
  if [[ -f "$TARGET_DIR/public/assets/img/social/medico-og.jpg" ]]; then
    mv "$TARGET_DIR/public/assets/img/social/medico-og.jpg" "$TARGET_DIR/public/assets/img/social/${SLUG}-og.jpg"
  fi
  if [[ -f "$TARGET_DIR/public/assets/img/social/medico-og.webp" ]]; then
    mv "$TARGET_DIR/public/assets/img/social/medico-og.webp" "$TARGET_DIR/public/assets/img/social/${SLUG}-og.webp"
  fi

  if [[ -f "$content_file" ]]; then
    sed -i "s|assets/img/hero/medico-|assets/img/hero/$(sed_escape "$SLUG")-|g" "$content_file"
    sed -i "s|assets/img/social/medico-og.jpg|assets/img/social/$(sed_escape "$SLUG")-og.jpg|g" "$content_file"
    sed -i "s|assets/img/social/medico-og.webp|assets/img/social/$(sed_escape "$SLUG")-og.webp|g" "$content_file"
  fi
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --target)
      TARGET_DIR="${2:-}"
      shift 2
      ;;
    --base-dir)
      BASE_DIR="${2:-}"
      shift 2
      ;;
    --name)
      APP_NAME="${2:-}"
      shift 2
      ;;
    --mark)
      APP_MARK="${2:-}"
      shift 2
      ;;
    --palette)
      APP_PALETTE="${2:-}"
      shift 2
      ;;
    --request-prefix)
      REQUEST_PREFIX="${2:-}"
      shift 2
      ;;
    --dry-run)
      DRY_RUN=1
      shift
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    -*)
      echo "[error] argumento desconhecido: $1" >&2
      usage
      exit 1
      ;;
    *)
      if [[ -n "$SLUG" ]]; then
        echo "[error] slug duplicado: $1" >&2
        usage
        exit 1
      fi
      SLUG="$1"
      shift
      ;;
  esac
done

if [[ -z "$SLUG" ]]; then
  echo "[error] informe o SLUG" >&2
  usage
  exit 1
fi

if [[ ! "$SLUG" =~ ^[a-z0-9][a-z0-9-]*$ ]]; then
  echo "[error] slug inválido: use letras minúsculas, números e hífen" >&2
  exit 1
fi

if [[ -z "$APP_PALETTE" ]]; then
  APP_PALETTE="$(preset_value palette)"
  APP_PALETTE="${APP_PALETTE:-blue}"
fi

if ! is_allowed_palette "$APP_PALETTE"; then
  echo "[error] paleta inválida: $APP_PALETTE" >&2
  exit 1
fi

if [[ -z "$TARGET_DIR" ]]; then
  TARGET_DIR="${BASE_DIR%/}/${SLUG}"
fi

if [[ -z "$APP_NAME" ]]; then
  APP_NAME="$(preset_value name)"
  APP_NAME="${APP_NAME:-$(titleize_slug "$SLUG")}"
fi

if [[ -z "$APP_MARK" ]]; then
  APP_MARK="$(preset_value mark)"
  APP_MARK="${APP_MARK:-$(printf '%s' "$APP_NAME" | sed 's/^[[:space:]]*//' | cut -c 1 | tr '[:lower:]' '[:upper:]')}"
fi

if [[ -z "$REQUEST_PREFIX" ]]; then
  REQUEST_PREFIX="$(preset_value request_prefix)"
  REQUEST_PREFIX="${REQUEST_PREFIX:-$(printf '%s' "$SLUG" | tr -cd '[:alnum:]' | tr '[:lower:]' '[:upper:]' | cut -c 1-6)}"
fi

if [[ -e "$TARGET_DIR" ]] && [[ -n "$(find "$TARGET_DIR" -mindepth 1 -maxdepth 1 -print -quit 2>/dev/null)" ]]; then
  echo "[error] destino já existe e não está vazio: $TARGET_DIR" >&2
  exit 1
fi

echo "[info] origem:  $SOURCE_ROOT"
echo "[info] destino: $TARGET_DIR"
echo "[info] slug:    $SLUG"
echo "[info] nome:    $APP_NAME"
echo "[info] paleta:  $APP_PALETTE"
echo "[info] prefixo: $REQUEST_PREFIX"

if [[ "$DRY_RUN" -eq 1 ]]; then
  echo "[dry ] nenhum arquivo foi copiado"
  exit 0
fi

mkdir -p "$TARGET_DIR"

(
  cd "$SOURCE_ROOT"
  tar \
    --exclude='./.git' \
    --exclude='./.env' \
    --exclude='./.env.bak*' \
    --exclude='./.env.local' \
    --exclude='./vendor' \
    --exclude='./node_modules' \
    --exclude='./storage/cache' \
    --exclude='./storage/logs' \
    --exclude='./storage/rate-limit' \
    --exclude='./.phpunit.cache' \
    --exclude='./test-results' \
    --exclude='./playwright-report' \
    -cf - .
) | (
  cd "$TARGET_DIR"
  tar -xf -
)

mkdir -p "$TARGET_DIR/storage/cache/twig" "$TARGET_DIR/storage/logs" "$TARGET_DIR/storage/rate-limit"
touch "$TARGET_DIR/storage/cache/twig/.gitkeep" "$TARGET_DIR/storage/logs/.gitkeep" "$TARGET_DIR/storage/rate-limit/.gitkeep"
rename_standard_assets
apply_content_preset

cp "$TARGET_DIR/.env.example" "$TARGET_DIR/.env"
set_env_value "$TARGET_DIR/.env" "APP_NAME" "$APP_NAME"
set_env_value "$TARGET_DIR/.env" "APP_MARK" "$APP_MARK"
set_env_value "$TARGET_DIR/.env" "APP_BADGE" "$APP_NAME"
set_env_value "$TARGET_DIR/.env" "APP_PAGE_TITLE" "$APP_NAME | Atendimento com hora marcada"
set_env_value "$TARGET_DIR/.env" "APP_SLUG" "$SLUG"
set_env_value "$TARGET_DIR/.env" "APP_REQUEST_PREFIX" "$REQUEST_PREFIX"
set_env_value "$TARGET_DIR/.env" "APP_CONTENT_FILE" "landing"
set_env_value "$TARGET_DIR/.env" "APP_BASE" "/$SLUG"
set_env_value "$TARGET_DIR/.env" "APP_PALETTE" "$APP_PALETTE"

printf '# slug=palette\n%s=%s\n' "$SLUG" "$APP_PALETTE" > "$TARGET_DIR/palettes.map"

echo "[ok  ] landing criada em $TARGET_DIR"
echo "[next] ajuste config/content/landing.php e imagens, rode composer install e valide com php scripts/validate-landing-content.php"
