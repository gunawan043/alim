@extends('layouts.master')

@section('title', 'Dashboard Kepala Satuan Pendidikan')

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
    @slot('title') Kepala Satuan Pendidikan @endslot
@endcomponent

{{-- STAT CARDS --}}
<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Santri Aktif" :value="$totalSantri" icon="ri-group-line" color="primary" />
    <x-dashboards.stat-card label="GTK Aktif" :value="$totalGtk" icon="ri-user-follow-line" color="success" />
    <x-dashboards.stat-card label="Rombel Aktif" :value="$totalRombel" icon="ri-school-line" color="warning" />
    <x-dashboards.stat-card label="Kehadiran Hari Ini" :value="$kehadiranHariIni . '%'" icon="ri-checkbox-circle-line" color="info" />
</div>

<div class="row g-3 mb-3">
    {{-- TINGKAT KEPATUHAN KURIKULUM --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Tingkat Kepatuhan Kurikulum</h5>
            </div>
            <div class="card-body">
                <div class="progress mb-2" style="height: 24px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 78%" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100">78% Realisasi Silabus</div>
                </div>
                <small class="text-muted">Rata-rata capaian silabus semester ini</small>
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
                    <a href="{{ route('user.gtk-positions.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-primary">
                        <i class="ri-file-text-line me-1"></i>Tinjau SK GTK
                    </a>
                    <a href="{{ route('user.gtk-requests.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-warning">
                        <i class="ri-mail-unread-line me-1"></i>Approve Pengajuan GTK
                        @if($pendingGtkRequests > 0)
                        <span class="badge bg-danger ms-1">{{ $pendingGtkRequests }}</span>
                        @endif
                    </a>
                    <a href="{{ route('user.violation-points.dashboard', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-danger">
                        <i class="ri-alert-line me-1"></i>Panel Disiplin Santri
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- DAFTAR INSIDEN TERBARU --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Insiden Terbaru (7 Hari)</h5>
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
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentViolations as $violation)
                            <tr>
                                <td>{{ $violation->violation_date?->format('d M Y') }}</td>
                                <td>{{ $violation->student?->name ?? '-' }}</td>
                                <td>{{ $violation->violation_type ?? '-' }}</td>
                                <td><span class="badge bg-danger">{{ $violation->points }}</span></td>
                                <td><span class="badge bg-{{ $violation->action_taken ? 'success' : 'warning' }}">{{ $violation->action_taken ? 'Ditangani' : 'Pending' }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada insiden terbaru</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- KEHADIRAN 7 HARI --}}
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Trend Kehadiran 7 Hari</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    @for($i = 6; $i >= 0; $i--)
                        @php $d = now()->subDays($i); $h = \App\Models\StudentAttendance::where('attendance_date', $d->toDateString())->where('status', 'hadir')->count(); $t = \App\Models\StudentAttendance::where('attendance_date', $d->toDateString())->count(); $pct = $t > 0 ? round(($h/$t)*100) : 0; @endphp
                        <div class="d-flex align-items-center mb-1">
                            <span class="text-muted" style="width:60px;font-size:11px;">{{ $d->format('D') }}</span>
                            <div class="progress flex-grow-1" style="height:12px;">
                                <div class="progress-bar bg-{{ $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning' : 'danger') }}" style="width:{{ $pct }}%"></div>
                            </div>
                            <span class="ms-2" style="font-size:11px;">{{ $pct }}%</span>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- GTK BARU & PENSIUN --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">GTK Bulan Ini</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="p-3 bg-success-subtle rounded">
                            <h3 class="text-success fw-bold">{{ $gtkBaru ?? 0 }}</h3>
                            <small class="text-muted">GTK Baru</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-warning-subtle rounded">
                            <h3 class="text-warning fw-bold">{{ $gtkExpiring ?? 0 }}</h3>
                            <small class="text-muted">SK Approaching Expiry</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- QUICK STATS --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Ringkasan Manajemen</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="ri-file-text-line text-primary me-2"></i>Pengajuan GTK Pending</span>
                        <span class="badge bg-warning rounded-pill">{{ $pendingGtkRequests }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="ri-alert-line text-warning me-2"></i>SK GTK Mendekati Expired</span>
                        <span class="badge bg-warning rounded-pill">{{ $gtkExpiring }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="ri-group-line text-success me-2"></i>Total Santri Aktif</span>
                        <span class="badge bg-success rounded-pill">{{ $totalSantri }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    @if(isset($taskKepsek) && $taskKepsek)
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
                            <strong>{{ $taskKepsek->nama_tugas }}</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Jam/Minggu</small>
                            <strong>{{ $taskKepsek->hours_per_week ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TMT</small>
                            <strong>{{ $taskKepsek->tmt?->format('d M Y') ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TST</small>
                            <strong>{{ $taskKepsek->tst?->format('d M Y') ?? '<span class="text-success">Selamanya</span>' }}</strong>
                        </div>
                    </div>
                </div>
                @if($taskKepsek->decree)
                <div class="mt-3">
                    <small class="text-muted">SK: <code>{{ $taskKepsek->decree->decree_number }}</code></small>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
