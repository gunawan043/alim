@extends('layouts.master')
@section('title') Tugas Tambahan Guru @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('title') Tugas Tambahan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Tugas Tambahan Guru</h5>
                            <p class="text-muted mb-0">Wali Kelas, Koordinator, Kesiswaan, dll.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Add Form --}}
                    <form method="POST" action="{{ route('user.other-teacher-tasks.store', ['userId' => $userId]) }}" class="row g-2 mb-4 p-3 border rounded-3 bg-light">
                        @csrf
                        <div class="col-md-3">
                            <select name="teacher_id" class="form-control" required>
                                <option value="">-- Pilih Guru --</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="task_name" class="form-control" placeholder="Nama Tugas (cth: Wali Kelas 7A)" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="weekly_hours" class="form-control" placeholder="Jam/Minggu" min="0" max="40" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="notes" class="form-control" placeholder="Keterangan (opsional)">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-add-line me-1"></i> Tambah
                            </button>
                        </div>
                    </form>

                    {{-- Task List --}}
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Guru</th>
                                    <th>Tugas</th>
                                    <th>Jam/Minggu</th>
                                    <th>Catatan</th>
                                    <th style="width:80px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $task)
                                    <tr>
                                        <td class="fw-medium">{{ $task->teacher?->name ?? '-' }}</td>
                                        <td>{{ $task->task_name }}</td>
                                        <td class="text-center">{{ $task->weekly_hours }} JP</td>
                                        <td class="text-muted" style="font-size:12px">{{ $task->notes ?? '-' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('user.other-teacher-tasks.destroy', ['userId' => $userId, 'id' => $task->id]) }}"
                                                  onsubmit="return confirm('Hapus tugas &quot;{{ $task->task_name }}&quot;?')" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-soft-danger btn-sm">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="ri-file-list-3-line fs-1 mb-2 d-block"></i>
                                            <p class="mb-0">Belum ada tugas tambahan.</p>
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
@endsection