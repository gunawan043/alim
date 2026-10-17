@extends('layouts.sarpras')

@section('title', 'Tambah Sparepart')

@section('content')
<div class="card">
    <div class="card-header bg-gradient-primary text-white">
        <h5 class="mb-0">Tambah Sparepart Baru</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('sarpras.sparepart.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Part Number</label>
                    <input type="text" name="part_number" class="form-control" value="{{ old('part_number') }}" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nama Sparepart <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Satuan (Unit) <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        @foreach ($units as $u)
                            <option value="{{ $u->id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->symbol }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control" value="{{ old('brand') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Manufacturer</label>
                    <input type="text" name="manufacturer" class="form-control" value="{{ old('manufacturer') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Manufacturer Part</label>
                    <input type="text" name="manufacturer_part" class="form-control" value="{{ old('manufacturer_part') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Primary Vendor</label>
                    <select name="primary_vendor_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}" {{ old('primary_vendor_id') == $v->id ? 'selected' : '' }}>
                                {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gudang <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" {{ old('warehouse_id') == $w->id ? 'selected' : '' }}>
                                {{ $w->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lokasi Rak</label>
                    <select name="bin_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @if(request('warehouse_id'))
                            @foreach(\App\Models\WarehouseBin::where('warehouse_id', request('warehouse_id'))->get() as $bin)
                                <option value="{{ $bin->id }}" {{ old('bin_id') == $bin->id ? 'selected' : '' }}>
                                    {{ $bin->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Barcode</label>
                    <input type="text" name="barcode" class="form-control" value="{{ old('barcode') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Harga Satuan <span class="text-danger">*</span></label>
                    <input type="number" name="unit_price" class="form-control" value="{{ old('unit_price', 0) }}" step="0.01" min="0" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rata-rata Biaya</label>
                    <input type="number" name="average_cost" class="form-control" value="{{ old('average_cost', old('unit_price', 0)) }}" step="0.01" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Lead Time (Hari)</label>
                    <input type="number" name="lead_time_days" class="form-control" value="{{ old('lead_time_days', 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Min. Stok</label>
                    <input type="number" name="min_stock" class="form-control" value="{{ old('min_stock', 0) }}" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Max. Stok</label>
                    <input type="number" name="max_stock" class="form-control" value="{{ old('max_stock', 0) }}" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Titik Reorder</label>
                    <input type="number" name="reorder_point" class="form-control" value="{{ old('reorder_point', 0) }}" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jml Reorder</label>
                    <input type="number" name="reorder_quantity" class="form-control" value="{{ old('reorder_quantity', 0) }}" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Berat (Kg)</label>
                    <input type="number" name="weight_kg" class="form-control" value="{{ old('weight_kg', 0) }}" step="0.001">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Umur Pakai (Hari)</label>
                    <input type="number" name="lifetime_days" class="form-control" value="{{ old('lifetime_days', 0) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_hazardous" value="1" class="form-check-input" id="is_hazardous">
                        <label class="form-check-label" for="is_hazardous">Bahan Berbahaya</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_consumable" value="1" class="form-check-input" id="is_consumable" {{ old('is_consumable') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_consumable">Konsumabel</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" checked>
                        <label class="form-check-label" for="is_active">Aktif</label>
                    </div>
                </div>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="{{ route('sarpras.sparepart.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Sparepart</button>
            </div>
        </form>
    </div>
</div>
@endsection