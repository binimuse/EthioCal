#!/bin/sh
set -e
cd /app

SLUG="binimuse-geez-calendar"
ZIP="/app/${SLUG}.zip"
rm -f "$ZIP"

# Read .distignore and build exclude args for zip
EXCLUDES=""
while IFS= read -r line; do
    case "$line" in
        ""|\#*) continue ;;
    esac
    EXCLUDES="$EXCLUDES --exclude=./${SLUG}/${line} --exclude=./${SLUG}/${line}/*"
done < /app/.distignore

# Stage via a temp dir so the zip has binimuse-geez-calendar/ at the top level
TMP=$(mktemp -d)
ln -s /app "$TMP/$SLUG"

# shellcheck disable=SC2086
(cd "$TMP" && zip -r "$ZIP" "./${SLUG}/" $EXCLUDES \
    --exclude="*/.git/*" \
    --exclude="*/.git" \
    --exclude="./${SLUG}/*.zip")

rm -rf "$TMP"

echo ""
echo "=== ZIP created: $(du -sh "$ZIP" | cut -f1) ==="
echo ""
echo "=== vendor/ top-level packages ==="
unzip -l "$ZIP" | awk '{print $NF}' | grep -E "^[^/]+/vendor/[^/]+/$" | sed 's|.*/vendor/||' | sort -u
echo ""
echo "=== DONE ==="
