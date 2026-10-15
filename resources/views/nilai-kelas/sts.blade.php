@extends('layouts.master')
@section('title') Leger Nilai {{ strtoupper($legerMode ?? 'sts') }} — {{ $studyGroup->name ?? '' }} @endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? Auth::id();
        $activeTab = request('tab', 'leger');
        $selectedAy = $academicYears->firstWhere('id', $selectedAyId);
        $totalSantri = $students->count();
        $totalMapel = $subjectMap->count();

        $isAgama = function($name) {
            $l = strtolower($name);
            return preg_match('/(aqidah|adab|fiqih|tahfidz|hafalan hadits|bahasa arab|b\. ?arab|qowaid|ta[\'"]?bir|sharaf)/i', $l);
        };
        $autosaveUrl = route('user.schools.nilai-kelas.sts.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]);
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">Data Nilai</a> @endslot
        @slot('title') STS — {{ $studyGroup->name ?? '' }}</title>
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    @if($selectedAy)
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="bx bx-group text-primary"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Kelas</p>
                            <h3 class="fw-bold ff-secondary mb-0" style="font-size:18px;">{{ $studyGroup->name }}</h3>
                            @if($studyGroup->homeroomTeacher)
                                <small class="text-muted"><i class="ri-user-line me-1"></i>{{ $studyGroup->homeroomTeacher->name }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="ri-book-open-line text-info"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Tahun Ajaran</p>
                            <h3 class="fw-bold ff-secondary mb-0" style="font-size:16px;">{{ $selectedAy->name }}</h3>
                            <small class="text-muted text-capitalize">{{ $selectedSemester }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-file-text-line text-success"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Mata Pelajaran</p>
                            <h3 class="fw-bold ff-secondary mb-1">{{ $totalMapel }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-user-3-line text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Jumlah Santri</p>
                            <h3 class="fw-bold ff-secondary mb-1">{{ $totalSantri }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Card --}}
    <div class="card" id="nilaiKelas">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">
                        <i class="ri-file-edit-line text-primary me-1"></i>
                        Leger Nilai {{ strtoupper($legerMode ?? 'sts') }} — Kelas {{ $studyGroup->name ?? '' }}
                    </h5>

                    @if($isPrivileged)
                    <span class="badge bg-info-subtle text-info me-1" style="font-size:11px;padding:4px 10px;">
                        <i class="ri-user-settings-line align-bottom me-1"></i>Mode TU / Waka
                    </span>
                    @endif
                </div>
                <div class="col-sm-auto">
                    {{-- Action Buttons --}}
                    <a href="{{ route('user.schools.nilai-kelas.rapor', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester]) }}"
                    class="btn btn-outline-primary btn-md me-1">
                        <i class="ri-file-paper-2-line me-1"></i> Cetak Rapor
                    </a>
                    <!-- <a href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester, 'jenis' => 'sas']) }}"
                    class="btn btn-success btn-md me-1" target="_blank">
                        <i class="ri-printer-line me-1"></i> Rapor Massal
                    </a> -->
                    <a href="{{ route('user.schools.nilai-kelas.leger.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester, 'jenis' => 'sts']) }}"
                    class="btn btn-outline-dark btn-md me-1" target="_blank">
                        <i class="ri-printer-line me-1"></i> Leger STS
                    </a>
                    <a href="{{ route('user.schools.nilai-kelas.leger.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester, 'jenis' => 'sas']) }}"
                    class="btn btn-outline-dark btn-md me-1" target="_blank">
                        <i class="ri-printer-line me-1"></i> Leger SAS
                    </a>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary btn-md dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="ri-download-2-line me-1"></i> Download Leger (Excel)
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('user.schools.nilai-kelas.leger.download', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester, 'jenis' => 'sts']) }}">
                                    Leger STS
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('user.schools.nilai-kelas.leger.download', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester, 'jenis' => 'sas']) }}">
                                    Leger SAS
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter inside card header --}}
        <div class="card-header py-2 bg-light border-bottom">
            <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                @if($activeTab === 'mapel' && $selectedBookId)
                    <input type="hidden" name="admin_book_id" value="{{ $selectedBookId }}">
                @endif
                <input type="hidden" name="search" id="searchInputHidden" value="{{ request('search') }}">
                <span class="text-muted fw-medium" style="font-size:12px;">
                    <i class="ri-filter-line me-1"></i>Filter:
                </span>
                @if(($isPrivileged ?? false) && $schoolGroups->isNotEmpty())
                <div>
                    <select class="form-select form-select-sm fw-semibold" style="min-width:150px;"
                            onchange="if (this.value) window.location.href = this.value;" title="Pilih kelas">
                        @foreach($schoolGroups as $g)
                            <option value="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $g->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester]) }}"
                                    {{ $g->id === $studyGroup->id ? 'selected' : '' }}>
                                Kelas - {{ $g->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:160px;">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $selectedAyId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:120px;">
                        <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Semester Ganjil</option>
                        <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Semester Genap</option>
                    </select>
                </div>
                <div>
                    <input type="text" id="searchStudentInput" class="form-control form-control-sm"
                           placeholder="Cari nama Santri..."
                           value="{{ request('search') }}"
                           style="min-width:150px;"
                           oninput="syncSearchInput(this.value)">
                </div>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span id="autosave-indicator" class="badge bg-light text-muted border d-flex align-items-center gap-1" style="font-size:.7rem;">
                        <span id="autosave-dot" class="rounded-circle bg-secondary" style="width:7px;height:7px;display:inline-block;"></span>
                        <span id="autosave-text">Menunggu…</span>
                    </span>
                    <span class="badge bg-primary-subtle text-primary" style="font-size:12px;">
                        {{ $totalMapel }} Mapel
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:12px;">
                        {{ $totalSantri }} Santri
                    </span>
                </div>
            </form>
        </div>

        @if(($autoProvisionedBooks ?? 0) > 0)
            <div class="alert alert-info border-0 rounded-0 mb-0 d-flex align-items-center gap-2" style="font-size:12px;">
                <i class="ri-information-line fs-16"></i>
                <span>Buku nilai semester {{ $selectedSemester }} belum ada — <strong>{{ $autoProvisionedBooks }} buku</strong> otomatis disiapkan dari semester lain. Silakan mulai mengisi nilai.</span>
            </div>
        @endif

        {{-- Wizard Tabs --}}
        <ul class="nav nav-tabs px-4 pt-3" role="tablist" style="overflow-x:auto;flex-wrap:nowrap;white-space:nowrap;">
            <li class="nav-item">
                <a href="{{ route('user.schools.nilai-kelas.sts', array_merge(['userId' => $userId, 'studyGroupId' => $studyGroup->id], request()->query(), ['tab' => 'leger'])) }}"
                   class="nav-link {{ $activeTab === 'leger' ? 'active' : '' }}"
                   style="font-size:12px;">
                    <i class="ri-table-line me-1"></i>Leger
                </a>
            </li>
            @foreach($subjectMap as $idx => $subject)
                @php
                    $book = $bookMap[$subject->id] ?? null;
                    $isActive = $activeTab === 'mapel' && $selectedBookId == $book?->id;
                @endphp
                <li class="nav-item">
                    <a href="{{ route('user.schools.nilai-kelas.sts', array_merge(['userId' => $userId, 'studyGroupId' => $studyGroup->id], request()->query(), ['tab' => 'mapel', 'admin_book_id' => $book?->id])) }}"
                       class="nav-link {{ $isActive ? 'active' : '' }}"
                       style="font-size:12px;">
                        <span class="badge bg-{{ $isAgama($subject->name) ? 'primary' : 'secondary' }}-subtle text-{{ $isAgama($subject->name) ? 'primary' : 'secondary' }}"
                              style="font-size:9px;padding:1px 5px;">{{ $idx + 1 }}</span>
                        {{ $subject->name }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="card-body p-0">

            {{-- ========================================== --}}
            {{-- TAB: LEGER --}}
            {{-- ========================================== --}}
            @if($activeTab === 'leger')
                @if($subjectMap->isEmpty())
                    <div class="text-center py-5">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-title bg-light rounded-circle">
                                <i class="ri-book-open-line fs-1 text-muted"></i>
                            </div>
                        </div>
                        <h6 class="text-muted">Belum ada mapel di kelas ini</h6>
                        <p class="text-muted mb-0" style="font-size:12px;">Pastikan Buku Admin Guru sudah terisi.</p>
                    </div>
                @else
                    {{-- Wizard sheet: Leger STS | Leger SAS --}}
                    <div class="px-4 pt-3 pb-1">
                        <ul class="nav nav-pills gap-2">
                            <li class="nav-item">
                                <a class="nav-link {{ ($legerMode ?? 'sts') === 'sts' ? 'active' : '' }}"
                                   href="{{ route('user.schools.nilai-kelas.sts', array_merge(['userId' => $userId, 'studyGroupId' => $studyGroup->id], request()->query(), ['tab' => 'leger', 'leger' => 'sts'])) }}">
                                    <i class="ri-table-line me-1"></i>Leger STS
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ ($legerMode ?? 'sts') === 'sas' ? 'active' : '' }}"
                                   href="{{ route('user.schools.nilai-kelas.sts', array_merge(['userId' => $userId, 'studyGroupId' => $studyGroup->id], request()->query(), ['tab' => 'leger', 'leger' => 'sas'])) }}">
                                    <i class="ri-file-list-3-line me-1"></i>Leger SAS
                                </a>
                            </li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('user.schools.nilai-kelas.sts.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}" id="formLeger">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                        <input type="hidden" name="tab" value="leger">

                        <div class="table-responsive leger-scroll">
                            <table id="tab-leger" class="table table-bordered table-hover align-middle mb-0" style="min-width: max-content;">
                                <thead class="table-light text-center">
                                    @php
                                        $isSasLeger = ($legerMode ?? 'sts') === 'sas';
                                        $umumCount = $subjectMap->count() - ($agamaCount ?? 0);
                                    @endphp
                                    {{-- Baris grup: Pembiasaan/Tahfidz (SAS) · Agama | Umum · Presensi --}}
                                    <tr>
                                        <th rowspan="3" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="no">No</th>
                                        <th rowspan="3" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="nis">NIS</th>
                                        <th rowspan="3" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="name">
                                            <i class="ri-user-line me-1"></i>Nama Santri
                                        </th>
                                        @if($isSasLeger)
                                            <th colspan="3" class="align-middle" style="background:var(--bs-warning-bg-subtle);color:var(--bs-warning-text-emphasis);letter-spacing:0.4px;">PEMBIASAAN PAGI</th>
                                            <th colspan="2" class="align-middle" style="background:var(--bs-primary-bg-subtle);color:var(--bs-primary-text-emphasis);letter-spacing:0.4px;">TAHFIDZ</th>
                                        @endif
                                        @if(($agamaCount ?? 0) > 0)
                                            <th colspan="{{ $agamaCount }}" class="align-middle bg-success-subtle text-success" style="letter-spacing:0.5px;">AGAMA</th>
                                        @endif
                                        @if($umumCount > 0)
                                            <th colspan="{{ $umumCount }}" class="align-middle bg-primary-subtle text-primary" style="letter-spacing:0.5px;">UMUM</th>
                                        @endif
                                        <th rowspan="4" style="background:var(--bs-secondary-bg);" class="text-dark align-middle" data-sort="total">Jumlah</th>
                                        <th rowspan="4" style="background:var(--bs-secondary-bg);" class="text-dark align-middle" data-sort="avg">Rata-rata</th>
                                        <th rowspan="4" style="background:var(--bs-secondary-bg);" class="text-dark align-middle" data-sort="rank">Peringkat</th>
                                        <th rowspan="4" style="background:var(--bs-secondary-bg);" class="text-dark align-middle" data-sort="pred">Predikat</th>
                                        <th class="bg-secondary-subtle text-secondary" colspan="3">PRESENSI</th>
                                    </tr>
                                    <tr class="text-center">
                                        @if($isSasLeger)
                                            <th rowspan="3" style="background:var(--bs-warning-bg-subtle);" data-sort="doa">Do'a</th>
                                            <th rowspan="3" style="background:var(--bs-warning-bg-subtle);" data-sort="hiwar">Hiwar</th>
                                            <th rowspan="3" style="background:var(--bs-warning-bg-subtle);" data-sort="conv">Conversation</th>
                                            <th rowspan="3" style="background:var(--bs-primary-bg-subtle);" data-sort="juz">Juz</th>
                                            <th rowspan="3" style="background:var(--bs-primary-bg-subtle);" data-sort="halaqoh">Halaqoh</th>
                                        @endif
                                        @foreach($subjectMap as $subject)
                                            <th rowspan="2" style="background:var(--bs-tertiary-bg);" title="{{ $subject->name }}" data-sort="subject-{{ $subject->id }}">
                                                <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:0.2px;">
                                                    {{ \App\Support\AcademicNilai::shortSubjectName($subject->name) }}
                                                </div>
                                            </th>
                                        @endforeach
                                        <td colspan="3" style="background:var(--bs-tertiary-bg);"></td>
                                    </tr>
                                    <tr class="text-center">
                                        <th rowspan="2" class="bg-success-subtle text-success" data-sort="s">S</th>
                                        <th rowspan="2" class="bg-warning-subtle text-warning" data-sort="i">I</th>
                                        <th rowspan="2" class="bg-danger-subtle text-danger" data-sort="a">A</th>
                                    </tr>
                                    <tr>
                                        <th colspan="{{ 3 }}" class="text-center fw-bold" style="background:var(--bs-tertiary-bg);vertical-align:middle;" data-col="name">
                                            <span class="bg-primary-subtle text-primary">
                                                <i class="ri-checkbox-circle-line me-1"></i>KKTP
                                            </span>
                                        </th>
                                        @foreach($subjectMap as $subject)
                                            @php $book = $bookMap[$subject->id] ?? null; @endphp
                                            <th>
                                                @if($book)
                                                    <input type="number" class="kktp-input"
                                                           name="leger_kkm[{{ $book->id }}]"
                                                           value="{{ $book?->kktp?->kkm_score ?? 75 }}"
                                                           min="0" max="100" step="0.01"
                                                           oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                           title="KKTP {{ $subject->name }}">
                                                @else
                                                    <span style="font-weight:700;color:var(--bs-secondary-color);">—</span>
                                                @endif
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>

                                    {{-- Student Rows --}}
                                    @forelse($students as $idx => $history)
                                        @php
                                            $student = $history->student;
                                            $sid = $student->id;
                                            $avgVal = $legerAggMap[$sid] ?? null;
                                            $rankVal = $rankMap[$sid] ?? null;
                                            $pres = $presensiMap[$sid] ?? null;
                                            $jumlahSts = 0; $countMapel = 0;
                                            foreach ($subjectMap as $subject) {
                                                $book = $bookMap[$subject->id] ?? null;
                                                if (!$book) continue;
                                                $n = $nilaiMap[$sid][$book->id] ?? null;
                                                $col = ($legerMode ?? 'sts') === 'sas' ? 'sas' : 'sts';
                                                if ($n && $n->{$col} !== null) {
                                                    $jumlahSts += $n->{$col};
                                                    $countMapel++;
                                                }
                                            }
                                            if ($avgVal === null) $predikat = '—';
                                            elseif ($avgVal >= 95) $predikat = "Mumtaz Murtafi'";
                                            elseif ($avgVal >= 90) $predikat = 'Mumtaz';
                                            elseif ($avgVal >= 85) $predikat = 'Jayyid Jiddan';
                                            elseif ($avgVal >= 80) $predikat = 'Jayyid';
                                            elseif ($avgVal >= 75) $predikat = 'Maqbul';
                                            else $predikat = 'Roosib';
                                        @endphp
                                        <tr data-name="{{ strtolower($student->name) }}">
                                            <td class="text-center text-muted" data-col="no">{{ $idx + 1 }}</td>
                                            <td class="text-center" data-col="nis">{{ $student->nis ?? '-' }}</td>
                                            <td class="text-start" style="white-space:nowrap;" data-col="name">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-xs">
                                                        <span class="avatar-title rounded-circle bg-primary text-white">
                                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold">{{ $student->name }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            @if(($legerMode ?? 'sts') === 'sas')
                                                @php
                                                    $pembRow = $pembiasaanMap[$sid] ?? null;
                                                    $tjuzRow = $tahfidzJuzMap[$sid] ?? null;
                                                    $hqRow = $halaqohMap[$sid] ?? null;
                                                @endphp
                                                <td class="text-center" style="background:var(--bs-warning-bg-subtle);" data-col="doa">{{ $pembRow && $pembRow->skor_doa !== null ? number_format($pembRow->skor_doa, 1) : '—' }}</td>
                                                <td class="text-center" style="background:var(--bs-warning-bg-subtle);" data-col="hiwar">{{ $pembRow && $pembRow->skor_hiwar !== null ? number_format($pembRow->skor_hiwar, 1) : '—' }}</td>
                                                <td class="text-center" style="background:var(--bs-warning-bg-subtle);" data-col="conv">{{ $pembRow && $pembRow->skor_conversation !== null ? number_format($pembRow->skor_conversation, 1) : '—' }}</td>
                                                <td class="text-center" style="background:var(--bs-primary-bg-subtle);" data-col="juz">{{ $tjuzRow?->juz_number ?? '—' }}</td>
                                                <td class="text-center" style="background:var(--bs-primary-bg-subtle);" data-col="halaqoh">{{ $hqRow?->name ?? '—' }}</td>
                                            @endif
                                            @foreach($subjectMap as $subject)
                                                @php
                                                    $book = $bookMap[$subject->id] ?? null;
                                                    $n = $book ? ($nilaiMap[$student->id][$book->id] ?? null) : null;
                                                    $kkm = $book?->kktp?->kkm_score ?? 75;
                                                    $stsVal = $n ? (($legerMode ?? 'sts') === 'sas' ? $n->sas : $n->sts) : null;
                                                    $belowKkm = $stsVal !== null && $stsVal < $kkm;
                                                @endphp
                                                <td style="text-align:center;" data-col="subject-{{ $subject->id }}">
                                                    @if($book)
                                                        <span class="fw-semibold{{ $belowKkm ? ' text-danger' : '' }}">
                                                            {{ $stsVal !== null ? number_format($stsVal, 0, '.', '') : '---' }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            {{-- Jumlah --}}
                                            <td class="text-center fw-semibold" style="color:var(--bs-body-color);" data-col="total">
                                                {{ $jumlahSts > 0 ? number_format($jumlahSts, 1) : '—' }}
                                            </td>
                                            {{-- Rata-rata --}}
                                            <td class="text-center fw-bold" style="color:var(--bs-primary);" data-col="avg">
                                                {{ $avgVal !== null ? number_format($avgVal, 1) : '—' }}
                                            </td>
                                            {{-- Peringkat --}}
                                            <td class="text-center" data-col="rank">
                                                @if($rankVal)
                                                    <span class="badge {{ $rankVal == 1 ? 'bg-warning text-dark' : 'bg-secondary-subtle text-secondary' }}"
                                                          style="min-width:32px;">{{ $rankVal }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            {{-- Predikat --}}
                                            <td class="text-center" data-col="pred">
                                                <span class="badge fw-bold
                                                    @if($avgVal === null) bg-light text-muted
                                                    @elseif($avgVal >= 95) bg-success text-white
                                                    @elseif($avgVal >= 90) bg-primary text-white
                                                    @elseif($avgVal >= 85) bg-info text-white
                                                    @elseif($avgVal >= 80) bg-warning text-dark
                                                    @elseif($avgVal >= 75) bg-secondary text-white
                                                    @else bg-danger text-white
                                                    @endif"
                                                    style="min-width:130px;padding:4px 6px;white-space:nowrap;">{{ $predikat }}</span>
                                            </td>
                                            {{-- PRESENSI S --}}
                                            <td class="text-center fw-semibold bg-success-subtle" style="color:var(--bs-success-text-emphasis);" data-col="s">
                                                {{ $pres['s'] ?? '—' }}
                                            </td>
                                            {{-- PRESENSI I --}}
                                            <td class="text-center fw-semibold bg-warning-subtle" style="color:var(--bs-warning-text-emphasis);" data-col="i">
                                                {{ $pres['i'] ?? '—' }}
                                            </td>
                                            {{-- PRESENSI A --}}
                                            <td class="text-center fw-semibold bg-danger-subtle" style="color:var(--bs-danger-text-emphasis);" data-col="a">
                                                {{ $pres['a'] ?? '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $subjectMap->count() + 10 + ((($legerMode ?? 'sts') === 'sas') ? 5 : 0) }}" class="text-center text-muted py-4">
                                                <i class="ri-group-line me-1"></i>Tidak ada Santri.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer border-top-dashed px-4 py-3">
                            <small class="text-muted" style="font-size:12px;">
                                <i class="ri-information-line me-1"></i>
                                Leger bersifat <strong>baca-saja</strong> — nilai diambil otomatis dari <strong>tab Mapel</strong>. Input nilai (termasuk Nilai Murni STS/SAS) dilakukan di tab Mapel. Nilai Murni wajib diisi sebelum kolom STS/SAS.
                            </small>
                        </div>
                    </form>
                @endif
            @endif

            {{-- ========================================== --}}
            {{-- TAB: MAPEL --}}
            {{-- ========================================== --}}
            @if($activeTab === 'mapel' && $activeBook)
                <div class="px-4 py-3" style="background:var(--bs-tertiary-bg);border-bottom:1px solid var(--bs-border-color);">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white" style="font-size:12px;">
                                <i class="ri-book-line me-1"></i>{{ $activeBook->subject->name }}
                            </span>
                            <span class="badge bg-secondary-subtle text-secondary text-capitalize">
                                {{ $activeBook->semester }}
                            </span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#kolomModal" style="font-size:12px;">
                                <i class="ri-settings-3-line me-1"></i>Kelola Kolom
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2" style="font-size:12px;">
                            <span class="badge bg-primary-subtle text-primary">
                                <i class="ri-bar-chart-line me-1"></i>RS = Rata-rata S1–S6 (50%)
                            </span>
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="ri-edit-2-line me-1"></i>STS (25%)
                            </span>
                            <span class="badge bg-info-subtle text-info">
                                <i class="ri-file-list-3-line me-1"></i>RSA / NR Murni / NR Final (otomatis)
                            </span>
                            <span class="badge bg-success-subtle text-success">
                                <i class="ri-award-line me-1"></i>Penghargaan &amp; Formatif (read-only)
                            </span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('user.schools.nilai-kelas.sts.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}" id="formMapel">
                    @csrf
                    <input type="hidden" name="admin_book_id" value="{{ $activeBook->id }}">
                    <input type="hidden" name="tab" value="mapel">

                    <div class="table-responsive leger-scroll">
                        <table id="tab-mapel" class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th rowspan="2" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="no">No</th>
                                    <th rowspan="2" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="nis">NIS</th>
                                    <th rowspan="2" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="name">
                                        <i class="ri-user-line me-1"></i>Nama Santri
                                    </th>
                                    <th colspan="{{ count($columns) }}">Asesmen Sumatif Harian</th>
                                    <th rowspan="2" class="align-middle bg-primary-subtle" width="80" data-sort="rs">
                                        RS<br><small class="fw-normal">({{ $activeBook?->nr_final_weight_rs ?? 50 }}%)</small>
                                    </th>
                                    <th rowspan="2" class="align-middle bg-warning-subtle" width="80" data-sort="sts-murni">
                                        N. Murni<br><small class="fw-normal">STS</small>
                                    </th>
                                    <th rowspan="2" class="align-middle bg-warning-subtle" width="80" data-sort="sts">
                                        STS<br><small class="fw-normal">({{ $activeBook?->nr_final_weight_sts ?? 25 }}%)</small>
                                    </th>
                                    <th rowspan="2" class="align-middle bg-info-subtle" width="80" data-sort="sas-murni">
                                        N. Murni<br><small class="fw-normal">SAS</small>
                                    </th>
                                    <th rowspan="2" class="align-middle bg-info-subtle" width="80" data-sort="sas">
                                        SAS<br><small class="fw-normal">(25%)</small>
                                    </th>
                                    <th rowspan="2" class="align-middle bg-info-subtle" width="80">RSA</th>
                                    <th rowspan="2" class="align-middle bg-info-subtle" width="80">NR<br>Murni</th>
                                    <th colspan="6" class="align-middle bg-success-subtle">Penghargaan Akademik</th>
                                    <th rowspan="2" class="align-middle bg-success-subtle" width="80">NR Final</th>
                                    <th colspan="4" class="align-middle bg-secondary-subtle">Penilaian Asesmen Formatif</th>
                                    <th rowspan="2" class="align-middle bg-secondary-subtle" width="80">RATA-<br>RATA</th>
                                </tr>
                                <tr>
                                    @foreach($columns as $col)
                                        @php
                                            $colLabel = (string) $col['label'];
                                            $colW = max(56, min(150, mb_strlen($colLabel) * 7 + 20));
                                        @endphp
                                        <th title="{{ $col['legacy'] ? 'Kolom legacy (S1–S6)' : 'Kolom tambahan' }} — {{ $colLabel }}"
                                            data-sort="sh-{{ $col['id'] }}"
                                            style="width:{{ $colW }}px;min-width:{{ $colW }}px;white-space:nowrap;">{{ $colLabel }}</th>
                                    @endforeach
                                    <th width="52">Jujur</th>
                                    <th width="52">Disiplin</th>
                                    <th width="52">Peduli</th>
                                    <th width="52">Adab</th>
                                    <th width="52">Kehadiran</th>
                                    <th width="52">Keaktifan</th>
                                    <th width="52">LKPD</th>
                                    <th width="52">Diskusi</th>
                                    <th width="52">Kuis</th>
                                    <th width="52">Antar<br>Teman</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $i => $history)
                                    @php $student = $history->student; @endphp
                                    @php $existing = $nilaiMap[$student->id][$activeBook->id] ?? null; @endphp
                                    <tr>
                                        <td class="text-center text-muted fw-semibold" data-col="no">{{ $i + 1 }}</td>
                                        <td class="text-center" data-col="nis">{{ $student->nis ?? '-' }}</td>
                                        <td class="text-start" data-col="name">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-primary text-white">
                                                        {{ strtoupper(substr($student->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <span class="fw-medium">{{ $student->name }}</span>
                                            </div>
                                        </td>
                                        @foreach($columns as $col)
                                            @php $shVal = $shMap[$student->id][$col['id']] ?? null; @endphp
                                            @if($col['id'] === 's6')
                                                @php $fmtS6 = $formatifMap[$student->id] ?? null; @endphp
                                                <td class="p-0" style="text-align:center;" data-col="sh-s6">
                                                    <span id="s6-{{ $student->id }}"
                                                          data-rs-extra="{{ $fmtS6?->nr_final !== null ? (float) $fmtS6->nr_final : '' }}"
                                                          class="d-inline-block w-100 py-1 fw-semibold"
                                                          title="S6 otomatis = rata-rata Penilaian Asesmen Formatif">
                                                        {{ $fmtS6?->nr_final !== null ? number_format($fmtS6->nr_final, 1) : '—' }}
                                                    </span>
                                                </td>
                                            @else
                                            <td class="p-0" style="text-align:center;" data-col="sh-{{ $col['id'] }}">
                                                <input type="number" class="sumatif-input sh-input sheet-input"
                                                       name="nilai[{{ $student->id }}][sh][{{ $col['id'] }}]"
                                                       value="{{ $shVal !== null ? $shVal : '' }}"
                                                       placeholder="---"
                                                       min="0" max="100" step="0.01"
                                                       oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;">
                                            </td>
                                            @endif
                                        @endforeach
                                        <td class="text-center" data-col="rs">
                                            <span id="rs-{{ $student->id }}"
                                                  class="badge bg-primary-subtle text-primary fw-bold rs-display {{ $existing?->rs ? '' : 'bg-light text-muted' }}"
                                                  style="padding:5px 10px;min-width:60px;">
                                                {{ $existing?->rs ? number_format($existing->rs, 1) : '-' }}
                                            </span>
                                        </td>
                                        <td class="p-0" data-col="sts-murni">
                                            <input type="number" class="sheet-input"
                                                   data-key="sts-murni"
                                                   name="nilai[{{ $student->id }}][sts_murni]"
                                                   value="{{ $existing?->sts_murni ?? '' }}"
                                                   oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                   min="0" max="100" step="0.01" placeholder="---">
                                        </td>
                                        <td class="p-0" data-col="sts">
                                            <input type="number" class="sheet-input sts-input js-gated"
                                                   data-requires="sts-murni"
                                                   name="nilai[{{ $student->id }}][sts]"
                                                   value="{{ $existing?->sts ?? '' }}"
                                                   oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                   min="0" max="100" step="0.01" placeholder="---"
                                                   @if($existing?->sts_murni === null) readonly @endif>
                                        </td>
                                        @php $ph = $penghargaanMap[$student->id] ?? null; @endphp
                                        <td class="p-0" data-col="sas-murni">
                                            <input type="number" class="sheet-input"
                                                   data-key="sas-murni"
                                                   name="nilai[{{ $student->id }}][sas_murni]"
                                                   value="{{ $existing?->sas_murni ?? '' }}"
                                                   oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                   min="0" max="100" step="0.01" placeholder="---">
                                        </td>
                                        <td class="p-0" data-col="sas">
                                            <input type="number" class="sheet-input sas-input js-gated"
                                                   data-requires="sas-murni"
                                                   name="nilai[{{ $student->id }}][sas]"
                                                   value="{{ $existing?->sas ?? '' }}"
                                                   oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                   min="0" max="100" step="0.01" placeholder="---"
                                                   @if($existing?->sas_murni === null) readonly @endif>
                                        </td>
                                        <td class="text-center" id="rsa-{{ $student->id }}">{{ $existing?->rsa !== null ? number_format($existing->rsa, 1) : '---' }}</td>
                                        <td class="text-center" id="nrm-{{ $student->id }}">{{ $existing?->nr_murni !== null ? number_format($existing->nr_murni, 1) : '---' }}</td>
                                        <td class="text-center">{{ $ph?->jujur ?? '---' }}</td>
                                        <td class="text-center">{{ $ph?->disiplin ?? '---' }}</td>
                                        <td class="text-center">{{ $ph?->peduli ?? '---' }}</td>
                                        <td class="text-center">{{ $ph?->adab ?? '---' }}</td>
                                        <td class="text-center">{{ $ph?->kehadiran ?? '---' }}</td>
                                        <td class="text-center">{{ $ph?->keaktifan ?? '---' }}</td>
                                        <td class="text-center fw-bold" id="nrf-{{ $student->id }}">{{ $existing?->nr_final !== null ? number_format($existing->nr_final, 1) : '---' }}</td>
                                        @php
                                            $fm = $formatifMap[$student->id] ?? null;
                                            $fmVals = $fm ? array_filter([$fm->skor_lkpd, $fm->skor_diskusi, $fm->skor_kuis, $fm->skor_antarteman], fn ($v) => $v !== null) : [];
                                            $fmAvg = count($fmVals) > 0 ? array_sum($fmVals) / count($fmVals) : null;
                                        @endphp
                                        <td class="text-center">{{ $fm?->skor_lkpd ?? '---' }}</td>
                                        <td class="text-center">{{ $fm?->skor_diskusi ?? '---' }}</td>
                                        <td class="text-center">{{ $fm?->skor_kuis ?? '---' }}</td>
                                        <td class="text-center">{{ $fm?->skor_antarteman ?? '---' }}</td>
                                        <td class="text-center fw-semibold">{{ $fmAvg !== null ? number_format($fmAvg, 1) : '---' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 20 + count($columns) }}" class="text-center text-muted py-4">
                                            <i class="ri-group-line me-1"></i>Tidak ada Santri.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer border-top-dashed px-4 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3" style="font-size:12px;">
                                <span class="badge bg-primary-subtle text-primary">RS</span>
                                <span class="text-muted">= Rata-rata Asesmen Harian × 50%</span>
                                <span class="badge bg-warning-subtle text-warning">STS</span>
                                <span class="text-muted">= Sumatif Tengah Semester × 25%. Tersimpan otomatis saat mengetik.</span>
                            </div>
                        </div>
                    </div>
                </form>
            @endif

            {{-- Tab mapel belum dipilih --}}
            @if($activeTab === 'mapel' && !$activeBook)
                <div class="text-center py-5">
                    <div class="avatar-lg mx-auto mb-3">
                        <div class="avatar-title bg-light rounded-circle">
                            <i class="ri-book-line fs-1 text-muted"></i>
                        </div>
                    </div>
                    <h6 class="text-muted">Pilih mata pelajaran</h6>
                    <p class="text-muted mb-0" style="font-size:12px;">Klik tab mapel di atas untuk mulai input nilai.</p>
                </div>
            @endif

        </div>
    </div>
    {{-- Modal: peringatan Nilai Murni (template lord-icon / zoomIn) --}}
    <div class="modal fade zoomIn" id="murniModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#005981" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Nilai Murni Belum Diisi</h4>
                            <p class="text-muted mx-4 mb-0" id="murniModalBody">
                                Isi <strong>Nilai Murni</strong> (nilai hasil ujian, tanpa elemen lain) terlebih dahulu sebelum mengisi kolom STS/SAS.
                            </p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn w-sm btn-primary" id="murniModalFocus">Isi Nilai Murni Sekarang</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

{{-- Modal: Kelola Kolom Sumatif Harian --}}
    <div class="modal fade" id="kolomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="ri-layout-column-line me-1"></i>Kelola Kolom Sumatif Harian</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:12px;">
                        Kolom <strong>S1–S5 (legacy)</strong> wajib dipertahankan; <strong>S6 otomatis</strong> dari rata-rata Penilaian Asesmen Formatif.
                        Kolom baru dapat ditambah, diganti nama, diurutkan, dan dihapus.
                    </p>
                    <button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="kolomAdd()">
                        <i class="ri-add-line me-1"></i>Tambah Kolom
                    </button>
                    <div id="kolom-list"></div>
                </div>
                <div class="modal-footer">
                    <span id="kolom-status" class="me-auto text-muted" style="font-size:12px;"></span>
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="kolomSave()">
                        <i class="ri-save-line me-1"></i>Simpan Kolom
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<style>
    /* ── Scroll & header beku ── */
    .leger-scroll { max-height: 72vh; overflow: auto; }
    .leger-scroll thead th { position: sticky; top: 0; z-index: 30; background: var(--bs-tertiary-bg); }

    /* ── Kolom identitas: SERAGAM di kedua tab; NAMA tanpa batas maksimum ── */
    #tab-leger th[data-sort="no"], #tab-leger td[data-col="no"],
    #tab-mapel th[data-sort="no"], #tab-mapel td[data-col="no"] { width: 50px; min-width: 50px; max-width: 50px; }
    #tab-leger th[data-sort="nis"], #tab-leger td[data-col="nis"],
    #tab-mapel th[data-sort="nis"], #tab-mapel td[data-col="nis"] { width: 84px; min-width: 84px; max-width: 84px; }
    #tab-leger th[data-sort="name"], #tab-leger td[data-col="name"],
    #tab-mapel th[data-sort="name"], #tab-mapel td[data-col="name"] { min-width: 220px; width: auto; max-width: none; white-space: nowrap; }

    /* HANYA sel body yang di-freeze; header identitas tetap memakai aturan thead (z-index 40) */
    #tab-leger td[data-col="no"], #tab-leger td[data-col="nis"], #tab-leger td[data-col="name"],
    #tab-mapel td[data-col="no"], #tab-mapel td[data-col="nis"], #tab-mapel td[data-col="name"] {
        position: sticky; z-index: 10; background: var(--bs-body-bg);
    }
    /* Header identitas TIDAK boleh ditimpa aturan ID: kunci lapisannya */
    #tab-leger thead th[data-sort="no"], #tab-leger thead th[data-sort="nis"], #tab-leger thead th[data-sort="name"],
    #tab-mapel thead th[data-sort="no"], #tab-mapel thead th[data-sort="nis"], #tab-mapel thead th[data-sort="name"] {
        position: sticky; z-index: 40; background: var(--bs-tertiary-bg);
    }
    /* Sel KKTP (kolom sejajar No/NIS/Nama) ikut beku horizontal */
    #tab-leger thead th[data-col="name"] {
        position: sticky; left: 0; z-index: 40; background: var(--bs-tertiary-bg);
    }
    #tab-leger th[data-sort="no"], #tab-leger td[data-col="no"],
    #tab-mapel th[data-sort="no"], #tab-mapel td[data-col="no"] { left: 0; }
    #tab-leger th[data-sort="nis"], #tab-leger td[data-col="nis"],
    #tab-mapel th[data-sort="nis"], #tab-mapel td[data-col="nis"] { left: 50px; }
    #tab-leger th[data-sort="name"], #tab-leger td[data-col="name"],
    #tab-mapel th[data-sort="name"], #tab-mapel td[data-col="name"] { left: 134px; }
    .leger-scroll thead th[data-sort="no"], .leger-scroll thead th[data-sort="nis"], .leger-scroll thead th[data-sort="name"] { z-index: 40; }

    /* Layar kecil (mobile/tablet): kolom beku dimatikan agar tabel bisa digeser ke samping */
    @media (max-width: 991.98px) {
        #tab-leger td[data-col="no"], #tab-leger td[data-col="nis"], #tab-leger td[data-col="name"],
        #tab-mapel td[data-col="no"], #tab-mapel td[data-col="nis"], #tab-mapel td[data-col="name"],
        #tab-leger thead th[data-sort="no"], #tab-leger thead th[data-sort="nis"], #tab-leger thead th[data-sort="name"],
        #tab-mapel thead th[data-sort="no"], #tab-mapel thead th[data-sort="nis"], #tab-mapel thead th[data-sort="name"],
        #tab-leger thead th[data-col="name"] {
            left: auto !important;
            z-index: auto !important;
        }
    }

    /* ── Lebar kolom konsisten ── */
    #tab-leger th[data-sort^="subject-"], #tab-leger td[data-col^="subject-"] { width: 92px; min-width: 92px; max-width: 92px; }
    #tab-leger th[data-sort="doa"], #tab-leger td[data-col="doa"] { width: 46px; min-width: 46px; max-width: 46px; }
    #tab-leger th[data-sort="hiwar"], #tab-leger td[data-col="hiwar"] { width: 50px; min-width: 50px; max-width: 50px; }
    #tab-leger th[data-sort="conv"], #tab-leger td[data-col="conv"] { width: 84px; min-width: 84px; max-width: 84px; }
    #tab-leger th[data-sort="juz"], #tab-leger td[data-col="juz"] { width: 46px; min-width: 46px; max-width: 46px; }
    #tab-leger th[data-sort="halaqoh"], #tab-leger td[data-col="halaqoh"] { width: 84px; min-width: 84px; max-width: 84px; }
    #tab-leger th[data-sort="total"], #tab-leger td[data-col="total"] { width: 76px; min-width: 76px; max-width: 76px; }
    #tab-leger th[data-sort="avg"], #tab-leger td[data-col="avg"] { width: 82px; min-width: 82px; max-width: 82px; }
    #tab-leger th[data-sort="rank"], #tab-leger td[data-col="rank"] { width: 64px; min-width: 64px; max-width: 64px; }
    #tab-leger th[data-sort="pred"], #tab-leger td[data-col="pred"] { width: 150px; min-width: 150px; max-width: 150px; }
    #tab-leger th[data-sort="s"], #tab-leger td[data-col="s"],
    #tab-leger th[data-sort="i"], #tab-leger td[data-col="i"],
    #tab-leger th[data-sort="a"], #tab-leger td[data-col="a"] { width: 42px; min-width: 42px; max-width: 42px; }
    #tab-mapel th[data-sort^="sh-"], #tab-mapel td[data-col^="sh-"] { min-width: 56px; }

    /* ── Input nilai ── */
    #tab-leger input[type="number"],
    #tab-mapel input[type="number"] { text-align: center; text-align-last: center; }
    #tab-leger input[type="number"]::placeholder,
    #tab-mapel input[type="number"]::placeholder { text-align: center; opacity: .45; }
    /* Gaya spreadsheet: tanpa border (border dari tabel); fokus hanya latar halus */
    #tab-leger input.leger-input {
        width: 52px; height: 27px; border: 0; border-radius: 0;
        background: transparent; color: var(--bs-body-color);
        font-weight: 600; font-size: 12px;
    }
    #tab-leger input.leger-input.is-below { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
    #tab-leger input.leger-input:focus { border: 0; box-shadow: none; outline: none; background: rgba(var(--bs-primary-rgb), .06); }
    /* Tidak ada border/ring saat input diklik (border dari tabel) */
    #tab-leger input:focus, #tab-mapel input:focus,
    #tab-leger input:focus-visible, #tab-mapel input:focus-visible {
        border-color: transparent !important; box-shadow: none !important; outline: 0 !important;
    }
    #tab-mapel input.sh-input {
        width: 40px; height: 26px; border: 0; border-radius: 0;
        background: transparent; color: var(--bs-body-color);
        font-size: 12px;
    }
    #tab-mapel input.sh-input:focus { border: 0; box-shadow: none; outline: none; background: rgba(var(--bs-primary-rgb), .06); }
    #tab-mapel input.sts-input { background: var(--bs-warning-bg-subtle); font-weight: 600; }
    #tab-mapel input.sts-input:focus { border: 0; box-shadow: none; outline: none; background: rgba(var(--bs-warning-rgb), .18); }

    /* ── Urutkan kolom ── */
    th[data-sort] { cursor: pointer; user-select: none; white-space: nowrap; }
    th[data-sort]:hover { filter: brightness(.96); }
    th[data-dir="asc"]::after { content: " ▲"; font-size: 9px; color: var(--bs-primary); }
    th[data-dir="desc"]::after { content: " ▼"; font-size: 9px; color: var(--bs-primary); }

    /* ── Baris ── */
    #tab-leger tbody tr, #tab-mapel tbody tr { transition: background-color .12s; }
    #tab-leger tbody tr:hover td, #tab-mapel tbody tr:hover td { background: rgba(var(--bs-primary-rgb), .05); }
    #tab-leger tbody tr:hover td[data-col="no"], #tab-leger tbody tr:hover td[data-col="nis"], #tab-leger tbody tr:hover td[data-col="name"],
    #tab-mapel tbody tr:hover td[data-col="no"], #tab-mapel tbody tr:hover td[data-col="nis"], #tab-mapel tbody tr:hover td[data-col="name"] { background: var(--bs-secondary-bg); }

    /* ── Tipografi seragam di tabel (12px) ── */
    #tab-leger, #tab-mapel { font-size: 12px; }
    #tab-leger thead th, #tab-mapel thead th { font-size: 12px; font-weight: 600; }
    #tab-leger thead th[colspan], #tab-mapel thead th[colspan] { font-size: 11px; letter-spacing: .4px; }
    #tab-leger thead th div, #tab-mapel thead th div { font-size: 11px; }
    #tab-leger thead th small, #tab-mapel thead th small { font-size: 10px; }
    #tab-leger tbody td, #tab-mapel tbody td { font-size: 12px; }
    #tab-leger .badge, #tab-mapel .badge { font-size: 11px; }
    #tab-leger .avatar-title, #tab-mapel .avatar-title { font-size: 10px; }

    /* ── Gaya spreadsheet: input tanpa border, penuh kolom ── */
    #tab-mapel td.p-0 { padding: 0; }
    #tab-mapel input.sheet-input {
        width: 100%; height: 30px; padding: 0 4px;
        border: 0; border-radius: 0; box-shadow: none;
        background: transparent; color: var(--bs-body-color);
        font-size: 12px; text-align: center;
        transition: box-shadow .1s, background-color .1s;
    }
    #tab-mapel input.sheet-input:focus { outline: none; border: 0; box-shadow: none; background: rgba(var(--bs-primary-rgb), .06); }
    #tab-mapel input.sheet-input:disabled,
    #tab-mapel input.sheet-input[readonly] { background: var(--bs-tertiary-bg); color: var(--bs-secondary-color); opacity: .75; }
    #tab-mapel input.sheet-input[readonly] { cursor: pointer; }
    #tab-leger th input.kktp-input {
        width: 100%; height: 26px; padding: 0 4px;
        border: 0; border-radius: 0; box-shadow: none;
        background: transparent; color: var(--bs-body-color);
        font-weight: 700; font-size: 12px; text-align: center;
    }
    #tab-leger th input.kktp-input:focus { border: 0; outline: none; box-shadow: none; background: rgba(var(--bs-primary-rgb), .06); }
</style>
<script>
function syncSearchInput(val) {
    document.getElementById('searchInputHidden').value = val;
    filterStudentRows(val);
}

function filterStudentRows(query) {
    var q = query.toLowerCase().trim();
    document.querySelectorAll('#tab-leger tbody tr[data-name]').forEach(function(row) {
        var name = row.getAttribute('data-name') || '';
        row.style.display = (q === '' || name.indexOf(q) !== -1) ? '' : 'none';
    });
}

/* ── Urutkan kolom: klik header ── */
function _cellVal(cell) {
    if (!cell) return null;
    var inp = cell.querySelector('input, select');
    var raw = (inp ? inp.value : cell.textContent).trim();
    if (raw === '' || raw === '—' || raw === '-' || raw === '---') return null;
    var n = parseFloat(raw.replace(',', '.'));
    return isNaN(n) ? raw.toLowerCase() : n;
}

/* ── Update sel turunan (RS/RSA/NR Murni/NR Final) dari hasil simpan ── */
function updateRowCells(studentId, data) {
    var set = function (prefix, val) {
        var el = document.getElementById(prefix + studentId);
        if (!el) return;
        el.textContent = (val === null || val === undefined || val === '') ? '-' : Number(val).toFixed(1);
    };
    if (data.rs !== undefined) set('rs-', data.rs);
    if (data.rsa !== undefined) set('rsa-', data.rsa);
    if (data.nr_murni !== undefined) set('nrm-', data.nr_murni);
    if (data.nr_final !== undefined) set('nrf-', data.nr_final);
}

/* ── Header multi-baris: hitung posisi sticky tiap baris ── */
function applyStickyHeaderTops(tableId) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var rows = table.querySelectorAll('thead tr');
    var top = 0;
    rows.forEach(function (tr) {
        var h = tr.getBoundingClientRect().height;
        tr.querySelectorAll('th').forEach(function (th) {
            th.style.top = top + 'px';
        });
        top += h;
    });
}

