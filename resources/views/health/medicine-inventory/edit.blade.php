@extends('layouts.master')
@section('title') Edit Obat @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId]) }}">Inventori Obat</a> @endslot
        @slot('title') Edit Obat @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.uks.medicine-inventory.update', ['userId' => $userId, 'uuid' => $inventory->id]) }}">
        @csrf @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light"><h5 class="mb-0">Edit Inventori Obat</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Obat <span class="text-danger">*</span></label>
                                    <input type="text" name="medicine_name" class="form-control @error('medicine_name') is-invalid @enderror" value="{{ old('medicine_name', $inventory->medicine_name) }}" required>
                                    @error('medicine_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kode Obat</label>
                                    <input type="text" name="medicine_code" class="form-control" value="{{ old('medicine_code', $inventory->medicine_code) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <select name="category" class="form-control @error('category') is-invalid @enderror" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach(['obat_dalam','obat_luar','vitamin','antiseptik','alat_medis'] as $cat)
                                            <option value="{{ $cat }}" {{ old('category', $inventory->category) == $cat ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$cat)) }}</option>
                                        @endforeach
                                    </select>
                                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Satuan <span class="text-danger">*</span></label>
                                    <select name="unit" class="form-control @error('unit') is-invalid @enderror" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach(['tablet','kaplet','kapsul','sirup','botol','tube','lembar','unit','box'] as $u)
                                            <option value="{{ $u }}" {{ old('unit', $inventory->unit) == $u ? 'selected' : '' }}>{{ ucfirst($u) }}</option>
                                        @endforeach
                                    </select>
                                    @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Nama Generik</label>
                                    <input type="text" name="generic_name" class="form-control" value="{{ old('generic_name', $inventory->generic_name) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stok Saat Ini</label>
                                    <input type="number" name="current_stock" class="form-control" value="{{ old('current_stock', $inventory->current_stock) }}" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Minimal Stok Alert</label>
                                    <input type="number" name="min_stock_alert" class="form-control" value="{{ old('min_stock_alert', $inventory->min_stock_alert) }}" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Kadaluarsa</label>
                                    <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date', $inventory->expiry_date?->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Lokasi Penyimpanan</label>
                                    <input type="text" name="storage_location" class="form-control" value="{{ old('storage_location', $inventory->storage_location) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Supplier</label>
                                    <input type="text" name="supplier" class="form-control" value="{{ old('supplier', $inventory->supplier) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Pembelian</label>
                                    <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $inventory->purchase_date?->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Harga per Unit (Rp)</label>
                                    <input type="number" name="unit_price" class="form-control" value="{{ old('unit_price', $inventory->unit_price) }}" min="0">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Info Dosis</label>
                                    <input type="text" name="dosage_info" class="form-control" value="{{ old('dosage_info', $inventory->dosage_info) }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $inventory->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                            <a href="{{ route('user.uks.medicine-inventory.show', ['userId' => $userId, 'uuid' => $inventory->id]) }}" class="btn btn-secondary"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection