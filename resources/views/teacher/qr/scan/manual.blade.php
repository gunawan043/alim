{{-- Teacher QR Manual Check-in --}}
@extends('layouts.master')
@section('title') Absen Manual Guru @endsection

@push('css')
<style>
    .stat-card {
        border: none; border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    .stat-card .card-body { padding: .85rem 1rem; }
    .stat-icon {
        width: 40px; height: 40px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    .stat-value { font-size: 1.35rem; font-weight: 700; line-height: 1.2; }
    .stat-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .5px; margin: 0; }
    .teacher-card {
        border: 1px solid #e2e8f0; border-radius: 8px;
        padding: 10px 14px; cursor: pointer;
        transition: all .15s;
    }
    .teacher-card:hover { border-color: #6366f1; background: #faf5ff; }
    .teacher-card.selected { border-color: #6366f1; background: #ede9fe; }
    .schedule-timeline {
        border-left: 2px solid #e2e8f0;
        margin-left: 8px; padding-left: 20px;
    }
    .schedule-timeline-item {
        position: relative; padding: 8px 0;
    }
    .schedule-timeline-item::before {
        content: ''; position: absolute;
        left: -25px; top: 14px;
        width: 10px; height: 10px; border-radius: 50%;
        background: #cbd5e1; border: 2px solid #fff;
    }
    .schedule-timeline-item.filled::before { background: #22c55e; }
    .schedule-timeline-item.late::before { background: #f59e0b; }
    .form-section {
        background: #f8fafc; border-radius: 8px;
        padding: 16px; margin-top: 12px;
    }
    .quick-time-btn {
        font-size: .78rem; padding: 4px 10px;
        border: 1px solid #e2e8f0; border-radius: 6px;
        background: #fff; cursor: pointer; transition: all .15s;
    }
    .quick-time-btn:hover { border-color: #6366f1; color: #6366f1; }
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Guru @endslot
    @slot('li_2') Absen Manual @endslot
    @slot('title') Absen Manual Guru @endslot
@endcomponent

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

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#eff6ff;color:#3b82f6"><i class="ri-user-line"></i></div>
                <div><p class="stat-label text-muted mb-1">Total Guru</p><h4 class="stat-value mb-0 text-primary">{{ $allTeachers->count() }}</h4></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#f0fdf4;color:#22c55e"><i class="ri-checkbox-circle-fill"></i></div>
                <div><p class="stat-label text-muted mb-1">Sudah Absen</p><h4 class="stat-value mb-0 text-success">{{ $todayAttendances->count() }}</h4></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fffbeb;color:#f59e0b"><i class="ri-time-line"></i></div>
                <div><p class="stat-label text-muted mb-1">Belum Absen</p><h4 class="stat-value mb-0 text-warning">{{ $allTeachers->count() - $todayAttendances->count() }}</h4></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Left: Teacher Selection --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-2">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;background:#eff6ff;border-radius:8px;display:flex;align-items:center;justify-content:center">
                        <i class="ri-user-search-line" style="color:#6366f1"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0 fw-bold">Pilih Guru</h5>
                        <p class="text-muted mb-0" style="font-size:.78rem">Cari dan pilih guru yang akan diabsen</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-3">
                <input type="text" class="form-control form-control-sm mb-3" id="teacherSearch" placeholder="Cari nama guru..." autocomplete="off">
                <div id="teacherList" style="max-height:320px;overflow-y:auto">
                    @foreach($allTeachers as $t)
                        @php
                            $hasToday = $todayAttendances->has($t->id);
                        @endphp
                        <div class="teacher-card mb-2" data-teacher-id="{{ $t->id }}" data-teacher-name="{{ strtolower($t->name) }}" onclick="selectTeacher('{{ $t->id }}', '{{ addslashes($t->name) }}')">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:32px;height:32px;border-radius:50%;background:#6366f118;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <span class="fw-bold text-primary" style="font-size:.75rem">{{ substr($t->name, 0, 1) }}</span>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-medium d-block" style="font-size:.82rem">{{ $t->name }}</span>
                                    <small class="text-muted">{{ $t->roles->first()?->name ?? '' }}</small>
                                </div>
                                @if($hasToday)
                                    <span class="badge bg-success" style="font-size:.65rem">Sudah</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Schedule + Form --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-2">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center">
                        <i class="ri-calendar-check-line" style="color:#22c55e"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0 fw-bold">Jadwal Hari Ini — {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</h5>
                        <p class="text-muted mb-0" style="font-size:.78rem">Pilih jadwal yang akan diisi absennya</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="selectedTeacherInfo" class="d-none mb-3 p-3 rounded" style="background:#faf5ff;border:1px solid #ddd6fe">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:50%;background:#6366f1;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem" id="selAvatar"></div>
                        <div>
                            <small class="text-muted d-block">Guru dipilih:</small>
                            <span class="fw-bold" id="selName"></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-muted ms-auto" onclick="clearSelection()">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                </div>

                <div id="scheduleList" class="schedule-timeline">
                    @forelse($schedules as $s)
                        @php
                            $jadwalKbmId = $s->id;
                            $startTime = \Carbon\Carbon::parse($s->start_time)->format('H:i');
                            $endTime = \Carbon\Carbon::parse($s->end_time)->format('H:i');
                            $teacherId = $s->teacher_id ?? '';
                            $hasAtt = $todayAttendances->has($teacherId) &&
                                $todayAttendances->get($teacherId)->jadwal_kbm_id === $jadwalKbmId;
                            $itemClass = $hasAtt ? 'filled' : '';
                        @endphp
                        <div class="schedule-timeline-item {{ $itemClass }}">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="time-pill fw-medium" style="font-size:.78rem">{{ $startTime }} - {{ $endTime }}</span>
                                <span class="fw-semibold" style="font-size:.82rem">{{ $s->studyGroup?->name ?? '-' }}</span>
                                <small class="text-muted">{{ $s->subject?->name ?? '' }}</small>
                                @if($hasAtt)
                                    <span class="badge bg-success" style="font-size:.65rem">Sudah absen</span>
                                @else
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        style="font-size:.72rem;padding:2px 8px"
                                        onclick="selectSchedule('{{ $jadwalKbmId }}', '{{ addslashes($s->studyGroup?->name ?? '') }}', '{{ $startTime }}')">
                                        <i class="ri-add-line me-1"></i>Absen
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="ri-calendar-off-line" style="font-size:1.5rem;color:#cbd5e1"></i>
                            <p class="mt-2 mb-0 small">Tidak ada jadwal hari ini</p>
                        </div>
                    @endforelse
                </div>

                {{-- Manual Entry Form --}}
                <div id="manualForm" class="form-section d-none">
                    <h6 class="fw-bold mb-3"><i class="ri-edit-line me-1"></i>Form Absen Manual</h6>
                    <form method="POST" action="{{ route('user.teacher-qr.manual.store', ['userId' => $userId]) }}">
                        @csrf
                        <input type="hidden" name="teacher_id" id="form_teacher_id">
                        <input type="hidden" name="jadwal_kbm_id" id="form_jadwal_kbm_id">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-medium text-muted">Waktu Masuk</label>
                                <input type="time" name="checkin_time" id="form_checkin_time" class="form-control form-control-sm" value="{{ now()->format('H:i') }}" required>
                                <div class="mt-1">
                                    <button type="button" class="quick-time-btn" onclick="setQuickTime('07:00')">07:00</button>
                                    <button type="button" class="quick-time-btn" onclick="setQuickTime('07:15')">07:15</button>
                                    <button type="button" class="quick-time-btn" onclick="setQuickTime('07:30')">07:30</button>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-medium text-muted">Catatan (opsional)</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Contoh: Izin dari dokter">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="ri-save-line me-1"></i>Simpan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
let selectedTeacherId = null;
let selectedTeacherName = '';
let selectedJadwalKbmId = null;

function selectTeacher(id, name) {
    selectedTeacherId = id;
    selectedTeacherName = name;
    document.getElementById('form_teacher_id').value = id;
    document.getElementById('selectedTeacherInfo').classList.remove('d-none');
    document.getElementById('selAvatar').textContent = name.charAt(0);
    document.getElementById('selName').textContent = name;
    document.querySelectorAll('.teacher-card').forEach(el => {
        el.classList.toggle('selected', el.dataset.teacherId === id);
    });
}

function clearSelection() {
    selectedTeacherId = null;
    selectedTeacherName = '';
    document.getElementById('selectedTeacherInfo').classList.add('d-none');
    document.querySelectorAll('.teacher-card').forEach(el => el.classList.remove('selected'));
}

function selectSchedule(jadwalKbmId, className, defaultTime) {
    if (!selectedTeacherId) {
        alert('Pilih guru terlebih dahulu.');
        return;
    }
    selectedJadwalKbmId = jadwalKbmId;
    document.getElementById('form_jadwal_kbm_id').value = jadwalKbmId;
    document.getElementById('form_checkin_time').value = defaultTime;
    document.getElementById('manualForm').classList.remove('d-none');
    document.getElementById('manualForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function setQuickTime(time) {
    document.getElementById('form_checkin_time').value = time;
}

document.getElementById('teacherSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.teacher-card').forEach(el => {
        el.style.display = el.dataset.teacherName.includes(q) ? '' : 'none';
    });
});
</script>
@endpush
