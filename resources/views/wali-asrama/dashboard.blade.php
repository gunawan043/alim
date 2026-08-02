@extends('layouts.master')
@section('title') Dashboard Wali Asrama @endsection

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
    @slot('title') Dashboard Wali Asrama @endslot
@endcomponent

{{-- ROW 1: Overview --}}
<div class="row g-3 mb-3">
    {{-- Total Santri --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #0d6efd;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-subtle">
                        <i class="ri-home-heart-line text-primary fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Asrama</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($totalDormitories) }}</h2>
                        <small class="text-muted">Unit Aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Permohonan Santri --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #fd7e14;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-subtle">
                        <i class="ri-mail-unread-line text-warning fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Izin Pending</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($pendingPermits) }}</h2>
                        <small class="text-muted">Menunggu Persetujuan</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kunjungan --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #6f42c1;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-info-subtle">
                        <i class="ri-door-open-line text-info fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Kunjungan</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($pendingVisits) }}</h2>
                        <small class="text-muted">Menunggu Approval</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Approved --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #198754;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success-subtle">
                        <i class="ri-checkbox-circle-line text-success fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Izin Approved</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($approvedPermits) }}</h2>
                        <small class="text-muted">Disetujui</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ROW 2: Menu Cepat --}}
<div class="row g-3 mb-3">
    <div class="col-xl-12">
        <div class="card stat-card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-flashlight-line text-warning me-1"></i> Menu Wali Asrama</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <a href="{{ route('wali.dashboard', ['userId' => $user->id]) }}" class="btn quick-action-btn w-100 text-start py-3 px-2">
                            <i class="ri-home-gesture-line text-primary me-1"></i> Portal Wali
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn quick-action-btn w-100 text-start py-3 px-2">
                            <i class="ri-file-list-line text-success me-1"></i> Lihat Izin Santri
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn quick-action-btn w-100 text-start py-3 px-2">
                            <i class="ri-visit-line text-info me-1"></i> Jadwal Kunjungan
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn quick-action-btn w-100 text-start py-3 px-2">
                            <i class="ri-notification-3-line text-danger me-1"></i> Notifikasi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ROW 3: Info Panel --}}
<div class="row g-3">
    <div class="col-xl-12">
        <div class="card stat-card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-information-line text-info me-1"></i> Tentang Dashboard Wali Asrama</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">Dashboard ini memberikan ringkasan aktivitas asrama termasuk izin pulang-pulang santri, kunjungan wali, dan status approval yang menunggu persetujuan dari tim asrama.</p>
            </div>
        </div>
    </div>
</div>
@endsection
