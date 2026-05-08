@extends('layouts.master')
@section('title')Todo List @endsection

@section('css')
<link href="{{ URL::asset('build/libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
.modal-backdrop.show { z-index: 1055 !important; }
#todoModal,
#detailModal,
#listModal,
#deleteModal { z-index: 1056 !important; }
#todoModal .modal-body,
#detailModal .modal-body {
    overflow-y: auto;
    max-height: calc(100vh - 200px);
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Home @endslot
    @slot('title') Todo List @endslot
@endcomponent

@php
    $authId = auth()->id();
    $currentListId = $selectedListId ?? ($defaultList->id ?? null);
    $currentList = $todoLists->firstWhere('id', $currentListId) ?? $defaultList;
@endphp

<div class="row">
    {{-- LEFT: Todo Lists Sidebar --}}
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h6 class="card-title mb-0 flex-grow-1">
                    <i class="ri-list-check-2 me-1 align-bottom"></i>Daftar Todo
                </h6>
                <button class="btn btn-sm btn-soft-primary" onclick="openListModal()" title="Tambah daftar">
                    <i class="ri-add-circle-line"></i>
                </button>
            </div>
            <div class="card-body p-0">
                <div class="live-preview">
                    @forelse($todoLists as $list)
                    <div class="list-group-item d-flex align-items-center gap-2 px-3 py-2 {{ $currentListId == $list->id ? 'active' : '' }}"
                         style="cursor:pointer;text-decoration:none;transition:background .15s;"
                         onclick="window.location='{{ route('user.todos.index', ['userId' => $authId, 'list_id' => $list->id, 'tab' => $activeTab]) }}'">
                        <span class="flex-shrink-0 rounded-circle" style="width:10px;height:10px;background:{{ $list->color }};"></span>
                        <span class="flex-grow-1 text-truncate" style="font-size:13px;">{{ $list->name }}</span>
                        <span class="badge bg-secondary-subtle text-secondary flex-shrink-0" style="font-size:10px;">{{ $list->todos_count ?? 0 }}</span>
                        <div class="dropdown" onclick="event.stopPropagation()">
                            <a href="#" class="btn btn-sm btn-ghost-secondary px-1 py-0" data-bs-toggle="dropdown">
                                <i class="ri-more-2-line" style="font-size:14px;"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="event.stopPropagation();openEditListModal('{{ $list->id }}','{{ e(addslashes($list->name)) }}','{{ $list->color }}')">
                                        <i class="ri-edit-line me-1"></i>Edit
                                    </a>
                                </li>
                                @if(!$list->is_default)
                                <li>
                                    <a class="dropdown-item" href="#" onclick="event.stopPropagation();document.getElementById('set-default-{{ $list->id }}').submit()">
                                        <i class="ri-star-line me-1"></i>Jadikan Default
                                    </a>
                                    <form id="set-default-{{ $list->id }}" method="POST"
                                          action="{{ route('user.todos.lists.set-default', ['userId' => $authId, 'id' => $list->id]) }}"
                                          style="display:none;">@csrf</form>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#"
                                       onclick="event.stopPropagation();Swal.fire({title:'Hapus daftar?',text:'{{ e(addslashes($list->name)) }} akan dihapus.',icon:'warning',showCancelButton:true,confirmButtonText:'Ya, Hapus',confirmButtonColor:'#dc3545'}).then(r=>r.isConfirmed&&document.getElementById('delete-list-{{ $list->id }}').submit())">
                                        <i class="ri-delete-bin-line me-1"></i>Hapus
                                    </a>
                                    <form id="delete-list-{{ $list->id }}" method="POST"
                                          action="{{ route('user.todos.lists.destroy', ['userId' => $authId, 'id' => $list->id]) }}"
                                          style="display:none;">@csrf @method('DELETE')</form>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <p class="text-muted mb-2" style="font-size:13px;">Belum ada daftar</p>
                        <button class="btn btn-sm btn-soft-primary" onclick="openListModal()">
                            <i class="ri-add-line me-1"></i>Buat Daftar
                        </button>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: Main Content --}}
    <div class="col-lg-9">

        {{-- Page Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-1" style="font-size:18px;font-weight:700;">
                    Todo List
                    @if($currentList)
                        <span class="text-muted fw-normal" style="font-size:15px;">· {{ $currentList->name }}</span>
                    @endif
                </h4>
                <p class="mb-0 text-muted" style="font-size:12px;">
                    {{ $stats['total'] }} tugas &nbsp;·&nbsp;
                    <span class="text-success">{{ $stats['completed'] }} selesai</span> &nbsp;·&nbsp;
                    <span class="text-danger">{{ $stats['overdue'] }} terlambat</span>
                </p>
            </div>
            <button class="btn btn-primary d-flex align-items-center gap-1" onclick="openTodoModal()">
                <i class="ri-add-line"></i>Buat Tugas
            </button>
        </div>

        {{-- Stats Row --}}
        <div class="row mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-primary-subtle text-primary rounded fs-2">
                                    <i class="bx bx-checkbox-marked"></i>
                                </span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Total</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-success-subtle text-success rounded fs-2">
                                    <i class="bx bx-check-circle"></i>
                                </span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Selesai</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $stats['completed'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-warning-subtle text-warning rounded fs-2">
                                    <i class="bx bx-loader"></i>
                                </span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Berjalan</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $stats['in_progress'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-danger-subtle text-danger rounded fs-2">
                                    <i class="bx bx-alarm-exclamation"></i>
                                </span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Terlambat</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $stats['overdue'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs + Filter Card --}}
        <div class="card">
            {{-- Tabs --}}
            <div class="card-header border-bottom">
                <ul class="nav nav-tabs-custom card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab == 'my' ? 'active' : '' }}"
                           href="{{ route('user.todos.index', ['userId' => $authId, 'tab' => 'my', 'list_id' => $currentListId]) }}">
                            <i class="ri-user-line me-1 align-bottom"></i>Todo Saya
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab == 'delegated' ? 'active' : '' }}"
                           href="{{ route('user.todos.index', ['userId' => $authId, 'tab' => 'delegated', 'list_id' => $currentListId]) }}">
                            <i class="ri-send-plane-line me-1 align-bottom"></i>Didelegasikan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab == 'watched' ? 'active' : '' }}"
                           href="{{ route('user.todos.index', ['userId' => $authId, 'tab' => 'watched', 'list_id' => $currentListId]) }}">
                            <i class="ri-eye-line me-1 align-bottom"></i>Saya Amati
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Filter Bar --}}
            <div class="card-body border-bottom">
                <form method="GET" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <input type="hidden" name="list_id" value="{{ $currentListId }}">
                    <div class="col-auto">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                            <option value="">Semua Status</option>
                            <option value="belum_mulai" {{ $filters['status']=='belum_mulai'?'selected':'' }}>Belum Mulai</option>
                            <option value="sedang_berjalan" {{ $filters['status']=='sedang_berjalan'?'selected':'' }}>Sedang Berjalan</option>
                            <option value="selesai" {{ $filters['status']=='selesai'?'selected':'' }}>Selesai</option>
                            <option value="ditunda" {{ $filters['status']=='ditunda'?'selected':'' }}>Ditunda</option>
                            <option value="dibatalkan" {{ $filters['status']=='dibatalkan'?'selected':'' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                            <option value="">Semua Prioritas</option>
                            <option value="mendesak" {{ $filters['priority']=='mendesak'?'selected':'' }}>Mendesak</option>
                            <option value="tinggi" {{ $filters['priority']=='tinggi'?'selected':'' }}>Tinggi</option>
                            <option value="sedang" {{ $filters['priority']=='sedang'?'selected':'' }}>Sedang</option>
                            <option value="rendah" {{ $filters['priority']=='rendah'?'selected':'' }}>Rendah</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <div class="input-group input-group-sm" style="width:200px;">
                            <span class="input-group-text bg-light border-0"><i class="ri-search-line text-muted" style="font-size:12px;"></i></span>
                            <input type="text" name="search" class="form-control border-0 bg-light" placeholder="Cari judul..." value="{{ $filters['search'] }}" onchange="this.form.submit()">
                        </div>
                    </div>
                    @if($filters['status'] || $filters['priority'] || $filters['search'])
                    <div class="col-auto">
                        <a href="{{ route('user.todos.index', ['userId' => $authId, 'tab' => $activeTab, 'list_id' => $currentListId]) }}"
                           class="btn btn-sm btn-ghost-secondary">Reset</a>
                    </div>
                    @endif
                </form>
            </div>

            {{-- Todo Table --}}
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;"></th>
                                <th>Judul</th>
                                <th style="width:130px;">Penanggung Jawab</th>
                                <th style="width:110px;">Tenggat</th>
                                <th style="width:90px;">Prioritas</th>
                                <th style="width:110px;">Status</th>
                                <th style="width:80px;text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todos as $todo)
                            <tr onclick="showTodoDetail('{{ $todo->id }}')" style="cursor:pointer;" class="{{ $todo->status === 'selesai' || $todo->status === 'dibatalkan' ? 'table-active' : '' }}">
                                <td class="text-center">
                                    @if($todo->is_pinned)
                                        <i class="ri-pushpin-fill text-warning" title="Disematkan" style="font-size:13px;"></i>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span class="fw-medium" style="font-size:13.5px; {{ $todo->status === 'selesai' ? 'text-decoration:line-through;color:#888;' : '' }}">
                                            {{ $todo->title }}
                                        </span>
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            @if($todo->subtasks->count() > 0)
                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">
                                                    <i class="ri-checkbox-multiple-line me-1"></i>{{ $todo->subtasks->where('is_completed',1)->count() }}/{{ $todo->subtasks->count() }}
                                                </span>
                                            @endif
                                            @if($todo->tags)
                                                @foreach(array_slice(array_filter(array_map('trim', explode(',', $todo->tags))), 0, 2) as $tag)
                                                    <span class="badge" style="background:#eef2ff;color:#405189;font-size:10px;">{{ $tag }}</span>
                                                @endforeach
                                            @endif
                                            @if($todo->is_overdue)
                                                <span class="badge bg-danger-subtle text-danger" style="font-size:10px;">
                                                    <i class="ri-time-line me-1"></i>Terlambat
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs">
                                            <span class="avatar-title-xs bg-primary-subtle text-primary rounded-circle" style="font-size:11px;">
                                                {{ substr($todo->owner?->name ?? '?', 0, 1) }}
                                            </span>
                                        </div>
                                        <div class="text-truncate" style="max-width:90px;font-size:12.5px;">
                                            {{ $todo->owner?->name ?? '-' }}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($todo->due_date)
                                        <span class="fw-medium {{ $todo->is_overdue ? 'text-danger' : 'text-muted' }}" style="font-size:12.5px;">
                                            <i class="ri-calendar-2-line me-1 align-bottom"></i>{{ $todo->due_date->format('d M') }}
                                        </span>
                                    @else
                                        <span class="text-muted" style="font-size:12px;">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $todo->priorityBadgeClass }}" style="font-size:10.5px;">
                                        {{ $todo->priorityLabel }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $todo->statusBadgeClass }}" style="font-size:10.5px;">
                                        {{ $todo->statusLabel }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-center gap-1" onclick="event.stopPropagation()">
                                        <button onclick="editTodo('{{ $todo->id }}')"
                                                class="btn btn-sm btn-ghost-primary" title="Edit">
                                            <i class="ri-edit-2-line"></i>
                                        </button>
                                        @if($todo->status !== 'selesai')
                                        <button onclick="quickComplete('{{ $todo->id }}')"
                                                class="btn btn-sm btn-ghost-success" title="Tandai Selesai">
                                            <i class="ri-check-line"></i>
                                        </button>
                                        @endif
                                        <button onclick="confirmDelete('{{ $todo->id }}','{{ e(addslashes($todo->title)) }}')"
                                                class="btn btn-sm btn-ghost-danger" title="Hapus">
                                            <i class="ri-delete-bin-2-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7">
                                    <div class="text-center py-5">
                                        <div class="avatar-md mx-auto mb-3">
                                            <span class="avatar-title bg-light text-muted rounded-circle fs-1">
                                                <i class="bx bx-task"></i>
                                            </span>
                                        </div>
                                        <h6 class="text-muted mb-1" style="font-size:14px;">Belum ada tugas</h6>
                                        <p class="text-muted" style="font-size:12px;">Tambahkan tugas pertama untuk memulai.</p>
                                        <button class="btn btn-primary btn-sm" onclick="openTodoModal()">
                                            <i class="ri-add-line me-1 align-bottom"></i>Buat Tugas
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($todos->hasPages())
                <div class="card-footer border-top-0 d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size:12px;">
                        Menampilkan {{ $todos->count() }} dari {{ $todos->total() }} tugas
                    </span>
                    {{ $todos->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- MODAL: Create / Edit Todo --}}
<div class="modal fade" id="todoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width:580px;">
        <div class="modal-content">
            <form id="todoForm" method="POST" action="{{ route('user.todos.store', ['userId' => $authId]) }}">
                @csrf
                <input type="hidden" name="_method" id="todo-form-method" value="POST">
                <input type="hidden" name="id" id="todo-id">

                <div class="modal-header">
                    <h5 class="modal-title" id="todo-modal-title">
                        <i class="ri-add-circle-fill text-primary me-1 align-bottom"></i>Buat Tugas Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div id="todo-error" class="alert alert-danger py-2" style="display:none;font-size:13px;"></div>

                    <div class="mb-3">
                        <label class="form-label">Judul Tugas <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="f-title" name="title"
                               placeholder="Contoh: Menyusun laporan bulanan" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="f-desc" name="description"
                                  rows="2" placeholder="Detail tugas..."></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Penanggung Jawab</label>
                            <select class="form-select" id="f-owner" name="owner_id" data-choices>
                                <option value="">-- Pilih --</option>
                                @foreach($userOptions as $u)
                                    <option value="{{ $u['id'] }}" {{ $u['id'] == $authId ? 'selected' : '' }}>{{ $u['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Daftar</label>
                            <select class="form-select" id="f-list" name="todo_list_id" data-choices>
                                @foreach($todoLists as $l)
                                    <option value="{{ $l->id }}" {{ $l->id == $currentListId ? 'selected' : '' }}>{{ $l->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <label class="form-label">Prioritas</label>
                            <select class="form-select" id="f-priority" name="priority" data-choices>
                                <option value="rendah">Rendah</option>
                                <option value="sedang" selected>Sedang</option>
                                <option value="tinggi">Tinggi</option>
                                <option value="mendesak">Mendesak</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="f-status" name="status" data-choices>
                                <option value="belum_mulai">Belum Mulai</option>
                                <option value="sedang_berjalan">Sedang Berjalan</option>
                                <option value="ditunda">Ditunda</option>
                                <option value="selesai">Selesai</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Tenggat</label>
                            <input type="date" class="form-control" id="f-due-date" name="due_date">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <label class="form-label">Jam</label>
                            <input type="time" class="form-control" id="f-due-time" name="due_time">
                        </div>
                        <div class="col-8">
                            <label class="form-label">Pengingat</label>
                            <input type="datetime-local" class="form-control" id="f-reminder" name="reminder_at">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tags</label>
                        <input type="text" class="form-control" id="f-tags" name="tags"
                               placeholder="urgent, laporan (pisahkan dengan koma)">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-auto">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="f-pinned" name="is_pinned" value="1">
                                <label class="form-check-label" for="f-pinned">
                                    <i class="ri-pushpin-line me-1 align-bottom"></i>Sematkan
                                </label>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="f-private" name="is_private" value="1">
                                <label class="form-check-label" for="f-private">
                                    <i class="ri-lock-line me-1 align-bottom"></i>Tugas Pribadi
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;">Subtask</label>
                            <button type="button" class="btn btn-sm btn-soft-primary" onclick="addSubtaskRow()">
                                <i class="ri-add-line me-1 align-bottom"></i>Tambah
                            </button>
                        </div>
                        <div id="subtask-rows">
                            <p id="subtask-hint" class="text-muted mb-0" style="font-size:12px;">Klik "Tambah" untuk menambahkan subtask.</p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveTodo">
                        <i class="ri-save-line align-bottom me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: Todo Detail --}}
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width:640px;">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <div class="avatar-xs me-2 flex-shrink-0">
                    <span class="avatar-title-xs bg-primary-subtle text-primary rounded-circle">
                        <i class="bx bx-task text-primary" style="font-size:14px;"></i>
                    </span>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h5 class="modal-title text-truncate mb-0" id="detail-title-text" style="font-size:15px;"></h5>
                    <span class="text-muted" id="detail-list-name" style="font-size:11px;"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <ul class="nav nav-tabs-custom px-2" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-btn-detail" data-bs-toggle="tab" href="#tab-detail" onclick="switchDetailTab('detail')" role="tab">
                        <i class="ri-information-line me-1 align-bottom"></i>Detail
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-btn-subtask" data-bs-toggle="tab" href="#tab-subtask" onclick="switchDetailTab('subtask')" role="tab">
                        <i class="ri-checkbox-multiple-line me-1 align-bottom"></i>Subtask
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1" id="d-subtask-count">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-btn-comment" data-bs-toggle="tab" href="#tab-comment" onclick="switchDetailTab('comment')" role="tab">
                        <i class="ri-chat-3-line me-1 align-bottom"></i>Komentar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-btn-watcher" data-bs-toggle="tab" href="#tab-watcher" onclick="switchDetailTab('watcher')" role="tab">
                        <i class="ri-eye-line me-1 align-bottom"></i>Pengamat
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1" id="d-watcher-count">0</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="detail-body">
                <div class="tab-pane active" id="tab-detail" role="tabpanel">
                    <p class="text-center text-muted py-4"><i class="ri-loader-4-line"></i> Memuat...</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-outline-primary" id="btnEditFromDetail" onclick="editTodo(currentDetailId)">
                    <i class="ri-edit-2-line align-bottom me-1"></i>Edit
                </button>
                <button type="button" class="btn btn-success" id="btnCompleteDetail" onclick="quickComplete(currentDetailId)">
                    <i class="ri-check-line align-bottom me-1"></i>Tandai Selesai
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: Create / Edit List --}}
<div class="modal fade" id="listModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content">
            <form id="listForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="list-modal-title">
                        <i class="ri-list-check-2 me-2 text-primary align-bottom"></i>Daftar Todo Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="list-modal-id">
                    <div class="mb-3">
                        <label class="form-label">Nama Daftar</label>
                        <input type="text" class="form-control" id="list-modal-name" placeholder="Contoh: Pekerjaan Kantor" required>
                    </div>
                    <div>
                        <label class="form-label">Warna</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['#0ab39c','#f06548','#40539d','#f7b84b','#0d6efd','#adb5bd','#dc3545','#198754','#e91e63','#673ab7'] as $color)
                            <div>
                                <input type="radio" class="btn-check" name="list-color"
                                       id="lc-{{ str_replace('#','',$color) }}" value="{{ $color }}"
                                       {{ $loop->first ? 'checked' : '' }}>
                                <label class="btn btn-sm" for="lc-{{ str_replace('#','',$color) }}"
                                       style="width:28px;height:28px;padding:0;border-radius:50%;background:{{ $color }};border:none;"></label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line align-bottom me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: Confirm Delete --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pb-4">
                <div class="avatar-md mx-auto mb-3">
                    <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-1">
                        <i class="bx bx-trash"></i>
                    </span>
                </div>
                <h5 class="mb-2" style="font-size:15px;">Hapus tugas ini?</h5>
                <p class="text-muted mb-0" style="font-size:13px;" id="delete-todo-name"></p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <form id="delete-form" method="POST" style="display:inline;">@csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="ri-delete-bin-line align-bottom me-1"></i>Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
<script>
const AUTH_ID = '{{ $authId }}';
const CSRF = '{{ csrf_token() }}';
let subtaskCount = 0;
let currentDetailId = null;
let currentTab = 'detail';
const allUsers = @json($userOptions);

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function fmt(str) {
    if (!str) return '-';
    return new Date(str).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' });
}

// ── Todo Modal ───────────────────────────────────────────────
function openTodoModal() {
    document.getElementById('todoForm').reset();
    document.getElementById('todo-form-method').value = 'POST';
    document.getElementById('todo-id').value = '';
    document.getElementById('todo-modal-title').innerHTML = '<i class="ri-add-circle-fill text-primary me-1 align-bottom"></i>Buat Tugas Baru';
    document.getElementById('btnSaveTodo').innerHTML = '<i class="ri-save-line align-bottom me-1"></i>Simpan';
    document.getElementById('todo-error').style.display = 'none';
    document.getElementById('todoForm').action = '/' + AUTH_ID + '/todos';
    resetSubtasks();
    initChoices();
    new bootstrap.Modal(document.getElementById('todoModal')).show();
}

async function editTodo(id) {
    if (id) currentDetailId = id;
    const res = await fetch('/' + AUTH_ID + '/todos/' + currentDetailId, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await res.json();
    if (!data.success) { Swal.fire({ icon:'error', text: data.message }); return; }
    const t = data.data;

    document.getElementById('todo-form-method').value = 'PUT';
    document.getElementById('todo-id').value = t.id;
    document.getElementById('todo-modal-title').innerHTML = '<i class="ri-edit-2-line text-primary me-1 align-bottom"></i>Edit Tugas';
    document.getElementById('btnSaveTodo').innerHTML = '<i class="ri-save-line align-bottom me-1"></i>Perbarui';
    document.getElementById('todoForm').action = '/' + AUTH_ID + '/todos/' + t.id;

    document.getElementById('f-title').value = t.title || '';
    document.getElementById('f-desc').value = t.description || '';
    document.getElementById('f-due-date').value = t.due_date || '';
    document.getElementById('f-due-time').value = t.due_time || '';
    document.getElementById('f-tags').value = t.tags || '';
    document.getElementById('f-pinned').checked = !!t.is_pinned;
    document.getElementById('f-private').checked = !!t.is_private;
    if (t.reminder_at) document.getElementById('f-reminder').value = t.reminder_at.slice(0,16);

    initChoices();
    setTimeout(() => {
        const pairs = { f_owner: t.owner_id, f_list: t.todo_list_id, f_priority: t.priority, f_status: t.status };
        Object.entries(pairs).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el?._choices && val) el._choices.setChoiceByValue(val);
        });
    }, 80);

    resetSubtasks();
    (t.subtasks || []).forEach(st => addSubtaskRow(st.title, st.id));

    bootstrap.Modal.getInstance(document.getElementById('detailModal'))?.hide();
    new bootstrap.Modal(document.getElementById('todoModal')).show();
}

// ── Subtasks ─────────────────────────────────────────────────
function addSubtaskRow(title = '', id = '') {
    const container = document.getElementById('subtask-rows');
    const hint = document.getElementById('subtask-hint');
    if (hint) hint.remove();
    subtaskCount++;
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.id = 'st-row-' + subtaskCount;
    div.innerHTML = `
        <span class="input-group-text bg-light border-0" style="font-size:12px;min-width:32px;justify-content:center;">${subtaskCount}.</span>
        <input type="hidden" name="subtasks[${subtaskCount}][id]" value="${id}">
        <input type="text" class="form-control form-control-sm" name="subtasks[${subtaskCount}][title]" value="${escHtml(title)}" placeholder="Subtask ${subtaskCount}">
        <button type="button" class="btn btn-sm btn-ghost-danger" onclick="removeSubtaskRow(${subtaskCount})">
            <i class="ri-close-line"></i>
        </button>`;
    container.appendChild(div);
}

function removeSubtaskRow(n) {
    document.getElementById('st-row-' + n)?.remove();
    if (!document.getElementById('subtask-rows').children.length) {
        document.getElementById('subtask-rows').innerHTML =
            '<p id="subtask-hint" class="text-muted mb-0" style="font-size:12px;">Klik "Tambah" untuk menambahkan subtask.</p>';
    }
}

function resetSubtasks() {
    subtaskCount = 0;
    document.getElementById('subtask-rows').innerHTML =
        '<p id="subtask-hint" class="text-muted mb-0" style="font-size:12px;">Klik "Tambah" untuk menambahkan subtask.</p>';
}

// ── Todo Form Submit ─────────────────────────────────────────
document.getElementById('todoForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const errEl = document.getElementById('todo-error');
    errEl.style.display = 'none';

    if (!document.getElementById('f-title').value.trim()) {
        errEl.textContent = 'Judul tugas wajib diisi.';
        errEl.style.display = 'block';
        return;
    }

    const method = document.getElementById('todo-form-method').value;
    const id = document.getElementById('todo-id').value;
    const url = method === 'PUT' && id ? '/' + AUTH_ID + '/todos/' + id : '/' + AUTH_ID + '/todos';

    const body = new FormData(this);
    body.append('_method', method);

    const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body,
    });
    const json = await res.json();
    if (json.success) {
        bootstrap.Modal.getInstance(document.getElementById('todoModal'))?.hide();
        Swal.fire({ toast:true, position:'top-end', icon:'success', title: json.message, showConfirmButton:false, timer:2000 });
        setTimeout(() => window.location.reload(), 400);
    } else {
        errEl.textContent = json.message || 'Terjadi kesalahan.';
        errEl.style.display = 'block';
    }
});

// ── Quick Complete ───────────────────────────────────────────
async function quickComplete(id) {
    const res = await fetch('/' + AUTH_ID + '/todos/' + id, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ status: 'selesai' }),
    });
    const data = await res.json();
    if (data.success) {
        Swal.fire({ toast:true, position:'top-end', icon:'success', title: data.message, showConfirmButton:false, timer:2000 });
        setTimeout(() => window.location.reload(), 400);
    } else {
        Swal.fire({ icon:'error', text: data.message });
    }
}

// ── Delete ───────────────────────────────────────────────────
function confirmDelete(id, name) {
    document.getElementById('delete-todo-name').textContent = name;
    document.getElementById('delete-form').action = '/' + AUTH_ID + '/todos/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// ── Detail Modal ─────────────────────────────────────────────
async function showTodoDetail(id) {
    currentDetailId = id;
    currentTab = 'detail';
    document.getElementById('detail-body').innerHTML = `
        <div class="tab-pane active" id="tab-detail" role="tabpanel">
            <div class="text-center py-4"><i class="ri-loader-4-line fs-4 text-muted"></i></div>
        </div>`;
    document.querySelectorAll('.nav-link[id^="tab-btn-"]').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-btn-detail').classList.add('active');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('detailModal')).show();

    try {
        const res = await fetch('/' + AUTH_ID + '/todos/' + id, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        renderDetail(data.data);
    } catch(e) {
        document.getElementById('detail-body').innerHTML = `
            <div class="tab-pane active" id="tab-detail" role="tabpanel">
                <p class="text-danger text-center py-3"><i class="ri-error-warning-line me-1"></i>Gagal memuat detail.</p>
            </div>`;
    }
}

function renderDetail(t) {
    document.getElementById('detail-title-text').textContent = t.title || '';
    document.getElementById('detail-list-name').textContent = t.todo_list?.name || '';
    document.getElementById('d-subtask-count').textContent = (t.subtasks || []).length;
    document.getElementById('d-watcher-count').textContent = (t.watchers || []).length;
    document.getElementById('btnCompleteDetail').style.display = t.status === 'selesai' ? 'none' : '';
    renderDetailTab(t);
}

function switchDetailTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.nav-link[id^="tab-btn-"]').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-btn-' + tab)?.classList.add('active');
    if (!currentDetailId) return;
    fetch('/' + AUTH_ID + '/todos/' + currentDetailId, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) renderDetailTab(data.data);
    });
}

