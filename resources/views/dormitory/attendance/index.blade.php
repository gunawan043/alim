@extends('layouts.master')
@section('title') Absensi Asrama @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Absensi @endslot
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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> Terjadi kesalahan. Silakan coba lagi.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ============================================================
         STATS CARDS (6 status, gaya template)
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Hadir</p>
                            <h3 class="stat-value">{{ number_format($stats['hadir'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-user-smile-line me-1"></i>Santri hadir</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-flight-takeoff-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Izin</p>
                            <h3 class="stat-value">{{ number_format($stats['izin'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Dengan izin resmi</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded">
                                <i class="ri-heart-pulse-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Sakit</p>
                            <h3 class="stat-value">{{ number_format($stats['sakit'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-first-aid-kit-line me-1"></i>Dalam perawatan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded">
                                <i class="ri-prohibited-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Alpa</p>
                            <h3 class="stat-value">{{ number_format($stats['alpa'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-alert-line me-1"></i>Tanpa keterangan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-secondary-subtle rounded">
                                <i class="ri-logout-box-r-line text-secondary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Pulang</p>
                            <h3 class="stat-value">{{ number_format($stats['pulang'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-home-4-line me-1"></i>Izin pulang</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-dark-subtle rounded">
                                <i class="ri-team-line text-dark"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Penghuni</p>
                            <h3 class="stat-value">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-group-line me-1"></i>Total penghuni asrama</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Absensi Asrama</h5>
                            <p class="text-muted mb-0 small">
                                {{ $dormitory->name ?? 'Asrama' }} &mdash;
                                {{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('d M Y') : 'Semua Tanggal' }}
                                {{ $selectedSession ? ' — Sesi ' . ucfirst($selectedSession) : '' }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.attendance.recap', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-outline-info btn-sm" title="Lihat Rekap Bulanan">
                                    <i class="ri-bar-chart-line me-1"></i> Rekap Bulanan
                                </a>
                                <a href="{{ route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}?date={{ $selectedDate ?? now()->toDateString() }}&session={{ $selectedSession ?? 'pagi' }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="ri-add-line me-1"></i> Catat Absensi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         TOOLBAR PENCARIAN + FILTER (pola template)
                    ============================================================ --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:520px;">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Cari nama santri..." value="{{ request('search') }}">
                            @foreach(['attendance_date', 'session', 'room_id'] as $filterKey)
                                @if(request($filterKey))
                                    <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                                @endif
                            @endforeach
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="ri-search-line"></i> Cari
                            </button>
                        </form>

                        <div class="toolbar-actions">
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#attendanceFilterModal">
                                <i class="ri-filter-3-line me-1"></i> Filter Lanjutan
                                @if(request()->hasAny(['attendance_date', 'session', 'room_id']))
                                    <span class="badge bg-light text-dark ms-1">Aktif</span>
                                @endif
                            </button>
                            <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light btn-sm" title="Reset filter">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </div>
                    </div>

                    @if(request()->hasAny(['search','attendance_date','session','room_id']))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('attendance_date'))
                                <span class="filter-badge active"><i class="ri-calendar-line"></i> {{ \Carbon\Carbon::parse(request('attendance_date'))->format('d/m/Y') }}</span>
                            @endif
                            @if(request('session'))
                                <span class="filter-badge active"><i class="ri-time-line"></i> Sesi {{ ucfirst(request('session')) }}</span>
                            @endif
                            @if(request('room_id'))
                                <span class="filter-badge active"><i class="ri-home-4-line"></i> {{ $rooms->firstWhere('id', request('room_id'))?->name ?? 'Kamar #'.request('room_id') }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $attendanceRecords->count() }}</strong> dari
                            <strong>{{ $attendanceRecords->total() }}</strong> catatan absensi
                        </small>
                    </div>

                    {{-- ============================================================
                         TABEL ABSENSI
                    ============================================================ --}}
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Nama Santri</th>
                                    <th style="width:12%">Kamar</th>
                                    <th class="text-center" style="width:10%">Bed</th>
                                    <th class="text-center" style="width:12%">Status</th>
                                    <th style="width:10%">Sesi</th>
                                    <th style="width:20%">Catatan</th>
                                    <th style="width:15%">Recorder</th>
                                    <th class="text-center" style="width:80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendanceRecords ?? [] as $i => $record)
                                    <tr class="{{ $record->status === 'alpa' ? 'table-danger' : ($record->status === 'izin' ? 'table-warning' : '') }}">
                                        <td class="text-center text-muted">{{ $attendanceRecords->firstItem() + $i }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $record->resident?->student?->name ?? '—' }}</span>
                                            @if($record->resident?->student?->nisn)
                                                <br><small class="text-muted">{{ $record->resident->student->nisn }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                <i class="ri-home-4-line me-1"></i>{{ $record->resident?->room?->name ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">#{{ $record->resident?->bed_number ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            @switch($record->status)
                                                @case('hadir')
                                                    <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line me-1"></i>Hadir</span>
                                                    @break
                                                @case('izin')
                                                    <span class="badge bg-warning-subtle text-warning"><i class="ri-flight-takeoff-line me-1"></i>Izin</span>
                                                    @break
                                                @case('sakit')
                                                    <span class="badge bg-info-subtle text-info"><i class="ri-heart-pulse-line me-1"></i>Sakit</span>
                                                    @break
                                                @case('alpa')
                                                    <span class="badge bg-danger-subtle text-danger"><i class="ri-prohibited-line me-1"></i>Alpa</span>
                                                    @break
                                                @case('pulang')
                                                    <span class="badge bg-secondary-subtle text-secondary"><i class="ri-logout-box-r-line me-1"></i>Pulang</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-light text-muted">—</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <span class="badge bg-dark-subtle text-dark">{{ ucfirst($record->session) }}</span>
                                        </td>
                                        <td>
                                            <span class="small text-muted">{{ Str::limit($record->notes ?? '—', 50) }}</span>
                                        </td>
                                        <td>
                                            <span class="small text-muted">{{ $record->recorder?->name ?? '—' }}</span>
                                            <br><span class="text-muted small">{{ $record->created_at?->format('d/m H:i') }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.attendance.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'attendanceUuid' => $record->id]) }}"
                                                   class="btn btn-sm btn-outline-secondary" title="Edit Absensi">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="9" title="Belum ada data absensi"
                                        message="Belum ada catatan absensi untuk filter tersebut.">
                                        <a href="{{ route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}?date={{ $selectedDate ?? now()->toDateString() }}"
                                           class="btn btn-primary btn-sm mt-3">
                                            <i class="ri-add-line me-1"></i> Catat Absensi
                                        </a>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($attendanceRecords->hasPages())
                        @include('shared._pagination', ['paginator' => $attendanceRecords->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL FILTER LANJUTAN (pola template)
    ============================================================ --}}
    <div class="modal fade" id="attendanceFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Absensi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama santri...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-calendar-line"></i>Tanggal</div>
                                    <input type="date" name="attendance_date" class="form-control" value="{{ $selectedDate ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-time-line"></i>Sesi</div>
                                    <select name="session" class="form-select">
                                        <option value="">Semua Sesi</option>
                                        <option value="pagi" {{ ($selectedSession ?? '') === 'pagi' ? 'selected' : '' }}>Pagi</option>
                                        <option value="siang" {{ ($selectedSession ?? '') === 'siang' ? 'selected' : '' }}>Siang</option>
                                        <option value="sore" {{ ($selectedSession ?? '') === 'sore' ? 'selected' : '' }}>Sore</option>
                                        <option value="malam" {{ ($selectedSession ?? '') === 'malam' ? 'selected' : '' }}>Malam</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-home-4-line"></i>Kamar</div>
                                    <select name="room_id" class="form-select">
                                        <option value="">Semua Kamar</option>
                                        @foreach($rooms as $room)
                                            <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                                {{ $room->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i> Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection