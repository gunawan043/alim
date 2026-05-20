@extends('layouts.master')
@section('title') Dashboard Personalia @endsection
@php
    $currentUser = auth()->user();
    $userId = $currentUser->id;
@endphp

@section('css')
<style>
.card-animate { transition: all 0.3s ease; }
.card-animate:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.quick-action-btn { transition: all 0.2s ease; border: 1px solid #e2e5e8; }
.quick-action-btn:hover { transform: translateY(-2px); border-color: #0d6efd; }
.progress-bar-thin { height: 6px; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') Dashboard Personalia @endslot
@endcomponent

{{-- ROW 1: Overview Stats GTK --}}
<div class="row g-3 mb-3">
    {{-- Total GTK --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-subtle">
                        <i class="ri-group-line text-primary fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Total GTK</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h2>
                        <small class="text-muted">Aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Guru --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success-subtle">
                        <i class="ri-shield-user-line text-success fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Guru</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($stats['guru']) }}</h2>
                        <small class="text-muted">Aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tendik --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-subtle">
                        <i class="ri-admin-line text-warning fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Tendik</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($stats['tendik']) }}</h2>
                        <small class="text-muted">Aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Akan Pensiun --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-danger-subtle">
                        <i class="ri-alarm-warning-line text-danger fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Akan Pensiun</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ $approachingBup->count() }}</h2>
                        <small class="text-muted">dalam 6 bulan</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ROW 2: GTK by Gender + Quick Actions --}}
<div class="row g-3 mb-3">
    {{-- GTK by Gender --}}
    <div class="col-xl-4 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-pie-chart-line text-primary me-1"></i> GTK berdasarkan Jenis Kelamin</h5>
            </div>
            <div class="card-body">
                @php
                    $total = $gtkByGender['total'] > 0 ? $gtkByGender['total'] : 1;
                    $malePercent = round($gtkByGender['male'] / $total * 100);
                    $femalePercent = round($gtkByGender['female'] / $total * 100);
                @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="ri-men-line text-primary me-1"></i> Laki-laki</span>
                        <strong>{{ $gtkByGender['male'] }} ({{ $malePercent }}%)</strong>
                    </div>
                    <div class="progress progress-bar-thin">
                        <div class="progress-bar bg-primary" style="width: {{ $malePercent }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="ri-women-line text-danger me-1"></i> Perempuan</span>
                        <strong>{{ $gtkByGender['female'] }} ({{ $femalePercent }}%)</strong>
                    </div>
                    <div class="progress progress-bar-thin">
                        <div class="progress-bar bg-danger" style="width: {{ $femalePercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- GTK by Work Unit --}}
    <div class="col-xl-8 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-government-line text-primary me-1"></i> GTK berdasarkan Satuan Kerja</h5>
            </div>
            <div class="card-body">
                @if($gtkByWorkUnit->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Satuan Kerja</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($gtkByWorkUnit as $unit)
                                <tr>
                                    <td>{{ $unit->name }}</td>
                                    <td class="text-center"><span class="badge bg-primary rounded-pill">{{ $unit->total }}</span></td>
                                    <td class="text-center">{{ $stats['total'] > 0 ? round($unit->total / $stats['total'] * 100) : 0 }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center mb-0 py-3">Belum ada data GTK</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ROW 3: GTK Akan Pensiun + GTK Baru + Quick Actions --}}
<div class="row g-3 mb-3">
    {{-- GTK Akan Pensiun --}}
    <div class="col-xl-6 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ri-alarm-warning-fill text-danger me-1"></i>
                    GTK Akan Pensiun
                    <span class="badge bg-danger ms-1">{{ $approachingBup->count() }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                @if($approachingBup->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>Tanggal Lahir</th>
                                    <th class="text-center">Estimasi Pensiun</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($approachingBup as $gtk)
                                <tr>
                                    <td>
                                        <strong>{{ $gtk->name }}</strong>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($gtk->tanggal_lahir)->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        @php
                                            $bupAge = (int) \App\Models\PensionSetting::get('bup_age', '58');
                                            $bupDate = \Carbon\Carbon::parse($gtk->tanggal_lahir)->addYears($bupAge)->format('d/m/Y');
                                        @endphp
                                        <span class="badge bg-warning text-dark">{{ $bupDate }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('user.pension.index', ['userId' => $userId]) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center mb-0 py-4">
                        <i class="ri-checkbox-circle-line fs-1 d-block mb-2"></i>
                        Tidak ada GTK yang akan pensiun
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- GTK Terbaru + Quick Actions --}}
    <div class="col-xl-6 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ri-user-add-line text-success me-1"></i>
                    GTK Terbaru
                </h5>
            </div>
            <div class="card-body p-0">
                @if($recentGtk->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>Jenis</th>
                                    <th>Tanggal Daftar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentGtk as $gtk)
                                <tr>
                                    <td>
                                        <strong>{{ $gtk->name }}</strong>
                                        <div class="small text-muted">{{ $gtk->email }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $gtk->employment?->jenis_gtk == 'Guru' ? 'primary' : 'info' }}">
                                            {{ $gtk->employment?->jenis_gtk ?? '-' }}
                                        </span>
                                    </td>
                                    <td>{{ $gtk->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center mb-0 py-4">Belum ada GTK baru</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ROW 4: Quick Actions --}}
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-links-line text-primary me-1"></i> Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-xl-2 col-md-4 col-6">
                        <a href="{{ route('user.gtk.index', ['userId' => $userId]) }}"
                           class="quick-action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="ri-group-line fs-2 text-primary mb-2"></i>
                            <span class="text-dark fw-medium">Data GTK</span>
                            <small class="text-muted">{{ $stats['total'] }} GTK</small>
                        </a>
                    </div>
                    <div class="col-xl-2 col-md-4 col-6">
                        <a href="{{ route('user.gtk-requests.index', ['userId' => $userId]) }}"
                           class="quick-action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="ri-file-add-line fs-2 text-success mb-2"></i>
                            <span class="text-dark fw-medium">Pengajuan GTK</span>
                            <small class="text-muted">Ajukan baru</small>
                        </a>
                    </div>
                    <div class="col-xl-2 col-md-4 col-6">
                        <a href="{{ route('user.absensi-gtk.index', ['userId' => $userId]) }}"
                           class="quick-action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="ri-time-line fs-2 text-info mb-2"></i>
                            <span class="text-dark fw-medium">Absensi GTK</span>
                            <small class="text-muted">Rekap & harian</small>
                        </a>
                    </div>
                    <div class="col-xl-2 col-md-4 col-6">
                        <a href="{{ route('user.cuti.index', ['userId' => $userId]) }}"
                           class="quick-action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="ri-calendar-check-line fs-2 text-warning mb-2"></i>
                            <span class="text-dark fw-medium">Cuti & Izin</span>
                            <small class="text-muted">Kelola cuti</small>
                        </a>
                    </div>
                    <div class="col-xl-2 col-md-4 col-6">
                        <a href="{{ route('user.payroll.index', ['userId' => $userId]) }}"
                           class="quick-action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="ri-wallet-line fs-2 text-secondary mb-2"></i>
                            <span class="text-dark fw-medium">Payroll</span>
                            <small class="text-muted">Gaji & tunjangan</small>
                        </a>
                    </div>
                    <div class="col-xl-2 col-md-4 col-6">
                        <a href="{{ route('user.pelatihan.index', ['userId' => $userId]) }}"
                           class="quick-action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="ri-graduation-cap-line fs-2 text-danger mb-2"></i>
                            <span class="text-dark fw-medium">Pelatihan</span>
                            <small class="text-muted">Diklat & seminar</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection