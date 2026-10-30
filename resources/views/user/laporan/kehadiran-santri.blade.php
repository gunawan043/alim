@extends('layouts.master')

@section('title') Laporan Kehadiran Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') <a href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}">Analisis Akademik</a> @endslot
        @slot('title') Kehadiran Santri @endslot
    @endcomponent

    @include('user.laporan.partials.academic-tabs')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <p class="text-muted mb-0 small">
            Rekap kehadiran dari presensi harian atau presensi mapel. Sumber data ditampilkan agar angka tidak tercampur.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.laporan.akademik.kehadiran.export', array_merge(['userId' => $userId], request()->query())) }}" class="btn btn-success btn-sm">
                <i class="ri-file-excel-2-line me-1"></i>Excel
            </a>
            <a href="{{ route('user.laporan.akademik.kehadiran.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="btn btn-danger btn-sm">
                <i class="ri-file-pdf-2-line me-1"></i>PDF
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.laporan.akademik.kehadiran', ['userId' => $userId]) }}" class="row g-2 align-items-end">
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
                    <label class="form-label small fw-semibold">Sumber</label>
                    <select name="source" class="form-select">
                        <option value="harian" {{ $filters['source'] === 'harian' ? 'selected' : '' }}>Presensi Harian</option>
                        <option value="mapel" {{ $filters['source'] === 'mapel' ? 'selected' : '' }}>Presensi Mapel</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Dari</label>
                    <input type="date" name="dari" class="form-control" value="{{ $filters['dari'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Sampai</label>
                    <input type="date" name="sampai" class="form-control" value="{{ $filters['sampai'] }}">
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
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Cari Santri</label>
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Nama">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-light border small d-flex flex-wrap justify-content-between gap-2">
        <span>Periode: <strong>{{ \Illuminate\Support\Carbon::parse($filters['dari'])->format('d/m/Y') }} — {{ \Illuminate\Support\Carbon::parse($filters['sampai'])->format('d/m/Y') }}</strong></span>
        <span>Sumber: <strong>{{ $report['source'] === 'mapel' ? 'Presensi Mapel' : 'Presensi Harian' }}</strong></span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Catatan</div>
                <div class="fs-3 fw-bold">{{ number_format($report['summary']['total']) }}</div>
                <div class="small text-muted">{{ number_format($report['summary']['students']) }} santri</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Kehadiran</div>
                <div class="fs-3 fw-bold {{ ($report['summary']['attendance_rate'] ?? 100) < 85 ? 'text-danger' : 'text-success' }}">{{ $report['summary']['attendance_rate'] !== null ? number_format($report['summary']['attendance_rate'], 2).'%' : '—' }}</div>
                <div class="small text-muted">{{ number_format($report['summary']['present']) }} hadir/terlambat</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Alpa</div>
                <div class="fs-3 fw-bold text-danger">{{ number_format($report['summary']['alpa']) }}</div>
                <div class="small text-muted">{{ $report['summary']['absence_rate'] !== null ? number_format($report['summary']['absence_rate'], 2).'%' : '—' }} tidak hadir</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Izin</div>
                <div class="fs-3 fw-bold text-warning">{{ number_format($report['summary']['izin']) }}</div>
                <div class="small text-muted">catatan</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Sakit</div>
                <div class="fs-3 fw-bold text-info">{{ number_format($report['summary']['sakit']) }}</div>
                <div class="small text-muted">catatan</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Kelas</div>
                <div class="fs-3 fw-bold">{{ number_format($report['summary']['classes']) }}</div>
                <div class="small text-muted">{{ number_format($report['summary']['subjects']) }} mapel</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-line-chart-line me-1 text-primary"></i>Tren Kehadiran per Bulan</h5></div>
                <div class="card-body"><div id="attendanceChart"></div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-user-search-line me-1 text-warning"></i>Perlu Perhatian</h5></div>
                <div class="card-body">
                    @forelse($report['findings'] as $finding)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="small fw-semibold">{{ $finding['title'] }}</span>
                                <span class="badge bg-warning-subtle text-warning">{{ $finding['count'] }}</span>
                            </div>
                            @foreach($finding['rows']->take(3) as $row)
                                <div class="small text-muted mt-1">{{ $row['student_name'] ?? $row['name'] ?? '-' }} · {{ $row['class_name'] ?? '' }}</div>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Tidak ada temuan pada filter ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Rekap per Kelas</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Kelas</th><th class="text-center">Catatan</th><th class="text-center">Hadir</th><th class="text-center">Alpa</th><th class="text-center">%</th></tr></thead>
                        <tbody>
                            @forelse($report['class_stats'] as $class)
                                <tr>
                                    <td>{{ $class['name'] }}</td>
                                    <td class="text-center">{{ $class['total'] }}</td>
                                    <td class="text-center">{{ $class['present'] }}</td>
                                    <td class="text-center text-danger">{{ $class['alpa'] }}</td>
                                    <td class="text-center {{ ($class['attendance_rate'] ?? 100) < 85 ? 'text-danger' : 'text-success' }}">{{ $class['attendance_rate'] !== null ? number_format($class['attendance_rate'], 2).'%' : '—' }}</td>
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
                <div class="card-header"><h5 class="card-title mb-0">Hubungan dengan Nilai</h5></div>
                <div class="card-body">
                    @if($report['academic_relation']['has_data'])
                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="text-muted small">Kehadiran &lt; 85%</div>
                                <div class="fs-4 fw-bold text-danger">{{ $report['academic_relation']['high_average'] !== null ? number_format($report['academic_relation']['high_average'], 2) : '—' }}</div>
                                <div class="small text-muted">{{ $report['academic_relation']['high_count'] }} santri dengan nilai</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Kehadiran ≥ 85%</div>
                                <div class="fs-4 fw-bold text-success">{{ $report['academic_relation']['low_average'] !== null ? number_format($report['academic_relation']['low_average'], 2) : '—' }}</div>
                                <div class="small text-muted">{{ $report['academic_relation']['low_count'] }} santri dengan nilai</div>
                            </div>
                        </div>
                        <p class="text-muted small mb-0 mt-3">Rata-rata nilai SAS/STS per kelompok. Ini bahan analisis, bukan kesimpulan sebab-akibat.</p>
                    @else
                        <p class="text-muted small mb-0">Belum ada nilai yang dapat dipasangkan dengan data kehadiran pada filter ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-table-line me-1 text-primary"></i>Detail Kehadiran</h5>
            <span class="small text-muted">{{ number_format($report['summary']['total']) }} catatan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>Tanggal</th><th>Santri</th><th>Kelas</th><th>Mapel</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $rows->firstItem() + $loop->index }}</td>
                                <td>{{ $row['date'] ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '—' }}</td>
                                <td><div class="fw-medium">{{ $row['student_name'] }}</div><div class="small text-muted">{{ $row['nisn'] ?: '—' }}</div></td>
                                <td>{{ $row['class_name'] }}</td>
                                <td>{{ $row['subject_name'] ?: '—' }}</td>
                                <td>
                                    @php
                                        $tone = match ($row['status']) {
                                            'hadir' => 'success', 'terlambat' => 'info',
                                            'izin' => 'warning', 'sakit' => 'primary',
                                            'alpa' => 'danger', default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ $row['status_text'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada data kehadiran pada filter ini.</td></tr>
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
        const monthly = @json($report['monthly']);
        const el = document.querySelector('#attendanceChart');
        if (!el) return;
        if (!monthly.length) {
            el.innerHTML = '<div class="text-center text-muted py-5">Belum ada data bulanan.</div>';
            return;
        }
        new ApexCharts(el, {
            chart: { type: 'line', height: 310, toolbar: { show: false } },
            series: [{ name: 'Kehadiran (%)', data: monthly.map(function (row) { return row.attendance_rate; }) }],
            xaxis: { categories: monthly.map(function (row) { return row.label; }) },
            colors: ['#20c997'],
            stroke: { curve: 'smooth', width: 3 },
            dataLabels: { enabled: false },
            yaxis: { min: 0, max: 100, title: { text: '%' } },
            grid: { borderColor: '#e2e8f0' }
        }).render();
    })();
</script>
@endsection
