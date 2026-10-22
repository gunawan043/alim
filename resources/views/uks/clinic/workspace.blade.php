@extends('layouts.master')

@php $isClinicMode = (string) setting('uks_mode', 'simple') === 'clinic'; @endphp

@section('title', $isClinicMode ? 'Lembar Pemeriksaan UKS' : 'Pemeriksaan UKS')

@section('css')
    @include('uks._ui-styles')
@endsection

@section('content')
    @php
        $student = $patient->student;
        $healthRecord = $student?->healthRecord;
        $dorm = $student?->activeDormitoryResident?->dormitory?->name;
        $age = $student?->birth_date ? $student->birth_date->age : null;
        $statusColor = match ($patient->status) {
            \App\Models\Uks\UksPatient::STATUS_WAITING,
            \App\Models\Uks\UksPatient::STATUS_TREATED => 'warning',
            \App\Models\Uks\UksPatient::STATUS_OBSERVATION,
            \App\Models\Uks\UksPatient::STATUS_INPATIENT => 'primary',
            \App\Models\Uks\UksPatient::STATUS_RESTING_UKS => 'info',
            \App\Models\Uks\UksPatient::STATUS_RETURN_DORM,
            \App\Models\Uks\UksPatient::STATUS_RETURN_SCHOOL,
            \App\Models\Uks\UksPatient::STATUS_LEAVING,
            \App\Models\Uks\UksPatient::STATUS_COMPLETED => 'success',
            \App\Models\Uks\UksPatient::STATUS_PICKED_UP => 'secondary',
            \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC,
            \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL => 'danger',
            default => 'light',
        };
        $latestVitals = $latestEncounter?->vitals ?? $patient->vitals ?? [];
        $encounters = $patient->encounters->sortByDesc('created_at');
        $prescriptions = $patient->prescriptions->sortByDesc('prescribed_at');
        $referrals = $patient->referrals->sortByDesc('referred_at');
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') {{ $isClinicMode ? 'Klinik' : 'Pemeriksaan' }} @endslot
        @slot('li_3') {{ $isClinicMode ? ($patient->queue_number ?? 'Pasien') : 'Pasien' }} @endslot
        @slot('title') {{ $isClinicMode ? 'Lembar Pemeriksaan' : 'Pemeriksaan UKS' }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i><strong>Periksa kembali input:</strong> {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="card card-animate border border-{{ $statusColor }}-subtle mb-3">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3 flex-wrap">
                <div class="avatar-lg flex-shrink-0">
                    <span class="avatar-title bg-{{ $student?->gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $student?->gender === 'P' ? 'danger' : 'primary' }} rounded-circle fs-2 fw-bold">
                        {{ strtoupper(substr($student?->name ?? '?', 0, 1)) }}
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h4 class="mb-0">{{ $student?->name ?? '-' }}</h4>
                        <span class="badge bg-dark fs-13">{{ $patient->queue_number ?? 'Tanpa antrean' }}</span>
                        <span class="badge bg-{{ $patient->triage_color }}-subtle text-{{ $patient->triage_color }}">{{ $patient->triage_label }}</span>
                        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ $patient->queue_label }}</span>
                        <span class="badge bg-light text-muted">{{ \App\Models\Uks\UksPatient::$statusLabels[$patient->status] ?? $patient->status }}</span>
                        <span class="badge bg-{{ $patient->care_location_color }}-subtle text-{{ $patient->care_location_color }}">
                            <i class="{{ $patient->care_location_icon }} me-1"></i>{{ $patient->care_location_label }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted fs-12 mt-2">
                        <span><i class="ri-id-card-line me-1"></i>RM: <strong>{{ $patient->medical_record_no ?? '-' }}</strong></span>
                        <span><i class="ri-user-line me-1"></i>{{ $student?->gender === 'P' ? 'Putri' : ($student?->gender === 'L' ? 'Putra' : '-') }} @if($age) · {{ $age }} tahun @endif</span>
                        <span><i class="ri-building-line me-1"></i>{{ $dorm ?? 'Tanpa asrama' }}</span>
                        <span><i class="ri-calendar-line me-1"></i>Masuk {{ $patient->admitted_at?->format('d M Y H:i') ?? '-' }}</span>
                        @if($patient->currentBedAssignment?->bed)
                            <span><i class="ri-hotel-bed-line me-1"></i>{{ $patient->currentBedAssignment->bed->identifier }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('user.uks.clinic.queue', ['userId' => auth()->id()]) }}" class="btn btn-soft-secondary btn-sm">
                        <i class="ri-arrow-left-line me-1"></i>Antrean
                    </a>
                    <a href="{{ route('user.uks.patients.show', ['userId' => auth()->id(), 'uuid' => $patient->id]) }}" class="btn btn-soft-primary btn-sm">
                        <i class="ri-file-text-line me-1"></i>Detail Pasien
                    </a>
                    @if(in_array($patient->queue_status, [\App\Models\Uks\UksPatient::QUEUE_WAITING, \App\Models\Uks\UksPatient::QUEUE_CALLED], true))
                        <form method="POST" action="{{ route('user.uks.clinic.start', ['userId' => auth()->id(), 'uuid' => $patient->id]) }}">
                            @csrf
                            <button class="btn btn-primary btn-sm"><i class="ri-play-line me-1"></i>Mulai Pemeriksaan</button>
                        </form>
                    @endif
                    @if(in_array($patient->queue_status, [\App\Models\Uks\UksPatient::QUEUE_IN_PROGRESS], true))
                        <form method="POST" action="{{ route('user.uks.clinic.finish', ['userId' => auth()->id(), 'uuid' => $patient->id]) }}">
                            @csrf
                            <button class="btn btn-success btn-sm"><i class="ri-check-line me-1"></i>Selesai</button>
                        </form>
                    @endif
                    @if($patient->queue_status === \App\Models\Uks\UksPatient::QUEUE_PHARMACY)
                        <a href="{{ route('user.uks.pharmacy.index', ['userId' => auth()->id()]) }}" class="btn btn-success btn-sm">
                            <i class="ri-capsule-line me-1"></i>Buka Farmasi
                        </a>
                    @endif
                </div>
            </div>

            @if($patient->allergies || $healthRecord?->allergies)
                <div class="alert alert-danger d-flex align-items-center gap-2 mt-3 mb-0 py-2">
                    <i class="ri-error-warning-line fs-5"></i>
                    <div><strong>Alergi:</strong> {{ $patient->allergies ?: $healthRecord?->allergies }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            {{-- SOAP + RESEP --}}
            <form method="POST" action="{{ route('user.uks.clinic.encounter.store', ['userId' => auth()->id(), 'uuid' => $patient->id]) }}" id="soap-form">
                @csrf

                <div class="card mb-3">
                    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ri-stethoscope-line me-2 text-primary"></i>Pemeriksaan Klinis (SOAP)</h5>
                        @if($latestEncounter)
                            <span class="badge bg-light text-muted">Pemeriksaan terakhir: {{ $latestEncounter->created_at->format('d M Y H:i') }}</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">S — Subjective (keluhan pasien)</label>
                                <textarea name="chief_complaint" rows="2" class="form-control" placeholder="Keluhan utama yang disampaikan pasien...">{{ old('chief_complaint', $latestEncounter?->chief_complaint ?? $patient->chief_complaint) }}</textarea>
                                <textarea name="subjective" rows="2" class="form-control mt-2" placeholder="Riwayat singkat, onset, faktor pemicu, keluhan penyerta...">{{ old('subjective', $latestEncounter?->subjective) }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">O — Objective (tanda vital & pemeriksaan fisik)</label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">TD</span>
                                            <input type="text" name="blood_pressure" class="form-control" placeholder="120/80" value="{{ old('blood_pressure', $latestVitals['blood_pressure'] ?? '') }}">
                                            <span class="input-group-text">mmHg</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Suhu</span>
                                            <input type="number" step="0.1" name="temperature" class="form-control" placeholder="36.5" value="{{ old('temperature', $latestVitals['temperature'] ?? '') }}">
                                            <span class="input-group-text">°C</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Nadi</span>
                                            <input type="number" name="pulse" class="form-control" placeholder="80" value="{{ old('pulse', $latestVitals['pulse'] ?? '') }}">
                                            <span class="input-group-text">/m</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">RR</span>
                                            <input type="number" name="respiration_rate" class="form-control" placeholder="20" value="{{ old('respiration_rate', $latestVitals['respiration_rate'] ?? '') }}">
                                            <span class="input-group-text">/m</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">SpO2</span>
                                            <input type="number" name="oxygen_saturation" class="form-control" placeholder="98" value="{{ old('oxygen_saturation', $latestVitals['oxygen_saturation'] ?? '') }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">TB</span>
                                            <input type="number" step="0.1" name="height" class="form-control" placeholder="160" value="{{ old('height', $latestVitals['height'] ?? '') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">BB</span>
                                            <input type="number" step="0.1" name="weight" class="form-control" placeholder="50" value="{{ old('weight', $latestVitals['weight'] ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                                <textarea name="physical_exam" rows="2" class="form-control mt-2" placeholder="Pemeriksaan fisik: mata, THT, thorax, abdomen, kulit...">{{ old('physical_exam', $latestEncounter?->physical_exam) }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">A — Assessment (diagnosis)</label>
                                <div class="row g-2">
                                    <div class="col-md-8">
                                        <input type="text" name="diagnosis" class="form-control" placeholder="Diagnosis kerja / akhir" value="{{ old('diagnosis', $latestEncounter?->diagnosis ?? $patient->diagnosis) }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="icd10_code" class="form-control" placeholder="ICD-10" value="{{ old('icd10_code', $latestEncounter?->icd10_code) }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="icd10_name" class="form-control" placeholder="Nama ICD" value="{{ old('icd10_name', $latestEncounter?->icd10_name) }}">
                                    </div>
                                    <div class="col-12">
                                        <textarea name="assessment" rows="2" class="form-control" placeholder="Ringkasan assessment / diagnosis banding...">{{ old('assessment', $latestEncounter?->assessment) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">P — Plan (tindakan & rencana)</label>
                                <textarea name="treatment" rows="2" class="form-control" placeholder="Tindakan yang dilakukan (kompres, nebulisasi, wound care, dll.)">{{ old('treatment', $latestEncounter?->treatment) }}</textarea>
                                <textarea name="plan" rows="2" class="form-control mt-2" placeholder="Rencana: obat, istirahat, kontrol, edukasi...">{{ old('plan', $latestEncounter?->plan) }}</textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Disposisi</label>
                                <select name="disposition" class="form-select">
                                    <option value="">— Pilih disposisi —</option>
                                    <option value="pulang" {{ old('disposition', $latestEncounter?->disposition) === 'pulang' ? 'selected' : '' }}>Pulang</option>
                                    <option value="kontrol" {{ old('disposition', $latestEncounter?->disposition) === 'kontrol' ? 'selected' : '' }}>Kontrol / rawat jalan</option>
                                    <option value="observasi" {{ old('disposition', $latestEncounter?->disposition) === 'observasi' ? 'selected' : '' }}>Observasi UKS</option>
                                    <option value="rawat_uks" {{ old('disposition', $latestEncounter?->disposition) === 'rawat_uks' ? 'selected' : '' }}>Rawat inap UKS</option>
                                    <option value="rujuk" {{ old('disposition', $latestEncounter?->disposition) === 'rujuk' ? 'selected' : '' }}>Rujuk ke faskes</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kontrol berikutnya</label>
                                <input type="date" name="follow_up_date" class="form-control" value="{{ old('follow_up_date', $latestEncounter?->follow_up_date?->toDateString()) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Alergi (update)</label>
                                <input type="text" name="allergies" class="form-control" value="{{ old('allergies', $patient->allergies ?? $healthRecord?->allergies) }}" placeholder="Alergi obat/makanan">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Catatan tambahan</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="Catatan perawat/dokter...">{{ old('notes', $latestEncounter?->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RESEP --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ri-capsule-line me-2 text-success"></i>Resep Obat (opsional)</h5>
                        <button type="button" class="btn btn-sm btn-soft-success" id="add-rx-row"><i class="ri-add-line me-1"></i>Tambah Obat</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="rx-table">
                                <thead class="table-light fs-12 text-muted">
                                    <tr>
                                        <th style="min-width: 180px;">Obat</th>
                                        <th style="width: 110px;">Dosis</th>
                                        <th style="width: 120px;">Frekuensi</th>
                                        <th style="width: 80px;">Jumlah</th>
                                        <th style="width: 100px;">Rute</th>
                                        <th style="width: 90px;">Hari</th>
                                        <th>Aturan pakai</th>
                                        <th style="width: 45px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="rx-rows"></tbody>
                            </table>
                        </div>
                        <p class="text-muted fs-12 mb-0 mt-2"><i class="ri-information-line me-1"></i>Kosongkan resep jika pasien tidak memerlukan obat. Resep otomatis masuk ke antrean farmasi.</p>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Pemeriksaan</button>
                    </div>
                </div>
            </form>

            {{-- RIWAYAT PEMERIKSAAN --}}
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="ri-history-line me-2 text-info"></i>Riwayat Pemeriksaan</h5>
                    <span class="badge bg-info-subtle text-info">{{ $encounters->count() }} pemeriksaan</span>
                </div>
                <div class="card-body">
                    @forelse($encounters->take(5) as $encounter)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <span class="badge bg-light text-muted">{{ $encounter->created_at->format('d M Y H:i') }}</span>
                                    <strong class="ms-2">{{ $encounter->diagnosis ?: 'Pemeriksaan' }}</strong>
                                    @if($encounter->icd10_code)
                                        <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $encounter->icd10_code }}</span>
                                    @endif
                                </div>
                                <small class="text-muted"><i class="ri-user-line me-1"></i>{{ $encounter->performedBy?->name ?? '-' }}</small>
                            </div>
                            <div class="row g-2 mt-1 fs-13">
                                <div class="col-md-6"><span class="text-muted">S:</span> {{ \Illuminate\Support\Str::limit($encounter->subjective ?? $encounter->chief_complaint ?? '-', 100) }}</div>
                                <div class="col-md-6"><span class="text-muted">O:</span> {{ \Illuminate\Support\Str::limit($encounter->physical_exam ?? '-', 100) }}</div>
                                <div class="col-md-6"><span class="text-muted">A:</span> {{ \Illuminate\Support\Str::limit($encounter->assessment ?? '-', 100) }}</div>
                                <div class="col-md-6"><span class="text-muted">P:</span> {{ \Illuminate\Support\Str::limit($encounter->plan ?? $encounter->treatment ?? '-', 100) }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada riwayat pemeriksaan.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            {{-- RINGKASAN PASIEN --}}
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-profile-line me-2 text-primary"></i>Ringkasan Pasien</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 fs-13">
                        <dt class="col-5 text-muted fw-normal">No. RM</dt>
                        <dd class="col-7 fw-semibold">{{ $patient->medical_record_no ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Antrean</dt>
                        <dd class="col-7">{{ $patient->queue_number ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Gol. Darah</dt>
                        <dd class="col-7">{{ $healthRecord?->blood_type ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Alergi</dt>
                        <dd class="col-7 text-danger">{{ $patient->allergies ?? $healthRecord?->allergies ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Penyakit kronis</dt>
                        <dd class="col-7">{{ $healthRecord?->chronic_diseases ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Obat rutin</dt>
                        <dd class="col-7">{{ $healthRecord?->current_medications ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Asuransi</dt>
                        <dd class="col-7">{{ $healthRecord?->insurance_provider ?? '-' }} {{ $healthRecord?->insurance_number ? '· '.$healthRecord->insurance_number : '' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Kontak darurat</dt>
                        <dd class="col-7">{{ $healthRecord?->emergency_notes ?? '-' }}</dd>
                    </dl>
                    @if($patient->currentBedAssignment?->bed)
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted fs-11 text-uppercase">Bed</div>
                                <div class="fw-semibold">{{ $patient->currentBedAssignment->bed->identifier }}</div>
                            </div>
                            <form method="POST" action="{{ route('user.uks.treatment-status.release-bed', ['userId' => auth()->id(), 'uuid' => $patient->id]) }}">
                                @csrf
                                <select name="care_location" class="form-select form-select-sm mb-1" required>
                                    @foreach(\App\Models\Uks\UksPatient::$careLocationLabels as $key => $label)
                                        <option value="{{ $key }}" {{ $key === \App\Models\Uks\UksPatient::LOCATION_ASRAMA ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="notes" class="form-control form-control-sm mb-1" placeholder="Catatan keluar bed">
                                <button class="btn btn-sm btn-soft-warning w-100"><i class="ri-logout-box-r-line me-1"></i>Kosongkan</button>
                            </form>
                        </div>
                    @else
                        <hr>
                        <a href="{{ route('user.uks.treatment-status.show', ['userId' => auth()->id(), 'uuid' => $patient->id]) }}" class="btn btn-soft-info btn-sm w-100">
                            <i class="ri-bed-line me-1"></i>Tempatkan / Kelola Bed
                        </a>
                    @endif
                </div>
            </div>

            {{-- RESEP AKTIF --}}
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="ri-capsule-line me-2 text-success"></i>Resep</h5>
                    <a href="{{ route('user.uks.pharmacy.index', ['userId' => auth()->id()]) }}" class="btn btn-sm btn-soft-success">Farmasi</a>
                </div>
                <div class="card-body">
                    @forelse($prescriptions->take(5) as $prescription)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold fs-13">{{ $prescription->prescription_no }}</span>
                                <span class="badge bg-{{ $prescription->status === 'diserahkan' ? 'success' : ($prescription->status === 'dibatalkan' ? 'secondary' : 'warning') }}-subtle text-{{ $prescription->status === 'diserahkan' ? 'success' : ($prescription->status === 'dibatalkan' ? 'secondary' : 'warning') }}">{{ $prescription->status_label }}</span>
                            </div>
                            <ul class="mb-0 mt-1 ps-3 fs-12 text-muted">
                                @foreach($prescription->items as $item)
                                    <li>{{ $item->medicine_name }} — {{ $item->dosage ?? '-' }} · {{ $item->quantity }} {{ $item->inventory->unit ?? 'unit' }} @if($item->frequency) · {{ $item->frequency }} @endif</li>
                                @endforeach
                            </ul>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">{{ $prescription->prescribed_at?->format('d M H:i') }}</small>
                                <a href="{{ route('user.uks.prescriptions.print', ['userId' => auth()->id(), 'uuid' => $prescription->id]) }}" target="_blank" class="btn btn-sm btn-link p-0">Cetak</a>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0 fs-13">Belum ada resep.</p>
                    @endforelse
                </div>
            </div>

            {{-- RUJUKAN --}}
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-hospital-line me-2 text-danger"></i>Rujukan</h5>
                </div>
                <div class="card-body">
                    @forelse($referrals->take(3) as $referral)
                        <div class="border rounded p-2 mb-2 fs-13">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $referral->facility_name }}</strong>
                                <span class="badge bg-{{ $referral->status === 'kembali' ? 'success' : 'danger' }}-subtle text-{{ $referral->status === 'kembali' ? 'success' : 'danger' }}">{{ $referral->status_label }}</span>
                            </div>
                            <div class="text-muted fs-12">{{ $referral->referral_no }} · {{ $referral->referred_at?->format('d M Y H:i') }}</div>
                            <div class="mt-1">{{ \Illuminate\Support\Str::limit($referral->reason, 90) }}</div>
                            <div class="d-flex gap-2 mt-1">
                                <a href="{{ route('user.uks.referrals.print', ['userId' => auth()->id(), 'uuid' => $referral->id]) }}" target="_blank" class="btn btn-sm btn-link p-0">Surat rujukan</a>
                                @if($referral->status !== 'kembali')
                                    <button type="button" class="btn btn-sm btn-link p-0 text-success" data-bs-toggle="collapse" data-bs-target="#return-{{ $referral->id }}">Tandai kembali</button>
                                @endif
                            </div>
                            <div class="collapse mt-2" id="return-{{ $referral->id }}">
                                <form method="POST" action="{{ route('user.uks.referrals.return', ['userId' => auth()->id(), 'uuid' => $referral->id]) }}">
                                    @csrf
                                    <select name="return_status" class="form-select form-select-sm mb-1" required>
                                        <option value="kembali_ke_asrama">Kembali ke asrama</option>
                                        <option value="kembali_ke_sekolah">Kembali ke sekolah</option>
                                        <option value="dijemput_wali">Dijemput wali</option>
                                        <option value="pulang">Pulang</option>
                                        <option value="selesai">Selesai</option>
                                    </select>
                                    <input type="text" name="outcome" class="form-control form-control-sm mb-1" placeholder="Hasil dari faskes...">
                                    <button class="btn btn-sm btn-success w-100">Simpan</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted fs-13">Belum pernah dirujuk.</p>
                    @endforelse

                    <hr>
                    <form method="POST" action="{{ route('user.uks.clinic.referrals.store', ['userId' => auth()->id(), 'uuid' => $patient->id]) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label fs-13 mb-1">Fasilitas rujukan</label>
                            <select name="facility_id" class="form-select form-select-sm">
                                <option value="">— Pilih faskes —</option>
                                @foreach($facilities as $facility)
                                    <option value="{{ $facility->id }}">{{ $facility->facility_name }} ({{ $facility->facility_type_text }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fs-13 mb-1">Atau nama faskes manual</label>
                            <input type="text" name="facility_name" class="form-control form-control-sm" placeholder="Nama klinik / RS">
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label fs-13 mb-1">Urgensi</label>
                                <select name="urgency" class="form-select form-select-sm">
                                    <option value="rutin">Rutin</option>
                                    <option value="segera">Segera</option>
                                    <option value="darurat">Darurat</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fs-13 mb-1">Transport</label>
                                <input type="text" name="transport" class="form-control form-control-sm" placeholder="Ambulans / kendaraan">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fs-13 mb-1">Alasan rujukan <span class="text-danger">*</span></label>
                            <textarea name="reason" rows="2" class="form-control form-control-sm" required placeholder="Alasan medis rujukan..."></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fs-13 mb-1">Pendamping</label>
                            <input type="text" name="accompanied_by" class="form-control form-control-sm" placeholder="Nama pendamping">
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm w-100"><i class="ri-send-plane-line me-1"></i>Buat Rujukan</button>
                    </form>
                </div>
            </div>

            {{-- KUNJUNGAN SEBELUMNYA --}}
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-history-line me-2 text-secondary"></i>Kunjungan Sebelumnya</h5>
                </div>
                <div class="card-body">
                    @forelse($previousVisits as $visit)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2 fs-13">
                            <div>
                                <div class="fw-medium">{{ $visit->admitted_at?->format('d M Y') ?? '-' }}</div>
                                <div class="text-muted fs-12">{{ \Illuminate\Support\Str::limit($visit->diagnosis ?? $visit->chief_complaint ?? '-', 40) }}</div>
                            </div>
                            <a href="{{ route('user.uks.patients.show', ['userId' => auth()->id(), 'uuid' => $visit->id]) }}" class="btn btn-sm btn-link p-0">Lihat</a>
                        </div>
                    @empty
                        <p class="text-muted fs-13 mb-0">Kunjungan pertama pasien ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.getElementById('rx-rows');
    const addButton = document.getElementById('add-rx-row');
    const inventory = @json($inventory->map(fn ($item) => [
        'id' => $item->id,
        'name' => $item->medicine_name,
        'unit' => $item->unit,
        'stock' => (float) $item->current_stock,
    ])->values());

    let index = 0;

    function addRow() {
        const current = index++;
        const wrapper = document.createElement('tr');
        wrapper.innerHTML = `
            <td>
                <select name="items[${current}][inventory_id]" class="form-select form-select-sm rx-inventory">
                    <option value="">— Obat manual —</option>
                    ${inventory.map(item => `<option value="${item.id}">${item.name} (stok ${item.stock} ${item.unit})</option>`).join('')}
                </select>
                <input type="text" name="items[${current}][medicine_name]" class="form-control form-control-sm mt-1" placeholder="Nama obat manual">
            </td>
            <td><input type="text" name="items[${current}][dosage]" class="form-control form-control-sm" placeholder="500 mg"></td>
            <td><input type="text" name="items[${current}][frequency]" class="form-control form-control-sm" placeholder="3x1"></td>
            <td><input type="number" step="0.01" min="0.01" name="items[${current}][quantity]" class="form-control form-control-sm" value="1"></td>
            <td>
                <select name="items[${current}][route]" class="form-select form-select-sm">
                    <option value="oral">Oral</option>
                    <option value="topikal">Topikal</option>
                    <option value="injeksi">Injeksi</option>
                    <option value="inhaled">Inhalasi</option>
                    <option value="rectal">Rektal</option>
                    <option value="other">Lain</option>
                </select>
            </td>
            <td><input type="number" min="1" max="365" name="items[${current}][duration_days]" class="form-control form-control-sm" placeholder="3"></td>
            <td><input type="text" name="items[${current}][instructions]" class="form-control form-control-sm" placeholder="Sesudah makan"></td>
            <td><button type="button" class="btn btn-sm btn-soft-danger remove-rx"><i class="ri-delete-bin-line"></i></button></td>
        `;
        rows.appendChild(wrapper);
    }

    addButton?.addEventListener('click', addRow);

    rows?.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-rx');
        if (button) {
            button.closest('tr')?.remove();
        }
    });

    if (rows && rows.children.length === 0) {
        addRow();
    }
});
</script>
@endpush
