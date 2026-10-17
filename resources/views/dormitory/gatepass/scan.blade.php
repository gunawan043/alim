@extends('layouts.master')
@section('title') Scan Gatepass @endsection

@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.gatepass.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">Gatepass</a> @endslot
        @slot('title') Scan Gatepass @endslot
    @endcomponent

    <div class="row"><div class="col-lg-6 mx-auto">
        <div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-qr-scan-2-line me-2 text-primary"></i>Pindai QR Santri</h5></div>
            <div class="card-body">
                <div id="reader" style="width:100%"></div>
                <div class="text-center text-muted small my-2">Arahkan kamera ke QR gatepass santri.</div>
                <form method="POST" action="{{ route('user.asrama.gatepass.process', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" id="gatepassForm">
                    @csrf
                    <label class="form-label">Token QR (otomatis dari kamera / tempel manual)</label>
                    <input type="text" name="token" id="gatepassToken" class="form-control" required>
                    <div class="row g-2 mt-1">
                        <div class="col-md-6"><input type="text" name="purpose" class="form-control" placeholder="Tujuan (opsional)"></div>
                        <div class="col-md-6"><input type="text" name="gate" class="form-control" placeholder="Gerbang (opsional)"></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3"><i class="ri-check-double-line me-1"></i>Proses Gatepass</button>
                </form>
            </div>
        </div>
    </div></div>
@endsection

@section('script')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    (function () {
        if (typeof Html5Qrcode === 'undefined') return;
        var scanner = new Html5Qrcode('reader');
        var input = document.getElementById('gatepassToken');

        scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 240 }, function (text) {
            input.value = text;
            scanner.stop().catch(function () {});
            document.getElementById('gatepassForm').submit();
        }).catch(function () {
            var box = document.getElementById('reader');
            if (box) box.innerHTML = '<div class="alert alert-warning small mb-0">Kamera tidak dapat diakses. Tempel token QR secara manual.</div>';
        });
    })();
</script>
@endsection
