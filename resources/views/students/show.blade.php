@extends('layouts.master')
@section('title') Detail Santri - {{ $student->name }} @endsection
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
        .family-member-card { transition: all 0.3s ease; border: 1px solid var(--bs-border-color); }
        .family-member-card:hover { transform: translateY(-5px); box-shadow: var(--bs-box-shadow); }
        .profile-wrapper { background: linear-gradient(to right, rgba(var(--bs-primary-rgb), 0.9), rgba(var(--bs-info-rgb), 0.7)); border-radius: 12px; padding: 20px; margin-top: -30px; position: relative; z-index: 10; }
        [data-bs-theme="dark"] .profile-wrapper { background: linear-gradient(to right, rgba(13, 110, 253, 0.8), rgba(13, 202, 240, 0.6)); }
        [data-bs-theme="dark"] .profile-wid-bg img { filter: brightness(0.4); }
    </style>
@endsection

@section('content')
    <!-- MODAL VERIFIKASI PASSWORD (NIK / KK) -->
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
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password Anda">
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

    <!-- MODAL DATA IDENTITAS TERBUKA -->
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
                                    <div class="card-text"><code class="fs-5" id="nikData">{{ $student->nik ?? '–' }}</code></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body text-center">
                                    <div class="mb-2"><i class="ri-home-4-line fs-2 text-success"></i></div>
                                    <h6 class="card-title mb-1">No. KK</h6>
                                    <div class="card-text"><code class="fs-5" id="kkData">{{ $student->no_kk ?? '–' }}</code></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        @if($student->nik)
                        <button class="btn btn-outline-primary btn-sm" onclick="copyToClipboard('nikData', 'NIK')">
                            <i class="ri-file-copy-line me-1"></i> Salin NIK
                        </button>
                        @endif
                        @if($student->no_kk)
                        <button class="btn btn-outline-success btn-sm ms-2" onclick="copyToClipboard('kkData', 'No. KK')">
                            <i class="ri-file-copy-line me-1"></i> Salin No. KK
                        </button>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    @php
        $masked_nik   = $student->nik   ? (strlen($student->nik) >= 10 ? substr($student->nik, 0, 6) . str_repeat('•', strlen($student->nik) - 10) . substr($student->nik, -4) : str_repeat('•', strlen($student->nik))) : str_repeat('•', 16);
        $masked_no_kk = $student->no_kk ? (strlen($student->no_kk) >= 10 ? substr($student->no_kk, 0, 6) . str_repeat('•', strlen($student->no_kk) - 10) . substr($student->no_kk, -4) : str_repeat('•', strlen($student->no_kk))) : str_repeat('•', 16);

        $activeHistory = $student->classHistories->where('is_active', true)->first();
        $currentRombel = $activeHistory?->studyGroup;
    @endphp

    <!-- Header Profile Background -->
    <div class="profile-foreground position-relative mx-n4 mt-n4">
        <div class="profile-wid-bg">
            <img src="{{ URL::asset('build/images/auth-one-bg.jpg') }}" alt="Background Profile" class="profile-wid-img" />
            <div class="overlay-content position-absolute bottom-0 start-0 p-4 text-white">
                <h4 class="mb-1">{{ $student->name }}</h4>
                <p class="mb-0 opacity-75">
                    {{ $currentRombel ? $currentRombel->full_name : 'Belum masuk rombel' }}
                    @if ($student->school) • {{ $student->school->name }} @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Profile Stats -->
    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper">
        <div class="row g-4">
            <div class="col-auto">
                <div class="avatar-lg position-relative">
                    @if($student->photo_path)
                        <img src="{{ asset('storage/' . $student->photo_path) }}"
                            alt="Foto Profil {{ $student->name }}"
                            class="img-thumbnail rounded-circle shadow" />
                    @else
                        <div class="avatar-lg">
                            <span class="avatar-title rounded-circle fs-2 fw-bold bg-{{ $student->gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $student->gender === 'P' ? 'danger' : 'primary' }}">
                                {{ strtoupper(substr($student->name, 0, 2)) }}
                            </span>
                        </div>
                    @endif
                    <span class="position-absolute bottom-0 end-0 badge rounded-circle p-0 border border-3 border-white bg-{{ $student->status === 'active' ? 'success' : ($student->status === 'graduate' ? 'info' : 'danger') }}">
                        <i class="ri-circle-fill fs-24"></i>
                    </span>
                </div>
            </div>
            <div class="col">
                <div class="p-2">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h3 class="text-white mb-0">{{ $student->name }}</h3>
                        <span class="badge bg-{{ $student->status === 'active' ? 'success' : ($student->status === 'graduate' ? 'info' : 'secondary') }}-subtle text-{{ $student->status === 'active' ? 'success' : ($student->status === 'graduate' ? 'info' : 'secondary') }}">
                            <i class="ri-user-{{ $student->status === 'active' ? 'follow' : 'unfollow' }}-line me-1"></i>
                            {{ $student->status_text }}
                        </span>
                        @if($currentRombel)
                            <span class="badge bg-info-subtle text-info">
                                <i class="ri-group-line me-1"></i>{{ $currentRombel->full_name }}
                            </span>
                        @endif
                    </div>
                    <p class="text-white text-opacity-90 mb-3">
                        <i class="ri-user-line align-middle me-1"></i>
                        {{ $student->gender_text }}
                        @if($student->birth_place)
                            <span class="text-white-75">• {{ $student->birth_place }}, {{ $student->birth_date?->format('d M Y') }}</span>
                        @endif
                    </p>
                    <div class="text-white d-flex flex-wrap gap-3 text-white-75">
                        <div class="d-flex align-items-center">
                            <i class="ri-bookmark-line me-2"></i>
                            <span> NISN: <code class="text-white">{{ $student->nisn }}</code></span>
                        </div>
                        @if($student->nis)
                            <div class="d-flex align-items-center">
                                <i class="ri-file-list-line me-2"></i>
                                <span>NIS: <code class="text-white">{{ $student->nis }}</code></span>
                            </div>
                        @endif
                        @if($student->mobile_phone)
                            <div class="d-flex align-items-center">
                                <i class="ri-phone-line me-2"></i>
                                <span>{{ $student->mobile_phone }}</span>
                            </div>
                        @endif
                        @if($student->school)
                            <div class="d-flex align-items-center">
                                <i class="ri-building-2-line me-2"></i>
                                <span>{{ $student->school->name }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Tombol: rata kanan --}}
            <div class="col-auto d-flex align-items-center">
                <div class="d-flex gap-2">
                    <a href="{{ route('user.students.edit', ['userId' => $userId, 'santriUuid' => $student->id]) }}" class="btn btn-success">
                        <i class="ri-edit-box-line align-middle me-1"></i> Edit Data
                    </a>
                    <a href="{{ route('user.students.index', ['userId' => $userId]) }}" class="btn btn-light">
                        <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                    </a>
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
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#alamat-tab" role="tab">
                                    <i class="ri-home-line me-1"></i> Alamat
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#keluarga-tab" role="tab">
                                    <i class="ri-parent-line me-1"></i> Keluarga
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#kesehatan-tab" role="tab">
                                    <i class="ri-heart-line me-1"></i> Kesehatan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#pendaftaran-tab" role="tab">
                                    <i class="ri-file-list-line me-1"></i> Pendaftaran
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#rombel-tab" role="tab">
                                    <i class="ri-group-line me-1"></i> Rombel
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#prestasi-tab" role="tab">
                                    <i class="ri-trophy-line me-1"></i> Prestasi
                                </a>
                            </li>
                        </ul>
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
                                                            <div class="fs-12 text-muted">Status</div>
                                                            <div class="fw-semibold">{{ $student->status_text }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle"><i class="ri-calendar-check-line text-success"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Terdaftar</div>
                                                            <div class="fw-semibold">{{ $student->created_at->format('d M Y') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle"><i class="ri-history-line text-info"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Terakhir Update</div>
                                                            <div class="fw-semibold">{{ $student->updated_at->format('d M Y H:i') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle"><i class="ri-graduation-cap-line text-warning"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Tingkat Masuk</div>
                                                            <div class="fw-semibold">{{ $student->entry_grade_level ? 'Kelas ' . $student->entry_grade_level : '-' }}</div>
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
                                            <div class="contact-list">
                                                @if($student->mobile_phone)
                                                <div class="contact-item d-flex align-items-center">
                                                    <div class="icon-circle me-3"><i class="ri-smartphone-line text-success"></i></div>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold">No. HP</div>
                                                        <div class="text-muted">{{ $student->mobile_phone }}</div>
                                                    </div>
                                                </div>
                                                @endif
                                                @if($student->phone)
                                                <div class="contact-item d-flex align-items-center">
                                                    <div class="icon-circle me-3"><i class="ri-phone-line text-primary"></i></div>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold">Telepon</div>
                                                        <div class="text-muted">{{ $student->phone }}</div>
                                                    </div>
                                                </div>
                                                @endif
                                                @if($student->email)
                                                <div class="contact-item d-flex align-items-center">
                                                    <div class="icon-circle me-3"><i class="ri-mail-line text-info"></i></div>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold">Email</div>
                                                        <div class="text-muted">{{ $student->email }}</div>
                                                    </div>
                                                </div>
                                                @endif
                                                @if(!$student->mobile_phone && !$student->phone && !$student->email)
                                                    <div class="alert alert-light"><i class="ri-information-line me-1"></i>Informasi kontak belum tersedia.</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Data Identitas (dilindungi) -->
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
                                                <div class="detail-value" id="nikDisplay">{{ $masked_nik }}</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label detail-label">No. KK</label>
                                                <div class="detail-value" id="kkDisplay">{{ $masked_no_kk }}</div>
                                            </div>
                                            @if($student->nik || $student->no_kk)
                                            <button class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#identityModal">
                                                <i class="ri-eye-line me-1"></i> Tampilkan Data Identitas
                                            </button>
                                            @endif
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
                                                        <div class="detail-value">{{ $student->name }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">NISN</label>
                                                        <div class="detail-value"><code>{{ $student->nisn }}</code></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">NIS</label>
                                                        <div class="detail-value">{{ $student->nis ?: '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Jenis Kelamin</label>
                                                        <div class="detail-value">
                                                            <span class="badge bg-{{ $student->gender === 'L' ? 'primary' : 'danger' }}-subtle text-{{ $student->gender === 'L' ? 'primary' : 'danger' }}">
                                                                <i class="ri-{{ $student->gender === 'L' ? 'men' : 'women' }}-line me-1"></i>
                                                                {{ $student->gender_text }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tempat Lahir</label>
                                                        <div class="detail-value">{{ $student->birth_place ?: '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tanggal Lahir</label>
                                                        <div class="detail-value">{{ $student->birth_date?->format('d F Y') ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Agama</label>
                                                        <div class="detail-value">{{ $student->religion ? ucfirst($student->religion) : '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Kebutuhan Khusus</label>
                                                        <div class="detail-value">{{ $student->special_needs_text }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tempat Tinggal</label>
                                                        <div class="detail-value">{{ $student->residence_type ? ucfirst(str_replace('_', ' ', $student->residence_type)) : '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Transportasi</label>
                                                        <div class="detail-value">{{ $student->transportation ? ucfirst(str_replace('_', ' ', $student->transportation)) : '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Jarak ke Sekolah</label>
                                                        <div class="detail-value">{{ $student->distance_to_school ? $student->distance_to_school . ' km' : '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Asal Sekolah</label>
                                                        <div class="detail-value">{{ $student->previous_school ?: '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tingkat Masuk</label>
                                                        <div class="detail-value">{{ $student->entry_grade_level ? 'Kelas ' . $student->entry_grade_level : '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tanggal Masuk</label>
                                                        <div class="detail-value">{{ $student->entry_date ? \Carbon\Carbon::parse($student->entry_date)->format('d/m/Y') : '-' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Data Ayah -->
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3 d-flex align-items-center">
                                                <i class="ri-user-location-line text-primary me-2"></i>Data Ayah
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Nama</label><div class="detail-value">{{ $student->father_name ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">NIK</label><div class="detail-value">{{ $student->father_nik ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Tahun Lahir</label><div class="detail-value">{{ $student->father_birth_year ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Pendidikan</label><div class="detail-value">{{ $student->father_education ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Pekerjaan</label><div class="detail-value">{{ $student->father_occupation ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Penghasilan</label><div class="detail-value">{{ $student->father_income ? 'Rp ' . number_format($student->father_income, 0, ',', '.') : '-' }}</div></div></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Data Ibu -->
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3 d-flex align-items-center">
                                                <i class="ri-user-heart-line text-danger me-2"></i>Data Ibu
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Nama</label><div class="detail-value">{{ $student->mother_name ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">NIK</label><div class="detail-value">{{ $student->mother_nik ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Tahun Lahir</label><div class="detail-value">{{ $student->mother_birth_year ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Pendidikan</label><div class="detail-value">{{ $student->mother_education ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Pekerjaan</label><div class="detail-value">{{ $student->mother_occupation ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Penghasilan</label><div class="detail-value">{{ $student->mother_income ? 'Rp ' . number_format($student->mother_income, 0, ',', '.') : '-' }}</div></div></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Data Wali -->
                                    @if($student->guardian_name)
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3 d-flex align-items-center">
                                                <i class="ri-user-star-line text-warning me-2"></i>Data Wali
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Nama</label><div class="detail-value">{{ $student->guardian_name }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">NIK</label><div class="detail-value">{{ $student->guardian_nik ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Tahun Lahir</label><div class="detail-value">{{ $student->guardian_birth_year ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Pendidikan</label><div class="detail-value">{{ $student->guardian_education ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Pekerjaan</label><div class="detail-value">{{ $student->guardian_occupation ?: '-' }}</div></div></div>
                                                <div class="col-md-4"><div class="mb-3"><label class="form-label detail-label">Penghasilan</label><div class="detail-value">{{ $student->guardian_income ? 'Rp ' . number_format($student->guardian_income, 0, ',', '.') : '-' }}</div></div></div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 2: ALAMAT
                        ============================================================ --}}
                        <div class="tab-pane fade" id="alamat-tab" role="tabpanel">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-4 d-flex align-items-center">
                                        <i class="ri-home-4-line text-primary me-2"></i>Alamat Lengkap
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="address-card p-4 rounded mb-4">
                                                <div class="mb-3"><strong>{{ $student->address ?: '-' }}</strong></div>
                                                <div class="row g-2">
                                                    @if($student->rt || $student->rw)
                                                        <div class="col-6"><div class="text-muted small">RT/RW:</div><div>{{ $student->rt }}/{{ $student->rw }}</div></div>
                                                    @endif
                                                    @if($student->hamlet)
                                                        <div class="col-6"><div class="text-muted small">Dusun:</div><div>{{ $student->hamlet }}</div></div>
                                                    @endif
                                                    @if($student->village)
                                                        <div class="col-6"><div class="text-muted small">Desa/Kelurahan:</div><div>{{ $student->village->name }}</div></div>
                                                    @endif
                                                    @if($student->district)
                                                        <div class="col-6"><div class="text-muted small">Kecamatan:</div><div>{{ $student->district->name }}</div></div>
                                                    @endif
                                                    @if($student->city)
                                                        <div class="col-6"><div class="text-muted small">Kabupaten/Kota:</div><div>{{ $student->city->name }}</div></div>
                                                    @endif
                                                    @if($student->province)
                                                        <div class="col-6"><div class="text-muted small">Provinsi:</div><div>{{ $student->province->name }}</div></div>
                                                    @endif
                                                    @if($student->postal_code)
                                                        <div class="col-6"><div class="text-muted small">Kode Pos:</div><div>{{ $student->postal_code }}</div></div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-3"><i class="ri-map-pin-2-line me-2 text-primary"></i>Ringkasan</h6>
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="icon-circle me-3"><i class="ri-user-location-line text-success"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Tempat Tinggal</div>
                                                            <div class="fw-semibold">{{ $student->residence_type ? ucfirst(str_replace('_', ' ', $student->residence_type)) : '-' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="icon-circle me-3"><i class="ri-car-line text-info"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Transportasi</div>
                                                            <div class="fw-semibold">{{ $student->transportation ? ucfirst(str_replace('_', ' ', $student->transportation)) : '-' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle me-3"><i class="ri-route-line text-warning"></i></div>
                                                        <div>
                                                            <div class="fs-12 text-muted">Jarak ke Sekolah</div>
                                                            <div class="fw-semibold">{{ $student->distance_to_school ? $student->distance_to_school . ' km' : '-' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 3: KELUARGA
                        ============================================================ --}}
                        <div class="tab-pane fade" id="keluarga-tab" role="tabpanel">
                            <div class="row g-3">
                                <!-- Ayah -->
                                <div class="col-xl-4 col-lg-6">
                                    <div class="card h-100 family-member-card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="avatar-md me-3">
                                                    <div class="avatar-title rounded-circle bg-primary-subtle">
                                                        <i class="ri-father-line fs-24 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold">Ayah</h6>
                                                    <span class="badge bg-primary-subtle text-primary mt-1">Orang Tua</span>
                                                </div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-12"><div class="text-muted small">Nama</div><div>{{ $student->father_name ?: '-' }}</div></div>
                                                <div class="col-6"><div class="text-muted small">NIK</div><div>{{ $student->father_nik ?: '-' }}</div></div>
                                                <div class="col-6"><div class="text-muted small">Tahun Lahir</div><div>{{ $student->father_birth_year ?: '-' }}</div></div>
                                                <div class="col-6"><div class="text-muted small">Pendidikan</div><div>{{ $student->father_education ?: '-' }}</div></div>
                                                <div class="col-6"><div class="text-muted small">Pekerjaan</div><div>{{ $student->father_occupation ?: '-' }}</div></div>
                                                <div class="col-12"><div class="text-muted small">Penghasilan</div><div>{{ $student->father_income ? 'Rp ' . number_format($student->father_income, 0, ',', '.') : '-' }}</div></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ibu -->
                                <div class="col-xl-4 col-lg-6">
                                    <div class="card h-100 family-member-card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="avatar-md me-3">
                                                    <div class="avatar-title rounded-circle bg-danger-subtle">
                                                        <i class="ri-mother-line fs-24 text-danger"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold">Ibu</h6>
                                                    <span class="badge bg-danger-subtle text-danger mt-1">Orang Tua</span>
                                                </div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-12"><div class="text-muted small">Nama</div><div>{{ $student->mother_name ?: '-' }}</div></div>
                                                <div class="col-6"><div class="text-muted small">NIK</div><div>{{ $student->mother_nik ?: '-' }}</div></div>
                                                <div class="col-6"><div class="text-muted small">Tahun Lahir</div><div>{{ $student->mother_birth_year ?: '-' }}</div></div>
                                                <div class="col-6"><div class="text-muted small">Pendidikan</div><div>{{ $student->mother_education ?: '-' }}</div></div>
                                                <div class="col-6"><div class="text-muted small">Pekerjaan</div><div>{{ $student->mother_occupation ?: '-' }}</div></div>
                                                <div class="col-12"><div class="text-muted small">Penghasilan</div><div>{{ $student->mother_income ? 'Rp ' . number_format($student->mother_income, 0, ',', '.') : '-' }}</div></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Wali -->
                                <div class="col-xl-4 col-lg-6">
                                    <div class="card h-100 family-member-card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="avatar-md me-3">
                                                    <div class="avatar-title rounded-circle bg-warning-subtle">
                                                        <i class="ri-user-star-line fs-24 text-warning"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold">Wali</h6>
                                                    <span class="badge bg-warning-subtle text-warning mt-1">Wali</span>
                                                </div>
                                            </div>
                                            @if($student->guardian_name)
                                            <div class="row g-2">
                                                <div class="col-12"><div class="text-muted small">Nama</div><div>{{ $student->guardian_name }}</div></div>
                                                <div class="col-6"><div class="text-muted small">NIK</div><div>{{ $student->guardian_nik ?: '-' }}</div></div>
                                                <div class="col-6"><div class="text-muted small">Tahun Lahir</div><div>{{ $student->guardian_birth_year ?: '-' }}</div></div>
                                                <div class="col-6"><div class="text-muted small">Pendidikan</div><div>{{ $student->guardian_education ?: '-' }}</div></div>
                                                <div class="col-6"><div class="text-muted small">Pekerjaan</div><div>{{ $student->guardian_occupation ?: '-' }}</div></div>
                                                <div class="col-12"><div class="text-muted small">Penghasilan</div><div>{{ $student->guardian_income ? 'Rp ' . number_format($student->guardian_income, 0, ',', '.') : '-' }}</div></div>
                                            </div>
                                            @else
                                                <div class="alert alert-light mb-0"><i class="ri-information-line me-1"></i>Data wali belum diisi.</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 4: KESEHATAN
                        ============================================================ --}}
                        <div class="tab-pane fade" id="kesehatan-tab" role="tabpanel">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-4 d-flex align-items-center">
                                        <i class="ri-heart-line text-danger me-2"></i>Data Kesehatan
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="card bg-light h-100">
                                                <div class="card-body text-center">
                                                    <div class="text-primary mb-2"><i class="ri-ruler-line fs-2"></i></div>
                                                    <div class="fs-4 fw-semibold">{{ $student->height ? $student->height . ' cm' : '-' }}</div>
                                                    <div class="text-muted">Tinggi Badan</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-light h-100">
                                                <div class="card-body text-center">
                                                    <div class="text-success mb-2"><i class="ri-scales-line fs-2"></i></div>
                                                    <div class="fs-4 fw-semibold">{{ $student->weight ? $student->weight . ' kg' : '-' }}</div>
                                                    <div class="text-muted">Berat Badan</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-light h-100">
                                                <div class="card-body text-center">
                                                    <div class="text-info mb-2"><i class="ri-bubble-chart-line fs-2"></i></div>
                                                    <div class="fs-4 fw-semibold">{{ $student->head_circumference ? $student->head_circumference . ' cm' : '-' }}</div>
                                                    <div class="text-muted">Lingkar Kepala</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-light h-100">
                                                <div class="card-body text-center">
                                                    <div class="text-warning mb-2"><i class="ri-team-line fs-2"></i></div>
                                                    <div class="fs-4 fw-semibold">{{ $student->sibling_count ?? '-' }}</div>
                                                    <div class="text-muted">Jumlah Saudara</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 5: PENDAFTARAN
                        ============================================================ --}}
                        <div class="tab-pane fade" id="pendaftaran-tab" role="tabpanel">
                            <div class="row">
                                <div class="col-xxl-6">
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-4 d-flex align-items-center">
                                                <i class="ri-file-list-line text-primary me-2"></i>Data Pendaftaran
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Anak ke-</label>
                                                        <div class="detail-value">{{ $student->child_number ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Asal Sekolah</label>
                                                        <div class="detail-value">{{ $student->previous_school ?: '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tingkat Masuk</label>
                                                        <div class="detail-value">{{ $student->entry_grade_level ? 'Kelas ' . $student->entry_grade_level : '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tanggal Masuk</label>
                                                        <div class="detail-value">{{ $student->entry_date?->format('d F Y') ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">SKHUN</label>
                                                        <div class="detail-value">{{ $student->skhun ?: '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">No. UN/SKP</label>
                                                        <div class="detail-value">{{ $student->ujian_national_number ?: '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">No. Ijazah</label>
                                                        <div class="detail-value">{{ $student->certificate_number ?: '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">No. Akta Lahir</label>
                                                        <div class="detail-value">{{ $student->birth_certificate_number ?: '-' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xxl-6">
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title mb-4 d-flex align-items-center">
                                                <i class="ri-bank-line text-warning me-2"></i>Bantuan & Perizinan
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Penerima KPS</label>
                                                        <div class="detail-value">{!! $student->is_kps_receiver ? 'Ya <code>' . $student->kps_number . '</code>' : 'Tidak' !!}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Penerima KIP</label>
                                                        <div class="detail-value">{!! $student->is_kip_receiver ? 'Ya <code>' . $student->kip_number . '</code> — ' . $student->kip_name : 'Tidak' !!}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">No. KKS</label>
                                                        <div class="detail-value">{{ $student->kks_number ?: '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Layak PIP</label>
                                                        <div class="detail-value">{!! $student->is_pip_eligible ? 'Ya' . ($student->pip_reason ? ': ' . $student->pip_reason : '') : 'Tidak' !!}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Bank</label>
                                                        <div class="detail-value">{{ $student->bank_name ? $student->bank_name . ' — ' . $student->bank_account_number . ' a.n. ' . $student->bank_account_name : '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Status Kelulusan</label>
                                                        <div class="detail-value">
                                                            <span class="badge bg-{{ $student->status === 'active' ? 'success' : ($student->status === 'graduate' ? 'info' : 'secondary') }}">
                                                                {{ $student->status_text }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if($student->graduation_year)
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tahun Lulus</label>
                                                        <div class="detail-value">{{ $student->graduation_year }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label detail-label">Tanggal Lulus</label>
                                                        <div class="detail-value">{{ $student->graduation_date?->format('d F Y') ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 6: ROMBEL
                        ============================================================ --}}
                        <div class="tab-pane fade" id="rombel-tab" role="tabpanel">
                            @if($student->classHistories && $student->classHistories->count() > 0)
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4 d-flex align-items-center">
                                            <i class="ri-group-line text-primary me-2"></i>Riwayat Kelas / Rombel
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center" width="5%">No</th>
                                                        <th>Kelas / Rombel</th>
                                                        <th>Tahun Ajaran</th>
                                                        <th class="text-center">Aktif</th>
                                                        <th class="text-center">Tanggal Masuk</th>
                                                        <th width="10%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($student->classHistories->sortByDesc(fn($h) => $h->academicYear?->year_start ?? 0) as $index => $history)
                                                        <tr>
                                                            <td class="text-center">{{ $index + 1 }}</td>
                                                            <td>
                                                                @if($history->studyGroup)
                                                                    <div class="fw-semibold">{{ $history->studyGroup->full_name }}</div>
                                                                    <div class="text-muted small">{{ $history->studyGroup->gradeLevel?->name ?? '-' }}</div>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $history->academicYear?->name ?? ($history->academicYear?->year_start . '/' . $history->academicYear?->year_end) }}</td>
                                                            <td class="text-center">
                                                                @if($history->is_active)
                                                                    <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line me-1"></i>Aktif</span>
                                                                @else
                                                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">{{ $history->entry_date?->format('d M Y') ?? '-' }}</td>
                                                            <td class="text-center">
                                                                @if($history->studyGroup)
                                                                    <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $history->studyGroup->id]) }}"
                                                                       class="btn btn-sm btn-soft-primary">
                                                                        <i class="ri-eye-line"></i>
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
                                    <div class="mb-4"><i class="ri-group-line text-muted" style="font-size: 5rem;"></i></div>
                                    <h5 class="text-muted mb-3">Belum Ada Riwayat Kelas</h5>
                                    <p class="text-muted mb-4">Santri ini belum terdaftar di rombel manapun.</p>
                                    <a href="{{ route('user.student-class-histories.create', ['userId' => $userId, 'student_id' => $student->id]) }}"
                                       class="btn btn-primary">
                                        <i class="ri-add-line me-1"></i> Tambah ke Rombel
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- ============================================================
                             TAB 7: PRESTASI
                        ============================================================ --}}
                        <div class="tab-pane fade" id="prestasi-tab" role="tabpanel">
                            @php
                                $allAchievements = $student->achievements->sortByDesc(fn($a) => $a->event_date ?? '');
                                $groupedAchievements = $allAchievements->groupBy(fn($a) => $a->academicYear?->name ?? 'Lainnya');
                            @endphp

                            @if($allAchievements->isEmpty())
                                <div class="text-center py-5">
                                    <div class="mb-4"><i class="ri-trophy-line text-muted" style="font-size: 5rem;"></i></div>
                                    <h5 class="text-muted mb-3">Belum Ada Prestasi</h5>
                                    <p class="text-muted mb-4">Prestasi akademik, hafalan, dan non-akademik akan ditampilkan di sini.</p>
                                </div>
                            @else
                                @foreach($groupedAchievements as $yearName => $achievements)
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><i class="ri-calendar-line me-1"></i> {{ $yearName }}</h6>
                                                <span class="badge bg-primary-subtle text-primary">{{ $achievements->count() }} prestasi</span>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center" width="5%">No</th>
                                                        <th>Tipe</th>
                                                        <th>Kegiatan</th>
                                                        <th>Tingkat</th>
                                                        <th>Peringkat</th>
                                                        <th>Tanggal</th>
                                                        <th class="text-center" width="10%">Piagam</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($achievements as $index => $ach)
                                                        <tr>
                                                            <td class="text-center">{{ $index + 1 }}</td>
                                                            <td>
                                                                <span class="badge bg-{{ $ach->achievement_type === 'akademik' ? 'primary' : ($ach->achievement_type === 'hafalan' ? 'success' : 'info') }}">
                                                                    {{ $ach->type_label }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="fw-medium">{{ $ach->event_name }}</div>
                                                                @if($ach->organizer)
                                                                    <div class="small text-secondary">{{ $ach->organizer }}</div>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-{{ match($ach->level){
                                                                    'internal'=>'secondary','kecamatan'=>'info','kabupaten_kota'=>'primary',
                                                                    'provinsi'=>'warning text-dark','nasional'=>'danger','internasional'=>'dark',
                                                                    default=>'secondary'
                                                                } }}">
                                                                    {{ $ach->level_label }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-{{ match($ach->position){
                                                                    'juara_1'=>'warning text-dark','juara_2'=>'secondary text-white','juara_3'=>'danger text-white',
                                                                    'harapan_1'=>'info text-white','harapan_2'=>'info text-white','harapan_3'=>'info text-white',
                                                                    'mumtaz_murtafi'=>'success text-white','peserta'=>'secondary text-white','lainnya'=>'dark text-white',
                                                                    default=>'secondary text-white'
                                                                } }}">
                                                                    {{ $ach->position_label }}
                                                                </span>
                                                            </td>
                                                            <td class="text-center text-muted">{{ $ach->event_date?->format('d M Y') ?? '-' }}</td>
                                                            <td class="text-center">
                                                                @if($ach->certificate_url)
                                                                    <a href="{{ $ach->certificate_url }}" target="_blank"
                                                                       class="btn btn-sm btn-outline-success rounded-pill px-2"
                                                                       title="Lihat piagam">
                                                                        <i class="ri-image-line"></i>
                                                                    </a>
                                                                @else
                                                                    <span class="text-muted"><i class="ri-image-off-line"></i></span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
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
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
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
       IDENTITY MODAL — countdown
    ========================================================================== */
    $(document).ready(function () {
        var countdownInterval;

        $('#identityModal').on('shown.bs.modal', function () {
            var timeLeft = 60;
            clearInterval(countdownInterval);
            countdownInterval = setInterval(function () {
                timeLeft--;
                $('#countdown').text(timeLeft);
                if (timeLeft <= 0) { clearInterval(countdownInterval); $('#identityModal').modal('hide'); }
            }, 1000);
        });

        $('#identityModal').on('hidden.bs.modal', function () {
            clearInterval(countdownInterval);
        });
    });
    </script>
@endsection
