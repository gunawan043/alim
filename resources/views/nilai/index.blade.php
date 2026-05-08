@extends('layouts.master')
@section('title') Data Nilai @endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .stats-card { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    .stats-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 22px;
    }
    .semester-pill {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
        border: 1px solid transparent;
    }
    .semester-pill.gasal { background: #dbeafe; color: #1d4ed8; border-color: #93c5fd; }
    .semester-pill.genap { background: #fef9c3; color: #854d0e; border-color: #fcd34d; }
    .semester-pill.inactive { background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; cursor: default; }
</style>
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? Auth::id();
        $totalKelas = count($kelasList) ?? 0;
        $totalMapel  = 0;
        foreach ($kelasList as $k) { $totalMapel += $k['total_mapel'] ?? 0; }
        $totalSiswa  = 0;
        foreach ($kelasList as $k) { $totalSiswa += $k['total_siswa'] ?? 0; }
        $selectedAy  = $academicYears->firstWhere('id', $selectedAcademicYearId);
        $selectedGl  = request('grade_level_id')
            ? (\App\Models\GradeLevel::find(request('grade_level_id'))?->name ?? null)
            : null;
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('title') Data Nilai @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    @if($selectedAcademicYearId && $selectedAy?->is_active)
    <div class="row g-3 mb-3">
        <div class="col-xl-4 col-md-4">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-primary-subtle">
                            <i class="bx bx-group text-primary"></i>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Total Kelas</p>
                            <h2 class="fw-bold ff-secondary mb-0">{{ number_format($totalKelas) }}</h2>
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block">
                        <i class="ri-user-line me-1"></i>{{ number_format($totalSiswa) }} siswa
                    </small>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-info-subtle">
                            <i class="ri-book-open-line text-info"></i>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Mata Pelajaran</p>
                            <h2 class="fw-bold ff-secondary mb-0">{{ number_format($totalMapel) }}</h2>
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block">dari {{ $totalKelas }} kelas</small>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-warning-subtle">
                            <i class="ri-calendar-check-line text-warning"></i>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Tahun Ajaran</p>
                            <h2 class="fw-bold ff-secondary mb-0" style="font-size:18px;">{{ $selectedAy->name }}</h2>
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block">
                        <i class="ri-stack-line me-1"></i>
                        Semester <strong>{{ $selectedSemester === 'ganjil' ? 'Ganjil' : 'Genap' }}</strong>
                        {{ $selectedGl ? "— $selectedGl" : '' }}
                    </small>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="nilaiList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">
                                <i class="ri-file-edit-line text-primary me-1"></i>
                                Data Nilai
                            </h5>
                        </div>
                        @if($isPrivileged && $selectedAcademicYearId)
                        <div class="col-sm-auto">
                            <span class="badge bg-info-subtle text-info" style="font-size:11px;padding:4px 10px;">
                                <i class="ri-user-settings-line align-bottom me-1"></i>Mode TU / Waka
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Tahun Ajaran</label>
                                <select name="academic_year_id" class="form-select"
                                    onchange="document.getElementById('filterForm').submit()">
                                    @if($academicYears->isEmpty())
                                        <option value="">— Tidak ada TA aktif —</option>
                                    @else
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}"
                                                {{ $selectedAcademicYearId == $ay->id ? 'selected' : '' }}>
                                                {{ $ay->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Semester</label>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('user.schools.nilai.index', ['userId' => $userId, 'academic_year_id' => $selectedAcademicYearId, 'semester' => 'ganjil', 'grade_level_id' => request('grade_level_id')]) }}"
                                       class="semester-pill {{ $selectedSemester === 'ganjil' ? 'gasal' : 'inactive' }}">
                                        <i class="ri-file-list-line"></i> Ganjil
                                    </a>
                                    <a href="{{ route('user.schools.nilai.index', ['userId' => $userId, 'academic_year_id' => $selectedAcademicYearId, 'semester' => 'genap', 'grade_level_id' => request('grade_level_id')]) }}"
                                       class="semester-pill {{ $selectedSemester === 'genap' ? 'genap' : 'inactive' }}">
                                        <i class="ri-file-list-2-line"></i> Genap
                                    </a>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Tingkat</label>
                                <select name="grade_level_id" class="form-select"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">Semua</option>
                                    @foreach($gradeLevelIds as $gl)
                                        <option value="{{ $gl->id }}"
                                            {{ request('grade_level_id') == $gl->id ? 'selected' : '' }}>
                                            {{ $gl->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @if($selectedAcademicYearId)
                            <div class="col-md-3">
                                <a href="{{ route('user.schools.nilai.index', ['userId' => $userId, 'academic_year_id' => $selectedAcademicYearId, 'semester' => $selectedSemester]) }}"
                                   class="btn btn-primary w-100">
                                    <i class="ri-reset-right-line me-1"></i> Reset Filter
                                </a>
                            </div>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- Table --}}
                @if($selectedAcademicYearId && $selectedAy?->is_active)
                <div class="border-top">
                    @if($totalKelas > 0)
                    <div class="px-4 py-2 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span class="text-muted" style="font-size:13px;">
                            <i class="ri-list-check me-1"></i>
                            <strong>{{ $totalKelas }}</strong> kelas
                            &middot; <strong>{{ $totalMapel }}</strong> mapel
                            &middot; <strong>{{ $totalSiswa }}</strong> siswa
                        </span>
                        <div class="d-flex gap-2" style="font-size:12px;">
                            <span class="badge bg-secondary-subtle text-secondary">{{ $selectedAy->name }}</span>
                            <span class="badge {{ $selectedSemester === 'ganjil' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning' }}">
                                {{ $selectedSemester === 'ganjil' ? 'Semester Ganjil' : 'Semester Genap' }}
                            </span>
                        </div>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;text-align:center;">#</th>
                                    <th>Rombel</th>
                                    <th>Tingkat</th>
                                    <th>Wali Kelas</th>
                                    <th class="text-center" style="min-width:200px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kelasList as $kelas)
                                    @php $sg = $kelas['study_group']; @endphp
                                    <tr>
                                        <td class="text-center text-muted" style="font-size:13px;">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-medium">{{ $sg->name }}</div>
                                            <small class="text-muted">{{ $kelas['total_siswa'] }} siswa &middot; {{ $kelas['total_mapel'] }} mapel</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark-subtle text-dark">{{ $sg->gradeLevel?->name ?? '-' }}</span>
                                        </td>
                                        <td>
                                            @if($sg->homeroomTeacher)
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-xs">
                                                        <span class="avatar-title rounded-circle bg-primary text-white" style="font-size:11px;">
                                                            {{ strtoupper(substr($sg->homeroomTeacher->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <span class="text-muted" style="font-size:13px;">{{ $sg->homeroomTeacher->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted" style="font-size:13px;">— Belum ada wali kelas</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($isPrivileged)
                                                <a href="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $sg->id, 'academic_year_id' => $selectedAcademicYearId, 'semester' => $selectedSemester]) }}"
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="ri-edit-2-line me-1"></i> STS
                                                </a>
                                            @else
                                                <a href="{{ route('user.schools.nilai.sts', ['userId' => $userId, 'adminBookId' => $kelas['first_book']->id]) }}"
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="ri-edit-2-line me-1"></i> STS
                                                </a>
                                            @endif
                                            <a href="{{ route('user.schools.nilai.sas', ['userId' => $userId, 'adminBookId' => $kelas['first_book']->id]) }}"
                                               class="btn btn-sm btn-outline-warning">
                                                <i class="ri-edit-2-line me-1"></i> SAS
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="avatar-lg mx-auto mb-3">
                                                <div class="avatar-title bg-light rounded-circle">
                                                    <i class="ri-book-open-line fs-1 text-muted"></i>
                                                </div>
                                            </div>
                                            <h6 class="text-muted">Belum ada kelas ditemukan</h6>
                                            <p class="text-muted mb-0" style="font-size:13px;">
                                                @if($academicYears->isEmpty())
                                                    Tidak ada <strong>Tahun Ajaran aktif</strong>. Hubungi admin untuk mengaktifkan tahun ajaran.
                                                @else
                                                    Pastikan <strong>Buku Admin Guru</strong> sudah terisi untuk semester ini.
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection