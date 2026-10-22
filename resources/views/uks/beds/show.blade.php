@extends('layouts.master')

@section('title', 'Detail Bed UKS')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Rawat Inap @endslot
        @slot('li_3') Bed & Ruang Rawat @endslot
        @slot('title') Detail Bed @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $assignment = $bed->currentAssignment;
        $patient = $assignment?->patient;
        $student = $patient?->student;
        $occupied = $assignment !== null;
        $statusColor = match ($bed->status) {
            'tersedia' => 'success',
            'dipakai' => 'danger',
            'perbaikan' => 'warning',
            default => 'secondary',
        };
    @endphp

    <div class="row g-3">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-hotel-bed-line me-2 text-primary"></i>Informasi Bed</h5>
                    <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ ucfirst($bed->status) }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 fs-13">
                        <dt class="col-5 text-muted fw-normal">Nomor Bed</dt>
                        <dd class="col-7 fw-bold">{{ $bed->bed_number }}</dd>
                        <dt class="col-5 text-muted fw-normal">Gedung / Ruang</dt>
                        <dd class="col-7">{{ $bed->building ?: $bed->building_or_room }}</dd>
                        <dt class="col-5 text-muted fw-normal">Ruangan</dt>
                        <dd class="col-7">{{ $bed->room ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Section</dt>
                        <dd class="col-7">{{ $bed->section ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Gender</dt>
                        <dd class="col-7">{{ $bed->gender === 'L' ? 'Putra' : 'Putri' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Asrama / Unit</dt>
                        <dd class="col-7">{{ $bed->dormitory?->name ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Catatan</dt>
                        <dd class="col-7">{{ $bed->notes ?? '-' }}</dd>
                    </dl>
                </div>
                <div class="card-footer d-flex gap-2 justify-content-end">
                    <a href="{{ route('user.uks.beds.index', ['userId' => auth()->id()]) }}" class="btn btn-light btn-sm">Kembali</a>
                    <a href="{{ route('user.uks.beds.edit', ['userId' => auth()->id(), 'uuid' => $bed->id]) }}" class="btn btn-primary btn-sm"><i class="ri-edit-line me-1"></i>Edit</a>
                    @if(! $occupied)
                        <form method="POST" action="{{ route('user.uks.beds.destroy', ['userId' => auth()->id(), 'uuid' => $bed->id]) }}" onsubmit="return confirm('Hapus bed ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-line me-1"></i>Hapus</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-user-heart-line me-2 text-danger"></i>Pasien Saat Ini</h5>
                    <a href="{{ route('user.uks.care-locations.index', ['userId' => auth()->id()]) }}" class="btn btn-sm btn-soft-info">Papan Lokasi</a>
                </div>
                <div class="card-body">
                    @if($occupied)
                        <div class="d-flex align-items-start gap-3 flex-wrap">
                            <div class="avatar-md">
                                <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-4 fw-bold">
                                    {{ strtoupper(substr($student?->name ?? '?', 0, 1)) }}
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">{{ $student?->name ?? '-' }}</h5>
                                <div class="text-muted fs-12">
                                    {{ $patient?->medical_record_no ?? '-' }} ·
                                    {{ $student?->gender === 'P' ? 'Putri' : 'Putra' }}
                                    · Masuk {{ $assignment->assigned_at?->format('d M Y H:i') }}
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge bg-{{ $patient?->care_location_color }}-subtle text-{{ $patient?->care_location_color }}">
                                        <i class="{{ $patient?->care_location_icon }} me-1"></i>{{ $patient?->care_location_label }}
                                    </span>
                                    <span class="badge bg-light text-muted">{{ \App\Models\Uks\UksPatient::$statusLabels[$patient?->status] ?? $patient?->status }}</span>
                                </div>
                                <div class="mt-2 fs-13">
                                    <span class="text-muted">Diagnosis:</span> {{ $patient?->diagnosis ?? '-' }}<br>
                                    <span class="text-muted">Keluhan:</span> {{ $patient?->chief_complaint ?? '-' }}
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('user.uks.clinic.workspace', ['userId' => auth()->id(), 'uuid' => $patient->id]) }}" class="btn btn-primary btn-sm">
                                <i class="ri-stethoscope-line me-1"></i>Lembar Pemeriksaan
                            </a>
                            <a href="{{ route('user.uks.patients.show', ['userId' => auth()->id(), 'uuid' => $patient->id]) }}" class="btn btn-soft-secondary btn-sm">
                                <i class="ri-file-text-line me-1"></i>Detail Pasien
                            </a>
                        </div>

                        <hr>

                        <h6 class="fs-13 text-muted text-uppercase">Kosongkan Bed & Pindahkan Lokasi</h6>
                        <form method="POST" action="{{ route('user.uks.patients.release-bed', ['userId' => auth()->id(), 'uuid' => $patient->id]) }}">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label fs-12 mb-1">Lokasi Tujuan</label>
                                    <select name="care_location" class="form-select form-select-sm" required>
                                        @foreach(\App\Models\Uks\UksPatient::$careLocationLabels as $key => $label)
                                            <option value="{{ $key }}" {{ $key === \App\Models\Uks\UksPatient::LOCATION_ASRAMA ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fs-12 mb-1">Catatan</label>
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="cth: Kembali ke asrama">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-warning btn-sm w-100">Kosongkan</button>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="text-center py-4">
                            <span class="avatar-lg bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2">
                                <i class="ri-hotel-bed-line fs-2 text-muted"></i>
                            </span>
                            <h6 class="text-muted mb-1">Bed kosong</h6>
                            <p class="text-muted mb-0">Tempatkan pasien dari lembar pemeriksaan atau status perawatan.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-history-line me-2 text-info"></i>Riwayat Pemakaian Bed</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light fs-12 text-muted">
                                <tr>
                                    <th>Pasien</th>
                                    <th>No. RM</th>
                                    <th>Masuk</th>
                                    <th>Keluar</th>
                                    <th>Status</th>
                                    <th>Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignmentHistory as $history)
                                    <tr>
                                        <td class="fw-medium">{{ $history->patient?->student?->name ?? '-' }}</td>
                                        <td class="fs-12 text-muted">{{ $history->patient?->medical_record_no ?? '-' }}</td>
                                        <td class="fs-12">{{ $history->assigned_at?->format('d M Y H:i') ?? '-' }}</td>
                                        <td class="fs-12">{{ $history->released_at?->format('d M Y H:i') ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $history->status === 'assigned' ? 'danger' : 'success' }}-subtle text-{{ $history->status === 'assigned' ? 'danger' : 'success' }}">
                                                {{ $history->status === 'assigned' ? 'Masih di bed' : 'Sudah keluar' }}
                                            </span>
                                        </td>
                                        <td class="fs-12 text-muted">{{ $history->reason ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat pemakaian.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
