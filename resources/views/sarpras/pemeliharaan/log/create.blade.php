@extends('layouts.master')
@section('title') Tambah Perawatan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pemeliharaan.log.index') }}">Riwayat Perawatan</a> @endslot
    @slot('title') Tambah @endslot
@endcomponent

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
                                        <option value="asset">Aset</option>
                                        <option value="room">Ruang</option>
                                        <option value="building">Gedung</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <select name="asset_id" class="form-select" id="assetSelect">
                                        <option value="">Pilih Aset</option>
                                        @foreach($assets as $a)
                                            <option value="{{ $a->id }}">{{ $a->asset_name }}</option>
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
                            <input type="text" name="maintenance_type" class="form-control" value="{{ old('maintenance_type') }}" required>
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
