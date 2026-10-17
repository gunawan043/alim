@extends('layouts.master')
@section('title') Permissions @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') Roles & Permissions @endslot
        @slot('title') Permissions @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Permissions</h5>
                            <p class="text-muted mb-0">Kelola permission sistem.</p>
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPermModal">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Permission
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari permission..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="group" class="form-control">
                                <option value="">Semua Group</option>
                                @foreach($groups as $g)
                                    <option value="{{ $g }}" {{ request('group') == $g ? 'selected' : '' }}>{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.sa.permissions.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Permission</th>
                                    <th>Group</th>
                                    <th>Guard</th>
                                    <th>Roles</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permissions as $perm)
                                    <tr>
                                        <td><code>{{ $perm->name }}</code></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $perm->group ?? '-' }}</span></td>
                                        <td><span class="badge bg-light text-dark">{{ $perm->guard_name }}</span></td>
                                        <td>
                                            @forelse($perm->roles->take(2) as $r)
                                                <span class="badge bg-primary-subtle text-primary">{{ $r->name }}</span>
                                            @empty
                                                <span class="text-muted small">Tanpa role</span>
                                            @endforelse
                                            @if($perm->roles()->count() > 2)
                                                <span class="badge bg-light text-dark">+{{ $perm->roles()->count() - 2 }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal"
                                                            data-bs-target="#editPermModal-{{ $perm->id }}">
                                                            <i class="ri-pencil-line text-primary me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    @if($perm->roles()->count() == 0)
                                                        <li>
                                                            <button class="dropdown-item text-danger delete-perm"
                                                                data-id="{{ $perm->id }}" data-name="{{ $perm->name }}">
                                                                <i class="ri-delete-bin-line text-danger me-2"></i>Hapus
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Edit Modal --}}
                                    <div class="modal fade zoomIn" id="editPermModal-{{ $perm->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Permission</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="{{ route('user.sa.permissions.update', ['userId' => $userId, 'id' => $perm->id]) }}">
                                                    @csrf @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nama Permission</label>
                                                            <input type="text" name="name" class="form-control" value="{{ $perm->name }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Group</label>
                                                            <input type="text" name="group" class="form-control" value="{{ $perm->group }}" placeholder="Contoh: GTK, Master Data">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Deskripsi</label>
                                                            <textarea name="description" class="form-control" rows="2">{{ $perm->description }}</textarea>
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
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada permission.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($permissions->hasPages())
    @include('shared._pagination', ['paginator' => $permissions])
@endif
                </div>
            </div>
        </div>
    </div>

    {{-- Create Permission Modal --}}
    <div class="modal fade zoomIn" id="createPermModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Permission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('user.sa.permissions.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Permission <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: gtk.view_any" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Group</label>
                            <input type="text" name="group" class="form-control" placeholder="Contoh: GTK, Master Data">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
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

    {{-- Delete Permission Modal --}}
    <div class="modal fade zoomIn" id="deletePermModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">Hapus Permission?</h4>
                    <p class="text-muted">Permission <strong id="deletePermName"></strong> akan dihapus permanen.</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <form id="deletePermForm" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">Ya, Hapus!</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-perm').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('deletePermName').textContent = this.dataset.name;
                document.getElementById('deletePermForm').action = `/{{ $userId }}/sa/permissions/${this.dataset.id}`;
                new bootstrap.Modal(document.getElementById('deletePermModal')).show();
            });
        });
    });
    </script>
@endsection
