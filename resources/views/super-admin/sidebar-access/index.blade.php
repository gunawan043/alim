@extends('layouts.master')
@section('title') Sidebar Access @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') Roles & Permissions @endslot
        @slot('title') Sidebar Access @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Sidebar Access</h5>
                            <p class="text-muted mb-0">Kelola akses menu sidebar per role.</p>
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createMenuModal">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Menu
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nama Menu</th>
                                    <th>Menu Key</th>
                                    <th>Roles yang Bisa Akses</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accesses as $access)
                                    <tr>
                                        <td><strong>{{ $access->display_name }}</strong></td>
                                        <td><code>{{ $access->menu_key }}</code></td>
                                        <td>
                                            @if(empty($access->allowed_roles))
                                                <span class="badge bg-warning-subtle text-warning">Semua Role</span>
                                            @else
                                                @foreach($access->allowed_roles as $role)
                                                    <span class="badge bg-primary-subtle text-primary">{{ $role }}</span>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item edit-sidebar-access"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editAccessModal"
                                                                data-menu-key="{{ $access->menu_key }}"
                                                                data-display-name="{{ $access->display_name }}"
                                                                data-allowed-roles='@json($access->allowed_roles ?? [])'>
                                                            <i class="ri-edit-line me-1"></i> Edit
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item text-danger delete-sidebar-access"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteAccessModal"
                                                                data-menu-key="{{ $access->menu_key }}"
                                                                data-display-name="{{ $access->display_name }}">
                                                            <i class="ri-delete-bin-line me-1"></i> Hapus
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <span class="text-muted">Belum ada akses sidebar. Klik "Tambah Menu" untuk menambahkan.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Menu Modal --}}
    <div class="modal fade zoomIn" id="createMenuModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Menu Sidebar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('super-admin.sidebar-access.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Menu <span class="text-danger">*</span></label>
                            <input type="text" name="display_name" class="form-control" placeholder="Contoh: Laporan Keuangan" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Menu Key <span class="text-danger">*</span></label>
                            <input type="text" name="menu_key" class="form-control" placeholder="Contoh: laporan-keuangan" required>
                            <small class="text-muted">Huruf kecil, gunakan tanda strip untuk pemisah.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Roles yang Bisa Akses</label>
                            <select name="allowed_roles[]" class="form-select" multiple size="5">
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Kosongkan jika semua role bisa akses.</small>
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

    {{-- Edit Access Modal --}}
    <div class="modal fade zoomIn" id="editAccessModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Akses Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editAccessForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Menu</label>
                            <input type="text" class="form-control" id="editDisplayName" readonly>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label mb-0">Roles yang Bisa Akses</label>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" id="selectAllRoles">
                                    <label class="form-check-label small" for="selectAllRoles">Pilih Semua</label>
                                </div>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" id="deselectAllRoles">
                                    <label class="form-check-label small" for="deselectAllRoles">Kosongkan</label>
                                </div>
                            </div>
                            <div id="editRoleCheckboxes" class="border rounded p-2" style="max-height: 220px; overflow-y: auto;">
                                @foreach($roles as $role)
                                    <div class="form-check">
                                        <input class="form-check-input role-checkbox" type="checkbox" name="allowed_roles[]" value="{{ $role->name }}" id="edit_role_{{ strtolower(str_replace(' ', '-', $role->name)) }}">
                                        <label class="form-check-label" for="edit_role_{{ strtolower(str_replace(' ', '-', $role->name)) }}">
                                            {{ $role->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted">Kosongkan jika semua role bisa akses.</small>
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

    {{-- Delete Access Modal --}}
    <div class="modal fade zoomIn" id="deleteAccessModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">Hapus Menu?</h4>
                    <p class="text-muted">Menu <strong id="deleteAccessName"></strong> akan dihapus permanen.</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteAccessForm" method="POST" style="display:inline">
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
        // Edit modal — populate checkboxes
        document.querySelectorAll('.edit-sidebar-access').forEach(btn => {
            btn.addEventListener('click', function () {
                var menuKey = this.dataset.menuKey;
                var displayName = this.dataset.displayName;
                var allowedRoles = JSON.parse(this.dataset.allowedRoles);

                document.getElementById('editDisplayName').value = displayName;
                document.getElementById('editAccessForm').action = `/super-admin/sidebar-access/${menuKey}`;

                document.querySelectorAll('.role-checkbox').forEach(cb => {
                    cb.checked = allowedRoles.includes(cb.value);
                });
            });
        });

        // "Pilih Semua" / "Kosongkan" helpers
        document.getElementById('selectAllRoles').addEventListener('change', function () {
            document.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = this.checked);
        });
        document.getElementById('deselectAllRoles').addEventListener('change', function () {
            document.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = false);
        });

        // Delete modal
        document.querySelectorAll('.delete-sidebar-access').forEach(btn => {
            btn.addEventListener('click', function () {
                var menuKey = this.dataset.menuKey;
                var displayName = this.dataset.displayName;

                document.getElementById('deleteAccessName').textContent = displayName;
                document.getElementById('deleteAccessForm').action = `/super-admin/sidebar-access/${menuKey}`;
            });
        });
    });
    </script>
@endsection
