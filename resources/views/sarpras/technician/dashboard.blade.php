@extends('layouts.master')
@section('title') Workspace Teknisi @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.teknisi.dashboard') }}">Workspace Teknisi</a> @endslot
    @slot('title') Dashboard @endslot
@endcomponent

{{-- Available (Claimable) Orders --}}
@if(($available ?? collect())->count())
<div class="card mb-3 border-info">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="ri-inbox-unarchive-line me-1 text-info"></i>
            Work Order Belum Di-claim
            <span class="badge bg-info ms-2">{{ $available->count() }}</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Aset</th>
                        <th>Tipe</th>
                        <th>Laporan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($available as $wo)
                        <tr>
                            <td><code>{{ $wo->wo_number ?? $wo->order_number }}</code></td>
                            <td>{{ $wo->asset?->asset_name ?? '-' }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($wo->type) }}</span></td>
                            <td>
                                @if($wo->repair_request_id)
                                    <a href="{{ route('sarpras.pic.index') }}" class="small">Dilihat</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('sarpras.teknisi.claim', $wo->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-info">
                                        <i class="ri-hand-heart-line me-1"></i>Claim
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Quick Scan --}}
<div class="card mb-3">
    <div class="card-body text-center">
        <a href="{{ route('sarpass.scan.start') }}" class="btn btn-primary btn-lg">
            <i class="ri-qr-scan-line me-2 fs-4"></i>Scan QR Code untuk Work Order
        </a>
        <p class="text-muted mt-2 mb-0">Scan QR dari work order untuk langsung lanjutkan pengerjaan</p>
    </div>
</div>

{{-- Active Orders --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ri-tools-line me-1"></i> Work Order Saya</h5>
        <span class="badge bg-primary">{{ $orders->where('status', 'in_progress')->count() }} aktif</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Aset</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Batas</th>
                        <th>SLA</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $wo)
                    <tr>
                        <td><code>{{ $wo->order_number }}</code></td>
                        <td>
                            <a href="{{ route('sarpass.assets.passport.show', $wo->asset?->uuid ?? $wo->asset?->id) }}" class="text-decoration-none">
                                {{ $wo->asset?->asset_name ?? '-' }}
                            </a>
                        </td>
                        <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $wo->type)) }}</span></td>
                        <td>
                            <span class="badge {{ $wo->status == 'in_progress' ? 'bg-success' : ($wo->status == 'paused' ? 'bg-warning text-dark' : 'bg-info') }}">
                                {{ ucfirst($wo->status) }}
                            </span>
                        </td>
                        <td>{{ $wo->due_date?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>
                            @if($wo->sla_tracker)
                                @if($wo->sla_tracker->breached)
                                    <span class="badge bg-danger">BREACH</span>
                                @elseif($wo->sla_tracker->is_imminent)
                                    <span class="badge bg-warning text-dark">SOON</span>
                                @else
                                    <span class="badge bg-success">{{ $wo->sla_tracker->time_remaining_text }}</span>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('sarpras.teknisi.show', $wo->id) }}" class="btn btn-sm btn-outline-primary">
                                {{ $wo->status == 'in_progress' ? 'Lanjutkan' : 'Buka' }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada work order</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $orders->links() }}
</div>
@endsection
