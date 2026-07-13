@extends('layouts.master')
@section('title') Portal Division @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Portal {{ auth()->user()->name }} — Division @endslot
@endcomponent

{{-- Stats Cards --}}
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card widget-shadow">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm bg-light rounded">
                            <i class="mdi mdi-archive text-primary fs-3"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-1">{{ $stats['total_assets'] }}</h3>
                        <p class="text-muted mb-0">Total Aset Division</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card widget-shadow">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm bg-light rounded">
                            <i class="mdi mdi-check-circle text-success fs-3"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-1 text-success">{{ $stats['good'] }}</h3>
                        <p class="text-muted mb-0">Kondisi Baik</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card widget-shadow">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm bg-light rounded">
                            <i class="mdi mdi-wrench text-warning fs-3"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-1 text-warning">{{ $stats['maintenance'] }}</h3>
                        <p class="text-muted mb-0">Dalam Maintenance</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card widget-shadow">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm bg-light rounded">
                            <i class="mdi mdi-alert text-danger fs-3"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-1 text-danger">{{ $stats['broken'] }}</h3>
                        <p class="text-muted mb-0">Perlu Perbaikan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SLA Alerts --}}
@if($slaAlerts->isNotEmpty())
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="mdi mdi-alert-circle me-1"></i> SLA Alerts</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($slaAlerts as $alert)
                    <div class="list-group-item d-flex align-items-start">
                        <i class="mdi mdi-{{ $alert['type'] == 'breached' ? 'alert-circle text-danger' : 'alert-decagram text-warning' }} mt-1 me-2"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $alert['title'] }}</h6>
                            <p class="text-muted mb-1 small">{{ $alert['message'] }}</p>
                            <a href="{{ $alert['link'] }}" class="btn btn-sm btn-link px-0">Detail &rarr;</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    {{-- Recent Assets --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="mdi mdi-package-variant me-1"></i> Aset Division</h5>
                <a href="{{ route('sarpras.division.assets') }}" class="btn btn-sm btn-outline-secondary float-end">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Kode</th>
                                <th>Ruang</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAssets as $a)
                            <tr>
                                <td>
                                    <a href="{{ route('sarpass.assets.passport.show', $a->uuid ?? $a->id) }}" class="text-decoration-none">
                                        <i class="ri-passport-line text-info me-1"></i>{{ Str::limit($a->asset_name, 30) }}
                                    </a>
                                </td>
                                <td><code>{{ $a->asset_code }}</code></td>
                                <td>{{ $a->room?->room_name ?? '-' }}</td>
                                <td>
                                    @if($a->is_active)
                                        <span class="badge bg-success-subtle text-success fs-11">{{ ucfirst(str_replace('_', ' ', $a->status)) }}</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger fs-11">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('sarpass.assets.passport.show', $a->uuid ?? $a->id) }}" class="btn btn-sm btn-link p-0" title="Passport">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada aset di division Anda</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Work Orders --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="mdi mdi-clipboard-list me-1"></i> Work Order Aktif</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($pendingWOs as $wo)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong class="text-truncate" style="max-width:200px">{{ $wo->asset?->asset_name }}</strong>
                            <span class="badge bg-info bg-opacity-10 text-info fs-11">{{ ucfirst($wo->status) }}</span>
                        </div>
                        <small class="text-muted">PIC: {{ $wo->technician?->profile?->first_name ?? '-' }} | Due: {{ $wo->due_date?->format('d/m/Y') }}</small>
                        @if($wo->sla_tracker && $wo->sla_tracker->breached)
                        <span class="badge bg-danger">BREACHED</span>
                        @endif
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted">Tidak ada WO aktif</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection