@extends('layouts.master')
@section('title') Edit {{ $gradeLevel->name }} @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">Data Kelas</a> @endslot
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
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-0"><i class="ri-pencil-line text-primary me-1"></i>Edit Tingkat Kelas</h5>
                            <p class="text-muted mb-0 small">{{ $gradeLevel->school?->name ?? 'Institusi' }}</p>
                        </div>
                        <span class="badge {{ $gradeLevel->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                            {{ $gradeLevel->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
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
                                <label class="form-label">Fase</label>
                                <input type="text" name="fase" class="form-control" value="{{ old('fase', $gradeLevel->fase) }}" maxlength="5" placeholder="Contoh: D">
                                <small class="text-muted">Fase capaian pembelajaran (A–F) — dipakai CP, TP, dan ATP.</small>
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
                            <a href="{{ route('user.grade-levels.show', ['userId' => $userId, 'id' => $gradeLevel->id]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-links-line text-primary me-1"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2">
                            <a href="{{ route('user.grade-levels.show', ['userId' => $userId, 'id' => $gradeLevel->id]) }}" class="btn btn-light w-100 text-start">
                                <i class="ri-eye-line me-2"></i> Lihat Detail Tingkat
                            </a>
                            <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}" class="btn btn-light w-100 text-start">
                                <i class="ri-list-check me-2"></i> Daftar Data Kelas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
