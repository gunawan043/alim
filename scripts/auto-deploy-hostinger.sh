#!/usr/bin/env bash
# =============================================================================
# ALIM — auto deploy ke Hostinger setelah `git push` dari mesin ini.
# =============================================================================
# Script ini dipanggil oleh .git/hooks/pre-push (lihat scripts/install-auto-deploy.sh).
# Yang dikirim adalah ISI COMMIT (git archive), bukan working tree. Perubahan
# yang belum di-commit tidak pernah ikut ke server, sehingga kode produksi
# selalu sama dengan commit yang di-push.
# =============================================================================

set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REMOTE_ALIAS="alim-deploy"
REMOTE_APP="alim-app"
REMOTE_DEPLOY="alim-deploy-live.sh"
LOG_FILE="${ALIM_DEPLOY_LOG:-/tmp/alim-auto-deploy.log}"
DEPLOY_REF="${ALIM_DEPLOY_REF:-HEAD}"

# Koneksi ke Hostinger diputus saat menganggur ~2 menit (rsync menghitung
# checksum ribuan file di server). Keepalive menahan sesi tetap hidup.
SSH_CMD="ssh -o BatchMode=yes -o ServerAliveInterval=15 -o ServerAliveCountMax=20"

timestamp() {
    date '+%Y-%m-%d %H:%M:%S'
}

log() {
    echo "[$(timestamp)] $1" >>"${LOG_FILE}"
}

if ! command -v rsync >/dev/null 2>&1; then
    log "ERROR: rsync tidak ditemukan."
    exit 1
fi

if ! git -C "${ROOT_DIR}" rev-parse --verify "${DEPLOY_REF}^{commit}" >/dev/null 2>&1; then
    log "ERROR: ref '${DEPLOY_REF}' tidak ditemukan; deploy dibatalkan."
    exit 1
fi

COMMIT_SHA="$(git -C "${ROOT_DIR}" rev-parse --verify "${DEPLOY_REF}^{commit}")"
COMMIT_SHORT="$(git -C "${ROOT_DIR}" rev-parse --short "${COMMIT_SHA}")"
COMMIT_SUBJECT="$(git -C "${ROOT_DIR}" log -1 --pretty=%s "${COMMIT_SHA}")"
DIRTY_COUNT="$(git -C "${ROOT_DIR}" status --porcelain | wc -l | tr -d ' ')"

# Beri waktu singkat agar proses `git push` selesai lebih dulu.
sleep "${ALIM_DEPLOY_DELAY:-8}"

# -----------------------------------------------------------------------------
# PENJAGA REMOTE: rsync di bawah memakai --delete, jadi berkas di server yang
# tidak ada di dalam commit akan DIHAPUS. Pada 2026-11-25 production memuat
# 6.830 berkas di luar commit (termasuk 35 migration yang sudah "Ran").
# Periksa dulu worktree server; bila kotor, batalkan sebelum satu byte pun
# terkirim. Tidak ada override lewat environment — sama seperti deploy.sh.
# -----------------------------------------------------------------------------
REMOTE_DIRTY="$(${SSH_CMD} "${REMOTE_ALIAS}" "cd ${REMOTE_APP} && git status --porcelain | wc -l" 2>/dev/null | tr -d '[:space:]')"

if [ -z "${REMOTE_DIRTY}" ]; then
    log "ERROR: gagal membaca status worktree server; deploy dibatalkan (tidak bisa memverifikasi keamanan)."
    exit 3
fi

if [ "${REMOTE_DIRTY}" -gt 0 ]; then
    log "ERROR: worktree server memuat ${REMOTE_DIRTY} berkas di luar commit."
    log "ERROR: rsync --delete akan MENGHAPUS berkas tersebut; deploy dibatalkan."
    log "Rujukan: docs/integration/alim-core-production-remediation-cycle-1.md"
    exit 3
fi

log "============================================================"
log "AUTO DEPLOY DIMULAI (git archive)"
log "Repo: ${ROOT_DIR}"
log "Commit: ${COMMIT_SHORT} — ${COMMIT_SUBJECT}"
if [ "${DIRTY_COUNT}" -gt 0 ]; then
    log "Catatan: ${DIRTY_COUNT} file lokal belum di-commit — tidak ikut dikirim."
fi

EXPORT_DIR="$(mktemp -d "${TMPDIR:-/tmp}/alim-deploy.XXXXXX")"
if [ -z "${EXPORT_DIR}" ] || [ ! -d "${EXPORT_DIR}" ]; then
    log "ERROR: gagal membuat direktori sementara."
    exit 1
fi

# mktemp membuat direktori 700. rsync -a menyalin izin direktori root ke
# server, dan web server tidak bisa menembus direktori 700 -> seluruh situs
# jadi 404. Paksa 755 supaya docroot tetap dapat diakses.
chmod 755 "${EXPORT_DIR}"

