@extends('layouts.master')
@section('title') Presensi Siswa @endsection



@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Buku Admin Guru @endslot
        @slot('li_3') Presensi Siswa @endslot
        @slot('title') Presensi Siswa @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $activeTab = old('_tab', session('_tab', 'input'));
    @endphp

    <div class="card border-primary-subtle mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-md">
                    <h5 class="card-title mb-2"><i class="ri-article-line me-1 text-primary"></i>Buku Nilai — Presensi Siswa</h5>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-primary-subtle text-primary"><i class="ri-book-2-line me-1"></i>{{ $book['adminBook']->subject->name ?? '-' }}</span>
                        <span class="badge bg-secondary-subtle text-secondary"><i class="ri-team-line me-1"></i>Kelas - {{ $book['adminBook']->studyGroup->name }}</span>
                        <span class="badge bg-dark-subtle text-dark"><i class="ri-calendar-line me-1"></i>Semester {{ ucfirst($book['adminBook']->semester) }}</span>
                        <span class="badge bg-warning-subtle text-warning"><i class="ri-calendar-2-line me-1"></i>{{ $book['adminBook']->academicYear->name ?? '-' }}</span>
                    </div>
                </div>
                <div class="col-md-auto d-flex justify-content-md-end align-items-center gap-2 flex-wrap">
                    <a href="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId, 'academic_year_id' => $book['adminBook']->academic_year_id, 'semester' => $book['adminBook']->semester]) }}" class="btn btn-soft-primary btn-sm">
                        <i class="ri-line-chart-line me-1"></i> Realisasi
                    </a>
                    <select class="form-select form-select-sm" style="width:auto;" onchange="location.href=this.value">
                        @foreach($books as $b)
                            <option value="{{ route('user.schools.guru-mapel.w1', ['userId' => $userId, 'adminBookId' => $b->id]) }}" {{ $b->id == $book['adminBook']->id ? 'selected' : '' }}>
                                {{ $b->subject->name ?? '-' }} | {{ $b->studyGroup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed py-2">
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('user.schools.guru-mapel.w1', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-primary">Presensi Siswa</a>
                        <a href="{{ route('user.schools.guru-mapel.w2', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Jurnal Pembelajaran</a>
                        <a href="{{ route('user.schools.guru-mapel.w3', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Nilai Sumatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w4', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Asesmen Formatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w5', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Penghargaan Akademik</a>
                        <a href="{{ route('user.schools.guru-mapel.w6', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Catatan Guru</a>
                    </div>
                </div>

                {{-- Tab Navigation --}}
                <ul class="nav nav-tabs px-3 pt-3 pb-0" id="presensiTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab == 'input' ? 'active' : '' }}" id="tab-input" data-bs-toggle="tab" data-bs-target="#panel-input" type="button" role="tab">
                            <i class="ri-edit-2-line me-1"></i>Input Presensi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab == 'riwayat' ? 'active' : '' }}" id="tab-riwayat" data-bs-toggle="tab" data-bs-target="#panel-riwayat" type="button" role="tab">
                            <i class="ri-history-line me-1"></i>Riwayat
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab == 'rekap' ? 'active' : '' }}" id="tab-rekap" data-bs-toggle="tab" data-bs-target="#panel-rekap" type="button" role="tab">
                            <i class="ri-bar-chart-box-line me-1"></i>Rekap Bulanan
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="presensiTabContent">

                    {{-- ============================================================ --}}
                    {{-- TAB 1: INPUT PRESENSI --}}
                    {{-- ============================================================ --}}
                    <div class="tab-pane fade {{ $activeTab == 'input' ? 'show active' : '' }}" id="panel-input" role="tabpanel">

                        <form method="GET" action="{{ route('user.schools.guru-mapel.w1', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}"
                              id="filterForm" class="d-none">
                            <input name="_tab" value="input">
                            <input name="attendance_date" id="filterDate" value="{{ $selectedDate }}">
                            <input name="search_student" id="filterStudent" value="{{ request('search_student') }}">
                        </form>

                        <form method="POST" action="{{ route('user.schools.guru-mapel.w1.store', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}"
                              id="presensiForm">
                            @csrf
                            <input type="hidden" name="_tab" value="input">
                            <div class="card-body pb-0">
                                <div class="row g-3 align-items-end mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label small">
                                            <i class="ri-calendar-event-line text-primary me-1"></i>Tanggal Pertemuan
                                        </label>
                                        <input type="date" name="attendance_date" class="form-control form-control-sm"
                                               value="{{ $selectedDate }}"
                                               onchange="submitFilter(this.value)">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">
                                            <i class="ri-search-line text-primary me-1"></i>Cari Nama Siswa
                                        </label>
                                        <input type="text" class="form-control form-control-sm" id="searchStudentInput"
                                               placeholder="Ketik nama siswa..."
                                               value="{{ request('search_student') }}"
                                               oninput="filterTable(this.value)">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">
                                            <i class="ri-filter-3-line text-primary me-1"></i>Status
                                        </label>
                                        <select id="statusFilter" class="form-select form-select-sm" onchange="filterTable(document.getElementById('searchStudentInput').value)">
                                            <option value="">Semua</option>
                                            <option value="hadir">Hadir</option>
                                            <option value="terlambat">Terlambat</option>
                                            <option value="sakit">Sakit</option>
                                            <option value="izin">Izin</option>
                                            <option value="alpa">Alpa</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <p class="text-muted mb-2 small">
                                            <i class="ri-group-line me-1"></i>
                                            <span id="studentCount">{{ $students->count() }}</span> siswa
                                            &bull; {{ $meetings->count() }} pertemuan tercatat
                                            @if($presensiMap->count() > 0)
                                                <span class="badge bg-success-subtle text-success ms-1">Data tersimpan</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Aksi cepat + rekapitulasi harian --}}
                            <div class="d-flex flex-wrap align-items-center gap-2 mx-3 mb-3">
                                <span class="text-muted small"><i class="ri-flashlight-line me-1"></i>Aksi Cepat:</span>
                                <button type="button" class="btn btn-sm btn-soft-primary" onclick="w1SetAll('hadir')">
                                    <i class="ri-checkbox-circle-line me-1"></i>Semua Hadir
                                </button>
                                <button type="button" class="btn btn-sm btn-soft-warning" onclick="w1SetAll('terlambat')">
                                    <i class="ri-timer-line me-1"></i>Semua Telat
                                </button>
                                <button type="button" class="btn btn-sm btn-soft-warning" onclick="w1SetAll('sakit')">
                                    <i class="ri-hospital-line me-1"></i>Semua Sakit
                                </button>
                                <button type="button" class="btn btn-sm btn-soft-success" onclick="w1SetAll('izin')">
                                    <i class="ri-file-text-line me-1"></i>Semua Izin
                                </button>
                                <button type="button" class="btn btn-sm btn-soft-danger" onclick="w1SetAll('alpa')">
                                    <i class="ri-close-circle-line me-1"></i>Semua Alfa
                                </button>

                                <span class="ms-auto d-flex flex-wrap gap-1 align-items-center" title="Rekapitulasi hari ini">
                                    <span class="text-muted small me-1"><i class="ri-bar-chart-2-line me-1"></i>Rekap:</span>
                                    <span class="badge bg-primary-subtle text-primary">H <span id="w1-count-hadir">0</span></span>
                                    <span class="badge bg-warning-subtle text-warning">T <span id="w1-count-terlambat">0</span></span>
                                    <span class="badge bg-warning-subtle text-warning">S <span id="w1-count-sakit">0</span></span>
                                    <span class="badge bg-success-subtle text-success">I <span id="w1-count-izin">0</span></span>
                                    <span class="badge bg-danger-subtle text-danger">A <span id="w1-count-alpa">0</span></span>
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle table-nowrap mb-0" id="presensiList">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width:50px">No</th>
                                            <th style="">NIS</th>
                                            <th>Nama</th>
                                            <th style="">Presensi Kehadiran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $statusColors = ['hadir' => 'primary', 'terlambat' => 'warning', 'sakit' => 'warning', 'izin' => 'success', 'alpa' => 'danger'];
                                        @endphp
                                        @forelse($students as $i => $s)
                                            @php
                                                $saved = $presensiMap->get($s->student_id);
                                                $cur = $saved?->status ?? 'hadir';
                                                $showNotes = $cur !== 'hadir';
                                            @endphp
                                            <tr class="presensi-row" data-sid="{{ $s->student_id }}"
                                                data-name="{{ strtolower($s->student->name ?? '') }}"
                                                data-status="{{ $cur }}">
                                                <td class="text-center text-muted">{{ $i + 1 }}</td>
                                                <td class="text-muted"><code>{{ $s->student->nis ?? '-' }}</code></td>
                                                <td class="">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar-xs">
                                                            <span class="avatar-title rounded-circle bg-primary text-white">
                                                                {{ strtoupper(mb_substr(($__w = preg_split('/\s+/', trim((string) $s->student->name)))[0] ?? '', 0, 1) . (count($__w) > 1 ? mb_substr($__w[count($__w) - 1], 0, 1) : mb_substr($__w[0] ?? '', 1, 1))) }}
                                                            </span>
                                                        </div>
                                                        <span class="">{{ $s->student->name }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="status-group d-inline-flex gap-2" data-sid="{{ $s->student_id }}">
                                                        @foreach(['hadir' => 'H', 'terlambat' => 'T', 'sakit' => 'S', 'izin' => 'I', 'alpa' => 'A'] as $st => $letter)
                                                            @php $color = $statusColors[$st] ?? 'primary'; @endphp
                                                            <button type="button"
                                                                    class="status-btn btn btn-sm btn-icon rounded-circle {{ $cur === $st ? 'btn-'.$color.' on' : 'btn-soft-'.$color }}"
                                                                    data-s="{{ $st }}" data-color="{{ $color }}"
                                                                    title="{{ ucfirst($st) }}"
                                                                    onclick="setW1Status('{{ $s->student_id }}', '{{ $st }}', this)">{{ $letter }}</button>
                                                        @endforeach
                                                    </div>

                                                    {{-- Form keterangan: muncul jika status bukan Hadir --}}
                                                    <div class="note-inline mt-2{{ $showNotes ? '' : ' d-none' }}" id="w1-note-wrap-{{ $s->student_id }}">
                                                        <input type="text"
                                                               name="notes[{{ $s->student_id }}]"
                                                               id="w1-notes-{{ $s->student_id }}"
                                                               class="form-control form-control-sm"
                                                               placeholder="Keterangan/Alasan ({{ $cur }})"
                                                               value="{{ $saved?->notes ?? '' }}"
                                                               maxlength="255">
                                                    </div>

                                                    <input type="hidden" name="status[{{ $s->student_id }}]" id="w1-status-{{ $s->student_id }}" value="{{ $cur }}">
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">Belum ada siswa.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="card-footer border-top-dashed bg-light">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="text-muted small">
                                        <span class="badge bg-secondary-subtle text-secondary">H</span> Hadir &nbsp;
                                        <span class="badge bg-secondary-subtle text-secondary">T</span> Terlambat &nbsp;
                                        <span class="badge bg-success-subtle text-success">I</span> Izin &nbsp;
                                        <span class="badge bg-warning-subtle text-warning">S</span> Sakit &nbsp;
                                        <span class="badge bg-secondary-subtle text-secondary">A</span> Alpa
                                        @if($presensiMap->count() > 0)
                                            &nbsp;<span class="text-success"><i class="ri-checkbox-circle-fill me-1"></i>Data sudah tersimpan untuk tanggal ini</span>
                                        @endif
                                    </div>
                                    <span id="w1-autosave-indicator" class="badge bg-light text-muted border me-2">Menunggu…</span>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ri-save-line me-1"></i> Simpan Presensi
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- TAB 2: RIWAYAT PRESENSI --}}
                    {{-- ============================================================ --}}
                    <div class="tab-pane fade {{ $activeTab == 'riwayat' ? 'show active' : '' }}" id="panel-riwayat" role="tabpanel">
                        <div class="card-body">
                            <form method="GET"
                                  action="{{ route('user.schools.guru-mapel.w1', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}"
                                  class="row g-3 mb-3">
                                <input type="hidden" name="_tab" value="riwayat">
                                <div class="col-md-4">
                                    <label class="form-label small">
                                        <i class="ri-calendar-line text-primary me-1"></i>Cari berdasarkan tanggal
                                    </label>
                                    <input type="date" name="search_date" class="form-control form-control-sm"
                                           value="{{ request('search_date') }}">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="ri-search-line me-1"></i>Cari
                                        </button>
                                        <a href="{{ route('user.schools.guru-mapel.w1', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id, '_tab' => 'riwayat']) }}"
                                           class="btn btn-outline-secondary btn-sm">
                                            <i class="ri-reset-right-line"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <p class="text-muted mb-0 small">
                                        <i class="ri-list-check me-1"></i>{{ $meetings->count() }} pertemuan ditemukan
                                    </p>
                                </div>
                            </form>

                            @if(request('search_date'))
                                <div class="alert alert-info py-2 mb-3 small">
                                    <i class="ri-information-line me-1"></i>
                                    Menampilkan presensi untuk tanggal:
                                    <strong>{{ \Carbon\Carbon::parse(request('search_date'))->translatedFormat('d F Y') }}</strong>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle mb-0 js-tools-table" id="presensiTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:50px">#</th>
                                            <th>Tanggal</th>
                                            <th>Hari</th>
                                            <th class="text-center">Hadir</th>
                                            <th class="text-center">Izin</th>
                                            <th class="text-center">Sakit</th>
                                            <th class="text-center">Alpa</th>
                                            <th class="text-center">Total Siswa</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($meetings as $idx => $meeting)
                                        @php
                                            $presensi = $meeting->presensiSiswa;
                                            $hadir = $presensi->where('status', 'hadir')->count();
                                            $izin  = $presensi->where('status', 'izin')->count();
                                            $sakit = $presensi->where('status', 'sakit')->count();
                                            $alpa  = $presensi->where('status', 'alpa')->count();
                                            $hari  = \Carbon\Carbon::parse($meeting->attendance_date)->translatedFormat('l');
                                        @endphp
                                        <tr>
                                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                            <td>
                                                <i class="ri-calendar-event-fill text-primary me-1"></i>
                                                {{ \Carbon\Carbon::parse($meeting->attendance_date)->translatedFormat('d M Y') }}
                                            </td>
                                            <td>{{ $hari }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $hadir }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success-subtle text-success">{{ $izin }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning-subtle text-warning">{{ $sakit }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $alpa }}</span>
                                            </td>
                                            <td class="text-center">{{ $presensi->count() }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('user.schools.guru-mapel.w1', [
                                                    'userId' => $userId,
                                                    'adminBookId' => $book['adminBook']->id,
                                                    '_tab' => 'input',
                                                    'attendance_date' => $meeting->attendance_date->toDateString(),
                                                ]) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="ri-eye-line"></i> Lihat
                                                </a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="ri-inbox-line fs-3 d-block mb-2"></i>
                                                @if(request('search_date'))
                                                    Tidak ada data presensi untuk tanggal tersebut.
                                                @else
                                                    Belum ada data presensi.
                                                @endif
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- TAB 3: REKAP BULANAN PER SISWA --}}
                    {{-- ============================================================ --}}
                    <div class="tab-pane fade {{ $activeTab == 'rekap' ? 'show active' : '' }}" id="panel-rekap" role="tabpanel">
                        <div class="card-body pb-0">
                            <div class="row g-3 align-items-end mb-3">
                                <div class="col-md-5">
                                    <label class="form-label small">
                                        <i class="ri-calendar-2-line text-primary me-1"></i>Pilih Bulan
                                    </label>
                                    <form method="GET"
                                          action="{{ route('user.schools.guru-mapel.w1', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}"
                                          id="rekapMonthForm">
                                        <input type="hidden" name="_tab" value="rekap">
                                        <select name="rekap_month" class="form-select form-select-sm"
                                                onchange="document.getElementById('rekapMonthForm').submit()">
                                            @forelse($recapPerMonth as $rm)
                                                <option value="{{ $rm['month'] }}"
                                                        {{ $rm['month'] == ($rekapMonth ?? '') ? 'selected' : '' }}>
                                                    {{ $rm['label'] }} ({{ $rm['total_meetings'] }} pertemuan)
                                                </option>
                                            @empty
                                                <option value="">—</option>
                                            @endforelse
                                        </select>
                                    </form>
                                </div>
                                <div class="col-md-7 text-end">
                                    <span class="text-muted small">
                                        <i class="ri-information-line me-1"></i>
                                        Kolom Pertemuan = tanggal hari tersebut
                                        &nbsp;|&nbsp;
                                        <span class="badge bg-secondary-subtle text-secondary">H</span> Hadir
                                        <span class="badge bg-success-subtle text-success">I</span> Izin
                                        <span class="badge bg-warning-subtle text-warning">S</span> Sakit
                                        <span class="badge bg-secondary-subtle text-secondary">A</span> Alpa
                                        <span class="badge bg-secondary-subtle text-secondary">–</span> Blm Presensi
                                    </span>
                                </div>
                            </div>

                            @if($rekapSelectedMonth)
                                @if($rekapStudentData->count() && collect($rekapStudentData->first()->per_meeting)->count())
                                    @php
                                        $meetingCount = collect($rekapStudentData->first()->per_meeting)->count();
                                        $summaryColCount = 5;
                                        $tableWidth = 2 + $meetingCount + $summaryColCount;
                                    @endphp

                                    <div class="table-responsive rekap-table-wrapper">
                                        <table class="table table-bordered table-hover align-middle mb-0 js-tools-table" id="presensiTableRekap">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center align-middle" style="width:40px; min-width:40px;">#</th>
                                                    <th class="text-center align-middle" style="min-width:150px;">Nama Siswa</th>
                                                    @foreach(collect($rekapStudentData->first()->per_meeting) as $mi => $m)
                                                        <th class="text-center align-middle" style="min-width:44px;" title="{{ $m['label'] }}">
                                                            {{ $m['label'] }}
                                                        </th>
                                                    @endforeach
                                                    <th class="text-center align-middle" style="min-width:50px;">Jml<br>Pert.</th>
                                                    <th class="text-center align-middle" style="min-width:60px;">Kehadiran<br>(%)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $rowNum = 1; @endphp
                                                @foreach($rekapStudentData as $sd)
                                                    @php
                                                        $kehadiranClass = $sd->kehadiran >= 90 ? 'text-success'
                                                            : ($sd->kehadiran >= 70 ? 'text-warning' : 'text-danger');
                                                    @endphp
                                                    <tr class="{{ $rowNum % 2 == 0 ? 'table-active' : '' }}">
                                                        <td class="text-center text-muted fw-bold align-middle">{{ $rowNum++ }}</td>
                                                        <td class="align-middle">
                                                            <span class="fw-semibold">{{ $sd->student->name ?? '-' }}</span>
                                                            <br><small class="text-muted">{{ $sd->student->nis ?? '' }}</small>
                                                        </td>
                                                        @foreach($sd->per_meeting as $pm)
                                                            @php
                                                                $cellClass = match ($pm['raw']) {
                                                                    'hadir' => 'text-secondary',
                                                                    'izin'  => 'text-success',
                                                                    'sakit' => 'text-warning',
                                                                    'alpa'  => 'text-secondary',
                                                                    default => 'text-muted',
                                                                };
                                                            @endphp
                                                            <td class="text-center align-middle {{ $cellClass }}"
                                                                style="font-size:0.75rem;"
                                                                title="{{ $pm['label'] }} — {{ $pm['raw'] ?? 'belum presensi' }}">
                                                                {{ $pm['status'] }}
                                                            </td>
                                                        @endforeach
                                                        <td class="text-center align-middle fw-bold">
                                                            {{ $sd->total_meetings }}
                                                        </td>
                                                        <td class="text-center align-middle fw-bold {{ $kehadiranClass }}">
                                                            {{ $sd->kehadiran }}%
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-light">
                                                @php
                                                    $totalHadir = $rekapStudentData->sum('hadir');
                                                    $totalIzin  = $rekapStudentData->sum('izin');
                                                    $totalSakit = $rekapStudentData->sum('sakit');
                                                    $totalAlpa  = $rekapStudentData->sum('alpa');
                                                    $totalPert  = $rekapStudentData->sum('total_meetings');
                                                    $avgKehadiran = $rekapStudentData->count() > 0
                                                        ? round($rekapStudentData->avg('kehadiran'), 1)
                                                        : 0;
                                                @endphp
                                            </tfoot>
                                        </table>
                                    </div>

                                    {{-- Keterangan legend di bawah tabel --}}
                                    <div class="row mt-2 g-2">
                                        <div class="col-md-3">
                                            <div class="card border-secondary-subtle">
                                                <div class="card-body py-1 text-center">
                                                    <strong class="text-secondary">{{ $totalHadir }}</strong>
                                                    <small class="text-muted d-block">Total Hadir</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-success-subtle">
                                                <div class="card-body py-1 text-center">
                                                    <strong class="text-success">{{ $totalIzin }}</strong>
                                                    <small class="text-muted d-block">Total Izin</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-warning-subtle">
                                                <div class="card-body py-1 text-center">
                                                    <strong class="text-warning">{{ $totalSakit }}</strong>
                                                    <small class="text-muted d-block">Total Sakit</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-secondary-subtle">
                                                <div class="card-body py-1 text-center">
                                                    <strong class="text-secondary">{{ $totalAlpa }}</strong>
                                                    <small class="text-muted d-block">Total Alpa</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5">
                                        <i class="ri-bar-chart-box-line fs-1 d-block mb-3"></i>
                                        <p>Tidak ada pertemuan yang tercatat untuk bulan ini.</p>
                                    </div>
                                @endif
                            @else
                                <div class="text-center text-muted py-5">
                                    <i class="ri-bar-chart-box-line fs-1 d-block mb-3"></i>
                                    <p>Belum ada data presensi untuk direkap.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>{{-- end tab-content --}}
            </div>
        </div>
    </div>

    <script>
        // Submit filter form when date changes
        function submitFilter(date) {
            const form = document.getElementById('filterForm') || createFilterForm(date);
            document.getElementById('filterDate').value = date;
            form.submit();
        }

        function createFilterForm(date) {
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = '{{ route("user.schools.guru-mapel.w1", ["userId" => $userId, "adminBookId" => $book["adminBook"]->id]) }}';
            form.id = 'filterForm';
            form.innerHTML = `
                <input name="_tab" value="input">
                <input name="attendance_date" id="filterDate" value="${date}">
            `;
            form.style.display = 'none';
            document.body.appendChild(form);
            return form;
        }

        // Filter table rows by student name + status
        function filterTable(query) {
            query = (query || '').toLowerCase().trim();
            var statusValue = document.getElementById('statusFilter') ? document.getElementById('statusFilter').value : '';
            var visibleCount = 0;

            document.querySelectorAll('#presensiList .presensi-row').forEach(function (row) {
                var name = (row.getAttribute('data-name') || '').toLowerCase();
                var st = row.getAttribute('data-status') || 'hadir';
                var match = name.indexOf(query) !== -1 && (statusValue === '' || st === statusValue);
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            var countEl = document.getElementById('studentCount');
            if (countEl) countEl.textContent = visibleCount;
        }

        // Initialize search on page load
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('searchStudentInput');
            if (input && input.value) {
                filterTable(input.value);
            }
        });
    </script>
    <script>
    // ─── Autosave + Aksi Cepat + Rekap Harian (wizard 1) ───
    (function () {
        var form = document.getElementById('presensiForm');
        var list = document.getElementById('presensiList');
        if (!form || !list) return;

        var url = form.getAttribute('action');
        var token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        var dateInput = form.querySelector('[name="attendance_date"]');
        var badge = document.getElementById('w1-autosave-indicator');
        var timer = null;
        var lastSid = null;

        function setBadge(cls, text) {
            if (!badge) return;
            badge.className = 'badge me-2 ' + cls;
            badge.textContent = text;
        }

        function send(fd) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: fd,
            })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
            .then(function (d) {
                if (d && d.saved) {
                    var t = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    setBadge('bg-success-subtle text-success border', 'Tersimpan ' + t);
                    setTimeout(function () { setBadge('bg-light text-muted border', 'Menunggu…'); }, 2500);
                } else {
                    setBadge('bg-danger-subtle text-danger border', (d && d.message) ? d.message : 'Gagal menyimpan');
                }
            })
            .catch(function () { setBadge('bg-danger-subtle text-danger border', 'Gagal menyimpan'); });
        }

        function buildFormData(onlySid) {
            var fd = new FormData();
            fd.append('_token', token);
            fd.append('attendance_date', dateInput ? dateInput.value : '{{ $selectedDate }}');

            var rows;
            if (onlySid) {
                var found = list.querySelector('.presensi-row[data-sid="' + onlySid + '"]');
                rows = found ? [found] : [];
            } else {
                rows = Array.prototype.slice.call(list.querySelectorAll('.presensi-row'));
            }

            rows.forEach(function (row) {
                var sid = row.getAttribute('data-sid');
                var st = document.getElementById('w1-status-' + sid);
                var nt = document.getElementById('w1-notes-' + sid);
                if (st) fd.append('status[' + sid + ']', st.value);
                if (nt) fd.append('notes[' + sid + ']', nt.value || '');
            });

            return fd;
        }

        function schedule(sid) {
            if (!sid) return;
            lastSid = sid;
            clearTimeout(timer);
            setBadge('bg-light text-muted border', 'Menyimpan…');
            timer = setTimeout(function () { send(buildFormData(lastSid)); }, 900);
        }

        function saveAll() {
            clearTimeout(timer);
            setBadge('bg-light text-muted border', 'Menyimpan semua…');
            send(buildFormData(null));
        }

        function updateW1Rekap() {
            var counts = { hadir: 0, terlambat: 0, sakit: 0, izin: 0, alpa: 0 };

            list.querySelectorAll('.presensi-row').forEach(function (row) {
                var sid = row.getAttribute('data-sid');
                var st = document.getElementById('w1-status-' + sid);
                var v = st ? st.value : 'hadir';
                if (counts[v] !== undefined) counts[v]++;
            });

            Object.keys(counts).forEach(function (k) {
                var el = document.getElementById('w1-count-' + k);
                if (el) el.textContent = counts[k];
            });
        }

        // Aksi cepat: set semua santri ke satu status lalu simpan sekaligus
        window.w1SetAll = function (status) {
            list.querySelectorAll('.presensi-row').forEach(function (row) {
                var sid = row.getAttribute('data-sid');
                var btn = row.querySelector('.status-btn[data-s="' + status + '"]');
                if (btn && typeof window.setW1Status === 'function') {
                    window.setW1Status(sid, status, btn);
                }
            });
            updateW1Rekap();
            saveAll();
        };

        // Klik tombol status → autosave + segarkan rekap
        list.addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('.status-btn');
            if (!btn) return;
            var row = btn.closest('.presensi-row');
            if (row) {
                schedule(row.getAttribute('data-sid'));
                updateW1Rekap();
            }
        });

        // Keterangan diketik → autosave
        list.addEventListener('input', function (e) {
            if (e.target && e.target.id && e.target.id.indexOf('w1-notes-') === 0) {
                schedule(e.target.id.replace('w1-notes-', ''));
            }
        });

        window.__w1AutoSave = schedule;
        window.__w1SaveAll = saveAll;

        updateW1Rekap();
        document.addEventListener('DOMContentLoaded', updateW1Rekap);
    })();
    </script>

    @include('nilai-guru.partials.table-tools')
@endsection