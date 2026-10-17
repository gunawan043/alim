@extends('layouts.master')

@section('title', 'Dashboard UKS')

@section('css')
    <link href="{{ URL::asset('build/libs/apexcharts/apexcharts.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@php
    $userId = request()->route('userId') ?? Auth::id();
@endphp

{{-- ── Breadcrumb ──────────────────────────────────────────────── --}}
@component('components.breadcrumb')
    @slot('li_1') UKS @endslot
    @slot('li_2') Dashboard @endslot
    @slot('title') Dashboard UKS @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ri-check-double-line me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row g-4 mb-4">

    {{-- 1. Santri Aktif Dirawat Sekarang --}}
    <div class="col-xl col-md-6 col-sm-6">
        <div class="card card-animate h-100 overflow-hidden">
            <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                <div class="position-absolute top-0 end-0 m-3">
                    <span class="avatar-lg rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center">
                        <i class="ri-pulse-line fs-3 text-warning"></i>
                    </span>
                </div>
                <div>
                    <p class="text-uppercase fw-medium text-muted mb-1" style="font-size: 11px; letter-spacing: 0.5px;">
                        Santri Dirawat Sekarang
                    </p>
                    <h2 class="fw-bold ff-secondary mb-0">{{ number_format($activePatients) }}</h2>
                    <p class="text-muted small mb-0 mt-1">
                        dari {{ number_format($totalSantri) }} total santri
                    </p>
                </div>
                <div class="progress mt-3" style="height: 4px;">
                    @php $pctActive = $totalSantri > 0 ? round($activePatients / $totalSantri * 100, 1) : 0; @endphp
                    <div class="progress-bar bg-warning" style="width: {{ min($pctActive, 100) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Selesai Hari Ini --}}
    <div class="col-xl col-md-6 col-sm-6">
        <div class="card card-animate h-100 overflow-hidden">
            @php
                $todayDischarged = \App\Models\Uks\UksPatient::whereDate('discharged_at', $today)
                    ->when($schoolId ?? null, fn ($q) => $q->where('school_id', $schoolId))
                    ->count();
            @endphp
            <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                <div class="position-absolute top-0 end-0 m-3">
                    <span class="avatar-lg rounded-circle bg-success-subtle d-flex align-items-center justify-content-center">
                        <i class="ri-check-double-line fs-3 text-success"></i>
                    </span>
                </div>
                <div>
                    <p class="text-uppercase fw-medium text-muted mb-1" style="font-size: 11px; letter-spacing: 0.5px;">
                        Pulang / Selesai Hari Ini
                    </p>
                    <h2 class="fw-bold ff-secondary mb-0">{{ number_format($todayDischarged) }}</h2>
                    <p class="text-muted small mb-0 mt-1">
                        selesai dirawat
                    </p>
                </div>
                <div class="d-flex gap-1 mt-3">
                    <span class="badge bg-success-subtle text-success" style="font-size: 10px;">
                        <i class="ri-arrow-right-line me-1"></i>Berbahana
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Beri Obat --}}
    <div class="col-xl col-md-6 col-sm-6">
        <div class="card card-animate h-100 overflow-hidden">
            @php
                $medicineGivenToday = \App\Models\Uks\UksPatient::whereDate('admitted_at', $today)
                    ->whereNotNull('medicine_given')
                    ->whereNotIn('status', ['selesai'])
                    ->when($schoolId ?? null, fn ($q) => $q->where('school_id', $schoolId))
                    ->count();
            @endphp
            <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                <div class="position-absolute top-0 end-0 m-3">
                    <span class="avatar-lg rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center">
                        <i class="ri-capsule-line fs-3 text-primary"></i>
                    </span>
                </div>
                <div>
                    <p class="text-uppercase fw-medium text-muted mb-1" style="font-size: 11px; letter-spacing: 0.5px;">
                        Beri Obat Hari Ini
                    </p>
                    <h2 class="fw-bold ff-secondary mb-0">{{ number_format($medicineGivenToday) }}</h2>
                    <p class="text-muted small mb-0 mt-1">
                        santri mendapat obat
                    </p>
                </div>
                <div class="d-flex gap-1 mt-3">
                    <span class="badge bg-primary-subtle text-primary" style="font-size: 10px;">
                        <i class="ri-flask-line me-1"></i>Pengobatan
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Rujukan Bulan Ini --}}
    <div class="col-xl col-md-6 col-sm-6">
        <div class="card card-animate h-100 overflow-hidden">
            <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                <div class="position-absolute top-0 end-0 m-3">
                    <span class="avatar-lg rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center">
                        <i class="ri-hospital-line fs-3 text-danger"></i>
                    </span>
                </div>
                <div>
                    <p class="text-uppercase fw-medium text-muted mb-1" style="font-size: 11px; letter-spacing: 0.5px;">
                        Rujukan ke Faskes
                    </p>
                    <h2 class="fw-bold ff-secondary mb-0">{{ number_format($monthReferrals) }}</h2>
                    <p class="text-muted small mb-0 mt-1">
                        bulan {{ $today->format('F Y') }}
                    </p>
                </div>
                <div class="d-flex gap-1 mt-3">
                    <span class="badge bg-danger-subtle text-danger" style="font-size: 10px;">
                        <i class="ri-truck-line me-1"></i>Transportasi
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- 5. Total GTK UKS --}}
    <div class="col-xl col-md-6 col-sm-6">
        <div class="card card-animate h-100 overflow-hidden">
            <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                <div class="position-absolute top-0 end-0 m-3">
                    <span class="avatar-lg rounded-circle bg-info-subtle d-flex align-items-center justify-content-center">
                        <i class="ri-users-line fs-3 text-info"></i>
                    </span>
                </div>
                <div>
                    <p class="text-uppercase fw-medium text-muted mb-1" style="font-size: 11px; letter-spacing: 0.5px;">
                        GTK Tim UKS
                    </p>
                    <h2 class="fw-bold ff-secondary mb-0">{{ number_format($uksGtkCount) }}</h2>
                    <p class="text-muted small mb-0 mt-1">
                        tenaga kesehatan aktif
                    </p>
                </div>
                <div class="d-flex gap-1 mt-3">
                    <span class="badge bg-info-subtle text-info" style="font-size: 10px;">
                        <i class="ri-shield-user-line me-1"></i>Personel
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>
<!--end row-->

{{-- ── STATUS PERAWATAN — STATUS BREAKDOWN ──────────────────────── --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card card-hover">
            <div class="card-header border-dashed d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-0 fs-15"><i class="ri-pulse-line me-2"></i>Status Perawatan</h4>
                    <p class="text-muted small mb-0 mt-1">Ringkasan status perawatan pasien &amp; ketersediaan bed</p>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @php
                        $statusCards = [
                            ['key' => 'rawat_uks',         'label' => 'Sedang Dirawat',   'icon' => 'ri-hospital-line',      'color' => 'primary'],
                            ['key' => 'observasi',         'label' => 'Observasi',         'icon' => 'ri-eye-line',           'color' => 'warning'],
                            ['key' => 'kembali_ke_asrama','label' => 'Kembali ke Asrama', 'icon' => 'ri-home-heart-line',    'color' => 'success'],
                            ['key' => 'dirujuk',           'label' => 'Dirujuk',           'icon' => 'ri-hospital-line',      'color' => 'danger'],
                        ];
                    @endphp
                    @foreach($statusCards as $card)
                        <div class="col-xl col-md-6">
                            <div class="border rounded p-3 h-100 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small text-uppercase fw-medium" style="font-size: 10px; letter-spacing: 0.5px;">
                                        {{ $card['label'] }}
                                    </div>
                                    <h4 class="mb-0 mt-1 fw-bold ff-secondary">
                                        {{ $patientStatusCounts[$card['key']] ?? 0 }}
                                    </h4>
                                </div>
                                <span class="avatar-sm rounded bg-{{ $card['color'] }}-subtle d-flex align-items-center justify-content-center">
                                    <i class="{{ $card['icon'] }} fs-4 text-{{ $card['color'] }}"></i>
                                </span>
                            </div>
                        </div>
                    @endforeach

                    @php
                        $bedTerisi = $bedStats['occupied'] ?? 0;
                        $bedKosong = $bedStats['available'] ?? 0;
                    @endphp
                    <div class="col-xl col-md-6">
                        <div class="border rounded p-3 h-100 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small text-uppercase fw-medium" style="font-size: 10px; letter-spacing: 0.5px;">Bed Terisi</div>
                                <h4 class="mb-0 mt-1 fw-bold ff-secondary">{{ $bedTerisi }}</h4>
                            </div>
                            <span class="avatar-sm rounded bg-danger-subtle d-flex align-items-center justify-content-center">
                                <i class="ri-hotel-bed-fill fs-4 text-danger"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-xl col-md-6">
                        <div class="border rounded p-3 h-100 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small text-uppercase fw-medium" style="font-size: 10px; letter-spacing: 0.5px;">Bed Kosong</div>
                                <h4 class="mb-0 mt-1 fw-bold ff-secondary">{{ $bedKosong }}</h4>
                            </div>
                            <span class="avatar-sm rounded bg-success-subtle d-flex align-items-center justify-content-center">
                                <i class="ri-hotel-bed-line fs-4 text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end row-->

{{-- ── MAIN CONTENT ROW ────────────────────────────────────────── --}}
<div class="row g-4">

    {{-- ── SANTRI AKTIF DIANGKUT (RAWAT / BALIK / PULANG) TABLE ─── --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header border-dashed d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-0 fs-15"><i class="ri-hospital-fill me-2"></i>Santri dalam Perawatan</h4>
                    <p class="text-muted small mb-0 mt-1">Status terkini — rawat di UKS, pulang asrama, atau kembali</p>
                </div>
                <a href="{{ route('user.uks.patients.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-primary">
                    <i class="ri-list-check me-1"></i> Lihat Semua
                </a>
            </div>
            <div class="card-body p-0">
                @if($recentPatients->isEmpty())
                    <div class="text-center py-5">
                        <div class="avatar-sm mx-auto mb-3">
                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                <i class="bx bx-check-circle text-success"></i>
                            </span>
                        </div>
                        <p class="text-muted mb-1 fw-medium">Tidak ada pasien aktif saat ini.</p>
                        <p class="text-muted small">Semua santri telah pulang atau selesai perawatan.</p>
                    </div>
                @else
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px">No</th>
                                    <th>Nama Siswa</th>
                                    <th>Tipe Status</th>
                                    <th>Keluhan</th>
                                    <th>Obat Diberi</th>
                                    <th>Status</th>
                                    <th style="width: 140px">Aksi Cepat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentPatients as $idx => $patient)
                                <tr>
                                    <td class="text-muted fw-medium">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="flex-shrink-0 avatar-xs">
                                                <span class="avatar-title bg-{{ $patient->student?->gender === 'L' ? 'primary-subtle' : 'danger-subtle' }} rounded-circle">
                                                    {{ strtoupper(substr($patient->student?->name ?? '?', 0, 1)) }}
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="{{ route('user.uks.patients.show', ['uuid' => $patient->id, 'userId' => $userId]) }}"
                                                   class="fw-medium text-reset text-decoration-none">
                                                    {{ $patient->student?->name ?? '-' }}
                                                </a>
                                                <div class="text-muted small" style="font-size: 11px;">
                                                    <i class="ri-home-7-line me-1"></i>{{ $patient->student?->dormitory?->name ?? 'Tidak berasrama' }}
                                                    @if($patient->student)
                                                        &middot; {{ $patient->student->classGroup?->name ?? '-' }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-{{ $patient->patient_type === 'rawat' ? 'info' : ($patient->patient_type === 'balik' ? 'warning' : 'secondary') }}">
                                            @if($patient->patient_type === 'rawat')
                                                <i class="ri-hospital-line me-1"></i>Rawat
                                            @elseif($patient->patient_type === 'balik')
                                                <i class="ri-arrow-go-back-line me-1"></i>Balik Asrama
                                            @else
                                                <i class="ri-roadster-line me-1"></i>Pulang
                                            @endif
                                        </span>
                                    </td>
                                    <td><span class="text-muted" style="font-size: 13px;">{{ Str::limit($patient->chief_complaint, 35) }}</span></td>
                                    <td>
                                        @if($patient->medicine_given)
                                            <span class="badge bg-primary-subtle text-primary" style="font-size: 11px;">
                                                <i class="ri-capsule-fill me-1"></i>{{ $patient->medicine_given }}
                                            </span>
                                        @else
                                            <span class="text-muted" style="font-size: 12px;">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($patient->status) {
                                                'aktif' => 'warning',
                                                'selesai' => 'success',
                                                'dirujuk' => 'danger',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge badge-soft-{{ $statusClass }}">
                                            {{ ucfirst($patient->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-list gap-1 flex-nowrap">
                                            @if($patient->status === 'aktif')
                                                {{-- Quick: mark discharge to pulang --}}
                                                <form action="{{ route('user.uks.patients.discharge', ['uuid' => $patient->id, 'userId' => $userId]) }}"
                                                      method="POST" style="display:inline;"
                                                      onsubmit="return confirm('Tandai pulang sebagai selesai?')">
                                                    @csrf
                                                    <input type="hidden" name="action" value="selesai">
                                                    <button type="submit" class="btn btn-soft-success btn-sm px-2" title="Pulangkan" data-bs-toggle="tooltip">
                                                        <i class="ri-roadster-line"></i>
                                                    </button>
                                                </form>
                                                {{-- Quick: mark return ke asrama --}}
                                                <form action="{{ route('user.uks.patients.mark-return', ['uuid' => $patient->id, 'userId' => $userId]) }}"
                                                      method="POST" style="display:inline;"
                                                      onsubmit="return confirm('Tandai kembali ke asrama?')">
                                                    @csrf
                                                    <input type="hidden" name="return_type" value="balik">
                                                    <input type="hidden" name="returned_at" value="{{ now()->format('Y-m-d H:i') }}">
                                                    <button type="submit" class="btn btn-soft-warning btn-sm px-2" title="Balik ke Asrama" data-bs-toggle="tooltip">
                                                        <i class="ri-arrow-go-back-line"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('user.uks.patients.show', ['uuid' => $patient->id, 'userId' => $userId]) }}"
                                               class="btn btn-soft-secondary btn-sm px-2" title="Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── RIGHT SIDEBAR: Summary Cards ──────────────────────────── --}}

    {{-- Ringkasan Pasien Hari Ini --}}
    <div class="col-xl-4">
        {{-- Summary of Today --}}
        <div class="card mb-4">
            <div class="card-header border-dashed">
                <h4 class="mb-0 fs-15"><i class="ri-calendar-schedule-line me-2"></i>Ringkasan Hari Ini</h4>
            </div>
            <div class="card-body p-0">
                @php
                    $todayPatients = \App\Models\Uks\UksPatient::whereDate('admitted_at', $today)
                        ->when($schoolId ?? null, fn ($q) => $q->where('school_id', $schoolId))
                        ->get();
                    $todayRawat = $todayPatients->where('patient_type', 'rawat')->count();
                    $todayPulang = $todayPatients->where('patient_type', 'pulang')->count();
                    $todayBalik = $todayPatients->where('patient_type', 'balik')->count();
                    $todayObat = $todayPatients->whereNotNull('medicine_given')->count();
                @endphp
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-xs rounded-circle bg-info-subtle d-flex align-items-center justify-content-center">
                                <i class="ri-hospital-line text-info" style="font-size: 12px;"></i>
                            </span>
                            <span class="text-muted" style="font-size: 13px;">Dirawat (Rawat)</span>
                        </div>
                        <span class="fw-bold fs-5">{{ $todayRawat }}</span>
                    </div>
                    <div class="list-group-item px-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-xs rounded-circle bg-success-subtle d-flex align-items-center justify-content-center">
                                <i class="ri-roadster-line text-success" style="font-size: 12px;"></i>
                            </span>
                            <span class="text-muted" style="font-size: 13px;">Dipulangkan</span>
                        </div>
                        <span class="fw-bold fs-5">{{ $todayPulang }}</span>
                    </div>
                    <div class="list-group-item px-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-xs rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center">
                                <i class="ri-arrow-go-back-line text-warning" style="font-size: 12px;"></i>
                            </span>
                            <span class="text-muted" style="font-size: 13px;">Balik ke Asrama</span>
                        </div>
                        <span class="fw-bold fs-5">{{ $todayBalik }}</span>
                    </div>
                    <div class="list-group-item px-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-xs rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center">
                                <i class="ri-capsule-fill text-primary" style="font-size: 12px;"></i>
                            </span>
                            <span class="text-muted" style="font-size: 13px;">Diberi Obat</span>
                        </div>
                        <span class="fw-bold fs-5">{{ $todayObat }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Breakdown --}}
        <div class="card mb-4">
            <div class="card-header border-dashed">
                <h4 class="mb-0 fs-15"><i class="ri-pulse-line me-2"></i>Status Pasien</h4>
            </div>
            <div class="card-body p-0">
                @php
                    $statusItems = [
                        ['key' => 'menunggu', 'label' => 'Menunggu', 'color' => 'warning', 'icon' => 'ri-time-line'],
                        ['key' => 'sedang_ditangani', 'label' => 'Ditangani', 'color' => 'warning', 'icon' => 'ri-stethoscope-line'],
                        ['key' => 'observasi', 'label' => 'Observasi', 'color' => 'primary', 'icon' => 'ri-eye-line'],
                        ['key' => 'rawat_uks', 'label' => 'Rawat UKS', 'color' => 'primary', 'icon' => 'ri-hospital-line'],
                        ['key' => 'istirahat_di_uks', 'label' => 'Istirahat UKS', 'color' => 'info', 'icon' => 'ri-sleep-line'],
                        ['key' => \App\Models\Uks\UksPatient::STATUS_RETURN_DORM, 'label' => 'Kembali Asrama', 'color' => 'success', 'icon' => 'ri-home-heart-line'],
                        ['key' => \App\Models\Uks\UksPatient::STATUS_RETURN_SCHOOL, 'label' => 'Kembali Sekolah', 'color' => 'success', 'icon' => 'ri-school-line'],
                        ['key' => \App\Models\Uks\UksPatient::STATUS_PICKED_UP, 'label' => 'Dijemput Wali', 'color' => 'secondary', 'icon' => 'ri-user-heart-line'],
                        ['key' => 'pulang', 'label' => 'Pulang', 'color' => 'dark', 'icon' => 'ri-logout-box-r-line'],
                        ['key' => 'dirujuk', 'label' => 'Dirujuk', 'color' => 'danger', 'icon' => 'ri-hospital-line'],
                        ['key' => 'selesai', 'label' => 'Selesai', 'color' => 'success', 'icon' => 'ri-check-line'],
                    ];
                @endphp
                <div class="list-group list-group-flush">
                    @foreach($statusItems as $item)
                    <div class="list-group-item px-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-xs rounded-circle bg-{{ $item['color'] }}-subtle d-flex align-items-center justify-content-center">
                                <i class="{{ $item['icon'] }} text-{{ $item['color'] }}" style="font-size: 12px;"></i>
                            </span>
                            <span class="text-muted" style="font-size: 13px;">{{ $item['label'] }}</span>
                        </div>
                        <span class="fw-bold fs-5">{{ number_format($patientStatusCounts[$item['key']] ?? 0) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Distribusi Golongan Darah GTK --}}
        <div class="card mb-4">
            <div class="card-header border-dashed">
                <h4 class="mb-0 fs-15"><i class="ri-droplet-line me-2"></i>Golongan Darah GTK</h4>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach(['A', 'B', 'AB', 'O'] as $bt)
                    @php
                        $colors = ['A' => 'danger', 'B' => 'info', 'AB' => 'warning', 'O' => 'success'];
                        $color = $colors[$bt] ?? 'secondary';
                    @endphp
                    <div class="list-group-item px-0 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-{{ $color }} rounded-pill"
                                  style="min-width: 36px; text-align: center; font-size: 13px;">
                                {{ $bt }}+
                            </span>
                            <span class="text-muted" style="font-size: 13px;">Gol. {{ $bt }}</span>
                        </div>
                        <span class="fw-bold fs-5">{{ number_format($bloodTypeSummary[$bt] ?? 0) }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="text-center mt-3">
                    <a href="{{ route('user.uks.gtk-health.index', ['userId' => $userId]) }}" class="text-decoration-underline text-muted small">
                        Kelola data GTK &rarr;
                    </a>
                </div>
            </div>
        </div>

        {{-- Registrasi Pasien Baru (Quick Form Trigger) --}}
        <div class="card">
            <div class="card-body text-center p-4">
                <div class="avatar-lg mx-auto mb-3 rounded-circle bg-gradient-primary d-flex align-items-center justify-content-center">
                    <i class="ri-add-circle-line text-white fs-2"></i>
                </div>
                <h5 class="mb-1">Registrasi Pasien Baru</h5>
                <p class="text-muted small mb-3">Daftarkan santri yang datang ke UKS</p>
                <a href="{{ route('user.uks.patients.create', ['userId' => $userId]) }}" class="btn btn-primary w-100">
                    <i class="ri-add-line me-2"></i>Daftar Pasien Baru
                </a>
            </div>
        </div>
    </div>

</div>
<!--end row-->
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate counters
    document.querySelectorAll('.card-animate').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = '0 0.5rem 1.5rem rgba(0, 0, 0, 0.08)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
});
</script>
@endsection
