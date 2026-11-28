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
        // Roster efektif: pada TA aktif, kelas yang seluruh riwayatnya sudah
        // nonaktif tetap diperlakukan sebagai roster berjalan (kasus data migrasi).
        $fallbackRoster = ! ($isArchiveAy ?? false)
            && $students->isNotEmpty()
            && ! $students->contains(fn ($history) => $history->is_active);
        $autosaveUrl = route('user.schools.nilai-kelas.sts.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]);
        $presensiUrl = route('user.schools.nilai-kelas.presensi.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]);
        // Kolom nilai mapel tahfidz (tab Mapel): NPT, Ziyadah, Rabt, Tikrar, STS, SAS.
        $thfKeys = \App\Services\TahfidzGradebookCalculator::KEYS;
        $thfCols = \App\Services\TahfidzLegerColumns::COLUMNS;
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">Data Nilai</a> @endslot
        @slot('title') STS — {{ $studyGroup->name ?? '' }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error') && ! str_contains((string) session('error'), 'Publikasi dibatalkan'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-alert-line me-2"></i>{{ session('warning') }}
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
                                <small class="text-muted"><i class="ri-user-line me-1"></i>{{ $studyGroup->homeroomDisplayName() }}</small>
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

                    @if($isArchiveAy ?? false)
                    <span class="badge bg-warning-subtle text-warning" style="font-size:11px;padding:4px 10px;">
                        <i class="ri-archive-line align-bottom me-1"></i>Arsip {{ $selectedAy?->name }} — baca-saja
                    </span>
                    @endif
                </div>
                <div class="col-sm-auto">
                    {{-- Action Buttons --}}
                    @if(($activeTab ?? '') === 'mapel' && !empty($activeBook))
                        @php
                            $canPublishBook = ($isPrivileged ?? false) || (string) $activeBook->teacher_id === (string) auth()->id();
                        @endphp
                        @if($canPublishBook)
                            @if($activeBook->isPublished())
                                <span class="badge bg-success-subtle text-success me-1" style="font-size:11px;padding:6px 10px;">
                                    <i class="ri-eye-line me-1"></i>Dipublikasikan ke wali
                                </span>
                                <form method="POST" action="{{ route('user.schools.nilai-kelas.buku.unpublish', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'adminBookId' => $activeBook->id]) }}"
                                      class="d-inline" onsubmit="return confirm('Batalkan publikasi nilai mapel ini?')">
                                    @csrf
                                    <button class="btn btn-outline-danger btn-md me-1"><i class="ri-eye-off-line me-1"></i>Batalkan Publish</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('user.schools.nilai-kelas.buku.publish', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'adminBookId' => $activeBook->id]) }}"
                                      class="d-inline" onsubmit="return confirm('Publikasikan nilai {{ strtoupper($legerMode ?? 'sts') }} mapel ini ke wali santri?')">
                                    @csrf
                                    <input type="hidden" name="jenis" value="{{ $legerMode ?? 'sts' }}">
                                    <button class="btn btn-success btn-md me-1"><i class="ri-send-plane-line me-1"></i>Publish Nilai</button>
                                </form>
                            @endif
                        @endif
                    @endif
                    <div class="btn-group me-1">
                        <a href="{{ route('user.schools.nilai-kelas.rapor', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester]) }}"
                           class="btn btn-outline-primary btn-md">
                            <i class="ri-file-paper-2-line me-1"></i> Cetak Rapor
                        </a>
                        <button type="button" class="btn btn-outline-primary btn-md dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Pilih format rapor</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Rapor Massal — STS</h6></li>
                            <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester, 'jenis' => 'sts', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                            <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester, 'jenis' => 'sts', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                            <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester, 'jenis' => 'sts', 'bahasa' => 'id-ar']) }}">Bilingual (ID + AR)</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Rapor Massal — SAS</h6></li>
                            <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester, 'jenis' => 'sas', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                            <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester, 'jenis' => 'sas', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                            <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester, 'jenis' => 'sas', 'bahasa' => 'id-ar']) }}">Bilingual (ID + AR)</a></li>
                        </ul>
                    </div>
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

        @if(($activeTab ?? '') === 'mapel' && !empty($activeBook) && $activeBook->isPublished())
            <div class="alert alert-success border-0 rounded-0 mb-0 py-2">
                <i class="ri-eye-line me-1"></i>
                Nilai <strong>{{ $activeBook->subject?->name ?? 'mapel ini' }}</strong> sudah dipublikasikan ke wali santri.
                Perubahan nilai dinonaktifkan sampai publikasi dibatalkan.
            </div>
        @endif

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
                            <option value="{{ $ay->id }}" {{ $selectedAyId == $ay->id ? 'selected' : '' }}>{{ $ay->name }} ({{ $ay->semester_text }})</option>
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

        @if(! empty($periodNotice))
            <div class="alert alert-warning border-0 rounded-0 mb-0 d-flex align-items-center gap-2" style="font-size:12px;">
                <i class="ri-calendar-sync-line fs-16"></i>
                <span>{{ $periodNotice }}</span>
            </div>
        @endif

        @if(($subjectSource ?? 'admin_book') === 'rombel_assignment')
            <div class="alert alert-info border-0 rounded-0 mb-0 d-flex align-items-center gap-2" style="font-size:12px;">
                <i class="ri-information-line fs-16"></i>
                <span>Buku nilai belum tersedia untuk periode ini. Kolom mapel diambil dari <strong>struktur rombel</strong>; nilai akan muncul setelah guru mengisi buku nilai.</span>
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
                    <div class="px-4 pt-3 pb-1 d-flex flex-wrap justify-content-between align-items-center gap-2">
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

                        {{-- Filter kelompok mapel. Mapel "Pondok + Dinas" tampil di kedua
                             filter. Rata-rata, jumlah, peringkat, dan predikat dihitung
                             hanya dari mapel yang tampil. --}}
                        @php
                            $legerFilter = $legerFilter ?? 'semua';
                        @endphp
                        <div class="btn-group btn-group-sm" role="group" aria-label="Filter kelompok mapel leger">
                            @foreach(['semua' => 'Semua Mapel', 'pondok' => 'Mapel Pondok', 'dinas' => 'Mapel Dinas'] as $filterKey => $filterLabel)
                                <a href="{{ route('user.schools.nilai-kelas.sts', array_merge(['userId' => $userId, 'studyGroupId' => $studyGroup->id], request()->except('sumber'), ['tab' => 'leger', 'leger' => ($legerMode ?? 'sts'), 'sumber' => $filterKey])) }}"
                                   class="btn {{ $legerFilter === $filterKey ? 'btn-primary' : 'btn-outline-primary' }}"
                                   data-leger-filter="{{ $filterKey }}">
                                    {{ $filterLabel }}
                                </a>
                            @endforeach
                        </div>

                        @if($legerFilter !== 'semua' && ($legerSubjectMap ?? $subjectMap)->isEmpty())
                            <div class="alert alert-warning d-flex align-items-start gap-2 py-2 px-3 mt-2 mb-0 small" role="alert">
                                <i class="ri-error-warning-line mt-1"></i>
                                <div>
                                    Belum ada mapel yang ditandai
                                    <strong>{{ $legerFilter === 'pondok' ? 'Pondok' : 'Dinas' }}</strong>
                                    untuk tingkat <strong>{{ $studyGroup->gradeLevel?->name ?? 'ini' }}</strong>,
                                    sehingga tidak ada mapel yang bisa ditampilkan.
                                    Atur kolom <strong>Sumber Mapel</strong> di
                                    <a href="{{ route('user.kurikulum.index', ['userId' => $userId, 'grade_level_id' => $studyGroup->grade_level_id, 'academic_year_id' => $selectedAyId]) }}">Peta Kurikulum</a>
                                    agar filter ini terisi.
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Catatan rapor per predikat (dipakai bila catatan wali kosong) --}}
                    <div class="px-4 pt-2 mb-2">
                        <details class="predicate-notes-box">
                            <summary class="d-flex align-items-center gap-2">
                                <i class="ri-chat-quote-line"></i>
                                <span class="fw-semibold">Catatan Rapor per Predikat</span>
                                <span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">
                                    {{ count($predicateNotes ?? []) }} terisi
                                </span>
                                <span class="text-muted ms-auto" style="font-size:11px;">Klik untuk buka</span>
                            </summary>
                            <form method="POST"
                                  action="{{ route('user.schools.nilai-kelas.sts.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                                  class="mt-2">
                                @csrf
                                <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                                <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                                <input type="hidden" name="tab" value="leger">
                                <input type="hidden" name="leger_mode" value="{{ $legerMode ?? 'sts' }}">

                                <div class="row g-2">
                                    @foreach(\App\Models\RaporPredicateNote::PREDICATES as $predicate)
                                        <div class="col-md-6 col-xl-4">
                                            <label class="form-label mb-1" style="font-size:11px;font-weight:600;">{{ $predicate }}</label>
                                            <textarea name="predicate_notes[{{ $predicate }}]" rows="2" maxlength="500"
                                                      class="form-control form-control-sm mb-1"
                                                      style="font-size:12px;"
                                                      placeholder="Catatan Indonesia untuk predikat {{ $predicate }} — gunakan {santri} untuk nama santri">{{ $predicateNotes[$predicate] ?? '' }}</textarea>
                                            <textarea name="predicate_notes_ar[{{ $predicate }}]" rows="2" maxlength="500" dir="rtl"
                                                      class="form-control form-control-sm"
                                                      style="font-size:12px;font-family:inherit;"
                                                      placeholder="الملاحظة العربية — استخدم {santri} لاسم الطالب">{{ $predicateNotesAr[$predicate] ?? '' }}</textarea>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 mb-1">
                                    <small class="text-muted" style="font-size:11px;">
                                        <i class="ri-information-line me-1"></i>
                                        Catatan ini tercetak pada rapor santri yang predikatnya sesuai, hanya bila catatan wali kelas masih kosong.
                                        Gunakan <strong>{santri}</strong> untuk menyebut nama santri; teks Arab dipakai saat mencetak rapor Arab.
                                    </small>
                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                        <i class="ri-save-3-line me-1"></i>Simpan Catatan Predikat
                                    </button>
                                </div>
                            </form>
                        </details>
                    </div>

                    <form method="POST" action="{{ route('user.schools.nilai-kelas.sts.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}" id="formLeger">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                        <input type="hidden" name="tab" value="leger">
                        <input type="hidden" name="leger_mode" value="{{ $legerMode ?? 'sts' }}">

                        <div class="table-responsive leger-scroll">
                            <table id="tab-leger" class="table table-bordered table-hover align-middle mb-0" style="min-width: max-content;">
                                <thead class="table-light text-center">
                                    @php
                                        $isSasLeger = ($legerMode ?? 'sts') === 'sas';
                                        // Mapel tahfidz tampil seperti mapel umum: satu kolom
                                        // nilai (NR Final). Rincian NPT/Ziyadah/Rabt/Tikrar/STS/SAS
                                        // ada di tab Mapel, bukan di leger.
                                        $legerSubjectCols = fn ($subject) => $isSasLeger ? 2 : 1;
                                        // Grup AGAMA/UMUM mengikuti filter sumber: hitungannya
                                        // diambil dari controller agar colspan tidak melenceng.
                                        $agamaCount = $legerAgamaCount ?? ($agamaCount ?? 0);
                                        $umumCount = ($legerSubjectMap ?? $subjectMap)->count() - $agamaCount;
                                        // Rencana kolom eksplisit — tidak ada span yang boleh tumpang tindih.
                                        // Baris: 1 grup · 2 nama mapel + S/I/A · 3 N/Jumlah (SAS) · 4 KKTP · 5 Bobot (SAS)
                                        $tailRowCount = $isSasLeger ? 2 : 1;            // baris KKTP (+ Bobot untuk SAS)
                                        $identitySpan = $isSasLeger ? 3 : 2;            // No/NIS/Nama: STS s/d baris 2, SAS s/d baris 3
                                        $headerTailSpan = $isSasLeger ? 5 : 2;          // Jumlah s/d Predikat: STS baris 2-3, SAS baris 1-5
                                    @endphp
                                    {{-- Baris 1: grup kolom --}}
                                    <tr>
                                        <th rowspan="{{ $identitySpan }}" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="no">No</th>
                                        <th rowspan="{{ $identitySpan }}" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="nis">NIS</th>
                                        <th rowspan="{{ $identitySpan }}" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="name">
                                            <i class="ri-user-line me-1"></i>Nama Santri
                                        </th>
                                        @if($isSasLeger)
                                            <th colspan="3" class="align-middle" style="background:var(--bs-warning-bg-subtle);color:var(--bs-warning-text-emphasis);letter-spacing:0.4px;">PEMBIASAAN PAGI</th>
                                            <th colspan="3" class="align-middle" style="background:var(--bs-primary-bg-subtle);color:var(--bs-primary-text-emphasis);letter-spacing:0.4px;">TAHFIDZ</th>
                                        @endif
                                        @if(($agamaCount ?? 0) > 0)
                                            <th colspan="{{ collect(($legerSubjectMap ?? $subjectMap)->take($agamaCount))->sum($legerSubjectCols) }}" class="align-middle bg-success-subtle text-success" style="letter-spacing:0.5px;">AGAMA</th>
                                        @endif
                                        @if($umumCount > 0)
                                            <th colspan="{{ collect(($legerSubjectMap ?? $subjectMap)->slice($agamaCount))->sum($legerSubjectCols) }}" class="align-middle bg-primary-subtle text-primary" style="letter-spacing:0.5px;">UMUM</th>
                                        @endif
                                        <th rowspan="{{ $headerTailSpan }}" style="background:var(--bs-secondary-bg);" class="text-dark align-middle" data-sort="total" title="Jumlah = Σ (Nilai × Bobot)">Jumlah</th>
                                        @if($isSasLeger)
                                            <th rowspan="{{ $headerTailSpan }}" style="background:var(--bs-secondary-bg);" class="text-dark align-middle" data-sort="total_bobot" title="Jumlah (Bobot × Nilai)">
                                                Jumlah<br><small style="font-size:9px;font-weight:400;">B×N</small>
                                            </th>
                                        @endif
                                        <th rowspan="{{ $headerTailSpan }}" style="background:var(--bs-secondary-bg);" class="text-dark align-middle" data-sort="avg">Rata-rata</th>
                                        <th rowspan="{{ $headerTailSpan }}" style="background:var(--bs-secondary-bg);" class="text-dark align-middle" data-sort="rank">Peringkat</th>
                                        <th rowspan="{{ $headerTailSpan }}" style="background:var(--bs-secondary-bg);" class="text-dark align-middle" data-sort="pred">Predikat</th>
                                        <th colspan="3" class="bg-secondary-subtle text-secondary">PRESENSI</th>
                                    </tr>
                                    {{-- Baris 2: nama kolom mapel + S/I/A di bawah PRESENSI --}}
                                    <tr class="text-center leger-subhead">
                                        @if($isSasLeger)
                                            @foreach(['doa' => 'Do\'a', 'hiwar' => 'Hiwar', 'conv' => 'Conversation'] as $key => $label)
                                                <th rowspan="1" style="background:var(--bs-warning-bg-subtle);" data-sort="{{ $key }}">{{ $label }}</th>
                                            @endforeach
                                            @foreach(['juz' => 'Juz', 'jumlah_juz' => 'Jml Juz', 'halaqoh' => 'Halaqoh'] as $key => $label)
                                                <th rowspan="1" style="background:var(--bs-primary-bg-subtle);" data-sort="{{ $key }}">{{ $label }}</th>
                                            @endforeach
                                        @endif
                                        @foreach(($legerSubjectMap ?? $subjectMap) as $subject)
                                            <th colspan="{{ $legerSubjectCols($subject) }}" style="background:var(--bs-tertiary-bg);" title="{{ $subject->name }}" data-sort="subject-{{ $subject->id }}">
                                                <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:0.2px;">
                                                    {{ \App\Support\AcademicNilai::shortSubjectName($subject->name) }}
                                                </div>
                                            </th>
                                        @endforeach
                                        <th class="bg-success-subtle text-success" data-sort="s">S</th>
                                        <th class="bg-warning-subtle text-warning" data-sort="i">I</th>
                                        <th class="bg-danger-subtle text-danger" data-sort="a">A</th>
                                    </tr>
                                    @if($isSasLeger)
                                        {{-- Baris 3 (SAS): N | Jumlah per mapel; sisa kolom ditutup sel pembentang --}}
                                        <tr class="text-center">
                                            <td colspan="6" style="background:var(--bs-tertiary-bg);"></td>
                                            @foreach(($legerSubjectMap ?? $subjectMap) as $subject)
                                                <th class="sub-n" style="background:var(--bs-tertiary-bg);font-size:9px;" title="Nilai {{ $subject->name }}">N</th>
                                                <th class="sub-bn" style="background:var(--bs-secondary-bg);font-size:9px;" title="Jumlah = Nilai × Bobot {{ $subject->name }}">Jumlah</th>
                                            @endforeach
                                            <td colspan="3" style="background:var(--bs-tertiary-bg);"></td>
                                        </tr>
                                    @endif
                                    {{-- Baris 4: KKTP — label mulai kolom 1 (No+NIS+Nama), input di tiap mapel --}}
                                    <tr>
                                        <th colspan="3" rowspan="{{ $tailRowCount }}" class="text-center fw-bold kktp-cell" style="background:var(--bs-tertiary-bg);vertical-align:middle;text-align:center !important;" data-col="name">
                                            <span class="bg-primary-subtle text-primary" style="text-centerdisplay:inline-flex;align-items:center;gap:2px;margin:0;">
                                                <i class="ri-checkbox-circle-line"></i>KKTP
                                            </span>
                                            @if($isSasLeger)
                                                <div class="bg-secondary-subtle text-secondary" style="margin-top:2px;font-weight:600;display:block;padding:0 4px;">
                                                    <i class="ri-scales-3-line"></i> Bobot
                                                </div>
                                            @endif
                                        </th>
                                        @if($isSasLeger)
                                            <td colspan="6" style="background:var(--bs-tertiary-bg);"></td>
                                        @endif
                                        @foreach(($legerSubjectMap ?? $subjectMap) as $subject)
                                            @php $book = $bookMap[$subject->id] ?? null; @endphp
                                            <th colspan="{{ $legerSubjectCols($subject) }}" class="kktp-cell">
                                                @if($book)
                                                    <input type="number" class="kktp-input"
                                                           name="leger_kkm[{{ $book->id }}]"
                                                           value="{{ ($kktpLevelMap[$subject->id] ?? null)?->kkm_score ?? $book?->kktp?->kkm_score ?? 75 }}"
                                                           min="0" max="100" step="0.01"
                                                           oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                           title="KKTP {{ $subject->name }} — berlaku untuk seluruh rombel di tingkat ini">
                                                @else
                                                    <span style="font-weight:700;color:var(--bs-secondary-color);">—</span>
                                                @endif
                                            </th>
                                        @endforeach
                                        @if($isSasLeger)
                                            <td colspan="3" style="background:var(--bs-tertiary-bg);"></td>
                                        @endif
                                    </tr>
                                    @if($isSasLeger)
                                        {{-- Baris 5 (SAS): Bobot per mapel — label melanjutkan sel KKTP di atas --}}
                                        <tr>
                                            <td colspan="6" style="background:var(--bs-tertiary-bg);"></td>
                                            @foreach(($legerSubjectMap ?? $subjectMap) as $subject)
                                                @php
                                                    $bobotManual = $bobotManualMap[$subject->id] ?? null;
                                                    $bobotJp = $bobotJpMap[$subject->id] ?? null;
                                                    $bobotVal = $bobotManual !== null
                                                        ? rtrim(rtrim(number_format((float) $bobotManual, 2, '.', ''), '0'), '.')
                                                        : '';
                                                    $bobotJpText = $bobotJp !== null
                                                        ? rtrim(rtrim(number_format((float) $bobotJp, 2, '.', ''), '0'), '.')
                                                        : '';
                                                @endphp
                                                <th colspan="{{ $legerSubjectCols($subject) }}" class="bobot-cell" title="Bobot {{ $subject->name }} — kosong berarti JP ÷ 2 ({{ $bobotJpText ?: '—' }}); JP 1 tetap 1">
                                                    <input type="number" class="bobot-input"
                                                           name="leger_bobot[{{ $subject->id }}]"
                                                           value="{{ $bobotVal }}"
                                                           min="0" max="99" step="0.01"
                                                           placeholder="{{ $bobotJpText }}"
                                                           oninput="if(this.value < 0) this.value = 0;"
                                                           title="Bobot {{ $subject->name }}">
                                                </th>
                                            @endforeach
                                            <td colspan="3" style="background:var(--bs-tertiary-bg);"></td>
                                        </tr>
                                    @endif
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
                                            // Kolom Jumlah = Σ (Nilai × Bobot) memakai nilai yang sama
                                            // dengan yang tampil di kolom mapel leger ini. Rata-rata,
                                            // peringkat, dan predikat dihitung di controller dari
                                            // $legerValueMap + $legerSubjectMap dengan aturan yang sama.
                                            $jumlahSts = 0; $countMapel = 0;
                                            foreach (($legerSubjectMap ?? $subjectMap) as $subject) {
                                                $book = $bookMap[$subject->id] ?? null;
                                                if (!$book) continue;
                                                $col = ($legerMode ?? 'sts') === 'sas' ? 'sas' : 'sts';
                                                $val = ($legerValueMap ?? [])[$sid][$book->id] ?? null;
                                                if ($val === null) {
                                                    $n = $nilaiMap[$sid][$book->id] ?? null;
                                                    $val = $n && $n->{$col} !== null ? $n->{$col} : null;
                                                }
                                                if ($val !== null) {
                                                    $jumlahSts += (float) ($bobotMap[$subject->id] ?? 0) * (float) $val;
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
                                                        <div class="fw-semibold">{{ $student->name }}
                                                            @if(! $history->is_active)
                                                                <span class="badge bg-secondary-subtle text-secondary ms-1">Pindah</span>
                                                            @endif
                                                        </div>
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
                                                <td class="text-center fw-semibold" style="background:var(--bs-primary-bg-subtle);" data-col="jumlah_juz">{{ $jumlahJuzMap[$sid] ?? 0 }}</td>
                                                <td class="text-center" style="background:var(--bs-primary-bg-subtle);" data-col="halaqoh">
                                                    @if($hqRow?->name)
                                                        <span class="d-inline-block text-truncate" style="max-width:76px;" title="{{ $hqRow->name }}">{{ $hqRow->name }}</span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            @endif
                                            @foreach(($legerSubjectMap ?? $subjectMap) as $subject)
                                                @php
                                                    $book = $bookMap[$subject->id] ?? null;
                                                    $n = $book ? ($nilaiMap[$student->id][$book->id] ?? null) : null;
                                                    $kkm = $book?->kktp?->kkm_score ?? 75;
                                                    $stsVal = ($legerValueMap ?? [])[$student->id][$book?->id] ?? null;
                                                    if ($stsVal === null && $book) {
                                                        $stsVal = $n ? (($legerMode ?? 'sts') === 'sas' ? $n->sas : $n->sts) : null;
                                                    }
                                                    $belowKkm = $stsVal !== null && $stsVal < $kkm;
                                                    $bobotEfektif = (float) ($bobotMap[$subject->id] ?? 0);
                                                    $nilaiTertimbang = $stsVal !== null ? $bobotEfektif * (float) $stsVal : null;
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
                                                @if($isSasLeger)
                                                    <td class="sub-bn" style="text-align:center;background:var(--bs-secondary-bg);" data-col="subject-{{ $subject->id }}-bn">
                                                        @if($nilaiTertimbang !== null)
                                                            <span class="fw-semibold">{{ number_format($nilaiTertimbang, 1, '.', '') }}</span>
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                @endif
                                            @endforeach
                                            {{-- Jumlah berbobot (STS & SAS) --}}
                                            <td class="text-center fw-semibold" style="color:var(--bs-body-color);" data-col="total">
                                                {{ $jumlahSts > 0 ? number_format($jumlahSts, 1) : '—' }}
                                            </td>
                                            @if($isSasLeger)
                                                {{-- Jumlah (Bobot × Nilai) --}}
                                                <td class="text-center fw-semibold" style="color:var(--bs-body-color);" data-col="total_bobot">
                                                    {{ isset($legerJumlahMap[$sid]) ? number_format($legerJumlahMap[$sid], 1) : '—' }}
                                                </td>
                                            @endif
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
                                            {{-- PRESENSI S / I / A — bisa diisi; kosong = ikut absensi harian --}}
                                            @foreach(['s' => ['bg-success-subtle', 'success', 'Sakit / S'],
                                                      'i' => ['bg-warning-subtle', 'warning', 'Izin / I'],
                                                      'a' => ['bg-danger-subtle', 'danger', 'Alpa / A']] as $kode => $meta)
                                                <td class="text-center fw-semibold {{ $meta[0] }} presensi-cell" data-col="{{ $kode }}">
                                                    <input type="number" class="leger-input presensi-input"
                                                           name="presensi[{{ $sid }}][{{ $kode }}]"
                                                           data-col="{{ $kode }}"
                                                           value="{{ isset($presensiManualMap[$sid]) && $presensiManualMap[$sid]->{$kode} !== null ? $presensiManualMap[$sid]->{$kode} : '' }}"
                                                           placeholder="{{ $pres[$kode] ?? 0 }}"
                                                           min="0" max="999" step="1"
                                                           style="color:var(--bs-{{ $meta[1] }}-text-emphasis);"
                                                           title="Presensi {{ $meta[2] }} — kosongkan agar mengikuti rekap absensi harian ({{ $pres[$kode] ?? 0 }})">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ collect($legerSubjectMap ?? $subjectMap)->sum($legerSubjectCols) + ($isSasLeger ? 17 : 10) }}" class="text-center text-muted py-4">
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
                                @if(collect(($legerSubjectMap ?? $subjectMap))->contains(fn ($s) => app(\App\Services\TahfidzRaporService::class)->isTahfidzSubject($s)))
                                    <br><i class="ri-book-open-line me-1"></i>
                                    Mapel tahfidz menampilkan <strong>NR Final</strong> dari buku nilai halaqah; rincian NPT · Ziyadah · Rabt · Tikrar · STS · SAS ada di tab Mapel.
                                    Tanda <strong>—</strong> berarti datanya memang belum ada (mis. santri belum masuk halaqah).
                                @endif
                            </small>
                        </div>
                    </form>
                @endif
            @endif

            {{-- ========================================== --}}
            {{-- TAB: MAPEL — tahfidz memakai format per halaqah --}}
            {{-- TAB: MAPEL — format tahfidz. Tampilan, fitur, dan tata letak
                 SAMA dengan tab mapel umum; hanya isi kolom nilainya yang
                 berbeda (NPT/Ziyadah/Rabt/Tikrar/STS/SAS per halaqah). --}}
            @if($activeTab === 'mapel' && $activeBook && ($mapelIsTahfidz ?? false))
                @php
                    $thfRows = $tahfidzMapel['rows'] ?? [];
                    $thfWeights = $tahfidzMapel['weights'] ?? [];
                    $thfW = fn ($k) => (float) ($thfWeights[$k] ?? $thfKeys[$k] ?? 0);
                    $thfTotal = collect(array_keys($thfKeys))->sum(fn ($k) => $thfW($k));
                    $thfFmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
                    $thfAuto = ['npt', 'tikrar'];
                    // Jumlah = NR Final × Bobot mapel (kolom "Jumlah" tab umum).
                    $thfBobot = (float) ($bobotMap[$activeBook->subject_id] ?? 0);
                @endphp

                @if($students->isNotEmpty() && $students->every(fn ($history) => ! $history->is_active) && ! $fallbackRoster)
                    <div class="alert alert-warning border-0 rounded-0 mb-0 px-4 py-3" style="font-size:12px;">
                        <i class="ri-information-line me-1"></i>
                        Data ini berasal dari arsip tahun ajaran lama. Nilai tetap ditampilkan sebagai referensi
                        dan kolom input dikunci.
                    </div>
                @endif
                <div class="px-4 py-3" style="background:var(--bs-tertiary-bg);border-bottom:1px solid var(--bs-border-color);">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white" style="font-size:12px;">
                                <i class="ri-book-line me-1"></i>{{ $activeBook->subject->name }}
                            </span>
                            <span class="badge bg-secondary-subtle text-secondary text-capitalize">
                                {{ $activeBook->semester }}
                            </span>
                            <span class="text-muted" style="font-size:12px;">
                                <i class="ri-lock-line me-1"></i>Bobot diatur Departemen Tahfidz
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size:12px;">
                            @foreach($thfKeys as $wk)
                                <span class="badge bg-success-subtle text-success">
                                    {{ $thfCols[$wk] }} ({{ $thfFmt($thfW($wk)) }}%)
                                </span>
                            @endforeach
                            <span class="badge {{ abs($thfTotal - 100) < 0.001 ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning' }}"
                                  id="thfWeightTotal">
                                <i class="ri-calculator-line me-1"></i>Total {{ $thfFmt($thfTotal) }}%
                            </span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('user.schools.nilai-kelas.sts.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}" id="formMapelTahfidz">
                    @csrf
                    <input type="hidden" name="admin_book_id" value="{{ $activeBook->id }}">
                    <input type="hidden" name="tab" value="mapel_tahfidz">
                    <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                    <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">

                    <div class="table-responsive leger-scroll">
                        <table id="tab-mapel" data-paste-grid class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th rowspan="2" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="no">No</th>
                                    <th rowspan="2" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="nis">NIS</th>
                                    <th rowspan="2" class="text-center align-middle text-muted" style="background:var(--bs-tertiary-bg);" data-sort="name">
                                        <i class="ri-user-line me-1"></i>Nama Santri
                                    </th>
                                    <th colspan="{{ count($thfKeys) }}" class="bg-success-subtle text-success">Nilai Tahfidz</th>
                                    <th rowspan="2" class="align-middle bg-success-subtle" width="80">NR Final</th>
                                    <th rowspan="2" class="align-middle bg-secondary-subtle" width="80" title="Jumlah = NR Final × Bobot">Jumlah</th>
                                    <th rowspan="2" class="align-middle bg-secondary-subtle" width="120">Keterangan</th>
                                </tr>
                                <tr>
                                    @foreach($thfKeys as $wk)
                                        <th style="min-width:76px;white-space:nowrap;" title="{{ $thfCols[$wk] }} — bobot {{ $thfFmt($thfW($wk)) }}%">
                                            {{ $thfCols[$wk] }}<br>
                                            <small class="fw-normal">({{ $thfFmt($thfW($wk)) }}%)</small>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $i => $history)
                                    @php
                                        $student = $history->student;
                                        $row = $thfRows[$student->id] ?? [];
                                        $jumlahNilai = isset($row['nr_final']) && $row['nr_final'] !== null
                                            ? $thfBobot * (float) $row['nr_final']
                                            : null;
                                    @endphp
                                    <tr data-inactive="{{ (($isArchiveAy ?? false) || (! $history->is_active && ! $fallbackRoster)) ? '1' : '0' }}">
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
                                                @if(! $history->is_active && ! $fallbackRoster)
                                                    <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:10px;">Nonaktif</span>
                                                @endif
                                            </div>
                                        </td>
                                        @foreach($thfKeys as $wk)
                                            @php
                                                $cell = $row[$wk] ?? null;
                                                $isAuto = in_array($wk, $thfAuto, true);
                                            @endphp
                                            <td class="p-0" style="text-align:center;" data-col="{{ $wk }}">
                                                <input type="number" class="sheet-input tahfidz-score-input"
                                                       data-key="{{ $wk }}"
                                                       @if($isAuto) data-auto="1" title="Nilai awal otomatis — boleh dikoreksi" @endif
                                                       name="nilai[{{ $student->id }}][{{ $wk }}]"
                                                       value="{{ $cell !== null ? $thfFmt($cell) : '' }}"
                                                       placeholder="---"
                                                       min="0" max="100" step="0.01"
                                                       oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;">
                                            </td>
                                        @endforeach
                                        <td class="text-center fw-bold bg-light" id="nrf-{{ $student->id }}">{{ isset($row['nr_final']) && $row['nr_final'] !== null ? number_format((float) $row['nr_final'], 1) : '---' }}</td>
                                        <td class="text-center fw-semibold bg-light" id="jml-{{ $student->id }}" data-bobot="{{ $thfBobot }}" title="Jumlah = NR Final × Bobot ({{ $thfFmt($thfBobot) }})">{{ $jumlahNilai !== null ? number_format($jumlahNilai, 1) : '---' }}</td>
                                        <td class="p-0">
                                            <input type="text" class="sheet-input" maxlength="100"
                                                   name="nilai[{{ $student->id }}][ket]"
                                                   value="{{ $row['ket'] ?? '' }}" placeholder="Catatan (opsional)">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 6 + count($thfKeys) }}" class="text-center text-muted py-4">
                                            <i class="ri-group-line me-1"></i>Tidak ada Santri.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer border-top-dashed px-4 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:12px;">
                                <span class="badge bg-success-subtle text-success">Tahfidz</span>
                                <span class="text-muted">= NPT, Ziyadah, Rabt, Tikrar, STS, SAS per halaqah. Bobot NR Final ditetapkan Departemen Tahfidz. Tersimpan otomatis saat mengetik.</span>
                                <span class="text-muted">NPT &amp; Tikrar terisi otomatis dari target &amp; setoran — boleh dikoreksi.</span>
                            </div>
                        </div>
                    </div>
                </form>
            @endif

            {{-- TAB: MAPEL --}}
            {{-- ========================================== --}}
            @if($activeTab === 'mapel' && $activeBook && ! ($mapelIsTahfidz ?? false))
                @if($students->isNotEmpty() && $students->every(fn ($history) => ! $history->is_active) && ! $fallbackRoster)
                    <div class="alert alert-warning border-0 rounded-0 mb-0 px-4 py-3" style="font-size:12px;">
                        <i class="ri-information-line me-1"></i>
                        Data ini berasal dari arsip tahun ajaran lama. Nilai tetap ditampilkan sebagai referensi
                        dan kolom input dikunci.
                    </div>
                @endif
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
                    <input type="hidden" name="admin_book_id" value="{{ $activeBook->id }}">
                    <input type="hidden" name="tab" value="mapel">

                    <div class="table-responsive leger-scroll">
                        <table id="tab-mapel" data-paste-grid class="table table-bordered table-nowrap align-middle mb-0">
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
                                    @if($stsColumns === [])
                                        <th rowspan="2" class="align-middle bg-warning-subtle" width="80" data-sort="sts">
                                            STS<br><small class="fw-normal">({{ $activeBook?->nr_final_weight_sts ?? 25 }}%)</small>
                                            <button type="button" class="btn btn-outline-secondary btn-sm mt-1 py-0 px-1 d-block mx-auto" style="font-size:9px;"
                                                    onclick="komponenOpen('sts')" title="Aktifkan komponen Praktik/Tulis">
                                                <i class="ri-settings-3-line"></i> Komponen
                                            </button>
                                        </th>
                                    @else
                                        <th colspan="{{ count($stsColumns) + 1 }}" class="align-middle bg-warning-subtle">
                                            STS<br><small class="fw-normal">({{ $activeBook?->nr_final_weight_sts ?? 25 }}%)</small>
                                            <button type="button" class="btn btn-outline-primary btn-sm ms-1 py-0 px-1" style="font-size:9px;"
                                                    onclick="komponenOpen('sts')">
                                                <i class="ri-settings-3-line"></i> Komponen
                                            </button>
                                        </th>
                                    @endif
                                    <th rowspan="2" class="align-middle bg-info-subtle" width="80" data-sort="sas-murni">
                                        N. Murni<br><small class="fw-normal">SAS</small>
                                    </th>
                                    @if($sasColumns === [])
                                        <th rowspan="2" class="align-middle bg-info-subtle" width="80" data-sort="sas">
                                            SAS<br><small class="fw-normal">(25%)</small>
                                            <button type="button" class="btn btn-outline-secondary btn-sm mt-1 py-0 px-1 d-block mx-auto" style="font-size:9px;"
                                                    onclick="komponenOpen('sas')" title="Aktifkan komponen Praktik/Tulis">
                                                <i class="ri-settings-3-line"></i> Komponen
                                            </button>
                                        </th>
                                    @else
                                        <th colspan="{{ count($sasColumns) + 1 }}" class="align-middle bg-info-subtle">
                                            SAS<br><small class="fw-normal">(25%)</small>
                                            <button type="button" class="btn btn-outline-primary btn-sm ms-1 py-0 px-1" style="font-size:9px;"
                                                    onclick="komponenOpen('sas')">
                                                <i class="ri-settings-3-line"></i> Komponen
                                            </button>
                                        </th>
                                    @endif
                                    <th rowspan="2" class="align-middle bg-info-subtle" width="80">RSA</th>
                                    <th rowspan="2" class="align-middle bg-info-subtle" width="80">NR<br>Murni</th>
                                    <th colspan="6" class="align-middle bg-success-subtle">Penghargaan Akademik</th>
                                    <th rowspan="2" class="align-middle bg-success-subtle" width="80">NR Final</th>
                                    <th rowspan="2" class="align-middle bg-secondary-subtle" width="80" title="Jumlah = NR Final × Bobot">Jumlah</th>
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
                                    @if($stsColumns !== [])
                                        @foreach($stsColumns as $col)
                                            <th style="min-width:60px;white-space:nowrap;" title="Komponen STS — {{ $col['label'] }}">{{ $col['label'] }}</th>
                                        @endforeach
                                        <th style="min-width:60px;" title="Nilai akhir STS = rata-rata komponen">Nilai</th>
                                    @endif
                                    @if($sasColumns !== [])
                                        @foreach($sasColumns as $col)
                                            <th style="min-width:60px;white-space:nowrap;" title="Komponen SAS — {{ $col['label'] }}">{{ $col['label'] }}</th>
                                        @endforeach
                                        <th style="min-width:60px;" title="Nilai akhir SAS = rata-rata komponen">Nilai</th>
                                    @endif
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
                                    @php
                                        $bobotAktif = (float) ($bobotMap[$activeBook->subject_id] ?? 0);
                                        $jumlahNilai = $existing?->nr_final !== null ? $bobotAktif * (float) $existing->nr_final : null;
                                    @endphp
                                    <tr data-inactive="{{ (($isArchiveAy ?? false) || (! $history->is_active && ! $fallbackRoster)) ? '1' : '0' }}">
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
                                                @if(! $history->is_active && ! $fallbackRoster)
                                                    <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:10px;">Nonaktif</span>
                                                @endif
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
                                        @if($stsColumns === [])
                                        <td class="p-0" data-col="sts">
                                            <input type="number" class="sheet-input sts-input js-gated"
                                                   data-requires="sts-murni"
                                                   name="nilai[{{ $student->id }}][sts]"
                                                   value="{{ $existing?->sts ?? '' }}"
                                                   oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                   min="0" max="100" step="0.01" placeholder="---"
                                                   @if(! ($murniOptional ?? false) && $existing?->sts_murni === null) readonly @endif>
                                        </td>
                                        @else
                                            @php $stsVals = $stsComponentMap[$student->id] ?? []; @endphp
                                            @foreach($stsColumns as $col)
                                                <td class="p-0" data-col="sts-komponen-{{ $col['id'] }}">
                                                    <input type="number" class="sheet-input komponen-input komponen-sts"
                                                           data-jenis="sts" data-student="{{ $student->id }}"
                                                           name="nilai[{{ $student->id }}][sts_components][{{ $col['id'] }}]"
                                                           value="{{ $stsVals[$col['id']] ?? '' }}"
                                                           oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                           min="0" max="100" step="0.01" placeholder="---"
                                                           @if(! ($murniOptional ?? false) && $existing?->sts_murni === null) readonly @endif>
                                                </td>
                                            @endforeach
                                            <td class="text-center fw-semibold" id="sts-final-{{ $student->id }}">
                                                {{ $existing?->sts !== null ? number_format((float) $existing->sts, 1) : '---' }}
                                            </td>
                                        @endif
                                        @php $ph = $penghargaanMap[$student->id] ?? null; @endphp
                                        <td class="p-0" data-col="sas-murni">
                                            <input type="number" class="sheet-input"
                                                   data-key="sas-murni"
                                                   name="nilai[{{ $student->id }}][sas_murni]"
                                                   value="{{ $existing?->sas_murni ?? '' }}"
                                                   oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                   min="0" max="100" step="0.01" placeholder="---">
                                        </td>
                                        @if($sasColumns === [])
                                        <td class="p-0" data-col="sas">
                                            <input type="number" class="sheet-input sas-input js-gated"
                                                   data-requires="sas-murni"
                                                   name="nilai[{{ $student->id }}][sas]"
                                                   value="{{ $existing?->sas ?? '' }}"
                                                   oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                   min="0" max="100" step="0.01" placeholder="---"
                                                   @if(! ($murniOptional ?? false) && $existing?->sas_murni === null) readonly @endif>
                                        </td>
                                        @else
                                            @php $sasVals = $sasComponentMap[$student->id] ?? []; @endphp
                                            @foreach($sasColumns as $col)
                                                <td class="p-0" data-col="sas-komponen-{{ $col['id'] }}">
                                                    <input type="number" class="sheet-input komponen-input komponen-sas"
                                                           data-jenis="sas" data-student="{{ $student->id }}"
                                                           name="nilai[{{ $student->id }}][sas_components][{{ $col['id'] }}]"
                                                           value="{{ $sasVals[$col['id']] ?? '' }}"
                                                           oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                           min="0" max="100" step="0.01" placeholder="---"
                                                           @if(! ($murniOptional ?? false) && $existing?->sas_murni === null) readonly @endif>
                                                </td>
                                            @endforeach
                                            <td class="text-center fw-semibold" id="sas-final-{{ $student->id }}">
                                                {{ $existing?->sas !== null ? number_format((float) $existing->sas, 1) : '---' }}
                                            </td>
                                        @endif
                                        {{-- RSA bisa diisi manual; kosong = otomatis (rata-rata STS & SAS). --}}
                                        <td class="p-0 text-center" data-col="rsa">
                                            <input type="number" class="rsa-input" id="rsa-{{ $student->id }}" data-student="{{ $student->id }}"
                                                   name="nilai[{{ $student->id }}][rsa]"
                                                   value="{{ $existing?->rsa_manual ? rtrim(rtrim(number_format((float) $existing->rsa, 2, '.', ''), '0'), '.') : '' }}"
                                                   placeholder="{{ $existing?->rsa !== null ? number_format((float) $existing->rsa, 1) : '---' }}"
                                                   min="0" max="100" step="0.01"
                                                   title="RSA manual — kosongkan untuk kembali otomatis (rata-rata STS & SAS)">
                                        </td>
                                        {{-- NR Murni bisa diisi manual; kosong = otomatis (rata-rata RS & RSA). --}}
                                        <td class="p-0 text-center" data-col="nr_murni">
                                            <input type="number" class="rsa-input" id="nrm-{{ $student->id }}" data-student="{{ $student->id }}"
                                                   name="nilai[{{ $student->id }}][nr_murni]"
                                                   value="{{ $existing?->nr_murni_manual ? rtrim(rtrim(number_format((float) $existing->nr_murni, 2, '.', ''), '0'), '.') : '' }}"
                                                   placeholder="{{ $existing?->nr_murni !== null ? number_format((float) $existing->nr_murni, 1) : '---' }}"
                                                   min="0" max="100" step="0.01"
                                                   title="NR Murni manual — kosongkan untuk kembali otomatis (rata-rata RS & RSA)">
                                        </td>
                                        <td class="text-center">{{ $ph?->jujur ?? '---' }}</td>
                                        <td class="text-center">{{ $ph?->disiplin ?? '---' }}</td>
                                        <td class="text-center">{{ $ph?->peduli ?? '---' }}</td>
                                        <td class="text-center">{{ $ph?->adab ?? '---' }}</td>
                                        <td class="text-center">{{ $ph?->kehadiran ?? '---' }}</td>
                                        <td class="text-center">{{ $ph?->keaktifan ?? '---' }}</td>
                                        <td class="text-center fw-bold bg-light" id="nrf-{{ $student->id }}">{{ $existing?->nr_final !== null ? number_format($existing->nr_final, 1) : '---' }}</td>
                                        <td class="text-center fw-semibold bg-light" id="jml-{{ $student->id }}" data-bobot="{{ $bobotAktif }}" title="Jumlah = NR Final × Bobot ({{ rtrim(rtrim(number_format($bobotAktif, 2, '.', ''), '0'), '.') }})">{{ $jumlahNilai !== null ? number_format($jumlahNilai, 1) : '---' }}</td>
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
                                        <td colspan="{{ 23 + count($columns) + count($stsColumns) + count($sasColumns) }}" class="text-center text-muted py-4">
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

