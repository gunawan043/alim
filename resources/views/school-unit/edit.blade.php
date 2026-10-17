{{-- School Unit: Edit Satuan Pendidikan --}}
@extends('layouts.master')
@section('title') Edit {{ $school->name }} @endsection
@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container--default .select2-selection--single{height:36px}
.form-section{margin-bottom:2rem}
.form-section-title{font-size:1rem;font-weight:600;padding-bottom:.5rem;border-bottom:2px solid #e2e8f0;margin-bottom:1rem}
</style>
@endsection

@section('content')
@php
    $userId = $userId ?? auth()->id();
    $action = route('user.schools.satuan-kerja.edit', ['userId' => $userId, 'workUnitId' => $workUnit->id, 'schoolId' => $school->id]);
@endphp

@component('components.breadcrumb')
    @slot('li_1') Satuan Pendidikan @endslot
    @slot('li_2') {{ $workUnit->name }} @endslot
    @slot('li_3') Edit @endslot
    @slot('title') Edit {{ $school->name }} @endslot
@endcomponent

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light-subtle border-bottom-dashed d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ri-edit-line text-primary me-1"></i>Edit {{ $school->name }}</h5>
                <a href="{{ route('user.schools.satuan-kerja.show', ['userId' => $userId, 'workUnitId' => $workUnit->id, 'schoolId' => $school->id]) }}" class="btn btn-light btn-sm">
                    <i class="ri-arrow-left-line me-1"></i>Kembali
                </a>
            </div>
            <div class="card-body">
                <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Identitas --}}
                    <div class="form-section">
                        <div class="form-section-title"><i class="ri-shield-star-line me-1 text-primary"></i>Identitas Sekolah</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Sekolah <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $school->name) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">NPSN</label>
                                <input type="text" name="npsn" class="form-control form-control-sm" value="{{ old('npsn', $school->npsn) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">NSS</label>
                                <input type="text" name="nss" class="form-control form-control-sm" value="{{ old('nss', $school->nss) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenjang <span class="text-danger">*</span></label>
                                <select name="school_level" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Jenjang --</option>
                                    <option value="sd"    {{ $school->school_level === 'sd'    ? 'selected' : '' }}>SD</option>
                                    <option value="smp"   {{ $school->school_level === 'smp'  ? 'selected' : '' }}>SMP</option>
                                    <option value="sma"   {{ $school->school_level === 'sma'  ? 'selected' : '' }}>SMA</option>
                                    <option value="smk"   {{ $school->school_level === 'smk'  ? 'selected' : '' }}>SMK</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="school_status" class="form-select form-select-sm">
                                    <option value="">-- Pilih --</option>
                                    <option value="negeri" {{ $school->school_status === 'negeri' ? 'selected' : '' }}>Negeri</option>
                                    <option value="swasta" {{ $school->school_status === 'swasta' ? 'selected' : '' }}>Swasta</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Akreditasi</label>
                                <select name="accreditation" class="form-select form-select-sm">
                                    <option value="">-- Pilih --</option>
                                    <option value="A" {{ $school->accreditation === 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ $school->accreditation === 'B' ? 'selected' : '' }}>B</option>
                                    <option value="C" {{ $school->accreditation === 'C' ? 'selected' : '' }}>C</option>
                                    <option value="D" {{ $school->accreditation === 'D' ? 'selected' : '' }}>D</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahun Akreditasi</label>
                                <input type="number" name="accreditation_year" class="form-control form-control-sm" value="{{ old('accreditation_year', $school->accreditation_year) }}" min="2000" max="{{ date('Y') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jam Operasional</label>
                                <select name="operational_hours" class="form-select form-select-sm">
                                    <option value="">-- Pilih --</option>
                                    <option value="pagi" {{ $school->operational_hours === 'pagi' ? 'selected' : '' }}>Pagi</option>
                                    <option value="siang" {{ $school->operational_hours === 'siang' ? 'selected' : '' }}>Siang</option>
                                    <option value="full_day" {{ $school->operational_hours === 'full_day' ? 'selected' : '' }}>Full Day</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Luas Tanah (m2)</label>
                                <input type="number" step="0.01" name="land_area" class="form-control form-control-sm" value="{{ old('land_area', $school->land_area) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Luas Bangunan (m2)</label>
                                <input type="number" step="0.01" name="building_area" class="form-control form-control-sm" value="{{ old('building_area', $school->building_area) }}">
                            </div>
                        </div>
                    </div>

                    {{-- Lokasi --}}
                    <div class="form-section">
                        <div class="form-section-title"><i class="ri-map-pin-line me-1 text-danger"></i>Lokasi</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Provinsi</label>
                                <select name="province_code" class="form-select form-select-sm js-province" data-selected="{{ $school->province_code }}">
                                    <option value="">-- Pilih Provinsi --</option>
                                    @foreach($provinces as $prov)
                                        <option value="{{ $prov->code }}" {{ $school->province_code == $prov->code ? 'selected' : '' }}>{{ $prov->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kota / Kab</label>
                                <input type="text" class="form-control form-control-sm" value="{{ $school->city?->name ?? '' }}" readonly placeholder="Otomatis dari kode wilayah">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kecamatan</label>
                                <input type="text" class="form-control form-control-sm" value="{{ $school->district?->name ?? '' }}" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control form-control-sm" rows="2">{{ old('address', $school->address) }}</textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" name="postal_code" class="form-control form-control-sm" value="{{ old('postal_code', $school->postal_code) }}">
                            </div>
                        </div>
                    </div>

                    {{-- Kontak --}}
                    <div class="form-section">
                        <div class="form-section-title"><i class="ri-phone-line me-1 text-warning"></i>Kontak</div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" class="form-control form-control-sm" value="{{ old('phone', $school->phone) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email', $school->email) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control form-control-sm" value="{{ old('website', $school->website) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select form-select-sm">
                                    <option value="1" {{ $school->is_active ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ !$school->is_active ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Kepala Sekolah --}}
                    <div class="form-section">
                        <div class="form-section-title"><i class="ri-user-star-line me-1 text-purple"></i>Kepala Sekolah</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Kepsek</label>
                                <input type="text" name="principal_name" class="form-control form-control-sm" value="{{ old('principal_name', $school->principal_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIP Kepsek</label>
                                <input type="text" name="principal_nip" class="form-control form-control-sm" value="{{ old('principal_nip', $school->principal_nip) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kepala Sekolah (User)</label>
                                <select name="principal_user_id" class="form-select form-select-sm">
                                    <option value="">-- Pilih User --</option>
                                    @foreach($principals as $p)
                                        <option value="{{ $p->id }}" {{ $school->principal_user_id == $p->id ? 'selected' : '' }}>
                                            {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Bank & NPWP --}}
                    <div class="form-section">
                        <div class="form-section-title"><i class="ri-bank-line me-1 text-success"></i>Informasi Bank & NPWP</div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Nama Bank</label>
                                <input type="text" name="bank_name" class="form-control form-control-sm" value="{{ old('bank_name', $school->bank_name) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cabang</label>
                                <input type="text" name="bank_cabang" class="form-control form-control-sm" value="{{ old('bank_cabang', $school->bank_cabang) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">No. Rekening</label>
                                <input type="text" name="bank_rekening" class="form-control form-control-sm" value="{{ old('bank_rekening', $school->bank_rekening) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Atas Nama</label>
                                <input type="text" name="bank_an" class="form-control form-control-sm" value="{{ old('bank_an', $school->bank_an) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">NPWP</label>
                                <input type="text" name="npwp" class="form-control form-control-sm" value="{{ old('npwp', $school->npwp) }}">
                            </div>
                        </div>
                    </div>

                    {{-- Tombol --}}
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ri-save-line me-1"></i>Simpan Perubahan</button>
                        <a href="{{ route('user.schools.satuan-kerja.show', ['userId' => $userId, 'workUnitId' => $workUnit->id, 'schoolId' => $school->id]) }}" class="btn btn-light btn-sm"><i class="ri-close-line me-1"></i>Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function(){
    $('.js-province').select2({
        minimumResultsForSearch: 10,
        width: '100%'
    });
});
</script>
@endpush
@endsection
