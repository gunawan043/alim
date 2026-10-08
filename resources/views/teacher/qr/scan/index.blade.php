{{-- Scan QR Kehadiran Guru — 1 QR per kelas, check-in/check-out otomatis --}}
@extends('layouts.master')
@section('title') Scan QR Kehadiran @endsection

@push('css')
<style>
    #qr-reader { width: 100%; max-width: 420px; margin: 0 auto; }
    #qr-reader video { border-radius: 10px; width: 100% !important; height: auto !important; object-fit: cover; }
    #qr-reader img { max-width: 100%; height: auto; }
    @media (max-width: 575.98px) { #qr-reader { max-width: 100%; } }

    .stat-card { border: none; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); transition: transform .2s; }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-card .card-body { padding: .8rem 1rem; }
    .stat-icon { width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0; }
    .stat-value { font-size: 1.35rem; font-weight: 700; line-height: 1.15; }
    .stat-label { font-size: .66rem; text-transform: uppercase; letter-spacing: .5px; margin: 0; color: #94a3b8; }

    .clock-chip { font-family: 'SF Mono', Monaco, monospace; font-size: .8rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 20px; padding: 4px 12px; white-space: nowrap; }

    .nav-tabs-custom { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; border-bottom: 2px solid #e2e8f0; }
    .nav-tabs-custom::-webkit-scrollbar { display: none; }
    .nav-tabs-custom .nav-link { border: none; border-bottom: 3px solid transparent; color: #64748b; font-weight: 500; padding: 10px 13px; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
    .nav-tabs-custom .nav-link:hover { color: #6366f1; border-bottom-color: #cbd5e1; }
    .nav-tabs-custom .nav-link.active { color: #6366f1; border-bottom-color: #6366f1; background: transparent; }
    .nav-tabs-custom .step-number { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #e2e8f0; color: #64748b; font-size: 11px; font-weight: 700; flex-shrink: 0; }
    .nav-tabs-custom .nav-link.active .step-number { background: #6366f1; color: #fff; }

    .spotlight-card { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 12px; border: 1px solid; margin-bottom: 12px; }
    .spotlight-card.ongoing { background: #eff6ff; border-color: #bfdbfe; }
    .spotlight-card.next { background: #f8fafc; border-color: #e2e8f0; }
    .spotlight-label { font-size: .66rem; text-transform: uppercase; letter-spacing: .5px; color: #64748b; }
    .pulse-dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; flex-shrink: 0; animation: pulse 1.6s infinite; }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(34,197,94,.45); }
        70% { box-shadow: 0 0 0 10px rgba(34,197,94,0); }
        100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }

    .schedule-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .schedule-table-wrap table { min-width: 620px; margin-bottom: 0; }
    .schedule-table-wrap thead th { white-space: nowrap; font-size: .76rem; background: #f8fafc; text-transform: uppercase; letter-spacing: .3px; color: #64748b; }
    .schedule-table-wrap tbody td { vertical-align: middle; }
    .time-pill { font-family: 'SF Mono', Monaco, monospace; font-size: .76rem; background: #f1f5f9; border-radius: 6px; padding: 2px 8px; white-space: nowrap; }
    .row-now { box-shadow: inset 3px 0 0 #6366f1; }

    .scanner-shell { border: 2px dashed #cbd5e1; border-radius: 12px; min-height: 260px; display: flex; align-items: center; justify-content: center; background: #f8fafc; transition: border-color .2s; overflow: hidden; }
    .scanner-shell.scanning { border-color: #6366f1; border-style: solid; }

    .result-icon { width: 84px; height: 84px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; font-size: 2.6rem; }
    .result-icon.ok { background: #dcfce7; color: #16a34a; }
    .result-icon.fail { background: #fee2e2; color: #dc2626; }
</style>
@endpush

@section('content')
@php
    $userId = $userId ?? auth()->id();
    $nowHms = now()->format('H:i:s');
@endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Kehadiran @endslot
    @slot('li_2') Scan QR @endslot
    @slot('title') Absensi Kehadiran Guru @endslot
@endcomponent

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <p class="text-muted small mb-0">
        {{ $dayName }}, {{ $today->translatedFormat('d F Y') }}
        @if($academicYear) • {{ $academicYear->name }} ({{ ucfirst($academicYear->semester ?? '-') }}) @endif
        • {{ $stats['total'] }} jadwal hari ini
    </p>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="clock-chip" title="Waktu saat ini"><i class="ri-time-line me-1 text-primary"></i><span id="live-clock">--:--:--</span> WITA</span>
        @if($stats['total'] > 0)
            <span class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded border bg-white small">
                <span class="text-muted">Kelengkapan</span>
                <span class="progress" style="width:90px;height:6px">
                    <span class="progress-bar bg-{{ $completion >= 100 ? 'success' : 'primary' }}" style="width: {{ $completion }}%"></span>
                </span>
                <span class="fw-semibold">{{ $stats['checked_out'] }}/{{ $stats['total'] }}</span>
            </span>
        @endif
        <a href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
            <i class="ri-history-line me-1"></i>Riwayat
        </a>
    </div>
</div>

@if($schedules->isEmpty())
    <div class="alert alert-info d-flex align-items-center gap-2">
        <i class="ri-information-line fs-5"></i>
        <div>
            <strong>Tidak ada jadwal mengajar hari ini ({{ $dayName }}).</strong>
            <div class="small">Halaman scan tetap bisa dibuka, tetapi absensi hanya dapat diproses pada jam mengajar Anda.</div>
        </div>
    </div>
@endif

{{-- Statistik ringkas --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon bg-primary-subtle text-primary"><i class="ri-calendar-check-line"></i></span>
                <div>
                    <p class="stat-label">Jadwal Hari Ini</p>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon bg-success-subtle text-success"><i class="ri-login-box-line"></i></span>
                <div>
                    <p class="stat-label">Sudah Masuk</p>
                    <div class="stat-value text-success">{{ $stats['checked_in'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon bg-info-subtle text-info"><i class="ri-logout-box-r-line"></i></span>
                <div>
                    <p class="stat-label">Sudah Keluar</p>
                    <div class="stat-value text-info">{{ $stats['checked_out'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon bg-warning-subtle text-warning"><i class="ri-time-line"></i></span>
                <div>
                    <p class="stat-label">Terlambat</p>
                    <div class="stat-value text-warning">{{ $stats['late'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon bg-danger-subtle text-danger"><i class="ri-user-unfollow-line"></i></span>
                <div>
                    <p class="stat-label">Belum Absen</p>
                    <div class="stat-value text-danger">{{ $stats['pending'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Scanner --}}
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ri-qr-scan-2-line me-2 text-primary"></i>Scan QR Kelas</h5>
                <span class="badge bg-light text-secondary border" id="camera-state"><i class="ri-camera-line me-1"></i>Kamera</span>
            </div>
            <div class="card-body">
                {{-- Spotlight: kelas berlangsung / berikutnya --}}
                @if($currentClass)
                    @php $att = $attendances[$currentClass->id] ?? null; @endphp
                    <div class="spotlight-card ongoing">
                        <span class="pulse-dot"></span>
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="spotlight-label">Sedang berlangsung</div>
                            <div class="fw-bold text-truncate">{{ $currentClass->studyGroup?->name ?? '-' }} · {{ $currentClass->subject?->name ?? '-' }}</div>
                            <div class="small text-muted">
                                Jam {{ $currentClass->slot_index }} · <span class="time-pill">{{ substr($currentClass->start_time, 0, 5) }}–{{ substr($currentClass->end_time, 0, 5) }}</span>
                                <span id="countdown-end" data-countdown-end="{{ substr($currentClass->end_time, 0, 5) }}"></span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success flex-shrink-0" onclick="triggerScan('{{ $currentClass->study_group_id }}')">
                            <i class="ri-logout-box-r-line me-1"></i>Scan Keluar
                        </button>
                    </div>
                @elseif($nextClass)
                    <div class="spotlight-card next">
                        <span class="stat-icon bg-primary-subtle text-primary" style="width:34px;height:34px"><i class="ri-calendar-schedule-line"></i></span>
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="spotlight-label">Kelas berikutnya</div>
                            <div class="fw-bold text-truncate">{{ $nextClass->studyGroup?->name ?? '-' }} · {{ $nextClass->subject?->name ?? '-' }}</div>
                            <div class="small text-muted">
                                Jam {{ $nextClass->slot_index }} · <span class="time-pill">{{ substr($nextClass->start_time, 0, 5) }}–{{ substr($nextClass->end_time, 0, 5) }}</span>
                                <span id="countdown-start" data-countdown-start="{{ substr($nextClass->start_time, 0, 5) }}"></span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary flex-shrink-0" onclick="triggerScan('{{ $nextClass->study_group_id }}')">
                            <i class="ri-login-box-line me-1"></i>Scan Masuk
                        </button>
                    </div>
                @elseif($stats['total'] > 0)
                    <div class="spotlight-card next">
                        <span class="stat-icon bg-success-subtle text-success" style="width:34px;height:34px"><i class="ri-checkbox-circle-line"></i></span>
                        <div class="flex-grow-1">
                            <div class="spotlight-label">Selesai</div>
                            <div class="fw-semibold">Semua kelas hari ini sudah diabsen lengkap.</div>
                        </div>
                    </div>
                @endif

@php $canManualAbsen = canPermission('teacher-attendance_manual'); @endphp
                <ul class="nav nav-pills nav-pills-custom mb-3" id="scanTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="camera-tab" data-bs-toggle="tab" data-bs-target="#camera-panel" type="button" role="tab">
                            <i class="ri-camera-line me-1"></i>Kamera
                        </button>
                    </li>
                    @if($canManualAbsen)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual-panel" type="button" role="tab">
                                <i class="ri-keyboard-line me-1"></i>Manual
                            </button>
                        </li>
                    @endif
                </ul>

                <div class="tab-content" id="scanTabContent">
                    <div class="tab-pane fade show active" id="camera-panel" role="tabpanel">
                        <div class="scanner-shell" id="scanner-container">
                            <div class="text-center text-muted px-3">
                                <i class="ri-camera-line" style="font-size:2rem;color:#cbd5e1"></i>
                                <p class="mt-2 mb-0">Kamera sedang disiapkan…</p>
                            </div>
                        </div>
                        <div id="scanner-status" class="text-center text-muted small mt-2"></div>
                    </div>

                    @if($canManualAbsen)
                    <div class="tab-pane fade" id="manual-panel" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pilih Kelas / Jam Hari Ini</label>
                            <select id="manual_schedule" class="form-select form-select-sm">
                                <option value="">— Pilih jadwal mengajar —</option>
                                @foreach($schedules as $jadwal)
                                    @php $att = $attendances[$jadwal->id] ?? null; @endphp
                                    <option value="{{ $jadwal->study_group_id }}">
                                        Jam {{ $jadwal->slot_index }} · {{ substr($jadwal->start_time, 0, 5) }}–{{ substr($jadwal->end_time, 0, 5) }}
                                        · {{ $jadwal->studyGroup?->name ?? '-' }} · {{ $jadwal->subject?->name ?? '-' }}
                                        @if($att && $att->actual_time_out) (selesai)
                                        @elseif($att && $att->actual_time_in) (sedang berlangsung)
                                        @else (belum absen)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary w-100" id="btn-manual-process" {{ $schedules->isEmpty() ? 'disabled' : '' }}>
                            <i class="ri-check-double-line me-1"></i>Proses Absen Masuk / Keluar
                        </button>
                        <div class="text-center text-muted small my-2">— atau tempel isi QR —</div>
                        <input type="text" id="manual_token" class="form-control form-control-sm" placeholder="Tempel URL / token hasil scan QR">
                        <button type="button" class="btn btn-outline-secondary w-100 mt-2" id="btn-manual-token">
                            <i class="ri-qr-scan-line me-1"></i>Proses dari Token QR
                        </button>
                    </div>
                    @endif
                </div>

                <div class="alert alert-light border small mb-0 mt-3">
                    <i class="ri-information-line me-1 text-primary"></i>
                    Scan <strong>masuk</strong> saat tiba di kelas dan <strong>keluar</strong> saat jam berakhir.
                    QR ditempel di depan kelas (lihat menu <a href="{{ route('user.qr.index', ['userId' => $userId]) }}">QR Kelas</a>).
                </div>

                @unless($canManualAbsen)
                    <div class="alert alert-warning border small mb-0 mt-2">
                        <i class="ri-shield-user-line me-1"></i>
                        Absensi manual (tanpa scan QR) hanya dapat dilakukan oleh
                        <strong>Waka / Kepala / Tata Usaha</strong>. Guru wajib memindai QR kelas saat jam mengajar.
                    </div>
                @endunless
            </div>
        </div>
    </div>

    {{-- Wizard jadwal hari ini --}}
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header pb-0">
                <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#step-pending" type="button" role="tab">
                            <span class="step-number">1</span><i class="ri-user-unfollow-line"></i>Belum Absen
                            <span class="badge bg-danger-subtle text-danger ms-1">{{ $belumAbsen->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step-ongoing" type="button" role="tab">
                            <span class="step-number">2</span><i class="ri-hourglass-line"></i>Berlangsung
                            <span class="badge bg-info-subtle text-info ms-1">{{ $berlangsung->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step-done" type="button" role="tab">
                            <span class="step-number">3</span><i class="ri-checkbox-circle-line"></i>Selesai
                            <span class="badge bg-success-subtle text-success ms-1">{{ $selesaiHariIni->count() }}</span>
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-3">
                <div class="tab-content">
                    {{-- Belum absen --}}
                    <div class="tab-pane fade show active" id="step-pending" role="tabpanel">
                        @if($belumAbsen->isEmpty())
                            <div class="text-center py-4 text-muted">
                                <i class="{{ $schedules->isEmpty() ? 'ri-calendar-close-line' : 'ri-emotion-happy-line' }} d-block mb-1" style="font-size:1.6rem"></i>
                                {{ $schedules->isEmpty() ? 'Tidak ada jadwal mengajar hari ini.' : 'Semua jadwal hari ini sudah diabsen.' }}
                            </div>
                        @else
                            <div class="schedule-table-wrap">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr><th>Jam</th><th>Waktu</th><th>Kelas</th><th>Mapel</th><th class="text-end">Aksi</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($belumAbsen as $jadwal)
                                            @php $isNowRow = $nowHms >= $jadwal->start_time && $nowHms <= $jadwal->end_time; @endphp
                                            <tr class="{{ $isNowRow ? 'row-now' : '' }}">
                                                <td class="text-center fw-semibold">{{ $jadwal->slot_index }}</td>
                                                <td><span class="time-pill">{{ substr($jadwal->start_time, 0, 5) }}–{{ substr($jadwal->end_time, 0, 5) }}</span></td>
                                                <td>
                                                    <div class="fw-medium">
                                                        {{ $jadwal->studyGroup?->name ?? '-' }}
                                                        @if($isNowRow) <span class="badge bg-primary ms-1">Sekarang</span> @endif
                                                    </div>
                                                    <small class="text-muted">{{ $jadwal->studyGroup?->gradeLevel?->name ?? '' }} {{ $jadwal->room ? '· '.$jadwal->room : '' }}</small>
                                                </td>
                                                <td>{{ $jadwal->subject?->name ?? '-' }}</td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                            onclick="triggerScan('{{ $jadwal->study_group_id }}')">
                                                        <i class="ri-login-box-line me-1"></i>Scan Masuk
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Berlangsung --}}
                    <div class="tab-pane fade" id="step-ongoing" role="tabpanel">
                        @if($berlangsung->isEmpty())
                            <div class="text-center py-4 text-muted">
                                <i class="ri-hourglass-line d-block mb-1" style="font-size:1.6rem"></i>
                                Tidak ada kelas yang sedang berlangsung.
                            </div>
                        @else
                            <div class="schedule-table-wrap">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr><th>Jam</th><th>Kelas</th><th>Mapel</th><th>Scan Masuk</th><th class="text-end">Aksi</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($berlangsung as $jadwal)
                                            @php
                                                $att = $attendances[$jadwal->id] ?? null;
                                                $isNowRow = $nowHms >= $jadwal->start_time && $nowHms <= $jadwal->end_time;
                                            @endphp
                                            <tr class="table-info-subtle {{ $isNowRow ? 'row-now' : '' }}">
                                                <td class="text-center fw-semibold">{{ $jadwal->slot_index }}</td>
                                                <td>
                                                    <div class="fw-medium">
                                                        {{ $jadwal->studyGroup?->name ?? '-' }}
                                                        @if($isNowRow) <span class="badge bg-primary ms-1">Sekarang</span> @endif
                                                    </div>
                                                    <small class="text-muted"><span class="time-pill">{{ substr($jadwal->start_time, 0, 5) }}–{{ substr($jadwal->end_time, 0, 5) }}</span></small>
                                                </td>
                                                <td>{{ $jadwal->subject?->name ?? '-' }}</td>
                                                <td>
                                                    <span class="time-pill">{{ $att?->actual_time_in?->format('H:i') ?? '—' }}</span>
                                                    @if($att && ($att->status_masuk === 'terlambat' || $att->late_minutes > 0))
                                                        <span class="badge bg-warning-subtle text-warning ms-1">Terlambat {{ $att->late_minutes }} mnt</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-success"
                                                            onclick="triggerScan('{{ $jadwal->study_group_id }}')">
                                                        <i class="ri-logout-box-r-line me-1"></i>Scan Keluar
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Selesai --}}
                    <div class="tab-pane fade" id="step-done" role="tabpanel">
                        @if($selesaiHariIni->isEmpty())
                            <div class="text-center py-4 text-muted">
                                <i class="ri-calendar-line d-block mb-1" style="font-size:1.6rem"></i>
                                Belum ada kelas yang selesai diabsen hari ini.
                            </div>
                        @else
                            <div class="schedule-table-wrap">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr><th>Jam</th><th>Kelas</th><th>Mapel</th><th>Masuk</th><th>Keluar</th><th>Durasi</th><th class="text-center">Status</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selesaiHariIni as $jadwal)
                                            @php $att = $attendances[$jadwal->id] ?? null; @endphp
                                            <tr class="table-success-subtle">
                                                <td class="text-center fw-semibold">{{ $jadwal->slot_index }}</td>
                                                <td>
                                                    <div class="fw-medium">{{ $jadwal->studyGroup?->name ?? '-' }}</div>
                                                    <small class="text-muted"><span class="time-pill">{{ substr($jadwal->start_time, 0, 5) }}–{{ substr($jadwal->end_time, 0, 5) }}</span></small>
                                                </td>
                                                <td>{{ $jadwal->subject?->name ?? '-' }}</td>
                                                <td><span class="time-pill">{{ $att?->actual_time_in?->format('H:i') ?? '—' }}</span></td>
                                                <td><span class="time-pill">{{ $att?->actual_time_out?->format('H:i') ?? '—' }}</span></td>
                                                <td>{{ $att && $att->duration_minutes > 0 ? $att->duration_minutes.' mnt' : '—' }}</td>
                                                <td class="text-center">
                                                    @if($att && ($att->status_masuk === 'terlambat' || $att->late_minutes > 0))
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Terlambat</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Tepat Waktu</span>
                                                    @endif
                                                    @if($att && ($att->status_keluar === 'keluar_cepat' || $att->early_leave_minutes > 0))
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle">Keluar Cepat</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Riwayat 7 hari terakhir --}}
@if($recentRecords->isNotEmpty())
    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0"><i class="ri-history-line text-secondary me-1"></i>Aktivitas 7 Hari Terakhir</h6>
            <a href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-secondary">
                Lihat Semua <i class="ri-arrow-right-line ms-1"></i>
            </a>
        </div>
        <div class="card-body py-2">
            <div class="row g-2">
                @foreach($recentRecords as $record)
                    @php
                        $late = $record->status_masuk === 'terlambat' || $record->late_minutes > 0;
                        $early = $record->status_keluar === 'keluar_cepat' || $record->early_leave_minutes > 0;
                        $tone = $early ? 'info' : ($late ? 'warning' : 'success');
                    @endphp
                    <div class="col-md-6 col-xl-4">
                        <div class="d-flex align-items-center gap-2 p-2 rounded border h-100">
                            <span class="stat-icon bg-{{ $tone }}-subtle text-{{ $tone }}" style="width:34px;height:34px;font-size:.95rem">
                                <i class="ri-checkbox-circle-line"></i>
                            </span>
                            <div class="flex-grow-1" style="min-width:0">
                                <div class="fw-medium small text-truncate">
                                    {{ $record->jadwalKbm?->studyGroup?->name ?? '-' }} · {{ $record->jadwalKbm?->subject?->name ?? '-' }}
                                </div>
                                <small class="text-muted d-block">
                                    {{ $record->attendance_date?->translatedFormat('d M') }} ·
                                    {{ $record->actual_time_in?->format('H:i') ?? '—' }}–{{ $record->actual_time_out?->format('H:i') ?? '—' }}
                                </small>
                                <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}" style="font-size:.64rem">
                                    {{ $late ? 'Terlambat '.$record->late_minutes.' mnt' : 'Tepat waktu' }}{{ $early ? ' · Keluar cepat '.$record->early_leave_minutes.' mnt' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const userId = {{ Js::from($userId) }};
const scanUrls = @json($scanUrls);

let html5QrCode = null;
let isProcessing = false;

/* ── Jam live + countdown ───────────────────────────────────── */
function tickClock() {
    const el = document.getElementById('live-clock');
    if (el) {
        el.textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}

function tickCountdowns() {
    const now = new Date();

    const startEl = document.getElementById('countdown-start');
    if (startEl && startEl.dataset.countdownStart) {
        const [h, m] = startEl.dataset.countdownStart.split(':').map(Number);
        const target = new Date(); target.setHours(h, m, 0, 0);
        const diff = Math.round((target - now) / 60000);
        startEl.textContent = diff > 0 ? `• mulai dalam ${diff} menit` : '• siap dimulai';
    }

    const endEl = document.getElementById('countdown-end');
    if (endEl && endEl.dataset.countdownEnd) {
        const [h, m] = endEl.dataset.countdownEnd.split(':').map(Number);
        const target = new Date(); target.setHours(h, m, 0, 0);
        const diff = Math.round((target - now) / 60000);
        endEl.textContent = diff > 0 ? `• berakhir dalam ${diff} menit` : '• segera berakhir';
    }
}

setInterval(tickClock, 1000);
setInterval(tickCountdowns, 30000);
tickClock();
tickCountdowns();

/* ── Scanner kamera ─────────────────────────────────────────── */
function setCameraState(on) {
    const el = document.getElementById('camera-state');
    if (!el) return;
    el.className = 'badge border ' + (on ? 'bg-success-subtle text-success border-success-subtle' : 'bg-light text-secondary border');
    el.innerHTML = '<i class="ri-camera-line me-1"></i>' + (on ? 'Aktif' : 'Kamera');
}

function startScanner() {
    const container = document.getElementById('scanner-container');
    if (!container || isProcessing) return;

    html5QrCode = new Html5Qrcode("scanner-container");
    const config = {
        fps: 10,
        qrbox: function (viewfinderWidth, viewfinderHeight) {
            const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
            const size = Math.max(200, Math.min(320, Math.floor(minEdge * 0.8)));
            return { width: size, height: size };
        },
        aspectRatio: 1.0,
    };

    html5QrCode.start({ facingMode: "environment" }, config, (decodedText) => {
        const studyGroupId = extractStudyGroupId(decodedText);
        if (!studyGroupId) {
            showResult(false, 'QR tidak dikenali. Pastikan memindai QR Kelas yang benar.');
            return;
        }
        if (html5QrCode) html5QrCode.stop().catch(() => {});
        processScan(studyGroupId);
    }, () => {}).then(() => {
        container.classList.add('scanning');
        setCameraState(true);
        setScannerStatus('📷 Kamera aktif — arahkan ke QR Kelas.', false);
    }).catch(() => {
        setCameraState(false);
        container.classList.remove('scanning');
        container.innerHTML = `<div class="text-center text-muted px-4">
            <i class="ri-camera-off-line d-block mb-2" style="font-size:2rem;color:#cbd5e1"></i>
            <p class="mb-1 fw-medium">Kamera tidak tersedia / akses ditolak</p>
            <small>Gunakan tab <strong>Manual</strong> untuk memilih kelas, atau izinkan akses kamera lalu muat ulang halaman.</small>
        </div>`;
        setScannerStatus('⚠️ Kamera tidak aktif — gunakan tab Manual.', true);
    });
}

function stopScanner() {
    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop().catch(() => {});
    }
}

function setScannerStatus(text, warning) {
    const el = document.getElementById('scanner-status');
    if (el) {
        el.textContent = text;
        el.className = 'text-center small mt-2 ' + (warning ? 'text-warning' : 'text-muted');
    }
}

/**
 * QR payload berisi JSON {study_group_id, url, ...}.
 * Fallback: query param / path URL agar QR format lama tetap terbaca.
 */
function extractStudyGroupId(decodedText) {
    try {
        const payload = JSON.parse(decodedText);
        if (payload && payload.study_group_id) return payload.study_group_id;
    } catch (e) { /* bukan JSON — lanjut fallback URL */ }

    const params = new URLSearchParams((decodedText.split('?')[1] || ''));
    if (params.get('study_group_id')) return params.get('study_group_id');

    const match = decodedText.match(/process\/([0-9a-fA-F-]{36})/);
    return match ? match[1] : null;
}

/* ── Proses scan ────────────────────────────────────────────── */
function triggerScan(studyGroupId) {
    processScan(studyGroupId);
}

async function processScan(studyGroupId) {
    if (isProcessing) return;

    const url = scanUrls[studyGroupId];
    if (!url) {
        showResult(false, 'Anda tidak memiliki jadwal mengajar di kelas ini hari ini.');
        return;
    }

    isProcessing = true;
    showLoadingModal();
    try {
        const response = await fetch(url, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        let data = {};
        try { data = await response.json(); } catch (e) { /* ignore */ }

        if (response.ok && data.success) {
            showResult(true, data.message || 'Absensi berhasil dicatat.', data);
        } else {
            showResult(false, data.message || 'Absensi gagal diproses.', data);
        }
    } catch (err) {
        showResult(false, 'Terjadi kesalahan jaringan. Coba lagi.');
    } finally {
        isProcessing = false;
    }
}

/* ── Modal hasil ────────────────────────────────────────────── */
function buildModal(ok, message, extraHtml) {
    const existing = document.getElementById('scanResultModal');
    if (existing) existing.remove();

    const wrap = document.createElement('div');
    wrap.innerHTML = `
        <div class="modal fade" id="scanResultModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center p-4">
                        <div class="result-icon ${ok ? 'ok' : 'fail'}">
                            <i class="${ok ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill'}"></i>
                        </div>
                        <h5 class="mb-2 ${ok ? 'text-success' : 'text-danger'}">${ok ? 'Berhasil!' : 'Gagal'}</h5>
                        <p class="text-muted mb-2">${message}</p>
                        ${extraHtml || ''}
                        <div class="d-flex gap-2 justify-content-center mt-3">
                            <button type="button" class="btn btn-${ok ? 'success' : 'danger'}" data-bs-dismiss="modal">${ok ? 'Selesai' : 'Tutup'}</button>
                            ${ok ? '' : '<button type="button" class="btn btn-outline-primary" id="btn-retry-scan"><i class="ri-refresh-line me-1"></i>Scan Ulang</button>'}
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    document.body.appendChild(wrap.firstElementChild);

    const el = document.getElementById('scanResultModal');
    const modal = new bootstrap.Modal(el, { backdrop: 'static', keyboard: false });
    el.addEventListener('hidden.bs.modal', () => {
        el.remove();
        if (ok) {
            window.location.reload();
        } else {
            startScanner();
        }
    }, { once: true });

    el.querySelector('#btn-retry-scan')?.addEventListener('click', () => modal.hide());
    modal.show();
    return modal;
}

function showLoadingModal() {
    const existing = document.getElementById('scanResultModal');
    if (existing) existing.remove();

    const wrap = document.createElement('div');
    wrap.innerHTML = `
        <div class="modal fade" id="scanResultModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center p-4">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="mb-0 text-muted">Memproses absensi…</p>
                    </div>
                </div>
            </div>
        </div>`;
    document.body.appendChild(wrap.firstElementChild);
    new bootstrap.Modal(document.getElementById('scanResultModal'), { backdrop: 'static', keyboard: false }).show();
}

function showResult(ok, message, data) {
    let extra = '';
    if (data && data.type === 'check_in' && data.scheduled_start) {
        extra = `<p class="small text-muted mb-0">Jadwal: ${data.scheduled_start?.substring(0,5) ?? ''}${data.scheduled_end ? '–' + data.scheduled_end.substring(0,5) : ''} · ${data.study_group_name ?? ''}</p>`;
        if (data.status === 'terlambat') {
            extra += `<p class="small text-warning mb-0"><i class="ri-time-line me-1"></i>Terlambat ${data.late_minutes ?? 0} menit</p>`;
        }
        extra += `<p class="small text-muted mb-0">${data.action_hint ?? ''}</p>`;
    }
    if (data && data.type === 'check_out' && data.early_leave_minutes > 0) {
        extra = `<p class="small text-warning mb-0"><i class="ri-logout-box-r-line me-1"></i>Keluar ${data.early_leave_minutes} menit lebih awal</p>`;
    }
    if (data && data.already_completed) {
        extra += '<p class="small text-warning mb-0"><i class="ri-alert-line me-1"></i>Absensi kelas ini sudah lengkap hari ini.</p>';
    }
    buildModal(ok, message, extra);
}

/* ── Inisialisasi ───────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    startScanner();

    // Restart kamera saat kembali ke tab kamera
    document.getElementById('camera-tab')?.addEventListener('shown.bs.tab', function () {
        if (html5QrCode && !html5QrCode.isScanning) startScanner();
    });
    document.getElementById('manual-tab')?.addEventListener('shown.bs.tab', stopScanner);

    // Manual: pilih kelas
    document.getElementById('btn-manual-process')?.addEventListener('click', function () {
        const studyGroupId = document.getElementById('manual_schedule')?.value;
        if (!studyGroupId) {
            showResult(false, 'Pilih jadwal/kelas terlebih dahulu.');
            return;
        }
        processScan(studyGroupId);
    });

    // Manual: tempel token/URL QR
    document.getElementById('btn-manual-token')?.addEventListener('click', function () {
        const raw = document.getElementById('manual_token')?.value?.trim();
        if (!raw) {
            showResult(false, 'Tempel URL atau token QR terlebih dahulu.');
            return;
        }
        const studyGroupId = extractStudyGroupId(raw);
        if (!studyGroupId || !scanUrls[studyGroupId]) {
            showResult(false, 'QR tidak dikenali atau bukan kelas yang Anda ajar hari ini.');
            return;
        }
        processScan(studyGroupId);
    });

    // Responsif: restart scanner saat resize / rotate
    let resizeTimer = null;
    window.addEventListener('resize', function () {
        if (resizeTimer) clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => startScanner()).catch(() => {});
            }
        }, 350);
    });
    window.addEventListener('orientationchange', function () {
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().then(() => setTimeout(startScanner, 400)).catch(() => {});
        }
    });
});
</script>
@endpush