{{-- Modal: Komponen STS/SAS (mis. Praktik & Tulis) --}}
    <div class="modal fade" id="komponenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="komponen-title"><i class="ri-layout-column-line me-1"></i>Kelola Komponen STS</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="komponen-jenis" value="sts">
                    <p class="text-muted mb-3" style="font-size:12px;">
                        Nilai akhir <strong>STS/SAS</strong> = rata-rata komponen yang terisi.
                        Kosongkan semua komponen lalu simpan untuk mematikan fitur.
                    </p>
                    <div id="komponen-list"></div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-1" onclick="komponenAdd()">
                        <i class="ri-add-line me-1"></i>Tambah Komponen
                    </button>
                </div>
                <div class="modal-footer">
                    <span id="komponen-status" class="me-auto text-muted" style="font-size:12px;"></span>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="komponen-reset" onclick="komponenReset()"
                            title="Hapus semua komponen dan kembalikan ke satu kolom nilai">
                        <i class="ri-arrow-go-back-line me-1"></i>Reset ke 1 Kolom
                    </button>
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="komponenSave()">
                        <i class="ri-save-line me-1"></i>Simpan Komponen
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

    .predicate-notes-box {
        border: 1px dashed var(--bs-border-color);
        border-radius: 8px;
        padding: 8px 12px;
        background: var(--bs-tertiary-bg);
    }

    .predicate-notes-box summary {
        cursor: pointer;
        list-style: none;
        font-size: 12px;
        color: var(--bs-body-color);
    }

    .predicate-notes-box summary::-webkit-details-marker {
        display: none;
    }

    .predicate-notes-box[open] summary .ms-auto {
        visibility: hidden;
    }

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
    #tab-leger thead th[data-sort="name"], #tab-leger td[data-col="name"],
    #tab-mapel th[data-sort="name"], #tab-mapel td[data-col="name"] { left: 134px; }
    /* Sel KKTP membentang No+NIS+Nama (sticky) — mulai dari kolom pertama */
    #tab-leger thead th[data-col="name"].kktp-cell { left: 0; }
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
    /* Satu mapel = 2 sub-kolom: N (64px) + Jumlah berbobot (68px) = 132px. */
    #tab-leger th[data-sort^="subject-"] { width: 132px; min-width: 132px; max-width: 132px; }
    #tab-leger td[data-col^="subject-"] { width: 64px; min-width: 64px; max-width: 64px; }
    #tab-leger th.sub-n { width: 64px; min-width: 64px; max-width: 64px; }
    #tab-leger th.sub-bn, #tab-leger td.sub-bn { width: 68px; min-width: 68px; max-width: 68px; }
    /* PEMBIASAAN PAGI (Do'a/Hiwar/Conversation) & TAHFIDZ (Juz/Jml Juz/Halaqoh):
       lebar seragam + isi rata tengah (horizontal & vertikal). */
    #tab-leger th[data-sort="doa"], #tab-leger td[data-col="doa"],
    #tab-leger th[data-sort="hiwar"], #tab-leger td[data-col="hiwar"],
    #tab-leger th[data-sort="conv"], #tab-leger td[data-col="conv"],
    #tab-leger th[data-sort="juz"], #tab-leger td[data-col="juz"],
    #tab-leger th[data-sort="jumlah_juz"], #tab-leger td[data-col="jumlah_juz"],
    #tab-leger th[data-sort="halaqoh"], #tab-leger td[data-col="halaqoh"] {
        width: 84px; min-width: 84px; max-width: 84px;
        text-align: center; vertical-align: middle;
    }
    #tab-leger td[data-col="halaqoh"] { overflow: hidden; text-overflow: ellipsis; }
    #tab-leger th[data-sort="total"], #tab-leger td[data-col="total"] { width: 76px; min-width: 76px; max-width: 76px; }
    #tab-leger th[data-sort="total_bobot"], #tab-leger td[data-col="total_bobot"] { width: 76px; min-width: 76px; max-width: 76px; }
    #tab-leger th[data-sort="avg"], #tab-leger td[data-col="avg"] { width: 82px; min-width: 82px; max-width: 82px; }
    #tab-leger th[data-sort="rank"], #tab-leger td[data-col="rank"] { width: 64px; min-width: 64px; max-width: 64px; }
    #tab-leger th[data-sort="pred"], #tab-leger td[data-col="pred"] { width: 150px; min-width: 150px; max-width: 150px; }
    #tab-leger th[data-sort="s"], #tab-leger td[data-col="s"],
    #tab-leger th[data-sort="i"], #tab-leger td[data-col="i"],
    #tab-leger th[data-sort="a"], #tab-leger td[data-col="a"] { width: 42px; min-width: 42px; max-width: 42px; }
    #tab-mapel th[data-sort^="sh-"], #tab-mapel td[data-col^="sh-"] { min-width: 56px; }
    /* Sub-kolom komponen STS/SAS + kolom Nilai akhir: lebar seragam */
    #tab-mapel th[title^="Komponen"], #tab-mapel th[title^="Nilai akhir"],
    #tab-mapel td[data-col^="sts-komponen-"], #tab-mapel td[data-col^="sas-komponen-"] { min-width: 64px; }
    #tab-mapel td[id^="sts-final-"], #tab-mapel td[id^="sas-final-"] { min-width: 60px; }

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
    /* Presensi S/I/A: input muat dalam kolom 42px (tanpa spinner) agar tabel tidak melebar */
    #tab-leger td.presensi-cell { padding: 0 1px !important; vertical-align: middle; }
    #tab-leger input.presensi-input.leger-input {
        width: 100%; min-width: 0; max-width: 100%; height: 27px; padding: 0;
        text-align: center; font-variant-numeric: tabular-nums;
        -moz-appearance: textfield; appearance: textfield;
    }
    #tab-leger input.presensi-input.leger-input::-webkit-outer-spin-button,
    #tab-leger input.presensi-input.leger-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    #tab-leger input.presensi-input.leger-input::placeholder { color: var(--bs-secondary-color); font-weight: 500; opacity: .55; }
    /* Kolom terkunci (menunggu Nilai Murni) diberi corak agar terlihat sebelum diketik */
    #tab-mapel input.js-gated[readonly] {
        background: repeating-linear-gradient(45deg, #fff8e1, #fff8e1 4px, #ffe9a8 4px, #ffe9a8 8px);
        cursor: not-allowed;
    }
    #tab-mapel input.js-gated[readonly]:focus { background: repeating-linear-gradient(45deg, #fff8e1, #fff8e1 4px, #ffe9a8 4px, #ffe9a8 8px); }
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
    /* RSA manual: tampil seperti spreadsheet, angka otomatis hanya di placeholder */
    #tab-mapel input.rsa-input {
        width: 100%; height: 30px; padding: 0 4px;
        border: 0; border-radius: 0; box-shadow: none;
        background: transparent; color: var(--bs-body-color);
        font-size: 12px; font-style: italic; font-weight: 600;
        text-align: center; text-align-last: center;
    }
    #tab-mapel input.rsa-input:focus { outline: none; border: 0; box-shadow: none; background: rgba(var(--bs-primary-rgb), .06); }
    #tab-mapel input.rsa-input::placeholder { color: var(--bs-secondary-color); font-weight: 500; opacity: .75; }
    #tab-mapel input.sts-input { background: var(--bs-warning-bg-subtle); font-weight: 600; }
    #tab-mapel input.sts-input:focus { border: 0; box-shadow: none; outline: none; background: rgba(var(--bs-warning-rgb), .18); }

    /* ── Urutkan kolom ── */
    th[data-sort] { cursor: pointer; user-select: none; white-space: nowrap; }
    th[data-sort]:hover { filter: brightness(.96); }
    th[data-dir="asc"]::after { content: " ▲"; font-size: 9px; color: var(--bs-primary); }
    th[data-dir="desc"]::after { content: " ▼"; font-size: 9px; color: var(--bs-primary); }

    /* ── Grid tipis: spreadsheet, bukan tabel berbingkai tebal ──
       Bootstrap memberi 1px penuh di setiap sisi sehingga garis vertikal
       dan horizontal bertumpuk jadi terlihat tebal. Di sini hanya sisi kanan
       dan bawah yang digambar — hasilnya satu garis tipis antar sel. */
    #tab-leger.table-bordered, #tab-mapel.table-bordered { border-width: 0 !important; }
    #tab-leger.table-bordered > :not(caption) > *, #tab-mapel.table-bordered > :not(caption) > * { border-width: 0 !important; }
    #tab-leger.table-bordered > :not(caption) > * > *,
    #tab-mapel.table-bordered > :not(caption) > * > * {
        border-width: 0 !important;
        border-right: 1px solid rgba(var(--bs-body-color-rgb), .12) !important;
        border-bottom: 1px solid rgba(var(--bs-body-color-rgb), .12) !important;
    }
    /* Garis luar tabel tetap satu, sedikit lebih jelas sebagai batas area. */
    #tab-leger.table-bordered > tbody > tr:first-child > *,
    #tab-mapel.table-bordered > tbody > tr:first-child > * { border-top: 1px solid rgba(var(--bs-body-color-rgb), .18) !important; }
    #tab-leger.table-bordered > * > tr > *:first-child,
    #tab-mapel.table-bordered > * > tr > *:first-child { border-left: 1px solid rgba(var(--bs-body-color-rgb), .18) !important; }

    /* ── Baris ── */
    #tab-leger tbody tr, #tab-mapel tbody tr { transition: background-color .12s; }
    #tab-leger tbody tr:hover td, #tab-mapel tbody tr:hover td { background: rgba(var(--bs-primary-rgb), .05); }
    /* Kolom beku saat hover: warnanya WAJIB sama dengan kolom lain, tetapi
       harus OPAK — latar transparan membuat kolom yang bergeser di bawahnya
       tembus pandang. Karena itu warna tipisnya dilapis di atas latar solid. */
    #tab-leger tbody tr:hover td[data-col="no"], #tab-leger tbody tr:hover td[data-col="nis"], #tab-leger tbody tr:hover td[data-col="name"],
    #tab-mapel tbody tr:hover td[data-col="no"], #tab-mapel tbody tr:hover td[data-col="nis"], #tab-mapel tbody tr:hover td[data-col="name"] {
        background-color: var(--bs-body-bg);
        background-image: linear-gradient(rgba(var(--bs-primary-rgb), .05), rgba(var(--bs-primary-rgb), .05));
    }

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
        width: 100%; height: 21px; padding: 0 4px;
        border: 0; border-radius: 0; box-shadow: none;
        background: transparent; color: var(--bs-body-color);
        font-weight: 700; font-size: 11px; text-align: center;
    }
    #tab-leger th input.kktp-input:focus { border: 0; outline: none; box-shadow: none; background: rgba(var(--bs-primary-rgb), .06); }

    #tab-leger th input.bobot-input {
        width: 100%; min-width: 46px; height: 21px; padding: 0 2px;
        border: 0; border-radius: 0; box-shadow: none;
        background: transparent; color: var(--bs-body-color);
        font-weight: 700; font-size: 11px; text-align: center;
        -moz-appearance: textfield;
    }
    #tab-leger th input.bobot-input::-webkit-outer-spin-button,
    #tab-leger th input.bobot-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    #tab-leger th input.bobot-input::placeholder { color: var(--bs-secondary-color); font-weight: 500; }
    #tab-leger th input.bobot-input:focus { border: 0; outline: none; box-shadow: none; background: rgba(var(--bs-primary-rgb), .06); }

    /* ── Baris KKTP & Bobot: tinggi diringkas ── */
    /* Header ringkasan (Jumlah..Predikat) membentang beberapa baris: pastikan garis bawah tetap tergambar */
    #tab-leger thead th[data-sort="total"], #tab-leger thead th[data-sort="total_bobot"],
    #tab-leger thead th[data-sort="avg"], #tab-leger thead th[data-sort="rank"],
    #tab-leger thead th[data-sort="pred"] {
        border-bottom: var(--bs-border-width) solid var(--bs-border-color) !important;
    }
    #tab-leger thead th.kktp-cell,
    #tab-leger thead th.bobot-cell { padding: 0 2px; line-height: 1; vertical-align: middle; }
    #tab-leger thead th[data-col="name"].kktp-cell { padding: 0 6px; }
    #tab-leger thead th[data-col="name"] > span {
        display: inline-block; padding: 1px 6px; line-height: 1.2;
    }
    /* Label KKTP: rata kiri sejajar kolom No */
    #tab-leger thead th[data-col="name"].kktp-cell { text-align: left; padding: 0 2px 0 4px; line-height: 1; }
    #tab-leger thead th[data-col="name"].kktp-cell > span { padding: 1px 4px; }
    /* SAS: sel KKTP menampung dua label (KKTP + Bobot) — beri jarak baris */
    #tab-leger thead th[data-col="name"].kktp-cell > div { margin-top: 3px !important; line-height: 1.4; padding: 0 4px !important; }
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
    // Sel yang kini berupa input manual (RSA, NR Murni): jangan timpa isian
    // guru, cukup perbarui angka otomatis pada placeholder.
    var setPlaceholder = function (prefix, val) {
        var el = document.getElementById(prefix + studentId);
        if (!el) return;
        var text = (val === null || val === undefined || val === '') ? '---' : Number(val).toFixed(1);
        if (el.tagName === 'INPUT') {
            el.placeholder = text;
        } else {
            el.textContent = text;
        }
    };
    if (data.rs !== undefined) set('rs-', data.rs);
    if (data.rsa !== undefined) setPlaceholder('rsa-', data.rsa);
    if (data.nr_murni !== undefined) setPlaceholder('nrm-', data.nr_murni);
    if (data.nr_final !== undefined) {
        set('nrf-', data.nr_final);

        // Jumlah = NR Final × bobot mapel (dibulatkan 1 desimal).
        var jml = document.getElementById('jml-' + studentId);
        if (jml) {
            var bobot = parseFloat(jml.dataset.bobot || '0');
            var nrf = parseFloat(data.nr_final);
            jml.textContent = (isNaN(nrf) || !bobot) ? '---' : (bobot * nrf).toFixed(1);
        }
    }
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

        // Santri nonaktif (mis. sudah pindah/riwayat rombel) tetap ditampilkan
        // agar nilai yang ada di leger tidak terlihat kosong. Input dikunci.
        table.querySelectorAll('tbody tr[data-inactive="1"] input').forEach(function (input) {
            input.disabled = true;
            input.readOnly = true;
        });

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
        // Pengecualian: Tahfidz, Hadits, dan Bahasa Inggris kelas 1–3 SD tidak wajib.
        var MURNI_OPTIONAL = @json($murniOptional ?? false);

        function refreshGate(inp) {
            if (inp.disabled) return;
            var row = inp.closest('tr');
            var need = inp.getAttribute('data-requires');
            if (!row || !need) return;
            if (MURNI_OPTIONAL) {
                inp.readOnly = false;
                inp.title = '';
                return;
            }
            var src = row.querySelector('input[data-key="' + need + '"]');
            var has = src && String(src.value).trim() !== '';
            inp.readOnly = !has;
            inp.title = has ? '' : 'Isi ' + (need === 'sts-murni' ? 'Nilai Murni STS' : 'Nilai Murni SAS') + ' terlebih dahulu.';
        }

        // Klik pada kolom terkunci → modal peringatan
        table.addEventListener('click', function (e) {
            var inp = e.target;
            if (MURNI_OPTIONAL) return;
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
            var mapel = document.getElementById('formMapel') || document.getElementById('formMapelTahfidz');
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
        [document.getElementById('formLeger'), document.getElementById('formMapel'), document.getElementById('formMapelTahfidz')].forEach(function(form) {
            if (form) {
                form.querySelectorAll('input').forEach(function(el) {
                    el.addEventListener('input', triggerSave);
                    el.addEventListener('change', triggerSave);
                });
            }
        });
    })();

    // ─── Auto-save Presensi S/I/A (terpisah dari nilai) ──────────
    (function() {
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        var dot = document.getElementById('autosave-dot');
        var text = document.getElementById('autosave-text');
        var timer = null;
        var inFlight = false;

        function setIndicator(cls, msg) {
            if (dot) dot.className = 'rounded-circle ' + cls;
            if (text) text.textContent = msg;
        }

        function save() {
            if (inFlight) return;

            var payload = new URLSearchParams();
            payload.append('academic_year_id', '{{ $selectedAyId }}');
            payload.append('semester', '{{ $selectedSemester }}');

            var count = 0;
            document.querySelectorAll('#tab-leger .presensi-input').forEach(function(inp) {
                var m = inp.name.match(/^presensi\[([^\]]+)\]\[(s|i|a)\]$/);
                if (!m) return;
                var v = inp.value.trim();
                payload.append('presensi[' + m[1] + '][' + m[2] + ']', v === '' ? '' : v);
                count++;
            });
            if (count === 0) return;

            inFlight = true;
            setIndicator('bg-warning', 'Menyimpan presensi…');

            fetch('{{ $presensiUrl }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: payload.toString(),
            })
            .then(function(res) { return res.json().then(function(j) { return { ok: res.ok, body: j }; }); })
            .then(function(r) {
                inFlight = false;
                if (r.ok && r.body && r.body.success === true) {
                    var time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    setIndicator('bg-success', 'Presensi tersimpan ' + time);
                    setTimeout(function() { setIndicator('bg-secondary', 'Menunggu…'); }, 3000);
                } else {
                    setIndicator('bg-danger', (r.body && r.body.message) || 'Gagal menyimpan presensi');
                    setTimeout(function() { setIndicator('bg-secondary', 'Menunggu…'); }, 4000);
                }
            })
            .catch(function() {
                inFlight = false;
                setIndicator('bg-danger', 'Gagal menyimpan presensi');
                setTimeout(function() { setIndicator('bg-secondary', 'Menunggu…'); }, 4000);
            });
        }

        document.querySelectorAll('#tab-leger .presensi-input').forEach(function(inp) {
            inp.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(save, 1500);
            });
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

