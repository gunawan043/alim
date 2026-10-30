@extends('layouts.master')
@section('title') Daftar Santri Asrama @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Daftar Santri @endslot
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
            <i class="ri-error-warning-line me-1"></i> Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ============================================================
         STATS CARDS (mengikuti template visits)
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-user-follow-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Santri di Asrama</p>
                            <h3 class="stat-value">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Santri terdaftar pada asrama ini</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-home-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Kamar Terisi</p>
                            <h3 class="stat-value">
                                {{ number_format($stats['occupied_rooms'] ?? 0) }}<span class="fs-5 text-muted">/{{ number_format($stats['total_rooms'] ?? 0) }}</span>
                            </h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-door-open-line me-1"></i>Kamar yang telah dihuni santri</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-user-location-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Di Asrama</p>
                            <h3 class="stat-value">{{ number_format($stats['in_dormitory'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-home-heart-line me-1"></i>Santri sedang berada di asrama</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded">
                                <i class="ri-route-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Sedang Izin Pulang</p>
                            <h3 class="stat-value">{{ number_format($stats['on_permit'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-notification-3-line me-1"></i>Santri dengan izin pulang aktif</p>
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
                            <h5 class="card-title mb-0">Daftar Santri Asrama</h5>
                            <p class="text-muted mb-0 small">
                                {{ $dormitory->name ?? 'Asrama' }} &mdash; {{ number_format($stats['total'] ?? 0) }} santri terdaftar.
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-outline-info btn-sm" title="Lihat Absensi Asrama">
                                    <i class="ri-calendar-check-line me-1"></i> Absensi
                                </a>
                                <a href="{{ route('user.asrama.residents.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="ri-add-line me-1"></i> Tempatkan Santri
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
                                   placeholder="Cari nama / NISN santri..." value="{{ request('search') }}">
                            @foreach(['room_id', 'status'] as $filterKey)
                                @if(request($filterKey))
                                    <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                                @endif
                            @endforeach
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="ri-search-line"></i> Cari
                            </button>
                        </form>

                        <div class="toolbar-actions">
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#residentFilterModal">
                                <i class="ri-filter-3-line me-1"></i> Filter Lanjutan
                                @if(request()->hasAny(['room_id', 'status']))
                                    <span class="badge bg-light text-dark ms-1">Aktif</span>
                                @endif
                            </button>
                            <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light btn-sm" title="Reset filter">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </div>
                    </div>

                    @if(request()->hasAny(['search','room_id','status']))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('room_id'))
                                <span class="filter-badge active"><i class="ri-home-4-line"></i> {{ $rooms->firstWhere('id', request('room_id'))?->name ?? 'Kamar #'.request('room_id') }}</span>
                            @endif
                            @if(request('status'))
                                <span class="filter-badge active"><i class="ri-flag-line"></i> {{ ucfirst(request('status')) }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $residents->count() }}</strong> dari
                            <strong>{{ $residents->total() }}</strong> santri
                        </small>
                    </div>

                    {{-- ============================================================
                         TABEL
                    ============================================================ --}}
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Nama Santri</th>
                                    <th style="width:12%">NISN</th>
                                    <th style="width:12%">Ruang / Kamar</th>
                                    <th class="text-center" style="width:7%">Tempat Tidur</th>
                                    <th class="text-center" style="width:10%">Status</th>
                                    <th style="width:14%">Keluar</th>
                                    <th class="text-center" style="width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($residents ?? [] as $i => $resident)
                                    @php
                                        $permit = $activePermits->get($resident->student_id);
                                        $isOverdue = $permit && $permit->status === 'overdue';
                                        $expectedReturn = $permit?->expected_return_datetime;
                                    @endphp
                                    <tr class="{{ $isOverdue ? 'table-warning' : '' }}">
                                        <td class="text-center text-muted">{{ $residents->firstItem() + $i }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-3">
                                                    <div class="avatar-title rounded-circle bg-{{ $resident->student?->gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $resident->student?->gender === 'P' ? 'danger' : 'primary' }} fw-bold fs-10">
                                                        {{ strtoupper(substr($resident->student?->name ?? '?', 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="fw-semibold">{{ $resident->student?->name ?? '—' }}</span>
                                                    @if($resident->student?->nisn)
                                                        <br><small class="text-muted">{{ $resident->student->nisn }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td><code class="text-dark">{{ $resident->student?->nisn ?? '—' }}</code></td>
                                        <td>
                                            <span class="badge bg-primary text-light">
                                                <i class="ri-home-4-line me-1"></i>{{ $resident->room?->name ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">#{{ $resident->bed_number }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($permit)
                                                <span class="badge bg-{{ $isOverdue ? 'danger' : 'warning' }}-subtle text-{{ $isOverdue ? 'danger' : 'warning' }}"
                                                      title="Kembali: {{ $expectedReturn?->format('d M Y H:i') }}">
                                                    <i class="ri-{{ $isOverdue ? 'alarm-warning-line' : 'route-line' }} me-1"></i>
                                                    {{ $isOverdue ? 'Terlambat' : 'Izin Pulang' }}
                                                </span>
                                                @if($expectedReturn)
                                                    <div class="small text-muted mt-1">{{ $expectedReturn->format('d M H:i') }}</div>
                                                @endif
                                            @elseif($resident->is_active)
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-home-heart-line me-1"></i>Di Asrama
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    <i class="ri-logout-box-r-line me-1"></i>Keluar
                                                </span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if(! $resident->is_active)
                                                <div class="text-muted">{{ $resident->check_out_date?->format('d/m/Y') ?? '—' }}</div>
                                                <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst(str_replace('_', ' ', (string) $resident->check_out_reason)) }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.residents.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentUuid' => $resident->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail Santri">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @if($resident->is_active)
                                                    <form method="POST"
                                                          action="{{ route('user.asrama.residents.checkout', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentUuid' => $resident->id]) }}"
                                                          class="d-inline"
                                                          onsubmit="return confirm('Keluarkan penghuni ini dari asrama? Data tetap tersimpan sebagai riwayat.');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Check-out">
                                                            <i class="ri-logout-box-r-line"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" title="Belum ada penghuni"
                                        message="Belum ada santri yang ditempatkan di asrama ini pada filter tersebut.">
                                        <a href="{{ route('user.asrama.residents.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                           class="btn btn-primary btn-sm mt-3">
                                            <i class="ri-add-line me-1"></i> Tempatkan Santri
                                        </a>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($residents->hasPages())
                        @include('shared._pagination', ['paginator' => $residents->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL FILTER LANJUTAN (pola template)
    ============================================================ --}}
    <div class="modal fade" id="residentFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Santri</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama / NISN santri...">
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
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-flag-line"></i>Status Penghuni</div>
                                    <select name="status" class="form-select">
                                        <option value="aktif" {{ ($status ?? 'aktif') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                                        <option value="keluar" {{ ($status ?? '') === 'keluar' ? 'selected' : '' }}>Keluar</option>
                                        <option value="semua" {{ ($status ?? '') === 'semua' ? 'selected' : '' }}>Semua</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i> Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection