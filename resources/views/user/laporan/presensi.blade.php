@extends('layouts.master')

@section('title') Laporan Presensi @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Presensi GTK @endslot
    @endcomponent

    @php
        $statusLabels = [
            'hadir' => 'Hadir',
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'alpa' => 'Alpa',
            'cuti' => 'Cuti',
            'dinas_luar' => 'Dinas luar',
        ];

        $tidakHadir = $summary['sakit'] + $summary['izin'] + $summary['alpa'];

        $trendRows = collect($trend ?? []);
        $trendTotals = [
            'hadir' => (int) $trendRows->sum('hadir'),
            'terlambat' => (int) $trendRows->sum('terlambat'),
            'izin' => (int) $trendRows->sum('izin'),
            'sakit' => (int) $trendRows->sum('sakit'),
            'alpa' => (int) $trendRows->sum('alpa'),
        ];

        $periodeLabel = \Illuminate\Support\Carbon::parse($dari)->locale('id')->translatedFormat('d M Y')
            .' — '.\Illuminate\Support\Carbon::parse($sampai)->locale('id')->translatedFormat('d M Y');
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Presensi GTK</h1>
                <p class="lap-head-desc">Rekap kehadiran harian GTK: hadir, terlambat, izin, sakit, dan alpa pada rentang tanggal yang dipilih.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.export', array_merge(['userId' => $userId, 'type' => 'presensi'], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh CSV
                </a>
                <a href="{{ route('user.laporan.pdf', array_merge(['userId' => $userId, 'type' => 'presensi'], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('user.laporan.presensi', ['userId' => $userId]) }}" class="lap-filters">
                    <div class="lap-field">
                        <label class="form-label" for="lap-dari">Dari tanggal</label>
                        <input type="date" id="lap-dari" name="dari" class="form-control" value="{{ $dari }}">
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-sampai">Sampai tanggal</label>
                        <input type="date" id="lap-sampai" name="sampai" class="form-control" value="{{ $sampai }}">
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-status">Status</label>
                        <select id="lap-status" name="status" class="form-select">
                            <option value="">Semua status</option>
                            @foreach ($statusLabels as $val => $label)
                                <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-q">Cari GTK</label>
                        <input type="text" id="lap-q" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama GTK">
                    </div>
                    <div class="lap-actions">
                        <button type="submit" class="lap-btn lap-btn-primary">Terapkan</button>
                        <a href="{{ route('user.laporan.presensi', ['userId' => $userId]) }}" class="lap-btn lap-btn-icon" title="Atur ulang" aria-label="Atur ulang saringan">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @include('user.laporan.partials.period', [
            'period' => $periodeLabel,
            'note' => 'Hanya catatan presensi yang sudah tersimpan yang dihitung.',
        ])

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Total catatan', 'value' => $summary['total'], 'note' => 'satu baris per GTK per hari'],
            [
                'label' => 'Hadir',
                'value' => $summary['hadir'],
                'note' => $summary['total'] > 0 ? round($summary['hadir'] / $summary['total'] * 100).'% dari catatan' : 'belum ada catatan',
                'tone' => $summary['total'] > 0 && $summary['hadir'] / $summary['total'] >= 0.9 ? 'ok' : null,
            ],
            ['label' => 'Terlambat', 'value' => $summary['terlambat'], 'note' => 'masuk melewati jam', 'tone' => $summary['terlambat'] > 0 ? 'warn' : null],
            ['label' => 'Tidak hadir', 'value' => $tidakHadir, 'note' => 'Sakit '.$summary['sakit'].' · Izin '.$summary['izin'].' · Alpa '.$summary['alpa'], 'tone' => $tidakHadir > 0 ? 'bad' : null],
        ]])

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="ri-line-chart-line"></i>Tren harian</h2>
                <span class="lap-meta">{{ $trendRows->count() }} hari tercatat</span>
            </div>

            @if ($trendRows->isEmpty())
                @include('user.laporan.partials.empty', [
                    'title' => 'Belum ada catatan pada periode ini',
                    'desc' => 'Geser rentang tanggal atau atur ulang saringan.',
                    'height' => 240,
                ])
            @else
                <div class="lap-chart-legend">
                    <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#00416c"></span>Hadir <span class="lap-legend-value">{{ number_format($trendTotals['hadir'], 0, ',', '.') }}</span></span>
                    <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#ffae01"></span>Terlambat <span class="lap-legend-value">{{ number_format($trendTotals['terlambat'], 0, ',', '.') }}</span></span>
                    <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#8b949f"></span>Izin <span class="lap-legend-value">{{ number_format($trendTotals['izin'], 0, ',', '.') }}</span></span>
                    <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#c3c9d1"></span>Sakit <span class="lap-legend-value">{{ number_format($trendTotals['sakit'], 0, ',', '.') }}</span></span>
                    <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#a4462f"></span>Alpa <span class="lap-legend-value">{{ number_format($trendTotals['alpa'], 0, ',', '.') }}</span></span>
                </div>
                <div class="card-body pt-2">
                    <div id="presensiTrend" class="lap-chart"></div>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="ri-bar-chart-box-line"></i>Rekap presensi</h2>
                <span class="lap-meta">{{ number_format($absensi->total(), 0, ',', '.') }} baris</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:56px">No</th>
                                <th style="width:110px">Tanggal</th>
                                <th>GTK</th>
                                <th style="width:100px">Status</th>
                                <th style="width:90px">Masuk</th>
                                <th style="width:90px">Pulang</th>
                                <th style="width:100px" class="text-end">Terlambat</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($absensi as $i => $row)
                                @php
                                    $tone = match ($row->status) {
                                        'hadir' => 'success',
                                        'sakit', 'cuti' => 'warning',
                                        'izin', 'dinas_luar' => 'info',
                                        'alpa' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="muted">{{ $absensi->firstItem() + $i }}</td>
                                    <td>{{ $row->tanggal?->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="lap-table-name">{{ $row->gtk?->user?->name ?? '—' }}</div>
                                        <div class="lap-table-sub">{{ $row->gtk?->user?->email }}</div>
                                    </td>
                                    <td><span class="badge bg-{{ $tone }}-subtle">{{ $statusLabels[$row->status] ?? ucfirst($row->status) }}</span></td>
                                    <td>{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '—' }}</td>
                                    <td>{{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '—' }}</td>
                                    <td class="text-end {{ $row->terlambat_menit > 0 ? 'text-warning' : 'muted' }}">
                                        {{ $row->terlambat_menit > 0 ? $row->terlambat_menit.' mnt' : '—' }}
                                    </td>
                                    <td class="muted">{{ \Illuminate\Support\Str::limit($row->keterangan, 60) ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Belum ada data presensi',
                                            'desc' => 'Tidak ada catatan presensi GTK pada periode ini.',
                                        ])
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($absensi->hasPages())
                <div class="card-footer">
                    @include('shared._pagination', ['paginator' => $absensi])
                </div>
            @endif
        </div>
    </div>
@endsection

@section('script')
    @include('user.laporan.partials._script')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    @if (filled($trend))
        <script>
            (function () {
                var rows = @json($trend);

                window.lapChart('presensiTrend', {
                    chart: { type: 'bar', height: 300, stacked: true },
                    series: [
                        { name: 'Hadir', data: rows.map(function (d) { return d.hadir; }) },
                        { name: 'Terlambat', data: rows.map(function (d) { return d.terlambat; }) },
                        { name: 'Izin', data: rows.map(function (d) { return d.izin; }) },
                        { name: 'Sakit', data: rows.map(function (d) { return d.sakit; }) },
                        { name: 'Alpa', data: rows.map(function (d) { return d.alpa; }) }
                    ],
                    colors: ['#00416c', '#ffae01', '#8b949f', '#c3c9d1', '#a4462f'],
                    plotOptions: {
                        bar: { columnWidth: '56%', borderRadius: 2, borderRadiusApplication: 'end' }
                    },
                    stroke: { width: 1, colors: ['transparent'] },
                    xaxis: {
                        categories: rows.map(function (d) { return d.day; }),
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { rotate: 0, hideOverlappingLabels: true, style: { colors: window.lapMuted(), fontSize: '11px' } }
                    },
                    yaxis: {
                        labels: { style: { colors: window.lapMuted(), fontSize: '11px' }, formatter: function (v) { return window.lapFormat(v); } }
                    },
                    tooltip: {
                        custom: function (ctx) {
                            var row = rows[ctx.dataPointIndex] || {};
                            return window.lapTooltip(ctx.opts.xaxis.categories[ctx.dataPointIndex], [
                                ['Hadir', window.lapFormat(row.hadir)],
                                ['Terlambat', window.lapFormat(row.terlambat)],
                                ['Izin', window.lapFormat(row.izin)],
                                ['Sakit', window.lapFormat(row.sakit)],
                                ['Alpa', window.lapFormat(row.alpa)]
                            ]);
                        }
                    }
                });
            })();
        </script>
    @endif
@endsection
