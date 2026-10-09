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
    <div class="card-header border-bottom-dashed">
        <h5 class="card-title mb-0"><i class="ri-user-star-line text-primary me-1"></i>Jadwal Mengajar — {{ $teacher->name ?? '-' }}</h5>
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
@endsection
