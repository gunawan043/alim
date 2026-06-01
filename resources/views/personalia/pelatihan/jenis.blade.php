{{-- Pelatihan: Jenis Pelatihan --}}
@extends('layouts.master')
@section('title') Jenis Pelatihan @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#eef2ff 0%,#f5f7ff 100%);border:1px solid #c7d2fe;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1e1b4b 0%,#1e1a2e 100%);border-color:#4338ca}
.stat-card{transition:all .25s ease;cursor:default}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:max-content;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:12px 16px;word-break:break-word}
.table-freeze th:first-child,.table-freeze td:first-child{position:sticky;left:0;z-index:10;background:#fff;min-width:200px;box-shadow:2px 0 5px rgba(0,0,0,.05)}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
[data-bs-theme="dark"] .table-freeze th:first-child,[data-bs-theme="dark"] .table-freeze td:first-child{background:#1e293b}
[data-bs-theme="dark"] .table-freeze thead th{background:#1e293b}
[data-bs-theme="dark"] .form-control,[data-bs-theme="dark"] .form-select,[data-bs-theme="dark"] textarea{background:#1e293b;color:#e2e8f0;border-color:#334155}
[data-bs-theme="dark"] label{color:#cbd5e1}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Personalia @endslot
    @slot('li_2') Pelatihan @endslot
    @slot('title') Jenis Pelatihan @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Jenis Pelatihan</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Kelola jenis dan kategori program pelatihan.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.pelatihan.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Daftar
        </a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addJenisModal">
            <i class="ri-add-line me-1"></i> Tambah Jenis
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-indigo-subtle rounded fs-2"><i class="ri-folder-line text-indigo"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Jenis</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $jeniss->total() ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-folder-chart-line text-indigo me-1"></i> Daftar Jenis Pelatihan</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Jenis</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Program</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jeniss as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><span class="fw-medium">{{ $item->nama ?? '-' }}</span></td>
                    <td class="text-muted" style="font-size:.85rem">{{ $item->deskripsi ?? '-' }}</td>
                    <td><span class="badge bg-info-subtle text-info">{{ $item->pelatihans->count() ?? 0 }}</span></td>
                    <td>
                        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editJenisModal{{ $item->id }}" title="Edit">
                            <i class="ri-edit-line"></i>
                        </button>
                        <form action="{{ route('user.ats.pelatihan.jenis.destroy', ['userId' => $userId, 'id' => $item->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jenis ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light text-danger" title="Hapus">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                {{-- Edit Modal --}}
                <div class="modal fade" id="editJenisModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="ri-edit-line me-1"></i> Edit Jenis Pelatihan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="{{ route('user.ats.pelatihan.jenis.update', ['userId' => $userId, 'id' => $item->id]) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Jenis</label>
                                        <input type="text" name="nama" class="form-control" value="{{ $item->nama }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea name="deskripsi" class="form-control" rows="3">{{ $item->deskripsi }}</textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <tr><td colspan="999" class="text-center py-5">
                    <i class="ri-inbox-line" style="font-size:3rem;color:#9ca3af"></i>
                    <h6 class="mt-2 text-muted">Belum ada data</h6>
                    <p class="text-muted" style="font-size:.8rem">Data akan muncul di sini ketika sudah ada.</p>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($jeniss->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Menampilkan {{ $jeniss->firstItem() ?? 0 }} - {{ $jeniss->lastItem() ?? 0 }} dari {{ $jeniss->total() }} data</span>
            <nav>{{ $jeniss->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>

{{-- Modal: Tambah Jenis --}}
<div class="modal fade" id="addJenisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-add-circle-line me-1"></i> Tambah Jenis Pelatihan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('user.ats.pelatihan.jenis.store', ['userId' => $userId]) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Jenis <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Pelatihan, Seminar, Workshop" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi jenis pelatihan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection