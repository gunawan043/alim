@extends('layouts.master')
@push('css')
<style>
.page-header-card{
  background:linear-gradient(135deg,#f5f3ff 0%,#faf8ff 100%);
  border:1px solid #ddd6fe;
  padding:1.25rem 1.5rem;
  border-radius:.625rem;
}
[data-bs-theme="dark"] .page-header-card{
  background:linear-gradient(135deg,#1e1535 0%,#1a1028 100%);
  border-color:#6d28d9;
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

@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Personalia @endslot
    @slot('li_2') Kontrak @endslot
    @slot('title') Daftar Kontrak Kerja @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Daftar Kontrak Kerja</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Kelola kontrak kerja GTK di lingkungan Alim.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.kontrak.expiring', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-time-line me-1"></i> Akan Berakhir
        </a>
        <a href="{{ route('user.ats.kontrak.template', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-file-text-line me-1"></i> Template
        </a>
        <a href="{{ route('user.ats.kontrak.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
            <i class="ri-add-line me-1"></i> Tambah Kontrak
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.8rem">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="AKTIF" {{ request('status')=='AKTIF'?'selected':'' }}>Aktif</option>
                    <option value="MENJADI_TETAP" {{ request('status')=='MENJADI_TETAP'?'selected':'' }}>Menjadi Tetap</option>
                    <option value="SELESAI" {{ request('status')=='SELESAI'?'selected':'' }}>Selesai</option>
                    <option value="DIBATALKAN" {{ request('status')=='DIBATALKAN'?'selected':'' }}>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.8rem">Jenis</label>
                <select name="jenis" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="PKWT" {{ request('jenis')=='PKWT'?'selected':'' }}>PKWT</option>
                    <option value="PKWTT" {{ request('jenis')=='PKWTT'?'selected':'' }}>PKWTT</option>
                    <option value="MITRA" {{ request('jenis')=='MITRA'?'selected':'' }}>MITRA</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.8rem">Bulan</label>
                <select name="bulan" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @for ($i=1; $i<=12; $i++)
                    <option value="{{ $i }}" {{ request('bulan')==$i?'selected':'' }}>{{ Date::monthName($i) }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="ri-filter-line me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-auto">
                <a href="{{ route('user.ats.kontrak.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-purple-subtle rounded fs-2"><i class="ri-file-list-3-line text-purple"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Kontrak</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $kontraks->total() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-check-line text-success"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Aktif</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $statusCounts->aktif ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-time-line text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Akan Berakhir</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $statusCounts->expiring ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-close-circle-line text-danger"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Expired</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $statusCounts->dibatalkan ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>GTK</th>
                        <th>Jenis Kontrak</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Durasi</th>
                        <th>Jabatan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kontraks as $kontrak)
                    <tr>
                        <td>{{ $loop->iteration + ($kontraks->currentPage() - 1) * $kontraks->perPage() }}</td>
                        <td>
                            <div class="fw-medium">{{ $kontrak->gtk?->nama ?? '-' }}</div>
                            <small class="text-muted">{{ $kontrak->gtk?->nik ?? '' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-purple-subtle text-purple">{{ $kontrak->jenis_kontrak }}</span>
                        </td>
                        <td>{{ $kontrak->tanggal_mulai?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $kontrak->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $kontrak->durasi_bulan ?? 0 }} bulan</td>
                        <td>{{ $kontrak->jabatan ?? '-' }}</td>
                        <td>
                            @switch($kontrak->status)
                                @case('AKTIF')
                                    <span class="badge bg-success-subtle text-success">Aktif</span> @break
                                @case('MENJADI_TETAP')
                                    <span class="badge bg-primary-subtle text-primary">Menjadi Tetap</span> @break
                                @case('SELESAI')
                                    <span class="badge bg-secondary-subtle text-secondary">Selesai</span> @break
                                @case('DIBATALKAN')
                                    <span class="badge bg-danger-subtle text-danger">Dibatalkan</span> @break
                                @default
                                    <span class="badge bg-secondary-subtle">-</span>
                            @endswitch
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('user.ats.kontrak.edit', ['userId' => $userId, 'id' => $kontrak->id]) }}"
                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="ri-edit-2-line"></i>
                                </a>
                                <form method="POST" action="{{ route('user.ats.kontrak.destroy', ['userId' => $userId, 'id' => $kontrak->id]) }}"
                                      onsubmit="return confirm('Hapus kontrak ini?')" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="999" class="text-center py-5">
                        <i class="ri-file-list-3-line" style="font-size:3rem;color:#9ca3af"></i>
                        <h6 class="mt-2 text-muted">Belum ada data kontrak</h6>
                        <p class="text-muted" style="font-size:.8rem">Data kontrak akan muncul di sini ketika sudah ada.</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($kontraks->hasPages())
        <div class="px-3 py-2 border-top">
            {{ $kontraks->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection