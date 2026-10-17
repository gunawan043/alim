@extends('layouts.sarpras')

@section('title', 'Tambah Vendor Baru')

@section('content')
<div class="card">
    <div class="card-header bg-gradient-primary text-white">
        <h5 class="mb-0">Tambah Vendor Baru</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('sarpras.vendor.store') }}">
            @csrf

            <h6 class="border-bottom pb-2 mb-3">Informasi Dasar</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Vendor Code</label>
                    <input type="text" name="vendor_code" class="form-control" value="{{ old('vendor_code') }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nama Vendor <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Legal Name</label>
                    <input type="text" name="legal_name" class="form-control" value="{{ old('legal_name') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" class="form-control" value="{{ old('npwp') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Vendor</label>
                    <select name="vendor_type" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="supplier" {{ old('vendor_type') === 'supplier' ? 'selected' : '' }}>Supplier</option>
                        <option value="service_provider" {{ old('vendor_type') === 'service_provider' ? 'selected' : '' }}>Service Provider</option>
                        <option value="manufacturer" {{ old('vendor_type') === 'manufacturer' ? 'selected' : '' }}>Manufacturer</option>
                        <option value="distributor" {{ old('vendor_type') === 'distributor' ? 'selected' : '' }}>Distributor</option>
                        <option value="contractor" {{ old('vendor_type') === 'contractor' ? 'selected' : '' }}>Contractor</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="prospective" {{ old('status') === 'prospective' ? 'selected' : '' }}>Prospect</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Est. Tahun</label>
                    <input type="number" name="established_year" class="form-control" value="{{ old('established_year') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jumlah Karyawan</label>
                    <input type="number" name="total_employees" class="form-control" value="{{ old('total_employees') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Credit Limit (Rp)</label>
                    <input type="number" name="credit_limit" class="form-control" value="{{ old('credit_limit') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Risk Class</label>
                    <select name="risk_classification" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="low" {{ old('risk_classification') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('risk_classification') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('risk_classification') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('risk_classification') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>
            </div>

            <h6 class="border-bottom pb-2 mb-3">Kontak</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control" value="{{ old('website') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Terms (hari)</label>
                    <input type="number" name="payment_term_days" class="form-control" value="{{ old('payment_term_days', 30) }}">
                </div>
            </div>

            <h6 class="border-bottom pb-2 mb-3">Alamat Utama</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Alamat</label>
                    <textarea name="addresses[0][street_address]" class="form-control" rows="2">{{ old('addresses.0.street_address') }}</textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kota</label>
                    <input type="text" name="addresses[0][city]" class="form-control" value="{{ old('addresses.0.city') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Provinsi</label>
                    <input type="text" name="addresses[0][province]" class="form-control" value="{{ old('addresses.0.province') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" name="addresses[0][postal_code]" class="form-control" value="{{ old('addresses.0.postal_code') }}">
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="{{ route('sarpras.vendor.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Vendor</button>
            </div>
        </form>
    </div>
</div>
@endsection