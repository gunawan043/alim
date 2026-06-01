{{-- Cuti: Rekap Cuti GTK --}}
@extends('layouts.master')
@section('title') Rekap Cuti @endsection

@push('css')
<style>
    .table-freeze { table-layout: auto; min-width: 900px; width: 100%; margin-bottom: 0; }
    .table-freeze th, .table-freeze td { vertical-align: middle; padding: 11px 14px; word-break: break-word; }
    .table-freeze th:first-child, .table-freeze td:first-child { position: sticky; left: 0; z-index: 10; background: #fff; min-width: 180px; box-shadow: 2px 0 5px rgba(0,0,0,0.05); }
    .table-freeze thead th { position: sticky; top: 0; z-index: 20; font-weight: 600; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .table-freeze tbody tr:hover td { background: #f8f9ff; }
    [data-bs-theme="dark"] .table-freeze thead th { background: #1a1f3a; }
    [data-bs-theme="dark"] .table-freeze th:first-child,
    [data-bs-theme="dark"] .table-freeze td:first-child { background: #1a1f3a; }
    .stat-card { transition: all 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
    .page-header-card { background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%); padding: 1.25rem 1.5rem; border: 1px solid #e4e7f5 !important; }
    .filter-bar { background: #fff; }
    [data-bs-theme="dark"] .page-header-card { background: linear-gradient(135deg, #1a1f3a 0%, #1e2445 100%); border-color: #2a3055 !important; }
    [data-bs-theme="dark"] .filter-bar { background: #1a1f3a; border-color: #2a3055 !important; }
    @media print { .no-print { display: none !important; } }
    .badge-status { font-size: 0.78rem; padding: 0.35em 0.7em; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- Page Header Card --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-header-card rounded-3 border-0 shadow-sm">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="page-header-icon" style="background:#e8f5e9;color:#2e7d32;">
                            <i class="ri-file-chart-line fs-4"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold text-dark mb-0">Rekap Cuti GTK</h4>
                            <p class="text-muted mb-0 small">Ringkasan pengajuan cuti per periode. Lihat semua pengajuan cuti yang telah disetujui, ditolak, atau masih menunggu.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 no-print">
                        <a href="{{ route('cuti.approval') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card" style="border-left:3px solid #2e7d32;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title rounded-3 fs-2" style="background:#e8f5e9;">
                                <i class="ri-file-list-3-line" style="color:#2e7d32;"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Total Pengajuan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $totalCount ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card" style="border-left:3px solid #198754;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded-3 fs-2">
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Disetujui</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $approvedCount ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card" style="border-left:3px solid #dc3545;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded-3 fs-2">
                                <i class="ri-close-circle-line text-danger"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Ditolak</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $rejectedCount ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card" style="border-left:3px solid #f5a623;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded-3 fs-2">
                                <i class="ri-hourglass-line text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Menunggu</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $pendingCount ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar rounded-2 border p-3 mb-3 no-print">
        <form method="GET" action="{{ route('cuti.rekap') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label for="filter_tahun" class="form-label small text-muted mb-1">Tahun</label>
                <select name="tahun" id="filter_tahun" class="form-select form-select-sm">
                    <option value="">Semua Tahun</option>
                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter_bulan" class="form-label small text-muted mb-1">Bulan</label>
                <select name="bulan" id="filter_bulan" class="form-select form-select-sm">
                    <option value="">Semua Bulan</option>
                    @foreach([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April',   5 => 'Mei',      6 => 'Juni',
                        7 => 'Juli',    8 => 'Agustus',  9 => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ] as $num => $name)
                        <option value="{{ $num }}" {{ request('bulan') == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter_jenis" class="form-label small text-muted mb-1">Jenis Cuti</label>
                <select name="jenis" id="filter_jenis" class="form-select form-select-sm">
                    <option value="">Semua Jenis</option>
                    <option value="Cuti Tahunan"              {{ request('jenis') == 'Cuti Tahunan'              ? 'selected' : '' }}>Cuti Tahunan</option>
                    <option value="Cuti Besar"                {{ request('jenis') == 'Cuti Besar'                ? 'selected' : '' }}>Cuti Besar</option>
                    <option value="Cuti Sakit"                {{ request('jenis') == 'Cuti Sakit'                ? 'selected' : '' }}>Cuti Sakit</option>
                    <option value="Cuti Melanjutkan"           {{ request('jenis') == 'Cuti Melanjutkan'           ? 'selected' : '' }}>Cuti Melanjutkan</option>
                    <option value="Cuti Karena Alasan Penting" {{ request('jenis') == 'Cuti Karena Alasan Penting' ? 'selected' : '' }}>Cuti Karena Alasan Penting</option>
                    <option value="Izin"                      {{ request('jenis') == 'Izin'                      ? 'selected' : '' }}>Izin</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_gtk" class="form-label small text-muted mb-1">Nama GTK</label>
                <input type="text" name="gtk" id="filter_gtk" class="form-control form-control-sm" placeholder="Cari nama GTK..." value="{{ request('gtk') }}">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="ri-search-line me-1"></i> Filter</button>
                <a href="{{ route('cuti.rekap') }}" class="btn btn-outline-secondary btn-sm"><i class="ri-close-line"></i> Reset</a>
            </div>
        </form>
    </div>

    {{-- Main Table Card --}}
    <div class="card">
        <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-table-2 text-primary me-1"></i> Daftar Pengajuan Cuti</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="ri-printer-line"></i> Print</button>
                <a href="{{ route('cuti.rekap.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm"><i class="ri-file-pdf-line me-1"></i> PDF</a>
                <a href="{{ route('cuti.rekap.excel', request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="ri-file-excel-line me-1"></i> Excel</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Nama GTK</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal</th>
                        <th width="80">Durasi</th>
                        <th width="110">Status</th>
                        <th>Disetujui Oleh</th>
                        <th>Tgl Persetujuan</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalHariApproved = 0; $totalHariRejected = 0; @endphp
                    @forelse($rekapList ?? [] as $item)
                        @php
                            $status = strtolower($item->status ?? 'pending');
                            if ($status == 'approved' || $status == 'disetujui') {
                                $totalHariApproved += ($item->jumlah_hari ?? 0);
                            } elseif ($status == 'rejected' || $status == 'ditolak') {
                                $totalHariRejected += ($item->jumlah_hari ?? 0);
                            }
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration + (($rekapList->currentPage() - 1) * $rekapList->perPage())) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:0.7rem">
                                        {{ strtoupper(substr($item->gtk->nama ?? 'G', 0, 1)) }}
                                    </div>
                                    <span class="fw-medium">{{ $item->gtk->nama ?? '-' }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">{{ $item->jenis_cuti ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="small">{{ $item->tanggal_mulai ? \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y') : '-' }}</span>
                                <span class="text-muted mx-1">-</span>
                                <span class="small">{{ $item->tanggal_selesai ? \Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y') : '-' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $item->jumlah_hari ?? 0 }} hari</span>
                            </td>
                            <td>
                                @if($status == 'approved' || $status == 'disetujui')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                        <i class="ri-checkbox-circle-line me-1"></i> Disetujui
                                    </span>
                                @elseif($status == 'rejected' || $status == 'ditolak')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                        <i class="ri-close-circle-line me-1"></i> Ditolak
                                    </span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">
                                        <i class="ri-time-line me-1"></i> Menunggu
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="small text-muted">{{ $item->approvedByUser->name ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="small text-muted">
                                    {{ $item->approved_at ? \Carbon\Carbon::parse($item->approved_at)->format('d/m/Y') : '-' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <i class="ri-inbox-line text-muted" style="font-size:3rem;"></i>
                                    <h5 class="fw-semibold text-dark mt-2 mb-1">Belum ada data</h5>
                                    <p class="text-muted mb-0 small">Data akan muncul di sini</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(isset($rekapList) && !$rekapList->isEmpty())
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end pe-3">Total Hari Disetujui:</td>
                            <td><span class="badge bg-success-subtle text-success border border-success">{{ $totalHariApproved }} hari</span></td>
                            <td colspan="2" class="text-end pe-3">Total Hari Ditolak:</td>
                            <td><span class="badge bg-danger-subtle text-danger border border-danger">{{ $totalHariRejected }} hari</span></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if(isset($rekapList) && $rekapList->hasPages())
            <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center no-print">
                <span class="text-muted small">
                    Menampilkan {{ $rekapList->firstItem() ?? 0 }} - {{ $rekapList->lastItem() ?? 0 }}
                    dari {{ $rekapList->total() }} data
                </span>
                <nav>{{ $rekapList->appends(request()->query())->links() }}</nav>
            </div>
        @endif
    </div>

</div>
@endsection