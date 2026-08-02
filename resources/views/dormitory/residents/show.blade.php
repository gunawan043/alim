@extends('layouts.master')
@section('title') Detail Santri - {{ $resident->student?->name ?? '?' }} @endsection

@php
    $userId = $userId ?? request()->route('userId') ?? (function() { if (auth()->check()) return auth()->id(); return null; })();
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Detail Santri @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>Terjadi kesalahan. Silakan coba lagi.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ============================================================
         PROFILE HEADER
    ============================================================ --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                {{-- Avatar --}}
                <div class="col-auto">
                    <div class="position-relative d-inline-block">
                        @if($resident->student?->photo_path)
                            <img src="{{ asset('storage/' . $resident->student->photo_path) }}"
                                 alt="Foto {{ $resident->student->name }}"
                                 class="rounded-circle shadow"
                                 style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="avatar-lg">
                                <span class="avatar-title rounded-circle fs-2 fw-bold bg-{{ $resident->student?->gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $resident->student?->gender === 'P' ? 'danger' : 'primary' }}">
                                    {{ strtoupper(substr($resident->student?->name ?? '?', 0, 2)) }}
                                </span>
                            </div>
                        @endif
                        {{-- Status dot --}}
                        <span class="position-absolute bottom-0 end-0 badge rounded-circle p-0 border-3 border-white bg-{{ $resident->is_active ? 'success' : 'secondary' }}">
                            <i class="ri-circle-fill fs-16"></i>
                        </span>
                    </div>
                </div>

                {{-- Info --}}
                <div class="col">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <h4 class="mb-0">{{ $resident->student?->name ?? '-' }}</h4>
                        <span class="badge bg-{{ $resident->is_active ? 'success' : 'secondary' }}-subtle text-{{ $resident->is_active ? 'success' : 'secondary' }}">
                            <i class="ri-{{ $resident->is_active ? 'checkbox-circle' : 'close-circle' }}-line me-1"></i>
                            {{ $resident->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        @if($resident->room)
                            <span class="badge bg-info-subtle text-info">
                                <i class="ri-home-4-line me-1"></i>{{ $resident->room->name }}
                            </span>
                            <span class="badge bg-secondary-subtle text-secondary">
                                Bed #{{ $resident->bed_number }}
                            </span>
                        @endif
                    </div>
                    <div class="text-muted small">
                        @if($resident->student?->nisn)
                            <span class="me-3"><i class="ri-bookmark-line me-1"></i>NISN: <code>{{ $resident->student->nisn }}</code></span>
                        @endif
                        @if($resident->student?->nis)
                            <span class="me-3"><i class="ri-file-list-line me-1"></i>NIS: {{ $resident->student->nis }}</span>
                        @endif
                        @if($resident->student?->gender)
                            <span class="me-3"><i class="ri-{{ $resident->student->gender === 'L' ? 'men' : 'women' }}-line me-1"></i>{{ $resident->student->gender_text }}</span>
                        @endif
                        @if($resident->student?->birth_date)
                            <span><i class="ri-calendar-line me-1"></i>{{ $resident->student->birth_date->format('d M Y') }}</span>
                        @endif
                    </div>
                    <div class="mt-2">
                        <span class="small text-muted">
                            <i class="ri-time-line me-1"></i>
                            Tanggal Ditempatkan: {{ $resident->check_in_date?->format('d M Y') ?? '-' }}
                            @if($resident->check_out_date)
                                &nbsp;|&nbsp;
                                <i class="ri-logout-box-r-line me-1"></i>
                                Tanggal Keluar: {{ $resident->check_out_date->format('d M Y') }}
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="col-auto d-flex gap-2">
                    <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                       class="btn btn-light">
                        <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                    </a>
                    @if($resident->is_active)
                        <form method="POST"
                              action="{{ route('user.asrama.residents.checkout', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentUuid' => $resident->id]) }}"
                              class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-warning checkout-btn">
                                <i class="ri-logout-box-r-line align-middle me-1"></i> Keluar
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB NAVIGATION
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="d-flex flex-column flex-md-row align-items-md-center border-bottom p-3">
                        <ul class="nav nav-pills gap-2 gap-lg-3 flex-grow-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link fs-14 active" data-bs-toggle="tab" href="#info-tab" role="tab">
                                    <i class="ri-user-line me-1"></i> Info Santri
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#mahrom-tab" role="tab">
                                    <i class="ri-parent-line me-1"></i> Mahrom
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#room-tab" role="tab">
                                    <i class="ri-home-4-line me-1"></i> Kamar
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#violations-tab" role="tab">
                                    <i class="ri-error-warning-line me-1"></i> Pelanggaran
                                    @if($violations->count() > 0)
                                        <span class="badge bg-danger rounded-pill ms-1">{{ $violations->count() }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#permits-tab" role="tab">
                                    <i class="ri-file-list-line me-1"></i> Izin
                                    @if($permits->count() > 0)
                                        <span class="badge bg-warning rounded-pill ms-1">{{ $permits->count() }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content p-4">

                        {{-- ============================================================
                             TAB 1: INFO SANTRI
                        ============================================================ --}}
                        <div class="tab-pane active" id="info-tab" role="tabpanel">
                            @if($resident->student)
                                <div class="row">
                                    <div class="col-xxl-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">
                                                    <i class="ri-profile-line text-primary me-2"></i>Data Pribadi
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">Nama Lengkap</label>
                                                        <div class="detail-value fw-semibold">{{ $resident->student->name }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">NISN</label>
                                                        <div class="detail-value"><code>{{ $resident->student->nisn }}</code></div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">NIS</label>
                                                        <div class="detail-value">{{ $resident->student->nis ?: '-' }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">Jenis Kelamin</label>
                                                        <div class="detail-value">
                                                            <span class="badge bg-{{ $resident->student->gender === 'L' ? 'primary' : 'danger' }}-subtle text-{{ $resident->student->gender === 'L' ? 'primary' : 'danger' }}">
                                                                {{ $resident->student->gender_text }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">Tempat Lahir</label>
                                                        <div class="detail-value">{{ $resident->student->birth_place ?: '-' }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">Tanggal Lahir</label>
                                                        <div class="detail-value">{{ $resident->student->birth_date?->format('d F Y') ?? '-' }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">Agama</label>
                                                        <div class="detail-value">{{ $resident->student->religion ? ucfirst($resident->student->religion) : '-' }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">No. HP</label>
                                                        <div class="detail-value">{{ $resident->student->mobile_phone ?: '-' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">
                                                    <i class="ri-file-check-line text-primary me-2"></i>Informasi Asrama
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">Asrama</label>
                                                        <div class="detail-value">
                                                            <span class="badge bg-info-subtle text-info">{{ $dormitory->name ?? '-' }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">Kamar</label>
                                                        <div class="detail-value">
                                                            {{ $resident->room?->name ?? '-' }}
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">No. Bed</label>
                                                        <div class="detail-value">
                                                            <span class="badge bg-secondary-subtle text-secondary">#{{ $resident->bed_number }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">Status</label>
                                                        <div class="detail-value">
                                                            @if($resident->is_active)
                                                                <span class="badge bg-success-subtle text-success">
                                                                    <i class="ri-checkbox-circle-line me-1"></i>Aktif
                                                                </span>
                                                            @else
                                                                <span class="badge bg-secondary-subtle text-secondary">
                                                                    <i class="ri-close-circle-line me-1"></i>Nonaktif
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">Tanggal Ditempatkan</label>
                                                        <div class="detail-value">{{ $resident->check_in_date?->format('d F Y') ?? '-' }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">Tanggal Keluar</label>
                                                        <div class="detail-value">{{ $resident->check_out_date?->format('d F Y') ?? '-' }}</div>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label detail-label">Catatan</label>
                                                        <div class="detail-value">
                                                            <div class="p-2 rounded" style="background: var(--bs-tertiary-bg);">
                                                                {{ $resident->notes ?? '-' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ============================================================
                                         PROFIL AKADEMIK (READ-ONLY)
                                         Source of Truth: Academic Module (Student model)
                                    ============================================================ --}}
                                    <div class="col-xxl-6">
                                        <div class="card mb-4 border-info">
                                            <div class="card-header bg-info-subtle">
                                                <h5 class="card-title mb-0">
                                                    <i class="ri-school-line text-info me-2"></i>Profil Akademik
                                                    <span class="badge bg-secondary ms-2" style="font-size: 0.7rem;">READ-ONLY</span>
                                                </h5>
                                                <small class="text-muted">Data dari modul Akademik — tidak dapat diubah di sini</small>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    @if(isset($academicProfile) && $academicProfile)
                                                        {{-- Status Akademik --}}
                                                        <div class="col-12">
                                                            <label class="form-label detail-label">Status Akademik</label>
                                                            <div class="detail-value">
                                                                @php
                                                                    $statusColor = match($academicProfile->status ?? '') {
                                                                        'active' => 'success',
                                                                        'graduate' => 'info',
                                                                        'transfer_out', 'dropped', 'inactive' => 'warning',
                                                                        default => 'secondary'
                                                                    };
                                                                @endphp
                                                                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                                                    {{ $academicProfile->status_text ?? ucfirst($academicProfile->status ?? '-') }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        @if($academicProfile->school ?? null)
                                                        <div class="col-md-6">
                                                            <label class="form-label detail-label">Sekolah</label>
                                                            <div class="detail-value text-muted">{{ $academicProfile->school }}</div>
                                                        </div>
                                                        @endif

                                                        @if($academicProfile->current_class ?? null)
                                                        <div class="col-md-6">
                                                            <label class="form-label detail-label">Kelas Saat Ini</label>
                                                            <div class="detail-value text-muted">{{ $academicProfile->current_class }}</div>
                                                        </div>
                                                        @endif

                                                        @if($academicProfile->current_section ?? null)
                                                        <div class="col-md-6">
                                                            <label class="form-label detail-label">Rombongan Belajar</label>
                                                            <div class="detail-value text-muted">{{ $academicProfile->current_section }}</div>
                                                        </div>
                                                        @endif

                                                        @if($academicProfile->entry_date ?? null)
                                                        <div class="col-md-6">
                                                            <label class="form-label detail-label">Tahun Masuk</label>
                                                            <div class="detail-value text-muted">{{ \Carbon\Carbon::parse($academicProfile->entry_date)->format('Y') }}</div>
                                                        </div>
                                                        @endif

                                                        @if($academicProfile->graduation_year ?? null)
                                                        <div class="col-md-6">
                                                            <label class="form-label detail-label">Tahun Lulus</label>
                                                            <div class="detail-value text-muted">{{ $academicProfile->graduation_year }}</div>
                                                        </div>
                                                        @endif

                                                        @if($academicProfile->nik ?? null)
                                                        <div class="col-md-6">
                                                            <label class="form-label detail-label">NIK</label>
                                                            <div class="detail-value"><code class="text-muted">{{ $academicProfile->nik }}</code></div>
                                                        </div>
                                                        @endif

                                                        @if($academicProfile->email ?? null)
                                                        <div class="col-md-6">
                                                            <label class="form-label detail-label">Email</label>
                                                            <div class="detail-value text-muted">{{ $academicProfile->email }}</div>
                                                        </div>
                                                        @endif

                                                        @if($academicProfile->mobile_phone ?? $academicProfile->phone ?? null)
                                                        <div class="col-md-6">
                                                            <label class="form-label detail-label">No. Handphone</label>
                                                            <div class="detail-value text-muted">{{ $academicProfile->mobile_phone ?? $academicProfile->phone }}</div>
                                                        </div>
                                                        @endif

                                                        @if($academicProfile->full_address ?? null)
                                                        <div class="col-12">
                                                            <label class="form-label detail-label">Alamat</label>
                                                            <div class="detail-value text-muted">{{ $academicProfile->full_address }}</div>
                                                        </div>
                                                        @endif

                                                        @if($academicProfile->special_needs ?? null)
                                                        <div class="col-12">
                                                            <label class="form-label detail-label">Kebutuhan Khusus</label>
                                                            <div class="detail-value text-muted">{{ $academicProfile->special_needs }}</div>
                                                        </div>
                                                        @endif

                                                        {{-- Divider --}}
                                                        <div class="col-12"><hr class="text-muted"></div>
                                                        <div class="col-12">
                                                            <label class="form-label detail-label fw-bold">Informasi Orang Tua & Wali</label>
                                                        </div>

                                                        @if(($academicProfile->father['name'] ?? null) || ($academicProfile->father['occupation'] ?? null))
                                                        <div class="col-md-4">
                                                            <div class="p-2 rounded bg-primary-subtle">
                                                                <div class="text-primary small fw-bold">Ayah</div>
                                                                @if($academicProfile->father['name'])<div class="fw-semibold">{{ $academicProfile->father['name'] }}</div>@endif
                                                                @if($academicProfile->father['occupation'])<div class="text-muted small">{{ $academicProfile->father['occupation'] }}</div>@endif
                                                                @if($academicProfile->father['education'])<div class="text-muted small">Pendidikan: {{ $academicProfile->father['education'] }}</div>@endif
                                                                @if($academicProfile->father['birth_year'])<div class="text-muted small">Tahun Lahir: {{ $academicProfile->father['birth_year'] }}</div>@endif
                                                            </div>
                                                        </div>
                                                        @endif

                                                        @if(($academicProfile->mother['name'] ?? null) || ($academicProfile->mother['occupation'] ?? null))
                                                        <div class="col-md-4">
                                                            <div class="p-2 rounded bg-danger-subtle">
                                                                <div class="text-danger small fw-bold">Ibu</div>
                                                                @if($academicProfile->mother['name'])<div class="fw-semibold">{{ $academicProfile->mother['name'] }}</div>@endif
                                                                @if($academicProfile->mother['occupation'])<div class="text-muted small">{{ $academicProfile->mother['occupation'] }}</div>@endif
                                                                @if($academicProfile->mother['education'])<div class="text-muted small">Pendidikan: {{ $academicProfile->mother['education'] }}</div>@endif
                                                                @if($academicProfile->mother['birth_year'])<div class="text-muted small">Tahun Lahir: {{ $academicProfile->mother['birth_year'] }}</div>@endif
                                                            </div>
                                                        </div>
                                                        @endif

                                                        @if(($academicProfile->guardian['name'] ?? null) || ($academicProfile->guardian['occupation'] ?? null))
                                                        <div class="col-md-4">
                                                            <div class="p-2 rounded bg-secondary-subtle">
                                                                <div class="text-muted small fw-bold">Wali</div>
                                                                @if($academicProfile->guardian['name'])<div class="fw-semibold">{{ $academicProfile->guardian['name'] }}</div>@endif
                                                                @if($academicProfile->guardian['occupation'])<div class="text-muted small">{{ $academicProfile->guardian['occupation'] }}</div>@endif
                                                                @if($academicProfile->guardian['education'])<div class="text-muted small">Pendidikan: {{ $academicProfile->guardian['education'] }}</div>@endif
                                                                @if($academicProfile->guardian['birth_year'])<div class="text-muted small">Tahun Lahir: {{ $academicProfile->guardian['birth_year'] }}</div>@endif
                                                            </div>
                                                        </div>
                                                        @endif

                                                        @if(
                                                            !($academicProfile->father['name'] ?? null) &&
                                                            !($academicProfile->mother['name'] ?? null) &&
                                                            !($academicProfile->guardian['name'] ?? null)
                                                        )
                                                        <div class="col-12">
                                                            <div class="text-muted small fst-italic">Informasi orang tua/wali belum diisi di modul akademik.</div>
                                                        </div>
                                                        @endif

                                                    @else
                                                        <div class="col-12 text-center py-3">
                                                            <i class="ri-error-warning-line text-muted fs-2"></i>
                                                            <p class="text-muted small mb-0">Profil akademik tidak tersedia.</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xxl-6">
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="ri-alert-line me-2"></i>Data santri tidak ditemukan. Santri mungkin telah dihapus dari sistem.
                                </div>
                            @endif
                        </div>

                        {{-- ============================================================
                             TAB 2: MAHROM
                        ============================================================ --}}
                        <div class="tab-pane fade" id="mahrom-tab" role="tabpanel">
                            @if($resident->student && $resident->student->mahroms && $resident->student->mahroms->count() > 0)
                                <div class="row g-3">
                                    @foreach($resident->student->mahroms as $mahrom)
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="card h-100 border-0 shadow-sm">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="avatar-md me-3">
                                                            <div class="avatar-title rounded-circle bg-{{ $mahrom->relationship === 'father' ? 'primary' : ($mahrom->relationship === 'mother' ? 'danger' : 'warning') }}-subtle">
                                                                <i class="ri-{{ $mahrom->relationship === 'father' ? 'user-location' : ($mahrom->relationship === 'mother' ? 'user-heart' : 'user-star') }}-line fs-20 text-{{ $mahrom->relationship === 'father' ? 'primary' : ($mahrom->relationship === 'mother' ? 'danger' : 'warning') }}"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $mahrom->name }}</h6>
                                                            <span class="badge bg-{{ $mahrom->relationship === 'father' ? 'primary' : ($mahrom->relationship === 'mother' ? 'danger' : 'warning') }}-subtle text-{{ $mahrom->relationship === 'father' ? 'primary' : ($mahrom->relationship === 'mother' ? 'danger' : 'warning') }} mt-1">
                                                                {{ $mahrom->relationship_text }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="row g-2 small">
                                                        @if($mahrom->nik)
                                                            <div class="col-12"><div class="text-muted">NIK</div><div>{{ $mahrom->nik }}</div></div>
                                                        @endif
                                                        @if($mahrom->phone)
                                                            <div class="col-6"><div class="text-muted">No. HP</div><div>{{ $mahrom->phone }}</div></div>
                                                        @endif
                                                        @if($mahrom->job)
                                                            <div class="col-6"><div class="text-muted">Pekerjaan</div><div>{{ $mahrom->job }}</div></div>
                                                        @endif
                                                        @if($mahrom->address)
                                                            <div class="col-12"><div class="text-muted">Alamat</div><div>{{ $mahrom->address }}</div></div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-4">
                                        <i class="ri-parent-line fs-1 d-block text-muted" style="font-size: 4rem;"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">Belum Ada Data Mahrom</h5>
                                    <p class="text-muted">Data mahrom/wali belum tersedia untuk santri ini.</p>
                                </div>
                            @endif
                        </div>

                        {{-- ============================================================
                             TAB 3: ROOM
                        ============================================================ --}}
                        <div class="tab-pane fade" id="room-tab" role="tabpanel">
                            @if($resident->room)
                                <div class="row">
                                    <div class="col-xxl-8">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">
                                                    <i class="ri-home-4-line text-primary me-2"></i>Detail Kamar
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label detail-label">Nama Kamar</label>
                                                        <div class="detail-value fw-semibold fs-5">{{ $resident->room->name }}</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label detail-label">Lantai</label>
                                                        <div class="detail-value">{{ $resident->room->floor ?? '-' }}</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label detail-label">Kapasitas</label>
                                                        <div class="detail-value">{{ $resident->room->current_occupancy ?? 0 }} / {{ $resident->room->capacity ?? 0 }} bed</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">No. Bed</label>
                                                        <div class="detail-value">
                                                            <span class="badge bg-primary-subtle text-primary fs-6">#{{ $resident->bed_number }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label detail-label">Gender</label>
                                                        <div class="detail-value">
                                                            <span class="badge bg-{{ $resident->room->gender === 'L' ? 'primary' : 'danger' }}-subtle text-{{ $resident->room->gender === 'L' ? 'primary' : 'danger' }}">
                                                                {{ $resident->room->gender_text }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Other residents in the same room --}}
                                        @if($roomResidents && $roomResidents->count() > 0)
                                            <div class="card">
                                                <div class="card-header">
                                                    <h5 class="card-title mb-0">
                                                        <i class="ri-team-line text-info me-2"></i>Santri Lain di Kamar Ini
                                                    </h5>
                                                </div>
                                                <div class="card-body p-0">
                                                    <div class="table-responsive">
                                                        <table class="table table-hover align-middle mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th class="text-center" style="width: 5%">No</th>
                                                                    <th>Nama Santri</th>
                                                                    <th class="text-center" style="width: 12%">Bed</th>
                                                                    <th class="text-center" style="width: 12%">Status</th>
                                                                    <th class="text-center" style="width: 12%">Aksi</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($roomResidents as $idx => $r)
                                                                    @if($r->id !== $resident->id)
                                                                        <tr>
                                                                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                                                            <td>
                                                                                <span class="fw-semibold">{{ $r->student?->name ?? '-' }}</span>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <span class="badge bg-secondary-subtle text-secondary">#{{ $r->bed_number }}</span>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                @if($r->is_active)
                                                                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                                                                @else
                                                                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                                                                @endif
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <a href="{{ route('user.asrama.residents.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentUuid' => $r->id]) }}"
                                                                                   class="btn btn-sm btn-outline-primary">
                                                                                    <i class="ri-eye-line"></i>
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    @endif
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="col-xxl-4">
                                        <div class="card bg-light border-0 h-100">
                                            <div class="card-body">
                                                <h6 class="card-title mb-3">
                                                    <i class="ri-home-4-line text-primary me-2"></i>Denah Bed
                                                </h6>
                                                <div class="row g-2" id="bedMap">
                                                    @for($b = 1; $b <= ($resident->room->capacity ?? 4); $b++)
                                                        @php
                                                            $bedOccupied = $roomResidents->contains('bed_number', $b);
                                                            $isMyBed = $b == $resident->bed_number;
                                                        @endphp
                                                        <div class="col-6">
                                                            <div class="p-3 rounded text-center border
                                                                {{ $isMyBed ? 'border-primary bg-primary-subtle' : 'border-light' }}
                                                                {{ $bedOccupied && !$isMyBed ? 'bg-success-subtle' : '' }}">
                                                                <div class="fw-bold mb-1">Bed {{ $b }}</div>
                                                                @if($isMyBed)
                                                                    <span class="badge bg-primary">Anda</span>
                                                                @elseif($bedOccupied)
                                                                    <span class="badge bg-success-subtle text-success">Terisi</span>
                                                                @else
                                                                    <span class="badge bg-light text-muted">Kosong</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endfor
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-secondary">
                                    <i class="ri-home-line me-2"></i>Kamar belum ditetapkan untuk santri ini.
                                </div>
                            @endif
                        </div>

                        {{-- ============================================================
                             TAB 4: VIOLATIONS
                        ============================================================ --}}
                        <div class="tab-pane fade" id="violations-tab" role="tabpanel">
                            @if($violations->count() > 0)
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">
                                                <i class="ri-error-warning-line text-danger me-2"></i>Daftar Pelanggaran Asrama
                                            </h5>
                                            <span class="badge bg-danger fs-6">{{ $violations->count() }} pelanggaran</span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center" style="width: 5%">No</th>
                                                        <th style="width: 12%">Tanggal</th>
                                                        <th>Jenis Pelanggaran</th>
                                                        <th class="text-center" style="width: 10%">Poin</th>
                                                        <th>Tindakan</th>
                                                        <th style="width: 12%">Recorder</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($violations as $idx => $v)
                                                        <tr>
                                                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                                            <td>{{ $v->violation_date?->format('d/m/Y') ?? '-' }}</td>
                                                            <td>{{ $v->violation_type ?? '-' }}</td>
                                                            <td class="text-center">
                                                                <span class="badge bg-danger">{{ $v->points ?? 0 }}</span>
                                                            </td>
                                                            <td class="small">{{ Str::limit($v->action_taken ?? '-', 60) }}</td>
                                                            <td class="small text-muted">{{ $v->recorder?->name ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        {{-- Summary stat --}}
                                        <div class="mt-3 p-3 rounded bg-danger-subtle border border-danger-subtle">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-semibold">Total Poin Pelanggaran</span>
                                                <span class="badge bg-danger fs-5">
                                                    {{ $violations->sum('points') }} poin
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-4">
                                        <i class="ri-checkbox-circle-line fs-1 d-block text-success" style="font-size: 4rem;"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">Tidak Ada Pelanggaran</h5>
                                    <p class="text-muted">Santri ini belum memiliki catatan pelanggaran asrama.</p>
                                </div>
                            @endif
                        </div>

                        {{-- ============================================================
                             TAB 5: PERMITS / IZIN
                        ============================================================ --}}
                        <div class="tab-pane fade" id="permits-tab" role="tabpanel">
                            @if($permits->count() > 0)
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">
                                            <i class="ri-file-list-line text-warning me-2"></i>Daftar Izin Asrama
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center" style="width: 5%">No</th>
                                                        <th style="width: 12%">Tanggal</th>
                                                        <th style="width: 12%">Jenis</th>
                                                        <th>Alasan</th>
                                                        <th style="width: 15%">Dari - Sampai</th>
                                                        <th class="text-center" style="width: 12%">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($permits as $idx => $p)
                                                        <tr>
                                                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                                            <td>{{ $p->application_date?->format('d/m/Y') ?? '-' }}</td>
                                                            <td>
                                                                <span class="badge bg-{{ $p->permit_type === 'out' ? 'warning' : 'info' }}-subtle text-{{ $p->permit_type === 'out' ? 'warning' : 'info' }}">
                                                                    {{ $p->permit_type_text }}
                                                                </span>
                                                            </td>
                                                            <td class="small">{{ Str::limit($p->reason ?? '-', 80) }}</td>
                                                            <td>
                                                                {{ $p->start_date?->format('d/m/Y') ?? '-' }}
                                                                -
                                                                {{ $p->end_date?->format('d/m/Y') ?? '-' }}
                                                            </td>
                                                            <td class="text-center">
                                                                @if($p->status === 'approved')
                                                                    <span class="badge bg-success-subtle text-success">
                                                                        <i class="ri-checkbox-circle-line me-1"></i>Disetujui
                                                                    </span>
                                                                @elseif($p->status === 'rejected')
                                                                    <span class="badge bg-danger-subtle text-danger">
                                                                        <i class="ri-close-circle-line me-1"></i>Ditolak
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-secondary-subtle text-secondary">
                                                                        <i class="ri-time-line me-1"></i>Menunggu
                                                                    </span>
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
                                    <div class="mb-4">
                                        <i class="ri-file-list-line fs-1 d-block text-muted" style="font-size: 4rem;"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">Belum Ada Izin</h5>
                                    <p class="text-muted">-</p>
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
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    document.querySelectorAll('.checkout-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form = btn.closest('form');
            Swal.fire({
                title: 'Konfirmasi Keluar',
                html: '<p>Yakin ingin mengeluarkan <strong>{{ $resident->student?->name ?? 'santri ini' }}</strong> dari asrama?</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="ri-logout-box-r-line me-1"></i> Ya, Keluarkan',
                cancelButtonText: 'Batal',
                confirmButtonClass: 'btn btn-warning me-2',
                cancelButtonClass: 'btn btn-light',
                reverseButtons: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endsection