@extends('layouts.master')

@section('title', 'WhatsApp Connect')

@section('css')
<style>
    .whatsapp-connect-frame-wrap {
        position: relative;
        width: 100%;
        height: calc(100vh - 190px);
        min-height: 620px;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        overflow: hidden;
        background: #f8fafc;
    }
    .whatsapp-connect-frame-wrap iframe {
        width: 100%;
        height: 100%;
        border: 0;
        display: block;
    }
    .whatsapp-connect-loading {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: .75rem;
        background: rgba(248, 250, 252, .92);
        z-index: 2;
    }
    @media (max-width: 767.98px) {
        .whatsapp-connect-frame-wrap {
            height: calc(100vh - 150px);
            min-height: 480px;
        }
    }
</style>
@endsection

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
                            Akses WhatsApp Connect tanpa meninggalkan ALIM Core.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-sm" id="open-connect-btn">
                            <i class="ri-external-link-line me-1"></i> Buka di tab baru
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    @if(! $configured)
                        <div class="alert alert-warning mb-0">
                            <strong>WhatsApp Connect belum dikonfigurasi.</strong>
                            Isi <code>CONNECT_SSO_HANDOFF_URL</code> dan
                            <code>CONNECT_SSO_FRAME_ORIGIN</code> pada environment Core.
                            Halaman ini tidak akan menampilkan iframe sampai konfigurasi tersedia.
                        </div>
                    @else
                        <div id="connect-alert" class="alert alert-danger d-none" role="alert"></div>

                        <div class="whatsapp-connect-frame-wrap" id="connect-frame-wrap">
                            <div class="whatsapp-connect-loading" id="connect-loading">
                                <div class="spinner-border text-success" role="status" aria-hidden="true"></div>
                                <div class="small text-muted">Menyiapkan sesi WhatsApp Connect…</div>
                            </div>
                            <iframe
                                name="connect-frame"
                                id="connect-frame"
                                title="WhatsApp Connect"
                                allow="clipboard-read; clipboard-write"
                            ></iframe>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="button" class="btn btn-outline-success btn-sm" id="reload-connect-btn">
                                <i class="ri-refresh-line me-1"></i> Muat ulang sesi
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="new-tab-connect-btn">
                                <i class="ri-external-link-line me-1"></i> Buka di tab baru jika iframe gagal
                            </button>
                        </div>
                        <p class="text-muted small mt-2 mb-0">
                            Jika Connect meminta login ulang, tekan “Muat ulang sesi”.
                            Logout ALIM Core tidak otomatis mengakhiri sesi Connect.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<form id="connect-handoff-form" method="POST" action="" target="connect-frame" class="d-none" aria-hidden="true">
    <input type="hidden" name="code" id="connect-handoff-code">
    <input type="hidden" name="audience" value="connect">
    <input type="hidden" name="scope" value="whatsapp.embed">
</form>
@endsection

@push('scripts')
@if($configured)
<script>
(function () {
    const handoffUrl = @json($handoffUrl);
    const connectSsoUrl = @json($connectSsoUrl);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const form = document.getElementById('connect-handoff-form');
    const codeInput = document.getElementById('connect-handoff-code');
    const frame = document.getElementById('connect-frame');
    const loading = document.getElementById('connect-loading');
    const alertBox = document.getElementById('connect-alert');

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
        const response = await fetch(handoffUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const body = await response.json().catch(() => ({}));

        if (!response.ok || !body.success) {
            throw new Error(body.message || 'Gagal membuat sesi WhatsApp Connect.');
        }

        if (!body.data || !body.data.code || !body.data.connect_sso_url) {
            throw new Error('Respons handoff tidak lengkap.');
        }

        return body.data;
    }

    function submitToTarget(data, target) {
        form.action = data.connect_sso_url || connectSsoUrl;
        form.target = target;
        codeInput.value = data.code;
        form.submit();
    }

    function loadIframe() {
        clearError();
        loading.classList.remove('d-none');

        requestHandoff()
            .then((data) => {
                submitToTarget(data, 'connect-frame');
            })
            .catch((error) => {
                loading.classList.add('d-none');
                showError(error.message);
            });
    }

    function openNewTab() {
        clearError();

        // Buka window secara sinkron agar tidak diblokir popup blocker.
        const targetWindow = window.open('about:blank', '_blank');

        if (!targetWindow) {
            showError('Popup diblokir browser. Izinkan popup untuk membuka WhatsApp Connect.');
            return;
        }

        requestHandoff()
            .then((data) => {
                const newForm = targetWindow.document.createElement('form');
                newForm.method = 'POST';
                newForm.action = data.connect_sso_url || connectSsoUrl;
                newForm.target = '_self';

                ['code', 'audience', 'scope'].forEach((name) => {
                    const input = targetWindow.document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = name === 'code' ? data.code : (name === 'audience' ? 'connect' : 'whatsapp.embed');
                    newForm.appendChild(input);
                });

                targetWindow.document.body.appendChild(newForm);
                newForm.submit();
            })
            .catch((error) => {
                try { targetWindow.close(); } catch (e) {}
                showError(error.message);
            });
    }

    frame?.addEventListener('load', function () {
        loading?.classList.add('d-none');
    });

    document.getElementById('open-connect-btn')?.addEventListener('click', openNewTab);
    document.getElementById('new-tab-connect-btn')?.addEventListener('click', openNewTab);
    document.getElementById('reload-connect-btn')?.addEventListener('click', loadIframe);

    loadIframe();
})();
</script>
@endif
@endpush
