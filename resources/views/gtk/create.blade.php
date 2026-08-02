@extends('layouts.master')
@section('title')
    @lang('translation.tambah_gtk')
@endsection

@section('content')
    @php $userId = request()->route('userId') ?? Auth::id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('title') Tambah Data GTK @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Form Pendaftaran GTK</h4>
                    <div class="mt-3">
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-danger" role="progressbar"
                                 style="width: 0%" id="progressBar"
                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="mt-2 text-center">
                            <span id="progressText" class="badge bg-primary">0% Lengkap</span>
                        </div>
                    </div>
                </div>

                <div class="card-body form-steps">
                    <form id="gtkWizardForm" class="vertical-navs-step" enctype="multipart/form-data">
                        @csrf
                        <div class="row gy-5">

                            {{-- ============================================================
                                 SIDEBAR NAV
                            ============================================================ --}}
                            <div class="col-lg-3">
                                <div class="nav flex-column custom-nav nav-pills" role="tablist" aria-orientation="vertical">

                                    <button class="nav-link active" id="v-pills-data-dasar-tab"
                                        data-bs-toggle="pill" data-bs-target="#v-pills-data-dasar"
                                        type="button" role="tab" aria-selected="true">
                                        <span class="step-title me-2">
                                            <i class="ri-user-fill step-icon me-2"></i> Step 1
                                        </span>
                                        Data Dasar
                                    </button>

                                    <button class="nav-link" id="v-pills-alamat-tab"
                                        data-bs-toggle="pill" data-bs-target="#v-pills-alamat"
                                        type="button" role="tab">
                                        <span class="step-title me-2">
                                            <i class="ri-home-fill step-icon me-2"></i> Step 2
                                        </span>
                                        Alamat
                                    </button>

                                    <button class="nav-link" id="v-pills-pendidikan-tab"
                                        data-bs-toggle="pill" data-bs-target="#v-pills-pendidikan"
                                        type="button" role="tab">
                                        <span class="step-title me-2">
                                            <i class="ri-graduation-cap-fill step-icon me-2"></i> Step 3
                                        </span>
                                        Riwayat Pendidikan
                                    </button>

                                    <button class="nav-link" id="v-pills-kontak-tab"
                                        data-bs-toggle="pill" data-bs-target="#v-pills-kontak"
                                        type="button" role="tab">
                                        <span class="step-title me-2">
                                            <i class="ri-phone-fill step-icon me-2"></i> Step 4
                                        </span>
                                        Kontak
                                    </button>

                                    <button class="nav-link" id="v-pills-kepegawaian-tab"
                                        data-bs-toggle="pill" data-bs-target="#v-pills-kepegawaian"
                                        type="button" role="tab">
                                        <span class="step-title me-2">
                                            <i class="ri-briefcase-fill step-icon me-2"></i> Step 5
                                        </span>
                                        Kepegawaian
                                    </button>

                                    <button class="nav-link" id="v-pills-keluarga-tab"
                                        data-bs-toggle="pill" data-bs-target="#v-pills-keluarga"
                                        type="button" role="tab">
                                        <span class="step-title me-2">
                                            <i class="ri-group-fill step-icon me-2"></i> Step 6
                                        </span>
                                        Keluarga
                                    </button>

                                    <button class="nav-link" id="v-pills-review-tab"
                                        data-bs-toggle="pill" data-bs-target="#v-pills-review"
                                        type="button" role="tab">
                                        <span class="step-title me-2">
                                            <i class="ri-checkbox-circle-fill step-icon me-2"></i> Step 7
                                        </span>
                                        Review
                                    </button>

                                </div>
                            </div>
                            {{-- end sidebar --}}

                            {{-- ============================================================
                                 TAB CONTENT
                            ============================================================ --}}
                            <div class="col-lg-9">
                                <div class="px-lg-4">
                                    <div class="tab-content">

                                        {{-- ================================================
                                             STEP 1: DATA DASAR
                                        ================================================ --}}
                                        <div class="tab-pane fade show active" id="v-pills-data-dasar"
                                             role="tabpanel" aria-labelledby="v-pills-data-dasar-tab">
                                            <div>
                                                <h5>Data Dasar</h5>
                                                <p class="text-muted">Isi data pribadi GTK</p>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="name" name="name"
                                                           placeholder="Masukkan nama lengkap" required>
                                                    <div class="invalid-feedback">Harap masukkan nama lengkap</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                                    <input type="email" class="form-control" id="email" name="email"
                                                           placeholder="nama@contoh.com" required>
                                                    <div class="invalid-feedback">Harap masukkan email yang valid</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="nik" class="form-label">NIK <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="nik" name="nik"
                                                           placeholder="16 digit NIK" required
                                                           maxlength="16" inputmode="numeric" pattern="\d{16}">
                                                    <div class="invalid-feedback">NIK harus 16 digit angka</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="no_kk" class="form-label">No. KK</label>
                                                    <input type="text" class="form-control" id="no_kk" name="no_kk"
                                                           placeholder="16 digit No. KK" maxlength="16" inputmode="numeric">
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="tempat_lahir" class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="tempat_lahir"
                                                           name="tempat_lahir" placeholder="Kota/Kabupaten" required>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="tanggal_lahir" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" id="tanggal_lahir"
                                                           name="tanggal_lahir" required>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="jenis_kelamin" class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                                        <option value="">Pilih...</option>
                                                        <option value="L">Laki-laki</option>
                                                        <option value="P">Perempuan</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="golongan_darah" class="form-label">Golongan Darah</label>
                                                    <select class="form-select" id="golongan_darah" name="golongan_darah">
                                                        <option value="">Pilih...</option>
                                                        <option value="A">A</option>
                                                        <option value="B">B</option>
                                                        <option value="AB">AB</option>
                                                        <option value="O">O</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="agama" class="form-label">Agama</label>
                                                    <select class="form-select" id="agama" name="agama">
                                                        <option value="">Pilih...</option>
                                                        <option value="islam">Islam</option>
                                                        <option value="kristen">Kristen</option>
                                                        <option value="katolik">Katolik</option>
                                                        <option value="hindu">Hindu</option>
                                                        <option value="buddha">Buddha</option>
                                                        <option value="konghucu">Konghucu</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="status_perkawinan" class="form-label">Status Perkawinan</label>
                                                    <select class="form-select" id="status_perkawinan" name="status_perkawinan">
                                                        <option value="">Pilih...</option>
                                                        <option value="belum_kawin">Belum Kawin</option>
                                                        <option value="kawin">Kawin</option>
                                                        <option value="cerai_hidup">Cerai Hidup</option>
                                                        <option value="cerai_mati">Cerai Mati</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="nama_ibu_kandung" class="form-label">Nama Ibu Kandung</label>
                                                    <input type="text" class="form-control" id="nama_ibu_kandung"
                                                           name="nama_ibu_kandung" placeholder="Nama ibu kandung">
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="npwp" class="form-label">NPWP</label>
                                                    <input type="text" class="form-control" id="npwp"
                                                           name="npwp" placeholder="Nomor NPWP">
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="button" class="btn btn-success btn-label right ms-auto next-step"
                                                        data-next="v-pills-alamat-tab">
                                                    <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                    Lanjut ke Alamat
                                                </button>
                                            </div>
                                        </div>

                                        {{-- ================================================
                                             STEP 2: ALAMAT
                                        ================================================ --}}
                                        <div class="tab-pane fade" id="v-pills-alamat" role="tabpanel">

                                            <div>
                                                <h5>Alamat</h5>
                                                <p class="text-muted">Isi data alamat domisili dan KTP</p>
                                            </div>

                                            {{-- DOMISILI --}}
                                            <div class="mb-4">
                                                <h6 class="border-bottom pb-2">Alamat Domisili</h6>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Provinsi <span class="text-danger">*</span></label>
                                                        {{-- FIX: gunakan $province->code bukan $province->id --}}
                                                        <select class="form-select" id="provinsi_domisili"
                                                                name="alamat_domisili[province_code]" required>
                                                            <option value="">Pilih Provinsi</option>
                                                            @foreach ($provinces as $province)
                                                                <option value="{{ $province->code }}">{{ $province->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="invalid-feedback">Harap pilih provinsi</div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Kabupaten/Kota <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="kabupaten_domisili"
                                                                name="alamat_domisili[city_code]" required>
                                                            <option value="">Pilih Kabupaten/Kota</option>
                                                        </select>
                                                        <div class="invalid-feedback">Harap pilih kabupaten/kota</div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Kecamatan <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="kecamatan_domisili"
                                                                name="alamat_domisili[district_code]" required>
                                                            <option value="">Pilih Kecamatan</option>
                                                        </select>
                                                        <div class="invalid-feedback">Harap pilih kecamatan</div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Desa <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="desa_domisili"
                                                                name="alamat_domisili[village_code]" required>
                                                            <option value="">Pilih Desa</option>
                                                        </select>
                                                        <div class="invalid-feedback">Harap pilih desa</div>
                                                    </div>

                                                    <div class="col-12">
                                                        <label class="form-label">Jalan <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="jalan_domisili"
                                                               name="alamat_domisili[jalan]" required
                                                               placeholder="Nama jalan, nomor rumah">
                                                        <div class="invalid-feedback">Harap isi nama jalan</div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">RT/RW <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="rt_rw_domisili"
                                                               name="alamat_domisili[rt_rw]" placeholder="001/002" required>
                                                        <div class="invalid-feedback">Harap isi RT/RW</div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">Dusun</label>
                                                        <input type="text" class="form-control" id="dusun_domisili"
                                                               name="alamat_domisili[dusun]">
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">Kode Pos</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="kode_pos_domisili"
                                                                   name="alamat_domisili[kode_pos]"
                                                                   placeholder="Auto / isi manual">
                                                            <button class="btn btn-outline-secondary" type="button"
                                                                    onclick="document.getElementById('kode_pos_domisili').value=''">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                        <small class="text-muted">Terisi otomatis jika data tersedia, atau isi manual</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-check mb-4">
                                                <input class="form-check-input" type="checkbox" id="sameAddress">
                                                <label class="form-check-label" for="sameAddress">
                                                    Alamat KTP sama dengan alamat domisili
                                                </label>
                                            </div>

                                            {{-- KTP --}}
                                            <div>
                                                <h6 class="border-bottom pb-2">Alamat KTP</h6>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Provinsi</label>
                                                        <select class="form-select" id="provinsi_ktp"
                                                                name="alamat_ktp[province_code]">
                                                            <option value="">Pilih Provinsi</option>
                                                            @foreach ($provinces as $province)
                                                                <option value="{{ $province->code }}">{{ $province->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Kabupaten/Kota</label>
                                                        <select class="form-select" id="kabupaten_ktp"
                                                                name="alamat_ktp[city_code]">
                                                            <option value="">Pilih Kabupaten/Kota</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Kecamatan</label>
                                                        <select class="form-select" id="kecamatan_ktp"
                                                                name="alamat_ktp[district_code]">
                                                            <option value="">Pilih Kecamatan</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Desa</label>
                                                        <select class="form-select" id="desa_ktp"
                                                                name="alamat_ktp[village_code]">
                                                            <option value="">Pilih Desa</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-12">
                                                        <label class="form-label">Jalan</label>
                                                        <input type="text" class="form-control" id="jalan_ktp"
                                                               name="alamat_ktp[jalan]">
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">RT/RW</label>
                                                        <input type="text" class="form-control" id="rt_rw_ktp"
                                                               name="alamat_ktp[rt_rw]" placeholder="001/002">
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">Dusun</label>
                                                        <input type="text" class="form-control" id="dusun_ktp"
                                                               name="alamat_ktp[dusun]">
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">Kode Pos</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="kode_pos_ktp"
                                                                   name="alamat_ktp[kode_pos]"
                                                                   placeholder="Auto / isi manual">
                                                            <button class="btn btn-outline-secondary" type="button"
                                                                    onclick="document.getElementById('kode_pos_ktp').value=''">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="button" class="btn btn-light btn-label prev-step"
                                                        data-prev="v-pills-data-dasar-tab">
                                                    <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                    Kembali ke Data Dasar
                                                </button>
                                                <button type="button" class="btn btn-success btn-label right ms-auto next-step"
                                                        data-next="v-pills-pendidikan-tab">
                                                    <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                    Lanjut ke Pendidikan
                                                </button>
                                            </div>
                                        </div>

                                        {{-- ================================================
                                             STEP 3: RIWAYAT PENDIDIKAN
                                        ================================================ --}}
                                        <div class="tab-pane fade" id="v-pills-pendidikan" role="tabpanel">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h5>Riwayat Pendidikan</h5>
                                                    <p class="text-muted">Tambahkan semua riwayat pendidikan dari SD hingga terakhir (opsional)</p>
                                                </div>
                                                <button type="button" class="btn btn-primary btn-sm"
                                                        onclick="addEducationRow()">
                                                    <i class="ri-add-line align-middle me-1"></i> Tambah Pendidikan
                                                </button>
                                            </div>

                                            <div class="alert alert-info py-2">
                                                <i class="ri-information-line align-middle me-1"></i>
                                                Urutkan dari pendidikan <strong>paling rendah (SD)</strong> ke paling tinggi. Upload ijazah bersifat opsional.
                                            </div>

                                            <div id="educationContainer"></div>

                                            <div id="educationEmpty" class="text-center py-5 text-muted">
                                                <i class="ri-graduation-cap-line fs-1 d-block mb-2"></i>
                                                <p>Belum ada riwayat pendidikan.<br>
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-1"
                                                        onclick="addEducationRow()">Klik di sini untuk menambahkan</button>
                                                </p>
                                            </div>

                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="button" class="btn btn-light btn-label prev-step"
                                                        data-prev="v-pills-alamat-tab">
                                                    <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                    Kembali ke Alamat
                                                </button>
                                                <button type="button" class="btn btn-success btn-label right ms-auto next-step"
                                                        data-next="v-pills-kontak-tab">
                                                    <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                    Lanjut ke Kontak
                                                </button>
                                            </div>
                                        </div>

                                        {{-- ================================================
                                             STEP 4: KONTAK
                                        ================================================ --}}
                                        <div class="tab-pane fade" id="v-pills-kontak" role="tabpanel">
                                            <div>
                                                <h5>Kontak</h5>
                                                <p class="text-muted">Isi informasi kontak GTK</p>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">No. HP <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="no_hp"
                                                           name="kontak[no_hp]" required inputmode="numeric"
                                                           placeholder="Contoh: 08123456789">
                                                    <div class="invalid-feedback">Harap isi nomor HP</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">No. WhatsApp</label>
                                                    <input type="text" class="form-control" id="no_whatsapp"
                                                           name="kontak[no_whatsapp]" inputmode="numeric"
                                                           placeholder="Kosongkan jika sama dengan HP">
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label">Kontak Darurat</label>
                                                    <input type="text" class="form-control" id="kontak_darurat"
                                                           name="kontak[kontak_darurat]"
                                                           placeholder="Nama - Hubungan - No. Telepon">
                                                </div>

                                                <div class="col-12"><hr class="my-1"><small class="text-muted">Media Sosial (opsional)</small></div>

                                                <div class="col-md-4">
                                                    <label class="form-label">Instagram</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">@</span>
                                                        <input type="text" class="form-control" id="instagram"
                                                               name="kontak[instagram]" placeholder="username">
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label">Facebook</label>
                                                    <input type="text" class="form-control" id="facebook"
                                                           name="kontak[facebook]" placeholder="nama.facebook">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label">Twitter/X</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">@</span>
                                                        <input type="text" class="form-control" id="twitter"
                                                               name="kontak[twitter]" placeholder="username">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="button" class="btn btn-light btn-label prev-step"
                                                        data-prev="v-pills-pendidikan-tab">
                                                    <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                    Kembali ke Pendidikan
                                                </button>
                                                <button type="button" class="btn btn-success btn-label right ms-auto next-step"
                                                        data-next="v-pills-kepegawaian-tab">
                                                    <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                    Lanjut ke Kepegawaian
                                                </button>
                                            </div>
                                        </div>

                                        {{-- ================================================
                                             STEP 5: KEPEGAWAIAN
                                        ================================================ --}}
                                        <div class="tab-pane fade" id="v-pills-kepegawaian" role="tabpanel">
                                            <div>
                                                <h5>Kepegawaian & Unit Kerja</h5>
                                                <p class="text-muted">Isi data kepegawaian GTK</p>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">NUPY <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="nupy"
                                                           name="kepegawaian[nupy]" required
                                                           oninput="syncNupyPassword(this.value)">
                                                    <small class="text-muted">Password default: <strong>NUPY@12345</strong></small>
                                                    <div class="invalid-feedback">Harap isi NUPY</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Jenis GTK <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="jenis_gtk"
                                                            name="kepegawaian[jenis_gtk]" required>
                                                        <option value="">Pilih...</option>
                                                        @foreach($jenisGtk as $jg)
                                                            <option value="{{ $jg->id }}">{{ $jg->nama }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="invalid-feedback">Harap pilih jenis GTK</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="jabatan"
                                                            name="kepegawaian[jabatan]" required>
                                                        <option value="">Pilih jabatan...</option>
                                                    </select>
                                                    <div class="invalid-feedback">Harap pilih jabatan</div>
                                                    <small id="jabatan-role-preview" class="form-text text-muted d-none">
                                                        Role otomatis: <span class="badge bg-info">GTK</span>
                                                        <span class="jabatan-roles-badges"></span>
                                                    </small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Status Kepegawaian <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="status_kepegawaian"
                                                            name="kepegawaian[status_kepegawaian]" required>
                                                        <option value="">Pilih...</option>
                                                        <option value="PTT">Pegawai Tidak Tetap</option>
                                                        <option value="PTY">Pegawai Tetap Yayasan</option>
                                                        <option value="GTT">Guru Tidak Tetap</option>
                                                        <option value="GTY">Guru Tetap Yayasan</option>
                                                        <option value="Percobaan">Pegawai Percobaan</option>
                                                        <option value="Magang">Pegawai Magang</option>
                                                        <option value="KONTRAK">Kontrak</option>
                                                    </select>
                                                    <div class="invalid-feedback">Harap pilih status kepegawaian</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">TMT <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" id="tmt"
                                                           name="kepegawaian[tmt]" required>
                                                    <div class="invalid-feedback">Harap pilih TMT</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Nomor SK <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="nomor_sk"
                                                           name="kepegawaian[nomor_sk]" required>
                                                    <div class="invalid-feedback">Harap isi nomor SK</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Tanggal SK <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" id="tanggal_sk"
                                                           name="kepegawaian[tanggal_sk]" required>
                                                    <div class="invalid-feedback">Harap pilih tanggal SK</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Pangkat/Golongan</label>
                                                    <input type="text" class="form-control" id="pangkat_golongan"
                                                           name="kepegawaian[pangkat_golongan]"
                                                           placeholder="Contoh: III/A">
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Unit Kerja <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="work_unit_id" name="work_unit_id" required>
                                                        <option value="">Pilih Unit Kerja</option>
                                                        @foreach ($workUnits as $unit)
                                                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="invalid-feedback">Harap pilih unit kerja</div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="alert alert-warning mb-0">
                                                        <i class="ri-alert-line align-middle me-2"></i>
                                                        Password default akan dibuat dari <strong>NUPY@12345</strong>. Catat sebelum menyimpan.
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="button" class="btn btn-light btn-label prev-step"
                                                        data-prev="v-pills-kontak-tab">
                                                    <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                    Kembali ke Kontak
                                                </button>
                                                <button type="button" class="btn btn-success btn-label right ms-auto next-step"
                                                        data-next="v-pills-keluarga-tab">
                                                    <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                    Lanjut ke Keluarga
                                                </button>
                                            </div>
                                        </div>

                                        {{-- ================================================
                                             STEP 6: KELUARGA
                                        ================================================ --}}
                                        <div class="tab-pane fade" id="v-pills-keluarga" role="tabpanel">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5>Anggota Keluarga</h5>
                                                    <p class="text-muted mb-0">Tambahkan anggota keluarga GTK (opsional)</p>
                                                </div>
                                                <button type="button" class="btn btn-primary btn-sm"
                                                        onclick="showAddFamilyModal()">
                                                    <i class="ri-add-line align-middle me-1"></i> Tambah Anggota
                                                </button>
                                            </div>

                                            <div class="card border mb-3" id="spouseSearchCard">
                                                <div class="card-body py-3">
                                                    <h6 class="mb-2">
                                                        <i class="ri-search-line me-1"></i>
                                                        Cari Pasangan yang Sudah Terdaftar sebagai GTK
                                                    </h6>
                                                    <p class="text-muted small mb-3">
                                                        Jika pasangan Anda sudah terdaftar sebagai GTK, cari di sini dan data akan terisi otomatis.
                                                    </p>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="spouseSearchInput"
                                                               placeholder="Ketik nama atau NUPY pasangan..."
                                                               autocomplete="off">
                                                        <button class="btn btn-outline-primary" type="button" id="spouseSearchBtn">
                                                            <i class="ri-search-line"></i> Cari
                                                        </button>
                                                    </div>
                                                    <div id="spouseSearchResults" class="mt-2" style="display:none;">
                                                        <div class="list-group" id="spouseResultList"></div>
                                                    </div>
                                                    <div id="spouseSelectedPreview" class="mt-3" style="display:none;">
                                                        <div class="alert alert-success py-2 d-flex align-items-center justify-content-between mb-0">
                                                            <div>
                                                                <i class="ri-checkbox-circle-fill me-2"></i>
                                                                <span id="spouseSelectedName"></span>
                                                                <span class="badge bg-success ms-2">Terhubung</span>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                    onclick="clearSpouseSelection()">
                                                                <i class="ri-close-line"></i> Hapus
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="familyMembersContainer"></div>

                                            <div id="familyEmpty" class="text-center py-4 text-muted">
                                                <i class="ri-group-line fs-1 d-block mb-2"></i>
                                                <p class="mb-0">Belum ada anggota keluarga yang ditambahkan.</p>
                                            </div>

                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="button" class="btn btn-light btn-label prev-step"
                                                        data-prev="v-pills-kepegawaian-tab">
                                                    <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                    Kembali ke Kepegawaian
                                                </button>
                                                <button type="button" class="btn btn-success btn-label right ms-auto next-step"
                                                        data-next="v-pills-review-tab">
                                                    <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                    Lanjut ke Review
                                                </button>
                                            </div>
                                        </div>

                                        {{-- ================================================
                                             STEP 7: REVIEW
                                        ================================================ --}}
                                        <div class="tab-pane fade" id="v-pills-review" role="tabpanel">
                                            <div class="text-center pt-4 pb-2">
                                                <div class="mb-4">
                                                    <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop"
                                                               colors="primary:#0ab39c,secondary:#005981"
                                                               style="width:120px;height:120px"></lord-icon>
                                                </div>
                                                <h5>Review Data GTK</h5>
                                                <p class="text-muted">Pastikan semua data sudah benar sebelum disimpan.</p>
                                            </div>

                                            <div id="reviewData" class="mb-4"></div>

                                            <div class="alert alert-info">
                                                <i class="ri-information-line align-middle me-2"></i>
                                                Password default: <strong id="reviewPassword" class="text-danger">-</strong>
                                                — Catat sebelum menyimpan!
                                            </div>

                                            <div class="d-flex justify-content-between mt-4">
                                                <button type="button" class="btn btn-light btn-label prev-step"
                                                        data-prev="v-pills-keluarga-tab">
                                                    <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                    Kembali ke Keluarga
                                                </button>
                                                <button type="button" class="btn btn-success btn-label"
                                                        id="submitButton" onclick="submitForm()">
                                                    <i class="ri-save-line label-icon align-middle fs-16 me-2"></i>
                                                    Simpan Data GTK
                                                </button>
                                            </div>
                                        </div>

                                    </div>{{-- end tab-content --}}
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: TAMBAH / EDIT ANGGOTA KELUARGA --}}
    <div class="modal fade" id="familyMemberModal" tabindex="-1" aria-labelledby="familyMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="familyMemberModalLabel">Tambah Anggota Keluarga</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="fm_edit_index" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Hubungan <span class="text-danger">*</span></label>
                            <select class="form-select" id="fm_relationship">
                                <option value="">Pilih...</option>
                                <option value="suami">Suami</option>
                                <option value="istri">Istri</option>
                                <option value="anak">Anak</option>
                                <option value="ayah">Ayah</option>
                                <option value="ibu">Ibu</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fm_nama" placeholder="Nama lengkap">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select class="form-select" id="fm_jenis_kelamin">
                                <option value="">Pilih...</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" class="form-control" id="fm_tempat_lahir" placeholder="Kota/Kabupaten">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" id="fm_tanggal_lahir">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pekerjaan</label>
                            <input type="text" class="form-control" id="fm_pekerjaan" placeholder="Pekerjaan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pendidikan Terakhir</label>
                            <select class="form-select" id="fm_pendidikan_terakhir">
                                <option value="">Pilih...</option>
                                <option value="SD">SD</option>
                                <option value="SMP">SMP</option>
                                <option value="SMA/SMK">SMA/SMK</option>
                                <option value="D3">D3</option>
                                <option value="S1">S1</option>
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" id="fm_alamat" rows="2"
                                      placeholder="Alamat lengkap anggota keluarga"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="saveFamilyMember()">
                        <i class="ri-save-line me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script src="{{ URL::asset('build/js/pages/form-wizard.init.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
/* ==========================================================================
   CONSTANTS & STATE
   ========================================================================== */
const jabatanByJenis = {};
@foreach($jabatan as $j)
if (!jabatanByJenis['{{ $j->jenis_gtk_id }}']) jabatanByJenis['{{ $j->jenis_gtk_id }}'] = [];
jabatanByJenis['{{ $j->jenis_gtk_id }}'].push({
    id: '{{ $j->id }}',
    nama: @json($j->nama),
    roles: @json($j->roles ?? [])
});
@endforeach

let educationList  = [];
let familyList     = [];
let educationFiles = {};
let _spouseGtkId   = null;
let searchTimeout  = null;
let _eduEditIndex  = null;

/* ==========================================================================
   INIT
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function () {
    setupNavigation();
    setupTabListeners();
    setupWilayahListeners();
    setupJenisGtkListener();
    setupSpouseSearch();
    updateProgress();
    renderEducationList();
    renderFamilyList();
});

/* ==========================================================================
   NAVIGATION & PROGRESS
   ========================================================================== */
function setupNavigation() {
    document.querySelectorAll('.next-step').forEach(function(btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (validateCurrentStep()) switchToTab(this.dataset.next);
        });
    });
    document.querySelectorAll('.prev-step').forEach(function(btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            switchToTab(this.dataset.prev);
        });
    });
}

function setupTabListeners() {
    document.querySelectorAll('[data-bs-toggle="pill"]').forEach(function(btn) {
        btn.addEventListener('show.bs.tab', function (e) {
            if (e.target.getAttribute('data-bs-target') === '#v-pills-review') {
                setTimeout(showReviewData, 300);
            }
        });
    });
}

function switchToTab(tabId) {
    const el = document.getElementById(tabId);
    if (el) bootstrap.Tab.getOrCreateInstance(el).show();
}

function updateProgress() {
    const fields = document.querySelectorAll('#gtkWizardForm [required]');
    let filled = 0;
    fields.forEach(function(f) { if (f.value.trim()) filled++; });
    const pct  = fields.length ? Math.round((filled / fields.length) * 100) : 0;
    const bar  = document.getElementById('progressBar');
    const text = document.getElementById('progressText');
    if (!bar || !text) return;
    bar.style.width = pct + '%';
    bar.setAttribute('aria-valuenow', pct);
    text.textContent = pct + '% Lengkap';
    bar.className = 'progress-bar ' + (pct < 30 ? 'bg-danger' : pct < 70 ? 'bg-warning' : 'bg-success');
}

/* ==========================================================================
   VALIDATION
   ========================================================================== */
function validateCurrentStep() {
    const pane = document.querySelector('.tab-pane.active');
    if (!pane) return true;
    const fields = pane.querySelectorAll('[required]');
    let valid = true;
    let first = null;
    fields.forEach(function(f) {
        f.classList.remove('is-invalid');
        let ok = !!f.value.trim();
        if (ok && f.name === 'nik')   ok = /^\d{16}$/.test(f.value);
        if (ok && f.name === 'email') ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.value);
        if (!ok) { f.classList.add('is-invalid'); valid = false; if (!first) first = f; }
    });
    if (!valid) {
        first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        first?.focus();
        Swal.fire({ icon: 'warning', title: 'Data Belum Lengkap', text: 'Harap isi semua field wajib dengan benar.' });
    }
    return valid;
}

/* ==========================================================================
   KEPEGAWAIAN: JABATAN DYNAMIC
   ========================================================================== */
function setupJenisGtkListener() {
    document.getElementById('jenis_gtk')?.addEventListener('change', function () {
        const jabatanSelect = document.getElementById('jabatan');
        jabatanSelect.innerHTML = '<option value="">Pilih jabatan...</option>';
        const list = jabatanByJenis[this.value] || [];
        list.forEach(function(j) {
            const opt = document.createElement('option');
            opt.value = j.id;
            opt.textContent = j.nama;
            opt.dataset.nama = j.nama;
            opt.dataset.roles = (j.roles || []).join(',');
            jabatanSelect.appendChild(opt);
        });
        updateProgress();
        updateJabatanRolePreview();
    });

    document.getElementById('jabatan')?.addEventListener('change', updateJabatanRolePreview);
}

function updateJabatanRolePreview() {
    const select = document.getElementById('jabatan');
    const preview = document.getElementById('jabatan-role-preview');
    const badgesEl = preview?.querySelector('.jabatan-roles-badges');
    if (!select || !preview || !badgesEl) return;

    const opt = select.options[select.selectedIndex];
    const rolesCsv = opt?.dataset?.roles || '';
    if (!opt || !opt.value || !rolesCsv) {
        preview.classList.add('d-none');
        badgesEl.innerHTML = '';
        return;
    }

    const roles = rolesCsv.split(',').filter(Boolean);
    badgesEl.innerHTML = roles.map(r =>
        '<span class="badge bg-primary me-1">' + escHtml(r) + '</span>'
    ).join('');
    preview.classList.remove('d-none');
}

/* ==========================================================================
   WILAYAH — FIX: credentials: 'same-origin' di semua fetch
   ========================================================================== */
function setupWilayahListeners() {
    document.getElementById('provinsi_domisili')?.addEventListener('change', function () {
        loadCities(this.value, 'kabupaten_domisili');
        resetSelect('kecamatan_domisili', 'Pilih Kecamatan');
        resetSelect('desa_domisili', 'Pilih Desa');
        clearKodePos('kode_pos_domisili');
    });
    document.getElementById('kabupaten_domisili')?.addEventListener('change', function () {
        loadDistricts(this.value, 'kecamatan_domisili');
        resetSelect('desa_domisili', 'Pilih Desa');
        clearKodePos('kode_pos_domisili');
    });
    document.getElementById('kecamatan_domisili')?.addEventListener('change', function () {
        loadVillages(this.value, 'desa_domisili', 'kode_pos_domisili');
    });
    document.getElementById('desa_domisili')?.addEventListener('change', function () {
        applyPostalCode(this, 'kode_pos_domisili');
    });

    document.getElementById('provinsi_ktp')?.addEventListener('change', function () {
        loadCities(this.value, 'kabupaten_ktp');
        resetSelect('kecamatan_ktp', 'Pilih Kecamatan');
        resetSelect('desa_ktp', 'Pilih Desa');
        clearKodePos('kode_pos_ktp');
    });
    document.getElementById('kabupaten_ktp')?.addEventListener('change', function () {
        loadDistricts(this.value, 'kecamatan_ktp');
        resetSelect('desa_ktp', 'Pilih Desa');
        clearKodePos('kode_pos_ktp');
    });
    document.getElementById('kecamatan_ktp')?.addEventListener('change', function () {
        loadVillages(this.value, 'desa_ktp', 'kode_pos_ktp');
    });
    document.getElementById('desa_ktp')?.addEventListener('change', function () {
        applyPostalCode(this, 'kode_pos_ktp');
    });

    document.getElementById('sameAddress')?.addEventListener('change', copyAddressToKTP);
    document.getElementById('gtkWizardForm')?.addEventListener('input', updateProgress);
    document.getElementById('gtkWizardForm')?.addEventListener('change', updateProgress);
}

function resetSelect(id, placeholder) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = '<option value="">' + placeholder + '</option>';
}

