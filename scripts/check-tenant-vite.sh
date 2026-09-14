#!/usr/bin/env bash
# Verifies that every tenant panel page's @vite() entry is declared in vite.config.js,
# every declared tenant page entry is actually used by a view, and every used entry
# is present in the built manifest. Run after `npm run build`.
#
# Usage: scripts/check-tenant-vite.sh
# Exit code 0 = clean, 1 = mismatch found.

set -euo pipefail
cd "$(dirname "$0")/.."

USED_FILE="$(mktemp)"
DECLARED_FILE="$(mktemp)"
trap 'rm -f "$USED_FILE" "$DECLARED_FILE"' EXIT

grep -rhoE "@vite\(\[?'resources/js/tenant/pages/[^']+'" resources/views/tenant \
  | sed -E "s/@vite\(\[?'([^']+)'/\1/" | sort -u > "$USED_FILE"

grep -oE "'resources/js/tenant/pages/[^']+'" vite.config.js | tr -d "'" | sort -u > "$DECLARED_FILE"

status=0

used_not_declared="$(comm -23 "$USED_FILE" "$DECLARED_FILE")"
if [ -n "$used_not_declared" ]; then
  echo "USED BUT NOT DECLARED in vite.config.js:"
  echo "$used_not_declared"
  status=1
fi

declared_not_used="$(comm -13 "$USED_FILE" "$DECLARED_FILE")"
if [ -n "$declared_not_used" ]; then
  echo "DECLARED BUT UNUSED (no @vite() reference found):"
  echo "$declared_not_used"
  status=1
fi

if [ ! -f public/build/manifest.json ]; then
  echo "MISSING public/build/manifest.json — run 'npm run build' first."
  exit 1
fi

while read -r f; do
  [ -z "$f" ] && continue
  grep -q "\"$f\"" public/build/manifest.json || { echo "MISSING IN MANIFEST: $f"; status=1; }
done < "$USED_FILE"

if [ "$status" -eq 0 ]; then
  echo "OK: tenant Vite entries are consistent (used, declared, and manifest all agree)."
fi

exit "$status"
