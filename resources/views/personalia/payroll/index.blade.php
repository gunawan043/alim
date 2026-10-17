{{-- Payroll: Daftar Gaji GTK --}}
@extends('layouts.master')
@section('title') Daftar Gaji GTK @endsection

@section('css')
<style>
    .page-header-card { background: linear-gradient(135deg,#fffbeb 0%,#fffef5 100%); border:1px solid #fde68a; padding:1.25rem 1.5rem; border-radius:.625rem; }
    .stat-card { transition: all .25s ease; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
    .table-freeze th, .table-freeze td { vertical-align: middle; white-space: nowrap; }
</style>
@endsection

@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Gaji & Kompensasi @endslot
        @slot('li_2') Daftar Gaji @endslot
        @slot('title') Daftar Gaji GTK @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('generate_errors') && count(session('generate_errors')))
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1"><i class="ri-error-warning-line me-1"></i>Sebagian GTK tidak dapat digenerate:</div>
            <ul class="mb-0 small">
                @foreach(session('generate_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="page-header-card d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h5 class="fw-semibold mb-1">Daftar Gaji GTK</h5>
            <p class="text-muted mb-0" style="font-size:.85rem">Payroll dihitung dari skala gaji, tunjangan, dan kehadiran GTK.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.payroll.master', $userId) }}" class="btn btn-outline-dark btn-sm">
                <i class="ri-settings-3-line me-1"></i>Master Gaji
            </a>
            <a href="{{ route('user.payroll.generate', $userId) }}" class="btn btn-success btn-sm">
                <i class="ri-flashlight-line me-1"></i>Generate Payroll
            </a>
            <a href="{{ route('user.payroll.create', $userId) }}" class="btn btn-warning btn-sm">
                <i class="ri-add-line me-1"></i>Tambah Gaji
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100"><div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar-sm"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-group-line text-primary"></i></span></span>
                    <div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total GTK</p>
                        <h3 class="fw-bold mb-0">{{ number_format($totalGtk) }}</h3></div>
                </div>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100"><div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar-sm"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-file-list-3-line text-warning"></i></span></span>
                    <div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Slip (Filter)</p>
                        <h3 class="fw-bold mb-0">{{ number_format($stats['total']) }}</h3></div>
                </div>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100"><div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar-sm"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-money-dollar-circle-line text-success"></i></span></span>
                    <div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Gaji Bersih</p>
                        <h3 class="fw-bold mb-0" style="font-size:1.1rem;">Rp {{ number_format($totalGaji, 0, ',', '.') }}</h3></div>
                </div>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100"><div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar-sm"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-line-chart-line text-info"></i></span></span>
                    <div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Rata-rata Gaji</p>
                        <h3 class="fw-bold mb-0" style="font-size:1.1rem;">Rp {{ number_format($avgGaji, 0, ',', '.') }}</h3></div>
                </div>
            </div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.payroll.index', $userId) }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">GTK</label>
                    <select name="gtk_id" class="form-select form-select-sm">
                        <option value="">Semua GTK</option>
                        @foreach($gtkList as $gtk)
                            <option value="{{ $gtk->id }}" {{ request('gtk_id') === $gtk->id ? 'selected' : '' }}>{{ $gtk->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Bulan</label>
                    <select name="bulan" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (string) request('bulan') === (string) $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tahun</label>
                    <select name="tahun" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" {{ (string) request('tahun') === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach(['draft' => 'Draft', 'published' => 'Published', 'paid' => 'Paid', 'void' => 'Void'] as $val => $label)
                            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="ri-filter-3-line me-1"></i>Filter</button>
                    <a href="{{ route('user.payroll.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-refresh-line"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-list-check text-warning me-1"></i>Daftar Gaji GTK</h5>
            <span class="badge bg-warning-subtle text-warning">{{ $payrolls->total() }} slip</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-freeze mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">No</th>
                            <th>GTK</th>
                            <th>Periode</th>
                            <th class="text-end">Gaji Pokok</th>
                            <th class="text-end">Tunjangan</th>
                            <th class="text-end">Potongan</th>
                            <th class="text-end">Gaji Bersih</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="width:170px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $i => $payroll)
                            @php
                                $tone = match ($payroll->status) {
                                    'draft' => 'warning',
                                    'published' => 'primary',
                                    'paid' => 'success',
                                    'void' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>{{ $payrolls->firstItem() + $i }}</td>
                                <td>
                                    <div class="fw-medium">{{ $payroll->gtk?->nama ?? '-' }}</div>
                                    <div class="small text-muted">{{ $payroll->gtk?->latestEmployment?->jabatan ?? '' }}</div>
                                </td>
                                <td>{{ str_pad($payroll->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $payroll->tahun }}</td>
                                <td class="text-end">Rp {{ number_format((float) $payroll->gaji_pokok, 0, ',', '.') }}</td>
                                <td class="text-end text-success">+Rp {{ number_format((float) $payroll->total_tunjangan, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">-Rp {{ number_format((float) $payroll->total_potongan, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">Rp {{ number_format((float) $payroll->gaji_bersih, 0, ',', '.') }}</td>
                                <td class="text-center"><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst($payroll->status) }}</span></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('user.payroll-slip.show', [$userId, $payroll->id]) }}" class="btn btn-sm btn-outline-info" title="Slip"><i class="ri-eye-line"></i></a>
                                        @if($payroll->status === 'draft')
                                            <a href="{{ route('user.payroll.edit', [$userId, $payroll->id]) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri-edit-line"></i></a>
                                            <form action="{{ route('user.payroll.publish', [$userId, $payroll->id]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-primary" title="Publikasikan"><i class="ri-send-plane-line"></i></button>
                                            </form>
                                        @elseif($payroll->status === 'published')
                                            <form action="{{ route('user.payroll.pay', [$userId, $payroll->id]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-success" title="Tandai Dibayar"><i class="ri-check-double-line"></i></button>
                                            </form>
                                        @endif
                                        @if($payroll->status !== 'paid')
                                            <form action="{{ route('user.payroll.destroy', [$userId, $payroll->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus slip ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="9" title="Belum ada slip gaji"
                                message="Generate payroll atau tambahkan slip manual untuk memulai.">
                                <a href="{{ route('user.payroll.generate', $userId) }}" class="btn btn-success mt-3"><i class="ri-flashlight-line me-1"></i>Generate Payroll</a>
                            </x-empty-state>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payrolls->hasPages())
            <div class="card-footer">@include('shared._pagination', ['paginator' => $payrolls])</div>
        @endif
    </div>
@endsection
