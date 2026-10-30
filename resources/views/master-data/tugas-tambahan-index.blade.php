@extends('layouts.master')

@section('title', 'Jenis Tugas Tambahan')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Jenis Tugas Tambahan</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                    <li class="breadcrumb-item active">Jenis Tugas Tambahan</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">Daftar Jenis Tugas Tambahan</h5>
                    <p class="text-muted mb-0 small">Master ini dipakai pada form Tugas Tambahan GTK dan Tugas Tambahan Guru.</p>
                </div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="ri-add-line me-1"></i> Tambah Jenis Tugas
                </button>
            </div>
            <div class="card-body">
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
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Cari nama atau kode tugas...">
                    </div>
                    <div class="col-md-3">
                        <select name="jenis_gtk_id" class="form-select form-select-sm">
                            <option value="">-- Jenis GTK --</option>
                            @foreach($jenisGtkList as $jenis)
                                <option value="{{ $jenis->id }}" @selected((string) request('jenis_gtk_id') === (string) $jenis->id)>{{ $jenis->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="is_active" class="form-select form-select-sm">
                            <option value="">-- Status --</option>
                            <option value="1" @selected(request('is_active') === '1')>Aktif</option>
                            <option value="0" @selected(request('is_active') === '0')>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-soft-secondary btn-sm w-100" type="submit">Filter</button>
                    </div>
                    @if(request()->hasAny(['search', 'jenis_gtk_id', 'is_active']))
                        <div class="col-md-1">
                            <a href="{{ route('user.master-data.tugas-tambahan.index', ['userId' => $userId]) }}" class="btn btn-soft-danger btn-sm w-100">Reset</a>
                        </div>
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama Tugas</th>
                                <th>Jenis GTK</th>
                                <th>Kode</th>
                                <th>Deskripsi</th>
                                <th>Urutan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($taskTypes as $i => $taskType)
                                <tr>
                                    <td>{{ $taskTypes->firstItem() + $i }}</td>
                                    <td><strong>{{ $taskType->nama }}</strong></td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ $taskType->jenisGtk?->nama ?? '—' }}</span></td>
                                    <td><code>{{ $taskType->kode }}</code></td>
                                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($taskType->deskripsi, 70) ?: '—' }}</td>
                                    <td>{{ $taskType->urutan }}</td>
                                    <td>
                                        @if($taskType->is_active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button type="button"
                                                class="btn btn-soft-warning btn-sm btn-edit-task"
                                                data-id="{{ $taskType->id }}"
                                                data-jenis="{{ $taskType->jenis_gtk_id }}"
                                                data-nama="{{ $taskType->nama }}"
                                                data-deskripsi="{{ $taskType->deskripsi }}"
                                                data-urutan="{{ $taskType->urutan }}"
                                                data-active="{{ $taskType->is_active ? '1' : '0' }}"
                                                data-bs-toggle="modal" data-bs-target="#modalEdit">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <form action="{{ route('user.master-data.tugas-tambahan.destroy', ['userId' => $userId, 'id' => $taskType->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jenis tugas ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">Belum ada jenis tugas tambahan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('shared._pagination', ['paginator' => $taskTypes])
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('user.master-data.tugas-tambahan.store', ['userId' => $userId]) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-add-line me-1"></i>Tambah Jenis Tugas Tambahan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Jenis GTK <span class="text-danger">*</span></label>
                            <select name="jenis_gtk_id" class="form-select" required>
                                <option value="">— Pilih Jenis GTK —</option>
                                @foreach($jenisGtkList as $jenis)
                                    <option value="{{ $jenis->id }}">{{ $jenis->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Tugas <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" required maxlength="100" placeholder="Contoh: Koordinator Tahfidz">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Urutan</label>
                            <input type="number" name="urutan" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="2" maxlength="1000" placeholder="Opsional"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formEditTask" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-edit-line me-1"></i>Edit Jenis Tugas Tambahan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Jenis GTK <span class="text-danger">*</span></label>
                            <select name="jenis_gtk_id" id="edit_jenis_gtk_id" class="form-select" required>
                                <option value="">— Pilih Jenis GTK —</option>
                                @foreach($jenisGtkList as $jenis)
                                    <option value="{{ $jenis->id }}">{{ $jenis->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Tugas <span class="text-danger">*</span></label>
                            <input type="text" name="nama" id="edit_nama" class="form-control" required maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Urutan</label>
                            <input type="number" name="urutan" id="edit_urutan" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="is_active" id="edit_is_active" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="2" maxlength="1000"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="ri-save-line me-1"></i>Update</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const taskUpdateUrl = @json(route('user.master-data.tugas-tambahan.update', ['userId' => $userId, 'id' => '__ID__']));
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-edit-task').forEach(function (button) {
            button.addEventListener('click', function () {
                const form = document.getElementById('formEditTask');
                form.action = taskUpdateUrl.replace('__ID__', this.dataset.id);
                document.getElementById('edit_jenis_gtk_id').value = this.dataset.jenis;
                document.getElementById('edit_nama').value = this.dataset.nama;
                document.getElementById('edit_urutan').value = this.dataset.urutan;
                document.getElementById('edit_is_active').value = this.dataset.active;
                document.getElementById('edit_deskripsi').value = this.dataset.deskripsi || '';
            });
        });
    });
</script>
@endpush
