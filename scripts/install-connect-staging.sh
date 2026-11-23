#!/usr/bin/env bash
#
# Pasang ALIM Core STAGING di subdomain ber-HTTPS, terpisah dari produksi.
#
# Dijalankan DI SERVER STAGING (lewat SSH), bukan di mesin pengembang.
# Aman dijalankan berulang: default = PREFLIGHT (tidak menulis apa pun).
#
#   ./scripts/install-connect-staging.sh \
#       --docroot /home/USER/domains/alim-staging.abuhurairah.id/public_html \
#       --app-dir /home/USER/alim-staging \
#       --profile /tmp/alim-staging-profile \
#       --prod-app-dir /home/USER/alim-app \
#       [--install]
#
# Domain staging baku: alim-staging.abuhurairah.id (domain produksi: alim.abuhurairah.id).
# Domain Connect: alim-wa.abuhurairah.id — hanya dipakai sebagai Origin/handoff SSO,
# tidak pernah diubah dan tidak pernah menjadi target instalasi.
#
# Status keluar:
#   0  preflight lolos (atau instalasi selesai)
#   2  argumen tidak lengkap
#   3  kondisi keamanan tidak terpenuhi — BERHENTI, tidak ada yang ditulis
#   4  verifikasi pasca-instalasi gagal
#
# Skrip TIDAK PERNAH mencetak nilai rahasia. Yang dilaporkan hanya nama variabel
# yang hilang, panjang, dan fingerprint SHA-256 12 karakter (satu arah).
set -euo pipefail

say()  { printf '%s\n' "$*"; }
ok()   { printf '  [ok]   %s\n' "$*"; }
warn() { printf '  [warn] %s\n' "$*"; }
die()  { printf '  [STOP] %s\n' "$*" >&2; exit 3; }

# Domain baku. Sengaja konstanta: skrip ini tidak boleh dipakai untuk memasang
# di domain produksi (alim) atau domain Connect (alim-wa).
readonly STAGE_DOMAIN="alim-staging.abuhurairah.id"
readonly PROD_DOMAIN="alim.abuhurairah.id"
readonly CONNECT_DOMAIN="alim-wa.abuhurairah.id"

DOCROOT=""
APP_DIR=""
PROFILE=""
PROD_APP_DIR=""
DO_INSTALL=0

while [ $# -gt 0 ]; do
  case "$1" in
    --docroot)      DOCROOT="${2:-}";     shift 2 ;;
    --app-dir)      APP_DIR="${2:-}";     shift 2 ;;
    --profile)      PROFILE="${2:-}";     shift 2 ;;
    --prod-app-dir) PROD_APP_DIR="${2:-}"; shift 2 ;;
    --install)      DO_INSTALL=1;         shift ;;
    -h|--help)      sed -n '2,26p' "$0"; exit 0 ;;
    *) die "Argumen tidak dikenal: $1" ;;
  esac
done

say "ALIM Core staging — preflight"
say ""

# `realpath -m` ada di Linux (server) tetapi tidak di macOS (mesin pengembang).
# Fallback ini menormalkan jalur yang belum ada tanpa menyentuh berkas sistem.
abspath() {
  if realpath -m / >/dev/null 2>&1; then
    realpath -m "$1"
  else
    python3 -c 'import os,sys; print(os.path.realpath(os.path.expanduser(sys.argv[1])))' "$1"
  fi
}

# ---------------------------------------------------------------- 1. argumen
missing=()
for pair in "docroot:$DOCROOT" "app-dir:$APP_DIR" \
            "profile:$PROFILE" "prod-app-dir:$PROD_APP_DIR"; do
  [ -n "${pair#*:}" ] || missing+=("--${pair%%:*}")
