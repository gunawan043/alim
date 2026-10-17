@extends('layouts.master')
@section('title') Edit Assignment — {{ $assignment->subject?->code ?? '?' }} @endsection

@section('content')
@php
    $userId = $userId ?? request()->route('userId') ?? auth()->id();
    $subject = $assignment->subject;
@endphp
@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('li_2')
        <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">Rombongan Belajar</a>
    @endslot
    @slot('li_3')
        <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id]) }}">
            {{ $studyGroup->full_name }}
        </a>
    @endslot
    @slot('li_4') Edit Assignment @endslot
    @slot('title') Edit Assignment @endslot
@endcomponent

<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('user.study-groups.subjects.show', ['userId' => $userId, 'id' => $studyGroup->id, 'assignmentId' => $assignment->id]) }}"
           class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Assignment</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('user.study-groups.subjects.update', ['userId' => $userId, 'id' => $studyGroup->id, 'assignmentId' => $assignment->id]) }}"
                      method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3 p-3 bg-light rounded">
                        <strong>{{ $subject?->code ?? '?' }} — {{ $subject?->name ?? 'Deleted' }}</strong>
                        @if($assignment->teacher)
                            <br><span class="text-muted">Guru saat ini: {{ $assignment->teacher->name }}</span>
                        @endif
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Guru Pengampu</label>
                            <select name="teacher_id" class="form-select">
                                <option value="">-- Belum ada guru --</option>
                                @foreach($studyGroup->school->users->where('employment.school_id', $studyGroup->school_id)->sortBy('name') ?? [] as $teacher)
                                    <option value="{{ $teacher->id }}"
                                        {{ old('teacher_id', $assignment->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">JP/Minggu</label>
                            <input type="number" name="weekly_hours" class="form-control"
                                   value="{{ old('weekly_hours', $assignment->weekly_hours) }}"
                                   min="0.5" max="40" step="0.5">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ old('is_active', $assignment->is_active) ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ !old('is_active', $assignment->is_active) ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Catatan opsional">{{ old('notes', $assignment->notes) }}</textarea>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Simpan
                        </button>
                        <a href="{{ route('user.study-groups.subjects.show', ['userId' => $userId, 'id' => $studyGroup->id, 'assignmentId' => $assignment->id]) }}"
                           class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
