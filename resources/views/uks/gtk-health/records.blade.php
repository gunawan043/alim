@extends('layouts.master')

@section('title', 'Riwayat Kesehatan GTK — UKS')
@section('subtitle', __('Riwayat Kesehatan GTK'))

@section('css')
<style>
    .vital-box {
        padding: 10px 0;
        border-bottom: 1px dashed #eee;
    }
    .vital-box:last-child { border-bottom: none; }
    .vital-label { font-size: 11px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .vital-value { font-size: 16px; font-weight: 600; }
    .vital-normal { color: #198754; }
    .vital-warning { color: #fd7e14; }
    .vital-danger { color: #dc3545; }
    .record-card { border-left: 4px solid #0ea5e9; }
    .record-abnormal { background-color: #fff7ed; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.gtk-health.index', ['userId' => auth()->user()->id]) }}">GTK & Kesehatan</a> @endslot
        @slot('title') Riwayat Kesehatan — {{ $user->name }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- GTK Staff Selector for Quick Navigation --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <form id="staffSwitchForm" class="d-flex align-items-center gap-2">
                        <label for="staffSwitcher" class="form-label mb-0 text-muted small fw-semibold">
                            <i class="ri-user-search-line me-1"></i>Pilih GTK:
                        </label>
                        <select class="form-select form-select-sm" style="max-width: 280px;" id="staffSelect" onchange="window.location.href = this.value;">
                            @foreach($gtkStaff ?? [] as $staff)
                                <option value="{{ route('user.uks.gtk-health.show', ['userId' => auth()->user()->id, 'gtkUuid' => $staff->id]) }}"
                                        {{ $staff->id == $user->id ? 'selected' : '' }}>
                                    {{ $staff->name }} ({{ $staff->gender_label . ' ' . $staff->blood_label }})
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('user.uks.gtk-health.index', ['userId' => auth()->user()->id]) }}" class="btn btn-sm btn-secondary ms-auto">
                            <i class="ri-list-check me-1"></i>Daftar GTK
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Breadcrumb back --}}
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('user.uks.gtk-health.show', ['userId' => auth()->user()->id, 'gtkUuid' => $user->id]) }}"
               class="btn btn-soft-secondary btn-sm">
                <i class="ri-arrow-left-line me-1"></i> Kembali ke Profil GTK
            </a>
        </div>
    </div>

    {{-- Profile + Latest Record Side by Side --}}
    <div class="row g-4 mb-4">
        {{-- Profile Info --}}
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-user-settings-line me-2 text-primary"></i>Data GTK</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <span class="avatar-lg mx-auto d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle">
                            <span style="font-size: 32px; font-weight: bold;">{{ strtoupper(substr($user->name ?? '?', 0, 1)) }}</span>
                        </span>
                        <h5 class="mt-2">{{ $user->name }}</h5>
                        <p class="text-muted small mb-0">{{ $user->email }}</p>
                    </div>
                    @if($profile)
                    <dl class="row small">
                        <dt class="col-5 text-muted">Gol. Darah</dt>
                        <dd class="col-7 fw-semibold">{{ $profile->golongan_darah ?? '-' }}</dd>
                        <dt class="col-5 text-muted">JK</dt>
                        <dd class="col-7">{{ $profile->jenis_kelamin === 'L' ? 'Laki-laki' : ($profile->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</dd>
                        <dt class="col-5 text-muted">TD</dt>
                        <dd class="col-7">{{ $profile->tekanan_darah ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Tinggi</dt>
                        <dd class="col-7">{{ $profile->tinggi_badan ? number_format($profile->tinggi_badan, 1).' cm' : '-' }}</dd>
                        <dt class="col-5 text-muted">Berat</dt>
                        <dd class="col-7">{{ $profile->berat_badan ? number_format($profile->berat_badan, 1).' kg' : '-' }}</dd>
                        <dt class="col-5 text-muted">T.Lahir</dt>
                        <dd class="col-7">
                            @if($profile->tempat_lahir && $profile->tanggal_lahir)
                                {{ $profile->tempat_lahir }},
                                {{ \Carbon\Carbon::parse($profile->tanggal_lahir)->isoFormat('D MMM Y') }}
                            @endif
                        </dd>
                        @if($profile->alergi)
                        <dt class="col-5 text-muted">Alergi</dt>
                        <dd class="col-7 text-danger">{{ $profile->alergi }}</dd>
                        @endif
                        @if($profile->ongoing_medication)
                        <dt class="col-5 text-muted">Obat Rutin</dt>
                        <dd class="col-7">{{ $profile->ongoing_medication }}</dd>
                        @endif
                    </dl>
                    @endif
                </div>
            </div>
        </div>

        {{-- Latest Health Summary --}}
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ri-heart-pulse-line me-2 text-info"></i>Ringkasan Kesehatan Terbaru</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addHealthRecordModal">
                        <i class="ri-add-line me-1"></i>Tambah Pemeriksaan
                    </button>
                </div>
                <div class="card-body">
                    @if($latest)
                        @php
                            $bp = explode('/', $latest->blood_pressure ?? '');
                            $sbp = (int)($bp[0] ?? 0);
                            $dbp = (int)($bp[1] ?? 0);
                            $tdClass = '';
                            if ($sbp > 0) {
                                if ($sbp >= 140 || $dbp >= 90) $tdClass = 'vital-danger';
                                elseif ($sbp >= 130 || $dbp >= 80) $tdClass = 'vital-warning';
                                else $tdClass = 'vital-normal';
                            }
                            $bmi = $latest->bmi ?? 0;
                            $bmiStatus = '';
                            if ($bmi >= 18.5 && $bmi <= 24.9) $bmiStatus = 'Normal';
                            elseif ($bmi < 18.5) $bmiStatus = 'Kurus';
                            else $bmiStatus = 'Gemuk';
                            if ($bmi > 28) $bmiStatus .= ' ⚠️';
                            $bmiClass = $bmi > 25 ? 'vital-warning' : 'vital-normal';
                        @endphp

                        <div class="row g-3">
                            {{-- Vitals Row --}}
                            <div class="col-md-4 vital-box">
                                <div class="vital-label">Tekanan Darah</div>
                                <div class="vital-value {{ $tdClass }}">{{ $latest->blood_pressure ?? '-' }}</div>
                                <small class="text-muted">mmHg</small>
                            </div>
                            <div class="col-md-4 vital-box">
                                <div class="vital-label">Denyut Jantung</div>
                                <div class="vital-value {{ $latest->pulse && ($latest->pulse < 60 || $latest->pulse > 100) ? 'vital-warning' : 'vital-normal' }}">
                                    {{ $latest->pulse ?? '-' }}
                                </div>
                                <small class="text-muted">bpm</small>
                            </div>
                            <div class="col-md-4 vital-box">
                                <div class="vital-label">Suhu Tubuh</div>
                                <div class="vital-value {{ $latest->temperature && $latest->temperature > 37.5 ? 'vital-warning' : 'vital-normal' }}">
                                    {{ $latest->temperature ? number_format($latest->temperature, 1) . '°C' : '-' }}
                                </div>
                            </div>

                            {{-- Anthropometry --}}
                            <div class="col-md-3 vital-box">
                                <div class="vital-label">Tinggi Badan</div>
                                <div class="vital-value">{{ $latest->height ? number_format($latest->height, 1) : '-' }}</div>
                                <small class="text-muted">cm</small>
                            </div>
                            <div class="col-md-3 vital-box">
                                <div class="vital-label">Berat Badan</div>
                                <div class="vital-value">{{ $latest->weight ? number_format($latest->weight, 1) : '-' }}</div>
                                <small class="text-muted">kg</small>
                            </div>
                            <div class="col-md-3 vital-box">
                                <div class="vital-label">BMI / IMT</div>
                                <div class="vital-value {{ $bmiClass }}">
                                    {{ $bmi ? number_format($bmi, 1) : '-' }}
                                </div>
                                <small class="text-muted">{{ $bmiStatus }}</small>
                            </div>
                            <div class="col-md-3 vital-box">
                                <div class="vital-label">Lingkar Pinggang</div>
                                <div class="vital-value">{{ $latest->waist_circumference ? number_format($latest->waist_circumference, 0) : '-' }}</div>
                                <small class="text-muted">cm</small>
                            </div>

                            {{-- Labs Row --}}
                            <div class="col-md-3 vital-box">
                                <div class="vital-label">Kolesterol</div>
                                <div class="vital-value {{ $latest->cholesterol_total && $latest->cholesterol_total > 200 ? 'vital-warning' : 'vital-normal' }}">
                                    {{ $latest->cholesterol_total ? number_format($latest->cholesterol_total) : '-' }}
                                </div>
                                <small class="text-muted">mg/dL</small>
                            </div>
                            <div class="col-md-3 vital-box">
                                <div class="vital-label">Gula Darah</div>
                                <div class="vital-value {{ $latest->blood_sugar_fasting && $latest->blood_sugar_fasting > 126 ? 'vital-danger' : ($latest->blood_sugar_fasting && $latest->blood_sugar_fasting > 100 ? 'vital-warning' : 'vital-normal') }}">
                                    {{ $latest->blood_sugar_fasting ? number_format($latest->blood_sugar_fasting) : '-' }}
                                </div>
                                <small class="text-muted">mg/dL (GDP)</small>
                            </div>
                            <div class="col-md-3 vital-box">
                                <div class="vital-label">Asam Urat</div>
                                <div class="vital-value {{ $latest->uric_acid && $latest->uric_acid > 7 ? 'vital-warning' : 'vital-normal' }}">
                                    {{ $latest->uric_acid ? number_format($latest->uric_acid, 1) : '-' }}
                                </div>
                                <small class="text-muted">mg/dL</small>
                            </div>
                            <div class="col-md-3 vital-box">
                                <div class="vital-label">Hemoglobin</div>
                                <div class="vital-value {{ $latest->hemoglobin && $latest->hemoglobin < 12 ? 'vital-danger' : ($latest->hemoglobin && $latest->hemoglobin < 13 ? 'vital-warning' : 'vital-normal') }}">
                                    {{ $latest->hemoglobin ? number_format($latest->hemoglobin, 1) : '-' }}
                                </div>
                                <small class="text-muted">g/dL</small>
                            </div>

                            {{-- Diagnosis & Recommendation --}}
                            <div class="col-12 mt-3">
                                @if($latest->diagnosis)
                                <div class="alert alert-info py-2 mb-2">
                                    <strong><i class="ri-stethoscope-line me-1"></i>Diagnosa:</strong>
                                    {{ $latest->diagnosis }}
                                </div>
                                @endif
                                @if($latest->recommendation)
                                <div class="alert alert-light py-2 mb-2 border">
                                    <strong>Saran:</strong> {{ $latest->recommendation }}
                                </div>
                                @endif
                                @if($latest->fitness_status)
                                <span class="badge bg-{{ match($latest->fitness_status) {
                                    'sehat' => 'success',
                                    'sehat_dengan_catatan' => 'warning',
                                    'belum_sehat' => 'danger',
                                    default => 'secondary'
                                } }}">
                                    Status: {{ ucfirst(str_replace('_', ' ', $latest->fitness_status)) }}
                                </span>
                                @endif
                            </div>

                            <div class="col-12 text-muted small mt-2">
                                <i class="ri-calendar-line me-1"></i> Diperiksa pada:
                                {{ \Carbon\Carbon::parse($latest->check_date)->isoFormat('dddd, D MMMM Y') }}
                                &middot; Sumber: {{ ucfirst($latest->source ?? 'mcu') }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="mdi mdi-chart-line fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Belum ada riwayat pemeriksaan kesehatan.</p>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addHealthRecordModal">
                                <i class="ri-add-line me-1"></i>Tambah Pemeriksaan Pertama
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- History Records Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-history-line me-2"></i>Riwayat Pemeriksaan</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Tekanan Darah</th>
                                <th>Nadi</th>
                                <th>BMI</th>
                                <th>Kolesterol</th>
                                <th>Gula Darah</th>
                                <th>Hb</th>
                                <th>Diagnosa</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $rec)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $rec->check_date->isoFormat('D MMM Y') }}</div>
                                    <small class="text-muted">{{ $rec->created_at->format('H:i') }}</small>
                                </td>
                                <td>{{ $rec->blood_pressure ?? '-' }}</td>
                                <td>{{ $rec->pulse ? $rec->pulse.' bpm' : '-' }}</td>
                                <td>
                                    @if($rec->bmi)
                                        <span class="badge bg-{{ $rec->bmi > 25 ? 'warning' : 'success' }}-subtle text-{{ $rec->bmi > 25 ? 'warning' : 'success' }}">
                                            {{ number_format($rec->bmi, 1) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $rec->cholesterol_total ? number_format($rec->cholesterol_total) : '-' }}</td>
                                <td>
                                    @if($rec->blood_sugar_fasting)
                                        <span class="{{ $rec->blood_sugar_fasting > 100 ? 'text-warning' : 'text-success' }}">
                                            {{ number_format($rec->blood_sugar_fasting) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $rec->hemoglobin ? number_format($rec->hemoglobin, 1) : '-' }}</td>
                                <td style="max-width: 120px;">
                                    @if($rec->diagnosis)
                                        <span class="text-truncate d-inline-block" style="max-width: 120px;" title="{{ $rec->diagnosis }}">
                                            {{ Str::limit($rec->diagnosis, 30) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($rec->fitness_status)
                                        <span class="badge bg-{{ match($rec->fitness_status) {
                                            'sehat' => 'success-subtle text-success',
                                            'sehat_dengan_catatan' => 'warning-subtle text-warning',
                                            'belum_sehat' => 'danger-subtle text-danger',
                                            default => 'secondary-subtle'
                                        } }}">
                                            {{ ucfirst(str_replace('_', ' ', $rec->fitness_status)) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-soft-primary btn-sm" data-bs-toggle="modal" data-bs-target="#recordDetailModal{{ $rec->id }}">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </td>
                            </tr>

                            {{-- Detail Modal for each record --}}
                            <div class="modal fade" id="recordDetailModal{{ $rec->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detail Pemeriksaan — {{ $rec->check_date->isoFormat('D MMMM Y') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <h6 class="text-primary mb-2">Vital Signs</h6>
                                                    <dl class="small row">
                                                        <dt class="col-5 text-muted">TD:</dt>
                                                        <dd class="col-7">{{ $rec->blood_pressure ?? '-' }}</dd>
                                                        <dt class="col-5 text-muted">Nadi:</dt>
                                                        <dd class="col-7">{{ $rec->pulse ? $rec->pulse.' bpm' : '-' }}</dd>
                                                        <dt class="col-5 text-muted">Suhu:</dt>
                                                        <dd class="col-7">{{ $rec->temperature ? number_format($rec->temperature, 1).'°C' : '-' }}</dd>
                                                        <dt class="col-5 text-muted">TB:</dt>
                                                        <dd class="col-7">{{ $rec->height ? number_format($rec->height).' cm' : '-' }}</dd>
                                                        <dt class="col-5 text-muted">BB:</dt>
                                                        <dd class="col-7">{{ $rec->weight ? number_format($rec->weight).' kg' : '-' }}</dd>
                                                        <dt class="col-5 text-muted">IMT:</dt>
                                                        <dd class="col-7">{{ $rec->bmi ? number_format($rec->bmi) : '-' }}</dd>
                                                    </dl>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-primary mb-2">Laboratorium</h6>
                                                    <dl class="small row">
                                                        <dt class="col-5 text-muted">Kolesterol:</dt>
                                                        <dd class="col-7">{{ $rec->cholesterol_total ? number_format($rec->cholesterol_total).' mg/dL' : '-' }}</dd>
                                                        <dt class="col-5 text-muted">GDP:</dt>
                                                        <dd class="col-7">{{ $rec->blood_sugar_fasting ? number_format($rec->blood_sugar_fasting).' mg/dL' : '-' }}</dd>
                                                        <dt class="col-5 text-muted">Asam Urat:</dt>
                                                        <dd class="col-7">{{ $rec->uric_acid ? number_format($rec->uric_acid).' mg/dL' : '-' }}</dd>
                                                        <dt class="col-5 text-muted">Hb:</dt>
                                                        <dd class="col-7">{{ $rec->hemoglobin ? number_format($rec->hemoglobin).' g/dL' : '-' }}</dd>
                                                    </dl>
                                                </div>
                                                @if($rec->complaints || $rec->physical_examination || $rec->diagnosis || $rec->recommendation)
                                                <div class="col-12">
                                                    @if($rec->complaints)
                                                    <p><strong>Keluhan:</strong> {{ $rec->complaints }}</p>
                                                    @endif
                                                    @if($rec->physical_examination)
                                                    <p><strong>Pemeriksaan Fisik:</strong> {{ $rec->physical_examination }}</p>
                                                    @endif
                                                    @if($rec->diagnosis)
                                                    <p><strong>Diagnosa:</strong> {{ $rec->diagnosis }}</p>
                                                    @endif
                                                    @if($rec->recommendation)
                                                    <p><strong>Rekomendasi:</strong> {{ $rec->recommendation }}</p>
                                                    @endif
                                                    @if($rec->referred_to_faskes)
                                                    <div class="alert alert-danger py-1 small">
                                                        <i class="ri-hospital-line me-1"></i> Diritujuk ke faskes
                                                        @if($rec->referral_reason)
                                                            — {{ $rec->referral_reason }}
                                                        @endif
                                                    </div>
                                                    @endif
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <tr><td colspan="10" class="text-center py-4 text-muted">Tidak ada riwayat pemeriksaan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($records->hasPages())
                <div class="card-footer">
                    {{ $records->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
// Auto-calculate BMI in form
document.getElementById('healthBmiCalc').addEventListener('change', calculateBmi);
document.getElementById('healthHeight').addEventListener('input', calculateBmi);
document.getElementById('healthWeight').addEventListener('input', calculateBmi);

function calculateBmi() {
    const h = parseFloat(document.getElementById('healthHeight').value);
    const w = parseFloat(document.getElementById('healthWeight').value);
    const bmiEl = document.getElementById('healthBmi');
    if (h > 0 && w > 0) {
        const hM = h / 100;
        const bmi = (w / (hM * hM)).toFixed(1);
        bmiEl.value = bmi;
    } else {
        bmiEl.value = '';
    }
}
</script>
@endsection

{{-- Add Health Record Modal --}}
@push('scripts')
<script>
// Open modal when button is clicked
const modalBtn = document.querySelector('[data-bs-target="#addHealthRecordModal"]');
if (modalBtn) {
    modalBtn.addEventListener('show.bs.modal', function () {
        // Reset form
        document.getElementById('healthRecordForm').reset();
    });
}
</script>
@endpush

<!-- Add Health Record Modal -->
<div class="modal fade" id="addHealthRecordModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="{{ route('user.uks.gtk-health.records.store', ['userId' => auth()->user()->id, 'gtkUuid' => $user->id]) }}" method="POST" id="healthRecordForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-file-list-3-line me-2"></i>Tambah Data Pemeriksaan Kesehatan GTK</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Tab Navigation --}}
                    <ul class="nav nav-tabs mb-3" id="healthTabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabVitals">Vital Signs</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabAnthro">Antropometri</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabLabs">Lab / Rontgen</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSummary">Diagnosa & Saran</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSpecial">Pemeriksaan Khusus</a></li>
                    </ul>

                    <div class="tab-content">
                        {{-- Tab 1: Vital Signs --}}
                        <div class="tab-pane fade show active" id="tabVitals">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small">Tekanan Darah</label>
                                    <input type="text" name="blood_pressure" class="form-control form-control-sm" placeholder="Contoh: 120/80">
                                    <small class="text-muted">Normal: 90-120 / 60-80 mmHg</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Denyut Nadi (BPM)</label>
                                    <input type="number" name="pulse" class="form-control form-control-sm" placeholder="60-100">
                                    <small class="text-muted">Normal: 60-100 bpm</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Suhu Tubuh (°C)</label>
                                    <input type="number" step="0.1" name="temperature" class="form-control form-control-sm" placeholder="36.5">
                                    <small class="text-muted">Normal: 36.1-37.2 °C</small>
                                </div>
                            </div>
                        </div>

                        {{-- Tab 2: Anthropometry --}}
                        <div class="tab-pane fade" id="tabAnthro">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small">Tinggi Badan (cm)</label>
                                    <input type="number" name="height" id="healthHeight" class="form-control form-control-sm" placeholder="165">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Berat Badan (kg)</label>
                                    <input type="number" name="weight" id="healthWeight" class="form-control form-control-sm" placeholder="60">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">BMI / IMT</label>
                                    <input type="number" step="0.1" name="bmi" id="healthBmi" class="form-control form-control-sm" readonly placeholder="Otomatis">
                                    <small class="text-muted">Normal: 18.5-24.9</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Lingkar Pinggang (cm)</label>
                                    <input type="number" name="waist_circumference" class="form-control form-control-sm" placeholder="< 80 (P) / < 90 (L)">
                                </div>
                            </div>
                        </div>

                        {{-- Tab 3: Lab --}}
                        <div class="tab-pane fade" id="tabLabs">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small">Kolesterol Total (mg/dL)</label>
                                    <input type="number" name="cholesterol_total" class="form-control form-control-sm" placeholder="< 200">
                                    <small class="text-muted">Desirable: < 200 mg/dL</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Trigliserida (mg/dL)</label>
                                    <input type="number" name="triglycerides" class="form-control form-control-sm" placeholder="< 150">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Gula Darah Puas (mg/dL)</label>
                                    <input type="number" name="blood_sugar_fasting" class="form-control form-control-sm" placeholder="70-100">
                                    <small class="text-muted">Normal: 70-100 mg/dL</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Asam Urat (mg/dL)</label>
                                    <input type="number" step="0.1" name="uric_acid" class="form-control form-control-sm" placeholder="< 7.0">
                                    <small class="text-muted">L: 3.5-7.2, P: 2.6-6.0</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Hemoglobin (g/dL)</label>
                                    <input type="number" step="0.1" name="hemoglobin" class="form-control form-control-sm" placeholder="13-17">
                                    <small class="text-muted">L: 13.8-17.2, P: 12.0-15.5</small>
                                </div>
                            </div>
                        </div>

                        {{-- Tab 4: Summary --}}
                        <div class="tab-pane fade" id="tabSummary">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Keluhan (Saat MCU)</label>
                                    <textarea name="complaints" class="form-control form-control-sm" rows="2" placeholder="Keluhan yang dirasakan saat pemeriksaan"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Hasil Pemeriksaan Fisik</label>
                                    <textarea name="physical_examination" class="form-control form-control-sm" rows="2" placeholder="Temuan pemeriksaan fisik"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Diagnosa</label>
                                    <textarea name="diagnosis" class="form-control form-control-sm" rows="2" placeholder="Diagnosa medis atau kode ICD-10"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Rekomendasi / Saran</label>
                                    <textarea name="recommendation" class="form-control form-control-sm" rows="2" placeholder="Saran medis, anjuran diet, olahraga, dll"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Status Kesapatan Kerja</label>
                                    <select name="fitness_status" class="form-select form-select-sm">
                                        <option value="">Pilih</option>
                                        <option value="sehat">Sehat</option>
                                        <option value="sehat_dengan_catatan">Sehat dengan Catatan</option>
                                        <option value="belum_sehat">Belum Sehat</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Dirujuk?</label>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="referred_to_faskes" id="refCheck" value="1">
                                        <label class="form-check-label small" for="refCheck">Ya, dirujuk ke faskes</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Sumber Data</label>
                                    <select name="source" class="form-select form-select-sm">
                                        <option value="mcu">MCU Rutin</option>
                                        <option value="mandiri">Check-up Mandiri</option>
                                        <option value="medical_check">Medical Check</option>
                                        <option value="klinik">Klinik</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Tab 5: Special --}}
                        <div class="tab-pane fade" id="tabSpecial">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small">Visus Mata Kanan</label>
                                    <input type="text" name="right_eye_vision" class="form-control form-control-sm" placeholder="6/6">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Visus Mata Kiri</label>
                                    <input type="text" name="left_eye_vision" class="form-control form-control-sm" placeholder="6/6">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Peak Flow (L/min)</label>
                                    <input type="number" name="peak_flow" class="form-control form-control-sm" placeholder="400-700">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Status Merokok</label>
                                    <select name="smoking_status" class="form-select form-select-sm">
                                        <option value="">Pilih</option>
                                        <option value="tidak_pernah">Tidak Pernah</option>
                                        <option value="mantan">Mantan Perokok</option>
                                        <option value="aktif">Perokok Aktif</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Aktivitas Fisik</label>
                                    <select name="physical_activity" class="form-select form-select-sm">
                                        <option value="">Pilih</option>
                                        <option value="jarang">Jarang</option>
                                        <option value="sedang">Sedang</option>
                                        <option value="sering">Sering</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small">Tanggal Periksa</label>
                        <input type="date" name="check_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="ri-save-3-line me-1"></i>Simpan Pemeriksaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
