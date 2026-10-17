@extends('layouts.master')
@section('title') Edit Gedung @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('li_2') <a href="{{ route('sarpras.gedung.index') }}">Sarana Prasarana</a> @endslot
        @slot('li_3') <a href="{{ route('sarpras.gedung.index') }}">Gedung</a> @endslot
        @slot('title') Edit: {{ $gedung->building_name }} @endslot
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

    <form method="POST" action="{{ route('sarpras.gedung.update', ['id' => $gedung->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Informasi Gedung</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Satuan Pendidikan <span class="text-danger">*</span></label>
                                <select name="school_id" id="school_id" class="form-control" required>
                                    <option value="">— Pilih Sekolah —</option>
                                    @foreach($schools as $s)
                                        <option value="{{ $s->id }}" {{ old('school_id', $gedung->school_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Gedung <span class="text-danger">*</span></label>
                                <input type="text" name="building_name" class="form-control" value="{{ old('building_name', $gedung->building_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Gedung</label>
                                <input type="text" name="building_code" class="form-control" value="{{ old('building_code', $gedung->building_code) }}" maxlength="30">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe Gedung <span class="text-danger">*</span></label>
                                <select name="building_type" class="form-control" required>
                                    <option value="">— Pilih Tipe —</option>
                                    @foreach(App\Models\AssetBuilding::BUILDING_TYPE_OPTIONS as $t)
                                        <option value="{{ $t }}" {{ old('building_type', $gedung->building_type) == $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jumlah Lantai</label>
                                <input type="number" name="total_floors" class="form-control" value="{{ old('total_floors', $gedung->total_floors) }}" min="1" max="20">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Luas (m²)</label>
                                <input type="number" name="building_area" class="form-control" value="{{ old('building_area', $gedung->building_area) }}" min="0" step="0.1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahun Dibangun</label>
                                <input type="number" name="build_year" class="form-control" value="{{ old('build_year', $gedung->build_year) }}" min="1900" max="2100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kondisi Struktur <span class="text-danger">*</span></label>
                                <select name="structure_condition" class="form-control" required>
                                    <option value="">— Pilih Kondisi —</option>
                                    @foreach(App\Models\AssetBuilding::CONDITION_OPTIONS as $c)
                                        <option value="{{ $c }}" {{ old('structure_condition', $gedung->structure_condition) == $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $c)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Kepemilikan</label>
                                <select name="ownership_status" class="form-control">
                                    <option value="">— Pilih —</option>
                                    @foreach(App\Models\AssetBuilding::OWNERSHIP_OPTIONS as $o)
                                        <option value="{{ $o }}" {{ old('ownership_status', $gedung->ownership_status) == $o ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $o)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor IMB</label>
                                <input type="text" name="imb_number" class="form-control" value="{{ old('imb_number', $gedung->imb_number) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $gedung->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Pengaturan</h5></div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $gedung->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label">Gedung aktif</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('sarpras.gedung.index') }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-success">
                <i class="ri-save-line me-1"></i> Simpan Perubahan
            </button>
        </div>
    </form>
@endsection