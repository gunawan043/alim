@extends('layouts.master')
@section('title') Buat Laporan Kerusakan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
        @slot('li_2') <a href="{{ route('sarpras.user.kerusakan.index', ['userId' => $userId]) }}">Kerusakan</a> @endslot
        @slot('title') Buat Laporan
    @endcomponent

    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <form action="{{ route('sarpras.user.kerusakan.store', ['userId' => $userId]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ri-error-warning-line me-2 text-danger"></i>Detail Kerusakan</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label">Aset <span class="text-danger">*</span></label><select name="asset_id" class="form-select" required><option value="">— Pilih Aset —</option>@foreach($damagedAssets as $asset)<option value="{{ $asset->id }}" {{ (string) old('asset_id', request('asset_id')) === (string) $asset->id ? 'selected' : '' }}>{{ $asset->asset_name }}@if($asset->room) — {{ $asset->room->room_name }}@endif</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Tingkat Kerusakan <span class="text-danger">*</span></label><select name="damage_level" class="form-select" required>@foreach(['ringan'=>'Ringan','sedang'=>'Sedang','berat'=>'Berat'] as $key=>$label)<option value="{{ $key }}" {{ old('damage_level') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Foto Bukti / Link Google Drive</label><input type="url" name="photo_urls[]" class="form-control mb-1" placeholder="https://drive.google.com/..."><input type="file" id="damage_photo" name="photos[]" class="form-control" accept="image/*" multiple><button type="button" class="btn btn-outline-danger btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="damage_photo" data-preview="damage_photo_preview"><i class="ri-camera-line me-1"></i>Kamera + watermark</button><div id="damage_photo_preview"></div></div>
                            <div class="col-12"><label class="form-label">Deskripsi Kerusakan <span class="text-danger">*</span></label><textarea name="description" class="form-control" rows="4" required placeholder="Jelaskan kerusakan secara detail…">{{ old('description') }}</textarea></div>
                            <div class="col-12"><label class="form-label">Catatan Tambahan</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2"><a href="{{ route('sarpras.user.kerusakan.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a><button class="btn btn-danger"><i class="ri-send-plane-line me-1"></i>Kirim Laporan</button></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-danger"><div class="card-header bg-danger-subtle"><h5 class="card-title mb-0 text-danger"><i class="ri-information-line me-1"></i>Alur</h5></div><div class="card-body small text-muted"><ol class="ps-3 mb-0"><li class="mb-2">Laporan masuk antrean URT.</li><li class="mb-2">URT memverifikasi dan menugaskan petugas.</li><li class="mb-2">Work order dijadwalkan.</li><li>Riwayat perbaikan masuk passport QR aset.</li></ol></div></div>
            </div>
        </div>
    </form>

    @include('sarpras.partials.camera-modal')
@endsection
