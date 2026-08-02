@extends('layouts.master')
@section('title') Mutasi Kamar — Asrama @endsection

@section('css')
    <style>
        .card-animate { transition: all 0.3s ease; }
        .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
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
         STATS CARDS — TEMPLATE PERIZINAN (seperti permits)
    ============================================================ --}}
    <div class="row g-3 mb-3">
        {{-- 1. Menunggu --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-time-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Menunggu</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['pending'] ?? 0 }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">permohonan</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">
                        <i class="ri-information-line me-1"></i>Perlu tindakan admin
                    </p>
                </div>
            </div>
        </div>

        {{-- 2. Disetujui --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Disetujui</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['approved'] ?? 0 }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">permohonan</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">
                        <i class="ri-information-line me-1"></i>Berlaku valid
                    </p>
                </div>
            </div>
        </div>

        {{-- 3. Ditolak --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2">
                                <i class="ri-close-circle-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Ditolak</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['rejected'] ?? 0 }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">permohonan</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">
                        <i class="ri-information-line me-1"></i>Tidak disetujui
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Mutasi Kamar</h5>
                        <p class="text-muted mb-0" style="font-size:11px;">
                            {{ $dormitory->name ?? 'Asrama' }} &mdash; {{ $roomMoves->total() ?? 0 }} permohonan
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('user.asrama.room-moves.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                           class="btn btn-primary">
                            <i class="ri-add-line align-bottom me-1"></i> Ajukan Mutasi
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         FILTER FORM
                    ============================================================ --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:11px;">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size:11px;">Dari</label>
                            <input type="date" name="start_date" class="form-control"
                                   value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size:11px;">Sampai</label>
                            <input type="date" name="end_date" class="form-control"
                                   value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:11px;">Cari Santri</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Nama Santri..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100" title="Filter">
                                <i class="ri-search-line"></i>
                            </button>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <a href="{{ route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light w-100" title="Reset">
                                <i class="ri-reset-right-line"></i>
                            </a>
                        </div>
                    </form>

                    {{-- ============================================================
                         TABLE (sesuai style perizinan)
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
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon> <br>
                                            <h6 class="text-muted mb-1 mt-3">Belum Ada Data Mutasi</h6>
                                            <p class="text-muted mb-3">Tidak ada permohonan mutasi kamar yang tercatat.</p>
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

                    <x-pagination :paginator="$roomMoves" />
                </div>
            </div>
        </div>
    </div>
@endsection
