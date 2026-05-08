@extends('layouts.master')
@section('title', 'Kelola Menu Sidebar')

@section('content')
<div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">Kelola Menu Sidebar</h4>
    <div class="page-title-right">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="#">Admin</a></li>
            <li class="breadcrumb-item active">Sidebar Menu</li>
        </ol>
    </div>
</div>

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
    {{-- KIRI: Tree menu --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Struktur Menu</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="ri-add-line me-1"></i> Tambah Menu
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="4%">#</th>
                                <th width="6%">Icon</th>
                                <th>Nama / Label</th>
                                <th width="22%">Route / URL</th>
                                <th width="10%">Tipe</th>
                                <th width="10%">Akses Role</th>
                                <th width="8%">Status</th>
                                <th width="12%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allMenus as $menu)
                            <tr class="{{ $menu->parent_id ? 'bg-light' : '' }}">
                                <td class="text-center">
                                    @if($menu->parent_id)
                                        <i class="ri-subtract-line text-muted"></i>
                                    @endif
                                    {{ $loop->iteration }}
                                </td>
                                <td class="text-center">
                                    @if($menu->icon)
                                        <i class="{{ $menu->icon }} text-primary"></i>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $menu->is_group_header ? 'fw-bold text-uppercase' : '' }}">
                                        {{ $menu->name }}
                                    </span>
                                    @if($menu->parent_id && $menu->parent)
                                        <span class="text-muted small">← {{ $menu->parent->name }}</span>
                                    @endif
                                </td>
                                <td>
                                    <code class="small">{{ $menu->route ?? ($menu->url ?? '-') }}</code>
                                </td>
                                <td>
                                    @if($menu->is_group_header)
                                        <span class="badge bg-dark">Group Header</span>
                                    @elseif($menu->parent_id)
                                        <span class="badge bg-secondary">Sub Menu</span>
                                    @else
                                        <span class="badge bg-primary">Menu</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse($menu->roles as $role)
                                        <span class="badge bg-info me-1">{{ $role->name }}</span>
                                    @empty
                                        <span class="badge bg-success">Semua Role</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if($menu->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-danger">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-soft-warning"
                                        onclick="editMenu('{{ $menu->id }}', '{{ addslashes($menu->name) }}', '{{ $menu->icon ?? '' }}', '{{ $menu->route ?? '' }}', '{{ $menu->url ?? '' }}', '{{ $menu->parent_id ?? '' }}', {{ $menu->order }}, {{ $menu->is_group_header ? 'true' : 'false' }}, {{ $menu->is_active ? 'true' : 'false' }}, '{{ $menu->roles->pluck('id')->implode(',') }}')">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('user.admin.sidebar-menu.destroy', $menu->id) }}" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-soft-danger delete-btn" data-message='Hapus menu "{{ $menu->name }}?"'>
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="ri-menu-add-line" style="font-size:2rem;"></i>
                                    <p class="mb-0">Belum ada menu. Tambahkan menu pertama.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- KANAN: Preview --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-eye-line me-1"></i> Preview Sidebar</h5>
            </div>
            <div class="card-body p-0">
                <div style="background:#2d3748; min-height:400px; padding:1rem;">
                    <p class="text-white small mb-2 opacity-50">LIVE PREVIEW</p>
                    <ul class="nav flex-column" id="sidebarPreview">
                        @forelse($allMenus->whereNull('parent_id') as $menu)
                            @if($menu->is_group_header)
                                <li class="nav-item mt-3 mb-1">
                                    <span class="text-white-50 small fw-bold text-uppercase">{{ $menu->name }}</span>
                                </li>
                            @else
                                <li class="nav-item mb-1">
                                    <a class="nav-link text-white-50 py-1 px-2 rounded" href="#"
                                        style="font-size:0.85rem;">
                                        @if($menu->icon)<i class="{{ $menu->icon }} me-2"></i>@endif
                                        {{ $menu->name }}
                                    </a>
                                </li>
                            @endif

                            @foreach($allMenus->where('parent_id', $menu->id) as $child)
                            <li class="nav-item ms-3 mb-1">
                                <a class="nav-link text-white-50 py-1 px-2 rounded" href="#"
                                    style="font-size:0.8rem;">
                                    @if($child->icon)<i class="{{ $child->icon }} me-2"></i>@endif
                                    {{ $child->name }}
                                </a>
                            </li>
                            @endforeach
                        @empty
                            <li class="nav-item text-white-50 py-1">Menu kosong — Tambahkan menu baru.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-information-line me-1"></i> Petunjuk</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0 small text-muted">
                    <li><strong>Group Header:</strong> Judul section (tidak bisa diklik), misal "Data", "Laporan"</li>
                    <li><strong>Menu:</strong> Item menu level 1 dengan route/url</li>
                    <li><strong>Sub Menu:</strong> Item anak di bawah menu utama</li>
                    <li>Menu tanpa role = bisa diakses semua user</li>
                    <li>Hapus sub-menu terlebih dahulu sebelum menghapus parent-nya</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('user.admin.sidebar-menu.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahLabel">Tambah Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama / Label <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="misal: Data GTK">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon (Remix Icon class)</label>
                        <input type="text" name="icon" class="form-control" placeholder="misal: ri-user-line">
                        <small class="text-muted"><a href="https://remixicon.com/" target="_blank">remixicon.com</a> — tanpa prefix "ri-"</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Route Name</label>
                        <input type="text" name="route" class="form-control" placeholder="misal: personalia.gtk.index">
                        <small class="text-muted">Gunakan route name, bukan URL. Kosongkan jika ini Group Header.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL Langsung</label>
                        <input type="text" name="url" class="form-control" placeholder="misal: /custom-url">
                        <small class="text-muted">Isi jika tidak ada route name.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Menu Induk</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- Menu Utama (Level 1) --</option>
                            @foreach($allMenus->where('is_group_header', false) as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="order" class="form-control" value="0" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Akses Role</label>
                        <div class="border rounded p-2" style="max-height:150px; overflow-y:auto;">
                            @foreach($roles as $role)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="roleAdd{{ $role->id }}">
                                    <label class="form-check-label" for="roleAdd{{ $role->id }}">{{ $role->name }}</label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Kosongkan = semua role bisa mengakses.</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_group_header" id="isGroupHeaderAdd" value="1">
                            <label class="form-check-label" for="isGroupHeaderAdd">Ini adalah Group Header (judul section)</label>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActiveAdd" value="1" checked>
                        <label class="form-check-label" for="isActiveAdd">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEdit" action="">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditLabel">Edit Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama / Label <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" name="icon" id="editIcon" class="form-control" placeholder="misal: ri-user-line">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Route Name</label>
                        <input type="text" name="route" id="editRoute" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL Langsung</label>
                        <input type="text" name="url" id="editUrl" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Menu Induk</label>
                        <select name="parent_id" id="editParent" class="form-select">
                            <option value="">-- Menu Utama (Level 1) --</option>
                            @foreach($allMenus->where('is_group_header', false) as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="order" id="editOrder" class="form-control" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Akses Role</label>
                        <div class="border rounded p-2" style="max-height:150px; overflow-y:auto;" id="editRolesContainer">
                            @foreach($roles as $role)
                                <div class="form-check">
                                    <input class="form-check-input edit-role-checkbox" type="checkbox" name="roles[]" value="{{ $role->id }}" id="editRole{{ $role->id }}">
                                    <label class="form-check-label" for="editRole{{ $role->id }}">{{ $role->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_group_header" id="editIsGroupHeader" value="1">
                            <label class="form-check-label" for="editIsGroupHeader">Group Header</label>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" value="1">
                        <label class="form-check-label" for="editIsActive">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function editMenu(id, name, icon, route, url, parentId, order, isGroupHeader, isActive, roleIds) {
    document.getElementById('formEdit').action = '/admin/sidebar-menu/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editIcon').value = icon;
    document.getElementById('editRoute').value = route;
    document.getElementById('editUrl').value = url;
    document.getElementById('editParent').value = parentId;
    document.getElementById('editOrder').value = order;
    document.getElementById('editIsGroupHeader').checked = isGroupHeader;
    document.getElementById('editIsActive').checked = isActive;

    // Reset & set role checkboxes
    document.querySelectorAll('.edit-role-checkbox').forEach(cb => cb.checked = false);
    if (roleIds) {
        roleIds.split(',').forEach(rid => {
            const cb = document.getElementById('editRole' + rid.trim());
            if (cb) cb.checked = true;
        });
    }

    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>
@endsection
