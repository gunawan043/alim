{{-- Analisis GTK: Beban Kerja / Workload Analysis --}}
@extends('layouts.master')
@section('title') Beban Kerja GTK @endsection

@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.page-header-card{background:linear-gradient(135deg,#eef2ff 0%,#e0e7ff 100%);border:1px solid #c7d2fe;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1a0f00 0%,#1f1500 100%);border-color:#92400e}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
.gap-negatif{color:#dc2626;font-weight:700}
.gap-netral{color:#d97706;font-weight:700}
.gap-positif{color:#16a34a;font-weight:700}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#6366f118;color:#4f46e5;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-bar-chart-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Beban Kerja GTK</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Analisis beban mengajar per GTK: jam aktual vs standar ideal</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.analisis-gtk.rasio-ideal', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-pie-chart-2-line me-1"></i> Rasio Ideal
        </a>
        <a href="{{ route('user.analisis-gtk.proyeksi', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-line-chart-line me-1"></i> Proyeksi SDM
        </a>
        <a href="{{ route('user.analisis-gtk.gap', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-right-circle-line me-1"></i> Gap Analysis
        </a>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #4f46e5;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title rounded-3 fs-2" style="background:#6366f118;">
                            <i class="ri-team-line" style="color:#4f46e5;"></i>
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
        <div class="card stat-card" style="border-left:3px solid #2563eb;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded-3 fs-2">
                            <i class="ri-time-line text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Total Jam/Mgg</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalJamMengajar ?? 0 }} <small class="fw-normal text-muted" style="font-size:.7rem">jam</small></h3>
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
                            <i class="ri-arrow-up-line text-danger"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Kelebihan Beban</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $bebanPerGuru->where('status','surplus')->count() ?? 0 }}</h3>
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
                            <i class="ri-check-line text-success"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Ideal (18-40 jam)</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $bebanPerGuru->where('status','balanced')->count() ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-table-2 text-primary me-1"></i> Beban Mengajar per GTK</h5>
        <span class="text-muted small">Standar ideal: 18-40 jam/minggu</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th class="bg-light text-center" style="width:48px">No</th>
                    <th class="bg-light">Nama GTK</th>
                    <th class="bg-light text-center">Jam Mengajar</th>
                    <th class="bg-light text-center">Jam Tugas Tambahan</th>
                    <th class="bg-light text-center">Total Jam</th>
                    <th class="bg-light text-center">Mapel</th>
                    <th class="bg-light text-center">Status</th>
                    <th class="bg-light text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bebanPerGuru ?? [] as $item)
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
                        <td class="text-center">{{ number_format($item['teaching'] ?? 0, 1) }}</td>
                        <td class="text-center">{{ number_format($item['additional'] ?? 0, 1) }}</td>
                        <td class="text-center fw-bold">{{ number_format($item['hours'] ?? 0, 1) }}</td>
                        <td class="text-center">{{ $item['details']['subject_count'] ?? '-' }}</td>
                        <td class="text-center">
                            @php
                                $h = $item['hours'] ?? 0;
                                if ($h <= 0) $badge = '<span class="badge bg-secondary bg-opacity-10 text-secondary">Belum Ditugaskan</span>';
                                elseif ($h > 40) $badge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Berlebih</span>';
                                elseif ($h >= 18) $badge = '<span class="badge bg-success bg-opacity-10 text-success border border-success">Ideal</span>';
                                else $badge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Underload</span>';
                            @endphp
                            {!! $badge !!}
                        </td>
                        <td class="text-center no-print">
                            <button class="btn btn-sm btn-light detail-toggle" data-hours="{{ $item['hours'] }}" data-status="{{ $item['status'] }}">
                                <i class="ri-eye-line"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div style="color:#6366f1;opacity:.4"><i class="ri-arrow-right-circle-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Belum ada data</h5>
                            <p class="text-muted mb-0 small">Data beban kerja GTK akan muncul di sini setelah analisis dijalankan</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.detail-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        const h = parseFloat(this.dataset.hours);
        const status = this.dataset.status;
        let msg = '';
        if (h <= 0) msg = 'GTK ini belum memiliki tugas mengajar.';
        else if (h > 40) msg = `GTK ini mengajar ${h} jam/minggu (beroverload, ideal maks 40 jam).`;
        else if (h >= 18) msg = `GTK ini mengajar ${h} jam/minggu (dalam batas ideal 18-40 jam).`;
        else msg = `GTK ini mengajar ${h} jam/minggu (di bawah standar minimal 18 jam).`;
        alert(msg);
    });
});
</script>
@endpush
@endsection
