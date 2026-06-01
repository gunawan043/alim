@extends('layouts.master')
@section('title') Tambah Jam Kerja @endsection
@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#f5f3ff 0%,#ede9fe 100%);border:1px solid #c4b5fd;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#100c1f 0%,#150e22 100%);border-color:#5b21b6}
@media print{.no-print{display:none!important}}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#8b5cf618;color:#7c3aed;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-add-circle-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Tambah Jam Kerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Form tambah jam kerja baru</p>
        </div>
    </div>
    <a href="{{ route('user.jam-kerja.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('user.jam-kerja.store', $userId) }}">
            @csrf
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-time-line me-1"></i> Data Jam Kerja</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nama Jam Kerja <span class="text-danger">*</span></label>
                            <input type="text" name="nama" value="{{ old('nama') }}" class="form-control @error('nama') is-invalid @enderror" placeholder="Contoh: Pagi, Siang, Full Day" required>
                            @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Masuk <span class="text-danger">*</span></label>
                            <input type="time" name="jam_masuk" value="{{ old('jam_masuk','08:00') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Pulang <span class="text-danger">*</span></label>
                            <input type="time" name="jam_pulang" value="{{ old('jam_pulang','16:00') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Durasi Istirahat (menit)</label>
                            <input type="number" name="istirahat_menit" value="{{ old('istirahat_menit',60) }}" class="form-control" min="0" placeholder="60">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Mulai Istirahat</label>
                            <input type="time" name="istirahat_mulai" value="{{ old('istirahat_mulai','12:00') }}" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2 no-print">
                    <a href="{{ route('user.jam-kerja.index', $userId) }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection