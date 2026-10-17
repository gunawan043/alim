@extends('layouts.master')
@section('title') Daftar Program Kesejahteraan @endsection

@push('css')
<style>
.page-header-card{
  background:linear-gradient(135deg,#ecfdf5 0%,#f0fdf4 100%);
  border:1px solid #a7f3d0;
  padding:1.25rem 1.5rem;
  border-radius:.625rem;
}
[data-bs-theme="dark"] .page-header-card{
  background:linear-gradient(135deg,#064e3b 0%,#022c22 100%);
  border-color:#059669;
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
@php
$userId = request()->route('userId') ?? auth()->id();
$programs = $programs ?? collect();
$stats = ['total' => $programs->count(), 'aktif' => $programs->where('is_active', 1)->count(), 'bpjs' => $programs->where('jenis', 'bpjs')->count(), 'bantuan' => $programs->where('jenis', 'bantuan')->count()];
@endphp

@component('components.breadcrumb')
    @slot('li_1') Kesejahteraan & Benefit @endslot
    @slot('title') Program Kesejahteraan @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Program Kesejahteraan GTK</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Kelola bantuan, santunan, fasilitas, dan klaim kesejahteraan</p>
    </div>
    <a href="{{ route('user.ats.kesejahteraan.create', ['userId' => $userId]) }}" class="btn btn-success btn-sm">
        <i class="ri-add-circle-line me-1"></i> Tambah Program
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-heart-pulse-line text-success"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Program</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total'] }}</h3>
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
                        <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-primary"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Program Aktif</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['aktif'] }}</h3>
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
                        <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-shield-star-line text-info"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">BPJS</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['bpjs'] }}</h3>
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
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-hand-coin-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Bantuan</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stats['bantuan'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <select class="form-select form-select-sm" style="width:auto">
                    <option value="">Semua Jenis</option>
                    <option value="bantuan">Bantuan</option>
                    <option value="santunan">Santunan</option>
                    <option value="bpjs">BPJS</option>
                    <option value="klaim">Klaim</option>
                    <option value="fasilitas">Fasilitas</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.ats.kesejahteraan.asuransi', ['userId' => $userId]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ri-shield-star-line me-1"></i> Asuransi
                </a>
                <a href="{{ route('user.ats.kesejahteraan.benefit', ['userId' => $userId]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ri-gift-line me-1"></i> Benefit
                </a>
                <a href="{{ route('user.ats.kesejahteraan.umum', ['userId' => $userId]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ri-government-line me-1"></i> Umum
                </a>
                <a href="{{ route('user.ats.kesejahteraan.laporan', ['userId' => $userId]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ri-bar-chart-box-line me-1"></i> Laporan
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze">
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th>Nama Program</th>
                        <th>Jenis</th>
                        <th>Nilai Default</th>
                        <th>Jumlah Peserta</th>
                        <th>Status</th>
                        <th style="width:120px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($programs as $p)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="fw-semibold">{{ $p->nama }}</div>
                            <div class="text-muted" style="font-size:.8rem">{{ Str::limit($p->deskripsi, 60) }}</div>
                        </td>
                        <td><span class="badge bg-{{ $p->jenis=='bpjs'?'info':($p->jenis=='bantuan'?'warning':($p->jenis=='santunan'?'success':($p->jenis=='klaim'?'danger':'secondary'))) }} bg-opacity-10 text-{{ $p->jenis=='bpjs'?'info':($p->jenis=='bantuan'?'warning':($p->jenis=='santunan'?'success':($p->jenis=='klaim'?'danger':'secondary'))) }}">{{ ucfirst($p->jenis) }}</span></td>
                        <td>{{ $p->nilai_default ? 'Rp '.number_format($p->nilai_default, 0, ',', '.') : '—' }}</td>
                        <td>{{ $p->jumlah_peserta ?? 0 }} GTK</td>
                        <td>
                            @if($p->is_active)
                            <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line me-1"></i>Aktif</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary"><i class="ri-close-circle-line me-1"></i>Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('user.ats.kesejahteraan.show', ['userId' => $userId, 'id' => $p->id]) }}" class="btn btn-sm btn-ghost-secondary"><i class="ri-eye-line"></i></a>
                            <a href="{{ route('user.ats.kesejahteraan.edit', ['userId' => $userId, 'id' => $p->id]) }}" class="btn btn-sm btn-ghost-primary"><i class="ri-edit-2-line"></i></a>
                            <form method="POST" action="{{ route('user.ats.kesejahteraan.destroy', ['userId' => $userId, 'id' => $p->id]) }}" class="d-inline" onsubmit="return confirm('Hapus program ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost-danger"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5">
                        <i class="ri-heart-pulse-line" style="font-size:3rem;color:#9ca3af"></i>
                        <h6 class="mt-2 text-muted">Belum ada program kesejahteraan</h6>
                        <p class="text-muted" style="font-size:.8rem">Tambahkan program pertama untuk memulai.</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection