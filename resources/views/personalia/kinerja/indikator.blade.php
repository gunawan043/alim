@extends('layouts.master')
@section('title') Indikator Kinerja GTK @endsection
@push('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.page-header-card{background:linear-gradient(135deg,#fff7ed 0%,#fffbf5 100%);border:1px solid #fed7aa;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1a0f00 0%,#1f1500 100%);border-color:#92400e}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php
$userId = request()->route('userId') ?? auth()->id();
$currentUser = auth()->user();
$isAdmin = $currentUser->hasAnyRole(['Personalia','Super Admin','Admin Tata Usaha']);
$totalIndikator = \App\Models\KinerjaIndikator::count();
$totalKategori = \App\Models\KinerjaIndikator::whereNotNull('kategori')->distinct('kategori')->count('kategori');
$totalBobot = \App\Models\KinerjaIndikator::sum('bobot_persen') ?? 0;
@endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#f9731618;color:#ea580c;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-list-checks fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Indikator Kinerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Kelola indikator dan parameter penilaian kinerja GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.ats.kinerja.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i> Daftar</a>
        <a href="{{ route('user.ats.kinerja.reward', $userId) }}" class="btn btn-light btn-sm"><i class="ri-medal-line me-1"></i> Reward</a>
        <a href="{{ route('user.ats.kinerja.laporan', $userId) }}" class="btn btn-light btn-sm"><i class="ri-file-chart-line me-1"></i> Laporan</a>
        @if($isAdmin)
        <a href="{{ route('user.ats.kinerja.indikator.create', $userId) }}" class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i> Tambah Indikator</a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-list-check text-primary"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Total Indikator</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalIndikator }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-folder-chart-line text-success"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Kategori</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalKategori }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-info">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-percent-line text-info"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Total Bobot</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalBobot }}%</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-bullseye-line text-warning"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Target</p>
                        <h3 class="fw-bold ff-secondary mb-0">100%</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-list-checks text-primary me-1"></i> Daftar Indikator Kinerja GTK</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Nama Indikator</th>
                        <th>Kategori</th>
                        <th>Bobot (%)</th>
                        <th>Deskripsi</th>
                        <th class="no-print" style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($indikators as $i => $ind)
                    <tr>
                        <td class="text-center text-muted">{{ $indikators->firstItem() + $i }}</td>
                        <td><span class="fw-semibold">{{ $ind->nama }}</span></td>
                        <td><span class="badge bg-light text-dark">{{ $ind->kategori ?? '-' }}</span></td>
                        <td><span class="badge bg-primary-subtle text-primary">{{ $ind->bobot_persen }}%</span></td>
                        <td><span class="text-muted small">{{ Str::limit($ind->deskripsi, 100) ?? '-' }}</span></td>
                        <td class="no-print">
                            <div class="d-flex gap-1">
                                @if($isAdmin)
                                <a href="{{ route('user.ats.kinerja.indikator.edit', [$userId, $ind->id]) }}" class="btn btn-soft-warning btn-sm"><i class="ri-edit-2-line"></i></a>
                                <form action="{{ route('user.ats.kinerja.indikator.destroy', [$userId, $ind->id]) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-soft-danger btn-sm" onclick="return confirm('Hapus indikator ini?')"><i class="ri-delete-bin-line"></i></button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <i class="ri-list-checks text-muted" style="font-size:3rem;"></i>
                                <h5 class="fw-semibold text-dark mt-2 mb-1">Belum ada indikator kinerja</h5>
                                <p class="text-muted mb-0 small">Klik <strong>Tambah Indikator</strong> untuk menambahkan indikator baru.</p>
                                @if($isAdmin)<a href="{{ route('user.ats.kinerja.indikator.create', $userId) }}" class="btn btn-primary btn-sm mt-2"><i class="ri-add-line me-1"></i> Tambah Indikator</a>@endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($indikators->hasPages())
    <div class="card-footer border-top-dashed bg-transparent no-print">
        <div class="d-flex justify-content-between align-items-center px-2">
            <p class="text-muted mb-0" style="font-size:.8rem">Menampilkan {{ $indikators->firstItem() }}–{{ $indikators->lastItem() }} dari {{ $indikators->total() }} data</p>
            {{ $indikators->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
@endsection