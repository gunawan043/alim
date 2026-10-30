@extends('layouts.master')
@section('title') Statistik Kepulangan @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Statistik Kepulangan @endslot
    @endcomponent

    {{-- ============================================================
         INFO PERIODE
    ============================================================ --}}
    <div class="row mb-2">
        <div class="col-12">
            <div class="alert alert-info d-flex align-items-center mb-0" role="alert">
                <i class="ri-calendar-event-line fs-4 me-3"></i>
                <div>
                    <strong>Statistik bulan ini</strong>
                    &mdash; {{ now()->translatedFormat('F Y') }} di {{ $dormitory->name ?? 'Asrama' }}
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-file-list-3-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Izin Bulan Ini</p>
                            <h3 class="stat-value">{{ number_format($stats['total_this_month'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Seluruh pengajuan izin</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-home-heart-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Sudah Kembali</p>
                            <h3 class="stat-value">{{ number_format($stats['returned_count'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-check-line me-1"></i>Santri telah kembali ke asrama</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-time-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Menunggu Approval</p>
                            <h3 class="stat-value">{{ number_format($stats['pending_count'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-notification-3-line me-1"></i>Perlu ditinjau pengasuh</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded">
                                <i class="ri-alarm-warning-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Terlambat Kembali</p>
                            <h3 class="stat-value">{{ number_format($stats['overdue_count'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-error-warning-line me-1"></i>Melewati batas waktu izin</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         RINGKASAN JENIS IZIN & TOP SANTRI
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-pie-chart-line me-2 text-primary"></i>Izin Berdasarkan Jenis</h5>
                            <p class="text-muted mb-0 small">Distribusi pengajuan izin pulang pada periode ini.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze mb-0">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th class="text-end" style="width:30%;">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stats['by_type'] ?? [] as $type => $count)
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="ri-file-text-line me-1"></i>{{ Str::ucfirst(str_replace('_', ' ', $type)) }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold">{{ number_format($count) }}</td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="2" title="Belum ada data izin"
                                        message="Tidak ada pengajuan izin pada bulan ini." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-user-star-line me-2 text-primary"></i>Top 5 Santri Terbanyak Izin</h5>
                            <p class="text-muted mb-0 small">Santri dengan frekuensi izin pulang tertinggi.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze mb-0">
                            <thead>
                                <tr>
                                    <th style="width:50px;" class="text-center">#</th>
                                    <th>Santri</th>
                                    <th class="text-end" style="width:30%;">Izin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stats['top_students'] ?? [] as $idx => $entry)
                                    @php
                                        $rankColor = ['success', 'primary', 'info', 'secondary', 'secondary'][$idx] ?? 'secondary';
                                    @endphp
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $rankColor }}-subtle text-{{ $rankColor }} fw-bold">
                                                {{ $idx + 1 }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $entry->student->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-dark-subtle text-dark">{{ number_format($entry->total ?? 0) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="3" title="Belum ada data santri"
                                        message="Tidak ada data santri dengan izin pada bulan ini." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         FOOTER NAVIGASI
    ============================================================ --}}
    <div class="d-flex justify-content-start mt-3">
        <a href="{{ route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
           class="btn btn-outline-secondary btn-sm" title="Kembali ke Kedatangan Santri">
            <i class="ri-arrow-left-line me-1"></i> Kembali ke Kedatangan Santri
        </a>
    </div>
@endsection