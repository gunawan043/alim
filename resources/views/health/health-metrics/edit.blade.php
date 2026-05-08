@extends('layouts.master')
@section('title') Edit Antropometri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}">Antropometri</a> @endslot
        @slot('title') Edit Data @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.uks.health-metrics.update', ['userId' => $userId, 'uuid' => $metric->id]) }}">
        @csrf @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light"><h5 class="mb-0">Edit Antropometri</h5></div>
                    <div class="card-body">
                        @component('components.student-select', [
                            'label' => 'Nama Santri',
                            'inputId' => 'studentFilter',
                            'selectId' => 'studentSelect',
                            'selectName' => 'student_id',
                            'groupedStudents' => $groupedStudents,
                            'selectedId' => $metric->student_id,
                            'errorName' => 'student_id',
                        ])@endcomponent

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Sesi Pengukuran</label>
                                    <select name="measurement_session" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        @foreach(['awal_tahun','tengah_tahun','akhir_tahun','lainnya'] as $s)
                                            <option value="{{ $s }}" {{ old('measurement_session', $metric->measurement_session) == $s ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('_',' ',$s)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Ukur <span class="text-danger">*</span></label>
                                    <input type="date" name="record_date" class="form-control @error('record_date') is-invalid @enderror" value="{{ old('record_date', $metric->record_date?->format('Y-m-d')) }}" required>
                                    @error('record_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Tinggi Badan (cm) <span class="text-danger">*</span></label>
                                    <input type="number" name="height_cm" class="form-control @error('height_cm') is-invalid @enderror" value="{{ old('height_cm', $metric->height_cm) }}" min="30" max="250" step="0.1" required>
                                    @error('height_cm')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Berat Badan (kg) <span class="text-danger">*</span></label>
                                    <input type="number" name="weight_kg" class="form-control @error('weight_kg') is-invalid @enderror" value="{{ old('weight_kg', $metric->weight_kg) }}" min="5" max="200" step="0.1" required>
                                    @error('weight_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">BMI</label>
                                    <div class="form-control bg-light">{{ $metric->bmi ? number_format($metric->bmi, 2) : '-' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Kategori BMI</label>
                                    <div class="form-control bg-light">{{ $metric->bmi_category_text }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Petugas Ukur</label>
                                    <select name="measured_by" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        @foreach($staff as $s)
                                            <option value="{{ $s->id }}" {{ old('measured_by', $metric->measured_by) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $metric->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                            <a href="{{ route('user.uks.health-metrics.show', ['userId' => $userId, 'uuid' => $metric->id]) }}" class="btn btn-secondary"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection