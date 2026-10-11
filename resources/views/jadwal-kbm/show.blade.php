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

@php
    $slotNumbers = collect();
    foreach ($masterSlots as $daySlots) { $slotNumbers = $slotNumbers->merge($daySlots->pluck('slot_number')); }
    foreach ($jadwals as $dayRows) { $slotNumbers = $slotNumbers->merge($dayRows->pluck('slot_index')); }
    $slotNumbers = $slotNumbers->unique()->sort()->values();

    $slotMeta = [];
    foreach ($slotNumbers as $no) {
        foreach ($days as $dayNumber => $dayName) {
            $ms = ($masterSlots[$dayNumber] ?? collect())->firstWhere('slot_number', $no);
            if ($ms) {
                $slotMeta[$no] = [
                    'start' => substr((string) $ms->time_start, 0, 5),
                    'end' => substr((string) $ms->time_end, 0, 5),
                    'is_break' => (bool) $ms->is_break,
                ];
                break;
            }
        }
    }
@endphp

@if($slotNumbers->isNotEmpty())
    @if($jadwals->isEmpty())
        <div class="alert alert-info small">
            <i class="ri-information-line me-1"></i>
            Belum ada mapel terjadwal untuk TA ini — klik <strong>Generate Jadwal Sekolah</strong>.
            Baris istirahat/kegiatan di bawah mengikuti master Jam Pelajaran sekolah.
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ri-calendar-2-line me-1"></i>Tabel Jadwal — {{ $studyGroup->full_name ?? $studyGroup->name }}</h5>
            <span class="text-muted small">Kolom = hari · Baris = jam pelajaran</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0" style="min-width:900px">
                <thead class="table-light text-center align-middle">
                    <tr>
                        <th style="width:80px">Jam Ke-</th>
                        <th style="width:110px">Waktu</th>
                        @foreach($days as $dayNumber => $dayName)
                            <th style="min-width:150px">{{ $dayName }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($slotNumbers as $no)
                        @php
                            $meta = $slotMeta[$no] ?? null;
                            $isBreakRow = $meta['is_break'] ?? false;
                        @endphp
                        <tr style="{{ $isBreakRow ? 'background:#fff8e1;' : '' }}">
                            <td class="text-center fw-semibold">{{ $isBreakRow ? '-' : $no }}</td>
                            <td class="text-center text-nowrap" style="font-size:12px">
                                {{ $meta ? $meta['start'].' – '.$meta['end'] : '—' }}
                            </td>
                            @foreach($days as $dayNumber => $dayName)
                                @php
                                    $ms = ($masterSlots[$dayNumber] ?? collect())->firstWhere('slot_number', $no);
                                    $cell = ($jadwals[$dayNumber] ?? collect())->firstWhere('slot_index', $no);
                                @endphp
                                <td class="text-center">
                                    @if($ms && $ms->is_break)
                                        <span class="fst-italic text-muted" style="font-size:12px">{{ $ms->label ?: 'Istirahat' }}</span>
                                    @elseif($cell)
                                        <div class="fw-medium" style="font-size:12px">{{ $cell->subject?->name ?? '-' }}</div>
                                        <div class="text-muted" style="font-size:11px">{{ $cell->teacher?->name ?? '' }}</div>
                                    @elseif($ms)
                                        <span class="text-light">–</span>
                                    @else
                                        <span class="text-light">·</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="text-muted mb-2"><i class="ri-calendar-close-line" style="font-size:2.5rem;opacity:.4"></i></div>
            <h6 class="fw-semibold">Belum ada jadwal untuk rombel ini</h6>
            <p class="text-muted small mb-3">Generate jadwal berdasarkan penugasan mengajar yang sudah disusun.</p>
            <a href="{{ route('user.jadwal-kbm.generate', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
                <i class="ri-magic-line me-1"></i>Generate Jadwal
            </a>
        </div>
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
