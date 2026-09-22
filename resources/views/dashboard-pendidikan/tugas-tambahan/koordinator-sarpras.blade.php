@extends('layouts.master')

@section('title', 'Dashboard Koordinator Sarpras')

@section('css')
<style>
.stat-card { transition: all 0.3s ease; border-left: 4px solid transparent; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.quick-action-btn { transition: all 0.2s ease; border: 1px solid #e2e5e8; }
.quick-action-btn:hover { transform: translateY(-2px); border-color: #0d6efd; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') Koordinator Sarpras @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Gedung Kondisi Baik" :value="$gedungBaik" icon="ri-building-line" color="success" />
    <x-dashboards.stat-card label="Aset Rusak Berat" :value="$asetRusakBerat" icon="ri-error-warning-line" color="danger" />
    <x-dashboards.stat-card label="Maintenance Mendatang" :value="$maintenanceJangkaDekat" icon="ri-tools-line" color="warning" />
    <x-dashboards.stat-card label="Pengadaan Pending" :value="$pengadaanPending" icon="ri-shopping-cart-2-line" color="info" />
</div>

<div class="row g-3 mb-3">
    {{-- OVERVIEW GEDUNG --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Status Gedung & Ruangan</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Kondisi Baik</small>
                        <small class="fw-bold text-success">{{ $gedungBaik }}</small>
                    </div>
                    <div class="progress mb-3" style="height: 12px;">
                        @php
                            $totalGedung = $gedungStatus->sum('cnt') ?? 1;
                            $baikPct = $totalGedung > 0 ? round(($gedungBaik / $totalGedung) * 100) : 0;
                        @endphp
                        <div class="progress-bar bg-success" style="width: {{ $baikPct }}%"></div>
                    </div>
                </div>
                @foreach($gedungStatus as $gs)
                @php
                    $color = match($gs->structure_condition) {
                        'baik' => 'success',
                        'ringan' => 'warning',
                        'berat' => 'danger',
                        default => 'secondary'
                    };
                    $label = ucfirst($gs->structure_condition ?? 'Unknown');
                @endphp
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">{{ $label }}</span>
                    <div class="progress flex-grow-1 mx-3" style="height:8px;">
                        <div class="progress-bar bg-{{ $color }}" style="width: {{ $totalGedung > 0 ? round(($gs->cnt / $totalGedung) * 100) : 0 }}%"></div>
                    </div>
                    <small class="fw-bold">{{ $gs->cnt }}</small>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- AKSI CEPAT --}}
    <div class="col-xl-3">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('user.sarpras-dashboard', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-primary">
                        <i class="ri-dashboard-line me-1"></i>Dashboard Sarpras
                    </a>
                    <a href="{{ route('user.procurement-requests.create', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-warning">
                        <i class="ri-shopping-cart-2-line me-1"></i>Request Pengadaan
                    </a>
                    <a href="{{ route('user.sarpras-aset.create', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-success">
                        <i class="ri-add-circle-line me-1"></i>Tambah Aset Baru
                    </a>
                    <a href="{{ route('user.sarpras-pemeliharaan.create', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-info">
                        <i class="ri-calendar-schedule-line me-1"></i>Jadwalkan Maintenance
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- SUMMARY STATS --}}
    <div class="col-xl-3">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Ringkasan Aset</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <div class="p-3 bg-success-subtle rounded text-center">
                        <h4 class="text-success fw-bold mb-1">{{ $gedungBaik }}</h4>
                        <small class="text-muted">Gedung Baik</small>
                    </div>
                    <div class="p-3 bg-danger-subtle rounded text-center">
                        <h4 class="text-danger fw-bold mb-1">{{ $asetRusakBerat }}</h4>
                        <small class="text-muted">Aset Rusak Berat</small>
                    </div>
                    <div class="p-3 bg-warning-subtle rounded text-center">
                        <h4 class="text-warning fw-bold mb-1">{{ $pengadaanPending }}</h4>
                        <small class="text-muted">Pengadaan Pending</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- JADWAL MAINTENANCE --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Jadwal Maintenance Mendatang</h5>
                <a href="{{ route('user.sarpras-pemeliharaan.index', ['userId' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Aset / Gedung</th>
                                <th>Jenis</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jadwalMaintenance as $jm)
                            <tr>
                                <td>{{ $jm->next_maintenance_date?->format('d M Y') }}</td>
                                <td>{{ $jm->asset?->name ?? $jm->building?->name ?? 'Unknown' }}</td>
                                <td>{{ $jm->maintenance_type ?? '-' }}</td>
                                <td><span class="badge bg-{{ $jm->is_completed ? 'success' : 'warning' }}">{{ $jm->is_completed ? 'Selesai' : 'Scheduled' }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada jadwal maintenance mendatang</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- PENGADAAN PENDING --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Pengadaan Pending</h5>
                <span class="badge bg-warning">{{ $pengadaanPending }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pemohon</th>
                                <th>Item</th>
                                <th>Nominal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pengadaanList as $pr)
                            <tr>
                                <td>{{ $pr->requester?->name ?? 'Unknown' }}</td>
                                <td>{{ Str::limit($pr->reason ?? $pr->items ?? '-', 30) }}</td>
                                <td>{{ number_format($pr->estimated_budget ?? $pr->total_amount ?? 0, 0, ',', '.') }}</td>
                                <td><span class="badge bg-warning">Pending</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada pengadaan pending</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
