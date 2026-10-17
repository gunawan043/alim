{{-- Riwayat absensi guru — pola UI mengikuti gtk/index --}}
@extends('layouts.master')
@section('title') Riwayat Absensi @endsection

@section('css')
<style>
    .stat-card { border: none; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); transition: transform .2s; }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-card .card-body { padding: .85rem 1rem; }
    .stat-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .stat-value { font-size: 1.4rem; font-weight: 700; line-height: 1.2; }
    .stat-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .5px; margin: 0; color: #94a3b8; }

    .table-container { position: relative; width: 100%; overflow-x: auto; }
    .table-freeze { table-layout: auto; min-width: 940px; width: 100%; margin-bottom: 0; }
    .table-freeze th, .table-freeze td { vertical-align: middle; padding: 11px 14px; word-break: break-word; }
    .table-freeze thead th { position: sticky; top: 0; z-index: 20; font-weight: 600; background: #f8fafc; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
    .table-freeze tbody tr:hover td { background: #f8fafc; }

    .filter-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 30px; font-size: 12px; transition: all .2s; cursor: pointer; background: #fff; color: #64748b; text-decoration: none; }
    .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
    .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }

    .teacher-avatar { width: 28px; height: 28px; border-radius: 50%; background: #6366f118; display: inline-flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 700; color: #6366f1; flex-shrink: 0; }
    .time-mono { font-family: 'SF Mono', Monaco, monospace; font-size: .8rem; }
</style>
@endsection

@section('content')
@php
    $userId = request()->route('userId') ?? auth()->id();
    $canViewAll = $hasFullReportExport ?? canPermission('teacher-attendance_report_export');
    $exportUrl = route('user.teacher-qr.history.export', array_filter([
        'userId' => $userId,
        'start_date' => $start,
        'end_date' => $end,
        'status' => $statusFilter,
        'q' => $search ?? null,
    ], fn ($v) => $v !== null && $v !== ''));
@endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Kehadiran @endslot
    @slot('li_2') Scan QR @endslot
    @slot('title') Riwayat Absensi Guru @endslot
@endcomponent

{{-- Header halaman --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h5 class="mb-1"><i class="ri-history-line me-2 text-primary"></i>Riwayat Absensi Guru</h5>
        <p class="text-muted small mb-0">Rekap check-in dan check-out scan QR per jadwal mengajar.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ $exportUrl }}" class="btn btn-success btn-sm">
            <i class="ri-file-excel-2-line me-1"></i>Export Excel
        </a>
        <a href="{{ route('user.teacher-qr.scan', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
            <i class="ri-qr-scan-2-line me-1"></i>Scan QR
        </a>
    </div>
</div>

{{-- Statistik --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#eff6ff;color:#3b82f6"><i class="ri-file-list-3-line"></i></div>
                <div>
                    <p class="stat-label mb-1">Total Record</p>
                    <h4 class="stat-value mb-0 text-primary">{{ number_format($stats['total'] ?? 0) }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#f0fdf4;color:#22c55e"><i class="ri-checkbox-circle-fill"></i></div>
                <div>
                    <p class="stat-label mb-1">Tepat Waktu</p>
                    <h4 class="stat-value mb-0 text-success">{{ number_format($stats['hadir'] ?? 0) }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fffbeb;color:#f59e0b"><i class="ri-alert-fill"></i></div>
                <div>
                    <p class="stat-label mb-1">Terlambat</p>
                    <h4 class="stat-value mb-0 text-warning">{{ number_format($stats['terlambat'] ?? 0) }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fef2f2;color:#ef4444"><i class="ri-time-fill"></i></div>
                <div>
                    <p class="stat-label mb-1">Belum Keluar</p>
                    <h4 class="stat-value mb-0" style="color:#ef4444">{{ number_format($stats['belum_keluar'] ?? 0) }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#f8fafc;color:#64748b"><i class="ri-repeat-clock-line"></i></div>
                <div>
                    <p class="stat-label mb-1">Rata-rata Terlambat</p>
                    <h4 class="stat-value mb-0 text-muted">{{ number_format($stats['avg_late'] ?? 0) }} <span class="fs-6">mnt</span></h4>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-filter-3-line me-1 text-primary"></i>Filter Riwayat</h5>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ request()->fullUrlWithQuery(['start_date' => today()->startOfWeek()->format('Y-m-d'), 'end_date' => today()->endOfWeek()->format('Y-m-d'), 'status' => null, 'q' => null]) }}"
               class="filter-badge {{ $start === today()->startOfWeek()->format('Y-m-d') ? 'active' : '' }}">
                <i class="ri-calendar-event-line"></i>Minggu Ini
            </a>
            <a href="{{ request()->fullUrlWithQuery(['start_date' => today()->startOfMonth()->format('Y-m-d'), 'end_date' => today()->format('Y-m-d'), 'status' => null, 'q' => null]) }}"
               class="filter-badge {{ $start === today()->startOfMonth()->format('Y-m-d') && $end === today()->format('Y-m-d') ? 'active' : '' }}">
                <i class="ri-calendar-line"></i>Bulan Ini
            </a>
            <a href="{{ request()->fullUrlWithQuery(['start_date' => today()->subDays(6)->format('Y-m-d'), 'end_date' => today()->format('Y-m-d'), 'status' => null, 'q' => null]) }}"
               class="filter-badge {{ $start === today()->subDays(6)->format('Y-m-d') && $end === today()->format('Y-m-d') ? 'active' : '' }}">
                <i class="ri-time-line"></i>7 Hari Terakhir
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Mulai</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Sampai</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="hadir" {{ $statusFilter === 'hadir' ? 'selected' : '' }}>Tepat Waktu</option>
                    <option value="terlambat" {{ $statusFilter === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="belum_keluar" {{ $statusFilter === 'belum_keluar' ? 'selected' : '' }}>Belum Keluar</option>
                    <option value="keluar_cepat" {{ $statusFilter === 'keluar_cepat' ? 'selected' : '' }}>Pulang Cepat</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ $search ?? '' }}"
                       placeholder="{{ $canViewAll ? 'Nama guru / kelas / mapel…' : 'Kelas / mapel…' }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="ri-search-line me-1"></i>Terapkan
                </button>
                <a href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" class="btn btn-light btn-sm" title="Reset">
                    <i class="ri-refresh-line"></i>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Tabel --}}
<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-table-2 me-1 text-primary"></i>Data Absensi</h5>
        <span class="badge bg-primary-subtle text-primary">
            {{ number_format($stats['total'] ?? 0) }} record
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table table-hover align-middle table-freeze mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px" class="text-center">No</th>
                        <th>Tanggal</th>
                        @if($canViewAll)<th>Guru</th>@endif
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th>Jadwal</th>
                        <th>Masuk</th>
                        <th>Keluar</th>
                        <th>Keterlambatan</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td class="text-center">{{ ($records->firstItem() ?? 1) + $loop->index }}</td>
                            <td>
                                <span class="fw-medium small">{{ $record->attendance_date?->translatedFormat('d M Y') }}</span>
                                <div class="small text-muted">{{ $record->attendance_date?->translatedFormat('l') }}</div>
                            </td>
                            @if($canViewAll)
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="teacher-avatar">{{ strtoupper(substr($record->teacher?->name ?? $record->jadwalKbm?->teacher?->name ?? '-', 0, 1)) }}</span>
                                        <span class="small">{{ $record->teacher?->name ?? $record->jadwalKbm?->teacher?->name ?? '-' }}</span>
                                    </div>
                                </td>
                            @endif
                            <td>
                                <span class="fw-medium small">{{ $record->jadwalKbm?->studyGroup?->name ?? '-' }}</span>
                                <div class="small text-muted">{{ $record->jadwalKbm?->studyGroup?->gradeLevel?->name ?? '' }}</div>
                            </td>
                            <td class="small text-muted">{{ $record->jadwalKbm?->subject?->name ?? '-' }}</td>
                            <td class="time-mono text-muted">
                                {{ $record->scheduled_start_time ? substr($record->scheduled_start_time, 0, 5) : '-' }}
                                <span class="text-muted">–</span>
                                {{ $record->scheduled_end_time ? substr($record->scheduled_end_time, 0, 5) : '-' }}
                            </td>
                            <td>
                                @if($record->actual_time_in)
                                    <span class="fw-medium text-success time-mono">{{ \Carbon\Carbon::parse($record->actual_time_in)->format('H:i') }}</span>
                                    <div>
                                        <span class="badge bg-{{ $record->status_masuk === 'terlambat' ? 'warning' : 'success' }}-subtle text-{{ $record->status_masuk === 'terlambat' ? 'warning' : 'success' }}">
                                            {{ $record->status_masuk === 'terlambat' ? 'Terlambat' : 'Hadir' }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->actual_time_out)
                                    <span class="fw-medium text-primary time-mono">{{ \Carbon\Carbon::parse($record->actual_time_out)->format('H:i') }}</span>
                                    <div>
                                        <span class="badge bg-info-subtle text-info">
                                            {{ $record->status_keluar === 'keluar_cepat' ? 'Pulang Cepat' : 'Selesai' }}
                                        </span>
                                    </div>
                                @elseif($record->actual_time_in)
                                    <span class="badge bg-warning-subtle text-warning">Belum Keluar</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->late_minutes > 0)
                                    <span class="badge bg-warning-subtle text-warning">+{{ $record->late_minutes }} mnt</span>
                                @elseif($record->early_leave_minutes > 0)
                                    <span class="badge bg-danger-subtle text-danger">-{{ $record->early_leave_minutes }} mnt</span>
                                @else
                                    <span class="text-muted small">Tepat waktu</span>
                                @endif
                            </td>
                            <td>
                                @if($record->notes)
                                    <span class="text-muted small" title="{{ $record->notes }}"><i class="ri-sticky-note-line me-1"></i>{{ \Illuminate\Support\Str::limit($record->notes, 40) }}</span>
                                @endif
                                @if($record->duration_minutes)
                                    <div class="small text-muted">{{ $record->duration_minutes }} menit</div>
                                @endif
                                @if(! $record->notes && ! $record->duration_minutes)
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="{{ $canViewAll ? 10 : 9 }}"
                            title="Belum ada riwayat absensi"
                            message="Tidak ada data pada periode atau filter yang dipilih.">
                            <a href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" class="btn btn-light mt-3">
                                <i class="ri-refresh-line me-1"></i>Reset Filter
                            </a>
                        </x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($records->hasPages())
        <div class="card-footer">
            @include('shared._pagination', ['paginator' => $records])
        </div>
    @endif
</div>
@endsection
