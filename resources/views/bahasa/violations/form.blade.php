@extends('layouts.master')

@section('title', 'Form Pelanggaran Bahasa')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') <a href="{{ route('user.bahasa.violations.index', ['userId' => $userId]) }}">Pelanggaran</a> @endslot
        @slot('title') {{ $violation->exists ? 'Edit Pelanggaran' : 'Catat Pelanggaran' }} @endslot
    @endcomponent

    <form method="POST" action="{{ $violation->exists ? route('user.bahasa.violations.update', ['userId' => $userId, 'violation' => $violation->id]) : route('user.bahasa.violations.store', ['userId' => $userId]) }}">
        @csrf
        @if($violation->exists) @method('PUT') @endif

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Data Pelanggaran</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Santri <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">— Pilih santri —</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected((string) old('student_id', $violation->student_id) === (string) $student->id)>{{ $student->name }} — {{ $student->currentClassHistory?->studyGroup?->name ?? 'Tanpa kelas' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="violation_date" class="form-control" value="{{ old('violation_date', $violation->violation_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Poin <span class="text-danger">*</span></label>
                        <input type="number" name="points" min="1" max="1000" class="form-control" value="{{ old('points', $violation->points ?: 1) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jenis Pelanggaran <span class="text-danger">*</span></label>
                        <input name="violation_type" class="form-control" value="{{ old('violation_type', $violation->violation_type) }}" placeholder="Contoh: berbicara dengan bahasa selain Arab" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tempat</label>
                        <input name="place" class="form-control" value="{{ old('place', $violation->place) }}" placeholder="Asrama / kelas / kantin">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kelas</label>
                        <input name="class_name" class="form-control" value="{{ old('class_name', $violation->class_name) }}" placeholder="Opsional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Asrama</label>
                        <select name="dormitory_id" class="form-select">
                            <option value="">— Pilih asrama —</option>
                            @foreach($dormitories ?? [] as $dormitory)
                                <option value="{{ $dormitory->id }}" @selected((string) old('dormitory_id', $violation->dormitory_id) === (string) $dormitory->id)>{{ $dormitory->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $violation->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $violation->notes) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('user.bahasa.violations.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                <button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan</button>
            </div>
        </div>
    </form>
@endsection
