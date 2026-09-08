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
                                                <div class="modal-header bg-primary-subtle">
                                                    <h5 class="modal-title"><i class="ri-edit-line me-1"></i> Edit Role: <span class="fw-bold">{{ $role->name }}</span></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="{{ route('user.sa.roles.update', ['userId' => $userId, 'id' => $role->id]) }}">
                                                    @csrf @method('PUT')
                                                    <div class="modal-body" style="max-height:70vh;overflow-y:auto">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Nama Role <span class="text-danger">*</span></label>
                                                                <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Level <span class="text-danger">*</span></label>
                                                                <input type="number" name="level" class="form-control" value="{{ $role->level }}" min="0" max="100" required>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Deskripsi</label>
                                                                <input type="text" name="description" class="form-control" value="{{ $role->description ?? '' }}">
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label fw-semibold">Permissions <span class="badge bg-secondary fs-6 ms-1" id="permCount-{{ $role->id }}">{{ $role->permissions->count() }}/{{ $permissions->count() }}</span></label>
                                                                <div class="position-relative mb-2">
                                                                    <i class="ri-search-line text-muted position-absolute" style="left:10px;top:10px;z-index:2;pointer-events:none"></i>
                                                                    <input type="text" class="form-control perm-search" placeholder="Cari permission (misal: user_view, gtk_edit)..." data-role-id="{{ $role->id }}" style="padding-left:32px;font-size:13px">
                                                                </div>
                                                                <div class="d-flex gap-2 mb-2 flex-wrap">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary perm-btn-all" data-role-id="{{ $role->id }}">Pilih Semua</button>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary perm-btn-none" data-role-id="{{ $role->id }}">Kosongkan</button>
                                                                    <button type="button" class="btn btn-sm btn-outline-success perm-btn-invert" data-role-id="{{ $role->id }}">Balik</button>
                                                                    <button type="button" class="btn btn-sm btn-outline-info perm-btn-all-groups" data-role-id="{{ $role->id }}">Pilih Semua Grup</button>
                                                                    <button type="button" class="btn btn-sm btn-outline-warning perm-btn-none-groups" data-role-id="{{ $role->id }}">Kosongkan Semua Grup</button>
                                                                </div>
                                                                <div class="perm-groups role-perms" data-role-id="{{ $role->id }}">
                                                                    @foreach($groupedPermissions as $groupName => $perms)
                                                                        <div class="perm-group mb-1 border rounded">
                                                                            <div class="d-flex align-items-center gap-2 py-1 px-2 bg-light" style="cursor:pointer;border-radius:6px 6px 0 0" data-group-toggle="{{ $role->id }}-{{ Str::slug($groupName) }}">
                                                                                <i class="ri-arrow-down-s-line toggle-icon" style="font-size:14px;transition:transform 0.2s"></i>
                                                                                <input type="checkbox" class="form-check-input me-1 group-check" data-role="{{ $role->id }}" data-group="{{ Str::slug($groupName) }}" {{ $perms->every(fn($p) => $role->permissions->contains('id', $p->id)) ? 'checked' : '' }}>
                                                                                <strong style="font-size:12px;flex:1">{{ $groupName }}</strong>
                                                                                <small class="text-muted group-count">{{ $perms->count() }} items</small>
                                                                            </div>
                                                                            <div class="perm-group-items ps-3 pt-1 pb-1 bg-white" style="display:none;border-radius:0 0 6px 6px;border:1px solid #dee2e6;border-top:none">
                                                                                @foreach($perms as $perm)
                                                                                    <div class="form-check perm-item py-1" data-group="{{ Str::slug($groupName) }}" data-name="{{ strtolower($perm->name) }}" data-role="{{ $role->id }}">
                                                                                        <input class="form-check-input perm-check" type="checkbox"
                                                                                            name="permissions[]" value="{{ $perm->id }}"
                                                                                            id="perm-{{ $role->id }}-{{ $perm->id }}"
                                                                                            data-role="{{ $role->id }}"
                                                                                            data-group="{{ Str::slug($groupName) }}"
                                                                                            {{ $role->permissions->contains('id', $perm->id) ? 'checked' : '' }}>
                                                                                        <label class="form-check-label small text-dark" for="perm-{{ $role->id }}-{{ $perm->id }}">{{ $perm->name }}</label>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan</button>
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
                    <form id="deleteRoleForm" method="POST" style="display:inline" action="">
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
        // Delete role modal
        document.querySelectorAll('.delete-role').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('deleteRoleName').textContent = this.dataset.name;
                const roleId = this.dataset.id;
                // Use exact route pattern to avoid 404
                document.getElementById('deleteRoleForm').action = `/{{ $userId }}/sa/roles/` + roleId;
                new bootstrap.Modal(document.getElementById('deleteRoleModal')).show();
            });
        });

        // Edit modal: expand all groups on open, scroll to top
        document.addEventListener('shown.bs.modal', function(e) {
            const modal = e.target;
            if (!modal.id || !modal.id.startsWith('editRoleModal-')) return;

            // Expand all permission groups
            modal.querySelectorAll('.perm-group-items').forEach(el => el.style.display = 'block');
            modal.querySelectorAll('.toggle-icon').forEach(el => el.style.transform = 'rotate(180deg)');
            modal.querySelector('.modal-body').scrollTop = 0;

            // Reset search
            modal.querySelectorAll('.perm-search').forEach(i => i.value = '');
        });

        // Group header click → toggle items (expand/collapse)
        document.addEventListener('click', function(e) {
            const groupEl = e.target.closest('.perm-group');
            if (!groupEl) return;

            const modal = groupEl.closest('.modal');
            if (!modal) return;

            // Don't toggle if clicking the checkbox itself
            if (e.target.classList.contains('group-check')) {
                // Let the change event handle it
                return;
            }

            // Toggle visibility of group items
            const items = groupEl.querySelector('.perm-group-items');
            if (!items) return;

            const show = items.style.display === 'none';
            items.style.display = show ? 'block' : 'none';
            const icon = groupEl.querySelector('.toggle-icon');
            if (icon) icon.style.transform = show ? 'rotate(180deg)' : '';
        });

        // Group checkbox → toggle all items in that group (including hidden)
        document.addEventListener('change', function(e) {
            if (!e.target.classList.contains('group-check')) return;

            const checkbox = e.target;
            const modal = checkbox.closest('.modal');
            const roleId = checkbox.dataset.role;
            const group = checkbox.dataset.group;
            const checked = checkbox.checked;

            // Toggle ALL checkboxes in the same group within this modal
            if (modal && roleId && group) {
                modal.querySelectorAll(`.perm-check[data-role="${roleId}"][data-group="${group}"]`).forEach(cb => {
                    cb.checked = checked;
                });
            }
            updatePermCount(roleId);
            updateGroupIndicators(roleId);
        });

        // Individual permission checkbox → update group indicator
        document.addEventListener('change', function(e) {
            if (!e.target.classList.contains('perm-check')) return;

            const checkbox = e.target;
            const modal = checkbox.closest('.modal');
            const roleId = checkbox.dataset.role;
            const group = checkbox.dataset.group;

            if (modal && roleId && group) {
                updateGroupIndicators(roleId);
            }
            updatePermCount(roleId);
        });

        // Search → filter permissions across all groups
        document.querySelectorAll('.perm-search').forEach(input => {
            input.addEventListener('input', function() {
                const roleId = this.dataset.roleId;
                const query = this.value.toLowerCase().trim();
                const rolePerms = this.closest('.modal-body').querySelector('.role-perms');
                rolePerms.querySelectorAll('.perm-group').forEach(group => {
                    let groupVisible = false;
                    group.querySelectorAll('.perm-item').forEach(item => {
                        const name = item.dataset.name;
                        const cb = item.querySelector('.perm-check');
                        const match = !query || name.includes(query);
                        item.style.display = match ? '' : 'none';
                        if (match) {
                            groupVisible = true;
                            if (cb) cb.checked = false;
                        }
                    });
                    group.style.display = groupVisible ? '' : 'none';
                });
                if (query) {
                    rolePerms.querySelectorAll('.perm-group-items').forEach(el => el.style.display = 'block');
                    rolePerms.querySelectorAll('.toggle-icon').forEach(el => el.style.transform = 'rotate(180deg)');
                }
                updatePermCount(roleId);
            });
        });

        // Bulk action buttons: all / none / invert / all-groups / none-groups (using event delegation)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.perm-btn-all, .perm-btn-none, .perm-btn-invert, .perm-btn-all-groups, .perm-btn-none-groups');
            if (!btn) return;

            const modal = btn.closest('.modal');
            const roleId = modal ? modal.id.replace('editRoleModal-', '') : btn.dataset.roleId;
            const action = btn.classList.contains('perm-btn-all') ? 'all'
                         : btn.classList.contains('perm-btn-none') ? 'none'
                         : btn.classList.contains('perm-btn-invert') ? 'invert'
                         : btn.classList.contains('perm-btn-all-groups') ? 'all-groups'
                         : 'none-groups';

            if (action === 'all-groups') {
                // Expand all groups and check all checkboxes
                modal.querySelectorAll('.perm-group-items').forEach(el => el.style.display = 'block');
                modal.querySelectorAll('.toggle-icon').forEach(el => el.style.transform = 'rotate(180deg)');
                modal.querySelectorAll(`.perm-check[data-role="${roleId}"]`).forEach(cb => cb.checked = true);
            } else if (action === 'none-groups') {
                // Collapse all groups and uncheck all checkboxes
                modal.querySelectorAll('.perm-group-items').forEach(el => el.style.display = 'none');
                modal.querySelectorAll('.toggle-icon').forEach(el => el.style.transform = '');
                modal.querySelectorAll(`.perm-check[data-role="${roleId}"]`).forEach(cb => cb.checked = false);
            } else {
                const checkboxes = modal.querySelectorAll(`.perm-check[data-role="${roleId}"]`);
                checkboxes.forEach(cb => {
                    if (action === 'all') cb.checked = true;
                    else if (action === 'none') cb.checked = false;
                    else cb.checked = !cb.checked;
                });
            }
            updatePermCount(roleId);
            updateGroupIndicators(roleId);
        });

        // Create modal: check all per group
        document.querySelectorAll('[id^="checkAll-"]').forEach(cb => {
            cb.addEventListener('change', function () {
                const group = this.id.replace('checkAll-', '');
                document.querySelectorAll(`.perm-check[data-group="${group}"]`).forEach(c => c.checked = this.checked);
            });
        });

        function updatePermCount(roleId) {
            const total = document.querySelectorAll(`.perm-check[data-role="${roleId}"]`).length;
            const checked = document.querySelectorAll(`.perm-check[data-role="${roleId}"]:checked`).length;
            const badge = document.getElementById(`permCount-${roleId}`);
            if (badge) badge.textContent = `${checked}/${total}`;
        }

        function updateGroupIndicators(roleId) {
            // Update group checkboxes based on their children
            document.querySelectorAll(`.group-check[data-role="${roleId}"]`).forEach(groupCb => {
                const group = groupCb.dataset.group;
                const parentModal = groupCb.closest('.modal');
                if (!parentModal) return;

                const allInGroup = parentModal.querySelectorAll(`.perm-check[data-role="${roleId}"][data-group="${group}"]`);
                const checkedInGroup = parentModal.querySelectorAll(`.perm-check[data-role="${roleId}"][data-group="${group}"]:checked`);

                if (allInGroup.length === 0) {
                    groupCb.checked = false;
                    groupCb.indeterminate = false;
                } else if (checkedInGroup.length === allInGroup.length) {
                    groupCb.checked = true;
                    groupCb.indeterminate = false;
                } else if (checkedInGroup.length > 0) {
                    groupCb.checked = false;
                    groupCb.indeterminate = true;
                } else {
                    groupCb.checked = false;
                    groupCb.indeterminate = false;
                }
            });
        }

        // Show success/error notifications from session
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '{{ session('error') }}',
                showConfirmButton: true
            });
        @endif
    });
    </script>
@endsection
