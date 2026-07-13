@extends('layouts.master')
@section('title') QR Scanner @endsection

@section('css')
<style>
#qr-reader { width: 100%; max-width: 500px; margin: 0 auto; border: 2px solid #ddd !important; border-radius: 8px; overflow: hidden; }
#qr-reader video { width: 100% !important; border-radius: 8px; }
.result-card { display:none; }
.result-card.show { display:block; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.qr.index') }}">QR Code</a> @endslot
    @slot('title') Scanner @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Scan QR Code Aset</h5></div>
            <div class="card-body text-center">
                <div id="qr-reader" class="mb-3"></div>

                {{-- Auto-scan mode: go directly to passport --}}
                <div id="qr-result" class="d-none">
                    <div class="alert alert-success">
                        <h5><i class="mdi mdi-check-circle"></i> <span id="result-name"></span></h5>
                        <div class="row text-start mt-2">
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Kode:</strong> <span id="result-code"></span></p>
                                <p class="mb-1"><strong>Ruang:</strong> <span id="result-room"></span></p>
                                <p class="mb-1"><strong>Kondisi:</strong> <span id="result-condition"></span></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Status:</strong> <span id="result-status"></span></p>
                                <p class="mb-1"><strong>PIC:</strong> <span id="result-pic"></span></p>
                                <p class="mb-1"><strong>Kategori:</strong> <span id="result-category"></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a id="result-passport-link" href="#" class="btn btn-primary">
                            <i class="ri-eye-line me-1"></i> Buka Asset Passport
                        </a>
                        <a id="result-detail-link" href="#" class="btn btn-outline-secondary">
                            <i class="mdi mdi-information-outline me-1"></i> Lihat Detail Sederhana
                        </a>
                    </div>
                    <div class="mt-2 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto-open-passport">
                        <label class="form-check-label small" for="auto-open-passport">Auto-buka passport setelah scan</label>
                    </div>
                </div>

                <div id="qr-error" class="d-none">
                    <div class="alert alert-danger" id="error-message"></div>
                    <a id="error-link" href="#" class="btn btn-outline-danger btn-sm mt-2">Buka Passport</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let scanner = null;
let lastResult = null;

// Configurable: toggle auto-open passport mode
const AUTO_OPEN = false;

function onScanSuccess(decodedText) {
    fetch('/sarpras/assets/' + encodeURIComponent(decodedText) + '/passport.json')
        .then(r => {
            if (!r.ok) throw new Error('Not found');
            return r.json();
        })
        .then(data => {
            if (data.success && data.data) {
                lastResult = data.data;
                showResult(data.data, data.asset);
            } else if (data.data) {
                // Passport data returned directly (no success envelope)
                lastResult = data.data;
                showResult(data.data, null);
            }
        })
        .catch(() => {
            // Fallback: try basic lookup, then offer passport link
            fetch('/sarpras/qr/lookup?code=' + encodeURIComponent(decodedText))
                .then(r => r.json())
                .then(data2 => {
                    if (data2.success && data2.data && data2.data.asset) {
                        showFallbackResult(decodedText, data2.data.asset);
                    } else {
                        $('#error-message').text('Aset tidak ditemukan dengan kode: ' + decodedText);
                        $('#qr-error').removeClass('d-none');
                        $('#qr-result').addClass('d-none');
                        $('#error-link').attr('href', '#');
                    }
                })
                .catch(() => {
                    $('#error-message').text('Terjadi kesalahan. Coba lagi.');
                    $('#qr-error').removeClass('d-none');
                    $('#qr-result').addClass('d-none');
                });
        });
}

function showResult(passport, asset) {
    // Extract basic info from passport identity
    const identity = passport ? passport.identity : {};
    const risk = passport ? passport.risk : {};

    $('#result-name').text(identity.asset_name || '-');
    $('#result-code').text(identity.asset_code || '-');
    $('#result-room').text((identity.location?.room) || '-');
    $('#result-status').html(getStatusBadge(identity.status));
    $('#result-condition').html(getConditionBadge(identity.status));
    $('#result-pic').text(identity.pic || '-');
    $('#result-category').text(identity.category || '-');

    const passportUrl = '/sarpras/assets/' + (identity.asset_id || decodedText) + '/passport';
    $('#result-passport-link').attr('href', passportUrl);
    $('#result-detail-link').attr('href', '/sarpras/aset/' + (identity.asset_id || decodedText));

    $('#qr-result').removeClass('d-none');
    $('#qr-error').addClass('d-none');

    // Show risk indicators
    if (risk.is_critical) {
        $('#qr-result .alert').removeClass('alert-success').addClass('alert-warning');
        $('#qr-result .alert').prepend('<i class="mdi mdi-alert"></i> <strong>Aset Kritikal:</strong> Perbaikan sering / biaya tinggi.<br>');
    }
}

function showFallbackResult(code, asset) {
    $('#result-name').text(asset.asset_name || '-');
    $('#result-code').text(asset.asset_code || '-');
    $('#result-room').text(asset.room_name || '-');
    $('#result-condition').text((asset.condition || '-').replace(/_/g, ' '));
    $('#result-status').text((asset.status || '-').replace(/_/g, ' '));
    $('#result-pic').text('-');
    $('#result-category').text('-');

    $('#result-passport-link').attr('href', '/sarpras/assets/' + asset.id + '/passport');
    $('#result-detail-link').attr('href', '/sarpras/aset/' + asset.id);

    $('#qr-result').removeClass('d-none');
    $('#qr-error').addClass('d-none');
}

function getStatusBadge(status) {
    const map = {
        'tersedia': 'bg-success',
        'dipakai': 'bg-primary',
        'maintenance': 'bg-warning',
        'rusak': 'bg-danger',
        'dihapus': 'bg-secondary',
    };
    return '<span class="badge ' + (map[status] || 'bg-info') + '">' + (status || '-').replace(/_/g, ' ') + '</span>';
}

function getConditionBadge(condition) {
    const map = {
        'baik': 'text-success fw-bold',
        'rusak_ringan': 'text-warning',
        'rusak_sedang': 'text-danger',
        'rusak_berat': 'text-dark fw-bold',
    };
    return '<span class="' + (map[condition] || 'text-muted') + '">' + (condition || '-').replace(/_/g, ' ') + '</span>';
}

$('#start-scan').on('click', function() {
    scanner = new Html5Qrcode('qr-reader');
    scanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: 250 },
        onScanSuccess
    );
    $('#start-scan').addClass('d-none');
    $('#stop-scan').addClass('d-none');
    $('#qr-result').addClass('d-none');
    $('#qr-error').addClass('d-none');
});

$('#stop-scan').on('click', function() {
    if (scanner) {
        scanner.stop().finally(() => { scanner = null; });
    }
    $('#start-scan').removeClass('d-none');
    $('#stop-scan').addClass('d-none');
});

// Auto-open passport mode
$('#result-passport-link').on('click', function(e) {
    if ($('#auto-open-passport').prop('checked') || AUTO_OPEN) {
        // Don't follow immediately — flash the link color first to confirm
        return;
    }
});

// Auto-navigate when auto-open enabled
setInterval(() => {
    if ($('#auto-open-passport').prop('checked') && lastResult) {
        const passportUrl = $('#result-passport-link').attr('href');
        if (passportUrl) {
            window.location.href = passportUrl;
        }
    }
}, 2000);
</script>
@endsection