@extends('layouts.master')

@section('title', 'Form Mufradat')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') <a href="{{ route('user.bahasa.mufradat.index', ['userId' => $userId]) }}">Mufradat</a> @endslot
        @slot('title') {{ $period->exists ? 'Edit Periode' : 'Buat Periode' }} @endslot
    @endcomponent

    <form method="POST" action="{{ $period->exists ? route('user.bahasa.mufradat.update', ['userId' => $userId, 'period' => $period->id]) : route('user.bahasa.mufradat.store', ['userId' => $userId]) }}">
        @csrf
        @if($period->exists) @method('PUT') @endif

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Identitas Periode</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <input type="hidden" name="school_id" value="{{ old('school_id', $schoolId) }}">
                    <div class="col-md-6">
                        <label class="form-label">Nama Periode <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $period->title) }}" placeholder="Pekan 1 — Oktober 2026" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tingkat</label>
                        <select name="grade_level_id" class="form-select">
                            <option value="">Semua tingkat</option>
                            @foreach($gradeLevels ?? [] as $grade)
                                <option value="{{ $grade->id }}" @selected((string) old('grade_level_id', $period->grade_level_id) === (string) $grade->id)>{{ $grade->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kelas</label>
                        <select name="study_group_id" class="form-select">
                            <option value="">Semua kelas</option>
                            @foreach($studyGroups ?? [] as $group)
                                <option value="{{ $group->id }}" @selected((string) old('study_group_id', $period->study_group_id) === (string) $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="period_start" class="form-control" value="{{ old('period_start', $period->period_start?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="period_end" class="form-control" value="{{ old('period_end', $period->period_end?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mading Sekolah</label>
                        <input type="text" name="location_school" class="form-control" value="{{ old('location_school', $period->location_school) }}" placeholder="Mading utama / koridor">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mading Asrama</label>
                        <input type="text" name="location_dorm" class="form-control" value="{{ old('location_dorm', $period->location_dorm) }}" placeholder="Mading asrama putra/putri">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Minimal Kosakata</label>
                        <input type="number" name="min_words" min="1" max="100" class="form-control" value="{{ old('min_words', $period->min_words ?: 10) }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" @checked(old('is_active', $period->is_active ?? true))>
                            <label class="form-check-label" for="isActive">Aktif</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $period->notes) }}</textarea>
                    </div>
                </div>
                <div class="mt-3 lang-muted">
                    Tahun ajaran aktif: <strong>{{ $activeYear?->name ?? '-' }}</strong>. Periode baru otomatis memakai tahun ajaran ini.
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('user.bahasa.mufradat.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                <button class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
            </div>
        </div>
    </form>
@endsection
