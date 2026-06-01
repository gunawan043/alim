{{-- Kalender Kegiatan: Evaluasi GTK --}}
@extends('layouts.master')
@section('title') Jadwal Evaluasi GTK @endsection

@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.page-header-card{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:1px solid #bbf7d0;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#052e16 0%,#0a2e1a 100%);border-color:#166534}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#22c55e18;color:#16a34a;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-clipboard-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Jadwal Evaluasi GTK</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Timeline dan jadwal kegiatan evaluasi kinerja GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.kalender-kegiatan.akademik', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-calendar-event-line me-1"></i> Akademik
        </a>
        <a href="{{ route('user.kalender-kegiatan.kontrak', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-file-paper-2-line me-1"></i> Kontrak
        </a>
        <a href="{{ route('user.kalender-kegiatan.training', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-graduation-cap-line me-1"></i> Training
        </a>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #16a34a;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title rounded-3 fs-2" style="background:#22c55e18;">
                            <i class="ri-file-list-3-line" style="color:#16a34a;"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Total Evaluasi</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalEvaluasi ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #16a34a;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded-3 fs-2">
                            <i class="ri-checkbox-circle-line text-success"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Selesai</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $selesai ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #d97706;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-3 fs-2">
                            <i class="ri-time-line text-warning"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Menunggu</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $menunggu ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #dc2626;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded-3 fs-2">
                            <i class="ri-error-warning-line text-danger"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Ditolak</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $ditolak ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar rounded-2 border p-3 mb-3 no-print">
    <form method="GET" action="{{ route('user.kalender-kegiatan.evaluasi', $userId) }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Nama GTK</label>
            <select name="gtk_id" class="form-select form-select-sm">
                <option value="">Semua GTK</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="menunggu">Menunggu</option>
                <option value="selesai">Selesai</option>
                <option value="ditolak">Ditolak</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-1">
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
            <a href="{{ route('user.kalender-kegiatan.evaluasi', $userId) }}" class="btn btn-light btn-sm"><i class="ri-reset-right-line me-1"></i>Reset</a>
        </div>
        <div class="col-md-3 d-flex align-items-end justify-content-end">
            <button onclick="window.print()" class="btn btn-light btn-sm"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-table-2 text-primary me-1"></i> Jadwal Evaluasi GTK</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th class="bg-light text-center" style="width:48px">No</th>
                    <th class="bg-light">Nama GTK</th>
                    <th class="bg-light">Tanggal Evaluasi</th>
                    <th class="bg-light">Penilai</th>
                    <th class="bg-light text-center">Status</th>
                    <th class="bg-light text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($evaluasiList ?? [] as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($item['nama_gtk'] ?? 'G', 0, 1)) }}
                                </div>
                                <span class="fw-medium">{{ $item['nama_gtk'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td>{{ $item['tanggal'] ?? '-' }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($item['penilai'] ?? 'P', 0, 1)) }}
                                </div>
                                <span class="small">{{ $item['penilai'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            @php $status = $item['status'] ?? 'menunggu'; @endphp
                            @if($status == 'selesai')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Selesai</span>
                            @elseif($status == 'ditolak')
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Ditolak</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Menunggu</span>
                            @endif
                        </td>
                        <td class="text-center no-print">
                            <a href="{{ route('user.kalender-kegiatan.evaluasi', $userId) }}" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div style="color:#22c55e;opacity:.4"><i class="ri-clipboard-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Belum ada data</h5>
                            <p class="text-muted mb-0 small">Data jadwal evaluasi GTK akan muncul di sini</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($evaluasiList) && method_exists($evaluasiList, 'hasPages') && $evaluasiList->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center no-print">
            <span class="text-muted small">Menampilkan {{ $evaluasiList->firstItem() ?? 0 }} - {{ $evaluasiList->lastItem() ?? 0 }} dari {{ $evaluasiList->total() }} data</span>
            <nav>{{ $evaluasiList->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>
@endsection