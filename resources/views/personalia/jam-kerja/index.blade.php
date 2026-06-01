@extends('layouts.master')
@section('title') Jam Kerja @endsection
@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.page-header-card{background:linear-gradient(135deg,#f5f3ff 0%,#ede9fe 100%);border:1px solid #c4b5fd;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#100c1f 0%,#150e22 100%);border-color:#5b21b6}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php
$userId = request()->route('userId') ?? auth()->id();
$currentUser = auth()->user();
$isAdmin = $currentUser->hasAnyRole(['Personalia','Super Admin','Admin Tata Usaha']);
$totalJamKerja = \App\Models\JamKerja::count();
$totalShift = \App\Models\Shift::count();
@endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#8b5cf618;color:#7c3aed;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-time-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Jam Kerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Kelola jam kerja dan shift GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.jam-kerja.shift', $userId) }}" class="btn btn-light btn-sm"><i class="ri-team-line me-1"></i>Shift</a>
        <a href="{{ route('user.jam-kerja.kalender', $userId) }}" class="btn btn-light btn-sm"><i class="ri-calendar-todo-line me-1"></i>Kalender</a>
        @if($isAdmin)
        <a href="{{ route('user.jam-kerja.create', $userId) }}" class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i>Tambah Jam Kerja</a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-4 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-time-line text-primary"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Total Jam Kerja</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalJamKerja }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-info">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-team-line text-info"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Total Shift</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalShift }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Aktif</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ \App\Models\JamKerja::where('is_active',true)->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-list-check-2 text-primary me-1"></i> Daftar Jam Kerja</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Nama Jam Kerja</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Durasi</th>
                        <th>Istirahat</th>
                        <th>Status</th>
                        <th class="no-print" style="width:120px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jamKerjas as $i => $jk)
                    @php
                        $jamMasuk = \Carbon\Carbon::parse($jk->jam_masuk);
                        $jamPulang = \Carbon\Carbon::parse($jk->jam_pulang);
                        $durasi = $jamMasuk->diffInHours($jamPulang);
                        $jamIstirahat = $jk->istirahat_menit ?? 0;
                    @endphp
                    <tr>
                        <td class="text-center text-muted" style="width:40px">{{ $jamKerjas->firstItem() + $i }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded bg-purple-subtle text-purple d-flex align-items-center justify-content-center">
                                    <i class="ri-time-line"></i>
                                </div>
                                <span class="fw-semibold">{{ $jk->nama }}</span>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark"><i class="ri-arrow-up-line me-1 text-success"></i>{{ $jk->jam_masuk }}</span></td>
                        <td><span class="badge bg-light text-dark"><i class="ri-arrow-down-line me-1 text-danger"></i>{{ $jk->jam_pulang }}</span></td>
                        <td><span class="badge bg-info-subtle text-info">{{ $durasi }} jam</span></td>
                        <td><span class="text-muted small">{{ $jamIstirahat > 0 ? $jamIstirahat . ' menit' : '-' }}</span></td>
                        <td>
                            @if($jk->is_active)
                            <span class="badge bg-success-subtle text-success badge-status"><i class="ri-checkbox-circle-line me-1"></i>Aktif</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary badge-status">Nonaktif</span>
                            @endif
                        </td>
                        <td class="no-print">
                            <div class="d-flex gap-1">
                                <a href="{{ route('user.jam-kerja.show', [$userId, $jk->id]) }}" class="btn btn-soft-primary btn-sm" title="Detail"><i class="ri-eye-line"></i></a>
                                @if($isAdmin)
                                <a href="{{ route('user.jam-kerja.edit', [$userId, $jk->id]) }}" class="btn btn-soft-warning btn-sm" title="Edit"><i class="ri-edit-2-line"></i></a>
                                <form action="{{ route('user.jam-kerja.destroy', [$userId, $jk->id]) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-soft-danger btn-sm" title="Hapus" onclick="return confirm('Hapus jam kerja ini?')"><i class="ri-delete-bin-line"></i></button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <i class="ri-time-line text-muted" style="font-size:3rem;"></i>
                                <h5 class="fw-semibold text-dark mt-2 mb-1">Belum ada data jam kerja</h5>
                                <p class="text-muted mb-0 small">Klik <strong>Tambah Jam Kerja</strong> untuk menambahkan jam kerja baru.</p>
                                @if($isAdmin)<a href="{{ route('user.jam-kerja.create', $userId) }}" class="btn btn-primary btn-sm mt-2"><i class="ri-add-line me-1"></i> Tambah Jam Kerja</a>@endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($jamKerjas->hasPages())
    <div class="card-footer border-top-dashed bg-transparent no-print">
        <div class="d-flex justify-content-between align-items-center px-2">
            <p class="text-muted mb-0" style="font-size:.8rem">Menampilkan {{ $jamKerjas->firstItem() }}–{{ $jamKerjas->lastItem() }} dari {{ $jamKerjas->total() }} data</p>
            {{ $jamKerjas->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
@endsection