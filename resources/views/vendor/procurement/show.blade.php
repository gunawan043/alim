@extends('vendor.layouts.app')
@section('title', 'Detail Pengadaan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>{{ $request->request_number }}</h4>
    <a href="{{ route('vendor.procurement.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="text-muted small">Tujuan</label>
                <p class="mb-1">{{ $request->purpose }}</p>
            </div>
            <div class="col-md-3">
                <label class="text-muted small">Urgensi</label>
                <p class="mb-1"><span class="badge bg-{{ match($request->urgency) { 'mendesak' => 'danger', 'tinggi' => 'warning', 'rendah' => 'info', default => 'secondary' } }}">{{ ucfirst($request->urgency) }}</span></p>
            </div>
            <div class="col-md-3">
                <label class="text-muted small">Status</label>
                <p class="mb-1"><span class="badge bg-{{ match($request->status) {
                    'draft' => 'secondary', 'pending' => 'warning', 'approved' => 'info',
                    'ordered' => 'primary', 'delivered' => 'success',
                    'completed' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary',
                    default => 'secondary'
                } }}">{{ ucfirst($request->status) }}</span></p>
            </div>
            <div class="col-md-4">
                <label class="text-muted small">Estimasi Anggaran</label>
                <p class="mb-1 fw-bold">Rp {{ number_format($request->total_estimated_budget ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="col-md-4">
                <label class="text-muted small">Tanggal Pengiriman</label>
                <p class="mb-1">{{ $request->delivery_date?->format('d M Y') ?? '-' }}</p>
            </div>
            <div class="col-md-4">
                <label class="text-muted small">Metode Pengadaan</label>
                <p class="mb-1">{{ Str::replace('_', ' ', $request->procurement_method ?? '-') }}</p>
            </div>
            @if ($request->notes)
            <div class="col-12">
                <label class="text-muted small">Catatan</label>
                <p class="mb-1">{{ $request->notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

@if ($request->items->count() > 0)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white"><h6 class="mb-0">Item</h6></div>
    <table class="table table-sm mb-0">
        <thead class="table-light">
            <tr><th>Item</th><th>Qty</th><th>Satuan</th><th>Harga/Unit</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            @foreach ($request->items as $item)
            <tr>
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->unit }}</td>
                <td>Rp {{ number_format($item->estimated_unit_price ?? 0, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->subtotal ?? 0, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Update status if ordered --}}
@if (in_array($request->status, ['ordered', 'delivered']))
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="mb-3">Update Status Pengiriman</h6>
        <form method="POST" action="{{ route('vendor.procurement.status.update', $request->id) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Aksi</label>
                    <select name="action" class="form-select">
                        <option value="update_delivery">Update Delivery</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor Tracking</label>
                    <input type="text" name="tracking_number" class="form-control" placeholder="Opsional">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
