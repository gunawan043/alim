@extends('layouts.master')
@section('title', 'Succession Plan')

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
<style>
.urgensi-kritis { border-left: 4px solid #212529 !important; }
.urgensi-tinggi { border-left: 4px solid #dc3545 !important; }
.urgensi-sedang { border-left: 4px solid #ffc107 !important; }
.urgensi-rendah { border-left: 4px solid #6c757d !important; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Succession Plan</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="#">Jenjang Karir</a></li>
                    <li class="breadcrumb-item active">Succession Plan</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Succession Plan</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="ri-add-line me-1"></i> Buat Plan
                </button>
            </div>
            <div class="card-body">
                {{-- Filter --}}
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="form-control form-control-sm" placeholder="Cari jabatan kunci...">
                    </div>
                    <div class="col-md-2">
                        <select name="urgensi" class="form-select form-select-sm">
                            <option value="">-- Urgensi --</option>
                            <option value="kritis" {{ request('urgensi')=='kritis'?'selected':'' }}>Kritis</option>
                            <option value="tinggi" {{ request('urgensi')=='tinggi'?'selected':'' }}>Tinggi</option>
                            <option value="sedang" {{ request('urgensi')=='sedang'?'selected':'' }}>Sedang</option>
                            <option value="rendah" {{ request('urgensi')=='rendah'?'selected':'' }}>Rendah</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- Status --</option>
                            <option value="aktif" {{ request('status')=='aktif'?'selected':'' }}>Aktif</option>
                            <option value="selesai" {{ request('status')=='selesai'?'selected':'' }}>Selesai</option>
                            <option value="dibatalkan" {{ request('status')=='dibatalkan'?'selected':'' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-soft-secondary btn-sm w-100" type="submit">Filter</button>
                    </div>
                    @if(request()->hasAny(['search','urgensi','status']))
                    <div class="col-md-2">
                        <a href="{{ route('user.jenjang-karir.succession.index', ['userId' => $userId]) }}" class="btn btn-soft-danger btn-sm w-100">Reset</a>
                    </div>
                    @endif
                </form>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Plan Cards --}}
                <div class="row">
                    @forelse($successionList as $plan)
                    <div class="col-md-6 col-xl-4 mb-4">
                        <div class="card border urgensi-{{ $plan->urgensi }} h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold mb-0">{{ $plan->jabatan_kunci }}</h6>
                                    <span class="badge bg-{{ $plan->urgensi_color }}-subtle text-{{ $plan->urgensi_color }} text-uppercase ms-2">
                                        {{ $plan->urgensi }}
                                    </span>
                                </div>
                                @if($plan->unit_kerja)
                                    <p class="text-muted small mb-1"><i class="ri-building-line me-1"></i>{{ $plan->unit_kerja }}</p>
                                @endif
                                @if($plan->pemegangJabatan)
                                    <p class="text-muted small mb-1"><i class="ri-user-line me-1"></i>{{ $plan->pemegangJabatan->name }}</p>
                                @endif
                                @if($plan->perkiraan_kekosongan)
                                    <p class="text-muted small mb-2"><i class="ri-calendar-line me-1"></i>Perkiraan kosong: {{ $plan->perkiraan_kekosongan->format('d/m/Y') }}</p>
                                @endif

                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-{{ $plan->status === 'aktif' ? 'success' : 'secondary' }}-subtle text-{{ $plan->status === 'aktif' ? 'success' : 'secondary' }}">
                                        {{ $plan->status_label }}
                                    </span>
                                    <small class="text-muted">{{ $plan->kandidat->count() }} kandidat</small>
                                </div>

                                {{-- Kandidat List --}}
                                @if($plan->kandidat->count())
                                <div class="border-top pt-2">
                                    <p class="small fw-semibold mb-1">Kandidat:</p>
                                    @foreach($plan->kandidat->sortBy('prioritas') as $k)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-light text-dark">{{ $k->prioritas }}</span>
                                            <small>{{ $k->user->name ?? '-' }}</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-{{ $k->kesiapan_color }}-subtle text-{{ $k->kesiapan_color }} small">
                                                {{ $k->kesiapan_label }}
                                            </span>
                                            <form action="{{ route('user.jenjang-karir.succession.kandidat.destroy', ['userId' => $userId, 'jenjang-karir' => $k->id]) }}" method="POST" class="d-inline form-delete-kandidat">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm p-0 text-danger" title="Hapus kandidat">
                                                    <i class="ri-close-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif

                                <div class="d-flex gap-1 mt-3">
                                    <button class="btn btn-soft-info btn-sm flex-grow-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalKandidat"
                                        data-plan-id="{{ $plan->id }}"
                                        data-plan-jabatan="{{ $plan->jabatan_kunci }}">
                                        <i class="ri-user-add-line me-1"></i> Tambah Kandidat
                                    </button>
                                    <button class="btn btn-soft-warning btn-sm btn-edit"
                                        data-id="{{ $plan->id }}"
                                        data-jabatan_kunci="{{ $plan->jabatan_kunci }}"
                                        data-unit_kerja="{{ $plan->unit_kerja }}"
                                        data-pemegang_jabatan_id="{{ $plan->pemegang_jabatan_id }}"
                                        data-perkiraan_kekosongan="{{ $plan->perkiraan_kekosongan ? $plan->perkiraan_kekosongan->format('Y-m-d') : '' }}"
                                        data-urgensi="{{ $plan->urgensi }}"
                                        data-status="{{ $plan->status }}"
                                        data-deskripsi="{{ $plan->deskripsi_jabatan }}"
                                        data-persyaratan="{{ $plan->persyaratan_kompetensi }}"
                                        data-catatan="{{ $plan->catatan }}"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('user.jenjang-karir.succession.destroy', ['userId' => $userId, 'jenjang-karir' => $plan->id]) }}" method="POST" class="d-inline form-delete">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-soft-danger btn-sm">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="text-center text-muted py-5">
                            <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                            Belum ada succession plan.
                        </div>
                    </div>
                    @endforelse
                </div>

                @include('shared._pagination', ['paginator' => $successionList])
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah Plan --}}
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('user.jenjang-karir.succession.store', ['userId' => $userId]) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Succession Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan Kunci <span class="text-danger">*</span></label>
                            <input type="text" name="jabatan_kunci" class="form-control" required maxlength="191">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit Kerja</label>
                            <input type="text" name="unit_kerja" class="form-control" maxlength="191">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pemegang Jabatan Saat Ini</label>
                            <select name="pemegang_jabatan_id" class="form-select">
                                <option value="">-- Pilih GTK --</option>
                                @foreach($gtkList as $gtk)
                                    <option value="{{ $gtk->id }}">{{ $gtk->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Perkiraan Kekosongan</label>
                            <input type="date" name="perkiraan_kekosongan" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Urgensi <span class="text-danger">*</span></label>
                            <select name="urgensi" class="form-select" required>
                                <option value="rendah">Rendah</option>
                                <option value="sedang" selected>Sedang</option>
                                <option value="tinggi">Tinggi</option>
                                <option value="kritis">Kritis</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Deskripsi Jabatan</label>
                            <textarea name="deskripsi_jabatan" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Persyaratan Kompetensi</label>
                            <textarea name="persyaratan_kompetensi" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="catatan" class="form-control" rows="2"></textarea>
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

{{-- Modal Edit Plan --}}
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formEdit" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Succession Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan Kunci <span class="text-danger">*</span></label>
                            <input type="text" name="jabatan_kunci" id="edit_jabatan_kunci" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit Kerja</label>
                            <input type="text" name="unit_kerja" id="edit_unit_kerja" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pemegang Jabatan</label>
                            <select name="pemegang_jabatan_id" id="edit_pemegang" class="form-select">
                                <option value="">-- Pilih GTK --</option>
                                @foreach($gtkList as $gtk)
                                    <option value="{{ $gtk->id }}">{{ $gtk->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Perkiraan Kekosongan</label>
                            <input type="date" name="perkiraan_kekosongan" id="edit_kekosongan" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Urgensi</label>
                            <select name="urgensi" id="edit_urgensi" class="form-select">
                                <option value="rendah">Rendah</option>
                                <option value="sedang">Sedang</option>
                                <option value="tinggi">Tinggi</option>
                                <option value="kritis">Kritis</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="aktif">Aktif</option>
                                <option value="selesai">Selesai</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Deskripsi Jabatan</label>
                            <textarea name="deskripsi_jabatan" id="edit_deskripsi" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Persyaratan Kompetensi</label>
                            <textarea name="persyaratan_kompetensi" id="edit_persyaratan" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="catatan" id="edit_catatan" class="form-control" rows="2"></textarea>
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

{{-- Modal Tambah Kandidat --}}
<div class="modal fade" id="modalKandidat" tabindex="-1">
    <div class="modal-dialog">
        <form id="formKandidat" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kandidat — <span id="labelJabatan"></span></h5>
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tingkat Kesiapan <span class="text-danger">*</span></label>
                            <select name="kesiapan" class="form-select" required>
                                <option value="siap_sekarang">Siap Sekarang</option>
                                <option value="siap_1_2_tahun" selected>Siap 1-2 Tahun</option>
                                <option value="siap_3_5_tahun">Siap 3-5 Tahun</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Skor Kesiapan</label>
                            <input type="number" name="skor_kesiapan" class="form-control" min="0" max="100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Prioritas <span class="text-danger">*</span></label>
                            <input type="number" name="prioritas" class="form-control" min="1" value="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kekuatan</label>
                        <textarea name="kekuatan" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Area Pengembangan</label>
                        <textarea name="area_pengembangan" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rencana Pengembangan</label>
                        <textarea name="rencana_pengembangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah Kandidat</button>
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
        document.getElementById('formEdit').action = `/personalia/jenjang-karir/succession-plan/${id}`;
        document.getElementById('edit_jabatan_kunci').value = this.dataset.jabatan_kunci;
        document.getElementById('edit_unit_kerja').value = this.dataset.unit_kerja;
        document.getElementById('edit_pemegang').value = this.dataset.pemegang_jabatan_id;
        document.getElementById('edit_kekosongan').value = this.dataset.perkiraan_kekosongan;
        document.getElementById('edit_urgensi').value = this.dataset.urgensi;
        document.getElementById('edit_status').value = this.dataset.status;
        document.getElementById('edit_deskripsi').value = this.dataset.deskripsi;
        document.getElementById('edit_persyaratan').value = this.dataset.persyaratan;
        document.getElementById('edit_catatan').value = this.dataset.catatan;
    });
});

document.querySelectorAll('[data-bs-target="#modalKandidat"]').forEach(btn => {
    btn.addEventListener('click', function () {
        const planId = this.dataset.planId;
        const jabatan = this.dataset.planJabatan;
        document.getElementById('formKandidat').action = `/personalia/jenjang-karir/succession-plan/${planId}/kandidat`;
        document.getElementById('labelJabatan').textContent = jabatan;
    });
});

document.querySelectorAll('.form-delete, .form-delete-kandidat').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        Swal.fire({ title: 'Hapus data ini?', icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', confirmButtonColor: '#d33'
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });
});
</script>
@endsection
