@extends('layouts.master')
@section('title') Dokumen ISO @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection

@php $userId = $userId ?? auth()->id(); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('title') Dokumen ISO @endslot
    @endcomponent

    {{-- Stat Widgets --}}
    <div class="row">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Total Dokumen</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-20 fw-semibold mb-0">{{ $totalDokumen }}</h4>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                <i class="bx bx-file text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Revisi Terakhir</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-20 fw-semibold mb-0">Rev. {{ $latestRevisi }}</h4>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                <i class="bx bx-refresh text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Jumlah Divisi</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-40 fw-semibold mb-0">{{ $divisiList->count() }}</h4>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                <i class="bx bx-buildings text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="dokumenList">
                <div class="card-header d-flex align-items-center border-bottom py-2">
                    <h5 class="card-title flex-grow-1 mb-0">Data Dokumen ISO</h5>
                    <div class="d-flex gap-1 flex-wrap">
                        <button class="btn btn-soft-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                            <i class="ri-delete-bin-2-line me-1"></i> Hapus Terpilih
                        </button>
                        @if($isSuperAdmin)
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="ri-add-line align-bottom me-1"></i> Tambah
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Filter --}}
                <form method="GET" class="card-body py-2 border-bottom">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="form-control form-control"
                                placeholder="Cari nama / kode dokumen...">
                        </div>
                        <div class="col-md-3">
                            <select name="divisi_id" class="form-select form-select">
                                <option value="">-- Semua Divisi --</option>
                                @foreach($divisiList as $div)
                                    <option value="{{ $div->id }}" {{ request('divisi_id')==$div->id ? 'selected' : '' }}>{{ $div->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="kategori" class="form-select form-select">
                                <option value="">-- Semua --</option>
                                <option value="PROSEDUR" {{ request('kategori')=='PROSEDUR' ? 'selected' : '' }}>PROSEDUR</option>
                                <option value="FORMULIR" {{ request('kategori')=='FORMULIR' ? 'selected' : '' }}>FORMULIR</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-soft-secondary btn-md w-100" type="submit">
                                <i class="ri-filter-3-line me-1"></i> Filter
                            </button>
                        </div>
                        @if(request()->hasAny(['search','divisi_id']))
                        <div class="col-md-1">
                            <a href="{{ route('user.dokumen-iso.index', ['userId' => $userId]) }}"
                                class="btn btn-soft-danger btn-md w-100">
                                <i class="ri-close-line"></i>
                            </a>
                        </div>
                        @endif
                    </div>
                </form>

                <div class="card-body">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                        <i class="ri-check-line me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <div class="table-responsive table-card mb-0">
                        <table class="table align-middle table-nowrap mb-0" id="dokumenTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:45px">#</th>
                                    <th>Kode</th>
                                    <th>Nama Dokumen</th>
                                    {{-- <th style="width:130px">Divisi</th> --}}
                                    <th style="width:80px">Rev</th>
                                    <th style="width:100px">Berlaku</th>
                                    <th style="width:100px">Kategori</th>
                                    <th style="width:130px">Prosedur</th>
                                    <th style="width:60px">Pasal</th>
                                    <th style="width:80px">Link</th>
                                    <th>Ket.</th>
                                    @if($isSuperAdmin)
                                    <th class="text-center" style="width:80px">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dokumenList as $i => $d)
                                <tr class="{{ !$d->is_active ? 'table-secondary' : '' }}">
                                    <td class="text-center text-muted" style="font-size:0.75rem">
                                        {{ $dokumenList->firstItem() + $i }}
                                    </td>
                                    <td>
                                        @if($d->kode_dokumen)
                                            <code class="px-2 py-1 rounded" style="background:#e7f1ff;color:#0d47a1;font-size:0.72rem;letter-spacing:0.3px">{{ $d->kode_dokumen }}</code>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="{{ !$d->is_active ? 'text-muted text-decoration-line-through' : 'fw-semibold' }}"
                                            style="font-size:0.82rem">{{ $d->nama_dokumen }}</span>
                                        @if(!$d->is_active)
                                            <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:0.65rem">Nonaktif</span>
                                        @endif
                                    </td>
                                    {{-- <td>
                                        @if($d->divisi)
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.7rem">{{ $d->divisi->nama }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td> --}}
                                    <td class="text-center">
                                        <span class="badge bg-warning-subtle text-warning" style="font-size:0.7rem">{{ $d->revisi_ke ?? '0' }}</span>
                                    </td>
                                    <td class="small text-muted">
                                        @if($d->tanggal_berlaku)
                                            {{ $d->tanggal_berlaku->format('d/m/Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($d->kategori === 'FORMULIR')
                                            <span class="badge bg-info-subtle text-info" style="font-size:0.65rem">FORM</span>
                                        @else
                                            <span class="badge bg-primary-subtle text-primary" style="font-size:0.65rem">PROS</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $d->prosedur_konsultan ?? '—' }}</td>
                                    <td class="text-center small text-muted">{{ $d->pasal ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($d->link_dokumen)
                                            <a href="{{ $d->link_dokumen }}" target="_blank" rel="noopener noreferrer"
                                               class="btn btn-soft-primary btn-sm py-1 px-2 d-inline-flex align-items-center gap-1"
                                               style="font-size:0.7rem" title="Lihat prosedur lengkap di Google Drive">
                                                <i class="ri-external-link-line" style="font-size:0.8rem"></i>
                                                <span>Drive</span>
                                            </a>
                                        @else
                                            <span class="badge bg-light text-secondary border" style="font-size:0.65rem; cursor:default">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted" style="max-width:80px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">
                                        {{ $d->keterangan ?? '—' }}
                                    </td>
                                    @if($isSuperAdmin)
                                    <td class="text-center">
                                        <button class="btn btn-soft-warning btn-sm btn-edit"
                                            data-id="{{ $d->id }}"
                                            data-nama_dokumen="{{ e($d->nama_dokumen) }}"
                                            data-divisi_id="{{ $d->divisi_id ?? '' }}"
                                            data-prosedur_konsultan="{{ e($d->prosedur_konsultan) }}"
                                            data-pasal="{{ e($d->pasal) }}"
                                            data-kode_dokumen="{{ e($d->kode_dokumen) }}"
                                            data-tanggal_berlaku="{{ $d->tanggal_berlaku?->format('Y-m-d') }}"
                                            data-revisi_ke="{{ e($d->revisi_ke) }}"
                                            data-keterangan="{{ e($d->keterangan) }}"
                                            data-kategori="{{ e($d->kategori) }}"
                                            data-link_dokumen="{{ e($d->link_dokumen) }}"
                                            data-is_active="{{ $d->is_active ? '1' : '0' }}"
                                            data-bs-toggle="modal" data-bs-target="#modalEdit">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <form action="{{ route('user.dokumen-iso.destroy', ['userId' => $userId, 'id' => $d->id]) }}"
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
                                <tr>
                                    <td colspan="{{ $isSuperAdmin ? 11 : 10 }}" class="text-center text-muted py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="ri-file-search-line fs-1 mb-2 opacity-25"></i>
                                            <p class="mb-0" style="font-size:0.82rem">Belum ada dokumen ISO</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('shared._pagination', ['paginator' => $dokumenList])
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah --}}
    @if($isSuperAdmin)
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('user.dokumen-iso.store', ['userId' => $userId]) }}" method="POST">
                @csrf
                <div class="modal-content" style="border-top: 3px solid #0f4c9e;">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="modalTambahLabel">
                            <i class="ri-file-add-line text-primary me-1"></i> Tambah Dokumen ISO
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Dokumen <span class="text-danger">*</span></label>
                            <input type="text" name="nama_dokumen" class="form-control" required maxlength="255"
                                placeholder="Contoh: SOP Penerimaan Santri Baru">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Divisi</label>
                                <select name="divisi_id" class="form-select">
                                    <option value="">— Pilih —</option>
                                    @foreach($divisiList as $div)
                                        <option value="{{ $div->id }}">{{ $div->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kode Dokumen</label>
                                <input type="text" name="kode_dokumen" class="form-control" maxlength="50" placeholder="ISO-001">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" class="form-select">
                                    <option value="PROSEDUR">PROSEDUR</option>
                                    <option value="FORMULIR">FORMULIR</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Berlaku</label>
                                <input type="date" name="tanggal_berlaku" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pasal</label>
                                <input type="text" name="pasal" class="form-control" maxlength="100" placeholder="4.2">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Prosedur Konsultan</label>
                                <input type="text" name="prosedur_konsultan" class="form-control" maxlength="255" placeholder="PK-001">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Revisi</label>
                                <input type="text" name="revisi_ke" class="form-control" maxlength="20" placeholder="3">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Link Google Drive</label>
                            <input type="url" name="link_dokumen" class="form-control" maxlength="500"
                                placeholder="https://drive.google.com/...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="hstack gap-2 justify-content-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formEdit" method="POST">
                @csrf @method('PUT')
                <div class="modal-content" style="border-top: 3px solid #f59e0b;">
                    <div class="modal-header py-2">
                        <h5 class="modal-title">
                            <i class="ri-edit-2-line text-warning me-1"></i> Edit Dokumen ISO
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Dokumen <span class="text-danger">*</span></label>
                            <input type="text" name="nama_dokumen" id="edit_nama_dokumen" class="form-control" required maxlength="255">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Divisi</label>
                                <select name="divisi_id" id="edit_divisi_id" class="form-select">
                                    <option value="">— Pilih —</option>
                                    @foreach($divisiList as $div)
                                        <option value="{{ $div->id }}">{{ $div->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kode Dokumen</label>
                                <input type="text" name="kode_dokumen" id="edit_kode_dokumen" class="form-control" maxlength="50">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" id="edit_kategori" class="form-select">
                                    <option value="PROSEDUR">PROSEDUR</option>
                                    <option value="FORMULIR">FORMULIR</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tanggal Berlaku</label>
                                <input type="date" name="tanggal_berlaku" id="edit_tanggal_berlaku" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Prosedur Konsultan</label>
                                <input type="text" name="prosedur_konsultan" id="edit_prosedur_konsultan" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pasal</label>
                                <input type="text" name="pasal" id="edit_pasal" class="form-control" maxlength="100">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Revisi</label>
                                <input type="text" name="revisi_ke" id="edit_revisi_ke" class="form-control" maxlength="20">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Status</label>
                                <select name="is_active" id="edit_is_active" class="form-select">
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Keterangan</label>
                            <input type="text" name="keterangan" id="edit_keterangan" class="form-control" maxlength="255">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Link Google Drive</label>
                            <input type="url" name="link_dokumen" id="edit_link_dokumen" class="form-control" maxlength="500"
                                placeholder="https://drive.google.com/...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="hstack gap-2 justify-content-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning text-dark">
                                <i class="ri-save-line me-1"></i> Update
                            </button>
                        </div>
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
const baseUrl = '/personalia/' + window.userId + '/dokumen-iso';

document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('formEdit').action = baseUrl + '/' + this.dataset.id;
        document.getElementById('edit_nama_dokumen').value = this.dataset.nama_dokumen || '';
        document.getElementById('edit_divisi_id').value = this.dataset.divisi_id || '';
        document.getElementById('edit_prosedur_konsultan').value = this.dataset.prosedur_konsultan || '';
        document.getElementById('edit_pasal').value = this.dataset.pasal || '';
        document.getElementById('edit_kode_dokumen').value = this.dataset.kode_dokumen || '';
        document.getElementById('edit_tanggal_berlaku').value = this.dataset.tanggal_berlaku || '';
        document.getElementById('edit_revisi_ke').value = this.dataset.revisi_ke || '';
        document.getElementById('edit_keterangan').value = this.dataset.keterangan || '';
        document.getElementById('edit_kategori').value = this.dataset.kategori || 'PROSEDUR';
        document.getElementById('edit_link_dokumen').value = this.dataset.link_dokumen || '';
        document.getElementById('edit_is_active').value = this.dataset.is_active || '1';
    });
});

document.querySelectorAll('.form-delete').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        Swal.fire({
            title: 'Hapus dokumen ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33'
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });
});

function checkAll(src) {
    document.querySelectorAll('.chk-child').forEach(cb => cb.checked = src.checked);
    updateBulkUI();
}
function checkChange() { updateBulkUI(); }

function updateBulkUI() {
    const checked = document.querySelectorAll('.chk-child:checked').length;
    const btn = document.getElementById('remove-actions');
    if (checked > 0) {
        btn.classList.remove('d-none');
        btn.innerHTML = `<i class="ri-delete-bin-2-line me-1"></i> Hapus Terpilih (${checked})`;
    } else {
        btn.classList.add('d-none');
    }
}

updateBulkUI();
</script>
@endsection