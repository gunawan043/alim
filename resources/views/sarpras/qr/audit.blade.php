@extends('layouts.master')
@section('title') Audit Aset - {{ $asset->asset_name }} @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.qr.index') }}">QR Code</a> @endslot
    @slot('title') Audit Aset @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Audit Aset: {{ $asset->asset_name }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.qr.audit.submit', ['id' => $asset->id]) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Aset</label>
                            <input type="text" class="form-control" value="{{ $asset->asset_name }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kode</label>
                            <input type="text" class="form-control" value="{{ $asset->asset_code ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kondisi Saat Ini</label>
                            <input type="text" class="form-control" value="{{ ucfirst(str_replace('_',' ',$asset->condition)) }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Update Kondisi <span class="text-danger">*</span></label>
                            <select name="condition" class="form-select" required>
                                @foreach(App\Models\Asset::CONDITION_OPTIONS as $c)
                                    <option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Audit</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Catatan hasil audit..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Foto Audit (Multiple)</label>
                            <input type="file" name="photos[]" class="form-control" multiple accept="image/*">
                        </div>
                    </div>
                    <div class="hstack gap-2 mt-4">
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan Audit</button>
                        <a href="{{ route('sarpras.qr.scanner') }}" class="btn btn-light">Kembali ke Scanner</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        @if($asset->photos->isNotEmpty())
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Foto Aset</h5></div>
            <div class="card-body">
                @foreach($asset->photos as $photo)
                <img src="{{ asset('storage/'.$photo->photo_path) }}" class="img-fluid rounded mb-2" alt="{{ $photo->caption }}">
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
