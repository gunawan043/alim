@extends('layouts.master')

@section('title', 'Feature Activation — Konsol Sistem')

@section('css')
    @include('dashboard._shared._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Konsol Sistem @endslot
        @slot('title') Feature Activation @endslot
    @endcomponent

    <div class="dashboard-role" data-role="super-admin">
        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon"><i class="ri-dashboard-3-line"></i></span>
                <h6 class="dash-section-title">Dashboard per Role</h6>
                <span class="dash-section-line"></span>
                <span class="dash-section-count">{{ count($roleDashboards) }} role</span>
            </div>

            <div class="dash-grid">
                @foreach ($roleDashboards as $dash)
                    @php
                        $available = \Illuminate\Support\Facades\Route::has($dash['route']);
                        $params = str_starts_with($dash['route'], 'user.') ? ['userId' => auth()->id()] : [];
                    @endphp
                    <div class="dash-span" style="--span-xl: 3; --span-md: 6;">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <p class="dash-label">{{ $dash['role'] }}</p>
                                        <h5 class="fw-semibold mb-2">{{ $dash['label'] }}</h5>
                                        @if ($available)
                                            <a href="{{ route($dash['route'], $params) }}" class="btn btn-sm btn-soft-{{ $dash['color'] }}">
                                                <i class="ri-external-link-line me-1"></i>Buka
                                            </a>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">route belum ada</span>
                                        @endif
                                    </div>
                                    <span class="avatar-title bg-{{ $dash['color'] }}-subtle flex-shrink-0">
                                        <i class="{{ $dash['icon'] }} text-{{ $dash['color'] }}"></i>
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
                <span class="dash-section-icon"><i class="ri-apps-2-line"></i></span>
                <h6 class="dash-section-title">Status Modul (berdasarkan skema database)</h6>
                <span class="dash-section-line"></span>
            </div>

            <div class="dash-grid">
                @foreach ($modules as $module)
                    @php
                        $badge = match ($module['status']) {
                            'aktif'    => 'success',
                            'sebagian' => 'warning',
                            default    => 'secondary',
                        };
                        $label = match ($module['status']) {
                            'aktif'    => 'Aktif',
                            'sebagian' => 'Sebagian',
                            default    => 'Belum ada skema',
                        };
                    @endphp
                    <div class="dash-span" style="--span-xl: 4; --span-md: 6;">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <h6 class="fw-semibold mb-0">{{ $module['label'] }}</h6>
                                    <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $label }}</span>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-{{ $badge }}" style="width: {{ $module['total'] > 0 ? round($module['found'] / $module['total'] * 100) : 0 }}%"></div>
                                </div>
                                <p class="mb-0 text-muted fs-13">
                                    {{ $module['found'] }}/{{ $module['total'] }} tabel tersedia
                                </p>
                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    @foreach ($module['tables'] as $table)
                                        <code class="fs-11 {{ \Illuminate\Support\Facades\Schema::hasTable($table) ? 'text-success' : 'text-muted text-decoration-line-through' }}">{{ $table }}</code>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
