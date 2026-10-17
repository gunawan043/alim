@extends('layouts.master')
@section('title') Manajemen User @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') Manajemen User @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Manajemen User</h5>
                            <p class="text-muted mb-0">Kelola semua user sistem.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.sa.users.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah User
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama / email..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="role" class="form-control">
                                <option value="">Semua Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ request('role') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.sa.users.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Roles</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-xs">
                                                    <div class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <strong>{{ $user->name }}</strong>
                                                    <br><small class="text-muted">{{ $user->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @forelse($user->roles as $role)
                                                <span class="badge bg-primary-subtle text-primary mb-1">{{ $role->name }}</span>
                                            @empty
                                                <span class="badge bg-secondary-subtle text-secondary">Tanpa Role</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if($user->is_active)
                                                <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-fill me-1"></i>Aktif</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger"><i class="ri-close-circle-fill me-1"></i>Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.sa.users.edit', ['userId' => $userId, 'id' => $user->id]) }}">
                                                            <i class="ri-pencil-line text-primary me-2"></i>Edit User
                                                        </a>
                                                    </li>
                                                    {{-- Quick manage roles --}}
                                                    <li>
                                                        <button class="dropdown-item manage-roles"
                                                            data-id="{{ $user->id }}"
                                                            data-name="{{ $user->name }}"
                                                            data-roles="{{ $user->roles->pluck('id')->implode(',') }}">
                                                            <i class="ri-shield-check-line text-info me-2"></i>Atur Roles
                                                        </button>
                                                    </li>
                                                    @if(auth()->id() !== $user->id)
                                                        <li>
                                                            <button class="dropdown-item toggle-status" data-id="{{ $user->id }}" data-active="{{ $user->is_active }}">
                                                                <i class="ri-toggle-{{ $user->is_active ? 'fill text-warning' : 'line text-success' }} me-2"></i>
                                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                            </button>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item text-danger delete-user" data-id="{{ $user->id }}" data-name="{{ $user->name }}">
                                                                <i class="ri-delete-bin-line text-danger me-2"></i>Hapus
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada user.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($users->hasPages())
    @include('shared._pagination', ['paginator' => $users])
@endif
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade zoomIn" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">Hapus User?</h4>
                    <p class="text-muted">User <strong id="deleteUserName"></strong> akan dihapus permanen.</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Ya, Hapus!</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Manage Roles Modal --}}
    <div class="modal fade zoomIn" id="manageRolesModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Atur Roles — <span id="manageRolesUserName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="manageRolesForm">
                    <div class="modal-body">
                        <p class="text-muted small mb-3">Pilih role yang ingin diatur ke user ini:</p>
                        <div class="row" id="rolesCheckboxes"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan Roles</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // All available roles for manage-roles modal
        const allRoles = @json($roles);

        // ── Toggle status ──────────────────────────────────────────────
        document.querySelectorAll('.toggle-status').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.dataset.id;
                const active = this.dataset.active === '1';
                const action = active ? 'menonaktifkan' : 'mengaktifkan';
                if (!confirm(`Yakin ingin ${action} user ini?`)) return;

                const res = await fetch(`/{{ $userId }}/sa/users/${id}/toggle-status`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'json' }
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // ── Delete ────────────────────────────────────────────────────
        let deleteId = null;
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', function () {
                deleteId = this.dataset.id;
                document.getElementById('deleteUserName').textContent = this.dataset.name;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });
        });

        document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
            if (!deleteId) return;
            const res = await fetch(`/{{ $userId }}/sa/users/${deleteId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'json' }
            });
            const data = await res.json();
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });

        // ── Manage Roles ──────────────────────────────────────────────
        let manageUserId = null;
        document.querySelectorAll('.manage-roles').forEach(btn => {
            btn.addEventListener('click', function () {
                manageUserId = this.dataset.id;
                const userName = this.dataset.name;
                const currentRoleIds = (this.dataset.roles || '').split(',').filter(Boolean);

                document.getElementById('manageRolesUserName').textContent = userName;

                const container = document.getElementById('rolesCheckboxes');
                container.innerHTML = allRoles.map(role => `
                    <div class="col-6 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                name="roles[]" value="${role.id}"
                                id="modal-role-${role.id}"
                                ${currentRoleIds.includes(role.id) ? 'checked' : ''}>
                            <label class="form-check-label" for="modal-role-${role.id}">
                                ${role.name}
                                <span class="badge bg-secondary-subtle text-secondary" style="font-size:10px">Lv.${role.level}</span>
                            </label>
                        </div>
                    </div>
                `).join('');

                new bootstrap.Modal(document.getElementById('manageRolesModal')).show();
            });
        });

        document.getElementById('manageRolesForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const checked = Array.from(document.querySelectorAll('#manageRolesForm input[name="roles[]"]:checked'))
                .map(cb => cb.value);

            if (checked.length === 0) {
                Swal.fire('Peringatan', 'User harus punya minimal 1 role.', 'warning');
                return;
            }

            const res = await fetch(`/{{ $userId }}/sa/users/${manageUserId}/assign-roles`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ roles: checked }),
            });

            const data = await res.json();
            bootstrap.Modal.getInstance(document.getElementById('manageRolesModal')).hide();

            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire('Error', data.message ?? 'Terjadi kesalahan.', 'error');
            }
        });
    });
    </script>
@endsection
