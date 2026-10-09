@extends('layouts.master')
@section('title') Data Alumni @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    @include('kurikulum._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Peserta Didik @endslot
        @slot('title') Data Alumni @endslot
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

    {{-- Statistik --}}
    @php
        $pctFilled = $totalAlumni > 0 ? round($tracerFilled / $totalAlumni * 100) : 0;
        $pctVerified = $totalAlumni > 0 ? round($tracerVerified / $totalAlumni * 100) : 0;
    @endphp
    <div class="row">
        {{-- 1. Total Alumni --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-user-follow-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Alumni</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalAlumni) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-information-line me-1"></i>Tracer study persatuan pendidikan
                    </p>
                </div>
            </div>
        </div>
        {{-- 2. Tracer Terisi --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Tracer Terisi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($tracerFilled) }}</h3>
                        </div>
                    </div>
                    <div class="progress mb-1" style="height:6px;">
                        <div class="progress-bar bg-success" style="width:{{ $pctFilled }}%"></div>
                        <div class="progress-bar bg-secondary" style="width:{{ 100 - $pctFilled }}%"></div>
                    </div>
                    <p class="text-muted mb-0 stat-label">{{ $pctFilled }}% dari {{ number_format($totalAlumni) }} alumni</p>
                </div>
            </div>
        </div>
        {{-- 3. Tracer Pending --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-time-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Tracer Pending</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($tracerPending) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-information-line me-1"></i>Menunggu diisi alumni
                    </p>
                </div>
            </div>
        </div>
        {{-- 4. Terverifikasi --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-shield-check-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Terverifikasi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($tracerVerified) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-check-double-line me-1"></i>{{ $pctVerified }}% sudah diverifikasi
                    </p>
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
                            <h5 class="card-title mb-0">Daftar Alumni</h5>
                            <p class="text-muted mb-0">Tracer study alumni persatuan pendidikan.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="gap-2 d-flex flex-wrap">
                                <a href="{{ route('user.alumni.statistics', ['userId' => $userId]) }}" class="btn btn-info">
                                    <i class="ri-bar-chart-line me-1"></i> Statistik
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i> Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('user.alumni.export', array_merge(request()->query(), ['userId' => $userId, 'format' => 'xlsx'])) }}">Download Excel</a></li>
                                        <li><a class="dropdown-item" href="{{ route('user.alumni.export', array_merge(request()->query(), ['userId' => $userId, 'format' => 'pdf'])) }}">Download PDF</a></li>
                                    </ul>
                                </div>
                                <a href="{{ route('user.alumni.index', array_merge(request()->query(), ['userId' => $userId, 'sync' => 1])) }}"
                                   class="btn btn-outline-primary"
                                   onclick="return confirm('Sinkronkan data alumni dari Santri lulus?')">
                                    <i class="ri-refresh-line me-1"></i> Sync
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filter Cepat --}}
                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['tracer_status' => null, 'page' => null]) }}"
                           class="filter-badge {{ !request('tracer_status') ? 'active' : '' }}">
                            <i class="ri-list-check-2"></i> Semua
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['tracer_status' => 'pending', 'page' => null]) }}"
                           class="filter-badge {{ request('tracer_status') === 'pending' ? 'active' : '' }}">
                            <i class="ri-time-line"></i> Pending
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['tracer_status' => 'filled', 'page' => null]) }}"
                           class="filter-badge {{ request('tracer_status') === 'filled' ? 'active' : '' }}">
                            <i class="ri-checkbox-circle-line"></i> Filled
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['tracer_status' => 'verified', 'page' => null]) }}"
                           class="filter-badge {{ request('tracer_status') === 'verified' ? 'active' : '' }}">
                            <i class="ri-shield-check-line"></i> Verified
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Nama, NISN, NIK..."
                                   value="{{ request('search') }}">
                        </div>
                        @if(!$schoolContextId)
                        <div class="col-md-2">
                            <select name="school_id" class="form-control">
                                <option value="">Semua Satuan</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-2">
                            <select name="graduation_year" class="form-control">
                                <option value="">Semua Tahun</option>
                                @foreach($graduationYears as $year)
                                    <option value="{{ $year }}" {{ request('graduation_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="tracer_status" class="form-control">
                                <option value="">Semua Tracer</option>
                                <option value="pending" {{ request('tracer_status') === 'pending' ? 'selected' : '' }}>Belum Diisi</option>
                                <option value="filled" {{ request('tracer_status') === 'filled' ? 'selected' : '' }}>Sudah Diisi</option>
                                <option value="verified" {{ request('tracer_status') === 'verified' ? 'selected' : '' }}>Diverifikasi</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('user.alumni.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Lengkap</th>
                                    <th>NISN</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Tahun Lulus</th>
                                    <th>Satuan Pendidikan</th>
                                    <th>Tracer</th>
                                    <th>Studi</th>
                                    <th>Bekerja</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($alumni as $i => $a)
                                    <tr>
                                        <td>{{ $alumni->firstItem() + $i }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $a->student->name ?? '-' }}</span>
                                        </td>
                                        <td>{{ $a->student->nisn ?? '-' }}</td>
                                        <td>
                                            @if($a->student?->gender === 'L')
                                                <span class="badge bg-primary-subtle text-primary">Laki-laki</span>
                                            @elseif($a->student?->gender === 'P')
                                                <span class="badge bg-danger-subtle text-danger">Perempuan</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $a->graduation_year }}</td>
                                        <td>{{ $a->school->name ?? '-' }}</td>
                                        <td>
                                            @if($a->tracer_status === 'verified')
                                                <span class="badge bg-success-subtle text-success">Diverifikasi</span>
                                            @elseif($a->tracer_status === 'filled')
                                                <span class="badge bg-info-subtle text-info">Sudah Diisi</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Belum</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($a->continuing_study_status === 'sudah')
                                                <span class="badge bg-primary-subtle text-primary">✓ {{ $a->higher_education_institution ?? '' }}</span>
                                            @elseif($a->continuing_study_status === 'sedang')
                                                <span class="badge bg-warning-subtle text-warning">Sedang</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Belum</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($a->working_status === 'sudah')
                                                <span class="badge bg-success-subtle text-success">✓ {{ $a->occupation ?? '' }}</span>
                                            @elseif($a->working_status === 'sedang')
                                                <span class="badge bg-warning-subtle text-warning">Sedang</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Belum</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.alumni.show', ['userId' => $userId, 'alumniUuid' => $a->id]) }}"
                                               class="btn btn-sm btn-soft-primary" title="Lihat">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('user.alumni.edit', ['userId' => $userId, 'alumniUuid' => $a->id]) }}"
                                               class="btn btn-sm btn-soft-warning" title="Tracer Study">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="ri-user-follow-line fs-1 d-block mb-2"></i>
                                            Belum ada data alumni.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @include('shared._pagination', ['paginator' => $alumni])
                </div>
            </div>
        </div>
    </div>
@endsection
