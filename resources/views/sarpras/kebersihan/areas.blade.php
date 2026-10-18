@extends('layouts.master')
@section('title') Area Kerja Kebersihan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.kebersihan.index') }}">Kebersihan</a> @endslot
        @slot('title') Area Kerja
    @endcomponent

    <div class="card mb-3"><div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div><h5 class="card-title mb-0"><i class="ri-map-pin-line me-2 text-primary"></i>Area Kerja Kebersihan</h5><p class="text-muted small mb-0">Gedung/ruang mengikuti master Sarpras. Kosongkan untuk area non-ruang seperti taman/gorong-gorong.</p></div>
        <div class="d-flex gap-2">@if($canReport)<a href="{{ route('sarpras.kebersihan.report.create') }}" class="btn btn-success btn-sm"><i class="ri-add-line me-1"></i>Laporan Harian</a>@endif @if($canManage)<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAreaModal"><i class="ri-add-line me-1"></i>Tambah Area</button>@endif</div>
    </div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>No</th><th>Area</th><th>Gedung / Ruang Sarpras</th><th>Koordinator</th><th>Petugas</th><th class="text-end">Uraian</th><th>Laporan Hari Ini</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($areas as $i => $area)
            <tr><td>{{ $i + 1 }}</td><td><div class="fw-medium">{{ $area->name }}</div><small class="text-muted">{{ ucfirst(str_replace('_',' ', $area->area_type)) }}</small></td><td class="small">{{ $area->building?->building_name ?? '-' }}{{ $area->room?->room_name ? ' — '.$area->room->room_name : '' }}</td><td class="small">{{ $area->coordinator?->name ?? '-' }}</td><td class="small">@forelse($area->activeAssignments as $assignment)<span class="badge bg-light text-dark border">{{ $assignment->user?->name }}@if($assignment->backupUser) → {{ $assignment->backupUser->name }}@endif</span>@empty<span class="text-muted">Belum ditugaskan</span>@endforelse</td><td class="text-end">{{ $area->tasks_count }}</td><td>@if($area->today_reports_count)<span class="badge bg-success-subtle text-success">{{ $area->today_reports_count }}</span>@else<span class="badge bg-warning-subtle text-warning">Belum</span>@endif</td><td class="text-end"><div class="d-inline-flex gap-1"><a href="{{ route('sarpras.kebersihan.area.show', ['areaId' => $area->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-settings-3-line"></i></a>@if($canReport)<a href="{{ route('sarpras.kebersihan.report.create', ['area_id' => $area->id]) }}" class="btn btn-sm btn-outline-success"><i class="ri-add-line"></i></a>@endif</div></td></tr>
        @empty
            <x-empty-state colspan="8" title="Belum ada area kerja" message="Tambahkan area dan uraian pekerjaan kebersihan." />
        @endforelse
    </tbody></table></div></div></div>

    @if($canManage)
        <div class="modal fade" id="addAreaModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.kebersihan.area.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Area Kerja</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
            <div class="mb-3"><label class="form-label">Nama Area <span class="text-danger">*</span></label><input name="name" class="form-control" required placeholder="Contoh: Kamar Mandi Gedung A / Taman"></div>
            <div class="mb-3"><label class="form-label">Jenis Area <span class="text-danger">*</span></label><select name="area_type" class="form-select" required>@foreach(App\Models\CleaningArea::TYPE_OPTIONS as $type)<option value="{{ $type }}">{{ ucfirst(str_replace('_',' ', $type)) }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Gedung <span class="text-muted">(opsional)</span></label><select name="building_id" id="cleaningBuildingSelect" class="form-select"><option value="">— Tidak terikat gedung —</option>@foreach($buildings as $building)<option value="{{ $building->id }}">{{ $building->building_name }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Ruang <span class="text-muted">(opsional)</span></label><select name="room_id" id="cleaningRoomSelect" class="form-select"><option value="">— Tidak terikat ruang —</option>@foreach($rooms as $room)<option value="{{ $room->id }}" data-building="{{ $room->building_id }}">{{ $room->building?->building_name ? $room->building->building_name.' — ' : '' }}{{ $room->room_name }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Koordinator</label><select name="coordinator_id" class="form-select"><option value="">— Pilih —</option>@foreach($officers as $officer)<option value="{{ $officer->id }}">{{ $officer->name }}</option>@endforeach</select></div>
            <div><label class="form-label">Catatan Lokasi</label><textarea name="location_notes" class="form-control" rows="2"></textarea></div>
        </div><div class="modal-footer"><button class="btn btn-primary">Simpan Area</button></div></form></div></div>
    @endif
@endsection

@push('script')
<script>
    (function () {
        const building = document.getElementById('cleaningBuildingSelect');
        const room = document.getElementById('cleaningRoomSelect');
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
