@extends('layouts.master')
@section('title') Area Kebersihan — {{ $area->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.kebersihan.index') }}">Kebersihan</a> @endslot
        @slot('title') {{ $area->name }}
    @endcomponent

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h4 class="mb-1">{{ $area->name }}</h4>
                    <div class="text-muted small">{{ ucfirst(str_replace('_',' ', $area->area_type)) }} · {{ $area->building?->building_name ?? '-' }}{{ $area->room?->room_name ? ' — '.$area->room->room_name : '' }} · Koordinator: {{ $area->coordinator?->name ?? '-' }}</div>
                    @if($area->location_notes)<p class="text-muted small mt-2 mb-0">{{ $area->location_notes }}</p>@endif
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if($canManage)<button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editAreaModal"><i class="ri-edit-line me-1"></i>Edit Area</button>@endif
                    @if($canManage)<form method="POST" action="{{ route('sarpras.kebersihan.area.destroy', ['areaId' => $area->id]) }}" class="d-inline" data-confirm="Hapus area {{ $area->name }}? Jika sudah memiliki riwayat laporan, area akan dinonaktifkan.">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm"><i class="ri-delete-bin-line me-1"></i>Hapus Area</button></form>@endif
                    @if($canManage)<button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal"><i class="ri-add-line me-1"></i>Tambah Uraian</button>@endif
                    @if($canManage)<button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAssignmentModal"><i class="ri-user-add-line me-1"></i>Tugaskan Petugas</button>@endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-list-check-2 me-1"></i>Uraian Pekerjaan</h5></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>No</th><th>Uraian</th><th>Standar</th><th>Frekuensi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @php
                        $groupedTasks = $area->tasks->groupBy(fn ($task) => $task->group_label ?: 'Pekerjaan');
                        $taskNumber = 0;
                    @endphp
                    @forelse($groupedTasks as $groupLabel => $groupTasks)
                        <tr class="table-secondary"><td colspan="6" class="fw-semibold"><i class="ri-folder-line me-1"></i>{{ $groupLabel }}</td></tr>
                        @foreach($groupTasks as $task)
                            @php $taskNumber++; @endphp
                            <tr><td>{{ $taskNumber }}</td><td><div class="fw-medium">{{ $task->title }}</div><small class="text-muted">{{ $task->method }}</small></td><td class="small">{{ $task->standard ?? '-' }}</td><td class="small">{{ ucfirst($task->frequency) }}</td><td><span class="badge bg-{{ $task->is_active ? 'success' : 'secondary' }}-subtle text-{{ $task->is_active ? 'success' : 'secondary' }}">{{ $task->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="text-end"><div class="d-inline-flex gap-1">@if($canManage)<form method="POST" action="{{ route('sarpras.kebersihan.task.toggle', ['taskId' => $task->id]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-{{ $task->is_active ? 'danger' : 'success' }}"><i class="ri-shut-down-line"></i></button></form><form method="POST" action="{{ route('sarpras.kebersihan.task.destroy', ['taskId' => $task->id]) }}" class="d-inline" data-confirm="Hapus uraian {{ $task->title }}? Jika sudah dipakai pada laporan, uraian akan dinonaktifkan.">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form>@endif</div></td></tr>
                        @endforeach
                    @empty
                        <x-empty-state colspan="6" title="Belum ada uraian pekerjaan" message="Tambahkan item pekerjaan kebersihan untuk area ini." />
                    @endforelse
                </tbody></table></div></div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-history-line me-1"></i>Laporan Terakhir</h5></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Petugas</th><th>Menggantikan</th><th>Kondisi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($reports as $report)
                        @php $tone = ['submitted'=>'warning','verified'=>'success','rejected'=>'danger'][$report->status] ?? 'secondary'; @endphp
                        <tr><td class="small">{{ $report->report_date?->format('d/m/Y') }}</td><td class="small">{{ $report->officer?->name ?? '-' }}</td><td class="small">{{ $report->substituteFor?->name ?? '-' }}</td><td class="small">{{ ucfirst(str_replace('_',' ',$report->overall_condition)) }}</td><td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst($report->status) }}</span></td><td class="text-end"><a href="{{ route('sarpras.kebersihan.report.show', ['reportId' => $report->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>
                    @empty
                        <x-empty-state colspan="6" title="Belum ada laporan" message="Belum ada laporan untuk area ini." />
                    @endforelse
                </tbody></table></div></div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-user-star-line me-1"></i>Petugas & Backup</h5></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Petugas</th><th>Peran</th><th>Koordinator</th><th>Backup</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($area->assignments as $assignment)
                        <tr><td class="small fw-medium">{{ $assignment->user?->name ?? '-' }}</td><td class="small">{{ ucfirst($assignment->role) }}</td><td class="small">{{ $assignment->supervisor?->name ?? $area->coordinator?->name ?? '-' }}</td><td class="small">{{ $assignment->backupUser?->name ?? '-' }}</td><td><span class="badge bg-{{ $assignment->is_active ? 'success' : 'secondary' }}-subtle text-{{ $assignment->is_active ? 'success' : 'secondary' }}">{{ $assignment->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="text-end">@if($canManage)<form method="POST" action="{{ route('sarpras.kebersihan.assignment.toggle', ['assignmentId' => $assignment->id]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-{{ $assignment->is_active ? 'danger' : 'success' }}"><i class="ri-shut-down-line"></i></button></form>@endif</td></tr>
                    @empty
                        <x-empty-state colspan="6" title="Belum ada petugas" message="Tugaskan petugas dan backup untuk area ini." />
                    @endforelse
                </tbody></table></div></div>
            </div>
        </div>
    </div>

    @if($canManage)
        <div class="modal fade" id="editAreaModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.kebersihan.area.update', ['areaId' => $area->id]) }}" class="modal-content">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Edit Area Kerja</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
            <div class="mb-3"><label class="form-label">Nama Area <span class="text-danger">*</span></label><input name="name" class="form-control" value="{{ $area->name }}" required></div>
            <div class="mb-3"><label class="form-label">Jenis Area <span class="text-danger">*</span></label><select name="area_type" class="form-select" required>@foreach(App\Models\CleaningArea::TYPE_OPTIONS as $type)<option value="{{ $type }}" {{ $area->area_type === $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $type)) }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Gedung <span class="text-muted">(master Sarpras, opsional)</span></label><select name="building_id" id="editCleaningBuilding" class="form-select"><option value="">— Tidak terikat gedung —</option>@foreach($buildings as $building)<option value="{{ $building->id }}" {{ $area->building_id === $building->id ? 'selected' : '' }}>{{ $building->building_name }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Ruang <span class="text-muted">(master Sarpras, opsional)</span></label><select name="room_id" id="editCleaningRoom" class="form-select"><option value="">— Tidak terikat ruang —</option>@foreach($rooms as $room)<option value="{{ $room->id }}" data-building="{{ $room->building_id }}" {{ $area->room_id === $room->id ? 'selected' : '' }}>{{ $room->building?->building_name ? $room->building->building_name.' — ' : '' }}{{ $room->room_name }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Koordinator</label><select name="coordinator_id" class="form-select"><option value="">— Pilih —</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ $area->coordinator_id === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Catatan Lokasi</label><textarea name="location_notes" class="form-control" rows="2">{{ $area->location_notes }}</textarea></div>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="areaActive" {{ $area->is_active ? 'checked' : '' }}><label class="form-check-label" for="areaActive">Area aktif</label></div>
        </div><div class="modal-footer"><button class="btn btn-primary">Simpan Perubahan</button></div></form></div></div>

        <div class="modal fade" id="addTaskModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.kebersihan.task.store', ['areaId' => $area->id]) }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Uraian Pekerjaan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Kelompok Pekerjaan <span class="text-muted">(opsional)</span></label><input name="group_label" class="form-control" placeholder="Contoh: Penyiraman & Kelembapan"></div><div class="mb-3"><label class="form-label">Uraian <span class="text-danger">*</span></label><input name="title" class="form-control" required></div><div class="mb-3"><label class="form-label">Standar</label><input name="standard" class="form-control" placeholder="Contoh: Bersih dan tidak licin"></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Metode</label><input name="method" class="form-control" placeholder="Visual"></div><div class="col-md-6"><label class="form-label">Frekuensi <span class="text-danger">*</span></label><select name="frequency" class="form-select" required>@foreach(App\Models\CleaningTask::FREQUENCY_OPTIONS as $frequency)<option value="{{ $frequency }}">{{ ucfirst($frequency) }}</option>@endforeach</select></div></div><div class="mt-3"><label class="form-label">Urutan</label><input type="number" name="sort_order" class="form-control" min="0"></div></div><div class="modal-footer"><button class="btn btn-success">Simpan</button></div></form></div></div>

        <div class="modal fade" id="addAssignmentModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.kebersihan.assignment.store', ['areaId' => $area->id]) }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tugaskan Petugas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-light border small"><i class="ri-user-star-line me-1"></i>Supervisor otomatis mengikuti <strong>Koordinator Area</strong>: {{ $area->coordinator?->name ?? 'belum ditentukan' }}.</div><div class="mb-3"><label class="form-label">Petugas <span class="text-danger">*</span></label><select name="user_id" class="form-select" required><option value="">— Pilih —</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label">Peran <span class="text-danger">*</span></label><select name="role" class="form-select" required><option value="petugas">Petugas</option><option value="koordinator">Koordinator</option></select></div><div class="mb-3"><label class="form-label">Backup Jika Tidak Masuk</label><select name="backup_user_id" class="form-select"><option value="">— Tidak ada —</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div><div class="row g-3"><div class="col-6"><label class="form-label">Mulai</label><input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}"></div><div class="col-6"><label class="form-label">Selesai</label><input type="date" name="end_date" class="form-control"></div></div><div class="mt-3"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan Penugasan</button></div></form></div></div>
    @endif
@endsection

@push('script')
<script>
    (function () {
        const building = document.getElementById('editCleaningBuilding');
        const room = document.getElementById('editCleaningRoom');
        if (!building || !room) return;
        function filterRooms() {
            const selected = building.value;
            Array.from(room.options).forEach(function (option) {
                if (!option.value) return;
                option.hidden = selected !== '' && option.dataset.building !== selected;
            });
            if (room.selectedOptions[0] && room.selectedOptions[0].hidden) room.value = '';
        }
        building.addEventListener('change', filterRooms);
        filterRooms();
    })();
</script>
@endpush
