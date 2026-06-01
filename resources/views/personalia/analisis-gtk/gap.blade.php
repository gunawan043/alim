{{-- Analisis GTK: Gap Analysis --}}
@extends('layouts.master')
@section('title') Gap Analysis GTK @endsection

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
.gap-negatif{color:#dc2626;font-weight:700}
.gap-netral{color:#d97706;font-weight:700}
.gap-positif{color:#16a34a;font-weight:700}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#6366f118;color:#4f46e5;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-arrow-right-circle-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Gap Analysis</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Analisis kesenjangan kompetensi dan kebutuhan pelatihan GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.analisis-gtk.rasio-ideal', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-pie-chart-2-line me-1"></i> Rasio Ideal
        </a>
        <a href="{{ route('user.analisis-gtk.beban-kerja', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-bar-chart-2-line me-1"></i> Beban Kerja
        </a>
        <a href="{{ route('user.analisis-gtk.proyeksi', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-line-chart-line me-1"></i> Proyeksi SDM
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
                            <i class="ri-file-list-3-line" style="color:#4f46e5;"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Total Kompetensi</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalKompetensi ?? 0 }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Gap Negatif</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $gapNegatif ?? 0 }}</h3>
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
                            <i class="ri-checkbox-circle-line text-warning"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Sesuai Standar</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $sesuaiStandar ?? 0 }}</h3>
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
                            <i class="ri-star-line text-success"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Di Bawah Standar</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $diBawahStandar ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar rounded-2 border p-3 mb-3 no-print">
    <form method="GET" action="{{ route('user.analisis-gtk.gap', $userId) }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Nama GTK</label>
            <select name="gtk_id" class="form-select form-select-sm">
                <option value="">Semua GTK</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Jenis Kompetensi</label>
            <select name="jenis_kompetensi" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="pedagogik">Pedagogik</option>
                <option value="kepribadian">Kepribadian</option>
                <option value="profesional">Profesional</option>
                <option value="sosial">Sosial</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-1">
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
            <a href="{{ route('user.analisis-gtk.gap', $userId) }}" class="btn btn-light btn-sm"><i class="ri-reset-right-line me-1"></i>Reset</a>
        </div>
        <div class="col-md-3 d-flex align-items-end justify-content-end">
            <button onclick="window.print()" class="btn btn-light btn-sm"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-table-2 text-primary me-1"></i> Detail Gap Analysis Kompetensi</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th class="bg-light text-center" style="width:48px">No</th>
                    <th class="bg-light">Nama GTK</th>
                    <th class="bg-light">Kompetensi</th>
                    <th class="bg-light text-center">Standar</th>
                    <th class="bg-light text-center">Aktual</th>
                    <th class="bg-light text-center">Gap</th>
                    <th class="bg-light text-center">Indikator</th>
                    <th class="bg-light text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gapList ?? [] as $item)
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
                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $item['kompetensi'] ?? '-' }}</span></td>
                        <td class="text-center">{{ $item['standar'] ?? 0 }}</td>
                        <td class="text-center">{{ $item['aktual'] ?? 0 }}</td>
                        <td class="text-center">
                            @php $gap = ($item['aktual'] ?? 0) - ($item['standar'] ?? 0); @endphp
                            @if($gap < 0)
                                <span class="gap-negatif">{{ $gap }}</span>
                            @elseif($gap == 0)
                                <span class="gap-netral">0</span>
                            @else
                                <span class="gap-positif">+{{ $gap }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php $gap = ($item['aktual'] ?? 0) - ($item['standar'] ?? 0); @endphp
                            @if($gap < 0)
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Perlu Pelatihan</span>
                            @elseif($gap == 0)
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Sesuai</span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Unggul</span>
                            @endif
                        </td>
                        <td class="text-center no-print">
                            <a href="{{ route('user.analisis-gtk.gap', $userId) }}" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div style="color:#6366f1;opacity:.4"><i class="ri-arrow-right-circle-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Belum ada data</h5>
                            <p class="text-muted mb-0 small">Data gap analysis akan muncul di sini</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($gapList) && method_exists($gapList, 'hasPages') && $gapList->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center no-print">
            <span class="text-muted small">Menampilkan {{ $gapList->firstItem() ?? 0 }} - {{ $gapList->lastItem() ?? 0 }} dari {{ $gapList->total() }} data</span>
            <nav>{{ $gapList->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>
@endsection
