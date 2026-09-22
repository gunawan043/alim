@extends('layouts.master')

@section('title', 'Dashboard Staf Tata Usaha')

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
    @slot('title') Staf Tata Usaha @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Surat Masuk Hari Ini" :value="$suratMasuk" icon="ri-mail-open-line" color="primary" />
    <x-dashboards.stat-card label="Surat Keluar Hari Ini" :value="$suratKeluar" icon="ri-send-plane-line" color="success" />
    <x-dashboards.stat-card label="Dokumen GTK Pending" :value="$dokumenPending" icon="ri-file-warning-line" color="warning" />
    <x-dashboards.stat-card label="Agenda TU Hari Ini" :value="$agendaHariIni" icon="ri-calendar-event-line" color="info" />
</div>

<div class="row g-3 mb-3">
    {{-- INBOX SURAT --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Inbox Surat</h5>
                <a href="{{ route('user.surat-masuk.index', ['userId' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($inboxSurat as $s)
                    <a href="{{ route('user.surat-masuk.show', ['userId' => $user->id, 'suratMasuk' => $s->id]) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">{{ Str::limit($s->perihal ?? $s->subject ?? 'Tanpa Perihal', 40) }}</h6>
                            <small class="text-muted">{{ $s->created_at?->format('d M H:i') }}</small>
                        </div>
                        <small class="text-muted">{{ $s->pengirim ?? 'Unknown' }}</small>
                    </a>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">Tidak ada surat masuk pending</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- OUTBOX SURAT --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Outbox Surat</h5>
                <a href="{{ route('user.surat-keluar.index', ['userId' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($outboxSurat as $s)
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">{{ Str::limit($s->perihal ?? $s->subject ?? 'Tanpa Perihal', 40) }}</h6>
                            <small class="text-muted">{{ $s->created_at?->format('d M H:i') }}</small>
                        </div>
                        <small class="text-muted">{{ $s->tujuan ?? 'Unknown' }}</small>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">Belum ada surat keluar</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- AGENDA --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Agenda TU Hari Ini</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Kegiatan</th>
                                <th>PIC</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agendaList as $a)
                            <tr>
                                <td>{{ $a->start_date?->format('d M Y') }}</td>
                                <td>{{ $a->name ?? '-' }}</td>
                                <td>{{ $a->workUnit?->name ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada agenda hari ini</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    @if(isset($taskStafTU) && $taskStafTU)
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
                            <strong>{{ $taskStafTU->nama_tugas }}</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Jam/Minggu</small>
                            <strong>{{ $taskStafTU->hours_per_week ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TMT</small>
                            <strong>{{ $taskStafTU->tmt?->format('d M Y') ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">TST</small>
                            <strong>{{ $taskStafTU->tst?->format('d M Y') ?? '<span class="text-success">Selamanya</span>' }}</strong>
                        </div>
                    </div>
                </div>
                @if($taskStafTU->decree)
                <div class="mt-3">
                    <small class="text-muted">SK: <code>{{ $taskStafTU->decree->decree_number }}</code></small>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
