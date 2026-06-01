{{-- Payroll: Index — Daftar Gaji GTK --}}
@extends('layouts.master')
@section('title') Daftar Gaji GTK @endsection

@push('css')
<style>
.page-header-card{
  background:linear-gradient(135deg,#fffbeb 0%,#fffef5 100%);
  border:1px solid #fde68a;
  padding:1.25rem 1.5rem;
  border-radius:.625rem;
}
[data-bs-theme="dark"] .page-header-card{
  background:linear-gradient(135deg,#1c1400 0%,#1f1800 100%);
  border-color:#d97706;
}
.stat-card{transition:all .25s ease;cursor:default}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:max-content;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:12px 16px;word-break:break-word}
.table-freeze th:first-child,.table-freeze td:first-child{position:sticky;left:0;z-index:10;background:#fff;min-width:200px;box-shadow:2px 0 5px rgba(0,0,0,.05)}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
[data-bs-theme="dark"] .table-freeze th:first-child,[data-bs-theme="dark"] .table-freeze td:first-child{background:#1e293b}
[data-bs-theme="dark"] .table-freeze thead th{background:#1e293b}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Gaji & Kompensasi @endslot
    @slot('li_2') Daftar Gaji @endslot
    @slot('title') Daftar Gaji GTK @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Daftar Gaji GTK</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Kelola slip gaji dan komponen payroll GTK.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.payroll.bpjstk', $userId) }}" class="btn btn-outline-secondary btn-sm"><i class="ri-shield-star-line me-1"></i>BPJS TK</a>
        <a href="{{ route('user.payroll.bpjs-kes', $userId) }}" class="btn btn-outline-secondary btn-sm"><i class="ri-heart-line me-1"></i>BPJS Kesehatan</a>
        <a href="{{ route('user.payroll.settings', $userId) }}" class="btn btn-outline-dark btn-sm"><i class="ri-settings-3-line me-1"></i>Pengaturan</a>
        <a href="{{ route('user.payroll.create', $userId) }}" class="btn btn-warning btn-sm"><i class="ri-add-line me-1"></i>Tambah Gaji</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-group-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total GTK</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalGtk ?? 0 }}</h3>
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
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-calendar-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Bulan Berjalan</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ date('F Y') }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Gaji</p>
                        <h3 class="fw-bold ff-secondary mb-0">Rp {{ number_format($totalGaji ?? 0, 0, ',', '.') }}</h3>
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
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-trending-up-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Rata-rata Gaji</p>
                        <h3 class="fw-bold ff-secondary mb-0">Rp {{ number_format($avgGaji ?? 0, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-list-check text-warning me-1"></i> Daftar Gaji GTK</h5>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" style="width:140px">
                <option value="">-- Bulan --</option>
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}">{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
            <select class="form-select form-select-sm" style="width:110px">
                <option value="">-- Tahun --</option>
                @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th>No</th>
                    <th>GTK</th>
                    <th>Jabatan</th>
                    <th class="text-end">Gaji Pokok</th>
                    <th class="text-end">Total Tunjangan</th>
                    <th class="text-end">Total Potongan</th>
                    <th class="text-end">Gaji Bersih</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data ?? [] as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item['gtk'] ?? '-' }}</td>
                    <td>{{ $item['jabatan'] ?? '-' }}</td>
                    <td class="text-end">{{ $item['gaji_pokok'] ?? 'Rp 0' }}</td>
                    <td class="text-end">{{ $item['tunjangan'] ?? 'Rp 0' }}</td>
                    <td class="text-end">{{ $item['potongan'] ?? 'Rp 0' }}</td>
                    <td class="text-end fw-semibold">{{ $item['bersih'] ?? 'Rp 0' }}</td>
                    <td class="text-center">
                        <a href="{{ route('user.payroll.edit', [$userId, $item['id'] ?? '']) }}" class="btn btn-sm btn-outline-primary"><i class="ri-edit-2-line"></i></a>
                        <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button>
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