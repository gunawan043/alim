@extends('layouts.master')
@section('title') Penanganan Listrik Padam @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.electricity.index') }}">Listrik &amp; Token</a> @endslot
        @slot('title') Penanganan Listrik Padam
    @endcomponent

    <x-sarpras-stats :items="[
        ['label'=>'Total Kejadian','value'=>$stats['total'],'tone'=>'primary','icon'=>'ri-alarm-warning-line'],
        ['label'=>'Masih Padam','value'=>$stats['open'],'tone'=>'danger','icon'=>'ri-flashlight-off-line'],
        ['label'=>'Sudah Menyala','value'=>$stats['resolved'],'tone'=>'success','icon'=>'ri-check-double-line'],
        ['label'=>'Kejadian Hari Ini','value'=>$stats['today'],'tone'=>'warning','icon'=>'ri-calendar-event-line'],
    ]" />

    <x-sarpras-filter-card :action="route('sarpras.electricity.outages.index')" :reset="route('sarpras.electricity.outages.index')">
        <div class="col-md-4"><label class="form-label small fw-semibold">Cari</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Lokasi / penyebab…"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option><option value="open" {{ request('status')==='open'?'selected':'' }}>Padam</option><option value="resolved" {{ request('status')==='resolved'?'selected':'' }}>Menyala</option></select></div>
    </x-sarpras-filter-card>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-alarm-warning-line me-2 text-danger"></i>Kejadian Listrik Padam</h5><p class="text-muted small mb-0">Catat kejadian, tindakan petugas, waktu menyala kembali, dan foto sebelum/sesudah.</p></div>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addOutageModal"><i class="ri-add-line me-1"></i>Catat Kejadian</button>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>No</th><th>Waktu</th><th>Lokasi</th><th>Jenis</th><th>Penyebab</th><th>Petugas</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($outages as $i => $outage)
                @php $tone = $outage->status === 'resolved' ? 'success' : 'danger'; @endphp
                <tr><td>{{ $outages->firstItem() + $i }}</td><td class="small">{{ $outage->occurred_at?->format('d/m/Y H:i') }}</td><td class="small">{{ $outage->location }}</td><td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_',' ',$outage->outage_type)) }}</span></td><td class="small">{{ \Illuminate\Support\Str::limit($outage->cause, 50) ?: '-' }}</td><td class="small">{{ $outage->actions->first()?->officer?->name ?? '-' }}</td><td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ $outage->status === 'resolved' ? 'Menyala' : 'Padam' }}</span></td><td class="text-end"><a href="{{ route('sarpras.electricity.outage.show', ['outageId' => $outage->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>
            @empty
                <x-empty-state colspan="8" title="Belum ada kejadian listrik padam" message="Catat kejadian untuk memulai laporan penanganan." />
            @endforelse
        </tbody></table></div></div>
        @if($outages->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $outages])</div>@endif
    </div>

    <div class="modal fade" id="addOutageModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.electricity.outage.store') }}" enctype="multipart/form-data" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Catat Listrik Padam</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Waktu Kejadian</label><input type="datetime-local" name="occurred_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div><div class="mb-3"><label class="form-label">Lokasi</label><input name="location" class="form-control" required></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Jenis Padam</label><select name="outage_type" class="form-select"><option value="tidak_terencana">Tidak Terencana</option><option value="terencana">Terencana</option></select></div><div class="col-md-6"><label class="form-label">Pelapor</label><input name="reporter_name" class="form-control" value="{{ auth()->user()->name }}"></div></div><div class="mt-3"><label class="form-label">Penyebab Singkat</label><textarea name="cause" class="form-control" rows="2"></textarea></div><div class="mt-3"><label class="form-label">Foto Sebelum / Link Drive</label><input type="url" name="before_url" class="form-control mb-1" placeholder="Link Google Drive"><input type="file" id="outage_before_file" name="before_photo" class="form-control" accept="image/*"><button type="button" class="btn btn-outline-danger btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="outage_before_file" data-preview="outage_before_preview"><i class="ri-camera-line me-1"></i>Kamera + watermark</button><div id="outage_before_preview"></div></div><div class="mt-3"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div></div><div class="modal-footer"><button class="btn btn-danger">Simpan Kejadian</button></div></form></div></div>

    @include('sarpras.partials.camera-modal')
@endsection
