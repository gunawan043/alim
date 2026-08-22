@extends('layouts.master')
@section('title') Ajukan Pengadaan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
    @slot('li_2') <a href="{{ route('sarpras.user.pengadaan.index', ['userId' => $userId]) }}">Pengadaan</a> @endslot
    @slot('title') Ajukan Pengadaan @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="ri-shopping-cart-line text-success me-2"></i>Ajukan Permintaan Pengadaan</h5></div>
            <div class="card-body">
                <form action="{{ route('sarpras.user.pengadaan.store', ['userId' => $userId]) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" class="form-control" required placeholder="Contoh: Meja Siswa 2 Unit" value="{{ old('item_name') }}">
                            @error('item_name') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" required min="1" placeholder="1" value="{{ old('quantity', 1) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <input type="text" name="unit" class="form-control" placeholder="unit, pcs, set" value="{{ old('unit') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estimasi Harga/Satuan (Rp)</label>
                            <input type="number" name="estimated_price" class="form-control" min="0" placeholder="0" value="{{ old('estimated_price') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Urgensi <span class="text-danger">*</span></label>
                            <select name="urgency" class="form-select" required>
                                <option value="biasa" {{ old('urgency')=='biasa'?'selected':'' }}>Biasa</option>
                                <option value="urgent" {{ old('urgency')=='urgent'?'selected':'' }}>Urgent</option>
                                <option value="kritis" {{ old('urgency')=='kritis'?'selected':'' }}>Kritis</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keperluan <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="3" required placeholder="Jelaskan keperluan pengadaan barang ini...">{{ old('purpose') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Informasi tambahan (opsional)">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success"><i class="ri-send-plane-line me-1"></i>Kirim Pengajuan</button>
                        <a href="{{ route('sarpras.user.pengadaan.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection