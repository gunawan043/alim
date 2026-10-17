@extends('layouts.master')
@section('title') Audit Log Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.reports.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">Laporan</a> @endslot
        @slot('title') Audit Log @endslot
    @endcomponent

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.asrama.reports.audit', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Aksi</label>
                    <select name="action" class="form-select">
                        <option value="">Semua aksi</option>
                        @foreach(['DORMITORY_PERMIT_APPROVED', 'DORMITORY_PERMIT_REJECTED', 'DORMITORY_ROOM_MOVE_APPROVED', 'DORMITORY_ROOM_MOVE_REJECTED', 'DORMITORY_ACTIVITY_CREATED', 'DORMITORY_ACTIVITY_CLOSED', 'DORMITORY_ACTIVITY_REOPENED', 'DORMITORY_BROADCAST_SENT'] as $action)
                            <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('user.asrama.reports.audit', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ri-history-line me-2 text-primary"></i>Audit Log Asrama</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $logs->total() }} entri</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Tabel</th><th>Record</th><th>IP</th></tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="small">{{ $log->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="small">{{ $log->user?->name ?? 'Sistem' }}</td>
                                <td><span class="badge bg-info-subtle text-info">{{ $log->action }}</span></td>
                                <td class="small text-muted">{{ $log->table_name }}</td>
                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($log->record_id, 12) }}</td>
                                <td class="small text-muted">{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6" title="Belum ada audit log"
                                message="Aktivitas izin, mutasi, kegiatan, dan broadcast akan tercatat di sini." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">@include('shared._pagination', ['paginator' => $logs])</div>
        @endif
    </div>
@endsection
