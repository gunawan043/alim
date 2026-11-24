#!/usr/bin/env bash
#
# Verifikasi pasca-instalasi ALIM Core STAGING di VPS (Cycle 9.6).
#
# Dijalankan DI SERVER STAGING, di direktori aplikasi staging, setelah nginx +
# TLS terpasang. Seluruh pengujian memakai data sintetis dari seeder; tidak ada
# data produksi dan tidak ada koneksi ke mesin produksi.
#
#   ./scripts/verify-connect-staging-vps.sh --host alim-staging.abuhurairah.id \
#        --admin-source 198.51.100.7
#
# --admin-source : IP yang dipakai untuk uji "IP di luar allowlist". Opsional;
#                  bila kosong uji tersebut dilewati (bukan lulus).
#
# Status keluar: 0 semua lulus · 2 argumen kurang · 3 prasyarat gagal · 4 ada kegagalan.
set -euo pipefail

say()  { printf '%s\n' "$*"; }
ok()   { printf '  [ok]   %s\n' "$*"; }
bad()  { printf '  [GAGAL] %s\n' "$*" >&2; }
skip() { printf '  [lewat] %s\n' "$*"; }
die()  { printf '  [STOP] %s\n' "$*" >&2; exit 3; }

HOST=""
ADMIN_SOURCE=""

while [ $# -gt 0 ]; do
  case "$1" in
    --host)          HOST="${2:-}";         shift 2 ;;
    --admin-source)  ADMIN_SOURCE="${2:-}"; shift 2 ;;
    -h|--help)       sed -n '2,15p' "$0"; exit 0 ;;
    *) die "Argumen tidak dikenal: $1" ;;
  esac
done
[ -n "$HOST" ] || { printf '  argumen kurang: --host\n' >&2; exit 2; }
[ "$HOST" = "alim-staging.abuhurairah.id" ] || die "host harus alim-staging.abuhurairah.id (diberikan: $HOST)."
[ "$HOST" != "alim.abuhurairah.id" ] || die "host staging tidak boleh domain produksi."

BASE="https://${HOST}"
FAIL=0
pass() { ok "$*"; }
fail() { bad "$*"; FAIL=$((FAIL+1)); }

# ------------------------------------------------------------------ prasyarat
# Skrip harus dijalankan di direktori aplikasi staging, bukan di mesin lain.
[ -f artisan ] && [ -f .env ] || die "jalankan dari direktori aplikasi staging (artisan + .env)."
APP_ENV_FILE="$(php -r 'foreach(file(".env") as $l){if(str_starts_with($l,"APP_ENV=")){echo trim(explode("=",$l,2)[1]);}}')"
[ "$APP_ENV_FILE" = "staging" ] || die ".env di direktori ini bukan APP_ENV=staging."
DPOINT="$(php -r 'foreach(file(".env") as $l){if(str_starts_with($l,"DB_DATABASE=")){echo trim(explode("=",$l,2)[1]);}}')"
case "$DPOINT" in
  *staging*.sqlite) ok "database menunjuk berkas staging: $DPOINT" ;;
  *) die "DB_DATABASE bukan berkas staging ($DPOINT)." ;;
esac
command -v curl >/dev/null 2>&1 || die "curl tidak tersedia."

# Kunci dibaca dari .env langsung; tidak pernah dicetak.
read_env() { php -r 'foreach(file(".env") as $l){ if(str_starts_with($l,$argv[1]."=")){ echo trim(substr($l, strlen($argv[1])+1)); } }' "$1"; }
SSO_KEY="$(read_env CONNECT_SSO_API_KEY)"
APPROVAL_KEY="$(read_env CONNECT_APPROVAL_API_KEY)"
[ ${#SSO_KEY} -ge 32 ] && [ ${#APPROVAL_KEY} -ge 32 ] || die "kunci Connect tidak ditemukan di .env."
[ "$SSO_KEY" != "$APPROVAL_KEY" ] || die "kunci SSO dan Approval identik."
ok "kunci SSO/Approval ada dan terpisah (fingerprint $(printf %s "$SSO_KEY" | shasum -a 256 | cut -c1-12))"

SSO="${BASE}/api/connect/v1/sso/exchange"
APPROVAL="${BASE}/api/connect/v1/approval-commands"

code() { # code <expected> <label> <curl args...>
  local want="$1" label="$2"; shift 2
  local got; got="$(curl -s -o /tmp/alim-vps-body.$$ -w '%{http_code}' -m 20 "$@" || echo 000)"
  if [ "$got" = "$want" ]; then pass "$label (HTTP $got)"; else fail "$label: harap $want, dapat $got"; fi
}

has() { # has <jq-free needle> <label>
  if grep -q -- "$1" /tmp/alim-vps-body.$$; then pass "$2"; else fail "$2 (penanda '$1' tidak ada)"; fi
}

say ""
say "Pemeriksaan HTTPS: $BASE"

# --------------------------------------------------------------- 1. /health
code 200 "1. /health dapat dijangkau" "$BASE/health"
HEALTH_KEYS="$(php -r '$j=json_decode(file_get_contents($argv[1]),true); echo is_array($j)?implode(",",array_keys($j)):"BUKAN_JSON";' /tmp/alim-vps-body.$$ 2>/dev/null || echo BUKAN_JSON)"
# request_id ditambahkan RequestIdMiddleware untuk semua JSON; selain itu tidak boleh ada apa pun.
HEALTH_SORTED="$(printf '%s' "$HEALTH_KEYS" | tr ',' '\n' | sort | paste -sd, -)"
[ "$HEALTH_SORTED" = "request_id,status,time" ] \
  && pass "1b. /health minimal (kunci: $HEALTH_KEYS)" \
  || fail "1b. /health memuat kunci di luar status/time/request_id: $HEALTH_KEYS"

# ------------------------------------------------------- 2. endpoint negatif
code 401 "2a. Approval tanpa kunci → 401" -X POST -H 'Accept: application/json' "$APPROVAL"
code 401 "2b. SSO tanpa kunci → 401" -X POST -H 'Accept: application/json' "$SSO"
code 401 "2c. Approval dengan kunci salah → 401" -X POST -H 'Accept: application/json' \
  -H "X-ALIM-CONNECT-APPROVAL-KEY: $(printf 'x%.0s' $(seq 1 64))" "$APPROVAL"
code 401 "2d. Kunci SSO dipakai di endpoint Approval → 401" -X POST -H 'Accept: application/json' \
  -H "X-ALIM-CONNECT-APPROVAL-KEY: ${SSO_KEY}" "$APPROVAL"
code 401 "2e. Kunci Approval dipakai di endpoint SSO → 401" -X POST -H 'Accept: application/json' \
  -H "X-ALIM-CONNECT-SSO-KEY: ${APPROVAL_KEY}" "$SSO"

# ----------------------------------------------------------- 3. versi API
code 422 "3a. Versi API 2 ditolak → 422" -X POST -H 'Accept: application/json' \
  -H "X-ALIM-CONNECT-APPROVAL-KEY: ${APPROVAL_KEY}" -H 'X-ALIM-API-Version: 2' "$APPROVAL"
has 'unsupported_api_version' "3b. Alasan = unsupported_api_version"
code 422 "3c. Versi 1 diterima gate, gagal di payload → 422" -X POST -H 'Accept: application/json' \
  -H "X-ALIM-CONNECT-APPROVAL-KEY: ${APPROVAL_KEY}" -H 'X-ALIM-API-Version: 1' "$APPROVAL"
grep -q 'unsupported_api_version' /tmp/alim-vps-body.$$ \
  && fail "3d. Versi 1 seharusnya lolos gate versi" || pass "3d. Versi 1 lolos gate versi"

# ---------------------------------------------------------- 4. aktor asing
code 403 "4. Aktor tak terpetakan → 403" -X POST -H 'Accept: application/json' \
  -H "X-ALIM-CONNECT-APPROVAL-KEY: ${APPROVAL_KEY}" -H 'X-ALIM-API-Version: 1' \
  -H 'Content-Type: application/json' \
  -d '{"command_id":"vps-unmapped-'$RANDOM'","request_id":"00000000-0000-0000-0000-000000000000","decision":"approved","approved_duration_days":1,"actor_external_id":"6289999999999","source":"whatsapp"}' \
  "$APPROVAL"
has 'actor_identity_unmapped' "4b. Alasan = actor_identity_unmapped"

# --------------------------------------------------- 5. allowlist IP (opsional)
if [ -n "$ADMIN_SOURCE" ]; then
  code 403 "5. Sumber di luar allowlist diminta → 403" -X POST -H 'Accept: application/json' \
    -H "X-Forwarded-For: ${ADMIN_SOURCE}" \
    -H "X-ALIM-CONNECT-APPROVAL-KEY: ${APPROVAL_KEY}" -H 'X-ALIM-API-Version: 1' "$APPROVAL"
else
  skip "5. Uji allowlist dilewati (--admin-source tidak diberikan)"
fi

# --------------------------------------------------------- 6. kebocoran
LEAKS=0
for needle in SQLSTATE 'Stack trace' '/srv/alim-staging' 'vendor/'; do
  grep -q -- "$needle" /tmp/alim-vps-body.$$ && { fail "6. Body error membocorkan '$needle'"; LEAKS=1; }
done
[ "$LEAKS" -eq 0 ] && pass "6. Body error tanpa SQLSTATE/stack/path"

# ------------------------------------------------- 7. isolasi data sintetis
PERMITS="$(php artisan tinker --execute='echo DB::table("dormitory_permits")->where("purpose","like","Uji integrasi Connect%")->count();' 2>/dev/null | tr -dc '0-9')"
[ "$PERMITS" = "2" ] && pass "7a. Hanya 2 permit sintetis" || fail "7a. Jumlah permit sintetis: ${PERMITS:-?}"

REAL="$(php artisan tinker --execute='
echo DB::table("students")->whereNotNull("nisn")->where("nisn","!=", "")->count();' 2>/dev/null | tr -dc '0-9')"
[ -n "$REAL" ] || fail "7b. query santri gagal dijalankan"
[ "${REAL:-x}" = "0" ] && pass "7b. Tidak ada NISN (data produksi) di DB staging" || fail "7b. Ada ${REAL:-?} santri ber-NISN — kemungkinan salinan data nyata"

# ------------------------------------------------- 8. kontrak mutasi (permit)
say ""
say "Uji kontrak mutasi lewat HTTP (memakai permit sintetis):"
if APP_ENV=staging php artisan alim:connect-staging-verify --base-url "$BASE" 2>&1 | sed 's/^/    /'; then
  pass "8. Kontrak penuh (200/duplicate/409/audit) lulus"
else
  fail "8. Kontrak penuh belum lulus — lihat baris ✘ pada keluaran di atas"
fi

rm -f /tmp/alim-vps-body.$$
say ""
if [ "$FAIL" -eq 0 ]; then
  say "SEMUA PEMERIKSAAN LULUS."
  exit 0
fi
say "$FAIL pemeriksaan GAGAL." >&2
exit 4
