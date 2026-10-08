@extends('layouts.master')

@section('title', 'Dashboard Bendahara Sekolah')

@section('css')
<style>
.stat-card { transition: all 0.3s ease; border-left: 4px solid transparent; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.quick-action-btn { transition: all 0.2s ease; border: 1px solid #e2e5e8; }
.quick-action-btn:hover { transform: translateY(-2px); border-color: #0d6efd; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') Bendahara Sekolah @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Saldo Kas" :value="$saldoKas" icon="ri-wallet-3-line" color="primary" />
    <x-dashboards.stat-card label="Pengeluaran Hari Ini" :value="$pengeluaranHariIni" icon="ri-arrow-down-double-line" color="danger" />
    <x-dashboards.stat-card label="Pemasukan Hari Ini" :value="$pemasukanHariIni" icon="ri-arrow-up-double-line" color="success" />
    <x-dashboards.stat-card label="Tagihan Pending" :value="$tagihanPending" icon="ri-file-list-3-line" color="warning" />
</div>

<div class="row g-3 mb-3">
    {{-- ARUS KAS --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Arus Kas Harian</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="p-3 bg-success-subtle rounded">
                            <h4 class="text-success fw-bold mb-1">+{{ $pemasukanHariIni }}</h4>
                            <small class="text-muted">Pemasukan Hari Ini</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-danger-subtle rounded">
                            <h4 class="text-danger fw-bold mb-1">-{{ $pengeluaranHariIni }}</h4>
                            <small class="text-muted">Pengeluaran Hari Ini</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-primary-subtle rounded">
                            <h4 class="text-primary fw-bold mb-1">{{ $saldoKas }}</h4>
                            <small class="text-muted">Saldo Kas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- AKSI CEPAT --}}
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- TAGIHAN PENDING --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Tagihan Vendor Pending</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vendor</th>
                                <th>Nominal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tagihanVendor as $pr)
                            <tr>
                                <td>{{ $pr->vendor?->name ?? 'Unknown' }}</td>
                                <td>{{ number_format($pr->total_estimated_budget ?? 0, 0, ',', '.') }}</td>
                                <td><span class="badge bg-warning">{{ ucfirst($pr->status) }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada tagihan pending</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- PENGELUARAN PENDING --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Pengeluaran Pending Approval</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vendor</th>
                                <th>Nominal</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pengeluaranPending as $gr)
                            <tr>
                                <td>{{ $gr->vendor?->name ?? 'Unknown' }}</td>
                                <td>{{ number_format($gr->total_amount ?? 0, 0, ',', '.') }}</td>
                                <td>{{ $gr->receipt_date?->format('d M Y') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada pengeluaran pending</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    @if(isset($taskBendahara) && $taskBendahara)
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ri-task-line text-primary me-1"></i>Tugas Tambahan
                </h5>
                <a href="{{ route('user.schools.satuan-kerja.additional-tasks', ['userId' => $user->id, 'workUnitId' => $primaryWorkUnit?->id]) }}" class="btn btn-sm btn-outline-primary">Kelola</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Nama Tugas</small>
                            <strong>{{ $taskBendahara->nama_tugas }}</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Jam/Minggu</small>
                            <strong>{{ $taskBendahara->hours_per_week ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TMT</small>
                            <strong>{{ $taskBendahara->tmt?->format('d M Y') ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TST</small>
                            <strong>{{ $taskBendahara->tst?->format('d M Y') ?? '<span class="text-success">Selamanya</span>' }}</strong>
                        </div>
                    </div>
                </div>
                @if($taskBendahara->decree)
                <div class="mt-3">
                    <small class="text-muted">SK: <code>{{ $taskBendahara->decree->decree_number }}</code></small>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
