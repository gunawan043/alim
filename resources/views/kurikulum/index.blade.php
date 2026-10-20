@extends('layouts.master')
@section('title', 'Kurikulum Pembelajaran')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $totalMapel = $rows->unique('subject_id')->count();
        $atpCount = $rows->whereNotNull('atp_id')->count();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Kurikulum Pembelajaran @endslot
    @endcomponent

    @if($isArchive ?? false)
        <div class="alert alert-warning py-2 px-3 small d-flex align-items-center gap-2" role="alert">
            <i class="ri-archive-line fs-5"></i>
            <div>
                Menampilkan <strong>arsip Peta Kurikulum</strong> tahun ajaran
                <strong>{{ $academicYears->firstWhere('id', $academicYearId)?->name ?? '-' }}
                    ({{ $academicYears->firstWhere('id', $academicYearId)?->semester_text ?? '-' }})</strong>.
                Data periode lain tidak berubah oleh pengaturan di periode ini.
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-calendar-check-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Minggu Efektif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($mingguEfektif) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-information-line me-1"></i>
                        {{ $ringkasan && ($ringkasan['is_persisted'] ?? false) ? 'Tersimpan dari Kalender' : 'Hitung langsung dari Kalender' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-calendar-2-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Hari Efektif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($ringkasan['total_hari_efektif'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Dari Pekan Efektif semester ini</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-book-2-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Mapel Terdaftar</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalMapel) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-stack-line me-1"></i>{{ $rows->count() }} baris jenjang × mapel</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-route-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">ATP Tersusun</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($atpCount) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Dari {{ $rows->count() }} kombinasi</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="kurikulumList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Peta Kurikulum per Jenjang &amp; Mapel</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $rows->count() }} baris</span>
                                @if($curriculumProfile ?? null)
                                    <span class="badge bg-success-subtle text-success ms-1">{{ $curriculumProfile->name }}</span>
                                @endif
                                <span class="text-muted small ms-2">Kalender → Pekan Efektif → JP Efektif → CP → TP → ATP.</span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form method="GET" action="{{ route('user.kurikulum.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
                                    <select name="academic_year_id" class="form-select" style="width:200px" onchange="this.form.submit()">
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }} ({{ $ay->semester_text }})</option>
                                        @endforeach
                                    </select>
                                    <select name="semester" class="form-select" style="width:110px" onchange="this.form.submit()">
                                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                                    </select>
                                    <a href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                </form>
                                @if(($canPrepare ?? false) && $academicYearId && $academicYears->count() > 1)
                                    <form id="siapkanPetaForm" method="POST"
                                          action="{{ route('user.kurikulum.siapkan', ['userId' => $userId]) }}"
                                          class="d-flex flex-wrap gap-2">
                                        @csrf
                                        <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                                        <select name="source_academic_year_id" class="form-select" style="width:210px" title="TA sumber salinan">
                                            @foreach($academicYears as $ay)
                                                @continue($ay->id == $academicYearId)
                                                <option value="{{ $ay->id }}" {{ (string) $activeAcademicYearId === (string) $ay->id ? 'selected' : '' }}>Salin dari {{ $ay->name }} ({{ $ay->semester_text }})</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-outline-success"
                                                data-bs-toggle="modal" data-bs-target="#siapkanPetaModal"
                                                title="Siapkan Peta Kurikulum untuk TA terpilih">
                                            <i class="ri-archive-drawer-line align-bottom me-1"></i> Siapkan TA Ini
                                        </button>
                                    </form>
                                @endif
                                <div class="dropdown">
                                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-apps-2-line align-bottom me-1"></i> Buka Modul
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.cp.index', ['userId' => $userId]) }}"><i class="ri-flag-2-line me-1"></i> CP</a></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.tp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-list-check-2 me-1"></i> TP</a></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-route-line me-1"></i> ATP</a></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.prota.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-calendar-schedule-line me-1"></i> PROTA</a></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.prosem.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-calendar-2-line me-1"></i> PROSEM</a></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-line-chart-line me-1"></i> Realisasi</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-booklet-line me-1"></i> Perangkat Pembelajaran</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Tingkat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['grade_level_id' => null]) }}" class="filter-badge {{ ! $gradeLevelId ? 'active' : '' }}">Semua</a>
                        @foreach(($gradeLevelOptions ?? collect()) as $g)
                            <a href="{{ request()->fullUrlWithQuery(['grade_level_id' => $g->id]) }}" class="filter-badge {{ (string) $gradeLevelId === (string) $g->id ? 'active' : '' }}">{{ $g->name }}</a>
                        @endforeach
                        <span class="text-muted small ms-2 me-2">·</span>
                        <span class="text-muted small fw-semibold me-2">Semester:</span>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'ganjil']) }}" class="filter-badge {{ $semester === 'ganjil' ? 'active' : '' }}">Ganjil</a>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'genap']) }}" class="filter-badge {{ $semester === 'genap' ? 'active' : '' }}">Genap</a>
                        <span class="text-muted small ms-2 me-2">·</span>
                        <span class="text-muted small">{{ $totalMapel }} mapel · {{ $atpCount }} ATP tersusun</span>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted small fw-semibold"><i class="ri-stack-line me-1"></i>Total JP per Tingkat:</span>
                        @foreach(($jpSummary ?? collect()) as $sum)
                            <span class="badge bg-success-subtle text-success">{{ $sum['name'] }}: {{ rtrim(rtrim(number_format($sum['total'], 1, ',', '.'), '0'), ',') }} JP</span>
                        @endforeach
                    </div>
                </div>

                <form id="kktpFormPeta" method="POST" action="{{ route('user.kurikulum.kktp.save', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Jenjang</th>
                                <th class="text-center">Fase</th>
                                <th>Mata Pelajaran</th>
                                <th class="text-center">JP/Minggu</th>
                                <th class="text-center">JP Efektif</th>
                                <th class="text-center" title="Kriteria Ketercapaian Tujuan Pembelajaran (0-100)">N-KKTP</th>
                                <th class="text-center">CP</th>
                                <th class="text-center">TP</th>
                                <th>ATP</th>
                                <th class="text-center">Rombel</th>
                                <th class="text-center" title="Jumlah guru pengampu mapel ini pada TA terpilih di satuan pendidikan ini (tidak menghitung lintas lembaga)">Guru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td class="fw-medium">{{ $row['grade'] }}</td>
                                    <td class="text-center">
                                        @if($row['fase'])
                                            <span class="badge bg-info-subtle text-info">{{ $row['fase'] }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['subject'] }}</td>
                                    <td class="text-center">{{ $row['weekly_hours'] }}</td>
                                    <td class="text-center fw-semibold">{{ $row['jp_efektif'] }}</td>
                                    <td class="text-center">
                                        @if($canEditKktp ?? false)
                                            <input type="number"
                                                   form="kktpFormPeta"
                                                   name="kktp[{{ $row['grade_level_id'] }}][{{ $row['subject_id'] }}]"
                                                   value="{{ $row['kktp'] ?? '' }}"
                                                   min="0" max="100" placeholder="—"
                                                   class="form-control form-control-sm text-center"
                                                   style="width:80px;margin:0 auto"
                                                   title="N-KKTP (0-100)">
                                        @else
                                            <span class="badge {{ ($row['kktp'] ?? null) !== null ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $row['kktp'] ?? '—' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $row['cp_count'] > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $row['cp_count'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $row['tp_count'] > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $row['tp_count'] }}</span>
                                    </td>
                                    <td>
                                        @if($row['atp_id'])
                                            <a href="{{ route('user.kurikulum.atp.show', ['userId' => $userId, 'id' => $row['atp_id']]) }}"
                                               class="badge {{ $row['atp_status'] === 'published' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} text-decoration-none">
                                                {{ \App\Models\AlurTujuanPembelajaran::STATUS_OPTIONS[$row['atp_status']] ?? $row['atp_status'] }} · {{ $row['atp_total_jp'] }} JP
                                            </a>
                                        @else
                                            <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester, 'subject_id' => $row['subject_id']]) }}"
                                               class="link-primary small text-decoration-underline">
                                                Susun ATP
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $row['rombel'] }}</td>
                                    <td class="text-center">{{ $row['guru'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-inbox-archive-line fs-1 d-block mb-2"></i>
                                            Belum ada mapel per jenjang. Lengkapi di
                                            <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}" class="link-primary">Data Kelas</a>.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($canEditKktp ?? false)
                    <div class="d-flex justify-content-end p-3 border-top">
                        <button type="submit" form="kktpFormPeta" class="btn btn-success btn-sm">
                            <i class="ri-save-line me-1"></i>Simpan N-KKTP
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('modal')
    <x-modal-confirm id="siapkanPetaModal"
        icon="https://cdn.lordicon.com/lupuorrc.json"
        title="Siapkan Peta Kurikulum TA Ini?"
        message="Struktur mapel, KKTP, dan CP akan disalin dari tahun ajaran sumber ke tahun ajaran terpilih. Tahun ajaran sumber tidak diubah; data yang sudah ada di TA tujuan tidak ditimpa."
        submit-label="Ya, Siapkan"
        submit-icon="ri-archive-drawer-line"
        submit-class="btn-success"
        form-id="siapkanPetaForm" />
@endsection
