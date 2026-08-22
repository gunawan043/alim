{{-- Teacher QR scan attendance --}}
@extends('layouts.master')
@section('title') Absensi Guru @endsection

@push('css')
<style>
.schedules-list{border-radius:8px;overflow:hidden}
.schedule-item{padding:12px 16px;border-bottom:1px solid #e2e8f0;transition:background .15s}
.schedule-item:last-child{border-bottom:none}
.schedule-item:hover{background:#f8fafc}
.schedule-item.checked-in{background:#f0fdf4;border-left:3px solid #22c55e}
.schedule-item.checked-out{background:#eff6ff;border-left:3px solid #3b82f6}
.schedule-item.late{background:#fffbeb;border-left:3px solid #f59e0b}
.schedule-item.not-started{background:#fafafa;border-left:3px solid #94a3b8}
.time-badge{font-family:'SF Mono',Monaco,monospace;font-size:.8rem}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Guru @endslot
    @slot('li_2') Scan QR @endslot
    @slot('title') Sistem Absensi Guru via QR @endslot
@endcomponent

<div class="row g-4">
    {{-- Scan area --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
                <i class="ri-qr-scan-2-line"></i>
                <h5 class="mb-0">Scan QR Absen</h5>
            </div>
            <div class="card-body">
                <div id="scanner-container" style="border:3px dashed #6366f1;border-radius:12px;min-height:300px;display:flex;align-items:center;justify-content:center;background:#f8fafc">
                    <div class="text-center text-muted">
                        <i class="ri-camera-line fs-1 mb-2"></i>
                        <p class="mb-1">Klik tombol "Mulai Scan", izinkan akses kamera</p>
                        <small>Arahkan kamera ke QR code yang ditempel di kelas</small>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button type="button" class="btn btn-primary" onclick="startScanner()">
                        <i class="ri-camera-fill me-1"></i>Mulai Scan
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="stopScanner()">
                        <i class="ri-stop-fill me-1"></i>Stop
                    </button>
                </div>
                <div class="mt-2 text-center text-muted" style="font-size:.8rem">
                    <i class="ri-information-line me-1"></i>Mendukung 3 status: datang awal, terlambat, atau tepat waktu
                </div>

                {{-- Session error/success --}}
                @if(session('error'))
                    <div class="alert alert-danger mt-3">
                        <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
                    </div>
                @endif
                @if(session('success'))
                    <div class="alert alert-success mt-3">
                        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Today's schedule --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-success text-white d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-calendar-check-line"></i>
                    <h5 class="mb-0">Jadwal Hari Ini</h5>
                </div>
                <span class="badge bg-white text-success">{{ $schedules->where(fn($s) => !isset($attendances[$s->id]))->count() }} jam belum absen</span>
            </div>
            <div class="card-body p-0 schedules-list" style="max-height:500px;overflow-y:auto">
                @forelse($schedules as $schedule)
                    @php
                        $att = $attendances->get($schedule->id);
                        $statusClass = '';
                        $statusBadge = '';
                        $statusIcon = '';
                        if ($att) {
                            if ($att->actual_time_in && !$att->actual_time_out) {
                                $statusClass = 'checked-in';
                                $statusBadge = 'Sudah absen · belum keluar';
                                $statusIcon = 'ri-checkbox-circle-fill text-success';
                            } elseif ($att->actual_time_in && $att->actual_time_out) {
                                $statusClass = 'checked-out';
                                $statusBadge = $att->status_keluar === 'keluar_cepat' ? 'Selesai · pulang cepat' : 'Selesai';
                                $statusIcon = 'ri-check-double-fill text-primary';
                            } else {
                                $statusClass = 'late';
                                $statusBadge = 'Terlambat';
                                $statusIcon = 'ri-alert-fill text-warning';
                            }
                        } else {
                            $statusClass = 'not-started';
                            $startTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i');
                            $statusBadge = "Belum absen · mulai {$startTime}";
                            $statusIcon = 'ri-time-line text-muted';
                        }
                    @endphp
                    <div class="schedule-item {{ $statusClass }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="fw-medium d-block">{{ $schedule->studyGroup?->name ?? 'Kelas tidak dikenal' }}</span>
                                <small class="text-muted">
                                    {{ $schedule->studyGroup?->gradeLevel?->name ?? '' }}
                                    {{ $schedule->subject?->name ? ' · ' . $schedule->subject->name : '' }}
                                </small>
                            </div>
                            <span class="time-badge bg-light px-2 py-1 rounded">
                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <i class="{{ $statusIcon }}"></i>
                            <small class="text-muted">{{ $statusBadge }}</small>
                            @if($att)
                                @if($att->late_minutes > 0)
                                    <span class="badge bg-warning text-dark ms-auto">+{{ $att->late_minutes }} mnt</span>
                                @endif
                                @if($att->actual_time_in)
                                    <small class="text-muted ms-auto">Masuk: {{ $att->actual_time_in }}</small>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="ri-calendar-off-line fs-1 mb-2 d-block"></i>
                        Tidak ada jadwal hari ini
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Manual check-in entry — Waka only --}}
@if(canPermission('teacher-attendance_manual'))
<div class="card mt-4">
    <div class="card-header bg-light d-flex align-items-center gap-2">
        <i class="ri-keyboard-line"></i>
        <h5 class="mb-0">Absen Manual (hanya untuk Waka)</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label small fw-medium text-muted">Pilih Kelas</label>
                <select id="manual-study-group" class="form-select">
                    <option value="">— Pilih kelas —</option>
                    @foreach($schedules as $s)
                        <option value="{{ $s->study_group_id }}">{{ $s->studyGroup?->name ?? '' }} ({{ $s->studyGroup?->gradeLevel?->name ?? '' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100" onclick="doManualCheckin()">
                    <i class="ri-login-box-line me-1"></i>Absen Manual
                </button>
            </div>
            <div class="col-md-3">
                <small class="text-muted">
                    <i class="ri-shield-check-line me-1"></i>
                    Waktu absen: {{ now()->format('H:i:s') }}
                </small>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode = null;
let isScanning = false;

function startScanner() {
    if (isScanning) return;
    const container = document.getElementById('scanner-container');
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memulai kamera...</p></div>';

    html5QrCode = new Html5Qrcode("scanner-container");
    const config = { fps: 10, qrbox: { width: 300, height: 300 } };

    html5QrCode.start(
        { facingMode: "environment" },
        config,
        (decodedText) => {
            // QR content should be the signed URL — extract study_group_id from it
            const params = new URLSearchParams(decodedText.split('?')[1]);
            const studyGroupId = params.get('study_group_id');
            if (!studyGroupId) {
                showResult(false, 'Gagal membaca ID kelas dari QR, gunakan input manual.');
                return;
            }
            html5QrCode.stop().then(() => {
                isScanning = false;
                processScan(studyGroupId);
            });
        },
        () => {} // ignore scan errors
    ).then(() => {
        isScanning = true;
        container.classList.add('scanning');
    }).catch(err => {
        container.innerHTML = `<div class="text-center text-danger"><i class="ri-error-warning-line fs-1"></i><p class="mt-2">Kamera gagal diakses</p><small>${err}</small></div>`;
    });
}

function stopScanner() {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(() => {
            isScanning = false;
            document.getElementById('scanner-container').classList.remove('scanning');
            document.getElementById('scanner-container').innerHTML =
                '<div class="text-center text-muted"><i class="ri-camera-line fs-1 mb-2"></i><p>Kamera dihentikan</p></div>';
        });
    }
}

function processScan(studyGroupId) {
    const container = document.getElementById('scanner-container');
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memproses...</p></div>';

    // Rebuild the signed URL
    const url = `{{ route('user.teacher-qr.scan.process', ['study_group_id' => ':id', 'userId' => $userId]) }}`.replace(':id', studyGroupId);

    fetch(url, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showResult(true, data.message, data);
        } else {
            showResult(false, data.message);
        }
    })
    .catch(() => {
        showResult(false, 'Kesalahan jaringan, coba lagi');
    });
}

function vibrate(pattern) {
    if ('vibrate' in navigator) {
        navigator.vibrate(pattern);
    }
}

function showResult(success, message, data = null) {
    const container = document.getElementById('scanner-container');
    const color = success ? '#22c55e' : '#ef4444';
    const icon = success ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill';
    let extra = '';
    if (data && data.action_hint) {
        extra = `<p class="text-muted small mt-2">${data.action_hint}</p>`;
    }
    if (data && data.already_completed) {
        extra += `<p class="text-warning small mt-2"><i class="ri-alert-line"></i> Absensi hari ini sudah lengkap. Kembali besok.</p>`;
    }
    container.innerHTML = `
        <div class="text-center">
            <i class="${icon} fs-1 mb-2" style="color:${color}"></i>
            <h5 class="${success ? 'text-success' : 'text-danger'}">${message}</h5>
            ${extra}
            @if($userId !== auth()->id())
            <a href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" class="btn btn-outline-secondary btn-sm mt-2 me-1">
                <i class="ri-history-line me-1"></i>Riwayat
            </a>
            @endif
            <button class="btn btn-outline-primary btn-sm mt-3" onclick="startScanner()">
                <i class="ri-refresh-line me-1"></i>Lanjut Scan
            </button>
        </div>`;
    if (!success) {
        setTimeout(() => { container.classList.remove('scanning'); }, 1000);
    }
    vibrate(success ? [100, 50, 100] : [200, 100, 200]);
}

function doManualCheckin() {
    const studyGroupId = document.getElementById('manual-study-group').value;
    if (!studyGroupId) { alert('Pilih kelas terlebih dahulu'); return; }
    // Redirect to scan process with the study group ID
    window.location.href = `{{ route('user.teacher-qr.scan.process', ['study_group_id' => ':id', 'userId' => $userId]) }}`.replace(':id', studyGroupId);
}
</script>
@endpush
