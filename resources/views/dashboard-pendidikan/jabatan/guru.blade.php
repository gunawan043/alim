@extends('layouts.master')

@section('title', 'Dashboard Guru')

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
    @slot('title') Guru @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Jam Pelajaran Hari Ini" :value="$jadwalHariIni" icon="ri-time-line" color="primary" />
    <x-dashboards.stat-card label="Kelas Berlangsung" :value="$kelasAktif" icon="ri-play-circle-line" color="success" />
    <x-dashboards.stat-card label="Siswa Absen di Kelas" :value="$siswaAbsen" icon="ri-user-unfollow-line" color="danger" />
    <x-dashboards.stat-card label="Grading Pending" :value="$gradingPending" icon="ri-file-text-line" color="warning" />
</div>

<div class="row g-3 mb-3">
    {{-- JADWAL HARI INI --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Jadwal Hari Ini</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Jam</th>
                                <th>Kelas</th>
                                <th>Mapel</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jadwalDetail as $jm)
                            <tr>
                                <td>{{ $jm->start_time ?? '-' }} - {{ $jm->end_time ?? '-' }}</td>
                                <td>{{ $jm->studyGroup?->full_name ?? '-' }}</td>
                                <td>{{ $jm->subject?->name ?? '-' }}</td>
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
                            <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada jadwal hari ini</td></tr>
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
                        <i class="ri-qr-code-line me-1"></i>Ambil Absensi
                    </a>
                    <a href="{{ route('user.schools.nilai-kelas.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-success">
                        <i class="ri-bar-chart-line me-1"></i>Input Nilai
                    </a>
                    <a href="{{ route('user.violation-points.create', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-warning">
                        <i class="ri-alert-line me-1"></i>Catat Pelanggaran
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- PENDING GRADING --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Grading Pending</h5>
                <a href="{{ route('user.schools.nilai-kelas.index', ['userId' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kelas</th>
                                <th>Mapel</th>
                                <th>Jenis Nilai</th>
                                <th>Deadline</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingGrading as $book)
                            <tr>
                                <td>{{ $book->studyGroup?->full_name ?? '-' }}</td>
                                <td>{{ $book->subject?->name ?? '-' }}</td>
                                <td><span class="badge bg-info">{{ $book->semester ?? '-' }}</span></td>
                                <td><span class="text-muted">-</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Semua nilai sudah terinput</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- TUGAS TAMBAHAN: WALI KELAS --}}
    @if($taskWaliKelas && $waliKelasInfo)
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-header bg-primary bg-opacity-10 border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ri-user-star-line text-primary me-1"></i>Tugas Tambahan: Wali Kelas
                </h5>
                <a href="{{ route('user.schools.satuan-kerja.additional-tasks', ['userId' => $user->id, 'workUnitId' => $primaryWorkUnit?->id]) }}" class="btn btn-sm btn-outline-primary">Kelola</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="p-3 bg-primary bg-opacity-10 rounded">
                            <small class="text-muted d-block">Kelas Diampu</small>
                            <strong class="text-primary">{{ $waliKelasInfo['kelas']->full_name }}</strong>
                            <br><small class="text-muted">{{ $waliKelasInfo['kelas']->gradeLevel?->name ?? '-' }}</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Total Santri</small>
                            <strong>{{ $waliKelasInfo['totalSantri'] }}</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Hadir Hari Ini</small>
                            <strong class="text-success">{{ $waliKelasInfo['hadirToday'] }}</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Persentase</small>
                            <strong>{{ $waliKelasInfo['hadirPercent'] }}%</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">SK Tugas</small>
                            <code>{{ $taskWaliKelas->decree?->decree_number ?? '-' }}</code>
                            <br><small class="text-muted">TMT: {{ $taskWaliKelas->tmt?->format('d M Y') ?? '-' }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- TUGAS TAMBAHAN: KOORDINATOR GURU --}}
    @if($taskGuruKoor)
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ri-task-line text-primary me-1"></i>Tugas Tambahan: Koordinator Guru
                </h5>
                <a href="{{ route('user.schools.satuan-kerja.additional-tasks', ['userId' => $user->id, 'workUnitId' => $primaryWorkUnit?->id]) }}" class="btn btn-sm btn-outline-primary">Kelola</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Nama Tugas</small>
                            <strong>{{ $taskGuruKoor->nama_tugas }}</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Jam/Minggu</small>
                            <strong>{{ $taskGuruKoor->hours_per_week ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TMT</small>
                            <strong>{{ $taskGuruKoor->tmt?->format('d M Y') ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TST</small>
                            <strong>{{ $taskGuruKoor->tst?->format('d M Y') ?? '<span class="text-success">Selamanya</span>' }}</strong>
                        </div>
                    </div>
                </div>
                @if($taskGuruKoor->decree)
                <div class="mt-3">
                    <small class="text-muted">SK: <code>{{ $taskGuruKoor->decree->decree_number }}</code></small>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- TUGAS TAMBAHAN: LAINNYA --}}
    @foreach([$taskEkskul, $taskLab, $taskSarpras] as $task)
        @if($task)
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ri-task-line text-primary me-1"></i>Tugas Tambahan: {{ ucwords(str_replace('_', ' ', $task->nama_tugas)) }}
                    </h5>
                    <a href="{{ route('user.schools.satuan-kerja.additional-tasks', ['userId' => $user->id, 'workUnitId' => $primaryWorkUnit?->id]) }}" class="btn btn-sm btn-outline-primary">Kelola</a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Nama Tugas</small>
                                <strong>{{ $task->nama_tugas }}</strong>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Jam/Minggu</small>
                                <strong>{{ $task->hours_per_week ?? '-' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">TMT</small>
                                <strong>{{ $task->tmt?->format('d M Y') ?? '-' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">TST</small>
                                <strong>{{ $task->tst?->format('d M Y') ?? '<span class="text-success">Selamanya</span>' }}</strong>
                            </div>
                        </div>
                    </div>
                    @if($task->decree)
                    <div class="mt-3">
                        <small class="text-muted">SK: <code>{{ $task->decree->decree_number }}</code></small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    @endforeach
</div>
@endsection
