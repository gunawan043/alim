@extends('layouts.master')

@section('title') Laporan Mutasi Santri @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Mutasi &amp; populasi @endslot
        @slot('title') Mutasi santri @endslot
    @endcomponent

    @php
        $monthly = collect($report['monthly'] ?? []);
        $monthlyTotals = [
            'in' => (int) $monthly->sum('in'),
            'out' => (int) $monthly->sum('out'),
            'internal' => (int) $monthly->sum('internal'),
        ];
        $net = $report['summary']['net'];
        $hasMutations = ($monthlyTotals['in'] + $monthlyTotals['out'] + $monthlyTotals['internal']) > 0;
        $periodeLabel = $report['period']['from']->locale('id')->translatedFormat('d M Y')
            .' — '.$report['period']['to']->locale('id')->translatedFormat('d M Y');
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Mutasi santri</h1>
                <p class="lap-head-desc">Mutasi masuk, pindahan, berhenti, kelulusan, dan perpindahan internal. Hanya mutasi berstatus disetujui yang dihitung.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.populasi.mutasi.export', array_merge(['userId' => $userId], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh Excel
                </a>
                <a href="{{ route('user.laporan.populasi.mutasi.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        @include('user.laporan.partials.population-tabs')

        <div class="card">
            <div class="card-body">
                @include('user.laporan.partials.population-filters', [
                    'filterRoute' => 'user.laporan.populasi.mutasi',
                    'showDates' => true,
                ])
            </div>
        </div>

        @include('user.laporan.partials.period', [
            'period' => $periodeLabel,
            'note' => $report['source_note'],
        ])

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Total peristiwa', 'value' => $report['summary']['total'], 'note' => 'seluruh jenis'],
            ['label' => 'Masuk', 'value' => $report['summary']['total_in'], 'note' => 'baru dan pindahan'],
            ['label' => 'Keluar', 'value' => $report['summary']['total_out'], 'note' => 'pindahan, berhenti, lulus'],
            ['label' => 'Internal', 'value' => $report['summary']['total_internal'], 'note' => 'unit, jenjang, rombel'],
            ['label' => 'Berhenti', 'value' => $report['summary']['berhenti'], 'note' => 'drop out'],
            [
                'label' => 'Selisih',
                'value' => ($net > 0 ? '+' : '').$net,
                'note' => 'masuk dikurangi keluar',
                'tone' => $net > 0 ? 'ok' : ($net < 0 ? 'bad' : null),
            ],
        ]])

        <div class="lap-grid lap-grid-main">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-swap-box-line"></i>Mutasi bulanan</h2>
                    <span class="lap-meta">{{ $monthly->count() }} bulan</span>
                </div>

                @if (! $hasMutations)
                    @include('user.laporan.partials.empty', [
                        'title' => 'Belum ada mutasi pada periode ini',
                        'desc' => 'Geser rentang tanggal atau pilih unit lain.',
                        'height' => 300,
                    ])
                @else
                    <div class="lap-chart-legend">
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#00416c"></span>Masuk <span class="lap-legend-value">{{ number_format($monthlyTotals['in'], 0, ',', '.') }}</span></span>
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#c3c9d1"></span>Keluar <span class="lap-legend-value">{{ number_format($monthlyTotals['out'], 0, ',', '.') }}</span></span>
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#8b949f"></span>Internal <span class="lap-legend-value">{{ number_format($monthlyTotals['internal'], 0, ',', '.') }}</span></span>
                    </div>
                    <div class="card-body pt-2">
                        <div id="mutationMonthlyChart" class="lap-chart"></div>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-bar-chart-box-line"></i>Per tahun ajaran</h2>
                </div>
                <div class="card-body p-0">
                    @if (blank($report['yearly']))
                        @include('user.laporan.partials.empty', [
                            'title' => 'Belum ada data tahunan',
                            'desc' => 'Data muncul setelah mutasi tercatat.',
                            'height' => 240,
                        ])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tahun</th>
                                        <th class="text-end">Masuk</th>
                                        <th class="text-end">Keluar</th>
                                        <th class="text-end">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['yearly'] as $row)
                                        @php $yearNet = (int) $row['in'] - (int) $row['out']; @endphp
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td class="text-end">{{ number_format($row['in'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['out'], 0, ',', '.') }}</td>
                                            <td class="text-end {{ $yearNet > 0 ? 'text-success' : ($yearNet < 0 ? 'text-danger' : 'muted') }}">
                                                {{ $yearNet > 0 ? '+' : '' }}{{ number_format($yearNet, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="lap-grid lap-grid-3">
            @foreach ([
                ['title' => 'Berdasarkan unit', 'first' => 'Unit', 'rows' => $report['by_unit']],
                ['title' => 'Berdasarkan jenjang', 'first' => 'Jenjang', 'rows' => $report['by_grade']],
                ['title' => 'Berdasarkan kelas', 'first' => 'Kelas', 'rows' => $report['by_class']],
            ] as $block)
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="ri-bar-chart-box-line"></i>{{ $block['title'] }}</h2>
                    </div>
                    <div class="card-body p-0">
                        @if (blank($block['rows']))
                            @include('user.laporan.partials.empty', [
                                'title' => 'Belum ada data',
                                'desc' => 'Tidak ada mutasi pada kelompok ini.',
                                'height' => 160,
                            ])
                        @else
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ $block['first'] }}</th>
                                            <th class="text-end">Masuk</th>
                                            <th class="text-end">Keluar</th>
                                            <th class="text-end">Internal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($block['rows'] as $row)
                                            <tr>
                                                <td>{{ $row['name'] }}</td>
                                                <td class="text-end">{{ number_format($row['in'], 0, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format($row['out'], 0, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format($row['internal'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
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

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="ri-list-check-2-line"></i>Detail mutasi</h2>
                <span class="lap-meta">{{ number_format($report['row_count'], 0, ',', '.') }} baris</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:56px">No</th>
                                <th style="width:100px">Tanggal</th>
                                <th style="width:120px">Jenis</th>
                                <th>Santri</th>
                                <th>Unit</th>
                                <th>Jenjang</th>
                                <th>Kelas</th>
                                <th>Dari</th>
                                <th>Ke</th>
                                <th>Alasan</th>
                                <th>Sumber</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="muted">{{ $rows->firstItem() + $loop->index }}</td>
                                    <td class="text-nowrap">{{ $row['date'] ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '—' }}</td>
                                    <td><span class="badge">{{ $row['type_label'] }}</span></td>
                                    <td>
                                        <div class="lap-table-name">{{ $row['student_name'] ?: '—' }}</div>
                                        <div class="lap-table-sub">{{ $row['nisn'] ?: '—' }}</div>
                                    </td>
                                    <td>{{ $row['school_name'] ?: '—' }}</td>
                                    <td>{{ $row['grade_name'] ?: '—' }}</td>
                                    <td>{{ $row['class_name'] ?: '—' }}</td>
                                    <td>{{ $row['from_name'] ?: '—' }}</td>
                                    <td>{{ $row['to_name'] ?: '—' }}</td>
                                    <td>{{ $row['reason'] ?: '—' }}</td>
                                    <td class="muted">{{ $row['source'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Belum ada mutasi',
                                            'desc' => 'Tidak ada mutasi pada periode atau saringan ini.',
                                        ])
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($rows->hasPages())
                <div class="card-footer">
                    @include('shared._pagination', ['paginator' => $rows])
                </div>
            @endif
        </div>
    </div>
@endsection

@section('script')
    @include('user.laporan.partials._script')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    @if ($hasMutations)
        <script>
            (function () {
                var rows = @json($report['monthly']);

                window.lapChart('mutationMonthlyChart', {
                    chart: { type: 'bar', height: 320 },
                    series: [
                        { name: 'Masuk', data: rows.map(function (r) { return r.in; }) },
                        { name: 'Keluar', data: rows.map(function (r) { return r.out; }) },
                        { name: 'Internal', data: rows.map(function (r) { return r.internal; }) }
                    ],
                    colors: ['#00416c', '#c3c9d1', '#8b949f'],
                    plotOptions: { bar: { columnWidth: '58%', borderRadius: 2, borderRadiusApplication: 'end' } },
                    xaxis: {
                        categories: rows.map(function (r) { return r.label; }),
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { rotate: 0, style: { colors: window.lapMuted(), fontSize: '11px' } }
                    },
                    yaxis: {
                        labels: { style: { colors: window.lapMuted(), fontSize: '11px' }, formatter: function (v) { return window.lapFormat(v); } }
                    },
                    tooltip: {
                        custom: function (ctx) {
                            var row = rows[ctx.dataPointIndex] || {};
                            return window.lapTooltip(ctx.opts.xaxis.categories[ctx.dataPointIndex], [
                                ['Masuk', window.lapFormat(row.in)],
                                ['Keluar', window.lapFormat(row.out)],
                                ['Internal', window.lapFormat(row.internal)]
                            ]);
                        }
                    }
                });
            })();
        </script>
    @endif
@endsection
