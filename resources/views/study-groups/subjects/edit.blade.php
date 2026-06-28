@extends('layouts.master')
@section('title') Edit Mata Pelajaran Rombel @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">Rombongan Belajar</a> @endslot
        @slot('li_3') <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id]) }}">{{ $studyGroup->full_name }}</a> @endslot
        @slot('title') Edit Mata Pelajaran @endslot
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

    <form method="POST" action="{{ route('user.study-groups.subjects.update', ['userId' => $userId, 'id' => $studyGroup->id, 'assignmentId' => $assignment->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ $assignment->subject->name ?? 'Mata Pelajaran' }}</h5>

                        <div class="mb-3">
                            <label class="form-label">Jam Pelajaran per Minggu</label>
                            <input type="number" name="hours_per_week" min="1" max="40"
                                   class="form-control @error('hours_per_week') is-invalid @enderror"
                                   value="{{ old('hours_per_week', $assignment->hours_per_week ?? 2) }}">
                            @error('hours_per_week')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                   id="isActive" {{ old('is_active', $assignment->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Aktif</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id]) }}"
                               class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
