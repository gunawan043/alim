@extends('layouts.master')
@section('title') Wali Kamar — {{ $dormitory->name ?? 'Asrama' }} @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Wali Kamar @endslot
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
         STATS CARDS (3 kartu)
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-user-star-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Wali Kamar Aktif</p>
                            <h3 class="stat-value">{{ number_format($stats['total_active'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-shield-user-line me-1"></i>Wali kamar yang sedang bertugas</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-door-open-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Kamar</p>
                            <h3 class="stat-value">{{ number_format($stats['total_rooms'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-home-4-line me-1"></i>Seluruh kamar pada asrama</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-shield-user-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Kamar Terjaga</p>
                            <h3 class="stat-value">
                                {{ number_format($stats['rooms_with_supervisor'] ?? 0) }}<span class="fs-5 text-muted">/{{ number_format($stats['total_rooms'] ?? 0) }}</span>
                            </h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-check-double-line me-1"></i>Kamar yang memiliki wali</p>
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
                            <h5 class="card-title mb-0"><i class="ri-shield-user-line me-1 text-primary"></i>Daftar Wali Kamar</h5>
                            <p class="text-muted mb-0 small">Wali kamar yang ditetapkan untuk {{ $dormitory->name ?? 'asrama ini' }}.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.room-supervisors.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="ri-user-add-line me-1"></i> Tetapkan Wali Kamar
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
                                   placeholder="Cari nama / kode kamar..." value="{{ request('search') }}">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="ri-search-line"></i> Cari
                            </button>
                        </form>

                        <div class="toolbar-actions">
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#supervisorFilterModal">
                                <i class="ri-filter-3-line me-1"></i> Filter Lanjutan
                                @if(request('status'))
                                    <span class="badge bg-light text-dark ms-1">Aktif</span>
                                @endif
                            </button>
                            <a href="{{ route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light btn-sm" title="Reset filter">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </div>
                    </div>

                    @if(request()->hasAny(['search','status']))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('status'))
                                <span class="filter-badge active"><i class="ri-flag-line"></i> {{ ucfirst(request('status')) }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $supervisors->count() }}</strong> dari
                            <strong>{{ $supervisors->total() }}</strong> wali kamar
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
                                    <th>Wali Kamar</th>
                                    <th>Kamar</th>
                                    <th>Blok</th>
                                    <th>Periode</th>
                                    <th>SK</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($supervisors ?? [] as $i => $s)
                                    <tr>
                                        <td class="text-center text-muted">{{ $supervisors->firstItem() + $i }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-3">
                                                    <div class="avatar-title rounded-circle bg-primary-subtle text-primary fw-bold fs-10">
                                                        {{ strtoupper(substr($s->user->name ?? '?', 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <a href="{{ route('user.asrama.room-supervisors.profile', ['userId' => $userId, 'supervisorUserUuid' => $s->user_id]) }}"
                                                       class="fw-semibold text-body">{{ $s->user->name ?? '—' }}</a>
                                                    <br><small class="text-muted">{{ $s->user->email ?? '' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark-subtle text-dark">{{ $s->room->code ?? '—' }}</span>
                                            <small class="d-block text-muted">{{ $s->room->name ?? '' }}</small>
                                        </td>
                                        <td>{{ $s->room?->wing?->name ?? '—' }}</td>
                                        <td>
                                            <small class="d-block">{{ $s->start_date?->format('d M Y') ?? '—' }}</small>
                                            <small class="text-muted">s/d {{ $s->end_date?->format('d M Y') ?? 'Sekarang' }}</small>
                                        </td>
                                        <td>
                                            @if($s->decree)
                                                <small>{{ $s->decree->number ?? $s->decree->id }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusMap = [
                                                    'active' => ['success', 'Aktif', 'ri-checkbox-circle-line'],
                                                    'inactive' => ['warning', 'Non-aktif', 'ri-pause-circle-line'],
                                                    'ended' => ['secondary', 'Berakhir', 'ri-logout-box-r-line'],
                                                ];
                                                [$bg, $label, $icon] = $statusMap[$s->status] ?? ['secondary', ucfirst($s->status), 'ri-flag-line'];
                                            @endphp
                                            <span class="badge bg-{{ $bg }}-subtle text-{{ $bg }}">
                                                <i class="{{ $icon }} me-1"></i>{{ $label }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.room-supervisors.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'supervisorUuid' => $s->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" title="Belum ada Wali Kamar"
                                        message="Belum ada Wali Kamar yang ditetapkan untuk asrama ini.">
                                        <a href="{{ route('user.asrama.room-supervisors.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                           class="btn btn-primary btn-sm mt-3">
                                            <i class="ri-user-add-line me-1"></i> Tetapkan Wali Kamar Pertama
                                        </a>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($supervisors->hasPages())
                        @include('shared._pagination', ['paginator' => $supervisors->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL FILTER LANJUTAN
    ============================================================ --}}
    <div class="modal fade" id="supervisorFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Wali Kamar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama / kode kamar...">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-flag-line"></i>Status</div>
                                    <select name="status" class="form-select">
                                        <option value="">Semua Status</option>
                                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-aktif</option>
                                        <option value="ended" {{ request('status') === 'ended' ? 'selected' : '' }}>Berakhir</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i> Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection