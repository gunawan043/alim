{{-- Konfirmasi global: form/tombol data-confirm dan pola confirm() lama memakai modal, bukan dialog browser. --}}
<x-modal-confirm id="globalConfirmModal" class="zoomIn"
    iconClass="ri-send-plane-line"
    title="Konfirmasi"
    submitLabel="Ya, Lanjutkan"
    submitIcon="ri-check-line"
    submitClass="btn-primary"
    submitId="globalConfirmSubmit">
    <p class="text-center text-muted mb-0" id="globalConfirmMessage">Lanjutkan aksi ini?</p>
</x-modal-confirm>

@push('scripts')
<script>
    (function () {
        'use strict';

        var pendingForm = null;
        var pendingButton = null;
        var pendingAction = null;

        function openConfirm(message, action) {
            pendingAction = action || null;
            var msg = document.getElementById('globalConfirmMessage');
            if (msg) msg.textContent = message || 'Lanjutkan aksi ini?';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('globalConfirmModal')).show();
        }

        function resetPending() {
            pendingForm = null;
            pendingButton = null;
            pendingAction = null;
        }

        function parseConfirmMessage(attribute) {
            if (!attribute) return null;

            var start = attribute.indexOf('confirm(');
            if (start === -1) return null;

            var body = attribute.slice(start + 'confirm('.length).trim();
            var end = body.lastIndexOf(')');
            if (end !== -1) body = body.slice(0, end).trim();
            if ((body.charAt(0) === "'" && body.charAt(body.length - 1) === "'")
                || (body.charAt(0) === '"' && body.charAt(body.length - 1) === '"')) {
                body = body.slice(1, -1);
            }

            return body.replace(/\\n/g, '\n').replace(/\\'/g, "'").replace(/\\"/g, '"').trim();
        }

        // Konversi pola lama confirm() pada form/tombol menjadi data-confirm
        // agar semua halaman memakai modal yang sama.
        document.querySelectorAll('form[onsubmit*="confirm"]').forEach(function (form) {
            var message = parseConfirmMessage(form.getAttribute('onsubmit'));
            if (message) form.setAttribute('data-confirm', message);
            form.removeAttribute('onsubmit');
        });

        document.querySelectorAll('button[onclick*="confirm"], input[type="submit"][onclick*="confirm"]').forEach(function (button) {
            var form = button.closest('form');
            if (!form) return;
            var message = parseConfirmMessage(button.getAttribute('onclick'));
            if (message) form.setAttribute('data-confirm', message);
            button.removeAttribute('onclick');
        });

        document.addEventListener('submit', function (event) {
            var form = event.target.closest('form[data-confirm]');
            if (!form || form.dataset.confirmed === '1' || !window.bootstrap) return;

            event.preventDefault();
            pendingForm = form;
            pendingButton = event.submitter || null;
            openConfirm(form.getAttribute('data-confirm'));
        });

        document.addEventListener('click', function (event) {
            if (!window.bootstrap) return;

            var button = event.target.closest('button[data-confirm]');
            if (button && button.form && !button.form.hasAttribute('data-confirm')) {
                event.preventDefault();
                pendingForm = button.form;
                pendingButton = button;
                openConfirm(button.getAttribute('data-confirm'));
                return;
            }

            var link = event.target.closest('a[data-confirm]');
            if (!link || event.ctrlKey || event.metaKey || event.shiftKey || link.target === '_blank') return;
            event.preventDefault();
            openConfirm(link.getAttribute('data-confirm'), function () { window.location.href = link.href; });
        });

        document.getElementById('globalConfirmSubmit')?.addEventListener('click', function () {
            bootstrap.Modal.getInstance(document.getElementById('globalConfirmModal'))?.hide();

            if (pendingForm) {
                pendingForm.dataset.confirmed = '1';
                if (pendingButton && pendingForm.contains(pendingButton)) {
                    pendingForm.requestSubmit(pendingButton);
                } else {
                    pendingForm.requestSubmit();
                }
            } else if (pendingAction) {
                pendingAction();
            }
            resetPending();
        });

        document.getElementById('globalConfirmModal')?.addEventListener('hidden.bs.modal', resetPending);
    })();
</script>
@endpush
