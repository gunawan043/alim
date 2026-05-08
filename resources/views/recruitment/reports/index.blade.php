@extends('layouts.master')
@section('title')
    Recruitment Dashboard
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Reports
        @endslot
        @slot('title')
            Recruitment Dashboard
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Total Lowongan</p>
                            <h2 class="my-4">{{ $stats['total_jobs'] }}</h2>
                            <p class="text-muted mb-0">
                                <span class="badge bg-light text-success mb-0">
                                    <i class="ri-arrow-up-line"></i> {{ $stats['active_jobs'] }} </span> aktif
                            </p>
                        </div>
                        <div class="flex-shrink-0 avatar-sm">
                            <div class="avatar-title bg-soft-primary text-primary rounded-circle fs-18">
                                <i class="ri-briefcase-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Total Pelamar</p>
                            <h2 class="my-4">{{ $stats['total_applications'] }}</h2>
                            <p class="text-muted mb-0">
                                <span class="badge bg-light text-success mb-0">
                                    <i class="ri-arrow-up-line"></i> {{ $stats['application_growth'] }}% </span> growth
                            </p>
                        </div>
                        <div class="flex-shrink-0 avatar-sm">
                            <div class="avatar-title bg-soft-success text-success rounded-circle fs-18">
                                <i class="ri-user-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Diterima</p>
                            <h2 class="my-4">{{ $stats['hired_count'] }}</h2>
                            <p class="text-muted mb-0">
                                <span class="badge bg-light text-success mb-0">
                                    <i class="ri-arrow-up-line"></i> {{ $stats['hired_growth'] }}% </span> growth
                            </p>
                        </div>
                        <div class="flex-shrink-0 avatar-sm">
                            <div class="avatar-title bg-soft-success text-success rounded-circle fs-18">
                                <i class="ri-user-star-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Conversion Rate</p>
                            <h2 class="my-4">{{ $stats['application_rate'] }}%</h2>
                            <p class="text-muted mb-0">
                                <span class="badge bg-light text-warning mb-0">
                                    <i class="ri-bar-chart-line"></i> dari total pelamar
                            </p>
                        </div>
                        <div class="flex-shrink-0 avatar-sm">
                            <div class="avatar-title bg-soft-warning text-warning rounded-circle fs-18">
                                <i class="ri-pie-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Hiring Funnel</h5>
                </div>
                <div class="card-body">
                    <div id="hiring_funnel_chart"
                        data-colors='["--vz-primary", "--vz-info", "--vz-success", "--vz-warning", "--vz-danger"]'
                        class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Time to Hire</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center gap-5">
                        <div class="text-center">
                            <h4 class="mb-2">{{ $stats['time_to_hire']['average_days'] }} Hari</h4>
                            <p class="text-muted">Rata-rata</p>
                        </div>
                        <div class="text-center">
                            <h4 class="mb-2">{{ $stats['time_to_hire']['median_days'] }} Hari</h4>
                            <p class="text-muted">Median</p>
                        </div>
                        <div class="text-center">
                            <h4 class="mb-2">{{ $stats['time_to_hire']['min_days'] }} -
                                {{ $stats['time_to_hire']['max_days'] }}</h4>
                            <p class="text-muted">Range</p>
                        </div>
                    </div>
                    <div id="time_to_hire_chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Demografi Pelamar</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-custom nav-primary mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#gender-tab" role="tab">Gender</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#age-tab" role="tab">Usia</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#edu-tab" role="tab">Pendidikan</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="gender-tab">
                            <div id="gender_chart" class="apex-charts" dir="ltr"></div>
                        </div>
                        <div class="tab-pane" id="age-tab">
                            <div id="age_chart" class="apex-charts" dir="ltr"></div>
                        </div>
                        <div class="tab-pane" id="edu-tab">
                            <div id="education_chart" class="apex-charts" dir="ltr"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Trend Pelamar</h5>
                </div>
                <div class="card-body">
                    <div id="trend_chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Performa Lowongan</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Judul</th>
                                    <th>Unit</th>
                                    <th>Kuota</th>
                                    <th>Terisi</th>
                                    <th>Pelamar</th>
                                    <th>Konversi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stats['job_performance'] as $job)
                                    <tr>
                                        <td>{{ $job['kode'] }}</td>
                                        <td>{{ $job['judul'] }}</td>
                                        <td>{{ $job['unit'] }}</td>
                                        <td>{{ $job['kuota'] }}</td>
                                        <td>{{ $job['terisi'] }}</td>
                                        <td>{{ $job['total_pelamar'] }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress progress-sm w-100 me-2" style="height: 5px;">
                                                    <div class="progress-bar bg-success"
                                                        style="width: {{ $job['konversi'] }}%"></div>
                                                </div>
                                                <span>{{ $job['konversi'] }}%</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($job['sisa_kuota'] > 0)
                                                <span class="badge bg-success">Open</span>
                                            @else
                                                <span class="badge bg-secondary">Closed</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        var funnelOptions = {
            series: [
                {{ $stats['hiring_funnel']['applications']['count'] }},
                {{ $stats['hiring_funnel']['administrasi_lolos']['count'] }},
                {{ $stats['hiring_funnel']['tes_lolos']['count'] }},
                {{ $stats['hiring_funnel']['wawancara_lolos']['count'] }},
                {{ $stats['hiring_funnel']['hired']['count'] }}
            ],
            chart: {
                type: 'bar',
                height: 350
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    distributed: true,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: ['#299cdb', '#3452e1', '#34c38f', '#f1b44c', '#f06548'],
            dataLabels: {
                enabled: true,
                formatter: function(val, opt) {
                    return opt.w.globals.labels[opt.dataPointIndex] + ': ' + val
                },
                offsetX: 30,
                style: {
                    fontSize: '12px',
                    fontWeight: 600
                }
            },
            xaxis: {
                categories: ['Aplikasi', 'Lolos Adm', 'Lolos Tes', 'Lolos Interview', 'Diterima']
            }
        };

        var funnelChart = new ApexCharts(document.querySelector("#hiring_funnel_chart"), funnelOptions);
        funnelChart.render();

        // Time to hire chart
        var timeOptions = {
            series: [{
                name: 'Time to Hire',
                data: [{{ $stats['time_to_hire']['distribution']['< 7'] ?? 0 }},
                    {{ $stats['time_to_hire']['distribution']['7-14'] ?? 0 }},
                    {{ $stats['time_to_hire']['distribution']['15-30'] ?? 0 }},
                    {{ $stats['time_to_hire']['distribution']['31-60'] ?? 0 }},
                    {{ $stats['time_to_hire']['distribution']['> 60'] ?? 0 }}
                ]
            }],
            chart: {
                type: 'bar',
                height: 300
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '60%'
                }
            },
            xaxis: {
                categories: ['< 7 hari', '7-14 hari', '15-30 hari', '31-60 hari', '> 60 hari']
            },
            colors: ['#34c38f']
        };

        var timeChart = new ApexCharts(document.querySelector("#time_to_hire_chart"), timeOptions);
        timeChart.render();

        // Gender chart
        var genderOptions = {
            series: [{{ $stats['applicant_demographics']['gender']['L'] }},
                {{ $stats['applicant_demographics']['gender']['P'] }}
            ],
            chart: {
                type: 'pie',
                height: 300
            },
            labels: ['Laki-laki', 'Perempuan'],
            colors: ['#299cdb', '#f06548'],
            legend: {
                position: 'bottom'
            }
        };

        var genderChart = new ApexCharts(document.querySelector("#gender_chart"), genderOptions);
        genderChart.render();

        // Age chart
        var ageOptions = {
            series: [{
                data: [
                    {{ $stats['applicant_demographics']['age_groups']['< 25'] }},
                    {{ $stats['applicant_demographics']['age_groups']['25-30'] }},
                    {{ $stats['applicant_demographics']['age_groups']['31-35'] }},
                    {{ $stats['applicant_demographics']['age_groups']['36-40'] }},
                    {{ $stats['applicant_demographics']['age_groups']['> 40'] }}
                ]
            }],
            chart: {
                type: 'bar',
                height: 300
            },
            xaxis: {
                categories: ['< 25', '25-30', '31-35', '36-40', '> 40']
            },
            colors: ['#34c38f']
        };

        var ageChart = new ApexCharts(document.querySelector("#age_chart"), ageOptions);
        ageChart.render();

        // Trend chart
        var trendOptions = {
            series: [{
                name: 'Aplikasi',
                data: [{{ implode(',', $stats['trends']['applications']->pluck('total')->toArray()) }}]
            }, {
                name: 'Diterima',
                data: [{{ implode(',', $stats['trends']['hires']->pluck('total')->toArray()) }}]
            }],
            chart: {
                type: 'line',
                height: 350
            },
            xaxis: {
                categories: [
                    {{ implode(',', $stats['trends']['applications']->pluck('period')->map(fn($p) => "'$p'")->toArray()) }}
                ]
            },
            colors: ['#299cdb', '#34c38f'],
            stroke: {
                curve: 'smooth',
                width: 3
            }
        };

        var trendChart = new ApexCharts(document.querySelector("#trend_chart"), trendOptions);
        trendChart.render();
    </script>
@endsection
