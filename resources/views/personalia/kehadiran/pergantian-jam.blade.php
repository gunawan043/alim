{{-- Rekap Kehadiran Pergantian Jam: jadwal seharusnya vs scan QR masuk/keluar --}}
@extends('layouts.master')

@section('title', 'Rekap Pergantian Jam')

@push('css')
<style>
    .stat-card { border: none; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); transition: transform .2s, box-shadow .2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.09); }
    .stat-value { font-size: 1.3rem; font-weight: 700; line-height: 1.2; }
    .stat-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .5px; margin: 0; }
    .stat-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .table-freeze { min-width: 1080px; width: 100%; margin-bottom: 0; }
    .table-freeze th, .table-freeze td { vertical-align: middle; padding: 9px 10px; }
    .table-freeze thead th { position: sticky; top: 0; z-index: 5; font-weight: 600; background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-size: .78rem; }
    .time-cell { font-family: 'SF Mono', Monaco, monospace; font-size: .78rem; white-space: nowrap; }
    .badge-status { font-size: .72rem; padding: .35em .65em; }
    @media print { .no-print { display: none !important; } .table-freeze { min-width: 0; } }
</style>
@endpush

@section('content')
@php
    $userId = $userId ?? auth()->id();
    $statusColors = ['tepat' => 'success', 'terlambat' => 'warning', 'keluar_cepat' => 'info', 'belum_keluar' => 'secondary', 'tidak_hadir' => 'danger'];
@endphp

@component('components.breadcrumb')
    @slot('li_1') Kehadiran @endslot
    @slot('li_2') Pergantian Jam @endslot
    @slot('title') Rekap Kehadiran Pergantian Jam @endslot
@endcomponent

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <p class="text-muted small mb-0">
        Jadwal mengajar vs scan QR masuk/keluar kelas
        @if($activeAy) • {{ $activeAy->name }} @endif
    </p>
    <div class="d-flex gap-2 no-print">
        <a href="{{ route('user.kehadiran.pergantian-jam.export', array_merge(['userId' => $userId], request()->query())) }}"
           class="btn btn-success btn-sm">
            <i class="ri-file-excel-2-line me-1"></i>Export Excel
        </a>
        <button onclick="window.print()" class="btn btn-light btn-sm">
            <i class="ri-printer-line me-1"></i>Print
        </button>
    </div>
</div>

