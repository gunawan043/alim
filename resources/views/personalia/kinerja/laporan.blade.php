@extends('layouts.master')
@section('title') Laporan Kinerja GTK @endsection
@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.page-header-card{background:linear-gradient(135deg,#fff7ed 0%,#fffbf5 100%);border:1px solid #fed7aa;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1a0f00 0%,#1f1500 100%);border-color:#92400e}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php
$userId = request()->route('userId') ?? auth()->id();
$currentUser = auth()->user();
$isAdmin = $currentUser->hasAnyRole(['Personalia','Super Admin','Admin Tata Usaha']);
$totalPenilaian = \App\Models\KinerjaPenilaian::count();
$avgSkor = \App\Models\KinerjaPenilaian::whereNotNull('total_skor')->avg('total_skor') ?? 0;
$countA = \App\Models\KinerjaPenilaian::where('nilai_huruf','A')->count();
$countFinal = \App\Models\KinerjaPenilaian::where('status','final')->count();
@endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#f9731618;color:#ea580c;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-file-chart-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Laporan Kinerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Lihat dan unduh laporan hasil penilaian kinerja GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.ats.kinerja.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Daftar</a>
        <a href="{{ route('user.ats.kinerja.indikator', $userId) }}" class="btn btn-light btn-sm"><i class="ri-list-checks me-1"></i>Indikator</a>
        <a href="{{ route('user.ats.kinerja.reward', $userId) }}" class="btn btn-light btn-sm"><i class="ri-medal-line me-1"></i>Reward</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-file-chart-line text-primary"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Total Penilaian</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalPenilaian }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-info">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-star-line text-info"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Rata-rata Skor</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($avgSkor,1) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-medal-line text-success"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Nilai A</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $countA }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-warning"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Status Final</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $countFinal }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-bar-chart-2-line text-primary me-1"></i> Grafik Distribusi Nilai</h5>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-5">
                    <div style="width:72px;height:72px;background:#f9731618;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                        <i class="ri-file-chart-line" style="font-size:2rem;"></i>
                    </div>
                    <h5 class="fw-semibold">Grafik dalam pengembangan</h5>
                    <p class="text-muted mb-0 small">Fitur grafik dan ekspor laporan akan segera hadir.</p>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-3 text-center">
                        <div class="p-3 rounded bg-success-subtle">
                            <h2 class="fw-bold text-success mb-0">{{ $countA }}</h2>
                            <span class="badge bg-success-subtle text-success mt-1">Nilai A</span>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="p-3 rounded bg-primary-subtle">
                            <h2 class="fw-bold text-primary mb-0">{{ \App\Models\KinerjaPenilaian::where('nilai_huruf','B')->count() }}</h2>
                            <span class="badge bg-primary-subtle text-primary mt-1">Nilai B</span>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="p-3 rounded bg-warning-subtle">
                            <h2 class="fw-bold text-warning mb-0">{{ \App\Models\KinerjaPenilaian::where('nilai_huruf','C')->count() }}</h2>
                            <span class="badge bg-warning-subtle text-warning mt-1">Nilai C</span>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="p-3 rounded bg-danger-subtle">
                            <h2 class="fw-bold text-danger mb-0">{{ \App\Models\KinerjaPenilaian::where('nilai_huruf','D')->count() }}</h2>
                            <span class="badge bg-danger-subtle text-danger mt-1">Nilai D</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-download-line text-primary me-1"></i> Aksi</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2">
                    @if($isAdmin)
                    <a href="{{ route('user.ats.kinerja.laporan.export', $userId) }}" class="btn btn-outline-primary btn-sm no-print"><i class="ri-fileExcel-line me-1"></i> Export Excel</a>
                    <a href="javascript:window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="ri-printer-line me-1"></i> Cetak Laporan</a>
                    @endif
                    <a href="{{ route('user.ats.kinerja.index', $userId) }}" class="btn btn-light btn-sm no-print"><i class="ri-list-check me-1"></i> Lihat Daftar Penilaian</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection