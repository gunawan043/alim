@extends('layouts.master')
@section('title') Detail Approval @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('title') Detail Approval @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success"><i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger"><i class="ri-error-warning-line me-1"></i>{{ session('error') }}</div>
    @endif

    @php
        $step = $approval->currentStep();
        $user = auth()->user();
        $isAdmin = (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());
        $canAct = $approval->status === 'PENDING' && $step && (
            $isAdmin
            || ($step->step_permission && canPermission($step->step_permission))
            || ($step->role_name && method_exists($user, 'hasRole') && $user->hasRole($step->role_name))
        );
    @endphp

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detail Approval</h5>
                    @php $statusColors = ['PENDING' => 'warning', 'APPROVED' => 'success', 'REJECTED' => 'danger']; @endphp
                    <span class="badge bg-{{ $statusColors[$approval->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$approval->status] ?? 'secondary' }}">
                        {{ $approval->status_text }}
                    </span>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width:180px">Tipe Permintaan</th>
                            <td>{{ $approval->request_type_text }}</td>
                        </tr>
                        <tr>
                            <th>Pemohon</th>
                            <td>{{ $approval->requestedBy?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Pengajuan</th>
                            <td>{{ $approval->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if ($step)
                            <tr>
                                <th>Tahap Menunggu</th>
                                <td>
                                    <span class="badge bg-warning-subtle text-warning">
                                        Step {{ $step->step_order }} — {{ $step->role_name }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                    </table>

                    @if($approval->actions->count())
                        <h6 class="mt-4 mb-3">Langkah Approval</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:60px">Step</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Oleh</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approval->actions as $action)
                                        <tr>
                                            <td>{{ $action->step_order }}</td>
                                            <td>{{ $action->role_name }}</td>
                                            <td>
                                                @php
                                                    $actionColors = ['PENDING' => 'warning', 'APPROVED' => 'success', 'REJECTED' => 'danger'];
                                                @endphp
                                                <span class="badge bg-{{ $actionColors[$action->action] ?? 'secondary' }}-subtle text-{{ $actionColors[$action->action] ?? 'secondary' }}">
                                                    {{ $action->action }}
                                                </span>
                                            </td>
                                            <td>{{ $action->approvedBy?->name ?? '-' }}</td>
                                            <td><small>{{ $action->action_at?->format('d/m/Y H:i') ?? '-' }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($canAct)
                        <hr>
                        <h6 class="mb-3"><i class="ri-git-pull-request-line me-1"></i>Aksi Persetujuan</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <form method="POST"
                                      action="{{ route('user.approvals.approve', ['userId' => $user->id, 'approvalUuid' => $approval->id]) }}">
                                    @csrf
                                    <label class="form-label small">Catatan (opsional)</label>
                                    <textarea name="note" rows="2" class="form-control mb-2" placeholder="Catatan persetujuan..."></textarea>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="ri-check-double-line me-1"></i> Setujui Tahap Ini
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="POST"
                                      action="{{ route('user.approvals.reject', ['userId' => $user->id, 'approvalUuid' => $approval->id]) }}">
                                    @csrf
                                    <label class="form-label small">Alasan penolakan <span class="text-danger">*</span></label>
                                    <textarea name="note" rows="2" class="form-control mb-2" required placeholder="Alasan penolakan..."></textarea>
                                    <button type="submit" class="btn btn-outline-danger w-100"
                                            onclick="return confirm('Tolak pengajuan ini?')">
                                        <i class="ri-close-circle-line me-1"></i> Tolak Pengajuan
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <div class="mt-3 d-flex gap-2">
                        <a href="{{ route('user.approvals.index', ['userId' => $user->id]) }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                        @if (\Illuminate\Support\Facades\Route::has('user.approvals.track'))
                            <a href="{{ route('user.approvals.track', ['userId' => $user->id, 'approvalUuid' => $approval->id]) }}"
                               class="btn btn-soft-info">
                                <i class="ri-route-line me-1"></i> Lacak Alur
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
