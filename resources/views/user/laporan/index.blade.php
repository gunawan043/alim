@extends('layouts.master')

@section('title') Laporan Pondok @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Dashboard @endslot
        @slot('title') Laporan Pondok @endslot
    @endcomponent

    <div class="row g-4 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate overflow-hidden">
                <div class="card-body" style="background: linear-gradient(135deg, #563d9c, #7d3fd6);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-white-50 text-uppercase fw-semibold mb-1">Santri Aktif</p>
                            <h3 class="text-white mb-0 fw-bold">{{ number_format($stats['total_siswa'] ?? 0) }}</h3>
                        </div>
                        <div class="avatar-lg rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="ri-users-line fs-2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate overflow-hidden">
                <div class="card-body" style="background: linear-gradient(135deg, #17a2b8, #20c997);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-white-50 text-uppercase fw-semibold mb-1">GTK Aktif</p>
                            <h3 class="text-white mb-0 fw-bold">{{ number_format($stats['total_gtk'] ?? 0) }}</h3>
                        </div>
                        <div class="avatar-lg rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="ri-contacts-book-2-line fs-2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate overflow-hidden">
                <div class="card-body" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-white-50 text-uppercase fw-semibold mb-1">Alumni</p>
                            <h3 class="text-white mb-0 fw-bold">{{ number_format($stats['total_alumni'] ?? 0) }}</h3>
                        </div>
                        <div class="avatar-lg rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="ri-graduation-cap-line fs-2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate overflow-hidden">
                <div class="card-body" style="background: linear-gradient(135deg, #fd7e14, #ffc107);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-white-50 text-uppercase fw-semibold mb-1">Alumni Bekerja</p>
                            <h3 class="text-white mb-0 fw-bold">{{ number_format($stats['alumni_aktif'] ?? 0) }}</h3>
                        </div>
                        <div class="avatar-lg rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="ri-briefcase-line fs-2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate overflow-hidden">
                <div class="card-body" style="background: linear-gradient(135deg, #405189, #6f42c1);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-white-50 text-uppercase fw-semibold mb-1">Rombel Aktif</p>
                            <h3 class="text-white mb-0 fw-bold">{{ number_format($stats['total_rombel'] ?? 0) }}</h3>
                        </div>
                        <div class="avatar-lg rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="ri-building-4-line fs-2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate overflow-hidden">
                <div class="card-body" style="background: linear-gradient(135deg, #0d6efd, #3dd5f3);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-white-50 text-uppercase fw-semibold mb-1">Presensi GTK Hari Ini</p>
                            <h3 class="text-white mb-0 fw-bold">{{ number_format($stats['presensi_hari_ini'] ?? 0) }}</h3>
                        </div>
                        <div class="avatar-lg rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="ri-calendar-check-line fs-2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}" class="card card-block-link h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-line-chart-line fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Analisis Akademik</h5>
                            <p class="text-muted mb-0 small">Nilai murni STS/SAS</p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Perbandingan STS/SAS, distribusi, tren, temuan otomatis, dan analisis per kelas.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.akademik.ketuntasan', ['userId' => $userId]) }}" class="card card-block-link h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-checkbox-circle-line fs-4 text-success"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Nilai &amp; Ketuntasan</h5>
                            <p class="text-muted mb-0 small">Rekap akademik</p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Kelengkapan nilai, ketuntasan per kelas/mapel, dan perbandingan periode.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.akademik.kehadiran', ['userId' => $userId]) }}" class="card card-block-link h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-calendar-check-line fs-4 text-info"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Kehadiran Santri</h5>
                            <p class="text-muted mb-0 small">Presensi harian/mapel</p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Persentase kehadiran, tren, santri berisiko, dan hubungan dengan nilai.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.akademik.keterlaksanaan', ['userId' => $userId]) }}" class="card card-block-link h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-book-open-line fs-4 text-warning"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Keterlaksanaan</h5>
                            <p class="text-muted mb-0 small">Rencana vs realisasi</p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Perangkat, jurnal, presensi mapel, jadwal, dan status pelaksanaan per guru.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.akademik.administrasi', ['userId' => $userId]) }}" class="card card-block-link h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-folder-check-line fs-4 text-danger"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Administrasi Akademik</h5>
                            <p class="text-muted mb-0 small">Checklist kewajiban</p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Kelengkapan perangkat, jurnal, presensi, input nilai, dan tindak lanjut.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.populasi.mutasi', ['userId' => $userId]) }}" class="card card-block-link h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-swap-box-line fs-4 text-info"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Mutasi &amp; Populasi</h5>
                            <p class="text-muted mb-0 small">Perubahan jumlah santri</p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Mutasi, pertumbuhan, profil sebaran, keberlanjutan, kenaikan kelas, dan kelulusan.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.presensi', ['userId' => $userId]) }}" class="card card-block-link h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-calendar-check-line fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Laporan Presensi</h5>
                            <p class="text-muted mb-0 small">Kehadiran GTK</p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Rekap hadir, terlambat, izin, sakit, dan alpa per periode.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.santri', ['userId' => $userId]) }}" class="card card-block-link h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-user-star-line fs-4 text-success"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Laporan Santri</h5>
                            <p class="text-muted mb-0 small">Data & status santri</p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Daftar santri per rombel, status, dan jenis kelamin — dapat diekspor.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.gtk', ['userId' => $userId]) }}" class="card card-block-link h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-contacts-book-2-line fs-4 text-info"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Laporan GTK</h5>
                            <p class="text-muted mb-0 small">Data kepegawaian</p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Nama, jabatan, jenis GTK, dan status kepegawaian — dapat diekspor.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.asrama', ['userId' => $userId]) }}" class="card card-block-link h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-home-heart-line fs-4 text-warning"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Laporan Asrama</h5>
                            <p class="text-muted mb-0 small">Penghuni & kapasitas</p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Jumlah penghuni aktif, kapasitas, dan kamar per asrama.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.laporan.keuangan', ['userId' => $userId]) }}" class="card card-block-link h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i class="ri-wallet-3-line fs-4 text-danger"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Laporan Keuangan</h5>
                            <p class="text-muted mb-0 small">Status modul</p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Informasi ketersediaan modul keuangan pondok.</p>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-user-add-line me-2 text-primary"></i>Santri Terbaru</h5>
                    <a href="{{ route('user.laporan.santri', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Lihat semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <tbody>
                                @forelse($recentStudents as $student)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-medium">{{ $student->name }}</div>
                                            <div class="small text-muted">{{ $student->nisn ?: $student->nis ?: '—' }}</div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $student->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($student->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center text-muted py-4">Belum ada data santri.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-contacts-line me-2 text-info"></i>GTK Terbaru</h5>
                    <a href="{{ route('user.laporan.gtk', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Lihat semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <tbody>
                                @forelse($recentGtk as $person)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-medium">{{ $person->name }}</div>
                                            <div class="small text-muted">{{ $person->employment?->jabatan ?: $person->email }}</div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-{{ $person->is_active ? 'success' : 'secondary' }}-subtle text-{{ $person->is_active ? 'success' : 'secondary' }}">
                                                {{ $person->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center text-muted py-4">Belum ada data GTK.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