{{-- Statistik ringkas --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <span class="stat-icon bg-primary-subtle text-primary"><i class="ri-calendar-check-line"></i></span>
                <div>
                    <p class="stat-label text-muted">Total Jadwal</p>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <span class="stat-icon bg-success-subtle text-success"><i class="ri-checkbox-circle-line"></i></span>
                <div>
                    <p class="stat-label text-muted">Tepat</p>
                    <div class="stat-value">{{ $stats['tepat'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <span class="stat-icon bg-warning-subtle text-warning"><i class="ri-time-line"></i></span>
                <div>
                    <p class="stat-label text-muted">Terlambat</p>
                    <div class="stat-value">{{ $stats['terlambat'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <span class="stat-icon bg-info-subtle text-info"><i class="ri-logout-box-r-line"></i></span>
                <div>
                    <p class="stat-label text-muted">Keluar Cepat</p>
                    <div class="stat-value">{{ $stats['keluar_cepat'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <span class="stat-icon bg-danger-subtle text-danger"><i class="ri-user-unfollow-line"></i></span>
                <div>
                    <p class="stat-label text-muted">Tidak Hadir</p>
                    <div class="stat-value">{{ $stats['tidak_hadir'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <span class="stat-icon bg-secondary-subtle text-secondary"><i class="ri-hourglass-line"></i></span>
                <div>
                    <p class="stat-label text-muted">Belum Keluar</p>
                    <div class="stat-value">{{ $stats['belum_keluar'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="card border mb-3 no-print">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('user.kehadiran.pergantian-jam', ['userId' => $userId]) }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label mb-0 small">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $start }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0 small">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $end }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0 small">Guru</label>
                <select name="teacher_id" class="form-select form-select-sm">
                    <option value="">Semua Guru</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" {{ $filters['teacher_id'] === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0 small">Rombel</label>
                <select name="study_group_id" class="form-select form-select-sm">
                    <option value="">Semua Rombel</option>
                    @foreach($studyGroups as $group)
                        <option value="{{ $group->id }}" {{ $filters['study_group_id'] === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0 small">Mata Pelajaran</label>
                <select name="subject_id" class="form-select form-select-sm">
                    <option value="">Semua Mapel</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ $filters['subject_id'] === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label mb-0 small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($statusLabels as $key => $label)
                        <option value="{{ $key }}" {{ $filters['status'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100" title="Filter"><i class="ri-filter-3-line"></i></button>
                <a href="{{ route('user.kehadiran.pergantian-jam', ['userId' => $userId]) }}" class="btn btn-light btn-sm" title="Reset"><i class="ri-reset-right-line"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- Tabel --}}
<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h6 class="card-title mb-0"><i class="ri-table-2 text-primary me-1"></i>Jadwal vs Kehadiran Aktual</h6>
        <span class="small text-muted">
            Periode {{ \Illuminate\Support\Carbon::parse($start)->translatedFormat('d M Y') }} — {{ \Illuminate\Support\Carbon::parse($end)->translatedFormat('d M Y') }}
            • Kehadiran {{ $stats['persen_kehadiran'] }}%
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-freeze">
            <thead>
                <tr>
                    <th class="text-center" style="width:44px">No</th>
                    <th>Tanggal</th>
                    <th>Guru</th>
                    <th>Rombel</th>
                    <th>Mata Pelajaran</th>
                    <th class="text-center">Jam Jadwal</th>
                    <th class="text-center">Scan Masuk</th>
                    <th class="text-center">Scan Keluar</th>
                    <th class="text-center">Terlambat</th>
                    <th class="text-center">Keluar Cepat</th>
                    <th class="text-center">Durasi</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <div class="fw-medium">{{ $row['date']->translatedFormat('d M Y') }}</div>
                            <small class="text-muted">{{ $row['day_name'] }}</small>
                        </td>
                        <td>{{ $row['teacher'] }}</td>
                        <td>{{ $row['study_group'] }}</td>
                        <td>{{ $row['subject'] }}</td>
                        <td class="text-center time-cell">
                            {{ $row['scheduled_start'] ? substr($row['scheduled_start'], 0, 5) : '—' }}–{{ $row['scheduled_end'] ? substr($row['scheduled_end'], 0, 5) : '—' }}
                        </td>
                        <td class="text-center time-cell">{{ $row['scan_in']?->format('H:i') ?? '—' }}</td>
                        <td class="text-center time-cell">{{ $row['scan_out']?->format('H:i') ?? '—' }}</td>
                        <td class="text-center">
                            @if($row['late_minutes'] > 0)
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle badge-status">{{ $row['late_minutes'] }} mnt</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($row['early_minutes'] > 0)
                                <span class="badge bg-info-subtle text-info border border-info-subtle badge-status">{{ $row['early_minutes'] }} mnt</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $row['duration'] > 0 ? $row['duration'].' mnt' : '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $statusColors[$row['status']] ?? 'secondary' }}-subtle text-{{ $statusColors[$row['status']] ?? 'secondary' }} border border-{{ $statusColors[$row['status']] ?? 'secondary' }}-subtle badge-status">
                                {{ $row['status_label'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="ri-calendar-close-line" style="font-size:2.5rem;opacity:.4"></i></div>
                            <h6 class="fw-semibold">Belum ada data pada periode/filter ini</h6>
                            <p class="text-muted small mb-0">Rekap dihitung dari jadwal KBM aktif dibandingkan absensi QR guru.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-2">
        <span class="small text-muted">
            <i class="ri-information-line me-1"></i>
            "Tidak Hadir" dihitung dari jadwal aktif yang tidak memiliki record absensi QR pada tanggal tersebut.
            Rentang maksimum {{ 92 }} hari per rekap.
        </span>
    </div>
</div>
@endsection
