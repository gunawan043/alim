@extends('layouts.master')
@section('title') Servis Rutin @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.armada.index') }}">Armada</a> @endslot
        @slot('title') Servis &amp; Perawatan
    @endcomponent

    <x-sarpras-stats :items="[
        ['label' => 'Servis Selesai', 'value' => $stats['selesai'], 'tone' => 'success', 'icon' => 'ri-checkbox-circle-line'],
        ['label' => 'Terjadwal', 'value' => $stats['terjadwal'], 'tone' => 'warning', 'icon' => 'ri-calendar-line'],
        ['label' => 'Biaya Bulan Ini', 'value' => 'Rp '.number_format($stats['biaya_bulan'], 0, ',', '.'), 'tone' => 'primary', 'icon' => 'ri-money-rupee-circle-line'],
    ]" />

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-tools-line me-2 text-primary"></i>Log Servis &amp; Perawatan</h5>
                <p class="text-muted small mb-0">Riwayat servis rutin, perbaikan, dan penggantian komponen.</p></div>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#serviceModal"><i class="ri-add-line me-1"></i>Catat Servis</button>
        </div>
        <div class="card-body py-2">
            <form method="GET" action="{{ route('sarpras.armada.service') }}" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small fw-semibold">Kendaraan</label><select name="vehicle_id" class="form-select"><option value="">Semua kendaraan</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected(request('vehicle_id') === $v->id)>{{ $v->name }} ({{ $v->plate_number }})</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua status</option>@foreach(\App\Models\VehicleServiceLog::STATUS_OPTIONS as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Filter</button></div>
            </form>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th style="width:60px">No</th><th>Tanggal</th><th>Kendaraan</th><th>Jenis Servis</th><th>Bengkel</th><th class="text-end">Biaya</th><th>Servis Berikutnya</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($services as $i => $service)
                @php $tone = ['terjadwal'=>'warning','berjalan'=>'info','selesai'=>'success'][$service->status] ?? 'secondary'; @endphp
                <tr>
                    <td>{{ $services->firstItem() + $i }}</td>
                    <td class="small">{{ $service->service_date?->format('d/m/Y') }}</td>
                    <td><div class="fw-medium">{{ $service->vehicle?->name ?? '-' }}</div><small class="text-muted">{{ $service->vehicle?->plate_number }}</small></td>
                    <td><span class="badge bg-info-subtle text-info">{{ ucfirst($service->service_type) }}</span></td>
                    <td class="small">{{ $service->vendor_name ?? '-' }}</td>
                    <td class="text-end">Rp {{ number_format((float) $service->cost, 0, ',', '.') }}</td>
                    <td class="small">{{ $service->next_service_date?->format('d/m/Y') ?? '-' }}</td>
                    <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst($service->status) }}</span></td>
                    <td class="text-end"><form method="POST" action="{{ route('sarpras.armada.service.destroy', ['serviceId' => $service->id]) }}" onsubmit="return confirm('Hapus catatan servis ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form></td>
                </tr>
            @empty
                <tr><td colspan="9"><x-empty-state title="Belum ada catatan servis" message="Catat servis rutin atau perbaikan kendaraan." /></td></tr>
            @endforelse
            </tbody></table></div></div>
        @if($services->hasPages())<div class="card-footer">{{ $services->links() }}</div>@endif
    </div>

    <div class="modal fade" id="serviceModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('sarpras.armada.service.store') }}" class="modal-content">@csrf
            <div class="modal-header"><h5 class="modal-title">Catat Servis Kendaraan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Kendaraan <span class="text-danger">*</span></label><select name="vehicle_id" class="form-select" required><option value="">Pilih kendaraan</option>@foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->name }} ({{ $v->plate_number }})</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Jenis Servis <span class="text-danger">*</span></label><select name="service_type" class="form-select" required>@foreach(\App\Models\VehicleServiceLog::TYPE_OPTIONS as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="service_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                <div class="col-md-3"><label class="form-label">Odometer (km)</label><input type="number" name="odometer" class="form-control" min="0"></div>
                <div class="col-md-4"><label class="form-label">Biaya (Rp)</label><input type="number" step="1" name="cost" class="form-control" min="0"></div>
                <div class="col-md-6"><label class="form-label">Status <span class="text-danger">*</span></label><select name="status" class="form-select" required>@foreach(\App\Models\VehicleServiceLog::STATUS_OPTIONS as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Bengkel / Vendor</label><input name="vendor_name" class="form-control" maxlength="191"></div>
                <div class="col-md-6"><label class="form-label">Komponen Diganti</label><textarea name="items_replaced" class="form-control" rows="2" placeholder="Contoh: oli mesin, filter udara"></textarea></div>
                <div class="col-md-6"><label class="form-label">Servis Berikutnya</label><input type="date" name="next_service_date" class="form-control"></div>
                <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan</button></div>
        </form></div></div>
@endsection
