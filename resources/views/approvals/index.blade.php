@extends('layouts.master')
@section('title') Approval @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('title') Approval @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Approval</h5>
                            <p class="text-muted mb-0">Kelola semua permintaan persetujuan.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Quick stats --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-body py-2 text-center">
                                    <h6 class="mb-1 text-warning">{{ $requests->where('status', 'PENDING')->count() }}</h6>
                                    <small class="text-muted">Menunggu</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-body py-2 text-center">
                                    <h6 class="mb-1 text-success">{{ $requests->where('status', 'APPROVED')->count() }}</h6>
                                    <small class="text-muted">Disetujui</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-body py-2 text-center">
                                    <h6 class="mb-1 text-danger">{{ $requests->where('status', 'REJECTED')->count() }}</h6>
                                    <small class="text-muted">Ditolak</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Tipe</th>
                                    <th>Pemohon</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                    <tr>
                                        <td><strong>{{ $req->request_type_text }}</strong></td>
                                        <td>{{ $req->requestedBy?->name ?? '-' }}</td>
                                        <td>
                                            @php $statusColors = ['PENDING' => 'warning', 'APPROVED' => 'success', 'REJECTED' => 'danger']; @endphp
                                            <span class="badge bg-{{ $statusColors[$req->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$req->status] ?? 'secondary' }}">
                                                {{ $req->status_text }}
                                            </span>
                                        </td>
                                        <td><small>{{ $req->created_at->format('d/m/Y H:i') }}</small></td>
                                        <td class="text-center">
                                            <a href="{{ route('user.approvals.show', $req->id) }}" class="btn btn-sm btn-soft-secondary">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada data approval.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($requests->hasPages())
                        @include('shared._pagination', ['paginator' => $requests])
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
