@extends('layouts.master')
@section('title', 'Jabatan')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Jabatan</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                    <li class="breadcrumb-item active">Jabatan</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Jabatan</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="ri-add-line me-1"></i> Tambah Jabatan
                </button>
            </div>
            <div class="card-body">
                {{-- Filter --}}
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="form-control form-control-sm" placeholder="Cari nama jabatan...">
                    </div>
                    <div class="col-md-3">
                        <select name="jenis_gtk_id" class="form-select form-select-sm">
                            <option value="">-- Jenis GTK --</option>
                            @foreach($jenisGtkList as $j)
                                <option value="{{ $j->id }}" {{ request('jenis_gtk_id')==$j->id ? 'selected' : '' }}>{{ $j->nama }}</option>
                            @endforeach
                        </select>
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
                    @if(request()->hasAny(['search','jenis_gtk_id','is_active']))
                    <div class="col-md-2">
                        <a href="{{ route('user.master-data.jabatan.index', ['userId' => $userId]) }}" class="btn btn-soft-danger btn-sm w-100">Reset</a>
                    </div>
                    @endif
                </form>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama Jabatan</th>
                                <th>Jenis GTK</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Urutan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jabatanList as $i => $j)
                            <tr>
                                <td>{{ $jabatanList->firstItem() + $i }}</td>
                                <td><strong>{{ $j->nama }}</strong></td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $j->jenisGtk->nama ?? '-' }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $j->kategori ?? '-' }}</td>
                                <td class="small text-muted">{{ $j->deskripsi ?? '-' }}</td>
                                <td>{{ $j->urutan }}</td>
                                <td>
                                    @if($j->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-soft-warning btn-sm btn-edit"
                                        data-id="{{ $j->id }}"
                                        data-jenis_gtk_id="{{ $j->jenis_gtk_id }}"
                                        data-nama="{{ $j->nama }}"
                                        data-kategori="{{ $j->kategori }}"
                                        data-deskripsi="{{ $j->deskripsi }}"
                                        data-is_active="{{ $j->is_active ? '1' : '0' }}"
                                        data-urutan="{{ $j->urutan }}"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('user.master-data.jabatan.destroy', ['userId' => $userId, 'id' => $j->id]) }}" method="POST" class="d-inline form-delete">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-soft-danger btn-sm">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('shared._pagination', ['paginator' => $jabatanList])
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('user.master-data.jabatan.store', ['userId' => $userId]) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Jabatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis GTK <span class="text-danger">*</span></label>
                        <select name="jenis_gtk_id" class="form-select" required>
                            <option value="">-- Pilih Jenis GTK --</option>
                            @foreach($jenisGtkList as $j)
                                <option value="{{ $j->id }}">{{ $j->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required maxlength="150" placeholder="Contoh: Guru Matematika">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori</label>
                            <input type="text" name="kategori" class="form-control" maxlength="50" placeholder="Contoh: Akademik">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" name="urutan" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="deskripsi" class="form-control" maxlength="255" placeholder="Opsional">
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

{{-- Modal Edit --}}
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form id="formEdit" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Jabatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis GTK <span class="text-danger">*</span></label>
                        <select name="jenis_gtk_id" id="edit_jenis_gtk_id" class="form-select" required>
                            <option value="">-- Pilih Jenis GTK --</option>
                            @foreach($jenisGtkList as $j)
                                <option value="{{ $j->id }}">{{ $j->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required maxlength="150">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori</label>
                            <input type="text" name="kategori" id="edit_kategori" class="form-control" maxlength="50">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" name="urutan" id="edit_urutan" class="form-control" min="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" id="edit_is_active" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="deskripsi" id="edit_deskripsi" class="form-control" maxlength="255">
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
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('formEdit').action = '/personalia/master-data/jabatan/' + this.dataset.id;
        document.getElementById('edit_jenis_gtk_id').value = this.dataset.jenis_gtk_id;
        document.getElementById('edit_nama').value = this.dataset.nama;
        document.getElementById('edit_kategori').value = this.dataset.kategori || '';
        document.getElementById('edit_deskripsi').value = this.dataset.deskripsi || '';
        document.getElementById('edit_urutan').value = this.dataset.urutan || 0;
        document.getElementById('edit_is_active').value = this.dataset.is_active;
    });
});

document.querySelectorAll('.form-delete').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        Swal.fire({ title: 'Hapus jabatan ini?', icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', confirmButtonColor: '#d33'
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });
});
</script>
@endsection
