@extends('layouts.master')
@section('title') Kedatangan Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Kedatangan Santri @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row g-3 mb-2">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-time-line fs-24 text-warning"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Belum Kembali</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['pending'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-login-box-line fs-24 text-success"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Kembali Hari Ini</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['today_returned'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-alarm-warning-line fs-24 text-danger"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Terlambat</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['overdue'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Kedatangan Santri</h5>
                            <p class="text-muted mb-0">{{ $dormitory->name }} — Tahun Ajaran {{ $activeYear->name ?? '-' }}</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.dormitory-returns.statistics', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-soft-secondary">
                                <i class="ri-bar-chart-2-line me-1"></i> Statistik
                            </a>
                            <a href="{{ route('user.asrama.permits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-primary">
                                <i class="ri-add-line me-1"></i> Izin Baru
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter Tabs --}}
                    <ul class="nav nav-pills nav-sm mb-3 gap-1">
                        @php
                            $tabs = [
                                'pending' => ['Belum Kembali', 'ri-time-line'],
                                'today'   => ['Kembali Hari Ini', 'ri-calendar-event-line'],
                                'overdue' => ['Terlambat', 'ri-alarm-warning-line'],
                                'all'     => ['Semua', 'ri-list-unordered'],
                            ];
                        @endphp
                        @foreach($tabs as $key => [$label, $icon])
                            <li class="nav-item">
                                <a href="{{ route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'filter' => $key]) }}"
                                   class="nav-link {{ $filter === $key ? 'active' : '' }}">
                                    <i class="{{ $icon }} me-1"></i>{{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Search --}}
                    <form method="GET" class="row g-2 mb-3">
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        <div class="col-md-6 col-lg-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="ri-search-line text-muted"></i></span>
                                <input type="text" name="search" class="form-control"
                                       placeholder="Cari nama siswa..." value="{{ request('search') }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-search-line"></i>
                                </button>
                            </div>
                        </div>
                        @if(request('search'))
                            <div class="col-auto">
                                <a href="{{ route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'filter' => $filter]) }}"
                                   class="btn btn-soft-secondary">
                                    <i class="ri-close-line me-1"></i>Reset
                                </a>
                            </div>
                        @endif
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">#</th>
                                    <th>Santri</th>
                                    <th>Jenis Izin</th>
                                    <th>Tujuan</th>
                                    <th>Berangkat</th>
                                    <th>Taksiran Kembali</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permits as $i => $permit)
                                    <tr>
                                        <td class="text-center">{{ $permits->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $permit->student?->name ?? '—' }}</div>
                                            @if($permit->room)
                                                <div class="text-muted small">Kamar: {{ $permit->room->name }}</div>
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
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if($permit->expected_return_datetime)
                                                <span class="small">{{ $permit->expected_return_datetime->format('d/m/Y') }}</span>
                                                <div class="text-muted small">{{ $permit->expected_return_datetime->format('H:i') }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($permit->status === 'returned')
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-check-line me-1"></i>Kembali
                                                </span>
                                            @elseif($permit->status === 'overdue')
                                                <span class="badge bg-danger">
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
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('user.asrama.permits.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}"
                                                   class="btn btn-soft-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @if(in_array($permit->status, ['approved', 'overdue']) && !$permit->actual_return_datetime)
                                                    <button type="button" class="ms-1 btn btn-soft-success"
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
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                            <h6 class="text-muted mb-1 mt-3">Tidak Ada Data</h6>
                                            <p class="text-muted mb-3 small">
                                                @if($filter === 'pending')
                                                    Tidak ada izin yang menunggu dicatat kepulangannya.
                                                @elseif($filter === 'today')
                                                    Belum ada kepulangan yang dicatat hari ini.
                                                @elseif($filter === 'overdue')
                                                    Tidak ada izin yang terlambat saat ini.
                                                @else
                                                    Belum ada data perizinan kepulangan.
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-pagination :paginator="$permits" />
                </div>
            </div>
        </div>
    </div>

    <x-modal id="returnModal" size="sm">
        @slot('title')<i class="ri-home-heart-line me-1"></i>Catat Kedatangan @endslot
        <form method="POST" id="returnModalForm">
            @csrf
            <div class="fw-semibold mb-2" id="returnModalStudent"></div>
            <small class="text-muted d-block mb-2" id="returnModalExpected"></small>
            <label class="form-label fw-semibold">Waktu Kembali <span class="text-danger">*</span></label>
            <input type="datetime-local" name="actual_return_datetime" class="form-control"
                   value="{{ now()->format('Y-m-d\TH:i') }}" required>
        </form>
        @slot('actions')
            <button type="submit" form="returnModalForm" class="btn btn-success">
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
