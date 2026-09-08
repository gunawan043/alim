@extends('layouts.master')
@section('title') Dashboard Sistem @endsection

@php
    $user = auth()->user();
@endphp

@section('css')
<style>
    .welcome-banner {
        background: linear-gradient(135deg, #4b38b3 0%, #3577f1 100%);
        border-radius: 0.75rem;
        color: #fff;
    }
    .stat-card {
        transition: all 0.25s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: 1px solid rgba(0,0,0,0.05) !important;
        border-radius: 0.75rem;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(149, 157, 165, 0.15) !important;
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
    .avatar-title-initial {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 12px;
    }
    .chart-card {
        border-radius: 0.75rem;
    }
    .badge-status {
        font-size: 0.68rem;
        padding: 0.25em 0.6em;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .table-activity td {
        padding: 0.75rem 1rem;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') Dashboard Sistem @endslot
@endcomponent

{{-- ── WELCOME BANNER ────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="welcome-banner p-4 shadow-sm position-relative overflow-hidden">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="fw-bold text-white mb-1">Selamat Datang Kembali, {{ $user->name ?? 'Administrator' }}! 👋</h4>
                    <p class="text-white-50 mb-0">Berikut adalah ringkasan operasional Ponpes Abu Hurairah.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-white bg-opacity-20 text-primary px-3 py-2 fs-12 rounded-pill">
                        <i class="ri-calendar-event-line me-1"></i> {{ now()->translatedFormat('l, d F Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── ROW 1: Stat Cards ─────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100 mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Santri Aktif</p>
                        <h3 class="fw-bold mb-1">{{ number_format($stats['students_active']) }}</h3>
                        <span class="text-muted fs-12">Total: <strong>{{ number_format($stats['students_total']) }}</strong> Santri</span>
                    </div>
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="ri-user-follow-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100 mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Rombel Belajar</p>
                        <h3 class="fw-bold mb-1">{{ number_format($stats['study_groups_total']) }}</h3>
                        <span class="text-muted fs-12">Kelompok Belajar</span>
                    </div>
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="ri-school-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100 mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Unit Asrama</p>
                        <h3 class="fw-bold mb-1">{{ number_format($stats['dormitories_total']) }}</h3>
                        <span class="text-muted fs-12">Gedung / Unit Tinggal</span>
                    </div>
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="ri-home-4-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100 mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Izin Pending</p>
                        <h3 class="fw-bold mb-1 text-danger">{{ number_format($stats['permits_pending']) }}</h3>
                        <span class="text-muted fs-12">Menunggu Persetujuan</span>
                    </div>
                    <div class="stat-icon bg-danger-subtle text-danger">
                        <i class="ri-mail-warning-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── ROW 2: Charts ────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card chart-card h-100 mb-0">
            <div class="card-header align-items-center d-flex bg-transparent border-bottom-dashed">
                <h5 class="card-title mb-0 flex-grow-1"><i class="ri-line-chart-line text-primary me-2"></i>Tren Kedatangan Santri Masuk</h5>
            </div>
            <div class="card-body">
                <div id="chart-student-arrival" 
                     data-series="{{ json_encode([['name' => 'Santri Masuk', 'data' => $studentArrivalData]]) }}"
                     data-categories="{{ json_encode($studentArrivalLabels) }}"
                     data-colors='["#3577f1"]'></div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card chart-card h-100 mb-0">
            <div class="card-header align-items-center d-flex bg-transparent border-bottom-dashed">
                <h5 class="card-title mb-0 flex-grow-1"><i class="ri-pie-chart-2-line text-warning me-2"></i>Status Santri</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div id="chart-student-status" 
                     data-series="{{ json_encode($studentStatusData) }}"
                     data-labels="{{ json_encode($studentStatusLabels) }}"
                     data-colors='["#0ab39c","#405189","#f7b84b","#f06548"]'></div>
            </div>
        </div>
    </div>
</div>

{{-- ── ROW 3: Violations + Occupancy ─────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card chart-card h-100 mb-0">
            <div class="card-header align-items-center d-flex bg-transparent border-bottom-dashed">
                <h5 class="card-title mb-0 flex-grow-1"><i class="ri-alert-line text-danger me-2"></i>Tren Pelanggaran (6 Bulan Terakhir)</h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-danger-subtle text-danger fs-11">Berat: <strong>{{ collect($incidentHeavy)->sum() }}</strong></span>
                    <span class="badge bg-success-subtle text-success fs-11">Ringan: <strong>{{ collect($incidentLight)->sum() }}</strong></span>
                </div>
            </div>
            <div class="card-body">
                <div id="chart-incidents" 
                     data-series="{{ json_encode([['name' => 'Berat', 'data' => $incidentHeavy], ['name' => 'Ringan', 'data' => $incidentLight]]) }}"
                     data-categories="{{ json_encode($incidentLabels) }}"
                     data-colors='["#f06548", "#0ab39c"]'></div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card chart-card h-100 mb-0">
            <div class="card-header align-items-center d-flex bg-transparent border-bottom-dashed">
                <h5 class="card-title mb-0 flex-grow-1"><i class="ri-home-heart-line text-info me-2"></i>Kapasitas Asrama</h5>
                <span class="badge bg-info-subtle text-info fs-11">
                    {{ number_format(array_sum($occupancyCurrent)) }} / {{ number_format(array_sum($occupancyCap)) }}
                </span>
            </div>
            <div class="card-body">
                <div id="chart-occupancy" 
                     data-series="{{ json_encode([['name' => 'Terisi', 'data' => $occupancyCurrent], ['name' => 'Kapasitas', 'data' => $occupancyCap]]) }}"
                     data-categories="{{ json_encode($occupancyLabels) }}"
                     data-colors='["#3577f1", "#f7b84b"]'></div>
            </div>
        </div>
    </div>
</div>

{{-- ── ROW 4: Activity Tables ────────────────────────────────── --}}
<div class="row g-3 mb-3">
    {{-- Pelanggaran Terbaru --}}
    <div class="col-xl-4">
        <div class="card h-100 mb-0">
            <div class="card-header align-items-center d-flex bg-transparent border-bottom-dashed">
                <h5 class="card-title mb-0 flex-grow-1"><i class="ri-alert-fill text-danger me-1"></i> Pelanggaran Terbaru</h5>
                <a href="{{ route('system.violations.index') }}" class="btn btn-soft-secondary btn-sm">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                @if($recentIncidents->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-centered table-hover align-middle mb-0 table-activity">
                            <tbody>
                                @foreach($recentIncidents as $inc)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-title-initial bg-danger-subtle text-danger">
                                                {{ strtoupper(substr($inc->student?->name ?? 'N', 0, 1)) }}
                                            </div>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold fs-13">{{ $inc->student?->name ?? 'N/A' }}</span>
                                                <small class="text-muted fs-11">{{ $inc->room?->code ?? $inc->dormitory?->name ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $sevClass = match($inc->violation_category ?? '') {
                                                'berat'  => 'danger',
                                                'sedang' => 'warning',
                                                'ringan' => 'success',
                                                default  => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $sevClass }}-subtle text-{{ $sevClass }} badge-status text-uppercase">
                                            {{ $inc->violation_category ?? '-' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted text-center py-4 fs-13">Tidak ada data pelanggaran</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Izin Keluar Terbaru --}}
    <div class="col-xl-4">
        <div class="card h-100 mb-0">
            <div class="card-header align-items-center d-flex bg-transparent border-bottom-dashed">
                <h5 class="card-title mb-0 flex-grow-1"><i class="ri-mail-line text-primary me-1"></i> Izin Keluar Terbaru</h5>
                <a href="{{ route('system.permits.index') }}" class="btn btn-soft-secondary btn-sm">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                @if($recentPermits->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-centered table-hover align-middle mb-0 table-activity">
                            <tbody>
                                @foreach($recentPermits as $permit)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-title-initial bg-primary-subtle text-primary">
                                                {{ strtoupper(substr($permit->student?->name ?? 'N', 0, 1)) }}
                                            </div>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold fs-13">{{ $permit->student?->name ?? 'N/A' }}</span>
                                                <small class="text-muted fs-11">{{ $permit->dormitory?->name ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $statusColor = match($permit->status) {
                                                'pending'  => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                default    => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} badge-status text-uppercase me-1">
                                            {{ $permit->status }}
                                        </span>
                                        <a href="{{ route('permits.show', ['asramaUuid' => $permit->dormitory?->id ?? '', 'permitUuid' => $permit->id]) }}"
                                           class="btn btn-icon btn-sm btn-ghost-secondary rounded-circle" title="Detail">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted text-center py-4 fs-13">Tidak ada data izin keluar</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Kunjungan Terbaru --}}
    <div class="col-xl-4">
        <div class="card h-100 mb-0">
            <div class="card-header align-items-center d-flex bg-transparent border-bottom-dashed">
                <h5 class="card-title mb-0 flex-grow-1"><i class="ri-contacts-line text-success me-1"></i> Kunjungan Terbaru</h5>
                <a href="#" class="btn btn-soft-secondary btn-sm">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                @if($recentVisits->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-centered table-hover align-middle mb-0 table-activity">
                            <tbody>
                                @foreach($recentVisits as $visit)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-title-initial bg-success-subtle text-success">
                                                {{ strtoupper(substr($visit->student?->name ?? 'N', 0, 1)) }}
                                            </div>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold fs-13">{{ $visit->student?->name ?? 'N/A' }}</span>
                                                <small class="text-muted fs-11">Tamu: {{ $visit->visitor_name ?? 'N/A' }} ({{ $visit->visitor_relationship_text ?? '-' }})</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $vStatusColor = match($visit->status) {
                                                'pending'    => 'warning',
                                                'approved'   => 'success',
                                                'rejected'   => 'danger',
                                                'arrived'    => 'info',
                                                'checked_out'=> 'secondary',
                                                'cancelled'  => 'dark',
                                                default      => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $vStatusColor }}-subtle text-{{ $vStatusColor }} badge-status text-uppercase">
                                            {{ $visit->status_text ?? $visit->status }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted text-center py-4 fs-13">Tidak ada data kunjungan</div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    
    // 1. Chart: Tren Kedatangan Santri (Area)
    const elArrival = document.getElementById('chart-student-arrival');
    if (elArrival) {
        new ApexCharts(elArrival, {
            series: JSON.parse(elArrival.dataset.series),
            chart: { type: 'area', height: 290, toolbar: { show: false } },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
            colors: JSON.parse(elArrival.dataset.colors),
            xaxis: { categories: JSON.parse(elArrival.dataset.categories) },
            grid: { borderColor: '#f1f1f1' },
            dataLabels: { enabled: false }
        }).render();
    }

    // 2. Chart: Status Santri (Donut)
    const elStatus = document.getElementById('chart-student-status');
    if (elStatus) {
        new ApexCharts(elStatus, {
            series: JSON.parse(elStatus.dataset.series),
            labels: JSON.parse(elStatus.dataset.labels),
            chart: { type: 'donut', height: 260 },
            colors: JSON.parse(elStatus.dataset.colors),
            legend: { position: 'bottom' },
            plotOptions: { pie: { donut: { size: '68%' } } },
            dataLabels: { enabled: true, formatter: (val) => val.toFixed(1) + '%' }
        }).render();
    }

    // 3. Chart: Pelanggaran (Bar)
    const elIncidents = document.getElementById('chart-incidents');
    if (elIncidents) {
        new ApexCharts(elIncidents, {
            series: JSON.parse(elIncidents.dataset.series),
            chart: { type: 'bar', height: 290, toolbar: { show: false } },
            plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
            colors: JSON.parse(elIncidents.dataset.colors),
            xaxis: { categories: JSON.parse(elIncidents.dataset.categories) },
            grid: { borderColor: '#f1f1f1' },
            dataLabels: { enabled: false }
        }).render();
    }

    // 4. Chart: Kapasitas Asrama (Bar)
    const elOccupancy = document.getElementById('chart-occupancy');
    if (elOccupancy) {
        new ApexCharts(elOccupancy, {
            series: JSON.parse(elOccupancy.dataset.series),
            chart: { type: 'bar', height: 290, toolbar: { show: false } },
            plotOptions: { bar: { columnWidth: '50%', borderRadius: 4 } },
            colors: JSON.parse(elOccupancy.dataset.colors),
            xaxis: { categories: JSON.parse(elOccupancy.dataset.categories) },
            grid: { borderColor: '#f1f1f1' },
            dataLabels: { enabled: false }
        }).render();
    }
});
</script>
@endsection