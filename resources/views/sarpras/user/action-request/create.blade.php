@extends('layouts.master')
@section('title') Permintaan Tindakan Aset @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
        @slot('li_2') <a href="{{ route('sarpras.user.kerusakan.index', ['userId' => $userId]) }}">Laporan Saya</a> @endslot
        @slot('title') Permintaan Tindakan
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('sarpras.user.action.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ri-tools-line me-2 text-primary"></i>Permintaan Tindakan Aset</h5><p class="text-muted small mb-0">Permintaan akan masuk ke antrean URT. Petugas URT akan melakukan verifikasi dan tindakan.</p></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Aset <span class="text-danger">*</span></label>
                                <select name="asset_id" class="form-select" required>
                                    <option value="">— Pilih Aset —</option>
                                    @foreach($assets as $asset)
                                        <option value="{{ $asset->id }}" {{ (string) old('asset_id', $selectedAssetId) === (string) $asset->id ? 'selected' : '' }}>
                                            {{ $asset->asset_name }} — {{ $asset->asset_code }}@if($asset->room) · {{ $asset->room->room_name }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Jenis Permintaan <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    @foreach([
                                        ['perbaikan','Perbaikan','ri-tools-line','danger','Aset rusak atau tidak berfungsi'],
                                        ['perawatan','Perawatan','ri-calendar-todo-line','info','Servis, pembersihan, atau perawatan rutin'],
                                        ['pengecekan','Pengecekan','ri-search-eye-line','success','Minta petugas memeriksa kondisi aset'],
                                    ] as [$value, $label, $icon, $tone, $desc])
                                        <div class="col-md-4">
                                            <input type="radio" class="btn-check" name="action_type" id="action_{{ $value }}" value="{{ $value }}" {{ old('action_type', $action) === $value ? 'checked' : '' }}>
                                            <label class="card h-100 border-{{ $tone }} action-choice" for="action_{{ $value }}">
                                                <div class="card-body text-center py-3">
                                                    <i class="{{ $icon }} fs-3 text-{{ $tone }}"></i>
                                                    <div class="fw-semibold mt-1">{{ $label }}</div>
                                                    <small class="text-muted d-block">{{ $desc }}</small>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Prioritas <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select" required>
                                    @foreach(['low'=>'Rendah','normal'=>'Normal','high'=>'Tinggi','urgent'=>'Mendesak'] as $value=>$label)
                                        <option value="{{ $value }}" {{ old('priority','normal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Uraian Permintaan <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="4" required placeholder="Jelaskan kondisi atau tindakan yang dibutuhkan…">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('sarpras.user.kerusakan.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                        <button class="btn btn-primary"><i class="ri-send-plane-line me-1"></i>Kirim Permintaan</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-primary">
                    <div class="card-header bg-primary-subtle"><h5 class="card-title mb-0 text-primary"><i class="ri-information-line me-1"></i>Alur</h5></div>
                    <div class="card-body small text-muted">
                        <ol class="ps-3 mb-0"><li class="mb-2">Permintaan masuk ke antrean URT.</li><li class="mb-2">URT memverifikasi dan menentukan tindakan.</li><li class="mb-2">Jika disetujui, petugas mencatat pekerjaan.</li><li>Riwayat tersimpan pada aset.</li></ol>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('css')
<style>.action-choice{cursor:pointer;transition:.2s}.btn-check:checked + .action-choice{border-width:2px;background:#f8fafc;box-shadow:0 4px 12px rgba(0,0,0,.08)}</style>
@endpush
