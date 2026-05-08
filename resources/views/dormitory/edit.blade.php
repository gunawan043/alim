@extends('layouts.master')
@section('title') Edit Asrama — {{ $dormitory->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
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
                                <label class="form-label">Kode Asrama <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ $dormitory->code }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Asrama <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ $dormitory->name }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-control">
                                    <option value="putra" {{ $dormitory->gender === 'putra' ? 'selected' : '' }}>Putra</option>
                                    <option value="putri" {{ $dormitory->gender === 'putri' ? 'selected' : '' }}>Putri</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                                <select name="school_id" class="form-control" required>
                                    <option value="">— Pilih Sekolah —</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}" {{ $dormitory->school_id == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kapasitas Total</label>
                                <input type="number" name="capacity" class="form-control" value="{{ $dormitory->capacity }}" min="1">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2">{{ $dormitory->address }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" class="form-control" value="{{ $dormitory->phone }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Kamar</label>
                                <input type="number" name="total_rooms" class="form-control" value="{{ $dormitory->total_rooms }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Gedung</label>
                                <input type="number" name="total_wings" class="form-control" value="{{ $dormitory->total_wings }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ $dormitory->notes }}</textarea>
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
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                        <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection