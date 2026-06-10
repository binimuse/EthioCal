#!/usr/bin/env bash
# Build a production-ready distribution zip.
#
# Usage: bash bin/build-dist.sh
#
# Steps:
#   1. Build JS block assets
#   2. Install production-only Composer dependencies
#   3. Create binimuse-geez-calendar.zip (honouring .distignore)
#   4. Restore dev Composer dependencies
#
set -euo pipefail

PLUGIN_SLUG="binimuse-geez-calendar"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_ZIP="${ROOT_DIR}/${PLUGIN_SLUG}.zip"

cd "$ROOT_DIR"

echo "==> Building JS block assets..."
npm ci --prefer-offline
npm run build

echo "==> Installing production Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Creating ${PLUGIN_SLUG}.zip..."
rm -f "$OUT_ZIP"

# Build an exclusion list from .distignore (one pattern per line; skip blanks/comments)
EXCLUDES=()
while IFS= read -r line; do
    [[ -z "$line" || "$line" == \#* ]] && continue
    EXCLUDES+=("--exclude=./${PLUGIN_SLUG}/${line}")
    EXCLUDES+=("--exclude=./${PLUGIN_SLUG}/${line}/*")
done < .distignore

# Create a temp dir and symlink the plugin folder under its slug, then zip.
TMP_DIR=$(mktemp -d)
ln -s "$ROOT_DIR" "${TMP_DIR}/${PLUGIN_SLUG}"

(
    cd "$TMP_DIR"
    zip -r "$OUT_ZIP" "./${PLUGIN_SLUG}/" \
        "${EXCLUDES[@]}" \
        -x "*.git*" \
        -x "*/.DS_Store" \
        -x "*/__MACOSX/*"
)

rm -rf "$TMP_DIR"

echo "==> Restoring dev Composer dependencies..."
composer install --no-interaction

echo ""
echo "Done: ${OUT_ZIP} ($(du -sh "$OUT_ZIP" | cut -f1))"
echo ""
echo "Contents (vendor/ top-level):"
unzip -l "$OUT_ZIP" | grep -E "vendor/[^/]+/$" | awk '{print $NF}' | sort -u
