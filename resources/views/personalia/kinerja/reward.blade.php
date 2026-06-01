@extends('layouts.master')
@section('title') Reward & Punishment @endsection
@push('css')
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
$totalReward = \App\Models\KinerjaReward::where('tipe','reward')->count();
$totalPunishment = \App\Models\KinerjaReward::where('tipe','punishment')->count();
@endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#f9731618;color:#ea580c;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-medal-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Reward & Punishment</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Catat dan kelola reward serta punishment untuk GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.ats.kinerja.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Daftar</a>
        <a href="{{ route('user.ats.kinerja.indikator', $userId) }}" class="btn btn-light btn-sm"><i class="ri-list-checks me-1"></i>Indikator</a>
        <a href="{{ route('user.ats.kinerja.laporan', $userId) }}" class="btn btn-light btn-sm"><i class="ri-file-chart-line me-1"></i>Laporan</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-gift-line text-success"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Total Reward</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalReward }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-danger">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-shield-star-line text-danger"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Total Punishment</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalPunishment }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-medal-line text-warning"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">GTK Terbaik</p>
                        <h3 class="fw-bold ff-secondary mb-0">-</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-danger">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-error-warning-line text-danger"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Pelanggaran Tertinggi</p>
                        <h3 class="fw-bold ff-secondary mb-0">-</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3 no-print" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#reward" role="tab">
            <i class="ri-gift-line me-1 align-middle"></i> Daftar Reward
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#punishment" role="tab">
            <i class="ri-shield-star-line me-1 align-middle"></i> Daftar Punishment
        </a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="reward" role="tabpanel">
        <div class="card">
            <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="card-title mb-0"><i class="ri-gift-line text-success me-1"></i> Daftar Reward GTK</h5>
                <div class="d-flex gap-2">
                    @if($isAdmin)
                    <a href="{{ route('user.ats.kinerja.reward.create', $userId) }}" class="btn btn-success btn-sm"><i class="ri-add-line me-1"></i> Tambah Reward</a>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>GTK</th>
                                <th>Jenis Reward</th>
                                <th>Nilai</th>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th class="no-print" style="width:80px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rewards as $i => $r)
                            <tr>
                                <td class="text-center text-muted">{{ $rewards->firstItem() + $i }}</td>
                                <td><span class="fw-semibold">{{ $r->user->name ?? '-' }}</span></td>
                                <td><span class="badge bg-success-subtle text-success">{{ $r->jenis ?></span></td>
                                <td><span class="badge bg-warning-subtle text-warning">{{ number_format($r->nilai) }}</span></td>
                                <td>{{ $r->tanggal?->format('d M Y') }}</td>
                                <td><span class="text-muted small">{{ Str::limit($r->keterangan, 60) }}</span></td>
                                <td class="no-print">
                                    <div class="d-flex gap-1">
                                        @if($isAdmin)
                                        <a href="{{ route('user.ats.kinerja.reward.edit', [$userId, $r->id]) }}" class="btn btn-soft-warning btn-sm"><i class="ri-edit-2-line"></i></a>
                                        <form action="{{ route('user.ats.kinerja.reward.destroy', [$userId, $r->id]) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-soft-danger btn-sm" onclick="return confirm('Hapus?')"><i class="ri-delete-bin-line"></i></button></form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i class="ri-gift-line text-muted" style="font-size:3rem;"></i>
                                        <h5 class="fw-semibold text-dark mt-2 mb-1">Belum ada data reward</h5>
                                        <p class="text-muted mb-0 small">Klik <strong>Tambah Reward</strong> untuk mencatat reward GTK.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($rewards->hasPages())
            <div class="card-footer border-top-dashed bg-transparent no-print">
                {{ $rewards->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>

    <div class="tab-pane" id="punishment" role="tabpanel">
        <div class="card">
            <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="card-title mb-0"><i class="ri-shield-star-line text-danger me-1"></i> Daftar Punishment GTK</h5>
                <div class="d-flex gap-2">
                    @if($isAdmin)
                    <a href="{{ route('user.ats.kinerja.punishment.create', $userId) }}" class="btn btn-danger btn-sm"><i class="ri-add-line me-1"></i> Tambah Punishment</a>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>GTK</th>
                                <th>Jenis Pelanggaran</th>
                                <th>Tingkat</th>
                                <th>Sanksi</th>
                                <th>Tanggal</th>
                                <th class="no-print" style="width:80px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($punishments as $i => $p)
                            <tr>
                                <td class="text-center text-muted">{{ $punishments->firstItem() + $i }}</td>
                                <td><span class="fw-semibold">{{ $p->user->name ?? '-' }}</span></td>
                                <td><span class="badge bg-danger-subtle text-danger">{{ $p->jenis ?></span></td>
                                <td><span class="badge {{ $p->tingkat=='berat'?'bg-danger-subtle text-danger':($p->tingkat=='sedang'?'bg-warning-subtle text-warning':'bg-secondary-subtle text-secondary') }}">{{ ucfirst($p->tingkat ?? '-') }}</span></td>
                                <td><span class="text-muted small">{{ Str::limit($p->sanksi, 60) }}</span></td>
                                <td>{{ $p->tanggal?->format('d M Y') }}</td>
                                <td class="no-print">
                                    <div class="d-flex gap-1">
                                        @if($isAdmin)
                                        <a href="{{ route('user.ats.kinerja.punishment.edit', [$userId, $p->id]) }}" class="btn btn-soft-warning btn-sm"><i class="ri-edit-2-line"></i></a>
                                        <form action="{{ route('user.ats.kinerja.punishment.destroy', [$userId, $p->id]) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-soft-danger btn-sm" onclick="return confirm('Hapus?')"><i class="ri-delete-bin-line"></i></button></form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i class="ri-shield-star-line text-muted" style="font-size:3rem;"></i>
                                        <h5 class="fw-semibold text-dark mt-2 mb-1">Belum ada data punishment</h5>
                                        <p class="text-muted mb-0 small">Klik <strong>Tambah Punishment</strong> untuk mencatat pelanggaran GTK.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($punishments->hasPages())
            <div class="card-footer border-top-dashed bg-transparent no-print">
                {{ $punishments->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection