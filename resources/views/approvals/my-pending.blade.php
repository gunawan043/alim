@extends('layouts.master')
@section('title') Approval Saya @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('title') Approval Saya @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Approval Saya</h5>
                            <p class="text-muted mb-0">Permintaan yang menunggu persetujuan Anda.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Tipe</th>
                                    <th>Pemohon</th>
                                    <th>Tahap</th>
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
                                            @php $currentStep = $req->actions->where('action', 'PENDING')->first(); @endphp
                                            @if($currentStep)
                                                <span class="badge bg-warning-subtle text-warning">
                                                    Step {{ $currentStep->step_order }} — {{ $currentStep->role_name }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">-</span>
                                            @endif
                                        </td>
                                        <td><small>{{ $req->created_at->format('d/m/Y H:i') }}</small></td>
                                        <td class="text-center">
                                            <a href="{{ route('user.approvals.show', $req->id) }}" class="btn btn-sm btn-soft-primary">
                                                <i class="ri-eye-line me-1"></i> Review
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Tidak ada approval yang menunggu.</td>
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
