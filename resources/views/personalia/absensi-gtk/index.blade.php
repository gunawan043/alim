{{-- Absensi GTK: Rekap Kehadiran --}}
@extends('layouts.master')
@section('title') Rekap Absensi GTK @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
@php
    $userId = request()->route('userId') ?? auth()->id();
    $totalGtk = $stats['total_gtk'] ?? 0;
    $totalBaris = $stats['total'] ?? 0;
    $hadir = $stats['hadir'] ?? 0;
    $terlambat = $stats['terlambat'] ?? 0;
    $tidakHadir = $stats['tidak_hadir'] ?? 0;
    $sakit = $stats['sakit'] ?? 0;
    $izin = $stats['izin'] ?? 0;
    $alpa = $stats['alpa'] ?? 0;
    $pctHadir = $totalBaris > 0 ? round($hadir / $totalBaris * 100) : 0;
@endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi GTK @endslot
    @slot('li_2') Rekap Kehadiran @endslot
    @slot('title') Rekap Kehadiran GTK @endslot
@endcomponent

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-1">Rekap Absensi GTK</h4>
        <p class="text-muted mb-0 small">Ringkasan kehadiran GTK dari data absensi yang tercatat.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.absensi-gtk.harian', $userId) }}" class="btn btn-light btn-sm"><i class="ri-calendar-check-line me-1"></i>Kehadiran Harian</a>
        <a href="{{ route('user.absensi-gtk.settings', $userId) }}" class="btn btn-light btn-sm"><i class="ri-settings-4-line me-1"></i>Pengaturan</a>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-group-line text-primary"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total GTK</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalGtk) }}</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label">
                    <i class="ri-file-list-3-line me-1"></i>{{ number_format($totalBaris) }} baris absensi (terfilter)
                </p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Hadir</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($hadir) }}</h3>
                    </div>
                </div>
                <div class="progress mt-1" style="height:6px;">
                    <div class="progress-bar bg-success" style="width:{{ $pctHadir }}%"></div>
                </div>
                <p class="text-muted mb-0 stat-label mt-1"><i class="ri-information-line me-1"></i>{{ $pctHadir }}% dari baris absensi</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-timer-line text-warning"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Terlambat</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($terlambat) }}</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Hadir dengan menit terlambat &gt; 0</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-user-unfollow-line text-danger"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Tidak Hadir</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($tidakHadir) }}</h3>
                    </div>
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    <span class="badge bg-warning-subtle text-warning stat-label">Sakit {{ number_format($sakit) }}</span>
                    <span class="badge bg-info-subtle text-info stat-label">Izin {{ number_format($izin) }}</span>
                    <span class="badge bg-danger-subtle text-danger stat-label">Alpa {{ number_format($alpa) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('user.absensi-gtk.index', $userId) }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-0" style="font-size:.8rem">GTK</label>
                <select name="gtk_id" class="form-select form-select-sm">
                    <option value="">Semua GTK</option>
                    @foreach($gtkList ?? [] as $gtk)
                        <option value="{{ $gtk->id }}" {{ request('gtk_id') == $gtk->id ? 'selected' : '' }}>{{ $gtk->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0" style="font-size:.8rem">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="hadir" {{ request('status') === 'hadir' ? 'selected' : '' }}>Hadir</option>
                    <option value="sakit" {{ request('status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="izin" {{ request('status') === 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="alpa" {{ request('status') === 'alpa' ? 'selected' : '' }}>Alpa</option>
                    <option value="cuti" {{ request('status') === 'cuti' ? 'selected' : '' }}>Cuti</option>
                    <option value="dinas_luar" {{ request('status') === 'dinas_luar' ? 'selected' : '' }}>Dinas Luar</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0" style="font-size:.8rem">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ request('tanggal') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
                <a href="{{ route('user.absensi-gtk.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-reset-right-line me-1"></i>Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-file-list-3-line text-success me-1"></i> Daftar Absensi GTK</h5>
        <span class="badge bg-primary-subtle text-primary">{{ number_format($totalBaris) }} baris</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width:48px">No</th>
                    <th>GTK</th>
                    <th>Tanggal</th>
                    <th class="text-center">Jam Masuk</th>
                    <th class="text-center">Jam Pulang</th>
                    <th class="text-center">Status</th>
                    <th>Keterangan</th>
                    <th class="text-center" style="width:80px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($absensis ?? [] as $absensi)
                    <tr>
                        <td class="text-center">{{ ($absensis->currentPage() - 1) * $absensis->perPage() + $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($absensi->gtk?->nama ?? 'G', 0, 1)) }}
                                </div>
                                <span class="fw-medium">{{ $absensi->gtk?->nama ?? '-' }}</span>
                            </div>
                        </td>
                        <td>{{ $absensi->tanggal ? \Carbon\Carbon::parse($absensi->tanggal)->format('d M Y') : '-' }}</td>
                        <td class="text-center">{{ $absensi->jam_masuk ? \Carbon\Carbon::parse($absensi->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="text-center">{{ $absensi->jam_pulang ? \Carbon\Carbon::parse($absensi->jam_pulang)->format('H:i') : '-' }}</td>
                        <td class="text-center">
                            @php
                                $statusMap = [
                                    'hadir' => 'success', 'sakit' => 'warning', 'izin' => 'info',
                                    'alpa' => 'danger', 'cuti' => 'secondary', 'dinas_luar' => 'primary',
                                ];
                                $badgeColor = $statusMap[$absensi->status] ?? 'secondary';
                                $badgeLabel = ucwords(str_replace('_',' ', $absensi->status));
                            @endphp
                            <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }}">{{ $badgeLabel }}</span>
                            @if($absensi->status === 'hadir' && $absensi->terlambat_menit > 0)
                                <span class="badge bg-warning-subtle text-warning stat-label" title="Terlambat {{ $absensi->terlambat_menit }} menit">
                                    +{{ $absensi->terlambat_menit }}m
                                </span>
                            @endif
                        </td>
                        <td><span class="small text-muted">{{ $absensi->keterangan ?? ($absensi->lokasi_masuk ?? '-') }}</span></td>
                        <td class="text-center">
                            <a href="#" class="btn btn-sm btn-light" title="Detail"><i class="ri-eye-line"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                                Belum ada data absensi pada filter ini.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body pt-0">
        @include('shared._pagination', ['paginator' => $absensis])
    </div>
</div>
@endsection
