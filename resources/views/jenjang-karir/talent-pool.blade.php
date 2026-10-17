@extends('layouts.master')
@section('title', 'Talent Pool')

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Talent Pool</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="#">Jenjang Karir</a></li>
                    <li class="breadcrumb-item active">Talent Pool</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Stats Card --}}
<div class="row mb-3">
    @php
        $statsKategori = [
            'high_potential'  => ['label'=>'High Potential', 'color'=>'primary', 'icon'=>'ri-star-line'],
            'high_performer'  => ['label'=>'High Performer', 'color'=>'success', 'icon'=>'ri-award-line'],
            'key_talent'      => ['label'=>'Key Talent', 'color'=>'warning', 'icon'=>'ri-key-line'],
            'emerging_talent' => ['label'=>'Emerging Talent', 'color'=>'info', 'icon'=>'ri-seedling-line'],
        ];
    @endphp
    @foreach($statsKategori as $key => $s)
    <div class="col-md-3">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar-sm rounded bg-{{ $s['color'] }}-subtle flex-shrink-0">
                    <i class="{{ $s['icon'] }} fs-4 text-{{ $s['color'] }} d-flex align-items-center justify-content-center h-100"></i>
                </div>
                <div>
                    <p class="text-muted mb-0 small">{{ $s['label'] }}</p>
                    <h4 class="mb-0">{{ $talentList->where('kategori', $key)->count() }}</h4>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Talent Pool</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="ri-add-line me-1"></i> Tambah Talent
                </button>
            </div>
            <div class="card-body">
                {{-- Filter --}}
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="form-control form-control-sm" placeholder="Cari nama GTK...">
                    </div>
                    <div class="col-md-3">
                        <select name="kategori" class="form-select form-select-sm">
                            <option value="">-- Kategori --</option>
                            <option value="high_potential" {{ request('kategori')=='high_potential'?'selected':'' }}>High Potential</option>
                            <option value="high_performer" {{ request('kategori')=='high_performer'?'selected':'' }}>High Performer</option>
                            <option value="key_talent" {{ request('kategori')=='key_talent'?'selected':'' }}>Key Talent</option>
                            <option value="emerging_talent" {{ request('kategori')=='emerging_talent'?'selected':'' }}>Emerging Talent</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- Status --</option>
                            <option value="aktif" {{ request('status')=='aktif'?'selected':'' }}>Aktif</option>
                            <option value="tidak_aktif" {{ request('status')=='tidak_aktif'?'selected':'' }}>Tidak Aktif</option>
                            <option value="dipromosikan" {{ request('status')=='dipromosikan'?'selected':'' }}>Dipromosikan</option>
                            <option value="keluar" {{ request('status')=='keluar'?'selected':'' }}>Keluar</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-soft-secondary btn-sm w-100" type="submit">Filter</button>
                    </div>
                    @if(request()->hasAny(['search','kategori','status']))
                    <div class="col-md-2">
                        <a href="{{ route('user.jenjang-karir.talent.index', ['userId' => $userId]) }}" class="btn btn-soft-danger btn-sm w-100">Reset</a>
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
                                <th>Kategori</th>
                                <th>Jabatan Target</th>
                                <th>Skor Potensi</th>
                                <th>Skor Kinerja</th>
                                <th>Estimasi Siap</th>
                                <th>Tgl Masuk</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($talentList as $i => $t)
                            <tr>
                                <td>{{ $talentList->firstItem() + $i }}</td>
                                <td>{{ $t->user->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $t->kategori_color }}-subtle text-{{ $t->kategori_color }}">
                                        {{ $t->kategori_label }}
                                    </span>
                                </td>
                                <td>{{ $t->jabatan_target ?? '-' }}</td>
                                <td>
                                    @if($t->skor_potensi !== null)
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="progress flex-grow-1" style="height:6px;width:60px">
                                            <div class="progress-bar bg-primary" style="width:{{ $t->skor_potensi }}%"></div>
                                        </div>
                                        <small>{{ $t->skor_potensi }}</small>
                                    </div>
                                    @else <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($t->skor_kinerja !== null)
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="progress flex-grow-1" style="height:6px;width:60px">
                                            <div class="progress-bar bg-success" style="width:{{ $t->skor_kinerja }}%"></div>
                                        </div>
                                        <small>{{ $t->skor_kinerja }}</small>
                                    </div>
                                    @else <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $t->estimasi_siap_tahun ? $t->estimasi_siap_tahun.' th' : '-' }}</td>
                                <td>{{ $t->tanggal_masuk_pool->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $t->status === 'aktif' ? 'success' : 'secondary' }}-subtle text-{{ $t->status === 'aktif' ? 'success' : 'secondary' }}">
                                        {{ $t->status_label }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-soft-warning btn-sm btn-edit"
                                        data-id="{{ $t->id }}"
                                        data-user_id="{{ $t->user_id }}"
                                        data-kategori="{{ $t->kategori }}"
                                        data-status="{{ $t->status }}"
                                        data-skor_potensi="{{ $t->skor_potensi }}"
                                        data-skor_kinerja="{{ $t->skor_kinerja }}"
                                        data-kompetensi_unggulan="{{ $t->kompetensi_unggulan }}"
                                        data-area_pengembangan="{{ $t->area_pengembangan }}"
                                        data-jabatan_target="{{ $t->jabatan_target }}"
                                        data-estimasi_siap_tahun="{{ $t->estimasi_siap_tahun }}"
                                        data-tanggal_masuk_pool="{{ $t->tanggal_masuk_pool->format('Y-m-d') }}"
                                        data-tanggal_keluar_pool="{{ $t->tanggal_keluar_pool ? $t->tanggal_keluar_pool->format('Y-m-d') : '' }}"
                                        data-catatan="{{ $t->catatan }}"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('user.jenjang-karir.talent.destroy', ['userId' => $userId, 'jenjang-karir' => $t->id]) }}" method="POST" class="d-inline form-delete">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-soft-danger btn-sm">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">Tidak ada data talent pool.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('shared._pagination', ['paginator' => $talentList])
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('user.jenjang-karir.talent.store', ['userId' => $userId]) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Talent</h5>
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
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori" class="form-select" required>
                                <option value="high_potential">High Potential</option>
                                <option value="high_performer">High Performer</option>
                                <option value="key_talent">Key Talent</option>
                                <option value="emerging_talent">Emerging Talent</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan Target</label>
                            <input type="text" name="jabatan_target" class="form-control" maxlength="191">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Skor Potensi (0-100)</label>
                            <input type="number" name="skor_potensi" class="form-control" min="0" max="100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Skor Kinerja (0-100)</label>
                            <input type="number" name="skor_kinerja" class="form-control" min="0" max="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estimasi Siap (tahun)</label>
                            <input type="number" name="estimasi_siap_tahun" class="form-control" min="0" max="10">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Masuk Pool <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_masuk_pool" class="form-control" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Kompetensi Unggulan</label>
                            <textarea name="kompetensi_unggulan" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Area Pengembangan</label>
                            <textarea name="area_pengembangan" class="form-control" rows="2"></textarea>
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

{{-- Modal Edit --}}
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formEdit" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Talent Pool</h5>
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
                            <label class="form-label">Kategori</label>
                            <select name="kategori" id="edit_kategori" class="form-select">
                                <option value="high_potential">High Potential</option>
                                <option value="high_performer">High Performer</option>
                                <option value="key_talent">Key Talent</option>
                                <option value="emerging_talent">Emerging Talent</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="aktif">Aktif</option>
                                <option value="tidak_aktif">Tidak Aktif</option>
                                <option value="dipromosikan">Dipromosikan</option>
                                <option value="keluar">Keluar</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan Target</label>
                            <input type="text" name="jabatan_target" id="edit_jabatan_target" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Skor Potensi</label>
                            <input type="number" name="skor_potensi" id="edit_skor_potensi" class="form-control" min="0" max="100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Skor Kinerja</label>
                            <input type="number" name="skor_kinerja" id="edit_skor_kinerja" class="form-control" min="0" max="100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Estimasi Siap (th)</label>
                            <input type="number" name="estimasi_siap_tahun" id="edit_estimasi" class="form-control" min="0" max="10">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tgl Masuk Pool</label>
                            <input type="date" name="tanggal_masuk_pool" id="edit_masuk" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tgl Keluar Pool</label>
                            <input type="date" name="tanggal_keluar_pool" id="edit_keluar" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Kompetensi Unggulan</label>
                            <textarea name="kompetensi_unggulan" id="edit_kompetensi" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Area Pengembangan</label>
                            <textarea name="area_pengembangan" id="edit_area" class="form-control" rows="2"></textarea>
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
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        document.getElementById('formEdit').action = `/personalia/jenjang-karir/talent-pool/${id}`;
        document.getElementById('edit_user_id').value = this.dataset.user_id;
        document.getElementById('edit_kategori').value = this.dataset.kategori;
        document.getElementById('edit_status').value = this.dataset.status;
        document.getElementById('edit_jabatan_target').value = this.dataset.jabatan_target;
        document.getElementById('edit_skor_potensi').value = this.dataset.skor_potensi;
        document.getElementById('edit_skor_kinerja').value = this.dataset.skor_kinerja;
        document.getElementById('edit_estimasi').value = this.dataset.estimasi_siap_tahun;
        document.getElementById('edit_masuk').value = this.dataset.tanggal_masuk_pool;
        document.getElementById('edit_keluar').value = this.dataset.tanggal_keluar_pool;
        document.getElementById('edit_kompetensi').value = this.dataset.kompetensi_unggulan;
        document.getElementById('edit_area').value = this.dataset.area_pengembangan;
        document.getElementById('edit_catatan').value = this.dataset.catatan;
    });
});

document.querySelectorAll('.form-delete').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        Swal.fire({ title: 'Hapus talent ini?', icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', confirmButtonColor: '#d33'
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });
});
</script>
@endsection
