@extends('layouts.master')

@section('title') Laporan Mutasi Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Mutasi & Populasi @endslot
        @slot('title') Mutasi Santri @endslot
    @endcomponent

    @include('user.laporan.partials.population-tabs')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <p class="text-muted mb-0 small">
            Mutasi masuk, pindahan, berhenti, kelulusan, dan perpindahan internal. Hanya mutasi berstatus disetujui yang dihitung.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.laporan.populasi.mutasi.export', array_merge(['userId' => $userId], request()->query())) }}" class="btn btn-success btn-sm">
                <i class="ri-file-excel-2-line me-1"></i>Excel
            </a>
            <a href="{{ route('user.laporan.populasi.mutasi.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="btn btn-danger btn-sm">
                <i class="ri-file-pdf-2-line me-1"></i>PDF
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            @include('user.laporan.partials.population-filters', [
                'filterRoute' => 'user.laporan.populasi.mutasi',
                'showDates' => true,
            ])
        </div>
    </div>

    <div class="alert alert-light border small d-flex flex-wrap justify-content-between gap-2">
        <span>Periode: <strong>{{ $report['period']['from']->format('d/m/Y') }} — {{ $report['period']['to']->format('d/m/Y') }}</strong></span>
        <span>{{ $report['source_note'] }}</span>
    </div>

    <div class="row g-3 mb-3">
        @php
            $cards = [
                ['label' => 'Total Peristiwa', 'value' => $report['summary']['total'], 'class' => 'text-primary', 'note' => 'seluruh jenis'],
                ['label' => 'Masuk', 'value' => $report['summary']['total_in'], 'class' => 'text-success', 'note' => 'baru + pindahan'],
                ['label' => 'Keluar', 'value' => $report['summary']['total_out'], 'class' => 'text-danger', 'note' => 'pindahan + berhenti + lulus'],
                ['label' => 'Internal', 'value' => $report['summary']['total_internal'], 'class' => 'text-info', 'note' => 'unit / jenjang / rombel'],
                ['label' => 'Berhenti', 'value' => $report['summary']['berhenti'], 'class' => 'text-warning', 'note' => 'drop out'],
                ['label' => 'Selisih', 'value' => ($report['summary']['net'] > 0 ? '+' : '').$report['summary']['net'], 'class' => $report['summary']['net'] < 0 ? 'text-danger' : 'text-success', 'note' => 'masuk - keluar'],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="col-6 col-xl-2">
                <div class="card h-100"><div class="card-body py-3">
                    <div class="text-muted small text-uppercase fw-semibold">{{ $card['label'] }}</div>
                    <div class="fs-3 fw-bold {{ $card['class'] }}">{{ number_format($card['value']) }}</div>
                    <div class="small text-muted">{{ $card['note'] }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-bar-chart-grouped-line me-1 text-primary"></i>Grafik Mutasi Bulanan</h5></div>
                <div class="card-body"><div id="mutationMonthlyChart"></div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-calendar-2-line me-1 text-info"></i>Grafik Tahunan</h5></div>
                <div class="card-body"><div id="mutationYearlyChart"></div></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Berdasarkan Unit</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Unit</th><th class="text-center">Masuk</th><th class="text-center">Keluar</th><th class="text-center">Internal</th></tr></thead>
                    <tbody>
                        @forelse($report['by_unit'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td class="text-center text-success">{{ $row['in'] }}</td><td class="text-center text-danger">{{ $row['out'] }}</td><td class="text-center text-info">{{ $row['internal'] }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Berdasarkan Jenjang</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Jenjang</th><th class="text-center">Masuk</th><th class="text-center">Keluar</th><th class="text-center">Internal</th></tr></thead>
                    <tbody>
                        @forelse($report['by_grade'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td class="text-center text-success">{{ $row['in'] }}</td><td class="text-center text-danger">{{ $row['out'] }}</td><td class="text-center text-info">{{ $row['internal'] }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Berdasarkan Kelas</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Kelas</th><th class="text-center">Masuk</th><th class="text-center">Keluar</th><th class="text-center">Internal</th></tr></thead>
                    <tbody>
                        @forelse($report['by_class'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td class="text-center text-success">{{ $row['in'] }}</td><td class="text-center text-danger">{{ $row['out'] }}</td><td class="text-center text-info">{{ $row['internal'] }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>

    @if($report['findings']->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Catatan Data</h5></div>
            <div class="card-body">
                @foreach($report['findings'] as $finding)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div><strong>{{ $finding['title'] }}</strong><div class="small text-muted">{{ $finding['description'] }}</div></div>
                        <span class="badge bg-warning-subtle text-warning">{{ number_format($finding['count']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-table-line me-1 text-primary"></i>Detail Mutasi</h5>
            <span class="small text-muted">{{ number_format($report['row_count']) }} baris</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>Tanggal</th><th>Jenis</th><th>Santri</th><th>Unit</th><th>Jenjang</th><th>Kelas</th><th>Dari</th><th>Ke</th><th>Alasan</th><th>Sumber</th></tr></thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $rows->firstItem() + $loop->index }}</td>
                                <td class="text-nowrap">{{ $row['date'] ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '—' }}</td>
                                <td><span class="badge bg-primary-subtle text-primary">{{ $row['type_label'] }}</span></td>
                                <td><div class="fw-medium">{{ $row['student_name'] ?: '—' }}</div><div class="small text-muted">{{ $row['nisn'] ?: '—' }}</div></td>
                                <td>{{ $row['school_name'] ?: '—' }}</td>
                                <td>{{ $row['grade_name'] ?: '—' }}</td>
                                <td>{{ $row['class_name'] ?: '—' }}</td>
                                <td>{{ $row['from_name'] ?: '—' }}</td>
                                <td>{{ $row['to_name'] ?: '—' }}</td>
                                <td class="small">{{ $row['reason'] ?: '—' }}</td>
                                <td class="small text-muted">{{ $row['source'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted py-5">Belum ada mutasi pada periode/filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rows->hasPages())
            <div class="card-footer">@include('shared._pagination', ['paginator' => $rows])</div>
        @endif
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    (function () {
        const monthly = @json($report['monthly']);
        const yearly = @json($report['yearly']);
        const monthlyEl = document.querySelector('#mutationMonthlyChart');
        const yearlyEl = document.querySelector('#mutationYearlyChart');

        if (monthlyEl && monthly.length) {
            new ApexCharts(monthlyEl, {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [
                    { name: 'Masuk', data: monthly.map(r => r.in) },
                    { name: 'Keluar', data: monthly.map(r => r.out) },
                    { name: 'Internal', data: monthly.map(r => r.internal) }
                ],
                xaxis: { categories: monthly.map(r => r.label) },
                colors: ['#20c997', '#dc3545', '#0dcaf0'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '58%' } },
                dataLabels: { enabled: false },
                legend: { position: 'top' },
                grid: { borderColor: '#e2e8f0' }
            }).render();
        } else if (monthlyEl) {
            monthlyEl.innerHTML = '<div class="text-center text-muted py-5">Belum ada data bulanan.</div>';
        }

        if (yearlyEl && yearly.length) {
            new ApexCharts(yearlyEl, {
                chart: { type: 'line', height: 320, toolbar: { show: false } },
                series: [
                    { name: 'Masuk', data: yearly.map(r => r.in) },
                    { name: 'Keluar', data: yearly.map(r => r.out) },
                    { name: 'Internal', data: yearly.map(r => r.internal) }
                ],
                xaxis: { categories: yearly.map(r => r.label) },
                colors: ['#20c997', '#dc3545', '#0dcaf0'],
                stroke: { curve: 'smooth', width: 2 },
                dataLabels: { enabled: false },
                legend: { position: 'top' },
                grid: { borderColor: '#e2e8f0' }
            }).render();
        } else if (yearlyEl) {
            yearlyEl.innerHTML = '<div class="text-center text-muted py-5">Belum ada data tahunan.</div>';
        }
    })();
</script>
@endsection
