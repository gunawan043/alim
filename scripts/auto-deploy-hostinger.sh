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
RSYNC_FLAGS=(-azc --partial --delete)
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
    -e 'ssh -o BatchMode=yes' \
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
ssh -o BatchMode=yes "${REMOTE_ALIAS}" "chmod 755 ${REMOTE_APP}" >>"${LOG_FILE}" 2>&1 || true

log "rsync selesai; menjalankan deploy di server..."
ssh -o BatchMode=yes "${REMOTE_ALIAS}" "bash ${REMOTE_DEPLOY}" >>"${LOG_FILE}" 2>&1
SSH_EXIT=$?

if [ "${SSH_EXIT}" -eq 0 ]; then
    log "AUTO DEPLOY SELESAI (${COMMIT_SHORT})"
else
    log "ERROR: deploy server gagal (${SSH_EXIT})"
fi

exit "${SSH_EXIT}"
