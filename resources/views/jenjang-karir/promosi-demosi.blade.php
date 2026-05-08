@extends('layouts.master')
@section('title', 'Promosi & Demosi')

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Promosi & Demosi</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="#">Jenjang Karir</a></li>
                    <li class="breadcrumb-item active">Promosi & Demosi</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Promosi & Demosi</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="ri-add-line me-1"></i> Tambah
                </button>
            </div>
            <div class="card-body">
                {{-- Filter --}}
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="form-control form-control-sm" placeholder="Cari nama GTK...">
                    </div>
                    <div class="col-md-2">
                        <select name="jenis" class="form-select form-select-sm">
                            <option value="">-- Jenis --</option>
                            <option value="promosi" {{ request('jenis')=='promosi'?'selected':'' }}>Promosi</option>
                            <option value="demosi" {{ request('jenis')=='demosi'?'selected':'' }}>Demosi</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- Status --</option>
                            <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Draft</option>
                            <option value="diajukan" {{ request('status')=='diajukan'?'selected':'' }}>Diajukan</option>
                            <option value="disetujui" {{ request('status')=='disetujui'?'selected':'' }}>Disetujui</option>
                            <option value="ditolak" {{ request('status')=='ditolak'?'selected':'' }}>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-soft-secondary btn-sm w-100" type="submit">Filter</button>
                    </div>
                    @if(request()->hasAny(['search','jenis','status']))
                    <div class="col-md-2">
                        <a href="{{ route('user.jenjang-karir.promosi.index', ['userId' => $userId]) }}" class="btn btn-soft-danger btn-sm w-100">Reset</a>
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
                                <th>Jenis</th>
                                <th>Jabatan Lama</th>
                                <th>Jabatan Baru</th>
                                <th>Nomor SK</th>
                                <th>TMT</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($promosiList as $i => $p)
                            <tr>
                                <td>{{ $promosiList->firstItem() + $i }}</td>
                                <td>{{ $p->user->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $p->jenis_color }}-subtle text-{{ $p->jenis_color }}">
                                        {{ $p->jenis_label }}
                                    </span>
                                </td>
                                <td>{{ $p->jabatan_lama ?? '-' }}</td>
                                <td>{{ $p->jabatan_baru }}</td>
                                <td><span class="text-muted small">{{ $p->nomor_sk ?? '-' }}</span></td>
                                <td>{{ $p->tmt->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $p->status_color }}-subtle text-{{ $p->status_color }}">
                                        {{ $p->status_label }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-soft-warning btn-sm btn-edit"
                                        data-id="{{ $p->id }}"
                                        data-user_id="{{ $p->user_id }}"
                                        data-jenis="{{ $p->jenis }}"
                                        data-jabatan_lama="{{ $p->jabatan_lama }}"
                                        data-jabatan_baru="{{ $p->jabatan_baru }}"
                                        data-unit_kerja_lama="{{ $p->unit_kerja_lama }}"
                                        data-unit_kerja_baru="{{ $p->unit_kerja_baru }}"
                                        data-nomor_sk="{{ $p->nomor_sk }}"
                                        data-tanggal_sk="{{ $p->tanggal_sk ? $p->tanggal_sk->format('Y-m-d') : '' }}"
                                        data-tmt="{{ $p->tmt->format('Y-m-d') }}"
                                        data-alasan="{{ $p->alasan }}"
                                        data-status="{{ $p->status }}"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('user.jenjang-karir.promosi.destroy', ['userId' => $userId, 'jenjang-karir' => $p->id]) }}" method="POST" class="d-inline form-delete">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-soft-danger btn-sm">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('shared._pagination', ['paginator' => $promosiList])
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('user.jenjang-karir.promosi.store', ['userId' => $userId]) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Promosi/Demosi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">GTK <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- Pilih GTK --</option>
                                @foreach($gtkList as $gtk)
                                    <option value="{{ $gtk->id }}">{{ $gtk->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jenis <span class="text-danger">*</span></label>
                            <select name="jenis" class="form-select" required>
                                <option value="promosi">Promosi</option>
                                <option value="demosi">Demosi</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan Lama</label>
                            <input type="text" name="jabatan_lama" class="form-control" maxlength="150">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan Baru <span class="text-danger">*</span></label>
                            <input type="text" name="jabatan_baru" class="form-control" required maxlength="150">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit Kerja Lama</label>
                            <input type="text" name="unit_kerja_lama" class="form-control" maxlength="191">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit Kerja Baru</label>
                            <input type="text" name="unit_kerja_baru" class="form-control" maxlength="191">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor SK</label>
                            <input type="text" name="nomor_sk" class="form-control" maxlength="100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tanggal SK</label>
                            <input type="date" name="tanggal_sk" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">TMT <span class="text-danger">*</span></label>
                            <input type="date" name="tmt" class="form-control" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Alasan</label>
                            <textarea name="alasan" class="form-control" rows="3"></textarea>
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
    <div class="modal-dialog modal-lg">
        <form id="formEdit" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Promosi/Demosi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">GTK <span class="text-danger">*</span></label>
                            <select name="user_id" id="edit_user_id" class="form-select" required>
                                <option value="">-- Pilih GTK --</option>
                                @foreach($gtkList as $gtk)
                                    <option value="{{ $gtk->id }}">{{ $gtk->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jenis <span class="text-danger">*</span></label>
                            <select name="jenis" id="edit_jenis" class="form-select" required>
                                <option value="promosi">Promosi</option>
                                <option value="demosi">Demosi</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan Lama</label>
                            <input type="text" name="jabatan_lama" id="edit_jabatan_lama" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan Baru <span class="text-danger">*</span></label>
                            <input type="text" name="jabatan_baru" id="edit_jabatan_baru" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit Kerja Lama</label>
                            <input type="text" name="unit_kerja_lama" id="edit_unit_lama" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit Kerja Baru</label>
                            <input type="text" name="unit_kerja_baru" id="edit_unit_baru" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor SK</label>
                            <input type="text" name="nomor_sk" id="edit_nomor_sk" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tanggal SK</label>
                            <input type="date" name="tanggal_sk" id="edit_tanggal_sk" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">TMT <span class="text-danger">*</span></label>
                            <input type="date" name="tmt" id="edit_tmt" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="diajukan">Diajukan</option>
                                <option value="disetujui">Disetujui</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Alasan</label>
                            <textarea name="alasan" id="edit_alasan" class="form-control" rows="3"></textarea>
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
        document.getElementById('formEdit').action = `/personalia/jenjang-karir/promosi-demosi/${id}`;
        document.getElementById('edit_user_id').value = this.dataset.user_id;
        document.getElementById('edit_jenis').value = this.dataset.jenis;
        document.getElementById('edit_jabatan_lama').value = this.dataset.jabatan_lama;
        document.getElementById('edit_jabatan_baru').value = this.dataset.jabatan_baru;
        document.getElementById('edit_unit_lama').value = this.dataset.unit_kerja_lama;
        document.getElementById('edit_unit_baru').value = this.dataset.unit_kerja_baru;
        document.getElementById('edit_nomor_sk').value = this.dataset.nomor_sk;
        document.getElementById('edit_tanggal_sk').value = this.dataset.tanggal_sk;
        document.getElementById('edit_tmt').value = this.dataset.tmt;
        document.getElementById('edit_alasan').value = this.dataset.alasan;
        document.getElementById('edit_status').value = this.dataset.status;
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
