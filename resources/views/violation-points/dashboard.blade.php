@extends('layouts.master')
@section('title') Dashboard Poin Pelanggaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK & Peserta Didik @endslot
        @slot('li_2') <a href="{{ route('user.violation-points.index', ['userId' => $userId]) }}">Poin Pelanggaran</a> @endslot
        @slot('title') Dashboard @endslot
    @endcomponent

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-sm-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-primary mb-2"><i class="ri-file-list-3-line fs-1"></i></div>
                    <h3 class="mb-0">{{ number_format($totalViolations) }}</h3>
                    <p class="text-muted mb-0 small">Total Pelanggaran</p>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-danger mb-2"><i class="ri-error-warning-line fs-1"></i></div>
                    <h3 class="mb-0">{{ number_format($totalPoints) }}</h3>
                    <p class="text-muted mb-0 small">Total Poin Diberikan</p>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-warning mb-2"><i class="ri-user-follow-line fs-1"></i></div>
                    <h3 class="mb-0">{{ number_format($uniqueStudents) }}</h3>
                    <p class="text-muted mb-0 small">Siswa Terlibat</p>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-success mb-2"><i class="ri-bar-chart-line fs-1"></i></div>
                    <h3 class="mb-0">{{ $totalViolations > 0 ? number_format($totalPoints / $totalViolations, 1) : '0' }}</h3>
                    <p class="text-muted mb-0 small">Rata-rata Poin/Pel.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Trend Bulanan --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="ri-line-chart-line me-1"></i>Trend Pelanggaran Bulanan {{ now()->year }}</h5>
                </div>
                <div class="card-body">
                    <div id="chart-monthly" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>

        {{-- Top Jenis Pelanggaran --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="ri-pie-chart-line me-1"></i>Jenis Pelanggaran Terbanyak</h5>
                </div>
                <div class="card-body">
                    <div id="chart-violation-types" class="apex-charts" dir="ltr"></div>
                    @if($topViolationTypes->isEmpty())
                        <p class="text-muted text-center mb-0 py-4">Belum ada data.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Per Rombel --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="ri-group-line me-1"></i>Pelanggaran per Rombel</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Rombel</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Total Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byStudyGroup as $row)
                                    <tr>
                                        <td>{{ $row->studyGroup?->full_name ?? '-' }}</td>
                                        <td class="text-center">{{ $row->total }}</td>
                                        <td class="text-center"><span class="badge bg-danger">{{ $row->total_points }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Belum ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Per Tingkat --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="ri-stack-line me-1"></i>Pelanggaran per Tingkat</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tingkat</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Total Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byGradeLevel as $row)
                                    <tr>
                                        <td>{{ $row->grade_level_name ?? '-' }}</td>
                                        <td class="text-center">{{ $row->total }}</td>
                                        <td class="text-center"><span class="badge bg-danger">{{ $row->total_points }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Belum ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ri-list-check-2 me-1"></i>Ringkasan per Siswa</h5>
                    <a href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-primary">
                        Lihat Semua <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Siswa</th>
                                    <th>Rombel</th>
                                    <th class="text-center">Total Poin</th>
                                    <th class="text-center">Jumlah Pelanggaran</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $topStudents = \App\Models\Student::when(request()->attributes->get('schoolContextId'), fn($q, $sid) => $q->where('school_id', $sid))
                                        ->where('status', 'active')
                                        ->with(['studyGroups' => fn($q) => $q->limit(1)])
                                        ->withCount('violationPoints')
                                        ->withSum('violationPoints', 'points')
                                        ->having('violation_points_count', '>', 0)
                                        ->orderByDesc('violation_points_sum_points')
                                        ->limit(5)->get();
                                @endphp
                                @forelse($topStudents as $s)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $s->name }}</span>
                                            @if($s->nisn)<small class="text-muted d-block">{{ $s->nisn }}</small>@endif
                                        </td>
                                        <td>{{ $s->studyGroups->first()?->studyGroup?->full_name ?? '-' }}</td>
                                        <td class="text-center"><span class="badge bg-danger">{{ $s->violation_points_sum_points ?? 0 }}</span></td>
                                        <td class="text-center">{{ $s->violation_points_count }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('user.violation-points.recap.detail', ['userId' => $userId, 'studentUuid' => $s->id]) }}"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ URL::asset('build/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Trend Chart
    var monthlyOptions = {
        series: [
            { name: 'Jumlah Pelanggaran', type: 'column', data: {!! json_encode($months->pluck('total')) !!} },
            { name: 'Total Poin', type: 'line', data: {!! json_encode($months->pluck('total_points')) !!} }
        ],
        chart: { height: 260, type: 'line', toolbar: { show: false } },
        stroke: { width: [0, 3], curve: 'smooth' },
        fill: { opacity: [0.85, 1], type: 'solid' },
        colors: ['#f06548', '#0ab39c'],
        xaxis: {
            categories: {!! json_encode($months->pluck('label')) !!},
            labels: { style: { colors: '#adb5bd', fontSize: '12px' } }
        },
        yaxis: { labels: { style: { colors: '#adb5bd', fontSize: '12px' } } },
        legend: { show: true, labels: { colors: '#495057' } },
        grid: { borderColor: '#f1f1f1' }
    };
    var monthlyChart = new ApexCharts(document.querySelector('#chart-monthly'), monthlyOptions);
    monthlyChart.render();

    // Top Violation Types Donut
    @if($topViolationTypes->count())
    var typeOptions = {
        series: {!! json_encode($topViolationTypes->pluck('total')) !!},
        labels: {!! json_encode($topViolationTypes->pluck('violation_type')) !!},
        chart: { type: 'donut', height: 260 },
        colors: ['#f06548','#0ab39c','#f7b84b','#3bc9db','#8b5cf6'],
        legend: { position: 'bottom', labels: { colors: '#495057' } },
        dataLabels: { enabled: true, style: { fontSize: '11px' } },
        plotOptions: { pie: { donut: { size: '60%' } } }
    };
    var typeChart = new ApexCharts(document.querySelector('#chart-violation-types'), typeOptions);
    typeChart.render();
    @endif
});
</script>
@endpush
