@extends('layouts.master')

@section('title', 'Dashboard Koordinator Laboratorium')

@section('css')
<style>
.stat-card { transition: all 0.3s ease; border-left: 4px solid transparent; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.quick-action-btn { transition: all 0.2s ease; border: 1px solid #e2e5e8; }
.quick-action-btn:hover { transform: translateY(-2px); border-color: #0d6efd; }
.lab-card { transition: all 0.2s ease; }
.lab-card:hover { transform: translateY(-2px); }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') Koordinator Laboratorium @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Lab Aktif" :value="$labAktif" icon="ri-flask-line" color="primary" />
    <x-dashboards.stat-card label="Aset Rusak Ringan" :value="$asetRusakRingan" icon="ri-tools-line" color="warning" />
    <x-dashboards.stat-card label="Peminjaman Hari Ini" :value="$peminjamanHariIni" icon="ri-arrow-right-up-line" color="info" />
    <x-dashboards.stat-card label="Jadwal Lab Sore" :value="$jadwalSore" icon="ri-time-line" color="success" />
</div>

<div class="row g-3 mb-3">
    {{-- STATUS RUANG LAB --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Status Ruang Lab Saat Ini</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @php
                        $labRooms = \App\Models\AssetRoom::where('room_type', 'lab')->where('is_active', true)->get();
                    @endphp
                    @forelse($labRooms as $room)
                    @php
                        $isBeingUsed = \App\Models\AssetRoomBooking::where('room_id', $room->id)
                            ->where('date', now()->toDateString())
                            ->where(function($q) {
                                $q->where('start_time', '<=', now()->format('H:i'))
                                  ->where('end_time', '>=', now()->format('H:i'));
                            })
                            ->exists();
                        $statusColor = $room->condition === 'baik' ? 'success' : ($room->condition === 'rusak_ringan' ? 'warning' : 'danger');
                    @endphp
                    <div class="col-md-6">
                        <div class="card lab-card h-100 border-{{ $statusColor }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1">{{ $room->name ?? 'Lab ' . $room->id }}</h6>
                                        <small class="text-muted">{{ $room->floor ?? 'Lantai -' }}</small>
                                    </div>
                                    <span class="badge bg-{{ $isBeingUsed ? 'primary' : $statusColor }}">
                                        {{ $isBeingUsed ? 'Sedang Dipakai' : ucfirst($room->condition ?? 'Baik') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="text-center text-muted py-4">Belum ada ruang lab terdaftar</div>
                    </div>
                    @endforelse
                </div>
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
                    <a href="{{ route('user.ruang.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-primary">
                        <i class="ri-building-line me-1"></i>Cek Kondisi Lab
                    </a>
                    <a href="{{ route('peminjaman.index') }}" class="btn quick-action-btn btn-outline-success">
                        <i class="ri-arrow-right-up-line me-1"></i>Approve Peminjaman
                    </a>
                    <a href="{{ route('pemeliharaan.schedule.create') }}" class="btn quick-action-btn btn-outline-warning">
                        <i class="ri-wrench-line me-1"></i>Lapor Kerusakan
                    </a>
                    <a href="{{ route('user.aset.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-info">
                        <i class="ri-archive-line me-1"></i>Inventory Lab
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- CHECKLIST KESELAMATAN --}}
    <div class="col-xl-3">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Checklist Selamat</h5>
            </div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check1" checked disabled>
                    <label class="form-check-label small" for="check1">Ventilasi Ruangan</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check2" checked disabled>
                    <label class="form-check-label small" for="check2">APD Tersedia</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check3" disabled>
                    <label class="form-check-label small text-danger" for="check3">Alat Pemadam Api</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check4" checked disabled>
                    <label class="form-check-label small" for="check4">P3K Lengkap</label>
                </div>
                <hr>
                <div class="text-center">
                    <span class="badge bg-warning text-dark">1 item perlu perhatian</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- MAINTENANCE URGENT --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 text-warning">
                    <i class="ri-tools-line me-1"></i>Maintenance Mendesak (7 Hari)
                </h5>
                <a href="{{ route('pemeliharaan.schedule.index') }}" class="btn btn-sm btn-outline-primary">Kelola Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Aset / Ruangan</th>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($maintenanceUrgent as $m)
                            <tr>
                                <td>{{ $m->asset?->name ?? $m->asset_id ?? 'Unknown' }}</td>
                                <td>{{ $m->next_maintenance_date?->format('d M Y') }}</td>
                                <td>{{ $m->maintenance_type ?? '-' }}</td>
                                <td><span class="badge bg-{{ $m->is_completed ? 'success' : 'warning' }}">{{ $m->is_completed ? 'Selesai' : 'Pending' }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada maintenance mendesak</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
