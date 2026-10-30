@extends('layouts.master')

@section('title', 'Rekap Absensi Bulanan')

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">Absensi Harian</a> @endslot
        @slot('title') Rekap Bulanan @endslot
    @endcomponent

    {{-- FLASH --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- HEADER HALAMAN --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">Rekap Absensi Bulanan</h5>
                    <p class="text-muted mb-0 small">Rekapitulasi absensi per bulan per rombel (format Excel).</p>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD FILTER --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="mb-0">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Rombel</label>
                        <select name="study_group_id" class="form-select">
                            <option value="">— Pilih Rombel —</option>
                            @foreach($studyGroups as $sg)
                                <option value="{{ $sg->id }}" {{ $selectedStudyGroupId == $sg->id ? 'selected' : '' }}>
                                    {{ $sg->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1">Bulan</label>
                        <select name="month" class="form-select">
                            @foreach($months as $m)
                                <option value="{{ $m['value'] }}" {{ $selectedMonth == $m['value'] ? 'selected' : '' }}>
                                    {{ $m['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1">Tahun</label>
                        <input type="number" name="year" class="form-control"
                               value="{{ $selectedYear }}" min="2020" max="2030">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1">Semester</label>
                        <select name="semester" class="form-select">
                            <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                            <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Genap</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Tahun Ajaran</label>
                        <select name="academic_year_id" class="form-select">
                            @foreach(($academicYears ?? collect()) as $ay)
                                <option value="{{ $ay->id }}" {{ (string) ($selectedAyId ?? '') === (string) $ay->id ? 'selected' : '' }}>
                                    {{ $ay->name }} ({{ $ay->semester_text }}){{ $ay->is_active ? ' — Aktif' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="ri-search-line me-1"></i> Tampilkan
                        </button>
                        @if($selectedStudyGroupId)
                            <button type="submit" name="export" value="1" class="btn btn-success flex-shrink-0">
                                <i class="ri-file-excel-2-line me-1"></i> Export Excel
                            </button>
                        @endif
                        <a href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}"
                           class="btn btn-light flex-shrink-0" title="Kembali ke Absensi Harian">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TREN KEHADIRAN --}}
    @if($selectedStudyGroupId)
        <div class="card mb-3">
            <div class="card-header border-bottom-dashed">
                <div class="row g-3 align-items-center">
                    <div class="col-sm">
                        <h5 class="card-title mb-0"><i class="ri-line-chart-line text-primary me-1"></i>Tren Kehadiran Santri</h5>
                        <p class="text-muted mb-0 small">Jumlah hari per status (hadir, terlambat, izin, sakit, alpa) per hari, pekan, atau bulan.</p>
                    </div>
                    <div class="col-sm-auto">
                        <div class="btn-group" role="group" aria-label="Mode grafik kehadiran">
                            <button type="button" class="btn btn-outline-primary" data-trend-mode="daily">
                                Hari
                            </button>
                            <button type="button" class="btn btn-primary active" data-trend-mode="weekly">
                                Pekan
                            </button>
                            <button type="button" class="btn btn-outline-primary" data-trend-mode="monthly">
                                Bulan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="attendanceTrendChart" style="min-height:340px"></div>
            </div>
        </div>
    @endif

    {{-- TABEL --}}
    @if($selectedStudyGroupId)
        <div class="card">
            <div class="card-body">
                {{-- COUNTER --}}
                <div class="d-flex align-items-center gap-2 mb-2">
                    <small class="text-muted">
                        <i class="ri-file-list-line me-1"></i> Menampilkan
                        <strong>{{ count($rekapData) }}</strong> santri
                    </small>
                </div>

                <div class="table-container">
                    <table class="table table-hover align-middle table-freeze mb-0" style="font-size:13px;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:50px;">No</th>
                                <th style="width:100px;">NIS</th>
                                <th>Nama Lengkap</th>
                                <th class="text-center" style="width:70px;">JK</th>
                                <th class="text-center"><i class="ri-checkbox-circle-line me-1 text-success"></i>Hadir</th>
                                <th class="text-center"><i class="ri-timer-line me-1 text-warning"></i>Terlambat</th>
                                <th class="text-center"><i class="ri-information-line me-1 text-info"></i>Izin</th>
                                <th class="text-center"><i class="ri-hospital-line me-1 text-secondary"></i>Sakit</th>
                                <th class="text-center"><i class="ri-close-circle-line me-1 text-danger"></i>Alpa</th>
                                <th class="text-center">Total Hari</th>
                                <th class="text-center">% Kehadiran</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rekapData as $idx => $row)
                                @php
                                    $total = $row['hadir'] + $row['terlambat'] + $row['izin'] + $row['sakit'] + $row['alpa'];
                                    $persen = $total > 0 ? round((($row['hadir'] + $row['terlambat']) / $total) * 100, 1) : 0;
                                    $persenClass = $persen >= 90 ? 'success' : ($persen >= 75 ? 'warning' : 'danger');
                                @endphp
                                <tr>
                                    <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                    <td><code class="text-dark">{{ $row['nis'] ?? '—' }}</code></td>
                                    <td>
                                        <a href="{{ route('user.absensi.harian.show', [
                                            'userId' => $userId,
                                            'studentUuid' => $row['student_id'] ?? '',
                                        ]) }}" class="text-decoration-none fw-semibold">
                                            {{ $row['name'] }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $row['gender'] === 'L' ? 'info' : 'danger' }}-subtle text-{{ $row['gender'] === 'L' ? 'info' : 'danger' }}">
                                            {{ $row['gender'] }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-semibold text-success">{{ $row['hadir'] }}</td>
                                    <td class="text-center text-warning">{{ $row['terlambat'] }}</td>
                                    <td class="text-center text-info">{{ $row['izin'] }}</td>
                                    <td class="text-center text-secondary">{{ $row['sakit'] }}</td>
                                    <td class="text-center text-danger">{{ $row['alpa'] }}</td>
                                    <td class="text-center fw-semibold">{{ $total }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $persenClass }}-subtle text-{{ $persenClass }}">{{ $persen }}%</span>
                                    </td>
                                    <td></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center py-5">
                                        <lord-icon
                                            src="https://cdn.lordicon.com/msoeawqm.json"
                                            trigger="loop"
                                            colors="primary:#121331,secondary:#08a88a"
                                            style="width:75px;height:75px">
                                        </lord-icon>
                                        <h6 class="text-muted mb-1 mt-3">Tidak ada data absensi</h6>
                                        <p class="text-muted mb-0 small">Pastikan absensi harian sudah diinput untuk periode ini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <lord-icon
                    src="https://cdn.lordicon.com/msoeawqm.json"
                    trigger="loop"
                    colors="primary:#121331,secondary:#08a88a"
                    style="width:75px;height:75px">
                </lord-icon>
                <h6 class="text-muted mb-1 mt-3">Silakan pilih rombel terlebih dahulu</h6>
                <p class="text-muted mb-0 small">Pilih rombel pada filter di atas untuk menampilkan rekap.</p>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartEl = document.getElementById('attendanceTrendChart');
            if (!chartEl || !window.ApexCharts) return;

            const trend = @json($attendanceTrend ?? ['daily' => [], 'weekly' => [], 'monthly' => []]);
            let currentMode = 'weekly';
            let currentRows = trend[currentMode] || [];

            const seriesConfig = [
                { key: 'hadir',     name: 'Hadir',     color: '#0ab39c' },
                { key: 'terlambat', name: 'Terlambat', color: '#f7b84b' },
                { key: 'izin',      name: 'Izin',      color: '#299cdb' },
                { key: 'sakit',     name: 'Sakit',     color: '#6c757d' },
                { key: 'alpa',      name: 'Alpa',      color: '#f06548' },
            ];

            function buildSeries(rows) {
                return seriesConfig.map(function (s) {
                    return {
                        name: s.name,
                        data: rows.map(function (row) { return Number(row[s.key]) || 0; }),
                    };
                });
            }

            const chart = new ApexCharts(chartEl, {
                chart: {
                    type: 'line',
                    height: 340,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                series: buildSeries(currentRows),
                stroke: { curve: 'smooth', width: 3 },
                colors: seriesConfig.map(function (s) { return s.color; }),
                markers: { size: 4, hover: { size: 6 } },
                dataLabels: { enabled: false },
                xaxis: { categories: currentRows.map(function (row) { return row.label; }) },
                yaxis: {
                    min: 0,
                    labels: { formatter: function (value) { return Math.round(value); } },
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    markers: { radius: 12 },
                },
                grid: { strokeDashArray: 4, borderColor: '#eef2f7' },
                tooltip: {
                    shared: true,
                    intersect: false,
                    custom: function ({ dataPointIndex }) {
                        const row = currentRows[dataPointIndex] || {};
                        const rowsHtml = seriesConfig.map(function (s) {
                            return '<div class="d-flex justify-content-between align-items-center gap-2">'
                                + '<span><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:' + s.color + '"></span>' + s.name + '</span>'
                                + '<strong>' + (row[s.key] || 0) + '</strong>'
                                + '</div>';
                        }).join('');
                        return '<div class="p-2" style="font-size:12px;min-width:220px">'
                            + '<div class="fw-semibold mb-1">' + (row.label || '') + (row.range ? ' (' + row.range + ')' : '') + '</div>'
                            + rowsHtml
                            + '<div class="border-top mt-1 pt-1 d-flex justify-content-between">'
                            + '<span>Total hari</span><strong>' + (row.total || 0) + '</strong>'
                            + '</div>'
                            + '<div class="d-flex justify-content-between text-success fw-semibold">'
                            + '<span>Kehadiran</span><strong>' + (row.percent || 0) + '%</strong>'
                            + '</div>'
                            + '</div>';
                    },
                },
                noData: { text: 'Belum ada data absensi pada periode ini.' },
            });

            function renderMode(mode) {
                currentMode = mode;
                currentRows = trend[mode] || [];
                chart.updateOptions({
                    xaxis: { categories: currentRows.map(function (row) { return row.label; }) },
                    noData: { text: 'Belum ada data absensi pada periode ini.' },
                });
                chart.updateSeries(buildSeries(currentRows));
            }

            document.querySelectorAll('[data-trend-mode]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.querySelectorAll('[data-trend-mode]').forEach(function (item) {
                        item.classList.remove('active', 'btn-primary');
                        item.classList.add('btn-outline-primary');
                    });
                    this.classList.add('active', 'btn-primary');
                    this.classList.remove('btn-outline-primary');
                    renderMode(this.dataset.trendMode);
                });
            });

            chart.render();
        });
    </script>
@endpush