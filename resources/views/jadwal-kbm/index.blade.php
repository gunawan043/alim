@extends('layouts.master')

@section('title', 'Jadwal KBM')

@push('css')
@include('kurikulum._styles')
<style>
    .sg-badge { font-size: .72rem; }
    .table-freeze th, .table-freeze td { vertical-align: middle; }
    .schedule-matrix-table { font-size: .78rem; }
    .schedule-matrix-table th, .schedule-matrix-table td { padding: .35rem .4rem; vertical-align: middle; }
    .schedule-matrix-table td.small { font-size: .74rem; }
    .subject-code { display: block; font-weight: 600; line-height: 1.15; }
    .teacher-code { display: block; color: #64748b; font-size: .68rem; line-height: 1.2; }
    .matrix-layout { display: flex; gap: 1rem; align-items: flex-start; }
    .matrix-schedule { flex: 1 1 74%; min-width: 0; }
    .matrix-teachers { flex: 0 0 25%; min-width: 275px; }
    .matrix-teachers .table-responsive {
        max-height: calc(100vh - 240px);
        overflow-y: auto;
        scrollbar-width: thin;
    }
    .matrix-teachers thead th {
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .teacher-load { margin: 0; }
    .teacher-load summary {
        display: flex;
        align-items: center;
        gap: .5rem;
        cursor: pointer;
        list-style: none;
        line-height: 1.25;
    }
    .teacher-load summary::-webkit-details-marker { display: none; }
    .teacher-load summary::after {
        content: '+';
        margin-left: auto;
        color: #64748b;
        font-weight: 700;
        flex: 0 0 auto;
    }
    .teacher-load[open] summary::after { content: '−'; }
    .teacher-load-summary { color: #64748b; font-size: .68rem; white-space: nowrap; }
    .teacher-load-detail { margin-top: .25rem; }
    .teacher-subject { display: flex; justify-content: space-between; gap: .5rem; border-top: 1px solid #e5e7eb; margin-top: .25rem; padding-top: .25rem; }
    .teacher-hours { white-space: nowrap; font-weight: 600; }
    .teacher-total { text-align: right; color: #64748b; margin-top: .25rem; }
    @media (max-width: 1199.98px) {
        .matrix-layout { flex-direction: column; }
        .matrix-schedule, .matrix-teachers { width: 100%; min-width: 0; }
    }
</style>
@endpush

@section('content')
@php $userId = $userId ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Kurikulum @endslot
    @slot('title') Jadwal Kegiatan Belajar @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@include('jadwal-kbm._report')

{{-- STATISTIK --}}
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-group-line text-primary"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Rombel Aktif</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['rombel']) }}</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Sesuai filter aktif</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-calendar-check-line text-success"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Sudah Terjadwal</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['terjadwal']) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">/ {{ $stats['rombel'] }}</small></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label">
                    @if($stats['rombel'] - $stats['terjadwal'] > 0)
                        <span class="badge bg-warning-subtle text-warning stat-label">{{ $stats['rombel'] - $stats['terjadwal'] }} belum terjadwal</span>
                    @else
                        <span class="badge bg-success-subtle text-success stat-label">Semua terjadwal</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-calendar-schedule-line text-info"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Slot Jadwal</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['slot']) }}</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Tahun ajaran aktif</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-user-star-line text-warning"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Guru Terlibat</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['guru']) }}</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-team-line me-1"></i>Guru pengajar di jadwal</p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3" id="jadwalMatrix">
    <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title mb-0"><i class="ri-table-line text-primary me-1"></i>Matriks Jadwal Pelajaran</h5>
            <p class="text-muted mb-0 small">Seluruh rombel dalam satu tampilan per hari dan jam.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary">{{ $activeAy?->name ?? 'Tahun ajaran belum aktif' }}</span>
    </div>
    <div class="card-body">
        @if($studyGroups->isEmpty())
            <div class="text-muted text-center py-4">Belum ada rombel aktif.</div>
        @else
            @include('jadwal-kbm._matrix', ['studyGroups' => $studyGroups, 'matrixData' => $matrixData])
        @endif
    </div>
</div>

<div class="card" id="jadwalKbmList">
    <div class="card-header border-bottom-dashed">
        <div class="row g-3 align-items-center">
            <div class="col-lg">
                <h5 class="card-title mb-0"><i class="ri-calendar-2-line text-primary me-1"></i>Daftar Jadwal per Rombel</h5>
                <p class="text-muted mb-0">
                    @if($activeAy)
                        <span class="badge bg-primary-subtle text-primary">Tahun Ajaran: {{ $activeAy->name }} ({{ ucfirst($activeAy->semester ?? '-') }})</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning">Tahun ajaran aktif belum ditetapkan</span>
                    @endif
                    <span class="text-muted small ms-2">Generator menyusun jadwal <strong>seluruh sekolah</strong> dari penugasan mengajar (SK guru) &amp; master Jam Pelajaran.</span>
                </p>
            </div>
            <div class="col-lg-auto">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('user.jam-pelajaran.index', ['userId' => $userId]) }}" class="btn btn-soft-primary">
                        <i class="ri-timer-line me-1"></i> Jam Pelajaran
                    </a>
                    <a href="{{ route('user.jadwal-kbm.availability', ['userId' => $userId]) }}" class="btn btn-soft-warning">
                        <i class="ri-user-settings-line me-1"></i> Ketersediaan Guru
                    </a>
                    <a href="{{ route('user.jadwal-kbm.cetak-all', ['userId' => $userId]) }}" class="btn btn-soft-secondary" target="_blank" rel="noopener">
                        <i class="ri-printer-line me-1"></i> Cetak Semua Rombel
                    </a>
                    <a href="{{ route('user.jadwal-kbm.generate', ['userId' => $userId]) }}" class="btn btn-primary">
                        <i class="ri-magic-line me-1"></i> Generate Jadwal Sekolah
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card-header py-2 bg-light border-bottom">
        <div class="d-flex flex-wrap align-items-center">
            <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
            <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="filter-badge {{ ! in_array($status, ['terjadwal', 'belum'], true) ? 'active' : '' }}">Semua</a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'terjadwal']) }}" class="filter-badge {{ $status === 'terjadwal' ? 'active' : '' }}"><i class="ri-checkbox-circle-line"></i>Sudah Terjadwal</a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'belum']) }}" class="filter-badge {{ $status === 'belum' ? 'active' : '' }}"><i class="ri-close-circle-line"></i>Belum Terjadwal</a>
            <span class="text-muted small ms-2 me-2">·</span>
            <span class="text-muted small">{{ $stats['slot'] }} slot · {{ $stats['guru'] }} guru</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:48px" class="text-center">No</th>
                    <th>Rombel</th>
                    <th>Tingkat</th>
                    <th>Wali Kelas</th>
                    <th class="text-center">Slot Jadwal</th>
                    <th class="text-end" style="width:230px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($studyGroups as $sg)
                    @php
                        $group = $jadwals[$sg->id] ?? collect();
                        $count = $group->count();
                    @endphp
                    <tr>
                        <td class="text-center text-muted">{{ $loop->iteration }}</td>
                        <td class="fw-medium">{{ $sg->full_name ?? $sg->name }}</td>
                        <td>{{ $sg->gradeLevel->name ?? '-' }}</td>
                        <td>
                            @if($sg->homeroomTeacher)
                                <span class="text-muted small"><i class="ri-user-star-line me-1"></i>{{ $sg->homeroomTeacher->name }}</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning" style="font-size:10px;">Belum ada</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($count > 0)
                                <span class="badge bg-success sg-badge">{{ $count }} slot</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary sg-badge">Belum ada jadwal</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($count > 0)
                                <a href="{{ route('user.jadwal-kbm.show', ['userId' => $userId, 'studyGroupId' => $sg->id]) }}"
                                   class="btn btn-sm btn-outline-primary" title="Lihat jadwal">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="{{ route('user.jadwal-kbm.edit', ['userId' => $userId, 'studyGroupId' => $sg->id]) }}"
                                   class="btn btn-sm btn-outline-warning" title="Edit manual">
                                    <i class="ri-edit-line"></i>
                                </a>
                                <a href="{{ route('user.jadwal-kbm.cetak', ['userId' => $userId, 'studyGroupId' => $sg->id]) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Cetak" target="_blank">
                                    <i class="ri-printer-line"></i>
                                </a>
                            @else
                                <a href="{{ route('user.jadwal-kbm.edit', ['userId' => $userId, 'studyGroupId' => $sg->id]) }}"
                                   class="btn btn-sm btn-outline-warning" title="Tambah jadwal manual">
                                    <i class="ri-edit-line"></i>
                                </a>
                                <form method="POST"
                                      action="{{ route('user.jadwal-kbm.generate.execute', ['userId' => $userId, 'studyGroupId' => $sg->id]) }}"
                                      class="d-inline js-generate-single">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary"
                                            data-name="{{ $sg->full_name ?? $sg->name }}">
                                        <i class="ri-magic-line me-1"></i>Generate
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="ri-calendar-close-line" style="font-size:2.5rem;opacity:.4"></i></div>
                            <h6 class="fw-semibold">Belum ada rombel pada filter ini</h6>
                            <p class="text-muted small mb-0">Tambahkan rombongan belajar atau ubah filter terlebih dahulu.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-generate-single').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        var name = form.querySelector('button[data-name]')?.dataset.name || 'rombel ini';
        if (! confirm('Generate ulang jadwal untuk ' + name + ' berdasarkan penugasan mengajar?')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
