@extends('layouts.master')

@section('title', 'AM Nasyath Syahri')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') AM Nasyath Syahri @endslot
        @slot('title') Kegiatan Bahasa Bulanan @endslot
    @endcomponent

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">AM Nasyath Syahri</h4>
            <p class="text-muted mb-0">Lomba pidato, permainan kata, film, drama, dan kegiatan bahasa lainnya.</p>
        </div>
        @if($canManage)
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createActivityModal"><i class="ri-add-line me-1"></i>Buat Kegiatan</button>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="lang-stat"><div class="label">Total Kegiatan</div><div class="value">{{ $stats['total'] }}</div></div></div>
        <div class="col-md-3"><div class="lang-stat"><div class="label">Direncanakan</div><div class="value">{{ $stats['planned'] }}</div></div></div>
        <div class="col-md-3"><div class="lang-stat"><div class="label">Selesai</div><div class="value">{{ $stats['completed'] }}</div></div></div>
        <div class="col-md-3"><div class="lang-stat"><div class="label">Partisipasi</div><div class="value">{{ number_format($stats['participants']) }}</div><div class="hint">Total peserta tercatat</div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <x-bahasa-filter-fields
                    :schools="$schools"
                    :grade-levels="$gradeLevels"
                    :study-groups="$studyGroups"
                    :show-school="$showSchool"
                    :school-id="$schoolId"
                    :grade-level-id="$gradeLevelId"
                    :study-group-id="$studyGroupId"
                />
                <div class="col-md-3"><label class="form-label small mb-1">Bulan</label><input type="month" name="month" value="{{ request('month') }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label small mb-1">Status</label><select name="status" class="form-select"><option value="">Semua</option><option value="planned" @selected(request('status') === 'planned')>Direncanakan</option><option value="completed" @selected(request('status') === 'completed')>Selesai</option><option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option></select></div>
                <div class="col-md-2"><button class="btn btn-soft-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Bulan</th><th>Kegiatan</th><th>Jenis</th><th>Tanggal</th><th>Pelaksana</th><th class="text-center">Peserta</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            <td>{{ $activity->period_month?->format('m/Y') }}</td>
                            <td>
                                <a href="{{ route('user.bahasa.activities.show', ['userId' => $userId, 'activity' => $activity->id]) }}" class="fw-semibold text-reset">{{ $activity->title }}</a>
                                <div class="lang-muted">{{ $activity->theme ?: '-' }}</div>
                            </td>
                            <td>{{ $types[$activity->activity_type] ?? $activity->activity_type }}</td>
                            <td class="lang-muted">{{ $activity->activity_date?->format('d/m/Y') ?? '-' }}<div>{{ $activity->time_start ? substr($activity->time_start, 0, 5) : '' }}{{ $activity->time_end ? ' - '.substr($activity->time_end, 0, 5) : '' }}</div></td>
                            <td>{{ ucfirst($activity->executor_type) }}{{ $activity->executor_name ? ' · '.$activity->executor_name : '' }}</td>
                            <td class="text-center">{{ $activity->participants_count }}</td>
                            <td><span class="badge lang-badge {{ $activity->status === 'completed' ? 'bg-success-subtle text-success' : ($activity->status === 'cancelled' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info') }}">{{ ucfirst($activity->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('user.bahasa.activities.show', ['userId' => $userId, 'activity' => $activity->id]) }}" class="btn btn-sm btn-soft-primary"><i class="ri-eye-line"></i></a>
                                @if($canManage)
                                    <a href="{{ route('user.bahasa.activities.edit', ['userId' => $userId, 'activity' => $activity->id]) }}" class="btn btn-sm btn-soft-warning"><i class="ri-pencil-line"></i></a>
                                    <button type="button" class="btn btn-sm btn-soft-danger delete-activity" data-action="{{ route('user.bahasa.activities.destroy', ['userId' => $userId, 'activity' => $activity->id]) }}" data-title="{{ $activity->title }}"><i class="ri-delete-bin-line"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">Belum ada kegiatan pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($activities->hasPages())<div class="card-footer">{{ $activities->links() }}</div>@endif
    </div>

    @if($canManage)
        <div class="modal fade" id="createActivityModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('user.bahasa.activities.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <div class="modal-header"><h5 class="modal-title"><i class="ri-add-circle-line me-1 text-success"></i>Buat Kegiatan Bahasa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Bulan <span class="text-danger">*</span></label><input type="month" name="period_month" value="{{ now()->format('Y-m') }}" class="form-control" required></div>
                            <div class="col-md-8"><label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label><input name="title" class="form-control" placeholder="Lomba Pidato Bahasa Arab" required></div>
                            <div class="col-md-4"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="activity_type" class="form-select" required>@foreach($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Tingkat</label><select name="grade_level_id" class="form-select"><option value="">Semua tingkat</option>@foreach($gradeLevels as $grade)<option value="{{ $grade->id }}">{{ $grade->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Kelas</label><select name="study_group_id" class="form-select"><option value="">Semua kelas</option>@foreach($studyGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Tanggal</label><input type="date" name="activity_date" value="{{ now()->toDateString() }}" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Tempat</label><input name="place" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Tema</label><input name="theme" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Pelaksana <span class="text-danger">*</span></label><select name="executor_type" class="form-select">@foreach(['santri' => 'Santri', 'guru' => 'Guru Bahasa', 'departemen' => 'Departemen', 'asrama' => 'Asrama'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">Nama Pelaksana</label><input name="executor_name" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Waktu Mulai</label><input type="time" name="time_start" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Waktu Selesai</label><input type="time" name="time_end" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="planned">Direncanakan</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select></div>
                            <div class="col-md-6"><label class="form-label">MC</label><input name="mc_name" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Jumlah Peserta</label><input type="number" min="0" name="participants_count" value="0" class="form-control"></div>
                            <div class="col-12"><label class="form-label">Panitia</label><textarea name="committee" rows="2" class="form-control"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan</button></div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="deleteActivityModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" id="deleteActivityForm" action="#">@csrf @method('DELETE')<div class="modal-body text-center p-4"><div class="avatar-lg mx-auto mb-3"><span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger"><i class="ri-delete-bin-6-line"></i></span></div><h5>Hapus Kegiatan?</h5><p class="text-muted mb-0">Kegiatan <strong id="deleteActivityTitle">ini</strong> akan dihapus permanen.</p></div><div class="modal-footer justify-content-center"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Hapus</button></div></form></div>
        </div>
    @endif
@endsection

@section('script')
<script>
document.querySelectorAll('.delete-activity').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('deleteActivityForm').action = button.dataset.action;
        document.getElementById('deleteActivityTitle').textContent = button.dataset.title || 'ini';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteActivityModal')).show();
    });
});
</script>
@endsection
