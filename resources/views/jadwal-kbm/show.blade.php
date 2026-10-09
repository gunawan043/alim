@extends('layouts.master')

@section('title', 'Jadwal KBM — ' . ($studyGroup->full_name ?? $studyGroup->name))

@push('css')
@include('kurikulum._styles')
<style>
    .day-card .table { margin-bottom: 0; }
    .day-card .table td { vertical-align: middle; }
    .time-cell { font-family: 'SF Mono', Monaco, monospace; font-size: .78rem; white-space: nowrap; }
</style>
@endpush

@section('content')
@php $userId = $userId ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">Jadwal KBM</a> @endslot
    @slot('li_2') {{ $studyGroup->full_name ?? $studyGroup->name }} @endslot
    @slot('title') Jadwal — {{ $studyGroup->full_name ?? $studyGroup->name }} @endslot
@endcomponent

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <p class="text-muted small mb-0">
        Wali Kelas: {{ $studyGroup->homeroomTeacher?->name ?? '-' }}
        @if($activeAy) • {{ $activeAy->name }} ({{ ucfirst($activeAy->semester ?? '-') }}) @endif
    </p>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i>Kembali
        </a>
        <a href="{{ route('user.jadwal-kbm.edit', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}" class="btn btn-warning btn-sm">
            <i class="ri-edit-line me-1"></i>Edit Manual
        </a>
        <a href="{{ route('user.jadwal-kbm.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}" class="btn btn-secondary btn-sm" target="_blank">
            <i class="ri-printer-line me-1"></i>Cetak
        </a>
        @if($activeAy)
            <form method="POST"
                  action="{{ route('user.jadwal-kbm.generate.execute', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                  class="d-inline js-regenerate">
                @csrf
                <input type="hidden" name="overwrite" value="1">
                <input type="hidden" name="semester" value="{{ $activeAy->semester ?? 'ganjil' }}">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ri-refresh-line me-1"></i>Generate Ulang
                </button>
            </form>
        @endif
    </div>
</div>

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

@if($jadwals->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="text-muted mb-2"><i class="ri-calendar-close-line" style="font-size:2.5rem;opacity:.4"></i></div>
            <h6 class="fw-semibold">Belum ada jadwal untuk rombel ini</h6>
            <p class="text-muted small mb-3">Generate jadwal berdasarkan SK guru yang sudah disusun.</p>
            <a href="{{ route('user.jadwal-kbm.generate', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
                <i class="ri-magic-line me-1"></i>Generate Jadwal
            </a>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach($days as $dayNumber => $dayName)
            @php $dayJadwals = $jadwals[$dayNumber] ?? collect(); @endphp
            @if($dayJadwals->isNotEmpty())
                <div class="col-xl-6">
                    <div class="card day-card h-100">
                        <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                            <h6 class="card-title mb-0">{{ $dayName }}</h6>
                            <span class="badge bg-primary-subtle text-primary">{{ $dayJadwals->count() }} jam</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:42px" class="text-center">Jam</th>
                                        <th>Waktu</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Guru</th>
                                        <th>Ruang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dayJadwals as $jadwal)
                                        <tr>
                                            <td class="text-center">{{ $jadwal->slot_index }}</td>
                                            <td class="time-cell">{{ substr($jadwal->start_time, 0, 5) }}–{{ substr($jadwal->end_time, 0, 5) }}</td>
                                            <td class="fw-medium">{{ $jadwal->subject?->name ?? '-' }}</td>
                                            <td>{{ $jadwal->teacher?->name ?? '-' }}</td>
                                            <td>{{ $jadwal->room ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-regenerate').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        if (! confirm('Generate ulang jadwal rombel ini? Jadwal lama akan ditimpa.')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