function renderDetailTab(t) {
    const container = document.getElementById('detail-body');
    let html = '';

    if (currentTab === 'detail') {
        html = `
        <div class="tab-pane active" id="tab-detail" role="tabpanel">
            <div class="p-3">
                ${t.description ? `<div class="mb-3"><p class="mb-1" style="font-size:13.5px;">${escHtml(t.description)}</p></div>` : ''}
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:130px;font-size:12px;">Penanggung Jawab</td><td><strong style="font-size:13px;">${t.owner?.name || '-'}</strong></td></tr>
                        <tr><td class="text-muted" style="font-size:12px;">Diorahkan Oleh</td><td style="font-size:13px;">${t.delegated_by_user?.name || '-'}</td></tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Prioritas</td>
                            <td><span class="${t.priority_badge_class || ''}" style="font-size:10.5px;">${t.priority_label || t.priority}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Status</td>
                            <td><span class="${t.status_badge_class || ''}" style="font-size:10.5px;">${t.status_label || t.status}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Tenggat</td>
                            <td class="${t.is_overdue && t.status !== 'selesai' ? 'text-danger' : ''}" style="font-size:13px;">
                                ${t.due_date ? fmt(t.due_date) + ' ' + (t.due_time || '') : '-'}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Progress</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress" style="height:6px;flex:1;min-width:80px;">
                                        <div class="progress-bar" role="progressbar" style="width:${t.progress_percent || 0}%"></div>
                                    </div>
                                    <span style="font-size:12px;font-weight:600;">${t.progress_percent || 0}%</span>
                                </div>
                            </td>
                        </tr>
                        <tr><td class="text-muted" style="font-size:12px;">Dibuat</td><td style="font-size:13px;">${t.created_at ? fmt(t.created_at) : '-'}</td></tr>
                        <tr><td class="text-muted" style="font-size:12px;">Selesai</td><td style="font-size:13px;">${t.completed_at ? fmt(t.completed_at) : '-'}</td></tr>
                    </table>
                </div>
                ${t.tags ? `
                <div class="mt-3">
                    <p class="text-muted mb-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Tags</p>
                    <div class="d-flex flex-wrap gap-1">
                        ${(t.tags||'').split(',').filter(Boolean).map(tag => '<span class="badge" style="background:#eef2ff;color:#405189;font-size:11px;">'+tag.trim()+'</span>').join('')}
                    </div>
                </div>` : ''}
                ${t.is_overdue && t.status !== 'selesai' ? `
                <div class="alert alert-danger py-2 mt-3 d-flex align-items-center gap-2" style="font-size:13px;">
                    <i class="ri-error-warning-line"></i>Tugas ini sudah melewati batas waktu.
                </div>` : ''}
            </div>
        </div>`;
    }
    else if (currentTab === 'subtask') {
        html = `
        <div class="tab-pane" id="tab-subtask" role="tabpanel">
            <div class="p-3">
                <div class="input-group mb-3">
                    <input type="text" class="form-control form-control-sm" id="d-subtask-input"
                           placeholder="Tambah subtask..."
                           onkeydown="if(event.key==='Enter'){event.preventDefault();addDSubtask()}">
                    <button class="btn btn-primary btn-sm" onclick="addDSubtask()">
                        <i class="ri-add-line"></i>
                    </button>
                </div>
                <div class="list-group list-group-flush">
                    ${(t.subtasks||[]).length ? t.subtasks.map(st => `
                    <div class="list-group-item px-0 d-flex align-items-center gap-2" id="ds-${st.id}">
                        <input type="checkbox" class="form-check-input flex-shrink-0" style="width:16px;height:16px;"
                               ${st.is_completed?'checked':''} onchange="toggleDSubtask('${st.id}')">
                        <span class="flex-grow-1" style="font-size:13px;${st.is_completed?'text-decoration:line-through;color:#888':''}">${escHtml(st.title)}</span>
                        <button onclick="deleteDSubtask('${st.id}')" class="btn btn-sm btn-ghost-danger px-1 py-0">
                            <i class="ri-delete-bin-2-line" style="font-size:14px;"></i>
                        </button>
                    </div>`).join('') : '<p class="text-muted text-center py-3 mb-0" style="font-size:13px;">Belum ada subtask.</p>'}
                </div>
            </div>
        </div>`;
    }
    else if (currentTab === 'comment') {
        html = `
        <div class="tab-pane" id="tab-comment" role="tabpanel">
            <div class="p-3">
                <div class="mb-3">
                    <textarea class="form-control form-control-sm" id="d-comment-input" rows="2"
                              placeholder="Tulis komentar..." style="resize:none;"></textarea>
                    <button onclick="addDComment()" class="btn btn-primary btn-sm mt-2">
                        <i class="ri-send-plane-line align-bottom me-1"></i>Kirim
                    </button>
                </div>
                ${(t.comments||[]).length ? t.comments.map(c => `
                <div class="d-flex gap-2 mb-3">
                    <div class="avatar-xs flex-shrink-0">
                        <span class="avatar-title-xs bg-primary-subtle text-primary rounded-circle" style="font-size:11px;">
                            ${(c.user?.name||'?').charAt(0).toUpperCase()}
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="bg-light rounded-3 p-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong style="font-size:12px;">${c.user?.name || '-'}</strong>
                                <span class="text-muted" style="font-size:10px;">${fmt(c.created_at)}</span>
                            </div>
                            <p class="mb-0" style="font-size:13px;">${escHtml(c.comment)}</p>
                        </div>
                    </div>
                </div>`).join('') : '<p class="text-muted text-center py-3 mb-0" style="font-size:13px;">Belum ada komentar.</p>'}
            </div>
        </div>`;
    }
    else if (currentTab === 'watcher') {
        html = `
        <div class="tab-pane" id="tab-watcher" role="tabpanel">
            <div class="p-3">
                <div class="input-group mb-3">
                    <select class="form-select form-select-sm" id="d-watcher-select" data-choices data-choices-search-true>
                        <option value="">-- Pilih User --</option>
                        ${allUsers.map(u => `<option value="${u.id}">${u.name}</option>`).join('')}
                    </select>
                    <button class="btn btn-primary btn-sm" onclick="addDWatcher()">
                        <i class="ri-user-add-line"></i>
                    </button>
                </div>
                <div class="list-group list-group-flush">
                    ${(t.watchers||[]).length ? t.watchers.map(w => `
                    <div class="list-group-item px-0 d-flex align-items-center gap-2">
                        <div class="avatar-xs flex-shrink-0">
                            <span class="avatar-title-xs bg-info-subtle text-info rounded-circle" style="font-size:11px;">
                                ${(w.user?.name||'?').charAt(0).toUpperCase()}
                            </span>
                        </div>
                        <span class="flex-grow-1" style="font-size:13px;">${w.user?.name || '-'}</span>
                        <button onclick="removeDWatcher('${w.id}')" class="btn btn-sm btn-ghost-danger px-1 py-0">
                            <i class="ri-user-unfollow-line" style="font-size:15px;"></i>
                        </button>
                    </div>`).join('') : '<p class="text-muted text-center py-3 mb-0" style="font-size:13px;"><i class="ri-eye-line me-1"></i>Belum ada pengamat.</p>'}
                </div>
            </div>
        </div>`;
        setTimeout(initChoices, 50);
    }

    container.innerHTML = html;
}

