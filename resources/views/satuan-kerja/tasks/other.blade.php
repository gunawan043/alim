@extends('layouts.master')
@section('title') Tugas Tambahan Guru — {{ $workUnit->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Satuan Kerja @endslot
        @slot('li_2') {{ $workUnit->name }} @endslot
        @slot('title') Tugas Tambahan Guru @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Tugas Tambahan Guru</h5>
                            <p class="text-muted mb-0">Satuan Kerja: <strong>{{ $workUnit->name }}</strong></p>
                        </div>
                        <div class="col-sm-auto">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
                                <i class="ri-add-line me-1"></i> Tambah Tugas
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <select name="teacher_id" class="form-control">
                                <option value="">Semua Guru</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.satuan-kerja.other-tasks', ['workUnitId' => $workUnitId, 'userId' => $userId]) }}"
                               class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Guru</th>
                                    <th>Tugas</th>
                                    <th>Jam/Minggu</th>
                                    <th>Keterangan</th>
                                    <th style="width:80px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $task)
                                <tr>
                                    <td>{{ $loop->iteration + ($tasks->currentPage() - 1) * $tasks->perPage() }}</td>
                                    <td class="fw-medium">{{ $task->teacher?->name ?? '-' }}</td>
                                    <td>{{ $task->task_name }}</td>
                                    <td class="text-center">{{ $task->weekly_hours }} JP</td>
                                    <td class="text-muted small">{{ $task->notes ?? '-' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-soft-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $task->id }}">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                        <form method="POST" action="{{ route('user.satuan-kerja.other-tasks.destroy', ['workUnitId' => $workUnitId, 'userId' => $userId, 'id' => $task->id]) }}"
                                              class="d-inline" onsubmit="return confirm('Hapus tugas ini?')">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-soft-danger btn-sm delete-btn">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Edit Modal --}}
                                <div class="modal fade" id="modalEdit{{ $task->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Tugas</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="{{ route('user.satuan-kerja.other-tasks.update', ['workUnitId' => $workUnitId, 'userId' => $userId, 'id' => $task->id]) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Tugas</label>
                                                        <input type="text" name="task_name" class="form-control" value="{{ $task->task_name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Jam/Minggu</label>
                                                        <input type="number" name="weekly_hours" class="form-control" value="{{ $task->weekly_hours }}" min="0" max="40" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Keterangan</label>
                                                        <input type="text" name="notes" class="form-control" value="{{ $task->notes ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="ri-folder-open-line fs-1 d-block mb-2"></i>
                                        Belum ada data tugas tambahan guru.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('shared._pagination', ['paginator' => $tasks])
                </div>
            </div>
        </div>
    </div>

    {{-- Add Modal --}}
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Tugas Tambahan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('user.satuan-kerja.other-tasks.store', ['workUnitId' => $workUnitId, 'userId' => $userId]) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Guru <span class="text-danger">*</span></label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">-- Pilih Guru --</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Tugas <span class="text-danger">*</span></label>
                            <input type="text" name="task_name" class="form-control" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jam/Minggu <span class="text-danger">*</span></label>
                            <input type="number" name="weekly_hours" class="form-control" required min="0" max="40">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <input type="text" name="notes" class="form-control" maxlength="255">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
