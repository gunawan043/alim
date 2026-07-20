@extends('layouts.master')
@section('title') Approval Kepala Sarpras @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Approval Final @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="ri-check-line me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ri-verified-badge-line me-1"></i>
                    Work Order Selesai — Menunggu Approval
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>WO #</th>
                                <th>Aset</th>
                                <th>Teknisi</th>
                                <th>Tipe</th>
                                <th>Diselesaikan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pending as $wo)
                                <tr>
                                    <td><code>{{ $wo->wo_number ?? $wo->order_number }}</code></td>
                                    <td>{{ $wo->asset?->asset_name ?? '-' }}</td>
                                    <td>{{ $wo->technician?->name ?? '-' }}</td>
                                    <td><span class="badge bg-secondary">{{ $wo->type }}</span></td>
                                    <td class="small text-muted">{{ $wo->completed_at?->diffForHumans() ?? '-' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('sarpras.kepala.show', $wo->id) }}" class="btn btn-sm btn-success">
                                            <i class="ri-eye-line"></i> Review
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-5">
                                    <i class="ri-checkbox-circle-line fs-1"></i><br>
                                    Tidak ada WO yang menunggu approval
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if(method_exists($pending, 'links'))
                <div class="card-footer">{{ $pending->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
