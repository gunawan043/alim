@extends('layouts.master')
@section('title', 'Mutasi & Rotasi')

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Mutasi & Rotasi</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="#">Jenjang Karir</a></li>
                    <li class="breadcrumb-item active">Mutasi & Rotasi</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Permintaan Mutasi/Rotasi</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="ri-add-line me-1"></i> Ajukan Mutasi
                </button>
            </div>
            <div class="card-body">
                {{-- Filter --}}
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="form-control form-control-sm" placeholder="Cari nama GTK...">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- Semua Status --</option>
                            <option value="PENDING" {{ request('status')=='PENDING'?'selected':'' }}>Pending</option>
                            <option value="APPROVED" {{ request('status')=='APPROVED'?'selected':'' }}>Disetujui</option>
                            <option value="REJECTED" {{ request('status')=='REJECTED'?'selected':'' }}>Ditolak</option>
                            <option value="CANCELLED" {{ request('status')=='CANCELLED'?'selected':'' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-soft-secondary btn-sm w-100" type="submit">Filter</button>
                    </div>
                    @if(request()->hasAny(['search','status']))
                    <div class="col-md-2">
                        <a href="{{ route('user.jenjang-karir.mutasi.index', ['userId' => $userId]) }}" class="btn btn-soft-danger btn-sm w-100">Reset</a>
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
                                <th>Jabatan</th>
                                <th>Dari Unit</th>
                                <th>Ke Unit</th>
                                <th>Tgl Pengajuan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mutasi as $i => $m)
                            <tr>
                                <td>{{ $mutasi->firstItem() + $i }}</td>
                                <td>{{ $m->user->name ?? '-' }}</td>
                                <td>{{ $m->jabatan ?? '-' }}</td>
                                <td>{{ $m->fromWorkUnit->name ?? '-' }}</td>
                                <td>{{ $m->toWorkUnit->name ?? '-' }}</td>
                                <td>{{ $m->created_at->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $m->status_color }}-subtle text-{{ $m->status_color }}">
                                        {{ $m->status_text }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($m->status === 'PENDING')
                                    <form action="{{ route('user.jenjang-karir.mutasi.approve', ['userId' => $userId, 'jenjang-karir' => $m->id]) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-soft-success btn-sm" title="Setujui">
                                            <i class="ri-check-line"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('user.jenjang-karir.mutasi.reject', ['userId' => $userId, 'jenjang-karir' => $m->id]) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-soft-danger btn-sm" title="Tolak">
                                            <i class="ri-close-line"></i>
                                        </button>
                                    </form>
                                    @endif
                                    <button class="btn btn-soft-warning btn-sm btn-edit"
                                        data-id="{{ $m->id }}"
                                        data-user_id="{{ $m->user_id }}"
                                        data-jabatan="{{ $m->jabatan }}"
                                        data-from_work_unit_id="{{ $m->from_work_unit_id }}"
                                        data-to_work_unit_id="{{ $m->to_work_unit_id }}"
                                        data-status="{{ $m->status }}"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('user.jenjang-karir.mutasi.destroy', ['userId' => $userId, 'jenjang-karir' => $m->id]) }}" method="POST" class="d-inline form-delete">
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

                @include('shared._pagination', ['paginator' => $mutasi])
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('user.jenjang-karir.mutasi.store', ['userId' => $userId]) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajukan Mutasi/Rotasi</h5>
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
                        <label class="form-label">Jabatan</label>
                        <input type="text" name="jabatan" class="form-control" maxlength="191">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dari Unit Kerja</label>
                        <select name="from_work_unit_id" class="form-select">
                            <option value="">-- Pilih Unit Asal --</option>
                            @foreach($workUnits as $wu)
                                <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ke Unit Kerja <span class="text-danger">*</span></label>
                        <select name="to_work_unit_id" class="form-select" required>
                            <option value="">-- Pilih Unit Tujuan --</option>
                            @foreach($workUnits as $wu)
                                <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan</label>
                        <textarea name="reason" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Ajukan</button>
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
                    <h5 class="modal-title">Edit Mutasi/Rotasi</h5>
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
                        <label class="form-label">Jabatan</label>
                        <input type="text" name="jabatan" id="edit_jabatan" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dari Unit Kerja</label>
                        <select name="from_work_unit_id" id="edit_from_unit" class="form-select">
                            <option value="">-- Pilih Unit Asal --</option>
                            @foreach($workUnits as $wu)
                                <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ke Unit Kerja <span class="text-danger">*</span></label>
                        <select name="to_work_unit_id" id="edit_to_unit" class="form-select" required>
                            <option value="">-- Pilih Unit Tujuan --</option>
                            @foreach($workUnits as $wu)
                                <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="PENDING">Pending</option>
                            <option value="APPROVED">Disetujui</option>
                            <option value="REJECTED">Ditolak</option>
                            <option value="CANCELLED">Dibatalkan</option>
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
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        document.getElementById('formEdit').action = `/personalia/jenjang-karir/mutasi-rotasi/${id}`;
        document.getElementById('edit_user_id').value = this.dataset.user_id;
        document.getElementById('edit_jabatan').value = this.dataset.jabatan;
        document.getElementById('edit_from_unit').value = this.dataset.from_work_unit_id;
        document.getElementById('edit_to_unit').value = this.dataset.to_work_unit_id;
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
