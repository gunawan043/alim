{{-- Teacher attendance history --}}
@extends('layouts.master')
@section('title') Riwayat Absensi @endsection

@push('css')
<style>
    .stat-card { border: none; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .stat-card .card-body { padding: .85rem 1rem; }
    .stat-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .stat-value { font-size: 1.35rem; font-weight: 700; line-height: 1.2; }
    .stat-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .5px; margin: 0; }
    .table-freeze { table-layout: auto; min-width: 900px; width: 100%; margin-bottom: 0; }
    .table-freeze th, .table-freeze td { vertical-align: middle; padding: 10px 14px; word-break: break-word; }
    .table-freeze thead th { position: sticky; top: 0; z-index: 20; font-weight: 600; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .table-freeze tbody tr:hover td { background: #f8fafc; }
    .table-freeze th:first-child, .table-freeze td:first-child { position: sticky; left: 0; z-index: 100; min-width: 120px; box-shadow: 2px 0 4px rgba(0,0,0,.06); background: inherit; }
    .table-freeze thead th:first-child { background: #f8fafc; }
    .filter-card { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 10px; }
    .date-chip { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border: 1px solid #e2e8f0; border-radius: 20px; font-size: .78rem; cursor: pointer; transition: all .15s; background: #fff; color: #64748b; text-decoration: none; }
    .date-chip:hover, .date-chip.active { background: #6366f1; border-color: #6366f1; color: #fff; }
    .teacher-avatar { width: 28px; height: 28px; border-radius: 50%; background: #6366f118; display: inline-flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 700; color: #6366f1; flex-shrink: 0; }
</style>
@endpush

@section('content')
@php
    $userId = request()->route('userId') ?? auth()->id();
    $canViewAll = canPermission('teacher-attendance_report_export');
@endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Guru @endslot
    @slot('li_2') Riwayat Absensi @endslot
    @slot('title') Riwayat Absensi Guru @endslot
@endcomponent

{{-- Date quick chips --}}
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ request()->fullUrlWithQuery(['start_date' => today()->startOfWeek()->format('Y-m-d'), 'end_date' => today()->endOfWeek()->format('Y-m-d'), 'status' => null]) }}" class="date-chip {{ !request('start_date') ? 'active' : '' }}">
        <i class="ri-calendar-event-line"></i> Minggu Ini
    </a>
    <a href="{{ request()->fullUrlWithQuery(['start_date' => today()->startOfMonth()->format('Y-m-d'), 'end_date' => today()->format('Y-m-d'), 'status' => null]) }}" class="date-chip {{ request('start_date') === today()->startOfMonth()->format('Y-m-d') ? 'active' : '' }}">
        <i class="ri-calendar-line"></i> Bulan Ini
    </a>
    <a href="{{ request()->fullUrlWithQuery(['start_date' => today()->subDays(7)->format('Y-m-d'), 'end_date' => today()->format('Y-m-d'), 'status' => null]) }}" class="date-chip {{ request('start_date') === today()->subDays(7)->format('Y-m-d') ? 'active' : '' }}">
        <i class="ri-time-line"></i> 7 Hari Terakhir
    </a>
</div>

<div class="card filter-card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted">Mulai</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date', today()->startOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
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
                <button type="submit" class="btn btn-primary btn-sm"><i class="ri-search-line me-1"></i>Filter</button>
                <a href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-refresh-line"></i></a>
                <a href="{{ route('user.teacher-qr.history.export', ['userId' => $userId, 'start_date' => request('start_date', today()->startOfMonth()->format('Y-m-d')), 'end_date' => request('end_date', today()->format('Y-m-d'))]) }}" class="btn btn-success btn-sm" title="Export Excel">
                    <i class="ri-file-excel-2-line me-1"></i>Export
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Stats --}}
@php
    $total = $records->count();
    $hadir = $records->where('status_masuk', 'hadir')->count();
    $terlambat = $records->where('status_masuk', 'terlambat')->count();
    $belumKeluar = $records->where('status_keluar', 'belum_keluar')->count();
    $rataLate = $total > 0 ? round($records->avg('late_minutes')) : 0;
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#eff6ff;color:#3b82f6"><i class="ri-file-list-3-line"></i></div>
                <div><p class="stat-label text-muted mb-1">Total Record</p><h4 class="stat-value mb-0 text-primary">{{ $total }}</h4></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#f0fdf4;color:#22c55e"><i class="ri-checkbox-circle-fill"></i></div>
                <div><p class="stat-label text-muted mb-1">Tepat Waktu</p><h4 class="stat-value mb-0 text-success">{{ $hadir }}</h4></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fffbeb;color:#f59e0b"><i class="ri-alert-fill"></i></div>
                <div><p class="stat-label text-muted mb-1">Terlambat</p><h4 class="stat-value mb-0 text-warning">{{ $terlambat }}</h4></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fef2f2;color:#ef4444"><i class="ri-time-fill"></i></div>
                <div><p class="stat-label text-muted mb-1">Belum Keluar</p><h4 class="stat-value mb-0" style="color:#ef4444">{{ $belumKeluar }}</h4></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#f8fafc;color:#64748b"><i class="ri-repeat-clock-line"></i></div>
                <div><p class="stat-label text-muted mb-1">Rata-ratta Terlambat</p><h4 class="stat-value mb-0 text-muted">{{ $rataLate }} mnt</h4></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
        <div class="d-flex align-items-center gap-2">
            <div style="width:32px;height:32px;background:#6366f118;border-radius:6px;display:flex;align-items:center;justify-content:center">
                <i class="ri-file-list-3-line" style="color:#6366f1;font-size:.9rem"></i>
            </div>
            <h6 class="mb-0 fw-bold">Data Absensi</h6>
        </div>
        <span class="badge rounded-pill" style="background:#eff6ff;color:#6366f1;font-size:.72rem">{{ $records->count() }} record</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-freeze mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        @if($canViewAll)<th>Guru</th>@endif
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th>Jadwal</th>
                        <th>Masuk</th>
                        <th>Keluar</th>
                        <th>Status</th>
                        <th>Ket</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                    <tr>
                        <td>
                            <span class="fw-medium" style="font-size:.8rem">{{ $record->attendance_date?->format('d M Y') }}</span>
                            <br><small class="text-muted" style="font-size:.7rem">{{ $record->attendance_date?->format('l') }}</small>
                        </td>
                        @if($canViewAll)
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="teacher-avatar">{{ substr($record->jadwalKbm?->teacher?->name ?? $record->teacher?->name ?? '-', 0, 1) }}</span>
                                <span style="font-size:.8rem">{{ $record->jadwalKbm?->teacher?->name ?? $record->teacher?->name ?? '-' }}</span>
                            </div>
                        </td>
                        @endif
                        <td>
                            <span class="fw-medium" style="font-size:.8rem">{{ $record->jadwalKbm?->studyGroup?->name ?? '-' }}</span>
                            <br><small class="text-muted" style="font-size:.7rem">{{ $record->jadwalKbm?->studyGroup?->gradeLevel?->name ?? '' }}</small>
                        </td>
                        <td><small class="text-muted">{{ $record->jadwalKbm?->subject?->name ?? '-' }}</small></td>
                        <td>
                            <small class="text-muted" style="font-size:.75rem;font-family:monospace">
                                {{ $record->scheduled_start_time ?? '-' }}<br>{{ $record->scheduled_end_time ?? '-' }}
                            </small>
                        </td>
                        <td>
                            @if($record->actual_time_in)
                                <span class="fw-medium text-success" style="font-size:.82rem;font-family:monospace">{{ \Carbon\Carbon::parse($record->actual_time_in)->format('H:i') }}</span>
                                <br>
                                <span class="badge {{ $record->status_masuk === 'terlambat' ? 'bg-warning text-dark' : 'bg-success' }}" style="font-size:.68rem">{{ $record->status_masuk === 'terlambat' ? 'Terlambat' : 'Hadir' }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if($record->actual_time_out)
                                <span class="fw-medium text-primary" style="font-size:.82rem;font-family:monospace">{{ \Carbon\Carbon::parse($record->actual_time_out)->format('H:i') }}</span>
                                <br>
                                <span class="badge bg-info" style="font-size:.68rem">{{ $record->status_keluar === 'keluar_cepat' ? 'Pulang Cepat' : 'Selesai' }}</span>
                            @elseif($record->actual_time_in)
                                <span class="badge bg-warning text-dark" style="font-size:.68rem">Belum Keluar</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if($record->late_minutes > 0)
                                <span class="badge bg-warning text-dark">+{{ $record->late_minutes }}m</span>
                            @elseif($record->early_leave_minutes > 0)
                                <span class="badge bg-danger">-{{ $record->early_leave_minutes }}m</span>
                            @else
                                <span class="text-muted small">Tepat waktu</span>
                            @endif
                        </td>
                        <td>
                            @if($record->notes)
                                <span class="text-muted small" title="{{ $record->notes }}"><i class="ri-sticky-note-line"></i></span>
                            @endif
                            @if($record->duration_minutes)
                                <br><small class="text-muted" style="font-size:.72rem">{{ $record->duration_minutes }} menit</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canViewAll ? 9 : 8 }}" class="text-center py-5 text-muted">
                            <i class="ri-inbox-line" style="font-size:2rem;color:#cbd5e1"></i>
                            <p class="mt-2 mb-0">Belum ada data absensi pada periode ini</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
