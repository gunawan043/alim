@extends('layouts.master')

@section('title') Laporan Pertumbuhan Populasi Santri @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Mutasi &amp; populasi @endslot
        @slot('title') Pertumbuhan populasi @endslot
    @endcomponent

    @php
        $summary = $report['summary'];
        $series = collect($report['series'] ?? []);
        $hasPopulation = ((int) $summary['opening'] + (int) $summary['closing']) > 0;
        $growthNote = $summary['growth_rate'] !== null
            ? number_format($summary['growth_rate'], 2, ',', '.').'% dari awal tahun'
            : ($summary['opening'] == 0 ? 'tahun pertama data' : 'persentase belum tersedia');
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Pertumbuhan populasi</h1>
                <p class="lap-head-desc">Rekonsiliasi populasi awal dan akhir tahun ajaran. Santri dihitung satu kali berdasarkan riwayat kelas unik.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.populasi.pertumbuhan.export', array_merge(['userId' => $userId], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh Excel
                </a>
                <a href="{{ route('user.laporan.populasi.pertumbuhan.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        @include('user.laporan.partials.population-tabs')

        <div class="card">
            <div class="card-body">
                @include('user.laporan.partials.population-filters', ['filterRoute' => 'user.laporan.populasi.pertumbuhan'])
            </div>
        </div>

        @include('user.laporan.partials.period', [
            'period' => $report['academic_year']->name,
            'note' => 'Awal tahun = populasi tahun sebelumnya. Akhir tahun = awal + baru + mutasi masuk − mutasi keluar − berhenti − lulusan.',
        ])

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Awal tahun', 'value' => $summary['opening'], 'note' => $report['previous_year']?->name ?? 'awal data'],
            ['label' => 'Santri baru', 'value' => $summary['new'], 'note' => 'pendaftaran baru'],
            ['label' => 'Akhir tahun', 'value' => $summary['closing'], 'note' => 'hasil rekonsiliasi'],
            [
                'label' => 'Pertumbuhan bersih',
                'value' => ($summary['net_growth'] > 0 ? '+' : '').$summary['net_growth'],
                'note' => $growthNote,
                'tone' => $summary['net_growth'] > 0 ? 'ok' : ($summary['net_growth'] < 0 ? 'bad' : null),
            ],
        ]])

        <div class="lap-grid lap-grid-main">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-bar-chart-box-line"></i>Populasi per tahun ajaran</h2>
                    <span class="lap-meta">{{ $series->count() }} tahun</span>
                </div>

                @if (! $hasPopulation)
                    @include('user.laporan.partials.empty', [
                        'title' => 'Belum ada riwayat tahun ajaran',
                        'desc' => 'Data muncul setelah populasi tercatat pada suatu tahun ajaran.',
                        'height' => 300,
                    ])
                @else
                    <div class="lap-chart-legend">
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#8b949f"></span>Awal tahun <span class="lap-legend-value">{{ number_format($summary['opening'], 0, ',', '.') }}</span></span>
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#00416c"></span>Akhir tahun <span class="lap-legend-value">{{ number_format($summary['closing'], 0, ',', '.') }}</span></span>
                    </div>
                    <div class="card-body pt-2">
                        <div id="growthChart" class="lap-chart"></div>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-bar-chart-box-line"></i>Rekonsiliasi</h2>
                </div>
                <div class="card-body">
                    <div class="lap-list">
                        <div class="lap-list-row">
                            <span class="lap-list-label">Populasi awal</span>
                            <span class="lap-list-value">{{ number_format($summary['opening'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Santri baru</span>
                            <span class="lap-list-value">+{{ number_format($summary['new'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Mutasi masuk</span>
                            <span class="lap-list-value">+{{ number_format($summary['mutation_in'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Mutasi keluar</span>
                            <span class="lap-list-value">−{{ number_format($summary['mutation_out'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Berhenti</span>
                            <span class="lap-list-value">−{{ number_format($summary['dropout'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Lulusan</span>
                            <span class="lap-list-value">−{{ number_format($summary['graduates'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label"><strong>Hasil rumus</strong></span>
                            <span class="lap-list-value">{{ number_format($summary['closing'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Populasi terdaftar</span>
                            <span class="lap-list-value">{{ number_format($summary['current_population'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Selisih</span>
                            <span class="lap-list-value {{ $summary['reconciliation_difference'] != 0 ? 'is-warn' : 'is-ok' }}">
                                {{ number_format($summary['reconciliation_difference'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    <p class="lap-note mt-3">Selisih bukan otomatis kesalahan. Dapat berasal dari riwayat kelas atau mutasi yang belum lengkap.</p>
                </div>
            </div>
        </div>

        @if ($report['findings']->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-bar-chart-box-line"></i>Catatan data</h2>
                    <span class="lap-meta">{{ $report['findings']->count() }} temuan</span>
                </div>
                <div class="card-body">
                    <div class="lap-list">
                        @foreach ($report['findings'] as $finding)
                            <div class="lap-list-row">
                                <span class="lap-list-label">
                                    {{ $finding['title'] }}
                                    @if (! empty($finding['description']))
                                        <span class="lap-table-sub d-block">{{ $finding['description'] }}</span>
                                    @endif
                                </span>
                                <span class="lap-list-value">{{ number_format($finding['count'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @foreach (['unit' => 'unit', 'grade' => 'jenjang', 'level' => 'kelompok pendidikan'] as $key => $label)
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-line-chart-line"></i>Pertumbuhan per {{ $label }}</h2>
                </div>
                <div class="card-body p-0">
                    @if (blank($report['breakdown'][$key]))
                        @include('user.laporan.partials.empty', [
                            'title' => 'Belum ada data',
                            'desc' => 'Tidak ada populasi tercatat pada kelompok ini.',
                            'height' => 140,
                        ])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ ucfirst($label) }}</th>
                                        <th class="text-end">Awal</th>
                                        <th class="text-end">Akhir</th>
                                        <th class="text-end">Bersih</th>
                                        <th class="text-end">Pertumbuhan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['breakdown'][$key] as $row)
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-end">{{ number_format($row['opening'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['closing'], 0, ',', '.') }}</td>
                                            <td class="text-end {{ $row['net'] < 0 ? 'text-danger' : ($row['net'] > 0 ? 'text-success' : 'muted') }}">
                                                {{ $row['net'] > 0 ? '+' : '' }}{{ number_format($row['net'], 0, ',', '.') }}
                                            </td>
                                            <td class="text-end muted">{{ $row['rate'] !== null ? number_format($row['rate'], 2, ',', '.').'%' : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="ri-line-chart-line"></i>Riwayat pertumbuhan</h2>
                <span class="lap-meta">{{ $series->count() }} tahun ajaran</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tahun ajaran</th>
                                <th class="text-end">Awal</th>
                                <th class="text-end">Baru</th>
                                <th class="text-end">Masuk</th>
                                <th class="text-end">Keluar</th>
                                <th class="text-end">Berhenti</th>
                                <th class="text-end">Lulus</th>
                                <th class="text-end">Akhir</th>
                                <th class="text-end">Bersih</th>
                                <th class="text-end">Pertumbuhan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($series as $row)
                                <tr class="{{ $row['id'] === $report['academic_year']->name ? 'table-active' : '' }}">
                                    <td>{{ $row['name'] }}</td>
                                    <td class="text-end">{{ number_format($row['opening'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['new'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['mutation_in'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['mutation_out'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['dropout'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['graduates'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['closing'], 0, ',', '.') }}</td>
                                    <td class="text-end {{ $row['net_growth'] < 0 ? 'text-danger' : ($row['net_growth'] > 0 ? 'text-success' : 'muted') }}">
                                        {{ $row['net_growth'] > 0 ? '+' : '' }}{{ number_format($row['net_growth'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end muted">{{ $row['growth_rate'] !== null ? number_format($row['growth_rate'], 2, ',', '.').'%' : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Belum ada riwayat',
                                            'desc' => 'Data muncul setelah populasi tercatat.',
                                        ])
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @include('user.laporan.partials._script')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    @if ($hasPopulation)
        <script>
            (function () {
                var rows = @json($report['series']);

                window.lapChart('growthChart', {
                    chart: { type: 'area', height: 300 },
                    series: [
                        { name: 'Awal tahun', data: rows.map(function (r) { return r.opening; }) },
                        { name: 'Akhir tahun', data: rows.map(function (r) { return r.closing; }) }
                    ],
                    colors: ['#8b949f', '#00416c'],
                    stroke: { curve: 'smooth', width: [1.5, 2.5], dashArray: [4, 0] },
                    fill: {
                        type: ['solid', 'gradient'],
                        opacity: [0, 0.16],
                        gradient: { shadeIntensity: 1, opacityFrom: .16, opacityTo: .02, stops: [0, 90, 100] }
                    },
                    xaxis: {
                        categories: rows.map(function (r) { return r.name; }),
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { rotate: 0, style: { colors: window.lapMuted(), fontSize: '11px' } }
                    },
                    yaxis: {
                        labels: { style: { colors: window.lapMuted(), fontSize: '11px' }, formatter: function (v) { return window.lapFormat(v); } }
                    },
                    markers: { size: 3, strokeWidth: 0, hover: { size: 5 } },
                    tooltip: {
                        custom: function (ctx) {
                            var row = rows[ctx.dataPointIndex] || {};
                            return window.lapTooltip(row.name, [
                                ['Awal tahun', window.lapFormat(row.opening)],
                                ['Akhir tahun', window.lapFormat(row.closing)],
                                ['Bersih', (row.net_growth > 0 ? '+' : '') + window.lapFormat(row.net_growth)]
                            ]);
                        }
                    }
                });
            })();
        </script>
    @endif
@endsection
