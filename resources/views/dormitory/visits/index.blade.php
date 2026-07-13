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

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card card-animate h-100 border border-warning-subtle">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-time-line text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Pending</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['pending'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <small class="text-muted">Menunggu persetujuan</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card card-animate h-100 border border-success-subtle">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Approved</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['approved'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <small class="text-muted">Disetujui</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card card-animate h-100 border border-info-subtle">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="ri-user-location-line text-info"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Arrived</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['arrived'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <small class="text-muted">Sudah tiba</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card card-animate h-100 border border-secondary-subtle">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-secondary-subtle rounded fs-2">
                                <i class="ri-logout-box-line text-secondary"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Checked Out</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['checked_out'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <small class="text-muted">Selesai</small>
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
                            <h5 class="card-title mb-0">Log Kunjungan</h5>
                            <p class="text-muted mb-0">Daftar kunjungan tamu ke asrama.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.visits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id ?? request('dormitory_id')]) }}"
                               class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Ajukan Kunjungan
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter Form --}}
                    <form method="GET" class="row g-3 mb-4">
                        @if(!isset($dormitory))
                        <div class="col-md-3">
                            <select name="dormitory_id" class="form-control">
                                <option value="">Semua Asrama</option>
                                @foreach($dormitories ?? [] as $d)
                                    <option value="{{ $d->id }}" {{ request('dormitory_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="arrived" {{ request('status') === 'arrived' ? 'selected' : '' }}>Arrived</option>
                                <option value="checked_out" {{ request('status') === 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama tamu / santri..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="ri-search-line"></i> Filter</button>
                            <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id ?? request('dormitory_id')]) }}"
                               class="btn btn-light">Reset</a>
                        </div>
                    </form>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Santri</th>
                                    <th>Tamu / Visitor</th>
                                    <th>Hubungan</th>
                                    <th>Tujuan</th>
                                    <th>Waktu Datang</th>
                                    <th class="text-center">Durasi</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visits ?? [] as $i => $visit)
                                    <tr>
                                        <td class="text-center">{{ ($visits->currentPage() - 1) * $visits->perPage() + $i + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $visit->student?->name ?? '—' }}</div>
                                            @if($visit->student?->nisn)
                                                <small class="text-muted">NISN: {{ $visit->student->nisn }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $visit->visitor_name ?? '—' }}</div>
                                            @if($visit->visitor_id_number)
                                                <small class="text-muted">{{ $visit->visitor_id_number }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($visit->visitor_relationship === 'mahrom')
                                                <span class="badge bg-danger-subtle text-danger">
                                                    <i class="ri-shield-star-line me-1"></i>Mahrom
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst(str_replace('_', ' ', $visit->visitor_relationship ?? '—')) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-dark">{{ $visit->purpose_text ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div><i class="ri-calendar-line me-1 text-muted"></i>{{ $visit->expected_arrival?->format('d/m/Y') ?? '—' }}</div>
                                                <div><i class="ri-time-line me-1 text-muted"></i>{{ $visit->expected_arrival?->format('H:i') ?? '—' }}</div>
                                                @if($visit->actual_arrival_at)
                                                    <div class="text-success small">
                                                        <i class="ri-check-line me-1"></i>Actual: {{ $visit->actual_arrival_at->format('H:i') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($visit->expected_duration_minutes)
                                                <span class="badge bg-light text-dark">{{ $visit->expected_duration_minutes }} menit</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {!! $visit->status_badge !!}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.asrama.visits.show', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id, 'visitUuid' => $visit->id]) }}"
                                               class="btn btn-sm btn-outline-primary me-1" title="Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            @if($visit->status === 'approved')
                                                <form method="POST"
                                                      action="{{ route('user.asrama.visits.checkin', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id, 'visitUuid' => $visit->id]) }}"
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Check-in">
                                                        <i class="ri-login-box-line"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if($visit->status === 'arrived')
                                                <form method="POST"
                                                      action="{{ route('user.asrama.visits.checkout', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id, 'visitUuid' => $visit->id]) }}"
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Check-out">
                                                        <i class="ri-logout-box-r-line"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            <i class="ri-user-follow-line fs-1 d-block mb-2"></i>
                                            Belum ada data kunjungan.
                                            <br>
                                            <a href="{{ route('user.asrama.visits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id ?? request('dormitory_id')]) }}"
                                               class="btn btn-sm btn-primary mt-2">
                                                <i class="ri-add-line"></i> Ajukan Kunjungan Baru
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(isset($visits) && $visits->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted small">Menampilkan {{ $visits->firstItem() ?? 0 }} - {{ $visits->lastItem() ?? 0 }} dari {{ $visits->total() }} data</div>
                            <div>{{ $visits->withQueryString()->links() }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection