@extends('layouts.master')
@section('title') Detail Antropometri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}">Antropometri</a> @endslot
        @slot('title') Detail Data @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detail Data Antropometri</h5>
                        <div>
                            <a href="{{ route('user.uks.health-metrics.edit', ['userId' => $userId, 'uuid' => $metric->id]) }}"
                               class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i> Edit</a>
                            <form method="POST" action="{{ route('user.uks.health-metrics.destroy', ['userId' => $userId, 'uuid' => $metric->id]) }}"
                                  class="d-inline" >
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <div class="text-muted small">Tinggi</div>
                                <div class="fs-3 fw-bold">{{ $metric->height_cm ?? '-' }} <span class="fs-6">cm</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <div class="text-muted small">Berat</div>
                                <div class="fs-3 fw-bold">{{ $metric->weight_kg ?? '-' }} <span class="fs-6">kg</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <div class="text-muted small">BMI</div>
                                <div class="fs-3 fw-bold {{ $metric->bmi_category === 'normal' ? 'text-success' : 'text-warning' }}">
                                    {{ $metric->bmi ? number_format($metric->bmi, 2) : '-' }}
                                </div>
                                <span class="badge bg-{{ $metric->bmi_category === 'normal' ? 'success' : 'warning' }}">
                                    {{ $metric->bmi_category_text }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-semibold text-muted" style="width:180px">Nama Santri</td><td class="fw-semibold">{{ $metric->student?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tahun Ajaran</td><td>{{ $metric->academicYear?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tanggal Ukur</td><td>{{ $metric->record_date?->format('d/m/Y') }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Sesi</td><td>{{ $metric->measurement_session ? ucfirst(str_replace('_',' ',$metric->measurement_session)) : '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Petugas Ukur</td><td>{{ $metric->measuredBy?->name ?? '-' }}</td></tr>
                            @if($metric->notes)
                                <tr><td class="fw-semibold text-muted">Catatan</td><td>{{ $metric->notes }}</td></tr>
                            @endif
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                        <a href="{{ route('user.uks.health-metrics.student', ['userId' => $userId, 'studentUuid' => $metric->student_id]) }}"
                           class="btn btn-outline-primary">
                            <i class="ri-line-chart me-1"></i> Lihat Grafik
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection