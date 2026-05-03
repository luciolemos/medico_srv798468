#!/usr/bin/env bash
set -euo pipefail

# Security + build quality gate.

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

echo "[step] Composer validate"
composer validate --no-check-publish

echo "[step] Composer audit"
composer audit

echo "[step] PHP lint"
while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null
done < <(find src public routes scripts -type f -name '*.php' -print0)

echo "[step] Landing content validation"
php scripts/validate-landing-content.php --project-root "$PROJECT_ROOT" --content landing

echo "[step] Shell script lint"
while IFS= read -r -d '' file; do
  bash -n "$file"
done < <(find scripts -type f -name '*.sh' -print0)

echo "[ok  ] quality gate passou."
