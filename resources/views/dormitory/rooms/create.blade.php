@extends('layouts.master')
@section('title') Tambah Kamar @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Kamar</a> @endslot
        @slot('title') Tambah Kamar @endslot
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
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.asrama.rooms.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Form Kamar Asrama</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kode Kamar <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" required placeholder="A-101" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Kamar</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Kamar Reguler 101" maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gedung</label>
                                <select name="wing_id" id="wing_id" class="form-control">
                                    <option value="">— Pilih Gedung —</option>
                                    @foreach($wings as $w)
                                        <option value="{{ $w->id }}" {{ old('wing_id') == $w->id || request('wing_id') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Lantai</label>
                                <input type="number" name="floor" class="form-control" value="{{ old('floor') }}" min="0" placeholder="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipe Kamar</label>
                                <select name="room_type" class="form-control">
                                    <option value="">— Pilih Tipe —</option>
                                    <option value="reguler" {{ old('room_type') === 'reguler' ? 'selected' : '' }}>Reguler</option>
                                    <option value="khusus" {{ old('room_type') === 'khusus' ? 'selected' : '' }}>Khusus</option>
                                    <option value="isolasi" {{ old('room_type') === 'isolasi' ? 'selected' : '' }}>Isolasi</option>
                                    <option value="musyrif" {{ old('room_type') === 'musyrif' ? 'selected' : '' }}>Musyrif</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kapasitas <span class="text-danger">*</span></label>
                                <input type="number" name="capacity" class="form-control" value="{{ old('capacity', 4) }}" min="1" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Fasilitas</label>
                                <textarea name="facility_notes" class="form-control" rows="2" placeholder="AC, Kamar Mandi Dalam, dll">{{ old('facility_notes') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                    <label class="form-check-label">Kamar aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">Batal</a>
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
