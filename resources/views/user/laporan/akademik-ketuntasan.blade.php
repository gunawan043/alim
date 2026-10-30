@extends('layouts.master')

@section('title') Laporan Ketuntasan Akademik @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') <a href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}">Analisis Akademik</a> @endslot
        @slot('title') Nilai & Ketuntasan Akademik @endslot
    @endcomponent

    @include('user.laporan.partials.academic-tabs')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <p class="text-muted mb-0 small">
            Rekap kelengkapan nilai dan ketuntasan per santri, kelas, dan mata pelajaran.
            Ketuntasan dihitung hanya jika KKTP tersedia.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.laporan.akademik.ketuntasan.export', array_merge(['userId' => $userId], request()->query())) }}" class="btn btn-success btn-sm">
                <i class="ri-file-excel-2-line me-1"></i>Excel
            </a>
            <a href="{{ route('user.laporan.akademik.ketuntasan.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="btn btn-danger btn-sm">
                <i class="ri-file-pdf-2-line me-1"></i>PDF
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.laporan.akademik.ketuntasan', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $filters['academic_year_id'] === $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="ganjil" {{ $filters['semester'] === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $filters['semester'] === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Jenjang</label>
                    <select name="grade_level_id" class="form-select">
                        <option value="">Semua jenjang</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade->id }}" {{ $filters['grade_level_id'] === $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Kelas</label>
                    <select name="study_group_id" class="form-select">
                        <option value="">Semua kelas</option>
                        @foreach($studyGroups as $group)
                            <option value="{{ $group->id }}" {{ $filters['study_group_id'] === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select">
                        <option value="">Semua mapel</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $filters['subject_id'] === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Cari</label>
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Nama">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Pembanding</label>
                    <select name="compare_mode" class="form-select">
                        <option value="previous_period" {{ $filters['compare_mode'] === 'previous_period' ? 'selected' : '' }}>Semester sebelumnya</option>
                        <option value="previous_year" {{ $filters['compare_mode'] === 'previous_year' ? 'selected' : '' }}>Tahun sebelumnya</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-light border small d-flex flex-wrap justify-content-between gap-2">
        <span>Periode: <strong>{{ $analysis['current_label'] }}</strong></span>
        <span>Pembanding: <strong>{{ $analysis['previous_label'] ?? 'Tidak tersedia' }}</strong></span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Data Nilai</div>
                <div class="fs-3 fw-bold">{{ number_format($analysis['summary']['total_rows']) }}</div>
                <div class="small text-muted">{{ number_format($analysis['summary']['students']) }} santri</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Nilai Lengkap</div>
                <div class="fs-3 fw-bold text-primary">{{ $analysis['summary']['complete_rate'] !== null ? number_format($analysis['summary']['complete_rate'], 2).'%' : '—' }}</div>
                <div class="small text-muted">{{ number_format($analysis['summary']['complete']) }} baris</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Ketuntasan</div>
                <div class="fs-3 fw-bold {{ ($analysis['summary']['mastery_rate'] ?? 100) < 60 ? 'text-danger' : 'text-success' }}">{{ $analysis['summary']['mastery_rate'] !== null ? number_format($analysis['summary']['mastery_rate'], 2).'%' : '—' }}</div>
                <div class="small text-muted">dari {{ number_format($analysis['summary']['with_kktp']) }} data ber-KKTP</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Belum Lengkap</div>
                <div class="fs-3 fw-bold text-warning">{{ number_format($analysis['summary']['missing']) }}</div>
                <div class="small text-muted">{{ $analysis['summary']['missing_rate'] !== null ? number_format($analysis['summary']['missing_rate'], 2).'%' : '—' }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Di Bawah KKTP</div>
                <div class="fs-3 fw-bold text-danger">{{ number_format($analysis['summary']['below_kktp']) }}</div>
                <div class="small text-muted">baris nilai</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Δ Kelengkapan</div>
                <div class="fs-3 fw-bold {{ ($analysis['comparison']['complete_rate_delta'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">{{ $analysis['comparison']['complete_rate_delta'] !== null ? (($analysis['comparison']['complete_rate_delta'] > 0 ? '+' : '').number_format($analysis['comparison']['complete_rate_delta'], 2).'%') : '—' }}</div>
                <div class="small text-muted">vs pembanding</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-bar-chart-grouped-line me-1 text-primary"></i>Kelengkapan & Ketuntasan per Kelas</h5></div>
                <div class="card-body"><div id="completionChart"></div></div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-git-compare-line me-1 text-info"></i>Perbandingan Periode</h5></div>
                <div class="card-body">
                    @if($analysis['previous_label'])
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted small">Kelengkapan</span>
                            <span><strong>{{ $analysis['summary']['complete_rate'] !== null ? number_format($analysis['summary']['complete_rate'], 2).'%' : '—' }}</strong>
                                <span class="{{ ($analysis['comparison']['complete_rate_delta'] ?? 0) < 0 ? 'text-danger' : 'text-success' }} ms-2">{{ $analysis['comparison']['complete_rate_delta'] !== null ? (($analysis['comparison']['complete_rate_delta'] > 0 ? '+' : '').number_format($analysis['comparison']['complete_rate_delta'], 2).'%') : '—' }}</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted small">Ketuntasan</span>
                            <span><strong>{{ $analysis['summary']['mastery_rate'] !== null ? number_format($analysis['summary']['mastery_rate'], 2).'%' : '—' }}</strong>
                                <span class="{{ ($analysis['comparison']['mastery_rate_delta'] ?? 0) < 0 ? 'text-danger' : 'text-success' }} ms-2">{{ $analysis['comparison']['mastery_rate_delta'] !== null ? (($analysis['comparison']['mastery_rate_delta'] > 0 ? '+' : '').number_format($analysis['comparison']['mastery_rate_delta'], 2).'%') : '—' }}</span>
                            </span>
                        </div>
                        <div class="small text-muted mt-2">Pembanding: lengkap {{ $analysis['previous_summary']['complete_rate'] !== null ? number_format($analysis['previous_summary']['complete_rate'], 2).'%' : '—' }} · tuntas {{ $analysis['previous_summary']['mastery_rate'] !== null ? number_format($analysis['previous_summary']['mastery_rate'], 2).'%' : '—' }}</div>
                    @else
                        <p class="text-muted small mb-0">Belum ada periode pembanding.</p>
                    @endif
                    <hr>
                    <div class="small text-muted">Klik batang grafik untuk memfilter kelas.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Rekap Kelas</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Kelas</th><th class="text-center">Data</th><th class="text-center">Lengkap</th><th class="text-center">Tuntas</th><th class="text-center">Di bawah</th></tr></thead>
                        <tbody>
                            @forelse($analysis['class_stats'] as $class)
                                <tr>
                                    <td>{{ $class['name'] }}</td>
                                    <td class="text-center">{{ $class['total'] }}</td>
                                    <td class="text-center">{{ $class['complete_rate'] !== null ? number_format($class['complete_rate'], 2).'%' : '—' }}</td>
                                    <td class="text-center">{{ $class['mastery_rate'] !== null ? number_format($class['mastery_rate'], 2).'%' : '—' }}</td>
                                    <td class="text-center text-danger">{{ $class['below_kktp'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Rekap Mata Pelajaran</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Mapel</th><th class="text-center">Data</th><th class="text-center">Lengkap</th><th class="text-center">Tuntas</th><th class="text-center">Di bawah</th></tr></thead>
                        <tbody>
                            @forelse($analysis['subject_stats'] as $subject)
                                <tr>
                                    <td>{{ $subject['name'] }}</td>
                                    <td class="text-center">{{ $subject['total'] }}</td>
                                    <td class="text-center">{{ $subject['complete_rate'] !== null ? number_format($subject['complete_rate'], 2).'%' : '—' }}</td>
                                    <td class="text-center">{{ $subject['mastery_rate'] !== null ? number_format($subject['mastery_rate'], 2).'%' : '—' }}</td>
                                    <td class="text-center text-danger">{{ $subject['below_kktp'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-table-line me-1 text-primary"></i>Detail Nilai & Ketuntasan</h5>
            <span class="small text-muted">{{ number_format($analysis['row_count']) }} baris</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>No</th><th>Santri</th><th>Kelas</th><th>Mapel</th><th class="text-center">STS</th><th class="text-center">SAS</th><th class="text-center">KKTP</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $rows->firstItem() + $loop->index }}</td>
                                <td><div class="fw-medium">{{ $row['student_name'] }}</div><div class="small text-muted">{{ $row['nisn'] ?: $row['nis'] ?: '—' }}</div></td>
                                <td>{{ $row['class_name'] }}</td>
                                <td><div>{{ $row['subject_name'] }}</div><div class="small text-muted">{{ $row['subject_code'] }}</div></td>
                                <td class="text-center">{{ $row['sts'] !== null ? number_format($row['sts'], 2) : '—' }}</td>
                                <td class="text-center">{{ $row['sas'] !== null ? number_format($row['sas'], 2) : '—' }}</td>
                                <td class="text-center">{{ $row['kktp'] !== null ? number_format($row['kktp'], 2) : '—' }}</td>
                                <td>
                                    @if($row['missing'])
                                        <span class="badge bg-warning-subtle text-warning">Belum lengkap</span>
                                    @elseif($row['below_kktp'])
                                        <span class="badge bg-danger-subtle text-danger">Di bawah KKTP</span>
                                    @elseif($row['kktp'] !== null)
                                        <span class="badge bg-success-subtle text-success">Tuntas</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">KKTP belum ada</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5">Belum ada data nilai pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rows->hasPages())
            <div class="card-footer">@include('shared._pagination', ['paginator' => $rows])</div>
        @endif
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    (function () {
        const stats = @json($analysis['class_stats']);
        const el = document.querySelector('#completionChart');
        if (!el) return;
        if (!stats.length) {
            el.innerHTML = '<div class="text-center text-muted py-5">Belum ada data kelas.</div>';
            return;
        }
        new ApexCharts(el, {
            chart: {
                type: 'bar', height: 300, toolbar: { show: false },
                events: {
                    dataPointSelection: function (event, chartContext, config) {
                        const row = stats[config.dataPointIndex];
                        if (!row || !row.id) return;
                        const url = new URL(window.location.href);
                        url.searchParams.set('study_group_id', row.id);
                        window.location.href = url.toString();
                    }
                }
            },
            series: [
                { name: 'Lengkap (%)', data: stats.map(function (row) { return row.complete_rate; }) },
                { name: 'Tuntas (%)', data: stats.map(function (row) { return row.mastery_rate; }) }
            ],
            xaxis: { categories: stats.map(function (row) { return row.name; }) },
            colors: ['#116891', '#20c997'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '48%' } },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
            yaxis: { min: 0, max: 100, title: { text: '%' } },
            grid: { borderColor: '#e2e8f0' }
        }).render();
    })();
</script>
@endsection
