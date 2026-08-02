@extends('layouts.master')
@section('title') Log Kegiatan Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Log Kegiatan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate h-90 border-start border-primary">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-list-check-3 fs-24 text-primary"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Log</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate h-90 border-start border-success">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-user-follow-line fs-24 text-success"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Hadir</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['hadir'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate h-90 border-start border-warning">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-time-line fs-24 text-warning"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Tidak Hadir</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['tidak_hadir'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Log Kegiatan</h5>
                            <p class="text-muted mb-0">{{ $dormitory->name }} &mdash; {{ $activeYear->name ?? 'Tahun Ajaran Aktif' }}</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Catat Kegiatan
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filters --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="date" name="log_date" class="form-control"
                                   value="{{ request('log_date', $date) }}">
                        </div>
                        <div class="col-md-3">
                            <select name="session" class="form-control">
                                <option value="">Semua Sesi</option>
                                <option value="subuh"  {{ request('session', $session) == 'subuh'  ? 'selected' : '' }}>Subuh</option>
                                <option value="pagi"   {{ request('session', $session) == 'pagi'   ? 'selected' : '' }}>Pagi</option>
                                <option value="siang"  {{ request('session', $session) == 'siang'  ? 'selected' : '' }}>Siang</option>
                                <option value="sore"   {{ request('session', $session) == 'sore'   ? 'selected' : '' }}>Sore</option>
                                <option value="isya"   {{ request('session', $session) == 'isya'   ? 'selected' : '' }}>Isya</option>
                                <option value="malam"  {{ request('session', $session) == 'malam'  ? 'selected' : '' }}>Malam</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line"></i> Filter</button>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('user.asrama.activities.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Santri</th>
                                    <th>Kamar</th>
                                    <th>Sesi</th>
                                    <th>Data Aktivitas</th>
                                    <th>Catatan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $i => $log)
                                    @php
                                        $dataItems = [];
                                        try {
                                            $decoded = is_string($log->data) ? json_decode($log->data, true) : $log->data;
                                            if (is_array($decoded)) { $dataItems = $decoded; }
                                        } catch (\Exception $e) { $dataItems = []; }
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $log->student?->name ?? '&mdash;' }}</div>
                                            @if($log->student?->nisn)
                                                <div class="text-muted small">NISN: {{ $log->student->nisn }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->room)
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $log->room->name }}</span>
                                            @else
                                                <span class="text-muted">&mdash;</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $sessionBadge = match($log->session) {
                                                    'subuh'  => ['bg-info-subtle text-info',   'ri-sun-line'],
                                                    'pagi'   => ['bg-warning-subtle text-warning', 'ri-sun-foggy-line'],
                                                    'siang'  => ['bg-primary-subtle text-primary', 'ri-sun-cloudy-line'],
                                                    'sore'   => ['bg-warning-subtle text-warning', 'ri-moon-line'],
                                                    'isya'   => ['bg-dark-subtle text-dark',    'ri-moon-clear-line'],
                                                    'malam'  => ['bg-dark-subtle text-dark',    'ri-star-line'],
                                                    default   => ['bg-secondary-subtle text-secondary', 'ri-time-line'],
                                                };
                                            @endphp
                                            <span class="badge {{ $sessionBadge[0] }}">
                                                <i class="{{ $sessionBadge[1] }} me-1"></i>{{ ucfirst($log->session) }}
                                            </span>
                                            @if($log->log_time)
                                                <div class="text-muted small">{{ $log->log_time->format('H:i') }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if(count($dataItems) > 0)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($dataItems as $key => $value)
                                                        @if(is_bool($value))
                                                            <span class="badge bg-{{ $value ? 'success' : 'danger' }}-subtle text-{{ $value ? 'success' : 'danger' }} mb-1">
                                                                <i class="ri-{{ $value ? 'checkbox-circle' : 'close-circle' }}-line me-1"></i>
                                                                {{ Str::title(str_replace('_', ' ', $key)) }}
                                                            </span>
                                                        @elseif(is_array($value))
                                                            <span class="badge bg-light text-dark mb-1" title="{{ $key }}">
                                                                {{ Str::title(str_replace('_', ' ', $key)) }}:
                                                                {{ is_array($value) ? implode(', ', $value) : $value }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-light text-dark mb-1" title="{{ $key }}">
                                                                <i class="ri-file-text-line me-1"></i>
                                                                {{ Str::title(str_replace('_', ' ', $key)) }}:
                                                                {{ Str::limit($value, 20) }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">&mdash;</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($log->notes))
                                                <span class="text-muted small" title="{{ $log->notes }}">
                                                    {{ Str::limit($log->notes, 40) }}
                                                </span>
                                            @else
                                                <span class="text-muted">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.asrama.activities.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'activityUuid' => $log->id]) }}"
                                               class="btn btn-sm btn-outline-primary me-1" title="Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'session' => $log->session, 'date' => $log->log_date ? $log->log_date->format('Y-m-d') : null]) }}"
                                               class="btn btn-sm btn-outline-warning me-1" title="Edit / Update">
                                                <i class="ri-edit-2-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <i class="ri-file-list-3-line fs-1 d-block mb-2 text-muted"></i>
                                            Belum ada data log kegiatan pada sesi ini.
                                            <br>
                                            <a href="{{ route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                               class="btn btn-sm btn-primary mt-2">
                                                <i class="ri-add-line me-1"></i> Catat Kegiatan Baru
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">Menampilkan 1 - {{ $logs->count() }} dari {{ $logs->count() }} data</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
