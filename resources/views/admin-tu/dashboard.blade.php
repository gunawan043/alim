@extends('layouts.master')
@section('title') Dashboard Admin TU @endsection

@php
    $user = auth()->user();
@endphp

@section('css')
<style>
.stat-card { transition: all 0.3s ease; border-left: 4px solid transparent; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.quick-action-btn { transition: all 0.2s ease; border: 1px solid #e2e5e8; }
.quick-action-btn:hover { transform: translateY(-2px); border-color: #0d6efd; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') Dashboard Admin Tata Usaha @endslot
@endcomponent

{{-- ROW 1: Overview --}}
<div class="row g-3 mb-3">
    {{-- Schools --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #0d6efd;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-subtle">
                        <i class="ri-school-line text-primary fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Sekolah</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($schools) }}</h2>
                        <small class="text-muted">{{ number_format($activeSchools) }} aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Work Units --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #198754;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success-subtle">
                        <i class="ri-government-line text-success fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Satuan Kerja</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($workUnits) }}</h2>
                        <small class="text-muted">Unit Aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- GTK --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #fd7e14;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-subtle">
                        <i class="ri-group-line text-warning fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">GTK</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($totalGtk) }}</h2>
                        <small class="text-muted">Terdaftar</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #dc3545;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-danger-subtle">
                        <i class="ri-mail-unread-line text-danger fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Menunggu</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($pendingRequests) }}</h2>
                        <small class="text-muted">Permintaan</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ROW 2: Quick Actions + Info --}}
<div class="row g-3 mb-3">
    {{-- Menu Cepat --}}
    <div class="col-xl-8">
        <div class="card stat-card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-flashlight-line text-warning me-1"></i> Menu Cepat</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4">
                        <a href="{{ route('user.admin-tu.gtk.index', ['userId' => $user->id]) }}" class="btn quick-action-btn w-100 text-start py-3 px-2">
                            <i class="ri-group-line text-primary me-1"></i> Kelola GTK
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('user.work-units.index', ['userId' => $user->id]) }}" class="btn quick-action-btn w-100 text-start py-3 px-2">
                            <i class="ri-government-line text-success me-1"></i> Satuan Kerja
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('user.profiles.school', ['userId' => $user->id]) }}" class="btn quick-action-btn w-100 text-start py-3 px-2">
                            <i class="ri-school-line text-info me-1"></i> Profil Sekolah
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('user.kaldik.index', ['userId' => $user->id]) }}" class="btn quick-action-btn w-100 text-start py-2 px-2">
                            <i class="ri-calendar-event-line text-secondary me-1"></i> Kaldik
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('user.dokumen-iso.index', ['userId' => $user->id]) }}" class="btn quick-action-btn w-100 text-start py-2 px-2">
                            <i class="ri-file-text-line text-muted me-1"></i> Dokumen ISO
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Info --}}
    <div class="col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-information-line text-info me-1"></i> Informasi</h5>
            </div>
            <div class="card-body">
                <p class="mb-2 text-muted small">Dashboard Admin TU memberikan overview lengkap tentang data sekolah, satuan kerja, dan GTK.</p>
                <ul class="list-unstyled mb-0">
                    <li><i class="ri-check-line text-success me-1"></i> Data {{ number_format($schools) }} sekolah terdaftar</li>
                    <li><i class="ri-check-line text-success me-1"></i> {{ number_format($workUnits) }} satuan kerja aktif</li>
                    <li><i class="ri-check-line text-success me-1"></i> {{ number_format($totalGtk) }} GTK terkelola</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- ROW 3: Stats Quick --}}
<div class="row g-3">
    <div class="col-xl-12">
        <div class="card stat-card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-bar-chart-line text-primary me-1"></i> Ringkasan Statistik</h5>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-md-3">
                        <div class="p-3 bg-primary bg-opacity-10 rounded">
                            <h3 class="fw-bold text-primary">{{ $quickStats['completedTasks'] }}</h3>
                            <small class="text-muted">Tugas Selesai</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-info bg-opacity-10 rounded">
                            <h3 class="fw-bold text-info">{{ $quickStats['pendingDocuments'] }}</h3>
                            <small class="text-muted">Dokumen Pending</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-warning bg-opacity-10 rounded">
                            <h3 class="fw-bold text-warning">{{ $quickStats['archivedRecords'] }}</h3>
                            <small class="text-muted">Arsip Record</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-success bg-opacity-10 rounded">
                            <h3 class="fw-bold text-success">{{ $schools + $workUnits + $totalGtk }}</h3>
                            <small class="text-muted">Total Item</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
