@extends('layouts.master')
@section('title') Dashboard Admin Asrama @endsection

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
.progress-bar-thin { height: 6px; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') Dashboard Admin Asrama @endslot
@endcomponent

{{-- ROW 1: Overview Stats --}}
<div class="row g-3 mb-3">
    {{-- Total Santri --}}
    <div class="col-xl-2 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #0d6efd;">
            <div class="card-body py-3 text-center">
                <i class="ri-group-line text-primary fs-2"></i>
                <h2 class="fw-bold ff-secondary mb-0 mt-2">{{ number_format($totalSantri) }}</h2>
                <small class="text-muted">Total Santri</small>
            </div>
        </div>
    </div>

    {{-- Unit Asrama --}}
    <div class="col-xl-2 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #198754;">
            <div class="card-body py-3 text-center">
                <i class="ri-home-heart-line text-success fs-2"></i>
                <h2 class="fw-bold ff-secondary mb-0 mt-2">{{ number_format($totalDormitories) }}</h2>
                <small class="text-muted">Unit Asrama</small>
            </div>
        </div>
    </div>

    {{-- Kamar --}}
    <div class="col-xl-2 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #6f42c1;">
            <div class="card-body py-3 text-center">
                <i class="ri-door-open-line text-info fs-2"></i>
                <h2 class="fw-bold ff-secondary mb-0 mt-2">{{ number_format($totalRooms) }}</h2>
                <small class="text-muted">Total Kamar</small>
            </div>
        </div>
    </div>

    {{-- Occupied --}}
    <div class="col-xl-2 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #fd7e14;">
            <div class="card-body py-3 text-center">
                <i class="ri-user-follow-line text-warning fs-2"></i>
                <h2 class="fw-bold ff-secondary mb-0 mt-2">{{ number_format($occupiedRooms) }}</h2>
                <small class="text-muted">Kamar Terisi</small>
            </div>
        </div>
    </div>

    {{-- Izin Pending --}}
    <div class="col-xl-2 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #dc3545;">
            <div class="card-body py-3 text-center">
                <i class="ri-mail-unread-line text-danger fs-2"></i>
                <h2 class="fw-bold ff-secondary mb-0 mt-2">{{ number_format($permitPending) }}</h2>
                <small class="text-muted">Izin Pending</small>
            </div>
        </div>
    </div>

    {{-- Kunjungan Pending --}}
    <div class="col-xl-2 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #20c997;">
            <div class="card-body py-3 text-center">
                <i class="ri-visit-line text-success fs-2"></i>
                <h2 class="fw-bold ff-secondary mb-0 mt-2">{{ number_format($visitPending) }}</h2>
                <small class="text-muted">Kunjungan Pending</small>
            </div>
        </div>
    </div>
</div>

{{-- ROW 2: Occupancy Rate + Recent Permits --}}
<div class="row g-3 mb-3">
    {{-- Occupancy Rate --}}
    <div class="col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-pie-chart-line text-primary me-1"></i> Ocupasi Kamar</h5>
            </div>
            <div class="card-body text-center">
                @php
                    $occupancyRate = $totalRooms > 0 ? round($occupiedRooms / $totalRooms * 100) : 0;
                @endphp
                <div class="mb-3">
                    <span class="display-4 fw-bold">{{ $occupancyRate }}%</span>
                    <p class="text-muted small mb-0">{{ $occupiedRooms }} dari {{ $totalRooms }} kamar terisi</p>
                </div>
                <div class="progress progress-bar-thin">
                    <div class="progress-bar bg-primary" style="width: {{ $occupancyRate }}%"></div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <h5 class="fw-bold text-success mb-0">{{ $totalRooms - $occupiedRooms }}</h5>
                        <small class="text-muted">Kosong</small>
                    </div>
                    <div class="col-6">
                        <h5 class="fw-bold text-primary mb-0">{{ $totalSantri }}</h5>
                        <small class="text-muted">Santri Aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Izin Baru Hari Ini --}}
    <div class="col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ri-file-list-3-line text-info me-1"></i> Izin Terbaru</h5>
                <span class="badge bg-info rounded-pill">{{ $recentPermits->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($recentPermits->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Santri</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentPermits as $permit)
                                <tr>
                                    <td>{{ $permit->student->name ?? 'N/A' }}</td>
                                    <td><small>{{ $permit->created_at->format('d M Y') }}</small></td>
                                    <td>
                                        <span class="badge
                                            @if($permit->status === 'approved') bg-success
                                            @elseif($permit->status === 'pending') bg-warning
                                            @else bg-danger @endif">
                                            {{ ucfirst($permit->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center mb-0 py-4">Tidak ada izin tercatat</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-flashlight-line text-warning me-1"></i> Menu Cepat</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="#" class="btn quick-action-btn w-100 text-start py-2 px-2">
                        <i class="ri-home-heart-line text-primary me-1"></i> Data Asrama & Kamar
                    </a>
                    <a href="#" class="btn quick-action-btn w-100 text-start py-2 px-2">
                        <i class="ri-file-list-3-line text-success me-1"></i> Surat Izin
                    </a>
                    <a href="#" class="btn quick-action-btn w-100 text-start py-2 px-2">
                        <i class="ri-calendar-event-line text-info me-1"></i> Visit Log
                    </a>
                    <a href="#" class="btn quick-action-btn w-100 text-start py-2 px-2">
                        <i class="ri-alarm-warning-line text-danger me-1"></i> Pelanggaran
                    </a>
                    <a href="#" class="btn quick-action-btn w-100 text-start py-2 px-2">
                        <i class="ri-transfer-down-line text-warning me-1"></i> Pindah Kamar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
