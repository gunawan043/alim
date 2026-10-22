@extends('layouts.master')
@section('title') Log Kunjungan Asrama @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id ?? request('dormitory_id')]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Kunjungan @endslot
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

    @php
        $totalVisit = ($stats['pending'] ?? 0) + ($stats['approved'] ?? 0) + ($stats['arrived'] ?? 0) + ($stats['checked_out'] ?? 0);
        $completionRate = $totalVisit > 0 ? round((($stats['checked_out'] ?? 0) / $totalVisit) * 100) : 0;
    @endphp

    {{-- Statistik ringkas: status mengikuti posisi santri --}}
    <div class="row">
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
                            <p class="stat-label mb-0">Menunggu Persetujuan</p>
                            <h3 class="stat-value">{{ number_format($stats['pending'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Perlu ditinjau pengasuh</p>
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
                            <p class="stat-label mb-0">Santri Belum Keluar</p>
                            <h3 class="stat-value">{{ number_format($stats['approved'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-notification-3-line me-1"></i>Menunggu dipanggil ke area kunjungan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-user-location-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Sedang dalam Kunjungan</p>
                            <h3 class="stat-value">{{ number_format($stats['arrived'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-walk-line me-1"></i>Santri sedang menemui tamu</p>
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
                            <p class="stat-label mb-0">Kunjungan Selesai</p>
                            <h3 class="stat-value">{{ number_format($stats['checked_out'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:5px;">
                            <div class="progress-bar bg-success" style="width:{{ $completionRate }}%"></div>
                        </div>
                        <span class="stat-note">{{ $completionRate }}%</span>
                    </div>
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
                            <h5 class="card-title mb-0">Log Kunjungan</h5>
                            <p class="text-muted mb-0 small">Daftar kunjungan keluarga dan status santri di {{ $dormitory->name ?? 'asrama' }}.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id ?? request('dormitory_id')]) }}" class="btn btn-outline-warning btn-sm" title="Pengaturan kuota dan kebijakan kunjungan">
                                    <i class="ri-settings-3-line me-1"></i> Pengaturan
                                </a>
                                <a href="{{ route('user.asrama.visits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id ?? request('dormitory_id')]) }}" class="btn btn-info btn-sm">
                                    <i class="ri-qr-scan-2-line me-1"></i> Scan QR
                                </a>
                                <a href="{{ route('user.asrama.visits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id ?? request('dormitory_id')]) }}" class="btn btn-primary btn-sm">
                                    <i class="ri-add-line me-1"></i> Ajukan Kunjungan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:520px;">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Cari nama tamu / santri..." value="{{ request('search') }}">
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
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#visitFilterModal">
                                <i class="ri-filter-3-line me-1"></i> Filter Lanjutan
                                @if(request()->hasAny(['status', 'start_date', 'end_date']))
                                    <span class="badge bg-light text-dark ms-1">Aktif</span>
                                @endif
                            </button>
                            <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id ?? request('dormitory_id')]) }}"
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
                                <span class="filter-badge active"><i class="ri-flag-line"></i> {{ ucfirst(str_replace('_', ' ', request('status'))) }}</span>
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
                            <strong>{{ $visits->count() }}</strong> dari
                            <strong>{{ $visits->total() }}</strong> kunjungan
                        </small>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Santri</th>
                                    <th>Tamu / Wali</th>
                                    <th>Hubungan</th>
                                    <th>Tujuan</th>
                                    <th>Jadwal</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visits ?? [] as $i => $visit)
                                    <tr>
                                        <td class="text-center text-muted">{{ $visits->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $visit->student?->name ?? '—' }}</div>
                                            <small class="text-muted">
                                                <i class="ri-home-4-line me-1"></i>{{ $visit->room?->name ?? 'Tanpa kamar' }}
                                                @if($visit->student?->nisn) · {{ $visit->student->nisn }} @endif
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $visit->visitor_name ?? '—' }}</div>
                                            @if($visit->visitor_phone)
                                                <small class="text-muted"><i class="ri-phone-line me-1"></i>{{ $visit->masked_visitor_phone }}</small>
                                            @elseif($visit->visitor_id_number)
                                                <small class="text-muted">{{ $visit->masked_visitor_id_number }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($visit->visitor_relationship === 'mahrom')
                                                <span class="badge bg-danger-subtle text-danger"><i class="ri-shield-star-line me-1"></i>Mahrom</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $visit->visitor_relationship_text }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $visit->purpose_text ?? '—' }}</td>
                                        <td>
                                            <div class="small">
                                                <div><i class="ri-calendar-line me-1 text-muted"></i>{{ $visit->expected_arrival?->format('d/m/Y') ?? '—' }}</div>
                                                <div><i class="ri-time-line me-1 text-muted"></i>{{ $visit->expected_arrival?->format('H:i') ?? '—' }}</div>
                                                @if($visit->check_in_at)
                                                    <div class="text-success"><i class="ri-login-box-line me-1"></i>Keluar: {{ $visit->check_in_at->format('H:i') }}</div>
                                                @endif
                                                @if($visit->check_out_at)
                                                    <div class="text-info"><i class="ri-logout-box-line me-1"></i>Kembali: {{ $visit->check_out_at->format('H:i') }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">{!! $visit->status_badge !!}</td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.visits.card', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id, 'visitUuid' => $visit->id]) }}"
                                                   target="_blank" class="btn btn-sm btn-outline-secondary" title="Cetak Kartu Kunjungan">
                                                    <i class="ri-printer-line"></i>
                                                </a>
                                                <a href="{{ route('user.asrama.visits.show', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id, 'visitUuid' => $visit->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @if($visit->status === 'approved')
                                                    <form method="POST" action="{{ route('user.asrama.visits.check-in', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id, 'visitUuid' => $visit->id]) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Catat Santri Keluar">
                                                            <i class="ri-login-box-line"></i>
                                                        </button>
                                                    </form>
                                                @elseif($visit->status === 'arrived')
                                                    <form method="POST" action="{{ route('user.asrama.visits.check-out', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id, 'visitUuid' => $visit->id]) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Catat Santri Kembali">
                                                            <i class="ri-logout-box-r-line"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" title="Belum ada data kunjungan"
                                        message="Belum ada kunjungan yang tercatat untuk asrama ini.">
                                        <a href="{{ route('user.asrama.visits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id ?? request('dormitory_id')]) }}" class="btn btn-primary btn-sm mt-3">
                                            <i class="ri-add-line me-1"></i> Ajukan Kunjungan Baru
                                        </a>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($visits->hasPages())
                        @include('shared._pagination', ['paginator' => $visits])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal filter lanjutan --}}
    <div class="modal fade" id="visitFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id ?? request('dormitory_id')]) }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Kunjungan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama tamu atau santri...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-flag-line"></i>Status Santri</div>
                                    <select name="status" class="form-select">
                                        <option value="">Semua status</option>
                                        @foreach([
                                            'pending' => 'Menunggu Persetujuan',
                                            'approved' => 'Santri Belum Keluar',
                                            'arrived' => 'Sedang dalam Kunjungan',
                                            'checked_out' => 'Kunjungan Selesai',
                                            'rejected' => 'Ditolak',
                                        ] as $value => $label)
                                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
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
                        <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id ?? request('dormitory_id')]) }}" class="btn btn-light">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i> Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
