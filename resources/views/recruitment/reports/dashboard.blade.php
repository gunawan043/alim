@extends('layouts.master')
@section('title') Dashboard Rekrutmen @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endSection
@section('content')
@php
$stats = $stats ?? [];
$demographics = $stats['applicant_demographics'] ?? ['gender'=>['L'=>0,'P'=>0],'age_groups'=>['< 25'=>0,'25-30'=>0,'31-35'=>0,'36-40'=>0,'> 40'=>0]];
$ttd = $stats['time_to_hire'] ?? ['average_days'=>0,'median_days'=>0,'min_days'=>0,'max_days'=>0,'distribution'=>['< 7'=>0,'7-14'=>0,'15-30'=>0,'31-60'=>0,'> 60'=>0]];
@endphp
@component('components.breadcrumb')
    @slot('li_1') Rekrutmen @endslot
    @slot('li_2') Laporan @endslot
    @slot('title') Dashboard Rekrutmen @endslot
@endComponent

{{-- ── Top Stats Row ── --}}
<div class="row g-3 mb-3">
<div class="col-md-3 col-sm-6">
        <div class="card border-start border-primary border-0 shadow-sm h-100">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0"><span class="avatar-title bg-primary-subtle text-primary rounded-2 fs-5"><i class="ri-file-list-3-line"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0" style="font-size:0.7rem">Total Pelamar</p>
                        <h4 class="mb-0">{{ $stats['total_applications'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-start border-success border-0 shadow-sm h-100">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0"><span class="avatar-title bg-success-subtle text-success rounded-2 fs-5"><i class="ri-checkbox-circle-line"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0" style="font-size:0.7rem">Diterima</p>
                        <h4 class="mb-0">{{ $stats['hired_count'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-start border-warning border-0 shadow-sm h-100">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0"><span class="avatar-title bg-warning-subtle text-warning rounded-2 fs-5"><i class="ri-briefcase-line"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0" style="font-size:0.7rem">Lowongan Aktif</p>
                        <h4 class="mb-0">{{ $stats['active_jobs'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-start border-info border-0 shadow-sm h-100">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0"><span class="avatar-title bg-info-subtle text-info rounded-2 fs-5"><i class="ri-time-line"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0" style="font-size:0.7rem">Rata-rata Waktu Hire</p>
                        <h4 class="mb-0">{{ $ttd['average_days'] ?? 0 }} <small class="text-muted" style="font-size:0.65rem">hari</small></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Charts Row ── --}}
<div class="row g-3 mb-3">
    {{-- Hiring Funnel --}}
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-0"><i class="ri-bar-chart-line me-1 text-primary"></i> Hiring Funnel</h6>
                    <a href="{{ route('user.ats.reports.hiring-funnel', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                </div>
            </div>
            <div class="card-body p-2">
                @php
                $funnel = $stats['hiring_funnel'] ?? [];
                $maxCount = collect($funnel)->max('count') ?: 1;
                @endphp
                <div class="d-flex flex-column gap-2 px-2">
                    @foreach($funnel as $key => $item)
                    <div>
                        <div class="d-flex justify-content-between mb-1" style="font-size:0.75rem">
                            <span class="fw-medium">{{ $item['label'] }}</span>
                            <span class="badge bg-{{ match($key){0=>'secondary',1=>'primary',2=>'success',3=>'info',4=>'teal'} }}">{{ $item['count'] }}</span>
                        </div>
                        <div class="progress" style="height:6px">
                            <div class="progress-bar bg-{{ match($key){0=>'secondary',1=>'primary',2=>'success',3=>'info',4=>'teal'} }}" role="progressbar" style="width:{{ $maxCount > 0 ? ($item['count'] / $maxCount * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Demografi --}}
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="mb-0"><i class="ri-pie-chart-2-line me-1 text-primary"></i> Demografi Pelamar</h6>
            </div>
            <div class="card-body p-2">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="text-center p-2 rounded bg-primary-subtle">
                            <div class="fw-bold fs-4 text-primary">{{ $demographics['gender']['L'] }}</div>
                            <div class="text-muted" style="font-size:0.7rem">Laki-laki</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-2 rounded bg-pink-subtle">
                            <div class="fw-bold fs-4" style="color:#e83e8c">{{ $demographics['gender']['P'] }}</div>
                            <div class="text-muted" style="font-size:0.7rem">Perempuan</div>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <p class="text-muted mb-1" style="font-size:0.7rem">Distribusi Usia</p>
                    @foreach(['< 25'=>'kurang_25','25-30'=>'25_30','31-35'=>'31_35','36-40'=>'36_40','> 40'=>'lebih_40'] as $label => $key)
                    <div class="d-flex justify-content-between" style="font-size:0.72rem">
                        <span class="text-muted">{{ $label }}</span>
                        <span class="fw-medium">{{ $demographics['age_groups'][$key] ?? 0 }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Lowongan & Distribusi Waktu ── --}}
<div class="row g-3">
    {{-- Top Lowongan --}}
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-0"><i class="ri-briefcase-3-line me-1 text-primary"></i> Performa Lowongan</h6>
                    <a href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-primary">Semua</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0" style="font-size:0.8rem">
                        <thead class="table-light" style="font-size:0.72rem">
                            <tr>
                                <th>Kode</th>
                                <th>Judul</th>
                                <th class="text-center">Kuota</th>
                                <th class="text-center">Pelamar</th>
                                <th class="text-center">Konversi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['job_performance'] ?? [] as $job)
                            <tr>
                                <td><span class="badge bg-dark-subtle text-dark">{{ $job['kode'] }}</span></td>
                                <td class="fw-medium">{{ Str::limit($job['judul'], 25) }}</td>
                                <td class="text-center">{{ $job['terisi'] }}/{{ $job['kuota'] }}</td>
                                <td class="text-center">{{ $job['total_pelamar'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $job['konversi'] >= 100 ? 'success' : ($job['konversi'] >= 50 ? 'warning' : 'secondary') }}">
                                        {{ $job['konversi'] }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Distribusi Waktu Hire --}}
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="mb-0"><i class="ri-timer-line me-1 text-primary"></i> Time to Hire</h6>
            </div>
            <div class="card-body p-2">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-2 rounded bg-light">
                            <div class="fw-bold text-primary" style="font-size:1.1rem">{{ $ttd['min_days'] }}</div>
                            <div class="text-muted" style="font-size:0.65rem">Min (hari)</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded bg-light">
                            <div class="fw-bold text-success" style="font-size:1.1rem">{{ $ttd['average_days'] }}</div>
                            <div class="text-muted" style="font-size:0.65rem">Rata-rata</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded bg-light">
                            <div class="fw-bold text-danger" style="font-size:1.1rem">{{ $ttd['max_days'] }}</div>
                            <div class="text-muted" style="font-size:0.65rem">Max (hari)</div>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <p class="text-muted mb-1" style="font-size:0.7rem">Distribusi</p>
                    @foreach(['< 7'=>'7 hari','7-14'=>'7-14 hari','15-30'=>'15-30 hari','31-60'=>'31-60 hari','> 60'=>'> 60 hari'] as $key => $label)
                    <div class="d-flex justify-content-between" style="font-size:0.72rem">
                        <span class="text-muted">{{ $label }}</span>
                        <span class="fw-medium">{{ $ttd['distribution'][$key] ?? 0 }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endSection
