{{-- Teacher attendance history --}}
@extends('layouts.master')
@section('title') Riwayat Absensi @endsection

@push('css')
<style>
.table-freeze{table-layout:auto;min-width:800px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:10px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.badge-status{font-size:.78rem;padding:.35em .7em}
.filter-card{background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);border:1px solid #93c5fd}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Guru @endslot
    @slot('li_2') Riwayat Absensi @endslot
    @slot('title') Riwayat Absensi Guru @endslot
@endcomponent

<div class="card filter-card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-medium text-muted">Mulai Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date', today()->startOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium text-muted">Sampai</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date', today()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="hadir" {{ request('status') == 'hadir' ? 'selected' : '' }}>Tepat Waktu</option>
                    <option value="terlambat" {{ request('status') == 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="belum_keluar" {{ request('status') == 'belum_keluar' ? 'selected' : '' }}>Belum Keluar</option>
                    <option value="keluar_cepat" {{ request('status') == 'keluar_cepat' ? 'selected' : '' }}>Pulang Cepat</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ri-search-line me-1"></i>Filter
                </button>
                <a href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
                    <i class="ri-refresh-line"></i>
                </a>
                <a href="{{ route('user.teacher-qr.history.export', ['userId' => $userId, 'start_date' => request('start_date', today()->startOfMonth()->format('Y-m-d')), 'end_date' => request('end_date', today()->format('Y-m-d'))]) }}"
                   class="btn btn-success btn-sm" title="Export Excel">
                    <i class="ri-file-excel-2-line me-1"></i>Export
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="ri-file-list-3-line"></i>
            <h5 class="mb-0">Data Absensi</h5>
        </div>
        <span class="badge bg-light text-primary">{{ $records->count() }} record</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-freeze mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th>Waktu</th>
                        <th>Status Masuk</th>
                        <th>Status Keluar</th>
                        <th>Terlambat / Pulang Cepat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                    <tr>
                        <td>
                            <span class="fw-medium">{{ $record->attendance_date?->format('Y-m-d') }}</span>
                        </td>
                        <td>
                            <span class="fw-medium">{{ $record->jadwalKbm?->studyGroup?->name ?? '-' }}</span>
                            <br><small class="text-muted">{{ $record->jadwalKbm?->studyGroup?->gradeLevel?->name ?? '' }}</small>
                        </td>
                        <td>
                            <small>{{ $record->jadwalKbm?->subject?->name ?? '-' }}</small>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $record->scheduled_start_time ?? '-' }} ~ {{ $record->scheduled_end_time ?? '-' }}
                            </small>
                        </td>
                        <td>
                            @if($record->actual_time_in)
                                <span class="text-success fw-medium">{{ $record->actual_time_in }}</span>
                                <br>
                                <span class="badge {{ $record->status_masuk === 'terlambat' ? 'bg-warning text-dark' : 'bg-success' }} badge-status">
                                    {{ $record->status_masuk === 'terlambat' ? 'Terlambat' : 'Tepat Waktu' }}
                                </span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if($record->actual_time_out)
                                <span class="text-primary fw-medium">{{ $record->actual_time_out }}</span>
                                <br>
                                <span class="badge bg-info badge-status">
                                    {{ $record->status_keluar === 'keluar_cepat' ? 'Pulang Cepat' : 'Sudah Keluar' }}
                                </span>
                            @elseif($record->actual_time_in)
                                <span class="badge bg-warning text-dark badge-status">Belum Keluar</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if($record->late_minutes > 0)
                                <span class="badge bg-warning text-dark">+{{ $record->late_minutes }} menit</span>
                            @elseif($record->early_leave_minutes > 0)
                                <span class="badge bg-danger">-{{ $record->early_leave_minutes }} menit</span>
                            @else
                                <span class="text-muted small">Tepat Waktu</span>
                            @endif
                            @if($record->duration_minutes)
                                <br><small class="text-muted">{{ $record->duration_minutes }} menit</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ri-inbox-line fs-1 mb-2 d-block"></i>
                            Belum ada data absensi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $records->links() }}
    </div>
</div>

@endsection
