@extends('layouts.master')
@section('title') Tambah Tingkat Kelas @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Tingkat Kelas @endslot
        @slot('title') Tambah Tingkat @endslot
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

    <form method="POST" action="{{ route('user.grade-levels.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Form Tingkat Kelas</h5></div>
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
                                <label class="form-label">Tingkat <span class="text-danger">*</span></label>
                                <input type="number" name="level" class="form-control" value="{{ old('level') }}" min="1" max="15" required placeholder="Contoh: 7">
                                <small class="text-muted">Angka tingkat (1–15)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Tingkat <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Contoh: Kelas 7">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="Contoh: VII" maxlength="20">
                                <small class="text-muted">Kode romawi opsional (VII, VIII, IX)</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                    <label class="form-check-label">Tingkat kelas aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
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
