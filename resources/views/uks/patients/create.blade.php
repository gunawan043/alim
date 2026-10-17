@extends('layouts.master')
@section('title') Pendaftaran Pasien UKS @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Rekam Medis Pasien @endslot
        @slot('title') Pendaftaran Pasien Baru @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>
            <strong>Validasi Gagal:</strong> Periksa input Anda.
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form action="{{ route('user.uks.patients.store', ['userId' => auth()->user()->id]) }}" method="POST">
        @csrf

        <div class="row g-3">

            {{-- ============================================================
                 STEP 1 — DATA PASIEN
            ============================================================ --}}
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">
                            <span class="badge bg-primary me-2">1</span>
                            <i class="ri-user-line align-bottom me-1"></i> Data Pasien
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="student_id" class="form-label">
                                    Siswa <span class="text-danger">*</span>
                                </label>
                                <select name="student_id" id="student_id"
                                        class="form-select select2-siswa @error('student_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Siswa --</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                            {{ $student->name }} ({{ $student->gender === 'L' ? 'Putra' : 'Putri' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="patient_type" class="form-label">
                                    Tipe Kunjungan <span class="text-danger">*</span>
                                </label>
                                <select name="patient_type" id="patient_type"
                                        class="form-select @error('patient_type') is-invalid @enderror" required>
                                    <option value="rawat" {{ old('patient_type') == 'rawat' ? 'selected' : '' }}>
                                        <i class="ri-hospital-line"></i> Rawat Jalan
                                    </option>
                                    <option value="pulang" {{ old('patient_type') == 'pulang' ? 'selected' : '' }}>
                                        Pulang (Dirujuk)
                                    </option>
                                    <option value="balik" {{ old('patient_type') == 'balik' ? 'selected' : '' }}>
                                        Balik ke Asrama
                                    </option>
                                </select>
                                @error('patient_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 STEP 2 — TANDA VITAL & KELUHAN
            ============================================================ --}}
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">
                            <span class="badge bg-primary me-2">2</span>
                            <i class="ri-pulse-line align-bottom me-1"></i> Tanda Vital & Keluhan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="chief_complaint" class="form-label">Keluhan Utama</label>
                                <textarea name="chief_complaint" id="chief_complaint" rows="2"
                                          class="form-control @error('chief_complaint') is-invalid @enderror"
                                          placeholder="Keluhan yang disampaikan siswa...">{{ old('chief_complaint') }}</textarea>
                                @error('chief_complaint')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="blood_pressure" class="form-label">Tekanan Darah</label>
                                <div class="input-group">
                                    <input type="text" name="blood_pressure" id="blood_pressure"
                                           value="{{ old('blood_pressure') }}"
                                           class="form-control @error('blood_pressure') is-invalid @enderror"
                                           placeholder="120/80">
                                    <span class="input-group-text bg-white">mmHg</span>
                                </div>
                                @error('blood_pressure')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="temperature" class="form-label">Suhu</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="temperature" id="temperature"
                                           value="{{ old('temperature') }}"
                                           class="form-control @error('temperature') is-invalid @enderror">
                                    <span class="input-group-text bg-white">°C</span>
                                </div>
                                @error('temperature')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label for="pulse" class="form-label">Nadi</label>
                                <div class="input-group">
                                    <input type="number" name="pulse" id="pulse"
                                           value="{{ old('pulse') }}"
                                           class="form-control @error('pulse') is-invalid @enderror">
                                    <span class="input-group-text bg-white">/min</span>
                                </div>
                                @error('pulse')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label for="height" class="form-label">Tinggi</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="height" id="height"
                                           value="{{ old('height') }}"
                                           class="form-control @error('height') is-invalid @enderror">
                                    <span class="input-group-text bg-white">cm</span>
                                </div>
                                @error('height')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label for="weight" class="form-label">Berat</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="weight" id="weight"
                                           value="{{ old('weight') }}"
                                           class="form-control @error('weight') is-invalid @enderror">
                                    <span class="input-group-text bg-white">kg</span>
                                </div>
                                @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 STEP 3 — DIAGNOSIS & PENGOBATAN
            ============================================================ --}}
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">
                            <span class="badge bg-primary me-2">3</span>
                            <i class="ri-stethoscope-line align-bottom me-1"></i> Diagnosis & Pengobatan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="diagnosis" class="form-label">Diagnosis</label>
                                <textarea name="diagnosis" id="diagnosis" rows="2"
                                          class="form-control @error('diagnosis') is-invalid @enderror"
                                          placeholder="Diagnosa sementara/akhir...">{{ old('diagnosis') }}</textarea>
                                @error('diagnosis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="treatment" class="form-label">Pengobatan / Perlakuan</label>
                                <textarea name="treatment" id="treatment" rows="2"
                                          class="form-control @error('treatment') is-invalid @enderror"
                                          placeholder="Tindakan yang dilakukan...">{{ old('treatment') }}</textarea>
                                @error('treatment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="medicine_given" class="form-label">Obat yang Diberikan</label>
                                <input type="text" name="medicine_given" id="medicine_given"
                                       value="{{ old('medicine_given') }}"
                                       class="form-control @error('medicine_given') is-invalid @enderror"
                                       placeholder="Contoh: Paracetamol, Dolex">
                                @error('medicine_given')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="referred_to_faskes" class="form-label">Diruju ke Faskes?</label>
                                <select name="referred_to_faskes" id="referred_to_faskes" class="form-select">
                                    <option value="0" {{ old('referred_to_faskes', '0') == 0 ? 'selected' : '' }}>Tidak</option>
                                    <option value="1" {{ old('referred_to_faskes') == 1 ? 'selected' : '' }}>Ya</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="referral_reason" class="form-label">Alasan Rujukan</label>
                                <textarea name="referral_reason" id="referral_reason" rows="2"
                                          class="form-control @error('referral_reason') is-invalid @enderror"
                                          placeholder="Wajib diisi jika dirujuk...">{{ old('referral_reason') }}</textarea>
                                @error('referral_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 STEP 4 — CATATAN TAMBAHAN
            ============================================================ --}}
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">
                            <span class="badge bg-primary me-2">4</span>
                            <i class="ri-file-text-line align-bottom me-1"></i> Catatan Tambahan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="notes" class="form-label">Catatan</label>
                                <textarea name="notes" id="notes" rows="2"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="Catatan tambahan (opsional)...">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 ACTIONS
            ============================================================ --}}
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body d-flex gap-2 justify-content-end">
                        <a href="{{ route('user.uks.patients.index', ['userId' => auth()->user()->id]) }}" class="btn btn-light">
                            <i class="ri-close-line align-bottom me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line align-bottom me-1"></i> Simpan Pendaftaran
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('head')
{{-- Select2 sudah di-load global via CDN di layouts.master --}}
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
    const $sel = jQuery('#student_id');
    if ($sel.length && !$sel.data('select2')) {
        $sel.select2({
            theme: 'default',
            width: '100%',
            placeholder: 'Cari nama siswa...',
            allowClear: true,
            language: {
                inputTooShort: function() { return 'Ketik untuk mencari...'; },
                searching: function() { return 'Mencari...'; },
                noResults: function() { return 'Siswa tidak ditemukan'; },
            },
        });
    }
});
</script>
@endpush