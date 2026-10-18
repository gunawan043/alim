@extends('layouts.master')
@section('title') Tambah Perawatan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pemeliharaan.log.index') }}">Riwayat Perawatan</a> @endslot
    @slot('title') Tambah @endslot
@endcomponent

@php
    $mode = $mode ?? request('mode');
    $selectedAssetId = $selectedAssetId ?? request('asset_id');
    $defaultType = $mode === 'repair' ? 'Perbaikan' : ($mode === 'maintenance' ? 'Perawatan' : '');
    $defaultLogType = $mode === 'repair' ? 'korektif' : ($mode === 'maintenance' ? 'preventif' : 'korektif');
@endphp

@if($mode === 'repair' || $mode === 'maintenance')
    <div class="alert alert-info d-flex align-items-center gap-2"><i class="ri-information-line fs-5"></i><span>Tindakan langsung petugas URT. Catatan akan masuk ke riwayat {{ $mode === 'repair' ? 'perbaikan' : 'perawatan' }} aset.</span></div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Catat Perawatan Baru</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.pemeliharaan.log.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Dari Jadwal? (Opsional)</label>
                            <select name="schedule_id" class="form-select">
                                <option value="">-- Tidak dari jadwal --</option>
                                @foreach($schedules as $sc)
                                    <option value="{{ $sc->id }}">{{ $sc->maintenance_type }} - {{ $sc->asset?->asset_name ?? $sc->room?->room_name ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Target <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <select name="target_type" class="form-select" required>
                                        <option value="">Tipe</option>
                                        <option value="asset" {{ old('target_type', $selectedAssetId ? 'asset' : '') === 'asset' ? 'selected' : '' }}>Aset</option>
                                        <option value="room" {{ old('target_type') === 'room' ? 'selected' : '' }}>Ruang</option>
                                        <option value="building" {{ old('target_type') === 'building' ? 'selected' : '' }}>Gedung</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <select name="asset_id" class="form-select" id="assetSelect">
                                        <option value="">Pilih Aset</option>
                                        @foreach($assets as $a)
                                            <option value="{{ $a->id }}" {{ (string) old('asset_id', $selectedAssetId) === (string) $a->id ? 'selected' : '' }}>{{ $a->asset_name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="room_id" class="form-select" id="roomSelect" style="display:none">
                                        <option value="">Pilih Ruang</option>
                                        @foreach($rooms as $r)
                                            <option value="{{ $r->id }}">{{ $r->room_name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="building_id" class="form-select" id="buildingSelect" style="display:none">
                                        <option value="">Pilih Gedung</option>
                                        @foreach($buildings as $g)
                                            <option value="{{ $g->id }}">{{ $g->building_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Perawatan <span class="text-danger">*</span></label>
                            <input type="text" name="maintenance_type" class="form-control" value="{{ old('maintenance_type', $defaultType) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Catatan</label>
                            <select name="log_type" class="form-select">
                                @foreach(['preventif'=>'Preventif / Perawatan','korektif'=>'Korektif / Perbaikan','inspeksi'=>'Inspeksi','penghapusan'=>'Penghapusan'] as $value=>$label)
                                    <option value="{{ $value }}" {{ old('log_type', $defaultLogType) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Perawatan <span class="text-danger">*</span></label>
                            <input type="date" name="maintenance_date" class="form-control" value="{{ old('maintenance_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Petugas</label>
                            <select name="performed_by" class="form-select">
                                <option value="">-</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vendor</label>
                            <input type="text" name="vendor_name" class="form-control" value="{{ old('vendor_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Biaya Aktual (Rp)</label>
                            <input type="number" name="actual_cost" class="form-control" min="0" value="{{ old('actual_cost') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kondisi Sebelum</label>
                            <select name="condition_before" class="form-select">
                                @foreach(App\Models\AssetMaintenanceLog::CONDITION_OPTIONS as $c)
                                    <option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kondisi Sesudah</label>
                            <select name="condition_after" class="form-select">
                                @foreach(App\Models\AssetMaintenanceLog::CONDITION_OPTIONS as $c)
                                    <option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi Pekerjaan</label>
                            <textarea name="work_description" class="form-control" rows="3">{{ old('work_description') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Suku Cadang / Perangkat yang Diganti</label>
                            <textarea name="parts_replaced" class="form-control" rows="2" placeholder="Contoh: kapasitor AC, filter, bearing…">{{ old('parts_replaced') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tindakan Berikutnya</label>
                            <textarea name="next_action_needed" class="form-control" rows="2" placeholder="Rekomendasi perawatan berikutnya…">{{ old('next_action_needed') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="hstack gap-2 mt-4">
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                        <a href="{{ route('sarpras.pemeliharaan.log.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@section('script')
<script>
document.querySelector('[name="target_type"]').addEventListener('change', function() {
    document.getElementById('assetSelect').style.display = this.value === 'asset' ? 'block' : 'none';
    document.getElementById('roomSelect').style.display = this.value === 'room' ? 'block' : 'none';
    document.getElementById('buildingSelect').style.display = this.value === 'building' ? 'block' : 'none';
});
</script>
@endsection
