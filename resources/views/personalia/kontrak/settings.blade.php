{{-- Kontrak Kerja: Settings --}}
@extends('layouts.master')
@section('title') Pengaturan Kontrak Kerja @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#f5f3ff 0%,#ede9fe 100%);border:1px solid #c4b5fd;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg:#1e1535 0%,#221640 100%);border-color:#7c3aed}
[data-bs-theme="dark"] .form-control,[data-bs-theme="dark"] .form-select,[data-bs-theme="dark"] .form-check-input{background:#1e1e2d;color:#e2e8f0;border-color:#374151}
[data-bs-theme="dark"] label{color:#cbd5e1}
.setting-card{background:#fff;border:1px solid #e2e8f0;border-radius:.625rem;transition:all .2s}
.setting-card:hover{border-color:#c4b5fd;box-shadow:0 4px 12px rgba(124,58,237,.08)}
[data-bs-theme="dark"] .setting-card{background:#1a1f3a;border-color:#2a3055}
[data-bs-theme="dark"] .form-switch .form-check-input{background:#374151}
@media print{.no-print{display:none!important}}
</style>
@endpush

@section('content')
@php
$userId = request()->route('userId') ?? Auth::id();
$currentUser = auth()->user();
$isAdmin = $currentUser && $currentUser->hasAnyRole(['Personalia','Super Admin']);
@endphp

{{-- Page header --}}
<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#7c3aed18;color:#7c3aed;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-settings-3-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Pengaturan Kontrak Kerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Konfigurasi parameter dan aturan kontrak kerja</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.ats.kontrak.index', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i>Kembali
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
        <a class="nav-link" href="{{ route('user.ats.kontrak.expiring', $userId) }}">
            <i class="ri-alert-line me-1"></i>Akan Berakhir
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('user.ats.kontrak.template', $userId) }}">
            <i class="ri-file-text-line me-1"></i>Template
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('user.ats.kontrak.settings', $userId) }}">
            <i class="ri-settings-3-line me-1"></i>Pengaturan
        </a>
    </li>
</ul>

@if($isAdmin)
<form method="POST" action="{{ route('user.ats.kontrak.settings.save', $userId) }}" class="mt-3">
    @csrf

    {{-- Card: Parameter Kontrak --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="ri-file-settings-line me-1"></i>Parameter Kontrak</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Durasi Default Kontrak (bulan)</label>
                    <input type="number" name="default_durasi" class="form-control" min="1" max="60" value="12">
                    <small class="text-muted">Durasi kontrak default saat membuat kontrak baru.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Batas Pengingat Expiring (hari)</label>
                    <input type="number" name="batas_expiring" class="form-control" min="1" max="365" value="90">
                    <small class="text-muted">Kontrak akan muncul di daftar expiring N hari sebelum berakhir.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jenis Kontrak Tersedia</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="jenis_pkwt" checked>
                        <label class="form-check-label" for="jenis_pkwt">PKWT (Perjanjian Kerja Waktu Tertentu)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="jenis_pkwtt" checked>
                        <label class="form-check-label" for="jenis_pkwtt">PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="jenis_proyek" checked>
                        <label class="form-check-label" for="jenis_proyek">Kontrak Proyek</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Unit Kerja</label>
                    @foreach(['TK','SD','SMP','SMA','Pondok'] as $unit)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="unit_{{ $unit }}" checked>
                        <label class="form-check-label" for="unit_{{ $unit }}">{{ $unit }}</label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Card: Notifikasi --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="ri-notification-3-line me-1"></i>Notifikasi</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="setting-card p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="fw-semibold mb-1" style="font-size:.9rem">Pengingat Otomatis</p>
                                <p class="text-muted mb-0 small">Kirim notifikasi otomatis saat kontrak akan berakhir.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="notif_auto" checked>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="setting-card p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="fw-semibold mb-1" style="font-size:.9rem">Notifikasi Email</p>
                                <p class="text-muted mb-0 small">Kirim notifikasi via email ke Personalia.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="notif_email" checked>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jumlah Hari Sebelum Berakhir</label>
                    <select name="notif_hari" class="form-select">
                        <option value="7">7 hari sebelumnya</option>
                        <option value="14">14 hari sebelumnya</option>
                        <option value="30" selected>30 hari sebelumnya</option>
                        <option value="60">60 hari sebelumnya</option>
                        <option value="90">90 hari sebelumnya</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Card: Aturan Otomatis --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="ri-robot-line me-1"></i>Aturan Otomatis</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="setting-card p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="fw-semibold mb-1" style="font-size:.9rem">Auto-Perpanjangan Kontrak</p>
                                <p class="text-muted mb-0 small">Secara otomatis membuatkan draft perpanjangan kontrak saat mendekati masa habis.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="auto_extend">
                            </div>
                        </div>
                    </div>
                    <div class="setting-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="fw-semibold mb-1" style="font-size:.9rem">Arsip Otomatis</p>
                                <p class="text-muted mb-0 small">Otomatis arsipkan kontrak yang sudah berakhir setelah 30 hari.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="auto_archive" checked>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end no-print">
        <button type="reset" class="btn btn-secondary">Reset</button>
        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Pengaturan</button>
    </div>
</form>
@else
<div class="card mt-3">
    <div class="card-body text-center py-5">
        <i class="ri-lock-line text-muted" style="font-size:3rem;"></i>
        <h5 class="mt-3 mb-1 fw-semibold">Akses Dibatasi</h5>
        <p class="text-muted mb-0">Hanya akun Personalia atau Super Admin yang dapat mengakses pengaturan kontrak.</p>
    </div>
</div>
@endif
@endsection