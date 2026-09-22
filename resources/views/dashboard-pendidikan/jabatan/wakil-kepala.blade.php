@extends('layouts.master')

@section('title', 'Dashboard Wakil Kepala Satuan Pendidikan')

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
    @slot('title') Wakil Kepala Satuan Pendidikan @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Kelas Diampu" :value="$totalKelas" icon="ri-school-line" color="primary" />
    <x-dashboards.stat-card label="Guru Absen Hari Ini" :value="$guruAbsen" icon="ri-user-unfollow-line" color="danger" />
    <x-dashboards.stat-card label="Pengajuan GTK Pending" :value="$pengajuanPending" icon="ri-mail-unread-line" color="warning" />
    <x-dashboards.stat-card label="Tugas Koordinator Pending" :value="$tugasPending" icon="ri-task-line" color="info" />
</div>

<div class="row g-3 mb-3">
    {{-- JADWAL PIKET --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Jadwal Piket Hari Ini</h5>
                <a href="{{ route('user.gtk.indexguru', ['userId' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Kelola</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kelas</th>
                                <th>Wali Kelas</th>
                                <th>No. HP</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($piketList as $kg)
                            <tr>
                                <td>{{ $kg->gradeLevel?->name ?? '-' }} {{ $kg->name }}</td>
                                <td>{{ $kg->homeroomTeacher?->name ?? '-' }}</td>
                                <td>{{ $kg->homeroomTeacher?->phone ?? '-' }}</td>
                                <td><span class="badge bg-success">Hadir</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data piket</td></tr>
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
                    <a href="{{ route('user.class-qr.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-success">
                        <i class="ri-qr-code-line me-1"></i>Class QR
                    </a>
                    <a href="{{ route('user.supervisi.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-warning">
                        <i class="ri-clipboard-line me-1"></i>Supervisi Mengajar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- JADWAL PELAJARAN --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Jadwal Pelajaran Hari Ini</h5>
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
                            @forelse($jadwalHariIni as $jm)
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
</div>

<div class="row g-3">
    @if(isset($taskWaka) && $taskWaka)
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ri-task-line text-primary me-1"></i>Tugas Tambahan
                </h5>
                <a href="{{ route('user.satuan-kerja.additional-tasks', ['userId' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Kelola</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Nama Tugas</small>
                            <strong>{{ $taskWaka->nama_tugas }}</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Jam/Minggu</small>
                            <strong>{{ $taskWaka->hours_per_week ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TMT</small>
                            <strong>{{ $taskWaka->tmt?->format('d M Y') ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TST</small>
                            <strong>{{ $taskWaka->tst?->format('d M Y') ?? '<span class="text-success">Selamanya</span>' }}</strong>
                        </div>
                    </div>
                </div>
                @if($taskWaka->decree)
                <div class="mt-3">
                    <small class="text-muted">SK: <code>{{ $taskWaka->decree->decree_number }}</code></small>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
