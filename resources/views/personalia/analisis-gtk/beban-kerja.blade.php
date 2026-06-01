{{-- Analisis GTK: Beban Kerja --}}
@extends('layouts.master')
@section('title') Beban Kerja GTK @endsection

@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.page-header-card{background:linear-gradient(135deg,#eef2ff 0%,#e0e7ff 100%);border:1px solid #c7d2fe;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1a0f00 0%,#1f1500 100%);border-color:#92400e}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#6366f118;color:#4f46e5;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-bar-chart-2-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Beban Kerja GTK</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Analisis beban kerja dan distribusi jam mengajar setiap GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.analisis-gtk.rasio-ideal', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-pie-chart-2-line me-1"></i> Rasio Ideal
        </a>
        <a href="{{ route('user.analisis-gtk.proyeksi', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-line-chart-line me-1"></i> Proyeksi SDM
        </a>
        <a href="{{ route('user.analisis-gtk.gap', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-right-circle-line me-1"></i> Gap Analysis
        </a>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #4f46e5;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title rounded-3 fs-2" style="background:#6366f118;">
                            <i class="ri-team-line" style="color:#4f46e5;"></i>
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
        <div class="card stat-card" style="border-left:3px solid #2563eb;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded-3 fs-2">
                            <i class="ri-time-line text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Total Jam Mengajar</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalJamMengajar ?? 0 }} <small class="fw-normal text-muted" style="font-size:.7rem">jam/mgg</small></h3>
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
                            <i class="ri-file-list-3-line text-success"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Jam Non-Mengajar</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalJamNonMengajar ?? 0 }} <small class="fw-normal text-muted" style="font-size:.7rem">jam/mgg</small></h3>
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
                            <i class="ri-pie-chart-line text-warning"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Rasio Mengajar</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $rasioMengajar ?? '0:0' }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar rounded-2 border p-3 mb-3 no-print">
    <form method="GET" action="{{ route('user.analisis-gtk.beban-kerja', $userId) }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Jenis GTK</label>
            <select name="jenis_gtk" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="guru">Guru</option>
                <option value="tenaga-kependidikan">Tenaga Kependidikan</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Tahun Ajaran</label>
            <select name="tahun_ajaran" class="form-select form-select-sm">
                <option value="">Semua</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-1">
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
            <a href="{{ route('user.analisis-gtk.beban-kerja', $userId) }}" class="btn btn-light btn-sm"><i class="ri-reset-right-line me-1"></i>Reset</a>
        </div>
        <div class="col-md-3 d-flex align-items-end justify-content-end">
            <button onclick="window.print()" class="btn btn-light btn-sm"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-table-2 text-primary me-1"></i> Distribusi Beban Kerja GTK</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th class="bg-light text-center" style="width:48px">No</th>
                    <th class="bg-light">Nama GTK</th>
                    <th class="bg-light">Jenis GTK</th>
                    <th class="bg-light text-center">Jam Mengajar</th>
                    <th class="bg-light text-center">Jam Non-Mengajar</th>
                    <th class="bg-light text-center">Total Jam</th>
                    <th class="bg-light text-center">Beban</th>
                    <th class="bg-light text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gtkList ?? [] as $gtk)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($gtk['nama'] ?? 'G', 0, 1)) }}
                                </div>
                                <span class="fw-medium">{{ $gtk['nama'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $gtk['jenis'] ?? '-' }}</span></td>
                        <td class="text-center">{{ $gtk['jam_mengajar'] ?? 0 }} <small class="text-muted">jam</small></td>
                        <td class="text-center">{{ $gtk['jam_non_mengajar'] ?? 0 }} <small class="text-muted">jam</small></td>
                        <td class="text-center fw-bold">{{ ($gtk['jam_mengajar'] ?? 0) + ($gtk['jam_non_mengajar'] ?? 0) }} <small class="text-muted">jam</small></td>
                        <td class="text-center">
                            @php $beban = $gtk['beban'] ?? 'ringan'; @endphp
                            @if($beban == 'berat')
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Berat</span>
                            @elseif($beban == 'sedang')
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Sedang</span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Ringan</span>
                            @endif
                        </td>
                        <td class="text-center no-print">
                            <a href="{{ route('user.analisis-gtk.beban-kerja', $userId) }}" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div style="color:#6366f1;opacity:.4"><i class="ri-bar-chart-2-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Belum ada data</h5>
                            <p class="text-muted mb-0 small">Data beban kerja GTK akan muncul di sini</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($gtkList) && $gtkList->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center no-print">
            <span class="text-muted small">Menampilkan {{ $gtkList->firstItem() ?? 0 }} - {{ $gtkList->lastItem() ?? 0 }} dari {{ $gtkList->total() }} data</span>
            <nav>{{ $gtkList->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>
@endsection
