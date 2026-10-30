@extends('layouts.master')
@section('title') Log BBM @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.armada.index') }}">Armada</a> @endslot
        @slot('title') Log BBM
    @endcomponent

    <x-sarpras-stats :items="[
        ['label' => 'Pengisian Bulan Ini', 'value' => number_format($summary->fills ?? 0, 0, ',', '.'), 'tone' => 'primary', 'icon' => 'ri-gas-station-line'],
        ['label' => 'Total Liter', 'value' => number_format((float) ($summary->liters ?? 0), 1, ',', '.').' L', 'tone' => 'info', 'icon' => 'ri-oil-line'],
        ['label' => 'Total Biaya', 'value' => 'Rp '.number_format((float) ($summary->cost ?? 0), 0, ',', '.'), 'tone' => 'success', 'icon' => 'ri-money-rupee-circle-line'],
    ]" />

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-gas-station-line me-2 text-primary"></i>Log Pengisian BBM</h5>
                <p class="text-muted small mb-0">Riwayat pengisian bahan bakar per kendaraan.</p></div>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#fuelModal"><i class="ri-add-line me-1"></i>Catat Pengisian</button>
        </div>
        <div class="card-body py-2">
            <form method="GET" action="{{ route('sarpras.armada.fuel') }}" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small fw-semibold">Kendaraan</label><select name="vehicle_id" class="form-select"><option value="">Semua kendaraan</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected(request('vehicle_id') === $v->id)>{{ $v->name }} ({{ $v->plate_number }})</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Bulan</label><input type="month" name="month" class="form-control" value="{{ $month }}"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Tampilkan</button></div>
            </form>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th style="width:60px">No</th><th>Tanggal</th><th>Kendaraan</th><th>Jenis BBM</th><th class="text-end">Liter</th><th class="text-end">Biaya</th><th class="text-end">Odometer</th><th>No. Nota</th><th>Dicatat</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($logs as $i => $log)
                <tr>
                    <td>{{ $logs->firstItem() + $i }}</td>
                    <td class="small">{{ $log->fuel_date?->format('d/m/Y') }}</td>
                    <td><div class="fw-medium">{{ $log->vehicle?->name ?? '-' }}</div><small class="text-muted">{{ $log->vehicle?->plate_number }}</small></td>
                    <td><span class="badge bg-info-subtle text-info">{{ ucfirst($log->fuel_type) }}</span></td>
                    <td class="text-end">{{ number_format((float) $log->liters, 2, ',', '.') }} L</td>
                    <td class="text-end">Rp {{ number_format((float) $log->cost, 0, ',', '.') }}</td>
                    <td class="text-end small">{{ $log->odometer ? number_format($log->odometer, 0, ',', '.') : '-' }}</td>
                    <td class="small">{{ $log->receipt_no ?? '-' }}</td>
                    <td class="small">{{ $log->recorder?->name ?? '-' }}</td>
                    <td class="text-end"><form method="POST" action="{{ route('sarpras.armada.fuel.destroy', ['fuelId' => $log->id]) }}" onsubmit="return confirm('Hapus catatan BBM ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form></td>
                </tr>
            @empty
                <tr><td colspan="10"><x-empty-state title="Belum ada catatan BBM" message="Catat pengisian bahan bakar kendaraan." /></td></tr>
            @endforelse
            </tbody></table></div></div>
        @if($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif
    </div>

    <div class="modal fade" id="fuelModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('sarpras.armada.fuel.store') }}" class="modal-content">@csrf
            <div class="modal-header"><h5 class="modal-title">Catat Pengisian BBM</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-12"><label class="form-label">Kendaraan <span class="text-danger">*</span></label><select name="vehicle_id" class="form-select" required><option value="">Pilih kendaraan</option>@foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->name }} ({{ $v->plate_number }})</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="fuel_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                <div class="col-md-6"><label class="form-label">Jenis BBM <span class="text-danger">*</span></label><select name="fuel_type" class="form-select" required>@foreach(\App\Models\VehicleFuelLog::TYPE_OPTIONS as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Jumlah (liter) <span class="text-danger">*</span></label><input type="number" step="0.01" name="liters" class="form-control" min="0.01" required></div>
                <div class="col-md-4"><label class="form-label">Biaya (Rp)</label><input type="number" step="1" name="cost" class="form-control" min="0"></div>
                <div class="col-md-4"><label class="form-label">Odometer (km)</label><input type="number" name="odometer" class="form-control" min="0"></div>
                <div class="col-12"><label class="form-label">Nomor Nota</label><input name="receipt_no" class="form-control" maxlength="100"></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan</button></div>
        </form></div></div>
@endsection
