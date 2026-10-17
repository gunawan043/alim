@extends('layouts.master')
@section('title') Catat Perpindahan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.perpindahan.index') }}">Perpindahan</a> @endslot
    @slot('title') Catat @endslot
@endcomponent

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Catat Perpindahan Aset</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.perpindahan.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Aset <span class="text-danger">*</span></label>
                        <select name="asset_id" class="form-select" required>
                            <option value="">Pilih Aset</option>
                            @foreach($assets as $a)
                                <option value="{{ $a->id }}">{{ $a->asset_name }} - {{ $a->room?->room_name ?? 'Belum ditempatkan' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ruang Tujuan <span class="text-danger">*</span></label>
                        <select name="to_room_id" class="form-select" required>
                            <option value="">Pilih Ruang Tujuan</option>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}">{{ $r->room_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Perpindahan <span class="text-danger">*</span></label>
                        <input type="date" name="moved_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan</label>
                        <textarea name="reason" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="hstack gap-2">
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                        <a href="{{ route('sarpras.perpindahan.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
