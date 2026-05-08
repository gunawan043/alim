@extends('layouts.master')
@section('title') Leger Nilai STS — {{ $studyGroup->name ?? '' }} @endsection

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
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
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
            <div class="card h-100">
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
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-file-text-line text-success"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Mata Pelajaran</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $totalMapel }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-user-3-line text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Jumlah Santri</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $totalSantri }}</h3>
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
                        Leger Nilai STS — {{ $studyGroup->name ?? '' }}
                    </h5>
                </div>
                @if($isPrivileged)
                <div class="col-sm-auto">
                    <span class="badge bg-info-subtle text-info me-1" style="font-size:11px;padding:4px 10px;">
                        <i class="ri-user-settings-line align-bottom me-1"></i>Mode TU / Waka
                    </span>
                    {{-- Action Buttons --}}
                    <a href="{{ route('user.schools.nilai-kelas.rapor', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester]) }}"
                    class="btn btn-outline-primary btn-md me-1">
                        <i class="ri-file-paper-2-line me-1"></i> Cetak Rapor
                    </a>
                    <a href="{{ route('user.schools.nilai-kelas.leger.download', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester]) }}"
                    class="btn btn-outline-secondary btn-md">
                        <i class="ri-download-2-line me-1"></i> Download Leger (Excel)
                    </a>
                </div>
                @endif
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

        {{-- Wizard Tabs --}}
        <ul class="nav nav-tabs px-4 pt-3" role="tablist" style="overflow-x:auto;flex-wrap:nowrap;white-space:nowrap;">
            <li class="nav-item">
                <a href="{{ route('user.schools.nilai-kelas.sts', array_merge(['userId' => $userId, 'studyGroupId' => $studyGroup->id], request()->query(), ['tab' => 'leger'])) }}"
                   class="nav-link {{ $activeTab === 'leger' ? 'active' : '' }}"
                   style="font-size:13px;">
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
                       style="font-size:13px;">
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
                        <p class="text-muted mb-0" style="font-size:13px;">Pastikan Buku Admin Guru sudah terisi.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('user.schools.nilai-kelas.sts.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}" id="formLeger">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                        <input type="hidden" name="tab" value="leger">

                        <div class="table-responsive">
                            <table id="tab-leger" class="table table-bordered table-hover align-middle mb-0" style="min-width: max-content;">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th rowspan="2" style="min-width:35px;background:#f8fafc;" class="text-muted">No</th>
                                        <th rowspan="2" style="min-width:70px;background:#f8fafc;" class="text-muted">NIS</th>
                                        <th rowspan="2" class="text-start align-middle text-muted" style="min-width:180px;background:#f8fafc;">
                                            <i class="ri-user-line me-1"></i>Nama Santri
                                        </th>
                                        @foreach($subjectMap as $subject)
                                            <th rowspan="2" style="min-width:70px;background:#f8fafc;" title="{{ $subject->name }}">
                                                <div style="hite-space:nowrap;letter-spacing:0.3px;text-orientation:upright;">
                                                    {{ $subject->code ?? $subject->name }}
                                                </div>
                                            </th>
                                        @endforeach
                                        <th rowspan="3" style="min-width:85px;background:#e2e8f0;" class="text-dark align-middle">Jumlah</th>
                                        <th rowspan="3" style="min-width:85px;background:#e2e8f0;" class="text-dark align-middle">Rata-rata</th>
                                        <th rowspan="3" style="min-width:60px;background:#e2e8f0;" class="text-dark align-middle">Peringkat</th>
                                        <th rowspan="3" style="min-width:100px;background:#e2e8f0;" class="text-dark align-middle">Predikat</th>
                                        <th class="bg-secondary-subtle text-secondary" style="min-width:210px;" colspan="3">PRESENSI</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th rowspan="2" class="bg-success-subtle text-success" style="font-size:12px;">S</th>
                                        <th rowspan="2" class="bg-warning-subtle text-warning" style="font-size:12px;">I</th>
                                        <th rowspan="2" class="bg-danger-subtle text-danger" style="font-size:12px;">A</th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-center fw-bold" style="background:#f1f5f9;vertical-align:middle;">
                                            <span class="badge bg-dark-subtle text-dark" style="font-size:12px;">
                                                <i class="ri-checkbox-circle-line me-1"></i>KKM
                                            </span>
                                        </th>
                                        @foreach($subjectMap as $subject)
                                            @php $book = $bookMap[$subject->id] ?? null; @endphp
                                            <th style="background:#f1f5f9;">
                                                @if($book)
                                                    <input type="number" class="form-control form-control-sm text-center"
                                                           name="leger_kkm[{{ $book->id }}]"
                                                           value="{{ $book?->kktp?->kkm_score ?? 75 }}"
                                                           min="0" max="100" step="0.01"
                                                           oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                           style="background:#f8fafc;border-color:#cbd5e1;font-weight:700;color:#475569;font-size:13px;"
                                                           title="KKM {{ $subject->name }}">
                                                @else
                                                    <span style="font-weight:700;color:#94a3b8;font-size:11px;">—</span>
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
                                                if ($n && $n->sts !== null) {
                                                    $jumlahSts += $n->sts;
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
                                            <td class="text-center text-muted" style="font-size:12px;">{{ $idx + 1 }}</td>
                                            <td class="text-center" style="font-size:12px;">{{ $student->nis ?? '-' }}</td>
                                            <td class="text-start" style="white-space:nowrap;">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-xs">
                                                        <span class="avatar-title rounded-circle bg-primary text-white" style="font-size:10px;">
                                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold" style="font-size:13px;">{{ $student->name }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            @foreach($subjectMap as $subject)
                                                @php
                                                    $book = $bookMap[$subject->id] ?? null;
                                                    $n = $book ? ($nilaiMap[$student->id][$book->id] ?? null) : null;
                                                    $kkm = $book?->kktp?->kkm_score ?? 75;
                                                    $stsVal = $n?->sts ?? null;
                                                    $belowKkm = $stsVal !== null && $stsVal < $kkm;
                                                @endphp
                                                <td style="text-align:center;">
                                                    @if($book)
                                                        <input type="number"
                                                               name="leger_sts[{{ $student->id }}][{{ $book->id }}][sts]"
                                                               value="{{ $stsVal !== null ? number_format($stsVal, 0, '.', '') : '' }}"
                                                               min="0" max="100" step="0.01"
                                                               oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                               placeholder="---"
                                                               @if($belowKkm)
                                                                   style="width:60px;text-align:center;border:0px solid #ef4444;background:#fef2f2;color:#dc2626;border-radius:4px;padding:2px 4px;font-size:14px;"
                                                               @else
                                                                   style="width:60px;text-align:center;border:0px solid #d1d5db;background:#fafafa00;color:#374151;border-radius:4px;padding:2px 4px;font-size:14px;"
                                                               @endif>
                                                    @else
                                                        <span class="text-muted" style="font-size:11px;">—</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            {{-- Jumlah --}}
                                            <td class="text-center fw-semibold" style="font-size:13px;color:#334155;">
                                                {{ $jumlahSts > 0 ? number_format($jumlahSts, 1) : '—' }}
                                            </td>
                                            {{-- Rata-rata --}}
                                            <td class="text-center fw-bold" style="font-size:13px;color:#1d4ed8;">
                                                {{ $avgVal !== null ? number_format($avgVal, 1) : '—' }}
                                            </td>
                                            {{-- Peringkat --}}
                                            <td class="text-center">
                                                @if($rankVal)
                                                    <span class="badge {{ $rankVal == 1 ? 'bg-warning text-dark' : 'bg-secondary-subtle text-secondary' }}"
                                                          style="font-size:12px;min-width:32px;">{{ $rankVal }}</span>
                                                @else
                                                    <span class="text-muted" style="font-size:12px;">—</span>
                                                @endif
                                            </td>
                                            {{-- Predikat --}}
                                            <td class="text-center">
                                                <span class="badge fw-bold
                                                    @if($avgVal === null) bg-light text-muted
                                                    @elseif($avgVal >= 95) bg-success text-white
                                                    @elseif($avgVal >= 90) bg-primary text-white
                                                    @elseif($avgVal >= 85) bg-info text-white
                                                    @elseif($avgVal >= 80) bg-warning text-dark
                                                    @elseif($avgVal >= 75) bg-secondary text-white
                                                    @else bg-danger text-white
                                                    @endif"
                                                    style="min-width:88px;font-size:11px;padding:4px 6px;">{{ $predikat }}</span>
                                            </td>
                                            {{-- PRESENSI S --}}
                                            <td class="text-center fw-semibold bg-success-subtle" style="color:#166534;font-size:13px;">
                                                {{ $pres['s'] ?? '—' }}
                                            </td>
                                            {{-- PRESENSI I --}}
                                            <td class="text-center fw-semibold bg-warning-subtle" style="color:#854d0e;font-size:13px;">
                                                {{ $pres['i'] ?? '—' }}
                                            </td>
                                            {{-- PRESENSI A --}}
                                            <td class="text-center fw-semibold bg-danger-subtle" style="color:#991b1b;font-size:13px;">
                                                {{ $pres['a'] ?? '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $subjectMap->count() + 10 }}" class="text-center text-muted py-4">
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
                                Isi STS per mapel. Klik tab mapel di atas untuk asesmen harian (S1–S6). Tersimpan otomatis saat mengetik.
                            </small>
                        </div>
                    </form>
                @endif
            @endif

            {{-- ========================================== --}}
            {{-- TAB: MAPEL --}}
            {{-- ========================================== --}}
            @if($activeTab === 'mapel' && $activeBook)
                <div class="px-4 py-3" style="background:#f8fafc;border-bottom:1px solid #e9ecef;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white" style="font-size:13px;">
                                <i class="ri-book-line me-1"></i>{{ $activeBook->subject->name }}
                            </span>
                            <span class="badge bg-secondary-subtle text-secondary text-capitalize">
                                {{ $activeBook->semester }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2" style="font-size:12px;">
                            <span class="badge bg-primary-subtle text-primary">
                                <i class="ri-bar-chart-line me-1"></i>RS = Rata-rata S1–S6 (50%)
                            </span>
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="ri-edit-2-line me-1"></i>STS (25%)
                            </span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('user.schools.nilai-kelas.sts.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}" id="formMapel">
                    @csrf
                    <input type="hidden" name="admin_book_id" value="{{ $activeBook->id }}">
                    <input type="hidden" name="tab" value="mapel">

                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th rowspan="2" class="align-middle" width="40">No</th>
                                    <th rowspan="2" class="align-middle" width="70">NIS</th>
                                    <th rowspan="2" class="text-start align-middle" width="220">Nama Santri</th>
                                    <th colspan="6">Asesmen Sumatif Harian</th>
                                    <th rowspan="2" class="align-middle bg-primary-subtle" width="80">
                                        RS<br><small class="fw-normal">(50%)</small>
                                    </th>
                                    <th rowspan="2" class="align-middle bg-warning-subtle" width="80">
                                        STS<br><small class="fw-normal">(25%)</small>
                                    </th>
                                </tr>
                                <tr>
                                    @foreach(['S1','S2','S3','S4','S5','S6'] as $s)
                                        <th width="10">{{ $s }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $i => $history)
                                    @php $student = $history->student; @endphp
                                    @php $existing = $nilaiMap[$student->id][$activeBook->id] ?? null; @endphp
                                    <tr>
                                        <td class="text-center text-muted fw-semibold" style="font-size:12px;">{{ $i + 1 }}</td>
                                        <td class="text-center" style="font-size:12px;">{{ $student->nis ?? '-' }}</td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-primary text-white" style="font-size:10px;">
                                                        {{ strtoupper(substr($student->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <span class="fw-medium" style="font-size:13px;">{{ $student->name }}</span>
                                            </div>
                                        </td>
                                        @foreach(['s1','s2','s3','s4','s5','s6'] as $s)
                                            <td style="text-align:center;">
                                                <input type="number" class="sumatif-input"
                                                       name="nilai[{{ $student->id }}][{{ $s }}]"
                                                       value="{{ $existing?->$s ?? '' }}"
                                                       placeholder="---"
                                                       min="0" max="100" step="0.01"
                                                       oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                       style="width:35px;text-align:center;text-align-last:center;border:0px solid #d1d5db;background:#fafafa00;color:#374151;border-radius:4px;padding:2px 4px;font-size:13px;">
                                            </td>
                                        @endforeach
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary fw-bold rs-display {{ $existing?->rs ? '' : 'bg-light text-muted' }}"
                                                  style="font-size:13px;padding:5px 10px;min-width:60px;">
                                                {{ $existing?->rs ? number_format($existing->rs, 1) : '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-center"
                                                   name="nilai[{{ $student->id }}][sts]"
                                                   value="{{ $existing?->sts ?? '' }}"
                                                   oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;"
                                                   min="0" max="100" step="0.01" placeholder="---"
                                                   style="text-align-last: center;background:#fffbeb;border-color:#fcd34d;">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center text-muted py-4">
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
                    <p class="text-muted mb-0" style="font-size:13px;">Klik tab mapel di atas untuk mulai input nilai.</p>
                </div>
            @endif

        </div>
    </div>
@endsection

@section('script')
<style>
    #tab-leger input[type="number"],
    #tab-mapel input[type="number"] {
        text-align: center;
        text-align-last: center;
    }
    #tab-leger input[type="number"]::placeholder,
    #tab-mapel input[type="number"]::placeholder {
        text-align: center;
        opacity: 0.5;
    }
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

document.addEventListener('DOMContentLoaded', function () {
    // Apply search filter on page load if query exists
    var initialQuery = document.getElementById('searchStudentInput').value;
    if (initialQuery) filterStudentRows(initialQuery);

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
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            })
            .then(function(res) {
                saveInFlight = false;
                if (!res.ok) {
                    return res.json().catch(function() { return { message: 'HTTP ' + res.status }; });
                }
                return res.json();
            })
            .then(function(data) {
                if (data && (data.success || data.message)) {
                    showSuccess();
                } else {
                    showError(data?.message || 'Gagal menyimpan');
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
@endsection