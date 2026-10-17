@extends('layouts.master')
@section('title', 'Career Path')

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Career Path</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="#">Jenjang Karir</a></li>
                    <li class="breadcrumb-item active">Career Path</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Career Path</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="ri-add-line me-1"></i> Tambah
                </button>
            </div>
            <div class="card-body">
                {{-- Filter --}}
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="form-control form-control-sm" placeholder="Cari jabatan...">
                    </div>
                    <div class="col-md-3">
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">-- Semua GTK --</option>
                            @foreach($gtkList as $gtk)
                                <option value="{{ $gtk->id }}" {{ request('user_id') == $gtk->id ? 'selected' : '' }}>
                                    {{ $gtk->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-soft-secondary btn-sm w-100" type="submit">Filter</button>
                    </div>
                    @if(request()->hasAny(['search','user_id']))
                    <div class="col-md-2">
                        <a href="{{ route('user.jenjang-karir.career-path.index', ['userId' => $userId]) }}" class="btn btn-soft-danger btn-sm w-100">Reset</a>
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
                                <th>Nama GTK</th>
                                <th>Jabatan/Fungsi</th>
                                <th>Nomor SK</th>
                                <th>TMT</th>
                                <th>TST</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($careerPaths as $i => $cp)
                            <tr>
                                <td>{{ $careerPaths->firstItem() + $i }}</td>
                                <td>{{ $cp->user->name ?? '-' }}</td>
                                <td>{{ $cp->jabatan_fungsi }}</td>
                                <td><span class="text-muted">{{ $cp->masked_nomor_sk ?? '-' }}</span></td>
                                <td>{{ $cp->tmt ? $cp->tmt->format('d/m/Y') : '-' }}</td>
                                <td>{{ $cp->tst ? $cp->tst->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @if($cp->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Selesai</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-soft-warning btn-sm btn-edit"
                                        data-id="{{ $cp->id }}"
                                        data-user_id="{{ $cp->user_id }}"
                                        data-jabatan_fungsi="{{ $cp->jabatan_fungsi }}"
                                        data-nomor_sk="{{ $cp->nomor_sk }}"
                                        data-tmt="{{ $cp->tmt ? $cp->tmt->format('Y-m-d') : '' }}"
                                        data-tst="{{ $cp->tst ? $cp->tst->format('Y-m-d') : '' }}"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('user.jenjang-karir.career-path.destroy', ['userId' => $userId, 'jenjang-karir' => $cp->id]) }}" method="POST" class="d-inline form-delete">
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

                @include('shared._pagination', ['paginator' => $careerPaths])
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('user.jenjang-karir.career-path.store', ['userId' => $userId]) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Career Path</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">GTK <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Pilih GTK --</option>
                            @foreach($gtkList as $gtk)
                                <option value="{{ $gtk->id }}">{{ $gtk->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jabatan/Fungsi <span class="text-danger">*</span></label>
                        <input type="text" name="jabatan_fungsi" class="form-control" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor SK</label>
                        <input type="text" name="nomor_sk" class="form-control" maxlength="100">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">TMT</label>
                            <input type="date" name="tmt" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">TST</label>
                            <input type="date" name="tst" class="form-control">
                        </div>
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
                    <h5 class="modal-title">Edit Career Path</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">GTK <span class="text-danger">*</span></label>
                        <select name="user_id" id="edit_user_id" class="form-select" required>
                            <option value="">-- Pilih GTK --</option>
                            @foreach($gtkList as $gtk)
                                <option value="{{ $gtk->id }}">{{ $gtk->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jabatan/Fungsi <span class="text-danger">*</span></label>
                        <input type="text" name="jabatan_fungsi" id="edit_jabatan_fungsi" class="form-control" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor SK</label>
                        <input type="text" name="nomor_sk" id="edit_nomor_sk" class="form-control" maxlength="100">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">TMT</label>
                            <input type="date" name="tmt" id="edit_tmt" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">TST</label>
                            <input type="date" name="tst" id="edit_tst" class="form-control">
                        </div>
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
        const id = this.dataset.id;
        document.getElementById('formEdit').action = `/personalia/jenjang-karir/career-path/${id}`;
        document.getElementById('edit_user_id').value = this.dataset.user_id;
        document.getElementById('edit_jabatan_fungsi').value = this.dataset.jabatan_fungsi;
        document.getElementById('edit_nomor_sk').value = this.dataset.nomor_sk;
        document.getElementById('edit_tmt').value = this.dataset.tmt;
        document.getElementById('edit_tst').value = this.dataset.tst;
    });
});

document.querySelectorAll('.form-delete').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        Swal.fire({ title: 'Hapus data ini?', icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', confirmButtonColor: '#d33'
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });
});
</script>
@endsection
