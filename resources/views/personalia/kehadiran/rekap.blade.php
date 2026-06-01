{{-- Kehadiran: Rekap Kehadiran GTK --}}
@extends('layouts.master')
@section('title') Rekap Kehadiran GTK @endsection

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
            <i class="ri-file-chart-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Rekap Kehadiran GTK</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Rekapitulasi kehadiran seluruh GTK berdasarkan periode tertentu</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.kehadiran.cuti-izin', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-calendar-check-line me-1"></i> Cuti & Izin
        </a>
        <a href="{{ route('user.kehadiran.pergantian-jam', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-loop-right-line me-1"></i> Pergantian Jam
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
                            <i class="ri-team-line" style="color:#16a34a;"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Total GTK</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalGtk ?? 0 }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Hadir</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalHadir ?? 0 }}</h3>
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
                            <i class="ri-close-circle-line text-danger"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Tidak Hadir</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $tidakHadir ?? 0 }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Terlambat</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $terlambat ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar rounded-2 border p-3 mb-3 no-print">
    <form method="GET" action="{{ route('user.kehadiran.rekap', $userId) }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Periode</label>
            <input type="month" name="periode" class="form-control form-control-sm" value="{{ request('periode') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Nama GTK</label>
            <select name="gtk_id" class="form-select form-select-sm">
                <option value="">Semua GTK</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-1">
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
            <a href="{{ route('user.kehadiran.rekap', $userId) }}" class="btn btn-light btn-sm"><i class="ri-reset-right-line me-1"></i>Reset</a>
        </div>
        <div class="col-md-3 d-flex align-items-end justify-content-end gap-1">
            <button onclick="window.print()" class="btn btn-light btn-sm"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-table-2 text-primary me-1"></i> Rekapitulasi Kehadiran GTK</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th class="bg-light text-center" style="width:48px">No</th>
                    <th class="bg-light">Nama GTK</th>
                    <th class="bg-light">Jenis GTK</th>
                    <th class="bg-light text-center">Hadir</th>
                    <th class="bg-light text-center">Izin</th>
                    <th class="bg-light text-center">Sakit</th>
                    <th class="bg-light text-center">Alfa</th>
                    <th class="bg-light text-center">Terlambat</th>
                    <th class="bg-light text-center">Total Hari</th>
                    <th class="bg-light text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kehadiranList ?? [] as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($item['nama'] ?? 'G', 0, 1)) }}
                                </div>
                                <span class="fw-medium">{{ $item['nama'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $item['jenis'] ?? '-' }}</span></td>
                        <td class="text-center">{{ $item['hadir'] ?? 0 }}</td>
                        <td class="text-center">{{ $item['izin'] ?? 0 }}</td>
                        <td class="text-center">{{ $item['sakit'] ?? 0 }}</td>
                        <td class="text-center">{{ $item['alfa'] ?? 0 }}</td>
                        <td class="text-center">{{ $item['terlambat'] ?? 0 }}</td>
                        <td class="text-center fw-bold">{{ $item['total_hari'] ?? 0 }}</td>
                        <td class="text-center no-print">
                            <a href="{{ route('user.kehadiran.rekap', $userId) }}" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div style="color:#22c55e;opacity:.4"><i class="ri-file-chart-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Belum ada data</h5>
                            <p class="text-muted mb-0 small">Data rekap kehadiran GTK akan muncul di sini</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($kehadiranList) && method_exists($kehadiranList, 'hasPages') && $kehadiranList->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center no-print">
            <span class="text-muted small">Menampilkan {{ $kehadiranList->firstItem() ?? 0 }} - {{ $kehadiranList->lastItem() ?? 0 }} dari {{ $kehadiranList->total() }} data</span>
            <nav>{{ $kehadiranList->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>
@endsection