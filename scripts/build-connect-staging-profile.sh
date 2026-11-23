#!/usr/bin/env bash
#
# Siapkan profil staging Core yang portabel untuk hosting ber-HTTPS.
#
# Yang dihasilkan: direktori artefak berisi .env staging yang DIISI dari secret manager
# (dan dari argument ingress/non-secret), tanpa pernah mencetak nilai rahasia.
#
# Pemakaian (dari root repo ALIM Core, APP_ENV staging sudah berisi secret):
#   scripts/build-connect-staging-profile.sh \
#     --stage-url https://<host-staging> \
#     --connect-origin https://<host-connect> \
#     --connect-handoff https://<host-connect>/sso/handoff \
#     --connect-egress 203.0.113.0/24 \
#     --out /tmp/alim-staging-profile
#
# Keluaran sengaja TIDAK berisi database: DB staging dibangun di server target oleh
# `php artisan migrate` + seeder, dengan APP_KEY dari berkas ini.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

STAGE_URL=""
CONNECT_ORIGIN=""
CONNECT_HANDOFF=""
CONNECT_EGRESS=""
OUT_DIR=""

while [ $# -gt 0 ]; do
  case "$1" in
    --stage-url) STAGE_URL="$2"; shift 2 ;;
    --connect-origin) CONNECT_ORIGIN="$2"; shift 2 ;;
    --connect-handoff) CONNECT_HANDOFF="$2"; shift 2 ;;
    --connect-egress) CONNECT_EGRESS="$2"; shift 2 ;;
    --out) OUT_DIR="$2"; shift 2 ;;
    *) echo "Argumen tidak dikenal: $1" >&2; exit 2 ;;
  esac
done

missing=()
for pair in "stage-url:$STAGE_URL" "connect-origin:$CONNECT_ORIGIN" "connect-handoff:$CONNECT_HANDOFF" "out:$OUT_DIR"; do
  name="${pair%%:*}"; value="${pair#*:}"
  [ -n "$value" ] || missing+=("--$name")
done
if [ ${#missing[@]} -gt 0 ]; then
  echo "Argumen wajib belum lengkap: ${missing[*]}" >&2
  exit 2
fi

case "$STAGE_URL$CONNECT_ORIGIN$CONNECT_HANDOFF" in
  *http://*) echo "Semua URL staging wajib https:// (hosting staging, bukan lokal)." >&2; exit 2 ;;
esac

# Secret dibaca dari secret manager, tidak pernah dicetak. Bila belum ada, berhenti.
SSO_KEY="$(php artisan alim:connect-secret get sso:exchange --env=staging --stdout)"
APPROVAL_KEY="$(php artisan alim:connect-secret get approval:command --env=staging --stdout)"
if [ "${#SSO_KEY}" -lt 32 ] || [ "${#APPROVAL_KEY}" -lt 32 ]; then
  echo "Secret staging belum memenuhi panjang minimum; jalankan alim:connect-secret generate." >&2
  exit 3
fi
if [ "$SSO_KEY" = "$APPROVAL_KEY" ]; then
  echo "SSO dan Approval memakai secret yang sama — wajib berbeda." >&2
  exit 3
fi

mkdir -p "$OUT_DIR"
chmod 700 "$OUT_DIR"

umask 077
KEYS_FILE="$OUT_DIR/connect-keys.env"
php artisan alim:connect-secret export-env --env=staging --stdout > "$KEYS_FILE"
chmod 600 "$KEYS_FILE"

APP_KEY_STAGING="$(grep -E '^APP_KEY=' .env.staging | cut -d= -f2-)"
if [ -z "$APP_KEY_STAGING" ]; then
  echo "APP_KEY staging tidak ditemukan di .env.staging." >&2
  exit 3
fi

TARGET_ENV="$OUT_DIR/.env.staging"
cp .env.staging "$TARGET_ENV"
chmod 600 "$TARGET_ENV"

# Gabungkan kunci Connect dari secret manager: nilai hanya berpindah berkas, tidak dicetak.
php -r '
$envPath = $argv[1]; $keysPath = $argv[2];
$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES) as $line) {
  if ($line === "" || str_starts_with($line, "#") || ! str_contains($line, "=")) { continue; }
  [$k, $v] = explode("=", $line, 2);
  $env[$k] = $v;
}
foreach (file($keysPath, FILE_IGNORE_NEW_LINES) as $line) {
  if ($line === "" || ! str_contains($line, "=")) { continue; }
  [$k, $v] = explode("=", $line, 2);
  $env[$k] = $v;
}
$out = "";
foreach ($env as $k => $v) { $out .= $k."=".$v.PHP_EOL; }
file_put_contents($envPath, $out);
' "$TARGET_ENV" "$KEYS_FILE"

# Nilai non-secret untuk hosting.
php -r '
$path = $argv[1];
$set = [
  "APP_URL" => $argv[2],
  "CONNECT_SSO_FRAME_ORIGIN" => $argv[3],
  "CONNECT_SSO_HANDOFF_URL" => $argv[4],
  "CONNECT_SSO_ALLOWED_IPS" => $argv[5],
  "CONNECT_APPROVAL_ALLOWED_IPS" => $argv[5],
  "CONNECT_SSO_REQUIRE_HTTPS" => "true",
  "CONNECT_APPROVAL_REQUIRE_HTTPS" => "true",
];
$lines = file($path, FILE_IGNORE_NEW_LINES);
$seen = [];
foreach ($lines as $i => $line) {
  foreach ($set as $key => $value) {
    if (str_starts_with($line, $key."=")) { $lines[$i] = $key."=".$value; $seen[$key] = true; }
  }
}
$append = "";
foreach ($set as $key => $value) {
  if (! isset($seen[$key])) { $append .= $key."=".$value.PHP_EOL; }
}
file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL.$append);
' "$TARGET_ENV" "$STAGE_URL" "$CONNECT_ORIGIN" "$CONNECT_HANDOFF" "$CONNECT_EGRESS"

# APP_KEY staging wajib sama dengan yang dipakai secret manager, bukan APP_KEY lokal.
php -r '
$path = $argv[1]; $key = $argv[2];
$lines = file($path, FILE_IGNORE_NEW_LINES);
$found = false;
foreach ($lines as $i => $line) {
  if (str_starts_with($line, "APP_KEY=")) { $lines[$i] = "APP_KEY=".$key; $found = true; }
}
if (! $found) { $lines[] = "APP_KEY=".$key; }
file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);
' "$TARGET_ENV" "$APP_KEY_STAGING"

# Periksa kelengkapan tanpa mencetak nilai.
php -r '
$env = [];
foreach (file($argv[1], FILE_IGNORE_NEW_LINES) as $line) {
  if ($line === "" || str_starts_with($line, "#") || ! str_contains($line, "=")) continue;
  [$k, $v] = explode("=", $line, 2);
  $env[$k] = $v;
}
$required = [
  "APP_ENV", "APP_KEY", "APP_URL", "DB_CONNECTION", "LOG_CHANNEL",
  "CONNECT_SSO_API_KEY", "CONNECT_APPROVAL_API_KEY",
  "CONNECT_SSO_FRAME_ORIGIN", "CONNECT_SSO_HANDOFF_URL",
  "CONNECT_SSO_ALLOWED_IPS", "CONNECT_APPROVAL_ALLOWED_IPS",
  "CONNECT_SSO_REQUIRE_HTTPS", "CONNECT_APPROVAL_REQUIRE_HTTPS",
];
$bad = [];
foreach ($required as $key) {
  if (($env[$key] ?? "") === "") $bad[] = $key;
}
if ($env["APP_ENV"] !== "staging") $bad[] = "APP_ENV(!=staging)";
foreach (["CONNECT_SSO_REQUIRE_HTTPS", "CONNECT_APPROVAL_REQUIRE_HTTPS"] as $key) {
  if (($env[$key] ?? "") !== "true") $bad[] = $key."(!=true)";
}
foreach (["CONNECT_SSO_API_KEY", "CONNECT_APPROVAL_API_KEY"] as $key) {
  if (strlen($env[$key] ?? "") < 32) $bad[] = $key."(pendek)";
}
if (($env["CONNECT_SSO_API_KEY"] ?? "") === ($env["CONNECT_APPROVAL_API_KEY"] ?? "")) $bad[] = "kunci-identik";
foreach (["APP_URL", "CONNECT_SSO_HANDOFF_URL", "CONNECT_SSO_FRAME_ORIGIN"] as $key) {
  if (! str_starts_with($env[$key] ?? "", "https://")) $bad[] = $key."(!=https)";
}
if ($bad !== []) {
  fwrite(STDERR, "Profil staging belum lengkap: ".implode(", ", $bad).PHP_EOL);
  exit(4);
}
// Fingerprint saja — satu arah, tidak dapat dibalik menjadi nilai.
// `ALIM_CONNECT_INGEST_KEY` milik Connect (Connect menerima permit dari Core). Core tidak
// memakainya, jadi profil ini tidak boleh mengisinya.
foreach (array_keys($env) as $key) {
  if (str_contains($key, "INGEST")) $bad[] = $key."(milik Connect)";
}
foreach (["CONNECT_SSO_API_KEY", "CONNECT_APPROVAL_API_KEY"] as $key) {
  echo sprintf("  %-29s len=%d finger=%s".PHP_EOL, $key, strlen($env[$key]), substr(hash("sha256", $env[$key]), 0, 12));
}
echo "  profil staging LENGKAP".PHP_EOL;
' "$TARGET_ENV"

echo
echo "Artefak: $TARGET_ENV"
echo "Kunci Connect untuk diserahkan ke operator Connect: $OUT_DIR/connect-keys.env"
echo "Jangan commit direktori ini. Hapus setelah terpasang di server."
