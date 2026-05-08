@extends('layouts.master')
@section('title') {{ $gradeLevel->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">Tingkat Kelas</a> @endslot
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

    {{-- STATS RINGKASAN ─────────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-primary bg-opacity-10 border-primary border-1 mb-0">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-1 text-uppercase" style="font-size:0.68rem;letter-spacing:0.06em;font-weight:600">Total Mapel</p>
                            <h4 class="mb-0">{{ $gradeLevelSubjects->count() }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary rounded-circle text-white fs-5"><i class="ri-book-2-line"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-success bg-opacity-10 border-success border-1 mb-0">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-1 text-uppercase" style="font-size:0.68rem;letter-spacing:0.06em;font-weight:600">Total Jam/Minggu</p>
                            <h4 class="mb-0">{{ $gradeLevelSubjects->sum('allocation_hours') }} <small class="fs-6 text-muted">jp</small></h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success rounded-circle text-white fs-5"><i class="ri-time-line"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-info bg-opacity-10 border-info border-1 mb-0">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-1 text-uppercase" style="font-size:0.68rem;letter-spacing:0.06em;font-weight:600">Mapel Nasional</p>
                            <h4 class="mb-0">{{ $gradeLevelSubjects->filter(fn($g) => ($g->subject->category ?? '') === 'nasional')->count() }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info rounded-circle text-white fs-5"><i class="ri-global-line"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-warning bg-opacity-10 border-warning border-1 mb-0">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-1 text-uppercase" style="font-size:0.68rem;letter-spacing:0.06em;font-weight:600">Muatan Lokal</p>
                            <h4 class="mb-0">{{ $gradeLevelSubjects->filter(fn($g) => ($g->subject->category ?? '') !== 'nasional')->count() }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning rounded-circle text-white fs-5"><i class="ri-home-line"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- KOLOM INFO ─────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card mb-0 h-100">
                <div class="card-header bg-light">
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
                            <tr><td class="text-muted py-2" style="font-size:0.82rem">Rombel Aktif</td>
                                <td class="py-2">
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $gradeLevel->studyGroups->where('is_active', true)->count() }} rombel
                                    </span>
                                </td>
                            </tr>
                            <tr><td class="text-muted py-2" style="font-size:0.82rem"> Wali Kelas</td>
                                <td class="py-2" style="font-size:0.82rem">
                                    @forelse($gradeLevel->studyGroups->where('is_active', true)->take(3)->filter(fn($sg) => $sg->homeroomTeacher) as $sg)
                                        <div><i class="ri-user-follow-line text-muted me-1"></i>{{ $sg->homeroomTeacher->name }}</div>
                                    @empty <span class="text-muted">-</span> @endforelse
                                    @if($gradeLevel->studyGroups->where('is_active', true)->filter(fn($sg) => $sg->homeroomTeacher)->count() > 3)
                                        <small class="text-muted">+{{ $gradeLevel->studyGroups->where('is_active', true)->filter(fn($sg) => $sg->homeroomTeacher)->count() - 3 }} lagi</small>
                                    @endif
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
        </div>

        {{-- KOLOM MAPEL + KKTP ─────────────────────── --}}
        <div class="col-lg-8">
            <div class="card mb-0">
                <div class="card-header bg-light">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h6 class="mb-0">
                                <i class="ri-book-2-line text-success me-1"></i>
                                Mata Pelajaran & KKTP
                                <span class="badge bg-success rounded-pill ms-1">{{ $gradeLevelSubjects->count() }}</span>
                            </h6>
                            <p class="text-muted mb-0" style="font-size:0.78rem">
                                {{ $gradeLevelSubjects->sum('allocation_hours') }} jp/minggu &mdash; Semester {{ $semester }} {{ $activeAy?->name ?? '' }}
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
                            <p class="text-muted mb-3" style="font-size:0.85rem">Tambahkan mapel terlebih dahulu</p>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                                <i class="ri-add-circle-line me-1"></i>Tambah Mapel
                            </button>
                        </div>
                    @else
                        {{-- FILTER TAHUN AJARAN & SEMESTER ─────────────── --}}
                        <div class="px-3 pt-3 pb-2 border-bottom bg-light">
                            <form method="GET" class="row g-2 align-items-end mb-0">
                                <div class="col-md-4">
                                    <label class="form-label" style="font-size:0.75rem">Tahun Ajaran</label>
                                    <select name="ay_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ ($activeAy?->id ?? '') == $ay->id ? 'selected' : '' }}>
                                                {{ $ay->name }} ({{ $ay->semester_text ?? $ay->semester }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light text-muted" style="font-size:0.72rem">
                                    <tr>
                                        <th class="text-center text-uppercase" style="width:45px">#</th>
                                        <th class="text-uppercase">Kode</th>
                                        <th class="text-uppercase">Mata Pelajaran</th>
                                        <th class="text-uppercase">Kategori</th>
                                        <th class="text-center text-uppercase">Jam/Mg</th>
                                        <th class="text-center text-uppercase">KKTP</th>
                                        <th class="text-center text-uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gradeLevelSubjects as $i => $gls)
                                        @php
                                            $cat = $gls->subject->category ?? 'nasional';
                                            $kktp = $kktpMap[$gls->subject_id] ?? null;
                                        @endphp
                                        <tr>
                                            <td class="text-center text-muted">{{ $i + 1 }}</td>
                                            <td><code style="font-size:0.8rem">{{ $gls->subject->code ?? '-' }}</code></td>
                                            <td><span class="fw-semibold" style="font-size:0.88rem">{{ $gls->subject->name }}</span></td>
                                            <td>
                                                @if($cat === 'nasional')
                                                    <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25">
                                                        <i class="ri-global-line me-1"></i>Nasional
                                                    </span>
                                                @elseif($cat === 'muatan_lokal')
                                                    <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25">
                                                        <i class="ri-home-4-line me-1"></i>Muatan Lokal
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">
                                                        <i class="ri-settings-line me-1"></i>Lokal
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning-subtle text-warning border">{{ $gls->allocation_hours }} jp</span>
                                            </td>
                                            <td class="text-center">
                                                @if($kktp)
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25"
                                                          style="font-size:0.8rem" title="KKM: {{ $kktp->kkm_score ?? '-' }}">
                                                        <i class="ri-check-line me-1"></i>{{ $kktp->kktp_score }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.8rem">
                                                        <i class="ri-minus-line me-1"></i>-
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <form method="POST" action="{{ route('user.grade-levels.subjects.remove', ['userId' => $userId, 'id' => $gradeLevel->id]) }}" class="d-inline">
                                                        @csrf @method('DELETE')
                                                        <input type="hidden" name="subject_id" value="{{ $gls->id }}">
                                                        <button type="submit" class="btn btn-soft-danger btn-sm px-2 py-1"
                                                            onclick="return confirm('Hapus {{ addslashes($gls->subject->name) }} dari {{ addslashes($gradeLevel->name) }}?')"
                                                            title="Hapus"><i class="ri-delete-bin-2-line"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end pe-3 fw-semibold text-muted" style="font-size:0.72rem;text-transform:uppercase">Total</td>
                                        <td class="text-center"><span class="badge bg-dark rounded-pill">{{ $gradeLevelSubjects->sum('allocation_hours') }} jp</span></td>
                                        <td class="text-center">
                                            <span class="badge bg-success rounded-pill">{{ $kktpMap->count() }}/{{ $gradeLevelSubjects->count() }}</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- TOMBOL ATUR KKTP ────────────────────── --}}
                        <div class="p-3 border-top bg-light">
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#kktpModal">
                                <i class="ri-bar-chart-line me-1"></i>Atur KKTP
                            </button>
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
                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <i class="ri-information-line me-1"></i>
                            Mapel yang sudah ditambahkan tidak akan tampil di daftar ini.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">-- Pilih Mapel --</option>
                                    @php $assignedIds = $gradeLevelSubjects->pluck('subject_id')->toArray(); @endphp
                                    @forelse($availableSubjects as $subj)
                                        @if(!in_array($subj->id, $assignedIds))
                                            <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                                        @endif
                                    @empty
                                        <option disabled>Semua mapel sudah ditambahkan</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-md-4">
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
                    <div class="modal-body">
                        {{-- Info semester & tahun ajaran --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tahun Ajaran</label>
                                <select name="academic_year_id" class="form-select" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ ($activeAy?->id ?? '') == $ay->id ? 'selected' : '' }}>
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
                                            $cat = $gls->subject->category ?? 'nasional';
                                            $savedKktp = $kktpMap[$gls->subject_id]->kktp_score ?? null;
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="fw-semibold" style="font-size:0.88rem">{{ $gls->subject->name }}</span>
                                                <br><code class="text-muted" style="font-size:0.75rem">{{ $gls->subject->code ?? '-' }}</code>
                                            </td>
                                            <td class="text-center">
                                                @if($cat === 'nasional')
                                                    <span class="badge bg-primary-subtle text-primary">Nasional</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">Muatan Lokal</span>
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
@endsection
