@extends('layouts.master')
@section('title') Edit Ruang @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('li_2') <a href="{{ route('user.sarpras.gedung.index', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
        @slot('li_3') <a href="{{ route('user.sarpras.ruang.index', ['userId' => $userId]) }}">Ruang</a> @endslot
        @slot('title') Edit: {{ $ruang->room_name }} @endslot
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

    <form method="POST" action="{{ route('user.sarpras.ruang.update', ['userId' => $userId, 'id' => $ruang->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Informasi Ruang</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Satuan Pendidikan <span class="text-danger">*</span></label>
                                <select name="school_id" id="school_id" class="form-control" required>
                                    <option value="">— Pilih Sekolah —</option>
                                    @foreach($schools as $s)
                                        <option value="{{ $s->id }}" {{ old('school_id', $ruang->school_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Ruang <span class="text-danger">*</span></label>
                                <input type="text" name="room_name" class="form-control" value="{{ old('room_name', $ruang->room_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Ruang</label>
                                <input type="text" name="room_code" class="form-control" value="{{ old('room_code', $ruang->room_code) }}" maxlength="30">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe Ruang <span class="text-danger">*</span></label>
                                <select name="room_type" class="form-control" required>
                                    <option value="">— Pilih Tipe —</option>
                                    @foreach(App\Models\AssetRoom::ROOM_TYPE_OPTIONS as $t)
                                        <option value="{{ $t }}" {{ old('room_type', $ruang->room_type) == $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gedung</label>
                                <select name="building_id" id="building_id" class="form-control">
                                    <option value="">— Tanpa Gedung —</option>
                                    @foreach($gedungs as $g)
                                        <option value="{{ $g->id }}" {{ old('building_id', $ruang->building_id) == $g->id ? 'selected' : '' }}>{{ $g->building_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Lantai</label>
                                <input type="number" name="floor" class="form-control" value="{{ old('floor', $ruang->floor) }}" min="0" max="20">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kapasitas (org)</label>
                                <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $ruang->capacity) }}" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Luas (m²)</label>
                                <input type="number" name="room_area" class="form-control" value="{{ old('room_area', $ruang->room_area) }}" min="0" step="0.1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kondisi <span class="text-danger">*</span></label>
                                <select name="condition" class="form-control" required>
                                    <option value="">— Pilih Kondisi —</option>
                                    @foreach(App\Models\AssetRoom::CONDITION_OPTIONS as $c)
                                        <option value="{{ $c }}" {{ old('condition', $ruang->condition) == $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $c)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Fasilitas</label>
                                <textarea name="facilities" class="form-control" rows="2">{{ old('facilities', $ruang->facilities) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $ruang->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Pengaturan</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_bookable" value="1" {{ old('is_bookable', $ruang->is_bookable) ? 'checked' : '' }}>
                                <label class="form-check-label">Ruang dapat dipinjam</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="booking_requires_approval" value="1" {{ old('booking_requires_approval', $ruang->booking_requires_approval) ? 'checked' : '' }}>
                                <label class="form-check-label">Perlu persetujuan peminjaman</label>
                            </div>
                        </div>
                        <hr>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $ruang->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label">Ruang aktif</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('user.sarpras.ruang.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-success">
                <i class="ri-save-line me-1"></i> Simpan Perubahan
            </button>
        </div>
    </form>
@endsection
