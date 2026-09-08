@extends('layouts.master')
@section('title', 'Divisi')

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') Divisi @endslot
        @slot('title') Divisi @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Divisi</h5>
                            <p class="text-muted mb-0">Kelola divisi sistem. Data ini bersifat global untuk semua sekolah.</p>
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createModal">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Divisi
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode divisi..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="is_active" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        @if(request()->hasAny(['search', 'is_active']))
                        <div class="col-md-2">
                            <a href="{{ route('system.sa.divisi.index') }}" class="btn btn-light w-100">Reset</a>
                        </div>
                        @endif
                    </form>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Kode</th>
                                    <th>Nama Divisi</th>
                                    <th>Deskripsi</th>
                                    <th class="text-center">Dok. ISO</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($divisiList as $i => $d)
                                    <tr>
                                        <td>{{ $divisiList->firstItem() + $i }}</td>
                                        <td><code class="bg-light px-1 py-0 rounded" style="font-size:0.78rem; color:#0f6cb2">{{ $d->kode }}</code></td>
                                        <td><strong>{{ $d->nama }}</strong></td>
                                        <td class="text-muted small">{{ Str::limit($d->deskripsi, 50) ?? '-' }}</td>
                                        <td class="text-center">
                                            @if($d->dokumen_iso_count > 0)
                                                <span class="badge bg-warning text-dark">{{ $d->dokumen_iso_count }}</span>
                                            @else
                                                <span class="badge bg-secondary">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($d->is_active)
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item edit-btn"
                                                            data-id="{{ $d->id }}"
                                                            data-nama="{{ e($d->nama) }}"
                                                            data-kode="{{ e($d->kode) }}"
                                                            data-deskripsi="{{ e($d->deskripsi) }}"
                                                            data-is_active="{{ $d->is_active ? '1' : '0' }}">
                                                            <i class="ri-pencil-line text-primary me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger delete-btn"
                                                            data-id="{{ $d->id }}"
                                                            data-nama="{{ e($d->nama) }}"
                                                            data-kode="{{ e($d->kode) }}">
                                                            <i class="ri-delete-bin-line text-danger me-2"></i>Hapus
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data divisi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($divisiList->hasPages())
                        @include('shared._pagination', ['paginator' => $divisiList])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div class="modal fade zoomIn" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Divisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('system.sa.divisi.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Divisi <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" required maxlength="150" placeholder="Contoh: Divisi Keuangan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode Divisi <span class="text-danger">*</span></label>
                            <input type="text" name="kode" class="form-control" required maxlength="30"
                                placeholder="CONTOH: DIV-KEU"
                                style="text-transform: uppercase"
                                oninput="this.value = this.value.toUpperCase()">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="2" maxlength="500" placeholder="Opsional"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
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

    {{-- Edit Modal --}}
    <div class="modal fade zoomIn" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Divisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Divisi <span class="text-danger">*</span></label>
                            <input type="text" name="nama" id="edit_nama" class="form-control" required maxlength="150">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode Divisi <span class="text-danger">*</span></label>
                            <input type="text" name="kode" id="edit_kode" class="form-control" required maxlength="30"
                                style="text-transform: uppercase"
                                oninput="this.value = this.value.toUpperCase()">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" id="edit_is_active" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade zoomIn" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hapus Divisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h5 class="mt-3">Hapus Divisi?</h5>
                    <p class="text-muted" id="deleteInfo"></p>
                    <p class="text-danger small">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteForm" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
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
        // Edit modal
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('editForm').action = `/system/sa/divisi/${this.dataset.id}`;
                document.getElementById('edit_nama').value = this.dataset.nama || '';
                document.getElementById('edit_kode').value = (this.dataset.kode || '').toUpperCase();
                document.getElementById('edit_deskripsi').value = this.dataset.deskripsi || '';
                document.getElementById('edit_is_active').value = this.dataset.is_active || '1';
            });
        });

        // Delete modal
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('deleteForm').action = `/system/sa/divisi/${this.dataset.id}`;
                document.getElementById('deleteInfo').innerHTML =
                    `<strong>${this.dataset.nama}</strong> (${this.dataset.kode})`;
            });
        });
    });
    </script>
@endsection
