#!/usr/bin/env bash
# =============================================================================
# ALIM — Auto Deploy Script
# Dipanggil otomatis oleh webhook saat ada push ke branch utama.
# =============================================================================
# Lokasi yang direkomendasikan: /var/www/alim/deploy.sh
# Set permission: chmod +x deploy.sh
# Pastikan www-data (user PHP-FPM) bisa membaca & mengeksekusi script ini.
# =============================================================================

set -euo pipefail

# ---------- Konfigurasi ----------
APP_DIR="/var/www/alim"
BRANCH="main"
PHP_BIN="php"
COMPOSER_BIN="composer"
NPM_BIN="npm"
KEEP_RELEASES=5

# Jika deploy.sh diletakkan di dalam project, APP_DIR auto-detect:
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "${SCRIPT_DIR}/artisan" ]; then
    APP_DIR="${SCRIPT_DIR}"
fi

LOG_DIR="${APP_DIR}/storage/logs"
mkdir -p "${LOG_DIR}"
DEPLOY_LOG="${LOG_DIR}/deploy-$(date +%Y-%m-%d).log"

log() {
    local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $1"
    echo "${msg}" | tee -a "${DEPLOY_LOG}"
}

# ---------- Lock (anti race) ----------
LOCK_FILE="/tmp/alim-deploy.lock"
exec 9>"${LOCK_FILE}"
if ! flock -n 9; then
    log "ERROR: Deploy lain sedang berjalan. Exit."
    exit 1
fi

cd "${APP_DIR}"

START_TIME=$(date +%s)
log "============================================================"
log "DEPLOY DIMULAI"
log "Direktori : ${APP_DIR}"
log "Branch    : ${BRANCH}"
log "============================================================"

# ---------- 1. Git fetch & reset ----------
log "[1/7] Git pull..."
git fetch origin "${BRANCH}" 2>&1 | tee -a "${DEPLOY_LOG}"
git reset --hard "origin/${BRANCH}" 2>&1 | tee -a "${DEPLOY_LOG}"

NEW_COMMIT=$(git rev-parse --short HEAD)
log "Sekarang di commit: ${NEW_COMMIT}"

# ---------- 2. Composer ----------
log "[2/7] Composer install..."
if [ -f composer.json ]; then
    if command -v "${COMPOSER_BIN}" >/dev/null 2>&1; then
        COMPOSER_MEMORY_LIMIT=-1 ${COMPOSER_BIN} install \
            --no-dev \
            --optimize-autoloader \
            --no-interaction \
            --prefer-dist 2>&1 | tee -a "${DEPLOY_LOG}"
    else
        log "  (composer tidak tersedia, skip — pastikan dependency sudah terinstall)"
    fi
else
    log "  (tidak ada composer.json, skip)"
fi

# ---------- 3. Migrate ----------
log "[3/7] Artisan migrate..."
${PHP_BIN} artisan migrate --force --no-interaction 2>&1 | tee -a "${DEPLOY_LOG}"

# ---------- 4. NPM build ----------
log "[4/7] NPM build..."
if [ -f package.json ]; then
    if ! command -v "${NPM_BIN}" >/dev/null 2>&1; then
        log "  (npm/node tidak tersedia di server, skip — pastikan public/build/ sudah di-commit)"
    else
        if [ -f package-lock.json ]; then
            ${NPM_BIN} ci --no-audit --no-fund 2>&1 | tee -a "${DEPLOY_LOG}"
        else
            ${NPM_BIN} install --no-audit --no-fund 2>&1 | tee -a "${DEPLOY_LOG}"
        fi
        ${NPM_BIN} run build 2>&1 | tee -a "${DEPLOY_LOG}"
    fi
else
    log "  (tidak ada package.json, skip)"
fi

# ---------- 5. Storage link ----------
log "[5/7] Storage:link..."
${PHP_BIN} artisan storage:link --force 2>&1 | tee -a "${DEPLOY_LOG}" || true

# ---------- 6. Cache & optimize ----------
log "[6/7] Clear & rebuild cache..."
${PHP_BIN} artisan config:clear 2>&1 | tee -a "${DEPLOY_LOG}"
${PHP_BIN} artisan route:clear 2>&1 | tee -a "${DEPLOY_LOG}"
${PHP_BIN} artisan view:clear 2>&1 | tee -a "${DEPLOY_LOG}"
${PHP_BIN} artisan cache:clear 2>&1 | tee -a "${DEPLOY_LOG}"
${PHP_BIN} artisan config:cache 2>&1 | tee -a "${DEPLOY_LOG}"
${PHP_BIN} artisan route:cache 2>&1 | tee -a "${DEPLOY_LOG}"
${PHP_BIN} artisan view:cache 2>&1 | tee -a "${DEPLOY_LOG}"

# ---------- 7. Restart workers & PHP-FPM ----------
log "[7/7] Restart workers..."
if [ -f artisan ]; then
    ${PHP_BIN} artisan queue:restart 2>&1 | tee -a "${DEPLOY_LOG}" || true
fi

# Reload PHP-FPM (jika ada permission, jika tidak skip)
if command -v systemctl >/dev/null 2>&1; then
    if systemctl list-unit-files php*-fpm.service >/dev/null 2>&1; then
        PHP_VER=$(php -r 'echo PHP_VERSION;' | cut -d. -f1,2)
        sudo -n systemctl reload "php${PHP_VER}-fpm" 2>&1 | tee -a "${DEPLOY_LOG}" || \
            log "  (skip reload php-fpm, butuh sudo)"
    fi
fi

# ---------- Health check ----------
log "Health check..."
HEALTH_URL="${HEALTH_URL:-http://127.0.0.1/health}"
if curl -fsS --max-time 5 "${HEALTH_URL}" >/dev/null 2>&1; then
    log "✓ Health check OK"
else
    log "⚠ Health check gagal (mungkin domain belum propagated, tidak fatal)"
fi

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
log "============================================================"
log "DEPLOY SELESAI dalam ${DURATION} detik"
log "Commit akhir: ${NEW_COMMIT}"
log "============================================================"
