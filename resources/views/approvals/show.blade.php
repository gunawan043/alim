@extends('layouts.master')
@section('title') Detail Approval @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('title') Detail Approval @endslot
    @endcomponent

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

                    <div class="mt-3">
                        <a href="{{ url()->previous() }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
