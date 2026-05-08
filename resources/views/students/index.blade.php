@extends('layouts.master')
@section('title') Data Santri @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
    .card-animate { transition: all 0.3s ease; }
    .card-animate:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Data Santri @endslot
        @slot('title')
            @if($isFilteredByClass && $studyGroup){{ $studyGroup->full_name }}@else Daftar Santri @endif
        @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats: berbeda tergantung mode akses --}}
    @php
        $pctActive = $totalAll > 0 ? round($totalActive / $totalAll * 100) : 0;
        $quotaRemaining = $capacity ? max(0, $capacity - $inClass) : 0;
        $quotaFilledPct = $capacity && $capacity > 0 ? round($inClass / $capacity * 100) : 0;
    @endphp

    {{-- MODE: PER ROMBEL --}}
    @if($isFilteredByClass && $studyGroup)
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="bx bx-group text-primary"></i></span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">{{ $studyGroup->full_name }}</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ number_format($inClass) }} <small class="fw-normal text-muted">/ {{ $capacity }}</small></h3>
                            </div>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar bg-primary" style="width:{{ $quotaFilledPct }}%"></div>
                            <div class="progress-bar bg-secondary" style="width:{{ 100 - $quotaFilledPct }}%"></div>
                        </div>
                        <small class="text-muted">{{ $quotaRemaining }} slot tersisa</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-info-subtle rounded fs-2"><i class="bx bx-check-circle text-info"></i></span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Aktif</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ number_format($inClass) }}</h3>
                            </div>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar bg-info" style="width:{{ $pctActive }}%"></div>
                            <div class="progress-bar bg-secondary" style="width:{{ 100 - $pctActive }}%"></div>
                        </div>
                        <small class="text-muted">{{ $pctActive }}% aktif</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="bx bx-user-circle text-warning"></i></span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Wali Kelas</p>
                                <h3 class="fw-bold ff-secondary mb-0" style="font-size:1rem;">{{ $studyGroup->homeroomTeacher?->name ?? '-' }}</h3>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="ri-bookmark-line me-1"></i>{{ $studyGroup->gradeLevel?->name ?? '-' }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="bx bx-buildings text-warning"></i></span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Tingkat</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $studyGroup->gradeLevel?->name ?? '-' }}</h3>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="ri-user-location-line me-1"></i>{{ $studyGroup->school?->name ?? '-' }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

    {{-- MODE: MASSAL --}}
    @else
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="bx bx-group text-primary"></i></span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Santri</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalAll) }}</h3>
                            </div>
                        </div>
                        <small class="text-muted"><i class="ri-checkbox-circle-fill text-success me-1"></i>{{ number_format($totalActive) }} aktif</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-success-subtle rounded fs-2"><i class="bx bx-check-circle text-success"></i></span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Sudah di Rombel</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ number_format($byRombel['in_rombel'] ?? 0) }}</h3>
                            </div>
                        </div>
                        @if(($byRombel['in_rombel'] ?? 0) + ($byRombel['unassigned'] ?? 0) > 0)
                            @php $pctInRombel = round(($byRombel['in_rombel'] ?? 0) / (($byRombel['in_rombel'] ?? 0) + ($byRombel['unassigned'] ?? 0)) * 100); @endphp
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-success" style="width:{{ $pctInRombel }}%"></div>
                                <div class="progress-bar bg-secondary" style="width:{{ 100 - $pctInRombel }}%"></div>
                            </div>
                            <small class="text-muted">{{ $pctInRombel }}% sudah ter-assign</small>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="bx bx-error-circle text-warning"></i></span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Belum di Rombel</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ number_format($byRombel['unassigned'] ?? 0) }}</h3>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="ri-arrow-right-line me-1"></i>
                            <a href="#" class="text-warning small">Tarik ke rombel</a>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-info-subtle rounded fs-2"><i class="bx bx-category text-info"></i></span>
                            </div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Distribusi per Tingkat</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ number_format($distribusiPerTingkat->sum('total')) }}</h3>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @forelse($distribusiPerTingkat as $row)
                                <span class="badge bg-info-subtle text-info" style="font-size:10px;">{{ $row->grade_name }}: {{ $row->total }}</span>
                            @empty
                                <small class="text-muted">Belum ada data rombel</small>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">
                                @if($isFilteredByClass && $studyGroup)
                                    {{ $studyGroup->full_name }}
                                @else
                                    Daftar Santri
                                @endif
                            </h5>
                            <p class="text-muted mb-0">Total {{ number_format($totalAll) }} data.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-2">
                                <a href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}" class="btn btn-outline-primary">
                                    <i class="ri-logout-box-line align-bottom me-1"></i>Mutasi Keluar
                                </a>
                                <a href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}" class="btn btn-outline-success">
                                    <i class="ri-login-box-line align-bottom me-1"></i>Mutasi Masuk
                                </a>
                                @php
                                    $gradeLevel = $studyGroup?->gradeLevel;
                                    $level = $gradeLevel?->level ?? 0;
                                    $schoolType = $studyGroup?->school?->school_type;
                                    // Tentukan grade akhir per jenjang
                                    $finalLevels = match($schoolType) {
                                        'smp' => [9],
                                        'sd'  => [6],
                                        default => [6, 9, 12],
                                    };
                                    $isFinalGrade = in_array($level, $finalLevels);
                                @endphp
                                @if($studyGroup)
                                    @if($isFinalGrade)
                                        <a href="{{ route('user.bulk-graduation.index', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}" class="btn btn-outline-warning">
                                            <i class="ri-graduation-cap-line align-bottom me-1"></i>Kelulusan Massal
                                        </a>
                                    @else
                                        <a href="{{ route('user.bulk-promotion.index', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}" class="btn btn-outline-warning">
                                            <i class="ri-arrow-up-line align-bottom me-1"></i>Kenaikan Kelas
                                        </a>
                                    @endif
                                @endif
                                <a href="{{ route('user.students.create', ['userId' => $userId]) }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Tambah Santri
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select name="school_id" class="form-control">
                                <option value="">Semua Sekolah</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Nama, NISN, NIS, NIK..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="gender" class="form-control">
                                <option value="">Semua Gender</option>
                                <option value="L" {{ request('gender') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ request('gender') === 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                                <option value="graduate" {{ request('status') === 'graduate' ? 'selected' : '' }}>Lulus</option>
                                <option value="dropped" {{ request('status') === 'dropped' ? 'selected' : '' }}>Dropout</option>
                                <option value="transfer_out" {{ request('status') === 'transfer_out' ? 'selected' : '' }}>Pindah (Keluar)</option>
                                <option value="transfer_in" {{ request('status') === 'transfer_in' ? 'selected' : '' }}>Pindah (Masuk)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>NISN</th>
                                    <th>JK</th>
                                    <th>Tempat, Tgl Lahir</th>
                                    <th>Sekolah</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $s)
                                    <tr>
                                        <td>{{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}</td>
                                        <td>
                                            <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $s->id]) }}" class="fw-medium link-primary">
                                                {{ $s->name }}
                                            </a>
                                        </td>
                                        <td><code>{{ $s->nisn }}</code></td>
                                        <td>
                                            @if($s->gender === 'L')
                                                <span class="badge bg-primary-subtle text-primary">L</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">P</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>
                                                {{ $s->birth_place ?: '-' }},
                                                {{ $s->birth_date?->format('d M Y') ?? '-' }}
                                            </small>
                                        </td>
                                        <td>
                                            <a href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $s->school_id]) }}" class="text-muted small">
                                                {{ $s->school?->name ?? '-' }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $s->status === 'active' ? 'success' : ($s->status === 'graduate' ? 'info' : 'secondary') }}-subtle text-{{ $s->status === 'active' ? 'success' : ($s->status === 'graduate' ? 'info' : 'secondary') }}">
                                                {{ $s->status_text }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                                                    <i class="ri-more-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $s->id]) }}">
                                                            <i class="ri-eye-line me-2"></i>Lihat
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.students.edit', ['userId' => $userId, 'santriUuid' => $s->id]) }}">
                                                            <i class="ri-pencil-line me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.mutations-out.create', ['userId' => $userId, 'student_id' => $s->id]) }}">
                                                            <i class="ri-logout-box-line me-2 text-danger"></i>Mutasi Keluar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.mutations-in.create', ['userId' => $userId, 'student_id' => $s->id]) }}">
                                                            <i class="ri-login-box-line me-2 text-success"></i>Mutasi Masuk
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="avatar-lg mx-auto mb-3">
                                                <div class="avatar-title bg-light rounded-circle">
                                                    <i class="ri-user-follow-line fs-1 text-muted"></i>
                                                </div>
                                            </div>
                                            <h5 class="text-muted">Belum ada data santri</h5>
                                            <a href="{{ route('user.students.create', ['userId' => $userId]) }}" class="btn btn-success">
                                                <i class="ri-add-line me-1"></i>Tambah Santri
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('shared._pagination', ['paginator' => $students])
                </div>
            </div>
        </div>
    </div>
@endsection
