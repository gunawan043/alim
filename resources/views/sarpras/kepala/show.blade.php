@extends('layouts.master')
@section('title') Review WO @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.kepala.index') }}">Approval</a> @endslot
    @slot('title') {{ $order->wo_number ?? $order->order_number }} @endslot
@endcomponent

<div class="row">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Detail WO</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">WO #</dt>
                    <dd class="col-sm-8"><code>{{ $order->wo_number ?? $order->order_number }}</code></dd>

                    <dt class="col-sm-4">Aset</dt>
                    <dd class="col-sm-8">
                        <strong>{{ $order->asset?->asset_name }}</strong>
                        <div class="small text-muted"><code>{{ $order->asset?->asset_code }}</code></div>
                    </dd>

                    <dt class="col-sm-4">Teknisi</dt>
                    <dd class="col-sm-8">{{ $order->technician?->name ?? '-' }}</dd>

                    <dt class="col-sm-4">Tipe</dt>
                    <dd class="col-sm-8"><span class="badge bg-secondary">{{ $order->type }}</span></dd>

                    <dt class="col-sm-4">Scope</dt>
                    <dd class="col-sm-8">{{ $order->scope_of_work }}</dd>

                    <dt class="col-sm-4">Selesai</dt>
                    <dd class="col-sm-8">{{ $order->completed_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Catatan Progres</h5></div>
            <div class="card-body" style="max-height:280px; overflow:auto;">
                @forelse($order->progressNotes ?? [] as $n)
                    <div class="border-start border-3 border-primary ps-2 mb-2">
                        <div class="small text-muted">
                            <strong>{{ $n->user?->name ?? '-' }}</strong> · {{ $n->created_at?->diffForHumans() }}
                        </div>
                        <div>{{ $n->note }}</div>
                    </div>
                @empty
                    <p class="text-muted text-center mb-0">Tidak ada catatan</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0"><i class="ri-verified-badge-line me-1"></i> Persetujuan</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('sarpras.kepala.approve', $order->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan Kepala Sarpras</label>
                        <textarea name="notes" id="notes" rows="4" class="form-control"
                            placeholder="Catatan persetujuan (opsional)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="ri-check-double-line me-1"></i> Setujui & Tutup WO
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
