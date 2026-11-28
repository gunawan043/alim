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

        // Kolom yang diisi guru lewat modal; NPT & Tikrar berasal dari setoran
        // harian, jadi hanya ditampilkan sebagai angka pendukung.
        $manualColumns = ['ziyadah', 'rabt', 'sts', 'sas'];
        $autoColumns = ['npt', 'tikrar'];
        $weightText = function ($key) use ($weights) {
            return rtrim(rtrim(number_format((float) ($weights[$key] ?? 0), 2, '.', ''), '0'), '.');
        };
        $examMaterials = [
            'sts' => $book?->examMaterial('sts'),
            'sas' => $book?->examMaterial('sas'),
        ];
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

    {{-- Ringkasan singkat: satu baris di HP, kartu di layar lebar --}}
    @if($academicYear)
        <div class="row g-2 g-md-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body py-2 py-md-3">
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;">Halaqah</p>
                        <h6 class="fw-bold mb-0 text-truncate">{{ $group?->name ?? 'Belum dipilih' }}</h6>
                        @if($rombelNames->isNotEmpty())
                            <small class="text-muted d-block text-truncate">{{ $rombelNames->implode(', ') }}</small>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body py-2 py-md-3">
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;">Tahun Ajaran</p>
                        <h6 class="fw-bold mb-0 text-truncate">{{ $academicYear->name }}</h6>
                        <small class="text-muted text-capitalize d-block">{{ $semester }}</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body py-2 py-md-3">
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;">Santri Aktif</p>
                        <h6 class="fw-bold mb-0">{{ $members->count() }}</h6>
                        <small class="text-muted d-block">{{ $filled }} sudah dinilai</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body py-2 py-md-3">
                        <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;">Rata-rata NR</p>
                        <h6 class="fw-bold mb-0">{{ $avgFinal !== null ? number_format($avgFinal, 1) : '—' }}</h6>
                        <small class="text-muted d-block">{{ $books->count() }} buku mapel tahfidz</small>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Filter ─────────────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-xl-4">
                    <label class="form-label small mb-1">Halaqah</label>
                    <select name="group_id" class="form-select form-select-sm fw-semibold"
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
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label small mb-1">Mapel</label>
                        <select name="subject_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($books as $b)
                                <option value="{{ $b->subject_id }}" @selected($selectedSubjectId === $b->subject_id)>{{ $b->subject?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small mb-1">Semester</label>
                    <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option>
                        <option value="genap" @selected($semester === 'genap')>Genap</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label small mb-1">Cari santri</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent"><i class="ri-search-line"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Nama atau NIS"
                               value="{{ $search }}" @if($group) oninput="filterSantriRows(this.value)" @endif>
                        @if($group)
                            <a href="{{ route('user.tahfidz.penilaian.index', ['userId' => $userId, 'group_id' => $group->id, 'semester' => $semester]) }}"
                               class="btn btn-outline-secondary" title="Reset pencarian"><i class="ri-refresh-line"></i></a>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-xl-auto">
                    <button class="btn btn-primary btn-sm w-100" type="submit"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Daftar santri ──────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center gap-2">
            <div class="flex-grow-1">
                <h5 class="card-title mb-0">
                    <i class="ri-file-edit-line text-primary me-1"></i>
                    Buku Nilai Halaqah{{ $group ? ' — '.$group->name : '' }}
                </h5>
                <p class="text-muted mb-0" style="font-size:12px;">
                    Ketuk tombol <strong>Nilai</strong> untuk mengisi Ziyadah, Rabt, STS, dan SAS lewat jendela singkat.
                    NPT dan Tikrar diambil otomatis dari setoran harian.
                </p>
            </div>
            @if($group)
                @if($book)
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMateri">
                        <i class="ri-file-text-line me-1"></i>Materi Ujian STS/SAS
                    </button>
                @endif
                <span class="badge bg-primary-subtle text-primary" style="font-size:12px;">{{ $members->count() }} Santri</span>
            @endif
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
                <p class="text-muted mb-0" style="font-size:12px;">Daftar santri dimuat setelah halaqah dipilih.</p>
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
        @elseif($rows->isEmpty())
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
            {{-- Tabel ringkas: hanya pratinjau nilai; penyuntingan lewat modal.
                 Di HP baris berubah menjadi kartu (lihat gaya .student-table). --}}
            <div class="table-responsive">
                <table id="tab-santri" class="table table-hover align-middle mb-0 student-table"
                       data-total-weight="{{ $totalWeight }}">
                    <thead class="table-light">
                        <tr>
                            <th class="text-muted text-center" style="width:44px;">No</th>
                            <th class="text-muted">Santri</th>
                            @foreach($manualColumns as $key)
                                <th class="text-center col-score" title="Nilai {{ $columnLabels[$key] }}">
                                    {{ $columnLabels[$key] }}
                                    <small class="fw-normal text-muted d-block">{{ $weightText($key) }}%</small>
                                </th>
                            @endforeach
                            <th class="text-center col-score">NPT<small class="fw-normal text-muted d-block">{{ $weightText('npt') }}%</small></th>
                            <th class="text-center col-score">Tikrar<small class="fw-normal text-muted d-block">{{ $weightText('tikrar') }}%</small></th>
                            <th class="text-center">NR Final</th>
                            <th class="text-center col-score">Jumlah</th>
                            <th class="text-end" style="width:96px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $i => $row)
                            @php
                                $member = $row['member'];
                                $score = $row['score'];
                                $sid = $member['student_id'];
                                $jumlah = $score?->nr_final !== null ? (float) $score->nr_final * $totalWeight / 100 : null;
                                $filledCount = collect($manualColumns)->filter(fn ($k) => $score?->{$k} !== null)->count();
                            @endphp
                            <tr data-name="{{ mb_strtolower($member['name']) }}|{{ mb_strtolower((string) $member['nis']) }}">
                                <td class="text-center text-muted fw-semibold" data-label="No">{{ $i + 1 }}</td>
                                <td data-label="Santri">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs flex-shrink-0">
                                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary fw-bold">
                                                {{ mb_strtoupper(mb_substr($member['name'], 0, 1)) }}
                                            </span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-medium text-truncate">{{ $member['name'] }}</div>
                                            <small class="text-muted">
                                                {{ $member['nis'] ?? '—' }}
                                                @if($member['study_group_name']) · {{ $member['study_group_name'] }} @endif
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                @foreach($manualColumns as $key)
                                    <td class="text-center col-score" data-label="{{ $columnLabels[$key] }}">
                                        <span class="score-cell {{ $score?->{$key} !== null ? '' : 'text-muted' }}"
                                              data-score="{{ $sid }}.{{ $key }}">
                                            {{ $score?->{$key} !== null ? rtrim(rtrim(number_format((float) $score->{$key}, 2, '.', ''), '0'), '.') : '—' }}
                                        </span>
                                    </td>
                                @endforeach
                                @foreach($autoColumns as $key)
                                    @php $autoValue = $row['auto'][$key] ?? null; @endphp
                                    <td class="text-center col-score text-muted" data-label="{{ $columnLabels[$key] }}"
                                        title="Diambil otomatis dari setoran harian">
                                        {{ $autoValue !== null ? rtrim(rtrim(number_format((float) $autoValue, 2, '.', ''), '0'), '.') : '—' }}
                                    </td>
                                @endforeach
                                <td class="text-center" data-label="NR Final">
                                    <span class="badge {{ $score?->nr_final !== null ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} fs-6"
                                          data-nr="{{ $sid }}">
                                        {{ $score?->nr_final !== null ? number_format((float) $score->nr_final, 1) : '—' }}
                                    </span>
                                </td>
                                <td class="text-center col-score fw-semibold text-muted" data-label="Jumlah" data-jml="{{ $sid }}">
                                    {{ $jumlah !== null ? number_format($jumlah, 1) : '—' }}
                                </td>
                                <td class="text-end" data-label="Aksi">
                                    <button type="button" class="btn btn-sm {{ $filledCount > 0 ? 'btn-outline-primary' : 'btn-primary' }}"
                                            data-bs-toggle="modal" data-bs-target="#modalNilai"
                                            data-student="{{ $sid }}"
                                            data-name="{{ $member['name'] }}"
                                            data-nis="{{ $member['nis'] ?? '' }}"
                                            data-rombel="{{ $member['study_group_name'] ?? '' }}"
                                            data-filled="{{ $filledCount }}">
                                        <i class="ri-edit-2-line"></i>
                                        <span class="d-none d-lg-inline ms-1">Nilai</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer border-top-dashed">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 text-muted" style="font-size:12px;">
                    <span>
                        <i class="ri-information-line me-1"></i>
                        NR Final = jumlah (nilai × bobot) dibagi total bobot kolom yang terisi.
                        Bobot diatur Departemen Tahfidz.
                    </span>
                    <span><i class="ri-lock-line me-1"></i>Perubahan tersimpan otomatis saat modal ditutup.</span>
                </div>
            </div>
        @endif
    </div>

    @if($group && $book && $rows->isNotEmpty())
        {{-- ── Modal materi ujian (per halaqah, berlaku semua santri) ───── --}}
        <div class="modal fade" id="modalMateri" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0">Materi Ujian STS &amp; SAS</h5>
                            <small class="text-muted">Berlaku untuk seluruh santri di halaqah {{ $group->name }}.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-pills nav-sm mb-3 gap-1" role="tablist">
                            @foreach(['sts' => 'STS', 'sas' => 'SAS'] as $jenisKey => $jenisLabel)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" type="button"
                                            data-bs-toggle="pill" data-bs-target="#materi-{{ $jenisKey }}"
                                            role="tab">{{ $jenisLabel }}</button>
                                </li>
                            @endforeach
                        </ul>

                        @foreach(['sts' => 'STS', 'sas' => 'SAS'] as $jenisKey => $jenisLabel)
                            @php $materi = $examMaterials[$jenisKey]; @endphp
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="materi-{{ $jenisKey }}" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small mb-1">Surah awal</label>
                                        <select name="materi[{{ $jenisKey }}][surah_start]" class="form-select exam-surah"
                                                data-exam="{{ $jenisKey }}" data-role="start">
                                            <option value="">— Pilih surah —</option>
                                            @foreach($surahs as $surah)
                                                <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}"
                                                        @selected(($materi['surah_start'] ?? null) === $surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small mb-1">Ayat awal</label>
                                        <input type="number" min="1" name="materi[{{ $jenisKey }}][ayah_start]"
                                               class="form-control exam-ayat" data-exam="{{ $jenisKey }}" data-role="start"
                                               value="{{ $materi['ayah_start'] ?? '' }}">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small mb-1">Juz</label>
                                        <input type="number" min="1" max="30" name="materi[{{ $jenisKey }}][juz]"
                                               class="form-control" value="{{ $materi['juz'] ?? '' }}">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small mb-1">Surah akhir <span class="fw-normal text-muted">(boleh sama)</span></label>
                                        <select name="materi[{{ $jenisKey }}][surah_end]" class="form-select exam-surah"
                                                data-exam="{{ $jenisKey }}" data-role="end">
                                            <option value="">— Sama dengan surah awal —</option>
                                            @foreach($surahs as $surah)
                                                <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}"
                                                        @selected(($materi['surah_end'] ?? null) === $surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small mb-1">Ayat akhir</label>
                                        <input type="number" min="1" name="materi[{{ $jenisKey }}][ayah_end]"
                                               class="form-control exam-ayat" data-exam="{{ $jenisKey }}" data-role="end"
                                               value="{{ $materi['ayah_end'] ?? '' }}">
                                    </div>
                                </div>

                                <hr class="my-3">
                                <p class="text-muted small mb-2">
                                    <i class="ri-bookmark-line me-1"></i>Halaman &amp; baris terisi otomatis dari Mushaf Madinah; ubah bila perlu.
                                </p>
                                <div class="row g-2">
                                    @foreach([['page_start', 'page-'.$jenisKey, 'Halaman awal', 604], ['line_start', 'line-'.$jenisKey, 'Baris awal', 15], ['page_end', 'pageend-'.$jenisKey, 'Halaman akhir', 604], ['line_end', 'lineend-'.$jenisKey, 'Baris akhir', 15]] as [$field, $fid, $flabel, $fmax])
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small mb-1">{{ $flabel }}</label>
                                            <input type="number" min="1" max="{{ $fmax }}" name="materi[{{ $jenisKey }}][{{ $field }}]"
                                                   id="{{ $fid }}" class="form-control" value="{{ $materi[$field] ?? '' }}">
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-text mt-2" id="hint-{{ $jenisKey }}">
                                    <i class="ri-information-line me-1"></i>Pilih surah dan ayat awal untuk mengisi posisi otomatis.
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-primary" id="btnSimpanMateri">
                            <i class="ri-save-line me-1"></i>Simpan Materi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Modal input nilai satu santri ────────────────────────────── --}}
        <div class="modal fade" id="modalNilai" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header align-items-start">
                        <div class="min-w-0">
                            <h5 class="modal-title mb-1 text-truncate" id="modalSantriNama">Nilai Santri</h5>
                            <div class="text-muted small text-truncate" id="modalSantriInfo"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body" id="formNilai">
                        <div class="row g-3">
                            @foreach($manualColumns as $key)
                                <div class="col-6">
                                    <label class="form-label fw-semibold mb-1" for="inp-{{ $key }}">
                                        {{ $columnLabels[$key] }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $weightText($key) }}%</span>
                                    </label>
                                    <input type="number" step="0.01" min="0" max="100" inputmode="decimal"
                                           class="form-control form-control-lg text-center nilai-input"
                                           id="inp-{{ $key }}" data-key="{{ $key }}" placeholder="—">
                                </div>
                            @endforeach
                            @if($examMaterials['sts'] || $examMaterials['sas'])
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach(['sts' => 'STS', 'sas' => 'SAS'] as $jenisKey => $jenisLabel)
                                            <span class="badge {{ $examMaterials[$jenisKey] ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }} exam-preview"
                                                  data-exam="{{ $jenisKey }}">
                                                <i class="ri-file-text-line me-1"></i>Materi {{ $jenisLabel }}:
                                                @if($examMaterials[$jenisKey])
                                                    {{ $examMaterials[$jenisKey]['surah_start'] ?? '—' }}{{ $examMaterials[$jenisKey]['ayah_start'] ? ':'.$examMaterials[$jenisKey]['ayah_start'] : '' }}
                                                    @if($examMaterials[$jenisKey]['surah_end'] || $examMaterials[$jenisKey]['ayah_end'])
                                                        – {{ $examMaterials[$jenisKey]['surah_end'] ?: $examMaterials[$jenisKey]['surah_start'] }}{{ $examMaterials[$jenisKey]['ayah_end'] ? ':'.$examMaterials[$jenisKey]['ayah_end'] : '' }}
                                                    @endif
                                                    @if($examMaterials[$jenisKey]['juz']) · Juz {{ $examMaterials[$jenisKey]['juz'] }} @endif
                                                @else
                                                    belum diisi
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label fw-semibold mb-1" for="inp-ket">Keterangan <span class="fw-normal text-muted">(opsional)</span></label>
                                <input type="text" maxlength="100" class="form-control" id="inp-ket" placeholder="Catatan singkat">
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-3 p-3 rounded bg-success-subtle">
                            <div>
                                <div class="text-muted" style="font-size:11px;">NR Final (otomatis)</div>
                                <div class="fw-bold fs-4 text-success" id="modalNr">—</div>
                            </div>
                            <div class="text-end" style="font-size:12px;">
                                <div class="text-muted">Jumlah (NR × bobot)</div>
                                <div class="fw-semibold" id="modalJumlah">—</div>
                            </div>
                        </div>
                        <div class="form-text mt-2">
                            NPT dan Tikrar diisi otomatis dari setoran harian. Nilai tersimpan otomatis saat modal ditutup.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <span class="me-auto small text-muted" id="modalStatus"></span>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-primary" id="btnSimpanNilai">
                            <i class="ri-save-line me-1"></i>Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('script')
<style>
    .student-table .badge.fs-6 { font-size: .8rem !important; }

    /* HP: baris tabel berubah menjadi kartu agar tidak perlu geser horizontal. */
    @media (max-width: 767.98px) {
        .student-table { display: block; width: 100%; }
        .student-table thead { display: none; }
        .student-table tbody { display: block; width: 100%; }
        .student-table tbody tr {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .25rem .75rem;
            width: 100%;
            padding: .75rem;
            border-bottom: 1px solid var(--bs-border-color);
        }
        .student-table tbody td { display: block; border: 0 !important; padding: .1rem 0; min-width: 0; }
        .student-table tbody td[data-label="No"] { display: none; }
        .student-table tbody td[data-label="Santri"] { grid-column: 1 / -1; }
        .student-table tbody td[data-label="NR Final"] { grid-column: 1; }
        .student-table tbody td[data-label="Aksi"] { grid-column: 2; text-align: right; }
        .student-table tbody td[data-label="Aksi"] .btn { width: 100%; }
        .student-table tbody td.col-score::before {
            content: attr(data-label) ": ";
            color: var(--bs-secondary-color);
            font-size: 11px;
        }
        .student-table tbody td[data-label="Jumlah"]::before { content: "Jumlah: "; font-size: 11px; }
        .student-table tbody td[data-label="NR Final"]::before { content: "NR: "; font-size: 11px; }
    }
    @media (min-width: 768px) {
        .student-table { table-layout: fixed; width: 100%; }
    }
