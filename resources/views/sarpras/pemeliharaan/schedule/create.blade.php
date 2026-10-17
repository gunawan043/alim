@extends('layouts.master')
@section('title') Tambah Jadwal Pemeliharaan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pemeliharaan.schedule.index') }}">Jadwal</a> @endslot
    @slot('title') Tambah @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Jadwal Pemeliharaan Baru</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.pemeliharaan.schedule.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Target <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <select name="target_type" class="form-select" required>
                                        <option value="">Pilih Tipe</option>
                                        <option value="asset" {{ old('target_type')=='asset'?'selected':'' }}>Aset</option>
                                        <option value="room" {{ old('target_type')=='room'?'selected':'' }}>Ruang</option>
                                        <option value="building" {{ old('target_type')=='building'?'selected':'' }}>Gedung</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <select name="asset_id" class="form-select" id="assetSelect">
                                        <option value="">Pilih Aset</option>
                                        @foreach($assets as $a)
                                            <option value="{{ $a->id }}" {{ old('asset_id')==$a->id?'selected':'' }}>{{ $a->asset_name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="room_id" class="form-select" id="roomSelect" style="display:none">
                                        <option value="">Pilih Ruang</option>
                                        @foreach($rooms as $r)
                                            <option value="{{ $r->id }}" {{ old('room_id')==$r->id?'selected':'' }}>{{ $r->room_name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="building_id" class="form-select" id="buildingSelect" style="display:none">
                                        <option value="">Pilih Gedung</option>
                                        @foreach($buildings as $g)
                                            <option value="{{ $g->id }}" {{ old('building_id')==$g->id?'selected':'' }}>{{ $g->building_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Pemeliharaan <span class="text-danger">*</span></label>
                            <input type="text" name="maintenance_type" class="form-control" placeholder="Contoh: Servis AC, Kalibrasi Alat Lab" value="{{ old('maintenance_type') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Frekuensi <span class="text-danger">*</span></label>
                            <select name="frequency" class="form-select" required>
                                @foreach(App\Models\AssetMaintenanceSchedule::FREQUENCY_OPTIONS as $f)
                                    <option value="{{ $f }}" {{ old('frequency')==$f?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$f)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jadwal Berikutnya <span class="text-danger">*</span></label>
                            <input type="date" name="next_maintenance_date" class="form-control" value="{{ old('next_maintenance_date') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Penanggung Jawab</label>
                            <select name="responsible_user_id" class="form-select">
                                <option value="">Pilih</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ old('responsible_user_id')==$u->id?'selected':'' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vendor / Penyedia Jasa</label>
                            <input type="text" name="vendor_name" class="form-control" value="{{ old('vendor_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estimasi Biaya (Rp)</label>
                            <input type="number" name="estimated_cost" class="form-control" value="{{ old('estimated_cost') }}" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="hstack gap-2 mt-4">
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                        <a href="{{ route('sarpras.pemeliharaan.schedule.index') }}" class="btn btn-light">Batal</a>
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
