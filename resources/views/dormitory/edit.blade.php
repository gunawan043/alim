@extends('layouts.master')
@section('title') Edit Asrama — {{ $dormitory->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Edit @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('user.asrama.update', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" method="POST">
                @csrf @method('PUT')
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ri-edit-line me-1"></i> Edit Asrama</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="dormitory_code" class="form-label">Kode Asrama <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="dormitory_code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $dormitory->code) }}" required>
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="dormitory_name" class="form-label">Nama Asrama <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="dormitory_name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $dormitory->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="dormitory_gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                <select name="gender" id="dormitory_gender" class="form-control @error('gender') is-invalid @enderror">
                                    <option value="putra" {{ old('gender', $dormitory->gender) === 'putra' ? 'selected' : '' }}>Putra</option>
                                    <option value="putri" {{ old('gender', $dormitory->gender) === 'putri' ? 'selected' : '' }}>Putri</option>
                                </select>
                                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="dormitory_school" class="form-label">Sekolah <span class="text-danger">*</span></label>
                                <select name="school_id" id="dormitory_school" class="form-control @error('school_id') is-invalid @enderror" required>
                                    <option value="">— Pilih Sekolah —</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}" {{ old('school_id', $dormitory->school_id) == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                                    @endforeach
                                </select>
                                @error('school_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="dormitory_capacity" class="form-label">Kapasitas Total</label>
                                <input type="number" name="capacity" id="dormitory_capacity" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity', $dormitory->capacity) }}" min="1">
                                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="dormitory_address" class="form-label">Alamat</label>
                                <textarea name="address" id="dormitory_address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $dormitory->address) }}</textarea>
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="dormitory_phone" class="form-label">Telepon</label>
                                <input type="text" name="phone" id="dormitory_phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $dormitory->phone) }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="dormitory_total_rooms" class="form-label">Jumlah Kamar</label>
                                <input type="number" name="total_rooms" id="dormitory_total_rooms" class="form-control @error('total_rooms') is-invalid @enderror" value="{{ old('total_rooms', $dormitory->total_rooms) }}">
                                @error('total_rooms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="dormitory_total_wings" class="form-label">Jumlah Blok</label>
                                <input type="number" name="total_wings" id="dormitory_total_wings" class="form-control @error('total_wings') is-invalid @enderror" value="{{ old('total_wings', $dormitory->total_wings) }}">
                                @error('total_wings')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="dormitory_notes" class="form-label">Catatan</label>
                                <textarea name="notes" id="dormitory_notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $dormitory->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ $dormitory->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">Asrama Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                        <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection