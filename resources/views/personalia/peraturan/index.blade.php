{{-- Peraturan: Daftar Dokumen --}}
@extends('layouts.master')
@section('title') Daftar Peraturan @endsection

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
    @slot('title') Daftar Peraturan @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Daftar Peraturan & Dokumen</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Kelola semua dokumen peraturan yang berlaku di lingkungan ponpes.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.peraturan.kategori', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-folder-line me-1"></i> Kategori
        </a>
        <a href="{{ route('user.ats.peraturan.violation', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-error-warning-line me-1"></i> Pelanggaran
        </a>
        <a href="{{ route('user.ats.peraturan.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
            <i class="ri-add-line me-1"></i> Tambah Peraturan
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-secondary-subtle rounded fs-2"><i class="ri-file-3-line text-secondary"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Dokumen</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total_dokumen'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Aktif</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['aktif'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-edit-2-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Draft</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['draft'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-shield-warning-line text-danger"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Pelanggaran Bulan Ini</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['violations_this_month'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-file-text-line text-secondary me-1"></i> Daftar Dokumen Peraturan</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Nomor</th>
                    <th>Tanggal Berlaku</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dokumens as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <a href="{{ route('user.ats.peraturan.show', ['userId' => $userId, 'id' => $item->id]) }}" class="fw-medium text-dark text-decoration-none">
                            {{ $item->judul }}
                        </a>
                        @if($item->file_path)
                            <i class="ri-attach-2 text-muted ms-1" style="font-size:.7rem"></i>
                        @endif
                    </td>
                    <td>
                        @if($item->kategori)
                            <span class="badge" style="background:{{ $item->kategori->warna ?? '#6c757d' }}20;color:{{ $item->kategori->warna ?? '#6c757d' }};border:1px solid {{ $item->kategori->warna ?? '#6c757d' }}40">
                                {{ $item->kategori->nama }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:.85rem">{{ $item->nomor ?? '-' }}</td>
                    <td class="text-muted" style="font-size:.85rem">
                        {{ $item->tanggal_berlaku ? $item->tanggal_berlaku->format('d/m/Y') : '-' }}
                    </td>
                    <td>
                        @if($item->status === 'aktif')
                            <span class="badge bg-success-subtle text-success">Aktif</span>
                        @elseif($item->status === 'draft')
                            <span class="badge bg-warning-subtle text-warning">Draft</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Diarsipkan</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('user.ats.peraturan.show', ['userId' => $userId, 'id' => $item->id]) }}" class="btn btn-sm btn-light" title="Lihat">
                            <i class="ri-eye-line"></i>
                        </a>
                        <a href="{{ route('user.ats.peraturan.edit', ['userId' => $userId, 'id' => $item->id]) }}" class="btn btn-sm btn-light" title="Edit">
                            <i class="ri-edit-line"></i>
                        </a>
                        <form action="{{ route('user.ats.peraturan.destroy', ['userId' => $userId, 'id' => $item->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus dokumen ini?')">
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
    @if($dokumens->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Menampilkan {{ $dokumens->firstItem() ?? 0 }} - {{ $dokumens->lastItem() ?? 0 }} dari {{ $dokumens->total() }} data</span>
            <nav>{{ $dokumens->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>
@endsection