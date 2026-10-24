{{-- Modal konfirmasi global: menggantikan confirm() bawaan browser pada form/aksi. --}}
<div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-labelledby="globalConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="avatar-lg mx-auto mb-3">
                    <span class="avatar-title bg-warning-subtle rounded-circle">
                        <i class="ri-question-line fs-1 text-warning"></i>
                    </span>
                </div>
                <h5 id="globalConfirmTitle" class="mb-2">Konfirmasi</h5>
                <p id="globalConfirmMessage" class="text-muted mb-4">Apakah Anda yakin ingin melanjutkan?</p>
                <div class="hstack gap-2 justify-content-center">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1"></i>Batal
                    </button>
                    <button type="button" id="globalConfirmButton" class="btn btn-primary">
                        <i class="ri-check-line me-1"></i>Ya, Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    'use strict';

    function parseConfirmMessage(attribute) {
        if (!attribute) return null;

        const start = attribute.indexOf('confirm(');
        if (start === -1) return null;

        let body = attribute.slice(start + 'confirm('.length).trim();
        const end = body.lastIndexOf(')');
        if (end !== -1) body = body.slice(0, end).trim();
        if ((body.startsWith("'") && body.endsWith("'")) || (body.startsWith('"') && body.endsWith('"'))) {
            body = body.slice(1, -1);
        }

        return body
            .replace(/\\n/g, '\n')
            .replace(/\\'/g, "'")
            .replace(/\\"/g, '"')
            .trim();
    }

    function prepareForm(form, message) {
        if (!form) return;

        const text = message || form.getAttribute('data-confirm-message') || 'Apakah Anda yakin ingin melanjutkan?';
        form.setAttribute('data-confirm-message', text);
    }

    function prepareControls() {
        document.querySelectorAll('form[onsubmit*="confirm"]').forEach(function (form) {
            prepareForm(form, parseConfirmMessage(form.getAttribute('onsubmit')));
            form.removeAttribute('onsubmit');
        });

        document.querySelectorAll('button[onclick*="confirm"], input[type="submit"][onclick*="confirm"]').forEach(function (button) {
            const form = button.closest('form');
            if (!form) return;

            prepareForm(form, parseConfirmMessage(button.getAttribute('onclick')));
            button.removeAttribute('onclick');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('globalConfirmModal');
        if (!modalElement || !window.bootstrap) return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const messageElement = document.getElementById('globalConfirmMessage');
        const confirmButton = document.getElementById('globalConfirmButton');
        let pendingForm = null;
        let pendingSubmitter = null;

        prepareControls();

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form || form.dataset.globalConfirmBypass === '1') return;

            const message = form.getAttribute('data-confirm-message');
            if (!message) return;

            event.preventDefault();
            pendingForm = form;
            pendingSubmitter = event.submitter || null;

            if (messageElement) messageElement.textContent = message;
            if (confirmButton) {
                const destructive = /hapus|batalkan|tolak|delete|remove|nonaktifkan|cabut/i.test(message);
                confirmButton.className = 'btn ' + (destructive ? 'btn-danger' : 'btn-primary');
            }

            modal.show();
        }, true);

        confirmButton?.addEventListener('click', function () {
            if (!pendingForm) return;

            const form = pendingForm;
            const submitter = pendingSubmitter;
            pendingForm = null;
            pendingSubmitter = null;

            form.dataset.globalConfirmBypass = '1';
            modal.hide();

            if (submitter && typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter);
            } else {
                form.submit();
            }

            setTimeout(function () {
                delete form.dataset.globalConfirmBypass;
            }, 0);
        });
    });
})();
</script>
