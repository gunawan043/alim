@extends('layouts.master')
@section('title') QR Scanner @endsection

@section('css')
<style>
#qr-reader { width: 100%; max-width: 500px; margin: 0 auto; border: 2px solid #ddd !important; border-radius: 8px; overflow: hidden; }
#qr-reader video { width: 100% !important; border-radius: 8px; }
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
                <div id="qr-result" class="d-none">
                    <div class="alert alert-info">
                        <h5 id="result-name"></h5>
                        <p class="mb-1"><strong>Kode:</strong> <span id="result-code"></span></p>
                        <p class="mb-1"><strong>Ruang:</strong> <span id="result-room"></span></p>
                        <p class="mb-1"><strong>Kondisi:</strong> <span id="result-condition"></span></p>
                        <p class="mb-1"><strong>Status:</strong> <span id="result-status"></span></p>
                    </div>
                    <a id="result-link" href="#" class="btn btn-primary"><i class="ri-eye-line me-1"></i> Lihat Detail</a>
                </div>
                <div id="qr-error" class="d-none">
                    <div class="alert alert-danger" id="error-message"></div>
                </div>
                <button id="start-scan" class="btn btn-success mt-3"><i class="ri-scan-line me-1"></i> Mulai Scan</button>
                <button id="stop-scan" class="btn btn-danger mt-3 d-none"><i class="ri-stop-line me-1"></i> Stop</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let scanner = null;

function onScanSuccess(decodedText) {
    fetch('/sarpras/qr/lookup?code=' + encodeURIComponent(decodedText))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                $('#result-name').text(data.asset.asset_name);
                $('#result-code').text(data.asset.asset_code || '-');
                $('#result-room').text(data.asset.room_name || '-');
                $('#result-condition').text(data.asset.condition.replace('_',' '));
                $('#result-status').text(data.asset.status.replace('_',' '));
                $('#result-link').attr('href', '/sarpras/aset/' + data.asset.id);
                $('#qr-result').removeClass('d-none');
                $('#qr-error').addClass('d-none');
            } else {
                $('#error-message').text('Aset tidak ditemukan.');
                $('#qr-error').removeClass('d-none');
                $('#qr-result').addClass('d-none');
            }
        })
        .catch(() => {
            $('#error-message').text('Terjadi kesalahan. Coba lagi.');
            $('#qr-error').removeClass('d-none');
        });
}

$('#start-scan').on('click', function() {
    scanner = new Html5Qrcode('qr-reader');
    scanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: 250 },
        onScanSuccess
    );
    $('#start-scan').addClass('d-none');
    $('#stop-scan').removeClass('d-none');
});

$('#stop-scan').on('click', function() {
    if (scanner) {
        scanner.stop();
        scanner = null;
    }
    $('#start-scan').removeClass('d-none');
    $('#stop-scan').addClass('d-none');
});
</script>
@endsection
