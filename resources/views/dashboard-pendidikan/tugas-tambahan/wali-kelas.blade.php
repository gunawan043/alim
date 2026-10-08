@extends('layouts.master')

@section('title', 'Dashboard Wali Kelas')

@section('css')
<style>
.stat-card { transition: all 0.3s ease; border-left: 4px solid transparent; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.quick-action-btn { transition: all 0.2s ease; border: 1px solid #e2e5e8; }
.quick-action-btn:hover { transform: translateY(-2px); border-color: #0d6efd; }
.attendance-bar { height: 8px; border-radius: 4px; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') Wali Kelas @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Total Santri Kelas" :value="$totalSantri" icon="ri-group-line" color="primary" />
    <x-dashboards.stat-card label="Hadir Hari Ini" :value="$hadirPercent . '%'" icon="ri-checkbox-circle-line" color="success" />
    <x-dashboards.stat-card label="Rata-rata Nilai" :value="number_format($nilaiRata, 1, ',', '.')" icon="ri-bar-chart-fill" color="warning" />
    <x-dashboards.stat-card label="Perlu Perhatian" :value="$perluPerhatian" icon="ri-alert-line" color="danger" />
</div>

<div class="row g-3 mb-3">
    {{-- PRESENSI KELAS HARI INI --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Presensi Kelas — {{ $kelas?->full_name ?? 'Belum Ada Kelas' }}</h5>
                <a href="{{ route('user.absensi.harian.create', ['userId' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Ambil Absensi</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Santri</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($santriList as $s)
                            @php $absen = $absensiList->firstWhere('student_id', $s->id); @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $s->name }}</td>
                                <td>
                                    @if($absen)
                                        @if($absen->status === 'hadir')
                                            <span class="badge bg-success">Hadir</span>
                                        @elseif($absen->status === 'sakit')
                                            <span class="badge bg-warning text-dark">Sakit</span>
                                        @elseif($absen->status === 'izin')
                                            <span class="badge bg-info">Izin</span>
                                        @else
                                            <span class="badge bg-danger">Alpha</span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">-</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $absen?->note ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data santri</td></tr>
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
                    <a href="{{ route('user.teacher-qr.scan', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-primary">
                        <i class="ri-qr-code-line me-1"></i>Ambil Presensi
                    </a>
                    <a href="{{ route('user.violation-points.create', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-warning">
                        <i class="ri-alert-line me-1"></i>Catat Pelanggaran
                    </a>
                    <a href="{{ route('user.schools.nilai-kelas.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-success">
                        <i class="ri-edit-circle-line me-1"></i>Input Nilai
                    </a>
                    @if($santriList->isNotEmpty())
                    <a href="{{ route('user.students.show', ['userId' => $user->id, 'santriUuid' => $santriList->first()->id]) }}" class="btn quick-action-btn btn-outline-info">
                        <i class="ri-file-list-3-line me-1"></i>Lihat Rapor
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- SANTRI PERLU PERHATIAN --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0 text-danger">
                    <i class="ri-alarm-warning-line me-1"></i>Santri Perlu Perhatian
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($santriList as $s)
                    @php
                        $poin = \App\Models\PelanggaranLog::where('study_group_id', $kelas?->id)
                            ->where('tanggal', '>=', now()->subDays(30))
                            ->where('student_id', $s->id)
                            ->join('pelanggaran as pl', 'pl.id', '=', 'pelanggaran_logs.pelanggaran_id')
                            ->sum('pl.poin');
                    @endphp
                    @if($poin > 10)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $s->name }}</strong>
                            <br><small class="text-muted">{{ $s->nis ?? 'NIS belum diisi' }}</small>
                        </div>
                        <span class="badge bg-danger rounded-pill">{{ $poin }} poin</span>
                    </div>
                    @endif
                    @empty
                    <div class="list-group-item text-center text-muted py-4">Tidak ada data santri</div>
                    @endforelse
                    @if($perluPerhatian === 0)
                    <div class="list-group-item text-center text-muted py-4">Semua santri dalam kondisi baik</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- RINGKASAN NILAI --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Ringkasan Nilai Kelas</h5>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-4">
                        <div class="p-3 bg-success-subtle rounded">
                            <h4 class="text-success fw-bold mb-1">{{ number_format($nilaiRata, 1, ',', '.') }}</h4>
                            <small class="text-muted">Rata-rata</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-primary-subtle rounded">
                            <h4 class="text-primary fw-bold mb-1">{{ $totalSantri }}</h4>
                            <small class="text-muted">Total Santri</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-warning-subtle rounded">
                            <h4 class="text-warning fw-bold mb-1">{{ $perluPerhatian }}</h4>
                            <small class="text-muted">Perlu Perhatian</small>
                        </div>
                    </div>
                </div>
                <hr class="my-3">
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Tingkat Kehadiran</small>
                        <small class="fw-bold">{{ $hadirPercent }}%</small>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-{{ $hadirPercent >= 90 ? 'success' : ($hadirPercent >= 75 ? 'warning' : 'danger') }}" style="width: {{ $hadirPercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
