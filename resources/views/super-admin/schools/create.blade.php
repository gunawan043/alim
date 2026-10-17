@extends('layouts.master')
@section('title') Tambah Sekolah @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') <a href="{{ route('user.sa.schools.index', ['userId' => $userId]) }}" class="text-muted">Manajemen Sekolah</a> @endslot
        @slot('title') Tambah Sekolah @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Tambah Sekolah Baru</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.sa.schools.store', ['userId' => $userId]) }}" enctype="multipart/form-data">
                        @csrf

                        <h6 class="mb-3">Informasi Dasar</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Nama Sekolah <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">NPSN <span class="text-danger">*</span></label>
                                <input type="text" name="npsn" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Sekolah</label>
                                <input type="text" name="school_code" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unit Kerja <span class="text-danger">*</span></label>
                                <select name="work_unit_id" class="form-select" required>
                                    <option value="">Pilih unit...</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenjang</label>
                                <select name="school_level" class="form-select">
                                    <option value="">Pilih jenjang...</option>
                                    <option value="sd">SD</option>
                                    <option value="smp">SMP</option>
                                    <option value="sma">SMA</option>
                                    <option value="smk">SMK</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="school_status" class="form-select">
                                    <option value="negeri">Negeri</option>
                                    <option value="swasta">Swasta</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="school_gender" class="form-select">
                                    <option value="putra">Putra</option>
                                    <option value="putri">Putri</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3">Alamat</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Provinsi</label>
                                <select name="province_code" class="form-select" onchange="loadCities(this.value, 'city_code')">
                                    <option value="">Pilih provinsi...</option>
                                    @foreach($provinces as $prov)
                                        <option value="{{ $prov->code }}">{{ $prov->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kota</label>
                                <select name="city_code" class="form-select">
                                    <option value="">Pilih kota...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kecamatan</label>
                                <select name="district_code" class="form-select">
                                    <option value="">Pilih kecamatan...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Desa</label>
                                <select name="village_code" class="form-select">
                                    <option value="">Pilih desa...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" name="postal_code" class="form-control">
                            </div>
                        </div>

                        <h6 class="mb-3">Kontak</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control">
                            </div>
                        </div>

                        <h6 class="mb-3">Data Kepala Sekolah</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Nama Kepala Sekolah</label>
                                <input type="text" name="principal_name" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">NIP</label>
                                <input type="text" name="principal_nip" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Akun Pengguna</label>
                                <select name="principal_user_id" class="form-select">
                                    <option value="">Tidak dihubungkan</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3">Informasi Lainnya</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Berdiri</label>
                                <input type="date" name="established_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nomor SK</label>
                                <input type="text" name="established_decree" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Akreditasi</label>
                                <select name="accreditation" class="form-select">
                                    <option value="">Belum dinilai</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jam Operasional</label>
                                <select name="operational_hours" class="form-select">
                                    <option value="pagi">Pagi</option>
                                    <option value="siang">Siang</option>
                                    <option value="full_day">Sepanjang Hari</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3">Dokumen</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Logo Sekolah</label>
                                <input type="file" name="logo_path" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kop Surat</label>
                                <input type="file" name="kop_path" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
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

@section('script')
    <script>
    function loadCities(provinceCode, selectId) {
        // Implementation - in production would use AJAX
        const select = document.getElementById(selectId);
        select.innerHTML = '<option value="">Pilih kota...</option>';
    }
    </script>
@endsection
