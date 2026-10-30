#!/usr/bin/env bash
# =============================================================================
# ALIM — auto deploy ke Hostinger setelah `git push` dari mesin ini.
# =============================================================================
# Script ini dipanggil oleh .git/hooks/pre-push (lihat scripts/install-auto-deploy.sh).
# Mode ini sengaja memakai rsync, bukan git reset, agar perubahan lokal yang
# belum di-commit tetap ikut terkirim ke server.
# =============================================================================

set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REMOTE_ALIAS="alim-deploy"
REMOTE_APP="alim-app"
REMOTE_DEPLOY="alim-deploy-live.sh"
LOG_FILE="${ALIM_DEPLOY_LOG:-/tmp/alim-auto-deploy.log}"

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

# Beri waktu singkat agar proses `git push` selesai lebih dulu. Server tetap
# memakai working tree lokal, jadi perubahan yang belum di-commit ikut terkirim.
sleep "${ALIM_DEPLOY_DELAY:-8}"

log "============================================================"
log "AUTO DEPLOY DIMULAI (rsync)"
log "Repo: ${ROOT_DIR}"

rsync -az --partial --delete \
    --exclude='.git/' \
    --exclude='.env' \
    --exclude='.env.backup' \
    --exclude='node_modules/' \
    --exclude='vendor/' \
    --exclude='storage/' \
    --exclude='database/database.sqlite' \
    --exclude='public/storage' \
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
    "${ROOT_DIR}/" "${REMOTE_ALIAS}:${REMOTE_APP}/" >>"${LOG_FILE}" 2>&1

RSYNC_EXIT=$?
if [ "${RSYNC_EXIT}" -ne 0 ]; then
    log "ERROR: rsync gagal (${RSYNC_EXIT}); deploy dibatalkan."
    exit "${RSYNC_EXIT}"
fi

log "rsync selesai; menjalankan deploy di server..."
ssh -o BatchMode=yes "${REMOTE_ALIAS}" "bash ${REMOTE_DEPLOY}" >>"${LOG_FILE}" 2>&1
SSH_EXIT=$?

if [ "${SSH_EXIT}" -eq 0 ]; then
    log "AUTO DEPLOY SELESAI"
else
    log "ERROR: deploy server gagal (${SSH_EXIT})"
fi

exit "${SSH_EXIT}"
