@extends('layouts.master')

@section('title', 'Developer Tools — Konsol Sistem')

@section('css')
    @include('dashboard._shared._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Konsol Sistem @endslot
        @slot('title') Developer Tools @endslot
    @endcomponent

    <div class="dashboard-role" data-role="super-admin">
        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-code-s-slash-line"></i></span>
                <h6 class="dash-section-title">Statistik Route</h6>
                <span class="dash-section-line"></span>
                <span class="dash-section-count">{{ array_sum($routesByMethod) }} route</span>
            </div>

            <div class="dash-grid">
                @foreach ([
                    ['label' => 'GET', 'value' => $routesByMethod['GET'], 'color' => 'success'],
                    ['label' => 'POST', 'value' => $routesByMethod['POST'], 'color' => 'primary'],
                    ['label' => 'PUT', 'value' => $routesByMethod['PUT'], 'color' => 'info'],
                    ['label' => 'PATCH', 'value' => $routesByMethod['PATCH'], 'color' => 'warning'],
                    ['label' => 'DELETE', 'value' => $routesByMethod['DELETE'], 'color' => 'danger'],
                    ['label' => 'Lainnya', 'value' => $routesByMethod['OTHER'], 'color' => 'secondary'],
                ] as $stat)
                    <div class="dash-span" style="--span-xl: 2; --span-md: 4;">
                        <div class="card card-animate">
                            <div class="card-body text-center py-3">
                                <span class="badge bg-{{ $stat['color'] }}-subtle text-{{ $stat['color'] }} mb-2">{{ $stat['label'] }}</span>
                                <h4 class="widget-stat-value mb-0">{{ number_format((float) $stat['value']) }}</h4>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-route-line"></i></span>
                <h6 class="dash-section-title">Prefix Route Terbanyak</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="row g-3">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive widget-table-scroll">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="text-muted fs-13">
                                            <th>Prefix</th>
                                            <th class="text-center">Jumlah Route</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topPrefixes as $prefix => $count)
                                            <tr>
                                                <td><code>{{ $prefix }}</code></td>
                                                <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $count }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="card-title">Ekstensi PHP ({{ count($extensions) }})</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-1" style="max-height: 260px; overflow-y: auto;">
                                @foreach ($extensions as $extension)
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $extension }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-terminal-box-line"></i></span>
                <h6 class="dash-section-title">Perintah Artisan Berguna</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="row g-3">
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="text-muted fs-13">
                                            <th>Perintah</th>
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
                </div>

                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="card-title">Migrasi Terakhir</h4>
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
    </div>
@endsection
