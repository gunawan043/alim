@extends('layouts.master')

@section('title') Laporan Keterlaksanaan Pembelajaran @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') <a href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}">Akademik</a> @endslot
        @slot('title') Keterlaksanaan pembelajaran @endslot
    @endcomponent

    @php
        $summary = $report['summary'];
        $teacherStats = collect($report['teacher_stats'] ?? []);
        $chartRows = $teacherStats->reverse()->values();
        $chartHeight = max(260, min(560, $chartRows->count() * 34 + 60));
        $chartMax = max(1, (int) $chartRows->max(fn ($row) => max((int) ($row['planned'] ?? 0), (int) ($row['journal'] ?? 0), (int) ($row['presensi'] ?? 0))));
        $chartTicks = min(5, max(1, $chartMax));
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Keterlaksanaan pembelajaran</h1>
                <p class="lap-head-desc">Membandingkan ketersediaan perangkat atau RPM, jurnal pembelajaran, presensi mapel, dan jadwal mingguan. Status terlaksana berarti jurnal dan presensi sudah tercatat.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.akademik.keterlaksanaan.export', array_merge(['userId' => $userId], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh Excel
                </a>
                <a href="{{ route('user.laporan.akademik.keterlaksanaan.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        @include('user.laporan.partials.academic-tabs')

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('user.laporan.akademik.keterlaksanaan', ['userId' => $userId]) }}" class="lap-filters">
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
                        <label class="form-label" for="lap-teacher">Guru</label>
                        <select id="lap-teacher" name="teacher_id" class="form-select">
                            <option value="">Semua guru</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ $filters['teacher_id'] === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
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
                    <div class="lap-actions">
                        <button type="submit" class="lap-btn lap-btn-primary">Terapkan</button>
                        <a href="{{ route('user.laporan.akademik.keterlaksanaan', ['userId' => $userId]) }}" class="lap-btn lap-btn-icon" title="Atur ulang" aria-label="Atur ulang saringan">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @include('user.laporan.partials.period', [
            'period' => $academicYear->name.' — '.ucfirst($filters['semester']),
            'note' => number_format($summary['total_scheduled_slots'], 0, ',', '.').' slot jadwal per minggu.',
        ])

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Buku administrasi', 'value' => $summary['total_books'], 'note' => 'satu buku per guru dan mapel'],
            ['label' => 'Berperangkat', 'value' => $summary['planned_books'], 'note' => number_format($summary['total_plans'], 0, ',', '.').' dokumen'],
            ['label' => 'Berjurnal', 'value' => $summary['journal_books'], 'note' => number_format($summary['total_journals'], 0, ',', '.').' entri'],
            ['label' => 'Berpresensi', 'value' => $summary['presensi_books'], 'note' => number_format($summary['total_meetings'], 0, ',', '.').' pertemuan'],
            ['label' => 'Terlaksana', 'value' => $summary['implemented_books'], 'note' => 'jurnal dan presensi terisi', 'tone' => 'ok'],
            ['label' => 'Belum terlaksana', 'value' => $summary['not_implemented_books'], 'note' => number_format($summary['partial_books'], 0, ',', '.').' sebagian', 'tone' => $summary['not_implemented_books'] > 0 ? 'bad' : null],
        ]])

        <div class="lap-grid lap-grid-main">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-book-2-line"></i>Perangkat, jurnal, dan presensi per guru</h2>
                    <span class="lap-meta">{{ $teacherStats->count() }} guru</span>
                </div>

                @if ($teacherStats->isEmpty())
                    @include('user.laporan.partials.empty', [
                        'title' => 'Belum ada data guru',
                        'desc' => 'Pilih tahun ajaran atau semester lain untuk melihat keterlaksanaan.',
                        'height' => 280,
                    ])
                @else
                    <div class="lap-chart-legend">
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#00416c"></span>Perangkat</span>
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#8b949f"></span>Jurnal</span>
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#ffae01"></span>Presensi</span>
                    </div>
                    <div class="card-body pt-2">
                        <div id="implementationChart" class="lap-chart"></div>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ri-alert-line"></i>Temuan</h2>
                </div>
                <div class="card-body">
                    @forelse ($report['findings'] as $finding)
                        <div class="lap-list-row align-items-start">
                            <span class="lap-list-label">
                                <span class="d-block" style="color:var(--lap-ink); font-weight:500;">{{ $finding['title'] }}</span>
                                <span class="lap-table-sub d-block">{{ $finding['description'] }}</span>
                            </span>
                            <span class="lap-list-value">{{ number_format($finding['count'], 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="lap-note">Tidak ada temuan pada saringan ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="ri-team-line"></i>Rekap per guru, mapel, dan kelas</h2>
                <span class="lap-meta">{{ number_format($summary['total_scheduled_slots'], 0, ',', '.') }} slot per minggu</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Guru</th>
                                <th>Mapel dan kelas</th>
                                <th class="text-end">Perangkat</th>
                                <th class="text-end">Jurnal</th>
                                <th class="text-end">Presensi</th>
                                <th class="text-end">Jadwal</th>
                                <th class="text-end">Nilai</th>
                                <th style="width:130px">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php
                                    $statusTone = $row['implementation_status'] === 'terlaksana'
                                        ? 'success'
                                        : ($row['implementation_status'] === 'sebagian' ? 'warning' : 'danger');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="lap-table-name">{{ $row['teacher_name'] }}</div>
                                        <div class="lap-table-sub">
                                            {{ $row['last_activity'] ? 'Aktivitas terakhir '.\Illuminate\Support\Carbon::parse($row['last_activity'])->format('d/m/Y') : 'Belum ada aktivitas' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $row['subject_name'] }}</div>
                                        <div class="lap-table-sub">{{ $row['class_name'] }}</div>
                                    </td>
                                    <td class="text-end {{ $row['plan_count'] > 0 ? '' : 'muted' }}">{{ $row['plan_count'] }}</td>
                                    <td class="text-end {{ $row['journal_count'] > 0 ? '' : 'muted' }}">{{ $row['journal_count'] }}</td>
                                    <td class="text-end {{ $row['presensi_count'] > 0 ? '' : 'muted' }}">{{ $row['presensi_count'] }}</td>
                                    <td class="text-end muted">{{ $row['scheduled_slots'] }}</td>
                                    <td class="text-end muted">{{ $row['nilai_percent'] !== null ? number_format($row['nilai_percent'], 1, ',', '.').'%' : '—' }}</td>
                                    <td><span class="badge bg-{{ $statusTone }}-subtle">{{ $row['implementation_label'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Belum ada buku administrasi',
                                            'desc' => 'Tidak ada data pada saringan ini.',
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
    @if ($teacherStats->isNotEmpty())
        <script>
            (function () {
                var rows = @json($chartRows);

                window.lapChart('implementationChart', {
                    chart: { type: 'bar', height: {{ $chartHeight }} },
                    series: [
                        { name: 'Perangkat', data: rows.map(function (r) { return r.planned; }) },
                        { name: 'Jurnal', data: rows.map(function (r) { return r.journal; }) },
                        { name: 'Presensi', data: rows.map(function (r) { return r.presensi; }) }
                    ],
                    colors: ['#00416c', '#8b949f', '#ffae01'],
                    plotOptions: {
                        bar: { horizontal: true, barHeight: '58%', borderRadius: 2, borderRadiusApplication: 'end' }
                    },
                    xaxis: {
                        categories: rows.map(function (r) { return r.teacher_name; }),
                        tickAmount: {{ $chartTicks }},
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { style: { colors: window.lapMuted(), fontSize: '11px' }, formatter: function (v) { return window.lapFormat(v); } }
                    },
                    yaxis: {
                        labels: { maxWidth: 150, style: { colors: window.lapSecondary(), fontSize: '11px' } }
                    },
                    tooltip: {
                        custom: function (ctx) {
                            var row = rows[ctx.dataPointIndex] || {};
                            return window.lapTooltip(row.teacher_name, [
                                ['Perangkat', window.lapFormat(row.planned)],
                                ['Jurnal', window.lapFormat(row.journal)],
                                ['Presensi', window.lapFormat(row.presensi)]
                            ]);
                        }
                    }
                });
            })();
        </script>
    @endif
@endsection
