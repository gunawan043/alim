@extends('layouts.master')

@section('title', 'Konsol Sistem — Super Admin')

@section('css')
    @include('dashboard._shared._css')
    <style>
        .sys-chip {
            display: inline-flex;
            align-items: center;
            font-size: .72rem;
            font-weight: 600;
            color: #fff;
            background: rgba(255, 255, 255, 0.14);
            border-radius: 999px;
            padding: .22rem .6rem;
        }
    </style>
@endsection

@section('content')
    @php
        $user = auth()->user();
        $quickLinks = collect([
            ['label' => 'Fitur Sistem',  'icon' => 'ri-apps-2-line',        'color' => 'primary', 'route' => 'system.features'],
            ['label' => 'Monitoring',    'icon' => 'ri-pulse-line',         'color' => 'success', 'route' => 'system.monitoring'],
            ['label' => 'Maintenance',   'icon' => 'ri-tools-line',         'color' => 'warning', 'route' => 'system.maintenance'],
            ['label' => 'Konfigurasi',   'icon' => 'ri-settings-4-line',    'color' => 'info',    'route' => 'system.config'],
            ['label' => 'Dev Tools',     'icon' => 'ri-code-s-slash-line',  'color' => 'danger',  'route' => 'system.devtools'],
        ])->filter(fn ($l) => \Illuminate\Support\Facades\Route::has($l['route']));
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Dashboard @endslot
        @slot('title') Konsol Sistem @endslot
    @endcomponent

    <div class="dashboard-role" data-role="super-admin">

        {{-- ── HERO ─────────────────────────────────────────────── --}}
        <div class="welcome-banner mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative">
                <div class="flex-grow-1">
                    <span class="banner-eyebrow"><i class="ri-shield-star-line me-1"></i>Super Admin · System Engineer</span>
                    <h3 class="banner-title">Selamat datang, {{ $user->name ?? 'Administrator' }}</h3>
                    <div class="banner-meta">
                        <span><i class="ri-server-line me-1"></i>{{ $stats['schools_active'] }} unit aktif</span>
                        <span class="banner-dot">•</span>
                        <span><i class="ri-user-line me-1"></i>{{ number_format($stats['users_active']) }} user aktif</span>
                        <span class="banner-dot">•</span>
                        <span><i class="ri-calendar-line me-1"></i>{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-1">
                        <span class="sys-chip"><i class="ri-database-2-line me-1"></i>{{ number_format($stats['migrations_total']) }} migrasi</span>
                        <span class="sys-chip"><i class="ri-shield-keyhole-line me-1"></i>{{ $stats['roles_total'] }} role · {{ number_format($stats['permissions_total']) }} permission</span>
                        <span class="sys-chip"><i class="ri-history-line me-1"></i>{{ number_format($stats['activity_total']) }} log</span>
                    </div>
                </div>
                <div class="banner-icon-wrap"><i class="ri-shield-user-line"></i></div>
            </div>
        </div>

        {{-- ── RINGKASAN SISTEM ─────────────────────────────────── --}}
        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-dashboard-3-line"></i></span>
                <h6 class="dash-section-title">Ringkasan Sistem</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="dash-grid">
                @foreach ([
                    ['label' => 'User Aktif', 'value' => $stats['users_active'], 'icon' => 'ri-user-follow-line', 'color' => 'success', 'sub' => 'Dari ' . number_format($stats['users_total']) . ' total user'],
                    ['label' => 'User Nonaktif', 'value' => $stats['users_inactive'], 'icon' => 'ri-user-unfollow-line', 'color' => 'warning', 'sub' => 'Akun dinonaktifkan'],
                    ['label' => 'User Tanpa Role', 'value' => $stats['users_no_role'], 'icon' => 'ri-user-warning-line', 'color' => $stats['users_no_role'] > 0 ? 'danger' : 'success', 'sub' => 'Perlu penetapan role'],
                    ['label' => 'System Admin', 'value' => $stats['system_admins'], 'icon' => 'ri-admin-line', 'color' => 'primary', 'sub' => 'Akses penuh sistem'],
                    ['label' => 'Role & Permission', 'value' => $stats['roles_total'], 'icon' => 'ri-shield-keyhole-line', 'color' => 'info', 'sub' => number_format($stats['permissions_total']) . ' permission terdaftar'],
                    ['label' => 'Migrasi Database', 'value' => $stats['migrations_total'], 'icon' => 'ri-database-2-line', 'color' => 'primary', 'sub' => 'Skema teregistrasi'],
                    ['label' => 'Log Aktivitas', 'value' => $stats['activity_total'], 'icon' => 'ri-history-line', 'color' => 'info', 'sub' => number_format($stats['activity_today']) . ' tercatat hari ini'],
                    ['label' => 'Antrean & Gagal', 'value' => $stats['jobs_pending'] . ' / ' . $stats['jobs_failed'], 'icon' => 'ri-stack-line', 'color' => $stats['jobs_failed'] > 0 ? 'danger' : 'success', 'sub' => 'Job pending / failed'],
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

        {{-- ── PENGGUNA & AKSES ─────────────────────────────────── --}}
        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-group-line"></i></span>
                <h6 class="dash-section-title">Pengguna & Akses</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="dash-grid">
                <div class="dash-span" style="--span-xl: 6; --span-md: 12;">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Distribusi User per Role</h4>
                        </div>
                        <div class="card-body">
                            <div id="chart-user-role" class="widget-chart-container"
                                 data-series="{{ json_encode([['name' => 'Jumlah User', 'data' => $roleData]]) }}"
                                 data-categories="{{ json_encode($roleLabels) }}"></div>
                        </div>
                    </div>
                </div>

                <div class="dash-span" style="--span-xl: 6; --span-md: 12;">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Role & Hak Akses</h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-table-scroll">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="text-muted fs-13">
                                            <th>Role</th>
                                            <th class="text-center">User</th>
                                            <th class="text-center">Permission</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($roleStats as $role)
                                            <tr>
                                                <td class="fw-medium">{{ $role->name }}</td>
                                                <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $role->users }}</span></td>
                                                <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $role->permissions }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="border-0 p-0">
                                                @include('dashboard._shared.widgets._empty', ['icon' => 'ri-shield-keyhole-line', 'title' => 'Belum ada role'])
                                            </td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dash-span" style="--span-xl: 6; --span-md: 12;">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">User Terbaru</h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-table-scroll">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="text-muted fs-13">
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Terdaftar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentUsers as $row)
                                            <tr>
                                                <td class="fw-medium">{{ $row->name }}</td>
                                                <td class="fs-13">{{ $row->email }}</td>
                                                <td><span class="badge bg-{{ $row->is_active ? 'success' : 'secondary' }}-subtle text-{{ $row->is_active ? 'success' : 'secondary' }}">{{ $row->is_active ? 'aktif' : 'nonaktif' }}</span></td>
                                                <td class="fs-13">{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->translatedFormat('d M Y') : '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="border-0 p-0">
                                                @include('dashboard._shared.widgets._empty', ['icon' => 'ri-user-line', 'title' => 'Belum ada user'])
                                            </td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dash-span" style="--span-xl: 6; --span-md: 12;">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">User Belum Memiliki Role</h4>
                            <span class="badge bg-danger-subtle text-danger">{{ $stats['users_no_role'] }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-table-scroll">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="text-muted fs-13">
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Terdaftar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($usersWithoutRole as $row)
                                            <tr>
                                                <td class="fw-medium">{{ $row->name }}</td>
                                                <td class="fs-13">{{ $row->email }}</td>
                                                <td class="fs-13">{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->translatedFormat('d M Y') : '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="border-0 p-0">
                                                @include('dashboard._shared.widgets._empty', ['icon' => 'ri-user-follow-line', 'title' => 'Semua user sudah memiliki role'])
                                            </td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── LOG AKTIVITAS ────────────────────────────────────── --}}
        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-history-line"></i></span>
                <h6 class="dash-section-title">Log Aktivitas</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="dash-grid">
                <div class="dash-span" style="--span-xl: 8; --span-md: 12;">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Aktivitas Sistem (7 Hari)</h4>
                            <span class="badge bg-primary-subtle text-primary">{{ number_format($stats['activity_today']) }} hari ini</span>
                        </div>
                        <div class="card-body">
                            <div id="chart-activity" class="widget-chart-container"
                                 data-series="{{ json_encode([['name' => 'Aktivitas', 'data' => $activityData]]) }}"
                                 data-categories="{{ json_encode($activityLabels) }}"></div>
                        </div>
                    </div>
                </div>

                <div class="dash-span" style="--span-xl: 4; --span-md: 6;">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Jenis Aktivitas</h4>
                        </div>
                        <div class="card-body">
                            @if (array_sum($activityEventData) > 0)
                                <div id="chart-activity-event" class="widget-chart-container"
                                     data-series="{{ json_encode($activityEventData) }}"
                                     data-labels="{{ json_encode($activityEventLabels) }}"></div>
                            @else
                                @include('dashboard._shared.widgets._empty', ['icon' => 'ri-bar-chart-box-line', 'title' => 'Belum ada aktivitas tercatat'])
                            @endif
                        </div>
                    </div>
                </div>

                <div class="dash-span" style="--span-xl: 6; --span-md: 12;">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Log Terbaru</h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="widget-table-scroll">
                                <ul class="list-group list-group-flush">
                                    @forelse ($recentActivities as $log)
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <p class="mb-1 fs-14">{{ $log->description ?: ($log->event ? ucfirst($log->event) : '—') }}</p>
                                                <small class="text-muted">{{ $log->log_name ?? 'default' }}</small>
                                            </div>
                                            <small class="text-muted text-end ms-2">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</small>
                                        </li>
                                    @empty
                                        <li class="list-group-item border-0 p-0">
                                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-history-line', 'title' => 'Belum ada log'])
                                        </li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dash-span" style="--span-xl: 6; --span-md: 12;">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Migrasi Terbaru</h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-table-scroll">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="text-muted fs-13">
                                            <th>Migrasi</th>
                                            <th class="text-center">Batch</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentMigrations as $migration)
                                            <tr>
                                                <td class="fs-13">{{ $migration->migration }}</td>
                                                <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">{{ $migration->batch }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="border-0 p-0">
                                                @include('dashboard._shared.widgets._empty', ['icon' => 'ri-database-2-line', 'title' => 'Belum ada migrasi'])
                                            </td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── RINGKASAN OPERASIONAL ────────────────────────────── --}}
        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-building-4-line"></i></span>
                <h6 class="dash-section-title">Ringkasan Operasional</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="dash-grid">
                @foreach ([
                    ['label' => 'Santri Aktif',     'value' => $stats['students_active'],    'icon' => 'ri-user-3-line',      'color' => 'primary', 'sub' => 'Seluruh unit'],
                    ['label' => 'GTK Terdata',      'value' => $stats['gtk_total'],          'icon' => 'ri-team-line',        'color' => 'success', 'sub' => 'Guru & tenaga kependidikan'],
                    ['label' => 'Rombel',           'value' => $stats['study_groups_total'], 'icon' => 'ri-school-line',      'color' => 'info',    'sub' => 'Kelompok belajar'],
                    ['label' => 'Unit Asrama',      'value' => $stats['dormitories_total'],  'icon' => 'ri-home-4-line',      'color' => 'warning', 'sub' => 'Asrama aktif'],
                    ['label' => 'Izin Pending',     'value' => $stats['permits_pending'],     'icon' => 'ri-mail-warning-line', 'color' => $stats['permits_pending'] > 0 ? 'danger' : 'success', 'sub' => 'Menunggu persetujuan'],
                    ['label' => 'Pelanggaran',      'value' => $stats['violations_total'],   'icon' => 'ri-alert-line',       'color' => 'secondary', 'sub' => 'Catatan asrama'],
                ] as $kpi)
                    <div class="dash-span" style="--span-xl: 4; --span-md: 6;">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <p class="dash-label">{{ $kpi['label'] }}</p>
                                        <h4 class="widget-stat-value mb-1">{{ number_format((float) $kpi['value']) }}</h4>
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

        {{-- ── AKSES CEPAT ──────────────────────────────────────── --}}
        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-links-line"></i></span>
                <h6 class="dash-section-title">Akses Cepat Sistem</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="dash-grid">
                @foreach ($quickLinks as $link)
                    <div class="dash-span" style="--span-xl: 4; --span-md: 6;">
                        <a href="{{ route($link['route']) }}" class="btn btn-soft-{{ $link['color'] }} w-100 py-3">
                            <i class="{{ $link['icon'] }} fs-4 d-block mb-2"></i>
                            <span class="fw-medium">{{ $link['label'] }}</span>
                        </a>
                    </div>
                @endforeach

                @if (\Illuminate\Support\Facades\Route::has('system.permits.index'))
                    <div class="dash-span" style="--span-xl: 4; --span-md: 6;">
                        <a href="{{ route('system.permits.index') }}" class="btn btn-soft-secondary w-100 py-3">
                            <i class="ri-file-list-2-line fs-4 d-block mb-2"></i>
                            <span class="fw-medium">Monitoring Izin</span>
                        </a>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.ApexCharts) return;

            var chartBar = function (id) {
                var el = document.getElementById(id);
                if (!el) return;
                new ApexCharts(el, {
                    series: JSON.parse(el.dataset.series),
                    chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
                    plotOptions: { bar: { horizontal: true, borderRadius: 4, columnWidth: '55%' } },
                    xaxis: { categories: JSON.parse(el.dataset.categories || '[]') },
                    colors: ['#405189'],
                    dataLabels: { enabled: true },
                    grid: { borderColor: '#f1f1f1' }
                }).render();
            };

            var chartArea = function (id) {
                var el = document.getElementById(id);
                if (!el) return;
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
            };

            var chartDonut = function (id) {
                var el = document.getElementById(id);
                if (!el) return;
                new ApexCharts(el, {
                    series: JSON.parse(el.dataset.series),
                    labels: JSON.parse(el.dataset.labels || '[]'),
                    chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
                    colors: ['#405189', '#0ab39c', '#f7b84b', '#f06548', '#299cdb', '#8b5cf6', '#6c757d', '#e83e8c'],
                    legend: { position: 'bottom' },
                    plotOptions: { pie: { donut: { size: '64%' } } },
                    dataLabels: { enabled: true }
                }).render();
            };

            chartArea('chart-activity');
            chartDonut('chart-activity-event');
            chartBar('chart-user-role');
        });
    </script>
@endpush
