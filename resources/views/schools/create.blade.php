@extends('layouts.master')
@section('title') Tambah Sekolah @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Daftar Sekolah @endslot
        @slot('title') Tambah Sekolah @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.schools.store', ['userId' => $userId]) }}" enctype="multipart/form-data">
        @csrf

        {{-- Tabs Navigation --}}
        <ul class="nav nav-tabs mb-3" id="schoolTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-identitas" data-bs-toggle="tab" data-bs-target="#identitas" type="button" role="tab">
                    <i class="ri-shield-star-line me-1"></i>Identitas Sekolah
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-alamat" data-bs-toggle="tab" data-bs-target="#alamat" type="button" role="tab">
                    <i class="ri-map-pin-line me-1"></i>Kontak & Alamat
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-kop" data-bs-toggle="tab" data-bs-target="#kop" type="button" role="tab">
                    <i class="ri-file-text-line me-1"></i>Kop Surat & Logo
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-legal" data-bs-toggle="tab" data-bs-target="#legal" type="button" role="tab">
                    <i class="ri-government-line me-1"></i>Legalitas & TTD
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-bank" data-bs-toggle="tab" data-bs-target="#bank" type="button" role="tab">
                    <i class="ri-bank-line me-1"></i>Informasi Bank
                </button>
            </li>
        </ul>

        <div class="tab-content" id="schoolTabContent">

            {{-- ── TAB 1: Identitas Sekolah ─────────────────────────── --}}
            <div class="tab-pane fade show active" id="identitas" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Identitas Sekolah</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Unit Akademik <span class="text-danger">*</span></label>
                                <select name="work_unit_id" id="work_unit_id" class="form-control" required>
                                    <option value="">— Pilih Unit Akademik —</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}" {{ old('work_unit_id') == $wu->id ? 'selected' : '' }}>{{ $wu->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Nama sekolah &amp; jenjang terisi otomatis.</small>
                                <input type="hidden" name="name" id="school_name" value="{{ old('name') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">NPSN</label>
                                <input type="text" name="npsn" class="form-control" value="{{ old('npsn') }}" maxlength="20">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">NSS</label>
                                <input type="text" name="nss" class="form-control" value="{{ old('nss') }}" maxlength="30">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenjang <span class="text-danger">*</span></label>
                                <select name="school_level" class="form-control" required>
                                    <option value="">Pilih Jenjang</option>
                                    <option value="sd"  {{ old('school_level') === 'sd'  ? 'selected' : '' }}>SD</option>
                                    <option value="smp" {{ old('school_level') === 'smp' ? 'selected' : '' }}>SMP</option>
                                    <option value="sma" {{ old('school_level') === 'sma' ? 'selected' : '' }}>SMA</option>
                                    <option value="smk" {{ old('school_level') === 'smk' ? 'selected' : '' }}>SMK</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="school_status" class="form-control" required>
                                    <option value="">Pilih Status</option>
                                    <option value="negeri"  {{ old('school_status') === 'negeri' ? 'selected' : '' }}>Negeri</option>
                                    <option value="swasta"  {{ old('school_status') === 'swasta' ? 'selected' : '' }}>Swasta</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Aktif</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                    <label class="form-check-label">Sekolah aktif</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Kepala Sekolah</label>
                                <select name="principal_user_id" id="principal_user_id" class="form-control">
                                    <option value="">— Pilih Kepala Sekolah —</option>
                                    @foreach($principals as $principal)
                                        <option value="{{ $principal->id }}" {{ old('principal_user_id') == $principal->id ? 'selected' : '' }}>
                                            {{ $principal->name }}
                                            @if($principal->gtkWorkUnits->first()?->workUnit)
                                                ({{ $principal->gtkWorkUnits->first()->workUnit->name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Pilih dari GTK yang bertugas di unit Unsur Pimpinan.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIP Kepala Sekolah</label>
                                <input type="text" name="principal_nip" class="form-control" value="{{ old('principal_nip') }}" maxlength="30">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Akreditasi</label>
                                <input type="text" name="accreditation" class="form-control" value="{{ old('accreditation') }}" maxlength="10" placeholder="A / B / C">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahun Akreditasi</label>
                                <input type="number" name="accreditation_year" class="form-control" value="{{ old('accreditation_year') }}" min="2000" max="2099">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jam Operasional</label>
                                <select name="operational_hours" class="form-control">
                                    <option value="">Pilih</option>
                                    <option value="pagi" {{ old('operational_hours') === 'pagi' ? 'selected' : '' }}>Pagi</option>
                                    <option value="siang" {{ old('operational_hours') === 'siang' ? 'selected' : '' }}>Siang</option>
                                    <option value="full_day" {{ old('operational_hours') === 'full_day' ? 'selected' : '' }}>Full Day</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun Berdiri</label>
                                <input type="date" name="established_date" class="form-control" value="{{ old('established_date') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SK Pendirian</label>
                                <input type="text" name="established_decree" class="form-control" value="{{ old('established_decree') }}" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Luas Tanah (m²)</label>
                                <input type="number" name="land_area" class="form-control" value="{{ old('land_area') }}" step="0.01">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Luas Bangunan (m²)</label>
                                <input type="number" name="building_area" class="form-control" value="{{ old('building_area') }}" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── TAB 2: Kontak & Alamat ───────────────────────────── --}}
            <div class="tab-pane fade" id="alamat" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Kontak & Alamat</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Provinsi</label>
                                <select id="province_code" name="province_code" class="form-control">
                                    <option value="">Pilih Provinsi</option>
                                    @foreach($provinces ?? [] as $prov)
                                        <option value="{{ $prov->code }}" {{ old('province_code') == $prov->code ? 'selected' : '' }}>{{ $prov->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kabupaten/Kota</label>
                                <select id="city_code" name="city_code" class="form-control">
                                    <option value="">Pilih Kabupaten/Kota</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kecamatan</label>
                                <select id="district_code" name="district_code" class="form-control">
                                    <option value="">Pilih Kecamatan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Desa/Kelurahan</label>
                                <select id="village_code" name="village_code" class="form-control">
                                    <option value="">Pilih Desa/Kelurahan</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code') }}" maxlength="10">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" maxlength="20">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Website</label>
                                <input type="text" name="website" class="form-control" value="{{ old('website') }}" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── TAB 3: Kop Surat & Logo ─────────────────────────── --}}
            <div class="tab-pane fade" id="kop" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Kop Surat & Logo Sekolah</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Logo Sekolah</label>
                                <input type="file" name="logo_path" class="form-control" accept="image/*">
                                <small class="text-muted">Format: JPG, PNG. Maks 2MB.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">File Kop Surat</label>
                                <input type="file" name="kop_path" class="form-control" accept="image/*,.pdf">
                                <small class="text-muted">Upload gambar/foto kop surat. Maks 2MB.</small>
                            </div>
                            <div class="col-12"><hr><h6 class="mb-3">Detail Kop Surat</h6></div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Institution (Kop)</label>
                                <input type="text" name="kop_nama" class="form-control" value="{{ old('kop_nama') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NPSN (Kop)</label>
                                <input type="text" name="kop_npsn" class="form-control" value="{{ old('kop_npsn') }}" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat (Kop)</label>
                                <textarea name="kop_alamat" class="form-control" rows="2">{{ old('kop_alamat') }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telepon (Kop)</label>
                                <input type="text" name="kop_telp" class="form-control" value="{{ old('kop_telp') }}" maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email (Kop)</label>
                                <input type="email" name="kop_email" class="form-control" value="{{ old('kop_email') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Website (Kop)</label>
                                <input type="text" name="kop_website" class="form-control" value="{{ old('kop_website') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── TAB 4: Legalitas & TTD ───────────────────────────── --}}
            <div class="tab-pane fade" id="legal" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Legalitas & Tanda Tangan</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Scan TTD Kepala Sekolah</label>
                                <input type="file" name="ttd_ksp_path" class="form-control" accept="image/*">
                                <small class="text-muted">Format: PNG dengan transparansi. Maks 2MB.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cap/Stempel Sekolah</label>
                                <input type="file" name="stamp_path" class="form-control" accept="image/*">
                                <small class="text-muted">Format: PNG dengan transparansi. Maks 2MB.</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="kopsis_active" value="1" checked>
                                    <label class="form-check-label">KOP Surat Aktif</label>
                                </div>
                                <small class="text-muted d-block">Jika aktif, kop surat digunakan saat mencetak dokumen.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── TAB 5: Informasi Bank ─────────────────────────────── --}}
            <div class="tab-pane fade" id="bank" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Informasi Bank & NPWP</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Bank</label>
                                <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cabang Bank</label>
                                <input type="text" name="bank_cabang" class="form-control" value="{{ old('bank_cabang') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Rekening</label>
                                <input type="text" name="bank_rekening" class="form-control" value="{{ old('bank_rekening') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Atas Nama</label>
                                <input type="text" name="bank_an" class="form-control" value="{{ old('bank_an') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NPWP</label>
                                <input type="text" name="npwp" class="form-control" value="{{ old('npwp') }}" maxlength="30">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Submit --}}
        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('user.schools.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-success">
                <i class="ri-save-line me-1"></i> Simpan Sekolah
            </button>
        </div>
    </form>
@endsection

@section('script')
    <script>
    // ── Geographic selects ───────────────────────────────────────
    async function loadCities(provinceCode, targetId) {
        if (!provinceCode) return;
        const target = document.getElementById(targetId);
        if (!target) return;
        target.disabled = true;
        try {
            const res = await fetch(`/api/wilayah/cities/${provinceCode}`);
            const json = await res.json();
            let html = '<option value="">Pilih Kabupaten/Kota</option>';
            json.data.forEach(c => { html += `<option value="${c.code}">${c.name}</option>`; });
            target.innerHTML = html;
        } catch (e) {
            target.innerHTML = '<option value="">Gagal memuat</option>';
        } finally {
            target.disabled = false;
        }
    }

    async function loadDistricts(cityCode, targetId) {
        if (!cityCode) return;
        const target = document.getElementById(targetId);
        if (!target) return;
        target.disabled = true;
        try {
            const res = await fetch(`/api/wilayah/districts/${cityCode}`);
            const json = await res.json();
            let html = '<option value="">Pilih Kecamatan</option>';
            json.data.forEach(d => { html += `<option value="${d.code}">${d.name}</option>`; });
            target.innerHTML = html;
        } catch (e) {
            target.innerHTML = '<option value="">Gagal memuat</option>';
        } finally {
            target.disabled = false;
        }
    }

    async function loadVillages(districtCode, targetId) {
        if (!districtCode) return;
        const target = document.getElementById(targetId);
        if (!target) return;
        target.disabled = true;
        try {
            const res = await fetch(`/api/wilayah/villages/${districtCode}`);
            const json = await res.json();
            let html = '<option value="">Pilih Desa/Kelurahan</option>';
            json.data.forEach(v => { html += `<option value="${v.code}">${v.name}</option>`; });
            target.innerHTML = html;
        } catch (e) {
            target.innerHTML = '<option value="">Gagal memuat</option>';
        } finally {
            target.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // ── WorkUnit auto-fill ───────────────────────────────────
        const workUnitsData = @json($workUnits);
        const schoolLevelEl = document.querySelector('select[name="school_level"]');
        const schoolNameEl = document.getElementById('school_name');

        function syncSchoolFields() {
            const selectedId = document.getElementById('work_unit_id')?.value;
            const wu = workUnitsData.find(w => w.id === selectedId);
            if (!wu) return;
            if (schoolNameEl) schoolNameEl.value = wu.name;
            const nameUpper = wu.name.toUpperCase();
            let school_level = '';
            if (/\bSD\b/.test(nameUpper)) school_level = 'sd';
            else if (/\bSMP\b/.test(nameUpper)) school_level = 'smp';
            else if (/\bSMA\b/.test(nameUpper) || /\bSMK\b/.test(nameUpper)) school_level = 'sma';
            else if (/\bMA\b/.test(nameUpper)) school_level = 'sma';
            if (schoolLevelEl && school_level) schoolLevelEl.value = school_level;
        }

        document.getElementById('work_unit_id')?.addEventListener('change', syncSchoolFields);

        // ── Province change → load cities
        document.getElementById('province_code')?.addEventListener('change', function () {
            loadCities(this.value, 'city_code');
            const districtSelect = document.getElementById('district_code');
            const villageSelect = document.getElementById('village_code');
            if (districtSelect) districtSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
            if (villageSelect) villageSelect.innerHTML = '<option value="">Pilih Desa/Kelurahan</option>';
        });

        // City change → load districts
        document.getElementById('city_code')?.addEventListener('change', function () {
            loadDistricts(this.value, 'district_code');
            const villageSelect = document.getElementById('village_code');
            if (villageSelect) villageSelect.innerHTML = '<option value="">Pilih Desa/Kelurahan</option>';
        });

        // District change → load villages
        document.getElementById('district_code')?.addEventListener('change', function () {
            loadVillages(this.value, 'village_code');
        });

        // Bootstrap tab persistence via hash
        const hash = window.location.hash;
        if (hash) {
            const tab = new bootstrap.Tab(document.querySelector(`[href='${hash}']`));
            tab.show();
        }
    });
    </script>
@endsection
