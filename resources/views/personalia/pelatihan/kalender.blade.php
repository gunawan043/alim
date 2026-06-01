@extends('layouts.master')
@section('title') Kalender Pelatihan @endsection
@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#f0fdfa 0%,#f5fffe 100%);border:1px solid #99f6e4;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#0c1f1c 0%,#102420 100%);border-color:#0f4a44}
@media print{.no-print{display:none!important}}
</style>
@endpush

@section('content')
@php
$userId = request()->route('userId') ?? auth()->id();
$currentUser = auth()->user();
$isAdmin = $currentUser->hasAnyRole(['Personalia','Super Admin','Admin Tata Usaha']);
@endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#14b8a618;color:#0d9488;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-calendar-todo-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Kalender Pelatihan</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Jadwal dan timeline program pelatihan GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.ats.pelatihan.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Daftar</a>
        @if($isAdmin)
        <a href="{{ route('user.ats.pelatihan.create', $userId) }}" class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i>Pelatihan Baru</a>
        @endif
    </div>
</div>

<div class="card no-print">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-calendar-check-line text-primary me-1"></i> Jadwal Bulan Ini</h5>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-light btn-sm" onclick="changeMonth(-1)"><i class="ri-arrow-left-s-line"></i></button>
            <span class="fw-semibold px-2">{{ $currentMonthName ?? 'Mei 2026' }}</span>
            <button class="btn btn-light btn-sm" onclick="changeMonth(1)"><i class="ri-arrow-right-s-line"></i></button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered text-center mb-0" style="table-layout:fixed">
                <thead>
                    <tr class="bg-light">
                        <th class="text-muted" style="width:14%">Min</th>
                        <th style="width:14%">Sen</th>
                        <th style="width:14%">Sel</th>
                        <th style="width:14%">Rab</th>
                        <th style="width:14%">Kam</th>
                        <th style="width:14%">Jum</th>
                        <th class="text-muted" style="width:14%">Sab</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($calendarWeeks ?? [] as $week)
                    <tr>
                        @foreach($week as $day)
                        <td class="p-2 {{ !$day['current'] ? 'text-muted' : '' }}" style="font-size:.8rem;min-width:70px">
                            <span class="d-block fw-semibold mb-1">{{ $day['day'] }}</span>
                            @forelse($day['events'] ?? [] as $event)
                            <div class="badge {{ $event['color'] }} mb-1 d-block text-start" style="font-size:.65rem;line-height:1.2">
                                <i class="ri-circle-fill me-1" style="font-size:.5rem;vertical-align:middle"></i>{{ Str::limit($event['title'], 15) }}
                            </div>
                            @endforelse
                        </td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-5 text-center text-muted">
                            <i class="ri-calendar-line" style="font-size:2rem"></i>
                            <p class="mb-0 mt-2">Tidak ada jadwal pelatihan bulan ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="row g-3 p-3 border-top">
            <div class="col-auto">
                <span class="badge bg-warning-subtle text-warning"><i class="ri-time-line me-1"></i> Terjadwal</span>
            </div>
            <div class="col-auto">
                <span class="badge bg-success-subtle text-success"><i class="ri-play-line me-1"></i> Berlangsung</span>
            </div>
            <div class="col-auto">
                <span class="badge bg-primary-subtle text-primary"><i class="ri-checkbox-circle-line me-1"></i> Selesai</span>
            </div>
            <div class="col-auto">
                <span class="badge bg-danger-subtle text-danger"><i class="ri-close-circle-line me-1"></i> Dibatalkan</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed">
        <h5 class="card-title mb-0"><i class="ri-list-check-2 text-primary me-1"></i> Daftar Pelatihan Bulan Ini</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Nama Pelatihan</th>
                        <th>Tanggal</th>
                        <th>Lokasi</th>
                        <th>Peserta</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bulanIni ?? [] as $i => $p)
                    <tr>
                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                        <td><span class="fw-semibold">{{ $p->nama }}</span></td>
                        <td>{{ $p->tanggal_mulai?->format('d M Y') }} – {{ $p->tanggal_selesai?->format('d M Y') }}</td>
                        <td><span class="text-muted small">{{ $p->lokasi ?? '-' }}</span></td>
                        <td><span class="badge bg-info-subtle text-info">{{ $p->peserta_count }} org</span></td>
                        <td>
                            @switch($p->status)
                                @case('scheduled')<span class="badge bg-warning-subtle text-warning badge-status">Terjadwal</span>@break
                                @case('ongoing')<span class="badge bg-success-subtle text-success badge-status">Berlangsung</span>@break
                                @case('completed')<span class="badge bg-primary-subtle text-primary badge-status">Selesai</span>@break
                                @case('cancelled')<span class="badge bg-danger-subtle text-danger badge-status">Dibatalkan</span>@break
                            @endswitch
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Tidak ada pelatihan bulan ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@push('scripts')
<script>
function changeMonth(direction) {
    window.location.href = '{{ route("user.pelatihan.kalender", $userId) }}?bulan=' + direction;
}
</script>
@endpush
@endsection