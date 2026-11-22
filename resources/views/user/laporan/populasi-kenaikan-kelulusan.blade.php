@extends('layouts.master')

@section('title') Laporan Kenaikan Kelas dan Kelulusan @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Mutasi &amp; populasi @endslot
        @slot('title') Kenaikan &amp; kelulusan @endslot
    @endcomponent

    @php
        $summary = $report['summary'];
        $byUnit = collect($report['by_unit'] ?? []);
        $promotionTotals = [
            'promoted' => (int) $byUnit->sum('promoted'),
            'retained' => (int) $byUnit->sum('retained'),
            'moved_unit' => (int) $byUnit->sum('moved_unit'),
            'not_recorded' => (int) $byUnit->sum('not_recorded'),
        ];
        $graduation = $report['graduation'] ?? [];
        $hasActions = array_sum($promotionTotals) > 0;
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Kenaikan &amp; kelulusan</h1>
                <p class="lap-head-desc">Santri naik kelas, tinggal kelas, berpindah rombel atau unit, beserta rekap kelulusan, angkatan, dan lama masa studi.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.populasi.kenaikan-kelulusan.export', array_merge(['userId' => $userId], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh Excel
                </a>
                <a href="{{ route('user.laporan.populasi.kenaikan-kelulusan.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        @include('user.laporan.partials.population-tabs')

        <div class="card">
            <div class="card-body">
                @include('user.laporan.partials.population-filters', ['filterRoute' => 'user.laporan.populasi.kenaikan-kelulusan'])
            </div>
        </div>

        @include('user.laporan.partials.period', [
            'period' => $academicYear->name,
            'note' => $report['source_note'],
        ])

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Santri acuan', 'value' => $summary['total'], 'note' => $academicYear->name],
            [
                'label' => 'Naik kelas',
                'value' => $summary['promoted'],
                'note' => $summary['promotion_rate'] !== null ? number_format($summary['promotion_rate'], 1, ',', '.').'% dari acuan' : 'persentase belum tersedia',
                'tone' => 'ok',
            ],
            ['label' => 'Tinggal kelas', 'value' => $summary['retained'], 'note' => 'jenjang berikutnya sama', 'tone' => $summary['retained'] > 0 ? 'warn' : null],
            ['label' => 'Pindah unit', 'value' => $summary['moved_unit'], 'note' => 'perpindahan internal'],
            ['label' => 'Pindah rombel', 'value' => $summary['rombel_moved'], 'note' => 'audit perpindahan'],
            [
                'label' => 'Lulusan',
                'value' => $graduation['graduates'] ?? 0,
                'note' => ($graduation['rate'] ?? null) !== null ? number_format($graduation['rate'], 1, ',', '.').'% dari kelas akhir' : 'angka belum tersedia',
            ],
        ]])

        <div class="lap-callout">
            <strong>Definisi tindakan.</strong>
            Naik kelas = jenjang berikutnya lebih tinggi. Tinggal kelas = jenjang berikutnya sama. Pindah unit = unit berikutnya berbeda. Belum tercatat = tidak ada riwayat kelas berikutnya pada data.
            <strong>Angka kelulusan</strong> = lulusan tahun ajaran ini ÷ santri kelas akhir pada tahun ajaran yang sama.
        </div>

        <div class="lap-grid lap-grid-main">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-git-branch-line"></i>Tindakan per unit</h2>
                    <span class="lap-meta">{{ $byUnit->count() }} unit</span>
                </div>

                @if (! $hasActions)
                    @include('user.laporan.partials.empty', [
                        'title' => 'Belum ada data tindakan',
                        'desc' => 'Perbandingan naik dan tinggal kelas memerlukan riwayat kelas tahun ajaran berikutnya.',
                        'height' => 300,
                    ])
                @else
                    <div class="lap-chart-legend">
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#00416c"></span>Naik <span class="lap-legend-value">{{ number_format($promotionTotals['promoted'], 0, ',', '.') }}</span></span>
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#ffae01"></span>Tinggal <span class="lap-legend-value">{{ number_format($promotionTotals['retained'], 0, ',', '.') }}</span></span>
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#8b949f"></span>Pindah unit <span class="lap-legend-value">{{ number_format($promotionTotals['moved_unit'], 0, ',', '.') }}</span></span>
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#dfe3e8"></span>Belum tercatat <span class="lap-legend-value">{{ number_format($promotionTotals['not_recorded'], 0, ',', '.') }}</span></span>
                    </div>
                    <div class="card-body pt-2">
                        <div id="promotionChart" class="lap-chart"></div>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-award-line"></i>Kelulusan per tahun</h2>
                </div>
                <div class="card-body p-0">
                    @if (blank($report['graduation_series']))
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada data kelulusan', 'desc' => 'Data muncul setelah kelulusan dicatat.', 'height' => 240])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tahun</th>
                                        <th class="text-end">Lulusan</th>
                                        <th class="text-end">Kelas akhir</th>
                                        <th class="text-end">Angka</th>
                                        <th class="text-end">Masa studi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['graduation_series'] as $row)
                                        <tr class="{{ $row['id'] === $academicYear->id ? 'table-active' : '' }}">
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-end">{{ number_format($row['graduates'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['final_cohort'], 0, ',', '.') }}</td>
                                            <td class="text-end muted">{{ $row['rate'] !== null ? number_format($row['rate'], 1, ',', '.').'%' : '—' }}</td>
                                            <td class="text-end muted">{{ $row['duration_average'] !== null ? number_format($row['duration_average'], 1, ',', '.').' th' : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="lap-grid lap-grid-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-git-branch-line"></i>Rekap tindakan per unit</h2>
                </div>
                <div class="card-body p-0">
                    @if ($byUnit->isEmpty())
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada data', 'desc' => 'Tidak ada tindakan pada saringan ini.', 'height' => 160])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Naik</th>
                                        <th class="text-end">Tinggal</th>
                                        <th class="text-end">Pindah</th>
                                        <th class="text-end">Belum tercatat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['by_unit'] as $row)
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-end">{{ number_format($row['total'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['promoted'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['retained'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['moved_unit'], 0, ',', '.') }}</td>
                                            <td class="text-end muted">{{ number_format($row['not_recorded'], 0, ',', '.') }}</td>
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
                    <h2 class="card-title"><i class="ri-git-branch-line"></i>Rekap tindakan per jenjang</h2>
                </div>
                <div class="card-body p-0">
                    @if (blank($report['by_grade']))
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada data', 'desc' => 'Tidak ada tindakan pada saringan ini.', 'height' => 160])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Jenjang</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Naik</th>
                                        <th class="text-end">Tinggal</th>
                                        <th class="text-end">Pindah</th>
                                        <th class="text-end">Belum tercatat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['by_grade'] as $row)
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-end">{{ number_format($row['total'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['promoted'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['retained'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row['moved_unit'], 0, ',', '.') }}</td>
                                            <td class="text-end muted">{{ number_format($row['not_recorded'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="lap-grid lap-grid-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-award-line"></i>Kelulusan per unit</h2>
                    <span class="lap-meta">{{ $academicYear->name }}</span>
                </div>
                <div class="card-body p-0">
                    @if (blank($report['graduation_by_unit']))
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada kelulusan', 'desc' => 'Tidak ada kelulusan pada saringan ini.', 'height' => 140])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th class="text-end">Lulusan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['graduation_by_unit'] as $row)
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-end">{{ number_format($row['graduates'], 0, ',', '.') }}</td>
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
                    <h2 class="card-title"><i class="ri-award-line"></i>Kelulusan per jenjang</h2>
                    <span class="lap-meta">{{ $academicYear->name }}</span>
                </div>
                <div class="card-body p-0">
                    @if (blank($report['graduation_by_grade']))
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada kelulusan', 'desc' => 'Tidak ada kelulusan pada saringan ini.', 'height' => 140])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Jenjang</th>
                                        <th class="text-end">Lulusan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['graduation_by_grade'] as $row)
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-end">{{ number_format($row['graduates'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="lap-grid lap-grid-main">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-calendar-2-line"></i>Angkatan &amp; lama masa studi</h2>
                </div>
                <div class="card-body p-0">
                    @if (blank($report['cohorts']))
                        @include('user.laporan.partials.empty', ['title' => 'Data angkatan belum tersedia', 'desc' => 'Data muncul setelah santri memiliki tahun masuk dan kelulusan.', 'height' => 160])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Angkatan masuk</th>
                                        <th class="text-end">Lulusan</th>
                                        <th class="text-end">Rata-rata masa studi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['cohorts'] as $row)
                                        <tr>
                                            <td>{{ $row['entry_year'] }}</td>
                                            <td class="text-end">{{ number_format($row['graduates'], 0, ',', '.') }}</td>
                                            <td class="text-end muted">{{ $row['duration_average'] !== null ? number_format($row['duration_average'], 1, ',', '.').' tahun' : '—' }}</td>
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
                    <h2 class="card-title"><i class="ri-information-line"></i>Catatan metode</h2>
                </div>
                <div class="card-body">
                    <p class="lap-note mb-2">Perbandingan naik dan tinggal kelas memerlukan riwayat kelas tahun ajaran berikutnya. Bila belum tersedia, santri ditampilkan sebagai <strong>belum tercatat</strong>, bukan otomatis tinggal kelas.</p>
                    <p class="lap-note">Lama masa studi dihitung dari tahun ajaran pertama santri tercatat hingga tanggal kelulusan. Data yang tidak lengkap tidak diisi dengan asumsi.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="ri-list-check-2-line"></i>Detail kenaikan dan kelulusan</h2>
                <span class="lap-meta">{{ number_format($report['details']->count(), 0, ',', '.') }} santri</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:56px">No</th>
                                <th>Santri</th>
                                <th>Unit</th>
                                <th>Jenjang</th>
                                <th>Kelas</th>
                                <th style="width:130px">Tindakan</th>
                                <th>Berikutnya</th>
                                <th style="width:110px" class="text-end">Pindah rombel</th>
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
                                    <td>{{ $row['school_name'] ?: '—' }}</td>
                                    <td>{{ $row['grade_name'] ?: '—' }}</td>
                                    <td>{{ $row['class_name'] ?: '—' }}</td>
                                    <td><span class="badge">{{ $row['action_label'] }}</span></td>
                                    <td>{{ $row['next_class_name'] ?: '—' }}</td>
                                    <td class="text-end">{{ $row['rombel_moved'] ? 'Ya' : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Belum ada data',
                                            'desc' => 'Tidak ada data kenaikan atau kelulusan pada saringan ini.',
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
    @if ($hasActions)
        <script>
            (function () {
                var rows = @json($report['by_unit']);

                window.lapChart('promotionChart', {
                    chart: { type: 'bar', height: 320, stacked: true },
                    series: [
                        { name: 'Naik', data: rows.map(function (r) { return r.promoted; }) },
                        { name: 'Tinggal', data: rows.map(function (r) { return r.retained; }) },
                        { name: 'Pindah unit', data: rows.map(function (r) { return r.moved_unit; }) },
                        { name: 'Belum tercatat', data: rows.map(function (r) { return r.not_recorded; }) }
                    ],
                    colors: ['#00416c', '#ffae01', '#8b949f', '#dfe3e8'],
                    plotOptions: { bar: { columnWidth: '52%', borderRadius: 2, borderRadiusApplication: 'end' } },
                    stroke: { width: 1, colors: ['transparent'] },
                    xaxis: {
                        categories: rows.map(function (r) { return r.name; }),
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
                                ['Naik kelas', window.lapFormat(row.promoted)],
                                ['Tinggal kelas', window.lapFormat(row.retained)],
                                ['Pindah unit', window.lapFormat(row.moved_unit)],
                                ['Belum tercatat', window.lapFormat(row.not_recorded)]
                            ]);
                        }
                    }
                });
            })();
        </script>
    @endif
@endsection
