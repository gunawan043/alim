@extends('layouts.master')
@section('title') Benefit GTK @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#ecfdf5 0%,#f0fdf4 100%);border:1px solid #a7f3d0;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#064e3b 0%,#022c22 100%);border-color:#059669}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Benefit & Tunjangan GTK</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Kelola benefit dan tunjangan kesejahteraan</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.kesejahteraan.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
        <button class="btn btn-success btn-sm"><i class="ri-add-circle-line me-1"></i> Tambah Benefit</button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Benefit</th>
                        <th>Jenis</th>
                        <th>Nilai / Besaran</th>
                        <th>Jumlah Penerima</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="7" class="text-center py-5">
                        <i class="ri-gift-line" style="font-size:3rem;color:#9ca3af"></i>
                        <h6 class="mt-2 text-muted">Belum ada benefit terdaftar</h6>
                        <p class="text-muted" style="font-size:.8rem">Tambahkan benefit untuk memulai.</p>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection