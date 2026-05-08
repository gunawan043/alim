@extends('layouts.master')
@section('title') Tambah Mata Pelajaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}">Mata Pelajaran</a> @endslot
        @slot('title') Tambah Mapel @endslot
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

    <form method="POST" action="{{ route('user.subjects.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Form Mata Pelajaran</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                                @if($schoolContext)
                                    <input type="text" class="form-control" value="{{ $schoolContext->name }}" readonly>
                                    <input type="hidden" name="school_id" value="{{ $schoolContext->id }}">
                                @else
                                    <select name="school_id" class="form-control" required>
                                        <option value="">— Pilih Sekolah —</option>
                                        @foreach($schools as $s)
                                            <option value="{{ $s->id }}" {{ old('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Mapel <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" required maxlength="20" placeholder="Contoh: MTK">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required maxlength="100" placeholder="Contoh: Matematika">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select name="category" class="form-control" required>
                                    <option value="">— Pilih Kategori —</option>
                                    <option value="nasional" {{ old('category') == 'nasional' ? 'selected' : '' }}>Nasional</option>
                                    <option value="lokal" {{ old('category') == 'lokal' ? 'selected' : '' }}>Lokal</option>
                                    <option value="muatan_lokal" {{ old('category') == 'muatan_lokal' ? 'selected' : '' }}>Muatan Lokal</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jam Pelajaran / Minggu <span class="text-danger">*</span></label>
                                <input type="number" name="credit_hours" class="form-control" value="{{ old('credit_hours', 2) }}" min="1" max="20" required placeholder="1–20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="2" maxlength="500" placeholder="Opsional">{{ old('description') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                    <label class="form-check-label">Mata pelajaran aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection