@extends('layouts.master')
@section('title') Riwayat Approval @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('title') Riwayat Approval @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Riwayat Approval</h5>
                            <p class="text-muted mb-0">Permintaan yang sudah diproses.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Cari pemohon..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Disetujui</option>
                                <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.approvals.history') }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

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
                                            @php $statusColors = ['APPROVED' => 'success', 'REJECTED' => 'danger']; @endphp
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
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat.</td>
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
