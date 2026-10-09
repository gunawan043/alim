@extends('layouts.master')
@section('title') Absensi Harian Peserta Didik @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $totalSantri = collect($rombelStats)->sum(fn ($s) => $s['total'] ?? 0);
        $totalHadir = collect($rombelStats)->sum(fn ($s) => $s['hadir'] ?? 0);
        $totalTerlambat = collect($rombelStats)->sum(fn ($s) => $s['terlambat'] ?? 0);
        $totalIzin = collect($rombelStats)->sum(fn ($s) => $s['izin'] ?? 0);
        $totalSakit = collect($rombelStats)->sum(fn ($s) => $s['sakit'] ?? 0);
        $totalAlpa = collect($rombelStats)->sum(fn ($s) => $s['alpa'] ?? 0);
        $totalRecorded = collect($rombelStats)->sum(fn ($s) => $s['recorded'] ?? 0);
        $izinSakit = $totalIzin + $totalSakit;
        $pctHadir = $totalSantri > 0 ? round($totalHadir / $totalSantri * 100) : 0;
        $pctRecorded = $totalSantri > 0 ? round($totalRecorded / $totalSantri * 100) : 0;

        $recordedFilter = request('recorded');
        $displayGroups = $studyGroups->filter(function ($sg) use ($rombelStats, $recordedFilter) {
            if (! $recordedFilter) {
                return true;
            }
            $s = $rombelStats[$sg->id] ?? null;
            $total = $s['total'] ?? 0;
            $recorded = $s['recorded'] ?? 0;

            return match ($recordedFilter) {
                'lengkap' => $total > 0 && $recorded >= $total,
                'belum' => $total > 0 && $recorded < $total,
                'tanpa' => $total === 0,
                default => true,
            };
        });
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('title') Absensi Peserta Didik @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-group-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Santri</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalSantri) }}</h3>
                        </div>
                    </div>
                    <div class="progress mt-1" style="height:6px;">
                        <div class="progress-bar bg-primary" style="width:{{ $pctRecorded }}%"></div>
                    </div>
                    <p class="text-muted mb-0 stat-label mt-1"><i class="ri-edit-2-line me-1"></i>Tercatat {{ number_format($totalRecorded) }} ({{ $pctRecorded }}%)</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Hadir</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalHadir) }}</h3>
                        </div>
                    </div>
                    <div class="d-flex gap-1 flex-wrap">
                        <span class="badge bg-success-subtle text-success stat-label">{{ $pctHadir }}% santri</span>
                        @if($totalTerlambat > 0)
                            <span class="badge bg-warning-subtle text-warning stat-label"><i class="ri-timer-line me-1"></i>{{ number_format($totalTerlambat) }} terlambat</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-hospital-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Izin / Sakit</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($izinSakit) }}</h3>
                        </div>
                    </div>
                    <div class="d-flex gap-1 flex-wrap">
                        <span class="badge bg-info-subtle text-info stat-label">Izin {{ number_format($totalIzin) }}</span>
                        <span class="badge bg-secondary-subtle text-secondary stat-label">Sakit {{ number_format($totalSakit) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-user-unfollow-line text-danger"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Alpa</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalAlpa) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Tidak hadir tanpa keterangan</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row align-items-center g-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Absensi Harian Peserta Didik</h5>
                            <p class="text-muted mb-0" style="font-size:0.8rem">
                                {{ $activeYear ? $activeYear->name . ' — Semester ' . ucfirst($activeYear->semester) : 'Tahun ajaran belum aktif' }}
                                &nbsp;|&nbsp; {{ $selectedDate->locale('id')->translatedFormat('j F Y') }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.absensi.harian.recap.semester', ['userId' => $userId]) }}"
                                class="btn btn-outline-secondary btn-sm">
                                <i class="ri-file-chart-2-line me-1"></i> Rekap Semester
                            </a>
                            <a href="{{ route('user.absensi.harian.create', ['userId' => $userId]) }}"
                                class="btn btn-primary btn-sm">
                                <i class="ri-edit-2-line me-1"></i> Input Absensi
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['date' => now()->toDateString(), 'page' => null]) }}"
                           class="filter-badge {{ $selectedDate->toDateString() === now()->toDateString() ? 'active' : '' }}">
                            <i class="ri-calendar-check-line"></i> Hari Ini
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['date' => now()->subDay()->toDateString(), 'page' => null]) }}"
                           class="filter-badge {{ $selectedDate->toDateString() === now()->subDay()->toDateString() ? 'active' : '' }}">
                            <i class="ri-history-line"></i> Kemarin
                        </a>
                        <span class="text-muted small ms-2 me-2">·</span>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'ganjil', 'page' => null]) }}"
                           class="filter-badge {{ $selectedSemester === 'ganjil' ? 'active' : '' }}">Ganjil</a>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'genap', 'page' => null]) }}"
                           class="filter-badge {{ $selectedSemester === 'genap' ? 'active' : '' }}">Genap</a>
                        <span class="text-muted small ms-2 me-2">·</span>
                        <a href="{{ request()->fullUrlWithQuery(['recorded' => null, 'page' => null]) }}"
                           class="filter-badge {{ ! $recordedFilter ? 'active' : '' }}">Semua Rombel</a>
                        <a href="{{ request()->fullUrlWithQuery(['recorded' => 'lengkap', 'page' => null]) }}"
                           class="filter-badge {{ $recordedFilter === 'lengkap' ? 'active' : '' }}">Sudah Tercatat</a>
                        <a href="{{ request()->fullUrlWithQuery(['recorded' => 'belum', 'page' => null]) }}"
                           class="filter-badge {{ $recordedFilter === 'belum' ? 'active' : '' }}">Belum Tercatat</a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <input type="hidden" name="recorded" value="{{ request('recorded') }}">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" class="form-control"
                                value="{{ $selectedDate->toDateString() }}" max="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-control">
                                <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> Tampilkan
                            </button>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}"
                                class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:40px">#</th>
                                    <th>Rombel</th>
                                    <th class="text-center">Wali Kelas</th>
                                    <th class="text-center">Siswa</th>
                                    <th class="text-center text-success"><i class="ri-checkbox-circle-line me-1"></i>Hadir</th>
                                    <th class="text-center text-warning"><i class="ri-timer-line me-1"></i>Terlambat</th>
                                    <th class="text-center text-info"><i class="ri-information-line me-1"></i>Izin</th>
                                    <th class="text-center text-secondary"><i class="ri-hospital-line me-1"></i>Sakit</th>
                                    <th class="text-center text-danger"><i class="ri-close-circle-line me-1"></i>Alpa</th>
                                    <th class="text-center">Tercatat</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($displayGroups as $sg)
                                    @php
                                        $stats = $rombelStats[$sg->id] ?? null;
                                        $total = $stats['total'] ?? 0;
                                        $recorded = $stats['recorded'] ?? 0;
                                        $pct = $total > 0 ? round($recorded / $total * 100) : 0;
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="{{ route('user.absensi.harian.recap.detail', [
                                                'userId' => $userId,
                                                'study_group_id' => $sg->id,
                                                'month' => $selectedDate->month,
                                                'year' => $selectedDate->year,
                                                'semester' => $selectedSemester,
                                            ]) }}" class="text-decoration-none fw-semibold">
                                                {{ $sg->full_name }}
                                            </a>
                                            @if($sg->homeroomTeacher)
                                                <span class="text-muted ms-1" style="font-size:0.75rem">({{ $sg->homeroomTeacher->name }})</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-muted" style="font-size:0.82rem">
                                            {{ $sg->homeroomTeacher?->name ?? '—' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-dark rounded-circle" style="font-size:0.72rem">{{ $total ?: '—' }}</span>
                                        </td>
                                        <td class="text-center bg-success-subtle fw-bold">{{ $stats['hadir'] ?? 0 }}</td>
                                        <td class="text-center bg-warning-subtle">{{ $stats['terlambat'] ?? 0 }}</td>
                                        <td class="text-center bg-info-subtle">{{ $stats['izin'] ?? 0 }}</td>
                                        <td class="text-center bg-secondary-subtle">{{ $stats['sakit'] ?? 0 }}</td>
                                        <td class="text-center bg-danger-subtle">{{ $stats['alpa'] ?? 0 }}</td>
                                        <td class="text-center">
                                            @if($total == 0)
                                                <span class="badge bg-light text-muted">Tanpa siswa</span>
                                            @elseif($pct == 100)
                                                <span class="badge bg-success">Lengkap</span>
                                            @elseif($pct > 0)
                                                <span class="badge bg-warning text-dark">{{ $recorded }}/{{ $total }}</span>
                                            @else
                                                <span class="badge bg-secondary">Belum</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.absensi.harian.create', [
                                                'userId' => $userId,
                                                'study_group_id' => $sg->id,
                                                'date' => $selectedDate->toDateString(),
                                                'semester' => $selectedSemester,
                                            ]) }}"
                                                class="btn btn-sm {{ $pct == 100 ? 'btn-outline-success' : 'btn-primary' }}"
                                                title="{{ $pct == 100 ? 'Lihat / Edit' : 'Input Absensi' }}">
                                                <i class="ri-{{ $pct == 100 ? 'eye' : 'edit' }}-2-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="ri-inbox-archive-line fs-1 d-block mb-2"></i>
                                                @if($recordedFilter)
                                                    Tidak ada rombel pada filter ini.
                                                @else
                                                    Tidak ada rombel ditemukan.
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
