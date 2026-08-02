@extends('layouts.master')
@section('title') Dashboard Asrama @endsection

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
    @slot('title') Dashboard Asrama @endslot
@endcomponent

{{-- ROW 1: Overview --}}
<div class="row g-3 mb-3">
    {{-- Total Santri --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #0d6efd;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-subtle">
                        <i class="ri-group-line text-primary fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Santri Aktif</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($totalSantri) }}</h2>
                        <small class="text-muted">Di Asrama</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Unit Asrama --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #198754;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success-subtle">
                        <i class="ri-home-gesture-line text-success fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Unit Asrama</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($totalDormitories) }}</h2>
                        <small class="text-muted">Aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pindah Kamar Pending --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #fd7e14;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-subtle">
                        <i class="ri-transfer-down-line text-warning fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Pindah Kamar</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($pendingRoomMoves) }}</h2>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pelanggaran Bulan Ini --}}
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100" style="border-left-color: #dc3545;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-danger-subtle">
                        <i class="ri-alarm-warning-line text-danger fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Pelanggaran</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($violationsThisMonth) }}</h2>
                        <small class="text-muted">Bulan Ini</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ROW 2: Recent Violations + Quick Actions --}}
<div class="row g-3 mb-3">
    {{-- Recent Violations --}}
    <div class="col-xl-8">
        <div class="card stat-card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ri-alarm-warning-line text-danger me-1"></i> Pelanggaran Terbaru</h5>
                <span class="badge bg-danger rounded-pill">{{ $recentViolations->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($recentViolations->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Santri</th>
                                    <th>Jenis</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentViolations as $v)
                                <tr>
                                    <td>{{ $v->student->name ?? 'N/A' }}</td>
                                    <td><span class="badge bg-danger">{{ $v->jenis_pelanggaran }}</span></td>
                                    <td><small>{{ $v->created_at->format('d M Y') }}</small></td>
                                    <td class="text-end">
                                        <a href="#" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center mb-0 py-4">Tidak ada pelanggaran tercatat</p>
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
                    <a href="{{ route('dormitory.index', ['userId' => $user->id]) }}" class="btn quick-action-btn w-100 text-start py-2 px-2">
                        <i class="ri-home-heart-line text-primary me-1"></i> Data Asrama
                    </a>
                    <a href="#" class="btn quick-action-btn w-100 text-start py-2 px-2">
                        <i class="ri-transfer-down-line text-info me-1"></i> Pindah Kamar
                    </a>
                    <a href="#" class="btn quick-action-btn w-100 text-start py-2 px-2">
                        <i class="ri-alarm-warning-line text-danger me-1"></i> Pelanggaran
                    </a>
                    <a href="#" class="btn quick-action-btn w-100 text-start py-2 px-2">
                        <i class="ri-calendar-event-line text-success me-1"></i> Kegiatan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