cleanup() {
    rm -rf "${EXPORT_DIR}"
}
trap cleanup EXIT

if ! git -C "${ROOT_DIR}" archive --format=tar "${COMMIT_SHA}" | tar -xf - -C "${EXPORT_DIR}"; then
    log "ERROR: gagal mengekspor commit ${COMMIT_SHORT}; deploy dibatalkan."
    exit 1
fi

log "Export selesai; mengirim ${COMMIT_SHORT} ke server..."

# --checksum: git archive menyamakan mtime semua file, jadi perbandingan
# ukuran+waktu akan mengirim ulang seluruh aset setiap deploy. Dengan
# checksum, hanya file yang isinya benar-benar berubah yang dikirim.
# Catatan: pada koneksi lambat, server kadang memutus sesi saat menghitung
# checksum ribuan berkas. Setel ALIM_DEPLOY_NOCHECKSUM=1 untuk memakai
# perbandingan ukuran+waktu bila itu terjadi.
RSYNC_FLAGS=(-az --partial --delete --timeout=120)
if [ "${ALIM_DEPLOY_NOCHECKSUM:-0}" != "1" ]; then
    RSYNC_FLAGS+=(-c)
fi
DRY_RUN="${ALIM_DEPLOY_DRY_RUN:-0}"
if [ "${DRY_RUN}" = "1" ]; then
    RSYNC_FLAGS+=(-n --itemize-changes)
    log "MODE UJI (dry-run): tidak ada file yang diubah."
fi

rsync "${RSYNC_FLAGS[@]}" \
    --exclude='.git/' \
    --exclude='.env' \
    --exclude='.env.backup' \
    --exclude='.env.testing' \
    --exclude='.phpunit.result.cache' \
    --exclude='PROJECT/' \
    --exclude='* 2.php' \
    --exclude='* 2.md' \
    --exclude='node_modules/' \
    --exclude='vendor/' \
    --exclude='storage/' \
    --exclude='database/database.sqlite' \
    --exclude='public/storage' \
    --exclude='public/bump' \
    --exclude='bootstrap/cache/' \
    --exclude='.claude/' \
    --exclude='phpbrew/' \
    --exclude='._*' \
    --exclude='.DS_Store' \
    --exclude='cookies.txt' \
    --exclude='_dorm_search_results.txt' \
    --exclude='tmp-*.html' \
    --exclude='*.log' \
    --exclude='.user.ini' \
    -e "${SSH_CMD}" \
    "${EXPORT_DIR}/" "${REMOTE_ALIAS}:${REMOTE_APP}/" >>"${LOG_FILE}" 2>&1

RSYNC_EXIT=$?
if [ "${RSYNC_EXIT}" -ne 0 ]; then
    log "ERROR: rsync gagal (${RSYNC_EXIT}); deploy dibatalkan."
    exit "${RSYNC_EXIT}"
fi

if [ "${DRY_RUN}" = "1" ]; then
    log "MODE UJI selesai: tidak ada perubahan di server."
    exit 0
fi

# Jaring pengaman: docroot harus 755 agar web server bisa menembusnya.
${SSH_CMD} "${REMOTE_ALIAS}" "chmod 755 ${REMOTE_APP}" >>"${LOG_FILE}" 2>&1 || true

log "rsync selesai; menjalankan deploy di server..."
${SSH_CMD} "${REMOTE_ALIAS}" "bash ${REMOTE_DEPLOY}" >>"${LOG_FILE}" 2>&1
SSH_EXIT=$?

if [ "${SSH_EXIT}" -eq 0 ]; then
    # Skema dicek terhadap database sungguhan, bukan cuma migrate:status:
    # migrasi pernah "Ran" padahal tabel belum ada, dan kolom baru yang
    # hilang membuat endpoint balas 500 tanpa ketahuan dari health check.
    log "Memverifikasi skema integrasi ALIM Connect di server..."
    if ${SSH_CMD} "${REMOTE_ALIAS}" "cd ${REMOTE_APP} && /usr/bin/php artisan alim:verify-connect-schema" >>"${LOG_FILE}" 2>&1; then
        log "Skema integrasi ALIM Connect lengkap."
    else
        log "PERINGATAN: skema integrasi ALIM Connect BELUM lengkap (lihat log di atas)."
        log "Jalankan: ssh ${REMOTE_ALIAS} \"cd ${REMOTE_APP} && /usr/bin/php artisan migrate --force\""
        SCHEMA_WARN=1
    fi

    log "AUTO DEPLOY SELESAI (${COMMIT_SHORT})"
    if [ "${SCHEMA_WARN:-0}" -eq 1 ]; then
        log "PERHATIAN: deploy terpasang tetapi ada migrasi yang belum dijalankan."
    fi
else
    log "ERROR: deploy server gagal (${SSH_EXIT})"
fi

exit "${SSH_EXIT}"
