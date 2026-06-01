@extends('layouts.master')
@section('title') Laporan Kesejahteraan @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#ecfdf5 0%,#f0fdf4 100%);border:1px solid #a7f3d0;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#064e3b 0%,#022c22 100%);border-color:#059669}
.stat-card{transition:all .25s ease;cursor:default}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Kesejahteraan & Benefit @endslot
    @slot('li_2') Laporan @endslot
    @slot('title') Laporan Kesejahteraan @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Laporan Kesejahteraan GTK</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Ringkasan kesejahteraan dan manfaat yang diterima GTK</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.kesejahteraan.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
        <button class="btn btn-success btn-sm"><i class="ri-file-excel-line me-1"></i> Export</button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-heart-pulse-line text-success"></i></span></div>
                    <div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Program</p><h3 class="fw-bold mb-0">0</h3></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-user-follow-line text-primary"></i></span></div>
                    <div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">GTK Terekam</p><h3 class="fw-bold mb-0">0</h3></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-money-dollar-circle-line text-warning"></i></span></div>
                    <div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Nilai (Rp)</p><h3 class="fw-bold mb-0">—</h3></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-calendar-check-line text-info"></i></span></div>
                    <div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Periode</p><h3 class="fw-bold mb-0">—</h3></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-semibold mb-0">Rekapan per Periode</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Periode</th>
                        <th>Total GTK</th>
                        <th>Total Nilai (Rp)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" class="text-center py-5">
                        <i class="ri-bar-chart-box-line" style="font-size:3rem;color:#9ca3af"></i>
                        <h6 class="mt-2 text-muted">Belum ada laporan.</h6>
                        <p class="text-muted" style="font-size:.8rem">Laporan akan tersedia setelah ada data kesejahteraan.</p>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection