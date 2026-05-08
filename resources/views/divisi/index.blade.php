@extends('layouts.master')
@section('title', 'Divisi')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Divisi</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                    <li class="breadcrumb-item active">Divisi</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Divisi</h5>
                @if($isSuperAdmin)
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="ri-add-line me-1"></i> Tambah Divisi
                </button>
                @endif
            </div>
            <div class="card-body">
                {{-- Filter --}}
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="form-control form-control-sm" placeholder="Cari nama / kode divisi...">
                    </div>
                    <div class="col-md-2">
                        <select name="is_active" class="form-select form-select-sm">
                            <option value="">-- Status --</option>
                            <option value="1" {{ request('is_active')==='1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ request('is_active')==='0' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-soft-secondary btn-sm w-100" type="submit">Filter</button>
                    </div>
                    @if(request()->hasAny(['search','is_active']))
                    <div class="col-md-2">
                        <a href="{{ route('user.divisi.index', ['userId' => $userId]) }}" class="btn btn-soft-danger btn-sm w-100">Reset</a>
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
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Kode</th>
                                <th>Nama Divisi</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Dok. ISO</th>
                                <th>Status</th>
                                @if($isSuperAdmin)
                                <th class="text-center">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($divisiList as $i => $d)
                            <tr>
                                <td>{{ $divisiList->firstItem() + $i }}</td>
                                <td><code class="bg-light px-1 py-0 rounded" style="font-size:0.78rem; color:#0f6cb2">{{ $d->kode }}</code></td>
                                <td><strong>{{ $d->nama }}</strong></td>
                                <td class="text-muted small">{{ $d->deskripsi ?? '-' }}</td>
                                <td class="text-center">
                                    @if($d->dokumen_iso_count > 0)
                                        <span class="badge bg-primary">{{ $d->dokumen_iso_count }}</span>
                                    @else
                                        <span class="badge bg-secondary">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if($d->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                @if($isSuperAdmin)
                                <td class="text-center">
                                    <button class="btn btn-soft-warning btn-sm btn-edit"
                                        data-id="{{ $d->id }}"
                                        data-nama="{{ e($d->nama) }}"
                                        data-kode="{{ e($d->kode) }}"
                                        data-deskripsi="{{ e($d->deskripsi) }}"
                                        data-is_active="{{ $d->is_active ? '1' : '0' }}"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('user.divisi.destroy', ['userId' => $userId, 'id' => $d->id]) }}"
                                          method="POST" class="d-inline form-delete">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-soft-danger btn-sm">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('shared._pagination', ['paginator' => $divisiList])
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah (Super Admin only) --}}
@if($isSuperAdmin)
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('user.divisi.store', ['userId' => $userId]) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Divisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Divisi <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required maxlength="150" placeholder="Contoh: Divisi Keuangan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Divisi <span class="text-danger">*</span></label>
                        <input type="text" name="kode" class="form-control" required maxlength="30"
                            placeholder="Contoh: DIV-KEU" style="text-transform: uppercase;"
                            oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="deskripsi" class="form-control" maxlength="255" placeholder="Opsional">
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
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit (Super Admin only) --}}
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form id="formEdit" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Divisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Divisi <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Divisi <span class="text-danger">*</span></label>
                        <input type="text" name="kode" id="edit_kode" class="form-control" required maxlength="30"
                            style="text-transform: uppercase;"
                            oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="deskripsi" id="edit_deskripsi" class="form-control" maxlength="255">
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
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('formEdit').action = '/personalia/' + window.userId + '/divisi/' + this.dataset.id;
        document.getElementById('edit_nama').value = this.dataset.nama || '';
        document.getElementById('edit_kode').value = (this.dataset.kode || '').toUpperCase();
        document.getElementById('edit_deskripsi').value = this.dataset.deskripsi || '';
        document.getElementById('edit_is_active').value = this.dataset.is_active || '1';
    });
});

document.querySelectorAll('.form-delete').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        Swal.fire({
            title: 'Hapus divisi ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33'
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });
});
</script>
@endsection
