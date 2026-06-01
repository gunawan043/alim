{{-- Cuti: Kuota GTK --}}
@extends('layouts.master')
@section('title') Kuota Cuti GTK @endsection

@push('css')
<style>
    .table-freeze { table-layout: auto; min-width: 900px; width: 100%; margin-bottom: 0; }
    .table-freeze th, .table-freeze td { vertical-align: middle; padding: 11px 14px; word-break: break-word; }
    .table-freeze th:first-child, .table-freeze td:first-child { position: sticky; left: 0; z-index: 10; background: #fff; min-width: 180px; box-shadow: 2px 0 5px rgba(0,0,0,0.05); }
    .table-freeze thead th { position: sticky; top: 0; z-index: 20; font-weight: 600; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .table-freeze tbody tr:hover td { background: #f8f9ff; }
    [data-bs-theme="dark"] .table-freeze thead th { background: #1a1f3a; }
    [data-bs-theme="dark"] .table-freeze th:first-child,
    [data-bs-theme="dark"] .table-freeze td:first-child { background: #1a1f3a; }
    .stat-card { transition: all 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
    .page-header-card { background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%); padding: 1.25rem 1.5rem; border: 1px solid #e4e7f5 !important; }
    .filter-bar { background: #fff; }
    [data-bs-theme="dark"] .page-header-card { background: linear-gradient(135deg, #1a1f3a 0%, #1e2445 100%); border-color: #2a3055 !important; }
    [data-bs-theme="dark"] .filter-bar { background: #1a1f3a; border-color: #2a3055 !important; }
    @media print { .no-print { display: none !important; } }
    .badge-status { font-size: 0.78rem; padding: 0.35em 0.7em; }

    /* Circular progress */
    .quota-circle-wrapper { position: relative; display: inline-flex; align-items: center; justify-content: center; }
    .quota-circle-wrapper svg { transform: rotate(-90deg); }
    .quota-circle-label { position: absolute; font-size: 0.7rem; font-weight: 700; color: #2e7d32; }
    .quota-bar-mini { height: 6px; border-radius: 3px; background: #e8f5e9; overflow: hidden; }
    .quota-bar-mini-fill { height: 100%; border-radius: 3px; background: #2e7d32; transition: width 0.5s ease; }
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? Auth::id(); @endphp
@component('components.breadcrumb')
    @slot('li_1') Kehadiran & Cuti @endslot
    @slot('title') Kuota GTK @endslot
@endcomponent

{{-- Page Header Card --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="page-header-card rounded-3 border-0 shadow-sm">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="page-header-icon" style="background:#e8f5e9;color:#2e7d32;">
                        <i class="ri-pie-chart-line fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-0">Kuota Cuti GTK</h4>
                        <p class="text-muted mb-0 small">Pengaturan dan pemantauan sisa kuota cuti tahunan setiap GTK.</p>
                    </div>
                </div>
                <div class="d-flex gap-2 no-print">
                    <a href="{{ route('user.cuti.settings', $userId) }}" class="btn btn-outline-primary btn-sm">
                        <i class="ri-settings-line me-1"></i> Pengaturan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #2e7d32;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title rounded-3 fs-2" style="background:#e8f5e9;">
                            <i class="ri-team-line" style="color:#2e7d32;"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Total GTK</p>
                        <h3 class="fw-bold ff-secondary mb-0">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #198754;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded-3 fs-2">
                            <i class="ri-checkbox-circle-line text-success"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Kuota Tersisa</p>
                        <h3 class="fw-bold ff-secondary mb-0">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #f5a623;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-3 fs-2">
                            <i class="ri-add-circle-line text-warning"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Cuti Ditambahkan</p>
                        <h3 class="fw-bold ff-secondary mb-0">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #0d6efd;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded-3 fs-2">
                            <i class="ri-calendar-check-line text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Masa Berlaku</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ date('Y') }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Main Table Card --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-pie-chart-line text-primary me-1"></i> Daftar Kuota Cuti GTK</h5>
        <div class="d-flex gap-2 no-print">
            <select class="form-select form-select-sm" style="width:140px">
                <option value="">Semua Tahun</option>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th width="50">No</th>
                    <th>Nama GTK</th>
                    <th>Jenis GTK</th>
                    <th width="120">Kuota Tahunan</th>
                    <th width="140">Terpakai / Sisa</th>
                    <th width="160">Penggunaan</th>
                    <th width="100">Masa Berlaku</th>
                    <th width="100">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center gap-2">
                            <i class="ri-inbox-line text-muted" style="font-size:3rem;"></i>
                            <h5 class="fw-semibold text-dark mt-2 mb-1">Belum ada data</h5>
                            <p class="text-muted mb-0 small">Data kuota cuti GTK akan muncul di sini</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Circular Progress Helper (hidden, for reference) --}}
{{-- Usage example for quota cells:
@php
    $total = $quota->kuota_tahunan ?? 12;
    $terpakai = $quota->terpakai ?? 0;
    $sisa = max(0, $total - $terpakai);
    $pct = $total > 0 ? round(($terpakai / $total) * 100) : 0;
    $color = $pct >= 80 ? '#dc3545' : ($pct >= 50 ? '#f5a623' : '#2e7d32');
@endphp
<div class="quota-circle-wrapper">
    <svg width="44" height="44">
        <circle cx="22" cy="22" r="18" fill="none" stroke="#e8f5e9" stroke-width="4"/>
        <circle cx="22" cy="22" r="18" fill="none" stroke="{{ $color }}" stroke-width="4"
            stroke-dasharray="{{ round(2 * pi() * 18) }}"
            stroke-dashoffset="{{ round(2 * pi() * 18 * (1 - $pct / 100)) }}"/>
    </svg>
    <span class="quota-circle-label" style="color:{{ $color }}">{{ $pct }}%</span>
</div>
--}}
@endsection