@extends('layouts.master')
@section('title') Pengaturan Item URT @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Unit Rumah Tangga @endslot
        @slot('title') Pengaturan Item
    @endcomponent

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1"><i class="ri-settings-4-line me-2 text-primary"></i>Konsol Pengaturan Item URT</h5>
                    <p class="text-muted small mb-0">Kelola, tambah, kelompokkan, dan perbarui item untuk Kebersihan, Pertamanan, Sampah, dan Checklist dalam satu tempat.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('sarpras.kebersihan.areas') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-map-pin-line me-1"></i>Area Kerja</a>
                    <a href="{{ route('sarpras.kebersihan.programs') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-calendar-todo-line me-1"></i>Program</a>
                    <a href="{{ route('sarpras.pertamanan.agenda.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-leaf-line me-1"></i>Agenda Harian</a>
                    <a href="{{ route('sarpras.waste.facilities') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-recycle-line me-1"></i>Fasilitas Sampah</a>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
        @php
            $tabs = [
                'area' => ['label' => 'Area Kerja & Taman', 'icon' => 'ri-plant-line'],
                'agenda' => ['label' => 'Agenda Harian', 'icon' => 'ri-leaf-line'],
                'program' => ['label' => 'Program Tahunan', 'icon' => 'ri-calendar-todo-line'],
                'sampah' => ['label' => 'Sampah DLH', 'icon' => 'ri-recycle-line'],
                'checklist' => ['label' => 'Checklist', 'icon' => 'ri-checkbox-multiple-line'],
            ];
        @endphp
        @foreach($tabs as $key => $meta)
            <li class="nav-item">
                <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                   href="{{ route('sarpras.item-manager.index', ['tab' => $key]) }}">
                    <i class="{{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
                </a>
            </li>
        @endforeach
    </ul>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TAB: AREA KERJA & TAMAN --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($tab === 'area')
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('sarpras.item-manager.index') }}" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="area">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Kelompok Area (area_type)</label>
                        <select name="area_type" class="form-select">
                            <option value="">Semua kelompok</option>
                            @foreach(\App\Models\CleaningArea::TYPE_OPTIONS as $value)
                                <option value="{{ $value }}" @selected(request('area_type') === $value)>{{ ucfirst(str_replace('_', ' ', $value)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><button class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Tampilkan</button></div>
                    <div class="col-md-5 text-md-end">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#areaModal"><i class="ri-add-line me-1"></i>Tambah Area</button>
                    </div>
                </form>
            </div>
        </div>

        @forelse($grouped as $areaType => $rows)
            <div class="card mb-3">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ri-folder-2-line me-2 text-primary"></i>Kelompok: {{ ucfirst(str_replace('_', ' ', $areaType)) }}
                        <span class="badge bg-primary-subtle text-primary ms-1">{{ $rows->count() }} area</span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr>
                                <th style="width:60px">No</th><th>Area</th><th>Gedung / Ruang</th><th>Koordinator</th>
                                <th class="text-center">Uraian</th><th>Status</th><th class="text-end">Aksi</th>
                            </tr></thead>
                            <tbody>
                            @foreach($rows as $i => $area)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-medium">{{ $area->name }}</div>
                                        @if($area->location_notes)<small class="text-muted">{{ \Illuminate\Support\Str::limit($area->location_notes, 50) }}</small>@endif
                                    </td>
                                    <td class="small">{{ $area->building?->building_name ?? '-' }}{{ $area->room?->room_name ? ' — '.$area->room->room_name : '' }}</td>
                                    <td class="small">{{ $area->coordinator?->name ?? '-' }}</td>
                                    <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $area->tasks->count() }} uraian</span></td>
                                    <td><span class="badge bg-{{ $area->is_active ? 'success' : 'secondary' }}-subtle text-{{ $area->is_active ? 'success' : 'secondary' }}">{{ $area->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('sarpras.kebersihan.area.show', ['areaId' => $area->id]) }}" class="btn btn-sm btn-outline-primary" title="Kelola uraian & penugasan"><i class="ri-list-check-2"></i></a>
                                            <button class="btn btn-sm btn-outline-warning edit-area" data-bs-toggle="modal" data-bs-target="#areaModal"
                                                data-action="{{ route('sarpras.kebersihan.area.update', ['areaId' => $area->id]) }}"
                                                data-name="{{ $area->name }}" data-type="{{ $area->area_type }}"
                                                data-building="{{ $area->building_id }}" data-room="{{ $area->room_id }}"
                                                data-coordinator="{{ $area->coordinator_id }}" data-notes="{{ $area->location_notes }}"><i class="ri-pencil-line"></i></button>
                                            <form method="POST" action="{{ route('sarpras.kebersihan.area.toggle', ['areaId' => $area->id]) }}">@csrf<button class="btn btn-sm btn-outline-{{ $area->is_active ? 'danger' : 'success' }}"><i class="ri-shut-down-line"></i></button></form>
                                            <form method="POST" action="{{ route('sarpras.kebersihan.area.destroy', ['areaId' => $area->id]) }}" onsubmit="return confirm('Hapus area ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body"><x-empty-state title="Belum ada area" message="Tambahkan area kerja atau area taman terlebih dahulu." /></div></div>
        @endforelse

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="ri-group-line me-2 text-warning"></i>Pindahkan Uraian ke Kelompok Lain (massal)</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.item-manager.tasks.regroup') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold">Pilih ID uraian (pisahkan dengan koma)</label>
                        <input name="task_ids" class="form-control" placeholder="uuid1,uuid2,..." required>
                        <small class="text-muted">Dapatkan ID dari halaman detail area.</small>
                    </div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">Kelompok Baru</label><input name="group_label" class="form-control" maxlength="100" required></div>
                    <div class="col-md-2"><button class="btn btn-warning w-100"><i class="ri-drag-move-line me-1"></i>Pindahkan</button></div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="areaModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ route('sarpras.kebersihan.area.store') }}" id="areaForm" class="modal-content">@csrf
                <div id="areaMethod"></div>
                <div class="modal-header"><h5 class="modal-title" id="areaModalTitle">Tambah Area</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-8"><label class="form-label">Nama Area <span class="text-danger">*</span></label><input name="name" id="areaName" class="form-control" maxlength="191" required></div>
                    <div class="col-md-4"><label class="form-label">Kelompok <span class="text-danger">*</span></label>
                        <select name="area_type" id="areaType" class="form-select" required>
                            @foreach(\App\Models\CleaningArea::TYPE_OPTIONS as $value)<option value="{{ $value }}">{{ ucfirst(str_replace('_', ' ', $value)) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Gedung</label><select name="building_id" id="areaBuilding" class="form-select"><option value="">-</option>@foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->building_name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Ruang</label><select name="room_id" id="areaRoom" class="form-select"><option value="">-</option>@foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->room_name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Koordinator</label><select name="coordinator_id" id="areaCoordinator" class="form-select"><option value="">-</option>@foreach($coordinators as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Catatan Lokasi</label><input name="location_notes" id="areaNotes" class="form-control" maxlength="1000"></div>
                </div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan Area</button></div>
            </form>
        </div></div>
    @endif
    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TAB: AGENDA HARIAN PERTAMANAN --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($tab === 'agenda')
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('sarpras.item-manager.index') }}" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="agenda">
                    <div class="col-md-3"><label class="form-label small fw-semibold">Tanggal Agenda</label><input type="date" name="date" class="form-control" value="{{ $date }}"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-search-eye-line me-1"></i>Lihat</button></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Tanggal tersedia</label>
                        <select class="form-select" onchange="window.location='{{ route('sarpras.item-manager.index', ['tab' => 'agenda']) }}&date='+this.value">
                            @forelse($recentDates as $d)<option value="{{ \Illuminate\Support\Carbon::parse($d)->toDateString() }}">{{ \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y') }}</option>@empty<option>-</option>@endforelse
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end"><a href="{{ route('sarpras.pertamanan.agenda.index', ['date' => $date]) }}" class="btn btn-outline-secondary"><i class="ri-external-link-line me-1"></i>Buka Agenda Lengkap</a></div>
                </form>
            </div>
        </div>

        @if($agenda)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ri-calendar-line me-2 text-success"></i>Agenda {{ \Illuminate\Support\Carbon::parse($agenda->agenda_date)->translatedFormat('d F Y') }}
                        <span class="badge bg-success-subtle text-success ms-1">{{ $agenda->items->count() }} item</span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th style="width:60px">No</th><th>Bagian</th><th>Kelompok</th><th>Uraian Pekerjaan</th><th>Petugas</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                            <tbody>
                            @foreach($agenda->items->groupBy('section') as $section => $items)
                                @foreach($items as $i => $item)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ ucfirst(str_replace('_', ' ', $section)) }}</span></td>
                                    <td class="small">{{ $item->group_title ?? '-' }}</td>
                                    <td><div class="fw-medium">{{ $item->title }}</div>@if($item->notes)<small class="text-muted">{{ \Illuminate\Support\Str::limit($item->notes, 45) }}</small>@endif</td>
                                    <td class="small">{{ $item->officer?->name ?? '-' }}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($item->status) }}</span></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <button class="btn btn-sm btn-outline-warning edit-agenda-item" data-bs-toggle="modal" data-bs-target="#agendaItemModal"
                                                data-action="{{ route('sarpras.item-manager.agenda-item.update', ['itemId' => $item->id]) }}"
                                                data-title="{{ $item->title }}" data-group="{{ $item->group_title }}" data-section="{{ $item->section }}"
                                                data-officer="{{ $item->officer_id }}" data-notes="{{ $item->notes }}" data-sort="{{ $item->sort_order }}"><i class="ri-pencil-line"></i></button>
                                            <form method="POST" action="{{ route('sarpras.pertamanan.agenda.items.destroy', ['itemId' => $item->id]) }}" onsubmit="return confirm('Hapus item agenda?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="ri-drag-move-line me-2 text-warning"></i>Pindahkan Item ke Bagian Lain (massal)</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('sarpras.item-manager.agenda.regroup') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-5"><label class="form-label small fw-semibold">ID Item (pisahkan koma)</label><input name="item_ids" class="form-control" placeholder="uuid1,uuid2,..." required></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Bagian Baru</label>
                            <select name="section" class="form-select">
                                <option value="pemeliharaan">Pemeliharaan</option>
                                <option value="pembibitan">Pembibitan</option>
                                <option value="permintaan_darurat">Permintaan Darurat</option>
                            </select>
                        </div>
                        <div class="col-md-3"><button class="btn btn-warning w-100"><i class="ri-drag-move-line me-1"></i>Pindahkan</button></div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="agendaItemModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('sarpras.item-manager.agenda-item.store') }}" id="agendaItemForm" class="modal-content">@csrf
                    <div id="agendaItemMethod"></div>
                    <div class="modal-header"><h5 class="modal-title" id="agendaItemModalTitle">Tambah Item Agenda</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><div class="row g-3">
                        <input type="hidden" name="agenda_date" value="{{ $date }}">
                        <input type="hidden" name="status" value="belum">
                        <div class="col-12"><label class="form-label">Uraian Pekerjaan <span class="text-danger">*</span></label><input name="title" id="agendaItemTitle" class="form-control" maxlength="191" required></div>
                        <div class="col-md-6"><label class="form-label">Bagian</label><select name="section" id="agendaItemSection" class="form-select">
                            <option value="pemeliharaan">Pemeliharaan</option><option value="pembibitan">Pembibitan</option><option value="permintaan_darurat">Permintaan Darurat</option>
                        </select></div>
                        <div class="col-md-6"><label class="form-label">Kelompok</label><input name="group_title" id="agendaItemGroup" class="form-control" maxlength="100" placeholder="Contoh: Penyiraman Pagi"></div>
                        <div class="col-md-6"><label class="form-label">Petugas</label><select name="officer_id" id="agendaItemOfficer" class="form-select"><option value="">-</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Urutan</label><input type="number" name="sort_order" id="agendaItemSort" class="form-control" min="0"></div>
                        <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="agendaItemNotes" class="form-control" rows="2"></textarea></div>
                    </div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan</button></div>
                </form>
            </div></div>
        @else
            <div class="card"><div class="card-body">
                <x-empty-state title="Belum ada agenda pada tanggal ini" message="Buat agenda baru melalui halaman Agenda Harian Pertamanan." />
                <div class="text-center"><a href="{{ route('sarpras.pertamanan.agenda.index', ['date' => $date]) }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Buat Agenda {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M Y') }}</a></div>
            </div></div>
        @endif
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TAB: PROGRAM TAHUNAN --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($tab === 'program')
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div><h6 class="mb-0"><i class="ri-calendar-todo-line me-2 text-primary"></i>Program Tahunan Kebersihan &amp; Pertamanan</h6><p class="text-muted small mb-0">Program dikelompokkan per kategori. Centang bulan untuk menandai pelaksanaan.</p></div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#programModal"><i class="ri-add-line me-1"></i>Tambah Program</button>
            </div>
        </div>

        @forelse($programs as $category => $rows)
            <div class="card mb-3">
                <div class="card-header bg-light-subtle"><h6 class="mb-0"><i class="ri-folder-2-line me-2 text-primary"></i>Kategori: {{ ucfirst(str_replace('_', ' ', $category)) }} <span class="badge bg-primary-subtle text-primary ms-1">{{ $rows->count() }} program</span></h6></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th style="width:60px">No</th><th>Program</th><th>Area</th><th>Gedung</th><th>Frekuensi</th><th>Penanggung Jawab</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    @foreach($rows as $i => $program)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><div class="fw-medium">{{ $program->title }}</div>@if($program->standard)<small class="text-muted">{{ \Illuminate\Support\Str::limit($program->standard, 45) }}</small>@endif</td>
                            <td class="small">{{ $program->area?->name ?? '-' }}</td>
                            <td class="small">{{ $program->building?->building_name ?? '-' }}</td>
                            <td class="small">{{ ucfirst($program->frequency) }}</td>
                            <td class="small">{{ $program->assignee?->name ?? '-' }}</td>
                            <td><span class="badge bg-{{ $program->is_active ? 'success' : 'secondary' }}-subtle text-{{ $program->is_active ? 'success' : 'secondary' }}">{{ $program->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="text-end"><div class="d-inline-flex gap-1">
                                <button class="btn btn-sm btn-outline-warning edit-program" data-bs-toggle="modal" data-bs-target="#programModal"
                                    data-action="{{ route('sarpras.kebersihan.program.update', ['programId' => $program->id]) }}"
                                    data-title="{{ $program->title }}" data-category="{{ $program->category }}" data-area="{{ $program->area_id }}"
                                    data-building="{{ $program->building_id }}" data-frequency="{{ $program->frequency }}" data-assignee="{{ $program->assigned_to }}"
                                    data-standard="{{ $program->standard }}" data-method="{{ $program->method }}" data-notes="{{ $program->notes }}"><i class="ri-pencil-line"></i></button>
                                <form method="POST" action="{{ route('sarpras.kebersihan.program.toggle', ['programId' => $program->id]) }}">@csrf<button class="btn btn-sm btn-outline-{{ $program->is_active ? 'danger' : 'success' }}"><i class="ri-shut-down-line"></i></button></form>
                                <form method="POST" action="{{ route('sarpras.kebersihan.program.destroy', ['programId' => $program->id]) }}" onsubmit="return confirm('Hapus program ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form>
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div></div>
            </div>
        @empty
            <div class="card"><div class="card-body"><x-empty-state title="Belum ada program" message="Tambahkan program tahunan kebersihan atau pertamanan." /></div></div>
        @endforelse

        <div class="modal fade" id="programModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ route('sarpras.kebersihan.program.store') }}" id="programForm" class="modal-content">@csrf
                <div id="programMethodHolder"></div>
                <div class="modal-header"><h5 class="modal-title" id="programModalTitle">Tambah Program</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-8"><label class="form-label">Nama Program <span class="text-danger">*</span></label><input name="title" id="programTitle" class="form-control" maxlength="191" required></div>
                    <div class="col-md-4"><label class="form-label">Kategori <span class="text-danger">*</span></label><select name="category" id="programCategory" class="form-select" required>
                        @foreach(\App\Models\CleaningProgram::CATEGORY_OPTIONS as $value)<option value="{{ $value }}">{{ ucfirst(str_replace('_', ' ', $value)) }}</option>@endforeach
                    </select></div>
                    <div class="col-md-6"><label class="form-label">Area Terkait</label><select name="area_id" id="programArea" class="form-select"><option value="">-</option>@foreach($areas as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Gedung</label><select name="building_id" id="programBuilding" class="form-select"><option value="">-</option>@foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->building_name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Frekuensi <span class="text-danger">*</span></label><select name="frequency" id="programFrequency" class="form-select" required>
                        @foreach(\App\Models\CleaningTask::FREQUENCY_OPTIONS as $value)<option value="{{ $value }}">{{ ucfirst($value) }}</option>@endforeach
                    </select></div>
                    <div class="col-md-6"><label class="form-label">Penanggung Jawab</label><select name="assigned_to" id="programAssignee" class="form-select"><option value="">-</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Standar</label><input name="standard" id="programStandard" class="form-control" maxlength="1000"></div>
                    <div class="col-md-6"><label class="form-label">Metode</label><input name="method" id="programMethod" class="form-control" maxlength="60"></div>
                    <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="programNotes" class="form-control" rows="2"></textarea></div>
                </div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan Program</button></div>
            </form>
        </div></div>
    @endif
    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TAB: SAMPAH DLH (Sumber + Fasilitas) --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($tab === 'sampah')
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div><h6 class="mb-0"><i class="ri-map-pin-2-line me-2 text-success"></i>Sumber Sampah (Pengelolaan DLH)</h6><p class="text-muted small mb-0">Titik penghasil sampah dikelompokkan per jenis sumber.</p></div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#sourceModal"><i class="ri-add-line me-1"></i>Tambah Sumber</button>
                    <a href="{{ route('sarpras.waste.facilities') }}" class="btn btn-sm btn-outline-primary"><i class="ri-recycle-line me-1"></i>Kelola Fasilitas</a>
                </div>
            </div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th style="width:60px">No</th><th>Sumber</th><th>Kelompok</th><th>Gedung</th><th>Catatan Tercatat</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                @forelse($sources as $sourceType => $rows)
                    @foreach($rows as $i => $source)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><div class="fw-medium">{{ $source->name }}</div></td>
                        <td><span class="badge bg-info-subtle text-info">{{ ucfirst($source->source_type) }}</span></td>
                        <td class="small">{{ $source->building?->building_name ?? '-' }}</td>
                        <td class="small">{{ $source->logs->count() }} catatan</td>
                        <td><span class="badge bg-{{ $source->is_active ? 'success' : 'secondary' }}-subtle text-{{ $source->is_active ? 'success' : 'secondary' }}">{{ $source->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-end"><div class="d-inline-flex gap-1">
                            <button class="btn btn-sm btn-outline-warning edit-source" data-bs-toggle="modal" data-bs-target="#sourceModal"
                                data-action="{{ route('sarpras.item-manager.source.update', ['sourceId' => $source->id]) }}"
                                data-name="{{ $source->name }}" data-type="{{ $source->source_type }}" data-building="{{ $source->building_id }}" data-notes="{{ $source->notes }}"><i class="ri-pencil-line"></i></button>
                            <form method="POST" action="{{ route('sarpras.item-manager.source.toggle', ['sourceId' => $source->id]) }}">@csrf<button class="btn btn-sm btn-outline-{{ $source->is_active ? 'danger' : 'success' }}"><i class="ri-shut-down-line"></i></button></form>
                            <form method="POST" action="{{ route('sarpras.item-manager.source.destroy', ['sourceId' => $source->id]) }}" onsubmit="return confirm('Hapus sumber sampah ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form>
                        </div></td>
                    </tr>
                @endforeach
                @empty
                    <tr><td colspan="7"><x-empty-state title="Belum ada sumber sampah" message="Tambahkan dapur, asrama, kelas, atau sumber lain." /></td></tr>
                @endforelse
                </tbody>
            </table></div></div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div><h6 class="mb-0"><i class="ri-building-3-line me-2 text-success"></i>Fasilitas Sampah</h6><p class="text-muted small mb-0">TPS, komposter, bank sampah beserta kapasitas dan PIC.</p></div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#facilityModal"><i class="ri-add-line me-1"></i>Tambah Fasilitas</button>
            </div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th style="width:60px">No</th><th>Nama</th><th>Jenis</th><th>Lokasi</th><th>PIC</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                @forelse($facilities->flatten() as $i => $facility)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-medium">{{ $facility->name }}</td>
                        <td><span class="badge bg-info-subtle text-info">{{ \App\Models\WasteFacility::TYPE_OPTIONS[$facility->facility_type] ?? ucfirst($facility->facility_type) }}</span></td>
                        <td class="small">{{ $facility->building?->building_name ?? $facility->location ?? '-' }}</td>
                        <td class="small">{{ $facility->pic?->name ?? '-' }}</td>
                        <td><span class="badge bg-{{ $facility->is_active ? 'success' : 'secondary' }}-subtle text-{{ $facility->is_active ? 'success' : 'secondary' }}">{{ $facility->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-end"><div class="d-inline-flex gap-1">
                            <a href="{{ route('sarpras.waste.facilities') }}" class="btn btn-sm btn-outline-primary"><i class="ri-pencil-line"></i></a>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state title="Belum ada fasilitas" message="Kelola fasilitas sampah di halaman Fasilitas." /></td></tr>
                @endforelse
            </tbody></table></div></div>
        </div>

        <div class="modal fade" id="facilityModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ route('sarpras.waste.facility.store') }}" id="facilityForm" class="modal-content">@csrf
                <div id="facilityMethod"></div>
                <div class="modal-header"><h5 class="modal-title" id="facilityModalTitle">Tambah Fasilitas Sampah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nama Fasilitas <span class="text-danger">*</span></label><input name="name" id="facilityName" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="facility_type" id="facilityType" class="form-select" required>@foreach(\App\Models\WasteFacility::TYPE_OPTIONS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Gedung</label><select name="building_id" id="facilityBuilding" class="form-select"><option value="">-</option>@foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->building_name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Lokasi</label><input name="location" id="facilityLocation" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Kapasitas (kg)</label><input type="number" step="0.01" name="capacity_kg" id="facilityCapacityKg" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">PIC</label><select name="pic_user_id" id="facilityPic" class="form-select"><option value="">-</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Kondisi</label><select name="condition" id="facilityCondition" class="form-select" required>@foreach(\App\Models\WasteFacility::CONDITION_OPTIONS as $c)<option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="facilityNotes" class="form-control" rows="2"></textarea></div>
                </div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan Fasilitas</button></div>
            </form>
        </div></div>

        <div class="modal fade" id="sourceModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('sarpras.waste.source.store') }}" id="sourceForm" class="modal-content">@csrf
                <div id="sourceMethod"></div>
                <div class="modal-header"><h5 class="modal-title" id="sourceModalTitle">Tambah Sumber Sampah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nama Sumber <span class="text-danger">*</span></label><input name="name" id="sourceName" class="form-control" maxlength="191" required></div>
                    <div class="col-md-6"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="source_type" id="sourceType" class="form-select" required>
                        @foreach(\App\Models\WasteSource::TYPE_OPTIONS as $value)<option value="{{ $value }}">{{ ucfirst($value) }}</option>@endforeach
                    </select></div>
                    <div class="col-md-6"><label class="form-label">Gedung</label><select name="building_id" id="sourceBuilding" class="form-select"><option value="">-</option>@foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->building_name }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="sourceNotes" class="form-control" rows="2"></textarea></div>
                </div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan</button></div>
            </form>
        </div></div>
    @endif
    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TAB: CHECKLIST PEMELIHARAAN --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($tab === 'checklist')
        @forelse($templates as $workflowType => $rows)
            <div class="card mb-3">
                <div class="card-header bg-light-subtle"><h6 class="mb-0"><i class="ri-checkbox-multiple-line me-2 text-primary"></i>Alur: {{ ucfirst($workflowType) }} <span class="badge bg-primary-subtle text-primary ms-1">{{ $rows->count() }} template</span></h6></div>
            </div>
            @foreach($rows as $template)
                <div class="card mb-3">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h6 class="mb-0">{{ $template->name }} <span class="badge bg-secondary">{{ $template->code }}</span>
                                <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}-subtle text-{{ $template->is_active ? 'success' : 'secondary' }} ms-1">{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </h6>
                            <small class="text-muted">v{{ $template->version }} · {{ $template->items->count() }} item · kategori: {{ $template->category_slug ?? '-' }}</small>
                        </div>
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('sarpras.checklist.template', ['templateId' => $template->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line me-1"></i>Lihat</a>
                            <button class="btn btn-sm btn-success add-checklist-item" data-bs-toggle="modal" data-bs-target="#checklistItemModal" data-template="{{ $template->id }}"><i class="ri-add-line me-1"></i>Tambah Item</button>
                            <form method="POST" action="{{ route('sarpras.item-manager.checklist.template.toggle', ['templateId' => $template->id]) }}">@csrf<button class="btn btn-sm btn-outline-{{ $template->is_active ? 'danger' : 'success' }}"><i class="ri-shut-down-line"></i></button></form>
                        </div>
                    </div>
                    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th style="width:50px">No</th><th>Kelompok</th><th>Item</th><th>Frekuensi</th><th class="text-center">Wajib</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                        @forelse($template->items->groupBy('group_label') as $groupLabel => $items)
                            @foreach($items as $item)
                            <tr>
                                <td>{{ $item->sequence }}</td>
                                <td><span class="badge bg-warning-subtle text-warning">{{ $groupLabel ??= $item->group_label ?: 'Tanpa Kelompok' }}</span></td>
                                <td class="fw-medium">{{ $item->label }}</td>
                                <td class="small">{{ ucfirst($item->frequency ?? '-') }}</td>
                                <td class="text-center">{{ $item->is_required ? '<i class="ri-checkbox-circle-fill text-success"></i>' : '-' }}</td>
                                <td class="text-end"><div class="d-inline-flex gap-1">
                                    <button class="btn btn-sm btn-outline-warning edit-checklist-item" data-bs-toggle="modal" data-bs-target="#checklistItemModal"
                                        data-template="{{ $item->template_id }}" data-action="{{ route('sarpras.item-manager.checklist.item.update', ['itemId' => $item->id]) }}"
                                        data-label="{{ $item->label }}" data-group="{{ $item->group_label }}" data-standard="{{ $item->standard }}"
                                        data-method="{{ $item->method }}" data-required="{{ $item->is_required ? 1 : 0 }}"
                                        data-frequency="{{ $item->frequency }}" data-notes="{{ $item->description }}" data-sequence="{{ $item->sequence }}"><i class="ri-pencil-line"></i></button>
                                    <form method="POST" action="{{ route('sarpras.item-manager.checklist.item.destroy', ['itemId' => $item->id]) }}" onsubmit="return confirm('Hapus item checklist?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form>
                                </div></td>
                            </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">Belum ada item pada template ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div></div>
                </div>
            @endforeach
        @empty
            <div class="card"><div class="card-body"><x-empty-state title="Belum ada template checklist" message="Template checklist pemeliharaan akan tampil di sini." /></div></div>
        @endforelse

        <div class="modal fade" id="checklistItemModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ route('sarpras.item-manager.checklist.item.store', ['templateId' => '__TEMPLATE__']) }}" id="checklistItemForm" class="modal-content">@csrf
                <div id="checklistItemMethod"></div>
                <div class="modal-header"><h5 class="modal-title" id="checklistItemModalTitle">Tambah Item Checklist</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Kelompok</label><input name="group_label" id="checklistItemGroup" class="form-control" maxlength="100" placeholder="mis. Lantai, Dinding, Plafon"></div>
                    <div class="col-md-6"><label class="form-label">Urutan</label><input type="number" name="sequence" id="checklistItemSequence" class="form-control" min="0"></div>
                    <div class="col-12"><label class="form-label">Item <span class="text-danger">*</span></label><input name="label" id="checklistItemLabel" class="form-control" maxlength="191" required></div>
                    <div class="col-md-6"><label class="form-label">Frekuensi</label><select name="frequency" id="checklistItemFrequency" class="form-select">
                        <option value="">-</option>
                        @foreach(\App\Models\CleaningTask::FREQUENCY_OPTIONS as $freq)<option value="{{ $freq }}">{{ ucfirst($freq) }}</option>@endforeach
                    </select></div>
                    <div class="col-md-6"><label class="form-label">Metode</label><input name="method" id="checklistItemMethodInput" class="form-control" maxlength="191"></div>
                    <div class="col-12"><label class="form-label">Standar</label><textarea name="standard" id="checklistItemStandard" class="form-control" rows="2" maxlength="1000"></textarea></div>
                    <div class="col-12"><label class="form-label">Keterangan</label><textarea name="description" id="checklistItemDescription" class="form-control" rows="2" maxlength="1000"></textarea></div>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_required" id="checklistItemRequired" value="1" checked><label class="form-check-label" for="checklistItemRequired">Wajib diisi saat pemeriksaan</label></div></div>
                </div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan Item</button></div>
            </form>
        </div></div>
    @endif
@endsection

@push('script')
<script>
    (function () {
        // ── Area modal ──────────────────────────────────────────
        const areaModal = document.getElementById('areaModal');
        if (areaModal) {
            const form = document.getElementById('areaForm');
            const method = document.getElementById('areaMethod');
            const title = document.getElementById('areaModalTitle');
            const reset = () => {
                title.textContent = 'Tambah Area';
                form.action = '{{ route('sarpras.kebersihan.area.store') }}';
                method.innerHTML = '';
                ['areaName','areaNotes'].forEach(id => document.getElementById(id).value = '');
                document.getElementById('areaBuilding').value = '';
                document.getElementById('areaRoom').value = '';
                document.getElementById('areaCoordinator').value = '';
                document.getElementById('areaType').value = 'kantor';
            };
            document.querySelectorAll('.edit-area').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    title.textContent = 'Edit Area';
                    form.action = btn.dataset.action;
                    method.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                    document.getElementById('areaName').value = btn.dataset.name || '';
                    document.getElementById('areaType').value = btn.dataset.type || 'kantor';
                    document.getElementById('areaBuilding').value = btn.dataset.building || '';
                    document.getElementById('areaRoom').value = btn.dataset.room || '';
                    document.getElementById('areaCoordinator').value = btn.dataset.coordinator || '';
                    document.getElementById('areaNotes').value = btn.dataset.notes || '';
                });
            });
            areaModal.addEventListener('hidden.bs.modal', reset);
        }

        // ── Agenda item ─────────────────────────────────────────
        const agendaModal = document.getElementById('agendaItemModal');
        if (agendaModal) {
            const form = document.getElementById('agendaItemForm');
            const method = document.getElementById('agendaItemMethod');
            const title = document.getElementById('agendaItemModalTitle');
            const reset = () => {
                title.textContent = 'Tambah Item Agenda';
                form.action = '{{ route('sarpras.item-manager.agenda-item.store') }}';
                method.innerHTML = '';
                ['agendaItemTitle','agendaItemGroup','agendaItemNotes'].forEach(id => document.getElementById(id).value = '');
                document.getElementById('agendaItemOfficer').value = '';
                document.getElementById('agendaItemSort').value = '';
                document.getElementById('agendaItemSection').value = 'pemeliharaan';
            };
            document.querySelectorAll('.edit-agenda-item').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    title.textContent = 'Edit Item Agenda';
                    form.action = btn.dataset.action;
                    method.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                    document.getElementById('agendaItemTitle').value = btn.dataset.title || '';
                    document.getElementById('agendaItemGroup').value = btn.dataset.group || '';
                    document.getElementById('agendaItemSection').value = btn.dataset.section || 'pemeliharaan';
                    document.getElementById('agendaItemOfficer').value = btn.dataset.officer || '';
                    document.getElementById('agendaItemSort').value = btn.dataset.sort || '';
                    document.getElementById('agendaItemNotes').value = btn.dataset.notes || '';
                });
            });
            agendaModal.addEventListener('hidden.bs.modal', reset);
        }

        // ── Program ─────────────────────────────────────────────
        const programModal = document.getElementById('programModal');
        if (programModal) {
            const form = document.getElementById('programForm');
            const method = document.getElementById('programMethodHolder');
            const title = document.getElementById('programModalTitle');
            const reset = () => {
                title.textContent = 'Tambah Program';
                form.action = '{{ route('sarpras.kebersihan.program.store') }}';
                method.innerHTML = '';
                ['programTitle','programStandard','programMethod','programNotes'].forEach(id => document.getElementById(id).value = '');
                ['programCategory','programArea','programBuilding','programFrequency','programAssignee'].forEach(id => document.getElementById(id).value = '');
            };
            document.querySelectorAll('.edit-program').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    title.textContent = 'Edit Program';
                    form.action = btn.dataset.action;
                    method.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                    document.getElementById('programTitle').value = btn.dataset.title || '';
                    document.getElementById('programCategory').value = btn.dataset.category || 'lainnya';
                    document.getElementById('programArea').value = btn.dataset.area || '';
                    document.getElementById('programBuilding').value = btn.dataset.building || '';
                    document.getElementById('programFrequency').value = btn.dataset.frequency || 'harian';
                    document.getElementById('programAssignee').value = btn.dataset.assignee || '';
                    document.getElementById('programStandard').value = btn.dataset.standard || '';
                    document.getElementById('programMethod').value = btn.dataset.method || '';
                    document.getElementById('programNotes').value = btn.dataset.notes || '';
                });
            });
            programModal.addEventListener('hidden.bs.modal', reset);
        }

        // ── Checklist item ─────────────────────────────────────
        const checklistModal = document.getElementById('checklistItemModal');
        if (checklistModal) {
            const form = document.getElementById('checklistItemForm');
            const method = document.getElementById('checklistItemMethod');
            const title = document.getElementById('checklistItemModalTitle');
            const storeAction = form.action;
            const set = (id, value) => document.getElementById(id).value = value || '';
            const reset = () => {
                title.textContent = 'Tambah Item Checklist';
                form.action = storeAction;
                method.innerHTML = '';
                ['checklistItemGroup','checklistItemSequence','checklistItemLabel','checklistItemMethodInput','checklistItemStandard','checklistItemDescription'].forEach(id => set(id, ''));
                set('checklistItemFrequency', '');
                document.getElementById('checklistItemRequired').checked = true;
            };
            document.querySelectorAll('.add-checklist-item').forEach(btn => btn.addEventListener('click', function () {
                reset();
                form.action = storeAction.replace('__TEMPLATE__', btn.dataset.template);
            }));
            document.querySelectorAll('.edit-checklist-item').forEach(btn => btn.addEventListener('click', function () {
                title.textContent = 'Edit Item Checklist';
                form.action = btn.dataset.action;
                method.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                set('checklistItemGroup', btn.dataset.group);
                set('checklistItemSequence', btn.dataset.sequence);
                set('checklistItemLabel', btn.dataset.label);
                set('checklistItemMethodInput', btn.dataset.method);
                set('checklistItemStandard', btn.dataset.standard);
                set('checklistItemDescription', btn.dataset.notes);
                set('checklistItemFrequency', btn.dataset.frequency);
                document.getElementById('checklistItemRequired').checked = btn.dataset.required === '1';
            }));
            checklistModal.addEventListener('hidden.bs.modal', reset);
        }

        // ── Sumber sampah ───────────────────────────────────────
        const sourceModal = document.getElementById('sourceModal');
        if (sourceModal) {
            const form = document.getElementById('sourceForm');
            const method = document.getElementById('sourceMethod');
            const title = document.getElementById('sourceModalTitle');
            const reset = () => {
                title.textContent = 'Tambah Sumber Sampah';
                form.action = '{{ route('sarpras.waste.source.store') }}';
                method.innerHTML = '';
                ['sourceName','sourceNotes'].forEach(id => document.getElementById(id).value = '');
                document.getElementById('sourceBuilding').value = '';
                document.getElementById('sourceType').value = 'dapur';
            };
            document.querySelectorAll('.edit-source').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    title.textContent = 'Edit Sumber Sampah';
                    form.action = btn.dataset.action;
                    method.innerHTML = '<input type="hidden" name="_method" value="POST">';
                    document.getElementById('sourceName').value = btn.dataset.name || '';
                    document.getElementById('sourceType').value = btn.dataset.type || 'lainnya';
                    document.getElementById('sourceBuilding').value = btn.dataset.building || '';
                    document.getElementById('sourceNotes').value = btn.dataset.notes || '';
                });
            });
            sourceModal.addEventListener('hidden.bs.modal', reset);
        }

        // ── Fasilitas (re-use endpoint waste.facility) ──────────
        const facModal = document.getElementById('facilityModal');
        if (facModal) {
            const form = document.getElementById('facilityForm');
            const method = document.getElementById('facilityMethod');
            const title = document.getElementById('facilityModalTitle');
            const set = (id, v) => document.getElementById(id).value = v || '';
            const reset = () => {
                title.textContent = 'Tambah Fasilitas Sampah';
                form.action = '{{ route('sarpras.waste.facility.store') }}';
                method.innerHTML = '';
                ['facilityName','facilityLocation','facilityCapacityKg','facilityNotes'].forEach(id => document.getElementById(id).value = '');
                document.getElementById('facilityBuilding').value = '';
                document.getElementById('facilityPic').value = '';
                document.getElementById('facilityType').value = 'tps';
                document.getElementById('facilityCondition').value = 'baik';
            };
            facModal.addEventListener('hidden.bs.modal', reset);
        }
    })();
</script>
@endpush
