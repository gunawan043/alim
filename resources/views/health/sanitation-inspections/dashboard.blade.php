@extends('layouts.master')
@section('title') Dashboard Sanitasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.sanitation-inspections.index', ['userId' => $userId]) }}">Inspeksi Sanitasi</a> @endslot
        @slot('title') Dashboard Sanitasi @endslot
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

    <div class="row g-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Total Inspeksi</div>
                    <div class="fs-2 fw-bold text-primary">{{ $totalInspection }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Rata-rata Skor</div>
                    <div class="fs-2 fw-bold text-success">{{ round($avgScore, 1) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Follow-up Tertunda</div>
                    <div class="fs-2 fw-bold text-danger">{{ $pendingFollowUp }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Lokasi Diinspeksi</div>
                    <div class="fs-2 fw-bold text-info">{{ $scoreByLocation->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Skor Rata-rata per Lokasi</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Lokasi</th>
                                    <th>Rata-rata Skor</th>
                                    <th>Jumlah Inspeksi</th>
                                    <th>Visualisasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($scoreByLocation as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row->location_type }}</td>
                                        <td class="text-center">{{ round($row->avg_score, 1) }} / 100</td>
                                        <td class="text-center">{{ $row->total }}</td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-{{ $row->avg_score >= 80 ? 'success' : ($row->avg_score >= 60 ? 'warning' : 'danger') }}"
                                                     style="width: {{ $row->avg_score }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Belum ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Tren Bulanan</h5></div>
                <div class="card-body">
                    @forelse($monthlyTrend as $tr)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">{{ $tr->month }}</span>
                            <span class="fw-semibold">{{ round($tr->avg_score, 1) }}</span>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">Belum ada data tren.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
