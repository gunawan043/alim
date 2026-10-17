{{-- Kontrak Kerja: Akan Berakhir --}}
@extends('layouts.master')
@section('title') Kontrak Akan Berakhir @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#fef9c3 0%,#fef08a 100%);border:1px solid #fde047;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1c1a00 0%,#1f1d00 100%);border-color:#78350f}
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#fffbeb}
[data-bs-theme="dark"] .table-freeze thead th{background:#1a1f3a}
[data-bs-theme="dark"] .table-freeze tbody tr:hover td{background:#1c1a00}
.info-card{background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);border:1px solid #fde68a;border-radius:.625rem;padding:1rem 1.25rem}
[data-bs-theme="dark"] .info-card{background:linear-gradient(135deg,#1c0e00 0%,#231400 100%);border-color:#78350f}
@media print{.no-print{display:none!important}}
.urgency-danger{color:#dc2626;background:#fee2e2}
.urgency-warning{color:#d97706;background:#fef3c7}
.urgency-safe{color:#2563eb;background:#dbeafe}
</style>
@endpush

@section('content')
@php
$userId = request()->route('userId') ?? Auth::id();
$currentUser = auth()->user();
$isAdmin = $currentUser && $currentUser->hasAnyRole(['Personalia','Super Admin']);
$urgentCount = $urgentCount ?? 0;
$warningCount = $warningCount ?? 0;
$safeCount = $safeCount ?? 0;
$kontraks = $kontraks ?? collect();
@endphp

{{-- Page header --}}
<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#dc262618;color:#dc2626;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-alert-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Kontrak Akan Berakhir</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Kontrak yang akan berakhir dalam 90 hari ke depan</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.ats.kontrak.index', $userId) }}" class="btn btn-outline-secondary btn-sm">
            <i class="ri-arrow-left-line me-1"></i>Daftar Kontrak
        </a>
        <a href="{{ route('user.ats.kontrak.create', $userId) }}" class="btn btn-primary btn-sm">
            <i class="ri-add-circle-line me-1"></i>Buat Baru
        </a>
    </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-0 border-0" role="tablist">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('user.ats.kontrak.index', $userId) }}">
            <i class="ri-file-paper-line me-1"></i>Daftar Kontrak
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('user.ats.kontrak.expiring', $userId) }}">
            <i class="ri-alert-line me-1"></i>Akan Berakhir
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('user.ats.kontrak.template', $userId) }}">
            <i class="ri-file-text-line me-1"></i>Template
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('user.ats.kontrak.settings', $userId) }}">
            <i class="ri-settings-3-line me-1"></i>Pengaturan
        </a>
    </li>
</ul>

{{-- Stats --}}
<div class="row g-3 mt-2 mb-3">
    <div class="col-sm-6 col-md-3">
        <div class="stat-card rounded-2 p-3 h-100 border border-2 border-danger">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title rounded-circle" style="background:#fee2e2;color:#dc2626">
                        <i class="ri-alert-line"></i>
                    </span>
                </div>
                <div>
                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:.7rem;letter-spacing:.05em"><=30 Hari</p>
                    <h3 class="fw-bold ff-secondary mb-0 text-danger">{{ $urgentCount }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card rounded-2 p-3 h-100 border border-2 border-warning">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title rounded-circle" style="background:#fef3c7;color:#d97706">
                        <i class="ri-time-line"></i>
                    </span>
                </div>
                <div>
                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:.7rem;letter-spacing:.05em">31-60 Hari</p>
                    <h3 class="fw-bold ff-secondary mb-0 text-warning">{{ $warningCount }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card rounded-2 p-3 h-100 border border-2 border-primary">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title rounded-circle" style="background:#dbeafe;color:#2563eb">
                        <i class="ri-checkbox-circle-line"></i>
                    </span>
                </div>
                <div>
                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:.7rem;letter-spacing:.05em">61-90 Hari</p>
                    <h3 class="fw-bold ff-secondary mb-0 text-primary">{{ $safeCount }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card rounded-2 p-3 h-100">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title rounded-circle" style="background:#f3e8ff;color:#7c3aed">
                        <i class="ri-file-paper-line"></i>
                    </span>
                </div>
                <div>
                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:.7rem;letter-spacing:.05em">Total Expiring</p>
                    <h3 class="fw-bold ff-secondary mb-0">{{ $kontraks->total() }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Info card --}}
<div class="info-card mb-3 no-print">
    <div class="d-flex align-items-start gap-2">
        <i class="ri-information-line text-warning fs-5 flex-shrink-0 mt-1"></i>
        <div>
            <p class="mb-1 fw-semibold" style="font-size:.85rem">Kontrak yang sudah berakhir tidak ditampilkan di sini.</p>
            <p class="mb-0 text-muted" style="font-size:.8rem">Segera lakukan perpanjangan kontrak untuk GTK yang akan berakhir agar operasional tetap berjalan.</p>
        </div>
    </div>
</div>

{{-- Card with table --}}
<div class="card border-danger border-2">
    <div class="card-header bg-transparent border-danger d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-alert-line me-1 text-danger"></i>Daftar Kontrak Expiring</h5>
        <span class="badge bg-danger">{{ $kontraks->total() }} kontrak</span>
    </div>
    <div class="card-body p-0">
        {{-- Filter bar --}}
        <div class="px-3 py-2 border-bottom bg-light">
            <div class="row g-2 align-items-center no-print">
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm" placeholder="Cari nama GTK...">
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm">
                        <option value="">Semua Urgensi</option>
                        <option> Kritis (<=30 Hari)</option>
                        <option>Menyusul (31-60 Hari)</option>
                        <option>Mendekati (61-90 Hari)</option>
                    </select>
                </div>
                <div class="col-md-auto d-flex gap-1">
                    <button class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
                    <button class="btn btn-light btn-sm"><i class="ri-reset-right-line me-1"></i>Reset</button>
                    <button class="btn btn-light btn-sm"><i class="ri-download-line me-1"></i>Export</button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze">
                <thead>
                    <tr>
                        <th>Nama GTK</th>
                        <th>Jenis</th>
                        <th>Tanggal Selesai</th>
                        <th>Sisa Hari</th>
                        <th>Status</th>
                        <th class="text-center no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
@forelse($kontraks as $kontrak)
    @php
        $daysLeft = $kontrak->tanggal_selesai ? now()->diffInDays($kontrak->tanggal_selesai, false) : 0;
        $urgency = $daysLeft <= 30 ? 'danger' : ($daysLeft <= 60 ? 'warning' : 'info');
        $urgencyClass = $daysLeft <= 30 ? 'urgency-danger' : ($daysLeft <= 60 ? 'urgency-warning' : 'urgency-safe');
    @endphp
    <tr>
        <td>
            <div class="fw-semibold">{{ $kontrak->gtk?->nama ?? '-' }}</div>
            <div class="small text-muted">{{ $kontrak->jabatan ?? '' }}</div>
        </td>
        <td><span class="badge badge-status bg-purple-subtle text-purple">{{ $kontrak->jenis_kontrak ?? '-' }}</span></td>
        <td>{{ $kontrak->tanggal_selesai ? $kontrak->tanggal_selesai->format('d M Y') : '-' }}</td>
        <td>
            <span class="badge badge-status {{ $urgencyClass }}">
                {{ $daysLeft }} hari
            </span>
        </td>
        <td><span class="badge badge-status {{ $urgencyClass }}">{{ $kontrak->status ?? '-' }}</span></td>
        <td class="text-center no-print">
            <div class="d-flex gap-1 justify-content-center">
                <a href="{{ route('user.ats.kontrak.edit', ['userId' => $userId, 'id' => $kontrak->id]) }}"
                   class="btn btn-light btn-sm" title="Edit Kontrak">
                    <i class="ri-pencil-line text-primary"></i>
                </a>
                <a href="{{ route('user.ats.kontrak.create', $userId) }}?gtk_id={{ $kontrak->gtk_id }}"
                   class="btn btn-light btn-sm" title="Buat Perpanjangan">
                    <i class="ri-file-add-line text-success"></i>
                </a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center py-5">
            <div class="py-4">
                <i class="ri-checkbox-circle-line text-success" style="font-size:3rem;opacity:.4"></i>
                <h5 class="mt-3 mb-1 fw-semibold text-success">Semua kontrak aman</h5>
                <p class="text-muted mb-0" style="font-size:.875rem">Tidak ada kontrak yang akan berakhir dalam 90 hari ke depan.</p>
            </div>
        </td>
    </tr>
@endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($kontraks->hasPages())
    <div class="card-footer border-top">{{ $kontraks->withQueryString()->links() }}</div>
    @endif
</div>
@endsection