done
if [ ${#missing[@]} -gt 0 ]; then
  printf '  argumen kurang: %s\n' "${missing[*]}" >&2
  exit 2
fi
ok "argumen lengkap"

# Domain yang diperiksa harus milik profil staging, bukan produksi/Connect.
case "$DOCROOT" in
  */domains/"$PROD_DOMAIN"/*) die "docroot berada di domain PRODUKSI ($PROD_DOMAIN)." ;;
  */domains/"$CONNECT_DOMAIN"/*) die "docroot berada di domain Connect ($CONNECT_DOMAIN)." ;;
  */domains/"$STAGE_DOMAIN"/*) ok "docroot berada di domain staging ($STAGE_DOMAIN)" ;;
  *) die "docroot wajib berada di bawah domains/$STAGE_DOMAIN (diberikan: $DOCROOT)." ;;
esac

# ------------------------------------------------- 2. pisahkan staging/produksi
# Perbandingan memakai realpath supaya symlink tidak mengelabui pemeriksaan.
PROD_DOCROOT="${PROD_APP_DIR%/}/public"
STAGE_RP="$(abspath "$APP_DIR")"
PROD_RP="$(abspath "$PROD_APP_DIR")"
DOC_RP="$(abspath "$DOCROOT")"
PROD_DOC_RP="$(abspath "$PROD_DOCROOT")"

[ "$STAGE_RP" != "$PROD_RP" ] || die "app-dir SAMA dengan direktori produksi ($PROD_APP_DIR)."
case "$STAGE_RP/" in
  "$PROD_RP"/*) die "app-dir berada DI DALAM diretori produksi ($PROD_APP_DIR)." ;;
esac
case "$PROD_RP/" in
  "$STAGE_RP"/*) die "app-dir MEMUAT direktori produksi ($PROD_APP_DIR)." ;;
esac
ok "app-dir terpisah dari produksi"

[ "$DOC_RP" != "$PROD_DOC_RP" ] || die "docroot SAMA dengan document root produksi ($PROD_DOCROOT)."
case "$DOC_RP/" in
  "$PROD_RP"/*) die "docroot berada DI DALAM aplikasi produksi ($PROD_APP_DIR)." ;;
esac
# Nama folder document root produksi Hostinger adalah public_html; staging tidak boleh
# memakai folder yang sama di bawah domain produksi.
PROD_DOMAIN_ROOT="$(dirname "$PROD_DOC_RP")"
case "$DOC_RP/" in
  "$PROD_DOMAIN_ROOT"/*) die "docroot berada di domain produksi ($PROD_DOMAIN_ROOT)." ;;
esac
ok "docroot terpisah dari produksi"

# ------------------------------------------------------------ 3. domain + TLS
if ! command -v curl >/dev/null 2>&1; then
  die "curl tidak tersedia; tidak dapat memverifikasi TLS."
fi

# Domain produksi dan Connect tidak boleh ikut terpasang: pastikan yang diuji memang
# domain staging, bukan salah satu dari keduanya.
[ "$STAGE_DOMAIN" != "$PROD_DOMAIN" ]    || die "domain staging sama dengan produksi."
[ "$STAGE_DOMAIN" != "$CONNECT_DOMAIN" ] || die "domain staging sama dengan domain Connect."

# Profil staging harus menyebut domain staging ini sebagai APP_URL, dan tidak boleh
# menunjuk domain produksi.
prof_url="$(grep -E '^APP_URL=' "$PROFILE/.env.staging" 2>/dev/null | head -1 | cut -d= -f2- || true)"
[ -n "$prof_url" ] || die "APP_URL tidak ditemukan di profil staging."
case "$prof_url" in
  "https://${STAGE_DOMAIN%/}"|"https://${STAGE_DOMAIN%/}/") ok "APP_URL profil = https://${STAGE_DOMAIN}" ;;
  *) die "APP_URL profil ($prof_url) bukan https://${STAGE_DOMAIN}." ;;
esac
if grep -qE "://${PROD_DOMAIN//./\\.}(/|$)" "$PROFILE/.env.staging" 2>/dev/null; then
  die "profil staging menyebut domain produksi ($PROD_DOMAIN)."
fi
ok "profil tidak menunjuk domain produksi"

probe_status="$(curl -s -o /dev/null -m 15 -w '%{http_code}' "https://${STAGE_DOMAIN}/" || true)"
[ "$probe_status" != "000" ] || die "https://${STAGE_DOMAIN}/ tidak dapat dijangkau (DNS/TLS belum siap)."
ok "https://${STAGE_DOMAIN} terjangkau (HTTP ${probe_status})"

# Sertifikat harus valid dan berlaku untuk domain ini. `--verify` gagal pada
# sertifikat kedaluwarsa, self-signed, atau CN/SAN yang tidak cocok.
if ! curl -s -o /dev/null --fail --verify -m 15 "https://${STAGE_DOMAIN}/" 2>/dev/null; then
  # 4xx/5xx tetap berarti TLS sah; yang diperiksa adalah kegagalan verifikasi.
  if ! curl -s -o /dev/null -m 15 "https://${STAGE_DOMAIN}/" >/dev/null 2>&1; then
    die "TLS https://${STAGE_DOMAIN} tidak valid (sertifikat tidak tepercaya/kedaluwarsa/nama tidak cocok)."
  fi
fi
ok "sertifikat TLS valid untuk ${STAGE_DOMAIN}"

# ------------------------------------------------------------- 4. ekstensi PHP
if ! command -v php >/dev/null 2>&1; then
  die "php tidak ditemukan di PATH server staging."
fi
PHP_VER="$(php -r 'echo PHP_VERSION;')"
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
  || die "PHP ${PHP_VER} terlalu lama (butuh >= 8.2)."
ok "PHP ${PHP_VER}"

ext_missing=()
for ext in pdo_sqlite sqlite3 mbstring openssl json ctype fileinfo tokenizer xml curl; do
  php -r "exit(extension_loaded('${ext}') ? 0 : 1);" || ext_missing+=("$ext")
done
[ ${#ext_missing[@]} -eq 0 ] || die "ekstensi PHP hilang: ${ext_missing[*]}"
ok "ekstensi PHP lengkap (pdo_sqlite, sqlite3, mbstring, openssl, …)"

# ---------------------------------------------------------- 5. profil staging
[ -d "$PROFILE" ] || die "direktori profil tidak ada: $PROFILE"
PROFILE_ENV="${PROFILE%/}/.env.staging"
[ -f "$PROFILE_ENV" ] || die "profil staging tidak lengkap: $PROFILE_ENV tidak ada"

perm="$(stat -c '%a' "$PROFILE_ENV" 2>/dev/null || stat -f '%Lp' "$PROFILE_ENV")"
[ "$perm" = "600" ] || die "permisi $PROFILE_ENV adalah $perm, harus 600."
ok "profil staging ada, permisi 600"

# Kelengkapan diperiksa tanpa mencetak nilai.
php -r '
$env = [];
foreach (file($argv[1], FILE_IGNORE_NEW_LINES) as $line) {
  if ($line === "" || str_starts_with($line, "#") || ! str_contains($line, "=")) continue;
  [$k, $v] = explode("=", $line, 2);
  $env[$k] = $v;
}
$required = [
  "APP_ENV", "APP_KEY", "APP_URL", "DB_CONNECTION", "DB_DATABASE", "LOG_CHANNEL",
  "CONNECT_SSO_API_KEY", "CONNECT_APPROVAL_API_KEY",
  "CONNECT_SSO_ALLOWED_IPS", "CONNECT_APPROVAL_ALLOWED_IPS",
  "CONNECT_SSO_FRAME_ORIGIN", "CONNECT_SSO_HANDOFF_URL",
  "CONNECT_SSO_REQUIRE_HTTPS", "CONNECT_APPROVAL_REQUIRE_HTTPS",
];
$bad = [];
foreach ($required as $key) { if (($env[$key] ?? "") === "") $bad[] = $key; }
if (($env["APP_ENV"] ?? "") !== "staging") $bad[] = "APP_ENV(!=staging)";
if (($env["DB_CONNECTION"] ?? "") !== "sqlite") $bad[] = "DB_CONNECTION(!=sqlite)";
foreach (["CONNECT_SSO_REQUIRE_HTTPS", "CONNECT_APPROVAL_REQUIRE_HTTPS"] as $k) {
  if (($env[$k] ?? "") !== "true") $bad[] = $k."(!=true)";
}
if (($env["CONNECT_SSO_API_KEY"] ?? "") === ($env["CONNECT_APPROVAL_API_KEY"] ?? "")) $bad[] = "kunci-identik";
foreach (["CONNECT_SSO_API_KEY", "CONNECT_APPROVAL_API_KEY"] as $k) {
  if (strlen($env[$k] ?? "") < 32) $bad[] = $k."(pendek)";
}
foreach (["APP_URL", "CONNECT_SSO_FRAME_ORIGIN", "CONNECT_SSO_HANDOFF_URL"] as $k) {
  if (! str_starts_with($env[$k] ?? "", "https://")) $bad[] = $k."(!=https)";
}
// Integrasi ini tidak boleh membawa kunci produksi: APP_KEY harus berbeda dari produksi,
// diperiksa pemanggil. Di sini cukup pastikan tidak ada nama variabel yang salah kaprah.
foreach (array_keys($env) as $k) {
  if (str_contains($k, "INGEST")) { $bad[] = $k."(milik Connect, tidak dipakai Core)"; }
}
if ($bad !== []) { fwrite(STDERR, "  [STOP] profil belum sah: ".implode(", ", $bad).PHP_EOL); exit(3); }
foreach (["CONNECT_SSO_API_KEY", "CONNECT_APPROVAL_API_KEY"] as $k) {
  printf("         %-26s len=%d finger=%s\n", $k, strlen($env[$k]), substr(hash("sha256", $env[$k]), 0, 12));
}
' "$PROFILE_ENV" || exit 3
ok "profil staging sah (isi diperiksa, tidak dicetak)"

# ------------------------------------------------- 6. konfigurasi vs produksi
[ -f "${PROD_APP_DIR%/}/.env" ] || die "file .env produksi tidak ditemukan di $PROD_APP_DIR — jalur produksi tidak dapat diperiksa."
prod_app_key="$(grep -E '^APP_KEY=' "${PROD_APP_DIR%/}/.env" | head -1 | cut -d= -f2-)"
stage_app_key="$(grep -E '^APP_KEY=' "$PROFILE_ENV" | head -1 | cut -d= -f2-)"
[ -n "$prod_app_key" ] || die "APP_KEY produksi kosong; tidak dapat memastikan pemisahan."
[ "$prod_app_key" != "$stage_app_key" ] || die "APP_KEY staging SAMA dengan produksi."
ok "APP_KEY staging berbeda dari produksi"

prod_db="$(grep -E '^DB_DATABASE=' "${PROD_APP_DIR%/}/.env" | head -1 | cut -d= -f2-)"
stage_db="$(grep -E '^DB_DATABASE=' "$PROFILE_ENV" | head -1 | cut -d= -f2-)"
[ -n "$prod_db" ] && [ -n "$stage_db" ] || die "DB_DATABASE tidak dapat dibaca; pemisahan database tidak terbukti."
prod_db_rp="$(abspath "$prod_db")"
stage_db_rp="$(abspath "${APP_DIR%/}/${stage_db}")"
[ "$prod_db_rp" != "$stage_db_rp" ] || die "database staging memakai berkas database PRODUKSI ($prod_db_rp)."
case "$stage_db_rp/" in
  "$PROD_RP"/*) die "berkas database staging berada di dalam direktori produksi." ;;
esac
ok "database terpisah dari produksi"

if [ -f "${PROFILE%/}/connect-keys.env" ]; then
  warn "direktori profil masih memuat connect-keys.env — hapus setelah kunci terpasang"
fi

# ------------------------------------------------------- 7. izin tulis sistem
if [ ! -d "$DOCROOT" ]; then
  die "docroot belum ada: $DOCROOT — buat subdomain di panel hosting lebih dulu."
fi
[ -w "$DOCROOT" ] || die "docroot tidak dapat ditulis: $DOCROOT"
[ -w "$(dirname "$APP_DIR")" ] || die "direktori induk app-dir tidak dapat ditulis: $(dirname "$APP_DIR")"
ok "docroot dan induk app-dir dapat ditulis"

say ""
say "Preflight LULUS."

if [ "$DO_INSTALL" -eq 0 ]; then
  say "Mode pemeriksaan — tidak ada yang ditulis. Jalankan ulang dengan --install untuk memasang."
  exit 0
fi

# ============================================================== INSTALASI
say ""
say "Instalasi dimulai."

DOC_TARGET="${DOCROOT%/}/public"
[ ! -e "$DOC_TARGET" ] || die "$DOC_TARGET sudah ada — bukan pemasangan bersih, berhenti."
[ ! -e "${APP_DIR%/}/.env" ] || die "${APP_DIR%/}/.env sudah ada — menimpa .env tidak diizinkan."

umask 022
mkdir -p "$APP_DIR"
ok "app-dir dibuat: $APP_DIR"

# Salin kode dari commit yang sedang terpasang, bukan dari working tree.
GIT_REF="${ALIM_STAGING_REF:-HEAD}"
git -C "$(pwd)" rev-parse --verify "${GIT_REF}^{commit}" >/dev/null 2>&1 \
  || die "ref ${GIT_REF} tidak ditemukan di repo."
git -C "$(pwd)" archive --format=tar "$GIT_REF" | tar -x -C "$APP_DIR"
ok "kode dari commit ${GIT_REF} dipasang"

umask 077
cp "$PROFILE_ENV" "${APP_DIR%/}/.env"
chmod 600 "${APP_DIR%/}/.env"
ok ".env staging dipasang (600)"

mkdir -p "${APP_DIR%/}/storage/app" "${APP_DIR%/}/storage/framework/cache" \
         "${APP_DIR%/}/storage/framework/sessions" "${APP_DIR%/}/storage/framework/views" \
         "${APP_DIR%/}/storage/logs" "${APP_DIR%/}/bootstrap/cache" "${APP_DIR%/}/database"
chmod -R u+rwX,g+rwX "${APP_DIR%/}/storage" "${APP_DIR%/}/bootstrap/cache"
umask 022
ok "storage dan cache dibuat"

# Database baru, terpisah. Tidak pernah menyalin database produksi.
DB_FILE="${APP_DIR%/}/database/staging.sqlite"
[ ! -e "$DB_FILE" ] || die "$DB_FILE sudah ada — bukan pemasangan bersih."
: > "$DB_FILE"
chmod 640 "$DB_FILE"
ok "database staging dibuat kosong"

cd "$APP_DIR"
php artisan migrate --force --no-interaction >/dev/null
ok "migrasi diterapkan"

php artisan db:seed --class=ConnectStagingTestDataSeeder --force --no-interaction >/dev/null
ok "data uji staging dibuat"

if ! php artisan alim:verify-connect-schema >/dev/null 2>&1; then
  say "  [GAGAL] alim:verify-connect-schema tidak lulus." >&2
  exit 4
fi
ok "skema kontrak terverifikasi"

php artisan config:cache >/dev/null
php artisan route:cache >/dev/null
ok "config dan route di-cache"

if [ -e "$DOC_TARGET" ]; then
  die "$DOC_TARGET muncul di tengah instalasi — berhenti tanpa menimpanya."
fi
ln -s "${APP_DIR%/}/public" "$DOC_TARGET"
ok "document root ditautkan ke ${APP_DIR%/}/public (symlink, docroot asli tidak ditimpa)"

say ""
say "Instalasi selesai. Lanjutkan dengan prosedur verifikasi (docs/integration Cycle 9.3 §5)."
