@extends('layouts.master')
@section('title')
    Profile Kandidat - {{ $candidate->user->name }}
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
        .private-data { border-radius: 4px; padding: 4px 8px; background: var(--bs-tertiary-bg); color: var(--bs-body-color); font-family: monospace; }
        .profile-wrapper { background: linear-gradient(to right, rgba(var(--bs-primary-rgb), 0.9), rgba(var(--bs-success-rgb), 0.7)); border-radius: 12px; padding: 20px; margin-top: -30px; position: relative; z-index: 10; }
        .education-card, .experience-card { transition: all 0.3s ease; border: 1px solid var(--bs-border-color); }
        .education-card:hover, .experience-card:hover { transform: translateY(-5px); box-shadow: var(--bs-box-shadow); }
        [data-bs-theme="dark"] .profile-wrapper { background: linear-gradient(to right, rgba(13, 110, 253, 0.8), rgba(25, 135, 84, 0.6)); }
        [data-bs-theme="dark"] .private-data { background: rgba(var(--bs-body-bg-rgb), 0.1); }
    </style>
@endsection

@section('content')
    {{-- MODAL VERIFIKASI PASSWORD --}}
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

    {{-- MODAL DATA IDENTITAS --}}
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

    {{-- Profile Background --}}
    <div class="profile-foreground position-relative mx-n4 mt-n4">
        <div class="profile-wid-bg">
            <img src="{{ URL::asset('build/images/auth-one-bg.jpg') }}" alt="Background Profile" class="profile-wid-img" />
            <div class="overlay-content position-absolute bottom-0 start-0 p-4 text-white">
                <h4 class="mb-1">{{ $candidate->user->name }}</h4>
                <p class="mb-0 opacity-75">
                    @foreach ($candidate->skills->take(3) as $skill)
                        {{ $skill->nama_skill }}@if (!$loop->last), @endif
                    @endforeach
                </p>
            </div>
        </div>
    </div>

    {{-- Profile Header --}}
    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper">
        <div class="row g-4">
            <div class="col-auto">
                <div class="avatar-lg position-relative">
                    <img src="{{ $candidate->user->avatar ? URL::asset('images/' . $candidate->user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}"
                        alt="Foto Profil {{ $candidate->user->name }}" class="img-thumbnail rounded-circle shadow" />
                    <span class="position-absolute bottom-0 end-0 badge rounded-circle p-0 border border-3 border-white bg-{{ $candidate->user->is_active ? 'success' : 'danger' }}">
                        <i class="ri-circle-fill fs-24"></i>
                    </span>
                </div>
            </div>
            <div class="col">
                <div class="p-2">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h3 class="text-white mb-0">{{ $candidate->user->name }}</h3>
                        <span class="badge bg-{{ $candidate->user->is_active ? 'success' : 'danger' }}-subtle text-{{ $candidate->user->is_active ? 'success' : 'danger' }}">
                            <i class="ri-user-{{ $candidate->user->is_active ? 'follow' : 'unfollow' }}-line me-1"></i>
                            {{ $candidate->user->is_active ? 'AKTIF' : 'NON-AKTIF' }}
                        </span>
                        <span class="badge bg-info-subtle text-info">
                            <i class="ri-briefcase-line me-1"></i>Kandidat
                        </span>
                    </div>
                    <p class="text-white text-opacity-90 mb-3">
                        <i class="ri-map-pin-user-line align-middle me-1"></i>
                        {{ $candidate->provinsi ?? 'Indonesia' }}
                        @if ($candidate->workExperiences->isNotEmpty())
                            <span class="text-white-75">• {{ $candidate->workExperiences->first()->nama_perusahaan ?? 'Fresh Graduate' }}</span>
                        @endif
                    </p>
                    <div class="text-white d-flex flex-wrap gap-3 text-white-75">
                        @if ($candidate->no_hp)
                            <div class="d-flex align-items-center">
                                <i class="ri-phone-line me-2"></i>
                                <span>{{ $candidate->no_hp }}</span>
                            </div>
                        @endif
                        <div class="d-flex align-items-center">
                            <i class="ri-file-list-line me-2"></i>
                            <span>{{ $candidate->applications->count() }} Lamaran</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="ri-briefcase-line me-2"></i>
                            <span>{{ $candidate->workExperiences->count() }} Pengalaman</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    {{-- Navigation Tabs --}}
                    <div class="d-flex flex-column flex-md-row align-items-md-center border-bottom p-3">
                        <ul class="nav nav-pills gap-2 gap-lg-3 flex-grow-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link fs-14 active" data-bs-toggle="tab" href="#overview-tab" role="tab">
                                    <i class="ri-user-line me-1"></i> Data Pribadi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#pendidikan" role="tab">
                                    <i class="ri-graduation-cap-line me-1"></i> Pendidikan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#pengalaman" role="tab">
                                    <i class="ri-briefcase-line me-1"></i> Pengalaman
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#skills" role="tab">
                                    <i class="ri-price-tag-line me-1"></i> Skills
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#dokumen" role="tab">
                                    <i class="ri-folder-line me-1"></i> Dokumen
                                </a>
                            </li>
                        </ul>
                        <div class="d-flex gap-2 mt-3 mt-md-0">
                            <a href="{{ route('user.ats.candidates.applications', ['userId' => $userId, 'candidate' => $candidate->id]) }}"
                                class="btn btn-success">
                                <i class="ri-file-list-line align-middle me-1"></i> Lihat Lamaran
                            </a>
                            <form action="{{ route('user.ats.candidates.sync-documents', ['userId' => $userId, 'candidate' => $candidate->id]) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-info" title="Sinkronkan dokumen dari recruitment.abuhurairah.id">
                                    <i class="ri-refresh-line align-middle me-1"></i> Sync Dokumen
                                </button>
                            </form>
                            <a href="{{ route('user.ats.candidates.index', ['userId' => $userId]) }}" class="btn btn-light">
                                <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                            </a>
                        </div>
                    </div>

                    {{-- Tab Content --}}
                    <div class="tab-content p-4">

                        {{-- ============================================================
                             TAB 1: DATA PRIBADI
                        ============================================================ --}}
                        <div class="tab-pane active" id="overview-tab" role="tabpanel">
                            <div class="row">
                                <div class="col-xxl-4">
                                    {{-- Profile Completion --}}
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3 d-flex align-items-center">
                                                <i class="ri-information-line text-primary me-2"></i>Status Informasi
                                            </h5>
                                            @php
                                                $totalFields = 10;
                                                $filledFields = 0;
                                                if ($candidate->nik) { $filledFields++; }
                                                if ($candidate->no_kk) { $filledFields++; }
                                                if ($candidate->tempat_lahir) { $filledFields++; }
                                                if ($candidate->tanggal_lahir) { $filledFields++; }
                                                if ($candidate->no_hp) { $filledFields++; }
                                                if ($candidate->alamat_lengkap) { $filledFields++; }
                                                if ($candidate->educations->count() > 0) { $filledFields++; }
                                                if ($candidate->workExperiences->count() > 0) { $filledFields++; }
                                                if ($candidate->skills->count() > 0) { $filledFields++; }
                                                if ($candidate->documents->count() > 0) { $filledFields++; }
                                                $percentage = round(($filledFields / $totalFields) * 100);
                                            @endphp
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="text-muted">Profile Completion</small>
                                                    <small class="fw-semibold">{{ $percentage }}%</small>
                                                </div>
                                                <div class="progress animated-progress custom-progress progress-label">
                                                    <div class="progress-bar bg-{{ $percentage >= 70 ? 'success' : ($percentage >= 40 ? 'warning' : 'danger') }}"
                                                        role="progressbar" style="width: {{ $percentage }}%">
                                                        <div class="label">{{ $percentage }}%</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle"><i class="ri-user-line text-primary"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Status Akun</div>
                                                            <div class="fw-semibold">{{ $candidate->user->is_active ? 'Aktif' : 'Non-Aktif' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle"><i class="ri-calendar-check-line text-success"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Bergabung</div>
                                                            <div class="fw-semibold">{{ $candidate->created_at->format('d M Y') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle"><i class="ri-history-line text-info"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Terakhir Update</div>
                                                            <div class="fw-semibold">{{ $candidate->updated_at->format('d M Y H:i') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle"><i class="ri-file-list-line text-warning"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Total Lamaran</div>
                                                            <div class="fw-semibold">{{ $candidate->applications->count() }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Kontak --}}
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3 d-flex align-items-center">
                                                <i class="ri-contacts-line text-primary me-2"></i>Informasi Kontak
                                            </h5>
                                            <div class="contact-list">
                                                @if ($candidate->no_hp)
                                                    <div class="contact-item d-flex align-items-center">
                                                        <div class="icon-circle me-3"><i class="ri-phone-line text-success"></i></div>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-semibold">No. Handphone</div>
                                                            <div class="text-muted">{{ $candidate->no_hp }}</div>
                                                        </div>
                                                    </div>
                                                @endif
                                                <div class="contact-item d-flex align-items-center">
                                                    <div class="icon-circle me-3"><i class="ri-mail-line text-primary"></i></div>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold">Email</div>
                                                        <div class="text-muted">{{ $candidate->user->email }}</div>
                                                    </div>
                                                </div>
                                                @if ($candidate->alamat_lengkap)
                                                    <div class="contact-item d-flex align-items-center">
                                                        <div class="icon-circle me-3"><i class="ri-map-pin-line text-danger"></i></div>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-semibold">Alamat Lengkap</div>
                                                            <div class="text-muted">{{ $candidate->alamat_lengkap }}</div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Data Identitas (Masked) --}}
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
                                                <div class="detail-value private-data" id="nikDisplay">
                                                    @if ($candidate->nik)
                                                        {{ substr($candidate->nik, 0, 6) . str_repeat('•', strlen($candidate->nik) - 8) . substr($candidate->nik, -2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label detail-label">No. KK</label>
                                                <div class="detail-value private-data" id="kkDisplay">
                                                    @if ($candidate->no_kk)
                                                        {{ substr($candidate->no_kk, 0, 6) . str_repeat('•', strlen($candidate->no_kk) - 8) . substr($candidate->no_kk, -2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </div>
                                            <button class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#passwordModal">
                                                <i class="ri-eye-line me-1"></i> Tampilkan Data Identitas
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xxl-8">
                                    {{-- Informasi Pribadi --}}
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-4 d-flex align-items-center">
                                                <i class="ri-profile-line text-primary me-2"></i>Informasi Pribadi
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Nama Lengkap</label>
                                                        <div class="detail-value">{{ $candidate->user->name }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Email</label>
                                                        <div class="detail-value">{{ $candidate->user->email }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tempat Lahir</label>
                                                        <div class="detail-value">{{ $candidate->tempat_lahir ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tanggal Lahir</label>
                                                        <div class="detail-value">
                                                            @if ($candidate->tanggal_lahir)
                                                                {{ \Carbon\Carbon::parse($candidate->tanggal_lahir)->format('d F Y') }}
                                                                <span class="text-muted ms-2">({{ \Carbon\Carbon::parse($candidate->tanggal_lahir)->age }} tahun)</span>
                                                            @else
                                                                -
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Jenis Kelamin</label>
                                                        <div class="detail-value">
                                                            @if ($candidate->jenis_kelamin)
                                                                <span class="badge bg-{{ $candidate->jenis_kelamin == 'L' ? 'primary' : 'danger' }}-subtle text-{{ $candidate->jenis_kelamin == 'L' ? 'primary' : 'danger' }}">
                                                                    <i class="ri-{{ $candidate->jenis_kelamin == 'L' ? 'men' : 'women' }}-line me-1"></i>
                                                                    {{ $candidate->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}
                                                                </span>
                                                            @else - @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Agama</label>
                                                        <div class="detail-value">{{ $candidate->agama ? ucfirst($candidate->agama) : '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Status Perkawinan</label>
                                                        <div class="detail-value">{{ $candidate->status_perkawinan ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Provinsi</label>
                                                        <div class="detail-value">{{ $candidate->provinsi ?? '-' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- About / Ringkasan --}}
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3 d-flex align-items-center">
                                                <i class="ri-file-text-line text-primary me-2"></i>Ringkasan Profil
                                            </h5>
                                            @php
                                                $cv = $candidate->documents->where('jenis_dokumen', 'cv')->first();
                                            @endphp
                                            <p class="mb-0">{{ $cv->ringkasan_profesional ?? 'Belum ada ringkasan profesional.' }}</p>
                                        </div>
                                    </div>

                                    {{-- Aktivitas Terakhir --}}
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3 d-flex align-items-center">
                                                <i class="ri-time-line text-primary me-2"></i>Aktivitas Terakhir
                                            </h5>
                                            @if ($candidate->applications->count() > 0)
                                                <div class="acitivity-timeline">
                                                    @foreach ($candidate->applications->sortByDesc('created_at')->take(5) as $app)
                                                        <div class="acitivity-item d-flex py-2">
                                                            <div class="flex-shrink-0 avatar-xs">
                                                                <div class="avatar-title rounded-circle bg-{{ match($app->status) { 'accepted' => 'success', 'rejected' => 'danger', 'under_review' => 'info', default => 'warning' } }}-subtle text-{{ match($app->status) { 'accepted' => 'success', 'rejected' => 'danger', 'under_review' => 'info', default => 'warning' } }}">
                                                                    <i class="ri-file-copy-line"></i>
                                                                </div>
                                                            </div>
                                                            <div class="flex-grow-1 ms-3">
                                                                <h6 class="mb-1">Melamar sebagai {{ $app->recruitmentJob->judul ?? '-' }}</h6>
                                                                <p class="text-muted mb-1">
                                                                    Status:
                                                                    <span class="badge bg-{{ match($app->status) { 'accepted' => 'success', 'rejected' => 'danger', 'under_review' => 'info', default => 'warning' } }}-subtle text-{{ match($app->status) { 'accepted' => 'success', 'rejected' => 'danger', 'under_review' => 'info', default => 'warning' } }}">
                                                                        {{ ucfirst(str_replace('_', ' ', $app->status)) }}
                                                                    </span>
                                                                </p>
                                                                <small class="text-muted">{{ $app->created_at->diffForHumans() }}</small>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-center py-3">
                                                    <p class="text-muted mb-0"><i class="ri-inbox-line me-1"></i>Belum ada aktivitas lamaran</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 2: PENDIDIKAN
                        ============================================================ --}}
                        <div class="tab-pane fade" id="pendidikan" role="tabpanel">
                            @if ($candidate->educations->count() > 0)
                                <div class="row">
                                    @foreach ($candidate->educations->sortByDesc('tahun_lulus') as $edu)
                                        <div class="col-xl-6 col-lg-6 mb-4">
                                            <div class="card education-card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start mb-3">
                                                        <div class="avatar-md me-3 flex-shrink-0">
                                                            <div class="avatar-title bg-{{ $edu->jenjang == 's1' ? 'success' : 'info' }}-subtle rounded">
                                                                <i class="ri-graduation-cap-line fs-24 text-{{ $edu->jenjang == 's1' ? 'success' : 'info' }}"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h5 class="mb-1">{{ $edu->jenjang }} - {{ $edu->jurusan ?? '-' }}</h5>
                                                            <p class="text-muted mb-2">{{ $edu->nama_satuan_pendidikan }}</p>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                @if ($edu->tahun_lulus)
                                                                    <span class="badge bg-light text-dark">
                                                                        <i class="ri-calendar-line me-1"></i>{{ $edu->tahun_masuk ?? '-' }} - {{ $edu->tahun_lulus }}
                                                                    </span>
                                                                @endif
                                                                @if ($edu->ipk || $edu->nilai_akhir)
                                                                    <span class="badge bg-light text-dark">
                                                                        <i class="ri-star-line me-1"></i>{{ $edu->ipk ?? $edu->nilai_akhir }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="border-top pt-2">
                                                        <div class="row g-2">
                                                            @if ($edu->no_ijazah)
                                                                <div class="col-12">
                                                                    <small class="text-muted"><i class="ri-file-text-line me-1"></i>No. Ijazah: {{ $edu->no_ijazah }}</small>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if ($edu->ijazah_path)
                                                        <div class="mt-3">
                                                            <a href="{{ asset('storage/' . $edu->ijazah_path) }}" target="_blank"
                                                                class="btn btn-sm btn-soft-primary">
                                                                <i class="ri-file-pdf-line me-1"></i> Lihat Ijazah
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-4"><i class="ri-graduation-cap-line text-muted" style="font-size: 5rem;"></i></div>
                                    <h5 class="text-muted mb-3">Riwayat Pendidikan Belum Tersedia</h5>
                                    <p class="text-muted mb-4">Informasi pendidikan untuk kandidat ini belum diinput.</p>
                                </div>
                            @endif
                        </div>

                        {{-- ============================================================
                             TAB 3: PENGALAMAN
                        ============================================================ --}}
                        <div class="tab-pane fade" id="pengalaman" role="tabpanel">
                            @if ($candidate->workExperiences->count() > 0)
                                <div class="row">
                                    @foreach ($candidate->workExperiences as $exp)
                                        <div class="col-xl-6 col-lg-6 mb-4">
                                            <div class="card experience-card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start mb-3">
                                                        <div class="avatar-md me-3 flex-shrink-0">
                                                            <div class="avatar-title bg-primary-subtle rounded">
                                                                <i class="ri-briefcase-line fs-24 text-primary"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h5 class="mb-1">{{ $exp->posisi_terakhir }}</h5>
                                                            <p class="text-muted mb-2">{{ $exp->nama_perusahaan }}</p>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                <span class="badge bg-light text-dark">
                                                                    <i class="ri-calendar-line me-1"></i>
                                                                    {{ $exp->tanggal_mulai->format('M Y') }} -
                                                                    {{ $exp->is_saat_ini ? 'Sekarang' : ($exp->tanggal_selesai ? $exp->tanggal_selesai->format('M Y') : '-') }}
                                                                </span>
                                                                @if ($exp->lama_bekerja_bulan)
                                                                    <span class="badge bg-light text-dark">
                                                                        <i class="ri-time-line me-1"></i>
                                                                        {{ floor($exp->lama_bekerja_bulan / 12) }} th {{ $exp->lama_bekerja_bulan % 12 }} bln
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @if ($exp->jobdesc)
                                                        <div class="border-top pt-2 mb-2">
                                                            <small class="text-muted"><i class="ri-list-check me-1"></i>Jobdesc:</small>
                                                            <p class="mb-0 mt-1">{{ $exp->jobdesc }}</p>
                                                        </div>
                                                    @endif
                                                    @if ($exp->pencapaian)
                                                        <div class="border-top pt-2">
                                                            <small class="text-muted"><i class="ri-trophy-line me-1"></i>Pencapaian:</small>
                                                            <ul class="mb-0 mt-1" style="padding-left: 1.2rem;">
                                                                @foreach (json_decode($exp->pencapaian) as $achievement)
                                                                    <li><small>{{ $achievement }}</small></li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-4"><i class="ri-briefcase-line text-muted" style="font-size: 5rem;"></i></div>
                                    <h5 class="text-muted mb-3">Riwayat Pekerjaan Belum Tersedia</h5>
                                    <p class="text-muted mb-4">Kandidat ini belum memiliki pengalaman kerja.</p>
                                </div>
                            @endif
                        </div>

                        {{-- ============================================================
                             TAB 4: SKILLS
                        ============================================================ --}}
                        <div class="tab-pane fade" id="skills" role="tabpanel">
                            <div class="row">
                                {{-- Technical Skills --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title mb-4 d-flex align-items-center">
                                                <i class="ri-tools-line text-primary me-2"></i>Technical Skills
                                            </h5>
                                            @php $hasTechnical = false; @endphp
                                            @foreach ($candidate->skills->where('kategori', 'teknis') as $skill)
                                                @php $hasTechnical = true; @endphp
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span>{{ $skill->nama_skill }}</span>
                                                        <span class="text-muted">{{ $skill->level ?? '-' }}</span>
                                                    </div>
                                                    <div class="progress" style="height: 5px;">
                                                        @php
                                                            $levelValue = match ($skill->level) {
                                                                'Pemula' => 30,
                                                                'Menengah' => 60,
                                                                'Ahli' => 90,
                                                                default => 50,
                                                            };
                                                        @endphp
                                                        <div class="progress-bar bg-success" style="width: {{ $levelValue }}%"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @if (!$hasTechnical)
                                                <p class="text-muted"><i class="ri-information-line me-1"></i>Belum ada technical skill</p>
                                            @endif

                                            <hr class="my-4">

                                            <h6 class="mb-3"><i class="ri-award-line me-2 text-warning"></i>Pelatihan & Sertifikasi</h6>
                                            @forelse($candidate->trainings as $training)
                                                <div class="d-flex mb-3">
                                                    <div class="avatar-sm flex-shrink-0 me-3">
                                                        <div class="avatar-title bg-warning-subtle rounded">
                                                            <i class="ri-medal-2-line text-warning fs-20"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="fs-15 mb-1">{{ $training->nama_pelatihan }}</h6>
                                                        <p class="text-muted mb-1">{{ $training->penyelenggara }} - {{ $training->tahun }}</p>
                                                        @if ($training->sertifikat_path)
                                                            <a href="{{ asset('storage/' . $training->sertifikat_path) }}"
                                                                target="_blank" class="btn btn-sm btn-soft-warning">
                                                                <i class="ri-file-pdf-line me-1"></i> Lihat Sertifikat
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-muted"><i class="ri-information-line me-1"></i>Belum ada pelatihan</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                {{-- Soft Skills & Bahasa --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title mb-4 d-flex align-items-center">
                                                <i class="ri-team-line text-info me-2"></i>Soft Skills
                                            </h5>
                                            @if ($candidate->skills->where('kategori', 'non_teknis')->count() > 0)
                                                <div class="d-flex flex-wrap gap-2 mb-4">
                                                    @foreach ($candidate->skills->where('kategori', 'non_teknis') as $skill)
                                                        <span class="badge bg-info-subtle text-info p-2">
                                                            {{ $skill->nama_skill }}
                                                            @if ($skill->level)
                                                                <span class="ms-1">({{ $skill->level }})</span>
                                                            @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-muted mb-4"><i class="ri-information-line me-1"></i>Belum ada soft skill</p>
                                            @endif

                                            <hr class="my-4">

                                            <h5 class="card-title mb-4 d-flex align-items-center">
                                                <i class="ri-global-line text-primary me-2"></i>Bahasa
                                            </h5>
                                            @php $hasBahasa = false; @endphp
                                            @foreach ($candidate->skills->where('kategori', 'bahasa') as $skill)
                                                @php $hasBahasa = true; @endphp
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span>{{ $skill->nama_skill }}</span>
                                                        <span class="text-muted">{{ $skill->level ?? '-' }}</span>
                                                    </div>
                                                    <div class="progress" style="height: 5px;">
                                                        @php
                                                            $levelValue = match ($skill->level) {
                                                                'Dasar' => 30,
                                                                'Menengah' => 60,
                                                                'Fluent' => 90,
                                                                default => 50,
                                                            };
                                                        @endphp
                                                        <div class="progress-bar bg-primary" style="width: {{ $levelValue }}%"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @if (!$hasBahasa)
                                                <p class="text-muted"><i class="ri-information-line me-1"></i>Belum ada data bahasa</p>
                                            @endif

                                            <hr class="my-4">

                                            <h5 class="card-title mb-4 d-flex align-items-center">
                                                <i class="ri-vip-diamond-line text-warning me-2"></i>Sertifikasi
                                            </h5>
                                            @php $hasSertifikasi = false; @endphp
                                            @foreach ($candidate->skills->where('kategori', 'sertifikasi') as $skill)
                                                @php $hasSertifikasi = true; @endphp
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="ri-shield-check-line text-success me-2"></i>
                                                    <span>{{ $skill->nama_skill }}</span>
                                                    @if ($skill->level)
                                                        <span class="badge bg-success-subtle text-success ms-2">{{ $skill->level }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if (!$hasSertifikasi)
                                                <p class="text-muted"><i class="ri-information-line me-1"></i>Belum ada sertifikasi</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 5: DOKUMEN (External — recruitment.abuhurairah.id)
                        ============================================================ --}}
                        <div class="tab-pane fade" id="dokumen" role="tabpanel">
                            {{-- Info Banner --}}
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="alert alert-info border-0 mb-0" role="alert">
                                        <i class="ri-cloud-line me-2"></i>
                                        <strong>Informasi:</strong> Dokumen dan foto pelamar disimpan di
                                        <a href="https://recruitment.abuhurairah.id" target="_blank" rel="noopener" class="alert-link">recruitment.abuhurairah.id</a>.
                                        Klik tombol di bawah untuk mengambil data dokumen terbaru.
                                    </div>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <button type="button" onclick="syncDocuments({{ $candidate->id }})"
                                                class="btn btn-primary w-100" id="btnSyncAll">
                                                <i class="ri-refresh-line me-1"></i> Sync Dokumen
                                            </button>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" onclick="syncPhoto({{ $candidate->id }})"
                                                class="btn btn-success w-100" id="btnSyncPhoto">
                                                <i class="ri-camera-line me-1"></i> Sync Foto Profil
                                            </button>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="https://recruitment.abuhurairah.id" target="_blank" rel="noopener"
                                                class="btn btn-outline-info w-100">
                                                <i class="ri-external-link-line me-1"></i> Buka Recruitment App
                                            </a>
                                        </div>
                                    </div>
                                    {{-- Status Sync --}}
                                    <div id="syncStatus" class="mt-3 d-none">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                        </div>
                                        <small class="text-muted mt-1 d-block" id="syncStatusText">Mengambil data dari recruitment.abuhurairah.id...</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Photo Section --}}
                            @if ($candidate->foto_url_external)
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0 d-flex align-items-center">
                                            <i class="ri-camera-line text-success me-2"></i>Foto Profil
                                        </h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <img src="{{ $candidate->foto_url_external }}"
                                             alt="Foto {{ $candidate->user->name }}"
                                             class="img-thumbnail rounded-circle"
                                             style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="mt-2">
                                            <a href="{{ $candidate->foto_url_external }}" target="_blank" rel="noopener"
                                                class="btn btn-sm btn-soft-success">
                                                <i class="ri-external-link-line"></i> Buka di recruitment.abuhurairah.id
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Documents List --}}
                            @php
                                // Prioritaskan dokumen external_url, fallback ke local
                                $docs = $candidate->documents
                                    ->sortByDesc('is_external')
                                    ->sortByDesc('is_primary')
                                    ->sortBy('jenis_dokumen');
                                $hasExternalDocs = $candidate->documents->where('is_external', true)->isNotEmpty();
                            @endphp

                            @if ($docs->count() > 0)
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <h6 class="card-title flex-grow-1 mb-0 d-flex align-items-center">
                                                <i class="ri-folder-line text-primary me-2"></i>Dokumen Terlampir
                                            </h6>
                                            <div class="d-flex gap-2">
                                                @if ($hasExternalDocs)
                                                    <span class="badge bg-info"><i class="ri-cloud-line me-1"></i>{{ $candidate->documents->where('is_external', true)->count() }} External</span>
                                                @endif
                                                <span class="badge bg-primary">{{ $docs->count() }} Dokumen</span>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-borderless align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Nama File</th>
                                                        <th>Jenis</th>
                                                        <th>Ukuran</th>
                                                        <th>Status</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($docs as $doc)
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="avatar-sm flex-shrink-0">
                                                                        <div class="avatar-title bg-{{ $doc->jenis_dokumen == 'cv' ? 'primary' : 'info' }}-subtle rounded fs-20">
                                                                            <i class="ri-file-{{ $doc->file_extension ?? 'document' }}-line text-{{ $doc->jenis_dokumen == 'cv' ? 'primary' : 'info' }}"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="ms-3">
                                                                        <h6 class="fs-15 mb-0">{{ $doc->nama_dokumen }}</h6>
                                                                        @if ($doc->is_primary)
                                                                            <span class="badge bg-success">Primary</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-{{ $doc->jenis_dokumen == 'cv' ? 'primary' : 'info' }}-subtle text-{{ $doc->jenis_dokumen == 'cv' ? 'primary' : 'info' }}">
                                                                    {{ strtoupper($doc->jenis_dokumen) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                @if ($doc->file_size)
                                                                    @php $size = number_format($doc->file_size / 1024, 1); @endphp
                                                                    {{ $size }} KB
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($doc->is_external)
                                                                    <span class="badge bg-info">
                                                                        <i class="ri-cloud-line"></i> External
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-secondary">Local</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($doc->is_external && $doc->external_url)
                                                                    <a href="{{ $doc->external_url }}" target="_blank" rel="noopener"
                                                                        class="btn btn-sm btn-soft-info" title="Buka di recruitment.abuhurairah.id">
                                                                        <i class="ri-external-link-line"></i>
                                                                    </a>
                                                                @elseif ($doc->file_path)
                                                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank"
                                                                        class="btn btn-sm btn-soft-primary" title="Download">
                                                                        <i class="ri-download-line"></i>
                                                                    </a>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-4"><i class="ri-folder-upload-line text-muted" style="font-size: 5rem;"></i></div>
                                    <h5 class="text-muted mb-3">Belum Ada Dokumen</h5>
                                    <p class="text-muted mb-4">Klik tombol <strong>"Sync Dokumen"</strong> di atas untuk mengambil data dari recruitment.abuhurairah.id</p>
                                    <button type="button" onclick="syncDocuments({{ $candidate->id }})" class="btn btn-primary">
                                        <i class="ri-refresh-line"></i> Sync Sekarang
                                    </button>
                                </div>
                            @endif
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
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
    /* ==========================================================================
       COPY TO CLIPBOARD
       ========================================================================== */
    function copyToClipboard(elementId, label) {
        const text = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(text).then(function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: label + ' berhasil disalin ke clipboard', timer: 2000, showConfirmButton: false });
            } else {
                alert(label + ' berhasil disalin ke clipboard');
            }
        }).catch(function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menyalin ke clipboard' });
            } else {
                alert('Gagal menyalin ke clipboard');
            }
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
                url: '{{ route('user.ats.candidates.verify-password', ['userId' => $userId, 'candidate' => $candidate->id]) }}',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', password: password },
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

        $('#password').on('input', function () {
            $(this).removeClass('is-invalid');
        });
    });

    /* ==========================================================================
       SYNC DOKUMEN DAN FOTO PROFIL dari recruitment.abuhurairah.id
       ========================================================================== */
    function showSyncStatus() {
        $('#syncStatus').removeClass('d-none');
        $('#btnSyncAll, #btnSyncPhoto').prop('disabled', true).addClass('opacity-50');
    }

    function hideSyncStatus() {
        $('#syncStatus').addClass('d-none');
        $('#btnSyncAll, #btnSyncPhoto').prop('disabled', false).removeClass('opacity-50');
    }

    function syncDocuments(candidateId) {
        showSyncStatus();
        $('#syncStatusText').text('Mengambil data dokumen dari recruitment.abuhurairah.id...');

        $.ajax({
            url: '{{ route('recruitment.sync-documents', ['userId' => $userId, 'candidate' => $candidate->id]) }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message || 'Dokumen berhasil disinkronkan.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                    // Reload halaman untuk update tab dokumen
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    hideSyncStatus();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: response.message || 'Gagal menyinkronkan dokumen.' });
                    }
                }
            },
            error: function(xhr) {
                hideSyncStatus();
                var msg = 'Gagal menyinkronkan dokumen.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }
            }
        });
    }

    function syncPhoto(candidateId) {
        showSyncStatus();
        $('#syncStatusText').text('Mengambil foto profil dari recruitment.abuhurairah.id...');

        $.ajax({
            url: '{{ route('recruitment.sync-photo', ['userId' => $userId, 'candidate' => $candidate->id]) }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Foto profil berhasil disinkronkan.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    hideSyncStatus();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: response.message || 'Gagal menyinkronkan foto profil.' });
                    }
                }
            },
            error: function(xhr) {
                hideSyncStatus();
                var msg = 'Gagal menyinkronkan foto profil.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }
            }
        });
    }

    </script>
@endsection
