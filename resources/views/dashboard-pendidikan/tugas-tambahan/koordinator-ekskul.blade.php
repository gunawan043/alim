@extends('layouts.master')

@section('title', 'Dashboard Koordinator Ekstrakurikuler')

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
    @slot('title') Koordinator Ekstrakurikuler @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Ekskul Dibina" :value="$ekskulDiampu" icon="ri-trophy-line" color="primary" />
    <x-dashboards.stat-card label="Total Anggota Aktif" :value="$totalAnggota" icon="ri-group-line" color="success" />
    <x-dashboards.stat-card label="Pertemuan Hari Ini" :value="$pertemuanHariIni" icon="ri-play-circle-line" color="info" />
    <x-dashboards.stat-card label="Acara Mendatang" :value="$acaraMendatang" icon="ri-calendar-event-line" color="warning" />
</div>

<div class="row g-3 mb-3">
    {{-- DAFTAR EKLAKSUL --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Ekstrakurikuler yang Dibina</h5>
                <a href="{{ route('user.ekstrakurikuler.index', ['userId' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Kelola</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Ekskul</th>
                                <th>Hari</th>
                                <th>Waktu</th>
                                <th>Anggota</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ekskulList as $ekskul)
                            <tr>
                                <td><strong>{{ $ekskul->nama }}</strong></td>
                                <td>{{ $ekskul->hari ?? '-' }}</td>
                                <td>{{ $ekskul->waktu ?? '-' }}</td>
                                <td>{{ $ekskul->anggota_aktif_count ?? $ekskul->anggota->count() }}</td>
                                <td><span class="badge bg-success">Aktif</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada ekstrakurikuler</td></tr>
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
                    <a href="{{ route('user.ekstrakurikuler.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-primary">
                        <i class="ri-list-check-line me-1"></i>Kelola Ekskul
                    </a>
                    <a href="{{ route('user.ekstrakurikuler-anggota.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-success">
                        <i class="ri-group-line me-1"></i>Anggota Ekskul
                    </a>
                    <a href="{{ route('user.recruitment.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-warning">
                        <i class="ri-add-circle-line me-1"></i>Daftarkan Lomba
                    </a>
                    <a href="{{ route('user.violation-points.create', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-info">
                        <i class="ri-file-chart-line me-1"></i>Laporan Kegiatan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- LOG KEGIATAN --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Log Kegiatan Terbaru</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($kegiatanLog as $log)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $log->nama }}</strong>
                            <br><small class="text-muted">{{ $log->updated_at?->format('d M Y H:i') }}</small>
                        </div>
                        <span class="badge bg-{{ $log->is_active ? 'success' : 'secondary' }}">
                            {{ $log->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">Belum ada log kegiatan</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
