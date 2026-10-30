{{-- Dashboard realtime absensi guru — ringkasan, filter, grafik, dan tindak lanjut --}}
@extends('layouts.master')

@section('title', 'Dashboard Realtime Absensi Guru')

@push('css')
<style>
    .attendance-hero {
        border-radius: 1rem;
        background: linear-gradient(125deg, #334155 0%, #405189 55%, #4f6b9a 100%);
        color: #fff;
        box-shadow: 0 12px 30px rgba(51, 65, 85, .18);
    }
    .attendance-hero .hero-kicker {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border-radius: 999px;
        padding: .22rem .65rem;
        background: rgba(255,255,255,.14);
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .attendance-hero .hero-title { font-size: 1.35rem; font-weight: 700; letter-spacing: -.02em; }
    .attendance-hero .hero-meta { color: rgba(255,255,255,.78); font-size: .82rem; }
    .attendance-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border-radius: 999px;
        padding: .35rem .7rem;
        font-size: .72rem;
        font-weight: 700;
        background: rgba(255,255,255,.14);
        color: #fff;
        white-space: nowrap;
    }
    .attendance-stat {
        border: 1px solid rgba(56,65,74,.10);
        border-radius: .85rem;
        height: 100%;
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .attendance-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(56,65,74,.09); }
    .attendance-stat .stat-icon {
        width: 42px; height: 42px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.15rem;
    }
    .attendance-stat .stat-label {
        color: var(--vz-secondary-color); font-size: .68rem; font-weight: 700;
        letter-spacing: .05em; text-transform: uppercase; margin-bottom: .2rem;
    }
    .attendance-stat .stat-value { font-size: 1.55rem; line-height: 1.1; font-weight: 700; letter-spacing: -.02em; }
    .attendance-stat .stat-note { color: var(--vz-secondary-color); font-size: .72rem; margin-top: .25rem; }
    .attendance-chip {
        display: inline-flex; align-items: center; gap: .3rem;
        border-radius: 999px; padding: .25rem .55rem;
        font-size: .7rem; font-weight: 700;
    }
    .attendance-progress { height: 7px; border-radius: 999px; background: #edf0f5; overflow: hidden; }
    .attendance-progress > span { display: block; height: 100%; border-radius: inherit; }
    .attendance-timeline { position: relative; padding-left: 1.5rem; }
    .attendance-timeline::before {
        content: ''; position: absolute; left: .42rem; top: .2rem; bottom: .2rem;
        width: 2px; background: #edf0f5;
    }
    .attendance-timeline-item { position: relative; padding-bottom: 1rem; }
    .attendance-timeline-item:last-child { padding-bottom: 0; }
    .attendance-timeline-item::before {
        content: ''; position: absolute; left: -1.24rem; top: .28rem; width: .62rem; height: .62rem;
        border-radius: 50%; background: #0ab39c; border: 2px solid #fff; box-shadow: 0 0 0 2px #d8f3ee;
    }
    .attendance-timeline-item.is-late::before { background: #f7b84b; box-shadow: 0 0 0 2px #fcefd6; }
    .attendance-table-wrap { max-height: 360px; overflow: auto; }
    .attendance-table-wrap thead th {
        position: sticky; top: 0; z-index: 2; background: var(--vz-card-bg, #fff);
        font-size: .7rem; text-transform: uppercase; letter-spacing: .04em;
    }
    .attendance-filter .form-label { font-size: .72rem; font-weight: 700; color: var(--vz-secondary-color); }
    .attendance-filter .form-control, .attendance-filter .form-select { min-height: 36px; }
    .chart-box { min-height: 280px; }
    .chart-box.is-empty { display: flex; align-items: center; justify-content: center; color: var(--vz-secondary-color); font-size: .8rem; }
    @media (max-width: 767.98px) {
        .attendance-hero { padding: 1.1rem !important; }
        .attendance-hero .hero-title { font-size: 1.1rem; }
    }
</style>
@endpush

@section('content')
@php
    $userId = request()->route('userId') ?? auth()->id();
    $dateLabel = $date->copy()->locale('id')->translatedFormat('l, d F Y');
    $selectedTeacher = $teacherOptions->firstWhere('id', $teacherId);
    $selectedClass = $studyGroupOptions->firstWhere('id', $studyGroupId);
    $statusLabels = array_column($statusDistribution, 'label');
    $statusValues = array_column($statusDistribution, 'value');
    $statusColors = array_column($statusDistribution, 'color');
    $classChartData = $classStats->take(10)->values();
    $attentionCount = $notPresent->count() + $notCheckedOut->count();
@endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Guru @endslot
    @slot('li_2') Dashboard Waka @endslot
    @slot('title') Dashboard Realtime Absensi Guru @endslot
@endcomponent

{{-- Header / filter --}}
<div class="attendance-hero p-4 p-lg-5 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <span class="hero-kicker"><i class="ri-pulse-line"></i> Monitoring kehadiran mengajar</span>
            <h4 class="hero-title mt-2 mb-1">Absensi Guru Realtime</h4>
            <p class="hero-meta mb-0">
                {{ $dateLabel }}
                @if($isToday)
                    <span class="mx-1">•</span> Diperbarui {{ now()->format('H:i:s') }} WITA
                @else
                    <span class="mx-1">•</span> Data historis
                @endif
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="attendance-pill">
                <i class="ri-calendar-check-line"></i>
                {{ $expected }} jadwal
            </span>
            <span class="attendance-pill">
                <i class="ri-checkbox-circle-line"></i>
                {{ $rate }}% kehadiran
            </span>
            @if($isToday)
                <button type="button" id="autoRefreshToggle" class="btn btn-sm btn-light">
                    <i class="ri-refresh-line me-1"></i><span>Auto-refresh</span>
                </button>
            @endif
            <a href="{{ route('user.teacher-qr.history.export', ['userId' => $userId, 'start_date' => $date->toDateString(), 'end_date' => $date->toDateString()]) }}"
               class="btn btn-sm btn-light">
                <i class="ri-download-2-line me-1"></i>Unduh
            </a>
        </div>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Filters --}}
<div class="card mb-4 attendance-filter">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('user.teacher-qr.waka-dashboard', ['userId' => $userId]) }}" class="row g-2 align-items-end">
            <div class="col-xl-2 col-md-4">
                <label class="form-label mb-1">Tanggal</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}">
            </div>
            <div class="col-xl-3 col-md-4">
                <label class="form-label mb-1">Guru</label>
                <select name="teacher_id" class="form-select form-select-sm">
                    <option value="">Semua guru</option>
                    @foreach($teacherOptions as $teacher)
                        <option value="{{ $teacher->id }}" @selected((string) $teacherId === (string) $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-3 col-md-4">
                <label class="form-label mb-1">Kelas</label>
                <select name="study_group_id" class="form-select form-select-sm">
                    <option value="">Semua kelas</option>
                    @foreach($studyGroupOptions as $group)
                        <option value="{{ $group->id }}" @selected((string) $studyGroupId === (string) $group->id)>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-md-6">
                <label class="form-label mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ $search }}" placeholder="Guru / kelas / mapel">
            </div>
            <div class="col-xl-2 col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
                <a href="{{ route('user.teacher-qr.waka-dashboard', ['userId' => $userId]) }}" class="btn btn-light btn-sm" title="Reset filter"><i class="ri-refresh-line"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- Ringkasan utama --}}
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card attendance-stat">
            <div class="card-body py-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-label">Jadwal</div>
                        <div class="stat-value">{{ $expected }}</div>
                        <div class="stat-note">pelajaran hari ini</div>
                    </div>
                    <div class="stat-icon bg-primary-subtle text-primary"><i class="ri-calendar-2-line"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card attendance-stat">
            <div class="card-body py-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-label">Hadir</div>
                        <div class="stat-value text-success">{{ $checked }}</div>
                        <div class="stat-note">{{ $checkedOut }} sudah checkout</div>
                    </div>
                    <div class="stat-icon bg-success-subtle text-success"><i class="ri-user-follow-line"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card attendance-stat">
            <div class="card-body py-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-label">Tepat waktu</div>
                        <div class="stat-value text-info">{{ $onTime }}</div>
                        <div class="stat-note">sesuai jadwal</div>
                    </div>
                    <div class="stat-icon bg-info-subtle text-info"><i class="ri-checkbox-circle-line"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card attendance-stat">
            <div class="card-body py-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-label">Terlambat</div>
                        <div class="stat-value text-warning">{{ $late }}</div>
                        <div class="stat-note">perlu perhatian</div>
                    </div>
                    <div class="stat-icon bg-warning-subtle text-warning"><i class="ri-time-line"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card attendance-stat">
            <div class="card-body py-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-label">Belum absen</div>
                        <div class="stat-value text-danger">{{ $absent }}</div>
                        <div class="stat-note">jadwal belum check-in</div>
                    </div>
                    <div class="stat-icon bg-danger-subtle text-danger"><i class="ri-user-unfollow-line"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card attendance-stat">
            <div class="card-body py-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-label">Kehadiran</div>
                        <div class="stat-value text-primary">{{ $rate }}%</div>
                        <div class="stat-note">{{ $notCheckedOutCount }} belum checkout</div>
                    </div>
                    <div class="stat-icon bg-primary-subtle text-primary"><i class="ri-pie-chart-2-line"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($lowestClass)
    <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2 py-2 mb-4">
        <i class="ri-focus-3-line fs-5"></i>
        <span class="small">
            Kelas dengan kehadiran terendah: <strong>{{ $lowestClass['name'] }}</strong>
            ({{ $lowestClass['rate'] }}%, {{ $lowestClass['absent'] }} jadwal belum absen).
        </span>
        <span class="ms-auto small text-muted">Gunakan filter kelas untuk melihat detail.</span>
    </div>
@endif

{{-- Grafik --}}
<div class="row g-4 mb-4">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-donut-chart-line me-1 text-primary"></i>Distribusi Status</h5>
                <span class="badge bg-light text-muted">{{ $expected }} jadwal</span>
            </div>
            <div class="card-body">
                <div id="statusChart" class="chart-box"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-bar-chart-2-line me-1 text-primary"></i>Kehadiran per Jam Pelajaran</h5>
                <span class="badge bg-light text-muted">Tepat waktu vs terlambat vs belum</span>
            </div>
            <div class="card-body">
                <div id="hourlyChart" class="chart-box"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-line-chart-line me-1 text-primary"></i>Tren Kehadiran 7 Hari</h5>
                <span class="badge bg-light text-muted">Persentase check-in</span>
            </div>
            <div class="card-body">
                <div id="weeklyChart" class="chart-box"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-horizontal-bar-chart-line me-1 text-primary"></i>Kehadiran per Kelas</h5>
                <span class="badge bg-light text-muted">Top 10 kelas</span>
            </div>
            <div class="card-body">
                <div id="classChart" class="chart-box"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-team-line me-1 text-primary"></i>Kepatuhan per Guru</h5>
                <span class="badge bg-light text-muted">{{ $teacherStats->count() }} guru</span>
            </div>
            <div class="card-body p-0">
                <div class="attendance-table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Guru</th>
                                <th class="text-center">Jadwal</th>
                                <th class="text-center">Hadir</th>
                                <th class="text-center">Terlambat</th>
                                <th class="text-center">Belum</th>
                                <th class="pe-3" style="min-width: 130px">Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teacherStats as $teacher)
                                <tr>
                                    <td class="ps-3">
                                        <span class="fw-medium">{{ $teacher['name'] }}</span>
                                    </td>
                                    <td class="text-center">{{ $teacher['total'] }}</td>
                                    <td class="text-center text-success fw-medium">{{ $teacher['present'] }}</td>
                                    <td class="text-center {{ $teacher['late'] > 0 ? 'text-warning fw-medium' : 'text-muted' }}">{{ $teacher['late'] }}</td>
                                    <td class="text-center {{ $teacher['absent'] > 0 ? 'text-danger fw-medium' : 'text-muted' }}">{{ $teacher['absent'] }}</td>
                                    <td class="pe-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="attendance-progress flex-grow-1">
                                                <span class="{{ $teacher['rate'] >= 80 ? 'bg-success' : ($teacher['rate'] >= 50 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $teacher['rate'] }}%"></span>
                                            </div>
                                            <small class="fw-semibold">{{ $teacher['rate'] }}%</small>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada jadwal pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-flashlight-line me-1 text-primary"></i>Aktivitas Check-in Terakhir</h5>
                <span class="badge bg-light text-muted">{{ $recentChecks->count() }} aktivitas</span>
            </div>
            <div class="card-body">
                @if($recentChecks->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="ri-inbox-line fs-1 d-block mb-2"></i>
                        <span class="small">Belum ada check-in pada filter ini.</span>
                    </div>
                @else
                    <div class="attendance-timeline">
                        @foreach($recentChecks as $check)
                            <div class="attendance-timeline-item {{ $check->status_masuk === 'terlambat' ? 'is-late' : '' }}">
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="min-w-0">
                                        <div class="fw-medium text-truncate">{{ $check->teacher?->name ?? 'Guru tidak dikenal' }}</div>
                                        <div class="small text-muted text-truncate">
                                            {{ $check->jadwalKbm?->studyGroup?->name ?? '—' }}
                                            @if($check->jadwalKbm?->subject?->name)
                                                · {{ $check->jadwalKbm->subject->name }}
                                            @endif
                                        </div>
                                    </div>
                                    <span class="attendance-chip {{ $check->status_masuk === 'terlambat' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">
                                        {{ $check->status_masuk === 'terlambat' ? 'Terlambat' : 'Tepat waktu' }}
                                    </span>
                                </div>
                                <div class="small text-muted mt-1">
                                    <i class="ri-login-box-line me-1"></i>{{ $check->actual_time_in?->format('H:i') ?? '—' }}
                                    @if($check->status_masuk === 'terlambat' && $check->late_minutes)
                                        <span class="ms-2 text-warning">+{{ $check->late_minutes }} menit</span>
                                    @endif
                                    @if($check->actual_time_out)
                                        <span class="ms-2"><i class="ri-logout-box-r-line me-1"></i>{{ $check->actual_time_out->format('H:i') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tindak lanjut --}}
<div class="row g-4">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-error-warning-line me-1 text-warning"></i>Belum Check-in</h5>
                <span class="badge {{ $notPresent->count() ? 'bg-danger-subtle text-danger' : 'bg-light text-muted' }}">{{ $notPresent->count() }} jadwal</span>
            </div>
            <div class="card-body p-0">
                <div class="attendance-table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Guru</th>
                                <th>Kelas / Mapel</th>
                                <th>Jam</th>
                                <th class="pe-3 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notPresent as $jadwal)
                                <tr>
                                    <td class="ps-3 fw-medium">{{ $jadwal->teacher?->name ?? 'Guru tidak dikenal' }}</td>
                                    <td>
                                        <div>{{ $jadwal->studyGroup?->name ?? '—' }}</div>
                                        <small class="text-muted">{{ $jadwal->subject?->name ?? '—' }}</small>
                                    </td>
                                    <td class="text-nowrap small">{{ $jadwal->start_time }}–{{ $jadwal->end_time }}</td>
                                    <td class="pe-3 text-end">
                                        @if($isToday)
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0"
                                                data-bs-toggle="modal" data-bs-target="#manualCheckinModal"
                                                data-teacher-id="{{ $jadwal->teacher_id }}"
                                                data-jadwal-id="{{ $jadwal->id }}"
                                                data-start-time="{{ $jadwal->start_time }}"
                                                title="Check-in manual">
                                                <i class="ri-login-box-line me-1"></i>Check-in
                                            </button>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-success py-4"><i class="ri-checkbox-circle-line me-1"></i>Semua jadwal sudah check-in.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-logout-box-r-line me-1 text-warning"></i>Belum Checkout</h5>
                <span class="badge {{ $notCheckedOut->count() ? 'bg-warning-subtle text-warning' : 'bg-light text-muted' }}">{{ $notCheckedOut->count() }} guru</span>
            </div>
            <div class="card-body p-0">
                <div class="attendance-table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Guru</th>
                                <th>Kelas</th>
                                <th class="pe-3 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notCheckedOut as $item)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-medium">{{ $item->teacher?->name ?? 'Guru tidak dikenal' }}</div>
                                        <small class="text-muted">Masuk {{ $item->actual_time_in?->format('H:i') ?? '—' }}</small>
                                    </td>
                                    <td class="small">{{ $item->jadwalKbm?->studyGroup?->name ?? '—' }}</td>
                                    <td class="pe-3 text-end">
                                        @if($isToday)
                                            <button type="button" class="btn btn-sm btn-outline-warning py-0"
                                                data-bs-toggle="modal" data-bs-target="#manualCheckoutModal"
                                                data-attendance-id="{{ $item->id }}"
                                                data-teacher-name="{{ $item->teacher?->name ?? '' }}"
                                                data-checkout-time="{{ $item->jadwalKbm?->end_time ?? now()->format('H:i') }}"
                                                title="Checkout manual">
                                                <i class="ri-logout-box-r-line me-1"></i>Checkout
                                            </button>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-success py-4"><i class="ri-checkbox-circle-line me-1"></i>Tidak ada yang tertinggal.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal check-in manual --}}
<div class="modal fade" id="manualCheckinModal" tabindex="-1" aria-labelledby="manualCheckinModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="manualCheckinModalLabel"><i class="ri-keyboard-line me-1"></i>Check-in Manual</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('user.teacher-qr.manual-checkin', ['userId' => $userId]) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Guru <span class="text-danger">*</span></label>
                        <select name="teacher_id" id="mc_teacher_id" class="form-select form-select-sm" required>
                            <option value="">— Pilih guru —</option>
                            @foreach($teacherOptions as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Jadwal / Kelas <span class="text-danger">*</span></label>
                        <select name="jadwal_kbm_id" id="mc_jadwal_kbm_id" class="form-select form-select-sm" required>
                            <option value="">— Pilih jadwal —</option>
                            @foreach($schedules as $jadwal)
                                <option value="{{ $jadwal->id }}" data-teacher="{{ $jadwal->teacher_id }}" data-start="{{ $jadwal->start_time }}">
                                    {{ $jadwal->teacher?->name ?? '?' }} · {{ $jadwal->studyGroup?->name ?? '' }} · {{ $jadwal->subject?->name ?? '' }} ({{ $jadwal->start_time }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Waktu Check-in <span class="text-danger">*</span></label>
                        <input type="time" name="checkin_time" id="mc_checkin_time" class="form-control form-control-sm" value="{{ now()->format('H:i') }}" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-medium">Catatan</label>
                        <textarea name="notes" id="mc_notes" class="form-control form-control-sm" rows="2" placeholder="Opsional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="ri-login-box-line me-1"></i>Simpan Check-in</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal checkout manual --}}
<div class="modal fade" id="manualCheckoutModal" tabindex="-1" aria-labelledby="manualCheckoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="manualCheckoutModalLabel"><i class="ri-logout-box-r-line me-1"></i>Checkout Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('user.teacher-qr.manual-checkout', ['userId' => $userId]) }}">
                @csrf
                <input type="hidden" name="attendance_id" id="mco_attendance_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Guru</label>
                        <input type="text" id="mco_teacher_name" class="form-control form-control-sm" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Waktu Checkout <span class="text-danger">*</span></label>
                        <input type="time" name="checkout_time" id="mco_checkout_time" class="form-control form-control-sm" value="{{ now()->format('H:i') }}" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-medium">Catatan</label>
                        <textarea name="notes" id="mco_notes" class="form-control form-control-sm" rows="2" placeholder="Opsional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning"><i class="ri-logout-box-r-line me-1"></i>Simpan Checkout</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    const isToday = @json($isToday);
    const statusLabels = @json($statusLabels);
    const statusValues = @json($statusValues);
    const statusColors = @json($statusColors);
    const hourly = @json($hourlyStats);
    const weekly = @json($weeklyTrend);
    const classes = @json($classChartData);

    if (window.ApexCharts) {
        window.Apex = {
            chart: { fontFamily: 'inherit', toolbar: { show: false } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            grid: { borderColor: '#edf0f5' },
            colors: ['#0ab39c', '#f7b84b', '#f06548', '#405189', '#299cdb'],
        };

        const hasStatus = statusValues.reduce((a, b) => a + Number(b || 0), 0) > 0;
        const statusEl = document.getElementById('statusChart');
        if (statusEl) {
            if (hasStatus) {
                new ApexCharts(statusEl, {
                    series: statusValues,
                    labels: statusLabels,
                    chart: { type: 'donut', height: 285 },
                    colors: statusColors,
                    legend: { position: 'bottom', fontSize: '12px' },
                    plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: 'Jadwal', formatter: () => String(statusValues.reduce((a, b) => a + Number(b || 0), 0)) } } } } },
                    tooltip: { y: { formatter: (v) => `${v} jadwal` } },
                }).render();
            } else {
                statusEl.classList.add('is-empty');
                statusEl.textContent = 'Belum ada data status.';
            }
        }

        const hourEl = document.getElementById('hourlyChart');
        if (hourEl) {
            if (hourly.length) {
                new ApexCharts(hourEl, {
                    series: [
                        { name: 'Tepat waktu', data: hourly.map((h) => Number(h.present || 0)) },
                        { name: 'Terlambat', data: hourly.map((h) => Number(h.late || 0)) },
                        { name: 'Belum absen', data: hourly.map((h) => Number(h.absent || 0)) },
                    ],
                    chart: { type: 'bar', height: 285, stacked: true },
                    plotOptions: { bar: { horizontal: false, columnWidth: '42%', borderRadius: 4 } },
                    xaxis: { categories: hourly.map((h) => h.label), labels: { style: { fontSize: '11px' } } },
                    yaxis: { labels: { formatter: (v) => Math.round(v) } },
                    legend: { position: 'bottom', fontSize: '12px' },
                    fill: { opacity: 1 },
                    tooltip: { y: { formatter: (v) => `${v} jadwal` } },
                }).render();
            } else {
                hourEl.classList.add('is-empty');
                hourEl.textContent = 'Belum ada jadwal pada filter ini.';
            }
        }

        const weeklyEl = document.getElementById('weeklyChart');
        if (weeklyEl) {
            new ApexCharts(weeklyEl, {
                series: [{ name: 'Kehadiran', data: weekly.map((d) => Number(d.rate || 0)) }],
                chart: { type: 'area', height: 285 },
                xaxis: { categories: weekly.map((d) => d.label), labels: { style: { fontSize: '11px' } } },
                yaxis: { min: 0, max: 100, labels: { formatter: (v) => `${Math.round(v)}%` } },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .35, opacityTo: .05, stops: [0, 100] } },
                markers: { size: 4, strokeWidth: 2 },
                tooltip: { y: { formatter: (v) => `${v}%` } },
                colors: ['#405189'],
            }).render();
        }

        const classEl = document.getElementById('classChart');
        if (classEl) {
            if (classes.length) {
                new ApexCharts(classEl, {
                    series: [{ name: 'Kehadiran', data: classes.map((c) => Number(c.rate || 0)) }],
                    chart: { type: 'bar', height: 285 },
                    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '56%' } },
                    xaxis: { categories: classes.map((c) => c.name), max: 100, labels: { formatter: (v) => `${Math.round(v)}%` } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    colors: ['#0ab39c'],
                    tooltip: { y: { formatter: (v) => `${v}%` } },
                }).render();
            } else {
                classEl.classList.add('is-empty');
                classEl.textContent = 'Belum ada data kelas.';
            }
        }
    }

    // Auto-refresh hanya untuk data hari ini.
    const toggle = document.getElementById('autoRefreshToggle');
    let timer = null;
    const refreshKey = 'alim_waka_autorefresh';

    function scheduleRefresh() {
        if (!isToday) return;
        if (localStorage.getItem(refreshKey) === '0') return;
        timer = setTimeout(() => window.location.reload(), 60000);
    }
    function updateToggle() {
        if (!toggle) return;
        const enabled = isToday && localStorage.getItem(refreshKey) !== '0';
        toggle.innerHTML = `<i class="ri-refresh-line me-1"></i>${enabled ? 'Auto-refresh aktif' : 'Auto-refresh nonaktif'}`;
        toggle.classList.toggle('btn-light', enabled);
        toggle.classList.toggle('btn-outline-light', !enabled);
    }
    if (toggle) {
        updateToggle();
        toggle.addEventListener('click', () => {
            const enabled = localStorage.getItem(refreshKey) !== '0';
            localStorage.setItem(refreshKey, enabled ? '0' : '1');
            if (timer) clearTimeout(timer);
            updateToggle();
            scheduleRefresh();
        });
        scheduleRefresh();
    }

    // Prefill modal check-in
    const mcModal = document.getElementById('manualCheckinModal');
    if (mcModal) {
        mcModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (!btn) return;
            document.getElementById('mc_teacher_id').value = btn.getAttribute('data-teacher-id') || '';
            document.getElementById('mc_jadwal_kbm_id').value = btn.getAttribute('data-jadwal-id') || '';
            document.getElementById('mc_checkin_time').value = (btn.getAttribute('data-start-time') || '').slice(0, 5) || '{{ now()->format('H:i') }}';
        });
    }

    // Prefill modal checkout
    const mcoModal = document.getElementById('manualCheckoutModal');
    if (mcoModal) {
        mcoModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (!btn) return;
            document.getElementById('mco_attendance_id').value = btn.getAttribute('data-attendance-id') || '';
            document.getElementById('mco_teacher_name').value = btn.getAttribute('data-teacher-name') || '';
            document.getElementById('mco_checkout_time').value = (btn.getAttribute('data-checkout-time') || '').slice(0, 5) || '{{ now()->format('H:i') }}';
        });
    }
})();
</script>

{{-- Live event: tetap tampilkan notifikasi; data diperbarui via auto-refresh. --}}
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.17.1/dist/echo.iife.js"></script>
<script>
(function () {
    const schoolId = @json($broadcastSchoolId ?? null);
    const pusherKey = @json(config('broadcasting.connections.pusher.key', ''));
    if (!schoolId || !pusherKey || !window.Echo) {
        return;
    }

    try {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: @json(config('broadcasting.connections.pusher.options.cluster', 'ap1')),
            forceTLS: true,
            encrypted: true,
        });

        window.Echo.channel(`waka-teacher-absensi.${schoolId}`)
            .listen('.teacher.qr.scanned', (e) => showLiveToast('✅', `${e.teacherName} masuk ${e.studyGroupName} (${e.status})`, 'success'))
            .listen('.teacher.checked.out', (e) => showLiveToast('🕓', `${e.teacherName} keluar dari ${e.studyGroupName}`, 'info'));
    } catch (error) {
        console.warn('[Attendance] Echo unavailable', error);
    }

    function showLiveToast(icon, message, type = 'info') {
        const id = 'toast-' + Date.now();
        const bg = type === 'success' ? 'bg-success' : 'bg-info';
        const html = `<div id="${id}" class="toast align-items-center ${bg} text-white border-0 position-fixed bottom-0 end-0 m-3" role="alert" style="z-index:9999">
            <div class="d-flex"><div class="toast-body fw-medium">${icon} ${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', html);
        const el = document.getElementById(id);
        new bootstrap.Toast(el, { delay: 4000 }).show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }
})();
</script>
@endpush
