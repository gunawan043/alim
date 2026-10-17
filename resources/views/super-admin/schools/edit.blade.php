@extends('layouts.master')
@section('title') Edit Sekolah @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') <a href="{{ route('user.sa.schools.index', ['userId' => $userId]) }}" class="text-muted">Manajemen Sekolah</a> @endslot
        @slot('title') Edit Sekolah @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Edit Sekolah</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.sa.schools.update', ['userId' => $userId, 'id' => $school->id]) }}" enctype="multipart/form-data">
                        @csrf @method('PUT')

                        <h6 class="mb-3">Informasi Dasar</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Nama Sekolah <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $school->name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">NPSN <span class="text-danger">*</span></label>
                                <input type="text" name="npsn" class="form-control" value="{{ old('npsn', $school->npsn) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Sekolah</label>
                                <input type="text" name="school_code" class="form-control" value="{{ old('school_code', $school->school_code) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unit Kerja <span class="text-danger">*</span></label>
                                <select name="work_unit_id" class="form-select" required>
                                    <option value="">Pilih unit...</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}" {{ $school->work_unit_id == $wu->id ? 'selected' : '' }}>{{ $wu->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenjang</label>
                                <select name="school_level" class="form-select">
                                    <option value="">Pilih jenjang...</option>
                                    <option value="sd" {{ $school->school_level == 'sd' ? 'selected' : '' }}>SD</option>
                                    <option value="smp" {{ $school->school_level == 'smp' ? 'selected' : '' }}>SMP</option>
                                    <option value="sma" {{ $school->school_level == 'sma' ? 'selected' : '' }}>SMA</option>
                                    <option value="smk" {{ $school->school_level == 'smk' ? 'selected' : '' }}>SMK</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="school_status" class="form-select">
                                    <option value="negeri" {{ $school->school_status == 'negeri' ? 'selected' : '' }}>Negeri</option>
                                    <option value="swasta" {{ $school->school_status == 'swasta' ? 'selected' : '' }}>Swasta</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="school_gender" class="form-select">
                                    <option value="putra" {{ $school->school_gender == 'putra' ? 'selected' : '' }}>Putra</option>
                                    <option value="putri" {{ $school->school_gender == 'putri' ? 'selected' : '' }}>Putri</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3">Alamat</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address', $school->address) }}</textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Provinsi</label>
                                <select name="province_code" class="form-select">
                                    <option value="">Pilih provinsi...</option>
                                    @foreach($provinces as $prov)
                                        <option value="{{ $prov->code }}" {{ $school->province_code == $prov->code ? 'selected' : '' }}>{{ $prov->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kota</label>
                                <input type="text" name="city_code" class="form-control" value="{{ old('city_code', $school->city_code) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kecamatan</label>
                                <input type="text" name="district_code" class="form-control" value="{{ old('district_code', $school->district_code) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Desa</label>
                                <input type="text" name="village_code" class="form-control" value="{{ old('village_code', $school->village_code) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $school->postal_code) }}">
                            </div>
                        </div>

                        <h6 class="mb-3">Kontak</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $school->phone) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $school->email) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control" value="{{ old('website', $school->website) }}">
                            </div>
                        </div>

                        <h6 class="mb-3">Data Kepala Sekolah</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Nama Kepala Sekolah</label>
                                <input type="text" name="principal_name" class="form-control" value="{{ old('principal_name', $school->principal_name) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">NIP</label>
                                <input type="text" name="principal_nip" class="form-control" value="{{ old('principal_nip', $school->principal_nip) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Akun Pengguna</label>
                                <select name="principal_user_id" class="form-select">
                                    <option value="">Tidak dihubungkan</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $school->principal_user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3">Informasi Lainnya</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Berdiri</label>
                                <input type="date" name="established_date" class="form-control" value="{{ old('established_date', $school->established_date?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nomor SK</label>
                                <input type="text" name="established_decree" class="form-control" value="{{ old('established_decree', $school->established_decree) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Akreditasi</label>
                                <select name="accreditation" class="form-select">
                                    <option value="">Belum dinilai</option>
                                    <option value="A" {{ $school->accreditation == 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ $school->accreditation == 'B' ? 'selected' : '' }}>B</option>
                                    <option value="C" {{ $school->accreditation == 'C' ? 'selected' : '' }}>C</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jam Operasional</label>
                                <select name="operational_hours" class="form-select">
                                    <option value="pagi" {{ $school->operational_hours == 'pagi' ? 'selected' : '' }}>Pagi</option>
                                    <option value="siang" {{ $school->operational_hours == 'siang' ? 'selected' : '' }}>Siang</option>
                                    <option value="full_day" {{ $school->operational_hours == 'full_day' ? 'selected' : '' }}>Sepanjang Hari</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3">Dokumen</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Logo Sekolah</label>
                                @if($school->logo_path)
                                    <div class="mb-2"><img src="{{ $school->logo_url }}" alt="logo" width="100"></div>
                                @endif
                                <input type="file" name="logo_path" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kop Surat</label>
                                <input type="file" name="kop_path" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" {{ $school->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>

                        <div class="float-end gap-2">
                            <a href="{{ route('user.sa.schools.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
