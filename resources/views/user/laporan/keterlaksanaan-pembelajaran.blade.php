@extends('layouts.master')

@section('title') Laporan Keterlaksanaan Pembelajaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') <a href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}">Analisis Akademik</a> @endslot
        @slot('title') Keterlaksanaan Pembelajaran @endslot
    @endcomponent

    @include('user.laporan.partials.academic-tabs')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <p class="text-muted mb-0 small">
            Membandingkan ketersediaan perangkat/RPM, jurnal pembelajaran, presensi mapel, dan jadwal mingguan.
            Status “terlaksana” berarti jurnal dan presensi sudah tercatat.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.laporan.akademik.keterlaksanaan.export', array_merge(['userId' => $userId], request()->query())) }}" class="btn btn-success btn-sm">
                <i class="ri-file-excel-2-line me-1"></i>Excel
            </a>
            <a href="{{ route('user.laporan.akademik.keterlaksanaan.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="btn btn-danger btn-sm">
                <i class="ri-file-pdf-2-line me-1"></i>PDF
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.laporan.akademik.keterlaksanaan', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Guru</label>
                    <select name="teacher_id" class="form-select">
                        <option value="">Semua guru</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ $filters['teacher_id'] === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
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
                    <label class="form-label small fw-semibold">Mapel</label>
                    <select name="subject_id" class="form-select">
                        <option value="">Semua mapel</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $filters['subject_id'] === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Buku</div><div class="fs-3 fw-bold">{{ number_format($report['summary']['total_books']) }}</div><div class="small text-muted">administrasi mapel</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Perangkat</div><div class="fs-3 fw-bold text-primary">{{ number_format($report['summary']['planned_books']) }}</div><div class="small text-muted">{{ number_format($report['summary']['total_plans']) }} dokumen</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Jurnal</div><div class="fs-3 fw-bold text-info">{{ number_format($report['summary']['journal_books']) }}</div><div class="small text-muted">{{ number_format($report['summary']['total_journals']) }} entri</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Presensi</div><div class="fs-3 fw-bold text-success">{{ number_format($report['summary']['presensi_books']) }}</div><div class="small text-muted">{{ number_format($report['summary']['total_meetings']) }} pertemuan</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Terlaksana</div><div class="fs-3 fw-bold text-success">{{ number_format($report['summary']['implemented_books']) }}</div><div class="small text-muted">jurnal + presensi</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Belum</div><div class="fs-3 fw-bold text-danger">{{ number_format($report['summary']['not_implemented_books']) }}</div><div class="small text-muted">{{ number_format($report['summary']['partial_books']) }} sebagian</div></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-bar-chart-grouped-line me-1 text-primary"></i>Perangkat, Jurnal, dan Presensi per Guru</h5></div>
                <div class="card-body"><div id="implementationChart"></div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-alert-line me-1 text-warning"></i>Temuan</h5></div>
                <div class="card-body">
                    @forelse($report['findings'] as $finding)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between gap-2"><span class="small fw-semibold">{{ $finding['title'] }}</span><span class="badge bg-warning-subtle text-warning">{{ $finding['count'] }}</span></div>
                            <div class="small text-muted mt-1">{{ $finding['description'] }}</div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Tidak ada temuan pada filter ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-table-line me-1 text-primary"></i>Rekap per Guru, Mapel, dan Kelas</h5>
            <span class="small text-muted">{{ number_format($report['summary']['total_scheduled_slots']) }} slot jadwal/minggu</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Guru</th><th>Mapel / Kelas</th><th class="text-center">Perangkat</th><th class="text-center">Jurnal</th><th class="text-center">Presensi</th><th class="text-center">Jadwal</th><th class="text-center">Nilai</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td><div class="fw-medium">{{ $row['teacher_name'] }}</div><div class="small text-muted">{{ $row['last_activity'] ? \Illuminate\Support\Carbon::parse($row['last_activity'])->format('d/m/Y') : 'Belum ada aktivitas' }}</div></td>
                                <td><div>{{ $row['subject_name'] }}</div><div class="small text-muted">{{ $row['class_name'] }}</div></td>
                                <td class="text-center"><span class="badge bg-{{ $row['has_plan'] ? 'success' : 'danger' }}-subtle text-{{ $row['has_plan'] ? 'success' : 'danger' }}">{{ $row['plan_count'] }}</span></td>
                                <td class="text-center"><span class="badge bg-{{ $row['has_journal'] ? 'success' : 'warning' }}-subtle text-{{ $row['has_journal'] ? 'success' : 'warning' }}">{{ $row['journal_count'] }}</span></td>
                                <td class="text-center"><span class="badge bg-{{ $row['has_presensi'] ? 'success' : 'warning' }}-subtle text-{{ $row['has_presensi'] ? 'success' : 'warning' }}">{{ $row['presensi_count'] }}</span></td>
                                <td class="text-center">{{ $row['scheduled_slots'] }}</td>
                                <td class="text-center {{ ($row['nilai_percent'] ?? 0) < 100 ? 'text-warning' : 'text-success' }}">{{ $row['nilai_percent'] !== null ? number_format($row['nilai_percent'], 2).'%' : '—' }}</td>
                                <td><span class="badge bg-{{ $row['implementation_status'] === 'terlaksana' ? 'success' : ($row['implementation_status'] === 'sebagian' ? 'warning' : 'danger') }}-subtle text-{{ $row['implementation_status'] === 'terlaksana' ? 'success' : ($row['implementation_status'] === 'sebagian' ? 'warning' : 'danger') }}">{{ $row['implementation_label'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5">Belum ada buku administrasi pada filter ini.</td></tr>
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
        const stats = @json($report['teacher_stats']);
        const el = document.querySelector('#implementationChart');
        if (!el) return;
        if (!stats.length) {
            el.innerHTML = '<div class="text-center text-muted py-5">Belum ada data guru.</div>';
            return;
        }
        new ApexCharts(el, {
            chart: { type: 'bar', height: 320, toolbar: { show: false } },
            series: [
                { name: 'Perangkat', data: stats.map(function (row) { return row.planned; }) },
                { name: 'Jurnal', data: stats.map(function (row) { return row.journal; }) },
                { name: 'Presensi', data: stats.map(function (row) { return row.presensi; }) }
            ],
            xaxis: { categories: stats.map(function (row) { return row.teacher_name; }), labels: { rotate: -25 } },
            colors: ['#116891', '#20c997', '#ffae01'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
            grid: { borderColor: '#e2e8f0' },
            yaxis: { min: 0, title: { text: 'Jumlah buku' } }
        }).render();
    })();
</script>
@endsection
