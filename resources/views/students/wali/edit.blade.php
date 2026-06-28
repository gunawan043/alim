@extends('layouts.master')
@section('title') Data Wali / Orang Tua — {{ $student->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('li_3') <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]) }}">{{ $student->name }}</a> @endslot
        @slot('title') Data Wali / Orang Tua @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.students.wali.update', ['userId' => $userId, 'santriUuid' => $student->id]) }}">
        @csrf
        @method('PUT')

        @php
            $educationLevels = ['SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'];
            $currentYear = date('Y') - 10;
        @endphp

        {{-- AYAH --}}
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="ri-user-3-line me-1"></i> Data Ayah</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nama Ayah</label>
                        <input type="text" name="father_name" class="form-control"
                               value="{{ old('father_name', $student->father_name) }}" maxlength="255">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tahun Lahir</label>
                        <input type="number" name="father_birth_year" class="form-control"
                               value="{{ old('father_birth_year', $student->father_birth_year) }}"
                               min="1900" max="{{ $currentYear }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pendidikan</label>
                        <select name="father_education" class="form-control">
                            <option value="">—</option>
                            @foreach($educationLevels as $ed)
                                <option value="{{ $ed }}" {{ old('father_education', $student->father_education) === $ed ? 'selected' : '' }}>{{ $ed }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pekerjaan</label>
                        <input type="text" name="father_occupation" class="form-control"
                               value="{{ old('father_occupation', $student->father_occupation) }}" maxlength="100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">NIK Ayah</label>
                        <input type="text" name="father_nik" class="form-control"
                               value="{{ old('father_nik', $student->father_nik) }}" maxlength="30">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Penghasilan/Bulan (Rp)</label>
                        <input type="number" name="father_income" class="form-control"
                               value="{{ old('father_income', $student->father_income) }}" step="1000" min="0">
                    </div>
                </div>
            </div>
        </div>

        {{-- IBU --}}
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="ri-user-2-line me-1"></i> Data Ibu</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nama Ibu</label>
                        <input type="text" name="mother_name" class="form-control"
                               value="{{ old('mother_name', $student->mother_name) }}" maxlength="255">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tahun Lahir</label>
                        <input type="number" name="mother_birth_year" class="form-control"
                               value="{{ old('mother_birth_year', $student->mother_birth_year) }}"
                               min="1900" max="{{ $currentYear }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pendidikan</label>
                        <select name="mother_education" class="form-control">
                            <option value="">—</option>
                            @foreach($educationLevels as $ed)
                                <option value="{{ $ed }}" {{ old('mother_education', $student->mother_education) === $ed ? 'selected' : '' }}>{{ $ed }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pekerjaan</label>
                        <input type="text" name="mother_occupation" class="form-control"
                               value="{{ old('mother_occupation', $student->mother_occupation) }}" maxlength="100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">NIK Ibu</label>
                        <input type="text" name="mother_nik" class="form-control"
                               value="{{ old('mother_nik', $student->mother_nik) }}" maxlength="30">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Penghasilan/Bulan (Rp)</label>
                        <input type="number" name="mother_income" class="form-control"
                               value="{{ old('mother_income', $student->mother_income) }}" step="1000" min="0">
                    </div>
                </div>
            </div>
        </div>

        {{-- WALI (opsional) --}}
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="ri-shield-user-line me-1"></i> Data Wali <small class="text-muted">(opsional, jika bukan ayah/ibu)</small></h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nama Wali</label>
                        <input type="text" name="guardian_name" class="form-control"
                               value="{{ old('guardian_name', $student->guardian_name) }}" maxlength="255">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tahun Lahir</label>
                        <input type="number" name="guardian_birth_year" class="form-control"
                               value="{{ old('guardian_birth_year', $student->guardian_birth_year) }}"
                               min="1900" max="{{ $currentYear }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pendidikan</label>
                        <select name="guardian_education" class="form-control">
                            <option value="">—</option>
                            @foreach($educationLevels as $ed)
                                <option value="{{ $ed }}" {{ old('guardian_education', $student->guardian_education) === $ed ? 'selected' : '' }}>{{ $ed }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pekerjaan</label>
                        <input type="text" name="guardian_occupation" class="form-control"
                               value="{{ old('guardian_occupation', $student->guardian_occupation) }}" maxlength="100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">NIK Wali</label>
                        <input type="text" name="guardian_nik" class="form-control"
                               value="{{ old('guardian_nik', $student->guardian_nik) }}" maxlength="30">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Penghasilan/Bulan (Rp)</label>
                        <input type="number" name="guardian_income" class="form-control"
                               value="{{ old('guardian_income', $student->guardian_income) }}" step="1000" min="0">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mb-4">
            <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
               class="btn btn-light">
                <i class="ri-arrow-left-line me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="ri-save-line me-1"></i> Simpan Data Wali
            </button>
        </div>
    </form>
@endsection
