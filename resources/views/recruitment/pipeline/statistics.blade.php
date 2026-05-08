@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3>Pipeline Statistics: {{ $job->judul }}</h3>
                </div>
                <div class="card-body">
                    {{-- Summary Cards --}}
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Applicants</span>
                                    <span class="info-box-number">{{ $stats['total_applications'] }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Accepted</span>
                                    <span class="info-box-number">{{ $stats['accepted_count'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">In Progress</span>
                                    <span class="info-box-number">{{ $stats['in_progress_count'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Rejected</span>
                                    <span class="info-box-number">{{ $stats['rejected_count'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Pipeline Funnel Chart --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Recruitment Funnel</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="funnelChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Conversion Rates</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Stage</th>
                                                <th>Applicants</th>
                                                <th>Conversion</th>
                                                <th>Avg Time (Days)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($stats['applications_by_stage'] as $stageName => $count)
                                                <tr>
                                                    <td>{{ $stageName }}</td>
                                                    <td>{{ $count }}</td>
                                                    <td>
                                                        @if(isset($stats['conversion_rate'][$stageName]))
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar" 
                                                                     style="width: {{ $stats['conversion_rate'][$stageName] }}%">
                                                                    {{ $stats['conversion_rate'][$stageName] }}%
                                                                </div>
                                                            </div>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $stats['average_time_per_stage'][$stageName] ?? 0 }} days
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Time Analysis --}}
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Time Analysis</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="timeChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Funnel Chart
var funnelCtx = document.getElementById('funnelChart').getContext('2d');
new Chart(funnelCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode(array_keys($stats['applications_by_stage'])) !!},
        datasets: [{
            label: 'Number of Applicants',
            data: {!! json_encode(array_values($stats['applications_by_stage'])) !!},
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Time Chart
var timeCtx = document.getElementById('timeChart').getContext('2d');
new Chart(timeCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_keys($stats['average_time_per_stage'])) !!},
        datasets: [{
            label: 'Average Time (Days)',
            data: {!! json_encode(array_values($stats['average_time_per_stage'])) !!},
            fill: false,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    }
});
</script>
@endpush
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection