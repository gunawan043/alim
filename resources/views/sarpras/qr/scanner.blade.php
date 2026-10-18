@extends('layouts.master')
@section('title') Scan QR Aset @endsection
@section('css')
<style>
    #qr-reader { width:100%; max-width:560px; margin:0 auto; border:2px solid #e2e8f0 !important; border-radius:14px; overflow:hidden; background:#000; }
    #qr-reader video { width:100% !important; border-radius:12px; }
    .scan-result { display:none; }
    .scan-result.show { display:block; }
    .scan-preview { max-height:160px; object-fit:cover; border-radius:12px; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Scan QR Aset
    @endcomponent

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-qr-scan-2-line me-2 text-primary"></i>Kamera Scanner</h5><p class="text-muted small mb-0">Arahkan kamera ke QR unit aset. Kamera memerlukan HTTPS dan izin browser.</p></div><span class="badge bg-info-subtle text-info" id="cameraState">Kamera belum aktif</span></div>
                <div class="card-body text-center">
                    <div id="qr-reader" class="mb-3"></div>
                    <div class="d-flex justify-content-center gap-2 flex-wrap"><button type="button" class="btn btn-primary" id="startScan"><i class="ri-camera-line me-1"></i>Mulai Kamera</button><button type="button" class="btn btn-outline-danger d-none" id="stopScan"><i class="ri-stop-circle-line me-1"></i>Hentikan</button></div>
                    <div class="alert alert-danger mt-3 d-none" id="cameraError"></div>
                    <hr>
                    <form id="manualForm" class="row g-2 align-items-end"><div class="col-8 text-start"><label class="form-label small fw-semibold">Atau masukkan kode aset</label><input type="text" id="manualCode" class="form-control" placeholder="Contoh: SDIT-0001"></div><div class="col-4"><button class="btn btn-outline-primary w-100">Cari Aset</button></div></form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card scan-result" id="resultCard">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-checkbox-circle-line me-1 text-success"></i>Hasil Scan</h5></div>
                <div class="card-body">
                    <div id="resultPhotoWrap" class="mb-3 d-none"><img id="resultPhoto" class="scan-preview w-100" alt="Foto aset"></div>
                    <h5 id="resultName">-</h5>
                    <div class="text-muted small mb-3"><code id="resultCode">-</code></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="text-muted small">Ruang</div><div id="resultRoom" class="fw-medium">-</div></div>
                        <div class="col-6"><div class="text-muted small">Satuan Pendidikan</div><div id="resultSchool" class="fw-medium">-</div></div>
                        <div class="col-6"><div class="text-muted small">Kondisi</div><div id="resultCondition">-</div></div>
                        <div class="col-6"><div class="text-muted small">Status</div><div id="resultStatus">-</div></div>
                    </div>
                    <div class="d-grid gap-2">
                        <a id="passportLink" class="btn btn-primary" href="#"><i class="ri-eye-line me-1"></i>Buka Asset Passport</a>
                        <div class="row g-2"><div class="col-4"><a id="reportLink" class="btn btn-outline-danger w-100" href="#"><i class="ri-error-warning-line d-block fs-5"></i><small>Lapor Rusak</small></a></div><div class="col-4"><a id="repairLink" class="btn btn-outline-warning w-100" href="#"><i class="ri-tools-line d-block fs-5"></i><small>Perbaikan</small></a></div><div class="col-4"><a id="scheduleLink" class="btn btn-outline-info w-100" href="#"><i class="ri-calendar-todo-line d-block fs-5"></i><small>Perawatan</small></a></div></div>
                        <button type="button" class="btn btn-light" id="scanAgain"><i class="ri-refresh-line me-1"></i>Scan Lagi</button>
                    </div>
                </div>
            </div>
            <div class="card" id="emptyCard"><div class="card-body"><x-empty-state title="Belum ada hasil scan" message="Scan QR atau masukkan kode aset untuk menampilkan informasi dan aksi cepat." /></div></div>
        </div>
    </div>
@endsection

@push('script')
<script src="{{ URL::asset('build/vendor/html5-qrcode.min.js') }}"></script>
<script>
(function () {
    const urls = {
        lookup: @json(route('sarpras.qr.lookup.get')),
        passport: @json(route('sarpras.assets.passport', ['uuid' => '__ID__'])),
        detail: @json(route('sarpras.aset.show', ['id' => '__ID__'])),
        report: @json(route('sarpras.user.kerusakan.create')),
        repair: @json(route('sarpras.repair.index')),
        schedule: @json(route('sarpras.pemeliharaan.schedule.index')),
    };
    const readerEl = document.getElementById('qr-reader');
    const startBtn = document.getElementById('startScan');
    const stopBtn = document.getElementById('stopScan');
    const errorEl = document.getElementById('cameraError');
    const stateEl = document.getElementById('cameraState');
    const resultCard = document.getElementById('resultCard');
    const emptyCard = document.getElementById('emptyCard');
    let scanner = null, running = false;

    function showError(message) { errorEl.textContent = message; errorEl.classList.remove('d-none'); }
    function clearError() { errorEl.classList.add('d-none'); errorEl.textContent = ''; }

    async function startCamera() {
        clearError();
        if (!window.Html5Qrcode) { showError('Library scanner tidak termuat. Muat ulang halaman.'); return; }
        scanner = new Html5Qrcode('qr-reader');
        try {
            await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 250, height: 250 } }, onScan, function () {});
            running = true;
            startBtn.classList.add('d-none'); stopBtn.classList.remove('d-none');
            stateEl.textContent = 'Kamera aktif'; stateEl.className = 'badge bg-success-subtle text-success';
        } catch (e) {
            showError('Kamera tidak dapat dibuka: ' + e + '. Pastikan izin kamera aktif dan akses memakai HTTPS.');
            stateEl.textContent = 'Kamera gagal'; stateEl.className = 'badge bg-danger-subtle text-danger';
        }
    }

    async function stopCamera() {
        if (scanner && running) { try { await scanner.stop(); await scanner.clear(); } catch (e) {} }
        running = false;
        startBtn.classList.remove('d-none'); stopBtn.classList.add('d-none');
        stateEl.textContent = 'Kamera belum aktif'; stateEl.className = 'badge bg-info-subtle text-info';
    }

    async function onScan(decodedText) {
        if (!running) return;
        await stopCamera();
        lookup(decodedText);
    }

    async function lookup(code) {
        clearError();
        try {
            const response = await fetch(urls.lookup + '?code=' + encodeURIComponent(code), { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.data?.asset) { throw new Error(data.message || data.error || 'Aset tidak ditemukan.'); }
            showResult(data.data.asset);
        } catch (e) {
            showError(e.message || 'Aset tidak ditemukan.');
            emptyCard.style.display = 'block'; resultCard.classList.remove('show');
        }
    }

    function showResult(asset) {
        const id = asset.id;
        document.getElementById('resultName').textContent = asset.asset_name || '-';
        document.getElementById('resultCode').textContent = asset.asset_code || '-';
        document.getElementById('resultRoom').textContent = asset.room_name || '-';
        document.getElementById('resultSchool').textContent = asset.school_name || '-';
        document.getElementById('resultCondition').textContent = (asset.condition || '-').replace(/_/g, ' ');
        document.getElementById('resultStatus').textContent = (asset.status || '-').replace(/_/g, ' ');
        document.getElementById('passportLink').href = urls.passport.replace('__ID__', encodeURIComponent(id));
        document.getElementById('reportLink').href = urls.report + '?asset_id=' + encodeURIComponent(id);
        document.getElementById('repairLink').href = urls.repair + '?search=' + encodeURIComponent(asset.asset_code || '');
        document.getElementById('scheduleLink').href = urls.schedule + '?asset_id=' + encodeURIComponent(id);
        if (asset.photo) { document.getElementById('resultPhoto').src = asset.photo; document.getElementById('resultPhotoWrap').classList.remove('d-none'); } else { document.getElementById('resultPhotoWrap').classList.add('d-none'); }
        emptyCard.style.display = 'none'; resultCard.classList.add('show');
    }

    startBtn.addEventListener('click', startCamera);
    stopBtn.addEventListener('click', stopCamera);
    document.getElementById('manualForm').addEventListener('submit', function (event) { event.preventDefault(); const code = document.getElementById('manualCode').value.trim(); if (code) lookup(code); });
    document.getElementById('scanAgain').addEventListener('click', function () { resultCard.classList.remove('show'); emptyCard.style.display = 'block'; startCamera(); });
    window.addEventListener('beforeunload', function () { if (scanner && running) scanner.stop().catch(function () {}); });
})();
</script>
@endpush
