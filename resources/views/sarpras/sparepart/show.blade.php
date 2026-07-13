@extends('layouts.sarpras')

@section('title', 'Detail Sparepart')

@section('content')
<div class="card mb-3">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ $sparepart->name }}</h5>
        <div>
            <a href="{{ route('sarpras.sparepart.edit', $sparepart) }}" class="btn btn-light btn-sm">Edit</a>
            <a href="{{ route('sarpras.sparepart.index') }}" class="btn btn-outline-light btn-sm">Kembali</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h6>Info</h6>
                <table class="table table-sm table-borderless">
                    <tr><th>Kode:</th><td>{{ $sparepart->sparepart_code }}</td></tr>
                    <tr><th>Kategori:</th><td>{{ $sparepart->category->name ?? '-' }}</td></tr>
                    <tr><th>Manufaktur:</th><td>{{ $sparepart->manufacturer }}</td></tr>
                    <tr><th>Model:</th><td>{{ $sparepart->manufacturer_model_no }}</td></tr>
                    <tr><th>Masa Pakai:</th><td>{{ $sparepart->expected_lifespan ?? '-' }} bln</td></tr>
                </table>
            </div>
            <div class="col-md-4">
                <h6>Stok</h6>
                <table class="table table-sm table-borderless">
                    <tr><th>Qty On Hand:</th><td>{{ $sparepart->qty_on_hand }}</td></tr>
                    <tr><th>Qty Reserved:</th><td>{{ $sparepart->qty_reserved }}</td></tr>
                    <tr><th>Qty Available:</th><td><strong>{{ $sparepart->qty_available }}</strong></td></tr>
                    <tr><th>Min Level:</th><td>{{ $sparepart->min_stock_level }}</td></tr>
                    <tr><th>Reorder:</th><td>{{ $sparepart->reorder_level }}</td></tr>
                    <tr><th>Unit Price:</th><td>Rp {{ number_format($sparepart->unit_price, 0, ',', '.') }}</td></tr>
                </table>
            </div>
            <div class="col-md-4">
                <h6>Lokasi</h6>
                <table class="table table-sm table-borderless">
                    <tr><th>Gudang:</th><td>{{ $sparepart->warehouse_location }}</td></tr>
                    <tr><th>Kota:</th><td>{{ $sparepart->location_city }}</td></tr>
                    <tr><th>Vendor:</th><td>{{ $sparepart->vendor_supplier }}</td></tr>
                    <tr><th>Barcode:</th><td><code>{{ $sparepart->barcode }}</code></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<h5 class="mt-4">Pergerakan (6 bulan terakhir)</h5>
<table class="table table-sm table-striped">
    <thead>
        <tr><th>Tanggal</th><th>Tipe</th><th>Dokumen</th><th>Pengaruh Stok</th></tr>
    </thead>
    <tbody>
        @forelse ($sparepart->movements as $mv)
            <tr>
                <td>{{ $mv->created_at->format('d M Y') }}</td>
                <td>{{ str_replace('_', ' ', $mv->movement_type) }}</td>
                <td>{{ $mv->reference_document }}</td>
                <td class="{{ $mv->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $mv->quantity >= 0 ? '+' : '' }}{{ $mv->quantity }}
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted">Belum ada pergerakan.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection