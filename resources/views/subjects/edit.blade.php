@extends('layouts.master')
@section('title') Edit {{ $subject->name }} @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}">Mata Pelajaran</a> @endslot
        @slot('title') Edit {{ $subject->name }} @endslot
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

    <form method="POST" action="{{ route('user.subjects.update', ['userId' => $userId, 'id' => $subject->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-0"><i class="ri-pencil-line text-primary me-1"></i>Edit Mata Pelajaran</h5>
                            <p class="text-muted mb-0 small">{{ $subject->school?->name ?? 'Institusi' }}</p>
                        </div>
                        <span class="badge {{ $subject->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                            {{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sekolah</label>
                                <input type="text" class="form-control" value="{{ $subject->school->name ?? '' }}" readonly>
                                <input type="hidden" name="school_id" value="{{ $subject->school_id }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Mapel <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $subject->code) }}" required maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $subject->name) }}" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    <option value="nasional" {{ old('category', $subject->category) == 'nasional' ? 'selected' : '' }}>Nasional</option>
                                    <option value="lokal" {{ old('category', $subject->category) == 'lokal' ? 'selected' : '' }}>Lokal</option>
                                    <option value="muatan_lokal" {{ old('category', $subject->category) == 'muatan_lokal' ? 'selected' : '' }}>Muatan Lokal</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jam Pelajaran / Minggu <span class="text-danger">*</span></label>
                                <input type="number" name="credit_hours" class="form-control" value="{{ old('credit_hours', $subject->credit_hours) }}" min="1" max="20" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="2" maxlength="500">{{ old('description', $subject->description) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $subject->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label">Mata pelajaran aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.subjects.show', ['userId' => $userId, 'id' => $subject->id]) }}" class="btn btn-light">Batal</a>
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
                            <a href="{{ route('user.subjects.show', ['userId' => $userId, 'id' => $subject->id]) }}" class="btn btn-light w-100 text-start">
                                <i class="ri-eye-line me-2"></i> Lihat Detail Mapel
                            </a>
                            <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}" class="btn btn-light w-100 text-start">
                                <i class="ri-list-check me-2"></i> Daftar Mata Pelajaran
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
