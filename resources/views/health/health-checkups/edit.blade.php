@extends('layouts.master')
@section('title') Edit Medical Check-up @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}">Medical Check-up</a> @endslot
        @slot('title') Edit Medical Check-up @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.uks.health-checkups.update', ['userId' => $userId, 'uuid' => $checkup->id]) }}">
        @csrf @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light"><h5 class="mb-0">Form Medical Check-up</h5></div>
                    <div class="card-body">
                        @component('components.student-select', [
                            'label' => 'Nama Santri',
                            'inputId' => 'studentFilter',
                            'selectId' => 'studentSelect',
                            'selectName' => 'student_id',
                            'groupedStudents' => $groupedStudents,
                            'selectedId' => $checkup->student_id,
                            'errorName' => 'student_id',
                        ])@endcomponent

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Pemeriksaan <span class="text-danger">*</span></label>
                                    <input type="date" name="checkup_date" class="form-control @error('checkup_date') is-invalid @enderror" value="{{ old('checkup_date', $checkup->checkup_date?->format('Y-m-d')) }}" required>
                                    @error('checkup_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Jenis Pemeriksaan <span class="text-danger">*</span></label>
                                    <select name="checkup_type" class="form-control @error('checkup_type') is-invalid @enderror" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach(['rutin','akar','masuk','lainnya'] as $t)
                                            <option value="{{ $t }}" {{ old('checkup_type', $checkup->checkup_type) == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                                        @endforeach
                                    </select>
                                    @error('checkup_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Petugas Pemeriksaan</label>
                                    <select name="exam_by" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        @foreach($examStaff as $staff)
                                            <option value="{{ $staff->id }}" {{ old('exam_by', $checkup->exam_by) == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <h6 class="text-muted mt-3 mb-2">Ukuran Tubuh</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tinggi Badan (cm)</label>
                                    <input type="number" name="height_cm" class="form-control" value="{{ old('height_cm', $checkup->height_cm) }}" placeholder="Contoh: 145">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Berat Badan (kg)</label>
                                    <input type="number" name="weight_kg" class="form-control" value="{{ old('weight_kg', $checkup->weight_kg) }}" placeholder="Contoh: 38">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">IMT (dihitung otomatis)</label>
                                    <input type="number" step="0.1" class="form-control" value="{{ $checkup->bmi }}" readonly>
                                </div>
                            </div>
                        </div>

                        <h6 class="text-muted mt-3 mb-2">Pemeriksaan Mata & Pendengaran</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Visus Mata Kiri</label>
                                    <input type="number" step="0.1" name="vision_left" class="form-control" value="{{ old('vision_left', $checkup->vision_left) }}" placeholder="0.0 - 3.0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Visus Mata Kanan</label>
                                    <input type="number" step="0.1" name="vision_right" class="form-control" value="{{ old('vision_right', $checkup->vision_right) }}" placeholder="0.0 - 3.0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Status Pendengaran</label>
                                    <select name="hearing_status" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        @foreach(['normal','kurang','tidak_ada'] as $h)
                                            <option value="{{ $h }}" {{ old('hearing_status', $checkup->hearing_status) == $h ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$h)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Status Gigi</label>
                                    <select name="dental_status" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        @foreach(['normal','karies','gangguan'] as $d)
                                            <option value="{{ $d }}" {{ old('dental_status', $checkup->dental_status) == $d ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$d)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <h6 class="text-muted mt-3 mb-2">Skrining TBC</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Hasil Skrining TBC</label>
                                    <select name="tb_screening_result" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        @foreach(['negatif','akur','laten','aktif_suspect'] as $r)
                                            <option value="{{ $r }}" {{ old('tb_screening_result', $checkup->tb_screening_result) == $r ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Catatan TB</label>
                                    <textarea name="tb_notes" class="form-control" rows="2">{{ old('tb_notes', $checkup->tb_notes) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_school_entry" class="form-check-input" id="isSchoolEntry" value="1" {{ old('is_school_entry', $checkup->is_school_entry) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isSchoolEntry">Pemeriksaan Masuk Sekolah</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $checkup->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                            <a href="{{ route('user.uks.health-checkups.show', ['userId' => $userId, 'uuid' => $checkup->id]) }}" class="btn btn-secondary"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection