@extends('layouts.master')
@section('title') Cetak Rapor — {{ $studyGroup->name ?? '' }} @endsection

@section('css')
    @include('kurikulum._styles')
    <style>
        /* Pastikan modal pengaturan rapor selalu bisa di-scroll sampai tombol simpan. */
        #layoutSettingsModal .modal-content,
        #signatureSettingsModal .modal-content {
            max-height: calc(100vh - 2rem);
        }
        #layoutSettingsModal .modal-body,
        #signatureSettingsModal .modal-body {
            overflow-y: auto;
            overscroll-behavior: contain;
        }
        #layoutSettingsModal form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }
        #layoutSettingsModal form > .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }
    </style>
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? Auth::id();
        $previewBaseUrl = route('user.schools.nilai-kelas.rapor.cetak', [
            'userId' => $userId,
            'studyGroupId' => $studyGroup->id,
            'studentId' => '__STUDENT__',
        ]);
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">Nilai Kelas</a> @endslot
        @slot('title') Cetak Rapor @endslot
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

    @php
        $stats = $stats ?? ['total_santri' => 0, 'catatan_terisi' => 0, 'nilai_final' => 0];
    @endphp

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-group-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Santri</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total_santri']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Santri aktif di {{ $studyGroup->name }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-chat-1-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Catatan Wali Terisi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['catatan_terisi']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-check-double-line me-1"></i>Dari {{ number_format($stats['total_santri']) }} santri</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-save-3-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Nilai Final Tersimpan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['nilai_final']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Tersimpan saat cetak rapor SAS</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-calendar-check-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Periode</p>
                            <h3 class="fw-bold ff-secondary mb-0" style="font-size:18px;">{{ ucfirst($selectedSem) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-calendar-2-line me-1"></i>{{ $selectedAy?->name ?? 'Belum ada TA' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Publikasi Rapor ke Wali Santri --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-send-plane-line text-success me-1"></i>Publikasi Rapor</h5>
                <p class="text-muted mb-0 small">Hanya rapor yang dipublikasikan yang dapat dilihat oleh wali santri.</p>
            </div>
            @php
                $pipeline = [
                    'draft' => $registrations->where('status', 'draft')->where('is_published', false)->count(),
                    'in_progress' => $registrations->where('status', 'in_progress')->where('is_published', false)->count(),
                    'finalized' => $registrations->where('status', 'finalized')->where('is_published', false)->count(),
                    'published' => $stats['published'] ?? 0,
                ];
            @endphp
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-secondary-subtle text-secondary">Draft {{ $pipeline['draft'] }}</span>
                <span class="badge bg-warning-subtle text-warning">Proses {{ $pipeline['in_progress'] }}</span>
                <span class="badge bg-info-subtle text-info">Final {{ $pipeline['finalized'] }}</span>
                <span class="badge bg-success-subtle text-success">Published {{ $pipeline['published'] }}</span>
            </div>
        </div>
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="small text-muted">
                <i class="ri-information-line me-1"></i>
                Pipeline: <strong>Draft → In Progress → Finalized → Published</strong>.
                Publikasi mencakup seluruh santri aktif di {{ $studyGroup->name }} untuk
                {{ $selectedAy?->name ?? 'TA aktif' }} — {{ ucfirst($selectedSem) }}.
            </div>
            @if($canPublish ?? false)
                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('user.schools.nilai-kelas.rapor.finalize', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                          onsubmit="return confirm('Finalisasi rapor rombel ini? Status berubah menjadi Finalized.')">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <button type="submit" class="btn btn-outline-info btn-sm">
                            <i class="ri-check-double-line me-1"></i>Finalisasi Rapor
                        </button>
                    </form>
                    <form method="POST" action="{{ route('user.schools.nilai-kelas.rapor.unpublish', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                          onsubmit="return confirm('Batalkan publikasi rapor? Wali tidak akan dapat melihat rapor ini.')">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="ri-eye-off-line me-1"></i>Batalkan Publikasi
                        </button>
                    </form>
                    <form method="POST" action="{{ route('user.schools.nilai-kelas.rapor.publish', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                          onsubmit="return confirm('Publikasikan rapor STS ke wali santri?')">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <input type="hidden" name="jenis" value="sts">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="ri-send-plane-line me-1"></i>Publikasikan STS
                        </button>
                    </form>
                    <form method="POST" action="{{ route('user.schools.nilai-kelas.rapor.publish', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                          onsubmit="return confirm('Publikasikan rapor SAS ke wali santri?')">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <input type="hidden" name="jenis" value="sas">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="ri-send-plane-line me-1"></i>Publikasikan SAS
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Pengaturan Tanggal Pembagian Rapor --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-calendar-event-line text-success me-1"></i>Tanggal Pembagian Rapor</h5>
            <p class="text-muted mb-0 small">Dipakai pada dokumen rapor (cetak &amp; arsip) untuk TA/semester ini — menggantikan tanggal cetak otomatis.</p>
        </div>
        <div class="card-body">
            <form method="POST"
                  action="{{ route('user.schools.nilai-kelas.rapor.pengaturan', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                  class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                <input type="hidden" name="semester" value="{{ $selectedSem }}">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tanggal Pembagian</label>
                    <input type="date" name="distribution_date" class="form-control"
                           value="{{ $distributionSetting?->distribution_date?->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Kota</label>
                    <input type="text" name="city" class="form-control" maxlength="100"
                           value="{{ $distributionSetting?->city ?? $studyGroup->school?->city }}"
                           placeholder="Mataram">
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-success">
                        <i class="ri-save-line me-1"></i>Simpan
                    </button>
                </div>
                @if($distributionSetting?->distribution_date)
                    <div class="col-12">
                        <span class="badge bg-info-subtle text-info">
                            Aktif: {{ $distributionSetting->distribution_date->format('d M Y') }}
                            @if($distributionSetting->city) — {{ $distributionSetting->city }} @endif
                        </span>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="row g-3 mb-5">
        {{-- Cetak Rapor Santri: lebar penuh di bawah pengaturan --}}
        <div class="col-12 order-2">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">
                                <i class="ri-file-paper-2-line text-primary me-1"></i>
                                Cetak Rapor Santri
                            </h5>
                            <p class="text-muted mb-0" style="font-size:12px;">
                                <span class="badge bg-primary-subtle text-primary">{{ $stats['total_santri'] }} santri</span>
                                {{ $studyGroup->school?->name ?? '' }} — {{ $studyGroup->name }} — TA {{ $selectedAy?->name ?? '' }} Semester {{ ucfirst($selectedSem) }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            @if(($stats['total_santri'] ?? 0) > 0)
                                <div class="btn-group me-1">
                                    <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-printer-line me-1"></i> Cetak Rapor Massal (1 PDF)
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><h6 class="dropdown-header">SAS — Nilai Akhir</h6></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'id-ar']) }}">Bilingual (ID + AR)</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><h6 class="dropdown-header">STS — Tengah Semester</h6></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'id-ar']) }}">Bilingual (ID + AR)</a></li>
                                    </ul>
                                </div>
                            @else
                                <span class="badge bg-warning-subtle text-warning me-1">
                                    <i class="ri-information-line me-1"></i>Belum ada santri pada TA/semester ini
                                </span>
                            @endif
                            <a href="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" style="font-size:12px;">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $selectedAyId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Semester</label>
                            <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="ganjil" {{ $selectedSem == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ $selectedSem == 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Cari Nama</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Nama Santri..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                        </div>
                    </form>

                    {{-- Student List --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze mb-0" style="font-size:13px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>NIS</th>
                                    <th>Nama Santri</th>
                                    <th style="width:75px;text-align:center;">Nilai</th>
                                    <th style="width:90px;text-align:center;">Predikat</th>
                                    <th style="width:70px;text-align:center;">Peringkat</th>
                                    <th style="min-width:220px;">Catatan Wali Kelas</th>
                                    <th style="width:120px;text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNum = 0; @endphp
                                @forelse($students as $history)
                                    @php
                                        $student = $history->student;
                                        if (request('search') && stripos($student->name, request('search')) === false)
                                            continue;
                                    @endphp
                                    @php
                                        $rowNum++;
                                        $summary = $summaries[$student->id] ?? ['nilai' => null, 'predikat' => '—', 'rank' => null];
                                        $predicateClass = match ($summary['predikat']) {
                                            'Mumtaz Murtafi\'', 'Mumtaz' => 'success',
                                            'Jayyid Jiddan' => 'info',
                                            'Jayyid' => 'warning',
                                            'Maqbul' => 'secondary',
                                            'Roosib' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted">{{ $rowNum }}</td>
                                        <td class="text-center">{{ $student->nis ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-primary text-white" style="font-size:10px;">
                                                        {{ strtoupper(mb_substr(($__w = preg_split('/\s+/', trim((string) $student->name)))[0] ?? '', 0, 1) . (count($__w) > 1 ? mb_substr($__w[count($__w) - 1], 0, 1) : mb_substr($__w[0] ?? '', 1, 1))) }}
                                                    </span>
                                                </div>
                                                <span class="fw-semibold">{{ $student->name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center fw-semibold">
                                            {{ $summary['nilai'] !== null ? number_format((float) $summary['nilai'], 1) : '—' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $predicateClass }}-subtle text-{{ $predicateClass }}">
                                                {{ $summary['predikat'] ?: '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($summary['rank'])
                                                <span class="badge bg-primary-subtle text-primary">#{{ $summary['rank'] }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $noteAction = route('user.schools.nilai-kelas.rapor.note', [
                                                    'userId' => $userId,
                                                    'studyGroupId' => $studyGroup->id,
                                                    'studentId' => $student->id,
                                                    'academic_year_id' => $selectedAyId,
                                                    'semester' => $selectedSem,
                                                ]);
                                                $noteValue = $registrations[$student->id]->homeroom_note ?? '';
                                            @endphp
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control js-note-inline"
                                                       value="{{ $noteValue }}"
                                                       maxlength="500"
                                                       placeholder="Catatan wali kelas…"
                                                       data-action="{{ $noteAction }}"
                                                       aria-label="Catatan {{ $student->name }}">
                                                <button type="button" class="btn btn-outline-secondary js-note"
                                                        title="Buka editor catatan"
                                                        data-name="{{ $student->name }}"
                                                        data-note="{{ $noteValue }}"
                                                        data-action="{{ $noteAction }}">
                                                    <i class="ri-expand-diagonal-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-printer-line me-1"></i> Rapor
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><h6 class="dropdown-header">STS — Tengah Semester</h6></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'id-ar']) }}">Bilingual (ID + AR)</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><h6 class="dropdown-header">SAS — Nilai Akhir</h6></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'id-ar']) }}">Bilingual (ID + AR)</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="ri-group-line me-1"></i>Tidak ada Santri.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 order-1">
            @php
                $previewStudent = $students->first()?->student;
                $previewUrl = $previewStudent
                    ? route('user.schools.nilai-kelas.rapor.cetak', [
                        'userId' => $userId,
                        'studyGroupId' => $studyGroup->id,
                        'studentId' => $previewStudent->id,
                        'academic_year_id' => $selectedAyId,
                        'semester' => $selectedSem,
                        'jenis' => $docJenis,
                        'bahasa' => $docBahasa,
                    ])
                    : null;
            @endphp
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h5 class="card-title mb-0"><i class="ri-settings-3-line text-success me-1"></i>Pengaturan &amp; Preview PDF</h5>
                            <p class="text-muted mb-0 small">Pengaturan disimpan per varian dokumen: STS/SAS dan Indonesia/Arab/Bilingual.</p>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @foreach([
                                    ['sts', 'id', 'STS — Indonesia'],
                                    ['sts', 'ar', 'STS — Arab'],
                                    ['sts', 'id-ar', 'STS — Bilingual'],
                                    ['sas', 'id', 'SAS — Indonesia'],
                                    ['sas', 'ar', 'SAS — Arab'],
                                    ['sas', 'id-ar', 'SAS — Bilingual'],
                                ] as [$variantJenis, $variantBahasa, $variantLabel])
                                    <button type="button"
                                            class="btn btn-sm js-variant-settings {{ $docJenis === $variantJenis && $docBahasa === $variantBahasa ? 'btn-primary' : 'btn-outline-secondary' }}"
                                            data-variant-jenis="{{ $variantJenis }}"
                                            data-variant-bahasa="{{ $variantBahasa }}"
                                            data-variant-label="{{ $variantLabel }}">
                                        {{ $variantLabel }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#layoutSettingsModal">
                                <i class="ri-layout-line me-1"></i>Margin, Font &amp; Kertas
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#signatureSettingsModal">
                                <i class="ri-image-line me-1"></i>Kop, TTD &amp; Stempel
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <i class="ri-error-warning-line me-1"></i> Periksa kembali pengaturan tata letak.
                        </div>
                    @endif

                    <div class="alert alert-light border py-2 mb-3 small">
                        <div class="d-flex flex-wrap gap-3">
                            <span><i class="ri-layout-line me-1 text-primary"></i>Margin:
                                {{ $layout['margin_top'] }}/{{ $layout['margin_right'] }}/{{ $layout['margin_bottom'] }}/{{ $layout['margin_left'] }} mm
                            </span>
                            <span><i class="ri-font-size-2 me-1 text-primary"></i>Font isi:
                                {{ $layout['font_size'] }} pt
                            </span>
                            <span><i class="ri-file-paper-2-line me-1 text-primary"></i>Kertas:
                                {{ strtoupper($layout['paper_size']) }}
                            </span>
                            <span class="text-muted">Varian: {{ strtoupper($docJenis) }} — {{ strtoupper($docBahasa) }}</span>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Catatan umum: tercetak pada rapor semua santri --}}
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <h6 class="mb-0 small fw-semibold"><i class="ri-chat-quote-line me-1"></i>Catatan Umum Rapor</h6>
                        <span class="badge bg-secondary-subtle text-secondary">Tercetak di semua rapor</span>
                    </div>
                    <form method="POST"
                          action="{{ route('user.schools.nilai-kelas.rapor.pengaturan', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <input type="hidden" name="distribution_date" value="{{ $distributionSetting?->distribution_date?->format('Y-m-d') }}">
                        <input type="hidden" name="city" value="{{ $distributionSetting?->city ?? $studyGroup->school?->city }}">
                        <textarea name="notes" rows="2" maxlength="500" class="form-control form-control-sm"
                                  placeholder="Mis. Rapor dibagikan bersama rapat wali santri. Kosongkan bila tidak perlu.">{{ old('notes', $distributionSetting?->notes) }}</textarea>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
                            <small class="text-muted">Tampil pada baris catatan di bawah tabel nilai.</small>
                            <button type="submit" class="btn btn-outline-success btn-sm">
                                <i class="ri-save-3-line me-1"></i>Simpan Catatan
                            </button>
                        </div>
                    </form>

                    <hr class="my-3">

                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <h6 class="mb-0 small fw-semibold"><i class="ri-eye-line me-1"></i>Preview PDF</h6>
                        <span class="badge bg-info-subtle text-info">STS / SAS</span>
                    </div>

                    @if($students->isNotEmpty() && $previewStudent)
                        <div class="row g-2 mb-2">
                            <div class="col-12">
                                <select id="previewStudent" class="form-select form-select-sm">
                                    @foreach($students as $history)
                                        @if($history->student)
                                            <option value="{{ $history->student->id }}">{{ $history->student->name }} — {{ $history->student->nis ?? $history->student->nisn ?? '-' }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-5">
                                <select id="previewJenis" class="form-select form-select-sm">
                                    <option value="sts" @selected($docJenis === 'sts')>STS</option>
                                    <option value="sas" @selected($docJenis === 'sas')>SAS</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <select id="previewBahasa" class="form-select form-select-sm">
                                    <option value="id" @selected($docBahasa === 'id')>Indonesia</option>
                                    <option value="ar" @selected($docBahasa === 'ar')>Arab</option>
                                    <option value="id-ar" @selected($docBahasa === 'id-ar')>Bilingual</option>
                                </select>
                            </div>
                            <div class="col-3">
                                <button type="button" id="previewRefresh" class="btn btn-outline-primary btn-sm w-100" title="Muat ulang preview">
                                    <i class="ri-refresh-line"></i>
                                </button>
                            </div>
                        </div>
                        <div class="border rounded overflow-hidden bg-light" style="height:620px;">
                            <iframe id="raporPreview"
                                    src="{{ $previewUrl }}"
                                    title="Preview PDF rapor"
                                    style="width:100%;height:100%;border:0;background:#fff;"
                                    loading="lazy"></iframe>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted">Preview memakai pengaturan yang sudah disimpan.</small>
                            <a href="{{ $previewUrl }}" target="_blank" id="previewOpen" class="btn btn-outline-success btn-sm">
                                <i class="ri-external-link-line me-1"></i>Buka PDF
                            </a>
                        </div>
                    @else
                        <div class="alert alert-warning py-2 mb-0 small">
                            <i class="ri-information-line me-1"></i>Belum ada santri pada TA/semester ini, preview belum tersedia.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Mapel yang masuk rapor: berdampingan dengan pengaturan --}}
        <div class="col-xl-6 order-1">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h5 class="card-title mb-0"><i class="ri-book-open-line text-success me-1"></i>Mapel yang Masuk Rapor &amp; Urutannya</h5>
                            <p class="text-muted mb-0 small">Centang mapel yang dicetak, ubah nama Latin/Arab, KKTP, dan bobot, lalu atur urutannya.</p>
                        </div>
                        <span class="badge bg-primary-subtle text-primary">{{ count($selectedSubjectIds) }} mapel dipilih</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($availableSubjects->isEmpty())
                        <div class="alert alert-warning py-2 mb-0 small">
                            <i class="ri-information-line me-1"></i>Belum ada buku/mapel aktif untuk rombel dan semester ini.
                        </div>
                    @else
                        <form method="POST"
                              action="{{ route('user.schools.nilai-kelas.rapor.subjects', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">
                            @csrf
                            <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                            <input type="hidden" name="semester" value="{{ $selectedSem }}">

                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:60px;" class="text-center">Masuk</th>
                                            <th style="min-width:240px;">Mata Pelajaran <span class="text-muted fw-normal">(Latin &amp; Arab)</span></th>
                                            <th style="width:100px;" class="text-center">KKTP</th>
                                            <th style="width:110px;" class="text-center">Bobot <span class="text-muted fw-normal">(SAS)</span></th>
                                            <th style="width:120px;" class="text-center">Urutan</th>
                                        </tr>
                                    </thead>
                                    <tbody id="subjectOrderBody">
                                        @foreach($availableSubjects as $subject)
                                            @php
                                                $isSelected = in_array($subject->id, $selectedSubjectIds, true);
                                                $kktpRow = ($subjectKktpMap ?? [])[$subject->id] ?? ['kktp_id' => null, 'score' => null];
                                                $kktpVal = $kktpRow['score'] !== null
                                                    ? rtrim(rtrim(number_format((float) $kktpRow['score'], 2, '.', ''), '0'), '.')
                                                    : '';
                                            @endphp
                                            <tr class="subject-row" data-subject-id="{{ $subject->id }}">
                                                <td class="text-center">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="subject_ids[]" value="{{ $subject->id }}"
                                                           id="subject-{{ $subject->id }}" @checked($isSelected)>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column gap-1">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text" style="width:42px;">ID</span>
                                                            <input type="text" class="form-control"
                                                                   name="subject_name[{{ $subject->id }}]"
                                                                   value="{{ old('subject_name.'.$subject->id, $subject->name) }}"
                                                                   maxlength="255" aria-label="Nama mapel Latin">
                                                        </div>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text" style="width:42px;">AR</span>
                                                            <input type="text" class="form-control" dir="rtl"
                                                                   name="subject_name_ar[{{ $subject->id }}]"
                                                                   value="{{ old('subject_name_ar.'.$subject->id, $subject->name_ar) }}"
                                                                   maxlength="255" placeholder="Nama Arab (opsional)" aria-label="Nama mapel Arab">
                                                        </div>
                                                        @if($subject->category)
                                                            <small class="text-muted">{{ $subject->category }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <input type="text" inputmode="decimal"
                                                           name="subject_kktp[{{ $subject->id }}]"
                                                           value="{{ old('subject_kktp.'.$subject->id, $kktpVal) }}"
                                                           placeholder="—"
                                                           class="form-control form-control-sm text-center mx-auto"
                                                           style="max-width:80px;"
                                                           aria-label="KKTP {{ $subject->name }}">
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $bobotRow = ($subjectBobotMap ?? [])[$subject->id] ?? ['bobot' => null, 'jp' => null, 'efektif' => null];
                                                        $bobotVal = $bobotRow['bobot'] !== null
                                                            ? rtrim(rtrim(number_format((float) $bobotRow['bobot'], 2, '.', ''), '0'), '.')
                                                            : '';
                                                    @endphp
                                                    <input type="text" inputmode="decimal"
                                                           name="subject_bobot[{{ $subject->id }}]"
                                                           value="{{ old('subject_bobot.'.$subject->id, $bobotVal) }}"
                                                           placeholder="{{ $bobotRow['jp'] !== null ? rtrim(rtrim(number_format((float) $bobotRow['jp'], 2, '.', ''), '0'), '.') : '0' }}"
                                                           class="form-control form-control-sm text-center mx-auto"
                                                           style="max-width:90px;"
                                                           aria-label="Bobot {{ $subject->name }}">
                                                    <small class="text-muted d-block" style="font-size:10px;">
                                                        JP: {{ $bobotRow['jp'] !== null ? rtrim(rtrim(number_format((float) $bobotRow['jp'], 2, '.', ''), '0'), '.') : '—' }}
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <input type="hidden" name="subject_order[{{ $subject->id }}]"
                                                           class="subject-order-input" value="{{ $loop->iteration }}">
                                                    <div class="d-inline-flex align-items-center gap-1">
                                                        <span class="badge bg-light text-dark subject-order-label">{{ $loop->iteration }}</span>
                                                        <div class="btn-group btn-group-sm" role="group" aria-label="Atur urutan {{ $subject->name }}">
                                                            <button type="button" class="btn btn-outline-secondary subject-move-up"
                                                                    onclick="moveSubjectRow(this, -1)" title="Naikkan">
                                                                <i class="ri-arrow-up-line"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-secondary subject-move-down"
                                                                    onclick="moveSubjectRow(this, 1)" title="Turunkan">
                                                                <i class="ri-arrow-down-line"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <small class="text-muted"><i class="ri-information-line me-1"></i>Minimal satu mapel harus dipilih. Bobot kosong = memakai JP rombel; boleh desimal (mis. 2,5). Nama Latin/Arab disimpan ke master mapel sehingga berlaku untuk semua rombel. KKTP kosong = tidak diubah. Urutan mengikuti posisi baris.</small>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ri-save-3-line me-1"></i>Simpan Mapel, Nama, KKTP &amp; Bobot
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Pengaturan Margin, Font & Kertas --}}
    <div class="modal fade" id="layoutSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="layoutSettingsForm" method="POST"
                      action="{{ route('user.schools.nilai-kelas.rapor.layout', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">
                    @csrf
                    <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                    <input type="hidden" name="semester" value="{{ $selectedSem }}">
                    <input type="hidden" name="doc_jenis" value="{{ $docJenis }}">
                    <input type="hidden" name="doc_bahasa" value="{{ $docBahasa }}">

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-layout-line me-2 text-primary"></i>Margin, Font &amp; Kertas <span id="layoutVariantLabel" class="text-muted fw-normal small"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Pengaturan untuk varian <strong>{{ strtoupper($docJenis) }} — {{ strtoupper($docBahasa) }}</strong>.
                        </p>

                        <h6 class="mb-2 small fw-semibold"><i class="ri-layout-line me-1"></i>Margin (mm)</h6>
                        <div class="row g-2 mb-3">
                            @foreach(['margin_top' => 'Atas', 'margin_right' => 'Kanan', 'margin_bottom' => 'Bawah', 'margin_left' => 'Kiri'] as $field => $label)
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1">{{ $label }}</label>
                                    <input type="number" name="{{ $field }}" class="form-control form-control-sm"
                                           min="0" max="50" step="0.5" required
                                           value="{{ old($field, $layout[$field]) }}">
                                </div>
                            @endforeach
                        </div>

                        <h6 class="mb-2 small fw-semibold"><i class="ri-font-size-2 me-1"></i>Ukuran Font (pt) &amp; Baris</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Isi</label>
                                <input type="number" name="font_size" class="form-control form-control-sm" min="6" max="24" step="0.5" required value="{{ old('font_size', $layout['font_size']) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Judul</label>
                                <input type="number" name="title_font_size" class="form-control form-control-sm" min="8" max="30" step="0.5" required value="{{ old('title_font_size', $layout['title_font_size']) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Tabel</label>
                                <input type="number" name="table_font_size" class="form-control form-control-sm" min="6" max="20" step="0.5" required value="{{ old('table_font_size', $layout['table_font_size']) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Info Santri</label>
                                <input type="number" name="info_font_size" class="form-control form-control-sm" min="6" max="20" step="0.5" required value="{{ old('info_font_size', $layout['info_font_size']) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Footer/TTD</label>
                                <input type="number" name="footer_font_size" class="form-control form-control-sm" min="5" max="16" step="0.5" required value="{{ old('footer_font_size', $layout['footer_font_size']) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Tinggi Baris Teks</label>
                                <input type="number" name="line_height" class="form-control form-control-sm" min="1" max="2.5" step="0.05" required value="{{ old('line_height', $layout['line_height']) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Line Height Tabel</label>
                                <input type="number" name="table_line_height" class="form-control form-control-sm" min="1" max="2.5" step="0.05" required value="{{ old('table_line_height', $layout['table_line_height'] ?? 1.05) }}">
                                <div class="form-text">Jarak antarbaris teks di dalam tabel nilai.</div>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Jarak Baris Santri (mm)</label>
                                <input type="number" name="table_row_padding" class="form-control form-control-sm" min="0" max="3" step="0.05" required value="{{ old('table_row_padding', $layout['table_row_padding'] ?? 0.5) }}">
                                <div class="form-text">Padding atas-bawah tiap baris data santri.</div>
                            </div>
                        </div>

                        <h6 class="mb-2 small fw-semibold"><i class="ri-table-line me-1"></i>Gaya Tabel</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Tebal Garis (mm)</label>
                                <input type="number" name="table_border_width" class="form-control form-control-sm"
                                       min="0" max="2" step="0.05" required
                                       value="{{ old('table_border_width', $layout['table_border_width'] ?? 0.3) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1 d-block">Teks</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="bold_table_header" value="1"
                                           id="boldTableHeader" @checked(old('bold_table_header', $layout['bold_table_header'] ?? true))>
                                    <label class="form-check-label small" for="boldTableHeader">Judul tebal</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1 d-block">&nbsp;</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="bold_subject" value="1"
                                           id="boldSubject" @checked(old('bold_subject', $layout['bold_subject'] ?? true))>
                                    <label class="form-check-label small" for="boldSubject">Nama mapel tebal</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Ukuran Kertas</label>
                                <select name="paper_size" class="form-select form-select-sm" required>
                                    <option value="a4" {{ old('paper_size', $layout['paper_size']) === 'a4' ? 'selected' : '' }}>A4</option>
                                    <option value="folio" {{ old('paper_size', $layout['paper_size']) === 'folio' ? 'selected' : '' }}>F4 / Folio</option>
                                    <option value="letter" {{ old('paper_size', $layout['paper_size']) === 'letter' ? 'selected' : '' }}>Letter</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Footer Kiri</label>
                                <input type="text" name="footer_left" class="form-control form-control-sm" maxlength="120"
                                       value="{{ old('footer_left', $layout['footer_left']) }}"
                                       placeholder="{{ $docBahasa === 'id-ar' ? 'PAH-KSP-FORM-14-04' : 'PAH-KSP-FORM-14-02' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Footer Tengah</label>
                                <input type="text" name="footer_center" class="form-control form-control-sm" maxlength="120"
                                       value="{{ old('footer_center', $layout['footer_center']) }}"
                                       placeholder="REV.01 / 10 September 2025">
                            </div>
                            <div class="col-12">
                                <div class="form-text">Nomor halaman di kanan footer tetap otomatis. Kosongkan untuk memakai teks bawaan.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="ri-save-3-line me-1"></i>Simpan Tata Letak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Pengaturan Kop, TTD & Stempel --}}
    <div class="modal fade" id="signatureSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-image-line me-2 text-primary"></i>Pengaturan Kop, TTD &amp; Stempel <span id="mediaVariantLabel" class="text-muted fw-normal small"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">Tampilan &amp; Ukuran Gambar (mm)</h6>
                            <p class="text-muted mb-0 small">Atur gambar yang tampil pada PDF rapor. Upload TTD KSP dan stempel tetap di menu Sekolah.</p>
                        </div>
                        <a href="{{ route('user.schools.edit', ['userId' => $userId, 'schoolId' => $studyGroup->school_id]) }}"
                           target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="ri-settings-4-line me-1"></i>Kelola TTD KSP &amp; Stempel
                        </a>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-{{ $studyGroup->school?->ttd_ksp_path ? 'success' : 'secondary' }}-subtle text-{{ $studyGroup->school?->ttd_ksp_path ? 'success' : 'secondary' }}">
                            TTD KSP: {{ $studyGroup->school?->ttd_ksp_path ? 'tersedia' : 'belum ada' }}
                        </span>
                        <span class="badge bg-{{ $studyGroup->school?->stamp_path ? 'success' : 'secondary' }}-subtle text-{{ $studyGroup->school?->stamp_path ? 'success' : 'secondary' }}">
                            Stempel: {{ $studyGroup->school?->stamp_path ? 'tersedia' : 'belum ada' }}
                        </span>
                        <span class="badge bg-{{ $studyGroup->homeroomTeacher?->ttd_path ? 'success' : 'secondary' }}-subtle text-{{ $studyGroup->homeroomTeacher?->ttd_path ? 'success' : 'secondary' }}">
                            TTD wali: {{ $studyGroup->homeroomTeacher?->ttd_path ? 'tersedia' : 'belum ada' }}
                        </span>
                    </div>

                    <form id="raporMediaForm" method="POST"
                          action="{{ route('user.schools.nilai-kelas.rapor.media', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <input type="hidden" name="doc_jenis" value="{{ $docJenis }}">
                        <input type="hidden" name="doc_bahasa" value="{{ $docBahasa }}">

                        <div class="row g-2 mb-3">
                            @foreach([
                                'show_kop' => 'Tampilkan kop',
                                'show_ttd_ksp' => 'Tampilkan TTD KSP',
                                'show_ttd_wali' => 'Tampilkan TTD wali',
                                'show_stamp' => 'Tampilkan stempel',
                                'show_qr' => 'Tampilkan QR verifikasi',
                            ] as $field => $label)
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="{{ $field }}" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               id="modal_{{ $field }}" name="{{ $field }}" value="1"
                                               {{ old($field, $layout[$field] ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="modal_{{ $field }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text mb-3">
                            QR verifikasi hanya tercetak bila dokumen memiliki verifikasi digital (mode QR/PAdES)
                            dan switch ini aktif. Jika dimatikan, TTD &amp; stempel dicetak sebagai tanda tangan visual.
                        </div>

                        @php
                            $leaderOverride = is_array($studyGroup->school?->document_visual_settings['leader'] ?? null)
                                ? $studyGroup->school->document_visual_settings['leader']
                                : [];
                            $autoLeader = app(\App\Services\Documents\SchoolLeaderService::class)->forSchool($studyGroup->school);
                        @endphp
                        <div class="row g-2 mb-2">
                            <div class="col-md-7">
                                <label class="form-label small mb-1">Nama &amp; gelar kepala sekolah (khusus dokumen)</label>
                                <input type="text" name="document_leader_name" class="form-control form-control-sm"
                                       value="{{ old('document_leader_name', $leaderOverride['name'] ?? $autoLeader['display_name']) }}"
                                       placeholder="Kosongkan untuk otomatis dari data GTK">
                                <div class="form-text">Terisi otomatis dari data GTK. Hapus isi untuk kembali otomatis.</div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small mb-1">NUPY (khusus dokumen)</label>
                                <input type="text" name="document_leader_nupy" class="form-control form-control-sm"
                                       value="{{ old('document_leader_nupy', $leaderOverride['nupy'] ?? $autoLeader['nupy']) }}"
                                       placeholder="Kosongkan untuk otomatis">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Tinggi Kop (mm)</label>
                                <input type="number" name="kop_height" class="form-control form-control-sm" min="5" max="80" step="0.5" required value="{{ old('kop_height', $layout['kop_height']) }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">TTD KSP — Lebar</label>
                                <input type="number" name="ttd_ksp_width" class="form-control form-control-sm" min="5" max="100" step="0.5" required value="{{ old('ttd_ksp_width', $layout['ttd_ksp_width']) }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">TTD KSP — Tinggi</label>
                                <input type="number" name="ttd_ksp_height" class="form-control form-control-sm" min="5" max="60" step="0.5" required value="{{ old('ttd_ksp_height', $layout['ttd_ksp_height']) }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">TTD KSP — Margin (mm)</label>
                                <input type="number" name="ttd_ksp_margin" class="form-control form-control-sm" min="0" max="30" step="0.5" required value="{{ old('ttd_ksp_margin', $layout['ttd_ksp_margin']) }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">TTD Wali — Lebar</label>
                                <input type="number" name="ttd_wali_width" class="form-control form-control-sm" min="5" max="100" step="0.5" required value="{{ old('ttd_wali_width', $layout['ttd_wali_width']) }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">TTD Wali — Tinggi</label>
                                <input type="number" name="ttd_wali_height" class="form-control form-control-sm" min="5" max="60" step="0.5" required value="{{ old('ttd_wali_height', $layout['ttd_wali_height']) }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">TTD Wali — Margin (mm)</label>
                                <input type="number" name="ttd_wali_margin" class="form-control form-control-sm" min="0" max="30" step="0.5" required value="{{ old('ttd_wali_margin', $layout['ttd_wali_margin']) }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Stempel — Lebar</label>
                                <input type="number" name="stamp_width" class="form-control form-control-sm" min="5" max="100" step="0.5" required value="{{ old('stamp_width', $layout['stamp_width']) }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Stempel — Tinggi</label>
                                <input type="number" name="stamp_height" class="form-control form-control-sm" min="5" max="100" step="0.5" required value="{{ old('stamp_height', $layout['stamp_height']) }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Stempel — Margin (mm)</label>
                                <input type="number" name="stamp_margin" class="form-control form-control-sm" min="0" max="30" step="0.5" required value="{{ old('stamp_margin', $layout['stamp_margin']) }}">
                            </div>
                        </div>
                    </form>

                    <hr class="my-4">

                    <h6 class="mb-2"><i class="ri-pen-nib-line me-1"></i>TTD Wali Kelas</h6>
                    @if($studyGroup->homeroomTeacher)
                        @if($studyGroup->homeroomTeacher->ttd_path)
                            <div class="mb-2 p-2 border rounded bg-light d-flex align-items-center gap-2">
                                <img src="{{ asset('storage/'.$studyGroup->homeroomTeacher->ttd_path) }}"
                                     alt="TTD wali kelas" style="max-height:70px;max-width:160px;object-fit:contain;">
                                <small class="text-muted">{{ $studyGroup->homeroomTeacher->name }}</small>
                            </div>
                        @endif
                        <form id="waliTtdForm" method="POST" enctype="multipart/form-data"
                              action="{{ route('user.schools.nilai-kelas.rapor.wali-ttd', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-8">
                                    <label class="form-label small mb-1">Upload / ganti TTD wali kelas</label>
                                    <input type="file" name="ttd_wali_file" class="form-control form-control-sm" accept="image/png,image/jpeg">
                                    <div class="form-text">PNG/JPG, maks. 1 MB. TTD ini dipakai pada rapor kelas ini.</div>
                                </div>
                                @if($studyGroup->homeroomTeacher->ttd_path)
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remove_ttd_wali" value="1" id="remove_ttd_wali">
                                            <label class="form-check-label small text-danger" for="remove_ttd_wali">Hapus TTD wali yang tersimpan</label>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </form>
                    @else
                        <div class="alert alert-warning py-2 mb-0 small">
                            <i class="ri-information-line me-1"></i>Rombel ini belum memiliki wali kelas. TTD tidak dapat diunggah.
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    @if($studyGroup->homeroomTeacher)
                        <button type="submit" form="waliTtdForm" class="btn btn-outline-primary">
                            <i class="ri-upload-2-line me-1"></i>Upload TTD Wali
                        </button>
                    @endif
                    <button type="submit" form="raporMediaForm" class="btn btn-success">
                        <i class="ri-save-3-line me-1"></i>Simpan Pengaturan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Catatan Wali Kelas --}}
    <div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="noteForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-chat-1-line me-1"></i> Catatan Wali Kelas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-2">Santri: <strong id="noteStudent">—</strong></p>
                        <textarea name="homeroom_note" id="noteText" class="form-control" rows="4" maxlength="500"
                                  placeholder="Catatan untuk rapor (maks. 500 karakter)"></textarea>
                        <small class="text-muted">Catatan ini tampil pada bagian "Catatan Wali Kelas" di Rapor.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function renumberSubjectRows() {
    const body = document.getElementById('subjectOrderBody');
    if (!body) return;

    const rows = Array.from(body.querySelectorAll('.subject-row'));

    rows.forEach(function (row, index) {
        const input = row.querySelector('.subject-order-input');
        const label = row.querySelector('.subject-order-label');
        const up = row.querySelector('.subject-move-up');
        const down = row.querySelector('.subject-move-down');

        if (input) input.value = index + 1;
        if (label) label.textContent = index + 1;
        if (up) up.disabled = index === 0;
        if (down) down.disabled = index === rows.length - 1;
    });
}

function moveSubjectRow(button, direction) {
    const row = button.closest('.subject-row');
    if (!row) return;

    if (direction < 0 && row.previousElementSibling) {
        row.parentNode.insertBefore(row, row.previousElementSibling);
    } else if (direction > 0 && row.nextElementSibling) {
        row.parentNode.insertBefore(row.nextElementSibling, row);
    }

    renumberSubjectRows();
}

document.addEventListener('DOMContentLoaded', renumberSubjectRows);
renumberSubjectRows();

(function () {
    const variantLayouts = @json($variantLayouts);
    const layoutForm = document.getElementById('layoutSettingsForm');
    const mediaForm = document.getElementById('raporMediaForm');
    const layoutVariantLabel = document.getElementById('layoutVariantLabel');
    const mediaVariantLabel = document.getElementById('mediaVariantLabel');
    const labels = {
        'sts:id': 'STS — Indonesia',
        'sts:ar': 'STS — Arab',
        'sts:id-ar': 'STS — Bilingual',
        'sas:id': 'SAS — Indonesia',
        'sas:ar': 'SAS — Arab',
        'sas:id-ar': 'SAS — Bilingual',
    };

    function setFormValue(form, key, value) {
        if (!form) return;
        const checkbox = form.querySelector('input[type="checkbox"][name="' + key + '"]');
        const field = checkbox || form.querySelector('[name="' + key + '"]');
        if (!field) return;
        if (field.type === 'checkbox') {
            field.checked = Boolean(value);
        } else {
            field.value = value ?? '';
        }
    }

    function applyVariant(jenis, bahasa) {
        const key = jenis + ':' + bahasa;
        const layout = variantLayouts[key] || {};

        [layoutForm, mediaForm].forEach(function (form) {
            if (!form) return;
            setFormValue(form, 'doc_jenis', jenis);
            setFormValue(form, 'doc_bahasa', bahasa);
        });

        [
            'margin_top', 'margin_right', 'margin_bottom', 'margin_left',
            'font_size', 'title_font_size', 'table_font_size', 'info_font_size',
            'footer_font_size', 'line_height', 'table_line_height', 'table_row_padding', 'paper_size',
            'footer_left', 'footer_center', 'bold_table_header', 'bold_subject',
            'show_kop', 'show_ttd_ksp', 'show_ttd_wali', 'show_stamp', 'show_qr',
            'kop_height', 'ttd_ksp_width', 'ttd_ksp_height',
            'ttd_wali_width', 'ttd_wali_height', 'stamp_width', 'stamp_height',
            'ttd_ksp_margin', 'ttd_wali_margin', 'stamp_margin',
        ].forEach(function (key) {
            if (layoutForm && layoutForm.querySelector('[name="' + key + '"]')) {
                setFormValue(layoutForm, key, layout[key]);
            }
            if (mediaForm && mediaForm.querySelector('[name="' + key + '"]')) {
                setFormValue(mediaForm, key, layout[key]);
            }
        });

        if (layoutVariantLabel) layoutVariantLabel.textContent = labels[key] || key;
        if (mediaVariantLabel) mediaVariantLabel.textContent = labels[key] || key;
    }

    document.querySelectorAll('.js-variant-settings').forEach(function (button) {
        button.addEventListener('click', function () {
            applyVariant(this.dataset.variantJenis, this.dataset.variantBahasa);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('layoutSettingsModal')).show();
        });
    });

    window.applyRaporVariant = applyVariant;
})();

@if($errors->any() && (old('kop_height') !== null || $errors->has('ttd_wali_file')))
    new bootstrap.Modal(document.getElementById('signatureSettingsModal')).show();
@endif

@if($errors->any() && old('margin_top') !== null)
    new bootstrap.Modal(document.getElementById('layoutSettingsModal')).show();
@endif

document.querySelectorAll('.js-note').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('noteForm').action = btn.dataset.action;
        document.getElementById('noteStudent').textContent = btn.dataset.name || '—';
        document.getElementById('noteText').value = btn.dataset.note || '';
        new bootstrap.Modal(document.getElementById('noteModal')).show();
    });
});

// Simpan catatan langsung dari tabel (tanpa membuka modal).
document.querySelectorAll('.js-note-inline').forEach(function (input) {
    var original = input.value;
    var saving = false;

    function simpan() {
        if (saving || input.value === original) return;

        saving = true;
        input.classList.add('border-warning');

        var body = new FormData();
        body.append('_token', '{{ csrf_token() }}');
        body.append('homeroom_note', input.value);

        fetch(input.dataset.action, {
            method: 'POST',
            body: body,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (res) {
            if (!res.ok) throw new Error('gagal');
            return res.json();
        }).then(function () {
            original = input.value;
            input.classList.remove('border-warning');
            input.classList.add('border-success');
            setTimeout(function () { input.classList.remove('border-success'); }, 1500);
        }).catch(function () {
            input.classList.remove('border-warning');
            input.classList.add('border-danger');
            input.title = 'Catatan gagal disimpan. Coba lagi.';
        }).finally(function () {
            saving = false;
        });
    }

    input.addEventListener('change', simpan);
    input.addEventListener('blur', simpan);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
    });
});

(function () {
    const iframe = document.getElementById('raporPreview');
    const student = document.getElementById('previewStudent');
    const jenis = document.getElementById('previewJenis');
    const bahasa = document.getElementById('previewBahasa');
    const open = document.getElementById('previewOpen');
    const refresh = document.getElementById('previewRefresh');

    if (!iframe || !student) return;

    const baseUrl = @json($previewBaseUrl);
    const academicYearId = @json($selectedAyId);
    const semester = @json($selectedSem);

    function buildPreviewUrl() {
        if (!student.value) return '';

        const url = new URL(baseUrl.replace('__STUDENT__', encodeURIComponent(student.value)));
        if (academicYearId) url.searchParams.set('academic_year_id', academicYearId);
        if (semester) url.searchParams.set('semester', semester);
        url.searchParams.set('jenis', jenis.value || 'sts');
        url.searchParams.set('bahasa', bahasa.value || 'id');

        return url.toString();
    }

    function refreshPreview() {
        const url = buildPreviewUrl();
        if (!url) return;
        iframe.src = url;
        if (open) open.href = url;
    }

    [student, jenis, bahasa].forEach(function (el) {
        if (el) el.addEventListener('change', refreshPreview);
    });
    if (refresh) refresh.addEventListener('click', refreshPreview);
})();
</script>
@endpush
