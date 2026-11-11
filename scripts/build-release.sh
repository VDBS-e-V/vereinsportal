#!/usr/bin/env bash
# Baut das Upload-Paket für das Webhosting (Linux/macOS). Aufruf: scripts/build-release.sh [Version]
# Ergebnis: build/portal-<Version>.zip (mit vendor/ und public/build, ohne .env, tests, docs, node_modules)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="${1:-$(date +%Y%m%d-%H%M)}"
TARGET="$ROOT/build/release"
cd "$ROOT"

[ -z "$(git status --porcelain)" ] || echo "Hinweis: Es gibt nicht committete Änderungen." >&2

rm -rf "$TARGET"; mkdir -p "$TARGET"

echo "1/5 Frontend bauen ..."
npm ci
npm run build

echo "2/5 Dateien kopieren ..."
rsync -a --exclude='.git' --exclude='.github' --exclude='node_modules' --exclude='tests' --exclude='docs' --exclude='build' \
  --exclude='.idea' --exclude='.vscode' --exclude='.env' --exclude='.env.*' --exclude='*.log' --exclude='phpunit.xml' ./ "$TARGET/"

mkdir -p "$TARGET"/storage/{logs,framework/{cache/data,sessions,views},app/{private,public}} "$TARGET/bootstrap/cache"
find "$TARGET/storage" -type f ! -name '.gitignore' -delete
find "$TARGET/bootstrap/cache" -type f ! -name '.gitignore' -delete

echo "3/5 Abhängigkeiten ohne Entwicklungspakete installieren ..."
composer install --no-dev --optimize-autoloader --no-interaction --working-dir="$TARGET"

echo "4/5 Prüfsummen schreiben ..."
php "$ROOT/scripts/release-manifest.php" "$TARGET" "$VERSION"

echo "5/5 ZIP-Datei erstellen ..."
mkdir -p "$ROOT/build"
( cd "$TARGET" && zip -qr "$ROOT/build/portal-$VERSION.zip" . )
echo "Fertig: build/portal-$VERSION.zip"
