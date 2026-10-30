@extends('layouts.master')
@section('title') Daftar Kendaraan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Unit Rumah Tangga @endslot
        @slot('title') Armada &amp; Transportasi
    @endcomponent

    <x-sarpras-stats :items="[
        ['label' => 'Kendaraan Aktif', 'value' => $stats['total'], 'tone' => 'primary', 'icon' => 'ri-roadster-line'],
        ['label' => 'Tersedia', 'value' => $stats['tersedia'], 'tone' => 'success', 'icon' => 'ri-checkbox-circle-line'],
        ['label' => 'Perawatan', 'value' => $stats['perawatan'], 'tone' => 'warning', 'icon' => 'ri-tools-line'],
        ['label' => 'Servis Jatuh Tempo', 'value' => $stats['servis_terjadwal'], 'tone' => 'danger', 'icon' => 'ri-alarm-warning-line'],
    ]" />

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-roadster-line me-2 text-primary"></i>Daftar Kendaraan</h5>
                <p class="text-muted small mb-0">Kendaraan operasional sekolah beserta status, pengemudi, dan jadwal servis.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('sarpras.armada.trips') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-calendar-schedule-line me-1"></i>Perjalanan</a>
                <a href="{{ route('sarpras.armada.fuel') }}" class="btn btn-sm btn-outline-primary"><i class="ri-gas-station-line me-1"></i>Log BBM</a>
                <a href="{{ route('sarpras.armada.service') }}" class="btn btn-sm btn-outline-primary"><i class="ri-tools-line me-1"></i>Servis</a>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#vehicleModal"><i class="ri-add-line me-1"></i>Tambah Kendaraan</button>
            </div>
        </div>
        <div class="card-body py-2">
            <form method="GET" action="{{ route('sarpras.armada.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small fw-semibold">Cari</label><input name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama, plat, atau merek"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Jenis</label><select name="type" class="form-select"><option value="">Semua jenis</option>@foreach(\App\Models\Vehicle::TYPE_OPTIONS as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua status</option>@foreach(\App\Models\Vehicle::STATUS_OPTIONS as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Filter</button></div>
            </form>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th style="width:60px">No</th><th>Kendaraan</th><th>Jenis</th><th>Plat</th><th>Pengemudi</th><th class="text-end">Odometer</th><th>Servis Berikutnya</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($vehicles as $i => $vehicle)
                @php $tone = ['tersedia'=>'success','digunakan'=>'info','perawatan'=>'warning','nonaktif'=>'secondary'][$vehicle->status] ?? 'secondary'; @endphp
                <tr>
                    <td>{{ $vehicles->firstItem() + $i }}</td>
                    <td><div class="fw-medium">{{ $vehicle->name }}</div><small class="text-muted">{{ $vehicle->brand }} {{ $vehicle->model }}</small></td>
                    <td><span class="badge bg-info-subtle text-info">{{ ucfirst($vehicle->type) }}</span></td>
                    <td class="fw-medium">{{ $vehicle->plate_number }}</td>
                    <td class="small">{{ $vehicle->driver?->name ?? '-' }}</td>
                    <td class="text-end small">{{ $vehicle->odometer ? number_format($vehicle->odometer, 0, ',', '.').' km' : '-' }}</td>
                    <td class="small">@if($vehicle->next_service_date)<span class="badge bg-{{ $vehicle->next_service_date->isPast() ? 'danger' : 'success' }}-subtle text-{{ $vehicle->next_service_date->isPast() ? 'danger' : 'success' }}">{{ $vehicle->next_service_date->format('d/m/Y') }}</span>@else - @endif</td>
                    <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ $vehicle->statusLabel() }}</span>@unless($vehicle->is_active)<small class="text-muted d-block">Nonaktif</small>@endunless</td>
                    <td class="text-end"><div class="d-inline-flex gap-1">
                        <button class="btn btn-sm btn-outline-warning edit-vehicle" data-bs-toggle="modal" data-bs-target="#vehicleModal"
                            data-action="{{ route('sarpras.armada.update', ['vehicleId' => $vehicle->id]) }}"
                            data-name="{{ $vehicle->name }}" data-plate="{{ $vehicle->plate_number }}" data-brand="{{ $vehicle->brand }}"
                            data-model="{{ $vehicle->model }}" data-type="{{ $vehicle->type }}" data-capacity="{{ $vehicle->capacity }}"
                            data-status="{{ $vehicle->status }}" data-driver="{{ $vehicle->driver_id }}"
                            data-last-service="{{ $vehicle->last_service_date?->format('Y-m-d') }}" data-next-service="{{ $vehicle->next_service_date?->format('Y-m-d') }}"
                            data-odometer="{{ $vehicle->odometer }}" data-notes="{{ $vehicle->notes }}"><i class="ri-pencil-line"></i></button>
                        <form method="POST" action="{{ route('sarpras.armada.toggle', ['vehicleId' => $vehicle->id]) }}">@csrf<button class="btn btn-sm btn-outline-{{ $vehicle->is_active ? 'danger' : 'success' }}"><i class="ri-shut-down-line"></i></button></form>
                        <form method="POST" action="{{ route('sarpras.armada.destroy', ['vehicleId' => $vehicle->id]) }}" onsubmit="return confirm('Hapus kendaraan ini? Kendaraan dengan riwayat akan dinonaktifkan saja.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="9"><x-empty-state title="Belum ada kendaraan" message="Daftarkan kendaraan operasional sekolah (mobil, motor, bus, ambulance)." /></td></tr>
            @endforelse
            </tbody></table></div></div>
        @if($vehicles->hasPages())<div class="card-footer">{{ $vehicles->links() }}</div>@endif
    </div>

    <div class="modal fade" id="vehicleModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('sarpras.armada.store') }}" id="vehicleForm" class="modal-content">@csrf
            <div id="vehicleMethod"></div>
            <div class="modal-header"><h5 class="modal-title" id="vehicleModalTitle">Tambah Kendaraan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">Plat Nomor <span class="text-danger">*</span></label><input name="plate_number" id="vehiclePlate" class="form-control" maxlength="20" required></div>
                <div class="col-md-8"><label class="form-label">Nama Kendaraan <span class="text-danger">*</span></label><input name="name" id="vehicleName" class="form-control" maxlength="100" required placeholder="Contoh: Mobil Dinas Harian"></div>
                <div class="col-md-4"><label class="form-label">Merek</label><input name="brand" id="vehicleBrand" class="form-control" maxlength="60"></div>
                <div class="col-md-4"><label class="form-label">Model / Tipe</label><input name="model" id="vehicleModel" class="form-control" maxlength="60"></div>
                <div class="col-md-4"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="type" id="vehicleType" class="form-select" required>@foreach(\App\Models\Vehicle::TYPE_OPTIONS as $value)<option value="{{ $value }}">{{ ucfirst($value) }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Kapasitas (orang)</label><input type="number" name="capacity" id="vehicleCapacity" class="form-control" min="0"></div>
                <div class="col-md-4"><label class="form-label">Status <span class="text-danger">*</span></label><select name="status" id="vehicleStatus" class="form-select" required>@foreach(\App\Models\Vehicle::STATUS_OPTIONS as $value)<option value="{{ $value }}">{{ ucfirst($value) }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Odometer (km)</label><input type="number" name="odometer" id="vehicleOdometer" class="form-control" min="0"></div>
                <div class="col-md-6"><label class="form-label">Pengemudi</label><select name="driver_id" id="vehicleDriver" class="form-select"><option value="">-</option>@foreach($drivers as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Servis Terakhir</label><input type="date" name="last_service_date" id="vehicleLastService" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Servis Berikutnya</label><input type="date" name="next_service_date" id="vehicleNextService" class="form-control"></div>
                <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="vehicleNotes" class="form-control" rows="2"></textarea></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan Kendaraan</button></div>
        </form></div></div>
@endsection

@push('script')
<script>
    (function () {
        const modal = document.getElementById('vehicleModal');
        const form = document.getElementById('vehicleForm');
        const method = document.getElementById('vehicleMethod');
        const title = document.getElementById('vehicleModalTitle');
        const set = (id, v) => document.getElementById(id).value = v || '';

        document.querySelectorAll('.edit-vehicle').forEach(function (button) {
            button.addEventListener('click', function () {
                title.textContent = 'Edit Kendaraan';
                form.action = button.dataset.action;
                method.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                set('vehiclePlate', button.dataset.plate);
                set('vehicleName', button.dataset.name);
                set('vehicleBrand', button.dataset.brand);
                set('vehicleModel', button.dataset.model);
                set('vehicleType', button.dataset.type);
                set('vehicleCapacity', button.dataset.capacity);
                set('vehicleStatus', button.dataset.status);
                set('vehicleDriver', button.dataset.driver);
                set('vehicleLastService', button.dataset.lastService);
                set('vehicleNextService', button.dataset.nextService);
                set('vehicleOdometer', button.dataset.odometer);
                set('vehicleNotes', button.dataset.notes);
            });
        });

        modal.addEventListener('hidden.bs.modal', function () {
            title.textContent = 'Tambah Kendaraan';
            form.action = '{{ route('sarpras.armada.store') }}';
            method.innerHTML = '';
            ['vehiclePlate','vehicleName','vehicleBrand','vehicleModel','vehicleCapacity','vehicleOdometer','vehicleLastService','vehicleNextService','vehicleNotes','vehicleDriver'].forEach(id => set(id, ''));
            set('vehicleType', 'mobil'); set('vehicleStatus', 'tersedia');
        });
    })();
</script>
@endpush
