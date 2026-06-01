{{-- Kalender Kegiatan: Akademik --}}
@extends('layouts.master')
@section('title') Kalender Akademik @endsection

@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.page-header-card{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:1px solid #bbf7d0;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#052e16 0%,#0a2e1a 100%);border-color:#166534}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#22c55e18;color:#16a34a;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-calendar-event-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Kalender Akademik</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Jadwal dan timeline kegiatan akademik tahun ajaran</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.kalender-kegiatan.kontrak', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-file-paper-2-line me-1"></i> Kontrak
        </a>
        <a href="{{ route('user.kalender-kegiatan.evaluasi', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-clipboard-line me-1"></i> Evaluasi GTK
        </a>
        <a href="{{ route('user.kalender-kegiatan.training', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-graduation-cap-line me-1"></i> Training
        </a>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #16a34a;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title rounded-3 fs-2" style="background:#22c55e18;">
                            <i class="ri-file-list-3-line" style="color:#16a34a;"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Total Kegiatan</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalKegiatan ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #2563eb;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded-3 fs-2">
                            <i class="ri-calendar-check-line text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Berlangsung</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $berlangsung ?? 0 }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Selesai</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $selesai ?? 0 }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Akan Datang</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $akanDatang ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar rounded-2 border p-3 mb-3 no-print">
    <form method="GET" action="{{ route('user.kalender-kegiatan.akademik', $userId) }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Bulan</label>
            <select name="bulan" class="form-select form-select-sm">
                <option value="">Semua Bulan</option>
                @foreach([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $num=>$name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Jenis Kegiatan</label>
            <select name="jenis" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="ujian">Ujian</option>
                <option value="pelatihan">Pelatihan</option>
                <option value="evaluasi">Evaluasi</option>
                <option value="kegiatan">Kegiatan Sekolah</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-1">
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
            <a href="{{ route('user.kalender-kegiatan.akademik', $userId) }}" class="btn btn-light btn-sm"><i class="ri-reset-right-line me-1"></i>Reset</a>
        </div>
        <div class="col-md-3 d-flex align-items-end justify-content-end">
            <button onclick="window.print()" class="btn btn-light btn-sm"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-table-2 text-primary me-1"></i> Jadwal Kegiatan Akademik</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th class="bg-light text-center" style="width:48px">No</th>
                    <th class="bg-light">Nama Kegiatan</th>
                    <th class="bg-light">Tanggal Mulai</th>
                    <th class="bg-light">Tanggal Selesai</th>
                    <th class="bg-light">Penanggung Jawab</th>
                    <th class="bg-light text-center">Status</th>
                    <th class="bg-light text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kegiatanList ?? [] as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="fw-medium">{{ $item['nama'] ?? '-' }}</td>
                        <td>{{ $item['tanggal_mulai'] ?? '-' }}</td>
                        <td>{{ $item['tanggal_selesai'] ?? '-' }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($item['pj'] ?? 'P', 0, 1)) }}
                                </div>
                                <span class="small">{{ $item['pj'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            @php $status = $item['status'] ?? 'akan_datang'; @endphp
                            @if($status == 'berlangsung')
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">Berlangsung</span>
                            @elseif($status == 'selesai')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Selesai</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Akan Datang</span>
                            @endif
                        </td>
                        <td class="text-center no-print">
                            <a href="{{ route('user.kalender-kegiatan.akademik', $userId) }}" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div style="color:#22c55e;opacity:.4"><i class="ri-calendar-event-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Belum ada data</h5>
                            <p class="text-muted mb-0 small">Jadwal kegiatan akademik akan muncul di sini</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($kegiatanList) && method_exists($kegiatanList, 'hasPages') && $kegiatanList->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center no-print">
            <span class="text-muted small">Menampilkan {{ $kegiatanList->firstItem() ?? 0 }} - {{ $kegiatanList->lastItem() ?? 0 }} dari {{ $kegiatanList->total() }} data</span>
            <nav>{{ $kegiatanList->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>
@endsection