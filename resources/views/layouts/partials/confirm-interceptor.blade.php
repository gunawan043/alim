{{-- Konfirmasi global: form/link/tombol dengan atribut data-confirm memakai modal lord-icon, bukan confirm(). --}}
<x-modal-confirm id="globalConfirmModal" class="zoomIn"
    icon="https://cdn.lordicon.com/gsqxdxog.json"
    primaryColor="#f7b84b" secondaryColor="#f06548"
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
