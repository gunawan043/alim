@extends('layouts.master')
@section('title') Tambah Aset @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
    @slot('li_2') <a href="{{ route('sarpras.user.aset.index', ['userId' => $userId]) }}">Aset</a> @endslot
    @slot('title') Tambah Aset @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="ri-add-line text-primary me-2"></i>Tambah Aset Baru</h5></div>
            <div class="card-body">
                <form action="{{ route('sarpras.user.aset.store', ['userId' => $userId]) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Aset <span class="text-danger">*</span></label>
                            <input type="text" name="asset_name" class="form-control" required placeholder="Contoh: Meja Guru MDF 120x60">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select name="asset_category_id" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kode Aset</label>
                            <input type="text" name="asset_code" class="form-control" placeholder="Otomatis jika kosong">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kuantitas</label>
                            <input type="number" name="quantity" class="form-control" value="{{ old('quantity', 1) }}" min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Satuan</label>
                            <input type="text" name="unit" class="form-control" value="{{ old('unit', 'unit') }}" placeholder="unit / buah / set">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Masa Pakai (tahun)</label>
                            <input type="number" name="useful_life_years" class="form-control" value="{{ old('useful_life_years') }}" min="0" max="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Harga Penyusutan / Tahun (Rp)</label>
                            <input type="number" name="depreciation_per_year" class="form-control" value="{{ old('depreciation_per_year') }}" min="0" placeholder="Otomatis bila kosong">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Merk / Brand</label>
                            <input type="text" name="brand" class="form-control" placeholder="Contoh: Yamaha">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Model / Tipe</label>
                            <input type="text" name="model" class="form-control" placeholder="Contoh: P-45B">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ruang</label>
                            <select name="room_id" class="form-select">
                                <option value="">-- Tidak ada ruang --</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->room_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kondisi <span class="text-danger">*</span></label>
                            <select name="condition" class="form-select" required>
                                @foreach(\App\Models\Asset::CONDITION_OPTIONS as $c)
                                    <option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Perolehan</label>
                            <input type="date" name="acquisition_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Harga Perolehan (Rp)</label>
                            <input type="number" name="acquisition_price" class="form-control" min="0" placeholder="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3"><i class="ri-information-line me-1"></i>Aset yang dibuat satuan kerja akan berstatus <strong>menunggu verifikasi Unit Rumah Tangga</strong>.</div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                        <a href="{{ route('sarpras.user.aset.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection