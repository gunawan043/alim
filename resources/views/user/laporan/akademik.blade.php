@extends('layouts.master')

@section('title') Analisis Akademik @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Akademik @endslot
        @slot('title') Analisis nilai murni STS/SAS @endslot
    @endcomponent

    @php
        $summary = $analysis['summary'];
        $classStats = collect($analysis['class_stats'] ?? []);
        $hasClassData = $classStats->contains(fn ($row) => ($row['avg_sts'] ?? null) !== null || ($row['avg_sas'] ?? null) !== null);
        $avgDelta = $summary['avg_delta'];
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Analisis nilai murni STS/SAS</h1>
                <p class="lap-head-desc">Perbandingan nilai murni STS dan SAS per santri, kelas, dan mata pelajaran. Klik batang grafik untuk memfilter kelas.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.akademik.export', array_merge(['userId' => $userId], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh Excel
                </a>
                <a href="{{ route('user.laporan.akademik.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        @include('user.laporan.partials.academic-tabs')

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('user.laporan.akademik', ['userId' => $userId]) }}" class="lap-filters">
                    <div class="lap-field">
                        <label class="form-label" for="lap-ay">Tahun ajaran</label>
                        <select id="lap-ay" name="academic_year_id" class="form-select">
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}" {{ $filters['academic_year_id'] === $year->id ? 'selected' : '' }}>
                                    {{ $year->name }}{{ $year->is_active ? ' (aktif)' : '' }}
                                </option>
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
                        <a href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}" class="lap-btn lap-btn-icon" title="Atur ulang" aria-label="Atur ulang saringan">
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
            [
                'label' => 'Rata-rata STS',
                'value' => $summary['avg_sts'] !== null ? number_format($summary['avg_sts'], 2, ',', '.') : '—',
                'note' => 'Median '.($summary['median_sts'] !== null ? number_format($summary['median_sts'], 2, ',', '.') : '—'),
            ],
            [
                'label' => 'Rata-rata SAS',
                'value' => $summary['avg_sas'] !== null ? number_format($summary['avg_sas'], 2, ',', '.') : '—',
                'note' => $avgDelta !== null ? 'Selisih '.($avgDelta > 0 ? '+' : '').number_format($avgDelta, 2, ',', '.') : 'selisih belum tersedia',
                'tone' => $avgDelta === null ? null : ($avgDelta < 0 ? 'bad' : 'ok'),
            ],
            ['label' => 'Meningkat', 'value' => $summary['increased'], 'note' => 'dari '.number_format($summary['total_rows'], 0, ',', '.').' baris nilai', 'tone' => 'ok'],
            ['label' => 'Menurun', 'value' => $summary['decreased'], 'note' => 'perlu perhatian', 'tone' => $summary['decreased'] > 0 ? 'bad' : null],
            ['label' => 'Belum lengkap', 'value' => $summary['missing'], 'note' => 'STS atau SAS kosong', 'tone' => $summary['missing'] > 0 ? 'warn' : null],
        ]])

        <div class="lap-grid lap-grid-main">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Rata-rata per kelas</h2>
                    <span class="lap-meta">klik batang untuk memfilter</span>
                </div>

                @if (! $hasClassData)
                    @include('user.laporan.partials.empty', [
                        'title' => 'Belum ada nilai per kelas',
                        'desc' => 'Pilih tahun ajaran atau semester lain, atau pastikan nilai STS dan SAS sudah diisi.',
                        'height' => 300,
                    ])
                @else
                    <div class="lap-chart-legend">
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#00416c"></span>STS</span>
                        <span class="lap-legend-item"><span class="lap-legend-swatch" style="background:#ffae01"></span>SAS</span>
                    </div>
                    <div class="card-body pt-2">
                        <div id="academicClassChart" class="lap-chart"></div>
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
                                <span class="lap-list-label">Rata-rata STS</span>
                                <span class="lap-list-value">
                                    {{ $summary['avg_sts'] !== null ? number_format($summary['avg_sts'], 2, ',', '.') : '—' }}
                                    @php $deltaSts = $analysis['comparison']['delta_avg_sts'] ?? null; @endphp
                                    @if ($deltaSts !== null)
                                        <span class="{{ $deltaSts < 0 ? 'is-bad' : 'is-ok' }} d-block lap-table-sub">
                                            {{ $deltaSts > 0 ? '+' : '' }}{{ number_format($deltaSts, 2, ',', '.') }}
                                        </span>
                                    @endif
                                </span>
                            </div>
                            <div class="lap-list-row">
                                <span class="lap-list-label">Rata-rata SAS</span>
                                <span class="lap-list-value">
                                    {{ $summary['avg_sas'] !== null ? number_format($summary['avg_sas'], 2, ',', '.') : '—' }}
                                    @php $deltaSas = $analysis['comparison']['delta_avg_sas'] ?? null; @endphp
                                    @if ($deltaSas !== null)
                                        <span class="{{ $deltaSas < 0 ? 'is-bad' : 'is-ok' }} d-block lap-table-sub">
                                            {{ $deltaSas > 0 ? '+' : '' }}{{ number_format($deltaSas, 2, ',', '.') }}
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        <p class="lap-note mt-2">
                            Periode sebelumnya: STS {{ $analysis['previous_summary']['avg_sts'] !== null ? number_format($analysis['previous_summary']['avg_sts'], 2, ',', '.') : '—' }}
                            · SAS {{ $analysis['previous_summary']['avg_sas'] !== null ? number_format($analysis['previous_summary']['avg_sas'], 2, ',', '.') : '—' }}
                        </p>
                    @else
                        <p class="lap-note">Belum ada periode pembanding yang dapat digunakan.</p>
                    @endif

                    <div class="lap-list mt-3">
                        <div class="lap-list-row">
                            <span class="lap-list-label">Santri</span>
                            <span class="lap-list-value">{{ number_format($summary['students'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Kelas</span>
                            <span class="lap-list-value">{{ number_format($summary['classes'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Mata pelajaran</span>
                            <span class="lap-list-value">{{ number_format($summary['subjects'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Di bawah KKTP</span>
                            <span class="lap-list-value {{ $summary['below_kktp'] > 0 ? 'is-bad' : '' }}">{{ number_format($summary['below_kktp'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (filled($analysis['findings']))
            <div class="card" id="findings">
                <div class="card-header">
                    <h2 class="card-title">Temuan otomatis</h2>
                    <span class="lap-meta">{{ count($analysis['findings']) }} temuan</span>
                </div>
                <div class="card-body">
                    <div class="lap-list">
                        @foreach ($analysis['findings'] as $finding)
                            <div class="lap-list-row align-items-start">
                                <span class="lap-list-label">
                                    <span class="d-block" style="color:var(--lap-ink); font-weight:500;">{{ $finding['title'] }}</span>
                                    <span class="lap-table-sub d-block">{{ $finding['description'] }}</span>
                                    @if ($finding['rows']->isNotEmpty())
                                        <span class="lap-table-sub d-block mt-1">
                                            {{ $finding['rows']->take(3)->map(fn ($r) => $r['student_name'].' · '.$r['subject_name'].' · '.$r['class_name'])->implode(' · ') }}
                                            @if ($finding['rows']->count() > 3)
                                                · +{{ $finding['rows']->count() - 3 }} lainnya
                                            @endif
                                        </span>
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
                <h2 class="card-title">Detail nilai murni</h2>
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
                                <th class="text-end">Rata-rata</th>
                                <th class="text-end">Δ SAS−STS</th>
                                <th style="width:120px">Tren</th>
                                <th class="text-end">Pembanding</th>
                                <th class="text-end">KKTP</th>
                                <th style="width:130px">Status</th>
                                <th style="width:56px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php
                                    $tone = match ($row['trend']) {
                                        'increased' => 'success',
                                        'decreased' => 'danger',
                                        'stable' => 'secondary',
                                        default => 'warning',
                                    };
                                @endphp
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
                                    <td class="text-end">{{ $row['average'] !== null ? number_format($row['average'], 2, ',', '.') : '—' }}</td>
                                    <td class="text-end {{ $row['delta'] !== null && $row['delta'] < 0 ? 'text-danger' : '' }}">
                                        {{ $row['delta'] !== null ? (($row['delta'] > 0 ? '+' : '').number_format($row['delta'], 2, ',', '.')) : '—' }}
                                    </td>
                                    <td>
                                        @if ($row['trend'] === 'missing')
                                            <span class="muted">—</span>
                                        @else
                                            <span class="badge bg-{{ $tone }}-subtle">
                                                {{ ['increased' => 'Meningkat', 'decreased' => 'Menurun', 'stable' => 'Tetap'][$row['trend']] ?? $row['trend'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end muted">
                                        @if ($row['previous_avg'] !== null)
                                            {{ number_format($row['previous_avg'], 2, ',', '.') }}
                                            @if ($row['period_delta'] !== null)
                                                <span class="{{ $row['period_delta'] < 0 ? 'text-danger' : 'text-success' }}">
                                                    ({{ $row['period_delta'] > 0 ? '+' : '' }}{{ number_format($row['period_delta'], 2, ',', '.') }})
                                                </span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $row['kktp'] !== null ? number_format($row['kktp'], 2, ',', '.') : '—' }}</td>
                                    <td>
                                        @if ($row['below_kktp'])
                                            <span class="badge bg-danger-subtle">Di bawah KKTP</span>
                                        @elseif ($row['missing'])
                                            <span class="badge bg-warning-subtle">Belum lengkap</span>
                                        @else
                                            <span class="badge bg-success-subtle">Terisi</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button"
                                                class="lap-btn lap-btn-sm lap-btn-icon history-button"
                                                data-url="{{ route('user.laporan.akademik.riwayat', ['userId' => $userId, 'studentId' => $row['student_id'], 'subject_id' => $row['subject_id']]) }}"
                                                title="Riwayat lintas semester" aria-label="Riwayat lintas semester">
                                            <i class="ri-history-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13">
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

    <div class="modal fade" id="studentHistoryModal" tabindex="-1" aria-labelledby="studentHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content laporan-page">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentHistoryModalLabel">Riwayat nilai santri</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="studentHistoryBody">
                    <div class="text-center text-muted py-4">Memuat riwayat…</div>
                </div>
                <div class="modal-footer">
                    <span class="small text-muted me-auto">Riwayat mapel terpilih lintas semester dan tahun ajaran.</span>
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @include('user.laporan.partials._script')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    @if ($hasClassData)
        <script>
            (function () {
                var rows = @json($analysis['class_stats']);

                window.lapChart('academicClassChart', {
                    chart: {
                        type: 'bar',
                        height: 320,
                        events: {
                            dataPointSelection: function (event, chartContext, config) {
                                var selected = rows[config.dataPointIndex];
                                if (!selected || !selected.id) { return; }
                                var url = new URL(window.location.href);
                                url.searchParams.set('study_group_id', selected.id);
                                window.location.href = url.toString();
                            }
                        }
                    },
                    series: [
                        { name: 'STS', data: rows.map(function (r) { return r.avg_sts; }) },
                        { name: 'SAS', data: rows.map(function (r) { return r.avg_sas; }) }
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
                        labels: { style: { colors: window.lapMuted(), fontSize: '11px' }, formatter: function (v) { return window.lapFormat(v); } }
                    },
                    tooltip: {
                        custom: function (ctx) {
                            var row = rows[ctx.dataPointIndex] || {};
                            return window.lapTooltip(row.name, [
                                ['STS', row.avg_sts === null ? '—' : Number(row.avg_sts).toFixed(2)],
                                ['SAS', row.avg_sas === null ? '—' : Number(row.avg_sas).toFixed(2)]
                            ]);
                        }
                    }
                });
            })();
        </script>
    @endif

    <script>
        (function () {
            var modalEl = document.querySelector('#studentHistoryModal');
            var body = document.querySelector('#studentHistoryBody');
            if (!modalEl || !body || !window.bootstrap) { return; }

            var modal = new bootstrap.Modal(modalEl);

            document.querySelectorAll('.history-button').forEach(function (button) {
                button.addEventListener('click', async function () {
                    body.innerHTML = '<div class="text-center text-muted py-4">Memuat riwayat…</div>';
                    modal.show();

                    try {
                        var response = await fetch(button.dataset.url, {
                            headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!response.ok) { throw new Error('Gagal memuat riwayat.'); }
                        body.innerHTML = await response.text();
                    } catch (error) {
                        body.innerHTML = '<div class="alert alert-danger mb-0">Riwayat tidak dapat dimuat. Silakan coba lagi.</div>';
                    }
                });
            });
        })();
    </script>
@endsection
