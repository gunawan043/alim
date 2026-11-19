#!/usr/bin/env bash
# Penjadwal Laravel untuk produksi Hostinger.
#
# Dipanggil cron setiap menit melalui path yang sudah terdaftar di hPanel:
#   ~/domains/alim.abuhurairah.id/public_html/webhook/run.sh
# public_html adalah symlink ke alim-app/public, sehingga file ini ikut
# ter-deploy lewat rsync.
#
# Menjalankan semua tugas terjadwal: pengingat kembali asrama, pengingat
# todo, pengingat jam pelajaran, backup, dan tugas lain di routes/console.php.

set -uo pipefail

APP_DIR="/home/u625880896/alim-app"
PHP_BIN="/usr/bin/php"
LOG_FILE="${APP_DIR}/storage/logs/scheduler.log"

cd "${APP_DIR}" || exit 1

# Batasi ukuran log agar tidak tumbuh tanpa henti.
if [ -f "${LOG_FILE}" ] && [ "$(wc -c <"${LOG_FILE}")" -gt 5242880 ]; then
    : >"${LOG_FILE}"
fi

"${PHP_BIN}" artisan schedule:run >>"${LOG_FILE}" 2>&1
