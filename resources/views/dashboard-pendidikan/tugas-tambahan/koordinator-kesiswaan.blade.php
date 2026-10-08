@extends('layouts.master')

@section('title', 'Dashboard Koordinator Kesiswaan')

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
    @slot('title') Koordinator Kesiswaan @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Total Santri Aktif" :value="$totalSantri" icon="ri-group-line" color="primary" />
    <x-dashboards.stat-card label="Pelanggaran Bulan Ini" :value="$pelanggaran" icon="ri-alert-line" color="danger" />
    <x-dashboards.stat-card label="Ekstrakurikuler Aktif" :value="$ekskulAktif" icon="ri-trophy-line" color="success" />
    <x-dashboards.stat-card label="Izin Keluar Pending" :value="$izinPending" icon="ri-mail-unread-line" color="warning" />
</div>

<div class="row g-3 mb-3">
    {{-- LIVE FEED PELANGGARAN --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ri-flashlight-line text-danger me-1"></i>Live Feed Pelanggaran (7 Hari)
                </h5>
                <a href="{{ route('user.violation-points.index', ['userId' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Santri</th>
                                <th>Jenis</th>
                                <th>Poin</th>
                                <th>Wali Kelas</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentViolations as $v)
                            <tr>
                                <td>{{ $v->tanggal?->format('d M Y') }}</td>
                                <td>{{ $v->student?->name ?? '-' }}</td>
                                <td>{{ $v->violation_type ?? $v->pelanggaran?->nama ?? '-' }}</td>
                                <td><span class="badge bg-danger">{{ $v->poin ?? 0 }}</span></td>
                                <td>{{ $v->dicatat_oleh?->name ?? '-' }}</td>
                                <td><span class="badge bg-{{ $v->status ?? 'pending' === 'penanganan' ? 'success' : 'warning' }}">{{ $v->status ?? 'Pending' }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada pelanggaran terbaru</td></tr>
                            @endforelse
                        </tbody>
                    </table>
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
                    <a href="{{ route('user.violation-points.create', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-danger">
                        <i class="ri-alert-line me-1"></i>Catat Pelanggaran
                    </a>
                    <a href="{{ route('user.asrama.permits.index', ['userId' => $user->id, 'asramaUuid' => $asramaUuid ?? \App\Models\Dormitory::where('is_active', true)->first()?->id]) }}" class="btn quick-action-btn btn-outline-warning">
                        <i class="ri-mail-line me-1"></i>Approve Izin Pulang
                        @if($izinPending > 0)
                        <span class="badge bg-danger ms-1">{{ $izinPending }}</span>
                        @endif
                    </a>
                    <a href="{{ route('waka.ekstrakurikuler.index') }}" class="btn quick-action-btn btn-outline-success">
                        <i class="ri-trophy-line me-1"></i>Kelola Ekskul
                    </a>
                    <a href="{{ route('user.student-achievements.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-info">
                        <i class="ri-medal-line me-1"></i>Santri Berprestasi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- EKLAKSUL AKTIF --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Ekstrakurikuler Aktif</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($ekskulList as $ekskul)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $ekskul->nama }}</strong>
                            <br><small class="text-muted">Pembina: {{ $ekskul->gtk?->name ?? 'Belum ditentukan' }}</small>
                        </div>
                        <span class="badge bg-success">{{ $ekskul->anggota_aktif_count ?? '0' }} anggota</span>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">Belum ada ekstrakurikuler aktif</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- SANTRI PERLU PERHATIAN --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0 text-warning">
                    <i class="ri-alert-line me-1"></i>Ringkasan Kedisiplinan
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-4">
                        <div class="p-3 bg-danger-subtle rounded">
                            <h4 class="text-danger fw-bold mb-1">{{ $pelanggaran }}</h4>
                            <small class="text-muted">Pelanggaran/Bulan</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-warning-subtle rounded">
                            <h4 class="text-warning fw-bold mb-1">{{ $izinPending }}</h4>
                            <small class="text-muted">Izin Pending</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-success-subtle rounded">
                            <h4 class="text-success fw-bold mb-1">{{ $ekskulAktif }}</h4>
                            <small class="text-muted">Ekskul Aktif</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
