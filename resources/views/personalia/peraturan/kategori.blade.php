{{-- Peraturan: Kategori --}}
@extends('layouts.master')
@section('title') Kategori Peraturan @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);border:1px solid #cbd5e1;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);border-color:#334155}
.stat-card{transition:all .25s ease;cursor:default}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:max-content;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:12px 16px;word-break:break-word}
.table-freeze th:first-child,.table-freeze td:first-child{position:sticky;left:0;z-index:10;background:#fff;min-width:200px;box-shadow:2px 0 5px rgba(0,0,0,.05)}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
[data-bs-theme="dark"] .table-freeze th:first-child,[data-bs-theme="dark"] .table-freeze td:first-child{background:#1e293b}
[data-bs-theme="dark"] .table-freeze thead th{background:#1e293b}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Personalia @endslot
    @slot('li_2') Peraturan @endslot
    @slot('title') Kategori Peraturan @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Kategori Peraturan</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Kelola kategori untuk mengelompokkan dokumen peraturan.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.peraturan.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Daftar Peraturan
        </a>
        <a href="{{ route('user.ats.peraturan.violation', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-error-warning-line me-1"></i> Pelanggaran
        </a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addKategoriModal">
            <i class="ri-add-line me-1"></i> Tambah Kategori
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-secondary-subtle rounded fs-2"><i class="ri-folder-line text-secondary"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Kategori</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $kategoris->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-list-check text-secondary me-1"></i> Daftar Kategori</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Kategori</th>
                    <th>Jumlah Peraturan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kategoris as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-2" style="background:{{ $item->warna ?? '#6c757d' }}22;color:{{ $item->warna ?? '#6c757d' }};border:1px solid {{ $item->warna ?? '#6c757d' }}44;width:12px;height:12px;flex-shrink:0"></span>
                            <span class="fw-medium">{{ $item->nama }}</span>
                        </div>
                        @if($item->deskripsi)
                            <p class="text-muted mb-0" style="font-size:.78rem">{{ $item->deskripsi }}</p>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary">{{ $item->dokumens_count ?? $item->dokumens->count() }}</span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editKategoriModal{{ $item->id }}" title="Edit">
                            <i class="ri-edit-line"></i>
                        </button>
                        <form action="{{ route('user.ats.peraturan.kategoriDestroy', ['userId' => $userId, 'id' => $item->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light text-danger" title="Hapus">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </form>
                    </td>
                </tr>
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
</div>

{{-- Modal: Tambah Kategori --}}
<div class="modal fade" id="addKategoriModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-bookmark-add-line me-1"></i> Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('user.ats.peraturan.kategoriStore', ['userId' => $userId]) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Kedisiplinan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="2" placeholder="Deskripsi kategori..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Warna Label</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach(['#475569','#dc2626','#d97706','#16a34a','#2563eb','#7c3aed'] as $color)
                            <div>
                                <input type="radio" name="warna" value="{{ $color }}" id="color_{{ str_replace('#','',$color) }}" class="btn-check" autocomplete="off">
                                <label for="color_{{ str_replace('#','',$color) }}" class="btn btn-sm" style="background:{{ $color }};width:32px;height:32px;border-radius:50%;cursor:pointer;border:2px solid transparent"></label>
                            </div>
                            @endforeach
                        </div>
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