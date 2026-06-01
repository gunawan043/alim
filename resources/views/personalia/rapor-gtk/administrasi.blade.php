{{-- Rapor GTK: Penilaian Administrasi --}}
@extends('layouts.master')
@section('title') Penilaian Administrasi @endsection

@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.page-header-card{background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);border:1px solid #bfdbfe;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#0a1a2e 0%,#0f2040 100%);border-color:#1e40af}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#3b82f618;color:#2563eb;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-file-list-3-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Penilaian Administrasi GTK</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Evaluasi kerapihan dokumen, laporan, dan administrasi GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.rapor-gtk.tahunan', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-file-chart-line me-1"></i> Rekap Tahunan
        </a>
        <a href="{{ route('user.rapor-gtk.akademik', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-book-open-line me-1"></i> Akademik
        </a>
        <a href="{{ route('user.rapor-gtk.disiplin', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-shield-check-line me-1"></i> Disiplin
        </a>
        <a href="{{ route('user.rapor-gtk.kepribadian', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-user-heart-line me-1"></i> Kepribadian
        </a>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #2563eb;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title rounded-3 fs-2" style="background:#3b82f618;">
                            <i class="ri-team-line" style="color:#2563eb;"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Total GTK</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalGtk ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #16a34a;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded-3 fs-2">
                            <i class="ri-checkbox-circle-line text-success"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Lengkap</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $dokumenLengkap ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #d97706;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-3 fs-2">
                            <i class="ri-time-line text-warning"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Belum Lengkap</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $dokumenBelumLengkap ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #dc2626;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded-3 fs-2">
                            <i class="ri-error-warning-line text-danger"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Nilai Admin</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $nilaiAdmin ?? '0' }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar rounded-2 border p-3 mb-3 no-print">
    <form method="GET" action="{{ route('user.rapor-gtk.administrasi', $userId) }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Nama GTK</label>
            <select name="gtk_id" class="form-select form-select-sm">
                <option value="">Semua GTK</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Kelengkapan</label>
            <select name="kelengkapan" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="lengkap">Lengkap</option>
                <option value="belum_lengkap">Belum Lengkap</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-1">
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
            <a href="{{ route('user.rapor-gtk.administrasi', $userId) }}" class="btn btn-light btn-sm"><i class="ri-reset-right-line me-1"></i>Reset</a>
        </div>
        <div class="col-md-3 d-flex align-items-end justify-content-end">
            <button onclick="window.print()" class="btn btn-light btn-sm"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-table-2 text-primary me-1"></i> Daftar Penilaian Administrasi GTK</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th class="bg-light text-center" style="width:48px">No</th>
                    <th class="bg-light">Nama GTK</th>
                    <th class="bg-light">Kelengkapan Dokumen</th>
                    <th class="bg-light text-center">Rapor</th>
                    <th class="bg-light text-center">Nilai Admin</th>
                    <th class="bg-light text-center">Predikat</th>
                    <th class="bg-light text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($administrasiList ?? [] as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($item['nama'] ?? 'G', 0, 1)) }}
                                </div>
                                <span class="fw-medium">{{ $item['nama'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td>
                            @php $dok = $item['kelengkapan'] ?? 'belum_lengkap'; @endphp
                            @if($dok == 'lengkap')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Lengkap</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Belum</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $item['rapor'] ?? '-' }}</td>
                        <td class="text-center fw-bold">{{ $item['nilai'] ?? '-' }}</td>
                        <td class="text-center">
                            @php $predikat = $item['predikat'] ?? 'C'; @endphp
                            @if($predikat == 'A')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">A</span>
                            @elseif($predikat == 'B')
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">B</span>
                            @elseif($predikat == 'C')
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">C</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">D</span>
                            @endif
                        </td>
                        <td class="text-center no-print">
                            <a href="{{ route('user.rapor-gtk.administrasi', $userId) }}" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div style="color:#3b82f6;opacity:.4"><i class="ri-file-list-3-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Belum ada data</h5>
                            <p class="text-muted mb-0 small">Data penilaian administrasi GTK akan muncul di sini</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($administrasiList) && method_exists($administrasiList, 'hasPages') && $administrasiList->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center no-print">
            <span class="text-muted small">Menampilkan {{ $administrasiList->firstItem() ?? 0 }} - {{ $administrasiList->lastItem() ?? 0 }} dari {{ $administrasiList->total() }} data</span>
            <nav>{{ $administrasiList->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>
@endsection
