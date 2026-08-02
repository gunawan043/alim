@extends('layouts.master')
@section('title') Detail Pasien - UKS @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Rekam Medis Pasien @endslot
        @slot('li_3') Daftar Pasien @endslot
        @slot('title') Detail Pasien @endslot
    @endcomponent

    @php
        $patientStatusLabel = ucfirst(str_replace('_', ' ', $patient->status));
        $statusColor = match($patient->status) {
            \App\Models\Uks\UksPatient::STATUS_WAITING,
            \App\Models\Uks\UksPatient::STATUS_TREATED          => 'warning',
            \App\Models\Uks\UksPatient::STATUS_OBSERVATION,
            \App\Models\Uks\UksPatient::STATUS_INPATIENT         => 'primary',
            \App\Models\Uks\UksPatient::STATUS_RESTING_UKS       => 'info',
            \App\Models\Uks\UksPatient::STATUS_RETURN_DORM,
            \App\Models\Uks\UksPatient::STATUS_RETURN_SCHOOL,
            \App\Models\Uks\UksPatient::STATUS_LEAVING,
            \App\Models\Uks\UksPatient::STATUS_COMPLETED         => 'success',
            \App\Models\Uks\UksPatient::STATUS_PICKED_UP         => 'secondary',
            \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC,
            \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL => 'danger',
            default                                               => 'light',
        };
        $gender = $patient->student?->gender;
        $genderColor = $gender === 'P' ? 'danger' : 'primary';
        $genderLabel = $gender === 'P' ? 'Putri' : ($gender === 'L' ? 'Putra' : '-');
    @endphp

    {{-- ============================================================
         PROFILE HEADER
    ============================================================ --}}
    <div class="row g-3 mb-2">
        <div class="col-12">
            <div class="card card-animate border border-{{ $statusColor }}-subtle">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="avatar-lg">
                            <div class="avatar-title bg-{{ $genderColor }}-subtle text-{{ $genderColor }} rounded-circle fs-1 fw-bold">
                                {{ strtoupper(substr($patient->student?->name ?? '?', 0, 1)) }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-1 fw-bold">{{ $patient->student?->name ?? '-' }}</h4>
                            <div class="d-flex flex-wrap gap-3 align-items-center text-muted fs-12">
                                <span>
                                    <i class="ri-user-line me-1"></i>{{ $genderLabel }}
                                </span>
                                <span>
                                    <i class="ri-home-2-line me-1"></i>
                                    {{ $patient->student?->dormitory?->name ?? 'Tanpa Asrama' }}
                                </span>
                                <span>
                                    <i class="ri-barcode-line me-1"></i>
                                    {{ $patient->patient_uuid ?? $patient->id }}
                                </span>
                                <span>
                                    <i class="ri-calendar-line me-1"></i>
                                    Daftar: {{ $patient->admitted_at ? $patient->admitted_at->format('d M Y H:i') : '-' }}
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} fs-13 px-3 py-2">
                                <i class="ri-pulse-line me-1"></i>{{ $patientStatusLabel }}
                            </span>
                            <div class="mt-2">
                                @if($patient->patient_type === 'rawat')
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="ri-hospital-line me-1"></i>Rawat
                                    </span>
                                @elseif($patient->patient_type === 'balik')
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="ri-arrow-go-back-line me-1"></i>Balik Asrama
                                    </span>
                                @elseif($patient->patient_type === 'pulang')
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <i class="ri-roadster-line me-1"></i>Pulang
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- ============================================================
             DIAGNOSIS & MEDICINE
        ============================================================ --}}
        <div class="col-xl-6">
            <div class="card card-animate h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">
                        <i class="ri-stethoscope-line align-bottom me-1 text-info"></i>
                        Diagnosis & Pengobatan
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted fw-normal">Keluhan Utama</dt>
                        <dd class="col-sm-8">{{ $patient->chief_complaint ?? '-' }}</dd>
                        <dt class="col-sm-4 text-muted fw-normal">Diagnosis</dt>
                        <dd class="col-sm-8">{{ $patient->diagnosis ?? '-' }}</dd>
                        <dt class="col-sm-4 text-muted fw-normal">Pengobatan</dt>
                        <dd class="col-sm-8">{{ $patient->treatment ?? '-' }}</dd>
                        <dt class="col-sm-4 text-muted fw-normal">Obat Diberikan</dt>
                        <dd class="col-sm-8">
                            @if($patient->medicine_given)
                                <span class="badge bg-success-subtle text-success">
                                    <i class="ri-capsule-line me-1"></i>{{ $patient->medicine_given }}
                                </span>
                            @else
                                -
                            @endif
                        </dd>
                        @if($patient->medication_details)
                            <dt class="col-sm-4 text-muted fw-normal">Detail Obat</dt>
                            <dd class="col-sm-8">{{ $patient->medication_details }}</dd>
                        @endif
                        @if($patient->referred_to_faskes)
                            <dt class="col-sm-4 text-muted fw-normal">Dirujuk Faskes</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="ri-ambulance-line me-1"></i>Ya
                                </span>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- ============================================================
             VITALS
        ============================================================ --}}
        <div class="col-xl-6">
            <div class="card card-animate h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">
                        <i class="ri-pulse-line align-bottom me-1 text-primary"></i>
                        Tanda Vital
                    </h5>
                </div>
                <div class="card-body">
                    @if($patient->vitals)
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="border rounded p-3 text-center">
                                    <i class="ri-heart-pulse-line text-danger fs-2"></i>
                                    <p class="text-muted text-uppercase fs-11 mb-1 mt-1">Tekanan Darah</p>
                                    <h5 class="mb-0 fw-bold">{{ $patient->vitals['blood_pressure'] ?? '-' }}</h5>
                                    <small class="text-muted">mmHg</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3 text-center">
                                    <i class="ri-temp-hot-line text-warning fs-2"></i>
                                    <p class="text-muted text-uppercase fs-11 mb-1 mt-1">Suhu</p>
                                    <h5 class="mb-0 fw-bold">{{ $patient->vitals['temperature'] ?? '-' }}</h5>
                                    <small class="text-muted">°C</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-3 text-center">
                                    <i class="ri-heart-line text-info fs-3"></i>
                                    <p class="text-muted text-uppercase fs-11 mb-1 mt-1">Nadi</p>
                                    <h6 class="mb-0 fw-bold">{{ $patient->vitals['pulse'] ?? '-' }}</h6>
                                    <small class="text-muted">/min</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-3 text-center">
                                    <i class="ri-ruler-line text-secondary fs-3"></i>
                                    <p class="text-muted text-uppercase fs-11 mb-1 mt-1">Tinggi</p>
                                    <h6 class="mb-0 fw-bold">{{ $patient->vitals['height'] ?? '-' }}</h6>
                                    <small class="text-muted">cm</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-3 text-center">
                                    <i class="ri-scales-line text-success fs-3"></i>
                                    <p class="text-muted text-uppercase fs-11 mb-1 mt-1">Berat</p>
                                    <h6 class="mb-0 fw-bold">{{ $patient->vitals['weight'] ?? '-' }}</h6>
                                    <small class="text-muted">kg</small>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-muted text-center py-4">
                            <i class="ri-pulse-line fs-1 d-block mb-2 opacity-50"></i>
                            Tanda vital belum tercatat
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============================================================
             BED ASSIGNMENT
        ============================================================ --}}
        @if($patient->currentBedAssignment)
            <div class="col-12">
                <div class="card card-animate">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">
                            <i class="ri-bed-line align-bottom me-1 text-info"></i>
                            Info Ranjang
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <p class="text-muted text-uppercase fs-11 mb-1">Ranjang</p>
                                <h6 class="mb-0 fw-bold">
                                    {{ $patient->currentBedAssignment->bed->identifier ?? $patient->bed_number ?? '-' }}
                                </h6>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted text-uppercase fs-11 mb-1">Status</p>
                                <h6 class="mb-0 fw-bold">
                                    @if($patient->currentBedAssignment->status === 'assigned')
                                        <span class="badge bg-primary-subtle text-primary">Ditempatkan</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($patient->currentBedAssignment->status) }}</span>
                                    @endif
                                </h6>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted text-uppercase fs-11 mb-1">Waktu Masuk</p>
                                <h6 class="mb-0 fw-bold">
                                    {{ $patient->taken_bed_at ? $patient->taken_bed_at->format('d M Y H:i') : '-' }}
                                </h6>
                            </div>
                            @if($patient->left_bed_at)
                                <div class="col-md-3">
                                    <p class="text-muted text-uppercase fs-11 mb-1">Waktu Keluar</p>
                                    <h6 class="mb-0 fw-bold">{{ $patient->left_bed_at->format('d M Y H:i') }}</h6>
                                </div>
                            @endif
                            @if($patient->currentBedAssignment->reason)
                                <div class="col-12">
                                    <p class="text-muted text-uppercase fs-11 mb-1">Alasan</p>
                                    <p class="mb-0">{{ $patient->currentBedAssignment->reason }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============================================================
             CARE TIMELINE
        ============================================================ --}}
        @if($allCareEvents->count() > 0)
            <div class="col-lg-8">
                <div class="card card-animate">
                    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-timeline-line align-bottom me-1 text-primary"></i>
                            Riwayat Perawatan
                        </h5>
                        <span class="badge bg-primary-subtle text-primary">{{ $allCareEvents->count() }} event</span>
                    </div>
                    <div class="card-body">
                        <div class="timeline timeline-with-check">
                            @foreach($allCareEvents as $event)
                                @php
                                    $eventColor = match($event->event_type) {
                                        'masuk' => 'primary',
                                        'pemeriksaan', 'pemeriksaan_ulang' => 'info',
                                        'pemberian_obat' => 'success',
                                        'istirahat' => 'warning',
                                        'pulang' => 'secondary',
                                        'dirujuk' => 'danger',
                                        'kembali_asrama', 'kembali_sekolah', 'jemput_wali' => 'purple',
                                        default => 'light',
                                    };
                                @endphp
                                <div class="timeline-item">
                                    <div class="timeline-dot bg-{{ $eventColor }}"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="badge bg-{{ $eventColor }}-subtle text-{{ $eventColor }}">
                                                {{ $event->eventTypeLabel() }}
                                            </span>
                                            <small class="text-muted">{{ $event->happened_at->format('d M Y H:i') }}</small>
                                        </div>
                                        @if($event->event_title)
                                            <div class="fw-semibold">{{ $event->event_title }}</div>
                                        @endif
                                        @if($event->description)
                                            <div class="text-muted small">{{ $event->description }}</div>
                                        @endif
                                        @if($event->performedBy)
                                            <div class="text-muted small mt-1">
                                                <i class="ri-user-line me-1"></i>{{ $event->performedBy->name }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============================================================
             MEDICATION HISTORY
        ============================================================ --}}
        @if($patient->medicationLogs && $patient->medicationLogs->count() > 0)
            <div class="@if($allCareEvents->count() > 0) col-lg-4 @else col-12 @endif">
                <div class="card card-animate h-100">
                    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-capsule-line align-bottom me-1 text-success"></i>
                            Pemberian Obat
                        </h5>
                        <span class="badge bg-success-subtle text-success">{{ $patient->medicationLogs->count() }}×</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-muted">Waktu</th>
                                        <th class="text-muted">Obat</th>
                                        <th class="text-muted">Dosis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($patient->medicationLogs as $log)
                                        <tr>
                                            <td><small class="text-muted">{{ $log->given_at ? $log->given_at->format('d M H:i') : '-' }}</small></td>
                                            <td>{{ $log->medicine_name ?? '-' }}</td>
                                            <td><small>{{ $log->dosage ?? '-' }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============================================================
             NOTES
        ============================================================ --}}
        @if($patient->notes)
            <div class="col-12">
                <div class="card card-animate">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">
                            <i class="ri-file-text-line align-bottom me-1 text-muted"></i>
                            Catatan
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-muted">{{ nl2br(e($patient->notes)) }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Riwayat Kunjungan UKS --}}
        @if($previousPatients->isNotEmpty())
            <div class="col-12">
                <div class="card card-animate">
                    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-eye-line align-bottom me-1 text-primary"></i>
                            Riwayat Kunjungan UKS
                        </h5>
                        <span class="badge bg-primary-subtle text-primary">{{ $previousPatients->count() }} episode</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-muted">Tanggal Masuk</th>
                                        <th class="text-muted">Tanggal Selesai</th>
                                        <th class="text-muted">Status</th>
                                        <th class="text-muted">Dikelola</th>
                                        <th class="text-muted text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previousPatients as $prev)
                                        @php
                                            $prevStatusColor = match($prev->status) {
                                                \App\Models\Uks\UksPatient::STATUS_WAITING => 'warning',
                                                \App\Models\Uks\UksPatient::STATUS_TREATED => 'info',
                                                \App\Models\Uks\UksPatient::STATUS_OBSERVATION => 'info',
                                                \App\Models\Uks\UksPatient::STATUS_INPATIENT => 'primary',
                                                \App\Models\Uks\UksPatient::STATUS_RESTING_UKS => 'info',
                                                \App\Models\Uks\UksPatient::STATUS_COMPLETED => 'success',
                                                \App\Models\Uks\UksPatient::STATUS_RETURN_DORM,
                                                \App\Models\Uks\UksPatient::STATUS_RETURN_SCHOOL,
                                                \App\Models\Uks\UksPatient::STATUS_LEAVING => 'secondary',
                                                \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC,
                                                \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <tr>
                                            <td><small>{{ $prev->admitted_at ? $prev->admitted_at->format('d M Y H:i') : '-' }}</small></td>
                                            <td><small>{{ $prev->discharged_at ? $prev->discharged_at->format('d M Y H:i') : '-' }}</small></td>
                                            <td>
                                                <span class="badge bg-{{ $prevStatusColor }}-subtle text-{{ $prevStatusColor }}">
                                                    {{ ucfirst(str_replace('_', ' ', $prev->status)) }}
                                                </span>
                                            </td>
                                            <td><small>{{ $prev->admittedBy->name ?? '-' }}</small></td>
                                            <td class="text-end">
                                                <a href="{{ route('user.uks.patients.show', ['userId' => auth()->user()->id, 'uuid' => $prev->id]) }}" class="btn btn-sm btn-soft-primary">
                                                    <i class="ri-eye-line align-bottom me-1"></i> Lihat
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============================================================
             STATUS ACTIONS
        ============================================================ --}}
        @if(!empty($actions))
            <div class="col-12">
                <div class="card card-animate">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">
                            <i class="ri-flashlight-line align-bottom me-1 text-warning"></i>
                            Tindakan Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($actions as $action)
                                <form action="{{ route('user.uks.patients.change-status', ['userId' => auth()->user()->id, 'uuid' => $patient->id]) }}"
                                      method="POST"
                                      onsubmit="return confirm('{{ $action['confirm'] }}')">
                                    @csrf
                                    <input type="hidden" name="new_status" value="{{ $action['status'] }}">
                                    <button type="submit" class="btn btn-{{ $action['color'] }} btn-sm">
                                        <i class="{{ $action['icon'] }} me-1"></i>
                                        {{ $action['label'] }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============================================================
             META & FOOTER
        ============================================================ --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-0 fs-12">
                                <i class="ri-user-add-line me-1"></i>
                                Didaftarkan oleh: <strong>{{ $patient->admittedBy->name ?? '-' }}</strong>
                                <i class="ri-calendar-line ms-3 me-1"></i>
                                {{ $patient->admitted_at ? $patient->admitted_at->format('d M Y H:i') : '-' }}
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <a href="{{ route('user.uks.patients.index', ['userId' => auth()->user()->id]) }}" class="btn btn-light">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> Kembali
                            </a>
                            <a href="{{ route('user.uks.treatment-status.show', ['userId' => auth()->user()->id, 'uuid' => $patient->id]) }}"
                               class="btn btn-primary">
                                <i class="ri-pulse-line align-bottom me-1"></i> Status Perawatan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection