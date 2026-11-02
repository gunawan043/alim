@extends('layouts.master')
@section('title') Penugasan & Area Kerja URT @endsection

@section('css')
<style>
    .assign-hero { background: linear-gradient(135deg, #143a52 0%, #1f7a6b 100%); color: #fff; border-radius: 14px; }
    .assign-hero .stat { background: rgba(255,255,255,.12); border-radius: 10px; padding: .75rem 1rem; }
    .assign-hero .stat .value { font-size: 1.35rem; font-weight: 700; line-height: 1.1; }
    .assign-hero .stat .label { font-size: .78rem; opacity: .85; }
    .step-add { border: 1px solid #e2e8f0; border-radius: 12px; padding: .9rem; height: 100%; background: #fff; }
    .step-add .step-no { width: 1.9rem; height: 1.9rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .8rem; background: #eef2f7; color: #33475b; }
    .step-add.is-done .step-no { background: #d1e7dd; color: #0f5132; }
    .table-freeze th, .table-freeze td { vertical-align: middle; }
    .gap-pill { border-left: 4px solid #f1b44c; }
    .gap-pill.is-clear { border-left-color: #34c38f; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.workflow.index') }}">Alur Kerja URT</a> @endslot
    @slot('title') Penugasan &amp; Area Kerja @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>Periksa kembali:</strong>
        <ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $link = function (string $name, array $params = []) {
        try { return route($name, $params); } catch (Throwable $e) { return '#'; }
    };
    $taskUrlTemplate = $link('sarpras.kebersihan.task.store', ['areaId' => '__AREA__']);
    $assignmentUrlTemplate = $link('sarpras.kebersihan.assignment.store', ['areaId' => '__AREA__']);
@endphp

<div class="assign-hero p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h4 class="mb-1 text-white">Penugasan &amp; Pembagian Area Kerja</h4>
            <p class="mb-0 opacity-75">Alur penambahan dari satuan kerja sampai checklist harian. Semua penambahan memakai modal, tanpa pindah halaman.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ $link('sarpras.workflow.index') }}" class="btn btn-light btn-sm"><i class="ri-flow-chart me-1"></i>Peta Alur</a>
            <a href="{{ $link('sarpras.kebersihan.report.create') }}" class="btn btn-outline-light btn-sm"><i class="ri-checkbox-multiple-line me-1"></i>Checklist Harian</a>
        </div>
    </div>
    <div class="row g-2 mt-3">
        <div class="col-6 col-lg-3"><div class="stat"><div class="value">{{ $stats['units_covered'] }}/{{ $stats['units'] }}</div><div class="label">Satuan kerja berpetsugas</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><div class="value">{{ $stats['officers'] }}</div><div class="label">Petugas aktif</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><div class="value">{{ $stats['areas_covered'] }}/{{ $stats['areas'] }}</div><div class="label">Area berpetsugas</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><div class="value">{{ $stats['tasks'] }}</div><div class="label">Uraian pekerjaan aktif</div></div></div>
    </div>
</div>

{{-- ALUR PENAMBAHAN --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ri-add-circle-line me-2 text-primary"></i>Alur Penambahan</h5>
        <p class="text-muted small mb-0">Ikuti berurutan. Tombol membuka modal pengisian di halaman ini.</p>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-12 col-md-6 col-xl-2">
                <div class="step-add is-done">
                    <div class="d-flex align-items-center gap-2 mb-2"><span class="step-no">1</span><span class="fw-semibold small">Satuan kerja</span></div>
                    <p class="text-muted small mb-2">Pastikan unit terdaftar.</p>
                    <a href="{{ $link('user.work-units.index') }}" class="btn btn-sm btn-outline-secondary w-100">Data Unit</a>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="step-add {{ $stats['officers'] > 0 ? 'is-done' : '' }}">
                    <div class="d-flex align-items-center gap-2 mb-2"><span class="step-no">2</span><span class="fw-semibold small">Tunjuk petugas</span></div>
                    <p class="text-muted small mb-2">GTK penanggung jawab unit.</p>
                    @if($canManage)<button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalOfficer">Tambah Petugas</button>@else<span class="text-muted small">Hanya pengelola</span>@endif
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="step-add {{ $stats['areas'] > 0 ? 'is-done' : '' }}">
                    <div class="d-flex align-items-center gap-2 mb-2"><span class="step-no">3</span><span class="fw-semibold small">Tambah area</span></div>
                    <p class="text-muted small mb-2">Gedung/ruang/taman.</p>
                    @if($canManage)<button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalArea">Tambah Area</button>@else<span class="text-muted small">Hanya pengelola</span>@endif
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="step-add {{ $stats['tasks'] > 0 ? 'is-done' : '' }}">
                    <div class="d-flex align-items-center gap-2 mb-2"><span class="step-no">4</span><span class="fw-semibold small">Tambah uraian</span></div>
                    <p class="text-muted small mb-2">Standar &amp; frekuensi kerja.</p>
                    @if($canManage)<button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalTask">Tambah Uraian</button>@else<span class="text-muted small">Hanya pengelola</span>@endif
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="step-add {{ $stats['areas_covered'] > 0 ? 'is-done' : '' }}">
                    <div class="d-flex align-items-center gap-2 mb-2"><span class="step-no">5</span><span class="fw-semibold small">Tugaskan ke area</span></div>
                    <p class="text-muted small mb-2">Petugas utama &amp; backup.</p>
                    @if($canManage)<button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalAssignment">Tugaskan</button>@else<span class="text-muted small">Hanya pengelola</span>@endif
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="step-add">
                    <div class="d-flex align-items-center gap-2 mb-2"><span class="step-no">6</span><span class="fw-semibold small">Checklist harian</span></div>
                    <p class="text-muted small mb-2">Petugas mengisi capaian.</p>
                    <a href="{{ $link('sarpras.kebersihan.report.create') }}" class="btn btn-sm btn-success w-100">Isi Checklist</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- GAP --}}
<div class="row g-3 mb-4">
    @foreach([
        'units_without_officer' => ['Satuan kerja belum punya petugas', 'Tunjuk Petugas', '#modalOfficer', 'primary'],
        'areas_without_officer' => ['Area belum punya petugas', 'Tugaskan', '#modalAssignment', 'warning'],
        'areas_without_task' => ['Area belum punya uraian', 'Tambah Uraian', '#modalTask', 'danger'],
    ] as $key => [$title, $cta, $target, $tone])
        @php $rows = $gaps[$key]; @endphp
        <div class="col-12 col-xl-4">
            <div class="card gap-pill h-100 {{ $rows->isEmpty() ? 'is-clear' : '' }}">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="fw-semibold mb-1">{{ $title }}</h6>
                        <span class="badge bg-{{ $rows->isEmpty() ? 'success' : $tone }}-subtle text-{{ $rows->isEmpty() ? 'success' : $tone }}">{{ $rows->count() }}</span>
                    </div>
                    @if($rows->isEmpty())
                        <span class="text-success small"><i class="ri-checkbox-circle-line me-1"></i>Sudah lengkap</span>
                    @else
                        <div class="text-muted small mb-2">{{ Str::limit($rows->pluck('name')->implode(', '), 90) }}</div>
                        @if($canManage)<button class="btn btn-sm btn-outline-{{ $tone }}" data-bs-toggle="modal" data-bs-target="{{ $target }}">{{ $cta }}</button>@endif
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- TABS --}}
<ul class="nav nav-tabs nav-tabs-custom mb-0" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-petugas" role="tab"><i class="ri-user-star-line me-1"></i>Petugas <span class="badge bg-light text-dark border ms-1">{{ $officers->count() }}</span></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-area" role="tab"><i class="ri-map-pin-line me-1"></i>Area Kerja <span class="badge bg-light text-dark border ms-1">{{ $areas->count() }}</span></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-uraian" role="tab"><i class="ri-list-check-2 me-1"></i>Uraian Pekerjaan <span class="badge bg-light text-dark border ms-1">{{ $tasks->count() }}</span></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-penugasan" role="tab"><i class="ri-links-line me-1"></i>Penugasan <span class="badge bg-light text-dark border ms-1">{{ $assignments->count() }}</span></a></li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-3 bg-white">
    {{-- TAB PETUGAS --}}
    <div class="tab-pane fade show active" id="tab-petugas" role="tabpanel">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <p class="text-muted small mb-0">Petugas Sarpras per satuan kerja. Satu petugas dapat ditandai sebagai petugas utama.</p>
            @if($canManage)<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalOfficer"><i class="ri-add-line me-1"></i>Tambah Petugas</button>@endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Petugas</th><th>Satuan Kerja</th><th>Peran</th><th>Area Kerja</th><th class="text-center">WO Aktif</th><th>Status</th>@if($canManage)<th class="text-end">Aksi</th>@endif</tr></thead>
                <tbody>
                @forelse($officers as $officer)
                    @php $assignedAreas = $areaByUser->get($officer->user_id, collect()); @endphp
                    <tr>
                        <td class="fw-medium">{{ $officer->user?->name ?? '-' }}</td>
                        <td class="small">{{ $officer->workUnit?->name ?? '-' }}</td>
                        <td>@if($officer->is_primary)<span class="badge bg-primary-subtle text-primary">Utama</span>@else<span class="badge bg-light text-dark border">Anggota</span>@endif</td>
                        <td class="small">
                            @forelse($assignedAreas as $assignment)
                                <span class="badge bg-light text-dark border">{{ $assignment->area?->name }}</span>
                            @empty
                                <span class="text-warning small"><i class="ri-error-warning-line me-1"></i>Belum ada area</span>
                            @endforelse
                        </td>
                        <td class="text-center">{{ (int) ($openOrders[$officer->user_id] ?? 0) }}</td>
                        <td><span class="badge bg-{{ $officer->is_active ? 'success' : 'secondary' }}-subtle text-{{ $officer->is_active ? 'success' : 'secondary' }}">{{ $officer->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        @if($canManage)
                        <td class="text-end">
                            <form action="{{ $link('sarpras.petugas.toggle', ['id' => $officer->id]) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-{{ $officer->is_active ? 'danger' : 'success' }}" title="{{ $officer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"><i class="ri-shut-down-line"></i></button>
                            </form>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada petugas. Klik <strong>Tambah Petugas</strong> untuk memulai.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- TAB AREA --}}
    <div class="tab-pane fade" id="tab-area" role="tabpanel">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <p class="text-muted small mb-0">Area mengikuti master gedung/ruang. Untuk taman, jalur, atau gorong-gorong, kosongkan gedung/ruang.</p>
            @if($canManage)<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalArea"><i class="ri-add-line me-1"></i>Tambah Area</button>@endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Area</th><th>Jenis</th><th>Satuan Kerja</th><th>Koordinator</th><th class="text-center">Petugas</th><th class="text-center">Uraian</th><th>Kesiapan</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                @forelse($areas as $area)
                    <tr>
                        <td class="fw-medium">{{ $area->name }}</td>
                        <td class="small">{{ ucfirst(str_replace('_', ' ', $area->area_type)) }}</td>
                        <td class="small">{{ $area->workUnit?->name ?? '-' }}</td>
                        <td class="small">{{ $area->coordinator?->name ?? '-' }}</td>
                        <td class="text-center">{{ $area->active_assignments_count }}</td>
                        <td class="text-center">{{ $area->active_tasks_count }}</td>
                        <td>
                            @if($area->active_assignments_count > 0 && $area->active_tasks_count > 0)
                                <span class="badge bg-success-subtle text-success">Siap</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Perlu dilengkapi</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                @if($canManage)
                                    <button class="btn btn-sm btn-outline-success" title="Tambah uraian" data-bs-toggle="modal" data-bs-target="#modalTask" data-area-id="{{ $area->id }}" data-area-name="{{ $area->name }}"><i class="ri-list-check-2"></i></button>
                                    <button class="btn btn-sm btn-outline-primary" title="Tugaskan petugas" data-bs-toggle="modal" data-bs-target="#modalAssignment" data-area-id="{{ $area->id }}" data-area-name="{{ $area->name }}"><i class="ri-user-add-line"></i></button>
                                @endif
                                <a href="{{ $link('sarpras.kebersihan.area.show', ['areaId' => $area->id]) }}" class="btn btn-sm btn-outline-secondary" title="Kelola area"><i class="ri-settings-3-line"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada area. Klik <strong>Tambah Area</strong>.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- TAB URAIAN --}}
    <div class="tab-pane fade" id="tab-uraian" role="tabpanel">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <p class="text-muted small mb-0">Uraian pekerjaan menentukan isi checklist harian petugas.</p>
            @if($canManage)<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTask"><i class="ri-add-line me-1"></i>Tambah Uraian</button>@endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Area</th><th>Kelompok</th><th>Uraian</th><th>Standar</th><th>Metode</th><th>Frekuensi</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($tasks as $task)
                    <tr>
                        <td class="small">{{ $task->area?->name ?? '-' }}</td>
                        <td class="small text-muted">{{ $task->group_label ?? '-' }}</td>
                        <td class="fw-medium">{{ $task->title }}</td>
                        <td class="small">{{ Str::limit($task->standard, 40) ?: '-' }}</td>
                        <td class="small">{{ $task->method ?: '-' }}</td>
                        <td class="small">{{ ucfirst($task->frequency) }}</td>
                        <td><span class="badge bg-{{ $task->is_active ? 'success' : 'secondary' }}-subtle text-{{ $task->is_active ? 'success' : 'secondary' }}">{{ $task->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada uraian pekerjaan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- TAB PENUGASAN --}}
    <div class="tab-pane fade" id="tab-penugasan" role="tabpanel">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <p class="text-muted small mb-0">Penugasan petugas ke area, termasuk backup dan supervisor.</p>
            @if($canManage)<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAssignment"><i class="ri-add-line me-1"></i>Tugaskan Petugas</button>@endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Area</th><th>Petugas</th><th>Peran</th><th>Backup</th><th>Supervisor</th><th>Mulai</th><th>Selesai</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td class="fw-medium">{{ $assignment->area?->name ?? '-' }}</td>
                        <td>{{ $assignment->user?->name ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ ucfirst($assignment->role) }}</span></td>
                        <td class="small">{{ $assignment->backupUser?->name ?? '-' }}</td>
                        <td class="small">{{ $assignment->supervisor?->name ?? '-' }}</td>
                        <td class="small">{{ $assignment->start_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="small">{{ $assignment->end_date?->format('d/m/Y') ?? '-' }}</td>
                        <td><span class="badge bg-{{ $assignment->is_active ? 'success' : 'secondary' }}-subtle text-{{ $assignment->is_active ? 'success' : 'secondary' }}">{{ $assignment->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada penugasan petugas ke area.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ═══════════════════ MODAL: TAMBAH PETUGAS ═══════════════════ --}}
@if($canManage)
<div class="modal fade" id="modalOfficer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ $link('sarpras.petugas.store') }}" method="POST" data-tab="petugas">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Petugas Sarpras</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Satuan Kerja <span class="text-danger">*</span></label>
                        <select name="work_unit_id" class="form-select" required>
                            <option value="">— Pilih satuan kerja —</option>
                            @foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GTK <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">— Pilih petugas —</option>
                            @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="officerPrimary">
                        <label class="form-check-label" for="officerPrimary">Jadikan petugas utama unit ini</label>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Petugas</button></div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════ MODAL: TAMBAH AREA ═══════════════════ --}}
<div class="modal fade" id="modalArea" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="{{ $link('sarpras.kebersihan.area.store') }}" method="POST" data-tab="area">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Area Kerja</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Nama Area <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="Contoh: Koridor Gedung A / Taman Depan Masjid">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Jenis Area <span class="text-danger">*</span></label>
                            <select name="area_type" class="form-select" required>
                                @foreach($areaTypes as $type)<option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gedung <span class="text-muted">(opsional)</span></label>
                            <select name="building_id" id="areaBuilding" class="form-select">
                                <option value="">— Tanpa gedung —</option>
                                @foreach($buildings as $building)<option value="{{ $building->id }}">{{ $building->building_name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ruang <span class="text-muted">(opsional)</span></label>
                            <select name="room_id" id="areaRoom" class="form-select">
                                <option value="">— Tanpa ruang —</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" data-building="{{ $room->building_id }}">{{ $room->room_name }}{{ $room->building ? ' — '.$room->building->building_name : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Koordinator Area</label>
                            <select name="coordinator_id" class="form-select">
                                <option value="">— Belum ditentukan —</option>
                                @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catatan Lokasi</label>
                            <input type="text" name="location_notes" class="form-control" placeholder="Patokan / lantai / sayap">
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Area</button></div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════ MODAL: TAMBAH URAIAN ═══════════════════ --}}
<div class="modal fade" id="modalTask" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="formTask" action="{{ $taskUrlTemplate }}" method="POST" data-tab="uraian">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Uraian Pekerjaan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="alert alert-light border small py-2"><i class="ri-information-line me-1"></i>Uraian ini menjadi butir checklist harian petugas area terpilih.</div>
                    <div class="mb-3">
                        <label class="form-label">Area Kerja <span class="text-danger">*</span></label>
                        <select name="area_id" id="taskArea" class="form-select" required>
                            <option value="">— Pilih area —</option>
                            @foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->name }} ({{ ucfirst(str_replace('_', ' ', $area->area_type)) }})</option>@endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kelompok Pekerjaan</label>
                            <input type="text" name="group_label" class="form-control" placeholder="Contoh: Penyiraman & Kelembapan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Uraian <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="Contoh: Menyapu koridor">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Standar</label>
                            <input type="text" name="standard" class="form-control" placeholder="Contoh: Bersih, tidak ada debu">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Metode</label>
                            <input type="text" name="method" class="form-control" placeholder="Visual / alat">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Frekuensi <span class="text-danger">*</span></label>
                            <select name="frequency" class="form-select" required>
                                @foreach(App\Models\CleaningTask::FREQUENCY_OPTIONS as $frequency)<option value="{{ $frequency }}">{{ ucfirst($frequency) }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan Uraian</button></div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════ MODAL: TUGASKAN PETUGAS ═══════════════════ --}}
<div class="modal fade" id="modalAssignment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="formAssignment" action="{{ $assignmentUrlTemplate }}" method="POST" data-tab="penugasan">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tugaskan Petugas ke Area</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Area Kerja <span class="text-danger">*</span></label>
                        <select name="area_id" id="assignArea" class="form-select" required>
                            <option value="">— Pilih area —</option>
                            @foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Petugas <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select" required>
                                <option value="">— Pilih petugas —</option>
                                @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Peran <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="petugas">Petugas</option>
                                <option value="koordinator">Koordinator</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Backup Jika Tidak Masuk</label>
                            <select name="backup_user_id" class="form-select">
                                <option value="">— Tidak ada —</option>
                                @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supervisor</label>
                            <select name="supervisor_id" class="form-select">
                                <option value="">— Ikut koordinator area —</option>
                                @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mulai</label>
                            <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selesai</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Penugasan</button></div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const taskTemplate = @json($taskUrlTemplate);
    const assignmentTemplate = @json($assignmentUrlTemplate);

    // Aktifkan kembali tab terakhir setelah submit modal.
    const savedTab = sessionStorage.getItem('urt_assign_tab');
    if (savedTab) {
        const trigger = document.querySelector('.nav-tabs a[href="#tab-' + savedTab + '"]');
        if (trigger) { new bootstrap.Tab(trigger).show(); }
        sessionStorage.removeItem('urt_assign_tab');
    }

    document.querySelectorAll('form[data-tab]').forEach(function (form) {
        form.addEventListener('submit', function () {
            sessionStorage.setItem('urt_assign_tab', form.dataset.tab);
        });
    });

    // Modal uraian: area terpilih menentukan URL simpan.
    const formTask = document.getElementById('formTask');
    const taskArea = document.getElementById('taskArea');
    if (formTask && taskArea) {
        const syncTask = function () {
            formTask.action = taskArea.value ? taskTemplate.replace('__AREA__', taskArea.value) : taskTemplate;
        };
        taskArea.addEventListener('change', syncTask);
        document.getElementById('modalTask').addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (btn && btn.dataset.areaId) { taskArea.value = btn.dataset.areaId; }
            syncTask();
        });
    }

    // Modal penugasan: area terpilih menentukan URL simpan.
    const formAssignment = document.getElementById('formAssignment');
    const assignArea = document.getElementById('assignArea');
    if (formAssignment && assignArea) {
        const syncAssign = function () {
            formAssignment.action = assignArea.value ? assignmentTemplate.replace('__AREA__', assignArea.value) : assignmentTemplate;
        };
        assignArea.addEventListener('change', syncAssign);
        document.getElementById('modalAssignment').addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (btn && btn.dataset.areaId) { assignArea.value = btn.dataset.areaId; }
            syncAssign();
        });
    }

    // Filter ruang berdasarkan gedung pada modal area.
    const areaBuilding = document.getElementById('areaBuilding');
    const areaRoom = document.getElementById('areaRoom');
    if (areaBuilding && areaRoom) {
        const filterRooms = function () {
            const buildingId = areaBuilding.value;
            Array.from(areaRoom.options).forEach(function (option) {
                if (!option.value) { return; }
                const matches = !buildingId || option.dataset.building === buildingId;
                option.hidden = !matches;
                if (!matches && option.selected) { areaRoom.value = ''; }
            });
        };
        areaBuilding.addEventListener('change', filterRooms);
    }
});
</script>
@endpush
