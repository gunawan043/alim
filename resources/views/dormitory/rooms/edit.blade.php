@extends('layouts.master')
@section('title') Edit {{ $room->name ?? $room->code }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Kamar</a> @endslot
        @slot('title') Edit {{ $room->name ?? $room->code }} @endslot
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

    <form method="POST" action="{{ route('user.asrama.rooms.update', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $room->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Edit Kamar — {{ $room->name ?? $room->code }}</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="room_code" class="form-label">Kode Kamar <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="room_code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $room->code) }}" required maxlength="20">
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="room_name" class="form-label">Nama Kamar</label>
                                <input type="text" name="name" id="room_name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $room->name) }}" maxlength="100">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="room_wing" class="form-label">Gedung</label>
                                <select name="wing_id" id="room_wing" class="form-control @error('wing_id') is-invalid @enderror">
                                    <option value="">— Pilih Gedung —</option>
                                    @foreach($wings as $w)
                                        <option value="{{ $w->id }}" {{ old('wing_id', $room->wing_id) == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                                    @endforeach
                                </select>
                                @error('wing_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="room_floor" class="form-label">Lantai</label>
                                <input type="number" name="floor" id="room_floor" class="form-control @error('floor') is-invalid @enderror" value="{{ old('floor', $room->floor) }}" min="0">
                                @error('floor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="room_type_select" class="form-label">Tipe Kamar</label>
                                <select name="room_type" id="room_type_select" class="form-control @error('room_type') is-invalid @enderror">
                                    <option value="">— Pilih Tipe —</option>
                                    <option value="reguler" {{ old('room_type', $room->room_type) === 'reguler' ? 'selected' : '' }}>Reguler</option>
                                    <option value="khusus" {{ old('room_type', $room->room_type) === 'khusus' ? 'selected' : '' }}>Khusus</option>
                                    <option value="isolasi" {{ old('room_type', $room->room_type) === 'isolasi' ? 'selected' : '' }}>Isolasi</option>
                                    <option value="musyrif" {{ old('room_type', $room->room_type) === 'musyrif' ? 'selected' : '' }}>Musyrif</option>
                                </select>
                                @error('room_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="room_capacity" class="form-label">Kapasitas <span class="text-danger">*</span></label>
                                <input type="number" name="capacity" id="room_capacity" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity', $room->capacity) }}" min="1" required>
                                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="room_facility_notes" class="form-label">Fasilitas</label>
                                <textarea name="facility_notes" id="room_facility_notes" class="form-control @error('facility_notes') is-invalid @enderror" rows="2">{{ old('facility_notes', $room->facility_notes) }}</textarea>
                                @error('facility_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="room_is_active" {{ old('is_active', $room->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="room_is_active">Kamar aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
