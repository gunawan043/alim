@extends('layouts.master')
@section('title') Kebersihan & CS @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Kebersihan & CS
    @endcomponent

    <div class="alert alert-info d-flex align-items-center gap-2">
        <i class="ri-google-line fs-5"></i>
        <span>Foto bukti dapat berupa <strong>tautan Google Drive</strong> (disarankan agar hemat memori) atau upload file lokal. Koordinator memantau dan memverifikasi; petugas mengerjakan area yang ditugaskan.</span>
    </div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Area Kerja', $stats['areas'], 'primary', 'ri-map-pin-line'],
            ['Uraian Pekerjaan', $stats['tasks'], 'info', 'ri-list-check-2'],
            ['Laporan Hari Ini', $stats['today'], 'success', 'ri-file-check-line'],
            ['Menunggu Verifikasi', $stats['pending'], 'warning', 'ri-time-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-3"><span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-map-pin-line me-2 text-primary"></i>Area Kerja</h5><p class="text-muted small mb-0">Koordinator mengelola area, uraian kerja, dan penugasan; petugas melapor harian.</p></div>
            <div class="d-flex gap-2">
                @if($canReport)<a href="{{ route('sarpras.kebersihan.report.create') }}" class="btn btn-success btn-sm"><i class="ri-add-line me-1"></i>Laporan Harian</a>@endif
                @if($canManage)<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAreaModal"><i class="ri-add-line me-1"></i>Tambah Area</button>@endif
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>Area</th><th>Lokasi</th><th>Koordinator</th><th>Petugas</th><th class="text-end">Uraian</th><th>Laporan Hari Ini</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    @forelse($areas as $i => $area)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><div class="fw-medium">{{ $area->name }}</div><small class="text-muted">{{ ucfirst(str_replace('_',' ', $area->area_type)) }}</small></td>
                            <td class="small">{{ $area->building?->building_name ?? '-' }}{{ $area->room?->room_name ? ' — '.$area->room->room_name : '' }}</td>
                            <td class="small">{{ $area->coordinator?->name ?? '-' }}</td>
                            <td class="small">@forelse($area->activeAssignments as $assignment)<span class="badge bg-light text-dark border">{{ $assignment->user?->name }}@if($assignment->backupUser) → backup: {{ $assignment->backupUser->name }}@endif</span>@empty<span class="text-muted">Belum ditugaskan</span>@endforelse</td>
                            <td class="text-end">{{ number_format($area->tasks_count) }}</td>
                            <td>@if($area->today_reports_count > 0)<span class="badge bg-success-subtle text-success">{{ $area->today_reports_count }} laporan</span>@else<span class="badge bg-warning-subtle text-warning">Belum ada</span>@endif</td>
                            <td class="text-end"><div class="d-inline-flex gap-1"><a href="{{ route('sarpras.kebersihan.area.show', $area->id) }}" class="btn btn-sm btn-outline-primary"><i class="ri-settings-3-line"></i></a>@if($canReport)<a href="{{ route('sarpras.kebersihan.report.create', ['area_id' => $area->id]) }}" class="btn btn-sm btn-outline-success"><i class="ri-add-line"></i></a>@endif</div></td>
                        </tr>
                    @empty
                        <x-empty-state colspan="8" title="Belum ada area kerja" message="Koordinator dapat menambahkan area dan uraian pekerjaan kebersihan." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-list-3-line me-2 text-success"></i>Laporan Kebersihan</h5></div>
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('sarpras.kebersihan.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small fw-semibold">Area</label><select name="area_id" class="form-select"><option value="">Semua</option>@foreach($areas as $area)<option value="{{ $area->id }}" {{ request('area_id') === $area->id ? 'selected' : '' }}>{{ $area->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Tanggal</label><input type="date" name="date" class="form-control" value="{{ request('date') }}"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(['submitted'=>'Menunggu Verifikasi','verified'=>'Diverifikasi','rejected'=>'Dikembalikan'] as $key=>$label)<option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
                <div class="col-md-2"><a href="{{ route('sarpras.kebersihan.index') }}" class="btn btn-light w-100">Reset</a></div>
            </form>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Area</th><th>Petugas</th><th>Menggantikan</th><th>Kondisi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($reports as $report)
                @php $tone = ['submitted'=>'warning','verified'=>'success','rejected'=>'danger'][$report->status] ?? 'secondary'; @endphp
                <tr>
                    <td class="small">{{ $report->report_date?->format('d/m/Y') }}</td>
                    <td><div class="fw-medium">{{ $report->area?->name ?? '-' }}</div><small class="text-muted">{{ $report->area?->building?->building_name }}</small></td>
                    <td class="small">{{ $report->officer?->name ?? '-' }}</td>
                    <td class="small">{{ $report->substituteFor?->name ?? '-' }}</td>
                    <td class="small">{{ ucfirst(str_replace('_',' ', $report->overall_condition)) }}</td>
                    <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst(str_replace('_',' ', $report->status)) }}</span></td>
                    <td class="text-end"><a href="{{ route('sarpras.kebersihan.report.show', $report->id) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td>
                </tr>
            @empty
                <x-empty-state colspan="7" title="Belum ada laporan kebersihan" message="Laporan harian petugas akan tampil di sini." />
            @endforelse
        </tbody></table></div></div>
        @if($reports->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $reports])</div>@endif
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-calendar-todo-line me-2 text-primary"></i>Program Kebersihan Berkala</h5><p class="text-muted small mb-0">Kipas, gorong-gorong, batu sikat, kanopi, plang nama, taman, kolam, dan pemangkasan.</p></div>@if($canManage)<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProgramModal"><i class="ri-add-line me-1"></i>Tambah Program</button>@endif</div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Program</th><th>Kategori</th><th>Frekuensi</th><th>Bulan</th><th>Petugas</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($programs as $program)
                @php $monthNames = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des']; @endphp
                <tr><td class="fw-medium">{{ $program->title }}<div class="small text-muted">{{ $program->area?->name ?? $program->building?->building_name }}</div></td><td class="small">{{ ucfirst(str_replace('_',' ', $program->category)) }}</td><td class="small">{{ ucfirst($program->frequency) }}</td><td>@foreach(($program->months ?? []) as $month)<span class="badge bg-light text-dark border">{{ $monthNames[$month] ?? $month }}</span>@endforeach</td><td class="small">{{ $program->assignee?->name ?? '-' }}</td><td class="text-end">@if($canManage)<form method="POST" action="{{ route('sarpras.kebersihan.program.toggle', $program->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-danger"><i class="ri-power-line"></i></button></form>@endif</td></tr>
            @empty
                <x-empty-state colspan="6" title="Belum ada program berkala" message="Tambahkan jadwal kebersihan berkala." />
            @endforelse
        </tbody></table></div></div>
    </div>

    @if($canManage)
        <div class="modal fade" id="addAreaModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.kebersihan.area.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Area Kerja</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
            <div class="mb-3"><label class="form-label">Nama Area <span class="text-danger">*</span></label><input name="name" class="form-control" required placeholder="Contoh: Kamar Mandi Gedung A"></div>
            <div class="mb-3"><label class="form-label">Jenis Area <span class="text-danger">*</span></label><select name="area_type" class="form-select" required>@foreach(App\Models\CleaningArea::TYPE_OPTIONS as $type)<option value="{{ $type }}">{{ ucfirst(str_replace('_',' ', $type)) }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Gedung <span class="text-muted">(master Sarpras, opsional)</span></label><select name="building_id" id="cleaningBuildingSelect" class="form-select"><option value="">— Tidak terikat gedung —</option>@foreach($buildings as $building)<option value="{{ $building->id }}">{{ $building->building_name }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Ruang <span class="text-muted">(master Sarpras, opsional)</span></label><select name="room_id" id="cleaningRoomSelect" class="form-select"><option value="">— Tidak terikat ruang —</option>@foreach($rooms as $room)<option value="{{ $room->id }}" data-building="{{ $room->building_id }}">{{ $room->building?->building_name ? $room->building->building_name.' — ' : '' }}{{ $room->room_name }}</option>@endforeach</select><small class="text-muted">Kosongkan untuk area non-ruang seperti taman, gorong-gorong, atau area umum.</small></div>
            <div class="mb-3"><label class="form-label">Koordinator</label><select name="coordinator_id" class="form-select"><option value="">— Pilih —</option>@foreach($officers as $officer)<option value="{{ $officer->id }}">{{ $officer->name }}</option>@endforeach</select></div>
            <div><label class="form-label">Catatan Lokasi</label><textarea name="location_notes" class="form-control" rows="2"></textarea></div>
        </div><div class="modal-footer"><button class="btn btn-primary">Simpan Area</button></div></form></div></div>

        <div class="modal fade" id="addProgramModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><form method="POST" action="{{ route('sarpras.kebersihan.program.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Program Kebersihan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
            <div class="col-md-8"><label class="form-label">Nama Program <span class="text-danger">*</span></label><input name="title" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Kategori <span class="text-danger">*</span></label><select name="category" class="form-select" required>@foreach(App\Models\CleaningProgram::CATEGORY_OPTIONS as $category)<option value="{{ $category }}">{{ ucfirst(str_replace('_',' ', $category)) }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Area</label><select name="area_id" class="form-select"><option value="">— Tidak ada —</option>@foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Gedung</label><select name="building_id" class="form-select"><option value="">— Tidak ada —</option>@foreach($buildings as $building)<option value="{{ $building->id }}">{{ $building->building_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Frekuensi <span class="text-danger">*</span></label><select name="frequency" class="form-select" required>@foreach(App\Models\CleaningTask::FREQUENCY_OPTIONS as $frequency)<option value="{{ $frequency }}">{{ ucfirst($frequency) }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label">Petugas</label><select name="assigned_to" class="form-select"><option value="">— Pilih —</option>@foreach($officers as $officer)<option value="{{ $officer->id }}">{{ $officer->name }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label">Bulan Pelaksanaan</label><div class="d-flex flex-wrap gap-2">@foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'] as $month => $label)<label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="months[]" value="{{ $month + 1 }}"><span class="form-check-label">{{ $label }}</span></label>@endforeach</div></div>
            <div class="col-md-6"><label class="form-label">Standar</label><input name="standard" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Metode</label><input name="method" class="form-control" placeholder="Visual / Tes fungsi"></div>
            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div></div><div class="modal-footer"><button class="btn btn-primary">Simpan Program</button></div></form></div></div>
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
