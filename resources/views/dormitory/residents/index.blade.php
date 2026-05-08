@extends('layouts.master')
@section('title') Daftar Penghuni Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('title') Penghuni</title>
        @slot('subtitle') {{ $dormitory->name ?? '' }}</title>
        @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md rounded-circle bg-success-subtle">
                                <i class="ri-user-follow-line fs-24 text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-muted text-truncate mb-1">Total Penghuni</p>
                            <h4 class="mb-0">{{ $stats['total'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md rounded-circle bg-primary-subtle">
                                <i class="ri-home-line fs-24 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-muted text-truncate mb-1">Kamar Terisi</p>
                            <h4 class="mb-0">{{ $stats['occupied_rooms'] ?? 0 }} / {{ $stats['total_rooms'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md rounded-circle bg-warning-subtle">
                                <i class="ri-checkbox-circle-line fs-24 text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-muted text-truncate mb-1">Aktif</p>
                            <h4 class="mb-0">{{ $stats['active'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md rounded-circle bg-danger-subtle">
                                <i class="ri-user-unfollow-line fs-24 text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-muted text-truncate mb-1">Nonaktif</p>
                            <h4 class="mb-0">{{ $stats['inactive'] ?? 0 }}</h4>
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
                            <h5 class="card-title mb-0">Daftar Penghuni Asrama</h5>
                            <p class="text-muted mb-0">
                                {{ $dormitory->name ?? 'Asrama' }} &mdash; {{ $stats['total'] ?? 0 }} penghuni terdaftar
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-info">
                                    <i class="ri-calendar-check-line align-bottom me-1"></i> Absensi
                                </a>
                                <a href="{{ route('user.asrama.residents.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Check-in Santri
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         FILTER FORM
                    ============================================================ --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Nama / NISN Santri..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="room_id" class="form-control">
                                <option value="">Semua Kamar</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                        {{ $room->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="is_active" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="gender" class="form-control">
                                <option value="">Semua Gender</option>
                                <option value="L" {{ request('gender') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ request('gender') === 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="ri-search-line me-1"></i> Filter
                                </button>
                                <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-light">
                                    <i class="ri-reset-right-line"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- ============================================================
                         RESIDENTS TABLE
                    ============================================================ --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 5%">No</th>
                                    <th>Nama Santri</th>
                                    <th style="width: 12%">NISN</th>
                                    <th style="width: 12%">Kamar</th>
                                    <th class="text-center" style="width: 7%">Bed</th>
                                    <th style="width: 12%">Check-in</th>
                                    <th class="text-center" style="width: 10%">Status</th>
                                    <th class="text-center" style="width: 10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($residents as $i => $resident)
                                    <tr>
                                        <td class="text-center text-muted">
                                            {{ $residents->firstItem() + $i }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-3">
                                                    <div class="avatar-title rounded-circle bg-{{ $resident->student?->gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $resident->student?->gender === 'P' ? 'danger' : 'primary' }} fw-bold fs-10">
                                                        {{ strtoupper(substr($resident->student?->name ?? '?', 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="fw-semibold">{{ $resident->student?->name ?? '-' }}</span>
                                                    @if($resident->student?->nisn)
                                                        <br><small class="text-muted">{{ $resident->student->nisn }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="text-dark">{{ $resident->student?->nisn ?? '-' }}</code>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                <i class="ri-home-4-line me-1"></i>{{ $resident->room?->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">#{{ $resident->bed_number }}</span>
                                        </td>
                                        <td>
                                            @if($resident->check_in_date)
                                                <span data-bs-toggle="tooltip" title="{{ $resident->check_in_date->format('l, d F Y') }}">
                                                    {{ $resident->check_in_date->format('d/m/Y') }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($resident->is_active)
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-checkbox-circle-line me-1"></i>Aktif
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    <i class="ri-close-circle-line me-1"></i>Nonaktif
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="{{ route('user.asrama.residents.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentUuid' => $resident->id]) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Detail Penghuni">
                                                    <i class="ri-eye-line"></i>
                                                </a>

                                                {{-- Check-out button for active residents --}}
                                                @if($resident->is_active)
                                                    <form method="POST"
                                                          action="{{ route('user.asrama.residents.checkout', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentUuid' => $resident->id]) }}"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-warning checkout-btn"
                                                                title="Check-out">
                                                            <i class="ri-logout-box-r-line"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <div class="mb-3">
                                                <i class="ri-user-search-line fs-1 d-block text-muted"></i>
                                            </div>
                                            <h6 class="text-muted mb-1">Belum Ada Penghuni</h6>
                                            <p class="text-muted mb-3">Tidak ada data penghuni yang sesuai filter Anda.</p>
                                            <a href="{{ route('user.asrama.residents.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                               class="btn btn-success btn-sm">
                                                <i class="ri-add-line me-1"></i> Check-in Santri Baru
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($residents->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $residents->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    // Check-out confirmation
    document.querySelectorAll('.checkout-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form = btn.closest('form');
            var studentName = btn.closest('tr').querySelector('.fw-semibold')?.textContent ?? 'santri ini';

            Swal.fire({
                title: 'Konfirmasi Check-out',
                html: '<p>Yakin ingin men-check-out <strong>' + studentName + '</strong> dari asrama?</p><p class="text-muted small">Status akan berubah menjadi nonaktif.</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="ri-logout-box-r-line me-1"></i> Ya, Check-out',
                cancelButtonText: 'Batal',
                confirmButtonClass: 'btn btn-warning me-2',
                cancelButtonClass: 'btn btn-light',
                reverseButtons: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endsection