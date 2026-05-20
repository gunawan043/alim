@extends('layouts.master')
@section('title') Tambah Ruang @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
    @slot('li_2') <a href="{{ route('sarpras.user.ruang.index', ['userId' => $userId]) }}">Ruang</a> @endslot
    @slot('title') Tambah Ruang @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="ri-door-open-line text-primary me-2"></i>Tambah Ruang Baru</h5></div>
            <div class="card-body">
                <form action="{{ route('sarpras.user.ruang.store', ['userId' => $userId]) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Ruang <span class="text-danger">*</span></label>
                            <input type="text" name="room_name" class="form-control" required placeholder="Contoh: Ruang Kelas X-1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Ruang <span class="text-danger">*</span></label>
                            <select name="room_type" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                @foreach(\App\Models\AssetRoom::ROOM_TYPE_OPTIONS as $t)
                                    <option value="{{ $t }}">{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kode Ruang</label>
                            <input type="text" name="room_code" class="form-control" placeholder="Contoh: X-1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gedung</label>
                            <select name="building_id" class="form-select">
                                <option value="">-- Tidak ada gedung --</option>
                                @foreach($buildings as $b)
                                    <option value="{{ $b->id }}">{{ $b->building_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lantai</label>
                            <input type="number" name="floor" class="form-control" min="0" max="20" placeholder="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kapasitas (orang)</label>
                            <input type="number" name="capacity" class="form-control" min="0" placeholder="40">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Luas (m²)</label>
                            <input type="number" name="room_area" class="form-control" min="0" step="0.1" placeholder="48">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kondisi <span class="text-danger">*</span></label>
                            <select name="condition" class="form-select" required>
                                @foreach(\App\Models\AssetRoom::CONDITION_OPTIONS as $c)
                                    <option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                        <a href="{{ route('sarpras.user.ruang.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection