@extends('layouts.master')
@section('title') Edit Poin Pelanggaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK & Peserta Didik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Peserta Didik</a> @endslot
        @slot('li_3') <a href="{{ route('user.violation-points.index', ['userId' => $userId]) }}">Poin Pelanggaran</a> @endslot
        @slot('title') Edit Poin Pelanggaran @endslot
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

    <form method="POST" action="{{ route('user.violation-points.update', ['userId' => $userId, 'violationUuid' => $violation->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Edit Poin Pelanggaran</h5>
                    </div>
                    <div class="card-body">

                        {{-- Siswa --}}
                        <div class="mb-3">
                            <label class="form-label">Peserta Didik <span class="text-danger">*</span></label>
                            <select name="student_id" class="form-control @error('student_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Peserta Didik --</option>
                                @foreach($students as $s)
                                    <option value="{{ $s->id }}" {{ old('student_id', $violation->student_id) == $s->id ? 'selected' : '' }}>
                                        {{ $s->name }} {{ $s->nisn ? "({$s->nisn})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Rombel --}}
                        <div class="mb-3">
                            <label class="form-label">Rombel <span class="text-danger">*</span></label>
                            <select name="study_group_id" class="form-control @error('study_group_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Rombel --</option>
                                @foreach($studyGroups as $sg)
                                    <option value="{{ $sg->id }}" {{ old('study_group_id', $violation->study_group_id) == $sg->id ? 'selected' : '' }}>
                                        {{ $sg->full_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('study_group_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tanggal & Jenis --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Pelanggaran <span class="text-danger">*</span></label>
                                    <input type="date" name="violation_date"
                                           class="form-control @error('violation_date') is-invalid @enderror"
                                           value="{{ old('violation_date', $violation->violation_date->format('Y-m-d')) }}" required>
                                    @error('violation_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Jenis Pelanggaran <span class="text-danger">*</span></label>
                                    <input type="text" name="violation_type"
                                           class="form-control @error('violation_type') is-invalid @enderror"
                                           value="{{ old('violation_type', $violation->violation_type) }}"
                                           maxlength="100" required>
                                    @error('violation_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Poin <span class="text-danger">*</span></label>
                                    <input type="number" name="points"
                                           class="form-control @error('points') is-invalid @enderror"
                                           value="{{ old('points', $violation->points) }}"
                                           min="0" max="100" required>
                                    @error('points')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Deskripsi --}}
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                      rows="3">{{ old('description', $violation->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tindakan --}}
                        <div class="mb-3">
                            <label class="form-label">Tindakan yang Diberikan</label>
                            <textarea name="action_taken" class="form-control @error('action_taken') is-invalid @enderror"
                                      rows="3">{{ old('action_taken', $violation->action_taken) }}</textarea>
                            @error('action_taken')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Update
                            </button>
                            <a href="{{ route('user.violation-points.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
