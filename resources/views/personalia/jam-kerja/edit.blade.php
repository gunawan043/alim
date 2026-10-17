@extends('layouts.master')
@section('title') Edit Jam Kerja @endsection
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
            <i class="ri-edit-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Edit Jam Kerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Perbarui data jam kerja</p>
        </div>
    </div>
    <a href="{{ route('user.jam-kerja.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('user.jam-kerja.update', [$userId, $jamKerja->id]) }}">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-edit-line me-1"></i> Edit Jam Kerja</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nama Jam Kerja <span class="text-danger">*</span></label>
                            <input type="text" name="nama" value="{{ old('nama', $jamKerja->nama) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ $jamKerja->is_active?'selected':'' }}>Aktif</option>
                                <option value="0" {{ !$jamKerja->is_active?'selected':'' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Masuk <span class="text-danger">*</span></label>
                            <input type="time" name="jam_masuk" value="{{ $jamKerja->jam_masuk }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Pulang <span class="text-danger">*</span></label>
                            <input type="time" name="jam_pulang" value="{{ $jamKerja->jam_pulang }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Durasi Istirahat (menit)</label>
                            <input type="number" name="istirahat_menit" value="{{ $jamKerja->istirahat_menit ?? 60 }}" class="form-control" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Mulai Istirahat</label>
                            <input type="time" name="istirahat_mulai" value="{{ $jamKerja->istirahat_mulai ?? '12:00' }}" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2">{{ $jamKerja->keterangan }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2 no-print">
                    <a href="{{ route('user.jam-kerja.index', $userId) }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Batal</a>
                    <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection