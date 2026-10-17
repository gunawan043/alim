{{-- Pelatihan: Create --}}
@extends('layouts.master')
@section('title') Tambah Pelatihan @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#eef2ff 0%,#f5f7ff 100%);border:1px solid #c7d2fe;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1e1b4b 0%,#1e1a2e 100%);border-color:#4338ca}
[data-bs-theme="dark"] .form-control,[data-bs-theme="dark"] .form-select,[data-bs-theme="dark"] textarea{background:#1e293b;color:#e2e8f0;border-color:#334155}
[data-bs-theme="dark"] label{color:#cbd5e1}
.form-label.required:after{content:" *";color:#dc2626}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Personalia @endslot
    @slot('li_2') Pelatihan @endslot
    @slot('li_3') Tambah @endslot
    @slot('title') Tambah Pelatihan @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Tambah Pelatihan Baru</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Buat program pelatihan baru untuk GTK.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.pelatihan.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>
</div>

<form method="POST" action="{{ route('user.ats.pelatihan.store', ['userId' => $userId]) }}" class="row" enctype="multipart/form-data">
    @csrf

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ri-file-text-line me-1"></i> Informasi Pelatihan</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required">Nama Pelatihan</label>
                    <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                           placeholder="Contoh: Pelatihan Kurikulum Merdeka" value="{{ old('nama') }}">
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select">
                            <option value="">-- Pilih --</option>
                            <option value="internal" {{ old('kategori') == 'internal' ? 'selected' : '' }}>Internal</option>
                            <option value="eksternal" {{ old('kategori') == 'eksternal' ? 'selected' : '' }}>Eksternal</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jenis</label>
                        <select name="jenis" class="form-select">
                            <option value="">-- Pilih --</option>
                            <option value="pelatihan" {{ old('jenis') == 'pelatihan' ? 'selected' : '' }}>Pelatihan</option>
                            <option value="seminar" {{ old('jenis') == 'seminar' ? 'selected' : '' }}>Seminar</option>
                            <option value="workshop" {{ old('jenis') == 'workshop' ? 'selected' : '' }}>Workshop</option>
                            <option value="sertifikasi" {{ old('jenis') == 'sertifikasi' ? 'selected' : '' }}>Sertifikasi</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Vendor / Penyelenggara</label>
                    <input type="text" name="vendor" class="form-control" placeholder="Contoh: Kemendikbud" value="{{ old('vendor') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi pelatihan...">{{ old('deskripsi') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ri-time-line me-1"></i> Jadwal & Lokasi</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control @error('tanggal_mulai') is-invalid @enderror" value="{{ old('tanggal_mulai') }}">
                        @error('tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control @error('tanggal_selesai') is-invalid @enderror" value="{{ old('tanggal_selesai') }}">
                        @error('tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Lokasi</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Ruang Meeting A" value="{{ old('lokasi') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kapasitas</label>
                        <input type="number" name="kapasitas" class="form-control" min="1" placeholder="Jumlah peserta" value="{{ old('kapasitas') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ri-wallet-line me-1"></i> Biaya & Status</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Biaya (Rp)</label>
                        <input type="number" name="biaya" class="form-control" min="0" placeholder="0" value="{{ old('biaya') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="ditetapkan" {{ old('status') == 'ditetapkan' ? 'selected' : '' }}>Ditetapkan</option>
                            <option value="selesai" {{ old('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                            <option value="dibatalkan" {{ old('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ri-attachment-line me-1"></i> Materi</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Upload Materi</label>
                    <input type="file" name="materi" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip">
                    <small class="text-muted">Format: PDF, DOC, DOCX, PPT, PPTX, ZIP. Maks 20MB.</small>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('user.ats.pelatihan.index', ['userId' => $userId]) }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan Pelatihan</button>
        </div>
    </div>
</form>
@endsection