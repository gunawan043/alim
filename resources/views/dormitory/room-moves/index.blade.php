@extends('layouts.master')
@section('title') Mutasi Kamar — Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') {{ $dormitory->name ?? 'Asrama' }} @endslot
        @slot('title') Mutasi Kamar @endslot
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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row mb-4">
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md rounded-circle bg-warning-subtle">
                                <i class="ri-time-line fs-24 text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-muted text-truncate mb-1">Menunggu</p>
                            <h4 class="mb-0">{{ $stats['pending'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md rounded-circle bg-success-subtle">
                                <i class="ri-checkbox-circle-line fs-24 text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-muted text-truncate mb-1">Disetujui</p>
                            <h4 class="mb-0">{{ $stats['approved'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md rounded-circle bg-danger-subtle">
                                <i class="ri-close-circle-line fs-24 text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-muted text-truncate mb-1">Ditolak</p>
                            <h4 class="mb-0">{{ $stats['rejected'] ?? 0 }}</h4>
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
                            <h5 class="card-title mb-0">Mutasi Kamar</h5>
                            <p class="text-muted mb-0">
                                {{ $dormitory->name ?? 'Asrama' }} &mdash; {{ $roomMoves->total() ?? 0 }} permohonan
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.room-moves.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Ajukan Mutasi
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         FILTER FORM
                    ============================================================ --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="start_date" class="form-control"
                                   value="{{ request('start_date') }}" placeholder="Dari Tanggal">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="end_date" class="form-control"
                                   value="{{ request('end_date') }}" placeholder="Sampai Tanggal">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Nama Santri..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light w-100">
                                <i class="ri-reset-right-line"></i> Reset
                            </a>
                        </div>
                    </form>

                    {{-- ============================================================
                         TABLE
                    ============================================================ --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 5%">No</th>
                                    <th>Nama Santri</th>
                                    <th style="width: 14%">Kamar Asal</th>
                                    <th style="width: 14%">Kamar Tujuan</th>
                                    <th style="width: 11%">Tanggal Pindah</th>
                                    <th style="width: 11%">Jenis</th>
                                    <th class="text-center" style="width: 11%">Status</th>
                                    <th class="text-center" style="width: 8%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roomMoves as $i => $move)
                                    <tr>
                                        <td class="text-center text-muted">
                                            {{ $roomMoves->firstItem() + $i }}
                                        </td>
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
                                                    <span class="fw-semibold">{{ $move->resident?->student?->name ?? '-' }}</span>
                                                    @if($move->resident?->student?->nisn)
                                                        <br><small class="text-muted">{{ $move->resident->student->nisn }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="ri-home-4-line me-1"></i>{{ $move->fromRoom?->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="ri-arrow-right-line me-1"></i>{{ $move->toRoom?->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($move->move_date)
                                                {{ $move->move_date->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                {{ ucfirst($move->move_type ?? '-') }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($move->status === 'pending')
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ri-time-line me-1"></i>Menunggu
                                                </span>
                                            @elseif($move->status === 'approved')
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-checkbox-circle-line me-1"></i>Disetujui
                                                </span>
                                            @elseif($move->status === 'rejected')
                                                <span class="badge bg-danger-subtle text-danger">
                                                    <i class="ri-close-circle-line me-1"></i>Ditolak
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    {{ ucfirst($move->status ?? '-') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.asrama.room-moves.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomMoveUuid' => $move->id]) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="Lihat Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <div class="mb-3">
                                                <i class="ri-home-office-line fs-1 d-block text-muted"></i>
                                            </div>
                                            <h6 class="text-muted mb-1">Belum Ada Data Mutasi</h6>
                                            <p class="text-muted mb-3">Tidak ada permohonan mutasi kamar.</p>
                                            <a href="{{ route('user.asrama.room-moves.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                               class="btn btn-primary btn-sm">
                                                <i class="ri-add-line me-1"></i> Ajukan Mutasi Baru
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($roomMoves->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted small">Menampilkan {{ $roomMoves->firstItem() ?? 0 }} - {{ $roomMoves->lastItem() ?? 0 }} dari {{ $roomMoves->total() }} data</div>
                            <div>{{ $roomMoves->withQueryString()->links() }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
