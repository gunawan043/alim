{{--
    View standalone fallback untuk route permit-types.create / permit-types.edit.

    UI utama menggunakan modal inline (permit-type-modal) di halaman index.
    View ini sebagai fallback jika user membuka halaman create/edit langsung via URL.
--}}
@extends('layouts.master')

@section('title', $mode === 'create' ? 'Tambah Jenis Izin' : 'Ubah Jenis Izin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $mode === 'create' ? 'Tambah Jenis Izin' : 'Ubah Jenis Izin' }}</h5>
                    <a href="{{ route('user.asrama.leave-policies.index', ['userId' => auth()->id(), 'asramaUuid' => $dormitory->id]) }}" class="btn btn-secondary btn-sm">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong><i class="ri-error-warning-line me-1"></i> Ada kesalahan pada input:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ $mode === 'create' ? route('permit-types.store', [auth()->id(), $dormitory->id]) : route('permit-types.update', [$dormitory->id, $permitType->id]) }}">
                        @csrf
                        @if ($mode === 'edit')
                            <input type="hidden" name="_method" value="PUT">
                        @endif

                        <!-- Label -->
                        <div class="mb-3">
                            <label for="ptLabel" class="form-label fw-bold">Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control {{ isset($errors['label']) ? 'is-invalid' : '' }}" id="ptLabel" name="label" value="{{ old('label', $permitType->label ?? '') }}">
                            @if ($errors->has('label')) <div class="invalid-feedback">{{ $errors->first('label') }}</div> @endif
                        </div>

                        <!-- Code -->
                        <div class="mb-3">
                            <label for="ptCode" class="form-label fw-bold">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control {{ isset($errors['code']) ? 'is-invalid' : '' }}" id="ptCode" name="code" value="{{ old('code', $permitType->code ?? '') }}">
                            @if ($errors->has('code')) <div class="invalid-feedback">{{ $errors->first('code') }}</div> @endif
                            <small class="form-text text-muted">Automatically generated from label if left blank (must be unique).</small>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="ptDescription" class="form-label">Deskripsi</label>
                            <textarea class="form-control {{ isset($errors['description']) ? 'is-invalid' : '' }}" id="ptDescription" name="description" rows="3">{{ old('description', $permitType->description ?? '') }}</textarea>
                            @if ($errors->has('description')) <div class="invalid-feedback">{{ $errors->first('description') }}</div> @endif
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label for="ptCategory" class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select {{ isset($errors['category']) ? 'is-invalid' : '' }}" id="ptCategory" name="category">
                                <option value="" disabled>Select Category</option>
                                @foreach(['custom', 'fixed'] as $cat)
                                    <option value="{{ $cat }}" {{ old('category', $permitType->category ?? '') == $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('category')) <div class="invalid-feedback">{{ $errors->first('category') }}</div> @endif
                        </div>

                        <!-- Icon -->
                        <div class="mb-3">
                            <label for="ptIcon" class="form-label">Icon (ri-*)</label>
                            <input type="text" class="form-control {{ isset($errors['icon']) ? 'is-invalid' : '' }}" id="ptIcon" name="icon" value="{{ old('icon', $permitType->icon ?? 'ri-file-list-3-line') }}">
                            @if ($errors->has('icon')) <div class="invalid-feedback">{{ $errors->first('icon') }}</div> @endif
                        </div>

                        <!-- Color -->
                        <div class="mb-3">
                            <label for="ptColor" class="form-label">Warna</label>
                            <select class="form-select {{ isset($errors['color']) ? 'is-invalid' : '' }}" id="ptColor" name="color">
                                <option value="">Pilih Warna</option>
                                @foreach(['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'] as $color)
                                    <option value="{{ $color }}" {{ old('color', $permitType->color ?? 'primary') == $color ? 'selected' : '' }}>{{ ucfirst($color) }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('color')) <div class="invalid-feedback">{{ $errors->first('color') }}</div> @endif
                        </div>

                        <!-- Sort Order -->
                        <div class="mb-3">
                            <label for="ptSortOrder" class="form-label">Urutan (Sort Order)</label>
                            <input type="number" class="form-control {{ isset($errors['sort_order']) ? 'is-invalid' : '' }}" id="ptSortOrder" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $permitType->sort_order ?? 0) }}">
                            @if ($errors->has('sort_order')) <div class="invalid-feedback">{{ $errors->first('sort_order') }}</div> @endif
                        </div>

                        <!-- Is Active -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input {{ isset($errors['is_active']) ? 'is-invalid' : '' }}" id="ptIsActive" name="is_active" value="1" {{ old('is_active', $permitType->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="ptIsActive">Aktifkan Izin</label>
                            @if ($errors->has('is_active')) <div class="invalid-feedback">{{ $errors->first('is_active') }}</div> @endif
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                {{ $mode === 'create' ? 'Simpan Baru' : 'Perbarui' }}
                            </button>
                            <a href="{{ route('user.asrama.leave-policies.index', ['userId' => auth()->id(), 'asramaUuid' => $dormitory->id]) }}" class="btn btn-secondary">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
