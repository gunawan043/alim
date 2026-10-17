@extends('layouts.master')
@section('title') Cuti & Izin @endsection

@push('css')
<style>
.page-header-card{
  background:linear-gradient(135deg,#eff6ff 0%,#f8fafc 100%);
  border:1px solid #bfdbfe;
  padding:1.25rem 1.5rem;
  border-radius:.625rem;
}
[data-bs-theme="dark"] .page-header-card{
  background:linear-gradient(135deg,#1e1b4b 0%,#1e1a2e 100%);
  border-color:#4338ca;
}
.stat-card{transition:all .25s ease;cursor:default}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:max-content;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:12px 16px;word-break:break-word}
.table-freeze th:first-child,.table-freeze td:first-child{position:sticky;left:0;z-index:10;background:#fff;min-width:200px;box-shadow:2px 0 5px rgba(0,0,0,.05)}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
[data-bs-theme="dark"] .table-freeze th:first-child,[data-bs-theme="dark"] .table-freeze td:first-child{background:#1e293b}
[data-bs-theme="dark"] .table-freeze thead th{background:#1e293b}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php
$userId = request()->route('userId') ?? auth()->id();
$currentUser = auth()->user();
$isAdmin = $currentUser && $currentUser->hasAnyRole(['Personalia','Super Admin','Admin Tata Usaha']);

// Stats from paginated collection
$menunggu  = $cutiRequests->total() > 0 ? $cutiRequests->filter(fn($r) => $r->status === 'PENDING')->count() : 0;
$disetujui = $cutiRequests->total() > 0 ? $cutiRequests->filter(fn($r) => $r->status === 'APPROVED')->count() : 0;
$ditolak   = $cutiRequests->total() > 0 ? $cutiRequests->filter(fn($r) => $r->status === 'REJECTED')->count() : 0;
$total     = $cutiRequests->total();
@endphp

@component('components.breadcrumb')
    @slot('li_1') Cuti & Izin @endslot
    @slot('title') Daftar Pengajuan @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Daftar Pengajuan Cuti & Izin</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">
            @if($isAdmin)
                Kelola semua pengajuan cuti dan izin GTK
            @else
                Kelola pengajuan cuti dan izin Anda
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="{{ route('user.cuti.quota', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-pie-chart-line me-1"></i> Kuota GTK
        </a>
        @if($isAdmin)
        <a href="{{ route('user.cuti.settings', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-settings-3-line me-1"></i> Pengaturan
        </a>
        @endif
        <a href="{{ route('user.cuti.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
            <i class="ri-add-circle-line me-1"></i> Ajukan Cuti
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-time-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Menunggu</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $menunggu }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Disetujui</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $disetujui }}</h3>
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
                        <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-close-circle-line text-danger"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Ditolak</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $ditolak }}</h3>
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
                        <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-calendar-check-line text-primary"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $total }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">
            <i class="ri-list-check me-1 text-primary"></i>
            Daftar Pengajuan Cuti & Izin
        </h5>
        <div class="d-flex gap-2 flex-wrap no-print">
            @if($isAdmin)
            <a href="{{ route('user.cuti.approval', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
                <i class="ri-checkbox-multiple-line me-1"></i> Persetujuan
            </a>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>GTK</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal Mulai - Selesai</th>
                        <th>Durasi (hari)</th>
                        <th>Status</th>
                        <th class="no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cutiRequests as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-light text-dark d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;font-size:.7rem;">
                                    {{ strtoupper(substr($item->user?->name ?? 'N', 0, 2)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold" style="font-size:.875rem">{{ $item->user?->name ?? '-' }}</div>
                                    <div class="text-muted" style="font-size:.75rem">{{ $item->user?->nik ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $item->template?->nama ?? '-' }}</span>
                        </td>
                        <td style="font-size:.85rem">
                            {{ $item->tanggal_mulai->format('d M Y') }}
                            <span class="text-muted mx-1">-</span>
                            {{ $item->tanggal_selesai->format('d M Y') }}
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $item->jumlah_hari }} hari</span>
                        </td>
                        <td>
                            @php
                                $badge = match($item->status) {
                                    'PENDING'  => '<span class="badge bg-warning-subtle text-warning">Menunggu</span>',
                                    'APPROVED' => '<span class="badge bg-success-subtle text-success">Disetujui</span>',
                                    'REJECTED' => '<span class="badge bg-danger-subtle text-danger">Ditolak</span>',
                                    default    => '<span class="badge bg-secondary-subtle">-</span>',
                                };
                            @endphp
                            {!! $badge !!}
                        </td>
                        <td class="no-print">
                            @if($item->status === 'PENDING' && ($isAdmin || $item->user_id == $currentUser?->id))
                            <div class="d-flex gap-1">
                                <form action="{{ route('user.cuti.destroy', ['userId' => $userId, 'id' => $item->id]) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Yakin hapus pengajuan ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" title="Hapus">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="999" class="text-center py-5">
                            <i class="ri-inbox-line" style="font-size:3rem;color:#9ca3af"></i>
                            <h6 class="mt-2 text-muted">Belum ada data</h6>
                            <p class="text-muted" style="font-size:.8rem">Data akan muncul di sini ketika sudah ada.</p>
                            <a href="{{ route('user.cuti.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm mt-2">
                                <i class="ri-add-circle-line me-1"></i> Ajukan Cuti Baru
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cutiRequests->hasPages())
        <div class="px-3 py-2 border-top no-print">
            {{ $cutiRequests->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection