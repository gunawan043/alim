#!/usr/bin/env bash
#
# Audit script: scan app/ for hardcoded role-name dependencies.
#
# Run:  bash scripts/audit-role-dependency.sh
# Exit: 0 = clean, 1 = violations found
#
set -uo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
APP_PATH="$PROJECT_ROOT/app"
VIOLATIONS_FILE="$(mktemp)"
trap 'rm -f "$VIOLATIONS_FILE"' EXIT

# Allowlist: directories that legitimately use role names
ALLOWLIST="$APP_PATH/Authorization"

# Patterns indicating real role-name dependencies (not coincidental matches)
PATTERNS=(
  "User::role\("
  "->whereHas\('roles'"
  "->whereHas\(\"roles\""
  "hasRole\(\$"
  "hasRole\(\"'"
  "hasRole\(\"\""
  "->role\s*==="
  "->role\s*=="
  "->role\s*!="
  "'role'\s*=>"
  "Gate::define\(.*role"
)

# Build a single regex
COMBINED_RE=""
for p in "${PATTERNS[@]}"; do
  if [ -z "$COMBINED_RE" ]; then
    COMBINED_RE="$p"
  else
    COMBINED_RE="$COMBINED_RE|$p"
  fi
done

# Find all .php files under app/ except allowlisted dirs
FILES=$(find "$APP_PATH" -type f -name "*.php" | grep -v "^$ALLOWLIST")

COUNT=0
for f in $FILES; do
  # Grep for role-name dependencies
  HITS=$(grep -nE "$COMBINED_RE" "$f" 2>/dev/null || true)
  if [ -n "$HITS" ]; then
    REL=${f#"$PROJECT_ROOT/"}
    echo "--- $REL ---" >> "$VIOLATIONS_FILE"
    echo "$HITS" >> "$VIOLATIONS_FILE"
    echo "" >> "$VIOLATIONS_FILE"
    COUNT=$((COUNT + 1))
  fi
done

if [ "$COUNT" -gt 0 ]; then
  echo "ROLE-NAME DEPENDENCY AUDIT FAILED"
  echo "  $COUNT file(s) contain hardcoded role-name references"
  echo ""
  cat "$VIOLATIONS_FILE"
  echo ""
  echo "Each violation should be replaced with a snapshot-permission check."
  echo "See: docs/authorization-architecture-revision.md"
  exit 1
fi

echo "ROLE-NAME DEPENDENCY AUDIT PASSED"
echo "All role-name references are isolated to the Authorization layer."
exit 0