@extends('layouts.master')

@section('title', 'Dashboard Koordinator Guru')

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
    @slot('title') Koordinator Guru @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Total Guru" :value="$totalGuruKoor" icon="ri-user-line" color="primary" />
    <x-dashboards.stat-card label="Guru Absen Hari Ini" :value="$guruAbsen" icon="ri-user-unfollow-line" color="danger" />
    <x-dashboards.stat-card label="Jadwal Berlangsung" :value="$jadwalBerlangsung" icon="ri-play-circle-line" color="success" />
    <x-dashboards.stat-card label="Kehadiran Bulanan" :value="$kehadiranPct . '%'" icon="ri-checkbox-circle-line" color="info" />
</div>

<div class="row g-3 mb-3">
    {{-- JADWAL MENGAJAR HARI INI --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Jadwal Mengajar Hari Ini</h5>
                <a href="{{ route('user.jadwal-kbm.index', ['userId' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Kelola</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Jam</th>
                                <th>Kelas</th>
                                <th>Mapel</th>
                                <th>Guru</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(\App\Models\JadwalKbm::where('date', now()->toDateString())->with(['studyGroup.gradeLevel', 'subject', 'teacher'])->limit(10)->get() as $jm)
                            <tr>
                                <td>{{ $jm->start_time ?? '-' }} - {{ $jm->end_time ?? '-' }}</td>
                                <td>{{ $jm->studyGroup?->full_name ?? '-' }}</td>
                                <td>{{ $jm->subject?->name ?? '-' }}</td>
                                <td>{{ $jm->teacher?->name ?? '-' }}</td>
                                <td>
                                    @if($jm->end_time && $jm->end_time <= now()->format('H:i'))
                                        <span class="badge bg-success">Selesai</span>
                                    @elseif($jm->start_time && $jm->start_time <= now()->format('H:i'))
                                        <span class="badge bg-primary">Berlangsung</span>
                                    @else
                                        <span class="badge bg-secondary">Belum</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada jadwal hari ini</td></tr>
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
                    <a href="{{ route('user.absensi-gtk.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-primary">
                        <i class="ri-checkbox-circle-line me-1"></i>Absensi Guru
                    </a>
                    <a href="{{ route('waka.supervisi.index') }}" class="btn quick-action-btn btn-outline-success">
                        <i class="ri-clipboard-line me-1"></i>Supervisi Mengajar
                    </a>
                    <a href="{{ route('user.schools.nilai-kelas.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-warning">
                        <i class="ri-bar-chart-line me-1"></i>Rekap Nilai Per Mapel
                    </a>
                    <a href="{{ route('user.jadwal-kbm.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-info">
                        <i class="ri-calendar-line me-1"></i>Jadwal Mengajar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- SUBSTITUSI HARI INI --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Substitusi / Pengganti Hari Ini</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($substitusi as $sub)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $sub->teacher?->name ?? 'Guru Tidak Dikenal' }}</strong>
                            <br><small class="text-muted">Mengantikan jam {{ $sub->start_time ?? '-' }} - {{ $sub->end_time ?? '-' }}</small>
                        </div>
                        <span class="badge bg-warning text-dark">Substitusi</span>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">Tidak ada substitusi hari ini</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- KEHADIRAN BULANAN --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Kehadiran Guru Bulan Ini</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Persentase Kehadiran</small>
                        <small class="fw-bold">{{ $kehadiranPct }}%</small>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-{{ $kehadiranPct >= 90 ? 'success' : ($kehadiranPct >= 75 ? 'warning' : 'danger') }}" style="width: {{ $kehadiranPct }}%"></div>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="p-2 bg-success-subtle rounded">
                            <h5 class="text-success fw-bold mb-0">{{ $kehadiranBulanan }}</h5>
                            <small class="text-muted">Hari Hadir</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-danger-subtle rounded">
                            <h5 class="text-danger fw-bold mb-0">{{ $guruAbsen }}</h5>
                            <small class="text-muted">Hari Ini Absen</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
