#!/usr/bin/env bash
#
# Pasang ALIM Core STAGING di VPS terpisah (Cycle 9.6).
#
# Default = PREFLIGHT: hanya membaca, tidak menulis apa pun.
#   sudo ./scripts/provision-connect-staging-vps.sh --app-dir /srv/alim-staging \
#        --profile /tmp/alim-staging-profile --host alim-staging.abuhurairah.id
#   sudo ./scripts/provision-connect-staging-vps.sh ... --install
#
# Skrip ini TIDAK PERNAH menyalin apa pun dari produksi. Prasyaratnya:
#   - dijalankan di host yang bukan host produksi,
#   - kode diambil dari commit di repo (git archive), bukan dari working tree,
#   - .env datang dari profil staging yang sudah divalidasi,
#   - database dibuat kosong lalu diisi seeder sintetis.
#
# Status keluar: 0 lolos/selesai · 2 argumen kurang · 3 prasyarat gagal · 4 verifikasi gagal.
#
# Tidak ada nilai rahasia yang dicetak: hanya nama, panjang, dan fingerprint.
set -euo pipefail

# Pemasangan setengah jalan harus bisa ditelusuri: laporkan langkah yang gagal.
trap 'rc=$?; if [ "$rc" -ne 0 ]; then printf "  [GAGAL] langkah terakhir keluar dengan kode %s\n" "$rc" >&2; fi' EXIT

say()  { printf '%s\n' "$*"; }
ok()   { printf '  [ok]   %s\n' "$*"; }
warn() { printf '  [warn] %s\n' "$*"; }
die()  { printf '  [STOP] %s\n' "$*" >&2; exit 3; }

readonly STAGE_DOMAIN="alim-staging.abuhurairah.id"
readonly PROD_DOMAIN="alim.abuhurairah.id"
readonly CONNECT_DOMAIN="alim-wa.abuhurairah.id"
readonly MIN_FREE_GB=12

APP_DIR=""
PROFILE=""
HOST=""
REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DO_INSTALL=0

while [ $# -gt 0 ]; do
  case "$1" in
    --app-dir)  APP_DIR="${2:-}";  shift 2 ;;
    --profile)  PROFILE="${2:-}";  shift 2 ;;
    --host)     HOST="${2:-}";     shift 2 ;;
    --repo)     REPO_DIR="${2:-}"; shift 2 ;;
    --install)  DO_INSTALL=1;      shift ;;
    -h|--help)  sed -n '2,20p' "$0"; exit 0 ;;
    *) die "Argumen tidak dikenal: $1" ;;
  esac
done

say "ALIM Core staging VPS — preflight"
say ""

abspath() {
  if realpath -m / >/dev/null 2>&1; then realpath -m "$1"; else
    python3 -c 'import os,sys; print(os.path.realpath(os.path.expanduser(sys.argv[1])))' "$1"
  fi
}

# ---------------------------------------------------------------- 1. argumen
missing=()
[ -n "$APP_DIR" ] || missing+=("--app-dir")
[ -n "$PROFILE" ] || missing+=("--profile")
[ -n "$HOST" ]    || missing+=("--host")
if [ ${#missing[@]} -gt 0 ]; then
  printf '  argumen kurang: %s\n' "${missing[*]}" >&2; exit 2
fi
ok "argumen lengkap"

# ------------------------------------------------- 2. bukan host produksi
# Host produksi memakai shared hosting dengan direktori ~/alim-app. Bila direktori
# itu terlihat, mesin ini adalah mesin produksi dan skrip harus berhenti.
for probe in /home/*/alim-app "$HOME/alim-app" /var/www/alim-app; do
  [ -e "$probe" ] || continue
  die "ditemukan direktori aplikasi produksi ($probe) — skrip staging tidak boleh jalan di host produksi."
done
ok "tidak ada direktori aplikasi produksi di host ini"

# Wajib jalur absolut: jalur relatif (mis. "..") akan diam-diam menjadi induk repo
# atau direktori aplikasi lain saat dijalankan dari tempat berbeda.
case "$APP_DIR" in
  /*) ;;
  *) die "app-dir harus jalur absolut (diberikan: $APP_DIR)." ;;
esac
[ -d "$(dirname "$APP_DIR")" ] || die "induk app-dir tidak ada: $(dirname "$APP_DIR") — buat lebih dulu (mis. /srv)."
APP_RP="$(abspath "$APP_DIR")"
case "$APP_RP" in
  /|/srv|/srv/|/var/www|/var/www/|/home|/root|/tmp|/var|/usr|/etc) die "app-dir terlalu umum: $APP_RP" ;;
esac
REPO_RP="$(abspath "$REPO_DIR")"
[ "$APP_RP" != "$REPO_RP" ] || die "app-dir sama dengan direktori repo."
case "$APP_RP/" in "$REPO_RP"/*) die "app-dir berada di dalam repo ($REPO_RP)." ;; esac
ok "app-dir terpisah dari repo: $APP_RP"

# ------------------------------------------------------------ 3. host + TLS
[ "$HOST" = "$STAGE_DOMAIN" ] || die "host harus $STAGE_DOMAIN (diberikan: $HOST)."
[ "$HOST" != "$PROD_DOMAIN" ] || die "host staging sama dengan produksi."
[ "$HOST" != "$CONNECT_DOMAIN" ] || die "host staging sama dengan domain Connect."
ok "host = $HOST"

# ------------------------------------------------------- 4. alat dan layanan
for bin in php nginx git tar composer sqlite3; do
  command -v "$bin" >/dev/null 2>&1 || die "$bin tidak ditemukan."
done
ok "php, nginx, git, tar, composer, sqlite3 tersedia"

php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' \
  || die "PHP $(php -r 'echo PHP_VERSION;') terlalu lama (composer.json butuh ^8.3)."
ok "PHP $(php -r 'echo PHP_VERSION;')"

ext_missing=()
for ext in pdo_sqlite sqlite3 mbstring openssl json ctype fileinfo tokenizer xml curl zip dom; do
  php -r "exit(extension_loaded('${ext}') ? 0 : 1);" || ext_missing+=("$ext")
done
[ ${#ext_missing[@]} -eq 0 ] || die "ekstensi PHP hilang: ${ext_missing[*]}"
ok "ekstensi PHP lengkap"

if command -v systemctl >/dev/null 2>&1; then
  systemctl list-units --type=service --state=running 2>/dev/null | grep -q 'php.*fpm' \
    || die "php-fpm tidak berjalan."
  ok "php-fpm berjalan"
else
  die "systemd tidak tersedia — host ini bukan VPS Linux yang didukung."
fi

# Timer cron/ scheduler aplikasi TIDAK boleh ada di staging: banyak perintah
# terjadwal mengirim surel/WhatsApp. Scheduler hanya dipasang di produksi.
if crontab -l 2>/dev/null | grep -vE '^\s*#' | grep -q 'schedule:run'; then
  die "crontab memuat schedule:run — scheduler tidak boleh aktif di staging."
fi
ok "scheduler aplikasi tidak aktif"

# ------------------------------------------------------------- 5. profil
[ -d "$PROFILE" ] || die "direktori profil tidak ada: $PROFILE"
PROFILE_ENV="${PROFILE%/}/.env.staging"
[ -f "$PROFILE_ENV" ] || die "profil tidak lengkap: $PROFILE_ENV"
# GNU stat (-c) di Linux; BSD stat (-f) di macOS/uji lokal. Dicek lewat --version,
# bukan dengan mencoba opsi (BSD stat menerima "-c" sebagai nama berkas).
STAT_CMD="/usr/bin/stat"; [ -x "$STAT_CMD" ] || STAT_CMD="stat"
if "$STAT_CMD" --version >/dev/null 2>&1; then
  perm="$("$STAT_CMD" -c %a "$PROFILE_ENV" 2>/dev/null || true)"
else
  perm="$("$STAT_CMD" -f %Lp "$PROFILE_ENV" 2>/dev/null || true)"
fi
[ "$perm" = "600" ] || die "permisi $PROFILE_ENV adalah ${perm:-tidak terbaca}, harus 600."

prof_url="$(grep -E '^APP_URL=' "$PROFILE_ENV" | head -1 | cut -d= -f2-)"
case "$prof_url" in
  "https://${HOST%/}"|"https://${HOST%/}/") ok "APP_URL profil = https://$HOST" ;;
  *) die "APP_URL profil ($prof_url) bukan https://$HOST." ;;
esac
grep -qE "://${PROD_DOMAIN//./\\.}" "$PROFILE_ENV" \
  && die "profil menyebut domain produksi ($PROD_DOMAIN)."
ok "profil tidak menunjuk produksi"

# Kanal keluar wajib tertutup di profil. Pesan error menyebut jalur perbaikan
# (builder) supaya tidak berhenti tanpa jalan keluar.
bad_out=""
for pair in "MAIL_MAILER=log" "BROADCAST_DRIVER=null" "BROADCAST_CONNECTION=null" \
            "QUEUE_CONNECTION=sync" "WA_ENABLED=false" "WA_PROVIDER=log" \
            "APP_DEBUG=false" "LOG_CHANNEL=staging"; do
  key="${pair%%=*}"; want="${pair#*=}"
  got="$(grep -E "^${key}=" "$PROFILE_ENV" | tail -1 | cut -d= -f2- || true)"
  [ "$got" = "$want" ] || bad_out+=" ${key}=${got:-kosong}(harus ${want})"
done
if [ -n "$bad_out" ]; then
  printf '  [STOP] kanal keluar staging tidak tertutup:%s\n' "$bad_out" >&2
  printf '         Perbaiki: jalankan ulang scripts/build-connect-staging-profile.sh — builder memaksa\n' >&2
  printf '         nilai aman — lalu salin .env.staging yang baru ke direktori profil.\n' >&2
  exit 3
fi
ok "kanal keluar tertutup (mail=log, broadcast=null, queue=sync, WA mati, debug mati)"

# ------------------------------------------------------- 6. ruang disk
free_gb="$(df -Pk "$(dirname "$APP_RP")" 2>/dev/null | awk 'NR==2 {print int($4/1048576)}' || echo 0)"
[ -n "$free_gb" ] || free_gb=0
[ "$free_gb" -ge "$MIN_FREE_GB" ] || die "ruang bebas $(dirname "$APP_RP") hanya ${free_gb} GB, butuh >= ${MIN_FREE_GB} GB."
ok "ruang bebas ${free_gb} GB (butuh >= ${MIN_FREE_GB} GB)"

# ------------------------------------------------------------ 7. sertifikat
cert="/etc/letsencrypt/live/$HOST/fullchain.pem"
if [ -f "$cert" ]; then
  ok "sertifikat TLS ada untuk $HOST"
else
  warn "sertifikat $cert belum ada — jalankan certbot SEBELUM uji HTTPS"
fi

# ------------------------------------------------- 8. commit sumber
# REF dicatat di preflight agar --install memakai commit yang sama dengan yang ditinjau.
WANT_REF="${ALIM_STAGING_REF:-HEAD}"
REF="$(git --git-dir="$REPO_DIR/.git" --work-tree="$REPO_DIR" rev-parse "${WANT_REF}^{commit}" 2>/dev/null || true)"
[ -n "$REF" ] || die "ref '${WANT_REF}' bukan commit di $REPO_DIR (periksa --repo dan ALIM_STAGING_REF)."
git --git-dir="$REPO_DIR/.git" --work-tree="$REPO_DIR" cat-file -e "$REF" 2>/dev/null || die "commit $REF tidak ditemukan di $REPO_DIR."
git --git-dir="$REPO_DIR/.git" --work-tree="$REPO_DIR" cat-file -e "${REF}:artisan" 2>/dev/null \
  || die "commit ${REF:0:8} tidak memuat artisan — bukan root repo ALIM Core."
ok "commit sumber ${REF:0:8}"

say ""
say "Preflight LULUS."
if [ "$DO_INSTALL" -eq 0 ]; then
  say "Mode pemeriksaan — tidak ada yang ditulis. Tambahkan --install untuk memasang."
  exit 0
fi

# ============================================================== INSTALASI
say ""
say "Instalasi dimulai: $APP_RP"

[ ! -e "$APP_RP" ] || [ -z "$(ls -A "$APP_RP" 2>/dev/null)" ] \
  || die "$APP_RP sudah berisi berkas — bukan pemasangan bersih, berhenti."

mkdir -p "$APP_RP"
git --git-dir="$REPO_DIR/.git" --work-tree="$REPO_DIR" archive --format=tar "$REF" | tar -x -C "$APP_RP"
ok "kode dipasang dari commit (bukan working tree)"

# vendor selalu di-generate di host, tidak pernah disalin.
( cd "$APP_RP" && composer install --no-dev --no-interaction --optimize-autoloader --no-scripts >/dev/null )
ok "vendor dibuat dari composer.lock"

umask 077
install -m 600 "$PROFILE_ENV" "$APP_RP/.env"
ok ".env staging dipasang (600)"

for d in storage/app/public storage/framework/cache storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache database \
        storage/app/alim-backups \
        storage/app/alim-staging/connect; do
  mkdir -p "$APP_RP/$d"
done
chown -R www-data:www-data "$APP_RP/storage" "$APP_RP/bootstrap/cache" 2>/dev/null || true
umask 022
ok "storage, cache, dan direktori backup dibuat"

DB_FILE="$APP_RP/database/staging.sqlite"
[ ! -e "$DB_FILE" ] || die "$DB_FILE sudah ada — bukan pemasangan bersih."
: > "$DB_FILE"; chmod 640 "$DB_FILE"; chown www-data:www-data "$DB_FILE" 2>/dev/null || true
ok "database staging dibuat kosong"

cd "$APP_RP"
php artisan migrate --force --no-interaction >/dev/null
ok "migrasi diterapkan"

php artisan db:seed --class=ConnectStagingTestDataSeeder --force --no-interaction >/dev/null
ok "data sintetis dibuat (seeder menolak bila APP_ENV bukan staging)"

php artisan alim:verify-connect-schema >/dev/null || { say "  [GAGAL] skema kontrak tidak terverifikasi." >&2; exit 4; }
ok "skema kontrak terverifikasi"

php artisan config:cache >/dev/null
php artisan route:cache >/dev/null
php artisan view:cache >/dev/null
ok "config, route, view di-cache"

# Bukti cepat bahwa isolasi berlaku pada konfigurasi yang benar-benar dimuat.
php artisan tinker --execute='
$checks = [
  "app_env_staging"   => app()->environment() === "staging",
  "mail_log"          => config("mail.default") === "log",
  "broadcast_null"    => (string) config("broadcasting.default") === "null",
  "queue_sync"        => config("queue.default") === "sync",
  "wa_disabled"       => filter_var(config("whatsapp.enabled"), FILTER_VALIDATE_BOOLEAN) === false,
  "db_sqlite_staging" => config("database.default") === "sqlite"
      && str_ends_with((string) config("database.connections.sqlite.database"), "database/staging.sqlite"),
];
$fail = array_keys(array_filter($checks, fn ($v) => ! $v));
foreach ($checks as $k => $v) { printf("         %-22s %s\n", $k, $v ? "ok" : "GAGAL"); }
exit($fail === [] ? 0 : 4);' || { say "  [GAGAL] isolasi konfigurasi tidak terpenuhi." >&2; exit 4; }
ok "isolasi konfigurasi terverifikasi (mail=log, broadcast=null, queue=sync, WA off)"

say ""
say "Instalasi selesai. Pemeriksaan pasca-instalasi: docs/integration Cycle 9.6 §5."
say "Pasang vhost nginx dan sertifikat TLS sebelum menguji HTTPS."
