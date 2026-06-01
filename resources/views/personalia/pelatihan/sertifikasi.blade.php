{{-- Pelatihan: Sertifikasi GTK --}}
@extends('layouts.master')
@section('title') Sertifikasi GTK @endsection

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
    @slot('title') Sertifikasi GTK @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Sertifikasi GTK</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Kelola dan pantau sertifikasi tenaga pendidik GTK.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.pelatihan.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Daftar
        </a>
        <a href="{{ route('user.ats.pelatihan.peserta', ['userId' => $userId, 'pelatihanId' => 0]) }}" class="btn btn-light btn-sm">
            <i class="ri-group-line me-1"></i> Peserta
        </a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSertifikasiModal">
            <i class="ri-add-line me-1"></i> Tambah Sertifikasi
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-indigo-subtle rounded fs-2"><i class="ri-medal-line text-indigo"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Sertifikasi</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $sertifikasis->total() }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Berlaku</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $sertifikasis->where('tanggal_expired', '>', now())->count() }}</h3>
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
                        <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-error-warning-line text-danger"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Expired</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $sertifikasis->where('tanggal_expired', '<=', now())->whereNotNull('tanggal_expired')->count() }}</h3>
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
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-time-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Akan Expired</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $sertifikasis->whereBetween('tanggal_expired', [now(), now()->addMonths(3)])->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-award-line text-indigo me-1"></i> Daftar Sertifikasi GTK</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th>No</th>
                    <th>GTK</th>
                    <th>Sertifikasi</th>
                    <th>Issuer</th>
                    <th>Tanggal Terbit</th>
                    <th>Expired</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sertifikasis as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-xs rounded-circle bg-indigo-subtle text-indigo d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem">
                                {{ strtoupper(substr($item->gtk->nama ?? 'G', 0, 1)) }}
                            </div>
                            <span class="fw-medium">{{ $item->gtk->nama ?? '-' }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="fw-medium">{{ $item->nama_sertifikat ?? '-' }}</span>
                        @if($item->nomor_sertifikat)
                            <span class="d-block text-muted" style="font-size:.75rem">{{ $item->nomor_sertifikat }}</span>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:.85rem">{{ $item->institusi_penerbit ?? '-' }}</td>
                    <td class="text-muted" style="font-size:.85rem">
                        {{ $item->tanggal_terbit ? Carbon\Carbon::parse($item->tanggal_terbit)->format('d/m/Y') : '-' }}
                    </td>
                    <td class="text-muted" style="font-size:.85rem">
                        {{ $item->tanggal_expired ? Carbon\Carbon::parse($item->tanggal_expired)->format('d/m/Y') : '-' }}
                    </td>
                    <td>
                        @if($item->tanggal_expired && $item->tanggal_expired < now())
                            <span class="badge bg-danger-subtle text-danger">Expired</span>
                        @elseif($item->tanggal_expired && $item->tanggal_expired < now()->addMonths(3))
                            <span class="badge bg-warning-subtle text-warning">Akan Expired</span>
                        @else
                            <span class="badge bg-success-subtle text-success">Berlaku</span>
                        @endif
                    </td>
                    <td>
                        @if($item->file_path)
                            <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="btn btn-sm btn-light" title="Lihat File">
                                <i class="ri-file-pdf-line"></i>
                            </a>
                        @endif
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
    @if($sertifikasis->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Menampilkan {{ $sertifikasis->firstItem() ?? 0 }} - {{ $sertifikasis->lastItem() ?? 0 }} dari {{ $sertifikasis->total() }} data</span>
            <nav>{{ $sertifikasis->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>

{{-- Modal: Tambah Sertifikasi --}}
<div class="modal fade" id="addSertifikasiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-medal-add-line me-1"></i> Tambah Sertifikasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('user.ats.pelatihan.sertifikasiStore', ['userId' => $userId]) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">GTK</label>
                        <select name="gtk_id" class="form-select" required>
                            <option value="">-- Pilih GTK --</option>
                            @foreach($gtkList as $gtk)
                                <option value="{{ $gtk->id }}">{{ $gtk->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Sertifikat <span class="text-danger">*</span></label>
                        <input type="text" name="nama_sertifikat" class="form-control" placeholder="Contoh: Sertifikat Pendidik" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Sertifikat</label>
                        <input type="text" name="nomor_sertifikat" class="form-control" placeholder="Nomor sertifikat...">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Institusi Penerbit</label>
                            <input type="text" name="institusi_penerbit" class="form-control" placeholder="Contoh: Kemendikbud">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <input type="text" name="kategori" class="form-control" placeholder="Contoh: Profesi">
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Terbit <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_terbit" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Expired</label>
                            <input type="date" name="tanggal_expired" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3 mt-2">
                        <label class="form-label">File Sertifikat</label>
                        <input type="file" name="file_path" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Format: PDF, JPG, PNG. Maks 10MB.</small>
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