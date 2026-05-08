@extends('layouts.master')
@section('title') Absensi Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? '' }}</a> @endslot
        @slot('title') Absensi @endslot
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
            <i class="ri-error-warning-line me-2"></i>Terjadi kesalahan. Silakan coba lagi.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="avatar-md mx-auto mb-2 rounded-circle bg-success-subtle">
                        <i class="ri-checkbox-circle-line fs-24 text-success"></i>
                    </div>
                    <h4 class="mb-0">{{ $stats['hadir'] ?? 0 }}</h4>
                    <p class="text-muted mb-0 small">Hadir</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="avatar-md mx-auto mb-2 rounded-circle bg-warning-subtle">
                        <i class="ri-flight-takeoff-line fs-24 text-warning"></i>
                    </div>
                    <h4 class="mb-0">{{ $stats['izin'] ?? 0 }}</h4>
                    <p class="text-muted mb-0 small">Izin</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="avatar-md mx-auto mb-2 rounded-circle bg-info-subtle">
                        <i class="ri-sick-line fs-24 text-info"></i>
                    </div>
                    <h4 class="mb-0">{{ $stats['sakit'] ?? 0 }}</h4>
                    <p class="text-muted mb-0 small">Sakit</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="avatar-md mx-auto mb-2 rounded-circle bg-danger-subtle">
                        <i class="ri-prohibited-line fs-24 text-danger"></i>
                    </div>
                    <h4 class="mb-0">{{ $stats['alpa'] ?? 0 }}</h4>
                    <p class="text-muted mb-0 small">Alpa</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="avatar-md mx-auto mb-2 rounded-circle bg-secondary-subtle">
                        <i class="ri-logout-box-r-line fs-24 text-secondary"></i>
                    </div>
                    <h4 class="mb-0">{{ $stats['pulang'] ?? 0 }}</h4>
                    <p class="text-muted mb-0 small">Pulang</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="avatar-md mx-auto mb-2 rounded-circle bg-dark-subtle">
                        <i class="ri-team-line fs-24 text-dark"></i>
                    </div>
                    <h4 class="mb-0">{{ $stats['total'] ?? 0 }}</h4>
                    <p class="text-muted mb-0 small">Total Penghuni</p>
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
                            <h5 class="card-title mb-0">Daftar Absensi Asrama</h5>
                            <p class="text-muted mb-0">
                                {{ $dormitory->name ?? 'Asrama' }} &mdash;
                                {{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('d M Y') : 'Semua Tanggal' }}
                                {{ $selectedSession ? ' — Sesi ' . $selectedSession : '' }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('user.asrama.attendance.recap', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-outline-info">
                                    <i class="ri-bar-chart-line align-bottom me-1"></i> Rekap Bulanan
                                </a>
                                <a href="{{ route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}?date={{ $selectedDate ?? now()->toDateString() }}&session={{ $selectedSession ?? 'pagi' }}"
                                   class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Catat Absensi
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
                            <label class="form-label small text-muted">Tanggal</label>
                            <input type="date" name="date" class="form-control" value="{{ $selectedDate ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Sesi</label>
                            <select name="session" class="form-control">
                                <option value="">Semua Sesi</option>
                                <option value="pagi" {{ ($selectedSession ?? '') === 'pagi' ? 'selected' : '' }}>Pagi</option>
                                <option value="siang" {{ ($selectedSession ?? '') === 'siang' ? 'selected' : '' }}>Siang</option>
                                <option value="sore" {{ ($selectedSession ?? '') === 'sore' ? 'selected' : '' }}>Sore</option>
                                <option value="malam" {{ ($selectedSession ?? '') === 'malam' ? 'selected' : '' }}>Malam</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Kamar</label>
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
                            <label class="form-label small text-muted">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">&nbsp;</label>
                            <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light w-100">
                                <i class="ri-reset-right-line"></i> Reset
                            </a>
                        </div>
                    </form>

                    {{-- ============================================================
                         ATTENDANCE TABLE
                    ============================================================ --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 5%">No</th>
                                    <th>Nama Santri</th>
                                    <th style="width: 12%">Kamar</th>
                                    <th class="text-center" style="width: 10%">Bed</th>
                                    <th class="text-center" style="width: 12%">Status</th>
                                    <th style="width: 10%">Sesi</th>
                                    <th style="width: 20%">Catatan</th>
                                    <th style="width: 15%">Recorder</th>
                                    <th class="text-center" style="width: 8%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendanceRecords as $i => $record)
                                    <tr class="{{ $record->status === 'alpa' ? 'table-danger' : ($record->status === 'izin' ? 'table-warning' : '') }}">
                                        <td class="text-center text-muted">
                                            {{ $attendanceRecords->firstItem() + $i }}
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $record->resident?->student?->name ?? '-' }}</span>
                                            @if($record->resident?->student?->nisn)
                                                <br><small class="text-muted">{{ $record->resident->student->nisn }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                <i class="ri-home-4-line me-1"></i>{{ $record->resident?->room?->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">#{{ $record->resident?->bed_number ?? '-' }}</span>
                                        </td>
                                        <td class="text-center">
                                            @switch($record->status)
                                                @case('hadir')
                                                    <span class="badge bg-success-subtle text-success">
                                                        <i class="ri-checkbox-circle-line me-1"></i>Hadir
                                                    </span>
                                                    @break
                                                @case('izin')
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        <i class="ri-flight-takeoff-line me-1"></i>Izin
                                                    </span>
                                                    @break
                                                @case('sakit')
                                                    <span class="badge bg-info-subtle text-info">
                                                        <i class="ri-sick-line me-1"></i>Sakit
                                                    </span>
                                                    @break
                                                @case('alpa')
                                                    <span class="badge bg-danger-subtle text-danger">
                                                        <i class="ri-prohibited-line me-1"></i>Alpa
                                                    </span>
                                                    @break
                                                @case('pulang')
                                                    <span class="badge bg-secondary-subtle text-secondary">
                                                        <i class="ri-logout-box-r-line me-1"></i>Pulang
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="badge bg-light text-muted">-</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <span class="badge bg-dark-subtle text-dark">{{ ucfirst($record->session) }}</span>
                                        </td>
                                        <td>
                                            <span class="small text-muted">{{ Str::limit($record->notes ?? '-', 50) }}</span>
                                        </td>
                                        <td>
                                            <span class="small text-muted">{{ $record->recorder?->name ?? '-' }}</span>
                                            <br><span class="text-muted small">{{ $record->created_at?->format('d/m H:i') }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.asrama.attendance.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'attendanceUuid' => $record->id]) }}"
                                               class="btn btn-sm btn-outline-secondary"
                                               title="Edit Absensi">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">
                                            <div class="mb-3">
                                                <i class="ri-calendar-check-line fs-1 d-block text-muted"></i>
                                            </div>
                                            <h6 class="text-muted mb-1">Belum Ada Data Absensi</h6>
                                            <p class="text-muted mb-3">
                                                @if($selectedDate)
                                                    Tidak ada data absensi untuk {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}.
                                                @else
                                                    Belum ada data absensi yang tercatat.
                                                @endif
                                            </p>
                                            <a href="{{ route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}?date={{ $selectedDate ?? now()->toDateString() }}"
                                               class="btn btn-success btn-sm">
                                                <i class="ri-add-line me-1"></i> Catat Absensi
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($attendanceRecords->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $attendanceRecords->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection