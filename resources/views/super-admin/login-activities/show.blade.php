@extends('layouts.master')

@section('title', 'Detail Aktivitas Login')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') <a href="{{ route('user.sa.login-activities.index', ['userId' => $userId]) }}">Aktivitas Login</a> @endslot
        @slot('title') Detail Aktivitas @endslot
    @endcomponent

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Detail Aktivitas Login</h5>
                    <span class="badge bg-{{ $activity->event_color }}-subtle text-{{ $activity->event_color }} fs-6">{{ $activity->event_label }}</span>
                </div>
                <div class="card-body">
                    <table class="table table-borderless align-middle mb-0">
                        <tr>
                            <td width="180" class="text-muted">Waktu</td>
                            <td><strong>{{ $activity->created_at?->format('d/m/Y H:i:s') }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Pengguna</td>
                            <td>
                                @if($activity->user)
                                    <div class="fw-semibold">{{ $activity->user->name }}</div>
                                    <small class="text-muted">{{ $activity->user->email }}</small>
                                @else
                                    <span class="text-muted">User tidak ditemukan</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Identity</td>
                            <td><code>{{ $activity->identity ?: '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td>{{ $activity->email ?: ($activity->user?->email ?? '-') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Alasan / Keterangan</td>
                            <td>{{ $activity->reason ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Alamat IP</td>
                            <td><code>{{ $activity->ip_address ?: '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Perangkat</td>
                            <td>{{ $activity->device }}<br><small class="text-muted">{{ $activity->user_agent ?: '-' }}</small></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                @if($activity->is_suspicious)
                                    <span class="badge bg-warning-subtle text-warning"><i class="ri-alert-line me-1"></i>Perlu dicek</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Normal</span>
                                @endif
                            </td>
                        </tr>
                    </table>

                    @if($activity->metadata)
                        <div class="mt-4">
                            <label class="form-label fw-semibold">Metadata</label>
                            <pre class="bg-light border rounded p-3 mb-0" style="font-size:12px">{{ json_encode($activity->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('user.sa.login-activities.index', ['userId' => $userId]) }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i>Kembali
                        </a>
                        <a href="{{ route('user.sa.audit-logs.index', ['userId' => $userId, 'search' => $activity->user?->email]) }}" class="btn btn-soft-primary">
                            <i class="ri-file-history-line me-1"></i>Buka Audit Log
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
