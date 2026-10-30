@extends('layouts.master')
@section('title')
    Data Santri
@endsection
@php
    $canViewAllSchools = auth()->check() && auth()->user()->hasPermissionTo('student-all-access');
@endphp
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Akademik
        @endslot
        @slot('li_2')
            Data Santri
        @endslot
        @slot('title')
            @if ($isFilteredByClass && $studyGroup)
                {{ $studyGroup->full_name }}
            @else
                Daftar Santri
            @endif
        @endslot
    @endcomponent

    @if($isFilteredByClass && $studyGroup && $studyGroup->academic_year_id !== \App\Models\AcademicYear::where('is_active', true)->value('id'))
        <div class="alert alert-info py-2 px-3 small" role="alert">
            <i class="ri-archive-line me-1"></i>
            Menampilkan <strong>arsip rombel</strong> tahun ajaran
            <strong>{{ $studyGroup->academicYear?->name ?? '-' }}</strong> — termasuk santri yang sudah pindah/naik kelas.
            Data nilai &amp; presensi tahun ajaran tersebut tetap tersimpan.
        </div>
    @elseif(($isArchiveAy ?? false))
        <div class="alert alert-warning py-2 px-3 small" role="alert">
            <i class="ri-archive-line me-1"></i>
            Menampilkan data santri periode <strong>arsip</strong> tahun ajaran
            <strong>{{ $academicYears->firstWhere('id', $rombelDisplayAyId)?->name ?? '-' }}
                ({{ $academicYears->firstWhere('id', $rombelDisplayAyId)?->semester_text ?? '-' }})</strong>.
            Kolom <strong>Rombel</strong> mengikuti periode tersebut, bukan rombel saat ini.
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-line me-1"></i> <strong>Error:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Warning: rombel melebihi kapasitas --}}
    @if (!$isFilteredByClass && !$overCapacityRombels->isEmpty())
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="ri-error-warning-fill fs-4"></i>
            <div>
                <strong>{{ $overCapacityRombels->count() }} rombel melebihi kapasitas:</strong>
                @foreach ($overCapacityRombels as $sg)
                    <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $sg->id]) }}"
                        class="fw-bold text-decoration-none text-danger" target="_blank">
                        {{ $sg->name }}
                    </a>
                    ({{ $sg->student_count }}/{{ $sg->capacity }}
                    — +{{ $sg->student_count - $sg->capacity }} lebih)
                    @if (!$loop->last)
                        ,
                    @endif
                @endforeach
                <br><span class="text-muted small">Segera pindahkan siswa atau naikkan kapasitas rombel.</span>
            </div>
        </div>
    @endif
    @if ($isFilteredByClass && $isCurrentRombelOverCapacity)
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="ri-error-warning-fill fs-4"></i>
            <div>
                <strong>{{ $studyGroup->full_name }} melebihi kapasitas!</strong>
                ({{ $inClass }}/{{ $capacity }} — +{{ $inClass - $capacity }} lebih)
                <br><span class="text-muted small">
                    Gunakan menu <a
                        href="{{ route('user.student-move.index', ['userId' => $userId, 'study_group_id' => $studyGroup->id]) }}"
                        class="alert-link">Pindahkan Santri</a>
                    untuk memindahkan Santri ke rombel lain, atau tambah kapasitas rombel.
                </span>
            </div>
        </div>
    @endif

    {{-- Import result dari halaman import (duplikat / error) --}}
    @if (session('import_result'))
        @php
            $result = session('import_result');
            $totalDup = count($result['duplicates'] ?? []);
            $totalErr = count($result['errors'] ?? []);
        @endphp
        @if ($result['created'] > 0)
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ri-check-line me-1"></i>
                <strong>Berhasil mengimport {{ $result['created'] }} data santri.</strong>
                @if ($totalDup)
                    — {{ $totalDup }} duplikat dilewati.
                @endif
                @if ($totalErr)
                    — {{ $totalErr }} error.
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if ($totalDup)
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="ri-alert-line me-1"></i>
                <strong>{{ $totalDup }} data duplikat</strong> — NISN/NIK sudah ada, dilewati.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if ($totalErr)
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ri-error-line me-1"></i>
                <strong>{{ $totalErr }} error:</strong>
                <ul class="mb-0">
                    @foreach ($result['errors'] as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endif

    {{-- Stats: berbeda tergantung mode akses --}}
    @php
        $pctActive = $totalAll > 0 ? round(($totalActive / $totalAll) * 100) : 0;
        $quotaRemaining = $capacity ? max(0, $capacity - $inClass) : 0;
        $quotaFilledPct = $capacity && $capacity > 0 ? round(($inClass / $capacity) * 100) : 0;
    @endphp

    {{-- MODE: PER ROMBEL --}}
    @if ($isFilteredByClass && $studyGroup)
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0"><span
                                    class="avatar-title bg-primary-subtle rounded fs-2"><i
                                        class="bx bx-group text-primary"></i></span></div>
                            <div class="flex-grow-1">
                                <p class="text-muted mb-0 small">{{ $studyGroup->full_name }}</p>
                                <h4 class="mb-0">{{ number_format($inClass) }}<span class="fw-normal text-muted fs-6"> /
                                        {{ $capacity }}</span></h4>
                            </div>
                        </div>
                        <div class="progress mt-2 mb-1" style="height:4px;">
                            <div class="progress-bar bg-primary" style="width:{{ min(100, $quotaFilledPct) }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span
                                class="{{ $quotaRemaining > 0 ? 'text-success' : 'text-danger' }}">{{ $quotaRemaining > 0 ? $quotaRemaining . ' slot' : 'Penuh' }}</span>
                            <span class="text-muted">{{ $quotaFilledPct }}%</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i
                                        class="bx bx-user-circle text-info"></i></span></div>
                            <div class="flex-grow-1">
                                <p class="text-muted mb-0 small">Wali Kelas</p>
                                <h5 class="mb-0" style="font-size:.95rem;">
                                    {{ $studyGroup->homeroomTeacher?->name ?? '—' }}</h5>
                            </div>
                        </div>
                        <div class="mt-2 small text-muted">
                            <span class="me-3"><i
                                    class="ri-bookmark-line me-1"></i>{{ $studyGroup->gradeLevel?->name ?? '—' }}</span>
                            <span><i class="ri-home-4-line me-1"></i>{{ $studyGroup->room ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0"><span
                                    class="avatar-title bg-success-subtle rounded fs-2"><i
                                        class="bx bx-check-circle text-success"></i></span></div>
                            <div class="flex-grow-1">
                                <p class="text-muted mb-0 small">Aktif</p>
                                <h4 class="mb-0">{{ number_format($inClass) }}</h4>
                            </div>
                        </div>
                        <div class="mt-2 small text-success"><i
                                class="ri-checkbox-circle-fill me-1"></i>{{ $pctActive }}% aktif</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0"><span
                                    class="avatar-title bg-warning-subtle rounded fs-2"><i
                                        class="bx bx-buildings text-warning"></i></span></div>
                            <div class="flex-grow-1">
                                <p class="text-muted mb-0 small">Tingkat</p>
                                <h5 class="mb-0" style="font-size:.95rem;">{{ $studyGroup->gradeLevel?->name ?? '—' }}
                                </h5>
                            </div>
                        </div>
                        <div class="mt-2 small text-muted"><i
                                class="ri-user-location-line me-1"></i>{{ $studyGroup->school?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0"><span
                                    class="avatar-title bg-primary-subtle rounded fs-2"><i
                                        class="bx bx-group text-primary"></i></span></div>
                            <div class="flex-grow-1">
                                <p class="text-muted mb-0 small">Total Santri</p>
                                <h4 class="mb-0">{{ number_format($totalAll) }}</h4>
                            </div>
                        </div>
                        <div class="mt-2 small text-success"><i
                                class="ri-checkbox-circle-fill me-1"></i>{{ number_format($totalActive) }} aktif
                            ({{ $totalAll > 0 ? round(($totalActive / $totalAll) * 100) : 0 }}%)</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0"><span
                                    class="avatar-title bg-success-subtle rounded fs-2"><i
                                        class="bx bx-check-circle text-success"></i></span></div>
                            <div class="flex-grow-1">
                                <p class="text-muted mb-0 small">Sudah di Rombel</p>
                                <h4 class="mb-0">{{ number_format($byRombel['in_rombel'] ?? 0) }}</h4>
                            </div>
                        </div>
                        @php
                            $inR = $byRombel['in_rombel'] ?? 0;
                            $ua = $byRombel['unassigned'] ?? 0;
                            $pctA = $inR + $ua > 0 ? round(($inR / ($inR + $ua)) * 100) : 0;
                        @endphp
                        <div class="progress mt-2 mb-1" style="height:4px;">
                            <div class="progress-bar bg-success" style="width:{{ $pctA }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">{{ $pctA }}%</span>
                            @if ($ua > 0)
                                <span class="text-danger">{{ number_format($ua) }} belum</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i
                                        class="bx bxs-booktent text-info"></i></span></div>
                            <div class="flex-grow-1">
                                <p class="text-muted mb-0 small">Distribusi Tingkat</p>
                                <h4 class="mb-0">{{ number_format($distribusiPerTingkat->sum('total')) }}</h4>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            @forelse($distribusiPerTingkat as $row)
                                <span class="badge bg-info-subtle text-info"
                                 style="font-size:10px;">{{ $row->grade_name }}
                                    <strong>{{ $row->total }}</strong></span>
                            @empty<span class="text-muted small">—</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i
                                        class="bx bx-transfer text-warning"></i></span></div>
                            <div class="flex-grow-1">
                                <p class="text-muted mb-0 small">Mutasi Bulan Ini</p>
                                <h4 class="mb-0">{{ number_format(($mutationInCount ?? 0) + ($mutationOutCount ?? 0)) }}</h4>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mt-2 small">
                            <span class="text-success"><i class="ri-login-box-line me-1"></i>{{ number_format($mutationInCount ?? 0) }} masuk</span>
                            <span class="text-danger"><i class="ri-logout-box-line me-1"></i>{{ number_format($mutationOutCount ?? 0) }} keluar</span>
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
                                @if ($isFilteredByClass && $studyGroup)
                                    {{ $studyGroup->full_name }}
                                @else
                                    Daftar Santri
                                @endif
                            </h5>
                            <p class="text-muted mb-0">Total {{ number_format($totalAll) }} data.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-2">
                                {{-- <a href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}" class="btn btn-outline-primary">
                                    <i class="ri-logout-box-line align-bottom me-1"></i>Mutasi Keluar
                                </a>
                                <a href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}" class="btn btn-outline-success">
                                    <i class="ri-login-box-line align-bottom me-1"></i>Mutasi Masuk
                                </a> --}}
                                @php
                                    $gradeLevel = $studyGroup?->gradeLevel;
                                    $level = $gradeLevel?->level ?? 0;
                                    $schoolType = $studyGroup?->school?->school_type;
                                    // Tentukan grade akhir per jenjang
                                    $finalLevels = match ($schoolType) {
                                        'smp' => [9],
                                        'sd' => [6],
                                        default => [6, 9, 12],
                                    };
                                    $isFinalGrade = in_array($level, $finalLevels);
                                @endphp
                                @if ($studyGroup && !($isDormitoryUser ?? false))
                                    <button type="button" class="btn btn-outline-info" onclick="openBulkModal()">
                                        <i class="ri-user-add-line align-bottom me-1"></i>Tarik Santri
                                    </button>
                                    <a href="{{ route('user.student-move.index', ['userId' => $userId, 'study_group_id' => $studyGroup->id]) }}"
                                        class="btn btn-outline-primary">
                                        <i class="ri-arrow-left-right-line align-bottom me-1"></i>Pindahkan Santri
                                    </a>
                                    @if ($isFinalGrade)
                                        <a href="{{ route('user.bulk-graduation.index', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                                            class="btn btn-outline-warning">
                                            <i class="ri-graduation-cap-line align-bottom me-1"></i>Kelulusan Massal
                                        </a>
                                    @else
                                        <a href="{{ route('user.bulk-promotion.index', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                                            class="btn btn-outline-warning">
                                            <i class="ri-arrow-up-line align-bottom me-1"></i>Kenaikan Kelas
                                        </a>
                                    @endif
                                @endif
                                @unless($isDormitoryUser ?? false)
                                    <a href="{{ route('user.students.create', ['userId' => $userId]) }}"
                                        class="btn btn-success">
                                        <i class="ri-add-line align-bottom me-1"></i> Tambah Santri
                                    </a>
                                    <a href="{{ route('user.students.import-form', ['userId' => $userId]) }}"
                                        class="btn btn-outline-secondary">
                                        <i class="ri-file-upload-line align-bottom me-1"></i> Import Excel
                                    </a>
                                    <a href="{{ route('user.students.import-nama-arab-form', ['userId' => $userId]) }}"
                                        class="btn btn-outline-dark">
                                        <i class="ri-translate-2 align-bottom me-1"></i> Import Nama Arab
                                    </a>
                                    <a href="{{ route('user.students.review-nama-arab', ['userId' => $userId]) }}"
                                        class="btn btn-outline-success">
                                        <i class="ri-check-double-line align-bottom me-1"></i> Review Nama Arab
                                    </a>
                                    <a href="{{ route('user.students.graduation-certificate.settings', ['userId' => $userId]) }}"
                                        class="btn btn-outline-primary" title="Atur format dan mata pelajaran Surat Keterangan Lulus">
                                        <i class="ri-file-paper-2-line align-bottom me-1"></i> Format SKL
                                    </a>
                                    <a href="{{ route('user.students.reconciliation', ['userId' => $userId]) }}"
                                        class="btn btn-outline-warning" title="Periksa data santri yang tidak konsisten">
                                        <i class="ri-error-warning-line align-bottom me-1"></i> Rekonsiliasi
                                    </a>
                                @else
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="ri-information-line me-1"></i>
                                        Anda hanya dapat melihat data Santri yang tinggal di asrama. Data Mahrom tetap dapat dikelola.
                                    </span>
                                @endunless
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Simple filter row --}}
                    <form method="GET" class="row g-2 align-items-end">
                        @if ($isFilteredByClass && $studyGroup)
                            <input type="hidden" name="study_group_id" value="{{ $studyGroup->id }}">
                        @endif

                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari nama, NISN, NIS, NIK..." value="{{ request('search') }}">
                        </div>

                        {{-- ARSIP PERIODE: filter tahun ajaran (kolom Rombel mengikuti TA ini) --}}
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Tahun Ajaran</option>
                                @foreach (($academicYears ?? collect()) as $ay)
                                    <option value="{{ $ay->id }}"
                                        {{ (string) ($selectedAcademicYearId ?? '') === (string) $ay->id ? 'selected' : '' }}>
                                        {{ $ay->name }} ({{ $ay->semester_text }})
                                        @if ($ay->is_active)
                                            — Aktif
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif
                                </option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif
                                </option>
                                <option value="graduate" {{ request('status') === 'graduate' ? 'selected' : '' }}>Lulus
                                </option>
                                <option value="dropped" {{ request('status') === 'dropped' ? 'selected' : '' }}>Dropout
                                </option>
                                <option value="transfer_out" {{ request('status') === 'transfer_out' ? 'selected' : '' }}>
                                    Pindah Keluar</option>
                                <option value="transfer_in" {{ request('status') === 'transfer_in' ? 'selected' : '' }}>
                                    Pindah Masuk</option>
                            </select>
                        </div>

                            <div class="col-md-3">
                                <label class="form-label">Status Boarding</label>
                                <select name="boarding_status" class="form-select">
                                    <option value="">Semua</option>
                                    <option value="boarding" {{ request('boarding_status') === 'boarding' ? 'selected' : '' }}>Boarding</option>
                                    <option value="non_boarding" {{ request('boarding_status') === 'non_boarding' ? 'selected' : '' }}>Non-Boarding</option>
                                    <option value="__none" {{ request('boarding_status') === '__none' ? 'selected' : '' }}>Belum Ditentukan</option>
                                </select>
                            </div>

                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                        </div>

                        @if (!$isFilteredByClass)
                            <div class="col-md-auto">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                                    data-bs-target="#filterModal">
                                    <i class="ri-settings-3-line me-1"></i> Lainnya
                                </button>
                            </div>
                        @endif
                    </form>
                </div>

                {{-- Modal Filter Lengkap --}}
                <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="filterModalLabel">
                                    <i class="ri-filter-3-line me-2 text-primary"></i>Filter Lanjutan
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="GET" id="filterFormModal">
                                @if ($isFilteredByClass && $studyGroup)
                                    <input type="hidden" name="study_group_id" value="{{ $studyGroup->id }}">
                                @endif
                                <div class="modal-body">
                                    <div class="row g-3">
                                        @if (!$isFilteredByClass && $canViewAllSchools)
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Sekolah</label>
                                                <select name="school_id" class="form-select">
                                                    <option value="">Semua Sekolah</option>
                                                    @foreach ($schools as $s)
                                                        <option value="{{ $s->id }}"
                                                            {{ request('school_id') == $s->id ? 'selected' : '' }}>
                                                            {{ $s->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif

                                        @if (!$isFilteredByClass)
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Tingkat</label>
                                                <select name="grade_level_id" class="form-select">
                                                    <option value="">Semua Tingkat</option>
                                                    @foreach ($gradeLevels as $gl)
                                                        <option value="{{ $gl->id }}"
                                                            {{ request('grade_level_id') == $gl->id ? 'selected' : '' }}>
                                                            {{ $gl->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Provinsi</label>
                                                <select name="province_code" class="form-select">
                                                    <option value="">Semua Provinsi</option>
                                                    @foreach ($provinces as $p)
                                                        <option value="{{ $p->code }}"
                                                            {{ request('province_code') == $p->code ? 'selected' : '' }}>
                                                            {{ $p->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Kategori</label>
                                                <select name="alumni_filter" class="form-select">
                                                    <option value="">Semua</option>
                                                    <option value="alumni"
                                                        {{ request('alumni_filter') === 'alumni' ? 'selected' : '' }}>
                                                        Alumni</option>
                                                    <option value="non_alumni"
                                                        {{ request('alumni_filter') === 'non_alumni' ? 'selected' : '' }}>
                                                        Non Alumni</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Jalur Masuk</label>
                                                <select name="entry_source" class="form-select">
                                                    <option value="">Semua Jalur</option>
                                                    @foreach (['manual' => 'Tambah manual', 'import' => 'Import Excel', 'mutation' => 'Mutasi masuk', 'guardian_mobile' => 'Registrasi wali'] as $value => $label)
                                                        <option value="{{ $value }}"
                                                            {{ request('entry_source') === $value ? 'selected' : '' }}>
                                                            {{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Agama</label>
                                                <select name="religion" class="form-select">
                                                    <option value="">Semua Agama</option>
                                                    @foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $agama)
                                                        <option value="{{ $agama }}"
                                                            {{ request('religion') === $agama ? 'selected' : '' }}>
                                                            {{ $agama }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Kabupaten/Kota (kode)</label>
                                                <input type="text" name="city_code" class="form-control"
                                                    maxlength="4" placeholder="Contoh: 3204"
                                                    value="{{ request('city_code') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Tingkat Masuk</label>
                                                <input type="number" name="entry_grade_level" class="form-control"
                                                    min="1" max="15" placeholder="Contoh: 7"
                                                    value="{{ request('entry_grade_level') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Penerima PIP</label>
                                                <select name="is_pip_eligible" class="form-select">
                                                    <option value="">Semua</option>
                                                    <option value="1"
                                                        {{ request('is_pip_eligible') === '1' ? 'selected' : '' }}>
                                                        Layak PIP</option>
                                                    <option value="0"
                                                        {{ request('is_pip_eligible') === '0' ? 'selected' : '' }}>
                                                        Tidak Layak</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Status</label>
                                                <select name="status" class="form-select">
                                                    <option value="">Semua Status</option>
                                                    @foreach (['active' => 'Aktif', 'inactive' => 'Nonaktif', 'transfer_in' => 'Pindahan Masuk', 'transfer_out' => 'Pindahan Keluar', 'graduate' => 'Lulus', 'dropped' => 'Dropout'] as $value => $label)
                                                        <option value="{{ $value }}"
                                                            {{ request('status') === $value ? 'selected' : '' }}>
                                                            {{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <a href="{{ route('user.students.index', ['userId' => $userId]) }}"
                                        class="btn btn-light">Reset</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-search-line me-1"></i> Terapkan Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'gender' => null, 'is_pip_eligible' => null, 'alumni_filter' => null, 'profile_incomplete' => null, 'arsip' => null, 'page' => null]) }}"
                           class="filter-badge {{ !request('status') && !request('is_pip_eligible') && !request('alumni_filter') && !request('profile_incomplete') && !request('arsip') ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'active', 'page' => null]) }}"
                           class="filter-badge {{ request('status') === 'active' ? 'active' : '' }}"><i class="ri-checkbox-circle-line"></i>Aktif</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'transfer_in', 'page' => null]) }}"
                           class="filter-badge {{ request('status') === 'transfer_in' ? 'active' : '' }}"><i class="ri-login-box-line"></i>Pindahan Masuk</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'transfer_out', 'page' => null]) }}"
                           class="filter-badge {{ request('status') === 'transfer_out' ? 'active' : '' }}"><i class="ri-logout-box-line"></i>Pindahan Keluar</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'graduate', 'page' => null]) }}"
                           class="filter-badge {{ request('status') === 'graduate' ? 'active' : '' }}"><i class="ri-graduation-cap-line"></i>Lulus</a>
                        <a href="{{ request()->fullUrlWithQuery(['is_pip_eligible' => '1', 'page' => null]) }}"
                           class="filter-badge {{ request('is_pip_eligible') ? 'active' : '' }}"><i class="ri-hand-coin-line"></i>Penerima PIP</a>
                        <a href="{{ request()->fullUrlWithQuery(['profile_incomplete' => 1, 'page' => null]) }}"
                           class="filter-badge {{ request('profile_incomplete') ? 'active' : '' }}"><i class="ri-error-warning-line"></i>Data Belum Lengkap</a>
                        <a href="{{ request()->fullUrlWithQuery(['arsip' => 1, 'status' => null, 'alumni_filter' => null, 'profile_incomplete' => null, 'page' => null]) }}"
                           class="filter-badge {{ request('arsip') ? 'active' : '' }}"><i class="ri-archive-line"></i>Diarsipkan</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px">#</th>
                                <th style="width:56px">Foto</th>
                                <th>Nama</th>
                                <th>Nama Arab</th>
                                <th>NISN</th>
                                <th>Rombel
                                    @if (($rombelDisplayAyId ?? null) && ($ay = ($academicYears ?? collect())->firstWhere('id', $rombelDisplayAyId)))
                                        <div class="text-muted fw-normal" style="font-size:10px;">
                                            TA {{ $ay->name }} ({{ $ay->semester_text }})
                                            @if (($isArchiveAy ?? false))
                                                · arsip
                                            @endif
                                        </div>
                                    @endif
                                </th>
                                <th>JK</th>
                                <th>Tempat, Tgl Lahir</th>
                                @if($showSchoolColumn ?? false)
                                    <th>Sekolah</th>
                                @endif
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $s)
                                <tr>
                                    <td class="text-muted">{{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}</td>
                                    <td>
                                        @if ($s->photo_url)
                                            <img src="{{ $s->photo_url }}" alt="{{ $s->name }}"
                                                 class="rounded-circle object-fit-cover" style="width:36px;height:36px;">
                                        @else
                                            @php
                                                $__words = preg_split('/\s+/', trim((string) $s->name)) ?: [];
                                                $__initials = strtoupper(mb_substr($__words[0] ?? '', 0, 1) . (count($__words) > 1 ? mb_substr($__words[count($__words) - 1], 0, 1) : ''));
                                            @endphp
                                            <span class="avatar-xs">
                                                <span class="avatar-title rounded-circle {{ $s->gender === 'P' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary' }}"
                                                      style="font-size:.65rem;">{{ $__initials }}</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($s->trashed())
                                            <span class="fw-medium text-muted">{{ $s->name }}</span>
                                            <span class="badge bg-secondary-subtle text-secondary ms-1"
                                                  title="Data diarsipkan — riwayat tetap tersimpan dan dapat dipulihkan">
                                                <i class="ri-archive-line"></i> Diarsipkan
                                            </span>
                                        @else
                                            <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $s->id]) }}"
                                                class="fw-medium link-primary">
                                                {{ $s->name }}
                                            </a>
                                        @endif
                                        @php
                                            $selectedRombelHistory = request('study_group_id')
                                                ? $s->classHistories->firstWhere('study_group_id', request('study_group_id'))
                                                : null;
                                        @endphp
                                        @if($selectedRombelHistory && ! $selectedRombelHistory->is_active)
                                            <span class="badge bg-secondary-subtle text-secondary ms-1"
                                                  title="Sudah pindah/naik dari rombel ini — data tetap tersimpan sebagai arsip">Pindah</span>
                                        @endif
                                        @php $missingProfile = $s->missingProfileFields(); @endphp
                                        @if($missingProfile)
                                            @unless(($isDormitoryUser ?? false) || $s->trashed())
                                                <a href="{{ route('user.students.edit', ['userId' => $userId, 'santriUuid' => $s->id]) }}"
                                                   class="badge bg-warning-subtle text-warning ms-1 text-decoration-none"
                                                   title="Belum lengkap: {{ implode(', ', $missingProfile) }} — klik untuk melengkapi">
                                                    <i class="ri-error-warning-line"></i> Belum lengkap
                                                </a>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning ms-1"
                                                      title="Belum lengkap: {{ implode(', ', $missingProfile) }}">Belum lengkap</span>
                                            @endunless
                                        @endif
                                    </td>
                                    <td dir="rtl" class="text-end">
                                        @if($s->name_ar)
                                            <span class="fw-medium">{{ $s->name_ar }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><code>{{ $s->nisn }}</code></td>
                                    @php
                                        // ARSIP PERIODE: rombel mengikuti TA yang ditampilkan (bukan rombel saat ini).
                                        $rombelHistory = ($rombelDisplayAyId ?? null)
                                            ? $s->classHistories->firstWhere('academic_year_id', $rombelDisplayAyId)
                                            : null;
                                        $rombelPlacement = $rombelHistory?->studyGroup;
                                    @endphp
                                    <td>
                                        @if($rombelPlacement)
                                            <a href="{{ route('user.students.index', ['userId' => $userId, 'study_group_id' => $rombelPlacement->id, 'academic_year_id' => $rombelDisplayAyId]) }}"
                                               class="badge bg-primary-subtle text-primary text-decoration-none"
                                               title="Tampilkan santri rombel ini pada TA tersebut">
                                                {{ $rombelPlacement->name }}
                                            </a>
                                            @if(! $rombelHistory->is_active)
                                                <span class="badge bg-secondary-subtle text-secondary"
                                                      title="Penempatan periode ini sudah berakhir (pindah/naik kelas) — tetap tampil sebagai arsip">Pindah</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($s->gender === 'L')
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
                                    @if($showSchoolColumn ?? false)
                                        <td>
                                            <a href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $s->school_id]) }}"
                                                class="text-muted small">
                                                {{ $s->school?->name ?? '-' }}
                                            </a>
                                        </td>
                                    @endif
                                    <td>
                                        <span
                                            class="badge bg-{{ $s->status === 'active' ? 'success' : ($s->status === 'graduate' ? 'info' : 'secondary') }}-subtle text-{{ $s->status === 'active' ? 'success' : ($s->status === 'graduate' ? 'info' : 'secondary') }}">
                                            {{ $s->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                                                <i class="ri-more-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                @if($s->trashed())
                                                    <li>
                                                        <form method="POST"
                                                            action="{{ route('user.students.restore', ['userId' => $userId, 'santriUuid' => $s->id]) }}">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="ri-arrow-go-back-line me-2 text-success"></i>Pulihkan dari Arsip
                                                            </button>
                                                        </form>
                                                    </li>
                                                @else
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $s->id]) }}">
                                                        <i class="ri-eye-line me-2"></i>Lihat
                                                    </a>
                                                </li>
                                                <li>
                                                    @unless($isDormitoryUser ?? false)
                                                        <a class="dropdown-item"
                                                            href="{{ route('user.students.edit', ['userId' => $userId, 'santriUuid' => $s->id]) }}">
                                                            <i class="ri-pencil-line me-2"></i>Edit
                                                        </a>
                                                    @endunless
                                                </li>
                                                @if ($studyGroup)
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('user.student-move.index', ['userId' => $userId, 'study_group_id' => $studyGroup->id]) }}">
                                                            <i
                                                                class="ri-arrow-left-right-line me-2 text-primary"></i>Pindahkan
                                                        </a>
                                                    </li>
                                                @endif
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ route('user.mutations-out.create', ['userId' => $userId, 'student_id' => $s->id]) }}">
                                                        <i class="ri-logout-box-line me-2 text-danger"></i>Mutasi Keluar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ route('user.mutations-in.create', ['userId' => $userId, 'student_id' => $s->id]) }}">
                                                        <i class="ri-login-box-line me-2 text-success"></i>Mutasi Masuk
                                                    </a>
                                                </li>
                                                @if ($s->status === 'graduate')
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" target="_blank"
                                                            href="{{ route('user.students.graduation-certificate.print', ['userId' => $userId, 'santriUuid' => $s->id]) }}">
                                                            <i class="ri-file-paper-2-line me-2 text-primary"></i>Cetak SKL
                                                        </a>
                                                    </li>
                                                @endif
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-empty-state colspan="{{ 10 + (($showSchoolColumn ?? false) ? 1 : 0) }}"
                                    title="{{ request('arsip') ? 'Tidak ada data di arsip' : 'Belum ada data santri' }}"
                                    message="{{ request('arsip') ? 'Data santri yang diarsipkan akan tampil di sini dan dapat dipulihkan.' : 'Data santri yang ditambahkan akan tampil di sini.' }}">
                                    @unless(($isDormitoryUser ?? false) || request('arsip'))
                                        <a href="{{ route('user.students.create', ['userId' => $userId]) }}" class="btn btn-success mt-3">
                                            <i class="ri-add-line me-1"></i>Tambah Santri
                                        </a>
                                    @endunless
                                </x-empty-state>
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

{{-- Modal Tarik Santri (hanya di mode rombel) --}}
@section('modal')
    @if ($studyGroup)
        <div class="modal fade" id="bulkAddModal" tabindex="-1" aria-labelledby="bulkAddModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkAddModalLabel">
                            <i class="ri-user-add-line me-1"></i>
                            Tarik Santri ke {{ $studyGroup->full_name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="px-3 pt-3 pb-2 border-bottom bg-light-subtle">
                            <div class="row align-items-center g-2">
                                <div class="col-md-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                                        <input type="text" id="bulkSearchInput" class="form-control"
                                            placeholder="Cari nama atau NISN..." autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="checkbox" id="selectAllStudents"
                                            onchange="toggleSelectAll(this)">
                                        <label class="form-check-label fw-semibold" for="selectAllStudents">Pilih
                                            Semua</label>
                                    </div>
                                    <span id="selectedCountBadge" class="badge bg-success ms-1"
                                        style="display:none"></span>
                                </div>
                            </div>
                        </div>
                        <div id="bulkStudentList" class="list-group list-group-flush"
                            style="max-height:380px;overflow-y:auto;">
                            <div class="text-center text-muted py-5" id="bulkLoadingState">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <div class="mt-2">Memuat data...</div>
                            </div>
                        </div>
                        <div id="bulkEmptyState" class="text-center text-muted py-5" style="display:none">
                            <i class="ri-user-follow-line fs-1 d-block mb-2"></i>
                            <p id="bulkEmptyMessage" class="mb-0">Semua santri sudah masuk rombel</p>
                        </div>
                        <div id="bulkErrorState" class="text-center py-4" style="display:none">
                            <i class="ri-error-warning-line fs-1 text-danger d-block mb-2"></i>
                            <p class="text-danger mb-2" id="bulkErrorMessage">Gagal memuat data santri.</p>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="loadUnassignedStudents()">
                                <i class="ri-refresh-line me-1"></i> Coba Lagi
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <span id="selectedInfoText" class="me-auto text-muted small"></span>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-success" id="confirmBulkAddBtn" disabled
                            onclick="submitBulkAdd()">
                            <i class="ri-add-line me-1"></i> Masukkan ke Rombel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('script')
    @if ($studyGroup)
        <script>
            const STUDY_GROUP_ID = '{{ $studyGroup->id }}';
            const USER_ID = '{{ $userId }}';
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            const URL_UNASSIGNED =
                '{{ route('user.api.study-groups.students.unassigned', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}';
            const URL_BULK_ADD =
                '{{ route('user.api.study-groups.students.bulk-add', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}';

            let allStudents = [];
            let displayedStudents = [];
            let selectedIds = new Set();
            let searchTimer = null;

            function openBulkModal() {
                selectedIds = new Set();
                allStudents = [];
                displayedStudents = [];

                const searchInput = document.getElementById('bulkSearchInput');
                if (searchInput) searchInput.value = '';
                document.getElementById('selectAllStudents').checked = false;
                document.getElementById('confirmBulkAddBtn').disabled = true;
                document.getElementById('selectedCountBadge').style.display = 'none';
                document.getElementById('selectedInfoText').textContent = '';

                showBulkState('loading');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkAddModal')).show();
                loadUnassignedStudents();
            }

            async function loadUnassignedStudents() {
                showBulkState('loading');
                try {
                    const res = await fetch(URL_UNASSIGNED, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });
                    if (!res.ok) {
                        let errMsg = 'Server error: ' + res.status;
                        try {
                            const e = await res.json();
                            errMsg = e.message || errMsg;
                        } catch (_) {}
                        throw new Error(errMsg);
                    }
                    const json = await res.json();
                    allStudents = json.data || [];
                    displayedStudents = [...allStudents];
                    renderBulkList(displayedStudents);
                } catch (err) {
                    console.error('[BulkAdd] error:', err);
                    document.getElementById('bulkErrorMessage').textContent = 'Gagal memuat data: ' + err.message;
                    showBulkState('error');
                }
            }

            function showBulkState(state) {
                document.getElementById('bulkStudentList').style.display = state === 'list' ? '' : 'none';
                document.getElementById('bulkLoadingState').style.display = state === 'loading' ? '' : 'none';
                document.getElementById('bulkEmptyState').style.display = state === 'empty' ? '' : 'none';
                document.getElementById('bulkErrorState').style.display = state === 'error' ? '' : 'none';
            }

            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('bulkSearchInput')?.addEventListener('input', function() {
                    clearTimeout(searchTimer);
                    const q = this.value.trim().toLowerCase();
                    searchTimer = setTimeout(function() {
                        displayedStudents = q ?
                            allStudents.filter(function(s) {
                                return (s.name || '').toLowerCase().includes(q) ||
                                    (s.nisn || '').toLowerCase().includes(q);
                            }) :
                            [...allStudents];
                        renderBulkList(displayedStudents);
                    }, 200);
                });
            });

            function renderBulkList(students) {
                if (!students.length) {
                    document.getElementById('bulkEmptyMessage').textContent =
                        allStudents.length === 0 ? 'Semua santri sudah masuk rombel' : 'Tidak ada hasil pencarian';
                    showBulkState('empty');
                    return;
                }
                showBulkState('list');
                const container = document.getElementById('bulkStudentList');
                container.innerHTML = students.map(function(s) {
                    const gc = s.gender === 'P' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary';
                    return '<div class="list-group-item d-flex align-items-center gap-2 py-2 student-item" data-id="' +
                        s.id + '">' +
                        '<input class="form-check-input student-checkbox flex-shrink-0" type="checkbox"' +
                        ' value="' + s.id + '" id="cb-' + s.id + '" onchange="toggleStudent(\'' + s.id + '\')">' +
                        '<label class="form-check-label flex-grow-1" for="cb-' + s.id + '" style="cursor:pointer">' +
                        '<strong>' + escHtml(s.name) + '</strong>' +
                        '<span class="text-muted ms-2">' + (s.nisn ? 'NISN: ' + s.nisn : '') + '</span>' +
                        '</label>' +
                        '<span class="badge ' + gc + '">' + (s.gender === 'P' ? 'P' : 'L') + '</span>' +
                        '</div>';
                }).join('');
                selectedIds.forEach(function(id) {
                    const cb = document.getElementById('cb-' + id);
                    if (cb) cb.checked = true;
                });
                updateBulkUI();
            }

            function toggleStudent(id) {
                const cb = document.getElementById('cb-' + id);
                if (cb && cb.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                    document.getElementById('selectAllStudents').checked = false;
                }
                updateBulkUI();
            }

            function toggleSelectAll(el) {
                if (el.checked) {
                    displayedStudents.forEach(function(s) {
                        selectedIds.add(s.id);
                    });
                    document.querySelectorAll('.student-checkbox').forEach(function(c) {
                        c.checked = true;
                    });
                } else {
                    displayedStudents.forEach(function(s) {
                        selectedIds.delete(s.id);
                    });
                    document.querySelectorAll('.student-checkbox').forEach(function(c) {
                        c.checked = false;
                    });
                }
                updateBulkUI();
            }

            function updateBulkUI() {
                const count = selectedIds.size;
                const btn = document.getElementById('confirmBulkAddBtn');
                btn.disabled = count === 0;
                btn.innerHTML = count > 0 ?
                    '<i class="ri-add-line me-1"></i> Masukkan ' + count + ' Santri' :
                    '<i class="ri-add-line me-1"></i> Masukkan ke Rombel';
                document.getElementById('selectedCountBadge').style.display = count > 0 ? '' : 'none';
                document.getElementById('selectedCountBadge').textContent = count > 0 ? count : '';
                document.getElementById('selectedInfoText').textContent = count > 0 ?
                    count + ' Santri dipilih dari ' + displayedStudents.length + ' yang ditampilkan' : '';
            }

            async function submitBulkAdd() {
                if (selectedIds.size === 0) return;
                const ids = Array.from(selectedIds);
                const btn = document.getElementById('confirmBulkAddBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

                try {
                    const res = await fetch(URL_BULK_ADD, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            student_ids: ids
                        }),
                    });
                    const json = await res.json();
                    if (json.success) {
                        bootstrap.Modal.getInstance(document.getElementById('bulkAddModal')).hide();
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: json.message || ids.length + ' Santri berhasil ditambahkan.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: json.message || 'Gagal menambahkan Santri.'
                            });
                        }
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ri-add-line me-1"></i> Masukkan ke Rombel';
                    }
                } catch (err) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.message
                        });
                    }
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ri-add-line me-1"></i> Masukkan ke Rombel';
                }
            }

            function escHtml(str) {
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }
        </script>
    @endif
@endsection
