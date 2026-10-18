@extends('layouts.master')
@section('title') Work Order {{ $order->order_number }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.teknisi.dashboard') }}">Workspace Teknisi</a> @endslot
        @slot('title') {{ $order->order_number }}
    @endcomponent

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Detail Work Order</h5><span class="badge bg-info-subtle text-info">{{ ucfirst(str_replace('_',' ', $order->status)) }}</span></div>
                <div class="card-body"><table class="table table-borderless mb-0"><tbody>
                    <tr><td class="text-muted" style="width:160px">Aset</td><td>@if($order->asset)<a href="{{ route('sarpras.assets.passport', $order->asset->id) }}" class="fw-medium">{{ $order->asset->asset_name }}</a><br><code class="small">{{ $order->asset->asset_code }}</code>@else - @endif</td></tr>
                    <tr><td class="text-muted">Lokasi</td><td>{{ $order->asset?->room?->building?->building_name ? $order->asset->room->building->building_name.' — ' : '' }}{{ $order->asset?->room?->room_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Jenis</td><td>{{ ucfirst($order->type) }}</td></tr>
                    <tr><td class="text-muted">Jadwal</td><td>{{ $order->scheduled_date?->format('d/m/Y') ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Lingkup Pekerjaan</td><td>{{ $order->scope_of_work }}</td></tr>
                    @if($order->completion_notes)<tr><td class="text-muted">Hasil</td><td>{{ $order->completion_notes }}</td></tr>@endif
                    <tr><td class="text-muted">Biaya</td><td>Rp {{ number_format($order->total_cost,0,',','.') }}</td></tr>
                </tbody></table></div>
                <div class="card-footer bg-transparent d-flex flex-wrap gap-2">
                    @if(in_array($order->status, ['dispatched','draft']))
                        <form method="POST" action="{{ route('sarpras.teknisi.start', $order->id) }}">@csrf<button class="btn btn-success"><i class="ri-play-line me-1"></i>Mulai</button></form>
                    @elseif($order->status === 'in_progress')
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#pauseModal"><i class="ri-pause-line me-1"></i>Jeda</button>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeModal"><i class="ri-check-line me-1"></i>Selesaikan</button>
                    @elseif($order->status === 'paused')
                        <form method="POST" action="{{ route('sarpras.teknisi.resume', $order->id) }}">@csrf<button class="btn btn-primary"><i class="ri-play-line me-1"></i>Lanjutkan</button></form>
                    @endif
                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#noteModal"><i class="ri-chat-1-line me-1"></i>Catatan</button>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-history-line me-1"></i>Progres Pekerjaan</h5></div>
                <div class="card-body p-0">
                    @forelse($order->progressSteps->sortByDesc('created_at') as $step)
                        <div class="border-bottom p-3"><div class="d-flex justify-content-between"><span class="small fw-medium">{{ $step->performer?->name ?? 'Petugas' }}</span><small class="text-muted">{{ $step->created_at?->format('d/m H:i') }}</small></div><div class="small text-muted">{{ $step->notes ?? $step->description }}</div></div>
                    @empty
                        <div class="p-3"><x-empty-state title="Belum ada progres" message="Mulai pekerjaan untuk mencatat progres." /></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pauseModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('sarpras.teknisi.pause', $order->id) }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Jeda Pekerjaan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Alasan</label><input name="reason_code" class="form-control mb-2" required placeholder="Contoh: menunggu sparepart"><textarea name="reason_text" class="form-control" rows="2" placeholder="Keterangan…"></textarea></div><div class="modal-footer"><button class="btn btn-warning">Jeda</button></div></form></div></div>
    <div class="modal fade" id="completeModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('sarpras.teknisi.complete', $order->id) }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Selesaikan Pekerjaan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Hasil Pekerjaan</label><textarea name="notes" class="form-control mb-2" rows="2" required></textarea><label class="form-label">Biaya Aktual (Rp)</label><input type="number" name="total_cost" class="form-control mb-2" min="0"><label class="form-label">Kondisi Setelah</label><select name="condition_after" class="form-select">@foreach(\App\Models\AssetMaintenanceLog::CONDITION_OPTIONS as $c)<option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>@endforeach</select></div><div class="modal-footer"><button class="btn btn-success">Selesai</button></div></form></div></div>
    <div class="modal fade" id="noteModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('sarpras.teknisi.note', $order->id) }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Catatan Pekerjaan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><textarea name="note" class="form-control" rows="3" required></textarea></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>
@endsection
