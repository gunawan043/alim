@extends('layouts.master')
@section('title') Periode Penilaian Kinerja @endsection
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
$userId = request()->route('userId') ?? Auth::id();
$totalPeriode = \App\Models\KinerjaPeriode::count();
$aktifPeriode = \App\Models\KinerjaPeriode::where('status','aktif')->count();
$selesaiPeriode = \App\Models\KinerjaPeriode::where('status','selesai')->count();
@endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#f9731618;color:#ea580c;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-calendar-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Periode Penilaian Kinerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Kelola periode penilaian kinerja GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.ats.kinerja.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Daftar</a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPeriodeModal"><i class="ri-add-line me-1"></i>Tambah Periode</button>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-4 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-calendar-line text-primary"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Total Periode</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalPeriode }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-calendar-check-line text-success"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Periode Aktif</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $aktifPeriode }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-info">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-info"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Selesai</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $selesaiPeriode }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-calendar-check-line text-primary me-1"></i> Daftar Periode</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Nama Periode</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Status</th>
                        <th>Jumlah Penilaian</th>
                        <th class="no-print" style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periodes as $i => $p)
                    <tr>
                        <td class="text-center text-muted">{{ $periodes->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $p->nama }}</td>
                        <td>{{ $p->tanggal_mulai?->format('d M Y') }}</td>
                        <td>{{ $p->tanggal_selesai?->format('d M Y') }}</td>
                        <td>
                            @switch($p->status)
                                @case('draft')<span class="badge bg-secondary-subtle text-secondary badge-status">Draft</span>@break
                                @case('aktif')<span class="badge bg-success-subtle text-success badge-status"><i class="ri-check-line me-1"></i>Aktif</span>@break
                                @case('selesai')<span class="badge bg-primary-subtle text-primary badge-status">Selesai</span>@break
                            @endswitch
                        </td>
                        <td><span class="badge bg-light text-dark">{{ $p->penilaian->count() }}</span></td>
                        <td class="no-print">
                            <div class="d-flex gap-1">
                                <a href="{{ route('user.ats.kinerja.periode') }}?edit={{ $p->id }}" class="btn btn-soft-warning btn-sm"><i class="ri-edit-2-line"></i></a>
                                <form action="{{ route('user.ats.kinerja.periode.destroy', [$userId, $p->id]) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-soft-danger btn-sm" onclick="return confirm('Hapus periode ini?')"><i class="ri-delete-bin-line"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <i class="ri-calendar-line text-muted" style="font-size:3rem;"></i>
                                <h5 class="fw-semibold text-dark mt-2 mb-1">Belum ada periode</h5>
                                <p class="text-muted mb-0 small">Klik <strong>Tambah Periode</strong> untuk menambahkan periode baru.</p>
                                <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#addPeriodeModal"><i class="ri-add-line me-1"></i> Tambah</button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($periodes->hasPages())
    <div class="card-footer border-top-dashed bg-transparent no-print">
        <div class="d-flex justify-content-between align-items-center px-2">
            <p class="text-muted mb-0" style="font-size:.8rem">Menampilkan {{ $periodes->firstItem() }}–{{ $periodes->lastItem() }} dari {{ $periodes->total() }} data</p>
            {{ $periodes->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>

<div class="modal fade" id="addPeriodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="ri-add-circle-line me-1"></i> Tambah Periode</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('user.ats.kinerja.periode.store', $userId) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Periode <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Semester Ganjil 2026" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_mulai" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_selesai" class="form-control" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft">Draft</option>
                            <option value="aktif">Aktif</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection