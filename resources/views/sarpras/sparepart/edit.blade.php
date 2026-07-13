@extends('layouts.sarpras')

@section('title', 'Edit Sparepart')

@section('content')
<div class="card">
    <div class="card-header bg-gradient-primary text-white">
        <h5 class="mb-0">Edit Sparepart — {{ $sparepart->name }}</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('sarpras.sparepart.update', $sparepart) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Part Number</label>
                    <input type="text" name="part_number" class="form-control" value="{{ old('part_number', $sparepart->part_number) }}" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nama Sparepart <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $sparepart->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $sparepart->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Satuan <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select" required>
                        @foreach ($units as $u)
                            <option value="{{ $u->id }}" {{ old('unit_id', $sparepart->unit_id) == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->symbol }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control" value="{{ old('brand', $sparepart->brand) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Manufacturer</label>
                    <input type="text" name="manufacturer" class="form-control" value="{{ old('manufacturer', $sparepart->manufacturer) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Primary Vendor</label>
                    <select name="primary_vendor_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}" {{ old('primary_vendor_id', $sparepart->primary_vendor_id) == $v->id ? 'selected' : '' }}>
                                {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gudang <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-select" required>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" {{ old('warehouse_id', $sparepart->warehouse_id) == $w->id ? 'selected' : '' }}>
                                {{ $w->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Barcode</label>
                    <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $sparepart->barcode) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Harga Satuan</label>
                    <input type="number" name="unit_price" class="form-control" value="{{ old('unit_price', $sparepart->unit_price) }}" step="0.01" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Lead Time (Hari)</label>
                    <input type="number" name="lead_time_days" class="form-control" value="{{ old('lead_time_days', $sparepart->lead_time_days) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Min. Stok</label>
                    <input type="number" name="min_stock" class="form-control" value="{{ old('min_stock', $sparepart->min_stock) }}" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Max. Stok</label>
                    <input type="number" name="max_stock" class="form-control" value="{{ old('max_stock', $sparepart->max_stock) }}" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Titik Reorder</label>
                    <input type="number" name="reorder_point" class="form-control" value="{{ old('reorder_point', $sparepart->reorder_point) }}" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jml Reorder</label>
                    <input type="number" name="reorder_quantity" class="form-control" value="{{ old('reorder_quantity', $sparepart->reorder_quantity) }}" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $sparepart->description) }}</textarea>
                </div>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="{{ route('sarpras.sparepart.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection