{{-- Pelatihan: Daftar Pelatihan --}}
@extends('layouts.master')
@section('title') Daftar Pelatihan @endsection

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
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Personalia @endslot
    @slot('li_2') Pelatihan @endslot
    @slot('title') Daftar Pelatihan @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Daftar Pelatihan & Sertifikasi</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Kelola program pelatihan dan sertifikasi GTK.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.pelatihan.sertifikasi', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-medal-line me-1"></i> Sertifikasi
        </a>
        <a href="{{ route('user.ats.pelatihan.rekap', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-file-chart-line me-1"></i> Rekap
        </a>
        <a href="{{ route('user.ats.pelatihan.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
            <i class="ri-add-line me-1"></i> Tambah Pelatihan
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-indigo-subtle rounded fs-2"><i class="ri-book-open-line text-indigo"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Pelatihan</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total_pelatihan'] ?? 0 }}</h3>
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
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-team-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Peserta</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total_peserta'] ?? 0 }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Selesai</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['completed'] ?? 0 }}</h3>
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
                        <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-calendar-event-line text-info"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Akan Datang</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['upcoming'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-file-list-3-line text-indigo me-1"></i> Daftar Pelatihan</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Pelatihan</th>
                    <th>Jenis</th>
                    <th>Tanggal</th>
                    <th>Tempat</th>
                    <th>Peserta</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pelatihans as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-xs rounded bg-indigo-subtle text-indigo d-flex align-items-center justify-content-center">
                                <i class="ri-graduation-cap-line"></i>
                            </div>
                            <div>
                                <span class="fw-medium">{{ $item->nama }}</span>
                                @if($item->vendor)
                                    <span class="text-muted small d-block" style="font-size:.75rem">{{ $item->vendor }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($item->jenis ?? '-') }}</span>
                    </td>
                    <td class="text-muted" style="font-size:.85rem">
                        {{ $item->tanggal_mulai ? Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y') : '-' }}
                        @if($item->tanggal_selesai && $item->tanggal_selesai != $item->tanggal_mulai)
                            - {{ Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y') }}
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:.85rem">{{ $item->lokasi ?? '-' }}</td>
                    <td>
                        <span class="badge bg-info-subtle text-info">{{ $item->pesertas->count() }} org</span>
                    </td>
                    <td>
                        @if($item->status === 'draft')
                            <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                        @elseif($item->status === 'ditetapkan')
                            <span class="badge bg-primary-subtle text-primary">Ditetapkan</span>
                        @elseif($item->status === 'selesai')
                            <span class="badge bg-success-subtle text-success">Selesai</span>
                        @elseif($item->status === 'dibatalkan')
                            <span class="badge bg-danger-subtle text-danger">Dibatalkan</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('user.ats.pelatihan.show', ['userId' => $userId, 'id' => $item->id]) }}" class="btn btn-sm btn-light" title="Lihat">
                            <i class="ri-eye-line"></i>
                        </a>
                        <a href="{{ route('user.ats.pelatihan.peserta', ['userId' => $userId, 'pelatihanId' => $item->id]) }}" class="btn btn-sm btn-light" title="Peserta">
                            <i class="ri-group-line"></i>
                        </a>
                        <a href="{{ route('user.ats.pelatihan.edit', ['userId' => $userId, 'id' => $item->id]) }}" class="btn btn-sm btn-light" title="Edit">
                            <i class="ri-edit-line"></i>
                        </a>
                        <form action="{{ route('user.ats.pelatihan.destroy', ['userId' => $userId, 'id' => $item->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pelatihan ini?')">
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
    @if($pelatihans->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Menampilkan {{ $pelatihans->firstItem() ?? 0 }} - {{ $pelatihans->lastItem() ?? 0 }} dari {{ $pelatihans->total() }} data</span>
            <nav>{{ $pelatihans->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>
@endsection