@extends('layouts.master')
@section('title') Pelanggaran Asrama @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Pelanggaran @endslot
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
                            <span class="avatar-title bg-dark-subtle rounded">
                                <i class="ri-error-warning-line text-dark"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Pelanggaran</p>
                            <h3 class="stat-value">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-file-list-line me-1"></i>Seluruh catatan pelanggaran</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded">
                                <i class="ri-information-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Ringan</p>
                            <h3 class="stat-value">{{ number_format($stats['ringan'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-shield-check-line me-1"></i>Kategori pelanggaran ringan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-alert-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Sedang</p>
                            <h3 class="stat-value">{{ number_format($stats['sedang'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-alarm-warning-line me-1"></i>Kategori pelanggaran sedang</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded">
                                <i class="ri-goblet-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Berat</p>
                            <h3 class="stat-value">{{ number_format($stats['berat'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-error-warning-line me-1"></i>Kategori pelanggaran berat</p>
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
                            <h5 class="card-title mb-0">Daftar Pelanggaran</h5>
                            <p class="text-muted mb-0 small">
                                {{ $dormitory->name ?? 'Asrama' }} &mdash; Tahun Ajaran {{ $activeYear->name ?? '—' }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.violations.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="ri-add-line me-1"></i> Catat Pelanggaran
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
                        <form method="GET" id="violationSearchForm" class="d-flex gap-2 flex-grow-1" style="max-width:520px;">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Cari nama / jenis pelanggaran..." value="{{ request('search') }}">
                            @foreach(['violation_category', 'start_date', 'end_date'] as $filterKey)
                                @if(request($filterKey))
                                    <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                                @endif
                            @endforeach
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="ri-search-line"></i> Cari
                            </button>
                        </form>

                        <div class="toolbar-actions">
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#violationFilterModal">
                                <i class="ri-filter-3-line me-1"></i> Filter Lanjutan
                                @if(request()->hasAny(['violation_category', 'start_date', 'end_date']))
                                    <span class="badge bg-light text-dark ms-1">Aktif</span>
                                @endif
                            </button>
                            <a href="{{ route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light btn-sm" title="Reset filter">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </div>
                    </div>

                    @if(request()->hasAny(['search','violation_category','start_date','end_date']))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('violation_category'))
                                <span class="filter-badge active"><i class="ri-flag-line"></i> {{ ucfirst(request('violation_category')) }}</span>
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
                            <strong>{{ $violations->count() }}</strong> dari
                            <strong>{{ $violations->total() }}</strong> pelanggaran
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
                                    <th>Santri</th>
                                    <th>Kamar</th>
                                    <th>Kategori</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th class="text-center">Poin</th>
                                    <th>Tanggal</th>
                                    <th>Tindakan</th>
                                    <th>Notifikasi Wali</th>
                                    <th class="text-center" style="width:80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($violations ?? [] as $i => $v)
                                    <tr>
                                        <td class="text-center text-muted">{{ $violations->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $v->student?->name ?? '—' }}</div>
                                            @if($v->student?->nisn)
                                                <div class="text-muted small">NISN: {{ $v->student->nisn }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($v->room)
                                                <span class="badge bg-info-subtle text-info">
                                                    <i class="ri-home-4-line me-1"></i>{{ $v->room->name }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($v->violation_category === 'ringan')
                                                <span class="badge bg-info-subtle text-info">Ringan</span>
                                            @elseif($v->violation_category === 'sedang')
                                                <span class="badge bg-warning-subtle text-warning">Sedang</span>
                                            @elseif($v->violation_category === 'berat')
                                                <span class="badge bg-danger-subtle text-danger">Berat</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $v->violation_category }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $v->violation_type ?: '—' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-danger-subtle text-danger">{{ $v->points }}</span>
                                        </td>
                                        <td>
                                            @if($v->violation_date)
                                                {{ $v->violation_date->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ Str::limit($v->action_taken, 35) }}</span>
                                        </td>
                                        <td>
                                            @if($v->parent_notified_at)
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-checkbox-circle-line me-1"></i>Terkirim
                                                </span>
                                                <div class="text-muted small mt-1">{{ $v->parent_notified_at->format('d/m/Y H:i') }}</div>
                                            @else
                                                <form method="POST"
                                                      action="{{ route('user.asrama.violations.notify', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'violationUuid' => $v->id]) }}"
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning"
                                                            onclick="return confirm('Kirim notifikasi ke wali murid?')"
                                                            title="Kirim Notifikasi Wali">
                                                        <i class="ri-notification-3-line"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.violations.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'violationUuid' => $v->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="10" title="Belum ada pelanggaran"
                                        message="Belum ada data pelanggaran yang tercatat.">
                                        <a href="{{ route('user.asrama.violations.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                           class="btn btn-primary btn-sm mt-3">
                                            <i class="ri-add-line me-1"></i> Catat Pelanggaran Baru
                                        </a>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($violations->hasPages())
                        @include('shared._pagination', ['paginator' => $violations->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL FILTER LANJUTAN
    ============================================================ --}}
    <div class="modal fade" id="violationFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" id="violationFilterForm" action="{{ route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Pelanggaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama / jenis pelanggaran...">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-flag-line"></i>Kategori</div>
                                    <select name="violation_category" class="form-select">
                                        <option value="">Semua Kategori</option>
                                        <option value="ringan" {{ request('violation_category') == 'ringan' ? 'selected' : '' }}>Ringan</option>
                                        <option value="sedang" {{ request('violation_category') == 'sedang' ? 'selected' : '' }}>Sedang</option>
                                        <option value="berat"  {{ request('violation_category') == 'berat'  ? 'selected' : '' }}>Berat</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-calendar-line"></i>Dari Tanggal</div>
                                    <input type="date" name="start_date" id="filter_start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-calendar-line"></i>Sampai Tanggal</div>
                                    <input type="date" name="end_date" id="filter_end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i> Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('violationFilterForm');
    const startInput = document.getElementById('filter_start_date');
    const endInput = document.getElementById('filter_end_date');

    if (!form || !startInput || !endInput) return;

    startInput.addEventListener('change', function () {
        if (this.value) {
            endInput.min = this.value;
            if (endInput.value && endInput.value < this.value) {
                endInput.value = '';
                Toastify({ text: 'Tanggal akhir diubah agar tidak lebih kecil dari tanggal mulai.', duration: 3000, gravity: 'top', position: 'right', backgroundColor: '#ffc107', stopOnFocus: true }).showToast();
            }
        } else {
            endInput.removeAttribute('min');
        }
    });

    form.addEventListener('submit', function (e) {
        if (startInput.value && endInput.value && endInput.value < startInput.value) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Tanggal tidak valid', text: 'Tanggal akhir tidak boleh lebih kecil dari tanggal mulai.', confirmButtonColor: '#405189' });
        }
    });

    if (startInput.value) {
        endInput.min = startInput.value;
    }
});
</script>
@endpush