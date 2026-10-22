@extends('layouts.master')
@section('title', 'Detail Laporan Santri')
@section('css')
    @include('uks._ui-styles')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.reports.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Detail Santri — {{ $student->name }} @endslot
    @endcomponent

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Identitas Santri</h5></div>
                <div class="card-body">
                    <h4>{{ $student->name }}</h4>
                    <p class="text-muted mb-2">{{ $student->nisn ?? $student->nis ?? '—' }}</p>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">Sekolah</dt><dd class="col-7">{{ $student->school?->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted">JK</dt><dd class="col-7">{{ $student->gender === 'L' ? 'Laki-laki' : ($student->gender === 'P' ? 'Perempuan' : '—') }}</dd>
                        <dt class="col-5 text-muted">Tinggi</dt><dd class="col-7">{{ $student->height ? $student->height.' cm' : '—' }}</dd>
                        <dt class="col-5 text-muted">Berat</dt><dd class="col-7">{{ $student->weight ? $student->weight.' kg' : '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Riwayat Kunjungan UKS</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Tanggal</th><th>Status</th><th>Lokasi</th><th>Keluhan</th></tr></thead>
                            <tbody>
                            @forelse($patients as $patient)
                                <tr>
                                    <td class="text-muted small">{{ $patient->arrived_at?->format('d/m/Y H:i') ?? $patient->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ \App\Models\Uks\UksPatient::$statusLabels[$patient->status] ?? $patient->status }}</td>
                                    <td>{{ $patient->care_location_label ?? '—' }}</td>
                                    <td class="text-muted small">{{ \Illuminate\Support\Str::limit($patient->chief_complaint ?? '—', 80) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada kunjungan UKS.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Riwayat Antropometri</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Tanggal</th><th>Tinggi</th><th>Berat</th><th>Lingkar Kepala</th><th>BMI</th></tr></thead>
                            <tbody>
                            @forelse($metrics as $metric)
                                <tr>
                                    <td class="text-muted small">{{ $metric->record_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $metric->height_cm ?? '—' }} cm</td>
                                    <td>{{ $metric->weight_kg ?? '—' }} kg</td>
                                    <td>{{ $metric->head_circumference_cm ?? '—' }} cm</td>
                                    <td>{{ $metric->bmi ?? '—' }} <span class="text-muted small">{{ $metric->bmi_category ?? '' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data antropometri.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
