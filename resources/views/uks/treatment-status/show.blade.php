@extends('layouts.master')

@section('title', 'Status Perawatan - UKS')
@section('subtitle', 'Status Perawatan & Perkembangan')

@php
    $student = $patient->student;
    $studentName = $student->name ?? 'Pasien';
    $initialLetter = strtoupper(substr($studentName, 0, 1));
    $genderBadgeColor = $student->gender === 'L' ? 'primary' : 'danger';
    $genderLabel = $student->gender === 'L' ? 'Putra' : 'Putri';
    $dormName = $student->dormitory->name ?? null;

    $patientStatusLabel = $statusLabels[$patient->status] ?? ucfirst($patient->status);
    $badgeColor = match($patient->status) {
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

    $isInCare = in_array($patient->status, [
        \App\Models\Uks\UksPatient::STATUS_INPATIENT,
        \App\Models\Uks\UksPatient::STATUS_RESTING_UKS,
    ], true);

    $currentBed = $patient->currentBedAssignment?->bed;
    $bedLabel = $currentBed ? trim(($currentBed->building ?? '').' '.($currentBed->room ?? '').' '.($currentBed->bed_number ?? '')) : null;

    $treatmentNotes = $patient->treatmentNotes->sortBy('recorded_at');
    $medicationAdmins = $patient->medicationAdministrations->sortBy('given_at');
    $statusHistories = $patient->statusHistories->sortBy('changed_at');

    $stats = [
        ['label' => 'Catatan', 'value' => $treatmentNotes->count(), 'icon' => 'ri-file-list-3-line', 'color' => 'primary'],
        ['label' => 'Pemberian Obat', 'value' => $medicationAdmins->count(), 'icon' => 'ri-capsule-line', 'color' => 'success'],
        ['label' => 'Perubahan Status', 'value' => $statusHistories->count(), 'icon' => 'ri-history-line', 'color' => 'info'],
    ];

    $durationSinceAdmission = $patient->admitted_at
        ? $patient->admitted_at->diffForHumans(now(), ['parts' => 2, 'short' => true])
        : '-';
@endphp

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0">Status Perawatan Pasien</h4>
                <small class="text-muted">Pantau perkembangan, obat, dan histori status perawatan</small>
            </div>
            <div class="page-title-right">
                <a href="{{ route('user.uks.patients.show', ['uuid' => $patient->id]) }}" class="btn btn-soft-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Profile Header --}}
<div class="row">
    <div class="col-12">
        <div class="card profile-user-card mb-0">
            <div class="card-body">
                <div class="row align-items-center gy-3">
                    <div class="col-auto">
                        <div class="avatar-xl">
                            <span class="avatar-title bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} rounded-circle fs-1">
                                {{ $initialLetter }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md">
                        <h4 class="mb-1">{{ $studentName }}</h4>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="badge bg-{{ $genderBadgeColor }}-subtle text-{{ $genderBadgeColor }}">
                                <i class="ri-user-line me-1"></i>{{ $genderLabel }}
                            </span>
                            @if($dormName)
                                <span class="badge bg-light text-muted">
                                    <i class="ri-building-line me-1"></i>{{ $dormName }}
                                </span>
                            @endif
                            <span class="badge badge-soft-{{ $badgeColor }} fs-12">
                                <i class="ri-pulse-line me-1"></i>{{ $patientStatusLabel }}
                            </span>
                            @if($bedLabel)
                                <span class="badge badge-soft-info fs-12">
                                    <i class="ri-bed-line me-1"></i>{{ $bedLabel }}
                                </span>
                            @endif
                        </div>
                        <div class="text-muted small">
                            <i class="ri-login-box-line me-1"></i>Didaftarkan {{ $patient->admitted_at?->format('d M Y H:i') }}
                            <span class="mx-2">·</span>
                            <i class="ri-time-line me-1"></i>{{ $durationSinceAdmission }} sejak pendaftaran
                            @if($patient->admittedBy)
                                <span class="mx-2">·</span>
                                <i class="ri-user-star-line me-1"></i>oleh {{ $patient->admittedBy->name }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Stats Row --}}
<div class="row">
    @foreach($stats as $stat)
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-md flex-shrink-0">
                        <span class="avatar-title bg-{{ $stat['color'] }}-subtle text-{{ $stat['color'] }} rounded fs-20">
                            <i class="{{ $stat['icon'] }}"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-muted mb-1 fs-13">{{ $stat['label'] }}</p>
                        <h4 class="mb-0">{{ $stat['value'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Action Panel: Status + Bed --}}
<div class="row">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0"><i class="ri-toggle-line me-2 text-primary"></i>Ubah Status Perawatan</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('user.uks.treatment-status.update-status', ['uuid' => $patient->id]) }}"
                      method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Status Baru</label>
                            <select name="new_status" class="form-select" required>
                                @foreach($statusLabels as $key => $label)
                                    <option value="{{ $key }}" {{ $patient->status === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Alasan (opsional)</label>
                            <input type="text" name="reason" maxlength="500" class="form-control"
                                   placeholder="cth: Kondisi membaik, dipindahkan ke klinik, dll.">
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i>Simpan Status
                        </button>
                        <a href="{{ route('user.uks.patients.show', ['uuid' => $patient->id]) }}" class="btn btn-soft-secondary">
                            <i class="ri-file-text-line me-1"></i>Lihat Detail
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0"><i class="ri-bed-line me-2 text-info"></i>Penempatan Bed</h4>
                @if($patient->currentBedAssignment)
                    <form action="{{ route('user.uks.treatment-status.release-bed', ['uuid' => $patient->id]) }}"
                          method="POST"
                          class="ms-auto"
                          onsubmit="return confirm('Kosongkan bed pasien ini?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-soft-warning">
                            <i class="ri-logout-box-r-line me-1"></i>Kosongkan
                        </button>
                    </form>
                @endif
            </div>
            <div class="card-body">
                @if($patient->currentBedAssignment)
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <th class="text-muted" style="width: 40%;"><i class="ri-building-2-line me-1"></i>Gedung</th>
                                    <td class="fw-medium">{{ $currentBed->building ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted"><i class="ri-door-open-line me-1"></i>Ruangan</th>
                                    <td class="fw-medium">{{ $currentBed->room ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted"><i class="ri-hotel-bed-line me-1"></i>Bed</th>
                                    <td><span class="badge badge-soft-info fs-12">{{ $currentBed->bed_number }}</span></td>
                                </tr>
                                <tr>
                                    <th class="text-muted"><i class="ri-time-line me-1"></i>Sejak</th>
                                    <td>{{ ($patient->taken_bed_at ?? $patient->currentBedAssignment->assigned_at)->format('d M Y H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-3">
                        <div class="avatar-md mx-auto mb-2">
                            <span class="avatar-title bg-light text-muted rounded-circle fs-3">
                                <i class="ri-bed-line"></i>
                            </span>
                        </div>
                        <p class="text-muted mb-0">Pasien belum di-bed.<br>Pilih bed di bawah untuk menempatkan.</p>
                    </div>
                @endif

                @if(!$patient->currentBedAssignment)
                    <hr>
                    <form action="{{ route('user.uks.treatment-status.assign-bed', ['uuid' => $patient->id]) }}"
                          method="POST">
                        @csrf
                        <label class="form-label small">Pilih Bed Tersedia</label>
                        <select name="bed_id" class="form-select form-select-sm" required>
                            <option value="">— Pilih Bed —</option>
                            @foreach($availableBeds as $building => $beds)
                                <optgroup label="{{ $building }}">
                                    @foreach($beds as $bed)
                                        @php $isOccupied = $bed->currentAssignment !== null; @endphp
                                        <option value="{{ $bed->id }}" {{ $isOccupied ? 'disabled' : '' }}>
                                            {{ $bed->bed_number }}
                                            @if($bed->room) — {{ $bed->room }} @endif
                                            {{ $isOccupied ? '(terisi)' : '(tersedia)' }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary mt-2 w-100">
                            <i class="ri-check-line me-1"></i>Tempatkan Pasien
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tabs: Catatan / Obat / Histori --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-notes" role="tab">
                            <i class="ri-file-list-3-line me-1"></i>Catatan Perkembangan
                            <span class="badge bg-primary-subtle text-primary ms-1">{{ $treatmentNotes->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-meds" role="tab">
                            <i class="ri-capsule-line me-1"></i>Pemberian Obat
                            <span class="badge bg-success-subtle text-success ms-1">{{ $medicationAdmins->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-history" role="tab">
                            <i class="ri-history-line me-1"></i>Histori Status
                            <span class="badge bg-info-subtle text-info ms-1">{{ $statusHistories->count() }}</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">

                    {{-- Tab: Catatan Perkembangan --}}
                    <div class="tab-pane active" id="tab-notes" role="tabpanel">
                        @if($isInCare)
                            <form action="{{ route('user.uks.treatment-status.store-note', ['uuid' => $patient->id]) }}"
                                  method="POST" class="mb-3">
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">Waktu</label>
                                        <input type="datetime-local" name="recorded_at" class="form-control form-control-sm"
                                               value="{{ now()->format('Y-m-d\TH:i') }}">
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label small mb-1">Catatan</label>
                                        <input type="text" name="note" maxlength="5000" class="form-control form-control-sm"
                                               placeholder="cth: Suhu tubuh 37.8°C, diberikan parasetamol 500mg..." required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="ri-add-line me-1"></i>Tambah
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        @if($treatmentNotes->count() > 0)
                            <div class="timeline timeline-with-check">
                                @foreach($treatmentNotes as $note)
                                <div class="timeline-item">
                                    <div class="timeline-dot bg-primary"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="badge badge-soft-primary">
                                                <i class="ri-time-line me-1"></i>{{ $note->recorded_at->format('d M Y H:i') }}
                                            </span>
                                            <small class="text-muted">
                                                <i class="ri-user-line me-1"></i>{{ $note->recordedBy?->name ?? '-' }}
                                            </small>
                                        </div>
                                        <p class="mb-0">{!! nl2br(e($note->note)) !!}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <div class="avatar-md mx-auto mb-2">
                                    <span class="avatar-title bg-light text-muted rounded-circle fs-3">
                                        <i class="ri-file-list-3-line"></i>
                                    </span>
                                </div>
                                <p class="text-muted mb-0">Belum ada catatan perkembangan.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Tab: Pemberian Obat --}}
                    <div class="tab-pane" id="tab-meds" role="tabpanel">
                        <form action="{{ route('user.uks.medication-administrations.store', ['uuid' => $patient->id]) }}"
                              method="POST" class="mb-3">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Nama Obat</label>
                                    <input type="text" name="medicine_name" maxlength="255" required
                                           class="form-control form-control-sm"
                                           placeholder="cth: Paracetamol">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Dosis</label>
                                    <input type="text" name="dosage" maxlength="100"
                                           class="form-control form-control-sm"
                                           placeholder="cth: 500 mg">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small mb-1">Jumlah</label>
                                    <input type="number" name="quantity" min="1" max="999" required
                                           class="form-control form-control-sm" value="1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Jam</label>
                                    <input type="datetime-local" name="given_at" class="form-control form-control-sm"
                                           value="{{ now()->format('Y-m-d\TH:i') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Catatan</label>
                                    <input type="text" name="notes" maxlength="2000" class="form-control form-control-sm"
                                           placeholder="cth: Setelah makan">
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="ri-add-line"></i>
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if($medicationAdmins->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-muted" style="width: 140px;">Jam</th>
                                            <th class="text-muted">Obat</th>
                                            <th class="text-muted">Dosis</th>
                                            <th class="text-muted text-center" style="width: 80px;">Jumlah</th>
                                            <th class="text-muted">Catatan</th>
                                            <th class="text-muted" style="width: 160px;">Petugas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($medicationAdmins as $admin)
                                        <tr>
                                            <td>
                                                <i class="ri-time-line me-1 text-muted"></i>
                                                {{ $admin->given_at->format('d M Y H:i') }}
                                            </td>
                                            <td><span class="fw-medium">{{ $admin->medicine_name }}</span></td>
                                            <td>{{ $admin->dosage ?? '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-soft-success">{{ $admin->quantity }}</span>
                                            </td>
                                            <td class="small text-muted">{{ $admin->notes ?? '-' }}</td>
                                            <td class="small text-muted">
                                                <i class="ri-user-line me-1"></i>{{ $admin->administeredBy?->name ?? '-' }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <div class="avatar-md mx-auto mb-2">
                                    <span class="avatar-title bg-light text-muted rounded-circle fs-3">
                                        <i class="ri-capsule-line"></i>
                                    </span>
                                </div>
                                <p class="text-muted mb-0">Belum ada pemberian obat.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Tab: Histori Status --}}
                    <div class="tab-pane" id="tab-history" role="tabpanel">
                        @if($statusHistories->count() > 0)
                            <div class="timeline timeline-with-check">
                                @foreach($statusHistories as $history)
                                <div class="timeline-item">
                                    <div class="timeline-dot bg-info"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <small class="text-muted">
                                                <i class="ri-time-line me-1"></i>{{ $history->changed_at->format('d M Y H:i') }}
                                            </small>
                                            <small class="text-muted">
                                                <i class="ri-user-line me-1"></i>{{ $history->changedBy?->name ?? '-' }}
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            <span class="badge badge-soft-secondary">
                                                {{ $statusLabels[$history->from_status] ?? $history->from_status }}
                                            </span>
                                            <i class="ri-arrow-right-line text-muted"></i>
                                            <span class="badge badge-soft-primary">
                                                {{ $statusLabels[$history->to_status] ?? $history->to_status }}
                                            </span>
                                        </div>
                                        @if($history->reason)
                                            <p class="mb-0 mt-2 text-muted small">
                                                <i class="ri-chat-quote-line me-1"></i>{{ $history->reason }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <div class="avatar-md mx-auto mb-2">
                                    <span class="avatar-title bg-light text-muted rounded-circle fs-3">
                                        <i class="ri-history-line"></i>
                                    </span>
                                </div>
                                <p class="text-muted mb-0">Belum ada perubahan status.</p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
