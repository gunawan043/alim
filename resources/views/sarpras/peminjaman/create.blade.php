@extends('layouts.master')
@section('title') Request Peminjaman @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.peminjaman.index') }}">Peminjaman</a> @endslot
    @slot('title') Request Peminjaman @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Request Peminjaman Aset</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.peminjaman.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Aset <span class="text-danger">*</span></label>
                            <select name="asset_id" class="form-select" required>
                                <option value="">Pilih Aset</option>
                                @foreach($assets as $a)
                                    <option value="{{ $a->id }}" {{ old('asset_id') == $a->id ? 'selected' : '' }}>
                                        {{ $a->asset_name }} - {{ $a->room?->room_name ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pinjam <span class="text-danger">*</span></label>
                            <input type="date" name="loan_date" class="form-control" value="{{ old('loan_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rencana Tanggal Kembali <span class="text-danger">*</span></label>
                            <input type="date" name="expected_return_date" class="form-control" value="{{ old('expected_return_date') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tujuan Peminjaman <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="3" required>{{ old('purpose') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="hstack gap-2 mt-4">
                        <button type="submit" class="btn btn-success"><i class="ri-send-plane-line me-1"></i> Ajukan</button>
                        <a href="{{ route('sarpras.peminjaman.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
