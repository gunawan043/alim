@extends('layouts.master')
@section('title') Edit Penilaian Kinerja @endsection
@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.page-header-card{background:linear-gradient(135deg,#fff7ed 0%,#fffbf5 100%);border:1px solid #fed7aa;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1a0f00 0%,#1f1500 100%);border-color:#92400e}
@media print{.no-print{display:none!important}}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? Auth::id(); @endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#f9731618;color:#ea580c;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-edit-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Edit Penilaian Kinerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Perbarui data penilaian kinerja GTK</p>
        </div>
    </div>
    <a href="{{ route('user.ats.kinerja.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('user.ats.kinerja.update', [$userId, $penilaian->id]) }}">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-edit-line me-1"></i> Edit Penilaian</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">GTK</label>
                            <select name="user_id" class="form-select" required>
                                @foreach(\App\Models\User::where('is_active',true)->orderBy('name')->get() as $u)
                                <option value="{{ $u->id }}" {{ $penilaian->user_id==$u->id?'selected':'' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Periode</label>
                            <select name="kinerja_periode_id" class="form-select" required>
                                @foreach(\App\Models\KinerjaPeriode::orderBy('tanggal_mulai','multilang')->get() as $p)
                                <option value="{{ $p->id }}" {{ $penilaian->kinerja_periode_id==$p->id?'selected':'' }}>{{ $p->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" {{ $penilaian->status=='draft'?'selected':'' }}>Draft</option>
                                <option value="dinilai" {{ $penilaian->status=='dinilai'?'selected':'' }}>Dinilai</option>
                                <option value="rekon" {{ $penilaian->status=='rekon'?'selected':'' }}>Rekonsiliasi</option>
                                <option value="final" {{ $penilaian->status=='final'?'selected':'' }}>Final</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Penilai</label>
                            <textarea name="catatan_penilai" class="form-control" rows="3">{{ $penilaian->catatan_penilai }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2 no-print">
                    <a href="{{ route('user.ats.kinerja.index', $userId) }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>$('select').select2();</script>
@endpush