function initSortableTable(tableId) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var heads = table.querySelectorAll('thead th[data-sort]');
    heads.forEach(function (th) {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            var key = th.getAttribute('data-sort');
            var dir = th.getAttribute('data-dir') === 'asc' ? 'desc' : 'asc';
            heads.forEach(function (h) { h.removeAttribute('data-dir'); });
            th.setAttribute('data-dir', dir);

            var tbody = table.querySelector('tbody');
            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-name]'));
            rows.sort(function (a, b) {
                var va = _cellVal(a.querySelector('[data-col="' + key + '"]'));
                var vb = _cellVal(b.querySelector('[data-col="' + key + '"]'));
                if (va === null && vb === null) return 0;
                if (va === null) return 1;
                if (vb === null) return -1;
                if (typeof va === 'number' && typeof vb === 'number') {
                    return dir === 'asc' ? va - vb : vb - va;
                }
                return dir === 'asc'
                    ? String(va).localeCompare(String(vb))
                    : String(vb).localeCompare(String(va));
            });
            rows.forEach(function (r, i) {
                var noCell = r.querySelector('[data-col="no"]');
                if (noCell) noCell.textContent = i + 1;
                tbody.appendChild(r);
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Apply search filter on page load if query exists
    var initialQuery = document.getElementById('searchStudentInput').value;
    if (initialQuery) filterStudentRows(initialQuery);

    // Aktifkan urutkan kolom di kedua tabel
    initSortableTable('tab-leger');
    initSortableTable('tab-mapel');

    // Header beku multi-baris: posisi top tiap baris header
    applyStickyHeaderTops('tab-leger');
    applyStickyHeaderTops('tab-mapel');
    window.addEventListener('resize', function () {
        applyStickyHeaderTops('tab-leger');
        applyStickyHeaderTops('tab-mapel');
    });
    window.addEventListener('load', function () {
        applyStickyHeaderTops('tab-leger');
        applyStickyHeaderTops('tab-mapel');
    });

    // ── Spreadsheet: gating Nilai Murni, navigasi, dan paste (tab Mapel) ──
    (function () {
        var table = document.getElementById('tab-mapel');
        if (!table) return;

        function allInputs() {
            return Array.prototype.slice.call(table.querySelectorAll('tbody input.sheet-input'));
        }

        var firstRow = table.querySelector('tbody tr');
        var perRow = firstRow ? firstRow.querySelectorAll('input.sheet-input').length : 0;
        if (!perRow) return;

        function nextEnabled(start, step) {
            var a = allInputs();
            for (var i = start; i >= 0 && i < a.length; i += step) {
                if (!a[i].readOnly && !a[i].disabled) return a[i];
            }
            return null;
        }

        // Gating: kolom STS/SAS terkunci sampai Nilai Murni terisi (readonly → klik memunculkan modal)
        function refreshGate(inp) {
            var row = inp.closest('tr');
            var need = inp.getAttribute('data-requires');
            if (!row || !need) return;
            var src = row.querySelector('input[data-key="' + need + '"]');
            var has = src && String(src.value).trim() !== '';
            inp.readOnly = !has;
            inp.title = has ? '' : 'Isi ' + (need === 'sts-murni' ? 'Nilai Murni STS' : 'Nilai Murni SAS') + ' terlebih dahulu.';
        }

        // Klik pada kolom terkunci → modal peringatan
        table.addEventListener('click', function (e) {
            var inp = e.target;
            if (!inp.classList || !inp.classList.contains('js-gated') || !inp.readOnly) return;

            var need = inp.getAttribute('data-requires');
            var label = need === 'sts-murni' ? 'Nilai Murni STS' : 'Nilai Murni SAS';
            var kolom = need === 'sts-murni' ? 'STS' : 'SAS';
            var row = inp.closest('tr');
            var src = row ? row.querySelector('input[data-key="' + need + '"]') : null;

            var modalEl = document.getElementById('murniModal');
            if (!modalEl || !window.bootstrap) return;
            var body = document.getElementById('murniModalBody');
            if (body) {
                body.innerHTML = 'Kolom <strong>' + kolom + '</strong> belum dapat diisi. Isi <strong>' + label +
                    '</strong> (nilai hasil ujian, tanpa elemen lain) terlebih dahulu';
            }
            var focusBtn = document.getElementById('murniModalFocus');
            if (focusBtn) {
                focusBtn.onclick = function () {
                    if (src) { src.focus(); if (src.select) src.select(); }
                    var m = bootstrap.Modal.getInstance(modalEl);
                    if (m) m.hide();
                };
            }
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            inp.blur();
        });

        table.querySelectorAll('input.js-gated').forEach(refreshGate);
        table.addEventListener('input', function (e) {
            var key = e.target.getAttribute && e.target.getAttribute('data-key');
            if (key) {
                table.querySelectorAll('input[data-requires="' + key + '"]').forEach(refreshGate);
            }
        });

        // Navigasi: Enter / ↑ / ↓ pindah baris
        table.addEventListener('keydown', function (e) {
            if (!e.target.classList || !e.target.classList.contains('sheet-input')) return;
            var a = allInputs();
            var idx = a.indexOf(e.target);
            if (idx === -1) return;

            var target = null;
            if (e.key === 'Enter' || e.key === 'ArrowDown') target = nextEnabled(idx + perRow, perRow);
            else if (e.key === 'ArrowUp') target = nextEnabled(idx - perRow, -perRow);
            else return;

            if (target) {
                e.preventDefault();
                target.focus();
                if (target.select) target.select();
            }
        });

        // Paste matriks dari Excel/Sheets mulai dari sel yang difokuskan
        table.addEventListener('paste', function (e) {
            if (!e.target.classList || !e.target.classList.contains('sheet-input')) return;
            var text = (e.clipboardData || window.clipboardData).getData('text');
            if (!text || (text.indexOf('\t') === -1 && text.indexOf('\n') === -1)) return;

            e.preventDefault();
            var grid = text.replace(/\r/g, '').replace(/\n+$/, '').split('\n').map(function (r) { return r.split('\t'); });
            var a = allInputs();
            var start = a.indexOf(e.target);
            var row0 = Math.floor(start / perRow);
            var col0 = start % perRow;

            grid.forEach(function (rowVals, dr) {
                rowVals.forEach(function (val, dc) {
                    var target = a[(row0 + dr) * perRow + (col0 + dc)];
                    if (!target || target.readOnly || target.disabled) return;
                    target.value = String(val).trim().replace(',', '.');
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });
        });
    })();

    // ─── Auto-save for Leger & Mapel ─────────────────────────────
    (function() {
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        var indicatorDot = document.getElementById('autosave-dot');
        var indicatorText = document.getElementById('autosave-text');
        var debounceTimer = null;
        var saveInFlight = false;

        function setIndicator(cls, text) {
            indicatorDot.className = 'rounded-circle ' + cls;
            indicatorText.textContent = text;
        }

        function showSuccess() {
            var time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            setIndicator('bg-success', 'Tersimpan ' + time);
            setTimeout(function() { setIndicator('bg-secondary', 'Menunggu…'); }, 3000);
        }

        function showError(msg) {
            setIndicator('bg-danger', msg || 'Gagal menyimpan');
            setTimeout(function() { setIndicator('bg-secondary', 'Menunggu…'); }, 4000);
        }

        function getActiveForm() {
            var leger = document.getElementById('formLeger');
            var mapel = document.getElementById('formMapel');
            if (leger && leger.style.display !== 'none' && leger.style.visibility !== 'hidden') return leger;
            if (mapel && mapel.style.display !== 'none' && mapel.style.visibility !== 'hidden') return mapel;
            return leger || mapel;
        }

        function doSave() {
            if (saveInFlight) return;
            var form = getActiveForm();
            if (!form) return;

            var formData = new FormData(form);
            saveInFlight = true;
            setIndicator('bg-warning', 'Menyimpan…');

            fetch('{{ $autosaveUrl }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            })
            .then(function(res) {
                saveInFlight = false;
                var ct = res.headers.get('content-type') || '';
                if (!res.ok || ct.indexOf('application/json') === -1) {
                    return res.json().catch(function() {
                        return { message: 'Sesi berakhir — muat ulang halaman (Ctrl+Shift+R).' };
                    });
                }
                return res.json();
            })
            .then(function(data) {
                if (data && data.success === true) {
                    if (Array.isArray(data.saved_rows)) {
                        data.saved_rows.forEach(function (row) { updateRowCells(row.student_id, row); });
                    }
                    showSuccess();
                } else {
                    showError((data && data.message) ? data.message : 'Gagal menyimpan');
                }
            })
            .catch(function(err) {
                saveInFlight = false;
                showError('Gagal menyimpan');
            });
        }

        function triggerSave() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(doSave, 1500);
        }

        // Attach to all inputs in both forms
        [document.getElementById('formLeger'), document.getElementById('formMapel')].forEach(function(form) {
            if (form) {
                form.querySelectorAll('input').forEach(function(el) {
                    el.addEventListener('input', triggerSave);
                    el.addEventListener('change', triggerSave);
                });
            }
        });
    })();

    // Auto-calculate RS on input change
    document.querySelectorAll('.sumatif-input').forEach(function(input) {
        input.addEventListener('input', function () {
            var row = this.closest('tr');
            var inputs = row.querySelectorAll('.sumatif-input');
            var values = [];
            inputs.forEach(function(inp) {
                var v = parseFloat(inp.value);
                if (!isNaN(v) && inp.value !== '') values.push(v);
            });
            // S6 otomatis (rata-rata formatif) ikut dihitung
            var extra = row.querySelector('[data-rs-extra]');
            if (extra) {
                var ev = parseFloat(extra.getAttribute('data-rs-extra'));
                if (!isNaN(ev)) values.push(ev);
            }
            var rsDisplay = row.querySelector('.rs-display');
            if (values.length > 0) {
                var avg = values.reduce(function(a, b) { return a + b; }, 0) / values.length;
                rsDisplay.textContent = avg.toFixed(1);
                rsDisplay.classList.remove('bg-light', 'text-muted');
                rsDisplay.classList.add('bg-primary-subtle', 'text-primary', 'fw-bold');
            } else {
                rsDisplay.textContent = '-';
                rsDisplay.classList.add('bg-light', 'text-muted');
                rsDisplay.classList.remove('bg-primary-subtle', 'text-primary', 'fw-bold');
            }
        });
    });

    // Ctrl+S to save
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            var form = document.querySelector('#formMapel, #formLeger');
            if (form) form.submit();
        }
    });
});
</script>

<script>
// ─── Kelola Kolom Sumatif Harian (tambah/rename/hapus/urut) ─────
const kolomColumnsUrl = "{{ route('user.schools.nilai-kelas.sts.kolom', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'admin_book_id' => $activeBook?->id]) }}";
const kolomLegacyIds = @json(\App\Services\SumatifHarianService::LEGACY_IDS);
let kolomData = @json($columns ?? []);

function kolomIsLocked(id) { return kolomLegacyIds.includes(id) || id === 's6'; }

function kolomRender() {
    const wrap = document.getElementById('kolom-list');
    if (!wrap) return;

    wrap.innerHTML = kolomData.map(function (c, i) {
        const locked = kolomIsLocked(c.id);
        const safeLabel = String(c.label).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        const badge = c.id === 's6' ? 'Otomatis' : (kolomLegacyIds.includes(c.id) ? 'Legacy' : 'Baru');
        return '<div class="input-group input-group-sm mb-2">' +
            '<span class="input-group-text" style="width:90px;font-size:.7rem;">' + badge + '</span>' +
            '<input type="text" class="form-control kolom-label" maxlength="60" value="' + safeLabel + '" oninput="kolomData[' + i + '].label = this.value" ' + (c.id === 's6' ? 'disabled' : '') + '>' +
            '<button type="button" class="btn btn-outline-secondary" onclick="kolomMove(' + i + ', -1)" ' + (i === 0 ? 'disabled' : '') + '><i class="ri-arrow-up-line"></i></button>' +
            '<button type="button" class="btn btn-outline-secondary" onclick="kolomMove(' + i + ', 1)" ' + (i === kolomData.length - 1 ? 'disabled' : '') + '><i class="ri-arrow-down-line"></i></button>' +
            (locked
                ? '<button type="button" class="btn btn-outline-secondary" disabled title="Kolom ini tidak dapat dihapus"><i class="ri-lock-line"></i></button>'
                : '<button type="button" class="btn btn-outline-danger" onclick="kolomRemove(' + i + ')" title="Hapus kolom"><i class="ri-delete-bin-line"></i></button>') +
            '</div>';
    }).join('');
}

function kolomUuid() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

function kolomAdd() {
    kolomData.push({ id: kolomUuid(), label: 'Sumatif Baru', legacy: false });
    kolomRender();
}

function kolomRemove(i) {
    if (!confirm('Hapus kolom "' + kolomData[i].label + '"? Nilai pada kolom ini akan ikut terhapus dari semua siswa.')) return;
    kolomData.splice(i, 1);
    kolomRender();
}

function kolomMove(i, dir) {
    const j = i + dir;
    if (j < 0 || j >= kolomData.length) return;
    const tmp = kolomData[i];
    kolomData[i] = kolomData[j];
    kolomData[j] = tmp;
    kolomRender();
}

function kolomSave() {
    const status = document.getElementById('kolom-status');
    status.textContent = 'Menyimpan…';

    fetch(kolomColumnsUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify({ columns: kolomData }),
    })
    .then(res => res.ok ? res.json() : Promise.reject(res))
    .then(function (data) {
        if (data && data.success) {
            status.textContent = 'Tersimpan! Memuat ulang…';
            window.location.reload();
        } else {
            status.textContent = (data && data.message) ? data.message : 'Gagal menyimpan kolom.';
        }
    })
    .catch(function () {
        status.textContent = 'Gagal menyimpan kolom.';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    kolomRender();
});
</script>
@endsection