function clearKodePos(id) {
    const el = document.getElementById(id);
    if (el) el.value = '';
}

function applyPostalCode(selectEl, kodeposId) {
    const kodeposEl = document.getElementById(kodeposId);
    if (!kodeposEl || !selectEl?.value) { if (kodeposEl) kodeposEl.value = ''; return; }
    const opt        = selectEl.options[selectEl.selectedIndex];
    const postalCode = opt.getAttribute('data-postal-code') || '';
    kodeposEl.value  = postalCode;
    updateProgress();
}

async function loadCities(provinceCode, targetId) {
    if (!provinceCode) return;
    const target = document.getElementById(targetId);
    if (!target) return;
    target.innerHTML = '<option value="">Memuat...</option>';
    target.disabled  = true;
    try {
        const res  = await fetch('/api/wilayah/cities/' + provinceCode, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error(res.status);
        const json = await res.json();
        const list = Array.isArray(json?.data) ? json.data : [];
        let html   = '<option value="">Pilih Kabupaten/Kota</option>';
        list.forEach(function(c) { html += '<option value="' + c.code + '">' + c.name + '</option>'; });
        target.innerHTML = html;
    } catch (err) {
        console.error('loadCities error:', err);
        target.innerHTML = '<option value="">Gagal memuat</option>';
    } finally {
        target.disabled = false;
    }
}

async function loadDistricts(cityCode, targetId) {
    if (!cityCode) return;
    const target = document.getElementById(targetId);
    if (!target) return;
    target.innerHTML = '<option value="">Memuat...</option>';
    target.disabled  = true;
    try {
        const res  = await fetch('/api/wilayah/districts/' + cityCode, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error(res.status);
        const json = await res.json();
        const list = Array.isArray(json?.data) ? json.data : [];
        let html   = '<option value="">Pilih Kecamatan</option>';
        list.forEach(function(d) { html += '<option value="' + d.code + '">' + d.name + '</option>'; });
        target.innerHTML = html;
    } catch (err) {
        console.error('loadDistricts error:', err);
        target.innerHTML = '<option value="">Gagal memuat</option>';
    } finally {
        target.disabled = false;
    }
}

async function loadVillages(districtCode, targetId, kodeposId) {
    if (!districtCode) return;
    const target = document.getElementById(targetId);
    if (!target) return;
    target.innerHTML = '<option value="">Memuat...</option>';
    target.disabled  = true;
    clearKodePos(kodeposId);
    try {
        const res  = await fetch('/api/wilayah/villages/' + districtCode, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error(res.status);
        const json = await res.json();
        const list = Array.isArray(json?.data) ? json.data : [];
        let html   = '<option value="">Pilih Desa</option>';
        list.forEach(function(v) {
            const pos = v.postal_code ?? '';
            html += '<option value="' + v.code + '" data-postal-code="' + pos + '">' + v.name + '</option>';
        });
        target.innerHTML = html;
    } catch (err) {
        console.error('loadVillages error:', err);
        target.innerHTML = '<option value="">Gagal memuat</option>';
    } finally {
        target.disabled = false;
    }
}

async function copyAddressToKTP() {
    const isSame = document.getElementById('sameAddress')?.checked;
    if (!isSame) {
        const elProvKtp = document.getElementById('provinsi_ktp'); if (elProvKtp) elProvKtp.value = '';
        resetSelect('kabupaten_ktp', 'Pilih Kabupaten/Kota');
        resetSelect('kecamatan_ktp', 'Pilih Kecamatan');
        resetSelect('desa_ktp', 'Pilih Desa');
        ['jalan_ktp','rt_rw_ktp','dusun_ktp','kode_pos_ktp'].forEach(function(id) {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        return;
    }
    const provDom = document.getElementById('provinsi_domisili');
    const elProvKtp = document.getElementById('provinsi_ktp'); if (elProvKtp) elProvKtp.value = provDom?.value ?? '';
    if (!provDom?.value) return;

    await loadCities(provDom.value, 'kabupaten_ktp');
    const kabDom = document.getElementById('kabupaten_domisili');
    const kabKtp = document.getElementById('kabupaten_ktp'); if (kabKtp && kabDom) kabKtp.value = kabDom.value;

    await loadDistricts((kabDom?.value) || '', 'kecamatan_ktp');
    const kecDom = document.getElementById('kecamatan_domisili');
    const kecKtp = document.getElementById('kecamatan_ktp'); if (kecKtp && kecDom) kecKtp.value = kecDom.value;

    await loadVillages((kecDom?.value) || '', 'desa_ktp', 'kode_pos_ktp');
    const desaDom = document.getElementById('desa_domisili');
    const desaKtp = document.getElementById('desa_ktp'); if (desaKtp && desaDom) desaKtp.value = desaDom.value;

    const elDesaKtp = document.getElementById('desa_ktp'); if (elDesaKtp) applyPostalCode(elDesaKtp, 'kode_pos_ktp');

    ['jalan','rt_rw','dusun'].forEach(function(f) {
        const src = document.getElementById(f + '_domisili');
        const tgt = document.getElementById(f + '_ktp');
        if (src && tgt) tgt.value = src.value;
    });
    const kpKtp = document.getElementById('kode_pos_ktp');
    if (kpKtp && !kpKtp.value) kpKtp.value = (document.getElementById('kode_pos_domisili')?.value) ?? '';
    updateProgress();
}

/* ==========================================================================
   RIWAYAT PENDIDIKAN — FIX: string concatenation, tidak pakai template literal
   ========================================================================== */
function renderEducationList() {
    const container = document.getElementById('educationContainer');
    const empty     = document.getElementById('educationEmpty');
    if (!educationList.length) {
        container.innerHTML = '';
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';

    container.innerHTML = educationList.map(function(edu, i) {
        const fileInfo = educationFiles[i]?.ijazah
            ? '<span class="badge bg-info">' + escHtml(educationFiles[i].ijazah.name) + '</span>'
            : '<span class="text-muted">Belum diupload</span>';

        const hiddenFields =
            '<input type="hidden" name="pendidikan[' + i + '][jenjang_pendidikan]"     value="' + escAttr(edu.jenjang_pendidikan) + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][nama_satuan_pendidikan]" value="' + escAttr(edu.nama_satuan_pendidikan) + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][jurusan]"                value="' + escAttr(edu.jurusan || '') + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][fakultas]"               value="' + escAttr(edu.fakultas || '') + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][tahun_masuk]"            value="' + escAttr(edu.tahun_masuk || '') + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][tahun_lulus]"            value="' + escAttr(edu.tahun_lulus || '') + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][no_ijazah]"              value="' + escAttr(edu.no_ijazah || '') + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][nama_kepala_sekolah]"    value="' + escAttr(edu.nama_kepala_sekolah || '') + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][nama_rektor]"            value="' + escAttr(edu.nama_rektor || '') + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][nilai_akhir]"            value="' + escAttr(edu.nilai_akhir || '') + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][skala_nilai]"            value="' + escAttr(edu.skala_nilai || '100') + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][status]"                 value="' + escAttr(edu.status || 'LULUS') + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][keterangan]"             value="' + escAttr(edu.keterangan || '') + '">' +
            '<input type="hidden" name="pendidikan[' + i + '][urutan]"                 value="' + (i + 1) + '">';

        return '<div class="card border mb-3" id="edu-card-' + i + '">' +
            '<div class="card-header py-2 d-flex justify-content-between align-items-center">' +
                '<span class="fw-semibold">' +
                    '<i class="ri-graduation-cap-line me-1 text-primary"></i>' +
                    escHtml(edu.jenjang_pendidikan) + ' — ' + escHtml(edu.nama_satuan_pendidikan) +
                '</span>' +
                '<div class="d-flex gap-2">' +
                    '<button type="button" class="btn btn-sm btn-outline-primary" onclick="editEducationRow(' + i + ')">' +
                        '<i class="ri-edit-line"></i>' +
                    '</button>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEducationRow(' + i + ')">' +
                        '<i class="ri-delete-bin-line"></i>' +
                    '</button>' +
                '</div>' +
            '</div>' +
            '<div class="card-body py-2">' +
                '<div class="row g-2 small text-muted">' +
                    '<div class="col-md-4"><strong>Jurusan:</strong> ' + escHtml(edu.jurusan || '-') + '</div>' +
                    '<div class="col-md-4"><strong>Tahun Lulus:</strong> ' + escHtml(edu.tahun_lulus || '-') + '</div>' +
                    '<div class="col-md-4"><strong>Status:</strong> <span class="badge ' + (edu.status === 'LULUS' ? 'bg-success' : 'bg-secondary') + '">' + escHtml(edu.status || '-') + '</span></div>' +
                    '<div class="col-md-4"><strong>Nilai/IPK:</strong> ' + escHtml(edu.nilai_akhir || '-') + '</div>' +
                    '<div class="col-md-4"><strong>No. Ijazah:</strong> ' + escHtml(edu.no_ijazah || '-') + '</div>' +
                    '<div class="col-md-4"><strong>File Ijazah:</strong> ' + fileInfo + '</div>' +
                '</div>' +
                hiddenFields +
            '</div>' +
        '</div>';
    }).join('');
}

function addEducationRow()       { showEducationModal(null); }
function editEducationRow(index) { showEducationModal(index); }

function removeEducationRow(index) {
    Swal.fire({
        icon: 'question',
        title: 'Hapus data pendidikan ini?',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33',
    }).then(function(result) {
        if (result.isConfirmed) {
            educationList.splice(index, 1);
            const newFiles = {};
            Object.keys(educationFiles).forEach(function(k) {
                const ki = parseInt(k);
                if (ki < index)      newFiles[ki]     = educationFiles[k];
                else if (ki > index) newFiles[ki - 1] = educationFiles[k];
            });
            educationFiles = newFiles;
            renderEducationList();
        }
    });
}

function showEducationModal(editIndex) {
    _eduEditIndex    = editIndex;
    const isEdit     = editIndex !== null;
    const edu        = isEdit ? educationList[editIndex] : {};
    const yr         = new Date().getFullYear();

    const jenjangOptions = ['SD','SMP','SMA','SMK','D1','D2','D3','D4','S1','S2','S3','PAKET_B','PAKET_C','PROFESI','SPESIALIS']
        .map(function(j) {
            return '<option value="' + j + '" ' + (edu.jenjang_pendidikan === j ? 'selected' : '') + '>' + j + '</option>';
        }).join('');

    const statusOptions = [['LULUS','Lulus'],['BELUM_LULUS','Belum Lulus'],['DROPOUT','Drop Out'],['PINDAH','Pindah']]
        .map(function(pair) {
            return '<option value="' + pair[0] + '" ' + ((edu.status || 'LULUS') === pair[0] ? 'selected' : '') + '>' + pair[1] + '</option>';
        }).join('');

    const existingIjazah   = isEdit && educationFiles[editIndex]?.ijazah
        ? '<small class="text-success mt-1 d-block"><i class="ri-file-line"></i> ' + escHtml(educationFiles[editIndex].ijazah.name) + '</small>'
        : '';
    const existingTranskrip = isEdit && educationFiles[editIndex]?.transkrip
        ? '<small class="text-success mt-1 d-block"><i class="ri-file-line"></i> ' + escHtml(educationFiles[editIndex].transkrip.name) + '</small>'
        : '';

    Swal.fire({
        title: isEdit ? 'Edit Riwayat Pendidikan' : 'Tambah Riwayat Pendidikan',
        width: '700px',
        html:
            '<div class="row g-3 text-start">' +
                '<div class="col-md-6">' +
                    '<label class="form-label fw-semibold">Jenjang Pendidikan <span class="text-danger">*</span></label>' +
                    '<select class="form-select" id="edu_jenjang" required>' +
                        '<option value="">Pilih...</option>' + jenjangOptions +
                    '</select>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<label class="form-label fw-semibold">Nama Sekolah/Universitas <span class="text-danger">*</span></label>' +
                    '<input class="form-control" id="edu_nama" value="' + escAttr(edu.nama_satuan_pendidikan || '') + '" placeholder="Nama institusi pendidikan">' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<label class="form-label">Jurusan / Program Studi</label>' +
                    '<input class="form-control" id="edu_jurusan" value="' + escAttr(edu.jurusan || '') + '" placeholder="Opsional">' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<label class="form-label">Fakultas</label>' +
                    '<input class="form-control" id="edu_fakultas" value="' + escAttr(edu.fakultas || '') + '" placeholder="Opsional">' +
                '</div>' +
                '<div class="col-md-3">' +
                    '<label class="form-label">Tahun Masuk</label>' +
                    '<input class="form-control" id="edu_masuk" type="number" min="1950" max="' + yr + '" value="' + escAttr(edu.tahun_masuk || '') + '" placeholder="YYYY">' +
                '</div>' +
                '<div class="col-md-3">' +
                    '<label class="form-label">Tahun Lulus <span class="text-danger">*</span></label>' +
                    '<input class="form-control" id="edu_lulus" type="number" min="1950" max="' + (yr + 5) + '" value="' + escAttr(edu.tahun_lulus || '') + '" placeholder="YYYY" required>' +
                '</div>' +
                '<div class="col-md-3">' +
                    '<label class="form-label">Nilai / IPK</label>' +
                    '<input class="form-control" id="edu_nilai" type="number" step="0.01" min="0" max="100" value="' + escAttr(edu.nilai_akhir || '') + '" placeholder="0.00">' +
                '</div>' +
                '<div class="col-md-3">' +
                    '<label class="form-label">Skala Nilai</label>' +
                    '<select class="form-select" id="edu_skala">' +
                        '<option value="100" ' + ((!edu.skala_nilai || edu.skala_nilai == '100') ? 'selected' : '') + '>0–100</option>' +
                        '<option value="4" ' + (edu.skala_nilai == '4' ? 'selected' : '') + '>0–4 (IPK)</option>' +
                    '</select>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<label class="form-label">Nomor Ijazah</label>' +
                    '<input class="form-control" id="edu_no_ijazah" value="' + escAttr(edu.no_ijazah || '') + '" placeholder="Opsional">' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<label class="form-label">Status</label>' +
                    '<select class="form-select" id="edu_status">' + statusOptions + '</select>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<label class="form-label">Nama Kepala Sekolah / Rektor</label>' +
                    '<input class="form-control" id="edu_kepala" value="' + escAttr(edu.nama_kepala_sekolah || edu.nama_rektor || '') + '" placeholder="Opsional">' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<label class="form-label">Keterangan</label>' +
                    '<input class="form-control" id="edu_keterangan" value="' + escAttr(edu.keterangan || '') + '" placeholder="Opsional">' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<label class="form-label">Upload Ijazah <small class="text-muted">(PDF/JPG/PNG, maks 2MB)</small></label>' +
                    '<input class="form-control" id="edu_ijazah_file" type="file" accept=".pdf,.jpg,.jpeg,.png">' +
                    existingIjazah +
                '</div>' +
                '<div class="col-md-6">' +
                    '<label class="form-label">Upload Transkrip <small class="text-muted">(PDF/JPG/PNG, maks 2MB)</small></label>' +
                    '<input class="form-control" id="edu_transkrip_file" type="file" accept=".pdf,.jpg,.jpeg,.png">' +
                    existingTranskrip +
                '</div>' +
            '</div>',
        showCancelButton: true,
        confirmButtonText: isEdit ? 'Perbarui' : 'Tambahkan',
        cancelButtonText: 'Batal',
        focusConfirm: false,
        preConfirm: function() {
            const jenjang = document.getElementById('edu_jenjang').value;
            const nama    = document.getElementById('edu_nama').value.trim();
            const lulus   = document.getElementById('edu_lulus').value;
            if (!jenjang || !nama || !lulus) {
                Swal.showValidationMessage('Jenjang, Nama Institusi, dan Tahun Lulus wajib diisi.');
                return false;
            }
            return {
                jenjang_pendidikan:     jenjang,
                nama_satuan_pendidikan: nama,
                jurusan:                document.getElementById('edu_jurusan').value.trim(),
                fakultas:               document.getElementById('edu_fakultas').value.trim(),
                tahun_masuk:            document.getElementById('edu_masuk').value,
                tahun_lulus:            lulus,
                nilai_akhir:            document.getElementById('edu_nilai').value,
                skala_nilai:            document.getElementById('edu_skala').value,
                no_ijazah:              document.getElementById('edu_no_ijazah').value.trim(),
                status:                 document.getElementById('edu_status').value,
                nama_kepala_sekolah:    document.getElementById('edu_kepala').value.trim(),
                nama_rektor:            document.getElementById('edu_kepala').value.trim(),
                keterangan:             document.getElementById('edu_keterangan').value.trim(),
                _ijazah:    document.getElementById('edu_ijazah_file')?.files[0] ?? null,
                _transkrip: document.getElementById('edu_transkrip_file')?.files[0] ?? null,
            };
        }
    }).then(function(result) {
        if (!result.isConfirmed) return;
        const data   = result.value;
        const idx    = _eduEditIndex !== null ? _eduEditIndex : educationList.length;
        if (!educationFiles[idx]) educationFiles[idx] = {};
        if (data._ijazah)    educationFiles[idx].ijazah    = data._ijazah;
        if (data._transkrip) educationFiles[idx].transkrip = data._transkrip;
        delete data._ijazah;
        delete data._transkrip;
        if (isEdit) educationList[idx] = data;
        else educationList.push(data);
        _eduEditIndex = null;
        renderEducationList();
    });
}

