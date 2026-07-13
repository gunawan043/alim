@extends('layouts.master')
@section('title') Edit {{ $wing->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Gedung</a> @endslot
        @slot('title') Edit {{ $wing->name }} @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.asrama.wings.update', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'wingUuid' => $wing->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ri-building-line me-1" aria-hidden="true"></i> Edit Gedung — {{ $wing->name }}</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_wing_code" class="form-label">Kode Gedung <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="edit_wing_code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $wing->code) }}" required maxlength="20">
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="edit_wing_name" class="form-label">Nama Gedung <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_wing_name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $wing->name) }}" required maxlength="100">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="edit_wing_floor" class="form-label">Lantai</label>
                                <input type="number" name="floor" id="edit_wing_floor" class="form-control @error('floor') is-invalid @enderror" value="{{ old('floor', $wing->floor) }}" min="0">
                                @error('floor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="edit_wing_capacity" class="form-label">Kapasitas</label>
                                <input type="number" name="capacity" id="edit_wing_capacity" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity', $wing->capacity) }}" min="0">
                                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="edit_wing_supervisor" class="form-label">Supervisor</label>
                                <select name="supervisor_id" id="edit_wing_supervisor" class="form-control @error('supervisor_id') is-invalid @enderror">
                                    <option value="">— Pilih Supervisor —</option>
                                    @foreach($supervisors as $s)
                                        <option value="{{ $s->id }}" {{ old('supervisor_id', $wing->supervisor_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                @error('supervisor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="edit_wing_notes" class="form-label">Catatan</label>
                                <textarea name="notes" id="edit_wing_notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $wing->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_wing_is_active" {{ old('is_active', $wing->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="edit_wing_is_active">Gedung aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light" aria-label="Batal dan kembali ke daftar gedung">
                                <i class="ri-close-line me-1" aria-hidden="true"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary" aria-label="Simpan perubahan gedung">
                                <i class="ri-save-line me-1" aria-hidden="true"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection