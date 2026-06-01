@extends('layouts.master')
@section('title') Dashboard Recruitment @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/libs/jsvectormap/jsvectormap.min.css') }}" rel="stylesheet">
    <style>
        .ats-bg-shape { position: absolute; start: 0; z-index: 0; opacity: .07; }
        .ats-avatar { width: 38px; height: 38px; border-radius: 50%; background: var(--vz-primary-bg-subtle); color: var(--vz-primary); display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; flex-shrink: 0; }
        .ats-pulse { position: relative; }
        .ats-pulse::before { content: ''; position: absolute; inset: -4px; border-radius: 50%; border: 2px solid currentColor; opacity: .3; animation: ats-pulse 1.5s infinite; }
        @keyframes ats-pulse { 0% { transform: scale(.9); opacity: .3; } 100% { transform: scale(1.6); opacity: 0; } }
        .ats-list-item { padding: 10px 12px; border-radius: 8px; transition: background .15s; display: flex; align-items: center; gap: 12px; }
        .ats-list-item:hover { background: var(--bs-light); }
        .ats-table th { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: var(--bs-secondary); font-weight: 600; }
        .ats-table td { vertical-align: middle; font-size: .875rem; }
        .ats-reminder { padding: 12px; border-radius: 8px; border-left: 3px solid var(--vz-primary); background: var(--bs-light); margin-bottom: 8px; }
        .ats-reminder.today { border-color: var(--vz-danger); background: rgba(220, 53, 69, .05); }
        .ats-reminder.tomorrow { border-color: var(--vz-warning); background: rgba(255, 193, 7, .05); }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Rekrutmen @endslot
        @slot('title') Dashboard @endslot
    @endcomponent

    {{-- ROW 1: Hero/CTA + 4 small stats (kiri 5) + 2 chart (kanan 7) --}}
    <div class="row">
        <div class="col-xxl-5">
            <div class="d-flex flex-column h-100">
                {{-- Hero Card (Trial pattern) --}}
                <div class="row h-100">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body p-0">
                                @php
                                    $activeJobs = $stats['active_jobs'];
                                    $lowonganBaru = \App\Models\RecruitmentJob::where('status', 'aktif')->where('tanggal_mulai', '>=', now()->subDays(7))->count();
                                    $pengingatInterview = $interviewReminders->count();
                                @endphp
                                <div class="alert alert-{{ $lowonganBaru > 0 ? 'primary' : 'secondary' }} border-0 rounded-0 m-0 d-flex align-items-center" role="alert">
                                    <i class="{{ $lowonganBaru > 0 ? 'ri-megaphone-line' : 'ri-information-line' }} text-{{ $lowonganBaru > 0 ? 'primary' : 'secondary' }} me-2 icon-sm"></i>
                                    <div class="flex-grow-1 text-truncate">
                                        Ada <b>{{ $lowonganBaru }}</b> lowongan baru dalam 7 hari terakhir &middot; <b>{{ $pengingatInterview }}</b> jadwal interview.
                                    </div>
                                    <div class="flex-shrink-0">
                                        <a href="{{ route('user.ats.jobs.create', ['userId' => $userId]) }}" class="text-reset text-decoration-underline"><b>Buat Lowongan</b></a>
                                    </div>
                                </div>

                                <div class="row align-items-end">
                                    <div class="col-sm-8">
                                        <div class="p-3">
                                            <p class="fs-20 lh-base">Total <span class="fw-semibold">{{ $stats['total_applications'] }}</span> pelamar &middot; <span class="fw-semibold text-success">{{ $stats['diterima'] }}</span> diterima <i class="mdi mdi-arrow-right text-success"></i></p>
                                            <div class="mt-3">
                                                <a href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}" class="btn btn-primary">
                                                    <i class="ri-file-list-3-line align-bottom me-1"></i> Kelola Lamaran
                                                </a>
                                                <a href="{{ route('user.ats.reports.index', ['userId' => $userId]) }}" class="btn btn-soft-secondary ms-1">
                                                    <i class="ri-bar-chart-line align-bottom me-1"></i> Lihat Report
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="px-3 text-center">
                                            <i class="ri-team-line text-primary" style="font-size: 80px; line-height: 1;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2 x 2 Small Stats (Users / Sessions / Avg / Bounce pattern) --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="fw-medium text-muted mb-0">Total Lowongan</p>
                                        <h2 class="mt-4 ff-secondary fw-semibold"><span class="counter-value" data-target="{{ $stats['total_jobs'] }}">0</span></h2>
                                        <p class="mb-0 text-muted">
                                            <span class="badge bg-light text-{{ $stats['active_jobs'] > 0 ? 'success' : 'secondary' }} mb-0">
                                                <i class="ri-checkbox-circle-line align-middle"></i> {{ $stats['active_jobs'] }} aktif
                                            </span>
                                            <span class="badge bg-light text-secondary mb-0">
                                                <i class="ri-draft-line align-middle"></i> {{ $stats['draft_jobs'] }} draft
                                            </span>
                                        </p>
                                    </div>
                                    <div>
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-info-subtle rounded-circle fs-2">
                                                <i class="ri-briefcase-4-line text-info"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="fw-medium text-muted mb-0">Total Pelamar</p>
                                        <h2 class="mt-4 ff-secondary fw-semibold"><span class="counter-value" data-target="{{ $stats['total_applications'] }}">0</span></h2>
                                        <p class="mb-0 text-muted">
                                            <span class="badge bg-light text-{{ $stats['app_growth'] >= 0 ? 'success' : 'danger' }} mb-0">
                                                <i class="ri-arrow-{{ $stats['app_growth'] >= 0 ? 'up' : 'down' }}-line align-middle"></i>
                                                {{ abs($stats['app_growth']) }}%
                                            </span>
                                            vs. bulan lalu
                                        </p>
                                    </div>
                                    <div>
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-primary-subtle rounded-circle fs-2">
                                                <i class="ri-user-line text-primary"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="fw-medium text-muted mb-0">Diterima</p>
                                        <h2 class="mt-4 ff-secondary fw-semibold text-success"><span class="counter-value" data-target="{{ $stats['diterima'] }}">0</span></h2>
                                        <p class="mb-0 text-muted">
                                            <span class="badge bg-light text-{{ $stats['hired_growth'] >= 0 ? 'success' : 'danger' }} mb-0">
                                                <i class="ri-arrow-{{ $stats['hired_growth'] >= 0 ? 'up' : 'down' }}-line align-middle"></i>
                                                {{ abs($stats['hired_growth']) }}%
                                            </span>
                                            vs. bulan lalu
                                        </p>
                                    </div>
                                    <div>
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-success-subtle rounded-circle fs-2">
                                                <i class="ri-user-follow-line text-success"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="fw-medium text-muted mb-0">Konversi</p>
                                        <h2 class="mt-4 ff-secondary fw-semibold text-warning"><span class="counter-value" data-target="{{ (int) $konversiRate }}">0</span>%</h2>
                                        <p class="mb-0 text-muted">
                                            <span class="badge bg-light text-warning mb-0">
                                                <i class="ri-pie-chart-line align-middle"></i> {{ $stats['dalam_proses'] }} proses
                                            </span>
                                        </p>
                                    </div>
                                    <div>
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                                                <i class="ri-percent-line text-warning"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-7">
            <div class="row h-100">
                {{-- Tren Bulanan (Live Users By Country pattern) --}}
                <div class="col-xl-6">
                    <div class="card card-height-100">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Tren Pelamar per Bulan</h4>
                            <div class="flex-shrink-0">
                                <a href="{{ route('user.ats.reports.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">Detail</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="applicants_trend" data-colors='["--vz-primary"]' class="apex-charts" dir="ltr" style="height: 252px"></div>

                            <div class="table-responsive table-card mt-3">
                                <table class="table table-borderless table-sm table-centered align-middle table-nowrap mb-1">
                                    <thead class="text-muted border-dashed border border-start-0 border-end-0 bg-light-subtle">
                                        <tr>
                                            <th>Periode</th>
                                            <th style="width: 30%;">Pelamar</th>
                                            <th style="width: 30%;">Diterima</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-0">
                                        @php
                                            $lastThree = array_slice($chartData['labels'], -3);
                                            $appLastThree = array_slice($chartData['applications'], -3);
                                            $hiredLastThree = array_slice($chartData['hired'], -3);
                                        @endphp
                                        @for($i = 0; $i < count($lastThree); $i++)
                                            <tr>
                                                <td>{{ $lastThree[$i] }}</td>
                                                <td>{{ number_format($appLastThree[$i]) }}</td>
                                                <td>{{ number_format($hiredLastThree[$i]) }}</td>
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Distribusi Status (Sessions by Countries pattern) --}}
                <div class="col-xl-6">
                    <div class="card card-height-100">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Distribusi Status Lamaran</h4>
                            <div>
                                <a href="{{ route('user.ats.reports.hiring-funnel', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">Funnel</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div>
                                <div id="status_distribution" class="apex-charts" dir="ltr"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 2: Audiences Metrics (line chart) + Audiences Sessions by Country (line chart 2) --}}
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Statistik Pelamar & Diterima</h4>
                    <div>
                        <button type="button" class="btn btn-soft-secondary btn-sm filter-range" data-range="6">6M</button>
                        <button type="button" class="btn btn-soft-primary btn-sm filter-range" data-range="12">1Y</button>
                    </div>
                </div>
                <div class="card-header p-0 border-0 bg-light-subtle">
                    <div class="row g-0 text-center">
                        <div class="col-6 col-sm-4">
                            <div class="p-3 border border-dashed border-start-0">
                                <h5 class="mb-1"><span class="counter-value" data-target="{{ $stats['total_applications'] }}">0</span>
                                    <span class="text-{{ $stats['app_growth'] >= 0 ? 'success' : 'danger' }} ms-1 fs-13">{{ abs($stats['app_growth']) }}%<i class="ri-arrow-right-{{ $stats['app_growth'] >= 0 ? 'up' : 'down' }}-line ms-1 align-middle"></i></span>
                                </h5>
                                <p class="text-muted mb-0">Total Lamaran</p>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="p-3 border border-dashed">
                                <h5 class="mb-1"><span class="counter-value" data-target="{{ $stats['diterima'] }}">0</span>
                                    <span class="text-{{ $stats['hired_growth'] >= 0 ? 'success' : 'danger' }} ms-1 fs-13">{{ abs($stats['hired_growth']) }}%<i class="ri-arrow-right-{{ $stats['hired_growth'] >= 0 ? 'up' : 'down' }}-line ms-1 align-middle"></i></span>
                                </h5>
                                <p class="text-muted mb-0">Diterima</p>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="p-3 border border-dashed border-end-0">
                                <h5 class="mb-1"><span class="counter-value" data-target="{{ $stats['dalam_proses'] }}">0</span>
                                    <span class="text-info ms-1 fs-13">Active<i class="ri-pulse-line ms-1 align-middle"></i></span>
                                </h5>
                                <p class="text-muted mb-0">Dalam Proses</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 pb-2">
                    <div>
                        <div id="audiences_metrics_charts" data-colors='["--vz-primary", "--vz-light"]' class="apex-charts" dir="ltr"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Hiring Funnel</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('user.ats.reports.hiring-funnel', ['userId' => $userId]) }}" class="text-reset dropdown-btn text-decoration-none">
                            <span class="fw-semibold text-uppercase fs-13">Detail <i class="ri-external-link-line align-bottom"></i></span>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div>
                        <div id="funnel_chart" data-colors='["--vz-success", "--vz-secondary"]' class="apex-charts" dir="ltr"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 3: Top Lowongan (Referrals Pages pattern) + Top Kandidat (Top Pages pattern) --}}
    <div class="row">
        <div class="col-xl-4">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Top Lowongan</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">Lihat Semua</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-3">
                        <div class="col-6">
                            <h6 class="text-muted text-uppercase fw-semibold text-truncate fs-13 mb-3">Total Pelamar</h6>
                            <h4 class="mb-0">{{ number_format($stats['total_applications']) }}</h4>
                            <p class="mb-0 mt-2 text-muted">
                                <span class="badge bg-{{ $stats['app_growth'] >= 0 ? 'success' : 'danger' }}-subtle text-{{ $stats['app_growth'] >= 0 ? 'success' : 'danger' }} mb-0">
                                    <i class="ri-arrow-{{ $stats['app_growth'] >= 0 ? 'up' : 'down' }}-line align-middle"></i>
                                    {{ abs($stats['app_growth']) }}%
                                </span>
                                vs. bulan lalu
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <i class="ri-briefcase-4-line text-primary" style="font-size: 60px; line-height: 1;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 pt-2">
                        @php
                            $colors = ['primary', 'info', 'success', 'warning', 'danger'];
                            $maxApp = $topJobs->max('applications_count') ?: 1;
                        @endphp
                        <div class="progress progress-lg rounded-pill mb-2">
                            @foreach($topJobs as $idx => $job)
                                @php
                                    $w = $maxApp > 0 ? round(($job->applications_count / $maxApp) * 100) : 0;
                                    if ($w == 0 && $job->applications_count > 0) $w = 5;
                                @endphp
                                <div class="progress-bar bg-{{ $colors[$idx % 5] }}" role="progressbar" style="width: {{ $w }}%" aria-valuenow="{{ $w }}" aria-valuemin="0" aria-valuemax="100"></div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-3 pt-2">
                        @forelse($topJobs as $idx => $job)
                            @php $color = $colors[$idx % 5]; @endphp
                            <div class="d-flex mb-2">
                                <div class="flex-grow-1">
                                    <p class="text-truncate text-muted fs-15 mb-0">
                                        <i class="mdi mdi-circle align-middle text-{{ $color }} me-2"></i>{{ $job->judul }}
                                        <small class="text-muted">({{ $job->kode_lowongan }})</small>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <p class="mb-0">{{ $job->applications_count }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted mb-0">Belum ada lowongan aktif.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Top Kandidat</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">Semua</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table align-middle table-borderless table-centered table-nowrap mb-0">
                            <thead class="text-muted table-light">
                                <tr>
                                    <th scope="col" style="width: 50%;">Kandidat</th>
                                    <th scope="col">Posisi</th>
                                    <th scope="col">Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCandidates as $cand)
                                    <tr>
                                        <td>
                                            <a href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $cand->id]) }}" class="d-flex align-items-center text-reset">
                                                <div class="ats-avatar me-2">{{ substr($cand->recruitmentProfile->user->name ?? '?', 0, 1) }}</div>
                                                <span class="text-truncate">{{ $cand->recruitmentProfile->user->name ?? '-' }}</span>
                                            </a>
                                        </td>
                                        <td><span class="text-muted">{{ Str::limit($cand->recruitmentJob->judul ?? '-', 25) }}</span></td>
                                        <td>
                                            <span class="badge bg-{{ $cand->nilai_akhir >= 80 ? 'success' : ($cand->nilai_akhir >= 60 ? 'warning' : 'secondary') }}-subtle text-{{ $cand->nilai_akhir >= 80 ? 'success' : ($cand->nilai_akhir >= 60 ? 'warning' : 'secondary') }}">
                                                {{ number_format($cand->nilai_akhir, 1) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Belum ada kandidat dengan nilai.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Reminder Interview</h4>
                </div>
                <div class="card-body">
                    <div data-simplebar style="max-height: 380px" class="px-1">
                        @forelse($interviewReminders as $reminder)
                            @php
                                $days = (int) now()->diffInDays(\Carbon\Carbon::parse($reminder->jadwal_mulai), false);
                                $cls = $days <= 0 ? 'today' : ($days == 1 ? 'tomorrow' : '');
                            @endphp
                            <div class="ats-reminder {{ $cls }}">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-2">
                                        <div class="ats-avatar ats-pulse text-primary">
                                            <i class="ri-calendar-event-line"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="fw-medium fs-13 text-truncate">{{ $reminder->recruitmentApplication->recruitmentProfile->user->name ?? '-' }}</div>
                                        <small class="text-muted text-truncate d-block">
                                            {{ $reminder->recruitmentApplication->recruitmentJob->judul ?? '-' }}
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <small class="fw-semibold d-block">
                                            @if($days <= 0)
                                                <span class="text-danger">HARI INI</span>
                                            @elseif($days == 1)
                                                <span class="text-warning">BESOK</span>
                                            @else
                                                <span class="text-primary">{{ $days }} hari</span>
                                            @endif
                                        </small>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($reminder->jadwal_mulai)->format('d M, H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="ri-calendar-check-line text-muted" style="font-size: 32px;"></i>
                                <p class="text-muted mb-0 mt-2">Tidak ada jadwal interview dalam 7 hari ke depan.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartData = @json($chartData);
        const stats = @json($stats);
        const funnel = @json($funnel);
        const demographics = @json($demographics);
        const getColor = (cls) => getComputedStyle(document.documentElement).getPropertyValue(cls).trim() || '#405189';

        // 1) Trend Bulanan (column chart) - Live Users By Country
        const trendEl = document.querySelector('#applicants_trend');
        if (trendEl) {
            new ApexCharts(trendEl, {
                chart: { type: 'bar', height: 252, toolbar: { show: false } },
                series: [{ name: 'Pelamar', data: chartData.applications }],
                colors: [getColor('--vz-primary')],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
                dataLabels: { enabled: false },
                xaxis: { categories: chartData.labels, axisBorder: { show: false }, axisTicks: { show: false } },
                grid: { borderColor: '#f1f1f1', padding: { top: 0, right: 10, bottom: 0 } },
                yaxis: { labels: { formatter: v => Math.round(v) } },
            }).render();
        }

        // 2) Status Distribution (donut) - Sessions by Countries pattern
        const statusEl = document.querySelector('#status_distribution');
        if (statusEl) {
            const distribution = [
                { label: 'Diterima',       value: stats.diterima,             color: '#0ab39c' },
                { label: 'Ditolak',        value: stats.ditolak,              color: '#f06548' },
                { label: 'Seleksi Adm',    value: stats.seleksi_adm,          color: '#299cdb' },
                { label: 'Menunggu',       value: stats.menunggu,             color: '#f7b84b' },
                { label: 'Dalam Proses',   value: stats.dalam_proses,         color: '#405189' },
            ];
            new ApexCharts(statusEl, {
                chart: { type: 'donut', height: 280 },
                series: distribution.map(d => d.value),
                labels: distribution.map(d => d.label),
                colors: distribution.map(d => d.color),
                legend: { position: 'bottom' },
                dataLabels: { enabled: true, formatter: val => Math.round(val) + '%' },
                plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Total', formatter: () => stats.total_applications } } } } },
            }).render();
        }

        // 3) Audiences Metrics - Statistik Pelamar & Diterima (line)
        const audEl = document.querySelector('#audiences_metrics_charts');
        if (audEl) {
            new ApexCharts(audEl, {
                chart: { type: 'area', height: 280, toolbar: { show: false } },
                series: [
                    { name: 'Pelamar',  data: chartData.applications },
                    { name: 'Diterima', data: chartData.hired },
                ],
                colors: [getColor('--vz-primary'), getColor('--vz-light')],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } },
                dataLabels: { enabled: false },
                xaxis: { categories: chartData.labels, axisBorder: { show: false }, axisTicks: { show: false } },
                grid: { borderColor: '#f1f1f1' },
                legend: { position: 'top' },
            }).render();
        }

        // 4) Funnel chart - Hiring Funnel (line area)
        const funnelEl = document.querySelector('#funnel_chart');
        if (funnelEl) {
            const stages = ['Seleksi Adm', 'Lolos Adm', 'Tes', 'Lolos Tes', 'Wawancara', 'Diterima'];
            const values = [
                funnel.seleksi_adm.count,
                funnel.lolos_adm.count,
                funnel.tes.count,
                funnel.lolos_tes.count,
                funnel.wawancara.count,
                funnel.diterima.count,
            ];
            new ApexCharts(funnelEl, {
                chart: { type: 'area', height: 300, toolbar: { show: false } },
                series: [{ name: 'Kandidat', data: values }],
                colors: [getColor('--vz-success')],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1, colorStops: [{ offset: 0, color: '#0ab39c', opacity: 0.4 }, { offset: 100, color: '#0ab39c', opacity: 0 }] } },
                dataLabels: { enabled: true, style: { fontSize: '11px' }, background: { enabled: false } },
                xaxis: { categories: stages, axisBorder: { show: false }, axisTicks: { show: false } },
                grid: { borderColor: '#f1f1f1' },
                markers: { size: 4, colors: ['#0ab39c'], strokeColors: '#fff', strokeWidth: 2 },
                yaxis: { labels: { formatter: v => Math.round(v) } },
            }).render();
        }

        // Counter-up
        if (typeof CounterUp !== 'undefined') {
            document.querySelectorAll('.counter-value').forEach(el => {
                const target = parseInt(el.dataset.target) || 0;
                if (target > 0) new CounterUp(el, { duration: 1000, delay: 16 }).start();
            });
        }
    });
    </script>
@endsection
