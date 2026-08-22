{{-- Teacher attendance Waka dashboard --}}
@extends('layouts.master')
@section('title') Dashboard Waka Absensi @endsection

@push('css')
<style>
.stat-card{transition:all .25s ease}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.realtime-indicator{animation:blink 1.5s infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:0.5}}
.attendance-bar{height:8px;border-radius:4px;background:linear-gradient(90deg,#22c55e 0%,#22c55e var(--percent),#e2e8f0 var(--percent),#e2e8f0 100%)}
.timeline-item{position:relative;padding-left:30px;margin-bottom:16px}
.timeline-item::before{content:'';position:absolute;left:8px;top:8px;width:12px;height:12px;border-radius:50%;background:#6366f1}
.timeline-item::after{content:'';position:absolute;left:13px;top:20px;width:2px;height:calc(100% - 12px);background:#e2e8f0}
.timeline-item:last-child::after{display:none}
.timeline-item.success::before{background:#22c55e}
.timeline-item.late::before{background:#f59e0b}
.timeline-item.fail::before{background:#ef4444}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Guru @endslot
    @slot('li_2') Dashboard Waka @endslot
    @slot('title') Dashboard Realtime Absensi Guru @endslot
@endcomponent

{{-- Flash messages --}}
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

{{-- Live status indicator --}}
<div class="alert alert-info d-flex align-items-center gap-2 mb-4">
    <i class="ri-live-line realtime-indicator fs-5"></i>
    <span>Live update — {{ now()->format('H:i:s') }}</span>
    <div class="ms-auto">
        <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
            <i class="ri-refresh-line"></i> Refresh
        </button>
    </div>
</div>

@php
    // Build stats from data
    $expected = $schedules->count();
    $checked = 0;
    $late = 0;
    $absent = 0;
    $checkedOut = 0;
    foreach ($schedules as $s) {
        $key = $s->teacher_id . '|' . $s->id;
        if (isset($attendances[$key])) {
            $a = $attendances[$key];
            if ($a->actual_time_in) {
                $checked++;
                if ($a->status_masuk === 'terlambat') $late++;
                if ($a->actual_time_out) $checkedOut++;
            } else {
                $absent++;
            }
        } else {
            $absent++;
        }
    }
    $rate = $expected > 0 ? round(($checked / $expected) * 100) : 0;
@endphp

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl">
        <div class="card stat-card" style="border-left:3px solid #6366f1">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-indigo-subtle rounded-3 fs-2"><i class="ri-user-line text-primary"></i></span></div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px">Jadwal</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $expected }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card stat-card" style="border-left:3px solid #22c55e">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded-3 fs-2"><i class="ri-checkbox-circle-line text-success"></i></span></div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px">Hadir</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $checked }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card stat-card" style="border-left:3px solid #f59e0b">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded-3 fs-2"><i class="ri-time-line text-warning"></i></span></div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px">Terlambat</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $late }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card stat-card" style="border-left:3px solid #ef4444">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-danger-subtle rounded-3 fs-2"><i class="ri-close-circle-line text-danger"></i></span></div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px">Absen</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $absent }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card stat-card" style="border-left:3px solid #06b6d4">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-cyan-subtle rounded-3 fs-2"><i class="ri-percent-line text-cyan"></i></span></div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px">Presentase</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $rate }}%</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Class attendance progress --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
                <i class="ri-bar-chart-box-line"></i>
                <h5 class="mb-0">Progres Absen per Kelas</h5>
            </div>
            <div class="card-body">
                @php
                    $classStats = [];
                    foreach ($schedules as $s) {
                        $gid = $s->study_group_id;
                        if (!isset($classStats[$gid])) $classStats[$gid] = ['name' => $s->studyGroup?->name ?? 'Unknown', 'total' => 0, 'checked' => 0];
                        $classStats[$gid]['total']++;
                        $key = $s->teacher_id . '|' . $s->id;
                        if (isset($attendances[$key]) && $attendances[$key]->actual_time_in) $classStats[$gid]['checked']++;
                    }
                @endphp
                @forelse($classStats as $gid => $stat)
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-medium small">{{ $stat['name'] }}</span>
                        <span class="text-muted small">{{ $stat['checked'] }}/{{ $stat['total'] }}</span>
                    </div>
                    <div class="attendance-bar" style="--percent: {{ $stat['total'] > 0 ? round(($stat['checked']/$stat['total'])*100) : 0 }}%"></div>
                </div>
                @empty
                <p class="text-muted small text-center py-3">Belum ada data kelas</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent check-in activity --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-success text-white d-flex align-items-center gap-2">
                <i class="ri-timer-flash-line"></i>
                <h5 class="mb-0">Aktivitas Terakhir</h5>
            </div>
            <div class="card-body" style="max-height:400px;overflow-y:auto">
                @php $recentChecks = $attendances->values()->sortByDesc(fn($a) => $a->actual_time_in); @endphp
                @forelse($recentChecks->take(10) as $check)
                <div class="timeline-item {{ $check->status_masuk === 'terlambat' ? 'late' : 'success' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-medium">{{ $check->teacher?->name ?? 'Guru tidak dikenal' }}</span>
                            <span class="text-muted small ms-2">{{ $check->jadwalKbm?->studyGroup?->name ?? '' }}</span>
                        </div>
                        <span class="badge {{ $check->status_masuk === 'terlambat' ? 'bg-warning text-dark' : 'bg-success' }}">
                            {{ $check->status_masuk === 'terlambat' ? 'Terlambat' : 'Tepat Waktu' }}
                        </span>
                    </div>
                    <small class="text-muted">{{ $check->actual_time_in }} · {{ $check->late_minutes }} menit terlambat</small>
                </div>
                @empty
                <div class="text-center text-muted py-5">
                    <i class="ri-inbox-line fs-1 mb-2 d-block"></i>
                    Belum ada data absensi
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Pending items --}}
@if($notCheckedOut->count() > 0 || $notPresent->count() > 0)
<div class="card mt-4 border-warning">
    <div class="card-header bg-warning text-dark d-flex align-items-center gap-2">
        <i class="ri-alarm-warning-line"></i>
        <h5 class="mb-0">Perlu Ditindaklanjuti ({{ $notCheckedOut->count() + $notPresent->count() }})</h5>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            {{-- Sudah absen tapi belum keluar --}}
            @foreach($notCheckedOut as $item)
            <div class="list-group-item d-flex align-items-center gap-3">
                <span class="badge bg-warning text-dark">Belum Keluar</span>
                <div class="flex-grow-1">
                    <span class="fw-medium">{{ $item->teacher?->name ?? 'Guru tidak dikenal' }}</span>
                    <span class="text-muted small ms-2">{{ $item->jadwalKbm?->studyGroup?->name ?? '' }}</span>
                </div>
                <small class="text-muted">Masuk: {{ $item->actual_time_in }}</small>
                <small class="text-muted">Seharusnya keluar: {{ $item->jadwalKbm?->end_time ?? '' }}</small>
                <button type="button" class="btn btn-sm btn-outline-warning py-0"
                    data-bs-toggle="modal" data-bs-target="#manualCheckoutModal"
                    data-attendance-id="{{ $item->id }}"
                    data-teacher-name="{{ $item->teacher?->name ?? '' }}"
                    data-checkout-time="{{ $item->jadwalKbm?->end_time ?? now()->format('H:i') }}"
                    title="Check-out manual">
                    <i class="ri-logout-box-r-line"></i> Checkout
                </button>
            </div>
            @endforeach
            {{-- Belum absen sama sekali --}}
            @foreach($notPresent as $jadwal)
            <div class="list-group-item d-flex align-items-center gap-3">
                <span class="badge bg-danger">Belum Absen</span>
                <div class="flex-grow-1">
                    <span class="fw-medium">{{ $jadwal->teacher?->name ?? 'Guru tidak dikenal' }}</span>
                    <span class="text-muted small ms-2">{{ $jadwal->studyGroup?->name ?? '' }} · {{ $jadwal->subject?->name ?? '' }}</span>
                </div>
                <small class="text-muted">{{ $jadwal->start_time }} - {{ $jadwal->end_time }}</small>
                <button type="button" class="btn btn-sm btn-outline-primary py-0"
                    data-bs-toggle="modal" data-bs-target="#manualCheckinModal"
                    data-teacher-id="{{ $jadwal->teacher_id }}"
                    data-jadwal-id="{{ $jadwal->id }}"
                    data-start-time="{{ $jadwal->start_time }}"
                    title="Check-in manual">
                    <i class="ri-login-box-line"></i>
                </button>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Manual Check-in Modal --}}
<div class="modal fade" id="manualCheckinModal" tabindex="-1" aria-labelledby="manualCheckinModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="manualCheckinModalLabel">
                    <i class="ri-keyboard-line me-1"></i>Check-in Manual (Waka)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('user.teacher-qr.manual-checkin') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Guru <span class="text-danger">*</span></label>
                        <select name="teacher_id" id="mc_teacher_id" class="form-select form-select-sm" required>
                            <option value="">— Pilih guru —</option>
                            @foreach($schedules->pluck('teacher')->unique('id') as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Jadwal / Kelas <span class="text-danger">*</span></label>
                        <select name="jadwal_kbm_id" id="mc_jadwal_kbm_id" class="form-select form-select-sm" required>
                            <option value="">— Pilih jadwal —</option>
                            @foreach($schedules as $j)
                                <option value="{{ $j->id }}" data-teacher="{{ $j->teacher_id }}" data-start="{{ $j->start_time }}">
                                    {{ $j->teacher?->name ?? '?' }} · {{ $j->studyGroup?->name ?? '' }} · {{ $j->subject?->name ?? '' }} ({{ $j->start_time }})
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
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ri-login-box-line me-1"></i>Simpan Check-in
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Manual Checkout Modal --}}
<div class="modal fade" id="manualCheckoutModal" tabindex="-1" aria-labelledby="manualCheckoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="manualCheckoutModalLabel">
                    <i class="ri-logout-box-r-line me-1"></i>Check-out Manual (Waka)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('user.teacher-qr.manual-checkout') }}">
                @csrf
                <input type="hidden" name="attendance_id" id="mco_attendance_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Guru</label>
                        <input type="text" id="mco_teacher_name" class="form-control form-control-sm" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Waktu Check-out <span class="text-danger">*</span></label>
                        <input type="time" name="checkout_time" id="mco_checkout_time" class="form-control form-control-sm" value="{{ now()->format('H:i') }}" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-medium">Catatan</label>
                        <textarea name="notes" id="mco_notes" class="form-control form-control-sm" rows="2" placeholder="Opsional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="ri-logout-box-r-line me-1"></i>Simpan Check-out
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.17.1/dist/echo.iife.js"></script>
<script>
(function () {
    const schoolId = {{ $schoolId ?? 0 }};
    if (!schoolId) {
        // No school context — fall back to polling
        setInterval(() => { location.reload(); }, 30000);
        return;
    }

    const pusherKey = '{{ config('broadcasting.connections.pusher.key', '') }}';
    if (!pusherKey) {
        // Pusher key not set — fall back to polling
        setInterval(() => { location.reload(); }, 30000);
        return;
    }

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: '{{ config('broadcasting.connections.pusher.options.cluster', 'ap1') }}',
        forceTLS: true,
        encrypted: true,
    });

    // Join per-school channel for teacher QR attendance events
    window.Echo.channel(`waka-teacher-absensi.${schoolId}`)
        .listen('.teacher.qr.scanned', (e) => {
            showLiveToast('✅', `${e.teacherName} masuk ${e.studyGroupName} (${e.status})`, 'success');
            // Auto-reload stats without full page reload would require AJAX
            // For now, just toast — a gentle reload happens on idle
        })
        .listen('.teacher.checked.out', (e) => {
            showLiveToast('🕓', `${e.teacherName} keluar dari ${e.studyGroupName}`, 'info');
        });

    function showLiveToast(icon, message, type = 'info') {
        const id = 'toast-' + Date.now();
        const bg = type === 'success' ? 'bg-success' : 'bg-info';
        const html = `<div id="${id}" class="toast align-items-center ${bg} text-white border-0 position-fixed bottom-0 end-0 m-3" role="alert" style="z-index:9999">
            <div class="d-flex">
                <div class="toast-body fw-medium">${icon} ${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', html);
        const el = document.getElementById(id);
        const toast = new bootstrap.Toast(el, { delay: 4000 });
        toast.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }

    // Prefill manual check-in modal when triggered from a "Belum Absen" row
    const mcModal = document.getElementById('manualCheckinModal');
    if (mcModal) {
        mcModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            const teacherId = btn.getAttribute('data-teacher-id');
            const jadwalId = btn.getAttribute('data-jadwal-id');
            const startTime = btn.getAttribute('data-start-time') || '{{ now()->format('H:i') }}';
            document.getElementById('mc_teacher_id').value = teacherId || '';
            document.getElementById('mc_jadwal_kbm_id').value = jadwalId || '';
            document.getElementById('mc_checkin_time').value = startTime;
        });
    }

    // Prefill manual checkout modal when triggered from "Belum Keluar" row
    const mcoModal = document.getElementById('manualCheckoutModal');
    if (mcoModal) {
        mcoModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            document.getElementById('mco_attendance_id').value = btn.getAttribute('data-attendance-id') || '';
            document.getElementById('mco_teacher_name').value = btn.getAttribute('data-teacher-name') || '';
            document.getElementById('mco_checkout_time').value = btn.getAttribute('data-checkout-time') || '{{ now()->format('H:i') }}';
        });
    }
})();
</script>
@endpush