// ── Detail Actions ───────────────────────────────────────────
async function addDSubtask() {
    const input = document.getElementById('d-subtask-input');
    if (!input?.value.trim() || !currentDetailId) return;
    const res = await fetch('/' + AUTH_ID + '/todos/' + currentDetailId + '/subtasks', {
        method: 'POST',
        headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json','X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ title: input.value.trim() }),
    });
    const data = await res.json();
    if (data.success) { input.value = ''; switchDetailTab('subtask'); }
}

async function toggleDSubtask(stId) {
    await fetch('/' + AUTH_ID + '/todos/' + currentDetailId + '/subtasks/' + stId + '/toggle', {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN':CSRF,'Accept':'application/json','X-Requested-With': 'XMLHttpRequest' }
    });
    switchDetailTab('subtask');
}

async function deleteDSubtask(stId) {
    await fetch('/' + AUTH_ID + '/todos/' + currentDetailId + '/subtasks/' + stId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN':CSRF,'Accept':'application/json','X-Requested-With': 'XMLHttpRequest' }
    });
    document.getElementById('ds-' + stId)?.remove();
}

async function addDComment() {
    const input = document.getElementById('d-comment-input');
    if (!input?.value.trim() || !currentDetailId) return;
    const res = await fetch('/' + AUTH_ID + '/todos/' + currentDetailId + '/comments', {
        method: 'POST',
        headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json','X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ comment: input.value.trim() }),
    });
    const data = await res.json();
    if (data.success) switchDetailTab('comment');
}

async function addDWatcher() {
    const select = document.getElementById('d-watcher-select');
    if (!select?.value || !currentDetailId) return;
    const res = await fetch('/' + AUTH_ID + '/todos/' + currentDetailId + '/watchers', {
        method: 'POST',
        headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json','X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ user_id: select.value }),
    });
    const data = await res.json();
    if (data.success) switchDetailTab('watcher');
    else Swal.fire({ icon:'error', text: data.message });
}

async function removeDWatcher(wId) {
    await fetch('/' + AUTH_ID + '/todos/' + currentDetailId + '/watchers/' + wId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN':CSRF,'Accept':'application/json','X-Requested-With': 'XMLHttpRequest' }
    });
    switchDetailTab('watcher');
}

// ── List Modal ───────────────────────────────────────────────
function openListModal() {
    document.getElementById('list-modal-id').value = '';
    document.getElementById('list-modal-name').value = '';
    document.getElementById('list-modal-title').innerHTML = '<i class="ri-list-check-2 me-2 text-primary align-bottom"></i>Daftar Todo Baru';
    document.querySelectorAll('input[name="list-color"]').forEach((r,i) => r.checked = i === 0);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('listModal')).show();
}

function openEditListModal(id, name, color) {
    document.getElementById('list-modal-id').value = id;
    document.getElementById('list-modal-name').value = name;
    document.getElementById('list-modal-title').innerHTML = '<i class="ri-edit-2-line me-2 text-primary align-bottom"></i>Edit Daftar';
    document.querySelectorAll('input[name="list-color"]').forEach(r => r.checked = r.value === color);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('listModal')).show();
}

