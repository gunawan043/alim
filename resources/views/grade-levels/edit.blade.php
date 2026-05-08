@extends('layouts.master')
@section('title') Edit {{ $gradeLevel->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">Tingkat Kelas</a> @endslot
        @slot('title') Edit {{ $gradeLevel->name }} @endslot
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

    <form method="POST" action="{{ route('user.grade-levels.update', ['userId' => $userId, 'id' => $gradeLevel->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Edit Tingkat Kelas</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sekolah</label>
                                <input type="text" class="form-control" value="{{ $gradeLevel->school->name ?? '' }}" readonly>
                                <input type="hidden" name="school_id" value="{{ $gradeLevel->school_id }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tingkat <span class="text-danger">*</span></label>
                                <input type="number" name="level" class="form-control" value="{{ old('level', $gradeLevel->level) }}" min="1" max="15" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Tingkat <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $gradeLevel->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $gradeLevel->code) }}" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $gradeLevel->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label">Tingkat kelas aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
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
