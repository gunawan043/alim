{{-- Absensi GTK: Izin & Sakit --}}
@extends('layouts.master')
@section('title') Daftar Izin & Sakit GTK @endsection

@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.page-header-card{background:linear-gradient(135deg,#f0fdfa 0%,#ccfbf1 100%);border:1px solid #5eead4;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#042f2e 0%,#115e59 100%);border-color:#14b8a6}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Kehadiran GTK @endslot
    @slot('li_2') Izin & Sakit @endslot
    @slot('title') Daftar Izin & Sakit GTK @endslot
@endcomponent

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#14b8a618;color:#0d9488;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-file-list-2-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Izin & Sakit GTK</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Daftar pengajuan izin dan cuti sakit seluruh GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="{{ route('user.absensi-gtk.harian', $userId) }}" class="btn btn-light btn-sm"><i class="ri-calendar-check-line me-1"></i>Kehadiran Harian</a>
        <a href="{{ route('user.absensi-gtk.rekap-bulanan', $userId) }}" class="btn btn-light btn-sm"><i class="ri-file-chart-line me-1"></i>Rekap Bulanan</a>
        <a href="{{ route('user.absensi-gtk.index', $userId) }}" class="btn btn-outline-primary btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #0891b2">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded-3 fs-2"><i class="ri-information-line text-info"></i></span></div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px">Total Izin</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $izins->where('status','izin')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-left:3px solid #d97706">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded-3 fs-2"><i class="ri-hospital-line text-warning"></i></span></div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:0.5px">Total Sakit</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $izins->where('status','sakit')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-file-list-2-line text-teal me-1"></i> Daftar Izin & Sakit</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th class="bg-light" style="width:48px">No</th>
                    <th class="bg-light">GTK</th>
                    <th class="bg-light text-center">Tanggal</th>
                    <th class="bg-light text-center">Status</th>
                    <th class="bg-light">Lokasi</th>
                    <th class="bg-light">Keterangan</th>
                    <th class="bg-light text-center" style="width:100px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($izins as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($item->gtk?->nama ?? 'G', 0, 1)) }}
                                </div>
                                <span class="fw-medium">{{ $item->gtk?->nama ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="small">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</span>
                        </td>
                        <td class="text-center">
                            @php
                                $c = $item->status === 'izin' ? 'info' : 'warning';
                                $l = ucfirst($item->status);
                            @endphp
                            <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">{{ $l }}</span>
                        </td>
                        <td><span class="small">{{ $item->lokasi_masuk ?? '-' }}</span></td>
                        <td><span class="small text-muted">{{ isset($item->keterangan) && strlen($item->keterangan) > 60 ? substr($item->keterangan, 0, 60).'...' : ($item->keterangan ?? '-') }}</span></td>
                        <td class="text-center">
                            <a href="#" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div style="color:#14b8a6;opacity:.4"><i class="ri-file-list-2-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Belum ada data</h5>
                            <p class="text-muted mb-0 small">Data izin dan sakit GTK akan muncul di sini</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($izins, 'hasPages') && $izins->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Menampilkan {{ $izins->firstItem() ?? 0 }} - {{ $izins->lastItem() ?? 0 }} dari {{ $izins->total() }} data</span>
            <nav>{{ $izins->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>
@endsection