</style>

@if($group && $book && $rows->isNotEmpty())
<script>
    (function () {
        var GROUP_ID = @json($group->id);
        var BOOK_ID = @json($book->id);
        var SEMESTER = @json($semester);
        var TOTAL_WEIGHT = @json($totalWeight);
        var STORE_URL = @json(route('user.tahfidz.penilaian.store', ['userId' => $userId]));
        var CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        var modalEl = document.getElementById('modalNilai');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var namaEl = document.getElementById('modalSantriNama');
        var infoEl = document.getElementById('modalSantriInfo');
        var nrEl = document.getElementById('modalNr');
        var jmlEl = document.getElementById('modalJumlah');
        var statusEl = document.getElementById('modalStatus');
        var inputs = Array.prototype.slice.call(modalEl.querySelectorAll('.nilai-input'));
        var ketInput = document.getElementById('inp-ket');
        var currentStudent = null;
        var eksisting = {};   // nilai santri saat modal dibuka

        function fmt(v) {
            if (v === null || v === undefined || v === '') return '—';
            return Number(v).toFixed(1);
        }

        function setStatus(msg, cls) {
            statusEl.textContent = msg;
            statusEl.className = 'me-auto small ' + (cls || 'text-muted');
        }

        // ── Buka modal dengan data baris ─────────────────────────────
        modalEl.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;

            currentStudent = btn.getAttribute('data-student');
            namaEl.textContent = btn.getAttribute('data-name');
            infoEl.textContent = [btn.getAttribute('data-nis'), btn.getAttribute('data-rombel')]
                .filter(Boolean).join(' · ');
            setStatus('', 'text-muted');

            eksisting = {};
            inputs.forEach(function (input) {
                var cell = document.querySelector('[data-score="' + currentStudent + '.' + input.dataset.key + '"]');
                var raw = cell ? cell.textContent.trim() : '';
                eksisting[input.dataset.key] = (raw === '—' || raw === '') ? '' : raw;
                input.value = eksisting[input.dataset.key];
            });
            ketInput.value = btn.getAttribute('data-ket') || '';
            hitung();
            // Fokus ke kolom pertama yang masih kosong supaya guru bisa
            // langsung mengetik.
            var kosong = inputs.find(function (i) { return i.value === ''; }) || inputs[0];
            if (kosong) setTimeout(function () { kosong.focus(); kosong.select && kosong.select(); }, 250);
        });

        function nilaiTerisi() {
            return inputs
                .map(function (i) { return i.value === '' ? null : parseFloat(i.value); })
                .filter(function (v) { return v !== null && !isNaN(v); });
        }

        // Pratinjau NR Final memakai bobot kolom yang isinya ada di modal.
        // Angka pastinya tetap dihitung server (NPT & Tikrar ikut masuk).
        function hitung() {
            var semua = {};
            inputs.forEach(function (i) { semua[i.dataset.key] = i.value === '' ? null : parseFloat(i.value); });
            var weights = @json($weights);
            var sum = 0, wsum = 0;
            Object.keys(semua).forEach(function (k) {
                if (semua[k] === null || isNaN(semua[k])) return;
                var w = parseFloat(weights[k] || 0);
                sum += semua[k] * w;
                wsum += w;
            });
            if (wsum === 0) { nrEl.textContent = '—'; jmlEl.textContent = '—'; return; }
            var nr = sum / wsum;
            nrEl.textContent = nr.toFixed(1);
            jmlEl.textContent = (nr * TOTAL_WEIGHT / 100).toFixed(1);
        }

        inputs.forEach(function (input) {
            input.addEventListener('input', function () {
                if (this.value !== '' && !isNaN(this.value)) {
                    if (parseFloat(this.value) > 100) this.value = '100';
                    if (parseFloat(this.value) < 0) this.value = '0';
                }
                hitung();
                setStatus('Belum disimpan', 'text-warning');
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); simpan(); }
            });
        });
        ketInput.addEventListener('input', function () { setStatus('Belum disimpan', 'text-warning'); });

        // ── Kirim satu santri lewat endpoint yang sama dengan autosave ──
        function payload(extra) {
            var body = {
                tahfidz_group_id: GROUP_ID,
                tahfidz_gradebook_id: BOOK_ID,
                semester: SEMESTER,
            };
            if (extra === 'materi') {
                body.materi = bacaMateri();
            } else {
                var row = { ket: ketInput.value };
                inputs.forEach(function (i) { row[i.dataset.key] = i.value === '' ? null : i.value; });
                body.scores = {};
                body.scores[currentStudent] = row;
                body.materi = bacaMateri();
            }
            return body;
        }

        function simpan() {
            if (!currentStudent) return;
            setStatus('Menyimpan…', 'text-muted');
            return fetch(STORE_URL, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify(payload()),
            })
            .then(function (res) { return res.json().catch(function () { return { success: false }; }); })
            .then(function (data) {
                if (!data || data.success !== true) {
                    setStatus(data && data.message ? data.message : 'Gagal menyimpan', 'text-danger');
                    return;
                }
                perbaruiBaris(currentStudent, data.saved_rows || []);
                setStatus('Tersimpan', 'text-success');
            })
            .catch(function () { setStatus('Gagal menyimpan — periksa koneksi', 'text-danger'); });
        }

        function perbaruiBaris(studentId, rows) {
            var row = rows.find(function (r) { return String(r.student_id) === String(studentId); });
            if (!row) return;

            inputs.forEach(function (i) {
                var cell = document.querySelector('[data-score="' + studentId + '.' + i.dataset.key + '"]');
                if (!cell) return;
                var v = row[i.dataset.key];
                cell.textContent = (v === null || v === undefined || v === '') ? '—' : v;
                cell.classList.toggle('text-muted', v === null || v === undefined || v === '');
            });

            var nr = document.querySelector('[data-nr="' + studentId + '"]');
            if (nr) {
                nr.textContent = fmt(row.nr_final);
                nr.className = 'badge fs-6 ' + (row.nr_final !== null ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary');
            }
            var jml = document.querySelector('[data-jml="' + studentId + '"]');
            if (jml) jml.textContent = fmt(row.weighted);

            var btn = document.querySelector('#tab-santri button[data-student="' + studentId + '"]');
            if (btn) {
                var adaNilai = ['ziyadah', 'rabt', 'sts', 'sas'].filter(function (k) {
                    return row[k] !== null && row[k] !== undefined;
                }).length;
                btn.className = 'btn btn-sm ' + (adaNilai > 0 ? 'btn-outline-primary' : 'btn-primary');
            }
        }

        // Simpan saat modal ditutup bila ada perubahan.
        modalEl.addEventListener('hide.bs.modal', function () {
            if (statusEl.classList.contains('text-warning')) simpan();
        });

        document.getElementById('btnSimpanNilai').addEventListener('click', function () {
            simpan().then(function () { setTimeout(function () { modal.hide(); }, 300); });
        });

        // ── Materi ujian STS/SAS ────────────────────────────────────────
        var materiModalEl = document.getElementById('modalMateri');
        var positionUrl = @json(route('user.tahfidz.quran-position', ['userId' => $userId]));

        function bacaMateri() {
            var materi = {};
            ['sts', 'sas'].forEach(function (jenis) {
                var out = {};
                ['surah_start', 'ayah_start', 'surah_end', 'ayah_end', 'juz', 'page_start', 'line_start', 'page_end', 'line_end']
                    .forEach(function (field) {
                        var el = materiModalEl.querySelector('[name="materi[' + jenis + '][' + field + ']"]');
                        if (el) out[field] = el.value;
                    });
                materi[jenis] = out;
            });
            return materi;
        }

        function fillExam(jenis) {
            var startSelect = materiModalEl.querySelector('.exam-surah[data-exam="' + jenis + '"][data-role="start"]');
            var endSelect = materiModalEl.querySelector('.exam-surah[data-exam="' + jenis + '"][data-role="end"]');
            var startAyah = materiModalEl.querySelector('.exam-ayat[data-exam="' + jenis + '"][data-role="start"]');
            var endAyah = materiModalEl.querySelector('.exam-ayat[data-exam="' + jenis + '"][data-role="end"]');
            var hint = document.getElementById('hint-' + jenis);

            var s = startSelect && startSelect.selectedOptions[0] && startSelect.selectedOptions[0].dataset.number;
            var a = startAyah && startAyah.value;
            if (!s || !a) return;

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
                    if (!data.found || !data.position) {
                        if (hint) hint.innerHTML = '<i class="ri-error-warning-line me-1 text-warning"></i>Posisi ayat tidak ditemukan, isi halaman dan baris manual.';
                        return;
                    }
                    materiModalEl.querySelector('[name="materi[' + jenis + '][page_start]"]').value = data.position.page_start;
                    materiModalEl.querySelector('[name="materi[' + jenis + '][line_start]"]').value = data.position.line_start;
                    materiModalEl.querySelector('[name="materi[' + jenis + '][page_end]"]').value = data.position.page_end;
                    materiModalEl.querySelector('[name="materi[' + jenis + '][line_end]"]').value = data.position.line_end;
                    if (hint) {
                        hint.innerHTML = '<i class="ri-bookmark-line me-1 text-success"></i>Halaman ' + data.position.page_start + '–'
                            + data.position.page_end + ' · baris ' + data.position.line_start + '–' + data.position.line_end;
                    }
                    perbaruiBadgeMateri(jenis);
                })
                .catch(function () {
                    if (hint) hint.innerHTML = '<i class="ri-error-warning-line me-1 text-warning"></i>Gagal mengambil posisi ayat, isi halaman dan baris manual.';
                });
        }

        function perbaruiBadgeMateri(jenis) {
            var surah = materiModalEl.querySelector('[name="materi[' + jenis + '][surah_start]"]');
            var ayah = materiModalEl.querySelector('[name="materi[' + jenis + '][ayah_start]"]').value;
            var surahAkhir = materiModalEl.querySelector('[name="materi[' + jenis + '][surah_end]"]').value;
            var ayahAkhir = materiModalEl.querySelector('[name="materi[' + jenis + '][ayah_end]"]').value;
            var badge = document.querySelector('.exam-preview[data-exam="' + jenis + '"]');
            if (!badge || !surah || !surah.value) return;

            var label = 'Materi ' + jenis.toUpperCase() + ': ' + surah.value + ':' + ayah;
            if (surahAkhir && surahAkhir !== surah.value) label += ' – ' + surahAkhir + (ayahAkhir ? ':' + ayahAkhir : '');
            badge.innerHTML = '<i class="ri-file-text-line me-1"></i>' + label;
            badge.className = 'badge bg-primary-subtle text-primary exam-preview';
        }

        materiModalEl.querySelectorAll('.exam-surah, .exam-ayat').forEach(function (el) {
            el.addEventListener('change', function () { fillExam(this.dataset.exam); });
        });

        document.getElementById('btnSimpanMateri').addEventListener('click', function () {
            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…';
            fetch(STORE_URL, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({
                    tahfidz_group_id: GROUP_ID,
                    tahfidz_gradebook_id: BOOK_ID,
                    semester: SEMESTER,
                    materi: bacaMateri(),
                }),
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-check-line me-1"></i>Tersimpan';
                if (!data.success) {
                    btn.innerHTML = '<i class="ri-save-line me-1"></i>Simpan Materi';
                    alert(data.message || 'Gagal menyimpan materi.');
                    return;
                }
                perbaruiBadgeMateri('sts');
                perbaruiBadgeMateri('sas');
                setTimeout(function () {
                    bootstrap.Modal.getOrCreateInstance(materiModalEl).hide();
                    btn.innerHTML = '<i class="ri-save-line me-1"></i>Simpan Materi';
                }, 700);
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-save-line me-1"></i>Simpan Materi';
                alert('Gagal menyimpan materi — periksa koneksi.');
            });
        });

        // ── Filter pencarian di sisi klien ─────────────────────────────
        window.filterSantriRows = function (query) {
            var q = (query || '').toLowerCase().trim();
            document.querySelectorAll('#tab-santri tbody tr[data-name]').forEach(function (row) {
                var hay = row.getAttribute('data-name') || '';
                row.style.display = (q === '' || hay.indexOf(q) !== -1) ? '' : 'none';
            });
        };
    })();
</script>
@endif
@endsection
