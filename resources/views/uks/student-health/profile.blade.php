@extends('layouts.master')

@section('title', 'Profil Kesehatan Santri — UKS')
@section('subtitle', 'Profil Kesehatan Santri')

@section('css')
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
        .icon-circle {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin-right: 12px; background: var(--bs-tertiary-bg);
        }
        .profile-foreground {
            position: relative;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-radius: 12px;
            min-height: 160px;
        }
        .profile-foreground .overlay-content {
            position: absolute;
            bottom: 0; left: 0;
            padding: 1.5rem;
            color: #fff;
        }
        .nav-pills .nav-link {
            color: var(--bs-secondary-color);
            font-weight: 500;
        }
        .nav-pills .nav-link.active {
            color: var(--bs-primary);
            background: var(--bs-primary-bg-subtle);
        }
        [data-bs-theme="dark"] .profile-foreground {
            background: linear-gradient(135deg, #b45309 0%, #92400e 100%);
        }
    </style>
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? Auth::id();

        // Resolve name & gender
        $santriName = $studentModel->nama ?? $studentModel->name ?? 'Santri';
        $jk = $studentModel->jenis_kelamin ?? $studentModel->gender ?? null;

        $avatarBg = match($jk) {
            'L' => 'bg-warning-subtle text-warning',
            'P' => 'bg-danger-subtle text-danger',
            default => 'bg-secondary-subtle text-secondary'
        };

        // Konteks penempatan (kelas / asrama)
        $kelas = $studentModel->currentClassHistory?->studyGroup?->full_name
            ?? $studentModel->currentClassHistory?->studyGroup?->name
            ?? null;
        $asrama = $studentModel->dormitoryResident?->dormitory?->name
            ?? $studentModel->activeDormitoryResident?->dormitory?->name
            ?? null;

        // Data kesehatan dasar
        $record = $record ?? null;
        $bt = $record?->blood_type;
        $hasBloodType = !empty($bt) && $bt !== 'tidak_diketahui' && $bt !== '-';

        // Latest metric (antropometri)
        $latestMetric = isset($metrics) && $metrics->count() > 0 ? $metrics->first() : null;
        $latestHeight = $latestMetric?->height_cm ?? $record?->height_cm;
        $latestWeight = $latestMetric?->weight_kg ?? $record?->weight_kg;
        $bmi = $latestMetric?->bmi ?? $record?->bmi;
        $bmiNumeric = $bmi !== null ? (float) $bmi : 0;

        $bmiStatus = '';
        $bmiClass = 'success';
        if ($bmiNumeric > 0) {
            // BMI untuk usia sekolah (Kemenkes): kurus <17, normal 17–22.99, gemuk ≥23
            if ($bmiNumeric >= 17 && $bmiNumeric < 23) {
                $bmiStatus = 'Normal';
                $bmiClass = 'success';
            } elseif ($bmiNumeric < 17) {
                $bmiStatus = 'Kurus';
                $bmiClass = 'warning';
            } else {
                $bmiStatus = 'Gemuk';
                $bmiClass = $bmiNumeric > 27 ? 'danger' : 'warning';
            }
        }

        // Latest checkup untuk highlight
        $latestCheckup = isset($checkups) && $checkups->count() > 0 ? $checkups->first() : null;
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.student-health.index', ['userId' => $userId]) }}">Data Kesehatan Santri</a> @endslot
        @slot('title') Profil Kesehatan — {{ $santriName }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-double-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ============================================================
         HEADER PROFIL — mengikuti template gtk/profile.blade.php
    ============================================================ --}}
    <div class="profile-foreground position-relative mx-n4 mt-n4">
        <div class="overlay-content">
            <h4 class="mb-1">{{ $santriName }}</h4>
            <p class="mb-0 opacity-75">
                @if($studentModel->nis) NIS: {{ $studentModel->nis }} @endif
                @if($studentModel->nis && $kelas) · @endif
                @if($kelas) {{ $kelas }} @endif
            </p>
        </div>
    </div>

    {{-- Profile Wrapper dengan avatar dan meta --}}
    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper"
         style="background: linear-gradient(to right, rgba(245, 158, 11, 0.9), rgba(217, 119, 6, 0.7));
                border-radius: 12px; padding: 20px; margin-top: -30px; position: relative; z-index: 10;">
        <div class="row g-4">
            <div class="col-auto">
                <div class="avatar-lg position-relative">
                    <div class="img-thumbnail rounded-circle shadow d-flex align-items-center justify-content-center bg-white"
                         style="width: 96px; height: 96px; font-size: 36px; font-weight: 700;">
                        <span class="{{ $avatarBg }} w-100 h-100 rounded-circle d-flex align-items-center justify-content-center">
                            {{ strtoupper(substr($santriName, 0, 1)) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="p-2">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h3 class="text-white mb-0">{{ $santriName }}</h3>
                        @if($jk)
                            <span class="badge bg-{{ $jk == 'L' ? 'warning' : 'danger' }}-subtle text-{{ $jk == 'L' ? 'warning' : 'danger' }}">
                                {{ $jk == 'L' ? '♂ Putra' : '♀ Putri' }}
                            </span>
                        @endif
                        @if($hasBloodType)
                            <span class="badge bg-danger-subtle text-danger">
                                <i class="ri-drop-line me-1"></i>Gol. {{ $bt }}
                            </span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="ri-alert-line me-1"></i>Gol. Darah belum diisi
                            </span>
                        @endif
                    </div>
                    <p class="text-white text-opacity-90 mb-3">
                        @if($studentModel->nis)
                            <i class="ri-barcode-line align-middle me-1"></i> NIS: {{ $studentModel->nis }}
                        @endif
                        @if($kelas)
                            <span class="text-white-75">· {{ $kelas }}</span>
                        @endif
                    </p>
                    <div class="text-white d-flex flex-wrap gap-3 text-white-75">
                        @if($asrama)
                            <div class="d-flex align-items-center">
                                <i class="ri-building-line me-2"></i>
                                <span>{{ $asrama }}</span>
                            </div>
                        @endif
                        @if($latestHeight)
                            <div class="d-flex align-items-center">
                                <i class="ri-arrow-up-double-line me-2"></i>
                                <span>{{ number_format((float) $latestHeight, 1) }} cm</span>
                            </div>
                        @endif
                        @if($latestWeight)
                            <div class="d-flex align-items-center">
                                <i class="ri-weight-line me-2"></i>
                                <span>{{ number_format((float) $latestWeight, 1) }} kg</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MAIN CONTENT dengan NAV PILLS
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
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
                                <a class="nav-link fs-14" data-bs-toggle="tab" href="#izin-sakit" role="tab">
                                    <i class="ri-file-shield-2-line me-1"></i> Izin Sakit
                                </a>
                            </li>
                        </ul>
                        <div class="d-flex gap-2 mt-3 mt-md-0">
                            <a href="{{ route('user.uks.patients.create', ['userId' => $userId, 'student_id' => $studentModel->id]) }}"
                               class="btn btn-primary">
                                <i class="ri-add-line align-middle me-1"></i> Kunjungan UKS
                            </a>
                            <a href="{{ route('user.uks.student-health.index', ['userId' => $userId]) }}"
                               class="btn btn-light">
                                <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                            </a>
                        </div>
                    </div>

                    {{-- Tab Content --}}
                    <div class="tab-content p-4">

                        {{-- ============================================================
                             TAB 1: RINGKASAN
                        ============================================================ --}}
                        <div class="tab-pane active" id="ringkasan" role="tabpanel">
                            @if($latestMetric || $record)
                                <p class="text-muted small mb-3">
                                    <i class="ri-calendar-line me-1"></i>
                                    Terakhir diukur:
                                    @if($latestMetric?->record_date)
                                        {{ \Carbon\Carbon::parse($latestMetric->record_date)->isoFormat('dddd, D MMMM Y') }}
                                    @elseif($record?->last_physical_exam_date)
                                        {{ \Carbon\Carbon::parse($record->last_physical_exam_date)->isoFormat('dddd, D MMMM Y') }}
                                    @else
                                        -
                                    @endif
                                </p>

                                <div class="row g-4">
                                    {{-- Tinggi Badan --}}
                                    <div class="col-md-3 col-sm-6">
                                        <div class="profile-stat text-center">
                                            <div class="mb-2"><i class="ri-arrow-up-double-line fs-4 text-success"></i></div>
                                            <h6 class="text-muted mb-1">Tinggi Badan</h6>
                                            <div class="fs-4 fw-bold">
                                                {{ $latestHeight ? number_format((float) $latestHeight, 1, ',', '.') : '-' }}
                                            </div>
                                            <small class="text-muted">cm</small>
                                        </div>
                                    </div>

                                    {{-- Berat Badan --}}
                                    <div class="col-md-3 col-sm-6">
                                        <div class="profile-stat text-center">
                                            <div class="mb-2"><i class="ri-weight-line fs-4 text-info"></i></div>
                                            <h6 class="text-muted mb-1">Berat Badan</h6>
                                            <div class="fs-4 fw-bold">
                                                {{ $latestWeight ? number_format((float) $latestWeight, 1, ',', '.') : '-' }}
                                            </div>
                                            <small class="text-muted">kg</small>
                                        </div>
                                    </div>

                                    {{-- BMI --}}
                                    <div class="col-md-3 col-sm-6">
                                        <div class="profile-stat text-center">
                                            <div class="mb-2"><i class="ri-body-scan-line fs-4 text-{{ $bmiClass }}"></i></div>
                                            <h6 class="text-muted mb-1">BMI / IMT</h6>
                                            <div class="fs-4 fw-bold text-{{ $bmiClass }}">
                                                {{ $bmiNumeric ? number_format($bmiNumeric, 1, ',', '.') : '-' }}
                                            </div>
                                            <small class="text-muted">{{ $bmiStatus ?: 'Belum diukur' }}</small>
                                        </div>
                                    </div>

                                    {{-- Golongan Darah --}}
                                    <div class="col-md-3 col-sm-6">
                                        <div class="profile-stat text-center">
                                            <div class="mb-2"><i class="ri-drop-line fs-4 text-danger"></i></div>
                                            <h6 class="text-muted mb-1">Golongan Darah</h6>
                                            <div class="fs-4 fw-bold">{{ $hasBloodType ? $bt : '-' }}</div>
                                            <small class="text-muted">
                                                @if($hasBloodType)
                                                    <i class="ri-check-line text-success"></i> Terdata
                                                @else
                                                    <i class="ri-alert-line text-warning"></i> Belum diisi
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                @if($latestCheckup)
                                    <div class="row g-2 mt-2">
                                        @if($latestCheckup->vision_left || $latestCheckup->vision_right)
                                            <div class="col-md-6">
                                                <div class="alert alert-info py-2 mb-2">
                                                    <strong><i class="ri-eye-line me-1"></i>Penglihatan:</strong>
                                                    Kiri {{ $latestCheckup->vision_left ?? '-' }} ·
                                                    Kanan {{ $latestCheckup->vision_right ?? '-' }}
                                                </div>
                                            </div>
                                        @endif
                                        @if($latestCheckup->tb_screening_result)
                                            <div class="col-md-6">
                                                <div class="alert alert-light border py-2 mb-2">
                                                    <strong><i class="ri-lungs-line me-1"></i>Skrining TB:</strong>
                                                    {{ ucfirst($latestCheckup->tb_screening_result) }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <div class="mb-3"><i class="ri-heart-add-line text-muted" style="font-size: 4rem;"></i></div>
                                        <p class="text-muted mb-2">Belum ada data pemeriksaan kesehatan.</p>
                                        <p class="text-muted small">Silakan lengkapi data kesehatan dasar terlebih dahulu.</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- ============================================================
                             TAB 2: DATA KESEHATAN
                        ============================================================ --}}
                        <div class="tab-pane fade" id="data-kesehatan" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title mb-0 d-flex align-items-center">
                                            <i class="ri-heart-pulse-line text-danger me-2"></i>Data Kesehatan Santri
                                        </h5>
                                    </div>

                                    <div class="row g-4">
                                        {{-- Golongan Darah --}}
                                        <div class="col-md-3 col-sm-6">
                                            <div class="profile-stat text-center">
                                                <div class="mb-2"><i class="ri-drop-line fs-4 text-danger"></i></div>
                                                <h6 class="text-muted mb-1">Golongan Darah</h6>
                                                <div class="fs-4 fw-bold">{{ $hasBloodType ? $bt : '-' }}</div>
                                            </div>
                                        </div>

                                        {{-- Tinggi Badan --}}
                                        <div class="col-md-3 col-sm-6">
                                            <div class="profile-stat text-center">
                                                <div class="mb-2"><i class="ri-arrow-up-double-line fs-4 text-success"></i></div>
                                                <h6 class="text-muted mb-1">Tinggi Badan</h6>
                                                <div class="fs-4 fw-bold">
                                                    {{ $latestHeight ? number_format((float) $latestHeight, 1, ',', '.') . ' cm' : '-' }}
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Berat Badan --}}
                                        <div class="col-md-3 col-sm-6">
                                            <div class="profile-stat text-center">
                                                <div class="mb-2"><i class="ri-weight-line fs-4 text-info"></i></div>
                                                <h6 class="text-muted mb-1">Berat Badan</h6>
                                                <div class="fs-4 fw-bold">
                                                    {{ $latestWeight ? number_format((float) $latestWeight, 1, ',', '.') . ' kg' : '-' }}
                                                </div>
                                            </div>
                                        </div>

                                        {{-- BMI --}}
                                        <div class="col-md-3 col-sm-6">
                                            <div class="profile-stat text-center">
                                                <div class="mb-2"><i class="ri-body-scan-line fs-4 text-{{ $bmiClass }}"></i></div>
                                                <h6 class="text-muted mb-1">BMI / IMT</h6>
                                                <div class="fs-4 fw-bold text-{{ $bmiClass }}">
                                                    {{ $bmiNumeric ? number_format($bmiNumeric, 1, ',', '.') : '-' }}
                                                </div>
                                                <small class="text-muted">{{ $bmiStatus ?: 'Belum diukur' }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- No. BPJS --}}
                                    <div class="mt-4">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="text-muted fw-semibold mb-1">No. BPJS:</label>
                                                <span>{{ $record?->bpjs_number ?: '-' }}</span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="text-muted fw-semibold mb-1">Asrama:</label>
                                                <span>{{ $asrama ?: '-' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Alergi --}}
                                    <div class="mt-4">
                                        <h6 class="fw-bold d-flex align-items-center">
                                            <i class="ri-shield-exclamation-line text-danger me-2"></i>Alergi
                                        </h6>
                                        <div class="border rounded p-3 bg-light">
                                            {!! $record?->allergies ? nl2br(e($record->allergies)) : '<span class="text-muted">Belum diisi</span>' !!}
                                        </div>
                                    </div>

                                    {{-- Penyakit Kronis --}}
                                    <div class="mt-4">
                                        <h6 class="fw-bold d-flex align-items-center">
                                            <i class="ri-file-list-3-line text-warning me-2"></i>Penyakit Kronis
                                        </h6>
                                        <div class="border rounded p-3 bg-light">
                                            {!! $record?->chronic_diseases ? nl2br(e($record->chronic_diseases)) : '<span class="text-muted">Belum diisi</span>' !!}
                                        </div>
                                    </div>

                                    {{-- Obat Rutin --}}
                                    <div class="mt-4">
                                        <h6 class="fw-bold d-flex align-items-center">
                                            <i class="ri-capsule-line text-warning me-2"></i>Obat Rutin
                                        </h6>
                                        <div class="border rounded p-3 bg-light">
                                            {!! $record?->current_medications ? nl2br(e($record->current_medications)) : '<span class="text-muted">Belum diisi</span>' !!}
                                        </div>
                                    </div>

                                    {{-- Catatan Darurat --}}
                                    <div class="mt-4">
                                        <h6 class="fw-bold d-flex align-items-center">
                                            <i class="ri-alarm-warning-line text-info me-2"></i>Catatan Darurat
                                        </h6>
                                        <div class="border rounded p-3 bg-light">
                                            {!! $record?->emergency_notes ? nl2br(e($record->emergency_notes)) : '<span class="text-muted">Belum diisi</span>' !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 3: RIWAYAT MCU
                        ============================================================ --}}
                        <div class="tab-pane fade" id="riwayat" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4 d-flex align-items-center">
                                        <i class="ri-stethoscope-line text-primary me-2"></i>Riwayat Pemeriksaan
                                    </h5>
                                    @php $checkupsList = $checkups ?? collect(); @endphp
                                    @if($checkupsList->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Tanggal</th>
                                                        <th>Tinggi</th>
                                                        <th>Berat</th>
                                                        <th>BMI</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($checkupsList as $row)
                                                        <tr>
                                                            <td>{{ \Carbon\Carbon::parse($row->checkup_date)->isoFormat('D MMM Y') }}</td>
                                                            <td>{{ $row->height_cm ? $row->height_cm.' cm' : '-' }}</td>
                                                            <td>{{ $row->weight_kg ? $row->weight_kg.' kg' : '-' }}</td>
                                                            <td>
                                                                @if($row->bmi)
                                                                    <span class="badge bg-{{ (float) $row->bmi >= 17 && (float) $row->bmi < 23 ? 'success' : 'warning' }}-subtle text-{{ (float) $row->bmi >= 17 && (float) $row->bmi < 23 ? 'success' : 'warning' }}">
                                                                        {{ number_format((float) $row->bmi, 1) }}
                                                                    </span>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info-subtle text-info">
                                                                    {{ ucfirst(str_replace('_', ' ', $row->checkup_type ?? 'pemeriksaan')) }}
                                                                </span>
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
                                            <p class="text-muted mb-0">Belum ada riwayat pemeriksaan.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             TAB 4: IZIN SAKIT
                        ============================================================ --}}
                        <div class="tab-pane fade" id="izin-sakit" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4 d-flex align-items-center">
                                        <i class="ri-file-shield-2-line text-info me-2"></i>Riwayat Izin Sakit
                                    </h5>
                                    @php $permitsList = $permits ?? collect(); @endphp
                                    @if($permitsList->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Mulai</th>
                                                        <th>Selesai</th>
                                                        <th>Jenis</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($permitsList as $permit)
                                                        <tr>
                                                            <td>{{ \Carbon\Carbon::parse($permit->start_date)->isoFormat('D MMM Y') }}</td>
                                                            <td>{{ $permit->end_date ? \Carbon\Carbon::parse($permit->end_date)->isoFormat('D MMM Y') : '-' }}</td>
                                                            <td>{{ ucfirst(str_replace('_', ' ', $permit->permit_type ?? '-')) }}</td>
                                                            <td>
                                                                @php
                                                                    $statusColor = match($permit->status ?? null) {
                                                                        'disetujui' => 'success',
                                                                        'ditolak' => 'danger',
                                                                        default => 'warning'
                                                                    };
                                                                @endphp
                                                                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                                                    {{ ucfirst(str_replace('_', ' ', $permit->status ?? 'menunggu')) }}
                                                                </span>
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
                                            <p class="text-muted mb-0">Belum ada riwayat izin sakit.</p>
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
<script>
(function () {
    'use strict';
    var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'));
    triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener('click', function (e) {
            e.preventDefault();
            tabTrigger.show();
        });
    });
})();
</script>
@endsection