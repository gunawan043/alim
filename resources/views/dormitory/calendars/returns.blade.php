@extends('layouts.master')

@section('title', 'Rencana Kepulangan')
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid ?? $dormitories->first()?->id]) }}">Perizinan</a> @endslot
        @slot('title') Rencana Kepulangan @endslot
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
                            <p class="stat-label mb-0">Total Rencana</p>
                            <h3 class="stat-value">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Izin pulang dalam rentang</p>
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
                            <p class="stat-label mb-0">Tepat Waktu</p>
                            <h3 class="stat-value">{{ number_format($stats['on_time'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-time-line me-1"></i>Kembali sesuai jadwal</p>
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
                            <p class="stat-label mb-0">Terlambat</p>
                            <h3 class="stat-value">{{ number_format($stats['overdue'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-error-warning-line me-1"></i>Melewati batas waktu</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-hourglass-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Belum Kembali</p>
                            <h3 class="stat-value">{{ number_format($stats['pending'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Lewat jadwal, belum tercatat kembali</p>
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
                            <h5 class="card-title mb-0">Rencana Kepulangan Santri</h5>
                            <p class="text-muted mb-0 small">Pantau kepulangan berdasarkan izin pulang.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid ?? $dormitories->first()?->id]) }}"
                                   class="btn btn-outline-secondary btn-sm" title="Kembali ke Perizinan">
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
                            <a href="{{ route('user.calendar.return.index', ['userId' => $userId]) }}"
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
                                <span class="badge bg-primary-subtle text-primary ms-2">{{ $items->count() }} rencana</span>
                            </h6>
                            <div class="table-container">
                                <table class="table table-hover align-middle table-freeze mb-0">
                                    <thead>
                                        <tr>
                                            <th>Santri</th>
                                            <th>Asrama / Kamar</th>
                                            <th>Tgl Kembali</th>
                                            <th>Aktual</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center" style="width:80px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $p)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $p->student->nama ?? '—' }}</div>
                                                    <small class="text-muted">{{ $p->student->nis ?? '—' }}</small>
                                                </td>
                                                <td>
                                                    {{ $p->dormitory->name ?? '—' }}
                                                    <br><small class="text-muted">Kamar {{ $p->room->kode ?? $p->room->nomor ?? '—' }}</small>
                                                </td>
                                                <td class="small">
                                                    {{ $p->expected_return_datetime->format('H:i') }}
                                                </td>
                                                <td class="small">
                                                    @if($p->actual_return_datetime)
                                                        <span class="text-success">{{ $p->actual_return_datetime->format('d M H:i') }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $statusMap = [
                                                            'returned' => ['success', 'Kembali', 'ri-check-line'],
                                                            'approved' => $p->expected_return_datetime->isPast()
                                                                ? ['warning', 'Lewat Jadwal', 'ri-hourglass-line']
                                                                : ['info', 'Disetujui', 'ri-checkbox-circle-line'],
                                                            'overdue' => ['danger', 'Terlambat', 'ri-alarm-warning-line'],
                                                        ];
                                                        [$bg, $label, $icon] = $statusMap[$p->status] ?? ['secondary', ucfirst($p->status), 'ri-flag-line'];
                                                    @endphp
                                                    <span class="badge bg-{{ $bg }}-subtle text-{{ $bg }}">
                                                        <i class="{{ $icon }} me-1"></i>{{ $label }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-1 justify-content-center">
                                                        <a href="{{ route('user.calendar.return.show', ['userId' => $userId, 'id' => $p->id]) }}"
                                                           class="btn btn-sm btn-outline-primary" title="Detail">
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
                        <x-empty-state colspan="6" title="Tidak ada rencana kepulangan"
                            message="Tidak ada rencana kepulangan dalam rentang waktu ini." />
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection