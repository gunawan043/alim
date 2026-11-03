@extends('layouts.master')

@section('title', 'Detail Patroli')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2')
            <a href="{{ route('user.keamanan.patrol.index', ['userId' => $userId]) }}">Patroli & Ronda</a>
        @endslot
        @slot('title') {{ $schedule->code }} @endslot
    @endcomponent

    @php
        $total = $checkpoints->count();
        $done = count($loggedIds);
        $percent = $total > 0 ? (int) round($done / $total * 100) : 0;
    @endphp

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Informasi Jadwal</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small text-uppercase">Status</div>
                        <span class="badge bg-{{ $schedule->statusColor() }}-subtle text-{{ $schedule->statusColor() }} fs-6">
                            {{ $schedule->statusLabel() }}
                        </span>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small text-uppercase">Kemajuan</div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $done }} / {{ $total }} checkpoint</span>
                            <span class="fw-semibold">{{ $percent }}%</span>
                        </div>
                        <div class="metric-bar">
                            <span class="bg-success" style="width: {{ $percent }}%"></span>
                        </div>
                    </div>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th class="text-muted ps-0">Tanggal</th><td>{{ optional($schedule->patrol_date)->translatedFormat('d F Y') }}</td></tr>
                        <tr><th class="text-muted ps-0">Regu</th><td>{{ $schedule->team->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted ps-0">Komandan</th><td>{{ $schedule->team->leader->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted ps-0">Shift</th><td>{{ $schedule->shift->label() ?? '—' }}</td></tr>
                        <tr><th class="text-muted ps-0">Pos</th><td>{{ $schedule->post->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted ps-0">Dibuat</th><td>{{ $schedule->creator->name ?? '—' }}</td></tr>
                    </table>
                    @if($schedule->notes)
                        <div class="alert alert-light border mt-3 mb-0 small">{{ $schedule->notes }}</div>
                    @endif
                </div>
                <div class="card-footer d-flex flex-wrap gap-2">
                    <a href="{{ route('user.keamanan.patrol.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                    @if($schedule->status === \App\Models\Security\SecurityPatrolSchedule::STATUS_PLANNED)
                        <form method="POST" action="{{ route('user.keamanan.patrol.start', ['userId' => $userId, 'schedule' => $schedule->id]) }}">
                            @csrf
                            <button class="btn btn-sm btn-success"><i class="ri-play-fill me-1"></i> Mulai</button>
                        </form>
                    @endif
                    @if($schedule->status !== \App\Models\Security\SecurityPatrolSchedule::STATUS_DONE)
                        <form method="POST" action="{{ route('user.keamanan.patrol.finish', ['userId' => $userId, 'schedule' => $schedule->id]) }}"
                              data-confirm="Tandai patroli ini selesai?">
                            @csrf
                            <button class="btn btn-sm btn-primary"><i class="ri-check-double-line me-1"></i> Selesai</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-map-pin-line me-1 text-primary"></i> Checkpoint</h5>
                </div>
                <div class="card-body p-0">
                    @forelse($checkpoints as $checkpoint)
                        @php $scanned = in_array($checkpoint->id, $loggedIds, true); @endphp
                        <div class="d-flex align-items-center gap-3 px-3 py-3 border-bottom">
                            <span class="avatar-xs flex-shrink-0">
                                <span class="avatar-title rounded-circle bg-{{ $scanned ? 'success' : 'secondary' }}-subtle text-{{ $scanned ? 'success' : 'secondary' }}">
                                    <i class="{{ $scanned ? 'ri-check-line' : 'ri-map-pin-line' }}"></i>
                                </span>
                            </span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $checkpoint->name }}</div>
                                <div class="small text-muted">{{ $checkpoint->code }}{{ $checkpoint->location ? ' · '.$checkpoint->location : '' }}</div>
                            </div>
                            @if($scanned)
                                <span class="badge bg-success-subtle text-success">Sudah dipindai</span>
                            @else
                                <a href="{{ route('user.keamanan.patrol.scan', ['userId' => $userId, 'token' => $checkpoint->qr_token]) }}"
                                   class="btn btn-sm btn-soft-primary">
                                    <i class="ri-qr-scan-2-line me-1"></i> Pindai
                                </a>
                            @endif
                        </div>
                    @empty
                        <x-empty-state title="Belum ada checkpoint"
                            message="Tambahkan checkpoint terlebih dahulu agar patroli bisa dipantau.">
                            <a href="{{ route('user.keamanan.patrol.checkpoints.index', ['userId' => $userId]) }}" class="btn btn-primary mt-3">
                                Kelola Checkpoint
                            </a>
                        </x-empty-state>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-history-line me-1"></i> Riwayat Pemindaian</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Waktu</th>
                                    <th>Checkpoint</th>
                                    <th>Petugas</th>
                                    <th>Kondisi</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedule->logs->sortByDesc('scanned_at') as $log)
                                    <tr>
                                        <td>{{ optional($log->scanned_at)->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $log->checkpoint->name ?? '—' }}</div>
                                            <div class="small text-muted">{{ $log->checkpoint->code ?? '' }}</div>
                                        </td>
                                        <td>{{ $log->scanner->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log->condition === 'aman' ? 'success' : 'warning' }}-subtle text-{{ $log->condition === 'aman' ? 'success' : 'warning' }}">
                                                {{ \App\Models\Security\SecurityPatrolLog::CONDITION_OPTIONS[$log->condition] ?? $log->condition }}
                                            </span>
                                        </td>
                                        <td>{{ $log->notes ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="5" title="Belum ada pemindaian"
                                        message="Pindai checkpoint untuk memulai pencatatan patroli." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
