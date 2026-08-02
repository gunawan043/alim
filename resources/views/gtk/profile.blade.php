@extends('layouts.master')
@section('title')
    Detail GTK - {{ $gtk->name }}
@endsection
@php $userId = $userId ?? request()->route('userId') ?? Auth::id(); @endphp
@section('css')
    <link rel="stylesheet" href="{{ URL::asset('build/libs/swiper/swiper-bundle.min.css') }}">
    <style>
        .info-badge { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
        .profile-stat { background: rgba(var(--bs-body-bg-rgb), 0.1); border-radius: 8px; padding: 1rem; transition: all 0.3s ease; }
        .profile-stat:hover { background: rgba(var(--bs-body-bg-rgb), 0.15); transform: translateY(-2px); }
        .detail-label { font-weight: 600; color: var(--bs-secondary-color); min-width: 180px; }
        .detail-value { color: var(--bs-body-color); }
        .icon-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; background: var(--bs-tertiary-bg); }
        .contact-item { border-bottom: 1px solid var(--bs-border-color); padding: 12px 0; }
        .contact-item:last-child { border-bottom: none; }
        .address-card { border-left: 4px solid var(--bs-primary); background: var(--bs-tertiary-bg); }
        .address-card.ktp { border-left-color: var(--bs-success); }
        .timeline-item { position: relative; padding-left: 30px; margin-bottom: 20px; }
        .timeline-item:before { content: ''; position: absolute; left: 0; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--bs-primary); }
        .private-data { border-radius: 4px; padding: 4px 8px; background: var(--bs-tertiary-bg); color: var(--bs-body-color); font-family: monospace; }
        .profile-wrapper { background: linear-gradient(to right, rgba(var(--bs-primary-rgb), 0.9), rgba(var(--bs-success-rgb), 0.7)); border-radius: 12px; padding: 20px; margin-top: -30px; position: relative; z-index: 10; }
        .family-member-card, .education-card { transition: all 0.3s ease; border: 1px solid var(--bs-border-color); }
        .family-member-card:hover, .education-card:hover { transform: translateY(-5px); box-shadow: var(--bs-box-shadow); }
        [data-bs-theme="dark"] .profile-wrapper { background: linear-gradient(to right, rgba(13, 110, 253, 0.8), rgba(25, 135, 84, 0.6)); }
    </style>
@endsection

@section('content')
    <!-- MODAL VERIFIKASI PASSWORD -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">
                        <i class="ri-shield-keyhole-line me-2"></i>Verifikasi Identitas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="passwordForm">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="ri-alarm-warning-line me-2"></i>
                            Data NIK dan No. KK bersifat rahasia. Masukkan password Anda untuk mengakses informasi ini.
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required
                                placeholder="Masukkan password Anda">
                            <div class="form-text">Password akun Anda diperlukan untuk memverifikasi identitas.</div>
                        </div>
                        <div id="passwordError" class="alert alert-danger d-none">
                            <i class="ri-error-warning-line me-2"></i>
                            <span id="errorMessage"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="submitPassword">
                            <i class="ri-check-line me-1"></i> Verifikasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DATA IDENTITAS -->
    <div class="modal fade" id="identityModal" tabindex="-1" aria-labelledby="identityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="identityModalLabel">
                        <i class="ri-id-card-line me-2"></i>Data Identitas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ri-information-line me-2"></i>
                        Data ini akan hilang dalam <span id="countdown">60</span> detik.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body text-center">
                                    <div class="mb-2"><i class="ri-id-card-line fs-2 text-primary"></i></div>
                                    <h6 class="card-title mb-1">NIK</h6>
                                    <div class="card-text"><code class="fs-5" id="nikData"></code></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body text-center">
                                    <div class="mb-2"><i class="ri-home-4-line fs-2 text-success"></i></div>
                                    <h6 class="card-title mb-1">No. KK</h6>
                                    <div class="card-text"><code class="fs-5" id="kkData"></code></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-outline-primary btn-sm" onclick="copyToClipboard('nikData', 'NIK')">
                            <i class="ri-file-copy-line me-1"></i> Salin NIK
                        </button>
                        <button class="btn btn-outline-success btn-sm ms-2" onclick="copyToClipboard('kkData', 'No. KK')">
                            <i class="ri-file-copy-line me-1"></i> Salin No. KK
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH / EDIT ANGGOTA KELUARGA -->
    <div class="modal fade" id="familyMemberModal" tabindex="-1" aria-labelledby="familyMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="familyMemberModalLabel">Tambah Anggota Keluarga</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="fm_edit_id" value="">
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
                            <textarea class="form-control" id="fm_alamat" rows="2" placeholder="Alamat lengkap anggota keluarga"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="fmSave()">
                        <i class="ri-save-line me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
        $domisiliAddress = $gtk->gtkProfile?->addresses->where('type', 'domisili')->first();
        $ktpAddress      = $gtk->gtkProfile?->addresses->where('type', 'ktp')->first();

        $provinsiDomisili  = $domisiliAddress?->province->name ?? ($domisiliAddress?->provinsi ?? '-');
        $kabKotaDomisili   = $domisiliAddress?->city->name    ?? ($domisiliAddress?->kab_kota  ?? '-');
        $kecamatanDomisili = $domisiliAddress?->district->name ?? ($domisiliAddress?->kecamatan ?? '-');
        $desaDomisili      = $domisiliAddress?->village->name  ?? ($domisiliAddress?->desa      ?? '-');

        $provinsiKtp  = $ktpAddress?->province->name  ?? ($ktpAddress?->provinsi  ?? '-');
        $kabKotaKtp   = $ktpAddress?->city->name      ?? ($ktpAddress?->kab_kota  ?? '-');
        $kecamatanKtp = $ktpAddress?->district->name  ?? ($ktpAddress?->kecamatan ?? '-');
        $desaKtp      = $ktpAddress?->village->name   ?? ($ktpAddress?->desa      ?? '-');

        $masked_nik   = $gtk->gtkProfile?->masked_nik   ?? str_repeat('•', 16);
        $masked_no_kk = $gtk->gtkProfile?->masked_no_kk ?? str_repeat('•', 16);
    @endphp

    <!-- Header Profile -->
    <div class="profile-foreground position-relative mx-n4 mt-n4">
        <div class="profile-wid-bg">
            <img src="{{ URL::asset('build/images/alim-one-bg.png') }}" alt="Background Profile" class="profile-wid-img" />
            <div class="overlay-content position-absolute bottom-0 start-0 p-4 text-white">
                <h4 class="mb-1">{{ $gtk->name }}</h4>
                <p class="mb-0 opacity-75">
                    {{ $gtk->employment?->jabatan ?? 'GTK' }}
                    @if ($gtk->employment?->workUnit) • {{ $gtk->employment->workUnit->name }} @endif
                </p>
                @php $gtkRoles = $gtk->getRoleNames(); @endphp
                @if($gtkRoles->count())
                    <div class="mt-2">
                        @foreach($gtkRoles as $roleName)
                            <span class="badge bg-light text-dark me-1">{{ $roleName }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Profile Stats -->
    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper">
        <div class="row g-4">
            <div class="col-auto">
                <div class="avatar-lg position-relative">
                    <img src="@if ($gtk->avatar != '') {{ URL::asset('images/' . $gtk->avatar) }} @else {{ URL::asset('build/images/users/avatar-1.jpg') }} @endif"
                        alt="Foto Profil {{ $gtk->name }}" class="img-thumbnail rounded-circle shadow" />
                    <span class="position-absolute bottom-0 end-0 badge rounded-circle p-0 border border-3 border-white bg-{{ $gtk->is_active ? 'success' : 'danger' }}">
                        <i class="ri-circle-fill fs-24"></i>
                    </span>
                </div>
            </div>
            <div class="col">
                <div class="p-2">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h3 class="text-white mb-0">{{ $gtk->name }}</h3>
                        <span class="badge bg-{{ $gtk->is_active ? 'success' : 'danger' }}-subtle text-{{ $gtk->is_active ? 'success' : 'danger' }}">
                            <i class="ri-user-{{ $gtk->is_active ? 'follow' : 'unfollow' }}-line me-1"></i>
                            {{ $gtk->is_active ? 'AKTIF' : 'NON-AKTIF' }}
                        </span>
                        @if ($gtk->employment)
                            <span class="badge bg-info-subtle text-info">
                                <i class="ri-briefcase-line me-1"></i>{{ $gtk->employment->status_kepegawaian }}
                            </span>
                        @endif
                    </div>
                    <p class="text-white text-opacity-90 mb-3">
                        <i class="ri-briefcase-line align-middle me-1"></i>
                        {{ $gtk->employment?->jabatan ?? 'Jabatan belum diisi' }}
                        @if ($gtk->employment?->workUnit)
                            <span class="text-white-75">• {{ $gtk->employment->workUnit->name }}</span>
                        @endif
                    </p>
                    <div class="text-white d-flex flex-wrap gap-3 text-white-75">
                        @if ($domisiliAddress)
                            <div class="d-flex align-items-center">
                                <i class="ri-map-pin-2-line me-2"></i>
                                <span>{{ $kecamatanDomisili }}, {{ $kabKotaDomisili }}</span>
                            </div>
                        @endif
                        @if ($gtk->employment?->workUnit)
                            <div class="d-flex align-items-center">
                                <i class="ri-building-2-line me-2"></i>
                                <span>{{ $gtk->employment->workUnit->name }}</span>
                            </div>
                        @endif
                        @if ($gtk->gtkContact?->no_hp)
                            <div class="d-flex align-items-center">
                                <i class="ri-phone-line me-2"></i>
                                <span>{{ $gtk->gtkContact->no_hp }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <!-- Navigation Tabs -->
                    <div class="d-flex flex-column flex-md-row align-items-md-center border-bottom p-3">
                        <ul class="nav nav-pills gap-2 gap-lg-3 flex-grow-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link fs-14 active" data-bs-toggle="tab" href="#overview-tab" role="tab">
                                    <i class="ri-user-line me-1"></i> Data Pribadi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#kepegawaian" role="tab">
                                    <i class="ri-briefcase-line me-1"></i> Kepegawaian
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#pendidikan" role="tab">
                                    <i class="ri-graduation-cap-line me-1"></i> Pendidikan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#keluarga" role="tab">
                                    <i class="ri-group-line me-1"></i> Keluarga
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#data-kesehatan" role="tab">
                                    <i class="ri-heart-pulse-line me-1"></i> Data Kesehatan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#dokumen" role="tab">
                                    <i class="ri-folder-line me-1"></i> Dokumen
                                </a>
                            </li>
                        </ul>
                        <div class="d-flex gap-2 mt-3 mt-md-0">
                            <a href="{{ route('user.gtk.edit', ['userId' => $userId, 'uuid' => $gtk->id]) }}" class="btn btn-success">
                                <i class="ri-edit-box-line align-middle me-1"></i> Edit Data
                            </a>
                            <a href="{{ route('user.profile.cv', ['userId' => $userId, 'uuid' => $gtk->id]) }}"
                               class="btn btn-primary"
                               target="_blank">
                                <i class="ri-file-pdf-2-line align-middle me-1"></i> Unduh CV
                            </a>
                            <a href="{{ route('user.gtk.index', ['userId' => $userId]) }}" class="btn btn-light">
                                <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                            </a>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content p-4">

                        {{-- ============================================================
                             TAB 1: DATA PRIBADI
                        ============================================================ --}}
                        <div class="tab-pane active" id="overview-tab" role="tabpanel">
                            <div class="row">
                                <div class="col-xxl-4">
                                    <!-- Status Card -->
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3 d-flex align-items-center">
                                                <i class="ri-information-line text-primary me-2"></i>Status Informasi
                                            </h5>
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle"><i class="ri-user-line text-primary"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Status Akun</div>
                                                            <div class="fw-semibold">{{ $gtk->is_active ? 'Aktif' : 'Non-Aktif' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle"><i class="ri-calendar-check-line text-success"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Bergabung</div>
                                                            <div class="fw-semibold">{{ $gtk->created_at->format('d M Y') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle"><i class="ri-history-line text-info"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Terakhir Update</div>
                                                            <div class="fw-semibold">{{ $gtk->updated_at->format('d M Y H:i') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle"><i class="ri-mail-line text-warning"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Email Terverifikasi</div>
                                                            <div class="fw-semibold">{{ $gtk->email_verified_at ? 'Ya' : 'Tidak' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kontak -->
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3 d-flex align-items-center">
                                                <i class="ri-contacts-line text-primary me-2"></i>Informasi Kontak
                                            </h5>
                                            @if ($gtk->gtkContact)
                                                <div class="contact-list">
                                                    <div class="contact-item d-flex align-items-center">
                                                        <div class="icon-circle me-3"><i class="ri-phone-line text-success"></i></div>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-semibold">No. Handphone</div>
                                                            <div class="text-muted">{{ $gtk->gtkContact->no_hp }}</div>
                                                        </div>
                                                    </div>
                                                    @if ($gtk->gtkContact->no_whatsapp)
                                                        <div class="contact-item d-flex align-items-center">
                                                            <div class="icon-circle me-3"><i class="ri-whatsapp-line text-primary"></i></div>
                                                            <div class="flex-grow-1">
                                                                <div class="fw-semibold">WhatsApp</div>
                                                                <div class="text-muted">{{ $gtk->gtkContact->no_whatsapp }}</div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div class="contact-item d-flex align-items-center">
                                                        <div class="icon-circle me-3"><i class="ri-alarm-warning-line text-danger"></i></div>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-semibold">Kontak Darurat</div>
                                                            <div class="text-muted">{{ $gtk->gtkContact->kontak_darurat }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="alert alert-info"><i class="ri-information-line me-1"></i>Informasi kontak belum tersedia.</div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Data Identitas -->
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3 d-flex align-items-center">
                                                <i class="ri-shield-keyhole-line text-primary me-2"></i>Data Identitas
                                            </h5>
                                            <div class="alert alert-warning">
                                                <i class="ri-shield-check-line me-1"></i>Data identitas dilindungi untuk privasi.
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label detail-label">NIK</label>
                                                <div class="detail-value private-data" id="nikDisplay">{{ $masked_nik }}</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label detail-label">No. KK</label>
                                                <div class="detail-value private-data" id="kkDisplay">{{ $masked_no_kk }}</div>
                                            </div>
                                            <button class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#passwordModal">
                                                <i class="ri-eye-line me-1"></i> Tampilkan Data Identitas
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xxl-8">
                                    <!-- Informasi Pribadi -->
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-4 d-flex align-items-center">
                                                <i class="ri-profile-line text-primary me-2"></i>Informasi Pribadi
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Nama Lengkap</label>
                                                        <div class="detail-value">{{ $gtk->name }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Email</label>
                                                        <div class="detail-value">{{ $gtk->email }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tempat Lahir</label>
                                                        <div class="detail-value">{{ $gtk->gtkProfile?->tempat_lahir ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tanggal Lahir</label>
                                                        <div class="detail-value">
                                                            @if ($gtk->gtkProfile && $gtk->gtkProfile->tanggal_lahir)
                                                                {{ \Carbon\Carbon::parse($gtk->gtkProfile->tanggal_lahir)->format('d F Y') }}
                                                                <span class="text-muted ms-2">({{ \Carbon\Carbon::parse($gtk->gtkProfile->tanggal_lahir)->age }} tahun)</span>
                                                            @else -
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Jenis Kelamin</label>
                                                        <div class="detail-value">
                                                            @if ($gtk->gtkProfile)
                                                                <span class="badge bg-{{ $gtk->gtkProfile->jenis_kelamin == 'L' ? 'primary' : 'danger' }}-subtle text-{{ $gtk->gtkProfile->jenis_kelamin == 'L' ? 'primary' : 'danger' }}">
                                                                    <i class="ri-{{ $gtk->gtkProfile->jenis_kelamin == 'L' ? 'men' : 'women' }}-line me-1"></i>
                                                                    {{ $gtk->gtkProfile->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}
                                                                </span>
                                                            @else - @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Golongan Darah</label>
                                                        <div class="detail-value">
                                                            @if ($gtk->gtkProfile && $gtk->gtkProfile->golongan_darah)
                                                                <span class="badge bg-danger-subtle text-danger">{{ $gtk->gtkProfile->golongan_darah }}</span>
                                                            @else - @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Status Perkawinan</label>
                                                        <div class="detail-value">{{ $gtk->gtkProfile?->status_perkawinan ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Agama</label>
                                                        <div class="detail-value">{{ $gtk->gtkProfile?->agama ? ucfirst($gtk->gtkProfile->agama) : '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">NPWP</label>
                                                        <div class="detail-value">{{ $gtk->gtkProfile?->npwp ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Nama Ibu Kandung</label>
                                                        <div class="detail-value">{{ $gtk->gtkProfile?->nama_ibu_kandung ?? '-' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Alamat -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card mb-4 h-100">
                                                <div class="card-body">
                                                    <h5 class="card-title mb-3 d-flex align-items-center">
                                                        <i class="ri-home-4-line text-primary me-2"></i>Alamat Domisili
                                                    </h5>
                                                    @if ($domisiliAddress)
                                                        <div class="address-card p-3 rounded">
                                                            <div class="mb-2"><strong>{{ $domisiliAddress->jalan }}</strong></div>
                                                            @if ($domisiliAddress->rt_rw) <div class="mb-1"><span class="text-muted">RT/RW:</span> {{ $domisiliAddress->rt_rw }}</div> @endif
                                                            @if ($domisiliAddress->dusun) <div class="mb-1"><span class="text-muted">Dusun:</span> {{ $domisiliAddress->dusun }}</div> @endif
                                                            <div class="mb-1"><span class="text-muted">Desa/Kelurahan:</span> {{ $desaDomisili }}</div>
                                                            <div class="mb-1"><span class="text-muted">Kecamatan:</span> {{ $kecamatanDomisili }}</div>
                                                            <div class="mb-1"><span class="text-muted">Kabupaten/Kota:</span> {{ $kabKotaDomisili }}</div>
                                                            <div class="mb-1"><span class="text-muted">Provinsi:</span> {{ $provinsiDomisili }}</div>
                                                            @if ($domisiliAddress->kode_pos) <div class="text-muted">Kode Pos: {{ $domisiliAddress->kode_pos }}</div> @endif
                                                        </div>
                                                    @else
                                                        <div class="alert alert-light"><i class="ri-map-pin-line me-2"></i>Alamat domisili belum diisi.</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card mb-4 h-100">
                                                <div class="card-body">
                                                    <h5 class="card-title mb-3 d-flex align-items-center">
                                                        <i class="ri-id-card-line text-success me-2"></i>Alamat KTP
                                                    </h5>
                                                    @if ($ktpAddress)
                                                        <div class="address-card ktp p-3 rounded">
                                                            <div class="mb-2"><strong>{{ $ktpAddress->jalan }}</strong></div>
                                                            @if ($ktpAddress->rt_rw) <div class="mb-1"><span class="text-muted">RT/RW:</span> {{ $ktpAddress->rt_rw }}</div> @endif
                                                            @if ($ktpAddress->dusun) <div class="mb-1"><span class="text-muted">Dusun:</span> {{ $ktpAddress->dusun }}</div> @endif
                                                            <div class="mb-1"><span class="text-muted">Desa/Kelurahan:</span> {{ $desaKtp }}</div>
                                                            <div class="mb-1"><span class="text-muted">Kecamatan:</span> {{ $kecamatanKtp }}</div>
                                                            <div class="mb-1"><span class="text-muted">Kabupaten/Kota:</span> {{ $kabKotaKtp }}</div>
                                                            <div class="mb-1"><span class="text-muted">Provinsi:</span> {{ $provinsiKtp }}</div>
                                                            @if ($ktpAddress->kode_pos) <div class="text-muted">Kode Pos: {{ $ktpAddress->kode_pos }}</div> @endif
                                                        </div>
                                                    @else
                                                        <div class="alert alert-light"><i class="ri-information-line me-2"></i>Alamat KTP belum diisi.</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 2: KEPEGAWAIAN
                        ============================================================ --}}
                        <div class="tab-pane fade" id="kepegawaian" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4 d-flex align-items-center">
                                        <i class="ri-briefcase-line text-primary me-2"></i>Informasi Kepegawaian
                                    </h5>
                                    @if ($gtk->employment)
                                        <div class="row">
                                            <div class="col-lg-8">
                                                <div class="row">
                                                    <div class="col-md-6"><div class="mb-4"><label class="form-label detail-label">Jenis GTK</label><div class="detail-value"><span class="badge bg-info-subtle text-info"><i class="ri-user-line me-1"></i>{{ $gtk->employment->jenisGtk?->nama ?? $gtk->employment->jenis_gtk ?? '-' }}</span></div></div></div>
                                                    <div class="col-md-6"><div class="mb-4"><label class="form-label detail-label">Jabatan</label><div class="detail-value"><strong>{{ $gtk->employment->jabatanRel?->nama ?? $gtk->employment->jabatan ?? '-' }}</strong></div></div></div>
                                                    <div class="col-md-6"><div class="mb-4"><label class="form-label detail-label">Status Kepegawaian</label><div class="detail-value"><span class="badge bg-warning-subtle text-warning">{{ $gtk->employment->status_kepegawaian ?? '-' }}</span></div></div></div>
                                                    <div class="col-md-6"><div class="mb-4"><label class="form-label detail-label">NUPY</label><div class="detail-value"><code>{{ $gtk->employment->nupy ?? '-' }}</code></div></div></div>
                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label detail-label">TMT</label>
                                                            <div class="detail-value">
                                                                @if ($gtk->employment->tmt)
                                                                    {{ \Carbon\Carbon::parse($gtk->employment->tmt)->format('d F Y') }}
                                                                    <span class="text-muted ms-2">({{ \Carbon\Carbon::parse($gtk->employment->tmt)->diffForHumans() }})</span>
                                                                @else - @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label detail-label">Tanggal SK</label>
                                                            <div class="detail-value">
                                                                @if ($gtk->employment->tanggal_sk)
                                                                    {{ \Carbon\Carbon::parse($gtk->employment->tanggal_sk)->format('d F Y') }}
                                                                @else - @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="mb-4">
                                                            <label class="form-label detail-label">Nomor SK</label>
                                                            <div class="detail-value">
                                                                <div class="input-group">
                                                                    <span class="input-group-text bg-light"><i class="ri-file-text-line"></i></span>
                                                                    <input type="text" class="form-control bg-light" value="{{ $gtk->employment->nomor_sk ?? '-' }}" readonly>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @if ($gtk->employment->pangkat_golongan)
                                                        <div class="col-md-6"><div class="mb-4"><label class="form-label detail-label">Pangkat/Golongan</label><div class="detail-value">{{ $gtk->employment->pangkat_golongan }}</div></div></div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6 class="card-title mb-3"><i class="ri-building-line me-2"></i>Satuan Kerja</h6>
                                                        @if ($gtk->gtkWorkUnits && $gtk->gtkWorkUnits->count() > 0)
                                                            @foreach ($gtk->gtkWorkUnits as $assignment)
                                                                @if ($assignment->workUnit)
                                                                    <div class="d-flex align-items-center mb-3 p-2 {{ $assignment->is_primary ? 'bg-primary bg-opacity-10 rounded' : '' }}">
                                                                        <div class="icon-circle me-3"><i class="ri-building-2-line text-{{ $assignment->is_primary ? 'primary' : 'secondary' }}"></i></div>
                                                                        <div>
                                                                            <div class="fw-semibold">{{ $assignment->workUnit->name }} @if ($assignment->is_primary) <span class="badge bg-primary ms-2">Utama</span> @endif</div>
                                                                            @if ($assignment->workUnit->code) <div class="text-muted fs-12">Kode: {{ $assignment->workUnit->code }}</div> @endif
                                                                            @if ($assignment->jabatan) <div class="text-muted fs-12"><i class="ri-briefcase-line me-1"></i>{{ $assignment->jabatan }}</div> @endif
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            <div class="alert alert-light"><i class="ri-information-line me-2"></i>Belum ditugaskan ke satuan kerja</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <div class="d-flex align-items-center">
                                                <i class="ri-information-line fs-4 me-3"></i>
                                                <div><h6 class="alert-heading">Data Kepegawaian Belum Tersedia</h6><p class="mb-0">Silakan edit data untuk menambahkan informasi kepegawaian.</p></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 2: INFO PENSIUN (embedded in profile)
                             ============================================================ --}}
                        @php
                            $pensionBupAge = \App\Models\PensionSetting::getInt('bup_age', 58);
                            $pensionTglLahir = $gtk->gtkProfile?->tanggal_lahir;
                            $pensionTmt = $pensionTglLahir ? \Carbon\Carbon::parse($pensionTglLahir)->addYears($pensionBupAge) : null;
                            $pensionSisa = $pensionTmt ? \Carbon\Carbon::now()->diffInMonths($pensionTmt, false) : null;
                            $pensionColor = $pensionSisa !== null
                                ? ($pensionSisa <= 0 ? 'danger' : ($pensionSisa <= 6 ? 'warning' : ($pensionSisa <= 12 ? 'info' : 'success')))
                                : 'secondary';
                            $pensionSisaLabel = $pensionSisa !== null
                                ? ($pensionSisa <= 0 ? 'Sudah BUP' : (intval($pensionSisa) . ' bulan (' . round($pensionSisa/12, 1) . ' tahun)'))
                                : '–';
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0"><i class="ri-time-line text-primary me-2"></i>Informasi Pensiun</h5>
                                <a href="{{ route('user.pension.edit', ['userId' => $userId, 'uuid' => $gtk->id]) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="ri-edit-2-line me-1"></i> Edit
                                </a>
                            </div>
                            <div class="row g-3">
                                <div class="col-xl-2 col-md-4 col-6">
                                    <div class="card card-animate h-100">
                                        <div class="card-body py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-sm flex-shrink-0">
                                                    <span class="avatar-title bg-primary-subtle rounded fs-2">
                                                        <i class="bx bx-user text-primary"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Usia Sekarang</p>
                                                    <h6 class="fw-bold ff-secondary mb-0">
                                                        @if($gtk->gtkProfile?->tanggal_lahir)
                                                            {{ \Carbon\Carbon::parse($gtk->gtkProfile->tanggal_lahir)->age }} th
                                                        @else
                                                            –
                                                        @endif
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-2 col-md-4 col-6">
                                    <div class="card card-animate h-100">
                                        <div class="card-body py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-sm flex-shrink-0">
                                                    <span class="avatar-title bg-success-subtle rounded fs-2">
                                                        <i class="bx bx-time-five text-success"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">BUP</p>
                                                    <h6 class="fw-bold ff-secondary mb-0">{{ $pensionBupAge }} th</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-2 col-md-4 col-6">
                                    <div class="card card-animate h-100">
                                        <div class="card-body py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-sm flex-shrink-0">
                                                    <span class="avatar-title bg-info-subtle rounded fs-2">
                                                        <i class="bx bx-calendar-check text-info"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">TMT Pensiun</p>
                                                    <h6 class="fw-bold ff-secondary mb-0">
                                                        @if($pensionTmt)
                                                            {{ $pensionTmt->format('d/m/Y') }}
                                                        @else
                                                            –
                                                        @endif
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-2 col-md-4 col-6">
                                    <div class="card card-animate h-100">
                                        <div class="card-body py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-sm flex-shrink-0">
                                                    <span class="avatar-title bg-warning-subtle rounded fs-2">
                                                        <i class="ri-time-line text-warning"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Sisa Waktu</p>
                                                    <h6 class="fw-bold ff-secondary mb-0 {{ $pensionColor }}">{{ $pensionSisaLabel }}</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-2 col-md-4 col-6">
                                    <div class="card card-animate h-100">
                                        <div class="card-body py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-sm flex-shrink-0">
                                                    <span class="avatar-title bg-secondary-subtle rounded fs-2">
                                                        <i class="bx bx-tag text-secondary"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Jenis</p>
                                                    <h6 class="fw-bold ff-secondary mb-0" style="font-size:0.8rem;">
                                                        @if($gtk->pension)
                                                            @if($gtk->pension->pension_type === 'normal') Normal
                                                            @elseif($gtk->pension->pension_type === 'dini') Dini
                                                            @elseif($gtk->pension->pension_type === 'cacat') Cacat
                                                            @elseif($gtk->pension->pension_type === 'janda') Janda/Duda
                                                            @else – @endif
                                                        @else – @endif
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-2 col-md-4 col-6">
                                    <div class="card card-animate h-100">
                                        <div class="card-body py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-sm flex-shrink-0">
                                                    <span class="avatar-title bg-danger-subtle rounded fs-2">
                                                        <i class="bx bx-flag text-danger"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Status</p>
                                                    <h6 class="fw-bold ff-secondary mb-0" style="font-size:0.8rem;">
                                                        @if($gtk->pension)
                                                            @if($gtk->pension->pension_status === 'draft') Draft
                                                            @elseif($gtk->pension->pension_status === 'pending') Pending
                                                            @elseif($gtk->pension->pension_status === 'approved') Disetujui
                                                            @elseif($gtk->pension->pension_status === 'completed') Selesai
                                                            @elseif($gtk->pension->pension_status === 'cancelled') Batal
                                                            @else – @endif
                                                        @else – @endif
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 3: PENDIDIKAN
                             FIX: Hapus modal HTML terpisah, gunakan SweetAlert2 seperti
                             halaman create. Data pendidikan di-embed sebagai JSON.
                        ============================================================ --}}
                        <div class="tab-pane fade" id="pendidikan" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title mb-0 d-flex align-items-center">
                                            <i class="ri-graduation-cap-line text-primary me-2"></i>Riwayat Pendidikan
                                        </h5>
                                        <button type="button" class="btn btn-sm btn-success" onclick="showEducationModal(null)">
                                            <i class="ri-add-line me-1"></i> Tambah Pendidikan
                                        </button>
                                    </div>

                                    @if ($gtk->educations && $gtk->educations->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped align-middle" id="educationTable">
                                                <thead class="table-light">
                                                    <tr class="text-center">
                                                        <th width="5%">No</th>
                                                        <th>Jenjang</th>
                                                        <th>Institusi</th>
                                                        <th>Jurusan / Fakultas</th>
                                                        <th>Tahun</th>
                                                        <th>No Ijazah</th>
                                                        <th>Nilai</th>
                                                        <th>Status</th>
                                                        <th width="10%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="educationTableBody">
                                                    @foreach ($gtk->educations->sortByDesc('tahun_lulus') as $index => $pendidikan)
                                                        <tr id="education-row-{{ $pendidikan->id }}">
                                                            <td class="text-center">{{ $index + 1 }}</td>
                                                            <td>{{ $pendidikan->jenjang_pendidikan }}</td>
                                                            <td><strong>{{ $pendidikan->nama_satuan_pendidikan }}</strong></td>
                                                            <td>
                                                                {{ $pendidikan->fakultas }}
                                                                {{ $pendidikan->fakultas && $pendidikan->jurusan ? ' - ' : '' }}
                                                                {{ $pendidikan->jurusan }}
                                                            </td>
                                                            <td class="text-center">{{ $pendidikan->tahun_masuk ?? '-' }} - {{ $pendidikan->tahun_lulus }}</td>
                                                            <td>{{ $pendidikan->no_ijazah ?? '-' }}</td>
                                                            <td class="text-center">{{ $pendidikan->nilai_akhir ?? '-' }}</td>
                                                            <td class="text-center">
                                                                @php
                                                                    $statusClass = match ($pendidikan->status) {
                                                                        'LULUS'       => 'success',
                                                                        'BELUM_LULUS' => 'warning',
                                                                        'DROPOUT'     => 'danger',
                                                                        'PINDAH'      => 'info',
                                                                        default       => 'secondary',
                                                                    };
                                                                @endphp
                                                                <span class="badge bg-{{ $statusClass }}">{{ $pendidikan->status }}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                {{-- Embed data sebagai JSON di data-* agar tidak perlu AJAX GET --}}
                                                                <button class="btn btn-sm btn-warning"
                                                                    data-edu="{{ json_encode([
                                                                        'id'                     => $pendidikan->id,
                                                                        'jenjang_pendidikan'     => $pendidikan->jenjang_pendidikan,
                                                                        'nama_satuan_pendidikan' => $pendidikan->nama_satuan_pendidikan,
                                                                        'jurusan'                => $pendidikan->jurusan ?? '',
                                                                        'fakultas'               => $pendidikan->fakultas ?? '',
                                                                        'tahun_masuk'            => $pendidikan->tahun_masuk ?? '',
                                                                        'tahun_lulus'            => $pendidikan->tahun_lulus ?? '',
                                                                        'no_ijazah'              => $pendidikan->no_ijazah ?? '',
                                                                        'nilai_akhir'            => $pendidikan->nilai_akhir ?? '',
                                                                        'skala_nilai'            => $pendidikan->skala_nilai ?? '100',
                                                                        'status'                 => $pendidikan->status ?? 'LULUS',
                                                                        'nama_kepala_sekolah'    => $pendidikan->nama_kepala_sekolah ?? '',
                                                                        'nama_rektor'            => $pendidikan->nama_rektor ?? '',
                                                                        'keterangan'             => $pendidikan->keterangan ?? '',
                                                                    ]) }}"
                                                                    onclick="editEducationFromBtn(this)">
                                                                    Edit
                                                                </button>
                                                                <button class="btn btn-sm btn-danger"
                                                                    onclick="deleteEducation('{{ $pendidikan->id }}', '{{ addslashes($pendidikan->jenjang_pendidikan . ' - ' . $pendidikan->nama_satuan_pendidikan) }}')">
                                                                    Hapus
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-5" id="educationEmptyState">
                                            <div class="mb-4"><i class="ri-graduation-cap-line text-muted" style="font-size: 5rem;"></i></div>
                                            <h5 class="text-muted mb-3">Riwayat Pendidikan Belum Tersedia</h5>
                                            <p class="text-muted mb-4">Informasi pendidikan untuk GTK ini belum diinput.</p>
                                            <button type="button" class="btn btn-primary" onclick="showEducationModal(null)">
                                                <i class="ri-add-line me-1"></i> Tambah Riwayat Pendidikan
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 4: KELUARGA
                        ============================================================ --}}
                        <div class="tab-pane fade" id="keluarga" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title mb-0 d-flex align-items-center">
                                            <i class="ri-group-line text-primary me-2"></i>Data Keluarga
                                        </h5>
                                        <div>
                                            <span class="badge bg-primary me-2">
                                                <i class="ri-user-line me-1"></i>
                                                {{ $gtk->gtkProfile?->familyMembers->count() ?? 0 }} Anggota
                                            </span>
                                            <button type="button" class="btn btn-sm btn-success" onclick="fmShowModal()">
                                                <i class="ri-user-add-line me-1"></i> Tambah Anggota
                                            </button>
                                        </div>
                                    </div>

                                    @if ($gtk->gtkProfile && $gtk->gtkProfile->familyMembers && $gtk->gtkProfile->familyMembers->count() > 0)
                                        <div class="row" id="family-members-container">
                                            @foreach ($gtk->gtkProfile->familyMembers as $anggota)
                                                <div class="col-xl-4 col-lg-6 mb-4"
                                                    id="family-member-{{ $anggota->id }}"
                                                    data-rel="{{ $anggota->relationship }}"
                                                    data-nama="{{ addslashes($anggota->nama) }}"
                                                    data-jk="{{ $anggota->jenis_kelamin }}"
                                                    data-tmpt="{{ addslashes($anggota->tempat_lahir ?? '') }}"
                                                    data-tgl="{{ $anggota->tanggal_lahir ?? '' }}"
                                                    data-pkrjn="{{ addslashes($anggota->pekerjaan ?? '') }}"
                                                    data-pnddk="{{ $anggota->pendidikan_terakhir ?? '' }}"
                                                    data-almt="{{ addslashes($anggota->alamat ?? '') }}">
                                                    <div class="card h-100 family-member-card">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="avatar-md me-3">
                                                                        <div class="avatar-title rounded-circle bg-{{ in_array(strtolower($anggota->relationship), ['suami', 'istri']) ? 'success' : (strtolower($anggota->relationship) == 'anak' ? 'info' : 'warning') }}-subtle">
                                                                            <i class="ri-{{ $anggota->jenis_kelamin == 'L' ? 'men' : 'women' }}-line fs-24 text-{{ in_array(strtolower($anggota->relationship), ['suami', 'istri']) ? 'success' : (strtolower($anggota->relationship) == 'anak' ? 'info' : 'warning') }}"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <h6 class="mb-0 fw-semibold">{{ $anggota->nama }}</h6>
                                                                        <span class="badge bg-{{ in_array(strtolower($anggota->relationship), ['suami', 'istri']) ? 'success' : (strtolower($anggota->relationship) == 'anak' ? 'info' : 'warning') }}-subtle text-{{ in_array(strtolower($anggota->relationship), ['suami', 'istri']) ? 'success' : (strtolower($anggota->relationship) == 'anak' ? 'info' : 'warning') }} mt-1">
                                                                            {{ ucfirst($anggota->relationship) }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="dropdown">
                                                                    <button class="btn btn-sm btn-icon btn-light" type="button" data-bs-toggle="dropdown">
                                                                        <i class="ri-more-2-fill"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="fmEdit('{{ $anggota->id }}')"><i class="ri-edit-box-line me-2"></i>Edit</a></li>
                                                                        <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="fmDelete('{{ $anggota->id }}', '{{ addslashes($anggota->nama) }}')"><i class="ri-delete-bin-line me-2"></i>Hapus</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="mt-3">
                                                                <div class="row g-2">
                                                                    <div class="col-6">
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="ri-{{ $anggota->jenis_kelamin == 'L' ? 'men' : 'women' }}-line text-muted me-1"></i>
                                                                            <small>{{ $anggota->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</small>
                                                                        </div>
                                                                    </div>
                                                                    @if ($anggota->tanggal_lahir)
                                                                        <div class="col-6">
                                                                            <div class="d-flex align-items-center">
                                                                                <i class="ri-cake-2-line text-muted me-1"></i>
                                                                                <small>{{ \Carbon\Carbon::parse($anggota->tanggal_lahir)->format('d/m/Y') }}</small>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                    @if ($anggota->pekerjaan)
                                                                        <div class="col-12"><div class="d-flex align-items-center"><i class="ri-briefcase-line text-muted me-1"></i><small>{{ $anggota->pekerjaan }}</small></div></div>
                                                                    @endif
                                                                    @if ($anggota->pendidikan_terakhir)
                                                                        <div class="col-12"><div class="d-flex align-items-center"><i class="ri-graduation-cap-line text-muted me-1"></i><small>{{ $anggota->pendidikan_terakhir }}</small></div></div>
                                                                    @endif
                                                                    @if ($anggota->alamat)
                                                                        <div class="col-12"><div class="d-flex align-items-start"><i class="ri-map-pin-line text-muted me-1 mt-1"></i><small class="text-truncate" title="{{ $anggota->alamat }}">{{ $anggota->alamat }}</small></div></div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            @if ($anggota->tanggal_lahir)
                                                                <div class="mt-3 pt-2 border-top">
                                                                    <small class="text-muted"><i class="ri-time-line me-1"></i>Usia: {{ \Carbon\Carbon::parse($anggota->tanggal_lahir)->age }} tahun</small>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-md-4">
                                                <div class="card bg-light"><div class="card-body text-center"><div class="text-primary mb-2"><i class="ri-user-line fs-2"></i></div><div class="fs-4 fw-semibold">{{ $gtk->gtkProfile->familyMembers->whereIn('relationship', ['suami', 'istri'])->count() }}</div><div class="text-muted">Pasangan</div></div></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-light"><div class="card-body text-center"><div class="text-info mb-2"><i class="ri-user-smile-line fs-2"></i></div><div class="fs-4 fw-semibold">{{ $gtk->gtkProfile->familyMembers->where('relationship', 'anak')->count() }}</div><div class="text-muted">Anak</div></div></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-light"><div class="card-body text-center"><div class="text-warning mb-2"><i class="ri-user-star-line fs-2"></i></div><div class="fs-4 fw-semibold">{{ $gtk->gtkProfile->familyMembers->whereIn('relationship', ['ayah', 'ibu'])->count() }}</div><div class="text-muted">Orang Tua</div></div></div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <div class="mb-4"><i class="ri-group-line text-muted" style="font-size: 5rem;"></i></div>
                                            <h5 class="text-muted mb-3">Data Keluarga Belum Tersedia</h5>
                                            <p class="text-muted mb-4">Informasi anggota keluarga untuk GTK ini belum diinput.</p>
                                            <button type="button" class="btn btn-primary" onclick="fmShowModal()">
                                                <i class="ri-user-add-line me-1"></i> Tambah Anggota Keluarga
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 5: DOKUMEN
                        ============================================================ --}}
                        <!-- Data Kesehatan Tab -->
                        <div class="tab-pane fade" id="data-kesehatan" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title mb-0 d-flex align-items-center">
                                            <i class="ri-heart-pulse-line text-danger me-2"></i>Data Kesehatan GTK
                                        </h5>
                                        <button type="button" class="btn btn-primary btn-sm" id="btnEditKesehatan" onclick="toggleHealthDataForm()">
                                            <i class="ri-edit-line me-1"></i> Edit Data
                                        </button>
                                    </div>

                                
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="dokumen" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4 d-flex align-items-center">
                                        <i class="ri-folder-line text-primary me-2"></i>Dokumen Terlampir
                                    </h5>
                                    <div class="alert alert-info">
                                        <div class="d-flex align-items-center">
                                            <i class="ri-information-line fs-4 me-3"></i>
                                            <div><h6 class="alert-heading">Fitur Dokumen</h6><p class="mb-0">Fitur untuk mengelola dokumen GTK akan segera hadir.</p></div>
                                        </div>
                                    </div>
                                    <div class="text-center py-5">
                                        <div class="mb-4"><i class="ri-folder-upload-line text-muted" style="font-size: 5rem;"></i></div>
                                        <h5 class="text-muted mb-3">Belum Ada Dokumen</h5>
                                        <button class="btn btn-primary" disabled><i class="ri-upload-line me-1"></i> Unggah Dokumen</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>{{-- end tab-content --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/profile.init.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
    /* ==========================================================================
       UTILITIES
       ========================================================================== */
    function escHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    function escAttr(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    /* ==========================================================================
       COPY TO CLIPBOARD
       ========================================================================== */
    function copyToClipboard(elementId, label) {
        const text = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(text).then(function() {
            Swal.fire({ icon: 'success', title: 'Berhasil', text: label + ' berhasil disalin ke clipboard', timer: 2000, showConfirmButton: false });
        }).catch(function() {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menyalin ke clipboard' });
        });
    }

    /* ==========================================================================
       VERIFIKASI PASSWORD (NIK / KK)
       ========================================================================== */
    $(document).ready(function () {

        var countdownInterval;

        $('#passwordForm').on('submit', function (e) {
            e.preventDefault();
            const password  = $('#password').val();
            const submitBtn = $('#submitPassword');
            $('#passwordError').addClass('d-none');
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Memverifikasi...');

            $.ajax({
                url: '{!! route('user.gtk.verify-password', ['userId' => $userId]) !!}',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', password: password, gtk_id: '{{ $gtk->id }}' },
                success: function (response) {
                    if (response.success) {
                        $('#nikData').text(response.data.nik);
                        $('#kkData').text(response.data.no_kk);
                        $('#nikDisplay').html('<span class="text-success fw-bold">' + response.data.nik + '</span>');
                        $('#kkDisplay').html('<span class="text-success fw-bold">' + response.data.no_kk + '</span>');
                        $('#passwordModal').modal('hide');
                        $('#password').val('');
                        $('#identityModal').modal('show');
                        var timeLeft = 60;
                        clearInterval(countdownInterval);
                        countdownInterval = setInterval(function () {
                            timeLeft--;
                            $('#countdown').text(timeLeft);
                            if (timeLeft <= 0) { clearInterval(countdownInterval); $('#identityModal').modal('hide'); }
                        }, 1000);
                    } else {
                        $('#errorMessage').text(response.message);
                        $('#passwordError').removeClass('d-none');
                        $('#password').addClass('is-invalid').focus();
                    }
                },
                error: function (xhr) {
                    $('#errorMessage').text(xhr.responseJSON?.message || 'Terjadi kesalahan pada server.');
                    $('#passwordError').removeClass('d-none');
                    $('#password').addClass('is-invalid').focus();
                },
                complete: function () {
                    submitBtn.prop('disabled', false).html('<i class="ri-check-line me-1"></i> Verifikasi');
                }
            });
        });

        $('#passwordModal').on('hidden.bs.modal', function () {
            $('#password').val('').removeClass('is-invalid');
            $('#passwordError').addClass('d-none');
        });
        $('#identityModal').on('hidden.bs.modal', function () { clearInterval(countdownInterval); });

        // Reset family modal on close
        $('#familyMemberModal').on('hidden.bs.modal', function () {
            document.getElementById('fm_edit_id').value        = '';
            document.getElementById('fm_relationship').value   = '';
            document.getElementById('fm_nama').value            = '';
            document.getElementById('fm_jenis_kelamin').value   = '';
            document.getElementById('fm_tempat_lahir').value    = '';
            document.getElementById('fm_tanggal_lahir').value   = '';
            document.getElementById('fm_pekerjaan').value       = '';
            document.getElementById('fm_pendidikan_terakhir').value = '';
            document.getElementById('fm_alamat').value          = '';
        });
    });

    /* ==========================================================================
       PENDIDIKAN — SweetAlert2 modal (sama persis dengan halaman create/edit)
       FIX 1: Tidak lagi menggunakan AJAX GET untuk load data edit.
       FIX 2: Data edit dibaca dari data-edu attribute pada tombol.
       FIX 3: Form lengkap (jurusan, fakultas, skala, kepala sekolah, keterangan).
    ========================================================================== */

    // Dipanggil dari tombol Edit di tabel (membaca data dari attribute)
    function editEducationFromBtn(btn) {
        var raw = btn.getAttribute('data-edu');
        var edu = {};
        try { edu = JSON.parse(raw); } catch (e) { console.error('Parse error data-edu:', e); }
        showEducationModal(edu);
    }

    // Modal SweetAlert2 pendidikan — null = tambah, object = edit
    function showEducationModal(edu) {
        var isEdit = edu !== null && edu !== undefined && edu.id;
        if (!edu) edu = {};
        var yr = new Date().getFullYear();

        var jenjangOptions = ['SD','SMP','SMA','SMK','D1','D2','D3','D4','S1','S2','S3','PAKET_B','PAKET_C','PROFESI','SPESIALIS']
            .map(function(j) {
                return '<option value="' + j + '" ' + (edu.jenjang_pendidikan === j ? 'selected' : '') + '>' + j + '</option>';
            }).join('');

        var statusOptions = [['LULUS','Lulus'],['BELUM_LULUS','Belum Lulus'],['DROPOUT','Drop Out'],['PINDAH','Pindah']]
            .map(function(pair) {
                return '<option value="' + pair[0] + '" ' + ((edu.status || 'LULUS') === pair[0] ? 'selected' : '') + '>' + pair[1] + '</option>';
            }).join('');

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
                    '<div id="edu_swal_error" class="col-12" style="display:none">' +
                        '<div class="alert alert-danger mb-0" id="edu_swal_error_msg"></div>' +
                    '</div>' +
                '</div>',
            showCancelButton: true,
            confirmButtonText: isEdit ? '<i class="ri-save-line me-1"></i> Perbarui' : '<i class="ri-save-line me-1"></i> Simpan',
            cancelButtonText: 'Batal',
            focusConfirm: false,
            showLoaderOnConfirm: true,
            allowOutsideClick: function() { return !Swal.isLoading(); },
            preConfirm: function() {
                var jenjang = document.getElementById('edu_jenjang').value;
                var nama    = document.getElementById('edu_nama').value.trim();
                var lulus   = document.getElementById('edu_lulus').value;

                if (!jenjang || !nama || !lulus) {
                    Swal.showValidationMessage('Jenjang, Nama Institusi, dan Tahun Lulus wajib diisi.');
                    return false;
                }

                var payload = {
                    _token:                 '{{ csrf_token() }}',
                    user_id:                '{{ $gtk->id }}',
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
                };

                // Pilih URL dan method berdasarkan mode
                var url    = isEdit
                    ? '{!! route('user.gtk.education.update', ['userId' => $userId, 'uuid' => $gtk->id, 'id' => ':id']) !!}'.replace(':id', edu.id)
                    : '{!! route('user.gtk.education.store', ['userId' => $userId, 'uuid' => $gtk->id]) !!}';
                var method = isEdit ? 'PUT' : 'POST';

                return fetch(url, {
                    method: method,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new URLSearchParams(payload).toString()
                }).then(function(res) {
                    return res.json().then(function(data) {
                        if (!res.ok) throw data;
                        return data;
                    });
                }).catch(function(err) {
                    var msg = 'Terjadi kesalahan.';
                    if (err && err.errors) {
                        msg = Object.values(err.errors).map(function(e) { return e[0]; }).join('<br>');
                    } else if (err && err.message) {
                        msg = err.message;
                    }
                    Swal.showValidationMessage('<span style="text-align:left;display:block">' + msg + '</span>');
                    return false;
                });
            }
        }).then(function(result) {
            if (!result.isConfirmed || !result.value) return;
            var response = result.value;
            if (response.success) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 2000, showConfirmButton: false });
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', html: response.message || 'Terjadi kesalahan.' });
            }
        });
    }

    // Hapus pendidikan
    function deleteEducation(id, title) {
        var deleteUrl = '{!! route('user.gtk.education.delete', ['userId' => $userId, 'uuid' => $gtk->id, 'id' => ':id']) !!}'.replace(':id', id);
        Swal.fire({
            title: 'Hapus Riwayat Pendidikan',
            text: 'Yakin hapus "' + title + '"? Data tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 2000, showConfirmButton: false });
                        $('#education-row-' + id).fadeOut(400, function() { $(this).remove(); });
                    }
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal', html: xhr.responseJSON?.message || 'Gagal menghapus data pendidikan' });
                }
            });
        });
    }

    /* ==========================================================================
       KELUARGA
       ========================================================================== */
    function fmShowModal(editId) {
        var modal   = document.getElementById('familyMemberModal');
        var editIdEl = document.getElementById('fm_edit_id');

        if (editId) {
            var card = document.getElementById('family-member-' + editId);
            if (!card) { console.error('Card not found: family-member-' + editId); return; }
            document.getElementById('familyMemberModalLabel').textContent = 'Edit Anggota Keluarga';
            editIdEl.value = editId;
            document.getElementById('fm_relationship').value       = card.dataset.rel    || '';
            document.getElementById('fm_nama').value                = card.dataset.nama   || '';
            document.getElementById('fm_jenis_kelamin').value       = card.dataset.jk     || '';
            document.getElementById('fm_tempat_lahir').value        = card.dataset.tmpt   || '';
            document.getElementById('fm_tanggal_lahir').value       = card.dataset.tgl    || '';
            document.getElementById('fm_pekerjaan').value           = card.dataset.pkrjn  || '';
            document.getElementById('fm_pendidikan_terakhir').value = card.dataset.pnddk  || '';
            document.getElementById('fm_alamat').value              = card.dataset.almt   || '';
        } else {
            document.getElementById('familyMemberModalLabel').textContent = 'Tambah Anggota Keluarga';
            editIdEl.value = '';
            ['fm_relationship','fm_nama','fm_jenis_kelamin','fm_tempat_lahir','fm_tanggal_lahir','fm_pekerjaan','fm_pendidikan_terakhir','fm_alamat']
                .forEach(function(id) { document.getElementById(id).value = ''; });
        }
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    function fmEdit(id) { fmShowModal(id); }

    function fmSave() {
        var rel    = document.getElementById('fm_relationship').value;
        var nama   = document.getElementById('fm_nama').value.trim();
        var jk     = document.getElementById('fm_jenis_kelamin').value;
        var editId = document.getElementById('fm_edit_id').value;
        var isEdit = !!editId;

        if (!rel || !nama || !jk) {
            Swal.fire({ icon: 'warning', title: 'Lengkapi data', text: 'Hubungan, Nama, dan Jenis Kelamin wajib diisi.' });
            return;
        }

        var data = {
            _token:              '{{ csrf_token() }}',
            gtk_profile_id:      '{{ $gtk->gtkProfile?->id ?? "" }}',
            relationship:        rel,
            nama:                nama,
            jenis_kelamin:       jk,
            tempat_lahir:        document.getElementById('fm_tempat_lahir').value.trim(),
            tanggal_lahir:       document.getElementById('fm_tanggal_lahir').value,
            pekerjaan:           document.getElementById('fm_pekerjaan').value.trim(),
            pendidikan_terakhir: document.getElementById('fm_pendidikan_terakhir').value,
            alamat:              document.getElementById('fm_alamat').value.trim(),
        };

        var url = isEdit
            ? '{!! route('user.gtk.family-member.update', ['userId' => $userId, 'uuid' => $gtk->id, 'id' => ':id']) !!}'.replace(':id', editId)
            : '{!! route('user.gtk.family-member.store', ['userId' => $userId, 'uuid' => $gtk->id]) !!}';

        var saveBtn = document.querySelector('#familyMemberModal .btn-primary');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        $.ajax({
            url: url,
            type: isEdit ? 'PUT' : 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 2000, showConfirmButton: false });
                    $('#familyMemberModal').modal('hide');
                    setTimeout(function() { location.reload(); }, 1500);
                }
            },
            error: function(xhr) {
                var msgs = xhr.responseJSON?.errors
                    ? Object.entries(xhr.responseJSON.errors).map(function(e) { return '<strong>' + e[0] + ':</strong> ' + e[1].join(', '); }).join('<br>')
                    : (xhr.responseJSON?.message || 'Gagal menyimpan');
                Swal.fire({ icon: 'error', title: 'Gagal', html: msgs });
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="ri-save-line me-1"></i> Simpan';
            }
        });
    }

    function fmDelete(id, nama) {
        var deleteUrl = '{!! route('user.gtk.family-member.delete', ['userId' => $userId, 'uuid' => $gtk->id, 'id' => ':id']) !!}'.replace(':id', id);
        Swal.fire({
            title: 'Hapus Anggota Keluarga',
            text: 'Yakin hapus "' + nama + '"? Data tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 2000, showConfirmButton: false });
                        document.getElementById('family-member-' + id)?.remove();
                    }
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal', html: xhr.responseJSON?.message || 'Gagal menghapus data' });
                }
            });
        });
    }

    /* ==========================================================================
       DATA KESEHATAN GTK
       ========================================================================== */
    let isHealthEditMode = false;

    function toggleHealthDataForm() {
        const display = document.getElementById('healthDataDisplay');
        const form = document.getElementById('healthDataForm');

        if (!isHealthEditMode) {
            // Switch to edit mode — prefill form from display data
            display.classList.add('d-none');
            form.classList.remove('d-none');
            document.getElementById('btnEditKesehatan').classList.add('d-none');
            isHealthEditMode = true;
        } else {
            // Back to display mode
            display.classList.remove('d-none');
            form.classList.add('d-none');
            document.getElementById('btnEditKesehatan').classList.remove('d-none');
            isHealthEditMode = false;
        }
    }

    function saveHealthData(event) {
        event.preventDefault();

        const form = document.getElementById('healthDataFormElement');
        const formData = new FormData(form);
        const saveBtn = document.getElementById('btnSaveHealth');

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i> Menyimpan...';

        // Determine method and URL
        var healthUrl = '{!! route("user.gtk.health-data.store", ["userId" => $userId, "uuid" => $gtk->id]) !!}';
        var method = 'POST';

        $.ajax({
            url: healthUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message || 'Data kesehatan berhasil disimpan',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Reload page to reflect changes
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: response.message || 'Gagal menyimpan data kesehatan' });
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="ri-save-line me-1"></i> Simpan Data';
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data kesehatan.';
                Swal.fire({ icon: 'error', title: 'Gagal', html: msg });
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="ri-save-line me-1"></i> Simpan Data';
            }
        });
    }
    </script>
@endsection