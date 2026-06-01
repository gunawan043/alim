@extends('layouts.master')
@section('title') Dashboard Recruitment @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
    <style>
        /* ====== GLOBAL DASHBOARD STYLE ====== */
        :root {
            --ats-grad-1: linear-gradient(135deg, #405189 0%, #5b73a8 100%);
            --ats-grad-2: linear-gradient(135deg, #0ab39c 0%, #299cdb 100%);
            --ats-grad-3: linear-gradient(135deg, #f7b84b 0%, #f06548 100%);
            --ats-grad-4: linear-gradient(135deg, #299cdb 0%, #405189 100%);
            --ats-grad-5: linear-gradient(135deg, #f06548 0%, #d63384 100%);
        }

        /* HERO */
        .ats-hero {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(135deg, #1a2545 0%, #2a3a6e 50%, #3b4d8a 100%);
            color: #fff;
            padding: 28px 30px;
            box-shadow: 0 10px 30px rgba(26, 37, 69, 0.25);
        }
        .ats-hero::before {
            content: '';
            position: absolute;
            top: -50px; right: -50px;
            width: 280px; height: 280px;
            background: radial-gradient(circle, rgba(255,255,255,.08) 0%, transparent 70%);
            border-radius: 50%;
        }
        .ats-hero::after {
            content: '';
            position: absolute;
            bottom: -80px; left: 30%;
            width: 320px; height: 320px;
            background: radial-gradient(circle, rgba(41, 156, 219, .12) 0%, transparent 70%);
            border-radius: 50%;
        }
        .ats-hero .hero-label {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 1px;
            padding: 4px 12px; border-radius: 20px;
            background: rgba(255,255,255,.12); backdrop-filter: blur(6px);
        }
        .ats-hero h1 { font-size: 26px; font-weight: 700; margin: 12px 0 4px; position: relative; z-index: 1; }
        .ats-hero p { font-size: 14px; opacity: .8; margin: 0; position: relative; z-index: 1; }
        .ats-hero .hero-stat { position: relative; z-index: 1; }
        .ats-hero .hero-stat-num { font-size: 42px; font-weight: 800; line-height: 1; letter-spacing: -1px; }
        .ats-hero .hero-stat-label { font-size: 12px; opacity: .75; text-transform: uppercase; letter-spacing: .5px; margin-top: 6px; }

        /* KPI Cards */
        .kpi-card {
            border: none; border-radius: 14px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
            transition: transform .2s, box-shadow .2s;
            position: relative; overflow: hidden;
        }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(0,0,0,.08); }
        .kpi-card .kpi-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 22px;
        }
        .kpi-card .kpi-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }
        .kpi-card .kpi-value { font-size: 28px; font-weight: 700; line-height: 1.1; color: #1a2545; }
        .kpi-card .kpi-meta { font-size: 12px; color: #6c757d; }
        .kpi-card .kpi-bar {
            position: absolute; bottom: 0; left: 0; height: 3px;
            background: var(--bar, #405189); width: var(--pct, 50%);
        }

        /* Section header */
        .section-head {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 16px;
        }
        .section-head h5 {
            font-size: 15px; font-weight: 700; color: #1a2545;
            margin: 0; display: flex; align-items: center; gap: 8px;
        }
        .section-head h5 .badge-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #0ab39c; display: inline-block;
        }

        /* Mini card */
        .mini-card {
            border: none; border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
            background: #fff;
        }
        .mini-card .card-body { padding: 18px 20px; }

        /* Funnel */
        .funnel-step {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 14px; margin-bottom: 8px;
            border-radius: 10px;
            background: #f8f9fb;
            position: relative; overflow: hidden;
        }
        .funnel-step .step-icon {
            width: 38px; height: 38px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 16px; color: #fff; flex-shrink: 0;
        }
        .funnel-step .step-name { font-size: 13px; font-weight: 600; color: #1a2545; }
        .funnel-step .step-count { font-size: 18px; font-weight: 700; color: #1a2545; }
        .funnel-step .step-bar {
            position: absolute; bottom: 0; left: 0; height: 3px;
            background: var(--step-color, #405189);
            width: var(--step-pct, 0%);
            transition: width .8s ease;
        }
        .funnel-step .step-conversion {
            font-size: 11px; color: #6c757d;
            background: #fff; padding: 2px 8px; border-radius: 10px;
            border: 1px solid #e9ecef;
        }

        /* Activity item */
        .activity-item {
            display: flex; gap: 12px; padding: 12px 0;
            border-bottom: 1px dashed #e9ecef;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-item .act-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .activity-item .act-title { font-size: 13px; font-weight: 600; color: #1a2545; }
        .activity-item .act-meta { font-size: 11px; color: #94a3b8; }

        /* Reminder */
        .reminder-card {
            display: flex; align-items: center; gap: 12px;
            padding: 12px; border-radius: 10px;
            background: #f8f9fb; margin-bottom: 8px;
            border-left: 3px solid #405189;
        }
        .reminder-card.today { border-color: #f06548; background: rgba(240,101,72,.05); }
        .reminder-card.tomorrow { border-color: #f7b84b; background: rgba(247,184,75,.05); }
        .reminder-card .rd-name { font-size: 13px; font-weight: 600; color: #1a2545; }
        .reminder-card .rd-job { font-size: 11px; color: #6c757d; }

        /* Avatars */
        .ats-avatar {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, #405189, #299cdb);
            color: #fff; display: inline-flex;
            align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px;
            flex-shrink: 0;
        }
        .ats-avatar-sm {
            width: 28px; height: 28px; border-radius: 8px;
            font-size: 12px; font-weight: 700;
        }

        /* Pulse */
        .pulse-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #f06548; position: relative;
        }
        .pulse-dot::after {
            content: ''; position: absolute; inset: -3px;
            border-radius: 50%; border: 2px solid #f06548;
            opacity: .4; animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(.9); opacity: .4; }
            100% { transform: scale(1.6); opacity: 0; }
        }

        /* Quick action */
        .quick-action {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 14px; border-radius: 10px;
            background: #f8f9fb; color: #1a2545;
            text-decoration: none;
            transition: background .2s, transform .15s;
            margin-bottom: 8px;
            border: 1px solid transparent;
        }
        .quick-action:hover {
            background: #fff; border-color: #e3e7ef;
            transform: translateX(3px); color: #1a2545;
        }
        .quick-action .qa-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 16px; color: #fff;
        }
        .quick-action .qa-label { font-size: 13px; font-weight: 600; }
        .quick-action .qa-meta { font-size: 11px; color: #6c757d; }

        /* Status pill */
        .status-pill {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; font-weight: 600;
            padding: 3px 10px; border-radius: 20px;
        }
        .status-pill::before {
            content: ''; width: 6px; height: 6px; border-radius: 50%;
            background: currentColor;
        }

        /* Chart container */
        .chart-box { min-height: 280px; position: relative; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Rekrutmen @endslot
        @slot('title') Dashboard @endslot
    @endcomponent

    @php
        $hour = (int) now()->format('H');
        $greeting = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
    @endphp

    {{-- ====== ROW 1: HERO + KPI GRID ====== --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="ats-hero h-100 d-flex flex-column justify-content-between">
                <div>
                    <span class="hero-label">
                        <i class="ri-dashboard-3-line"></i> Recruitment Overview
                    </span>
                    <h1 class="mt-2">{{ $greeting}}, {{ auth()->user()->name ?? 'Admin' }} 👋</h1>
                    <p>
                        Pantau progress rekrutmen, kelola pelamar, dan jadwalkan interview dalam satu tampilan terpadu.
                        @if($lowonganBaru > 0)
                            <strong style="color: #f7b84b;">{{ $lowonganBaru }} lowongan baru</strong> perlu ditinjau.
                        @endif
                    </p>
                </div>
                <div class="row mt-4 g-3 hero-stat">
                    <div class="col-6 col-md-3">
                        <div class="hero-stat-num counter-value" data-target="{{ $stats['total_applications'] }}">0</div>
                        <div class="hero-stat-label">Total Pelamar</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="hero-stat-num counter-value" data-target="{{ $stats['active_jobs'] }}">0</div>
                        <div class="hero-stat-label">Lowongan Aktif</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="hero-stat-num counter-value" data-target="{{ $stats['dalam_proses'] }}">0</div>
                        <div class="hero-stat-label">Sedang Diproses</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="hero-stat-num counter-value" data-target="{{ $stats['diterima'] }}">0</div>
                        <div class="hero-stat-label">Diterima</div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3 flex-wrap hero-stat">
                    <a href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm fw-semibold">
                        <i class="ri-file-list-3-line align-bottom me-1"></i> Kelola Lamaran
                    </a>
                    <a href="{{ route('user.ats.jobs.create', ['userId' => $userId]) }}" class="btn btn-outline-light btn-sm fw-semibold">
                        <i class="ri-add-circle-line align-bottom me-1"></i> Buat Lowongan
                    </a>
                    <a href="{{ route('user.ats.reports.index', ['userId' => $userId]) }}" class="btn btn-outline-light btn-sm fw-semibold">
                        <i class="ri-bar-chart-line align-bottom me-1"></i> Lihat Report
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <div class="kpi-label">Tingkat Konversi</div>
                            <div class="kpi-value mt-1">{{ (int) $konversiRate }}<span style="font-size: 20px; color: #6c757d;">%</span></div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(247, 184, 75, .15); color: #f7b84b;">
                            <i class="ri-percent-line"></i>
                        </div>
                    </div>
                    <div class="progress rounded-pill mb-2" style="height: 6px;">
                        <div class="progress-bar" style="width: {{ (int) $konversiRate }}%; background: #f7b84b;"></div>
                    </div>
                    <div class="kpi-meta">
                        <i class="ri-information-line"></i> {{ $stats['diterima'] }} diterima dari {{ $stats['total_applications'] }} pelamar
                    </div>
                    <hr class="my-3">
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <div class="fw-bold text-success" style="font-size: 18px;">{{ $stats['diterima'] }}</div>
                            <div class="kpi-meta">Diterima</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-danger" style="font-size: 18px;">{{ $stats['ditolak'] }}</div>
                            <div class="kpi-meta">Ditolak</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-info" style="font-size: 18px;">{{ $stats['dalam_proses'] }}</div>
                            <div class="kpi-meta">Proses</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== ROW 2: 4 KPI SMALL CARDS ====== --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card" style="--bar: #405189; --pct: 100%;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Total Lowongan</div>
                            <div class="kpi-value mt-1 counter-value" data-target="{{ $stats['total_jobs'] }}">0</div>
                            <div class="kpi-meta mt-2">
                                <span class="badge bg-success-subtle text-success">
                                    <i class="ri-checkbox-circle-line"></i> {{ $stats['active_jobs'] }} aktif
                                </span>
                                <span class="badge bg-light text-secondary ms-1">
                                    <i class="ri-draft-line"></i> {{ $stats['draft_jobs'] }} draft
                                </span>
                            </div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(64, 81, 137, .12); color: #405189;">
                            <i class="ri-briefcase-4-line"></i>
                        </div>
                    </div>
                </div>
                <div class="kpi-bar"></div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card" style="--bar: #299cdb; --pct: 100%;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Total Pelamar</div>
                            <div class="kpi-value mt-1 counter-value" data-target="{{ $stats['total_applications'] }}">0</div>
                            <div class="kpi-meta mt-2">
                                <span class="badge bg-{{ $stats['app_growth'] >= 0 ? 'success' : 'danger' }}-subtle text-{{ $stats['app_growth'] >= 0 ? 'success' : 'danger' }}">
                                    <i class="ri-arrow-{{ $stats['app_growth'] >= 0 ? 'up' : 'down' }}-line"></i>
                                    {{ abs($stats['app_growth']) }}%
                                </span>
                                <small class="text-muted ms-1">vs bulan lalu</small>
                            </div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(41, 156, 219, .12); color: #299cdb;">
                            <i class="ri-user-line"></i>
                        </div>
                    </div>
                </div>
                <div class="kpi-bar"></div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card" style="--bar: #0ab39c; --pct: 100%;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Diterima</div>
                            <div class="kpi-value mt-1 text-success counter-value" data-target="{{ $stats['diterima'] }}">0</div>
                            <div class="kpi-meta mt-2">
                                <span class="badge bg-{{ $stats['hired_growth'] >= 0 ? 'success' : 'danger' }}-subtle text-{{ $stats['hired_growth'] >= 0 ? 'success' : 'danger' }}">
                                    <i class="ri-arrow-{{ $stats['hired_growth'] >= 0 ? 'up' : 'down' }}-line"></i>
                                    {{ abs($stats['hired_growth']) }}%
                                </span>
                                <small class="text-muted ms-1">vs bulan lalu</small>
                            </div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(10, 179, 156, .12); color: #0ab39c;">
                            <i class="ri-user-follow-line"></i>
                        </div>
                    </div>
                </div>
                <div class="kpi-bar"></div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card" style="--bar: #f06548; --pct: 100%;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Ditolak</div>
                            <div class="kpi-value mt-1 text-danger counter-value" data-target="{{ $stats['ditolak'] }}">0</div>
                            <div class="kpi-meta mt-2">
                                <span class="badge bg-light text-secondary">
                                    <i class="ri-pie-chart-line"></i> {{ $stats['total_applications'] > 0 ? round(($stats['ditolak'] / max(1, $stats['total_applications'])) * 100) : 0 }}% dari total
                                </span>
                            </div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(240, 101, 72, .12); color: #f06548;">
                            <i class="ri-user-unfollow-line"></i>
                        </div>
                    </div>
                </div>
                <div class="kpi-bar"></div>
            </div>
        </div>
    </div>

    {{-- ====== ROW 3: TREND CHART + STATUS DISTRIBUTION + HIRING FUNNEL ====== --}}
    <div class="row g-3 mb-3">
        {{-- TREND --}}
        <div class="col-xl-7">
            <div class="card mini-card h-100">
                <div class="card-body">
                    <div class="section-head">
                        <h5>
                            <span class="badge-dot"></span>
                            Tren Pelamar & Penerimaan
                        </h5>
                        <div class="d-flex gap-2 align-items-center">
                            <small class="text-muted">12 bulan terakhir</small>
                            <a href="{{ route('user.ats.reports.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">
                                <i class="ri-external-link-line align-bottom"></i> Detail
                            </a>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <div class="kpi-label">Pelamar Bulan Ini</div>
                            <div class="kpi-value mt-1" style="font-size: 22px;">
                                {{ number_format(end($chartData['applications']) ?: 0) }}
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="kpi-label">Diterima Bulan Ini</div>
                            <div class="kpi-value mt-1" style="font-size: 22px; color: #0ab39c;">
                                {{ number_format(end($chartData['hired']) ?: 0) }}
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="kpi-label">Avg Pelamar/Bulan</div>
                            <div class="kpi-value mt-1" style="font-size: 22px; color: #405189;">
                                {{ number_format(round(array_sum($chartData['applications']) / max(1, count($chartData['applications'])))) }}
                            </div>
                        </div>
                    </div>
                    <div id="applicants_trend" class="apex-charts" dir="ltr" style="height: 280px"></div>
                </div>
            </div>
        </div>

        {{-- HIRING FUNNEL --}}
        <div class="col-xl-5">
            <div class="card mini-card h-100">
                <div class="card-body">
                    <div class="section-head">
                        <h5>
                            <span class="badge-dot" style="background:#0ab39c;"></span>
                            Hiring Funnel
                        </h5>
                        <a href="{{ route('user.ats.reports.hiring-funnel', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">
                            <i class="ri-external-link-line align-bottom"></i>
                        </a>
                    </div>
                    @php
                        $funnelData = [
                            ['name' => 'Total Pelamar',   'count' => $stats['total_applications'], 'icon' => 'ri-group-line',           'color' => '#405189'],
                            ['name' => 'Seleksi Adm',     'count' => $funnel['seleksi_adm']['count'] ?? 0,    'icon' => 'ri-file-list-3-line', 'color' => '#299cdb'],
                            ['name' => 'Lolos Adm',       'count' => $funnel['lolos_adm']['count'] ?? 0,      'icon' => 'ri-checkbox-circle-line', 'color' => '#0ab39c'],
                            ['name' => 'Tes Tertulis',    'count' => $funnel['tes']['count'] ?? 0,           'icon' => 'ri-edit-box-line',     'color' => '#f7b84b'],
                            ['name' => 'Wawancara',       'count' => $funnel['wawancara']['count'] ?? 0,     'icon' => 'ri-mic-line',          'color' => '#d63384'],
                            ['name' => 'Diterima',        'count' => $stats['diterima'],                    'icon' => 'ri-user-follow-line',  'color' => '#0ab39c'],
                        ];
                        $maxFunnel = max(array_column($funnelData, 'count')) ?: 1;
                    @endphp
                    @foreach($funnelData as $step)
                        @php
                            $pct = $maxFunnel > 0 ? round(($step['count'] / $maxFunnel) * 100) : 0;
                            $prev = $loop->index > 0 ? $funnelData[$loop->index - 1]['count'] : 0;
                            $conv = $prev > 0 ? round(($step['count'] / $prev) * 100) : ($step['count'] > 0 ? 100 : 0);
                        @endphp
                        <div class="funnel-step" style="--step-color: {{ $step['color'] }}; --step-pct: {{ $pct }}%;">
                            <div class="step-icon" style="background: {{ $step['color'] }};">
                                <i class="{{ $step['icon'] }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="step-name">{{ $step['name'] }}</div>
                                <div class="text-muted" style="font-size: 11px;">{{ number_format($step['count']) }} kandidat</div>
                            </div>
                            <div class="text-end">
                                <div class="step-count">{{ number_format($step['count']) }}</div>
                                @if($loop->index > 0)
                                    <span class="step-conversion"><i class="ri-arrow-down-line"></i> {{ $conv }}%</span>
                                @endif
                            </div>
                            <div class="step-bar"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ====== ROW 4: STATUS DISTRIBUTION + TOP JOBS ====== --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-4">
            <div class="card mini-card h-100">
                <div class="card-body">
                    <div class="section-head">
                        <h5>
                            <span class="badge-dot" style="background:#299cdb;"></span>
                            Distribusi Status
                        </h5>
                    </div>
                    <div id="status_distribution" class="apex-charts" dir="ltr" style="height: 300px"></div>

                    <div class="mt-3">
                        @php
                            $statusList = [
                                ['label' => 'Diterima',     'value' => $stats['diterima'],     'color' => '#0ab39c', 'icon' => 'ri-user-follow-line'],
                                ['label' => 'Ditolak',      'value' => $stats['ditolak'],      'color' => '#f06548', 'icon' => 'ri-user-unfollow-line'],
                                ['label' => 'Seleksi Adm',  'value' => $stats['seleksi_adm'] ?? 0,   'color' => '#299cdb', 'icon' => 'ri-file-list-3-line'],
                                ['label' => 'Menunggu',     'value' => $stats['menunggu'] ?? 0,       'color' => '#f7b84b', 'icon' => 'ri-time-line'],
                                ['label' => 'Dalam Proses', 'value' => $stats['dalam_proses'],  'color' => '#405189', 'icon' => 'ri-loader-4-line'],
                            ];
                        @endphp
                        @foreach($statusList as $s)
                            <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom: 1px dashed #eef0f5;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="{{ $s['icon'] }}" style="color: {{ $s['color'] }};"></i>
                                    <span class="text-muted" style="font-size: 13px;">{{ $s['label'] }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress rounded-pill" style="width: 60px; height: 5px;">
                                        <div class="progress-bar" style="width: {{ $stats['total_applications'] > 0 ? round(($s['value'] / max(1, $stats['total_applications'])) * 100) : 0 }}%; background: {{ $s['color'] }};"></div>
                                    </div>
                                    <span class="fw-semibold" style="font-size: 13px; min-width: 30px; text-align: right;">{{ $s['value'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mini-card h-100">
                <div class="card-body">
                    <div class="section-head">
                        <h5>
                            <span class="badge-dot" style="background:#f7b84b;"></span>
                            Top Lowongan
                        </h5>
                        <a href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">Semua</a>
                    </div>
                    @php
                        $colors = ['primary', 'info', 'success', 'warning', 'danger', 'purple'];
                        $maxApp = $topJobs->max('applications_count') ?: 1;
                    @endphp
                    @forelse($topJobs as $idx => $job)
                        @php
                            $w = $maxApp > 0 ? round(($job->applications_count / $maxApp) * 100) : 0;
                            if ($w == 0 && $job->applications_count > 0) $w = 5;
                        @endphp
                        <a href="{{ route('user.ats.jobs.show', ['userId' => $userId, 'job' => $job->id]) }}"
                           class="d-flex align-items-center gap-2 py-2 text-decoration-none text-reset"
                           style="border-bottom: 1px dashed #eef0f5;">
                            <div class="ats-avatar ats-avatar-sm" style="background: linear-gradient(135deg, #405189, #299cdb);">
                                {{ $idx + 1 }}
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-semibold text-truncate" style="font-size: 13px;">{{ $job->judul }}</div>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">{{ $job->kode_lowongan }}</small>
                                    <span class="status-pill text-{{ $job->status === 'aktif' ? 'success' : 'secondary' }}"
                                          style="background: {{ $job->status === 'aktif' ? 'rgba(10,179,156,.12)' : 'rgba(108,117,125,.12)' }};">
                                        {{ ucfirst($job->status) }}
                                    </span>
                                </div>
                                <div class="progress rounded-pill mt-1" style="height: 3px;">
                                    <div class="progress-bar bg-{{ $colors[$idx % 6] }}" style="width: {{ $w }}%;"></div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold" style="font-size: 16px; color: #1a2545;">{{ $job->applications_count }}</div>
                                <small class="text-muted">pelamar</small>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-4">
                            <i class="ri-briefcase-line text-muted" style="font-size: 32px;"></i>
                            <p class="text-muted mb-0 mt-2">Belum ada lowongan aktif.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mini-card h-100">
                <div class="card-body">
                    <div class="section-head">
                        <h5>
                            <span class="badge-dot" style="background:#d63384;"></span>
                            Reminder Interview
                            @if($interviewReminders->count() > 0)
                                <span class="pulse-dot ms-1"></span>
                            @endif
                        </h5>
                        <a href="{{ route('user.ats.interviews.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">Semua</a>
                    </div>
                    <div data-simplebar style="max-height: 360px">
                        @forelse($interviewReminders as $reminder)
                            @php
                                $days = (int) now()->diffInDays(\Carbon\Carbon::parse($reminder->jadwal_mulai), false);
                                $cls = $days <= 0 ? 'today' : ($days == 1 ? 'tomorrow' : '');
                            @endphp
                            <div class="reminder-card {{ $cls }}">
                                <div class="ats-avatar ats-avatar-sm">
                                    {{ substr($reminder->recruitmentApplication->recruitmentProfile->user->name ?? '?', 0, 1) }}
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="rd-name text-truncate">{{ $reminder->recruitmentApplication->recruitmentProfile->user->name ?? '-' }}</div>
                                    <div class="rd-job text-truncate">{{ $reminder->recruitmentApplication->recruitmentJob->judul ?? '-' }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold" style="font-size: 12px;">
                                        @if($days <= 0)
                                            <span class="text-danger">HARI INI</span>
                                        @elseif($days == 1)
                                            <span class="text-warning">BESOK</span>
                                        @else
                                            <span class="text-primary">{{ $days }} hari</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($reminder->jadwal_mulai)->format('d M, H:i') }}
                                    </small>
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

    {{-- ====== ROW 5: QUICK ACTIONS + RECENT ACTIVITIES ====== --}}
    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card mini-card h-100">
                <div class="card-body">
                    <div class="section-head">
                        <h5>
                            <span class="badge-dot" style="background:#405189;"></span>
                            Aksi Cepat
                        </h5>
                    </div>

                    <a href="{{ route('user.ats.jobs.create', ['userId' => $userId]) }}" class="quick-action">
                        <div class="qa-icon" style="background: linear-gradient(135deg, #405189, #5b73a8);"><i class="ri-add-circle-line"></i></div>
                        <div class="flex-grow-1">
                            <div class="qa-label">Buat Lowongan Baru</div>
                            <div class="qa-meta">Tambah lowongan pekerjaan</div>
                        </div>
                        <i class="ri-arrow-right-s-line text-muted"></i>
                    </a>

                    <a href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}" class="quick-action">
                        <div class="qa-icon" style="background: linear-gradient(135deg, #299cdb, #405189);"><i class="ri-file-list-3-line"></i></div>
                        <div class="flex-grow-1">
                            <div class="qa-label">Review Lamaran</div>
                            <div class="qa-meta">{{ $stats['dalam_proses'] }} sedang diproses</div>
                        </div>
                        <i class="ri-arrow-right-s-line text-muted"></i>
                    </a>

                    <a href="{{ route('user.ats.candidates.index', ['userId' => $userId]) }}" class="quick-action">
                        <div class="qa-icon" style="background: linear-gradient(135deg, #0ab39c, #299cdb);"><i class="ri-user-search-line"></i></div>
                        <div class="flex-grow-1">
                            <div class="qa-label">Cari Kandidat</div>
                            <div class="qa-meta">Database pelamar</div>
                        </div>
                        <i class="ri-arrow-right-s-line text-muted"></i>
                    </a>

                    <a href="{{ route('user.ats.interviews.index', ['userId' => $userId]) }}" class="quick-action">
                        <div class="qa-icon" style="background: linear-gradient(135deg, #f7b84b, #f06548);"><i class="ri-calendar-event-line"></i></div>
                        <div class="flex-grow-1">
                            <div class="qa-label">Jadwal Interview</div>
                            <div class="qa-meta">{{ $interviewReminders->count() }} agenda terdekat</div>
                        </div>
                        <i class="ri-arrow-right-s-line text-muted"></i>
                    </a>

                    <a href="{{ route('user.ats.reports.index', ['userId' => $userId]) }}" class="quick-action">
                        <div class="qa-icon" style="background: linear-gradient(135deg, #d63384, #f06548);"><i class="ri-bar-chart-box-line"></i></div>
                        <div class="flex-grow-1">
                            <div class="qa-label">Laporan & Analitik</div>
                            <div class="qa-meta">Hiring funnel, time-to-hire</div>
                        </div>
                        <i class="ri-arrow-right-s-line text-muted"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card mini-card h-100">
                <div class="card-body">
                    <div class="section-head">
                        <h5>
                            <span class="badge-dot" style="background:#94a3b8;"></span>
                            Aktivitas Terbaru
                        </h5>
                        <small class="text-muted">10 aktivitas terakhir</small>
                    </div>
                    <div data-simplebar style="max-height: 420px">
                        @forelse($recentActivities as $act)
                            <div class="activity-item">
                                <div class="act-icon" style="background: rgba(64, 81, 137, .1); color: #405189;">
                                    <i class="ri-history-line"></i>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="act-title text-truncate">{{ $act->description ?? 'Aktivitas recruitment' }}</div>
                                    <div class="act-meta">
                                        <i class="ri-user-line"></i> {{ $act->user->name ?? 'Sistem' }}
                                        &middot; <i class="ri-time-line"></i> {{ $act->created_at?->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="ri-history-line text-muted" style="font-size: 32px;"></i>
                                <p class="text-muted mb-0 mt-2">Belum ada aktivitas tercatat.</p>
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
        const getColor = (cls) => getComputedStyle(document.documentElement).getPropertyValue(cls).trim() || '#405189';

        // 1) TREND CHART (Area)
        const trendEl = document.querySelector('#applicants_trend');
        if (trendEl) {
            new ApexCharts(trendEl, {
                chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
                series: [
                    { name: 'Pelamar',  data: chartData.applications },
                    { name: 'Diterima', data: chartData.hired },
                ],
                colors: [getColor('--vz-primary'), '#0ab39c'],
                stroke: { curve: 'smooth', width: [3, 2] },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
                dataLabels: { enabled: false },
                xaxis: { categories: chartData.labels, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '11px' } } },
                grid: { borderColor: '#f1f1f1', strokeDashArray: 3 },
                legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' },
                yaxis: { labels: { formatter: v => Math.round(v) } },
                markers: { size: 0, hover: { size: 5 } },
                tooltip: { y: { formatter: v => v + ' kandidat' } },
            }).render();
        }

        // 2) STATUS DISTRIBUTION (Donut)
        const statusEl = document.querySelector('#status_distribution');
        if (statusEl) {
            const distribution = [
                { label: 'Diterima',       value: stats.diterima,             color: '#0ab39c' },
                { label: 'Ditolak',        value: stats.ditolak,              color: '#f06548' },
                { label: 'Seleksi Adm',    value: stats.seleksi_adm ?? 0,     color: '#299cdb' },
                { label: 'Menunggu',       value: stats.menunggu ?? 0,        color: '#f7b84b' },
                { label: 'Dalam Proses',   value: stats.dalam_proses,         color: '#405189' },
            ];
            new ApexCharts(statusEl, {
                chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
                series: distribution.map(d => d.value),
                labels: distribution.map(d => d.label),
                colors: distribution.map(d => d.color),
                legend: { show: false },
                dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 600 }, formatter: val => Math.round(val) + '%' },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '72%',
                            labels: {
                                show: true,
                                name: { show: true, fontSize: '13px', color: '#6c757d', offsetY: 8 },
                                value: { show: true, fontSize: '24px', fontWeight: 700, color: '#1a2545', offsetY: -4, formatter: v => Math.round(v) },
                                total: {
                                    show: true,
                                    label: 'Total Pelamar',
                                    color: '#6c757d',
                                    fontSize: '12px',
                                    formatter: () => stats.total_applications
                                }
                            }
                        }
                    }
                },
                stroke: { width: 3, colors: ['#fff'] },
            }).render();
        }

        // Counter-up
        if (typeof CounterUp !== 'undefined') {
            document.querySelectorAll('.counter-value').forEach(el => {
                const target = parseInt(el.dataset.target) || 0;
                if (target > 0) new CounterUp(el, { duration: 1200, delay: 16 }).start();
            });
        }
    });
    </script>
@endsection
