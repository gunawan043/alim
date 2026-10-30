@extends('layouts.master')
@section('title', $group->name)
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $juzSelesai = $activeMembers->sum(fn ($member) => ($juzProgress[$member->student_id] ?? collect())->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])->count());
        $targetAverage = $recaps->pluck('pencapaian_target_persen')->filter(fn ($value) => $value !== null)->avg();
    @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') <a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}">Halaqah</a> @endslot @slot('li_2') Detail @endslot @slot('title') {{ $group->name }} @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
            <div><h5 class="card-title mb-1">{{ $group->name }}</h5><div class="text-muted small">{{ $group->school?->name }} · {{ $group->studyGroup?->name ?? 'Tanpa kelas' }} · {{ strtoupper($group->gender) }} · {{ $group->academicYear?->name }}</div><div class="small mt-1">Guru: <strong>{{ $group->teacher?->name ?? '—' }}</strong>@if($group->coordinator) · Koordinator: {{ $group->coordinator->name }}@endif</div></div>
            <div class="d-flex flex-wrap gap-2 tahfidz-page-actions">
                <a href="{{ route('user.tahfidz.setoran.create', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-primary"><i class="ri-add-line me-1"></i>Input Setoran</a>
                <a href="{{ route('user.tahfidz.progress.index', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-soft-info"><i class="ri-line-chart-line me-1"></i>Progres & Rekap</a>
                <a href="{{ route('user.tahfidz.targets.index', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-soft-warning"><i class="ri-focus-3-line me-1"></i>Target</a>
                @if($canTarget)
                    <form method="POST" action="{{ route('user.tahfidz.halaqah.targets.apply', ['userId'=>$userId,'id'=>$group->id]) }}" class="d-inline" onsubmit="return confirm('Terapkan target muqorror ke seluruh santri aktif?')">
                        @csrf
                        <button class="btn btn-soft-success"><i class="ri-magic-line me-1"></i>Terapkan Target Muqorror</button>
                    </form>
                @endif
                @if($canManage)<a href="{{ route('user.tahfidz.halaqah.edit', ['userId'=>$userId,'id'=>$group->id]) }}" class="btn btn-warning"><i class="ri-edit-line me-1"></i>Edit</a>@endif
                <a href="{{ route('user.tahfidz.halaqah.index', ['userId'=>$userId]) }}" class="btn btn-light">Kembali</a>
            </div>
        </div>
        <div class="card-body">
            @if($group->notes)<p class="text-muted small">{{ $group->notes }}</p>@endif
            <div class="row g-3">
                <div class="col-xl-3 col-md-6"><div class="border rounded p-3 h-100"><div class="text-muted small">Anggota Aktif</div><div class="fs-4 fw-bold">{{ $group->active_members_count }}</div></div></div>
                <div class="col-xl-3 col-md-6"><div class="border rounded p-3 h-100"><div class="text-muted small">Total Setoran</div><div class="fs-4 fw-bold">{{ $group->setorans_count }}</div></div></div>
                <div class="col-xl-3 col-md-6"><div class="border rounded p-3 h-100"><div class="text-muted small">Juz Selesai</div><div class="fs-4 fw-bold text-success">{{ $juzSelesai }}</div></div></div>
                <div class="col-xl-3 col-md-6"><div class="border rounded p-3 h-100"><div class="text-muted small">Rata Capaian Target</div><div class="fs-4 fw-bold">{{ $targetAverage !== null ? number_format((float)$targetAverage, 1).'%' : '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h6 class="card-title mb-0"><i class="ri-team-line text-primary me-1"></i>Anggota Halaqah</h6><span class="badge bg-primary-subtle text-primary">{{ $activeMembers->count() }}</span></div>
                <div class="table-responsive"><table class="table table-hover align-middle mb-0 tahfidz-table"><thead class="table-light"><tr><th>Nama</th><th>NIS</th><th>Progres</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($group->members as $member)
                        @php $rows = $juzProgress[$member->student_id] ?? collect(); $selesai = $rows->whereIn('status', ['selesai_ziyadah','sudah_tasmi'])->count(); $avg = $rows->whereNotNull('percentage')->avg('percentage'); @endphp
                        <tr><td class="fw-medium">{{ $member->student?->name ?? '—' }}</td><td>{{ $member->student?->nis ?? '—' }}</td><td><span class="badge bg-success-subtle text-success">{{ $selesai }} juz</span> <span class="text-muted small">{{ $avg ? number_format($avg, 1).'%' : '' }}</span></td><td><span class="badge {{ $member->status==='aktif' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $member->status }}</span></td><td class="text-end text-nowrap"><a href="{{ route('user.tahfidz.santri.show', ['userId'=>$userId,'studentId'=>$member->student_id]) }}" class="btn btn-sm btn-outline-primary" title="Detail Tahfidz"><i class="ri-eye-line"></i></a>@if($canManage && $member->status==='aktif')<button type="button" class="btn btn-sm btn-outline-danger remove-member" data-action="{{ route('user.tahfidz.halaqah.members.remove', ['userId'=>$userId,'id'=>$group->id,'memberId'=>$member->id]) }}" data-name="{{ $member->student?->name }}" title="Keluarkan"><i class="ri-user-unfollow-line"></i></button>@endif</td></tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-4">Belum ada anggota.</td></tr>@endforelse
                </tbody></table></div>
                @if($canManage)
                    <div class="card-body border-top"><form method="POST" action="{{ route('user.tahfidz.halaqah.members.store', ['userId'=>$userId,'id'=>$group->id]) }}" class="row g-2 align-items-end">@csrf<div class="col-md-9"><label class="form-label small">Tambah Santri</label><select name="student_ids[]" class="form-select" multiple size="5">@foreach($availableStudents as $student)<option value="{{ $student->id }}">{{ $student->name }} ({{ $student->nis }})</option>@endforeach</select><div class="form-text">Ctrl/Cmd untuk memilih beberapa santri.</div></div><div class="col-md-3 d-grid"><button class="btn btn-outline-primary"><i class="ri-user-add-line me-1"></i>Tambah</button></div></form></div>
                @endif
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h6 class="card-title mb-0"><i class="ri-file-add-line text-primary me-1"></i>Setoran Terakhir</h6><a href="{{ route('user.tahfidz.setoran.index', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-sm btn-soft-info">Semua</a></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Jenis</th><th>Nilai</th></tr></thead><tbody>
                    @forelse($group->setorans as $setoran)<tr><td>{{ $setoran->setoran_date?->format('d/m') }}</td><td>{{ $setoran->student?->name }}</td><td class="small">{{ $setoran->setoran_type }}</td><td>{{ $setoran->nilai_setoran ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-3">Belum ada setoran.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h6 class="card-title mb-0"><i class="ri-calendar-schedule-line text-primary me-1"></i>Jadwal Halaqah</h6><p class="text-muted mb-0 small">Beberapa halaqah pada kelas yang sama dapat berjalan bersamaan.</p></div>
            @if($canSchedule)<button type="button" class="btn btn-primary btn-sm" onclick="openScheduleModal()"><i class="ri-add-line me-1"></i>Tambah Jadwal</button>@endif
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0 tahfidz-table"><thead class="table-light"><tr><th>Hari</th><th>Waktu</th><th>Jenis</th><th>Ruang</th><th>Berlaku</th><th>Status</th>@if($canSchedule)<th class="text-end">Aksi</th>@endif</tr></thead><tbody>
            @forelse($group->schedules as $schedule)
                @php $schedulePayload = ['id'=>$schedule->id,'day_of_week'=>$schedule->day_of_week,'time_start'=>substr((string)$schedule->time_start,0,5),'time_end'=>substr((string)$schedule->time_end,0,5),'schedule_type'=>$schedule->schedule_type,'room'=>$schedule->room,'is_active'=>(bool)$schedule->is_active,'effective_from'=>$schedule->effective_from?->format('Y-m-d'),'effective_until'=>$schedule->effective_until?->format('Y-m-d'),'notes'=>$schedule->notes]; @endphp
                <tr><td>{{ $schedule->day_name }}</td><td>{{ substr((string)$schedule->time_start,0,5) }}–{{ substr((string)$schedule->time_end,0,5) }}</td><td><span class="badge bg-primary-subtle text-primary">{{ $schedule->type_label }}</span></td><td>{{ $schedule->room ?? '—' }}</td><td class="small">{{ $schedule->effective_from?->format('d/m/Y') ?? 'Selalu' }}{{ $schedule->effective_until ? ' – '.$schedule->effective_until->format('d/m/Y') : '' }}</td><td><span class="badge {{ $schedule->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>@if($canSchedule)<td class="text-end"><button type="button" class="btn btn-sm btn-outline-warning" data-schedule="{{ json_encode($schedulePayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}" onclick="openScheduleEditModal(JSON.parse(this.dataset.schedule))"><i class="ri-edit-line"></i></button><button type="button" class="btn btn-sm btn-outline-danger delete-schedule" data-action="{{ route('user.tahfidz.halaqah.schedules.destroy', ['userId'=>$userId,'id'=>$group->id,'scheduleId'=>$schedule->id]) }}"><i class="ri-delete-bin-line"></i></button></td>@endif</tr>
            @empty<tr><td colspan="{{ $canSchedule ? 7 : 6 }}" class="text-center text-muted py-3">Belum ada jadwal halaqah.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    @if($canSchedule)
        <div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('user.tahfidz.halaqah.schedules.store', ['userId'=>$userId,'id'=>$group->id]) }}" id="scheduleForm">@csrf<input type="hidden" name="_method" value="PUT" id="scheduleMethod" disabled><div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="scheduleModalTitle">Tambah Jadwal Halaqah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-4"><label class="form-label">Hari <span class="text-danger">*</span></label><select name="day_of_week" id="schedule_day" class="form-select" required>@foreach([1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'] as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Mulai <span class="text-danger">*</span></label><input type="time" name="time_start" id="schedule_start" class="form-control" required></div><div class="col-md-4"><label class="form-label">Selesai <span class="text-danger">*</span></label><input type="time" name="time_end" id="schedule_end" class="form-control" required></div><div class="col-md-6"><label class="form-label">Jenis</label><select name="schedule_type" id="schedule_type" class="form-select" required><option value="setoran">Setoran</option><option value="murajaah">Murajaah</option><option value="tasmi">Tasmi’</option><option value="evaluasi">Evaluasi</option><option value="tikror">Tikror</option></select></div><div class="col-md-6"><label class="form-label">Ruang</label><input name="room" id="schedule_room" class="form-control" maxlength="100"></div><div class="col-md-4"><label class="form-label">Berlaku Mulai</label><input type="date" name="effective_from" id="schedule_effective_from" class="form-control"></div><div class="col-md-4"><label class="form-label">Berlaku Sampai</label><input type="date" name="effective_until" id="schedule_effective_until" class="form-control"></div><div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="schedule_notes" rows="2" class="form-control"></textarea></div><div class="col-12"><div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" id="schedule_active" class="form-check-input" checked><label class="form-check-label" for="schedule_active">Aktif</label></div></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="scheduleSubmit"><i class="ri-save-line me-1"></i>Simpan Jadwal</button></div></form></div></div></div>
        <form id="deleteScheduleForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
        <x-modal-confirm id="deleteScheduleModal" title="Hapus Jadwal?" message="Jadwal halaqah ini akan dihapus." submit-label="Ya, Hapus" submit-icon="ri-delete-bin-line" submit-class="btn-danger" form-id="deleteScheduleForm" />
    @endif

    @if($canManage)
        <form id="removeMemberForm" method="POST" class="d-none">@csrf</form>
        <x-modal-confirm id="removeMemberModal" title="Keluarkan Santri?" message="<strong id='removeMemberName'>Santri</strong> akan dikeluarkan dari halaqah ini." submit-label="Ya, Keluarkan" submit-icon="ri-user-unfollow-line" submit-class="btn-danger" form-id="removeMemberForm" />
    @endif
@endsection

@push('scripts')
<script>
    @php
        $scheduleStoreUrl = route('user.tahfidz.halaqah.schedules.store', ['userId'=>$userId,'id'=>$group->id]);
        $scheduleUpdateUrl = route('user.tahfidz.halaqah.schedules.update', ['userId'=>$userId,'id'=>$group->id,'scheduleId'=>'__ID__']);
    @endphp
    function setScheduleField(id, value) { const el = document.getElementById(id); if (el) el.value = value ?? ''; }
    function openScheduleModal() {
        const form = document.getElementById('scheduleForm');
        if (!form || !window.bootstrap) return;
        form.reset(); form.action = @json($scheduleStoreUrl);
        document.getElementById('scheduleMethod').disabled = true;
        document.getElementById('scheduleModalTitle').textContent = 'Tambah Jadwal Halaqah';
        document.getElementById('scheduleSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Jadwal';
        document.getElementById('schedule_active').checked = true;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('scheduleModal')).show();
    }
    function openScheduleEditModal(data) {
        const form = document.getElementById('scheduleForm');
        if (!form || !data || !window.bootstrap) return;
        form.action = @json($scheduleUpdateUrl).replace('__ID__', data.id);
        document.getElementById('scheduleMethod').disabled = false;
        document.getElementById('scheduleModalTitle').textContent = 'Edit Jadwal Halaqah';
        document.getElementById('scheduleSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Perubahan';
        setScheduleField('schedule_day', data.day_of_week); setScheduleField('schedule_start', data.time_start); setScheduleField('schedule_end', data.time_end); setScheduleField('schedule_type', data.schedule_type); setScheduleField('schedule_room', data.room); setScheduleField('schedule_effective_from', data.effective_from); setScheduleField('schedule_effective_until', data.effective_until); setScheduleField('schedule_notes', data.notes);
        document.getElementById('schedule_active').checked = !!data.is_active;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('scheduleModal')).show();
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-schedule').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('deleteScheduleForm').action = this.dataset.action;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteScheduleModal')).show();
            });
        });
        document.querySelectorAll('.remove-member').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('removeMemberForm').action = this.dataset.action;
                document.getElementById('removeMemberName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('removeMemberModal')).show();
            });
        });
    });
</script>
@endpush
