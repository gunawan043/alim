@extends('layouts.master')

@section('title')
    Profil Kesehatan GTK 
@endsection

@php 
    $userId = $userId ?? request()->route('userId') ?? Auth::id(); 
@endphp

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .profile-stat {
            background: rgba(var(--bs-body-bg-rgb), 0.1);
            border-radius: 8px;
            padding: 1rem;
            transition: all 0.3s ease;
            border: 1px solid var(--bs-border-color);
        }
        .profile-stat:hover {
            background: rgba(var(--bs-body-bg-rgb), 0.15);
            transform: translateY(-2px);
        }
        .profile-stat .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
        }
        .icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            background: var(--bs-tertiary-bg);
        }
        .profile-foreground {
            position: relative;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-radius: 12px;
            min-height: 160px;
        }
        .profile-foreground .overlay-content {
            position: absolute;
            bottom: 0;
            left: 0;
            padding: 1.5rem;
            color: #fff;
        }
        .profile-foreground .profile-wid-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
            opacity: 0.3;
        }
        .profile-wrapper {
            background: linear-gradient(to right, rgba(245, 158, 11, 0.9), rgba(217, 119, 6, 0.7));
            border-radius: 12px;
            padding: 20px;
            margin-top: -30px;
            position: relative;
            z-index: 10;
        }
        [data-bs-theme="dark"] .profile-wrapper {
            background: linear-gradient(to right, rgba(180, 83, 9, 0.8), rgba(146, 64, 14, 0.6));
        }
        [data-bs-theme="dark"] .profile-foreground {
            background: linear-gradient(135deg, #b45309 0%, #92400e 100%);
        }
        .nav-pills .nav-link {
            color: var(--bs-secondary-color);
            font-weight: 500;
        }
        .nav-pills .nav-link.active {
            color: var(--bs-primary);
            background: var(--bs-primary-bg-subtle);
        }
        .badge-rounded { font-size: 0.75rem; padding: 0.25rem 0.6rem; }
        .health-card { border-left: 4px solid var(--bs-primary); }
        .health-card.blood { border-left-color: #dc3545; }
        .health-card.bmi { border-left-color: #198754; }
        .health-card.sugar { border-left-color: #0dcaf0; }
        .health-card.pulse { border-left-color: #ffc107; }
        .card-animate { transition: all 0.3s ease; }
        .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .bg-light-custom { background: var(--bs-tertiary-bg); }
    </style>
@endsection

@section('content')
    @php
        // Data GTK
        $gtkName = $user->name ?? 'GTK';
        $profile = $user->gtkProfile;
        $jk = $profile?->jenis_kelamin;
        $avatarBg = match($jk) {
            'L' => 'bg-warning-subtle text-warning',
            'P' => 'bg-danger-subtle text-danger',
            default => 'bg-secondary-subtle text-secondary'
        };

        // Employment data (minimal)
        $primaryEmployment = $user->employments?->sortByDesc('start_date')->first();
        $jabatan = $primaryEmployment?->position?->name ?? $primaryEmployment?->jabatan ?? null;
        $jenisGtk = $primaryEmployment?->employmentType?->name ?? $primaryEmployment?->jenisGtk?->name ?? null;

        // Health data
        $health = $user->gtkHealthData ?? null;
        $latest = $latestRecord ?? null;

        // BMI status
        $bmi = $latest?->bmi ?? 0;
        $bmiStatus = '';
        $bmiClass = 'success';
        if ($bmi > 0) {
            if ($bmi >= 18.5 && $bmi <= 24.9) {
                $bmiStatus = 'Normal';
                $bmiClass = 'success';
            } elseif ($bmi < 18.5) {
                $bmiStatus = 'Kurus';
                $bmiClass = 'warning';
            } else {
                $bmiStatus = 'Gemuk';
                $bmiClass = $bmi > 28 ? 'danger' : 'warning';
            }
        }

        // BP status
        $bp = $latest?->blood_pressure ?? '';
        $bpClass = 'success';
        $bpStatus = '-';
        if ($bp && str_contains($bp, '/')) {
            [$sbp, $dbp] = array_map('intval', explode('/', $bp, 2));
            $bpStatus = $bp;
            if ($sbp >= 140 || $dbp >= 90) $bpClass = 'danger';
            elseif ($sbp >= 130 || $dbp >= 80) $bpClass = 'warning';
        }

        // Fitness status
        $fitnessBadge = match($latest?->fitness_status ?? null) {
            'sehat' => ['success', 'Sehat'],
            'sehat_dengan_catatan' => ['warning', 'Sehat dengan Catatan'],
            'belum_sehat' => ['danger', 'Belum Sehat'],
            default => ['secondary', 'Belum Diperiksa'],
        };
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.gtk-health.index', ['userId' => $userId]) }}">GTK & Kesehatan</a> @endslot
        @slot('title') Profil Kesehatan — {{ $gtkName }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-double-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header Profile -->
    <div class="profile-foreground position-relative mx-n4 mt-n4">
        <div class="profile-wid-bg">
            <img src="{{ URL::asset('build/images/alim-one-bg.png') }}" alt="Background Profile" class="profile-wid-img" />
            <div class="overlay-content position-absolute bottom-0 start-0 p-4 text-white">
                <h4 class="mb-1">{{ $gtkName }}</h4>
                <p class="mb-0 opacity-75">
                    {{ $jabatan ?? 'GTK' }}
                    @if($jenisGtk) · {{ $jenisGtk }} @endif
                </p>
                <div class="mt-2">
                    <span class="badge bg-{{ $fitnessBadge[0] }}-subtle text-{{ $fitnessBadge[0] }}">
                        <i class="ri-heart-pulse-line me-1"></i>{{ $fitnessBadge[1] }}
                    </span>
                    @if($jk)
                        <span class="badge bg-{{ $jk == 'L' ? 'warning' : 'danger' }}-subtle text-{{ $jk == 'L' ? 'warning' : 'danger' }}">
                            {{ $jk == 'L' ? '♂ Putra' : '♀ Putri' }}
                        </span>
                    @endif
                    @if($user->is_active ?? true)
                        <span class="badge bg-success-subtle text-success">
                            <i class="ri-checkbox-circle-fill me-1"></i>Aktif
                        </span>
                    @else
                        <span class="badge bg-danger-subtle text-danger">
                            <i class="ri-close-circle-fill me-1"></i>Nonaktif
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Wrapper Minimal -->
    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper">
        <div class="row g-4">
            <div class="col-auto">
                <div class="avatar-lg position-relative">
                    @if(($user->avatar ?? '') != '')
                        <img src="{{ URL::asset('images/' . $user->avatar) }}"
                             alt="Foto Profil {{ $gtkName }}" 
                             class="img-thumbnail rounded-circle shadow" style="width: 80px; height: 80px;" />
                    @else
                        <div class="img-thumbnail rounded-circle shadow d-flex align-items-center justify-content-center bg-white"
                             style="width: 80px; height: 80px; font-size: 32px; font-weight: 700;">
                            <span class="{{ $avatarBg }} w-100 h-100 rounded-circle d-flex align-items-center justify-content-center">
                                {{ strtoupper(substr($gtkName, 0, 1)) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col">
                <div class="p-2">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h3 class="text-white mb-0">{{ $gtkName }}</h3>
                    </div>
                    <div class="text-white d-flex flex-wrap gap-3 text-white-75">
                        @if($profile?->golongan_darah)
                            <div class="d-flex align-items-center">
                                <i class="ri-drop-line me-2"></i>
                                <span>Gol. {{ $profile->golongan_darah }}</span>
                            </div>
                        @endif
                        @if($profile?->tinggi_badan)
                            <div class="d-flex align-items-center">
                                <i class="ri-arrow-up-double-line me-2"></i>
                                <span>{{ number_format($profile->tinggi_badan, 1) }} cm</span>
                            </div>
                        @endif
                        @if($profile?->berat_badan)
                            <div class="d-flex align-items-center">
                                <i class="ri-weight-line me-2"></i>
                                <span>{{ number_format($profile->berat_badan, 1) }} kg</span>
                            </div>
                        @endif
                        @if($profile?->tanggal_lahir)
                            <div class="d-flex align-items-center">
                                <i class="ri-cake-2-line me-2"></i>
                                <span>{{ \Carbon\Carbon::parse($profile->tanggal_lahir)->age }} tahun</span>
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
            <div class="card" id="healthProfileCard">
                <div class="card-body p-0">
                    <!-- Navigation Tabs -->
                    <div class="d-flex flex-column flex-md-row align-items-md-center border-bottom p-3">
                        <ul class="nav nav-pills gap-2 gap-lg-3 flex-grow-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link fs-14 active" data-bs-toggle="tab" href="#ringkasan" role="tab">
                                    <i class="ri-information-line me-1"></i> Ringkasan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#data-kesehatan" role="tab">
                                    <i class="ri-heart-pulse-line me-1"></i> Data Kesehatan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#riwayat" role="tab">
                                    <i class="ri-history-line me-1"></i> Riwayat MCU
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#vaksinasi" role="tab">
                                    <i class="ri-shield-cross-line me-1"></i> Vaksinasi
                                </a>
                            </li>
                        </ul>
                        <div class="d-flex gap-2 mt-3 mt-md-0">
                            <a href="{{ route('user.uks.gtk-health.records.index', ['userId' => $userId, 'gtkUuid' => $user->id]) }}"
                               class="btn btn-primary">
                                <i class="ri-add-line align-middle me-1"></i> Tambah Pemeriksaan
                            </a>
                            <a href="{{ route('user.uks.gtk-health.index', ['userId' => $userId]) }}"
                               class="btn btn-light">
                                <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                            </a>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content p-4">

                        {{-- ============================================================
                             TAB 1: RINGKASAN KESEHATAN
                        ============================================================ --}}
                        <div class="tab-pane active" id="ringkasan" role="tabpanel">
                            @if($latest)
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="text-muted small mb-0">
                                        <i class="ri-calendar-line me-1"></i>
                                        Pemeriksaan terakhir:
                                        {{ \Carbon\Carbon::parse($latest->examined_at ?? $latest->check_date ?? $latest->created_at)->isoFormat('dddd, D MMMM Y') }}
                                    </p>
                                    <span class="badge bg-{{ $fitnessBadge[0] }}-subtle text-{{ $fitnessBadge[0] }} fs-12">
                                        <i class="ri-heart-pulse-line me-1"></i>{{ $fitnessBadge[1] }}
                                    </span>
                                </div>

                                <div class="row g-4">
                                    {{-- Tekanan Darah --}}
                                    <div class="col-md-3 col-sm-6">
                                        <div class="profile-stat text-center health-card blood">
                                            <div class="mb-2"><i class="ri-heart-line fs-4 text-danger"></i></div>
                                            <h6 class="text-muted mb-1">Tekanan Darah</h6>
                                            <div class="stat-number text-{{ $bpClass }}">{{ $bpStatus }}</div>
                                            <small class="text-muted">mmHg</small>
                                        </div>
                                    </div>

                                    {{-- Denyut Nadi --}}
                                    <div class="col-md-3 col-sm-6">
                                        <div class="profile-stat text-center health-card pulse">
                                            <div class="mb-2"><i class="ri-heart-pulse-line fs-4 text-warning"></i></div>
                                            <h6 class="text-muted mb-1">Denyut Nadi</h6>
                                            <div class="stat-number">{{ $latest->pulse_bpm ?? $latest->pulse ?? '-' }}</div>
                                            <small class="text-muted">bpm</small>
                                        </div>
                                    </div>

                                    {{-- BMI / IMT --}}
                                    <div class="col-md-3 col-sm-6">
                                        <div class="profile-stat text-center health-card bmi">
                                            <div class="mb-2"><i class="ri-body-scan-line fs-4 text-success"></i></div>
                                            <h6 class="text-muted mb-1">BMI / IMT</h6>
                                            <div class="stat-number text-{{ $bmiClass }}">
                                                {{ $bmi ? number_format($bmi, 1, ',', '.') : '-' }}
                                            </div>
                                            <small class="text-muted">{{ $bmiStatus ?: 'Belum diukur' }}</small>
                                        </div>
                                    </div>

                                    {{-- Gula Darah (GDP) --}}
                                    <div class="col-md-3 col-sm-6">
                                        <div class="profile-stat text-center health-card sugar">
                                            <div class="mb-2"><i class="ri-test-tube-line fs-4 text-info"></i></div>
                                            <h6 class="text-muted mb-1">Gula Darah (GDP)</h6>
                                            <div class="stat-number">
                                                {{ $latest->blood_sugar_fasting ? number_format($latest->blood_sugar_fasting, 0, ',', '.') : '-' }}
                                            </div>
                                            <small class="text-muted">mg/dL</small>
                                        </div>
                                    </div>
                                </div>

                                @if($latest->diagnosis || $latest->recommendation || $latest->notes)
                                    <div class="row g-3 mt-3">
                                        @if($latest->diagnosis)
                                            <div class="col-md-6">
                                                <div class="alert alert-info py-2 mb-0">
                                                    <strong><i class="ri-stethoscope-line me-1"></i>Diagnosa:</strong>
                                                    {{ $latest->diagnosis }}
                                                </div>
                                            </div>
                                        @endif
                                        @if($latest->recommendation)
                                            <div class="col-md-6">
                                                <div class="alert alert-light border py-2 mb-0">
                                                    <strong><i class="ri-lightbulb-line me-1"></i>Saran:</strong>
                                                    {{ $latest->recommendation }}
                                                </div>
                                            </div>
                                        @endif
                                        @if($latest->notes)
                                            <div class="col-12">
                                                <div class="alert alert-secondary py-2 mb-0">
                                                    <strong><i class="ri-file-text-line me-1"></i>Catatan:</strong>
                                                    {{ $latest->notes }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Ringkasan Riwayat Cepat -->
                                @if(isset($healthRecords) && $healthRecords->count() > 1)
                                    <div class="mt-4 pt-3 border-top">
                                        <h6 class="fw-semibold mb-2">
                                            <i class="ri-history-line me-1"></i>Riwayat Singkat (5 terakhir)
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Tanggal</th>
                                                        <th>TD</th>
                                                        <th>Nadi</th>
                                                        <th>BMI</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($healthRecords->take(5) as $rec)
                                                        @php
                                                            $recBmi = (float) ($rec->bmi ?? 0);
                                                            $recBmiClass = 'warning';
                                                            if ($recBmi > 0) {
                                                                $recBmiClass = ($recBmi >= 18.5 && $recBmi <= 24.9) ? 'success' : (($recBmi > 28) ? 'danger' : 'warning');
                                                            }
                                                            $recFitColor = match($rec->fitness_status ?? null) {
                                                                'sehat' => 'success',
                                                                'sehat_dengan_catatan' => 'warning',
                                                                'belum_sehat' => 'danger',
                                                                default => 'secondary',
                                                            };
                                                        @endphp
                                                        <tr>
                                                            <td>{{ \Carbon\Carbon::parse($rec->examined_at ?? $rec->check_date ?? $rec->created_at)->isoFormat('D MMM Y') }}</td>
                                                            <td>{{ $rec->blood_pressure ?? '-' }}</td>
                                                            <td>{{ $rec->pulse_bpm ?? $rec->pulse ?? '-' }}</td>
                                                            <td>
                                                                @if($rec->bmi)
                                                                    <span class="badge bg-{{ $recBmiClass }}-subtle text-{{ $recBmiClass }}">
                                                                        {{ number_format((float) $rec->bmi, 1) }}
                                                                    </span>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($rec->fitness_status)
                                                                    <span class="badge bg-{{ $recFitColor }}-subtle text-{{ $recFitColor }}">
                                                                        {{ ucfirst(str_replace('_', ' ', $rec->fitness_status)) }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @if($healthRecords->count() > 5)
                                            <div class="text-end mt-2">
                                                <a href="#riwayat" class="btn btn-sm btn-link" data-bs-toggle="tab">
                                                    Lihat semua ({{ $healthRecords->count() }}) <i class="ri-arrow-right-line ms-1"></i>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <div class="mb-3">
                                            <i class="ri-heart-add-line text-muted" style="font-size: 4rem;"></i>
                                        </div>
                                        <p class="text-muted mb-2">Belum ada data pemeriksaan kesehatan.</p>
                                        <p class="text-muted small">Mulai catat dengan menekan tombol "Tambah Pemeriksaan".</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- ============================================================
                             TAB 2: DATA KESEHATAN LENGKAP
                        ============================================================ --}}
                        <div class="tab-pane fade" id="data-kesehatan" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title mb-0 d-flex align-items-center">
                                            <i class="ri-heart-pulse-line text-danger me-2"></i>Data Kesehatan GTK
                                        </h5>
                                        <div>
                                            @if($health)
                                                <span class="badge bg-success me-2">
                                                    <i class="ri-check-line me-1"></i> Data Tersedia
                                                </span>
                                            @endif
                                            <button type="button" class="btn btn-primary btn-sm" id="btnEditKesehatan" onclick="toggleHealthDataForm()">
                                                <i class="ri-edit-line me-1"></i> Edit Data
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Display Mode -->
                                    <div id="healthDataDisplay">
                                        <div class="row g-4">
                                            <!-- Golongan Darah -->
                                            <div class="col-md-3 col-sm-6">
                                                <div class="profile-stat text-center">
                                                    <div class="mb-2"><i class="ri-drop-line fs-4 text-danger"></i></div>
                                                    <h6 class="text-muted mb-1">Golongan Darah</h6>
                                                    <div class="fs-4 fw-bold">{{ $health?->golongan_darah ?: '-' }}</div>
                                                </div>
                                            </div>

                                            <!-- Tekanan Darah -->
                                            <div class="col-md-3 col-sm-6">
                                                <div class="profile-stat text-center">
                                                    <div class="mb-2"><i class="ri-heart-line fs-4 text-danger"></i></div>
                                                    <h6 class="text-muted mb-1">Tekanan Darah</h6>
                                                    <div class="fs-4 fw-bold">{{ $health?->tekanan_darah ?: '-' }}</div>
                                                </div>
                                            </div>

                                            <!-- Tinggi Badan -->
                                            <div class="col-md-3 col-sm-6">
                                                <div class="profile-stat text-center">
                                                    <div class="mb-2"><i class="ri-arrow-up-double-line fs-4 text-success"></i></div>
                                                    <h6 class="text-muted mb-1">Tinggi Badan</h6>
                                                    <div class="fs-4 fw-bold">
                                                        {{ $health?->tinggi_badan ? number_format($health->tinggi_badan, 1, ',', '.') . ' cm' : '-' }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Berat Badan -->
                                            <div class="col-md-3 col-sm-6">
                                                <div class="profile-stat text-center">
                                                    <div class="mb-2"><i class="ri-weight-line fs-4 text-info"></i></div>
                                                    <h6 class="text-muted mb-1">Berat Badan</h6>
                                                    <div class="fs-4 fw-bold">
                                                        {{ $health?->berat_badan ? number_format($health->berat_badan, 1, ',', '.') . ' kg' : '-' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Informasi Detail -->
                                        <div class="mt-4">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="text-muted fw-semibold mb-1">Lingkar Kepala:</label>
                                                    <span>{{ $health?->lingkar_kepala ?: '-' }}</span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted fw-semibold mb-1">Lingkar Perut:</label>
                                                    <span>{{ $health?->lingkar_perut ?: '-' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Riwayat Penyakit -->
                                        <div class="mt-4">
                                            <h6 class="fw-bold d-flex align-items-center">
                                                <i class="ri-file-list-3-line text-warning me-2"></i>Riwayat Penyakit
                                            </h6>
                                            <div class="border rounded p-3 bg-light-custom">
                                                {!! $health?->riwayat_penyakit ? nl2br(e($health->riwayat_penyakit)) : '<span class="text-muted">Belum diisi</span>' !!}
                                            </div>
                                        </div>

                                        <!-- Alergi -->
                                        <div class="mt-4">
                                            <h6 class="fw-bold d-flex align-items-center">
                                                <i class="ri-shield-exclamation-line text-danger me-2"></i>Alergi
                                            </h6>
                                            <div class="border rounded p-3 bg-light-custom">
                                                {!! $health?->alergi ? nl2br(e($health->alergi)) : '<span class="text-muted">Belum diisi</span>' !!}
                                            </div>
                                        </div>

                                        <!-- Obat Rutin -->
                                        <div class="mt-4">
                                            <h6 class="fw-bold d-flex align-items-center">
                                                <i class="ri-capsule-line text-warning me-2"></i>Obat Rutin
                                            </h6>
                                            <div class="border rounded p-3 bg-light-custom">
                                                {!! $health?->ongoing_medication ? nl2br(e($health->ongoing_medication)) : '<span class="text-muted">Belum diisi</span>' !!}
                                            </div>
                                        </div>

                                        <!-- Catatan P3K -->
                                        <div class="mt-4">
                                            <h6 class="fw-bold d-flex align-items-center">
                                                <i class="ri-medkit-line text-success me-2"></i>Catatan P3K
                                            </h6>
                                            <div class="border rounded p-3 bg-light-custom">
                                                {!! $health?->p3k ? nl2br(e($health->p3k)) : '<span class="text-muted">Belum diisi</span>' !!}
                                            </div>
                                        </div>

                                        <!-- Keluhan -->
                                        <div class="mt-4">
                                            <h6 class="fw-bold d-flex align-items-center">
                                                <i class="ri-question-line text-info me-2"></i>Keluhan yang Dialami
                                            </h6>
                                            <div class="border rounded p-3 bg-light-custom">
                                                {!! $health?->keluhan_yang_dialami ? nl2br(e($health->keluhan_yang_dialami)) : '<span class="text-muted">Belum diisi</span>' !!}
                                            </div>
                                        </div>

                                        @if(!$health)
                                            <div class="text-center py-4 mt-3">
                                                <div class="mb-3"><i class="ri-heart-add-line text-muted" style="font-size: 4rem;"></i></div>
                                                <p class="text-muted">Belum ada data kesehatan. Klik tombol <strong>Edit Data</strong> untuk menambahkan.</p>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Edit Form -->
                                    <div id="healthDataForm" class="d-none">
                                        <form id="healthDataFormElement" onsubmit="saveHealthData(event)">
                                            @csrf
                                            <div class="row g-3">
                                                <!-- Golongan Darah -->
                                                <div class="col-md-3 col-sm-6">
                                                    <label class="form-label">Golongan Darah</label>
                                                    <select class="form-select" name="golongan_darah">
                                                        <option value="">Pilih</option>
                                                        <option value="A" {{ $health?->golongan_darah === 'A' ? 'selected' : '' }}>A</option>
                                                        <option value="B" {{ $health?->golongan_darah === 'B' ? 'selected' : '' }}>B</option>
                                                        <option value="AB" {{ $health?->golongan_darah === 'AB' ? 'selected' : '' }}>AB</option>
                                                        <option value="O" {{ $health?->golongan_darah === 'O' ? 'selected' : '' }}>O</option>
                                                    </select>
                                                </div>

                                                <!-- Tekanan Darah -->
                                                <div class="col-md-3 col-sm-6">
                                                    <label class="form-label">Tekanan Darah</label>
                                                    <input type="text" class="form-control" name="tekanan_darah" 
                                                           value="{{ old('tekanan_darah', $health?->tekanan_darah ?? '') }}" 
                                                           placeholder="Contoh: 120/80">
                                                </div>

                                                <!-- Tinggi Badan -->
                                                <div class="col-md-3 col-sm-6">
                                                    <label class="form-label">Tinggi Badan (cm)</label>
                                                    <input type="number" step="0.01" min="0" max="300" class="form-control" 
                                                           name="tinggi_badan" value="{{ $health?->tinggi_badan ?? '' }}" 
                                                           placeholder="cm">
                                                </div>

                                                <!-- Berat Badan -->
                                                <div class="col-md-3 col-sm-6">
                                                    <label class="form-label">Berat Badan (kg)</label>
                                                    <input type="number" step="0.01" min="0" max="500" class="form-control" 
                                                           name="berat_badan" value="{{ $health?->berat_badan ?? '' }}" 
                                                           placeholder="kg">
                                                </div>

                                                <!-- Lingkar Kepala -->
                                                <div class="col-md-4">
                                                    <label class="form-label">Lingkar Kepala (cm)</label>
                                                    <input type="text" class="form-control" name="lingkar_kepala" 
                                                           value="{{ old('lingkar_kepala', $health?->lingkar_kepala ?? '') }}" 
                                                           placeholder="cm">
                                                </div>

                                                <!-- Lingkar Perut -->
                                                <div class="col-md-4">
                                                    <label class="form-label">Lingkar Perut (cm)</label>
                                                    <input type="text" class="form-control" name="lingkar_perut" 
                                                           value="{{ old('lingkar_perut', $health?->lingkar_perut ?? '') }}" 
                                                           placeholder="cm">
                                                </div>

                                                <!-- Riwayat Penyakit -->
                                                <div class="col-md-4">
                                                    <label class="form-label">Riwayat Penyakit</label>
                                                    <textarea class="form-control" name="riwayat_penyakit" rows="2" 
                                                              placeholder="Riwayat penyakit yang pernah diderita">{{ old('riwayat_penyakit', $health?->riwayat_penyakit ?? '') }}</textarea>
                                                </div>

                                                <!-- Alergi -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Alergi</label>
                                                    <textarea class="form-control" name="alergi" rows="2" 
                                                              placeholder="Reaksi alergi (makanan, obat, dll.)">{{ old('alergi', $health?->alergi ?? '') }}</textarea>
                                                </div>

                                                <!-- Obat Rutin -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Obat Rutin</label>
                                                    <textarea class="form-control" name="ongoing_medication" rows="2" 
                                                              placeholder="Obat-obatan yang dikonsumsi rutin">{{ old('ongoing_medication', $health?->ongoing_medication ?? '') }}</textarea>
                                                </div>

                                                <!-- P3K -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Catatan P3K</label>
                                                    <textarea class="form-control" name="p3k" rows="2" 
                                                              placeholder="Pertolongan pertama yang sudah dilakukan">{{ old('p3k', $health?->p3k ?? '') }}</textarea>
                                                </div>

                                                <!-- Keluhan -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Keluhan yang Dialami</label>
                                                    <textarea class="form-control" name="keluhan_yang_dialami" rows="2" 
                                                              placeholder="Keluhan yang sedang dialami saat ini">{{ old('keluhan_yang_dialami', $health?->keluhan_yang_dialami ?? '') }}</textarea>
                                                </div>
                                            </div>

                                            <div class="mt-4 d-flex gap-2 justify-content-end">
                                                <button type="button" class="btn btn-secondary" onclick="toggleHealthDataForm()">
                                                    <i class="ri-close-line me-1"></i> Batal
                                                </button>
                                                <button type="submit" class="btn btn-success" id="btnSaveHealth">
                                                    <i class="ri-save-line me-1"></i> Simpan Data
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 3: RIWAYAT MCU LENGKAP
                        ============================================================ --}}
                        <div class="tab-pane fade" id="riwayat" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title mb-0 d-flex align-items-center">
                                            <i class="ri-history-line text-primary me-2"></i>Riwayat Pemeriksaan MCU
                                        </h5>
                                        <span class="badge bg-primary">
                                            {{ isset($healthRecords) ? $healthRecords->count() : 0 }} Pemeriksaan
                                        </span>
                                    </div>
                                    @php $recs = $healthRecords ?? collect(); @endphp
                                    @if($recs->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0" id="mcuHistoryTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Tanggal</th>
                                                        <th>TD</th>
                                                        <th>Nadi</th>
                                                        <th>BMI</th>
                                                        <th>Gula Darah</th>
                                                        <th>Status</th>
                                                        <th width="15%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recs->sortByDesc('examined_at') as $index => $rec)
                                                        @php
                                                            $recBmi = (float) ($rec->bmi ?? 0);
                                                            $recBmiClass = 'warning';
                                                            if ($recBmi > 0) {
                                                                $recBmiClass = ($recBmi >= 18.5 && $recBmi <= 24.9) ? 'success' : (($recBmi > 28) ? 'danger' : 'warning');
                                                            }
                                                            $recFitColor = match($rec->fitness_status ?? null) {
                                                                'sehat' => 'success',
                                                                'sehat_dengan_catatan' => 'warning',
                                                                'belum_sehat' => 'danger',
                                                                default => 'secondary',
                                                            };
                                                        @endphp
                                                        <tr>
                                                            <td class="text-center">{{ $index + 1 }}</td>
                                                            <td>{{ \Carbon\Carbon::parse($rec->examined_at ?? $rec->check_date ?? $rec->created_at)->isoFormat('D MMM Y') }}</td>
                                                            <td><span class="fw-semibold">{{ $rec->blood_pressure ?? '-' }}</span></td>
                                                            <td>{{ $rec->pulse_bpm ?? $rec->pulse ?? '-' }}</td>
                                                            <td>
                                                                @if($rec->bmi)
                                                                    <span class="badge bg-{{ $recBmiClass }}-subtle text-{{ $recBmiClass }}">
                                                                        {{ number_format((float) $rec->bmi, 1) }}
                                                                    </span>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td>{{ $rec->blood_sugar_fasting ? number_format($rec->blood_sugar_fasting, 0) . ' mg/dL' : '-' }}</td>
                                                            <td>
                                                                @if($rec->fitness_status)
                                                                    <span class="badge bg-{{ $recFitColor }}-subtle text-{{ $recFitColor }}">
                                                                        {{ ucfirst(str_replace('_', ' ', $rec->fitness_status)) }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <a href="{{ route('user.uks.gtk-health.records.show', ['userId' => $userId, 'gtkUuid' => $user->id, 'recordId' => $rec->id]) }}" 
                                                                   class="btn btn-sm btn-info">
                                                                    <i class="ri-eye-line"></i>
                                                                </a>
                                                                <a href="{{ route('user.uks.gtk-health.records.edit', ['userId' => $userId, 'gtkUuid' => $user->id, 'recordId' => $rec->id]) }}" 
                                                                   class="btn btn-sm btn-warning">
                                                                    <i class="ri-edit-line"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <div class="mb-3">
                                                <i class="ri-file-list-3-line text-muted" style="font-size: 4rem;"></i>
                                            </div>
                                            <p class="text-muted mb-0">Belum ada riwayat MCU.</p>
                                            <p class="text-muted small">Mulai catat dengan menekan tombol "Tambah Pemeriksaan".</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 4: VAKSINASI
                        ============================================================ --}}
                        <div class="tab-pane fade" id="vaksinasi" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title mb-0 d-flex align-items-center">
                                            <i class="ri-shield-cross-line text-success me-2"></i>Riwayat Vaksinasi
                                        </h5>
                                        <span class="badge bg-success">
                                            {{ isset($vaccinations) ? $vaccinations->count() : 0 }} Vaksinasi
                                        </span>
                                    </div>
                                    @if(isset($vaccinations) && $vaccinations->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Tanggal</th>
                                                        <th>Jenis Vaksin</th>
                                                        <th>Dosis</th>
                                                        <th>Lokasi</th>
                                                        <th>Pemberi</th>
                                                        <th>Keterangan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($vaccinations->sortByDesc('given_at') as $vax)
                                                        <tr>
                                                            <td>{{ \Carbon\Carbon::parse($vax->given_at)->isoFormat('D MMM Y') }}</td>
                                                            <td><strong>{{ $vax->vaccine_name ?? $vax->vaccine_type ?? '-' }}</strong></td>
                                                            <td>
                                                                <span class="badge bg-info">{{ $vax->dose ?? '-' }}</span>
                                                            </td>
                                                            <td>{{ $vax->location ?? '-' }}</td>
                                                            <td>{{ $vax->administeredBy?->name ?? '-' }}</td>
                                                            <td>{{ $vax->notes ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <div class="mb-3">
                                                <i class="ri-shield-cross-line text-muted" style="font-size: 4rem;"></i>
                                            </div>
                                            <p class="text-muted mb-0">Belum ada data vaksinasi.</p>
                                        </div>
                                    @endif
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
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    /* ==========================================================================
       DATA KESEHATAN GTK - Toggle Edit Form
       ========================================================================== */
    let isHealthEditMode = false;

    function toggleHealthDataForm() {
        const display = document.getElementById('healthDataDisplay');
        const form = document.getElementById('healthDataForm');
        const btnEdit = document.getElementById('btnEditKesehatan');

        if (!isHealthEditMode) {
            display.classList.add('d-none');
            form.classList.remove('d-none');
            if (btnEdit) btnEdit.textContent = 'Batal Edit';
            isHealthEditMode = true;
        } else {
            display.classList.remove('d-none');
            form.classList.add('d-none');
            if (btnEdit) btnEdit.innerHTML = '<i class="ri-edit-line me-1"></i> Edit Data';
            isHealthEditMode = false;
        }
    }

    /* ==========================================================================
       SAVE HEALTH DATA
       ========================================================================== */
    function saveHealthData(event) {
        event.preventDefault();

        const form = document.getElementById('healthDataFormElement');
        const formData = new FormData(form);
        const saveBtn = document.getElementById('btnSaveHealth');

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i> Menyimpan...';

        var healthUrl = '{!! route("user.gtk.health-data.store", ["userId" => $userId, "uuid" => $gtk->id]) !!}';

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
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Gagal', 
                        text: response.message || 'Gagal menyimpan data kesehatan' 
                    });
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="ri-save-line me-1"></i> Simpan Data';
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data kesehatan.';
                if (xhr.responseJSON?.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(function(e) { 
                        return e[0]; 
                    }).join('<br>');
                }
                Swal.fire({ icon: 'error', title: 'Gagal', html: msg });
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="ri-save-line me-1"></i> Simpan Data';
            }
        });
    }

    /* ==========================================================================
       TAB NAVIGATION - Bootstrap 5
       ========================================================================== */
    document.addEventListener('DOMContentLoaded', function() {
        var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'));
        triggerTabList.forEach(function(triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl);
            triggerEl.addEventListener('click', function(e) {
                e.preventDefault();
                tabTrigger.show();
            });
        });
    });
    </script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection