@extends('layouts.master')
@section('title') Fasilitas Pengelolaan Sampah @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.waste.index') }}">Sampah DLH</a> @endslot
        @slot('title') Fasilitas Sampah
    @endcomponent

    <x-sarpras-stats :items="[
        ['label'=>'Total Fasilitas','value'=>$stats['total'],'tone'=>'primary','icon'=>'ri-building-3-line'],
        ['label'=>'Fasilitas Aktif','value'=>$stats['active'],'tone'=>'success','icon'=>'ri-checkbox-circle-line'],
        ['label'=>'TPS','value'=>$stats['tps'],'tone'=>'info','icon'=>'ri-archive-2-line'],
        ['label'=>'Perlu Perbaikan','value'=>$stats['attention'],'tone'=>'danger','icon'=>'ri-error-warning-line'],
    ]" />

    <x-sarpras-filter-card :action="route('sarpras.waste.facilities')" :reset="route('sarpras.waste.facilities')">
        <div class="col-md-3"><label class="form-label small fw-semibold">Periode Catatan</label><input type="month" name="month" class="form-control" value="{{ $month }}"></div>
    </x-sarpras-filter-card>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-recycle-line me-2 text-success"></i>TPS, Komposter &amp; Bank Sampah</h5><p class="text-muted small mb-0">Master fasilitas pengelolaan sampah beserta kapasitas, kondisi, dan PIC.</p></div>
            <div class="d-flex gap-2"><a href="{{ route('sarpras.waste.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Dasbor Sampah</a><button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#facilityModal"><i class="ri-add-line me-1"></i>Tambah Fasilitas</button></div>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>No</th><th>Nama Fasilitas</th><th>Jenis</th><th>Lokasi</th><th>PIC</th><th class="text-end">Kapasitas</th><th>Kondisi</th><th class="text-center">Catatan Bulan Ini</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($facilities as $i => $facility)
                @php $tone = ['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'danger','rusak_berat'=>'danger'][$facility->condition] ?? 'secondary'; @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><div class="fw-medium">{{ $facility->name }}</div>@if($facility->notes)<small class="text-muted">{{ \Illuminate\Support\Str::limit($facility->notes, 45) }}</small>@endif</td>
                    <td><span class="badge bg-info-subtle text-info">{{ \App\Models\WasteFacility::TYPE_OPTIONS[$facility->facility_type] ?? ucfirst($facility->facility_type) }}</span></td>
                    <td class="small">{{ $facility->location ?? '-' }}@if($facility->building)<div class="text-muted">{{ $facility->building->building_name }}</div>@endif</td>
                    <td class="small">{{ $facility->pic?->name ?? '-' }}</td>
                    <td class="text-end small">{{ $facility->capacity_kg ? number_format((float) $facility->capacity_kg,0,',','.').' kg' : '-' }}@if($facility->capacity_m3)<div class="text-muted">{{ number_format((float) $facility->capacity_m3,1,',','.') }} m³</div>@endif</td>
                    <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst(str_replace('_',' ',$facility->condition)) }}</span></td>
                    <td class="text-center small">{{ $facility->month_logs }} catatan<div class="text-muted">{{ number_format((float) ($facility->month_weight ?? 0),2,',','.') }} kg</div></td>
                    <td><span class="badge bg-{{ $facility->is_active ? 'success' : 'secondary' }}-subtle text-{{ $facility->is_active ? 'success' : 'secondary' }}">{{ $facility->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="text-end"><div class="d-inline-flex gap-1"><button class="btn btn-sm btn-outline-warning edit-facility" data-bs-toggle="modal" data-bs-target="#facilityModal" data-action="{{ route('sarpras.waste.facility.update', ['facilityId' => $facility->id]) }}" data-name="{{ $facility->name }}" data-type="{{ $facility->facility_type }}" data-building="{{ $facility->building_id }}" data-location="{{ $facility->location }}" data-capacity-kg="{{ $facility->capacity_kg }}" data-capacity-m3="{{ $facility->capacity_m3 }}" data-condition="{{ $facility->condition }}" data-pic="{{ $facility->pic_user_id }}" data-notes="{{ $facility->notes }}"><i class="ri-pencil-line"></i></button><form method="POST" action="{{ route('sarpras.waste.facility.toggle', ['facilityId' => $facility->id]) }}">@csrf<button class="btn btn-sm btn-outline-{{ $facility->is_active ? 'danger' : 'success' }}"><i class="ri-shut-down-line"></i></button></form></div></td>
                </tr>
            @empty
                <x-empty-state colspan="10" title="Belum ada fasilitas sampah" message="Tambahkan TPS, komposter, atau bank sampah untuk memulai." />
            @endforelse
        </tbody></table></div></div>
    </div>

    <div class="modal fade" id="facilityModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('sarpras.waste.facility.store') }}" id="facilityForm" class="modal-content">@csrf<div id="facilityMethod"></div><div class="modal-header"><h5 class="modal-title" id="facilityModalTitle">Tambah Fasilitas Sampah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nama Fasilitas <span class="text-danger">*</span></label><input name="name" id="facilityName" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="facility_type" id="facilityType" class="form-select" required>@foreach(\App\Models\WasteFacility::TYPE_OPTIONS as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Gedung</label><select name="building_id" id="facilityBuilding" class="form-select"><option value="">-</option>@foreach($buildings as $building)<option value="{{ $building->id }}">{{ $building->building_name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Lokasi / Keterangan Tempat</label><input name="location" id="facilityLocation" class="form-control" placeholder="Contoh: belakang dapur"></div>
        <div class="col-md-6"><label class="form-label">Kapasitas (kg)</label><input type="number" step="0.01" min="0" name="capacity_kg" id="facilityCapacityKg" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Kapasitas (m³)</label><input type="number" step="0.01" min="0" name="capacity_m3" id="facilityCapacityM3" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Kondisi <span class="text-danger">*</span></label><select name="condition" id="facilityCondition" class="form-select" required>@foreach(\App\Models\WasteFacility::CONDITION_OPTIONS as $condition)<option value="{{ $condition }}">{{ ucfirst(str_replace('_',' ',$condition)) }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">PIC</label><select name="pic_user_id" id="facilityPic" class="form-select"><option value="">-</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="facilityNotes" class="form-control" rows="2"></textarea></div>
    </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan Fasilitas</button></div></form></div></div>
@endsection

@push('script')
<script>
    (function () {
        const modal = document.getElementById('facilityModal');
        const form = document.getElementById('facilityForm');
        const method = document.getElementById('facilityMethod');
        const title = document.getElementById('facilityModalTitle');
        const fields = ['Name','Type','Building','Location','CapacityKg','CapacityM3','Condition','Pic','Notes'];
        const set = (id, value) => { document.getElementById('facility' + id).value = value || ''; };

        document.querySelectorAll('.edit-facility').forEach(function (button) {
            button.addEventListener('click', function () {
                title.textContent = 'Edit Fasilitas Sampah';
                form.action = button.dataset.action;
                method.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                set('Name', button.dataset.name);
                set('Type', button.dataset.type);
                set('Building', button.dataset.building);
                set('Location', button.dataset.location);
                set('CapacityKg', button.dataset.capacityKg);
                set('CapacityM3', button.dataset.capacityM3);
                set('Condition', button.dataset.condition);
                set('Pic', button.dataset.pic);
                set('Notes', button.dataset.notes);
            });
        });

        modal.addEventListener('hidden.bs.modal', function () {
            title.textContent = 'Tambah Fasilitas Sampah';
            form.action = '{{ route('sarpras.waste.facility.store') }}';
            method.innerHTML = '';
            fields.forEach(function (field) { set(field, ''); });
            set('Type', 'tps'); set('Condition', 'baik');
        });
    })();
</script>
@endpush
