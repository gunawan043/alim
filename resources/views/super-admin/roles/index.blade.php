@extends('layouts.master')
@section('title') Roles & Permissions @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') Roles & Permissions @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Roles & Permissions</h5>
                            <p class="text-muted mb-0">Kelola roles dan hak akses sistem.</p>
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Role
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Cari role..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.sa.roles.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Level</th>
                                    <th>Deskripsi</th>
                                    <th>Jumlah User</th>
                                    <th>Permissions</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $role)
                                    <tr>
                                        <td>
                                            <strong>{{ $role->name }}</strong>
                                            @if(strtolower($role->name) === 'super admin')
                                                <span class="badge bg-danger-subtle text-danger ms-1">Protected</span>
                                            @endif
                                        </td>
                                        <td>{{ $role->level }}</td>
                                        <td><small class="text-muted">{{ $role->description ?? '-' }}</small></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $role->users()->count() }}</span></td>
                                        <td>
                                            @forelse($role->permissions->take(3) as $perm)
                                                <span class="badge bg-primary-subtle text-primary small">{{ $perm->name }}</span>
                                            @empty
                                                <span class="text-muted small">Tanpa permission</span>
                                            @endforelse
                                            @if($role->permissions->count() > 3)
                                                <span class="badge bg-light text-dark">+{{ $role->permissions->count() - 3 }}</span>
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
                                                            data-bs-target="#editRoleModal-{{ $role->id }}">
                                                            <i class="ri-pencil-line text-primary me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    @if(strtolower($role->name) !== 'super admin')
                                                        @if($role->users()->count() == 0)
                                                            <li>
                                                                <button class="dropdown-item text-danger delete-role"
                                                                    data-id="{{ $role->id }}" data-name="{{ $role->name }}">
                                                                    <i class="ri-delete-bin-line text-danger me-2"></i>Hapus
                                                                </button>
                                                            </li>
                                                        @endif
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Edit Role Modal --}}
                                    <div class="modal fade zoomIn" id="editRoleModal-{{ $role->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-xl modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Role: {{ $role->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="{{ route('user.sa.roles.update', ['userId' => $userId, 'id' => $role->id]) }}">
                                                    @csrf @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Nama Role</label>
                                                                <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Level</label>
                                                                <input type="number" name="level" class="form-control" value="{{ $role->level }}" min="0" max="100" required>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label class="form-label">Deskripsi</label>
                                                                <textarea name="description" class="form-control" rows="2">{{ $role->description }}</textarea>
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label">Permissions</label>
                                                                <div class="row">
                                                                    @foreach($groupedPermissions as $groupName => $perms)
                                                                        <div class="col-md-4 mb-2">
                                                                            <div class="card border">
                                                                                <div class="card-header py-1 px-2 bg-light">
                                                                                    <strong style="font-size:11px">{{ $groupName }}</strong>
                                                                                </div>
                                                                                <div class="card-body py-2 px-2">
                                                                                    @foreach($perms as $perm)
                                                                                        <div class="form-check">
                                                                                            <input class="form-check-input" type="checkbox"
                                                                                                name="permissions[]" value="{{ $perm->id }}"
                                                                                                id="perm-{{ $role->id }}-{{ $perm->id }}"
                                                                                                {{ $role->permissions->contains('id', $perm->id) ? 'checked' : '' }}>
                                                                                            <label class="form-check-label" for="perm-{{ $role->id }}-{{ $perm->id }}" style="font-size:12px">
                                                                                                {{ $perm->name }}
                                                                                            </label>
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
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
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada role.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($roles->hasPages())
    @include('shared._pagination', ['paginator' => $roles])
@endif
                </div>
            </div>
        </div>
    </div>

    {{-- Create Role Modal --}}
    <div class="modal fade zoomIn" id="createRoleModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Role Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('user.sa.roles.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Role <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Level <span class="text-danger">*</span></label>
                                <input type="number" name="level" class="form-control" value="1" min="0" max="100" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Permissions</label>
                                <div class="row">
                                    @foreach($groupedPermissions as $groupName => $perms)
                                        <div class="col-md-4 mb-2">
                                            <div class="card border">
                                                <div class="card-header py-1 px-2 bg-light">
                                                    <div class="form-check mb-0" style="font-size:11px">
                                                        <input class="form-check-input" type="checkbox" id="checkAll-{{ Str::slug($groupName) }}">
                                                        <label class="form-check-label fw-bold" for="checkAll-{{ Str::slug($groupName) }}">{{ $groupName }}</label>
                                                    </div>
                                                </div>
                                                <div class="card-body py-2 px-2">
                                                    @foreach($perms as $perm)
                                                        <div class="form-check">
                                                            <input class="form-check-input perm-check" type="checkbox"
                                                                name="permissions[]" value="{{ $perm->id }}"
                                                                id="create-perm-{{ $perm->id }}"
                                                                data-group="{{ Str::slug($groupName) }}">
                                                            <label class="form-check-label" for="create-perm-{{ $perm->id }}" style="font-size:12px">
                                                                {{ $perm->name }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
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

    {{-- Delete Role Modal --}}
    <div class="modal fade zoomIn" id="deleteRoleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">Hapus Role?</h4>
                    <p class="text-muted">Role <strong id="deleteRoleName"></strong> akan dihapus permanen.</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteRoleForm" method="POST" style="display:inline">
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
        // Delete role
        document.querySelectorAll('.delete-role').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('deleteRoleName').textContent = this.dataset.name;
                document.getElementById('deleteRoleForm').action = `/{{ $userId }}/sa/roles/${this.dataset.id}`;
                new bootstrap.Modal(document.getElementById('deleteRoleModal')).show();
            });
        });

        // Check all per group (create modal)
        document.querySelectorAll('[id^="checkAll-"]').forEach(cb => {
            cb.addEventListener('change', function () {
                const group = this.id.replace('checkAll-', '');
                document.querySelectorAll(`.perm-check[data-group="${group}"]`).forEach(c => c.checked = this.checked);
            });
        });
    });
    </script>
@endsection
