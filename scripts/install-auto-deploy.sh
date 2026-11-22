#!/usr/bin/env bash
# Pasang pre-push hook untuk auto deploy ALIM ke Hostinger.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOOK="${ROOT_DIR}/.git/hooks/pre-push"

if [ ! -d "${ROOT_DIR}/.git" ]; then
    echo "Bukan git repository: ${ROOT_DIR}" >&2
    exit 1
fi

chmod +x "${ROOT_DIR}/scripts/auto-deploy-hostinger.sh"

cat >"${HOOK}" <<'HOOK'
#!/bin/sh
# Auto deploy ALIM ke Hostinger setiap `git push` dari mesin ini.
# Yang dikirim adalah isi commit yang di-push, bukan working tree.
ROOT_DIR="$(git rev-parse --show-toplevel)"

DEPLOY_REF=""
while read -r local_ref local_sha remote_ref remote_sha; do
    [ -n "${local_sha}" ] || continue
    # Abaikan penghapusan branch (sha nol).
    case "${local_sha}" in
        0000000000000000000000000000000000000000) continue ;;
    esac
    DEPLOY_REF="${local_sha}"
    break
done

ALIM_DEPLOY_REF="${DEPLOY_REF:-HEAD}" \
    nohup "${ROOT_DIR}/scripts/auto-deploy-hostinger.sh" \
    >>/tmp/alim-auto-deploy.log 2>&1 &

exit 0
HOOK

chmod +x "${HOOK}"
echo "Installed: ${HOOK}"
