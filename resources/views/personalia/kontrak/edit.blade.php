{{-- Kontrak Kerja: Edit --}}
@extends('layouts.master')
@section('title') Edit Kontrak Kerja @endsection

@push('css')
<style>
.form-label.required:after{content:" *";color:#dc2626}
.page-header-card{background:linear-gradient(135deg,#f5f3ff 0%,#ede9fe 100%);border:1px solid #c4b5fd;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1e1535 0%,#221640 100%);border-color:#7c3aed}
[data-bs-theme="dark"] .form-control,[data-bs-theme="dark"] .form-select{background:#1e1e2d;color:#e2e8f0;border-color:#374151}
[data-bs-theme="dark"] label{color:#cbd5e1}
</style>
@endpush

@section('content')
@php
$userId = request()->route('userId') ?? Auth::id();
$currentUser = auth()->user();
$isAdmin = $currentUser && $currentUser->hasAnyRole(['Personalia','Super Admin']);
$kontrak = $kontrak ?? null;
@endphp

{{-- Page header --}}
<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#7c3aed18;color:#7c3aed;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-file-edit-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Edit Kontrak Kerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Perbarui data kontrak kerja GTK</p>
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
        <a class="nav-link" href="{{ route('user.ats.kontrak.settings', $userId) }}">
            <i class="ri-settings-3-line me-1"></i>Pengaturan
        </a>
    </li>
</ul>

@if($kontrak)
<form method="POST" action="{{ route('user.ats.kontrak.update', ['userId' => $userId, 'id' => $kontrak->id]) }}" class="mt-3">
    @csrf
    @method('PUT')
    <div class="row">

        {{-- Section: Data GTK --}}
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-user-line me-1"></i>Data GTK</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">GTK</label>
                            <input type="text" class="form-control" value="{{ $kontrak->gtk?->nama ?? '-' }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="jabatan" class="form-control" value="{{ old('jabatan', $kontrak->jabatan ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Kerja</label>
                            <select name="unit_kerja" class="form-select">
                                <option value="">-- Pilih Unit --</option>
                                <option {{ ($kontrak->unit_kerja ?? '') == 'TK' ? 'selected' : '' }}>TK</option>
                                <option {{ ($kontrak->unit_kerja ?? '') == 'SD' ? 'selected' : '' }}>SD</option>
                                <option {{ ($kontrak->unit_kerja ?? '') == 'SMP' ? 'selected' : '' }}>SMP</option>
                                <option {{ ($kontrak->unit_kerja ?? '') == 'SMA' ? 'selected' : '' }}>SMA</option>
                                <option {{ ($kontrak->unit_kerja ?? '') == 'Pondok' ? 'selected' : '' }}>Pondok</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIK / NIP</label>
                            <input type="text" name="nik" class="form-control" value="{{ old('nik', $kontrak->nik ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Informasi Kontrak --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-file-paper-2-line me-1"></i>Informasi Kontrak</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">No. Kontrak</label>
                            <input type="text" name="no_kontrak" class="form-control @error('no_kontrak') is-invalid @enderror" value="{{ old('no_kontrak', $kontrak->no_kontrak ?? '') }}">
                            @error('no_kontrak')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Jenis Kontrak</label>
                            <select name="jenis_kontrak" class="form-select @error('jenis_kontrak') is-invalid @enderror">
                                <option value="">-- Pilih --</option>
                                <option {{ ($kontrak->jenis_kontrak ?? '') == 'PKWT' ? 'selected' : '' }}>PKWT</option>
                                <option {{ ($kontrak->jenis_kontrak ?? '') == 'PKWTT' ? 'selected' : '' }}>PKWTT</option>
                                <option {{ ($kontrak->jenis_kontrak ?? '') == 'Kontrak Projet' ? 'selected' : '' }}>Kontrak Projet</option>
                            </select>
                            @error('jenis_kontrak')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Aktif" {{ ($kontrak->status ?? '') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="Berakhir" {{ ($kontrak->status ?? '') == 'Berakhir' ? 'selected' : '' }}>Berakhir</option>
                                <option value="Diperpanjang" {{ ($kontrak->status ?? '') == 'Diperpanjang' ? 'selected' : '' }}>Diperpanjang</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai', $kontrak->tanggal_mulai ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai', $kontrak->tanggal_selesai ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Template</label>
                            <select name="template_id" class="form-select">
                                <option value="">-- Pilih Template --</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Kompensasi --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-wallet-line me-1"></i>Kompensasi & Benefit</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Gaji Pokok (Rp)</label>
                            <input type="number" name="gaji_pokok" class="form-control" min="0" value="{{ old('gaji_pokok', $kontrak->gaji_pokok ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tunjangan (Rp)</label>
                            <input type="number" name="tunjangan" class="form-control" min="0" value="{{ old('tunjangan', $kontrak->tunjangan ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">BPJS Kesehatan</label>
                            <input type="number" name="bpjs_kesehatan" class="form-control" min="0" value="{{ old('bpjs_kesehatan', $kontrak->bpjs_kesehatan ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">BPJS Ketenagakerjaan</label>
                            <input type="number" name="bpjs_ketenagakerjaan" class="form-control" min="0" value="{{ old('bpjs_ketenagakerjaan', $kontrak->bpjs_ketenagakerjaan ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3">{{ old('catatan', $kontrak->catatan ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="col-12 d-flex gap-2 justify-content-end no-print">
            <a href="{{ route('user.ats.kontrak.index', $userId) }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ri-save-line me-1"></i>Perbarui Kontrak
            </button>
        </div>
    </div>
</form>
@else
<div class="card mt-3">
    <div class="card-body text-center py-5">
        <i class="ri-file-edit-line text-muted" style="font-size:3rem;"></i>
        <h5 class="mt-3 mb-1 fw-semibold">Kontrak tidak ditemukan</h5>
        <p class="text-muted mb-3">Data kontrak yang Anda cari tidak ditemukan.</p>
        <a href="{{ route('user.ats.kontrak.index', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i>Kembali ke Daftar
        </a>
    </div>
</div>
@endif
@endsection