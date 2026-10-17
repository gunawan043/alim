{{-- Absensi GTK: Kehadiran Harian --}}
@extends('layouts.master')
@section('title') Kehadiran Harian GTK @endsection

@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.page-header-card{background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%);border:1px solid #6ee7b7;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#022c22 0%,#064e3b 100%);border-color:#059669}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Kehadiran GTK @endslot
    @slot('li_2') Kehadiran Harian @endslot
    @slot('title') Kehadiran Harian GTK @endslot
@endcomponent

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#22c55e18;color:#16a34a;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-calendar-check-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Kehadiran Harian</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Catatan kehadiran GTK per hari</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="{{ route('user.absensi-gtk.rekap-bulanan', $userId) }}" class="btn btn-light btn-sm"><i class="ri-file-chart-line me-1"></i>Rekap Bulanan</a>
        <a href="{{ route('user.absensi-gtk.izin', $userId) }}" class="btn btn-light btn-sm"><i class="ri-file-list-2-line me-1"></i>Izin/Sakit</a>
        <a href="{{ route('user.absensi-gtk.index', $userId) }}" class="btn btn-outline-primary btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #16a34a">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded-3 fs-2"><i class="ri-checkbox-circle-line text-success"></i></span></div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px">Hadir</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $absensis->where('status','hadir')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #d97706">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded-3 fs-2"><i class="ri-error-warning-line text-warning"></i></span></div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px">Alpa</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $absensis->where('status','alpa')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #0891b2">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded-3 fs-2"><i class="ri-information-line text-info"></i></span></div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px">Izin</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $absensis->where('status','izin')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #7c3aed">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-purple-subtle rounded-3 fs-2"><i class="ri-hospital-line text-purple"></i></span></div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px">Sakit</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $absensis->where('status','sakit')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('user.absensi-gtk.harian', $userId) }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-0" style="font-size:.8rem">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-select-sm" value="{{ $tanggal ?? now()->toDateString() }}">
            </div>
            <div class="col-md-4">
                <label class="form-label mb-0" style="font-size:.8rem">GTK</label>
                <select name="gtk_id" class="form-select form-select-sm">
                    <option value="">Semua GTK</option>
                    @foreach($gtkList as $gtk)
                        <option value="{{ $gtk->id }}">{{ $gtk->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
                <a href="{{ route('user.absensi-gtk.harian', $userId) }}" class="btn btn-light btn-sm"><i class="ri-reset-right-line me-1"></i>Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-calendar-check-line text-success me-1"></i> Kehadiran Hari Ini — {{ \Carbon\Carbon::parse($tanggal)->format('d F Y') }}</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th class="bg-light" style="width:48px">No</th>
                    <th class="bg-light">GTK</th>
                    <th class="bg-light text-center">Jam Masuk</th>
                    <th class="bg-light text-center">Jam Pulang</th>
                    <th class="bg-light text-center">Terlambat</th>
                    <th class="bg-light text-center">Pulang Awal</th>
                    <th class="bg-light text-center">Status</th>
                    <th class="bg-light">Keterangan</th>
                    <th class="bg-light text-center" style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($absensis as $absensi)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($absensi->gtk?->nama ?? 'G', 0, 1)) }}
                                </div>
                                <span class="fw-medium">{{ $absensi->gtk?->nama ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="text-center">{{ $absensi->jam_masuk ? \Carbon\Carbon::parse($absensi->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="text-center">{{ $absensi->jam_pulang ? \Carbon\Carbon::parse($absensi->jam_pulang)->format('H:i') : '-' }}</td>
                        <td class="text-center">
                            @if($absensi->terlambat_menit > 0)
                                <span class="badge bg-danger-subtle text-danger">{{ $absensi->terlambat_menit }} mnt</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($absensi->pulang_awal_menit > 0)
                                <span class="badge bg-warning-subtle text-warning">{{ $absensi->pulang_awal_menit }} mnt</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
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
                        </td>
                        <td><span class="small text-muted">{{ $absensi->keterangan ?? ($absensi->lokasi_masuk ?? '-') }}</span></td>
                        <td class="text-center">
                            <a href="#" class="btn btn-sm btn-light" title="Edit"><i class="ri-edit-line"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div style="color:#22c55e;opacity:.4"><i class="ri-calendar-check-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Belum ada data</h5>
                            <p class="text-muted mb-0 small">Data kehadiran GTK akan muncul di sini</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
