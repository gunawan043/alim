@extends('layouts.master')
@section('title') Riwayat Laporan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.divisi.dashboard') }}">Portal Divisi</a> @endslot
    @slot('title') Riwayat Laporan Kerusakan @endslot
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
                    <i class="ri-history-line me-1"></i>
                    Laporan Saya
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No. Laporan</th>
                                <th>Aset</th>
                                <th>Judul</th>
                                <th>Prioritas</th>
                                <th>Status</th>
                                <th>WO</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myReports ?? [] as $r)
                                <tr>
                                    <td><code>{{ $r->request_number }}</code></td>
                                    <td>
                                        {{ Str::limit($r->asset?->asset_name, 30) }}
                                        <div class="small text-muted"><code>{{ $r->asset?->asset_code }}</code></div>
                                    </td>
                                    <td>{{ Str::limit($r->title, 40) }}</td>
                                    <td>
                                        @php
                                            $priorityBadge = match($r->priority) {
                                                'urgent' => 'bg-danger',
                                                'high' => 'bg-warning text-dark',
                                                'medium' => 'bg-info text-dark',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $priorityBadge }}">{{ ucfirst($r->priority) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusBadge = match($r->status) {
                                                'verification_pending' => 'bg-secondary',
                                                'verification_in_progress' => 'bg-info text-dark',
                                                'approval_pending' => 'bg-warning text-dark',
                                                'execution_pending' => 'bg-primary',
                                                'started' => 'bg-primary',
                                                'completed' => 'bg-success',
                                                'closed' => 'bg-success',
                                                'verification_rejected', 'approval_rejected' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusBadge }}">
                                            {{ str_replace('_', ' ', $r->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($r->workOrders->count())
                                            <span class="badge bg-info-subtle text-info">{{ $r->workOrders->count() }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-5">
                                    <i class="ri-inbox-line fs-1"></i><br>
                                    Belum ada laporan kerusakan
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
