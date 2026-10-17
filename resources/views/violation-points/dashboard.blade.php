@extends('layouts.master')
@section('title') Dashboard Poin Pelanggaran @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Peserta Didik</a> @endslot
        @slot('li_2') <a href="{{ route('user.violation-points.index', ['userId' => $userId]) }}">Poin Pelanggaran</a> @endslot
        @slot('title') Dashboard Kedisiplinan @endslot
    @endcomponent

    @php
        $avgPerViolation = $totalViolations > 0 ? number_format($totalPoints / $totalViolations, 1) : '0';
    @endphp

    {{-- Ringkasan --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-file-list-3-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Pelanggaran</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalViolations) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Seluruh catatan tercatat</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-error-warning-line text-danger"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Poin</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalPoints) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Akumulasi seluruh poin</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-user-follow-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Santri Terlibat</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($uniqueStudents) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Pernah tercatat pelanggaran</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-bar-chart-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Rata-rata Poin/Pelanggaran</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $avgPerViolation }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Rata-rata seluruh catatan</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="ri-line-chart-line text-primary me-1"></i> Trend Pelanggaran Bulanan {{ now()->year }}</h5>
                    <span class="badge bg-primary-subtle text-primary">12 bulan</span>
                </div>
                <div class="card-body">
                    <div id="chart-monthly" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-pie-chart-line text-primary me-1"></i> Jenis Pelanggaran Terbanyak</h5>
                </div>
                <div class="card-body">
                    @if($topViolationTypes->isEmpty())
                        <x-empty-state icon="https://cdn.lordicon.com/msoeawqm.json"
                            title="Belum ada data"
                            message="Catatan pelanggaran akan tampil di sini." />
                    @else
                        <div id="chart-violation-types" class="apex-charts" dir="ltr"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-group-line text-primary me-1"></i> Pelanggaran per Rombel</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Rombel</th>
                                    <th class="text-center" style="width:100px">Jumlah</th>
                                    <th class="text-center" style="width:110px">Total Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byStudyGroup as $row)
                                    <tr>
                                        <td class="fw-medium">{{ $row->studyGroup?->full_name ?? '-' }}</td>
                                        <td class="text-center">{{ $row->total }}</td>
                                        <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $row->total_points }}</span></td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="3"
                                        title="Belum ada data"
                                        message="Belum ada pelanggaran per rombel." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-stack-line text-primary me-1"></i> Pelanggaran per Tingkat</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tingkat</th>
                                    <th class="text-center" style="width:100px">Jumlah</th>
                                    <th class="text-center" style="width:110px">Total Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byGradeLevel as $row)
                                    <tr>
                                        <td class="fw-medium">{{ $row->grade_level_name ?? '-' }}</td>
                                        <td class="text-center">{{ $row->total }}</td>
                                        <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $row->total_points }}</span></td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="3"
                                        title="Belum ada data"
                                        message="Belum ada pelanggaran per tingkat." />
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
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="ri-list-check-2 text-primary me-1"></i> Ringkasan per Santri</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.violation-points.create', ['userId' => $userId]) }}" class="btn btn-success btn-sm">
                            <i class="ri-add-line me-1"></i>Tambah Pelanggaran
                        </a>
                        <a href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
                            Lihat Semua <i class="ri-arrow-right-line"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Santri</th>
                                    <th>Rombel</th>
                                    <th class="text-center" style="width:110px">Total Poin</th>
                                    <th class="text-center" style="width:150px">Jumlah Pelanggaran</th>
                                    <th class="text-center" style="width:90px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topStudents as $s)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $s->name }}</span>
                                            @if($s->nisn)<small class="text-muted d-block">{{ $s->nisn }}</small>@endif
                                        </td>
                                        <td>{{ $s->studyGroups->first()?->studyGroup?->full_name ?? '-' }}</td>
                                        <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $s->violation_points_sum_points ?? 0 }}</span></td>
                                        <td class="text-center">{{ $s->violation_points_count }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('user.violation-points.recap.detail', ['userId' => $userId, 'studentUuid' => $s->id]) }}"
                                               class="btn btn-sm btn-soft-primary" title="Detail per santri">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="5"
                                        title="Belum ada pelanggaran"
                                        message="Catatan pelanggaran santri akan tampil di sini." />
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
