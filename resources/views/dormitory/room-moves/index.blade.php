@extends('layouts.master')
@section('title') Mutasi Kamar — Asrama @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') {{ $dormitory->name ?? 'Asrama' }} @endslot
        @slot('title') Mutasi Kamar @endslot
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
         STATS CARDS (3 status)
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-time-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Menunggu</p>
                            <h3 class="stat-value">{{ number_format($stats['pending'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Perlu tindakan admin</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Disetujui</p>
                            <h3 class="stat-value">{{ number_format($stats['approved'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-shield-check-line me-1"></i>Berlaku valid</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded">
                                <i class="ri-close-circle-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Ditolak</p>
                            <h3 class="stat-value">{{ number_format($stats['rejected'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-close-line me-1"></i>Tidak disetujui</p>
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
                            <h5 class="card-title mb-0">Mutasi Kamar</h5>
                            <p class="text-muted mb-0 small">
                                {{ $dormitory->name ?? 'Asrama' }} &mdash; {{ number_format($roomMoves->total() ?? 0) }} permohonan
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.room-moves.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="ri-add-line me-1"></i> Ajukan Mutasi
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
                            @foreach(['status', 'start_date', 'end_date'] as $filterKey)
                                @if(request($filterKey))
                                    <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                                @endif
                            @endforeach
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="ri-search-line"></i> Cari
                            </button>
                        </form>

                        <div class="toolbar-actions">
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#roomMoveFilterModal">
                                <i class="ri-filter-3-line me-1"></i> Filter Lanjutan
                                @if(request()->hasAny(['status', 'start_date', 'end_date']))
                                    <span class="badge bg-light text-dark ms-1">Aktif</span>
                                @endif
                            </button>
                            <a href="{{ route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light btn-sm" title="Reset filter">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </div>
                    </div>

                    @if(request()->hasAny(['search','status','start_date','end_date']))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('status'))
                                <span class="filter-badge active"><i class="ri-flag-line"></i> {{ ucfirst(request('status')) }}</span>
                            @endif
                            @if(request('start_date'))
                                <span class="filter-badge active"><i class="ri-calendar-line"></i> ≥ {{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }}</span>
                            @endif
                            @if(request('end_date'))
                                <span class="filter-badge active"><i class="ri-calendar-line"></i> ≤ {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $roomMoves->count() }}</strong> dari
                            <strong>{{ $roomMoves->total() }}</strong> permohonan
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
                                    <th style="width:14%">Kamar Asal</th>
                                    <th style="width:14%">Kamar Tujuan</th>
                                    <th style="width:11%">Tanggal Pindah</th>
                                    <th style="width:11%">Jenis</th>
                                    <th class="text-center" style="width:11%">Status</th>
                                    <th class="text-center" style="width:80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roomMoves ?? [] as $i => $move)
                                    <tr>
                                        <td class="text-center text-muted">{{ $roomMoves->firstItem() + $i }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-3">
                                                    <div class="avatar-title rounded-circle
                                                        bg-{{ $move->resident?->student?->gender === 'P' ? 'danger' : 'primary' }}-subtle
                                                        text-{{ $move->resident?->student?->gender === 'P' ? 'danger' : 'primary' }}
                                                        fw-bold fs-10">
                                                        {{ strtoupper(substr($move->resident?->student?->name ?? '?', 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="fw-semibold">{{ $move->resident?->student?->name ?? '—' }}</span>
                                                    @if($move->resident?->student?->nisn)
                                                        <br><small class="text-muted">{{ $move->resident->student->nisn }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="ri-home-4-line me-1"></i>{{ $move->fromRoom?->name ?? '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="ri-arrow-right-line me-1"></i>{{ $move->toRoom?->name ?? '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($move->move_date)
                                                {{ $move->move_date->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                {{ ucfirst($move->move_type ?? '') }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($move->status === 'pending')
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ri-time-line me-1"></i>Menunggu
                                                </span>
                                            @elseif($move->status === 'approved')
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-check-circle-line me-1"></i>Disetujui
                                                </span>
                                            @elseif($move->status === 'rejected')
                                                <span class="badge bg-danger-subtle text-danger">
                                                    <i class="ri-close-circle-line me-1"></i>Ditolak
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    {{ ucfirst($move->status ?? '') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.room-moves.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomMoveUuid' => $move->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" title="Belum ada data mutasi"
                                        message="Tidak ada permohonan mutasi kamar yang tercatat.">
                                        <a href="{{ route('user.asrama.room-moves.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                           class="btn btn-primary btn-sm mt-3">
                                            <i class="ri-add-line me-1"></i> Ajukan Mutasi Baru
                                        </a>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($roomMoves->hasPages())
                        @include('shared._pagination', ['paginator' => $roomMoves->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL FILTER LANJUTAN (pola template)
    ============================================================ --}}
    <div class="modal fade" id="roomMoveFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Mutasi Kamar</h5>
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
                                    <div class="filter-group-title"><i class="ri-flag-line"></i>Status</div>
                                    <select name="status" class="form-select">
                                        <option value="">Semua Status</option>
                                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-calendar-line"></i>Dari Tanggal</div>
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-calendar-line"></i>Sampai Tanggal</div>
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i> Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection