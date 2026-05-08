@extends('layouts.master')
@section('title') Detail Obat @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId]) }}">Inventori Obat</a> @endslot
        @slot('title') Detail Obat @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detail Obat</h5>
                        <div>
                            <a href="{{ route('user.uks.medicine-inventory.edit', ['userId' => $userId, 'uuid' => $inventory->id]) }}"
                               class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i> Edit</a>
                            <form method="POST" action="{{ route('user.uks.medicine-inventory.destroy', ['userId' => $userId, 'uuid' => $inventory->id]) }}"
                                  class="d-inline" >
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 text-center">
                            <div class="fs-1"><i class="ri-flask-line text-primary"></i></div>
                        </div>
                        <div class="col-md-9">
                            <h4>{{ $inventory->medicine_name }}</h4>
                            @if($inventory->medicine_code)
                                <span class="badge bg-light text-dark">{{ $inventory->medicine_code }}</span>
                            @endif
                            <span class="badge bg-{{ $inventory->category === 'obat_dalam' ? 'info' : 'secondary' }}">
                                {{ $inventory->category_text }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <div class="text-muted small">Stok</div>
                                <div class="fs-3 fw-bold {{ $inventory->is_low_stock ? 'text-danger' : 'text-success' }}">
                                    {{ $inventory->current_stock }}
                                </div>
                                <div class="small text-muted">{{ ucfirst($inventory->unit) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <div class="text-muted small">Minimal Stok</div>
                                <div class="fs-3 fw-bold text-muted">{{ $inventory->min_stock_alert ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <div class="text-muted small">Kadaluarsa</div>
                                <div class="fs-6 fw-semibold {{ $inventory->is_expired ? 'text-danger' : 'text-success' }}">
                                    {{ $inventory->expiry_date?->format('d/m/Y') ?? '-' }}
                                </div>
                                @if($inventory->is_expired)
                                    <span class="badge bg-danger mt-1">Kedaluwarsa</span>
                                @elseif($inventory->is_expiring_soon)
                                    <span class="badge bg-warning mt-1">Akan Kadaluarsa</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-semibold text-muted" style="width:180px">Nama Generik</td><td>{{ $inventory->generic_name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Kategori</td><td>{{ $inventory->category_text }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Satuan</td><td>{{ ucfirst($inventory->unit) }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Lokasi Penyimpanan</td><td>{{ $inventory->storage_location ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Supplier</td><td>{{ $inventory->supplier ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tanggal Pembelian</td><td>{{ $inventory->purchase_date?->format('d/m/Y') ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Harga per Unit</td><td>{{ $inventory->unit_price ? 'Rp ' . number_format($inventory->unit_price, 0, ',', '.') : '-' }}</td></tr>
                            @if($inventory->dosage_info)
                                <tr><td class="fw-semibold text-muted">Info Dosis</td><td>{{ $inventory->dosage_info }}</td></tr>
                            @endif
                            @if($inventory->notes)
                                <tr><td class="fw-semibold text-muted">Catatan</td><td>{{ $inventory->notes }}</td></tr>
                            @endif
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection