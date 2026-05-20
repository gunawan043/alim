@extends('waka.master')
@section('title') Dashboard Akademik @endsection
@php
    $currentUser = auth()->user();
    $userId = $currentUser->id;
@endphp

@section('css')
<style>
.card-animate { transition: all 0.3s ease; }
.card-animate:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.cap-bar { height: 6px; border-radius: 3px; }
.cap-bar.over { background: linear-gradient(90deg, #f5222d 0%, #ff7875 100%); }
.cap-bar.warn { background: linear-gradient(90deg, #faad14 0%, #ffc53d 100%); }
.cap-bar.ok   { background: linear-gradient(90deg, #52c41a 0%, #73d13d 100%); }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.badge-over { font-size: 10px; padding: 2px 6px; border-radius: 4px; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') Dashboard Akademik @endslot
@endcomponent

{{-- Alert: Rombel Melebihi Kapasitas --}}
@if($overCapacityRombels->isNotEmpty())
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="ri-error-warning-fill fs-4"></i>
    <div>
        <strong>{{ $overCapacityRombels->count() }} rombel melebihi kapasitas:</strong>
        @foreach($overCapacityRombels->take(5) as $sg)
            <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $sg['id']]) }}"
               class="fw-bold text-decoration-none text-danger">{{ $sg['full_name'] }}</a>
            ({{ $sg['student_count'] }}/{{ $sg['capacity'] }} — +{{ $sg['over_by'] }} lebih)
            @if(!$loop->last), @endif
        @endforeach
        @if($overCapacityRombels->count() > 5) dan {{ $overCapacityRombels->count() - 5 }} lainnya.@endif
        <br><span class="text-muted small">Segera gunakan menu Pindahkan Santri atau tambah kapasitas.</span>
    </div>
</div>
@endif

{{-- Tahun Ajaran Active --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <span class="text-muted small">Tahun Ajaran Aktif:</span>
        <strong class="ms-1">{{ $activeAcademicYear?->name ?? '-' }} ({{ $activeAcademicYear?->semester_text ?? '-' }})</strong>
    </div>
    <div class="d-flex gap-2">
        @foreach($quickActions as $qa)
        <a href="{{ route($qa['route'], ['userId' => $userId]) }}" class="btn btn-outline-{{ $qa['color'] }} btn-sm">
            <i class="{{ $qa['icon'] }} me-1"></i>{{ $qa['label'] }}
        </a>
        @endforeach
    </div>
</div>

{{-- ROW 1: Overview Stats --}}
<div class="row g-3 mb-3">
    {{-- Total Santri --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-subtle">
                        <i class="bx bx-group text-primary fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Total Santri</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total_students']) }}</h2>
                        <small class="text-muted">{{ number_format($stats['active_students']) }} aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Di Rombel --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success-subtle">
                        <i class="bx bx-check-circle text-success fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Di Rombel</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($stats['in_rombel']) }}</h2>
                        @if($stats['unassigned'] > 0)
                        <a href="{{ route('user.students.index', ['userId' => $userId]) }}"
                           class="badge bg-warning-subtle text-warning badge-over mt-1">
                            <i class="ri-error-warning-line"></i> {{ $stats['unassigned'] }} belum rombel
                        </a>
                        @else
                        <small class="text-success"><i class="ri-checkbox-circle-fill me-1"></i>Semua ter-assign</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- GTK --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-info-subtle">
                        <i class="bx bx-user-circle text-info fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">GTK</p>
                        <h2 class="fw-bold ff-secondary mb-0">{{ number_format($gtkStats['total']) }}</h2>
                        <small class="text-muted">
                            <span class="text-primary">{{ $gtkStats['guru'] }} guru</span>
                            / <span class="text-info">{{ $gtkStats['tendik'] }} tendik</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mutasi Bulan Ini --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-subtle">
                        <i class="bx bx-refresh text-warning fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Mutasi Bulan Ini</p>
                        <h2 class="fw-bold ff-secondary mb-0">
                            {{ $stats['mutation_in_month'] + $stats['mutation_out_month'] }}
                        </h2>
                        <small class="text-muted">
                            <span class="text-success">{{ $stats['mutation_in_month'] }} masuk</span>
                            / <span class="text-danger">{{ $stats['mutation_out_month'] }} keluar</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ROW 2: Distribusi Per Tingkat + Kapasitas Rombel --}}
<div class="row g-3 mb-3">
    {{-- Distribusi Per Tingkat --}}
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header bg-light border-bottom-dashed">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="ri-bar-chart-line me-1"></i>Distribusi Per Tingkat</h5>
                    <a href="{{ route('user.students.index', ['userId' => $userId]) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light text-muted" style="font-size:11px">
                            <tr>
                                <th>Tingkat</th>
                                <th class="text-center">Santri</th>
                                <th class="text-center">Kapasitas</th>
                                <th style="width:120px">Pengisian</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:12px">
                            @forelse($levelDistribution as $row)
                            <tr class="{{ $row->over_capacity ? 'table-danger' : '' }}">
                                <td>
                                    <strong>{{ $row->level_name }}</strong>
                                    @if($row->over_capacity)
                                        <i class="ri-error-warning-fill text-danger ms-1" title="Melebihi kapasitas"></i>
                                    @endif
                                </td>
                                <td class="text-center fw-semibold">{{ number_format($row->student_count) }}</td>
                                <td class="text-center text-muted">{{ number_format($row->total_capacity) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 cap-bar" style="height:6px">
                                            <div class="progress-bar bg-{{ $row->over_capacity ? 'danger' : ($row->filled_pct >= 90 ? 'warning' : 'success') }}"
                                                 style="width:{{ $row->filled_pct }}%"></div>
                                        </div>
                                        <span class="badge bg-{{ $row->over_capacity ? 'danger' : ($row->filled_pct >= 90 ? 'warning' : 'success') }}-subtle text-{{ $row->over_capacity ? 'danger' : ($row->filled_pct >= 90 ? 'warning' : 'success') }}"
                                              style="font-size:10px;white-space:nowrap">
                                            {{ $row->filled_pct }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Belum ada data</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Kapasitas Per Rombel --}}
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header bg-light border-bottom-dashed">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="ri-dashboard-3-line me-1"></i>Status Kapasitas Rombel</h5>
                    <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:280px;overflow-y:auto">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light text-muted sticky-top" style="font-size:11px;top:0;z-index:1">
                            <tr>
                                <th>Rombel</th>
                                <th class="text-center">Kapasitas</th>
                                <th style="width:120px">Pengisian</th>
                                <th class="text-center">Wali Kelas</th>
                                <th class="text-center">Ruang</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:12px">
                            @forelse($rombelCapacity as $sg)
                            <tr class="{{ $sg['is_over'] ? 'table-danger' : ($sg['filled_pct'] >= 90 ? 'table-warning' : '') }}">
                                <td>
                                    <a href="{{ route('user.students.index', ['userId' => $userId, 'study_group_id' => $sg['id']]) }}"
                                       class="fw-medium text-primary small">
                                        {{ $sg['full_name'] }}
                                    </a>
                                    @if($sg['is_over'])
                                        <span class="badge bg-danger-subtle text-danger badge-over ms-1">PENUH +{{ $sg['over_by'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="fw-semibold">{{ $sg['student_count'] }}</span>
                                    <span class="text-muted">/{{ $sg['capacity'] }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="progress flex-grow-1 cap-bar" style="height:4px">
                                            <div class="progress-bar bg-{{ $sg['is_over'] ? 'danger' : ($sg['filled_pct'] >= 90 ? 'warning' : 'success') }}"
                                                 style="width:{{ $sg['filled_pct'] }}%"></div>
                                        </div>
                                        <span class="text-muted" style="font-size:10px;white-space:nowrap">{{ $sg['sisa'] }} sisa</span>
                                    </div>
                                </td>
                                <td class="text-center text-muted small">{{ $sg['teacher'] ?? '-' }}</td>
                                <td class="text-center text-muted small">{{ $sg['room'] ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada rombel</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ROW 3: Mutasi + Pelanggaran + Prestasi --}}
<div class="row g-3 mb-3">
    {{-- Mutasi Masuk --}}
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-light border-bottom-dashed py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="ri-login-box-line text-success me-1"></i>Mutasi Masuk</h5>
                    <a href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}"
                       class="btn btn-sm btn-outline-success">
                        <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($recentMutations['in']->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="ri-inbox-line fs-1 d-block mb-2 opacity-25"></i>
                        <small>Belum ada mutasi masuk</small>
                    </div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($recentMutations['in'] as $m)
                        <li class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
                            <div>
                                <div class="fw-semibold small">{{ $m->student?->name ?? '-' }}</div>
                                <div class="text-muted" style="font-size:10px">
                                    {{ $m->student?->nisn ?? '-' }} · {{ $m->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <span class="badge bg-{{ $m->status === 'approved' ? 'success' : 'warning' }}-subtle
                                         text-{{ $m->status === 'approved' ? 'success' : 'warning' }} badge-over">
                                {{ $m->status === 'approved' ? 'Disetujui' : 'Menunggu' }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- Mutasi Keluar --}}
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-light border-bottom-dashed py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="ri-logout-box-line text-danger me-1"></i>Mutasi Keluar</h5>
                    <a href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}"
                       class="btn btn-sm btn-outline-danger">
                        <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($recentMutations['out']->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="ri-inbox-line fs-1 d-block mb-2 opacity-25"></i>
                        <small>Belum ada mutasi keluar</small>
                    </div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($recentMutations['out'] as $m)
                        <li class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
                            <div>
                                <div class="fw-semibold small">{{ $m->student?->name ?? '-' }}</div>
                                <div class="text-muted" style="font-size:10px">
                                    {{ $m->student?->nisn ?? '-' }} · {{ $m->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <span class="badge bg-{{ $m->status === 'approved' ? 'danger' : 'warning' }}-subtle
                                         text-{{ $m->status === 'approved' ? 'danger' : 'warning' }} badge-over">
                                {{ $m->status === 'approved' ? 'Disetujui' : 'Menunggu' }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- Prestasi Terbaru --}}
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-light border-bottom-dashed py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="ri-trophy-line text-warning me-1"></i>Prestasi Terbaru</h5>
                    <a href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'akademik']) }}"
                       class="btn btn-sm btn-outline-warning">
                        <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($recentAchievements->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="ri-trophy-line fs-1 d-block mb-2 opacity-25"></i>
                        <small>Belum ada data prestasi</small>
                    </div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($recentAchievements as $a)
                        <li class="list-group-item d-flex align-items-center gap-2 py-2 px-3">
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">{{ $a->student?->name ?? '-' }}</div>
                                <div class="text-muted" style="font-size:10px">{{ $a->name ?? '-' }}</div>
                            </div>
                            <span class="badge bg-warning-subtle text-warning badge-over">{{ $a->level ?? '-' }}</span>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ROW 4: Quick Links + Info --}}
<div class="row g-3">
    {{-- Daftar Rombel --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header bg-light border-bottom-dashed">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="ri-group-line me-1"></i>Daftar Rombel</h5>
                    <span class="badge bg-primary-subtle text-primary">{{ $stats['total_rombel'] }} rombel</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:200px;overflow-y:auto">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <tbody>
                            @forelse($rombelList as $sg)
                            <tr>
                                <td>
                                    <a href="{{ route('user.students.index', ['userId' => $userId, 'study_group_id' => $sg['id']]) }}"
                                       class="fw-medium text-primary small">
                                        {{ $sg['full_name'] }}
                                    </a>
                                </td>
                                <td class="text-muted small text-center">{{ $sg['teacher'] ?? '-' }}</td>
                                <td class="text-muted small text-center">{{ $sg['room'] ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-2">Belum ada rombel</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Panel --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-light border-bottom-dashed">
                <h5 class="mb-0"><i class="ri-information-2-line me-1"></i>Informasi Sistem</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light-subtle">
                            <div class="text-muted small">Tahun Ajaran</div>
                            <div class="fw-semibold">{{ $activeAcademicYear?->name ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light-subtle">
                            <div class="text-muted small">Semester</div>
                            <div class="fw-semibold">{{ $activeAcademicYear?->semester_text ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light-subtle">
                            <div class="text-muted small">Total GTK Aktif</div>
                            <div class="fw-semibold">{{ number_format($gtkStats['total']) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light-subtle">
                            <div class="text-muted small">Total Santri Aktif</div>
                            <div class="fw-semibold">{{ number_format($stats['active_students']) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('user.student-move.index', ['userId' => $userId]) }}"
                           class="p-2 border rounded text-decoration-none d-block text-danger bg-danger-subtle">
                            <div class="small"><i class="ri-arrow-left-right-line me-1"></i>Pindahkan Santo</div>
                            <div class="fw-bold small">Tingkat Sama</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('user.bulk-promotion.index', ['userId' => $userId]) }}"
                           class="p-2 border rounded text-decoration-none d-block text-warning bg-warning-subtle">
                            <div class="small"><i class="ri-arrow-up-line me-1"></i>Kenaikan Kelas</div>
                            <div class="fw-bold small">Naik Tingkat</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
@endsection