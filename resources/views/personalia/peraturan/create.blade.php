{{-- Peraturan: Create --}}
@extends('layouts.master')
@section('title') Tambah Peraturan @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);border:1px solid #cbd5e1;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);border-color:#334155}
[data-bs-theme="dark"] .form-control,[data-bs-theme="dark"] .form-select,[data-bs-theme="dark"] textarea{background:#1e293b;color:#e2e8f0;border-color:#334155}
[data-bs-theme="dark"] label{color:#cbd5e1}
.form-label.required:after{content:" *";color:#dc2626}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Personalia @endslot
    @slot('li_2') Peraturan @endslot
    @slot('li_3') Tambah @endslot
    @slot('title') Tambah Peraturan @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Tambah Peraturan Baru</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Tambahkan dokumen peraturan baru untuk GTK.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.peraturan.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>
</div>

<form method="POST" action="{{ route('user.ats.peraturan.store', ['userId' => $userId]) }}" class="row" enctype="multipart/form-data">
    @csrf

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ri-file-text-line me-1"></i> Informasi Peraturan</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required">Judul Peraturan</label>
                    <input type="text" name="judul" class="form-control @error('judul') is-invalid @enderror"
                           placeholder="Contoh: Larangan Terlambat Masuk Kelas" value="{{ old('judul') }}">
                    @error('judul')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Kategori</label>
                        <select name="kategori_id" class="form-select">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($kategoris as $kat)
                                <option value="{{ $kat->id }}" {{ old('kategori_id') == $kat->id ? 'selected' : '' }}>{{ $kat->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nomor Peraturan</label>
                        <input type="text" name="nomor" class="form-control" placeholder="Contoh: SK-001/2024" value="{{ old('nomor') }}">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Ditetapkan</label>
                        <input type="date" name="tanggal_ditetapkan" class="form-control" value="{{ old('tanggal_ditetapkan') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Berlaku</label>
                        <input type="date" name="tanggal_berlaku" class="form-control" value="{{ old('tanggal_berlaku') }}">
                    </div>
                </div>
                <div class="mb-3 mt-2">
                    <label class="form-label">Versi</label>
                    <input type="text" name="versi" class="form-control" placeholder="Contoh: 1.0" value="{{ old('versi') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi / Isi Peraturan</label>
                    <textarea name="deskripsi" class="form-control" rows="4" placeholder="Tuliskan deskripsi peraturan secara lengkap...">{{ old('deskripsi') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ri-settings-3-line me-1"></i> Pengaturan</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="aktif" {{ old('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="diarsipkan" {{ old('status') == 'diarsipkan' ? 'selected' : '' }}>Diarsipkan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Visibilitas</label>
                        <select name="visibility" class="form-select">
                            <option value="all" {{ old('visibility') == 'all' ? 'selected' : '' }}>Semua</option>
                            <option value="gtk" {{ old('visibility') == 'gtk' ? 'selected' : '' }}>GTK Saja</option>
                            <option value="personalia" {{ old('visibility') == 'personalia' ? 'selected' : '' }}>Personalia</option>
                            <option value="management" {{ old('visibility') == 'management' ? 'selected' : '' }}>Management</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ri-attachment-line me-1"></i> Lampiran</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Upload File</label>
                    <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx">
                    <small class="text-muted">Format: PDF, DOC, DOCX, XLS, XLSX. Maks 10MB.</small>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('user.ats.peraturan.index', ['userId' => $userId]) }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan Peraturan</button>
        </div>
    </div>
</form>
@endsection