@extends('layouts.master')

@section('title', 'WhatsApp Connect')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="card-title mb-1">
                            <i class="ri-whatsapp-line text-success me-1"></i> WhatsApp Connect
                        </h5>
                        <p class="text-muted mb-0 small">
                            WhatsApp Connect dibuka di tab baru.
                        </p>
                    </div>
                </div>

                <div class="card-body">
                    @if(! $configured)
                        <div class="alert alert-warning mb-0">
                            <strong>WhatsApp Connect belum dikonfigurasi.</strong>
                            {{ $notConfiguredReason }}
                            Konfigurasi dibaca dari Pengaturan Sistem → WhatsApp Connect atau
                            dari environment Core.
                        </div>
                    @else
                        <div id="connect-alert" class="alert alert-danger d-none" role="alert"></div>

                        <p class="text-muted small mb-3">
                            Logout ALIM Core tidak otomatis mengakhiri sesi Connect.
                        </p>

                        <button type="button" class="btn btn-success" id="open-connect-btn">
                            <i class="ri-external-link-line me-1"></i> Buka WhatsApp Connect
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($configured)
<script>
(function () {
    const handoffUrl = @json($handoffUrl);
    const connectSsoUrl = @json($connectSsoUrl);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const alertBox = document.getElementById('connect-alert');
    const openBtn = document.getElementById('open-connect-btn');

    function showError(message) {
        if (!alertBox) return;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function clearError() {
        if (!alertBox) return;
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
    }

    async function requestHandoff() {
        let response;

        try {
            response = await fetch(handoffUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
        } catch (error) {
            throw new Error('ALIM Core tidak dapat dihubungi. Periksa koneksi lalu coba lagi.');
        }

        const body = await response.json().catch(() => ({}));

        if (!response.ok || !body.success) {
            throw new Error(body.message || 'Gagal membuat sesi WhatsApp Connect.');
        }

        if (!body.data || !body.data.code || !body.data.connect_sso_url) {
            throw new Error('Respons handoff tidak lengkap.');
        }

        return body.data;
    }

    function openConnect() {
        clearError();

        // Buka window secara sinkron agar tidak diblokir popup blocker.
        const targetWindow = window.open('about:blank', '_blank');

        if (!targetWindow) {
            showError('Popup diblokir browser. Izinkan popup untuk membuka WhatsApp Connect.');
            return;
        }

        requestHandoff()
            .then((data) => {
                const form = targetWindow.document.createElement('form');
                form.method = 'POST';
                form.action = data.connect_sso_url || connectSsoUrl;
                form.target = '_self';

                // Hanya `code` — Connect memakai whitelist ketat (forbidNonWhitelisted),
                // field tambahan ditolak dengan 400 sebelum kode diproses.
                const codeField = targetWindow.document.createElement('input');
                codeField.type = 'hidden';
                codeField.name = 'code';
                codeField.value = data.code;
                form.appendChild(codeField);

                targetWindow.document.body.appendChild(form);
                form.submit();
            })
            .catch((error) => {
                try { targetWindow.close(); } catch (e) {}
                showError(error.message);
            });
    }

    openBtn?.addEventListener('click', openConnect);
})();
</script>
@endif
@endpush
