@extends('layouts.master')
@section('title') Tracking Approval @endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Approval @endslot
    @slot('li_2_link') {{ route('user.approvals.index') }} @endslot
    @slot('li_2') Approval @endslot
    @slot('li_3_link') {{ route('user.approvals.show', $approval->id) }} @endslot
    @slot('li_3') {{ $approval->type_text }} @endslot
    @slot('li_4') Tracking @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-route-line me-2"></i>Tracking Approval</h5>
            </div>
            <div class="card-body">
                {{-- Info Card --}}
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <strong>{{ $approval->type_text }}</strong><br>
                            <small class="text-muted">Pemohon: {{ $approval->requestedBy?->name ?? 'N/A' }}</small>
                        </div>
                        <div class="ms-auto text-end">
                            <span class="badge bg-{{ $approval->status === 'PENDING' ? 'warning' : ($approval->status === 'APPROVED' ? 'success' : 'danger') }}">
                                {{ $approval->status_text }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="position-relative ps-4">
                    <div class="position-absolute top-0 bottom-0 start-0" style="width: 3px; background: #dee2e6; transform: translateX(-2px); border-radius: 2px;"></div>

                    @php
                        $steps = $approval->flow->steps ?? collect();
                        $actions = $approval->actions ?? collect();
                        $stepIndex = 0;
                    @endphp

                    @forelse($steps as $step)
                        @php $stepIndex++ @endphp
                        @php
                            $stepActions = $actions->where('step_name', $step->step_name)->sortBy('created_at');
                            $lastAction = $stepActions->last();
                            $statusClass = 'bg-secondary';
                            $icon = 'ri-question-line';
                            $statusText = 'Menunggu';

                            if ($lastAction) {
                                if ($lastAction->status === 'approved') {
                                    $statusClass = 'bg-success';
                                    $icon = 'ri-check-line';
                                    $statusText = 'Disetujui';
                                } elseif ($lastAction->status === 'rejected') {
                                    $statusClass = 'bg-danger';
                                    $icon = 'ri-close-line';
                                    $statusText = 'Ditolak';
                                }
                            }
                        @endphp
                        <div class="position-relative mb-4 pb-4 border-start border-2">
                            <div class="position-relative" style="margin-left: -21px; z-index: 1;">
                                <div class="rounded-circle {{ $statusClass }} d-flex align-items-center justify-content-center text-white"
                                     style="width: 40px; height: 40px;">
                                    <i class="{{ $icon }} fs-5"></i>
                                </div>
                            </div>
                            <div class="ms-3 mt-2">
                                <h6 class="mb-1 fw-semibold">{{ $step->step_name }}</h6>
                                @if($lastAction)
                                    <small class="text-muted">
                                        {{ $lastAction->actionBy?->name ?? 'N/A' }} — {{ $lastAction->created_at->diffForHumans() }}
                                    </small>
                                @else
                                    <small class="text-muted">Belum ada tindakan</small>
                                @endif
                                @if($lastAction && $lastAction->catatan)
                                    <blockquote class="blockquote mt-2 small fst-italic">"{{ $lastAction->catatan }}"</blockquote>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">Belum ada langkah approval dalam alur ini.</p>
                    @endforelse

                    {{-- History actions --}}
                    <h6 class="mt-4 mb-3 fw-semibold"><i class="ri-history-line me-1"></i> Riwayat Tindakan</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Aktor</th>
                                    <th>Langkah</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($actions as $action)
                                    <tr>
                                        <td><small>{{ $action->created_at->format('d/m/Y H:i') }}</small></td>
                                        <td>{{ $action->actionBy?->name ?? '-' }}</td>
                                        <td>{{ $action->step_name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $action->status === 'approved' ? 'success' : ($action->status === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($action->status) }}
                                            </span>
                                        </td>
                                        <td><small>{{ $action->catatan ?? '-' }}</small></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">Belum ada riwayat tindakan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('user.approvals.show', $approval->id) }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
