@extends('layouts.master')
@section('title') Edit {{ $studyGroup->full_name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">Rombongan Belajar</a> @endslot
        @slot('title') Edit {{ $studyGroup->full_name }} @endslot
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
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.study-groups.update', ['userId' => $userId, 'id' => $studyGroup->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Edit Rombongan Belajar</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sekolah</label>
                                <input type="text" class="form-control" value="{{ $studyGroup->school->name ?? '' }}" readonly>
                                <input type="hidden" name="school_id" value="{{ $studyGroup->school_id }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-control" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('academic_year_id', $studyGroup->academic_year_id) == $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }} ({{ $ay->semester_text }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tingkat Kelas <span class="text-danger">*</span></label>
                                <select name="grade_level_id" class="form-control" required>
                                    @foreach($gradeLevels as $gl)
                                        <option value="{{ $gl->id }}" {{ old('grade_level_id', $studyGroup->grade_level_id) == $gl->id ? 'selected' : '' }}>{{ $gl->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Rombel <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $studyGroup->name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Rombel</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $studyGroup->code) }}" maxlength="20">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kapasitas</label>
                                <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $studyGroup->capacity) }}" min="1" max="200">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ruang Kelas</label>
                                <input type="text" name="room" class="form-control" value="{{ old('room', $studyGroup->room) }}" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Wali Kelas</label>
                                <select name="homeroom_teacher_id" class="form-control">
                                    <option value="">— Pilih Wali Kelas —</option>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}" {{ old('homeroom_teacher_id', $studyGroup->homeroom_teacher_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kurikulum</label>
                                <select name="curriculum_type" class="form-control">
                                    <option value="merdeka" {{ old('curriculum_type', $studyGroup->curriculum_type) === 'merdeka' ? 'selected' : '' }}>Merdeka</option>
                                    <option value="2013" {{ old('curriculum_type', $studyGroup->curriculum_type) === '2013' ? 'selected' : '' }}>2013</option>
                                    <option value="ktsp" {{ old('curriculum_type', $studyGroup->curriculum_type) === 'ktsp' ? 'selected' : '' }}>KTSP</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Shift</label>
                                <select name="shift" class="form-control">
                                    <option value="pagi" {{ old('shift', $studyGroup->shift) === 'pagi' ? 'selected' : '' }}>Pagi</option>
                                    <option value="siang" {{ old('shift', $studyGroup->shift) === 'siang' ? 'selected' : '' }}>Siang</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $studyGroup->notes) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $studyGroup->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label">Rombel aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
