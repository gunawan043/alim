@extends('layouts.master')
@section('title') Dashboard Gizi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}">Antropometri</a> @endslot
        @slot('title') Dashboard Gizi @endslot
    @endcomponent

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <select name="academic_year_id" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Tahun Ajaran</option>
                @foreach($academicYears as $ay)
                    <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Distribusi Status Gizi (IMT)</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kategori IMT</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Persentase</th>
                                    <th class="text-center">Visualisasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $labels = [
                                        'sangat_kurang' => 'Sangat Kurang',
                                        'kurang' => 'Kurang',
                                        'normal' => 'Normal',
                                        'lebih' => 'Lebih',
                                        'gemuk' => 'Gemuk',
                                    ];
                                    $colors = [
                                        'sangat_kurang' => 'danger',
                                        'kurang' => 'warning',
                                        'normal' => 'success',
                                        'lebih' => 'info',
                                        'gemuk' => 'danger',
                                    ];
                                @endphp
                                @foreach($labels as $key => $label)
                                    @php
                                        $count = $stats[$key] ?? 0;
                                        $pct = $total > 0 ? round($count / $total * 100, 1) : 0;
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $label }}</td>
                                        <td class="text-center">{{ $count }}</td>
                                        <td class="text-center">{{ $pct }}%</td>
                                        <td>
                                            <div class="progress" style="height: 20px">
                                                <div class="progress-bar bg-{{ $colors[$key] }}" style="width: {{ $pct }}%" role="progressbar">
                                                    {{ $pct > 10 ? $pct . '%' : '' }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="fw-bold border-top">
                                    <td>Total</td>
                                    <td class="text-center">{{ $total }}</td>
                                    <td class="text-center">100%</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Ringkasan</h5></div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Total Santri Terukur</div>
                            <div class="fs-4 fw-bold">{{ $total }}</div>
                        </div>
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Sangat Kurang + Kurang</div>
                            <div class="fs-4 fw-bold text-danger">
                                {{ ($stats['sangat_kurang'] ?? 0) + ($stats['kurang'] ?? 0) }}
                            </div>
                        </div>
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Normal</div>
                            <div class="fs-4 fw-bold text-success">{{ $stats['normal'] ?? 0 }}</div>
                        </div>
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Gemuk + Lebih</div>
                            <div class="fs-4 fw-bold text-warning">
                                {{ ($stats['gemuk'] ?? 0) + ($stats['lebih'] ?? 0) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
