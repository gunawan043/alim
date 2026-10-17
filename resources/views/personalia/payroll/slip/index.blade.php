{{-- Payroll Slip: Daftar Slip Gaji --}}
@extends('layouts.master')
@section('title') Slip Gaji GTK @endsection

@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.page-header-card{background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);border:1px solid #fbbf24;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1c1400 0%,#2a1e00 100%);border-color:#d97706}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Gaji & Kompensasi @endslot
    @slot('li_2') Slip Gaji @endslot
    @slot('title') Slip Gaji GTK @endslot
@endcomponent

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Slip Gaji GTK</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Lihat dan cetak slip gaji seluruh GTK.</p>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="{{ route('user.payroll.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-file-list-3-line me-1"></i>Data Gaji</a>
        <a href="{{ route('user.payroll.create', $userId) }}" class="btn btn-warning btn-sm"><i class="ri-add-line me-1"></i>Tambah Gaji</a>
        <a href="{{ route('user.payroll.settings', $userId) }}" class="btn btn-outline-dark btn-sm"><i class="ri-settings-3-line me-1"></i>Pengaturan</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-4 col-6">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-file-list-3-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Slip</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $slips->total() ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-6">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-draft-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Draft</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['draft_count'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-6">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-payment-line text-success"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Dibayar</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['paid_count'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-6">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-money-dollar-circle-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Gaji</p>
                        <h3 class="fw-bold ff-secondary mb-0">Rp {{ number_format($stats['gaji_bersih_total'] ?? 0, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-file-list-3-line text-warning me-1"></i> Daftar Slip Gaji</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th class="bg-light" style="width:48px">No</th>
                    <th class="bg-light">GTK</th>
                    <th class="bg-light">Periode</th>
                    <th class="bg-light">Gaji Pokok</th>
                    <th class="bg-light text-end">Tunjangan</th>
                    <th class="bg-light text-end">Potongan</th>
                    <th class="bg-light text-end">Bersih</th>
                    <th class="bg-light text-center">Status</th>
                    <th class="bg-light text-center" style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slips as $item)
                    <tr>
                        <td class="text-center">{{ $slips->firstItem() + $loop->iteration - 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($item->gtk?->nama ?? 'G', 0, 1)) }}
                                </div>
                                <span class="fw-medium">{{ $item->gtk?->nama ?? '-' }}</span>
                            </div>
                        </td>
                        <td><span class="small">{{ str_pad($item->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $item->tahun }}</span></td>
                        <td class="small">Rp {{ number_format((float)$item->gaji_pokok, 0, ',', '.') }}</td>
                        <td class="text-end small text-success">Rp {{ number_format((float)$item->total_tunjangan, 0, ',', '.') }}</td>
                        <td class="text-end small text-danger">Rp {{ number_format((float)$item->total_potongan, 0, ',', '.') }}</td>
                        <td class="text-end fw-semibold small">Rp {{ number_format((float)$item->gaji_bersih, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @php
                                $s = $item->status ?? 'draft';
                                $mc = ['draft'=>'warning','published'=>'primary','paid'=>'success','void'=>'danger'];
                                $c = $mc[$s] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">{{ ucfirst($s) }}</span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('user.payroll-slip.show', [$userId, $item->id]) }}" class="btn btn-sm btn-light" title="Lihat Slip">
                                <i class="ri-file-text-line"></i>
                            </a>
                            <a href="{{ route('user.payroll-slip.pdf', [$userId, $item->id]) }}" class="btn btn-sm btn-light" title="Cetak PDF">
                                <i class="ri-printer-line"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div style="color:#f59e0b;opacity:.4"><i class="ri-file-list-3-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Belum ada slip gaji</h5>
                            <p class="text-muted mb-0 small">Buat data gaji terlebih dahulu di Daftar Gaji</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($slips, 'hasPages') && $slips->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Menampilkan {{ $slips->firstItem() ?? 0 }} - {{ $slips->lastItem() ?? 0 }} dari {{ $slips->total() }} data</span>
            <nav>{{ $slips->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>
@endsection
