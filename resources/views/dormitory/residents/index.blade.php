@extends('layouts.master')
@section('title') Daftar Santri Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Daftar Santri @endslot
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

    <div class="row g-3 mb-2">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90 border-start border-primary">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary rounded fs-2"><i class="ri-user-follow-line fs-4 text-white"></i></span>
                        </div>
                        <div>
                            <p class="text-muted text-uppercase fs-12 mb-1">Total Santri di Asrama</p>
                            <h3 class="mb-0 fw-bold">{{ $stats['total'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90 border-start border-success">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success rounded fs-2"><i class="ri-home-line fs-4 text-white"></i></span>
                        </div>
                        <div>
                            <p class="text-muted text-uppercase fs-12 mb-1">Kamar Terisi</p>
                            <h3 class="mb-0 fw-bold">{{ $stats['occupied_rooms'] ?? 0 }}<span class="fs-5 text-muted">/{{ $stats['total_rooms'] ?? 0 }}</span></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90 border-start border-warning">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning rounded fs-2"><i class="ri-user-location-line text-white"></i></span>
                        </div>
                        <div>
                            <p class="text-muted text-uppercase fs-12 mb-1">Di Asrama</p>
                            <h3 class="mb-0 fw-bold">{{ $stats['in_dormitory'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90 border-start border-danger">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger rounded fs-2"><i class="ri-route-line fs-4 text-white"></i></span>
                        </div>
                        <div>
                            <p class="text-muted text-uppercase fs-12 mb-1">Sedang Izin Pulang</p>
                            <h3 class="mb-0 fw-bold">{{ $stats['on_permit'] ?? 0 }}</h3>
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
                            <h5 class="card-title mb-0">Daftar Santri Asrama</h5>
                            <p class="text-muted mb-0">
                                {{ $dormitory->name ?? 'Asrama' }} &mdash; {{ $stats['total'] ?? 0 }} santri terdaftar
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-info btn-sm">
                                    <i class="ri-calendar-check-line align-bottom me-1"></i> Absensi
                                </a>
                                <a href="{{ route('user.asrama.residents.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="ri-add-line align-bottom me-1"></i> Tempatkan Santri
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         FILTER FORM
                    ============================================================ --}}
                    <form method="GET" class="row g-3 align-items-end mb-4 pb-3 border-bottom border-light">
                        <div class="col-md-4">
                            <label class="form-label fs-12 text-muted mb-1">Cari Santri</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="ri-search-line text-muted"></i>
                                </span>
                                <input type="text" name="search" class="form-control"
                                       placeholder="Nama / NISN Santri..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 text-muted mb-1">Kamar</label>
                            <select name="room_id" class="form-select">
                                <option value="">Semua Kamar</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                        {{ $room->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fs-12 text-muted mb-1">Status Aktif</label>
                            <select name="is_active" class="form-select">
                                <option value="">Semua</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="ri-search-line me-1"></i> Terapkan Filter
                                </button>
                                <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-outline-secondary"
                                   data-bs-toggle="tooltip" data-bs-placement="top" title="Reset Filter">
                                    <i class="ri-refresh-line"></i>
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
                                    <th style="width: 12%">Ruang / Kamar</th>
                                    <th class="text-center" style="width: 7%">Tempat Tidur</th>
                                    <th class="text-center" style="width: 10%">Status</th>
                                    <th class="text-center" style="width: 10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($residents as $i => $resident)
                                    @php
                                        $permit = $activePermits->get($resident->student_id);
                                        $isOverdue = $permit && $permit->status === 'overdue';
                                        $expectedReturn = $permit?->expected_return_datetime;
                                    @endphp
                                    <tr class="{{ $isOverdue ? 'table-warning' : '' }}">
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
                                            <span class="badge bg-primary text-light">
                                                <i class="ri-home-4-line me-1"></i>{{ $resident->room?->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">#{{ $resident->bed_number }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($permit)
                                                <span class="badge bg-{{ $isOverdue ? 'danger' : 'warning' }}-subtle text-{{ $isOverdue ? 'danger' : 'warning' }}"
                                                      title="Kembali: {{ $expectedReturn?->format('d M Y H:i') }}">
                                                    <i class="ri-{{ $isOverdue ? 'alarm-warning-line' : 'route-line' }} me-1"></i>
                                                    {{ $isOverdue ? 'Terlambat' : 'Izin Pulang' }}
                                                </span>
                                                @if($expectedReturn)
                                                    <div class="small text-muted mt-1">
                                                        {{ $expectedReturn->format('d M H:i') }}
                                                    </div>
                                                @endif
                                            @elseif($resident->is_active)
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-home-heart-line me-1"></i>Di Asrama
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    <i class="ri-logout-box-r-line me-1"></i>Keluar
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="{{ route('user.asrama.residents.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentUuid' => $resident->id]) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Detail Santri">
                                                    <i class="ri-eye-line"></i>
                                                </a>

                                                {{-- Check-out button for active residents --}}
                                                @if($resident->is_active)
                                                    <form method="POST"
                                                          action="{{ route('user.asrama.residents.checkout', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentUuid' => $resident->id]) }}"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-danger checkout-btn"
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
                                        <td colspan="7" class="text-center py-5">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                            <h6 class="text-muted mb-1 mt-3">Belum Ada Santri</h6>
                                            <p class="text-muted mb-3">Belum ada data santri yang ditempatkan di asrama ini.</p>
                                            <a href="{{ route('user.asrama.residents.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                               class="btn btn-primary btn-sm">
                                                <i class="ri-add-line me-1"></i> Tempatkan Santri
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination (shared template) --}}
                    @include('shared._pagination', ['paginator' => $residents->withQueryString()])
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
                title: 'Konfirmasi Keluar',
                html: '<p>Yakin ingin mengeluarkan <strong>' + studentName + '</strong> dari asrama?</p><p class="text-muted small">Status akan berubah menjadi nonaktif.</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="ri-logout-box-r-line me-1"></i> Ya, Keluarkan',
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