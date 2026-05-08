@extends('layouts.master')
@section('title') Presensi Siswa @endsection

@push('style')
@endpush

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

    {{-- Info Buku — full width atas --}}
    <div class="card border-primary mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center g-2">
                <div class="col-md-auto">
                    <p class="mb-n1 btn btn-primary btn-sm" style="font-size: 10px;"><i class="ri-book-2-line me-1"></i>{{ $book['adminBook']->subject->name ?? '-' }}</p>
                    <p class="mb-n1 btn btn-secondary btn-sm"><i class="ri-team-line me-1"></i>{{ $book['adminBook']->studyGroup->name }}</p>
                    <p class="mb-n1 btn btn-dark btn-sm"><i class="ri-calendar-line me-1"></i>{{ ucfirst($book['adminBook']->semester) }}</p>
                    <p class="mb-n1 btn btn-warning btn-sm"><i class="ri-government-line me-1"></i>{{ $book['adminBook']->academicYear->name ?? '-' }}</p>
                </div>
                <div class="col-md d-flex justify-content-md-end">
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

                        {{-- Filter: tanggal + cari nama --}}
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
                                        <label class="form-label">
                                            <i class="ri-calendar-event-line text-primary me-1"></i>Tanggal Pertemuan
                                        </label>
                                        <input type="date" name="attendance_date" class="form-control"
                                               value="{{ $selectedDate }}"
                                               onchange="submitFilter(this.value)">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            <i class="ri-search-line text-primary me-1"></i>Cari Nama Siswa
                                        </label>
                                        <input type="text" class="form-control" id="searchStudentInput"
                                               placeholder="Ketik nama siswa..."
                                               value="{{ request('search_student') }}"
                                               oninput="filterTable(this.value)">
                                    </div>
                                    <div class="col-md-5 d-flex align-items-end">
                                        <p class="text-muted mb-2">
                                            <i class="ri-group-line me-1"></i>
                                            <span id="studentCount">{{ $students->count() }}</span> siswa
                                            &bull; {{ $meetings->count() }} pertemuan tercatat
                                            @if($presensiMap->count() > 0)
                                                <span class="badge bg-success text-white ms-1">Data tersimpan</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive mx-3">
                                <table class="table table-bordered table-striped table-hover mb-0" id="presensiTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:40px">#</th>
                                            <th style="width:80px">NIS</th>
                                            <th>Nama Siswa</th>
                                            <th style="width:50px;text-align:center" title="Hadir">H</th>
                                            <th style="width:50px;text-align:center" title="Izin">I</th>
                                            <th style="width:50px;text-align:center" title="Sakit">S</th>
                                            <th style="width:50px;text-align:center" title="Alpa">A</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($students as $i => $s)
                                        @php
                                            $saved = $presensiMap->get($s->student_id);
                                        @endphp
                                        <tr class="student-row">
                                            <td class="text-center fw-bold text-muted">{{ $i + 1 }}</td>
                                            <td class="text-center">{{ $s->student->nis ?? '-' }}</td>
                                            <td class="student-name">{{ $s->student->name ?? '-' }}</td>
                                            <td class="text-center">
                                                <input type="radio"
                                                       name="status[{{ $s->student_id }}]"
                                                       value="hadir"
                                                       {{ ($saved ? $saved->status : 'hadir') === 'hadir' ? 'checked' : '' }}>
                                            </td>
                                            <td class="text-center">
                                                <input type="radio"
                                                       name="status[{{ $s->student_id }}]"
                                                       value="izin"
                                                       {{ $saved && $saved->status === 'izin' ? 'checked' : '' }}>
                                            </td>
                                            <td class="text-center">
                                                <input type="radio"
                                                       name="status[{{ $s->student_id }}]"
                                                       value="sakit"
                                                       {{ $saved && $saved->status === 'sakit' ? 'checked' : '' }}>
                                            </td>
                                            <td class="text-center">
                                                <input type="radio"
                                                       name="status[{{ $s->student_id }}]"
                                                       value="alpa"
                                                       {{ $saved && $saved->status === 'alpa' ? 'checked' : '' }}>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="7" class="text-center text-muted py-3">Belum ada siswa.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="card-footer border-top-dashed bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted">
                                        <span class="badge bg-danger text-white">A</span> Alpa &nbsp;
                                        <span class="badge bg-warning text-dark">I</span> Izin &nbsp;
                                        <span class="badge bg-success text-white">S</span> Sakit &nbsp;
                                        <span class="badge bg-primary text-white">H</span> Hadir (default)
                                        @if($presensiMap->count() > 0)
                                            &nbsp;<span class="text-success"><i class="ri-checkbox-circle-fill me-1"></i>Data sudah tersimpan untuk tanggal ini</span>
                                        @endif
                                    </div>
                                    <button type="submit" class="btn btn-primary">
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
                                    <label class="form-label">
                                        <i class="ri-calendar-line text-primary me-1"></i>Cari berdasarkan tanggal
                                    </label>
                                    <input type="date" name="search_date" class="form-control"
                                           value="{{ request('search_date') }}">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-search-line me-1"></i>Cari
                                        </button>
                                        <a href="{{ route('user.schools.guru-mapel.w1', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id, '_tab' => 'riwayat']) }}"
                                           class="btn btn-outline-secondary">
                                            <i class="ri-reset-right-line"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <p class="text-muted mb-0">
                                        <i class="ri-list-check me-1"></i>{{ $meetings->count() }} pertemuan ditemukan
                                    </p>
                                </div>
                            </form>

                            @if(request('search_date'))
                                <div class="alert alert-info py-2 mb-3">
                                    <i class="ri-information-line me-1"></i>
                                    Menampilkan presensi untuk tanggal:
                                    <strong>{{ \Carbon\Carbon::parse(request('search_date'))->translatedFormat('d F Y') }}</strong>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover mb-0" id="presensiTable">
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
                                                <span class="badge bg-primary">{{ $hadir }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning text-dark">{{ $izin }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success">{{ $sakit }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-danger">{{ $alpa }}</span>
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
                                    <label class="form-label">
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
                                        <span class="badge bg-primary text-white">H</span> Hadir
                                        <span class="badge bg-warning text-dark">I</span> Izin
                                        <span class="badge bg-success">S</span> Sakit
                                        <span class="badge bg-danger">A</span> Alpa
                                        <span class="badge bg-secondary">–</span> Blm Presensi
                                    </span>
                                </div>
                            </div>

                            @if($rekapSelectedMonth)
                                @if($rekapStudentData->count() && collect($rekapStudentData->first()->per_meeting)->count())
                                    {{-- Hitung jumlah meeting columns --}}
                                    @php
                                        $meetingCount = collect($rekapStudentData->first()->per_meeting)->count();
                                        // total colspan: # + nama + meeting columns + 5 summary cols
                                        $summaryColCount = 5;
                                        $tableWidth = 2 + $meetingCount + $summaryColCount;
                                    @endphp

                                    <div class="table-responsive rekap-table-wrapper">
                                        <table class="table table-bordered table-striped table-hover mb-0" id="presensiTable">
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
                                                    {{-- <th class="text-center align-middle text-primary" style="min-width:44px;">Tot<br>H</th>
                                                    <th class="text-center align-middle text-warning" style="min-width:44px;">Tot<br>I</th>
                                                    <th class="text-center align-middle text-success" style="min-width:44px;">Tot<br>S</th>
                                                    <th class="text-center align-middle text-danger" style="min-width:44px;">Tot<br>A</th> --}}
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
                                                                    'hadir' => 'text-primary',
                                                                    'izin'  => 'text-warning',
                                                                    'sakit' => 'text-success',
                                                                    'alpa'  => 'text-danger',
                                                                    default  => 'text-muted',
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
                                                        {{-- <td class="text-center align-middle text-primary">
                                                            {{ $sd->hadir }}
                                                        </td>
                                                        <td class="text-center align-middle text-warning">
                                                            {{ $sd->izin }}
                                                        </td>
                                                        <td class="text-center align-middle text-success">
                                                            {{ $sd->sakit }}
                                                        </td>
                                                        <td class="text-center align-middle text-danger">
                                                            {{ $sd->alpa }}
                                                        </td> --}}
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
                                    <div class="row mt-2">
                                        <div class="col-md-3">
                                            <div class="card border-primary">
                                                <div class="card-body py-1 text-center">
                                                    <strong class="text-primary">{{ $totalHadir }}</strong>
                                                    <small class="text-muted d-block">Total Hadir</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-warning">
                                                <div class="card-body py-1 text-center">
                                                    <strong class="text-warning">{{ $totalIzin }}</strong>
                                                    <small class="text-muted d-block">Total Izin</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-success">
                                                <div class="card-body py-1 text-center">
                                                    <strong class="text-success">{{ $totalSakit }}</strong>
                                                    <small class="text-muted d-block">Total Sakit</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-danger">
                                                <div class="card-body py-1 text-center">
                                                    <strong class="text-danger">{{ $totalAlpa }}</strong>
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

        // Filter table rows by student name
        function filterTable(query) {
            query = query.toLowerCase().trim();
            const rows = document.querySelectorAll('#presensiTable .student-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const nameCell = row.querySelector('.student-name');
                if (!nameCell) return;

                const name = nameCell.textContent.toLowerCase();
                const match = name.includes(query);
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            document.getElementById('studentCount').textContent = visibleCount;
        }

        // Initialize search on page load
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('searchStudentInput');
            if (input && input.value) {
                filterTable(input.value);
            }
        });
    </script>
@endsection
