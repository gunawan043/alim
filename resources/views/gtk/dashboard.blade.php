@extends('layouts.master')
@section('title') Dashboard GTK @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') Dashboard GTK @endslot
@endcomponent

{{-- Welcome Banner --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h4 class="text-white mb-1">Selamat Datang, {{ $user->name ?? 'GTK' }}</h4>
                <p class="mb-0">Dashboard GTK - {{ date('d F Y') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Statistics Cards --}}
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary-subtle rounded p-2">
                        <i class="ri-group-line text-primary fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small">Total GTK</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($totalGtk) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success-subtle rounded p-2">
                        <i class="ri-shield-user-line text-success fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small">Guru</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($guruCount) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning-subtle rounded p-2">
                        <i class="ri-admin-line text-warning fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small">Tendik</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($tendikCount) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-info-subtle rounded p-2">
                        <i class="ri-add-line text-info fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small">Baru Bulan Ini</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($newThisMonth) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Gender Distribution --}}
<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Distribusi Jenis Kelamin GTK</h5>
            </div>
            <div class="card-body">
                @php
                    $totalGender = $lakiCount + $perempuanCount;
                    $malePercent = $totalGender > 0 ? round($lakiCount / $totalGender * 100) : 0;
                    $femalePercent = $totalGender > 0 ? round($perempuanCount / $totalGender * 100) : 0;
                @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Laki-laki</span>
                        <strong>{{ $lakiCount }} ({{ $malePercent }}%)</strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" style="width: {{ $malePercent }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Perempuan</span>
                        <strong>{{ $perempuanCount }} ({{ $femalePercent }}%)</strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-danger" style="width: {{ $femalePercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Menu Cepat</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="#" class="btn btn-outline-primary w-100">
                            <i class="ri-shield-user-line d-block fs-4 mb-1"></i>
                            Data Guru
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="#" class="btn btn-outline-warning w-100">
                            <i class="ri-admin-line d-block fs-4 mb-1"></i>
                            Data Tendik
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="#" class="btn btn-outline-info w-100">
                            <i class="ri-user-line d-block fs-4 mb-1"></i>
                            Profil Saya
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="#" class="btn btn-outline-success w-100">
                            <i class="ri-school-line d-block fs-4 mb-1"></i>
                            Pembelajaran
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Info Box --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-4">
                <h5 class="mb-2">Dashboard GTK Sederhana</h5>
                <p class="text-muted mb-0">Dashboard ini menampilkan ringkasan data GTK secara umum.</p>
            </div>
        </div>
    </div>
</div>
@endsection