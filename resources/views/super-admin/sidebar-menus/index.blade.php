@extends('layouts.master')
@section('title') Kelola Menu Sidebar @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') Kelola Menu Sidebar @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            {{-- Tree View --}}
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Struktur Menu Sidebar</h5>
                            <p class="text-muted mb-0">Atur menu yang muncul di sidebar aplikasi.</p>
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createMenuModal">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Menu
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama / route..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="is_active" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.sa.sidebar-menus.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px">Icon</th>
                                    <th>Nama Menu</th>
                                    <th>Route / URL</th>
                                    <th>Parent</th>
                                    <th>Roles</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($menus as $menu)
                                    <tr>
                                        <td>
                                            @if($menu->icon)
                                                <i class="{{ $menu->icon }} text-muted"></i>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $menu->name }}</strong>
                                            @if($menu->is_group_header)
                                                <span class="badge bg-secondary-subtle text-secondary ms-1">Header</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($menu->route)
                                                <code style="font-size:11px">{{ $menu->route }}</code>
                                            @elseif($menu->url)
                                                <small class="text-muted">{{ $menu->url }}</small>
                                            @else
                                                <span class="badge bg-light text-dark">Group only</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($menu->parent)
                                                <span class="badge bg-light text-dark">{{ $menu->parent->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse($menu->roles->take(2) as $role)
                                                <span class="badge bg-primary-subtle text-primary">{{ $role->name }}</span>
                                            @empty
                                                <span class="badge bg-success-subtle text-success">Semua Role</span>
                                            @endforelse
                                            @if($menu->roles->count() > 2)
                                                <span class="badge bg-light text-dark">+{{ $menu->roles->count() - 2 }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $menu->order }}</td>
                                        <td>
                                            @if($menu->is_active)
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
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
                                                            data-bs-target="#editMenuModal-{{ $menu->id }}">
                                                            <i class="ri-pencil-line text-primary me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger delete-menu"
                                                            data-id="{{ $menu->id }}" data-name="{{ $menu->name }}">
                                                            <i class="ri-delete-bin-line text-danger me-2"></i>Hapus
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Edit Menu Modal --}}
                                    <div class="modal fade zoomIn" id="editMenuModal-{{ $menu->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Menu: {{ $menu->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="{{ route('user.sa.sidebar-menus.update', ['userId' => $userId, 'id' => $menu->id]) }}">
                                                    @csrf @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-8">
                                                                <label class="form-label">Nama Menu</label>
                                                                <input type="text" name="name" class="form-control" value="{{ $menu->name }}" required>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Order</label>
                                                                <input type="number" name="order" class="form-control" value="{{ $menu->order }}" min="0">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Route</label>
                                                                <input type="text" name="route" class="form-control" value="{{ $menu->route }}" placeholder="user.gtk.index">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">URL (alternatif)</label>
                                                                <input type="text" name="url" class="form-control" value="{{ $menu->url }}" placeholder="https://...">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Icon (Remix Icon class)</label>
                                                                <input type="text" name="icon" class="form-control" value="{{ $menu->icon }}" placeholder="ri-user-line">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Parent Menu</label>
                                                                <select name="parent_id" class="form-control">
                                                                    <option value="">Tidak ada (Menu Utama)</option>
                                                                    @foreach($parentMenus->where('id', '!=', $menu->id) as $parent)
                                                                        <option value="{{ $parent->id }}" {{ $menu->parent_id == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label">Roles yang bisa melihat</label>
                                                                <div class="row">
                                                                    @foreach($roles as $role)
                                                                        <div class="col-md-3">
                                                                            <div class="form-check">
                                                                                <input class="form-check-input" type="checkbox"
                                                                                    name="roles[]" value="{{ $role->id }}"
                                                                                    id="edit-role-{{ $menu->id }}-{{ $role->id }}"
                                                                                    {{ $menu->roles->contains('id', $role->id) ? 'checked' : '' }}>
                                                                                <label class="form-check-label" for="edit-role-{{ $menu->id }}-{{ $role->id }}">{{ $role->name }}</label>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="is_group_header" value="1" id="edit-header-{{ $menu->id }}"
                                                                        {{ $menu->is_group_header ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="edit-header-{{ $menu->id }}">Header Group (tanpa route)</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit-active-{{ $menu->id }}"
                                                                        {{ $menu->is_active ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="edit-active-{{ $menu->id }}">Menu Aktif</label>
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
                                        <td colspan="8" class="text-center py-4 text-muted">Belum ada menu.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($menus->hasPages())
                        @include('shared._pagination', ['paginator' => $menus])
                    @endif
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
                <form method="POST" action="{{ route('user.sa.sidebar-menus.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nama Menu <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Order</label>
                                <input type="number" name="order" class="form-control" value="0" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Route</label>
                                <input type="text" name="route" class="form-control" placeholder="user.gtk.index">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">URL (alternatif)</label>
                                <input type="text" name="url" class="form-control" placeholder="https://...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Icon (Remix Icon class)</label>
                                <input type="text" name="icon" class="form-control" placeholder="ri-user-line">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent Menu</label>
                                <select name="parent_id" class="form-control">
                                    <option value="">Tidak ada (Menu Utama)</option>
                                    @foreach($parentMenus as $parent)
                                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Roles yang bisa melihat</label>
                                <div class="row">
                                    @foreach($roles as $role)
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="create-role-{{ $role->id }}">
                                                <label class="form-check-label" for="create-role-{{ $role->id }}">{{ $role->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted">Kosongkan untuk menampilkan ke semua role.</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_group_header" value="1" id="create-header">
                                    <label class="form-check-label" for="create-header">Header Group (tanpa route)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="create-active" checked>
                                    <label class="form-check-label" for="create-active">Menu Aktif</label>
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

    {{-- Delete Menu Modal --}}
    <div class="modal fade zoomIn" id="deleteMenuModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">Hapus Menu?</h4>
                    <p class="text-muted">Menu <strong id="deleteMenuName"></strong> akan dihapus. Sub-menu akan dipindahkan ke level utama.</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteMenuForm" method="POST" style="display:inline">
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
        document.querySelectorAll('.delete-menu').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('deleteMenuName').textContent = this.dataset.name;
                document.getElementById('deleteMenuForm').action = `/{{ $userId }}/sa/sidebar-menus/${this.dataset.id}`;
                new bootstrap.Modal(document.getElementById('deleteMenuModal')).show();
            });
        });
    });
    </script>
@endsection
