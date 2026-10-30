@extends('layouts.master')
@section('title') Kedatangan Santri @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Kedatangan Santri @endslot
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
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-time-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Belum Kembali</p>
                            <h3 class="stat-value">{{ number_format($stats['pending'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Izin aktif belum kembali</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-login-box-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Kembali Hari Ini</p>
                            <h3 class="stat-value">{{ number_format($stats['today_returned'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-check-double-line me-1"></i>Sudah dicatat kembali hari ini</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
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
                    <p class="stat-note mb-0"><i class="ri-error-warning-line me-1"></i>Melewati taksiran kembali</p>
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
                            <h5 class="card-title mb-0">Daftar Kedatangan Santri</h5>
                            <p class="text-muted mb-0 small">
                                {{ $dormitory->name ?? 'Asrama' }} &mdash; Tahun Ajaran {{ $activeYear->name ?? '—' }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.dormitory-returns.statistics', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-outline-secondary btn-sm" title="Lihat Statistik Kepulangan">
                                    <i class="ri-bar-chart-2-line me-1"></i> Statistik
                                </a>
                                <a href="{{ route('user.asrama.permits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="ri-add-line me-1"></i> Izin Baru
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         FILTER TABS
                    ============================================================ --}}
                    @php
                        $tabs = [
                            'pending' => ['Belum Kembali', 'ri-time-line', $stats['pending'] ?? 0],
                            'today'   => ['Kembali Hari Ini', 'ri-calendar-event-line', $stats['today_returned'] ?? 0],
                            'overdue' => ['Terlambat', 'ri-alarm-warning-line', $stats['overdue'] ?? 0],
                            'all'     => ['Semua', 'ri-list-unordered', null],
                        ];
                    @endphp
                    <ul class="nav nav-pills nav-sm mb-3 gap-1">
                        @foreach($tabs as $key => [$label, $icon, $count])
                            <li class="nav-item">
                                <a href="{{ route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'filter' => $key]) }}"
                                   class="nav-link {{ $filter === $key ? 'active' : '' }}">
                                    <i class="{{ $icon }} me-1"></i>{{ $label }}
                                    @if($count !== null)
                                        <span class="badge bg-light text-dark ms-1">{{ number_format($count) }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    {{-- ============================================================
                         TOOLBAR PENCARIAN (pola template)
                    ============================================================ --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:520px;">
                            <input type="hidden" name="filter" value="{{ $filter }}">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Cari nama santri..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="ri-search-line"></i> Cari
                            </button>
                        </form>

                        <div class="toolbar-actions">
                            @if(request('search'))
                                <a href="{{ route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'filter' => $filter]) }}"
                                   class="btn btn-light btn-sm" title="Reset pencarian">
                                    <i class="ri-refresh-line"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if(request('search'))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $permits->count() }}</strong> dari
                            <strong>{{ $permits->total() }}</strong> data
                        </small>
                    </div>

                    {{-- ============================================================
                         TABEL
                    ============================================================ --}}
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">#</th>
                                    <th>Santri</th>
                                    <th>Jenis Izin</th>
                                    <th>Tujuan</th>
                                    <th>Berangkat</th>
                                    <th>Taksiran Kembali</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permits ?? [] as $i => $permit)
                                    <tr>
                                        <td class="text-center text-muted">{{ $permits->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $permit->student?->name ?? '—' }}</div>
                                            @if($permit->room)
                                                <div class="text-muted small"><i class="ri-home-4-line me-1"></i>Kamar: {{ $permit->room->name }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">{{ $permit->permit_type_text }}</span>
                                        </td>
                                        <td>{{ $permit->destination ?: '—' }}</td>
                                        <td>
                                            @if($permit->departure_datetime)
                                                <span class="small">{{ $permit->departure_datetime->format('d/m/Y') }}</span>
                                                <div class="text-muted small">{{ $permit->departure_datetime->format('H:i') }}</div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($permit->expected_return_datetime)
                                                <span class="small">{{ $permit->expected_return_datetime->format('d/m/Y') }}</span>
                                                <div class="text-muted small">{{ $permit->expected_return_datetime->format('H:i') }}</div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($permit->status === 'returned')
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-check-line me-1"></i>Kembali
                                                </span>
                                            @elseif($permit->status === 'overdue')
                                                <span class="badge bg-danger-subtle text-danger">
                                                    <i class="ri-alarm-warning-line me-1"></i>Terlambat
                                                </span>
                                            @elseif($permit->status === 'approved')
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ri-time-line me-1"></i>Belum Kembali
                                                </span>
                                            @elseif($permit->status === 'rejected')
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    <i class="ri-close-line me-1"></i>Ditolak
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted">{{ $permit->status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.permits.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @if(in_array($permit->status, ['approved', 'overdue']) && !$permit->actual_return_datetime)
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#returnModal"
                                                            data-permit-id="{{ $permit->id }}"
                                                            data-permit-name="{{ $permit->student?->name }}"
                                                            data-permit-expected="{{ $permit->expected_return_datetime?->format('d/m/Y H:i') ?? '—' }}"
                                                            title="Catat Kedatangan">
                                                        <i class="ri-login-box-line"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" title="Tidak ada data"
                                        message="{{ $filter === 'pending' ? 'Tidak ada izin yang menunggu dicatat kepulangannya.' : ($filter === 'today' ? 'Belum ada kepulangan yang dicatat hari ini.' : ($filter === 'overdue' ? 'Tidak ada izin yang terlambat saat ini.' : 'Belum ada data perizinan kepulangan.')) }}" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($permits->hasPages())
                        @include('shared._pagination', ['paginator' => $permits->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL: Catat Kedatangan (x-modal dipertahankan)
    ============================================================ --}}
    <x-modal id="returnModal" size="sm">
        @slot('title')<i class="ri-home-heart-line me-1"></i>Catat Kedatangan @endslot
        <form method="POST" id="returnModalForm">
            @csrf
            <div class="fw-semibold mb-2" id="returnModalStudent"></div>
            <small class="text-muted d-block mb-2" id="returnModalExpected"></small>
            <label class="form-label fs-12 text-muted mb-1">Waktu Kembali <span class="text-danger">*</span></label>
            <input type="datetime-local" name="actual_return_datetime" class="form-control"
                   value="{{ now()->format('Y-m-d\TH:i') }}" required>
        </form>
        @slot('actions')
            <button type="submit" form="returnModalForm" class="btn btn-primary">
                <i class="ri-check-line me-1"></i>Simpan
            </button>
        @endslot
    </x-modal>
@endsection

@section('script')
<script>
const RETURN_RECORD_URL = '{{ route('user.asrama.dormitory-returns.record', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => '__ID__']) }}';

document.getElementById('returnModal')?.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    if (!button || !button.dataset.permitId) return;

    document.getElementById('returnModalStudent').textContent = button.dataset.permitName || '';
    document.getElementById('returnModalExpected').textContent = 'Estimasi kembali: ' + (button.dataset.permitExpected || '—');
    document.getElementById('returnModalForm').action = RETURN_RECORD_URL.replace('__ID__', button.dataset.permitId);
});
</script>
@endsection