document.getElementById('listForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id = document.getElementById('list-modal-id').value;
    const url = id ? '/' + AUTH_ID + '/todos/lists/' + id : '/' + AUTH_ID + '/todos/lists';
    const method = id ? 'PUT' : 'POST';
    const res = await fetch(url, {
        method,
        headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json','X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({
            name: document.getElementById('list-modal-name').value,
            color: document.querySelector('input[name="list-color"]:checked')?.value || '#0ab39c',
        }),
    });
    const data = await res.json();
    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('listModal'))?.hide();
        Swal.fire({ toast:true, position:'top-end', icon:'success', title: data.message, showConfirmButton:false, timer:2000 });
        setTimeout(() => window.location.reload(), 400);
    } else {
        Swal.fire({ icon:'error', text: data.message });
    }
});

// ── Choices.js Init ───────────────────────────────────────────
function initChoices() {
    if (typeof Choices === 'undefined') return;
    ['f-owner','f-list','f-priority','f-status','d-watcher-select'].forEach(id => {
        const el = document.getElementById(id);
        if (el && !el.classList.contains('choices')) {
            new Choices(el, { searchEnabled: el.dataset.choicesSearch === 'true', shouldSort: false });
        }
    });
}
document.addEventListener('DOMContentLoaded', initChoices);
</script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
