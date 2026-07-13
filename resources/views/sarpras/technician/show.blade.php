@extends('layouts.master')
@section('title') WO {{ $order->order_number }} @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Teknisi @endslot
    @slot('li_2') <a href="{{ route('sarpras.teknisi.dashboard') }}">Dashboard</a> @endslot
    @slot('title') {{ $order->order_number }} @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <h5 class="card-title mb-0">{{ $order->title ?? $order->order_number }}</h5>
                <span class="badge {{ $order->status == 'in_progress' ? 'bg-success' : ($order->status == 'paused' ? 'bg-warning text-dark' : 'bg-info') }}">
                    {{ ucfirst($order->status) }}
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        <h6 class="text-muted">Aset</h6>
                        <p>
                            <a href="{{ route('sarpass.assets.passport.show', $order->asset?->uuid ?? $order->asset?->id) }}" class="text-decoration-none">
                                {{ $order->asset?->asset_name ?? '-' }}
                            </a>
                            <code class="ms-1">{{ $order->asset?->asset_code }}</code>
                        </p>
                    </div>
                    <div class="col-sm-6">
                        <h6 class="text-muted">Lokasi</h6>
                        <p>{{ $order->asset?->room?->room_name ?? '-' }}</p>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-6">
                        <h6 class="text-muted">Tipe</h6>
                        <p>{{ ucfirst(str_replace('_', ' ', $order->type ?? '-')) }}</p>
                    </div>
                    <div class="col-sm-6">
                        <h6 class="text-muted">Batas Waktu</h6>
                        <p>{{ $order->due_date?->format('d/m/Y H:i') ?? '-' }}</p>
                    </div>
                </div>
                @if($order->description)
                <div class="mt-3">
                    <h6 class="text-muted">Deskripsi</h6>
                    <p>{{ $order->description }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Progress Notes --}}
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-list-line me-1"></i> Catatan Progres</h5></div>
            <div class="card-body">
                @forelse($order->progressNotes as $note)
                <div class="border-start border-primary border-3 ps-3 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $note->user->name ?? '-' }}</strong>
                        <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                    </div>
                    <p class="mb-0">{{ $note->note }}</p>
                </div>
                @empty
                <p class="text-muted mb-0">Belum ada catatan</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Action Panel --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-gamepad-line me-1"></i> Aksi</h5></div>
            <div class="card-body">
                @if($order->status == 'assigned')
                <form method="POST" action="{{ route('sarpras.teknisi.start', $order->id) }}" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-success w-100">
                        <i class="ri-play-line me-1"></i> Mulai Pengerjaan
                    </button>
                </form>
                @endif

                @if($order->status == 'in_progress')
                <button class="btn btn-warning w-100 mb-2" data-bs-toggle="modal" data-bs-target="#pauseModal">
                    <i class="ri-pause-line me-1"></i> Pause
                </button>
                <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#noteModal">
                    <i class="ri-file-list-line me-1"></i> Catatan
                </button>
                <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#completeModal">
                    <i class="ri-check-double-line me-1"></i> Selesaikan
                </button>
                @endif

                @if($order->status == 'paused')
                <form method="POST" action="{{ route('sarpras.teknisi.resume', $order->id) }}" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-success w-100">
                        <i class="ri-play-line me-1"></i> Lanjutkan
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- SLA Card --}}
        @if($order->sla_tracker)
        <div class="card">
            <div class="card-header"><h6 class="mb-0">SLA Tracker</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong>Status:</strong>
                    @if($order->sla_tracker->breached)
                        <span class="badge bg-danger">BREACHED</span>
                    @elseif($order->sla_tracker->is_imminent)
                        <span class="badge bg-warning text-dark">SOON</span>
                    @else
                        <span class="badge bg-success">OK</span>
                    @endif
                </p>
                <p class="mb-1"><strong>Sisa:</strong> {{ $order->sla_tracker->time_remaining_text }}</p>
                <p class="mb-0"><strong>Deadline:</strong> {{ $order->sla_tracker->deadline_at?->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Pause Modal --}}
<div class="modal fade" id="pauseModal">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('sarpras.teknisi.pause', $order->id) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Pause Pengerjaan</h5></div>
            <div class="modal-body">
                <label class="form-label">Alasan</label>
                <select name="reason_code" class="form-select mb-2" required>
                    <option value="material_tunggu">Material Belum Datang</option>
                    <option value="suku_cadang">Suku Cadang Tidak Tersedia</option>
                    <option value="kompleksitas">Kompleksitas Tinggi</option>
                    <option value="lainnya">Lainnya</option>
                </select>
                <textarea name="reason_text" class="form-control" placeholder="Catatan tambahan..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning">Pause</button>
            </div>
        </form>
    </div>
</div>

{{-- Note Modal --}}
<div class="modal fade" id="noteModal">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('sarpras.teknisi.note', $order->id) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Tambah Catatan</h5></div>
            <div class="modal-body">
                <textarea name="note" class="form-control" rows="4" placeholder="Tulis catatan..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Complete Modal --}}
<div class="modal fade" id="completeModal">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('sarpras.teknisi.complete', $order->id) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Selesaikan Work Order</h5></div>
            <div class="modal-body">
                <label class="form-label">Catatan Penyelesaian</label>
                <textarea name="completion_notes" class="form-control mb-2" rows="3" placeholder="Apa yang sudah dikerjakan..." required></textarea>
                <label class="form-label">Kondisi Aset Setelah</label>
                <select name="post_condition" class="form-select" required>
                    <option value="baik">Baik</option>
                    <option value="rusak_ringan">Rusak Ringan</option>
                    <option value="rusak_sedang">Rusak Sedang</option>
                    <option value="rusak_berat">Rusak Berat</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Selesaikan</button>
            </div>
        </form>
    </div>
</div>
@endsection