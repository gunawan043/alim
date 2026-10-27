@extends('layouts.master')

@section('title', 'Jadwal Mengajar — ' . ($teacher->name ?? ''))

@push('css')
@include('kurikulum._styles')
<style>
    .time-cell { font-family: 'SF Mono', Monaco, monospace; font-size: .78rem; white-space: nowrap; }
</style>
@endpush

@section('content')
@php $userId = $userId ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Jadwal Mengajar @endslot
    @slot('title') Jadwal Mengajar — {{ $teacher->name ?? '-' }} @endslot
@endcomponent

@if($activeAy)
    <p class="text-muted small mb-3">Tahun Ajaran {{ $activeAy->name }} ({{ ucfirst($activeAy->semester ?? '-') }})</p>
@endif

<div class="card">
    <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-user-star-line text-primary me-1"></i>Jadwal Mengajar — {{ $teacher->name ?? '-' }}</h5>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
                <i class="ri-history-line me-1"></i>Riwayat Scan
            </a>
            <a href="{{ route('user.teacher-qr.scan', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
                <i class="ri-qr-scan-2-line me-1"></i>Scan Kehadiran
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        @if($jadwals->isEmpty())
            <div class="text-center py-5">
                <div class="text-muted mb-2"><i class="ri-calendar-close-line" style="font-size:2.5rem;opacity:.4"></i></div>
                <h6 class="fw-semibold mb-1">Belum ada jadwal mengajar</h6>
                <p class="text-muted small mb-0">Jadwal akan tampil setelah admin men-generate jadwal KBM.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:96px">Hari</th>
                            <th style="width:80px" class="text-center">Jam</th>
                            <th style="width:120px">Waktu</th>
                            <th>Mata Pelajaran</th>
                            <th>Rombel</th>
                            <th style="width:100px">Ruang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($days as $dayNumber => $dayName)
                            @php $dayJadwals = $jadwals[$dayNumber] ?? collect(); @endphp
                            @foreach($dayJadwals as $jadwal)
                                <tr>
                                    @if($loop->first)
                                        <td class="fw-semibold" rowspan="{{ $dayJadwals->count() }}">{{ $dayName }}</td>
                                    @endif
                                    <td class="text-center">{{ $jadwal->slot_index }}</td>
                                    <td class="time-cell">{{ substr($jadwal->start_time, 0, 5) }}–{{ substr($jadwal->end_time, 0, 5) }}</td>
                                    <td class="fw-medium">{{ $jadwal->subject?->name ?? '-' }}</td>
                                    <td>{{ $jadwal->studyGroup?->full_name ?? $jadwal->studyGroup?->name ?? '-' }}</td>
                                    <td>{{ $jadwal->room ?? '-' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card mt-3">
    <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-book-read-line text-primary me-1"></i>Jadwal Halaqah Tahfidz</h5></div>
    @if(($tahfidzSchedules ?? collect())->isEmpty())
        <div class="card-body text-muted small">Tidak ada jadwal halaqah untuk guru ini.</div>
    @else
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Hari</th><th>Waktu</th><th>Halaqah</th><th>Kelas</th><th>Jenis</th><th>Ruang</th></tr></thead><tbody>
            @foreach($tahfidzSchedules as $schedules)
                @foreach($schedules as $schedule)
                    <tr><td class="fw-semibold">{{ $schedule->day_name }}</td><td>{{ substr((string)$schedule->time_start,0,5) }}–{{ substr((string)$schedule->time_end,0,5) }}</td><td>{{ $schedule->group?->name }}</td><td>{{ $schedule->group?->studyGroup?->name ?? '—' }}</td><td><span class="badge bg-primary-subtle text-primary">{{ $schedule->type_label }}</span></td><td>{{ $schedule->room ?? '—' }}</td></tr>
                @endforeach
            @endforeach
        </tbody></table></div>
    @endif
</div>
@endsection
