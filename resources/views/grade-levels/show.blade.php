@extends('layouts.master')
@section('title') {{ $gradeLevel->name }} @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $periodeLabel = trim(($selectedAy?->name ?? '').' '.($selectedAy?->semester_text ?? ucfirst($semester)));

        // Ringkasan per asal mapel (Dinas / Pondok / Dinas + Pondok) — sama
        // dengan kategori master mapel yang berlaku sekarang.
        $jumlahAsal = ['pondok' => 0, 'pondok_dinas' => 0, 'dinas' => 0, 'belum' => 0];
        foreach ($gradeLevelSubjects as $gls) {
            $jumlahAsal[$gls->subject_source ?? 'belum'] = ($jumlahAsal[$gls->subject_source ?? 'belum'] ?? 0) + 1;
        }

        $badgeAsal = [
            'pondok' => 'bg-success-subtle text-success',
            'pondok_dinas' => 'bg-warning-subtle text-warning',
            'dinas' => 'bg-primary-subtle text-primary',
        ];
        $labelAsal = \App\Models\GradeLevelSubject::SOURCE_OPTIONS;

        // Penjurusan hanya berlaku untuk Kelas 11 & 12; tingkat lain tidak
        // menampilkan kolom/pilihan jurusan sama sekali.
        $pakaiJurusan = $gradeLevel->raporSubjectsPerStudyGroup();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">Data Kelas</a> @endslot
        @slot('title') {{ $gradeLevel->name }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- PERIODE ─────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center mb-0">
                <div class="col-auto">
                    <span class="text-muted small fw-semibold"><i class="ri-calendar-event-line me-1"></i>Periode</span>
                </div>
                <div class="col-md-4 col-lg-3">
                    <select name="ay_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ ($selectedAy?->id ?? '') == $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }} ({{ $ay->semester_text ?? ucfirst($ay->semester ?? 'ganjil') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    @if($isArchive)
                        <span class="badge bg-warning-subtle text-warning" title="Bukan tahun ajaran aktif — data lama, bukan struktur yang sedang berjalan">
                            <i class="ri-archive-line me-1"></i>Arsip
                        </span>
                    @else
                        <span class="badge bg-success-subtle text-success">Tahun ajaran aktif</span>
                    @endif
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('user.kurikulum.index', ['userId' => $userId, 'grade_level_id' => $gradeLevel->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $semester]) }}"
                       class="btn btn-soft-primary btn-sm">
                        <i class="ri-route-line me-1"></i>Buka di Peta Kurikulum
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- STATS RINGKASAN ─────────────────────────────── --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-book-2-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Mapel</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $gradeLevelSubjects->count() }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Mapel pada periode ini</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-time-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Jam/Minggu</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $gradeLevelSubjects->sum('allocation_hours') }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">JP</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Akumulasi alokasi JP</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-home-4-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Mapel Pondok</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $jumlahAsal['pondok'] + $jumlahAsal['pondok_dinas'] }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-flag-2-line me-1"></i>Pondok {{ $jumlahAsal['pondok'] }} · Pondok + Dinas {{ $jumlahAsal['pondok_dinas'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-government-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Mapel Dinas</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $jumlahAsal['dinas'] }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-information-line me-1"></i>
                        @if($jumlahAsal['belum'] > 0)
                            {{ $jumlahAsal['belum'] }} belum ditandai di Peta
                        @else
                            Kurikulum Dinas
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- KOLOM INFO ─────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0"><i class="ri-information-line text-primary me-1"></i>Informasi Tingkat</h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown"><i class="ri-more-fill"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('user.grade-levels.edit', ['userId' => $userId, 'id' => $gradeLevel->id]) }}">
                                    <i class="ri-pencil-line me-2"></i>Edit</a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">
                                    <i class="ri-arrow-left-line me-2"></i>Kembali</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr><td class="text-muted py-2" style="width:130px;font-size:0.82rem">Nama</td>
                                <td class="fw-semibold py-2" style="font-size:0.9rem">{{ $gradeLevel->name }}</td>
                            </tr>
                            <tr><td class="text-muted py-2" style="font-size:0.82rem">Kode</td>
                                <td class="py-2"><code style="font-size:0.85rem">{{ $gradeLevel->code ?? '-' }}</code></td>
                            </tr>
                            <tr><td class="text-muted py-2" style="font-size:0.82rem">Tingkat Angka</td>
                                <td class="py-2"><span class="badge bg-dark">{{ $gradeLevel->level }}</span></td>
                            </tr>
                            <tr><td class="text-muted py-2" style="font-size:0.82rem">Fase</td>
                                <td class="py-2">
                                    @if($gradeLevel->fase)
                                        <span class="badge bg-info-subtle text-info">{{ $gradeLevel->fase }}</span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted py-2" style="font-size:0.82rem">Sekolah</td>
                                <td class="py-2" style="font-size:0.85rem">
                                    @if($gradeLevel->school)
                                        <a href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $gradeLevel->school_id]) }}"
                                           class="text-primary text-decoration-none">
                                            <i class="ri-government-line me-1"></i>{{ $gradeLevel->school->name }}
                                        </a>
                                    @else - @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted py-2" style="font-size:0.82rem">Status</td>
                                <td class="py-2">
                                    @if($gradeLevel->is_active)
                                        <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line me-1"></i>Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary"><i class="ri-close-circle-line me-1"></i>Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted py-2" style="font-size:0.82rem">Rombel {{ $periodeLabel }}</td>
                                <td class="py-2">
                                    <span class="badge bg-primary-subtle text-primary">{{ $studyGroups->count() }} rombel</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="p-3 border-top">
                        <a href="{{ route('user.grade-levels.edit', ['userId' => $userId, 'id' => $gradeLevel->id]) }}"
                           class="btn btn-soft-primary w-100 btn-sm">
                            <i class="ri-pencil-line me-1"></i>Edit Informasi
                        </a>
                    </div>
                </div>
            </div>

            {{-- ROMBEL & JURUSAN — hanya Kelas 11 & 12 ────── --}}
            @if($pakaiJurusan)
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h6 class="mb-0"><i class="ri-team-line text-primary me-1"></i>Rombel &amp; Jurusan</h6>
                    <p class="text-muted mb-0" style="font-size:0.78rem">
                        Jurusan menentukan mapel apa yang diterima rombel: mapel bertanda jurusan hanya masuk ke rombel dengan jurusan itu.
                    </p>
                </div>
                <div class="card-body p-0">
                    @forelse($studyGroups as $sg)
                        <div class="d-flex align-items-center gap-2 px-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div class="flex-grow-1">
                                <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $sg->id]) }}"
                                   class="fw-semibold link-primary" style="font-size:0.88rem">{{ $sg->name }}</a>
                                <div class="text-muted" style="font-size:0.75rem">
                                    {{ $sg->homeroomTeacher?->name ?? 'Wali kelas belum diatur' }}
                                </div>
                            </div>
                            <form method="POST"
                                  action="{{ route('user.grade-levels.rombels.peminatan', ['userId' => $userId, 'id' => $gradeLevel->id, 'groupId' => $sg->id]) }}"
                                  class="d-flex align-items-center gap-1">
                                @csrf
                                @method('PUT')
                                <select name="peminatan" class="form-select form-select-sm" style="width:118px" onchange="this.form.submit()">
                                    <option value="" {{ blank($sg->peminatan) ? 'selected' : '' }}>Tanpa jurusan</option>
                                    @foreach(\App\Models\StudyGroup::PEMINATAN_OPTIONS as $value => $label)
                                        <option value="{{ $value }}" {{ $sg->peminatan === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <noscript><button class="btn btn-sm btn-primary">Simpan</button></noscript>
                            </form>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-muted mb-2" style="font-size:0.85rem">Belum ada rombel pada periode ini.</p>
                            <a href="{{ route('user.study-groups.create', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">
                                <i class="ri-add-line me-1"></i>Tambah Rombel
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
            @endif
        </div>

        {{-- KOLOM MAPEL + KKTP ─────────────────────── --}}
        <div class="col-lg-8">
            <div class="card mb-0">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h6 class="mb-0">
                                <i class="ri-book-2-line text-success me-1"></i>
                                Mata Pelajaran &amp; KKTP
                                <span class="badge bg-success rounded-pill ms-1">{{ $gradeLevelSubjects->count() }}</span>
                            </h6>
                            <p class="text-muted mb-0" style="font-size:0.78rem">
                                {{ $gradeLevelSubjects->sum('allocation_hours') }} jp/minggu &mdash; {{ $periodeLabel }}
                            </p>
                        </div>
                        <div class="col-sm-auto text-sm-end">
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                                <i class="ri-add-circle-line me-1"></i>Tambah Mapel
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($gradeLevelSubjects->isEmpty())
                        <div class="text-center py-5">
                            <div class="avatar-lg mx-auto mb-3">
                                <div class="avatar-title bg-light rounded-circle">
                                    <i class="ri-book-line fs-2 text-muted"></i>
                                </div>
                            </div>
                            <h5 class="text-muted">Belum ada mata pelajaran</h5>
                            <p class="text-muted mb-3" style="font-size:0.85rem">Tambahkan mapel terlebih dahulu pada periode {{ $periodeLabel }}</p>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                                <i class="ri-add-circle-line me-1"></i>Tambah Mapel
                            </button>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle table-freeze mb-0">
                                <thead class="table-light text-muted" style="font-size:0.72rem">
                                    <tr>
                                        <th class="text-center text-uppercase" style="width:45px">#</th>
                                        <th class="text-uppercase">Kode</th>
                                        <th class="text-uppercase">Mata Pelajaran</th>
                                        <th class="text-center text-uppercase">Kategori</th>
                                        @if($pakaiJurusan)
                                            <th class="text-center text-uppercase">Jurusan</th>
                                        @endif
                                        <th class="text-center text-uppercase">Urutan</th>
                                        <th class="text-center text-uppercase">Jam/Mg</th>
                                        <th class="text-center text-uppercase">KKTP</th>
                                        <th class="text-center text-uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gradeLevelSubjects as $i => $gls)
                                        @php
                                            $asal = $gls->subject_source;
                                            $kktp = $kktpMap[$gls->subject_id] ?? null;
                                            $trackUmum = \App\Services\Academic\RombelSubjectProvisioner::isTrackUmum($gls->track);
                                        @endphp
                                        <tr>
                                            <td class="text-center text-muted">{{ $i + 1 }}</td>
                                            <td><code style="font-size:0.8rem">{{ $gls->subject->code ?? '-' }}</code></td>
                                            <td><span class="fw-semibold" style="font-size:0.88rem">{{ $gls->subject->name }}</span></td>
                                            <td class="text-center">
                                                @if($asal)
                                                    <span class="badge {{ $badgeAsal[$asal] ?? 'bg-secondary-subtle text-secondary' }}">
                                                        {{ $labelAsal[$asal] ?? $asal }}
                                                    </span>
                                                @else
                                                    <a href="{{ route('user.kurikulum.index', ['userId' => $userId, 'grade_level_id' => $gradeLevel->id]) }}"
                                                       class="badge bg-secondary-subtle text-secondary text-decoration-none"
                                                       title="Asal mapel belum diatur — tetapkan di Peta Kurikulum">
                                                        <i class="ri-link me-1"></i>Atur di Peta
                                                    </a>
                                                @endif
                                            </td>
                                            @if($pakaiJurusan)
                                                <td class="text-center">
                                                    @if($trackUmum)
                                                        <span class="text-muted">Semua</span>
                                                    @else
                                                        <span class="badge bg-info-subtle text-info">{{ strtoupper($gls->track) }}</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="text-center">
                                                @if($gls->sort_order !== null)
                                                    {{ $gls->sort_order }}
                                                @else
                                                    <span class="text-muted" title="Belum bernomor — rapor mengurutkan berdasarkan nama">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning-subtle text-warning border">{{ $gls->allocation_hours }} jp</span>
                                            </td>
                                            <td class="text-center">
                                                @if($kktp)
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25"
                                                          style="font-size:0.8rem" title="KKTP: {{ $kktp->kkm_score ?? $kktp->kktp_score ?? '-' }}">
                                                        <i class="ri-check-line me-1"></i>{{ $kktp->kktp_score ?? $kktp->kkm_score }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.8rem">
                                                        <i class="ri-minus-line me-1"></i>-
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <button type="button" class="btn btn-soft-danger btn-sm px-2 py-1"
                                                        data-bs-toggle="modal" data-bs-target="#removeSubjectModal"
                                                        data-action="{{ route('user.grade-levels.subjects.remove', ['userId' => $userId, 'id' => $gradeLevel->id]) }}"
                                                        data-subject-id="{{ $gls->id }}"
                                                        data-subject-name="{{ $gls->subject->name }}"
                                                        title="Hapus"><i class="ri-delete-bin-2-line"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="{{ $pakaiJurusan ? 6 : 5 }}" class="text-end pe-3 fw-semibold text-muted" style="font-size:0.72rem;text-transform:uppercase">Total</td>
                                        <td class="text-center"><span class="badge bg-dark rounded-pill">{{ $gradeLevelSubjects->sum('allocation_hours') }} jp</span></td>
                                        <td class="text-center">
                                            <span class="badge bg-success rounded-pill">{{ $kktpMap->count() }}/{{ $gradeLevelSubjects->count() }}</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- TOMBOL ATUR KKTP / JP ────────────────────── --}}
                        <div class="p-3 border-top bg-light d-flex flex-wrap gap-2 align-items-center">
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#jpModal">
                                <i class="ri-time-line me-1"></i>Atur JP
                            </button>
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#kktpModal">
                                <i class="ri-bar-chart-line me-1"></i>Atur KKTP
                            </button>
                            <span class="text-muted ms-auto" style="font-size:0.75rem">
                                <i class="ri-information-line me-1"></i>
                                Urutan &amp; asal mapel diatur di
                                <a href="{{ route('user.kurikulum.index', ['userId' => $userId, 'grade_level_id' => $gradeLevel->id]) }}" class="link-primary">Peta Kurikulum</a>.
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: TAMBAH MAPEL ─────────────────────────── --}}
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">
                        <i class="ri-book-add-line me-1 text-success"></i>
                        Tambah Mapel ke {{ $gradeLevel->name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('user.grade-levels.subjects.add', ['userId' => $userId, 'id' => $gradeLevel->id]) }}">
                    @csrf
                    <input type="hidden" name="ay_filter" value="{{ $selectedAy?->id }}">
                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <i class="ri-information-line me-1"></i>
                            Mapel ditambahkan untuk periode <strong>{{ $periodeLabel }}</strong>.
                            @if($pakaiJurusan && $studyGroups->isNotEmpty())
                                Mapel <strong>Semua</strong> masuk ke {{ $studyGroups->count() }} rombel;
                                mapel berjurusan hanya masuk ke rombel jurusan itu.
                            @endif
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">-- Pilih Mapel --</option>
                                    @php $assignedIds = $gradeLevelSubjects->whereNull('track')->pluck('subject_id')->toArray(); @endphp
                                    @forelse($availableSubjects as $subj)
                                        @if(!in_array($subj->id, $assignedIds))
                                            <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                                        @endif
                                    @empty
                                        <option disabled>Semua mapel sudah ditambahkan</option>
                                    @endforelse
                                </select>
                            </div>
                            @if($pakaiJurusan)
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Jurusan</label>
                                    <select name="track" class="form-select">
                                        <option value="">Semua rombel</option>
                                        @foreach(\App\Models\StudyGroup::PEMINATAN_OPTIONS as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Alokasi Jam/Minggu</label>
                                <div class="input-group">
                                    <input type="number" name="allocation_hours" class="form-control" value="2" min="0" max="40">
                                    <span class="input-group-text bg-light">jp</span>
                                </div>
                                <small class="text-muted">1 jp = 40 menit</small>
                            </div>
                        </div>
                        @php $unassigned = $availableSubjects->filter(fn($s) => !in_array($s->id, $assignedIds)); @endphp
                        @if($unassigned->isNotEmpty())
                        <div class="mt-3 p-3 bg-light rounded">
                            <p class="text-muted mb-2" style="font-size:0.8rem">
                                <i class="ri-checkbox-circle-line me-1 text-success"></i>
                                <strong>{{ $unassigned->count() }}</strong> mapel belum ditambahkan
                            </p>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($unassigned as $subj)
                                    <span class="badge bg-light text-dark border">{{ $subj->name }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="ri-add-circle-line me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: ATUR KKTP ─────────────────────────────── --}}
    <div class="modal fade" id="kktpModal" tabindex="-1" aria-labelledby="kktpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="kktpModalLabel">
                        <i class="ri-bar-chart-line me-1 text-primary"></i>
                        Atur KKTP per Mapel — {{ $gradeLevel->name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('user.grade-levels.kktp.save', ['userId' => $userId, 'id' => $gradeLevel->id]) }}">
                    @csrf
                    <input type="hidden" name="ay_filter" value="{{ $selectedAy?->id }}">
                    <div class="modal-body">
                        {{-- Info semester & tahun ajaran --}}
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tahun Ajaran</label>
                                <select name="academic_year_id" class="form-select" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ ($selectedAy?->id ?? '') == $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Semester</label>
                                <select name="semester" class="form-select" required>
                                    <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-light border mb-3">
                            <i class="ri-information-line me-1 text-muted"></i>
                            <small class="text-muted">
                                <strong>KKTP</strong> (Kriteria Ketuntasan Tujuan Pembelajaran) adalah nilai minimal
                                yang harus dicapai siswa. Kosongkan field jika mapel tidak diujikan pada semester ini.
                            </small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light text-muted" style="font-size:0.75rem">
                                    <tr>
                                        <th class="text-uppercase">Mata Pelajaran</th>
                                        <th class="text-center text-uppercase">Kategori</th>
                                        <th class="text-center text-uppercase" style="width:110px">KKTP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gradeLevelSubjects as $gls)
                                        @php
                                            $asal = $gls->subject_source;
                                            $savedKktp = $kktpMap[$gls->subject_id]->kktp_score ?? $kktpMap[$gls->subject_id]->kkm_score ?? null;
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="fw-semibold" style="font-size:0.88rem">{{ $gls->subject->name }}</span>
                                                <br><code class="text-muted" style="font-size:0.75rem">{{ $gls->subject->code ?? '-' }}</code>
                                            </td>
                                            <td class="text-center">
                                                @if($asal)
                                                    <span class="badge {{ $badgeAsal[$asal] ?? 'bg-secondary-subtle text-secondary' }}">
                                                        {{ $labelAsal[$asal] ?? $asal }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">Belum ditandai</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="kktp[{{ $gls->subject_id }}]"
                                                           class="form-control text-center"
                                                           value="{{ $savedKktp ?? '' }}"
                                                           min="0" max="100" step="0.01"
                                                           placeholder="-">
                                                    <span class="input-group-text bg-light">%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i>Simpan KKTP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: ATUR JP PER MAPEL ─────────────────────── --}}
    <div class="modal fade" id="jpModal" tabindex="-1" aria-labelledby="jpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jpModalLabel">
                        <i class="ri-time-line me-1 text-primary"></i>
                        Atur JP per Mapel — {{ $gradeLevel->name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('user.grade-levels.subjects.hours', ['userId' => $userId, 'id' => $gradeLevel->id]) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="ay_filter" value="{{ $selectedAy?->id }}">
                    <div class="modal-body">
                        <div class="alert alert-light border mb-3">
                            <i class="ri-information-line me-1 text-muted"></i>
                            <small class="text-muted">
                                JP adalah alokasi jam pelajaran per minggu. Perubahan di sini juga
                                diterapkan ke mapel rombel agar leger, jadwal, dan perangkat memakai angka yang sama.
                            </small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light text-muted" style="font-size:0.75rem">
                                    <tr>
                                        <th class="text-uppercase">Mata Pelajaran</th>
                                        <th class="text-center text-uppercase">Kategori</th>
                                        @if($pakaiJurusan)
                                            <th class="text-center text-uppercase">Jurusan</th>
                                        @endif
                                        <th class="text-center text-uppercase" style="width:120px">JP / Minggu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gradeLevelSubjects as $gls)
                                        @php
                                            $asal = $gls->subject_source;
                                            $trackUmum = \App\Services\Academic\RombelSubjectProvisioner::isTrackUmum($gls->track);
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="fw-semibold" style="font-size:0.88rem">{{ $gls->subject->name }}</span>
                                                <br><code class="text-muted" style="font-size:0.75rem">{{ $gls->subject->code ?? '-' }}</code>
                                            </td>
                                            <td class="text-center">
                                                @if($asal)
                                                    <span class="badge {{ $badgeAsal[$asal] ?? 'bg-secondary-subtle text-secondary' }}">
                                                        {{ $labelAsal[$asal] ?? $asal }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">Belum ditandai</span>
                                                @endif
                                            </td>
                                            @if($pakaiJurusan)
                                                <td class="text-center">
                                                    @if($trackUmum)
                                                        <span class="text-muted">Semua</span>
                                                    @else
                                                        <span class="badge bg-info-subtle text-info">{{ strtoupper($gls->track) }}</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="text-center">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="hours[{{ $gls->id }}]"
                                                           class="form-control text-center"
                                                           value="{{ $gls->allocation_hours }}"
                                                           min="0" max="40" step="1">
                                                    <span class="input-group-text bg-light">JP</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i>Simpan JP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal konfirmasi hapus mapel — template lord-icon/zoomIn --}}
    <form id="removeSubjectForm" method="POST" action="" class="d-none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="subject_id" id="removeSubjectId" value="">
    </form>

    <x-modal-confirm id="removeSubjectModal"
        icon="https://cdn.lordicon.com/tdrtiskw.json"
        primary-color="#f7b84b" secondary-color="#005981"
        title="Hapus Mapel dari Tingkat?"
        message="Mapel <strong id=&quot;removeSubjectName&quot;>ini</strong> akan dihapus dari tingkat <strong>{{ $gradeLevel->name }}</strong> dan dinonaktifkan pada rombel yang menerimanya. Riwayat nilai tetap aman."
        form-id="removeSubjectForm"
        submit-label="Ya, Hapus"
        submit-icon="ri-delete-bin-2-line"
        submit-class="btn-danger" />

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('removeSubjectModal');
            if (!modal) return;

            modal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;

                var form = document.getElementById('removeSubjectForm');
                if (form) form.setAttribute('action', trigger.getAttribute('data-action') || '');

                var idField = document.getElementById('removeSubjectId');
                if (idField) idField.value = trigger.getAttribute('data-subject-id') || '';

                var nameField = document.getElementById('removeSubjectName');
                if (nameField) nameField.textContent = trigger.getAttribute('data-subject-name') || 'ini';
            });
        });
    </script>
    @endpush
@endsection
