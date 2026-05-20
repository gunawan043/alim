@extends('layouts.master')
@section('title') Edit Jadwal Pemeliharaan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pemeliharaan.schedule.index') }}">Jadwal</a> @endslot
    @slot('li_3') <a href="{{ route('sarpras.pemeliharaan.schedule.show', ['id' => $schedule->id]) }}">Detail</a> @endslot
    @slot('title') Edit @endslot
@endcomponent

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0 ps-3"><li>{{ $errors->first() }}</li></ul>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Edit Jadwal Pemeliharaan</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.pemeliharaan.schedule.update', ['id' => $schedule->id]) }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        {{-- Tipe target (readonly, tidak bisa diubah) --}}
                        <div class="col-md-12">
                            <label class="form-label">Target</label>
                            <div class="p-2 border rounded bg-light">
                                @if($schedule->asset)
                                    <span class="badge bg-primary-subtle text-primary me-1">Aset</span>
                                    <a href="{{ route('sarpras.aset.show', ['id' => $schedule->asset_id]) }}">{{ $schedule->asset->asset_name }}</a>
                                @elseif($schedule->room)
                                    <span class="badge bg-info-subtle text-info me-1">Ruang</span>
                                    {{ $schedule->room->room_name }}
                                @elseif($schedule->building)
                                    <span class="badge bg-secondary-subtle text-secondary me-1">Gedung</span>
                                    {{ $schedule->building->building_name }}
                                @endif
                            </div>
                            <input type="hidden" name="asset_id" value="{{ $schedule->asset_id }}">
                            <input type="hidden" name="room_id" value="{{ $schedule->room_id }}">
                            <input type="hidden" name="building_id" value="{{ $schedule->building_id }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Jenis Pemeliharaan <span class="text-danger">*</span></label>
                            <input type="text" name="maintenance_type" class="form-control"
                                value="{{ old('maintenance_type', $schedule->maintenance_type) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Frekuensi <span class="text-danger">*</span></label>
                            <select name="frequency" class="form-select" required>
                                @foreach(App\Models\AssetMaintenanceSchedule::FREQUENCY_OPTIONS as $f)
                                    <option value="{{ $f }}" {{ old('frequency', $schedule->frequency) == $f ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_',' ', $f)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jadwal Berikutnya <span class="text-danger">*</span></label>
                            <input type="date" name="next_maintenance_date" class="form-control"
                                value="{{ old('next_maintenance_date', $schedule->next_maintenance_date?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Penanggung Jawab</label>
                            <select name="responsible_user_id" class="form-select">
                                <option value="">— Pilih —</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ old('responsible_user_id', $schedule->responsible_user_id) == $u->id ? 'selected' : '' }}>
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vendor / Penyedia Jasa</label>
                            <input type="text" name="vendor_name" class="form-control"
                                value="{{ old('vendor_name', $schedule->vendor_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estimasi Biaya (Rp)</label>
                            <input type="number" name="estimated_cost" class="form-control"
                                value="{{ old('estimated_cost', $schedule->estimated_cost) }}" min="0" step="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pengingat</label>
                            <select name="reminder_days_before" class="form-select">
                                <option value="">Tidak ada</option>
                                @foreach([3, 7, 14, 30] as $d)
                                    <option value="{{ $d }}" {{ old('reminder_days_before', $schedule->reminder_days_before) == $d ? 'selected' : '' }}>
                                        {{ $d }} hari sebelumnya
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActiveSwitch"
                                    value="1" {{ old('is_active', $schedule->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActiveSwitch">Jadwal Aktif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $schedule->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="hstack gap-2 mt-4">
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan Perubahan</button>
                        <a href="{{ route('sarpras.pemeliharaan.schedule.show', ['id' => $schedule->id]) }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection