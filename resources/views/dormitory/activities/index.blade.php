@extends('layouts.master')

@section('title') Kegiatan Asrama @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Kegiatan Asrama @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i> {{ session('success') }}
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
                            <p class="stat-label mb-0">Total Kegiatan</p>
                            <h3 class="stat-value">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Seluruh kegiatan asrama</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-play-circle-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Sedang Dibuka</p>
                            <h3 class="stat-value">{{ number_format($stats['open'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-door-open-line me-1"></i>Presensi sedang berlangsung</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-secondary-subtle rounded">
                                <i class="ri-lock-line text-secondary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Ditutup</p>
                            <h3 class="stat-value">{{ number_format($stats['closed'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-check-double-line me-1"></i>Presensi telah selesai</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded">
                                <i class="ri-user-follow-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Baris Presensi</p>
                            <h3 class="stat-value">{{ number_format($stats['attendance'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-file-list-line me-1"></i>Total entri presensi</p>
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
                            <h5 class="card-title mb-0"><i class="ri-list-check me-1 text-primary"></i>Daftar Kegiatan</h5>
                            <p class="text-muted mb-0 small">Kegiatan dibuat Kepala/Wakil Asrama; presensi diisi Staf Asrama dan Wali Kamar.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                @if($canManage)
                                    <a href="{{ route('user.asrama.kegiatan.schedules', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                       class="btn btn-outline-primary btn-sm" title="Kegiatan Rutin">
                                        <i class="ri-repeat-line me-1"></i> Kegiatan Rutin
                                    </a>
                                    <a href="{{ route('user.asrama.kegiatan.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                       class="btn btn-primary btn-sm">
                                        <i class="ri-add-line me-1"></i> Tambah Kegiatan
                                    </a>
                                @endif
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
                            <input type="text" name="q" class="form-control form-control-sm"
                                   placeholder="Cari nama kegiatan..." value="{{ request('q') }}">
                            @foreach(['date', 'status'] as $filterKey)
                                @if(request($filterKey))
                                    <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                                @endif
                            @endforeach
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="ri-search-line"></i> Cari
                            </button>
                        </form>

                        <div class="toolbar-actions">
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#activityFilterModal">
                                <i class="ri-filter-3-line me-1"></i> Filter Lanjutan
                                @if(request()->hasAny(['date', 'status']))
                                    <span class="badge bg-light text-dark ms-1">Aktif</span>
                                @endif
                            </button>
                            <a href="{{ route('user.asrama.kegiatan.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light btn-sm" title="Reset filter">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </div>
                    </div>

                    @if(request()->hasAny(['q','date','status']))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('q'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('q') }}"</span>
                            @endif
                            @if(request('date'))
                                <span class="filter-badge active"><i class="ri-calendar-line"></i> {{ \Carbon\Carbon::parse(request('date'))->format('d/m/Y') }}</span>
                            @endif
                            @if(request('status'))
                                <span class="filter-badge active"><i class="ri-flag-line"></i> {{ request('status') === 'open' ? 'Dibuka' : 'Ditutup' }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $activities->count() }}</strong> dari
                            <strong>{{ $activities->total() }}</strong> kegiatan
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
                                    <th>Nama Kegiatan</th>
                                    <th>Tanggal / Waktu</th>
                                    <th>Lokasi</th>
                                    <th class="text-center">Hadir</th>
                                    <th class="text-center">Alpa</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activities ?? [] as $i => $activity)
                                    <tr>
                                        <td class="text-center text-muted">{{ $activities->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $activity->name }}</div>
                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit((string) $activity->description, 60) }}</div>
                                        </td>
                                        <td class="small">
                                            {{ $activity->activity_date?->translatedFormat('d M Y') }}
                                            @if($activity->start_time)
                                                <div class="text-muted">{{ substr($activity->start_time, 0, 5) }}@if($activity->end_time)–{{ substr($activity->end_time, 0, 5) }}@endif</div>
                                            @endif
                                        </td>
                                        <td class="small">{{ $activity->location ?: '—' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success">{{ $activity->hadir_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger-subtle text-danger">{{ $activity->alpa_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $activity->isOpen() ? 'success' : 'secondary' }}-subtle text-{{ $activity->isOpen() ? 'success' : 'secondary' }}">
                                                <i class="ri-{{ $activity->isOpen() ? 'play-circle-line' : 'lock-line' }} me-1"></i>
                                                {{ $activity->isOpen() ? 'Dibuka' : 'Ditutup' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.kegiatan.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'activityId' => $activity->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @if($canRecord && $activity->isOpen())
                                                    <a href="{{ route('user.asrama.kegiatan.attendance', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'activityId' => $activity->id]) }}"
                                                       class="btn btn-sm btn-outline-info" title="Isi Presensi">
                                                        <i class="ri-user-follow-line"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" title="Belum ada kegiatan"
                                        message="Kepala/Wakil Asrama dapat menambahkan kegiatan, lalu staf/wali kamar mengisi presensinya.">
                                        @if($canManage)
                                            <a href="{{ route('user.asrama.kegiatan.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                               class="btn btn-primary btn-sm mt-3">
                                                <i class="ri-add-line me-1"></i> Tambah Kegiatan
                                            </a>
                                        @endif
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($activities->hasPages())
                        @include('shared._pagination', ['paginator' => $activities->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL FILTER LANJUTAN
    ============================================================ --}}
    <div class="modal fade" id="activityFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('user.asrama.kegiatan.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Kegiatan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama kegiatan...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-calendar-line"></i>Tanggal</div>
                                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-flag-line"></i>Status</div>
                                    <select name="status" class="form-select">
                                        <option value="">Semua</option>
                                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Dibuka</option>
                                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Ditutup</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('user.asrama.kegiatan.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i> Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection