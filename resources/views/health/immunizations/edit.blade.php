@extends('layouts.master')
@section('title') Edit Imunisasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.immunizations.index', ['userId' => $userId]) }}">Imunisasi</a> @endslot
        @slot('title') Edit Imunisasi @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.uks.immunizations.update', ['userId' => $userId, 'uuid' => $immunization->id]) }}">
        @csrf @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light"><h5 class="mb-0">Edit Imunisasi</h5></div>
                    <div class="card-body">
                        @component('components.student-select', [
                            'label' => 'Nama Santri',
                            'inputId' => 'studentFilter',
                            'selectId' => 'studentSelect',
                            'selectName' => 'student_id',
                            'groupedStudents' => $groupedStudents,
                            'selectedId' => $immunization->student_id,
                            'errorName' => 'student_id',
                        ])@endcomponent

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Jenis Imunisasi <span class="text-danger">*</span></label>
                                    <select name="immunization_type" class="form-control @error('immunization_type') is-invalid @enderror" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach(['BCG','Polio_1','Polio_2','Polio_3','Polio_4','DPT_HB_Hib_1','DPT_HB_Hib_2','DPT_HB_Hib_3','Campak_MR','MR_2','Hepatitis_B','TT_1','TT_2','TT_3','TT_4','TT_5','Covid19','Influenza','Japanese_Encephalitis','lainnya'] as $type)
                                            <option value="{{ $type }}" {{ old('immunization_type', $immunization->immunization_type) == $type ? 'selected' : '' }}>
                                                {{ str_replace('_', ' ', $type) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('immunization_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Vaksin</label>
                                    <input type="text" name="vaccine_name" class="form-control" value="{{ old('vaccine_name', $immunization->vaccine_name) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Diberikan <span class="text-danger">*</span></label>
                                    <input type="date" name="date_given" class="form-control @error('date_given') is-invalid @enderror" value="{{ old('date_given', $immunization->date_given?->format('Y-m-d')) }}" required>
                                    @error('date_given')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Umur (hari)</label>
                                    <input type="number" name="age_at_vaccination_days" class="form-control" value="{{ old('age_at_vaccination_days', $immunization->age_at_vaccination_days) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tempat</label>
                                    <input type="text" name="place" class="form-control" value="{{ old('place', $immunization->place) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3"><label class="form-label">No. Batch</label>
                                    <input type="text" name="batch_number" class="form-control" value="{{ old('batch_number', $immunization->batch_number) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3"><label class="form-label">Petugas Medis</label>
                                    <input type="text" name="medical_staff" class="form-control" value="{{ old('medical_staff', $immunization->medical_staff) }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Efek Samping</label>
                            <textarea name="side_effects" class="form-control" rows="2">{{ old('side_effects', $immunization->side_effects) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $immunization->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                            <a href="{{ route('user.uks.immunizations.index', ['userId' => $userId]) }}" class="btn btn-secondary"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection