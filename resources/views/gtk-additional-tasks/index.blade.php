@extends('layouts.master')
@section('title') Tugas Tambahan GTK @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}">Surat Keputusan</a> @endslot
        @slot('title') Tugas Tambahan GTK @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
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
                            <p class="text-muted mb-0">Kelola tugas tambahan seperti Wali Kelas, Koordinator, Waka, dll.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.gtk-additional-tasks.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Tugas
                            </a>
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
                                    <option value="{{ $d->id }}" {{ request('decree_id') == $d->id ? 'selected' : '' }}>{{ $d->decree_number }} — {{ Str::limit($d->title, 40) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
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
                                            <br><small class="text-muted">{{ $t->user?->getRoleNames()->first() ?? '' }}</small>
                                        </td>
                                        <td>{{ $t->nama_tugas }}</td>
                                        <td class="text-center">{{ $t->hours_per_week ?? '-' }} JP</td>
                                        <td>
                                            @if($t->decree)
                                                <code>{{ $t->decree->decree_number }}</code>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $t->tmt?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $t->tst?->format('d/m/Y') ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('user.gtk-additional-tasks.edit', ['userId' => $userId, 'id' => $t->id]) }}"
                                               class="btn btn-soft-primary btn-sm">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <form method="POST" action="{{ route('user.gtk-additional-tasks.destroy', ['userId' => $userId, 'id' => $t->id]) }}"
                                                  class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="button" class="btn btn-soft-danger btn-sm delete-btn">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
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
@endsection
