@extends('layouts.master')

@section('title', 'Jadwal Patroli')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2') Patroli & Ronda @endslot
        @slot('title') Jadwal Patroli @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Jadwal Hari Ini', 'value' => number_format($stats['today']), 'icon' => 'ri-calendar-todo-line', 'color' => 'primary'],
            ['label' => 'Selesai', 'value' => number_format($stats['done']), 'icon' => 'ri-checkbox-circle-line', 'color' => 'success'],
            ['label' => 'Berjalan', 'value' => number_format($stats['running']), 'icon' => 'ri-walk-line', 'color' => 'violet'],
            ['label' => 'Terlewat', 'value' => number_format($stats['missed']), 'icon' => 'ri-error-warning-line', 'color' => 'danger'],
        ],
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Jadwal Patroli</h5>
                            <p class="text-muted mb-0 small">Setiap jadwal memiliki checkpoint yang dipindai petugas.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('user.keamanan.patrol.checkpoints.index', ['userId' => $userId]) }}" class="btn btn-soft-secondary">
                                    <i class="ri-map-pin-line me-1"></i> Checkpoint
                                </a>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                                    <i class="ri-add-line me-1"></i> Buat Jadwal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <form method="GET" class="row g-2 align-items-end"
                          action="{{ route('user.keamanan.patrol.index', ['userId' => $userId]) }}">
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Tanggal</label>
                            <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach(\App\Models\Security\SecurityPatrolSchedule::STATUS_OPTIONS as $value => $label)
                                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Pos Jaga</label>
                            <select name="post_id" class="form-select form-select-sm">
                                <option value="">Semua pos</option>
                                @foreach($posts as $post)
                                    <option value="{{ $post->id }}" {{ request('post_id') === $post->id ? 'selected' : '' }}>{{ $post->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="ri-filter-3-line me-1"></i> Terapkan</button>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Tanggal</th>
                                    <th>Regu</th>
                                    <th>Shift</th>
                                    <th>Pos</th>
                                    <th>Checkpoint</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedules as $schedule)
                                    <tr>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $schedule->code }}</span></td>
                                        <td>{{ optional($schedule->patrol_date)->translatedFormat('d M Y') }}</td>
                                        <td>{{ $schedule->team->name ?? '—' }}</td>
                                        <td>{{ $schedule->shift->label() ?? '—' }}</td>
                                        <td>{{ $schedule->post->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">{{ $schedule->logs_count }} dipindai</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $schedule->statusColor() }}-subtle text-{{ $schedule->statusColor() }}">
                                                {{ $schedule->statusLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.keamanan.patrol.show', ['userId' => $userId, 'schedule' => $schedule->id]) }}">
                                                            <i class="ri-eye-fill text-info me-2"></i> Lihat Detail
                                                        </a>
                                                    </li>
                                                    @if($schedule->status === \App\Models\Security\SecurityPatrolSchedule::STATUS_PLANNED)
                                                        <li>
                                                            <form method="POST" action="{{ route('user.keamanan.patrol.start', ['userId' => $userId, 'schedule' => $schedule->id]) }}">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="ri-play-fill text-success me-2"></i> Mulai Patroli
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                    @if($schedule->status !== \App\Models\Security\SecurityPatrolSchedule::STATUS_DONE)
                                                        <li>
                                                            <form method="POST" action="{{ route('user.keamanan.patrol.finish', ['userId' => $userId, 'schedule' => $schedule->id]) }}"
                                                                  data-confirm="Tandai patroli ini selesai?">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="ri-check-double-line text-primary me-2"></i> Tandai Selesai
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" title="Belum ada jadwal patroli"
                                        message="Buat jadwal agar petugas dapat memindai checkpoint.">
                                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                                            <i class="ri-add-line me-1"></i> Buat Jadwal
                                        </button>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($schedules->hasPages())
                        @include('shared._pagination', ['paginator' => $schedules])
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-calendar-todo-line me-2 text-primary"></i> Buat Jadwal Patroli</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('user.keamanan.patrol.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="patrol_date" class="form-control" value="{{ old('patrol_date', today()->toDateString()) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Regu</label>
                                <select name="team_id" class="form-select">
                                    <option value="">— Tanpa regu —</option>
                                    @foreach($teams as $team)
                                        <option value="{{ $team->id }}" {{ old('team_id') === $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Shift</label>
                                <select name="shift_id" class="form-select">
                                    <option value="">— Ikut regu —</option>
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}" {{ old('shift_id') === $shift->id ? 'selected' : '' }}>{{ $shift->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pos Jaga</label>
                                <select name="post_id" class="form-select">
                                    <option value="">— Ikut regu —</option>
                                    @foreach($posts as $post)
                                        <option value="{{ $post->id }}" {{ old('post_id') === $post->id ? 'selected' : '' }}>{{ $post->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
