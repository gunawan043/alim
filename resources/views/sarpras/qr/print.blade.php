@extends('layouts.master')
@section('title') Print QR Label @endsection

@section('css')
<style>
@media print {
    body { background: white; }
    .no-print { display: none !important; }
    .qr-label { page-break-inside: avoid; border: 1px solid #000; }
}
.qr-label {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
    background: white;
    border-radius: 4px;
}
.qr-label img { max-width: 100px; margin: 0 auto; display: block; }
.qr-label .asset-name { font-size: 11px; font-weight: 600; margin-top: 4px; word-break: break-all; }
.qr-label .asset-code { font-size: 9px; color: #666; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.qr.index') }}">QR Code</a> @endslot
    @slot('title') Print Label @endslot
@endcomponent

<div class="row no-print mb-3">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">{{ $assets->count() }} Label Siap Cetak</h5>
            <small class="text-muted">Pastikan printer label sudah siap dan kertas label sudah terpasang.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sarpras.qr.pdf') }}" class="btn btn-outline-danger btn-sm">
                <i class="ri-file-pdf-line me-1"></i> Download PDF
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="ri-printer-line me-1"></i> Cetak Sekarang
            </button>
        </div>
    </div>
</div>

{{-- FILTER --}}
<div class="row no-print mb-3">
    <div class="col-md-4">
        <form method="GET" class="d-flex gap-2">
            <select name="room_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Ruang</option>
                @foreach(\App\Models\AssetRoom::where('is_active', true)->orderBy('room_name')->get() as $r)
                    <option value="{{ $r->id }}" {{ request('room_id') == $r->id ? 'selected' : '' }}>{{ $r->room_name }}</option>
                @endforeach
            </select>
            <a href="{{ route('sarpras.qr.print') }}" class="btn btn-light btn-sm"><i class="ri-refresh-line"></i></a>
        </form>
    </div>
</div>

{{-- PRINT AREA --}}
<div class="row g-2" id="print-area">
    @forelse($assets as $asset)
    <div class="col-3">
        <div class="qr-label">
            <img src="{{ $qrData[$asset->id] ?? '' }}" alt="QR {{ $asset->asset_code }}">
            <div class="asset-name">{{ $asset->asset_name }}</div>
            <div class="asset-code">{{ $asset->asset_code ?? '-' }}</div>
            <div class="asset-code">{{ $asset->room?->room_name ?? '-' }}</div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <i class="ri-qr-code-line fs-1 text-muted"></i>
        <h5 class="text-muted mt-2">Tidak ada aset dengan QR code</h5>
        <a href="{{ route('sarpras.qr.generate-all') }}" class="btn btn-primary btn-sm mt-2" onclick="return confirm('Generate QR untuk semua aset?')">
            <i class="ri-qr-code-line me-1"></i> Generate QR Dahulu
        </a>
    </div>
    @endforelse
</div>

<div class="text-center no-print mt-4">
    <a href="{{ route('sarpras.qr.index') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
</div>
@endsection