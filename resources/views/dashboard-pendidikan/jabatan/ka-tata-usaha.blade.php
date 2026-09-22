@extends('layouts.master')

@section('title', 'Dashboard Kepala Tata Usaha')

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
    @slot('title') Kepala Tata Usaha @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Surat Masuk Hari Ini" :value="$suratMasuk" icon="ri-mail-open-line" color="primary" />
    <x-dashboards.stat-card label="Surat Keluar Hari Ini" :value="$suratKeluar" icon="ri-send-plane-line" color="success" />
    <x-dashboards.stat-card label="Dokumen GTK Expiring" :value="$dokumenExpiring" icon="ri-alert-line" color="danger" />
    <x-dashboards.stat-card label="GTK Baru Bulan Ini" :value="$gtkBaru" icon="ri-user-add-line" color="info" />
</div>

<div class="row g-3 mb-3">
    {{-- ANTRIAN SURAT MASUK --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Antrian Surat Masuk</h5>
                <a href="{{ route('user.surat-masuk.index', ['userId' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Pengirim</th>
                                <th>Perihal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suratPending as $s)
                            <tr>
                                <td>{{ $s->created_at?->format('d M Y') }}</td>
                                <td>{{ $s->pengirim ?? '-' }}</td>
                                <td>{{ Str::limit($s->perihal ?? $s->subject ?? '-', 40) }}</td>
                                <td><span class="badge bg-warning">Pending</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada surat pending</td></tr>
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
                    <a href="{{ route('user.surat-masuk.create', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-primary">
                        <i class="ri-mail-add-line me-1"></i>Catat Surat Masuk
                    </a>
                    <a href="{{ route('user.gtk.indexguru', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-success">
                        <i class="ri-group-line me-1"></i>Kelola Arsip GTK
                    </a>
                    <a href="{{ route('user.dokumen-iso.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-warning">
                        <i class="ri-file-list-3-line me-1"></i>Status Dokumen ISO
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- LOG AKTIVITAS --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Log Aktivitas Hari Ini</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Aksi</th>
                                <th>Tabel</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logAktivitas as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('H:i') }}</td>
                                <td>{{ $log->user_id ?? '-' }}</td>
                                <td>{{ $log->action ?? '-' }}</td>
                                <td><span class="badge bg-secondary">{{ $log->table_name ?? '-' }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada aktivitas</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    @if(isset($taskKaTU) && $taskKaTU)
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
                            <strong>{{ $taskKaTU->nama_tugas }}</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Jam/Minggu</small>
                            <strong>{{ $taskKaTU->hours_per_week ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TMT</small>
                            <strong>{{ $taskKaTU->tmt?->format('d M Y') ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TST</small>
                            <strong>{{ $taskKaTU->tst?->format('d M Y') ?? '<span class="text-success">Selamanya</span>' }}</strong>
                        </div>
                    </div>
                </div>
                @if($taskKaTU->decree)
                <div class="mt-3">
                    <small class="text-muted">SK: <code>{{ $taskKaTU->decree->decree_number }}</code></small>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
