@extends('layouts.master')
@section('title') Terima Barang Pengadaan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pengadaan.index') }}">Pengadaan</a> @endslot
    @slot('li_3') <a href="{{ route('sarpras.pengadaan.show', ['id' => $procurement->id]) }}">{{ $procurement->request_number }}</a> @endslot
    @slot('title') Terima Barang @endslot
@endcomponent

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
    {{-- RINGKASAN REQUEST --}}
    <div class="col-12 mb-3">
        @php
            $statusConfig = [
                'approved' => ['info', 'Disetujui — Menunggu Penyerahan'],
                'ordered'  => ['warning', 'Sudah Dipesan'],
            ];
            $sc = $statusConfig[$procurement->status] ?? ['secondary', $procurement->status];
        @endphp
        <div class="alert alert-{{ $sc[0] }} d-flex align-items-center gap-2 py-2">
            <i class="ri-truck-line fs-5"></i>
            <strong>Status:</strong> {{ $sc[1] }}
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-truck-line me-1"></i> Form Penerimaan Barang</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.pengadaan.receive', ['id' => $procurement->id]) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Kirim / Terima <span class="text-danger">*</span></label>
                            <input type="date" name="delivery_date" class="form-control"
                                value="{{ old('delivery_date', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Penerima <span class="text-danger">*</span></label>
                            <select name="received_by" class="form-select" required>
                                <option value="">— Pilih —</option>
                                @foreach(\App\Models\User::where('is_active', true)->orderBy('name')->get() as $u)
                                    <option value="{{ $u->id }}" {{ old('received_by') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vendor / Penyedia</label>
                            <input type="text" name="vendor_name" class="form-control"
                                value="{{ old('vendor_name', $procurement->vendor_name) }}" placeholder="Nama vendor atau supplier">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total Biaya Aktual (Rp)</label>
                            <input type="number" name="total_actual_cost" class="form-control"
                                value="{{ old('total_actual_cost', $procurement->total_actual_cost) }}" min="0" step="100" placeholder="Isi jika sudah ada invoce">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. Purchase Order</label>
                            <input type="text" name="purchase_order_number" class="form-control"
                                value="{{ old('purchase_order_number', $procurement->purchase_order_number) }}" placeholder="Nomor PO">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal PO</label>
                            <input type="date" name="purchase_order_date" class="form-control"
                                value="{{ old('purchase_order_date', $procurement->purchase_order_date?->format('Y-m-d')) }}">
                        </div>
                    </div>

                    @if($procurement->items->isNotEmpty())
                    <h5 class="mt-4 mb-3">Detail Item Diterima</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th style="width:80px">Diminta</th>
                                    <th style="width:100px">Diterima</th>
                                    <th style="width:130px">Harga/unit Aktual</th>
                                    <th style="width:120px">Tgl Terima</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($procurement->items as $i => $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->item_name }}</strong>
                                        @if($item->specification)
                                            <br><small class="text-muted">{{ $item->specification }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $item->quantity }} {{ $item->unit ?? 'pcs' }}</td>
                                    <td>
                                        <input type="number" name="items[{{ $i }}][id]" value="{{ $item->id }}" hidden>
                                        <input type="number" name="items[{{ $i }}][actual_quantity_received]"
                                            class="form-control form-control-sm" min="0"
                                            value="{{ old("items.{$i}.actual_quantity_received", $item->actual_quantity_received ?? $item->quantity) }}">
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $i }}][actual_price_per_unit]"
                                            class="form-control form-control-sm" min="0" step="100"
                                            placeholder="{{ $item->estimated_price_per_unit ? number_format($item->estimated_price_per_unit, 0, ',', '.') : 'Rp' }}"
                                            value="{{ old("items.{$i}.actual_price_per_unit", $item->actual_price_per_unit) }}">
                                    </td>
                                    <td>
                                        <input type="date" name="items[{{ $i }}][received_date]"
                                            class="form-control form-control-sm"
                                            value="{{ old("items.{$i}.received_date", $item->received_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <div class="hstack gap-2 mt-4">
                        <button type="submit" class="btn btn-success"><i class="ri-check-line me-1"></i> Konfirmasi Barang Diterima</button>
                        <a href="{{ route('sarpras.pengadaan.show', ['id' => $procurement->id]) }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SIDEBAR --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Info Request</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted small">No. Request</td><td class="fw-medium"><code>{{ $procurement->request_number }}</code></td></tr>
                    <tr><td class="text-muted small">Tujuan</td><td>{{ $procurement->purpose }}</td></tr>
                    <tr><td class="text-muted small">Sumber Dana</td><td>{{ $procurement->budget_source ?? '-' }}</td></tr>
                    <tr><td class="text-muted small">Total Estimasi</td>
                        <td>{{ $procurement->total_estimated_budget ? 'Rp ' . number_format($procurement->total_estimated_budget, 0, ',', '.') : '-' }}</td>
                    </tr>
                    <tr><td class="text-muted small">Jumlah Item</td><td>{{ $procurement->items->count() }} item</td></tr>
                </table>
            </div>
        </div>

        @if($procurement->status === 'delivered')
        <div class="card mt-3 border-success">
            <div class="card-header bg-success-subtle"><h5 class="card-title mb-0 text-success"><i class="ri-arrow-left-right-line me-1"></i> Konversi ke Aset</h5></div>
            <div class="card-body">
                <p class="text-muted small">Barang sudah diterima. Lanjut konversi ke aset?</p>
                <a href="{{ route('sarpras.pengadaan.convert', ['id' => $procurement->id]) }}" class="btn btn-success w-100">
                    <i class="ri-arrow-left-right-line me-1"></i> Konversi ke Aset
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection