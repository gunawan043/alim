@extends('layouts.master')
@section('title', 'Buku Nilai Halaqah')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $books = $books ?? collect();
        $rows = $rows ?? collect();
        $columns = $columns ?? \App\Services\TahfidzGradebookCalculator::KEYS;
        $columnLabels = $columnLabels ?? \App\Services\TahfidzLegerColumns::COLUMNS;
        $search = $search ?? '';
        $selectedGroupId = $group?->id;
        $selectedSubjectId = $book?->subject_id;
        $weights = $book ? $book->weights() : [];
        $totalWeight = $book ? $book->totalWeight() : 100;
        $filled = $rows->filter(fn ($row) => $row['score'] && $row['score']->nr_final !== null)->count();
        $avgFinal = $rows->map(fn ($row) => $row['score']?->nr_final)->filter(fn ($v) => $v !== null)->avg();
        $rombelNames = $rows->pluck('member.study_group_name')->filter()->unique()->values();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Tahfidz @endslot
        @slot('li_2') Penilaian @endslot
        @slot('title') Buku Nilai Halaqah @endslot
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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Periksa kembali nilai.</strong>
            <ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    @if($academicYear)
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
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Halaqah</p>
                            <h3 class="fw-bold ff-secondary mb-0" style="font-size:18px;">{{ $group?->name ?? 'Belum dipilih' }}</h3>
                            @if($rombelNames->isNotEmpty())
                                <small class="text-muted"><i class="ri-group-line me-1"></i>{{ $rombelNames->implode(', ') }}</small>
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
                            <h3 class="fw-bold ff-secondary mb-0" style="font-size:16px;">{{ $academicYear->name }}</h3>
                            <small class="text-muted text-capitalize">{{ $semester }}</small>
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
                                <i class="ri-user-3-line text-success"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Santri Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0" style="font-size:18px;">{{ $members->count() }}</h3>
                            <small class="text-muted">{{ $filled }} sudah dinilai</small>
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
                                <i class="ri-bar-chart-line text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Rata-rata NR Final</p>
                            <h3 class="fw-bold ff-secondary mb-0" style="font-size:18px;">{{ $avgFinal !== null ? number_format($avgFinal, 1) : '—' }}</h3>
                            <small class="text-muted">{{ $books->count() }} buku mapel tahfidz</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Card --}}
    <div class="card" id="bukuHalaqah">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">
                        <i class="ri-file-edit-line text-primary me-1"></i>
                        Buku Nilai Halaqah{{ $group ? ' — '.$group->name : '' }}
                    </h5>
                    <p class="text-muted mb-0" style="font-size:12px;">
                        Dinilai per halaqah dengan kolom Ziyadah, Rabt, STS, dan SAS.
                        NR Final = jumlah (nilai &times; bobot) dibagi total bobot kolom yang terisi. Nilai tersimpan otomatis.
                    </p>
                </div>
                <div class="col-sm-auto">
                    @if($group)
                        <span class="badge bg-{{ $semester === 'ganjil' ? 'info' : 'warning' }}-subtle text-{{ $semester === 'ganjil' ? 'info' : 'warning' }}" style="font-size:11px;padding:6px 10px;">
                            <i class="ri-calendar-line me-1"></i>{{ ucfirst($semester) }} · {{ $academicYear?->name }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Filter: halaqah dulu, baru data dimuat --}}
        <div class="card-header py-2 bg-light border-bottom">
            <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
                <span class="text-muted fw-medium" style="font-size:12px;">
                    <i class="ri-filter-line me-1"></i>Filter:
                </span>
                <div>
                    <select name="group_id" class="form-select form-select-sm fw-semibold" style="min-width:200px;"
                            onchange="this.form.submit()" @disabled($groups->isEmpty())>
                        <option value="">{{ $groups->isEmpty() ? 'Belum ada halaqah' : 'Pilih Halaqah…' }}</option>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}" @selected($selectedGroupId === $g->id)>
                                {{ $g->name }}@if($g->studyGroup?->name) — {{ $g->studyGroup->name }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($books->count() > 1)
                    <div>
                        <select name="subject_id" class="form-select form-select-sm" style="min-width:150px;" onchange="this.form.submit()">
                            @foreach($books as $b)
                                <option value="{{ $b->subject_id }}" @selected($selectedSubjectId === $b->subject_id)>{{ $b->subject?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <select name="semester" class="form-select form-select-sm" style="min-width:150px;" onchange="this.form.submit()">
                        <option value="ganjil" @selected($semester === 'ganjil')>Semester Ganjil</option>
                        <option value="genap" @selected($semester === 'genap')>Semester Genap</option>
                    </select>
                </div>
                <div>
                    <input type="text" name="search" class="form-control form-control-sm" style="min-width:170px;"
                           placeholder="Cari nama Santri / NIS…" value="{{ $search }}"
                           @if($rows->isNotEmpty()) oninput="filterHalaqahRows(this.value)" @endif>
                </div>
                @if($group)
                    <a href="{{ route('user.tahfidz.penilaian.index', ['userId' => $userId, 'group_id' => $group->id, 'semester' => $semester]) }}"
                       class="btn btn-light btn-sm" title="Reset pencarian">
                        <i class="ri-refresh-line"></i>
                    </a>
                @endif
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span id="autosave-indicator" class="badge bg-light text-muted border d-flex align-items-center gap-1" style="font-size:.7rem;">
                        <span id="autosave-dot" class="rounded-circle bg-secondary" style="width:7px;height:7px;display:inline-block;"></span>
                        <span id="autosave-text">Menunggu…</span>
                    </span>
                    <span class="badge bg-primary-subtle text-primary" style="font-size:12px;">{{ $members->count() }} Santri</span>
                </div>
            </form>
        </div>

        @if(! $academicYear)
            <div class="text-center py-5">
                <div class="avatar-lg mx-auto mb-3">
                    <div class="avatar-title bg-light rounded-circle">
                        <i class="ri-calendar-line fs-1 text-muted"></i>
                    </div>
                </div>
                <h6 class="text-muted">Belum ada tahun ajaran</h6>
                <p class="text-muted mb-0" style="font-size:12px;">Aktifkan tahun ajaran terlebih dahulu.</p>
            </div>
        @elseif($groups->isEmpty())
            <div class="text-center py-5">
                <div class="avatar-lg mx-auto mb-3">
                    <div class="avatar-title bg-light rounded-circle">
                        <i class="ri-team-line fs-1 text-muted"></i>
                    </div>
                </div>
                <h6 class="text-muted">Belum ada halaqah</h6>
                <p class="text-muted mb-0" style="font-size:12px;">Anda belum terdaftar sebagai pengajar halaqah mana pun pada tahun ajaran ini.</p>
            </div>
        @elseif(! $group)
            <div class="text-center py-5">
                <div class="avatar-lg mx-auto mb-3">
                    <div class="avatar-title bg-light rounded-circle">
                        <i class="ri-filter-3-line fs-1 text-muted"></i>
                    </div>
                </div>
                <h6 class="text-muted">Pilih halaqah untuk mulai menilai</h6>
                <p class="text-muted mb-0" style="font-size:12px;">Daftar santri dan kolom nilai dimuat setelah halaqah dipilih.</p>
            </div>
        @elseif($books->isEmpty())
            <div class="p-4">
                <div class="alert alert-warning mb-0">
                    <i class="ri-alert-line me-1"></i>
                    Belum ada mapel berformat tahfidz di sekolah ini, sehingga buku nilai belum bisa dibuat.
                    Tandai mapel tahfidz dulu di
                    <a href="{{ route('user.tahfidz.format.index', ['userId' => $userId]) }}">Format Penilaian Mapel</a>.
                </div>
            </div>
        @else
            {{-- Panel bobot NR Final: setara "Pengaturan NR Final" buku nilai sumatif --}}
            <form method="POST" action="{{ route('user.tahfidz.penilaian.store', ['userId' => $userId]) }}" id="formHalaqah">
                @csrf
                <input type="hidden" name="tahfidz_group_id" value="{{ $group->id }}">
                <input type="hidden" name="tahfidz_gradebook_id" value="{{ $book->id }}">
                <input type="hidden" name="semester" value="{{ $semester }}">

                {{-- Ringkasan buku: sepadan dengan panel atas tab Mapel di buku nilai sumatif --}}
                <div class="px-4 py-3" style="background:var(--bs-tertiary-bg);border-bottom:1px solid var(--bs-border-color);">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white" style="font-size:12px;">
                                <i class="ri-book-line me-1"></i>{{ $book->subject?->name ?? 'Tahfidz' }}
                            </span>
                            <span class="badge bg-secondary-subtle text-secondary text-capitalize">{{ $semester }}</span>
                            <span class="badge bg-info-subtle text-info">
                                <i class="ri-team-line me-1"></i>{{ $group->name }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2" style="font-size:12px;">
                            <span class="badge bg-primary-subtle text-primary"><i class="ri-bookmark-line me-1"></i>Ziyadah</span>
                            <span class="badge bg-success-subtle text-success"><i class="ri-link me-1"></i>Rabt</span>
                            <span class="badge bg-warning-subtle text-warning"><i class="ri-edit-2-line me-1"></i>STS</span>
                            <span class="badge bg-info-subtle text-info"><i class="ri-file-list-3-line me-1"></i>SAS</span>
                            <span class="badge bg-secondary-subtle text-secondary"><i class="ri-calculator-line me-1"></i>NR Final &amp; Jumlah (otomatis)</span>
                        </div>
                    </div>
                </div>

                <div class="px-4 pt-3 pb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap border rounded p-2" style="background:var(--bs-tertiary-bg);">
                        <span class="fw-semibold" style="font-size:12px;">Bobot NR Final:</span>
                        @foreach($columns as $key)
                            <div class="input-group input-group-sm" style="width:132px;">
                                <span class="input-group-text">{{ $columnLabels[$key] }} %</span>
                                <input type="number" name="weights[{{ $key }}]" id="w-{{ $key }}"
                                       class="form-control weight-input text-center" min="0" max="100" step="0.5"
                                       value="{{ old('weights.'.$key, $book->{'weight_'.$key}) }}">
                            </div>
                        @endforeach
                        <div id="weight-sum" class="badge bg-secondary">Total: {{ rtrim(rtrim(number_format($totalWeight, 2, '.', ''), '0'), '.') }}%</div>
                        <span class="text-muted ms-auto" style="font-size:11px;">Total bobot harus 100%.</span>
                    </div>
                </div>

                {{-- Materi ujian STS & SAS. Disimpan per buku (halaqah + mapel
                     + semester) karena satu halaqah diuji materi yang sama.
                     Bentuk isian mengikuti setoran: surah, ayat, juz, halaman. --}}
                @foreach(['sts' => 'STS', 'sas' => 'SAS'] as $jenisKey => $jenisLabel)
                    @php $materi = $book->examMaterial($jenisKey); @endphp
                    <div class="px-4 pt-3">
                        <div class="border rounded p-3" style="background:var(--bs-tertiary-bg);">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div class="fw-semibold" style="font-size:13px;">
                                    <i class="ri-file-text-line me-1 text-primary"></i>Materi Ujian {{ $jenisLabel }}
                                </div>
                                <span class="badge {{ $materi ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }} exam-preview" data-exam="{{ $jenisKey }}">
                                    @if($materi)
                                        {{ $materi['surah_start'] ?? '—' }}{{ $materi['ayah_start'] ? ':'.$materi['ayah_start'] : '' }}
                                        @if($materi['surah_end'] || $materi['ayah_end'])
                                            – {{ $materi['surah_end'] ?: $materi['surah_start'] }}{{ $materi['ayah_end'] ? ':'.$materi['ayah_end'] : '' }}
                                        @endif
                                        @if($materi['juz']) · Juz {{ $materi['juz'] }} @endif
                                    @else
                                        Belum diisi
                                    @endif
                                </span>
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Surah awal</label>
                                    <select name="materi[{{ $jenisKey }}][surah_start]" class="form-select form-select-sm exam-surah"
                                            data-exam="{{ $jenisKey }}" data-role="start">
                                        <option value="">— Pilih surah —</option>
                                        @foreach($surahs as $surah)
                                            <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}"
                                                    @selected(($materi['surah_start'] ?? null) === $surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1">Ayat awal</label>
                                    <input type="number" min="1" name="materi[{{ $jenisKey }}][ayah_start]"
                                           class="form-control form-control-sm exam-ayat" data-exam="{{ $jenisKey }}" data-role="start"
                                           value="{{ $materi['ayah_start'] ?? '' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Surah akhir <span class="fw-normal text-muted">(kosongkan bila sama)</span></label>
                                    <select name="materi[{{ $jenisKey }}][surah_end]" class="form-select form-select-sm exam-surah"
                                            data-exam="{{ $jenisKey }}" data-role="end">
                                        <option value="">— Sama dengan surah awal —</option>
                                        @foreach($surahs as $surah)
                                            <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}"
                                                    @selected(($materi['surah_end'] ?? null) === $surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1">Ayat akhir</label>
                                    <input type="number" min="1" name="materi[{{ $jenisKey }}][ayah_end]"
                                           class="form-control form-control-sm exam-ayat" data-exam="{{ $jenisKey }}" data-role="end"
                                           value="{{ $materi['ayah_end'] ?? '' }}">
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1">Juz</label>
                                    <input type="number" min="1" max="30" name="materi[{{ $jenisKey }}][juz]"
                                           class="form-control form-control-sm" value="{{ $materi['juz'] ?? '' }}">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1">Halaman</label>
                                    <input type="number" min="1" max="604" name="materi[{{ $jenisKey }}][page_start]"
                                           id="page-{{ $jenisKey }}" class="form-control form-control-sm" value="{{ $materi['page_start'] ?? '' }}">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1">Baris</label>
                                    <input type="number" min="1" max="15" name="materi[{{ $jenisKey }}][line_start]"
                                           id="line-{{ $jenisKey }}" class="form-control form-control-sm" value="{{ $materi['line_start'] ?? '' }}">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1">Hal. akhir</label>
                                    <input type="number" min="1" max="604" name="materi[{{ $jenisKey }}][page_end]"
                                           id="pageend-{{ $jenisKey }}" class="form-control form-control-sm" value="{{ $materi['page_end'] ?? '' }}">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1">Baris akhir</label>
                                    <input type="number" min="1" max="15" name="materi[{{ $jenisKey }}][line_end]"
                                           id="lineend-{{ $jenisKey }}" class="form-control form-control-sm" value="{{ $materi['line_end'] ?? '' }}">
                                </div>
                                <div class="col-md-4">
                                    <div class="form-text mb-0" id="hint-{{ $jenisKey }}">
                                        <i class="ri-bookmark-line me-1"></i>Halaman dan baris terisi otomatis dari Mushaf Madinah.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($rows->isEmpty())
                    <div class="text-center py-5">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-title bg-light rounded-circle">
                                <i class="ri-user-search-line fs-1 text-muted"></i>
                            </div>
                        </div>
                        <h6 class="text-muted">{{ $search !== '' ? 'Santri tidak ditemukan' : 'Belum ada santri aktif' }}</h6>
                        <p class="text-muted mb-0" style="font-size:12px;">
                            {{ $search !== '' ? 'Ubah kata kunci pencarian.' : 'Belum ada santri aktif di halaqah ini pada '.$academicYear->name.'.' }}
                        </p>
                    </div>
                @else
                    <div class="table-responsive" style="max-height:72vh;overflow:auto;">
                        <table id="tab-halaqah" class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th rowspan="2" class="align-middle text-muted" style="width:50px;background:var(--bs-tertiary-bg);">No</th>
                                    <th rowspan="2" class="align-middle text-muted" style="width:90px;background:var(--bs-tertiary-bg);">NIS</th>
                                    <th rowspan="2" class="align-middle text-muted text-start" style="min-width:230px;background:var(--bs-tertiary-bg);">
                                        <i class="ri-user-line me-1"></i>Nama Santri
                                    </th>
                                    <th rowspan="2" class="align-middle" style="width:96px;">Rombel</th>
                                    <th colspan="{{ count($columns) }}" class="align-middle">Nilai Tahfidz</th>
                                    <th rowspan="2" class="align-middle bg-success-subtle" style="width:92px;">NR Final</th>
                                    <th rowspan="2" class="align-middle bg-secondary-subtle" style="width:92px;" title="Jumlah = NR Final × Bobot">Jumlah</th>
                                    <th rowspan="2" class="align-middle bg-secondary-subtle" style="width:150px;">Keterangan</th>
                                </tr>
                                <tr>
                                    @foreach($columns as $key)
                                        <th style="width:88px;min-width:88px;" title="Nilai {{ $columnLabels[$key] }}">
                                            {{ $columnLabels[$key] }}<br>
                                            <small class="fw-normal text-muted">({{ rtrim(rtrim(number_format((float) ($weights[$key] ?? 0), 2, '.', ''), '0'), '.') }}%)</small>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $i => $row)
                                    @php
                                        $member = $row['member'];
                                        $score = $row['score'];
                                        $sid = $member['student_id'];
                                        $jumlah = $score?->nr_final !== null ? (float) $score->nr_final * $totalWeight / 100 : null;
                                    @endphp
                                    <tr data-name="{{ mb_strtolower($member['name']) }}|{{ mb_strtolower((string) $member['nis']) }}">
                                        <td class="text-center text-muted fw-semibold" data-col="no">{{ $i + 1 }}</td>
                                        <td class="text-center" data-col="nis">{{ $member['nis'] ?? '—' }}</td>
                                        <td class="text-start" data-col="name">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-primary text-white">
                                                        {{ mb_strtoupper(mb_substr($member['name'], 0, 1)) }}
                                                    </span>
                                                </div>
                                                <span class="fw-medium">{{ $member['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center text-muted">{{ $member['study_group_name'] ?? '—' }}</td>
                                        @foreach($columns as $key)
                                            <td class="p-0" data-col="{{ $key }}">
                                                <input type="number" step="0.01" min="0" max="100"
                                                       class="sheet-input score-input"
                                                       name="scores[{{ $sid }}][{{ $key }}]"
                                                       data-student="{{ $sid }}"
                                                       value="{{ $score?->{$key} !== null ? rtrim(rtrim(number_format((float) $score->{$key}, 2, '.', ''), '0'), '.') : '' }}"
                                                       placeholder="---"
                                                       oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;">
                                            </td>
                                        @endforeach
                                        <td class="text-center fw-bold" id="nrf-{{ $sid }}">{{ $score?->nr_final !== null ? number_format((float) $score->nr_final, 1) : '---' }}</td>
                                        <td class="text-center fw-semibold bg-light" id="jml-{{ $sid }}">{{ $jumlah !== null ? number_format($jumlah, 1) : '---' }}</td>
                                        <td class="p-0">
                                            <input type="text" maxlength="100" class="sheet-input"
                                                   name="scores[{{ $sid }}][ket]" data-student="{{ $sid }}"
                                                   value="{{ $score?->ket }}" placeholder="Catatan (opsional)">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer border-top-dashed px-4 py-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="text-muted" style="font-size:12px;">
                                <span class="badge bg-primary-subtle text-primary">Ziyadah</span>
                                <span class="me-2">hafalan baru.</span>
                                <span class="badge bg-success-subtle text-success">Rabt</span>
                                <span class="me-2">penyambung hafalan.</span>
                                <span class="badge bg-warning-subtle text-warning">STS</span> /
                                <span class="badge bg-info-subtle text-info">SAS</span>
                                <span>sumatif. Kosongkan kolom yang belum dinilai.</span>
                            </div>
                            <button class="btn btn-success btn-sm" type="submit"><i class="ri-save-line me-1"></i>Simpan Nilai &amp; Bobot</button>
                        </div>
                    </div>
                @endif
            </form>
        @endif
    </div>
@endsection

@section('script')
<style>
    #tab-halaqah { font-size: 12px; }
    #tab-halaqah thead th { font-size: 12px; font-weight: 600; }
    #tab-halaqah thead th[colspan] { font-size: 11px; letter-spacing: .4px; }
    #tab-halaqah thead th small { font-size: 10px; }
    #tab-halaqah tbody td { font-size: 12px; }
    #tab-halaqah tbody tr { transition: background-color .12s; }
    #tab-halaqah tbody tr:hover td { background: rgba(var(--bs-primary-rgb), .05); }

    /* Gaya spreadsheet: input tanpa border, fokus hanya latar halus */
    #tab-halaqah td.p-0 { padding: 0; }
    #tab-halaqah input.sheet-input {
        width: 100%; height: 30px; padding: 0 4px;
        border: 0; border-radius: 0; box-shadow: none;
        background: transparent; color: var(--bs-body-color);
        font-size: 12px; text-align: center;
    }
    #tab-halaqah input.sheet-input[type="text"] { text-align: left; }
    #tab-halaqah input.sheet-input:focus { outline: none; border: 0; box-shadow: none; background: rgba(var(--bs-primary-rgb), .06); }
    #tab-halaqah input.sheet-input::placeholder { color: var(--bs-secondary-color); opacity: .6; }
    #tab-halaqah input.score-input::placeholder { text-align: center; opacity: .45; }

    /* Header beku */
    #tab-halaqah thead th { position: sticky; top: 0; z-index: 20; background: var(--bs-tertiary-bg); }
    #tab-halaqah thead tr:nth-child(2) th { top: 40px; }

    @media (max-width: 991.98px) {
        #tab-halaqah thead th { position: static; }
    }
</style>

<script>
    function filterHalaqahRows(query) {
        var q = (query || '').toLowerCase().trim();
        document.querySelectorAll('#tab-halaqah tbody tr[data-name]').forEach(function (row) {
            var hay = row.getAttribute('data-name') || '';
            row.style.display = (q === '' || hay.indexOf(q) !== -1) ? '' : 'none';
        });
        renumberHalaqahRows();
    }

    function renumberHalaqahRows() {
        var n = 0;
        document.querySelectorAll('#tab-halaqah tbody tr[data-name]').forEach(function (row) {
            if (row.style.display === 'none') return;
            n++;
            var cell = row.querySelector('[data-col="no"]');
            if (cell) cell.textContent = n;
        });
    }

    (function () {
        // Indikator total bobot: hijau bila tepat 100%.
        var inputs = document.querySelectorAll('.weight-input');
        var sumEl = document.getElementById('weight-sum');
        if (inputs.length && sumEl) {
            var render = function () {
                var total = 0;
                inputs.forEach(function (el) { total += parseFloat(el.value) || 0; });
                var rounded = Math.round(total * 10) / 10;
                sumEl.textContent = 'Total: ' + rounded + '%';
                sumEl.className = 'badge ' + (Math.abs(rounded - 100) < 0.01 ? 'bg-success' : 'bg-danger');
            };
            inputs.forEach(function (el) { el.addEventListener('input', render); });
            render();
        }

        // Navigasi Enter / ↑ / ↓ antar sel nilai (seperti buku nilai sumatif).
        var table = document.getElementById('tab-halaqah');
        if (table) {
            table.addEventListener('keydown', function (e) {
                if (!e.target.classList || !e.target.classList.contains('score-input')) return;
                if (e.key !== 'Enter' && e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;

                var input = e.target;
                var col = Array.prototype.indexOf.call(
                    input.closest('tr').querySelectorAll('input.score-input'), input
                );
                var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-name]'))
                    .filter(function (r) { return r.style.display !== 'none'; });
                var rowIndex = rows.indexOf(input.closest('tr'));
                var next = rows[rowIndex + (e.key === 'ArrowUp' ? -1 : 1)];
                if (!next) return;

                var target = next.querySelectorAll('input.score-input')[col];
                if (target) {
                    e.preventDefault();
                    target.focus();
                    if (target.select) target.select();
                }
            });
        }

        // ─── Autosave nilai (pola sama dengan buku nilai sumatif) ─────
        var form = document.getElementById('formHalaqah');
        if (!form) return;

        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        var dot = document.getElementById('autosave-dot');
        var text = document.getElementById('autosave-text');
        var timer = null;
        var inFlight = false;
        var dirty = false;

        function setIndicator(cls, msg) {
            if (dot) dot.className = 'rounded-circle ' + cls;
            if (text) text.textContent = msg;
        }

        function showSuccess() {
            var time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            setIndicator('bg-success', 'Tersimpan ' + time);
            setTimeout(function () { setIndicator('bg-secondary', 'Menunggu…'); }, 3000);
        }

        function showError(msg) {
            setIndicator('bg-danger', msg || 'Gagal menyimpan');
            setTimeout(function () { setIndicator('bg-secondary', 'Menunggu…'); }, 4000);
        }

        function applyRows(rows) {
            (rows || []).forEach(function (row) {
                if (row.nr_final !== null && row.nr_final !== undefined) {
                    var finalCell = document.getElementById('nrf-' + row.student_id);
                    if (finalCell) finalCell.textContent = Number(row.nr_final).toFixed(1);
                }
                var sumCell = document.getElementById('jml-' + row.student_id);
                if (sumCell) {
                    sumCell.textContent = (row.weighted === null || row.weighted === undefined)
                        ? '---' : Number(row.weighted).toFixed(1);
                }
            });
        }

        function save() {
            if (inFlight) return;
            inFlight = true;
            dirty = false;
            setIndicator('bg-warning', 'Menyimpan…');

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(form),
            })
            .then(function (res) {
                inFlight = false;
                return res.json().catch(function () {
                    return { success: false, message: 'Sesi berakhir — muat ulang halaman (Ctrl+Shift+R).' };
                });
            })
            .then(function (data) {
                applyRows(data && data.saved_rows);
                if (data && data.success === true) {
                    showSuccess();
                } else {
                    showError((data && data.message) ? data.message : 'Gagal menyimpan');
                }
            })
            .catch(function () {
                inFlight = false;
                showError('Gagal menyimpan');
            });

            // Isian baru selama permintaan berjalan perlu dikirim lagi.
            if (dirty && !timer) timer = setTimeout(function () { timer = null; save(); }, 1200);
        }

        form.querySelectorAll('input.sheet-input, input.weight-input').forEach(function (el) {
            var onChange = function () {
                dirty = true;
                clearTimeout(timer);
                timer = setTimeout(function () { timer = null; save(); }, 1500);
            };
            el.addEventListener('input', onChange);
            el.addEventListener('change', onChange);
        });

        // Materi ujian ikut tersimpan lewat autosave yang sama.
        form.querySelectorAll('.exam-surah, .exam-ayat, input[name^="materi["]').forEach(function (el) {
            el.addEventListener('change', function () {
                dirty = true;
                clearTimeout(timer);
                timer = setTimeout(function () { timer = null; save(); }, 1500);
            });
        });

        // Nilai tetap tersimpan sebelum halaman ditinggalkan.
        window.addEventListener('beforeunload', function () {
            if (dirty) save();
        });
    })();

    // ── Materi ujian STS/SAS: halaman & baris otomatis ───────────────────
    (function () {
        var positionUrl = @json(route('user.tahfidz.quran-position', ['userId' => $userId]));

        document.querySelectorAll('.exam-surah').forEach(function (select) {
            select.addEventListener('change', function () {
                fillExam(this.dataset.exam);
            });
        });
        document.querySelectorAll('.exam-ayat').forEach(function (input) {
            input.addEventListener('change', function () {
                fillExam(this.dataset.exam);
            });
        });

        function fillExam(jenis) {
            var startSelect = document.querySelector('.exam-surah[data-exam="' + jenis + '"][data-role="start"]');
            var endSelect = document.querySelector('.exam-surah[data-exam="' + jenis + '"][data-role="end"]');
            var startAyah = document.querySelector('.exam-ayat[data-exam="' + jenis + '"][data-role="start"]');
            var endAyah = document.querySelector('.exam-ayat[data-exam="' + jenis + '"][data-role="end"]');
            var hint = document.getElementById('hint-' + jenis);

            var s = startSelect && startSelect.selectedOptions[0] && startSelect.selectedOptions[0].dataset.number;
            var a = startAyah && startAyah.value;
            if (! s || ! a) return;

            var es = (endSelect && endSelect.selectedOptions[0] && endSelect.selectedOptions[0].dataset.number) || s;
            var ea = (endAyah && endAyah.value) || a;

            var url = new URL(positionUrl, window.location.origin);
            url.searchParams.set('surah_start', s);
            url.searchParams.set('ayah_start', a);
            url.searchParams.set('surah_end', es);
            url.searchParams.set('ayah_end', ea);

            fetch(url, { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (! data.found || ! data.position) {
                        if (hint) hint.innerHTML = '<i class="ri-error-warning-line me-1 text-warning"></i>Posisi ayat tidak ditemukan, isi halaman dan baris manual.';
                        return;
                    }
                    document.getElementById('page-' + jenis).value = data.position.page_start;
                    document.getElementById('line-' + jenis).value = data.position.line_start;
                    document.getElementById('pageend-' + jenis).value = data.position.page_end;
                    document.getElementById('lineend-' + jenis).value = data.position.line_end;
                    if (hint) {
                        hint.innerHTML = '<i class="ri-bookmark-line me-1"></i>Halaman ' + data.position.page_start + '–'
                            + data.position.page_end + ' · baris ' + data.position.line_start + '–' + data.position.line_end;
                    }
                    // Ringkasan materi ikut diperbarui tanpa menunggu simpan.
                    var badge = document.querySelector('.exam-preview[data-exam="' + jenis + '"]');
                    if (badge) {
                        var surahName = startSelect.selectedOptions[0].textContent.trim();
                        var endName = (endSelect && endSelect.value) ? endSelect.value : surahName;
                        badge.textContent = surahName + ':' + a + (endName !== surahName || ea !== a ? ' – ' + endName + ':' + ea : '');
                        badge.className = 'badge bg-primary-subtle text-primary exam-preview';
                    }
                })
                .catch(function () {
                    if (hint) hint.innerHTML = '<i class="ri-error-warning-line me-1 text-warning"></i>Gagal mengambil posisi ayat, isi halaman dan baris manual.';
                });
        }
    })();
</script>
@endsection
