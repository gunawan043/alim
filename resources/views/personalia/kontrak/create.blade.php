@extends('layouts.master')
@push('css')
<style>
.page-header-card{
  background:linear-gradient(135deg,#f5f3ff 0%,#faf8ff 100%);
  border:1px solid #ddd6fe;
  padding:1.25rem 1.5rem;
  border-radius:.625rem;
}
[data-bs-theme="dark"] .page-header-card{
  background:linear-gradient(135deg,#1e1535 0%,#1a1028 100%);
  border-color:#6d28d9;
}
.form-label.required:after{content:" *";color:#dc2626}
[data-bs-theme="dark"] .form-control,[data-bs-theme="dark"] .form-select{
  background:#1e1e2d;color:#e2e8f0;border-color:#374151
}
[data-bs-theme="dark"] label{color:#cbd5e1}
</style>
@endpush

@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Personalia @endslot
    @slot('li_2') Kontrak @endslot
    @slot('title') Tambah Kontrak @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Tambah Kontrak Kerja</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Buat kontrak kerja baru untuk GTK.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.kontrak.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>
</div>

<form method="POST" action="{{ route('user.ats.kontrak.store', ['userId' => $userId]) }}">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-user-line me-1"></i> Data GTK</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Pilih GTK</label>
                            <select name="gtk_id" class="form-select @error('gtk_id') is-invalid @enderror">
                                <option value="">-- Pilih GTK --</option>
                                @foreach($gtkList as $gtk)
                                <option value="{{ $gtk->id }}" {{ old('gtk_id')==$gtk->id?'selected':'' }}>
                                    {{ $gtk->nama }} {{ $gtk->nik ? '- NIP.'.$gtk->nik : '' }}
                                </option>
                                @endforeach
                            </select>
                            @error('gtk_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Guru Matematika" value="{{ old('jabatan') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lokasi Kerja</label>
                            <input type="text" name="lokasi_kerja" class="form-control" placeholder="Contoh: Gedung A, Lantai 2" value="{{ old('lokasi_kerja') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-file-paper-2-line me-1"></i> Informasi Kontrak</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Jenis Kontrak</label>
                            <select name="jenis_kontrak" class="form-select @error('jenis_kontrak') is-invalid @enderror">
                                <option value="">-- Pilih --</option>
                                <option value="PKWT" {{ old('jenis_kontrak')=='PKWT'?'selected':'' }}>PKWT</option>
                                <option value="PKWTT" {{ old('jenis_kontrak')=='PKWTT'?'selected':'' }}>PKWTT</option>
                                <option value="MITRA" {{ old('jenis_kontrak')=='MITRA'?'selected':'' }}>MITRA</option>
                            </select>
                            @error('jenis_kontrak')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Template</label>
                            <select name="kontrak_template_id" class="form-select">
                                <option value="">-- Pilih Template (opsional) --</option>
                                @foreach($templates as $t)
                                <option value="{{ $t->id }}" {{ old('kontrak_template_id')==$t->id?'selected':'' }}>
                                    {{ $t->nama }} ({{ $t->jenis }})
                                </option>
                                @endforeach
                            </select>
                        </div>
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
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-wallet-line me-1"></i> Kompensasi</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Gaji Pokok (Rp)</label>
                            <input type="number" name="gaji_pokok" class="form-control" placeholder="0" min="0" value="{{ old('gaji_pokok') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3" placeholder="Catatan atau ketentuan tambahan...">{{ old('catatan') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('user.ats.kontrak.index', ['userId' => $userId]) }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ri-save-line me-1"></i> Simpan Kontrak
                </button>
            </div>
        </div>
    </div>
</form>
@endsection