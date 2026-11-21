@extends('layouts.master')

@section('title') Laporan Kehadiran Santri @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') <a href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}">Akademik</a> @endslot
        @slot('title') Kehadiran santri @endslot
    @endcomponent

    @php
        $summary = $report['summary'];
        $monthly = collect($report['monthly'] ?? []);
        $validRates = $monthly->pluck('attendance_rate')->filter(fn ($v) => $v !== null)->values();
        $rateMin = $validRates->isNotEmpty() ? (float) $validRates->min() : 0;
        $yMin = $rateMin >= 60 ? (int) (floor(($rateMin - 5) / 10) * 10) : 0;
        $relation = $report['academic_relation'];
        $periodeLabel = \Illuminate\Support\Carbon::parse($filters['dari'])->locale('id')->translatedFormat('d M Y')
            .' — '.\Illuminate\Support\Carbon::parse($filters['sampai'])->locale('id')->translatedFormat('d M Y');
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Kehadiran santri</h1>
                <p class="lap-head-desc">Rekap kehadiran dari presensi harian atau presensi mapel. Sumber data ditampilkan agar angka tidak tercampur.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.akademik.kehadiran.export', array_merge(['userId' => $userId], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh Excel
                </a>
                <a href="{{ route('user.laporan.akademik.kehadiran.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        @include('user.laporan.partials.academic-tabs')

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('user.laporan.akademik.kehadiran', ['userId' => $userId]) }}" class="lap-filters">
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
                        <label class="form-label" for="lap-source">Sumber</label>
                        <select id="lap-source" name="source" class="form-select">
                            <option value="harian" {{ $filters['source'] === 'harian' ? 'selected' : '' }}>Presensi harian</option>
                            <option value="mapel" {{ $filters['source'] === 'mapel' ? 'selected' : '' }}>Presensi mapel</option>
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-dari">Dari tanggal</label>
                        <input type="date" id="lap-dari" name="dari" class="form-control" value="{{ $filters['dari'] }}">
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-sampai">Sampai tanggal</label>
                        <input type="date" id="lap-sampai" name="sampai" class="form-control" value="{{ $filters['sampai'] }}">
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
                    <div class="lap-actions">
                        <button type="submit" class="lap-btn lap-btn-primary">Terapkan</button>
                        <a href="{{ route('user.laporan.akademik.kehadiran', ['userId' => $userId]) }}" class="lap-btn lap-btn-icon" title="Atur ulang" aria-label="Atur ulang saringan">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @include('user.laporan.partials.period', [
            'period' => $periodeLabel,
            'note' => 'Sumber data: '.($report['source'] === 'mapel' ? 'presensi mapel' : 'presensi harian').'. Hadir mencakup status terlambat.',
        ])

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Catatan', 'value' => $summary['total'], 'note' => number_format($summary['students'], 0, ',', '.').' santri'],
            [
                'label' => 'Kehadiran',
                'value' => $summary['attendance_rate'] !== null ? number_format($summary['attendance_rate'], 1, ',', '.').'%' : '—',
                'note' => number_format($summary['present'], 0, ',', '.').' hadir dan terlambat',
                'tone' => $summary['attendance_rate'] === null ? null : ($summary['attendance_rate'] < 85 ? 'bad' : 'ok'),
            ],
            [
                'label' => 'Alpa',
                'value' => $summary['alpa'],
                'note' => $summary['absence_rate'] !== null ? number_format($summary['absence_rate'], 1, ',', '.').'% tidak hadir' : 'catatan',
                'tone' => $summary['alpa'] > 0 ? 'bad' : null,
            ],
            ['label' => 'Izin', 'value' => $summary['izin'], 'note' => 'catatan'],
            ['label' => 'Sakit', 'value' => $summary['sakit'], 'note' => 'catatan'],
            ['label' => 'Kelas', 'value' => $summary['classes'], 'note' => number_format($summary['subjects'], 0, ',', '.').' mata pelajaran'],
        ]])

        <div class="lap-grid lap-grid-main">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Kehadiran per bulan</h2>
                    <span class="lap-meta">{{ $monthly->count() }} bulan</span>
                </div>

                @if ($validRates->isEmpty())
                    @include('user.laporan.partials.empty', [
                        'title' => 'Belum ada data kehadiran',
                        'desc' => 'Geser rentang tanggal atau ganti sumber presensi.',
                        'height' => 300,
                    ])
                @else
                    <div class="card-body">
                        <div id="attendanceChart" class="lap-chart"></div>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Perlu perhatian</h2>
                </div>
                <div class="card-body">
                    @forelse ($report['findings'] as $finding)
                        <div class="lap-list-row align-items-start">
                            <span class="lap-list-label">
                                <span class="d-block" style="color:var(--lap-ink); font-weight:500;">{{ $finding['title'] }}</span>
                                @foreach ($finding['rows']->take(3) as $row)
                                    <span class="lap-table-sub d-block">{{ $row['student_name'] ?? $row['name'] ?? '—' }}{{ ! empty($row['class_name']) ? ' · '.$row['class_name'] : '' }}</span>
                                @endforeach
                            </span>
                            <span class="lap-list-value">{{ number_format($finding['count'], 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="lap-note">Tidak ada temuan pada saringan ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="lap-grid lap-grid-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Rekap per kelas</h2>
                </div>
                <div class="card-body p-0">
                    @if (blank($report['class_stats']))
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada data', 'desc' => 'Tidak ada kelas pada saringan ini.', 'height' => 160])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kelas</th>
                                        <th class="text-end">Catatan</th>
                                        <th class="text-end">Hadir</th>
                                        <th class="text-end">Alpa</th>
                                        <th class="text-end">Kehadiran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['class_stats'] as $class)
                                        <tr>
                                            <td>{{ $class['name'] }}</td>
                                            <td class="text-end">{{ number_format($class['total'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($class['present'], 0, ',', '.') }}</td>
                                            <td class="text-end {{ $class['alpa'] > 0 ? 'text-danger' : 'muted' }}">{{ number_format($class['alpa'], 0, ',', '.') }}</td>
                                            <td class="text-end {{ ($class['attendance_rate'] ?? 100) < 85 ? 'text-danger' : 'muted' }}">
                                                {{ $class['attendance_rate'] !== null ? number_format($class['attendance_rate'], 1, ',', '.').'%' : '—' }}
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
                    <h2 class="card-title">Hubungan dengan nilai</h2>
                </div>
                <div class="card-body">
                    @if ($relation['has_data'])
                        <div class="lap-list">
                            <div class="lap-list-row">
                                <span class="lap-list-label">Kehadiran di bawah 85%</span>
                                <span class="lap-list-value">
                                    {{ $relation['high_average'] !== null ? number_format($relation['high_average'], 2, ',', '.') : '—' }}
                                    <span class="d-block lap-table-sub">{{ number_format($relation['high_count'], 0, ',', '.') }} santri bernilai</span>
                                </span>
                            </div>
                            <div class="lap-list-row">
                                <span class="lap-list-label">Kehadiran 85% ke atas</span>
                                <span class="lap-list-value">
                                    {{ $relation['low_average'] !== null ? number_format($relation['low_average'], 2, ',', '.') : '—' }}
                                    <span class="d-block lap-table-sub">{{ number_format($relation['low_count'], 0, ',', '.') }} santri bernilai</span>
                                </span>
                            </div>
                        </div>
                        <p class="lap-note mt-3">Rata-rata nilai SAS/STS per kelompok. Bahan analisis, bukan kesimpulan sebab-akibat.</p>
                    @else
                        <p class="lap-note">Belum ada nilai yang dapat dipasangkan dengan data kehadiran pada saringan ini.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Detail kehadiran</h2>
                <span class="lap-meta">{{ number_format($summary['total'], 0, ',', '.') }} catatan</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:56px">No</th>
                                <th style="width:110px">Tanggal</th>
                                <th>Santri</th>
                                <th>Kelas</th>
                                <th>Mata pelajaran</th>
                                <th style="width:130px">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php
                                    $tone = match ($row['status']) {
                                        'hadir' => 'success', 'terlambat' => 'warning',
                                        'izin' => 'warning', 'sakit' => 'secondary',
                                        'alpa' => 'danger', default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="muted">{{ $rows->firstItem() + $loop->index }}</td>
                                    <td>{{ $row['date'] ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '—' }}</td>
                                    <td>
                                        <div class="lap-table-name">{{ $row['student_name'] }}</div>
                                        <div class="lap-table-sub">{{ $row['nisn'] ?: '—' }}</div>
                                    </td>
                                    <td>{{ $row['class_name'] }}</td>
                                    <td>{{ $row['subject_name'] ?: '—' }}</td>
                                    <td><span class="badge bg-{{ $tone }}-subtle">{{ $row['status_text'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Belum ada data kehadiran',
                                            'desc' => 'Tidak ada catatan kehadiran pada saringan ini.',
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
    @if ($validRates->isNotEmpty())
        <script>
            (function () {
                var rows = @json($report['monthly']);

                window.lapChart('attendanceChart', {
                    chart: { type: 'area', height: 300 },
                    series: [{ name: 'Kehadiran', data: rows.map(function (r) { return r.attendance_rate; }) }],
                    colors: ['#00416c'],
                    stroke: { curve: 'smooth', width: 2.5 },
                    fill: {
                        type: 'gradient',
                        gradient: { shadeIntensity: 1, opacityFrom: .18, opacityTo: .02, stops: [0, 90, 100] }
                    },
                    markers: { size: 0, hover: { size: 4 } },
                    xaxis: {
                        categories: rows.map(function (r) { return r.label; }),
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { rotate: 0, style: { colors: window.lapMuted(), fontSize: '11px' } }
                    },
                    yaxis: {
                        min: {{ $yMin }},
                        max: 100,
                        tickAmount: {{ max(2, (int) ((100 - $yMin) / 10)) }},
                        labels: { style: { colors: window.lapMuted(), fontSize: '11px' }, formatter: function (v) { return v + '%'; } }
                    },
                    tooltip: {
                        custom: function (ctx) {
                            var row = rows[ctx.dataPointIndex] || {};
                            return window.lapTooltip(row.label, [
                                ['Kehadiran', row.attendance_rate === null ? '—' : Number(row.attendance_rate).toFixed(1) + '%'],
                                ['Catatan', window.lapFormat(row.total)]
                            ]);
                        }
                    }
                });
            })();
        </script>
    @endif
@endsection
