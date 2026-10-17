@extends('layouts.master')
@section('title') Edit Aset @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
    @slot('li_2') <a href="{{ route('sarpras.user.aset.index', ['userId' => $userId]) }}">Aset</a> @endslot
    @slot('title') Edit Aset @endslot
@endcomponent

<?php $aset = $asetData; ?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="ri-pencil-line text-warning me-2"></i>Edit Aset</h5></div>
            <div class="card-body">
                <form action="{{ route('sarpras.user.aset.update', ['userId' => $userId, 'id' => $aset->id]) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Aset <span class="text-danger">*</span></label>
                            <input type="text" name="asset_name" class="form-control" required value="{{ $aset->asset_name }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select name="asset_category_id" class="form-select" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $aset->asset_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kode Aset</label>
                            <input type="text" name="asset_code" class="form-control" value="{{ $aset->asset_code }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Merk / Brand</label>
                            <input type="text" name="brand" class="form-control" value="{{ $aset->brand }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Model / Tipe</label>
                            <input type="text" name="model" class="form-control" value="{{ $aset->model }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ruang</label>
                            <select name="room_id" class="form-select">
                                <option value="">-- Tidak ada ruang --</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ $aset->room_id == $room->id ? 'selected' : '' }}>{{ $room->room_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kondisi <span class="text-danger">*</span></label>
                            <select name="condition" class="form-select" required>
                                @foreach(\App\Models\Asset::CONDITION_OPTIONS as $c)
                                    <option value="{{ $c }}" {{ $aset->condition == $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Perolehan</label>
                            <input type="date" name="acquisition_date" class="form-control" value="{{ $aset->acquisition_date?->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Harga Perolehan (Rp)</label>
                            <input type="number" name="acquisition_price" class="form-control" min="0" value="{{ $aset->acquisition_price }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $aset->notes }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-warning"><i class="ri-save-line me-1"></i>Perbarui</button>
                        <a href="{{ route('sarpras.user.aset.show', ['userId' => $userId, 'id' => $aset->id]) }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection