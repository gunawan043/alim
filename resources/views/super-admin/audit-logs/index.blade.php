@extends('layouts.master')
@section('title') Audit Log @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') Audit Log @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Audit Log</h5>
                            <p class="text-muted mb-0">Riwayat aktivitas seluruh user sistem.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.sa.audit-logs.export', ['userId' => $userId]) }}" class="btn btn-soft-primary">
                                <i class="ri-download-cloud-2-line align-bottom me-1"></i> Export
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari action / tabel / record..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="user_id" class="form-control">
                                <option value="">Semua User</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="action" class="form-control">
                                <option value="">Semua Action</option>
                                @foreach($actions as $a)
                                    <option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>{{ $a }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" placeholder="Dari">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" placeholder="Sampai">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line"></i></button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('user.sa.audit-logs.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Tabel</th>
                                    <th>Record</th>
                                    <th>IP Address</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td><small>{{ $log->created_at->format('d/m/Y H:i:s') }}</small></td>
                                        <td>
                                            @if($log->user)
                                                <strong>{{ $log->user->name }}</strong>
                                                <br><small class="text-muted">{{ $log->user->email }}</small>
                                            @else
                                                <span class="text-muted">System</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $actionColors = [
                                                    'create' => 'success', 'store' => 'success',
                                                    'update' => 'primary', 'edit' => 'primary',
                                                    'delete' => 'danger', 'destroy' => 'danger',
                                                    'login' => 'info', 'logout' => 'secondary',
                                                ];
                                                $color = $actionColors[$log->action] ?? 'warning';
                                            @endphp
                                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ $log->action }}</span>
                                        </td>
                                        <td><code>{{ $log->table_name ?? '-' }}</code></td>
                                        <td><small class="text-muted">{{ $log->record_id ?? '-' }}</small></td>
                                        <td><small>{{ $log->ip_address ?? '-' }}</small></td>
                                        <td class="text-center">
                                            <a href="{{ route('user.sa.audit-logs.show', ['userId' => $userId, 'id' => $log->id]) }}" class="btn btn-sm btn-soft-primary">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Belum ada log.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($logs->hasPages())
    @include('shared._pagination', ['paginator' => $logs])
@endif
                </div>
            </div>
        </div>
    </div>
@endsection
