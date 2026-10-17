@extends('layouts.master')

@section('title', 'Monitoring — Konsol Sistem')

@section('css')
    @include('dashboard._shared._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Konsol Sistem @endslot
        @slot('title') Monitoring @endslot
    @endcomponent

    <div class="dashboard-role" data-role="super-admin">
        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-pulse-line"></i></span>
                <h6 class="dash-section-title">Kesehatan Sistem</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="dash-grid">
                @foreach ([
                    ['label' => 'PHP', 'value' => $monitoring['php_version'], 'icon' => 'ri-code-s-slash-line', 'color' => 'primary', 'sub' => 'Laravel ' . $monitoring['laravel_version']],
                    ['label' => 'Environment', 'value' => strtoupper($monitoring['environment']), 'icon' => 'ri-server-line', 'color' => $monitoring['environment'] === 'production' ? 'danger' : 'warning', 'sub' => 'Debug: ' . ($monitoring['debug_mode'] ? 'AKTIF' : 'nonaktif')],
                    ['label' => 'Tabel Database', 'value' => $monitoring['db_tables'], 'icon' => 'ri-database-2-line', 'color' => 'info', 'sub' => $monitoring['db_driver'] . ' · ' . $monitoring['db_size_mb'] . ' MB'],
                    ['label' => 'Disk Tersisa', 'value' => $monitoring['disk_free_gb'] . ' GB', 'icon' => 'ri-hard-drive-2-line', 'color' => 'success', 'sub' => 'dari ' . $monitoring['disk_total_gb'] . ' GB'],
                    ['label' => 'Antrean Job', 'value' => $monitoring['jobs_pending'], 'icon' => 'ri-stack-line', 'color' => 'primary', 'sub' => $monitoring['queue_driver'] . ' driver'],
                    ['label' => 'Job Gagal', 'value' => $monitoring['jobs_failed'], 'icon' => 'ri-error-warning-line', 'color' => $monitoring['jobs_failed'] > 0 ? 'danger' : 'success', 'sub' => 'failed_jobs'],
                    ['label' => 'Sesi Aktif', 'value' => $monitoring['sessions_active'], 'icon' => 'ri-user-voice-line', 'color' => 'info', 'sub' => $monitoring['session_driver'] . ' driver'],
                    ['label' => 'Ukuran Log', 'value' => $monitoring['log_size_kb'] . ' KB', 'icon' => 'ri-file-text-line', 'color' => 'secondary', 'sub' => $monitoring['log_modified'] ?? 'belum ada log'],
                ] as $kpi)
                    <div class="dash-span" style="--span-xl: 3; --span-md: 6;">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <p class="dash-label">{{ $kpi['label'] }}</p>
                                        <h4 class="widget-stat-value mb-1">{{ is_numeric($kpi['value']) ? number_format((float) $kpi['value']) : $kpi['value'] }}</h4>
                                        <p class="mb-0 text-muted fs-13">{{ $kpi['sub'] }}</p>
                                    </div>
                                    <span class="avatar-title bg-{{ $kpi['color'] }}-subtle flex-shrink-0">
                                        <i class="{{ $kpi['icon'] }} text-{{ $kpi['color'] }}"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-line-chart-line"></i></span>
                <h6 class="dash-section-title">Aktivitas 7 Hari</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="dash-grid">
                <div class="dash-span" style="--span-xl: 8; --span-md: 12;">
                    <div class="card">
                        <div class="card-body">
                            <div id="chart-monitoring-activity" class="widget-chart-container"
                                 data-series="{{ json_encode([['name' => 'Aktivitas', 'data' => $activityData]]) }}"
                                 data-categories="{{ json_encode($activityLabels) }}"></div>
                        </div>
                    </div>
                </div>

                <div class="dash-span" style="--span-xl: 4; --span-md: 12;">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Job Gagal Terbaru</h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="widget-table-scroll">
                                <ul class="list-group list-group-flush">
                                    @forelse ($failedJobs as $job)
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <p class="mb-0 fw-medium fs-14">Queue: {{ $job->queue ?? 'default' }}</p>
                                                <small class="text-muted">ID #{{ $job->id }}</small>
                                            </div>
                                            <small class="text-muted">{{ $job->failed_at ? \Carbon\Carbon::parse($job->failed_at)->diffForHumans() : '—' }}</small>
                                        </li>
                                    @empty
                                        <li class="list-group-item border-0 p-0">
                                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-check-double-line', 'title' => 'Tidak ada job gagal'])
                                        </li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('chart-monitoring-activity');
            if (!el || !window.ApexCharts) return;
            new ApexCharts(el, {
                series: JSON.parse(el.dataset.series),
                chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
                stroke: { curve: 'smooth', width: 3 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
                xaxis: { categories: JSON.parse(el.dataset.categories || '[]') },
                colors: ['#405189'],
                dataLabels: { enabled: false },
                grid: { borderColor: '#f1f1f1' }
            }).render();
        });
    </script>
@endpush