// ─── Komponen STS/SAS (Praktik/Tulis) ───────────────────────────
const komponenUrl = "{{ route('user.schools.nilai-kelas.sts.komponen', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'admin_book_id' => $activeBook?->id]) }}";
const komponenPreset = [{ id: 'praktik', label: 'Praktik' }, { id: 'tulis', label: 'Tulis' }];
let komponenJenis = 'sts';
let komponenData = { sts: @json($stsColumns ?? []), sas: @json($sasColumns ?? []) };
// Status tersimpan di server: tombol reset hanya aktif bila fitur memang sedang dipakai.
const komponenTersimpan = { sts: (@json($stsColumns ?? [])).length > 0, sas: (@json($sasColumns ?? [])).length > 0 };

function komponenEscape(value) {
    return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
}

function komponenRender() {
    const wrap = document.getElementById('komponen-list');
    if (!wrap) return;

    const rows = komponenData[komponenJenis] || [];
    const resetBtn = document.getElementById('komponen-reset');
    if (resetBtn) resetBtn.disabled = ! komponenTersimpan[komponenJenis];

    if (rows.length === 0) {
        wrap.innerHTML = '<div class="text-muted small mb-2">Belum ada komponen. Tambahkan komponen untuk mengaktifkan fitur.</div>';
        return;
    }

    wrap.innerHTML = rows.map(function (c, i) {
        return '<div class="input-group input-group-sm mb-2">' +
            '<span class="input-group-text" style="width:42px;">' + (i + 1) + '</span>' +
            '<input type="text" class="form-control" maxlength="60" value="' + komponenEscape(c.label) + '" oninput="komponenData[\'' + komponenJenis + '\'][' + i + '].label = this.value">' +
            '<button type="button" class="btn btn-outline-secondary" onclick="komponenMove(' + i + ', -1)" ' + (i === 0 ? 'disabled' : '') + '><i class="ri-arrow-up-line"></i></button>' +
            '<button type="button" class="btn btn-outline-secondary" onclick="komponenMove(' + i + ', 1)" ' + (i === rows.length - 1 ? 'disabled' : '') + '><i class="ri-arrow-down-line"></i></button>' +
            '<button type="button" class="btn btn-outline-danger" onclick="komponenRemove(' + i + ')" title="Hapus komponen"><i class="ri-delete-bin-line"></i></button>' +
            '</div>';
    }).join('');
}

function komponenUuid() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

function komponenOpen(jenis) {
    komponenJenis = jenis === 'sas' ? 'sas' : 'sts';

    if (!komponenData[komponenJenis] || komponenData[komponenJenis].length === 0) {
        komponenData[komponenJenis] = komponenPreset.map(function (c) {
            return { id: c.id, label: c.label };
        });
    }

    document.getElementById('komponen-jenis').value = komponenJenis;
    document.getElementById('komponen-title').textContent = 'Kelola Komponen ' + komponenJenis.toUpperCase();
    document.getElementById('komponen-status').textContent = '';
    komponenRender();

    if (window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('komponenModal')).show();
    }
}

function komponenAdd() {
    komponenData[komponenJenis].push({ id: komponenUuid(), label: 'Komponen Baru' });
    komponenRender();
}

function komponenRemove(i) {
    if (!confirm('Hapus komponen "' + komponenData[komponenJenis][i].label + '"? Nilai komponen ini ikut terhapus.')) return;
    komponenData[komponenJenis].splice(i, 1);
    komponenRender();
}

function komponenMove(i, dir) {
    const rows = komponenData[komponenJenis];
    const j = i + dir;
    if (j < 0 || j >= rows.length) return;
    const tmp = rows[i];
    rows[i] = rows[j];
    rows[j] = tmp;
    komponenRender();
}

/**
 * Reset ke satu kolom: hapus seluruh komponen jenis ini.
 * Nilai akhir STS/SAS yang sudah tersimpan tetap dipakai pada kolom tunggal.
 */
function komponenReset() {
    const jenis = komponenJenis.toUpperCase();
    const labels = (komponenData[komponenJenis] || []).map(function (c) { return c.label; }).join(', ');

    if (!confirm('Kembalikan ' + jenis + ' ke satu kolom nilai?\n\nKomponen (' + (labels || 'semua') + ') akan dihapus beserta nilai komponennya.\nNilai akhir ' + jenis + ' yang sudah tersimpan tetap dipertahankan pada kolom tunggal.')) {
        return;
    }

    komponenData[komponenJenis] = [];
    komponenRender();
    komponenSave();
}

function komponenSave() {
    const status = document.getElementById('komponen-status');
    status.textContent = 'Menyimpan…';

    fetch(komponenUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify({
            jenis: komponenJenis,
            columns: (komponenData[komponenJenis] || []).map(function (c) {
                return { id: c.id, label: c.label };
            }),
        }),
    })
    .then(res => res.ok ? res.json() : Promise.reject(res))
    .then(function (data) {
        if (data && data.success) {
            status.textContent = 'Tersimpan! Memuat ulang…';
            window.location.reload();
        } else {
            status.textContent = (data && data.message) ? data.message : 'Gagal menyimpan komponen.';
        }
    })
    .catch(function () {
        status.textContent = 'Gagal menyimpan komponen.';
    });
}

// Nilai akhir STS/SAS ditampilkan langsung saat komponen diisi.
document.addEventListener('input', function (event) {
    const input = event.target;
    if (! input.classList || ! input.classList.contains('komponen-input')) return;

    const jenis = input.dataset.jenis;
    const studentId = input.dataset.student;
    let sum = 0;
    let count = 0;

    document.querySelectorAll('.komponen-input[data-jenis="' + jenis + '"][data-student="' + studentId + '"]').forEach(function (el) {
        const value = parseFloat(el.value);
        if (! isNaN(value)) { sum += value; count++; }
    });

    const output = document.getElementById(jenis + '-final-' + studentId);
    if (output) {
        output.textContent = count > 0 ? String(parseFloat((sum / count).toFixed(2))) : '---';
    }
});
</script>
@if($mapelIsTahfidz ?? false)
<script>
// Autosave tab Mapel tahfidz — perilaku SAMA dengan tab mapel umum
// (debounce 1,5 detik, indikator pojok atas, Undo/Redo tabel).
(function () {
    const form = document.getElementById('formMapelTahfidz');
    if (! form) return;

    const indDot = document.getElementById('autosave-dot');
    const indText = document.getElementById('autosave-text');
    const keys = ['npt', 'ziyadah', 'rabt', 'tikrar', 'sts', 'sas'];
    let timer = null;
    let saving = false;

    function setIndicator(cls, text) {
        if (indDot) indDot.className = 'rounded-circle ' + cls;
        if (indText) indText.textContent = text;
    }

    function showSuccess() {
        const time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        setIndicator('bg-success', 'Tersimpan ' + time);
        setTimeout(function () { setIndicator('bg-secondary', 'Menunggu…'); }, 3000);
    }

    function showError(msg) {
        setIndicator('bg-danger', msg || 'Gagal menyimpan');
        setTimeout(function () { setIndicator('bg-secondary', 'Menunggu…'); }, 4000);
    }

    // Bobot milik departemen: dibaca sekali dari server, tidak bisa diubah guru.
    const DEPT_WEIGHTS = @json($thfWeights ?? []);

    function weights() {
        const out = {};
        keys.forEach(function (k) {
            out[k] = parseFloat(DEPT_WEIGHTS[k]) || 0;
        });
        return out;
    }

    function recalcRow(tr) {
        const w = weights();
        let weighted = 0;
        let totalW = 0;

        keys.forEach(function (k) {
            const input = tr.querySelector('.tahfidz-score-input[data-key="' + k + '"]');
            const v = input && input.value !== '' ? parseFloat(input.value) : null;
            if (v !== null && ! isNaN(v)) {
                weighted += v * w[k];
                totalW += w[k];
            }
        });

        const nr = totalW > 0 ? (weighted / totalW) : null;
        const out = tr.querySelector('[id^="nrf-"]');
        if (out) out.textContent = nr !== null ? nr.toFixed(1) : '---';

        const jml = tr.querySelector('[id^="jml-"]');
        if (jml && nr !== null) {
            const bobot = parseFloat(jml.getAttribute('data-bobot')) || 0;
            jml.textContent = (bobot * nr).toFixed(1);
            jml.classList.remove('bg-light');
        }
    }

    function save() {
        if (saving) return;
        saving = true;
        setIndicator('bg-warning', 'Menyimpan…');

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
            credentials: 'same-origin'
        }).then(function (r) {
            return r.json().catch(function () {
                return { success: false, message: 'Sesi berakhir — muat ulang halaman (Ctrl+Shift+R).' };
            });
        }).then(function (json) {
            if (json && json.success === true) {
                showSuccess();
            } else {
                showError(json && json.message ? json.message : 'Gagal menyimpan');
            }
        }).catch(function () {
            showError('Gagal menyimpan');
        }).finally(function () {
            saving = false;
        });
    }

    function triggerSave() {
        clearTimeout(timer);
        timer = setTimeout(save, 1500);
    }

    form.addEventListener('input', function (e) {
        if (e.target.classList.contains('tahfidz-score-input')) {
            recalcRow(e.target.closest('tr'));
        }
        triggerSave();
    });

    form.addEventListener('submit', function (e) { e.preventDefault(); save(); });

})();
</script>
@endif
@include('partials.paste-grid')
@endsection
