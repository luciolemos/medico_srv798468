#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

PROFILE="${1:-balanced}"
case "$PROFILE" in
	balanced)
		CONFIG_FILE=".lighthouserc.json"
		;;
	strict)
		CONFIG_FILE=".lighthouserc.strict.json"
		;;
	*)
		echo "[error] perfil invalido: $PROFILE (use balanced ou strict)" >&2
		exit 1
		;;
esac

ensure_chrome() {
	if command -v google-chrome >/dev/null 2>&1; then
		return 0
	fi
	if command -v chromium >/dev/null 2>&1; then
		return 0
	fi
	if command -v chromium-browser >/dev/null 2>&1; then
		return 0
	fi

	echo "[info] Chrome/Chromium nao encontrado. Instalando Chromium via Playwright..."
	npx -y playwright@1.53.0 install chromium

	local chromium_path
	chromium_path="$(find "$HOME/.cache/ms-playwright" -type f -path '*/chrome-linux/chrome' 2>/dev/null | head -n1 || true)"
	if [[ -n "$chromium_path" ]]; then
		export CHROME_PATH="$chromium_path"
		echo "[info] CHROME_PATH configurado: $CHROME_PATH"
		return 0
	fi

	echo "[error] nao foi possivel localizar Chromium apos instalacao." >&2
	return 1
}

ensure_chrome

echo "[step] Lighthouse CI ($PROFILE)"
npx -y @lhci/cli@0.13.0 autorun --config="$CONFIG_FILE"
echo "[ok  ] lighthouse ci passou."