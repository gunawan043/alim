@extends('layouts.master')
@section('title') Edit Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') <a href="{{ route('user.sa.dormitories.index', ['userId' => $userId]) }}" class="text-muted">Manajemen Asrama</a> @endslot
        @slot('title') Edit Asrama @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Edit Asrama</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.sa.dormitories.update', ['userId' => $userId, 'id' => $dormitory->id]) }}" enctype="multipart/form-data">
                        @csrf @method('PUT')

                        <h6 class="mb-3">Informasi Dasar</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Nama Asrama <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $dormitory->name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Asrama</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $dormitory->code) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="putra" {{ $dormitory->gender == 'putra' ? 'selected' : '' }}>Putra</option>
                                    <option value="putri" {{ $dormitory->gender == 'putri' ? 'selected' : '' }}>Putri</option>
                                    <option value="campuran" {{ $dormitory->gender == 'campuran' ? 'selected' : '' }}>Campuran</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                                <select name="school_id" class="form-select" required>
                                    <option value="">Pilih sekolah...</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}" {{ $dormitory->school_id == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unit Kerja</label>
                                <select name="work_unit_id" class="form-select">
                                    <option value="">Pilih unit...</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}" {{ $dormitory->work_unit_id == $wu->id ? 'selected' : '' }}>{{ $wu->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pengawas Asrama</label>
                                <select name="head_id" class="form-select">
                                    <option value="">Tidak ditentukan</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $dormitory->head_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3">Informasi Kapasitas</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Total Kapasitas <span class="text-danger">*</span></label>
                                <input type="number" name="capacity" class="form-control" min="1" value="{{ old('capacity', $dormitory->capacity) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jumlah Kamar</label>
                                <input type="number" name="total_rooms" class="form-control" min="0" value="{{ old('total_rooms', $dormitory->total_rooms) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jumlah Sayap</label>
                                <input type="number" name="total_wings" class="form-control" min="0" value="{{ old('total_wings', $dormitory->total_wings) }}">
                            </div>
                        </div>

                        <h6 class="mb-3">Informasi Kontak</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address', $dormitory->address) }}</textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $dormitory->phone) }}">
                            </div>
                        </div>

                        <h6 class="mb-3">Lainnya</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Logo Asrama</label>
                                @if($dormitory->logo_path)
                                    <div class="mb-2"><img src="{{ asset('storage/'.$dormitory->logo_path) }}" alt="logo" width="100"></div>
                                @endif
                                <input type="file" name="logo_path" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $dormitory->notes) }}</textarea>
                            </div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" {{ $dormitory->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>

                        <div class="float-end gap-2">
                            <a href="{{ route('user.sa.dormitories.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
