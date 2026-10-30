@extends('layouts.master')

@section('title') Dashboard GTK @endsection

@section('content')
@php $dashboardUserId = $user->id ?? auth()->id(); @endphp
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Dashboard GTK</h2>
            <p class="text-muted mb-0">Akses cepat ke pekerjaan harian Anda.</p>
        </div>
        <span class="badge bg-primary">Selamat Datang, {{ $user->name ?? 'Pengguna' }}</span>
    </div>

    <!-- Akses Harian -->
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0"><i class="ri-flashlight-line text-primary me-1"></i>Akses Harian</h5>
            <span class="badge bg-light text-muted ms-auto">Paling sering dipakai</span>
        </div>
        <div class="card-body">
            <div class="row g-2">
                @php
                    $quickLinks = [
                        ['user.teacher-qr.scan', 'Scan QR Kehadiran', 'ri-qr-scan-2-line', 'primary'],
                        ['user.schools.guru-mapel.index', 'Buku Nilai Saya', 'ri-book-3-line', 'success'],
                        ['user.jadwal-kbm.for-teacher', 'Jadwal Mengajar', 'ri-time-line', 'warning'],
                        ['user.kurikulum.realisasi.index', 'Jurnal Pembelajaran', 'ri-article-line', 'info'],
                        ['user.bank-soal.index', 'Bank Soal', 'ri-file-edit-line', 'purple'],
                        ['user.notifications.page', 'Notifikasi', 'ri-notification-3-line', 'secondary'],
                    ];
                @endphp
                @foreach($quickLinks as [$routeName, $label, $icon, $color])
                    @if(\Illuminate\Support\Facades\Route::has($routeName))
                        <div class="col-md-4 col-sm-6">
                            <a href="{{ route($routeName, array_merge(['userId' => $dashboardUserId], $routeName === 'user.jadwal-kbm.for-teacher' ? ['teacherId' => $dashboardUserId] : [])) }}"
                               class="btn btn-soft-{{ $color }} d-flex align-items-center gap-2 w-100 py-3 text-start">
                                <i class="{{ $icon }} fs-4"></i>
                                <span class="fw-medium">{{ $label }}</span>
                                <i class="ri-arrow-right-s-line ms-auto"></i>
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Main Content - Simple Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total GTK</h5>
                    <p class="card-text display-6">15</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Guru</h5>
                    <p class="card-text display-6">10</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tendik</h5>
                    <p class="card-text display-6">5</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer note -->
    <div class="text-muted small mt-4">
        Ringkasan GTK — gunakan akses harian di atas untuk pekerjaan rutin.
    </div>
</div>
@endsection