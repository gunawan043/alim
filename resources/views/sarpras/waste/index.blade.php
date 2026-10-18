@extends('layouts.master')
@section('title') Pengelolaan Sampah DLH @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Pengelolaan Sampah DLH
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            ['Berat Bulan Ini', number_format($stats['weight'], 2, ',', '.').' kg', 'primary', 'ri-scales-3-line'],
            ['Jumlah Karung', number_format($stats['sacks']), 'info', 'ri-shopping-bag-3-line'],
            ['Catatan Sampah', number_format($stats['logs']), 'success', 'ri-file-list-3-line'],
            ['Diserahkan ke DLH', number_format($stats['handovers'], 2, ',', '.').' kg', 'warning', 'ri-truck-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-3"><span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-5 fw-bold">{{ $value }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body"><form method="GET" action="{{ route('sarpras.waste.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label small fw-semibold">Bulan</label><input type="month" name="month" class="form-control" value="{{ $month }}"></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Sumber</label><select name="source_id" class="form-select"><option value="">Semua</option>@foreach($sources as $source)<option value="{{ $source->id }}" {{ request('source_id') === $source->id ? 'selected' : '' }}>{{ $source->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small fw-semibold">Jenis</label><select name="waste_type" class="form-select"><option value="">Semua</option>@foreach(App\Models\WasteLog::TYPE_OPTIONS as $type)<option value="{{ $type }}" {{ request('waste_type') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(['submitted'=>'Menunggu','verified'=>'Diverifikasi','rejected'=>'Ditolak'] as $key=>$label)<option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-1"><button class="btn btn-primary w-100"><i class="ri-filter-3-line"></i></button></div>
            <div class="col-md-2"><a href="{{ route('sarpras.waste.report', ['month' => $month]) }}" class="btn btn-danger w-100"><i class="ri-file-pdf-line me-1"></i>Laporan DLH</a></div>
        </form></div>
    </div>

    <div class="row g-3 mb-3">
        @foreach($typeSummary as $summary)
            <div class="col-6 col-xl-3"><div class="card border-0 bg-light"><div class="card-body py-2"><div class="text-muted small text-uppercase">{{ $summary->waste_type }}</div><div class="fw-bold">{{ number_format($summary->weight, 2, ',', '.') }} kg</div><div class="small text-muted">{{ number_format($summary->sacks) }} karung</div></div></div></div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-delete-bin-6-line me-2 text-success"></i>Catatan Sampah Harian</h5><p class="text-muted small mb-0">Wajib berat (kg) atau jumlah karung, jenis sampah, dan foto bukti.</p></div><div class="d-flex gap-2"><button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSourceModal"><i class="ri-add-line me-1"></i>Sumber</button><button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addLogModal"><i class="ri-add-line me-1"></i>Catat Sampah</button></div></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Sumber</th><th>Jenis</th><th class="text-end">Berat</th><th class="text-end">Karung</th><th>Pemilahan</th><th>Tujuan</th><th>Foto</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($logs as $log)
                @php $tone = ['submitted'=>'warning','verified'=>'success','rejected'=>'danger'][$log->status] ?? 'secondary'; $photo = $log->photo_url ?: ($log->photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($log->photo_path) : null); @endphp
                <tr><td class="small">{{ $log->log_date?->format('d/m/Y') }}</td><td class="small fw-medium">{{ $log->source?->name }}</td><td><span class="badge bg-light text-dark border">{{ ucfirst($log->waste_type) }}</span></td><td class="text-end">{{ number_format($log->weight_kg,2,',','.') }} kg</td><td class="text-end">{{ number_format($log->sacks_count) }}</td><td class="small">{{ ucfirst($log->sorting_status) }}</td><td class="small">{{ ucfirst(str_replace('_',' ',$log->destination)) }}</td><td>@if($photo)<a href="{{ $photo }}" target="_blank" class="btn btn-sm btn-outline-info"><i class="ri-image-line"></i></a>@else - @endif</td><td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst($log->status) }}</span></td><td class="text-end">@if($log->status === 'submitted')<button class="btn btn-sm btn-outline-success verify-log" data-bs-toggle="modal" data-bs-target="#verifyLogModal" data-action="{{ route('sarpras.waste.log.verify', $log->id) }}" data-name="{{ $log->source?->name }}"><i class="ri-check-double-line"></i></button>@endif</td></tr>
            @empty
                <x-empty-state colspan="10" title="Belum ada catatan sampah" message="Catat timbulan sampah harian untuk laporan DLH." />
            @endforelse
        </tbody></table></div></div>
        @if($logs->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $logs])</div>@endif
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-truck-line me-2 text-primary"></i>Penyerahan Sampah ke DLH / Pihak Ketiga</h5></div><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addHandoverModal"><i class="ri-add-line me-1"></i>Catat Penyerahan</button></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Tujuan</th><th>Jenis</th><th class="text-end">Berat</th><th class="text-end">Karung</th><th>No. Tanda Terima</th><th>Foto</th><th>Petugas</th></tr></thead><tbody>
            @forelse($handovers as $handover)
                @php $photo = $handover->photo_url ?: ($handover->photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($handover->photo_path) : null); @endphp
                <tr><td class="small">{{ $handover->handover_date?->format('d/m/Y') }}</td><td class="small">{{ $handover->destination }}</td><td class="small">{{ ucfirst($handover->waste_type ?? '-') }}</td><td class="text-end">{{ number_format($handover->total_weight_kg,2,',','.') }} kg</td><td class="text-end">{{ number_format($handover->total_sacks) }}</td><td class="small">{{ $handover->receipt_number ?? '-' }}</td><td>@if($photo)<a href="{{ $photo }}" target="_blank" class="btn btn-sm btn-outline-info"><i class="ri-image-line"></i></a>@else - @endif</td><td class="small">{{ $handover->officer?->name ?? '-' }}</td></tr>
            @empty
                <x-empty-state colspan="8" title="Belum ada penyerahan sampah" message="Catat penyerahan ke DLH/pihak ketiga." />
            @endforelse
        </tbody></table></div></div>
    </div>

    <div class="modal fade" id="addSourceModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.waste.source.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Sumber Sampah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Nama Sumber</label><input name="name" class="form-control" required></div><div class="mb-3"><label class="form-label">Jenis Sumber</label><select name="source_type" class="form-select">@foreach(App\Models\WasteSource::TYPE_OPTIONS as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select></div><div><label class="form-label">Gedung</label><select name="building_id" class="form-select"><option value="">— Tidak ada —</option>@foreach($buildings as $building)<option value="{{ $building->id }}">{{ $building->building_name }}</option>@endforeach</select></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>

    <div class="modal fade" id="addLogModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('sarpras.waste.log.store') }}" enctype="multipart/form-data" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Catat Sampah Harian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Sumber</label><select name="source_id" class="form-select" required><option value="">— Pilih —</option>@foreach($sources as $source)<option value="{{ $source->id }}">{{ $source->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Tanggal</label><input type="date" name="log_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
        <div class="col-md-4"><label class="form-label">Jenis Sampah</label><select name="waste_type" class="form-select" required>@foreach(App\Models\WasteLog::TYPE_OPTIONS as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Berat (kg)</label><input type="number" name="weight_kg" class="form-control" min="0" step="0.01" value="0"></div>
        <div class="col-md-4"><label class="form-label">Jumlah Karung</label><input type="number" name="sacks_count" class="form-control" min="0" value="0"></div>
        <div class="col-md-4"><label class="form-label">Volume (m³)</label><input type="number" name="volume_m3" class="form-control" min="0" step="0.01"></div>
        <div class="col-md-4"><label class="form-label">Pemilahan</label><select name="sorting_status" class="form-select"><option value="terpilah">Terpilah</option><option value="tercampur">Tercampur</option></select></div>
        <div class="col-md-4"><label class="form-label">Tujuan</label><select name="destination" class="form-select">@foreach(App\Models\WasteLog::DESTINATION_OPTIONS as $destination)<option value="{{ $destination }}">{{ ucfirst(str_replace('_',' ',$destination)) }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Foto Bukti / Link Google Drive <span class="text-danger">*</span></label><input type="url" name="photo_url" class="form-control mb-1" placeholder="Link Google Drive"><input type="file" id="waste_photo" name="photo" class="form-control" accept="image/*"><button type="button" class="btn btn-outline-success btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="waste_photo" data-preview="waste_photo_preview"><i class="ri-camera-line me-1"></i>Kamera + watermark</button><div id="waste_photo_preview"></div></div>
        <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
    </div></div><div class="modal-footer"><button class="btn btn-success">Simpan Catatan</button></div></form></div></div>

    <div class="modal fade" id="addHandoverModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('sarpras.waste.handover.store') }}" enctype="multipart/form-data" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Catat Penyerahan Sampah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
        <div class="col-md-4"><label class="form-label">Tanggal</label><input type="date" name="handover_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
        <div class="col-md-4"><label class="form-label">Tujuan</label><input name="destination" class="form-control" value="DLH" required></div>
        <div class="col-md-4"><label class="form-label">Jenis</label><select name="waste_type" class="form-select"><option value="">Campuran</option>@foreach(App\Models\WasteLog::TYPE_OPTIONS as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Total Berat (kg)</label><input type="number" name="total_weight_kg" class="form-control" min="0" step="0.01" required></div>
        <div class="col-md-4"><label class="form-label">Total Karung</label><input type="number" name="total_sacks" class="form-control" min="0" required></div>
        <div class="col-md-4"><label class="form-label">No. Tanda Terima</label><input name="receipt_number" class="form-control"></div>
        <div class="col-12"><label class="form-label">Foto Penyerahan <span class="text-danger">*</span></label><input type="url" name="photo_url" class="form-control mb-1" placeholder="Link Google Drive"><input type="file" id="handover_photo" name="photo" class="form-control" accept="image/*"><button type="button" class="btn btn-outline-success btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="handover_photo" data-preview="handover_photo_preview"><i class="ri-camera-line me-1"></i>Kamera + watermark</button><div id="handover_photo_preview"></div></div>
        <div class="col-md-6"><label class="form-label">Bukti Tanda Terima / Link</label><input type="url" name="receipt_url" class="form-control mb-1" placeholder="Link Google Drive"><input type="file" name="receipt" class="form-control" accept="image/*"></div>
        <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
    </div></div><div class="modal-footer"><button class="btn btn-primary">Simpan Penyerahan</button></div></form></div></div>

    <x-modal-confirm id="verifyLogModal" icon="https://cdn.lordicon.com/lupuorrc.json" title="Verifikasi Catatan Sampah?" submitLabel="Simpan Verifikasi" submitIcon="ri-check-double-line" submitClass="btn-success" formId="verifyLogForm">
        <p class="text-center text-muted mb-2" id="verifyLogName">Catatan sampah</p>
        <select name="decision" form="verifyLogForm" class="form-select mb-2"><option value="verified">Verifikasi</option><option value="rejected">Tolak / perlu perbaikan</option></select>
        <textarea name="coordinator_note" form="verifyLogForm" class="form-control" rows="2" placeholder="Catatan…"></textarea>
    </x-modal-confirm>
    <form id="verifyLogForm" method="POST" class="d-none">@csrf</form>

    @include('sarpras.partials.camera-modal')
@endsection

@push('script')
<script>
    document.querySelectorAll('.verify-log').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('verifyLogForm').action = button.dataset.action;
            document.getElementById('verifyLogName').textContent = button.dataset.name;
        });
    });
</script>
@endpush
