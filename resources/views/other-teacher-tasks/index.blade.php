@extends('layouts.master')
@section('title') Tugas Tambahan Guru @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $total = $stats['total'] ?? 0;
        $aktif = $stats['aktif'] ?? 0;
        $nonaktif = $stats['nonaktif'] ?? 0;
        $jenis = $stats['jenis'] ?? 0;
        $guru = $stats['guru'] ?? 0;
        $pctAktif = $total > 0 ? round($aktif / $total * 100) : 0;
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('title') Tugas Tambahan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-file-list-3-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Tugas</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($total) }}</h3>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-success-subtle text-success stat-label">
                            <i class="ri-checkbox-circle-fill me-1"></i>{{ number_format($aktif) }} Aktif
                        </span>
                        <span class="badge bg-danger-subtle text-danger stat-label">
                            <i class="ri-close-circle-fill me-1"></i>{{ number_format($nonaktif) }} Nonaktif
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-briefcase-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Jenis Tugas</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($jenis) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">jenis</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-price-tag-3-line me-1"></i>Nama tugas berbeda (distinct)</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-user-star-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Guru Terlibat</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($guru) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">guru</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-team-line me-1"></i>GTK dengan tugas tambahan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-pie-chart-2-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Rasio Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $pctAktif }}%</h3>
                        </div>
                    </div>
                    <div class="progress mt-1" style="height:6px;">
                        <div class="progress-bar bg-success" style="width:{{ $pctAktif }}%"></div>
                    </div>
                    <p class="text-muted mb-0 stat-label mt-1"><i class="ri-information-line me-1"></i>Dari {{ number_format($total) }} penugasan</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="otherTeacherTaskList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Tugas Tambahan Guru</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ number_format($total) }} tugas</span>
                                <span class="text-muted small ms-2">Wali Kelas, Koordinator, Kesiswaan, dll.</span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" action="{{ route('user.other-teacher-tasks.index', ['userId' => $userId]) }}"
                                  class="d-flex flex-wrap align-items-center gap-2">
                                <select name="academic_year_id" class="form-select form-select-sm" style="width:170px" onchange="this.form.submit()">
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                                @if(request('status'))
                                    <input type="hidden" name="status" value="{{ request('status') }}">
                                @endif
                                <a href="{{ route('user.other-teacher-tasks.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm" title="Reset"><i class="ri-refresh-line"></i></a>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" class="filter-badge {{ ! request('status') ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'aktif', 'page' => null]) }}" class="filter-badge {{ request('status') === 'aktif' ? 'active' : '' }}"><i class="ri-checkbox-circle-line"></i> Aktif</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'nonaktif', 'page' => null]) }}" class="filter-badge {{ request('status') === 'nonaktif' ? 'active' : '' }}"><i class="ri-close-circle-line"></i> Nonaktif</a>
                        <span class="text-muted small ms-2">·</span>
                        <span class="text-muted small ms-2">{{ $jenis }} jenis · {{ $guru }} guru terlibat</span>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Add Form --}}
                    <form method="POST" action="{{ route('user.other-teacher-tasks.store', ['userId' => $userId]) }}" class="row g-2 mb-4 p-3 border rounded-3 bg-light">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ old('academic_year_id', $activeAcademicYearId) }}">
                        <div class="col-md-3">
                            <select name="teacher_id" class="form-control" required>
                                <option value="">-- Pilih Guru --</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="task_name" class="form-control" placeholder="Nama Tugas (cth: Wali Kelas 7A)" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="weekly_hours" class="form-control" placeholder="Jam/Minggu" min="0" max="40" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="notes" class="form-control" placeholder="Keterangan (opsional)">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-add-line me-1"></i> Tambah
                            </button>
                        </div>
                    </form>

                    {{-- Task List --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Guru</th>
                                    <th>Tugas</th>
                                    <th class="text-center">Jam/Minggu</th>
                                    <th class="text-center">Status</th>
                                    <th>Catatan</th>
                                    <th class="text-center" style="width:80px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $task)
                                    <tr>
                                        <td class="fw-medium">{{ $task->teacher?->name ?? '-' }}</td>
                                        <td>{{ $task->task_name }}</td>
                                        <td class="text-center">{{ $task->weekly_hours }} JP</td>
                                        <td class="text-center">
                                            @if($task->is_active)
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-muted stat-label">{{ $task->notes ?? '-' }}</td>
                                        <td class="text-center">
                                            <form method="POST" action="{{ route('user.other-teacher-tasks.destroy', ['userId' => $userId, 'id' => $task->id]) }}"
                                                  onsubmit="return confirm('Hapus tugas &quot;{{ $task->task_name }}&quot;?')" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-soft-danger btn-sm">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                                                Belum ada tugas tambahan pada filter ini.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('shared._pagination', ['paginator' => $tasks])
                </div>
            </div>
        </div>
    </div>
@endsection
