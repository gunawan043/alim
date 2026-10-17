{{-- Konfirmasi global: form dengan atribut data-confirm akan memakai modal lord-icon, bukan confirm(). --}}
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

        document.addEventListener('submit', function (event) {
            var form = event.target.closest('form[data-confirm]');
            if (!form || form.dataset.confirmed === '1') return;
            if (!window.bootstrap) return;

            event.preventDefault();
            pendingForm = form;

            var msg = document.getElementById('globalConfirmMessage');
            if (msg) msg.textContent = form.getAttribute('data-confirm') || 'Lanjutkan aksi ini?';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('globalConfirmModal')).show();
        });

        document.getElementById('globalConfirmSubmit')?.addEventListener('click', function () {
            if (!pendingForm) return;
            pendingForm.dataset.confirmed = '1';
            var modalEl = document.getElementById('globalConfirmModal');
            bootstrap.Modal.getInstance(modalEl)?.hide();
            pendingForm.requestSubmit();
            pendingForm = null;
        });
    })();
</script>
@endpush
