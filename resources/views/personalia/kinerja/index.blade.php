@extends('layouts.master')
@section('title') Penilaian Kinerja @endsection
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
.score-bar{height:6px;border-radius:3px;background:#e2e8f0;overflow:hidden}
.score-bar-fill{height:100%;border-radius:3px;transition:width .6s ease}
</style>
@endpush

@section('content')
@php
$userId = request()->route('userId') ?? Auth::id();
$currentUser = auth()->user();
$isAdmin = $currentUser->hasAnyRole(['Personalia','Super Admin','Admin Tata Usaha']);
$totalPenilaian = \App\Models\KinerjaPenilaian::count();
$aktifPeriode = \App\Models\KinerjaPeriode::where('status','aktif')->count();
$avgSkor = \App\Models\KinerjaPenilaian::whereNotNull('total_skor')->avg('total_skor') ?? 0;
$topGtk = \App\Models\KinerjaPenilaian::with('user')->whereNotNull('total_skor')->orderByDesc('total_skor')->first();
@endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#f9731618;color:#ea580c;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-bar-chart-box-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Penilaian Kinerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">
                @if($isAdmin) Kelola dan pantau kinerja seluruh GTK
                @else Riwayat penilaian kinerja Anda
                @endif
            </p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.ats.kinerja.periode', $userId) }}" class="btn btn-light btn-sm"><i class="ri-calendar-line me-1"></i>Periode</a>
        <a href="{{ route('user.ats.kinerja.indikator', $userId) }}" class="btn btn-light btn-sm"><i class="ri-list-checks me-1"></i>Indikator</a>
        <a href="{{ route('user.ats.kinerja.reward', $userId) }}" class="btn btn-light btn-sm"><i class="ri-medal-line me-1"></i>Reward</a>
        <a href="{{ route('user.ats.kinerja.laporan', $userId) }}" class="btn btn-light btn-sm"><i class="ri-file-chart-line me-1"></i>Laporan</a>
        @if($isAdmin)
        <a href="{{ route('user.ats.kinerja.create', $userId) }}" class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i>Penilaian Baru</a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-file-chart-line text-primary"></i></span></div>
                    <div class="flex-grow-1 min-width-0">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Total Penilaian</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalPenilaian }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-calendar-check-line text-success"></i></span></div>
                    <div class="flex-grow-1 min-width-0">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Periode Aktif</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $aktifPeriode }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-info">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-star-line text-info"></i></span></div>
                    <div class="flex-grow-1 min-width-0">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Rata-rata Skor</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($avgSkor,1) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-medal-line text-warning"></i></span></div>
                    <div class="flex-grow-1 min-width-0">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">GTK Tertinggi</p>
                        <h3 class="fw-bold ff-secondary mb-0" style="font-size:.9rem">{{ $topGtk?->user?->name ?? '-' }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card no-print">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label mb-0" style="font-size:.78rem">Periode</label>
                <select name="periode" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach(\App\Models\KinerjaPeriode::orderBy('tanggal_mulai','desc')->get() as $p)
                    <option value="{{ $p->id }}" {{ request('periode')==$p->id?'selected':'' }}>{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0" style="font-size:.78rem">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Draft</option>
                    <option value="dinilai" {{ request('status')=='dinilai'?'selected':'' }}>Dinilai</option>
                    <option value="rekon" {{ request('status')=='rekon'?'selected':'' }}>Rekonsiliasi</option>
                    <option value="final" {{ request('status')=='final'?'selected':'' }}>Final</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0" style="font-size:.78rem">Nilai Huruf</label>
                <select name="nilai" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="A" {{ request('nilai')=='A'?'selected':'' }}>A (≥90)</option>
                    <option value="B" {{ request('nilai')=='B'?'selected':'' }}>B (80-89)</option>
                    <option value="C" {{ request('nilai')=='C'?'selected':'' }}>C (70-79)</option>
                    <option value="D" {{ request('nilai')=='D'?'selected':'' }}>< 80</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0" style="font-size:.78rem">Cari Nama</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Nama GTK...">
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="ri-filter-line me-1"></i>Filter</button>
                    <a href="{{ route('user.ats.kinerja.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-restart-line"></i></a>
                    <button onclick="window.print()" class="btn btn-light btn-sm"><i class="ri-printer-line"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-list-check text-primary me-1"></i> Daftar Penilaian</h5>
        <div class="d-flex gap-2 no-print">
            <button class="btn btn-outline-secondary btn-sm" onclick="exportTable('csv')"><i class="ri-download-line"></i> Export</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze" id="kinerjaTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>GTK</th>
                        <th>Periode</th>
                        <th>Total Skor</th>
                        <th>Nilai</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th class="no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($penilaians as $i => $p)
                    <tr>
                        <td class="text-center text-muted" style="width:40px">{{ $penilaians->firstItem() + $i }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-semibold" style="font-size:.75rem">{{ strtoupper(substr($p->user->name??'?',0,1)) }}</div>
                                <span class="fw-semibold">{{ $p->user->name ?? '-' }}</span>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark">{{ $p->periode->nama ?? '-' }}</span></td>
                        <td>
                            @if($p->total_skor)
                            <div class="d-flex align-items-center gap-2">
                                <div class="score-bar" style="width:80px"><div class="score-bar-fill" style="width:{{ $p->total_skor }}%;background:{{ $p->total_skor>=80?'#22c55e':($p->total_skor>=70?'#f59e0b':'#ef4444') }}"></div></div>
                                <span class="fw-semibold">{{ number_format($p->total_skor,1) }}</span>
                            </div>
                            @else<span class="text-muted">-</span>@endif
                        </td>
                        <td><span class="badge {{ $p->nilai_huruf=='A'?'bg-success-subtle text-success':($p->nilai_huruf=='B'?'bg-primary-subtle text-primary':'bg-secondary-subtle text-secondary') }} badge-status fw-bold">{{ $p->nilai_huruf ?? '-' }}</span></td>
                        <td><span class="badge bg-info-subtle text-info badge-status">{{ $p->kategori_hasil ?? '-' }}</span></td>
                        <td>
                            @switch($p->status)
                                @case('draft')<span class="badge bg-secondary-subtle text-secondary badge-status"><i class="ri-edit-line me-1"></i>Draft</span>@break
                                @case('dinilai')<span class="badge bg-primary-subtle text-primary badge-status"><i class="ri-check-line me-1"></i>Dinilai</span>@break
                                @case('rekon')<span class="badge bg-warning-subtle text-warning badge-status"><i class="ri-chat-check-line me-1"></i>Rekon</span>@break
                                @case('final')<span class="badge bg-success-subtle text-success badge-status"><i class="ri-checkbox-circle-line me-1"></i>Final</span>@break
                            @endswitch
                        </td>
                        <td class="no-print">
                            <div class="d-flex gap-1">
                                <a href="{{ route('user.ats.kinerja.show', [$userId, $p->id]) }}" class="btn btn-soft-primary btn-sm" title="Detail"><i class="ri-eye-line"></i></a>
                                @if($isAdmin)
                                <a href="{{ route('user.ats.kinerja.edit', [$userId, $p->id]) }}" class="btn btn-soft-warning btn-sm" title="Edit"><i class="ri-edit-2-line"></i></a>
                                <form action="{{ route('user.ats.kinerja.destroy', [$userId, $p->id]) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-soft-danger btn-sm" title="Hapus" onclick="return confirm('Hapus penilaian ini?')"><i class="ri-delete-bin-line"></i></button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <i class="ri-bar-chart-box-line text-muted" style="font-size:3rem;"></i>
                                <h5 class="fw-semibold text-dark mt-2 mb-1">Belum ada data penilaian kinerja</h5>
                                <p class="text-muted mb-0 small">
                                    @if($isAdmin)
                                    Klik <strong>Penilaian Baru</strong> untuk membuat penilaian kinerja GTK.
                                    @else
                                    Penilaian kinerja akan ditampilkan di sini setelah diinput oleh Personalia.
                                    @endif
                                </p>
                                @if($isAdmin)<a href="{{ route('user.ats.kinerja.create', $userId) }}" class="btn btn-primary btn-sm mt-2"><i class="ri-add-line me-1"></i> Penilaian Baru</a>@endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($penilaians->hasPages())
    <div class="card-footer border-top-dashed bg-transparent no-print">
        <div class="d-flex justify-content-between align-items-center px-2">
            <p class="text-muted mb-0" style="font-size:.8rem">Menampilkan {{ $penilaians->firstItem() }}–{{ $penilaians->lastItem() }} dari {{ $penilaians->total() }} data</p>
            {{ $penilaians->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
@endsection