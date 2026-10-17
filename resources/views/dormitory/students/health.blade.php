@extends('layouts.master')

@section('title', 'Rekam Kesehatan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Rekam Kesehatan Santri</h4>
                    <small class="text-muted">{{ $student->nama }} ({{ $student->nis ?? '-' }})</small>
                </div>
                <a href="{{ route('user.students.timeline', ['userId' => $userId, 'studentId' => $student->id]) }}"
                   class="btn btn-outline-primary">
                    <i class="ri-time-line me-1"></i> Timeline
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-heart-pulse-line me-1"></i> Biodata Kesehatan</h5></div>
                <div class="card-body">
                    @if($record)
                        <table class="table table-borderless">
                            <tr><th width="40%">Gol. Darah</th><td><code>{{ $record->blood_type ?? '-' }}</code></td></tr>
                            <tr><th>Tinggi</th><td>{{ $record->height_cm ? $record->height_cm . ' cm' : '-' }}</td></tr>
                            <tr><th>Berat</th><td>{{ $record->weight_kg ? $record->weight_kg . ' kg' : '-' }}</td></tr>
                            <tr><th>BMI</th><td>
                                @if($record->bmi)
                                    @php
                                        $bmiClass = match(true) {
                                            $record->bmi < 18.5 => 'warning',
                                            $record->bmi < 25 => 'success',
                                            $record->bmi < 30 => 'warning',
                                            default => 'danger',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $bmiClass }}">{{ number_format($record->bmi, 1) }}</span>
                                @else
                                    -
                                @endif
                            </td></tr>
                            <tr><th>Alergi</th><td>{{ $record->allergies ?? '-' }}</td></tr>
                            <tr><th>Penyakit Kronis</th><td>{{ $record->chronic_diseases ?? '-' }}</td></tr>
                            <tr><th>Obat Rutin</th><td>{{ $record->current_medications ?? '-' }}</td></tr>
                            <tr><th>Catatan Darurat</th><td>{{ $record->emergency_notes ?? '-' }}</td></tr>
                            <tr><th>BPJS</th><td>{{ $record->bpjs_number ?? '-' }}</td></tr>
                        </table>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ri-clipboard-line" style="font-size:2rem"></i>
                            <p class="mt-2">Belum ada rekam kesehatan.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-list-3-line me-1"></i> Riwayat Izin Sakit</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Keluhan</th>
                                    <th>Tindakan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permits as $p)
                                <tr>
                                    <td>{{ $p->created_at->format('d M Y') }}</td>
                                    <td><code class="text-{{ in_array($p->permit_type, ['sakit_berat','rawat_inap']) ? 'danger' : 'warning' }}">{{ str_replace('_',' ', $p->permit_type) }}</code></td>
                                    <td>{{ $p->complaint ?? '-' }}</td>
                                    <td>{{ $p->action_taken ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $p->status === 'approved' ? 'success' : ($p->status === 'pending' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($p->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-5">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Tidak Ada Riwayat Izin Sakit</h6>
                                    <p class="text-muted mb-3 small">Santri ini belum memiliki riwayat izin sakit tercatat.</p>
                                </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mini-stats-wid">
                        <div class="card-body">
                            <p class="text-muted mb-1">Total Izin Sakit</p>
                            <h4>{{ $stats['permits_total'] }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mini-stats-wid">
                        <div class="card-body">
                            <p class="text-muted mb-1">Sakit Berat / Rawat Inap</p>
                            <h4 class="text-danger">{{ $stats['permits_berat'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection