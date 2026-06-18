{{-- Analisis GTK: Gap Analysis — Beban Kerja & Coverage --}}
@extends('layouts.master')
@section('title') Gap Analysis GTK @endsection

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
            <i class="ri-scales-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Gap Analysis GTK</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Perbandingan kebutuhan jam mengajar per mapel vs realisasi penugasan</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.analisis-gtk.rasio-ideal', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-pie-chart-2-line me-1"></i> Rasio Ideal
        </a>
        <a href="{{ route('user.analisis-gtk.beban-kerja', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-bar-chart-2-line me-1"></i> Beban Kerja
        </a>
        <a href="{{ route('user.analisis-gtk.proyeksi', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-line-chart-line me-1"></i> Proyeksi SDM
        </a>
        <button id="btnTriggerAnalysis" class="btn btn-primary btn-sm" title="Jalankan analisis ulang">
            <i class="ri-refresh-line me-1"></i> Analisis Ulang
        </button>
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
                            <i class="ri-book-open-line" style="color:#4f46e5;"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Total Mapel</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalKompetensi ?? 0 }}</h3>
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
                            <i class="ri-alarm-warning-line text-danger"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Defisit Guru</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $gapNegatif ?? 0 }}</h3>
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
                            <i class="ri-check-line text-warning"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Sesuai Standar</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $sesuaiStandar ?? 0 }}</h3>
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
                            <i class="ri-arrow-up-line text-success"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px;">Surplus Guru</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $diBawahStandar ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-scales-line text-primary me-1"></i> Gap per Mata Pelajaran</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th class="bg-light text-center" style="width:48px">No</th>
                    <th class="bg-light">Mata Pelajaran</th>
                    <th class="bg-light text-center">Jam Dibutuhkan</th>
                    <th class="bg-light text-center">Jam Tersedia</th>
                    <th class="bg-light text-center">Gap</th>
                    <th class="bg-light text-center">Guru</th>
                    <th class="bg-light text-center">Status</th>
                    <th class="bg-light text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gapList ?? [] as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($item['nama_gtk'] ?? 'M', 0, 1)) }}
                                </div>
                                <span class="fw-medium">{{ $item['nama_gtk'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="text-center fw-medium">{{ number_format($item['standar'] ?? 0, 1) }}</td>
                        <td class="text-center fw-medium">{{ number_format($item['aktual'] ?? 0, 1) }}</td>
                        <td class="text-center">
                            @php $gap = ($item['aktual'] ?? 0) - ($item['standar'] ?? 0); @endphp
                            @if($gap < 0)
                                <span class="gap-negatif">-{{ abs($gap) }}</span>
                            @elseif($gap == 0)
                                <span class="gap-netral">0</span>
                            @else
                                <span class="gap-positif">+{{ number_format($gap, 1) }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $item['teacher_count'] ?? '-' }}</td>
                        <td class="text-center">
                            @if($item['status'] === 'deficit')
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Defisit Guru</span>
                            @elseif($item['status'] === 'uncovered')
                                <span class="badge bg-dark bg-opacity-10 text-dark border border-dark">Belum Ada Guru</span>
                            @elseif($item['status'] === 'surplus')
                                <span class="badge bg-info bg-opacity-10 text-info border border-info">Surplus Guru</span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Cukup</span>
                            @endif
                        </td>
                        <td class="text-center no-print">
                            <button class="btn btn-sm btn-light detail-toggle" data-gap="{{ $gap }}" data-status="{{ $item['status'] }}">
                                <i class="ri-eye-line"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div style="color:#6366f1;opacity:.4"><i class="ri-arrow-right-circle-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Belum ada data</h5>
                            <p class="text-muted mb-0 small">Data gap analysis akan muncul setelah analisis dijalankan</p>
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
        const gap = parseFloat(this.dataset.gap);
        const status = this.dataset.status;
        let msg = '';
        if (status === 'deficit') msg = `Mapel ini kekurangan ${Math.abs(gap)} jam/minggu. Perlu penugasan guru tambahan.`;
        else if (status === 'uncovered') msg = `Mapel ini belum memiliki guru yang mengajar.`;
        else if (status === 'surplus') msg = `Mapel ini memiliki surplus guru. Evaluasi distribusi beban.`;
        else msg = `Mapel ini cukup guru dan jam mengajar.`;
        alert(msg);
    });
});

document.getElementById('btnTriggerAnalysis').addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<i class="ri-loader-4-line me-1 spinner"></i> Menganalisis...';
    fetch('{{ route("user.analisis-gtk.analyze", $userId) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'processing') {
            setTimeout(() => location.reload(), 2000);
        } else {
            alert('Analisis selesai!');
            location.reload();
        }
    })
    .catch(() => {
        alert('Terjadi kesalahan.');
        this.disabled = false;
    });
});
</script>
@endpush
@endsection
