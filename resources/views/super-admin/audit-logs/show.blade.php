@extends('layouts.master')
@section('title') Detail Audit Log @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') Audit Log @endslot
        @slot('title') Detail Log #{{ $log->id }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Detail Audit Log</h5></div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td width="180" class="text-muted">Waktu</td>
                            <td><strong>{{ $log->created_at->format('d/m/Y H:i:s') }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">User</td>
                            <td>
                                @if($log->user)
                                    <strong>{{ $log->user->name }}</strong>
                                    <br><small class="text-muted">{{ $log->user->email }}</small>
                                @else
                                    <span class="text-muted">System</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Action</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $log->action }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tabel</td>
                            <td><code>{{ $log->table_name ?? '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Record ID</td>
                            <td><code>{{ $log->record_id ?? '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">IP Address</td>
                            <td>{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">User Agent</td>
                            <td><small class="text-muted">{{ $log->user_agent ?? '-' }}</small></td>
                        </tr>
                    </table>

                    @if($log->old_values)
                        <div class="mt-3">
                            <label class="form-label fw-bold">Data Lama (Sebelum)</label>
                            <pre class="bg-light p-3 rounded border" style="font-size:12px">{{ json_encode(json_decode($log->old_values), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    @if($log->new_values)
                        <div class="mt-3">
                            <label class="form-label fw-bold">Data Baru (Sesudah)</label>
                            <pre class="bg-light p-3 rounded border" style="font-size:12px">{{ json_encode(json_decode($log->new_values), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('user.sa.audit-logs.index', ['userId' => $userId]) }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
