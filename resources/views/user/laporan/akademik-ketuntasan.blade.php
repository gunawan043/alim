@extends('layouts.master')

@section('title') Laporan Ketuntasan Akademik @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') <a href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}">Akademik</a> @endslot
        @slot('title') Nilai &amp; ketuntasan @endslot
    @endcomponent

    @php
        $summary = $analysis['summary'];
        $classStats = collect($analysis['class_stats'] ?? []);
        $hasChart = $classStats->contains(fn ($row) => ($row['complete_rate'] ?? null) !== null || ($row['mastery_rate'] ?? null) !== null);
        $completeDelta = $analysis['comparison']['complete_rate_delta'] ?? null;
        $masteryDelta = $analysis['comparison']['mastery_rate_delta'] ?? null;
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Nilai &amp; ketuntasan</h1>
                <p class="lap-head-desc">Rekap kelengkapan nilai dan ketuntasan per santri, kelas, dan mata pelajaran. Ketuntasan hanya dihitung bila KKTP tersedia.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.akademik.ketuntasan.export', array_merge(['userId' => $userId], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh Excel
                </a>
                <a href="{{ route('user.laporan.akademik.ketuntasan.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        @include('user.laporan.partials.academic-tabs')

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('user.laporan.akademik.ketuntasan', ['userId' => $userId]) }}" class="lap-filters">
                    <div class="lap-field">
                        <label class="form-label" for="lap-ay">Tahun ajaran</label>
                        <select id="lap-ay" name="academic_year_id" class="form-select">
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}" {{ $filters['academic_year_id'] === $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-semester">Semester</label>
                        <select id="lap-semester" name="semester" class="form-select">
                            <option value="ganjil" {{ $filters['semester'] === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                            <option value="genap" {{ $filters['semester'] === 'genap' ? 'selected' : '' }}>Genap</option>
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-grade">Jenjang</label>
                        <select id="lap-grade" name="grade_level_id" class="form-select">
                            <option value="">Semua jenjang</option>
                            @foreach ($gradeLevels as $grade)
                                <option value="{{ $grade->id }}" {{ $filters['grade_level_id'] === $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-group">Kelas</label>
                        <select id="lap-group" name="study_group_id" class="form-select">
                            <option value="">Semua kelas</option>
                            @foreach ($studyGroups as $group)
                                <option value="{{ $group->id }}" {{ $filters['study_group_id'] === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-subject">Mata pelajaran</label>
                        <select id="lap-subject" name="subject_id" class="form-select">
                            <option value="">Semua mapel</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ $filters['subject_id'] === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-q">Cari santri</label>
                        <input type="text" id="lap-q" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Nama santri">
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-compare">Pembanding</label>
                        <select id="lap-compare" name="compare_mode" class="form-select">
                            <option value="previous_period" {{ $filters['compare_mode'] === 'previous_period' ? 'selected' : '' }}>Semester sebelumnya</option>
                            <option value="previous_year" {{ $filters['compare_mode'] === 'previous_year' ? 'selected' : '' }}>Tahun ajaran sebelumnya</option>
                        </select>
                    </div>
                    <div class="lap-actions">
                        <button type="submit" class="lap-btn lap-btn-primary">Terapkan</button>
                        <a href="{{ route('user.laporan.akademik.ketuntasan', ['userId' => $userId]) }}" class="lap-btn lap-btn-icon" title="Atur ulang" aria-label="Atur ulang saringan">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @include('user.laporan.partials.period', [
            'period' => $analysis['current_label'],
            'note' => $analysis['previous_label'] ? 'Dibandingkan dengan '.$analysis['previous_label'].'.' : 'Belum ada periode pembanding.',
        ])

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Data nilai', 'value' => $summary['total_rows'], 'note' => number_format($summary['students'], 0, ',', '.').' santri'],
            [
                'label' => 'Nilai lengkap',
                'value' => $summary['complete_rate'] !== null ? number_format($summary['complete_rate'], 1, ',', '.').'%' : '—',
                'note' => number_format($summary['complete'], 0, ',', '.').' baris terisi',
            ],
            [
                'label' => 'Ketuntasan',
                'value' => $summary['mastery_rate'] !== null ? number_format($summary['mastery_rate'], 1, ',', '.').'%' : '—',
                'note' => 'dari '.number_format($summary['with_kktp'], 0, ',', '.').' data ber-KKTP',
                'tone' => $summary['mastery_rate'] === null ? null : ($summary['mastery_rate'] < 60 ? 'bad' : 'ok'),
            ],
            [
                'label' => 'Belum lengkap',
                'value' => $summary['missing'],
                'note' => $summary['missing_rate'] !== null ? number_format($summary['missing_rate'], 1, ',', '.').'% dari data' : 'persentase belum tersedia',
                'tone' => $summary['missing'] > 0 ? 'warn' : null,
            ],
            ['label' => 'Di bawah KKTP', 'value' => $summary['below_kktp'], 'note' => 'baris nilai', 'tone' => $summary['below_kktp'] > 0 ? 'bad' : null],
            [
                'label' => 'Perubahan kelengkapan',
                'value' => $completeDelta !== null ? (($completeDelta > 0 ? '+' : '').number_format($completeDelta, 1, ',', '.').'%') : '—',
                'note' => $analysis['previous_label'] ? 'terhadap pembanding' : 'belum ada pembanding',
                'tone' => $completeDelta === null ? null : ($completeDelta < 0 ? 'bad' : 'ok'),
            ],
        ]])

        <div class="lap-grid lap-grid-main">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Kelengkapan &amp; ketuntasan per kelas</h2>
                    <span class="lap-meta">klik batang untuk memfilter</span>
                </div>

                @if (! $hasChart)
                    @include('user.laporan.partials.empty', [
                        'title' => 'Belum ada data per kelas',
                        'desc' => 'Pilih tahun ajaran atau semester lain untuk melihat rekap.',
                        'height' => 300,
                    ])
                @else
                    <div class="lap-chart-legend">
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#00416c"></span>Lengkap <span class="lap-legend-value">{{ $summary['complete_rate'] !== null ? number_format($summary['complete_rate'], 1, ',', '.').'%' : '—' }}</span></span>
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#ffae01"></span>Tuntas <span class="lap-legend-value">{{ $summary['mastery_rate'] !== null ? number_format($summary['mastery_rate'], 1, ',', '.').'%' : '—' }}</span></span>
                    </div>
                    <div class="card-body pt-2">
                        <div id="completionChart" class="lap-chart"></div>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Perbandingan periode</h2>
                </div>
                <div class="card-body">
                    @if ($analysis['previous_label'])
                        <div class="lap-list">
                            <div class="lap-list-row">
                                <span class="lap-list-label">Kelengkapan</span>
                                <span class="lap-list-value">
                                    {{ $summary['complete_rate'] !== null ? number_format($summary['complete_rate'], 1, ',', '.').'%' : '—' }}
                                    @if ($completeDelta !== null)
                                        <span class="d-block lap-table-sub {{ $completeDelta < 0 ? 'is-bad' : 'is-ok' }}">
                                            {{ $completeDelta > 0 ? '+' : '' }}{{ number_format($completeDelta, 1, ',', '.').'%' }}
                                        </span>
                                    @endif
                                </span>
                            </div>
                            <div class="lap-list-row">
                                <span class="lap-list-label">Ketuntasan</span>
                                <span class="lap-list-value">
                                    {{ $summary['mastery_rate'] !== null ? number_format($summary['mastery_rate'], 1, ',', '.').'%' : '—' }}
                                    @if ($masteryDelta !== null)
                                        <span class="d-block lap-table-sub {{ $masteryDelta < 0 ? 'is-bad' : 'is-ok' }}">
                                            {{ $masteryDelta > 0 ? '+' : '' }}{{ number_format($masteryDelta, 1, ',', '.').'%' }}
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        <p class="lap-note mt-2">
                            Pembanding: lengkap {{ $analysis['previous_summary']['complete_rate'] !== null ? number_format($analysis['previous_summary']['complete_rate'], 1, ',', '.').'%' : '—' }}
                            · tuntas {{ $analysis['previous_summary']['mastery_rate'] !== null ? number_format($analysis['previous_summary']['mastery_rate'], 1, ',', '.').'%' : '—' }}
                        </p>
                    @else
                        <p class="lap-note">Belum ada periode pembanding.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="lap-grid lap-grid-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Rekap kelas</h2>
                </div>
                <div class="card-body p-0">
                    @if ($classStats->isEmpty())
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada data', 'desc' => 'Tidak ada kelas pada saringan ini.', 'height' => 160])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kelas</th>
                                        <th class="text-end">Data</th>
                                        <th class="text-end">Lengkap</th>
                                        <th class="text-end">Tuntas</th>
                                        <th class="text-end">Di bawah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($classStats as $class)
                                        <tr>
                                            <td>{{ $class['name'] }}</td>
                                            <td class="text-end">{{ number_format($class['total'], 0, ',', '.') }}</td>
                                            <td class="text-end muted">{{ $class['complete_rate'] !== null ? number_format($class['complete_rate'], 1, ',', '.').'%' : '—' }}</td>
                                            <td class="text-end muted">{{ $class['mastery_rate'] !== null ? number_format($class['mastery_rate'], 1, ',', '.').'%' : '—' }}</td>
                                            <td class="text-end {{ $class['below_kktp'] > 0 ? 'text-danger' : 'muted' }}">{{ number_format($class['below_kktp'], 0, ',', '.') }}</td>
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
                    <h2 class="card-title">Rekap mata pelajaran</h2>
                </div>
                <div class="card-body p-0">
                    @if (blank($analysis['subject_stats']))
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada data', 'desc' => 'Tidak ada mapel pada saringan ini.', 'height' => 160])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Mata pelajaran</th>
                                        <th class="text-end">Data</th>
                                        <th class="text-end">Lengkap</th>
                                        <th class="text-end">Tuntas</th>
                                        <th class="text-end">Di bawah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($analysis['subject_stats'] as $subject)
                                        <tr>
                                            <td>{{ $subject['name'] }}</td>
                                            <td class="text-end">{{ number_format($subject['total'], 0, ',', '.') }}</td>
                                            <td class="text-end muted">{{ $subject['complete_rate'] !== null ? number_format($subject['complete_rate'], 1, ',', '.').'%' : '—' }}</td>
                                            <td class="text-end muted">{{ $subject['mastery_rate'] !== null ? number_format($subject['mastery_rate'], 1, ',', '.').'%' : '—' }}</td>
                                            <td class="text-end {{ $subject['below_kktp'] > 0 ? 'text-danger' : 'muted' }}">{{ number_format($subject['below_kktp'], 0, ',', '.') }}</td>
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
                <h2 class="card-title">Detail nilai &amp; ketuntasan</h2>
                <span class="lap-meta">{{ number_format($analysis['row_count'], 0, ',', '.') }} baris</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:56px">No</th>
                                <th>Santri</th>
                                <th>Kelas</th>
                                <th>Mata pelajaran</th>
                                <th class="text-end">STS</th>
                                <th class="text-end">SAS</th>
                                <th class="text-end">KKTP</th>
                                <th style="width:150px">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="muted">{{ $rows->firstItem() + $loop->index }}</td>
                                    <td>
                                        <div class="lap-table-name">{{ $row['student_name'] }}</div>
                                        <div class="lap-table-sub">{{ $row['nisn'] ?: $row['nis'] ?: '—' }}</div>
                                    </td>
                                    <td>{{ $row['class_name'] }}</td>
                                    <td>
                                        <div>{{ $row['subject_name'] }}</div>
                                        <div class="lap-table-sub">{{ $row['subject_code'] }}</div>
                                    </td>
                                    <td class="text-end">{{ $row['sts'] !== null ? number_format($row['sts'], 2, ',', '.') : '—' }}</td>
                                    <td class="text-end">{{ $row['sas'] !== null ? number_format($row['sas'], 2, ',', '.') : '—' }}</td>
                                    <td class="text-end">{{ $row['kktp'] !== null ? number_format($row['kktp'], 2, ',', '.') : '—' }}</td>
                                    <td>
                                        @if ($row['missing'])
                                            <span class="badge bg-warning-subtle">Belum lengkap</span>
                                        @elseif ($row['below_kktp'])
                                            <span class="badge bg-danger-subtle">Di bawah KKTP</span>
                                        @elseif ($row['kktp'] !== null)
                                            <span class="badge bg-success-subtle">Tuntas</span>
                                        @else
                                            <span class="badge">KKTP belum ada</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Belum ada data nilai',
                                            'desc' => 'Tidak ada nilai pada saringan ini.',
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
    @if ($hasChart)
        <script>
            (function () {
                var rows = @json($analysis['class_stats']);

                window.lapChart('completionChart', {
                    chart: {
                        type: 'bar',
                        height: 300,
                        events: {
                            dataPointSelection: function (event, chartContext, config) {
                                var row = rows[config.dataPointIndex];
                                if (!row || !row.id) { return; }
                                var url = new URL(window.location.href);
                                url.searchParams.set('study_group_id', row.id);
                                window.location.href = url.toString();
                            }
                        }
                    },
                    series: [
                        { name: 'Lengkap', data: rows.map(function (r) { return r.complete_rate; }) },
                        { name: 'Tuntas', data: rows.map(function (r) { return r.mastery_rate; }) }
                    ],
                    colors: ['#00416c', '#ffae01'],
                    plotOptions: { bar: { columnWidth: '46%', borderRadius: 2, borderRadiusApplication: 'end' } },
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
                                ['Data', window.lapFormat(row.total)],
                                ['Lengkap', row.complete_rate === null ? '—' : Number(row.complete_rate).toFixed(1) + '%'],
                                ['Tuntas', row.mastery_rate === null ? '—' : Number(row.mastery_rate).toFixed(1) + '%']
                            ]);
                        }
                    }
                });
            })();
        </script>
    @endif
@endsection