/* ==========================================================================
   DATA KELUARGA — FIX: string concatenation, tidak pakai template literal
   ========================================================================== */
function renderFamilyList() {
    const container = document.getElementById('familyMembersContainer');
    const empty     = document.getElementById('familyEmpty');
    if (!familyList.length) {
        container.innerHTML = '';
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';

    const relLabel = { suami:'Suami', istri:'Istri', anak:'Anak', ayah:'Ayah', ibu:'Ibu' };
    const jkLabel  = { L: 'Laki-laki', P: 'Perempuan' };

    container.innerHTML = familyList.map(function(m, i) {
        const gtkBadge  = m.gtk_id ? ' <span class="badge bg-success ms-1">GTK</span>' : '';
        const hiddenFields =
            (m.id ? '<input type="hidden" name="anggota_keluarga[' + i + '][id]" value="' + escAttr(m.id) + '">' : '') +
            '<input type="hidden" name="anggota_keluarga[' + i + '][relationship]"        value="' + escAttr(m.relationship || '') + '">' +
            '<input type="hidden" name="anggota_keluarga[' + i + '][nama]"                value="' + escAttr(m.nama || '') + '">' +
            '<input type="hidden" name="anggota_keluarga[' + i + '][jenis_kelamin]"       value="' + escAttr(m.jenis_kelamin || '') + '">' +
            '<input type="hidden" name="anggota_keluarga[' + i + '][tempat_lahir]"        value="' + escAttr(m.tempat_lahir || '') + '">' +
            '<input type="hidden" name="anggota_keluarga[' + i + '][tanggal_lahir]"       value="' + escAttr(m.tanggal_lahir || '') + '">' +
            '<input type="hidden" name="anggota_keluarga[' + i + '][pekerjaan]"           value="' + escAttr(m.pekerjaan || '') + '">' +
            '<input type="hidden" name="anggota_keluarga[' + i + '][pendidikan_terakhir]" value="' + escAttr(m.pendidikan_terakhir || '') + '">' +
            '<input type="hidden" name="anggota_keluarga[' + i + '][alamat]"              value="' + escAttr(m.alamat || '') + '">' +
            (m.gtk_id ? '<input type="hidden" name="anggota_keluarga[' + i + '][gtk_id]" value="' + escAttr(m.gtk_id) + '">' : '');

        return '<div class="card border mb-2" id="fam-card-' + i + '">' +
            '<div class="card-body py-2 d-flex justify-content-between align-items-center">' +
                '<div>' +
                    '<span class="badge bg-primary me-2">' + escHtml(relLabel[m.relationship] || m.relationship) + '</span>' +
                    '<strong>' + escHtml(m.nama) + '</strong>' +
                    '<span class="text-muted small ms-2">' + escHtml(jkLabel[m.jenis_kelamin] || '-') + gtkBadge + '</span>' +
                '</div>' +
                '<div class="d-flex gap-2">' +
                    '<button type="button" class="btn btn-sm btn-outline-primary" onclick="editFamilyMember(' + i + ')">' +
                        '<i class="ri-edit-line"></i>' +
                    '</button>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFamilyMember(' + i + ')">' +
                        '<i class="ri-delete-bin-line"></i>' +
                    '</button>' +
                '</div>' +
            '</div>' +
            '<div style="display:none">' + hiddenFields + '</div>' +
        '</div>';
    }).join('');
}

function showAddFamilyModal(editIndex) {
    editIndex = (editIndex !== undefined && editIndex !== null) ? editIndex : null;
    const isEdit = editIndex !== null;
    const m      = isEdit ? familyList[editIndex] : {};

    const modal   = document.getElementById('familyMemberModal');
    const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
    const editIdxEl = document.getElementById('fm_edit_index');

    document.getElementById('familyMemberModalLabel').textContent =
        isEdit ? 'Edit Anggota Keluarga' : 'Tambah Anggota Keluarga';

    editIdxEl.value = (isEdit ? editIndex : '');
    delete editIdxEl.dataset.gtkId;

    document.getElementById('fm_relationship').value       = m.relationship || '';
    document.getElementById('fm_nama').value                = m.nama || '';
    document.getElementById('fm_jenis_kelamin').value       = m.jenis_kelamin || '';
    document.getElementById('fm_tempat_lahir').value        = m.tempat_lahir || '';
    document.getElementById('fm_tanggal_lahir').value       = m.tanggal_lahir || '';
    document.getElementById('fm_pekerjaan').value           = m.pekerjaan || '';
    document.getElementById('fm_pendidikan_terakhir').value = m.pendidikan_terakhir || '';
    document.getElementById('fm_alamat').value              = m.alamat || '';

    bsModal.show();
}

function editFamilyMember(index) { showAddFamilyModal(index); }

function saveFamilyMember() {
    const rel  = document.getElementById('fm_relationship').value;
    const nama = document.getElementById('fm_nama').value.trim();
    const jk   = document.getElementById('fm_jenis_kelamin').value;
    if (!rel || !nama || !jk) {
        Swal.fire({ icon: 'warning', title: 'Lengkapi data', text: 'Hubungan, Nama, dan Jenis Kelamin wajib diisi.' });
        return;
    }
    const editIdxEl       = document.getElementById('fm_edit_index');
    const gtkIdFromSearch = editIdxEl.dataset.gtkId || null;
    delete editIdxEl.dataset.gtkId;

    const data = {
        relationship:        rel,
        nama:                nama,
        jenis_kelamin:       jk,
        tempat_lahir:        document.getElementById('fm_tempat_lahir').value.trim(),
        tanggal_lahir:       document.getElementById('fm_tanggal_lahir').value,
        pekerjaan:           document.getElementById('fm_pekerjaan').value.trim(),
        pendidikan_terakhir: document.getElementById('fm_pendidikan_terakhir').value,
        alamat:              document.getElementById('fm_alamat').value.trim(),
        gtk_id:              gtkIdFromSearch,
    };

    const editIdx = editIdxEl.value;
    if (editIdx !== '') {
        const idx = parseInt(editIdx);
        data.gtk_id = data.gtk_id ?? familyList[idx]?.gtk_id ?? null;
        if (familyList[idx]?.id) data.id = familyList[idx].id;
        familyList[idx] = Object.assign({}, familyList[idx], data);
    } else {
        familyList.push(data);
    }

    bootstrap.Modal.getInstance(document.getElementById('familyMemberModal'))?.hide();
    renderFamilyList();
}

function removeFamilyMember(index) {
    Swal.fire({
        icon: 'question',
        title: 'Hapus anggota keluarga ini?',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33'
    }).then(function(r) {
        if (r.isConfirmed) { familyList.splice(index, 1); renderFamilyList(); }
    });
}

/* ==========================================================================
   PENCARIAN PASANGAN GTK
   ========================================================================== */
function setupSpouseSearch() {
    const input = document.getElementById('spouseSearchInput');
    const btn   = document.getElementById('spouseSearchBtn');
    if (!input) return;

    input.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 2) { hideSpouseResults(); return; }
        searchTimeout = setTimeout(function() { searchGtk(q); }, 400);
    });
    btn.addEventListener('click', function () {
        const q = document.getElementById('spouseSearchInput').value.trim();
        if (q.length >= 2) searchGtk(q);
    });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const q = this.value.trim();
            if (q.length >= 2) searchGtk(q);
        }
    });
    document.addEventListener('click', function (e) {
        if (!document.getElementById('spouseSearchCard')?.contains(e.target)) hideSpouseResults();
    });
}

async function searchGtk(query) {
    const resultBox  = document.getElementById('spouseSearchResults');
    const resultList = document.getElementById('spouseResultList');
    resultList.innerHTML = '<div class="list-group-item text-muted py-2"><i class="ri-loader-4-line me-1"></i> Mencari...</div>';
    resultBox.style.display = 'block';
    try {
        const res  = await fetch('{{ route("user.gtk.index", ["userId" => $userId]) }}?search=' + encodeURIComponent(query) + '&format=json', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        const list = json.data?.data ?? json.data ?? json ?? [];
        if (!list.length) {
            resultList.innerHTML = '<div class="list-group-item text-muted py-2">Tidak ada hasil ditemukan.</div>';
            return;
        }
        resultList.innerHTML = list.slice(0, 8).map(function(gtk) {
            return '<button type="button" class="list-group-item list-group-item-action py-2"' +
                ' onclick=\'selectSpouseGtk(' + JSON.stringify(gtk).replace(/'/g, "\\'") + ')\'>' +
                '<div class="d-flex justify-content-between align-items-center">' +
                    '<div>' +
                        '<strong>' + escHtml(gtk.name || gtk.nama || '') + '</strong>' +
                        '<small class="text-muted ms-2">' + escHtml(gtk.employment?.nupy ?? gtk.nupy ?? '') + '</small>' +
                    '</div>' +
                    '<small class="text-muted">' + escHtml(gtk.email || '') + '</small>' +
                '</div>' +
                '</button>';
        }).join('');
    } catch (err) {
        console.error('searchGtk error:', err);
        resultList.innerHTML = '<div class="list-group-item text-danger py-2">Gagal melakukan pencarian.</div>';
    }
}

function selectSpouseGtk(gtk) {
    _spouseGtkId = gtk.id;
    hideSpouseResults();
    document.getElementById('spouseSearchInput').value = '';

    const jk   = gtk.gtk_profile?.jenis_kelamin ?? gtk.jenis_kelamin ?? '';
    const nama = gtk.name || gtk.nama || '';
    const rel  = jk === 'L' ? 'suami' : (jk === 'P' ? 'istri' : '');

    const existingIdx = familyList.findIndex(function(m) { return m.gtk_id === gtk.id; });
    if (existingIdx !== -1) { showAddFamilyModal(existingIdx); return; }

    const editIdxEl = document.getElementById('fm_edit_index');
    document.getElementById('familyMemberModalLabel').textContent = 'Tambah Anggota Keluarga (dari GTK)';
    editIdxEl.value = '';
    delete editIdxEl.dataset.gtkId;

    document.getElementById('fm_relationship').value       = rel;
    document.getElementById('fm_nama').value                = nama;
    document.getElementById('fm_jenis_kelamin').value       = jk;
    document.getElementById('fm_tempat_lahir').value        = gtk.gtk_profile?.tempat_lahir ?? '';
    document.getElementById('fm_tanggal_lahir').value       = gtk.gtk_profile?.tanggal_lahir ?? '';
    document.getElementById('fm_pekerjaan').value           = gtk.employment?.jabatan ?? '';
    document.getElementById('fm_pendidikan_terakhir').value = '';
    document.getElementById('fm_alamat').value              = '';

    editIdxEl.dataset.gtkId = gtk.id;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('familyMemberModal')).show();
}

function clearSpouseSelection() {
    familyList = familyList.filter(function(m) { return m.gtk_id !== _spouseGtkId; });
    _spouseGtkId = null;
    document.getElementById('spouseSelectedPreview').style.display = 'none';
    document.getElementById('spouseSearchInput').value = '';
    renderFamilyList();
}

function hideSpouseResults() {
    const box = document.getElementById('spouseSearchResults');
    if (box) box.style.display = 'none';
}

/* ==========================================================================
   REVIEW DATA
   ========================================================================== */
function showReviewData() {
    const v  = function(id) { return document.getElementById(id)?.value ?? ''; };
    const sv = function(id) {
        const el = document.getElementById(id);
        return el ? (el.options[el.selectedIndex]?.text ?? '-') : '-';
    };
    const vn = function(name) { return document.querySelector('[name="' + name + '"]')?.value ?? ''; };
    const sn = function(name) {
        const el = document.querySelector('[name="' + name + '"]');
        return el ? (el.options[el.selectedIndex]?.text ?? '-') : '-';
    };
    const addr = function(prefix) {
        const parts = [vn(prefix + '[jalan]'), vn(prefix + '[rt_rw]'), vn(prefix + '[dusun]')].filter(Boolean);
        return parts.length ? parts.join(', ') : '-';
    };

    const nupy = v('nupy') || vn('kepegawaian[nupy]');
    if (nupy) {
        const rp = document.getElementById('reviewPassword');
        if (rp) rp.textContent = nupy + '@12345';
    }

    const relLabel = { suami:'Suami', istri:'Istri', anak:'Anak', ayah:'Ayah', ibu:'Ibu' };
    const jkLabel  = { L:'Laki-laki', P:'Perempuan' };

    const eduSummary = educationList.length
        ? '<ul class="mb-0 ps-3">' + educationList.map(function(e) {
            return '<li><strong>' + escHtml(e.jenjang_pendidikan) + '</strong> — ' +
                escHtml(e.nama_satuan_pendidikan) + ' (' + escHtml(e.tahun_lulus || '-') + ')' +
                (e.jurusan ? ' | ' + escHtml(e.jurusan) : '') + '</li>';
          }).join('') + '</ul>'
        : '<span class="text-muted">-</span>';

    const famSummary = familyList.length
        ? '<ul class="mb-0 ps-3">' + familyList.map(function(m) {
            return '<li><strong>' + escHtml(relLabel[m.relationship] || m.relationship || '-') + '</strong> — ' +
                escHtml(m.nama) + ' (' + escHtml(jkLabel[m.jenis_kelamin] || '-') + ')' +
                (m.pekerjaan ? ' | ' + escHtml(m.pekerjaan) : '') +
                (m.gtk_id ? ' <span class="badge bg-success">GTK</span>' : '') + '</li>';
          }).join('') + '</ul>'
        : '<span class="text-muted">-</span>';

    const nikVal    = v('nik');
    const nikMasked = nikVal ? nikVal.substring(0,6) + '****' + nikVal.substring(12) : '-';

    document.getElementById('reviewData').innerHTML =
        '<div class="table-responsive">' +
        '<table class="table table-bordered table-sm">' +
        '<tbody>' +
            '<tr class="table-light"><th colspan="2" class="text-center">Data Pribadi</th></tr>' +
            '<tr><th width="35%">Nama Lengkap</th><td>' + escHtml(v('name')) + '</td></tr>' +
            '<tr><th>NIK</th><td>' + escHtml(nikMasked) + '</td></tr>' +
            '<tr><th>No. KK</th><td>' + escHtml(v('no_kk') || '-') + '</td></tr>' +
            '<tr><th>Tempat/Tgl Lahir</th><td>' + escHtml(v('tempat_lahir')) + ' / ' + escHtml(v('tanggal_lahir') ? formatDate(v('tanggal_lahir')) : '-') + '</td></tr>' +
            '<tr><th>Jenis Kelamin</th><td>' + (v('jenis_kelamin') === 'L' ? 'Laki-laki' : (v('jenis_kelamin') === 'P' ? 'Perempuan' : '-')) + '</td></tr>' +
            '<tr><th>Gol. Darah</th><td>' + escHtml(v('golongan_darah') || '-') + '</td></tr>' +
            '<tr><th>Agama</th><td>' + escHtml(sv('agama')) + '</td></tr>' +
            '<tr><th>Status Perkawinan</th><td>' + escHtml(sv('status_perkawinan')) + '</td></tr>' +
            '<tr><th>Nama Ibu Kandung</th><td>' + escHtml(v('nama_ibu_kandung') || '-') + '</td></tr>' +
            '<tr><th>NPWP</th><td>' + escHtml(v('npwp') || '-') + '</td></tr>' +

            '<tr class="table-light"><th colspan="2" class="text-center">Alamat</th></tr>' +
            '<tr><th>Domisili</th><td>' +
                addr('alamat_domisili') + ', ' +
                escHtml(sn('alamat_domisili[village_code]')) + ', ' +
                escHtml(sn('alamat_domisili[district_code]')) + ', ' +
                escHtml(sn('alamat_domisili[city_code]')) + ', ' +
                escHtml(sn('alamat_domisili[province_code]')) +
                (vn('alamat_domisili[kode_pos]') ? ' ' + escHtml(vn('alamat_domisili[kode_pos]')) : '') +
            '</td></tr>' +
            '<tr><th>Alamat KTP</th><td>' + (vn('alamat_ktp[jalan]')
                ? addr('alamat_ktp') + ', ' +
                  escHtml(sn('alamat_ktp[village_code]')) + ', ' +
                  escHtml(sn('alamat_ktp[district_code]')) + ', ' +
                  escHtml(sn('alamat_ktp[city_code]')) + ', ' +
                  escHtml(sn('alamat_ktp[province_code]'))
                : '<span class="text-muted">Sama dengan domisili</span>') + '</td></tr>' +

            '<tr class="table-light"><th colspan="2" class="text-center">Kontak</th></tr>' +
            '<tr><th>No. HP</th><td>' + escHtml(vn('kontak[no_hp]') || '-') + '</td></tr>' +
            '<tr><th>No. WhatsApp</th><td>' + escHtml(vn('kontak[no_whatsapp]') || '-') + '</td></tr>' +
            '<tr><th>Kontak Darurat</th><td>' + escHtml(vn('kontak[kontak_darurat]') || '-') + '</td></tr>' +
            '<tr><th>Instagram</th><td>' + escHtml(vn('kontak[instagram]') || '-') + '</td></tr>' +
            '<tr><th>Facebook</th><td>' + escHtml(vn('kontak[facebook]') || '-') + '</td></tr>' +

            '<tr class="table-light"><th colspan="2" class="text-center">Kepegawaian</th></tr>' +
            '<tr><th>NUPY</th><td>' + escHtml(nupy) + '</td></tr>' +
            '<tr><th>Jenis GTK</th><td>' + escHtml(sv('jenis_gtk')) + '</td></tr>' +
            '<tr><th>Jabatan</th><td>' + escHtml(sv('jabatan')) + '</td></tr>' +
            '<tr><th>Status Kepegawaian</th><td>' + escHtml(sv('status_kepegawaian')) + '</td></tr>' +
            '<tr><th>Unit Kerja</th><td>' + escHtml(sv('work_unit_id')) + '</td></tr>' +
            '<tr><th>TMT</th><td>' + escHtml(v('tmt') ? formatDate(v('tmt')) : '-') + '</td></tr>' +
            '<tr><th>No. SK</th><td>' + escHtml(vn('kepegawaian[nomor_sk]') || '-') + '</td></tr>' +
            '<tr><th>Tanggal SK</th><td>' + escHtml(vn('kepegawaian[tanggal_sk]') ? formatDate(vn('kepegawaian[tanggal_sk]')) : '-') + '</td></tr>' +

            '<tr class="table-light"><th colspan="2" class="text-center">Riwayat Pendidikan (' + educationList.length + ')</th></tr>' +
            '<tr><td colspan="2">' + eduSummary + '</td></tr>' +

            '<tr class="table-light"><th colspan="2" class="text-center">Anggota Keluarga (' + familyList.length + ')</th></tr>' +
            '<tr><td colspan="2">' + famSummary + '</td></tr>' +
        '</tbody>' +
        '</table>' +
        '</div>';
}

/* ==========================================================================
   SUBMIT FORM
   ========================================================================== */
async function submitForm() {
    const allRequired = document.querySelectorAll('#gtkWizardForm [required]');
    let valid = true;
    let firstInvalid = null;
    allRequired.forEach(function(f) {
        f.classList.remove('is-invalid');
        let ok = !!f.value.trim();
        if (ok && f.name === 'nik')   ok = /^\d{16}$/.test(f.value);
        if (ok && f.name === 'email') ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.value);
        if (!ok) { f.classList.add('is-invalid'); valid = false; if (!firstInvalid) firstInvalid = f; }
    });
    if (!valid) {
        Swal.fire({ icon: 'error', title: 'Data Belum Lengkap', text: 'Harap lengkapi semua field wajib.' });
        firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    const btn = document.getElementById('submitButton');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...';

    try {
        const formData = new FormData(document.getElementById('gtkWizardForm'));
        Object.entries(educationFiles).forEach(function([idx, files]) {
            if (files.ijazah)    formData.append('pendidikan_files[' + idx + '][ijazah]',    files.ijazah,    files.ijazah.name);
            if (files.transkrip) formData.append('pendidikan_files[' + idx + '][transkrip]', files.transkrip, files.transkrip.name);
        });

        const response = await fetch('{!! route('user.gtk.store', ['userId' => $userId]) !!}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData,
        });

        const result = await response.json();
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-save-line label-icon align-middle fs-16 me-2"></i> Simpan Data GTK';

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                html: '<div class="text-start">' +
                    '<p class="mb-1"><strong>Nama:</strong> ' + escHtml(result.data?.name ?? '') + '</p>' +
                    '<p class="mb-1"><strong>Email:</strong> ' + escHtml(result.data?.email ?? '') + '</p>' +
                    '<p class="mb-0"><strong>Password:</strong> <code>' + escHtml(result.data?.password ?? (document.getElementById('nupy')?.value + '@12345')) + '</code></p>' +
                    '</div>',
                confirmButtonText: 'OK'
            }).then(function() {
                window.location.href = '{!! route('user.gtk.index', ['userId' => $userId]) !!}';
            });
        } else {
            if (result.errors) {
                let html = 'Terjadi kesalahan validasi:<br><ul class="text-start">';
                Object.values(result.errors).forEach(function(e) { html += '<li>' + escHtml(e[0]) + '</li>'; });
                html += '</ul>';
                Swal.fire({ icon: 'error', title: 'Validasi Gagal', html: html });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    html: escHtml(result.message) + (result.exception ? '<br><small class="text-muted">' + escHtml(result.exception) + '</small>' : '')
                });
            }
        }
    } catch (err) {
        console.error('submitForm error:', err);
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-save-line label-icon align-middle fs-16 me-2"></i> Simpan Data GTK';
        Swal.fire({ icon: 'error', title: 'Error Jaringan!', text: 'Silakan coba lagi.' });
    }
}

/* ==========================================================================
   SYNC PASSWORD PREVIEW
   ========================================================================== */
function syncNupyPassword(val) {
    const el = document.getElementById('reviewPassword');
    if (el) el.textContent = val ? val + '@12345' : '-';
}

/* ==========================================================================
   UTILITIES
   ========================================================================== */
function escHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
function escAttr(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}
</script>
@endsection