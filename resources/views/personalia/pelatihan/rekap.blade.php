{{-- Pelatihan: Rekapitulasi --}}
@extends('layouts.master')
@section('title') Rekap Pelatihan @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#eef2ff 0%,#f5f7ff 100%);border:1px solid #c7d2fe;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1e1b4b 0%,#1e1a2e 100%);border-color:#4338ca}
.stat-card{transition:all .25s ease;cursor:default}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:max-content;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:12px 16px;word-break:break-word}
.table-freeze th:first-child,.table-freeze td:first-child{position:sticky;left:0;z-index:10;background:#fff;min-width:200px;box-shadow:2px 0 5px rgba(0,0,0,.05)}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
[data-bs-theme="dark"] .table-freeze th:first-child,[data-bs-theme="dark"] .table-freeze td:first-child{background:#1e293b}
[data-bs-theme="dark"] .table-freeze thead th{background:#1e293b}
[data-bs-theme="dark"] .form-control,[data-bs-theme="dark"] .form-select{background:#1e293b;color:#e2e8f0;border-color:#334155}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Personalia @endslot
    @slot('li_2') Pelatihan @endslot
    @slot('title') Rekapitulasi @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Rekapitulasi Pelatihan</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Ringkasan dan statistik program pelatihan GTK per periode.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" action="{{ route('user.ats.pelatihan.rekap', ['userId' => $userId]) }}" class="d-flex align-items-center gap-2">
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                    <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
        <a href="{{ route('user.ats.pelatihan.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Daftar
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-indigo-subtle rounded fs-2"><i class="ri-book-open-line text-indigo"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Program</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total_pelatihan'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-team-line text-success"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Peserta</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total_peserta'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-money-dollar-circle-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Biaya</p>
                        <h3 class="fw-bold ff-secondary mb-0">Rp {{ number_format($stats['total_biaya'] ?? 0, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-bar-chart-line text-primary"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Tingkat Penyelesaian</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['completion_rate'] ?? 0 }}%</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Per-bulan breakdown --}}
@if($monthly && $monthly->count())
<div class="card mb-4">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-calendar-event-line text-indigo me-1"></i> Per Bulan</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="text-center">Jumlah Program</th>
                    <th class="text-center">Total Peserta</th>
                    <th class="text-end">Total Biaya</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthly as $bulan => $data)
                <tr>
                    <td class="fw-medium">{{ $bulan }}</td>
                    <td class="text-center"><span class="badge bg-indigo-subtle text-indigo">{{ $data['count'] }}</span></td>
                    <td class="text-center">{{ $data['peserta'] }} org</td>
                    <td class="text-end">Rp {{ number_format($data['biaya'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Top Peserta --}}
@if($topParticipants && $topParticipants->count())
<div class="card mb-4">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-star-line text-indigo me-1"></i> GTK Paling Aktif</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th>No</th>
                    <th>GTK</th>
                    <th class="text-center">Total Pelatihan</th>
                    <th class="text-center">Hadir</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topParticipants as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-xs rounded-circle bg-indigo-subtle text-indigo d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem">
                                {{ strtoupper(substr($item['gtk']->nama ?? 'G', 0, 1)) }}
                            </div>
                            <span class="fw-medium">{{ $item['gtk']->nama ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $item['total'] }}</span></td>
                    <td class="text-center">{{ $item['hadir'] }} org</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Daftar pelatihan --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-file-chart-line text-indigo me-1"></i> Detail Pelatihan {{ $tahun }}</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Pelatihan</th>
                    <th>Jenis</th>
                    <th>Tanggal</th>
                    <th>Tempat</th>
                    <th class="text-center">Peserta</th>
                    <th>Biaya</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pelatihans as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><span class="fw-medium">{{ $item->nama }}</span></td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($item->kategori) }}</span>
                        <span class="badge bg-light text-dark ms-1">{{ ucfirst($item->jenis) }}</span>
                    </td>
                    <td class="text-muted" style="font-size:.85rem">
                        {{ $item->tanggal_mulai ? Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y') : '-' }}
                        @if($item->tanggal_selesai && $item->tanggal_selesai != $item->tanggal_mulai)
                            - {{ Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y') }}
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:.85rem">{{ $item->lokasi ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge bg-info-subtle text-info">{{ $item->pesertas->count() }} org</span>
                    </td>
                    <td class="text-muted" style="font-size:.85rem">
                        {{ $item->biaya ? 'Rp ' . number_format($item->biaya, 0, ',', '.') : '-' }}
                    </td>
                    <td>
                        @if($item->status === 'selesai')
                            <span class="badge bg-success-subtle text-success">Selesai</span>
                        @elseif($item->status === 'ditetapkan')
                            <span class="badge bg-primary-subtle text-primary">Ditetapkan</span>
                        @elseif($item->status === 'dibatalkan')
                            <span class="badge bg-danger-subtle text-danger">Dibatalkan</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="999" class="text-center py-5">
                    <i class="ri-inbox-line" style="font-size:3rem;color:#9ca3af"></i>
                    <h6 class="mt-2 text-muted">Belum ada data</h6>
                    <p class="text-muted" style="font-size:.8rem">Data akan muncul di sini ketika sudah ada.</p>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection