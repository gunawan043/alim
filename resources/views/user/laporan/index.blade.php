@extends('layouts.master')

@section('title') Laporan Pondok @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Dashboard @endslot
        @slot('title') Laporan Pondok @endslot
    @endcomponent

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate overflow-hidden">
                <div class="card-body" style="background: linear-gradient(135deg, #563d9c, #7d3fd6);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-white-50 text-uppercase fw-semibold mb-1">Total Santri</p>
                            <h3 class="text-white mb-0 fw-bold">{{ $stats['total_siswa'] ?? 0 }}</h3>
                        </div>
                        <div class="avatar-lg rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="ri-users-line fs-2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate overflow-hidden">
                <div class="card-body" style="background: linear-gradient(135deg, #17a2b8, #20c997);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-white-50 text-uppercase fw-semibold mb-1">GTK Aktif</p>
                            <h3 class="text-white mb-0 fw-bold">{{ $stats['total_gtk'] ?? 0 }}</h3>
                        </div>
                        <div class="avatar-lg rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="ri-contacts-book-2-line fs-2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate overflow-hidden">
                <div class="card-body" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-white-50 text-uppercase fw-semibold mb-1">Alumni</p>
                            <h3 class="text-white mb-0 fw-bold">{{ $stats['total_alumni'] ?? 0 }}</h3>
                        </div>
                        <div class="avatar-lg rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="ri-graduation-cap-line fs-2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate overflow-hidden">
                <div class="card-body" style="background: linear-gradient(135deg, #fd7e14, #ffc107);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-white-50 text-uppercase fw-semibold mb-1">Alumni Bekerja</p>
                            <h3 class="text-white mb-0 fw-bold">{{ $stats['alumni_aktif'] ?? 0 }}</h3>
                        </div>
                        <div class="avatar-lg rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="ri-briefcase-line fs-2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.presensi', ['userId' => $userId]) }}" class="card card-block-link">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-calendar-check-line fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Laporan Presensi</h5>
                            <p class="text-muted mb-0 small">Absensi GTK & Santri</p>
                        </div>
                    </div>
                    <p class="text-muted small">Lihat rekap presensi harian, mingguan, dan bulanan GTK serta santri.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.santri', ['userId' => $userId]) }}" class="card card-block-link">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-user-follow-line fs-4 text-success"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Laporan Santri</h5>
                            <p class="text-muted mb-0 small">Mutasi & Prestasi</p>
                        </div>
                    </div>
                    <p class="text-muted small">Mutasi masuk/keluar, prestasi akademik, dan hafalan santri.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.gtk', ['userId' => $userId]) }}" class="card card-block-link">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-contacts-book-2-line fs-4 text-info"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Laporan GTK</h5>
                            <p class="text-muted mb-0 small">GTK & Tugas Tambahan</p>
                        </div>
                    </div>
                    <p class="text-muted small">Data GTK, jadwal mengajar, dan tugas tambahan.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.keuangan', ['userId' => $userId]) }}" class="card card-block-link">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-money-dollar-circle-line fs-4 text-warning"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Laporan Keuangan</h5>
                            <p class="text-muted mb-0 small">Pemasukan & Pengeluaran</p>
                        </div>
                    </div>
                    <p class="text-muted small">Ringkasan keuangan pondok pesantren.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.asrama', ['userId' => $userId]) }}" class="card card-block-link">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-hotel-line fs-4 text-danger"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Laporan Asrama</h5>
                            <p class="text-muted mb-0 small">Kehadiran & Kebersihan</p>
                        </div>
                    </div>
                    <p class="text-muted small">Laporan kebersihan, inventaris, dan penghuni asrama.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.kalkulasi-nilai.index', ['userId' => $userId]) }}" class="card card-block-link">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-purple bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-calculator-line fs-4 text-purple"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Kalkulasi Nilai</h5>
                            <p class="text-muted mb-0 small">Sts & Sas</p>
                        </div>
                    </div>
                    <p class="text-muted small">Kalkulasi nilai STS dan SAS per kelas.</p>
                </div>
            </a>
        </div>
    </div>
@endsection
