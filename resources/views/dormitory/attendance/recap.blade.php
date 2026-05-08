@extends('layouts.master')
@section('title') Rekap Absensi Bulanan - {{ $dormitory->name ?? 'Asrama' }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? '' }}</a> @endslot
        @slot('li_4') <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Absensi</a> @endslot
        @slot('title') Rekap Bulanan @endslot
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

    {{-- ============================================================
         MONTH / YEAR SELECTOR
    ============================================================ --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Tahun</label>
                    <select name="year" class="form-control">
                        @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                            <option value="{{ $y }}" {{ ($selectedYear ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bulan</label>
                    <select name="month" class="form-control">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ ($selectedMonth ?? date('n')) == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromDate(null, $m, 1)->locale('id')->monthName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kamar</label>
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
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ri-search-line me-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MONTHLY SUMMARY CARDS
    ============================================================ --}}
    <div class="row mb-4">
        @foreach(['hadir', 'izin', 'sakit', 'alpa', 'pulang'] as $status)
            @php
                $statusLabel = ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa', 'pulang' => 'Pulang'];
                $statusColor = ['hadir' => 'success', 'izin' => 'warning', 'sakit' => 'info', 'alpa' => 'danger', 'pulang' => 'secondary'];
                $statusIcon  = ['hadir' => 'ri-checkbox-circle-line', 'izin' => 'ri-flight-takeoff-line', 'sakit' => 'ri-sick-line', 'alpa' => 'ri-prohibited-line', 'pulang' => 'ri-logout-box-r-line'];
                $total = $summary[$status] ?? 0;
            @endphp
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card card-animate border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <div class="avatar-md mx-auto mb-2 rounded-circle bg-{{ $statusColor[$status] }}-subtle">
                            <i class="{{ $statusIcon[$status] }} fs-24 text-{{ $statusColor[$status] }}"></i>
                        </div>
                        <h4 class="mb-0">{{ $total }}</h4>
                        <p class="text-muted mb-0 small">{{ $statusLabel[$status] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="avatar-md mx-auto mb-2 rounded-circle bg-primary-subtle">
                        <i class="ri-file-list-3-line fs-24 text-primary"></i>
                    </div>
                    <h4 class="mb-0">{{ $summary['total'] ?? 0 }}</h4>
                    <p class="text-muted mb-0 small">Total Catatan</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         SECTION: PER-STUDENT RECAP TABLE
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Rekap per Santri</h5>
                            <p class="text-muted mb-0">
                                {{ \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->locale('id')->monthName }} {{ $selectedYear }}
                                &mdash;
                                {{ $studentRecap->count() }} santi
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light btn-sm">
                                <i class="ri-arrow-left-line align-middle me-1"></i> Kembali ke Absensi
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($studentRecap->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th rowspan="2" class="align-middle" style="width: 5%">No</th>
                                        <th rowspan="2" class="align-middle">Nama Santri</th>
                                        <th rowspan="2" class="align-middle" style="width: 12%">Kamar</th>
                                        <th rowspan="2" class="align-middle" style="width: 7%">Bed</th>
                                        <th colspan="5" style="width: 40%">Jumlah per Status</th>
                                        <th rowspan="2" class="align-middle text-center" style="width: 8%">Total Catatan</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center bg-success-subtle text-success" style="width: 8%">
                                            <i class="ri-checkbox-circle-line me-1"></i>Hadir
                                        </th>
                                        <th class="text-center bg-warning-subtle text-warning" style="width: 8%">
                                            <i class="ri-flight-takeoff-line me-1"></i>Izin
                                        </th>
                                        <th class="text-center bg-info-subtle text-info" style="width: 8%">
                                            <i class="ri-sick-line me-1"></i>Sakit
                                        </th>
                                        <th class="text-center bg-danger-subtle text-danger" style="width: 8%">
                                            <i class="ri-prohibited-line me-1"></i>Alpa
                                        </th>
                                        <th class="text-center bg-secondary-subtle text-secondary" style="width: 8%">
                                            <i class="ri-logout-box-r-line me-1"></i>Pulang
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($studentRecap as $idx => $recap)
                                        <tr>
                                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-3">
                                                        <div class="avatar-title rounded-circle bg-{{ $recap['gender'] === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $recap['gender'] === 'P' ? 'danger' : 'primary' }} fw-bold fs-10">
                                                            {{ strtoupper(substr($recap['name'], 0, 1)) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <a href="{{ route('user.asrama.residents.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentUuid' => $recap['resident_id']]) }}"
                                                           class="fw-semibold text-body">
                                                            {{ $recap['name'] }}
                                                        </a>
                                                        <br><small class="text-muted">{{ $recap['nisn'] }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info-subtle text-info">{{ $recap['room'] }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary">#{{ $recap['bed_number'] }}</span>
                                            </td>
                                            {{-- Hadir --}}
                                            <td class="text-center">
                                                @if(($recap['counts']['hadir'] ?? 0) > 0)
                                                    <span class="badge bg-success-subtle text-success fs-6">
                                                        {{ $recap['counts']['hadir'] }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            {{-- Izin --}}
                                            <td class="text-center">
                                                @if(($recap['counts']['izin'] ?? 0) > 0)
                                                    <span class="badge bg-warning-subtle text-warning fs-6">
                                                        {{ $recap['counts']['izin'] }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            {{-- Sakit --}}
                                            <td class="text-center">
                                                @if(($recap['counts']['sakit'] ?? 0) > 0)
                                                    <span class="badge bg-info-subtle text-info fs-6">
                                                        {{ $recap['counts']['sakit'] }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            {{-- Alpa --}}
                                            <td class="text-center">
                                                @if(($recap['counts']['alpa'] ?? 0) > 0)
                                                    <span class="badge bg-danger-subtle text-danger fs-6 fw-bold">
                                                        {{ $recap['counts']['alpa'] }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            {{-- Pulang --}}
                                            <td class="text-center">
                                                @if(($recap['counts']['pulang'] ?? 0) > 0)
                                                    <span class="badge bg-secondary-subtle text-secondary fs-6">
                                                        {{ $recap['counts']['pulang'] }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-dark-subtle text-dark fs-6">
                                                    {{ $recap['total'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-5">
                                                <i class="ri-file-chart-line fs-1 d-block mb-2"></i>
                                                Tidak ada data absensi untuk periode ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                {{-- GRAND TOTAL ROW --}}
                                <tfoot class="table-dark">
                                    <tr>
                                        <td colspan="4" class="text-center fw-bold">TOTAL</td>
                                        <td class="text-center fw-bold text-success">{{ $summary['hadir'] ?? 0 }}</td>
                                        <td class="text-center fw-bold text-warning">{{ $summary['izin'] ?? 0 }}</td>
                                        <td class="text-center fw-bold text-info">{{ $summary['sakit'] ?? 0 }}</td>
                                        <td class="text-center fw-bold text-danger">{{ $summary['alpa'] ?? 0 }}</td>
                                        <td class="text-center fw-bold text-secondary">{{ $summary['pulang'] ?? 0 }}</td>
                                        <td class="text-center fw-bold">{{ $summary['total'] ?? 0 }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- ============================================================
                             ATTENDANCE RATE PROGRESS BAR
                        ============================================================ --}}
                        @php
                            $totalAttendance = $summary['total'] ?? 0;
                            $hadirCount = $summary['hadir'] ?? 0;
                            $attendanceRate = $totalAttendance > 0 ? round(($hadirCount / $totalAttendance) * 100, 1) : 0;
                        @endphp
                        <div class="card-footer bg-transparent border-top">
                            <div class="row align-items-center">
                                <div class="col-sm-6">
                                    <p class="mb-1 small text-muted">Tingkat Kehadiran Bulan Ini</p>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="flex-grow-1">
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                     style="width: {{ $attendanceRate }}%"
                                                     aria-valuenow="{{ $attendanceRate }}"
                                                     aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-bold text-success fs-5">{{ $attendanceRate }}%</span>
                                    </div>
                                </div>
                                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                                    <span class="small text-muted">
                                        {{ $hadirCount }} dari {{ $totalAttendance }} catatan kehadiran
                                    </span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="ri-file-chart-line fs-1 d-block text-muted" style="font-size: 4rem;"></i>
                            </div>
                            <h5 class="text-muted mb-2">Belum Ada Data Rekap</h5>
                            <p class="text-muted mb-4">
                                Tidak ada catatan absensi untuk
                                {{ \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->locale('id')->monthName }} {{ $selectedYear }}.
                            </p>
                            <a href="{{ route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-success">
                                <i class="ri-add-line me-1"></i> Catat Absensi
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MONTHLY CHART PLACEHOLDER
    ============================================================ --}}
    @if($studentRecap->count() > 0)
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-bar-chart-line text-primary me-2"></i>Grafik Kehadiran per Kamar
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $roomStats = $studentRecap->groupBy('room')->map(function($group) {
                            return [
                                'hadir' => $group->sum(fn($r) => $r['counts']['hadir'] ?? 0),
                                'izin'  => $group->sum(fn($r) => $r['counts']['izin'] ?? 0),
                                'sakit' => $group->sum(fn($r) => $r['counts']['sakit'] ?? 0),
                                'alpa'  => $group->sum(fn($r) => $r['counts']['alpa'] ?? 0),
                                'pulang'=> $group->sum(fn($r) => $r['counts']['pulang'] ?? 0),
                            ];
                        });
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Kamar</th>
                                    <th class="bg-success-subtle text-success">Hadir</th>
                                    <th class="bg-warning-subtle text-warning">Izin</th>
                                    <th class="bg-info-subtle text-info">Sakit</th>
                                    <th class="bg-danger-subtle text-danger">Alpa</th>
                                    <th class="bg-secondary-subtle text-secondary">Pulang</th>
                                    <th class="text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roomStats as $roomName => $stats)
                                    <tr>
                                        <td><span class="badge bg-info-subtle text-info">{{ $roomName }}</span></td>
                                        <td class="text-center text-success fw-semibold">{{ $stats['hadir'] }}</td>
                                        <td class="text-center text-warning fw-semibold">{{ $stats['izin'] }}</td>
                                        <td class="text-center text-info fw-semibold">{{ $stats['sakit'] }}</td>
                                        <td class="text-center text-danger fw-semibold">{{ $stats['alpa'] }}</td>
                                        <td class="text-center text-secondary fw-semibold">{{ $stats['pulang'] }}</td>
                                        <td class="text-center fw-bold">{{ array_sum($stats) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection