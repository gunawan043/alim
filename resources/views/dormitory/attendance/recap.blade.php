@extends('layouts.master')
@section('title') Rekap Absensi Bulanan - {{ $dormitory->name ?? 'Asrama' }} @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('li_4') <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Absensi</a> @endslot
        @slot('title') Rekap Bulanan @endslot
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
         PERIODE SELECTOR
    ============================================================ --}}
    <div class="card">
        <div class="card-body py-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fs-12 text-muted mb-1">Tahun</label>
                    <select name="year" class="form-select form-select-sm">
                        @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                            <option value="{{ $y }}" {{ ($selectedYear ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-12 text-muted mb-1">Bulan</label>
                    <select name="month" class="form-select form-select-sm">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ ($selectedMonth ?? date('n')) == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromDate(null, $m, 1)->locale('id')->monthName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-12 text-muted mb-1">Kamar</label>
                    <select name="room_id" class="form-select form-select-sm">
                        <option value="">Semua Kamar</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="ri-search-line me-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         SUMMARY CARDS (gaya stat-card template)
    ============================================================ --}}
    <div class="row">
        @foreach(['hadir', 'izin', 'sakit', 'alpa', 'pulang'] as $status)
            @php
                $statusLabel = ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa', 'pulang' => 'Pulang'];
                $statusColor = ['hadir' => 'success', 'izin' => 'warning', 'sakit' => 'info', 'alpa' => 'danger', 'pulang' => 'secondary'];
                $statusIcon  = ['hadir' => 'ri-checkbox-circle-line', 'izin' => 'ri-flight-takeoff-line', 'sakit' => 'ri-heart-pulse-line', 'alpa' => 'ri-prohibited-line', 'pulang' => 'ri-logout-box-r-line'];
                $total = $summary[$status] ?? 0;
            @endphp
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card card-animate stat-card h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-{{ $statusColor[$status] }}-subtle rounded">
                                    <i class="{{ $statusIcon[$status] }} text-{{ $statusColor[$status] }}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="stat-label mb-0">{{ $statusLabel[$status] }}</p>
                                <h3 class="stat-value">{{ number_format($total) }}</h3>
                            </div>
                        </div>
                        <p class="stat-note mb-0"><i class="ri-bar-chart-line me-1"></i>Jumlah catatan bulan ini</p>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-file-list-3-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Catatan</p>
                            <h3 class="stat-value">{{ number_format($summary['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-file-list-line me-1"></i>Akumulasi seluruh status</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         PER-STUDENT RECAP TABLE
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Rekap per Santri</h5>
                            <p class="text-muted mb-0 small">
                                {{ \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->locale('id')->monthName }} {{ $selectedYear }}
                                &mdash; {{ $studentRecap->count() }} Santri
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-outline-secondary btn-sm" title="Kembali ke Absensi">
                                    <i class="ri-arrow-left-line me-1"></i> Kembali ke Absensi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if($studentRecap->count() > 0)
                        <div class="table-container">
                            <table class="table table-hover align-middle table-freeze mb-0">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th rowspan="2" class="align-middle" style="width:50px;">No</th>
                                        <th rowspan="2" class="align-middle text-start">Nama Santri</th>
                                        <th rowspan="2" class="align-middle" style="width:12%">Kamar</th>
                                        <th rowspan="2" class="align-middle" style="width:7%">Bed</th>
                                        <th colspan="5">Jumlah per Status</th>
                                        <th rowspan="2" class="align-middle text-center" style="width:8%">Total</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center bg-success-subtle text-success"><i class="ri-checkbox-circle-line me-1"></i>Hadir</th>
                                        <th class="text-center bg-warning-subtle text-warning"><i class="ri-flight-takeoff-line me-1"></i>Izin</th>
                                        <th class="text-center bg-info-subtle text-info"><i class="ri-heart-pulse-line me-1"></i>Sakit</th>
                                        <th class="text-center bg-danger-subtle text-danger"><i class="ri-prohibited-line me-1"></i>Alpa</th>
                                        <th class="text-center bg-secondary-subtle text-secondary"><i class="ri-logout-box-r-line me-1"></i>Pulang</th>
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
                                                           class="fw-semibold text-body">{{ $recap['name'] }}</a>
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
                                            <td class="text-center">
                                                @if(($recap['counts']['hadir'] ?? 0) > 0)
                                                    <span class="badge bg-success-subtle text-success fs-6">{{ $recap['counts']['hadir'] }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if(($recap['counts']['izin'] ?? 0) > 0)
                                                    <span class="badge bg-warning-subtle text-warning fs-6">{{ $recap['counts']['izin'] }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if(($recap['counts']['sakit'] ?? 0) > 0)
                                                    <span class="badge bg-info-subtle text-info fs-6">{{ $recap['counts']['sakit'] }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if(($recap['counts']['alpa'] ?? 0) > 0)
                                                    <span class="badge bg-danger-subtle text-danger fs-6 fw-bold">{{ $recap['counts']['alpa'] }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if(($recap['counts']['pulang'] ?? 0) > 0)
                                                    <span class="badge bg-secondary-subtle text-secondary fs-6">{{ $recap['counts']['pulang'] }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-dark-subtle text-dark fs-6">{{ $recap['total'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-empty-state colspan="10" title="Belum ada data rekap"
                                            message="Tidak ada data absensi untuk periode ini." />
                                    @endforelse
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr>
                                        <td colspan="4" class="text-center fw-bold">TOTAL</td>
                                        <td class="text-center fw-bold text-success">{{ number_format($summary['hadir'] ?? 0) }}</td>
                                        <td class="text-center fw-bold text-warning">{{ number_format($summary['izin'] ?? 0) }}</td>
                                        <td class="text-center fw-bold text-info">{{ number_format($summary['sakit'] ?? 0) }}</td>
                                        <td class="text-center fw-bold text-danger">{{ number_format($summary['alpa'] ?? 0) }}</td>
                                        <td class="text-center fw-bold text-secondary">{{ number_format($summary['pulang'] ?? 0) }}</td>
                                        <td class="text-center fw-bold">{{ number_format($summary['total'] ?? 0) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- ============================================================
                             ATTENDANCE RATE
                        ============================================================ --}}
                        @php
                            $totalAttendance = $summary['total'] ?? 0;
                            $hadirCount = $summary['hadir'] ?? 0;
                            $attendanceRate = $totalAttendance > 0 ? round(($hadirCount / $totalAttendance) * 100, 1) : 0;
                        @endphp
                        <div class="mt-3 pt-3 border-top">
                            <div class="row align-items-center g-3">
                                <div class="col-md-6">
                                    <p class="mb-1 small text-muted">Tingkat Kehadiran Bulan Ini</p>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="progress flex-grow-1" style="height:5px;">
                                            <div class="progress-bar bg-success" role="progressbar"
                                                 style="width: {{ $attendanceRate }}%"
                                                 aria-valuenow="{{ $attendanceRate }}"
                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span class="fw-bold text-success">{{ $attendanceRate }}%</span>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <span class="small text-muted">{{ number_format($hadirCount) }} dari {{ number_format($totalAttendance) }} catatan kehadiran</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <x-empty-state colspan="10" title="Belum ada data rekap"
                            message="Tidak ada catatan absensi untuk periode {{ \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->locale('id')->monthName }} {{ $selectedYear }}.">
                            <a href="{{ route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-primary btn-sm mt-3">
                                <i class="ri-add-line me-1"></i> Catat Absensi
                            </a>
                        </x-empty-state>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         PER-ROOM SUMMARY
    ============================================================ --}}
    @if($studentRecap->count() > 0)
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-bar-chart-line text-primary me-2"></i>Rekap per Kamar</h5>
                    <p class="text-muted mb-0 small">Total catatan absensi berdasarkan kamar pada periode ini.</p>
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
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze mb-0">
                            <thead class="text-center">
                                <tr>
                                    <th class="text-start">Kamar</th>
                                    <th class="bg-success-subtle text-success">Hadir</th>
                                    <th class="bg-warning-subtle text-warning">Izin</th>
                                    <th class="bg-info-subtle text-info">Sakit</th>
                                    <th class="bg-danger-subtle text-danger">Alpa</th>
                                    <th class="bg-secondary-subtle text-secondary">Pulang</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roomStats as $roomName => $stats)
                                    <tr>
                                        <td><span class="badge bg-info-subtle text-info"><i class="ri-home-4-line me-1"></i>{{ $roomName }}</span></td>
                                        <td class="text-center text-success fw-semibold">{{ number_format($stats['hadir']) }}</td>
                                        <td class="text-center text-warning fw-semibold">{{ number_format($stats['izin']) }}</td>
                                        <td class="text-center text-info fw-semibold">{{ number_format($stats['sakit']) }}</td>
                                        <td class="text-center text-danger fw-semibold">{{ number_format($stats['alpa']) }}</td>
                                        <td class="text-center text-secondary fw-semibold">{{ number_format($stats['pulang']) }}</td>
                                        <td class="text-center fw-bold">{{ number_format(array_sum($stats)) }}</td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="7" title="Belum ada data kamar"
                                        message="Tidak ada data absensi per kamar untuk periode ini." />
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