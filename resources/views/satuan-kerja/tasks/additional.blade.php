@extends('layouts.master')
@section('title') Tugas Tambahan GTK — {{ $workUnit->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Satuan Kerja @endslot
        @slot('li_2') {{ $workUnit->name }} @endslot
        @slot('title') Tugas Tambahan GTK @endslot
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
                            <h5 class="card-title mb-0">Tugas Tambahan GTK</h5>
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
                        <div class="col-md-4">
                            <select name="decree_id" class="form-control">
                                <option value="">Semua SK</option>
                                @foreach($decrees as $d)
                                    <option value="{{ $d->id }}" {{ request('decree_id') == $d->id ? 'selected' : '' }}>
                                        {{ $d->decree_number }} — {{ Str::limit($d->title, 40) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.satuan-kerja.additional-tasks', ['workUnitId' => $workUnitId, 'userId' => $userId]) }}"
                               class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Guru</th>
                                    <th>Nama Tugas</th>
                                    <th>Jam/Mgg</th>
                                    <th>SK Referensi</th>
                                    <th>TMT</th>
                                    <th>TST</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $t)
                                <tr>
                                    <td>{{ $loop->iteration + ($tasks->currentPage() - 1) * $tasks->perPage() }}</td>
                                    <td>
                                        <span class="fw-medium">{{ $t->user?->name ?? '-' }}</span>
                                    </td>
                                    <td>{{ $t->nama_tugas }}</td>
                                    <td class="text-center">{{ $t->hours_per_week ?? '-' }}</td>
                                    <td>
                                        @if($t->decree)
                                            <code>{{ $t->decree->decree_number }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $t->tmt ? $t->tmt->format('d M Y') : '-' }}</td>
                                    <td>{{ $t->tst ? $t->tst->format('d M Y') : '<span class="text-muted">Selamanya</span>' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-soft-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $t->id }}">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                        <form method="POST" action="{{ route('user.satuan-kerja.additional-tasks.destroy', ['workUnitId' => $workUnitId, 'userId' => $userId, 'id' => $t->id]) }}"
                                              class="d-inline" onsubmit="return confirm('Hapus tugas tambahan ini?')">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-soft-danger btn-sm delete-btn">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Edit Modal --}}
                                <div class="modal fade" id="modalEdit{{ $t->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Tugas Tambahan</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="{{ route('user.satuan-kerja.additional-tasks.update', ['workUnitId' => $workUnitId, 'userId' => $userId, 'id' => $t->id]) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Guru</label>
                                                        <select name="user_id" class="form-select" required>
                                                            @foreach($teachers as $teacher)
                                                                <option value="{{ $teacher->id }}" {{ $t->user_id == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Tugas</label>
                                                        <input type="text" name="nama_tugas" class="form-control" value="{{ $t->nama_tugas }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">SK Referensi</label>
                                                        <select name="decree_id" class="form-select">
                                                            <option value="">-- Pilih SK --</option>
                                                            @foreach($decrees as $decree)
                                                                <option value="{{ $decree->id }}" {{ $t->decree_id == $decree->id ? 'selected' : '' }}>
                                                                    {{ $decree->decree_number }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Jam/Minggu</label>
                                                        <input type="number" name="hours_per_week" class="form-control" value="{{ $t->hours_per_week ?? '' }}" min="0" max="40">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">TMT</label>
                                                        <input type="date" name="tmt" class="form-control" value="{{ $t->tmt?->format('Y-m-d') }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">TST</label>
                                                        <input type="date" name="tst" class="form-control" value="{{ $t->tst?->format('Y-m-d') }}">
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
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="ri-folder-open-line fs-1 d-block mb-2"></i>
                                        Belum ada data tugas tambahan.
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
                <form method="POST" action="{{ route('user.satuan-kerja.additional-tasks.store', ['workUnitId' => $workUnitId, 'userId' => $userId]) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Guru <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- Pilih Guru --</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Tugas <span class="text-danger">*</span></label>
                            <input type="text" name="nama_tugas" class="form-control" required maxlength="150">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SK Referensi</label>
                            <select name="decree_id" class="form-select">
                                <option value="">-- Pilih SK --</option>
                                @foreach($decrees as $decree)
                                    <option value="{{ $decree->id }}">{{ $decree->decree_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jam/Minggu</label>
                            <input type="number" name="hours_per_week" class="form-control" min="0" max="40">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">TMT</label>
                            <input type="date" name="tmt" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">TST</label>
                            <input type="date" name="tst" class="form-control">
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
