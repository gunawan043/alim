@extends('layouts.master')

@section('title', 'Buat Peraturan Asrama')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0"><i class="ri-add-circle-fill me-2 text-primary"></i>Buat Peraturan Asrama Baru</h4>
                <a href="{{ route('user.boarding-regulations.index') }}" class="btn btn-secondary btn-lg">
                    <i class="ri-arrow-left-circle-line me-1"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @route('user.boarding-regulations.store')
                        {{-- Name Field --}}
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nama Peraturan</label>
                            <input type="text" name="name" class="form-control" id="name" value="{{ old('name') }}" required>
                            @if($errors->has('name'))
                                <div class="text-danger small mt-1">{{ $errors->first('name') }}</div>
                            @endif
                        </div>

                        {{-- Description Field --}}
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Deskripsi</label>
                            <textarea name="description" class="form-control" id="description" rows="2">{{ old('description') }}</textarea>
                            @if($errors->has('description'))
                                <div class="text-danger small mt-1">{{ $errors->first('description') }}</div>
                            @endif
                        </div>

                        {{-- Category Field --}}
                        <div class="mb-3">
                            <label for="category_id" class="form-label fw-semibold">Kategori</label>
                            <select name="category_id" class="form-select" id="category_id" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('category_id'))
                                <div class="text-danger small mt-1">{{ $errors->first('category_id') }}</div>
                            @endif
                        </div>

                        {{-- Content Field --}}
                        <div class="mb-3">
                            <label for="content" class="form-label fw-semibold">Konten Peraturan</label>
                            <textarea name="content" class="form-control" id="content" rows="8" required>{{ old('content') }}</textarea>
                            @if($errors->has('content'))
                                <div class="text-danger small mt-1">{{ $errors->first('content') }}</div>
                            @endif
                        </div>

                        {{-- Status Field --}}
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                            <label for="is_active" class="form-check-label fw-semibold">Aktif</label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="ri-save-fill me-1"></i>Simpan Peraturan
                        </button>
                    @endroute
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
