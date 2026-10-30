@extends('layouts.master')
@section('title') Jadwal Perjalanan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.armada.index') }}">Armada</a> @endslot
        @slot('title') Jadwal Perjalanan
    @endcomponent

    <x-sarpras-stats :items="[
        ['label' => 'Dijadwalkan', 'value' => $stats['dijadwalkan'], 'tone' => 'warning', 'icon' => 'ri-calendar-line'],
        ['label' => 'Sedang Berjalan', 'value' => $stats['berjalan'], 'tone' => 'info', 'icon' => 'ri-roadster-line'],
        ['label' => 'Selesai Bulan Ini', 'value' => $stats['selesai_bulan'], 'tone' => 'success', 'icon' => 'ri-checkbox-circle-line'],
    ]" />

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-calendar-schedule-line me-2 text-primary"></i>Jadwal Perjalanan Kendaraan</h5>
                <p class="text-muted small mb-0">Rencana perjalanan, pengemudi, dan realisasi odometer.</p></div>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#tripModal"><i class="ri-add-line me-1"></i>Jadwalkan Perjalanan</button>
        </div>
        <div class="card-body py-2">
            <form method="GET" action="{{ route('sarpras.armada.trips') }}" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small fw-semibold">Kendaraan</label><select name="vehicle_id" class="form-select"><option value="">Semua kendaraan</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected(request('vehicle_id') === $v->id)>{{ $v->name }} ({{ $v->plate_number }})</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua status</option>@foreach(\App\Models\VehicleTrip::STATUS_OPTIONS as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Filter</button></div>
            </form>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th style="width:60px">No</th><th>Kendaraan</th><th>Tujuan</th><th>Pengemudi</th><th>Berangkat</th><th>Kembali</th><th class="text-center">Penumpang</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($trips as $i => $trip)
                @php $tone = ['dijadwalkan'=>'warning','berangkat'=>'info','selesai'=>'success','batal'=>'secondary'][$trip->status] ?? 'secondary'; @endphp
                <tr>
                    <td>{{ $trips->firstItem() + $i }}</td>
                    <td><div class="fw-medium">{{ $trip->vehicle?->name ?? '-' }}</div><small class="text-muted">{{ $trip->vehicle?->plate_number }}</small></td>
                    <td><div class="fw-medium">{{ $trip->destination }}</div>@if($trip->purpose)<small class="text-muted">{{ \Illuminate\Support\Str::limit($trip->purpose, 40) }}</small>@endif</td>
                    <td class="small">{{ $trip->driver?->name ?? '-' }}</td>
                    <td class="small">{{ $trip->departure_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="small">{{ $trip->return_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="text-center small">{{ $trip->passengers ?? 0 }}</td>
                    <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst($trip->status) }}</span></td>
                    <td class="text-end"><div class="d-inline-flex gap-1">
                        <button class="btn btn-sm btn-outline-warning edit-trip" data-bs-toggle="modal" data-bs-target="#tripStatusModal"
                            data-action="{{ route('sarpras.armada.trip.update', ['tripId' => $trip->id]) }}"
                            data-status="{{ $trip->status }}" data-start="{{ $trip->odometer_start }}" data-end="{{ $trip->odometer_end }}" data-notes="{{ $trip->notes }}"><i class="ri-pencil-line"></i></button>
                        <form method="POST" action="{{ route('sarpras.armada.trip.destroy', ['tripId' => $trip->id]) }}" onsubmit="return confirm('Hapus jadwal perjalanan ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="9"><x-empty-state title="Belum ada perjalanan" message="Jadwalkan perjalanan kendaraan untuk kegiatan sekolah." /></td></tr>
            @endforelse
            </tbody></table></div></div>
        @if($trips->hasPages())<div class="card-footer">{{ $trips->links() }}</div>@endif
    </div>

    <div class="modal fade" id="tripModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('sarpras.armada.trip.store') }}" class="modal-content">@csrf
            <div class="modal-header"><h5 class="modal-title">Jadwalkan Perjalanan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Kendaraan <span class="text-danger">*</span></label><select name="vehicle_id" class="form-select" required><option value="">Pilih kendaraan</option>@foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->name }} ({{ $v->plate_number }})</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Pengemudi</label><select name="driver_id" class="form-select"><option value="">-</option>@foreach($drivers as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                <div class="col-md-8"><label class="form-label">Tujuan <span class="text-danger">*</span></label><input name="destination" class="form-control" maxlength="191" required></div>
                <div class="col-md-4"><label class="form-label">Penumpang</label><input type="number" name="passengers" class="form-control" min="0" value="0"></div>
                <div class="col-md-6"><label class="form-label">Berangkat</label><input type="datetime-local" name="departure_at" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Perkiraan Kembali</label><input type="datetime-local" name="return_at" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Odometer Awal (km)</label><input type="number" name="odometer_start" class="form-control" min="0"></div>
                <div class="col-md-6"><label class="form-label">Keperluan</label><input name="purpose" class="form-control" maxlength="191"></div>
                <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan Jadwal</button></div>
        </form></div></div>

    <div class="modal fade" id="tripStatusModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="tripStatusForm" class="modal-content">@csrf
            <div id="tripStatusMethod"></div>
            <div class="modal-header"><h5 class="modal-title">Perbarui Status Perjalanan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Status <span class="text-danger">*</span></label><select name="status" id="tripStatusSelect" class="form-select" required>@foreach(\App\Models\VehicleTrip::STATUS_OPTIONS as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Odometer Awal</label><input type="number" name="odometer_start" id="tripOdoStart" class="form-control" min="0"></div>
                <div class="col-md-3"><label class="form-label">Odometer Akhir</label><input type="number" name="odometer_end" id="tripOdoEnd" class="form-control" min="0"></div>
                <div class="col-md-6"><label class="form-label">Waktu Kembali</label><input type="datetime-local" name="return_at" id="tripReturn" class="form-control"></div>
                <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="tripNotes" class="form-control" rows="2"></textarea></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan</button></div>
        </form></div></div>
@endsection

@push('script')
<script>
    (function () {
        const modal = document.getElementById('tripStatusModal');
        const form = document.getElementById('tripStatusForm');
        const method = document.getElementById('tripStatusMethod');
        document.querySelectorAll('.edit-trip').forEach(function (button) {
            button.addEventListener('click', function () {
                form.action = button.dataset.action;
                method.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                document.getElementById('tripStatusSelect').value = button.dataset.status || 'dijadwalkan';
                document.getElementById('tripOdoStart').value = button.dataset.start || '';
                document.getElementById('tripOdoEnd').value = button.dataset.end || '';
                document.getElementById('tripNotes').value = button.dataset.notes || '';
            });
        });
    })();
</script>
@endpush
