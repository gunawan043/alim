@extends('layouts.master')

@section('title') Laporan Profil dan Sebaran Santri @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Mutasi &amp; populasi @endslot
        @slot('title') Profil &amp; sebaran @endslot
    @endcomponent

    @php
        $summary = $report['summary'];
        $male = (int) $summary['male'];
        $female = (int) $summary['female'];
        $genderTotal = $male + $female;
        $maleShare = $genderTotal > 0 ? round($male / $genderTotal * 100, 1) : 0;
        $femaleShare = $genderTotal > 0 ? round($female / $genderTotal * 100, 1) : 0;
        $ages = collect($report['age_groups'] ?? []);
        $ageMax = (int) ($ages->max('count') ?: 0);
        $hasAges = $ages->sum('count') > 0;
        $growthRate = $summary['growth_rate'];
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Profil &amp; sebaran santri</h1>
                <p class="lap-head-desc">Jumlah santri aktif per unit, jenjang, kelas, dan rombel, beserta sebaran usia dan jenis kelamin bila data tersedia.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.populasi.profil.export', array_merge(['userId' => $userId], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh Excel
                </a>
                <a href="{{ route('user.laporan.populasi.profil.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        @include('user.laporan.partials.population-tabs')

        <div class="card">
            <div class="card-body">
                @include('user.laporan.partials.population-filters', [
                    'filterRoute' => 'user.laporan.populasi.profil',
                    'showGender' => true,
                ])
            </div>
        </div>

        @include('user.laporan.partials.period', [
            'period' => $report['academic_year']->name,
            'note' => 'Santri aktif dihitung satu kali per tahun ajaran, mengikuti rombel terakhir yang tercatat.',
        ])

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Santri aktif', 'value' => $summary['total'], 'note' => $report['academic_year']->name],
            ['label' => 'Laki-laki', 'value' => $male, 'note' => $genderTotal > 0 ? number_format($maleShare, 1, ',', '.').'% dari santri' : 'belum ada data'],
            ['label' => 'Perempuan', 'value' => $female, 'note' => $genderTotal > 0 ? number_format($femaleShare, 1, ',', '.').'% dari santri' : 'belum ada data'],
            ['label' => 'Rombel', 'value' => $summary['rombel'], 'note' => 'kelas aktif'],
            [
                'label' => 'Rata-rata per rombel',
                'value' => $summary['average_per_rombel'] !== null ? number_format($summary['average_per_rombel'], 1, ',', '.') : '—',
                'note' => 'santri per rombel',
            ],
            [
                'label' => 'Perubahan populasi',
                'value' => $growthRate !== null ? (($growthRate > 0 ? '+' : '').number_format($growthRate, 2, ',', '.').'%') : '—',
                'note' => $report['previous_year']?->name ?? 'belum ada pembanding',
                'tone' => $growthRate === null ? null : ($growthRate < 0 ? 'bad' : 'ok'),
            ],
        ]])

        <div class="lap-grid lap-grid-top" style="grid-template-columns: minmax(0, 1fr) minmax(0, 1.7fr);">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-pie-chart-line"></i>Komposisi jenis kelamin</h2>
                    <span class="lap-meta">{{ number_format($genderTotal, 0, ',', '.') }} santri</span>
                </div>
                <div class="card-body">
                    @if ($genderTotal === 0)
                        @include('user.laporan.partials.empty', [
                            'title' => 'Data jenis kelamin belum tersedia',
                            'desc' => 'Isi jenis kelamin pada data santri untuk melihat komposisi.',
                            'height' => 200,
                        ])
                    @else
                        <div class="lap-stack" role="img" aria-label="Laki-laki {{ number_format($maleShare, 1, ',', '.') }} persen, perempuan {{ number_format($femaleShare, 1, ',', '.') }} persen">
                            <span style="width: {{ $maleShare }}%; background:#00416c;"></span>
                            <span style="width: {{ $femaleShare }}%; background:#c3c9d1;"></span>
                        </div>
                        <div class="lap-list mt-3">
                            <div class="lap-list-row">
                                <span class="lap-list-label"><span class="lap-legend-swatch d-inline-block me-1" style="background:#00416c"></span>Laki-laki</span>
                                <span class="lap-list-value">{{ number_format($male, 0, ',', '.') }} <span class="muted">· {{ number_format($maleShare, 1, ',', '.') }}%</span></span>
                            </div>
                            <div class="lap-list-row">
                                <span class="lap-list-label"><span class="lap-legend-swatch d-inline-block me-1" style="background:#c3c9d1"></span>Perempuan</span>
                                <span class="lap-list-value">{{ number_format($female, 0, ',', '.') }} <span class="muted">· {{ number_format($femaleShare, 1, ',', '.') }}%</span></span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-pie-chart-line"></i>Sebaran usia</h2>
                    <span class="lap-meta">{{ $ages->count() }} kelompok</span>
                </div>
                @if (! $hasAges)
                    @include('user.laporan.partials.empty', [
                        'title' => 'Data usia belum tersedia',
                        'desc' => 'Isi tanggal lahir santri untuk melihat sebaran usia.',
                        'height' => 260,
                    ])
                @else
                    <div class="card-body">
                        <div id="ageChart" class="lap-chart"></div>
                    </div>
                @endif
            </div>
        </div>

        <div class="lap-grid lap-grid-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-bar-chart-box-line"></i>Per unit</h2>
                </div>
                <div class="card-body p-0">
                    @if (blank($report['by_unit']))
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada data', 'desc' => 'Tidak ada santri aktif pada unit ini.', 'height' => 160])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th class="text-end">Santri</th>
                                        <th class="text-end">L</th>
                                        <th class="text-end">P</th>
                                        <th class="text-end">Perubahan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['by_unit'] as $row)
                                        @php
                                            $composition = $report['composition']->firstWhere('id', $row['id']);
                                            $delta = $composition['delta'] ?? 0;
                                        @endphp
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-end">{{ number_format($row['total'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['male'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['female'], 0, ',', '.') }}</td>
                                            <td class="text-end {{ $delta < 0 ? 'text-danger' : ($delta > 0 ? 'text-success' : 'muted') }}">
                                                {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-bar-chart-box-line"></i>Per jenjang</h2>
                </div>
                <div class="card-body p-0">
                    @if (blank($report['by_grade']))
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada data', 'desc' => 'Tidak ada santri aktif pada jenjang ini.', 'height' => 160])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Jenjang</th>
                                        <th class="text-end">Santri</th>
                                        <th class="text-end">L</th>
                                        <th class="text-end">P</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['by_grade'] as $row)
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-end">{{ number_format($row['total'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['male'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['female'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="ri-pie-chart-line"></i>Sebaran per kelas</h2>
                <span class="lap-meta">{{ number_format($report['by_class']->count(), 0, ',', '.') }} rombel</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rombel</th>
                                <th>Unit</th>
                                <th>Jenjang</th>
                                <th class="text-end">Santri</th>
                                <th class="text-end">Kapasitas</th>
                                <th class="text-end" style="width:160px">Keterisian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['by_class'] as $row)
                                @php
                                    $fill = $row['fill_rate'];
                                    $fillTone = $fill === null ? '' : ($fill > 100 ? 'is-full' : ($fill < 60 ? 'is-low' : ''));
                                @endphp
                                <tr>
                                    <td class="lap-table-name">{{ $row['name'] }}</td>
                                    <td>{{ $row['school_name'] }}</td>
                                    <td>{{ $row['grade_name'] }}</td>
                                    <td class="text-end">{{ number_format($row['total'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ $row['capacity'] ?: '—' }}</td>
                                    <td class="text-end">
                                        @if ($fill === null)
                                            <span class="muted">—</span>
                                        @else
                                            <span class="lap-mini">
                                                <span class="lap-mini-track">
                                                    <span class="lap-mini-fill {{ $fillTone }}" data-lap-bar="{{ min(1, $fill / 100) }}"></span>
                                                </span>
                                                <span>{{ number_format($fill, 1, ',', '.') }}%</span>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Belum ada data rombel',
                                            'desc' => 'Data muncul setelah santri ditempatkan ke rombel.',
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
    @if ($hasAges)
        <script>
            (function () {
                var rows = @json($report['age_groups']);

                window.lapChart('ageChart', {
                    chart: { type: 'bar', height: 260 },
                    series: [{ name: 'Santri', data: rows.map(function (r) { return r.count; }) }],
                    colors: ['#c3c9d1'],
                    plotOptions: {
                        bar: {
                            columnWidth: '52%',
                            borderRadius: 2,
                            borderRadiusApplication: 'end',
                            colors: {
                                ranges: [{ from: {{ max(1, $ageMax) }}, to: {{ max(1, $ageMax) }}, color: '#00416c' }]
                            }
                        }
                    },
                    xaxis: {
                        categories: rows.map(function (r) { return r.label; }),
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
                            return window.lapTooltip(row.label + ' tahun', [['Santri', window.lapFormat(row.count)]]);
                        }
                    }
                });
            })();
        </script>
    @endif
@endsection
