@extends('layouts.master')

@section('title', $type === 'jasus' ? 'Monitoring Jasus' : 'Percakapan Harian')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Operasional Harian @endslot
        @slot('title') {{ $type === 'jasus' ? 'Monitoring Jasus' : 'Percakapan Harian' }} @endslot
    @endcomponent

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $type === 'jasus' ? 'Monitoring Jasus' : 'Percakapan Harian' }}</h4>
            <p class="text-muted mb-0">Catatan pelaksanaan bahasa oleh guru bahasa dan pengurus asrama; Departemen Bahasa sebagai pengawas.</p>
        </div>
        @if($canRecord)
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createDailyModal"><i class="ri-add-line me-1"></i>Tambah Catatan</button>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <ul class="nav nav-pills gap-2 mb-3">
        <li class="nav-item"><a class="nav-link {{ $type === 'percakapan' ? 'active' : '' }}" href="{{ route('user.bahasa.daily.index', ['userId' => $userId, 'type' => 'percakapan']) }}"><i class="ri-chat-3-line me-1"></i>Percakapan Harian</a></li>
        <li class="nav-item"><a class="nav-link {{ $type === 'jasus' ? 'active' : '' }}" href="{{ route('user.bahasa.daily.index', ['userId' => $userId, 'type' => 'jasus']) }}"><i class="ri-spy-line me-1"></i>Monitoring Jasus</a></li>
    </ul>

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Percakapan</p><h3 class="fw-bold mb-0">{{ $stats['percakapan'] }}</h3></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Monitoring Jasus</p><h3 class="fw-bold mb-0">{{ $stats['jasus'] }}</h3></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Bulan Ini</p><h3 class="fw-bold mb-0">{{ $stats['month'] }}</h3></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Ada Temuan</p><h3 class="fw-bold mb-0">{{ $stats['findings'] }}</h3></div></div></div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="type" value="{{ $type }}">
                <x-bahasa-filter-fields
                    :schools="$schools"
                    :grade-levels="$gradeLevels"
                    :study-groups="$studyGroups"
                    :show-school="$showSchool"
                    :school-id="$schoolId"
                    :grade-level-id="$gradeLevelId"
                    :study-group-id="$studyGroupId"
                />
                <div class="col-md-2"><label class="form-label small mb-1">Bulan</label><input type="month" name="month" value="{{ request('month') }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label small mb-1">Cari</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Tema, temuan, atau kelas"></div>
                <div class="col-md-2"><button class="btn btn-soft-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Tanggal</th><th>Tema / Temuan</th><th>Kelas / Lokasi</th><th>Mustami</th><th class="text-center">Peserta</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-muted small">{{ $log->log_date?->format('d/m/Y') }}</td>
                            <td><div class="fw-semibold">{{ $log->theme ?: $log->type_label }}</div><div class="text-muted small">{{ \Illuminate\Support\Str::limit((string) $log->findings, 90) }}</div></td>
                            <td class="small">{{ $log->class_name ?: '-' }}<div class="text-muted">{{ $log->location ?: '' }}</div></td>
                            <td>{{ $log->mustami?->name ?? '-' }}</td>
                            <td class="text-center">{{ $log->participants_count }}</td>
                            <td><span class="badge {{ $log->status === 'perlu_tindak_lanjut' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">{{ $log->status === 'perlu_tindak_lanjut' ? 'Perlu Tindak Lanjut' : 'Selesai' }}</span></td>
                            <td class="text-end">
                                @if($canManage)
                                    <button type="button" class="btn btn-sm btn-soft-danger delete-daily" data-action="{{ route('user.bahasa.daily.destroy', ['userId' => $userId, 'log' => $log->id]) }}" data-title="{{ $log->theme ?: $log->type_label }}"><i class="ri-delete-bin-line"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">Belum ada catatan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif
    </div>

    @if($canRecord)
        <div class="modal fade" id="createDailyModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('user.bahasa.daily.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <input type="hidden" name="log_type" value="{{ $type }}">
                    <div class="modal-header"><h5 class="modal-title"><i class="ri-add-circle-line me-1 text-success"></i>{{ $type === 'jasus' ? 'Catat Monitoring Jasus' : 'Catat Percakapan Harian' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="log_date" value="{{ now()->toDateString() }}" class="form-control" required></div>
                            <div class="col-md-8"><label class="form-label">Tema / Judul</label><input name="theme" class="form-control" placeholder="{{ $type === 'jasus' ? 'Temuan penggunaan bahasa' : 'Tema percakapan' }}"></div>
                            <div class="col-md-4"><label class="form-label">Kelas</label><input name="class_name" class="form-control" placeholder="7A"></div>
                            <div class="col-md-4"><label class="form-label">Tingkat</label><select name="grade_level_id" class="form-select"><option value="">Semua tingkat</option>@foreach($gradeLevels as $grade)<option value="{{ $grade->id }}">{{ $grade->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Kelas (filter)</label><select name="study_group_id" class="form-select"><option value="">Semua kelas</option>@foreach($studyGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Lokasi</label><input name="location" class="form-control" placeholder="Asrama / kelas"></div>
                            <div class="col-md-4"><label class="form-label">Mustami / Petugas</label><select name="mustami_id" class="form-select"><option value="">— Pilih —</option>@foreach($mustamis as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Jumlah Peserta</label><input type="number" min="0" name="participants_count" value="0" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="selesai">Selesai</option><option value="perlu_tindak_lanjut">Perlu Tindak Lanjut</option></select></div>
                            <div class="col-12"><label class="form-label">{{ $type === 'jasus' ? 'Temuan' : 'Catatan Percakapan' }}</label><textarea name="findings" rows="3" class="form-control"></textarea></div>
                            <div class="col-12"><label class="form-label">Tindak Lanjut</label><textarea name="follow_up" rows="2" class="form-control"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan</button></div>
                </form>
            </div>
        </div>
    @endif

    @if($canManage)
        <div class="modal fade" id="deleteDailyModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" id="deleteDailyForm" action="#">@csrf @method('DELETE')<div class="modal-body text-center p-4"><div class="avatar-lg mx-auto mb-3"><span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger"><i class="ri-delete-bin-6-line"></i></span></div><h5>Hapus Catatan?</h5><p class="text-muted mb-0"><strong id="deleteDailyTitle">Catatan ini</strong> akan dihapus permanen.</p></div><div class="modal-footer justify-content-center"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Hapus</button></div></form></div>
        </div>
    @endif
@endsection

@section('script')
<script>
document.querySelectorAll('.delete-daily').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('deleteDailyForm').action = button.dataset.action;
        document.getElementById('deleteDailyTitle').textContent = button.dataset.title || 'Catatan ini';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteDailyModal')).show();
    });
});
</script>
@endsection
