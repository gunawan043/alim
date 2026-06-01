@extends('layouts.master')
@section('title') Edit Pengajuan Cuti @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#eff6ff 0%,#f8fafc 100%);border:1px solid #bfdbfe;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1e1b4b 0%,#1e1a2e 100%);border-color:#4338ca}
.form-card{border:.5px solid #bfdbfe;border-radius:.625rem;overflow:hidden}
.form-card .card-header{background:#f0f9ff;border-bottom:1px solid #bfdbfe;padding:.75rem 1.25rem;font-weight:600;font-size:.9rem}
[data-bs-theme="dark"] .form-card{border-color:#4338ca}
[data-bs-theme="dark"] .form-card .card-header{background:#1e1b4b;border-bottom-color:#4338ca}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Cuti & Izin @endslot
    @slot('li_2') <a href="{{ route('user.cuti.index', ['userId' => $userId]) }}">Daftar Pengajuan</a> @endslot
    @slot('title') Edit Pengajuan @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Edit Pengajuan Cuti</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Ubah detail pengajuan cuti Anda</p>
    </div>
    <a href="{{ route('user.cuti.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card form-card">
            <div class="card-header"><i class="ri-file-edit-line me-1"></i> Form Edit Cuti</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('user.cuti.update', ['userId' => $userId, 'id' => $cuti->id]) }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                        <select name="cuti_template_id" class="form-select @error('cuti_template_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Jenis Cuti --</option>
                            @foreach($templates as $t)
                            <option value="{{ $t->id }}" {{ $cuti->cuti_template_id == $t->id ? 'selected' : '' }}>
                                {{ $t->nama }} ({{ $t->jumlah_hari }} hari)
                            </option>
                            @endforeach
                        </select>
                        @error('cuti_template_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_mulai" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                   value="{{ $cuti->tanggal_mulai->format('Y-m-d') }}" required>
                            @error('tanggal_mulai') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_selesai" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                                   value="{{ $cuti->tanggal_selesai->format('Y-m-d') }}" required>
                            @error('tanggal_selesai') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan / Keterangan</label>
                        <textarea name="alasan" class="form-control" rows="3" placeholder="Jelaskan alasan...">{{ $cuti->alasan }}</textarea>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Update Pengajuan</button>
                        <a href="{{ route('user.cuti.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection