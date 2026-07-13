@extends('layouts.sarpras')

@section('title', 'Edit Vendor — ' . $vendor->name)

@section('content')
<div class="card">
    <div class="card-header bg-gradient-warning text-dark">
        <h5 class="mb-0">Edit Vendor</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('sarpras.vendor.update', $vendor) }}">
            @csrf
            @method('PUT')

            <h6 class="border-bottom pb-2 mb-3">Informasi Dasar</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Vendor Code</label>
                    <input type="text" name="vendor_code" class="form-control" value="{{ old('vendor_code', $vendor->vendor_code) }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nama Vendor <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $vendor->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Legal Name</label>
                    <input type="text" name="legal_name" class="form-control" value="{{ old('legal_name', $vendor->legal_name) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" class="form-control" value="{{ old('npwp', $vendor->npwp) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $vendor->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Vendor</label>
                    <select name="vendor_type" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="supplier" {{ old('vendor_type', $vendor->vendor_type) === 'supplier' ? 'selected' : '' }}>Supplier</option>
                        <option value="service_provider" {{ old('vendor_type', $vendor->vendor_type) === 'service_provider' ? 'selected' : '' }}>Service Provider</option>
                        <option value="manufacturer" {{ old('vendor_type', $vendor->vendor_type) === 'manufacturer' ? 'selected' : '' }}>Manufacturer</option>
                        <option value="distributor" {{ old('vendor_type', $vendor->vendor_type) === 'distributor' ? 'selected' : '' }}>Distributor</option>
                        <option value="contractor" {{ old('vendor_type', $vendor->vendor_type) === 'contractor' ? 'selected' : '' }}>Contractor</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $vendor->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="prospective" {{ old('status', $vendor->status) === 'prospective' ? 'selected' : '' }}>Prospect</option>
                        <option value="inactive" {{ old('status', $vendor->status) === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                        <option value="blacklist" {{ old('status', $vendor->status) === 'blacklist' ? 'selected' : '' }}>Blacklist</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Risk Class</label>
                    <select name="risk_classification" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="low" {{ old('risk_classification', $vendor->risk_classification) === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('risk_classification', $vendor->risk_classification) === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('risk_classification', $vendor->risk_classification) === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('risk_classification', $vendor->risk_classification) === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>
            </div>

            <h6 class="border-bottom pb-2 mb-3">Kontak</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $vendor->phone) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $vendor->email) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control" value="{{ old('website', $vendor->website) }}">
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="{{ route('sarpras.vendor.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-warning">Update Vendor</button>
            </div>
        </form>
    </div>
</div>
@endsection