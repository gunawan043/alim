@extends('layouts.master')

@section('title', 'Rencana Kunjungan')
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid ?? $dormitories->first()?->id]) }}">Kunjungan</a> @endslot
        @slot('title') Rencana Kunjungan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

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
                                <i class="ri-calendar-event-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Jadwal</p>
                            <h3 class="stat-value">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Kunjungan dalam rentang</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded">
                                <i class="ri-time-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Akan Datang</p>
                            <h3 class="stat-value">{{ number_format($stats['upcoming'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-notification-3-line me-1"></i>Jadwal belum berlangsung</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Sudah Check-in</p>
                            <h3 class="stat-value">{{ number_format($stats['arrived'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-login-box-line me-1"></i>Tamu sudah tiba</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded">
                                <i class="ri-user-unfollow-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">No-Show</p>
                            <h3 class="stat-value">{{ number_format($stats['no_show'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-error-warning-line me-1"></i>Tidak hadir tanpa kabar</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         CARD UTAMA
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Rencana Kunjungan</h5>
                            <p class="text-muted mb-0 small">Jadwal kunjungan wali &amp; keluarga ke asrama.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid ?? $dormitories->first()?->id]) }}"
                                   class="btn btn-outline-secondary btn-sm" title="Kembali ke Kunjungan">
                                    <i class="ri-arrow-left-line me-1"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         FILTER CEPAT (inline)
                    ============================================================ --}}
                    <form method="GET" class="row g-2 align-items-end mb-3">
                        <div class="col-md-3">
                            <label class="form-label fs-12 text-muted mb-1">Dari</label>
                            <input type="date" name="start" class="form-control form-control-sm" value="{{ $start->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 text-muted mb-1">Sampai</label>
                            <input type="date" name="end" class="form-control form-control-sm" value="{{ $end->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 text-muted mb-1">Asrama</label>
                            <select name="dormitory_id" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach($dormitories as $d)
                                    <option value="{{ $d->id }}" {{ $selectedDorm == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="ri-filter-line me-1"></i> Terapkan
                            </button>
                            <a href="{{ route('user.calendar.visit.index', ['userId' => $userId]) }}"
                               class="btn btn-light btn-sm" title="Reset filter">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </div>
                    </form>

                    {{-- ============================================================
                         DAFTAR PER TANGGAL
                    ============================================================ --}}
                    @forelse($grouped as $date => $items)
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="ri-calendar-event-line me-1"></i>
                                {{ \Carbon\Carbon::parse($date)->isoFormat('dddd, D MMMM Y') }}
                                <span class="badge bg-primary-subtle text-primary ms-2">{{ $items->count() }} kunjungan</span>
                            </h6>
                            <div class="table-container">
                                <table class="table table-hover align-middle table-freeze mb-0">
                                    <thead>
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Pengunjung</th>
                                            <th>Santri</th>
                                            <th>Asrama / Kamar</th>
                                            <th>Keperluan</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center" style="width:80px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $v)
                                            <tr>
                                                <td class="small">
                                                    <strong>{{ $v->expected_arrival_datetime->format('H:i') }}</strong>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $v->visitor_name }}</div>
                                                    <small class="text-muted">{{ $v->visitor_relationship }}</small>
                                                </td>
                                                <td>
                                                    {{ $v->student->nama ?? '—' }}
                                                    <br><small class="text-muted">{{ $v->student->nis ?? '—' }}</small>
                                                </td>
                                                <td>
                                                    {{ $v->dormitory->name ?? '—' }}
                                                    <br><small class="text-muted">Kamar {{ $v->room->kode ?? $v->room->nomor ?? '—' }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary-subtle text-primary">{{ $v->purpose }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $statusMap = [
                                                            'arrived' => ['success', 'Check-in', 'ri-login-box-line'],
                                                            'checked_out' => ['secondary', 'Selesai', 'ri-logout-box-r-line'],
                                                            'approved' => ['info', 'Disetujui', 'ri-checkbox-circle-line'],
                                                            'pending' => ['warning', 'Menunggu', 'ri-time-line'],
                                                            'rejected' => ['danger', 'Ditolak', 'ri-close-circle-line'],
                                                            'cancelled' => ['danger', 'Dibatalkan', 'ri-close-circle-line'],
                                                            'no_show' => ['dark', 'No-Show', 'ri-user-unfollow-line'],
                                                        ];
                                                        [$bg, $label, $icon] = $statusMap[$v->status] ?? ['secondary', ucfirst(str_replace('_',' ',$v->status)), 'ri-flag-line'];
                                                    @endphp
                                                    <span class="badge bg-{{ $bg }}-subtle text-{{ $bg }}">
                                                        <i class="{{ $icon }} me-1"></i>{{ $label }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-1 justify-content-center">
                                                        <a href="{{ route('user.calendar.visit.show', ['userId' => $userId, 'id' => $v->id]) }}"
                                                           class="btn btn-sm btn-outline-primary" title="Detail Kunjungan">
                                                            <i class="ri-eye-line"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <x-empty-state colspan="7" title="Tidak ada jadwal kunjungan"
                            message="Tidak ada jadwal kunjungan dalam rentang waktu ini." />
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection