@extends('layouts.master')
@section('title') Buat Laporan Kerusakan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
    @slot('li_2') <a href="{{ route('sarpras.user.kerusakan.index', ['userId' => $userId]) }}">Kerusakan</a> @endslot
    @slot('title') Buat Laporan @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="ri-error-warning-line text-danger me-2"></i>Buat Laporan Kerusakan</h5></div>
            <div class="card-body">
                <form action="{{ route('sarpras.user.kerusakan.store', ['userId' => $userId]) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Aset <span class="text-danger">*</span></label>
                            <select name="asset_id" class="form-select" required>
                                <option value="">-- Pilih Aset --</option>
                                @foreach($damagedAssets as $asset)
                                    <option value="{{ $asset->id }}">
                                        {{ $asset->asset_name }}
                                        @if($asset->room) — {{ $asset->room->room_name }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tingkat Kerusakan <span class="text-danger">*</span></label>
                            <select name="damage_level" class="form-select" required>
                                <option value="ringan">Ringan</option>
                                <option value="sedang">Sedang</option>
                                <option value="berat">Berat</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi Kerusakan <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="4" required placeholder="Jelaskan secara detail kerusakan yang terjadi...">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Informasi tambahan (opsional)">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-danger"><i class="ri-send-plane-line me-1"></i>Kirim Laporan</button>
                        <a href="{{ route('sarpras.user.kerusakan.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection