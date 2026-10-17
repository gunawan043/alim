@extends('layouts.master')

@section('title', 'Maintenance — Konsol Sistem')

@section('css')
    @include('dashboard._shared._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Konsol Sistem @endslot
        @slot('title') Maintenance @endslot
    @endcomponent

    <div class="dashboard-role" data-role="super-admin">
        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-tools-line"></i></span>
                <h6 class="dash-section-title">Status Pemeliharaan</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="dash-grid">
                @foreach ([
                    ['label' => 'Maintenance Mode', 'value' => $maintenance['down'] ? 'AKTIF' : 'Nonaktif', 'icon' => 'ri-shut-down-line', 'color' => $maintenance['down'] ? 'danger' : 'success', 'sub' => 'Status aplikasi'],
                    ['label' => 'Config Cache', 'value' => $maintenance['config_cached'] ? 'Tersimpan' : 'Belum', 'icon' => 'ri-settings-4-line', 'color' => $maintenance['config_cached'] ? 'success' : 'warning', 'sub' => 'bootstrap/cache/config.php'],
                    ['label' => 'Route Cache', 'value' => $maintenance['routes_cached'] ? 'Tersimpan' : 'Belum', 'icon' => 'ri-route-line', 'color' => $maintenance['routes_cached'] ? 'success' : 'warning', 'sub' => 'bootstrap/cache/routes'],
                    ['label' => 'View Terkompilasi', 'value' => $maintenance['compiled_views'], 'icon' => 'ri-file-code-line', 'color' => 'info', 'sub' => 'storage/framework/views'],
                    ['label' => 'Cache Driver', 'value' => $maintenance['cache_driver'], 'icon' => 'ri-database-line', 'color' => 'primary', 'sub' => 'Queue: ' . $maintenance['queue_driver']],
                    ['label' => 'Antrean Job', 'value' => $maintenance['jobs_pending'] . ' / ' . $maintenance['jobs_failed'], 'icon' => 'ri-stack-line', 'color' => $maintenance['jobs_failed'] > 0 ? 'danger' : 'success', 'sub' => 'pending / gagal'],
                    ['label' => 'Log Aplikasi', 'value' => $maintenance['log_size_kb'] . ' KB', 'icon' => 'ri-file-text-line', 'color' => 'secondary', 'sub' => $maintenance['log_modified'] ?? 'belum ada'],
                    ['label' => 'Migrasi Terakhir', 'value' => '✓', 'icon' => 'ri-database-2-line', 'color' => 'success', 'sub' => $maintenance['last_migration'] ?? '—'],
                ] as $kpi)
                    <div class="dash-span" style="--span-xl: 3; --span-md: 6;">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <p class="dash-label">{{ $kpi['label'] }}</p>
                                        <h4 class="widget-stat-value mb-1" style="font-size: 1.15rem;">{{ is_numeric($kpi['value']) ? number_format((float) $kpi['value']) : $kpi['value'] }}</h4>
                                        <p class="mb-0 text-muted fs-13 text-truncate" title="{{ $kpi['sub'] }}">{{ $kpi['sub'] }}</p>
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
                <span class="dash-section-icon"><i class="ri-terminal-box-line"></i></span>
                <h6 class="dash-section-title">Perintah Maintenance</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="alert alert-warning d-flex align-items-start gap-2">
                <i class="ri-alert-line mt-1"></i>
                <div>
                    Perintah dijalankan melalui terminal/SSH di server. Halaman ini hanya referensi —
                    tidak mengeksekusi perintah apa pun dari browser.
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-muted fs-13">
                                    <th style="width: 45%;">Perintah</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($commands as $cmd)
                                    <tr>
                                        <td><code class="text-primary">{{ $cmd['command'] }}</code></td>
                                        <td class="fs-13">{{ $cmd['desc'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
