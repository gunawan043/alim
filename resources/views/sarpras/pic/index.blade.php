@extends('layouts.master')
@section('title') PIC Approval @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.dashboard') }}">Dashboard</a> @endslot
    @slot('title') Verifikasi & Approval Laporan @endslot
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
                    <i class="ri-shield-check-line me-1"></i>
                    Laporan Menunggu Verifikasi
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No. Laporan</th>
                                <th>Aset</th>
                                <th>Pelapor</th>
                                <th>Prioritas</th>
                                <th>Status</th>
                                <th>Dilaporkan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pending as $r)
                                @php
                                    $priorityBadge = match($r->priority) {
                                        'urgent' => 'bg-danger',
                                        'high' => 'bg-warning text-dark',
                                        'medium' => 'bg-info text-dark',
                                        default => 'bg-secondary'
                                    };
                                    $statusBadge = match($r->status) {
                                        'verification_pending' => 'bg-secondary',
                                        'approval_pending' => 'bg-warning text-dark',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td><code>{{ $r->request_number }}</code></td>
                                    <td>
                                        {{ Str::limit($r->asset?->asset_name, 28) }}
                                        <div class="small text-muted"><code>{{ $r->asset?->asset_code }}</code></div>
                                    </td>
                                    <td>{{ $r->reportedBy?->name ?? '-' }}</td>
                                    <td><span class="badge {{ $priorityBadge }}">{{ ucfirst($r->priority) }}</span></td>
                                    <td><span class="badge {{ $statusBadge }}">{{ str_replace('_', ' ', $r->status) }}</span></td>
                                    <td class="small text-muted">{{ $r->created_at?->diffForHumans() }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('sarpras.pic.show', $r->id) }}" class="btn btn-sm btn-primary">
                                            <i class="ri-eye-line"></i> Verifikasi
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-5">
                                    <i class="ri-check-double-line fs-1"></i><br>
                                    Tidak ada laporan yang perlu diverifikasi
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
