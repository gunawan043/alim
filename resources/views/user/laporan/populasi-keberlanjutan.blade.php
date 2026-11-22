@extends('layouts.master')

@section('title') Laporan Keberlanjutan Pendidikan dan Retensi @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Mutasi &amp; populasi @endslot
        @slot('title') Keberlanjutan &amp; retensi @endslot
    @endcomponent

    @php
        $summary = $report['summary'];
        $cohorts = collect($report['cohorts'] ?? []);
        $charted = $cohorts->filter(fn ($c) => $c['rate'] !== null)->values();
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Keberlanjutan &amp; retensi</h1>
                <p class="lap-head-desc">Santri yang melanjutkan ke jenjang atau unit berikutnya, yang belum tercatat melanjutkan, dan perjalanan pendidikan sejak masuk.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.populasi.keberlanjutan.export', array_merge(['userId' => $userId], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh Excel
                </a>
                <a href="{{ route('user.laporan.populasi.keberlanjutan.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        @include('user.laporan.partials.population-tabs')

        <div class="card">
            <div class="card-body">
                @include('user.laporan.partials.population-filters', ['filterRoute' => 'user.laporan.populasi.keberlanjutan'])
            </div>
        </div>

        @include('user.laporan.partials.period', [
            'period' => $academicYear->name,
            'note' => $report['next_year'] ? 'Dibandingkan dengan tahun ajaran '.$report['next_year']->name.'.' : 'Tahun ajaran berikutnya belum tersedia.',
        ])

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Santri acuan', 'value' => $summary['cohort'], 'note' => $academicYear->name],
            ['label' => 'Melanjutkan', 'value' => $summary['continued'], 'note' => 'punya riwayat setelahnya', 'tone' => 'ok'],
            ['label' => 'Belum tercatat', 'value' => $summary['not_continued'], 'note' => 'belum ada riwayat lanjutan'],
            ['label' => 'Tercatat lulus', 'value' => $summary['graduated'], 'note' => 'dari kelompok belum tercatat'],
            ['label' => 'Keluar tercatat', 'value' => $summary['known_exit'], 'note' => 'mutasi atau berhenti'],
            [
                'label' => 'Angka keberlanjutan',
                'value' => $summary['rate'] !== null ? number_format($summary['rate'], 1, ',', '.').'%' : '—',
                'note' => $report['next_year'] ? 'terhadap tahun berikutnya' : 'tahun berikutnya belum tersedia',
            ],
        ]])

        <div class="lap-callout">
            <strong>Belum tercatat melanjutkan bukan berarti putus sekolah.</strong>
            Santri dapat melanjutkan ke lembaga lain, pindah domisili, atau belum tercatat karena data belum lengkap. Status ini menggambarkan kelengkapan data, bukan keputusan tentang santri.
        </div>

        <div class="lap-grid lap-grid-main">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-arrow-right-up-line"></i>Angka keberlanjutan per tahun acuan</h2>
                    <span class="lap-meta">{{ $charted->count() }} tahun</span>
                </div>
                @if ($charted->isEmpty())
                    @include('user.laporan.partials.empty', [
                        'title' => 'Belum ada angka keberlanjutan',
                        'desc' => 'Angka dihitung setelah tahun ajaran berikutnya memiliki data.',
                        'height' => 280,
                    ])
                @else
                    <div class="card-body">
                        <div id="continuityChart" class="lap-chart"></div>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-calendar-2-line"></i>Rekap per tahun</h2>
                </div>
                <div class="card-body p-0">
                    @if ($cohorts->isEmpty())
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada data', 'desc' => 'Tidak ada angkatan pada saringan ini.', 'height' => 200])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tahun</th>
                                        <th class="text-end">Santri</th>
                                        <th class="text-end">Lanjut</th>
                                        <th class="text-end">Rasio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cohorts as $cohort)
                                        <tr class="{{ $cohort['selected'] ? 'table-active' : '' }}">
                                            <td>{{ $cohort['name'] }}</td>
                                            <td class="text-end">{{ number_format($cohort['cohort'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($cohort['continued'], 0, ',', '.') }}</td>
                                            <td class="text-end muted">{{ $cohort['rate'] !== null ? number_format($cohort['rate'], 1, ',', '.').'%' : '—' }}</td>
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
                <h2 class="card-title"><i class="ri-route-line"></i>Perjalanan santri</h2>
                <span class="lap-meta">{{ number_format($report['journey']->count(), 0, ',', '.') }} santri</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:56px">No</th>
                                <th>Santri</th>
                                <th>Tahun masuk</th>
                                <th>Kelas awal</th>
                                <th>Tahun terakhir</th>
                                <th>Kelas akhir</th>
                                <th style="width:140px">Melanjutkan</th>
                                <th>Status</th>
                                <th>Lulus</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="muted">{{ $rows->firstItem() + $loop->index }}</td>
                                    <td>
                                        <div class="lap-table-name">{{ $row['student_name'] ?: '—' }}</div>
                                        <div class="lap-table-sub">{{ $row['nisn'] ?: '—' }}</div>
                                    </td>
                                    <td>{{ $row['first_year'] ?: '—' }}</td>
                                    <td>{{ $row['first_class'] ?: '—' }}</td>
                                    <td>{{ $row['last_year'] ?: '—' }}</td>
                                    <td>{{ $row['last_class'] ?: '—' }}</td>
                                    <td>
                                        @if ($row['continued'])
                                            <span class="badge bg-success-subtle">Ya</span>
                                        @else
                                            <span class="badge bg-warning-subtle">Belum tercatat</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['status'] ?: '—' }}</td>
                                    <td>{{ $row['graduation_year'] ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Belum ada data',
                                            'desc' => 'Tidak ada perjalanan santri pada saringan ini.',
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
    @if ($charted->isNotEmpty())
        <script>
            (function () {
                var rows = @json($charted);

                window.lapChart('continuityChart', {
                    chart: { type: 'bar', height: 300 },
                    series: [{ name: 'Keberlanjutan', data: rows.map(function (r) { return r.rate; }) }],
                    colors: ['#00416c'],
                    plotOptions: { bar: { columnWidth: '46%', borderRadius: 2, borderRadiusApplication: 'end' } },
                    dataLabels: {
                        enabled: true,
                        offsetY: -18,
                        style: { fontSize: '11px', fontWeight: 500, colors: [window.lapSecondary()] },
                        formatter: function (v) { return v === null ? '—' : Number(v).toFixed(1) + '%'; }
                    },
                    xaxis: {
                        categories: rows.map(function (r) { return r.name; }),
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { rotate: 0, style: { colors: window.lapMuted(), fontSize: '11px' } }
                    },
                    yaxis: {
                        min: 0,
                        max: 100,
                        tickAmount: 4,
                        labels: { style: { colors: window.lapMuted(), fontSize: '11px' }, formatter: function (v) { return v + '%'; } }
                    },
                    tooltip: {
                        custom: function (ctx) {
                            var row = rows[ctx.dataPointIndex] || {};
                            return window.lapTooltip(row.name, [
                                ['Santri acuan', window.lapFormat(row.cohort)],
                                ['Melanjutkan', window.lapFormat(row.continued)],
                                ['Angka', row.rate === null ? '—' : Number(row.rate).toFixed(1) + '%']
                            ]);
                        }
                    }
                });
            })();
        </script>
    @endif
@endsection
