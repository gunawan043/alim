{{-- Teacher QR Scan Attendance --}}
@extends('layouts.master')
@section('title') Absensi Guru via QR @endsection

@push('css')
<style>
    .stat-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
        transition: transform .2s, box-shadow .2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.1); }
    .stat-card .card-body { padding: .85rem 1rem; }
    .stat-icon {
        width: 40px; height: 40px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    .stat-value { font-size: 1.35rem; font-weight: 700; line-height: 1.2; }
    .stat-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .5px; margin: 0; }
    .scanner-wrapper {
        border: 2px dashed #cbd5e1; border-radius: 12px;
        min-height: 280px; display: flex; align-items: center;
        justify-content: center; background: #f8fafc;
        transition: border-color .2s;
    }
    .scanner-wrapper.scanning { border-color: #6366f1; border-style: solid; }
    .scanner-wrapper video { border-radius: 8px; }
    .schedule-item {
        border: 1px solid #e2e8f0; border-radius: 8px;
        padding: 10px 14px; margin-bottom: 8px;
        transition: background .15s;
    }
    .schedule-item:last-child { margin-bottom: 0; }
    .schedule-item:hover { background: #f8fafc; }
    .schedule-item.status-hadir { border-left: 3px solid #22c55e; background: #f0fdf4; }
    .schedule-item.status-late { border-left: 3px solid #f59e0b; background: #fffbeb; }
    .schedule-item.status-checkout { border-left: 3px solid #3b82f6; background: #eff6ff; }
    .schedule-item.status-pending { border-left: 3px solid #94a3b8; }
    .time-pill {
        font-family: 'SF Mono', Monaco, monospace;
        font-size: .78rem; background: #f1f5f9;
        padding: 2px 8px; border-radius: 4px; white-space: nowrap;
    }
    .recent-item {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 0; border-bottom: 1px solid #f1f5f9;
    }
    .recent-item:last-child { border-bottom: none; }
    .recent-dot {
        width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
    }
    .nav-pills-custom .nav-link {
        color: #64748b; font-size: .82rem; font-weight: 500;
        padding: 6px 14px; border-radius: 6px;
        transition: all .15s;
    }
    .nav-pills-custom .nav-link.active {
        background: #6366f1; color: #fff;
    }
    .nav-pills-custom .nav-link:hover:not(.active) { background: #f1f5f9; }
    .class-badge {
        font-size: .75rem; padding: 2px 8px;
        border-radius: 4px; font-weight: 600;
    }
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Guru @endslot
    @slot('li_2') Scan QR @endslot
    @slot('title') Absensi Guru via QR @endslot
@endcomponent

{{-- Session messages --}}
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Stats Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#eff6ff;color:#3b82f6">
                    <i class="ri-calendar-line"></i>
                </div>
                <div>
                    <p class="stat-label text-muted mb-1">Total Jam</p>
                    <h4 class="stat-value mb-0 text-primary">{{ $stats['total'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#f0fdf4;color:#22c55e">
                    <i class="ri-checkbox-circle-fill"></i>
                </div>
                <div>
                    <p class="stat-label text-muted mb-1">Sudah Absen</p>
                    <h4 class="stat-value mb-0 text-success">{{ $stats['checked_in'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#eff6ff;color:#6366f1">
                    <i class="ri-logout-box-r-line"></i>
                </div>
                <div>
                    <p class="stat-label text-muted mb-1">Sudah Keluar</p>
                    <h4 class="stat-value mb-0" style="color:#6366f1">{{ $stats['checked_out'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fffbeb;color:#f59e0b">
                    <i class="ri-alert-fill"></i>
                </div>
                <div>
                    <p class="stat-label text-muted mb-1">Terlambat</p>
                    <h4 class="stat-value mb-0 text-warning">{{ $stats['late'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#f8fafc;color:#64748b">
                    <i class="ri-hourglass-line"></i>
                </div>
                <div>
                    <p class="stat-label text-muted mb-1">Belum Absen</p>
                    <h4 class="stat-value mb-0 text-muted">{{ $stats['pending'] }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Scanner --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div style="width:36px;height:36px;background:#6366f118;border-radius:8px;display:flex;align-items:center;justify-content:center">
                        <i class="ri-qr-scan-2-line" style="color:#6366f1;font-size:1rem"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0 fw-bold">Scan QR Absensi</h5>
                        <p class="text-muted mb-0" style="font-size:.78rem">Arahkan kamera ke QR code di kelas</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="scanner-container" class="scanner-wrapper">
                    <div class="text-center text-muted px-3">
                        <i class="ri-camera-line" style="font-size:2.5rem;color:#cbd5e1"></i>
                        <p class="mt-2 mb-1 fw-medium">Klik tombol di bawah untuk memulai scan</p>
                        <small class="text-muted">Izinkan akses kamera saat diminta browser</small>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button type="button" class="btn btn-primary" id="btn-start-scan" onclick="startScanner()">
                        <i class="ri-camera-fill me-1"></i>Mulai Scan
                    </button>
                    <button type="button" class="btn btn-light" id="btn-stop-scan" onclick="stopScanner()" style="display:none">
                        <i class="ri-stop-fill me-1"></i>Stop
                    </button>
                </div>
                <div class="text-center mt-2">
                    <small class="text-muted" style="font-size:.75rem">
                        <i class="ri-information-line me-1"></i>
                        Scanner otomatis mendeteksi check-in atau check-out
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- Today's Schedule --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:36px;height:36px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center">
                            <i class="ri-calendar-check-line" style="color:#22c55e;font-size:1rem"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0 fw-bold">Jadwal Hari Ini</h5>
                            <p class="text-muted mb-0" style="font-size:.78rem">{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</p>
                        </div>
                    </div>
                    <span class="badge rounded-pill" style="background:#eff6ff;color:#6366f1;font-size:.72rem">
                        {{ $stats['pending'] }} belum absen
                    </span>
                </div>
            </div>
            <div class="card-body p-3" style="max-height:420px;overflow-y:auto">
                @forelse($schedules as $schedule)
                    @php
                        $att = $attendances->get($schedule->id);
                        $startTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i');
                        $endTime = \Carbon\Carbon::parse($schedule->end_time)->format('H:i');
                        $statusClass = 'status-pending';
                        $statusText = 'Belum Absen';
                        $statusColor = '#94a3b8';
                        if ($att) {
                            if ($att->actual_time_in && $att->actual_time_out) {
                                $statusClass = 'status-checkout';
                                $statusText = $att->status_keluar === 'keluar_cepat' ? 'Pulang Cepat' : 'Selesai';
                                $statusColor = '#3b82f6';
                            } elseif ($att->actual_time_in && !$att->actual_time_out) {
                                $statusClass = $att->status_masuk === 'terlambat' ? 'status-late' : 'status-hadir';
                                $statusText = $att->status_masuk === 'terlambat' ? 'Terlambat · Masuk' : 'Sudah Absen';
                                $statusColor = $att->status_masuk === 'terlambat' ? '#f59e0b' : '#22c55e';
                            }
                        }
                    @endphp
                    <div class="schedule-item {{ $statusClass }}">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <span class="fw-semibold d-block" style="font-size:.85rem">
                                    {{ $schedule->studyGroup?->name ?? '-' }}
                                </span>
                                <small class="text-muted" style="font-size:.73rem">
                                    {{ $schedule->studyGroup?->gradeLevel?->name ?? '' }}
                                    {{ $schedule->subject?->name ? ' · ' . $schedule->subject->name : '' }}
                                </small>
                            </div>
                            <span class="time-pill">{{ $startTime }} - {{ $endTime }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2" style="font-size:.75rem">
                            <span class="recent-dot" style="background:{{ $statusColor }}"></span>
                            <span style="color:{{ $statusColor }};font-weight:500">{{ $statusText }}</span>
                            @if($att && $att->actual_time_in)
                                <span class="text-muted ms-auto" style="font-family:monospace;font-size:.72rem">
                                    {{ \Carbon\Carbon::parse($att->actual_time_in)->format('H:i') }}
                                </span>
                            @endif
                            @if($att && $att->late_minutes > 0)
                                <span class="badge bg-warning text-dark" style="font-size:.68rem">+{{ $att->late_minutes }}m</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="ri-calendar-off-line" style="font-size:2rem;color:#cbd5e1"></i>
                        <p class="mt-2 mb-0">Tidak ada jadwal mengajar hari ini</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Tabs: Recent Records --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
        <ul class="nav nav-pills nav-pills-custom gap-1" id="scanTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabRecent">
                    <i class="ri-history-line me-1"></i>Riwayat Terbaru
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabAllHistory">
                    <i class="ri-file-list-3-line me-1"></i>Semua Riwayat
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tabRecent">
                @if($recentRecords->count() > 0)
                    @foreach($recentRecords as $rec)
                        <div class="recent-item">
                            <span class="recent-dot" style="background:{{ $rec->status_masuk === 'terlambat' ? '#f59e0b' : '#22c55e' }}"></span>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-medium" style="font-size:.82rem">
                                        {{ $rec->jadwalKbm?->studyGroup?->name ?? '-' }}
                                    </span>
                                    <span class="text-muted" style="font-size:.73rem">
                                        {{ $rec->jadwalKbm?->subject?->name ?? '' }}
                                    </span>
                                    <span class="badge {{ $rec->status_masuk === 'terlambat' ? 'bg-warning text-dark' : 'bg-success' }}" style="font-size:.68rem">
                                        {{ $rec->status_masuk === 'terlambat' ? 'Terlambat' : 'Hadir' }}
                                    </span>
                                </div>
                                <small class="text-muted" style="font-size:.72rem">
                                    {{ $rec->attendance_date?->format('d M Y') }}
                                    · Masuk: {{ $rec->actual_time_in ? \Carbon\Carbon::parse($rec->actual_time_in)->format('H:i') : '-' }}
                                    @if($rec->actual_time_out)
                                        · Keluar: {{ \Carbon\Carbon::parse($rec->actual_time_out)->format('H:i') }}
                                    @endif
                                    @if($rec->late_minutes > 0)
                                        · Terlambat {{ $rec->late_minutes }} menit
                                    @endif
                                </small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="ri-inbox-line" style="font-size:1.5rem;color:#cbd5e1"></i>
                        <p class="mt-2 mb-0 small">Belum ada riwayat absensi</p>
                    </div>
                @endif
            </div>
            <div class="tab-pane fade" id="tabAllHistory">
                <div class="text-center py-3">
                    <a href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-primary">
                        <i class="ri-arrow-right-line me-1"></i>Lihat Semua Riwayat
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode = null;
let isScanning = false;
const userId = {{ Js::from($userId) }};

function startScanner() {
    if (isScanning) return;
    const container = document.getElementById('scanner-container');
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Memulai kamera...</p></div>';
    document.getElementById('btn-start-scan').style.display = 'none';
    document.getElementById('btn-stop-scan').style.display = '';

    html5QrCode = new Html5Qrcode("scanner-container");
    const config = { fps: 10, qrbox: { width: 280, height: 280 } };

    html5QrCode.start(
        { facingMode: "environment" },
        config,
        (decodedText) => {
            const params = new URLSearchParams(decodedText.split('?')[1]);
            const studyGroupId = params.get('study_group_id');
            if (!studyGroupId) {
                showResult(false, 'Gagal membaca ID kelas dari QR.');
                return;
            }
            html5QrCode.stop().then(() => {
                isScanning = false;
                processScan(studyGroupId);
            });
        },
        () => {}
    ).then(() => {
        isScanning = true;
        container.classList.add('scanning');
    }).catch(err => {
        container.innerHTML = `<div class="text-center text-danger px-3">
            <i class="ri-error-warning-line fs-4 d-block mb-2"></i>
            <p class="mb-1">Kamera gagal diakses</p>
            <small class="text-muted">${err.message || err}</small>
        </div>`;
        document.getElementById('btn-start-scan').style.display = '';
        document.getElementById('btn-stop-scan').style.display = 'none';
    });
}

function stopScanner() {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(() => {
            isScanning = false;
            const container = document.getElementById('scanner-container');
            container.classList.remove('scanning');
            container.innerHTML = `<div class="text-center text-muted px-3">
                <i class="ri-camera-line" style="font-size:2rem;color:#cbd5e1"></i>
                <p class="mt-2 mb-0">Kamera dihentikan</p>
            </div>`;
            document.getElementById('btn-start-scan').style.display = '';
            document.getElementById('btn-stop-scan').style.display = 'none';
        });
    }
}

function processScan(studyGroupId) {
    const container = document.getElementById('scanner-container');
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Memproses...</p></div>';
    const url = `{{ route('user.teacher-qr.scan.process', ['study_group_id' => ':id', 'userId' => $userId]) }}`.replace(':id', studyGroupId);
    fetch(url, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => res.json())
        .then(data => {
            showResult(data.success, data.message, data);
        })
        .catch(() => showResult(false, 'Kesalahan jaringan, coba lagi'));
}

function vibrate(pattern) {
    if ('vibrate' in navigator) navigator.vibrate(pattern);
}

function showResult(success, message, data = null) {
    const container = document.getElementById('scanner-container');
    const color = success ? '#22c55e' : '#ef4444';
    const icon = success ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill';
    let extra = '';
    if (data && data.action_hint) {
        extra = `<p class="text-muted small mt-2 mb-2">${data.action_hint}</p>`;
    }
    if (data && data.already_completed) {
        extra += `<p class="text-warning small mt-1"><i class="ri-alert-line me-1"></i>Absensi hari ini sudah lengkap.</p>`;
    }
    container.innerHTML = `
        <div class="text-center px-3">
            <i class="${icon} mb-2" style="font-size:2.5rem;color:${color}"></i>
            <h6 class="${success ? 'text-success' : 'text-danger'} fw-bold">${message}</h6>
            ${extra}
            <div class="d-flex gap-2 justify-content-center mt-2">
                <button class="btn btn-sm btn-outline-primary" onclick="startScanner()">
                    <i class="ri-refresh-line me-1"></i>Scan Lagi
                </button>
                <a href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ri-history-line me-1"></i>Riwayat
                </a>
            </div>
        </div>`;
    container.classList.remove('scanning');
    document.getElementById('btn-start-scan').style.display = '';
    document.getElementById('btn-stop-scan').style.display = 'none';
    vibrate(success ? [100, 50, 100] : [200, 100, 200]);
}
</script>
@endpush
