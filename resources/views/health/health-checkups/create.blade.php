@extends('layouts.master')
@section('title') Tambah Medical Check-up @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}">Medical Check-up</a> @endslot
        @slot('title') Tambah Check-up @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.uks.health-checkups.store', ['userId' => $userId]) }}">
        @csrf
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
                            'errorName' => 'student_id',
                        ])@endcomponent

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Check-up <span class="text-danger">*</span></label>
                                    <input type="date" name="checkup_date" class="form-control @error('checkup_date') is-invalid @enderror" value="{{ old('checkup_date', now()->format('Y-m-d')) }}" required>
                                    @error('checkup_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Jenis Pemeriksaan</label>
                                    <select name="checkup_type" class="form-control">
                                        <option value="rutin" {{ old('checkup_type','rutin')=='rutin'?'selected':'' }}>Rutin</option>
                                        <option value="akar" {{ old('checkup_type')=='akar'?'selected':'' }}>Akar</option>
                                        <option value="masuk" {{ old('checkup_type')=='masuk'?'selected':'' }}>Masuk</option>
                                        <option value="lainnya" {{ old('checkup_type')=='lainnya'?'selected':'' }}>Lainnya</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Petugas Pemeriksa</label>
                                    <select name="exam_by" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        @foreach($examStaff as $u)
                                            <option value="{{ $u->id }}" {{ old('exam_by') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="mt-2 mb-3">
                        <h6>Antropometri</h6>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tinggi Badan (cm)</label>
                                    <input type="number" name="height_cm" class="form-control" value="{{ old('height_cm') }}" min="30" max="250" placeholder="Contoh: 145">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Berat Badan (kg)</label>
                                    <input type="number" name="weight_kg" class="form-control" value="{{ old('weight_kg') }}" min="5" max="200" step="0.1" placeholder="Contoh: 38.5">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">BMI (diisi otomatis)</label>
                                    <input type="text" class="form-control" readonly placeholder="Akan dihitung otomatis">
                                </div>
                            </div>
                        </div>

                        <hr class="mt-2 mb-3">
                        <h6>Pemeriksaan Lainnya</h6>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Visus Mata Kiri</label>
                                    <input type="number" name="vision_left" class="form-control" value="{{ old('vision_left') }}" step="0.01" min="0" max="3" placeholder="1.0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Visus Mata Kanan</label>
                                    <input type="number" name="vision_right" class="form-control" value="{{ old('vision_right') }}" step="0.01" min="0" max="3" placeholder="1.0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Pendengaran</label>
                                    <select name="hearing_status" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        <option value="normal" {{ old('hearing_status')=='normal'?'selected':'' }}>Normal</option>
                                        <option value="kurang" {{ old('hearing_status')=='kurang'?'selected':'' }}>Kurang</option>
                                        <option value="tidak_ada" {{ old('hearing_status')=='tidak_ada'?'selected':'' }}>Tidak Ada</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Gigi</label>
                                    <select name="dental_status" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        <option value="normal" {{ old('dental_status')=='normal'?'selected':'' }}>Normal</option>
                                        <option value="karies" {{ old('dental_status')=='karies'?'selected':'' }}>Karies</option>
                                        <option value="gangguan" {{ old('dental_status')=='gangguan'?'selected':'' }}>Gangguan</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Skrining TB</label>
                                    <select name="tb_screening_result" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        <option value="negatif" {{ old('tb_screening_result')=='negatif'?'selected':'' }}>Negatif</option>
                                        <option value="akur" {{ old('tb_screening_result')=='akur'?'selected':'' }}>akur</option>
                                        <option value="laten" {{ old('tb_screening_result')=='laten'?'selected':'' }}>Laten</option>
                                        <option value="aktif_suspect" {{ old('tb_screening_result')=='aktif_suspect'?'selected':'' }}>Aktif Suspect</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-check mt-4 pt-3">
                                        <input type="checkbox" name="is_school_entry" class="form-check-input" id="is_school_entry" value="1" {{ old('is_school_entry')?'checked':'' }}>
                                        <label class="form-check-label" for="is_school_entry">Pemeriksaan Masuk Sekolah Baru</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan TB</label>
                            <textarea name="tb_notes" class="form-control" rows="2">{{ old('tb_notes') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                            <a href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}" class="btn btn-secondary"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection