@extends('layouts.master')
@section('title', 'Buku Kerja Tahfidz')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Buku Kerja @endslot @slot('title') Buku Kerja Tahfidz @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card border-primary-subtle mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-md">
                    <h5 class="card-title mb-2"><i class="ri-book-2-line me-1 text-primary"></i>Buku Kerja Tahfidz</h5>
                    <p class="text-muted mb-0 small">Pilih halaqah, lalu buka absensi, setoran, atau mutaba’ah. Semua administrasi harian ada dalam satu tempat.</p>
                </div>
                <div class="col-md-auto d-flex flex-wrap gap-2">
                    <a href="{{ route('user.tahfidz.tasmi.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm"><i class="ri-award-line me-1"></i>Tasmi’</a>
                    <a href="{{ route('user.tahfidz.progress.index', ['userId' => $userId]) }}" class="btn btn-soft-info btn-sm"><i class="ri-line-chart-line me-1"></i>Progres</a>
                    <a href="{{ route('user.tahfidz.monitoring.index', ['userId' => $userId]) }}" class="btn btn-soft-warning btn-sm"><i class="ri-radar-line me-1"></i>Monitoring</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row tahfidz-stat g-2 mb-2">
        @foreach([
            ['Halaqah Saya', $stats['groups'], 'ri-team-line', 'primary', 'Halaqah aktif tahun ini'],
            ['Santri', $stats['students'], 'ri-user-heart-line', 'info', 'Santri di halaqah Anda'],
            ['Setoran Hari Ini', $stats['setoran_today'], 'ri-book-read-line', 'success', 'Setoran tercatat hari ini'],
            ['Absensi Hari Ini', $stats['attendance_today'], 'ri-calendar-check-line', 'warning', 'Baris presensi hari ini'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ number_format($value) }}</h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 mt-2 stat-label"><i class="ri-information-line me-1"></i>{{ $note }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-team-line text-primary me-1"></i>Daftar Halaqah</h5>
                <p class="text-muted mb-0 small">{{ $academicYear?->name ?? 'Tahun ajaran aktif' }} · {{ $groups->count() }} halaqah</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.attendance.index', ['userId' => $userId]) }}" class="btn btn-primary btn-sm"><i class="ri-calendar-check-line me-1"></i>Absensi</a>
                <a href="{{ route('user.tahfidz.setoran.index', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm"><i class="ri-file-add-line me-1"></i>Setoran</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Halaqah</th>
                        <th>Kelas / Sekolah</th>
                        <th>Guru</th>
                        <th>Jadwal Hari Ini</th>
                        <th class="text-center">Santri</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                        @php $schedule = $group->schedules->first(); @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $group->name }}</div>
                                <div class="text-muted small">{{ strtoupper($group->gender) }}{{ $group->room ? ' · '.$group->room : '' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">{{ $group->studyGroup?->name ?? 'Tanpa kelas' }}</span>
                                <div class="text-muted small mt-1">{{ $group->school?->name ?? '—' }}</div>
                            </td>
                            <td>
                                {{ $group->teacher?->name ?? '—' }}
                                @if($group->coordinator)<div class="text-muted small">Koor: {{ $group->coordinator->name }}</div>@endif
                            </td>
                            <td>
                                @if($schedule)
                                    <span class="badge bg-primary-subtle text-primary">{{ substr((string) $schedule->time_start, 0, 5) }}–{{ substr((string) $schedule->time_end, 0, 5) }}</span>
                                    <div class="text-muted small mt-1">{{ $schedule->room ?? $group->room ?? 'Ruang belum diisi' }}</div>
                                @else
                                    <span class="text-muted small">Tidak ada jadwal hari ini</span>
                                @endif
                            </td>
                            <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $group->active_members_count }}</span></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('user.tahfidz.attendance.index', ['userId' => $userId, 'group_id' => $group->id, 'tab' => 'input']) }}" class="btn btn-primary" title="Absensi"><i class="ri-calendar-check-line"></i><span class="d-none d-xl-inline ms-1">Absensi</span></a>
                                    <a href="{{ route('user.tahfidz.setoran.create', ['userId' => $userId, 'group_id' => $group->id]) }}" class="btn btn-outline-primary" title="Input Setoran"><i class="ri-add-line"></i></a>
                                    <a href="{{ route('user.tahfidz.mutabaah.index', ['userId' => $userId, 'group_id' => $group->id]) }}" class="btn btn-outline-primary" title="Mutaba’ah"><i class="ri-calendar-todo-line"></i></a>
                                    <a href="{{ route('user.tahfidz.halaqah.show', ['userId' => $userId, 'id' => $group->id]) }}" class="btn btn-outline-secondary" title="Detail Halaqah"><i class="ri-eye-line"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ri-team-line fs-3 d-block mb-2"></i>Belum ada halaqah aktif. Hubungi koordinator Tahfidz sekolah.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-file-list-3-line text-primary me-1"></i>Perangkat &amp; Evaluasi</h5></div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach([
                            ['Target Hafalan', 'ri-focus-3-line', 'user.tahfidz.targets.index'],
                            ['Progres & Rekap', 'ri-line-chart-line', 'user.tahfidz.progress.index'],
                            ['Tindak Lanjut', 'ri-task-line', 'user.tahfidz.interventions.index'],
                            ['Halaqah & Jadwal', 'ri-calendar-schedule-line', 'user.tahfidz.halaqah.index'],
                        ] as [$label, $icon, $routeName])
                            <div class="col-6"><a href="{{ route($routeName, ['userId' => $userId]) }}" class="btn btn-soft-primary w-100 text-start py-2"><i class="{{ $icon }} me-1"></i>{{ $label }}</a></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-book-open-line text-primary me-1"></i>Muqorror &amp; RPP</h5></div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach([
                            ['Muqorror Tahfizh', 'ri-book-open-line', route('user.tahfidz.muqorror.index', ['userId' => $userId])],
                            ['RPP Tahfizh', 'ri-file-list-3-line', route('user.tahfidz.rpp.index', ['userId' => $userId])],
                            ['RPP Iqro’', 'ri-book-read-line', route('user.tahfidz.rpp.index', ['userId' => $userId, 'type' => 'iqro'])],
                            ['Waraqat Mutaba’ah', 'ri-calendar-todo-line', route('user.tahfidz.mutabaah.index', ['userId' => $userId])],
                        ] as [$label, $icon, $url])
                            <div class="col-6"><a href="{{ $url }}" class="btn btn-soft-primary w-100 text-start py-2"><i class="{{ $icon }} me-1"></i>{{ $label }}</a></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
