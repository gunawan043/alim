#!/bin/bash
# Audit FK constraints in all migration files
cd "/Users/wawangunawan/Documents/@ALIM PROJECT/alim/database/migrations"

echo "=== SCANNING ALL FK CONSTRAINTS ==="
echo ""

# Find all migration files with FK constraints (excluding backup)
for f in *.php; do
  [[ "$f" == *"backup"* ]] && continue
  [[ ! -f "$f" ]] && continue

  content=$(cat "$f")

  # Check for foreignUuid with constrained() without explicit table
  if echo "$content" | grep -qE "foreignUuid\(.*\)->constrained\(\)"; then
    # Extract the column name and check if auto-pluralization could be wrong
    col=$(echo "$content" | grep -oP "foreignUuid\('\K[a-z_]+")
    # Auto-plural: if column ends with _id, Laravel will use the table name derived from column
    # foreignUuid('jenis_gtk_id') -> table 'jenis_gtks' (WRONG!)
    if echo "$col" | grep -qE '_gt$'; then
      echo "⚠️  $f: foreignUuid('$col')->constrained() — might pluralize wrong!"
    fi
  fi

  # Check for ->constrained() without table name
  if echo "$content" | grep -qE "->constrained\(\)[^']"; then
    # This is bare constrained() — check context
    echo "🔍 $f: has ->constrained() call"
  fi
done

echo ""
echo "=== CHECKING FOR TABLES CREATED AFTER THEIR REFERENCES ==="
echo ""

# Build a list of all table creation migrations
declare -A tables
for f in *.php; do
  [[ "$f" == *"backup"* ]] && continue
  while IFS= read -r line; do
    if [[ "$line" =~ Schema::create\('([^']+)' ]]; then
      table="${BASH_REMATCH[1]}"
      tables["$table"][0]="$f"
    fi
  done < "$f"
done

# Check each FK reference against table creation
for f in *.php; do
  [[ "$f" == *"backup"* ]] && continue
  [[ ! -f "$f" ]] && continue

  # Extract table this migration creates
  created_table=$(grep -oP "Schema::create\('\K[a-z_]+" "$f" | head -1)
  [[ -z "$created_table" ]] && continue

  # Extract FK references to other tables
  while IFS= read -r match; do
    ref_table=$(echo "$match" | sed -E "s/.*->constrained\('([^']+)'.*/\1/")
    [[ -z "$ref_table" || "$ref_table" == "->constrained()" ]] && continue

    ref_time="${tables[$ref_table][0]}"
    [[ -z "$ref_time" ]] && continue

    # Compare timestamps
    create_time=$(echo "$f" | grep -oP "^\d{4}_\d{2}_\d{2}_\d{6}")
    ref_time_val=$(echo "$ref_time" | grep -oP "^\d{4}_\d{2}_\d{2}_\d{6}")

    if [[ "$create_time" < "$ref_time_val" ]]; then
      echo "❌ $f references $ref_table from $ref_time (created BEFORE reference)"
    elif [[ "$create_time" == "$ref_time_val" ]]; then
      # Same timestamp - check ordering within file
      :
    fi
  done < <(grep -oP "->constrained\('[^']+'\)" "$f" 2>/dev/null)

  # Also check explicit foreign() calls
  while IFS= read -r match; do
    ref_table=$(echo "$match" | sed -E "s/.*->on\('([^']+)'.*/\1/")
    [[ -z "$ref_table" ]] && continue
    [[ "$ref_table" == "->on(" ]] && continue

    ref_time="${tables[$ref_table][0]}"
    [[ -z "$ref_time" ]] && continue

    create_time=$(echo "$f" | grep -oP "^\d{4}_\d{2}_\d{2}_\d{6}")
    ref_time_val=$(echo "$ref_time" | grep -oP "^\d{4}_\d{2}_\d{2}_\d{6}")

    if [[ "$create_time" < "$ref_time_val" ]]; then
      echo "❌ $f references $ref_table from $ref_time (created BEFORE reference)"
    fi
  done < <(grep -oP "->on\('[^']+'\)" "$f" 2>/dev/null)

done

echo ""
echo "=== SUMMARY ==="
