@extends('layouts.master')
@section('title') Lookup Aset @endsection

@section('css')
<style>
.search-box-large { font-size: 18px; padding: 12px 16px; }
.result-card { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.result-card .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.qr.index') }}">QR Code</a> @endslot
    @slot('title') Lookup @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-search-line me-1"></i> Lookup Aset via QR Code</h5></div>
            <div class="card-body">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-light"><i class="ri-qr-scan-line"></i></span>
                    <input type="text" id="lookup-code" class="form-control search-box-large"
                        placeholder="Scan atau ketik kode QR / ID aset..." autofocus>
                    <button type="button" class="btn btn-primary" onclick="doLookup()">
                        <i class="ri-search-line me-1"></i> Cari
                    </button>
                </div>
                <small class="text-muted">Contoh: ID numerik aset atau URL lengkap dari QR code.</small>

                <div id="lookup-result" class="mt-4 d-none">
                    <div class="result-card">
                        <div class="card-header p-3">
                            <h5 class="mb-0" id="result-name">-</h5>
                            <small id="result-code">-</small>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-borderless mb-0">
                                <tr><td class="text-muted small" style="width:140px">Merek / Model</td><td id="result-brand">-</td></tr>
                                <tr><td class="text-muted small">Ruang</td><td id="result-room">-</td></tr>
                                <tr><td class="text-muted small">Satuan Pendidikan</td><td id="result-school">-</td></tr>
                                <tr><td class="text-muted small">Kondisi</td><td id="result-condition">-</td></tr>
                                <tr><td class="text-muted small">Status</td><td id="result-status">-</td></tr>
                            </table>
                            @if($asset ?? false)
                            @endif
                        </div>
                        <div class="card-footer bg-transparent">
                            <a id="result-link" href="#" class="btn btn-primary btn-sm">
                                <i class="ri-eye-line me-1"></i> Lihat Detail Aset
                            </a>
                            <a href="{{ route('sarpras.qr.scanner') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ri-scan-line me-1"></i> Scan QR
                            </a>
                        </div>
                    </div>
                </div>

                <div id="lookup-error" class="alert alert-danger mt-4 d-none">
                    <i class="ri-error-warning-line me-1"></i>
                    <span id="error-text">Aset tidak ditemukan.</span>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Aset Terbaru dengan QR</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Kode</th><th>Nama</th><th>Ruang</th><th>Kondisi</th><th></th></tr></thead>
                    <tbody>
                        @foreach(\App\Models\Asset::where('is_active', true)->whereNotNull('qr_generated_at')->latest()->limit(10)->get() as $a)
                        <tr>
                            <td><code>{{ $a->asset_code ?? '-' }}</code></td>
                            <td>{{ Str::limit($a->asset_name, 30) }}</td>
                            <td>{{ $a->room?->room_name ?? '-' }}</td>
                            <td>{{ ucfirst(str_replace('_',' ', $a->condition)) }}</td>
                            <td>
                                <button class="btn btn-sm btn-link p-0" onclick="lookupByCode('{{ $a->id }}')">
                                    <i class="ri-external-link-line"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
function doLookup() {
    const code = document.getElementById('lookup-code').value.trim();
    if (!code) return;
    performLookup(code);
}

function lookupByCode(id) {
    document.getElementById('lookup-code').value = id;
    performLookup(id);
}

function performLookup(code) {
    document.getElementById('lookup-result').classList.add('d-none');
    document.getElementById('lookup-error').classList.add('d-none');

    fetch('/sarpras/qr/lookup?code=' + encodeURIComponent(code))
        .then(r => r.json())
        .then(data => {
            if (data.success && data.asset) {
                const a = data.asset;
                document.getElementById('result-name').textContent = a.asset_name;
                document.getElementById('result-code').textContent = 'Kode: ' + (a.asset_code || '-');
                document.getElementById('result-brand').textContent = a.brand && a.model ? a.brand + ' ' + a.model : '-';
                document.getElementById('result-room').textContent = a.room_name || '-';
                document.getElementById('result-school').textContent = a.school_name || '-';

                const cond = (a.condition || '-').replace('_',' ');
                document.getElementById('result-condition').innerHTML = '<span class="badge bg-warning-subtle text-warning">' + cond.charAt(0).toUpperCase() + cond.slice(1) + '</span>';
                document.getElementById('result-status').innerHTML = '<span class="badge bg-success-subtle text-success">' + (a.status || '-').replace('_',' ') + '</span>';

                document.getElementById('result-link').href = '/sarpras/aset/' + a.id;
                document.getElementById('lookup-result').classList.remove('d-none');
            } else {
                document.getElementById('error-text').textContent = data.error || 'Aset tidak ditemukan.';
                document.getElementById('lookup-error').classList.remove('d-none');
            }
        })
        .catch(() => {
            document.getElementById('error-text').textContent = 'Terjadi kesalahan koneksi.';
            document.getElementById('lookup-error').classList.remove('d-none');
        });
}

document.getElementById('lookup-code').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); doLookup(); }
});
</script>
@endsection