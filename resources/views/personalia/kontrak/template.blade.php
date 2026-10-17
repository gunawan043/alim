{{-- Kontrak Kerja: Template --}}
@extends('layouts.master')
@section('title') Template Kontrak Kerja @endsection

@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.page-header-card{background:linear-gradient(135deg,#f5f3ff 0%,#ede9fe 100%);border:1px solid #c4b5fd;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1e1535 0%,#221640 100%);border-color:#7c3aed}
[data-bs-theme="dark"] .table-freeze thead th{background:#1a1f3a}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
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
            <i class="ri-file-text-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Template Kontrak Kerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Kelola template dan format standar kontrak kerja</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.ats.kontrak.index', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i>Kembali
        </a>
        @if($isAdmin)
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
            <i class="ri-add-circle-line me-1"></i>Tambah Template
        </button>
        @endif
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
        <a class="nav-link active" href="{{ route('user.ats.kontrak.template', $userId) }}">
            <i class="ri-file-text-line me-1"></i>Template
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('user.ats.kontrak.settings', $userId) }}">
            <i class="ri-settings-3-line me-1"></i>Pengaturan
        </a>
    </li>
</ul>

{{-- Card with table --}}
<div class="card mt-3">
    <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-file-text-line me-1"></i>Daftar Template</h5>
        <div class="d-flex gap-2 no-print">
            <button class="btn btn-light btn-sm"><i class="ri-download-line me-1"></i>Export</button>
        </div>
    </div>
    <div class="card-body p-0">
        {{-- Filter bar --}}
        <div class="px-3 py-2 border-bottom bg-light">
            <div class="row g-2 align-items-center no-print">
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-sm" placeholder="Cari template...">
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm">
                        <option value="">Semua Jenis</option>
                        <option>PKWT</option>
                        <option>PKWTT</option>
                        <option>Kontrak Projet</option>
                    </select>
                </div>
                <div class="col-md-auto d-flex gap-1">
                    <button class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
                    <button class="btn btn-light btn-sm"><i class="ri-reset-right-line me-1"></i>Reset</button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze">
                <thead>
                    <tr>
                        <th width="40">#</th>
                        <th>Nama Template</th>
                        <th>Jenis Kontrak</th>
                        <th>File</th>
                        <th>Terakhir Diperbarui</th>
                        <th class="no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="py-4">
                                <i class="ri-file-text-line text-muted" style="font-size:3.5rem;"></i>
                                <h5 class="mt-3 mb-1 fw-semibold">Belum ada template kontrak</h5>
                                <p class="text-muted mb-3" style="font-size:.875rem">Template kontrak kerja GTK akan muncul di sini.</p>
                                @if($isAdmin)
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                                    <i class="ri-add-line me-1"></i>Tambah Template
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal: Tambah Template --}}
@if($isAdmin)
<div class="modal fade" id="addTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-file-add-line me-1"></i>Tambah Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('user.ats.kontrak.template.store', $userId) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Nama Template</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Template PKWT Standar" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Jenis Kontrak</label>
                        <select name="jenis" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option>PKWT</option>
                            <option>PKWTT</option>
                            <option>Kontrak Projet</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi template..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File Template</label>
                        <input type="file" name="file" class="form-control" accept=".doc,.docx,.pdf">
                        <small class="text-muted">Format: DOC, DOCX, PDF. Maks 10MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection